<?php
/* =====================================================================
   Painel Balanças.com — núcleo
   Configuração, cabeçalhos de segurança, erros, pasta privada (arquivos
   .php que começam com <?php exit; ?>), gravação atômica, travas, sessão
   (guardada na pasta privada), CSRF, mensagens e registro de atividades.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

const PREFIXO_PRIVADO = "<?php exit; ?>\n";
const JSON_GRAVAR = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP;
const JSON_PRIVADO = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

final class ErroUsuario extends RuntimeException {}   // mensagem pode ir para a tela

function cfg(string $chave)
{
    static $c = null;
    if ($c === null) {
        $c = require __DIR__ . '/../config.php';
        if (!is_array($c)) throw new RuntimeException('config.php inválido');
        // Só na linha de comando (ferramentas/aplicar-pacote.php): outra pasta compartilhado/ e uma pasta
        // privada temporária. Num pedido pelo navegador isto nunca vale.
        if (PHP_SAPI === 'cli' && is_array($GLOBALS['PAINEL_CFG_CLI'] ?? null)) $c = $GLOBALS['PAINEL_CFG_CLI'] + $c;
    }
    return $c[$chave] ?? null;
}

/* ------------------------------------------------------------------
   Utilidades de texto e saída
   ------------------------------------------------------------------ */
function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

function tamanho(string $s): int
{
    if (function_exists('mb_strlen')) return mb_strlen($s, 'UTF-8');
    return (int) preg_match_all('/./us', $s);
}

function cortar(string $s, int $n): string
{
    if (function_exists('mb_substr')) return mb_substr($s, 0, $n, 'UTF-8');
    return preg_match('/^.{0,' . $n . '}/us', $s, $m) ? $m[0] : '';
}

function https(): bool
{
    $h = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    return ($h !== '' && $h !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function ip(): string
{
    // Só REMOTE_ADDR: cabeçalhos como X-Forwarded-For podem ser inventados por quem ataca.
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function pedidoJson(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function link_(string $acao = 'lista', array $params = []): string
{
    $q = $acao === 'lista' && !$params ? '' : http_build_query(['a' => $acao] + $params);
    return 'index.php' . ($q !== '' ? '?' . $q : '');
}

function redirecionar(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

function responderJson(array $dados, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    exit;
}

function quando(int $ts, bool $comHora = true): string
{
    return date('d/m/Y', $ts) . ($comHora ? ' às ' . date('H:i', $ts) : '');
}

/* ------------------------------------------------------------------
   Inicialização: erros, fuso, cabeçalhos de segurança
   ------------------------------------------------------------------ */
function iniciarPainel(): void
{
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
    date_default_timezone_set((string) cfg('fuso'));
    if (function_exists('mb_internal_encoding')) mb_internal_encoding('UTF-8');

    set_error_handler(static function (int $nivel, string $msg, string $arq = '', int $linha = 0): bool {
        if (!(error_reporting() & $nivel)) return false;            // chamada com @
        if ($nivel & (E_DEPRECATED | E_USER_DEPRECATED)) return false;
        throw new ErrorException($msg, 0, $nivel, $arq, $linha);
    });
    set_exception_handler(static function (Throwable $ex): void {
        $ref = strtoupper(bin2hex(random_bytes(3)));
        try { registrarErro($ref, $ex); } catch (Throwable $t) { error_log('painel: ' . $ex); }
        if (!headers_sent()) { http_response_code(500); header('Content-Type: text/html; charset=utf-8'); }
        if (pedidoJson()) { echo json_encode(['ok' => false, 'msg' => 'Algo deu errado (ref. ' . $ref . '). Tente de novo.']); return; }
        paginaErro('Algo deu errado', 'Não foi possível concluir. Nada foi perdido: tente de novo. Se continuar, informe o código ' . $ref . ' a quem cuida do site.');
    });

    cabecalhosSeguranca();
}

function cabecalhosSeguranca(): void
{
    header_remove('X-Powered-By');
    header('Content-Type: text/html; charset=utf-8');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' https://fonts.googleapis.com; "
        . "font-src https://fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self'; form-action 'self'; "
        . "frame-ancestors 'none'; base-uri 'none'; manifest-src 'self'");
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('Cache-Control: no-store, max-age=0');
    header('Pragma: no-cache');
    if (https()) header('Strict-Transport-Security: max-age=15552000');
}

/* ------------------------------------------------------------------
   Pasta privada
   ------------------------------------------------------------------ */
/** Pasta acima da raiz do site (ex.: /home/conta, ao lado de public_html), ou null. */
function pastaAcimaDoSite(): ?string
{
    $doc = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
    $doc = $doc !== '' ? @realpath($doc) : false;
    if (!$doc) return null;
    $partes = explode('/', $doc);
    // Domínio adicional dentro de public_html (public_html/outro-site): sobe até acima de public_html,
    // para a pasta privada nunca ficar dentro de outro site.
    foreach ($partes as $i => $parte) {
        if ($i > 0 && in_array(strtolower($parte), ['public_html', 'www', 'htdocs', 'httpdocs', 'wwwroot', 'html'], true)) {
            $acima = implode('/', array_slice($partes, 0, $i));
            return ($acima === '' || substr_count($acima, '/') < 2) ? null : $acima;   // nunca "/" nem "/home"
        }
    }
    $acima = dirname($doc);
    return substr_count($acima, '/') < 2 ? null : $acima;
}

function pastaPrivada(): string
{
    static $p = null;
    if ($p !== null) return $p;
    $conf = cfg('pasta_privada');
    $interna = dirname(__DIR__) . '/privado';
    if (is_string($conf) && $conf !== '') {
        $p = rtrim($conf, '/');
    } else {
        $acima = pastaAcimaDoSite();
        $externa = $acima ? $acima . '/' . cfg('nome_pasta_privada') : null;
        if ($externa && @is_dir($externa)) $p = $externa;
        elseif (@is_file($interna . '/config.php') || @is_file($interna . '/primeiro-acesso.php')) $p = $interna;
        elseif ($externa && @is_writable($acima) && (@mkdir($externa, 0700) || @is_dir($externa))) $p = $externa;
        else $p = $interna;
    }
    prepararPastaPrivada($p);
    return $p;
}

/** true quando a pasta privada está fora da raiz do site (o caso ideal). */
function privadaForaDoSite(): bool
{
    $doc = @realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $p = @realpath(pastaPrivada());
    return $doc && $p && strpos($p . '/', rtrim($doc, '/') . '/') !== 0;
}

/** Descrição, para o dono, de onde fica a pasta privada no Gerenciador de Arquivos. */
function ondeFicaPastaPrivada(): string
{
    $p = @realpath(pastaPrivada()) ?: pastaPrivada();
    $doc = @realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($doc && strpos($p . '/', rtrim($doc, '/') . '/') === 0) {
        return basename($doc) . substr($p, strlen(rtrim($doc, '/')));     // ex.: public_html/painel/privado
    }
    $acima = pastaAcimaDoSite();
    if ($acima && strpos($p, $acima . '/') === 0) return substr($p, strlen($acima) + 1);   // ex.: painel-balancas-privado
    return $p;
}

function prepararPastaPrivada(string $dir): void
{
    if (!@is_dir($dir) && !@mkdir($dir, 0700, true) && !@is_dir($dir)) {
        throw new RuntimeException('Não foi possível criar a pasta privada: ' . $dir);
    }
    $ht = $dir . '/.htaccess';
    if (!@is_file($ht)) {
        @file_put_contents($ht, "# Pasta privada do painel: nada aqui pode ser aberto pelo navegador.\n"
            . "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n");
    }
    if (!@is_file($dir . '/index.php')) @file_put_contents($dir . '/index.php', PREFIXO_PRIVADO);
}

function caminhoPrivado(string $nome): string
{
    // Só nomes simples, sempre terminando em .php (nada de "../")
    if (!preg_match('~^(?:[a-z0-9-]+/)?[A-Za-z0-9][A-Za-z0-9._-]*\.php$~', $nome) || str_contains($nome, '..')) {
        throw new InvalidArgumentException('nome privado inválido');
    }
    $arq = pastaPrivada() . '/' . $nome;
    $sub = dirname($arq);
    if ($sub !== pastaPrivada()) prepararPastaPrivada($sub);
    return $arq;
}

function lerPrivado(string $nome): ?string
{
    $arq = caminhoPrivado($nome);
    if (!@is_file($arq)) return null;
    $c = @file_get_contents($arq);
    if ($c === false || strncmp($c, PREFIXO_PRIVADO, strlen(PREFIXO_PRIVADO)) !== 0) return null;
    return substr($c, strlen(PREFIXO_PRIVADO));
}

function gravarPrivado(string $nome, string $conteudo): void
{
    gravarAtomico(caminhoPrivado($nome), PREFIXO_PRIVADO . $conteudo, 0600);
}

function lerJsonPrivado(string $nome, $padrao = null)
{
    $c = lerPrivado($nome);
    if ($c === null) return $padrao;
    $d = json_decode($c, true);
    return $d === null ? $padrao : $d;
}

function gravarJsonPrivado(string $nome, $dados): void
{
    gravarPrivado($nome, json_encode($dados, JSON_PRIVADO | JSON_THROW_ON_ERROR));
}

/** Grava por completo ou não grava: arquivo temporário na mesma pasta + rename. */
function gravarAtomico(string $destino, string $conteudo, int $modo): void
{
    // Temporário de arquivo privado também termina em .php (e começa com o PREFIXO_PRIVADO)
    $tmp = dirname($destino) . '/.' . basename($destino) . '.' . bin2hex(random_bytes(5)) . (str_ends_with($destino, '.php') ? '.tmp.php' : '.tmp');
    $f = @fopen($tmp, 'xb');
    if (!$f) throw new RuntimeException('Sem permissão para gravar em ' . dirname($destino));
    try {
        $ok = fwrite($f, $conteudo) === strlen($conteudo) && fflush($f);
        if ($ok && function_exists('fsync')) @fsync($f);
    } finally {
        fclose($f);
    }
    if (!$ok) { @unlink($tmp); throw new RuntimeException('Falha ao gravar ' . basename($destino) . ' (disco cheio?)'); }
    @chmod($tmp, $modo);
    if (!@rename($tmp, $destino)) { @unlink($tmp); throw new RuntimeException('Falha ao substituir ' . basename($destino)); }
    clearstatcache(true, $destino);
}

/** Executa $fn com uma trava exclusiva (flock) — uma gravação por vez. */
function comTrava(string $nome, callable $fn)
{
    $arq = caminhoPrivado('trava-' . $nome . '.php');
    $f = @fopen($arq, 'c+');
    if (!$f) throw new RuntimeException('Não foi possível criar a trava ' . $nome);
    try {
        if (!flock($f, LOCK_EX)) throw new RuntimeException('Não foi possível travar ' . $nome);
        if (fstat($f)['size'] === 0) { fwrite($f, PREFIXO_PRIVADO); fflush($f); @chmod($arq, 0600); }
        return $fn();
    } finally {
        flock($f, LOCK_UN);
        fclose($f);
    }
}

/* ------------------------------------------------------------------
   Sessão guardada na pasta privada (arquivos sess_*.php)
   - não depende da limpeza automática do servidor (que costuma apagar
     sessões em 24 min), então os 2 h de inatividade valem de verdade;
   - cada arquivo começa com <?php exit; ?>.
   ------------------------------------------------------------------ */
final class SessaoPrivada implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    public function __construct(private string $dir, private int $vida) {}

    private function arq(string $id): ?string
    {
        return preg_match('/^[a-zA-Z0-9,-]{22,256}$/', $id) ? $this->dir . '/sess_' . $id . '.php' : null;
    }
    public function open(string $path, string $name): bool { return is_dir($this->dir); }
    public function close(): bool { return true; }
    public function read(string $id): string|false
    {
        $a = $this->arq($id);
        if (!$a || !@is_file($a) || @filemtime($a) + $this->vida < time()) return '';
        $c = @file_get_contents($a);
        if ($c === false || strncmp($c, PREFIXO_PRIVADO, strlen(PREFIXO_PRIVADO)) !== 0) return '';
        $d = base64_decode(substr($c, strlen(PREFIXO_PRIVADO)), true);
        return $d === false ? '' : $d;
    }
    public function write(string $id, string $data): bool
    {
        $a = $this->arq($id);
        if (!$a) return false;
        try { gravarAtomico($a, PREFIXO_PRIVADO . base64_encode($data), 0600); return true; }
        catch (Throwable $t) { return false; }
    }
    public function destroy(string $id): bool
    {
        $a = $this->arq($id);
        if ($a && @is_file($a)) @unlink($a);
        return true;
    }
    public function gc(int $max): int|false
    {
        $n = 0;
        foreach (glob($this->dir . '/sess_*.php') ?: [] as $a) {
            if (@filemtime($a) + $this->vida < time() && @unlink($a)) $n++;
        }
        return $n;
    }
    public function validateId(string $id): bool
    {
        $a = $this->arq($id);
        return $a !== null && @is_file($a) && @filemtime($a) + $this->vida >= time();
    }
    public function updateTimestamp(string $id, string $data): bool
    {
        $a = $this->arq($id);
        return $a !== null && @touch($a);
    }
}

function caminhoCookie(): string
{
    $dir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    return rtrim($dir, '/') . '/';
}

function iniciarSessao(): void
{
    $dir = pastaPrivada() . '/sessoes';
    prepararPastaPrivada($dir);
    $vida = (int) cfg('inatividade') + 300;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cache_limiter', '');
    ini_set('session.gc_maxlifetime', (string) $vida);
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '50');
    session_set_save_handler(new SessaoPrivada($dir, $vida), true);
    session_name('bc_painel');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => caminhoCookie(),
        'secure'   => https(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

/** Pedido vindo de outro site (link no WhatsApp Web, e-mail ou uma página maliciosa): o navegador
 *  não manda o cookie SameSite=Strict. Nesses casos o painel NÃO inicia sessão (isso criaria um
 *  cookie novo e derrubaria o login atual): GET recarrega a página uma vez como navegação do
 *  próprio site; POST é recusado. */
function chegouDeOutroSite(): bool
{
    return !isset($_COOKIE['bc_painel'])
        && ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site';
}

/* ------------------------------------------------------------------
   CSRF
   ------------------------------------------------------------------ */
function tokenCsrf(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function campoCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(tokenCsrf()) . '">';
}

function csrfValido(): bool
{
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || $t === '' || empty($_SESSION['csrf']) || !hash_equals((string) $_SESSION['csrf'], $t)) return false;
    // Defesa extra: o pedido tem de vir deste mesmo site
    $origem = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origem !== '' && $origem !== 'null') {
        $host = parse_url($origem, PHP_URL_HOST);
        $porta = parse_url($origem, PHP_URL_PORT);
        $esperado = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if (strtolower($host . ($porta ? ':' . $porta : '')) !== $esperado) return false;
    }
    $sfs = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
    return $sfs === '' || $sfs === 'same-origin';
}

/* ------------------------------------------------------------------
   Mensagens (aparecem uma vez, na próxima página)
   ------------------------------------------------------------------ */
function avisar(string $msg, string $tipo = 'ok', array $extra = []): void
{
    $_SESSION['avisos'][] = ['msg' => $msg, 'tipo' => $tipo] + $extra;
}

function pegarAvisos(): array
{
    $a = $_SESSION['avisos'] ?? [];
    unset($_SESSION['avisos']);
    return is_array($a) ? $a : [];
}

/* ------------------------------------------------------------------
   Registro de atividades (privado) e de erros
   ------------------------------------------------------------------ */
/** $quem: login de quem fez (padrão: quem está logado; "-" quando ninguém). */
function registrar(string $evento, string $detalhe = '', ?string $quem = null): void
{
    try {
        $arq = caminhoPrivado('registro.php');
        if (@is_file($arq) && @filesize($arq) > 512 * 1024) @rename($arq, caminhoPrivado('registro-anterior.php'));
        if (!@is_file($arq)) { @file_put_contents($arq, PREFIXO_PRIVADO, LOCK_EX); @chmod($arq, 0600); }
        $limpa = static fn(string $s): string => trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $s) ?? '');
        $linha = implode("\t", [date('Y-m-d H:i:s'), $limpa($quem ?? (string) ($_SESSION['usuario'] ?? '-')), ip(), $limpa($evento), $limpa(cortar($detalhe, 300))]) . "\n";
        @file_put_contents($arq, $linha, FILE_APPEND | LOCK_EX);
    } catch (Throwable $t) {
        error_log('painel: registro: ' . $t->getMessage());
    }
}

/** Últimas $n linhas do registro, mais novas primeiro ($anterior: inclui registro-anterior.php). */
function ultimasAtividades(int $n = 20, bool $anterior = false): array
{
    $c = lerPrivado('registro.php') ?? '';
    if ($anterior) $c = (lerPrivado('registro-anterior.php') ?? '') . "\n" . $c;
    $linhas = array_slice(array_filter(explode("\n", $c)), -$n);
    $out = [];
    foreach (array_reverse($linhas) as $l) {
        $p = explode("\t", $l);
        if (count($p) >= 4) $out[] = ['quando' => strtotime($p[0]) ?: 0, 'usuario' => $p[1], 'ip' => $p[2], 'evento' => $p[3], 'detalhe' => $p[4] ?? ''];
    }
    return $out;
}

function registrarErro(string $ref, Throwable $ex): void
{
    $arq = caminhoPrivado('erros.php');
    if (@is_file($arq) && @filesize($arq) > 512 * 1024) @rename($arq, caminhoPrivado('erros-anterior.php'));
    if (!@is_file($arq)) { @file_put_contents($arq, PREFIXO_PRIVADO, LOCK_EX); @chmod($arq, 0600); }
    $txt = date('Y-m-d H:i:s') . ' [' . $ref . '] ' . get_class($ex) . ': ' . $ex->getMessage() . ' em ' . $ex->getFile() . ':' . $ex->getLine() . "\n";
    @file_put_contents($arq, $txt, FILE_APPEND | LOCK_EX);
    error_log('painel [' . $ref . '] ' . $ex->getMessage());
}
