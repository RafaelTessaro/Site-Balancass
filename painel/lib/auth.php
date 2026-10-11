<?php
/* =====================================================================
   Painel Balanças.com — acesso
   Primeiro acesso com código (lido no cPanel), login/logout, limite de
   tentativas (por IP + usuário), expiração por inatividade, troca de senha, senha
   criada no primeiro acesso (depois da senha temporária) e "Recuperar
   acesso" pelo arquivo recuperar.txt. Os usuários: lib/usuarios.php.
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
/** Há pelo menos um administrador ativo (depois da conversão do config.php antigo). */
function instalado(): bool
{
    return adminsAtivos(lerUsuarios()) > 0;
}

function impressaoSenha(string $hash): string
{
    return substr(hash('sha256', 'bc-painel|' . $hash), 0, 32);
}

function usuarioLogado(): ?string
{
    return isset($_SESSION['usuario']) && is_string($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
}

/** Por que a sessão terminou (senha redefinida, acesso desativado…), ou null se não se sabe. */
function motivoSaida(): ?string
{
    return is_string($_SESSION['motivo_saida'] ?? null) ? $_SESSION['motivo_saida'] : null;
}

/**
 * Chamada a cada página: encerra a sessão vencida, de senha trocada/redefinida, de usuário
 * desativado ou excluído. A fórmula da "impressão" não muda (a sessão de antes da conversão
 * para usuários continua valendo). O papel nunca fica guardado na sessão: é lido a cada pedido.
 */
function verificarSessao(): void
{
    if (usuarioLogado() === null) return;
    $u = usuarioPorLogin((string) $_SESSION['usuario']);
    $motivo = null;
    if (time() - (int) ($_SESSION['ultimo'] ?? 0) > (int) cfg('inatividade')) {
        $motivo = 'Você ficou mais de 2 horas sem usar o painel e saiu automaticamente. Entre de novo.';
    } elseif ($u === null) {
        $motivo = usuariosDanificado() ? 'O arquivo de usuários do painel está com problema. Abra o painel de novo e siga as instruções.'
                                       : 'Seu usuário foi excluído do painel. Fale com um administrador.';
    } elseif (!$u['ativo']) {
        $motivo = 'Seu acesso foi desativado por um administrador. Fale com ele para voltar a usar o painel.';
    } elseif (!hash_equals(impressaoSenha($u['hash']), (string) ($_SESSION['impressao'] ?? ''))) {
        $motivo = $u['trocar_senha'] ? 'Sua senha foi redefinida por um administrador. Entre com a senha nova.'
                                     : 'A senha foi trocada. Entre de novo com a senha nova.';
    }
    if ($motivo !== null) {
        // O motivo fica guardado para a janela "Entre de novo" (a=sessao) dizer o que houve
        $_SESSION = ['motivo_saida' => $motivo];
        session_regenerate_id(true);
        avisar($motivo, 'info');
        return;
    }
    $_SESSION['ultimo'] = time();
}

/** O usuário entrou com a senha temporária e ainda não criou a dele. */
function precisaCriarSenha(?array $eu): bool
{
    return $eu !== null && $eu['trocar_senha'];
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
   Limite de tentativas (guardado com hash — não grava o IP puro)
   Conta por internet (IP) + usuário digitado: várias pessoas usam o painel
   do mesmo Wi-Fi da loja, e quem erra a própria senha não pode trancar os
   outros. Um teto bem mais alto, só por IP, segura quem tenta em massa.
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

/** Chave da internet inteira (o teto de tentativas em massa). */
function chaveIp(): string
{
    return substr(hash_hmac('sha256', ip(), segredoLocal()), 0, 32);
}

/** Chave do usuário digitado nesta internet ('' = código do primeiro acesso / recuperar). */
function chaveTentativa(string $login): string
{
    return substr(hash_hmac('sha256', ip() . "\n" . strtolower(trim($login)), segredoLocal()), 0, 32);
}

/** Máximo de erros, somando todos os usuários, antes de bloquear a internet inteira. */
function tetoTentativasIp(): int
{
    return max((int) cfg('tentativas_max'), (int) (cfg('tentativas_max_ip') ?? 4 * (int) cfg('tentativas_max')));
}

/** Segundos que faltam de bloqueio do registro $chave (0 = liberado). */
function bloqueioDe(array $t, string $chave): int
{
    $r = $t[$chave] ?? null;
    $ate = is_array($r) ? (int) ($r['ate'] ?? 0) : 0;
    return $ate > time() ? $ate - time() : 0;
}

/** Segundos que faltam de bloqueio para este usuário nesta internet (0 = liberado). */
function bloqueioRestante(string $login = ''): int
{
    $t = lerJsonPrivado('tentativas.php', []);
    if (!is_array($t)) return 0;
    return max(bloqueioDe($t, chaveTentativa($login)), bloqueioDe($t, chaveIp()));
}

/** O bloqueio vale para a internet inteira (passou do teto), não só para um usuário. */
function bloqueioDaInternet(): bool
{
    $t = lerJsonPrivado('tentativas.php', []);
    return is_array($t) && bloqueioDe($t, chaveIp()) > 0;
}

/** Conta uma falha; devolve quantas tentativas ainda restam (0 = acabou de bloquear). */
function registrarFalha(string $login = ''): int
{
    $max = (int) cfg('tentativas_max');
    $teto = tetoTentativasIp();
    $janela = (int) cfg('bloqueio');
    return comTrava('tentativas', static function () use ($login, $max, $teto, $janela): int {
        $t = lerJsonPrivado('tentativas.php', []);
        if (!is_array($t)) $t = [];
        $agora = time();
        foreach ($t as $k => $r) {   // limpa o que já venceu
            if (!is_array($r) || (($r['ate'] ?? 0) < $agora && ($r['desde'] ?? 0) + $janela < $agora)) unset($t[$k]);
        }
        $restam = PHP_INT_MAX;
        foreach ([chaveTentativa($login) => $max, chaveIp() => $teto] as $k => $limite) {
            $r = $t[$k] ?? ['n' => 0, 'desde' => $agora, 'ate' => 0];
            if ($r['ate'] > $agora) { $restam = 0; continue; }       // já bloqueado
            if ($r['ate'] || $r['desde'] + $janela < $agora) $r = ['n' => 0, 'desde' => $agora, 'ate' => 0];
            $r['n']++;
            if ($r['n'] >= $limite) $r['ate'] = $agora + $janela;
            $t[$k] = $r;
            $restam = min($restam, max(0, $limite - $r['n']));
        }
        gravarJsonPrivado('tentativas.php', $t);
        return $restam;
    });
}

/** Acertou: zera os erros desse usuário nesta internet (o teto da internet vence sozinho). */
function limparFalhas(string $login = ''): void
{
    comTrava('tentativas', static function () use ($login): void {
        $t = lerJsonPrivado('tentativas.php', []);
        $k = chaveTentativa($login);
        if (is_array($t) && isset($t[$k])) { unset($t[$k]); gravarJsonPrivado('tentativas.php', $t); }
    });
}

/** $login: o bloqueio é só desse usuário (os outros continuam entrando), a não ser que a internet inteira tenha passado do teto. */
function msgBloqueio(int $segundos, string $login = ''): string
{
    $min = max(1, (int) ceil($segundos / 60));
    $tempo = $min . ($min === 1 ? ' minuto' : ' minutos');
    if ($login !== '' && !bloqueioDaInternet()) {
        return 'Muitas tentativas erradas com este usuário. Por segurança, ele ficou bloqueado por '
            . $tempo . '. Espere e tente de novo. Os outros usuários continuam entrando normalmente.';
    }
    return 'Muitas tentativas erradas. Por segurança, o acesso a partir desta internet ficou bloqueado por '
        . $tempo . '. Espere e tente de novo.';
}

function msgFalha(string $base, int $restam, string $login = ''): string
{
    if ($restam <= 0) return msgBloqueio((int) cfg('bloqueio'), $login);
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

/**
 * Código atual do arquivo primeiro-acesso.php (cria um novo se não houver ou se venceu).
 * Serve para o primeiro acesso e para "Recuperar acesso" ($para = 'recuperar': muda só o texto do arquivo).
 */
function codigoInstalacao(string $para = 'instalar'): array
{
    return comTrava('instalacao', static function () use ($para): array {
        $c = lerPrivado('primeiro-acesso.php');
        if ($c !== null && preg_match('/^CODIGO=([A-Z0-9-]{14})$/m', $c, $m) && preg_match('/^CRIADO=(\d+)$/m', $c, $t)
            && (int) $t[1] + (int) cfg('codigo_validade') > time()) {
            return ['codigo' => $m[1], 'criado' => (int) $t[1]];
        }
        $codigo = gerarCodigo();
        $agora = time();
        $vale = quando($agora + (int) cfg('codigo_validade'));
        $rec = $para === 'recuperar';
        gravarPrivado('primeiro-acesso.php', implode("\n", array_merge([
            '==================================================================',
            $rec ? ' PAINEL BALANÇAS.COM — CÓDIGO PARA RECUPERAR O ACESSO' : ' PAINEL BALANÇAS.COM — CÓDIGO DE PRIMEIRO ACESSO',
            '==================================================================',
            '',
            '   Seu código:   ' . $codigo,
            '',
        ], $rec ? [
            ' Digite este código na tela "Recuperar acesso" do painel para criar',
            ' uma senha nova para um administrador. Ele vale até ' . $vale . '.',
            ' Quando o acesso for recuperado, este arquivo e o recuperar.txt',
            ' são apagados.',
        ] : [
            ' Digite este código na tela "Primeiro acesso" do painel para criar',
            ' o seu usuário e a sua senha. Ele vale até ' . $vale . '.',
            ' Depois disso, basta abrir o painel de novo que um código novo é',
            ' gerado aqui. Quando o usuário for criado, este arquivo é apagado.',
        ], [
            '',
            ' Não compartilhe este código: com ele, qualquer pessoa cria a senha.',
            '',
            '------------------------------------------------------------------',
            'CODIGO=' . $codigo,
            'CRIADO=' . $agora,
            '',
        ])));
        registrar('codigo-gerado', $rec ? 'código para recuperar o acesso criado' : 'código de primeiro acesso criado');
        return ['codigo' => $codigo, 'criado' => $agora];
    });
}

/** Confere o código digitado. Devolve a mensagem de erro (e conta a falha), ou null. */
function conferirCodigo(string $digitado, string $para): ?string
{
    $atual = codigoInstalacao($para);
    $digitado = preg_replace('/[^A-Z0-9]/', '', strtoupper($digitado)) ?? '';
    if ($digitado !== '' && hash_equals(str_replace('-', '', $atual['codigo']), $digitado)) return null;
    $restam = registrarFalha();
    registrar('codigo-errado', $para === 'recuperar' ? 'tentativa de recuperar o acesso com código errado' : 'tentativa de primeiro acesso com código errado');
    return msgFalha('Código incorreto. Confira no arquivo primeiro-acesso.php (letras maiúsculas, sem espaços).', $restam);
}

function validarUsuario(string $u): ?string
{
    if (!preg_match(RE_USUARIO, $u)) return 'Usuário: use de 3 a 40 letras sem acento, números, ponto, hífen ou sublinhado (sem espaços).';
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

/** Cria o primeiro administrador. Devolve mensagem de erro, ou null quando deu certo (e já entra). */
function instalar(string $codigo, string $nome, string $usuario, string $senha, string $repetida): ?string
{
    if ($s = bloqueioRestante()) return msgBloqueio($s);
    if ($erro = conferirCodigo($codigo, 'instalar')) return $erro;
    [$nome, $erroNome] = nomeUsuarioValido($nome);
    if ($erroNome) return $erroNome;
    if ($erro = validarUsuario($usuario)) return $erro;
    if ($erro = validarSenhaNova($senha, $repetida, $usuario)) return $erro;

    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $erro = comTrava(TRAVA_USUARIOS, static function () use ($nome, $usuario, $hash): ?string {
        if (instalado()) return 'O painel já foi configurado. Entre com o seu usuário e senha.';
        guardarUsuariosDanificado();
        $lista = lerUsuarios();
        if (acharUsuario($lista, $usuario) !== null) return 'Esse usuário já existe. Escolha outro.';
        $lista[] = novoUsuario($usuario, $nome, 'admin', $hash, false);
        gravarUsuarios($lista);
        @unlink(caminhoPrivado('primeiro-acesso.php'));
        return null;
    });
    if ($erro !== null) return $erro;
    limparFalhas();
    abrirSessaoDe($usuario, $hash);
    registrar('instalou', 'administrador criado: ' . $usuario);
    return null;
}

/* ------------------------------------------------------------------
   Entrar / trocar senha / criar a própria senha
   ------------------------------------------------------------------ */
function entrar(string $usuario, string $senha): ?string
{
    if ($s = bloqueioRestante($usuario)) return msgBloqueio($s, $usuario);
    $lista = lerUsuarios();
    if (!$lista) return 'O painel ainda não foi configurado.';
    $i = acharUsuario($lista, $usuario);
    $u = $i === null ? null : $lista[$i];
    // Sempre confere uma senha (a falsa, se o usuário não existe): o tempo de resposta fica igual
    $senhaOk = password_verify($senha, $u ? $u['hash'] : HASH_FALSO);
    if ($u && $senhaOk) {
        if (!$u['ativo']) {
            registrar('acesso-desativado', '', $u['usuario']);
            return 'Este acesso está desativado. Fale com um administrador do painel.';
        }
        limparFalhas($usuario);
        $novoHash = password_needs_rehash($u['hash'], PASSWORD_DEFAULT) ? password_hash($senha, PASSWORD_DEFAULT) : null;
        $hash = alterarUsuarios(static function (array &$l) use ($u, $novoHash): string {
            $j = acharUsuario($l, $u['usuario']);
            if ($j === null) return $u['hash'];
            if ($novoHash !== null && hash_equals($l[$j]['hash'], $u['hash'])) $l[$j]['hash'] = $novoHash;
            $l[$j]['ultimo_acesso'] = time();
            return $l[$j]['hash'];
        });
        abrirSessaoDe($u['usuario'], $hash);
        registrar('entrou');
        return null;
    }
    usleep(random_int(250000, 600000));   // atrasa quem tenta adivinhar
    $restam = registrarFalha($usuario);
    registrar('senha-errada', 'usuário digitado: ' . cortar($usuario, 40));
    return msgFalha('Usuário ou senha incorretos.', $restam, $usuario);
}

/** Troca a própria senha (Conta). */
function trocarSenha(string $atual, string $nova, string $repetida): ?string
{
    $login = usuarioLogado() ?? '';
    if ($s = bloqueioRestante($login)) return msgBloqueio($s, $login);
    $eu = usuarioAtual();
    if (!$eu || !password_verify($atual, $eu['hash'])) {
        $restam = registrarFalha($login);
        registrar('senha-errada', 'senha atual errada ao tentar trocar');
        return msgFalha('A senha atual não confere.', $restam, $login);
    }
    if ($erro = validarSenhaNova($nova, $repetida, $eu['usuario'])) return $erro;
    if (hash_equals($atual, $nova)) return 'A senha nova precisa ser diferente da atual.';
    $hash = gravarSenhaPropria($eu, password_hash($nova, PASSWORD_DEFAULT));
    abrirSessaoDe($eu['usuario'], $hash);   // novo ID de sessão; outros aparelhos saem
    registrar('trocou-senha');
    return null;
}

/** Primeiro acesso de quem recebeu uma senha temporária: cria a própria senha. */
function criarSenhaPropria(string $nova, string $repetida): ?string
{
    $eu = usuarioAtual();
    if (!$eu) return 'Sua sessão terminou. Entre de novo.';
    if ($erro = validarSenhaNova($nova, $repetida, $eu['usuario'])) return $erro;
    if (password_verify($nova, $eu['hash'])) return 'A senha nova precisa ser diferente da senha temporária.';
    $hash = gravarSenhaPropria($eu, password_hash($nova, PASSWORD_DEFAULT));
    abrirSessaoDe($eu['usuario'], $hash);
    registrar('criou-senha');
    return null;
}

/** Grava o hash novo da própria senha (e tira o "trocar_senha"). Devolve o hash gravado. */
function gravarSenhaPropria(array $eu, string $hash): string
{
    alterarUsuarios(static function (array &$l) use ($eu, $hash): void {
        $i = acharUsuario($l, $eu['usuario']);
        if ($i === null) throw new ErroUsuario('Seu usuário não existe mais. Fale com um administrador.');
        $l[$i]['hash'] = $hash;
        $l[$i]['trocar_senha'] = false;
        $l[$i]['senha_alterada'] = $l[$i]['alterado'] = time();
    });
    return $hash;
}

/* ------------------------------------------------------------------
   Recuperar acesso (último recurso de quem entra no cPanel)
   Um arquivo vazio "recuperar.txt" (ou "recuperar") na pasta privada troca
   a tela de entrar pela tela "Recuperar acesso", que usa o mesmo código do
   primeiro acesso para dar uma senha nova a um administrador (ou criar um).
   ------------------------------------------------------------------ */
/** Arquivos recuperar.txt / recuperar que estão na pasta privada (vazio = nenhum). */
function arquivosRecuperacao(): array
{
    $dir = pastaPrivada();
    $achados = [];
    foreach (@scandir($dir) ?: [] as $f) {
        if (in_array(strtolower($f), ['recuperar.txt', 'recuperar'], true) && @is_file($dir . '/' . $f)) $achados[] = $f;
    }
    return $achados;
}

/**
 * Devolve [erro ou null, arquivos que não deu para apagar]. Quando dá certo, já entra.
 * Usuário de um administrador: senha nova, reativado. Usuário novo: vira administrador.
 * Usuário de um editor: recusado. Os outros usuários não mudam.
 */
function recuperarAcesso(string $codigo, string $usuario, string $senha, string $repetida): array
{
    if ($s = bloqueioRestante()) return [msgBloqueio($s), []];
    if ($erro = conferirCodigo($codigo, 'recuperar')) return [$erro, []];
    if ($erro = validarUsuario($usuario)) return [$erro, []];
    if ($erro = validarSenhaNova($senha, $repetida, $usuario)) return [$erro, []];

    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $r = comTrava(TRAVA_USUARIOS, static function () use ($usuario, $hash): array {
        guardarUsuariosDanificado();
        $lista = lerUsuarios();
        $i = acharUsuario($lista, $usuario);
        if ($i !== null && $lista[$i]['papel'] !== 'admin') {
            return ['erro' => '“' . $lista[$i]['usuario'] . '” é um usuário Editor. Para recuperar o acesso, digite o usuário de um administrador, ou um usuário novo (ele é criado como administrador).'];
        }
        if ($i === null && count($lista) >= MAX_USUARIOS) {
            return ['erro' => 'O painel já tem ' . MAX_USUARIOS . ' usuários, o máximo. Digite o usuário de um administrador que já existe.'];
        }
        if ($i !== null) {
            $lista[$i]['hash'] = $hash;
            $lista[$i]['ativo'] = true;
            $lista[$i]['trocar_senha'] = false;
            $lista[$i]['senha_alterada'] = $lista[$i]['alterado'] = time();
            $login = $lista[$i]['usuario'];
        } else {
            $lista[] = novoUsuario($usuario, $usuario, 'admin', $hash, false);
            $login = $usuario;
        }
        gravarUsuarios($lista);
        @unlink(caminhoPrivado('primeiro-acesso.php'));
        return ['login' => $login, 'novo' => $i === null];
    });
    if (isset($r['erro'])) return [$r['erro'], []];
    $sobrou = [];
    foreach (arquivosRecuperacao() as $f) {
        if (!@unlink(pastaPrivada() . '/' . $f)) $sobrou[] = $f;
    }
    limparFalhas();
    abrirSessaoDe($r['login'], $hash);
    registrar('recuperou-acesso', ($r['novo'] ? 'administrador novo: ' : 'senha nova para o administrador ') . $r['login']);
    return [null, $sobrou];
}
