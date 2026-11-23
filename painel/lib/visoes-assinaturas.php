<?php
/* =====================================================================
   Painel Balanças.com — tela "Assinaturas de e-mail" (HTML)
   Página própria, com o visual do gerador de assinaturas (não usa a
   moldura do painel). Todo o comportamento está em assets/assinaturas.js
   e o visual em assets/assinaturas.css: nenhum <script> nem <style> aqui.
   O PHP só entrega os dados por atributos data- do <body>: o endereço
   público das imagens (lib/assinaturas.php), quem está logado e o cargo
   dessa pessoa na equipe do site. Tudo passa por e().
   ===================================================================== */
declare(strict_types=1);
if (!defined('PAINEL')) { http_response_code(404); exit; }

function telaAssinaturas(array $eu, string $cargo): void
{
    $voltar = link_('conta');   // o link para cá fica em Conta
    ?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive">
<meta name="theme-color" content="#0A0A0A">
<title>Assinaturas de e-mail · Painel Balanças.com</title>
<link rel="icon" href="<?= e(cfg('url_compartilhado')) ?>img/logo/balancas-icone.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Big+Shoulders:opsz,wght@10..72,700..900&amp;family=Archivo:wdth,wght@62..125,400..800&amp;family=Martian+Mono:wght@400..500&amp;display=swap">
<link rel="stylesheet" href="<?= e(versaoAsset('assets/assinaturas.css')) ?>">
<script src="<?= e(versaoAsset('assets/assinaturas.js')) ?>" defer></script>
</head>
<body data-imagens="<?= e(enderecoImagensAssinatura()) ?>" data-imagens-local="<?= e(caminhoLocalImagensAssinatura()) ?>"
      data-arquivos="<?= e(implode(' ', IMAGENS_ASSINATURA)) ?>" data-usuario="<?= e($eu['usuario']) ?>"
      data-nome="<?= e($eu['nome']) ?>" data-cargo="<?= e($cargo) ?>">

<header class="hdr">
  <div class="wrap hdr__in">
    <a class="logo" href="<?= e($voltar) ?>" aria-label="Balanças.com — voltar ao painel">
      <svg class="logo__icon" viewBox="-100 -100 200 200" aria-hidden="true">
        <defs>
          <path id="lg-b" d="M -30 -91.2 L -30 0 A 30 30 0 0 0 26 15 L 26 56.3 A 62 62 0 0 1 -62 0 L -62 -73.3 A 96 96 0 0 1 -30 -91.2 Z"/>
          <path id="lg-q" d="M 30 91.2 L 30 0 A 30 30 0 0 0 -26 -15 L -26 -56.3 A 62 62 0 0 1 62 0 L 62 73.3 A 96 96 0 0 1 30 91.2 Z"/>
          <mask id="lg-m"><rect x="-100" y="-100" width="200" height="200" fill="#fff"/><circle r="34" fill="#000"/><use href="#lg-b" fill="#000" stroke="#000" stroke-width="8" stroke-linejoin="round"/><use href="#lg-q" fill="#000" stroke="#000" stroke-width="8" stroke-linejoin="round"/></mask>
        </defs>
        <circle r="100" fill="currentColor" mask="url(#lg-m)"/>
        <use href="#lg-b" fill="#019F42"/><use href="#lg-q" fill="#019F42"/>
      </svg>
      <span class="logo__txt"><span class="logo__name">Balanças<span class="logo__dot">.</span>com</span><span class="logo__tag">Automação comercial</span></span>
    </a>
    <div class="hdr__lado">
      <span class="tag">Gerador de assinaturas</span>
      <div class="hdr__conta">
        <span class="hdr__quem" title="<?= e('Você entrou como ' . $eu['nome'] . ' (' . PAPEIS[$eu['papel']] . ')') ?>"><?= e($eu['nome']) ?></span>
        <a class="hdr__voltar" href="<?= e($voltar) ?>" aria-label="Voltar ao painel">← <span class="hdr__voltar-mais">Voltar ao </span>painel</a>
      </div>
    </div>
  </div>
</header>

<main>
  <section class="hero">
    <div class="wrap">
      <p class="kicker">Uso interno · Equipe Balanças.com</p>
      <h1>Assinatura<br>de e-mail<em>.</em></h1>
      <p class="lead">Preencha seus dados, copie e cole no seu e-mail. Leva um minuto, e a equipe inteira fica com a mesma identidade.</p>
    </div>
  </section>

  <section class="wrap grid" aria-label="Gerador de assinatura">
    <form class="panel" id="form" novalidate autocomplete="off">
      <h2 class="kicker">01 / Preencha</h2>

      <div class="field">
        <div class="field__top"><label class="lbl" for="f-nome">Nome</label></div>
        <input class="inp" id="f-nome" type="text" placeholder="Ex.: Maria Silva" maxlength="60">
      </div>
      <div class="field">
        <div class="field__top"><label class="lbl" for="f-cargo">Cargo</label></div>
        <input class="inp" id="f-cargo" type="text" placeholder="Ex.: Assistência Técnica" maxlength="60">
      </div>
      <div class="field">
        <div class="field__top"><label class="lbl" for="f-email">E-mail</label></div>
        <input class="inp" id="f-email" type="email" placeholder="nome@empresa.com.br" maxlength="80" spellcheck="false">
        <p class="hint" id="h-email" hidden>Confira o e-mail: parece incompleto.</p>
      </div>
      <div class="field">
        <div class="field__top">
          <label class="lbl" for="f-tel">Telefone</label>
          <label class="chk"><input id="f-tel-wa" type="checkbox" checked> WhatsApp</label>
        </div>
        <input class="inp" id="f-tel" type="tel" placeholder="(19) 3023-9050" maxlength="24">
        <p class="hint" id="h-tel" hidden>Confira o telefone: escreva um número só, com DDD. Do jeito que está, ele sai sem link.</p>
      </div>
      <div class="field">
        <div class="field__top">
          <label class="lbl" for="f-cel">Celular (opcional)</label>
          <label class="chk"><input id="f-cel-wa" type="checkbox" checked> WhatsApp</label>
        </div>
        <input class="inp" id="f-cel" type="tel" value="" placeholder="(19) 90000-0000" maxlength="24">
        <p class="hint" id="h-cel" hidden>Confira o celular: escreva um número só, com DDD. Do jeito que está, ele sai sem link.</p>
      </div>

      <fieldset class="group">
        <legend class="lbl">Modelo</legend>
        <div class="seg" id="modelos"></div>
      </fieldset>

      <fieldset class="group" id="g-opcoes">
        <legend class="lbl">Opções</legend>
        <div class="opts">
          <label class="chk"><input id="o-endereco" type="checkbox" checked> Mostrar o endereço</label>
        </div>
        <div class="field field--opcao">
          <div class="field__top">
            <label class="lbl" for="f-endereco">Endereço da empresa</label>
            <button class="link" type="button" id="b-end-padrao" hidden>Voltar ao padrão</button>
          </div>
          <input class="inp" id="f-endereco" type="text" maxlength="90">
          <p class="hint hint--info" id="h-endereco" hidden>Endereço alterado. O ícone e o botão do mapa passam a abrir este endereço no Google Maps.</p>
        </div>
      </fieldset>

      <div class="panel__foot">
        <button class="link" type="button" id="b-meus" hidden>Voltar aos meus dados</button>
        <button class="link" type="button" id="b-limpar">Limpar para outra pessoa</button>
      </div>
    </form>

    <div class="stage">
      <h2 class="kicker">02 / Confira e copie</h2>
      <div class="mail">
        <div class="mail__bar">Nova mensagem</div>
        <dl class="mail__meta">
          <div><dt>Para</dt><dd>cliente@exemplo.com.br</dd></div>
          <div><dt>Assunto</dt><dd>Orçamento</dd></div>
        </dl>
        <iframe id="previa" title="Prévia da assinatura dentro de um e-mail"></iframe>
      </div>
      <div class="actions">
        <button class="btn" type="button" id="b-copiar">Copiar assinatura</button>
        <button class="btn btn--line btn--sm" type="button" id="b-texto">Copiar só o texto</button>
        <button class="btn btn--line btn--sm" type="button" id="b-baixar">Baixar arquivo .htm</button>
      </div>
      <p class="count" id="contagem"></p>
      <p class="status" id="status-topo" role="status"></p>
    </div>
  </section>

  <section class="how" id="publicar">
    <div class="wrap">
      <h2 class="kicker">03 / Imagens no site</h2>
      <p class="how__title">Teste rápido.</p>
      <p class="how__lead">As imagens das assinaturas já ficam no site, num endereço difícil de adivinhar, e vão junto em cada atualização. Clique em Testar agora para conferir.</p>
      <div class="how__act">
        <button class="btn btn--ink btn--sm" type="button" id="b-testar">Testar agora</button>
      </div>
      <p class="status" id="status-pub" role="status"></p>
    </div>
  </section>

  <section class="how how--alt">
    <div class="wrap">
      <h2 class="kicker">04 / Cole no seu e-mail</h2>
      <p class="how__title">Como instalar.</p>

      <div class="tabs" role="tablist" aria-label="Programa de e-mail">
        <button class="tab" type="button" role="tab" id="t-gmail" aria-controls="p-gmail" aria-selected="true">Gmail</button>
        <button class="tab" type="button" role="tab" id="t-outlook" aria-controls="p-outlook" aria-selected="false" tabindex="-1">Outlook</button>
        <button class="tab" type="button" role="tab" id="t-thunderbird" aria-controls="p-thunderbird" aria-selected="false" tabindex="-1">Thunderbird</button>
        <button class="tab" type="button" role="tab" id="t-celular" aria-controls="p-celular" aria-selected="false" tabindex="-1">Celular</button>
      </div>

      <div class="pane" role="tabpanel" id="p-gmail" aria-labelledby="t-gmail">
        <ol class="steps">
          <li>Lá em cima, confira se o modelo marcado é o completo (<b>Dois blocos</b>, o padrão da equipe) e clique em <b>Copiar assinatura</b>.</li>
          <li>No Gmail, clique na engrenagem (canto superior direito) e em <b>Ver todas as configurações</b>.</li>
          <li>Na aba <b>Geral</b>, desça até <b>Assinatura</b>, clique em <b>Criar nova</b> e dê um nome a ela, como “Completa”.</li>
          <li>Clique dentro da caixa de texto da assinatura e cole com <kbd>Ctrl</kbd> + <kbd>V</kbd>.</li>
          <li>Volte a esta página, escolha o modelo <b>Compacta</b>, clique em <b>Copiar assinatura</b> e repita os passos 03 e 04 no Gmail, com o nome “Compacta”. Ela é para as respostas, que assim não ficam carregadas.</li>
          <li>Em <b>Padrões de assinatura</b>, escolha a “Completa” para novos e-mails e a “Compacta” para respostas e encaminhamentos. Logo abaixo, marque a caixa que insere a assinatura antes do texto das mensagens anteriores e remove a linha “--” (o Gmail põe essa linha acima da assinatura).</li>
          <li>Role até o fim da página e clique em <b>Salvar alterações</b>.</li>
          <li>Envie um e-mail para você mesmo e abra no computador e no celular. As imagens precisam aparecer, a assinatura precisa caber na tela do celular sem arrastar para o lado, e o botão do WhatsApp precisa abrir a conversa.</li>
        </ol>
        <p>Se a assinatura aparecer sem cores e sem imagens na hora de escrever, a mensagem está em texto simples: na janela de escrita, abra <b>Mais opções</b> (⋮) e desmarque <b>Modo de texto simples</b>.</p>
      </div>

      <div class="pane" role="tabpanel" id="p-outlook" aria-labelledby="t-outlook" hidden>
        <h3>Outlook novo e Outlook na web</h3>
        <ol class="steps">
          <li>Clique em <b>Copiar assinatura</b>, lá em cima.</li>
          <li>Abra as <b>Configurações</b> (engrenagem) e vá em <b>Contas</b> → <b>Assinaturas</b>. O caminho é o mesmo no Outlook novo, no Outlook na web e no Outlook.com.</li>
          <li>Crie uma nova assinatura, dê um nome, clique na caixa de edição e cole com <kbd>Ctrl</kbd> + <kbd>V</kbd>.</li>
          <li>Escolha a assinatura padrão para novas mensagens e para respostas e encaminhamentos, e clique em <b>Salvar</b>.</li>
          <li>Mande um e-mail para você mesmo e confira se as imagens saíram no tamanho certo.</li>
        </ol>
        <h3>Outlook clássico (Office)</h3>
        <ol class="steps">
          <li>Clique em <b>Baixar arquivo .htm</b>, lá em cima. O arquivo se chama <code data-arquivo="completo">assinatura-seu-nome.htm</code>.</li>
          <li>No Outlook, vá em <b>Arquivo</b> → <b>Opções</b> → <b>Email</b> → <b>Assinaturas…</b>, clique em <b>Novo</b> e dê à assinatura o mesmo nome do arquivo, sem o “.htm”: <code data-arquivo="nome">assinatura-seu-nome</code>.</li>
          <li>À direita, escolha essa assinatura em <b>Novas mensagens</b> e em <b>Respostas/encaminhamentos</b>. Não escreva nada na caixa de edição e feche a janela em <b>OK</b>.</li>
          <li>De volta em <b>Arquivo</b> → <b>Opções</b> → <b>Email</b>, segure a tecla <kbd>Ctrl</kbd> e clique em <b>Assinaturas…</b>: em vez da janela, abre a pasta onde o Outlook guarda as assinaturas. Feche o Outlook.</li>
          <li>Nessa pasta já existe um arquivo <b>.htm</b> com o nome da assinatura que você acabou de criar (em algumas versões, com o seu e-mail entre parênteses no fim). Dê ao arquivo baixado exatamente esse mesmo nome e copie-o para a pasta. O Windows avisa que o arquivo já existe: escolha <b>Substituir o arquivo no destino</b>. Se ele não avisar, os nomes não ficaram iguais.</li>
          <li>Abra o Outlook e crie um e-mail novo para conferir.</li>
        </ol>
        <p>No Outlook clássico, não cole a assinatura na caixa de edição nem a edite por lá: ele reescreve o código e pode passar a enviar as imagens como anexos.</p>
      </div>

      <div class="pane" role="tabpanel" id="p-thunderbird" aria-labelledby="t-thunderbird" hidden>
        <ol class="steps">
          <li>Clique em <b>Baixar arquivo .htm</b> e guarde o arquivo em uma pasta que não será apagada, como Documentos.</li>
          <li>No Thunderbird, abra o menu <b>≡</b> → <b>Configurações da conta</b> e selecione a sua conta.</li>
          <li>Marque <b>Anexar assinatura a partir de um arquivo</b>, clique em <b>Escolher…</b> e selecione o arquivo baixado.</li>
        </ol>
      </div>

      <div class="pane" role="tabpanel" id="p-celular" aria-labelledby="t-celular" hidden>
        <h3>Aplicativo do Gmail (Android e iPhone)</h3>
        <ol class="steps">
          <li>Salve a assinatura no Gmail do computador (aba <b>Gmail</b>).</li>
          <li>No aplicativo, toque no menu <b>☰</b> (canto superior esquerdo) e em <b>Configurações</b>. No Android, escolha a sua conta e toque em <b>Assinatura para dispositivo móvel</b>; no iPhone, toque em <b>Configurações da assinatura</b>.</li>
          <li>Deixe essa assinatura vazia (Android) ou desativada (iPhone). Sem ela, o aplicativo usa a mesma assinatura do computador.</li>
        </ol>
        <h3>iPhone (aplicativo Mail)</h3>
        <ol class="steps">
          <li>Envie para você mesmo um e-mail com a assinatura e abra no iPhone.</li>
          <li>Toque e segure sobre a assinatura, selecione tudo e toque em <b>Copiar</b>.</li>
          <li>Vá em <b>Ajustes</b> → <b>Apps</b> → <b>Mail</b> → <b>Assinatura</b> e cole. Em versões mais antigas do iOS, o caminho é <b>Ajustes</b> → <b>Mail</b> → <b>Assinatura</b>.</li>
          <li>Se as cores sumirem ao colar, agite o aparelho e toque em <b>Desfazer</b>.</li>
        </ol>
        <p>Se o aplicativo só aceitar texto simples, use o botão <b>Copiar só o texto</b>.</p>
      </div>
    </div>
  </section>
</main>

<footer class="ftr">
  <div class="wrap" id="rodape">Balanças.com · Automação Comercial · Rua 13, 650 · Rio Claro/SP · (19) 3023-9050</div>
</footer>

<div class="toast" id="toast" role="status" aria-live="polite"></div>
<noscript><p class="sem-js">Ative o JavaScript do navegador para usar o gerador.</p></noscript>
</body>
</html>
<?php
}

/** Cartão "Assinaturas de e-mail" em Conta (todos os usuários: Administrador e Editor). */
function cartaoAssinaturas(): void
{
    ?>
      <a class="cartao" href="<?= e(link_('assinaturas')) ?>">
        <?= icone('email', 'i cartao__ic') ?>
        <span class="cartao__txt"><b>Assinaturas de e-mail</b><span>Gere a sua assinatura com a marca da empresa, copie e cole no Gmail, no Outlook ou no celular.</span></span>
        <?= icone('seta', 'i cartao__seta') ?>
      </a>
<?php
}
