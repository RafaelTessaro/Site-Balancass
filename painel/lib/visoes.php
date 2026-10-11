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
  <symbol id="i-show" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" fill="currentColor"/></symbol>
</svg>
<?php
}

/* ------------------------------------------------------------------
   Moldura das páginas
   ------------------------------------------------------------------ */
function pagina(string $titulo, callable $conteudo, array $o = []): void
{
    $usuario = usuarioLogado();
    $atual = $o['menu'] ?? '';
    $classe = trim(($o['classe'] ?? '') . ($usuario ? ' com-menu' : ''));
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
<?php if ($usuario): ?>
<header class="topo">
  <div class="topo__in">
    <a class="marca" href="index.php" aria-label="Painel Balanças.com — produtos">
      <img src="<?= e(cfg('url_compartilhado')) ?>img/logo/balancas-icone-claro.svg" alt="" width="34" height="34">
      <span class="marca__nome">Balanças<span>.com</span></span>
      <span class="marca__tag">Painel</span>
    </a>
    <nav class="menu" aria-label="Seções do painel">
      <a href="index.php"<?= $atual === 'produtos' ? ' aria-current="page"' : '' ?>><?= icone('box') ?><span>Produtos</span></a>
      <a class="menu__novo" href="<?= e(link_('novo')) ?>"<?= $atual === 'novo' ? ' aria-current="page"' : '' ?>><?= icone('plus') ?><span>Novo</span></a>
      <a href="<?= e(link_('copias')) ?>"<?= $atual === 'copias' ? ' aria-current="page"' : '' ?>><?= icone('restore') ?><span>Cópias</span></a>
      <a href="<?= e(link_('conta')) ?>"<?= $atual === 'conta' ? ' aria-current="page"' : '' ?>><?= icone('user') ?><span>Conta</span></a>
    </nav>
    <div class="topo__acoes">
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
<?php if ($usuario): ?>
<dialog class="dialogo dialogo--login" data-relogin aria-labelledby="relogin-tit">
  <form class="dialogo__in form-acesso" data-relogin-form novalidate>
    <h2 id="relogin-tit">Entre de novo</h2>
    <p>Sua sessão terminou (mais de 2 horas sem uso). Digite a senha para continuar: nada do que está nesta tela se perde.</p>
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
function molduraAcesso(string $titulo, callable $miolo): void
{
    pagina($titulo, static function () use ($miolo): void { ?>
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
    <p class="acesso__rodape"><a href="<?= e(cfg('url_site')) ?>">← Voltar ao site</a></p>
  </div>
<?php }, ['classe' => 'pg-acesso', 'main' => 'principal-acesso']);
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

/** Passo a passo para criar a senha de novo (quem entra no cPanel). */
function passosEsqueciSenha(): void
{
    $onde = ondeFicaPastaPrivada();
    $fora = privadaForaDoSite();
    ?>
        <p class="ajuda">Quem entra no <b>cPanel</b> da hospedagem resolve em 1 minuto:</p>
        <ol class="passos passos--p">
          <li>cPanel → <b>Gerenciador de Arquivos</b>.</li>
          <li>Abra a pasta <code><?= e($onde) ?></code><?= $fora ? ' (na pasta inicial da conta, ao lado da <b>public_html</b>)' : '' ?>. Não é a pasta <code>painel</code> do site.</li>
          <li>Apague só o arquivo <code>config.php</code> dessa pasta. As cópias de segurança e o resto continuam.</li>
          <li>Abra o painel de novo e siga o <b>Primeiro acesso</b> (o código novo fica em <code>primeiro-acesso.php</code>, na mesma pasta).</li>
        </ol>
<?php
}

function telaInstalar(string $erro = '', array $v = []): void
{
    $onde = ondeFicaPastaPrivada();
    $fora = privadaForaDoSite();
    molduraAcesso('Primeiro acesso', static function () use ($erro, $v, $onde, $fora): void { ?>
      <h1 class="acesso__tit">Primeiro acesso</h1>
      <p class="acesso__sub">Crie o usuário e a senha do painel. Para provar que é você, use o código que o painel acabou de gravar na hospedagem.</p>
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
          <label for="f-usuario">Usuário</label>
          <input id="f-usuario" name="usuario" autocomplete="username" autocapitalize="off" spellcheck="false" required maxlength="40" pattern="[A-Za-z0-9._\-]{3,40}" value="<?= e($v['usuario'] ?? '') ?>" aria-describedby="f-usuario-ajuda">
          <p class="ajuda" id="f-usuario-ajuda">De 3 a 40 letras sem acento ou números (ex.: fabio).</p>
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
function telaLista(array $cat, array $filtro): void
{
    $categorias = $cat['categorias'];
    $produtos = $cat['produtos'];
    $total = count($produtos);
    $visiveis = count(array_filter($produtos, static fn($p) => ($p['visivel'] ?? true) !== false));
    $destaques = count(array_filter($produtos, static fn($p) => !empty($p['destaque']) && ($p['visivel'] ?? true) !== false));
    $ctx = ['home' => destaquesDaHome($produtos), 'capa' => produtoCapa()];
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

    pagina('Produtos', static function () use ($categorias, $produtos, $porCat, $filtro, $passa, $total, $visiveis, $destaques, $achados, $ctx): void {
        cabecalhoPagina('Catálogo do site', 'Produtos',
            '<span><b data-n="total">' . $total . '</b> produtos</span><span><b data-n="site">' . $visiveis . '</b> no site</span><span><b data-n="destaque">' . $destaques . '</b> em destaque</span>'
            . '<span><b data-n="home">' . count($ctx['home']) . '</b> na página inicial</span>',
            '<a class="btn btn--verde" href="' . e(link_('novo')) . '">' . icone('plus') . 'Novo produto</a>');
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
      <noscript><button class="btn btn--linha btn--p" type="submit">Filtrar</button></noscript>
    </div>
  </form>
  <p class="contagem" data-contagem aria-live="polite"><?= $achados === count($produtos) ? 'Mostrando todos os ' . $achados . ' produtos.' : $achados . ($achados === 1 ? ' produto encontrado.' : ' produtos encontrados.') ?></p>
  <div class="dica" data-capa="<?= e($ctx['capa']) ?>">
    <?= icone('up') ?>
    <p><span><b>Ordem no site:</b> Pronta entrega primeiro, depois os destaques, depois a ordem das setas; Esgotado fica por último.</span>
    <span><b>Página inicial:</b> mostra até <?= HOME_MAX_DESTAQUES ?> destaques, revezando as categorias (o de cima de cada categoria entra primeiro). Destaque além disso aparece como “fora da página inicial”.</span></p>
  </div>

  <div class="vazio" data-vazio<?= $achados ? ' hidden' : '' ?>>
    <p><b>Nenhum produto encontrado.</b> Tente outra palavra ou <a href="index.php">limpe os filtros</a>.</p>
    <p><a class="btn btn--verde btn--p" href="<?= e(link_('novo')) ?>"><?= icone('plus') ?>Cadastrar produto novo</a></p>
  </div>

  <?php foreach ($categorias as $c):
        $lista = $porCat[$c['id']] ?? [];
        if (!$lista) continue;
        $mostra = count(array_filter($lista, $passa)); ?>
  <section class="grupo" data-grupo="<?= e($c['id']) ?>"<?= $mostra ? '' : ' hidden' ?> aria-labelledby="g-<?= e($c['id']) ?>">
    <h2 class="grupo__tit" id="g-<?= e($c['id']) ?>"><?= e($c['nome']) ?> <span><?= count($lista) ?></span></h2>
    <ol class="lista" role="list">
      <?php foreach ($lista as $i => $p) itemProduto($p, $categorias, $i === 0, $i === count($lista) - 1, $passa($p), $ctx); ?>
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
      <?php foreach (array_values($orfaos) as $i => $p) itemProduto($p, $categorias, true, true, true, $ctx); ?>
    </ol>
  </section>
  <?php endif; ?>
<?php
    }, ['menu' => 'produtos', 'classe' => 'pg-lista']);
}

function nomeCurtoCategoria(array $c): string
{
    return ['balancas' => 'Balanças', 'automacao' => 'Automação', 'informatica' => 'Informática'][$c['id']] ?? $c['nome'];
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
    if ($id === ($ctx['capa'] ?? '')) return ['Destaque', ''];
    return in_array($id, $ctx['home'] ?? [], true)
        ? ['Destaque · página inicial', '']
        : ['Destaque · fora da página inicial', ' is-fora'];
}

function itemProduto(array $p, array $categorias, bool $primeiro, bool $ultimo, bool $mostra, array $ctx = []): void
{
    $id = (string) $p['id'];
    $nome = nomeCompleto($p);
    $vis = ($p['visivel'] ?? true) !== false;
    $dest = !empty($p['destaque']);
    $capa = $id === ($ctx['capa'] ?? '');
    [$txtDest, $clsDest] = textoSeloDestaque($id, $ctx);
    $est = (string) ($p['estoque'] ?? 'consulte');
    $img = is_string($p['imagem'] ?? null) && preg_match(RE_ARQUIVO_FOTO, $p['imagem']) ? urlFoto($p['imagem']) : '';
    $editar = link_('editar', ['id' => $id]);
    ?>
      <li class="item<?= $vis ? '' : ' is-oculto' ?>" id="p-<?= e($id) ?>" data-id="<?= e($id) ?>"<?= $capa ? ' data-na-capa' : '' ?> data-cat="<?= e($p['categoria'] ?? '') ?>" data-busca="<?= e(textoBusca($p, $categorias)) ?>" data-visivel="<?= $vis ? '1' : '0' ?>" data-destaque="<?= $dest ? '1' : '0' ?>"<?= $mostra ? '' : ' hidden' ?>>
        <a class="item__foto" href="<?= e($editar) ?>" tabindex="-1" aria-hidden="true">
          <?php if ($img): ?><img src="<?= e($img) ?>" alt="" loading="lazy" decoding="async" width="80" height="80"><?php else: ?><span class="item__semfoto"><?= icone('camera') ?></span><?php endif; ?>
        </a>
        <div class="item__txt">
          <p class="item__marca"><?= e($p['marca'] ?? '') ?><span> · <?= e($p['subcategoria'] ?? '') ?></span></p>
          <h3 class="item__nome"><a href="<?= e($editar) ?>"><?= e($p['nome'] ?? $id) ?></a></h3>
          <p class="item__selos">
            <?php if ($capa): ?><span class="selo selo--capa" title="A balança grande do topo da página inicial do site">Na capa do site</span><?php endif; ?>
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
          $conf = $capa ? ' data-confirmar-se="0" data-confirmar="Esconder “' . e($nome) . '” do site? Ele é o produto da capa: a foto da capa continua lá, mas o link dela vai abrir o catálogo com o aviso “produto fora do catálogo”." data-confirmar-ok="Esconder" data-confirmar-tit="Esconder o produto da capa?"' : '';
          formRapido($p, 'visivel', $vis ? '0' : '1', '<input type="hidden" name="valor" value="' . ($vis ? '0' : '1') . '"><button type="submit" class="tg tg--visivel" aria-pressed="' . ($vis ? 'true' : 'false') . '" title="Mostrar no site">' . icone('eye', 'i i--on') . icone('eye-off', 'i i--off') . '<span>No site</span><span class="sr-only"> — ' . e($nome) . '</span></button>', 'rapido--visivel', $conf);
          ?>
        </div>
        <div class="item__acoes">
          <form method="post" action="<?= e(link_('mover')) ?>" data-mover>
            <?= campoCsrf() ?><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="dir" value="cima">
            <button type="submit" class="ic" aria-label="Subir <?= e($nome) ?>" title="Subir"<?= $primeiro ? ' disabled' : '' ?> data-mover-cima><?= icone('up') ?></button>
          </form>
          <form method="post" action="<?= e(link_('mover')) ?>" data-mover>
            <?= campoCsrf() ?><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="dir" value="baixo">
            <button type="submit" class="ic" aria-label="Descer <?= e($nome) ?>" title="Descer"<?= $ultimo ? ' disabled' : '' ?> data-mover-baixo><?= icone('down') ?></button>
          </form>
          <a class="btn btn--linha btn--p item__editar" href="<?= e($editar) ?>"><?= icone('edit') ?><span>Editar</span><span class="sr-only"> <?= e($nome) ?></span></a>
          <form method="post" action="<?= e(link_('excluir')) ?>" data-confirmar="<?= e(textoExcluir($p)) ?>" data-confirmar-ok="Excluir">
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
    // Situação do destaque na página inicial (produto já salvo)
    $home = destaquesDaHome($cat['produtos']);
    $situacao = '';
    if ($capa) $situacao = 'Este é o produto da capa: já aparece no topo da página inicial.';
    elseif (!$novo && !empty($p['destaque']) && ($p['visivel'] ?? true) !== false) {
        $situacao = in_array($original, $home, true) ? 'Hoje: aparece na página inicial.' : 'Hoje: fora da página inicial (já há ' . HOME_MAX_DESTAQUES . ' destaques na frente dele).';
    }

    pagina($titulo, static function () use ($novo, $p, $categorias, $marcas, $specs, $img, $titulo, $acao, $erros, $err, $inv, $original, $capa, $situacao): void {
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
  <div class="aviso aviso--info aviso--capa"><?= icone('alert') ?><p><b>Produto da capa do site.</b> A balança grande do topo da página inicial é este produto (foto e link fixos). Se esconder, excluir ou mudar o código, a foto da capa continua lá, mas o link dela deixa de abrir os detalhes.</p></div>
  <?php endif; ?>
  <form class="fp" method="post" action="<?= e($acao) ?>" enctype="multipart/form-data" data-form-produto data-form-enviar data-rascunho="<?= e($novo ? 'novo' : 'editar:' . $original) ?>"<?= $capa ? ' data-capa="' . e((string) $original) . '"' : '' ?>>
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
function telaConfirmar(string $titulo, string $texto, string $acao, array $campos, string $botao, string $voltar): void
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
<?php }, ['menu' => 'produtos']);
}

/* ------------------------------------------------------------------
   Cópias de segurança
   ------------------------------------------------------------------ */
function telaCopias(array $copias, ?int $totalAtual, array $idsAtuais = []): void
{
    pagina('Cópias de segurança', static function () use ($copias, $totalAtual, $idsAtuais): void {
        cabecalhoPagina('Desfazer alterações', 'Cópias de segurança',
            'Antes de cada mudança no catálogo o painel guarda uma cópia de como ele estava. Ficam sempre as ' . (int) cfg('copias_max')
            . ' mais recentes e tudo dos últimos ' . (int) cfg('copias_dias') . ' dias; as de <b>excluir</b> e <b>restaurar</b> ficam ' . (int) cfg('copias_protegidas_dias') . ' dias. '
            . 'Mudanças seguidas do mesmo tipo (ordem, estoque, destaque, no site) feitas em menos de ' . (int) cfg('copias_juntar_min') . ' minutos ficam numa cópia só. '
            . '<b>Restaurar</b> volta o catálogo inteiro, com as fotos, e desfaz também o que foi feito depois. Para devolver só um produto excluído, use <b>Trazer de volta</b>.');
        if (!$copias): ?>
  <div class="vazio"><p><b>Ainda não há cópias.</b> Elas aparecem aqui depois da primeira alteração no catálogo.</p></div>
  <?php else: ?>
  <ol class="copias" role="list">
    <?php foreach ($copias as $i => $c):
        $titulo = $c['motivo'] !== '' ? $c['motivo'] : 'Antes de: ' . lcfirst($c['feitos'][0] ?? 'alteração');
        $mais = (int) $c['juntou'] - 1;
        $voltaSo = $c['tipo'] === 'excluir' && $c['alvo'] !== '' && !isset($idsAtuais[$c['alvo']]); ?>
    <li class="copia">
      <div class="copia__txt">
        <p class="copia__quando"><?= e(quando($c['criado'])) ?><?php if ($c['ultimo'] - $c['criado'] >= 60): ?><span class="copia__ate"> até <?= e(date('H:i', $c['ultimo'])) ?></span><?php endif; ?><?php if ($i === 0): ?> <span class="selo selo--destaque">Mais recente</span><?php endif; ?></p>
        <p class="copia__motivo"><?= e($titulo) ?><?php if ($mais > 0): ?> <span class="copia__mais">e mais <?= $mais ?> <?= $mais === 1 ? 'alteração' : 'alterações' ?> do mesmo tipo</span><?php endif; ?></p>
        <p class="copia__meta"><?= (int) $c['total'] ?> produtos · <?= (int) $c['visiveis'] ?> no site<?= $c['usuario'] !== '' ? ' · por ' . e($c['usuario']) : '' ?><?= in_array($c['tipo'], TIPOS_PROTEGIDOS, true) ? ' · guardada por ' . (int) cfg('copias_protegidas_dias') . ' dias' : '' ?></p>
      </div>
      <div class="copia__bts">
        <?php if ($voltaSo): ?>
        <form method="post" action="<?= e(link_('desfazer')) ?>" data-form-enviar>
          <?= campoCsrf() ?><input type="hidden" name="copia" value="<?= e($c['nome']) ?>"><input type="hidden" name="id" value="<?= e($c['alvo']) ?>">
          <button class="btn btn--verde btn--p" type="submit"><?= icone('restore') ?>Trazer de volta<span class="sr-only"> “<?= e($c['alvoNome']) ?>”</span></button>
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

/* ------------------------------------------------------------------
   Conta
   ------------------------------------------------------------------ */
function telaConta(string $erro = ''): void
{
    $conf = configConta() ?? [];
    // Linhas técnicas (fotos indo e voltando da lixeira) ficam de fora; repetições seguidas
    // (ex.: 30 toques nas setas) viram uma linha só, com "(30 vezes)"
    $atividades = [];
    foreach (ultimasAtividades(300) as $a) {
        if (in_array($a['evento'], ['foto-lixeira', 'foto-restaurada'], true)) continue;
        $u = count($atividades) - 1;
        if ($u >= 0 && $atividades[$u]['evento'] === $a['evento'] && $atividades[$u]['detalhe'] === $a['detalhe'] && $atividades[$u]['usuario'] === $a['usuario']) {
            $atividades[$u]['vezes']++;
            continue;
        }
        if (count($atividades) >= 15) break;
        $atividades[] = $a + ['vezes' => 1];
    }
    $nomes = ['entrou' => 'Entrou no painel', 'saiu' => 'Saiu do painel', 'senha-errada' => 'Senha errada', 'trocou-senha' => 'Trocou a senha',
        'instalou' => 'Criou o acesso', 'codigo-gerado' => 'Gerou o código de primeiro acesso', 'codigo-errado' => 'Código de primeiro acesso errado',
        'bloqueado' => 'Bloqueio por tentativas', 'csrf' => 'Pedido recusado'];
    pagina('Minha conta', static function () use ($conf, $atividades, $nomes, $erro): void {
        cabecalhoPagina('Acesso', 'Minha conta', 'Usuário <b>' . e($conf['usuario'] ?? '') . '</b>. Por segurança, o painel sai sozinho depois de 2 horas sem uso.');
        ?>
  <div class="conta">
    <section class="caixa">
      <h2 class="caixa__tit">Trocar senha</h2>
      <?php if ($erro !== ''): ?><div class="aviso aviso--erro" role="alert"><?= icone('alert') ?><p><?= e($erro) ?></p></div><?php endif; ?>
      <form method="post" action="<?= e(link_('senha')) ?>" class="form-acesso" data-form-enviar>
        <?= campoCsrf() ?>
        <input type="text" name="usuario" value="<?= e($conf['usuario'] ?? '') ?>" autocomplete="username" hidden>
        <?php campoSenha('f-atual', 'atual', 'Senha atual', 'current-password'); ?>
        <?php campoSenha('f-nova', 'nova', 'Senha nova', 'new-password', 'Pelo menos ' . (int) cfg('senha_minimo') . ' caracteres. Ao trocar, outros aparelhos conectados saem.'); ?>
        <?php campoSenha('f-nova2', 'nova2', 'Repita a senha nova', 'new-password'); ?>
        <button class="btn btn--verde" type="submit"><?= icone('lock') ?>Trocar senha</button>
      </form>
    </section>
    <section class="caixa">
      <h2 class="caixa__tit">Últimas atividades</h2>
      <?php if (!$atividades): ?><p class="ajuda">Nada registrado ainda.</p><?php else: ?>
      <ol class="atividades" role="list">
        <?php foreach ($atividades as $a):
            $alerta = in_array($a['evento'], ['senha-errada', 'codigo-errado', 'bloqueado', 'csrf'], true);
            $quem = $a['usuario'] !== '-' ? $a['usuario'] : ''; ?>
        <li class="atv<?= $alerta ? ' atv--alerta' : '' ?>">
          <time datetime="<?= e(date('c', $a['quando'])) ?>"><?= e(date('d/m H:i', $a['quando'])) ?></time>
          <?php if ($a['evento'] === 'catalogo'): ?>
          <span><b><?= e(frasePassado($a['detalhe'])) ?></b><?= $a['vezes'] > 1 ? ' (' . (int) $a['vezes'] . ' vezes)' : '' ?></span>
          <?php else: ?>
          <span><b><?= e($nomes[$a['evento']] ?? $a['evento']) ?></b><?= $alerta && $a['detalhe'] !== '' ? ' — ' . e($a['detalhe']) : '' ?><?= $a['vezes'] > 1 ? ' (' . (int) $a['vezes'] . ' vezes)' : '' ?></span>
          <?php endif; ?>
          <?php if ($quem !== '' || $alerta): ?><small><?= e($quem) ?><?= $alerta ? ($quem !== '' ? ' · ' : '') . 'IP ' . e($a['ip']) : '' ?></small><?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>
    </section>
    <section class="caixa caixa--info">
      <h2 class="caixa__tit">Sobre este painel</h2>
      <ul class="info">
        <li>Dados privados (senha, cópias, lixeira de fotos): <code><?= e(ondeFicaPastaPrivada()) ?></code><?= privadaForaDoSite() ? ' — fora da pasta do site.' : ' — dentro do site, protegida.' ?></li>
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

function telaCatalogoIlegivel(string $motivo): void
{
    http_response_code(500);
    pagina('Catálogo com problema', static function () use ($motivo): void { ?>
  <section class="vazio-grande">
    <?= icone('alert', 'i i--grande') ?>
    <h1>Não consegui ler o catálogo</h1>
    <p>O arquivo <code>compartilhado/dados/produtos.js</code> não está no formato que o painel grava (<?= e($motivo) ?>). Isso acontece quando ele é editado à mão ou substituído por uma versão antiga.</p>
    <p>O jeito mais rápido de resolver é restaurar uma cópia de segurança. Se for a primeira vez que o painel roda, o arquivo precisa ser convertido (veja o LEIA-ME do painel).</p>
    <p><a class="btn btn--verde" href="<?= e(link_('copias')) ?>"><?= icone('restore') ?>Ver cópias de segurança</a></p>
  </section>
<?php }, ['menu' => 'produtos']);
}
