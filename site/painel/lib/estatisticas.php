<?php
/* =====================================================================
   Painel Balanças.com — "Mais procurados" (contagem anônima de acessos)
   ---------------------------------------------------------------------
   O site (linha-verde/js/core.js) manda um aviso pequeno para
   painel/contar.php a cada página vista, produto aberto no catálogo e
   clique no WhatsApp ou em "Ligar". Aqui ficam:
   - a conferência de cada aviso (listas fechadas, código do produto que
     existe no catálogo atual, robôs de fora);
   - o limite por visitante e por rede SEM guardar o IP: códigos curtos
     HMAC(IP + navegador) e HMAC(rede) com uma chave que troca todo dia; a
     lista do dia é apagada na virada do dia;
   - os contadores por dia, somados por mês na pasta privada
     (estatisticas/AAAA-MM.php, JSON depois do PREFIXO_PRIVADO), guardados
     por 24 meses;
   - a leitura e o resumo para a tela "Mais procurados" (a=procurados).
   Nada de cookies, nada de dados pessoais: só "quantos", por dia.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

// O que o site conta (t)
const EST_TIPOS = ['pagina', 'produto', 'whatsapp', 'ligar'];
// Páginas do site (p): a classe pg-<p> do <body> de cada página
const EST_PAGINAS = [
    'inicio'      => 'Página inicial',
    'produtos'    => 'Produtos',
    'assistencia' => 'Assistência técnica',
    'sistema'     => 'Sistema (BC System)',
    'empresa'     => 'Empresa',
    'contato'     => 'Contato',
];
// De onde veio o clique no WhatsApp ou em "Ligar" (o): lugar do botão na página, em palavras simples
const EST_ORIGENS = [
    'topo'        => 'Botão do topo',
    'menu'        => 'Menu do celular',
    'hero'        => 'Abertura da página',
    'flutuante'   => 'Botão flutuante',
    'chamada'     => 'Chamada no fim da página',
    'rodape'      => 'Rodapé',
    'cartao'      => 'Cartão do produto',
    'ficha'       => 'Ficha do produto',
    'kit'         => 'Kits por tipo de comércio',
    'quadros'     => 'Quadros da página inicial',
    'naoachou'    => '“Não achou?” do catálogo',
    'sat'         => 'Aviso do SAT',
    'ipem'        => 'Seção IPEM',
    'faq'         => 'Perguntas frequentes',
    'recursos'    => 'Seção Recursos',
    'informatica' => 'Suporte em informática',
    'fichas'      => 'BC Fichas',
    'assunto'     => 'Escolha do assunto',
    'canais'      => 'Canais de contato',
    'formulario'  => 'Formulário de contato',
    'secao'       => 'Outro ponto da página',
];
// Lugares que são de um produto só (o aviso traz o código do produto)
const EST_ORIGENS_PRODUTO = ['cartao', 'ficha'];

const EST_MAX_BYTES = 1024;          // tamanho máximo do aviso
const EST_MAX_DIA = 120;             // avisos por visitante por dia
const EST_MAX_MINUTO = 30;           // avisos por visitante por minuto
const EST_MAX_REDE_DIA = 300;        // avisos por dia vindos da mesma rede (o IP; no IPv6, o prefixo /64)
const EST_MAX_REDE_NAVEGADORES = 20; // navegadores diferentes por dia na mesma rede (trocar o navegador não fura o limite)
const EST_MAX_VISITANTES = 5000;     // visitantes (e redes) guardados por dia: o tamanho máximo da lista do dia
const EST_MAX_LOTADO_MINUTO = 20;    // lista do dia cheia (enxurrada de robôs): avisos por minuto, somando todos os que não cabem mais nela
const EST_MESES = 24;                // meses guardados (12 meses + os 12 anteriores, para comparar)
const EST_PASTA = 'estatisticas';
const RE_EST_MES = '/^(\d{4})-(\d{2})\.php$/';
const RE_EST_DIA = '/^\d{4}-\d{2}-\d{2}$/';
// Navegador de robô (buscadores, prévias de link, testes automáticos, ferramentas de linha de comando)
const RE_EST_ROBO = '~bot|crawl|spider|slurp|preview|headless|lighthouse|pagespeed|gtmetrix|pingdom|uptime|phantomjs|selenium|puppeteer|playwright|python|curl/|wget/|httpclient|okhttp|go-http|java/|libwww|scrapy|node-fetch|axios|facebookexternalhit~i';

/* ------------------------------------------------------------------
   Conferência do aviso
   ------------------------------------------------------------------ */
/** Navegador vazio ou de robô? */
function ehRobo(string $ua): bool
{
    return trim($ua) === '' || strlen($ua) > 600 || (bool) preg_match(RE_EST_ROBO, $ua);
}

/**
 * Confere o aviso que o site mandou ({t, p, id, o}, todos texto). Devolve [t, p, id, o] ou null.
 * $ids: códigos do catálogo atual (como chaves).
 */
function eventoDoSite($d, array $ids): ?array
{
    if (!is_array($d) || array_is_list($d) || count($d) !== 4) return null;
    foreach (['t', 'p', 'id', 'o'] as $k) if (!is_string($d[$k] ?? null) || strlen($d[$k]) > 80) return null;
    [$t, $p, $id, $o] = [$d['t'], $d['p'], $d['id'], $d['o']];
    if (!in_array($t, EST_TIPOS, true) || !isset(EST_PAGINAS[$p])) return null;
    if ($o !== '' && !isset(EST_ORIGENS[$o])) return null;
    if ($id !== '' && (!preg_match(RE_ID, $id) || !isset($ids[$id]))) return null;
    switch ($t) {
        case 'pagina':     // página vista: só a página
            return $id === '' && $o === '' ? [$t, $p, '', ''] : null;
        case 'produto':    // produto aberto: a ficha do catálogo (produtos.html#p=…)
            return $id !== '' && $o === '' && $p === 'produtos' ? [$t, $p, $id, ''] : null;
        default:           // whatsapp / ligar: sempre com o lugar; com produto só nos lugares de um produto
            if ($o === '') return null;
            $doProduto = in_array($o, EST_ORIGENS_PRODUTO, true);
            if ($doProduto !== ($id !== '')) return null;
            return [$t, $p, $id, $o];
    }
}

/**
 * Código curto do visitante: HMAC(código da rede + navegador, chave do dia), só 4 letras (16 bits).
 * Do IP, a lista do dia guarda só os 16 bits do código da rede: muitos IPs dão o mesmo código, então
 * nem com a chave do dia dá para saber quem visitou (dois visitantes com o mesmo código só dividem o
 * limite). A chave e a lista somem na virada do dia.
 */
function chaveDoVisitante(string $ip, string $ua, string $segredo): string
{
    return substr(hash_hmac('sha256', chaveDaRede($ip, $segredo) . "\n" . $ua, $segredo), 0, 4);
}

/** A rede do IP: o próprio IPv4; no IPv6, o prefixo /64 (um aparelho troca o fim do endereço à vontade). */
function redeDoIp(string $ip): string
{
    $bin = str_contains($ip, ':') ? @inet_pton($ip) : false;
    if (!is_string($bin) || strlen($bin) !== 16) return $ip;
    if (substr($bin, 0, 12) === str_repeat("\0", 10) . "\xff\xff") return (string) inet_ntop(substr($bin, 12));   // ::ffff:1.2.3.4 = IPv4
    return bin2hex(substr($bin, 0, 8)) . '/64';
}

/** Código curto da rede (4 letras, 16 bits): HMAC(IP, ou o /64 no IPv6; chave do dia). É o limite que trocar de navegador não fura. */
function chaveDaRede(string $ip, string $segredo): string
{
    return substr(hash_hmac('sha256', "rede\n" . redeDoIp($ip), $segredo), 0, 4);
}

/* ------------------------------------------------------------------
   Arquivos (pasta privada estatisticas/)
   ------------------------------------------------------------------ */
function arquivoEstatisticas(string $nome): string
{
    return EST_PASTA . '/' . $nome;
}

/** Dia (AAAA-MM-DD) no fuso do painel. */
function diaEstatisticas(int $ts): string
{
    return (new DateTimeImmutable('@' . $ts))->setTimezone(new DateTimeZone((string) (cfg('fuso') ?: 'America/Sao_Paulo')))->format('Y-m-d');
}

/**
 * Chave do dia para o limite por visitante. Na virada do dia: chave nova (a velha é descartada),
 * a lista de visitantes do dia anterior é apagada e os meses velhos saem. Chamar com a trava.
 */
function segredoDoDia(string $dia): string
{
    $s = lerJsonPrivado(arquivoEstatisticas('segredo.php'));
    if (is_array($s) && ($s['dia'] ?? '') === $dia && is_string($s['chave'] ?? null) && strlen($s['chave']) === 64) return $s['chave'];
    $chave = bin2hex(random_bytes(32));
    gravarJsonPrivado(arquivoEstatisticas('segredo.php'), ['dia' => $dia, 'chave' => $chave]);
    podarEstatisticas($dia);
    return $chave;
}

/** Apaga as listas de visitantes que não são de hoje e os meses além dos 24 guardados. */
function podarEstatisticas(string $dia): void
{
    $dir = pastaPrivada() . '/' . EST_PASTA;
    foreach (glob($dir . '/visitantes-*.php') ?: [] as $a) {
        if (basename($a) !== 'visitantes-' . $dia . '.php') @unlink($a);
    }
    $limite = mesesAtras(substr($dia, 0, 7), EST_MESES - 1);   // o mais antigo que fica (AAAA-MM)
    foreach (glob($dir . '/[0-9][0-9][0-9][0-9]-[0-9][0-9].php') ?: [] as $a) {
        if (preg_match(RE_EST_MES, basename($a), $m) && $m[1] . '-' . $m[2] < $limite) @unlink($a);
    }
}

/** "2026-10" menos N meses → "2024-11". */
function mesesAtras(string $mes, int $n): string
{
    [$a, $m] = array_map('intval', explode('-', $mes));
    $total = $a * 12 + ($m - 1) - $n;
    return sprintf('%04d-%02d', intdiv($total, 12), $total % 12 + 1);
}

/**
 * Códigos do catálogo atual (como chaves), guardados em estatisticas/ids.php e lidos de novo só
 * quando o produtos.js muda (data e tamanho). Na mesma hora, guarda marca + nome de cada produto
 * em estatisticas/nomes.php: a tela mostra o nome de quem já saiu do catálogo.
 */
function idsParaContagem(): array
{
    $arq = arquivoCatalogo();
    clearstatcache(true, $arq);
    $mt = (int) @filemtime($arq);
    $tam = (int) @filesize($arq);
    $c = lerJsonPrivado(arquivoEstatisticas('ids.php'));
    if (is_array($c) && ($c['mtime'] ?? -1) === $mt && ($c['tamanho'] ?? -1) === $tam && is_array($c['ids'] ?? null)) {
        return array_fill_keys(array_map('strval', $c['ids']), true);
    }
    return comTrava('estatisticas-ids', static function () use ($arq, $mt, $tam): array {
        $src = @file_get_contents($arq);
        if ($src === false) return [];
        try { $cat = interpretarCatalogo($src); } catch (ErroCatalogo $e) { return []; }   // catálogo com problema: não conta produto
        $ids = [];
        $nomes = lerJsonPrivado(arquivoEstatisticas('nomes.php'), []);
        if (!is_array($nomes)) $nomes = [];
        foreach ($cat['produtos'] as $p) {
            $id = (string) $p['id'];
            if (!preg_match(RE_ID, $id)) continue;
            $ids[] = $id;
            $nome = trim((string) ($p['marca'] ?? '') . ' ' . (string) ($p['nome'] ?? ''));
            if ($nome !== '') { unset($nomes[$id]); $nomes[$id] = cortar($nome, 120); }   // o mais recente fica no fim
        }
        if (count($nomes) > 3000) $nomes = array_slice($nomes, -3000, null, true);
        gravarJsonPrivado(arquivoEstatisticas('nomes.php'), (object) $nomes);
        gravarJsonPrivado(arquivoEstatisticas('ids.php'), ['mtime' => $mt, 'tamanho' => $tam, 'ids' => $ids]);
        return array_fill_keys($ids, true);
    });
}

/* ------------------------------------------------------------------
   Contar
   ------------------------------------------------------------------ */
/**
 * Soma um aviso já conferido ([t, p, id, o]) nos contadores do dia, respeitando os limites:
 * - por visitante (código do IP + navegador): 120 por dia, 30 por minuto;
 * - por rede (código do IP, ou do /64 no IPv6): 300 por dia e 20 navegadores diferentes por dia;
 * - lista do dia cheia (5000 códigos: enxurrada de robôs vindos de muitas redes): quem não cabe mais
 *   nela ainda conta, mas no máximo 20 avisos por minuto somando todos, e o dia fica marcado como
 *   "limitado" no arquivo do mês (a tela avisa). Nunca para de contar todo mundo até a virada do dia.
 * Devolve 'ok', 'limite-dia', 'limite-minuto', 'limite-rede' ou 'lotado' (só para os testes: o site
 * recebe sempre 204).
 */
function contarEvento(array $ev, string $ip, string $ua, ?int $agora = null): string
{
    $agora ??= time();
    $dia = diaEstatisticas($agora);
    return comTrava('estatisticas', static function () use ($ev, $ip, $ua, $agora, $dia): string {
        $segredo = segredoDoDia($dia);
        $chave = chaveDoVisitante($ip, $ua, $segredo);
        $chaveRede = chaveDaRede($ip, $segredo);
        $nomeV = arquivoEstatisticas('visitantes-' . $dia . '.php');
        $vis = lerJsonPrivado($nomeV);
        if (!is_array($vis) || ($vis['dia'] ?? '') !== $dia || !is_array($vis['v'] ?? null)) $vis = ['dia' => $dia, 'v' => []];
        if (!is_array($vis['r'] ?? null)) $vis['r'] = [];
        $min = intdiv($agora, 60);
        $nomeM = arquivoEstatisticas(substr($dia, 0, 7) . '.php');
        $mes = lerJsonPrivado($nomeM);
        if (!is_array($mes) || !is_array($mes['dias'] ?? null)) $mes = ['versao' => 1, 'dias' => []];

        // A rede: [avisos hoje, navegadores hoje] (null = a lista de redes do dia está cheia)
        $rede = $vis['r'][$chaveRede] ?? null;
        if (is_array($rede) && count($rede) === 2) $rede = array_map('intval', $rede);
        else $rede = count($vis['r']) < EST_MAX_VISITANTES ? [0, 0] : null;
        if ($rede !== null && $rede[0] >= EST_MAX_REDE_DIA) return 'limite-rede';

        $v = $vis['v'][$chave] ?? null;
        $novo = !is_array($v) || count($v) !== 3;
        if ($novo && $rede !== null && $rede[1] >= EST_MAX_REDE_NAVEGADORES) return 'limite-rede';
        if ($novo && count($vis['v']) >= EST_MAX_VISITANTES) {
            // Lista do dia cheia: não guarda mais ninguém; conta com o limite por minuto somando todos
            $marcar = !in_array($dia, is_array($mes['limitados'] ?? null) ? $mes['limitados'] : [], true);
            if ($marcar) $mes['limitados'] = array_values(array_merge(is_array($mes['limitados'] ?? null) ? $mes['limitados'] : [], [$dia]));
            [$lMin, $lN] = is_array($vis['l'] ?? null) && count($vis['l']) === 2 ? array_map('intval', $vis['l']) : [$min, 0];
            if ($lMin !== $min) { $lMin = $min; $lN = 0; }
            if ($lN >= EST_MAX_LOTADO_MINUTO) {
                if ($marcar) gravarJsonPrivado($nomeM, $mes);
                return 'lotado';
            }
            $vis['l'] = [$lMin, $lN + 1];
        } else {
            [$total, $minuto, $noMinuto] = $novo ? [0, $min, 0] : array_map('intval', $v);
            if ($minuto !== $min) { $minuto = $min; $noMinuto = 0; }
            if ($total >= EST_MAX_DIA) return 'limite-dia';
            if ($noMinuto >= EST_MAX_MINUTO) return 'limite-minuto';
            $vis['v'][$chave] = [$total + 1, $minuto, $noMinuto + 1];
            if ($novo && $rede !== null) $rede[1]++;
        }
        if ($rede !== null) $vis['r'][$chaveRede] = [$rede[0] + 1, $rede[1]];

        $k = implode('|', $ev);
        $mes['dias'][$dia][$k] = (int) ($mes['dias'][$dia][$k] ?? 0) + 1;

        gravarJsonPrivado($nomeM, $mes);
        gravarJsonPrivado($nomeV, $vis);
        return 'ok';
    });
}

/* ------------------------------------------------------------------
   Ler e resumir (tela "Mais procurados")
   ------------------------------------------------------------------ */
/**
 * Contadores dos dias de $de a $ate (AAAA-MM-DD): [dia => ["t|p|id|o" => n]].
 * Em $limitados, os dias do período em que a contagem foi limitada (lista do dia cheia).
 */
function lerEstatisticas(string $de, string $ate, ?array &$limitados = null): array
{
    $out = [];
    $limitados = [];
    $mes = substr($de, 0, 7);
    for ($i = 0; $mes <= substr($ate, 0, 7) && $i < 40; $i++, $mes = mesesAtras($mes, -1)) {
        $d = lerJsonPrivado(arquivoEstatisticas($mes . '.php'));
        if (!is_array($d) || !is_array($d['dias'] ?? null)) continue;
        foreach (is_array($d['limitados'] ?? null) ? $d['limitados'] : [] as $dia) {
            if (is_string($dia) && preg_match(RE_EST_DIA, $dia) && $dia >= $de && $dia <= $ate) $limitados[$dia] = $dia;
        }
        foreach ($d['dias'] as $dia => $cont) {
            if (!is_string($dia) || !preg_match(RE_EST_DIA, $dia) || $dia < $de || $dia > $ate || !is_array($cont)) continue;
            $out[$dia] = $cont;
        }
    }
    ksort($out);
    ksort($limitados);
    $limitados = array_values($limitados);
    return $out;
}

/** Primeiro dia com algum número (AAAA-MM-DD), ou null se o site ainda não contou nada. */
function primeiroDiaContado(): ?string
{
    $meses = [];
    foreach (glob(pastaPrivada() . '/' . EST_PASTA . '/[0-9][0-9][0-9][0-9]-[0-9][0-9].php') ?: [] as $a) {
        if (preg_match(RE_EST_MES, basename($a), $m)) $meses[] = $m[1] . '-' . $m[2];
    }
    sort($meses);
    foreach ($meses as $mes) {
        $d = lerJsonPrivado(arquivoEstatisticas($mes . '.php'));
        $dias = is_array($d['dias'] ?? null) ? array_filter(array_keys($d['dias']), static fn($x) => is_string($x) && preg_match(RE_EST_DIA, $x)) : [];
        if ($dias) { sort($dias); return $dias[0]; }
    }
    return null;
}

/**
 * Soma os dias: totais por tipo, série por dia (ou por mês), produtos, lugares dos cliques e páginas.
 * $dias = lerEstatisticas(); $de/$ate = o período (para a série ter todos os dias, mesmo os zerados).
 */
function resumoEstatisticas(array $dias, string $de, string $ate, bool $porMes = false): array
{
    $r = [
        'total' => array_fill_keys(EST_TIPOS, 0),
        'serie' => [],                 // [rótulo AAAA-MM-DD ou AAAA-MM => whatsapp]
        'produtos' => [],              // [id => ['abertos' => n, 'whatsapp' => n, 'ligar' => n]]
        'lugares' => [],               // ["whatsapp|p|o" => n] e ["ligar|p|o" => n]
        'paginas' => [],               // [p => ['visitas' => n, 'whatsapp' => n, 'ligar' => n]]
    ];
    $utc = new DateTimeZone('UTC');
    $d = new DateTimeImmutable($de, $utc);
    $fim = new DateTimeImmutable($ate, $utc);
    for ($i = 0; $d <= $fim && $i < 800; $i++, $d = $d->modify('+1 day')) {
        $r['serie'][$porMes ? $d->format('Y-m') : $d->format('Y-m-d')] = 0;
    }
    foreach ($dias as $dia => $cont) {
        foreach ($cont as $k => $n) {
            $n = (int) $n;
            $partes = explode('|', (string) $k);
            if ($n <= 0 || count($partes) !== 4) continue;
            [$t, $p, $id, $o] = $partes;
            if (!isset($r['total'][$t]) || !isset(EST_PAGINAS[$p])) continue;   // arquivo mexido à mão: ignora o que não conhece
            $r['total'][$t] += $n;
            if ($t === 'produto') {
                $r['produtos'][$id] ??= ['abertos' => 0, 'whatsapp' => 0, 'ligar' => 0];
                $r['produtos'][$id]['abertos'] += $n;
                continue;
            }
            $r['paginas'][$p] ??= ['visitas' => 0, 'whatsapp' => 0, 'ligar' => 0];
            if ($t === 'pagina') { $r['paginas'][$p]['visitas'] += $n; continue; }
            $r['paginas'][$p][$t] += $n;
            $lugar = $t . '|' . $p . '|' . $o;
            $r['lugares'][$lugar] = ($r['lugares'][$lugar] ?? 0) + $n;
            if ($id !== '') {
                $r['produtos'][$id] ??= ['abertos' => 0, 'whatsapp' => 0, 'ligar' => 0];
                $r['produtos'][$id][$t] += $n;
            }
            if ($t === 'whatsapp') {
                $chave = $porMes ? substr((string) $dia, 0, 7) : (string) $dia;
                if (isset($r['serie'][$chave])) $r['serie'][$chave] += $n;
            }
        }
    }
    uasort($r['produtos'], static fn($a, $b) => [$b['abertos'] + $b['whatsapp'] + $b['ligar'], $b['whatsapp']] <=> [$a['abertos'] + $a['whatsapp'] + $a['ligar'], $a['whatsapp']]);
    arsort($r['lugares']);
    uasort($r['paginas'], static fn($a, $b) => [$b['visitas'], $b['whatsapp']] <=> [$a['visitas'], $a['whatsapp']]);
    return $r;
}

/** Períodos da tela (os chips). */
const EST_PERIODOS = ['7d' => '7 dias', '30d' => '30 dias', 'mes' => 'Este mês', 'mes-passado' => 'Mês passado', '12m' => '12 meses'];

/**
 * Datas do período e do período anterior igual, para comparar:
 * 7 e 30 dias (contando hoje) × os 7/30 dias antes; este mês (dia 1 até hoje) × os mesmos dias do
 * mês anterior; mês passado × o mês antes dele; 12 meses (do dia 1 de 11 meses atrás até hoje) ×
 * as mesmas datas um ano antes (por isso os números ficam 24 meses).
 */
function periodoEstatisticas(string $cod, ?int $agora = null): array
{
    $hoje = new DateTimeImmutable(diaEstatisticas($agora ?? time()), new DateTimeZone('UTC'));   // só datas: contas em UTC (sem horário de verão)
    $f = static fn(DateTimeImmutable $d): string => $d->format('Y-m-d');
    switch ($cod) {
        case '7d':
        case '30d':
            $n = $cod === '7d' ? 7 : 30;
            $de = $hoje->modify('-' . ($n - 1) . ' days');
            return ['cod' => $cod, 'de' => $f($de), 'ate' => $f($hoje), 'antesDe' => $f($de->modify('-' . $n . ' days')), 'antesAte' => $f($de->modify('-1 day')),
                'porMes' => false, 'anterior' => 'os ' . $n . ' dias anteriores'];
        case 'mes':
            $de = $hoje->modify('first day of this month');
            $antesDe = $de->modify('-1 month');
            $antesAte = $antesDe->modify('+' . ((int) $hoje->format('j') - 1) . ' days');
            if ($antesAte->format('m') !== $antesDe->format('m')) $antesAte = $antesDe->modify('last day of this month');
            return ['cod' => $cod, 'de' => $f($de), 'ate' => $f($hoje), 'antesDe' => $f($antesDe), 'antesAte' => $f($antesAte),
                'porMes' => false, 'anterior' => 'os mesmos dias do mês anterior'];
        case 'mes-passado':
            $de = $hoje->modify('first day of last month');
            $antesDe = $de->modify('-1 month');
            return ['cod' => $cod, 'de' => $f($de), 'ate' => $f($de->modify('last day of this month')), 'antesDe' => $f($antesDe), 'antesAte' => $f($antesDe->modify('last day of this month')),
                'porMes' => false, 'anterior' => 'o mês antes dele'];
        default:   // 12m
            $de = $hoje->modify('first day of this month')->modify('-11 months');
            $antesAte = $hoje->modify('-1 year');   // 29/02 → 01/03 do ano anterior: tudo bem, é só a comparação
            return ['cod' => '12m', 'de' => $f($de), 'ate' => $f($hoje), 'antesDe' => $f($de->modify('-1 year')), 'antesAte' => $f($antesAte),
                'porMes' => true, 'anterior' => 'as mesmas datas um ano antes'];
    }
}

/** Nome de um produto que não está mais no catálogo: o guardado pela contagem; senão, o das cópias de segurança. */
function nomesDeProdutosAntigos(array $ids): array
{
    $out = [];
    $nomes = lerJsonPrivado(arquivoEstatisticas('nomes.php'), []);
    foreach ($ids as $id) if (is_array($nomes) && is_string($nomes[$id] ?? null)) $out[$id] = $nomes[$id];
    $faltam = array_values(array_diff($ids, array_keys($out)));
    if (!$faltam || !function_exists('arquivosCopias')) return $out;
    foreach (array_slice(arquivosCopias(), 0, 60) as $a) {   // das mais novas para as mais velhas, no máximo 60
        $d = lerJsonPrivado('copias/' . basename($a));
        foreach ((is_array($d['produtos'] ?? null) ? $d['produtos'] : []) as $p) {
            $id = is_array($p) ? (string) ($p['id'] ?? '') : '';
            if ($id !== '' && in_array($id, $faltam, true) && !isset($out[$id])) $out[$id] = trim((string) ($p['marca'] ?? '') . ' ' . (string) ($p['nome'] ?? ''));
        }
        if (count(array_diff($faltam, array_keys($out))) === 0) break;
    }
    return $out;
}

/** Variação em relação ao período anterior: ['txt' => '+12%', 'classe' => 'sobe'|'desce'|'igual'|'novo'|'sem']. */
function variacaoEstatisticas(int $agora, int $antes, bool $comparavel): array
{
    if (!$comparavel) return ['txt' => 'sem comparação ainda', 'classe' => 'sem'];
    if ($antes === 0) return $agora === 0 ? ['txt' => 'igual', 'classe' => 'igual'] : ['txt' => 'antes: 0', 'classe' => 'novo'];
    $pct = (int) round(($agora - $antes) * 100 / $antes);
    if ($pct === 0) return ['txt' => 'igual', 'classe' => 'igual'];
    return ['txt' => ($pct > 0 ? '+' : '−') . abs($pct) . '%', 'classe' => $pct > 0 ? 'sobe' : 'desce'];
}

/** Tela a=procurados (todos os usuários; só leitura). */
function acaoProcurados(): void
{
    $cod = is_string($_GET['periodo'] ?? null) && isset(EST_PERIODOS[$_GET['periodo']]) ? $_GET['periodo'] : '30d';
    $per = periodoEstatisticas($cod);
    // Virada do dia sem nenhuma visita ainda: a lista de visitantes de ontem sai agora (e os meses velhos também)
    try { comTrava('estatisticas', static fn() => segredoDoDia(diaEstatisticas(time()))); } catch (Throwable $t) { /* só limpeza */ }
    $primeiro = primeiroDiaContado();
    $r = resumoEstatisticas(lerEstatisticas($per['de'], $per['ate'], $limitados), $per['de'], $per['ate'], $per['porMes']);
    $antes = resumoEstatisticas(lerEstatisticas($per['antesDe'], $per['antesAte']), $per['antesDe'], $per['antesAte']);
    try {
        $cat = lerCatalogo();
        $produtos = [];
        foreach ($cat['produtos'] as $p) $produtos[(string) $p['id']] = $p;
        // Nome de cada categoria (Categorias do painel; o curto enquanto não foi renomeada), para o ranking
        $nomesCat = [];
        foreach ($cat['categorias'] as $c) $nomesCat[(string) $c['id']] = categoriaCurta($c);
    } catch (ErroCatalogo $e) {
        $produtos = null;   // catálogo com problema: o ranking mostra só os códigos
        $nomesCat = [];
    }
    $fora = array_keys(array_diff_key($r['produtos'], $produtos ?? []));
    telaProcurados($per, $r, $antes, $primeiro, $produtos, $fora ? nomesDeProdutosAntigos($fora) : [], $nomesCat, $limitados);
}

/** Números curtos para o cartão "Mais procurados" (lista de produtos e Conta): cliques no WhatsApp nos últimos 7 dias. */
function resumoCurtoProcurados(): array
{
    try {
        $per = periodoEstatisticas('7d');
        $r = resumoEstatisticas(lerEstatisticas($per['de'], $per['ate']), $per['de'], $per['ate']);
        return ['whatsapp' => $r['total']['whatsapp'], 'visitas' => $r['total']['pagina'], 'tem' => primeiroDiaContado() !== null];
    } catch (Throwable $t) {
        return ['whatsapp' => 0, 'visitas' => 0, 'tem' => false];
    }
}
