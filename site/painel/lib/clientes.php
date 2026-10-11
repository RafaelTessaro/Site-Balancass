<?php
/* =====================================================================
   Painel Balanças.com — clientes (logos que aparecem no site)
   Lê e grava compartilhado/dados/clientes.js (window.CLIENTES = [JSON];),
   no mesmo estilo do produtos.js. Cada cliente: {"nome": "…", "logo": "…"}
   (logo = arquivo em compartilhado/img/clientes/); a ordem da lista é a
   ordem no site. O site usa essa lista no lugar de EMPRESA.clientes.
   Logos: PNG, JPG ou WebP (SVG não), até 5 MB, conferidos (finfo +
   getimagesize) e REDESENHADOS com GD, mantendo a transparência; as
   sobras vazias são cortadas e o logo é salvo em WebP (até 600 × 300 px)
   com nome aleatório. Cópias de segurança próprias (copias/clientes-*.php)
   e a mesma lixeira privada das fotos (lixeira/clientes--*.php).
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

final class ErroClientes extends RuntimeException {}   // clientes.js existe mas não está no formato do painel

const LIMITE_NOME_CLIENTE = 80;
const MAX_CLIENTES = 200;
// Logo de um cliente: só o nome do arquivo (nada de pastas). SVG só para listas escritas à mão: o envio pelo painel não aceita.
const RE_ARQUIVO_LOGO = '/^[A-Za-z0-9][A-Za-z0-9._-]{0,120}\.(?:webp|png|jpe?g|svg)$/';
// Logos criados pelo painel: <nome>-<10 hex>.webp (ou .png, se o servidor não gravar WebP).
// Só esses vão para a lixeira ao sair da lista: os logos que vieram com o site ficam (as páginas usam direto).
const RE_LOGO_DO_PAINEL = '/^[a-z0-9-]{1,40}-[0-9a-f]{10}\.(?:webp|png)$/';
// Cópias de segurança da lista de clientes (mesmos grupos de RE_COPIA: data, hora, tipo)
const RE_COPIA_CLIENTES = '/^clientes-(\d{8})-(\d{6})-\d{3}-[0-9a-f]{4}-([a-z]{3,12})\.php$/';

function arquivoClientes(): string
{
    return cfg('compartilhado') . '/dados/clientes.js';
}

/* ------------------------------------------------------------------
   Formato do arquivo
   ------------------------------------------------------------------ */
function cabecalhoClientes(string $linhaData): string
{
    return implode("\n", [
        '/* =====================================================================',
        '   CLIENTES — Balanças.com (logos da página Empresa e da página inicial)',
        '   ---------------------------------------------------------------------',
        '   Arquivo GERADO pelo painel de administração do site (área Clientes).',
        '   Para adicionar, trocar, reordenar ou excluir logos, use o painel: ele',
        '   confere os dados, trata as imagens, guarda cópias de segurança e não',
        '   deixa a lista quebrar. O que for editado à mão aqui some na próxima',
        '   vez que o painel salvar.',
        '',
        '   Formato: JSON puro dentro de window.CLIENTES. Cada item:',
        '   {"nome": "Nome do cliente", "logo": "arquivo.webp"} — o logo fica em',
        '   compartilhado/img/clientes/. A ordem da lista é a ordem no site.',
        '   Sem este arquivo, o site usa a lista "clientes" de empresa.js.',
        '   ' . str_replace('*/', '* /', $linhaData),
        '   ===================================================================== */',
        '',
    ]);
}

function montarClientes(array $clientes): string
{
    return cabecalhoClientes('Última gravação pelo painel: ' . date('d/m/Y') . ' ' . date('H:i') . '.')
        . 'window.CLIENTES = ' . json_encode(array_values($clientes), JSON_GRAVAR | JSON_THROW_ON_ERROR) . ";\n";
}

/** Lê window.CLIENTES sem executar nada. Lança ErroClientes com explicação. */
function interpretarClientes(string $src): array
{
    $src = preg_replace('/^\xEF\xBB\xBF/', '', $src) ?? $src;
    try {
        $lista = jsonDepois($src, 'CLIENTES');
    } catch (ErroCatalogo $e) {
        throw new ErroClientes($e->getMessage());
    }
    if (!is_array($lista) || !array_is_list($lista)) throw new ErroClientes('window.CLIENTES precisa ser uma lista [ … ]');
    foreach ($lista as $i => $c) {
        $n = $i + 1;
        if (!is_array($c) || !is_string($c['nome'] ?? null)) throw new ErroClientes('o item ' . $n . ' não tem "nome"');
        if (array_key_exists('logo', $c) && !is_string($c['logo'])) throw new ErroClientes('o "logo" do item ' . $n . ' não é texto');
        $logo = (string) ($c['logo'] ?? '');
        if ($logo !== '' && !preg_match(RE_ARQUIVO_LOGO, $logo)) throw new ErroClientes('o logo do item ' . $n . ' tem um nome de arquivo inválido');
    }
    return $lista;
}

/** Lista atual. Sem o arquivo no servidor: lista vazia e 'existe' => false (a tela mostra um aviso). */
function lerClientes(): array
{
    $arq = arquivoClientes();
    if (!@is_file($arq)) return ['clientes' => [], 'existe' => false];
    $src = @file_get_contents($arq);
    if ($src === false) throw new ErroClientes('sem permissão para ler compartilhado/dados/clientes.js');
    return ['clientes' => interpretarClientes($src), 'existe' => true];
}

function gravarClientes(array $clientes): void
{
    $clientes = array_values($clientes);
    $texto = montarClientes($clientes);
    // Confere o que vai ser gravado antes de substituir o arquivo do site
    if (interpretarClientes($texto) !== $clientes) throw new RuntimeException('conferência da lista de clientes falhou');
    if (!@is_writable(arquivoClientes()) && !@is_writable(dirname(arquivoClientes()))) {
        throw new ErroUsuario('O servidor não deixa gravar compartilhado/dados/clientes.js. Confira as permissões no Gerenciador de Arquivos (arquivo 644, pasta dados 755).');
    }
    gravarAtomico(arquivoClientes(), $texto, 0644);
}

/**
 * Altera a lista com segurança: trava → lê → $fn(lista) devolve a lista nova → cópia de
 * segurança do estado anterior → grava (temporário + rename) → acerta a lixeira de logos.
 * $acao = ['tipo' => 'excluir'…, 'antes' => 'Antes de: …', 'feito' => 'Excluiu …', 'alvo' => logo, 'alvoNome' => nome]
 * Devolve ['clientes' => lista nova, 'copia' => nome da cópia].
 */
function alterarClientes(array $acao, callable $fn): array
{
    return comTrava('clientes', static function () use ($acao, $fn): array {
        $antes = lerClientes();
        $novos = array_values($fn($antes['clientes']));
        if (count($novos) > MAX_CLIENTES) throw new ErroUsuario('A lista chegou a ' . MAX_CLIENTES . ' clientes, o máximo do painel. Exclua algum antes de cadastrar outro.');
        // Confere antes de mexer em qualquer coisa (a gravação confere de novo)
        if (interpretarClientes(montarClientes($novos)) !== $novos) throw new RuntimeException('conferência da lista de clientes falhou');
        $copia = fazerCopiaClientes($antes, $acao);
        gravarClientes($novos);
        protegerPastasFotos();
        sincronizarLogos($antes['clientes'], $novos);
        registrar('clientes', (string) ($acao['feito'] ?? $acao['antes'] ?? ''));
        return ['clientes' => $novos, 'copia' => $copia];
    });
}

/* ------------------------------------------------------------------
   Busca e validação
   ------------------------------------------------------------------ */
/**
 * Posição do cliente na lista. O formulário manda a posição (n, começa em 1) e o logo:
 * vale a posição se o logo bater; senão procura pelo logo (a lista pode ter mudado em
 * outro aparelho). Devolve null se não achar.
 */
function acharCliente(array $lista, int $i, ?string $logo): ?int
{
    if ($logo === null || ($logo !== '' && !preg_match(RE_ARQUIVO_LOGO, $logo))) return null;
    if (isset($lista[$i]) && (string) ($lista[$i]['logo'] ?? '') === $logo) return $i;
    if ($logo === '') return null;
    foreach ($lista as $j => $c) if ((string) ($c['logo'] ?? '') === $logo) return $j;
    return null;
}

/** [posição (0…), logo] vindos do formulário/endereço (n começa em 1). */
function refCliente(array $origem): array
{
    $n = is_string($origem['n'] ?? null) && preg_match('/^\d{1,4}$/', $origem['n']) ? (int) $origem['n'] - 1 : -1;
    $logo = is_string($origem['logo'] ?? null) ? $origem['logo'] : null;
    return [$n, $logo];
}

/** Nome do cliente: [nome limpo, mensagem de erro ou null]. */
function nomeClienteValido($v): array
{
    $n = limparLinha($v);
    if ($n === null) return ['', 'Nome: há caracteres inválidos. Apague e digite de novo.'];
    if ($n === '') return [$n, 'Nome: escreva o nome do cliente (ex.: Supermercado Bom Preço).'];
    if (tamanho($n) > LIMITE_NOME_CLIENTE) return [$n, 'Nome: no máximo ' . LIMITE_NOME_CLIENTE . ' caracteres (tem ' . tamanho($n) . ').'];
    if (preg_match('/[<>]/', $n)) return [$n, 'Nome: não use os sinais < e >. Escreva só o nome do cliente.'];
    return [$n, null];
}

function nomeRepetido(array $lista, string $nome, ?int $ignorar): bool
{
    $chave = trim(preg_replace('/\s+/', ' ', semAcento($nome)) ?? '');
    foreach ($lista as $i => $c) {
        if ($i === $ignorar) continue;
        if (trim(preg_replace('/\s+/', ' ', semAcento((string) ($c['nome'] ?? ''))) ?? '') === $chave) return true;
    }
    return false;
}

/** Endereço (para o navegador) e tamanho do logo, se o arquivo existir. */
function infoLogo(string $logo): ?array
{
    if ($logo === '' || !preg_match(RE_ARQUIVO_LOGO, $logo)) return null;
    $arq = pastaFotos('clientes') . '/' . $logo;
    if (!@is_file($arq)) return null;
    $tam = str_ends_with(strtolower($logo), '.svg') ? false : @getimagesize($arq);
    $w = is_array($tam) ? (int) $tam[0] : 0;
    $h = is_array($tam) ? (int) $tam[1] : 0;
    return ['url' => urlFoto($logo, 'clientes') . '?v=' . (int) @filemtime($arq), 'w' => $w, 'h' => $h,
        // "Chapa quadrada" no site: logo alto deixa a chapa com mais altura útil
        'sq' => $w > 0 && $h > 0 && $w / $h < 1.35];   // mesma regra de js/core.js (clients__i--sq)
}

/* ------------------------------------------------------------------
   Logo enviado: confere, redesenha (mantendo a transparência), corta
   as sobras vazias, reduz para até 600 × 300 e salva em WebP
   ------------------------------------------------------------------ */
function processarLogo($arq, string $nomeCliente): array
{
    if (!is_array($arq) || !isset($arq['error'], $arq['tmp_name']) || is_array($arq['error'])) throw new ErroFoto('Envio do logo inválido.');
    switch ((int) $arq['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE: throw new ErroFoto('O logo é maior do que o servidor aceita (' . mb_(min(limiteEnvio(), (int) (cfg('logo_max_bytes') ?? 5242880))) . '). Use um arquivo menor.');
        case UPLOAD_ERR_PARTIAL: throw new ErroFoto('O envio do logo foi interrompido. Tente de novo.');
        case UPLOAD_ERR_NO_FILE: throw new ErroFoto('Escolha o arquivo do logo.');
        default: throw new ErroFoto('O servidor não conseguiu receber o logo (erro ' . (int) $arq['error'] . '). Tente de novo.');
    }
    $tmp = (string) $arq['tmp_name'];
    if (!is_uploaded_file($tmp)) throw new ErroFoto('Envio do logo inválido.');
    return converterLogo($tmp, $nomeCliente);
}

function converterLogo(string $tmp, string $nomeCliente): array
{
    $max = (int) (cfg('logo_max_bytes') ?? 5242880);
    $tam = (int) @filesize($tmp);
    if ($tam <= 0) throw new ErroFoto('O arquivo do logo chegou vazio.');
    if ($tam > $max) throw new ErroFoto('O logo tem ' . mb_($tam) . '; o máximo é ' . mb_($max) . '. Use um arquivo menor (logo não precisa ser grande).');

    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (in_array($mime, ['image/svg+xml', 'image/svg'], true) || preg_match('/^\s*(<\?xml|<svg)/i', (string) @file_get_contents($tmp, false, null, 0, 512))) {
        throw new ErroFoto('Logo em SVG não é aceito (por segurança). Peça ao cliente o logo em PNG, de preferência com fundo transparente, ou abra o SVG no navegador, tire um print e use o print.');
    }
    if (in_array($mime, ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'], true)) {
        throw new ErroFoto('Esse arquivo está em HEIC, formato que o site não usa. Salve o logo como PNG ou JPG e escolha de novo.');
    }
    if ($mime === 'application/pdf') throw new ErroFoto('Esse arquivo é um PDF. Salve o logo como PNG (ou tire um print dele) e escolha de novo.');
    if (!isset(TIPOS_FOTO[$mime])) throw new ErroFoto('Esse arquivo não é uma imagem PNG, JPG ou WebP. Use o logo salvo do site do cliente ou enviado por ele.');
    $info = @getimagesize($tmp);
    if (!is_array($info) || ($info[2] ?? null) !== TIPOS_FOTO[$mime] || ($info['mime'] ?? '') !== $mime) {
        throw new ErroFoto('Esse arquivo não é uma imagem válida (ou está danificado).');
    }
    [$w, $h] = $info;
    $min = max(1, (int) (cfg('logo_min_largura') ?? 80));
    if ($w < $min || $h < 20) throw new ErroFoto('O logo é pequeno demais (' . $w . ' × ' . $h . ' px). Use um com pelo menos ' . $min . ' px de largura (o ideal é 300 px ou mais).');
    $maxPx = max(1000000, (int) (cfg('foto_max_pixels') ?? 20000000));
    if ($w > 8000 || $h > 8000 || $w * $h > $maxPx) {
        throw new ErroFoto('O logo tem resolução alta demais (' . $w . ' × ' . $h . ' px). Reduza para até 2000 px de largura e escolha de novo.');
    }

    garantirMemoria($w * $h);
    $img = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($tmp),
        'image/png'  => @imagecreatefrompng($tmp),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
    };
    if (!$img) throw new ErroFoto('Não consegui abrir o logo (arquivo danificado, animado ou formato não suportado). Salve de novo como PNG e tente outra vez.');
    if (!imageistruecolor($img)) imagepalettetotruecolor($img);
    imagealphablending($img, false);
    imagesavealpha($img, true);
    if ($mime === 'image/jpeg') $img = aplicarOrientacao($img, orientacaoExif($tmp));

    // Sobras: transparentes (logo com fundo transparente) ou brancas (logo com fundo branco)
    try {
        $analise = analisarFoto($img);
    } catch (ErroFoto $e) {
        throw new ErroFoto('O logo parece estar vazio (todo transparente). Confira o arquivo e escolha de novo.');
    }
    $sw = imagesx($img); $sh = imagesy($img);
    [$x0, $y0, $x1, $y1] = $analise['caixa'] ?? [0, 0, $sw, $sh];
    $cw = $x1 - $x0; $ch = $y1 - $y0;
    if ($cw < $min * 0.75 || $ch < 12) {
        throw new ErroFoto('Sem as margens vazias, o logo fica com só ' . $cw . ' × ' . $ch . ' px. Use um logo maior (pelo menos ' . $min . ' px de largura de desenho).');
    }
    $f = min(1, (int) (cfg('logo_max_largura') ?? 600) / $cw, (int) (cfg('logo_max_altura') ?? 300) / $ch);
    $ow = max(1, (int) round($cw * $f)); $oh = max(1, (int) round($ch * $f));

    // Redesenha numa tela nova, transparente (nada do arquivo original sobrevive)
    $out = imagecreatetruecolor($ow, $oh);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagefill($out, 0, 0, imagecolorallocatealpha($out, 255, 255, 255, 127));
    imagecopyresampled($out, $img, 0, 0, $x0, $y0, $ow, $oh, $cw, $ch);

    protegerPastasFotos();
    $base = (slug($nomeCliente, 40) ?: 'cliente') . '-' . bin2hex(random_bytes(5));
    $arquivo = gravarLogo($out, pastaFotos('clientes'), $base);
    return ['logo' => $arquivo, 'largura' => $ow, 'altura' => $oh, 'transparente' => $analise['transparente'], 'antes' => [$w, $h]];
}

function gravarLogo(GdImage $img, string $pasta, string $base): string
{
    if (!@is_dir($pasta) && !@mkdir($pasta, 0755, true) && !@is_dir($pasta)) throw new ErroFoto('Não consegui criar a pasta dos logos no servidor.');
    $q = max(50, min(100, (int) (cfg('logo_qualidade') ?? 90)));
    $tentativas = function_exists('imagewebp') ? ['webp', 'png'] : ['png'];
    foreach ($tentativas as $ext) {
        $nome = $base . '.' . $ext;
        $tmp = $pasta . '/.' . $nome . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $ok = $ext === 'webp' ? @imagewebp($img, $tmp, $q) : @imagepng($img, $tmp, 9);
        clearstatcache(true, $tmp);
        if ($ok && @filesize($tmp) > 0 && @rename($tmp, $pasta . '/' . $nome)) {
            @chmod($pasta . '/' . $nome, 0644);
            return $nome;
        }
        @unlink($tmp);
    }
    throw new ErroFoto('Não consegui salvar o logo no servidor (sem espaço ou sem permissão na pasta compartilhado/img/clientes).');
}

/** Apaga um logo recém-salvo (quando o cliente acabou não sendo gravado). */
function descartarLogo(array $logo): void
{
    if (!empty($logo['logo']) && preg_match(RE_LOGO_DO_PAINEL, $logo['logo'])) @unlink(pastaFotos('clientes') . '/' . $logo['logo']);
}

/* ------------------------------------------------------------------
   Lixeira de logos (a mesma pasta privada lixeira/ das fotos)
   ------------------------------------------------------------------ */
function logosUsados(array $clientes): array
{
    $u = [];
    foreach ($clientes as $c) {
        if (is_array($c) && is_string($c['logo'] ?? null) && preg_match(RE_ARQUIVO_FOTO, $c['logo'])) $u[$c['logo']] = true;
    }
    return $u;
}

/** Logo do painel que saiu da lista → lixeira; logo da lista que sumiu → volta da lixeira. */
function sincronizarLogos(array $antes, array $depois): void
{
    $a = logosUsados($antes);
    $d = logosUsados($depois);
    foreach (array_diff_key($a, $d) as $arq => $_) {
        if (preg_match(RE_LOGO_DO_PAINEL, $arq)) moverParaLixeira('clientes', $arq);
    }
    foreach ($d as $arq => $_) if (!@is_file(pastaFotos('clientes') . '/' . $arq)) voltarDaLixeira('clientes', $arq);
}

/** Apaga da lixeira os logos que nenhuma cópia (nem a lista atual) usa mais (no máximo 1 vez por hora). */
function limparLixeiraClientes(bool $agora = false): void
{
    $arqs = glob(pastaPrivada() . '/lixeira/clientes--*.php') ?: [];
    $velhas = array_filter($arqs, static fn($a) => (int) @filemtime($a) < time() - 3600);
    if (!$velhas) return;
    $marca = caminhoPrivado('lixeira-clientes-conferida.php');
    if (!$agora && @is_file($marca) && (int) @filemtime($marca) > time() - 3600) return;
    @file_put_contents($marca, PREFIXO_PRIVADO);
    $usados = [];
    foreach (arquivosCopiasClientes() as $c) {
        $d = lerJsonPrivado('copias/' . basename($c));
        if (is_array($d['clientes'] ?? null)) $usados += logosUsados($d['clientes']);
    }
    try { $usados += logosUsados(lerClientes()['clientes']); } catch (Throwable $t) { return; }   // lista ilegível: não apaga nada
    foreach ($velhas as $a) {
        if (!preg_match('/^clientes--(.+)\.php$/', basename($a), $m)) continue;
        if (!isset($usados[$m[1]])) @unlink($a);
    }
}

/* ------------------------------------------------------------------
   Cópias de segurança da lista (pasta privada copias/clientes-*.php)
   Mesmas regras das cópias do catálogo (config.php): juntar ações
   seguidas de ordem, guardar as 30 mais recentes, 14 dias etc.
   ------------------------------------------------------------------ */
function fazerCopiaClientes(array $antes, array $acao): string
{
    $tipo = preg_match('/^[a-z]{3,12}$/', (string) ($acao['tipo'] ?? '')) ? (string) $acao['tipo'] : 'outro';
    $micro = microtime(true);
    $agora = (int) $micro;
    $usuario = usuarioLogado() ?? '';
    if (in_array($tipo, TIPOS_JUNTAR, true)) {
        $ultima = arquivosCopiasClientes()[0] ?? null;
        $d = $ultima ? lerJsonPrivado('copias/' . basename($ultima)) : null;
        $janela = max(1, (int) (cfg('copias_juntar_min') ?? 10)) * 60;
        if (is_array($d) && ($d['tipo'] ?? '') === $tipo && ($d['usuario'] ?? '') === $usuario
            && (int) ($d['ultimo'] ?? $d['criado'] ?? 0) >= $agora - $janela && is_array($d['clientes'] ?? null)) {
            $d['ultimo'] = $agora;
            $d['juntou'] = (int) ($d['juntou'] ?? 1) + 1;
            $d['feitos'] = array_slice(array_merge(is_array($d['feitos'] ?? null) ? $d['feitos'] : [], [(string) ($acao['feito'] ?? '')]), -40);
            gravarJsonPrivado('copias/' . basename($ultima), $d);
            return basename($ultima);
        }
    }
    $nome = 'clientes-' . date('Ymd-His', $agora) . '-' . sprintf('%03d', min(999, (int) (($micro - $agora) * 1000))) . '-' . bin2hex(random_bytes(2)) . '-' . $tipo . '.php';
    gravarJsonPrivado('copias/' . $nome, [
        'versao' => 1,
        'area' => 'clientes',
        'criado' => $agora,
        'ultimo' => $agora,
        'usuario' => $usuario,
        'tipo' => $tipo,
        'motivo' => (string) ($acao['antes'] ?? ''),
        'feitos' => [(string) ($acao['feito'] ?? '')],
        'juntou' => 1,
        'alvo' => (string) ($acao['alvo'] ?? ''),
        'alvoNome' => (string) ($acao['alvoNome'] ?? ''),
        'existia' => (bool) ($antes['existe'] ?? true),
        'clientes' => array_values($antes['clientes']),
    ]);
    podarCopias(arquivosCopiasClientes(), RE_COPIA_CLIENTES);
    limparLixeiraClientes();
    return $nome;
}

/** Caminhos das cópias da lista de clientes, mais novas primeiro. */
function arquivosCopiasClientes(): array
{
    $lista = glob(pastaPrivada() . '/copias/clientes-[0-9]*.php') ?: [];
    $lista = array_filter($lista, static fn($a) => (bool) preg_match(RE_COPIA_CLIENTES, basename($a)));
    rsort($lista, SORT_STRING);
    return array_values($lista);
}

function listarCopiasClientes(): array
{
    $out = [];
    foreach (arquivosCopiasClientes() as $a) {
        $d = lerJsonPrivado('copias/' . basename($a));
        if (!is_array($d) || !is_array($d['clientes'] ?? null)) continue;
        $info = infoNomeCopia(basename($a), RE_COPIA_CLIENTES);
        $out[] = [
            'nome' => basename($a),
            'criado' => (int) ($d['criado'] ?? $info['criado']),
            'ultimo' => (int) ($d['ultimo'] ?? $d['criado'] ?? $info['criado']),
            'usuario' => (string) ($d['usuario'] ?? ''),
            'tipo' => (string) ($d['tipo'] ?? $info['tipo']),
            'motivo' => (string) ($d['motivo'] ?? ''),
            'feitos' => feitosDaCopia($d),
            'juntou' => max(1, (int) ($d['juntou'] ?? 1)),
            'alvo' => (string) ($d['alvo'] ?? ''),
            'alvoNome' => (string) ($d['alvoNome'] ?? ''),
            'total' => count($d['clientes']),
            'existia' => ($d['existia'] ?? true) !== false,
        ];
    }
    return $out;
}

function lerCopiaClientes(string $nome): array
{
    if (!preg_match(RE_COPIA_CLIENTES, $nome)) throw new ErroUsuario('Cópia inválida.');
    $d = lerJsonPrivado('copias/' . $nome);
    if (!is_array($d) || !is_array($d['clientes'] ?? null)) throw new ErroUsuario('Essa cópia não foi encontrada ou está danificada.');
    try {
        $d['clientes'] = interpretarClientes(montarClientes($d['clientes']));   // valida como se fosse o arquivo do site
    } catch (ErroClientes $e) {
        throw new ErroUsuario('Essa cópia está danificada (' . $e->getMessage() . ').');
    }
    return $d;
}

/** Texto da confirmação de restaurar a cópia $i (lista de listarCopiasClientes(), mais novas primeiro). */
function textoRestaurarClientes(array $copias, int $i, ?int $totalAtual): string
{
    $c = $copias[$i];
    $n = 0;
    for ($j = 0; $j <= $i; $j++) $n += (int) $copias[$j]['juntou'];
    $frases = resumoFeitos($copias, $i);
    $max = 6;
    $linhas = array_map(static fn($f) => '• ' . $f, array_slice($frases, 0, $max));
    if (count($frases) > $max) $linhas[] = '• e mais ' . (count($frases) - $max) . (count($frases) - $max === 1 ? ' item' : ' itens');
    return 'Voltar a lista de clientes inteira para como estava em ' . quando($c['criado']) . '? '
        . ($n === 1 ? 'Isso desfaz 1 alteração:' : 'Isso desfaz ' . $n . ' alterações, inclusive as feitas depois:') . "\n"
        . implode("\n", $linhas) . "\n"
        . 'Na cópia: ' . $c['total'] . ($c['total'] === 1 ? ' cliente' : ' clientes') . ($totalAtual !== null ? '; hoje: ' . $totalAtual . '.' : '.')
        . ' O estado atual vira uma cópia, então dá para desfazer.';
}

function textoExcluirCliente(array $c): string
{
    return 'Excluir “' . (string) ($c['nome'] ?? '') . '” da lista de clientes? O logo sai do site na hora e vai para a lixeira do painel. '
        . 'Logo depois aparece o botão Desfazer, e a exclusão fica em Cópias de segurança por ' . (int) cfg('copias_protegidas_dias') . ' dias.';
}
