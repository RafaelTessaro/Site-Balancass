<?php
/* =====================================================================
   Painel Balanças.com — tela Categorias (HTML), só administradores
   Ações em lib/categorias.php. Tudo funciona sem JavaScript: cada linha
   tem setas (formulários) e "Renomear" / "Apagar" em <details> que abrem
   logo abaixo dela; com JavaScript (assets/painel.js, bloco Categorias),
   a ordem muda sem recarregar e só um painel fica aberto por vez.
   Tudo passa por e(); sem scripts nem estilos inline.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

/**
 * $estado (depois de um erro de digitação): ['form' => chave do formulário que volta aberto
 * (chaveFormCategoria), 'erro' => mensagem, 'valores' => o que foi digitado].
 */
function telaCategorias(array $cat, array $estado = []): void
{
    $categorias = array_values($cat['categorias']);
    $produtos = $cat['produtos'];
    $cont = contagemCategorias($produtos);
    $fora = [];   // fora do site, por categoria e por subcategoria
    foreach ($produtos as $p) {
        if (($p['visivel'] ?? true) !== false) continue;
        $c = (string) ($p['categoria'] ?? '');
        $fora[$c]['total'] = ($fora[$c]['total'] ?? 0) + 1;
        $fora[$c]['subs'][(string) ($p['subcategoria'] ?? '')] = ($fora[$c]['subs'][(string) ($p['subcategoria'] ?? '')] ?? 0) + 1;
    }
    $nomesPorSub = [];
    foreach ($produtos as $p) $nomesPorSub[(string) ($p['categoria'] ?? '')][(string) ($p['subcategoria'] ?? '')][] = nomeCompleto($p);
    $orfaos = produtosForaDasCategorias($categorias, $produtos);
    $nSubs = array_sum(array_map(static fn($c) => count($c['subcategorias']), $categorias));
    $ctx = ['cont' => $cont, 'fora' => $fora, 'nomes' => $nomesPorSub, 'estado' => $estado, 'categorias' => $categorias, 'total' => count($categorias)];

    pagina('Categorias', static function () use ($categorias, $produtos, $orfaos, $nSubs, $ctx, $estado): void { ?>
  <p class="migalha"><a href="index.php"><?= icone('up', 'i i--esq') ?>Produtos</a></p>
  <?php cabecalhoPagina('Catálogo do site', 'Categorias',
        '<span><b>' . count($categorias) . '</b> ' . (count($categorias) === 1 ? 'categoria' : 'categorias') . '</span><span><b>' . $nSubs . '</b> ' . ($nSubs === 1 ? 'subcategoria' : 'subcategorias') . '</span>'
        . '<span><b>' . count($produtos) . '</b> produtos</span>',
        '<a class="btn btn--linha btn--p" href="#nova" data-cat-ir-nova>' . icone('plus') . 'Nova categoria</a>'); ?>
  <?php if (!empty($estado['erro'])): ?>
  <div class="aviso aviso--erro" role="alert"><?= icone('alert') ?><p><b>Nada foi alterado.</b> <?= e($estado['erro']) ?> <a href="#<?= e(ancoraDoForm((string) ($estado['form'] ?? ''), $categorias)) ?>">Corrigir</a></p></div>
  <?php endif; ?>
  <div class="dica dica--cat">
    <?= icone('lista') ?>
    <p><span><b>Categorias</b> são as abas do catálogo do site, na ordem desta lista; <b>subcategorias</b> são os filtros de tipo dentro de cada uma.</span>
    <span><b>Renomear</b> muda o nome em todo o site, e os produtos acompanham. O endereço da categoria no site (ex.: <code>produtos.html?cat=balancas</code>) nunca muda.</span>
    <span>Para <b>apagar</b>, a categoria precisa estar vazia; a subcategoria pode ter produtos se você escolher para onde eles vão. Cada mudança guarda uma cópia de segurança (Conta → Cópias de segurança).</span></p>
  </div>
  <?php if ($orfaos): ?>
  <div class="aviso aviso--erro aviso--fixo cat-orfaos" role="alert">
    <?= icone('alert') ?>
    <div>
      <p><b><?= count($orfaos) === 1 ? '1 produto está' : count($orfaos) . ' produtos estão' ?> numa categoria ou subcategoria que não existe.</b> No site <?= count($orfaos) === 1 ? 'ele não aparece' : 'eles não aparecem' ?> nos filtros. Abra e escolha outra:</p>
      <ul><?php foreach (array_slice($orfaos, 0, 12) as $p): ?><li><a href="<?= e(link_('editar', ['id' => (string) $p['id']])) ?>"><?= e(nomeCompleto($p)) ?></a> <span class="cat-orfaos__onde">(<?= e((string) ($p['categoria'] ?? '')) ?> › <?= e((string) ($p['subcategoria'] ?? '')) ?>)</span></li><?php endforeach; ?></ul>
    </div>
  </div>
  <?php endif; ?>

  <ol class="ctgs" role="list" data-ctgs>
    <?php foreach ($categorias as $i => $c) blocoCategoria($c, $i, $ctx); ?>
  </ol>

  <?php formNovaCategoria($estado, count($categorias)); ?>
<?php
    }, ['menu' => 'produtos', 'classe' => 'pg-categorias']);
}

/** Âncora da tela para o formulário que deu erro. */
function ancoraDoForm(string $form, array $categorias): string
{
    $p = explode(':', $form);
    if (($p[0] ?? '') === 'cat' && isset($p[1])) return 'cat-' . $p[1];
    if (($p[0] ?? '') === 'subnova' && isset($p[1])) return 'subnova-' . $p[1];
    if (($p[0] ?? '') === 'sub' && isset($p[1], $p[2])) return 'sub-' . $p[1] . '-' . $p[2];
    return 'nova';
}

/** Este formulário voltou com erro? Devolve [aberto, valores digitados, erro]. */
function estadoDoForm(array $estado, string $chave): array
{
    $meu = ($estado['form'] ?? '') === $chave;
    return [$meu, $meu ? (array) ($estado['valores'] ?? []) : [], $meu ? (string) ($estado['erro'] ?? '') : ''];
}

function textoQtd(int $n, int $fora): string
{
    return qtdProdutos($n) . ($fora ? ' · ' . $fora . ' fora do site' : '');
}

/** Setas ↑↓ (formulários; com JavaScript a troca acontece sem recarregar). */
function setasCategoria(string $acao, array $campos, string $nome, bool $primeiro, bool $ultimo): void
{
    foreach (['cima' => ['up', 'Subir', $primeiro], 'baixo' => ['down', 'Descer', $ultimo]] as $dir => [$ic, $rot, $off]): ?>
      <form method="post" action="<?= e(link_($acao)) ?>" class="ctg__seta" data-cat-mover>
        <?= campoCsrf() ?><?php foreach ($campos as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?><input type="hidden" name="dir" value="<?= $dir ?>">
        <button type="submit" class="ic" aria-label="<?= e($rot . ' ' . $nome) ?>" title="<?= e($rot) ?>"<?= $off ? ' disabled' : '' ?> data-cat-<?= $dir ?>><?= icone($ic) ?></button>
      </form>
<?php endforeach;
}

function blocoCategoria(array $c, int $i, array $ctx): void
{
    $id = (string) $c['id'];
    $n = (int) ($ctx['cont'][$id]['total'] ?? 0);
    $fora = (int) ($ctx['fora'][$id]['total'] ?? 0);
    $noSite = $n - $fora;
    [$aberto, $val, $erro] = estadoDoForm($ctx['estado'], chaveFormCategoria('cat', $id));
    $subs = array_values($c['subcategorias']);
    ?>
    <li class="ctg" id="cat-<?= e($id) ?>" data-cat="<?= e($id) ?>">
      <div class="ctg__linha">
        <div class="ctg__txt">
          <p class="ctg__pos"><span data-cat-pos><?= $i + 1 ?>ª aba</span> · <span class="ctg__cod">?cat=<?= e($id) ?></span><?php if ($noSite === 0): ?> · <span class="ctg__oculta">não aparece no site (sem produtos à mostra)</span><?php endif; ?></p>
          <h2 class="ctg__nome"><?= e($c['nome']) ?> <span><?= e(textoQtd($n, $fora)) ?></span></h2>
          <?php if (trim((string) ($c['descricao'] ?? '')) !== ''): ?><p class="ctg__desc"><?= e((string) $c['descricao']) ?></p><?php endif; ?>
        </div>
        <?php setasCategoria('categoria-mover', ['cat' => $id], $c['nome'], $i === 0, $i === $ctx['total'] - 1); ?>
        <details class="ctg__det" data-cat-menu<?= $aberto ? ' open' : '' ?>>
          <summary class="ctg__bt"><?= icone('edit') ?><span>Editar</span><span class="sr-only"> <?= e($c['nome']) ?></span></summary>
          <form method="post" action="<?= e(link_('categoria-salvar')) ?>" class="ctg__painel" data-form-enviar>
            <?= campoCsrf() ?><input type="hidden" name="cat" value="<?= e($id) ?>">
            <?php if ($erro !== ''): ?><p class="campo__erro" role="alert"><?= icone('alert') ?><?= e($erro) ?></p><?php endif; ?>
            <div class="campo">
              <label for="cn-<?= e($id) ?>">Nome da categoria <small>até <?= LIMITES_CATEGORIA['nome'] ?> letras; aparece na aba do site</small></label>
              <input id="cn-<?= e($id) ?>" name="nome" required maxlength="<?= LIMITES_CATEGORIA['nome'] ?>" value="<?= e((string) ($val['nome'] ?? $c['nome'])) ?>" autocomplete="off"<?= $erro !== '' ? ' aria-invalid="true"' : '' ?>>
            </div>
            <div class="campo">
              <label for="cd-<?= e($id) ?>">Descrição <small>opcional; um resumo do que tem nela</small></label>
              <textarea id="cd-<?= e($id) ?>" name="descricao" rows="2" maxlength="<?= LIMITES_CATEGORIA['descricao'] ?>"><?= e((string) ($val['descricao'] ?? ($c['descricao'] ?? ''))) ?></textarea>
            </div>
            <p class="ajuda">O endereço no site continua <code>produtos.html?cat=<?= e($id) ?></code>: links antigos não quebram.</p>
            <div class="ctg__bts"><button type="submit" class="btn btn--verde btn--p"><?= icone('check') ?>Salvar</button></div>
          </form>
        </details>
        <details class="ctg__det ctg__det--perigo" data-cat-menu>
          <summary class="ctg__bt ctg__bt--perigo" title="Apagar a categoria"><?= icone('trash') ?><span class="sr-only">Apagar <?= e($c['nome']) ?></span></summary>
          <div class="ctg__painel">
            <?php if ($n > 0): ?>
            <p class="ctg__painel-tit">Apagar “<?= e($c['nome']) ?>”</p>
            <p class="ajuda">Para apagar, a categoria precisa estar vazia. Hoje ela tem <b><?= e(qtdProdutos($n)) ?></b>: mova <?= $n === 1 ? 'o produto' : 'os produtos' ?> para outra categoria (<a href="<?= e(link_('lista', ['cat' => $id, 'sel' => '1'])) ?>">Produtos → Selecionar → Mover</a>) ou apague as subcategorias dela, escolhendo para onde vão os produtos.</p>
            <?php elseif ($ctx['total'] <= 1): ?>
            <p class="ajuda">O catálogo precisa de pelo menos uma categoria.</p>
            <?php else: ?>
            <p class="ctg__painel-tit">Apagar “<?= e($c['nome']) ?>”</p>
            <p class="ajuda">Ela não tem produtos<?= $subs ? ' (' . (count($subs) === 1 ? 'a subcategoria dela também some' : 'as ' . count($subs) . ' subcategorias dela também somem') . ')' : '' ?>.</p>
            <form method="post" action="<?= e(link_('categoria-excluir')) ?>" class="ctg__bts" data-confirmar="<?= e(textoApagarCategoria($c)) ?>" data-confirmar-ok="Apagar" data-confirmar-tit="Apagar categoria?">
              <?= campoCsrf() ?><input type="hidden" name="cat" value="<?= e($id) ?>">
              <button type="submit" class="btn btn--perigo btn--p"><?= icone('trash') ?>Apagar a categoria</button>
            </form>
            <?php endif; ?>
          </div>
        </details>
      </div>
      <ol class="sbs" role="list" data-sbs>
        <?php foreach ($subs as $k => $s) linhaSub($c, $s, $k, count($subs), $ctx); ?>
      </ol>
      <?php if (!$subs): ?><p class="sbs__vazio">Nenhuma subcategoria: crie uma para poder pôr produtos nesta categoria.</p><?php endif; ?>
      <?php formNovaSub($c, $ctx); ?>
    </li>
<?php
}

function linhaSub(array $c, string $s, int $k, int $total, array $ctx): void
{
    $id = (string) $c['id'];
    $n = (int) ($ctx['cont'][$id]['subs'][$s] ?? 0);
    $fora = (int) ($ctx['fora'][$id]['subs'][$s] ?? 0);
    $pos = $k + 1;
    $campos = ['cat' => $id, 'sub' => $s];
    [$aberto, $val, $erro] = estadoDoForm($ctx['estado'], chaveFormCategoria('sub', $id, $pos));
    $nomes = $ctx['nomes'][$id][$s] ?? [];
    $uid = $id . '-' . $pos;
    $links = avisoLinksDaSub($c, $s);   // link fixo para este tipo nas páginas do site (ex.: Assistência técnica)
    $linksApagar = avisoLinksDaSub($c, $s, true);
    ?>
        <li class="sb<?= $n ? '' : ' is-vazia' ?>" id="sub-<?= e($uid) ?>" data-sub="<?= e($s) ?>">
          <p class="sb__txt"><span class="sb__nome"><?= e($s) ?></span> <span class="sb__n"><?= e(textoQtd($n, $fora)) ?></span></p>
          <?php setasCategoria('subcategoria-mover', $campos, $s, $k === 0, $k === $total - 1); ?>
          <details class="ctg__det" data-cat-menu<?= $aberto ? ' open' : '' ?>>
            <summary class="ctg__bt"><?= icone('edit') ?><span>Renomear</span><span class="sr-only"> <?= e($s) ?></span></summary>
            <form method="post" action="<?= e(link_('subcategoria-salvar')) ?>" class="ctg__painel" data-form-enviar>
              <?= campoCsrf() ?><input type="hidden" name="cat" value="<?= e($id) ?>"><input type="hidden" name="sub" value="<?= e($s) ?>">
              <?php if ($erro !== ''): ?><p class="campo__erro" role="alert"><?= icone('alert') ?><?= e($erro) ?></p><?php endif; ?>
              <div class="campo">
                <label for="sn-<?= e($uid) ?>">Novo nome <small>até <?= LIMITES_CATEGORIA['subcategoria'] ?> letras</small></label>
                <input id="sn-<?= e($uid) ?>" name="nome" required maxlength="<?= LIMITES_CATEGORIA['subcategoria'] ?>" value="<?= e((string) ($val['nome'] ?? $s)) ?>" autocomplete="off"<?= $erro !== '' ? ' aria-invalid="true"' : '' ?>>
              </div>
              <p class="ajuda"><?= $n ? ($n === 1 ? 'O produto desta subcategoria muda junto.' : 'Os ' . $n . ' produtos desta subcategoria mudam junto.') . ' ' : '' ?>Links que já usavam o nome antigo abrem a categoria inteira.</p>
              <?php if ($links !== ''): ?><p class="ajuda ajuda--alerta"><?= icone('alert') ?><span><b>Atenção:</b> <?= e($links) ?></span></p><?php endif; ?>
              <div class="ctg__bts"><button type="submit" class="btn btn--verde btn--p"><?= icone('check') ?>Renomear</button></div>
            </form>
          </details>
          <details class="ctg__det ctg__det--perigo" data-cat-menu>
            <summary class="ctg__bt ctg__bt--perigo" title="Apagar a subcategoria"><?= icone('trash') ?><span class="sr-only">Apagar <?= e($s) ?></span></summary>
            <?php if ($n === 0): ?>
            <form method="post" action="<?= e(link_('subcategoria-excluir')) ?>" class="ctg__painel" data-confirmar="<?= e(textoApagarSub($c, $s)) ?>" data-confirmar-ok="Apagar" data-confirmar-tit="Apagar subcategoria?">
              <?= campoCsrf() ?><input type="hidden" name="cat" value="<?= e($id) ?>"><input type="hidden" name="sub" value="<?= e($s) ?>">
              <p class="ctg__painel-tit">Apagar “<?= e($s) ?>”</p>
              <p class="ajuda">Ela não tem produtos. Sai da lista do painel e dos filtros de tipo do site.</p>
              <?php if ($linksApagar !== ''): ?><p class="ajuda ajuda--alerta"><?= icone('alert') ?><span><b>Atenção:</b> <?= e($linksApagar) ?></span></p><?php endif; ?>
              <div class="ctg__bts"><button type="submit" class="btn btn--perigo btn--p"><?= icone('trash') ?>Apagar a subcategoria</button></div>
            </form>
            <?php else: ?>
            <form method="post" action="<?= e(link_('subcategoria-excluir')) ?>" class="ctg__painel" data-confirmar="<?= e('Mover os produtos de “' . $s . '” e apagar a subcategoria?') ?>" data-confirmar-ok="Mover e apagar" data-confirmar-tit="Mover e apagar?" data-cat-mover-apagar data-n="<?= $n ?>" data-sub-nome="<?= e($s) ?>" data-cat-nome="<?= e($c['nome']) ?>">
              <?= campoCsrf() ?><input type="hidden" name="cat" value="<?= e($id) ?>"><input type="hidden" name="sub" value="<?= e($s) ?>">
              <p class="ctg__painel-tit">Apagar “<?= e($s) ?>”</p>
              <p class="ajuda">Ela tem <b><?= e(qtdProdutos($n)) ?></b>: <?= e(listaCurtaDeNomes($nomes)) ?>. Para apagar, escolha para onde <?= $n === 1 ? 'ele vai' : 'eles vão' ?> (na mesma alteração, com cópia de segurança).</p>
              <div class="campo">
                <label for="sd-<?= e($uid) ?>">Mover <?= $n === 1 ? 'o produto' : 'os ' . $n . ' produtos' ?> para</label>
                <select id="sd-<?= e($uid) ?>" name="destino" required data-cat-destino>
                  <option value="">Escolha a categoria e a subcategoria…</option>
                  <?php foreach ($ctx['categorias'] as $dc): if (!$dc['subcategorias']) continue; ?>
                  <optgroup label="<?= e($dc['nome']) ?>">
                    <?php foreach ($dc['subcategorias'] as $ds): if ($dc['id'] === $id && $ds === $s) continue; ?><option value="<?= e($dc['id'] . '|' . $ds) ?>"><?= e($ds) ?></option><?php endforeach; ?>
                  </optgroup>
                  <?php endforeach; ?>
                </select>
              </div>
              <p class="ajuda">Quem muda de categoria entra no fim dela.</p>
              <?php if ($linksApagar !== ''): ?><p class="ajuda ajuda--alerta"><?= icone('alert') ?><span><b>Atenção:</b> <?= e($linksApagar) ?></span></p><?php endif; ?>
              <div class="ctg__bts"><button type="submit" class="btn btn--perigo btn--p"><?= icone('mover') ?>Mover e apagar</button></div>
            </form>
            <?php endif; ?>
          </details>
        </li>
<?php
}

/** “Toledo Prix 4 Uno”, “Toledo Prix 3 Fit” e mais 4 */
function listaCurtaDeNomes(array $nomes, int $max = 4): string
{
    $mostra = array_map(static fn($n) => '“' . $n . '”', array_slice($nomes, 0, $max));
    $resto = count($nomes) - count($mostra);
    if ($resto > 0) return implode(', ', $mostra) . ' e mais ' . $resto;
    if (count($mostra) <= 1) return implode('', $mostra);
    return implode(', ', array_slice($mostra, 0, -1)) . ' e ' . end($mostra);
}

function formNovaSub(array $c, array $ctx): void
{
    $id = (string) $c['id'];
    [$aberto, $val, $erro] = estadoDoForm($ctx['estado'], chaveFormCategoria('subnova', $id));
    ?>
      <details class="sb-nova" id="subnova-<?= e($id) ?>" data-cat-menu<?= $aberto ? ' open' : '' ?>>
        <summary class="sb-nova__bt"><?= icone('plus') ?><span>Nova subcategoria</span><span class="sr-only"> em <?= e($c['nome']) ?></span></summary>
        <form method="post" action="<?= e(link_('subcategoria-salvar')) ?>" class="ctg__painel" data-form-enviar>
          <?= campoCsrf() ?><input type="hidden" name="cat" value="<?= e($id) ?>">
          <?php if ($erro !== ''): ?><p class="campo__erro" role="alert"><?= icone('alert') ?><?= e($erro) ?></p><?php endif; ?>
          <div class="campo">
            <label for="sv-<?= e($id) ?>">Nome da subcategoria <small>em <?= e($c['nome']) ?></small></label>
            <input id="sv-<?= e($id) ?>" name="nome" required maxlength="<?= LIMITES_CATEGORIA['subcategoria'] ?>" value="<?= e((string) ($val['nome'] ?? '')) ?>" autocomplete="off" placeholder="Ex.: Etiquetas adesivas"<?= $erro !== '' ? ' aria-invalid="true"' : '' ?>>
          </div>
          <div class="ctg__bts"><button type="submit" class="btn btn--verde btn--p"><?= icone('plus') ?>Criar subcategoria</button></div>
        </form>
      </details>
<?php
}

function formNovaCategoria(array $estado, int $total): void
{
    [$aberto, $val, $erro] = estadoDoForm($estado, chaveFormCategoria('nova'));
    $cheio = $total >= LIMITES_CATEGORIA['categorias'];
    ?>
  <section class="caixa ctg-nova" id="nova" aria-labelledby="nova-tit" tabindex="-1">
    <h2 class="caixa__tit" id="nova-tit">Nova categoria</h2>
    <?php if ($cheio): ?>
    <p class="ajuda">O catálogo já tem <?= LIMITES_CATEGORIA['categorias'] ?> categorias, o máximo. Apague uma que não usa mais para criar outra.</p>
    <?php else: ?>
    <p class="ajuda">Entra no fim da lista (depois, use as setas). Ela aparece no site quando tiver produtos: cadastre um produto nela ou mova produtos para ela em Produtos → Selecionar → Mover.</p>
    <form method="post" action="<?= e(link_('categoria-salvar')) ?>" class="ctg-nova__form" data-form-enviar>
      <?= campoCsrf() ?>
      <?php if ($erro !== ''): ?><div class="aviso aviso--erro" role="alert"><?= icone('alert') ?><p><?= e($erro) ?></p></div><?php endif; ?>
      <div class="campo">
        <label for="nc-nome">Nome <small>até <?= LIMITES_CATEGORIA['nome'] ?> letras; nome curto fica melhor na aba do site</small></label>
        <input id="nc-nome" name="nome" required maxlength="<?= LIMITES_CATEGORIA['nome'] ?>" value="<?= e((string) ($val['nome'] ?? '')) ?>" autocomplete="off" placeholder="Ex.: Etiquetas e bobinas">
      </div>
      <div class="campo">
        <label for="nc-desc">Descrição <small>opcional</small></label>
        <textarea id="nc-desc" name="descricao" rows="2" maxlength="<?= LIMITES_CATEGORIA['descricao'] ?>" placeholder="Ex.: Etiquetas adesivas, bobinas térmicas e ribbons."><?= e((string) ($val['descricao'] ?? '')) ?></textarea>
      </div>
      <div class="campo">
        <label for="nc-subs">Subcategorias <small>uma por linha; pelo menos uma</small></label>
        <textarea id="nc-subs" name="subcategorias" rows="3" required placeholder="Ex.:&#10;Etiquetas adesivas&#10;Bobinas térmicas"><?= e((string) ($val['subcategorias'] ?? '')) ?></textarea>
        <p class="ajuda">Os produtos precisam de uma subcategoria. No site, elas viram os filtros de tipo.</p>
      </div>
      <div class="ctg__bts"><button type="submit" class="btn btn--verde"><?= icone('plus') ?>Criar categoria</button></div>
    </form>
    <?php endif; ?>
  </section>
<?php
}

/* ------------------------------------------------------------------
   Atalhos para a tela (chamados pelas outras telas; só administradores)
   ------------------------------------------------------------------ */
/** Botão "Categorias" (lista de produtos, ao lado de "Selecionar"). Editor: nada. */
function atalhoCategorias(): string
{
    if (!ehAdmin()) return '';
    return '<a class="btn btn--linha btn--p lista-topo__cat" href="' . e(link_('categorias')) . '" title="Criar, renomear, mudar a ordem e apagar categorias e subcategorias">'
        . icone('categorias') . '<span>Categorias</span></a>';
}

/** Linha de ajuda embaixo de Categoria/Subcategoria no formulário do produto. Editor: nada. */
function ajudaCategoriasNoForm(): string
{
    if (!ehAdmin()) return '';
    return '<p class="ajuda ajuda--cat">Falta uma categoria ou subcategoria? Crie em <a href="' . e(link_('categorias')) . '">Categorias</a> (o que já foi digitado aqui fica guardado no rascunho).</p>';
}

/** Cartão do hub da Conta (administradores). $categorias: as do catálogo (para o resumo), ou null se não deu para ler. */
function cartaoCategorias(?array $categorias): void
{
    if (!ehAdmin()) return;
    $n = $categorias === null ? null : count($categorias);
    $s = $categorias === null ? 0 : array_sum(array_map(static fn($c) => count((array) ($c['subcategorias'] ?? [])), $categorias));
    ?>
      <a class="cartao" href="<?= e(link_('categorias')) ?>">
        <?= icone('categorias', 'i cartao__ic') ?>
        <span class="cartao__txt"><b>Categorias</b><span><?= $n === null ? 'As abas do catálogo do site e os tipos de cada uma.' : e($n . ($n === 1 ? ' categoria' : ' categorias') . ' e ' . $s . ($s === 1 ? ' subcategoria' : ' subcategorias') . '.') ?> Crie, renomeie, mude a ordem e apague.</span></span>
        <?= icone('seta', 'i cartao__seta') ?>
      </a>
<?php
}
