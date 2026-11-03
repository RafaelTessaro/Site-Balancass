<?php
/* =====================================================================
   Painel Balanças.com — telas da verificação em duas etapas (HTML)
   2ª tela do login (código do aplicativo ou de reserva), Conta →
   Verificação em duas etapas (ligar com o código QR, códigos de
   reserva, aparelhos lembrados, desligar), os códigos de reserva (uma
   vez só, para imprimir/copiar) e os pedaços de Conta e Usuários.
   Tudo passa por e(); sem estilos nem scripts inline.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

const AJUDA_CODIGO_APP = '6 números. O código muda a cada 30 segundos: digite o que está aparecendo agora.';

/* ------------------------------------------------------------------
   2ª tela do login
   ------------------------------------------------------------------ */
function telaVerificar(string $erro = '', string $modo = 'app'): void
{
    $p = pendente2fa();
    $u = $p ? usuarioPorLogin($p['usuario']) : null;
    $primeiro = $u ? explode(' ', $u['nome'])[0] : '';
    $reserva = $modo === 'reserva';
    $subApp = 'Olá, ' . $primeiro . '! Abra o aplicativo autenticador no seu celular (Google Authenticator ou outro) e digite o código de 6 números que aparece em “Balanças.com”.';
    $subRes = 'Olá, ' . $primeiro . '! Digite um dos 10 códigos de reserva que você guardou quando ligou a verificação.';
    $min = minutosEspera2fa();
    molduraAcesso('Verificação', static function () use ($erro, $reserva, $subApp, $subRes, $min): void { ?>
      <h1 class="acesso__tit">Verificação</h1>
      <p class="acesso__sub" data-sub-app="<?= e($subApp) ?>" data-sub-reserva="<?= e($subRes) ?>"><?= e($reserva ? $subRes : $subApp) ?></p>
      <?php if ($erro !== ''): ?><div class="aviso aviso--erro" role="alert"><?= icone('alert') ?><p><?= e($erro) ?></p></div><?php endif; ?>
      <form method="post" action="<?= e(link_('verificar')) ?>" class="form-acesso" data-form-enviar data-verificar>
        <?= campoCsrf() ?>
        <input type="hidden" name="modo" value="<?= $reserva ? 'reserva' : 'app' ?>">
        <div class="campo" data-modo-campo="app"<?= $reserva ? ' hidden' : '' ?>>
          <label for="f-codigo">Código do aplicativo</label>
          <input id="f-codigo" name="codigo" class="codigo" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required<?= $reserva ? ' disabled' : ' autofocus' ?> aria-describedby="f-codigo-ajuda" data-codigo-app>
          <p class="ajuda" id="f-codigo-ajuda"><?= e(AJUDA_CODIGO_APP) ?></p>
        </div>
        <div class="campo" data-modo-campo="reserva"<?= $reserva ? '' : ' hidden' ?>>
          <label for="f-reserva">Código de reserva</label>
          <input id="f-reserva" name="codigo" class="codigo" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="12" required<?= $reserva ? ' autofocus' : ' disabled' ?> aria-describedby="f-reserva-ajuda">
          <p class="ajuda" id="f-reserva-ajuda">8 letras e números (ex.: K7QM-2XPA). Cada código vale uma vez só.</p>
        </div>
        <div class="campo">
          <label class="caixinha"><input type="checkbox" name="lembrar" value="1" aria-describedby="f-lembrar-ajuda"><span>Lembrar este aparelho por 30 dias</span></label>
          <p class="ajuda" id="f-lembrar-ajuda">Neste aparelho, o painel não pede o código por 30 dias (a senha continua sendo pedida). Não marque em computador de outra pessoa.</p>
        </div>
        <button class="btn btn--verde btn--largo" type="submit"><?= icone('lock') ?>Confirmar e entrar</button>
      </form>
      <p class="acesso__troca"><a href="<?= e($reserva ? link_('verificar') : link_('verificar', ['reserva' => '1'])) ?>" data-trocar-modo data-txt-app="Usar um código de reserva" data-txt-reserva="Usar o código do aplicativo"><?= $reserva ? 'Usar o código do aplicativo' : 'Usar um código de reserva' ?></a></p>
      <details class="acesso__ajuda">
        <summary>O código não confere ou perdeu o celular?</summary>
        <p class="ajuda"><b>Confira se a hora do celular está automática</b> (Ajustes → Data e hora → Automática; no iPhone, Ajustes → Geral → Data e Hora → Automaticamente). Com a hora errada, o código não confere. Hora do painel agora: <b><?= e(horaPainel()) ?></b>.</p>
        <p class="ajuda"><b>Sem o celular:</b> use um dos códigos de reserva (<a href="<?= e(link_('verificar', ['reserva' => '1'])) ?>" data-trocar-modo="reserva">Usar um código de reserva</a>).</p>
        <p class="ajuda"><b>Sem os códigos de reserva:</b> peça a um administrador para desligar a sua verificação (Conta → Usuários). É o único administrador? Quem entra no cPanel cria o arquivo <code>recuperar.txt</code> (veja “Esqueci a senha” na tela de entrar): a recuperação também desliga a verificação.</p>
      </details>
      <p class="ajuda">Por segurança, esta tela vale <?= $min ?> minutos. Depois disso, o painel pede a senha de novo.</p>
<?php }, ['rodape' => static function (): void { ?>
    <form method="post" action="<?= e(link_('verificar-cancelar')) ?>" class="acesso__rodape">
      <?= campoCsrf() ?>
      <button type="submit"><?= icone('out') ?>Voltar e entrar com outro usuário</button>
    </form>
<?php }]);
}

/* ------------------------------------------------------------------
   Conta → Verificação em duas etapas
   ------------------------------------------------------------------ */
/** $onde: em que caixa vai o erro (ligar | ligar-senha | reservas | desligar). */
function telaDoisFatores(string $erro = '', string $onde = ''): void
{
    $eu = usuarioAtual();
    if (!doisFatoresLigado($eu)) {
        $segredo = segredoNovo2fa();
        if (precisaConfigurar2fa($eu)) {
            // "Exigir de todos": sem menu, como a criação da senha; a pessoa só sai daqui configurando (ou saindo)
            molduraAcesso('Verificação em duas etapas', static function () use ($eu, $segredo, $erro, $onde): void { ?>
      <h1 class="acesso__tit">Duas etapas</h1>
      <div class="aviso aviso--info" role="status"><?= icone('lock') ?><p>O administrador do painel pede que todos usem a verificação em duas etapas. Configure agora para continuar: leva 2 minutos.</p></div>
      <?php blocoConfigurar2fa($eu, $segredo, $erro, $onde === 'ligar-senha'); ?>
<?php       }, ['rodape' => static function (): void { ?>
    <form method="post" action="<?= e(link_('sair')) ?>" class="acesso__rodape">
      <?= campoCsrf() ?>
      <button type="submit"><?= icone('out') ?>Sair sem configurar</button>
    </form>
<?php       }]);
            return;
        }
        pagina('Verificação em duas etapas', static function () use ($eu, $segredo, $erro, $onde): void {
            migalhaConta();
            cabecalhoPagina('Conta · segurança', 'Verificação em duas etapas', e('Além da senha, o painel passa a pedir um código de 6 números que muda a cada 30 segundos e só aparece no seu celular. Quem descobrir a sua senha não entra sem o seu celular.'));
            blocoConfigurar2fa($eu, $segredo, $erro, $onde === 'ligar-senha');
        }, ['menu' => 'conta', 'classe' => 'pg-2fa']);
        return;
    }

    $df = $eu['dois_fatores'];
    $danificada = doisFatoresDanificada($eu);
    $restam = $danificada ? 0 : count($df['reservas']);
    $aparelhos = $danificada ? [] : aparelhosDe($eu);
    $exigir = exigirDoisFatores();
    $caixaErro = static function (string $aqui) use ($erro, $onde): void {
        if ($erro !== '' && $onde === $aqui) { ?>
      <div class="aviso aviso--erro" role="alert" tabindex="-1" data-erros><?= icone('alert') ?><p><?= e($erro) ?></p></div>
<?php   }
    };
    pagina('Verificação em duas etapas', static function () use ($eu, $df, $danificada, $restam, $aparelhos, $exigir, $caixaErro): void {
        migalhaConta();
        cabecalhoPagina('Conta · segurança', 'Verificação em duas etapas', $danificada
            ? e('Ligada, mas com problema no servidor.')
            : e('Ligada desde ' . quando($df['ligado']) . '. Ao entrar, depois da senha, o painel pede o código do aplicativo.'));
        $campoUsuario = '<input type="text" name="usuario" value="' . e($eu['usuario']) . '" autocomplete="username" hidden>';
        if ($danificada): ?>
  <div class="aviso aviso--erro aviso--fixo" role="alert"><?= icone('alert') ?><p><?= e(MSG_2FA_DANIFICADA) ?></p></div>
<?php       return;
        endif; ?>
  <div class="conta df-conta">
    <section class="caixa" id="df-reservas" aria-labelledby="df-reservas-tit">
      <h2 class="caixa__tit" id="df-reservas-tit">Códigos de reserva</h2>
      <p>Restam <b><?= $restam ?></b> de <?= RESERVAS_QTD ?>. Cada um serve uma vez, para entrar quando você estiver sem o celular.</p>
      <?php if ($restam <= 3): ?><div class="aviso aviso--info" role="status"><?= icone('alert') ?><p><?= $restam === 0 ? 'Acabaram os códigos de reserva: gere códigos novos.' : 'Restam poucos: gere códigos novos.' ?></p></div><?php endif; ?>
      <?php $caixaErro('reservas'); ?>
      <form method="post" action="<?= e(link_('dois-fatores-novas-reservas')) ?>#df-reservas" class="form-acesso" data-form-enviar>
        <?= campoCsrf() ?><?= $campoUsuario ?>
        <?php campoSenha('f-senha-r', 'senha', 'Senha atual', 'current-password'); ?>
        <p><button class="btn btn--linha btn--p" type="submit"><?= icone('chave') ?>Gerar códigos novos</button></p>
        <p class="ajuda">Os códigos de agora param de valer.</p>
      </form>
    </section>
    <section class="caixa" id="df-aparelhos" aria-labelledby="df-aparelhos-tit">
      <h2 class="caixa__tit" id="df-aparelhos-tit">Aparelhos lembrados</h2>
      <?php if (!$aparelhos): ?>
      <p class="ajuda">Nenhum aparelho lembrado. Para lembrar, marque “Lembrar este aparelho por 30 dias” ao digitar o código.</p>
      <?php else: ?>
      <ul class="df-aparelhos" role="list">
        <?php foreach ($aparelhos as $a): ?>
        <li><b><?= e($a['rotulo']) ?></b> · lembrado em <?= e(date('d/m/Y', $a['criado'])) ?> · vale até <?= e(date('d/m/Y', $a['vence'])) ?><?php if ($a['este']): ?> · <b class="df-este">este aparelho</b><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
      <form method="post" action="<?= e(link_('aparelhos-esquecer')) ?>" data-confirmar="Esquecer todos os aparelhos? Em todos eles, inclusive neste, o painel volta a pedir o código do aplicativo no próximo acesso." data-confirmar-ok="Esquecer" data-confirmar-tit="Esquecer os aparelhos?" data-confirmar-tipo="ok">
        <?= campoCsrf() ?>
        <button class="btn btn--linha btn--p" type="submit"><?= icone('x') ?>Esquecer todos os aparelhos</button>
      </form>
      <?php endif; ?>
    </section>
    <section class="caixa" aria-labelledby="df-troca-tit">
      <h2 class="caixa__tit" id="df-troca-tit">Trocou de celular?</h2>
      <p class="ajuda">Desligue a verificação na caixa “Desligar a verificação” desta página (com o código do celular antigo ou um código de reserva) e ligue de novo com o celular novo. No Google Authenticator dá também para passar as contas para o celular novo (menu → Transferir contas). Perdeu o celular? Use um código de reserva, ou peça a um administrador.</p>
    </section>
    <section class="caixa" id="df-desligar" aria-labelledby="df-desligar-tit">
      <h2 class="caixa__tit" id="df-desligar-tit">Desligar a verificação</h2>
      <?php $caixaErro('desligar'); ?>
      <form method="post" action="<?= e(link_('dois-fatores-desligar')) ?>#df-desligar" class="form-acesso" data-form-enviar>
        <?= campoCsrf() ?><?= $campoUsuario ?>
        <?php campoSenha('f-senha-d', 'senha', 'Senha atual', 'current-password'); ?>
        <div class="campo">
          <label for="f-codigo-d">Código do aplicativo ou de reserva</label>
          <input id="f-codigo-d" name="codigo" class="codigo" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false" maxlength="12" required>
        </div>
        <p><button class="btn btn--perigo btn--p" type="submit"><?= icone('x') ?>Desligar a verificação</button></p>
        <p class="ajuda">Sem ela, só a senha protege o seu acesso.</p>
        <?php if ($exigir): ?><p class="ajuda">O administrador exige a verificação de todos: ao desligar, o painel pede para configurar de novo na hora (serve para trocar de celular).</p><?php endif; ?>
      </form>
    </section>
  </div>
<?php
    }, ['menu' => 'conta', 'classe' => 'pg-2fa']);
}

/**
 * Passo a passo para ligar: instalar o app, ler o QR (ou digitar a chave), confirmar com a senha atual e um código
 * (na tela obrigatória, só o código: a pessoa acabou de digitar a senha). Sem autofocus (nem na tela obrigatória): a
 * página abre no alto, com a explicação e o passo 1; com erro, abre no passo 3 (#df-ligar). $erroSenha: o erro é da senha.
 */
function blocoConfigurar2fa(array $eu, string $segredo, string $erro, bool $erroSenha = false): void
{
    $uri = uriOtpauth($eu['usuario'], $segredo);
    $pedeSenha = !precisaConfigurar2fa($eu);
    $erroCodigo = $erro !== '' && !$erroSenha;
    ?>
  <ol class="passos df-passos">
    <li>
      <p><b>Instale no celular um aplicativo autenticador (é grátis):</b> <b>Google Authenticator</b> — procure na Play Store (Android) ou na App Store (iPhone). Também servem o Microsoft Authenticator e o Authy.</p>
    </li>
    <li>
      <p><b>No aplicativo, toque em + (ou “Adicionar um código”) → “Ler código QR” e aponte a câmera para o quadrado:</b></p>
      <figure class="qr"><?= qrSvg($uri, 'Código QR para o aplicativo autenticador. A chave também está escrita logo abaixo.') ?></figure>
      <div class="df-mesmo">
        <p>Está usando o painel no mesmo celular do aplicativo?</p>
        <p><a class="btn btn--linha btn--p" href="<?= e($uri) ?>" data-otpauth><?= icone('ext') ?>Abrir no aplicativo autenticador</a></p>
      </div>
      <details class="df-chave">
        <summary>Não dá para ler o QR? Digite a chave</summary>
        <p class="ajuda">No aplicativo: + → “Inserir uma chave de configuração”.</p>
        <dl>
          <div><dt>Nome da conta</dt><dd>Balanças.com (<?= e($eu['usuario']) ?>)</dd></div>
          <div><dt>Chave</dt><dd><code id="chave-2fa"><?= e(chaveAgrupada($segredo)) ?></code></dd></div>
          <div><dt>Tipo de chave</dt><dd>Baseada no horário</dd></div>
        </dl>
        <p><button type="button" class="btn btn--linha btn--p" data-copiar="chave-2fa" hidden><?= icone('copiar') ?><span>Copiar chave</span></button></p>
      </details>
    </li>
    <li id="df-ligar">
      <p><b><?= $pedeSenha ? 'Digite a sua senha atual e o código de 6 números que o aplicativo mostra para “Balanças.com”:' : 'Digite o código de 6 números que o aplicativo mostra para “Balanças.com”:' ?></b></p>
      <?php if ($erro !== ''): ?><div class="aviso aviso--erro" role="alert" tabindex="-1" id="df-ligar-erro" data-erros><?= icone('alert') ?><p><?= e($erro) ?></p></div><?php endif; ?>
      <form method="post" action="<?= e(link_('dois-fatores-ligar')) ?>#df-ligar" class="form-acesso" data-form-enviar>
        <?= campoCsrf() ?>
        <?php if ($pedeSenha): ?>
        <input type="text" name="usuario" value="<?= e($eu['usuario']) ?>" autocomplete="username" hidden>
        <?php campoSenha('f-senha-l', 'senha', 'Senha atual', 'current-password', 'Para ninguém ligar no seu lugar, num computador que ficou aberto.'); ?>
        <?php endif; ?>
        <div class="campo">
          <label for="f-codigo-novo">Código do aplicativo</label>
          <input id="f-codigo-novo" name="codigo" class="codigo" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required<?= $erroCodigo ? ' aria-invalid="true" aria-describedby="df-ligar-erro f-codigo-novo-ajuda"' : ' aria-describedby="f-codigo-novo-ajuda"' ?> data-codigo-app>
          <p class="ajuda" id="f-codigo-novo-ajuda"><?= e(AJUDA_CODIGO_APP) ?></p>
        </div>
        <p><button class="btn btn--verde" type="submit"><?= icone('check') ?>Confirmar e ligar</button></p>
      </form>
      <p class="ajuda">Hora do painel agora: <?= e(horaPainel()) ?>. Se o código não conferir, confira se a hora do celular está automática.</p>
    </li>
  </ol>
  <form method="post" action="<?= e(link_('dois-fatores-recomecar')) ?>" class="df-recomecar">
    <?= campoCsrf() ?>
    <p><button class="btn btn--linha btn--p" type="submit"><?= icone('restore') ?>Gerar outro código QR</button></p>
    <p class="ajuda">Use se apagou a conta no aplicativo ou se o QR não funcionou. Se você já tinha lido um QR antes, apague no aplicativo a conta “Balanças.com” antiga.</p>
  </form>
<?php
}

/** Os 10 códigos de reserva, uma vez só, com Imprimir e Copiar. */
function telaCodigosReserva(array $f): void
{
    $eu = usuarioAtual();
    $depois = is_string($f['depois'] ?? null) ? $f['depois'] : link_('dois-fatores');
    pagina('Códigos de reserva', static function () use ($eu, $f, $depois): void {
        cabecalhoPagina('Verificação em duas etapas', 'Códigos de reserva', e('Para entrar quando você estiver sem o celular.'));
        if (!empty($f['novos'])): ?>
  <div class="aviso aviso--info aviso--fixo" role="status"><?= icone('alert') ?><p>Códigos novos criados. Os anteriores pararam de valer.</p></div>
<?php   endif; ?>
  <section class="reservas" aria-labelledby="res-tit">
    <p class="reservas__rot" id="res-tit">Códigos de reserva de <?= e($eu['nome']) ?> (<?= e($eu['usuario']) ?>)</p>
    <ol id="codigos-reserva" class="reservas__lista" tabindex="0">
<?php foreach ($f['codigos'] as $c): ?>
<li><code><?= e((string) $c) ?></code></li>
<?php endforeach; ?>
</ol>
    <p class="so-impressao">Balanças.com · painel · gerados em <?= e(quando((int) $f['quando'])) ?></p>
    <p class="reservas__txt">Guarde estes códigos num lugar seguro: imprima e guarde com os seus documentos, ou anote num papel. Cada código serve uma vez só. Por segurança, eles não aparecem de novo. Não mande por mensagem para ninguém.</p>
    <p class="reservas__bts">
      <button type="button" class="btn btn--verde" data-imprimir hidden><?= icone('lista') ?>Imprimir</button>
      <button type="button" class="btn btn--linha btn--p" data-copiar="codigos-reserva" hidden><?= icone('copiar') ?><span>Copiar os códigos</span></button>
    </p>
  </section>
  <p class="reservas__fim"><a class="btn btn--linha" href="<?= e($depois) ?>"><?= icone('check') ?>Pronto, já guardei</a></p>
  <p class="ajuda reservas__fim">Se fechar esta página sem guardar, gere códigos novos em Conta → Verificação em duas etapas.</p>
<?php
    }, ['menu' => 'conta', 'classe' => 'pg-reservas']);
}

/* ------------------------------------------------------------------
   Pedaços de Conta e Usuários
   ------------------------------------------------------------------ */
/** Caixa "Verificação em duas etapas" em Conta (logo depois de "Trocar senha"). */
function caixaDoisFatoresConta(array $eu): void
{
    ?>
    <section class="caixa df-caixa" id="conta-2fa">
      <h3 class="caixa__tit">Verificação em duas etapas</h3>
      <?php if (doisFatoresDanificada($eu)): ?>
      <p>A verificação em duas etapas está com problema no servidor. Peça a um administrador para desligar (Conta → Usuários) e ligue de novo.</p>
      <?php elseif (doisFatoresLigado($eu)):
          $restam = count($eu['dois_fatores']['reservas']);
          $n = count(aparelhosDe($eu)); ?>
      <p>Ligada desde <?= e(date('d/m/Y', $eu['dois_fatores']['ligado'])) ?>. Restam <?= $restam ?> de <?= RESERVAS_QTD ?> códigos de reserva.<?= $n > 0 ? ' Lembrada em ' . $n . ($n === 1 ? ' aparelho.' : ' aparelhos.') : '' ?></p>
      <p><a class="btn btn--linha btn--p" href="<?= e(link_('dois-fatores')) ?>"><?= icone('chave') ?>Ver e mudar</a></p>
      <?php if ($restam <= 3): ?><p class="ajuda ajuda--alerta"><?= icone('alert') ?><span><?= $restam === 0 ? 'Acabaram os códigos de reserva: gere códigos novos.' : 'Restam poucos códigos de reserva: gere códigos novos.' ?></span></p><?php endif; ?>
      <?php else: ?>
      <p>Desligada. Ligue para o painel pedir, além da senha, um código que aparece só no seu celular (Google Authenticator ou outro aplicativo autenticador).</p>
      <p><a class="btn btn--verde btn--p" href="<?= e(link_('dois-fatores')) ?>"><?= icone('lock') ?>Ligar a verificação</a></p>
      <?php endif; ?>
    </section>
<?php
}

/** Quadro em Usuários: quantos usam e "Exigir de todos" / "Deixar de exigir". */
function quadroDoisFatoresUsuarios(array $lista): void
{
    $seg = lerSeguranca();
    $ativos = array_values(array_filter($lista, static fn(array $u): bool => $u['ativo']));
    $m = count($ativos);
    $n = count(array_filter($ativos, 'doisFatoresOk'));
    $eu = usuarioAtual();
    $possoExigir = doisFatoresOk($eu);
    $por = $seg['por'] !== null ? nomeDe($seg['por']) : '';
    ?>
  <section class="caixa df-politica" aria-labelledby="df-pol-tit">
    <h2 class="caixa__tit" id="df-pol-tit">Verificação em duas etapas</h2>
    <?php if (!$seg['exigir']): ?>
    <p><?= $n ?> de <?= $m ?> <?= $m === 1 ? 'pessoa com acesso usa' : 'pessoas com acesso usam' ?> a verificação em duas etapas (senha + código do celular). Hoje cada pessoa escolhe se usa.</p>
    <form method="post" action="<?= e(link_('dois-fatores-exigir')) ?>" data-confirmar="<?= e(textoExigir2fa($lista)) ?>" data-confirmar-ok="Exigir" data-confirmar-tit="Exigir de todos?" data-confirmar-tipo="ok">
      <?= campoCsrf() ?><input type="hidden" name="valor" value="1">
      <p><button class="btn btn--linha btn--p" type="submit"<?= $possoExigir ? '' : ' disabled aria-describedby="df-pol-sem"' ?>><?= icone('lock') ?>Exigir de todos</button></p>
    </form>
    <p class="ajuda">Quem ainda não tem vai precisar configurar no próximo acesso, antes de usar o painel.</p>
    <?php if (!$possoExigir): ?><p class="ajuda" id="df-pol-sem">Para exigir de todos, ligue primeiro a sua: <a href="<?= e(link_('dois-fatores')) ?>">Conta → Verificação em duas etapas</a>.</p><?php endif; ?>
    <?php else: ?>
    <p><?= $seg['desde'] !== null ? 'Exigida de todos desde ' . e(date('d/m/Y', $seg['desde'])) . ($por !== '' ? ' (' . e($por) . ')' : '') . '.' : 'Exigida de todos.' ?> <?= $n ?> de <?= $m ?> já configuraram; quem falta configura no próximo acesso.</p>
    <form method="post" action="<?= e(link_('dois-fatores-exigir')) ?>" data-confirmar="<?= e(TEXTO_DEIXAR_EXIGIR) ?>" data-confirmar-ok="Deixar de exigir" data-confirmar-tit="Deixar de exigir?" data-confirmar-tipo="ok">
      <?= campoCsrf() ?><input type="hidden" name="valor" value="0">
      <p><button class="btn btn--linha btn--p" type="submit"><?= icone('x') ?>Deixar de exigir</button></p>
    </form>
    <?php endif; ?>
  </section>
<?php
}

/** Em Editar usuário → Acesso (não é você): situação e "Desligar a verificação". */
function linhaDoisFatoresUsuario(array $u): void
{
    ?>
    <div class="acesso-usu__linha">
      <?php if (doisFatoresLigado($u)): ?>
      <p><b>Verificação em duas etapas</b> <?= doisFatoresDanificada($u) ? 'Com problema no servidor: a pessoa não consegue entrar. Desligue e peça para ela ligar de novo.' : 'Ligada desde ' . e(date('d/m/Y', $u['dois_fatores']['ligado'])) . '. Desligue se a pessoa perdeu o celular: ela passa a entrar só com a senha e liga de novo depois.' ?></p>
      <form method="post" action="<?= e(link_('usuario-dois-fatores')) ?>" data-confirmar="<?= e(textoDesligar2fa($u)) ?>" data-confirmar-ok="Desligar" data-confirmar-tit="Desligar a verificação?">
        <?= campoCsrf() ?><input type="hidden" name="u" value="<?= e($u['usuario']) ?>">
        <button type="submit" class="btn btn--linha btn--p"><?= icone('x') ?>Desligar a verificação</button>
      </form>
      <?php else: ?>
      <p><b>Verificação em duas etapas</b> Desligada. Só a própria pessoa liga, em Conta → Verificação em duas etapas.</p>
      <?php endif; ?>
    </div>
<?php
}

/** Selo da verificação na lista de usuários ('' quando não há o que mostrar), + “Entrada bloqueada” (códigos errados). */
function seloDoisFatores(array $u, bool $exigir): string
{
    $bloq = bloqueioConta($u['usuario']) > 0 ? ' <span class="selo selo--off">Entrada bloqueada</span>' : '';
    if (doisFatoresDanificada($u)) return '<span class="selo selo--off">Verificação com problema</span>' . $bloq;
    if (doisFatoresLigado($u)) return '<span class="selo selo--2fa">2 etapas</span>' . $bloq;
    if ($exigir && $u['ativo']) return '<span class="selo selo--espera">Falta a verificação em 2 etapas</span>' . $bloq;
    return ltrim($bloq);
}
