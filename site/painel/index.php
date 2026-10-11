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
require __DIR__ . '/lib/catalogo.php';
require __DIR__ . '/lib/imagens.php';
require __DIR__ . '/lib/visoes.php';

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
verificarSessao();

const ACOES = ['lista', 'novo', 'editar', 'salvar', 'rapido', 'mover', 'excluir', 'desfazer', 'copias', 'restaurar', 'conta', 'senha', 'sair', 'entrar', 'instalar', 'sessao'];
const SO_POST = ['salvar', 'rapido', 'mover', 'desfazer', 'senha', 'sair'];
$acao = is_string($_GET['a'] ?? null) && in_array($_GET['a'], ACOES, true) ? $_GET['a'] : 'lista';
$post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

// A tela do produto confere a sessão antes de enviar (e, se ela venceu, pede a senha
// numa janela, sem sair da página e sem perder o que foi digitado nem a foto)
if ($acao === 'sessao') {
    responderJson(['ok' => true, 'logado' => instalado() && usuarioLogado() !== null, 'csrf' => tokenCsrf()]);
}

// Envio maior que o limite do PHP: o servidor descarta tudo ($_POST e $_FILES vazios)
if ($post && !$_POST && !$_FILES && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $msg = 'O envio ficou maior do que o servidor aceita (' . mb_(limiteEnvio()) . '). Use uma foto menor e tente de novo.';
    if (pedidoJson()) responderJson(['ok' => false, 'msg' => $msg], 413);
    if (usuarioLogado()) {
        avisar($msg, 'erro');
        $id = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
        redirecionar($acao === 'salvar' ? ($id !== '' ? link_('editar', ['id' => $id]) : link_('novo')) : 'index.php');
    }
    redirecionar('index.php');
}

/* ------------------------------------------------------------------
   Sem usuário criado: só o primeiro acesso
   ------------------------------------------------------------------ */
if (!instalado()) {
    if ($post && $acao === 'instalar') {
        if (!csrfValido()) { http_response_code(403); telaInstalar('A página ficou aberta tempo demais e expirou. Confira os dados e envie de novo.', ['usuario' => (string) ($_POST['usuario'] ?? '')]); exit; }
        $v = ['codigo' => (string) ($_POST['codigo'] ?? ''), 'usuario' => trim((string) ($_POST['usuario'] ?? ''))];
        $erro = instalar($v['codigo'], $v['usuario'], (string) ($_POST['senha'] ?? ''), (string) ($_POST['senha2'] ?? ''));
        if ($erro === null) {
            avisar('Pronto! Seu acesso foi criado. Guarde a senha num lugar seguro.');
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
   Sem login: só a tela de entrar
   ------------------------------------------------------------------ */
if (usuarioLogado() === null) {
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
        if (pedidoJson()) responderJson(['ok' => false, 'msg' => $erro, 'csrf' => tokenCsrf()], bloqueioRestante() ? 429 : 401);
        http_response_code(bloqueioRestante() ? 429 : 401);
        telaEntrar($erro, $usuario);
        exit;
    }
    if ($post) {
        if (pedidoJson()) responderJson(['ok' => false, 'login' => true, 'msg' => 'Sua sessão terminou. Entre de novo.', 'csrf' => tokenCsrf()], 401);
        // Depois de entrar, volta para a tela de onde veio (o formulário do produto, com o rascunho)
        $id = is_string($_GET['id'] ?? null) && preg_match(RE_ID, $_GET['id']) ? $_GET['id'] : '';
        if ($acao === 'salvar') {
            $_SESSION['voltar'] = $id !== '' ? link_('editar', ['id' => $id]) : link_('novo');
            avisar('Sua sessão terminou e o produto não foi salvo. Entre de novo: o painel volta para o formulário com o que você tinha digitado (a foto precisa ser escolhida de novo).', 'info');
        } else {
            avisar('Sua sessão terminou e a última ação não foi feita. Entre de novo para continuar.', 'info');
        }
        redirecionar(link_('entrar'));
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
if ($acao === 'entrar' || $acao === 'instalar') redirecionar('index.php');
if ($post && !csrfValido()) {
    registrar('csrf', 'pedido recusado (token ausente ou inválido) em ' . $acao);
    if (pedidoJson()) responderJson(['ok' => false, 'msg' => 'A página ficou desatualizada. Recarregue e tente de novo.'], 403);
    http_response_code(403);
    paginaErro('Ação recusada', 'A página ficou desatualizada (ou o pedido não veio do painel) e nada foi alterado. Volte, recarregue a página e tente de novo.');
    exit;
}
if (!$post && in_array($acao, SO_POST, true)) redirecionar('index.php');

try {
    match ($acao) {
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
    };
} catch (ErroCatalogo $e) {
    if (pedidoJson()) responderJson(['ok' => false, 'msg' => 'Não consegui ler o catálogo: ' . $e->getMessage()], 500);
    telaCatalogoIlegivel($e->getMessage());
} catch (ErroUsuario $e) {
    if (pedidoJson()) responderJson(['ok' => false, 'msg' => $e->getMessage()], 409);
    avisar($e->getMessage(), 'erro');
    redirecionar('index.php');
}
exit;

/* ================================================================== */

/** Endereço interno do painel para voltar depois de entrar (ou null se não for seguro). */
function destinoSeguro($url): ?string
{
    if (!is_string($url) || strlen($url) > 300) return null;
    if (!preg_match('/^index\.php\?a=(novo|editar|copias|conta)(&[A-Za-z0-9_=%.\-]*)*$/', $url)) return null;
    return $url;
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
    $semFoto = is_string($p['imagem'] ?? null) && $p['imagem'] !== '' && !@is_file(pastaFotos() . '/' . $p['imagem']);
    avisar('“' . $nome . '” voltou para o catálogo, no mesmo lugar' . ($semFoto ? '. A foto não estava mais na lixeira: escolha outra em Editar.' : ' e com a foto.'), $semFoto ? 'erro' : 'ok',
        ($p['visivel'] ?? true) === false ? [] : linkSite($id));
    redirecionar('index.php#p-' . $id);
}

function acaoCopias(): void
{
    try { $prods = lerCatalogo()['produtos']; } catch (ErroCatalogo $e) { $prods = null; }
    $ids = [];
    foreach ($prods ?? [] as $p) $ids[(string) ($p['id'] ?? '')] = true;
    telaCopias(listarCopias(), $prods === null ? null : count($prods), $ids);
}

function acaoRestaurar(bool $post): void
{
    $nome = is_string($_POST['copia'] ?? null) ? $_POST['copia'] : (is_string($_GET['copia'] ?? null) ? $_GET['copia'] : '');
    $copia = lerCopia($nome);
    $quando = quando((int) ($copia['criado'] ?? 0));
    if (!$post || ($_POST['confirmado'] ?? '') !== '1') {
        $lista = listarCopias();
        $i = array_search($nome, array_column($lista, 'nome'), true);
        try { $total = count(lerCatalogo()['produtos']); } catch (ErroCatalogo $e) { $total = null; }
        telaConfirmar('Restaurar esta cópia?', $i === false ? 'Voltar o catálogo inteiro para como estava em ' . $quando . '? O estado atual vira uma cópia, então dá para desfazer.' : textoRestaurar($lista, (int) $i, $total),
            link_('restaurar'), ['copia' => $nome], 'Sim, restaurar', link_('copias'));
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

function acaoSenha(): void
{
    $erro = trocarSenha((string) ($_POST['atual'] ?? ''), (string) ($_POST['nova'] ?? ''), (string) ($_POST['nova2'] ?? ''));
    if ($erro !== null) {
        http_response_code(bloqueioRestante() ? 429 : 422);
        if (bloqueioRestante()) { sair(); avisar($erro, 'erro'); redirecionar(link_('entrar')); }
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
