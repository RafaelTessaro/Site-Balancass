<?php
/* =====================================================================
   Painel Balanças.com — tela "Mais procurados" (a=procurados)
   Todos os usuários; só leitura. Os números vêm de lib/estatisticas.php
   (contagem anônima do site, sem cookies). Sem scripts nem estilos
   inline: o gráfico é um SVG montado aqui, com atributos e classes.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

/** Ícone de gráfico (desenhado aqui, para não depender do sprite). */
function iconeProcurados(string $classe = 'i'): string
{
    return '<svg class="' . e($classe) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 20V10M10 20V4M16 20v-7M21.5 20H2.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>';
}

function numProc(int $n): string
{
    return number_format($n, 0, ',', '.');
}

/** "15/09 a 14/10" (com o ano quando muda de ano ou não é o ano de hoje). */
function textoPeriodo(string $de, string $ate): string
{
    $a = new DateTimeImmutable($de);
    $b = new DateTimeImmutable($ate);
    $ano = $a->format('Y') !== $b->format('Y') || $b->format('Y') !== date('Y');
    if ($de === $ate) return $b->format($ano ? 'd/m/Y' : 'd/m');
    return $a->format($ano ? 'd/m/Y' : 'd/m') . ' a ' . $b->format($ano ? 'd/m/Y' : 'd/m');
}

const MESES_CURTOS = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

/** Rótulo de um ponto da série: "03/10" (dia) ou "out/26" (mês). */
function rotuloSerie(string $k): string
{
    if (strlen($k) === 7) return MESES_CURTOS[(int) substr($k, 5, 2) - 1] . '/' . substr($k, 2, 2);
    return substr($k, 8, 2) . '/' . substr($k, 5, 2);
}

/** Barra horizontal (SVG): largura proporcional, sem estilo inline. */
function barraProc(int $n, int $max, string $classe = ''): string
{
    $w = $max > 0 ? max($n > 0 ? 1.5 : 0, round($n * 100 / $max, 1)) : 0;
    return '<svg class="proc-barra' . ($classe !== '' ? ' ' . e($classe) : '') . '" viewBox="0 0 100 8" preserveAspectRatio="none" aria-hidden="true" focusable="false">'
        . '<rect class="proc-barra__fundo" x="0" y="0" width="100" height="8"/><rect class="proc-barra__valor" x="0" y="0" width="' . $w . '" height="8"/></svg>';
}

/**
 * Gráfico de barras dos cliques no WhatsApp (por dia ou por mês). $primeiro = primeiro dia contado:
 * os dias (ou meses) antes dele ficam numa faixa clara, sem barra (o site ainda não contava; não é zero).
 */
function graficoProc(array $serie, bool $porMes, string $hoje, ?string $primeiro = null): void
{
    $n = count($serie);
    if (!$n) return;
    $max = max(1, ...array_values($serie));
    $total = array_sum($serie);
    $maiorK = array_search(max(array_values($serie)), $serie, true);
    $w = $n * 10;
    $rotulos = array_keys($serie);
    $meio = $rotulos[intdiv($n - 1, 2)];
    $kHoje = $porMes ? substr($hoje, 0, 7) : $hoje;
    $inicio = $primeiro === null ? '' : ($porMes ? substr($primeiro, 0, 7) : $primeiro);
    $nAntes = count(array_filter($rotulos, static fn($k) => (string) $k < $inicio));   // antes de começar a contar
    $desdeTxt = $nAntes ? textoPeriodo((string) $primeiro, (string) $primeiro) : '';
    $desc = 'Cliques no WhatsApp por ' . ($porMes ? 'mês' : 'dia') . ', de ' . rotuloSerie($rotulos[0]) . ' a ' . rotuloSerie($rotulos[$n - 1])
        . ': ' . numProc($total) . ' no total' . ($total ? '; o maior foi ' . numProc((int) $serie[$maiorK]) . ' em ' . rotuloSerie((string) $maiorK) : '') . '.'
        . ($nAntes ? ' Antes de ' . $desdeTxt . ' o site ainda não contava.' : '');
    ?>
      <div class="proc-graf">
        <p class="proc-graf__max" aria-hidden="true"><?= numProc($max) ?></p>
        <svg class="proc-graf__svg" viewBox="0 0 <?= $w ?> 100" preserveAspectRatio="none" role="img" aria-label="<?= e($desc) ?>" focusable="false">
          <?php if ($nAntes): ?><rect class="proc-graf__antes" x="0" y="0" width="<?= $nAntes * 10 ?>" height="100"><title><?= e('Antes de ' . $desdeTxt . ': o site ainda não contava') ?></title></rect><?php endif; ?>
          <line class="proc-graf__linha" x1="0" y1="50" x2="<?= $w ?>" y2="50" vector-effect="non-scaling-stroke"/>
          <line class="proc-graf__linha" x1="0" y1="2" x2="<?= $w ?>" y2="2" vector-effect="non-scaling-stroke"/>
          <?php $i = 0; foreach ($serie as $k => $v):
              if ($i < $nAntes) { $i++; continue; }
              $h = $v > 0 ? max(2.5, round($v * 98 / $max, 2)) : 1;
              $cls = 'proc-graf__barra' . ($v > 0 ? '' : ' is-zero') . ($kHoje === (string) $k ? ' is-hoje' : ''); ?>
          <rect class="<?= $cls ?>" x="<?= $i * 10 + 1.5 ?>" y="<?= 100 - $h ?>" width="7" height="<?= $h ?>"><title><?= e(rotuloSerie((string) $k) . ': ' . numProc((int) $v) . ((int) $v === 1 ? ' clique' : ' cliques') . ($kHoje === (string) $k ? ($porMes ? ' (este mês, até hoje)' : ' (hoje, ainda contando)') : '')) ?></title></rect>
          <?php $i++; endforeach; ?>
        </svg>
        <p class="proc-graf__eixo" aria-hidden="true"><span><?= e(rotuloSerie($rotulos[0])) ?></span><?php if ($n > 2): ?><span><?= e(rotuloSerie($meio)) ?></span><?php endif; ?><span><?= e(rotuloSerie($rotulos[$n - 1])) ?></span></p>
        <?php if (isset($serie[$kHoje]) || $nAntes): ?>
        <p class="proc-graf__legenda">
          <?php if (isset($serie[$kHoje])): ?><span><i class="proc-graf__cor" aria-hidden="true"></i>Preto: <?= $porMes ? 'este mês (até hoje)' : 'hoje (ainda contando)' ?></span><?php endif; ?>
          <?php if ($nAntes): ?><span><i class="proc-graf__cor proc-graf__cor--antes" aria-hidden="true"></i>Antes de <?= e($desdeTxt) ?> o site ainda não contava</span><?php endif; ?>
        </p>
        <?php endif; ?>
      </div>
      <details class="proc-tabela">
        <summary>Ver os números <?= $porMes ? 'mês a mês' : 'dia a dia' ?></summary>
        <table>
          <thead><tr><th scope="col"><?= $porMes ? 'Mês' : 'Dia' ?></th><th scope="col">Cliques no WhatsApp</th></tr></thead>
          <tbody>
          <?php foreach (array_reverse(array_slice($serie, $nAntes, null, true), true) as $k => $v): ?>
            <tr><td><?= e(rotuloSerie((string) $k)) ?><?= $kHoje === (string) $k ? ($porMes ? ' (até hoje)' : ' (hoje)') : '' ?></td><td><?= numProc((int) $v) ?></td></tr>
          <?php endforeach; ?>
          <?php if ($nAntes): ?>
            <tr class="proc-tabela__antes"><td colspan="2">Antes de <?= e($desdeTxt) ?> o site ainda não contava.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </details>
<?php
}

/** Um produto do ranking. $p = produto do catálogo (null = não está mais no catálogo). */
function itemProcurado(int $pos, string $id, array $n, ?array $p, string $nomeAntigo, int $max, array $nomesCat = []): void
{
    $vis = $p !== null && ($p['visivel'] ?? true) !== false;
    $img = $p !== null && is_string($p['imagem'] ?? null) && preg_match(RE_ARQUIVO_FOTO, $p['imagem']) ? urlFoto($p['imagem']) : '';
    $marca = $p !== null ? (string) ($p['marca'] ?? '') : '';
    $categoria = $p !== null ? (string) ($nomesCat[(string) ($p['categoria'] ?? '')] ?? '') : '';   // nome da categoria (tela Categorias)
    $nome = $p !== null ? (string) ($p['nome'] ?? $id) : ($nomeAntigo !== '' ? $nomeAntigo : $id);
    $soma = $n['abertos'] + $n['whatsapp'] + $n['ligar'];
    ?>
        <li class="proc-prod<?= $p === null ? ' is-fora' : '' ?>">
          <span class="proc-prod__pos" aria-hidden="true"><?= $pos ?></span>
          <span class="proc-prod__foto"><?php if ($img): ?><img src="<?= e($img) ?>" alt="" loading="lazy" decoding="async" width="56" height="56"><?php else: ?><?= icone('camera') ?><?php endif; ?></span>
          <span class="proc-prod__txt">
            <?php if ($marca !== '' || $categoria !== ''): ?><small><?= e(implode(' · ', array_filter([$marca, $categoria], 'strlen'))) ?></small><?php endif; ?>
            <b><?= e($nome) ?></b>
            <?php if ($p === null): ?><span class="selo selo--oculto">Não está mais no catálogo</span><?php elseif (!$vis): ?><span class="selo selo--oculto">Fora do site</span><?php endif; ?>
          </span>
          <span class="proc-prod__nums">
            <span><b><?= numProc($n['abertos']) ?></b> <?= $n['abertos'] === 1 ? 'aberto' : 'abertos' ?></span>
            <span class="proc-prod__wa"><b><?= numProc($n['whatsapp']) ?></b> WhatsApp</span>
            <?php if ($n['ligar']): ?><span><b><?= numProc($n['ligar']) ?></b> Ligar</span><?php endif; ?>
          </span>
          <?= barraProc($soma, $max, 'proc-prod__barra') ?>
          <?php if ($p !== null): ?>
          <a class="ic proc-prod__editar" href="<?= e(link_('editar', ['id' => $id])) ?>" title="Editar"><?= icone('edit') ?><span class="sr-only">Editar <?= e(trim($marca . ' ' . $nome)) ?></span></a>
          <?php endif; ?>
        </li>
<?php
}

/** Cliques de um tipo agrupados pelo lugar do botão: [o => ['total' => n, 'paginas' => [p => n]]], maiores primeiro. */
function lugaresAgrupados(array $lugares, string $tipo): array
{
    $g = [];
    foreach ($lugares as $k => $n) {
        [$t, $p, $o] = array_pad(explode('|', (string) $k), 3, '');
        if ($t !== $tipo) continue;
        $g[$o] ??= ['total' => 0, 'paginas' => []];
        $g[$o]['total'] += (int) $n;
        $g[$o]['paginas'][$p] = ($g[$o]['paginas'][$p] ?? 0) + (int) $n;
    }
    foreach ($g as &$x) arsort($x['paginas']);
    unset($x);
    uasort($g, static fn($a, $b) => $b['total'] <=> $a['total']);
    return $g;
}

/** Uma linha "de onde vêm os cliques": o lugar, as páginas (com quantos em cada) e a barra. */
function linhaLugar(string $o, array $x, int $max): void
{
    $pags = [];
    foreach ($x['paginas'] as $p => $n) $pags[] = (EST_PAGINAS[$p] ?? $p) . (count($x['paginas']) > 1 ? "\u{a0}" . numProc((int) $n) : '');   // número nunca sozinho na linha
    ?>
        <li>
          <span class="proc-lista__txt"><b><?= e(EST_ORIGENS[$o] ?? 'Outro ponto da página') ?></b><small><?= e(implode(' · ', $pags)) ?></small></span>
          <span class="proc-lista__n"><?= numProc($x['total']) ?></span>
          <?= barraProc($x['total'], $max) ?>
        </li>
<?php
}

/** Lista "de onde vêm os cliques" (WhatsApp ou Ligar), agrupada pelo lugar do botão; além dos 8 primeiros, em “Ver mais”. */
function listaLugares(array $lugares, string $tipo): void
{
    $g = lugaresAgrupados($lugares, $tipo);
    if (!$g) return;
    $max = max(array_column($g, 'total'));
    $primeiros = array_slice($g, 0, 8, true);
    $resto = array_slice($g, 8, null, true);
    ?>
      <ol class="proc-lista" role="list">
        <?php foreach ($primeiros as $o => $x) linhaLugar((string) $o, $x, $max); ?>
      </ol>
      <?php if ($resto): ?>
      <details class="proc-mais">
        <summary>Ver os outros <?= count($resto) ?> lugares</summary>
        <ol class="proc-lista" role="list">
          <?php foreach ($resto as $o => $x) linhaLugar((string) $o, $x, $max); ?>
        </ol>
      </details>
      <?php endif;
}

/**
 * Tela "Mais procurados". $per = periodoEstatisticas(); $r/$antes = resumoEstatisticas() do período
 * e do anterior; $primeiro = primeiro dia contado (null = nada ainda); $produtos = [id => produto]
 * do catálogo (null = catálogo ilegível); $nomesAntigos = [id => "Marca Nome"] de quem saiu.
 */
function telaProcurados(array $per, array $r, array $antes, ?string $primeiro, ?array $produtos, array $nomesAntigos, array $nomesCat = [], array $limitados = []): void
{
    pagina('Mais procurados', static function () use ($per, $r, $antes, $primeiro, $produtos, $nomesAntigos, $nomesCat, $limitados): void {
        $hoje = diaEstatisticas(time());
        cabecalhoPagina('Site · interesse dos visitantes', 'Mais procurados',
            'O que as pessoas mais abrem no site e de onde vêm os contatos pelo WhatsApp.'
            . ($primeiro !== null ? ' <span class="proc-desde">Contando desde ' . e((new DateTimeImmutable($primeiro))->format('d/m/Y')) . '.</span>' : ''));
        ?>
<?php   if ($primeiro === null): ?>
  <section class="proc-vazio">
    <?= iconeProcurados('i i--grande') ?>
    <h2>Os números começam a aparecer a partir de hoje</h2>
    <p>A partir de agora o site conta, por dia, as páginas vistas, os produtos abertos no catálogo e os cliques no WhatsApp e em “Ligar”. Volte daqui a alguns dias para ver quais produtos geram mais interesse e de onde vêm os contatos.</p>
  </section>
<?php   else:
            $comparavel = $primeiro <= $per['antesDe']; ?>
  <nav class="chips proc-periodos" aria-label="Período">
    <?php foreach (EST_PERIODOS as $k => $rot): ?>
    <a class="chip" href="<?= e(link_('procurados', $k === '30d' ? [] : ['periodo' => $k])) ?>"<?= $per['cod'] === $k ? ' aria-current="page"' : '' ?>><?= e($rot) ?></a>
    <?php endforeach; ?>
  </nav>
<?php
            $tot = $r['total'];
            $cartoes = ['pagina' => 'Visitas às páginas', 'produto' => 'Produtos abertos', 'whatsapp' => 'Cliques no WhatsApp', 'ligar' => 'Cliques em Ligar']; ?>
  <p class="proc-quando"><b><?= e(textoPeriodo($per['de'], $per['ate'])) ?></b>
    <?php if ($comparavel): ?><span>comparado com <?= e($per['anterior']) ?> (<?= e(textoPeriodo($per['antesDe'], $per['antesAte'])) ?>)</span>
    <?php else: ?><span>a comparação com <?= e($per['anterior']) ?> aparece quando a contagem tiver chegado lá</span><?php endif; ?>
  </p>
  <section class="proc-totais" aria-label="Totais do período">
    <?php foreach ($cartoes as $t => $rot):
            $v = variacaoEstatisticas($tot[$t], $antes['total'][$t], $comparavel); ?>
    <div class="proc-total proc-total--<?= e($t) ?>">
      <p class="proc-total__n"><?= numProc($tot[$t]) ?></p>
      <p class="proc-total__rot"><?= e($rot) ?></p>
      <p class="proc-var proc-var--<?= e($v['classe']) ?>"<?= $comparavel ? ' title="' . e('Antes: ' . numProc($antes['total'][$t])) . '"' : '' ?>><?= e($v['txt']) ?><?php if ($comparavel && $v['classe'] !== 'igual'): ?><span class="sr-only"> em relação a <?= e($per['anterior']) ?> (antes: <?= numProc($antes['total'][$t]) ?>)</span><?php endif; ?></p>
    </div>
    <?php endforeach; ?>
  </section>
  <?php if ($limitados):
            $diasTxt = array_map(static fn($d) => textoPeriodo($d, $d), array_slice($limitados, 0, 6));
            $ult = count($diasTxt) > 1 ? array_pop($diasTxt) : '';
            $diasTxt = implode(', ', $diasTxt) . ($ult !== '' ? ' e ' . $ult : '') . (count($limitados) > 6 ? ' e mais ' . (count($limitados) - 6) . ' dias' : ''); ?>
  <p class="dica proc-limitado"><?= icone('alert') ?><span><b>Em <?= e($diasTxt) ?> a contagem foi limitada por excesso de acessos automáticos.</b> Os números <?= count($limitados) === 1 ? 'desse dia' : 'desses dias' ?> podem estar menores do que as visitas de verdade.</span></p>
  <?php endif; ?>

  <?php if (!array_sum($tot)): ?>
  <div class="vazio"><p><b>Nenhuma visita registrada neste período.</b> Escolha outro período acima.</p></div>
  <?php endif; ?>

  <section class="caixa proc-caixa" aria-labelledby="proc-graf-tit">
    <h2 class="caixa__tit" id="proc-graf-tit">Cliques no WhatsApp por <?= $per['porMes'] ? 'mês' : 'dia' ?></h2>
    <?php graficoProc($r['serie'], $per['porMes'], $hoje, $primeiro); ?>
  </section>

  <section class="proc-secao" aria-labelledby="proc-prod-tit">
    <h2 class="secao-tit" id="proc-prod-tit">Produtos mais procurados</h2>
    <p class="dica"><?= icone('box') ?><span><b>Abertos:</b> quantas vezes a ficha do produto foi aberta no catálogo. <b>WhatsApp:</b> cliques em “Solicitar orçamento” desse produto (no cartão ou na ficha). A ordem soma aberturas, cliques no WhatsApp e em Ligar.</span></p>
    <?php if (!$r['produtos']): ?>
    <p class="ajuda proc-nada">Nenhum produto aberto neste período.</p>
    <?php else:
            $lista = $r['produtos'];
            $max = max(array_map(static fn($n) => $n['abertos'] + $n['whatsapp'] + $n['ligar'], $lista));
            $pos = 0; ?>
    <ol class="proc-prods" role="list">
      <?php foreach (array_slice($lista, 0, 10, true) as $id => $n) itemProcurado(++$pos, (string) $id, $n, $produtos[$id] ?? null, $nomesAntigos[$id] ?? '', $max, $nomesCat); ?>
    </ol>
    <?php if (count($lista) > 10): ?>
    <details class="proc-mais">
      <summary>Ver os outros <?= count($lista) - 10 ?> produtos</summary>
      <ol class="proc-prods" role="list">
        <?php foreach (array_slice($lista, 10, null, true) as $id => $n) itemProcurado(++$pos, (string) $id, $n, $produtos[$id] ?? null, $nomesAntigos[$id] ?? '', $max, $nomesCat); ?>
      </ol>
    </details>
    <?php endif; ?>
    <?php if ($produtos === null): ?><p class="ajuda">O catálogo do site está com problema agora: os produtos aparecem pelo código.</p><?php endif; ?>
    <?php endif; ?>
  </section>

  <div class="proc-colunas">
    <section class="proc-secao" aria-labelledby="proc-lug-tit">
      <h2 class="secao-tit" id="proc-lug-tit">De onde vêm os cliques no WhatsApp</h2>
      <p class="dica"><?= icone('seta') ?><span>O lugar do botão em que a pessoa tocou e, embaixo, em quais páginas.</span></p>
      <?php if (!$tot['whatsapp']): ?><p class="ajuda proc-nada">Nenhum clique no WhatsApp neste período.</p><?php else: listaLugares($r['lugares'], 'whatsapp'); endif; ?>
      <?php if ($tot['ligar']): ?>
      <h3 class="proc-sub">Cliques em Ligar</h3>
      <?php listaLugares($r['lugares'], 'ligar'); ?>
      <?php endif; ?>
    </section>

    <section class="proc-secao" aria-labelledby="proc-pag-tit">
      <h2 class="secao-tit" id="proc-pag-tit">Páginas</h2>
      <?php if (!$r['paginas']): ?><p class="ajuda proc-nada">Nenhuma visita neste período.</p><?php else:
            $maxV = max(1, ...array_map(static fn($x) => $x['visitas'], array_values($r['paginas']))); ?>
      <ol class="proc-lista" role="list">
        <?php foreach ($r['paginas'] as $p => $x): ?>
        <li>
          <span class="proc-lista__txt"><b><?= e(EST_PAGINAS[$p] ?? $p) ?></b><small><?= numProc($x['whatsapp']) ?> no WhatsApp<?= $x['ligar'] ? ' · ' . numProc($x['ligar']) . ' em Ligar' : '' ?></small></span>
          <span class="proc-lista__n"><?= numProc($x['visitas']) ?> <small><?= $x['visitas'] === 1 ? 'visita' : 'visitas' ?></small></span>
          <?= barraProc($x['visitas'], $maxV) ?>
        </li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>
    </section>
  </div>
<?php   endif; ?>

  <section class="caixa proc-aparelho" aria-labelledby="proc-ap-tit">
    <h2 class="caixa__tit" id="proc-ap-tit">Este aparelho</h2>
    <div class="proc-aparelho__chave" data-nao-contar-caixa hidden>
      <label class="chave">
        <input type="checkbox" role="switch" data-nao-contar>
        <span class="chave__trilho" aria-hidden="true"></span>
        <span class="chave__txt"><b>Não contar os acessos deste aparelho</b><small data-nao-contar-estado aria-live="polite"></small></span>
      </label>
    </div>
    <p class="ajuda">Ligado, as visitas que você fizer ao site <b>neste aparelho e neste navegador</b> não entram nos números (assim os seus testes não parecem interesse de clientes). Liga sozinho quando alguém entra no painel aqui. Desligue se clientes usam este aparelho, como o computador do balcão.</p>
    <p class="ajuda" data-nao-contar-sem-js>Para usar esta opção, o navegador precisa estar com o JavaScript ligado.</p>
  </section>

  <p class="dica proc-priv"><?= icone('lock') ?><span><b>Contagem anônima, sem cookies e sem guardar dados de quem visita.</b> O site só soma, por dia, páginas vistas, produtos abertos e cliques. Robôs (Google e outros) não entram. Para o limite contra excesso de acessos, só um código curto e temporário, apagado na virada do dia. Os números ficam guardados por 24 meses.</span></p>
<?php
    }, ['menu' => 'produtos', 'classe' => 'pg-procurados']);
}

/** Atalho no topo da lista de produtos (o integrador chama em telaLista, logo depois do cabeçalho). */
function atalhoProcurados(): void
{
    $c = resumoCurtoProcurados();
    $txt = !$c['tem'] ? 'Veja os produtos que mais geram interesse e de onde vêm os contatos.'
        : numProc($c['whatsapp']) . ($c['whatsapp'] === 1 ? ' clique' : ' cliques') . ' no WhatsApp e ' . numProc($c['visitas']) . ($c['visitas'] === 1 ? ' visita' : ' visitas') . ' nos últimos 7 dias.';
    // No celular, o cartão fica numa linha só (com os números curtos): a lista de produtos aparece na primeira tela
    $curto = $c['tem'] ? numProc($c['whatsapp']) . ' no WhatsApp · 7 dias' : '';
    ?>
  <a class="proc-atalho" href="<?= e(link_('procurados')) ?>">
    <?= iconeProcurados('i proc-atalho__ic') ?>
    <span class="proc-atalho__txt"><b>Mais procurados</b><span class="proc-atalho__longo"><?= e($txt) ?></span><?php if ($curto !== ''): ?><span class="proc-atalho__curto" aria-hidden="true"><?= e($curto) ?></span><?php endif; ?></span>
    <?= icone('seta', 'i proc-atalho__seta') ?>
  </a>
<?php
}

/** Cartão no hub da Conta (para todos os usuários). */
function cartaoProcurados(): void
{
    ?>
      <a class="cartao" href="<?= e(link_('procurados')) ?>">
        <?= iconeProcurados('i cartao__ic') ?>
        <span class="cartao__txt"><b>Mais procurados</b><span>Produtos e páginas que mais interessam aos visitantes e de onde vêm os cliques no WhatsApp.</span></span>
        <?= icone('seta', 'i cartao__seta') ?>
      </a>
<?php
}
