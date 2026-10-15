<?php
/* =====================================================================
   Painel Balanças.com — telas da área Empresa (ajustes do site)
   Uma tela só, com uma caixa por parte (contato, endereço, horário,
   feriados, aviso, Google e redes, equipe, depoimentos, marcas), cada
   uma com o seu formulário e o seu Salvar. Tudo funciona sem
   JavaScript (linhas vazias para acrescentar, botões que só mostram a
   tela de novo); com ele (assets/painel.js), fica mais rápido: prévias
   ao vivo, acrescentar/tirar/mover linhas sem recarregar.
   Toda saída passa por e(); nada de scripts nem estilos inline.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

// Nome curto de cada parte (índice do topo) e onde ela aparece no site
const CURTO_SECOES = ['contato' => 'Contato', 'endereco' => 'Endereço', 'horario' => 'Horário', 'feriados' => 'Feriados', 'aviso' => 'Aviso',
    'google' => 'Google e redes', 'equipe' => 'Equipe', 'depoimentos' => 'Depoimentos', 'marcas' => 'Marcas', 'capa' => 'Capa do site'];
const ONDE_SECOES = [
    'contato'     => 'No rodapé de todas as páginas, na página Contato e nos botões de ligar e de WhatsApp. A busca do Google pode levar alguns dias para mostrar o número novo; o resumo do link do site compartilhado no WhatsApp ou no Facebook é um texto fixo das páginas.',
    'endereco'    => 'No rodapé de todas as páginas e na página Contato (com o botão “Abrir no mapa”). A busca do Google pode levar alguns dias para mostrar o endereço novo; o resumo do link do site compartilhado no WhatsApp ou no Facebook é um texto fixo das páginas.',
    'horario'     => 'No rodapé, na página Contato e no selo “Aberto agora / Fechado agora” das páginas.',
    'feriados'    => 'No selo “Aberto agora / Fechado agora”: nesses dias, o site mostra a loja fechada.',
    'aviso'       => 'Numa faixa no topo de todas as páginas do site, enquanto estiver ligado.',
    'google'      => 'Na faixa de confiança (página inicial e Empresa) e no rodapé (Facebook e Instagram).',
    'equipe'      => 'Na página Empresa, na parte “Equipe”.',
    'depoimentos' => 'Na página Empresa (os 3 primeiros) e na página Sistema (os 3 seguintes).',
    'marcas'      => 'Na faixa de marcas que corre na página inicial e na página Empresa.',
    'capa'        => 'No topo da página inicial: a foto grande sobre a faixa verde, com as etiquetas técnicas presas por um fio a partes do produto e a legenda com o link para a ficha dele.',
];
const BOTAO_SECOES = ['contato' => 'Salvar contato', 'endereco' => 'Salvar endereço', 'horario' => 'Salvar horário', 'feriados' => 'Salvar feriados',
    'aviso' => 'Salvar aviso', 'google' => 'Salvar Google e redes', 'equipe' => 'Salvar equipe', 'depoimentos' => 'Salvar depoimentos', 'marcas' => 'Salvar marcas',
    'capa' => 'Salvar capa'];

/* ------------------------------------------------------------------
   Pedaços dos formulários
   ------------------------------------------------------------------ */
/**
 * Um campo (rótulo, entrada, erro, ajuda). $k = chave do erro; id = emp-<seção>-<k>.
 * $o: nome, tipo (text, tel, email, date, textarea), max, linhas, modo (inputmode), auto (autocomplete),
 * exemplo (placeholder), obrig, opcional (mostra "(opcional)"), ajuda (HTML já escapado), attrs (já escapados), classe.
 */
function campoEmpresa(string $secao, string $k, string $rotulo, string $valor, array $erros, array $o = []): void
{
    $id = 'emp-' . $secao . '-' . $k;
    $desc = trim((isset($o['ajuda']) ? $id . '-ajuda' : '') . (isset($erros[$k]) ? ' erro-' . $id : ''));
    $attrs = ' id="' . e($id) . '" name="' . e($o['nome'] ?? $k) . '"'
        . (isset($o['max']) ? ' maxlength="' . (int) $o['max'] . '"' : '')
        . (isset($o['modo']) ? ' inputmode="' . e($o['modo']) . '"' : '')
        . ' autocomplete="' . e($o['auto'] ?? 'off') . '"'
        . (isset($o['exemplo']) ? ' placeholder="' . e($o['exemplo']) . '"' : '')
        . (!empty($o['obrig']) ? ' required' : '')
        . ($desc !== '' ? ' aria-describedby="' . e($desc) . '"' : '')
        . (isset($erros[$k]) ? ' aria-invalid="true"' : '')
        . ($o['attrs'] ?? '');
    ?>
        <div class="campo<?= isset($o['classe']) ? ' ' . e($o['classe']) : '' ?>">
          <label for="<?= e($id) ?>"><?= e($rotulo) ?><?php if (!empty($o['opcional'])): ?> <small>(opcional)</small><?php endif; ?></label>
          <?php if (($o['tipo'] ?? '') === 'textarea'): ?>
          <textarea<?= $attrs ?> rows="<?= (int) ($o['linhas'] ?? 3) ?>"><?= e($valor) ?></textarea>
          <?php else: ?>
          <input<?= $attrs ?> type="<?= e($o['tipo'] ?? 'text') ?>" value="<?= e($valor) ?>">
          <?php endif; ?>
          <?= erroEmpresa($secao, $k, $erros) ?>
          <?php if (isset($o['ajuda'])): ?><p class="ajuda" id="<?= e($id) ?>-ajuda"><?= $o['ajuda'] /* já escapado por quem chama */ ?></p><?php endif; ?>
        </div>
<?php
}

function erroEmpresa(string $secao, string $k, array $erros): string
{
    return isset($erros[$k]) ? '<p class="campo__erro" id="erro-emp-' . e($secao . '-' . $k) . '">' . icone('alert') . e($erros[$k]) . '</p>' : '';
}

/** Caixa de uma parte: título numerado, onde aparece no site, erros/nota e o formulário. */
function caixaEmpresa(string $secao, int $n, array $ctx, array $est, callable $miolo): void
{
    $erros = $est['erros'];
    $ajustado = false;
    foreach (CHAVES_DA_SECAO[$secao] as $k) if (array_key_exists($k, $ctx['ajustes'])) $ajustado = true;
    $link = linkSiteAjustes($secao)['link'];
    $sujo = $erros || $est['nota'] !== '';   // voltou com o que foi digitado: ainda não está salvo
    ?>
  <section class="emp-sec<?= $erros ? ' is-erro' : '' ?>" id="e-<?= e($secao) ?>" tabindex="-1" aria-labelledby="e-<?= e($secao) ?>-tit">
    <header class="emp-sec__cab">
      <p class="emp-sec__n" aria-hidden="true"><?= sprintf('%02d', $n) ?></p>
      <div class="emp-sec__txt">
        <h2 class="emp-sec__tit" id="e-<?= e($secao) ?>-tit"><?= e(SECOES_AJUSTES[$secao]) ?></h2>
        <p class="emp-sec__onde"><?= e(ONDE_SECOES[$secao]) ?> <a href="<?= e($link) ?>" target="_blank" rel="noopener"><span>Ver no site</span><?= icone('ext') ?></a></p>
        <p class="emp-sec__selos"><?php if ($ajustado): ?><span class="selo selo--ajustado"><?= $secao === 'capa' ? 'Escolhida pelo painel' : 'Mudado pelo painel' ?></span><?php else: ?><span class="selo"><?= $secao === 'capa' ? 'Capa original (' . e(NOME_CAPA_ORIGINAL) . ')' : 'Dados originais do site' ?></span><?php endif; ?><span class="selo selo--sujo" data-emp-sujo hidden>Não salvo</span></p>
      </div>
    </header>
    <?php if ($erros): ?>
    <div class="aviso aviso--erro" role="alert" tabindex="-1" data-emp-erros>
      <?= icone('alert') ?>
      <div>
        <p><b>“<?= e(SECOES_AJUSTES[$secao]) ?>” não foi salvo.</b> Confira:</p>
        <ul><?php foreach ($erros as $k => $m): ?><li><a href="#emp-<?= e($secao . '-' . $k) ?>"><?= e($m) ?></a></li><?php endforeach; ?></ul>
      </div>
    </div>
    <?php elseif ($est['nota'] !== ''): ?>
    <div class="aviso aviso--info" role="status" tabindex="-1" data-emp-erros><?= icone('alert') ?><p><?= e($est['nota']) ?></p></div>
    <?php endif; ?>
    <form class="emp-form" method="post" action="<?= e(link_('empresa-salvar', ['secao' => $secao]) . '#e-' . $secao) ?>" data-emp-form="<?= e($secao) ?>"<?= $sujo ? ' data-emp-voltou' : '' ?>>
      <?= campoCsrf() ?>
      <?php /* Enter num campo "clica" no primeiro botão de envio: este (Salvar), nunca ↑ ↓, Repetir ou as sugestões */ ?>
      <button type="submit" class="emp-padrao" tabindex="-1" aria-hidden="true">Salvar</button>
      <?php $miolo($est['v'], $erros); ?>
      <div class="emp-salvar">
        <button class="btn btn--verde" type="submit" data-salvar><?= icone('check') ?><span><?= e(BOTAO_SECOES[$secao]) ?></span></button>
      </div>
    </form>
  </section>
<?php
}

/** Botões ↑ ↓ (sem JavaScript, mostram a tela de novo com a ordem trocada) e Remover de uma linha de lista. */
function botoesLinha(string $lista, string $chave, string $rotulo, bool $mover): void
{
    if ($mover): ?>
            <button type="submit" class="ic" name="mover" value="<?= e($chave) ?>:cima" formnovalidate data-emp-mover="cima" aria-label="Subir <?= e($rotulo) ?>" title="Subir"><?= icone('up') ?></button>
            <button type="submit" class="ic" name="mover" value="<?= e($chave) ?>:baixo" formnovalidate data-emp-mover="baixo" aria-label="Descer <?= e($rotulo) ?>" title="Descer"><?= icone('down') ?></button>
    <?php endif; ?>
            <label class="emp-rm" data-emp-rm-sem-js><input type="checkbox" name="<?= e($lista . '[' . $chave . '][remover]') ?>" value="1"><span>Remover</span></label>
            <button type="button" class="ic ic--perigo" data-emp-rm hidden aria-label="Remover <?= e($rotulo) ?>" title="Remover"><?= icone('trash') ?></button>
<?php
}

/* ------------------------------------------------------------------
   As nove partes
   ------------------------------------------------------------------ */
function partesContato(array $v, array $erros): void
{
    $wa = preg_replace('/\D+/', '', $v['whatsapp']) ?? '';
    if (strlen($wa) === 10 || strlen($wa) === 11) $wa = '55' . $wa;
    ?>
      <div class="campos-2">
        <?php campoEmpresa('contato', 'telefone', 'Telefone', $v['telefone'], $erros, ['tipo' => 'tel', 'max' => 30, 'modo' => 'tel', 'exemplo' => '(19) 3023-9050', 'obrig' => true,
            'ajuda' => 'Com o DDD. Aparece no site e no botão <b>Ligar</b>.']); ?>
        <?php campoEmpresa('contato', 'whatsapp', 'WhatsApp', $v['whatsapp'], $erros, ['tipo' => 'tel', 'max' => 30, 'modo' => 'tel', 'exemplo' => '(19) 99999-9999', 'obrig' => true,
            'ajuda' => 'Com o DDD; o 55 do Brasil o painel põe sozinho.'
                . (preg_match('/^55\d{10,11}$/', $wa) ? ' <a href="https://wa.me/' . e($wa) . '" target="_blank" rel="noopener" data-emp-testar-wa>Testar este número no WhatsApp</a>' : '')]); ?>
      </div>
      <?php campoEmpresa('contato', 'mensagem', 'Mensagem pronta do WhatsApp', $v['mensagem'], $erros, ['tipo' => 'textarea', 'linhas' => 2, 'max' => LIMITES_AJUSTES['mensagem'], 'obrig' => true,
          'attrs' => ' data-emp-contador', 'ajuda' => 'Já vem escrita quando a pessoa toca no botão do WhatsApp do site. Ela pode mudar antes de enviar.']); ?>
      <?php campoEmpresa('contato', 'email', 'E-mail', $v['email'], $erros, ['tipo' => 'email', 'max' => LIMITES_AJUSTES['email'], 'modo' => 'email', 'exemplo' => 'balancas.com@gmail.com', 'obrig' => true]); ?>
<?php
}

function partesEndereco(array $v, array $erros): void
{
    ?>
      <?php campoEmpresa('endereco', 'rua', 'Rua e número', $v['rua'], $erros, ['max' => LIMITES_AJUSTES['rua'], 'exemplo' => 'Rua 13, 650', 'obrig' => true, 'auto' => 'off']); ?>
      <?php campoEmpresa('endereco', 'referencia', 'Ponto de referência', $v['referencia'], $erros, ['max' => LIMITES_AJUSTES['referencia'], 'exemplo' => 'Entre as Avenidas 9 e 11', 'opcional' => true]); ?>
      <div class="campos-2">
        <?php campoEmpresa('endereco', 'bairro', 'Bairro', $v['bairro'], $erros, ['max' => LIMITES_AJUSTES['bairro'], 'exemplo' => 'Boa Morte', 'obrig' => true]); ?>
        <?php campoEmpresa('endereco', 'cidade', 'Cidade', $v['cidade'], $erros, ['max' => LIMITES_AJUSTES['cidade'], 'exemplo' => 'Rio Claro', 'obrig' => true]); ?>
      </div>
      <div class="campos-2 emp-uf-cep">
        <div class="campo">
          <label for="emp-endereco-uf">Estado</label>
          <select id="emp-endereco-uf" name="uf"<?= isset($erros['uf']) ? ' aria-invalid="true" aria-describedby="erro-emp-endereco-uf"' : '' ?>>
            <?php $ufs = UFS; if (!in_array($v['uf'], $ufs, true)) array_unshift($ufs, $v['uf']);
            foreach ($ufs as $uf): ?><option value="<?= e($uf) ?>"<?= $uf === $v['uf'] ? ' selected' : '' ?>><?= e($uf === '' ? 'Escolha' : $uf) ?></option><?php endforeach; ?>
          </select>
          <?= erroEmpresa('endereco', 'uf', $erros) ?>
        </div>
        <?php campoEmpresa('endereco', 'cep', 'CEP', $v['cep'], $erros, ['max' => 10, 'modo' => 'numeric', 'exemplo' => '13500-120', 'obrig' => true]); ?>
      </div>
      <?php campoEmpresa('endereco', 'mapa', 'Link do mapa (Google Maps)', $v['mapa'], $erros, ['max' => LIMITES_AJUSTES['url'], 'modo' => 'url', 'exemplo' => 'https://maps.app.goo.gl/…', 'obrig' => true,
          'ajuda' => 'É para onde vai o botão <b>Abrir no mapa</b>. No Google Maps, abra a loja, toque em <b>Compartilhar</b> → <b>Copiar link</b> e cole aqui.'
              . ($v['mapa'] !== '' && preg_match('~^https://~', $v['mapa']) ? ' <a href="' . e($v['mapa']) . '" target="_blank" rel="noopener">Abrir o link atual</a>' : '')]); ?>
<?php
}

function partesHorario(array $v, array $erros): void
{
    [$grade, $errosGrade] = gradeDosDias($v['dias']);
    $previa = $errosGrade ? null : linhasHorario($grade);
    ?>
      <p class="ajuda ajuda--topo">Até 2 horários por dia (ex.: manhã e tarde). Nos feriados (parte 4), o site mostra a loja fechada.</p>
      <div class="emp-dias">
      <?php foreach (ORDEM_DIAS as $d):
          $dia = $v['dias'][$d];
          $nome = ucfirst(NOMES_DIAS[$d]);
          $int = $dia['int'];
          $vagas = max(2, count($int));
          $erro = $erros['h-' . $d] ?? null; ?>
        <div class="emp-dia<?= $dia['fechado'] ? ' is-fechado' : '' ?><?= $erro ? ' is-erro' : '' ?>" id="emp-horario-h-<?= $d ?>" role="group" aria-labelledby="emp-dia-<?= $d ?>" data-emp-dia="<?= $d ?>"<?= $erro ? ' aria-describedby="erro-emp-horario-h-' . $d . '"' : '' ?>>
          <p class="emp-dia__nome" id="emp-dia-<?= $d ?>"><?= e($nome) ?></p>
          <label class="emp-dia__fechado"><input type="checkbox" name="h[<?= $d ?>][fechado]" value="1"<?= $dia['fechado'] ? ' checked' : '' ?> data-emp-fechado><span>Fechado</span></label>
          <div class="emp-dia__int" data-emp-int>
            <?php for ($i = 0; $i < $vagas; $i++): $iv = $int[$i] ?? ['', '']; ?>
            <p class="emp-iv">
              <span class="emp-iv__par"><span class="emp-iv__rot" aria-hidden="true"><?= $i === 0 ? 'das' : 'e das' ?></span>
              <input type="time" name="h[<?= $d ?>][a][<?= $i ?>]" value="<?= e($iv[0]) ?>" step="300" aria-label="<?= e($nome . ', ' . ($i + 1) . 'º horário, começa às') ?>"></span>
              <span class="emp-iv__par"><span class="emp-iv__rot" aria-hidden="true">às</span>
              <input type="time" name="h[<?= $d ?>][b][<?= $i ?>]" value="<?= e($iv[1]) ?>" step="300" aria-label="<?= e($nome . ', ' . ($i + 1) . 'º horário, termina às') ?>"></span>
            </p>
            <?php endfor; ?>
          </div>
          <?= erroEmpresa('horario', 'h-' . $d, $erros) ?>
        </div>
        <?php if ($d === 1): ?>
        <p class="emp-repetir"><button type="submit" class="btn btn--linha btn--p" name="repetir" value="1" formnovalidate data-emp-repetir><?= icone('down') ?>Repetir o horário de segunda de terça a sexta</button></p>
        <?php endif; ?>
      <?php endforeach; ?>
      </div>
      <div class="emp-previa" aria-live="polite">
        <p class="emp-previa__rot">Como fica no site</p>
        <ul class="emp-horas" role="list" data-emp-previa-horas>
          <?php foreach ($previa ?? [] as $l): ?><li><span><?= e($l['dias']) ?></span><span><?= e($l['horas']) ?></span></li><?php endforeach; ?>
        </ul>
        <p class="ajuda" data-emp-previa-erro<?= $previa === null ? '' : ' hidden' ?>>Corrija o horário marcado em vermelho para ver a prévia.</p>
      </div>
<?php
}

function partesFeriados(array $v, array $erros): void
{
    $mun = $v['mun'];
    $dias = $v['dias'];
    $temMun = static function (string $md) use ($mun): bool {
        foreach ($mun as $l) if (diaMesDoForm($l['data']) === $md) return true;
        return false;
    };
    ?>
      <details class="emp-ja">
        <summary>Feriados que o site já conhece (não precisa cadastrar)</summary>
        <p class="ajuda"><?= e(implode(' · ', FERIADOS_DO_SITE)) ?>.</p>
      </details>

      <fieldset class="emp-grupo" id="emp-feriados-mun"<?= isset($erros['mun']) ? ' aria-describedby="erro-emp-feriados-mun"' : '' ?>>
        <legend class="emp-grupo__tit">Feriados da cidade <small>(todo ano)</small></legend>
        <?= erroEmpresa('feriados', 'mun', $erros) ?>
        <ol class="emp-linhas" role="list" data-emp-lista="mun" data-emp-rotulo="Feriado">
          <?php foreach ($mun as $i => $l) linhaFeriado('mun', (string) $i, $l, $erros, false); ?>
          <?php for ($j = 0; $j < 2; $j++) linhaFeriado('mun', (string) (count($mun) + $j), ['data' => '', 'nome' => ''], [], true); ?>
        </ol>
        <template data-emp-modelo><?php linhaFeriado('mun', '__K__', ['data' => '', 'nome' => ''], [], false); ?></template>
        <p class="emp-mais" hidden><button type="button" class="btn btn--linha btn--p" data-emp-adicionar="mun"><?= icone('plus') ?>Acrescentar feriado</button></p>
      </fieldset>

      <div class="emp-sug">
        <p class="emp-sug__tit">Sugestões para Rio Claro</p>
        <p class="ajuda">Confira no calendário oficial da Prefeitura antes de usar: nada aqui vale até você colocar na lista e salvar.</p>
        <ul class="emp-sug__lista" role="list">
          <?php foreach (SUGESTOES_RIO_CLARO as $k => $s):
              $ja = isset($s['movel']) ? in_array($s['movel'], $v['moveis'], true) : $temMun($s['data']); ?>
          <li class="emp-sug__item">
            <p><b><?= e(isset($s['data']) ? diaMesTela($s['data']) . ' · ' . $s['nome'] : $s['nome']) ?></b><span><?= e($s['nota']) ?></span></p>
            <button type="submit" class="btn btn--linha btn--p" name="sugerir" value="<?= e($k) ?>" formnovalidate data-emp-sugerir="<?= e($k) ?>"
              <?= isset($s['movel']) ? 'data-movel="' . (int) $s['movel'] . '"' : 'data-data="' . e(diaMesTela($s['data'])) . '" data-nome="' . e($s['nome']) . '"' ?><?= $ja ? ' disabled' : '' ?>><?= icone($ja ? 'check' : 'plus') ?><span><?= $ja ? 'Já está na lista' : 'Colocar na lista' ?></span></button>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <fieldset class="emp-grupo" id="emp-feriados-moveis">
        <legend class="emp-grupo__tit">Datas que mudam todo ano</legend>
        <div class="emp-moveis">
          <?php foreach (FERIADOS_MOVEIS as $n => $nome): ?>
          <label class="emp-check"><input type="checkbox" name="moveis[]" value="<?= (int) $n ?>"<?= in_array($n, $v['moveis'], true) ? ' checked' : '' ?> data-emp-movel="<?= (int) $n ?>"><span><?= e($nome) ?></span></label>
          <?php endforeach; ?>
        </div>
        <p class="ajuda">Carnaval e Quarta-feira de Cinzas são ponto facultativo: as repartições fecham, mas o comércio costuma abrir. Marque só se a loja fechar. O site calcula as datas de cada ano sozinho.</p>
      </fieldset>

      <fieldset class="emp-grupo" id="emp-feriados-dia"<?= isset($erros['dia']) ? ' aria-describedby="erro-emp-feriados-dia"' : '' ?>>
        <legend class="emp-grupo__tit">Dias fechados <small>(só uma vez)</small></legend>
        <p class="ajuda ajuda--topo">Ponte de feriado, recesso de fim de ano, inventário… Depois de <?= DIAS_GUARDAR_DATAS ?> dias, a data sai da lista sozinha.</p>
        <?= erroEmpresa('feriados', 'dia', $erros) ?>
        <ol class="emp-linhas" role="list" data-emp-lista="dia" data-emp-rotulo="Dia fechado">
          <?php foreach ($dias as $i => $l) linhaFeriado('dia', (string) $i, $l, $erros, false); ?>
          <?php for ($j = 0; $j < 2; $j++) linhaFeriado('dia', (string) (count($dias) + $j), ['data' => '', 'nome' => ''], [], true); ?>
        </ol>
        <template data-emp-modelo><?php linhaFeriado('dia', '__K__', ['data' => '', 'nome' => ''], [], false); ?></template>
        <p class="emp-mais" hidden><button type="button" class="btn btn--linha btn--p" data-emp-adicionar="dia"><?= icone('plus') ?>Acrescentar dia fechado</button></p>
      </fieldset>
<?php
}

/** Linha de feriado da cidade (mun: dia/mês) ou de dia fechado (dia: data completa). */
function linhaFeriado(string $lista, string $k, array $l, array $erros, bool $extra): void
{
    $mun = $lista === 'mun';
    $base = 'emp-feriados-' . $lista . '-' . $k;
    $rot = ($mun ? 'Feriado ' : 'Dia fechado ') . (ctype_digit($k) ? (int) $k + 1 : '');
    $eData = $erros[$lista . '-' . $k . '-data'] ?? null;
    $eNome = $erros[$lista . '-' . $k . '-nome'] ?? null;
    ?>
          <li class="emp-fer<?= $mun ? '' : ' emp-fer--dia' ?><?= $extra ? ' is-extra' : '' ?>" data-emp-item<?= $extra ? ' data-emp-extra' : '' ?>>
            <div class="emp-fer__data">
              <label class="sr-only" for="<?= e($base) ?>-data"><?= e($rot . ($mun ? ', dia e mês' : ', data')) ?></label>
              <input id="<?= e($base) ?>-data" name="<?= e($lista . '[' . $k . '][data]') ?>" value="<?= e($l['data']) ?>"
                <?= $mun ? 'type="text" inputmode="numeric" maxlength="5" placeholder="dd/mm"' : 'type="date"' ?> autocomplete="off"<?= $eData ? ' aria-invalid="true" aria-describedby="erro-emp-feriados-' . e($lista . '-' . $k) . '-data"' : '' ?>>
            </div>
            <div class="emp-fer__nome">
              <label class="sr-only" for="<?= e($base) ?>-nome"><?= e($rot . ($mun ? ', nome' : ', motivo')) ?></label>
              <input id="<?= e($base) ?>-nome" name="<?= e($lista . '[' . $k . '][nome]') ?>" value="<?= e($l['nome']) ?>" maxlength="<?= LIMITES_AJUSTES['feriado_nome'] ?>"
                placeholder="<?= $mun ? 'Ex.: Aniversário da cidade' : 'Ex.: Recesso de fim de ano' ?>" autocomplete="off"<?= $eNome ? ' aria-invalid="true" aria-describedby="erro-emp-feriados-' . e($lista . '-' . $k) . '-nome"' : '' ?>>
            </div>
            <div class="emp-fer__bts"><?php if (!$extra) botoesLinha($lista, $k, $rot, false); ?></div>
            <?= $eData ? erroEmpresa('feriados', $lista . '-' . $k . '-data', $erros) : '' ?>
            <?= $eNome ? erroEmpresa('feriados', $lista . '-' . $k . '-nome', $erros) : '' ?>
          </li>
<?php
}

function partesAviso(array $v, array $erros): void
{
    $texto = trim($v['texto']);
    ?>
      <label class="chave" for="emp-aviso-ativo">
        <input id="emp-aviso-ativo" type="checkbox" name="ativo" value="1"<?= $v['ativo'] ? ' checked' : '' ?> data-emp-aviso-ativo>
        <span class="chave__trilho" aria-hidden="true"></span>
        <span class="chave__txt"><b>Mostrar o aviso no site</b><small>Desligado, o aviso fica guardado aqui e some do site.</small></span>
      </label>
      <?php campoEmpresa('aviso', 'texto', 'Texto do aviso', $v['texto'], $erros, ['tipo' => 'textarea', 'linhas' => 2, 'max' => LIMITES_AJUSTES['aviso'],
          'exemplo' => 'Ex.: Fechados de 24/12 a 01/01 para férias coletivas. Voltamos dia 02/01!', 'attrs' => ' data-emp-contador',
          'ajuda' => 'Curto e direto: de ' . LIMITES_AJUSTES['aviso_min'] . ' a ' . LIMITES_AJUSTES['aviso'] . ' caracteres, sem links.']); ?>
      <fieldset class="campo">
        <legend class="rotulo">Cor do aviso</legend>
        <div class="opcoes">
          <label class="opcao"><input type="radio" name="tom" value="info"<?= $v['tom'] !== 'alerta' ? ' checked' : '' ?> data-emp-aviso-tom><span>Verde · novidade</span></label>
          <label class="opcao"><input type="radio" name="tom" value="alerta"<?= $v['tom'] === 'alerta' ? ' checked' : '' ?> data-emp-aviso-tom><span>Amarelo · atenção</span></label>
        </div>
      </fieldset>
      <div class="campos-2">
        <?php campoEmpresa('aviso', 'inicio', 'Começa em', $v['inicio'], $erros, ['tipo' => 'date', 'opcional' => true, 'ajuda' => 'Vazio: já começa.']); ?>
        <?php campoEmpresa('aviso', 'fim', 'Termina em', $v['fim'], $erros, ['tipo' => 'date', 'opcional' => true, 'ajuda' => 'Vazio: fica até você desligar. O último dia ainda mostra o aviso.']); ?>
      </div>
      <div class="emp-previa">
        <p class="emp-previa__rot">Como fica no site</p>
        <div class="emp-faixa<?= $v['tom'] === 'alerta' ? ' emp-faixa--alerta' : '' ?><?= $v['ativo'] ? '' : ' is-desligado' ?>" data-emp-previa-aviso>
          <span class="emp-faixa__rot">Aviso</span>
          <p class="emp-faixa__txt" data-emp-previa-texto><?= e($texto !== '' ? $texto : 'Escreva o texto do aviso.') ?></p>
          <span class="emp-faixa__x" aria-hidden="true"><?= icone('x') ?></span>
        </div>
        <p class="emp-aviso-estado" data-emp-aviso-estado><?= e(estadoAviso($v)) ?></p>
      </div>
<?php
}

/** Frase do estado do aviso: aparece agora, a partir de, já terminou, desligado. */
function estadoAviso(array $v): string
{
    if (!$v['ativo']) return 'Desligado: não aparece no site.';
    $hoje = hojeIso();
    $ini = dataValida($v['inicio']) ? $v['inicio'] : '';
    $fim = dataValida($v['fim']) ? $v['fim'] : '';
    if ($fim !== '' && $fim < $hoje) return 'Já terminou em ' . dataBr($fim) . ': não aparece mais no site.';
    if ($ini !== '' && $ini > $hoje) return 'Aparece no site de ' . dataBr($ini) . ($fim !== '' ? ' a ' . dataBr($fim) : ' em diante') . '.';
    return 'Aparece no site agora' . ($fim !== '' ? ', até ' . dataBr($fim) : '') . '.';
}

function partesGoogle(array $v, array $erros): void
{
    ?>
      <div class="campos-2 emp-nota">
        <?php campoEmpresa('google', 'nota', 'Nota no Google', $v['nota'], $erros, ['max' => 4, 'modo' => 'decimal', 'exemplo' => '5,0', 'obrig' => true, 'ajuda' => 'De 1,0 a 5,0.']); ?>
        <?php campoEmpresa('google', 'avaliacoes', 'Número de avaliações', $v['avaliacoes'], $erros, ['max' => 7, 'modo' => 'numeric', 'exemplo' => '56', 'obrig' => true, 'ajuda' => 'O número que aparece no Google.']); ?>
      </div>
      <?php campoEmpresa('google', 'link', 'Link das avaliações', $v['link'], $erros, ['max' => LIMITES_AJUSTES['url'], 'modo' => 'url', 'obrig' => true, 'exemplo' => 'https://maps.app.goo.gl/…',
          'ajuda' => 'Para onde vai quem toca na nota. O link da loja no Google Maps serve.']); ?>
      <?php campoEmpresa('google', 'facebook', 'Facebook', $v['facebook'], $erros, ['max' => LIMITES_AJUSTES['url'], 'modo' => 'url', 'opcional' => true, 'exemplo' => 'https://www.facebook.com/…',
          'ajuda' => 'Vazio: o link do Facebook some do site.']); ?>
      <?php campoEmpresa('google', 'instagram', 'Instagram', $v['instagram'], $erros, ['max' => LIMITES_AJUSTES['url'], 'modo' => 'url', 'opcional' => true, 'exemplo' => '@perfil ou https://www.instagram.com/…',
          'ajuda' => 'Pode ser só <b>@perfil</b>. Vazio: o link do Instagram não aparece no site.']); ?>
<?php
}

/** Equipe e depoimentos: uma caixinha por item, com ↑ ↓ e Remover; 2 linhas vazias no fim para acrescentar. */
function partesLista(string $secao, array $v, array $erros): void
{
    $itens = $v['itens'];
    $rotulo = $secao === 'equipe' ? 'Pessoa' : 'Depoimento';
    $max = LIMITES_AJUSTES[$secao];
    ?>
      <p class="ajuda ajuda--topo"><?= $secao === 'equipe' ? 'A ordem daqui é a ordem no site. Até ' . $max . ' pessoas.' : 'A ordem daqui é a ordem no site. Use depoimentos reais, com a autorização do cliente. Até ' . $max . '.' ?></p>
      <?= erroEmpresa($secao, $secao, $erros) ?>
      <ol class="emp-itens" role="list" id="emp-<?= e($secao . '-' . $secao) ?>" data-emp-lista="<?= e($secao) ?>" data-emp-rotulo="<?= e($rotulo) ?>" data-emp-max="<?= $max ?>">
        <?php foreach ($itens as $i => $l) itemLista($secao, (string) $i, $l, $erros, false, $i === 0, $i === count($itens) - 1); ?>
        <?php for ($j = 0; $j < 2; $j++) itemLista($secao, (string) (count($itens) + $j), array_fill_keys(array_keys(CAMPOS_LISTA[$secao]), ''), [], true, false, false); ?>
      </ol>
      <template data-emp-modelo><?php itemLista($secao, '__K__', array_fill_keys(array_keys(CAMPOS_LISTA[$secao]), ''), [], false, false, false); ?></template>
      <p class="emp-mais" hidden><button type="button" class="btn btn--linha btn--p" data-emp-adicionar="<?= e($secao) ?>"><?= icone('plus') ?><?= $secao === 'equipe' ? 'Adicionar pessoa' : 'Adicionar depoimento' ?></button></p>
<?php
}

function itemLista(string $secao, string $k, array $l, array $erros, bool $extra, bool $primeiro, bool $ultimo): void
{
    $num = ctype_digit($k) ? (int) $k + 1 : 0;
    $rot = ($secao === 'equipe' ? 'Pessoa ' : 'Depoimento ') . ($num ?: '');
    $campos = CAMPOS_LISTA[$secao];
    ?>
        <li class="emp-item<?= $extra ? ' is-extra' : '' ?>" data-emp-item<?= $extra ? ' data-emp-extra' : '' ?>>
          <div class="emp-item__cab">
            <p class="emp-item__n" data-emp-n><?= $extra ? ($secao === 'equipe' ? 'Nova pessoa' : 'Novo depoimento') . ' <small>(opcional)</small>' : e($rot) ?></p>
            <div class="emp-item__bts"><?php if (!$extra) botoesLinha($secao, $k, $rot, true); ?></div>
          </div>
          <?php if ($secao === 'equipe'): ?>
          <div class="campos-2">
            <?php campoItem($secao, $k, 'nome', $l, $erros, ['exemplo' => 'Ex.: Fábio Godoy']); ?>
            <?php campoItem($secao, $k, 'cargo', $l, $erros, ['exemplo' => 'Ex.: Técnico de balanças']); ?>
          </div>
          <?php campoItem($secao, $k, 'texto', $l, $erros, ['tipo' => 'textarea', 'linhas' => 3, 'opcional' => true]); ?>
          <?php else: ?>
          <?php campoItem($secao, $k, 'texto', $l, $erros, ['tipo' => 'textarea', 'linhas' => 4]); ?>
          <div class="campos-2">
            <?php campoItem($secao, $k, 'nome', $l, $erros, ['exemplo' => 'Ex.: Juliana']); ?>
            <?php campoItem($secao, $k, 'empresa', $l, $erros, ['exemplo' => 'Ex.: Padaria Phenix', 'opcional' => true]); ?>
          </div>
          <?php endif; ?>
        </li>
<?php
}

function campoItem(string $secao, string $k, string $c, array $l, array $erros, array $o): void
{
    [$rotulo, $max] = CAMPOS_LISTA[$secao][$c];
    campoEmpresa($secao, $secao . '-' . $k . '-' . $c, $rotulo, (string) ($l[$c] ?? ''), $erros, $o + [
        'nome' => $secao . '[' . $k . '][' . $c . ']', 'max' => $max, 'attrs' => ($o['tipo'] ?? '') === 'textarea' ? ' data-emp-contador' : '',
    ]);
}

function partesMarcas(array $v, array $erros): void
{
    $n = count(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', $v['texto']) ?: [])));
    campoEmpresa('marcas', 'marcas', 'Marcas, uma por linha', $v['texto'], $erros, ['tipo' => 'textarea', 'linhas' => 10, 'max' => 4000, 'classe' => 'emp-marcas',
        'attrs' => ' spellcheck="false"',
        'ajuda' => 'Na ordem da faixa do site. Até ' . LIMITES_AJUSTES['marcas'] . ' marcas, de até ' . LIMITES_AJUSTES['marca'] . ' letras cada. <b data-emp-marcas-n>' . $n . '</b> ' . ($n === 1 ? 'marca' : 'marcas') . ' agora.']);
}

/* ------------------------------------------------------------------
   Capa do site: produto, 4 etiquetas com ponto (toque na foto),
   legenda e prévias (computador e celular). Sem JavaScript: os pontos
   vão pelos campos de posição e a prévia usa as classes cx-N/cy-N de
   assets/painel.css (nada de style=""); com assets/painel.js: troca de
   produto na hora, toque/arraste na foto, setas do teclado.
   ------------------------------------------------------------------ */
// Em cima ou embaixo da foto, como no site (css/home-v3.css): no computador 1 e 3 em cima, 2 e 4 embaixo;
// no celular aparecem só a 1 (em cima) e a 3 (embaixo)
const CAPA_EMBAIXO = ['pc' => [1, 3], 'cel' => [2]];
const CAPA_NO_CELULAR = [0, 2];

/** Classes de cada ponto na prévia ($tela = pc|cel): posição, em cima/embaixo e para que lado a etiqueta cresce. */
function classesPontosCapa(array $pontos, string $tela): array
{
    $baixo = CAPA_EMBAIXO[$tela];
    $visiveis = [];
    foreach ($pontos as $i => $pt) {
        if ($tela === 'cel' && !in_array($i, CAPA_NO_CELULAR, true)) continue;
        if (trim($pt['rotulo'] . $pt['valor'] . $pt['detalhe']) === '') continue;
        $visiveis[$i] = $pt;
    }
    $lado = [];
    foreach ([false, true] as $emBaixo) {
        $fila = array_filter($visiveis, static fn(int $i): bool => in_array($i, $baixo, true) === $emBaixo, ARRAY_FILTER_USE_KEY);
        uasort($fila, static fn(array $a, array $b): int => (float) $a['x'] <=> (float) $b['x']);
        $k = array_keys($fila);
        // Duas na mesma fila: a da esquerda cresce para a esquerda, a da direita para a direita; sozinha: para o lado com mais espaço
        if (count($k) === 1) $lado[$k[0]] = (float) $fila[$k[0]]['x'] < 50 ? 'd' : 'e';
        else foreach ($k as $j => $i) $lado[$i] = $j === 0 ? 'e' : 'd';
    }
    $out = [];
    foreach ($pontos as $i => $pt) {
        $x = is_numeric(str_replace(',', '.', $pt['x'])) ? (int) round(max(0, min(100, (float) str_replace(',', '.', $pt['x'])))) : 50;
        $y = is_numeric(str_replace(',', '.', $pt['y'])) ? (int) round(max(0, min(100, (float) str_replace(',', '.', $pt['y'])))) : 50;
        $out[$i] = 'capa-pt cx-' . $x . ' cy-' . $y . (in_array($i, $baixo, true) ? ' capa-pt--dn' : '') . ' capa-pt--' . ($lado[$i] ?? 'e')
            . (isset($visiveis[$i]) ? '' : ' is-vazio');
    }
    return $out;
}

/** Uma tela de prévia (computador ou celular) da capa. */
function previaCapa(string $tela, ?array $item, array $v): void
{
    $classes = classesPontosCapa($v['pontos'], $tela);
    $cel = $tela === 'cel';
    $marca = $item['marca'] ?? '';
    $modelo = $item['modelo'] ?? '';
    ?>
          <div class="capa-escala capa-escala--<?= e($tela) ?>" data-capa-escala="<?= $cel ? 292 : 560 ?>">
          <div class="capa-tela capa-tela--<?= e($tela) ?>" data-capa-tela="<?= e($tela) ?>">
            <span class="capa-tela__faixa" aria-hidden="true"></span>
            <!-- As duas prévias aceitam o toque para marcar o ponto (os mesmos % da foto); no celular a do celular é a maior -->
            <div class="capa-foto" data-capa-alvo="<?= e($tela) ?>">
              <img src="<?= e($item['url'] ?? '') ?>" width="<?= (int) ($item['largura'] ?? 0) ?: 900 ?>" height="<?= (int) ($item['altura'] ?? 0) ?: 600 ?>" alt="" data-capa-img draggable="false">
              <ol class="capa-pontos" role="list" aria-hidden="true">
                <?php foreach ($v['pontos'] as $i => $pt): ?>
                <li class="<?= e($classes[$i]) ?>" data-capa-pt="<?= $i ?>"><span class="capa-pt__caixa"><span class="capa-pt__k"><b><?= sprintf('%02d', $i + 1) ?></b> <span data-capa-txt="rotulo"><?= e($pt['rotulo']) ?></span></span><span class="capa-pt__v" data-capa-txt="valor"><?= e($pt['valor']) ?></span><?php if (!$cel): ?><span class="capa-pt__s" data-capa-txt="detalhe"<?= trim($pt['detalhe']) === '' ? ' hidden' : '' ?>><?= e($pt['detalhe']) ?></span><?php endif; ?></span></li>
                <?php endforeach; ?>
              </ol>
            </div>
            <p class="capa-tela__legenda"><span data-capa-leg="marca"><?= e($marca) ?></span> <span data-capa-leg="modelo"><?= e($modelo) ?></span><?php if (!$cel): ?><span class="capa-tela__legx" data-capa-leg="texto"<?= trim($v['texto']) === '' ? ' hidden' : '' ?>> — <?= e(trim($v['texto'])) ?></span><?php endif; ?></p>
          </div>
          </div>
<?php
}

function partesCapa(array $v, array $erros, array $ctx): void
{
    $info = infoCapa();
    if ($info['erro'] !== null): ?>
      <div class="aviso aviso--erro" role="alert"><?= icone('alert') ?><p><b>Não consegui ler o catálogo de produtos</b> (<?= e($info['erro']) ?>). A capa do site continua como está; resolva o catálogo em Produtos e volte aqui.</p></div>
<?php
        return;
    endif;
    $itens = $info['produtos'];
    $salva = $ctx['atual']['capa'];
    // Capa escolhida que não pode aparecer, ou a capa original (a Prix 7E) fora do site (o topo mostraria o link quebrado)
    $problema = problemaCapa($info['lista'], $salva !== null ? (string) ($salva['produto'] ?? '') : null);
    $origFora = motivoOriginalForaDoSite($info['lista']);
    // Produto escolhido que saiu do catálogo: a tela recomeça da capa original (o aviso explica)
    if (!isset($itens[$v['produto']]) && !$erros) $v = capaParaTela(CAPA_ORIGINAL);
    $item = $itens[$v['produto']] ?? null;
    $podem = array_filter($itens, static fn(array $x): bool => $x['ok']);
    $nomesCat = [];
    foreach ($info['categorias'] as $c) if (is_array($c) && is_string($c['id'] ?? null)) $nomesCat[$c['id']] = (string) ($c['nome'] ?? $c['id']);
    $grupos = [];
    foreach ($itens as $id => $x) if ($x['ok'] || $id === $v['produto']) $grupos[$x['categoria']][] = $x;
    ?>
      <?php if ($problema !== null): ?>
      <div class="aviso aviso--erro capa-problema" role="alert"><?= icone('alert') ?><p><?= e($problema) ?></p></div>
      <?php endif; ?>
      <p class="ajuda ajuda--topo">Escolha o produto da foto grande e escreva até 4 etiquetas. Cada etiqueta tem um ponto preso por um fio a uma parte do produto: escolha a etiqueta e toque na foto do produto (em qualquer uma das duas prévias), no lugar de que ela fala.</p>

      <div class="capa" data-capa-parte>
        <div class="capa__produto">
          <div class="campo">
            <label for="emp-capa-produto">Produto da capa</label>
            <div class="capa__escolha">
              <select id="emp-capa-produto" name="produto" data-capa-produto aria-describedby="emp-capa-produto-ajuda<?= isset($erros['produto']) ? ' erro-emp-capa-produto' : '' ?>"<?= isset($erros['produto']) ? ' aria-invalid="true"' : '' ?>>
                <?php foreach ($grupos as $cat => $lista): ?>
                <optgroup label="<?= e($nomesCat[$cat] ?? ($cat !== '' ? $cat : 'Outros')) ?>">
                  <?php foreach ($lista as $x):
                      $orig = $x['id'] === CAPA_ORIGINAL['produto'];
                      $sug = $orig ? capaParaTela(CAPA_ORIGINAL) : capaParaTela(['produto' => $x['id']] + $x['sugestao']); ?>
                  <option value="<?= e($x['id']) ?>"<?= $x['id'] === $v['produto'] ? ' selected' : '' ?> data-url="<?= e($x['url']) ?>" data-w="<?= (int) $x['largura'] ?>" data-h="<?= (int) $x['altura'] ?>"
                    data-marca="<?= e($x['marca']) ?>" data-modelo="<?= e($x['modelo']) ?>" data-sug="<?= e(json_encode($sug, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"<?= $orig ? ' data-original' : '' ?><?= $x['ok'] ? '' : ' data-fora="' . e($x['motivo']) . '"' ?>><?= e($x['nome'] . ($orig ? ' (capa original)' : '') . ($x['ok'] ? '' : ' — ' . $x['motivo'])) ?></option>
                  <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn--linha btn--p" name="trocar" value="1" formnovalidate data-capa-trocar><?= icone('check') ?>Usar este produto</button>
            </div>
            <?= erroEmpresa('capa', 'produto', $erros) ?>
            <?php $noSite = count(array_filter($info['lista'], static fn($p): bool => is_array($p) && ($p['visivel'] ?? true) !== false)); ?>
            <p class="ajuda" id="emp-capa-produto-ajuda">Só aparecem produtos que estão no site e têm foto com fundo transparente (recorte): <?= count($podem) === $noSite ? 'hoje são todos os ' . $noSite : count($podem) . ' dos ' . $noSite . ' do site' ?>. Na capa, o produto fica “solto” sobre a faixa verde: uma foto com fundo branco viraria um retângulo branco no meio da página. Produto escondido também não entra, porque a capa leva para a ficha dele.</p>
          </div>
          <?php campoEmpresa('capa', 'texto', 'Legenda', $v['texto'], $erros, ['max' => LIMITES_AJUSTES['capa_texto'], 'opcional' => true, 'exemplo' => 'Ex.: balança computadora com impressora integrada',
              'attrs' => ' data-capa-campo="texto" data-emp-contador',
              'ajuda' => 'Vem depois da marca e do nome, no link para a ficha do produto (ex.: <b>Toledo Prix 7E — balança computadora com impressora integrada</b>). No celular aparecem só a marca e o nome.']); ?>
        </div>

        <div class="capa__previas">
          <div class="capa-previa capa-previa--pc">
            <p class="capa-previa__rot" id="capa-previa-pc">No computador<span class="capa-previa__dica" data-capa-dica hidden> · toque na foto para marcar o ponto da etiqueta <b data-capa-qual>1</b></span></p>
            <?php previaCapa('pc', $item, $v); ?>
          </div>
          <div class="capa-previa capa-previa--cel">
            <p class="capa-previa__rot">No celular <span>· só as etiquetas 1 e 3, sem o detalhe</span><span class="capa-previa__dica" data-capa-dica hidden> · aqui também dá para tocar na foto e marcar o ponto da etiqueta <b data-capa-qual>1</b></span></p>
            <?php previaCapa('cel', $item, $v); ?>
          </div>
          <p class="capa-aviso-ponto" data-capa-status role="status" aria-live="polite"></p>
        </div>

        <ol class="capa-etiquetas" role="list">
          <?php foreach ($v['pontos'] as $i => $pt): $n = $i + 1;
              $temErro = (bool) array_filter(array_keys($erros), static fn($k): bool => str_starts_with((string) $k, 'p' . $i . '-')); ?>
          <li class="capa-et<?= $temErro ? ' is-erro' : '' ?>" data-capa-et="<?= $i ?>">
            <fieldset>
              <legend class="capa-et__tit"><b><?= sprintf('%02d', $n) ?></b> Etiqueta <?= $n ?> <small><?= in_array($i, CAPA_EMBAIXO['pc'], true) ? 'embaixo da foto' : 'em cima da foto' ?><?= in_array($i, CAPA_NO_CELULAR, true) ? ' · aparece no celular' : ' · só no computador' ?></small></legend>
              <div class="capa-et__campos">
                <?php campoEmpresa('capa', 'p' . $i . '-rotulo', 'Nome', $pt['rotulo'], $erros, ['nome' => 'ponto[' . $i . '][rotulo]', 'max' => LIMITES_AJUSTES['capa_rotulo'], 'exemplo' => $i === 0 ? 'Capacidade' : '', 'attrs' => ' data-capa-campo="rotulo"', 'classe' => 'capa-et__rot']); ?>
                <?php campoEmpresa('capa', 'p' . $i . '-valor', 'Destaque', $pt['valor'], $erros, ['nome' => 'ponto[' . $i . '][valor]', 'max' => LIMITES_AJUSTES['capa_valor'], 'exemplo' => $i === 0 ? '32 kg multifaixa' : '', 'attrs' => ' data-capa-campo="valor"', 'classe' => 'capa-et__val']); ?>
                <?php campoEmpresa('capa', 'p' . $i . '-detalhe', 'Detalhe', $pt['detalhe'], $erros, ['nome' => 'ponto[' . $i . '][detalhe]', 'max' => LIMITES_AJUSTES['capa_detalhe'], 'opcional' => true, 'exemplo' => $i === 0 ? 'divisão de 2 g até 6 kg' : '', 'attrs' => ' data-capa-campo="detalhe"', 'classe' => 'capa-et__det']); ?>
              </div>
              <div class="capa-et__ponto">
                <button type="button" class="btn btn--linha btn--p capa-et__marcar" data-capa-marcar="<?= $i ?>" aria-pressed="false" hidden><?= icone('up') ?><span>Marcar o ponto na foto</span></button>
                <div class="capa-et__xy" role="group" aria-label="Posição do ponto da etiqueta <?= $n ?>">
                  <?php campoEmpresa('capa', 'p' . $i . '-x', 'Ponto: da esquerda (%)', $pt['x'], $erros, ['nome' => 'ponto[' . $i . '][x]', 'max' => 5, 'modo' => 'decimal', 'exemplo' => '0 a 100', 'attrs' => ' data-capa-campo="x"', 'classe' => 'capa-et__num']); ?>
                  <?php campoEmpresa('capa', 'p' . $i . '-y', 'de cima (%)', $pt['y'], $erros, ['nome' => 'ponto[' . $i . '][y]', 'max' => 5, 'modo' => 'decimal', 'exemplo' => '0 a 100', 'attrs' => ' data-capa-campo="y"', 'classe' => 'capa-et__num']); ?>
                </div>
              </div>
            </fieldset>
          </li>
          <?php endforeach; ?>
        </ol>
        <p class="ajuda capa-vazias">Para usar menos etiquetas, deixe vazias as últimas (nome, destaque e detalhe em branco).</p>
      </div>
      <?php if ($salva !== null): ?>
      <div class="capa-original">
        <button type="submit" class="btn btn--linha" name="original" value="1" formnovalidate data-capa-original
          data-confirmar-txt="<?= e(textoVoltarCapaOriginal($salva, $info['lista'])) ?>"><?= icone('restore') ?><span>Voltar para a Prix 7E (capa original)</span></button>
        <?php if ($origFora !== ''): ?>
        <p class="ajuda ajuda--alerta"><?= icone('alert') ?><span><b>Atenção:</b> a <?= e(NOME_CAPA_ORIGINAL) ?> <?= e($origFora) ?>. Voltando para ela, o topo da página inicial mostra a foto dela com o link para um produto fora do catálogo: prefira escolher outro produto acima.</span></p>
        <?php else: ?>
        <p class="ajuda">Tira a capa escolhida aqui: a página inicial volta a mostrar a <?= e(NOME_CAPA_ORIGINAL) ?> com as etiquetas de sempre.</p>
        <?php endif; ?>
      </div>
      <?php endif; ?>
<?php
}

/* ------------------------------------------------------------------
   Telas
   ------------------------------------------------------------------ */
/** $estado[seção] = resultado de formularioSecao() (o que foi digitado, erros, nota) para mostrar de novo. */
function telaEmpresa(array $ctx, array $estado = []): void
{
    pagina('Dados da empresa', static function () use ($ctx, $estado): void {
        cabecalhoPagina('Ajustes do site', 'Dados da empresa',
            'O que o site mostra sobre a empresa. Cada parte tem o seu botão <b>Salvar</b>, e o site muda na hora. Antes de cada mudança, o painel guarda uma cópia de segurança.');
        if ($ctx['erroPadrao'] !== null): ?>
  <div class="aviso aviso--info aviso--fixo" role="status"><?= icone('alert') ?><p><b>Não consegui ler os dados originais do site</b> (<?= e($ctx['erroPadrao']) ?>). As partes que ainda não foram mudadas pelo painel podem aparecer vazias aqui; o site continua como está.</p></div>
        <?php endif; ?>
  <div class="emp">
    <nav class="emp-indice" aria-label="Partes desta tela">
      <ol role="list">
        <?php $n = 0; foreach (CURTO_SECOES as $s => $curto): $n++; ?>
        <li><a class="emp-indice__a<?= !empty($estado[$s]['erros']) ? ' is-erro' : '' ?>" href="#e-<?= e($s) ?>" data-emp-indice="<?= e($s) ?>"><b><?= sprintf('%02d', $n) ?></b><?= e($curto) ?></a></li>
        <?php endforeach; ?>
      </ol>
    </nav>
    <div class="emp-secoes">
<?php
        $n = 0;
        foreach (array_keys(SECOES_AJUSTES) as $s) {
            $n++;
            $est = $estado[$s] ?? ['v' => valoresDaTela($s, $ctx['atual']), 'erros' => [], 'nota' => ''];
            caixaEmpresa($s, $n, $ctx, $est, static function (array $v, array $erros) use ($s, $ctx): void {
                match ($s) {
                    'contato' => partesContato($v, $erros),
                    'endereco' => partesEndereco($v, $erros),
                    'horario' => partesHorario($v, $erros),
                    'feriados' => partesFeriados($v, $erros),
                    'aviso' => partesAviso($v, $erros),
                    'google' => partesGoogle($v, $erros),
                    'equipe', 'depoimentos' => partesLista($s, $v, $erros),
                    'marcas' => partesMarcas($v, $erros),
                    'capa' => partesCapa($v, $erros, $ctx),
                };
            });
        }
        ?>
      <p class="emp-rodape">
        <a href="<?= e(link_('copias', ['de' => 'empresa'])) ?>"><?= icone('restore') ?>Cópias de segurança dos dados da empresa</a>
        <a href="<?= e(cfg('url_site')) ?>" target="_blank" rel="noopener"><?= icone('ext') ?>Ver o site</a>
      </p>
    </div>
  </div>
<?php
    }, ['menu' => 'empresa', 'classe' => 'pg-empresa']);
}

function telaAjustesIlegivel(string $motivo): void
{
    http_response_code(500);
    $temCopias = (bool) arquivosCopiasAjustes();
    pagina('Dados da empresa com problema', static function () use ($motivo, $temCopias): void { ?>
  <section class="vazio-grande">
    <?= icone('alert', 'i i--grande') ?>
    <h1>Não consegui ler os ajustes do site</h1>
    <p>O arquivo <code>compartilhado/dados/ajustes.js</code> não está no formato que o painel grava (<?= e($motivo) ?>). Isso acontece quando ele é editado à mão ou substituído por uma versão errada. Enquanto isso, o site mostra os dados originais da empresa. Produtos e clientes não foram afetados.</p>
    <?php if ($temCopias): ?>
    <p>O jeito mais rápido de resolver é restaurar uma cópia de segurança dos dados da empresa.</p>
    <p><a class="btn btn--verde" href="<?= e(link_('copias', ['de' => 'empresa'])) ?>"><?= icone('restore') ?>Ver cópias dos dados da empresa</a></p>
    <?php endif; ?>
    <p><?= $temCopias ? 'Se nenhuma cópia servir, dá para recomeçar' : 'Ainda não há cópias de segurança dos dados da empresa. Dá para recomeçar' ?> com os dados originais do site: o arquivo com problema fica guardado na pasta privada do painel.</p>
    <form method="post" action="<?= e(link_('empresa-salvar', ['secao' => 'recomecar'])) ?>" data-confirmar="<?= e(textoRecomecarAjustes()) ?>" data-confirmar-ok="Recomeçar" data-confirmar-tit="Recomeçar do zero?">
      <?= campoCsrf() ?>
      <button class="btn btn--linha" type="submit"><?= icone('restore') ?>Recomeçar com os dados originais</button>
    </form>
  </section>
<?php }, ['menu' => 'empresa']);
}

/** Aba "Empresa" das cópias de segurança (as outras abas ficam em lib/visoes.php). */
function telaCopiasAjustes(array $copias): void
{
    $nomes = nomesDosUsuarios();
    pagina('Cópias de segurança', static function () use ($copias, $nomes): void {
        migalhaConta();
        cabecalhoPagina('Desfazer alterações', 'Cópias de segurança',
            'Antes de cada mudança nos <b>dados da empresa</b> (área Empresa) o painel guarda uma cópia de como eles estavam, com as mesmas regras das outras cópias (as '
            . (int) cfg('copias_max') . ' mais recentes, tudo dos últimos ' . (int) cfg('copias_dias') . ' dias; as de <b>restaurar</b> ficam ' . (int) cfg('copias_protegidas_dias') . ' dias). '
            . '<b>Restaurar</b> volta todas as partes juntas (contato, endereço, horário, feriados, aviso, Google, equipe, depoimentos, marcas e a capa do site).');
        abasCopias('empresa');
        if (!$copias): ?>
  <div class="vazio"><p><b>Ainda não há cópias dos dados da empresa.</b> Elas aparecem aqui depois da primeira alteração na área Empresa.</p></div>
  <?php else: ?>
  <ol class="copias" role="list">
    <?php foreach ($copias as $i => $c):
        $titulo = $c['motivo'] !== '' ? $c['motivo'] : 'Antes de: ' . lcfirst($c['feitos'][0] ?? 'alteração'); ?>
    <li class="copia">
      <div class="copia__txt">
        <p class="copia__quando"><?= e(quando($c['criado'])) ?><?php if ($i === 0): ?> <span class="selo selo--destaque">Mais recente</span><?php endif; ?></p>
        <p class="copia__motivo"><?= e($titulo) ?></p>
        <p class="copia__meta"><?= $c['partes'] ? 'Mudado pelo painel: ' . e(implode(', ', $c['partes'])) : ($c['existia'] ? 'Só dados originais do site' : 'Antes da primeira mudança pelo painel') ?><?= $c['usuario'] !== '' ? ' · por ' . e(nomeDe($c['usuario'], $nomes)) : '' ?><?= in_array($c['tipo'], TIPOS_PROTEGIDOS, true) ? ' · guardada por ' . (int) cfg('copias_protegidas_dias') . ' dias' : '' ?></p>
      </div>
      <div class="copia__bts">
        <form method="post" action="<?= e(link_('restaurar')) ?>" data-confirmar="<?= e(textoRestaurarAjustes($copias, $i)) ?>" data-confirmar-ok="Restaurar" data-confirmar-tit="Restaurar esta cópia?">
          <?= campoCsrf() ?><input type="hidden" name="copia" value="<?= e($c['nome']) ?>">
          <button class="btn btn--linha btn--p" type="submit"><?= icone('restore') ?>Restaurar</button>
        </form>
      </div>
    </li>
    <?php endforeach; ?>
  </ol>
  <?php endif;
    }, ['menu' => 'copias', 'classe' => 'pg-copias']);
}
