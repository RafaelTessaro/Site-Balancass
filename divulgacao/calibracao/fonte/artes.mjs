// As artes da campanha (revisão 3). Segue o "Guia de revisão das artes" e a "Reavaliação das artes V2"
// (18/11/2026). Cada arte: nome, pasta, tamanho, fundo, CSS próprio, HTML e o tamanho mínimo de letra (min).
// Ordem de leitura em todas: serviço ou necessidade → benefício → imagem → ação → contato.
// Regras de texto (ver ../TEXTOS-DOS-ANUNCIOS.md, item 2): nada de "maioria das auditorias",
// "a gente certifica", "pronto para mostrar", "sem tirar do lugar"; a não acreditação aparece legível
// sempre que a peça fala de auditoria; o certificado aparece só como exemplo, com legenda externa;
// o peso fica AO LADO da balança (nunca no prato de uma foto com o visor zerado); nenhum texto sobre a
// faixa verde (o gerador confere).
import { logo, simbolo, cert, balPeso, peso, rodape, legendaCert, ICON_WA, ICON_ARROW, ICON_DOWN, RESSALVA, RESSALVA_CURTA } from './pecas.mjs';

const faixa = (bx, bw, extra = '') => `<div class="band ${extra}" style="--bx:${bx};--bw:${bw}"></div>`;
const T_PRINCIPAL = `<span>Calibração</span><span>de balanças</span><span class="g">com certificado.</span>`;
const APOIO = 'Conheça os resultados de medição da sua balança, registrados em certificado.';
const PEDIDO = 'Envie uma foto da identificação da balança e informe a cidade do atendimento.';
const ENTREGA = ['Resultados documentados', 'Incerteza e rastreabilidade', 'Certificado em PDF'];
const lista = (itens) => `<ul class="ck" data-dentro>${itens.map((i) => `<li>${i}</li>`).join('')}</ul>`;
const ORCAMENTO = `${ICON_WA}Solicite um orçamento`;

export const ARTES = [];
const arte = (a) => ARTES.push(a);

/* =====================================================================
   INSTAGRAM E FACEBOOK — FEED 4:5 (1080×1350) e QUADRADO 1:1 (1080×1080)
   ===================================================================== */

// A · PRINCIPAL — apresenta o serviço
arte({
  nome: 'feed-4x5-A-principal', pasta: 'instagram-facebook', w: 1080, h: 1350, min: 36,
  css: `
  .lg { position:absolute; left:64px; top:60px; }
  .tag { position:absolute; left:70px; top:172px; }
  .hd { position:absolute; left:64px; top:236px; --hs:102px; }
  .sub { position:absolute; left:64px; top:552px; width:600px; }
  .cta { position:absolute; left:70px; top:1124px; }
  `,
  html: `${faixa('72%', '24%')}${logo()}
  <span class="tag" data-deco>Novo serviço</span>
  <h1 class="h hd" data-dentro>${T_PRINCIPAL}</h1>
  <p class="p sub" data-dentro>${APOIO} <strong>Rio Claro e região.</strong></p>
  ${balPeso('right:40px; top:830px; width:540px;')}
  <p class="cta" data-dentro>${ORCAMENTO}</p>
  ${rodape()}`,
});

// B · AUDITORIA — quem prepara auditoria: o serviço, a condição de não acreditação e o pedido
arte({
  nome: 'feed-4x5-B-auditoria', pasta: 'instagram-facebook', w: 1080, h: 1350, min: 36, classe: 'is-paper',
  css: `
  .lg { position:absolute; left:64px; top:60px; }
  .hd { position:absolute; left:64px; top:196px; --hs:116px; }
  .sub { position:absolute; left:64px; top:440px; width:620px; }
  .inf { position:absolute; left:64px; top:680px; width:640px; --ps:44px; }
  .fine { position:absolute; left:64px; top:860px; width:660px; padding-left:22px; border-left:5px solid var(--green); }
  .cta { position:absolute; left:70px; top:1098px; }
  `,
  html: `${faixa('78%', '18%')}${logo('claro')}
  <h1 class="h hd" data-dentro><span>Auditoria em</span><span class="g">preparação?</span></h1>
  <p class="p sub" data-dentro>Confira os requisitos de calibração das suas balanças.</p>
  ${peso('peso', 'right:70px; top:420px; width:240px;')}
  <p class="p inf" data-dentro><strong>Calibração de balanças com emissão de certificado.</strong></p>
  <p class="fine" data-dentro><b>Serviço de calibração não acreditado pela Cgcre.</b> Consulte a adequação aos requisitos da sua empresa.</p>
  <p class="cta cta--ink" data-dentro>${ORCAMENTO}</p>
  ${rodape()}`,
});

// C · O QUE VOCÊ RECEBE — a entrega, em três itens, com o certificado identificado por fora
arte({
  nome: 'feed-4x5-C-o-que-voce-recebe', pasta: 'instagram-facebook', w: 1080, h: 1350, min: 36,
  css: `
  .lg { position:absolute; left:64px; top:60px; }
  .hd { position:absolute; left:64px; top:190px; --hs:100px; }
  .ck { position:absolute; left:64px; top:410px; gap:.5em; }
  .fine { position:absolute; left:64px; top:760px; width:400px; }
  .cert-leg { right:64px; top:632px; }
  .cta { position:absolute; left:70px; top:1124px; }
  `,
  html: `${faixa('72%', '24%')}${logo()}
  <h1 class="h hd" data-dentro><span>O que você recebe</span><span class="g">na calibração.</span></h1>
  ${lista(ENTREGA)}
  ${legendaCert()}
  ${cert('--c:15px; right:64px; top:756px;')}
  <p class="fine" data-dentro>${RESSALVA_CURTA} O certificado informa essa condição.</p>
  <p class="cta" data-dentro>${ORCAMENTO}</p>
  ${rodape()}`,
});

arte({
  nome: 'quadrado-1x1-A-principal', pasta: 'instagram-facebook', w: 1080, h: 1080, min: 36,
  css: `
  .lg { position:absolute; left:60px; top:56px; }
  .tag { position:absolute; left:66px; top:160px; }
  .hd { position:absolute; left:60px; top:222px; --hs:92px; }
  .sub { position:absolute; left:60px; top:494px; width:540px; --ps:40px; }
  .cta { position:absolute; left:66px; top:862px; }
  `,
  html: `${faixa('72%', '24%')}${logo()}
  <span class="tag" data-deco>Novo serviço</span>
  <h1 class="h hd" data-dentro>${T_PRINCIPAL}</h1>
  <p class="p sub" data-dentro>${APOIO} <strong>Rio Claro e região.</strong></p>
  ${balPeso('right:30px; top:640px; width:440px;')}
  <p class="cta" data-dentro>${ORCAMENTO}</p>
  ${rodape()}`,
});

// B · SUA EMPRESA — a necessidade e um pedido simples (foto e cidade)
arte({
  nome: 'quadrado-1x1-B-sua-empresa', pasta: 'instagram-facebook', w: 1080, h: 1080, min: 36, classe: 'is-paper',
  css: `
  .lg { position:absolute; left:60px; top:56px; }
  .hd { position:absolute; left:60px; top:170px; --hs:94px; }
  .sub { position:absolute; left:60px; top:446px; width:620px; --ps:42px; }
  .cta { position:absolute; left:66px; top:862px; }
  `,
  html: `${faixa('78%', '18%')}${logo('claro')}
  <h1 class="h hd" data-dentro><span>Sua empresa</span><span>precisa calibrar</span><span class="g">as balanças?</span></h1>
  <p class="p sub" data-dentro>${PEDIDO}</p>
  ${peso('peso', 'right:70px; top:470px; width:190px;')}
  <p class="cta cta--ink" data-dentro>${ORCAMENTO}</p>
  ${rodape()}`,
});

/* =====================================================================
   9:16 (1080×1920) — uma versão para cada uso
   Área livre: 250 px no alto e 340 px embaixo (perfil, resposta, botão ou figurinha).
   Conferir nas prévias da própria plataforma antes de publicar.
   ===================================================================== */
const STORY_CSS = `
  .lg { position:absolute; left:72px; top:270px; --lg:42px; }
  .tag { position:absolute; left:78px; top:380px; }
  .hd { position:absolute; left:72px; top:444px; --hs:120px; }
  .sub { position:absolute; left:72px; top:806px; width:760px; --ps:46px; }
`;
// Anúncio (Stories/Reels pago): a seta aponta para o botão da plataforma, que é o link
arte({
  nome: 'stories-9x16-anuncio-principal', pasta: 'instagram-facebook/9x16', w: 1080, h: 1920, min: 36,
  css: STORY_CSS + `
  .cta { position:absolute; left:80px; top:1474px; --cs:46px; }
  `,
  html: `${faixa('81%', '17%')}${logo()}
  <span class="tag" data-deco>Novo serviço</span>
  <h1 class="h hd" data-dentro>${T_PRINCIPAL}</h1>
  <p class="p sub" data-dentro>${APOIO} <strong>Rio Claro e região.</strong></p>
  ${balPeso('left:120px; top:1110px; width:700px;')}
  <p class="cta" data-dentro>Solicite um orçamento${ICON_DOWN}</p>`,
});
arte({
  nome: 'stories-9x16-anuncio-auditoria', pasta: 'instagram-facebook/9x16', w: 1080, h: 1920, min: 36, classe: 'is-paper',
  css: `
  .lg { position:absolute; left:72px; top:270px; --lg:42px; }
  .hd { position:absolute; left:72px; top:390px; --hs:130px; }
  .sub { position:absolute; left:72px; top:650px; width:820px; --ps:46px; }
  .inf { position:absolute; left:72px; top:880px; width:560px; --ps:46px; }
  .fine { position:absolute; left:72px; top:1070px; width:560px; --fn:38px; padding-left:22px; border-left:5px solid var(--green); }
  .cta { position:absolute; left:80px; top:1474px; --cs:46px; }
  `,
  html: `${faixa('80%', '18%')}${logo('claro')}
  <h1 class="h hd" data-dentro><span>Auditoria em</span><span class="g">preparação?</span></h1>
  <p class="p sub" data-dentro>Confira os requisitos de calibração das suas balanças.</p>
  <p class="p inf" data-dentro><strong>Calibração de balanças com emissão de certificado.</strong></p>
  <p class="fine" data-dentro><b>Serviço de calibração não acreditado pela Cgcre.</b> Consulte a adequação aos requisitos da sua empresa.</p>
  ${peso('peso', 'right:96px; top:900px; width:260px;')}
  <p class="cta cta--ink" data-dentro>Solicite um orçamento${ICON_DOWN}</p>`,
});
// Story do perfil: o link é a figurinha de link, posta na área livre logo abaixo de "Toque no link"
arte({
  nome: 'stories-9x16-organico-link', pasta: 'instagram-facebook/9x16', w: 1080, h: 1920, min: 36,
  css: STORY_CSS + `
  .dica { position:absolute; left:72px; top:1350px; width:640px; display:flex; align-items:center; gap:.4em; font:700 46px/1.15 var(--f-text); color: var(--paper); }
  .dica svg { width:1em; height:1em; flex:none; color: var(--green); }
  `,
  html: `${faixa('81%', '17%')}${logo()}
  <span class="tag" data-deco>Novo serviço</span>
  <h1 class="h hd" data-dentro>${T_PRINCIPAL}</h1>
  <p class="p sub" data-dentro>${PEDIDO}</p>
  ${balPeso('left:120px; top:1040px; width:620px;')}
  <p class="dica" data-dentro>Toque no link e solicite um orçamento${ICON_DOWN}</p>`,
});
// Status do WhatsApp: quem vê pode responder; o telefone vai escrito
arte({
  nome: 'status-whatsapp-9x16', pasta: 'instagram-facebook/9x16', w: 1080, h: 1920, min: 36,
  css: STORY_CSS + `
  .resp { position:absolute; left:72px; top:1350px; width:700px; font:400 44px/1.3 var(--f-text); color: var(--body-dark); }
  .resp b { display:flex; align-items:center; gap:.4em; margin-top:.3em; font:700 58px/1.1 var(--f-text); color: var(--paper); }
  .resp svg { width:1em; height:1em; color: var(--green); }
  `,
  html: `${faixa('81%', '17%')}${logo()}
  <span class="tag" data-deco>Novo serviço</span>
  <h1 class="h hd" data-dentro>${T_PRINCIPAL}</h1>
  <p class="p sub" data-dentro>${APOIO}</p>
  ${balPeso('left:120px; top:1040px; width:620px;')}
  <p class="resp" data-dentro>Responda este status ou chame no WhatsApp: <b>${ICON_WA}(19) 3023-9050</b></p>`,
});

/* =====================================================================
   CARROSSEL 4:5 (1080×1350 × 5) — post do perfil, explicativo
   O número da página e o "Arraste" ficam embaixo, à esquerda (fora da faixa).
   ===================================================================== */
const pg = (n) => `<span class="pgn" data-deco>${String(n).padStart(2, '0')}<i>/05</i></span>`;
const CAR_CSS = `
  .lg { position:absolute; left:64px; top:60px; }
  .pgn { position:absolute; left:64px; bottom:62px; font: 900 48px/1 var(--f-display); color: var(--paper); }
  .pgn i { font: 400 22px/1 var(--f-mono); font-style: normal; color: var(--grey); margin-left: 6px; }
  .is-paper .pgn, .is-green .pgn { color: var(--ink); }
  .is-paper .pgn i, .is-green .pgn i { color: rgba(10,10,10,.6); }
  .swipe { position:absolute; left:190px; bottom:68px; display:inline-flex; align-items:center; gap:.5em; font: 500 24px/1 var(--f-mono); letter-spacing:.05em; text-transform:uppercase; }
  .swipe svg { width: 1.4em; height: 1.4em; color: var(--green); }
  .is-green .swipe svg { color: var(--ink); }
`;
const arraste = `<span class="swipe" data-deco>Arraste ${ICON_ARROW}</span>`;
arte({
  nome: 'carrossel-1-capa', pasta: 'instagram-facebook/carrossel', w: 1080, h: 1350, min: 36,
  css: CAR_CSS + `
  .hd { position:absolute; left:64px; top:236px; --hs:102px; }
  .sub { position:absolute; left:64px; top:552px; width:620px; }
  `,
  html: `${faixa('72%', '24%')}${logo()}${pg(1)}
  <h1 class="h hd" data-dentro>${T_PRINCIPAL}</h1>
  <p class="p sub" data-dentro>Conheça o serviço da Balanças.com para o controle de qualidade da sua empresa.</p>
  ${balPeso('right:40px; top:860px; width:580px;')}
  ${arraste}`,
});
arte({
  nome: 'carrossel-2-o-que-e', pasta: 'instagram-facebook/carrossel', w: 1080, h: 1350, min: 36, classe: 'is-paper',
  css: CAR_CSS + `
  .hd { position:absolute; left:64px; top:200px; --hs:130px; }
  .sub { position:absolute; left:64px; top:470px; width:640px; }
  `,
  html: `${faixa('76%', '20%')}${logo('claro')}${pg(2)}
  <h2 class="h hd" data-dentro><span>O que é</span><span class="g">calibração?</span></h2>
  <p class="p sub" data-dentro><strong>Comparamos as indicações da balança com padrões de referência.</strong> Os resultados e a incerteza de medição são registrados no certificado.</p>
  ${peso('peso', 'right:100px; top:520px; width:240px;')}
  ${arraste}`,
});
arte({
  nome: 'carrossel-3-o-que-informa', pasta: 'instagram-facebook/carrossel', w: 1080, h: 1350, min: 36,
  css: CAR_CSS + `
  .hd { position:absolute; left:64px; top:190px; --hs:104px; }
  .ck { position:absolute; left:64px; top:500px; gap:.5em; }
  .fine { position:absolute; left:64px; top:800px; width:420px; }
  .cert-leg { right:64px; top:1176px; }
  `,
  html: `${faixa('72%', '24%')}${logo()}${pg(3)}
  <h2 class="h hd" data-dentro><span>O que o</span><span>certificado</span><span class="g">informa?</span></h2>
  ${lista(['Resultados dos ensaios', 'Incerteza de medição', 'Rastreabilidade'])}
  <p class="fine" data-dentro>Os resultados apresentados correspondem aos ensaios realizados.</p>
  ${legendaCert()}
  ${cert('--c:16px; right:64px; top:766px;')}
  ${arraste}`,
});
arte({
  nome: 'carrossel-4-finalidades', pasta: 'instagram-facebook/carrossel', w: 1080, h: 1350, min: 36, classe: 'is-green',
  css: CAR_CSS + `
  .hd { position:absolute; left:64px; top:180px; --hs:92px; }
  .blk { position:absolute; left:64px; right:64px; top:520px; display:grid; gap:26px; }
  .blk div { border-top: 4px solid var(--ink); padding-top: 16px; display:grid; grid-template-columns: 330px 1fr; gap: 24px; align-items: baseline; }
  .blk div.ipem { border-top-style: dashed; }
  .blk h3 { font: 900 50px/.95 var(--f-display); text-transform: uppercase; }
  .blk h3 small { display:block; margin-top:8px; font: 700 36px/1.2 var(--f-text); text-transform: none; }
  .blk p { font: 400 36px/1.3 var(--f-text); color: #052612; }
  .fim { position:absolute; left:64px; top:1080px; width:900px; font: 700 38px/1.3 var(--f-text); }
  `,
  html: `${logo('placa')}${pg(4)}
  <h2 class="h hd" data-dentro><span>Calibração e verificação</span><span>têm finalidades</span><span>diferentes.</span></h2>
  <div class="blk" data-dentro>
    <div><h3>Calibração<small>Balanças.com</small></h3><p>Compara a balança com padrões de referência e registra resultados e incerteza.</p></div>
    <div><h3>Manutenção<small>Balanças.com</small></h3><p>Conserto e ajuste, com ordem de serviço e lacre.</p></div>
    <div class="ipem"><h3>Verificação<small>IPEM-SP</small></h3><p>Avaliação dos requisitos legais aplicáveis ao instrumento.</p></div>
  </div>
  <p class="fim" data-dentro>A calibração não substitui a verificação metrológica do IPEM-SP.</p>
  ${arraste}`,
});
arte({
  nome: 'carrossel-5-orcamento', pasta: 'instagram-facebook/carrossel', w: 1080, h: 1350, min: 36,
  css: CAR_CSS + `
  .hd { position:absolute; left:64px; top:200px; --hs:130px; }
  .sub { position:absolute; left:64px; top:470px; width:660px; }
  .ctt { position:absolute; left:64px; top:700px; display:grid; gap:10px; font: 400 40px/1.2 var(--f-text); color: var(--body-dark); }
  .ctt b { display:flex; align-items:center; gap:.4em; font: 700 60px/1.1 var(--f-text); color: var(--paper); }
  .ctt svg { width:1em; height:1em; color: var(--green); }
  .fine { position:absolute; left:64px; top:930px; width:620px; padding-left:22px; border-left:5px solid var(--green); }
  `,
  html: `${faixa('80%', '16%')}${logo()}${pg(5)}
  <h2 class="h hd" data-dentro><span>Solicite seu</span><span class="g">orçamento.</span></h2>
  <p class="p sub" data-dentro>${PEDIDO}</p>
  <p class="ctt" data-dentro><b>${ICON_WA}(19) 3023-9050</b><span>WhatsApp · balancass.com</span></p>
  <p class="fine" data-dentro><b>Serviço de calibração não acreditado pela Cgcre.</b> A calibração não substitui a verificação metrológica do IPEM-SP, quando aplicável.</p>`,
});

/* =====================================================================
   CAPA DO FACEBOOK (1640×624) — institucional (não envelhece com "agora")
   No celular aparece o centro (cerca de x 265 a 1375); no computador a
   foto do perfil fica embaixo, à esquerda: o texto começa em x 330.
   ===================================================================== */
arte({
  nome: 'capa-facebook-1640x624', pasta: 'instagram-facebook', w: 1640, h: 624, min: 44,
  css: `
  .hd { position:absolute; left:330px; top:110px; --hs:96px; }
  .sub { position:absolute; left:334px; top:300px; width:660px; --ps:44px; }
  `,
  html: `${faixa('70%', '20%')}
  <h1 class="h hd" data-dentro><span>A medida certa</span><span class="g">para o seu negócio.</span></h1>
  <p class="p sub" data-dentro>Assistência técnica, calibração de balanças e automação comercial. <strong>Rio Claro e região.</strong></p>
  ${balPeso('left:1090px; top:250px; width:320px;')}`,
});

/* =====================================================================
   LINKEDIN — título curto, um benefício, uma ação e a condição de não acreditação
   ===================================================================== */
arte({
  nome: 'linkedin-1200x627', pasta: 'linkedin', w: 1200, h: 627, min: 38,
  css: `
  .lg { position:absolute; left:56px; top:40px; --lg:38px; }
  .hd { position:absolute; left:56px; top:118px; --hs:108px; }
  .sub { position:absolute; left:58px; top:330px; --ps:42px; }
  .cta { position:absolute; left:62px; top:400px; --cs:40px; }
  .fine { position:absolute; left:58px; top:522px; --fn:38px; }
  `,
  html: `${faixa('74%', '22%')}${logo()}
  <h1 class="h hd" data-dentro><span>Calibração</span><span class="g">de balanças</span></h1>
  <p class="p sub" data-dentro>Com certificado · <strong>Rio Claro e região</strong></p>
  <p class="cta" data-dentro>Solicite um orçamento${ICON_ARROW}</p>
  <p class="fine" data-dentro>Serviço não acreditado pela Cgcre.</p>
  ${balPeso('right:30px; top:160px; width:380px;')}`,
});
arte({
  nome: 'linkedin-1200x1200', pasta: 'linkedin', w: 1200, h: 1200, min: 38,
  css: `
  .lg { position:absolute; left:64px; top:60px; }
  .hd { position:absolute; left:64px; top:170px; --hs:92px; }
  .ck { position:absolute; left:64px; top:470px; --ks:44px; gap:.45em; }
  .fine { position:absolute; left:64px; top:720px; width:720px; --fn:38px; }
  .cta { position:absolute; left:70px; top:960px; --cs:44px; }
  `,
  html: `${faixa('76%', '20%')}${logo()}
  <h1 class="h hd" data-dentro><span>Calibração de balanças</span><span class="g">para o controle</span><span class="g">de qualidade.</span></h1>
  ${lista(ENTREGA)}
  ${balPeso('right:30px; top:490px; width:360px;')}
  <p class="fine" data-dentro>${RESSALVA}</p>
  <p class="cta" data-dentro>Solicite um orçamento${ICON_ARROW}</p>
  ${rodape()}`,
});

/* =====================================================================
   SITE — prévia do link (og:image, 1200×630). Não é banner do site.
   ===================================================================== */
arte({
  nome: 'og-calibracao', pasta: 'site', w: 1200, h: 630, jpg: 86, min: 40,
  css: `
  .lg { position:absolute; left:56px; top:48px; --lg:34px; }
  .hd { position:absolute; left:54px; top:150px; --hs:132px; }
  .sub { position:absolute; left:58px; top:430px; --ps:40px; }
  `,
  html: `${faixa('72%', '22%')}${logo()}
  <h1 class="h hd" data-dentro><span>Calibração</span><span class="g">de balanças</span></h1>
  <p class="p sub" data-dentro><strong>Balanças.com</strong> · Rio Claro e região</p>
  ${balPeso('right:40px; top:150px; width:440px;')}`,
});

/* =====================================================================
   GOOGLE DISPLAY — tamanhos fixos (JPG até 150 KB). Só o serviço, o local e a ação.
   ===================================================================== */
const gd = (nome, w, h, min, css, html) => arte({ nome, pasta: 'google-display', w, h, jpg: 88, maxKB: 150, min, css, html });
const lgp = (s, tom = 'escuro') => `<div class="lg" data-deco style="--lg:${s}px">${simbolo(tom)}<span class="lg__t"><b>Balanças<i>.</i>com</b></span></div>`;
const GD_SUB = 'Com certificado · Rio Claro e região';

gd('display-300x250', 300, 250, 12, `
  .lg { position:absolute; left:16px; top:16px; }
  .hd { position:absolute; left:16px; top:58px; --hs:38px; }
  .sub { position:absolute; left:16px; top:136px; width:160px; --ps:14px; line-height:1.35; }
  .cta { position:absolute; left:19px; bottom:18px; --cs:14px; }
`, `${faixa('74%', '24%')}${lgp(15)}
  <p class="h hd" data-dentro><span>Calibração</span><span class="g">de balanças</span></p>
  <p class="p sub" data-dentro>${GD_SUB}</p>
  ${peso('peso', 'right:20px; bottom:22px; width:56px;')}
  <p class="cta" data-dentro>Solicite orçamento</p>`);

gd('display-336x280', 336, 280, 12, `
  .lg { position:absolute; left:18px; top:18px; }
  .hd { position:absolute; left:18px; top:64px; --hs:42px; }
  .sub { position:absolute; left:18px; top:152px; width:180px; --ps:15px; line-height:1.35; }
  .cta { position:absolute; left:21px; bottom:20px; --cs:15px; }
`, `${faixa('74%', '24%')}${lgp(16)}
  <p class="h hd" data-dentro><span>Calibração</span><span class="g">de balanças</span></p>
  <p class="p sub" data-dentro>${GD_SUB}</p>
  ${peso('peso', 'right:22px; bottom:24px; width:62px;')}
  <p class="cta" data-dentro>Solicite orçamento</p>`);

gd('display-728x90', 728, 90, 13, `
  .lg { position:absolute; left:16px; top:29px; }
  .hd { position:absolute; left:176px; top:17px; --hs:31px; }
  .sub { position:absolute; left:178px; top:54px; --ps:14px; }
  .cta { position:absolute; right:66px; top:25px; --cs:15px; }
`, `${faixa('95%', '9%')}${lgp(16)}
  <p class="h hd" data-dentro><span>Calibração <span class="g" style="display:inline">de balanças</span></span></p>
  <p class="p sub" data-dentro>${GD_SUB}</p>
  <p class="cta" data-dentro>Solicite orçamento</p>`);

gd('display-300x600', 300, 600, 13, `
  .lg { position:absolute; left:20px; top:22px; }
  .hd { position:absolute; left:20px; top:76px; --hs:46px; }
  .sub { position:absolute; left:20px; top:198px; width:200px; --ps:16px; line-height:1.4; }
  .cta { position:absolute; left:23px; bottom:26px; --cs:16px; }
`, `${faixa('77%', '21%')}${lgp(16)}
  <p class="h hd" data-dentro><span>Calibração</span><span class="g">de balanças</span></p>
  <p class="p sub" data-dentro>${GD_SUB}</p>
  ${balPeso('left:14px; top:330px; width:272px;')}
  <p class="cta" data-dentro>Solicite orçamento</p>`);

gd('display-160x600', 160, 600, 12, `
  .lg { position:absolute; left:12px; top:18px; }
  .hd { position:absolute; left:12px; top:70px; --hs:26px; }
  .sub { position:absolute; left:12px; top:134px; width:104px; --ps:13px; line-height:1.4; }
  .cta { position:absolute; left:15px; top:226px; --cs:13px; padding:.75em .9em .7em; }
`, `${faixa('76%', '30%')}${lgp(13)}
  <p class="h hd" data-dentro><span>Calibração</span><span class="g">de balanças</span></p>
  <p class="p sub" data-dentro>${GD_SUB}</p>
  <p class="cta" data-dentro>Orçamento${ICON_ARROW}</p>
  ${peso('peso', 'left:30px; top:330px; width:100px;')}`);

gd('display-320x100', 320, 100, 12, `
  .lg { position:absolute; left:12px; top:12px; }
  .hd { position:absolute; left:12px; top:38px; --hs:25px; }
  .cta { position:absolute; right:12px; bottom:14px; --cs:13px; padding:.7em .9em .66em; }
`, `${faixa('80%', '12%')}${lgp(12)}
  <p class="h hd" data-dentro><span>Calibração</span><span class="g">de balanças</span></p>
  <p class="cta" data-dentro>Orçamento${ICON_ARROW}</p>`);

gd('display-320x50', 320, 50, 11, `
  .si { position:absolute; left:10px; top:10px; width:30px; height:30px; }
  .hd { position:absolute; left:50px; top:7px; --hs:19px; line-height:.95; }
  .cta { position:absolute; right:10px; top:10px; --cs:13px; padding:.6em .9em .56em; }
`, `${simbolo('escuro', 'si')}
  <p class="h hd" data-dentro><span>Calibração</span><span class="g">de balanças</span></p>
  <p class="cta" data-dentro>Orçamento</p>`);

/* Anúncio responsivo do Google: imagens sem texto (o Google recomenda; títulos e logo vão nos
   campos próprios). São composições ilustrativas: troque por fotos reais do atendimento assim que houver. */
arte({ nome: 'responsivo-paisagem-1200x628', pasta: 'google-display/responsivo', w: 1200, h: 628, jpg: 88,
  html: `${faixa('36%', '30%')}${balPeso('left:200px; top:150px; width:800px;')}` });
arte({ nome: 'responsivo-quadrado-1200x1200', pasta: 'google-display/responsivo', w: 1200, h: 1200, jpg: 88,
  html: `${faixa('34%', '32%')}${balPeso('left:110px; top:380px; width:980px;')}` });
arte({ nome: 'logo-quadrado-1200x1200', pasta: 'google-display/responsivo', w: 1200, h: 1200,
  css: `.art{display:grid;place-items:center} .lg__i{width:760px;height:760px}`,
  html: `${simbolo()}` });
arte({ nome: 'logo-paisagem-1200x300', pasta: 'google-display/responsivo', w: 1200, h: 300,
  css: `.art{display:grid;place-items:center} .lg{--lg:120px}`,
  html: `${logo()}` });

/* Grupos da galeria (ver-todas.html): título, nota e quais artes entram */
const em = (p) => (a) => a.pasta === p;
export const GRUPOS = [
  ['Instagram e Facebook · feed (4:5) e quadrado (1:1)', 'Anúncio ou post. A versão B fala de auditoria e por isso traz a condição de não acreditação na própria arte.', (a) => a.pasta === 'instagram-facebook' && !a.nome.startsWith('capa')],
  ['Anúncio vertical · Stories e Reels', 'Para anúncio: a seta aponta para o botão da plataforma, que é o link. Não são vídeos.', (a) => a.nome.startsWith('stories-9x16-anuncio')],
  ['Story do perfil (orgânico)', 'Ponha a figurinha de link logo abaixo de “Toque no link”.', (a) => a.nome === 'stories-9x16-organico-link'],
  ['Status do WhatsApp', 'Quem vê pode responder o status; o telefone está escrito.', (a) => a.nome === 'status-whatsapp-9x16'],
  ['Carrossel do perfil (na ordem)', '', em('instagram-facebook/carrossel')],
  ['Capa do Facebook', 'Institucional. Confira no celular e no computador o corte e a foto do perfil.', (a) => a.nome.startsWith('capa')],
  ['LinkedIn', 'Para o feed, prefira a quadrada; a horizontal serve para anúncio com link.', em('linkedin')],
  ['Google Display · tamanhos fixos', 'JPG abaixo de 150 KB.', em('google-display')],
  ['Google Display responsivo · imagens sem texto e logos', 'Composições ilustrativas: troque as duas imagens por fotos reais do atendimento assim que houver.', em('google-display/responsivo')],
  ['Site · prévia do link (og:image)', 'Vai no pacote do site (atualizacao.zip). Confira a prévia na página publicada.', em('site')],
];
