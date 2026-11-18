// As artes da campanha. Cada uma: nome, pasta, tamanho, classe do fundo, CSS próprio e HTML.
// A linguagem muda com a peça (quem vê, em que momento, o que se pede): ver ../TEXTOS-DOS-ANUNCIOS.md.
// Regras de texto de todas as peças (vêm do sistema de certificados e da pesquisa de normas):
//   - o serviço é "calibração" com "certificado"; nunca "aferição" como nome do serviço;
//   - não usar "RBC", "acreditado", "Inmetro" como selo; "não RBC" só para explicar;
//   - "atende a maioria das auditorias ISO 9001" (nunca "vale para todas");
//   - o certificado não tem QR code: tem número e código e sai em PDF assinado digitalmente;
//   - nada de "validade" nem de "substitui o IPEM".
import { logo, simbolo, cert, balanca, peso, rodape, ICON_WA, ICON_ARROW } from './pecas.mjs';

const fundo = (bx, bw, extra = '') => `<div class="grid"></div><div class="band ${extra}" style="--bx:${bx};--bw:${bw}"></div><span class="crop crop--tl"></span>`;
const ICON_DOWN = `<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="square" d="M12 4v15M6 13l6 6 6-6"/></svg>`;
const FINE = 'Certificado não RBC · atende a maioria das auditorias ISO 9001';
const ITENS = ['Erro de indicação', 'Repetibilidade', 'Excentricidade', 'Incerteza de medição', 'Padrões rastreáveis', 'PDF assinado digitalmente'];
const lista = (itens = ITENS) => `<ul class="ck">${itens.map((i) => `<li>${i}</li>`).join('')}</ul>`;

export const ARTES = [];
const arte = (a) => ARTES.push(a);

/* =====================================================================
   INSTAGRAM E FACEBOOK — FEED 4:5 (1080×1350)
   ===================================================================== */

// A · LANÇAMENTO — quem já segue ou já é cliente: novidade, tom próximo
arte({
  nome: 'feed-4x5-A-lancamento', pasta: 'instagram-facebook', w: 1080, h: 1350,
  css: `
  .top { position:absolute; left:64px; right:64px; top:56px; display:flex; justify-content:space-between; align-items:center; }
  .tg { position:absolute; left:70px; top:176px; }
  .hd { position:absolute; left:64px; top:238px; --hs:122px; }
  .sub { position:absolute; left:64px; top:600px; width:420px; --ps:30px; }
  .cta { position:absolute; left:70px; bottom:128px; --cs:27px; }
  `,
  html: `${fundo('66%', '28%')}
  <div class="top">${logo()}</div>
  <span class="tag tg" style="--tg:24px">Novo serviço</span>
  <h1 class="h hd" data-dentro><span>Agora com</span><span>certificado de</span><span class="g">calibração.</span></h1>
  <p class="p sub" data-dentro><strong>Para a sua balança.</strong> Erro, incerteza e rastreabilidade num PDF assinado digitalmente.</p>
  ${cert('--c:22px; left:506px; top:648px;')}
  ${balanca('left:-26px; bottom:226px; width:440px;')}
  ${peso('peso', 'right:56px; bottom:116px; width:128px;')}
  <span class="call" style="left:600px; top:604px"><b>01</b> Erro e incerteza</span>
  <a class="cta" data-dentro>${ICON_WA}Peça seu orçamento</a>
  ${rodape()}
  `,
});

// B · AUDITORIA — público frio (tráfego pago): começa pela dor, fundo claro para variar no teste A/B
arte({
  nome: 'feed-4x5-B-auditoria', pasta: 'instagram-facebook', w: 1080, h: 1350, classe: 'is-paper',
  css: `
  .top { position:absolute; left:64px; right:64px; top:56px; display:flex; justify-content:space-between; align-items:center; }
  .hd { position:absolute; left:64px; top:186px; --hs:118px; }
  .sub { position:absolute; left:64px; top:660px; width:440px; --ps:30px; }
  .cta { position:absolute; left:70px; top:880px; --cs:27px; }
  .fine { position:absolute; left:64px; top:990px; width:430px; }
  .ft { background: var(--ink); }
  `,
  html: `${fundo('70%', '24%')}
  <div class="top">${logo()}<span class="tag tag--ink" style="--tg:21px">ISO 9001 · Qualidade</span></div>
  <h1 class="h hd" data-dentro><span>Auditoria</span><span>chegando?</span><span class="hl">Calibre a</span><span class="hl">balança antes.</span></h1>
  <p class="p sub" data-dentro>O certificado de calibração mostra o erro e a incerteza da sua balança, com a rastreabilidade que o auditor pede.</p>
  <a class="cta cta--ink" data-dentro>${ICON_WA}Peça seu orçamento</a>
  <p class="fine" data-dentro>${FINE}</p>
  ${cert('--c:22px; left:548px; top:664px;')}
  ${peso('peso', 'right:64px; bottom:94px; width:118px;')}
  <span class="call" style="right:40px; top:622px"><b>✓</b> Pronto para mostrar</span>
  ${rodape()}
  `,
});

// C · CHECKLIST — quem já viu A ou B (remarketing): o que vem no certificado
arte({
  nome: 'feed-4x5-C-o-que-vem', pasta: 'instagram-facebook', w: 1080, h: 1350,
  css: `
  .top { position:absolute; left:64px; right:64px; top:56px; display:flex; justify-content:space-between; align-items:center; }
  .hd { position:absolute; left:64px; top:180px; --hs:150px; }
  .k2 { position:absolute; left:64px; top:676px; }
  .ck { position:absolute; left:64px; top:736px; --ks:33px; gap:.62em; }
  .cta { position:absolute; left:70px; bottom:128px; --cs:27px; }
  `,
  html: `${fundo('62%', '32%')}
  <div class="top">${logo()}<span class="tag" style="--tg:21px">Certificado de calibração</span></div>
  <h1 class="h hd" data-dentro><span>Mede.</span><span>Calcula.</span><span class="g">Certifica.</span></h1>
  <p class="kicker k2">O que vem <b>no certificado</b></p>
  ${lista()}
  ${peso('peso', 'right:150px; top:250px; width:250px;')}
  ${cert('--c:16px; right:30px; top:700px;')}
  <a class="cta" data-dentro>${ICON_WA}Peça seu orçamento</a>
  ${rodape()}
  `,
});

/* =====================================================================
   QUADRADO 1:1 (1080×1080) — feed do Facebook, WhatsApp, Google (quadrado)
   ===================================================================== */
arte({
  nome: 'quadrado-1x1-A-lancamento', pasta: 'instagram-facebook', w: 1080, h: 1080,
  css: `
  .top { position:absolute; left:60px; right:60px; top:52px; display:flex; justify-content:space-between; align-items:center; }
  .hd { position:absolute; left:60px; top:160px; --hs:112px; }
  .sub { position:absolute; left:60px; top:500px; width:400px; --ps:28px; }
  .cta { position:absolute; left:66px; bottom:122px; --cs:25px; }
  .ft { --ftp: 22px; --fs: 19px; --pad: 60px; }
  `,
  html: `${fundo('66%', '28%')}
  <div class="top">${logo()}<span class="tag" style="--tg:22px">Novo serviço</span></div>
  <h1 class="h hd" data-dentro><span>Agora com</span><span>certificado de</span><span class="g">calibração.</span></h1>
  <p class="p sub" data-dentro><strong>Para a sua balança</strong>, com erro, incerteza e rastreabilidade.</p>
  ${cert('--c:18px; left:560px; top:520px;')}
  ${balanca('left:-20px; bottom:190px; width:360px;')}
  ${peso('peso', 'right:44px; bottom:92px; width:104px;')}
  <a class="cta" data-dentro>${ICON_WA}Peça seu orçamento</a>
  ${rodape()}
  `,
});
arte({
  nome: 'quadrado-1x1-B-auditoria', pasta: 'instagram-facebook', w: 1080, h: 1080, classe: 'is-paper',
  css: `
  .top { position:absolute; left:60px; right:60px; top:52px; display:flex; justify-content:space-between; align-items:center; }
  .hd { position:absolute; left:60px; top:160px; --hs:118px; }
  .sub { position:absolute; left:60px; top:640px; width:440px; --ps:27px; }
  .cta { position:absolute; left:66px; bottom:122px; --cs:25px; }
  .ft { --ftp: 22px; --fs: 19px; --pad: 60px; }
  `,
  html: `${fundo('70%', '24%')}
  <div class="top">${logo()}<span class="tag tag--ink" style="--tg:20px">ISO 9001 · Qualidade</span></div>
  <h1 class="h hd" data-dentro><span>Auditoria</span><span>chegando?</span><span class="hl">Calibre antes.</span></h1>
  <p class="p sub" data-dentro>Certificado de calibração com erro, incerteza e rastreabilidade, feito onde a balança trabalha.</p>
  ${cert('--c:19px; left:560px; top:470px;')}
  ${peso('peso', 'left:500px; bottom:86px; width:120px;')}
  <a class="cta cta--ink" data-dentro>${ICON_WA}Peça seu orçamento</a>
  ${rodape()}
  `,
});

/* =====================================================================
   STORIES E REELS 9:16 (1080×1920) — também status do WhatsApp
   Área segura: nada importante nos 250 px de cima nem nos 340 px de baixo
   (onde ficam o perfil, a barra de resposta e o botão do anúncio).
   ===================================================================== */
arte({
  nome: 'stories-9x16-A-lancamento', pasta: 'instagram-facebook', w: 1080, h: 1920,
  css: `
  .top { position:absolute; left:72px; right:72px; top:260px; display:flex; justify-content:space-between; align-items:center; --lg:34px; }
  .hd { position:absolute; left:72px; top:420px; --hs:128px; }
  .sub { position:absolute; left:72px; top:900px; width:640px; --ps:38px; }
  .cta { position:absolute; left:80px; top:1440px; --cs:32px; }
  `,
  html: `${fundo('66%', '30%')}
  <div class="top">${logo()}<span class="tag" style="--tg:26px">Novidade</span></div>
  <h1 class="h hd" data-dentro><span>Certificado</span><span>de calibração</span><span class="g">para a sua</span><span class="g">balança.</span></h1>
  <p class="p sub" data-dentro>Sem tirar a balança do lugar. <strong>Em PDF, assinado digitalmente.</strong></p>
  ${cert('--c:20px; right:40px; top:1030px;')}
  ${balanca('left:-40px; top:1070px; width:420px;')}
  ${peso('peso', 'left:372px; top:1240px; width:112px;')}
  <a class="cta" data-dentro>Peça seu orçamento${ICON_DOWN}</a>
  `,
});
arte({
  nome: 'stories-9x16-B-auditoria', pasta: 'instagram-facebook', w: 1080, h: 1920, classe: 'is-paper',
  css: `
  .top { position:absolute; left:72px; right:72px; top:260px; display:flex; justify-content:space-between; align-items:center; --lg:34px; }
  .hd { position:absolute; left:72px; top:400px; --hs:150px; }
  .sub { position:absolute; left:72px; top:990px; width:560px; --ps:36px; }
  .cta { position:absolute; left:80px; top:1440px; --cs:32px; }
  .fine { position:absolute; left:72px; top:1130px; width:540px; --fn:20px; }
  `,
  html: `${fundo('68%', '28%')}
  <div class="top">${logo()}<span class="tag tag--ink" style="--tg:24px">ISO 9001</span></div>
  <h1 class="h hd" data-dentro><span>Auditoria</span><span>chegando?</span><span class="hl">Calibre</span><span class="hl">antes.</span></h1>
  <p class="p sub" data-dentro>O certificado da sua balança, com erro, incerteza e rastreabilidade.</p>
  ${cert('--c:17px; right:-50px; top:1040px;')}
  ${peso('peso', 'left:560px; top:1230px; width:116px;')}
  <p class="fine" data-dentro>${FINE}</p>
  <a class="cta cta--ink" data-dentro>Peça seu orçamento${ICON_DOWN}</a>
  `,
});

/* =====================================================================
   CARROSSEL 4:5 (1080×1350 × 5) — post do perfil: explica e conversa
   ===================================================================== */
const pg = (n) => `<span class="pgn">${String(n).padStart(2, '0')}<i>/05</i></span>`;
const CAR_CSS = `
  .top { position:absolute; left:64px; right:64px; top:56px; display:flex; justify-content:space-between; align-items:center; }
  .pgn { font: 900 44px/1 var(--f-display); color: var(--paper); }
  .is-paper .pgn, .is-green .pgn { color: var(--ink); }
  .pgn i { font: 400 20px/1 var(--f-mono); font-style: normal; color: var(--grey); margin-left: 6px; }
  .is-paper .pgn i, .is-green .pgn i, .on-band .pgn i { color: rgba(10,10,10,.65); }
  .swipe { position:absolute; right:64px; bottom:70px; display:inline-flex; align-items:center; gap:.6em; font: 500 22px/1 var(--f-mono); letter-spacing:.05em; text-transform:uppercase; }
  .swipe svg { width: 1.4em; height: 1.4em; color: var(--green); }
  .is-green .swipe svg { color: var(--ink); }
`;
arte({
  nome: 'carrossel-1-capa', pasta: 'instagram-facebook/carrossel', w: 1080, h: 1350,
  css: CAR_CSS + `
  .tg { position:absolute; left:70px; top:190px; }
  .hd { position:absolute; left:64px; top:250px; --hs:106px; }
  .sub { position:absolute; left:64px; bottom:150px; width:520px; --ps:32px; }
  `,
  html: `${fundo('70%', '26%')}
  <div class="top">${logo()}${pg(1)}</div>
  <span class="tag tg" style="--tg:24px">Novo na Balanças.com</span>
  <h1 class="h hd" data-dentro><span>Sua balança</span><span>tem certificado</span><span class="g">de calibração?</span></h1>
  ${cert('--c:19px; right:40px; top:640px;')}
  ${peso('peso', 'right:500px; top:830px; width:130px;')}
  <p class="p sub" data-dentro><strong>Agora a gente emite.</strong> Arraste para ver o que muda para a sua empresa.</p>
  <span class="swipe">Arraste ${ICON_ARROW}</span>
  `,
});
arte({
  nome: 'carrossel-2-o-que-e', pasta: 'instagram-facebook/carrossel', w: 1080, h: 1350, classe: 'is-paper',
  css: CAR_CSS + `
  .hd { position:absolute; left:64px; top:190px; --hs:150px; }
  .p1 { position:absolute; left:64px; top:530px; width:640px; --ps:38px; }
  .p2 { position:absolute; left:64px; top:820px; width:470px; --ps:30px; }
  `,
  html: `${fundo('70%', '24%')}
  <div class="top">${logo()}${pg(2)}</div>
  <h2 class="h hd" data-dentro><span>O que é</span><span class="g">calibração?</span></h2>
  <p class="p p1" data-dentro><strong>É medir quanto a sua balança erra</strong> — e com que segurança — usando pesos-padrão de massa conhecida.</p>
  <p class="p p2" data-dentro>O resultado vai num certificado, com os números de cada ponto. É o documento que a qualidade, o auditor e muitos clientes pedem.</p>
  ${peso('peso', 'left:590px; top:900px; width:150px;')}
  ${peso('peso', 'left:740px; top:960px; width:110px;')}
  ${peso('peso', 'left:850px; top:1010px; width:80px;')}
  <span class="swipe">Arraste ${ICON_ARROW}</span>
  `,
});
arte({
  nome: 'carrossel-3-o-que-vem', pasta: 'instagram-facebook/carrossel', w: 1080, h: 1350,
  css: CAR_CSS + `
  .hd { position:absolute; left:64px; top:190px; --hs:128px; }
  .ck { position:absolute; left:64px; top:540px; --ks:36px; gap:.7em; }
  .fine { position:absolute; left:64px; bottom:150px; width:600px; }
  `,
  html: `${fundo('68%', '28%')}
  <div class="top">${logo()}${pg(3)}</div>
  <h2 class="h hd" data-dentro><span>O que vem no</span><span class="g">certificado.</span></h2>
  ${lista()}
  ${cert('--c:15px; right:-30px; top:560px;')}
  <p class="fine" data-dentro>Segue a ABNT NBR ISO/IEC 17025 (item 7.8) e o guia EURAMET cg-18 no que vale para um laboratório não acreditado.</p>
  <span class="swipe">Arraste ${ICON_ARROW}</span>
  `,
});
arte({
  nome: 'carrossel-4-nao-e-afericao', pasta: 'instagram-facebook/carrossel', w: 1080, h: 1350, classe: 'is-green',
  css: CAR_CSS + `
  .hd { position:absolute; left:64px; top:190px; --hs:132px; }
  .cols { position:absolute; left:64px; right:64px; top:560px; display:grid; grid-template-columns:1fr 1fr; gap:40px; }
  .col { border-top: 4px solid var(--ink); padding-top: 22px; display:grid; gap:14px; align-content:start; }
  .col h3 { font: 900 46px/.95 var(--f-display); text-transform: uppercase; }
  .col p { font: 400 27px/1.4 var(--f-text); color:#052612; }
  .col .k { font: 500 19px/1.2 var(--f-mono); letter-spacing:.05em; text-transform:uppercase; }
  .dup { position:absolute; left:64px; top:930px; width:820px; --ps:36px; }
  `,
  html: `<div class="grid"></div><span class="crop crop--tl"></span>
  <div class="top">${logo()}${pg(4)}</div>
  <h2 class="h hd" data-dentro><span>Calibração</span><span>não é aferição.</span></h2>
  <div class="cols" data-dentro>
    <div class="col"><span class="k">Calibração · com certificado</span><h3>Para a qualidade</h3><p>Mostra o erro e a incerteza da balança. Atende auditorias, o controle de qualidade e o pedido de clientes.</p></div>
    <div class="col"><span class="k">Verificação do IPEM-SP</span><h3>Para a lei</h3><p>A antiga “aferição”: obrigatória para balança que vende por peso, uma vez por ano.</p></div>
  </div>
  <p class="p dup" data-dentro><strong>Uma não substitui a outra.</strong> E a gente cuida das duas: a calibração e a manutenção para o IPEM.</p>
  <span class="swipe">Arraste ${ICON_ARROW}</span>
  `,
});
arte({
  nome: 'carrossel-5-orcamento', pasta: 'instagram-facebook/carrossel', w: 1080, h: 1350,
  css: CAR_CSS + `
  .hd { position:absolute; left:64px; top:190px; --hs:150px; }
  .st { position:absolute; left:64px; width:700px; top:560px; display:grid; gap:0; list-style:none; counter-reset:s; }
  .st li { display:grid; grid-template-columns:110px 1fr; align-items:baseline; padding:20px 0 18px; border-top:1px solid var(--line); font: 400 30px/1.35 var(--f-text); color: var(--body-dark); }
  .st li b { color: var(--paper); font-weight: 700; }
  .st li::before { counter-increment:s; content: counter(s, decimal-leading-zero); font: 900 54px/.8 var(--f-display); color: transparent; -webkit-text-stroke: 1.5px var(--green); }
  .cta { position:absolute; left:70px; top:1080px; --cs:28px; }
  .fine { position:absolute; left:64px; top:1190px; width:660px; }
  .ft { --ftp: 22px; }
  `,
  html: `${fundo('74%', '20%')}
  <div class="top">${logo()}${pg(5)}</div>
  <h2 class="h hd" data-dentro><span>Peça seu</span><span class="g">orçamento.</span></h2>
  <ol class="st" data-dentro>
    <li><span><b>Mande o modelo e a capacidade</b> da balança pelo WhatsApp.</span></li>
    <li><span><b>Combinamos o dia</b> que não atrapalha o seu trabalho.</span></li>
    <li><span><b>Calibramos no local</b>, sem abrir a balança.</span></li>
    <li><span><b>O certificado chega em PDF</b>, assinado digitalmente.</span></li>
  </ol>
  <a class="cta" data-dentro>${ICON_WA}(19) 3023-9050</a>
  <p class="fine" data-dentro>${FINE}. Precisa de RBC? Fale com a gente antes.</p>
  ${rodape('', { site: true })}
  `,
});

/* =====================================================================
   CAPA DO FACEBOOK (1640×624) — o centro aparece no celular; a foto do
   perfil cobre o canto de baixo à esquerda no computador
   ===================================================================== */
arte({
  nome: 'capa-facebook-1640x624', pasta: 'instagram-facebook', w: 1640, h: 624,
  css: `
  .tg { position:absolute; left:330px; top:120px; }
  .hd { position:absolute; left:324px; top:166px; --hs:94px; }
  .sub { position:absolute; left:330px; top:434px; width:600px; --ps:26px; }
  `,
  html: `${fundo('66%', '22%')}
  <span class="tag tg" style="--tg:20px">Novo serviço</span>
  <h1 class="h hd" data-dentro><span>Agora com</span><span>certificado de</span><span class="g">calibração.</span></h1>
  <p class="p sub" data-dentro>Balanças, PDV, NFC-e e, agora, <strong>calibração com certificado</strong>. Rio Claro e região.</p>
  ${cert('--c:15px; left:1050px; top:110px;')}
  ${balanca('left:1000px; top:300px; width:300px;')}
  ${peso('peso', 'left:1380px; top:380px; width:96px;')}
  `,
});

/* =====================================================================
   LINKEDIN (e anúncio com link no Facebook) — empresa para empresa
   ===================================================================== */
arte({
  nome: 'linkedin-1200x627', pasta: 'linkedin', w: 1200, h: 627,
  css: `
  .top { position:absolute; left:56px; top:46px; --lg:26px; }
  .k2 { position:absolute; left:58px; top:138px; --kk:17px; }
  .hd { position:absolute; left:56px; top:174px; --hs:70px; }
  .sub { position:absolute; left:58px; top:440px; width:580px; --ps:21px; }
  .ft { --ftp: 16px; --fs: 16px; --pad: 56px; }
  `,
  html: `${fundo('62%', '28%')}
  <div class="top">${logo()}</div>
  <p class="kicker k2">Novo serviço <b>· Calibração</b></p>
  <h1 class="h hd" data-dentro><span>Certificado de</span><span>calibração para</span><span class="g">a sua auditoria</span><span class="g">ISO 9001.</span></h1>
  <p class="p sub" data-dentro>Ensaios no local, incerteza pelo EURAMET cg-18 e rastreabilidade dos pesos-padrão. <strong>Rio Claro e região.</strong></p>
  ${cert('--c:15px; left:760px; top:70px;')}
  ${peso('peso', 'right:40px; bottom:60px; width:96px;')}
  ${rodape('', { site: true })}
  `,
});
arte({
  nome: 'linkedin-1200x1200', pasta: 'linkedin', w: 1200, h: 1200,
  css: `
  .top { position:absolute; left:64px; right:64px; top:58px; display:flex; justify-content:space-between; align-items:center; }
  .hd { position:absolute; left:64px; top:170px; --hs:112px; }
  .ck { position:absolute; left:64px; top:620px; --ks:30px; gap:.6em; }
  .fine { position:absolute; left:64px; bottom:120px; width:620px; }
  .ft { --ftp: 22px; --fs: 19px; }
  `,
  html: `${fundo('66%', '28%')}
  <div class="top">${logo()}<span class="tag" style="--tg:20px">Para a área da qualidade</span></div>
  <h1 class="h hd" data-dentro><span>A auditoria</span><span>pede.</span><span class="g">A gente</span><span class="g">certifica.</span></h1>
  ${lista()}
  ${cert('--c:17px; right:-20px; top:600px;')}
  <p class="fine" data-dentro>${FINE}. Laboratório não acreditado; não substitui a verificação do IPEM-SP.</p>
  ${rodape('', { site: true })}
  `,
});

/* =====================================================================
   SITE — imagem de compartilhamento da página (og:image, 1200×630)
   ===================================================================== */
arte({
  nome: 'og-calibracao', pasta: 'site', w: 1200, h: 630, jpg: 86,
  css: `
  .top { position:absolute; left:56px; top:48px; --lg:26px; }
  .hd { position:absolute; left:54px; top:150px; --hs:118px; }
  .sub { position:absolute; left:58px; top:500px; width:520px; --ps:23px; }
  `,
  html: `${fundo('60%', '30%')}
  <div class="top">${logo()}</div>
  <h1 class="h hd" data-dentro><span>Mede.</span><span>Calcula.</span><span class="g">Certifica.</span></h1>
  <p class="p sub" data-dentro><strong>Certificado de calibração</strong> para a sua balança. Rio Claro e região.</p>
  ${cert('--c:16px; left:640px; top:60px;')}
  ${balanca('left:560px; top:330px; width:330px;')}
  ${peso('peso', 'right:60px; top:400px; width:110px;')}
  `,
});

/* =====================================================================
   GOOGLE DISPLAY — tamanhos padrão (JPG até 150 KB)
   Só o essencial: o que é, para quem, onde, e o pedido.
   ===================================================================== */
// (sem a marca de canto: nos tamanhos pequenos ela encostaria no logo)
const gd = (nome, w, h, css, html) => arte({ nome, pasta: 'google-display', w, h, jpg: 88, maxKB: 150, css, html: html.replace('<span class="crop crop--tl"></span>', '') });
const lgp = (s) => `<div class="lg" style="--lg:${s}px">${simbolo()}<span class="lg__t"><b>Balanças<i>.</i>com</b></span></div>`;

gd('display-300x250', 300, 250, `
  .lg { position:absolute; left:16px; top:15px; }
  .hd { position:absolute; left:16px; top:52px; --hs:31px; }
  .sub { position:absolute; left:16px; top:122px; width:168px; --ps:12.5px; line-height:1.4; }
  .cta { position:absolute; left:19px; bottom:17px; --cs:12px; }
`, `${fundo('72%', '26%')}${lgp(13)}
  <p class="h hd" data-dentro><span>Certificado de</span><span class="g">calibração</span></p>
  <p class="p sub" data-dentro>Erro, incerteza e rastreabilidade da sua balança. Rio Claro e região.</p>
  ${peso('peso', 'right:22px; bottom:20px; width:64px;')}
  <a class="cta" data-dentro>Peça orçamento${ICON_ARROW}</a>`);

gd('display-336x280', 336, 280, `
  .lg { position:absolute; left:18px; top:17px; }
  .hd { position:absolute; left:18px; top:58px; --hs:35px; }
  .sub { position:absolute; left:18px; top:138px; width:186px; --ps:13.5px; line-height:1.4; }
  .cta { position:absolute; left:21px; bottom:19px; --cs:13px; }
`, `${fundo('72%', '26%')}${lgp(14)}
  <p class="h hd" data-dentro><span>Certificado de</span><span class="g">calibração</span></p>
  <p class="p sub" data-dentro>Erro, incerteza e rastreabilidade da sua balança. Rio Claro e região.</p>
  ${peso('peso', 'right:24px; bottom:22px; width:72px;')}
  <a class="cta" data-dentro>Peça orçamento${ICON_ARROW}</a>`);

gd('display-728x90', 728, 90, `
  .lg { position:absolute; left:16px; top:27px; }
  .hd { position:absolute; left:184px; top:17px; --hs:26px; }
  .sub { position:absolute; left:185px; top:52px; --ps:12.5px; }
  .cta { position:absolute; right:20px; top:24px; --cs:12.5px; }
`, `<div class="grid"></div><div class="band" style="--bx:65%;--bw:13%"></div>${lgp(16)}
  <p class="h hd" data-dentro><span>Certificado de <span class="g" style="display:inline">calibração</span></span></p>
  <p class="p sub" data-dentro>Para a sua balança · Rio Claro e região</p>
  ${peso('peso', 'left:486px; top:16px; width:40px;')}
  <a class="cta" data-dentro>Peça orçamento${ICON_ARROW}</a>`);

gd('display-300x600', 300, 600, `
  .lg { position:absolute; left:20px; top:22px; }
  .tg { position:absolute; left:23px; top:72px; }
  .hd { position:absolute; left:20px; top:110px; --hs:44px; }
  .sub { position:absolute; left:20px; top:250px; width:190px; --ps:15px; line-height:1.4; }
  .cta { position:absolute; left:23px; bottom:24px; --cs:14px; }
`, `${fundo('72%', '30%')}${lgp(15)}
  <span class="tag tg" style="--tg:11px">Novo serviço</span>
  <p class="h hd" data-dentro><span>Certificado</span><span>de</span><span class="g">calibração</span></p>
  <p class="p sub" data-dentro>Erro, incerteza e rastreabilidade da sua balança. Rio Claro e região.</p>
  ${cert('--c:8.4px; left:34px; top:338px;')}
  ${peso('peso', 'right:16px; top:424px; width:54px;')}
  <a class="cta" data-dentro>Peça orçamento${ICON_ARROW}</a>`);

gd('display-160x600', 160, 600, `
  .lg { position:absolute; left:12px; top:18px; }
  .hd { position:absolute; left:12px; top:66px; --hs:24px; }
  .sub { position:absolute; left:12px; top:162px; width:136px; --ps:12.5px; line-height:1.4; }
  .cta { position:absolute; left:15px; bottom:20px; --cs:11px; padding:.9em 1.15em .84em; }
`, `${fundo('66%', '40%')}${lgp(10)}
  <p class="h hd" data-dentro><span>Certificado</span><span>de</span><span class="g">calibração</span></p>
  <p class="p sub" data-dentro>Para a sua balança. Rio Claro e região.</p>
  ${peso('peso', 'left:46px; top:330px; width:80px;')}
  <a class="cta" data-dentro>Orçamento${ICON_ARROW}</a>`);

gd('display-320x100', 320, 100, `
  .lg { position:absolute; left:12px; top:12px; }
  .hd { position:absolute; left:12px; top:42px; --hs:22px; }
  .cta { position:absolute; right:12px; bottom:13px; --cs:10.5px; padding:.85em 1.1em .8em; }
`, `<div class="grid"></div><div class="band" style="--bx:74%;--bw:16%"></div>${lgp(11)}
  <p class="h hd" data-dentro><span>Certificado de</span><span class="g">calibração</span></p>
  ${peso('peso', 'right:22px; top:7px; width:30px;')}
  <a class="cta" data-dentro>Orçamento${ICON_ARROW}</a>`);

gd('display-320x50', 320, 50, `
  .lg { position:absolute; left:10px; top:13px; }
  .hd { position:absolute; left:120px; top:10px; --hs:15px; line-height:.95; }
  .cta { position:absolute; right:10px; top:10px; --cs:9px; padding:.95em 1.05em .9em; }
`, `<div class="grid"></div>${lgp(9)}
  <p class="h hd" data-dentro><span>Certificado de</span><span class="g">calibração</span></p>
  <a class="cta" data-dentro>Orçamento</a>`);

/* Anúncio responsivo do Google: imagens SEM texto por cima (a regra do Google) + logos */
arte({ nome: 'responsivo-paisagem-1200x628', pasta: 'google-display/responsivo', w: 1200, h: 628, jpg: 88,
  html: `<div class="grid"></div><div class="band" style="--bx:40%;--bw:30%"></div>
  ${cert('--c:17px; left:430px; top:60px;')}
  ${balanca('left:200px; top:250px; width:380px;')}
  ${peso('peso', 'left:860px; top:360px; width:120px;')}` });
arte({ nome: 'responsivo-quadrado-1200x1200', pasta: 'google-display/responsivo', w: 1200, h: 1200, jpg: 88,
  html: `<div class="grid"></div><div class="band" style="--bx:34%;--bw:34%"></div>
  ${cert('--c:26px; left:300px; top:170px;')}
  ${balanca('left:80px; top:600px; width:560px;')}
  ${peso('peso', 'left:830px; top:760px; width:170px;')}` });
arte({ nome: 'logo-quadrado-1200x1200', pasta: 'google-display/responsivo', w: 1200, h: 1200,
  css: `.art{display:grid;place-items:center} .lg__i{width:760px;height:760px}`,
  html: `${simbolo()}` });
arte({ nome: 'logo-paisagem-1200x300', pasta: 'google-display/responsivo', w: 1200, h: 300,
  css: `.art{display:grid;place-items:center} .lg{--lg:110px}`,
  html: `${logo()}` });
