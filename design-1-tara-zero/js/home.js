/* =====================================================================
   Tara Zero — Home
   Pesagem do hero, morph do display em contadores, vitrine fixada,
   segmentos, prova social, equipe, FAQ e formulário → WhatsApp.
   ===================================================================== */
(function () {
  "use strict";

  var TZ = window.TZ, BC = window.BC;
  if (!TZ || !BC) return;
  var $ = TZ.$, $$ = TZ.$$;
  var d = document, html = d.documentElement;
  var E = BC.empresa || {};
  var hasG = TZ.hasG;
  var motion = hasG && !TZ.reduce;
  var esc = BC.escape;

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
    if (!motion) {
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
    itemAtual = (itemAtual + 1) % ITENS.length;
    weigh(ITENS[itemAtual]);
  });
  paintTicket(ITENS[0]);

  /* Intro do hero (depois do preloader) */
  function heroIntro() {
    var hero = $(".hero");
    if (!hero) return;
    if (!motion) { html.classList.remove("is-intro"); weigh(ITENS[0]); return; }
    var title = $('[data-hero="title"]');
    var tl = gsap.timeline({ defaults: { ease: "expo.out" } });
    var lines = null;
    if (window.SplitText && title) {
      var sp = SplitText.create(title, { type: "lines", mask: "lines", linesClass: "ln" });
      lines = sp.lines;
    }
    gsap.set(ticket, { yPercent: -104 });
    render(ITENS[0], 0); led("est", false); led("zero", true);
    html.classList.remove("is-intro");

    if (lines) tl.from(lines, { yPercent: 115, duration: 1.3, stagger: .11 }, .05);
    tl.from('[data-hero="eyebrow"]', { autoAlpha: 0, y: 14, duration: .9 }, 0)
      .from('[data-hero="lead"]', { autoAlpha: 0, y: 24, duration: 1.1 }, .35)
      .from('[data-hero="ctas"] > *', { autoAlpha: 0, y: 22, duration: 1, stagger: .08 }, .45)
      .from('[data-hero="trust"] li', { autoAlpha: 0, y: 16, duration: .9, stagger: .06 }, .6)
      .from(".hero__product-img", { autoAlpha: 0, yPercent: 10, scale: .94, duration: 1.6 }, .1)
      .from(".hero__halo", { autoAlpha: 0, scale: .5, duration: 1.6 }, .3)
      .from(".hero__floor", { scaleX: 0, duration: 1.4, ease: "expo.inOut" }, .2)
      .from(".hero__spec", { autoAlpha: 0, x: -12, duration: 1 }, .9)
      .from(".readout", { autoAlpha: 0, y: 40, duration: 1.2 }, .5)
      .from(".hdr .nav li, .hdr .status-pill, .hdr__cta", { autoAlpha: 0, y: -10, duration: .8, stagger: .04 }, .2);
    var lg = TZ.logoIn($(".hdr .logo-anim"), { ring: .8, bq: .7 });
    if (lg) tl.add(lg, .1);
    var eb = $('[data-hero="eyebrow"] span');
    if (eb && window.ScrambleTextPlugin) tl.to(eb, { duration: 1.2, ease: "none", scrambleText: { text: eb.textContent, chars: "01234567890·—", revealDelay: .2, speed: .7 } }, 0);
    weigh(ITENS[0], 1.0);
    // flutuação suave do produto
    tl.add(function () {
      gsap.to(".hero__product-img", { yPercent: -2.2, duration: 3.2, ease: "sine.inOut", yoyo: true, repeat: -1 });
    }, 1.8);
  }
  if (!motion) html.classList.remove("is-intro");
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
    if (!motion || !window.ScrollTrigger || !TZ.mm) return;
    var hero = $(".hero"), wrap = $(".readout-wrap");
    if (!hero || !wrap) return;
    TZ.mm.add("(min-width: 1024px) and (min-height: 640px) and (prefers-reduced-motion: no-preference)", function () {
      html.classList.add("is-pinmorph");
      var A = $$(".cell__a", hero), B = $$(".cell__b", hero);
      var sD = $('[data-stat="desde"]'), sA = $('[data-stat="anos"]'), sN = $('[data-stat="nota"]');
      var o = { d: 1990, a: 0, n: 0 };
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
        .to(".hero__trust", { y: 40, autoAlpha: 0, duration: .25, ease: "power1.in" }, 0)
        .to(".ticket-slot", { y: 60, autoAlpha: 0, duration: .22 }, 0)
        .to(".hero__product", { xPercent: 14, yPercent: -10, scale: 1.18, autoAlpha: 0, duration: .5, ease: "power1.in" }, .04)
        .to(wrap, { x: function () { return delta().x; }, y: function () { return delta().y; }, scale: 1.32, duration: .55 }, .08)
        .to(".readout__foot", { autoAlpha: 0, y: -10, duration: .18, ease: "power1.in" }, .3)
        .to(A, { autoAlpha: 0, yPercent: -40, duration: .14, stagger: .03, ease: "power1.in" }, .5)
        .to(B, { autoAlpha: 1, yPercent: 0, duration: .16, stagger: .03, ease: "power2.out" }, .56)
        .to(o, { d: E.desde || 2010, a: E.anosExperiencia || 25, n: parseFloat(String((E.google || {}).nota || "5,0").replace(",", ".")), duration: .28, ease: "power1.out", onUpdate: paint }, .58)
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
    html2 += '<a class="rail-end" href="produtos.html" data-cursor="view">' +
      '<div><span class="rail-end__num">' + BC.produtos.length + '</span><p>equipamentos no catálogo, com busca e filtros por categoria.</p></div>' +
      '<span class="link-arrow">Ver catálogo completo <svg aria-hidden="true"><use href="#i-arrow-ur"/></svg></span></a>';
    track.innerHTML = html2;
    var tot = $("[data-rail-total]"); if (tot) tot.textContent = ("0" + list.length).slice(-2);
  }
  function railProgress(p) {
    var bar = $("[data-rail-bar]"), cur = $("[data-rail-cur]"), n = BC.destaques().length;
    if (bar) bar.style.transform = "scaleX(" + Math.max(.06, p).toFixed(3) + ")";
    if (cur) cur.textContent = ("0" + Math.min(n, Math.max(1, Math.round(p * (n - 1)) + 1))).slice(-2);
  }
  function initRail() {
    var rail = $("[data-rail]"), track = $("[data-rail-track]"), sec = $("#destaques");
    if (!rail || !track) return;
    rail.addEventListener("scroll", function () {
      var max = rail.scrollWidth - rail.clientWidth;
      railProgress(max > 0 ? rail.scrollLeft / max : 0);
    }, { passive: true });
    railProgress(0);
    if (!motion || !window.ScrollTrigger || !TZ.mm) return;
    TZ.mm.add("(min-width: 1024px) and (min-height: 640px) and (prefers-reduced-motion: no-preference)", function () {
      html.classList.add("is-railpin");
      var pad = function () { return parseFloat(getComputedStyle(rail).paddingLeft) || 0; };
      var dist = function () { return Math.max(0, track.scrollWidth + pad() * 2 - window.innerWidth); };
      var tw = gsap.to(track, {
        x: function () { return -dist(); }, ease: "none",
        scrollTrigger: {
          trigger: sec, start: "top top", end: function () { return "+=" + dist(); },
          pin: true, scrub: .8, invalidateOnRefresh: true, anticipatePin: 1,
          onUpdate: function (s) { railProgress(s.progress); }
        }
      });
      // cards entram com leve paralaxe de profundidade
      $$(".pcard, .rail-end", track).forEach(function (c, i) {
        gsap.from(c, { y: 60, autoAlpha: 0, duration: 1, ease: "power3.out", delay: Math.min(i, 3) * .08, scrollTrigger: { trigger: sec, start: "top 70%", once: true } });
      });
      return function () { html.classList.remove("is-railpin"); tw.kill(); gsap.set(track, { clearProps: "x" }); };
    });
  }

  /* =========================================================
     4. Segmentos
     ========================================================= */
  var SEGMENTOS = [
    { id: "padaria", nome: "Padaria", titulo: "Padaria e confeitaria", texto: "Pão francês, frios e doces pesados e etiquetados no balcão. No caixa, é só passar o código de barras da etiqueta — com NFC-e.",
      kit: [["toledo-prix-4-uno", "Balança com etiqueta para o balcão"], ["toledo-uni-350-ga", "Fatiador para frios"], ["elgin-el4200", "Leitor que lê a etiqueta no caixa"], ["elgin-i9", "Impressora de cupom"], ["sys", "Frente de caixa com NFC-e"]] },
    { id: "acougue", nome: "Açougue", titulo: "Açougue e casa de carnes", texto: "Etiqueta com peso, preço e código de barras: a carne sai do balcão e passa no caixa sem erro de digitação.",
      kit: [["toledo-prix-4-due", "Balança com etiqueta, 30 kg"], ["balmak-orion-1-plus", "Alternativa com ótimo custo-benefício"], ["tanca-tl-900", "Leitor fixo para o checkout"], ["sys", "Estoque e vendas integrados à balança"]] },
    { id: "hortifruti", nome: "Hortifrúti", titulo: "Hortifrúti e sacolão", texto: "Pesagem direto no caixa, integrada ao sistema: o cliente passa e o preço já sai certo.",
      kit: [["toledo-8217", "Balança embutida no checkout"], ["toledo-prix-3-fit", "Balança computadora para as bancas"], ["centrium-mini-pc", "Computador compacto para o caixa"], ["sys", "PDV integrado à balança"]] },
    { id: "mercado", nome: "Mercado", titulo: "Mercado e supermercado", texto: "Checkout completo, etiquetas na seção de frios e terminal para o cliente conferir o preço.",
      kit: [["balmak-bck30", "Balança de checkout"], ["toledo-prix-5-plus", "Etiquetas na seção de frios"], ["gertec-tc504", "Terminal de consulta de preço"], ["gaveta-menno", "Gaveta de dinheiro"], ["sys", "BC System com NFC-e"]] },
    { id: "restaurante", nome: "Restaurante", titulo: "Restaurante e lanchonete", texto: "Comida por quilo pesada na hora e um caixa ágil para o horário de pico.",
      kit: [["toledo-9094-plus", "Balança pesadora para o self-service"], ["gertec-tec-44", "Teclado programável para o caixa"], ["elgin-i9", "Impressora de cupom"], ["sys", "Frente de caixa com NFC-e"]] },
    { id: "petshop", nome: "Pet shop", titulo: "Pet shop", texto: "Ração a granel pesada com precisão e estoque sempre sob controle.",
      kit: [["toledo-prix-3-fit", "Balança para ração a granel"], ["tanca-tl-120", "Leitor de código de barras"], ["tanca-tp-650", "Impressora de cupom"], ["sys", "Controle de estoque e vendas"]] },
    { id: "varejo", nome: "Varejo", titulo: "Loja de roupas e varejo", texto: "Venda no carnê, controle de peças consignadas e etiquetas de preço — tudo no mesmo sistema.",
      kit: [["elgin-l42-pro", "Impressora de etiquetas de preço"], ["tanca-tl-220", "Leitor de código de barras"], ["gaveta-bematech", "Gaveta de dinheiro"], ["sys", "Carnê e consignação no BC System"]] },
    { id: "farmacia", nome: "Farmácia", titulo: "Farmácias e clínicas", texto: "Balança para pesar clientes e pacientes com conforto, e um caixa simples de operar.",
      kit: [["toledo-hospitalar", "Balança médico-hospitalar"], ["balmak-bk200f", "Balança de 200 kg para clínicas"], ["elgin-el4200", "Leitor de código de barras"], ["sys", "PDV com NFC-e"]] },
    { id: "industria", nome: "Indústria", titulo: "Indústria e depósitos", texto: "Pesagem de volumes, conferência de cargas e etiquetas de identificação que aguentam o tranco.",
      kit: [["toledo-2098", "Balança industrial de plataforma"], ["balmak-w300", "Balança de 300 kg para conferência"], ["zebra-zt230", "Impressora de etiquetas industrial"], ["nobreak-nhs", "Nobreak para o sistema não parar"]] }
  ];

  function renderSegments() {
    var tabs = $("[data-segtabs]"), panels = $("[data-segpanels]");
    if (!tabs || !panels) return;
    var tHtml = "", pHtml = "";
    SEGMENTOS.forEach(function (s, i) {
      var n = ("0" + (i + 1)).slice(-2);
      tHtml += '<button class="segtab" type="button" role="tab" id="tab-' + s.id + '" aria-controls="seg-' + s.id + '" aria-selected="' + (i === 0) + '" tabindex="' + (i === 0 ? 0 : -1) + '"><small>' + n + "</small>" + esc(s.nome) + "</button>";
      var nomesKit = [];
      var items = s.kit.map(function (k, j) {
        var idx = ("0" + (j + 1)).slice(-2);
        if (k[0] === "sys") {
          nomesKit.push("BC System");
          return '<li><a href="#sistema"><span class="kit__i">' + idx + '</span><span class="stage kit__thumb is-sys"><svg aria-hidden="true"><use href="#i-monitor"/></svg></span><span class="kit__txt"><b>BC System</b><span>' + esc(k[1]) + '</span></span><span class="kit__go"><svg aria-hidden="true"><use href="#i-arrow-ur"/></svg></span></a></li>';
        }
        var p = BC.porId(k[0]);
        if (!p) return "";
        nomesKit.push(p.marca + " " + p.nome);
        return '<li><a href="produtos.html#p=' + encodeURIComponent(p.id) + '"><span class="kit__i">' + idx + '</span><span class="stage kit__thumb"><img src="' + esc(BC.imgProduto(p, false)) + '" alt="" width="76" height="57" loading="lazy" decoding="async"></span><span class="kit__txt"><b>' + esc(p.marca + " " + p.nome) + "</b><span>" + esc(k[1]) + '</span></span><span class="kit__go"><svg aria-hidden="true"><use href="#i-arrow-ur"/></svg></span></a></li>';
      }).join("");
      var msg = "Olá! Vim pelo site da Balanças.com. Tenho um(a) " + s.titulo.toLowerCase() + " e quero um orçamento do kit: " + nomesKit.join(", ") + ".";
      pHtml += '<div class="segpanel" role="tabpanel" id="seg-' + s.id + '" aria-labelledby="tab-' + s.id + '"' + (i === 0 ? "" : " hidden") + ' tabindex="0">' +
        '<div class="segpanel__intro"><span class="segpanel__num" aria-hidden="true"><span class="seg__ghost">88</span><span class="seg__val">' + n + '</span></span><h3 class="h3">' + esc(s.titulo) + "</h3><p>" + esc(s.texto) + '</p><p class="kit-note">' + s.kit.length + ' itens no kit sugerido</p><a class="btn btn--primary" href="' + esc(BC.whatsLink(msg)) + '" target="_blank" rel="noopener"><svg aria-hidden="true"><use href="#i-wa"/></svg> Pedir este kit</a></div>' +
        '<ul class="kit">' + items + "</ul></div>";
    });
    tabs.innerHTML = tHtml;
    panels.innerHTML = pHtml;

    var btns = $$(".segtab", tabs);
    function select(i, focus) {
      btns.forEach(function (b, j) {
        var on = i === j;
        b.setAttribute("aria-selected", on);
        b.tabIndex = on ? 0 : -1;
        var pn = d.getElementById(b.getAttribute("aria-controls"));
        if (pn) pn.hidden = !on;
      });
      if (focus) btns[i].focus();
      var panel = d.getElementById(btns[i].getAttribute("aria-controls"));
      if (motion && panel) {
        gsap.fromTo($$(".segpanel__intro > *", panel), { autoAlpha: 0, y: 18 }, { autoAlpha: 1, y: 0, duration: .7, ease: "expo.out", stagger: .05 });
        gsap.fromTo($$(".kit li", panel), { autoAlpha: 0, x: 24 }, { autoAlpha: 1, x: 0, duration: .7, ease: "expo.out", stagger: .06, delay: .05 });
      }
      if (window.ScrollTrigger) ScrollTrigger.refresh();
      // mantém o chip ativo visível no mobile
      var b = btns[i];
      if (tabs.scrollWidth > tabs.clientWidth) tabs.scrollTo({ left: b.offsetLeft - 20, behavior: motion ? "smooth" : "auto" });
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
  }

  /* =========================================================
     5. Marcas, clientes, depoimentos e equipe (dados da empresa)
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
          '<div class="member__ring"><svg viewBox="0 0 80 80" aria-hidden="true"><circle cx="40" cy="40" r="38" fill="none" stroke="rgba(238,242,239,.14)"/>' +
          '<circle cx="40" cy="40" r="38" fill="none" stroke="#1FE07A" stroke-width="2" stroke-linecap="round" stroke-dasharray="' + (40 + i * 14) + ' 240" transform="rotate(' + rot + ' 40 40)"/></svg>' +
          "<span>" + esc(initials(m.nome)) + "</span></div>" +
          "<h3>" + esc(m.nome) + '</h3><p class="member__role">' + esc(m.cargo) + "</p><p>" + esc(m.texto) + "</p></article>";
      }).join("");
    }
  }

  /* =========================================================
     7. Formulário → WhatsApp
     ========================================================= */
  function initForm() {
    var f = $("[data-wa-form]");
    if (!f) return;
    f.addEventListener("submit", function (ev) {
      ev.preventDefault();
      var nome = f.nome.value.trim(), assunto = f.assunto.value, msg = f.mensagem.value.trim();
      if (!nome) {
        f.nome.setAttribute("aria-invalid", "true");
        f.nome.focus();
        f.nome.placeholder = "Por favor, informe seu nome";
        return;
      }
      f.nome.removeAttribute("aria-invalid");
      var texto = "Olá! Meu nome é " + nome + ".\n*Assunto:* " + assunto + (msg ? "\n\n" + msg : "") + "\n\n(Mensagem enviada pelo site)";
      window.open(BC.whatsLink(texto), "_blank", "noopener");
    });
  }

  /* =========================================================
     8. Extras de movimento: desenho técnico e multa
     ========================================================= */
  function initDecor() {
    if (!motion || !window.ScrollTrigger) return;
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

  /* ---------------- Boot ---------------- */
  renderRail();
  renderSegments();
  TZ.marquee();
  renderProof();
  TZ.fill(d);
  TZ.faq();
  initForm();
  TZ.boot();
  initDecor();
  initMorph();
  initRail();
})();
