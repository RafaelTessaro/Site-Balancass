<?php
/* =====================================================================
   Painel Balanças.com — ajustes do site (área Empresa)
   Lê e grava compartilhado/dados/ajustes.js (window.AJUSTES = {JSON};),
   que guarda SÓ o que foi mudado pelo painel: contato, endereço e mapa,
   horário, feriados, aviso no site, Google e redes, equipe, depoimentos
   e marcas. Chave ausente = o site usa compartilhado/dados/empresa.js
   (os valores originais, que vêm com as atualizações do site; o
   ajustes.js nunca vem nelas, então o que o painel gravou continua).
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
];
const CHAVES_DA_SECAO = [
    'contato' => ['contato'], 'endereco' => ['endereco', 'mapa'], 'horario' => ['horario'], 'feriados' => ['feriados'],
    'aviso' => ['aviso'], 'google' => ['google', 'redes'], 'equipe' => ['equipe'], 'depoimentos' => ['depoimentos'], 'marcas' => ['marcas'],
];
// Ordem das chaves no arquivo (chaves desconhecidas são mantidas, depois destas)
const ORDEM_AJUSTES = ['versao', 'contato', 'endereco', 'mapa', 'horario', 'feriados', 'aviso', 'google', 'redes', 'equipe', 'depoimentos', 'marcas'];
// Cópias de segurança dos ajustes (mesmos grupos de RE_COPIA: data, hora, tipo)
const RE_COPIA_AJUSTES = '/^ajustes-(\d{8})-(\d{6})-\d{3}-[0-9a-f]{4}-([a-z]{3,12})\.php$/';
const LIMITES_AJUSTES = [
    'mensagem' => 200, 'email' => 120, 'rua' => 80, 'referencia' => 80, 'bairro' => 60, 'cidade' => 60, 'url' => 300,
    'aviso_min' => 5, 'aviso' => 160, 'feriado_nome' => 60, 'municipais' => 12, 'datas' => 30, 'intervalos' => 3,
    'equipe' => 12, 'equipe_nome' => 60, 'equipe_cargo' => 60, 'equipe_texto' => 220,
    'depoimentos' => 12, 'dep_texto' => 300, 'dep_nome' => 60, 'dep_empresa' => 60,
    'marcas' => 40, 'marca' => 30,
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
        '   depoimentos e marcas. Para mudar, use o painel: ele confere os',
        '   dados e guarda cópias de segurança. O que for editado à mão aqui',
        '   some na próxima vez que o painel salvar.',
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
        'equipe' => 'empresa.html#equipe', 'depoimentos', 'google' => 'empresa.html#clientes', 'marcas', 'aviso' => '',
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
            foreach ($f['valor'] as $k => $v) $aj[$k] = $v;
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
    return 'O site volta a mostrar os dados originais (de empresa.js) em todas as partes: contato, endereço, horário, feriados, aviso, Google, equipe, depoimentos e marcas. '
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
