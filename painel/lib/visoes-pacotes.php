<?php
/* =====================================================================
   Painel Balanças.com — telas da atualização do catálogo (pacotes)
   Aviso no topo da lista de produtos, tela de revisão (marcar e aplicar)
   e lista de atualizações. Só administradores. Funciona sem JavaScript
   (formulário comum); com ele, a confirmação resume o que está marcado.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

/** Cartão no topo da lista de produtos (só administradores): uma atualização pronta ou com problema. */
function avisoPacotes(array $avisos): void
{
    foreach ($avisos as $a):
        $erro = $a['erro'] !== null;
        $idTit = 'pk-av-' . $a['id']; ?>
  <section class="pk-aviso<?= $erro ? ' pk-aviso--erro' : '' ?>" aria-labelledby="<?= e($idTit) ?>">
    <span class="pk-aviso__ic" aria-hidden="true"><?= icone($erro ? 'alert' : 'box') ?></span>
    <div class="pk-aviso__txt">
      <p class="pk-aviso__sobre"><?= e($a['titulo']) ?></p>
      <h2 id="<?= e($idTit) ?>"><?= $erro ? 'Atualização do catálogo com problema' : 'Atualização do catálogo pronta' ?></h2>
      <p><?= $erro ? 'Ela não pode ser aplicada. Avise quem cuida do site; o catálogo continua como está.' : e(implode(', ', $a['frases']) . '.') ?></p>
    </div>
    <a class="btn <?= $erro ? 'btn--linha' : 'btn--verde' ?> btn--p pk-aviso__bt" href="<?= e(link_('pacote', ['id' => $a['id']])) ?>"><?= $erro ? 'Ver o problema' : 'Ver e aplicar' ?><?= icone('seta') ?></a>
  </section>
<?php
    endforeach;
}

/** Lista das atualizações que estão no servidor (Conta → Atualizações do catálogo). */
function telaPacotes(array $lista): void
{
    $nomes = nomesDosUsuarios();
    pagina('Atualizações do catálogo', static function () use ($lista, $nomes): void { ?>
  <p class="migalha"><a href="index.php"><?= icone('up', 'i i--esq') ?>Produtos</a></p>
<?php   cabecalhoPagina('Catálogo do site', 'Atualizações do catálogo',
            'Mudanças no catálogo preparadas por quem cuida do site (produtos novos, modelos trocados pelos atuais, fotos novas). Você revê e aplica com um clique; antes, o painel guarda uma cópia de segurança.');
        if (!$lista): ?>
  <div class="vazio"><p><b>Nenhuma atualização do catálogo por aqui.</b> Quando chegar uma, ela aparece no topo da lista de produtos.</p></div>
<?php   else: ?>
  <ol class="copias" role="list">
    <?php foreach ($lista as $x):
        $pac = $x['pacote']; $reg = $x['registro']; $n = $x['pendentes']; $nd = (int) ($x['desmarcadas'] ?? 0);
        // Aplicada e depois desfeita (cópia restaurada): não dizer "Aplicada em…", como a tela de revisão
        $desfeita = !empty($x['situacao']['desfeita']);
        if ($pac['erro'] !== null) $sit = 'Com problema: não pode ser aplicada.';
        elseif ($desfeita) $sit = 'Desfeita' . (!empty($x['situacao']['restaurada']) ? ' em ' . quando((int) $x['situacao']['restaurada']) : '') . ' (cópia de segurança restaurada): pode ser aplicada de novo na tela.';
        elseif (array_sum($n)) $sit = 'Para aplicar: ' . implode(', ', frasesContagem($n)) . '.' . ($reg['dispensado'] ? ' Guardada para depois em ' . quando($reg['dispensado'], false) . '.' : '');
        else $sit = 'Nada pendente.' . ($nd ? ' ' . ($nd === 1 ? '1 mudança ficou desmarcada e pode ser aplicada na tela.' : $nd . ' mudanças ficaram desmarcadas e podem ser aplicadas na tela.') : '');
        if ($reg['aplicado'] && !$desfeita) $sit .= ' Aplicada em ' . quando($reg['aplicado']) . ' por ' . nomeDe($reg['por'], $nomes) . '.'; ?>
    <li class="copia">
      <div class="copia__txt">
        <p class="copia__quando"><?= e($pac['titulo']) ?></p>
        <p class="copia__motivo"><?= e($sit) ?></p>
        <?php if ($pac['criado'] !== ''): ?><p class="copia__meta">Preparada em <?= e(date('d/m/Y', (int) strtotime($pac['criado']))) ?> · <?= count($pac['operacoes']) ?> mudanças</p><?php endif; ?>
      </div>
      <div class="copia__bts"><a class="btn btn--linha btn--p" href="<?= e(link_('pacote', ['id' => $pac['id']])) ?>">Ver<?= icone('seta') ?></a></div>
    </li>
    <?php endforeach; ?>
  </ol>
<?php   endif;
    }, ['menu' => 'produtos', 'classe' => 'pg-pacotes']);
}

/** Texto da janela de confirmação (o painel.js refaz igual quando a pessoa marca/desmarca). */
function textoAplicarPacote(array $n): string
{
    $total = array_sum($n);
    $linhas = array_map(static fn($f) => '• ' . $f, frasesContagem($n, 'dialogo'));
    return 'No catálogo do site (' . $total . ($total === 1 ? ' mudança' : ' mudanças') . "):\n" . implode("\n", $linhas)
        . "\nAntes, o painel guarda uma cópia de segurança: restaurar essa cópia desfaz tudo de uma vez, fotos inclusive.";
}

/** Quadro do topo depois de aplicar: quando, o que vale e qual cópia desfaz tudo (ou que foi desfeita). */
function quadroAplicado(array $pac, array $reg, array $sit, array $nomes, int $feitas, int $total, int $podem): void
{
    if (!$reg['aplicado'] || !$sit) return;
    if ($sit['desfeita']): ?>
  <div class="aviso aviso--info aviso--fixo pk-feito pk-feito--desfeita" role="status">
    <?= icone('pausa') ?>
    <p><b>A atualização foi desfeita<?= $sit['restaurada'] ? ' em ' . e(quando((int) $sit['restaurada'])) . ' (cópia de segurança restaurada)' : '' ?>.</b>
      Ela tinha sido aplicada em <?= e(quando((int) $reg['aplicado'])) ?> por <?= e(nomeDe($reg['por'], $nomes)) ?>; agora nenhuma das mudanças aplicadas está no catálogo.
      <?= $podem ? 'Para aplicar de novo, marque as mudanças (ou toque em <b>Marcar todas</b>) e depois em <b>Aplicar os marcados</b>.' : '' ?></p>
  </div>
<?php   return;
    endif;
    $valendo = $sit['valendo'];
    $primeira = $sit['primeira'];
    $vezes = count($valendo); ?>
  <div class="aviso aviso--fixo pk-feito" role="status">
    <?= icone('check') ?>
    <p><b>Aplicado em <?= e(quando((int) $reg['aplicado'])) ?> por <?= e(nomeDe($reg['por'], $nomes)) ?>.</b>
      <?= $feitas === $total ? 'Todas as ' . $total . ' mudanças já estão no catálogo.' : $feitas . ' de ' . $total . ' mudanças já estão no catálogo' . ($podem ? '; as outras podem ser aplicadas quando quiser.' : '.') ?>
<?php if ($sit['copiaExiste']): ?>
      Para desfazer tudo de uma vez: <a href="<?= e(link_('copias')) ?>">Conta → Cópias de segurança</a> → <b>Restaurar</b> a cópia de <b><?= e(quando((int) ($primeira['quando'] ?? 0))) ?></b> (“<?= e(rotuloCopiaPacote($pac['titulo'])) ?>”)<?php
        if ($vezes > 1): ?>. A atualização foi aplicada em <?= $vezes ?> vezes; essa é a cópia de antes da primeira e desfaz todas (e também o que mais tiver mudado no catálogo depois dela)<?php
            // Duas aplicações no mesmo minuto: a lista de cópias mostra o mesmo horário duas vezes
            $minuto = static fn($h) => date('YmdHi', (int) ($h['quando'] ?? 0));
            if (count(array_filter($valendo, static fn($h) => $minuto($h) === $minuto($primeira))) > 1): ?>. Se aparecerem duas cópias com esse horário, restaure a de baixo (a mais antiga)<?php endif;
        endif; ?>.</p>
<?php else: ?>
      A cópia de segurança de antes da aplicação já não existe (essas cópias ficam guardadas 30 dias): para desfazer, troque ou exclua os produtos pela lista de produtos.</p>
<?php endif; ?>
  </div>
<?php
}

/** Tela de revisão: cada mudança com caixinha, foto, ficha, motivo e situação. $sit: situacaoDasAplicacoes(). */
function telaPacote(array $pac, array $estados, array $reg, array $antigos = [], array $sit = []): void
{
    $nomes = nomesDosUsuarios();
    $ok = $pac['erro'] === null;
    $grupos = array_fill_keys(array_keys(GRUPOS_PACOTE), []);
    foreach ($pac['operacoes'] as $i => $o) $grupos[$o['tipo']][] = $i;
    $marcadas = array_fill_keys(TIPOS_OPERACAO, 0);
    $podem = 0; $feitas = 0;
    foreach ($estados as $i => $st) {
        if ($st['marcado']) $marcadas[$pac['operacoes'][$i]['tipo']]++;
        if ($st['estado'] === 'aplicavel' || $st['estado'] === 'opcional') $podem++;
        if ($st['estado'] === 'feito') $feitas++;
    }
    $total = count($pac['operacoes']);
    $desc = ($pac['resumo'] !== '' ? '<span>' . e($pac['resumo']) . '</span>' : '')
        . ($pac['criado'] !== '' ? '<span>preparada em ' . e(date('d/m/Y', (int) strtotime($pac['criado']))) . '</span>' : '')
        . ($ok ? '<span><b>' . $total . '</b> ' . ($total === 1 ? 'mudança' : 'mudanças') . '</span>' : '');

    pagina('Atualização do catálogo', static function () use ($pac, $estados, $reg, $nomes, $ok, $grupos, $marcadas, $podem, $feitas, $total, $desc, $antigos, $sit): void { ?>
  <p class="migalha"><a href="index.php"><?= icone('up', 'i i--esq') ?>Produtos</a></p>
<?php   cabecalhoPagina('Atualização do catálogo', $pac['titulo'], $desc);
        if (!$ok): ?>
  <div class="aviso aviso--erro aviso--fixo" role="alert"><?= icone('alert') ?><p><b>Pacote com problema: <?= e($pac['erro']) ?>.</b> Nada desta atualização pode ser aplicado e o catálogo do site continua como está. Avise quem cuida do site: o pacote precisa ser corrigido e enviado de novo.</p></div>
<?php   endif;
        if ($ok) quadroAplicado($pac, $reg, $sit, $nomes, $feitas, $total, $podem);
        if ($reg['dispensado'] && ($podem || !$ok)): ?>
  <div class="aviso aviso--info aviso--fixo pk-dispensado" role="status">
    <?= icone('pausa') ?>
    <p>Você escolheu <b>Agora não</b> em <?= e(quando((int) $reg['dispensado'])) ?>: o aviso desta atualização não aparece na lista de produtos.</p>
    <form method="post" action="<?= e(link_('pacote-dispensar')) ?>" class="aviso__form">
      <?= campoCsrf() ?><input type="hidden" name="id" value="<?= e($pac['id']) ?>"><input type="hidden" name="mostrar" value="1">
      <button type="submit" class="btn btn--linha btn--p"><?= icone('show') ?>Mostrar o aviso de novo</button>
    </form>
  </div>
<?php   endif;
        if (!$ok) {
            if (!$reg['dispensado']): ?>
  <form method="post" action="<?= e(link_('pacote-dispensar')) ?>" class="pk-so-dispensar">
    <?= campoCsrf() ?><input type="hidden" name="id" value="<?= e($pac['id']) ?>">
    <button type="submit" class="btn btn--linha btn--p"><?= icone('pausa') ?>Esconder o aviso da lista de produtos</button>
  </form>
<?php       endif;
            return;
        } ?>
  <p class="dica pk-dica"><?= icone('check') ?><span>Confira cada mudança. <b>As marcadas entram no site juntas</b>; antes, o painel guarda uma cópia de segurança (restaurar essa cópia desfaz tudo, fotos inclusive). O que ficar desmarcado pode ser aplicado depois. Toque na foto para ver maior.</span></p>
  <?php if ($podem > array_sum($marcadas)): ?>
  <p class="pk-todas"><a class="btn btn--linha btn--p" href="<?= e(link_('pacote', ['id' => $pac['id'], 'marcar' => 'todas'])) ?>#pk-form" data-pacote-todas><?= icone('check') ?>Marcar todas (<?= $podem ?>)</a></p>
  <?php endif; ?>
  <form id="pk-form" class="pk" method="post" action="<?= e(link_('pacote-aplicar')) ?>" data-pacote data-confirmar="<?= e(textoAplicarPacote($marcadas)) ?>" data-confirmar-ok="Aplicar" data-confirmar-tit="Aplicar a atualização?" data-confirmar-tipo="ok">
    <?= campoCsrf() ?>
    <input type="hidden" name="id" value="<?= e($pac['id']) ?>">
    <input type="hidden" name="versao" value="<?= e($pac['versao']) ?>">
    <?php foreach ($grupos as $tipo => $lista):
        if (!$lista) continue; ?>
    <section class="pk-grupo" aria-labelledby="pk-g-<?= e($tipo) ?>">
      <h2 class="grupo__tit" id="pk-g-<?= e($tipo) ?>"><?= e(GRUPOS_PACOTE[$tipo]) ?> <span><?= count($lista) ?></span></h2>
      <ol class="pk-lista" role="list">
        <?php foreach ($lista as $i) itemPacote($pac, $i, $estados[$i], $nomes, $antigos); ?>
      </ol>
    </section>
    <?php endforeach; ?>
  </form>
  <?php if ($podem): ?>
  <div class="barra-salvar pk-barra<?= $reg['dispensado'] ? ' pk-barra--so' : '' ?>">
    <p class="pk-barra__conta" data-pacote-conta aria-live="polite"><?= array_sum($marcadas) ?> de <?= $podem ?> marcadas</p>
    <?php if (!$reg['dispensado']): ?>
    <form method="post" action="<?= e(link_('pacote-dispensar')) ?>" class="pk-barra__form">
      <?= campoCsrf() ?><input type="hidden" name="id" value="<?= e($pac['id']) ?>">
      <button type="submit" class="btn btn--linha" title="Esconde o aviso da lista de produtos sem mudar nada no site">Agora não</button>
    </form>
    <?php endif; ?>
    <button type="submit" form="pk-form" class="btn btn--verde" data-pacote-aplicar><?= icone('check') ?><span>Aplicar os marcados</span></button>
  </div>
  <?php endif; ?>
<?php
    }, ['menu' => 'produtos', 'classe' => 'pg-produto pg-pacote']);
}

/** Uma mudança na tela de revisão. $antigos: produtos que já saíram do catálogo (das cópias), para mostrar o nome. */
function itemPacote(array $pac, int $i, array $st, array $nomes, array $antigos = []): void
{
    $o = $pac['operacoes'][$i];
    $tipo = $o['tipo'];
    $atual = $st['atual'];
    // Já saiu do catálogo (aplicada por este pacote): nome, marca e resumo de como era, sem a foto (está na lixeira)
    $antigo = $atual === null && isset($o['alvo']) ? ($antigos[$o['alvo']] ?? null) : null;
    if ($antigo !== null) unset($antigo['imagem']);
    $ativo = $st['estado'] === 'aplicavel' || $st['estado'] === 'opcional';
    $campo = 'op-' . $i;
    // Nome do produto antigo que já não está no catálogo: das cópias deste pacote, senão o "nome_antigo" do pacote, senão o código
    $nomeAntigo = $antigo !== null ? nomeCompleto($antigo) : (($o['nome_antigo'] ?? '') !== '' ? $o['nome_antigo'] : (string) ($o['alvo'] ?? ''));
    // O que aparece: o produto do pacote (novo/troca), ou o produto do catálogo (foto/sair)
    $p = $o['produto'] ?? $atual ?? $antigo ?? ['id' => $o['alvo'] ?? '', 'nome' => $nomeAntigo, 'marca' => ''];
    $nome = nomeCompleto($p) !== '' ? nomeCompleto($p) : (string) ($p['id'] ?? '');
    if (isset($o['produto'])) $foto = urlFotoPacote($pac['id'], (string) $o['produto']['imagem']);
    elseif ($tipo === 'foto') $foto = urlFotoPacote($pac['id'], $o['imagem']);
    else $foto = is_string($atual['imagem'] ?? null) && preg_match(RE_ARQUIVO_FOTO, $atual['imagem']) ? urlFoto($atual['imagem']) : '';
    $fotoAtual = is_string($atual['imagem'] ?? null) && preg_match(RE_ARQUIVO_FOTO, $atual['imagem']) ? urlFoto($atual['imagem']) : '';
    // A foto da troca de foto já aplicada é a de hoje: não mostra de novo como "antiga"
    if ($tipo === 'foto' && ($atual['imagem'] ?? '') === $o['imagem']) $fotoAtual = '';
    $acao = ['adicionar' => 'Cadastrar', 'substituir' => 'Trocar pelo modelo atual', 'foto' => 'Trocar a foto de', 'remover' => 'Tirar do site'][$tipo];
    [$selo, $classeSelo] = match ($st['estado']) {
        'opcional'   => ['Confira', 'selo--espera'],
        'feito'      => [$st['aplicada'] ? 'Aplicada' : 'Já está assim', 'selo--ok'],
        'impossivel' => ['Não dá para aplicar', 'selo--off'],
        default      => ['', ''],
    };
    $situacao = $st['situacao'];
    if ($st['estado'] === 'feito' && $st['aplicada']) {
        $situacao = 'Aplicada em ' . quando($st['aplicada']['quando']) . ' por ' . nomeDe($st['aplicada']['por'], $nomes) . '.';
    }
    $specs = is_array($p['especificacoes'] ?? null) && isset($o['produto']) ? array_slice($p['especificacoes'], 0, 3) : [];
    // "Ver no site": o produto novo quando já está no site; na foto nova, o próprio produto depois de trocada
    $noSite = $tipo === 'foto' ? ($st['estado'] === 'feito' ? $atual : null) : $st['novo'];
    $verNoSite = $noSite !== null && ($noSite['visivel'] ?? true) !== false ? (string) $noSite['id'] : '';
    $tituloZoom = 'Ver a foto maior (abre em outra aba)';
    ?>
        <li class="pk-item pk-item--<?= e($tipo) ?> is-<?= e($st['estado']) ?>" id="pk-<?= $i ?>">
          <label class="pk-item__chk" title="<?= $ativo ? 'Marque para aplicar' : e($selo) ?>">
            <input type="checkbox" id="<?= e($campo) ?>" name="op[]" value="<?= $i ?>" data-tipo="<?= e($tipo) ?>"<?= $st['marcado'] ? ' checked' : '' ?><?= $ativo ? '' : ' disabled' ?>>
            <span class="sr-only"><?= e($acao . ': ' . $nome) ?></span>
          </label>
          <figure class="pk-item__foto"><?php if ($foto !== ''): ?><a href="<?= e($foto) ?>" target="_blank" rel="noopener" title="<?= e($tituloZoom) ?>"><img src="<?= e($foto) ?>" alt="<?= e('Foto: ' . $nome) ?>" loading="lazy" decoding="async" width="96" height="96"><span class="pk-item__zoom" aria-hidden="true"><?= icone('search') ?></span><span class="sr-only"> (ver maior, abre em outra aba)</span></a><?php else: ?><span class="item__semfoto"><?= icone('camera') ?></span><?php endif; ?></figure>
          <div class="pk-item__txt">
            <?php if (($p['marca'] ?? '') !== '' || ($p['subcategoria'] ?? '') !== ''): ?><p class="item__marca"><?= e($p['marca'] ?? '') ?><?php if (($p['subcategoria'] ?? '') !== ''): ?><?= ($p['marca'] ?? '') !== '' ? ' · ' : '' ?><?= e($p['subcategoria']) ?><?php endif; ?></p><?php endif; ?>
            <h3 class="pk-item__nome"><label for="<?= e($campo) ?>"><?= e(($p['nome'] ?? '') !== '' ? $p['nome'] : $nome) ?></label></h3>
            <?php if ($selo !== ''): ?><p class="item__selos"><span class="selo <?= e($classeSelo) ?>"><?= e($selo) ?></span></p><?php endif; ?>
          </div>
          <div class="pk-item__det">
            <div class="pk-item__info">
            <?php if ($tipo === 'adicionar' || $tipo === 'substituir' || $tipo === 'remover'):
                $resumo = (string) ($p['resumo'] ?? '');
                if ($resumo !== ''): ?><p class="pk-item__resumo"><?= e($resumo) ?></p><?php endif;
            endif; ?>
            <?php if ($tipo === 'substituir'): ?>
            <div class="pk-troca">
              <?php if ($atual && $fotoAtual !== ''): ?><a href="<?= e($fotoAtual) ?>" target="_blank" rel="noopener" title="<?= e($tituloZoom) ?>"><img src="<?= e($fotoAtual) ?>" alt="" loading="lazy" decoding="async" width="48" height="48"><span class="sr-only">Foto do modelo antigo (abre em outra aba)</span></a><?php endif; ?>
              <p>No lugar de: <b><?= e($atual ? nomeCompleto($atual) : $nomeAntigo) ?></b><?= $atual || $st['aplicada'] ? '' : ' <span>(não está mais no catálogo)</span>' ?><?php
                if ($atual && $ativo) {
                    $herda = [];
                    if (!empty($atual['destaque'])) $herda[] = 'continua em destaque';
                    if (($atual['visivel'] ?? true) === false) $herda[] = 'continua fora do site';
                    if ($herda) echo '<span class="pk-troca__herda"> · ' . e(ucfirst(implode(' e ', $herda))) . ', como o antigo.</span>';
                } ?></p>
            </div>
            <?php elseif ($tipo === 'foto' && $st['estado'] !== 'feito'): ?>
            <div class="pk-troca">
              <?php if ($fotoAtual !== ''): ?><a href="<?= e($fotoAtual) ?>" target="_blank" rel="noopener" title="<?= e($tituloZoom) ?>"><img src="<?= e($fotoAtual) ?>" alt="" loading="lazy" decoding="async" width="48" height="48"><span class="sr-only">Foto de hoje (abre em outra aba)</span></a><?php endif; ?>
              <p><?= $atual ? '<b>Hoje no site:</b> esta foto. A nova é a outra, maior: toque nela (ou nesta) para ver em tamanho grande.' : 'Produto: <b>' . e($nomeAntigo) . '</b>' ?></p>
            </div>
            <?php endif; ?>
            <p class="pk-motivo"><b>Por quê:</b> <?= e($o['motivo']) ?></p>
            <?php if ($situacao !== ''): ?><p class="pk-situacao pk-situacao--<?= e($st['estado']) ?>"><?= e($situacao) ?><?php if ($verNoSite !== ''): ?> <a href="<?= e(cfg('url_site') . 'produtos.html#p=' . rawurlencode($verNoSite)) ?>" target="_blank" rel="noopener">Ver no site</a><?php endif; ?></p><?php endif; ?>
            <?php if ($st['aviso'] !== ''): ?><p class="pk-alerta"><?= icone('alert') ?><span><?= e($st['aviso']) ?></span></p><?php endif; ?>
            </div>
            <?php if ($specs): ?>
            <dl class="pk-specs"><?php foreach ($specs as $s): ?><div><dt><?= e((string) $s[0]) ?></dt><dd><?= e((string) $s[1]) ?></dd></div><?php endforeach; ?></dl>
            <?php endif; ?>
          </div>
        </li>
<?php
}
