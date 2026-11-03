<?php
/* =====================================================================
   Painel Balanças.com — verificação em duas etapas
   Senha + código de 6 números que muda a cada 30 s no celular (TOTP,
   RFC 6238: Google Authenticator, Microsoft Authenticator, Authy…).
   Cada pessoa liga a sua em Conta; o administrador pode desligar a de
   outra pessoa (celular perdido) e exigir de todos. 10 códigos de
   reserva (só o hash fica guardado), "Lembrar este aparelho por 30 dias"
   (cookie só do painel; no servidor só o hash), tudo no registro.
   Dados: usuarios.php (campo dois_fatores), aparelhos.php e
   seguranca.php, na pasta privada. Telas: lib/visoes-dois-fatores.php.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

const EMISSOR_2FA = 'Balanças.com';
const COOKIE_APARELHO = 'bc_aparelho';
const RESERVAS_QTD = 10;
const RE_SEGREDO_2FA = '/^[A-Z2-7]{32}$/';
const RE_HASH_RESERVA = '/^\$2y\$\d\d\$[.\/A-Za-z0-9]{53}$/';
const RE_CODIGO_RESERVA = '/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{8}$/';   // ALFABETO_CODIGO (lib/auth.php): sem 0/O e 1/I/L
const ALFABETO_BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
const APARELHOS_POR_USUARIO = 10;
const APARELHOS_MAX = 200;

/** Código já usado / verificação mudou no meio (dentro da trava: nada é gravado). */
final class ErroCodigo2fa extends RuntimeException {}

/* ------------------------------------------------------------------
   Campo dois_fatores do usuário (usuarios.php)
   ------------------------------------------------------------------ */
/** null/ausente → null; todos os campos certos → o array normalizado; qualquer outra coisa → ['danificada' => true]. */
function normalizarDoisFatores($v): ?array
{
    if ($v === null) return null;
    $ruim = ['danificada' => true];
    if (!is_array($v)) return $ruim;
    $s = $v['segredo'] ?? null;
    $id = $v['id'] ?? null;
    $res = $v['reservas'] ?? null;
    if (!is_string($s) || !preg_match(RE_SEGREDO_2FA, $s) || !is_string($id) || !preg_match('/^[0-9a-f]{16}$/', $id)
        || !is_int($v['ligado'] ?? null) || $v['ligado'] <= 0 || !is_int($v['passo'] ?? null) || $v['passo'] < 0
        || !is_int($v['reservas_em'] ?? null) || $v['reservas_em'] <= 0 || !is_array($res) || count($res) > RESERVAS_QTD) {
        return $ruim;
    }
    foreach ($res as $h) if (!is_string($h) || !preg_match(RE_HASH_RESERVA, $h)) return $ruim;
    return ['segredo' => $s, 'id' => $id, 'ligado' => $v['ligado'], 'passo' => $v['passo'],
        'reservas' => array_values($res), 'reservas_em' => $v['reservas_em']];
}

/** Ligada (inclusive danificada: falha fechada, pede um código que nunca confere). */
function doisFatoresLigado(?array $u): bool
{
    return $u !== null && ($u['dois_fatores'] ?? null) !== null;
}

function doisFatoresDanificada(?array $u): bool
{
    return $u !== null && !empty($u['dois_fatores']['danificada']);
}

/** Ligada e boa (dá para conferir códigos). */
function doisFatoresOk(?array $u): bool
{
    return doisFatoresLigado($u) && !doisFatoresDanificada($u);
}

/* ------------------------------------------------------------------
   Base32 (RFC 4648) e TOTP (RFC 6238 / HOTP RFC 4226)
   ------------------------------------------------------------------ */
/** Maiúsculas, sem "=" no fim. */
function base32Codificar(string $bytes): string
{
    $out = '';
    $buf = 0;
    $nb = 0;
    for ($i = 0, $n = strlen($bytes); $i < $n; $i++) {
        $buf = ($buf << 8) | ord($bytes[$i]);
        $nb += 8;
        while ($nb >= 5) {
            $nb -= 5;
            $out .= ALFABETO_BASE32[($buf >> $nb) & 31];
        }
        $buf &= (1 << $nb) - 1;
    }
    if ($nb > 0) $out .= ALFABETO_BASE32[($buf << (5 - $nb)) & 31];
    return $out;
}

/** Aceita minúsculas, espaços e "=" no fim; outro caractere → null. */
function base32Decodificar(string $s): ?string
{
    $s = rtrim(strtoupper(preg_replace('/\s+/', '', $s) ?? ''), '=');
    if ($s !== '' && !preg_match('/^[A-Z2-7]+$/', $s)) return null;
    $out = '';
    $buf = 0;
    $nb = 0;
    for ($i = 0, $n = strlen($s); $i < $n; $i++) {
        $buf = ($buf << 5) | strpos(ALFABETO_BASE32, $s[$i]);
        $nb += 5;
        if ($nb >= 8) {
            $nb -= 8;
            $out .= chr(($buf >> $nb) & 0xFF);
        }
        $buf &= (1 << $nb) - 1;
    }
    return $out;
}

function hotp(string $chave, int $contador, int $digitos = 6): string
{
    $h = hash_hmac('sha1', pack('N2', $contador >> 32, $contador & 0xFFFFFFFF), $chave, true);
    $o = ord($h[19]) & 0x0F;
    $n = ((ord($h[$o]) & 0x7F) << 24) | (ord($h[$o + 1]) << 16) | (ord($h[$o + 2]) << 8) | ord($h[$o + 3]);
    return str_pad((string) ($n % (10 ** $digitos)), $digitos, '0', STR_PAD_LEFT);
}

/** Passo de 30 s (hora UTC do servidor: fuso e horário de verão não interferem). */
function passoTotp(?int $agora = null): int
{
    return intdiv($agora ?? time(), 30);
}

/** O código que o aplicativo mostra agora (ou em $agora). */
function codigoTotp(string $segredo, ?int $agora = null, int $digitos = 6): string
{
    return hotp(base32Decodificar($segredo) ?? '', passoTotp($agora), $digitos);
}

/**
 * Confere um código do aplicativo (passo atual ±1). Não grava nada: quem chama grava o passo dentro da
 * trava (consumirCodigo2fa). motivo: ok | formato | errado | usado | relogio | danificada.
 * relogio: o código bate com a hora de 1 a 5 min atrás/à frente (não entra; a mensagem fala da hora do celular).
 */
function conferirCodigoApp(array $df, string $digitado, ?int $agora = null): array
{
    $r = ['motivo' => 'errado', 'passo' => null, 'minutos' => 0, 'sentido' => ''];
    if (!empty($df['danificada'])) return ['motivo' => 'danificada'] + $r;
    $d = preg_replace('/[\s-]+/', '', $digitado) ?? '';
    if (!preg_match('/^\d{6}$/', $d)) return ['motivo' => 'formato'] + $r;
    $chave = base32Decodificar((string) ($df['segredo'] ?? ''));
    if ($chave === null || strlen($chave) < 10) return ['motivo' => 'danificada'] + $r;
    $p = passoTotp($agora);
    $bateu = null;
    for ($k = -1; $k <= 1; $k++) {   // compara os três, sem sair no primeiro; fica o maior que bateu
        if (hash_equals(hotp($chave, $p + $k), $d)) $bateu = $p + $k;
    }
    if ($bateu !== null) {
        $ultimo = (int) ($df['passo'] ?? 0);
        // Já usado — a não ser que o relógio do servidor tenha voltado (o gravado está no futuro)
        $usado = $bateu <= $ultimo && $ultimo <= $p + 1;
        return ['motivo' => $usado ? 'usado' : 'ok', 'passo' => $bateu] + $r;
    }
    $achou = null;
    for ($k = 2; $k <= 10; $k++) {
        foreach ([$k, -$k] as $dd) {
            if (hash_equals(hotp($chave, $p + $dd), $d) && $achou === null) $achou = $dd;
        }
    }
    if ($achou === null) return $r;
    return ['motivo' => 'relogio', 'passo' => null, 'minutos' => max(1, (int) round(abs($achou) * 30 / 60)),
        'sentido' => $achou > 0 ? 'adiantada' : 'atrasada'];
}

function novoSegredo2fa(): string
{
    return base32Codificar(random_bytes(20));
}

/** "ABCD EFGH IJKL …" (8 grupos de 4), para digitar à mão. */
function chaveAgrupada(string $segredo): string
{
    return trim(chunk_split($segredo, 4, ' '));
}

/** otpauth://totp/Balan%C3%A7as.com:<login>?secret=…&issuer=Balan%C3%A7as.com (SHA1/6/30 são o padrão: o QR fica menor). */
function uriOtpauth(string $login, string $segredo): string
{
    $emissor = rawurlencode(EMISSOR_2FA);
    return 'otpauth://totp/' . $emissor . ':' . rawurlencode($login) . '?secret=' . $segredo . '&issuer=' . $emissor;
}

/* ------------------------------------------------------------------
   Códigos de reserva (10, uso único, só o hash fica guardado)
   ------------------------------------------------------------------ */
/** [10 códigos "XXXX-XXXX" para mostrar uma vez, 10 hashes para guardar]. */
function gerarCodigosReserva(): array
{
    $codigos = [];
    while (count($codigos) < RESERVAS_QTD) {
        $c = '';
        for ($i = 0; $i < 8; $i++) $c .= ALFABETO_CODIGO[random_int(0, strlen(ALFABETO_CODIGO) - 1)];
        if (!in_array($c, $codigos, true)) $codigos[] = $c;
    }
    $visiveis = [];
    $hashes = [];
    foreach ($codigos as $c) {
        $visiveis[] = substr($c, 0, 4) . '-' . substr($c, 4);
        // Custo fixo: o padrão do PHP 8.4 subiu para 12 e conferir 10 hashes ficaria lento
        $hashes[] = password_hash($c, PASSWORD_BCRYPT, ['cost' => 8]);
    }
    return [$visiveis, $hashes];
}

/** Maiúsculas, sem espaços e hífens; 8 do alfabeto ou null. */
function normalizarCodigoReserva(string $s): ?string
{
    $c = strtoupper(preg_replace('/[\s-]+/', '', $s) ?? '');
    return preg_match(RE_CODIGO_RESERVA, $c) ? $c : null;
}

/** O hash que bate com o código (normalizado), ou null. */
function acharReserva(array $hashes, string $norm): ?string
{
    foreach ($hashes as $h) {
        if (is_string($h) && password_verify($norm, $h)) return $h;
    }
    return null;
}

/**
 * Na trava de usuários: confere de novo que a verificação é a mesma ($id) e que o código ainda não foi
 * usado, e grava — o passo do aplicativo ($tipo 'app', $valor = passo) ou tira a reserva ($tipo 'reserva',
 * $valor = hash). $extra(array &$u) muda mais campos na mesma gravação (último acesso, desligar).
 * Devolve ['ok', usuário como ficou] ou ['usado' | 'mudou', null] (nada gravado).
 */
function consumirCodigo2fa(string $login, string $id, string $tipo, int|string $valor, ?callable $extra = null): array
{
    try {
        $u = alterarUsuarios(static function (array &$l) use ($login, $id, $tipo, $valor, $extra): array {
            $i = acharUsuario($l, $login);
            $df = $i === null ? null : $l[$i]['dois_fatores'];
            if ($i === null || !$l[$i]['ativo'] || !is_array($df) || !empty($df['danificada']) || !hash_equals($df['id'], $id)) {
                throw new ErroCodigo2fa('mudou');
            }
            if ($tipo === 'app') {
                if ((int) $valor <= $df['passo'] && $df['passo'] <= passoTotp() + 1) throw new ErroCodigo2fa('usado');
                $l[$i]['dois_fatores']['passo'] = (int) $valor;
            } else {
                $k = array_search($valor, $df['reservas'], true);
                if ($k === false) throw new ErroCodigo2fa('usado');
                array_splice($l[$i]['dois_fatores']['reservas'], (int) $k, 1);
            }
            if ($extra) $extra($l[$i]);
            return $l[$i];
        });
        return ['ok', $u];
    } catch (ErroCodigo2fa $e) {
        return [$e->getMessage(), null];
    }
}

/* ------------------------------------------------------------------
   Mensagens
   ------------------------------------------------------------------ */
function horaPainel(): string
{
    return date('H:i');
}

function minutosEspera2fa(): int
{
    return max(1, intdiv((int) (cfg('dois_fatores_espera') ?? 300), 60));
}

function msgRelogio2fa(array $res): string
{
    $min = $res['minutos'] . ($res['minutos'] === 1 ? ' minuto' : ' minutos');
    $atras = $res['sentido'] === 'atrasada';
    return 'O código confere com a hora de uns ' . $min . ' ' . ($atras ? 'atrás' : 'à frente') . ': a hora do celular parece estar '
        . $res['sentido'] . '. No celular, ligue a hora automática (Ajustes → Data e hora) e digite o código novo. Hora do painel agora: ' . horaPainel() . '.';
}

function msgCodigoErrado2fa(): string
{
    return 'Código incorreto. Digite o código que está aparecendo agora no aplicativo (ele muda a cada 30 segundos) e confira se a hora do celular está automática. Hora do painel agora: ' . horaPainel() . '.';
}

/**
 * Código do aplicativo que não entrou, na 2ª etapa e ao desligar ($errado: o texto de código incorreto).
 * A hora do celular só é comentada quando ele está ATRASADO: um palpite que bate com um passo futuro
 * voltaria a valer dali a pouco, então "adiantada" responde como código incorreto (e fica só no registro).
 * Na tela de ligar (a pessoa já tem a chave) o diagnóstico continua completo (msgRelogio2fa).
 */
function msgCodigoApp2fa(array $res, string $errado): string
{
    if (($res['motivo'] ?? '') === 'usado') return MSG_2FA_USADO;
    if (($res['motivo'] ?? '') === 'relogio' && ($res['sentido'] ?? '') === 'atrasada') return msgRelogio2fa($res);
    return $errado;
}

/** A mensagem de quem já estava bloqueado (a conta, em qualquer internet, ou este usuário nesta internet). */
function msgBloqueado2fa(string $login): string
{
    if ($s = bloqueioConta($login)) return msgBloqueioConta($s);
    return msgBloqueio(bloqueioRestante($login) ?: (int) cfg('bloqueio'), $login);
}

const MSG_2FA_USADO = 'Esse código já foi usado. Espere o aplicativo mostrar o próximo (muda a cada 30 segundos) e digite o novo.';
const MSG_2FA_RESERVA = 'Código de reserva incorreto ou já usado. Confira as letras (o painel não usa 0, O, 1, I nem L).';
const MSG_2FA_DANIFICADA = 'A verificação em duas etapas deste usuário está com problema no servidor. Peça a um administrador para desligar (Conta → Usuários) ou use a recuperação de acesso (Esqueci a senha).';
const MSG_2FA_FORMATO_APP = 'Digite os 6 números do código (só números).';
const MSG_2FA_FORMATO_RESERVA = 'O código de reserva tem 8 letras e números (ex.: K7QM-2XPA).';
const MSG_2FA_JA_LIGADA = 'A verificação em duas etapas já tinha sido ligada (talvez em outro aparelho). Se o aplicativo não mostra o código certo, desligue e ligue de novo.';
const MSG_2FA_EXIGIR_SEM = 'Ligue primeiro a sua verificação em duas etapas (Conta → Verificação em duas etapas). Depois volte aqui para exigir de todos.';

/** Detalhe do registro para um código errado (nunca o código digitado). */
function detalheCodigoErrado(string $tipo, array $res = []): string
{
    if ($tipo === 'reserva') return 'código de reserva';
    if (($res['motivo'] ?? '') === 'usado') return 'código já usado';
    if (($res['motivo'] ?? '') === 'relogio') return 'hora do celular ' . $res['sentido'] . ' uns ' . $res['minutos'] . ' min';
    return 'código do aplicativo';
}

/**
 * Código errado (2ª etapa, desligar), já contado por reservarTentativaCodigo() antes de conferir: na chave
 * internet + usuário de hoje (5) e nas da conta (10 em 15 min e 30 em 24 h, somando todas as internets). $restam: o que ela devolveu.
 * Devolve [mensagem com o "Restam…", bloqueou?]. Registra o bloqueio.
 */
function falhaCodigo2fa(string $login, string $base, int $restam): array
{
    usleep(random_int(250000, 600000));   // atrasa quem tenta adivinhar
    if ($restam > 0) return [msgFalha($base, $restam, $login, restamNoDia($login) <= $restam ? tempoBloqueio(JANELA_CODIGOS_DIA) : '15 minutos'), false];
    if ($s = bloqueioConta($login)) {
        $dia = $s > (int) cfg('bloqueio');   // o teto de 24 horas (30 códigos), não o de 15 minutos (10)
        registrar('bloqueado', 'entrada de ' . $login . ' bloqueada por ' . ($dia
            ? tempoBloqueio(JANELA_CODIGOS_DIA) . ' (' . maxCodigosContaDia() . ' códigos errados em um dia)'
            : intdiv((int) cfg('bloqueio'), 60) . ' minutos (' . maxCodigosConta() . ' códigos errados)'), $login);
        return [msgBloqueioConta($s), true];
    }
    registrar('bloqueado', $login . ' bloqueado nesta internet por ' . intdiv((int) cfg('bloqueio'), 60) . ' minutos (tentativas erradas)', $login);
    return [msgFalha($base, 0, $login), true];
}

/* ------------------------------------------------------------------
   Entrada: senha certa → código (estado pendente na sessão)
   ------------------------------------------------------------------ */
/** O login esperando o código, ou null (venceu: guarda o motivo para a mensagem). */
function pendente2fa(): ?array
{
    $p = $_SESSION['entrada_2fa'] ?? null;
    if (!is_array($p) || !is_string($p['usuario'] ?? null)) return null;
    if (time() - (int) ($p['desde'] ?? 0) > (int) (cfg('dois_fatores_espera') ?? 300)) {
        unset($_SESSION['entrada_2fa']);
        $_SESSION['motivo_2fa'] = 'expirou';
        return null;
    }
    return $p;
}

/** Depois da senha certa, com a verificação ligada: novo ID de sessão, ninguém logado ainda. */
function abrirPendente2fa(array $u): void
{
    session_regenerate_id(true);
    $_SESSION = [
        // senha_em + impressão da senha: a senha trocada no meio (mesmo no mesmo segundo) manda de volta para a senha
        'entrada_2fa' => ['usuario' => $u['usuario'], 'senha_em' => $u['senha_alterada'], 'impressao' => impressaoSenha($u['hash']), 'desde' => time(), 'voltar' => null],
        'csrf'        => bin2hex(random_bytes(32)),
    ];
}

/** Abre a sessão (novo ID, CSRF novo) e registra como entrou. $restam: entrou com código de reserva. */
function concluirEntrada(array $u, string $como, ?int $restam = null): void
{
    abrirSessaoDe($u['usuario'], $u['hash']);
    if (exigirDoisFatores() && !doisFatoresLigado($u)) $_SESSION['configurar_2fa'] = true;
    if ($restam !== null) registrar('entrou-reserva', 'restam ' . $restam);
    else registrar('entrou', $como);
}

/**
 * 2ª etapa da entrada. Devolve ['erro' => ?string, 'senha' => volta para a senha?, 'status' => int,
 * 'aviso' => ?[msg, tipo], 'voltar' => ?string].
 */
function verificarCodigoEntrada(string $codigo, string $modo, bool $lembrar): array
{
    $r = ['erro' => null, 'senha' => false, 'status' => 200, 'aviso' => null, 'voltar' => null];
    $p = pendente2fa();
    if ($p === null) {
        unset($_SESSION['motivo_2fa']);   // a mensagem vai agora (não aparece de novo na próxima página)
        return ['erro' => 'Passaram mais de ' . minutosEspera2fa() . ' minutos. Por segurança, digite a senha de novo.', 'senha' => true, 'status' => 401] + $r;
    }
    $login = $p['usuario'];
    $voltar = is_string($p['voltar'] ?? null) ? $p['voltar'] : null;
    $paraSenha = static function (string $msg, int $status) use ($r): array {
        unset($_SESSION['entrada_2fa']);
        return ['erro' => $msg, 'senha' => true, 'status' => $status] + $r;
    };
    if ($s = bloqueioConta($login)) return $paraSenha(msgBloqueioConta($s), 429);
    if ($s = bloqueioRestante($login)) return $paraSenha(msgBloqueio($s, $login), 429);
    $u = usuarioPorLogin($login);
    if ($u === null) return $paraSenha('Seu usuário foi excluído do painel. Fale com um administrador.', 401);
    if (!$u['ativo']) return $paraSenha('Este acesso está desativado. Fale com um administrador do painel.', 401);
    if ($u['senha_alterada'] !== (int) ($p['senha_em'] ?? 0) || !hash_equals(impressaoSenha($u['hash']), (string) ($p['impressao'] ?? ''))) return $paraSenha('A senha deste usuário foi trocada. Entre de novo com a senha nova.', 401);
    if (!doisFatoresLigado($u)) return $paraSenha('A verificação em duas etapas desta conta foi desligada por um administrador. Entre de novo só com a senha.', 401);
    if (doisFatoresDanificada($u)) return ['erro' => MSG_2FA_DANIFICADA, 'status' => 409] + $r;

    $c = preg_replace('/[\s-]+/', '', cortar($codigo, 20)) ?? '';
    $app = preg_match('/^\d{6}$/', $c) === 1;
    $norm = $app ? null : normalizarCodigoReserva($c);
    if (!$app && $norm === null) {
        return ['erro' => $modo === 'reserva' ? MSG_2FA_FORMATO_RESERVA : MSG_2FA_FORMATO_APP, 'status' => 422] + $r;   // não conta
    }
    // Conta como erro antes de conferir (pedidos em paralelo não passam juntos do limite); acertou → devolve
    $restam = reservarTentativaCodigo($login);
    if ($restam < 0) return $paraSenha(msgBloqueado2fa($login), 429);
    $df = $u['dois_fatores'];
    $acesso = static function (array &$x): void { $x['ultimo_acesso'] = time(); };
    $res = [];
    $st = null;
    if ($app) {
        $res = conferirCodigoApp($df, $c);
        if ($res['motivo'] === 'ok') [$st, $novo] = consumirCodigo2fa($login, $df['id'], 'app', (int) $res['passo'], $acesso);
        if ($st === 'usado') $res['motivo'] = 'usado';
    } elseif (($h = acharReserva($df['reservas'], $norm)) !== null) {
        [$st, $novo] = consumirCodigo2fa($login, $df['id'], 'reserva', $h, $acesso);
    }
    if ($st === 'ok' || $st === 'mudou') devolverTentativaCodigo($login);   // o código estava certo
    if ($st === 'mudou') return $paraSenha('A verificação em duas etapas desta conta mudou. Entre de novo.', 401);
    if ($st === 'ok') {
        $n = $app ? null : count($novo['dois_fatores']['reservas']);
        concluirEntrada($novo, $app ? 'com o código do aplicativo' : 'com um código de reserva', $n);
        avisarCodigosErrados($login, $u['ultimo_acesso'], (int) ($p['erros'] ?? 0));
        if ($lembrar) lembrarAparelho($novo);
        return ['voltar' => $voltar, 'aviso' => $n === null ? null : avisoReserva($n)] + $r;
    }
    registrar('codigo-app-errado', detalheCodigoErrado($app ? 'app' : 'reserva', $res), $login);
    $_SESSION['entrada_2fa']['erros'] = (int) ($p['erros'] ?? 0) + 1;   // os desta entrada não entram no aviso de quando ela acertar
    [$msg, $bloqueou] = falhaCodigo2fa($login, $app ? msgCodigoApp2fa($res, msgCodigoErrado2fa()) : MSG_2FA_RESERVA, $restam);
    if ($bloqueou) return $paraSenha($msg, 429);
    return ['erro' => $msg, 'status' => 401] + $r;
}

/**
 * Ao entrar (código certo ou aparelho lembrado): os códigos errados da 2ª etapa com este usuário desde o último acesso
 * ($desde; o registro tem segundos: conta também o mesmo segundo, em que os erros da pessoa antes de entrar não caem),
 * tirando os desta mesma entrada ($meus). Só chega ao código quem acerta a senha, então eles mostram que
 * alguém tem a senha. Lidos do registro (Atividades), que só o administrador vê; os de "desligar" ficam de fora
 * (pedem uma sessão aberta: em geral, o próprio dono errando).
 */
function avisarCodigosErrados(string $login, ?int $desde, int $meus = 0): void
{
    $n = -$meus;
    foreach (ultimasAtividades(5000, true) as $a) {
        if ($a['quando'] >= (int) $desde && $a['evento'] === 'codigo-app-errado' && strcasecmp($a['usuario'], $login) === 0
            && !str_contains($a['detalhe'], 'ao desligar')) $n++;
    }
    if ($n <= 0) return;
    avisar('Atenção: desde o seu último acesso, ' . ($n === 1 ? 'houve 1 código errado' : 'houve ' . $n . ' códigos errados')
        . ' da verificação em duas etapas com o seu usuário. Só chega ao código quem acerta a senha: se não foi você, troque a senha agora em Conta → Trocar senha.', 'erro');
}

/** [mensagem, tipo] depois de entrar com um código de reserva. */
function avisoReserva(int $restam): array
{
    if ($restam === 0) return ['Você usou o último código de reserva. Gere códigos novos agora em Conta → Verificação em duas etapas: sem eles, se perder o celular, só um administrador consegue liberar a sua entrada.', 'erro'];
    if ($restam <= 3) return ['Você entrou com um código de reserva. Restam ' . $restam . ': gere códigos novos em Conta → Verificação em duas etapas.', 'erro'];
    return ['Você entrou com um código de reserva. Restam ' . $restam . '.', 'info'];
}

/* ------------------------------------------------------------------
   "Lembrar este aparelho por 30 dias" (aparelhos.php: só o hash do cookie)
   ------------------------------------------------------------------ */
function lerAparelhos(): array
{
    $d = lerJsonPrivado('aparelhos.php');
    if (!is_array($d) || !is_array($d['aparelhos'] ?? null)) return [];   // ausente/ilegível: todos voltam a pedir o código
    $out = [];
    foreach ($d['aparelhos'] as $a) {
        if (!is_array($a) || !is_string($a['usuario'] ?? null) || !preg_match(RE_USUARIO, $a['usuario'])
            || !is_string($a['h'] ?? null) || !preg_match('/^[0-9a-f]{64}$/', $a['h']) || !is_string($a['df'] ?? null)
            || !is_int($a['criado'] ?? null) || !is_int($a['vence'] ?? null) || !is_int($a['senha_em'] ?? null)) continue;
        $out[] = ['usuario' => $a['usuario'], 'h' => $a['h'], 'rotulo' => is_string($a['rotulo'] ?? null) ? cortar($a['rotulo'], 60) : 'Navegador',
            'criado' => $a['criado'], 'vence' => $a['vence'], 'uso' => is_int($a['uso'] ?? null) ? $a['uso'] : $a['criado'],
            'df' => $a['df'], 'senha_em' => $a['senha_em']];
    }
    return $out;
}

/** A entrada vale para este usuário agora? (verificação, senha e validade iguais às de quando lembrou) */
function aparelhoVale(array $a, array $u): bool
{
    $df = $u['dois_fatores'] ?? null;
    return strcasecmp($a['usuario'], $u['usuario']) === 0 && $u['ativo'] && is_array($df) && empty($df['danificada'])
        && hash_equals($df['id'], $a['df']) && $a['senha_em'] === $u['senha_alterada'] && $a['vence'] > time();
}

/** Só dentro de comTrava('aparelhos'). Limpa o que não vale mais; no máximo 10 por usuário e 200 no total. */
function gravarAparelhos(array $lista): void
{
    $usuarios = [];
    foreach (lerUsuarios() as $u) $usuarios[strtolower($u['usuario'])] = $u;
    $lista = array_filter($lista, static fn(array $a): bool => isset($usuarios[strtolower($a['usuario'])]) && aparelhoVale($a, $usuarios[strtolower($a['usuario'])]));
    usort($lista, static fn(array $a, array $b): int => $b['criado'] <=> $a['criado']);   // mais novos primeiro (a ordem é estável)
    $porUsuario = [];
    $out = [];
    foreach ($lista as $a) {
        $k = strtolower($a['usuario']);
        if (($porUsuario[$k] ?? 0) >= APARELHOS_POR_USUARIO || count($out) >= APARELHOS_MAX) continue;
        $porUsuario[$k] = ($porUsuario[$k] ?? 0) + 1;
        $out[] = $a;
    }
    gravarJsonPrivado('aparelhos.php', ['versao' => 1, 'aparelhos' => $out]);
}

/** O token do cookie bc_aparelho, se tiver o formato certo. */
function tokenDoCookie(): ?string
{
    $t = $_COOKIE[COOKIE_APARELHO] ?? null;
    return is_string($t) && preg_match('/^[A-Za-z0-9_-]{43}$/', $t) ? $t : null;
}

function aparelhoValido(array $u): bool
{
    $t = tokenDoCookie();
    if ($t === null || !doisFatoresOk($u)) return false;
    $h = hash('sha256', $t);
    $achou = null;
    foreach (lerAparelhos() as $a) {
        if (hash_equals($a['h'], $h) && aparelhoVale($a, $u)) $achou = $a;
    }
    if ($achou === null) return false;
    if (time() - $achou['uso'] >= 3600) {   // "uso" no máximo 1 vez por hora (não grava a cada entrada)
        comTrava('aparelhos', static function () use ($u, $h): void {
            $lista = lerAparelhos();
            foreach ($lista as &$a) {
                if (strcasecmp($a['usuario'], $u['usuario']) === 0 && hash_equals($a['h'], $h)) $a['uso'] = time();
            }
            unset($a);
            gravarAparelhos($lista);
        });
    }
    return true;
}

function lembrarAparelho(array $u): void
{
    if (!doisFatoresOk($u)) return;
    // Sempre um token novo: um valor que outra pessoa pôs neste navegador (ou já conhecia) nunca passa a valer
    $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $h = hash('sha256', $token);
    $velho = tokenDoCookie();
    $hv = $velho !== null ? hash('sha256', $velho) : '';
    $dias = max(1, (int) (cfg('aparelho_dias') ?? 30));
    $agora = time();
    $rotulo = rotuloAparelho((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    comTrava('aparelhos', static function () use ($u, $h, $hv, $dias, $agora, $rotulo): void {
        $lista = [];
        foreach (lerAparelhos() as $a) {
            if ($hv !== '' && hash_equals($a['h'], $hv)) {
                if (strcasecmp($a['usuario'], $u['usuario']) === 0) continue;   // o "lembrar" de antes desta pessoa: vale o novo
                $a['h'] = $h;   // outra pessoa lembrada neste navegador continua lembrada (um cookie por navegador)
            }
            $lista[] = $a;
        }
        // O novo vai na frente: a lista fica dos mais novos para os mais antigos (mesmo no mesmo segundo)
        array_unshift($lista, ['usuario' => $u['usuario'], 'h' => $h, 'rotulo' => $rotulo, 'criado' => $agora, 'vence' => $agora + $dias * 86400,
            'uso' => $agora, 'df' => $u['dois_fatores']['id'], 'senha_em' => $u['senha_alterada']]);
        gravarAparelhos($lista);
    });
    if (!headers_sent()) {
        setcookie(COOKIE_APARELHO, $token, ['expires' => $agora + $dias * 86400, 'path' => caminhoCookie() . 'index.php',
            'secure' => https(), 'httponly' => true, 'samesite' => 'Strict']);
    }
}

/** Esquece os aparelhos lembrados de $login. Devolve quantos valiam. */
function esquecerAparelhos(string $login): int
{
    if (!@is_file(caminhoPrivado('aparelhos.php'))) return 0;
    return comTrava('aparelhos', static function () use ($login): int {
        $lista = lerAparelhos();
        $u = usuarioPorLogin($login);
        $n = 0;
        $fica = [];
        foreach ($lista as $a) {
            if (strcasecmp($a['usuario'], $login) !== 0) { $fica[] = $a; continue; }
            if ($u === null || aparelhoVale($a, $u)) $n++;
        }
        gravarAparelhos($fica);
        return $n;
    });
}

/** Os aparelhos lembrados que valem para $u, mais novos primeiro, com 'este' (o cookie deste navegador). */
function aparelhosDe(array $u): array
{
    $t = tokenDoCookie();
    $h = $t !== null ? hash('sha256', $t) : '';
    $out = [];
    foreach (lerAparelhos() as $a) {
        if (aparelhoVale($a, $u)) $out[] = $a + ['este' => $h !== '' && hash_equals($a['h'], $h)];
    }
    usort($out, static fn(array $a, array $b): int => $b['criado'] <=> $a['criado']);
    return $out;
}

/** "Chrome no Android": só de listas fixas (nada do texto do navegador é gravado). */
function rotuloAparelho(string $ua): string
{
    $nav = match (true) {
        str_contains($ua, 'Edg/')                                  => 'Edge',
        str_contains($ua, 'OPR/')                                  => 'Opera',
        str_contains($ua, 'SamsungBrowser')                        => 'Samsung Internet',
        str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS') => 'Firefox',
        str_contains($ua, 'CriOS') || str_contains($ua, 'Chrome/')  => 'Chrome',
        str_contains($ua, 'Safari/')                               => 'Safari',
        default                                                    => 'Navegador',
    };
    $sis = match (true) {
        str_contains($ua, 'iPhone')                                       => 'iPhone',
        str_contains($ua, 'iPad')                                         => 'iPad',
        str_contains($ua, 'Android')                                      => 'Android',
        str_contains($ua, 'Windows')                                      => 'Windows',
        str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS X')   => 'Mac',
        str_contains($ua, 'CrOS')                                         => 'Chromebook',
        str_contains($ua, 'Linux')                                        => 'Linux',
        default                                                           => 'aparelho',
    };
    return $nav . ' no ' . $sis;
}

/* ------------------------------------------------------------------
   "Exigir de todos" (seguranca.php)
   ------------------------------------------------------------------ */
/** ['exigir' => bool, 'desde' => ?int, 'por' => ?string, 'ilegivel' => bool]. Ilegível = exige (falha fechada). */
function lerSeguranca(): array
{
    if (!@is_file(caminhoPrivado('seguranca.php'))) return ['exigir' => false, 'desde' => null, 'por' => null, 'ilegivel' => false];
    $d = lerJsonPrivado('seguranca.php');
    if (!is_array($d) || !is_bool($d['exigir_dois_fatores'] ?? null)) return ['exigir' => true, 'desde' => null, 'por' => null, 'ilegivel' => true];
    return ['exigir' => $d['exigir_dois_fatores'], 'desde' => is_int($d['desde'] ?? null) ? $d['desde'] : null,
        'por' => is_string($d['por'] ?? null) ? $d['por'] : null, 'ilegivel' => false];
}

function exigirDoisFatores(): bool
{
    return lerSeguranca()['exigir'];
}

/** Entrou com "Exigir de todos" ligado e ainda não configurou (a obrigação vale a partir do login). */
function precisaConfigurar2fa(?array $eu): bool
{
    if (!empty($_SESSION['configurar_2fa']) && !exigirDoisFatores()) unset($_SESSION['configurar_2fa']);   // deixou de exigir: a marca da sessão não volta a valer depois
    return $eu !== null && !empty($_SESSION['configurar_2fa']) && exigirDoisFatores() && !doisFatoresLigado($eu);
}

/* ------------------------------------------------------------------
   Ações (chamadas pelo index.php)
   ------------------------------------------------------------------ */
/** O código do formulário, cortado (nunca array). */
function codigoDoPost(): string
{
    return is_string($_POST['codigo'] ?? null) ? cortar($_POST['codigo'], 20) : '';
}

/** O segredo da configuração (antes de confirmar): fica só na sessão. */
function segredoNovo2fa(bool $trocar = false): string
{
    $n = $_SESSION['novo_2fa'] ?? null;
    if ($trocar || !is_array($n) || !is_string($n['segredo'] ?? null) || !preg_match(RE_SEGREDO_2FA, $n['segredo'])) {
        $n = ['segredo' => novoSegredo2fa(), 'criado' => time()];
        $_SESSION['novo_2fa'] = $n;
    }
    return $n['segredo'];
}

function acaoDoisFatores(): void
{
    $eu = usuarioAtual();
    if (!doisFatoresLigado($eu)) segredoNovo2fa();
    telaDoisFatores();
}

function acaoDoisFatoresLigar(): void
{
    $eu = usuarioAtual();
    if (doisFatoresLigado($eu)) throw new ErroUsuario(MSG_2FA_JA_LIGADA);
    $n = $_SESSION['novo_2fa'] ?? null;
    if (!is_array($n) || !is_string($n['segredo'] ?? null) || !preg_match(RE_SEGREDO_2FA, $n['segredo'])) {
        segredoNovo2fa(true);
        http_response_code(409);   // o erro vai no passo 3, onde a pessoa está (o formulário abre a página ali: #df-ligar)
        telaDoisFatores('A página ficou aberta tempo demais e o código QR mudou. Apague no aplicativo a conta “Balanças.com” que você acabou de criar e leia o QR novo.', 'ligar');
        return;
    }
    // A senha atual (menos na tela obrigatória, que vem logo depois da senha): com uma sessão que ficou aberta, e sem a
    // senha, ninguém liga a verificação de outra pessoa com o próprio celular (e a tranca para fora)
    if (!precisaConfigurar2fa($eu)) {
        $login = $eu['usuario'];
        if ($s = bloqueioRestante($login)) sairSeBloqueado(msgBloqueio($s, $login), $login);
        if (conferirSenhaAtual2fa($eu, 'ligar-senha', 'senha atual errada ao ligar a verificação') !== null) return;
    }
    $segredo = $n['segredo'];
    $res = conferirCodigoApp(['segredo' => $segredo, 'passo' => 0], codigoDoPost());
    if ($res['motivo'] !== 'ok') {
        $erro = match ($res['motivo']) {
            'formato' => MSG_2FA_FORMATO_APP,
            'relogio' => msgRelogio2fa($res),
            default   => 'Esse código não confere. Confira se digitou o código de “Balanças.com” que está aparecendo agora (muda a cada 30 segundos) e se a hora do celular está automática (Ajustes → Data e hora). Hora do painel agora: ' . horaPainel() . '.',
        };
        http_response_code(422);
        telaDoisFatores($erro, 'ligar');
        return;
    }
    [$codigos, $hashes] = gerarCodigosReserva();
    $agora = time();
    alterarUsuarios(static function (array &$l) use ($segredo, $hashes, $agora, $res): void {
        $i = acharUsuario($l, (string) usuarioLogado());
        if ($i === null) throw new ErroUsuario('Seu usuário não existe mais. Fale com um administrador.');
        if ($l[$i]['dois_fatores'] !== null) throw new ErroUsuario(MSG_2FA_JA_LIGADA);
        $l[$i]['dois_fatores'] = ['segredo' => $segredo, 'id' => bin2hex(random_bytes(8)), 'ligado' => $agora,
            'passo' => (int) $res['passo'], 'reservas' => $hashes, 'reservas_em' => $agora];
    });
    $depois = destinoSeguro2fa($_SESSION['configurar_voltar'] ?? null) ?? (!empty($_SESSION['configurar_2fa']) ? 'index.php' : link_('dois-fatores'));
    unset($_SESSION['novo_2fa'], $_SESSION['configurar_2fa'], $_SESSION['configurar_voltar']);
    $_SESSION['reservas_2fa'] = ['codigos' => $codigos, 'novos' => false, 'quando' => $agora, 'depois' => $depois];
    registrar('dois-fatores-ligou');
    // Curto: fica acima dos códigos. Como testar vem depois deles (telaCodigosReserva), para ninguém sair antes de guardar
    avisar('Pronto! A verificação em duas etapas está ligada. Agora guarde os códigos de reserva abaixo.');
    redirecionar(link_('dois-fatores-reservas'));
}

/** Endereço interno para voltar (o mesmo filtro do index.php), ou null. */
function destinoSeguro2fa($url): ?string
{
    return function_exists('destinoSeguro') ? destinoSeguro($url) : null;
}

function acaoDoisFatoresRecomecar(): void
{
    if (doisFatoresLigado(usuarioAtual())) redirecionar(link_('dois-fatores'));
    segredoNovo2fa(true);
    avisar('Código QR novo criado. Se você já tinha lido o anterior, apague no aplicativo a conta “Balanças.com” que não funciona.', 'info');
    redirecionar(link_('dois-fatores'));
}

/**
 * Os códigos de reserva aparecem uma vez só: ficam na sessão enquanto esta página está aberta (recarregar ou voltar
 * de outro aplicativo no celular mostra de novo, por até 15 min); abrir qualquer outra página os apaga (index.php).
 */
function acaoDoisFatoresReservas(): void
{
    $f = $_SESSION['reservas_2fa'] ?? null;
    if (!is_array($f) || !is_array($f['codigos'] ?? null) || time() - (int) ($f['quando'] ?? 0) > 900) {
        unset($_SESSION['reservas_2fa']);
        avisar('Os códigos de reserva aparecem uma vez só. Se eles se perderam, gere códigos novos aqui.', 'info');
        redirecionar(link_('dois-fatores'));
    }
    telaCodigosReserva($f);
}

/** Confere a senha atual (ligar, gerar códigos novos, desligar). null = certa; senão a mensagem (e, se bloqueou, já saiu). */
function conferirSenhaAtual2fa(array $eu, string $onde, string $detalhe): ?string
{
    $login = $eu['usuario'];
    $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';
    if ($senha !== '' && strlen($senha) <= 200 && password_verify($senha, $eu['hash'])) return null;
    $restam = registrarFalha($login);
    registrar('senha-errada', $detalhe);
    $msg = msgFalha('A senha atual não confere.', $restam, $login);
    sairSeBloqueado($msg, $login);
    http_response_code(422);
    telaDoisFatores($msg, $onde);
    return $msg;
}

/**
 * Bloqueado nesta internet (senha ou código errado demais): sai do painel e volta para a tela de entrar, como na
 * troca de senha. O bloqueio da conta (códigos errados em qualquer internet) não tira ninguém de uma sessão já
 * aberta: quem errou pode ter sido outra pessoa com a senha, e o dono precisa continuar para trocá-la.
 */
function sairSeBloqueado(string $msg, string $login): void
{
    if (bloqueioRestante($login) > 0) {
        http_response_code(429);
        sair();
        avisar($msg, 'erro');
        redirecionar(link_('entrar'));
    }
}

function acaoDoisFatoresNovasReservas(): void
{
    $eu = usuarioAtual();
    $login = $eu['usuario'];
    if ($s = bloqueioRestante($login)) sairSeBloqueado(msgBloqueio($s, $login), $login);
    if (!doisFatoresOk($eu)) {
        avisar(doisFatoresDanificada($eu) ? MSG_2FA_DANIFICADA : 'A verificação em duas etapas está desligada: ligue para ter códigos de reserva.', 'info');
        redirecionar(link_('dois-fatores'));
    }
    if (conferirSenhaAtual2fa($eu, 'reservas', 'senha atual errada ao gerar códigos de reserva') !== null) return;
    [$codigos, $hashes] = gerarCodigosReserva();
    $agora = time();
    $id = $eu['dois_fatores']['id'];
    alterarUsuarios(static function (array &$l) use ($login, $id, $hashes, $agora): void {
        $i = acharUsuario($l, $login);
        $df = $i === null ? null : $l[$i]['dois_fatores'];
        if (!is_array($df) || !empty($df['danificada']) || $df['id'] !== $id) throw new ErroUsuario('A verificação em duas etapas mudou (talvez em outro aparelho). Confira e tente de novo.');
        $l[$i]['dois_fatores']['reservas'] = $hashes;
        $l[$i]['dois_fatores']['reservas_em'] = $agora;
    });
    $_SESSION['reservas_2fa'] = ['codigos' => $codigos, 'novos' => true, 'quando' => $agora, 'depois' => link_('dois-fatores')];
    registrar('dois-fatores-reservas');
    redirecionar(link_('dois-fatores-reservas'));
}

function acaoDoisFatoresDesligar(): void
{
    $eu = usuarioAtual();
    $login = $eu['usuario'];
    if ($s = bloqueioRestante($login)) sairSeBloqueado(msgBloqueio($s, $login), $login);
    if (!doisFatoresLigado($eu)) {
        avisar('A verificação em duas etapas já estava desligada.', 'info');
        redirecionar(link_('conta'));
    }
    if (conferirSenhaAtual2fa($eu, 'desligar', 'senha atual errada ao desligar a verificação') !== null) return;
    if (doisFatoresDanificada($eu)) {
        http_response_code(409);
        telaDoisFatores(MSG_2FA_DANIFICADA, 'desligar');
        return;
    }
    $df = $eu['dois_fatores'];
    $c = preg_replace('/[\s-]+/', '', codigoDoPost()) ?? '';
    $naoConfere = 'O código não confere (nem do aplicativo, nem de reserva).';
    $app = preg_match('/^\d{6}$/', $c) === 1;
    $norm = $app ? null : normalizarCodigoReserva($c);
    if (!$app && $norm === null) {
        http_response_code(422);
        telaDoisFatores($naoConfere, 'desligar');   // formato: não conta
        return;
    }
    // Conta como erro antes de conferir (como na 2ª etapa da entrada); acertou → devolve
    $restam = reservarTentativaCodigo($login);
    if ($restam < 0) {
        $msg = msgBloqueado2fa($login);
        sairSeBloqueado($msg, $login);   // bloqueado nesta internet: sai
        http_response_code(429);          // só a conta (pode ter sido outra pessoa): recusa o código, a sessão continua
        telaDoisFatores($msg, 'desligar');
        return;
    }
    $desligar = static function (array &$x): void { $x['dois_fatores'] = null; };
    $st = null;
    $res = [];
    if ($app) {
        $res = conferirCodigoApp($df, $c);
        if ($res['motivo'] === 'ok') [$st] = consumirCodigo2fa($login, $df['id'], 'app', (int) $res['passo'], $desligar);
        if ($st === 'usado') $res['motivo'] = 'usado';
    } elseif (($h = acharReserva($df['reservas'], $norm)) !== null) {
        [$st] = consumirCodigo2fa($login, $df['id'], 'reserva', $h, $desligar);
    }
    if ($st === 'ok' || $st === 'mudou') devolverTentativaCodigo($login);   // o código estava certo
    if ($st === 'mudou') throw new ErroUsuario('A verificação em duas etapas mudou (talvez em outro aparelho). Confira e tente de novo.');
    if ($st !== 'ok') {
        $base = $app ? msgCodigoApp2fa($res, $naoConfere) : $naoConfere;
        registrar('codigo-app-errado', detalheCodigoErrado($app ? 'app' : 'reserva', $res) . ' (ao desligar a verificação)', $login);
        [$msg] = falhaCodigo2fa($login, $base, $restam);
        sairSeBloqueado($msg, $login);
        http_response_code(422);
        telaDoisFatores($msg, 'desligar');
        return;
    }
    esquecerAparelhos($login);
    registrar('dois-fatores-desligou');
    if (exigirDoisFatores()) {
        $_SESSION['configurar_2fa'] = true;
        segredoNovo2fa(true);
        avisar('A verificação em duas etapas foi desligada. Como o administrador exige de todos, configure de novo agora (com o celular novo, se trocou).', 'info');
        redirecionar(link_('dois-fatores'));
    }
    avisar('A verificação em duas etapas foi desligada e os aparelhos lembrados foram esquecidos. Para ligar de novo: Conta → Verificação em duas etapas.');
    redirecionar(link_('conta'));
}

function acaoAparelhosEsquecer(): void
{
    $n = esquecerAparelhos((string) usuarioLogado());
    if ($n > 0) {
        registrar('aparelhos-esquecidos', $n . ($n === 1 ? ' aparelho' : ' aparelhos'));
        avisar($n === 1 ? 'Pronto: 1 aparelho esquecido. Nele, o painel volta a pedir o código do aplicativo.'
                        : 'Pronto: ' . $n . ' aparelhos esquecidos. Neles, o painel volta a pedir o código do aplicativo.');
    } else {
        avisar('Nenhum aparelho estava lembrado.', 'info');
    }
    redirecionar(link_('dois-fatores'));
}

/* ------------------------------------------------------------------
   Administrador: desligar a de outra pessoa, exigir de todos
   ------------------------------------------------------------------ */
function textoDesligar2fa(array $u): string
{
    return 'Desligar a verificação em duas etapas de ' . rotuloUsuario($u) . '? Use quando a pessoa perdeu o celular. Ela passa a entrar só com a senha '
        . 'e os aparelhos lembrados dela são esquecidos. Depois, ela liga de novo em Conta → Verificação em duas etapas.'
        . (bloqueioConta((string) $u['usuario'])
            ? ' Atenção: a entrada dela está bloqueada por códigos errados, então alguém pode estar com a senha dela. Depois de desligar, use Redefinir senha (em Usuários → Editar) para trocar a senha.'
            : ' Se alguém pode estar com a senha dela (celular roubado, códigos errados que não foram dela), use também Redefinir senha.')
        . (exigirDoisFatores() ? ' Como a verificação é exigida de todos, o painel pede para ela configurar no próximo acesso.' : '');
}

function acaoUsuarioDoisFatores(bool $post): void
{
    $u = usuarioDaUrl($post ? 'post' : 'get');
    $volta = link_('usuarios') . '#u-' . $u['usuario'];
    if (ehVoce($u)) throw new ErroUsuario('Para desligar a sua verificação, use Conta → Verificação em duas etapas (pede a senha e um código).');
    if (!doisFatoresLigado($u)) {
        avisar($u['nome'] . ' já estava sem a verificação em duas etapas.', 'info');
        redirecionar($volta);
    }
    if (!$post || ($_POST['confirmado'] ?? '') !== '1') {
        telaConfirmar('Desligar a verificação?', textoDesligar2fa($u), link_('usuario-dois-fatores'), ['u' => $u['usuario']], 'Sim, desligar', $volta, 'usuarios');
        return;
    }
    $depois = alterarUsuarios(static function (array &$l) use ($u): array {
        exigirAdminNaLista($l);
        $i = acharUsuario($l, $u['usuario']);
        if ($i === null) throw new ErroUsuario('Esse usuário foi excluído (talvez em outro aparelho).');
        if (ehVoce($l[$i])) throw new ErroUsuario('Para desligar a sua verificação, use Conta → Verificação em duas etapas (pede a senha e um código).');
        $l[$i]['dois_fatores'] = null;
        $l[$i]['alterado'] = time();
        return $l[$i];
    });
    esquecerAparelhos($depois['usuario']);
    registrar('usuario-dois-fatores', 'Desligou a verificação em duas etapas de ' . rotuloUsuario($depois));
    avisar('A verificação em duas etapas de ' . $depois['nome'] . ' foi desligada. A pessoa entra só com a senha até ligar de novo (Conta → Verificação em duas etapas).');
    redirecionar($volta);
}

/** "Lizi Ellen, Rafael e Maurício" */
function nomesComE(array $nomes): string
{
    $nomes = array_values($nomes);
    if (count($nomes) < 2) return $nomes[0] ?? '';
    return implode(', ', array_slice($nomes, 0, -1)) . ' e ' . $nomes[count($nomes) - 1];
}

/** Ativos (com acesso) que ainda não ligaram. */
function semDoisFatores(array $lista): array
{
    return array_values(array_filter($lista, static fn(array $u): bool => $u['ativo'] && !doisFatoresOk($u)));
}

function textoExigir2fa(array $lista): string
{
    $faltam = semDoisFatores($lista);
    return 'Exigir a verificação em duas etapas de todos? ' . ($faltam
        ? 'Quem ainda não tem (' . nomesComE(array_map(static fn(array $u): string => $u['nome'], $faltam)) . ') vai precisar configurar no próximo acesso, antes de usar o painel. Cada pessoa precisa de um celular com um aplicativo autenticador.'
        : 'Todos já usam; quem for criado depois vai precisar configurar no primeiro acesso.');
}

const TEXTO_DEIXAR_EXIGIR = 'Deixar de exigir a verificação em duas etapas? Quem já tem continua usando; quem não tem pode entrar só com a senha.';

function acaoDoisFatoresExigir(bool $post): void
{
    $fonte = $post ? $_POST : $_GET;
    $exigir = ($fonte['valor'] ?? '') === '1';
    $seg = lerSeguranca();
    if ($seg['exigir'] === $exigir && !$seg['ilegivel']) {
        avisar($exigir ? 'A verificação em duas etapas já é exigida de todos.' : 'A verificação em duas etapas já não era exigida.', 'info');
        redirecionar(link_('usuarios'));
    }
    if ($exigir && !doisFatoresOk(usuarioAtual())) throw new ErroUsuario(MSG_2FA_EXIGIR_SEM);
    if (!$post || ($_POST['confirmado'] ?? '') !== '1') {
        telaConfirmar($exigir ? 'Exigir de todos?' : 'Deixar de exigir?', $exigir ? textoExigir2fa(lerUsuarios()) : TEXTO_DEIXAR_EXIGIR,
            link_('dois-fatores-exigir'), ['valor' => $exigir ? '1' : '0'], $exigir ? 'Sim, exigir' : 'Sim, deixar de exigir', link_('usuarios'), 'usuarios');
        return;
    }
    comTrava('seguranca', static function () use ($exigir): void {
        $lista = lerUsuarios();
        exigirAdminNaLista($lista);
        if ($exigir && !doisFatoresOk($lista[acharUsuario($lista, (string) usuarioLogado())])) throw new ErroUsuario(MSG_2FA_EXIGIR_SEM);
        gravarJsonPrivado('seguranca.php', ['versao' => 1, 'exigir_dois_fatores' => $exigir, 'desde' => time(), 'por' => (string) usuarioLogado()]);
    });
    registrar('dois-fatores-exigir', $exigir ? 'Passou a exigir a verificação em duas etapas de todos' : 'Deixou de exigir a verificação em duas etapas');
    avisar($exigir ? 'Pronto: a verificação em duas etapas agora é exigida de todos. Quem ainda não tem configura no próximo acesso.'
                   : 'Pronto: a verificação em duas etapas deixou de ser exigida. Quem já tem continua usando.');
    redirecionar(link_('usuarios'));
}
