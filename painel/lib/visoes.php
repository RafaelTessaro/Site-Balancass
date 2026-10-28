<?php
/* =====================================================================
   Painel Balanças.com — telas (HTML)
   Toda saída de dados passa por e() (htmlspecialchars). Sem scripts nem
   estilos inline (a Content-Security-Policy do painel não permite).
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

function versaoAsset(string $arq): string
{
    return $arq . '?v=' . (@filemtime(dirname(__DIR__) . '/' . $arq) ?: '1');
}

function icone(string $nome, string $classe = 'i'): string
{
    return '<svg class="' . e($classe) . '" aria-hidden="true" focusable="false"><use href="#i-' . e($nome) . '"/></svg>';
}

function spriteIcones(): void
{
    ?>
<svg class="sprite" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
  <symbol id="i-up" viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-down" viewBox="0 0 24 24"><path d="M12 5v14M19 12l-7 7-7-7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-edit" viewBox="0 0 24 24"><path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4zM13.5 6.5l4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-trash" viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-star" viewBox="0 0 24 24"><path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
  <symbol id="i-eye-off" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.1M6.6 6.6C3.8 8.4 2 12 2 12s3.6 7 10 7a9.7 9.7 0 0 0 5.4-1.6M9.9 9.9a3 3 0 0 0 4.2 4.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-eye" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/></symbol>
  <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></symbol>
  <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="M16 16l4.5 4.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></symbol>
  <symbol id="i-out" viewBox="0 0 24 24"><path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4M15 16l4-4-4-4M19 12H9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-ext" viewBox="0 0 24 24"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-restore" viewBox="0 0 24 24"><path d="M4 12a8 8 0 1 0 2.4-5.7M4 4v4.5h4.5M12 8v4.5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 21c1.2-4 4.3-6 8-6s6.8 2 8 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
  <symbol id="i-check" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-alert" viewBox="0 0 24 24"><path d="M12 3l10 18H2L12 3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M12 10v4.5M12 17.5v.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></symbol>
  <symbol id="i-camera" viewBox="0 0 24 24"><path d="M3 8a2 2 0 0 1 2-2h2.5L9 4h6l1.5 2H19a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="12" cy="13" r="3.5" fill="none" stroke="currentColor" stroke-width="2"/></symbol>
  <symbol id="i-x" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></symbol>
  <symbol id="i-box" viewBox="0 0 24 24"><path d="M3 7.5L12 3l9 4.5v9L12 21l-9-4.5v-9zM3 7.5l9 4.5 9-4.5M12 12v9" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
  <symbol id="i-lock" viewBox="0 0 24 24"><rect x="4.5" y="10.5" width="15" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="2"/></symbol>
  <symbol id="i-loja" viewBox="0 0 24 24"><path d="M4 4h16l1.5 5a2.4 2.4 0 0 1-4.8 0 2.4 2.4 0 0 1-4.7 0 2.4 2.4 0 0 1-4.7 0 2.4 2.4 0 0 1-4.8 0L4 4zM4.5 11.5V20h15v-8.5M10 20v-5h4v5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/></symbol>
  <symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c.9-3.5 3.4-5.5 6.5-5.5s5.6 2 6.5 5.5M15.5 4.8a3.5 3.5 0 0 1 0 6.4M17.5 14.8c2 .6 3.4 2.4 4 5.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
  <symbol id="i-empresa" viewBox="0 0 24 24"><path d="M4 21V5.5L13 3v18M13 8.5l7 2V21M2.5 21h19M7.5 8v.01M7.5 11.5v.01M7.5 15v.01M9.8 8v.01M9.8 11.5v.01M9.8 15v.01M16.5 13.5v.01M16.5 17v.01" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-lista" viewBox="0 0 24 24"><path d="M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></symbol>
  <symbol id="i-copiar" viewBox="0 0 24 24"><rect x="8.5" y="8.5" width="12" height="12" rx="1.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M15.5 8.5V5a1.5 1.5 0 0 0-1.5-1.5H5A1.5 1.5 0 0 0 3.5 5v9A1.5 1.5 0 0 0 5 15.5h3.5" fill="none" stroke="currentColor" stroke-width="2"/></symbol>
  <symbol id="i-chave" viewBox="0 0 24 24"><circle cx="8" cy="15" r="4.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M11.2 11.8L20 3M16.5 6.5l2.5 2.5M14 9l2 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-seta" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-pausa" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M10 9v6M14 9v6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></symbol>
  <symbol id="i-show" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" fill="currentColor"/></symbol>
  <symbol id="i-marcar" viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="17" height="17" rx="1.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M7.5 12.3l3 3 6-6.6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-categorias" viewBox="0 0 24 24"><path d="M4 4h6.5v6.5H4zM13.5 4H20v6.5h-6.5zM4 13.5h6.5V20H4zM13.5 13.5H20V20h-6.5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
  <symbol id="i-lugar" viewBox="0 0 24 24"><path d="M8 20V4M4 8l4-4 4 4M16 4v16M12 16l4 4 4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-arrastar" viewBox="0 0 24 24"><g fill="currentColor"><circle cx="9" cy="5.5" r="1.8"/><circle cx="15" cy="5.5" r="1.8"/><circle cx="9" cy="12" r="1.8"/><circle cx="15" cy="12" r="1.8"/><circle cx="9" cy="18.5" r="1.8"/><circle cx="15" cy="18.5" r="1.8"/></g></symbol>
  <symbol id="i-mover" viewBox="0 0 24 24"><path d="M3 6.5A1.5 1.5 0 0 1 4.5 5h4.2l2 2.2h8.8A1.5 1.5 0 0 1 21 8.7v9.8a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 13.5h6.5M13 11l2.5 2.5L13 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
</svg>
<?php
}

/* ------------------------------------------------------------------
   Moldura das páginas
   ------------------------------------------------------------------ */
/** Itens do menu (no celular, a barra de baixo comporta no máximo 5). Cópias, Usuários e
 *  Atividades ficam dentro de Conta. */
function itensMenu(bool $admin): array
{
    $itens = [
        'produtos' => ['index.php', 'box', 'Produtos'],
        'novo'     => [link_('novo'), 'plus', 'Novo'],
        'clientes' => [link_('clientes'), 'loja', 'Clientes'],
    ];
    if ($admin) $itens['empresa'] = [link_('empresa'), 'empresa', 'Empresa'];
    $itens['conta'] = [link_('conta'), 'user', 'Conta'];
    return $itens;
}

function pagina(string $titulo, callable $conteudo, array $o = []): void
{
    $usuario = usuarioLogado();
    $eu = $usuario !== null ? usuarioAtual() : null;
    $comMenu = $eu !== null && empty($o['semMenu']);
    $atual = $o['menu'] ?? '';
    if (in_array($atual, ['copias', 'usuarios', 'atividades'], true)) $atual = 'conta';   // telas que ficam dentro de Conta
    $classe = trim(($o['classe'] ?? '') . ($comMenu ? ' com-menu' : ''));
    $avisos = pegarAvisos();
    ?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow, noarchive">
<meta name="theme-color" content="#0A0A0A">
<title><?= e($titulo) ?> · Painel Balanças.com</title>
<link rel="icon" href="<?= e(cfg('url_compartilhado')) ?>img/logo/balancas-icone.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Big+Shoulders:opsz,wght@10..72,700..900&amp;family=Archivo:wdth,wght@62..125,400..800&amp;family=Martian+Mono:wght@400..500&amp;display=swap">
<link rel="stylesheet" href="<?= e(versaoAsset('assets/painel.css')) ?>">
<script src="<?= e(versaoAsset('assets/painel.js')) ?>" defer></script>
</head>
<body class="<?= e($classe) ?>"<?= $usuario ? ' data-usuario="' . e($usuario) . '"' : '' ?>>
<?php spriteIcones(); ?>
<a class="pular" href="#conteudo">Pular para o conteúdo</a>
<?php if ($comMenu): ?>
<header class="topo">
  <div class="topo__in">
    <a class="marca" href="index.php" aria-label="Painel Balanças.com — produtos">
      <img src="<?= e(cfg('url_compartilhado')) ?>img/logo/balancas-icone-claro.svg" alt="" width="34" height="34">
      <span class="marca__nome">Balanças<span>.com</span></span>
      <span class="marca__tag">Painel</span>
    </a>
    <nav class="menu" aria-label="Seções do painel">
      <?php foreach (itensMenu($eu['papel'] === 'admin') as $k => [$href, $ic, $rot]): ?>
      <a<?= $k === 'novo' ? ' class="menu__novo"' : '' ?> href="<?= e($href) ?>"<?= $atual === $k ? ' aria-current="page"' : '' ?>><?= icone($ic) ?><span><?= e($rot) ?></span></a>
      <?php endforeach; ?>
    </nav>
    <div class="topo__acoes">
      <span class="topo__quem" title="<?= e('Você entrou como ' . $eu['nome'] . ' (' . PAPEIS[$eu['papel']] . ')') ?>"><span><?= e($eu['nome']) ?></span><small><?= e(PAPEIS[$eu['papel']]) ?></small></span>
      <a class="topo__site" href="<?= e(cfg('url_site')) ?>" target="_blank" rel="noopener"><?= icone('ext') ?><span>Ver site</span></a>
      <form method="post" action="<?= e(link_('sair')) ?>" class="topo__sair">
        <?= campoCsrf() ?>
        <button type="submit" title="Sair do painel"><?= icone('out') ?><span>Sair</span></button>
      </form>
    </div>
  </div>
</header>
<?php endif; ?>
<main id="conteudo" class="<?= e($o['main'] ?? 'pagina') ?>" tabindex="-1">
<?php if ($avisos): ?>
  <div class="avisos" data-avisos>
  <?php foreach ($avisos as $a): $f = is_array($a['form'] ?? null) ? $a['form'] : null; ?>
    <div class="aviso aviso--<?= e($a['tipo']) ?>" role="<?= $a['tipo'] === 'erro' ? 'alert' : 'status' ?>"<?= !empty($a['rascunho']) ? ' data-rascunho-limpar="' . e($a['rascunho']) . '"' : '' ?><?= (!empty($a['link']) || $f) ? ' data-aviso-acao' : '' ?>>
      <?= icone($a['tipo'] === 'erro' ? 'alert' : 'check') ?>
      <p><?= e($a['msg']) ?><?php if (!empty($a['link'])): ?> <a href="<?= e($a['link']) ?>"<?= !empty($a['externo']) ? ' target="_blank" rel="noopener"' : '' ?>><?= e($a['textoLink'] ?? 'Ver') ?></a><?php endif; ?></p>
      <?php if ($f): ?>
      <form method="post" action="<?= e((string) $f['acao']) ?>" class="aviso__form">
        <?= campoCsrf() ?>
        <?php foreach ((array) ($f['campos'] ?? []) as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
        <button type="submit" class="btn btn--linha btn--p"><?= icone('restore') ?><?= e((string) ($f['botao'] ?? 'Desfazer')) ?></button>
      </form>
      <?php endif; ?>
      <button type="button" class="aviso__fechar" data-aviso-fechar aria-label="Fechar aviso" hidden><?= icone('x') ?></button>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php $conteudo(); ?>
</main>
<div class="toast" data-toast role="status" aria-live="polite" hidden></div>
<?php if ($comMenu): ?>
<dialog class="dialogo dialogo--login" data-relogin aria-labelledby="relogin-tit">
  <form class="dialogo__in form-acesso" data-relogin-form novalidate>
    <h2 id="relogin-tit">Entre de novo</h2>
    <p data-relogin-motivo>Sua sessão terminou. Digite a senha para continuar: nada do que está nesta tela se perde.</p>
    <div class="aviso aviso--erro" role="alert" data-relogin-erro hidden><?= icone('alert') ?><p></p></div>
    <div class="campo">
      <label for="rl-usuario">Usuário</label>
      <input id="rl-usuario" name="usuario" autocomplete="username" autocapitalize="off" spellcheck="false" required maxlength="40" value="<?= e($usuario) ?>">
    </div>
    <div class="campo">
      <label for="rl-senha">Senha</label>
      <div class="senha"><input id="rl-senha" name="senha" type="password" autocomplete="current-password" required maxlength="200"></div>
    </div>
    <div class="dialogo__bts">
      <button type="button" class="btn btn--linha" data-relogin-cancelar>Cancelar</button>
      <button type="submit" class="btn btn--verde" data-relogin-ok><?= icone('lock') ?>Entrar e continuar</button>
    </div>
  </form>
</dialog>
<?php endif; ?>
<dialog class="dialogo" data-dialogo aria-labelledby="dialogo-tit">
  <form method="dialog" class="dialogo__in">
    <h2 id="dialogo-tit" data-dialogo-tit>Tem certeza?</h2>
    <p data-dialogo-txt></p>
    <div class="dialogo__bts">
      <button class="btn btn--linha" value="nao">Cancelar</button>
      <button class="btn btn--perigo" value="sim" data-dialogo-ok>Confirmar</button>
    </div>
  </form>
</dialog>
</body>
</html>
<?php
}

function paginaErro(string $titulo, string $texto, int $status = 0): void
{
    if ($status && !headers_sent()) http_response_code($status);
    $f = static function () use ($titulo, $texto): void { ?>
  <section class="vazio-grande">
    <?= icone('alert', 'i i--grande') ?>
    <h1><?= e($titulo) ?></h1>
    <p><?= e($texto) ?></p>
    <p><a class="btn btn--verde" href="index.php">Voltar ao painel</a></p>
  </section>
<?php };
    try { pagina($titulo, $f); } catch (Throwable $t) { echo '<!doctype html><meta charset="utf-8"><title>Erro</title><p>' . e($texto) . '</p>'; }
}

function cabecalhoPagina(string $sobre, string $titulo, string $texto = '', string $acoes = ''): void
{
    ?>
  <header class="cab">
    <div class="cab__txt">
      <p class="sobre"><?= e($sobre) ?></p>
      <h1><?= e($titulo) ?></h1>
      <?php if ($texto !== ''): ?><p class="cab__desc"><?= $texto /* já escapado por quem chama */ ?></p><?php endif; ?>
    </div>
    <?php if ($acoes !== ''): ?><div class="cab__acoes"><?= $acoes ?></div><?php endif; ?>
  </header>
<?php
}

/* ------------------------------------------------------------------
   Entrar / primeiro acesso (sem menu, fundo da marca)
   ------------------------------------------------------------------ */
function molduraAcesso(string $titulo, callable $miolo, array $o = []): void
{
    $rodape = $o['rodape'] ?? null;
    pagina($titulo, static function () use ($miolo, $rodape): void { ?>
  <div class="acesso">
    <div class="acesso__faixa" aria-hidden="true"></div>
    <div class="acesso__cartao">
      <p class="acesso__marca">
        <img src="<?= e(cfg('url_compartilhado')) ?>img/logo/balancas-icone.svg" alt="" width="44" height="44">
        <span class="marca__nome">Balanças<span>.com</span></span>
        <span class="marca__tag">Painel</span>
      </p>
      <?php $miolo(); ?>
    </div>
    <?php if ($rodape): $rodape(); else: ?>
    <p class="acesso__rodape"><a href="<?= e(cfg('url_site')) ?>">← Voltar ao site</a></p>
    <?php endif; ?>
  </div>
<?php }, ['classe' => 'pg-acesso', 'main' => 'principal-acesso', 'semMenu' => true]);
}

function campoSenha(string $id, string $nome, string $rotulo, string $auto, string $ajuda = '', bool $foco = false): void
{
    ?>
      <div class="campo">
        <label for="<?= e($id) ?>"><?= e($rotulo) ?></label>
        <div class="senha">
          <input id="<?= e($id) ?>" name="<?= e($nome) ?>" type="password" autocomplete="<?= e($auto) ?>" required maxlength="200"<?= $foco ? ' autofocus' : '' ?><?= $ajuda ? ' aria-describedby="' . e($id) . '-ajuda"' : '' ?>>
          <button type="button" class="senha__ver" data-ver-senha aria-label="Mostrar senha" aria-pressed="false" hidden><?= icone('show') ?></button>
        </div>
        <?php if ($ajuda): ?><p class="ajuda" id="<?= e($id) ?>-ajuda"><?= e($ajuda) ?></p><?php endif; ?>
      </div>
<?php
}

function telaEntrar(string $erro = '', string $usuario = ''): void
{
    molduraAcesso('Entrar', static function () use ($erro, $usuario): void { ?>
      <h1 class="acesso__tit">Entrar</h1>
      <p class="acesso__sub">Atualize produtos e fotos do site.</p>
      <?php if ($erro !== ''): ?><div class="aviso aviso--erro" role="alert"><?= icone('alert') ?><p><?= e($erro) ?></p></div><?php endif; ?>
      <form method="post" action="<?= e(link_('entrar')) ?>" class="form-acesso" data-form-enviar>
        <?= campoCsrf() ?>
        <div class="campo">
          <label for="f-usuario">Usuário</label>
          <input id="f-usuario" name="usuario" autocomplete="username" autocapitalize="off" spellcheck="false" required maxlength="40" value="<?= e($usuario) ?>"<?= $usuario === '' ? ' autofocus' : '' ?>>
        </div>
        <?php campoSenha('f-senha', 'senha', 'Senha', 'current-password', '', $usuario !== ''); ?>
        <button class="btn btn--verde btn--largo" type="submit"><?= icone('lock') ?>Entrar</button>
      </form>
      <details class="acesso__ajuda">
        <summary>Esqueci a senha</summary>
        <?php passosEsqueciSenha(); ?>
      </details>
<?php });
}

/** Esqueci a senha: pedir a um administrador; ou, último recurso de quem entra no cPanel, o recuperar.txt. */
function passosEsqueciSenha(): void
{
    $onde = ondeFicaPastaPrivada();
    $fora = privadaForaDoSite();
    ?>
        <p class="ajuda"><b>Tem outro administrador no painel?</b> Peça a um administrador para redefinir a sua senha (Conta → Usuários). Você recebe uma senha temporária e cria a sua no primeiro acesso.</p>
        <p class="ajuda"><b>É o único administrador, ou ninguém consegue entrar?</b> Quem entra no <b>cPanel</b> da hospedagem resolve em 2 minutos:</p>
        <ol class="passos passos--p">
          <li>cPanel → <b>Gerenciador de Arquivos</b>.</li>
          <li>Abra a pasta <code><?= e($onde) ?></code><?= $fora ? ' (na pasta inicial da conta, ao lado da <b>public_html</b>)' : '' ?>. Não é a pasta <code>painel</code> do site.</li>
          <li>Clique em <b>+ Arquivo</b> e crie nessa pasta um arquivo vazio chamado <code>recuperar.txt</code>.</li>
          <li>Abra o painel de novo: aparece a tela <b>Recuperar acesso</b>. O código fica no arquivo <code>primeiro-acesso.php</code>, na mesma pasta.</li>
        </ol>
        <p class="ajuda">Os outros usuários, as cópias de segurança e o resto continuam como estão. No fim, o painel apaga o <code>recuperar.txt</code> sozinho.</p>
<?php
}

function telaInstalar(string $erro = '', array $v = []): void
{
    $onde = ondeFicaPastaPrivada();
    $fora = privadaForaDoSite();
    molduraAcesso('Primeiro acesso', static function () use ($erro, $v, $onde, $fora): void { ?>
      <h1 class="acesso__tit">Primeiro acesso</h1>
      <p class="acesso__sub">Crie o seu usuário de <b>Administrador</b> do painel. Para provar que é você, use o código que o painel acabou de gravar na hospedagem.</p>
      <?php if (usuariosDanificado()): ?><div class="aviso aviso--info" role="status"><?= icone('alert') ?><p>O arquivo de usuários (<code>usuarios.php</code>) está danificado e não pôde ser lido. Crie um acesso novo: o arquivo antigo fica guardado na pasta privada, com “danificado” no nome.</p></div><?php endif; ?>
      <ol class="passos">
        <li>Entre no <b>cPanel</b> da hospedagem e abra o <b>Gerenciador de Arquivos</b>.</li>
        <li><?php if ($fora): ?>Na pasta inicial da sua conta (a mesma que tem a <b>public_html</b>), abra a pasta <code><?= e($onde) ?></code>.<?php else: ?>Abra a pasta <code><?= e($onde) ?></code>.<?php endif; ?></li>
        <li>Clique com o botão direito em <code>primeiro-acesso.php</code> → <b>Ver</b> (ou Editar) e copie o código.</li>
      </ol>
      <?php if ($erro !== ''): ?><div class="aviso aviso--erro" role="alert"><?= icone('alert') ?><p><?= e($erro) ?></p></div><?php endif; ?>
      <form method="post" action="<?= e(link_('instalar')) ?>" class="form-acesso" data-form-enviar>
        <?= campoCsrf() ?>
        <div class="campo">
          <label for="f-codigo">Código de primeiro acesso</label>
          <input id="f-codigo" name="codigo" class="codigo" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false" required maxlength="20" placeholder="XXXX-XXXX-XXXX" value="<?= e($v['codigo'] ?? '') ?>" autofocus>
          <p class="ajuda">Vale por 24 horas. Só quem entra no cPanel consegue ler.</p>
        </div>
        <div class="campo">
          <label for="f-nome">Seu nome</label>
          <input id="f-nome" name="nome" autocomplete="name" required maxlength="60" value="<?= e($v['nome'] ?? '') ?>" aria-describedby="f-nome-ajuda" data-sugerir-login>
          <p class="ajuda" id="f-nome-ajuda">Como você aparece para os outros usuários do painel (ex.: Fábio Godoy).</p>
        </div>
        <div class="campo">
          <label for="f-usuario">Usuário</label>
          <input id="f-usuario" name="usuario" autocomplete="username" autocapitalize="off" spellcheck="false" required maxlength="40" pattern="[A-Za-z0-9._\-]{3,40}" value="<?= e($v['usuario'] ?? '') ?>" aria-describedby="f-usuario-ajuda" data-login>
          <p class="ajuda" id="f-usuario-ajuda">É o que você digita para entrar. De 3 a 40 letras sem acento ou números (ex.: fabio).</p>
        </div>
        <?php campoSenha('f-senha', 'senha', 'Senha', 'new-password', 'Pelo menos ' . (int) cfg('senha_minimo') . ' caracteres. Dica: junte 3 ou 4 palavras que só você saiba, com um número (não use exemplos de guias).'); ?>
        <?php campoSenha('f-senha2', 'senha2', 'Repita a senha', 'new-password'); ?>
        <button class="btn btn--verde btn--largo" type="submit"><?= icone('check') ?>Criar acesso e entrar</button>
      </form>
<?php });
}

/* ------------------------------------------------------------------
   Lista de produtos
   ------------------------------------------------------------------ */
/** $lote (edição em lote): ['sel' => modo "Selecionar", 'todos' => marcar todos os da lista, 'marcados' => [códigos]] */
function telaLista(array $cat, array $filtro, array $pacotes = [], array $lote = []): void
{
    $categorias = $cat['categorias'];
    $produtos = $cat['produtos'];
    $total = count($produtos);
    $visiveis = count(array_filter($produtos, static fn($p) => ($p['visivel'] ?? true) !== false));
    $destaques = count(array_filter($produtos, static fn($p) => !empty($p['destaque']) && ($p['visivel'] ?? true) !== false));
    $sel = !empty($lote['sel']) && $total > 0;
    $capaSite = capaEfetiva($produtos);   // o produto que o topo do site mostra de fato (o escolhido, ou a Prix 7E no lugar dele)
    $ctx = ['home' => destaquesDaHome($produtos), 'capa' => produtoCapa(), 'capaSite' => $capaSite, 'capaFrase' => fraseCapaFora(false, $capaSite, $produtos),
        'marcados' => array_fill_keys($lote['marcados'] ?? [], true), 'todos' => $sel && !empty($lote['todos']), 'filtro' => $filtro];
    $capaFora = problemaCapa($produtos);   // capa escolhida em Empresa → Capa do site que não pode aparecer (lib/ajustes.php)
    $porCat = [];
    foreach ($produtos as $p) $porCat[$p['categoria'] ?? ''][] = $p;
    $q = semAcento($filtro['q']);
    $termos = array_values(array_filter(preg_split('/\s+/', $q) ?: []));
    $passa = static function (array $p) use ($filtro, $termos, $categorias): bool {
        if ($filtro['cat'] !== '' && ($p['categoria'] ?? '') !== $filtro['cat']) return false;
        $vis = ($p['visivel'] ?? true) !== false;
        if ($filtro['ver'] === 'site' && !$vis) return false;
        if ($filtro['ver'] === 'ocultos' && $vis) return false;
        if ($filtro['ver'] === 'destaques' && empty($p['destaque'])) return false;
        $t = textoBusca($p, $categorias);
        foreach ($termos as $termo) if (!str_contains($t, $termo)) return false;
        return true;
    };
    $achados = count(array_filter($produtos, $passa));
    $marcado = static fn(array $p): bool => isset($ctx['marcados'][(string) $p['id']]) || ($ctx['todos'] && $passa($p));
    $nMarcados = $sel ? count(array_filter($produtos, $marcado)) : 0;
    // "Desmarcar todos" (sem JavaScript) quando todos os desta lista já estão marcados
    $todosDaLista = $sel && $achados > 0 && count(array_filter($produtos, static fn($p) => $passa($p) && $marcado($p))) === $achados;

    pagina('Produtos', static function () use ($categorias, $produtos, $porCat, $filtro, $passa, $total, $visiveis, $destaques, $achados, $ctx, $pacotes, $sel, $nMarcados, $todosDaLista, $capaFora): void {
        cabecalhoPagina('Catálogo do site', 'Produtos',
            '<span><b data-n="total">' . $total . '</b> produtos</span><span><b data-n="site">' . $visiveis . '</b> no site</span><span><b data-n="destaque">' . $destaques . '</b> em destaque</span>'
            . '<span><b data-n="home">' . count($ctx['home']) . '</b> na página inicial</span>',
            '<a class="btn btn--verde" href="' . e(link_('novo')) . '">' . icone('plus') . 'Novo produto</a>');
        if ($capaFora !== null): ?>
  <div class="aviso aviso--erro aviso--fixo" role="alert"><?= icone('alert') ?><p><?= e($capaFora) ?><?php if (ehAdmin()): ?> <a href="<?= e(link_('empresa') . '#e-capa') ?>">Abrir Capa do site</a><?php endif; ?></p></div>
<?php   endif;
        if ($pacotes) avisoPacotes($pacotes);   // só administradores (acaoLista)
        atalhoProcurados();   // Mais procurados (lib/visoes-procurados.php), para todos
        ?>
  <form class="filtros" method="get" action="index.php" role="search" data-filtros>
    <label class="busca">
      <?= icone('search') ?>
      <span class="sr-only">Buscar produto</span>
      <input type="search" name="q" value="<?= e($filtro['q']) ?>" placeholder="Nome, marca ou código" autocomplete="off" enterkeyhint="search" data-busca>
    </label>
    <div class="filtros__linha">
      <div class="chips" role="group" aria-label="Categoria">
        <button type="submit" name="cat" value="" class="chip" aria-pressed="<?= $filtro['cat'] === '' ? 'true' : 'false' ?>" data-chip="">Todos <b><?= count($produtos) ?></b></button>
        <?php foreach ($categorias as $c): ?>
        <button type="submit" name="cat" value="<?= e($c['id']) ?>" class="chip" aria-pressed="<?= $filtro['cat'] === $c['id'] ? 'true' : 'false' ?>" data-chip="<?= e($c['id']) ?>"><?= e(nomeCurtoCategoria($c)) ?> <b><?= count($porCat[$c['id']] ?? []) ?></b></button>
        <?php endforeach; ?>
      </div>
      <label class="ver">
        <span>Mostrar</span>
        <select name="ver" data-ver>
          <?php foreach (['' => 'Todos', 'site' => 'Só os do site', 'ocultos' => 'Só os ocultos', 'destaques' => 'Só destaques'] as $k => $r): ?>
          <option value="<?= e($k) ?>"<?= $filtro['ver'] === $k ? ' selected' : '' ?>><?= e($r) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php if ($filtro['cat'] !== ''): ?><input type="hidden" name="cat" value="<?= e($filtro['cat']) ?>" data-cat-atual><?php endif; ?>
      <?php if ($sel): ?><input type="hidden" name="sel" value="1"><?php endif; ?>
      <noscript><button class="btn btn--linha btn--p" type="submit">Filtrar</button></noscript>
    </div>
  </form>
  <div class="lista-topo">
    <p class="contagem<?= $achados === count($produtos) ? ' is-todos' : '' ?>" data-contagem aria-live="polite"><?= $achados === count($produtos) ? 'Mostrando todos os ' . $achados . ' produtos.' : $achados . ($achados === 1 ? ' produto encontrado.' : ' produtos encontrados.') ?></p>
    <?= atalhoCategorias() /* só administradores (lib/visoes-categorias.php) */ ?>
    <?php if ($produtos): ?>
    <a class="btn btn--linha btn--p lista-topo__sel" href="<?= e($sel ? linkLista($filtro) : linkLista($filtro, ['sel' => '1'])) ?>" data-selecionar aria-pressed="<?= $sel ? 'true' : 'false' ?>" title="Marcar vários produtos e mudar todos de uma vez"><?= icone('marcar') ?><span data-selecionar-txt><?= $sel ? 'Sair da seleção' : 'Selecionar' ?></span></a>
    <?php endif; ?>
  </div>
  <p class="lote-dica" data-lote-dica><?= icone('marcar') ?><span><b>Toque nos produtos para marcar.</b> Depois escolha, na barra de baixo, o que fazer com todos os marcados: estoque, destaque, no site, mover para outra categoria ou excluir.</span></p>
  <div class="dica" data-capa="<?= e($ctx['capa']) ?>" data-capa-site="<?= e($ctx['capaSite']) ?>" data-capa-frase="<?= e($ctx['capaFrase']) ?>">
    <?= icone('lugar') ?>
    <?php $dicaOrdem = '<span><b>Ordem no site:</b> Pronta entrega primeiro, depois os destaques, depois a ordem desta lista (para mudar, use <b>Mudar lugar</b>); Esgotado fica por último.'
        . '<span class="so-pc"> No computador, dá também para arrastar pela alça ⠿.</span></span>'
        . '<span><b>Página inicial:</b> mostra até ' . HOME_MAX_DESTAQUES . ' destaques, revezando as categorias (o de cima de cada categoria entra primeiro). Destaque além disso aparece como “fora da página inicial”.</span>'; ?>
    <p class="dica__texto"><?= $dicaOrdem ?></p>
    <?php /* No celular, a explicação fica fechada (uma linha): a lista de produtos aparece na primeira tela */ ?>
    <details class="dica__cel"><summary>Ordem no site e página inicial</summary><p><?= $dicaOrdem ?></p></details>
  </div>

  <div class="vazio" data-vazio<?= $achados ? ' hidden' : '' ?>>
    <p><b>Nenhum produto encontrado.</b> Tente outra palavra ou <a href="index.php">limpe os filtros</a>.</p>
    <p><a class="btn btn--verde btn--p" href="<?= e(link_('novo')) ?>"><?= icone('plus') ?>Cadastrar produto novo</a></p>
  </div>

  <?php foreach ($categorias as $c):
        $lista = $porCat[$c['id']] ?? [];
        if (!$lista) continue;
        $mostra = count(array_filter($lista, $passa)); ?>
  <section class="grupo" data-grupo="<?= e($c['id']) ?>" data-cat-nome="<?= e($c['nome']) ?>"<?= $mostra ? '' : ' hidden' ?> aria-labelledby="g-<?= e($c['id']) ?>">
    <h2 class="grupo__tit" id="g-<?= e($c['id']) ?>"><?= e($c['nome']) ?> <span><?= count($lista) ?></span></h2>
    <ol class="lista" role="list">
      <?php foreach ($lista as $p) itemProduto($p, $categorias, count($lista) > 1, $passa($p), $ctx); ?>
    </ol>
  </section>
  <?php endforeach; ?>
  <?php
        // Produtos com categoria que não existe mais (editados à mão): aparecem no fim para correção
        $orfaos = array_filter($produtos, static fn($p) => !categoriaPorId($categorias, (string) ($p['categoria'] ?? '')));
        if ($orfaos): ?>
  <section class="grupo grupo--alerta" data-grupo="">
    <h2 class="grupo__tit">Sem categoria válida <span><?= count($orfaos) ?></span></h2>
    <ol class="lista" role="list">
      <?php foreach ($orfaos as $p) itemProduto($p, $categorias, false, true, $ctx); ?>
    </ol>
  </section>
  <?php endif; ?>
  <?php if ($produtos) barraLote($categorias, $filtro, $sel, $nMarcados, $achados, $todosDaLista); ?>
  <?php if ($produtos) dialogoLugar('produtos', $filtro); ?>
<?php
    }, ['menu' => 'produtos', 'classe' => 'pg-lista' . ($sel ? ' is-selecionando' : '')]);
}

/* ------------------------------------------------------------------
   Edição em lote: barra de ações (presa embaixo) e confirmação de excluir
   Sem JavaScript: formulário normal (caixinhas com form="f-lote", menus em
   <details>, um botão por ação). Com JavaScript (assets/painel.js): contador,
   marcar todos desta lista, estoque/destaque/no site sem recarregar.
   ------------------------------------------------------------------ */
/** Texto do "Marcar todos os N desta lista" (o JavaScript usa a mesma regra). */
function textoMarcarTodos(int $n): string
{
    return $n === 1 ? 'Marcar o produto desta lista' : 'Marcar todos os ' . $n . '<span class="lote__todos-mais"> desta lista</span>';
}

function barraLote(array $categorias, array $filtro, bool $sel, int $nMarcados, int $achados, bool $todosDaLista = false): void
{
    $destinos = array_values(array_filter($categorias, static fn($c) => !empty($c['subcategorias'])));
    ?>
  <form id="f-lote" class="lote" method="post" action="<?= e(link_('lote')) ?>" data-lote data-dias="<?= (int) cfg('copias_protegidas_dias') ?>" data-max="<?= LOTE_MAX ?>" aria-labelledby="lote-tit"<?= $sel ? '' : ' hidden' ?>>
    <?= campoCsrf() ?>
    <?php foreach (['q', 'cat', 'ver'] as $k): ?><input type="hidden" name="<?= $k ?>" value="<?= e($filtro[$k]) ?>" data-lote-filtro="<?= $k ?>"><?php endforeach; ?>
    <h2 class="sr-only" id="lote-tit">O que fazer com os produtos marcados</h2>
    <div class="lote__in">
      <div class="lote__topo">
        <a class="lote__sair" href="<?= e(linkLista($filtro)) ?>" data-lote-sair title="Sair da seleção"><?= icone('x') ?><span class="sr-only">Sair da seleção</span></a>
        <p class="lote__conta" data-lote-conta aria-live="polite"><?= $nMarcados ? e(qtdLote($nMarcados, 'marcado', 'marcados')) : 'Marque os produtos e escolha a ação' /* sem JavaScript o número não muda ao marcar; com ele, vira o contador ao vivo */ ?></p>
        <?php if ($achados): ?><a class="lote__todos" href="<?= e(linkLista($filtro, $todosDaLista ? ['sel' => '1'] : ['sel' => '1', 'todos' => '1'])) ?>" data-lote-todos><?= $todosDaLista ? 'Desmarcar todos' : textoMarcarTodos($achados) ?></a><?php endif; ?>
      </div>
      <div class="lote__acoes">
        <details class="lote__menu" data-lote-menu>
          <summary class="lote__bt"><?= icone('box') ?><span>Estoque</span></summary>
          <div class="lote__pop">
            <p class="lote__pop-tit">Estoque dos marcados</p>
            <?php foreach (ESTOQUES as $k => $r): ?>
            <button type="submit" name="acao" value="estoque:<?= e($k) ?>" class="lote__op lote__op--<?= e($k) ?> estoque--<?= e($k) ?>" data-rotulo="<?= e($r) ?>"><?= e($r) ?></button>
            <?php endforeach; ?>
          </div>
        </details>
        <details class="lote__menu" data-lote-menu>
          <summary class="lote__bt"><?= icone('star') ?><span>Destaque</span></summary>
          <div class="lote__pop">
            <p class="lote__pop-tit">Destaque dos marcados</p>
            <button type="submit" name="acao" value="destaque:1" class="lote__op"><?= icone('star') ?>Pôr em destaque</button>
            <button type="submit" name="acao" value="destaque:0" class="lote__op">Tirar do destaque</button>
            <p class="lote__pop-ajuda">A página inicial mostra até <?= HOME_MAX_DESTAQUES ?> destaques, revezando as categorias.</p>
          </div>
        </details>
        <details class="lote__menu" data-lote-menu>
          <summary class="lote__bt"><?= icone('eye') ?><span>No site</span></summary>
          <div class="lote__pop">
            <p class="lote__pop-tit">Os marcados no site</p>
            <button type="submit" name="acao" value="visivel:1" class="lote__op"><?= icone('eye') ?>Mostrar no site</button>
            <button type="submit" name="acao" value="visivel:0" class="lote__op"><?= icone('eye-off') ?>Esconder do site</button>
            <p class="lote__pop-ajuda">Esconder não apaga: o produto continua aqui no painel, com o selo “Fora do site”.</p>
          </div>
        </details>
        <?php if ($destinos): ?>
        <details class="lote__menu lote__menu--mover" data-lote-menu>
          <summary class="lote__bt"><?= icone('mover') ?><span>Mover</span></summary>
          <div class="lote__pop">
            <label class="lote__pop-tit" for="lote-destino">Mover os marcados para</label>
            <select id="lote-destino" name="destino" data-lote-destino>
              <option value="">Escolha a categoria e o tipo…</option>
              <?php foreach ($destinos as $c): ?>
              <optgroup label="<?= e($c['nome']) ?>">
                <?php foreach ($c['subcategorias'] as $s): ?><option value="<?= e($c['id'] . '|' . $s) ?>"><?= e($s) ?></option><?php endforeach; ?>
              </optgroup>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="acao" value="mover" class="btn btn--verde btn--p"><?= icone('mover') ?>Mover</button>
            <p class="lote__pop-ajuda">Quem muda de categoria entra logo depois dos outros do mesmo tipo (para mudar, use Mudar lugar).</p>
          </div>
        </details>
        <?php endif; ?>
        <button type="submit" name="acao" value="excluir" class="lote__bt lote__bt--perigo"><?= icone('trash') ?><span>Excluir</span></button>
      </div>
    </div>
  </form>
<?php
}

/** Confirmação de excluir em lote sem JavaScript (com JavaScript, a janela pede a mesma confirmação). */
function telaConfirmarLote(array $prods, array $filtro): void
{
    $n = count($prods);
    $titulo = $n === 1 ? 'Excluir produto?' : 'Excluir ' . $n . ' produtos?';
    $voltar = linkLista($filtro, ['sel' => '1']);
    pagina($titulo, static function () use ($prods, $n, $titulo, $filtro, $voltar): void { ?>
  <section class="confirmar">
    <?= icone('alert', 'i i--grande') ?>
    <h1><?= e($titulo) ?></h1>
    <p><?= e(textoExcluirLote($prods)) ?></p>
    <form method="post" action="<?= e(link_('lote')) ?>" class="confirmar__bts">
      <?= campoCsrf() ?>
      <?php foreach ($prods as $p): ?><input type="hidden" name="ids[]" value="<?= e((string) $p['id']) ?>"><?php endforeach; ?>
      <?php foreach (['q', 'cat', 'ver'] as $k): ?><input type="hidden" name="<?= $k ?>" value="<?= e($filtro[$k]) ?>"><?php endforeach; ?>
      <input type="hidden" name="acao" value="excluir">
      <input type="hidden" name="confirmado" value="1">
      <a class="btn btn--linha" href="<?= e($voltar) ?>">Cancelar</a>
      <button class="btn btn--perigo" type="submit"><?= $n === 1 ? 'Sim, excluir' : 'Sim, excluir os ' . $n ?></button>
    </form>
  </section>
<?php }, ['menu' => 'produtos']);
}

function nomeCurtoCategoria(array $c): string
{
    return categoriaCurta($c);   // lib/catalogo.php: o nome curto original enquanto a categoria não foi renomeada
}

function formRapido(array $p, string $campo, string $valor, string $conteudo, string $classe = '', string $attrs = ''): void
{
    ?><form method="post" action="<?= e(link_('rapido')) ?>" class="rapido <?= e($classe) ?>" data-rapido<?= $attrs ?>>
          <?= campoCsrf() ?><input type="hidden" name="id" value="<?= e($p['id']) ?>"><input type="hidden" name="campo" value="<?= e($campo) ?>"><?= $conteudo ?>
        </form><?php
}

/** Texto do selo de destaque: dentro ou fora da página inicial. */
function textoSeloDestaque(string $id, array $ctx): array
{
    if ($id === ($ctx['capaSite'] ?? $ctx['capa'] ?? '')) return ['Destaque', ''];   // a capa que o site mostra de fato
    return in_array($id, $ctx['home'] ?? [], true)
        ? ['Destaque · página inicial', '']
        : ['Destaque · fora da página inicial', ' is-fora'];
}

/** $lugar: o produto tem categoria válida com 2 ou mais produtos (dá para mudar o lugar). */
function itemProduto(array $p, array $categorias, bool $lugar, bool $mostra, array $ctx = []): void
{
    $id = (string) $p['id'];
    $nome = nomeCompleto($p);
    $vis = ($p['visivel'] ?? true) !== false;
    $dest = !empty($p['destaque']);
    // Selo e confirmações seguem a capa que o site mostra de fato; o escolhido em Empresa → Capa do site que
    // não pode aparecer (escondido, sem recorte) ganha um selo próprio
    $capa = $id === ($ctx['capaSite'] ?? $ctx['capa'] ?? '');
    $escolhidoFora = !$capa && $id === ($ctx['capa'] ?? '');
    [$txtDest, $clsDest] = textoSeloDestaque($id, $ctx);
    $est = (string) ($p['estoque'] ?? 'consulte');
    $img = is_string($p['imagem'] ?? null) && preg_match(RE_ARQUIVO_FOTO, $p['imagem']) ? urlFoto($p['imagem']) : '';
    $editar = link_('editar', ['id' => $id]);
    // Edição em lote: caixinha (só aparece no modo "Selecionar"); fica fora do formulário da barra, ligada a ele por form="f-lote"
    $marcado = isset($ctx['marcados'][$id]) || (!empty($ctx['todos']) && $mostra);
    ?>
      <li class="item<?= $vis ? '' : ' is-oculto' ?><?= $marcado ? ' is-marcado' : '' ?>" id="p-<?= e($id) ?>" data-id="<?= e($id) ?>"<?= $capa ? ' data-na-capa' : '' ?> data-cat="<?= e($p['categoria'] ?? '') ?>" data-busca="<?= e(textoBusca($p, $categorias)) ?>" data-visivel="<?= $vis ? '1' : '0' ?>" data-destaque="<?= $dest ? '1' : '0' ?>" data-nome="<?= e($nome) ?>" data-sub="<?= e($p['subcategoria'] ?? '') ?>"<?= $mostra ? '' : ' hidden' ?>>
        <?php if ($lugar): ?><span class="alca" data-alca aria-hidden="true" tabindex="-1" title="Arraste para mudar o lugar"><?= icone('arrastar') ?></span><?php endif; ?>
        <label class="item__marcar"><input type="checkbox" name="ids[]" value="<?= e($id) ?>" form="f-lote" data-marcar<?= $marcado ? ' checked' : '' ?>><span class="sr-only">Marcar <?= e($nome) ?></span></label>
        <a class="item__foto" href="<?= e($editar) ?>" tabindex="-1" aria-hidden="true">
          <?php if ($img): ?><img src="<?= e($img) ?>" alt="" loading="lazy" decoding="async" width="80" height="80"><?php else: ?><span class="item__semfoto"><?= icone('camera') ?></span><?php endif; ?>
        </a>
        <div class="item__txt">
          <p class="item__marca"><?= e($p['marca'] ?? '') ?><span> · <?= e($p['subcategoria'] ?? '') ?></span></p>
          <h3 class="item__nome"><a href="<?= e($editar) ?>"><?= e($p['nome'] ?? $id) ?></a></h3>
          <p class="item__selos">
            <span class="selo selo--estoque estoque--<?= e($est) ?>" data-selo-estoque><?= e(ESTOQUES_CURTO[$est] ?? $est) ?></span>
            <?php if ($capa): ?><span class="selo selo--capa" title="A balança grande do topo da página inicial do site">Na capa do site</span><?php endif; ?>
            <?php if ($escolhidoFora): ?><span class="selo selo--capa-fora" title="Escolhido em Empresa → Capa do site, mas não pode aparecer: o topo do site mostra outro produto no lugar">Escolhido para a capa (fora do site)</span><?php endif; ?>
            <span class="selo selo--oculto" data-selo-oculto<?= $vis ? ' hidden' : '' ?>>Fora do site</span>
            <span class="selo selo--destaque<?= e($clsDest) ?>" data-selo-destaque<?= $dest ? '' : ' hidden' ?> title="A página inicial mostra até <?= HOME_MAX_DESTAQUES ?> destaques, revezando as categorias"><?= e($txtDest) ?></span>
            <?php if (($p['condicao'] ?? '') === 'seminovo'): ?><span class="selo">Seminovo</span><?php endif; ?>
            <?php if (!empty($p['preco'])): ?><span class="selo selo--preco"><?= e($p['preco']) ?></span><?php endif; ?>
          </p>
        </div>
        <div class="item__rapido">
          <?php
          $ops = '';
          foreach (ESTOQUES_CURTO as $k => $r) $ops .= '<option value="' . e($k) . '"' . ($k === $est ? ' selected' : '') . ' title="No site: ' . e(ESTOQUES[$k]) . '">' . e($r) . '</option>';
          formRapido($p, 'estoque', $est, '<label class="estoque estoque--' . e($est) . '" data-estoque><span class="sr-only">Estoque de ' . e($nome) . '</span><select name="valor" data-auto>' . $ops . '</select></label><noscript><button type="submit" class="btn btn--linha btn--p">OK</button></noscript>', 'rapido--estoque');
          formRapido($p, 'destaque', $dest ? '0' : '1', '<input type="hidden" name="valor" value="' . ($dest ? '0' : '1') . '"><button type="submit" class="tg tg--destaque" aria-pressed="' . ($dest ? 'true' : 'false') . '" title="Destaque: sobe no catálogo e entra na página inicial (cabem ' . HOME_MAX_DESTAQUES . ')">' . icone('star') . '<span>Destaque</span><span class="sr-only"> — ' . e($nome) . '</span></button>', 'rapido--destaque');
          // Esconder o produto da capa pede confirmação (o link da capa deixaria de abrir os detalhes)
          $conf = $capa ? ' data-confirmar-se="0" data-confirmar="' . e('Esconder “' . $nome . '” do site? Ele é o produto da capa. ' . ($ctx['capaFrase'] ?? fraseCapaFora())) . '" data-confirmar-ok="Esconder" data-confirmar-tit="Esconder o produto da capa?"' : '';
          formRapido($p, 'visivel', $vis ? '0' : '1', '<input type="hidden" name="valor" value="' . ($vis ? '0' : '1') . '"><button type="submit" class="tg tg--visivel" aria-pressed="' . ($vis ? 'true' : 'false') . '" title="Mostrar no site">' . icone('eye', 'i i--on') . icone('eye-off', 'i i--off') . '<span>No site</span><span class="sr-only"> — ' . e($nome) . '</span></button>', 'rapido--visivel', $conf);
          ?>
        </div>
        <div class="item__acoes">
          <?php if ($lugar): ?>
          <a class="btn btn--linha btn--p item__lugar" href="<?= e(link_('lugar', ['id' => $id] + array_filter($ctx['filtro'] ?? [], static fn($x) => $x !== ''))) ?>" data-lugar><?= icone('lugar') ?><span><span class="item__lugar-mudar">Mudar </span>lugar</span><span class="sr-only"> de <?= e($nome) ?></span></a>
          <?php else: ?>
          <?php $catP = categoriaPorId($categorias, (string) ($p['categoria'] ?? '')); /* o motivo aparece ao tocar (painel.js) e ao passar o mouse */ ?>
          <span class="btn btn--linha btn--p item__lugar is-off" aria-disabled="true" title="<?= e($catP ? 'Ele é o único de ' . $catP['nome'] . ': não há outro lugar.' : ERRO_SEM_CATEGORIA) ?>" data-lugar-off><?= icone('lugar') ?><span><span class="item__lugar-mudar">Mudar </span>lugar</span><span class="sr-only"><?= $catP ? ' (é o único da categoria)' : ' (escolha a categoria em Editar)' ?></span></span>
          <?php endif; ?>
          <a class="btn btn--linha btn--p item__editar" href="<?= e($editar) ?>"><?= icone('edit') ?><span>Editar</span><span class="sr-only"> <?= e($nome) ?></span></a>
          <form method="post" action="<?= e(link_('excluir')) ?>" data-confirmar="<?= e(textoExcluir($p, $ctx['capaSite'] ?? null, $ctx['capaFrase'] ?? null)) ?>" data-confirmar-ok="Excluir">
            <?= campoCsrf() ?><input type="hidden" name="id" value="<?= e($id) ?>">
            <button type="submit" class="ic ic--perigo" aria-label="Excluir <?= e($nome) ?>" title="Excluir"><?= icone('trash') ?></button>
          </form>
        </div>
      </li>
<?php
}

/* ------------------------------------------------------------------
   Formulário do produto
   ------------------------------------------------------------------ */
function telaProduto(array $cat, ?array $p, array $erros = [], ?string $original = null): void
{
    $novo = $original === null;
    $p = $p ?? ['id' => '', 'nome' => '', 'marca' => '', 'categoria' => '', 'subcategoria' => '', 'resumo' => '', 'descricao' => '',
        'especificacoes' => [], 'imagem' => '', 'estoque' => 'consulte', 'condicao' => 'novo', 'preco' => '', 'destaque' => false, 'visivel' => true];
    $categorias = $cat['categorias'];
    $marcas = array_values(array_unique(array_filter(array_map(static fn($x) => (string) ($x['marca'] ?? ''), $cat['produtos']))));
    sort($marcas, SORT_NATURAL | SORT_FLAG_CASE);
    $specs = is_array($p['especificacoes'] ?? null) ? $p['especificacoes'] : [];
    $img = is_string($p['imagem'] ?? null) && $p['imagem'] !== '' && preg_match(RE_ARQUIVO_FOTO, $p['imagem']) ? urlFoto($p['imagem']) : '';
    $titulo = $novo ? 'Novo produto' : 'Editar produto';
    $acao = link_('salvar', $novo ? [] : ['id' => $original]);
    $err = static fn(string $c): string => isset($erros[$c]) ? '<p class="campo__erro" id="erro-' . e($c) . '">' . icone('alert') . e($erros[$c]) . '</p>' : '';
    $inv = static fn(string $c): string => isset($erros[$c]) ? ' aria-invalid="true" aria-describedby="erro-' . e($c) . '"' : '';
    $capa = !$novo && $original === produtoCapa();
    $capaPainel = $capa && produtoCapaAjustes() !== null;   // escolhida em Empresa → Capa do site (foto e etiquetas de lá)
    // Categoria sem nenhuma subcategoria não serve (subcategoria é obrigatória), a não ser a do próprio produto
    $categoriasForm = array_values(array_filter($categorias, static fn($c) => !empty($c['subcategorias']) || ($p['categoria'] ?? '') === $c['id']));
    // Situação do destaque na página inicial (produto já salvo)
    $home = destaquesDaHome($cat['produtos']);
    $situacao = '';
    if ($capa) $situacao = 'Este é o produto da capa: já aparece no topo da página inicial.';
    elseif (!$novo && !empty($p['destaque']) && ($p['visivel'] ?? true) !== false) {
        $situacao = in_array($original, $home, true) ? 'Hoje: aparece na página inicial.' : 'Hoje: fora da página inicial (já há ' . HOME_MAX_DESTAQUES . ' destaques na frente dele).';
    }

    // Produto novo: "Junto da mesma subcategoria" (padrão) ou "Em 1º na categoria"; a escolha volta marcada depois de um erro
    $lugar = ($_POST['lugar'] ?? '') === 'inicio' ? 'inicio' : 'tipo';

    pagina($titulo, static function () use ($novo, $p, $categoriasForm, $marcas, $specs, $img, $titulo, $acao, $erros, $err, $inv, $original, $capa, $capaPainel, $situacao, $lugar): void {
        $categorias = $categoriasForm;
        $siteLink = !$novo ? '<a class="btn btn--linha btn--p" href="' . e(cfg('url_site') . 'produtos.html#p=' . rawurlencode((string) $original)) . '" target="_blank" rel="noopener">' . icone('ext') . 'Ver no site</a>' : '';
        ?>
  <p class="migalha"><a href="index.php#p-<?= e((string) $original) ?>"><?= icone('up', 'i i--esq') ?>Produtos</a></p>
  <?php cabecalhoPagina($novo ? 'Cadastro' : nomeCompleto($p), $titulo, '', $siteLink); ?>
  <?php if ($erros): ?>
  <div class="aviso aviso--erro" role="alert" tabindex="-1" data-erros>
    <?= icone('alert') ?>
    <div>
      <p><b>O produto não foi salvo.</b> Confira:</p>
      <ul><?php foreach ($erros as $c => $m): ?><li><a href="#f-<?= e($c === 'especificacoes' ? 'spec' : $c) ?>"><?= e($m) ?></a></li><?php endforeach; ?></ul>
    </div>
  </div>
  <?php endif; ?>
  <?php if ($capa): ?>
  <?php if ($capaPainel): ?>
  <div class="aviso aviso--info aviso--capa"><?= icone('alert') ?><p><b>Produto da capa do site.</b> O produto grande do topo da página inicial é este (foto e etiquetas escolhidas em <?php if (ehAdmin()): ?><a href="<?= e(link_('empresa') . '#e-capa') ?>">Empresa → Capa do site</a><?php else: ?>Empresa → Capa do site<?php endif; ?>). Se esconder, excluir, mudar o código ou trocar a foto por uma sem fundo transparente, a página inicial volta a mostrar a <?= e(NOME_CAPA_ORIGINAL) ?> (a capa original).</p></div>
  <?php else: ?>
  <div class="aviso aviso--info aviso--capa"><?= icone('alert') ?><p><b>Produto da capa do site.</b> A balança grande do topo da página inicial é este produto (foto e link fixos). Se esconder, excluir ou mudar o código, a foto da capa continua lá, mas o link dela deixa de abrir os detalhes.</p></div>
  <?php endif; ?>
  <?php endif; ?>
  <form class="fp" method="post" action="<?= e($acao) ?>" enctype="multipart/form-data" data-form-produto data-form-enviar data-rascunho="<?= e($novo ? 'novo' : 'editar:' . $original) ?>"<?= $capa ? ' data-capa="' . e((string) $original) . '" data-capa-frase="' . e(fraseCapaFora()) . '"' : '' ?>>
    <?= campoCsrf() ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) cfg('foto_max_bytes') ?>">
    <div class="fp__lado">
      <fieldset class="caixa caixa--foto">
        <legend>Foto</legend>
        <p class="foto__rot" id="foto-rot">Como fica no cartão do site</p>
        <div class="foto<?= $img ? '' : ' foto--vazia' ?>" data-foto aria-describedby="foto-rot">
          <img class="foto__img" src="<?= e($img) ?>" alt="<?= $img ? 'Foto atual do produto' : '' ?>" data-foto-previa<?= $img ? '' : ' hidden' ?>>
          <p class="foto__vazio" data-foto-vazio<?= $img ? ' hidden' : '' ?>><?= icone('camera', 'i i--grande') ?><span>Nenhuma foto ainda</span></p>
        </div>
        <label class="btn btn--linha btn--largo foto__btn" for="f-foto"><?= icone('camera') ?><span data-foto-rotulo><?= $img ? 'Trocar foto' : 'Escolher foto' ?></span></label>
        <input class="foto__input" id="f-foto" type="file" name="foto" accept="image/jpeg,image/png,image/webp"<?= $novo ? ' required' : '' ?><?= $inv('foto') ?> data-foto-input data-min="<?= (int) (cfg('foto_min_lado') ?? 300) ?>" data-max-bytes="<?= (int) cfg('foto_max_bytes') ?>">
        <?= $err('foto') ?>
        <p class="ajuda" data-foto-info>Pode ser foto do celular: ela é girada e reduzida sozinha. Para ficar como as outras do site: <b>celular deitado</b>, produto ocupando quase toda a foto, sobre <b>fundo branco ou liso</b>. JPG, PNG ou WebP, até 8 MB.</p>
      </fieldset>
      <fieldset class="caixa">
        <legend>No site</legend>
        <label class="chave">
          <input type="checkbox" name="visivel" value="1"<?= ($p['visivel'] ?? true) !== false ? ' checked' : '' ?>>
          <span class="chave__trilho" aria-hidden="true"></span>
          <span class="chave__txt"><b>Visível no site</b><small>Desligue para esconder sem apagar.<?= $capa ? ' Atenção: é o produto da capa.' : '' ?></small></span>
        </label>
        <label class="chave">
          <input type="checkbox" name="destaque" value="1"<?= !empty($p['destaque']) ? ' checked' : '' ?>>
          <span class="chave__trilho" aria-hidden="true"></span>
          <span class="chave__txt"><b>Destaque</b><small>Sobe no catálogo e entra na página inicial, que mostra até <?= HOME_MAX_DESTAQUES ?> destaques revezando as categorias (Pronta entrega continua vindo antes).</small></span>
        </label>
        <?php if ($situacao !== ''): ?><p class="ajuda chave__situacao"><?= e($situacao) ?></p><?php endif; ?>
      </fieldset>
    </div>

    <div class="fp__principal">
      <fieldset class="caixa">
        <legend>Produto</legend>
        <div class="campo">
          <label for="f-nome">Nome do produto</label>
          <input id="f-nome" name="nome" required maxlength="<?= LIMITES['nome'] ?>" value="<?= e($p['nome'] ?? '') ?>" autocomplete="off" placeholder="Ex.: Prix 7E"<?= $inv('nome') ?> data-slug-origem>
          <?= $err('nome') ?>
        </div>
        <div class="campo">
          <label for="f-marca">Marca</label>
          <input id="f-marca" name="marca" required maxlength="<?= LIMITES['marca'] ?>" value="<?= e($p['marca'] ?? '') ?>" list="lista-marcas" autocomplete="off" placeholder="Ex.: Toledo"<?= $inv('marca') ?> data-slug-origem>
          <datalist id="lista-marcas"><?php foreach ($marcas as $m): ?><option value="<?= e($m) ?>"><?php endforeach; ?></datalist>
          <?= $err('marca') ?>
        </div>
        <div class="campos-2">
          <div class="campo">
            <label for="f-categoria">Categoria</label>
            <select id="f-categoria" name="categoria" required<?= $inv('categoria') ?> data-categoria>
              <option value="">Escolha…</option>
              <?php foreach ($categorias as $c): ?><option value="<?= e($c['id']) ?>"<?= ($p['categoria'] ?? '') === $c['id'] ? ' selected' : '' ?>><?= e($c['nome']) ?></option><?php endforeach; ?>
            </select>
            <?= $err('categoria') ?>
          </div>
          <div class="campo">
            <label for="f-subcategoria">Subcategoria</label>
            <select id="f-subcategoria" name="subcategoria" required<?= $inv('subcategoria') ?> data-subcategoria>
              <option value="">Escolha…</option>
              <?php foreach ($categorias as $c): ?>
              <optgroup label="<?= e($c['nome']) ?>" data-cat="<?= e($c['id']) ?>">
                <?php foreach ($c['subcategorias'] as $s): ?><option value="<?= e($s) ?>"<?= ($p['categoria'] ?? '') === $c['id'] && ($p['subcategoria'] ?? '') === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
              </optgroup>
              <?php endforeach; ?>
            </select>
            <?= $err('subcategoria') ?>
          </div>
        </div>
        <?php if ($novo): ?>
        <div class="campo">
          <p class="rotulo" id="r-lugar">Lugar na lista</p>
          <div class="opcoes" role="radiogroup" aria-labelledby="r-lugar">
            <label class="opcao"><input type="radio" name="lugar" value="tipo"<?= $lugar !== 'inicio' ? ' checked' : '' ?>><span>Junto da mesma subcategoria</span></label>
            <label class="opcao"><input type="radio" name="lugar" value="inicio"<?= $lugar === 'inicio' ? ' checked' : '' ?>><span>Em 1º na categoria</span></label>
          </div>
          <p class="ajuda">Junto da mesma subcategoria: logo depois dos outros do mesmo tipo (um fatiador novo entra depois dos outros fatiadores). Em 1º: antes de todos da categoria. Para mudar depois: Produtos → Mudar lugar.</p>
        </div>
        <?php else: ?>
        <p class="ajuda">Se trocar a categoria, ele entra logo depois dos outros da mesma subcategoria na categoria nova. Para mudar o lugar: Produtos → Mudar lugar.</p>
        <?php endif; ?>
        <?= ajudaCategoriasNoForm() /* só administradores (lib/visoes-categorias.php) */ ?>
        <div class="campo">
          <label for="f-resumo">Resumo <small>uma frase, aparece no cartão</small></label>
          <textarea id="f-resumo" name="resumo" rows="2" required maxlength="<?= LIMITES['resumo'] ?>"<?= $inv('resumo') ?> data-contador><?= e($p['resumo'] ?? '') ?></textarea>
          <?= $err('resumo') ?>
        </div>
        <div class="campo">
          <label for="f-descricao">Descrição <small>aparece ao abrir os detalhes</small></label>
          <textarea id="f-descricao" name="descricao" rows="6" maxlength="<?= LIMITES['descricao'] ?>"<?= $inv('descricao') ?> data-contador><?= e($p['descricao'] ?? '') ?></textarea>
          <?= $err('descricao') ?>
        </div>
      </fieldset>

      <fieldset class="caixa" id="f-spec">
        <legend>Ficha técnica</legend>
        <p class="ajuda ajuda--topo">As 3 primeiras linhas aparecem no cartão do produto. Linha vazia é ignorada.</p>
        <?= $err('especificacoes') ?>
        <ol class="specs" role="list" data-specs>
          <?php
          $linhas = $specs ?: [['', ''], ['', ''], ['', '']];
          foreach ($linhas as $i => $s) linhaSpec($i + 1, (string) ($s[0] ?? ''), (string) ($s[1] ?? ''));
          ?>
        </ol>
        <template data-spec-modelo><?php linhaSpec(0, '', ''); ?></template>
        <button type="button" class="btn btn--linha btn--p" data-spec-adicionar hidden><?= icone('plus') ?>Adicionar linha</button>
      </fieldset>

      <fieldset class="caixa">
        <legend>Venda</legend>
        <div class="campo">
          <p class="rotulo" id="r-estoque">Estoque <small>selo que aparece no site</small></p>
          <div class="opcoes opcoes--4" role="radiogroup" aria-labelledby="r-estoque">
            <?php foreach (ESTOQUES as $k => $r): ?>
            <label class="opcao opcao--<?= e($k) ?>"><input type="radio" name="estoque" value="<?= e($k) ?>"<?= ($p['estoque'] ?? 'consulte') === $k ? ' checked' : '' ?> required><span><?= e($r) ?></span></label>
            <?php endforeach; ?>
          </div>
          <?= $err('estoque') ?>
        </div>
        <div class="campo">
          <p class="rotulo" id="r-condicao">Condição</p>
          <div class="opcoes" role="radiogroup" aria-labelledby="r-condicao">
            <?php foreach (CONDICOES as $k => $r): ?>
            <label class="opcao"><input type="radio" name="condicao" value="<?= e($k) ?>"<?= ($p['condicao'] ?? 'novo') === $k ? ' checked' : '' ?> required><span><?= e($r) ?></span></label>
            <?php endforeach; ?>
          </div>
          <?= $err('condicao') ?>
        </div>
        <div class="campo">
          <label for="f-preco">Preço <small>opcional; vazio mostra “Consulte”</small></label>
          <input id="f-preco" name="preco" maxlength="<?= LIMITES['preco'] ?>" value="<?= e($p['preco'] ?? '') ?>" placeholder="Ex.: R$ 2.490,00 à vista" inputmode="text" autocomplete="off"<?= $inv('preco') ?>>
          <?= $err('preco') ?>
        </div>
      </fieldset>

      <details class="caixa caixa--avancado"<?= isset($erros['id']) || isset($erros['nota']) ? ' open' : '' ?>>
        <summary>Avançado <small>código do produto e anotação</small></summary>
        <div class="campo">
          <label for="f-id">Código do produto</label>
          <?php $auto = $novo && (($p['id'] ?? '') === '' || ($_POST['id_auto'] ?? '') === '1'); ?>
          <input type="hidden" name="id_auto" value="<?= $auto ? '1' : '0' ?>" data-slug-auto-campo>
          <div class="prefixo"><span>produtos.html#p=</span><input id="f-id" name="id" maxlength="<?= LIMITES['id'] ?>" value="<?= e($p['id'] ?? '') ?>" pattern="[a-z0-9]+(-[a-z0-9]+)*" autocapitalize="off" autocomplete="off" spellcheck="false"<?= $inv('id') ?> data-slug data-slug-auto="<?= $auto ? '1' : '0' ?>"></div>
          <?= $err('id') ?>
          <p class="ajuda"><?= $novo ? 'Criado sozinho a partir da marca e do nome (pode deixar como está).' : 'Cuidado: se mudar, links antigos deste produto (WhatsApp, Google' . ($capa ? ' e a capa do site' : '') . ') deixam de abrir direto nele.' ?></p>
        </div>
        <div class="campo">
          <label for="f-nota">Anotação interna</label>
          <textarea id="f-nota" name="nota" rows="2" maxlength="<?= LIMITES['nota'] ?>"<?= $inv('nota') ?>><?= e($p['nota'] ?? '') ?></textarea>
          <?= $err('nota') ?>
          <p class="ajuda">Fica guardada só no painel (na pasta privada da hospedagem): não aparece no site nem no arquivo do catálogo.</p>
        </div>
      </details>
    </div>

    <div class="barra-salvar">
      <a class="btn btn--linha" href="index.php#p-<?= e((string) $original) ?>">Cancelar</a>
      <button class="btn btn--verde" type="submit" data-salvar><?= icone('check') ?><span><?= $novo ? 'Cadastrar' : 'Salvar' ?></span></button>
    </div>
  </form>
<?php
    }, ['menu' => $novo ? 'novo' : 'produtos', 'classe' => 'pg-produto']);
}

function linhaSpec(int $n, string $nome, string $valor): void
{
    ?>
          <li class="spec">
            <input name="spec_nome[]" value="<?= e($nome) ?>" maxlength="<?= LIMITES['spec_nome'] ?>" placeholder="Ex.: Capacidade" aria-label="Característica <?= $n ?: '' ?>" autocomplete="off"<?= $n === 1 ? ' id="f-spec-1"' : '' ?>>
            <input name="spec_valor[]" value="<?= e($valor) ?>" maxlength="<?= LIMITES['spec_valor'] ?>" placeholder="Ex.: 15 kg" aria-label="Valor <?= $n ?: '' ?>" autocomplete="off">
            <button type="button" class="ic" data-spec-remover aria-label="Remover linha <?= $n ?: '' ?>" hidden><?= icone('x') ?></button>
          </li>
<?php
}

/* ------------------------------------------------------------------
   Confirmação sem JavaScript (excluir / restaurar)
   ------------------------------------------------------------------ */
function telaConfirmar(string $titulo, string $texto, string $acao, array $campos, string $botao, string $voltar, string $menu = 'produtos'): void
{
    pagina($titulo, static function () use ($titulo, $texto, $acao, $campos, $botao, $voltar): void { ?>
  <section class="confirmar">
    <?= icone('alert', 'i i--grande') ?>
    <h1><?= e($titulo) ?></h1>
    <p><?= e($texto) ?></p>
    <form method="post" action="<?= e($acao) ?>" class="confirmar__bts">
      <?= campoCsrf() ?>
      <?php foreach ($campos as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
      <input type="hidden" name="confirmado" value="1">
      <a class="btn btn--linha" href="<?= e($voltar) ?>">Cancelar</a>
      <button class="btn btn--perigo" type="submit"><?= e($botao) ?></button>
    </form>
  </section>
<?php }, ['menu' => $menu]);
}

/* ------------------------------------------------------------------
   Cópias de segurança
   ------------------------------------------------------------------ */
function telaCopias(array $copias, ?int $totalAtual, array $idsAtuais = []): void
{
    $nomes = nomesDosUsuarios();
    pagina('Cópias de segurança', static function () use ($copias, $totalAtual, $idsAtuais, $nomes): void {
        migalhaConta();
        cabecalhoPagina('Desfazer alterações', 'Cópias de segurança',
            'Antes de cada mudança no catálogo o painel guarda uma cópia de como ele estava. Ficam sempre as ' . (int) cfg('copias_max')
            . ' mais recentes e tudo dos últimos ' . (int) cfg('copias_dias') . ' dias; as de <b>excluir</b>, <b>restaurar</b> e <b>aplicar uma atualização do catálogo</b> ficam ' . (int) cfg('copias_protegidas_dias') . ' dias. '
            . 'Mudanças seguidas do mesmo tipo (ordem, estoque, destaque, no site) feitas em menos de ' . (int) cfg('copias_juntar_min') . ' minutos ficam numa cópia só. '
            . '<b>Restaurar</b> volta o catálogo inteiro, com as fotos, e desfaz também o que foi feito depois. Para devolver só os produtos de uma exclusão (de um ou de vários de uma vez), use <b>Trazer de volta</b>.');
        abasCopias('produtos');
        if (!$copias): ?>
  <div class="vazio"><p><b>Ainda não há cópias.</b> Elas aparecem aqui depois da primeira alteração no catálogo.</p></div>
  <?php else: ?>
  <ol class="copias" role="list">
    <?php foreach ($copias as $i => $c):
        $titulo = $c['motivo'] !== '' ? $c['motivo'] : 'Antes de: ' . lcfirst($c['feitos'][0] ?? 'alteração');
        $mais = (int) $c['juntou'] - 1;
        $voltaSo = $c['tipo'] === 'excluir' && $c['alvo'] !== '' && !isset($idsAtuais[$c['alvo']]);
        // Exclusão em lote: os produtos dela que ainda estão fora do catálogo (trazer de volta todos ou um por um)
        $voltaLote = $c['tipo'] === 'excluir' && $c['alvo'] === '' ? array_diff_key($c['alvos'] ?? [], $idsAtuais) : []; ?>
    <li class="copia">
      <div class="copia__txt">
        <p class="copia__quando"><?= e(quando($c['criado'])) ?><?php if ($c['ultimo'] - $c['criado'] >= 60): ?><span class="copia__ate"> até <?= e(date('H:i', $c['ultimo'])) ?></span><?php endif; ?><?php if ($i === 0): ?> <span class="selo selo--destaque">Mais recente</span><?php endif; ?></p>
        <p class="copia__motivo"><?= e($titulo) ?><?php if ($mais > 0): ?> <span class="copia__mais">e mais <?= $mais ?> <?= $mais === 1 ? 'alteração' : 'alterações' ?> do mesmo tipo</span><?php endif; ?></p>
        <p class="copia__meta"><?= (int) $c['total'] ?> produtos · <?= (int) $c['visiveis'] ?> no site<?= $c['usuario'] !== '' ? ' · por ' . e(nomeDe($c['usuario'], $nomes)) : '' ?><?= in_array($c['tipo'], TIPOS_PROTEGIDOS, true) ? ' · guardada por ' . (int) cfg('copias_protegidas_dias') . ' dias' : '' ?></p>
        <?php if ($voltaLote): ?>
        <details class="copia__prods">
          <summary><?= count($voltaLote) === 1 ? 'Ver o produto que está fora do catálogo' : 'Ver os ' . count($voltaLote) . ' produtos que estão fora do catálogo' ?></summary>
          <ul role="list">
            <?php foreach ($voltaLote as $idV => $nomeV): ?>
            <li>
              <span><?= e($nomeV) ?></span>
              <form method="post" action="<?= e(link_('desfazer')) ?>" data-form-enviar>
                <?= campoCsrf() ?><input type="hidden" name="copia" value="<?= e($c['nome']) ?>"><input type="hidden" name="id" value="<?= e((string) $idV) ?>">
                <button class="btn btn--linha btn--p" type="submit"><?= icone('restore') ?>Trazer de volta<span class="sr-only"> “<?= e($nomeV) ?>”</span></button>
              </form>
            </li>
            <?php endforeach; ?>
          </ul>
        </details>
        <?php endif; ?>
      </div>
      <div class="copia__bts">
        <?php if ($voltaSo): ?>
        <form method="post" action="<?= e(link_('desfazer')) ?>" data-form-enviar>
          <?= campoCsrf() ?><input type="hidden" name="copia" value="<?= e($c['nome']) ?>"><input type="hidden" name="id" value="<?= e($c['alvo']) ?>">
          <button class="btn btn--verde btn--p" type="submit"><?= icone('restore') ?>Trazer de volta<span class="sr-only"> “<?= e($c['alvoNome']) ?>”</span></button>
        </form>
        <?php endif; ?>
        <?php if (count($voltaLote) > 1): ?>
        <form method="post" action="<?= e(link_('lote-desfazer')) ?>" data-form-enviar>
          <?= campoCsrf() ?><input type="hidden" name="copia" value="<?= e($c['nome']) ?>">
          <button class="btn btn--verde btn--p" type="submit"><?= icone('restore') ?>Trazer de volta os <?= count($voltaLote) ?></button>
        </form>
        <?php endif; ?>
        <form method="post" action="<?= e(link_('restaurar')) ?>" data-confirmar="<?= e(textoRestaurar($copias, $i, $totalAtual)) ?>" data-confirmar-ok="Restaurar" data-confirmar-tit="Restaurar esta cópia?">
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

function telaCatalogoIlegivel(string $motivo): void
{
    http_response_code(500);
    pagina('Catálogo com problema', static function () use ($motivo): void { ?>
  <section class="vazio-grande">
    <?= icone('alert', 'i i--grande') ?>
    <h1>Não consegui ler o catálogo</h1>
    <p>O arquivo <code>compartilhado/dados/produtos.js</code> não está no formato que o painel grava (<?= e($motivo) ?>). Isso acontece quando ele é editado à mão ou substituído por uma versão antiga.</p>
    <?php if (ehAdmin()): ?>
    <p>O jeito mais rápido de resolver é restaurar uma cópia de segurança. Se for a primeira vez que o painel roda, o arquivo precisa ser convertido (veja o LEIA-ME do painel).</p>
    <p><a class="btn btn--verde" href="<?= e(link_('copias')) ?>"><?= icone('restore') ?>Ver cópias de segurança</a></p>
    <?php else: ?>
    <p>Avise um administrador do painel: ele resolve restaurando uma cópia de segurança (Conta → Cópias de segurança).</p>
    <?php endif; ?>
  </section>
<?php }, ['menu' => 'produtos']);
}

/* ------------------------------------------------------------------
   Clientes (logos que aparecem na página Empresa e na página inicial)
   ------------------------------------------------------------------ */
/** Aviso de que o clientes.js ainda não está no servidor (vem no pacote de atualização). */
function avisoSemArquivoClientes(bool $curto = false): void
{
    ?>
  <div class="aviso aviso--info aviso--fixo" role="status">
    <?= icone('alert') ?>
    <p><?php if ($curto): ?><b>A lista de clientes ainda não foi instalada no servidor.</b> Se cadastrar agora, o painel cria a lista só com o que você cadastrar aqui (veja o aviso da tela Clientes).<?php else: ?><b>A lista de clientes ainda não foi instalada no servidor.</b> Falta o arquivo <code>compartilhado/dados/clientes.js</code>, que vem no pacote de atualização do site (<code>atualizacao.zip</code>). Até ele ser instalado, o site continua mostrando os logos antigos. Instale a atualização antes de cadastrar logos aqui: se cadastrar agora, o painel cria a lista só com o que você cadastrar.<?php endif; ?></p>
  </div>
<?php
}

/** Chapa branca do mesmo formato do site (3:2, logo em cor centralizado). */
function chapaLogo(?array $info, string $classe = ''): string
{
    if (!$info) return '<span class="chapa chapa--vazia ' . e($classe) . '">' . icone('camera') . '</span>';
    $dim = $info['w'] > 0 ? ' width="' . (int) $info['w'] . '" height="' . (int) $info['h'] . '"' : '';
    return '<span class="chapa' . ($info['sq'] ? ' chapa--sq' : '') . ' ' . e($classe) . '"><img src="' . e($info['url']) . '" alt="" loading="lazy" decoding="async"' . $dim . '></span>';
}

function telaClientes(array $dados): void
{
    $lista = $dados['clientes'];
    $comLogo = count(array_filter($lista, static fn($c) => infoLogo((string) ($c['logo'] ?? '')) !== null));
    pagina('Clientes', static function () use ($dados, $lista, $comLogo): void {
        cabecalhoPagina('Logos no site', 'Clientes',
            '<span><b data-n="clientes">' . $comLogo . '</b> ' . ($comLogo === 1 ? 'logo' : 'logos') . ' no site</span><span>página Empresa e página inicial</span>',
            '<a class="btn btn--verde" href="' . e(link_('cliente-novo')) . '">' . icone('plus') . 'Novo cliente</a>');
        if (!$dados['existe']) avisoSemArquivoClientes();
        ?>
  <div class="dica">
    <?= icone('lugar') ?>
    <p><span><b>Ordem no site:</b> a mesma desta lista (da esquerda para a direita, de cima para baixo). Para mudar, toque em <b>Mudar lugar</b>.<span class="so-pc"> No computador, dá também para arrastar pela alça ⠿.</span></span>
    <span><b>Logo bom:</b> PNG com fundo transparente (ou fundo branco). O painel corta as sobras e reduz sozinho; a prévia mostra a chapa branca do site.</span></p>
  </div>
  <?php if (!$lista): ?>
  <div class="vazio">
    <p><b>Nenhum cliente cadastrado ainda.</b></p>
    <p><a class="btn btn--verde btn--p" href="<?= e(link_('cliente-novo')) ?>"><?= icone('plus') ?>Cadastrar cliente</a></p>
  </div>
  <?php else: ?>
  <ol class="clientes" role="list" data-clientes>
    <?php foreach ($lista as $i => $c) itemCliente($c, $i, count($lista)); ?>
  </ol>
  <?php if (count($lista) > 1) dialogoLugar('clientes'); ?>
  <?php endif; ?>
  <p class="clientes__rodape">
    <?php if (ehAdmin()): ?><a href="<?= e(link_('copias', ['de' => 'clientes'])) ?>"><?= icone('restore') ?>Cópias de segurança da lista de clientes</a><?php endif; ?>
    <a href="<?= e(cfg('url_site') . 'empresa.html#clientes') ?>" target="_blank" rel="noopener"><?= icone('ext') ?>Ver os logos no site</a>
  </p>
<?php
    }, ['menu' => 'clientes', 'classe' => 'pg-clientes']);
}

function itemCliente(array $c, int $i, int $total): void
{
    $n = $i + 1;
    $nome = (string) ($c['nome'] ?? '');
    $logo = (string) ($c['logo'] ?? '');
    $info = infoLogo($logo);
    $ref = ['n' => (string) $n, 'logo' => $logo];
    $editar = link_('cliente-editar', $ref);
    $campos = campoCsrf() . '<input type="hidden" name="n" value="' . $n . '"><input type="hidden" name="logo" value="' . e($logo) . '">';
    ?>
    <li class="cli" id="c-<?= $n ?>" data-nome="<?= e($nome) ?>" data-logo="<?= e($logo) ?>">
      <?php if ($total > 1): ?><span class="alca" data-alca aria-hidden="true" tabindex="-1" title="Arraste para mudar o lugar"><?= icone('arrastar') ?></span><?php endif; ?>
      <a class="cli__chapa" href="<?= e($editar) ?>" tabindex="-1" aria-hidden="true"><?= chapaLogo($info) ?></a>
      <div class="cli__txt">
        <p class="cli__pos" data-pos><?= $n ?>º no site</p>
        <h3 class="cli__nome"><a href="<?= e($editar) ?>"><?= e($nome) ?></a></h3>
        <?php if (!$info): ?><p class="item__selos"><span class="selo selo--oculto"><?= $logo === '' ? 'Sem logo: não aparece no site' : 'Logo não encontrado no servidor' ?></span></p><?php endif; ?>
      </div>
      <div class="cli__acoes">
        <?php if ($total > 1): ?>
        <a class="btn btn--linha btn--p cli__lugar" href="<?= e(link_('cliente-lugar', $ref)) ?>" data-lugar><?= icone('lugar') ?><span>Mudar lugar</span><span class="sr-only"> de <?= e($nome) ?></span></a>
        <?php else: ?>
        <span class="btn btn--linha btn--p cli__lugar is-off" aria-disabled="true" title="Ele é o único da lista: não há outro lugar." data-lugar-off><?= icone('lugar') ?><span>Mudar lugar</span><span class="sr-only"> (é o único da lista)</span></span>
        <?php endif; ?>
        <a class="btn btn--linha btn--p cli__editar" href="<?= e($editar) ?>"><?= icone('edit') ?><span>Editar</span><span class="sr-only"> <?= e($nome) ?></span></a>
        <form method="post" action="<?= e(link_('cliente-excluir')) ?>" data-confirmar="<?= e(textoExcluirCliente($c)) ?>" data-confirmar-ok="Excluir" data-confirmar-tit="Excluir cliente?">
          <?= $campos ?>
          <button type="submit" class="ic ic--perigo" aria-label="Excluir <?= e($nome) ?>" title="Excluir"><?= icone('trash') ?></button>
        </form>
      </div>
    </li>
<?php
}

/* ------------------------------------------------------------------
   "Mudar lugar" (clientes e produtos): a janela (com JavaScript) e a
   página (sem JavaScript, ou navegador sem <dialog>) mostram a mesma
   lista de lugares (opcoesLugar, lib/ordem.php = opcoes, painel.js).
   ------------------------------------------------------------------ */
/** Casca da janela "Mudar o lugar" (uma por página; o painel.js preenche). $area: produtos ou clientes. */
function dialogoLugar(string $area, array $filtro = []): void
{
    $prod = $area === 'produtos';
    ?>
  <dialog class="dialogo dialogo--ok dialogo--lugar" data-lugar-dialogo data-area="<?= e($area) ?>" aria-labelledby="lugar-tit" aria-describedby="lugar-hoje">
    <div class="dialogo__in">
      <h2 id="lugar-tit">Mudar o lugar</h2>
      <p class="lugar__nome" data-lugar-nome></p>
      <p class="lugar__hoje" id="lugar-hoje" data-lugar-hoje></p>
      <?php if ($prod): ?><p class="lugar__nota" data-lugar-filtro hidden></p><?php endif; ?>
      <p class="lugar__rot" id="lugar-rot">Escolha o novo lugar:</p>
      <p class="sr-only" id="lugar-teclas">Use as setas para escolher e Enter para confirmar. Esc fecha sem mudar.</p>
      <ol class="lugar__lista" role="list" aria-labelledby="lugar-rot" aria-describedby="lugar-teclas" data-lugar-lista></ol>
      <?php if ($prod): ?><p class="lugar__nota"><?= e(TEXTO_ORDEM_SITE) ?></p><?php endif; ?>
      <div class="dialogo__bts"><button type="button" class="btn btn--linha" data-lugar-fechar>Cancelar</button></div>
    </div>
    <?php /* Fica no documento para atualizarCsrf() trocar o csrf depois de entrar de novo */ ?>
    <form method="post" action="<?= e(link_($prod ? 'lugar' : 'cliente-lugar')) ?>" data-lugar-form hidden>
      <?= campoCsrf() ?>
      <?php if ($prod): ?><input type="hidden" name="id"><?php foreach (['q', 'cat', 'ver'] as $k): ?><input type="hidden" name="<?= $k ?>" value="<?= e($filtro[$k] ?? '') ?>"><?php endforeach; ?>
      <?php else: ?><input type="hidden" name="n"><input type="hidden" name="logo"><input type="hidden" name="nome"><?php endif; ?>
      <input type="hidden" name="para"><input type="hidden" name="desfazer">
    </form>
  </dialog>
<?php
}

/** Um lugar: "12º depois de X · começo de Pesadoras" (o mesmo desenho dos botões da janela). */
function textoOpcaoLugar(array $o): string
{
    return '<b class="lugar__num">' . (int) $o['pos'] . 'º</b> <span class="lugar__txt">' . e($o['texto'])
        . ($o['sub'] !== '' ? '<small> · começo de ' . e($o['sub']) . '</small>' : '') . '</span>';
}

/** Os lugares como botões de envio (name="para"); o lugar de hoje é um link de volta ($voltar). */
function listaLugar(array $opcoes, string $voltar): void
{
    ?>
      <ol class="lugar__lista" role="list" aria-labelledby="lugar-rot">
        <?php foreach ($opcoes as $o): ?>
        <li><?php if ($o['atual']): ?><a class="lugar__op" href="<?= e($voltar) ?>" aria-current="true"><?= textoOpcaoLugar($o) ?></a><?php else: ?><button type="submit" class="lugar__op" name="para" value="<?= e($o['valor']) ?>"><?= textoOpcaoLugar($o) ?></button><?php endif; ?></li>
        <?php endforeach; ?>
      </ol>
<?php
}

/** Página "Mudar o lugar" de um produto: a categoria inteira (inclusive o que a busca/o filtro escondem). */
function telaLugarProduto(array $cat, array $p, array $filtro, string $erro = ''): void
{
    $id = (string) $p['id'];
    $c = categoriaPorId($cat['categorias'], (string) ($p['categoria'] ?? '')) ?? ['id' => '', 'nome' => ''];
    $itens = itensLugarProdutos($cat['produtos'], (string) $c['id']);
    $i = (int) array_search($id, array_column($itens, 'valor'), true);
    $opcoes = opcoesLugar($itens, $i, (string) $c['nome']);
    $voltar = linkLista($filtro) . '#p-' . $id;
    $oculto = ($p['visivel'] ?? true) === false;
    pagina('Mudar o lugar', static function () use ($p, $id, $c, $itens, $i, $opcoes, $voltar, $oculto, $filtro, $erro): void { ?>
  <p class="migalha"><a href="<?= e($voltar) ?>"><?= icone('up', 'i i--esq') ?>Produtos</a></p>
  <?php cabecalhoPagina('Catálogo do site', 'Mudar o lugar'); ?>
  <form method="post" action="<?= e(link_('lugar')) ?>" class="lugar" data-lugar-pagina>
    <?= campoCsrf() ?><input type="hidden" name="id" value="<?= e($id) ?>">
    <?php foreach (['q', 'cat', 'ver'] as $k): ?><input type="hidden" name="<?= $k ?>" value="<?= e($filtro[$k] ?? '') ?>"><?php endforeach; ?>
    <div class="lugar__cab">
      <p class="lugar__nome">“<?= e(nomeCompleto($p)) ?>”</p>
      <p class="lugar__hoje">Hoje: <?= $i + 1 ?>º de <?= count($itens) ?> em <?= e($c['nome']) ?>.<?= $oculto ? ' Está fora do site.' : '' ?></p>
    </div>
    <?php if ($erro !== ''): ?><div class="aviso aviso--erro" role="alert"><?= icone('alert') ?><p><?= e($erro) ?></p></div><?php endif; ?>
    <?php if (($filtro['q'] ?? '') !== '' || ($filtro['ver'] ?? '') !== ''): ?><p class="lugar__nota">A lista tem todos os <?= count($itens) ?> de <?= e($c['nome']) ?>, inclusive os que a busca ou o filtro estão escondendo.</p><?php endif; ?>
    <p class="lugar__nota"><?= e(TEXTO_ORDEM_SITE) ?></p>
    <p class="lugar__rot" id="lugar-rot">Escolha o novo lugar:</p>
    <?php listaLugar($opcoes, $voltar); ?>
    <?php if (count($itens) < 2): ?><p class="lugar__nota">Ele é o único de <?= e($c['nome']) ?>: não há outro lugar.</p><?php endif; ?>
    <p class="lugar__bts"><a class="btn btn--linha" href="<?= e($voltar) ?>">Cancelar</a></p>
  </form>
<?php }, ['menu' => 'produtos', 'classe' => 'pg-lugar']);
}

/** Página "Mudar o lugar" de um cliente (o da posição $i). */
function telaLugarCliente(array $lista, int $i, string $erro = ''): void
{
    $lista = array_values($lista);
    $c = $lista[$i];
    $opcoes = opcoesLugar(itensLugarClientes($lista), $i);
    $voltar = link_('clientes') . '#c-' . ($i + 1);
    pagina('Mudar o lugar', static function () use ($lista, $c, $i, $opcoes, $voltar, $erro): void { ?>
  <p class="migalha"><a href="<?= e($voltar) ?>"><?= icone('up', 'i i--esq') ?>Clientes</a></p>
  <?php cabecalhoPagina('Logos no site', 'Mudar o lugar'); ?>
  <form method="post" action="<?= e(link_('cliente-lugar')) ?>" class="lugar" data-lugar-pagina>
    <?= campoCsrf() ?><input type="hidden" name="n" value="<?= $i + 1 ?>"><input type="hidden" name="logo" value="<?= e((string) ($c['logo'] ?? '')) ?>"><input type="hidden" name="nome" value="<?= e((string) ($c['nome'] ?? '')) ?>">
    <div class="lugar__cab">
      <p class="lugar__nome">“<?= e((string) ($c['nome'] ?? '')) ?>”</p>
      <p class="lugar__hoje">Hoje: <?= $i + 1 ?>º no site (de <?= count($lista) ?>).</p>
    </div>
    <?php if ($erro !== ''): ?><div class="aviso aviso--erro" role="alert"><?= icone('alert') ?><p><?= e($erro) ?></p></div><?php endif; ?>
    <p class="lugar__rot" id="lugar-rot">Escolha o novo lugar:</p>
    <?php listaLugar($opcoes, $voltar); ?>
    <p class="lugar__bts"><a class="btn btn--linha" href="<?= e($voltar) ?>">Cancelar</a></p>
  </form>
<?php }, ['menu' => 'clientes', 'classe' => 'pg-lugar']);
}

/** Formulário do cliente. $ref = ['n' => posição (1…), 'logo' => arquivo] ao editar; null = novo. */
function telaCliente(array $dados, ?array $c, array $erros = [], ?array $ref = null): void
{
    $novo = $ref === null;
    $c = $c ?? ['nome' => '', 'logo' => ''];
    $info = infoLogo((string) ($c['logo'] ?? ''));
    $titulo = $novo ? 'Novo cliente' : 'Editar cliente';
    $acao = link_('cliente-salvar', $novo ? [] : ['n' => (string) $ref['n'], 'logo' => (string) $ref['logo']]);
    $voltar = link_('clientes') . ($novo ? '' : '#c-' . (int) $ref['n']);
    $err = static fn(string $k): string => isset($erros[$k]) ? '<p class="campo__erro" id="erro-' . e($k) . '">' . icone('alert') . e($erros[$k]) . '</p>' : '';
    $inv = static fn(string $k): string => isset($erros[$k]) ? ' aria-invalid="true" aria-describedby="erro-' . e($k) . '"' : '';
    $minL = (int) (cfg('logo_min_largura') ?? 80);
    $maxB = (int) (cfg('logo_max_bytes') ?? 5242880);
    // Cliente novo: "Lugar no site" (padrão: 1º); a escolha volta marcada depois de um erro
    $lugares = $novo && $dados['clientes'] ? opcoesLugar(itensLugarClientes($dados['clientes']), null) : [];
    $escolhido = is_string($_POST['lugar'] ?? null) && in_array($_POST['lugar'], array_column($lugares, 'valor'), true) ? $_POST['lugar'] : 'inicio';

    pagina($titulo, static function () use ($dados, $c, $erros, $novo, $info, $titulo, $acao, $voltar, $err, $inv, $minL, $maxB, $lugares, $escolhido): void { ?>
  <p class="migalha"><a href="<?= e($voltar) ?>"><?= icone('up', 'i i--esq') ?>Clientes</a></p>
  <?php cabecalhoPagina($novo ? 'Logos no site' : (string) $c['nome'], $titulo); ?>
  <?php if ($erros): ?>
  <div class="aviso aviso--erro" role="alert" tabindex="-1" data-erros>
    <?= icone('alert') ?>
    <div>
      <p><b>O cliente não foi salvo.</b> Confira:</p>
      <ul><?php foreach ($erros as $k => $m): ?><li><a href="#f-<?= e($k === 'geral' ? 'nome' : $k) ?>"><?= e($m) ?></a></li><?php endforeach; ?></ul>
    </div>
  </div>
  <?php endif; ?>
  <?php if (!$dados['existe']) avisoSemArquivoClientes(true); ?>
  <form class="fp fc" method="post" action="<?= e($acao) ?>" enctype="multipart/form-data" data-form-cliente data-form-enviar>
    <?= campoCsrf() ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= $maxB ?>">
    <div class="fp__lado">
      <fieldset class="caixa caixa--foto">
        <legend>Logo</legend>
        <p class="foto__rot" id="logo-rot">Como fica no site</p>
        <div class="palco" data-palco aria-describedby="logo-rot">
          <div class="chapa chapa--grande<?= $info && $info['sq'] ? ' chapa--sq' : '' ?><?= $info ? '' : ' chapa--vazia' ?>" data-chapa>
            <img src="<?= e($info['url'] ?? '') ?>" alt="<?= $info ? 'Logo atual de ' . e((string) $c['nome']) : '' ?>" data-chapa-img<?= $info ? '' : ' hidden' ?>>
            <p class="foto__vazio" data-chapa-vazio<?= $info ? ' hidden' : '' ?>><?= icone('camera', 'i i--grande') ?><span>Nenhum logo ainda</span></p>
          </div>
        </div>
        <label class="btn btn--linha btn--largo foto__btn" for="f-logo"><?= icone('camera') ?><span data-logo-rotulo><?= $info ? 'Trocar logo' : 'Escolher logo' ?></span></label>
        <input class="foto__input" id="f-logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp"<?= $info ? '' : ' required' ?><?= $inv('logo') ?> data-logo-input data-min="<?= $minL ?>" data-max-bytes="<?= $maxB ?>">
        <?= $err('logo') ?>
        <p class="ajuda" data-logo-info><b>PNG com fundo transparente</b> fica melhor; JPG ou WebP com <b>fundo branco</b> também servem. Até <?= e(mb_($maxB)) ?>, com pelo menos <?= $minL ?> px de largura. <b>SVG não é aceito</b>: peça o logo em PNG. O painel corta as sobras, reduz e otimiza sozinho.</p>
      </fieldset>
    </div>
    <div class="fp__principal">
      <fieldset class="caixa">
        <legend>Cliente</legend>
        <div class="campo">
          <label for="f-nome">Nome do cliente</label>
          <input id="f-nome" name="nome" required maxlength="<?= LIMITE_NOME_CLIENTE ?>" value="<?= e((string) $c['nome']) ?>" autocomplete="off" placeholder="Ex.: Supermercado Bom Preço"<?= $inv('nome') ?>>
          <?= $err('nome') ?>
          <p class="ajuda">Não aparece escrito no site: é o texto do logo para o Google, para leitores de tela e para quando a imagem não carrega. Até <?= LIMITE_NOME_CLIENTE ?> caracteres.</p>
        </div>
        <?php if ($lugares): ?>
        <div class="campo">
          <label for="f-lugar">Lugar no site</label>
          <select id="f-lugar" name="lugar"><?php foreach ($lugares as $o): ?>
            <option value="<?= e($o['valor']) ?>"<?= $o['valor'] === $escolhido ? ' selected' : '' ?>><?= e($o['pos'] . 'º, ' . $o['texto']) ?></option><?php endforeach; ?>
          </select>
          <p class="ajuda">Os logos aparecem no site nesta ordem (da esquerda para a direita, de cima para baixo). Dá para mudar depois em Clientes → Mudar lugar.</p>
        </div>
        <?php elseif ($novo): ?><input type="hidden" name="lugar" value="inicio"><?php endif; ?>
        <?php if (isset($erros['geral'])): ?><?= $err('geral') ?><?php endif; ?>
      </fieldset>
    </div>
    <div class="barra-salvar">
      <a class="btn btn--linha" href="<?= e($voltar) ?>">Cancelar</a>
      <button class="btn btn--verde" type="submit" data-salvar><?= icone('check') ?><span><?= $novo ? 'Cadastrar' : 'Salvar' ?></span></button>
    </div>
  </form>
<?php
    }, ['menu' => 'clientes', 'classe' => 'pg-produto pg-cliente']);
}

/** Abas da tela de cópias: catálogo de produtos, lista de clientes ou dados da empresa. */
function abasCopias(string $atual): void
{
    ?>
  <nav class="chips abas-copias" aria-label="Cópias de">
    <a class="chip" href="<?= e(link_('copias')) ?>"<?= $atual === 'produtos' ? ' aria-current="page"' : '' ?>><?= icone('box') ?>Produtos</a>
    <a class="chip" href="<?= e(link_('copias', ['de' => 'clientes'])) ?>"<?= $atual === 'clientes' ? ' aria-current="page"' : '' ?>><?= icone('loja') ?>Clientes</a>
    <a class="chip" href="<?= e(link_('copias', ['de' => 'empresa'])) ?>"<?= $atual === 'empresa' ? ' aria-current="page"' : '' ?>><?= icone('empresa') ?>Empresa</a>
  </nav>
<?php
}

function telaCopiasClientes(array $copias, ?int $totalAtual, array $logosAtuais = []): void
{
    $nomes = nomesDosUsuarios();
    pagina('Cópias de segurança', static function () use ($copias, $totalAtual, $logosAtuais, $nomes): void {
        migalhaConta();
        cabecalhoPagina('Desfazer alterações', 'Cópias de segurança',
            'Antes de cada mudança na lista de <b>clientes</b> o painel guarda uma cópia de como ela estava, com as mesmas regras das cópias do catálogo (as '
            . (int) cfg('copias_max') . ' mais recentes, tudo dos últimos ' . (int) cfg('copias_dias') . ' dias; as de <b>excluir</b> e <b>restaurar</b> ficam ' . (int) cfg('copias_protegidas_dias') . ' dias). '
            . '<b>Restaurar</b> volta a lista inteira, com os logos. Para devolver só um cliente excluído, use <b>Trazer de volta</b>.');
        abasCopias('clientes');
        if (!$copias): ?>
  <div class="vazio"><p><b>Ainda não há cópias da lista de clientes.</b> Elas aparecem aqui depois da primeira alteração em Clientes.</p></div>
  <?php else: ?>
  <ol class="copias" role="list">
    <?php foreach ($copias as $i => $c):
        $titulo = $c['motivo'] !== '' ? $c['motivo'] : 'Antes de: ' . lcfirst($c['feitos'][0] ?? 'alteração');
        $mais = (int) $c['juntou'] - 1;
        $voltaSo = $c['tipo'] === 'excluir' && $c['alvo'] !== '' && !isset($logosAtuais[$c['alvo']]);
        // Posição do cliente excluído na cópia (para o "Trazer de volta")
        $pos = 0;
        if ($voltaSo) {
            $d = lerJsonPrivado('copias/' . $c['nome']);
            $k = is_array($d['clientes'] ?? null) ? acharCliente($d['clientes'], -1, $c['alvo']) : null;
            if ($k === null) $voltaSo = false; else $pos = $k + 1;
        } ?>
    <li class="copia">
      <div class="copia__txt">
        <p class="copia__quando"><?= e(quando($c['criado'])) ?><?php if ($c['ultimo'] - $c['criado'] >= 60): ?><span class="copia__ate"> até <?= e(date('H:i', $c['ultimo'])) ?></span><?php endif; ?><?php if ($i === 0): ?> <span class="selo selo--destaque">Mais recente</span><?php endif; ?></p>
        <p class="copia__motivo"><?= e($titulo) ?><?php if ($mais > 0): ?> <span class="copia__mais">e mais <?= $mais ?> <?= $mais === 1 ? 'alteração' : 'alterações' ?> do mesmo tipo</span><?php endif; ?></p>
        <p class="copia__meta"><?= (int) $c['total'] ?> <?= $c['total'] === 1 ? 'cliente' : 'clientes' ?><?= $c['existia'] ? '' : ' (a lista ainda não existia)' ?><?= $c['usuario'] !== '' ? ' · por ' . e(nomeDe($c['usuario'], $nomes)) : '' ?><?= in_array($c['tipo'], TIPOS_PROTEGIDOS, true) ? ' · guardada por ' . (int) cfg('copias_protegidas_dias') . ' dias' : '' ?></p>
      </div>
      <div class="copia__bts">
        <?php if ($voltaSo): ?>
        <form method="post" action="<?= e(link_('cliente-desfazer')) ?>" data-form-enviar>
          <?= campoCsrf() ?><input type="hidden" name="copia" value="<?= e($c['nome']) ?>"><input type="hidden" name="n" value="<?= $pos ?>"><input type="hidden" name="logo" value="<?= e($c['alvo']) ?>">
          <button class="btn btn--verde btn--p" type="submit"><?= icone('restore') ?>Trazer de volta<span class="sr-only"> “<?= e($c['alvoNome']) ?>”</span></button>
        </form>
        <?php endif; ?>
        <form method="post" action="<?= e(link_('restaurar')) ?>" data-confirmar="<?= e(textoRestaurarClientes($copias, $i, $totalAtual)) ?>" data-confirmar-ok="Restaurar" data-confirmar-tit="Restaurar esta cópia?">
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

function telaClientesIlegivel(string $motivo): void
{
    http_response_code(500);
    pagina('Lista de clientes com problema', static function () use ($motivo): void { ?>
  <section class="vazio-grande">
    <?= icone('alert', 'i i--grande') ?>
    <h1>Não consegui ler a lista de clientes</h1>
    <p>O arquivo <code>compartilhado/dados/clientes.js</code> não está no formato que o painel grava (<?= e($motivo) ?>). Isso acontece quando ele é editado à mão ou substituído por uma versão errada. Os produtos não foram afetados.</p>
    <?php if (ehAdmin()): ?>
    <p>O jeito mais rápido de resolver é restaurar uma cópia de segurança da lista de clientes.</p>
    <p><a class="btn btn--verde" href="<?= e(link_('copias', ['de' => 'clientes'])) ?>"><?= icone('restore') ?>Ver cópias dos clientes</a></p>
    <?php else: ?>
    <p>Avise um administrador do painel: ele resolve restaurando uma cópia de segurança da lista de clientes (Conta → Cópias de segurança).</p>
    <?php endif; ?>
  </section>
<?php }, ['menu' => 'clientes']);
}
