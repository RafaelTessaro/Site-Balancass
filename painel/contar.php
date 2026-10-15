<?php
/* =====================================================================
   Painel Balanças.com — contagem anônima do site ("Mais procurados")
   ---------------------------------------------------------------------
   Endereço público: o site (js/core.js) manda aqui, com navigator.sendBeacon,
   um aviso pequeno: {"t": "pagina|produto|whatsapp|ligar", "p": página,
   "id": código do produto ou "", "o": lugar do botão ou ""}.
   - Só POST, texto (text/plain) ou JSON, até 1 KB.
   - Nunca abre sessão nem grava cookie; não guarda IP nem navegador.
   - Responde sempre 204 (vazio), na hora, aceite ou não o aviso.
   As regras ficam em lib/estatisticas.php; a tela, em a=procurados.
   ===================================================================== */
declare(strict_types=1);
define('PAINEL', true);

header_remove('X-Powered-By');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
header('Referrer-Policy: no-referrer');

/** Responde 204 e solta o navegador; o resto (contar) continua no servidor, se der. */
function responderJa(int $status = 204): void
{
    header_remove('Set-Cookie');   // nunca: nem que alguma configuração do servidor abra sessão sozinha
    http_response_code($status);
    header('Content-Length: 0');
    if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();
    elseif (function_exists('litespeed_finish_request')) @litespeed_finish_request();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responderJa(405);
    exit;
}

// Antes de qualquer trabalho: o aviso tem cara de vir do site?
$ok = true;
$tipo = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
if (!in_array($tipo, ['text/plain', 'application/json'], true)) $ok = false;
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 1024) $ok = false;
// Pedido montado por outro site (formulário ou script de uma página qualquer): não conta
$sfs = (string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
if ($sfs !== '' && $sfs !== 'same-origin') $ok = false;
$origem = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
if ($origem !== '' && $origem !== 'null') {
    $host = (string) parse_url($origem, PHP_URL_HOST);
    $porta = parse_url($origem, PHP_URL_PORT);
    if (strtolower($host . ($porta ? ':' . $porta : '')) !== strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''))) $ok = false;
}
$ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
$corpo = $ok ? (string) @file_get_contents('php://input', false, null, 0, 1025) : '';

ignore_user_abort(true);
responderJa(204);
if (!$ok || $corpo === '' || strlen($corpo) > 1024) exit;

try {
    require __DIR__ . '/lib/nucleo.php';
    require __DIR__ . '/lib/catalogo.php';
    require __DIR__ . '/lib/estatisticas.php';
    ini_set('display_errors', '0');
    date_default_timezone_set((string) (cfg('fuso') ?: 'America/Sao_Paulo'));
    if (ehRobo($ua)) exit;
    $dados = json_decode($corpo, true, 4);
    if (!is_array($dados)) exit;
    $ev = eventoDoSite($dados, idsParaContagem());
    if ($ev === null) exit;
    contarEvento($ev, ip(), $ua);
} catch (Throwable $t) {
    // Nunca atrapalha o site: só anota para quem cuida do servidor
    error_log('painel/contar.php: ' . $t->getMessage());
}
