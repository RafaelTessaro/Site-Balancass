/* =====================================================================
   Balanças.com — "Linha Verde" — Página inicial · VARIAÇÃO 2 "Capítulos"
   js/home-v2.js — só index-2.html. Carregado depois do core.js e antes
   dos scripts do CDN (todos com "defer").
   1. Vitrine: escolhe as fichas em window.BC (BC.destaques) e monta com
      LV.cardProduto no modelo LIMPO (foto, marca, nome, uma linha do que
      é, selo e Detalhes + WhatsApp); botões ‹ › (aria-disabled, não perdem
      o foco), setas ← → do teclado entre as fichas e barra de progresso.
   2. Capa: a faixa verde da manchete reveza 3 produtos (balança com
      etiqueta, impressora de etiquetas e kit PDV), com legenda e botão de
      pausa. Pausa sozinha no mouse/foco, fora da tela e com a aba oculta;
      dá 2 voltas e para na balança. Com "reduzir movimento": produto fixo.
   3. Movimento (LV.onMotion): abertura da capa, linhas do índice, fichas
      da vitrine e atendimento. Sem GSAP ou com "reduzir movimento", tudo
      aparece direto.
   4. Fase de escolha: tira o seletor de variação (js/preview.js) da
      frente da capa.
   5. Botões flutuantes: saem de cena quando o "Falar no WhatsApp" da
      capa está na tela (html.hv2-cta-vis), sem sobrepor os dois.
   ===================================================================== */
(function () {
  "use strict";

  var BC = window.BC, LV = window.LV;
  if (!LV) return;
  var $ = LV.$, $$ = LV.$$;
  var doc = document.documentElement;
  var reduceMQ = window.matchMedia("(prefers-reduced-motion: reduce)");

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

  // "Uma linha do que é": a 1ª oração do resumo ("Balança etiquetadora compacta de 32 kg.")
  function resumoCurto(s) {
    s = String(s || "").replace(/\s+/g, " ").trim().replace(/\.$/, "");
    var c = s.split(/,\s|;\s|\s[–—]\s/)[0];
    if (c.length > 64) { var cut = c.slice(0, 64); c = cut.slice(0, cut.lastIndexOf(" ")) + "…"; }
    // "32 kg", "203 dpi": número e unidade não se separam
    c = c.replace(/(\d) (kg|g|mm\/s|mm|dpi|horas|scans\/s)(?=[\s,.;)]|$)/g, "$1 $2");
    return c + (/…$/.test(c) ? "" : ".");
  }

  // Ficha no modelo limpo (o mesmo das fichas de reserva do HTML)
  function fichaLimpa(p, i) {
    var t = document.createElement("template");
    t.innerHTML = LV.cardProduto(p, { variante: "compacta", classe: "hv2-card", n: i + 1, linhas: 0, sizes: "(max-width: 640px) 70vw, 300px" });
    var card = t.content.firstElementChild;
    if (!card) return "";
    $$(".spec__head, .spec__table, .spec__price:not(.has-preco)", card).forEach(function (el) { el.parentNode.removeChild(el); });
    var sub = $(".spec__sub", card);
    if (sub) sub.textContent = resumoCurto(p.resumo);
    return '<li class="hv2-strip__i" data-id="' + LV.esc(p.id) + '">' + card.outerHTML + "</li>";
  }

  function montarVitrine() {
    if (!strip || !BC || !LV.cardProduto) return;
    var lista = escolher(6);
    if (!lista.length) return;   // sem dados: ficam as fichas de reserva do HTML
    var mais = $(".hv2-strip__more", strip);
    var html = lista.map(fichaLimpa).join("");
    if (!html) return;
    // Sem o snap durante a troca: senão o navegador "re-encaixa" numa ficha qualquer
    // e a faixa já abre rolada.
    strip.style.scrollSnapType = "none";
    $$("[data-fallback]", strip).forEach(function (li) { li.parentNode.removeChild(li); });
    if (mais) mais.insertAdjacentHTML("beforebegin", html);
    else strip.insertAdjacentHTML("beforeend", html);
    strip.scrollLeft = 0;
    requestAnimationFrame(function () { strip.scrollLeft = 0; strip.style.scrollSnapType = ""; });
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
    // aria-disabled (e não "disabled"): o botão continua com o foco do teclado no fim da faixa
    function trava(b, sim) { if (b) b.setAttribute("aria-disabled", sim ? "true" : "false"); }
    var raf = 0;
    function atualiza() {
      raf = 0;
      var max = strip.scrollWidth - strip.clientWidth, x = strip.scrollLeft;
      if (box) box.hidden = max <= 4;
      trava(prev, x <= 4);
      trava(next, x >= max - 4);
      if (bar) bar.style.setProperty("--p", max > 4 ? Math.min(1, (x + strip.clientWidth) / strip.scrollWidth).toFixed(4) : "1");
    }
    function agenda() { if (!raf) raf = requestAnimationFrame(atualiza); }
    if (prev) prev.addEventListener("click", function () { if (prev.getAttribute("aria-disabled") !== "true") ir(-1); });
    if (next) next.addEventListener("click", function () { if (next.getAttribute("aria-disabled") !== "true") ir(1); });
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
    // Setas ← → levam ao mesmo botão da ficha vizinha (Detalhes → Detalhes, WhatsApp → WhatsApp)
    strip.addEventListener("keydown", function (e) {
      if ((e.key !== "ArrowRight" && e.key !== "ArrowLeft") || e.altKey || e.ctrlKey || e.metaKey) return;
      var li = e.target.closest ? e.target.closest(".hv2-strip__i") : null;
      if (!li) return;
      var itens = $$(".hv2-strip__i", strip), k = itens.indexOf(li) + (e.key === "ArrowRight" ? 1 : -1);
      if (k < 0 || k >= itens.length) return;
      var sel = e.target.classList.contains("btn--green") ? ".btn--green" : ".btn--line";
      var alvo = $(sel, itens[k]) || $("a, button", itens[k]);
      if (alvo) { e.preventDefault(); alvo.focus(); }
    });
    atualiza();
  }

  /* ------------------------------------------------------------------
     2. CAPA — a faixa verde reveza 3 produtos
     ------------------------------------------------------------------ */
  function capa() {
    var slab = $("[data-slab]"), ps = $("[data-slab-ps]"), btn = $("[data-cap-btn]"), link = $("[data-cap-a]");
    var cn = $("[data-cap-n]"), ct = $("[data-cap-t]"), cm = $("[data-cap-m]"), hero = $(".hv2-hero");
    if (!slab || !ps || !link || !BC) return;
    var p1 = BC.porId("toledo-prix-4-uno"), p2 = BC.porId("elgin-l42-pro");
    var itens = [{ el: $(".hv2-slab__p", ps), tipo: "Balança com etiqueta", nome: p1 ? p1.marca + " " + p1.nome : "Toledo Prix 4 Uno", href: "produtos.html#p=toledo-prix-4-uno" }];
    if (!itens[0].el || reduceMQ.matches) return;   // movimento reduzido: a balança fica fixa

    function novo(cls, img) {
      var s = document.createElement("span");
      s.className = "hv2-slab__p hv2-slab__p--" + cls;
      s.innerHTML = img;
      return s;
    }
    var extra = [];
    if (p2) extra.push({
      el: novo("l42", LV.imgProduto(p2, { alt: "", eager: true, sizes: "(max-width: 860px) 46vw, 24vw" })),
      tipo: "Impressora de etiquetas", nome: p2.marca + " " + p2.nome, href: "produtos.html#p=" + encodeURIComponent(p2.id)
    });
    extra.push({
      el: novo("pdv", '<img src="../compartilhado/img/fotos/prix-6-pdv.webp" width="1044" height="1098" alt="" decoding="async">'),
      tipo: "Kit PDV", nome: "Balança, gaveta e impressora", href: "produtos.html#kits"
    });

    var n = 0, i = 0, timer = 0, trocas = 0, MAX = 0;
    var pausado = false, emCima = false, foco = false, visivel = true;

    function pad(k) { return (k < 10 ? "0" : "") + k; }
    function legenda(k) {
      var it = itens[k];
      if (cn) cn.textContent = pad(k + 1) + "/" + pad(n);
      if (ct) ct.textContent = it.tipo;
      if (cm) cm.textContent = it.nome;
      link.href = it.href;
      link.setAttribute("aria-label", it.tipo + ": " + it.nome + " (ver no catálogo)");
    }
    function pode() { return !pausado && !emCima && !foco && visivel && !document.hidden && trocas < MAX; }
    function agenda() { clearTimeout(timer); timer = 0; if (pode()) timer = setTimeout(proximo, 4200); }
    function botao() {
      if (!btn) return;
      var parado = pausado || trocas >= MAX;
      btn.setAttribute("aria-pressed", parado ? "true" : "false");
    }
    function ir(k) {
      var de = itens[i], para = itens[k];
      if (de === para) return;
      slab.classList.remove("is-swap"); void slab.offsetWidth; slab.classList.add("is-swap");
      de.el.classList.remove("is-on"); de.el.classList.add("is-out");
      para.el.classList.remove("is-out"); para.el.classList.add("is-on");
      setTimeout(function () { de.el.classList.remove("is-out"); }, 700);
      i = k; legenda(k);
    }
    function proximo() { trocas++; ir((i + 1) % n); botao(); agenda(); }

    function ligar() {
      extra.forEach(function (it) { ps.appendChild(it.el); itens.push(it); });
      n = itens.length; MAX = n * 2;   // duas voltas e para de novo na balança
      legenda(0);
      if (btn) {
        btn.hidden = false;
        btn.addEventListener("click", function () {
          if (pausado || trocas >= MAX) { pausado = false; if (trocas >= MAX) trocas = 0; proximo(); }
          else { pausado = true; clearTimeout(timer); botao(); }
        });
      }
      botao();
      [$(".hv2-title", hero), $(".hv2-cap", hero)].forEach(function (el) {
        if (!el) return;
        el.addEventListener("mouseenter", function () { emCima = true; clearTimeout(timer); });
        el.addEventListener("mouseleave", function () { emCima = false; agenda(); });
      });
      var cap = $(".hv2-cap", hero);
      if (cap) {
        cap.addEventListener("focusin", function () { foco = true; clearTimeout(timer); });
        cap.addEventListener("focusout", function () { foco = false; agenda(); });
      }
      document.addEventListener("visibilitychange", agenda);
      if ("IntersectionObserver" in window && hero) {
        new IntersectionObserver(function (en) { visivel = en[en.length - 1].isIntersecting; agenda(); }, { threshold: 0.25 }).observe(hero);
      }
      agenda();
    }
    // Só depois que a página carregou (a 1ª imagem é a do LCP) e com as outras já decodificadas
    function preparar() {
      var imgs = extra.map(function (it) { return $("img", it.el); });
      Promise.all(imgs.map(function (im) {
        return im.decode ? im.decode().catch(function () {}) : Promise.resolve();
      })).then(function () { setTimeout(ligar, 600); });
    }
    if (document.readyState === "complete") setTimeout(preparar, 900);
    else window.addEventListener("load", function () { setTimeout(preparar, 900); }, { once: true });
  }

  /* ------------------------------------------------------------------
     3. MOVIMENTO (dentro do gsap.matchMedia "sem reduzir movimento")
     ------------------------------------------------------------------ */
  LV.onMotion(function () {
    var viaVT = doc.classList.contains("via-vt");
    // CDN lento: a rede de segurança do <head> já mostrou a página ("lv-tarde").
    // O que já está na tela não some para tocar a entrada de novo.
    var tarde = doc.classList.contains("lv-tarde");

    // 3.1 Capa: linha de abertura, palavras cortadas pela barra verde, faixa, produto, cota e texto
    var hero = $(".hv2-hero");
    if (hero) {
      var tl = gsap.timeline({ defaults: { ease: "expo.out" }, delay: viaVT ? 0 : 0.08 });
      var topo = [$(".hv2-title__pre", hero), $(".hv2-hero__status", hero)].filter(Boolean);
      tl.from(topo, { opacity: 0, y: 14, duration: 0.9, stagger: 0.06 }, 0);
      $$(".hv2-title .w", hero).forEach(function (w, i) {
        LV.wipe(w, { tl: tl, at: 0.16 + i * 0.075, cor: w.classList.contains("w--green") ? "paper" : "green" });
      });
      // A faixa abre da esquerda para a direita com a borda inclinada a −12° (o recorte
      // é aplicado antes da inclinação); o produto sobe para dentro dela.
      var band = $(".hv2-slab__band", hero), ps = $(".hv2-slab__ps", hero);
      if (band) tl.fromTo(band, { clipPath: "inset(0% 100% 0% 0%)" }, { clipPath: "inset(0% 0% 0% 0%)", duration: 0.95, ease: "expo.inOut", clearProps: "clipPath" }, 0.42);
      if (ps) tl.fromTo(ps, { yPercent: 26, opacity: 0 }, { yPercent: 0, opacity: 1, duration: 1.3, clearProps: "opacity" }, 0.8);
      var rule = $(".hv2-rule", hero);
      if (rule) tl.fromTo(rule, { scaleX: 0, transformOrigin: "0% 50%" }, { scaleX: 1, duration: 1.3, ease: "expo.inOut", clearProps: "transform" }, 0.55);
      var resto = [$(".hv2-cap", hero), $(".hv2-hero__lead", hero), $(".phero__strip", hero)].filter(Boolean);
      tl.from(resto, { opacity: 0, y: 24, duration: 1.05, stagger: 0.09 }, 0.82);
      if (viaVT || tarde) tl.progress(1);
      else {
        // Na volta pela transição diagonal, o "pagereveal" pode chegar DEPOIS deste gancho
        // (o DOMContentLoaded veio antes): a capa também tem de aparecer pronta.
        window.addEventListener("pagereveal", function (e) { if (e.viewTransition) tl.progress(1); }, { once: true });
      }

      // Ao rolar: o título sobe mais devagar e o produto "flutua" para fora da faixa
      var big = $(".hv2-title__big", hero);
      var stl = gsap.timeline({ scrollTrigger: { trigger: hero, start: "top top", end: "bottom top", scrub: true, invalidateOnRefresh: true } });
      if (big) stl.to(big, { yPercent: 14, ease: "none" }, 0);
      if (ps) stl.to(ps, { y: function () { return -ps.offsetHeight * 0.14; }, ease: "none" }, 0);
    }

    // 3.2 Índice: o fio de cada linha se desenha e o conteúdo sobe
    var rows = $$(".hv2-row__a");
    if (rows.length) {
      var partes = function (a) {
        return $$(".hv2-row__n, .hv2-row__t, .hv2-row__th, .hv2-row__d, .hv2-row__go", a)
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
        t.to(partes(a), { opacity: 1, y: 0, duration: 0.95, ease: "expo.out", stagger: 0.05, clearProps: "transform,opacity" }, 0.22);
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

    // 3.4 Atendimento: a barra preta revela o 5,0 (a nota é fixa, sem contador),
    // as estrelas acendem e os logos dos clientes entram em fila
    var score = $(".hv2-score");
    if (score && !(tarde && naTela(score))) {
      var nota = $(".hv2-score__n", score);
      var stl2 = gsap.timeline({ scrollTrigger: LV.st(score, "top 82%") });
      if (nota) LV.wipe(nota, { tl: stl2, at: 0, cor: "ink" });
      var stars = $$(".hv2-score__stars .i", score);
      if (stars.length) stl2.from(stars, { scale: 0, rotation: -40, transformOrigin: "50% 55%", duration: 0.7, ease: "back.out(2.2)", stagger: 0.08 }, 0.5);
      var txt = $$(".hv2-score__txt, .hv2-score__sub, .hv2-clients__k", score);
      if (txt.length) stl2.from(txt, { opacity: 0, y: 18, duration: 0.9, ease: "expo.out", stagger: 0.08 }, 0.7);
      var logos = $$(".hv2-clients__i", score);
      if (logos.length) stl2.from(logos, { opacity: 0, y: 12, duration: 0.8, ease: "expo.out", stagger: 0.05, clearProps: "transform,opacity" }, 0.95);
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
      // mas o seletor cobriria o título do índice logo abaixo)
      doc.classList.toggle("hv2-lvp-off", r.top < window.innerHeight && r.bottom > window.innerHeight * 0.4);
    }
    function agenda() { if (!raf) raf = requestAnimationFrame(avalia); }
    window.addEventListener("scroll", agenda, { passive: true });
    window.addEventListener("resize", agenda);
    avalia();
  }

  /* ------------------------------------------------------------------
     5. BOTÕES FLUTUANTES × BOTÕES DA CAPA
     A capa NÃO tem data-float-off (só os botões dela têm): em celular
     baixo e tablet em pé, enquanto os botões da capa estão abaixo da
     dobra, o WhatsApp flutuante é o contato da 1ª tela. O core só tira
     o flutuante quando os botões passam de 88% da altura da tela; entre
     88% e o pé da tela os dois apareciam juntos (768×1024) ou um cobria
     o outro (375×667). Aqui
     o flutuante sai assim que o "Falar no WhatsApp" da capa aparece
     quase inteiro (≥ 55% visível), e volta quando ele sai da tela.
     ------------------------------------------------------------------ */
  function flutuanteNaCapa() {
    var wa = $(".hv2-hero__ctas [data-wa]");
    if (!wa || !$("[data-float]") || !("IntersectionObserver" in window)) return;
    new IntersectionObserver(function (en) {
      var e = en[en.length - 1];
      doc.classList.toggle("hv2-cta-vis", e.isIntersecting && e.intersectionRatio >= 0.55);
    }, { threshold: [0, 0.3, 0.55, 0.8, 1] }).observe(wa);
  }

  /* ------------------------------------------------------------------
     Início (o core já preencheu os dados; aqui só o que é desta página)
     ------------------------------------------------------------------ */
  try { montarVitrine(); } catch (e) { if (window.console) console.warn(e); }
  controlesVitrine();
  try { capa(); } catch (e) { if (window.console) console.warn(e); }
  seletorVariacao();
  flutuanteNaCapa();
})();
