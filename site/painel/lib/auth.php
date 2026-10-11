<?php
/* =====================================================================
   Painel Balanças.com — acesso
   Primeiro acesso com código (lido no cPanel), login/logout, limite de
   tentativas por IP, expiração por inatividade, troca de senha.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

// Hash qualquer, usado quando o usuário digitado não existe (o tempo de resposta fica igual)
const HASH_FALSO = '$2y$10$OLarazc4v.EgaQcHzIBPueDFwp5W/B7D4XDW5hgPidHZCJPV6n6au';
const ALFABETO_CODIGO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';   // sem 0/O e 1/I/L, que se confundem
const SENHAS_FRACAS = ['1234567890', '0123456789', '12345678910', '1234567891', '0987654321', 'qwertyuiop',
    'asdfghjkl', 'abcdefghij', 'senha12345', 'senha123456', 'minhasenha', 'balancas123', 'balancas2010',
    'balancas.com', 'balancascom', 'balanca123', 'administrador', 'admin12345', 'password12', 'iloveyou12',
    'rioclaro123', 'mudar12345', 'trocar12345'];
// Exemplos que já apareceram em telas e guias antigos: nunca podem virar senha
const SENHAS_EXEMPLO = ['balancaverdenarua13', 'balancaverdenarua'];

/* ------------------------------------------------------------------ */
function configConta(): ?array
{
    $c = lerJsonPrivado('config.php');
    return (is_array($c) && !empty($c['usuario']) && is_string($c['hash'] ?? null)) ? $c : null;
}

function instalado(): bool
{
    return configConta() !== null;
}

function impressaoSenha(string $hash): string
{
    return substr(hash('sha256', 'bc-painel|' . $hash), 0, 32);
}

function usuarioLogado(): ?string
{
    return isset($_SESSION['usuario']) && is_string($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
}

/** Chamada a cada página: encerra a sessão vencida ou de senha trocada. */
function verificarSessao(): void
{
    if (usuarioLogado() === null) return;
    $conf = configConta();
    $expirou = time() - (int) ($_SESSION['ultimo'] ?? 0) > (int) cfg('inatividade');
    $mudou = !$conf || strcasecmp($conf['usuario'], (string) $_SESSION['usuario']) !== 0
        || !hash_equals(impressaoSenha($conf['hash']), (string) ($_SESSION['impressao'] ?? ''));
    if ($expirou || $mudou) {
        $_SESSION = [];
        session_regenerate_id(true);
        avisar($expirou ? 'Você ficou mais de 2 horas sem usar o painel e saiu automaticamente. Entre de novo.'
                        : 'A senha foi trocada. Entre de novo com a senha nova.', 'info');
        return;
    }
    $_SESSION['ultimo'] = time();
}

function abrirSessaoDe(string $usuario, string $hash): void
{
    session_regenerate_id(true);
    $_SESSION = [
        'usuario'   => $usuario,
        'entrou'    => time(),
        'ultimo'    => time(),
        'impressao' => impressaoSenha($hash),
        'csrf'      => bin2hex(random_bytes(32)),
    ];
}

function sair(): void
{
    registrar('saiu');
    $_SESSION = [];
    session_regenerate_id(true);
}

/* ------------------------------------------------------------------
   Limite de tentativas (por IP, guardado com hash — não grava o IP puro)
   ------------------------------------------------------------------ */
function segredoLocal(): string
{
    $s = lerPrivado('chave.php');
    if ($s !== null && strlen(trim($s)) === 64) return trim($s);
    return comTrava('chave', static function (): string {
        $s = lerPrivado('chave.php');
        if ($s !== null && strlen(trim($s)) === 64) return trim($s);
        $s = bin2hex(random_bytes(32));
        gravarPrivado('chave.php', $s);
        return $s;
    });
}

function chaveIp(): string
{
    return substr(hash_hmac('sha256', ip(), segredoLocal()), 0, 32);
}

/** Segundos que faltam de bloqueio para este IP (0 = liberado). */
function bloqueioRestante(): int
{
    $t = lerJsonPrivado('tentativas.php', []);
    $r = is_array($t) ? ($t[chaveIp()] ?? null) : null;
    $ate = is_array($r) ? (int) ($r['ate'] ?? 0) : 0;
    return $ate > time() ? $ate - time() : 0;
}

/** Conta uma falha; devolve quantas tentativas ainda restam (0 = acabou de bloquear). */
function registrarFalha(): int
{
    $max = (int) cfg('tentativas_max');
    $janela = (int) cfg('bloqueio');
    return comTrava('tentativas', static function () use ($max, $janela): int {
        $t = lerJsonPrivado('tentativas.php', []);
        if (!is_array($t)) $t = [];
        $agora = time();
        foreach ($t as $k => $r) {   // limpa o que já venceu
            if (!is_array($r) || (($r['ate'] ?? 0) < $agora && ($r['desde'] ?? 0) + $janela < $agora)) unset($t[$k]);
        }
        $k = chaveIp();
        $r = $t[$k] ?? ['n' => 0, 'desde' => $agora, 'ate' => 0];
        if ($r['ate'] > $agora) return 0;                          // já bloqueado
        if ($r['ate'] || $r['desde'] + $janela < $agora) $r = ['n' => 0, 'desde' => $agora, 'ate' => 0];
        $r['n']++;
        if ($r['n'] >= $max) $r['ate'] = $agora + $janela;
        $t[$k] = $r;
        gravarJsonPrivado('tentativas.php', $t);
        return max(0, $max - $r['n']);
    });
}

function limparFalhas(): void
{
    comTrava('tentativas', static function (): void {
        $t = lerJsonPrivado('tentativas.php', []);
        if (is_array($t) && isset($t[chaveIp()])) { unset($t[chaveIp()]); gravarJsonPrivado('tentativas.php', $t); }
    });
}

function msgBloqueio(int $segundos): string
{
    $min = max(1, (int) ceil($segundos / 60));
    return 'Muitas tentativas erradas. Por segurança, o acesso a partir desta internet ficou bloqueado por '
        . $min . ($min === 1 ? ' minuto' : ' minutos') . '. Espere e tente de novo.';
}

function msgFalha(string $base, int $restam): string
{
    if ($restam <= 0) return msgBloqueio((int) cfg('bloqueio'));
    return $base . ($restam <= 3 ? ' ' . ($restam === 1 ? 'Resta 1 tentativa' : 'Restam ' . $restam . ' tentativas') . ' antes do bloqueio de 15 minutos.' : '');
}

/* ------------------------------------------------------------------
   Primeiro acesso
   ------------------------------------------------------------------ */
function gerarCodigo(): string
{
    $c = '';
    for ($i = 0; $i < 12; $i++) {
        if ($i && $i % 4 === 0) $c .= '-';
        $c .= ALFABETO_CODIGO[random_int(0, strlen(ALFABETO_CODIGO) - 1)];
    }
    return $c;
}

/** Código de instalação atual (cria um novo se não houver ou se venceu). */
function codigoInstalacao(): array
{
    return comTrava('instalacao', static function (): array {
        $c = lerPrivado('primeiro-acesso.php');
        if ($c !== null && preg_match('/^CODIGO=([A-Z0-9-]{14})$/m', $c, $m) && preg_match('/^CRIADO=(\d+)$/m', $c, $t)
            && (int) $t[1] + (int) cfg('codigo_validade') > time()) {
            return ['codigo' => $m[1], 'criado' => (int) $t[1]];
        }
        $codigo = gerarCodigo();
        $agora = time();
        $vale = quando($agora + (int) cfg('codigo_validade'));
        gravarPrivado('primeiro-acesso.php', implode("\n", [
            '==================================================================',
            ' PAINEL BALANÇAS.COM — CÓDIGO DE PRIMEIRO ACESSO',
            '==================================================================',
            '',
            '   Seu código:   ' . $codigo,
            '',
            ' Digite este código na tela "Primeiro acesso" do painel para criar',
            ' o seu usuário e a sua senha. Ele vale até ' . $vale . '.',
            ' Depois disso, basta abrir o painel de novo que um código novo é',
            ' gerado aqui. Quando o usuário for criado, este arquivo é apagado.',
            '',
            ' Não compartilhe este código: com ele, qualquer pessoa cria a senha.',
            '',
            '------------------------------------------------------------------',
            'CODIGO=' . $codigo,
            'CRIADO=' . $agora,
            '',
        ]));
        registrar('codigo-gerado', 'código de primeiro acesso criado');
        return ['codigo' => $codigo, 'criado' => $agora];
    });
}

function validarUsuario(string $u): ?string
{
    if (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $u)) return 'Usuário: use de 3 a 40 letras sem acento, números, ponto, hífen ou sublinhado (sem espaços).';
    return null;
}

function validarSenhaNova(string $senha, string $repetida, string $usuario): ?string
{
    $min = (int) cfg('senha_minimo');
    if (tamanho($senha) < $min) return 'A senha precisa ter pelo menos ' . $min . ' caracteres. Dica: junte 3 ou 4 palavras que só você saiba, com um número.';
    if (strlen($senha) > 200) return 'Senha longa demais (máximo 200 caracteres).';
    $baixa = strtolower($senha);
    $soLetras = preg_replace('/[^a-z0-9]/', '', strtolower(strtr($senha, ['ç' => 'c', 'Ç' => 'c', 'ã' => 'a', 'á' => 'a', 'à' => 'a', 'â' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u']))) ?? '';
    if (in_array($soLetras, SENHAS_EXEMPLO, true)) return 'Essa é a senha de exemplo de um guia antigo: invente a sua (3 ou 4 palavras que só você saiba, com um número).';
    if (in_array($baixa, SENHAS_FRACAS, true) || preg_match('/^(.)\1+$/', $senha) || preg_match('/^\d+$/', $senha) && count(array_unique(str_split($senha))) < 5) {
        return 'Essa senha é fácil demais de adivinhar. Escolha outra.';
    }
    if ($usuario !== '' && strlen($usuario) >= 3 && str_contains($baixa, strtolower($usuario))) return 'A senha não pode conter o nome de usuário.';
    if (!hash_equals($senha, $repetida)) return 'As duas senhas digitadas não são iguais.';
    return null;
}

/** Cria o usuário. Devolve mensagem de erro, ou null quando deu certo (e já entra). */
function instalar(string $codigo, string $usuario, string $senha, string $repetida): ?string
{
    if ($s = bloqueioRestante()) return msgBloqueio($s);
    $atual = codigoInstalacao();
    $digitado = preg_replace('/[^A-Z0-9]/', '', strtoupper($codigo)) ?? '';
    $certo = str_replace('-', '', $atual['codigo']);
    if ($digitado === '' || !hash_equals($certo, $digitado)) {
        $restam = registrarFalha();
        registrar('codigo-errado', 'tentativa de primeiro acesso com código errado');
        return msgFalha('Código incorreto. Confira no arquivo primeiro-acesso.php (letras maiúsculas, sem espaços).', $restam);
    }
    if ($erro = validarUsuario($usuario)) return $erro;
    if ($erro = validarSenhaNova($senha, $repetida, $usuario)) return $erro;

    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $criou = comTrava('instalacao', static function () use ($usuario, $hash): bool {
        if (instalado()) return false;
        gravarJsonPrivado('config.php', ['usuario' => $usuario, 'hash' => $hash, 'criado' => time(), 'alterado' => time()]);
        @unlink(caminhoPrivado('primeiro-acesso.php'));
        return true;
    });
    if (!$criou) return 'O painel já foi configurado. Entre com o seu usuário e senha.';
    limparFalhas();
    abrirSessaoDe($usuario, $hash);
    registrar('instalou', 'usuário criado: ' . $usuario);
    return null;
}

/* ------------------------------------------------------------------
   Entrar / trocar senha
   ------------------------------------------------------------------ */
function entrar(string $usuario, string $senha): ?string
{
    if ($s = bloqueioRestante()) return msgBloqueio($s);
    $conf = configConta();
    if (!$conf) return 'O painel ainda não foi configurado.';
    $usuarioOk = $usuario !== '' && hash_equals(strtolower($conf['usuario']), strtolower($usuario));
    $senhaOk = password_verify($senha, $usuarioOk ? $conf['hash'] : HASH_FALSO);
    if ($usuarioOk && $senhaOk) {
        limparFalhas();
        $hash = $conf['hash'];
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            comTrava('instalacao', static function () use ($conf, $hash): void {
                gravarJsonPrivado('config.php', ['hash' => $hash] + $conf);
            });
        }
        abrirSessaoDe($conf['usuario'], $hash);
        registrar('entrou');
        return null;
    }
    usleep(random_int(250000, 600000));   // atrasa quem tenta adivinhar
    $restam = registrarFalha();
    registrar('senha-errada', 'usuário digitado: ' . cortar($usuario, 40));
    return msgFalha('Usuário ou senha incorretos.', $restam);
}

function trocarSenha(string $atual, string $nova, string $repetida): ?string
{
    if ($s = bloqueioRestante()) return msgBloqueio($s);
    $conf = configConta();
    if (!$conf || !password_verify($atual, $conf['hash'])) {
        $restam = registrarFalha();
        registrar('senha-errada', 'senha atual errada ao tentar trocar');
        return msgFalha('A senha atual não confere.', $restam);
    }
    if ($erro = validarSenhaNova($nova, $repetida, $conf['usuario'])) return $erro;
    if (hash_equals($atual, $nova)) return 'A senha nova precisa ser diferente da atual.';
    $hash = password_hash($nova, PASSWORD_DEFAULT);
    comTrava('instalacao', static function () use ($conf, $hash): void {
        gravarJsonPrivado('config.php', ['hash' => $hash, 'alterado' => time()] + $conf);
    });
    abrirSessaoDe($conf['usuario'], $hash);   // novo ID de sessão; outros aparelhos saem
    registrar('trocou-senha');
    return null;
}
