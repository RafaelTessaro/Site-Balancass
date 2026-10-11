<?php
/* =====================================================================
   Painel Balanças.com — fotos
   Recebe JPEG/PNG/WebP (até 8 MB), confere com finfo + getimagesize,
   abre com GD e REDESENHA a imagem (nada do arquivo original sobrevive:
   metadados, comentários, código escondido), corrige a rotação do
   celular, põe sobre fundo branco, recorta bordas vazias, reduz para no
   máximo 900 px e salva em WebP (ou JPEG) com nome aleatório.
   Se a foto tiver fundo transparente, também salva uma versão "recorte"
   (fundo transparente) para as seções escuras do site.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

final class ErroFoto extends RuntimeException {}

const TIPOS_FOTO = ['image/jpeg' => IMAGETYPE_JPEG, 'image/png' => IMAGETYPE_PNG, 'image/webp' => IMAGETYPE_WEBP];

function htaccessFotos(): string
{
    return "# Gerado pelo painel. Esta pasta só guarda fotos: nenhum script pode rodar aqui.\n"
        . "<FilesMatch \"(?i)\\.(php\\d?|phtml|phar|cgi|pl|py|sh)(\\.|$)\">\n"
        . "  <IfModule mod_authz_core.c>\n    Require all denied\n  </IfModule>\n"
        . "  <IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n  </IfModule>\n"
        . "</FilesMatch>\n";
}

/** Cria o .htaccess de proteção nas pastas de fotos e de logos de clientes, se ainda não existir. */
function protegerPastasFotos(): void
{
    foreach (['produtos', 'recortes', 'clientes'] as $tipo) {
        $dir = pastaFotos($tipo);
        if (!@is_dir($dir)) @mkdir($dir, 0755, true);
        if (@is_dir($dir) && !@is_file($dir . '/.htaccess')) @file_put_contents($dir . '/.htaccess', htaccessFotos());
    }
}

function bytesIni(string $v): int
{
    $v = trim($v);
    if ($v === '' || $v === '-1') return -1;
    $n = (int) $v;
    switch (strtolower(substr($v, -1))) {
        case 'g': $n *= 1024; // segue
        case 'm': $n *= 1024; // segue
        case 'k': $n *= 1024;
    }
    return $n;
}

/** Maior arquivo que o servidor aceita receber (menor entre o painel e o PHP). */
function limiteEnvio(): int
{
    $lim = [(int) cfg('foto_max_bytes')];
    foreach (['upload_max_filesize', 'post_max_size'] as $k) {
        $b = bytesIni((string) ini_get($k));
        if ($b > 0) $lim[] = $b;
    }
    return min($lim);
}

function mb_(int $bytes): string
{
    return str_replace('.', ',', (string) round($bytes / 1048576, $bytes < 10485760 ? 1 : 0)) . ' MB';
}

function garantirMemoria(int $pixels): void
{
    $precisa = (int) ($pixels * 4 * 2.6) + 48 * 1048576;
    $atual = bytesIni((string) ini_get('memory_limit'));
    if ($atual !== -1 && $atual < $precisa) @ini_set('memory_limit', (string) $precisa);
}

/* ------------------------------------------------------------------
   Rotação gravada pelo celular (EXIF Orientation), sem depender da
   extensão exif do PHP
   ------------------------------------------------------------------ */
function orientacaoExif(string $arquivo): int
{
    $f = @fopen($arquivo, 'rb');
    if (!$f) return 1;
    $d = (string) fread($f, 262144);
    fclose($f);
    if (strncmp($d, "\xFF\xD8", 2) !== 0) return 1;
    $p = 2;
    $n = strlen($d);
    while ($p + 4 <= $n) {
        if ($d[$p] !== "\xFF") return 1;
        $m = ord($d[$p + 1]);
        if ($m === 0xFF) { $p++; continue; }
        if ($m === 0x01 || ($m >= 0xD0 && $m <= 0xD8)) { $p += 2; continue; }
        if ($m === 0xDA || $m === 0xD9) return 1;
        $len = unpack('n', substr($d, $p + 2, 2))[1];
        if ($len < 2) return 1;
        if ($m === 0xE1 && substr($d, $p + 4, 6) === "Exif\0\0") return orientacaoTiff(substr($d, $p + 10, $len - 8));
        $p += 2 + $len;
    }
    return 1;
}

function orientacaoTiff(string $t): int
{
    $tam = strlen($t);
    if ($tam < 8) return 1;
    $ordem = substr($t, 0, 2);
    if ($ordem === 'II') { $u16 = 'v'; $u32 = 'V'; }
    elseif ($ordem === 'MM') { $u16 = 'n'; $u32 = 'N'; }
    else return 1;
    $ifd = unpack($u32, substr($t, 4, 4))[1];
    if ($ifd < 8 || $ifd + 2 > $tam) return 1;
    $num = unpack($u16, substr($t, $ifd, 2))[1];
    for ($i = 0; $i < $num && $i < 256; $i++) {
        $e = $ifd + 2 + $i * 12;
        if ($e + 12 > $tam) break;
        if (unpack($u16, substr($t, $e, 2))[1] === 0x0112) {
            $v = unpack($u16, substr($t, $e + 8, 2))[1];
            return ($v >= 1 && $v <= 8) ? $v : 1;
        }
    }
    return 1;
}

function aplicarOrientacao(GdImage $img, int $o): GdImage
{
    $girar = static function (GdImage $i, int $graus): GdImage {
        $r = imagerotate($i, $graus, 0);
        if (!$r) throw new ErroFoto('Não consegui girar a foto.');
        return $r;
    };
    switch ($o) {
        case 2: imageflip($img, IMG_FLIP_HORIZONTAL); return $img;
        case 3: return $girar($img, 180);
        case 4: imageflip($img, IMG_FLIP_VERTICAL); return $img;
        case 5: $r = $girar($img, 270); imageflip($r, IMG_FLIP_HORIZONTAL); return $r;
        case 6: return $girar($img, 270);
        case 7: $r = $girar($img, 90); imageflip($r, IMG_FLIP_HORIZONTAL); return $r;
        case 8: return $girar($img, 90);
    }
    return $img;
}

/* ------------------------------------------------------------------
   Análise numa miniatura: transparência e área ocupada pelo produto
   ------------------------------------------------------------------ */
function miniatura(GdImage $img, int $max): GdImage
{
    $w = imagesx($img); $h = imagesy($img);
    $f = min(1, $max / max($w, $h));
    $tw = max(1, (int) round($w * $f)); $th = max(1, (int) round($h * $f));
    $t = imagecreatetruecolor($tw, $th);
    imagealphablending($t, false);
    imagesavealpha($t, true);
    imagefill($t, 0, 0, imagecolorallocatealpha($t, 255, 255, 255, 127));
    imagecopyresampled($t, $img, 0, 0, 0, 0, $tw, $th, $w, $h);
    return $t;
}

/** ['transparente' => bool, 'caixa' => [x0, y0, x1, y1] em coordenadas da foto | null] */
function analisarFoto(GdImage $img): array
{
    $t = miniatura($img, 320);
    $tw = imagesx($t); $th = imagesy($t);
    $transp = 0;
    $a = [$tw, $th, -1, -1];   // caixa do que não é transparente
    $b = [$tw, $th, -1, -1];   // caixa do que não é branco (com a foto sobre fundo branco)
    for ($y = 0; $y < $th; $y++) {
        for ($x = 0; $x < $tw; $x++) {
            $c = imagecolorat($t, $x, $y);
            $alfa = ($c >> 24) & 0x7F;                 // 0 = opaco, 127 = transparente
            if ($alfa > 96) $transp++;
            if ($alfa < 96) { if ($x < $a[0]) $a[0] = $x; if ($y < $a[1]) $a[1] = $y; if ($x > $a[2]) $a[2] = $x; if ($y > $a[3]) $a[3] = $y; }
            $op = 1 - $alfa / 127;
            $min = min(($c >> 16) & 255, ($c >> 8) & 255, $c & 255) * $op + 255 * (1 - $op);
            if ($min < 238) { if ($x < $b[0]) $b[0] = $x; if ($y < $b[1]) $b[1] = $y; if ($x > $b[2]) $b[2] = $x; if ($y > $b[3]) $b[3] = $y; }
        }
    }
    $transparente = $transp / ($tw * $th) > 0.01;
    $c = $transparente ? $a : $b;
    if ($c[2] < 0) {
        if ($transparente) throw new ErroFoto('A foto parece estar vazia (toda transparente).');
        return ['transparente' => false, 'caixa' => null];   // toda branca/clara: usa inteira
    }
    // Volta para a escala da foto, com uma folga de 3%
    $w = imagesx($img); $h = imagesy($img);
    $sx = $w / $tw; $sy = $h / $th;
    $x0 = (int) floor($c[0] * $sx); $y0 = (int) floor($c[1] * $sy);
    $x1 = (int) ceil(($c[2] + 1) * $sx); $y1 = (int) ceil(($c[3] + 1) * $sy);
    $m = (int) round(0.03 * max($x1 - $x0, $y1 - $y0));
    $caixa = [max(0, $x0 - $m), max(0, $y0 - $m), min($w, $x1 + $m), min($h, $y1 + $m)];
    // Só recorta se ganhar pelo menos 2% em algum lado
    $cw = $caixa[2] - $caixa[0]; $ch = $caixa[3] - $caixa[1];
    if ($cw > $w * 0.98 && $ch > $h * 0.98) $caixa = null;
    return ['transparente' => $transparente, 'caixa' => $caixa];
}

/* ------------------------------------------------------------------ */
function gravarImagem(GdImage $img, string $pasta, string $base, bool $alfa): string
{
    $q = (int) cfg('foto_qualidade');
    $tentativas = [];
    if (function_exists('imagewebp')) $tentativas[] = 'webp';
    $tentativas[] = $alfa ? 'png' : 'jpg';
    foreach ($tentativas as $ext) {
        $nome = $base . '.' . $ext;
        $tmp = $pasta . '/.' . $nome . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if ($ext === 'jpg') imageinterlace($img, true);   // JPEG progressivo
        $ok = match ($ext) {
            'webp' => @imagewebp($img, $tmp, $q),
            'png'  => @imagepng($img, $tmp, 9),
            'jpg'  => @imagejpeg($img, $tmp, min(95, $q + 2)),
        };
        clearstatcache(true, $tmp);
        if ($ok && @filesize($tmp) > 0 && @rename($tmp, $pasta . '/' . $nome)) {
            @chmod($pasta . '/' . $nome, 0644);
            return $nome;
        }
        @unlink($tmp);
    }
    throw new ErroFoto('Não consegui salvar a foto no servidor (sem espaço ou sem permissão na pasta de fotos).');
}

/**
 * Processa o arquivo enviado ($_FILES['foto']) para o produto $id.
 * Devolve ['imagem' => arquivo, 'recorte' => arquivo|null, 'largura' => px, 'altura' => px].
 */
function processarFoto($arq, string $id): array
{
    if (!is_array($arq) || !isset($arq['error'], $arq['tmp_name']) || is_array($arq['error'])) throw new ErroFoto('Envio de foto inválido.');
    switch ((int) $arq['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE: throw new ErroFoto('A foto é maior do que o servidor aceita (' . mb_(limiteEnvio()) . '). Use uma foto menor.');
        case UPLOAD_ERR_PARTIAL: throw new ErroFoto('O envio da foto foi interrompido. Tente de novo.');
        case UPLOAD_ERR_NO_FILE: throw new ErroFoto('Escolha uma foto para o produto.');
        default: throw new ErroFoto('O servidor não conseguiu receber a foto (erro ' . (int) $arq['error'] . '). Tente de novo.');
    }
    $tmp = (string) $arq['tmp_name'];
    if (!is_uploaded_file($tmp)) throw new ErroFoto('Envio de foto inválido.');
    return converterFoto($tmp, $id);
}

/** Confere, redesenha e salva a foto que está em $tmp (já recebida pelo servidor). */
function converterFoto(string $tmp, string $id): array
{
    if (!preg_match(RE_ID, $id)) throw new ErroFoto('Código do produto inválido para nomear a foto.');
    $tam = (int) @filesize($tmp);
    if ($tam <= 0) throw new ErroFoto('O arquivo da foto chegou vazio.');
    if ($tam > (int) cfg('foto_max_bytes')) throw new ErroFoto('A foto tem ' . mb_($tam) . '; o máximo é ' . mb_((int) cfg('foto_max_bytes')) . '.');

    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (in_array($mime, ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'], true)) {
        throw new ErroFoto('Seu celular salvou a foto em HEIC, formato que o site não usa. Na câmera, desligue a opção “alta eficiência” (HEIF) ou compartilhe a foto como JPG e escolha de novo.');
    }
    if ($mime === 'application/pdf') throw new ErroFoto('Esse arquivo é um PDF, não uma foto. Salve a imagem do produto como JPG (ou tire um print) e escolha de novo.');
    if (!isset(TIPOS_FOTO[$mime])) throw new ErroFoto('Esse arquivo não é uma foto JPG, PNG ou WebP. Use uma foto tirada pela câmera ou salva do site do fabricante.');
    $info = @getimagesize($tmp);
    if (!is_array($info) || ($info[2] ?? null) !== TIPOS_FOTO[$mime] || ($info['mime'] ?? '') !== $mime) {
        throw new ErroFoto('Esse arquivo não é uma foto válida (ou está danificado).');
    }
    [$w, $h] = $info;
    $min = max(1, (int) (cfg('foto_min_lado') ?? 300));
    if ($w < $min || $h < $min) throw new ErroFoto('A foto é pequena demais (' . $w . ' × ' . $h . ' px). Use uma com pelo menos ' . $min . ' px de largura e de altura.');
    // Teto de pixels: o site usa no máximo 900 px; acima disso só gastaria memória do servidor
    $maxPx = max(1000000, (int) (cfg('foto_max_pixels') ?? 20000000));
    if ($w > 8000 || $h > 8000 || $w * $h > $maxPx) {
        throw new ErroFoto('A foto tem resolução alta demais (' . $w . ' × ' . $h . ' px). Reduza para até 4000 px no lado maior e escolha de novo.');
    }

    garantirMemoria($w * $h);
    $img = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($tmp),
        'image/png'  => @imagecreatefrompng($tmp),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
    };
    if (!$img) throw new ErroFoto('Não consegui abrir a foto (arquivo danificado ou formato não suportado). Salve de novo como JPG e tente outra vez.');
    if (!imageistruecolor($img)) imagepalettetotruecolor($img);
    if ($mime === 'image/jpeg') $img = aplicarOrientacao($img, orientacaoExif($tmp));

    $analise = analisarFoto($img);
    $sw = imagesx($img); $sh = imagesy($img);
    [$x0, $y0, $x1, $y1] = $analise['caixa'] ?? [0, 0, $sw, $sh];
    $cw = $x1 - $x0; $ch = $y1 - $y0;
    $lado = (int) cfg('foto_max_lado');
    $f = min(1, $lado / max($cw, $ch));
    $ow = max(1, (int) round($cw * $f)); $oh = max(1, (int) round($ch * $f));

    protegerPastasFotos();
    $base = rtrim(substr($id, 0, 50), '-') . '-' . bin2hex(random_bytes(5));

    // Foto principal: sobre fundo branco (achata a transparência)
    $out = imagecreatetruecolor($ow, $oh);
    imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
    imagealphablending($out, true);
    imagecopyresampled($out, $img, 0, 0, $x0, $y0, $ow, $oh, $cw, $ch);
    $principal = gravarImagem($out, pastaFotos('produtos'), $base, false);

    // Recorte: mesma área, fundo transparente
    $recorte = null;
    if ($analise['transparente']) {
        try {
            $rc = imagecreatetruecolor($ow, $oh);
            imagealphablending($rc, false);
            imagesavealpha($rc, true);
            imagefill($rc, 0, 0, imagecolorallocatealpha($rc, 255, 255, 255, 127));
            imagecopyresampled($rc, $img, 0, 0, $x0, $y0, $ow, $oh, $cw, $ch);
            $recorte = gravarImagem($rc, pastaFotos('recortes'), $base, true);
        } catch (Throwable $t) {
            $recorte = null;   // sem recorte o site usa a foto com fundo branco
        }
    }
    return ['imagem' => $principal, 'recorte' => $recorte, 'largura' => $ow, 'altura' => $oh];
}

/** Apaga uma foto recém-salva (quando o produto acabou não sendo gravado). */
function descartarFoto(array $foto): void
{
    if (!empty($foto['imagem']) && preg_match(RE_FOTO_DO_PAINEL, $foto['imagem'])) @unlink(pastaFotos('produtos') . '/' . $foto['imagem']);
    if (!empty($foto['recorte']) && preg_match(RE_FOTO_DO_PAINEL, $foto['recorte'])) @unlink(pastaFotos('recortes') . '/' . $foto['recorte']);
}
