<?php
/* =====================================================================
   Painel Balanças.com — entrada única
   Todas as telas e ações passam por aqui: index.php?a=<ação>.
   Instruções: LEIA-ME.md (nesta pasta).
   ===================================================================== */
declare(strict_types=1);
define('PAINEL', true);

require __DIR__ . '/lib/nucleo.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/usuarios.php';
require __DIR__ . '/lib/catalogo.php';
require __DIR__ . '/lib/imagens.php';
require __DIR__ . '/lib/clientes.php';
require __DIR__ . '/lib/ajustes.php';
require __DIR__ . '/lib/visoes.php';
require __DIR__ . '/lib/visoes-usuarios.php';
require __DIR__ . '/lib/visoes-empresa.php';

iniciarPainel();

if (chegouDeOutroSite()) {
    // Nunca inicia sessão aqui: isso criaria um cookie novo e derrubaria o login atual.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        http_response_code(403);
        echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="robots" content="noindex">'
            . '<title>Painel</title><p>Pedido recusado. <a href="index.php">Abrir o painel</a></p></html>';
        exit;
    }
    $jaRecarregou = isset($_GET['_r']);
    $url = 'index.php?' . http_build_query(array_map('strval', array_filter($_GET, 'is_string')) + ['_r' => '1']);
    echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="robots" content="noindex">'
        . ($jaRecarregou ? '' : '<meta http-equiv="refresh" content="0;url=' . e($url) . '">')
        . '<title>Abrindo o painel…</title>'
        . '<p><a href="' . e($url) . '">Abrir o painel</a></p></html>';
    exit;
}

iniciarSessao();
migrarContaAntiga();   // config.php antigo (um usuário só) → usuarios.php, sem derrubar a sessão aberta
verificarSessao();

const ACOES = ['lista', 'novo', 'editar', 'salvar', 'rapido', 'mover', 'excluir', 'desfazer', 'copias', 'restaurar', 'conta', 'senha', 'sair', 'entrar', 'instalar', 'sessao',
    'clientes', 'cliente-novo', 'cliente-editar', 'cliente-salvar', 'cliente-mover', 'cliente-excluir', 'cliente-desfazer',
    'usuarios', 'usuario-novo', 'usuario-editar', 'usuario-salvar', 'usuario-senha', 'usuario-ativo', 'usuario-excluir', 'senha-temporaria',
    'atividades', 'criar-senha', 'recuperar', 'empresa', 'empresa-salvar'];
const SO_POST = ['salvar', 'rapido', 'mover', 'desfazer', 'senha', 'sair', 'cliente-salvar', 'cliente-mover', 'cliente-desfazer', 'usuario-salvar', 'empresa-salvar'];
// Só administradores. O papel é lido do usuarios.php a cada pedido (mudou, vale na hora).
// Vale para GET, POST e fetch (JSON): editor recebe 403.
const SO_ADMIN = ['usuarios', 'usuario-novo', 'usuario-editar', 'usuario-salvar', 'usuario-senha', 'usuario-ativo', 'usuario-excluir', 'senha-temporaria',
    'atividades', 'copias', 'restaurar', 'empresa', 'empresa-salvar'];
// Enquanto a pessoa não cria a própria senha (entrou com a senha temporária), só estas
const SEM_SENHA_PROPRIA = ['criar-senha', 'sair'];
$acao = is_string($_GET['a'] ?? null) && in_array($_GET['a'], ACOES, true) ? $_GET['a'] : 'lista';
$post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

// A tela do produto confere a sessão antes de enviar (e, se ela venceu, pede a senha
// numa janela, sem sair da página e sem perder o que foi digitado nem a foto)
if ($acao === 'sessao') {
    $logado = usuarioLogado() !== null;   // verificarSessao() já conferiu o usuário
    responderJson(['ok' => true, 'logado' => $logado, 'csrf' => tokenCsrf()] + ($logado ? [] : ['motivo' => motivoSaida()]));
}

// Envio maior que o limite do PHP: o servidor descarta tudo ($_POST e $_FILES vazios)
if ($post && !$_POST && !$_FILES && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $ehCliente = $acao === 'cliente-salvar';
    $msg = 'O envio ficou maior do que o servidor aceita (' . mb_(limiteEnvio()) . '). Use ' . ($ehCliente ? 'um logo menor' : 'uma foto menor') . ' e tente de novo.';
    if (pedidoJson()) responderJson(['ok' => false, 'msg' => $msg], 413);
    if (usuarioLogado()) {
        avisar($msg, 'erro');
        $id = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
        if ($ehCliente) redirecionar(linkFormCliente($_GET));
        redirecionar($acao === 'salvar' ? ($id !== '' ? link_('editar', ['id' => $id]) : link_('novo')) : 'index.php');
    }
    redirecionar('index.php');
}

/* ------------------------------------------------------------------
   Nenhum usuário ainda (instalação nova): só o primeiro acesso
   ------------------------------------------------------------------ */
if (lerUsuarios() === []) {
    if ($post && $acao === 'instalar') {
        $v = ['codigo' => (string) ($_POST['codigo'] ?? ''), 'nome' => (string) ($_POST['nome'] ?? ''), 'usuario' => trim((string) ($_POST['usuario'] ?? ''))];
        if (!csrfValido()) { http_response_code(403); telaInstalar('A página ficou aberta tempo demais e expirou. Confira os dados e envie de novo.', ['nome' => $v['nome'], 'usuario' => $v['usuario']]); exit; }
        $erro = instalar($v['codigo'], $v['nome'], $v['usuario'], (string) ($_POST['senha'] ?? ''), (string) ($_POST['senha2'] ?? ''));
        if ($erro === null) {
            avisar('Pronto! Seu acesso foi criado, como Administrador. Guarde a senha num lugar seguro. Para outras pessoas usarem o painel, crie um usuário para cada uma em Conta → Usuários.');
            redirecionar('index.php');
        }
        http_response_code(bloqueioRestante() ? 429 : 422);
        telaInstalar($erro, $v);
        exit;
    }
    if ($post) redirecionar(link_('instalar'));
    codigoInstalacao();   // garante que o arquivo com o código exista
    telaInstalar();
    exit;
}

/* ------------------------------------------------------------------
   Sem login: só a tela de entrar (ou "Recuperar acesso", se o dono
   criou o arquivo recuperar.txt na pasta privada pelo cPanel)
   ------------------------------------------------------------------ */
if (usuarioLogado() === null) {
    // Também quando há usuários mas nenhum administrador ativo (ninguém mais conseguiria dar acesso)
    $recuperando = arquivosRecuperacao() !== [] || !instalado();
    if ($post && $acao === 'recuperar' && $recuperando) {
        $v = ['codigo' => (string) ($_POST['codigo'] ?? ''), 'usuario' => trim((string) ($_POST['usuario'] ?? ''))];
        if (!csrfValido()) { http_response_code(403); telaRecuperar('A página ficou aberta tempo demais e expirou. Confira os dados e envie de novo.', ['usuario' => $v['usuario']]); exit; }
        [$erro, $sobrou] = recuperarAcesso($v['codigo'], cortar($v['usuario'], 60), (string) ($_POST['senha'] ?? ''), (string) ($_POST['senha2'] ?? ''));
        if ($erro === null) {
            avisar('Pronto! O acesso foi recuperado e você já entrou como Administrador. Os outros usuários continuam como estavam.');
            if ($sobrou) avisar('Não consegui apagar o arquivo ' . implode(' e ', $sobrou) . ' da pasta privada. Apague pelo Gerenciador de Arquivos do cPanel: enquanto ele existir, quem não está logado vê a tela “Recuperar acesso” em vez da tela de entrar.', 'erro');
            redirecionar('index.php');
        }
        http_response_code(bloqueioRestante() ? 429 : 422);
        telaRecuperar($erro, $v);
        exit;
    }
    if ($post && $acao === 'entrar') {
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        if (!csrfValido()) {
            $msg = 'A página ficou aberta tempo demais e expirou. Digite a senha de novo.';
            if (pedidoJson()) responderJson(['ok' => false, 'msg' => $msg, 'csrf' => tokenCsrf()], 403);
            http_response_code(403); telaEntrar($msg, $usuario); exit;
        }
        $voltar = destinoSeguro($_SESSION['voltar'] ?? null);   // a sessão é trocada ao entrar
        $erro = entrar(cortar($usuario, 60), (string) ($_POST['senha'] ?? ''));
        if ($erro === null) {
            if (pedidoJson()) responderJson(['ok' => true, 'csrf' => tokenCsrf()]);
            redirecionar($voltar ?? 'index.php');
        }
        $bloqueado = bloqueioRestante(cortar($usuario, 60)) > 0;
        if (pedidoJson()) responderJson(['ok' => false, 'msg' => $erro, 'csrf' => tokenCsrf()], $bloqueado ? 429 : 401);
        http_response_code($bloqueado ? 429 : 401);
        telaEntrar($erro, $usuario);
        exit;
    }
    if ($post) {
        if (pedidoJson()) responderJson(['ok' => false, 'login' => true, 'msg' => 'Sua sessão terminou. Entre de novo.', 'motivo' => motivoSaida(), 'csrf' => tokenCsrf()], 401);
        // Depois de entrar, volta para a tela de onde veio (o formulário do produto, com o rascunho)
        $id = is_string($_GET['id'] ?? null) && preg_match(RE_ID, $_GET['id']) ? $_GET['id'] : '';
        if ($acao === 'salvar') {
            $_SESSION['voltar'] = $id !== '' ? link_('editar', ['id' => $id]) : link_('novo');
            avisar('Sua sessão terminou e o produto não foi salvo. Entre de novo: o painel volta para o formulário com o que você tinha digitado (a foto precisa ser escolhida de novo).', 'info');
        } elseif ($acao === 'cliente-salvar') {
            $_SESSION['voltar'] = linkFormCliente($_GET);
            avisar('Sua sessão terminou e o cliente não foi salvo. Entre de novo: o painel volta para o formulário (escolha o logo de novo).', 'info');
        } elseif ($acao === 'empresa-salvar') {
            $_SESSION['voltar'] = link_('empresa');
            avisar('Sua sessão terminou e os dados da empresa não foram salvos. Entre de novo e salve outra vez.', 'info');
        } else {
            avisar('Sua sessão terminou e a última ação não foi feita. Entre de novo para continuar.', 'info');
        }
        redirecionar(link_('entrar'));
    }
    if ($recuperando) {
        codigoInstalacao('recuperar');   // garante que o arquivo com o código exista
        telaRecuperar();
        exit;
    }
    if ($acao !== 'entrar' && $acao !== 'lista') {
        $_SESSION['voltar'] = 'index.php?' . http_build_query(array_map('strval', array_filter($_GET, 'is_string')));
        redirecionar(link_('entrar'));
    }
    telaEntrar();
    exit;
}

/* ------------------------------------------------------------------
   Logado
   ------------------------------------------------------------------ */
if ($acao === 'entrar' || $acao === 'instalar' || $acao === 'recuperar') redirecionar('index.php');
garantirArquivoAjustes();
if ($post && !csrfValido()) {
    registrar('csrf', 'pedido recusado (token ausente ou inválido) em ' . $acao);
    if (pedidoJson()) responderJson(['ok' => false, 'msg' => 'A página ficou desatualizada. Recarregue e tente de novo.'], 403);
    http_response_code(403);
    paginaErro('Ação recusada', 'A página ficou desatualizada (ou o pedido não veio do painel) e nada foi alterado. Volte, recarregue a página e tente de novo.');
    exit;
}
$eu = usuarioAtual();
if ($eu === null) { sair(); redirecionar(link_('entrar')); }   // excluído no meio do caminho
// Entrou com a senha temporária: antes de qualquer coisa, cria a própria senha
if (precisaCriarSenha($eu) && !in_array($acao, SEM_SENHA_PROPRIA, true)) {
    if (pedidoJson()) responderJson(['ok' => false, 'msg' => 'Antes de continuar, crie a sua senha: recarregue a página.'], 403);
    redirecionar(link_('criar-senha'));
}
if (in_array($acao, SO_ADMIN, true) && $eu['papel'] !== 'admin') {
    registrar('negado', nomeDaArea($acao));
    $msg = 'Esta área é só para administradores do painel.';
    if (pedidoJson()) responderJson(['ok' => false, 'msg' => $msg], 403);
    paginaErro('Só para administradores', $msg . ' Se precisar dela, fale com um administrador (ele pode mudar o seu papel em Conta → Usuários).', 403);
    exit;
}
if (!$post && in_array($acao, SO_POST, true)) redirecionar('index.php');

try {
    match ($acao) {
        'clientes'         => telaClientes(lerClientes()),
        'cliente-novo'     => telaCliente(lerClientes(), null),
        'cliente-editar'   => acaoClienteEditar(),
        'cliente-salvar'   => acaoClienteSalvar(),
        'cliente-mover'    => acaoClienteMover(),
        'cliente-excluir'  => acaoClienteExcluir($post),
        'cliente-desfazer' => acaoClienteDesfazer(),
        'lista'     => acaoLista(),
        'novo'      => telaProduto(catalogoOuTela(), null),
        'editar'    => acaoEditar(),
        'salvar'    => acaoSalvar(),
        'rapido'    => acaoRapido(),
        'mover'     => acaoMover(),
        'excluir'   => acaoExcluir($post),
        'desfazer'  => acaoDesfazer(),
        'copias'    => acaoCopias(),
        'restaurar' => acaoRestaurar($post),
        'conta'     => telaConta(),
        'senha'     => acaoSenha(),
        'sair'      => acaoSair(),
        'criar-senha'      => acaoCriarSenha($post),
        'usuarios'         => acaoUsuarios(),
        'usuario-novo'     => telaUsuarioForm(null),
        'usuario-editar'   => acaoUsuarioEditar(),
        'usuario-salvar'   => acaoUsuarioSalvar(),
        'usuario-senha'    => acaoUsuarioSenha($post),
        'usuario-ativo'    => acaoUsuarioAtivo($post),
        'usuario-excluir'  => acaoUsuarioExcluir($post),
        'senha-temporaria' => acaoSenhaTemporaria(),
        'atividades'       => acaoAtividades(),
        // Área Empresa (ajustes do site): lib/ajustes.php e lib/visoes-empresa.php
        'empresa'          => acaoEmpresa(),
        'empresa-salvar'   => acaoEmpresaSalvar(),
    };
} catch (ErroCatalogo $e) {
    if (pedidoJson()) responderJson(['ok' => false, 'msg' => 'Não consegui ler o catálogo: ' . $e->getMessage()], 500);
    telaCatalogoIlegivel($e->getMessage());
} catch (ErroClientes $e) {
    if (pedidoJson()) responderJson(['ok' => false, 'msg' => 'Não consegui ler a lista de clientes: ' . $e->getMessage()], 500);
    telaClientesIlegivel($e->getMessage());
} catch (ErroAjustes $e) {
    if (pedidoJson()) responderJson(['ok' => false, 'msg' => 'Não consegui ler os ajustes do site: ' . $e->getMessage()], 500);
    telaAjustesIlegivel($e->getMessage());
} catch (ErroUsuario $e) {
    if (pedidoJson()) responderJson(['ok' => false, 'msg' => $e->getMessage()], 409);
    avisar($e->getMessage(), 'erro');
    redirecionar(voltarDepoisDoErro($acao));
}
exit;

/* ================================================================== */

/** Endereço interno do painel para voltar depois de entrar (ou null se não for seguro). */
function destinoSeguro($url): ?string
{
    if (!is_string($url) || strlen($url) > 300) return null;
    if (!preg_match('/^index\.php\?a=(novo|editar|copias|conta|clientes|cliente-novo|cliente-editar|usuarios|usuario-novo|usuario-editar|atividades|empresa)(&[A-Za-z0-9_=%.\-]*)*$/', $url)) return null;
    return $url;
}

/** Nome da área de administrador, para o registro ("Tentou abrir… — Usuários"). */
function nomeDaArea(string $acao): string
{
    if (str_starts_with($acao, 'usuario') || $acao === 'senha-temporaria') return 'Usuários';
    if (str_starts_with($acao, 'empresa')) return 'Empresa';
    return ['copias' => 'Cópias de segurança', 'restaurar' => 'Cópias de segurança (restaurar)', 'atividades' => 'Atividades'][$acao] ?? $acao;
}

/** Para onde volta quando uma ação dá erro (ErroUsuario): a lista da mesma área. */
function voltarDepoisDoErro(string $acao): string
{
    if (str_starts_with($acao, 'cliente')) return link_('clientes');
    if (str_starts_with($acao, 'usuario') || $acao === 'usuarios' || $acao === 'senha-temporaria') return link_('usuarios');
    if ($acao === 'criar-senha') return link_('criar-senha');
    if (in_array($acao, ['conta', 'senha', 'atividades'], true)) return link_('conta');
    if (in_array($acao, ['copias', 'restaurar'], true)) return link_('copias');
    if (str_starts_with($acao, 'empresa')) return link_('empresa');
    return 'index.php';
}

function catalogoOuTela(): array
{
    return lerCatalogo();   // ErroCatalogo vira a tela de "catálogo com problema"
}

function filtroDaUrl(array $categorias): array
{
    $cat = is_string($_GET['cat'] ?? null) ? $_GET['cat'] : '';
    $ver = is_string($_GET['ver'] ?? null) ? $_GET['ver'] : '';
    return [
        'q'   => cortar(limparLinha($_GET['q'] ?? '') ?? '', 80),
        'cat' => categoriaPorId($categorias, $cat) ? $cat : '',
        'ver' => in_array($ver, ['site', 'ocultos', 'destaques'], true) ? $ver : '',
    ];
}

function acaoLista(): void
{
    $cat = catalogoOuTela();
    telaLista($cat, filtroDaUrl($cat['categorias']));
}

function produtoDaUrl(array $cat, string $fonte = 'get'): array
{
    $id = $fonte === 'get' ? ($_GET['id'] ?? '') : ($_POST['id'] ?? '');
    $i = is_string($id) && preg_match(RE_ID, $id) ? indicePorId($cat['produtos'], $id) : null;
    if ($i === null) throw new ErroUsuario('Produto não encontrado. Ele pode ter sido excluído ou ter mudado de código.');
    return $cat['produtos'][$i];
}

function acaoEditar(): void
{
    $cat = catalogoOuTela();
    $p = produtoDaUrl($cat);
    telaProduto($cat, $p, [], (string) $p['id']);
}

function linkSite(string $id): array
{
    return ['link' => cfg('url_site') . 'produtos.html#p=' . rawurlencode($id), 'textoLink' => 'Ver no site', 'externo' => true];
}

function acaoSalvar(): void
{
    $cat = catalogoOuTela();
    $original = null;
    $atual = null;
    if (isset($_GET['id'])) {
        $atual = produtoDaUrl($cat);
        $original = (string) $atual['id'];
    }
    [$p, $erros] = produtoDoFormulario($_POST, $cat['categorias'], $cat['produtos'], $atual);

    $arq = $_FILES['foto'] ?? null;
    $temFoto = is_array($arq) && isset($arq['error']) && !is_array($arq['error']) && (int) $arq['error'] !== UPLOAD_ERR_NO_FILE;
    if (!$temFoto && ($atual === null || empty($atual['imagem']))) $erros['foto'] = 'Escolha uma foto para o produto.';
    $foto = null;
    if (!$erros && $temFoto) {
        try { $foto = processarFoto($arq, $p['id']); }
        catch (ErroFoto $e) { $erros['foto'] = $e->getMessage(); }
    } elseif ($erros && $temFoto && !isset($erros['foto'])) {
        $erros['foto'] = 'Corrija os outros campos e escolha a foto de novo (por segurança, o navegador não guarda a foto escolhida).';
    }
    if ($erros) {
        http_response_code(422);
        telaProduto($cat, $p, $erros, $original);
        return;
    }
    if ($foto) {
        $p['imagem'] = $foto['imagem'];
        if ($foto['recorte']) $p['recorte'] = $foto['recorte'];
        else unset($p['recorte']);   // recorte antigo era da foto antiga
        $p = ordenarCampos($p);
    }
    $nome = nomeCompleto($p);
    $mudouCodigo = $original !== null && $p['id'] !== $original;
    $acaoCat = $atual
        ? ['tipo' => 'editar', 'antes' => 'Antes de: editar “' . nomeCompleto($atual) . '”',
           'feito' => 'Editou “' . $nome . '”' . ($mudouCodigo ? ' (código: ' . $original . ' → ' . $p['id'] . ')' : ''), 'alvo' => $p['id'], 'alvoNome' => $nome]
        : ['tipo' => 'cadastrar', 'antes' => 'Antes de: cadastrar “' . $nome . '”', 'feito' => 'Cadastrou “' . $nome . '”', 'alvo' => $p['id'], 'alvoNome' => $nome];
    try {
        $novoCat = alterarCatalogo($acaoCat, static function (array $c) use ($p, $original): array {
            $prods = $c['produtos'];
            if ($original !== null) {
                $i = indicePorId($prods, $original);
                if ($i === null) throw new ErroUsuario('Esse produto foi excluído (talvez em outro aparelho) enquanto você editava.');
                if ($p['id'] !== $original && indicePorId($prods, $p['id']) !== null) throw new ErroUsuario('Já existe outro produto com o código “' . $p['id'] . '”.');
                if (($prods[$i]['categoria'] ?? '') !== $p['categoria']) {
                    array_splice($prods, $i, 1);
                    array_splice($prods, posicaoNaCategoria($prods, $p['categoria']), 0, [$p]);
                } else {
                    $prods[$i] = $p;
                }
            } else {
                if (indicePorId($prods, $p['id']) !== null) throw new ErroUsuario('Já existe um produto com o código “' . $p['id'] . '”.');
                array_splice($prods, posicaoNaCategoria($prods, $p['categoria']), 0, [$p]);
            }
            return ['produtos' => $prods];
        });
    } catch (ErroUsuario $e) {
        if ($foto) descartarFoto($foto);
        http_response_code(409);
        telaProduto($cat, $p, ['geral' => $e->getMessage()], $original);
        return;
    } catch (Throwable $t) {
        if ($foto) descartarFoto($foto);
        throw $t;
    }
    $oculto = ($p['visivel'] ?? true) === false ? ' Ele está oculto: não aparece no site.' : '';
    $extra = ['rascunho' => $atual ? 'editar:' . $original : 'novo'] + ($oculto ? [] : linkSite($p['id']));
    avisar('“' . $nome . '” ' . ($atual ? 'foi salvo.' : 'foi cadastrado.') . $oculto . ($oculto ? '' : fraseHome($p, $novoCat['produtos'])), 'ok', $extra);
    // A capa do site aponta para o código antigo
    $capa = produtoCapa();
    if ($original !== null && $original === $capa && ($mudouCodigo || $oculto)) {
        avisar('Atenção: a capa do site usa este produto pelo código “' . $capa . '”. A foto da capa continua lá, mas o link dela agora abre o catálogo com o aviso “produto fora do catálogo”. '
            . ($mudouCodigo ? 'Para corrigir, volte o código para “' . $capa . '”.' : 'Para corrigir, deixe o produto visível no site.'), 'erro');
    }
    redirecionar('index.php#p-' . $p['id']);
}

function acaoRapido(): void
{
    $campo = is_string($_POST['campo'] ?? null) ? $_POST['campo'] : '';
    $valor = is_string($_POST['valor'] ?? null) ? $_POST['valor'] : '';
    if ($campo === 'estoque') {
        if (!isset(ESTOQUES[$valor])) throw new ErroUsuario('Situação de estoque inválida.');
        $novo = $valor;
    } elseif ($campo === 'destaque' || $campo === 'visivel') {
        if ($valor !== '0' && $valor !== '1') throw new ErroUsuario('Valor inválido.');
        $novo = $valor === '1';
    } else {
        throw new ErroUsuario('Ação inválida.');
    }
    $p = produtoDaUrl(catalogoOuTela(), 'post');
    $nome = nomeCompleto($p);
    $atual = $campo === 'visivel' ? (($p['visivel'] ?? true) !== false) : ($campo === 'destaque' ? !empty($p['destaque']) : ($p['estoque'] ?? 'consulte'));
    $txt = match ($campo) {
        'estoque'  => '“' . $nome . '”: ' . ESTOQUES[$novo] . '.',
        'destaque' => $novo ? '“' . $nome . '” agora está em destaque.' : '“' . $nome . '” saiu do destaque.',
        'visivel'  => $novo ? '“' . $nome . '” voltou para o site.' : '“' . $nome . '” foi escondido do site.',
    };
    $produtos = null;
    if ($atual !== $novo) {
        $frase = match ($campo) {
            'estoque'  => ['mudar o estoque de “' . $nome . '” para ' . ESTOQUES[$novo], 'Mudou o estoque de “' . $nome . '” para ' . ESTOQUES[$novo]],
            'destaque' => $novo ? ['pôr “' . $nome . '” em destaque', 'Pôs “' . $nome . '” em destaque'] : ['tirar “' . $nome . '” do destaque', 'Tirou “' . $nome . '” do destaque'],
            'visivel'  => $novo ? ['mostrar “' . $nome . '” no site', 'Mostrou “' . $nome . '” no site'] : ['esconder “' . $nome . '” do site', 'Escondeu “' . $nome . '” do site'],
        };
        $produtos = alterarCatalogo(['tipo' => $campo, 'antes' => 'Antes de: ' . $frase[0], 'feito' => $frase[1], 'alvo' => $p['id'], 'alvoNome' => $nome],
            static function (array $c) use ($p, $campo, $novo): array {
                $prods = $c['produtos'];
                $i = indicePorId($prods, (string) $p['id']);
                if ($i === null) throw new ErroUsuario('Produto não encontrado.');
                $prods[$i][$campo] = $novo;
                $prods[$i] = ordenarCampos($prods[$i]);
                return ['produtos' => $prods];
            })['produtos'];
    }
    $produtos ??= catalogoOuTela()['produtos'];
    $depois = $p;
    $depois[$campo] = $novo;
    if ($campo === 'destaque' && $novo) $txt .= fraseHome($depois, $produtos);
    if ($campo === 'visivel' && !$novo && (string) $p['id'] === produtoCapa()) {
        $txt .= ' Atenção: é o produto da capa. A foto da capa continua lá, mas o link dela agora abre o catálogo com o aviso “produto fora do catálogo”.';
    }
    if (pedidoJson()) responderJson(['ok' => true, 'msg' => $txt, 'id' => $p['id'], 'campo' => $campo, 'valor' => is_bool($novo) ? ($novo ? '1' : '0') : $novo, 'home' => destaquesDaHome($produtos)]);
    avisar($txt);
    redirecionar('index.php#p-' . $p['id']);
}

function acaoMover(): void
{
    $dir = ($_POST['dir'] ?? '') === 'cima' ? 'cima' : 'baixo';
    $p = produtoDaUrl(catalogoOuTela(), 'post');
    $vizinho = null;
    $nome = nomeCompleto($p);
    $novoCat = alterarCatalogo(['tipo' => 'ordem', 'antes' => 'Antes de: mudar a ordem de “' . $nome . '”', 'feito' => 'Mudou a ordem de “' . $nome . '”', 'alvo' => $p['id'], 'alvoNome' => $nome],
        static function (array $c) use ($p, $dir, &$vizinho): array {
        $novos = moverProduto($c['produtos'], (string) $p['id'], $dir);
        if ($novos === null) throw new ErroUsuario('“' . nomeCompleto($p) . '” já é o ' . ($dir === 'cima' ? 'primeiro' : 'último') . ' da categoria.');
        $i = indicePorId($c['produtos'], (string) $p['id']);
        $vizinho = $novos[$i]['id'];   // quem ocupou o lugar dele
        return ['produtos' => $novos];
    });
    $txt = '“' . nomeCompleto($p) . '” ' . ($dir === 'cima' ? 'subiu' : 'desceu') . ' uma posição.';
    if (pedidoJson()) responderJson(['ok' => true, 'msg' => $txt, 'id' => $p['id'], 'dir' => $dir, 'com' => $vizinho, 'home' => destaquesDaHome($novoCat['produtos'])]);
    avisar($txt);
    redirecionar('index.php#p-' . $p['id']);
}

function acaoExcluir(bool $post): void
{
    $cat = catalogoOuTela();
    $p = produtoDaUrl($cat, $post ? 'post' : 'get');
    $nome = nomeCompleto($p);
    $ehCapa = (string) $p['id'] === produtoCapa();
    if (!$post || ($_POST['confirmado'] ?? '') !== '1') {
        telaConfirmar('Excluir produto?', textoExcluir($p),
            link_('excluir'), ['id' => (string) $p['id']], 'Sim, excluir', 'index.php#p-' . $p['id']);
        return;
    }
    $r = alterarCatalogo(['tipo' => 'excluir', 'antes' => 'Antes de: excluir “' . $nome . '”', 'feito' => 'Excluiu “' . $nome . '”', 'alvo' => $p['id'], 'alvoNome' => $nome],
        static function (array $c) use ($p): array {
            $prods = $c['produtos'];
            $i = indicePorId($prods, (string) $p['id']);
            if ($i === null) throw new ErroUsuario('Esse produto já tinha sido excluído.');
            array_splice($prods, $i, 1);
            return ['produtos' => $prods];
        });
    guardarDesfazer($r['copia']);
    avisar('“' . $nome . '” foi excluído.' . ($ehCapa ? ' Atenção: era o produto da capa; a foto da capa continua, mas o link dela agora abre o catálogo com o aviso “produto fora do catálogo”.' : ''), 'ok',
        ['form' => ['acao' => link_('desfazer'), 'campos' => ['copia' => $r['copia'], 'id' => (string) $p['id']], 'botao' => 'Desfazer']]);
    redirecionar('index.php');
}

/** Devolve ao catálogo um produto excluído, no mesmo lugar e com a foto (vinda da lixeira). */
function acaoDesfazer(): void
{
    $nomeCopia = is_string($_POST['copia'] ?? null) ? $_POST['copia'] : '';
    $id = is_string($_POST['id'] ?? null) && preg_match(RE_ID, $_POST['id']) ? $_POST['id'] : '';
    $copia = lerCopia($nomeCopia);
    exigirCopiaPropria($copia, $nomeCopia, $id);
    $i = $id !== '' ? indicePorId($copia['produtos'], $id) : null;
    if ($i === null) throw new ErroUsuario('Esse produto não está nessa cópia de segurança.');
    $p = $copia['produtos'][$i];
    $nome = nomeCompleto($p);
    alterarCatalogo(['tipo' => 'desfazer', 'antes' => 'Antes de: trazer de volta “' . $nome . '”', 'feito' => 'Trouxe de volta “' . $nome . '”', 'alvo' => $id, 'alvoNome' => $nome],
        static function (array $c) use ($copia, $i, $p, $nome): array {
            $prods = $c['produtos'];
            if (indicePorId($prods, (string) $p['id']) !== null) throw new ErroUsuario('“' . $nome . '” já está no catálogo.');
            if (!categoriaPorId($c['categorias'], (string) ($p['categoria'] ?? ''))) throw new ErroUsuario('A categoria de “' . $nome . '” não existe mais. Use Cópias de segurança → Restaurar.');
            // Mesmo lugar: logo depois do vizinho de cima (da mesma categoria) que ainda existir…
            $pos = null;
            for ($j = $i - 1; $j >= 0 && $pos === null; $j--) {
                if (($copia['produtos'][$j]['categoria'] ?? '') !== $p['categoria']) continue;
                $k = indicePorId($prods, (string) $copia['produtos'][$j]['id']);
                if ($k !== null) $pos = $k + 1;
            }
            // …ou logo antes do vizinho de baixo; senão, no fim da categoria
            for ($j = $i + 1; $j < count($copia['produtos']) && $pos === null; $j++) {
                if (($copia['produtos'][$j]['categoria'] ?? '') !== $p['categoria']) continue;
                $k = indicePorId($prods, (string) $copia['produtos'][$j]['id']);
                if ($k !== null) $pos = $k;
            }
            array_splice($prods, $pos ?? posicaoNaCategoria($prods, (string) $p['categoria']), 0, [$p]);
            return ['produtos' => $prods];
        });
    unset($_SESSION['desfazer'][$nomeCopia]);
    $semFoto = is_string($p['imagem'] ?? null) && $p['imagem'] !== '' && !@is_file(pastaFotos() . '/' . $p['imagem']);
    avisar('“' . $nome . '” voltou para o catálogo, no mesmo lugar' . ($semFoto ? '. A foto não estava mais na lixeira: escolha outra em Editar.' : ' e com a foto.'), $semFoto ? 'erro' : 'ok',
        ($p['visivel'] ?? true) === false ? [] : linkSite($id));
    redirecionar('index.php#p-' . $id);
}

function acaoCopias(): void
{
    if (($_GET['de'] ?? '') === 'empresa') { acaoCopiasAjustes(); return; }
    if (($_GET['de'] ?? '') === 'clientes') {
        try { $atual = lerClientes()['clientes']; } catch (ErroClientes $e) { $atual = null; }
        $logos = $atual === null ? [] : array_fill_keys(array_map(static fn($c) => (string) ($c['logo'] ?? ''), $atual), true);
        telaCopiasClientes(listarCopiasClientes(), $atual === null ? null : count($atual), $logos);
        return;
    }
    try { $prods = lerCatalogo()['produtos']; } catch (ErroCatalogo $e) { $prods = null; }
    $ids = [];
    foreach ($prods ?? [] as $p) $ids[(string) ($p['id'] ?? '')] = true;
    telaCopias(listarCopias(), $prods === null ? null : count($prods), $ids);
}

function acaoRestaurar(bool $post): void
{
    $nome = is_string($_POST['copia'] ?? null) ? $_POST['copia'] : (is_string($_GET['copia'] ?? null) ? $_GET['copia'] : '');
    if (preg_match(RE_COPIA_CLIENTES, $nome)) { acaoRestaurarClientes($post, $nome); return; }
    if (preg_match(RE_COPIA_AJUSTES, $nome)) { acaoRestaurarAjustes($post, $nome); return; }
    $copia = lerCopia($nome);
    $quando = quando((int) ($copia['criado'] ?? 0));
    if (!$post || ($_POST['confirmado'] ?? '') !== '1') {
        $lista = listarCopias();
        $i = array_search($nome, array_column($lista, 'nome'), true);
        try { $total = count(lerCatalogo()['produtos']); } catch (ErroCatalogo $e) { $total = null; }
        telaConfirmar('Restaurar esta cópia?', $i === false ? 'Voltar o catálogo inteiro para como estava em ' . $quando . '? O estado atual vira uma cópia, então dá para desfazer.' : textoRestaurar($lista, (int) $i, $total),
            link_('restaurar'), ['copia' => $nome], 'Sim, restaurar', link_('copias'), 'copias');
        return;
    }
    $acaoCat = ['tipo' => 'restaurar', 'antes' => 'Antes de: restaurar a cópia de ' . $quando, 'feito' => 'Restaurou a cópia de ' . $quando];
    try {
        alterarCatalogo($acaoCat, static fn(array $c): array => ['categorias' => $copia['categorias'], 'produtos' => $copia['produtos']]);
    } catch (ErroCatalogo $e) {
        // O arquivo atual está ilegível: guarda o que havia e grava a cópia por cima
        comTrava('catalogo', static function () use ($copia, $acaoCat): void {
            $bruto = @file_get_contents(arquivoCatalogo());
            if ($bruto !== false) gravarPrivado('copias/ilegivel-' . date('Ymd-His') . '.php', base64_encode($bruto));
            gravarCatalogo($copia['categorias'], $copia['produtos']);
            sincronizarFotos([], $copia['produtos']);
            registrar('catalogo', $acaoCat['feito'] . ' (o arquivo atual estava ilegível)');
        });
    }
    avisar('Pronto: o catálogo voltou para como estava em ' . $quando . '.');
    redirecionar('index.php');
}

/** O "Desfazer" logo depois de excluir vale para todos, mas só para o item daquela exclusão
 *  ($alvo: código do produto ou logo do cliente; sem logo, confere também o nome). Toda cópia
 *  guarda a lista inteira: trazer de volta o que OUTRA pessoa excluiu (ou outro item da cópia)
 *  é coisa das Cópias de segurança, que são só dos administradores. */
/** Guarda na sessão a cópia de uma exclusão: o Desfazer dela vale uma vez, nesta sessão. */
function guardarDesfazer(string $nomeCopia): void
{
    $d = is_array($_SESSION['desfazer'] ?? null) ? $_SESSION['desfazer'] : [];
    $d[$nomeCopia] = time();
    $_SESSION['desfazer'] = array_slice($d, -20, null, true);
}

function exigirCopiaPropria(array $copia, string $nomeCopia, string $alvo, ?string $alvoNome = null): void
{
    if (ehAdmin()) return;
    // Editor: só o Desfazer da exclusão que ele mesmo acabou de fazer, uma vez (reenviar um Desfazer
    // antigo traria de volta algo que um administrador excluiu depois)
    if (!isset($_SESSION['desfazer'][$nomeCopia])) {
        throw new ErroUsuario('Esse Desfazer já foi usado ou expirou. Para trazer o item de volta, fale com um administrador (Conta → Cópias de segurança).');
    }
    if (strcasecmp((string) ($copia['usuario'] ?? ''), (string) usuarioLogado()) !== 0) {
        throw new ErroUsuario('Só um administrador pode trazer de volta o que outra pessoa excluiu (Conta → Cópias de segurança).');
    }
    if (($copia['tipo'] ?? '') !== 'excluir' || $alvo === '' && $alvoNome === null || (string) ($copia['alvo'] ?? '') !== $alvo
        || ($alvoNome !== null && (string) ($copia['alvoNome'] ?? '') !== $alvoNome)) {
        throw new ErroUsuario('Esse Desfazer só traz de volta o que você acabou de excluir. Para trazer outro item, fale com um administrador (Conta → Cópias de segurança).');
    }
}

/** Primeiro acesso de quem recebeu senha temporária (ou teve a senha redefinida). */
function acaoCriarSenha(bool $post): void
{
    if (!precisaCriarSenha(usuarioAtual())) redirecionar('index.php');
    if (!$post) { telaCriarSenha(); return; }
    $erro = criarSenhaPropria((string) ($_POST['nova'] ?? ''), (string) ($_POST['nova2'] ?? ''));
    if ($erro !== null) {
        http_response_code(422);
        telaCriarSenha($erro);
        return;
    }
    avisar('Pronto! Sua senha foi criada. Use ela nas próximas vezes que entrar no painel.');
    redirecionar('index.php');
}

function acaoSenha(): void
{
    $erro = trocarSenha((string) ($_POST['atual'] ?? ''), (string) ($_POST['nova'] ?? ''), (string) ($_POST['nova2'] ?? ''));
    if ($erro !== null) {
        $bloqueado = bloqueioRestante(usuarioLogado() ?? '') > 0;
        http_response_code($bloqueado ? 429 : 422);
        if ($bloqueado) { sair(); avisar($erro, 'erro'); redirecionar(link_('entrar')); }
        telaConta($erro);
        return;
    }
    avisar('Senha trocada. Nos outros aparelhos, será preciso entrar de novo.');
    redirecionar(link_('conta'));
}

function acaoSair(): void
{
    sair();
    avisar('Você saiu do painel.', 'info');
    redirecionar(link_('entrar'));
}

/* ==================================================================
   Clientes (logos do site) — lib/clientes.php e telas em lib/visoes.php
   ================================================================== */

/** Endereço do formulário do cliente a partir de n/logo (novo, se não vierem). */
function linkFormCliente(array $origem): string
{
    [$i, $logo] = refCliente($origem);
    return $i >= 0 && $logo !== null && ($logo === '' || preg_match(RE_ARQUIVO_LOGO, $logo))
        ? link_('cliente-editar', ['n' => $i + 1, 'logo' => $logo]) : link_('cliente-novo');
}

function linkSiteClientes(): array
{
    return ['link' => cfg('url_site') . 'empresa.html#clientes', 'textoLink' => 'Ver no site', 'externo' => true];
}

/** [posição, cliente] pelo n/logo do endereço (GET) ou do formulário (POST). */
function clienteDaUrl(array $lista, string $fonte = 'get'): array
{
    [$n, $logo] = refCliente($fonte === 'get' ? $_GET : $_POST);
    $i = acharCliente($lista, $n, $logo);
    if ($i === null) throw new ErroUsuario('Cliente não encontrado. Ele pode ter sido excluído ou alterado em outro aparelho.');
    return [$i, $lista[$i]];
}

function acaoClienteEditar(): void
{
    $dados = lerClientes();
    [$i, $c] = clienteDaUrl($dados['clientes']);
    telaCliente($dados, $c, [], ['n' => $i + 1, 'logo' => (string) ($c['logo'] ?? '')]);
}

function acaoClienteSalvar(): void
{
    $dados = lerClientes();
    $lista = $dados['clientes'];
    $atual = null; $ref = null; $pos = null;
    if (isset($_GET['logo'])) {
        [$pos, $atual] = clienteDaUrl($lista);
        $ref = ['n' => $pos + 1, 'logo' => (string) ($atual['logo'] ?? '')];
    }
    [$nome, $erroNome] = nomeClienteValido($_POST['nome'] ?? '');
    $erros = [];
    if ($erroNome !== null) $erros['nome'] = $erroNome;
    elseif (nomeRepetido($lista, $nome, $pos)) $erros['nome'] = 'Nome: já existe um cliente chamado “' . $nome . '”. Se for outra loja, acrescente a cidade ou o bairro.';

    $arq = $_FILES['logo'] ?? null;
    $temLogo = is_array($arq) && isset($arq['error']) && !is_array($arq['error']) && (int) $arq['error'] !== UPLOAD_ERR_NO_FILE;
    if (!$temLogo && ($atual === null || (string) ($atual['logo'] ?? '') === '')) $erros['logo'] = 'Escolha o arquivo do logo (PNG, JPG ou WebP).';
    $logo = null;
    if (!$erros && $temLogo) {
        try { $logo = processarLogo($arq, $nome); }
        catch (ErroFoto $e) { $erros['logo'] = $e->getMessage(); }
    } elseif ($erros && $temLogo && !isset($erros['logo'])) {
        $erros['logo'] = 'Corrija o nome e escolha o logo de novo (por segurança, o navegador não guarda o arquivo escolhido).';
    }
    $novo = ['nome' => $nome, 'logo' => $logo ? $logo['logo'] : (string) ($atual['logo'] ?? '')] + ($atual ?? []);
    if ($erros) {
        http_response_code(422);
        telaCliente($dados, $novo, $erros, $ref);
        return;
    }
    $acao = $atual
        ? ['tipo' => 'editar', 'antes' => 'Antes de: editar o cliente “' . $atual['nome'] . '”',
           'feito' => 'Editou o cliente “' . $nome . '”' . ($logo ? ' (logo novo)' : ''), 'alvo' => $novo['logo'], 'alvoNome' => $nome]
        : ['tipo' => 'cadastrar', 'antes' => 'Antes de: cadastrar o cliente “' . $nome . '”', 'feito' => 'Cadastrou o cliente “' . $nome . '”', 'alvo' => $novo['logo'], 'alvoNome' => $nome];
    $posFinal = 0;
    try {
        alterarClientes($acao, static function (array $l) use ($novo, $ref, &$posFinal): array {
            if ($ref !== null) {
                $j = acharCliente($l, $ref['n'] - 1, $ref['logo']);
                if ($j === null) throw new ErroUsuario('Esse cliente foi excluído (talvez em outro aparelho) enquanto você editava.');
                $l[$j] = $novo;
                $posFinal = $j + 1;
            } else {
                $l[] = $novo;
                $posFinal = count($l);
            }
            return $l;
        });
    } catch (ErroUsuario $e) {
        if ($logo) descartarLogo($logo);
        http_response_code(409);
        telaCliente($dados, $novo, ['geral' => $e->getMessage()], $ref);
        return;
    } catch (Throwable $t) {
        if ($logo) descartarLogo($logo);
        throw $t;
    }
    $msg = '“' . $nome . '” ' . ($atual ? 'foi salvo.' : 'foi cadastrado. Ele entrou no fim da lista: use as setas para mudar a posição no site.');
    if (!$dados['existe']) $msg .= ' A lista de clientes foi criada agora, só com este cliente: cadastre também os outros clientes que devem continuar aparecendo no site.';
    avisar($msg, 'ok', linkSiteClientes());
    redirecionar(link_('clientes') . '#c-' . $posFinal);
}

function acaoClienteMover(): void
{
    $dir = ($_POST['dir'] ?? '') === 'cima' ? 'cima' : 'baixo';
    [$i, $c] = clienteDaUrl(lerClientes()['clientes'], 'post');
    [$n, $logo] = refCliente($_POST);
    $nome = (string) $c['nome'];
    $novaPos = 0;
    alterarClientes(['tipo' => 'ordem', 'antes' => 'Antes de: mudar a ordem do cliente “' . $nome . '”', 'feito' => 'Mudou a ordem do cliente “' . $nome . '”', 'alvo' => (string) ($c['logo'] ?? ''), 'alvoNome' => $nome],
        static function (array $l) use ($n, $logo, $dir, $nome, &$novaPos): array {
            $j = acharCliente($l, $n, $logo);
            if ($j === null) throw new ErroUsuario('Cliente não encontrado. Recarregue a página.');
            $k = $dir === 'cima' ? $j - 1 : $j + 1;
            if ($k < 0 || $k >= count($l)) throw new ErroUsuario('“' . $nome . '” já é o ' . ($dir === 'cima' ? 'primeiro' : 'último') . ' da lista.');
            [$l[$j], $l[$k]] = [$l[$k], $l[$j]];
            $novaPos = $k + 1;
            return $l;
        });
    $txt = '“' . $nome . '” ' . ($dir === 'cima' ? 'subiu' : 'desceu') . ' para a ' . $novaPos . 'ª posição.';
    if (pedidoJson()) responderJson(['ok' => true, 'msg' => $txt, 'dir' => $dir, 'pos' => $novaPos]);
    avisar($txt);
    redirecionar(link_('clientes') . '#c-' . $novaPos);
}

function acaoClienteExcluir(bool $post): void
{
    [$i, $c] = clienteDaUrl(lerClientes()['clientes'], $post ? 'post' : 'get');
    $nome = (string) $c['nome'];
    $logo = (string) ($c['logo'] ?? '');
    if (!$post || ($_POST['confirmado'] ?? '') !== '1') {
        telaConfirmar('Excluir cliente?', textoExcluirCliente($c), link_('cliente-excluir'), ['n' => (string) ($i + 1), 'logo' => $logo],
            'Sim, excluir', link_('clientes') . '#c-' . ($i + 1), 'clientes');
        return;
    }
    [$n, $ref] = refCliente($_POST);
    $r = alterarClientes(['tipo' => 'excluir', 'antes' => 'Antes de: excluir o cliente “' . $nome . '”', 'feito' => 'Excluiu o cliente “' . $nome . '”', 'alvo' => $logo, 'alvoNome' => $nome],
        static function (array $l) use ($n, $ref): array {
            $j = acharCliente($l, $n, $ref);
            if ($j === null) throw new ErroUsuario('Esse cliente já tinha sido excluído.');
            array_splice($l, $j, 1);
            return $l;
        });
    guardarDesfazer($r['copia']);
    avisar('“' . $nome . '” foi excluído da lista de clientes.', 'ok',
        ['form' => ['acao' => link_('cliente-desfazer'), 'campos' => ['copia' => $r['copia'], 'n' => (string) ($i + 1), 'logo' => $logo], 'botao' => 'Desfazer']]);
    redirecionar(link_('clientes'));
}

/** Devolve à lista um cliente excluído, no mesmo lugar e com o logo (vindo da lixeira). */
function acaoClienteDesfazer(): void
{
    $nomeCopia = is_string($_POST['copia'] ?? null) ? $_POST['copia'] : '';
    $copia = lerCopiaClientes($nomeCopia);
    [$n, $logo] = refCliente($_POST);
    $i = acharCliente($copia['clientes'], $n, $logo);
    if ($i === null) throw new ErroUsuario('Esse cliente não está nessa cópia de segurança.');
    $c = $copia['clientes'][$i];
    $nome = (string) $c['nome'];
    exigirCopiaPropria($copia, $nomeCopia, (string) ($c['logo'] ?? ''), (string) ($c['logo'] ?? '') === '' ? $nome : null);
    $pos = 0;
    alterarClientes(['tipo' => 'desfazer', 'antes' => 'Antes de: trazer de volta o cliente “' . $nome . '”', 'feito' => 'Trouxe de volta o cliente “' . $nome . '”', 'alvo' => (string) ($c['logo'] ?? ''), 'alvoNome' => $nome],
        static function (array $l) use ($copia, $i, $c, $nome, &$pos): array {
            $logo = (string) ($c['logo'] ?? '');
            foreach ($l as $x) {
                if (($logo !== '' && (string) ($x['logo'] ?? '') === $logo) || ($logo === '' && (string) ($x['nome'] ?? '') === $nome)) {
                    throw new ErroUsuario('“' . $nome . '” já está na lista de clientes.');
                }
            }
            // Mesmo lugar: logo depois do vizinho de cima que ainda existir, ou antes do de baixo; senão, no fim
            $onde = null;
            for ($j = $i - 1; $j >= 0 && $onde === null; $j--) {
                $k = acharCliente($l, -1, (string) ($copia['clientes'][$j]['logo'] ?? ''));
                if ($k !== null) $onde = $k + 1;
            }
            for ($j = $i + 1; $j < count($copia['clientes']) && $onde === null; $j++) {
                $k = acharCliente($l, -1, (string) ($copia['clientes'][$j]['logo'] ?? ''));
                if ($k !== null) $onde = $k;
            }
            $onde ??= count($l);
            array_splice($l, $onde, 0, [$c]);
            $pos = $onde + 1;
            return $l;
        });
    unset($_SESSION['desfazer'][$nomeCopia]);
    $semLogo = (string) ($c['logo'] ?? '') !== '' && !@is_file(pastaFotos('clientes') . '/' . $c['logo']);
    avisar('“' . $nome . '” voltou para a lista de clientes, na ' . $pos . 'ª posição' . ($semLogo ? '. O logo não estava mais na lixeira: escolha outro em Editar.' : ', com o logo.'),
        $semLogo ? 'erro' : 'ok', $semLogo ? [] : linkSiteClientes());
    redirecionar(link_('clientes') . '#c-' . $pos);
}

function acaoRestaurarClientes(bool $post, string $nome): void
{
    $copia = lerCopiaClientes($nome);
    $quando = quando((int) ($copia['criado'] ?? 0));
    if (!$post || ($_POST['confirmado'] ?? '') !== '1') {
        $lista = listarCopiasClientes();
        $i = array_search($nome, array_column($lista, 'nome'), true);
        try { $total = count(lerClientes()['clientes']); } catch (ErroClientes $e) { $total = null; }
        telaConfirmar('Restaurar esta cópia?', $i === false ? 'Voltar a lista de clientes inteira para como estava em ' . $quando . '? O estado atual vira uma cópia, então dá para desfazer.' : textoRestaurarClientes($lista, (int) $i, $total),
            link_('restaurar'), ['copia' => $nome], 'Sim, restaurar', link_('copias', ['de' => 'clientes']), 'copias');
        return;
    }
    $acao = ['tipo' => 'restaurar', 'antes' => 'Antes de: restaurar a cópia dos clientes de ' . $quando, 'feito' => 'Restaurou a cópia dos clientes de ' . $quando];
    try {
        alterarClientes($acao, static fn(array $l): array => $copia['clientes']);
    } catch (ErroClientes $e) {
        // O arquivo atual está ilegível: guarda o que havia e grava a cópia por cima
        comTrava('clientes', static function () use ($copia, $acao): void {
            $bruto = @file_get_contents(arquivoClientes());
            if ($bruto !== false) gravarPrivado('copias/ilegivel-clientes-' . date('Ymd-His') . '.php', base64_encode($bruto));
            gravarClientes($copia['clientes']);
            protegerPastasFotos();
            sincronizarLogos([], $copia['clientes']);
            registrar('clientes', $acao['feito'] . ' (o arquivo atual estava ilegível)');
        });
    }
    avisar('Pronto: a lista de clientes voltou para como estava em ' . $quando . '.', 'ok', linkSiteClientes());
    redirecionar(link_('clientes'));
}
