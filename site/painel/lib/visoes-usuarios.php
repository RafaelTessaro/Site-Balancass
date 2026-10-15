<?php
/* =====================================================================
   Painel Balanças.com — telas de conta e usuários (HTML)
   Conta (o "hub": minha conta e, para administradores, Usuários,
   Atividades e Cópias), lista e formulário de usuários, senha
   temporária (aparece uma vez), atividades de todos, "Crie a sua
   senha" e "Recuperar acesso". Tudo passa por e(); sem inline.
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

/** "← Conta" no topo das telas que ficam dentro de Conta. */
function migalhaConta(string $rotulo = 'Conta', string $href = ''): void
{
    ?>
  <p class="migalha"><a href="<?= e($href !== '' ? $href : link_('conta')) ?>"><?= icone('up', 'i i--esq') ?><?= e($rotulo) ?></a></p>
<?php
}

function selosUsuario(array $u): string
{
    $h = '<span class="selo selo--' . ($u['papel'] === 'admin' ? 'admin' : 'editor') . '">' . e(PAPEIS[$u['papel']]) . '</span>';
    if ($s = situacaoUsuario($u)) $h .= ' <span class="selo ' . e($s[1]) . '">' . e($s[0]) . '</span>';
    return $h;
}

function iniciais(string $nome): string
{
    $partes = array_values(array_filter(preg_split('/\s+/u', trim($nome)) ?: []));
    $ini = static fn(string $p): string => function_exists('mb_substr') ? mb_strtoupper(mb_substr($p, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($p, 0, 1));
    if (!$partes) return '?';
    return $ini($partes[0]) . (count($partes) > 1 ? $ini($partes[count($partes) - 1]) : '');
}

/** Endereço completo do painel (para a mensagem pronta com o acesso). */
function urlDoPainel(): string
{
    $host = preg_replace('/[^A-Za-z0-9.:\[\]-]/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')) ?? '';
    return (https() ? 'https' : 'http') . '://' . $host . caminhoCookie();
}

/* ------------------------------------------------------------------
   Conta: minha conta + (administrador) Usuários, Atividades, Cópias
   ------------------------------------------------------------------ */
function telaConta(string $erro = ''): void
{
    $eu = usuarioAtual();
    $admin = $eu['papel'] === 'admin';
    $todas = ultimasAtividades(5000, true);
    $minhas = atividadesFiltradas($todas, $eu['usuario'], '', 12);
    $usuarios = $admin ? lerUsuarios() : [];
    $esperando = count(array_filter($usuarios, static fn($u) => $u['ativo'] && $u['trocar_senha']));
    $desativados = count(array_filter($usuarios, static fn($u) => !$u['ativo']));
    $comAcesso = count($usuarios) - $desativados;   // desativado não tem acesso
    $recuperar = $admin ? arquivosRecuperacao() : [];
    $pacotes = $admin ? idsPacotes() : [];   // atualizações do catálogo no servidor (painel/pacotes/)
    $categorias = null;   // para o resumo do cartão Categorias (só administradores)
    if ($admin) { try { $categorias = lerCatalogo()['categorias']; } catch (ErroCatalogo $e) { $categorias = null; } }

    pagina('Conta', static function () use ($eu, $admin, $minhas, $comAcesso, $esperando, $desativados, $recuperar, $erro, $pacotes, $categorias): void {
        cabecalhoPagina('Acesso', 'Conta',
            '<span>Você entrou como <b>' . e($eu['nome']) . '</b></span><span>usuário <b>' . e($eu['usuario']) . '</b></span><span>' . e(PAPEIS[$eu['papel']]) . '</span>',
            $admin ? '<a class="btn btn--linha btn--p" href="' . e(link_('usuario-editar', ['u' => $eu['usuario']])) . '">' . icone('edit') . 'Mudar o meu nome</a>' : '');
        if ($recuperar): ?>
  <div class="aviso aviso--erro aviso--fixo" role="alert"><?= icone('alert') ?><p><b>O arquivo <?= e(implode(' e ', $recuperar)) ?> ainda está na pasta privada.</b> Enquanto ele existir, quem não está logado vê a tela “Recuperar acesso” em vez da tela de entrar. Apague pelo Gerenciador de Arquivos do cPanel (pasta <code><?= e(ondeFicaPastaPrivada()) ?></code>).</p></div>
<?php   endif; ?>
  <section class="hub" aria-labelledby="hub-site-tit">
    <h2 class="secao-tit" id="hub-site-tit">Site</h2>
    <div class="hub__cartoes">
      <?php cartaoProcurados();   /* todos (lib/visoes-procurados.php) */ ?>
      <?php if ($admin) cartaoCategorias($categorias);   /* só administradores (lib/visoes-categorias.php) */ ?>
    </div>
  </section>
<?php   if ($admin): ?>
  <section class="hub" aria-labelledby="hub-tit">
    <h2 class="secao-tit" id="hub-tit">Administração</h2>
    <div class="hub__cartoes<?= $pacotes ? ' hub__cartoes--4' : '' ?>">
      <a class="cartao" href="<?= e(link_('usuarios')) ?>">
        <?= icone('users', 'i cartao__ic') ?>
        <span class="cartao__txt"><b>Usuários</b><span><?= $comAcesso ?> <?= $comAcesso === 1 ? 'pessoa' : 'pessoas' ?> com acesso<?= $esperando ? ' · ' . $esperando . ' aguardando o 1º acesso' : '' ?><?= $desativados ? ' · ' . $desativados . ($desativados === 1 ? ' desativada' : ' desativadas') : '' ?>. Crie, mude o papel, redefina a senha.</span></span>
        <?= icone('seta', 'i cartao__seta') ?>
      </a>
      <a class="cartao" href="<?= e(link_('atividades')) ?>">
        <?= icone('lista', 'i cartao__ic') ?>
        <span class="cartao__txt"><b>Atividades</b><span>O que cada pessoa fez no painel: alterações, acessos e alertas.</span></span>
        <?= icone('seta', 'i cartao__seta') ?>
      </a>
      <a class="cartao" href="<?= e(link_('copias')) ?>">
        <?= icone('restore', 'i cartao__ic') ?>
        <span class="cartao__txt"><b>Cópias de segurança</b><span>Volte os produtos, a lista de clientes ou os dados da empresa para como estavam antes.</span></span>
        <?= icone('seta', 'i cartao__seta') ?>
      </a>
      <?php if ($pacotes): ?>
      <a class="cartao" href="<?= e(link_('pacote', count($pacotes) > 1 ? ['lista' => '1'] : ['id' => $pacotes[0]])) ?>">
        <?= icone('box', 'i cartao__ic') ?>
        <span class="cartao__txt"><b>Atualizações do catálogo</b><span>Produtos novos, modelos trocados pelos atuais e fotos novas preparados por quem cuida do site: reveja e aplique.</span></span>
        <?= icone('seta', 'i cartao__seta') ?>
      </a>
      <?php endif; ?>
    </div>
  </section>
<?php   else: ?>
  <p class="dica dica--papel"><?= icone('user') ?><span>Você é <b>Editor</b>: cuida dos produtos e dos clientes (logos). Usuários, categorias, dados da empresa (e a capa do site) e cópias de segurança ficam com os administradores do painel.</span></p>
<?php   endif; ?>
  <h2 class="secao-tit">Minha conta</h2>
  <div class="conta">
    <section class="caixa">
      <h3 class="caixa__tit">Trocar senha</h3>
      <?php if ($erro !== ''): ?><div class="aviso aviso--erro" role="alert"><?= icone('alert') ?><p><?= e($erro) ?></p></div><?php endif; ?>
      <form method="post" action="<?= e(link_('senha')) ?>" class="form-acesso" data-form-enviar>
        <?= campoCsrf() ?>
        <input type="text" name="usuario" value="<?= e($eu['usuario']) ?>" autocomplete="username" hidden>
        <?php campoSenha('f-atual', 'atual', 'Senha atual', 'current-password'); ?>
        <?php campoSenha('f-nova', 'nova', 'Senha nova', 'new-password', 'Pelo menos ' . (int) cfg('senha_minimo') . ' caracteres. Ao trocar, outros aparelhos conectados saem.'); ?>
        <?php campoSenha('f-nova2', 'nova2', 'Repita a senha nova', 'new-password'); ?>
        <button class="btn btn--verde" type="submit"><?= icone('lock') ?>Trocar senha</button>
      </form>
    </section>
    <section class="caixa">
      <h3 class="caixa__tit">Minhas últimas atividades</h3>
      <?php if (!$minhas): ?><p class="ajuda">Nada registrado ainda.</p><?php else: ?>
      <ol class="atividades" role="list">
        <?php foreach ($minhas as $a) linhaAtividade($a, null); ?>
      </ol>
      <?php endif; ?>
      <?php if ($admin): ?><p class="caixa__mais"><a href="<?= e(link_('atividades')) ?>"><?= icone('lista') ?>Ver as atividades de todos</a></p><?php endif; ?>
    </section>
    <section class="caixa caixa--info">
      <h3 class="caixa__tit">Sobre este painel</h3>
      <ul class="info">
        <li>Por segurança, o painel sai sozinho depois de 2 horas sem uso.</li>
        <li>Dados privados (usuários, cópias, lixeira de fotos): <code><?= e(ondeFicaPastaPrivada()) ?></code><?= privadaForaDoSite() ? ' — fora da pasta do site.' : ' — dentro do site, protegida.' ?></li>
        <li>Maior foto aceita agora: <b><?= e(mb_(limiteEnvio())) ?></b>.</li>
      </ul>
      <details class="acesso__ajuda conta__senha">
        <summary>Esqueceu a senha?</summary>
        <?php passosEsqueciSenha(); ?>
      </details>
    </section>
  </div>
<?php
    }, ['menu' => 'conta', 'classe' => 'pg-conta']);
}

/** Uma linha de atividade. $nomes = null: lista só da própria pessoa (sem repetir o nome). */
function linhaAtividade(array $a, ?array $nomes): void
{
    $alerta = tipoDoEvento($a['evento']) === 'alertas';
    $quem = $a['usuario'] !== '-' && $a['usuario'] !== '' ? $a['usuario'] : '';
    $data = $nomes === null ? date('d/m H:i', $a['quando']) : date('H:i', $a['quando']);
    ?>
        <li class="atv<?= $alerta ? ' atv--alerta' : '' ?>">
          <time datetime="<?= e(date('c', $a['quando'])) ?>"><?= e($data) ?></time>
          <span><?php if ($nomes !== null): ?><b class="atv__quem"><?= e($quem !== '' ? nomeDe($quem, $nomes) : 'Alguém') ?></b> <?php endif; ?><b><?= e(fraseAtividade($a)) ?></b><?= $alerta && $a['detalhe'] !== '' ? ' — ' . e($a['detalhe']) : '' ?><?= $a['vezes'] > 1 ? ' (' . (int) $a['vezes'] . ' vezes)' : '' ?></span>
          <?php if ($alerta): ?><small>IP <?= e($a['ip']) ?></small><?php endif; ?>
        </li>
<?php
}

/* ------------------------------------------------------------------
   Usuários (só administradores)
   ------------------------------------------------------------------ */
function telaUsuarios(array $lista): void
{
    $admins = adminsAtivos($lista);
    $ativos = count(array_filter($lista, static fn($u) => $u['ativo']));   // desativado não tem acesso
    $desativados = count($lista) - $ativos;
    pagina('Usuários', static function () use ($lista, $admins, $ativos, $desativados): void {
        migalhaConta();
        cabecalhoPagina('Conta · administração', 'Usuários',
            '<span><b>' . $ativos . '</b> ' . ($ativos === 1 ? 'pessoa' : 'pessoas') . ' com acesso</span><span><b>' . $admins . '</b> ' . ($admins === 1 ? 'administrador' : 'administradores') . '</span>'
            . ($desativados ? '<span><b>' . $desativados . '</b> ' . ($desativados === 1 ? 'desativada' : 'desativadas') . '</span>' : ''),
            count($lista) < MAX_USUARIOS ? '<a class="btn btn--verde" href="' . e(link_('usuario-novo')) . '">' . icone('plus') . 'Novo usuário</a>' : '');
        ?>
  <div class="papeis-info">
    <p><span class="selo selo--admin">Administrador</span> Faz tudo: produtos, clientes, categorias, dados da empresa e capa do site, cópias de segurança, usuários e as atividades de todos.</p>
    <p><span class="selo selo--editor">Editor</span> Cadastra e edita produtos (foto, estoque, destaque, ordem, excluir, um ou vários de uma vez) e os logos de clientes, e vê os Mais procurados. Não mexe em categorias, usuários, dados da empresa (nem na capa do site) nem cópias de segurança.</p>
  </div>
  <ol class="usuarios" role="list">
    <?php foreach ($lista as $u) itemUsuario($u); ?>
  </ol>
  <p class="usuarios__rodape"><?= count($lista) >= MAX_USUARIOS ? 'O painel chegou ao máximo de ' . MAX_USUARIOS . ' usuários: exclua quem não usa mais para criar outro.' : 'Até ' . MAX_USUARIOS . ' usuários. Mudança de papel vale na hora, sem a pessoa precisar entrar de novo.' ?></p>
<?php
    }, ['menu' => 'usuarios', 'classe' => 'pg-usuarios']);
}

function itemUsuario(array $u): void
{
    $voce = ehVoce($u);
    $login = $u['usuario'];
    $campos = campoCsrf() . '<input type="hidden" name="u" value="' . e($login) . '">';
    $acesso = $u['ultimo_acesso'] ? 'Último acesso: ' . quandoCurto($u['ultimo_acesso']) : ($u['trocar_senha'] ? 'Ainda não entrou' : 'Último acesso: ainda não registrado');
    ?>
    <li class="usu<?= $u['ativo'] ? '' : ' is-off' ?>" id="u-<?= e($login) ?>">
      <span class="usu__ini" aria-hidden="true"><?= e(iniciais($u['nome'])) ?></span>
      <div class="usu__txt">
        <h3 class="usu__nome"><a href="<?= e(link_('usuario-editar', ['u' => $login])) ?>"><?= e($u['nome']) ?></a><?php if ($voce): ?> <span class="usu__voce">(você)</span><?php endif; ?></h3>
        <p class="usu__login">usuário <b><?= e($login) ?></b></p>
        <p class="item__selos"><?= selosUsuario($u) ?></p>
        <p class="usu__acesso"><?= e($acesso) ?></p>
      </div>
      <div class="usu__acoes">
        <a class="btn btn--linha btn--p" href="<?= e(link_('usuario-editar', ['u' => $login])) ?>"><?= icone('edit') ?><span>Editar</span><span class="sr-only"> <?= e($u['nome']) ?></span></a>
        <?php if (!$voce): ?>
        <form method="post" action="<?= e(link_('usuario-senha')) ?>" data-confirmar="<?= e(textoRedefinirSenha($u)) ?>" data-confirmar-ok="Redefinir" data-confirmar-tit="Redefinir a senha?">
          <?= $campos ?><button type="submit" class="btn btn--linha btn--p"><?= icone('chave') ?><span>Redefinir senha</span><span class="sr-only"> de <?= e($u['nome']) ?></span></button>
        </form>
        <form method="post" action="<?= e(link_('usuario-ativo')) ?>" data-confirmar="<?= e($u['ativo'] ? textoDesativar($u) : textoReativar($u)) ?>" data-confirmar-ok="<?= $u['ativo'] ? 'Desativar' : 'Reativar' ?>" data-confirmar-tit="<?= $u['ativo'] ? 'Desativar o acesso?' : 'Reativar o acesso?' ?>">
          <?= $campos ?><input type="hidden" name="valor" value="<?= $u['ativo'] ? '0' : '1' ?>">
          <button type="submit" class="btn btn--linha btn--p"><?= icone($u['ativo'] ? 'pausa' : 'check') ?><span><?= $u['ativo'] ? 'Desativar' : 'Reativar' ?></span><span class="sr-only"> <?= e($u['nome']) ?></span></button>
        </form>
        <form method="post" action="<?= e(link_('usuario-excluir')) ?>" data-confirmar="<?= e(textoExcluirUsuario($u)) ?>" data-confirmar-ok="Excluir" data-confirmar-tit="Excluir usuário?">
          <?= $campos ?><button type="submit" class="ic ic--perigo" aria-label="Excluir <?= e($u['nome']) ?>" title="Excluir"><?= icone('trash') ?></button>
        </form>
        <?php endif; ?>
      </div>
    </li>
<?php
}

/** Novo usuário ($atual = null) ou editar nome/papel. $v = valores digitados. */
function telaUsuarioForm(?array $atual, array $v = [], array $erros = []): void
{
    $novo = $atual === null;
    $v += $atual ?? ['usuario' => '', 'nome' => '', 'papel' => 'editor'];
    $voce = !$novo && ehVoce($atual);
    $titulo = $novo ? 'Novo usuário' : 'Editar usuário';
    $acao = link_('usuario-salvar', $novo ? [] : ['u' => $atual['usuario']]);
    $voltar = link_('usuarios') . ($novo ? '' : '#u-' . $atual['usuario']);
    $err = static fn(string $k): string => isset($erros[$k]) ? '<p class="campo__erro" id="erro-' . e($k) . '">' . icone('alert') . e($erros[$k]) . '</p>' : '';
    $inv = static fn(string $k): string => isset($erros[$k]) ? ' aria-invalid="true" aria-describedby="erro-' . e($k) . '"' : '';
    $ocupados = $novo ? implode(' ', array_map(static fn($u) => strtolower($u['usuario']), lerUsuarios())) : '';

    pagina($titulo, static function () use ($novo, $atual, $v, $erros, $voce, $titulo, $acao, $voltar, $err, $inv, $ocupados): void {
        migalhaConta('Usuários', $voltar);
        cabecalhoPagina($novo ? 'Conta · administração' : $atual['nome'], $titulo, $novo ? 'A pessoa recebe uma senha temporária, que só aparece uma vez, e cria a própria senha no primeiro acesso.' : ''); ?>
  <?php if ($erros): ?>
  <div class="aviso aviso--erro" role="alert" tabindex="-1" data-erros>
    <?= icone('alert') ?>
    <div>
      <p><b><?= $novo ? 'O usuário não foi criado.' : 'Nada foi salvo.' ?></b> Confira:</p>
      <ul><?php foreach ($erros as $k => $m): ?><li><a href="#f-<?= e($k === 'geral' ? 'nome' : $k) ?>"><?= e($m) ?></a></li><?php endforeach; ?></ul>
    </div>
  </div>
  <?php endif; ?>
  <form class="fp fu" method="post" action="<?= e($acao) ?>" data-form-enviar>
    <?= campoCsrf() ?>
    <fieldset class="caixa">
      <legend>Pessoa</legend>
      <div class="campo">
        <label for="f-nome">Nome</label>
        <input id="f-nome" name="nome" required maxlength="60" value="<?= e($v['nome']) ?>" autocomplete="off" placeholder="Ex.: Lizi Ellen"<?= $inv('nome') ?><?= $novo ? ' data-sugerir-login' : '' ?>>
        <?= $err('nome') ?>
        <p class="ajuda">Como a pessoa aparece no painel (na lista de usuários e nas atividades).</p>
      </div>
      <div class="campo">
        <?php if ($novo): ?>
        <label for="f-usuario">Usuário <small>o que a pessoa digita para entrar</small></label>
        <input id="f-usuario" name="usuario" required maxlength="40" pattern="[A-Za-z0-9._\-]{3,40}" value="<?= e($v['usuario']) ?>" autocapitalize="off" autocomplete="off" spellcheck="false"<?= $inv('usuario') ?> data-login data-ocupados="<?= e($ocupados) ?>">
        <?= $err('usuario') ?>
        <p class="ajuda">De 3 a 40 letras sem acento, números, ponto ou hífen (ex.: lizi). Não muda depois de criado.</p>
        <?php else: ?>
        <p class="rotulo">Usuário</p>
        <p class="usu__fixo"><b><?= e($atual['usuario']) ?></b><span>O usuário (o que a pessoa digita para entrar) não muda depois de criado.</span></p>
        <?php endif; ?>
      </div>
    </fieldset>
    <fieldset class="caixa" id="f-papel">
      <legend>Papel</legend>
      <?php if ($voce): ?><p class="ajuda ajuda--topo">Você não pode mudar o seu próprio papel. Se precisar, peça a outro administrador.</p><input type="hidden" name="papel" value="<?= e($atual['papel']) ?>"><?php endif; ?>
      <?= $err('papel') ?>
      <div class="papeis" role="radiogroup" aria-label="Papel">
        <?php opcaoPapel('editor', $v['papel'], $voce); opcaoPapel('admin', $v['papel'], $voce); ?>
      </div>
    </fieldset>
    <?php if (isset($erros['geral'])): ?><?= $err('geral') ?><?php endif; ?>
    <div class="barra-salvar">
      <a class="btn btn--linha" href="<?= e($voltar) ?>">Cancelar</a>
      <button class="btn btn--verde" type="submit" data-salvar><?= icone('check') ?><span><?= $novo ? 'Criar usuário' : 'Salvar' ?></span></button>
    </div>
  </form>
  <?php if (!$novo && !$voce) acessoDoUsuario($atual); ?>
<?php
    }, ['menu' => 'usuarios', 'classe' => 'pg-produto pg-usuario']);
}

/** Cartão grande de papel (rádio). */
function opcaoPapel(string $papel, string $marcado, bool $travado): void
{
    $itens = $papel === 'admin'
        ? [['sim', 'Tudo o que o editor faz'], ['sim', 'Dados da empresa e capa do site (telefone, horário, aviso no site…)'], ['sim', 'Categorias, usuários, atividades de todos e cópias de segurança']]
        : [['sim', 'Produtos: cadastrar, editar, foto, estoque, destaque, ordem, excluir (um ou vários de uma vez)'], ['sim', 'Clientes (logos do site) e Mais procurados'], ['nao', 'Categorias, usuários, dados da empresa e cópias de segurança']];
    ?>
        <label class="papel">
          <input type="radio" name="papel" value="<?= e($papel) ?>"<?= $marcado === $papel ? ' checked' : '' ?><?= $travado ? ' disabled' : ' required' ?>>
          <span class="papel__in">
            <b class="papel__nome"><?= e(PAPEIS[$papel]) ?></b>
            <span class="papel__desc"><?= $papel === 'admin' ? 'Para quem cuida do painel e das pessoas que usam.' : 'Para quem cuida do dia a dia do catálogo.' ?></span>
            <span class="papel__pode">
              <?php foreach ($itens as [$tipo, $txt]): ?><span class="papel__item papel__item--<?= e($tipo) ?>"><?= icone($tipo === 'sim' ? 'check' : 'x') ?><span><?= $tipo === 'nao' ? '<span class="sr-only">Não: </span>' : '' ?><?= e($txt) ?></span></span><?php endforeach; ?>
            </span>
          </span>
        </label>
<?php
}

/** Na tela de editar: redefinir senha, desativar/reativar e excluir, com o que cada um faz. */
function acessoDoUsuario(array $u): void
{
    $campos = campoCsrf() . '<input type="hidden" name="u" value="' . e($u['usuario']) . '">';
    ?>
  <section class="caixa acesso-usu" aria-labelledby="acesso-tit">
    <h2 class="caixa__tit" id="acesso-tit">Acesso</h2>
    <p class="item__selos"><?= selosUsuario($u) ?></p>
    <div class="acesso-usu__linha">
      <p><b>Redefinir senha</b> Cria uma senha temporária nova (a atual para de funcionar na hora). Use quando a pessoa esquecer a senha.</p>
      <form method="post" action="<?= e(link_('usuario-senha')) ?>" data-confirmar="<?= e(textoRedefinirSenha($u)) ?>" data-confirmar-ok="Redefinir" data-confirmar-tit="Redefinir a senha?">
        <?= $campos ?><button type="submit" class="btn btn--linha btn--p"><?= icone('chave') ?>Redefinir senha</button>
      </form>
    </div>
    <div class="acesso-usu__linha">
      <?php if ($u['ativo']): ?>
      <p><b>Desativar</b> A pessoa sai na hora e não consegue mais entrar, até alguém reativar. Nada do que ela fez é desfeito.</p>
      <?php else: ?>
      <p><b>Reativar</b> A pessoa volta a entrar, com a mesma senha de antes.</p>
      <?php endif; ?>
      <form method="post" action="<?= e(link_('usuario-ativo')) ?>" data-confirmar="<?= e($u['ativo'] ? textoDesativar($u) : textoReativar($u)) ?>" data-confirmar-ok="<?= $u['ativo'] ? 'Desativar' : 'Reativar' ?>" data-confirmar-tit="<?= $u['ativo'] ? 'Desativar o acesso?' : 'Reativar o acesso?' ?>">
        <?= $campos ?><input type="hidden" name="valor" value="<?= $u['ativo'] ? '0' : '1' ?>">
        <button type="submit" class="btn btn--linha btn--p"><?= icone($u['ativo'] ? 'pausa' : 'check') ?><?= $u['ativo'] ? 'Desativar' : 'Reativar' ?></button>
      </form>
    </div>
    <div class="acesso-usu__linha">
      <p><b>Excluir</b> Tira o usuário do painel de vez. As atividades dele continuam registradas.</p>
      <form method="post" action="<?= e(link_('usuario-excluir')) ?>" data-confirmar="<?= e(textoExcluirUsuario($u)) ?>" data-confirmar-ok="Excluir" data-confirmar-tit="Excluir usuário?">
        <?= $campos ?><button type="submit" class="btn btn--perigo btn--p"><?= icone('trash') ?>Excluir</button>
      </form>
    </div>
  </section>
<?php
}

/** A senha temporária, uma vez só, com "Copiar" e uma mensagem pronta para mandar. */
function telaSenhaTemporaria(array $f): void
{
    $papel = PAPEIS[$f['papel']] ?? 'Editor';
    $msg = "Seu acesso ao painel do site Balanças.com:\nEndereço: " . urlDoPainel() . "\nUsuário: " . $f['usuario'] . "\nSenha temporária: " . $f['senha']
        . "\nNo primeiro acesso, o painel pede para você criar a sua senha.";
    pagina('Senha temporária', static function () use ($f, $papel, $msg): void {
        migalhaConta('Usuários', link_('usuarios') . '#u-' . $f['usuario']);
        cabecalhoPagina($f['novo'] ? 'Usuário criado' : 'Senha redefinida', 'Senha temporária',
            '<span>Para <b>' . e($f['nome']) . '</b></span><span>usuário <b>' . e($f['usuario']) . '</b></span><span>' . e($papel) . '</span>');
        if (!$f['ativo']): ?>
  <div class="aviso aviso--info aviso--fixo" role="status"><?= icone('alert') ?><p>Este acesso está <b>desativado</b>: a pessoa só consegue entrar depois que você reativar (Usuários → Reativar).</p></div>
<?php   endif; ?>
  <section class="senha-temp" aria-labelledby="st-tit">
    <p class="senha-temp__rot" id="st-tit">Senha temporária de <?= e($f['nome']) ?></p>
    <p class="senha-temp__valor"><code id="senha-temp" tabindex="0"><?= e($f['senha']) ?></code></p>
    <button type="button" class="btn btn--verde" data-copiar="senha-temp" hidden><?= icone('copiar') ?><span>Copiar senha</span></button>
    <p class="senha-temp__txt">Passe esta senha para a pessoa (pessoalmente ou por mensagem). No primeiro acesso, o painel pede para ela criar a própria senha. Por segurança, esta senha não aparece de novo.</p>
  </section>
  <section class="caixa mensagem-pronta">
    <h2 class="caixa__tit">Mensagem pronta</h2>
    <p class="ajuda">Para mandar pelo WhatsApp: copie e cole na conversa com a pessoa.</p>
    <pre class="mensagem" id="msg-acesso" tabindex="0"><?= e($msg) ?></pre>
    <p><button type="button" class="btn btn--linha btn--p" data-copiar="msg-acesso" hidden><?= icone('copiar') ?><span>Copiar mensagem</span></button></p>
  </section>
  <p class="senha-temp__fim"><a class="btn btn--linha" href="<?= e(link_('usuarios') . '#u-' . $f['usuario']) ?>"><?= icone('check') ?>Pronto, voltar para Usuários</a></p>
<?php
    }, ['menu' => 'usuarios', 'classe' => 'pg-senha-temp']);
}

/* ------------------------------------------------------------------
   Atividades de todos (só administradores)
   ------------------------------------------------------------------ */
function telaAtividades(array $lista, array $pessoas, string $quem, string $tipo, array $nomes): void
{
    // Agrupa por dia: "Hoje", "Ontem", "Quinta, 08/10/2026"
    $dias = [];
    foreach ($lista as $a) $dias[date('Y-m-d', $a['quando'])][] = $a;
    $semana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    $rotuloDia = static function (string $d) use ($semana): array {
        $ts = strtotime($d) ?: 0;
        if ($d === date('Y-m-d')) return ['Hoje', date('d/m/Y', $ts)];
        if ($d === date('Y-m-d', strtotime('yesterday'))) return ['Ontem', date('d/m/Y', $ts)];
        return [$semana[(int) date('w', $ts)], date('d/m/Y', $ts)];
    };
    pagina('Atividades', static function () use ($dias, $lista, $pessoas, $quem, $tipo, $nomes, $rotuloDia): void {
        migalhaConta();
        cabecalhoPagina('Conta · administração', 'Atividades', 'O que cada pessoa fez no painel, das mais novas para as mais antigas (até 300 linhas). Repetições seguidas aparecem numa linha só.');
        ?>
  <form class="filtros filtros--atv" method="get" action="index.php" data-filtros-atv>
    <input type="hidden" name="a" value="atividades">
    <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
    <label class="ver">
      <span>Pessoa</span>
      <select name="quem" data-auto-enviar>
        <option value="">Todas as pessoas</option>
        <?php foreach ($pessoas as $k => $rot): ?><option value="<?= e($k) ?>"<?= $quem === $k ? ' selected' : '' ?>><?= e($rot) ?></option><?php endforeach; ?>
      </select>
    </label>
    <div class="chips" role="group" aria-label="Tipo de atividade">
      <?php foreach (TIPOS_ATIVIDADE as $k => $rot): ?>
      <button type="submit" name="tipo" value="<?= e($k) ?>" class="chip" aria-pressed="<?= $tipo === $k ? 'true' : 'false' ?>"><?= e($rot) ?></button>
      <?php endforeach; ?>
    </div>
    <noscript><button class="btn btn--linha btn--p" type="submit">Filtrar</button></noscript>
  </form>
  <p class="contagem"><?= count($lista) === 0 ? 'Nenhuma atividade com esses filtros.' : (count($lista) === 1 ? '1 linha.' : count($lista) . ' linhas.') ?><?= $quem !== '' || $tipo !== '' ? ' <a href="' . e(link_('atividades')) . '">Limpar filtros</a>' : '' ?></p>
  <?php foreach ($dias as $d => $itens): [$dia, $data] = $rotuloDia($d); ?>
  <section class="dia" aria-label="<?= e($dia . ', ' . $data) ?>">
    <h2 class="dia__tit"><?= e($dia) ?> <span><?= e($data) ?></span></h2>
    <ol class="atividades" role="list">
      <?php foreach ($itens as $a) linhaAtividade($a, $nomes); ?>
    </ol>
  </section>
  <?php endforeach; ?>
<?php
    }, ['menu' => 'atividades', 'classe' => 'pg-atividades']);
}

/* ------------------------------------------------------------------
   Crie a sua senha (depois de entrar com a senha temporária)
   ------------------------------------------------------------------ */
function telaCriarSenha(string $erro = ''): void
{
    $eu = usuarioAtual();
    $primeiro = explode(' ', $eu['nome'])[0];
    molduraAcesso('Crie a sua senha', static function () use ($eu, $primeiro, $erro): void { ?>
      <h1 class="acesso__tit">Crie a sua senha</h1>
      <p class="acesso__sub">Olá, <?= e($primeiro) ?>! Você entrou com uma senha temporária. Agora crie a sua: só você vai saber, e é ela que você usa nas próximas vezes.</p>
      <?php if ($erro !== ''): ?><div class="aviso aviso--erro" role="alert"><?= icone('alert') ?><p><?= e($erro) ?></p></div><?php endif; ?>
      <form method="post" action="<?= e(link_('criar-senha')) ?>" class="form-acesso" data-form-enviar>
        <?= campoCsrf() ?>
        <input type="text" name="usuario" value="<?= e($eu['usuario']) ?>" autocomplete="username" hidden>
        <?php campoSenha('f-nova', 'nova', 'Senha nova', 'new-password', 'Pelo menos ' . (int) cfg('senha_minimo') . ' caracteres, diferente da temporária. Dica: junte 3 ou 4 palavras que só você saiba, com um número.', true); ?>
        <?php campoSenha('f-nova2', 'nova2', 'Repita a senha nova', 'new-password'); ?>
        <button class="btn btn--verde btn--largo" type="submit"><?= icone('check') ?>Criar senha e continuar</button>
      </form>
<?php }, ['rodape' => static function (): void { ?>
    <form method="post" action="<?= e(link_('sair')) ?>" class="acesso__rodape">
      <?= campoCsrf() ?>
      <button type="submit"><?= icone('out') ?>Sair sem criar a senha</button>
    </form>
<?php }]);
}

/* ------------------------------------------------------------------
   Recuperar acesso (o arquivo recuperar.txt existe na pasta privada)
   ------------------------------------------------------------------ */
function telaRecuperar(string $erro = '', array $v = []): void
{
    $onde = ondeFicaPastaPrivada();
    $fora = privadaForaDoSite();
    molduraAcesso('Recuperar acesso', static function () use ($erro, $v, $onde, $fora): void { ?>
      <h1 class="acesso__tit">Recuperar acesso</h1>
      <?php if (arquivosRecuperacao()): ?>
      <p class="acesso__sub">O arquivo <code>recuperar.txt</code> está na pasta privada. Com o código gravado na hospedagem, crie uma senha nova para um administrador.</p>
      <?php else: ?>
      <p class="acesso__sub">O painel está sem nenhum administrador ativo. Com o código gravado na hospedagem, crie uma senha nova para um administrador (ele volta a ficar ativo).</p>
      <?php endif; ?>
      <ol class="passos">
        <li>Entre no <b>cPanel</b> da hospedagem e abra o <b>Gerenciador de Arquivos</b>.</li>
        <li><?php if ($fora): ?>Na pasta inicial da sua conta (a mesma que tem a <b>public_html</b>), abra a pasta <code><?= e($onde) ?></code>.<?php else: ?>Abra a pasta <code><?= e($onde) ?></code>.<?php endif; ?></li>
        <li>Clique com o botão direito em <code>primeiro-acesso.php</code> → <b>Ver</b> (ou Editar) e copie o código.</li>
      </ol>
      <?php if ($erro !== ''): ?><div class="aviso aviso--erro" role="alert"><?= icone('alert') ?><p><?= e($erro) ?></p></div><?php endif; ?>
      <form method="post" action="<?= e(link_('recuperar')) ?>" class="form-acesso" data-form-enviar>
        <?= campoCsrf() ?>
        <div class="campo">
          <label for="f-codigo">Código</label>
          <input id="f-codigo" name="codigo" class="codigo" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false" required maxlength="20" placeholder="XXXX-XXXX-XXXX" autofocus>
          <p class="ajuda">Vale por 24 horas. Só quem entra no cPanel consegue ler.</p>
        </div>
        <div class="campo">
          <label for="f-usuario">Usuário de administrador</label>
          <input id="f-usuario" name="usuario" autocomplete="username" autocapitalize="off" spellcheck="false" required maxlength="40" pattern="[A-Za-z0-9._\-]{3,40}" value="<?= e($v['usuario'] ?? '') ?>" aria-describedby="f-usuario-ajuda">
          <p class="ajuda" id="f-usuario-ajuda">O seu usuário de administrador (ele ganha a senha nova e volta a ficar ativo). Se digitar um usuário que não existe, ele é criado como administrador.</p>
        </div>
        <?php campoSenha('f-senha', 'senha', 'Senha nova', 'new-password', 'Pelo menos ' . (int) cfg('senha_minimo') . ' caracteres. Dica: junte 3 ou 4 palavras que só você saiba, com um número.'); ?>
        <?php campoSenha('f-senha2', 'senha2', 'Repita a senha nova', 'new-password'); ?>
        <button class="btn btn--verde btn--largo" type="submit"><?= icone('lock') ?>Recuperar e entrar</button>
      </form>
      <p class="ajuda">Os outros usuários, as cópias de segurança e o resto continuam como estão.<?php if (arquivosRecuperacao()): ?> No fim, o painel apaga o <code>recuperar.txt</code> sozinho. Não era para recuperar? Apague o <code>recuperar.txt</code> e a tela de entrar volta.<?php endif; ?></p>
<?php });
}
