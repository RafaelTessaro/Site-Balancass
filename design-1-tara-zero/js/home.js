/* =====================================================================
   Tara Zero — Home
   Pesagem do hero, morph do display em contadores, vitrine fixada,
   segmentos, prova social, equipe, FAQ e formulário → WhatsApp.
   Conteúdo roda na hora; o movimento entra por TZ.onPre / TZ.onMotion.
   ===================================================================== */
(function () {
  "use strict";

  var TZ = window.TZ, BC = window.BC;
  if (!TZ || !BC) return;
  var $ = TZ.$, $$ = TZ.$$;
  var d = document, html = d.documentElement;
  var E = BC.empresa || {};
  var esc = BC.escape;
  var G = E.google || {};
  var NOTA = parseFloat(String(G.nota || "5,0").replace(",", ".")) || 5;

  /* =========================================================
     1. HERO — simulação de pesagem
     ========================================================= */
  var ITENS = [
    { nome: "QUEIJO PRATO", plu: "014", centavos: 4990, gramas: 1250 },
    { nome: "PICANHA", plu: "102", centavos: 7990, gramas: 865 },
    { nome: "PÃO FRANCÊS", plu: "001", centavos: 1690, gramas: 540 },
    { nome: "TOMATE", plu: "230", centavos: 899, gramas: 2315 },
    { nome: "PRESUNTO", plu: "021", centavos: 3990, gramas: 300 }
  ];
  var itemAtual = 0;

  var segPeso = $('[data-seg="peso"]'), segPreco = $('[data-seg="preco"]'), segTotal = $('[data-seg="total"]');
  var segItem = $('[data-seg="item"]'), segPlu = $('[data-seg="plu"]'), segSr = $("[data-seg-sr]");
  var ticket = $(".ticket"), productImg = $(".hero__product-img");

  function totalCent(it, g) { return Math.round(g * it.centavos / 1000); }
  function brl(c) { return (c / 100).toFixed(2).replace(".", ","); }
  function kg(g) { return (g / 1000).toFixed(3).replace(".", ","); }

  function render(it, g) {
    if (segPeso) segPeso.textContent = (g / 1000).toFixed(3);
    if (segTotal) segTotal.textContent = (totalCent(it, g) / 100).toFixed(2);
  }
  function setItem(it) {
    if (segPreco) segPreco.textContent = (it.centavos / 100).toFixed(2);
    if (segItem) segItem.textContent = it.nome;
    if (segPlu) segPlu.textContent = it.plu;
  }
  function led(name, on) { var l = $('[data-led="' + name + '"]'); if (l) l.classList.toggle("is-on", !!on); }

  /* EAN-13 de peso variável (prefixo 2), desenhado em SVG */
  var EAN_L = ["0001101", "0011001", "0010011", "0111101", "0100011", "0110001", "0101111", "0111011", "0110111", "0001011"];
  var EAN_G = ["0100111", "0110011", "0011011", "0100001", "0011101", "0111001", "0000101", "0010001", "0001001", "0010111"];
  var EAN_P = ["LLLLLL", "LLGLGG", "LLGGLG", "LLGGGL", "LGLLGG", "LGGLLG", "LGGGLL", "LGLGLG", "LGLGGL", "LGGLGL"];
  function ean13(code12) {
    var s = 0;
    for (var i = 0; i < 12; i++) s += (+code12[i]) * (i % 2 ? 3 : 1);
    var code = code12 + ((10 - s % 10) % 10);
    var bits = "101", par = EAN_P[+code[0]];
    for (i = 1; i <= 6; i++) bits += (par[i - 1] === "L" ? EAN_L : EAN_G)[+code[i]];
    bits += "01010";
    for (i = 7; i <= 12; i++) bits += EAN_L[+code[i]].replace(/./g, function (c) { return c === "0" ? "1" : "0"; });
    bits += "101";
    return { code: code, bits: bits };
  }
  function paintTicket(it) {
    if (!ticket) return;
    var c = totalCent(it, it.gramas);
    var e = ean13("2" + ("00000" + it.plu).slice(-5) + ("000000" + c).slice(-6));
    var q = function (k) { return $('[data-tk="' + k + '"]', ticket); };
    q("item").textContent = it.nome;
    q("peso").textContent = kg(it.gramas) + " kg";
    q("preco").textContent = brl(it.centavos);
    q("total").textContent = brl(c);
    q("code").textContent = e.code[0] + " " + e.code.slice(1, 7) + " " + e.code.slice(7);
    var svg = $(".ticket__bars", ticket), out = "", x = 0;
    while (x < e.bits.length) {
      if (e.bits[x] === "1") { var w = 1; while (e.bits[x + w] === "1") w++; out += '<rect x="' + x + '" y="0" width="' + w + '" height="30"/>'; x += w; } else x++;
    }
    svg.innerHTML = '<g fill="#121513">' + out + "</g>";
  }
  function paintSr(it) {
    if (segSr) segSr.textContent = "Simulação de pesagem: " + kg(it.gramas) + " kg de " + it.nome.toLowerCase() + " a R$ " + brl(it.centavos) + " o quilo, total de R$ " + brl(totalCent(it, it.gramas)) + ".";
  }

  var weighTl = null;
  function weigh(it, delay) {
    setItem(it);
    if (!TZ.motion) {
      render(it, it.gramas); led("zero", false); led("est", true); paintTicket(it); paintSr(it);
      return;
    }
    if (weighTl) weighTl.kill();
    var o = { g: 0 };
    weighTl = gsap.timeline({ delay: delay || 0 });
    weighTl
      .add(function () { led("est", false); led("liq", false); led("zero", true); render(it, 0); }, 0)
      .to(ticket, { yPercent: -104, duration: .35, ease: "power2.in" }, 0)
      .to(o, {
        g: it.gramas, duration: 1.7, ease: "elastic.out(1, 0.36)",
        onStart: function () { led("zero", false); },
        onUpdate: function () {
          var p = this.progress();
          var noise = (1 - p) * (1 - p) * it.gramas * .06 * (Math.random() - .5);
          render(it, Math.max(0, Math.round(o.g + noise)));
        },
        onComplete: function () { render(it, it.gramas); led("est", true); paintTicket(it); paintSr(it); }
      }, .45)
      .fromTo(productImg, { y: 0 }, { y: 6, duration: .16, ease: "power2.out", yoyo: true, repeat: 1 }, .45)
      .fromTo(ticket, { yPercent: -104 }, { yPercent: 0, duration: 1.1, ease: "power2.out" }, 2.2);
  }

  var tareBtn = $("[data-tare]");
  if (tareBtn) tareBtn.addEventListener("click", function () {
    if (segSr) segSr.setAttribute("aria-live", "polite");
    itemAtual = (itemAtual + 1) % ITENS.length;
    weigh(ITENS[itemAtual]);
  });
  paintTicket(ITENS[0]);

  // Legenda e alt do produto do hero vindos do catálogo (nome atualizado automaticamente)
  (function () {
    var p = BC.porId("balmak-orion-1-plus");
    var cap = $(".hero__spec"), img = $(".hero__product-img");
    if (!p) return;
    var capSpec = (p.especificacoes || []).filter(function (s) { return /^capacidade/i.test(s[0]); })[0];
    if (cap) cap.innerHTML = "FIG. 01 · <b>" + esc((p.marca + " " + p.nome).toUpperCase()) + "</b><br>" + esc(p.subcategoria.toUpperCase()) + (capSpec ? " · " + esc(TZ.specVal(capSpec[1], capSpec[0]).toUpperCase()) : "");
    if (img) img.alt = "Balança " + p.marca + " " + p.nome + " (" + p.subcategoria.toLowerCase() + ")";
  })();

  /* Flutuação suave do produto: pausa assim que o hero sai de cena */
  var floatTw = null;
  function startFloat() {
    if (floatTw || !TZ.motion) return;
    floatTw = gsap.to(".hero__product-img", { yPercent: -2.2, duration: 3.2, ease: "sine.inOut", yoyo: true, repeat: -1 });
    if (!window.ScrollTrigger) return;
    var st = ScrollTrigger.create({
      trigger: ".hero", start: "top top", end: function () { return "+=" + Math.round(window.innerHeight * .25); },
      onToggle: function (s) { if (s.isActive) floatTw.resume(); else floatTw.pause(); }
    });
    if (!st.isActive) floatTw.pause();
  }

  /* Intro do hero (depois do preloader). A imagem (LCP) nunca fica invisível. */
  function heroIntro() {
    var hero = $(".hero");
    if (!hero) return;
    if (!TZ.motion) { html.classList.remove("is-intro"); weigh(ITENS[0]); return; }
    gsap.set(ticket, { yPercent: -104 });
    render(ITENS[0], 0); led("est", false); led("zero", true);
    if (TZ.late) {
      // o texto já estava na tela (timeout do <head>): só a balança pesa
      html.classList.remove("is-intro");
      weigh(ITENS[0], .2);
      gsap.delayedCall(2, startFloat);
      return;
    }
    var title = $('[data-hero="title"]');
    var tl = gsap.timeline({ defaults: { ease: "expo.out" } });
    var lines = null;
    if (window.SplitText && title) {
      var sp = SplitText.create(title, { type: "lines", mask: "lines", linesClass: "ln" });
      lines = sp.lines;
    }
    html.classList.remove("is-intro");

    if (lines) tl.from(lines, { yPercent: 115, duration: 1.3, stagger: .11 }, .05);
    tl.from('[data-hero="eyebrow"]', { opacity: 0, y: 14, duration: .9 }, 0)
      .from('[data-hero="lead"]', { opacity: 0, y: 24, duration: 1.1 }, .35)
      .from('[data-hero="ctas"] > *', { opacity: 0, y: 22, duration: 1, stagger: .08 }, .45)
      .from('[data-hero="trust"] li', { opacity: 0, y: 16, duration: .9, stagger: .06 }, .6)
      .from(".hero__product-img", { yPercent: 6, scale: .96, duration: 1.6 }, .1)
      .from(".hero__halo", { autoAlpha: 0, scale: .5, duration: 1.6 }, .3)
      .from(".hero__floor", { scaleX: 0, duration: 1.4, ease: "expo.inOut" }, .2)
      .from(".hero__spec", { autoAlpha: 0, x: -12, duration: 1 }, .9)
      .from(".readout", { opacity: 0, y: 40, duration: 1.2 }, .5)
      .from(".hdr .nav li, .hdr .status-pill, .hdr__cta", { opacity: 0, y: -10, duration: .8, stagger: .04 }, .2);
    var lg = TZ.logoIn($(".hdr .logo-anim"), { ring: .8, bq: .7 });
    if (lg) tl.add(lg, .1);
    var eb = $('[data-hero="eyebrow"] span');
    if (eb && window.ScrambleTextPlugin) tl.to(eb, { duration: 1.2, ease: "none", scrambleText: { text: eb.textContent, chars: "01234567890·—", revealDelay: .2, speed: .7 } }, 0);
    weigh(ITENS[0], 1.0);
    tl.add(startFloat, 1.8);
  }
  TZ.whenReady(heroIntro);

  /* =========================================================
     2. Morph do display → contadores (desktop, com movimento)
     ========================================================= */
  function offsetWithin(el, anc) {
    var x = 0, y = 0, n = el;
    while (n && n !== anc) { x += n.offsetLeft; y += n.offsetTop; n = n.offsetParent; }
    return { x: x, y: y };
  }
  function initMorph() {
    if (!TZ.motion || !window.ScrollTrigger || !TZ.mm) return;
    var hero = $(".hero"), wrap = $(".readout-wrap");
    if (!hero || !wrap) return;
    TZ.mm.add("(min-width: 1024px) and (min-height: 640px) and (prefers-reduced-motion: no-preference)", function () {
      html.classList.add("is-pinmorph");
      var A = $$(".cell__a", hero), B = $$(".cell__b", hero);
      var sD = $('[data-stat="desde"]'), sA = $('[data-stat="anos"]'), sN = $('[data-stat="nota"]');
      // Os números entram já corretos: a rolagem anima só a troca das células, nunca os valores.
      var fin = { d: E.desde || 2010, a: E.anosExperiencia || 25, n: NOTA };
      var o = { d: fin.d, a: fin.a, n: fin.n };
      var paint = function () { sD.textContent = Math.round(o.d); sA.textContent = Math.round(o.a); sN.textContent = o.n.toFixed(1); };
      gsap.set(B, { autoAlpha: 0, yPercent: 40 });
      gsap.set(".hero__caption", { autoAlpha: 0, y: 30 });
      paint();
      function delta() {
        var p = offsetWithin(wrap, hero);
        return { x: window.innerWidth / 2 - (p.x + wrap.offsetWidth / 2), y: window.innerHeight * .42 - (p.y + wrap.offsetHeight / 2) };
      }
      var tl = gsap.timeline({
        defaults: { ease: "power2.inOut" },
        scrollTrigger: { trigger: hero, start: "top top", end: "+=115%", pin: true, scrub: .7, anticipatePin: 1, invalidateOnRefresh: true }
      });
      tl.to(".hero__copy", { y: -90, autoAlpha: 0, duration: .32, ease: "power1.in" }, 0)
        .to(".ticket-slot", { y: 60, autoAlpha: 0, duration: .22 }, 0)
        .to(".hero__product", { xPercent: 14, yPercent: -10, scale: 1.18, autoAlpha: 0, duration: .5, ease: "power1.in" }, .04)
        .to(wrap, { x: function () { return delta().x; }, y: function () { return delta().y; }, scale: 1.32, duration: .55 }, .08)
        .to([".readout__foot > .readout__item:not(.readout__item--b)", ".readout__foot .tare"], { autoAlpha: 0, y: -10, duration: .18, ease: "power1.in" }, .3)
        .to(A, { autoAlpha: 0, yPercent: -40, duration: .14, stagger: .03, ease: "power1.in" }, .5)
        .to(B, { autoAlpha: 1, yPercent: 0, duration: .16, stagger: .03, ease: "power2.out" }, .56)
        .fromTo(".readout__item--b", { autoAlpha: 0, y: 10 }, { autoAlpha: 1, y: 0, duration: .16, ease: "power2.out" }, .56)
        // contagem de disparo único (fora do scrub): termina sozinha mesmo se a rolagem parar
        .add(function () {
          if (tl.scrollTrigger && tl.scrollTrigger.direction > 0) {
            gsap.fromTo(o, { d: 1990, a: 0, n: 0 }, { d: fin.d, a: fin.a, n: fin.n, duration: .9, ease: "power2.out", onUpdate: paint, overwrite: true });
          }
        }, .56)
        .add(function () { led("est", true); led("zero", false); }, .6)
        .to(".hero__caption", { autoAlpha: 1, y: 0, duration: .22, ease: "power2.out" }, .72)
        .to({}, { duration: .12 });
      return function () { html.classList.remove("is-pinmorph"); };
    });
  }

  /* =========================================================
     3. Vitrine (destaques) — rail horizontal fixado
     ========================================================= */
  function renderRail() {
    var track = $("[data-rail-track]");
    if (!track) return;
    var list = BC.destaques();
    var html2 = list.map(function (p, i) {
      return TZ.card(p, { base: "produtos.html", fig: "FIG. " + ("0" + (i + 1)).slice(-2) });
    }).join("");
    var n = String(BC.produtos.length);
    var cats = (BC.categorias || []).map(function (c) {
      return "<li><span>" + esc(c.nome) + "</span><b>" + BC.contar({ categoria: c.id }) + "</b></li>";
    }).join("");
    html2 += '<a class="rail-end" href="produtos.html" data-cursor="view">' +
      '<div><span class="rail-end__num"><span class="seg__ghost" aria-hidden="true">' + n.replace(/./g, "8") + '</span><span class="seg__val">' + n + "</span></span>" +
      "<p>equipamentos no catálogo, com busca e filtros por categoria.</p>" +
      (cats ? '<ul class="rail-end__cats" aria-hidden="true">' + cats + "</ul>" : "") + "</div>" +
      '<span class="link-arrow">Ver catálogo completo <svg aria-hidden="true"><use href="#i-arrow-ur"/></svg></span></a>';
    track.innerHTML = html2;
    var tot = $("[data-rail-total]"); if (tot) tot.textContent = ("0" + list.length).slice(-2);
    TZ.tallImgs(track);
  }
  function railProgress(p) {
    var bar = $("[data-rail-bar]"), cur = $("[data-rail-cur]"), n = BC.destaques().length;
    if (bar) bar.style.transform = "scaleX(" + Math.max(.06, p).toFixed(3) + ")";
    if (cur) cur.textContent = ("0" + Math.min(n, Math.max(1, Math.round(p * (n - 1)) + 1))).slice(-2);
  }
  // Sem pin (celular, sem animação): rolagem horizontal nativa com indicador
  function initRailNative() {
    var rail = $("[data-rail]");
    if (!rail) return;
    rail.addEventListener("scroll", function () {
      var max = rail.scrollWidth - rail.clientWidth;
      railProgress(max > 0 ? rail.scrollLeft / max : 0);
    }, { passive: true });
    railProgress(0);
  }
  function initRail() {
    var rail = $("[data-rail]"), track = $("[data-rail-track]"), sec = $("#destaques");
    if (!rail || !track || !TZ.motion || !window.ScrollTrigger || !TZ.mm) return;
    TZ.mm.add("(min-width: 1024px) and (min-height: 640px) and (prefers-reduced-motion: no-preference)", function () {
      html.classList.add("is-railpin");
      var pad = function () { return parseFloat(getComputedStyle(rail).paddingLeft) || 0; };
      var dist = function () { return Math.max(0, track.scrollWidth + pad() * 2 - window.innerWidth); };
      var tw = gsap.to(track, {
        x: function () { return -dist(); }, ease: "none",
        scrollTrigger: {
          trigger: sec, start: "top top", end: function () { return "+=" + dist(); },
          // transform evita o layout shift na entrada/saída do pin; em toque, "fixed" evita trepidação
          pin: true, pinType: ScrollTrigger.isTouch === 1 ? "fixed" : "transform",
          scrub: .8, invalidateOnRefresh: true, anticipatePin: 1,
          onUpdate: function (s) { railProgress(s.progress); }
        }
      });
      // Tab num card fora da tela: rola a página até o ponto do pin em que ele aparece
      function onFocus(e) {
        var c = e.target.closest(".pcard, .rail-end");
        var st = tw.scrollTrigger;
        if (!c || !st) return;
        var first = track.firstElementChild;
        var p = gsap.utils.clamp(0, 1, (c.offsetLeft - (first ? first.offsetLeft : 0)) / Math.max(1, dist()));
        var y = st.start + p * (st.end - st.start);
        if (TZ.lenis) TZ.lenis.scrollTo(y, { immediate: true, force: true }); else window.scrollTo(0, y);
        tw.scrollTrigger.update();
      }
      track.addEventListener("focusin", onFocus);
      // cards entram com leve paralaxe de profundidade (só opacidade: continuam focáveis)
      $$(".pcard, .rail-end", track).forEach(function (c, i) {
        gsap.from(c, { y: 60, opacity: 0, duration: 1, ease: "power3.out", delay: Math.min(i, 3) * .08, scrollTrigger: { trigger: sec, start: "top 70%", once: true } });
      });
      return function () { track.removeEventListener("focusin", onFocus); html.classList.remove("is-railpin"); tw.kill(); gsap.set(track, { clearProps: "x" }); };
    });
  }

  /* =========================================================
     4. Segmentos
     ========================================================= */
  var SEGMENTOS = [
    { id: "padaria", nome: "Padaria", quem: "uma padaria", titulo: "Padaria e confeitaria", texto: "Pão francês, frios e doces pesados e etiquetados no balcão. No caixa, é só passar o código de barras da etiqueta — com NFC-e.",
      kit: [["toledo-prix-4-uno", "Balança com etiqueta para o balcão"], ["toledo-uni-350-ga", "Fatiador para frios"], ["elgin-el4200", "Leitor da etiqueta no caixa"], ["elgin-i9", "Impressora de cupom"], ["sys", "Frente de caixa com NFC-e"]] },
    { id: "acougue", nome: "Açougue", quem: "um açougue", titulo: "Açougue e casa de carnes", texto: "Etiqueta com peso, preço e código de barras: a carne sai do balcão e passa no caixa sem erro de digitação.",
      kit: [["toledo-prix-4-due", "Balança com etiqueta para alto volume"], ["balmak-orion-1-plus", "Opção Balmak com etiqueta"], ["tanca-tl-900", "Leitor fixo para o checkout"], ["sys", "Estoque e vendas integrados à balança"]] },
    { id: "hortifruti", nome: "Hortifrúti", quem: "um hortifrúti", titulo: "Hortifrúti e sacolão", texto: "Pesagem direto no caixa, integrada ao sistema: o cliente passa e o preço já sai certo.",
      kit: [["toledo-8217", "Balança embutida no checkout"], ["toledo-prix-3-fit", "Balança computadora para as bancas"], ["centrium-mini-pc", "Computador compacto para o caixa"], ["sys", "PDV integrado à balança"]] },
    { id: "mercado", nome: "Mercado", quem: "um mercado", titulo: "Mercado e supermercado", texto: "Checkout completo, etiquetas na seção de frios e terminal para o cliente conferir o preço.",
      kit: [["balmak-bck30", "Balança de checkout"], ["toledo-prix-5-plus", "Etiquetas na seção de frios"], ["gertec-tc504", "Terminal de consulta de preço"], ["gaveta-menno", "Gaveta de dinheiro"], ["sys", "BC System com NFC-e"]] },
    { id: "restaurante", nome: "Restaurante", quem: "um restaurante ou lanchonete", titulo: "Restaurante e lanchonete", texto: "Comida por quilo pesada na hora e um caixa ágil para o horário de pico.",
      kit: [["toledo-9094-plus", "Balança pesadora para o self-service"], ["gertec-tec-44", "Teclado programável para o caixa"], ["elgin-i9", "Impressora de cupom"], ["sys", "Frente de caixa com NFC-e"]] },
    { id: "petshop", nome: "Pet shop", quem: "um pet shop", titulo: "Pet shop", texto: "Ração a granel pesada com precisão e estoque sempre sob controle.",
      kit: [["toledo-prix-3-fit", "Balança para ração a granel"], ["tanca-tl-120", "Leitor de código de barras"], ["tanca-tp-650", "Impressora de cupom"], ["sys", "Controle de estoque e vendas"]] },
    { id: "varejo", nome: "Varejo", quem: "uma loja de varejo", titulo: "Loja de roupas e varejo", texto: "Venda no carnê, controle de peças consignadas e etiquetas de preço — tudo no mesmo sistema.",
      kit: [["elgin-l42-pro", "Impressora de etiquetas de preço"], ["tanca-tl-220", "Leitor de código de barras"], ["gaveta-bematech", "Gaveta de dinheiro"], ["sys", "Carnê e consignação no BC System"]] },
    { id: "farmacia", nome: "Farmácia", quem: "uma farmácia ou clínica", titulo: "Farmácias e clínicas", texto: "Balança para pesar clientes e pacientes com conforto, e um caixa simples de operar.",
      kit: [["toledo-hospitalar", "Balança médico-hospitalar"], ["balmak-bk200f", "Balança de 200 kg para clínicas"], ["elgin-el4200", "Leitor de código de barras"], ["sys", "PDV com NFC-e"]] },
    { id: "industria", nome: "Indústria", quem: "uma indústria ou depósito", titulo: "Indústria e depósitos", texto: "Pesagem de volumes, conferência de cargas e etiquetas de identificação que aguentam o tranco.",
      kit: [["toledo-2098", "Balança de bancada inox para conferência"], ["balmak-w300", "Balança de plataforma de 300 kg"], ["zebra-zt230", "Impressora de etiquetas industrial"], ["nobreak-nhs", "Nobreak para o sistema não parar"]] }
  ];

  // Miniaturas leves (160×120) dos kits; a foto grande fica de reserva
  function kitImg(p) {
    var big = BC.imgProduto(p, false);
    return '<img src="' + esc(big) + '" srcset="img/kit/' + esc(p.id) + '.webp 160w" sizes="76px" alt="" width="76" height="57" loading="lazy" decoding="async" data-kit-img>';
  }

  var panels = null;
  // Altura fixa = maior painel: trocar de aba não muda o layout (nem exige ScrollTrigger.refresh)
  function fixPanelsH() {
    if (!panels) return false;
    var before = panels.style.minHeight;
    panels.style.minHeight = "";
    var max = 0;
    $$(".segpanel", panels).forEach(function (pn) {
      var was = pn.hidden; pn.hidden = false;
      max = Math.max(max, pn.offsetHeight);
      pn.hidden = was;
    });
    panels.style.minHeight = max ? max + "px" : "";
    return panels.style.minHeight !== before;
  }

  function renderSegments() {
    var tabs = $("[data-segtabs]");
    panels = $("[data-segpanels]");
    if (!tabs || !panels) return;
    var tHtml = "", pHtml = "";
    SEGMENTOS.forEach(function (s, i) {
      var n = ("0" + (i + 1)).slice(-2);
      tHtml += '<button class="segtab" type="button" role="tab" id="tab-' + s.id + '" aria-controls="seg-' + s.id + '" aria-selected="' + (i === 0) + '" tabindex="' + (i === 0 ? 0 : -1) + '"><small aria-hidden="true">' + n + "</small>" + esc(s.nome) + "</button>";
      var nomesKit = [];
      var items = s.kit.map(function (k, j) {
        var idx = ("0" + (j + 1)).slice(-2);
        if (k[0] === "sys") {
          nomesKit.push("BC System");
          return '<li><a href="#sistema"><span class="kit__i" aria-hidden="true">' + idx + '</span><span class="stage kit__thumb is-sys"><svg aria-hidden="true"><use href="#i-monitor"/></svg></span><span class="kit__txt"><b>BC System</b><span>' + esc(k[1]) + '</span></span><span class="kit__go"><svg aria-hidden="true"><use href="#i-arrow-ur"/></svg></span></a></li>';
        }
        var p = BC.porId(k[0]);
        if (!p) return "";
        nomesKit.push(p.marca + " " + p.nome);
        return '<li><a href="produtos.html#p=' + encodeURIComponent(p.id) + '"><span class="kit__i" aria-hidden="true">' + idx + '</span><span class="stage kit__thumb">' + kitImg(p) + '</span><span class="kit__txt"><b>' + esc(p.marca + " " + p.nome) + "</b><span>" + esc(k[1]) + '</span></span><span class="kit__go"><svg aria-hidden="true"><use href="#i-arrow-ur"/></svg></span></a></li>';
      }).join("");
      var msg = "Olá! Vim pelo site da Balanças.com. Tenho " + s.quem + " e quero um orçamento do kit sugerido: " + nomesKit.join(", ") + ".";
      pHtml += '<div class="segpanel" role="tabpanel" id="seg-' + s.id + '" aria-labelledby="tab-' + s.id + '"' + (i === 0 ? "" : " hidden") + ' tabindex="0">' +
        '<div class="segpanel__intro"><span class="segpanel__num" aria-hidden="true"><span class="seg__ghost">88</span><span class="seg__val">' + n + '</span></span><h3 class="h3">' + esc(s.titulo) + "</h3><p>" + esc(s.texto) + '</p><p class="kit-note">' + s.kit.length + ' itens no kit sugerido</p><a class="btn btn--primary" href="' + esc(BC.whatsLink(msg)) + '" target="_blank" rel="noopener"><svg aria-hidden="true"><use href="#i-wa"/></svg> Pedir este kit</a></div>' +
        '<ul class="kit">' + items + "</ul></div>";
    });
    tabs.innerHTML = tHtml;
    panels.innerHTML = pHtml;
    // miniatura ausente? volta para a foto grande
    $$("[data-kit-img]", panels).forEach(function (img) {
      img.addEventListener("error", function () { if (img.getAttribute("srcset")) img.removeAttribute("srcset"); }, { once: true });
    });

    var btns = $$(".segtab", tabs);
    function select(i, focus) {
      var h0 = panels.offsetHeight;
      btns.forEach(function (b, j) {
        var on = i === j;
        b.setAttribute("aria-selected", on);
        b.tabIndex = on ? 0 : -1;
        var pn = d.getElementById(b.getAttribute("aria-controls"));
        if (pn) pn.hidden = !on;
      });
      if (focus) btns[i].focus();
      var panel = d.getElementById(btns[i].getAttribute("aria-controls"));
      if (TZ.motion && panel) {
        gsap.fromTo($$(".segpanel__intro > *", panel), { opacity: 0, y: 18 }, { opacity: 1, y: 0, duration: .7, ease: "expo.out", stagger: .05 });
        gsap.fromTo($$(".kit li", panel), { opacity: 0, x: 24 }, { opacity: 1, x: 0, duration: .7, ease: "expo.out", stagger: .06, delay: .05 });
      }
      // só recalcula os gatilhos se a altura realmente mudou (raro, com min-height fixo)
      if (window.ScrollTrigger && TZ.hasG && panels.offsetHeight !== h0) ScrollTrigger.refresh();
      // mantém o chip ativo visível no mobile
      var b = btns[i];
      if (tabs.scrollWidth > tabs.clientWidth) tabs.scrollTo({ left: b.offsetLeft - 20, behavior: TZ.motion ? "smooth" : "auto" });
    }
    btns.forEach(function (b, i) {
      b.addEventListener("click", function () { select(i); });
      b.addEventListener("keydown", function (ev) {
        var k = ev.key, n = btns.length, j = null;
        if (k === "ArrowRight") j = (i + 1) % n;
        else if (k === "ArrowLeft") j = (i - 1 + n) % n;
        else if (k === "Home") j = 0;
        else if (k === "End") j = n - 1;
        if (j !== null) { ev.preventDefault(); select(j, true); }
      });
    });

    fixPanelsH();
    var rt = null;
    window.addEventListener("resize", function () {
      clearTimeout(rt);
      rt = setTimeout(function () { if (fixPanelsH() && window.ScrollTrigger && TZ.hasG) ScrollTrigger.refresh(); }, 160);
    });
    if (d.fonts && d.fonts.ready) d.fonts.ready.then(function () { if (fixPanelsH() && window.ScrollTrigger && TZ.hasG) ScrollTrigger.refresh(); });
  }

  /* =========================================================
     5. Clientes, depoimentos e equipe (dados da empresa)
        O HTML já traz uma cópia estática (sem JS); aqui ela é
        refeita a partir de empresa.js.
     ========================================================= */
  function initials(nome) {
    var p = String(nome).trim().split(/\s+/);
    return (p[0].charAt(0) + (p.length > 1 ? p[p.length - 1].charAt(0) : "")).toUpperCase();
  }

  function renderProof() {
    var cl = $("[data-clients]");
    if (cl && E.clientes) {
      cl.innerHTML = E.clientes.map(function (c) {
        return '<li class="client"><img src="' + esc(BC.img("clientes/" + c.logo)) + '" alt="' + esc(c.nome) + '" width="240" height="120" loading="lazy" decoding="async"></li>';
      }).join("");
    }
    var qg = $("[data-quotes]");
    if (qg && E.depoimentos) {
      qg.innerHTML = E.depoimentos.map(function (q) {
        return '<figure class="card card--spot quote" data-spot><svg class="quote__mark" aria-hidden="true"><use href="#i-quote"/></svg>' +
          "<blockquote><p>" + esc(q.texto) + "</p></blockquote>" +
          '<figcaption><span class="mono-avatar" aria-hidden="true">' + esc(initials(q.nome)) + '</span><span class="quote__who"><b>' + esc(q.nome) + "</b><span>" + esc(q.empresa) + "</span></span></figcaption></figure>";
      }).join("");
    }
    var tm = $("[data-team]");
    if (tm && E.equipe) {
      tm.innerHTML = E.equipe.map(function (m, i) {
        var rot = (i * 67) % 360;
        return '<article class="card card--spot member' + (i === 0 ? " member--lead" : "") + '" data-spot>' +
          '<div class="member__ring" aria-hidden="true"><svg viewBox="0 0 80 80"><circle cx="40" cy="40" r="38" fill="none" stroke="rgba(238,242,239,.14)"/>' +
          '<circle cx="40" cy="40" r="38" fill="none" stroke="#1FE07A" stroke-width="2" stroke-linecap="round" stroke-dasharray="' + (40 + i * 14) + ' 240" transform="rotate(' + rot + ' 40 40)"/></svg>' +
          "<span>" + esc(initials(m.nome)) + "</span></div>" +
          "<h3>" + esc(m.nome) + '</h3><p class="member__role">' + esc(m.cargo) + "</p><p>" + esc(m.texto) + "</p></article>";
      }).join("");
    }
    // Linha do tempo do Sobre: quantidades vindas de empresa.js
    $$("[data-count-of]").forEach(function (el) {
      var v = E[el.getAttribute("data-count-of")];
      if (v && v.length) el.textContent = v.length;
    });
    // Nota do Google vinda de empresa.js (painel de números e placar)
    var sn = $("[data-stat-nota]");
    if (sn) { sn.setAttribute("data-count", NOTA.toFixed(1)); sn.textContent = NOTA.toFixed(1); }
    var gn = $(".gscore__num");
    if (gn && G.nota) gn.setAttribute("aria-label", "Nota " + G.nota + " de 5");
  }

  /* =========================================================
     7. Formulário → WhatsApp
     ========================================================= */
  function initForm() {
    var f = $("[data-wa-form]");
    if (!f) return;
    var err = $("#f-nome-err");
    function ok() { f.nome.removeAttribute("aria-invalid"); if (err) err.hidden = true; }
    f.nome.addEventListener("input", function () { if (f.nome.value.trim()) ok(); });
    f.addEventListener("submit", function (ev) {
      ev.preventDefault();
      var nome = f.nome.value.trim(), assunto = f.assunto.value, msg = f.mensagem.value.trim();
      if (!nome) {
        f.nome.setAttribute("aria-invalid", "true");
        if (err) err.hidden = false;
        f.nome.focus();
        return;
      }
      ok();
      var texto = "Olá! Meu nome é " + nome + ".\n*Assunto:* " + assunto + (msg ? "\n\n" + msg : "") + "\n\n(Mensagem enviada pelo site)";
      window.open(BC.whatsLink(texto), "_blank", "noopener");
    });
  }

  /* =========================================================
     8. Extras de movimento: desenho técnico
     ========================================================= */
  function initDecor() {
    if (!TZ.motion || !window.ScrollTrigger) return;
    var bp = $(".blueprint");
    if (bp) {
      var paths = $$(".draw", bp);
      paths.forEach(function (p) { p.style.strokeDasharray = "1 1"; p.style.strokeDashoffset = "1"; });
      gsap.to(paths, {
        strokeDashoffset: 0, duration: 1.4, ease: "power2.inOut", stagger: .04,
        scrollTrigger: { trigger: bp, start: "top 82%", once: true },
        onComplete: function () { paths.forEach(function (p) { p.style.strokeDasharray = ""; p.style.strokeDashoffset = ""; }); }
      });
      gsap.from($$(".bp-dot, text", bp), { autoAlpha: 0, duration: .6, stagger: .05, scrollTrigger: { trigger: bp, start: "top 75%", once: true }, delay: .8 });
    }
  }

  /* =========================================================
     9. Menu: destaca a seção visível
     ========================================================= */
  function initSpy() {
    if (!window.ScrollTrigger) return;
    var links = $$(".nav a");
    var map = [["#topo", ".hero"], ["#servicos", "#servicos"], ["#sistema", "#sistema"], ["#sobre", "#sobre"], ["#contato", "#contato"]];
    var cur = "#topo";
    function set(h) { cur = h; links.forEach(function (a) { a.classList.toggle("is-active", a.getAttribute("href") === h); }); }
    map.forEach(function (m) {
      var el = $(m[1]); if (!el) return;
      ScrollTrigger.create({
        trigger: el, start: "top 45%", end: "bottom 45%",
        onToggle: function (st) { if (st.isActive) set(m[0]); else if (cur === m[0]) set(null); }
      });
    });
  }

  /* ---------------- Boot: conteúdo (na hora) ---------------- */
  renderRail();
  initRailNative();
  renderSegments();
  TZ.marquee();
  renderProof();
  TZ.fill(d);
  TZ.faq();
  initForm();
  TZ.boot();

  /* ---------------- Boot: movimento (quando o GSAP chegar) ---------------- */
  // classes de layout dos pins antes do SplitText medir as linhas
  TZ.onPre(function () {
    if (TZ.motion && window.ScrollTrigger && window.matchMedia("(min-width: 1024px) and (min-height: 640px)").matches) html.classList.add("is-pinmorph", "is-railpin");
  });
  TZ.onMotion(function () {
    TZ.bindTilt($("[data-rail-track]"));
    initDecor();
    initMorph();
    initRail();
    initSpy();
  });
})();
