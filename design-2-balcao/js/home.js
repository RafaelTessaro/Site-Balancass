/* =====================================================================
   Balanças.com — Design 2 "Balcão" — home.js
   Hero (pesa → etiqueta → vende), "Monte seu PDV" com Flip, marcas,
   destaques, depoimentos, equipe e formulário de contato.
   ===================================================================== */
(function () {
  "use strict";

  var doc = document;
  var BC = window.BC;
  var D2 = window.D2 || {};
  var gsap = window.gsap;
  var Flip = window.Flip;
  var anim = !!D2.anim;
  var $all = D2.$all || function (s, c) { return Array.prototype.slice.call((c || doc).querySelectorAll(s)); };
  var esc = D2.esc || function (s) { return String(s); };
  var E = BC && BC.empresa;

  /* ==================================================================
     1. Conteúdo vindo de empresa.js / produtos.js
     ================================================================== */
  function renderDados() {
    if (!BC) return;

    // Produtos em destaque
    var grid = doc.querySelector("[data-destaques]");
    if (grid) {
      var lista = BC.destaques().slice(0, 8);
      grid.innerHTML = lista.map(function (p) { return D2.card(p, { base: "produtos.html" }); }).join("");
      $all(".pcard", grid).forEach(function (c) { c.setAttribute("data-reveal", ""); });
    }

    // Marcas
    var ml = doc.querySelector("[data-marcas]");
    if (ml && E.marcas && E.marcas.length) {
      ml.innerHTML = E.marcas.map(function (m) { return "<li>" + esc(m) + "</li>"; }).join("");
    }

    // Clientes
    var cl = doc.querySelector("[data-clientes]");
    if (cl && E.clientes && E.clientes.length) {
      cl.innerHTML = E.clientes.map(function (c) {
        return '<li data-reveal><img src="' + esc(BC.img("clientes/" + c.logo)) + '" alt="' + esc(c.nome) + '" loading="lazy" decoding="async"></li>';
      }).join("");
    }

    // Depoimentos
    var dl = doc.querySelector("[data-depoimentos]");
    if (dl && E.depoimentos && E.depoimentos.length) {
      dl.innerHTML = E.depoimentos.map(function (d) {
        return '<li class="quote" data-reveal><svg class="i quote__mark" aria-hidden="true"><use href="#i-quote"/></svg>' +
          "<blockquote>" + esc(d.texto) + "</blockquote>" +
          '<p class="quote__who"><span class="mono" aria-hidden="true">' + esc((d.nome || "?").charAt(0)) + "</span>" +
          "<span><b>" + esc(d.nome) + "</b><small>" + esc(d.empresa) + " · cliente BC System</small></span></p></li>";
      }).join("");
    }

    // Equipe
    var el = doc.querySelector("[data-equipe]");
    if (el && E.equipe && E.equipe.length) {
      el.innerHTML = E.equipe.map(function (p) {
        var ini = String(p.nome || "").split(/\s+/).filter(Boolean);
        ini = (ini[0] ? ini[0].charAt(0) : "") + (ini.length > 1 ? ini[ini.length - 1].charAt(0) : "");
        return '<li class="person" data-reveal><span class="person__mono" aria-hidden="true">' + esc(ini.toUpperCase()) + "</span>" +
          '<b class="person__name">' + esc(p.nome) + '</b><span class="person__role">' + esc(p.cargo) + "</span>" +
          "<p>" + esc(p.texto) + "</p></li>";
      }).join("");
    }
  }

  /* ==================================================================
     2. Faixa de marcas (marquee com pausa)
     ================================================================== */
  function initMarquee() {
    var mq = doc.querySelector("[data-marquee]");
    if (!mq) return;
    var track = mq.querySelector(".marquee__track");
    var list = mq.querySelector(".marquee__list");
    if (!track || !list || D2.reduce) return;
    var clone = list.cloneNode(true);
    clone.setAttribute("aria-hidden", "true");
    clone.removeAttribute("data-marcas");
    track.appendChild(clone);
    mq.classList.add("is-ready");
    var btn = doc.querySelector("[data-marquee-toggle]");
    if (btn) {
      btn.addEventListener("click", function () {
        var paused = mq.classList.toggle("is-paused");
        btn.setAttribute("aria-pressed", String(paused));
        btn.querySelector('[data-ico="pause"]').hidden = paused;
        btn.querySelector('[data-ico="play"]').hidden = !paused;
        btn.querySelector("[data-lbl]").textContent = paused ? "Continuar faixa de marcas" : "Pausar faixa de marcas";
      });
    }
  }

  /* ==================================================================
     3. HERO — pesa, etiqueta, vende
     ================================================================== */
  var ITENS = [
    { nome: "PÃO FRANCÊS", curto: "Pão francês", kg: 0.452, pkg: 15.99, cod: "00145" },
    { nome: "QUEIJO PRATO", curto: "Queijo prato", kg: 0.318, pkg: 54.90, cod: "00231" },
    { nome: "PICANHA", curto: "Picanha", kg: 1.236, pkg: 79.90, cod: "00087" },
    { nome: "BANANA PRATA", curto: "Banana prata", kg: 1.105, pkg: 6.99, cod: "00412" }
  ];
  var idx = 0;
  var vendas = 4810.07;
  var heroTl = null;

  // Código de barras EAN-13 de verdade (formato de etiqueta de balança: 2 + código + 0 + total)
  var EAN_L = ["0001101", "0011001", "0010011", "0111101", "0100011", "0110001", "0101111", "0111011", "0110111", "0001011"];
  var EAN_G = ["0100111", "0110011", "0011011", "0100001", "0011101", "0111001", "0000101", "0010001", "0001001", "0010111"];
  var EAN_R = ["1110010", "1100110", "1101100", "1000010", "1011100", "1001110", "1010000", "1000100", "1001000", "1110100"];
  var EAN_P = ["LLLLLL", "LLGLGG", "LLGGLG", "LLGGGL", "LGLLGG", "LGGLLG", "LGGGLL", "LGLGGL", "LGLGLL", "LGGLGL"];
  function ean13(d12) {
    var d = d12.split("").map(Number), s = 0, i;
    for (i = 0; i < 12; i++) s += d[i] * (i % 2 ? 3 : 1);
    d.push((10 - (s % 10)) % 10);
    var bits = "101", par = EAN_P[d[0]];
    for (i = 1; i <= 6; i++) bits += (par[i - 1] === "L" ? EAN_L : EAN_G)[d[i]];
    bits += "01010";
    for (i = 7; i <= 12; i++) bits += EAN_R[d[i]];
    bits += "101";
    return { bits: bits, digits: d.join("") };
  }
  function drawBarcode(svg, bits) {
    var out = "", x = 0;
    while (x < bits.length) {
      if (bits[x] === "1") {
        var w = 1;
        while (bits[x + w] === "1") w++;
        var guard = x < 3 || (x >= 45 && x < 50) || x >= 92;
        out += '<rect x="' + x + '" y="0" width="' + w + '" height="' + (guard ? 30 : 26) + '"/>';
        x += w;
      } else x++;
    }
    svg.innerHTML = out;
  }
  function pad(n, len) { n = String(n); while (n.length < len) n = "0" + n; return n; }
  function brl(v) { return D2.fmtNum ? D2.fmtNum(v, 2) : v.toFixed(2).replace(".", ","); }

  var H = {};
  function heroRefs() {
    H.ro = {
      nome: doc.querySelector('[data-ro="nome"]'),
      kg: doc.querySelector('[data-ro="kg"]'),
      pkg: doc.querySelector('[data-ro="pkg"]'),
      tot: doc.querySelector('[data-ro="tot"]'),
      stable: doc.querySelector('[data-ro="stable"]'),
      sr: doc.querySelector("[data-ro-sr]")
    };
    H.label = doc.querySelector("[data-label]");
    H.lbl = {
      nome: doc.querySelector('[data-lbl="nome"]'),
      kg: doc.querySelector('[data-lbl="kg"]'),
      pkg: doc.querySelector('[data-lbl="pkg"]'),
      tot: doc.querySelector('[data-lbl="tot"]'),
      ean: doc.querySelector('[data-lbl="ean"]')
    };
    H.bc = doc.querySelector("[data-barcode]");
    H.sysTotal = doc.querySelector("[data-sys-total]");
    H.toast = doc.querySelector("[data-sys-toast]");
    H.last = doc.querySelector("[data-sys-last]");
    H.bars = $all(".sys__chart i");
    H.replay = doc.querySelector("[data-replay]");
    return !!(H.ro.kg && H.label && H.bc);
  }

  function setLabel(it, tot) {
    var cents = Math.round(tot * 100);
    var code = ean13("2" + it.cod + "0" + pad(cents, 5));
    drawBarcode(H.bc, code.bits);
    H.lbl.nome.textContent = it.nome;
    H.lbl.kg.textContent = it.kg.toFixed(3).replace(".", ",") + " kg";
    H.lbl.pkg.textContent = brl(it.pkg);
    H.lbl.tot.textContent = "R$ " + brl(tot);
    var g = code.digits;
    H.lbl.ean.textContent = g.charAt(0) + " " + g.slice(1, 7) + " " + g.slice(7);
  }

  function setReadout(kg, it) {
    H.ro.kg.textContent = kg.toFixed(3);
    H.ro.tot.textContent = (Math.round(kg * it.pkg * 100) / 100).toFixed(2);
  }

  function pesar(animate) {
    var it = ITENS[idx];
    var tot = Math.round(it.kg * it.pkg * 100) / 100;
    if (heroTl) { heroTl.kill(); heroTl = null; }
    H.ro.nome.textContent = it.nome;
    H.ro.pkg.textContent = it.pkg.toFixed(2);
    if (H.ro.sr) H.ro.sr.textContent = "Exemplo de pesagem: " + it.curto.toLowerCase() + ", " + it.kg.toFixed(3).replace(".", ",") + " kg a R$ " + brl(it.pkg) + " o quilo, total R$ " + brl(tot) + ".";
    setLabel(it, tot);
    var antes = vendas;
    vendas = Math.round((vendas + tot) * 100) / 100;

    if (!animate || !anim) {
      setReadout(it.kg, it);
      H.sysTotal.textContent = brl(vendas);
      H.last.textContent = "R$ " + brl(tot) + " · " + it.curto;
      return;
    }

    var o = { kg: 0 }, s = { v: antes };
    var rects = $all("rect", H.bc);
    var tl = heroTl = gsap.timeline();
    tl.set(H.ro.stable, { opacity: 0.12 })
      .set(H.label, { yPercent: -104 })
      .set(rects, { scaleY: 0, transformOrigin: "50% 0%" })
      .to(o, { kg: it.kg * 1.16, duration: 0.35, ease: "power2.out", onUpdate: function () { setReadout(o.kg, it); } })
      .to(o, { kg: it.kg, duration: 0.85, ease: "elastic.out(1, 0.45)", onUpdate: function () { setReadout(o.kg, it); } })
      .to(H.ro.stable, { opacity: 1, duration: 0.2 }, "-=0.3")
      // impressora empurra a etiqueta para fora, em passos
      .to(H.label, { yPercent: 0, duration: 0.9, ease: "steps(12)" }, "-=0.35")
      .to(rects, { scaleY: 1, duration: 0.2, ease: "power2.out", stagger: { each: 0.01 } }, "-=0.6")
      // venda no sistema
      .add(function () { H.last.textContent = "R$ " + brl(tot) + " · " + it.curto; }, "-=0.05")
      .fromTo(H.toast, { y: 18, opacity: 0, scale: 0.96 }, { y: 0, opacity: 1, scale: 1, duration: 0.6, ease: "back.out(1.7)" }, "<")
      .to(s, { v: vendas, duration: 0.9, ease: "power2.out", onUpdate: function () { H.sysTotal.textContent = brl(s.v); } }, "<")
      .fromTo(H.bars[H.bars.length - 1], { scaleY: 0.82 }, { scaleY: 1, duration: 0.7, ease: "elastic.out(1, 0.5)" }, "<");
    return tl;
  }

  function initHero() {
    var bento = doc.querySelector("[data-hero-bento]");
    if (!bento || !BC || !heroRefs()) { doc.documentElement.classList.remove("intro"); return; }
    var html = doc.documentElement;
    var intro = html.classList.contains("intro");

    if (H.replay) {
      H.replay.addEventListener("click", function () {
        idx = (idx + 1) % ITENS.length;
        pesar(true);
      });
    }

    if (!anim || !intro) {
      html.classList.remove("intro");
      // sem animação: estado final já está no HTML; só ajusta o código de barras
      pesar(false);
      return;
    }

    var title = doc.querySelector("[data-intro-title]");
    var intros = $all("[data-intro]");
    var tiles = $all("[data-tile]", bento);
    var hdr = doc.querySelector(".hdr");
    var lines = [title];
    var split = null;
    if (window.SplitText) {
      split = window.SplitText.create(title, { type: "lines", mask: "lines", linesClass: "hl-line" });
      (split.masks || []).forEach(function (m) { m.style.paddingBottom = ".12em"; m.style.marginBottom = "-.12em"; });
      lines = split.lines;
    }

    // estado inicial (o CSS .intro deixava invisível; agora o GSAP assume)
    gsap.set(intros, { opacity: 0, y: 26 });
    gsap.set(title, { opacity: 1 });
    gsap.set(lines, { yPercent: 118 });
    gsap.set(tiles, { opacity: 0, y: 70, scale: 0.9, rotate: function (i) { return [-4, 3, -2.5, 1.5][i % 4]; }, transformOrigin: "50% 80%" });
    gsap.set(H.bars, { scaleY: 0, transformOrigin: "50% 100%" });
    gsap.set(H.toast, { opacity: 0, y: 18 });
    gsap.set(H.label, { yPercent: -104 });
    var s0 = { v: 0 };
    H.sysTotal.textContent = brl(0);
    setReadout(0, ITENS[0]);
    html.classList.remove("intro");

    var tl = gsap.timeline({ defaults: { ease: "expo.out" } });
    if (hdr) tl.from(hdr, { yPercent: -100, duration: 1, clearProps: "transform" }, 0);
    tl.to(intros[0], { opacity: 1, y: 0, duration: 1 }, 0.1)
      .to(lines, { yPercent: 0, duration: 1.25, stagger: 0.1 }, 0.15)
      .to(intros.slice(1), { opacity: 1, y: 0, duration: 1.1, stagger: 0.09 }, 0.5)
      .to(tiles, { opacity: 1, y: 0, scale: 1, rotate: 0, duration: 1.5, stagger: 0.12, ease: "expo.out", clearProps: "scale,rotate" }, 0.3)
      .to(H.bars, { scaleY: 1, duration: 0.9, stagger: 0.05, ease: "power3.out" }, 0.9)
      .to(s0, { v: vendas, duration: 1.6, ease: "power3.out", onUpdate: function () { H.sysTotal.textContent = brl(s0.v); } }, 0.9)
      .add(function () { pesar(true); }, 0.95);

    // linha de leitura do "laser" passando pelo título
    var scan = doc.createElement("span");
    scan.className = "hero__scan";
    scan.setAttribute("aria-hidden", "true");
    title.parentNode.insertBefore(scan, title);
    tl.add(function () {
      var top = title.offsetTop, h = title.offsetHeight;
      gsap.fromTo(scan, { y: top, opacity: 0 }, {
        keyframes: [
          { opacity: 1, duration: 0.15 },
          { y: top + h, duration: 0.9, ease: "power1.inOut" },
          { opacity: 0, duration: 0.25 }
        ],
        onComplete: function () { scan.remove(); }
      });
    }, 1.05);

    // Paralaxe com o ponteiro (só mouse/trackpad)
    if (D2.fine) {
      var movers = tiles.map(function (t) {
        var d = parseFloat(t.getAttribute("data-depth") || "1");
        return { d: d, x: gsap.quickTo(t, "x", { duration: 0.9, ease: "power3" }), y: gsap.quickTo(t, "y", { duration: 0.9, ease: "power3" }) };
      });
      var prod = bento.querySelector(".hb__prod");
      var px = prod && gsap.quickTo(prod, "xPercent", { duration: 1.1, ease: "power3" });
      var hero = doc.querySelector(".hero");
      var active = false;
      tl.eventCallback("onComplete", function () { active = true; });
      hero.addEventListener("mousemove", function (e) {
        if (!active) return;
        var nx = e.clientX / window.innerWidth - 0.5, ny = e.clientY / window.innerHeight - 0.5;
        movers.forEach(function (m) { m.x(nx * 10 * m.d); m.y(ny * 8 * m.d); });
        if (px) px(-50 + nx * 3);
      });
      hero.addEventListener("mouseleave", function () { movers.forEach(function (m) { m.x(0); m.y(0); }); });
    }

    // Paralaxe sutil ao rolar
    if (window.ScrollTrigger) {
      gsap.to(bento, { yPercent: -6, ease: "none", scrollTrigger: { trigger: ".hero", start: "top top", end: "bottom top", scrub: true } });
    }
  }

  /* ==================================================================
     4. MONTE SEU PDV — troca de segmento com Flip
     ================================================================== */
  var NOMES = {
    etiqueta: "Balança com etiqueta", computadora: "Balança computadora", checkout: "Balança de checkout",
    plataforma: "Balança de plataforma", hospitalar: "Balança antropométrica", pdv: "Computador para PDV",
    bcsystem: "BC System", cupom: "Impressora de cupom", leitor: "Leitor de código de barras",
    gaveta: "Gaveta de dinheiro", etiquetadora: "Impressora de etiquetas", fatiador: "Fatiador de frios"
  };
  var KITS = {
    padaria: { nome: "Padaria", desc: "Pesou, etiquetou, passou no caixa. A balança imprime a etiqueta com código de barras e o PDV lê na hora, com NFC-e emitida pelo BC System.", itens: ["etiqueta", "pdv", "bcsystem", "cupom", "leitor", "fatiador"] },
    acougue: { nome: "Açougue", desc: "Etiqueta com peso e preço em cada bandeja, caixa rápido e estoque das peças sob controle. Balança com etiqueta para o movimento do dia a dia.", itens: ["etiqueta", "pdv", "bcsystem", "cupom", "leitor", "gaveta"] },
    hortifruti: { nome: "Hortifrúti", desc: "Pesagem direto no caixa: a balança de checkout trabalha junto com o PDV e o cliente não precisa passar em outra balança.", itens: ["checkout", "computadora", "pdv", "bcsystem", "cupom", "gaveta"] },
    mercado: { nome: "Mercado", desc: "Do açougue ao caixa: balanças com etiqueta nos setores, checkout com balança e leitor, e o BC System cuidando de estoque, compras e NFC-e.", itens: ["checkout", "etiqueta", "pdv", "bcsystem", "leitor", "cupom", "gaveta"] },
    restaurante: { nome: "Restaurante", desc: "Self-service por quilo com balança que calcula o preço, caixa ágil e cupom na hora. Serve para restaurantes e lanchonetes.", itens: ["computadora", "pdv", "bcsystem", "cupom", "gaveta"] },
    petshop: { nome: "Pet shop", desc: "Ração a granel na balança computadora, leitor para os produtos embalados e o BC System controlando estoque e vendas.", itens: ["computadora", "pdv", "bcsystem", "leitor", "cupom", "gaveta"] },
    varejo: { nome: "Varejo", desc: "Lojas de roupas e varejo em geral: etiquetas de preço, leitor no caixa e BC System com carnê e controle de consignação.", itens: ["pdv", "bcsystem", "leitor", "cupom", "etiquetadora", "gaveta"] },
    farmacia: { nome: "Farmácia", desc: "Balança antropométrica para os clientes, caixa com leitor de código de barras e emissão de NFC-e pelo BC System.", itens: ["hospitalar", "pdv", "bcsystem", "leitor", "cupom", "gaveta"] },
    industria: { nome: "Indústria", desc: "Balança de plataforma para conferência e expedição, etiquetas com código de barras e estoque com emissão de NF-e.", itens: ["plataforma", "etiquetadora", "leitor", "pdv", "bcsystem"] }
  };
  var ORDEM = ["etiqueta", "pdv", "bcsystem", "cupom", "leitor", "fatiador", "gaveta", "computadora", "checkout", "etiquetadora", "plataforma", "hospitalar"];

  function initSegments() {
    var tabs = $all(".seg__tab");
    var grid = doc.querySelector("[data-eqgrid]");
    if (!tabs.length || !grid) return;
    var panel = doc.getElementById("seg-panel");
    var tabsBox = doc.querySelector(".seg__tabs");
    var kitIn = doc.querySelector("[data-kit]");
    var kTitle = doc.querySelector("[data-kit-title]");
    var kDesc = doc.querySelector("[data-kit-desc]");
    var kList = doc.querySelector("[data-kit-list]");
    var kWa = doc.querySelector("[data-kit-wa]");
    var tiles = {};
    $all(".eq", grid).forEach(function (li) { tiles[li.getAttribute("data-eq")] = li; });

    // indicador deslizante
    var ind = doc.createElement("span");
    ind.className = "seg__ind";
    ind.setAttribute("aria-hidden", "true");
    tabsBox.insertBefore(ind, tabsBox.firstChild);
    tabsBox.classList.add("has-ind");
    function moveInd(tab, instant) {
      if (instant) ind.style.transition = "none";
      ind.style.width = tab.offsetWidth + "px";
      ind.style.transform = "translateX(" + tab.offsetLeft + "px)";
      if (instant) { ind.offsetWidth; ind.style.transition = ""; }
    }

    function waMsg(kit) {
      return "Olá! Vim pelo site da Balanças.com e quero um orçamento do kit para *" + kit.nome + "*: " +
        kit.itens.map(function (k) { return NOMES[k]; }).join(", ") + ".";
    }

    function apply(key, animate) {
      var kit = KITS[key];
      if (!kit) return;
      var els = ORDEM.map(function (k) { return tiles[k]; }).filter(Boolean);
      var state = (animate && anim && Flip) ? Flip.getState(els) : null;

      var order = kit.itens.concat(ORDEM.filter(function (k) { return kit.itens.indexOf(k) === -1; }));
      order.forEach(function (k, i) {
        var li = tiles[k];
        if (!li) return;
        var on = i < kit.itens.length;
        li.classList.toggle("is-kit", on);
        li.classList.toggle("is-off", !on);
        var n = li.querySelector(".eq__n");
        if (n) n.textContent = on ? String(i + 1) : "";
        grid.appendChild(li);
      });

      if (state) {
        Flip.from(state, {
          duration: 0.8, ease: "power3.inOut", stagger: 0.012, absolute: false,
          onEnter: function (en) { return gsap.fromTo(en, { opacity: 0, scale: 0.9 }, { opacity: 1, scale: 1, duration: 0.5, delay: 0.25, clearProps: "opacity,scale,transform" }); }
        });
      }

      function swap() {
        kTitle.textContent = kit.nome;
        kDesc.textContent = kit.desc;
        kList.innerHTML = kit.itens.map(function (k) { return "<li>" + esc(NOMES[k]) + "</li>"; }).join("");
        if (kWa && BC) kWa.setAttribute("href", BC.whatsLink(waMsg(kit)));
      }
      if (animate && anim) {
        gsap.timeline()
          .to(kitIn, { opacity: 0, y: -10, duration: 0.22, ease: "power2.in", onComplete: swap })
          .fromTo(kitIn, { opacity: 0, y: 14 }, { opacity: 1, y: 0, duration: 0.6, ease: "expo.out", clearProps: "transform,opacity" })
          .from(kList.children, { opacity: 0, x: -12, duration: 0.5, stagger: 0.05, ease: "expo.out", clearProps: "all" }, "-=0.4");
      } else swap();
    }

    function select(tab, opts) {
      opts = opts || {};
      tabs.forEach(function (t) {
        var on = t === tab;
        t.setAttribute("aria-selected", String(on));
        t.tabIndex = on ? 0 : -1;
      });
      panel.setAttribute("aria-labelledby", tab.id);
      moveInd(tab, opts.instant);
      apply(tab.getAttribute("data-seg"), !opts.instant);
      if (opts.focus) tab.focus();
      // mantém a aba visível no carrossel horizontal (mobile)
      var wrap = tabsBox.parentElement;
      if (wrap && wrap.scrollWidth > wrap.clientWidth) {
        var left = tab.offsetLeft - (wrap.clientWidth - tab.offsetWidth) / 2;
        wrap.scrollTo({ left: left, behavior: D2.reduce ? "auto" : "smooth" });
      }
    }

    tabs.forEach(function (t, i) {
      t.addEventListener("click", function () { if (t.getAttribute("aria-selected") !== "true") select(t); });
      t.addEventListener("keydown", function (e) {
        var j = null;
        if (e.key === "ArrowRight") j = (i + 1) % tabs.length;
        else if (e.key === "ArrowLeft") j = (i - 1 + tabs.length) % tabs.length;
        else if (e.key === "Home") j = 0;
        else if (e.key === "End") j = tabs.length - 1;
        if (j !== null) { e.preventDefault(); select(tabs[j], { focus: true }); }
      });
    });

    var cur = tabs.filter(function (t) { return t.getAttribute("aria-selected") === "true"; })[0] || tabs[0];
    select(cur, { instant: true });
    window.addEventListener("resize", function () {
      var sel = tabs.filter(function (t) { return t.getAttribute("aria-selected") === "true"; })[0];
      if (sel) moveInd(sel, true);
    });
    if (doc.fonts && doc.fonts.ready) doc.fonts.ready.then(function () { moveInd(cur, true); });
  }

  /* ==================================================================
     5. Formulário → WhatsApp
     ================================================================== */
  function initForm() {
    var f = doc.getElementById("form-contato");
    if (!f || !BC) return;
    var err = f.querySelector("[data-form-err]");
    f.addEventListener("submit", function (e) {
      e.preventDefault();
      var nome = f.nome.value.trim(), assunto = f.assunto.value, msg = f.mensagem.value.trim();
      f.nome.setAttribute("aria-invalid", String(!nome));
      f.mensagem.setAttribute("aria-invalid", String(!msg));
      if (!nome || !msg) {
        err.hidden = false;
        (nome ? f.mensagem : f.nome).focus();
        return;
      }
      err.hidden = true;
      var texto = "Olá! Meu nome é " + nome + ".\n*Assunto:* " + assunto + "\n\n" + msg;
      var w = window.open(BC.whatsLink(texto), "_blank", "noopener");
      if (!w) location.href = BC.whatsLink(texto);
    });
    ["nome", "mensagem"].forEach(function (n) {
      f[n].addEventListener("input", function () { if (f[n].value.trim()) f[n].removeAttribute("aria-invalid"); });
    });
  }

  /* ==================================================================
     6. Detalhes extras de movimento
     ================================================================== */
  function initExtras() {
    if (!anim || !window.ScrollTrigger) return;
    // Imagem do BC System com paralaxe
    $all("[data-parallax]").forEach(function (img) {
      gsap.fromTo(img, { yPercent: 8 }, { yPercent: -8, ease: "none", scrollTrigger: { trigger: img.parentElement, start: "top bottom", end: "bottom top", scrub: true } });
    });
    // SAT riscado
    var strike = doc.querySelector(".sat__strike");
    if (strike) {
      gsap.fromTo(strike, { "--strike": 0 }, { "--strike": 1, duration: 0.9, ease: "power3.inOut", scrollTrigger: { trigger: strike, start: "top 85%", once: true } });
    }
    // Lacre desenhado
    var path = doc.querySelector("[data-draw]");
    if (path && path.getTotalLength) {
      var len = path.getTotalLength();
      path.style.strokeDasharray = len;
      gsap.fromTo(path, { strokeDashoffset: len }, { strokeDashoffset: 0, duration: 1.8, ease: "power2.inOut", scrollTrigger: { trigger: path, start: "top 85%", once: true } });
    }
  }

  /* ==================================================================
     Início
     ================================================================== */
  renderDados();
  initMarquee();
  initSegments();
  initForm();
  function boot() {
    initHero();
    initExtras();
  }
  // Espera as fontes (para quebrar as linhas do título certo), sem travar
  var started = false;
  function go() { if (!started) { started = true; boot(); } }
  if (doc.fonts && doc.fonts.ready && anim) {
    doc.fonts.ready.then(go);
    setTimeout(go, 900);
  } else go();
})();
