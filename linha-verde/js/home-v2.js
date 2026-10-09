/* =====================================================================
   Balanças.com — "Linha Verde" — Página inicial · VARIAÇÃO 2 "Capítulos"
   js/home-v2.js — só index-2.html. Carregado depois do core.js e antes
   dos scripts do CDN (todos com "defer").
   1. Vitrine: escolhe as fichas em window.BC (BC.destaques), monta com
      LV.cardProduto (no lugar das fichas de reserva do HTML), botões ‹ ›,
      barra de progresso e foco por teclado.
   2. Sumário: no mouse, a imagem de cada capítulo segue o cursor
      (sem GSAP, com requestAnimationFrame); no toque, miniaturas fixas.
      Monogramas da equipe (capítulo 04) a partir de BC.empresa.equipe.
   3. Movimento (LV.onMotion): abertura da capa, linhas do sumário,
      fichas da vitrine e contracapa. Sem GSAP ou com "reduzir
      movimento", tudo aparece direto.
   4. Fase de escolha: tira o seletor de variação (js/preview.js) da
      frente da capa.
   ===================================================================== */
(function () {
  "use strict";

  var BC = window.BC, LV = window.LV;
  if (!LV) return;
  var $ = LV.$, $$ = LV.$$;
  var doc = document.documentElement;
  var reduceMQ = window.matchMedia("(prefers-reduced-motion: reduce)");
  var fineMQ = window.matchMedia("(hover: hover) and (pointer: fine)");
  var wideMQ = window.matchMedia("(min-width: 861px)");

  function naTela(el) {
    var r = el.getBoundingClientRect();
    return r.bottom > 0 && r.top < window.innerHeight;
  }

  /* ------------------------------------------------------------------
     1. VITRINE
     ------------------------------------------------------------------ */
  var strip = $("[data-strip]");

  // Alterna as categorias (balanças → automação → informática…) e evita
  // repetir subcategoria, para a amostra mostrar a variedade da loja.
  function escolher(n) {
    var fonte = BC.destaques();
    if (fonte.length < n) fonte = fonte.concat(BC.produtos.filter(function (p) { return fonte.indexOf(p) < 0; }));
    var grupos = {}, ordem = [];
    (BC.categorias || []).forEach(function (c) { grupos[c.id] = []; ordem.push(c.id); });
    fonte.forEach(function (p) {
      if (!grupos[p.categoria]) { grupos[p.categoria] = []; ordem.push(p.categoria); }
      grupos[p.categoria].push(p);
    });
    var out = [], subs = {}, guarda = 0;
    while (out.length < n && guarda++ < 40) {
      var pegou = false;
      ordem.forEach(function (c) {
        var g = grupos[c];
        if (out.length >= n || !g.length) return;
        var i = 0;
        for (var k = 0; k < g.length; k++) { if (!subs[g[k].subcategoria]) { i = k; break; } }
        var p = g.splice(i, 1)[0];
        subs[p.subcategoria] = true; out.push(p); pegou = true;
      });
      if (!pegou) break;
    }
    return out;
  }

  function montarVitrine() {
    if (!strip || !BC || !LV.cardProduto) return;
    var lista = escolher(6);
    if (!lista.length) return;   // sem dados: ficam as fichas de reserva do HTML
    var mais = $(".hv2-strip__more", strip);
    var html = lista.map(function (p, i) {
      return '<li class="hv2-strip__i" data-id="' + LV.esc(p.id) + '">' +
        LV.cardProduto(p, { variante: "compacta", n: i + 1, sizes: "(max-width: 640px) 74vw, 320px" }) + "</li>";
    }).join("");
    // Sem o snap durante a troca: senão o navegador "re-encaixa" numa ficha qualquer
    // e a faixa já abre rolada.
    strip.style.scrollSnapType = "none";
    $$("[data-fallback]", strip).forEach(function (li) { li.parentNode.removeChild(li); });
    if (mais) mais.insertAdjacentHTML("beforebegin", html);
    else strip.insertAdjacentHTML("beforeend", html);
    strip.scrollLeft = 0;
    requestAnimationFrame(function () { strip.scrollLeft = 0; strip.style.scrollSnapType = ""; });
    // "32 kg", "203 dpi": número e unidade não se separam no resumo
    $$(".spec__sub", strip).forEach(function (el) {
      el.textContent = el.textContent.replace(/(\d) (kg|g|mm\/s|mm|dpi|horas|scans\/s)(?=[\s,.;)]|$)/g, "$1\u00a0$2");
    });
  }

  function controlesVitrine() {
    if (!strip) return;
    var box = $("[data-strip-btns]"), prev = $("[data-strip-prev]"), next = $("[data-strip-next]"), bar = $("[data-strip-bar]");
    function passo() {
      var it = $(".hv2-strip__i", strip);
      var gap = parseFloat(getComputedStyle(strip).columnGap) || 16;
      var w = it ? it.getBoundingClientRect().width + gap : strip.clientWidth * 0.8;
      return Math.max(1, Math.floor((strip.clientWidth * 0.86) / w)) * w;
    }
    function ir(dir) { strip.scrollBy({ left: dir * passo(), behavior: reduceMQ.matches ? "auto" : "smooth" }); }
    var raf = 0;
    function atualiza() {
      raf = 0;
      var max = strip.scrollWidth - strip.clientWidth, x = strip.scrollLeft;
      if (box) box.hidden = max <= 4;
      if (prev) prev.disabled = x <= 4;
      if (next) next.disabled = x >= max - 4;
      if (bar) bar.style.setProperty("--p", max > 4 ? Math.min(1, (x + strip.clientWidth) / strip.scrollWidth).toFixed(4) : "1");
    }
    function agenda() { if (!raf) raf = requestAnimationFrame(atualiza); }
    if (prev) prev.addEventListener("click", function () { ir(-1); });
    if (next) next.addEventListener("click", function () { ir(1); });
    strip.addEventListener("scroll", agenda, { passive: true });
    window.addEventListener("resize", agenda);
    window.addEventListener("load", agenda);
    // Teclado: a ficha que recebe o foco entra inteira na faixa (o Chrome não rola sozinho aqui)
    strip.addEventListener("focusin", function (e) {
      var li = e.target.closest ? e.target.closest(".hv2-strip__i") : null;
      if (!li) return;
      var r = li.getBoundingClientRect(), s = strip.getBoundingClientRect();
      var pad = parseFloat(getComputedStyle(strip).scrollPaddingLeft) || 0, dx = 0;
      if (r.left < s.left + pad) dx = r.left - (s.left + pad);
      else if (r.right > s.right - pad) dx = r.right - (s.right - pad);
      if (Math.abs(dx) > 1) strip.scrollBy({ left: dx, behavior: reduceMQ.matches ? "auto" : "smooth" });
    });
    atualiza();
  }

  /* ------------------------------------------------------------------
     2. SUMÁRIO — monogramas da equipe e imagem que segue o cursor
     ------------------------------------------------------------------ */
  function monogramas() {
    var box = $("[data-hv2-team]"), eq = BC && BC.empresa && BC.empresa.equipe;
    if (!box || !eq || !eq.length) return;
    box.innerHTML = eq.slice(0, 5).map(function (p) {
      var n = String(p.nome || "").split(/\s+/).filter(Boolean);
      return "<i>" + LV.esc(((n[0] || "").charAt(0) + (n.length > 1 ? n[n.length - 1].charAt(0) : "")).toUpperCase()) + "</i>";
    }).join("");
  }

  function seguidor() {
    var list = $("[data-peek-list]");
    if (!list) return;
    var rows = $$(".hv2-row__a", list);
    var peek = null, figs = [], ativo = false, cur = -1, z = 1, listaNaTela = false;
    var x = 0, y = 0, tx = 0, ty = 0, rot = 0, mx = -1, my = -1, raf = 0, visivel = false;

    function montar() {
      if (peek) return;
      peek = document.createElement("div");
      peek.className = "hv2-peek";
      peek.setAttribute("aria-hidden", "true");
      rows.forEach(function (a) {
        var th = $(".hv2-row__th", a);
        if (!th) { figs.push(null); return; }
        var f = th.cloneNode(true);
        f.classList.remove("hv2-row__th");
        f.removeAttribute("aria-hidden");
        $$("img", f).forEach(function (im) { im.removeAttribute("loading"); });
        peek.appendChild(f); figs.push(f);
      });
      document.body.appendChild(peek);
    }
    // A imagem fica ACIMA da linha em foco (o X segue o cursor): o título continua legível.
    // Sem espaço em cima (perto do cabeçalho), passa para baixo da linha.
    function alvo() {
      var w = peek.offsetWidth, h = peek.offsetHeight, top = LV.hdr() + 12;
      var rr = cur > -1 ? rows[cur].getBoundingClientRect() : null;
      tx = Math.min(Math.max(mx - w * 0.5, 16), window.innerWidth - w - 16);
      ty = rr ? Math.min(my - h - 26, rr.top - h - 8) : my - h - 26;
      if (ty < top) ty = Math.min(rr ? Math.max(my + 30, rr.bottom + 8) : my + 30, window.innerHeight - h - 12);
    }
    function loop() {
      raf = 0;
      if (!visivel) return;
      var dx = tx - x;
      x += dx * 0.17; y += (ty - y) * 0.17;
      rot += (Math.max(-7, Math.min(7, dx * 0.05)) - rot) * 0.2;
      peek.style.transform = "translate3d(" + x.toFixed(1) + "px," + y.toFixed(1) + "px,0) rotate(" + rot.toFixed(2) + "deg)";
      if (Math.abs(dx) > 0.3 || Math.abs(ty - y) > 0.3 || Math.abs(rot) > 0.05) raf = requestAnimationFrame(loop);
    }
    function mexe() { if (!raf) raf = requestAnimationFrame(loop); }
    function mostra(i) {
      if (!ativo || !peek || !figs[i]) return;
      if (cur !== i) {
        if (cur > -1 && figs[cur]) figs[cur].classList.remove("is-on");
        figs[i].style.zIndex = ++z;
        figs[i].classList.add("is-on");
        cur = i;
      }
      alvo();
      if (!visivel) { x = tx; y = ty; rot = 0; visivel = true; peek.classList.add("is-on"); }
      mexe();
    }
    function esconde() {
      if (!peek) return;
      visivel = false; peek.classList.remove("is-on");
      if (cur > -1 && figs[cur]) figs[cur].classList.remove("is-on");
      cur = -1;
    }
    function linhaSobCursor() {
      if (mx < 0) return -1;
      var el = document.elementFromPoint(mx, my);
      var a = el && el.closest ? el.closest(".hv2-row__a") : null;
      return a ? rows.indexOf(a) : -1;
    }

    rows.forEach(function (a, i) { a.addEventListener("mouseenter", function () { mostra(i); }); });
    list.addEventListener("mouseleave", esconde);
    window.addEventListener("mousemove", function (e) {
      mx = e.clientX; my = e.clientY;
      if (visivel) { alvo(); mexe(); }
    }, { passive: true });
    // Rolando com o mouse parado: atualiza a linha sob o cursor (só com o sumário na tela)
    window.addEventListener("scroll", function () {
      if (!ativo || !listaNaTela) return;
      var i = linhaSobCursor();
      if (i > -1) mostra(i); else if (visivel) esconde();
    }, { passive: true });
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (en) {
        listaNaTela = en[en.length - 1].isIntersecting;
        if (!listaNaTela && visivel) esconde();
      }).observe(list);
    } else listaNaTela = true;

    function avalia() {
      var pode = fineMQ.matches && !reduceMQ.matches && wideMQ.matches;
      if (pode === ativo) return;
      ativo = pode;
      doc.classList.toggle("hv2-follow", pode);
      if (!pode) esconde();
      else if (!peek) {
        // monta (e carrega as imagens) quando o sumário se aproxima da tela
        if ("IntersectionObserver" in window) {
          var io = new IntersectionObserver(function (en) {
            if (en.some(function (e) { return e.isIntersecting; })) { io.disconnect(); montar(); }
          }, { rootMargin: "600px 0px" });
          io.observe(list);
        } else montar();
      }
      LV.refresh();
    }
    [fineMQ, reduceMQ, wideMQ].forEach(function (mq) {
      if (mq.addEventListener) mq.addEventListener("change", avalia); else if (mq.addListener) mq.addListener(avalia);
    });
    avalia();
  }

  /* ------------------------------------------------------------------
     3. MOVIMENTO (dentro do gsap.matchMedia "sem reduzir movimento")
     ------------------------------------------------------------------ */
  LV.onMotion(function () {
    var viaVT = doc.classList.contains("via-vt");
    // CDN lento: a rede de segurança do <head> já mostrou a página ("lv-tarde").
    // O que já está na tela não some para tocar a entrada de novo.
    var tarde = doc.classList.contains("lv-tarde");

    // 3.1 Capa: linha de abertura, palavras cortadas pela barra verde, placa, cota e texto
    var hero = $(".hv2-hero");
    if (hero) {
      var tl = gsap.timeline({ defaults: { ease: "expo.out" }, delay: viaVT ? 0 : 0.08 });
      var topo = [$(".hv2-title__pre", hero), $(".hv2-hero__status", hero)].filter(Boolean);
      tl.from(topo, { opacity: 0, y: 14, duration: 0.9, stagger: 0.06 }, 0);
      $$(".hv2-title .w", hero).forEach(function (w, i) {
        LV.wipe(w, { tl: tl, at: 0.16 + i * 0.075, cor: w.classList.contains("w--green") ? "paper" : "green" });
      });
      var slab = $(".hv2-slab", hero), img = slab && $("img", slab);
      if (slab) {
        // A placa abre da esquerda para a direita com a borda inclinada a −12°, seja qual
        // for a largura dela (no desktop ela se estica até a margem). A janela vai de
        // −50% a 170% da altura para não cortar a balança, que sai da placa.
        var sw = slab.offsetWidth || 1, sh = slab.offsetHeight || 1;
        var dx = (sh * 2.2 * 0.2126) / sw * 100;
        var f = function (n) { return n.toFixed(2) + "%"; };
        tl.fromTo(slab,
          { clipPath: "polygon(-2% -50%, -2% -50%, " + f(-2 - dx) + " 170%, " + f(-2 - dx) + " 170%)" },
          { clipPath: "polygon(-2% -50%, " + f(104 + dx) + " -50%, 104% 170%, " + f(-2 - dx) + " 170%)", duration: 0.95, ease: "expo.inOut", clearProps: "clipPath" }, 0.42);
        if (img) tl.from(img, { yPercent: 38, rotation: -4, opacity: 0, duration: 1.3 }, 0.8);
      }
      var rule = $(".hv2-rule", hero);
      if (rule) tl.fromTo(rule, { scaleX: 0, transformOrigin: "0% 50%" }, { scaleX: 1, duration: 1.3, ease: "expo.inOut", clearProps: "transform" }, 0.55);
      var resto = [$(".hv2-hero__lead", hero), $(".hv2-hero__ctas", hero), $(".phero__strip", hero)].filter(Boolean);
      tl.from(resto, { opacity: 0, y: 24, duration: 1.05, stagger: 0.09 }, 0.82);
      if (viaVT || tarde) tl.progress(1);
      else {
        // Na volta pela transição diagonal, o "pagereveal" pode chegar DEPOIS deste gancho
        // (o DOMContentLoaded veio antes): a capa também tem de aparecer pronta.
        window.addEventListener("pagereveal", function (e) { if (e.viewTransition) tl.progress(1); }, { once: true });
      }

      // Ao rolar: o título sobe mais devagar e a balança "flutua" para fora da placa
      var big = $(".hv2-title__big", hero);
      var stl = gsap.timeline({ scrollTrigger: { trigger: hero, start: "top top", end: "bottom top", scrub: true } });
      if (big) stl.to(big, { yPercent: 14, ease: "none" }, 0);
      if (img) stl.to(img, { yPercent: -16, ease: "none" }, 0);
    }

    // 3.2 Sumário: o fio de cada linha se desenha e o conteúdo sobe
    var rows = $$(".hv2-row__a");
    if (rows.length) {
      var partes = function (a) {
        return $$(".hv2-row__n, .hv2-row__t, .hv2-row__d, .hv2-row__th, .hv2-row__go", a)
          .filter(function (el) { return el.offsetParent !== null; });
      };
      rows.forEach(function (a) {
        if (tarde && naTela(a)) { a._hv2 = true; return; }   // já está à vista: fica como está
        var r = $(".hv2-row__rule", a);
        if (r) gsap.set(r, { scaleX: 0 });
        gsap.set(partes(a), { opacity: 0, y: 26 });
        a._hv2 = false;
      });
      var abre = function (a, atraso) {
        if (a._hv2) return; a._hv2 = true;
        var r = $(".hv2-row__rule", a);
        var t = gsap.timeline({ delay: atraso || 0 });
        if (r) t.to(r, { scaleX: 1, duration: 1.1, ease: "expo.inOut" }, 0);
        t.to(partes(a), { opacity: 1, y: 0, duration: 0.95, ease: "expo.out", stagger: 0.05, clearProps: "transform" }, 0.22);
      };
      ScrollTrigger.batch(rows, {
        start: "top 92%", once: true,
        onEnter: function (b) {
          b.forEach(function (a, i) { abre(a, a.getBoundingClientRect().bottom < 0 ? 0 : i * 0.09); });
        }
      });
      rows.forEach(function (a) { a.addEventListener("focus", function () { abre(a, 0); }); });
    }

    // 3.3 Vitrine: as fichas entram da direita, uma a uma. Anima o FILHO de cada
    // <li> (o <li> é o ponto de encaixe do scroll-snap; mexer nele desloca a faixa).
    if (strip && !(tarde && naTela(strip))) {
      var itens = $$(".hv2-strip__i > *", strip);
      if (itens.length) {
        var feito = false;
        gsap.set(itens, { opacity: 0, x: 80 });
        var entra = function () {
          if (feito) return; feito = true;
          gsap.to(itens, { opacity: 1, x: 0, duration: 1.15, ease: "expo.out", stagger: 0.075, clearProps: "transform,opacity" });
        };
        ScrollTrigger.create({ trigger: strip, start: "top 86%", once: true, onEnter: entra });
        strip.addEventListener("focusin", entra);
      }
    }

    // 3.4 Contracapa: a barra preta revela o 5,0 e as estrelas acendem
    var score = $(".hv2-score");
    if (score && !(tarde && naTela(score))) {
      var n = $(".hv2-score__n", score);
      var stl2 = gsap.timeline({ scrollTrigger: LV.st(score, "top 82%") });
      if (n) LV.wipe(n, { tl: stl2, at: 0, cor: "ink" });
      var stars = $$(".hv2-score__stars .i", score);
      if (stars.length) stl2.from(stars, { scale: 0, rotation: -40, transformOrigin: "50% 55%", duration: 0.7, ease: "back.out(2.2)", stagger: 0.08 }, 0.5);
      var txt = $$(".hv2-score__txt, .hv2-score__sub", score);
      if (txt.length) stl2.from(txt, { opacity: 0, y: 18, duration: 0.9, ease: "expo.out", stagger: 0.08 }, 0.7);
      // Teclado: o link da nota recebe foco já visível
      score.addEventListener("focusin", function () { stl2.progress(1); });
    }
  });

  /* ------------------------------------------------------------------
     4. FASE DE ESCOLHA (some junto com o js/preview.js na publicação):
     o seletor "Variação 1 · 2 · 3", fixo no canto inferior esquerdo, sai
     de cena enquanto a capa ocupa o pé da tela (como os botões flutuantes).
     ------------------------------------------------------------------ */
  function seletorVariacao() {
    var hero = $(".hv2-hero");
    if (!hero || !document.querySelector('script[src*="preview.js"]')) return;
    var raf = 0;
    function avalia() {
      raf = 0;
      var r = hero.getBoundingClientRect();
      // enquanto a capa ocupa boa parte da tela (no tablet em pé ela não chega ao pé da tela,
      // mas o seletor cobriria o título do sumário logo abaixo)
      doc.classList.toggle("hv2-lvp-off", r.top < window.innerHeight && r.bottom > window.innerHeight * 0.4);
    }
    function agenda() { if (!raf) raf = requestAnimationFrame(avalia); }
    window.addEventListener("scroll", agenda, { passive: true });
    window.addEventListener("resize", agenda);
    avalia();
  }

  /* ------------------------------------------------------------------
     Início (o core já preencheu os dados; aqui só o que é desta página)
     ------------------------------------------------------------------ */
  try { montarVitrine(); } catch (e) { if (window.console) console.warn(e); }
  controlesVitrine();
  monogramas();
  seguidor();
  seletorVariacao();
})();
