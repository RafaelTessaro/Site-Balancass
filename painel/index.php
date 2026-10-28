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
require __DIR__ . '/lib/ordem.php';       // "Mudar lugar" (antes do lote.php: lote.php usa posicaoNaSubcategoria())
require __DIR__ . '/lib/ajustes.php';
require __DIR__ . '/lib/pacotes.php';
require __DIR__ . '/lib/estatisticas.php';   // Mais procurados (contagem anônima do site; contar.php usa o mesmo)
require __DIR__ . '/lib/lote.php';
require __DIR__ . '/lib/categorias.php';     // depois do lote.php: usa moverProdutosPara()
require __DIR__ . '/lib/visoes.php';
require __DIR__ . '/lib/visoes-usuarios.php';
require __DIR__ . '/lib/visoes-empresa.php';
require __DIR__ . '/lib/visoes-pacotes.php';
require __DIR__ . '/lib/visoes-procurados.php';
require __DIR__ . '/lib/visoes-categorias.php';

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

const ACOES = ['lista', 'novo', 'editar', 'salvar', 'rapido', 'mover', 'lugar', 'excluir', 'desfazer', 'lote', 'lote-desfazer', 'copias', 'restaurar', 'conta', 'senha', 'sair', 'entrar', 'instalar', 'sessao',
    'clientes', 'cliente-novo', 'cliente-editar', 'cliente-salvar', 'cliente-mover', 'cliente-lugar', 'cliente-excluir', 'cliente-desfazer',
    'usuarios', 'usuario-novo', 'usuario-editar', 'usuario-salvar', 'usuario-senha', 'usuario-ativo', 'usuario-excluir', 'senha-temporaria',
    'atividades', 'criar-senha', 'recuperar', 'empresa', 'empresa-salvar', 'pacote', 'pacote-aplicar', 'pacote-dispensar',
    'procurados', 'categorias', 'categoria-salvar', 'categoria-mover', 'categoria-excluir', 'subcategoria-salvar', 'subcategoria-mover', 'subcategoria-excluir'];
const SO_POST = ['salvar', 'rapido', 'mover', 'desfazer', 'lote', 'lote-desfazer', 'senha', 'sair', 'cliente-salvar', 'cliente-mover', 'cliente-desfazer', 'usuario-salvar', 'empresa-salvar',
    'pacote-aplicar', 'pacote-dispensar', 'categoria-salvar', 'categoria-mover', 'subcategoria-salvar', 'subcategoria-mover'];
// Só administradores. O papel é lido do usuarios.php a cada pedido (mudou, vale na hora).
// Vale para GET, POST e fetch (JSON): editor recebe 403.
const SO_ADMIN = ['usuarios', 'usuario-novo', 'usuario-editar', 'usuario-salvar', 'usuario-senha', 'usuario-ativo', 'usuario-excluir', 'senha-temporaria',
    'atividades', 'copias', 'restaurar', 'empresa', 'empresa-salvar', 'pacote', 'pacote-aplicar', 'pacote-dispensar',
    'categorias', 'categoria-salvar', 'categoria-mover', 'categoria-excluir', 'subcategoria-salvar', 'subcategoria-mover', 'subcategoria-excluir'];
// "Mais procurados" (procurados) fica de fora: todos os usuários veem (só leitura)
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
        } elseif ($acao === 'cliente-lugar') {
            [$iL, $logoL] = refCliente($_POST);
            $_SESSION['voltar'] = $iL >= 0 && $logoL !== null && ($logoL === '' || preg_match(RE_ARQUIVO_LOGO, $logoL)) ? link_('cliente-lugar', ['n' => $iL + 1, 'logo' => $logoL]) : link_('clientes');
            avisar('Sua sessão terminou e o cliente não mudou de lugar. Entre de novo e escolha o lugar outra vez.', 'info');
        } elseif ($acao === 'lugar') {
            $idL = is_string($_POST['id'] ?? null) && preg_match(RE_ID, $_POST['id']) ? $_POST['id'] : '';
            $_SESSION['voltar'] = $idL !== '' ? link_('lugar', ['id' => $idL] + array_filter(filtroDoPost(), static fn($x) => $x !== '')) : 'index.php';
            avisar('Sua sessão terminou e o produto não mudou de lugar. Entre de novo e escolha o lugar outra vez.', 'info');
        } elseif ($acao === 'empresa-salvar') {
            $_SESSION['voltar'] = link_('empresa');
            avisar('Sua sessão terminou e os dados da empresa não foram salvos. Entre de novo e salve outra vez.', 'info');
        } elseif (str_starts_with($acao, 'categoria') || str_starts_with($acao, 'subcategoria')) {
            $_SESSION['voltar'] = link_('categorias');
            avisar('Sua sessão terminou e nada mudou nas categorias. Entre de novo e repita a mudança.', 'info');
        } elseif ($acao === 'lote') {
            avisar('Sua sessão terminou e nada mudou nos produtos marcados. Entre de novo, marque os produtos e repita a ação.', 'info');
        } elseif ($acao === 'pacote-aplicar') {
            $idPac = is_string($_POST['id'] ?? null) && preg_match(RE_PACOTE, $_POST['id']) ? $_POST['id'] : '';
            $_SESSION['voltar'] = $idPac !== '' ? link_('pacote', ['id' => $idPac]) : link_('pacote');
            avisar('Sua sessão terminou e a atualização do catálogo não foi aplicada. Entre de novo, confira as mudanças e aplique outra vez.', 'info');
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
        'cliente-lugar'    => acaoClienteLugar($post),
        'cliente-excluir'  => acaoClienteExcluir($post),
        'cliente-desfazer' => acaoClienteDesfazer(),
        'lista'     => acaoLista(),
        'novo'      => telaProduto(catalogoOuTela(), null),
        'editar'    => acaoEditar(),
        'salvar'    => acaoSalvar(),
        'rapido'    => acaoRapido(),
        'mover'     => acaoMover(),
        'lugar'     => acaoLugar($post),   // "Mudar lugar": GET mostra a lista de lugares (sem JavaScript), POST grava
        'excluir'   => acaoExcluir($post),
        'desfazer'  => acaoDesfazer(),
        // Edição em lote (vários produtos de uma vez): lib/lote.php
        'lote'          => acaoLote(),
        'lote-desfazer' => acaoLoteDesfazer(),
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
        // Atualização do catálogo (pacotes em painel/pacotes/): lib/pacotes.php e lib/visoes-pacotes.php
        'pacote'           => acaoPacote(),
        'pacote-aplicar'   => acaoPacoteAplicar(),
        'pacote-dispensar' => acaoPacoteDispensar(),
        // Mais procurados (contagem anônima do site): lib/estatisticas.php e lib/visoes-procurados.php
        'procurados'       => acaoProcurados(),
        // Categorias e subcategorias (só administradores): lib/categorias.php e lib/visoes-categorias.php
        'categorias'           => acaoCategorias(),
        'categoria-salvar'     => acaoCategoriaSalvar(),
        'categoria-mover'      => acaoCategoriaMover(),
        'categoria-excluir'    => acaoCategoriaExcluir($post),
        'subcategoria-salvar'  => acaoSubcategoriaSalvar(),
        'subcategoria-mover'   => acaoSubcategoriaMover(),
        'subcategoria-excluir' => acaoSubcategoriaExcluir($post),
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
    // "+" é o espaço da busca (http_build_query): ?a=lugar&id=…&q=prix+3
    if (!preg_match('/^index\.php\?a=(novo|editar|lugar|copias|conta|clientes|cliente-novo|cliente-editar|cliente-lugar|usuarios|usuario-novo|usuario-editar|atividades|empresa|pacote|procurados|categorias)(&[A-Za-z0-9_=%.+\-]*)*$/', $url)) return null;
    return $url;
}

/** Nome da área de administrador, para o registro ("Tentou abrir… — Usuários"). */
function nomeDaArea(string $acao): string
{
    if (str_starts_with($acao, 'usuario') || $acao === 'senha-temporaria') return 'Usuários';
    if (str_starts_with($acao, 'empresa')) return 'Empresa';
    if (str_starts_with($acao, 'pacote')) return 'Atualização do catálogo';
    if (str_starts_with($acao, 'categoria') || str_starts_with($acao, 'subcategoria')) return 'Categorias';
    return ['copias' => 'Cópias de segurança', 'restaurar' => 'Cópias de segurança (restaurar)', 'atividades' => 'Atividades'][$acao] ?? $acao;
}

/** Para onde volta quando uma ação dá erro (ErroUsuario): a lista da mesma área. */
function voltarDepoisDoErro(string $acao): string
{
    // Ação em lote: a mesma lista, no modo "Selecionar", com os mesmos marcados (lib/lote.php)
    if ($acao === 'lote') return linkLista(filtroDoPost(), ['sel' => '1']);
    if ($acao === 'lugar') return linkLista(filtroDoPost());   // Mudar lugar: a mesma lista (busca e filtro)
    if (str_starts_with($acao, 'cliente')) return link_('clientes');
    if (str_starts_with($acao, 'usuario') || $acao === 'usuarios' || $acao === 'senha-temporaria') return link_('usuarios');
    if ($acao === 'criar-senha') return link_('criar-senha');
    if (in_array($acao, ['conta', 'senha', 'atividades'], true)) return link_('conta');
    if (in_array($acao, ['copias', 'restaurar'], true)) return link_('copias');
    if (str_starts_with($acao, 'empresa')) return link_('empresa');
    if (str_starts_with($acao, 'categoria') || str_starts_with($acao, 'subcategoria')) return link_('categorias');
    if (str_starts_with($acao, 'pacote')) {
        // De volta à mesma atualização (se o id for de uma que existe); senão, à lista delas
        $id = $_POST['id'] ?? $_GET['id'] ?? '';
        return $acao !== 'pacote' && is_string($id) && preg_match(RE_PACOTE, $id) && in_array($id, idsPacotes(), true) ? link_('pacote', ['id' => $id]) : link_('pacote', ['lista' => '1']);
    }
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
    // Edição em lote: ?sel=1 abre a lista no modo "Selecionar" (&todos=1 marca todos os desta lista);
    // depois de uma ação em lote sem JavaScript, os mesmos produtos voltam marcados (uma vez)
    $sel = ($_GET['sel'] ?? '') === '1';
    $marcados = $sel && is_array($_SESSION['loteMarcados'] ?? null) ? array_values(array_filter($_SESSION['loteMarcados'], 'is_string')) : [];
    unset($_SESSION['loteMarcados']);
    $lote = ['sel' => $sel, 'todos' => $sel && ($_GET['todos'] ?? '') === '1', 'marcados' => $marcados];
    // Atualização do catálogo pronta (painel/pacotes/): aviso no topo, só para administradores
    telaLista($cat, filtroDaUrl($cat['categorias']), ehAdmin() ? avisosDePacotes($cat) : [], $lote);
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
    // Produto novo: "Junto da mesma subcategoria" (padrão) ou "Em 1º na categoria" (lib/ordem.php)
    $lugar = ($_POST['lugar'] ?? '') === 'inicio' ? 'inicio' : 'tipo';

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
    $trocouCat = false;
    try {
        $novoCat = alterarCatalogo($acaoCat, static function (array $c) use ($p, $original, $lugar, &$trocouCat): array {
            $prods = $c['produtos'];
            if ($original !== null) {
                $i = indicePorId($prods, $original);
                if ($i === null) throw new ErroUsuario('Esse produto foi excluído (talvez em outro aparelho) enquanto você editava.');
                if ($p['id'] !== $original && indicePorId($prods, $p['id']) !== null) throw new ErroUsuario('Já existe outro produto com o código “' . $p['id'] . '”.');
                if (($prods[$i]['categoria'] ?? '') !== $p['categoria']) {
                    // Trocou de categoria: logo depois dos outros da mesma subcategoria na categoria nova
                    array_splice($prods, $i, 1);
                    array_splice($prods, posicaoNaSubcategoria($prods, $p['categoria'], $p['subcategoria'], $c['categorias']), 0, [$p]);
                    $trocouCat = true;
                } else {
                    $prods[$i] = $p;
                }
            } else {
                if (indicePorId($prods, $p['id']) !== null) throw new ErroUsuario('Já existe um produto com o código “' . $p['id'] . '”.');
                $onde = $lugar === 'inicio' ? inicioDaCategoria($prods, $p['categoria'], $c['categorias']) : posicaoNaSubcategoria($prods, $p['categoria'], $p['subcategoria'], $c['categorias']);
                array_splice($prods, $onde, 0, [$p]);
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
    avisar('“' . $nome . '” ' . fraseLugarSalvo($p, $novoCat['produtos'], $novoCat['categorias'], $atual === null, $lugar, $trocouCat) . $oculto . ($oculto ? '' : fraseHome($p, $novoCat['produtos']))
        . (!empty($novoCat['capaFoto']) ? ' A foto nova já vale na capa do site: confira se os pontos das etiquetas continuam no lugar certo em Empresa → Capa do site.' : ''), 'ok', $extra);
    // A capa do site aponta para o código antigo
    $capa = produtoCapa();
    if ($original !== null && $original === $capa && ($mudouCodigo || $oculto)) {
        avisar('Atenção: a capa do site usa este produto pelo código “' . $capa . '”. ' . fraseCapaFora(true) . ' '
            . ($mudouCodigo ? 'Para corrigir, volte o código para “' . $capa . '”.' : 'Para corrigir, deixe o produto visível no site.'), 'erro');
    }
    redirecionar('index.php#p-' . $p['id']);
}

/** Começo do aviso de "salvar": onde o produto ficou (lib/ordem.php). $lugar: "tipo" ou "inicio" (só no cadastro). */
function fraseLugarSalvo(array $p, array $produtos, array $categorias, bool $novo, string $lugar, bool $trocouCat): string
{
    if (!$novo && !$trocouCat) return 'foi salvo.';
    $id = (string) $p['id'];
    $nomeCat = (string) (categoriaPorId($categorias, (string) $p['categoria'])['nome'] ?? '');
    [$pos, $total] = posNaCategoria($produtos, $id);
    if ($novo && $lugar === 'inicio') return 'foi cadastrado em 1º lugar em ' . $nomeCat . '.';
    $outros = false;   // outros do mesmo tipo (mesma categoria e subcategoria)
    foreach ($produtos as $x) {
        if (($x['id'] ?? '') !== $id && ($x['categoria'] ?? '') === $p['categoria'] && ($x['subcategoria'] ?? '') === $p['subcategoria']) { $outros = true; break; }
    }
    $onde = $pos . 'º de ' . $total . ' em ' . $nomeCat;
    // "dos outros produtos do tipo “X”": não depende do gênero nem do número do nome da subcategoria
    $depoisDosOutros = 'entrou logo depois dos outros produtos do tipo “' . $p['subcategoria'] . '” (' . $onde . ').';
    if ($novo) return $outros ? 'foi cadastrado e ' . $depoisDosOutros : 'foi cadastrado (' . $onde . ').';
    return 'foi salvo e, como mudou de categoria, ' . ($outros ? $depoisDosOutros : 'ficou em ' . $onde . '.');
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
    $catAntes = catalogoOuTela();
    $p = produtoDaUrl($catAntes, 'post');
    $capaAntes = capaEfetiva($catAntes['produtos']);   // o produto que o topo do site mostra de fato
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
    if ($campo === 'visivel' && !$novo && (string) $p['id'] === $capaAntes) {
        $txt .= ' Atenção: é o produto da capa. ' . fraseCapaFora(true, $capaAntes, $produtos);
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

/**
 * "Mudar lugar" de um produto (lib/ordem.php). GET: a página com a lista de lugares (sem JavaScript ou
 * sem <dialog>). POST: para = "inicio" ou o código do vizinho de cima (da mesma categoria); desfazer=1
 * no Desfazer do aviso (é uma mudança de lugar comum, sem outro Desfazer), com categoria = a de quando
 * a mudança foi feita (se o produto mudou de categoria depois, não desfaz; sem o campo, abas antigas,
 * só o vizinho é conferido). Lugar igual: nada é gravado.
 */
function acaoLugar(bool $post): void
{
    $cat = catalogoOuTela();
    if (!$post) {
        $p = produtoDaUrl($cat);
        if (!categoriaPorId($cat['categorias'], (string) ($p['categoria'] ?? ''))) throw new ErroUsuario(ERRO_SEM_CATEGORIA);
        telaLugarProduto($cat, $p, filtroDaUrl($cat['categorias']));
        return;
    }
    $filtro = filtroDoPost();
    $desfazer = ($_POST['desfazer'] ?? '') === '1';
    $catDesfazer = $desfazer && is_string($_POST['categoria'] ?? null) ? $_POST['categoria'] : null;
    $info = [];
    try {
        $p = produtoDaUrl($cat, 'post');
        $id = (string) $p['id'];
        $nome = nomeCompleto($p);
        $para = paraProduto($_POST['para'] ?? null);
        if ($para === null) throw new ErroUsuario(ERRO_LUGAR_INVALIDO);
        if (!categoriaPorId($cat['categorias'], (string) ($p['categoria'] ?? ''))) throw new ErroUsuario(ERRO_SEM_CATEGORIA);
        $r = alterarCatalogo(['tipo' => 'ordem', 'antes' => 'Antes de: mudar o lugar de “' . $nome . '”', 'feito' => 'Mudou o lugar de “' . $nome . '”', 'alvo' => $id, 'alvoNome' => $nome],
            static function (array $atual) use ($id, $para, $desfazer, $catDesfazer, &$info): array {
                $i = indicePorId($atual['produtos'], $id);
                if ($i === null) throw new ErroUsuario('Esse produto não está mais no catálogo (talvez tenha sido excluído em outro aparelho).');
                $p = $atual['produtos'][$i];
                $c = categoriaPorId($atual['categorias'], (string) ($p['categoria'] ?? ''));
                if (!$c) throw new ErroUsuario(ERRO_SEM_CATEGORIA);
                $info = ['nome' => nomeCompleto($p), 'cat' => (string) $c['id'], 'nomeCat' => (string) $c['nome'], 'homeAntes' => destaquesDaHome($atual['produtos'])];
                // Desfazer: o produto ainda está na categoria de quando a mudança foi feita? (Editar ou Mover em outro aparelho)
                if ($catDesfazer !== null && $catDesfazer !== (string) $c['id']) throw new ErroUsuario(ERRO_DESFAZER_CATEGORIA);
                if ($desfazer && $para !== 'inicio') {
                    $v = indicePorId($atual['produtos'], $para);
                    if ($v === null || ($atual['produtos'][$v]['categoria'] ?? '') !== $c['id']) throw new ErroUsuario(ERRO_DESFAZER_LUGAR);
                }
                [$novos, $de, $pos, $total, $paraVolta] = moverProdutoPara($atual['produtos'], $id, $para === 'inicio' ? null : $para);
                if ($pos === $de) throw new SemMudanca();
                $info += ['de' => $de, 'pos' => $pos, 'total' => $total, 'paraVolta' => $paraVolta];
                $onde = 'lugar em ' . $info['nomeCat'];
                return ['produtos' => $novos, 'feito' => $desfazer
                    ? 'Desfez: “' . $info['nome'] . '” voltou para o ' . $pos . 'º ' . $onde
                    : 'Levou “' . $info['nome'] . '” do ' . $de . 'º para o ' . $pos . 'º ' . $onde];
            });
    } catch (SemMudanca $e) {
        $produtos = lerCatalogo()['produtos'];
        [$pos, $total] = posNaCategoria($produtos, $id);
        $msg = '“' . $info['nome'] . '” continua no ' . $pos . 'º lugar em ' . $info['nomeCat'] . '.';
        if (pedidoJson()) responderJson(['ok' => true, 'mudou' => false, 'msg' => $msg, 'id' => $id, 'cat' => $info['cat'], 'pos' => $pos, 'total' => $total,
            'ordem' => idsDaCategoria($produtos, $info['cat']), 'home' => destaquesDaHome($produtos)]);
        avisar($msg, 'info');
        redirecionar(linkLista($filtro) . '#p-' . $id);
    } catch (ErroUsuario $e) {
        if (pedidoJson()) responderJson(['ok' => false, 'msg' => $e->getMessage(), 'recarregar' => true], 409);
        // Sem JavaScript: a página Mudar o lugar de novo, com a lista de agora e o erro (o produto sumiu: volta para a lista)
        $atual = lerCatalogo();
        $i = isset($id) ? indicePorId($atual['produtos'], $id) : null;
        if ($i === null || !categoriaPorId($atual['categorias'], (string) ($atual['produtos'][$i]['categoria'] ?? ''))) throw $e;
        http_response_code(409);
        telaLugarProduto($atual, $atual['produtos'][$i], $filtro, $e->getMessage());
        return;
    }
    $novos = $r['produtos'];
    $home = destaquesDaHome($novos);
    $em = ' lugar em ' . $info['nomeCat'];
    $msg = $desfazer
        ? 'Desfeito: “' . $info['nome'] . '” voltou para o ' . $info['pos'] . 'º' . $em . '.' . fraseMudancaHome($info['homeAntes'], $home, $novos)
        : '“' . $info['nome'] . '” foi para o ' . $info['pos'] . 'º' . $em . ' (era o ' . $info['de'] . 'º).' . fraseOrdemSite($novos, $id, $info['nomeCat']) . fraseMudancaHome($info['homeAntes'], $home, $novos);
    $campos = ['id' => $id, 'para' => $info['paraVolta'], 'categoria' => $info['cat'], 'desfazer' => '1'];
    if (pedidoJson()) {
        responderJson(['ok' => true, 'mudou' => true, 'msg' => $msg, 'id' => $id, 'cat' => $info['cat'], 'de' => $info['de'], 'pos' => $info['pos'], 'total' => $info['total'],
            'ordem' => idsDaCategoria($novos, $info['cat']), 'home' => $home] + ($desfazer ? [] : ['desfazer' => ['acao' => link_('lugar'), 'campos' => $campos]]));
    }
    avisar($msg, 'ok', $desfazer ? [] : ['form' => ['acao' => link_('lugar'), 'campos' => $campos + $filtro, 'botao' => 'Desfazer']]);
    redirecionar(linkLista($filtro) . '#p-' . $id);
}

function acaoExcluir(bool $post): void
{
    $cat = catalogoOuTela();
    $p = produtoDaUrl($cat, $post ? 'post' : 'get');
    $nome = nomeCompleto($p);
    $capa = capaEfetiva($cat['produtos']);   // o produto que o topo do site mostra de fato
    $ehCapa = (string) $p['id'] === $capa;
    if (!$post || ($_POST['confirmado'] ?? '') !== '1') {
        telaConfirmar('Excluir produto?', textoExcluir($p, $capa),
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
    avisar('“' . $nome . '” foi excluído.' . ($ehCapa ? ' Atenção: era o produto da capa. ' . fraseCapaFora(true, $capa, $r['produtos']) : ''), 'ok',
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
    $troca = '';
    alterarCatalogo(['tipo' => 'desfazer', 'antes' => 'Antes de: trazer de volta “' . $nome . '”', 'feito' => 'Trouxe de volta “' . $nome . '”', 'alvo' => $id, 'alvoNome' => $nome],
        static function (array $c) use ($copia, $p, $nome, &$troca): array {
            if (indicePorId($c['produtos'], (string) $p['id']) !== null) throw new ErroUsuario('“' . $nome . '” já está no catálogo.');
            if (!categoriaPorId($c['categorias'], (string) ($p['categoria'] ?? ''))) throw new ErroUsuario('A categoria de “' . $nome . '” não existe mais. Use Cópias de segurança → Restaurar.');
            // Mesmo lugar: logo depois do vizinho de cima (da mesma categoria) que ainda existir, ou logo
            // antes do de baixo; senão, no fim da categoria. Subcategoria renomeada ou apagada depois da
            // exclusão: volta na nova (ou na primeira da categoria), com aviso (lib/lote.php, o mesmo do Desfazer em lote)
            [$prods, , , $trocas] = devolverProdutos($c['produtos'], $c['categorias'], $copia['produtos'], [(string) $p['id']], (array) $copia['categorias']);
            $troca = (string) ($trocas[(string) $p['id']] ?? '');
            return ['produtos' => $prods] + ($troca !== '' ? ['feito' => 'Trouxe de volta “' . $nome . '”; ' . $troca] : []);
        });
    unset($_SESSION['desfazer'][$nomeCopia]);
    $semFoto = is_string($p['imagem'] ?? null) && $p['imagem'] !== '' && !@is_file(pastaFotos() . '/' . $p['imagem']);
    avisar('“' . $nome . '” voltou para o catálogo, no mesmo lugar' . ($semFoto ? '. A foto não estava mais na lixeira: escolha outra em Editar.' : ' e com a foto.')
        . ($troca !== '' ? ' Atenção: ' . $troca . '. Confira o tipo dele em Editar.' : ''), $semFoto || $troca !== '' ? 'erro' : 'ok',
        $troca !== '' ? ['link' => link_('editar', ['id' => $id]), 'textoLink' => 'Editar'] : (($p['visivel'] ?? true) === false ? [] : linkSite($id)));
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
    // Cliente novo: o lugar escolhido no cadastro ("depois de X"); sem escolha (ou inválido), no começo (1º)
    $lugar = $atual === null ? (refLugarCliente($_POST['lugar'] ?? null) ?? ['inicio' => true]) : null;
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
    $posFinal = 0; $sumiu = false; $depoisDe = '';
    try {
        alterarClientes($acao, static function (array $l) use ($novo, $ref, $lugar, &$posFinal, &$sumiu, &$depoisDe): array {
            if ($ref !== null) {
                $j = acharCliente($l, $ref['n'] - 1, $ref['logo']);
                if ($j === null) throw new ErroUsuario('Esse cliente foi excluído (talvez em outro aparelho) enquanto você editava.');
                $l[$j] = $novo;
                $posFinal = $j + 1;
            } else {
                // Vizinho escolhido que sumiu entre abrir e enviar: no começo, com aviso (sem erro, para não perder o logo enviado)
                $k = isset($lugar['inicio']) ? null : acharCliente($l, $lugar['i'], $lugar['logo']);
                if ($k === null) {
                    $sumiu = !isset($lugar['inicio']);
                    array_unshift($l, $novo);
                    $posFinal = 1;
                } else {
                    array_splice($l, $k + 1, 0, [$novo]);
                    $posFinal = $k + 2;
                    $depoisDe = (string) ($l[$k]['nome'] ?? '');
                }
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
    $msg = '“' . $nome . '” ' . match (true) {
        $atual !== null => 'foi salvo.',
        $sumiu          => 'foi cadastrado e entrou em 1º lugar no site: o cliente que você escolheu como vizinho não está mais na lista. Use Mudar lugar se quiser outro lugar.',
        $posFinal === 1 => 'foi cadastrado e aparece em 1º lugar no site.',
        default         => 'foi cadastrado e aparece em ' . $posFinal . 'º lugar no site, depois de “' . $depoisDe . '”.',
    };
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

/**
 * "Mudar lugar" de um cliente (lib/ordem.php). GET: a página com a lista de lugares (sem JavaScript).
 * POST: n + logo (+ nome, que confere o cliente sem logo) do cliente; para = "inicio" ou "n:logo" do
 * vizinho de cima (n = posição dele na lista com o cliente); desfazer=1 no Desfazer do aviso.
 * Lugar igual: nada é gravado.
 */
function acaoClienteLugar(bool $post): void
{
    if (!$post) {
        $lista = lerClientes()['clientes'];
        [$i] = clienteDaUrl($lista);
        telaLugarCliente($lista, $i);
        return;
    }
    $desfazer = ($_POST['desfazer'] ?? '') === '1';
    [$n, $logo] = refCliente($_POST);
    $nomeRef = is_string($_POST['nome'] ?? null) ? $_POST['nome'] : null;
    $info = [];
    try {
        [, $c] = clienteDaUrl(lerClientes()['clientes'], 'post');
        $nome = (string) $c['nome'];
        $para = refLugarCliente($_POST['para'] ?? null);
        if ($para === null) throw new ErroUsuario(ERRO_LUGAR_INVALIDO);
        alterarClientes(['tipo' => 'ordem', 'antes' => 'Antes de: mudar o lugar do cliente “' . $nome . '”', 'feito' => 'Mudou o lugar do cliente “' . $nome . '”', 'alvo' => (string) ($c['logo'] ?? ''), 'alvoNome' => $nome],
            static function (array $l) use ($n, $logo, $nomeRef, $para, $desfazer, &$info): array {
                $j = acharCliente($l, $n, $logo, $nomeRef);
                // Outro cliente sem logo nessa posição (tela velha): a lista mudou, nada é gravado
                if ($j === null) throw new ErroUsuario($logo === '' && acharCliente($l, $n, '') !== null ? ERRO_LISTA_MUDOU : 'Esse cliente não está mais na lista (talvez tenha sido excluído em outro aparelho).');
                $info = ['nome' => (string) ($l[$j]['nome'] ?? ''), 'total' => count($l)];
                $k = null;
                if (!isset($para['inicio'])) {
                    $k = acharCliente($l, $para['i'], $para['logo']);
                    if ($k === null) throw new ErroUsuario($desfazer ? ERRO_DESFAZER_LUGAR : ERRO_LISTA_MUDOU);
                    if ($k === $j) throw new ErroUsuario(ERRO_LUGAR_INVALIDO);
                }
                [$novos, $pos] = moverClientePara($l, $j, $k);
                $info['pos'] = $pos;
                if ($pos === $j + 1) throw new SemMudanca();
                $info += ['de' => $j + 1, 'logo' => (string) ($l[$j]['logo'] ?? ''), 'paraVolta' => paraDesfazerCliente($l, $j, $pos - 1)];
                return ['clientes' => $novos, 'feito' => $desfazer
                    ? 'Desfez: o cliente “' . $info['nome'] . '” voltou para o ' . $pos . 'º lugar'
                    : 'Levou o cliente “' . $info['nome'] . '” do ' . ($j + 1) . 'º para o ' . $pos . 'º lugar'];
            });
    } catch (SemMudanca $e) {
        $lista = lerClientes()['clientes'];
        $msg = '“' . $info['nome'] . '” continua no ' . $info['pos'] . 'º lugar no site.';
        if (pedidoJson()) responderJson(['ok' => true, 'mudou' => false, 'msg' => $msg, 'pos' => $info['pos'], 'total' => count($lista), 'ordem' => ordemClientes($lista)]);
        avisar($msg, 'info');
        redirecionar(link_('clientes') . '#c-' . $info['pos']);
    } catch (ErroUsuario $e) {
        if (pedidoJson()) responderJson(['ok' => false, 'msg' => $e->getMessage(), 'recarregar' => true], 409);
        // Sem JavaScript: a página Mudar o lugar de novo, com a lista de agora e o erro (o cliente sumiu: volta para Clientes)
        $lista = lerClientes()['clientes'];
        $j = acharCliente($lista, $n, $logo, $nomeRef);
        if ($j === null) throw $e;
        http_response_code(409);
        telaLugarCliente($lista, $j, $e->getMessage());
        return;
    }
    $lista = lerClientes()['clientes'];
    $msg = $desfazer
        ? 'Desfeito: “' . $info['nome'] . '” voltou para o ' . $info['pos'] . 'º lugar no site.'
        : '“' . $info['nome'] . '” foi para o ' . $info['pos'] . 'º lugar no site (era o ' . $info['de'] . 'º).';
    $campos = ['n' => (string) $info['pos'], 'logo' => $info['logo'], 'nome' => $info['nome'], 'para' => $info['paraVolta'], 'desfazer' => '1'];
    if (pedidoJson()) {
        responderJson(['ok' => true, 'mudou' => true, 'msg' => $msg, 'de' => $info['de'], 'pos' => $info['pos'], 'total' => $info['total'], 'ordem' => ordemClientes($lista)]
            + ($desfazer ? [] : ['desfazer' => ['acao' => link_('cliente-lugar'), 'campos' => $campos]]));
    }
    avisar($msg, 'ok', $desfazer ? [] : ['form' => ['acao' => link_('cliente-lugar'), 'campos' => $campos, 'botao' => 'Desfazer']]);
    redirecionar(link_('clientes') . '#c-' . $info['pos']);
}

/** A lista em pares [nome, logo], para a tela conferir e se reordenar (aceita nomes e logos repetidos). */
function ordemClientes(array $lista): array
{
    return array_map(static fn($c) => [(string) ($c['nome'] ?? ''), (string) ($c['logo'] ?? '')], array_values($lista));
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
