/* =====================================================================
   Balanças.com — "Linha Verde" — PÁGINA INICIAL · VARIAÇÃO 1 "VITRINE"
   js/home-v1.js — carregado depois de js/core.js (window.LV) e antes
   dos scripts do CDN. Tudo o que é produto vem de window.BC.
   1. Vitrine do hero: três peças — Pesa · Imprime · Vende — com abas,
      troca automática (pausável) e movimento de "esteira".
   2. Produtos em destaque: BC.destaques(), misturando as categorias.
   3. Atalhos de categoria com a contagem real do catálogo.
   4. Animações (LV.onMotion): cota/régua do hero e contadores.
   Sem JS: a página mostra a Prix 4 Uno parada e um link para o catálogo.
   ===================================================================== */
(function () {
  "use strict";

  var BC = window.BC, LV = window.LV;
  if (!BC || !LV) return;
  var E = BC.empresa || {};
  var $ = LV.$, $$ = LV.$$;
  var reduceMQ = window.matchMedia("(prefers-reduced-motion: reduce)");

  /* ------------------------------------------------------------------
     1. VITRINE DO HERO
     ------------------------------------------------------------------ */
  var VITRINE = [
    { palavra: "Pesa", id: "toledo-prix-4-uno",
      alt: function (p) { return "Balança " + p.marca + " " + p.nome + " " + String(p.subcategoria || "").toLowerCase(); },
      c1: function (p) { var c = LV.spec(p, "capacidade"); return c ? "Pesa até " + LV.curto(c, "capacidade") : p.subcategoria; } },
    { palavra: "Imprime", id: "zebra-zt230",
      alt: function (p) { return "Impressora de etiquetas " + p.marca + " " + p.nome; },
      c1: function (p) { var v = LV.spec(p, "velocidade"); return v ? LV.curto(v, "velocidade") : p.subcategoria; } },
    { palavra: "Vende", sistema: "BC System" }
  ];

  function montaItens() {
    var itens = [];
    VITRINE.forEach(function (v) {
      if (v.sistema) {
        var s = (E.produtosProprios || []).filter(function (x) { return x.nome === v.sistema; })[0];
        if (!s) return;
        itens.push({
          palavra: v.palavra, nome: s.nome, href: "sistema.html",
          src: BC.img("sistema/bc-system-tablet-celular.webp"),
          alt: s.nome + " em tablet e celular: cadastro de produtos e tela de vendas",
          c1: "NFC-e e NF-e", c2: "Integrado à balança"
        });
        return;
      }
      var p = BC.porId(v.id);
      if (!p) return;
      itens.push({
        palavra: v.palavra, id: p.id, nome: p.marca + " " + p.nome, href: "produtos.html#p=" + encodeURIComponent(p.id),
        src: BC.imgProduto(p, true),
        alt: v.alt(p),
        c1: v.c1(p), c2: p.subcategoria
      });
    });
    return itens;
  }

  var vit = $("[data-vit]");
  var itens = montaItens();
  var vitAPI = null;

  function setupVitrine() {
    if (!vit || itens.length < 2) return;
    var stage = $("[data-vit-stage]", vit);
    var first = $(".vit__img", stage);
    var nameEl = $("[data-vit-name]", vit), link = $("[data-vit-link]", vit);
    var c1 = $("[data-vit-c1]", vit), c2 = $("[data-vit-c2]", vit), nEl = $("[data-vit-n]", vit), ref = $("[data-vit-ref]", vit);
    var imgs = [], cur = 0, busy = false;

    // O HTML já traz a primeira peça (sem JS); garante que ela corresponde ao item 0
    if (first) {
      first.setAttribute("data-vit-i", "0");
      if (first.getAttribute("src") !== itens[0].src) { first.src = itens[0].src; }
      first.alt = itens[0].alt;
      imgs[0] = first;
    }
    function img(i) {
      if (imgs[i]) return imgs[i];
      var im = document.createElement("img");
      im.className = "vit__img"; im.setAttribute("data-vit-i", String(i));
      im.src = itens[i].src; im.alt = itens[i].alt; im.decoding = "async";
      im.width = 900; im.height = 600;
      stage.insertBefore(im, $(".vit__ruler", stage));
      imgs[i] = im;
      return im;
    }
    // Pré-carrega as outras peças depois que a página terminou de carregar
    function preload() { for (var i = 1; i < itens.length; i++) img(i); }
    if (document.readyState === "complete") setTimeout(preload, 300);
    else window.addEventListener("load", function () { setTimeout(preload, 300); });

    // Abas + pausa
    var nav = document.createElement("div");
    nav.className = "vit__nav";
    nav.setAttribute("role", "group");
    nav.setAttribute("aria-label", "Vitrine de produtos");
    nav.innerHTML = itens.map(function (it, i) {
      return '<button class="vit__tab" type="button" aria-pressed="' + (i === 0) + '" data-vit-tab="' + i + '">' +
        '<span class="vit__tab-n" aria-hidden="true">' + LV.pad(i + 1) + '</span><span>' + LV.esc(it.palavra) + '</span>' +
        '<span class="sr-only">: ' + LV.esc(it.nome) + '</span><span class="vit__prog" aria-hidden="true"></span></button>';
    }).join("") +
      '<button class="vit__pause" type="button" data-vit-pause aria-label="Pausar a vitrine"><svg class="i" aria-hidden="true"><use href="#i-pause"/></svg></button>';
    vit.appendChild(nav);
    var live = document.createElement("p");
    live.className = "sr-only"; live.setAttribute("aria-live", "polite"); live.setAttribute("data-vit-live", "");
    vit.appendChild(live);
    var tabs = $$("[data-vit-tab]", nav), pauseBtn = $("[data-vit-pause]", nav);

    function textos(i) {
      var it = itens[i];
      if (nameEl) nameEl.textContent = it.nome;
      if (link) {
        link.href = it.href;
        link.setAttribute("aria-label", (it.id ? "Ver a ficha: " : "Conhecer o ") + it.nome);
      }
      if (c1) c1.textContent = it.c1;
      if (c2) c2.textContent = it.c2;
      if (nEl) nEl.textContent = LV.pad(i + 1);
      if (ref) ref.textContent = "Ref. " + LV.pad(i + 1) + " — " + it.nome;
      tabs.forEach(function (t, k) { t.setAttribute("aria-pressed", String(k === i)); });
    }
    textos(0);

    function show(i, user) {
      i = (i + itens.length) % itens.length;
      if (i === cur || busy) return;
      var from = imgs[cur], to = img(i), dir = !user || i > cur ? 1 : -1;   // a esteira anda sempre para a esquerda
      cur = i;
      textos(i);
      if (user) live.textContent = itens[i].palavra + ": " + itens[i].nome;
      if (LV.motionOK()) {
        busy = true;
        to.classList.add("is-on");
        var tl = gsap.timeline({ defaults: { ease: "expo.inOut" }, onComplete: function () {
          from.classList.remove("is-on");
          gsap.set([from, to], { clearProps: "transform,opacity,visibility" });
          busy = false;
        } });
        tl.fromTo(from, { xPercent: 0, autoAlpha: 1 }, { xPercent: -30 * dir, autoAlpha: 0, duration: 0.6, ease: "power3.in" }, 0)
          .fromTo(to, { xPercent: 30 * dir, autoAlpha: 0 }, { xPercent: 0, autoAlpha: 1, duration: 1.05, ease: "expo.out" }, 0.34)
          .fromTo(".vit__dim", { scaleX: 0.15 }, { scaleX: 1, duration: 0.9 }, 0.2)
          .fromTo([".vit__cap", ".vit .phero__call"], { autoAlpha: 0, y: 6 }, { autoAlpha: 1, y: 0, duration: 0.5, ease: "expo.out", stagger: 0.05 }, 0.55);
      } else {
        to.classList.add("is-on");
        from.classList.remove("is-on");
      }
    }

    // Troca automática: a barrinha da aba ativa (animação CSS) dita o tempo
    var stopped = false, holds = {};
    function autoOK() { return !stopped && !reduceMQ.matches; }
    function sync() {
      vit.classList.toggle("is-auto", autoOK());
      var held = Object.keys(holds).some(function (k) { return holds[k]; });
      vit.classList.toggle("is-hold", held);
    }
    function hold(k, on) { holds[k] = on; sync(); }
    function setStopped(v) {
      stopped = v;
      pauseBtn.setAttribute("aria-label", v ? "Continuar a vitrine" : "Pausar a vitrine");
      $("use", pauseBtn).setAttribute("href", v ? "#i-play" : "#i-pause");
      sync();
    }
    nav.addEventListener("animationend", function (e) {
      if (e.animationName !== "vt-prog" || !autoOK()) return;
      show(cur + 1, false);
    });
    tabs.forEach(function (t, i) {
      t.addEventListener("click", function () {
        if (i !== cur) show(i, true);
        if (!stopped) setStopped(true);   // quem escolhe assume o controle
      });
    });
    pauseBtn.addEventListener("click", function () {
      setStopped(!stopped);
      if (!stopped) hold("foco", false);
    });
    // Pausa enquanto o mouse está na vitrine, o foco está nela ou o hero saiu da tela
    vit.addEventListener("mouseenter", function () { hold("mouse", true); });
    vit.addEventListener("mouseleave", function () { hold("mouse", false); });
    vit.addEventListener("focusin", function () { hold("foco", true); });
    vit.addEventListener("focusout", function (e) { if (!vit.contains(e.relatedTarget)) hold("foco", false); });
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (en) { hold("tela", !en[0].isIntersecting); }, { threshold: 0.25 }).observe(vit);
    }
    document.addEventListener("visibilitychange", function () { hold("aba", document.hidden); });
    if (reduceMQ.addEventListener) reduceMQ.addEventListener("change", sync);
    sync();

    vitAPI = { show: show, atual: function () { return cur; }, itens: itens };
  }

  /* ------------------------------------------------------------------
     2. PRODUTOS EM DESTAQUE — mistura as categorias (balança, automação,
        informática…) e não repete o que já está na vitrine do hero
     ------------------------------------------------------------------ */
  function destaques(n) {
    var naVitrine = itens.map(function (it) { return it.id; }).filter(Boolean);
    var lista = BC.destaques().filter(function (p) { return naVitrine.indexOf(p.id) === -1; });
    if (lista.length < n) lista = lista.concat(BC.produtos.filter(function (p) { return lista.indexOf(p) === -1 && naVitrine.indexOf(p.id) === -1; }));
    var grupos = {}, ordem = [];
    lista.forEach(function (p) {
      if (!grupos[p.categoria]) { grupos[p.categoria] = []; ordem.push(p.categoria); }
      grupos[p.categoria].push(p);
    });
    (BC.categorias || []).forEach(function (c) { if (ordem.indexOf(c.id) === -1) ordem.push(c.id); });
    var out = [], resta = true;
    while (out.length < n && resta) {
      resta = false;
      ordem.forEach(function (c) {
        var g = grupos[c];
        if (out.length < n && g && g.length) { out.push(g.shift()); resta = true; }
      });
    }
    return out;
  }
  function renderDestaques() {
    var ul = $("[data-vt-destaques]");
    if (!ul) return;
    var lista = destaques(4);
    if (!lista.length) return;
    ul.innerHTML = LV.listaProdutos(lista, { sizes: "(max-width: 639px) 46vw, (max-width: 1180px) 30vw, 340px" });
    $$(".pgrid__i > .spec", ul).forEach(function (c) { c.setAttribute("data-reveal", ""); });
  }

  /* ------------------------------------------------------------------
     3. Atalhos de categoria (nome e contagem vindos do catálogo)
     ------------------------------------------------------------------ */
  function renderCategorias() {
    var ul = $("[data-vt-cats]");
    if (!ul || !(BC.categorias || []).length) return;
    ul.innerHTML = BC.categorias.map(function (c) {
      var n = BC.filtrar({ categoria: c.id }).length;
      return '<li><a href="produtos.html?cat=' + encodeURIComponent(c.id) + '"><span>' + LV.esc(c.nome) + '</span>' +
        '<b>' + n + '<span class="sr-only"> produtos</span></b><svg class="i" aria-hidden="true"><use href="#i-arrow"/></svg></a></li>';
    }).join("");
  }

  // Botão "Ligar": a largura reservada do texto acompanha o telefone dos dados
  $$("[data-vt-tel]").forEach(function (el) { if (E.telefone) el.setAttribute("data-t", E.telefone); });

  setupVitrine();
  renderDestaques();
  renderCategorias();
  LV.vitrine = vitAPI;

  /* ------------------------------------------------------------------
     4. Animações (só com GSAP e sem "reduzir movimento")
     ------------------------------------------------------------------ */
  LV.onMotion(function () {
    var viaVT = document.documentElement.classList.contains("via-vt");

    // Cota, régua e nome do produto entram depois da figura (o core anima faixa, título e figura)
    var tl = gsap.timeline({ defaults: { ease: "expo.inOut" } });
    tl.from(".vit__ruler", { scaleY: 0, transformOrigin: "50% 0%", duration: 1.1 }, 0.95)
      .from(".vit__dim", { scaleX: 0, duration: 1.1 }, 1.05)
      .from(".vit__cap", { autoAlpha: 0, y: 10, duration: 0.7, ease: "expo.out" }, 1.55)
      .from(".vit__nav > *", { autoAlpha: 0, y: 12, duration: 0.7, stagger: 0.06, ease: "expo.out" }, 1.45);
    if (viaVT) tl.progress(1);

    // Contadores sem pular o layout (o valor final, invisível, reserva a largura)
    $$("[data-vt-count]").forEach(function (el) {
      var txt = (el.textContent || "").trim(), dec = el.hasAttribute("data-vt-dec");
      var to = parseFloat(txt.replace(",", "."));
      if (isNaN(to)) return;
      el.innerHTML = '<span class="vt-cnt"><span class="vt-cnt__g" aria-hidden="true">' + LV.esc(txt) + '</span><span class="vt-cnt__v">' + LV.esc(txt) + "</span></span>";
      var v = el.querySelector(".vt-cnt__v"), o = { n: 0 };
      var fmt = function (n) { return dec ? n.toFixed(1).replace(".", ",") : String(Math.round(n)); };
      gsap.to(o, {
        n: to, duration: 1.6, ease: "power3.out", scrollTrigger: LV.st(el, "top 92%"),
        onStart: function () { v.textContent = fmt(0); },
        onUpdate: function () { v.textContent = fmt(o.n); },
        onComplete: function () { v.textContent = txt; }
      });
    });

    // O ano "2010" entra com a barra verde
    $$(".vt-num:first-child .vt-num__v > span").forEach(function (el) {
      LV.wipe(el, { tl: gsap.timeline({ scrollTrigger: LV.st(el, "top 92%"), delay: 0.15 }), at: 0, cor: "green" });
    });

    // Imagens dos blocos "O que fazemos": sobem devagar com a rolagem
    $$(".vt-do__fig img, .vt-do__disc").forEach(function (el) {
      gsap.fromTo(el, { yPercent: 8 }, { yPercent: -8, ease: "none", scrollTrigger: { trigger: el.closest(".vt-do__c"), start: "top bottom", end: "bottom top", scrub: true } });
    });
  });
})();
