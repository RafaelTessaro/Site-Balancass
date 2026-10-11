<?php
/* =====================================================================
   Painel Balanças.com — usuários e papéis
   Cada pessoa tem o seu usuário, com papel Administrador (faz tudo) ou
   Editor (produtos e clientes). A lista fica na pasta privada, em
   usuarios.php (só o "hash" das senhas, nunca a senha). O config.php
   antigo, de um usuário só, é convertido sozinho na primeira visita,
   sem derrubar quem já estava com o painel aberto.
   Telas em lib/visoes-usuarios.php.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

const PAPEIS = ['admin' => 'Administrador', 'editor' => 'Editor'];
const MAX_USUARIOS = 20;
const RE_USUARIO = '/^[A-Za-z0-9._-]{3,40}$/';
// A mesma trava do primeiro acesso: criar, converter e alterar usuários nunca acontecem ao mesmo tempo
const TRAVA_USUARIOS = 'instalacao';
// Palavras curtas e sem acento para a senha temporária (ex.: prato-verde-tecla-47): fácil de ditar
const PALAVRAS_SENHA = ['areia', 'balde', 'banco', 'barco', 'bolo', 'bota', 'cabra', 'caixa', 'cama', 'canto', 'carta',
    'cinto', 'copo', 'corda', 'dado', 'dente', 'doce', 'escada', 'faca', 'festa', 'figo', 'folha', 'fruta', 'garfo',
    'gato', 'gelo', 'ilha', 'janela', 'jarra', 'lago', 'leite', 'livro', 'lousa', 'manga', 'mapa', 'mesa', 'milho',
    'mola', 'navio', 'neve', 'nuvem', 'onda', 'ovo', 'panela', 'pato', 'pedra', 'pena', 'pera', 'pipa', 'ponte',
    'porta', 'prato', 'quadro', 'queijo', 'rampa', 'rede', 'roda', 'sapo', 'sino', 'sola', 'tecla', 'telha', 'tigre',
    'trigo', 'urso', 'vaso', 'vela', 'vento', 'verde', 'zebra'];
// Eventos do registro sobre usuários (a frase completa vai no detalhe)
const EVENTOS_USUARIOS = ['usuario-criou', 'usuario-editou', 'usuario-senha', 'usuario-desativou', 'usuario-reativou', 'usuario-excluiu'];

/* ------------------------------------------------------------------
   Leitura e gravação de usuarios.php
   ------------------------------------------------------------------ */
/** Um usuário com todos os campos e tipos certos (null se faltar o essencial). */
function normalizarUsuario(array $u): ?array
{
    $login = $u['usuario'] ?? null;
    $hash = $u['hash'] ?? null;
    if (!is_string($login) || !preg_match(RE_USUARIO, $login) || !is_string($hash) || $hash === '') return null;
    $ts = static fn($v): ?int => is_int($v) && $v > 0 ? $v : null;
    $nome = limparLinha($u['nome'] ?? '') ?? '';
    $criado = $ts($u['criado'] ?? null) ?? 0;
    return [
        'usuario'        => $login,
        'nome'           => $nome !== '' ? cortar($nome, 60) : $login,
        'papel'          => ($u['papel'] ?? '') === 'admin' ? 'admin' : 'editor',   // na dúvida, o papel com menos poder
        'hash'           => $hash,
        'ativo'          => ($u['ativo'] ?? true) !== false,
        'trocar_senha'   => ($u['trocar_senha'] ?? false) === true,
        'criado'         => $criado,
        'criado_por'     => is_string($u['criado_por'] ?? null) ? $u['criado_por'] : '-',
        'alterado'       => $ts($u['alterado'] ?? null) ?? $criado,
        'senha_alterada' => $ts($u['senha_alterada'] ?? null) ?? $criado,
        'ultimo_acesso'  => $ts($u['ultimo_acesso'] ?? null),
    ];
}

/** Todos os usuários (lista vazia quando ainda não há usuarios.php). */
function lerUsuarios(): array
{
    $d = lerJsonPrivado('usuarios.php');
    if (!is_array($d) || !is_array($d['usuarios'] ?? null)) return [];
    $out = [];
    foreach ($d['usuarios'] as $u) {
        if (is_array($u) && ($n = normalizarUsuario($u)) !== null) $out[] = $n;
    }
    return $out;
}

/** true quando usuarios.php existe mas não dá para ler (editado à mão, cortado no meio…). */
function usuariosDanificado(): bool
{
    if (!@is_file(caminhoPrivado('usuarios.php'))) return false;
    $d = lerJsonPrivado('usuarios.php');
    return !is_array($d) || !is_array($d['usuarios'] ?? null);
}

/** Antes de começar uma lista nova por cima de um usuarios.php danificado, guarda o arquivo como estava. */
function guardarUsuariosDanificado(): void
{
    if (!usuariosDanificado()) return;
    $bruto = @file_get_contents(caminhoPrivado('usuarios.php'));
    if ($bruto !== false) gravarPrivado('usuarios-danificado-' . date('Ymd-His') . '.php', base64_encode($bruto));
}

/** Só dentro de comTrava(TRAVA_USUARIOS). */
function gravarUsuarios(array $lista): void
{
    gravarJsonPrivado('usuarios.php', ['versao' => 1, 'usuarios' => array_values($lista)]);
}

/**
 * Altera a lista com a trava: $fn recebe a lista atual (por referência), pode lançar ErroUsuario
 * e devolve o que quiser. Nunca deixa o painel sem nenhum administrador ativo.
 */
function alterarUsuarios(callable $fn)
{
    return comTrava(TRAVA_USUARIOS, static function () use ($fn) {
        if (usuariosDanificado()) throw new RuntimeException('usuarios.php danificado: nada foi alterado');
        $lista = lerUsuarios();
        $antes = adminsAtivos($lista);
        $r = $fn($lista);
        if ($antes > 0 && adminsAtivos($lista) === 0) {
            throw new ErroUsuario('O painel precisa ter pelo menos um administrador ativo. Nada foi alterado.');
        }
        gravarUsuarios($lista);
        return $r;
    });
}

/** Posição do usuário na lista (login sem diferença de maiúsculas), ou null. */
function acharUsuario(array $lista, string $login): ?int
{
    if ($login === '') return null;
    foreach ($lista as $i => $u) if (strcasecmp($u['usuario'], $login) === 0) return $i;
    return null;
}

function usuarioPorLogin(string $login): ?array
{
    $lista = lerUsuarios();
    $i = acharUsuario($lista, $login);
    return $i === null ? null : $lista[$i];
}

function adminsAtivos(array $lista): int
{
    return count(array_filter($lista, static fn(array $u) => $u['papel'] === 'admin' && $u['ativo']));
}

/** Quem está usando o painel agora (lido do arquivo a cada pedido: papel novo vale na hora). */
function usuarioAtual(): ?array
{
    $login = usuarioLogado();
    return $login === null ? null : usuarioPorLogin($login);
}

function ehAdmin(): bool
{
    $eu = usuarioAtual();
    return $eu !== null && $eu['ativo'] && $eu['papel'] === 'admin';
}

/** Nome de exibição de cada login (para o registro, as cópias e as listas). */
function nomesDosUsuarios(): array
{
    $m = [];
    foreach (lerUsuarios() as $u) $m[strtolower($u['usuario'])] = $u['nome'];
    return $m;
}

function nomeDe(string $login, ?array $nomes = null): string
{
    $nomes ??= nomesDosUsuarios();
    return $nomes[strtolower($login)] ?? $login;
}

/** Usuário novo com todos os campos. */
function novoUsuario(string $login, string $nome, string $papel, string $hash, bool $trocarSenha): array
{
    $agora = time();
    return [
        'usuario' => $login, 'nome' => $nome, 'papel' => $papel, 'hash' => $hash,
        'ativo' => true, 'trocar_senha' => $trocarSenha,
        'criado' => $agora, 'criado_por' => usuarioLogado() ?? '-', 'alterado' => $agora,
        'senha_alterada' => $agora, 'ultimo_acesso' => null,
    ];
}

/* ------------------------------------------------------------------
   Conversão do config.php antigo (um usuário só) para usuarios.php
   ------------------------------------------------------------------ */
/** O config.php de antes (usuário + hash), ou null. */
function contaAntiga(): ?array
{
    $c = lerJsonPrivado('config.php');
    return (is_array($c) && is_string($c['usuario'] ?? null) && preg_match(RE_USUARIO, $c['usuario'])
        && is_string($c['hash'] ?? null) && $c['hash'] !== '') ? $c : null;
}

/**
 * Chamada a cada pedido, antes de conferir a sessão. Na primeira vez: o dono vira Administrador
 * (mesmo login e mesmo hash, então a sessão aberta continua valendo) e o config.php vira só uma
 * marca, sem senha. Se o código antigo rodasse de novo, ele pediria o código do cPanel (seguro).
 */
function migrarContaAntiga(): void
{
    if (@is_file(caminhoPrivado('usuarios.php')) || contaAntiga() === null) return;
    comTrava(TRAVA_USUARIOS, static function (): void {
        $c = contaAntiga();
        if ($c === null || @is_file(caminhoPrivado('usuarios.php'))) return;
        $agora = time();
        $criado = is_int($c['criado'] ?? null) && $c['criado'] > 0 ? $c['criado'] : $agora;
        // Quem está com o painel aberto agora: o "último acesso" é a hora em que entrou
        $logado = usuarioLogado();
        $entrou = $logado !== null && strcasecmp($logado, $c['usuario']) === 0 ? (int) ($_SESSION['entrou'] ?? 0) : 0;
        gravarUsuarios([[
            'usuario' => $c['usuario'], 'nome' => $c['usuario'], 'papel' => 'admin', 'hash' => $c['hash'],
            'ativo' => true, 'trocar_senha' => false, 'criado' => $criado, 'criado_por' => '-', 'alterado' => $agora,
            'senha_alterada' => is_int($c['alterado'] ?? null) && $c['alterado'] > 0 ? $c['alterado'] : $criado,
            'ultimo_acesso' => $entrou > 0 ? $entrou : null,
        ]]);
        gravarJsonPrivado('config.php', ['migrado' => $agora, 'para' => 'usuarios.php']);
        registrar('migrou-usuarios', $c['usuario'] . ' virou Administrador');
    });
}

/* ------------------------------------------------------------------
   Senha temporária e validações
   ------------------------------------------------------------------ */
/** Três palavras e um número (ex.: prato-verde-tecla-47): pelo menos 14 caracteres, fácil de ditar. */
function gerarSenhaTemporaria(): string
{
    $p = [];
    while (count($p) < 3) {
        $w = PALAVRAS_SENHA[random_int(0, count(PALAVRAS_SENHA) - 1)];
        if (!in_array($w, $p, true)) $p[] = $w;
    }
    return implode('-', $p) . '-' . random_int(10, 99);
}

/** [nome limpo, erro ou null] */
function nomeUsuarioValido($v): array
{
    $n = limparLinha($v);
    if ($n === null) return ['', 'Nome: o texto tem caracteres inválidos. Digite de novo.'];
    if (tamanho($n) < 2) return [$n, 'Nome: escreva o nome da pessoa (pelo menos 2 letras).'];
    if (tamanho($n) > 60) return [cortar($n, 60), 'Nome: use no máximo 60 caracteres.'];
    if (preg_match('/[<>]/', $n)) return [$n, 'Nome: não use os sinais < e >.'];
    return [$n, null];
}

/** "hoje às 14:02", "ontem às 09:10" ou a data. */
function quandoCurto(?int $ts): string
{
    if (!$ts) return '';
    $hoje = strtotime('today');
    if ($ts >= $hoje) return 'hoje às ' . date('H:i', $ts);
    if ($ts >= $hoje - 86400) return 'ontem às ' . date('H:i', $ts);
    return date('d/m/Y', $ts);
}

/** Situação do usuário para a lista: [texto, classe do selo] ou null quando está tudo normal. */
function situacaoUsuario(array $u): ?array
{
    if (!$u['ativo']) return ['Desativado', 'selo--off'];
    if ($u['trocar_senha']) return [$u['ultimo_acesso'] ? 'Aguardando a senha nova' : 'Aguardando o 1º acesso', 'selo--espera'];
    return null;
}

function rotuloUsuario(array $u): string
{
    return $u['nome'] . ' (' . $u['usuario'] . ')';
}

/* ------------------------------------------------------------------
   Ações das telas de usuários (chamadas pelo index.php; só administradores)
   ------------------------------------------------------------------ */
/** O usuário do endereço (?u=login) ou do formulário. */
function usuarioDaUrl(string $fonte = 'get'): array
{
    $login = $fonte === 'get' ? ($_GET['u'] ?? '') : ($_POST['u'] ?? '');
    $u = is_string($login) && preg_match(RE_USUARIO, $login) ? usuarioPorLogin($login) : null;
    if ($u === null) throw new ErroUsuario('Usuário não encontrado. Ele pode ter sido excluído (talvez em outro aparelho).');
    return $u;
}

function ehVoce(array $u): bool
{
    return strcasecmp($u['usuario'], (string) usuarioLogado()) === 0;
}

/** Dentro da trava: quem pediu ainda é administrador ativo? (o papel pode ter mudado no meio do caminho) */
function exigirAdminNaLista(array $lista): void
{
    $i = acharUsuario($lista, (string) usuarioLogado());
    if ($i === null || !$lista[$i]['ativo'] || $lista[$i]['papel'] !== 'admin') {
        throw new ErroUsuario('Esta área é só para administradores do painel.');
    }
}

/** Guarda a senha temporária para aparecer UMA vez na próxima tela (nunca vai para o registro). */
function mostrarSenhaUmaVez(array $u, string $senha, bool $novo): never
{
    $_SESSION['senha_temporaria'] = ['usuario' => $u['usuario'], 'nome' => $u['nome'], 'papel' => $u['papel'],
        'ativo' => $u['ativo'], 'senha' => $senha, 'novo' => $novo, 'quando' => time()];
    redirecionar(link_('senha-temporaria'));
}

function acaoSenhaTemporaria(): void
{
    $f = $_SESSION['senha_temporaria'] ?? null;
    unset($_SESSION['senha_temporaria']);   // aparece uma vez só
    if (!is_array($f) || !is_string($f['senha'] ?? null) || time() - (int) ($f['quando'] ?? 0) > 900) {
        avisar('A senha temporária aparece uma vez só. Se ela se perdeu, use “Redefinir senha” de novo na pessoa.', 'info');
        redirecionar(link_('usuarios'));
    }
    telaSenhaTemporaria($f);
}

function acaoUsuarios(): void
{
    if (usuariosDanificado()) throw new RuntimeException('usuarios.php danificado');
    telaUsuarios(lerUsuarios());
}

function acaoUsuarioEditar(): void
{
    $u = usuarioDaUrl();
    telaUsuarioForm($u, $u);
}

/** Cria (sem ?u=) ou edita (com ?u=login) nome e papel. */
function acaoUsuarioSalvar(): void
{
    $atual = isset($_GET['u']) ? usuarioDaUrl() : null;
    [$nome, $erroNome] = nomeUsuarioValido($_POST['nome'] ?? '');
    $papel = is_string($_POST['papel'] ?? null) && isset(PAPEIS[$_POST['papel']]) ? $_POST['papel'] : '';
    $login = $atual ? $atual['usuario'] : trim(is_string($_POST['usuario'] ?? null) ? $_POST['usuario'] : '');
    $erros = [];
    if ($erroNome !== null) $erros['nome'] = $erroNome;
    if (!$atual) {
        $lista = lerUsuarios();
        if ($e = validarUsuario($login)) $erros['usuario'] = $e;
        elseif (acharUsuario($lista, $login) !== null) $erros['usuario'] = 'Usuário: “' . $login . '” já existe. Escolha outro (por exemplo, com o sobrenome: ' . strtolower($login) . '.silva).';
        if (count($lista) >= MAX_USUARIOS) $erros['geral'] = 'O painel já tem ' . MAX_USUARIOS . ' usuários, o máximo. Exclua quem não usa mais para criar outro.';
    }
    if ($papel === '') $erros['papel'] = 'Papel: escolha Editor ou Administrador.';
    elseif ($atual && ehVoce($atual) && $papel !== $atual['papel']) $erros['papel'] = 'Você não pode mudar o seu próprio papel. Peça a outro administrador.';
    $dados = ['usuario' => $login, 'nome' => $nome, 'papel' => $papel];
    if ($erros) {
        http_response_code(422);
        telaUsuarioForm($atual, $dados, $erros);
        return;
    }

    if (!$atual) {
        $senha = gerarSenhaTemporaria();
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        try {
            $novo = alterarUsuarios(static function (array &$l) use ($login, $nome, $papel, $hash): array {
                exigirAdminNaLista($l);
                if (acharUsuario($l, $login) !== null) throw new ErroUsuario('Usuário: “' . $login . '” já existe. Escolha outro.');
                if (count($l) >= MAX_USUARIOS) throw new ErroUsuario('O painel já tem ' . MAX_USUARIOS . ' usuários, o máximo.');
                $u = novoUsuario($login, $nome, $papel, $hash, true);
                $l[] = $u;
                return $u;
            });
        } catch (ErroUsuario $e) {
            http_response_code(409);
            telaUsuarioForm(null, $dados, ['geral' => $e->getMessage()]);
            return;
        }
        registrar('usuario-criou', 'Criou o usuário ' . rotuloUsuario($novo) . ' como ' . PAPEIS[$papel]);
        mostrarSenhaUmaVez($novo, $senha, true);
    }

    $mudou = [];
    $depois = alterarUsuarios(static function (array &$l) use ($atual, $nome, $papel, &$mudou): array {
        exigirAdminNaLista($l);
        $i = acharUsuario($l, $atual['usuario']);
        if ($i === null) throw new ErroUsuario('Esse usuário foi excluído (talvez em outro aparelho) enquanto você editava.');
        if (ehVoce($l[$i]) && $papel !== $l[$i]['papel']) throw new ErroUsuario('Você não pode mudar o seu próprio papel. Peça a outro administrador.');
        if ($l[$i]['nome'] !== $nome) $mudou[] = 'nome “' . $l[$i]['nome'] . '” → “' . $nome . '”';
        if ($l[$i]['papel'] !== $papel) $mudou[] = 'papel ' . PAPEIS[$l[$i]['papel']] . ' → ' . PAPEIS[$papel];
        $l[$i]['nome'] = $nome;
        $l[$i]['papel'] = $papel;
        if ($mudou) $l[$i]['alterado'] = time();
        return $l[$i];
    });
    if (!$mudou) {
        avisar('Nada mudou em ' . $depois['nome'] . '.', 'info');
        redirecionar(link_('usuarios') . '#u-' . $depois['usuario']);
    }
    registrar('usuario-editou', 'Editou o usuário ' . rotuloUsuario($depois) . ': ' . implode('; ', $mudou));
    $msg = $depois['nome'] . ' foi salvo.';
    if ($papel !== $atual['papel']) {
        $msg = $depois['nome'] . ' agora é ' . PAPEIS[$papel] . '. Vale na hora, sem precisar entrar de novo'
            . ($papel === 'admin' ? ': já pode cuidar de usuários, dados da empresa e cópias de segurança.' : ': continua com produtos e clientes, mas sai das áreas de administrador.');
    }
    avisar($msg);
    redirecionar(link_('usuarios') . '#u-' . $depois['usuario']);
}

/** Redefinir senha: nova senha temporária; a antiga para de funcionar e a sessão da pessoa termina. */
function acaoUsuarioSenha(bool $post): void
{
    $u = usuarioDaUrl($post ? 'post' : 'get');
    if (ehVoce($u)) throw new ErroUsuario('Para trocar a sua própria senha, use Conta → Trocar senha.');
    if (!$post || ($_POST['confirmado'] ?? '') !== '1') {
        telaConfirmar('Redefinir a senha?', textoRedefinirSenha($u), link_('usuario-senha'), ['u' => $u['usuario']], 'Sim, redefinir', link_('usuarios') . '#u-' . $u['usuario'], 'usuarios');
        return;
    }
    $senha = gerarSenhaTemporaria();
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $depois = alterarUsuarios(static function (array &$l) use ($u, $hash): array {
        exigirAdminNaLista($l);
        $i = acharUsuario($l, $u['usuario']);
        if ($i === null) throw new ErroUsuario('Esse usuário já tinha sido excluído.');
        if (ehVoce($l[$i])) throw new ErroUsuario('Para trocar a sua própria senha, use Conta → Trocar senha.');
        $l[$i]['hash'] = $hash;   // a "impressão" da senha muda: a sessão aberta dela termina na próxima ação
        $l[$i]['trocar_senha'] = true;
        $l[$i]['senha_alterada'] = $l[$i]['alterado'] = time();
        return $l[$i];
    });
    registrar('usuario-senha', 'Redefiniu a senha de ' . rotuloUsuario($depois));
    mostrarSenhaUmaVez($depois, $senha, false);
}

function textoRedefinirSenha(array $u): string
{
    return 'Criar uma senha temporária nova para ' . rotuloUsuario($u) . '? A senha atual para de funcionar na hora e, se a pessoa estiver com o painel aberto, '
        . 'ela sai na próxima ação. No próximo acesso, o painel pede para ela criar a própria senha.';
}

/** Desativar (valor=0) ou reativar (valor=1). */
function acaoUsuarioAtivo(bool $post): void
{
    $u = usuarioDaUrl($post ? 'post' : 'get');
    $fonte = $post ? $_POST : $_GET;
    $ativar = ($fonte['valor'] ?? '') === '1';
    if (ehVoce($u)) throw new ErroUsuario('Você não pode desativar o seu próprio acesso. Peça a outro administrador.');
    if ($u['ativo'] === $ativar) {
        avisar($u['nome'] . ($ativar ? ' já está ativo.' : ' já estava desativado.'), 'info');
        redirecionar(link_('usuarios') . '#u-' . $u['usuario']);
    }
    if (!$post || ($_POST['confirmado'] ?? '') !== '1') {
        telaConfirmar($ativar ? 'Reativar o acesso?' : 'Desativar o acesso?', $ativar ? textoReativar($u) : textoDesativar($u),
            link_('usuario-ativo'), ['u' => $u['usuario'], 'valor' => $ativar ? '1' : '0'], $ativar ? 'Sim, reativar' : 'Sim, desativar',
            link_('usuarios') . '#u-' . $u['usuario'], 'usuarios');
        return;
    }
    $depois = alterarUsuarios(static function (array &$l) use ($u, $ativar): array {
        exigirAdminNaLista($l);
        $i = acharUsuario($l, $u['usuario']);
        if ($i === null) throw new ErroUsuario('Esse usuário foi excluído (talvez em outro aparelho).');
        if (ehVoce($l[$i])) throw new ErroUsuario('Você não pode desativar o seu próprio acesso. Peça a outro administrador.');
        $l[$i]['ativo'] = $ativar;
        $l[$i]['alterado'] = time();
        return $l[$i];
    });
    if ($ativar) {
        registrar('usuario-reativou', 'Reativou o acesso de ' . rotuloUsuario($depois));
        avisar($depois['nome'] . ' pode entrar de novo' . ($depois['trocar_senha'] ? ', com a senha temporária que você passou.' : ', com a mesma senha de antes.'));
    } else {
        registrar('usuario-desativou', 'Desativou o acesso de ' . rotuloUsuario($depois));
        avisar('O acesso de ' . $depois['nome'] . ' foi desativado. Se a pessoa estava com o painel aberto, saiu na hora. Para ela voltar, use Reativar.');
    }
    redirecionar(link_('usuarios') . '#u-' . $depois['usuario']);
}

function textoDesativar(array $u): string
{
    return 'Desativar o acesso de ' . rotuloUsuario($u) . '? A pessoa sai do painel na hora e não consegue mais entrar. '
        . 'Nada do que ela fez é desfeito, e dá para reativar depois (com a mesma senha).';
}

function textoReativar(array $u): string
{
    return 'Reativar o acesso de ' . rotuloUsuario($u) . '? A pessoa volta a entrar no painel como ' . PAPEIS[$u['papel']] . ', com a mesma senha de antes.';
}

function acaoUsuarioExcluir(bool $post): void
{
    $u = usuarioDaUrl($post ? 'post' : 'get');
    if (ehVoce($u)) throw new ErroUsuario('Você não pode excluir o seu próprio usuário. Peça a outro administrador.');
    if (!$post || ($_POST['confirmado'] ?? '') !== '1') {
        telaConfirmar('Excluir usuário?', textoExcluirUsuario($u), link_('usuario-excluir'), ['u' => $u['usuario']], 'Sim, excluir',
            link_('usuarios') . '#u-' . $u['usuario'], 'usuarios');
        return;
    }
    $removido = alterarUsuarios(static function (array &$l) use ($u): array {
        exigirAdminNaLista($l);
        $i = acharUsuario($l, $u['usuario']);
        if ($i === null) throw new ErroUsuario('Esse usuário já tinha sido excluído.');
        if (ehVoce($l[$i])) throw new ErroUsuario('Você não pode excluir o seu próprio usuário. Peça a outro administrador.');
        $x = $l[$i];
        array_splice($l, $i, 1);
        return $x;
    });
    registrar('usuario-excluiu', 'Excluiu o usuário ' . rotuloUsuario($removido));
    avisar('O usuário ' . rotuloUsuario($removido) . ' foi excluído. O que a pessoa fez no site continua, e as atividades dela continuam registradas.');
    redirecionar(link_('usuarios'));
}

function textoExcluirUsuario(array $u): string
{
    return 'Excluir o usuário ' . rotuloUsuario($u) . '? A pessoa perde o acesso na hora. Nada do que ela fez no site é desfeito, e as atividades dela continuam registradas. '
        . 'Para só suspender por um tempo, use Desativar.';
}

/* ------------------------------------------------------------------
   Atividades (registro.php + registro-anterior.php)
   ------------------------------------------------------------------ */
// Linhas técnicas que não interessam a ninguém (fotos indo e voltando da lixeira)
const EVENTOS_TECNICOS = ['foto-lixeira', 'foto-restaurada'];
const EVENTOS_ALERTA = ['senha-errada', 'codigo-errado', 'bloqueado', 'csrf', 'acesso-desativado', 'negado'];
const EVENTOS_ACESSO = ['entrou', 'saiu', 'trocou-senha', 'criou-senha', 'instalou', 'codigo-gerado', 'recuperou-acesso'];
const TIPOS_ATIVIDADE = ['' => 'Tudo', 'alteracoes' => 'Alterações', 'acessos' => 'Acessos', 'alertas' => 'Alertas'];

/** alteracoes | acessos | alertas */
function tipoDoEvento(string $evento): string
{
    if (in_array($evento, EVENTOS_ALERTA, true)) return 'alertas';
    if (in_array($evento, EVENTOS_ACESSO, true)) return 'acessos';
    return 'alteracoes';
}

/** Frase simples, no passado, para cada linha do registro. */
function fraseAtividade(array $a): string
{
    static $nomes = [
        'entrou' => 'Entrou no painel', 'saiu' => 'Saiu do painel', 'senha-errada' => 'Senha errada', 'trocou-senha' => 'Trocou a senha',
        'criou-senha' => 'Criou a própria senha (primeiro acesso ou senha redefinida)', 'instalou' => 'Criou o acesso (primeiro acesso do painel)',
        'codigo-gerado' => 'O painel gerou um código de acesso (arquivo primeiro-acesso.php)', 'codigo-errado' => 'Código de acesso errado',
        'bloqueado' => 'Bloqueio por tentativas', 'csrf' => 'Pedido recusado', 'acesso-desativado' => 'Tentou entrar com o acesso desativado',
        'negado' => 'Tentou abrir uma área só de administradores', 'migrou-usuarios' => 'O painel passou a ter usuários e papéis',
        'recuperou-acesso' => 'Recuperou o acesso pelo arquivo recuperar.txt',
    ];
    $ev = $a['evento'];
    if ($ev === 'catalogo' || $ev === 'clientes') return frasePassado($a['detalhe']);
    if ($ev === 'ajustes' || in_array($ev, EVENTOS_USUARIOS, true)) return $a['detalhe'] !== '' ? $a['detalhe'] : 'Alteração';
    // Atualização do catálogo deixada para depois / aviso de volta (aplicar fica no evento "catalogo")
    if ($ev === 'pacote') return $a['detalhe'] !== '' ? $a['detalhe'] : 'Atualização do catálogo';
    if ($ev === 'migrou-usuarios' && $a['detalhe'] !== '') return $nomes[$ev] . ' (' . $a['detalhe'] . ')';
    return $nomes[$ev] ?? $ev;
}

/**
 * Atividades para mostrar, mais novas primeiro: sem as linhas técnicas, com repetições seguidas
 * (ex.: 30 toques nas setas) juntas numa linha só. $todas: ultimasAtividades(); $quem: login
 * (ou '' = todos); $tipo: chave de TIPOS_ATIVIDADE.
 */
function atividadesFiltradas(array $todas, string $quem = '', string $tipo = '', int $max = 300): array
{
    $out = [];
    foreach ($todas as $a) {
        if (in_array($a['evento'], EVENTOS_TECNICOS, true)) continue;
        if ($tipo !== '' && tipoDoEvento($a['evento']) !== $tipo) continue;
        if ($quem !== '' && !atividadeDe($a, $quem)) continue;
        $u = count($out) - 1;
        if ($u >= 0 && $out[$u]['evento'] === $a['evento'] && $out[$u]['detalhe'] === $a['detalhe'] && $out[$u]['usuario'] === $a['usuario']) {
            $out[$u]['vezes']++;
            continue;
        }
        if (count($out) >= $max) break;
        $out[] = $a + ['vezes' => 1];
    }
    return $out;
}

/** A linha é dessa pessoa? (inclui as senhas erradas digitadas com o login dela) */
function atividadeDe(array $a, string $login): bool
{
    if (strcasecmp($a['usuario'], $login) === 0) return true;
    return $a['usuario'] === '-' && $a['evento'] === 'senha-errada' && strcasecmp($a['detalhe'], 'usuário digitado: ' . $login) === 0;
}

/** Quem aparece no filtro de pessoas: os usuários de hoje e quem aparece no registro. */
function pessoasDoRegistro(array $usuarios, array $todas): array
{
    $m = [];
    foreach ($usuarios as $u) $m[strtolower($u['usuario'])] = $u['nome'] . ' (' . $u['usuario'] . ')';
    foreach ($todas as $a) {
        $k = strtolower($a['usuario']);
        if ($a['usuario'] !== '-' && $a['usuario'] !== '' && !isset($m[$k]) && preg_match(RE_USUARIO, $a['usuario'])) $m[$k] = $a['usuario'] . ' (excluído)';
    }
    return $m;
}

function acaoAtividades(): void
{
    $todas = ultimasAtividades(5000, true);
    $pessoas = pessoasDoRegistro(lerUsuarios(), $todas);
    $quem = is_string($_GET['quem'] ?? null) ? strtolower($_GET['quem']) : '';
    if (!isset($pessoas[$quem])) $quem = '';
    $tipo = is_string($_GET['tipo'] ?? null) && isset(TIPOS_ATIVIDADE[$_GET['tipo']]) ? $_GET['tipo'] : '';
    telaAtividades(atividadesFiltradas($todas, $quem, $tipo), $pessoas, $quem, $tipo, nomesDosUsuarios());
}
