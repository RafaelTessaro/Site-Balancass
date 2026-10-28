<?php
/* =====================================================================
   Painel Balanças.com — edição em lote (vários produtos de uma vez)
   Na lista de produtos: "Selecionar" → marcar → Estoque, Destaque, No site,
   Mover para… ou Excluir. Cada ação é UMA gravação do catálogo
   (alterarCatalogo), com UMA cópia de segurança e UMA linha no registro.
   Rotas em index.php (a=lote e a=lote-desfazer); telas em lib/visoes.php
   (lista, barra de ações e a confirmação de excluir sem JavaScript).
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

const LOTE_MAX = 200;   // produtos por ação, no máximo
const LOTE_NOMES = 10;  // nomes listados na confirmação de excluir (o resto vira "e mais N")

/* ------------------------------------------------------------------
   Entrada (tudo conferido aqui; nada vem direto do formulário)
   ------------------------------------------------------------------ */
/** Códigos marcados: lista de 1 a 200 códigos válidos (sem repetição, na ordem em que vieram). */
function idsDoLote($v): array
{
    if ($v === null || $v === '' || $v === []) throw new ErroUsuario('Marque pelo menos um produto (toque em “Selecionar” e depois nos produtos).');
    if (!is_array($v) || !array_is_list($v)) throw new ErroUsuario('Seleção inválida. Recarregue a página e marque de novo.');
    if (count($v) > LOTE_MAX) throw new ErroUsuario('Marque no máximo ' . LOTE_MAX . ' produtos de cada vez (vieram ' . count($v) . ').');
    $ids = [];
    foreach ($v as $id) {
        if (!is_string($id) || strlen($id) > LIMITES['id'] || !preg_match(RE_ID, $id)) throw new ErroUsuario('Seleção inválida. Recarregue a página e marque de novo.');
        $ids[$id] = true;
    }
    return array_keys($ids);
}

/** Ação escolhida: [campo, valor] — estoque/destaque/visivel (valor novo), mover ou excluir. */
function acaoDoLote($v): array
{
    $v = is_string($v) ? $v : '';
    if ($v === 'mover' || $v === 'excluir') return [$v, null];
    if (str_starts_with($v, 'estoque:') && isset(ESTOQUES[substr($v, 8)])) return ['estoque', substr($v, 8)];
    if (in_array($v, ['destaque:1', 'destaque:0', 'visivel:1', 'visivel:0'], true)) return [substr($v, 0, -2), str_ends_with($v, '1')];
    throw new ErroUsuario('Escolha o que fazer com os produtos marcados.');
}

/** Destino de "Mover para…": "<id da categoria>|<nome da subcategoria>" → [categoria, subcategoria]. */
function destinoDoLote($v, array $categorias): array
{
    $v = is_string($v) ? $v : '';
    $p = strpos($v, '|');
    if ($v === '' || $p === false) throw new ErroUsuario('Escolha para onde mover os produtos marcados (categoria e tipo).');
    $c = categoriaPorId($categorias, substr($v, 0, $p));
    $sub = substr($v, $p + 1);
    if (!$c || !in_array($sub, $c['subcategorias'], true)) throw new ErroUsuario('Essa categoria (ou tipo) não existe mais. Recarregue a página e escolha de novo.');
    return [$c, $sub];
}

/** Filtro da lista vindo do formulário (para voltar à mesma lista depois da ação). */
function filtroDoPost(): array
{
    $cat = is_string($_POST['cat'] ?? null) ? $_POST['cat'] : '';
    $ver = is_string($_POST['ver'] ?? null) ? $_POST['ver'] : '';
    return [
        'q'   => cortar(limparLinha($_POST['q'] ?? '') ?? '', 80),
        'cat' => strlen($cat) <= 80 && preg_match(RE_ID, $cat) ? $cat : '',
        'ver' => in_array($ver, ['site', 'ocultos', 'destaques'], true) ? $ver : '',
    ];
}

/** Endereço da lista com o filtro (q, cat, ver) e o que mais vier ($extra: sel, todos). */
function linkLista(array $filtro, array $extra = []): string
{
    $p = array_filter(['q' => (string) ($filtro['q'] ?? ''), 'cat' => (string) ($filtro['cat'] ?? ''), 'ver' => (string) ($filtro['ver'] ?? '')], static fn($x) => $x !== '');
    $q = http_build_query($extra + $p);
    return 'index.php' . ($q !== '' ? '?' . $q : '');
}

/** Os produtos marcados, na ordem do catálogo. Todos precisam existir (senão nada muda). */
function produtosDoLote(array $produtos, array $ids): array
{
    $quer = array_fill_keys($ids, true);
    $achados = [];
    foreach ($produtos as $p) if (isset($quer[(string) ($p['id'] ?? '')])) $achados[] = $p;
    $faltam = count($ids) - count($achados);
    if ($faltam > 0) {
        throw new ErroUsuario(($faltam === 1 ? '1 dos produtos marcados não está' : $faltam . ' dos produtos marcados não estão')
            . ' mais no catálogo (pode ter sido excluído em outro aparelho). Nada foi alterado: recarregue a página e marque de novo.');
    }
    return $achados;
}

/* ------------------------------------------------------------------
   Mudanças (funções puras: recebem a lista de produtos e devolvem outra)
   ------------------------------------------------------------------ */
/** Valor atual de estoque (texto), destaque ou visivel (sim/não) de um produto. */
function valorDoCampo(array $p, string $campo)
{
    return match ($campo) {
        'estoque'  => (string) ($p['estoque'] ?? 'consulte'),
        'destaque' => !empty($p['destaque']),
        'visivel'  => ($p['visivel'] ?? true) !== false,
    };
}

/** Muda estoque, destaque ou visivel dos produtos $ids. Devolve [produtos, códigos que mudaram]. */
function mudarCampoEmLote(array $produtos, array $ids, string $campo, $valor): array
{
    $quer = array_fill_keys($ids, true);
    $mudou = [];
    foreach ($produtos as $i => $p) {
        $id = (string) ($p['id'] ?? '');
        if (!isset($quer[$id])) continue;
        if (valorDoCampo($p, $campo) === $valor) continue;
        $p[$campo] = $valor;
        $produtos[$i] = ordenarCampos($p);
        $mudou[] = $id;
    }
    return [$produtos, $mudou];
}

/**
 * Move os produtos $ids para a categoria/subcategoria. Quem muda de categoria entra em bloco, na ordem
 * em que estava, logo depois dos outros da mesma subcategoria na categoria nova (posicaoNaSubcategoria,
 * lib/ordem.php; categoria ainda vazia: no lugar dela na ordem de $categorias); quem só muda de
 * subcategoria fica no mesmo lugar.
 * Devolve [produtos, códigos que mudaram, códigos que mudaram de categoria].
 * (Serve também para "mover os N produtos para…" ao apagar uma subcategoria.)
 */
function moverProdutosPara(array $produtos, array $ids, string $categoria, string $sub, array $categorias = []): array
{
    $quer = array_fill_keys($ids, true);
    $mudou = []; $trocouCat = []; $vao = [];
    foreach ($produtos as $i => $p) {
        $id = (string) ($p['id'] ?? '');
        if (!isset($quer[$id]) || (($p['categoria'] ?? '') === $categoria && ($p['subcategoria'] ?? '') === $sub)) continue;
        $mudou[] = $id;
        if (($p['categoria'] ?? '') !== $categoria) { $trocouCat[] = $id; $vao[] = $i; }
        $p['categoria'] = $categoria;
        $p['subcategoria'] = $sub;
        $produtos[$i] = ordenarCampos($p);
    }
    if ($vao) {
        $saem = [];
        foreach ($vao as $i) $saem[] = $produtos[$i];
        foreach (array_reverse($vao) as $i) array_splice($produtos, $i, 1);
        array_splice($produtos, posicaoNaSubcategoria($produtos, $categoria, $sub, $categorias), 0, $saem);
    }
    return [array_values($produtos), $mudou, $trocouCat];
}

/** Tira do catálogo os produtos $ids. */
function excluirProdutosEmLote(array $produtos, array $ids): array
{
    $quer = array_fill_keys($ids, true);
    return array_values(array_filter($produtos, static fn($p) => !isset($quer[(string) ($p['id'] ?? '')])));
}

/** Onde um produto excluído volta (posição $i na cópia): logo depois do vizinho de cima da mesma
 *  categoria que ainda existir, ou logo antes do de baixo; senão, no fim da categoria. */
function posicaoDeVolta(array $produtos, array $daCopia, int $i, array $categorias = []): int
{
    $cat = (string) ($daCopia[$i]['categoria'] ?? '');
    for ($j = $i - 1; $j >= 0; $j--) {
        if (($daCopia[$j]['categoria'] ?? '') !== $cat) continue;
        $k = indicePorId($produtos, (string) ($daCopia[$j]['id'] ?? ''));
        if ($k !== null) return $k + 1;
    }
    for ($j = $i + 1, $n = count($daCopia); $j < $n; $j++) {
        if (($daCopia[$j]['categoria'] ?? '') !== $cat) continue;
        $k = indicePorId($produtos, (string) ($daCopia[$j]['id'] ?? ''));
        if ($k !== null) return $k;
    }
    return posicaoNaCategoria($produtos, $cat, $categorias);
}

/**
 * Subcategoria de um produto que volta de uma cópia: a mesma, se ainda existe na categoria $c; se ela
 * só mudou de nome (na cópia, $subsDaCopia, estava na mesma posição e o nome novo não existia), o nome
 * novo; senão, a primeira da categoria. Devolve [subcategoria, mudou?].
 */
function subDeVolta(array $c, array $subsDaCopia, string $sub): array
{
    $subs = array_values(array_map('strval', $c['subcategorias']));
    if (in_array($sub, $subs, true) || !$subs) return [$sub, false];
    $antes = array_values(array_map('strval', $subsDaCopia));
    $k = array_search($sub, $antes, true);
    if ($k !== false && count($antes) === count($subs) && !in_array($subs[$k], $antes, true)) return [$subs[$k], true];
    return [$subs[0], true];
}

/**
 * Devolve ao catálogo os produtos $ids da cópia, cada um no mesmo lugar (na ordem da cópia: quem
 * volta primeiro serve de vizinho para o seguinte). $catsDaCopia = as categorias da cópia (para achar
 * o nome novo de uma subcategoria renomeada). Devolve [produtos, os que voltaram, problemas, trocas]:
 * trocas = [código => frase] dos que voltaram em outra subcategoria (a deles não existe mais).
 */
function devolverProdutos(array $produtos, array $categorias, array $daCopia, array $ids, array $catsDaCopia = []): array
{
    $quer = array_fill_keys($ids, true);
    $voltaram = []; $problemas = []; $trocas = [];
    foreach ($daCopia as $i => $p) {
        $id = (string) ($p['id'] ?? '');
        if (!isset($quer[$id])) continue;
        if (indicePorId($produtos, $id) !== null) { $problemas[] = '“' . nomeCompleto($p) . '” já está no catálogo'; continue; }
        $c = categoriaPorId($categorias, (string) ($p['categoria'] ?? ''));
        if (!$c) { $problemas[] = 'a categoria de “' . nomeCompleto($p) . '” não existe mais'; continue; }
        // A subcategoria foi renomeada ou apagada depois da exclusão: volta na nova (ou na primeira) e avisa
        $sub = (string) ($p['subcategoria'] ?? '');
        $cCopia = categoriaPorId($catsDaCopia, (string) $c['id']);
        [$novaSub, $mudou] = subDeVolta($c, $cCopia ? (array) $cCopia['subcategorias'] : [], $sub);
        if ($mudou) {
            $p['subcategoria'] = $novaSub;
            $trocas[$id] = '“' . nomeCompleto($p) . '” ficou em “' . $c['nome'] . ' › ' . $novaSub . '” (a subcategoria “' . $sub . '” não existe mais)';
        }
        $daCopia[$i] = $p;
        array_splice($produtos, posicaoDeVolta($produtos, $daCopia, $i, $categorias), 0, [$p]);
        $voltaram[] = $p;
    }
    return [$produtos, $voltaram, $problemas, $trocas];
}

/* ------------------------------------------------------------------
   Textos (avisos, registro e confirmação)
   ------------------------------------------------------------------ */
function qtdLote(int $n, string $um, string $varios): string
{
    return $n . ' ' . ($n === 1 ? $um : $varios);
}

/** “A”, “B” e “C” — ou “A”, “B”, “C” e mais 5 (para caber no registro). */
function listaDeNomes(array $nomes, int $maxLetras = 170): string
{
    $out = [];
    $usadas = 0;
    foreach ($nomes as $k => $n) {
        $t = '“' . $n . '”';
        if ($out && $usadas + tamanho($t) > $maxLetras) {
            return implode(', ', $out) . ' e mais ' . (count($nomes) - $k);
        }
        $out[] = $t;
        $usadas += tamanho($t) + 2;
    }
    if (count($out) === 1) return $out[0];
    $ultimo = array_pop($out);
    return implode(', ', $out) . ' e ' . $ultimo;
}

/** "“X”" (um produto) ou "N produtos" — o sujeito das frases do lote. */
function alvoLote(array $prods): string
{
    return count($prods) === 1 ? '“' . nomeCompleto($prods[0]) . '”' : count($prods) . ' produtos';
}

/** [antes (motivo da cópia), feito (registro)] para a ação em $prods (os que vão mudar). */
function frasesLote(string $campo, $valor, array $prods, string $destino = ''): array
{
    $alvo = alvoLote($prods);
    [$antes, $feito] = match (true) {
        $campo === 'estoque'             => ['mudar o estoque de ' . $alvo . ' para ' . ESTOQUES[$valor], 'Mudou o estoque de ' . $alvo . ' para ' . ESTOQUES[$valor]],
        $campo === 'destaque' && $valor  => ['pôr ' . $alvo . ' em destaque', 'Pôs ' . $alvo . ' em destaque'],
        $campo === 'destaque'            => ['tirar ' . $alvo . ' do destaque', 'Tirou ' . $alvo . ' do destaque'],
        $campo === 'visivel' && $valor   => ['mostrar ' . $alvo . ' no site', 'Mostrou ' . $alvo . ' no site'],
        $campo === 'visivel'             => ['esconder ' . $alvo . ' do site', 'Escondeu ' . $alvo . ' do site'],
        $campo === 'mover'               => ['mover ' . $alvo . ' para ' . $destino, 'Moveu ' . $alvo . ' para ' . $destino],
        default                          => ['excluir ' . $alvo, 'Excluiu ' . $alvo],
    };
    // No registro (Atividades), quais foram: "Escondeu 3 produtos do site (“A”, “B” e “C”)"
    if (count($prods) > 1) $feito .= ' (' . listaDeNomes(array_map('nomeCompleto', $prods)) . ')';
    return ['Antes de: ' . $antes, $feito];
}

/** Nada mudou: todos já estavam assim. */
function fraseNadaMudou(string $campo, $valor, int $n, string $destino = ''): string
{
    $estado = match (true) {
        $campo === 'estoque'            => 'como ' . ESTOQUES[$valor],
        $campo === 'destaque' && $valor => 'em destaque',
        $campo === 'destaque'           => 'fora do destaque',
        $campo === 'visivel' && $valor  => 'no site',
        $campo === 'visivel'            => 'fora do site',
        default                         => 'em ' . $destino,
    };
    return 'Nada mudou: ' . ($n === 1 ? 'o produto marcado já estava ' : 'os ' . $n . ' marcados já estavam ') . $estado . '.';
}

/** Situação dos marcados na página inicial depois de pôr em destaque (como fraseHome, para vários). */
function fraseHomeLote(array $marcados, array $produtos): string
{
    if (count($marcados) === 1) return fraseHome($marcados[0], $produtos);
    $capa = capaEfetiva($produtos);
    $home = destaquesDaHome($produtos);
    $nomeCapa = ''; $ocultos = 0; $dentro = 0; $fora = 0;
    foreach ($marcados as $p) {
        $id = (string) $p['id'];
        if ($id === $capa) $nomeCapa = nomeCompleto($p);
        elseif (($p['visivel'] ?? true) === false) $ocultos++;
        elseif (in_array($id, $home, true)) $dentro++;
        else $fora++;
    }
    $t = '';
    if ($fora > 0) {
        $t .= ' ' . ($dentro === 0 ? 'Nenhum deles entrou' : ($dentro === 1 ? '1 aparece' : $dentro . ' aparecem') . ' na página inicial e ' . $fora . ($fora === 1 ? ' ficou' : ' ficaram') . ' de fora')
            . ($dentro === 0 ? ' na página inicial' : '') . ': ela mostra até ' . HOME_MAX_DESTAQUES . ' destaques, revezando as categorias. Para trocar quem aparece, tire a estrela de outros ou ponha estes mais para cima na categoria (Mudar lugar).';
    } elseif ($dentro > 0) {
        $t .= ' ' . ($dentro === 2 ? 'Os dois aparecem' : 'Todos aparecem') . ' na página inicial.';
    }
    if ($ocultos > 0) $t .= ' ' . ($ocultos === 1 ? '1 está fora do site: o destaque só aparece quando ele voltar' : $ocultos . ' estão fora do site: o destaque só aparece quando voltarem') . ' para o site.';
    if ($nomeCapa !== '') $t .= ' “' . $nomeCapa . '” é o produto da capa: já aparece no topo da página inicial.';
    return $t;
}

/** Texto da confirmação de excluir (a janela do JavaScript monta o mesmo texto no navegador). $todos = o catálogo
 *  (para saber qual produto o topo do site mostra de fato; null: lê o catálogo). */
function textoExcluirLote(array $prods, ?array $todos = null): string
{
    $n = count($prods);
    $linhas = array_map(static fn($p) => '• ' . nomeCompleto($p), array_slice($prods, 0, LOTE_NOMES));
    if ($n > LOTE_NOMES) $linhas[] = '• e mais ' . ($n - LOTE_NOMES);
    $t = ($n === 1 ? 'Excluir este produto do catálogo?' : 'Excluir estes ' . $n . ' produtos do catálogo?') . "\n" . implode("\n", $linhas) . "\n"
        . ($n === 1 ? 'A foto vai' : 'As fotos vão') . ' para a lixeira do painel. Logo depois aparece o botão Desfazer'
        . ($n === 1 ? '' : ' (traz ' . ($n === 2 ? 'os dois' : 'os ' . $n) . ' de volta de uma vez)')
        . ', e a exclusão fica em Cópias de segurança por ' . (int) cfg('copias_protegidas_dias') . ' dias.';
    if ($todos === null) {
        try { $todos = lerCatalogo()['produtos']; } catch (Throwable $t2) { $todos = $prods; }
    }
    $capa = capaEfetiva($todos);
    foreach ($prods as $p) {
        if ((string) $p['id'] === $capa) {
            $t .= "\nAtenção: “" . nomeCompleto($p) . '” é o produto da capa do site. ' . fraseCapaFora(false, $capa, $todos);
        }
    }
    return $t;
}

/** O produto que o topo do site mostra (capaEfetiva de $todos, o catálogo de antes) está entre estes? (nome dele, ou '') */
function capaNoLote(array $prods, array $todos): string
{
    $capa = capaEfetiva($todos);
    foreach ($prods as $p) if ((string) $p['id'] === $capa) return nomeCompleto($p);
    return '';
}

/** Onde entrou quem mudou de categoria no Mover (posicaoNaSubcategoria): logo depois dos outros do mesmo tipo
 *  (havia, antes, algum produto nessa categoria e subcategoria, inclusive um marcado que já estava lá) ou, sem outros, onde fica esse tipo
 *  na lista. $quem: 'um', 'varios' ou 'misto' (só parte dos marcados mudou de categoria). O nome da
 *  subcategoria vem sempre depois de "produtos do tipo"/"o tipo" (não depende do gênero nem do número dele). */
function fraseOndeEntrou(array $antes, array $marcados, array $categoria, string $sub, string $quem): string
{
    $quer = array_fill_keys($marcados, true);
    foreach ($antes as $p) {
        if (!isset($quer[(string) ($p['id'] ?? '')]) && ($p['categoria'] ?? '') === $categoria['id'] && ($p['subcategoria'] ?? '') === $sub) {
            return ' ' . ['um' => 'Entrou', 'varios' => 'Entraram', 'misto' => 'Quem mudou de categoria entrou'][$quem] . ' logo depois dos outros produtos do tipo “' . $sub . '”.';
        }
    }
    return ' ' . ['um' => 'Ele é o primeiro produto do tipo “' . $sub . '” e entrou onde fica esse tipo na lista.',
        'varios' => 'Eles são os primeiros produtos do tipo “' . $sub . '” e entraram onde fica esse tipo na lista.',
        'misto' => 'Quem mudou de categoria entrou onde fica o tipo “' . $sub . '” na lista.'][$quem];
}

/** "Balanças › Balanças computadoras" */
function nomeDestino(array $categoria, string $sub): string
{
    return (string) $categoria['nome'] . ' › ' . $sub;
}

/* ------------------------------------------------------------------
   Ações (rotas a=lote e a=lote-desfazer; o roteador já conferiu
   login, CSRF e que é POST — editor também pode, como nas ações de um produto só)
   ------------------------------------------------------------------ */
function acaoLote(): void
{
    $cat = catalogoOuTela();
    $filtro = filtroDoPost();
    $ids = idsDoLote($_POST['ids'] ?? null);
    $marcados = produtosDoLote($cat['produtos'], $ids);
    // Se algo der errado daqui em diante, a lista volta com os mesmos marcados
    if (!pedidoJson()) $_SESSION['loteMarcados'] = $ids;
    [$campo, $valor] = acaoDoLote($_POST['acao'] ?? null);
    $categoriaDestino = null; $sub = ''; $destino = '';
    if ($campo === 'mover') {
        [$categoriaDestino, $sub] = destinoDoLote($_POST['destino'] ?? null, $cat['categorias']);
        $destino = nomeDestino($categoriaDestino, $sub);
    }
    if ($campo === 'excluir' && ($_POST['confirmado'] ?? '') !== '1') {
        if (pedidoJson()) throw new ErroUsuario('Confirme a exclusão.');
        telaConfirmarLote($marcados, $filtro);
        return;
    }
    $n = count($marcados);

    // O que vai mudar de fato (quem já está assim fica de fora da frase e da cópia)
    $mudam = match ($campo) {
        'excluir' => $marcados,
        'mover'   => array_values(array_filter($marcados, static fn($p) => ($p['categoria'] ?? '') !== $categoriaDestino['id'] || ($p['subcategoria'] ?? '') !== $sub)),
        default   => array_values(array_filter($marcados, static fn($p): bool => valorDoCampo($p, $campo) !== $valor)),
    };

    $copia = null;
    $trocouCat = [];
    $produtos = $cat['produtos'];
    if ($mudam) {
        [$antes, $feito] = frasesLote($campo, $valor, $mudam, $destino);
        $um = count($mudam) === 1;
        // Tipo da cópia: "excluir" (guardada mais tempo, com Desfazer); "lote" nunca se junta com as
        // cópias das ações de um produto só (cada ação em lote tem a sua cópia)
        $idsMudam = array_map(static fn($p) => (string) $p['id'], $mudam);
        $acaoCat = ['tipo' => $campo === 'excluir' ? 'excluir' : 'lote', 'antes' => $antes, 'feito' => $feito,
            'alvo' => $um ? (string) $mudam[0]['id'] : '', 'alvoNome' => $um ? nomeCompleto($mudam[0]) : count($mudam) . ' produtos', 'alvos' => $idsMudam];
        $r = alterarCatalogo($acaoCat, static function (array $c) use ($campo, $valor, $idsMudam, $categoriaDestino, $sub, &$trocouCat): array {
            produtosDoLote($c['produtos'], $idsMudam);   // ainda existem todos? (outro aparelho pode ter excluído)
            if ($campo === 'excluir') return ['produtos' => excluirProdutosEmLote($c['produtos'], $idsMudam)];
            if ($campo === 'mover') {
                if (!categoriaPorId($c['categorias'], (string) $categoriaDestino['id'])) throw new ErroUsuario('Essa categoria não existe mais. Recarregue a página.');
                [$novos, , $trocouCat] = moverProdutosPara($c['produtos'], $idsMudam, (string) $categoriaDestino['id'], $sub, $c['categorias']);
                return ['produtos' => $novos];
            }
            return ['produtos' => mudarCampoEmLote($c['produtos'], $idsMudam, $campo, $valor)[0]];
        });
        $produtos = $r['produtos'];
        $copia = $r['copia'];
    }

    // Aviso em palavras simples (com os alertas das ações de um produto só)
    $k = count($mudam);
    $capaAntes = capaEfetiva($cat['produtos']);   // o produto que o topo do site mostrava antes desta ação
    $nomeCapa = capaNoLote($mudam, $cat['produtos']);
    $alvo = $k ? alvoLote($mudam) : '';
    $resto = $n - $k;
    $jaEstavam = $k && $resto ? ($resto === 1 ? ' O outro marcado já estava assim.' : ' Os outros ' . $resto . ' marcados já estavam assim.') : '';
    if (!$k) {
        $msg = fraseNadaMudou($campo, $valor, $n, $destino);
    } else {
        $varios = $k > 1;
        $msg = match (true) {
            $campo === 'estoque'            => 'Estoque mudado para ' . ESTOQUES[$valor] . ' em ' . $alvo . '.',
            $campo === 'destaque' && $valor => $alvo . ($varios ? ' agora estão' : ' agora está') . ' em destaque.',
            $campo === 'destaque'           => $alvo . ($varios ? ' saíram' : ' saiu') . ' do destaque. A página inicial agora mostra ' . qtdLote(count(destaquesDaHome($produtos)), 'destaque', 'destaques') . '.',
            $campo === 'visivel' && $valor  => $alvo . ($varios ? ' voltaram' : ' voltou') . ' para o site.',
            $campo === 'visivel'            => $alvo . ($varios ? ' foram escondidos' : ' foi escondido') . ' do site (' . ($varios ? 'continuam' : 'continua') . ' no painel, com o selo “Fora do site”).',
            $campo === 'mover'              => $alvo . ($varios ? ' foram' : ' foi') . ' para ' . $destino . '.'
                . ($trocouCat ? fraseOndeEntrou($cat['produtos'], $idsMudam, $categoriaDestino, $sub, count($trocouCat) < $k ? 'misto' : ($varios ? 'varios' : 'um')) : ''),
            default                         => $alvo . ($varios ? ' foram excluídos.' : ' foi excluído.'),
        } . $jaEstavam;
        $msg = ucfirst($msg);
        if ($campo === 'destaque' && $valor) {
            $depois = array_values(array_filter($produtos, static fn($p) => in_array((string) $p['id'], $ids, true)));
            $msg .= fraseHomeLote($depois, $produtos);
        }
        if ($nomeCapa !== '' && $campo === 'visivel' && !$valor) {
            $msg .= ' Atenção: “' . $nomeCapa . '” é o produto da capa. ' . fraseCapaFora(true, $capaAntes, $produtos);
        }
        if ($nomeCapa !== '' && $campo === 'excluir') {
            $msg .= ' Atenção: “' . $nomeCapa . '” era o produto da capa. ' . fraseCapaFora(true, $capaAntes, $produtos);
        }
        if ($nomeCapa !== '' && $campo === 'mover') {
            $msg .= ' “' . $nomeCapa . '” é o produto da capa: a capa do site continua igual.';
        }
    }

    if ($campo === 'excluir') {
        unset($_SESSION['loteMarcados']);
        guardarDesfazer((string) $copia);
        $d = is_array($_SESSION['desfazerLote'] ?? null) ? $_SESSION['desfazerLote'] : [];
        $d[(string) $copia] = ['ids' => array_map(static fn($p) => (string) $p['id'], $mudam), 't' => time()];
        $_SESSION['desfazerLote'] = array_slice($d, -20, null, true);
        avisar($msg, 'ok', ['form' => ['acao' => link_('lote-desfazer'), 'campos' => ['copia' => (string) $copia], 'botao' => 'Desfazer']]);
        $ir = linkLista($filtro, ['sel' => '1']);
        if (pedidoJson()) responderJson(['ok' => true, 'msg' => $msg, 'ir' => $ir]);
        redirecionar($ir);
    }
    // Estoque, destaque e no site: "Desfazer" no aviso (volta só esse campo desses produtos, uma vez, para quem fez)
    $desfazer = null;
    if ($copia !== null && $campo !== 'mover') {
        $d = is_array($_SESSION['desfazerLote'] ?? null) ? $_SESSION['desfazerLote'] : [];
        $d[(string) $copia] = ['ids' => array_map(static fn($p) => (string) $p['id'], $mudam), 'campo' => $campo, 'valor' => $valor, 't' => time()];
        $_SESSION['desfazerLote'] = array_slice($d, -20, null, true);
        $desfazer = ['acao' => link_('lote-desfazer'), 'campos' => ['copia' => (string) $copia], 'botao' => 'Desfazer'];
    }
    if (pedidoJson()) {
        if ($campo === 'mover') {
            avisar($msg);
            responderJson(['ok' => true, 'msg' => $msg, 'ir' => linkLista($filtro, ['sel' => '1']) . '#p-' . $ids[0]]);
        }
        responderJson(['ok' => true, 'msg' => $msg, 'campo' => $campo, 'valor' => is_bool($valor) ? ($valor ? '1' : '0') : $valor,
            'ids' => $ids, 'mudaram' => count($mudam), 'home' => destaquesDaHome($produtos)] + ($desfazer ? ['desfazer' => ['acao' => $desfazer['acao'], 'copia' => (string) $copia]] : []));
    }
    avisar($msg, 'ok', $desfazer ? ['form' => $desfazer] : []);
    // Com JavaScript (mover), a lista abre no primeiro marcado; sem, no alto (onde fica o aviso)
    redirecionar(linkLista($filtro, ['sel' => '1']) . (($_POST['js'] ?? '') === '1' ? '#p-' . $ids[0] : ''));
}

/**
 * Desfazer de estoque, destaque ou no site em lote: cada produto volta ao valor que tinha antes (o da cópia
 * de segurança daquela ação). Vale uma vez, nesta sessão, para quem fez. Quem mudou de novo depois (outro
 * valor que não o do lote) fica como está.
 */
function desfazerCampoEmLote(string $nomeCopia, array $sessao): void
{
    $copia = lerCopia($nomeCopia);
    $campo = (string) $sessao['campo'];
    $valorLote = $sessao['valor'] ?? null;
    if (($copia['tipo'] ?? '') !== 'lote' || strcasecmp((string) ($copia['usuario'] ?? ''), (string) usuarioLogado()) !== 0 || ($campo === 'estoque' ? !isset(ESTOQUES[(string) $valorLote]) : !is_bool($valorLote))) {
        throw new ErroUsuario('Esse Desfazer não vale mais. Mude os produtos de novo pela lista (Selecionar).');
    }
    $ids = array_fill_keys(array_filter(is_array($sessao['ids'] ?? null) ? $sessao['ids'] : [], static fn($id) => is_string($id) && preg_match(RE_ID, $id)), true);
    $antes = [];
    foreach ($copia['produtos'] as $p) if (isset($ids[(string) ($p['id'] ?? '')])) $antes[(string) $p['id']] = valorDoCampo($p, $campo);
    $voltaram = []; $ficaram = 0;
    $feito = 'Desfez a mudança em lote: ' . lcfirst((string) ($copia['feitos'][0] ?? 'estoque, destaque ou no site'));
    alterarCatalogo(['tipo' => 'lote', 'antes' => 'Antes de: desfazer a mudança em lote (' . lcfirst((string) preg_replace('/^Antes de:\s*/u', '', (string) ($copia['motivo'] ?? ''))) . ')', 'feito' => cortar($feito, 400),
        'alvo' => count($antes) === 1 ? (string) array_key_first($antes) : '', 'alvoNome' => count($antes) . ' produtos'],
        static function (array $c) use ($campo, $valorLote, $antes, &$voltaram, &$ficaram): array {
            $prods = $c['produtos'];
            foreach ($prods as $i => $p) {
                $id = (string) ($p['id'] ?? '');
                if (!array_key_exists($id, $antes)) continue;
                if (valorDoCampo($p, $campo) !== $valorLote) { $ficaram++; continue; }   // mudou de novo depois: fica
                $p[$campo] = $antes[$id];
                $prods[$i] = ordenarCampos($p);
                $voltaram[] = $p;
            }
            if (!$voltaram) throw new ErroUsuario('Nada para desfazer: ' . ($ficaram ? 'esses produtos já foram mudados de novo depois.' : 'eles não estão mais no catálogo.'));
            return ['produtos' => $prods];
        });
    unset($_SESSION['desfazerLote'][$nomeCopia]);
    $k = count($voltaram);
    $alvo = $k === 1 ? '“' . nomeCompleto($voltaram[0]) . '”' : $k . ' produtos';
    $msg = 'Desfeito: ' . match ($campo) {
        'estoque'  => ($k === 1 ? $alvo . ' voltou' : $alvo . ' voltaram') . ' a ter o estoque de antes.',
        'destaque' => ($k === 1 ? $alvo . ' voltou' : $alvo . ' voltaram') . ' a ficar ' . ($valorLote ? 'fora do destaque' : 'em destaque') . ', como antes.',
        default    => ($k === 1 ? $alvo . ' voltou' : $alvo . ' voltaram') . ' a ficar ' . ($valorLote ? 'fora do site' : 'no site') . ', como antes.',
    } . ($ficaram ? ' ' . ($ficaram === 1 ? '1 foi mudado' : $ficaram . ' foram mudados') . ' de novo depois e ' . ($ficaram === 1 ? 'ficou como está.' : 'ficaram como estão.') : '');
    avisar($msg);
    redirecionar('index.php#p-' . $voltaram[0]['id']);
}

/** Desfazer de uma exclusão em lote: devolve todos de uma vez, cada um no mesmo lugar e com a foto.
 *  Mesma regra do Desfazer de um produto só: vale uma vez, nesta sessão, para quem excluiu.
 *  Administrador, em Cópias de segurança: "Trazer de volta os N" de qualquer exclusão em lote (sem a
 *  regra da sessão), com os produtos que essa exclusão tirou (guardados na cópia, em "alvos"). */
function acaoLoteDesfazer(): void
{
    $nomeCopia = is_string($_POST['copia'] ?? null) ? $_POST['copia'] : '';
    $sessao = $_SESSION['desfazerLote'][$nomeCopia] ?? null;
    if (is_array($sessao) && in_array($sessao['campo'] ?? '', ['estoque', 'destaque', 'visivel'], true)) {
        desfazerCampoEmLote($nomeCopia, $sessao);
        return;
    }
    $copia = lerCopia($nomeCopia);
    exigirCopiaPropria($copia, $nomeCopia, (string) ($copia['alvo'] ?? ''), (string) ($copia['alvoNome'] ?? ''));
    $ids = $_SESSION['desfazerLote'][$nomeCopia]['ids'] ?? null;
    if ((!is_array($ids) || !$ids) && ehAdmin() && ($copia['tipo'] ?? '') === 'excluir' && is_array($copia['alvos'] ?? null)) {
        $atuais = lerCatalogo()['produtos'];
        $ids = array_values(array_filter($copia['alvos'], static fn($id) => is_string($id) && indicePorId($atuais, $id) === null));
        if (!$ids) throw new ErroUsuario('Os produtos dessa exclusão já estão todos no catálogo.');
    }
    if (!is_array($ids) || !$ids || ($copia['tipo'] ?? '') !== 'excluir') {
        throw new ErroUsuario('Esse Desfazer já foi usado ou expirou. Para trazer os produtos de volta, '
            . (ehAdmin() ? 'use Conta → Cópias de segurança.' : 'fale com um administrador (Conta → Cópias de segurança).'));
    }
    $ids = array_values(array_filter($ids, static fn($id) => is_string($id) && preg_match(RE_ID, $id)));
    $voltaram = []; $problemas = []; $trocas = [];
    $n = count($ids);
    $nomes = [];
    foreach ($copia['produtos'] as $p) if (in_array((string) ($p['id'] ?? ''), $ids, true)) $nomes[] = $p;
    $alvo = $nomes ? alvoLote($nomes) : $n . ' produtos';
    alterarCatalogo(['tipo' => 'desfazer', 'antes' => 'Antes de: trazer de volta ' . $alvo, 'feito' => 'Trouxe de volta ' . $alvo . (count($nomes) > 1 ? ' (' . listaDeNomes(array_map('nomeCompleto', $nomes)) . ')' : ''),
        'alvo' => $n === 1 ? $ids[0] : '', 'alvoNome' => $n === 1 && $nomes ? nomeCompleto($nomes[0]) : $n . ' produtos'],
        static function (array $c) use ($copia, $ids, $alvo, $nomes, &$voltaram, &$problemas, &$trocas): array {
            [$prods, $voltaram, $problemas, $trocas] = devolverProdutos($c['produtos'], $c['categorias'], $copia['produtos'], $ids, (array) $copia['categorias']);
            if (!$voltaram) throw new ErroUsuario('Nenhum produto voltou: ' . implode('; ', $problemas) . '.');
            $r = ['produtos' => $prods];
            // No registro, quem voltou em outra subcategoria
            if ($trocas) $r['feito'] = 'Trouxe de volta ' . $alvo . (count($nomes) > 1 ? ' (' . listaDeNomes(array_map('nomeCompleto', $nomes)) . ')' : '') . '; ' . implode('; ', $trocas);
            return $r;
        });
    unset($_SESSION['desfazer'][$nomeCopia], $_SESSION['desfazerLote'][$nomeCopia]);
    $semFoto = count(array_filter($voltaram, static fn($p) => is_string($p['imagem'] ?? null) && $p['imagem'] !== '' && !@is_file(pastaFotos() . '/' . $p['imagem'])));
    $k = count($voltaram);
    $msg = ($k === 1 ? alvoLote($voltaram) . ' voltou' : $k . ' produtos voltaram') . ' para o catálogo, ' . ($k === 1 ? 'no mesmo lugar' : 'cada um no mesmo lugar')
        . ($semFoto ? '. ' . ($semFoto === 1 ? 'Uma foto não estava' : $semFoto . ' fotos não estavam') . ' mais na lixeira: escolha outra em Editar.' : ($k === 1 ? ' e com a foto.' : ' e com as fotos.'))
        . ($trocas ? ' Atenção: ' . implode('; ', $trocas) . '. Confira ' . (count($trocas) === 1 ? 'o tipo dele' : 'o tipo deles') . ' em Editar.' : '')
        . ($problemas ? ' Não voltaram: ' . implode('; ', $problemas) . '.' : '');
    $primeiroTrocado = array_key_first($trocas);
    avisar($msg, $semFoto || $problemas || $trocas ? 'erro' : 'ok', $primeiroTrocado !== null ? ['link' => link_('editar', ['id' => (string) $primeiroTrocado]), 'textoLink' => 'Editar ' . (count($trocas) === 1 ? 'o produto' : 'o primeiro')] : []);
    redirecionar('index.php#p-' . ($primeiroTrocado ?? $voltaram[0]['id']));
}
