/* =====================================================================
   Balanças.com — "Linha Verde" — página inicial, variação 3 "Painel"
   js/home-v3.js — só index-3.html (depois de core.js, antes do CDN).
   1. Produtos (window.BC): a vitrine do módulo 03 e as 4 fichas em
      destaque saem de BC.destaques(), sem repetir e variando a categoria.
   2. Vitrine automática: troca a cada 5 s; pausa pelo botão, com o mouse
      em cima, com o foco do teclado dentro, fora da tela ou com a aba
      oculta. Com "reduzir movimento" começa parada.
   3. Links das categorias (contagem real do catálogo).
   4. Animações (LV.onMotion): abertura do hero, módulos que "acendem"
      com um corte diagonal, régua 2010 → hoje.
   ===================================================================== */
(function () {
  "use strict";
  var LV = window.LV, BC = window.BC;
  if (!LV || !BC || !BC.produtos) return;
  var $ = LV.$, $$ = LV.$$, esc = LV.esc;
  var reduceMQ = window.matchMedia("(prefers-reduced-motion: reduce)");
  var fineMQ = window.matchMedia("(hover: hover) and (pointer: fine)");

  /* ------------------------------------------------------------------
     1. Seleção dos produtos
     ------------------------------------------------------------------ */
  // Recortes claros/inox ficam ruins sobre o preto da vitrine: vão para as fichas (palco branco)
  var CLAROS = ["9094plus.webp", "hospitalar.webp", "bk200f.webp", "2098.webp", "8217.webp", "prix3plus.webp"];

  // Pega até n produtos alternando as categorias (balança, automação, informática, balança…)
  function variado(lista, n) {
    var grupos = {}, ordem = [], out = [];
    lista.forEach(function (p) {
      if (!grupos[p.categoria]) { grupos[p.categoria] = []; ordem.push(p.categoria); }
      grupos[p.categoria].push(p);
    });
    while (out.length < n) {
      var antes = out.length;
      ordem.forEach(function (c) { if (out.length < n && grupos[c].length) out.push(grupos[c].shift()); });
      if (out.length === antes) break;
    }
    return out;
  }
  function fora(lista, usados) { return lista.filter(function (p) { return usados.indexOf(p) < 0; }); }

  var dest = BC.destaques();
  var vitrine = variado(dest.filter(function (p) { return BC.temRecorte(p) && CLAROS.indexOf(p.imagem) < 0; }), 4);
  if (vitrine.length < 2) vitrine = variado(BC.produtos.filter(function (p) { return BC.temRecorte(p) && CLAROS.indexOf(p.imagem) < 0; }), 4);
  var grade = variado(fora(dest, vitrine), 4);
  if (grade.length < 4) grade = grade.concat(variado(fora(fora(BC.produtos, vitrine), grade), 4 - grade.length));

  /* ------------------------------------------------------------------
     2. Vitrine automática (módulo 03)
     ------------------------------------------------------------------ */
  var DUR = 5000;
  function montaVitrine(box, lista) {
    var n = lista.length;
    var ul = $("[data-pn-slides]", box), dotsUl = $("[data-pn-dots]", box), ctrl = $("[data-pn-ctrl]", box);
    var cnt = $("[data-pn-count]", box), tot = $("[data-pn-total]", box), btn = $("[data-pn-pause]", box);
    var tile = box.closest(".pn-t") || box;

    box.setAttribute("role", "region");
    box.setAttribute("aria-roledescription", "carrossel");
    box.setAttribute("aria-label", "Vitrine de produtos");
    ul.innerHTML = lista.map(function (p, i) {
      return '<li class="pn-vit__slide' + (i ? "" : " is-on") + '" role="group" aria-roledescription="produto" aria-label="' + (i + 1) + " de " + n + '">' +
        '<figure class="pn-vit__fig">' + LV.imgProduto(p, { alt: "", sizes: "(max-width: 640px) 86vw, (max-width: 1060px) 50vw, 460px" }) + "</figure>" +
        '<p class="pn-vit__cap"><span class="pn-vit__brand mono">' + esc(p.marca) + " · " + esc(LV.catCurto(p.categoria)) + "</span>" +
        '<a class="pn-vit__name" href="produtos.html#p=' + encodeURIComponent(p.id) + '"><span class="sr-only">' + esc(p.marca) + " </span>" + esc(p.nome) +
        '<svg class="i" aria-hidden="true"><use href="#i-arrow"/></svg></a></p></li>';
    }).join("");
    if (n < 2) return;   // um produto só: fica parado, sem controles

    dotsUl.innerHTML = lista.map(function (p, i) {
      return '<li><button class="pn-vit__dot" type="button" aria-label="Mostrar ' + esc(p.marca + " " + p.nome) + " (" + (i + 1) + " de " + n + ')"' +
        (i ? "" : ' aria-current="true"') + '><span class="pn-vit__fill"></span></button></li>';
    }).join("");
    tot.textContent = LV.pad(n);
    ctrl.hidden = false;

    var slides = $$(".pn-vit__slide", ul), dots = $$(".pn-vit__dot", dotsUl), fills = $$(".pn-vit__fill", dotsUl);
    var cur = 0, t = 0, last = 0, raf = 0;
    var st = { user: reduceMQ.matches, hover: false, focus: false, fora: true, oculta: document.hidden };
    function tocando() { return !st.user && !st.hover && !st.focus && !st.fora && !st.oculta; }

    function fill(i, r) { fills[i].style.transform = "skewX(-12deg) scaleX(" + Math.max(0, Math.min(1, r)) + ")"; }
    function sync() {
      var on = tocando();
      ul.setAttribute("aria-live", on ? "off" : "polite");
      btn.setAttribute("aria-pressed", st.user ? "true" : "false");
      $("use", btn).setAttribute("href", st.user ? "#i-play" : "#i-pause");
      if (on && !raf) { last = 0; raf = requestAnimationFrame(frame); }
    }
    function ir(i) {
      i = (i + n) % n;
      if (i === cur) return;
      var a = slides[cur], b = slides[i];
      a.classList.remove("is-on"); a.classList.add("is-out");
      b.classList.remove("is-out");
      // força o ponto de partida (direita) antes de entrar
      void b.offsetWidth;
      b.classList.add("is-on");
      setTimeout(function () { if (!a.classList.contains("is-on")) a.classList.remove("is-out"); }, 850);
      dots[cur].removeAttribute("aria-current"); fill(cur, 0);
      cur = i; t = 0;
      dots[cur].setAttribute("aria-current", "true"); fill(cur, 0);
      cnt.textContent = LV.pad(cur + 1);
    }
    function frame(now) {
      raf = 0;
      if (!tocando()) return;
      if (last) t += Math.min(100, now - last);
      last = now;
      fill(cur, t / DUR);
      if (t >= DUR) ir(cur + 1);
      raf = requestAnimationFrame(frame);
    }

    dots.forEach(function (d, i) {
      d.addEventListener("click", function () { st.user = true; ir(i); fill(cur, 1); sync(); });
    });
    btn.addEventListener("click", function () { st.user = !st.user; sync(); });
    if (fineMQ.matches) {
      // Mouse em cima pausa. Enquanto pausada, confere se o mouse ainda está lá
      // (rolagem ou redimensionamento podem "sair" sem disparar mouseleave).
      var hv = 0;
      var solta = function () { clearInterval(hv); hv = 0; st.hover = false; sync(); };
      tile.addEventListener("mouseenter", function () {
        st.hover = true; sync();
        if (!hv) hv = setInterval(function () { if (!tile.matches(":hover")) solta(); }, 700);
      });
      tile.addEventListener("mouseleave", solta);
    }
    // Foco do teclado dentro da vitrine: para enquanto estiver lá
    box.addEventListener("focusin", function (e) {
      var kb = true;
      try { kb = e.target.matches(":focus-visible"); } catch (err) { /* navegador antigo */ }
      if (kb) { st.focus = true; sync(); }
    });
    box.addEventListener("focusout", function (e) { if (!box.contains(e.relatedTarget)) { st.focus = false; sync(); } });
    document.addEventListener("visibilitychange", function () { st.oculta = document.hidden; sync(); });
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (en) { st.fora = !en[en.length - 1].isIntersecting; sync(); }, { threshold: 0.25 }).observe(box);
    } else { st.fora = false; }
    // "Reduzir movimento" ligado/desligado com a página aberta
    var onRM = function () { if (reduceMQ.matches) st.user = true; sync(); };
    if (reduceMQ.addEventListener) reduceMQ.addEventListener("change", onRM);
    if (st.user) fill(0, 0);
    sync();
  }
  var vitBox = $("[data-pn-vit]");
  if (vitBox && vitrine.length) montaVitrine(vitBox, vitrine);

  /* ------------------------------------------------------------------
     Fichas em destaque (seção 02) — o core.js revela os cards (data-reveal)
     ------------------------------------------------------------------ */
  var gradeEl = $("[data-pn-destaques]");
  if (gradeEl && grade.length) {
    gradeEl.innerHTML = LV.listaProdutos(grade, { linhas: 2, sizes: "(max-width: 639px) 46vw, (max-width: 1180px) 30vw, 340px" });
    $$(".pgrid__i > .spec", gradeEl).forEach(function (c) { c.setAttribute("data-reveal", ""); });
  }

  /* ------------------------------------------------------------------
     3. Categorias: nome curto + contagem real
     ------------------------------------------------------------------ */
  $$("[data-pn-cat]").forEach(function (a) {
    var id = a.getAttribute("data-pn-cat"), c = BC.categoria(id), li = a.closest("li");
    if (!c) { if (li) li.hidden = true; return; }
    var qtd = BC.contar({ categoria: id });
    if (!qtd) { if (li) li.hidden = true; return; }
    $("[data-pn-cat-nome]", a).textContent = LV.catCurto(id);
    $("[data-pn-cat-n]", a).textContent = qtd;
    a.setAttribute("aria-label", c.nome + ": " + qtd + (qtd === 1 ? " produto" : " produtos"));
  });

  /* ------------------------------------------------------------------
     4. Animações (só com GSAP e sem "reduzir movimento")
     ------------------------------------------------------------------ */
  LV.onMotion(function () {
    var doc = document.documentElement, viaVT = doc.classList.contains("via-vt");

    // Hero: faixa desce, barras verdes revelam as linhas, a imagem sobe
    var hero = $(".pn-hero");
    if (hero) {
      var tl = gsap.timeline({ defaults: { ease: "expo.out" }, delay: viaVT ? 0 : 0.08 });
      var band = $(".pn-hero__band", hero), fig = $(".pn-hero__fig", hero), img = $(".pn-hero__fig img", hero);
      if (band) tl.fromTo(band, { scaleY: 0, transformOrigin: "50% 0%" }, { scaleY: 1, duration: 1.15, ease: "expo.inOut" }, 0);
      $$(".pn-hero__l", hero).forEach(function (l, i) {
        LV.wipe(l, { tl: tl, at: 0.26 + i * 0.16, cor: l.classList.contains("pn-hero__l--green") ? "paper" : "green" });
      });
      if (img) tl.from(img, { yPercent: 12, autoAlpha: 0, duration: 1.3 }, 0.62);
      tl.from($$(".pn-hero__call, .pn-hero__ref", hero), { autoAlpha: 0, y: 8, stagger: 0.08, duration: 0.6 }, 1.2);
      var rest = $$("[data-intro]", hero).filter(function (el) { return !el.classList.contains("pn-hero__w"); });
      if (rest.length) tl.from(rest, { opacity: 0, y: 24, stagger: 0.08, duration: 1.05 }, 0.66);
      if (viaVT) tl.progress(1);

      var stl = gsap.timeline({ scrollTrigger: { trigger: hero, start: "top top", end: "bottom top", scrub: true } });
      $$(".pn-hero__l", hero).forEach(function (l, i) { stl.to(l, { xPercent: i % 2 ? 4 : -5, ease: "none" }, 0); });
      if (fig) stl.to(fig, { yPercent: -10, ease: "none" }, 0);
      if (band) stl.to(band, { xPercent: 7, ease: "none" }, 0);
    }

    // Painel: os módulos "acendem" com um corte diagonal, na ordem 01 → 07
    var tiles = $$(".pn-t");
    if (tiles.length) {
      var FECHADO = "polygon(0% 0%, 0% 0%, -14% 100%, -14% 100%)";
      var ABERTO = "polygon(0% 0%, 114% 0%, 100% 100%, -14% 100%)";
      gsap.set(tiles, { clipPath: FECHADO });
      var abre = function (lote) {
        lote = lote.filter(function (t) { return !t._pnOn; });
        if (!lote.length) return;
        lote.forEach(function (t) { t._pnOn = true; });
        var tl2 = gsap.timeline();
        tl2.to(lote, { clipPath: ABERTO, duration: 1.05, ease: "expo.inOut", stagger: 0.11, clearProps: "clipPath" }, 0);
        lote.forEach(function (t, i) {
          var at = 0.42 + i * 0.11;
          var tab = $(".pn-t__tab", t);
          if (tab) tl2.from(tab, { xPercent: -104, duration: 0.9, ease: "expo.out" }, at);
          var ticks = $(".pn-ruler__ticks", t);
          if (ticks) tl2.from(ticks, { scaleX: 0, duration: 1.2, ease: "expo.inOut" }, at + 0.1);
          var lbl = $(".pn-ruler__lbl", t);
          if (lbl) tl2.from(lbl, { autoAlpha: 0, y: 6, duration: 0.5 }, at + 0.9);
          var band2 = $(".pn-vit__band, .pn-sist__band", t);
          if (band2) tl2.from(band2, { scaleY: 0, transformOrigin: "50% 0%", duration: 1, ease: "expo.inOut" }, at);
          var ficha = $(".pn-fich__vis .ficha", t);
          if (ficha) tl2.from(ficha, { yPercent: 70, rotation: 6, autoAlpha: 0, duration: 1.1, ease: "expo.out" }, at + 0.25);
        });
      };
      ScrollTrigger.batch(tiles, { start: "top 90%", end: "max", once: true, batchMax: 7, onEnter: abre });
      // Foco do teclado num módulo ainda apagado: acende na hora
      $(".pn-grid").addEventListener("focusin", function (e) {
        var t = e.target.closest(".pn-t");
        if (t && !t._pnOn) { t._pnOn = true; gsap.set(t, { clearProps: "clipPath" }); }
      });
      // Marcas de registro "fecham" no canto quando o painel entra
      gsap.from($$(".pn-mark"), { scale: 2.2, autoAlpha: 0, duration: 0.9, ease: "expo.out", stagger: 0.06, scrollTrigger: LV.st(".pn-panel", "top 85%") });
    }

    // Imagem do BC System: leve paralaxe dentro do módulo
    var sImg = $(".pn-sist__vis img");
    if (sImg) gsap.fromTo(sImg, { yPercent: 8 }, { yPercent: -4, ease: "none", scrollTrigger: { trigger: sImg.parentElement, start: "top bottom", end: "bottom top", scrub: true } });
  });
})();
