<?php
/* =====================================================================
   Painel Balanças.com — catálogo
   Lê e grava compartilhado/dados/produtos.js (JSON dentro do JS), valida
   os produtos, guarda cópias de segurança (as 30 mais recentes sempre; o
   resto por tempo — veja config.php) e cuida da lixeira de fotos (foto
   trocada/excluída vai para a pasta privada e volta sozinha se uma cópia
   antiga for restaurada). A "anotação interna" de cada produto fica na
   pasta privada (notas.php), nunca no arquivo público do catálogo.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

const ESTOQUES = [
    'consulte'   => 'Consulte disponibilidade',
    'disponivel' => 'Pronta entrega',
    'encomenda'  => 'Sob encomenda',
    'esgotado'   => 'Esgotado',
];
// Rótulos curtos para o seletor da lista: o mesmo nome do site, só encurtado
const ESTOQUES_CURTO = [
    'consulte'   => 'Consulte',
    'disponivel' => 'Pronta entrega',
    'encomenda'  => 'Sob encomenda',
    'esgotado'   => 'Esgotado',
];
const CONDICOES = ['novo' => 'Novo', 'seminovo' => 'Seminovo'];
const LIMITES = [
    'id' => 80, 'nome' => 90, 'marca' => 40, 'resumo' => 220, 'descricao' => 3000,
    'spec_nome' => 60, 'spec_valor' => 300, 'specs' => 24, 'preco' => 60, 'nota' => 300,
];
// Ordem dos campos no arquivo (campos desconhecidos são mantidos, depois destes)
const ORDEM_CAMPOS = ['id', 'nome', 'marca', 'categoria', 'subcategoria', 'resumo', 'descricao', 'especificacoes',
    'imagem', 'recorte', 'estoque', 'condicao', 'preco', 'destaque', 'visivel', 'nota'];
const RE_ID = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
const RE_ARQUIVO_FOTO = '/^[A-Za-z0-9][A-Za-z0-9._-]{0,120}\.(?:webp|jpe?g|png)$/';
// Fotos criadas pelo painel: <id>-<10 hex>.<ext>
const RE_FOTO_DO_PAINEL = '/^[a-z0-9-]{1,60}-[0-9a-f]{10}\.(?:webp|jpg|png)$/';
// Cópias de segurança: copia-AAAAMMDD-HHMMSS-mmm-xxxx-tipo.php (mmm = milésimos: a ordem
// alfabética é a ordem em que foram feitas). Cópias antigas não têm -mmm nem -tipo.
const RE_COPIA = '/^copia-(\d{8})-(\d{6})(?:-\d{3})?-[0-9a-f]{4}(?:-([a-z]{3,12}))?\.php$/';
// Ações seguidas desses tipos (em poucos minutos) viram uma cópia só
const TIPOS_JUNTAR = ['ordem', 'estoque', 'destaque', 'visivel'];
// Cópias que ficam guardadas mais tempo (config: copias_protegidas_dias); "pacote" = antes de aplicar uma atualização do catálogo
const TIPOS_PROTEGIDOS = ['excluir', 'restaurar', 'pacote'];
// Página inicial do site (js/home-v3.js): quantos destaques cabem e a ordem do estoque no site
const HOME_MAX_DESTAQUES = 8;
const ORDEM_ESTOQUE_SITE = ['disponivel' => 0, 'consulte' => 1, 'encomenda' => 1, 'esgotado' => 2];
// Categorias e subcategorias (tela Categorias, só administradores: lib/categorias.php). O código (id)
// da categoria nunca muda depois de criada: links antigos (produtos.html?cat=balancas) continuam valendo.
// Os produtos guardam o id da categoria e o NOME da subcategoria.
const LIMITES_CATEGORIA = ['nome' => 40, 'descricao' => 200, 'subcategoria' => 60, 'categorias' => 12, 'subcategorias' => 30];
// "todos" é a aba "Todos" do catálogo do site (js/catalogo.js): nenhuma categoria pode ter esse código;
// "constructor" é nome de uma função de todo objeto do JavaScript (uma segunda trava: o site usa mapas sem protótipo)
const IDS_CATEGORIA_RESERVADOS = ['todos', 'constructor'];
// Nome curto das categorias que vieram com o site (abas do catálogo, chips do painel): vale enquanto a
// categoria tiver o nome original; renomeada (ou categoria nova), vale o nome do catálogo. O mesmo
// CAT_CURTO de linha-verde/js/core.js: se mudar aqui, mude lá.
const CATEGORIAS_CURTAS = [
    'balancas'    => ['Balanças', 'Balanças'],
    'automacao'   => ['Automação', 'Automação Comercial'],
    'informatica' => ['Informática', 'Informática e PDV'],
];

final class ErroCatalogo extends RuntimeException {}

function arquivoCatalogo(): string
{
    return cfg('compartilhado') . '/dados/produtos.js';
}
// Pastas de imagens que o painel grava (dentro de compartilhado/img/): fotos, recortes e logos de clientes
const PASTAS_IMAGENS = ['produtos' => 'produtos', 'recortes' => 'produtos-recorte', 'clientes' => 'clientes'];
function pastaFotos(string $tipo = 'produtos'): string
{
    return cfg('compartilhado') . '/img/' . (PASTAS_IMAGENS[$tipo] ?? 'produtos');
}
function urlFoto(string $arquivo, string $tipo = 'produtos'): string
{
    return cfg('url_compartilhado') . 'img/' . (PASTAS_IMAGENS[$tipo] ?? 'produtos') . '/' . rawurlencode($arquivo);
}

/* ------------------------------------------------------------------
   Formato do arquivo (igual ao de ferramentas/converter-produtos.mjs)
   ------------------------------------------------------------------ */
function cabecalhoCatalogo(string $linhaData): string
{
    return implode("\n", [
        '/* =====================================================================',
        '   CATÁLOGO DE PRODUTOS — Balanças.com',
        '   ---------------------------------------------------------------------',
        '   Arquivo GERADO pelo painel de administração do site. Para adicionar,',
        '   editar, esconder ou excluir produtos e trocar fotos, use o painel:',
        '   ele confere os dados, guarda cópias de segurança e não deixa o',
        '   catálogo quebrar. O que for editado à mão aqui some na próxima vez',
        '   que o painel salvar.',
        '',
        '   Formato: JSON puro dentro de window.CATEGORIAS e window.PRODUTOS.',
        '   Campos de cada produto: id, nome, marca, categoria, subcategoria,',
        '   resumo, descricao, especificacoes ([["Nome", "Valor"], ...]),',
        '   imagem (em compartilhado/img/produtos/), recorte (opcional, em',
        '   compartilhado/img/produtos-recorte/), estoque (consulte | disponivel |',
        '   encomenda | esgotado), condicao (novo | seminovo), preco ("" = Consulte),',
        '   destaque e visivel. (A anotação interna de cada produto fica só no',
        '   painel, na pasta privada: não é publicada aqui.)',
        '   ' . str_replace('*/', '* /', $linhaData),
        '   ===================================================================== */',
        '',
    ]);
}

function montarCatalogo(array $categorias, array $produtos): string
{
    return cabecalhoCatalogo('Última gravação pelo painel: ' . date('d/m/Y') . ' ' . date('H:i') . '.')
        . 'window.CATEGORIAS = ' . json_encode(array_values($categorias), JSON_GRAVAR | JSON_THROW_ON_ERROR) . ";\n"
        . 'window.PRODUTOS = ' . json_encode(array_values($produtos), JSON_GRAVAR | JSON_THROW_ON_ERROR) . ";\n";
}

/** Acha o JSON logo depois de "window.NOME =" sem executar nada (respeita strings). */
function jsonDepois(string $src, string $nome)
{
    if (!preg_match('/(?:^|\n)window\.' . $nome . '\s*=\s*/', $src, $m, PREG_OFFSET_CAPTURE)) {
        throw new ErroCatalogo('não achei "window.' . $nome . ' ="');
    }
    $ini = $m[0][1] + strlen($m[0][0]);
    if (!isset($src[$ini]) || $src[$ini] !== '[') throw new ErroCatalogo('window.' . $nome . ' não começa com [');
    $prof = 0; $str = false; $n = strlen($src);
    for ($i = $ini; $i < $n; $i++) {
        $c = $src[$i];
        if ($str) {
            if ($c === '\\') $i++;
            elseif ($c === '"') $str = false;
            continue;
        }
        if ($c === '"') $str = true;
        elseif ($c === '[' || $c === '{') $prof++;
        elseif ($c === ']' || $c === '}') {
            if (--$prof === 0) {
                try {
                    return json_decode(substr($src, $ini, $i - $ini + 1), true, 32, JSON_THROW_ON_ERROR);
                } catch (JsonException $ex) {
                    throw new ErroCatalogo('o conteúdo de window.' . $nome . ' não é JSON válido (' . $ex->getMessage() . ')');
                }
            }
        }
    }
    throw new ErroCatalogo('window.' . $nome . ' não termina');
}

function interpretarCatalogo(string $src): array
{
    $src = preg_replace('/^\xEF\xBB\xBF/', '', $src) ?? $src;
    $cats = jsonDepois($src, 'CATEGORIAS');
    $prods = jsonDepois($src, 'PRODUTOS');
    if (!is_array($cats) || !array_is_list($cats) || !is_array($prods) || !array_is_list($prods)) {
        throw new ErroCatalogo('CATEGORIAS e PRODUTOS precisam ser listas');
    }
    $idsCat = [];
    foreach ($cats as $c) {
        if (!is_array($c) || !is_string($c['id'] ?? null) || !is_string($c['nome'] ?? null) || !is_array($c['subcategorias'] ?? null)) {
            throw new ErroCatalogo('categoria sem id, nome ou subcategorias');
        }
        if (!preg_match(RE_ID, $c['id'])) throw new ErroCatalogo('código de categoria inválido: "' . cortar($c['id'], 40) . '"');
        if (isset($idsCat[$c['id']])) throw new ErroCatalogo('categoria repetida: ' . $c['id']);
        $idsCat[$c['id']] = true;
        if (!array_is_list($c['subcategorias']) || count(array_filter($c['subcategorias'], 'is_string')) !== count($c['subcategorias'])) {
            throw new ErroCatalogo('as subcategorias de "' . cortar($c['nome'], 40) . '" precisam ser uma lista de nomes');
        }
    }
    $ids = [];
    foreach ($prods as $i => $p) {
        if (!is_array($p) || !is_string($p['id'] ?? null) || $p['id'] === '') throw new ErroCatalogo('produto ' . ($i + 1) . ' sem id');
        if (isset($ids[$p['id']])) throw new ErroCatalogo('id repetido: ' . $p['id']);
        $ids[$p['id']] = true;
    }
    return ['categorias' => $cats, 'produtos' => $prods];
}

/** Lê o catálogo atual (com as anotações internas da pasta privada). Lança ErroCatalogo com explicação se não der. */
function lerCatalogo(): array
{
    $arq = arquivoCatalogo();
    if (!@is_file($arq)) throw new ErroCatalogo('o arquivo compartilhado/dados/produtos.js não foi encontrado');
    $src = @file_get_contents($arq);
    if ($src === false) throw new ErroCatalogo('sem permissão para ler compartilhado/dados/produtos.js');
    $d = interpretarCatalogo($src);
    $d['produtos'] = juntarNotas($d['produtos']);
    $d['bruto'] = $src;
    return $d;
}

/* ------------------------------------------------------------------
   Anotação interna: fica na pasta privada (notas.php), por código do produto.
   Notas antigas que ainda estejam no arquivo público passam para lá na
   próxima gravação.
   ------------------------------------------------------------------ */
function juntarNotas(array $produtos): array
{
    $notas = lerJsonPrivado('notas.php', []);
    if (!is_array($notas) || !$notas) return $produtos;
    foreach ($produtos as $i => $p) {
        $id = (string) ($p['id'] ?? '');
        if (!isset($p['nota']) && isset($notas[$id]) && is_string($notas[$id]) && $notas[$id] !== '') {
            $p['nota'] = $notas[$id];
            $produtos[$i] = ordenarCampos($p);
        }
    }
    return $produtos;
}

/** [produtos sem a nota (vão para o arquivo público), [id => nota] (vai para a pasta privada)] */
function separarNotas(array $produtos): array
{
    $notas = [];
    foreach ($produtos as $i => $p) {
        if (!array_key_exists('nota', $p)) continue;
        if (is_string($p['nota']) && $p['nota'] !== '' && is_string($p['id'] ?? null)) $notas[$p['id']] = $p['nota'];
        unset($p['nota']);
        $produtos[$i] = $p;
    }
    return [array_values($produtos), $notas];
}

/** Grava o catálogo público (sem notas) e as notas na pasta privada. */
function gravarCatalogo(array $categorias, array $produtos): string
{
    [$publicos, $notas] = separarNotas($produtos);
    $texto = montarCatalogo($categorias, $publicos);
    // Confere o que vai ser gravado antes de substituir o arquivo do site
    $conf = interpretarCatalogo($texto);
    if ($conf['produtos'] !== $publicos) throw new RuntimeException('conferência do catálogo falhou');
    if (!@is_writable(arquivoCatalogo()) && !@is_writable(dirname(arquivoCatalogo()))) {
        throw new ErroUsuario('O servidor não deixa gravar compartilhado/dados/produtos.js. Confira as permissões (644) no Gerenciador de Arquivos.');
    }
    gravarAtomico(arquivoCatalogo(), $texto, 0644);
    gravarJsonPrivado('notas.php', (object) $notas);
    return $texto;
}

/**
 * Altera o catálogo com segurança: trava → lê → $fn(catálogo) devolve a lista nova de
 * produtos → cópia de segurança do estado anterior → grava (temporário + rename) →
 * acerta a lixeira de fotos.
 * $acao = ['tipo' => 'excluir'…, 'antes' => 'Antes de: excluir “X”', 'feito' => 'Excluiu “X”', 'alvo' => id, 'alvoNome' => 'X']
 * Devolve o catálogo novo e o nome da cópia de segurança ('copia').
 */
function alterarCatalogo(array $acao, callable $fn): array
{
    $r = comTrava('catalogo', static function () use ($acao, $fn): array {
        $antes = lerCatalogo();
        $novo = $fn($antes);
        // Quem só sabe o que fez depois de olhar o catálogo (ex.: atualização do catálogo) devolve a frase final
        if (is_string($novo['feito'] ?? null) && $novo['feito'] !== '') $acao['feito'] = $novo['feito'];
        $categorias = $novo['categorias'] ?? $antes['categorias'];
        $produtos = array_values($novo['produtos']);
        // Confere antes de mexer em qualquer coisa (a gravação confere de novo)
        [$publicos] = separarNotas($produtos);
        $conf = interpretarCatalogo(montarCatalogo($categorias, $publicos));
        if ($conf['produtos'] !== $publicos) throw new RuntimeException('conferência do catálogo falhou');
        $hashAntes = @hash_file('sha256', arquivoCatalogo());
        $desfazerCopia = null;
        $copia = fazerCopia($antes, $acao, $desfazerCopia);
        try {
            gravarCatalogo($categorias, $produtos);
        } catch (Throwable $t) {
            // O produtos.js não mudou: a cópia não desfaz nada e só confundiria quem for restaurar
            // ("Aplicou…" que nunca aconteceu). Se o arquivo chegou a mudar, a cópia fica, como deve.
            if (is_callable($desfazerCopia) && @hash_file('sha256', arquivoCatalogo()) === $hashAntes) $desfazerCopia();
            throw $t;
        }
        sincronizarFotos($antes['produtos'], $produtos);
        registrar('catalogo', (string) ($acao['feito'] ?? $acao['antes'] ?? ''));
        return ['categorias' => $categorias, 'produtos' => $produtos, 'copia' => $copia];
    });
    // A foto do produto da capa pode ter mudado: a capa (ajustes.js) acompanha. Fora da trava do
    // catálogo; um problema aqui não desfaz o que já foi gravado.
    $r['capaFoto'] = null;
    if (function_exists('sincronizarFotoDaCapa')) {
        try { $r['capaFoto'] = sincronizarFotoDaCapa($r['produtos']); }
        catch (Throwable $t) { error_log('painel: capa: ' . $t->getMessage()); }
    }
    return $r;
}

/* ------------------------------------------------------------------
   Capa e página inicial do site (mesma regra de js/home-v3.js)
   ------------------------------------------------------------------ */
/** Código do produto da capa: o escolhido em Empresa → Capa do site (ajustes.js "capa", lib/ajustes.php);
 *  sem escolha, o data-hero-id do index.html do site; senão o do config.php. */
function produtoCapa(): string
{
    static $id = null;
    if ($id !== null) return $id;
    $aj = function_exists('produtoCapaAjustes') ? produtoCapaAjustes() : null;   // capa escolhida no painel
    if ($aj !== null) return $id = $aj;
    return $id = produtoCapaHtml();
}

/** A capa que vem no index.html (a Toledo Prix 7E): o data-hero-id; senão o produto_capa do config.php. */
function produtoCapaHtml(): string
{
    static $id = null;
    if ($id !== null) return $id;
    $id = (string) (cfg('produto_capa') ?? '');
    $html = @file_get_contents(dirname((string) cfg('compartilhado')) . '/index.html', false, null, 0, 600000);
    if (is_string($html) && preg_match('/\bdata-hero-id="([a-z0-9]+(?:-[a-z0-9]+)*)"/', $html, $m)) $id = $m[1];
    return $id;
}

/** O produto que a página inicial mostra de fato na capa, com estes $produtos: o escolhido no painel, ou — se ele
 *  saiu do site, foi excluído ou ficou sem recorte (problemaCapa) — a capa do index.html, como faz o js/home-v3.js. */
function capaEfetiva(array $produtos): string
{
    $id = produtoCapa();
    if (!function_exists('problemaCapa') || $id === produtoCapaHtml()) return $id;
    return problemaCapa($produtos, $id) === null ? $id : produtoCapaHtml();
}

/**
 * Destaques que aparecem na página inicial, na ordem: até 8, revezando as categorias
 * (o 1º destaque de cada categoria, depois o 2º de cada…). Dentro da categoria vale a
 * ordem do site: Pronta entrega primeiro, Esgotado por último, depois a ordem das setas.
 * O produto da capa fica de fora (ele já está no topo da página).
 */
function destaquesDaHome(array $produtos): array
{
    $capa = capaEfetiva($produtos);
    $lista = [];
    foreach (array_values($produtos) as $i => $p) {
        if (empty($p['destaque']) || ($p['visivel'] ?? true) === false || ($p['id'] ?? '') === $capa) continue;
        $lista[] = [ORDEM_ESTOQUE_SITE[(string) ($p['estoque'] ?? '')] ?? 1, $i, $p];
    }
    usort($lista, static fn(array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
    $grupos = [];
    foreach ($lista as $x) $grupos[(string) ($x[2]['categoria'] ?? '')][] = (string) $x[2]['id'];
    $out = [];
    while (count($out) < HOME_MAX_DESTAQUES) {
        $antes = count($out);
        foreach ($grupos as $c => $g) {
            if (count($out) < HOME_MAX_DESTAQUES && $g) $out[] = array_shift($grupos[$c]);
        }
        if (count($out) === $antes) break;
    }
    return $out;
}

/** Frase sobre o destaque na página inicial (para avisos), ou '' quando não precisa dizer nada. */
function fraseHome(array $p, array $produtos): string
{
    $id = (string) ($p['id'] ?? '');
    if ($id === capaEfetiva($produtos)) return ' É o produto da capa: já aparece no topo da página inicial.';
    if (empty($p['destaque']) || ($p['visivel'] ?? true) === false) return '';
    $home = destaquesDaHome($produtos);
    $pos = array_search($id, $home, true);
    if ($pos !== false) return ' Aparece na página inicial (destaque ' . ($pos + 1) . ' de ' . count($home) . ').';
    return ' Está em destaque no catálogo, mas não entrou na página inicial: cabem ' . HOME_MAX_DESTAQUES
        . ' destaques e já há ' . HOME_MAX_DESTAQUES . ' na frente dele. Para ele entrar, tire a estrela de outro ou suba este com as setas.';
}

/* ------------------------------------------------------------------
   Busca, posição, slug
   ------------------------------------------------------------------ */
function indicePorId(array $produtos, string $id): ?int
{
    foreach ($produtos as $i => $p) if (($p['id'] ?? null) === $id) return $i;
    return null;
}

function categoriaPorId(array $categorias, string $id): ?array
{
    foreach ($categorias as $c) if ($c['id'] === $id) return $c;
    return null;
}

/* ------------------------------------------------------------------
   Categorias: nomes, contagens e conferência (a tela fica em lib/categorias.php)
   ------------------------------------------------------------------ */
/** Nome curto da categoria (abas e chips): o original encurtado enquanto não foi renomeada; senão o nome. */
function categoriaCurta(array $c): string
{
    $id = (string) ($c['id'] ?? '');
    $nome = (string) ($c['nome'] ?? '');
    $orig = CATEGORIAS_CURTAS[$id] ?? null;
    if ($orig !== null && ($nome === '' || $nome === $orig[1])) return $orig[0];
    return $nome !== '' ? $nome : $id;
}

/** Nome comparável: sem acento, sem diferença de maiúsculas e de espaços ("Balanças " = "balancas"). */
function chaveDeNome(string $s): string
{
    return trim(preg_replace('/\s+/u', ' ', semAcento($s)) ?? '');
}

/** Quantos produtos há em cada categoria e subcategoria: [id => ['total' => n, 'subs' => [nome => n]]]. */
function contagemCategorias(array $produtos): array
{
    $n = [];
    foreach ($produtos as $p) {
        $c = (string) ($p['categoria'] ?? '');
        $s = (string) ($p['subcategoria'] ?? '');
        $n[$c]['total'] = ($n[$c]['total'] ?? 0) + 1;
        $n[$c]['subs'][$s] = ($n[$c]['subs'][$s] ?? 0) + 1;
    }
    return $n;
}

/** Produtos cuja categoria ou subcategoria não existe no catálogo (editados à mão ou vindos de cópia antiga). */
function produtosForaDasCategorias(array $categorias, array $produtos): array
{
    $subs = [];
    foreach ($categorias as $c) $subs[(string) $c['id']] = array_fill_keys(array_map('strval', $c['subcategorias']), true);
    return array_values(array_filter($produtos, static fn($p) => !isset($subs[(string) ($p['categoria'] ?? '')][(string) ($p['subcategoria'] ?? '')])));
}

/**
 * Confere a lista de categorias antes de gravar (a tela Categorias e quem mais mexer nelas):
 * códigos únicos e válidos, nomes preenchidos e sem repetição, subcategorias sem repetição na
 * mesma categoria. Devolve a lista de problemas (vazia = tudo certo).
 */
function problemasDasCategorias(array $categorias): array
{
    $out = [];
    $ids = []; $nomes = [];
    if (count($categorias) > LIMITES_CATEGORIA['categorias']) $out[] = 'no máximo ' . LIMITES_CATEGORIA['categorias'] . ' categorias';
    foreach ($categorias as $c) {
        $id = (string) ($c['id'] ?? '');
        $nome = (string) ($c['nome'] ?? '');
        if (!preg_match(RE_ID, $id) || strlen($id) > LIMITES['id'] || in_array($id, IDS_CATEGORIA_RESERVADOS, true)) $out[] = 'código de categoria inválido: ' . $id;
        if (isset($ids[$id])) $out[] = 'categoria repetida: ' . $id;
        $ids[$id] = true;
        if (trim($nome) === '' || tamanho($nome) > LIMITES_CATEGORIA['nome']) $out[] = 'nome de categoria vazio ou comprido demais: ' . $id;
        // O nome e o nome curto (o que as abas do site e os chips mostram) não podem repetir o de outra categoria
        $minhas = array_unique([chaveDeNome($nome), chaveDeNome(categoriaCurta($c))]);
        foreach ($minhas as $k) if (isset($nomes[$k])) { $out[] = 'duas categorias que aparecem com o nome “' . $nomes[$k] . '”'; break; }
        foreach ($minhas as $k) $nomes[$k] ??= categoriaCurta($c);
        if (tamanho((string) ($c['descricao'] ?? '')) > LIMITES_CATEGORIA['descricao']) $out[] = 'descrição comprida demais: ' . $id;
        $subs = is_array($c['subcategorias'] ?? null) ? $c['subcategorias'] : [];
        if (count($subs) > LIMITES_CATEGORIA['subcategorias']) $out[] = 'subcategorias demais em ' . $nome;
        $vistas = [];
        foreach ($subs as $s) {
            if (!is_string($s) || trim($s) === '' || tamanho($s) > LIMITES_CATEGORIA['subcategoria']) { $out[] = 'subcategoria vazia ou comprida demais em ' . $nome; continue; }
            if (isset($vistas[chaveDeNome($s)])) $out[] = 'subcategoria repetida em ' . $nome . ': ' . $s;
            $vistas[chaveDeNome($s)] = true;
        }
    }
    return $out;
}

function nomeCompleto(array $p): string
{
    return trim(($p['marca'] ?? '') . ' ' . ($p['nome'] ?? ''));
}

function semAcento(string $s): string
{
    static $mapa = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a', 'Å' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'Í' => 'i', 'Ì' => 'i', 'Î' => 'i', 'Ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'Ó' => 'o', 'Ò' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'Ú' => 'u', 'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u',
        'ç' => 'c', 'Ç' => 'c', 'ñ' => 'n', 'Ñ' => 'n', 'ý' => 'y', 'ÿ' => 'y', 'Ý' => 'y',
        'º' => 'o', 'ª' => 'a', '°' => 'o', '&' => ' e ', '+' => ' plus ',
    ];
    return strtolower(strtr($s, $mapa));
}

function slug(string $s, int $max = 80): string
{
    $s = trim(preg_replace('/[^a-z0-9]+/', '-', semAcento($s)) ?? '', '-');
    if (strlen($s) > $max) $s = rtrim(substr($s, 0, $max), '-');
    return $s;
}

function idUnico(string $base, array $produtos, ?string $ignorar = null): string
{
    $base = $base !== '' ? $base : 'produto';
    $usados = [];
    foreach ($produtos as $p) if (($p['id'] ?? '') !== $ignorar) $usados[$p['id']] = true;
    $id = $base;
    for ($n = 2; isset($usados[$id]); $n++) $id = rtrim(substr($base, 0, LIMITES['id'] - strlen((string) $n) - 1), '-') . '-' . $n;
    return $id;
}

/** Texto de busca sem acento (para filtrar a lista). */
function textoBusca(array $p, array $categorias): string
{
    $c = categoriaPorId($categorias, (string) ($p['categoria'] ?? ''));
    return semAcento(implode(' ', [$p['nome'] ?? '', $p['marca'] ?? '', $p['subcategoria'] ?? '', $p['id'] ?? '', $c['nome'] ?? '', $p['resumo'] ?? '']));
}

/* ------------------------------------------------------------------
   Validação do formulário
   ------------------------------------------------------------------ */
function textoUtf8($v): ?string
{
    if (!is_string($v)) return '';
    if (!preg_match('//u', $v)) return null;   // não é UTF-8 válido
    if (class_exists('Normalizer')) $v = Normalizer::normalize($v, Normalizer::FORM_C) ?: $v;
    return $v;
}

function limparLinha($v): ?string
{
    $v = textoUtf8($v);
    if ($v === null) return null;
    $v = preg_replace('/[\x00-\x1F\x7F\x{200B}-\x{200F}\x{2028}\x{2029}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]+/u', ' ', $v) ?? '';
    return trim(preg_replace('/\s+/u', ' ', $v) ?? '');
}

function limparTexto($v): ?string
{
    $v = textoUtf8($v);
    if ($v === null) return null;
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    $v = preg_replace('/[\x00-\x09\x0B-\x1F\x7F\x{200B}-\x{200F}\x{2028}\x{2029}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]+/u', ' ', $v) ?? '';
    $v = preg_replace('/[ \t]+\n/u', "\n", $v) ?? '';
    $v = preg_replace('/\n{3,}/u', "\n\n", $v) ?? '';
    return trim(preg_replace('/[ ]{2,}/u', ' ', $v) ?? '');
}

/**
 * Monta o produto a partir do formulário. Devolve [produto, erros por campo].
 * $atual = produto sendo editado (null = novo). Campos que o formulário não
 * conhece (ex.: recorte, outros futuros) são preservados.
 */
function produtoDoFormulario(array $f, array $categorias, array $produtos, ?array $atual): array
{
    $erros = [];
    $txt = static function (string $campo, bool $multi, bool $obrig, string $rotulo) use ($f, &$erros): string {
        $v = $multi ? limparTexto($f[$campo] ?? '') : limparLinha($f[$campo] ?? '');
        if ($v === null) { $erros[$campo] = $rotulo . ': há caracteres inválidos. Apague e digite de novo.'; return ''; }
        if ($obrig && $v === '') $erros[$campo] = $rotulo . ': preencha este campo.';
        elseif (tamanho($v) > LIMITES[$campo]) $erros[$campo] = $rotulo . ': no máximo ' . LIMITES[$campo] . ' caracteres (tem ' . tamanho($v) . ').';
        return $v;
    };
    $nome = $txt('nome', false, true, 'Nome');
    $marca = $txt('marca', false, true, 'Marca');
    $resumo = $txt('resumo', false, true, 'Resumo');
    $descricao = $txt('descricao', true, false, 'Descrição');
    $preco = $txt('preco', false, false, 'Preço');
    $nota = $txt('nota', true, false, 'Anotação');

    $categoria = is_string($f['categoria'] ?? null) ? $f['categoria'] : '';
    $cat = categoriaPorId($categorias, $categoria);
    if (!$cat) $erros['categoria'] = 'Escolha a categoria.';
    $sub = limparLinha($f['subcategoria'] ?? '') ?? '';
    if ($cat && !in_array($sub, $cat['subcategorias'], true)) $erros['subcategoria'] = 'Escolha uma subcategoria de “' . $cat['nome'] . '”.';

    $estoque = is_string($f['estoque'] ?? null) ? $f['estoque'] : '';
    if (!isset(ESTOQUES[$estoque])) $erros['estoque'] = 'Escolha a situação do estoque.';
    $condicao = is_string($f['condicao'] ?? null) ? $f['condicao'] : '';
    if (!isset(CONDICOES[$condicao])) $erros['condicao'] = 'Escolha a condição (novo ou seminovo).';

    // Especificações: linhas nome/valor; linha vazia é ignorada
    $specs = [];
    $nomes = is_array($f['spec_nome'] ?? null) ? array_values($f['spec_nome']) : [];
    $valores = is_array($f['spec_valor'] ?? null) ? array_values($f['spec_valor']) : [];
    for ($i = 0, $n = max(count($nomes), count($valores)); $i < $n && $i < 100; $i++) {
        $sn = limparLinha($nomes[$i] ?? '');
        $sv = limparLinha($valores[$i] ?? '');
        if ($sn === null || $sv === null) { $erros['especificacoes'] = 'Ficha técnica: há caracteres inválidos.'; continue; }
        if ($sn === '' && $sv === '') continue;
        if ($sn === '' || $sv === '') { $erros['especificacoes'] = 'Ficha técnica: cada linha precisa do nome e do valor (ex.: Capacidade · 15 kg).'; continue; }
        if (tamanho($sn) > LIMITES['spec_nome'] || tamanho($sv) > LIMITES['spec_valor']) {
            $erros['especificacoes'] = 'Ficha técnica: nome até ' . LIMITES['spec_nome'] . ' e valor até ' . LIMITES['spec_valor'] . ' caracteres.';
            continue;
        }
        $specs[] = [$sn, $sv];
    }
    if (count($specs) > LIMITES['specs']) $erros['especificacoes'] = 'Ficha técnica: no máximo ' . LIMITES['specs'] . ' linhas.';

    // Código (id): vazio = gerado de marca + nome
    // id_auto=1: o código foi preenchido sozinho pela tela (não digitado) → o servidor garante que seja único
    $idDigitado = ($f['id_auto'] ?? '') === '1' && $atual === null ? '' : strtolower(limparLinha($f['id'] ?? '') ?? '');
    $idAtual = $atual['id'] ?? null;
    if ($idDigitado === '') {
        $id = idUnico(slug($marca . ' ' . $nome), $produtos, $idAtual);
    } else {
        $id = $idDigitado;
        if (strlen($id) > LIMITES['id'] || !preg_match(RE_ID, $id)) {
            $erros['id'] = 'Código: só letras minúsculas sem acento, números e hífen (ex.: toledo-prix-7e).';
        } elseif ($id !== $idAtual && indicePorId($produtos, $id) !== null) {
            $erros['id'] = 'Código: já existe outro produto com “' . $id . '”. Use outro (ex.: ' . idUnico($id, $produtos) . ').';
        }
    }

    $novo = [
        'id' => $id, 'nome' => $nome, 'marca' => $marca, 'categoria' => $categoria, 'subcategoria' => $sub,
        'resumo' => $resumo, 'descricao' => $descricao, 'especificacoes' => $specs,
        'imagem' => $atual['imagem'] ?? '',
        'estoque' => $estoque, 'condicao' => $condicao, 'preco' => $preco,
        'destaque' => !empty($f['destaque']), 'visivel' => !empty($f['visivel']),
        'nota' => $nota,   // vazia sai do arquivo (ordenarCampos)
    ];
    if (isset($atual['recorte'])) $novo['recorte'] = $atual['recorte'];
    // Campos que o formulário não conhece continuam como estavam
    return [ordenarCampos($novo + ($atual ?? [])), $erros];
}

function ordenarCampos(array $p): array
{
    $out = [];
    foreach (ORDEM_CAMPOS as $k) if (array_key_exists($k, $p)) $out[$k] = $p[$k];
    foreach ($p as $k => $v) if (!array_key_exists($k, $out)) $out[$k] = $v;
    if (($out['nota'] ?? null) === '') unset($out['nota']);
    return $out;
}

/**
 * Posição para um produto novo (ou que mudou de categoria): logo após o último da mesma categoria.
 * Categoria ainda vazia: no lugar dela na ordem das categorias ($categorias) — depois do último produto das
 * categorias de cima, ou antes do primeiro das de baixo (no catálogo "Todos", a categoria de cima vem antes).
 */
function posicaoNaCategoria(array $produtos, string $categoria, array $categorias = []): int
{
    $pos = null;
    foreach ($produtos as $i => $p) if (($p['categoria'] ?? '') === $categoria) $pos = $i + 1;
    if ($pos !== null) return $pos;
    $ordem = array_values(array_map(static fn($c) => (string) ($c['id'] ?? ''), $categorias));
    $k = array_search($categoria, $ordem, true);
    if ($k === false) return count($produtos);
    $antes = array_fill_keys(array_slice($ordem, 0, $k), true);
    $depois = array_fill_keys(array_slice($ordem, $k + 1), true);
    $ultimoAntes = null; $primeiroDepois = null;
    foreach (array_values($produtos) as $i => $p) {
        $c = (string) ($p['categoria'] ?? '');
        if (isset($antes[$c])) $ultimoAntes = $i + 1;
        if (isset($depois[$c]) && $primeiroDepois === null) $primeiroDepois = $i;
    }
    return $ultimoAntes ?? $primeiroDepois ?? count($produtos);
}

/** Troca o produto de lugar com o vizinho da mesma categoria. */
function moverProduto(array $produtos, string $id, string $direcao): ?array
{
    $i = indicePorId($produtos, $id);
    if ($i === null) return null;
    $cat = $produtos[$i]['categoria'] ?? '';
    $passo = $direcao === 'cima' ? -1 : 1;
    for ($j = $i + $passo; $j >= 0 && $j < count($produtos); $j += $passo) {
        if (($produtos[$j]['categoria'] ?? '') === $cat) {
            [$produtos[$i], $produtos[$j]] = [$produtos[$j], $produtos[$i]];
            return $produtos;
        }
    }
    return null;   // já é o primeiro/último da categoria
}

/* ------------------------------------------------------------------
   Cópias de segurança (pasta privada copias/)
   ------------------------------------------------------------------ */
/**
 * Guarda o catálogo de antes da alteração. Ações seguidas do mesmo tipo (ordem, estoque,
 * destaque, no site), feitas em menos de 10 min, ficam numa cópia só: a de antes da primeira.
 * Devolve o nome da cópia; em $desfazer, uma função que desfaz o que foi feito aqui (apaga a
 * cópia nova ou devolve a juntada como estava), para quando a gravação do catálogo falha.
 */
function fazerCopia(array $catalogo, array $acao, ?callable &$desfazer = null): string
{
    $tipo = preg_match('/^[a-z]{3,12}$/', (string) ($acao['tipo'] ?? '')) ? (string) $acao['tipo'] : 'outro';
    $micro = microtime(true);
    $agora = (int) $micro;
    $usuario = usuarioLogado() ?? '';
    if (in_array($tipo, TIPOS_JUNTAR, true)) {
        $ultima = arquivosCopias()[0] ?? null;
        $d = $ultima ? lerJsonPrivado('copias/' . basename($ultima)) : null;
        $janela = max(1, (int) (cfg('copias_juntar_min') ?? 10)) * 60;
        if (is_array($d) && ($d['tipo'] ?? '') === $tipo && ($d['usuario'] ?? '') === $usuario
            && (int) ($d['ultimo'] ?? $d['criado'] ?? 0) >= $agora - $janela && is_array($d['produtos'] ?? null)) {
            $original = $d;
            $desfazer = static function () use ($ultima, $original): void { gravarJsonPrivado('copias/' . basename($ultima), $original); };
            $d['ultimo'] = $agora;
            $d['juntou'] = (int) ($d['juntou'] ?? 1) + 1;
            $d['feitos'] = array_slice(array_merge(is_array($d['feitos'] ?? null) ? $d['feitos'] : [], [(string) ($acao['feito'] ?? '')]), -40);
            gravarJsonPrivado('copias/' . basename($ultima), $d);
            return basename($ultima);
        }
    }
    $nome = 'copia-' . date('Ymd-His', $agora) . '-' . sprintf('%03d', min(999, (int) (($micro - $agora) * 1000))) . '-' . bin2hex(random_bytes(2)) . '-' . $tipo . '.php';
    gravarJsonPrivado('copias/' . $nome, [
        'versao' => 2,
        'criado' => $agora,
        'ultimo' => $agora,
        'usuario' => $usuario,
        'tipo' => $tipo,
        'motivo' => (string) ($acao['antes'] ?? ''),
        'feitos' => [(string) ($acao['feito'] ?? '')],
        'juntou' => 1,
        'alvo' => (string) ($acao['alvo'] ?? ''),
        'alvoNome' => (string) ($acao['alvoNome'] ?? ''),
        // Ações em lote: os códigos dos produtos (excluir vários: o "Trazer de volta" das Cópias de segurança)
        'alvos' => array_values(array_filter(is_array($acao['alvos'] ?? null) ? $acao['alvos'] : [], static fn($x) => is_string($x) && preg_match(RE_ID, $x))),
        'categorias' => $catalogo['categorias'],
        'produtos' => $catalogo['produtos'],
    ]);
    $desfazer = static function () use ($nome): void { @unlink(caminhoPrivado('copias/' . $nome)); };
    podarCopias();
    limparLixeira();
    return $nome;
}

/** Data (pelo nome do arquivo) e tipo de uma cópia ($re: RE_COPIA ou RE_COPIA_CLIENTES). */
function infoNomeCopia(string $nome, string $re = RE_COPIA): array
{
    if (!preg_match($re, $nome, $m)) return ['criado' => 0, 'tipo' => ''];
    $t = DateTime::createFromFormat('YmdHis', $m[1] . $m[2]);
    return ['criado' => $t ? $t->getTimestamp() : 0, 'tipo' => $m[3] ?? ''];
}

/**
 * Apaga as cópias velhas: as N mais recentes ficam sempre; além delas, ficam as dos
 * últimos X dias (até o teto); as de excluir/restaurar ficam Y dias mesmo além do teto.
 * Sem argumentos: cópias do catálogo de produtos. Os clientes têm as suas (lib/clientes.php).
 */
function podarCopias(?array $arquivos = null, string $re = RE_COPIA): void
{
    $agora = time();
    $sempre = max(1, (int) cfg('copias_max'));
    $dias = max(0, (int) (cfg('copias_dias') ?? 14)) * 86400;
    $teto = max($sempre, (int) (cfg('copias_teto') ?? 200));
    $protegidas = max(0, (int) (cfg('copias_protegidas_dias') ?? 30)) * 86400;
    foreach ($arquivos ?? arquivosCopias() as $i => $arq) {
        if ($i < $sempre) continue;
        $info = infoNomeCopia(basename($arq), $re);
        $idade = $agora - $info['criado'];
        if ($i < $teto && $idade < $dias) continue;
        if (in_array($info['tipo'], TIPOS_PROTEGIDOS, true) && $idade < $protegidas) continue;
        @unlink($arq);
    }
}

/** Caminhos das cópias, mais novas primeiro. */
function arquivosCopias(): array
{
    $dir = pastaPrivada() . '/copias';
    $lista = glob($dir . '/copia-[0-9]*.php') ?: [];
    $lista = array_filter($lista, static fn($a) => (bool) preg_match(RE_COPIA, basename($a)));
    rsort($lista, SORT_STRING);
    return array_values($lista);
}

/** O que a cópia desfaz, em frases curtas no passado ("Excluiu “X”"). */
function feitosDaCopia(array $d): array
{
    $f = array_values(array_filter(is_array($d['feitos'] ?? null) ? array_map('strval', $d['feitos']) : [], static fn($x) => $x !== ''));
    if (!$f && !empty($d['motivo'])) $f = [frasePassado((string) $d['motivo'])];
    return $f;
}

function listarCopias(): array
{
    $out = [];
    foreach (arquivosCopias() as $a) {
        $d = lerJsonPrivado('copias/' . basename($a));
        if (!is_array($d) || !is_array($d['produtos'] ?? null)) continue;
        $info = infoNomeCopia(basename($a));
        $out[] = [
            'nome' => basename($a),
            'criado' => (int) ($d['criado'] ?? $info['criado']),
            'ultimo' => (int) ($d['ultimo'] ?? $d['criado'] ?? $info['criado']),
            'usuario' => (string) ($d['usuario'] ?? ''),
            'tipo' => (string) ($d['tipo'] ?? $info['tipo']),
            'motivo' => (string) ($d['motivo'] ?? ''),
            'feitos' => feitosDaCopia($d),
            'juntou' => max(1, (int) ($d['juntou'] ?? 1)),
            'alvo' => (string) ($d['alvo'] ?? ''),
            'alvoNome' => (string) ($d['alvoNome'] ?? ''),
            'alvos' => nomesDosAlvos($d),
            'total' => count($d['produtos']),
            'visiveis' => count(array_filter($d['produtos'], static fn($p) => ($p['visivel'] ?? true) !== false)),
        ];
    }
    return $out;
}

/** Produtos de uma ação em lote guardados na cópia ("alvos"): [código => "Marca Nome"], na ordem da cópia. */
function nomesDosAlvos(array $d): array
{
    $alvos = is_array($d['alvos'] ?? null) ? array_fill_keys(array_filter($d['alvos'], 'is_string'), true) : [];
    $out = [];
    if (!$alvos) return $out;
    foreach ($d['produtos'] as $p) {
        $id = is_array($p) ? (string) ($p['id'] ?? '') : '';
        if (isset($alvos[$id])) $out[$id] = nomeCompleto($p);
    }
    return $out;
}

/** Frase no passado a partir do motivo antigo ("Antes de: excluir “X”" → "Excluiu “X”"). */
function frasePassado(string $motivo): string
{
    $s = preg_replace('/^Antes de:\s*/u', '', trim($motivo)) ?? $motivo;
    static $verbos = [
        'cadastrar ' => 'Cadastrou ', 'editar ' => 'Editou ', 'excluir ' => 'Excluiu ',
        'mudar a ordem de ' => 'Mudou a ordem de ', 'mudar o estoque de ' => 'Mudou o estoque de ',
        'pôr ' => 'Pôs ', 'tirar ' => 'Tirou ', 'mostrar ' => 'Mostrou ', 'esconder ' => 'Escondeu ',
        'restaurar ' => 'Restaurou ', 'trazer de volta ' => 'Trouxe de volta ', 'aplicar ' => 'Aplicou ',
    ];
    foreach ($verbos as $de => $para) if (str_starts_with($s, $de)) return $para . substr($s, strlen($de));
    return $s === '' ? 'Alteração no catálogo' : ucfirst($s);
}

function lerCopia(string $nome): array
{
    if (!preg_match(RE_COPIA, $nome)) throw new ErroUsuario('Cópia inválida.');
    $d = lerJsonPrivado('copias/' . $nome);
    if (!is_array($d) || !is_array($d['produtos'] ?? null) || !is_array($d['categorias'] ?? null)) throw new ErroUsuario('Essa cópia não foi encontrada ou está danificada.');
    // Valida como se fosse o arquivo do site
    interpretarCatalogo(montarCatalogo($d['categorias'], $d['produtos']));
    return $d;
}

/* ------------------------------------------------------------------
   Lixeira de fotos (pasta privada lixeira/, base64 dentro de .php)
   ------------------------------------------------------------------ */
function nomeNaLixeira(string $tipo, string $arquivo): string
{
    return 'lixeira/' . $tipo . '--' . $arquivo . '.php';
}

function fotosUsadas(array $produtos): array
{
    $u = ['produtos' => [], 'recortes' => []];
    foreach ($produtos as $p) {
        if (is_string($p['imagem'] ?? null) && preg_match(RE_ARQUIVO_FOTO, $p['imagem'])) $u['produtos'][$p['imagem']] = true;
        if (is_string($p['recorte'] ?? null) && preg_match(RE_ARQUIVO_FOTO, $p['recorte'])) $u['recortes'][$p['recorte']] = true;
    }
    return $u;
}

function moverParaLixeira(string $tipo, string $arquivo): void
{
    $origem = pastaFotos($tipo) . '/' . $arquivo;
    if (!preg_match(RE_ARQUIVO_FOTO, $arquivo) || !@is_file($origem)) return;
    $bytes = @file_get_contents($origem);
    if ($bytes === false) return;
    gravarPrivado(nomeNaLixeira($tipo, $arquivo), base64_encode($bytes));
    @unlink($origem);
    registrar('foto-lixeira', $tipo . '/' . $arquivo);
}

function voltarDaLixeira(string $tipo, string $arquivo): bool
{
    $destino = pastaFotos($tipo) . '/' . $arquivo;
    if (!preg_match(RE_ARQUIVO_FOTO, $arquivo) || @is_file($destino)) return false;
    $c = lerPrivado(nomeNaLixeira($tipo, $arquivo));
    $bytes = $c === null ? false : base64_decode($c, true);
    if ($bytes === false || $bytes === '') return false;
    gravarAtomico($destino, $bytes, 0644);
    @unlink(caminhoPrivado(nomeNaLixeira($tipo, $arquivo)));
    registrar('foto-restaurada', $tipo . '/' . $arquivo);
    return true;
}

/**
 * Foto que deixou de ser usada → lixeira; foto usada que sumiu → volta da lixeira.
 * Recortes antigos do site (não criados pelo painel) nunca saem do lugar: páginas podem
 * usá-los direto.
 */
function sincronizarFotos(array $antes, array $depois): void
{
    $a = fotosUsadas($antes);
    $d = fotosUsadas($depois);
    foreach (array_diff_key($a['produtos'], $d['produtos']) as $arq => $_) moverParaLixeira('produtos', $arq);
    foreach (array_diff_key($a['recortes'], $d['recortes']) as $arq => $_) {
        if (preg_match(RE_FOTO_DO_PAINEL, $arq)) moverParaLixeira('recortes', $arq);
    }
    foreach (['produtos', 'recortes'] as $tipo) {
        foreach ($d[$tipo] as $arq => $_) if (!@is_file(pastaFotos($tipo) . '/' . $arq)) voltarDaLixeira($tipo, $arq);
    }
}

/** Apaga da lixeira o que nenhuma cópia de segurança (nem o catálogo atual) usa mais (no máximo 1 vez por hora). */
function limparLixeira(bool $agora = false): void
{
    $dir = pastaPrivada() . '/lixeira';
    $arqs = glob($dir . '/*--*.php') ?: [];
    if (!$arqs) return;
    // Só vale olhar se houver foto com mais de 1 h na lixeira; e no máximo 1 vez por hora
    $velhas = array_filter($arqs, static fn($a) => (int) @filemtime($a) < time() - 3600);
    if (!$velhas) return;
    $marca = caminhoPrivado('lixeira-conferida.php');
    if (!$agora && @is_file($marca) && (int) @filemtime($marca) > time() - 3600) return;
    @file_put_contents($marca, PREFIXO_PRIVADO);
    $usadas = ['produtos' => [], 'recortes' => []];
    $junta = static function (array $produtos) use (&$usadas): void {
        $u = fotosUsadas($produtos);
        $usadas['produtos'] += $u['produtos'];
        $usadas['recortes'] += $u['recortes'];
    };
    foreach (arquivosCopias() as $c) {
        $d = lerJsonPrivado('copias/' . basename($c));
        if (is_array($d['produtos'] ?? null)) $junta($d['produtos']);
    }
    try { $junta(lerCatalogo()['produtos']); } catch (Throwable $t) { return; }   // sem catálogo legível, não apaga nada
    foreach ($arqs as $a) {
        if (!preg_match('/^(produtos|recortes)--(.+)\.php$/', basename($a), $m)) continue;
        if (!isset($usadas[$m[1]][$m[2]]) && @filemtime($a) < time() - 3600) @unlink($a);
    }
}

/* ------------------------------------------------------------------
   Textos das cópias (tela de cópias e confirmação de restaurar)
   ------------------------------------------------------------------ */
/** Frases (mais novas primeiro) do que a cópia desfaz, com repetições agrupadas: "Mudou a ordem de “X” (32 vezes)". */
function resumoFeitos(array $copias, int $ate): array
{
    $lista = [];
    for ($j = 0; $j <= $ate && isset($copias[$j]); $j++) {
        $f = array_reverse($copias[$j]['feitos']);
        // Cópia que juntou ações além das 40 guardadas: completa com a primeira frase
        $faltam = max(0, (int) $copias[$j]['juntou'] - count($f));
        if ($faltam && $f) for ($k = 0; $k < $faltam; $k++) $f[] = end($f);
        foreach ($f as $x) $lista[] = $x;
    }
    $out = [];
    foreach ($lista as $x) {
        $u = count($out) - 1;
        if ($u >= 0 && $out[$u][0] === $x) $out[$u][1]++;
        else $out[] = [$x, 1];
    }
    return array_map(static fn($o) => $o[0] . ($o[1] > 1 ? ' (' . $o[1] . ' vezes)' : ''), $out);
}

/** Texto da confirmação de restaurar a cópia $i (lista de listarCopias(), mais novas primeiro). */
function textoRestaurar(array $copias, int $i, ?int $totalAtual): string
{
    $c = $copias[$i];
    $n = 0;
    for ($j = 0; $j <= $i; $j++) $n += (int) $copias[$j]['juntou'];
    $frases = resumoFeitos($copias, $i);
    $max = 6;
    $linhas = array_map(static fn($f) => '• ' . $f, array_slice($frases, 0, $max));
    if (count($frases) > $max) $linhas[] = '• e mais ' . (count($frases) - $max) . (count($frases) - $max === 1 ? ' item' : ' itens');
    return 'Voltar o catálogo inteiro para como estava em ' . quando($c['criado']) . '? '
        . ($n === 1 ? 'Isso desfaz 1 alteração:' : 'Isso desfaz ' . $n . ' alterações, inclusive as feitas depois:') . "\n"
        . implode("\n", $linhas) . "\n"
        . 'Na cópia: ' . $c['total'] . ' produtos' . ($totalAtual !== null ? '; hoje: ' . $totalAtual . '.' : '.')
        . ' O estado atual vira uma cópia, então dá para desfazer.';
}

/** Texto da confirmação de excluir (avisa quando é o produto da capa do site). $capa = o produto que o topo do site
 *  mostra de fato (capaEfetiva; null: produtoCapa()); $frase = fraseCapaFora() já pronta, se quem chama tiver. */
function textoExcluir(array $p, ?string $capa = null, ?string $frase = null): string
{
    $t = 'Excluir “' . nomeCompleto($p) . '” do catálogo? A foto vai para a lixeira do painel. Logo depois aparece o botão Desfazer, e a exclusão fica em Cópias de segurança por 30 dias.';
    $capa ??= produtoCapa();
    if ((string) ($p['id'] ?? '') === $capa) {
        $t .= "\nAtenção: este é o produto da capa do site. " . ($frase ?? (function_exists('fraseCapaFora') ? fraseCapaFora(false, $capa) : 'A foto da capa continua lá, mas o link dela vai abrir o catálogo com o aviso “produto fora do catálogo”.'));
    }
    return $t;
}
