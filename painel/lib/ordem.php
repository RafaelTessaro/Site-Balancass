<?php
/* =====================================================================
   Painel Balanças.com — ordem de Clientes e Produtos ("Mudar lugar")
   Funções puras: recebem a lista e devolvem outra (ou um texto).
   O lugar escolhido é sempre "depois de X" (ou "no começo"): X é o
   vizinho de cima, identificado de forma estável (produto: código;
   cliente: posição + logo). A janela do painel.js usa as MESMAS regras
   para montar a lista de lugares (opcoesLugar = opcoes no JavaScript).
   Rotas em index.php (a=lugar e a=cliente-lugar); telas em lib/visoes.php.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

final class SemMudanca extends RuntimeException {}   // lançada dentro de alterarClientes/alterarCatalogo: nada a gravar

// Mensagens (as mesmas para clientes e produtos, com e sem JavaScript)
const ERRO_LISTA_MUDOU = 'A lista mudou enquanto você escolhia (talvez em outro aparelho). Confira e escolha o lugar de novo.';
const ERRO_LUGAR_INVALIDO = 'Lugar inválido. Recarregue a página e escolha de novo.';
const ERRO_DESFAZER_LUGAR = 'Não deu para desfazer: o vizinho de antes não está mais na lista. Use Mudar lugar para escolher o lugar.';
const ERRO_SEM_CATEGORIA = 'Esse produto está sem categoria válida: escolha a categoria em Editar antes de mudar o lugar.';
// Como o site usa a ordem dos produtos (compartilhado/js/balancas.js: estoque → destaque → ordem da lista)
const TEXTO_ORDEM_SITE = 'No site, Pronta entrega vem primeiro, depois os destaques; esta ordem decide entre os que estão na mesma situação. Esgotado fica por último.';

/* ------------------------------------------------------------------
   Lista de lugares (a mesma na janela, na página sem JavaScript e no
   campo "Lugar no site" do cliente novo)
   ------------------------------------------------------------------ */
/** Nome curto para a lista de lugares: mais de $max letras vira $max − 1 + "…". */
function rotuloCurto(string $nome, int $max = 45): string
{
    return tamanho($nome) > $max ? cortar($nome, $max - 1) . '…' : $nome;
}

/**
 * Lugares possíveis para o item $atual (índice em $itens; null = item novo). Tira o item da lista:
 * o lugar k+1 é "depois do k-ésimo dos que sobraram" (k = 0: no começo). O número é o lugar FINAL.
 * $itens: [['nome' => …, 'valor' => …, 'sub' => …], …]; $cat: nome da categoria (produtos) ou ''.
 * Devolve [['pos', 'valor' ('inicio' ou o valor do vizinho de cima), 'texto', 'sub', 'atual'], …].
 */
function opcoesLugar(array $itens, ?int $atual, string $cat = ''): array
{
    $resto = array_values($atual === null ? $itens : array_filter($itens, static fn($k) => $k !== $atual, ARRAY_FILTER_USE_KEY));
    $n = count($resto) + 1;
    $de = $cat !== '' ? ' de ' . $cat : '';
    $out = [];
    for ($k = 0; $k < $n; $k++) {
        $eAtual = $atual !== null && $k === $atual;
        $sub = '';
        if ($eAtual) $texto = 'fica onde está';
        elseif ($k === 0) $texto = 'no começo' . $de . ($resto ? ', antes de ' . $resto[0]['nome'] : '');
        elseif ($k === $n - 1) $texto = 'no fim' . $de . ', depois de ' . $resto[$k - 1]['nome'];
        else {
            $texto = 'depois de ' . $resto[$k - 1]['nome'];
            if ($resto[$k]['sub'] !== '' && $resto[$k]['sub'] !== $resto[$k - 1]['sub']) $sub = $resto[$k]['sub'];
        }
        $out[] = ['pos' => $k + 1, 'valor' => $k === 0 ? 'inicio' : (string) $resto[$k - 1]['valor'], 'texto' => $texto, 'sub' => $sub, 'atual' => $eAtual];
    }
    return $out;
}

/** Clientes para a lista de lugares: valor "n:logo" (n = posição na lista COM o item). */
function itensLugarClientes(array $lista): array
{
    $out = [];
    foreach (array_values($lista) as $i => $c) {
        $logo = (string) ($c['logo'] ?? '');
        $out[] = ['nome' => rotuloCurto((string) ($c['nome'] ?? '')) . ($logo === '' ? ' (sem logo)' : ''), 'valor' => ($i + 1) . ':' . $logo, 'sub' => ''];
    }
    return $out;
}

/** Produtos da categoria $cat, na ordem da lista: valor = código, sub = subcategoria. */
function itensLugarProdutos(array $produtos, string $cat): array
{
    $out = [];
    foreach ($produtos as $p) {
        if ((string) ($p['categoria'] ?? '') !== $cat) continue;
        $out[] = ['nome' => rotuloCurto(nomeCompleto($p)) . (($p['visivel'] ?? true) === false ? ' (fora do site)' : ''),
            'valor' => (string) ($p['id'] ?? ''), 'sub' => (string) ($p['subcategoria'] ?? '')];
    }
    return $out;
}

/* ------------------------------------------------------------------
   O que veio do formulário (conferido aqui; nada vem direto)
   ------------------------------------------------------------------ */
/** Lugar de um cliente: 'inicio' → ['inicio' => true]; "n:logo" → ['i' => n − 1, 'logo' => …]; inválido → null. */
function refLugarCliente($v): ?array
{
    if (!is_string($v)) return null;
    if ($v === 'inicio') return ['inicio' => true];
    if (strlen($v) > 140 || !preg_match('/^([1-9]\d{0,3}):([^\x00-\x1F\x7F]*)\z/', $v, $m)) return null;
    if ($m[2] !== '' && !preg_match(RE_ARQUIVO_LOGO, $m[2])) return null;   // nada de pastas ("../")
    return ['i' => (int) $m[1] - 1, 'logo' => $m[2]];
}

/** Lugar de um produto: 'inicio', o código do vizinho de cima, ou null (inválido). */
function paraProduto($v): ?string
{
    if (!is_string($v)) return null;
    if ($v === 'inicio') return $v;
    return strlen($v) <= LIMITES['id'] && preg_match(RE_ID, $v) && !str_contains($v, "\n") ? $v : null;
}

/* ------------------------------------------------------------------
   Clientes (a ordem da lista é a ordem no site)
   ------------------------------------------------------------------ */
/** Leva o cliente $j para logo depois do $k (null = no começo). Devolve [lista nova, posição final 1…]. */
function moverClientePara(array $l, int $j, ?int $k): array
{
    $l = array_values($l);
    $item = $l[$j];
    array_splice($l, $j, 1);
    if ($k === null) {
        array_unshift($l, $item);
        return [$l, 1];
    }
    $k2 = $k > $j ? $k - 1 : $k;
    array_splice($l, $k2 + 1, 0, [$item]);
    return [$l, $k2 + 2];
}

/** "para" do Desfazer: o vizinho de cima de antes ('inicio' se era o 1º), com a posição dele DEPOIS da mudança
 *  ($jNovo = índice final do cliente). A posição é necessária para o vizinho sem logo. */
function paraDesfazerCliente(array $antes, int $j, int $jNovo): string
{
    if ($j === 0) return 'inicio';
    $a = $j - 1;
    $aNovo = $jNovo <= $a ? $a + 1 : $a;
    return ($aNovo + 1) . ':' . (string) ($antes[$a]['logo'] ?? '');
}

/* ------------------------------------------------------------------
   Produtos (a ordem dentro da categoria; o site ainda põe Pronta
   entrega e destaques na frente — fraseOrdemSite explica)
   ------------------------------------------------------------------ */
function idsDaCategoria(array $produtos, string $cat): array
{
    $ids = [];
    foreach ($produtos as $p) if ((string) ($p['categoria'] ?? '') === $cat) $ids[] = (string) ($p['id'] ?? '');
    return $ids;
}

/** [posição 1… do produto na categoria dele, total da categoria] (posição 0: não achou). */
function posNaCategoria(array $produtos, string $id): array
{
    $i = indicePorId($produtos, $id);
    if ($i === null) return [0, 0];
    $ids = idsDaCategoria($produtos, (string) ($produtos[$i]['categoria'] ?? ''));
    return [(int) array_search($id, $ids, true) + 1, count($ids)];
}

/** Índice para "Em 1º na categoria": o do primeiro produto dela (categoria vazia: a regra de posicaoNaCategoria). */
function inicioDaCategoria(array $produtos, string $cat, array $categorias): int
{
    foreach (array_values($produtos) as $i => $p) if ((string) ($p['categoria'] ?? '') === $cat) return $i;
    return posicaoNaCategoria($produtos, $cat, $categorias);
}

/**
 * Índice para um produto que chega na categoria (novo, trocou de categoria, Mover em lote): logo depois
 * do último da mesma subcategoria; subcategoria ainda sem produtos: depois do último da categoria cuja
 * subcategoria vem antes (na ordem das subcategorias), senão antes do primeiro cuja subcategoria vem
 * depois; categoria vazia ou subcategoria desconhecida: posicaoNaCategoria (fim da categoria).
 */
function posicaoNaSubcategoria(array $produtos, string $cat, string $sub, array $categorias): int
{
    $produtos = array_values($produtos);
    $ultimo = null;
    foreach ($produtos as $i => $p) {
        if ((string) ($p['categoria'] ?? '') === $cat && (string) ($p['subcategoria'] ?? '') === $sub) $ultimo = $i;
    }
    if ($ultimo !== null) return $ultimo + 1;
    $c = categoriaPorId($categorias, $cat);
    $subs = $c ? array_values(array_map('strval', (array) ($c['subcategorias'] ?? []))) : [];
    $k = array_search($sub, $subs, true);
    if ($k !== false) {
        $ordem = array_flip($subs);
        $depoisDe = null; $antesDe = null;
        foreach ($produtos as $i => $p) {
            if ((string) ($p['categoria'] ?? '') !== $cat) continue;
            $o = $ordem[(string) ($p['subcategoria'] ?? '')] ?? null;
            if ($o === null) continue;
            if ($o < $k) $depoisDe = $i;
            elseif ($o > $k && $antesDe === null) $antesDe = $i;
        }
        if ($depoisDe !== null) return $depoisDe + 1;
        if ($antesDe !== null) return $antesDe;
    }
    return posicaoNaCategoria($produtos, $cat, $categorias);
}

/**
 * Leva o produto $id para logo depois de $depoisDe (código de outro produto da MESMA categoria; null = no
 * começo da categoria). Só os lugares da categoria dele mudam: os produtos de outras categorias ficam com
 * o mesmo índice (mesmo numa lista editada à mão, com a categoria espalhada).
 * Devolve [produtos, posição de antes, posição nova, total da categoria, "para" do Desfazer].
 */
function moverProdutoPara(array $produtos, string $id, ?string $depoisDe): array
{
    $produtos = array_values($produtos);
    $i = indicePorId($produtos, $id);
    if ($i === null) throw new ErroUsuario('Esse produto não está mais no catálogo (talvez tenha sido excluído em outro aparelho).');
    if ($depoisDe === $id) throw new ErroUsuario(ERRO_LUGAR_INVALIDO);
    $cat = (string) ($produtos[$i]['categoria'] ?? '');
    $lugares = []; $ids = []; $porId = [];
    foreach ($produtos as $j => $p) {
        if ((string) ($p['categoria'] ?? '') !== $cat) continue;
        $lugares[] = $j;
        $ids[] = (string) $p['id'];
        $porId[(string) $p['id']] = $p;
    }
    $de = (int) array_search($id, $ids, true) + 1;
    $total = count($ids);
    $paraVolta = $de === 1 ? 'inicio' : $ids[$de - 2];
    $ordem = array_values(array_filter($ids, static fn($x) => $x !== $id));
    if ($depoisDe === null) {
        array_unshift($ordem, $id);
        $pos = 1;
    } else {
        $k = array_search($depoisDe, $ordem, true);
        if ($k === false) throw new ErroUsuario(ERRO_LISTA_MUDOU);
        array_splice($ordem, $k + 1, 0, [$id]);
        $pos = $k + 2;
    }
    foreach ($lugares as $n => $j) $produtos[$j] = $porId[$ordem[$n]];
    return [$produtos, $de, $pos, $total, $paraVolta];
}

/** O que o site faz com a ordem (Pronta entrega e destaques na frente, Esgotado por último), para o aviso:
 *  outros passam na frente dele, ou ele passa na frente de quem está antes na lista. */
function fraseOrdemSite(array $produtos, string $id, string $nomeCat): string
{
    $produtos = array_values($produtos);
    $i = indicePorId($produtos, $id);
    if ($i === null) return '';
    $p = $produtos[$i];
    if (($p['visivel'] ?? true) === false) return ' Ele está fora do site: o lugar vale quando ele voltar.';
    $chave = static fn(array $x, int $j): array => [ORDEM_ESTOQUE_SITE[(string) ($x['estoque'] ?? '')] ?? 1, empty($x['destaque']) ? 1 : 0, $j];
    $minha = $chave($p, $i);
    $s = 1; $k = 0; $u = 0;   // k: passam na frente dele; u: estão antes dele na lista, mas ele passa na frente
    foreach ($produtos as $j => $x) {
        if ($j === $i || (string) ($x['categoria'] ?? '') !== (string) ($p['categoria'] ?? '') || ($x['visivel'] ?? true) === false) continue;
        if ($chave($x, $j) < $minha) {
            $s++;
            if ($j > $i) $k++;
        } elseif ($j < $i) $u++;
    }
    if ($k === 0) return $u ? ' No site, ele aparece mais acima, em ' . $s . 'º em ' . $nomeCat . ': lá, Pronta entrega vem primeiro, depois os destaques, e Esgotado fica por último.' : '';
    if ((string) ($p['estoque'] ?? '') === 'esgotado') return ' Como está Esgotado, no site ele aparece depois dos outros de ' . $nomeCat . ' que não estão esgotados.';
    return ' No site, ele aparece em ' . $s . 'º em ' . $nomeCat . ': '
        . ($k === 1 ? '1 produto com Pronta entrega ou em destaque vem antes.' : $k . ' produtos com Pronta entrega ou em destaque vêm antes.');
}

/** "“A”", "“A” e “B”", "“A”, “B” e “C”". */
function listaComE(array $nomes): string
{
    $nomes = array_map(static fn($n) => '“' . $n . '”', array_values($nomes));
    if (count($nomes) < 2) return $nomes[0] ?? '';
    return implode(', ', array_slice($nomes, 0, -1)) . ' e ' . $nomes[count($nomes) - 1];
}

/** Quem entrou e quem saiu da página inicial (destaquesDaHome antes e depois), ou '' se nada mudou. */
function fraseMudancaHome(array $homeAntes, array $homeDepois, array $produtos): string
{
    $entrou = array_values(array_diff($homeDepois, $homeAntes));
    $saiu = array_values(array_diff($homeAntes, $homeDepois));
    if (!$entrou && !$saiu) return '';
    $nomes = [];
    foreach ($produtos as $p) $nomes[(string) ($p['id'] ?? '')] = nomeCompleto($p);
    $nome = static fn(array $ids): array => array_map(static fn($x) => $nomes[$x] ?? $x, $ids);
    $partes = [];
    if ($entrou) $partes[] = (count($entrou) === 1 ? 'entrou ' : 'entraram ') . listaComE($nome($entrou));
    if ($saiu) $partes[] = (count($saiu) === 1 ? 'saiu ' : 'saíram ') . listaComE($nome($saiu));
    return ' A página inicial mudou: ' . implode(', ', $partes) . '.';
}
