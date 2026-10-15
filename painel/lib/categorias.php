<?php
/* =====================================================================
   Painel Balanças.com — categorias e subcategorias (só administradores)
   Tela index.php?a=categorias (telas em lib/visoes-categorias.php):
   renomear, criar, mudar a ordem e apagar categorias e subcategorias.
   - A categoria tem um código (id) que nunca muda: renomear muda só o
     nome e a descrição; links antigos (produtos.html?cat=…) continuam.
   - Os produtos guardam o NOME da subcategoria: renomear uma subcategoria
     muda junto todos os produtos dela, na MESMA gravação do catálogo
     (uma cópia de segurança, uma linha em Atividades).
   - Apagar: categoria só quando não tem produtos; subcategoria vazia, ou
     movendo antes os produtos dela para outra (moverProdutosPara, de
     lib/lote.php), tudo numa gravação só.
   - Mudar a ordem das categorias muda a ordem das abas do site e leva
     junto os produtos (no catálogo "Todos", a categoria de cima vem antes).
   Cada ação é UMA chamada de alterarCatalogo() (trava, cópia, gravação,
   registro). Conferência das regras em problemasDasCategorias()
   (lib/catalogo.php).
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

final class ErroFormCategoria extends RuntimeException
{
    /** $form: qual formulário da tela volta aberto (chaveFormCategoria); $valores: o que foi digitado */
    public function __construct(string $msg, public readonly string $form = '', public readonly array $valores = [])
    {
        parent::__construct($msg);
    }
}

/* ------------------------------------------------------------------
   Entrada (tudo conferido; nada vai direto do formulário para o arquivo)
   ------------------------------------------------------------------ */
/** Categoria pelo código vindo do pedido ($_POST ou $_GET 'cat'). Lança ErroUsuario. */
function categoriaDoPedido(array $categorias, $v): array
{
    $id = is_string($v) ? $v : '';
    $c = strlen($id) <= LIMITES['id'] && preg_match(RE_ID, $id) ? categoriaPorId($categorias, $id) : null;
    if (!$c) throw new ErroUsuario('Essa categoria não existe mais (pode ter sido apagada em outro aparelho). Confira a lista.');
    return $c;
}

/** Subcategoria (nome exato) da categoria $c vinda do pedido. Lança ErroUsuario. */
function subDoPedido(array $c, $v): string
{
    $s = is_string($v) ? $v : '';
    if ($s === '' || !in_array($s, $c['subcategorias'], true)) {
        throw new ErroUsuario('Essa subcategoria não existe mais em “' . $c['nome'] . '” (pode ter sido renomeada ou apagada em outro aparelho). Confira a lista.');
    }
    return $s;
}

/** Nome digitado (categoria ou subcategoria): [nome limpo, erro ou null]. $outros = nomes que já existem ali. */
function nomeDeCategoria($v, int $max, array $outros, string $oQue, string $onde = ''): array
{
    $nome = limparLinha(is_string($v) ? $v : '');
    if ($nome === null) return ['', 'O nome tem caracteres inválidos. Apague e digite de novo.'];
    if ($nome === '') return ['', 'Escreva o nome da ' . $oQue . '.'];
    if (tamanho($nome) > $max) return [$nome, 'Use no máximo ' . $max . ' caracteres (tem ' . tamanho($nome) . ').'];
    if (!preg_match('/[\p{L}\p{N}]/u', $nome)) return [$nome, 'O nome precisa ter letras ou números.'];
    // A aba "Todos" do catálogo do site e o filtro "Todos os tipos" de cada categoria
    if ($oQue === 'categoria' && chaveDeNome($nome) === 'todos') return [$nome, 'Esse nome já é usado pela aba que mostra tudo (“Todos”). Use outro nome.'];
    if ($oQue !== 'categoria' && in_array(chaveDeNome($nome), ['todos', 'todos os tipos'], true)) return [$nome, 'Esse nome já é usado pelo filtro que mostra todos os tipos (“Todos os tipos”). Use outro nome.'];
    foreach ($outros as $o) {
        if (chaveDeNome((string) $o) === chaveDeNome($nome)) {
            return [$nome, 'Já existe ' . ($oQue === 'categoria' ? 'uma categoria' : 'uma subcategoria') . ' “' . $o . '”' . ($onde !== '' ? ' em “' . $onde . '”' : '') . '. Use outro nome.'];
        }
    }
    return [$nome, null];
}

/**
 * Categoria: o nome e o nome curto (o que as abas do site, os atalhos da página inicial e os chips do painel
 * mostram: “Automação” para “Automação Comercial”) não podem coincidir com os de outra categoria.
 * $id = código da categoria salva (a nova: o código que ela vai ganhar). Devolve o erro ou null.
 */
function conflitoNomeCategoria(string $nome, string $id, array $categorias): ?string
{
    $eu = ['id' => $id, 'nome' => $nome];
    $minhas = array_unique([chaveDeNome($nome), chaveDeNome(categoriaCurta($eu))]);
    foreach ($categorias as $o) {
        if ((string) $o['id'] === $id) continue;
        $curta = categoriaCurta($o);
        if (!array_intersect($minhas, [chaveDeNome((string) $o['nome']), chaveDeNome($curta)])) continue;
        $comoAparece = in_array(chaveDeNome($curta), $minhas, true) ? $curta : (string) $o['nome'];
        return 'Já existe uma categoria que aparece no site como “' . $comoAparece . '”' . ($curta !== (string) $o['nome'] ? ' (“' . $o['nome'] . '”)' : '')
            . ($comoAparece !== $nome && categoriaCurta($eu) !== $nome ? ', e “' . $nome . '” apareceria como “' . categoriaCurta($eu) . '”' : '')
            . '. Use outro nome: as abas do site e os atalhos da página inicial ficariam com dois nomes iguais.';
    }
    return null;
}

// Páginas do site com links fixos para um tipo do catálogo (produtos.html?cat=…&sub=…). Com a
// subcategoria renomeada ou apagada, o link abre a categoria inteira: o painel avisa antes.
const PAGINAS_COM_LINKS_DE_TIPO = ['index.html' => 'Página inicial', 'assistencia.html' => 'Assistência técnica', 'sistema.html' => 'Sistema',
    'empresa.html' => 'Empresa', 'contato.html' => 'Contato'];

/** Páginas do site que têm link fixo para a subcategoria $sub da categoria $id: ['Assistência técnica', …]. */
function paginasComLinkDaSub(string $id, string $sub): array
{
    static $links = null;
    if ($links === null) {
        $links = [];
        $raiz = dirname((string) cfg('compartilhado'));
        foreach (PAGINAS_COM_LINKS_DE_TIPO as $arq => $nomePag) {
            $html = @file_get_contents($raiz . '/' . $arq, false, null, 0, 800000);
            if (!is_string($html) || !preg_match_all('/produtos\.html\?cat=([a-z0-9-]+)&(?:amp;)?sub=([^"\'&#\s<>]+)/', $html, $m, PREG_SET_ORDER)) continue;
            foreach ($m as $x) $links[$x[1] . '|' . urldecode(html_entity_decode($x[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'))][$nomePag] = true;
        }
    }
    return array_keys($links[$id . '|' . $sub] ?? []);
}

/** Aviso (ou '') para renomear/apagar uma subcategoria que tem link fixo nas páginas do site. */
function avisoLinksDaSub(array $c, string $sub, bool $apagar = false): string
{
    $pags = paginasComLinkDaSub((string) $c['id'], $sub);
    if (!$pags) return '';
    $quais = count($pags) === 1 ? 'A página “' . $pags[0] . '” do site tem' : 'As páginas “' . implode('” e “', $pags) . '” do site têm';
    return $quais . ' um link fixo para “' . $sub . '”: ' . ($apagar ? 'sem a subcategoria' : 'com outro nome') . ', esse link passa a abrir “' . $c['nome']
        . '” com todos os tipos (o link só muda numa atualização do site).';
}

function descricaoDeCategoria($v): array
{
    $d = limparLinha(is_string($v) ? $v : '');
    if ($d === null) return ['', 'A descrição tem caracteres inválidos. Apague e digite de novo.'];
    if (tamanho($d) > LIMITES_CATEGORIA['descricao']) return [$d, 'A descrição pode ter até ' . LIMITES_CATEGORIA['descricao'] . ' caracteres (tem ' . tamanho($d) . ').'];
    return [$d, null];
}

/** Subcategorias de uma categoria nova: uma por linha, sem repetir. [lista, erro ou null] */
function subsDigitadas($v, string $nomeCat): array
{
    $linhas = preg_split('/\R/u', is_string($v) ? $v : '') ?: [];
    $subs = [];
    foreach ($linhas as $l) {
        if (trim($l) === '') continue;
        [$s, $erro] = nomeDeCategoria($l, LIMITES_CATEGORIA['subcategoria'], $subs, 'subcategoria');
        if ($erro !== null) return [$subs, 'Subcategoria “' . cortar((string) $s, 40) . '”: ' . $erro];
        $subs[] = $s;
    }
    if (!$subs) return [[], 'Escreva pelo menos uma subcategoria (uma por linha): os produtos precisam de uma para entrar na categoria' . ($nomeCat !== '' ? ' “' . $nomeCat . '”' : '') . '.'];
    if (count($subs) > LIMITES_CATEGORIA['subcategorias']) return [$subs, 'No máximo ' . LIMITES_CATEGORIA['subcategorias'] . ' subcategorias.'];
    return [$subs, null];
}

/** Destino "<id da categoria>|<subcategoria>" (o mesmo formato do "Mover para…" da edição em lote). */
function destinoDeCategoria($v, array $categorias): array
{
    $v = is_string($v) ? $v : '';
    $p = strpos($v, '|');
    if ($v === '' || $p === false) throw new ErroUsuario('Escolha para onde vão os produtos (categoria e subcategoria).');
    $c = categoriaPorId($categorias, substr($v, 0, $p));
    $sub = substr($v, $p + 1);
    if (!$c || !in_array($sub, $c['subcategorias'], true)) throw new ErroUsuario('Esse destino não existe mais. Recarregue a página e escolha de novo.');
    return [$c, $sub];
}

/** Chave do formulário da tela (para voltar aberto com o erro): nova · cat:<id> · subnova:<id> · sub:<id>:<n> */
function chaveFormCategoria(string $tipo, string $id = '', int $n = 0): string
{
    return $tipo . ($id !== '' ? ':' . $id : '') . ($n > 0 ? ':' . $n : '');
}

/* ------------------------------------------------------------------
   Mudanças (funções puras: recebem listas e devolvem outras)
   ------------------------------------------------------------------ */
function posicaoCategoria(array $categorias, string $id): ?int
{
    foreach (array_values($categorias) as $i => $c) if (($c['id'] ?? '') === $id) return $i;
    return null;
}

/** Código novo a partir do nome: slug único, nunca "todos" (a aba Todos do site). */
function idDeCategoriaNova(string $nome, array $categorias): string
{
    $base = slug($nome, 40);
    if ($base === '' || !preg_match(RE_ID, $base)) $base = 'categoria';
    $usados = array_fill_keys(IDS_CATEGORIA_RESERVADOS, true);
    foreach ($categorias as $c) $usados[(string) $c['id']] = true;
    $id = $base;
    for ($n = 2; isset($usados[$id]); $n++) $id = rtrim(substr($base, 0, 36), '-') . '-' . $n;
    return $id;
}

/** Categoria nova no fim da lista. Devolve [categorias, id]. */
function comCategoriaNova(array $categorias, string $nome, string $descricao, array $subs): array
{
    $id = idDeCategoriaNova($nome, $categorias);
    $categorias[] = ['id' => $id, 'nome' => $nome, 'descricao' => $descricao, 'subcategorias' => array_values($subs)];
    return [array_values($categorias), $id];
}

/** Novo nome e descrição (o código não muda). Campos desconhecidos da categoria ficam como estão. */
function comCategoriaEditada(array $categorias, string $id, string $nome, string $descricao): array
{
    $i = posicaoCategoria($categorias, $id);
    if ($i === null) throw new ErroUsuario('Essa categoria não existe mais.');
    $categorias = array_values($categorias);
    $categorias[$i]['nome'] = $nome;
    $categorias[$i]['descricao'] = $descricao;
    return $categorias;
}

/** Troca a categoria de lugar com a vizinha. null = já é a primeira/última. */
function comCategoriaMovida(array $categorias, string $id, string $dir): ?array
{
    $categorias = array_values($categorias);
    $i = posicaoCategoria($categorias, $id);
    if ($i === null) throw new ErroUsuario('Essa categoria não existe mais.');
    $j = $dir === 'cima' ? $i - 1 : $i + 1;
    if ($j < 0 || $j >= count($categorias)) return null;
    [$categorias[$i], $categorias[$j]] = [$categorias[$j], $categorias[$i]];
    return $categorias;
}

/**
 * Produtos agrupados na ordem das categorias, sem mudar a ordem dentro de cada uma (é a ordem do
 * catálogo "Todos" do site e da página inicial). Produtos de categoria que não existe ficam no fim.
 */
function produtosNaOrdemDasCategorias(array $categorias, array $produtos): array
{
    $grupos = [];
    foreach ($categorias as $c) $grupos[(string) $c['id']] = [];
    $resto = [];
    foreach ($produtos as $p) {
        $c = (string) ($p['categoria'] ?? '');
        if (array_key_exists($c, $grupos)) $grupos[$c][] = $p;
        else $resto[] = $p;
    }
    return array_merge(array_merge(...array_values($grupos ?: [[]])), $resto);
}

function semCategoria(array $categorias, array $produtos, string $id): array
{
    foreach ($produtos as $p) if (($p['categoria'] ?? '') === $id) throw new ErroUsuario('Essa categoria tem produtos e não pode ser apagada.');
    if (count($categorias) <= 1) throw new ErroUsuario('O catálogo precisa de pelo menos uma categoria.');
    return array_values(array_filter($categorias, static fn($c) => ($c['id'] ?? '') !== $id));
}

function comSubNova(array $categorias, string $id, string $nome): array
{
    $i = posicaoCategoria($categorias, $id);
    if ($i === null) throw new ErroUsuario('Essa categoria não existe mais.');
    $categorias = array_values($categorias);
    $categorias[$i]['subcategorias'][] = $nome;
    return $categorias;
}

/** Renomeia a subcategoria e todos os produtos que usam ela. Devolve [categorias, produtos, quantos produtos mudaram]. */
function comSubRenomeada(array $categorias, array $produtos, string $id, string $de, string $para): array
{
    $i = posicaoCategoria($categorias, $id);
    $categorias = array_values($categorias);
    $k = $i === null ? false : array_search($de, $categorias[$i]['subcategorias'], true);
    if ($k === false) throw new ErroUsuario('Essa subcategoria não existe mais.');
    $categorias[$i]['subcategorias'][$k] = $para;
    $n = 0;
    foreach ($produtos as $j => $p) {
        if (($p['categoria'] ?? '') === $id && ($p['subcategoria'] ?? '') === $de) {
            $produtos[$j]['subcategoria'] = $para;
            $n++;
        }
    }
    return [$categorias, array_values($produtos), $n];
}

/** Troca a subcategoria de lugar com a vizinha (ordem dos "tipos" no site). null = já é a primeira/última. */
function comSubMovida(array $categorias, string $id, string $sub, string $dir): ?array
{
    $i = posicaoCategoria($categorias, $id);
    $categorias = array_values($categorias);
    $k = $i === null ? false : array_search($sub, $categorias[$i]['subcategorias'], true);
    if ($k === false) throw new ErroUsuario('Essa subcategoria não existe mais.');
    $subs = array_values($categorias[$i]['subcategorias']);
    $j = $dir === 'cima' ? $k - 1 : $k + 1;
    if ($j < 0 || $j >= count($subs)) return null;
    [$subs[$k], $subs[$j]] = [$subs[$j], $subs[$k]];
    $categorias[$i]['subcategorias'] = $subs;
    return $categorias;
}

function semSub(array $categorias, string $id, string $sub): array
{
    $i = posicaoCategoria($categorias, $id);
    $categorias = array_values($categorias);
    if ($i === null || !in_array($sub, $categorias[$i]['subcategorias'], true)) throw new ErroUsuario('Essa subcategoria não existe mais.');
    $categorias[$i]['subcategorias'] = array_values(array_filter($categorias[$i]['subcategorias'], static fn($s) => $s !== $sub));
    return $categorias;
}

/** Códigos dos produtos de uma subcategoria. */
function idsDaSub(array $produtos, string $id, string $sub): array
{
    $out = [];
    foreach ($produtos as $p) if (($p['categoria'] ?? '') === $id && ($p['subcategoria'] ?? '') === $sub) $out[] = (string) $p['id'];
    return $out;
}

/** Confere as categorias que vão ser gravadas (regras de lib/catalogo.php). */
function exigirCategoriasValidas(array $categorias): void
{
    $prob = problemasDasCategorias($categorias);
    if ($prob) throw new ErroUsuario('Nada foi alterado: ' . $prob[0] . '.');
}

/* ------------------------------------------------------------------
   Textos
   ------------------------------------------------------------------ */
function qtdProdutos(int $n): string
{
    return $n === 0 ? 'nenhum produto' : ($n === 1 ? '1 produto' : $n . ' produtos');
}

function textoApagarCategoria(array $c): string
{
    $ns = count($c['subcategorias']);
    return 'Apagar a categoria “' . $c['nome'] . '”? Ela não tem produtos.'
        . ($ns ? ' ' . ($ns === 1 ? 'A subcategoria dela também some.' : 'As ' . $ns . ' subcategorias dela também somem.') : '')
        . ' Links antigos para ela passam a abrir o catálogo com todos os produtos. Dá para desfazer em Cópias de segurança.';
}

function textoApagarSub(array $c, string $sub): string
{
    $links = avisoLinksDaSub($c, $sub, true);
    return 'Apagar a subcategoria “' . $sub . '” de “' . $c['nome'] . '”? Ela não tem produtos. Dá para desfazer em Cópias de segurança.' . ($links !== '' ? ' ' . $links : '');
}

function textoMoverEApagarSub(array $c, string $sub, int $n, array $destCat, string $destSub): string
{
    $links = avisoLinksDaSub($c, $sub, true);
    return 'Mover ' . ($n === 1 ? 'o produto' : 'os ' . $n . ' produtos') . ' de “' . $sub . '” para “' . nomeCatSub($destCat, $destSub)
        . '” e apagar a subcategoria “' . $sub . '” de “' . $c['nome'] . '”? Tudo numa alteração só, com cópia de segurança.' . ($links !== '' ? ' ' . $links : '');
}

/** “Balanças › Pesadoras” (o mesmo texto do nomeDestino da edição em lote). */
function nomeCatSub(array $c, string $sub): string
{
    return (string) $c['nome'] . ' › ' . $sub;
}

/* ------------------------------------------------------------------
   Ações (index.php?a=…) — só administradores (SO_ADMIN)
   ------------------------------------------------------------------ */
/** Volta para a tela, na categoria/subcategoria mexida; com fetch, responde JSON. */
function voltarCategorias(string $msg, string $ancora = '', string $tipo = 'ok', array $json = []): never
{
    if (pedidoJson()) responderJson(['ok' => $tipo !== 'erro', 'msg' => $msg] + $json, $tipo === 'erro' ? 409 : 200);
    avisar($msg, $tipo, $tipo === 'ok' ? ['link' => cfg('url_site') . 'produtos.html', 'textoLink' => 'Ver no site', 'externo' => true] : []);
    redirecionar(link_('categorias') . ($ancora !== '' ? '#' . $ancora : ''));
}

/** Erros de conferência (ErroUsuario) voltam para a tela com o aviso, sem passar pelo index.php. */
function comErrosDeCategorias(callable $fn, string $ancora = ''): void
{
    try {
        $fn();
    } catch (ErroUsuario $e) {
        voltarCategorias($e->getMessage(), $ancora, 'erro');
    }
}

function acaoCategorias(): void
{
    telaCategorias(lerCatalogo());
}

/** Criar categoria (sem 'cat') ou mudar nome/descrição (com 'cat'). */
function acaoCategoriaSalvar(): void
{
    $cat = lerCatalogo();
    $editar = isset($_POST['cat']) && $_POST['cat'] !== '';
    try {
        if ($editar) {
            $c = categoriaDoPedido($cat['categorias'], $_POST['cat']);
            $form = chaveFormCategoria('cat', $c['id']);
            $outros = array_map(static fn($x) => (string) $x['nome'], array_filter($cat['categorias'], static fn($x) => $x['id'] !== $c['id']));
            [$nome, $erro] = nomeDeCategoria($_POST['nome'] ?? '', LIMITES_CATEGORIA['nome'], $outros, 'categoria');
            $erro ??= conflitoNomeCategoria($nome, $c['id'], $cat['categorias']);
            [$desc, $erroD] = descricaoDeCategoria($_POST['descricao'] ?? '');
            $valores = ['nome' => $nome, 'descricao' => $desc];
            if ($erro !== null || $erroD !== null) throw new ErroFormCategoria((string) ($erro ?? $erroD), $form, $valores);
            $mudouNome = $nome !== $c['nome'];
            $mudouDesc = $desc !== (string) ($c['descricao'] ?? '');
            if (!$mudouNome && !$mudouDesc) voltarCategorias('Nada mudou em “' . $c['nome'] . '”.', 'cat-' . $c['id'], 'info');
            $feito = $mudouNome ? 'Renomeou a categoria “' . $c['nome'] . '” para “' . $nome . '”' . ($mudouDesc ? ' (e mudou a descrição)' : '')
                : 'Mudou a descrição da categoria “' . $c['nome'] . '”';
            alterarCatalogo(['tipo' => 'categorias', 'antes' => 'Antes de: ' . ($mudouNome ? 'renomear a categoria “' . $c['nome'] . '”' : 'mudar a descrição da categoria “' . $c['nome'] . '”'),
                'feito' => $feito, 'alvo' => $c['id'], 'alvoNome' => $nome],
                static function (array $atual) use ($c, $nome, $desc): array {
                    $cats = comCategoriaEditada($atual['categorias'], $c['id'], $nome, $desc);
                    exigirCategoriasValidas($cats);
                    return ['categorias' => $cats, 'produtos' => $atual['produtos']];
                });
            voltarCategorias($mudouNome ? 'Pronto: a categoria agora se chama “' . $nome . '”. O endereço dela no site continua o mesmo.' : 'Pronto: a descrição de “' . $nome . '” foi salva.', 'cat-' . $c['id']);
        }
        $form = chaveFormCategoria('nova');
        [$nome, $erro] = nomeDeCategoria($_POST['nome'] ?? '', LIMITES_CATEGORIA['nome'], array_map(static fn($x) => (string) $x['nome'], $cat['categorias']), 'categoria');
        $erro ??= conflitoNomeCategoria($nome, idDeCategoriaNova($nome, $cat['categorias']), $cat['categorias']);
        [$desc, $erroD] = descricaoDeCategoria($_POST['descricao'] ?? '');
        [$subs, $erroS] = subsDigitadas($_POST['subcategorias'] ?? '', $nome);
        $valores = ['nome' => $nome, 'descricao' => $desc, 'subcategorias' => is_string($_POST['subcategorias'] ?? null) ? cortar($_POST['subcategorias'], 4000) : ''];
        if ($erro !== null || $erroD !== null || $erroS !== null) throw new ErroFormCategoria((string) ($erro ?? $erroD ?? $erroS), $form, $valores);
        if (count($cat['categorias']) >= LIMITES_CATEGORIA['categorias']) throw new ErroFormCategoria('O catálogo já tem ' . LIMITES_CATEGORIA['categorias'] . ' categorias, o máximo. Apague uma que não usa mais antes de criar outra.', $form, $valores);
        $novoId = '';
        alterarCatalogo(['tipo' => 'categorias', 'antes' => 'Antes de: criar a categoria “' . $nome . '”',
            'feito' => 'Criou a categoria “' . $nome . '” (' . (count($subs) === 1 ? 'subcategoria “' . $subs[0] . '”' : count($subs) . ' subcategorias') . ')', 'alvoNome' => $nome],
            static function (array $atual) use ($nome, $desc, $subs, &$novoId): array {
                [$cats, $novoId] = comCategoriaNova($atual['categorias'], $nome, $desc, $subs);
                exigirCategoriasValidas($cats);
                return ['categorias' => $cats, 'produtos' => $atual['produtos']];
            });
        voltarCategorias('Pronto: a categoria “' . $nome . '” foi criada, no fim da lista. Ela aparece no site quando tiver produtos: cadastre um produto novo nela ou mova produtos para ela (Produtos → Selecionar → Mover).', 'cat-' . $novoId);
    } catch (ErroFormCategoria $e) {
        http_response_code(422);
        telaCategorias($cat, ['form' => $e->form, 'erro' => $e->getMessage(), 'valores' => $e->valores]);
    } catch (ErroUsuario $e) {
        voltarCategorias($e->getMessage(), '', 'erro');
    }
}

function acaoCategoriaMover(): void
{
    comErrosDeCategorias(static function (): void {
        $dir = ($_POST['dir'] ?? '') === 'cima' ? 'cima' : 'baixo';
        $c = categoriaDoPedido(lerCatalogo()['categorias'], $_POST['cat'] ?? '');
        $frase = '“' . $c['nome'] . '” ' . ($dir === 'cima' ? 'subiu' : 'desceu');
        $pos = 0;
        alterarCatalogo(['tipo' => 'ordem', 'antes' => 'Antes de: mudar a ordem das categorias', 'feito' => 'Mudou a ordem das categorias (' . $frase . ')', 'alvo' => $c['id'], 'alvoNome' => $c['nome']],
            static function (array $atual) use ($c, $dir, &$pos): array {
                $cats = comCategoriaMovida($atual['categorias'], $c['id'], $dir);
                if ($cats === null) throw new ErroUsuario('“' . $c['nome'] . '” já é a ' . ($dir === 'cima' ? 'primeira' : 'última') . ' categoria.');
                $pos = (int) posicaoCategoria($cats, $c['id']) + 1;
                return ['categorias' => $cats, 'produtos' => produtosNaOrdemDasCategorias($cats, $atual['produtos'])];
            });
        voltarCategorias($frase . ': agora é a ' . $pos . 'ª categoria (no site, a ' . $pos . 'ª aba do catálogo).', 'cat-' . $c['id'], 'ok', ['dir' => $dir, 'cat' => $c['id']]);
    });
}

function acaoCategoriaExcluir(bool $post): void
{
    comErrosDeCategorias(static function () use ($post): void {
        $cat = lerCatalogo();
        $c = categoriaDoPedido($cat['categorias'], $post ? ($_POST['cat'] ?? '') : ($_GET['cat'] ?? ''));
        $n = (int) (contagemCategorias($cat['produtos'])[$c['id']]['total'] ?? 0);
        if ($n > 0) {
            throw new ErroUsuario('“' . $c['nome'] . '” tem ' . qtdProdutos($n) . ' e não pode ser apagada. Primeiro mova ' . ($n === 1 ? 'o produto' : 'os produtos')
                . ' para outra categoria (Produtos → Selecionar → Mover, ou “Apagar” em cada subcategoria dela, escolhendo para onde vão).');
        }
        if (count($cat['categorias']) <= 1) throw new ErroUsuario('O catálogo precisa de pelo menos uma categoria.');
        if (!$post || ($_POST['confirmado'] ?? '') !== '1') {
            telaConfirmar('Apagar categoria?', textoApagarCategoria($c), link_('categoria-excluir'), ['cat' => $c['id']], 'Sim, apagar', link_('categorias') . '#cat-' . $c['id'], 'produtos');
            return;
        }
        alterarCatalogo(['tipo' => 'categorias', 'antes' => 'Antes de: apagar a categoria “' . $c['nome'] . '”', 'feito' => 'Apagou a categoria “' . $c['nome'] . '” (estava sem produtos)', 'alvo' => $c['id'], 'alvoNome' => $c['nome']],
            static function (array $atual) use ($c): array {
                return ['categorias' => semCategoria($atual['categorias'], $atual['produtos'], $c['id']), 'produtos' => $atual['produtos']];
            });
        voltarCategorias('A categoria “' . $c['nome'] . '” foi apagada. Para desfazer: Conta → Cópias de segurança.', '', 'ok');
    });
}

/** Criar subcategoria (sem 'sub') ou renomear (com 'sub'): os produtos dela mudam junto. */
function acaoSubcategoriaSalvar(): void
{
    $cat = lerCatalogo();
    try {
        $c = categoriaDoPedido($cat['categorias'], $_POST['cat'] ?? '');
        $renomear = isset($_POST['sub']) && $_POST['sub'] !== '';
        if ($renomear) {
            $de = subDoPedido($c, $_POST['sub']);
            $n = (int) array_search($de, $c['subcategorias'], true) + 1;
            $form = chaveFormCategoria('sub', $c['id'], $n);
            $outros = array_values(array_filter($c['subcategorias'], static fn($s) => $s !== $de));
            [$para, $erro] = nomeDeCategoria($_POST['nome'] ?? '', LIMITES_CATEGORIA['subcategoria'], $outros, 'subcategoria', $c['nome']);
            if ($erro !== null) throw new ErroFormCategoria($erro, $form, ['nome' => $para]);
            if ($para === $de) voltarCategorias('Nada mudou: o nome já era “' . $de . '”.', 'sub-' . $c['id'] . '-' . $n, 'info');
            $qtd = count(idsDaSub($cat['produtos'], $c['id'], $de));
            $mudaram = 0;
            alterarCatalogo(['tipo' => 'categorias', 'antes' => 'Antes de: renomear a subcategoria “' . $de . '” (' . $c['nome'] . ')',
                'feito' => 'Renomeou a subcategoria “' . $de . '” para “' . $para . '” em “' . $c['nome'] . '”' . ($qtd ? ' (' . qtdProdutos($qtd) . ' ' . ($qtd === 1 ? 'mudou' : 'mudaram') . ' junto)' : ''),
                'alvo' => $c['id'], 'alvoNome' => $para],
                static function (array $atual) use ($c, $de, $para, &$mudaram): array {
                    [$cats, $prods, $mudaram] = comSubRenomeada($atual['categorias'], $atual['produtos'], $c['id'], $de, $para);
                    exigirCategoriasValidas($cats);
                    return ['categorias' => $cats, 'produtos' => $prods];
                });
            $links = avisoLinksDaSub($c, $de);
            voltarCategorias('Pronto: “' . $de . '” agora se chama “' . $para . '”' . ($mudaram ? ', e ' . ($mudaram === 1 ? 'o produto dela mudou junto.' : 'os ' . $mudaram . ' produtos dela mudaram junto.') : '.')
                . ($links !== '' ? ' Atenção: ' . $links : ''), 'sub-' . $c['id'] . '-' . $n);
        }
        $form = chaveFormCategoria('subnova', $c['id']);
        [$nome, $erro] = nomeDeCategoria($_POST['nome'] ?? '', LIMITES_CATEGORIA['subcategoria'], $c['subcategorias'], 'subcategoria', $c['nome']);
        if ($erro !== null) throw new ErroFormCategoria($erro, $form, ['nome' => $nome]);
        if (count($c['subcategorias']) >= LIMITES_CATEGORIA['subcategorias']) throw new ErroFormCategoria('“' . $c['nome'] . '” já tem ' . LIMITES_CATEGORIA['subcategorias'] . ' subcategorias, o máximo.', $form, ['nome' => $nome]);
        alterarCatalogo(['tipo' => 'categorias', 'antes' => 'Antes de: criar a subcategoria “' . $nome . '” (' . $c['nome'] . ')',
            'feito' => 'Criou a subcategoria “' . $nome . '” em “' . $c['nome'] . '”', 'alvo' => $c['id'], 'alvoNome' => $nome],
            static function (array $atual) use ($c, $nome): array {
                $cats = comSubNova($atual['categorias'], $c['id'], $nome);
                exigirCategoriasValidas($cats);
                return ['categorias' => $cats, 'produtos' => $atual['produtos']];
            });
        voltarCategorias('Pronto: a subcategoria “' . $nome . '” foi criada em “' . $c['nome'] . '”, no fim da lista. No site ela aparece como filtro de tipo quando tiver produtos.', 'sub-' . $c['id'] . '-' . (count($c['subcategorias']) + 1));
    } catch (ErroFormCategoria $e) {
        http_response_code(422);
        telaCategorias($cat, ['form' => $e->form, 'erro' => $e->getMessage(), 'valores' => $e->valores]);
    } catch (ErroUsuario $e) {
        voltarCategorias($e->getMessage(), '', 'erro');
    }
}

function acaoSubcategoriaMover(): void
{
    comErrosDeCategorias(static function (): void {
        $dir = ($_POST['dir'] ?? '') === 'cima' ? 'cima' : 'baixo';
        $c = categoriaDoPedido(lerCatalogo()['categorias'], $_POST['cat'] ?? '');
        $sub = subDoPedido($c, $_POST['sub'] ?? '');
        $frase = '“' . $sub . '” ' . ($dir === 'cima' ? 'subiu' : 'desceu');
        $pos = 0;
        alterarCatalogo(['tipo' => 'ordem', 'antes' => 'Antes de: mudar a ordem das subcategorias de “' . $c['nome'] . '”',
            'feito' => 'Mudou a ordem das subcategorias de “' . $c['nome'] . '” (' . $frase . ')', 'alvo' => $c['id'], 'alvoNome' => $sub],
            static function (array $atual) use ($c, $sub, $dir, &$pos): array {
                $cats = comSubMovida($atual['categorias'], $c['id'], $sub, $dir);
                if ($cats === null) throw new ErroUsuario('“' . $sub . '” já é a ' . ($dir === 'cima' ? 'primeira' : 'última') . ' subcategoria de “' . $c['nome'] . '”.');
                $pos = (int) array_search($sub, categoriaPorId($cats, $c['id'])['subcategorias'], true) + 1;
                return ['categorias' => $cats, 'produtos' => $atual['produtos']];
            });
        voltarCategorias($frase . ': agora é a ' . $pos . 'ª de “' . $c['nome'] . '” (a ordem dos filtros de tipo no site).', 'sub-' . $c['id'] . '-' . $pos, 'ok', ['dir' => $dir, 'cat' => $c['id']]);
    });
}

/** Apagar subcategoria: vazia, ou com 'destino' ("cat|sub") para onde vão os produtos dela (tudo numa gravação). */
function acaoSubcategoriaExcluir(bool $post): void
{
    comErrosDeCategorias(static function () use ($post): void {
        $cat = lerCatalogo();
        $fonte = $post ? $_POST : $_GET;
        $c = categoriaDoPedido($cat['categorias'], $fonte['cat'] ?? '');
        $sub = subDoPedido($c, $fonte['sub'] ?? '');
        $ancora = 'sub-' . $c['id'] . '-' . ((int) array_search($sub, $c['subcategorias'], true) + 1);
        $ids = idsDaSub($cat['produtos'], $c['id'], $sub);
        $n = count($ids);
        $temDestino = is_string($fonte['destino'] ?? null) && $fonte['destino'] !== '';
        if ($n > 0 && !$temDestino) {
            throw new ErroUsuario('“' . $sub . '” tem ' . qtdProdutos($n) . ' e não foi apagada. Escolha para onde ' . ($n === 1 ? 'ele vai' : 'eles vão') . ' (em “Apagar…”, na subcategoria) e confirme: os produtos mudam e a subcategoria sai, numa alteração só.');
        }
        [$dCat, $dSub] = $n > 0 ? destinoDeCategoria($fonte['destino'], $cat['categorias']) : [null, ''];
        if ($dCat !== null && $dCat['id'] === $c['id'] && $dSub === $sub) throw new ErroUsuario('Escolha outra subcategoria para os produtos (não a que vai ser apagada).');
        if (!$post || ($_POST['confirmado'] ?? '') !== '1') {
            telaConfirmar($n ? 'Mover e apagar?' : 'Apagar subcategoria?', $n ? textoMoverEApagarSub($c, $sub, $n, $dCat, $dSub) : textoApagarSub($c, $sub),
                link_('subcategoria-excluir'), ['cat' => $c['id'], 'sub' => $sub] + ($n ? ['destino' => $dCat['id'] . '|' . $dSub] : []),
                $n ? 'Sim, mover e apagar' : 'Sim, apagar', link_('categorias') . '#' . $ancora, 'produtos');
            return;
        }
        $movidos = 0;
        $feito = $n ? 'Moveu ' . qtdProdutos($n) . ' de “' . $sub . '” para “' . nomeCatSub($dCat, $dSub) . '” e apagou a subcategoria “' . $sub . '” de “' . $c['nome'] . '”'
            : 'Apagou a subcategoria “' . $sub . '” de “' . $c['nome'] . '” (estava sem produtos)';
        alterarCatalogo(['tipo' => 'categorias', 'antes' => 'Antes de: apagar a subcategoria “' . $sub . '” (' . $c['nome'] . ')', 'feito' => $feito, 'alvo' => $c['id'], 'alvoNome' => $sub],
            static function (array $atual) use ($c, $sub, $dCat, $dSub, &$movidos): array {
                $prods = $atual['produtos'];
                $agora = idsDaSub($prods, $c['id'], $sub);
                if ($agora) {
                    // Mudou desde a tela (alguém pôs produtos nela) e não veio destino: não apaga
                    if ($dCat === null) throw new ErroUsuario('“' . $sub . '” ganhou produtos enquanto a tela estava aberta e não foi apagada. Confira e tente de novo.');
                    if (!categoriaPorId($atual['categorias'], $dCat['id']) || !in_array($dSub, categoriaPorId($atual['categorias'], $dCat['id'])['subcategorias'], true)) {
                        throw new ErroUsuario('O destino escolhido não existe mais. Nada foi alterado.');
                    }
                    [$prods, $mudou] = moverProdutosPara($prods, $agora, $dCat['id'], $dSub, $atual['categorias']);
                    $movidos = count($mudou);
                }
                $cats = semSub($atual['categorias'], $c['id'], $sub);
                exigirCategoriasValidas($cats);
                return ['categorias' => $cats, 'produtos' => $prods];
            });
        $msg = $movidos
            ? 'Pronto: ' . ($movidos === 1 ? 'o produto foi movido' : 'os ' . $movidos . ' produtos foram movidos') . ' para “' . nomeCatSub($dCat, $dSub) . '” e a subcategoria “' . $sub . '” foi apagada.'
            : 'A subcategoria “' . $sub . '” foi apagada de “' . $c['nome'] . '”.';
        voltarCategorias($msg . ' Para desfazer: Conta → Cópias de segurança.', 'cat-' . $c['id']);
    });
}
