<?php
/* =====================================================================
   Painel Balanças.com — ajustes do site (área Empresa)
   Lê e grava compartilhado/dados/ajustes.js (window.AJUSTES = {JSON};),
   que guarda SÓ o que foi mudado pelo painel: contato, endereço e mapa,
   horário, feriados, aviso no site, Google e redes, equipe, depoimentos
   e marcas, e o produto da capa do site (a balança grande do topo da
   página inicial). Chave ausente = o site usa compartilhado/dados/empresa.js
   (os valores originais, que vêm com as atualizações do site; o
   ajustes.js nunca vem nelas, então o que o painel gravou continua).
   Sem a chave "capa", a capa é a Toledo Prix 7E do index.html.
   Cada parte da tela grava as suas chaves inteiras; antes, o painel
   guarda uma cópia do arquivo (copias/ajustes-*.php, com as mesmas
   regras de retenção das outras cópias). Telas em lib/visoes-empresa.php.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

final class ErroAjustes extends RuntimeException {}   // ajustes.js existe mas não está no formato do painel

// Partes da tela (cada uma com o seu Salvar) e as chaves do ajustes.js que cada uma grava
const SECOES_AJUSTES = [
    'contato'     => 'Contato e WhatsApp',
    'endereco'    => 'Endereço e mapa',
    'horario'     => 'Horário de funcionamento',
    'feriados'    => 'Feriados e dias fechados',
    'aviso'       => 'Aviso no site',
    'google'      => 'Avaliações do Google e redes sociais',
    'equipe'      => 'Equipe',
    'depoimentos' => 'Depoimentos',
    'marcas'      => 'Marcas',
    'capa'        => 'Capa do site',
];
const CHAVES_DA_SECAO = [
    'contato' => ['contato'], 'endereco' => ['endereco', 'mapa'], 'horario' => ['horario'], 'feriados' => ['feriados'],
    'aviso' => ['aviso'], 'google' => ['google', 'redes'], 'equipe' => ['equipe'], 'depoimentos' => ['depoimentos'], 'marcas' => ['marcas'],
    'capa' => ['capa'],
];
// Ordem das chaves no arquivo (chaves desconhecidas são mantidas, depois destas)
const ORDEM_AJUSTES = ['versao', 'contato', 'endereco', 'mapa', 'horario', 'feriados', 'aviso', 'google', 'redes', 'equipe', 'depoimentos', 'marcas', 'capa'];
// Cópias de segurança dos ajustes (mesmos grupos de RE_COPIA: data, hora, tipo)
const RE_COPIA_AJUSTES = '/^ajustes-(\d{8})-(\d{6})-\d{3}-[0-9a-f]{4}-([a-z]{3,12})\.php$/';
const LIMITES_AJUSTES = [
    'mensagem' => 200, 'email' => 120, 'rua' => 80, 'referencia' => 80, 'bairro' => 60, 'cidade' => 60, 'url' => 300,
    'aviso_min' => 5, 'aviso' => 160, 'feriado_nome' => 60, 'municipais' => 12, 'datas' => 30, 'intervalos' => 3,
    'equipe' => 12, 'equipe_nome' => 60, 'equipe_cargo' => 60, 'equipe_texto' => 220,
    'depoimentos' => 12, 'dep_texto' => 300, 'dep_nome' => 60, 'dep_empresa' => 60,
    'marcas' => 40, 'marca' => 30,
    'capa_pontos' => 4, 'capa_rotulo' => 14, 'capa_valor' => 24, 'capa_detalhe' => 32, 'capa_texto' => 48,
];
// Campos de cada pessoa da equipe / depoimento: campo => [rótulo, limite, obrigatório, sem < >]
const CAMPOS_LISTA = [
    'equipe' => [
        'nome'  => ['Nome', LIMITES_AJUSTES['equipe_nome'], true, true],
        'cargo' => ['Cargo', LIMITES_AJUSTES['equipe_cargo'], true, true],
        'texto' => ['Texto', LIMITES_AJUSTES['equipe_texto'], false, false],
    ],
    'depoimentos' => [
        'texto'   => ['Depoimento', LIMITES_AJUSTES['dep_texto'], true, false],
        'nome'    => ['Nome', LIMITES_AJUSTES['dep_nome'], true, true],
        'empresa' => ['Empresa', LIMITES_AJUSTES['dep_empresa'], false, true],
    ],
];
// Dias na ordem do site (segunda → domingo); 0 = domingo, como no JavaScript
const ORDEM_DIAS = [1, 2, 3, 4, 5, 6, 0];
const NOMES_DIAS = [0 => 'domingo', 1 => 'segunda', 2 => 'terça', 3 => 'quarta', 4 => 'quinta', 5 => 'sexta', 6 => 'sábado'];
// Horário que o site usa quando ninguém mudou pelo painel: a mesma grade de linha-verde/js/core.js
const GRADE_PADRAO = [0 => [], 1 => [[480, 660], [780, 1080]], 2 => [[480, 660], [780, 1080]], 3 => [[480, 660], [780, 1080]],
    4 => [[480, 660], [780, 1080]], 5 => [[480, 660], [780, 1080]], 6 => [[480, 720]]];
// Feriados móveis que o dono pode marcar (dias contados a partir da Páscoa, como em core.js)
const FERIADOS_MOVEIS = [-48 => 'Segunda-feira de Carnaval', -47 => 'Terça-feira de Carnaval', -46 => 'Quarta-feira de Cinzas', 60 => 'Corpus Christi'];
// Feriados que o site já conta sozinho, todo ano (linha-verde/js/core.js)
const FERIADOS_DO_SITE = ['01/01 Confraternização', '21/04 Tiradentes', '01/05 Dia do Trabalho', '09/07 Revolução Constitucionalista (SP)',
    '07/09 Independência', '12/10 Nossa Senhora Aparecida', '02/11 Finados', '15/11 Proclamação da República',
    '20/11 Consciência Negra', '25/12 Natal', 'Sexta-feira Santa'];
// Sugestões para Rio Claro (fontes no INTEGRACAO.md da rodada 2): o dono confirma antes de usar.
const SUGESTOES_RIO_CLARO = [
    'aniversario' => ['data' => '06-24', 'nome' => 'Aniversário de Rio Claro (São João Batista)',
        'nota' => 'Feriado municipal: dia da cidade e do padroeiro (Lei Municipal 1.158/1970). Está no calendário oficial da Prefeitura como “Dia da Cidade”.'],
    'corpus' => ['movel' => 60, 'nome' => 'Corpus Christi',
        'nota' => 'Data móvel (quinta-feira, 60 dias depois da Páscoa). O calendário oficial da Prefeitura de Rio Claro trata como feriado.'],
];
// Datas avulsas que já passaram há mais que isto saem da lista ao salvar
const DIAS_GUARDAR_DATAS = 60;
const UFS = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
// Links aceitos (sempre https). Google Maps: estes endereços, ou google.com(.br) com /maps
const HOSTS_LINKS = [
    'mapa'      => ['maps.google.com', 'maps.google.com.br', 'maps.app.goo.gl', 'goo.gl', 'g.page', 'g.co'],
    'facebook'  => ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'web.facebook.com', 'pt-br.facebook.com', 'fb.com', 'www.fb.com'],
    'instagram' => ['instagram.com', 'www.instagram.com'],
];
const HOSTS_GOOGLE = ['google.com', 'www.google.com', 'google.com.br', 'www.google.com.br'];

function arquivoAjustes(): string
{
    return cfg('compartilhado') . '/dados/ajustes.js';
}

function arquivoEmpresa(): string
{
    return cfg('compartilhado') . '/dados/empresa.js';
}

/* ------------------------------------------------------------------
   Formato do arquivo
   ------------------------------------------------------------------ */
/** Nome para o comentário do arquivo: SÓ letras, números, espaço e . - _ (um "*" + "/" fecharia o comentário
 *  e o resto viraria código que todo visitante do site executa). */
function nomeParaCabecalho(string $s): string
{
    $s = preg_replace('/[^\p{L}\p{N} ._-]+/u', '', $s) ?? '';
    return cortar(trim(preg_replace('/\s+/', ' ', $s) ?? ''), 40);
}

function cabecalhoAjustes(string $linhaData): string
{
    return implode("\n", [
        '/* =====================================================================',
        '   AJUSTES DO SITE — Balanças.com (área Empresa do painel)',
        '   ---------------------------------------------------------------------',
        '   Arquivo GERADO pelo painel de administração do site (área Empresa).',
        '   Guarda só o que foi mudado pelo painel: contato, endereço e mapa,',
        '   horário, feriados, aviso no site, Google e redes sociais, equipe,',
        '   depoimentos, marcas e o produto da capa. Para mudar, use o painel:',
        '   ele confere os dados e guarda cópias de segurança. O que for',
        '   editado à mão aqui some na próxima vez que o painel salvar.',
        '',
        '   Formato: JSON puro dentro de window.AJUSTES. Toda chave é opcional:',
        '   a que não estiver aqui vem de compartilhado/dados/empresa.js (os',
        '   valores originais, que chegam com as atualizações do site). Este',
        '   arquivo não vem nas atualizações: o que o painel gravou continua.',
        '   ' . str_replace('*/', '* /', $linhaData),
        '   ===================================================================== */',
        '',
    ]);
}

/** Estrutura para o json_encode: a grade de horários e o arquivo vazio viram objetos {…}, não listas. */
function ajustesParaJson(array $aj)
{
    if (isset($aj['horario']['grade']) && is_array($aj['horario']['grade'])) {
        $g = new stdClass();
        foreach ($aj['horario']['grade'] as $d => $iv) $g->{(string) $d} = $iv;
        $aj['horario']['grade'] = $g;
    }
    return $aj ? $aj : new stdClass();
}

function montarAjustes(array $aj, ?string $quem = null): string
{
    $quem = nomeParaCabecalho((string) ($quem ?? quemAlterouAjustes()));
    return cabecalhoAjustes('Última alteração pelo painel: ' . date('d/m/Y') . ' às ' . date('H:i') . ($quem !== '' ? ', por ' . $quem : '') . '.')
        . 'window.AJUSTES = ' . json_encode(ajustesParaJson($aj), JSON_GRAVAR | JSON_THROW_ON_ERROR) . ";\n";
}

/** Nome de quem está salvando (nome de exibição; o login, se não houver). */
function quemAlterouAjustes(): string
{
    $login = (string) (usuarioLogado() ?? '');
    return $login === '' ? '' : nomeDe($login);
}

/**
 * Acha o objeto JSON logo depois de "window.NOME =" sem executar nada (respeita strings).
 * Devolve [objeto (array), posição logo depois dele]. Lança ErroAjustes com explicação.
 */
function objetoJsonDepois(string $src, string $nome): array
{
    if (!preg_match('/(?:^|\n)\s*window\.' . $nome . '\s*=\s*/', $src, $m, PREG_OFFSET_CAPTURE)) {
        throw new ErroAjustes('não achei "window.' . $nome . ' ="');
    }
    $ini = $m[0][1] + strlen($m[0][0]);
    if (($src[$ini] ?? '') !== '{') throw new ErroAjustes('window.' . $nome . ' não começa com {');
    $prof = 0; $str = false; $n = strlen($src);
    for ($i = $ini; $i < $n; $i++) {
        $c = $src[$i];
        if ($str) {
            if ($c === '\\') $i++;
            elseif ($c === '"') $str = false;
            continue;
        }
        if ($c === '"') $str = true;
        elseif ($c === '[' || $c === '{') $prof++;
        elseif (($c === ']' || $c === '}') && --$prof === 0) {
            return [substr($src, $ini, $i - $ini + 1), $i + 1, $m[0][1]];
        }
    }
    throw new ErroAjustes('window.' . $nome . ' não termina');
}

/** Lê window.AJUSTES sem executar nada e confere cada parte. Lança ErroAjustes com explicação. */
function interpretarAjustes(string $src): array
{
    $src = preg_replace('/^\xEF\xBB\xBF/', '', $src) ?? $src;
    if (trim($src) === '') throw new ErroAjustes('o arquivo está vazio');
    $src = jsSemComentarios($src);
    [$json, $fim, $comeco] = objetoJsonDepois($src, 'AJUSTES');
    // Fora os comentários, antes só pode haver espaço e depois só o ";": qualquer outra coisa não foi o painel que escreveu
    if (trim(substr($src, 0, $comeco)) !== '' || !preg_match('/^\s*;?\s*$/', substr($src, $fim))) throw new ErroAjustes('há texto fora do formato antes ou depois de window.AJUSTES');
    try {
        $d = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
    } catch (JsonException $ex) {
        throw new ErroAjustes('o conteúdo de window.AJUSTES não é JSON válido (' . $ex->getMessage() . ')');
    }
    if (!is_array($d) || ($d && array_is_list($d))) throw new ErroAjustes('window.AJUSTES precisa ser um objeto { … }');
    if (isset($d['horario']['grade']) && is_array($d['horario']['grade'])) ksort($d['horario']['grade']);
    $erro = conferirAjustes($d);
    if ($erro !== null) throw new ErroAjustes($erro);
    return $d;
}

/** Ajustes atuais. Sem o arquivo no servidor: nada ajustado ainda ('existe' => false). */
function lerAjustes(): array
{
    $arq = arquivoAjustes();
    clearstatcache(true, $arq);
    if (!@is_file($arq)) return ['ajustes' => [], 'existe' => false];
    $src = @file_get_contents($arq);
    if ($src === false) throw new ErroAjustes('sem permissão para ler compartilhado/dados/ajustes.js');
    return ['ajustes' => interpretarAjustes($src), 'existe' => true];
}

/** Chaves conhecidas na ordem do arquivo; as desconhecidas (de versões futuras) ficam, no fim. */
function ordenarAjustes(array $aj): array
{
    $out = [];
    foreach (ORDEM_AJUSTES as $k) if (array_key_exists($k, $aj)) $out[$k] = $aj[$k];
    return $out + $aj;
}

function gravarAjustes(array $aj, ?string $quem = null): void
{
    $texto = montarAjustes($aj, $quem);
    // Confere o que vai ser gravado antes de substituir o arquivo do site
    if (interpretarAjustes($texto) !== $aj) throw new RuntimeException('conferência dos ajustes do site falhou');
    if (!@is_writable(arquivoAjustes()) && !@is_writable(dirname(arquivoAjustes()))) {
        throw new ErroUsuario('O servidor não deixa gravar compartilhado/dados/ajustes.js. Confira as permissões no Gerenciador de Arquivos (arquivo 644, pasta dados 755).');
    }
    gravarAtomico(arquivoAjustes(), $texto, 0644);
}

/**
 * O pacote de atualização do site não leva o ajustes.js (para nunca apagar o que foi salvo na
 * área Empresa). No servidor que ainda não tem o arquivo, o painel cria o vazio na primeira vez
 * que alguém entra: até lá o site só registra um "arquivo não encontrado" e usa o empresa.js.
 */
function garantirArquivoAjustes(): void
{
    if (@is_file(arquivoAjustes())) return;
    try {
        comTrava('ajustes', static function (): void {
            clearstatcache(true, arquivoAjustes());
            if (!@is_file(arquivoAjustes())) gravarAjustes([], '');
        });
    } catch (Throwable $t) {
        error_log('painel: não foi possível criar o ajustes.js: ' . $t->getMessage());
    }
}

/**
 * Altera os ajustes com segurança: trava → lê → $fn(ajustes) devolve os novos → cópia de
 * segurança de antes → grava (temporário + rename) → registro.
 * $acao = ['tipo' => 'contato'…, 'antes' => 'Antes de: …', 'feito' => 'Alterou …', 'secao' => …]
 * Devolve ['mudou' => bool, 'ajustes' => novos, 'copia' => nome da cópia ou null].
 */
function alterarAjustes(array $acao, callable $fn): array
{
    return comTrava('ajustes', static function () use ($acao, $fn): array {
        $antes = lerAjustes();
        $novos = ordenarAjustes($fn($antes['ajustes']));
        if ($antes['existe'] && $novos === $antes['ajustes']) return ['mudou' => false, 'ajustes' => $novos, 'copia' => null];
        // Confere antes de mexer em qualquer coisa (a gravação confere de novo)
        if (interpretarAjustes(montarAjustes($novos)) !== $novos) throw new RuntimeException('conferência dos ajustes do site falhou');
        $copia = fazerCopiaAjustes($antes, $acao);
        gravarAjustes($novos);
        registrar('ajustes', (string) ($acao['feito'] ?? $acao['antes'] ?? ''));
        return ['mudou' => true, 'ajustes' => $novos, 'copia' => $copia];
    });
}

/* ------------------------------------------------------------------
   Valores originais (compartilhado/dados/empresa.js)
   ------------------------------------------------------------------ */
/**
 * window.EMPRESA = {JSON}; (formato novo, só JSON). Um empresa.js antigo, ainda em JavaScript
 * solto (chaves sem aspas, comentários, vírgula no fim), também é lido, para o painel nunca
 * mostrar campos vazios por causa dele. Devolve ['dados' => array, 'erro' => motivo ou null].
 */
function lerPadroesEmpresa(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $src = @is_file(arquivoEmpresa()) ? @file_get_contents(arquivoEmpresa()) : false;
    if ($src === false) return $cache = ['dados' => [], 'erro' => 'o arquivo compartilhado/dados/empresa.js não foi encontrado'];
    $src = preg_replace('/^\xEF\xBB\xBF/', '', $src) ?? $src;
    try {
        $src = jsSemComentarios($src);
        [$json] = objetoJsonDepois($src, 'EMPRESA');
        $d = json_decode($json, true, 32);
        if (!is_array($d)) $d = json_decode(jsAntigoParaJson($json), true, 32);
        if (!is_array($d)) throw new ErroAjustes('o conteúdo de window.EMPRESA não é JSON válido');
        return $cache = ['dados' => $d, 'erro' => null];
    } catch (ErroAjustes $e) {
        return $cache = ['dados' => [], 'erro' => $e->getMessage()];
    }
}

/** Tira comentários /* … *\/ e // … de um JavaScript, sem mexer no que está dentro de aspas. */
function jsSemComentarios(string $js): string
{
    $out = ''; $n = strlen($js); $aspa = '';
    for ($i = 0; $i < $n; $i++) {
        $c = $js[$i];
        if ($aspa !== '') {
            $out .= $c;
            if ($c === '\\' && $i + 1 < $n) $out .= $js[++$i];
            elseif ($c === $aspa) $aspa = '';
            continue;
        }
        if ($c === '"' || $c === "'") { $aspa = $c; $out .= $c; continue; }
        if ($c === '/' && ($js[$i + 1] ?? '') === '*') { $f = strpos($js, '*/', $i + 2); $i = $f === false ? $n : $f + 1; continue; }
        if ($c === '/' && ($js[$i + 1] ?? '') === '/') { $f = strpos($js, "\n", $i); $i = $f === false ? $n : $f - 1; continue; }
        $out .= $c;
    }
    return $out;
}

/** Objeto JavaScript simples (chaves sem aspas, vírgula sobrando) → JSON. Só para o empresa.js antigo. */
function jsAntigoParaJson(string $js): string
{
    $js = preg_replace('/([{,]\s*)([A-Za-z_][A-Za-z0-9_]*)\s*:/', '$1"$2":', $js) ?? $js;
    return preg_replace('/,(\s*[}\]])/', '$1', $js) ?? $js;
}

/** Texto do empresa.js (ou '' se não for texto). */
function textoPadrao(array $p, string ...$caminho): string
{
    foreach ($caminho as $k) {
        if (!is_array($p) || !array_key_exists($k, $p)) return '';
        $p = $p[$k];
    }
    return is_string($p) || is_int($p) ? (string) $p : '';
}

/**
 * O que o site mostra hoje, parte por parte: o ajuste do painel, se houver; senão, o empresa.js.
 * Devolve [chave do ajustes.js => estrutura], no mesmo formato que o painel grava.
 */
function valoresAtuais(array $aj, array $p): array
{
    $c = is_array($aj['contato'] ?? null) ? $aj['contato'] : [];
    $e = is_array($aj['endereco'] ?? null) ? $aj['endereco'] : [];
    $g = is_array($aj['google'] ?? null) ? $aj['google'] : [];
    $r = is_array($aj['redes'] ?? null) ? $aj['redes'] : [];
    $txt = static fn(array $de, string $k, string ...$pad): string => is_string($de[$k] ?? null) ? $de[$k] : textoPadrao($p, ...$pad);
    $out = [];
    $out['contato'] = [];
    foreach (['telefone', 'telefoneLink', 'whatsapp', 'whatsappMensagemPadrao', 'email'] as $k) $out['contato'][$k] = $txt($c, $k, $k);
    $out['endereco'] = [];
    foreach (['rua', 'referencia', 'bairro', 'cidade', 'uf', 'cep'] as $k) $out['endereco'][$k] = $txt($e, $k, 'endereco', $k);
    $out['mapa'] = is_string($aj['mapa'] ?? null) ? $aj['mapa'] : textoPadrao($p, 'mapa');

    $grade = $aj['horario']['grade'] ?? null;
    if (!is_array($grade)) [$grade] = conferirGrade($p['grade'] ?? null);
    $grade ??= GRADE_PADRAO;
    $linhas = $aj['horario']['linhas'] ?? (is_array($p['horarios'] ?? null) ? $p['horarios'] : linhasHorario($grade));
    $out['horario'] = ['grade' => $grade, 'linhas' => $linhas];

    $f = is_array($aj['feriados'] ?? null) ? $aj['feriados'] : (is_array($p['feriados'] ?? null) ? $p['feriados'] : []);
    $out['feriados'] = [
        'municipais' => is_array($f['municipais'] ?? null) ? array_values($f['municipais']) : [],
        'moveis' => is_array($f['moveis'] ?? null) ? array_values($f['moveis']) : [],
        'datas' => is_array($f['datas'] ?? null) ? array_values($f['datas']) : [],
    ];
    $a = is_array($aj['aviso'] ?? null) ? $aj['aviso'] : (is_array($p['aviso'] ?? null) ? $p['aviso'] : []);
    $out['aviso'] = montarAviso(($a['ativo'] ?? false) === true, is_string($a['texto'] ?? null) ? $a['texto'] : '',
        is_string($a['inicio'] ?? null) ? $a['inicio'] : '', is_string($a['fim'] ?? null) ? $a['fim'] : '', ($a['tom'] ?? '') === 'alerta' ? 'alerta' : 'info');

    $out['google'] = ['nota' => $txt($g, 'nota', 'google', 'nota'), 'avaliacoes' => is_int($g['avaliacoes'] ?? null) ? $g['avaliacoes'] : (int) textoPadrao($p, 'google', 'avaliacoes'),
        'link' => $txt($g, 'link', 'google', 'link')];
    $out['redes'] = ['facebook' => $txt($r, 'facebook', 'redes', 'facebook'), 'instagram' => $txt($r, 'instagram', 'redes', 'instagram')];
    foreach (['equipe', 'depoimentos', 'marcas'] as $k) {
        $out[$k] = is_array($aj[$k] ?? null) && $aj[$k] ? $aj[$k] : (is_array($p[$k] ?? null) ? array_values($p[$k]) : []);
    }
    // Capa: null = a Prix 7E do index.html (CAPA_ORIGINAL)
    $out['capa'] = is_array($aj['capa'] ?? null) ? $aj['capa'] : null;
    return $out;
}

/* ------------------------------------------------------------------
   Validação de cada campo: [valor limpo, mensagem de erro ou null]
   ------------------------------------------------------------------ */
/** Texto de uma linha. $min = 0: pode ficar vazio. */
function linhaAjuste($v, string $rotulo, int $min, int $max, bool $semSinais = false): array
{
    $t = limparLinha($v);
    if ($t === null) return ['', $rotulo . ': há caracteres inválidos. Apague e digite de novo.'];
    if ($t === '') return ['', $min > 0 ? $rotulo . ': preencha este campo.' : null];
    if (tamanho($t) < $min) return [$t, $rotulo . ': escreva pelo menos ' . $min . ' caracteres.'];
    if (tamanho($t) > $max) return [$t, $rotulo . ': no máximo ' . $max . ' caracteres (tem ' . tamanho($t) . ').'];
    if ($semSinais && preg_match('/[<>]/', $t)) return [$t, $rotulo . ': não use os sinais < e >.'];
    return [$t, null];
}

/** Telefone com DDD: [só os dígitos, sem o 55; erro]. Aceita "(19) 3023-9050", "19 99999-9999", "+55 19 …". */
function digitosTelefone($v, string $rotulo): array
{
    $t = is_string($v) ? trim($v) : '';
    if ($t === '') return ['', $rotulo . ': preencha com o DDD (ex.: (19) 3023-9050).'];
    if (preg_match('/[^\d\s()+.\-\/]/', $t)) return ['', $rotulo . ': use só números, com o DDD (ex.: (19) 3023-9050).'];
    $d = ltrim(preg_replace('/\D+/', '', $t) ?? '', '0');
    if ((strlen($d) === 12 || strlen($d) === 13) && str_starts_with($d, '55')) $d = substr($d, 2);
    if (strlen($d) < 10 || strlen($d) > 11) return ['', $rotulo . ': confira o número. Use o DDD e o número (ex.: (19) 3023-9050 ou (19) 99999-9999).'];
    if (!preg_match('/^[1-9][1-9]/', $d)) return ['', $rotulo . ': o DDD não parece certo (ex.: 19).'];
    if (strlen($d) === 11 && $d[2] !== '9') return ['', $rotulo . ': celular com 9 dígitos começa com 9 (ex.: (19) 99999-9999).'];
    if (strlen($d) === 10 && !in_array($d[2], ['2', '3', '4', '5'], true)) return ['', $rotulo . ': se for celular, falta o 9 na frente (ex.: (19) 99999-9999).'];
    return [$d, null];
}

function formatarTelefone(string $d): string
{
    if (strlen($d) === 13 || strlen($d) === 12) $d = substr($d, 2);   // com o 55
    if (strlen($d) === 11) return '(' . substr($d, 0, 2) . ') ' . substr($d, 2, 5) . '-' . substr($d, 7);
    if (strlen($d) === 10) return '(' . substr($d, 0, 2) . ') ' . substr($d, 2, 4) . '-' . substr($d, 6);
    return $d;
}

function emailAjuste($v): array
{
    $t = limparLinha($v);
    if ($t === null || $t === '') return ['', 'E-mail: preencha este campo (ex.: balancas.com@gmail.com).'];
    $t = strtolower($t);
    if (strlen($t) > LIMITES_AJUSTES['email'] || !filter_var($t, FILTER_VALIDATE_EMAIL)) return [$t, 'E-mail: confira o endereço (ex.: balancas.com@gmail.com).'];
    return [$t, null];
}

function cepAjuste($v): array
{
    $t = is_string($v) ? trim($v) : '';
    $d = preg_replace('/\D+/', '', $t) ?? '';
    if ($t === '') return ['', 'CEP: preencha este campo (ex.: 13500-120).'];
    if (preg_match('/[^\d\s.\-]/', $t) || strlen($d) !== 8) return [$t, 'CEP: use os 8 números (ex.: 13500-120).'];
    return [substr($d, 0, 5) . '-' . substr($d, 5), null];
}

function ufAjuste($v): array
{
    $t = strtoupper(trim(is_string($v) ? $v : ''));
    if (!in_array($t, UFS, true)) return [$t, 'Estado: use a sigla de 2 letras (ex.: SP).'];
    return [$t, null];
}

/**
 * Link de $tipo (mapa, facebook, instagram), sempre https. Aceita colar sem o https:// e,
 * no Instagram, só "@perfil". $obrig = false: pode ficar vazio (esconde o link no site).
 */
function linkAjuste($v, string $tipo, string $rotulo, bool $obrig): array
{
    $t = limparLinha($v);
    if ($t === null) return ['', $rotulo . ': há caracteres inválidos. Copie o link de novo.'];
    if ($t === '') return ['', $obrig ? $rotulo . ': cole o link (começa com https://).' : null];
    if ($tipo === 'instagram' && preg_match('/^@([A-Za-z0-9._]{1,30})$/', $t, $m)) return ['https://www.instagram.com/' . $m[1] . '/', null];
    if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $t)) $t = 'https://' . $t;      // "facebook.com/loja" → https://facebook.com/loja
    elseif (preg_match('~^http://~i', $t)) $t = 'https://' . substr($t, 7);
    $exemplo = match ($tipo) {
        'facebook'  => ' (ex.: https://www.facebook.com/balancas.comrc/)',
        'instagram' => ' (ex.: https://www.instagram.com/perfil/ ou só @perfil)',
        default     => ' (no Google Maps: Compartilhar → Copiar link; começa com https://maps.app.goo.gl/ ou https://www.google.com/maps/)',
    };
    if (strlen($t) > LIMITES_AJUSTES['url'] || preg_match('/[\s"\'<>\\\\`{}|^]/', $t)) return [$t, $rotulo . ': o link parece incompleto ou tem espaços. Copie de novo' . $exemplo . '.'];
    $p = parse_url($t);
    if (!is_array($p) || strtolower((string) ($p['scheme'] ?? '')) !== 'https' || empty($p['host']) || isset($p['user']) || isset($p['pass']) || isset($p['port'])) {
        return [$t, $rotulo . ': o link precisa começar com https://' . $exemplo . '.'];
    }
    $host = rtrim(strtolower($p['host']), '.');
    $caminho = (string) ($p['path'] ?? '');
    $temAlvo = trim($caminho, '/') !== '' || isset($p['query']);
    $ok = $tipo === 'mapa'
        ? $temAlvo && (in_array($host, HOSTS_LINKS['mapa'], true) || (in_array($host, HOSTS_GOOGLE, true) && preg_match('~^/maps(?:/|$)~', $caminho)))
        : in_array($host, HOSTS_LINKS[$tipo] ?? [], true) && trim($caminho, '/') !== '';
    if (!$ok) {
        return [$t, match ($tipo) {
            'facebook'  => $rotulo . ': use o link da página no Facebook' . $exemplo . '.',
            'instagram' => $rotulo . ': use o link do perfil no Instagram' . $exemplo . '.',
            default     => $rotulo . ': use um link do Google Maps' . $exemplo . '.',
        }];
    }
    // Monta de novo, só com as partes esperadas
    return ['https://' . $host . $caminho . (isset($p['query']) ? '?' . $p['query'] : '') . (isset($p['fragment']) ? '#' . $p['fragment'] : ''), null];
}

/** Nota do Google: "5", "4.8", "4,8" → "4,8" (de 1,0 a 5,0). */
function notaAjuste($v): array
{
    $t = str_replace(',', '.', trim(is_string($v) ? $v : ''));
    if ($t === '') return ['', 'Nota: preencha este campo (ex.: 5,0).'];
    if (!preg_match('/^\d(?:\.\d+)?$/', $t) || (float) $t < 1 || (float) $t > 5) return [is_string($v) ? trim($v) : '', 'Nota: use um número de 1,0 a 5,0 (ex.: 4,9).'];
    return [number_format(round((float) $t, 1), 1, ',', ''), null];
}

/** Número de avaliações: "56", "1.234" → 56, 1234 (de 0 a 100000). */
function avaliacoesAjuste($v): array
{
    $t = preg_replace('/[\s.]/', '', is_string($v) ? $v : (is_int($v) ? (string) $v : '')) ?? '';
    if ($t === '') return [0, 'Avaliações: preencha com o número que aparece no Google (ex.: 56).'];
    if (!preg_match('/^\d{1,6}$/', $t) || (int) $t > 100000) return [0, 'Avaliações: use só o número (ex.: 56).'];
    return [(int) $t, null];
}

function dataValida(string $s): bool
{
    return (bool) preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
}

/** Data digitada: "2026-12-24" (campo de data) ou "24/12/2026" → "2026-12-24"; '' = vazia; null = inválida. */
function dataDoForm($v): ?string
{
    $t = trim(is_string($v) ? $v : '');
    if ($t === '') return '';
    if (preg_match('~^(\d{1,2})[/.\-](\d{1,2})[/.\-](\d{4})$~', $t, $m)) $t = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    return dataValida($t) ? $t : null;
}

/** Dia e mês digitados ("24/06", "24/6", "24-06") → "06-24" (aceita 29/02); null = inválido. */
function diaMesDoForm($v): ?string
{
    $t = trim(is_string($v) ? $v : '');
    if (!preg_match('~^(\d{1,2})[/.\-](\d{1,2})$~', $t, $m) || !checkdate((int) $m[2], (int) $m[1], 2024)) return null;
    return sprintf('%02d-%02d', $m[2], $m[1]);
}

/** "06-24" → "24/06". */
function diaMesTela(string $md): string
{
    return strlen($md) === 5 ? substr($md, 3, 2) . '/' . substr($md, 0, 2) : $md;
}

function dataBr(string $iso): string
{
    return dataValida($iso) ? substr($iso, 8, 2) . '/' . substr($iso, 5, 2) . '/' . substr($iso, 0, 4) : $iso;
}

/** Hoje no fuso do painel (America/Sao_Paulo), "AAAA-MM-DD". */
function hojeIso(): string
{
    return date('Y-m-d');
}

/* ------------------------------------------------------------------
   Horário: grade (minutos desde 0h) e o texto que o site mostra
   ------------------------------------------------------------------ */
/** Confere a grade (dias 0…6, até 3 intervalos em ordem, sem sobrepor). [grade 0…6 ou null, erro]. */
function conferirGrade($g): array
{
    if (!is_array($g) || count($g) !== 7) return [null, 'a grade de horários precisa ter os 7 dias'];
    $out = [];
    for ($d = 0; $d <= 6; $d++) {
        $dia = $g[$d] ?? null;
        if (!is_array($dia) || !array_is_list($dia) || count($dia) > LIMITES_AJUSTES['intervalos']) return [null, 'o dia ' . $d . ' da grade de horários não está certo'];
        $fimAnt = -1;
        foreach ($dia as $iv) {
            if (!is_array($iv) || !array_is_list($iv) || count($iv) !== 2 || !is_int($iv[0]) || !is_int($iv[1])
                || $iv[0] < 0 || $iv[1] > 1440 || $iv[0] >= $iv[1] || $iv[0] < $fimAnt) {
                return [null, 'um horário do dia ' . $d . ' da grade não está certo'];
            }
            $fimAnt = $iv[1];
        }
        $out[$d] = $dia;
    }
    return [$out, null];
}

function horaTexto(int $min): string
{
    $h = intdiv($min, 60);
    $m = $min % 60;
    return $h . 'h' . ($m ? sprintf('%02d', $m) : '');
}

/** "8h às 11h · 13h às 18h"; sem intervalos: "Fechado". */
function horasTexto(array $intervalos): string
{
    if (!$intervalos) return 'Fechado';
    return implode(' · ', array_map(static fn(array $iv): string => horaTexto($iv[0]) . ' às ' . horaTexto($iv[1]), $intervalos));
}

/**
 * Nome de um grupo de dias. $pos = posições em ORDEM_DIAS (0 = segunda … 6 = domingo), em ordem.
 * 3 ou mais seguidos: "Segunda a sexta"; 2: "Segunda e terça"; separados: "Segunda, quarta e sexta".
 */
function nomeGrupoDias(array $pos, bool $comFeriados = false): string
{
    $itens = [];
    $n = count($pos);
    for ($i = 0; $i < $n; $i = $j + 1) {
        for ($j = $i; $j + 1 < $n && $pos[$j + 1] === $pos[$j] + 1; $j++);
        $ini = NOMES_DIAS[ORDEM_DIAS[$pos[$i]]];
        if ($j - $i >= 2) $itens[] = $ini . ' a ' . NOMES_DIAS[ORDEM_DIAS[$pos[$j]]];
        else for ($k = $i; $k <= $j; $k++) $itens[] = NOMES_DIAS[ORDEM_DIAS[$pos[$k]]];
    }
    if ($comFeriados) $itens[] = 'feriados';
    $ultimo = array_pop($itens);
    return ucfirst($itens ? implode(', ', $itens) . ' e ' . $ultimo : (string) $ultimo);
}

/**
 * Linhas que o site mostra (o painel gera; o site usa como estão). Dias com o mesmo horário
 * ficam juntos, na ordem segunda → domingo. Domingo fechado vai por último, com os feriados
 * ("Domingo e feriados — Fechado"); se a loja abre no domingo, a última linha é "Feriados — Fechado"
 * (nos feriados a loja sempre fecha: o "Aberto agora" do site conta assim).
 * Mesma regra de linhasHorario() em assets/painel.js (prévia da tela).
 */
function linhasHorario(array $grade): array
{
    $grupos = [];
    foreach (ORDEM_DIAS as $p => $d) $grupos[json_encode($grade[$d] ?? [])][] = $p;
    $linhas = [];
    $fechados = null;
    foreach ($grupos as $chave => $pos) {
        $iv = json_decode((string) $chave, true);
        if (!$iv && in_array(6, $pos, true)) { $fechados = $pos; continue; }
        $linhas[] = ['dias' => nomeGrupoDias($pos), 'horas' => horasTexto($iv)];
    }
    $linhas[] = $fechados !== null ? ['dias' => nomeGrupoDias($fechados, true), 'horas' => 'Fechado'] : ['dias' => 'Feriados', 'horas' => 'Fechado'];
    return $linhas;
}

/** "08:30", "8:30", "8h30", "8h", "8" → minutos; "24:00" só para o fim. null = inválido. */
function minutosDoForm(string $t, bool $fim): ?int
{
    $t = strtolower(str_replace(' ', '', $t));
    if (!preg_match('/^(\d{1,2})(?:[:h](\d{2})?)?$/', $t, $m)) return null;
    $h = (int) $m[1]; $min = (int) ($m[2] ?? 0);
    if ($min > 59 || $h > 24 || ($h === 24 && ($min > 0 || !$fim))) return null;
    return $h * 60 + $min;
}

function horaDoForm(int $min): string
{
    return sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
}

/* ------------------------------------------------------------------
   Conferência do arquivo inteiro (o que veio do ajustes.js)
   ------------------------------------------------------------------ */
/** null se tudo estiver no formato do painel; senão, o motivo (curto). Chaves desconhecidas passam. */
function conferirAjustes(array $d): ?string
{
    $str = static fn($v): bool => is_string($v);
    $parte = static fn(string $k): string => 'a parte “' . $k . '” não está no formato do painel';
    if (array_key_exists('versao', $d) && !is_int($d['versao'])) return $parte('versao');
    foreach (['contato', 'endereco', 'google', 'redes', 'horario', 'feriados', 'aviso'] as $k) {
        if (array_key_exists($k, $d) && (!is_array($d[$k]) || ($d[$k] && array_is_list($d[$k])))) return $parte($k);
    }
    foreach ($d['contato'] ?? [] as $k => $v) {
        if (!$str($v)) return $parte('contato');
        $ok = match ($k) {
            'telefone' => digitosTelefone($v, '')[1] === null,
            'telefoneLink' => (bool) preg_match('/^\+55[1-9]{2}\d{8,9}$/', $v),
            'whatsapp' => (bool) preg_match('/^55[1-9]{2}\d{8,9}$/', $v),
            'whatsappMensagemPadrao' => tamanho($v) <= LIMITES_AJUSTES['mensagem'],
            'email' => emailAjuste($v)[1] === null,
            default => true,
        };
        if (!$ok) return 'o campo contato.' . $k . ' não está certo';
    }
    foreach ($d['endereco'] ?? [] as $k => $v) {
        if (!$str($v) || tamanho($v) > 120) return $parte('endereco');
        if (($k === 'uf' && ufAjuste($v)[1] !== null) || ($k === 'cep' && cepAjuste($v)[1] !== null)) return 'o campo endereco.' . $k . ' não está certo';
    }
    $url = static fn($v, bool $vazio): bool => is_string($v) && (($vazio && $v === '') || (bool) preg_match('~^https?://[^\s"\'<>\\\\]+$~i', $v));
    if (array_key_exists('mapa', $d) && !$url($d['mapa'], false)) return 'o link do mapa não está certo';
    if (array_key_exists('google', $d)) {
        $g = $d['google'];
        if (array_key_exists('nota', $g) && (!$str($g['nota']) || !preg_match('/^(?:[1-4],\d|5,0)$/', $g['nota']))) return 'a nota do Google não está certa';
        if (array_key_exists('avaliacoes', $g) && (!is_int($g['avaliacoes']) || $g['avaliacoes'] < 0 || $g['avaliacoes'] > 100000)) return 'o número de avaliações do Google não está certo';
        if (array_key_exists('link', $g) && !$url($g['link'], false)) return 'o link do Google não está certo';
    }
    foreach ($d['redes'] ?? [] as $v) if (!$url($v, true)) return 'um link de rede social não está certo';
    if (array_key_exists('horario', $d)) {
        $h = $d['horario'];
        if (array_key_exists('grade', $h) && ($e = conferirGrade($h['grade'])[1]) !== null) return $e;
        if (array_key_exists('linhas', $h)) {
            if (!is_array($h['linhas']) || !array_is_list($h['linhas']) || count($h['linhas']) > 8) return $parte('horario');
            foreach ($h['linhas'] as $l) if (!is_array($l) || !$str($l['dias'] ?? null) || !$str($l['horas'] ?? null)) return $parte('horario');
        }
    }
    if (array_key_exists('feriados', $d)) {
        $f = $d['feriados'];
        foreach (['municipais' => '/^\d{2}-\d{2}$/', 'datas' => '/^\d{4}-\d{2}-\d{2}$/'] as $k => $re) {
            if (!array_key_exists($k, $f)) continue;
            if (!is_array($f[$k]) || !array_is_list($f[$k]) || count($f[$k]) > LIMITES_AJUSTES[$k] + 20) return $parte('feriados');
            foreach ($f[$k] as $x) {
                $data = is_array($x) ? ($x['data'] ?? null) : null;
                if (!$str($data) || !preg_match($re, $data) || !$str($x['nome'] ?? null)) return $parte('feriados');
                if ($k === 'municipais' ? !checkdate((int) substr($data, 0, 2), (int) substr($data, 3), 2024) : !dataValida($data)) return 'uma data dos feriados não existe';
            }
        }
        if (array_key_exists('moveis', $f)) {
            if (!is_array($f['moveis']) || !array_is_list($f['moveis'])) return $parte('feriados');
            foreach ($f['moveis'] as $n) if (!is_int($n) || !isset(FERIADOS_MOVEIS[$n])) return $parte('feriados');
        }
    }
    if (array_key_exists('aviso', $d)) {
        $a = $d['aviso'];
        if (!is_bool($a['ativo'] ?? false) || !$str($a['texto'] ?? '') || tamanho((string) ($a['texto'] ?? '')) > LIMITES_AJUSTES['aviso']) return $parte('aviso');
        foreach (['inicio', 'fim'] as $k) if (array_key_exists($k, $a) && (!$str($a[$k]) || ($a[$k] !== '' && !dataValida($a[$k])))) return 'uma data do aviso não está certa';
        if (array_key_exists('tom', $a) && !in_array($a['tom'], ['info', 'alerta'], true)) return $parte('aviso');
    }
    foreach (CAMPOS_LISTA as $k => $campos) {
        if (!array_key_exists($k, $d)) continue;
        if (!is_array($d[$k]) || !array_is_list($d[$k]) || count($d[$k]) > LIMITES_AJUSTES[$k]) return $parte($k);
        foreach ($d[$k] as $x) {
            if (!is_array($x)) return $parte($k);
            foreach ($campos as $c => [, $max]) if (array_key_exists($c, $x) && (!$str($x[$c]) || tamanho($x[$c]) > $max)) return $parte($k);
        }
    }
    if (array_key_exists('marcas', $d)) {
        if (!is_array($d['marcas']) || !array_is_list($d['marcas']) || count($d['marcas']) > LIMITES_AJUSTES['marcas']) return $parte('marcas');
        foreach ($d['marcas'] as $m) if (!$str($m) || tamanho($m) > LIMITES_AJUSTES['marca']) return $parte('marcas');
    }
    if (array_key_exists('capa', $d) && ($e = conferirCapa($d['capa'])) !== null) return $e;
    return null;
}

/* ------------------------------------------------------------------
   Formulários: o que foi digitado → [valores para a tela, estrutura, erros]
   Cada parte devolve ['v' => valores como digitados (para mostrar de novo),
   'valor' => [chave do ajustes.js => estrutura] (null se houver erro),
   'erros' => [campo => mensagem], 'nota' => texto (só nos ajudantes sem
   JavaScript, que mostram a tela de novo sem gravar)].
   ------------------------------------------------------------------ */
/** Valores para mostrar na tela a partir do que o site mostra hoje. */
function valoresDaTela(string $secao, array $atual): array
{
    switch ($secao) {
        case 'contato':
            $c = $atual['contato'];
            return ['telefone' => $c['telefone'], 'whatsapp' => formatarTelefone($c['whatsapp']), 'mensagem' => $c['whatsappMensagemPadrao'], 'email' => $c['email']];
        case 'endereco':
            return $atual['endereco'] + ['mapa' => $atual['mapa']];
        case 'horario':
            $dias = [];
            foreach (ORDEM_DIAS as $d) {
                $iv = $atual['horario']['grade'][$d] ?? [];
                $dias[$d] = ['fechado' => !$iv, 'int' => array_map(static fn(array $x): array => [horaDoForm($x[0]), horaDoForm($x[1])], $iv)];
            }
            return ['dias' => $dias];
        case 'feriados':
            $f = $atual['feriados'];
            return [
                'mun' => array_map(static fn($x): array => ['data' => diaMesTela((string) ($x['data'] ?? '')), 'nome' => (string) ($x['nome'] ?? '')], $f['municipais']),
                'moveis' => array_values(array_filter($f['moveis'], 'is_int')),
                'dias' => array_map(static fn($x): array => ['data' => (string) ($x['data'] ?? ''), 'nome' => (string) ($x['nome'] ?? '')], $f['datas']),
            ];
        case 'aviso':
            return $atual['aviso'] + ['inicio' => '', 'fim' => ''];
        case 'google':
            return ['nota' => $atual['google']['nota'], 'avaliacoes' => (string) $atual['google']['avaliacoes'], 'link' => $atual['google']['link'],
                'facebook' => $atual['redes']['facebook'], 'instagram' => $atual['redes']['instagram']];
        case 'equipe':
        case 'depoimentos':
            $itens = [];
            foreach ($atual[$secao] as $x) {
                $l = [];
                foreach (CAMPOS_LISTA[$secao] as $c => $_) $l[$c] = is_array($x) && is_string($x[$c] ?? null) ? $x[$c] : '';
                $itens[] = $l;
            }
            return ['itens' => $itens];
        case 'marcas':
            return ['texto' => implode("\n", array_map('strval', $atual['marcas']))];
        case 'capa':
            return capaParaTela($atual['capa'] ?? CAPA_ORIGINAL);
    }
    return [];
}

/** Texto do POST (ou '' se não vier texto). */
function campoPost(array $post, string $k): string
{
    return is_string($post[$k] ?? null) ? $post[$k] : '';
}

/** Linhas de uma lista do formulário (equipe[3][nome]…), na ordem em que vieram. */
function linhasDoPost(array $post, string $lista, array $campos): array
{
    $out = [];
    foreach (is_array($post[$lista] ?? null) ? $post[$lista] : [] as $chave => $l) {
        if (!is_array($l)) continue;
        $linha = ['_chave' => (string) $chave, '_remover' => ($l['remover'] ?? '') === '1'];
        foreach ($campos as $c) $linha[$c] = is_string($l[$c] ?? null) ? $l[$c] : '';
        $out[] = $linha;
        if (count($out) >= 60) break;   // ninguém digita tanto: pedido estranho
    }
    return $out;
}

/** Ajudante sem JavaScript: botões ↑ ↓ (mover=chave:cima). Devolve as linhas na ordem nova, ou null. */
function moverLinhaDoPost(array $linhas, $mover): ?array
{
    if (!is_string($mover) || !preg_match('/^([a-z0-9]{1,12}):(cima|baixo)$/', $mover, $m)) return null;
    foreach ($linhas as $i => $l) {
        if ($l['_chave'] !== $m[1]) continue;
        $j = $m[2] === 'cima' ? $i - 1 : $i + 1;
        if ($j >= 0 && $j < count($linhas)) [$linhas[$i], $linhas[$j]] = [$linhas[$j], $linhas[$i]];
        return $linhas;
    }
    return $linhas;
}

/** Tira as marcadas para remover e as totalmente vazias. */
function linhasPreenchidas(array $linhas, array $campos): array
{
    $out = [];
    foreach ($linhas as $l) {
        if ($l['_remover']) continue;
        $vazia = true;
        foreach ($campos as $c) if (trim($l[$c]) !== '') { $vazia = false; break; }
        if (!$vazia) $out[] = $l;
    }
    return $out;
}

function formularioSecao(string $secao, array $post, array $atual): array
{
    return match ($secao) {
        'contato'     => formContato($post),
        'endereco'    => formEndereco($post),
        'horario'     => formHorario($post),
        'feriados'    => formFeriados($post, $atual),
        'aviso'       => formAviso($post),
        'google'      => formGoogle($post),
        'equipe', 'depoimentos' => formLista($secao, $post),
        'marcas'      => formMarcas($post),
        'capa'        => formCapa($post),
    };
}

function formContato(array $post): array
{
    $v = ['telefone' => campoPost($post, 'telefone'), 'whatsapp' => campoPost($post, 'whatsapp'), 'mensagem' => campoPost($post, 'mensagem'), 'email' => campoPost($post, 'email')];
    $erros = [];
    [$tel, $e] = digitosTelefone($v['telefone'], 'Telefone'); if ($e) $erros['telefone'] = $e;
    [$wa, $e] = digitosTelefone($v['whatsapp'], 'WhatsApp'); if ($e) $erros['whatsapp'] = $e;
    [$msg, $e] = linhaAjuste($v['mensagem'], 'Mensagem pronta', 5, LIMITES_AJUSTES['mensagem']); if ($e) $erros['mensagem'] = $e;
    [$email, $e] = emailAjuste($v['email']); if ($e) $erros['email'] = $e;
    $valor = $erros ? null : ['contato' => [
        'telefone' => formatarTelefone($tel), 'telefoneLink' => '+55' . $tel, 'whatsapp' => '55' . $wa,
        'whatsappMensagemPadrao' => $msg, 'email' => $email,
    ]];
    return ['v' => $v, 'valor' => $valor, 'erros' => $erros, 'nota' => ''];
}

function formEndereco(array $post): array
{
    $v = [];
    foreach (['rua', 'referencia', 'bairro', 'cidade', 'uf', 'cep', 'mapa'] as $k) $v[$k] = campoPost($post, $k);
    $erros = [];
    $e = [];
    [$e['rua'], $erros['rua']] = linhaAjuste($v['rua'], 'Rua e número', 3, LIMITES_AJUSTES['rua'], true);
    [$e['referencia'], $erros['referencia']] = linhaAjuste($v['referencia'], 'Referência', 0, LIMITES_AJUSTES['referencia'], true);
    [$e['bairro'], $erros['bairro']] = linhaAjuste($v['bairro'], 'Bairro', 2, LIMITES_AJUSTES['bairro'], true);
    [$e['cidade'], $erros['cidade']] = linhaAjuste($v['cidade'], 'Cidade', 2, LIMITES_AJUSTES['cidade'], true);
    [$e['uf'], $erros['uf']] = ufAjuste($v['uf']);
    [$e['cep'], $erros['cep']] = cepAjuste($v['cep']);
    [$mapa, $erros['mapa']] = linkAjuste($v['mapa'], 'mapa', 'Link do mapa', true);
    $erros = array_filter($erros);
    return ['v' => $v, 'valor' => $erros ? null : ['endereco' => $e, 'mapa' => $mapa], 'erros' => $erros, 'nota' => ''];
}

/** Dias como digitados (['fechado' => bool, 'int' => [["08:00", "11:00"], …]]) → [grade 0…6, erros por dia]. */
function gradeDosDias(array $dias): array
{
    $grade = [];
    $erros = [];
    foreach (ORDEM_DIAS as $d) {
        $nome = ucfirst(NOMES_DIAS[$d]);
        $grade[$d] = [];
        if ($dias[$d]['fechado']) continue;
        foreach ($dias[$d]['int'] as [$a, $b]) {
            if ($a === '' || $b === '') { $erros['h-' . $d] = $nome . ': preencha o começo e o fim do horário (ex.: 08:00 e 12:00).'; continue 2; }
            $ma = minutosDoForm($a, false);
            $mb = minutosDoForm($b, true);
            if ($ma === null || $mb === null) { $erros['h-' . $d] = $nome . ': use horas como 08:00 ou 13:30.'; continue 2; }
            if ($ma >= $mb) { $erros['h-' . $d] = $nome . ': o fim (' . $b . ') precisa ser depois do começo (' . $a . ').'; continue 2; }
            $grade[$d][] = [$ma, $mb];
        }
        if (!$grade[$d]) { $erros['h-' . $d] = $nome . ': preencha o horário ou marque Fechado.'; continue; }
        usort($grade[$d], static fn(array $x, array $y): int => $x[0] <=> $y[0]);
        for ($i = 1; $i < count($grade[$d]); $i++) {
            if ($grade[$d][$i][0] < $grade[$d][$i - 1][1]) { $erros['h-' . $d] = $nome . ': os dois horários se sobrepõem. O segundo precisa começar depois que o primeiro termina.'; break; }
        }
    }
    ksort($grade);
    if (!$erros && !array_filter($grade)) $erros['h-1'] = 'Todos os dias estão como Fechado. Para fechar só por uns dias (férias, reforma), use “Dias fechados” em Feriados e mantenha o horário normal aqui.';
    return [$grade, $erros];
}

function formHorario(array $post): array
{
    $h = is_array($post['h'] ?? null) ? $post['h'] : [];
    $dias = [];
    foreach (ORDEM_DIAS as $d) {
        $x = is_array($h[$d] ?? null) ? $h[$d] : [];
        $as = is_array($x['a'] ?? null) ? $x['a'] : [];
        $bs = is_array($x['b'] ?? null) ? $x['b'] : [];
        $int = [];
        for ($i = 0; $i < LIMITES_AJUSTES['intervalos']; $i++) {
            $a = is_string($as[$i] ?? null) ? trim($as[$i]) : '';
            $b = is_string($bs[$i] ?? null) ? trim($bs[$i]) : '';
            if ($a !== '' || $b !== '') $int[] = [cortar($a, 8), cortar($b, 8)];
        }
        $dias[$d] = ['fechado' => ($x['fechado'] ?? '') === '1', 'int' => $int];
    }
    // Ajudante sem JavaScript: "Repetir o horário de segunda de terça a sexta"
    if (($post['repetir'] ?? '') === '1') {
        foreach ([2, 3, 4, 5] as $d) $dias[$d] = $dias[1];
        return ['v' => ['dias' => $dias], 'valor' => null, 'erros' => [], 'nota' => 'Copiei o horário de segunda para terça, quarta, quinta e sexta. Confira e toque em Salvar horário.'];
    }
    [$grade, $erros] = gradeDosDias($dias);
    $valor = $erros ? null : ['horario' => ['grade' => $grade, 'linhas' => linhasHorario($grade)]];
    return ['v' => ['dias' => $dias], 'valor' => $valor, 'erros' => $erros, 'nota' => ''];
}

function formFeriados(array $post, array $atual): array
{
    $mun = linhasDoPost($post, 'mun', ['data', 'nome']);
    $dias = linhasDoPost($post, 'dia', ['data', 'nome']);
    $moveis = [];
    foreach (is_array($post['moveis'] ?? null) ? $post['moveis'] : [] as $n) {
        if (is_string($n) && preg_match('/^-?\d{1,2}$/', $n) && isset(FERIADOS_MOVEIS[(int) $n])) $moveis[(int) $n] = true;
    }
    $moveis = array_values(array_filter(array_keys(FERIADOS_MOVEIS), static fn(int $n): bool => isset($moveis[$n])));
    $mun = linhasPreenchidas($mun, ['data', 'nome']);
    $dias = linhasPreenchidas($dias, ['data', 'nome']);
    $tela = static fn(array $l): array => ['data' => $l['data'], 'nome' => $l['nome']];

    // Ajudante sem JavaScript: as sugestões para Rio Claro entram no formulário (ainda sem gravar)
    $sug = campoPost($post, 'sugerir');
    if (isset(SUGESTOES_RIO_CLARO[$sug])) {
        $s = SUGESTOES_RIO_CLARO[$sug];
        if (isset($s['movel'])) { if (!in_array($s['movel'], $moveis, true)) { $moveis[] = $s['movel']; sort($moveis); } }
        else {
            $ja = false;
            foreach ($mun as $l) if (diaMesDoForm($l['data']) === $s['data']) $ja = true;
            if (!$ja) $mun[] = ['_chave' => 's', '_remover' => false, 'data' => diaMesTela($s['data']), 'nome' => $s['nome']];
        }
        $v = ['mun' => array_map($tela, $mun), 'moveis' => $moveis, 'dias' => array_map($tela, $dias)];
        return ['v' => $v, 'valor' => null, 'erros' => [], 'nota' => '“' . $s['nome'] . '” entrou na lista, mas ainda não foi salvo. Confira e toque em Salvar feriados.'];
    }

    $erros = [];
    $municipais = [];
    $usadas = [];
    foreach ($mun as $i => $l) {
        $md = diaMesDoForm($l['data']);
        if ($md === null) {
            $erros['mun-' . $i . '-data'] = 'Feriado ' . ($i + 1) . ': use dia/mês (ex.: 24/06).';
            continue;
        }
        [$nome, $e] = linhaAjuste($l['nome'], 'Feriado ' . ($i + 1) . ', nome', 2, LIMITES_AJUSTES['feriado_nome'], true);
        if ($e) { $erros['mun-' . $i . '-nome'] = $e; continue; }
        if (isset($usadas[$md])) { $erros['mun-' . $i . '-data'] = 'Feriado ' . ($i + 1) . ': o dia ' . diaMesTela($md) . ' já está na lista.'; continue; }
        $usadas[$md] = true;
        $municipais[] = ['data' => $md, 'nome' => $nome];
    }
    if (count($mun) > LIMITES_AJUSTES['municipais']) $erros['mun'] = 'Feriados da cidade: no máximo ' . LIMITES_AJUSTES['municipais'] . '.';

    $datas = [];
    $limite = date('Y-m-d', strtotime(hojeIso() . ' -' . DIAS_GUARDAR_DATAS . ' days'));
    $saiu = 0;
    $usadas = [];
    foreach ($dias as $i => $l) {
        $iso = dataDoForm($l['data']);
        if ($iso === null || $iso === '') { $erros['dia-' . $i . '-data'] = 'Dia fechado ' . ($i + 1) . ': escolha a data.'; continue; }
        [$nome, $e] = linhaAjuste($l['nome'], 'Dia fechado ' . ($i + 1) . ', motivo', 2, LIMITES_AJUSTES['feriado_nome'], true);
        if ($e) { $erros['dia-' . $i . '-nome'] = $e; continue; }
        if (isset($usadas[$iso])) { $erros['dia-' . $i . '-data'] = 'Dia fechado ' . ($i + 1) . ': o dia ' . dataBr($iso) . ' já está na lista.'; continue; }
        $usadas[$iso] = true;
        if ($iso < $limite) { $saiu++; continue; }   // já passou faz tempo: sai da lista
        $datas[] = ['data' => $iso, 'nome' => $nome];
    }
    if (count($datas) > LIMITES_AJUSTES['datas']) $erros['dia'] = 'Dias fechados: no máximo ' . LIMITES_AJUSTES['datas'] . '.';
    usort($municipais, static fn(array $a, array $b): int => strcmp($a['data'], $b['data']));
    usort($datas, static fn(array $a, array $b): int => strcmp($a['data'], $b['data']));
    $v = ['mun' => array_map($tela, $mun), 'moveis' => $moveis, 'dias' => array_map($tela, $dias)];
    $valor = $erros ? null : ['feriados' => ['municipais' => $municipais, 'moveis' => $moveis, 'datas' => $datas]];
    return ['v' => $v, 'valor' => $valor, 'erros' => $erros, 'nota' => '', 'saiu' => $saiu];
}

/** Aviso no formato do arquivo (início e fim só quando preenchidos). */
function montarAviso(bool $ativo, string $texto, string $inicio, string $fim, string $tom): array
{
    $a = ['ativo' => $ativo, 'texto' => $texto];
    if ($inicio !== '') $a['inicio'] = $inicio;
    if ($fim !== '') $a['fim'] = $fim;
    $a['tom'] = $tom;
    return $a;
}

function formAviso(array $post): array
{
    $v = ['ativo' => ($post['ativo'] ?? '') === '1', 'texto' => campoPost($post, 'texto'), 'inicio' => campoPost($post, 'inicio'),
        'fim' => campoPost($post, 'fim'), 'tom' => campoPost($post, 'tom') === 'alerta' ? 'alerta' : 'info'];
    $erros = [];
    [$texto, $e] = linhaAjuste($v['texto'], 'Texto do aviso', $v['ativo'] ? LIMITES_AJUSTES['aviso_min'] : 0, LIMITES_AJUSTES['aviso']);
    if ($e === null && $texto !== '' && tamanho($texto) < LIMITES_AJUSTES['aviso_min']) $e = 'Texto do aviso: escreva pelo menos ' . LIMITES_AJUSTES['aviso_min'] . ' caracteres.';
    if ($e) $erros['texto'] = $e;
    $inicio = dataDoForm($v['inicio']);
    $fim = dataDoForm($v['fim']);
    if ($inicio === null) $erros['inicio'] = 'Começa em: escolha uma data válida (ou deixe vazio).';
    if ($fim === null) $erros['fim'] = 'Termina em: escolha uma data válida (ou deixe vazio).';
    if ($inicio && $fim && $fim < $inicio) $erros['fim'] = 'Termina em: precisa ser no mesmo dia ou depois do começo (' . dataBr($inicio) . ').';
    elseif ($v['ativo'] && $fim && $fim < hojeIso()) $erros['fim'] = 'Termina em: essa data já passou, então o aviso não apareceria. Mude a data ou desligue o aviso.';
    $valor = $erros ? null : ['aviso' => montarAviso($v['ativo'], $texto, (string) $inicio, (string) $fim, $v['tom'])];
    return ['v' => $v, 'valor' => $valor, 'erros' => $erros, 'nota' => ''];
}

function formGoogle(array $post): array
{
    $v = [];
    foreach (['nota', 'avaliacoes', 'link', 'facebook', 'instagram'] as $k) $v[$k] = campoPost($post, $k);
    $erros = [];
    [$nota, $erros['nota']] = notaAjuste($v['nota']);
    [$qtd, $erros['avaliacoes']] = avaliacoesAjuste($v['avaliacoes']);
    [$link, $erros['link']] = linkAjuste($v['link'], 'mapa', 'Link das avaliações', true);
    [$fb, $erros['facebook']] = linkAjuste($v['facebook'], 'facebook', 'Facebook', false);
    [$ig, $erros['instagram']] = linkAjuste($v['instagram'], 'instagram', 'Instagram', false);
    $erros = array_filter($erros);
    $valor = $erros ? null : ['google' => ['nota' => $nota, 'avaliacoes' => $qtd, 'link' => $link], 'redes' => ['facebook' => $fb, 'instagram' => $ig]];
    return ['v' => $v, 'valor' => $valor, 'erros' => $erros, 'nota' => ''];
}

/** Equipe e depoimentos: linhas com ↑ ↓ e remover; sempre sobram linhas vazias para acrescentar sem JavaScript. */
function formLista(string $secao, array $post): array
{
    $campos = array_keys(CAMPOS_LISTA[$secao]);
    $linhas = linhasDoPost($post, $secao, $campos);
    $movidas = moverLinhaDoPost($linhas, $post['mover'] ?? null);
    $tela = static function (array $ls) use ($campos): array {
        return array_map(static function (array $l) use ($campos): array {
            $o = [];
            foreach ($campos as $c) $o[$c] = $l[$c];
            return $o;
        }, linhasPreenchidas($ls, $campos));
    };
    if ($movidas !== null) {
        return ['v' => ['itens' => $tela($movidas)], 'valor' => null, 'erros' => [], 'nota' => 'A ordem mudou, mas ainda não foi salva. Toque em Salvar ' . ($secao === 'equipe' ? 'equipe' : 'depoimentos') . ' para gravar.'];
    }
    $itens = $tela($linhas);
    $erros = [];
    $lista = [];
    foreach ($itens as $i => $l) {
        $item = [];
        foreach (CAMPOS_LISTA[$secao] as $c => [$rotulo, $max, $obrig, $semSinais]) {
            [$t, $e] = linhaAjuste($l[$c], ($secao === 'equipe' ? 'Pessoa ' : 'Depoimento ') . ($i + 1) . ', ' . lcfirst($rotulo), $obrig ? 2 : 0, $max, $semSinais);
            if ($e) $erros[$secao . '-' . $i . '-' . $c] = $e;
            $item[$c] = $t;
        }
        $lista[] = $item;
    }
    if (!$itens) $erros[$secao] = $secao === 'equipe' ? 'Equipe: a lista não pode ficar vazia. Deixe pelo menos 1 pessoa.' : 'Depoimentos: a lista não pode ficar vazia. Deixe pelo menos 1 depoimento.';
    if (count($itens) > LIMITES_AJUSTES[$secao]) $erros[$secao] = 'No máximo ' . LIMITES_AJUSTES[$secao] . ($secao === 'equipe' ? ' pessoas.' : ' depoimentos.');
    return ['v' => ['itens' => $itens], 'valor' => $erros ? null : [$secao => $lista], 'erros' => $erros, 'nota' => ''];
}

function formMarcas(array $post): array
{
    $v = ['texto' => cortar(campoPost($post, 'marcas'), 4000)];
    $erros = [];
    $marcas = [];
    $vistas = [];
    foreach (preg_split('/[\r\n,;]+/', $v['texto']) ?: [] as $i => $m) {
        [$t, $e] = linhaAjuste($m, 'Marca “' . cortar(trim($m), 30) . '”', 0, LIMITES_AJUSTES['marca'], true);
        if ($e) { $erros['marcas'] = $e; break; }
        if ($t === '' || isset($vistas[semAcento($t)])) continue;   // linha vazia ou repetida: fica de fora
        $vistas[semAcento($t)] = true;
        $marcas[] = $t;
    }
    if (!$erros && !$marcas) $erros['marcas'] = 'Marcas: escreva pelo menos uma (uma por linha).';
    if (!$erros && count($marcas) > LIMITES_AJUSTES['marcas']) $erros['marcas'] = 'Marcas: no máximo ' . LIMITES_AJUSTES['marcas'] . ' (há ' . count($marcas) . ').';
    return ['v' => $v, 'valor' => $erros ? null : ['marcas' => $marcas], 'erros' => $erros, 'nota' => ''];
}

/* ------------------------------------------------------------------
   Capa do site: a balança grande do topo da página inicial
   ajustes.js → "capa": {"produto": "<código>", "pontos": [{"rotulo",
   "valor", "detalhe", "x", "y"}, … até 4], "texto": "<legenda>",
   "largura": px, "altura": px}. x/y = onde fica o ponto da etiqueta,
   em % da caixa da foto (recorte), com uma casa decimal; largura e
   altura = tamanho do recorte quando os pontos foram marcados (o site
   reserva o espaço certo antes de a foto chegar). Sem a chave, o site
   mostra a Toledo Prix 7E do index.html (CAPA_ORIGINAL). Quem desenha:
   linha-verde/js/home-v3.js (mesma validação em compartilhado/js/balancas.js).
   ------------------------------------------------------------------ */
// A capa que vem no index.html (textos e pontos de linha-verde/index-3.html e css/home-v3.css)
const CAPA_ORIGINAL = [
    'produto' => 'toledo-prix-7e',
    'pontos' => [
        ['rotulo' => 'Capacidade', 'valor' => '32 kg multifaixa', 'detalhe' => 'divisão de 2 g até 6 kg', 'x' => 33, 'y' => 21],
        ['rotulo' => 'Teclado', 'valor' => '60 teclas rápidas', 'detalhe' => 'acesso a até 180 itens', 'x' => 25, 'y' => 61],
        ['rotulo' => 'Impressão', 'valor' => 'Térmica integrada', 'detalhe' => 'até 100 mm/s', 'x' => 66, 'y' => 47],
        ['rotulo' => 'Etiqueta', 'valor' => 'GS1 QR Code', 'detalhe' => 'com validade e lote', 'x' => 64, 'y' => 66],
    ],
    'texto' => 'balança computadora com impressora integrada',
];
const NOME_CAPA_ORIGINAL = 'Toledo Prix 7E';
// Onde ficam os pontos quando a pessoa troca de produto (1 e 3 em cima, 2 e 4 embaixo); o painel
// puxa cada um para o pedaço do recorte que não é transparente mais perto daqui
const CAPA_PONTOS_PADRAO = [[32, 24], [30, 70], [68, 32], [66, 72]];
// Palavras que não terminam bem uma etiqueta cortada ("Largura de" → "Largura")
const CAPA_PALAVRAS_SOLTAS = ['a', 'o', 'as', 'os', 'e', 'de', 'da', 'do', 'das', 'dos', 'em', 'no', 'na', 'nos', 'nas', 'com', 'sem', 'para', 'por', 'pelo', 'pela', 'via', 'ao', 'à', 'ou', 'até', 'que'];

/** Código do produto da capa escolhido no painel (ajustes.js "capa"), ou null: sem a chave (ou com o ajustes.js ilegível), vale a Prix 7E do index.html. */
function produtoCapaAjustes(): ?string
{
    try {
        $id = lerAjustes()['ajustes']['capa']['produto'] ?? null;
    } catch (Throwable $t) {
        return null;
    }
    return is_string($id) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id) ? $id : null;
}

/** Fotos antigas que já têm recorte sem o campo "recorte" no produto: a lista RECORTES de compartilhado/js/balancas.js. */
function recortesDoSite(): array
{
    static $lista = null;
    if ($lista !== null) return $lista;
    $lista = [];
    $js = @file_get_contents(cfg('compartilhado') . '/js/balancas.js');
    if (is_string($js) && preg_match('/\bRECORTES\s*=\s*\[([^\]]*)\]/', $js, $m) && preg_match_all('/"([^"\\\\]{1,140})"/', $m[1], $mm)) $lista = $mm[1];
    return $lista;
}

/** Arquivo do recorte (fundo transparente) que o site usa para o produto, ou null. Mesma regra de BC.temRecorte(). */
function recorteDoProduto(array $p): ?string
{
    $arq = null;
    if (is_string($p['recorte'] ?? null) && $p['recorte'] !== '') $arq = $p['recorte'];
    elseif (is_string($p['imagem'] ?? null) && in_array($p['imagem'], recortesDoSite(), true)) $arq = $p['imagem'];
    if ($arq === null || !preg_match(RE_ARQUIVO_FOTO, $arq) || !@is_file(pastaFotos('recortes') . '/' . $arq)) return null;
    return $arq;
}

/**
 * A capa guarda o arquivo e o tamanho da foto do produto escolhido (o <head> da página inicial já começa
 * a baixá-la antes dos scripts). Quando a foto desse produto muda — foto nova no formulário, cópia
 * restaurada, atualização do catálogo —, acompanha: senão cada visita pediria o arquivo antigo, que foi
 * para a lixeira. Só arquivo e tamanho mudam; textos e pontos ficam. Devolve o nome do produto quando
 * atualizou (para o aviso "confira os pontos"), ou null.
 */
function sincronizarFotoDaCapa(array $produtos): ?string
{
    $capa = lerAjustes()['ajustes']['capa'] ?? null;
    if (!is_array($capa) || !is_string($capa['produto'] ?? null)) return null;
    $p = null;
    foreach ($produtos as $x) if (($x['id'] ?? null) === $capa['produto']) { $p = $x; break; }
    if ($p === null) return null;   // saiu do catálogo: o site já volta sozinho para a capa original
    $arq = recorteDoProduto($p);
    $tam = $arq !== null ? @getimagesize(pastaFotos('recortes') . '/' . $arq) : false;
    $novo = $arq === null ? [] : ['recorte' => $arq] + (is_array($tam) ? ['largura' => (int) $tam[0], 'altura' => (int) $tam[1]] : []);
    $atual = array_intersect_key($capa, array_flip(['recorte', 'largura', 'altura']));
    ksort($atual); ksort($novo);
    if ($atual === $novo) return null;
    $nome = nomeCompleto($p);
    alterarAjustes(['tipo' => 'capa', 'secao' => 'capa', 'antes' => 'Antes de: atualizar a foto da capa do site',
        'feito' => 'Atualizou a foto da capa do site (' . $nome . ')'],
        static function (array $aj) use ($capa, $novo): array {
            if (!is_array($aj['capa'] ?? null) || ($aj['capa']['produto'] ?? null) !== $capa['produto']) return $aj;   // a capa mudou nesse meio-tempo
            $c = $aj['capa'];
            unset($c['recorte'], $c['largura'], $c['altura']);
            $aj['capa'] = $c + $novo;
            return $aj;
        });
    return $nome;
}

/** Por que o produto não pode ficar na capa ('' = pode): precisa estar no site e ter recorte. */
function motivoForaDaCapa(?array $p): string
{
    if ($p === null) return 'não está mais no catálogo';
    if (($p['visivel'] ?? true) === false) return 'está escondido do site';
    if (recorteDoProduto($p) === null) return 'não tem foto com fundo transparente (recorte)';
    return '';
}

/**
 * Produtos do catálogo para a parte "Capa do site": [código => item], na ordem do catálogo.
 * item: id, nome (marca + nome), marca, modelo, categoria, ok (pode ir para a capa), motivo,
 * arquivo e url do recorte (ou da foto), largura e altura da foto, sugestao (etiquetas e legenda).
 */
function infoCapa(): array
{
    static $info = null;
    if ($info !== null) return $info;
    try {
        $cat = lerCatalogo();
    } catch (Throwable $t) {
        return $info = ['produtos' => [], 'categorias' => [], 'lista' => [], 'erro' => $t->getMessage()];
    }
    $lista = [];
    foreach ($cat['produtos'] as $p) {
        if (!is_string($p['id'] ?? null)) continue;
        $arq = recorteDoProduto($p);
        $tipo = $arq !== null ? 'recortes' : 'produtos';
        $arqFoto = $arq ?? (is_string($p['imagem'] ?? null) && preg_match(RE_ARQUIVO_FOTO, $p['imagem']) ? $p['imagem'] : '');
        $tam = $arqFoto !== '' ? @getimagesize(pastaFotos($tipo) . '/' . $arqFoto) : false;
        $motivo = motivoForaDaCapa($p);
        $lista[$p['id']] = [
            'id' => $p['id'], 'nome' => nomeCompleto($p), 'marca' => (string) ($p['marca'] ?? ''), 'modelo' => (string) ($p['nome'] ?? ''),
            'categoria' => (string) ($p['categoria'] ?? ''), 'ok' => $motivo === '', 'motivo' => $motivo,
            'arquivo' => $arqFoto, 'caminho' => $arqFoto !== '' ? pastaFotos($tipo) . '/' . $arqFoto : '',
            'url' => $arqFoto !== '' ? urlFoto($arqFoto, $tipo) : '',
            'largura' => is_array($tam) ? (int) $tam[0] : 0, 'altura' => is_array($tam) ? (int) $tam[1] : 0,
            'sugestao' => sugestaoCapa($p),
        ];
    }
    return $info = ['produtos' => $lista, 'categorias' => $cat['categorias'], 'lista' => $cat['produtos'], 'erro' => null];
}

/** Nome do produto para avisos e registro ("Toledo Prix 7T"); o código, se ele saiu do catálogo. */
function nomeProdutoCapa(string $id): string
{
    if ($id === CAPA_ORIGINAL['produto']) return NOME_CAPA_ORIGINAL;
    return infoCapa()['produtos'][$id]['nome'] ?? '“' . $id . '”';
}

/** Corta um texto em até $max letras sem quebrar palavra nem terminar em "de", "com", "ou"… (nem num número solto, se cortou). */
function cortarCapa(string $t, int $max): string
{
    $t = trim(preg_replace('/\s+/u', ' ', $t) ?? '', " \t,;:.-–—/");
    $out = explode(' ', $t);
    $cortou = tamanho($t) > $max;
    if ($cortou) {
        $out = [];
        foreach (explode(' ', $t) as $w) {
            if (tamanho(implode(' ', array_merge($out, [$w]))) > $max) break;
            $out[] = $w;
        }
        // Cortou no meio de "… de alta [resolução]": sai o pedaço que ficou pela metade ("LCD extragrande")
        if (count($out) >= 3 && in_array(mb_strtolower($out[count($out) - 2], 'UTF-8'), CAPA_PALAVRAS_SOLTAS, true)) array_splice($out, -2);
    }
    while ($out) {
        $ult = mb_strtolower(trim((string) end($out), ',;:'), 'UTF-8');
        if (!in_array($ult, CAPA_PALAVRAS_SOLTAS, true) && !($cortou && preg_match('/^\d+(?:[.,]\d+)?$/', $ult))) break;
        array_pop($out);
    }
    $r = trim(implode(' ', $out), " ,;:-–—/");
    return $r !== '' ? $r : cortar($t, $max);
}

/** Junta partes ("2 g até 6 kg", "5 g até 15 kg"…) enquanto couberem em $max letras. Nem a primeira cabe: '' . */
function juntarAte(array $partes, string $sep, int $max): string
{
    $out = '';
    foreach ($partes as $x) {
        $x = trim($x);
        if ($x === '') continue;
        $novo = $out === '' ? $x : $out . $sep . $x;
        if (tamanho($novo) > $max) break;
        $out = $novo;
    }
    return $out;
}

/**
 * O maior começo de $t que cabe em $max letras terminando num separador natural (vírgula, ponto e vírgula,
 * parêntese, " com ", " e ", " ou ", " de ", " para "): "estrutura em aço carbono e tampa em aço inox" → "estrutura em aço carbono".
 * O resto vai fora (nunca vira outro pedaço da etiqueta). null = nenhum começo assim cabe.
 */
function cortarNoSeparador(string $t, int $max): ?string
{
    $t = trim($t);
    if (tamanho($t) <= $max) return $t;
    $melhor = null;
    if (preg_match_all('/\s*[,;(]|\s(?:com|e|ou|de|para)\s/u', $t, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as [, $pos]) {
            $antes = trim(substr($t, 0, $pos), " \t,;:-–—/");
            if ($antes === '' || tamanho($antes) > $max) break;   // os separadores vêm em ordem: daqui em diante só fica maior
            $ult = mb_strtolower((string) preg_replace('/^.*\s/u', '', $antes), 'UTF-8');
            if (!in_array($ult, CAPA_PALAVRAS_SOLTAS, true)) $melhor = $antes;
        }
    }
    return $melhor;
}

/**
 * Uma linha da ficha técnica → [rótulo, valor, detalhe] curtos para a etiqueta da capa.
 * "Capacidade" + "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)" → Capacidade · 32 kg · 2 g até 6 kg / 5 g até 15 kg.
 * É só um ponto de partida: a pessoa revê tudo antes de salvar.
 */
function etiquetaDaFicha(string $nome, string $valor): array
{
    $max = LIMITES_AJUSTES;
    $nome = trim(preg_split('~\s+/\s+|\s*\(~u', $nome)[0] ?? $nome);
    $rotulo = cortarCapa($nome, $max['capa_rotulo']);
    $valor = trim(preg_replace('/\s+/u', ' ', $valor) ?? '');
    // Principal = antes do "(" ou do ";"; o que vem entre parênteses ou depois vira o detalhe
    if (preg_match('/^([^(;]+?)\s*\((.+?)\)\s*[;,]?\s*(.*)$/u', $valor, $m)) { $principal = $m[1]; $resto = trim($m[2] . ($m[3] !== '' ? '; ' . $m[3] : '')); }
    elseif (preg_match('/^([^;]+?)\s*;\s*(.*)$/u', $valor, $m)) { $principal = $m[1]; $resto = $m[2]; }
    else { $principal = $valor; $resto = ''; }
    if (tamanho($principal) > $max['capa_valor']) {
        // Pedaços inteiros (até a vírgula) que cabem; as frases depois da vírgula vão para a frente do detalhe.
        // Nem a primeira frase cabe: corta num separador natural (" com ", " e "…) ou numa palavra inteira, e o
        // resto sai (uma frase nunca fica dividida entre o destaque e o detalhe)
        $partes = preg_split('/,\s+/u', $principal) ?: [$principal];
        $curto = juntarAte($partes, ', ', $max['capa_valor']);
        if ($curto !== '') {
            $sobra = str_starts_with($principal, $curto) ? trim(substr($principal, strlen($curto)), ' ,;') : '';
            $resto = trim($sobra . ($sobra !== '' && $resto !== '' ? '; ' : '') . $resto);
        } else {
            $curto = cortarNoSeparador((string) $partes[0], $max['capa_valor']) ?? cortarCapa((string) $partes[0], $max['capa_valor']);
        }
        $principal = $curto;
    }
    $detalhe = '';
    if ($resto !== '') {
        $primeiro = trim(preg_split('/\s*;\s*/u', $resto)[0] ?? $resto);
        if (tamanho($primeiro) <= $max['capa_detalhe']) $detalhe = $primeiro;
        else {
            // Partes inteiras que cabem ("2 g até 6 kg / 5 g até 15 kg"); senão, até um separador natural; senão, vazio
            $sep = str_contains($primeiro, ' / ') ? ' / ' : ', ';
            $detalhe = juntarAte(explode($sep, $primeiro), $sep, $max['capa_detalhe']);
            if ($detalhe === '') $detalhe = cortarNoSeparador($primeiro, $max['capa_detalhe']) ?? '';
        }
    }
    return [$rotulo, $principal, $detalhe];
}

/** Legenda sugerida ("Marca Nome — <texto>"): o começo do resumo do produto, curto e com inicial minúscula. */
function legendaSugerida(array $p): string
{
    $r = trim((string) ($p['resumo'] ?? ''));
    $r = preg_split('/\s*(?:[,;.(]|\s—\s|\s–\s|\scom\s|\sque\s|\spara\s)\s*/u', $r)[0] ?? '';
    $r = cortarCapa($r, LIMITES_AJUSTES['capa_texto']);
    // "Etiquetadora de 32 kg" → "etiquetadora de 32 kg" (siglas e marcas ficam como estão)
    if (preg_match('/^(\p{Lu})(\p{Ll})/u', $r)) $r = mb_strtolower(mb_substr($r, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($r, 1, null, 'UTF-8');
    return $r;
}

/** Etiquetas sugeridas para um produto: as 4 primeiras linhas da ficha, nos pontos padrão; e a legenda. */
function sugestaoCapa(array $p): array
{
    $pontos = [];
    foreach (array_slice(is_array($p['especificacoes'] ?? null) ? $p['especificacoes'] : [], 0, LIMITES_AJUSTES['capa_pontos']) as $i => $s) {
        if (!is_array($s) || !is_string($s[0] ?? null) || !is_string($s[1] ?? null)) continue;
        [$r, $v, $d] = etiquetaDaFicha($s[0], $s[1]);
        if ($r === '' || $v === '') continue;
        $pontos[] = ['rotulo' => $r, 'valor' => $v, 'detalhe' => $d, 'x' => CAPA_PONTOS_PADRAO[count($pontos)][0], 'y' => CAPA_PONTOS_PADRAO[count($pontos)][1]];
    }
    return ['pontos' => $pontos, 'texto' => legendaSugerida($p)];
}

/**
 * Puxa os pontos para cima do produto: para cada ponto, o lugar não transparente do recorte mais
 * perto dele (de preferência longe da borda). Sem GD ou sem o arquivo, devolve os pontos como vieram.
 */
function pontosNoProduto(string $caminho, array $pontos): array
{
    if ($caminho === '' || !@is_file($caminho)) return $pontos;
    $ext = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
    $abrir = ['webp' => 'imagecreatefromwebp', 'png' => 'imagecreatefrompng', 'jpg' => 'imagecreatefromjpeg', 'jpeg' => 'imagecreatefromjpeg'][$ext] ?? null;
    if ($abrir === null || !function_exists($abrir) || $ext === 'jpg' || $ext === 'jpeg') return $pontos;   // JPEG não tem transparência
    $img = @$abrir($caminho);
    if (!$img) return $pontos;
    $w = imagesx($img); $h = imagesy($img); $n = 40;
    $cheio = [];
    for ($j = 0; $j < $n; $j++) for ($i = 0; $i < $n; $i++) {
        $a = (imagecolorat($img, (int) min($w - 1, ($i + .5) * $w / $n), (int) min($h - 1, ($j + .5) * $h / $n)) >> 24) & 0x7F;
        $cheio[$j][$i] = $a < 60;
    }
    $dentro = static function (int $i, int $j) use ($cheio, $n): bool {
        foreach ([[0, 0], [1, 0], [-1, 0], [0, 1], [0, -1]] as [$di, $dj]) if (!($cheio[$j + $dj][$i + $di] ?? false)) return false;
        return true;
    };
    foreach ($pontos as $k => $pt) {
        $alvo = [(float) $pt['x'] / 100 * $n - .5, (float) $pt['y'] / 100 * $n - .5];
        if (($cheio[(int) round($alvo[1])][(int) round($alvo[0])] ?? false) && $dentro((int) round($alvo[0]), (int) round($alvo[1]))) continue;
        $melhor = null;
        foreach ([true, false] as $exigeDentro) {
            for ($j = 0; $j < $n; $j++) for ($i = 0; $i < $n; $i++) {
                if (!$cheio[$j][$i] || ($exigeDentro && !$dentro($i, $j))) continue;
                $d = ($i - $alvo[0]) ** 2 + ($j - $alvo[1]) ** 2;
                if ($melhor === null || $d < $melhor[0]) $melhor = [$d, $i, $j];
            }
            if ($melhor !== null) break;
        }
        if ($melhor !== null) {
            $pontos[$k]['x'] = numeroCapa(($melhor[1] + .5) / $n * 100);
            $pontos[$k]['y'] = numeroCapa(($melhor[2] + .5) / $n * 100);
        }
    }
    imagedestroy($img);
    return $pontos;
}

/** Posição com uma casa decimal; número inteiro sem ".0" (o JSON grava 33 e 33.5, e lê de volta igual). */
function numeroCapa(float $v)
{
    $v = round(max(0.0, min(100.0, $v)), 1);
    return abs($v - round($v)) < 1e-9 ? (int) round($v) : $v;
}

/** "33", "33.5", "33,5" → número de 0 a 100 com uma casa; null = inválido. */
function posicaoDoForm(string $t)
{
    $t = str_replace(',', '.', trim($t));
    if (!preg_match('/^\d{1,3}(?:\.\d+)?$/', $t) || (float) $t > 100) return null;
    return numeroCapa((float) $t);
}

/** Confere a parte "capa" lida do arquivo (null = no formato). Campos desconhecidos dentro dela passam. */
function conferirCapa($c): ?string
{
    $erro = 'a parte “capa” não está no formato do painel';
    if (!is_array($c) || ($c && array_is_list($c))) return $erro;
    if (!is_string($c['produto'] ?? null) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $c['produto']) || strlen($c['produto']) > 80) return 'o produto da capa não está certo';
    $pontos = $c['pontos'] ?? [];
    if (!is_array($pontos) || !array_is_list($pontos) || count($pontos) > LIMITES_AJUSTES['capa_pontos']) return $erro;
    foreach ($pontos as $pt) {
        if (!is_array($pt)) return $erro;
        foreach (['rotulo' => 'capa_rotulo', 'valor' => 'capa_valor', 'detalhe' => 'capa_detalhe'] as $k => $lim) {
            if (array_key_exists($k, $pt) && (!is_string($pt[$k]) || tamanho($pt[$k]) > LIMITES_AJUSTES[$lim])) return 'uma etiqueta da capa não está certa';
        }
        foreach (['x', 'y'] as $k) if (!(is_int($pt[$k] ?? null) || is_float($pt[$k] ?? null)) || $pt[$k] < 0 || $pt[$k] > 100) return 'a posição de uma etiqueta da capa não está certa';
    }
    if (array_key_exists('texto', $c) && (!is_string($c['texto']) || tamanho($c['texto']) > LIMITES_AJUSTES['capa_texto'])) return 'a legenda da capa não está certa';
    foreach (['largura', 'altura'] as $k) if (array_key_exists($k, $c) && (!is_int($c[$k]) || $c[$k] < 1 || $c[$k] > 20000)) return 'o tamanho da foto da capa não está certo';
    if (array_key_exists('recorte', $c) && (!is_string($c['recorte']) || !preg_match(RE_ARQUIVO_FOTO, $c['recorte']))) return 'a foto da capa não está certa';
    return null;
}

/**
 * A capa original (a Prix 7E do index.html) pode ficar no topo? '' = pode; senão, por quê. O site mostra a foto
 * do HTML de qualquer jeito (sem precisar de recorte no catálogo), mas o link da legenda abre a ficha dela.
 */
function motivoOriginalForaDoSite(array $produtos): string
{
    $id = produtoCapaHtml();
    foreach ($produtos as $p) if (($p['id'] ?? null) === $id) return ($p['visivel'] ?? true) === false ? 'está escondida do site' : '';
    return 'não está mais no catálogo';
}

/**
 * Aviso quando a capa do topo da página inicial não está certa, ou null:
 * - a escolhida no painel não pode aparecer (o site mostra a Prix 7E no lugar);
 * - e/ou a capa original (a Prix 7E) saiu do site: o topo mostra a foto dela com o link para um produto fora do catálogo.
 * $produtos = catálogo atual (lista de produtos); $id = código escolhido (null: lê o ajustes.js).
 * Para a lista de Produtos e a parte "Capa do site" da área Empresa.
 */
function problemaCapa(array $produtos, ?string $id = null): ?string
{
    $id ??= produtoCapaAjustes();
    $orig = motivoOriginalForaDoSite($produtos);
    $quebrada = 'a página inicial mostra a foto dela com o link para um produto fora do catálogo';
    if ($id === null || $id === produtoCapaHtml()) {
        if ($orig === '') return null;
        return 'A capa do site, a ' . NOME_CAPA_ORIGINAL . ', ' . $orig . ': ' . $quebrada . '. '
            . ($orig === 'está escondida do site' ? 'Mostre a ' . NOME_CAPA_ORIGINAL . ' no site de novo ou escolha' : 'Escolha') . ' outro produto em Empresa → Capa do site.';
    }
    $p = null;
    foreach ($produtos as $x) if (($x['id'] ?? null) === $id) { $p = $x; break; }
    $motivo = motivoForaDaCapa($p);
    if ($motivo === '') return null;
    $nome = $p !== null ? nomeCompleto($p) : '“' . $id . '”';
    $saida = match (true) {
        $p === null => 'Escolha outro produto',
        ($p['visivel'] ?? true) === false => 'Mostre o produto no site de novo ou escolha outro',
        default => 'Ponha de novo uma foto com fundo transparente no produto ou escolha outro',
    };
    if ($orig !== '') {
        return 'O produto escolhido para a capa do site, ' . $nome . ', ' . $motivo . ', e a ' . NOME_CAPA_ORIGINAL . ' (a capa original, que entraria no lugar) também '
            . $orig . ': ' . $quebrada . '. ' . $saida . ' em Empresa → Capa do site.';
    }
    return 'O produto escolhido para a capa do site, ' . $nome . ', ' . $motivo . ': por isso a página inicial mostra a '
        . NOME_CAPA_ORIGINAL . ' (a capa original) no lugar. ' . $saida . ' em Empresa → Capa do site.';
}

/**
 * O que acontece com a capa quando o produto que está nela sai do site (esconder, excluir, mudar o código).
 * $feito = false: "vai…" (confirmação, antes); true: "agora…" (aviso, depois). $quem = o produto que sai (o que
 * o topo do site mostra de fato: capaEfetiva(); vazio = produtoCapa()). $produtos = catálogo de agora (ou de
 * depois, com $feito), para saber se a capa original ainda pode entrar no lugar (null: lê o catálogo).
 */
function fraseCapaFora(bool $feito = false, string $quem = '', ?array $produtos = null): string
{
    $id = produtoCapaAjustes();
    $quem = $quem !== '' ? $quem : produtoCapa();
    if ($id !== null && $id !== CAPA_ORIGINAL['produto'] && $quem === $id) {
        if ($produtos === null) {
            try { $produtos = lerCatalogo()['produtos']; } catch (Throwable $t) { $produtos = []; }
        }
        $orig = $produtos ? motivoOriginalForaDoSite($produtos) : '';
        if ($orig !== '') {
            return ($feito ? 'A página inicial voltou para a ' : 'A página inicial volta para a ') . NOME_CAPA_ORIGINAL . ' (a capa original), mas ela também ' . $orig
                . ': o topo ' . ($feito ? 'mostra' : 'vai mostrar') . ' a foto dela com o link para um produto fora do catálogo. Escolha outro produto em Empresa → Capa do site.';
        }
        return $feito ? 'A página inicial voltou a mostrar a ' . NOME_CAPA_ORIGINAL . ' (a capa original). Para pôr outro produto na capa, use Empresa → Capa do site.'
            : 'A página inicial volta a mostrar a ' . NOME_CAPA_ORIGINAL . ' (a capa original) até você escolher outro produto em Empresa → Capa do site.';
    }
    return $feito ? 'A foto da capa continua, mas o link dela agora abre o catálogo com o aviso “produto fora do catálogo”. Para pôr outro produto na capa, use Empresa → Capa do site.'
        : 'A foto da capa continua lá, mas o link dela vai abrir o catálogo com o aviso “produto fora do catálogo”. Para pôr outro produto na capa, use Empresa → Capa do site.';
}

/** Valores da tela (4 etiquetas, sempre) a partir de uma capa no formato do arquivo. */
function capaParaTela(array $c): array
{
    $pontos = [];
    for ($i = 0; $i < LIMITES_AJUSTES['capa_pontos']; $i++) {
        $pt = $c['pontos'][$i] ?? null;
        $pontos[] = is_array($pt)
            ? ['rotulo' => (string) ($pt['rotulo'] ?? ''), 'valor' => (string) ($pt['valor'] ?? ''), 'detalhe' => (string) ($pt['detalhe'] ?? ''),
                'x' => isset($pt['x']) ? (string) $pt['x'] : '', 'y' => isset($pt['y']) ? (string) $pt['y'] : '']
            : ['rotulo' => '', 'valor' => '', 'detalhe' => '', 'x' => '', 'y' => ''];
    }
    return ['produto' => (string) ($c['produto'] ?? ''), 'texto' => (string) ($c['texto'] ?? ''), 'pontos' => $pontos];
}

/** Etiquetas sugeridas para o produto (pontos já sobre o produto), no formato da tela. */
function capaSugeridaParaTela(array $item): array
{
    $s = $item['sugestao'];
    return capaParaTela(['produto' => $item['id'], 'texto' => $s['texto'], 'pontos' => pontosNoProduto($item['caminho'], $s['pontos'])]);
}

function formCapa(array $post): array
{
    $info = infoCapa();
    $v = ['produto' => campoPost($post, 'produto'), 'texto' => campoPost($post, 'texto'), 'pontos' => []];
    $lin = is_array($post['ponto'] ?? null) ? $post['ponto'] : [];
    for ($i = 0; $i < LIMITES_AJUSTES['capa_pontos']; $i++) {
        $l = is_array($lin[$i] ?? null) ? $lin[$i] : [];
        $pt = [];
        foreach (['rotulo', 'valor', 'detalhe', 'x', 'y'] as $k) $pt[$k] = cortar(campoPost($l, $k), 80);
        $v['pontos'][] = $pt;
    }
    // "Voltar para a Prix 7E": tira a chave "capa"
    if (($post['original'] ?? '') === '1') return ['v' => $v, 'valor' => ['capa' => null], 'erros' => [], 'nota' => ''];
    if ($info['erro'] !== null) return ['v' => $v, 'valor' => null, 'erros' => ['produto' => 'Não consegui ler o catálogo de produtos (' . $info['erro'] . '). Resolva isso em Produtos e tente de novo.'], 'nota' => ''];
    $item = $info['produtos'][$v['produto']] ?? null;
    // Ajudante sem JavaScript: "Usar este produto" preenche as etiquetas com a ficha dele (ainda sem gravar)
    if (($post['trocar'] ?? '') === '1') {
        if ($item === null || !$item['ok']) return ['v' => $v, 'valor' => null, 'erros' => ['produto' => 'Produto: escolha um produto da lista.'], 'nota' => ''];
        $nova = $v['produto'] === CAPA_ORIGINAL['produto'] ? capaParaTela(CAPA_ORIGINAL) : capaSugeridaParaTela($item);
        return ['v' => $nova, 'valor' => null, 'erros' => [], 'nota' => 'As etiquetas foram preenchidas com a ficha de ' . $item['nome'] . ', mas ainda não foram salvas. Confira os textos e a posição de cada ponto e toque em Salvar capa.'];
    }
    $erros = [];
    if ($item === null) $erros['produto'] = 'Produto: escolha um produto da lista.';
    elseif (!$item['ok']) $erros['produto'] = 'Produto: ' . $item['nome'] . ' ' . $item['motivo'] . '. Escolha outro (ou ' . (str_contains($item['motivo'], 'escondido') ? 'mostre ele no site antes' : 'ponha uma foto com fundo transparente nele antes') . ').';
    $pontos = [];
    $vazioDepois = null;
    foreach ($v['pontos'] as $i => $pt) {
        $n = $i + 1;
        if (trim($pt['rotulo'] . $pt['valor'] . $pt['detalhe']) === '') { $vazioDepois ??= $n; continue; }
        if ($vazioDepois !== null) { $erros['p' . $i . '-rotulo'] = 'Etiqueta ' . $vazioDepois . ' está vazia, mas a ' . $n . ' não: preencha a ' . $vazioDepois . ' ou passe o texto da ' . $n . ' para ela (só as últimas podem ficar vazias).'; continue; }
        $novo = [];
        [$novo['rotulo'], $e] = linhaAjuste($pt['rotulo'], 'Etiqueta ' . $n . ', nome', 1, LIMITES_AJUSTES['capa_rotulo'], true); if ($e) $erros['p' . $i . '-rotulo'] = $e;
        [$novo['valor'], $e] = linhaAjuste($pt['valor'], 'Etiqueta ' . $n . ', destaque', 1, LIMITES_AJUSTES['capa_valor'], true); if ($e) $erros['p' . $i . '-valor'] = $e;
        [$novo['detalhe'], $e] = linhaAjuste($pt['detalhe'], 'Etiqueta ' . $n . ', detalhe', 0, LIMITES_AJUSTES['capa_detalhe'], true); if ($e) $erros['p' . $i . '-detalhe'] = $e;
        foreach (['x' => 'da esquerda para a direita', 'y' => 'de cima para baixo'] as $k => $sentido) {
            $num = posicaoDoForm($pt[$k]);
            if ($num === null) $erros['p' . $i . '-' . $k] = 'Etiqueta ' . $n . ': marque o ponto tocando na foto (ou escreva a posição ' . $sentido . ', de 0 a 100).';
            $novo[$k] = $num ?? 0;
        }
        $pontos[] = $novo;
    }
    [$texto, $e] = linhaAjuste($v['texto'], 'Legenda', 0, LIMITES_AJUSTES['capa_texto'], true);
    if ($e) $erros['texto'] = $e;
    if ($erros) return ['v' => $v, 'valor' => null, 'erros' => $erros, 'nota' => ''];
    $capa = ['produto' => $item['id'], 'pontos' => $pontos, 'texto' => $texto];
    if ($item['largura'] > 0 && $item['altura'] > 0) $capa += ['largura' => $item['largura'], 'altura' => $item['altura']];
    // Arquivo do recorte: o <head> do index.html já começa a baixar a foto certa (sem esperar os scripts)
    if (preg_match(RE_ARQUIVO_FOTO, $item['arquivo'])) $capa['recorte'] = $item['arquivo'];   // produto ok = arquivo é o recorte
    // Igual à capa que vem no index.html: não precisa da chave (o site mostra a original, do jeito de sempre)
    if (capaIgualOriginal($capa)) return ['v' => $v, 'valor' => ['capa' => null], 'erros' => [], 'nota' => ''];
    return ['v' => $v, 'valor' => ['capa' => $capa], 'erros' => [], 'nota' => ''];
}

function capaIgualOriginal(array $c): bool
{
    return ($c['produto'] ?? '') === CAPA_ORIGINAL['produto'] && ($c['texto'] ?? '') === CAPA_ORIGINAL['texto'] && ($c['pontos'] ?? null) == CAPA_ORIGINAL['pontos'];
}

/** Frase do registro para a capa: "Trocou a capa do site para Toledo Prix 7T", "Mudou as etiquetas da capa do site"… */
function mudancaCapa(?array $a, ?array $d): string
{
    if ($d === null) return 'Voltou a capa do site para a ' . NOME_CAPA_ORIGINAL . ' (a original)';
    $a ??= CAPA_ORIGINAL;
    if (($a['produto'] ?? '') !== $d['produto']) return 'Trocou a capa do site para ' . nomeProdutoCapa($d['produto']);
    $p = [];
    $ta = array_map(static fn(array $x): array => [$x['rotulo'] ?? '', $x['valor'] ?? '', $x['detalhe'] ?? ''], $a['pontos'] ?? []);
    $td = array_map(static fn(array $x): array => [$x['rotulo'] ?? '', $x['valor'] ?? '', $x['detalhe'] ?? ''], $d['pontos']);
    $pa = array_map(static fn(array $x): array => [(float) ($x['x'] ?? 0), (float) ($x['y'] ?? 0)], $a['pontos'] ?? []);
    $pd = array_map(static fn(array $x): array => [(float) $x['x'], (float) $x['y']], $d['pontos']);
    if ($ta !== $td) $p[] = 'as etiquetas';
    if ($pa !== $pd && count($pa) === count($pd)) $p[] = 'a posição dos pontos';
    if (($a['texto'] ?? '') !== ($d['texto'] ?? '')) $p[] = 'a legenda';
    return 'Mudou ' . ($p ? juntarFrases($p) : 'a capa') . ' da capa do site (' . nomeProdutoCapa($d['produto']) . ')';
}

/** Texto da confirmação de "Voltar para a Prix 7E (capa original)". $produtos: para avisar se a Prix 7E saiu do site. */
function textoVoltarCapaOriginal(array $capa, array $produtos = []): string
{
    $orig = $produtos ? motivoOriginalForaDoSite($produtos) : '';
    return 'A página inicial deixa de mostrar ' . nomeProdutoCapa((string) ($capa['produto'] ?? '')) . ' e volta para a ' . NOME_CAPA_ORIGINAL
        . ', com as etiquetas de sempre. As etiquetas que você escreveu ficam guardadas na cópia de segurança (aba Empresa em Cópias), se quiser voltar atrás.'
        . ($orig !== '' ? ' Atenção: a ' . NOME_CAPA_ORIGINAL . ' ' . $orig . ': o topo vai mostrar a foto dela com o link para um produto fora do catálogo.' : '');
}

/* ------------------------------------------------------------------
   Cópias de segurança (pasta privada copias/ajustes-*.php)
   Mesmas regras das cópias do catálogo (config.php): as 30 mais
   recentes, tudo dos últimos 14 dias, restaurar fica 30 dias.
   ------------------------------------------------------------------ */
function fazerCopiaAjustes(array $antes, array $acao): string
{
    $tipo = preg_match('/^[a-z]{3,12}$/', (string) ($acao['tipo'] ?? '')) ? (string) $acao['tipo'] : 'outro';
    $micro = microtime(true);
    $agora = (int) $micro;
    $nome = 'ajustes-' . date('Ymd-His', $agora) . '-' . sprintf('%03d', min(999, (int) (($micro - $agora) * 1000))) . '-' . bin2hex(random_bytes(2)) . '-' . $tipo . '.php';
    gravarJsonPrivado('copias/' . $nome, [
        'versao' => 1,
        'area' => 'ajustes',
        'criado' => $agora,
        'ultimo' => $agora,
        'usuario' => usuarioLogado() ?? '',
        'tipo' => $tipo,
        'motivo' => (string) ($acao['antes'] ?? ''),
        'feitos' => [(string) ($acao['feito'] ?? '')],
        'juntou' => 1,
        'secao' => (string) ($acao['secao'] ?? ''),
        'existia' => (bool) ($antes['existe'] ?? true),
        'ajustes' => ajustesParaJson($antes['ajustes']),
    ]);
    podarCopias(arquivosCopiasAjustes(), RE_COPIA_AJUSTES);
    return $nome;
}

/** Caminhos das cópias dos ajustes, mais novas primeiro. */
function arquivosCopiasAjustes(): array
{
    $lista = glob(pastaPrivada() . '/copias/ajustes-[0-9]*.php') ?: [];
    $lista = array_filter($lista, static fn($a) => (bool) preg_match(RE_COPIA_AJUSTES, basename($a)));
    rsort($lista, SORT_STRING);
    return array_values($lista);
}

/** Partes ajustadas numa cópia ("Contato e WhatsApp, Horário…"), pela ordem da tela. */
function partesAjustadas(array $aj): array
{
    $out = [];
    foreach (CHAVES_DA_SECAO as $secao => $chaves) {
        foreach ($chaves as $k) if (array_key_exists($k, $aj)) { $out[] = SECOES_AJUSTES[$secao]; break; }
    }
    return $out;
}

function listarCopiasAjustes(): array
{
    $out = [];
    foreach (arquivosCopiasAjustes() as $a) {
        $d = lerJsonPrivado('copias/' . basename($a));
        if (!is_array($d) || !is_array($d['ajustes'] ?? null)) continue;
        $info = infoNomeCopia(basename($a), RE_COPIA_AJUSTES);
        $out[] = [
            'nome' => basename($a),
            'criado' => (int) ($d['criado'] ?? $info['criado']),
            'ultimo' => (int) ($d['ultimo'] ?? $d['criado'] ?? $info['criado']),
            'usuario' => (string) ($d['usuario'] ?? ''),
            'tipo' => (string) ($d['tipo'] ?? $info['tipo']),
            'motivo' => (string) ($d['motivo'] ?? ''),
            'feitos' => feitosDaCopia($d),
            'juntou' => max(1, (int) ($d['juntou'] ?? 1)),
            'secao' => (string) ($d['secao'] ?? ''),
            'partes' => partesAjustadas($d['ajustes']),
            'existia' => ($d['existia'] ?? true) !== false,
        ];
    }
    return $out;
}

function lerCopiaAjustes(string $nome): array
{
    if (!preg_match(RE_COPIA_AJUSTES, $nome)) throw new ErroUsuario('Cópia inválida.');
    $d = lerJsonPrivado('copias/' . $nome);
    if (!is_array($d) || !is_array($d['ajustes'] ?? null)) throw new ErroUsuario('Essa cópia não foi encontrada ou está danificada.');
    try {
        $d['ajustes'] = ordenarAjustes(interpretarAjustes(montarAjustes($d['ajustes'], '')));   // valida como se fosse o arquivo do site
    } catch (ErroAjustes $e) {
        throw new ErroUsuario('Essa cópia está danificada (' . $e->getMessage() . ').');
    }
    return $d;
}

/** Texto da confirmação de restaurar a cópia $i (lista de listarCopiasAjustes(), mais novas primeiro). */
function textoRestaurarAjustes(array $copias, int $i): string
{
    $c = $copias[$i];
    $n = 0;
    for ($j = 0; $j <= $i; $j++) $n += (int) $copias[$j]['juntou'];
    $frases = resumoFeitos($copias, $i);
    $max = 6;
    $linhas = array_map(static fn($f) => '• ' . $f, array_slice($frases, 0, $max));
    if (count($frases) > $max) $linhas[] = '• e mais ' . (count($frases) - $max) . (count($frases) - $max === 1 ? ' item' : ' itens');
    $partes = $c['partes'] ? 'Na cópia, ajustado pelo painel: ' . implode(', ', $c['partes']) . '; o resto vem dos dados originais do site.'
        : 'Na cópia, nada tinha sido ajustado pelo painel: o site volta a mostrar os dados originais.';
    return 'Voltar os dados da empresa para como estavam em ' . quando($c['criado']) . '? '
        . ($n === 1 ? 'Isso desfaz 1 alteração:' : 'Isso desfaz ' . $n . ' alterações, inclusive as feitas depois:') . "\n"
        . implode("\n", $linhas) . "\n"
        . $partes . ' O estado atual vira uma cópia, então dá para desfazer.';
}

/* ------------------------------------------------------------------
   O que mudou, em palavras simples (vai para o registro, a tela
   Atividades e a confirmação de restaurar uma cópia)
   ------------------------------------------------------------------ */
/** "a", "a e b", "a, b e c". */
function juntarFrases(array $partes): string
{
    $partes = array_values(array_filter($partes, static fn($p): bool => $p !== ''));
    $ultimo = array_pop($partes);
    return $partes ? implode(', ', $partes) . ' e ' . $ultimo : (string) $ultimo;
}

/** [acrescentados, tirados] entre duas listas de textos (sem diferenciar maiúsculas e acentos). */
function diferencaListas(array $antes, array $depois): array
{
    $chave = static fn(string $s): string => semAcento($s);
    $a = []; foreach ($antes as $x) $a[$chave((string) $x)] = (string) $x;
    $d = []; foreach ($depois as $x) $d[$chave((string) $x)] = (string) $x;
    return [array_values(array_diff_key($d, $a)), array_values(array_diff_key($a, $d))];
}

/** "Rua 13, 650 – Boa Morte, Rio Claro/SP". */
function enderecoCurto(array $e): string
{
    return ($e['rua'] ?? '') . ' – ' . ($e['bairro'] ?? '') . ', ' . ($e['cidade'] ?? '') . '/' . ($e['uf'] ?? '');
}

/** " (de 20/12/2026 a 05/01/2027)", " (a partir de …)", " (até …)" ou ''. */
function periodoAviso(array $a): string
{
    $ini = (string) ($a['inicio'] ?? '');
    $fim = (string) ($a['fim'] ?? '');
    if ($ini !== '' && $fim !== '') return ' (de ' . dataBr($ini) . ' a ' . dataBr($fim) . ')';
    if ($ini !== '') return ' (a partir de ' . dataBr($ini) . ')';
    if ($fim !== '') return ' (até ' . dataBr($fim) . ')';
    return '';
}

/** Equipe e depoimentos: o que entrou, saiu, mudou ou só trocou de lugar. */
function mudancasLista(string $secao, array $antes, array $depois): string
{
    $rotulo = static fn($x): string => $secao === 'equipe' ? (string) ($x['nome'] ?? '')
        : 'o de ' . ($x['nome'] ?? '') . (($x['empresa'] ?? '') !== '' ? ' (' . $x['empresa'] . ')' : '');
    $porChave = static function (array $l) use ($rotulo): array {
        $m = [];
        foreach ($l as $x) if (is_array($x)) $m[semAcento($rotulo($x))] = $x;
        return $m;
    };
    $a = $porChave($antes);
    $d = $porChave($depois);
    $partes = [];
    $novos = array_map($rotulo, array_values(array_diff_key($d, $a)));
    $saidos = array_map($rotulo, array_values(array_diff_key($a, $d)));
    $mudados = [];
    foreach (array_intersect_key($d, $a) as $k => $x) if ($x != $a[$k]) $mudados[] = $rotulo($x);
    if ($novos) $partes[] = 'acrescentou ' . juntarFrases($novos);
    if ($saidos) $partes[] = 'tirou ' . juntarFrases($saidos);
    if ($mudados) $partes[] = ($secao === 'equipe' ? 'mudou os dados de ' : 'mudou o texto ') . juntarFrases($mudados);
    if (!$partes && array_keys($a) !== array_keys($d)) $partes[] = 'mudou a ordem';
    return $partes ? juntarFrases($partes) : '';
}

/**
 * Frase do registro para uma parte salva da área Empresa, comparando o que o site mostrava ($antes,
 * de valoresAtuais()) com o que foi gravado ($depois, de formularioSecao()). Ex.: "Mudou o telefone
 * para (19) 3023-9050 e o WhatsApp para (19) 99999-1234". Sem diferença reconhecida: "Alterou “<parte>”".
 */
function descreverMudancaAjustes(string $secao, array $antes, array $depois, int $saiu = 0): string
{
    $frase = '';
    switch ($secao) {
        case 'contato':
            $a = $antes['contato'];
            $d = $depois['contato'];
            $p = [];
            if ($a['telefone'] !== $d['telefone']) $p[] = 'o telefone para ' . $d['telefone'];
            if ($a['whatsapp'] !== $d['whatsapp']) $p[] = 'o WhatsApp para ' . formatarTelefone($d['whatsapp']);
            if ($a['whatsappMensagemPadrao'] !== $d['whatsappMensagemPadrao']) $p[] = 'a mensagem pronta do WhatsApp';
            if ($a['email'] !== $d['email']) $p[] = 'o e-mail para ' . $d['email'];
            if ($p) $frase = 'Mudou ' . juntarFrases($p);
            break;
        case 'endereco':
            $a = $antes['endereco'];
            $d = $depois['endereco'];
            $p = [];
            foreach (['rua', 'bairro', 'cidade', 'uf', 'cep'] as $k) {
                if (($a[$k] ?? '') !== ($d[$k] ?? '')) { $p[] = 'o endereço para ' . enderecoCurto($d) . ' (CEP ' . $d['cep'] . ')'; break; }
            }
            $tirou = ($a['referencia'] ?? '') !== '' && $d['referencia'] === '';
            if (!$tirou && ($a['referencia'] ?? '') !== $d['referencia']) $p[] = 'a referência para “' . $d['referencia'] . '”';
            if ($antes['mapa'] !== $depois['mapa']) $p[] = 'o link do mapa';
            $frase = ucfirst(juntarFrases([$p ? 'mudou ' . juntarFrases($p) : '', $tirou ? 'tirou a referência' : '']));
            break;
        case 'horario':
            $linhas = array_map(static fn(array $l): string => $l['dias'] . ' ' . $l['horas'], $depois['horario']['linhas']);
            $frase = 'Mudou o horário de funcionamento para: ' . implode('; ', $linhas);
            break;
        case 'feriados':
            $a = $antes['feriados'];
            $d = $depois['feriados'];
            $nomeDia = static fn(array $x, bool $ano): string => ($ano ? dataBr((string) $x['data']) : diaMesTela((string) $x['data'])) . ' (' . $x['nome'] . ')';
            $mapa = static function (array $l, bool $ano) use ($nomeDia): array {
                $m = [];
                foreach ($l as $x) if (is_array($x) && is_string($x['data'] ?? null)) $m[$x['data']] = $nomeDia($x + ['nome' => ''], $ano);
                return $m;
            };
            $mov = static fn(array $l): array => array_combine(array_map('strval', $l), array_map(static fn($n): string => FERIADOS_MOVEIS[$n] ?? (string) $n, $l)) ?: [];
            $antesM = $mapa($a['municipais'], false) + $mov(array_values(array_filter($a['moveis'], 'is_int'))) + $mapa($a['datas'], true);
            $depoisM = $mapa($d['municipais'], false) + $mov($d['moveis']) + $mapa($d['datas'], true);
            $p = [];
            $novos = array_values(array_diff_key($depoisM, $antesM));
            $saidos = array_values(array_diff_key($antesM, $depoisM));
            $renomeados = [];
            foreach ($depoisM as $k => $v) if (isset($antesM[$k]) && $antesM[$k] !== $v) $renomeados[] = $v;
            if ($novos) $p[] = 'acrescentou ' . juntarFrases($novos);
            if ($saidos) $p[] = 'tirou ' . juntarFrases($saidos);
            if ($renomeados) $p[] = 'mudou o nome de ' . juntarFrases($renomeados);
            if ($saiu) $p[] = ($saiu === 1 ? '1 dia fechado que já passou saiu' : $saiu . ' dias fechados que já passaram saíram') . ' da lista';
            if ($p) $frase = 'Feriados e dias fechados: ' . juntarFrases($p);
            break;
        case 'aviso':
            $a = $antes['aviso'];
            $d = $depois['aviso'];
            $txt = '“' . $d['texto'] . '”' . periodoAviso($d) . ($d['tom'] === 'alerta' ? ', em amarelo' : '');
            if (!$d['ativo']) $frase = $a['ativo'] ? 'Desligou o aviso no site' : 'Mudou o aviso no site (continua desligado)';
            else $frase = ($a['ativo'] ? 'Mudou o aviso no site para ' : 'Ligou o aviso no site: ') . $txt;
            break;
        case 'google':
            $a = $antes['google'];
            $d = $depois['google'];
            $p = [];
            if ($a['nota'] !== $d['nota'] || $a['avaliacoes'] !== $d['avaliacoes']) $p[] = 'a nota do Google para ' . $d['nota'] . ', com ' . $d['avaliacoes'] . ($d['avaliacoes'] === 1 ? ' avaliação' : ' avaliações');
            if ($a['link'] !== $d['link']) $p[] = 'o link das avaliações';
            $mud = $p ? ['Mudou ' . juntarFrases($p)] : [];
            foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram'] as $k => $rot) {
                $de = (string) ($antes['redes'][$k] ?? '');
                $para = (string) ($depois['redes'][$k] ?? '');
                if ($de === $para) continue;
                $mud[] = $para === '' ? 'tirou o ' . $rot . ' do site' : ($de === '' ? 'pôs o ' . $rot . ' no site' : 'mudou o link do ' . $rot);
            }
            if ($mud) $frase = ucfirst(juntarFrases($mud));
            break;
        case 'equipe':
        case 'depoimentos':
            $m = mudancasLista($secao, $antes[$secao], $depois[$secao]);
            if ($m !== '') $frase = ($secao === 'equipe' ? 'Equipe: ' : 'Depoimentos: ') . $m;
            break;
        case 'marcas':
            [$novas, $saidas] = diferencaListas($antes['marcas'], $depois['marcas']);
            $p = [];
            if ($novas) $p[] = 'acrescentou ' . juntarFrases($novas);
            if ($saidas) $p[] = 'tirou ' . juntarFrases($saidas);
            if (!$p && $antes['marcas'] !== $depois['marcas']) $p[] = 'mudou a ordem';
            if ($p) $frase = 'Marcas: ' . juntarFrases($p);
            break;
        case 'capa':
            $frase = mudancaCapa($antes['capa'] ?? null, $depois['capa'] ?? null);
            break;
    }
    if ($frase === '') return 'Alterou “' . SECOES_AJUSTES[$secao] . '”';
    return tamanho($frase) > 290 ? cortar($frase, 289) . '…' : $frase;
}

/* ------------------------------------------------------------------
   Ações (chamadas por index.php: a=empresa, a=empresa-salvar e,
   para as cópias, a=restaurar com uma cópia ajustes-*.php)
   ------------------------------------------------------------------ */
/** Tudo o que a tela precisa: ajustes, valores originais e o que o site mostra hoje. */
function contextoEmpresa(array $dados): array
{
    $padrao = lerPadroesEmpresa();
    return [
        'ajustes' => $dados['ajustes'],
        'existe' => $dados['existe'],
        'erroPadrao' => $padrao['erro'],
        'atual' => valoresAtuais($dados['ajustes'], $padrao['dados']),
    ];
}

function acaoEmpresa(): void
{
    try {
        $dados = lerAjustes();
    } catch (ErroAjustes $e) {
        telaAjustesIlegivel($e->getMessage());
        return;
    }
    telaEmpresa(contextoEmpresa($dados));
}

/** Onde cada parte aparece no site: [texto curto, página#âncora]. Também na mensagem de salvo. */
function linkSiteAjustes(string $secao): array
{
    $pag = match ($secao) {
        'equipe' => 'empresa.html#equipe', 'depoimentos', 'google' => 'empresa.html#clientes', 'marcas', 'aviso', 'capa' => '',
        default => 'contato.html#canais',
    };
    return ['link' => cfg('url_site') . $pag, 'textoLink' => 'Ver no site', 'externo' => true];
}

/** Frase de "salvo", parte por parte. */
function textoSalvoAjustes(string $secao, array $valor, int $saiu = 0): string
{
    $t = '“' . SECOES_AJUSTES[$secao] . '” foi salvo. ';
    switch ($secao) {
        case 'contato': return $t . 'O site já mostra o telefone, o WhatsApp e o e-mail novos.';
        case 'endereco': return $t . 'O site já mostra o endereço e o link do mapa novos.';
        case 'horario': return $t . 'O site já mostra o horário novo e usa ele no “Aberto agora”.';
        case 'feriados': return $t . 'O site já conta a loja como fechada nesses dias (no “Aberto agora”).'
            . ($saiu ? ' ' . ($saiu === 1 ? '1 dia fechado que já passou saiu da lista.' : $saiu . ' dias fechados que já passaram saíram da lista.') : '');
        case 'aviso':
            $a = $valor['aviso'];
            if (!$a['ativo']) return $t . 'O aviso está desligado e não aparece no site.';
            if (isset($a['inicio']) && $a['inicio'] > hojeIso()) return $t . 'O aviso aparece no topo do site a partir de ' . dataBr($a['inicio']) . (isset($a['fim']) ? ', até ' . dataBr($a['fim']) : '') . '.';
            return $t . 'O site já mostra o aviso no topo de todas as páginas' . (isset($a['fim']) ? ', até ' . dataBr($a['fim']) : '') . '.';
        case 'google': return $t . 'O site já mostra a nota, as avaliações e os links novos.';
        case 'equipe': return $t . 'O site já mostra a equipe nova, na página Empresa.';
        case 'depoimentos': return $t . 'O site já mostra os depoimentos novos.';
        case 'marcas': return $t . 'O site já mostra a faixa de marcas nova.';
        case 'capa':
            $c = $valor['capa'] ?? null;
            if ($c === null) {
                $orig = motivoOriginalForaDoSite(infoCapa()['lista']);
                return '“' . SECOES_AJUSTES[$secao] . '” foi salva. A página inicial voltou a mostrar a ' . NOME_CAPA_ORIGINAL . ' (a capa original).'
                    . ($orig !== '' ? ' Atenção: ela ' . $orig . ', então o link da capa abre um produto fora do catálogo. Escolha outro produto aqui.' : '');
            }
            return '“' . SECOES_AJUSTES[$secao] . '” foi salva. A página inicial já mostra ' . nomeProdutoCapa($c['produto']) . ' no topo, com as etiquetas novas.';
    }
    return $t;
}

function acaoEmpresaSalvar(): void
{
    if (!ehAdmin()) throw new ErroUsuario('Esta área é só para administradores do painel.');   // o index.php já confere; aqui, por garantia
    $secao = is_string($_GET['secao'] ?? null) ? $_GET['secao'] : '';
    if ($secao === 'recomecar') { acaoRecomecarAjustes(); return; }
    if (!isset(SECOES_AJUSTES[$secao])) throw new ErroUsuario('Essa parte da tela não existe. Recarregue a página e tente de novo.');
    try {
        $dados = lerAjustes();
    } catch (ErroAjustes $e) {
        telaAjustesIlegivel($e->getMessage());
        return;
    }
    $ctx = contextoEmpresa($dados);
    // "Voltar para a Prix 7E" sem JavaScript: confirma numa tela própria (com JavaScript, numa janela)
    if ($secao === 'capa' && ($_POST['original'] ?? '') === '1' && ($_POST['confirmado'] ?? '') !== '1' && $ctx['atual']['capa'] !== null) {
        telaConfirmar('Voltar para a capa original?', textoVoltarCapaOriginal($ctx['atual']['capa'], infoCapa()['lista']), link_('empresa-salvar', ['secao' => 'capa']),
            ['original' => '1'], 'Sim, voltar para a ' . NOME_CAPA_ORIGINAL, link_('empresa') . '#e-capa', 'empresa');
        return;
    }
    $f = formularioSecao($secao, $_POST, $ctx['atual']);
    if ($f['nota'] !== '') { telaEmpresa($ctx, [$secao => $f]); return; }   // ajudante sem JavaScript: mostra, não grava
    if ($f['erros']) {
        http_response_code(422);
        telaEmpresa($ctx, [$secao => $f]);
        return;
    }
    $voltar = link_('empresa') . '#e-' . $secao;
    // Igual ao que o site já mostra: não grava (nem "prende" um valor que ainda vem do empresa.js)
    $igual = true;
    foreach ($f['valor'] as $k => $v) if (($ctx['atual'][$k] ?? null) !== $v) $igual = false;
    if ($igual && !($f['saiu'] ?? 0)) {
        avisar('Nada mudou em “' . SECOES_AJUSTES[$secao] . '”: o site já mostra isso.', 'info');
        redirecionar($voltar);
    }
    $titulo = SECOES_AJUSTES[$secao];
    $feito = descreverMudancaAjustes($secao, $ctx['atual'], $f['valor'], (int) ($f['saiu'] ?? 0));
    alterarAjustes(['tipo' => $secao, 'secao' => $secao, 'antes' => 'Antes de: alterar “' . $titulo . '”', 'feito' => $feito],
        static function (array $aj) use ($f): array {
            foreach ($f['valor'] as $k => $v) {
                if ($v === null) unset($aj[$k]);   // ex.: "capa" → volta a valer o index.html
                else $aj[$k] = $v;
            }
            return ['versao' => 1] + $aj;
        });
    avisar(textoSalvoAjustes($secao, $f['valor'], (int) ($f['saiu'] ?? 0)), 'ok', linkSiteAjustes($secao));
    redirecionar($voltar);
}

/** ajustes.js ilegível e sem cópia que sirva: guarda o arquivo na pasta privada e recomeça do zero (só com confirmação). */
function acaoRecomecarAjustes(): void
{
    if (($_POST['confirmado'] ?? '') !== '1') {
        telaConfirmar('Recomeçar do zero?', textoRecomecarAjustes(), link_('empresa-salvar', ['secao' => 'recomecar']), [], 'Sim, recomeçar', link_('empresa'), 'empresa');
        return;
    }
    comTrava('ajustes', static function (): void {
        try {
            lerAjustes();
            throw new ErroUsuario('O arquivo dos ajustes está bom: não é preciso recomeçar.');
        } catch (ErroAjustes $e) {
            $bruto = @file_get_contents(arquivoAjustes());
            if ($bruto !== false) gravarPrivado('copias/ilegivel-ajustes-' . date('Ymd-His') . '.php', base64_encode($bruto));
            gravarAjustes([]);
            registrar('ajustes', 'Voltou os dados da empresa para os originais do site (o arquivo de ajustes estava com problema)');
        }
    });
    avisar('Pronto: os ajustes recomeçaram do zero e o site voltou a mostrar os dados originais. O arquivo com problema foi guardado na pasta privada do painel.', 'ok');
    redirecionar(link_('empresa'));
}

function textoRecomecarAjustes(): string
{
    return 'O site volta a mostrar os dados originais (de empresa.js) em todas as partes: contato, endereço, horário, feriados, aviso, Google, equipe, depoimentos e marcas; e a capa volta a ser a ' . NOME_CAPA_ORIGINAL . '. '
        . 'O arquivo com problema fica guardado na pasta privada do painel. Depois, é só ajustar de novo o que precisar.';
}

function acaoRestaurarAjustes(bool $post, string $nome): void
{
    $copia = lerCopiaAjustes($nome);
    $quando = quando((int) ($copia['criado'] ?? 0));
    if (!$post || ($_POST['confirmado'] ?? '') !== '1') {
        $lista = listarCopiasAjustes();
        $i = array_search($nome, array_column($lista, 'nome'), true);
        telaConfirmar('Restaurar esta cópia?', $i === false ? 'Voltar os dados da empresa para como estavam em ' . $quando . '? O estado atual vira uma cópia, então dá para desfazer.' : textoRestaurarAjustes($lista, (int) $i),
            link_('restaurar'), ['copia' => $nome], 'Sim, restaurar', link_('copias', ['de' => 'empresa']), 'copias');
        return;
    }
    $acao = ['tipo' => 'restaurar', 'antes' => 'Antes de: restaurar a cópia dos dados da empresa de ' . $quando, 'feito' => 'Restaurou a cópia dos dados da empresa de ' . $quando];
    try {
        $r = alterarAjustes($acao, static fn(array $aj): array => $copia['ajustes']);
    } catch (ErroAjustes $e) {
        // O arquivo atual está ilegível: guarda o que havia e grava a cópia por cima
        comTrava('ajustes', static function () use ($copia, $acao): void {
            $bruto = @file_get_contents(arquivoAjustes());
            if ($bruto !== false) gravarPrivado('copias/ilegivel-ajustes-' . date('Ymd-His') . '.php', base64_encode($bruto));
            gravarAjustes($copia['ajustes']);
            registrar('ajustes', $acao['feito'] . ' (o arquivo atual estava ilegível)');
        });
        $r = ['mudou' => true];
    }
    avisar($r['mudou'] ? 'Pronto: os dados da empresa voltaram para como estavam em ' . $quando . '.' : 'Os dados da empresa já estavam iguais a essa cópia: nada mudou.',
        $r['mudou'] ? 'ok' : 'info', ['link' => cfg('url_site'), 'textoLink' => 'Ver no site', 'externo' => true]);
    redirecionar(link_('empresa'));
}

/** Aba "Empresa" das cópias de segurança. */
function acaoCopiasAjustes(): void
{
    telaCopiasAjustes(listarCopiasAjustes());
}
