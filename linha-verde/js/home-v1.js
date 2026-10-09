/* =====================================================================
   Balanças.com — "Linha Verde" — PÁGINA INICIAL · VARIAÇÃO 1 "VITRINE"
   js/home-v1.js — carregado depois de js/core.js (window.LV) e antes
   dos scripts do CDN. Tudo o que é produto vem de window.BC.
   1. Vitrine do hero: três peças — Pesa · Imprime · Vende — com abas,
      troca automática (pausável) e movimento de "esteira".
   2. Produtos em destaque: BC.destaques(), uma peça de cada tipo, em
      fichas leves (variante "compacta").
   3. Seletor de variação (só na fase de escolha) some enquanto o hero está na tela.
   4. Animações (LV.onMotion): cota/régua do hero, contadores e paralaxe.
   Sem JS: a página mostra a Prix 4 Uno parada e os destaques escritos no HTML.
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
  // c1/c2: os dois rótulos técnicos do palco (frases curtas, no singular, sobre a peça mostrada)
  function ate(v) { return String(v || "").replace(/^até\s+/i, ""); }
  var VITRINE = [
    { palavra: "Pesa", id: "toledo-prix-4-uno",
      alt: function (p) { return "Balança " + p.marca + " " + p.nome + " " + String(p.subcategoria || "").toLowerCase(); },
      c1: function (p) { var c = LV.spec(p, "capacidade"); return c ? "Pesa até " + ate(LV.curto(c, "capacidade")) : "Balança"; },
      c2: function () { return "Com impressora de etiquetas"; } },
    { palavra: "Imprime", id: "zebra-zt230",
      alt: function (p) { return "Impressora de etiquetas " + p.marca + " " + p.nome; },
      c1: function (p) { var v = LV.spec(p, "velocidade"); return v ? "Imprime até " + ate(LV.curto(v, "velocidade")) : "Imprime etiquetas"; },
      c2: function () { return "Impressora industrial"; } },
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
        c1: v.c1(p), c2: v.c2(p)
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
    var c1 = $("[data-vit-c1]", vit), c2 = $("[data-vit-c2]", vit);
    var imgs = [], cur = 0, tlAtual = null;

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
      tabs.forEach(function (t, k) { t.setAttribute("aria-pressed", String(k === i)); });
    }
    textos(0);

    function show(i, user) {
      i = (i + itens.length) % itens.length;
      // Troca ainda em andamento (clique rápido): termina a anterior na hora e segue para a nova
      if (tlAtual) tlAtual.progress(1);
      if (i === cur) return;
      var from = imgs[cur], to = img(i), dir = !user || i > cur ? 1 : -1;   // a esteira anda sempre para a esquerda
      cur = i;
      textos(i);
      if (user) live.textContent = itens[i].palavra + ": " + itens[i].nome;
      // Sem GSAP (CDN fora do ar), a troca é um esmaecer em CSS (só sem "reduzir movimento")
      vit.classList.toggle("vit--css", !LV.motionOK());
      if (LV.motionOK()) {
        to.classList.add("is-on");
        var tl = tlAtual = gsap.timeline({ defaults: { ease: "expo.inOut" }, onComplete: function () {
          from.classList.remove("is-on");
          gsap.set([from, to], { clearProps: "transform,opacity,visibility" });
          if (tlAtual === tl) tlAtual = null;
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
    // Pausa enquanto o mouse está na vitrine, o foco está nela ou o hero saiu da tela.
    // Só o mouse de verdade: no toque, o "mouseenter" de compatibilidade nunca tem "mouseleave".
    function ptr(on) { return function (e) { if (!e.pointerType || e.pointerType === "mouse") hold("mouse", on); }; }
    if ("PointerEvent" in window) {
      vit.addEventListener("pointerenter", ptr(true));
      vit.addEventListener("pointerleave", ptr(false));
    }
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
     2. PRODUTOS EM DESTAQUE — uma peça de cada tipo (subcategoria),
        alternando as categorias, sem repetir o que já está no hero.
        Primeiro os preferidos (se ainda forem destaque no catálogo):
        balança de balcão, leitor de checkout, fatiador e impressora de cupom.
     ------------------------------------------------------------------ */
  var PREFERIDOS = ["toledo-prix-3-fit", "elgin-el4200", "toledo-uni-350-ga", "elgin-i9"];
  function destaques(n) {
    var noHero = itens.map(function (it) { return it.id; }).filter(Boolean);
    var subsHero = noHero.map(function (id) { var p = BC.porId(id); return p && p.subcategoria; });
    var base = BC.destaques().filter(function (p) { return noHero.indexOf(p.id) === -1; });
    var out = [], subs = subsHero.slice();
    function pega(p) {
      if (out.length >= n || out.indexOf(p) !== -1 || subs.indexOf(p.subcategoria) !== -1) return;
      out.push(p); subs.push(p.subcategoria);
    }
    PREFERIDOS.forEach(function (id) { var p = base.filter(function (x) { return x.id === id; })[0]; if (p) pega(p); });
    // Completa alternando as categorias (balança, automação, informática…)
    var resto = base.concat(BC.produtos.filter(function (p) { return base.indexOf(p) === -1 && noHero.indexOf(p.id) === -1; }));
    var cats = (BC.categorias || []).map(function (c) { return c.id; });
    for (var volta = 0; volta < 2 && out.length < n; volta++) {
      if (volta === 1) subs = subsHero.slice();   // 2ª volta: aceita repetir subcategoria
      for (var k = 0; k < resto.length && out.length < n; k++) {
        var cat = cats[out.length % (cats.length || 1)];
        var p = resto.filter(function (x) { return x.categoria === cat && out.indexOf(x) === -1 && subs.indexOf(x.subcategoria) === -1; })[0] ||
                resto.filter(function (x) { return out.indexOf(x) === -1 && subs.indexOf(x.subcategoria) === -1; })[0];
        if (!p) break;
        pega(p);
      }
    }
    return out;
  }
  function renderDestaques() {
    var ul = $("[data-vt-destaques]");
    if (!ul) return;
    var lista = destaques(4);
    if (!lista.length) return;
    // Ficha leve: foto, marca, modelo e resumo (sem nº, tabela, selo "consulte" e preço)
    ul.innerHTML = lista.map(function (p, i) {
      return '<li class="pgrid__i" data-id="' + LV.esc(p.id) + '">' +
        LV.cardProduto(p, { n: i + 1, variante: "compacta", linhas: 0, sizes: "(max-width: 899px) 46vw, (max-width: 1519px) 23vw, 340px" }) + "</li>";
    }).join("");
    $$(".pgrid__i > .spec", ul).forEach(function (c) { c.setAttribute("data-reveal", ""); });
  }

  /* ------------------------------------------------------------------
     3. Seletor de variação (js/preview.js, só na fase de escolha):
        some enquanto a primeira tela (hero + faixa) está visível, para
        não cobrir o produto, a faixa de confiança e as marcas.
     ------------------------------------------------------------------ */
  var topo = [$(".phero--home"), $(".vt-ticker")].filter(Boolean);
  if (topo.length && "IntersectionObserver" in window) {
    var naTela = [];
    var ioTopo = new IntersectionObserver(function (en) {
      en.forEach(function (e) { naTela[topo.indexOf(e.target)] = e.isIntersecting; });
      document.body.classList.toggle("vt-topo", naTela.some(Boolean));
    });
    topo.forEach(function (el) { ioTopo.observe(el); });
    document.body.classList.add("vt-topo");
  }

  // Botão "Ligar": a largura reservada do texto acompanha o telefone dos dados
  $$("[data-vt-tel]").forEach(function (el) { if (E.telefone) el.setAttribute("data-t", E.telefone); });

  setupVitrine();
  renderDestaques();
  LV.vitrine = vitAPI;

  /* ------------------------------------------------------------------
     4. Animações (só com GSAP e sem "reduzir movimento")
     ------------------------------------------------------------------ */
  LV.onMotion(function () {
    // via-vt: chegou pela transição entre páginas; lv-late: o CDN demorou e o conteúdo já apareceu
    var doc = document.documentElement;
    var viaVT = doc.classList.contains("via-vt") || doc.classList.contains("lv-late");

    // Paralaxe da figura do hero em pixels (y), não em yPercent: o yPercent é da entrada do
    // core.js, e as duas animações na mesma propriedade deixavam a figura fora do lugar
    // quando se rolava durante a entrada.
    var fig = $(".phero--home .phero__fig");
    if (fig && window.ScrollTrigger) {
      gsap.getTweensOf(fig).forEach(function (t) {
        if (t.parent && t.parent.scrollTrigger && t.vars && t.vars.yPercent != null) t.kill();
      });
      gsap.to(fig, {
        y: function () { return -fig.offsetHeight * 0.08; }, ease: "none",
        scrollTrigger: { trigger: ".phero--home", start: "top top", end: "bottom top", scrub: true, invalidateOnRefresh: true }
      });
    }

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

    // Imagens dos blocos "O que fazemos": sobem devagar com a rolagem. O paralaxe vai no
    // contêiner (.vt-do__fig); o zoom/giro do :hover fica livre na imagem e no disco.
    $$(".vt-do__fig").forEach(function (el) {
      gsap.fromTo(el, { yPercent: 6 }, { yPercent: -6, ease: "none", scrollTrigger: { trigger: el.closest(".vt-do__c"), start: "top bottom", end: "bottom top", scrub: true } });
    });
  });
})();
