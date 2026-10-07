/* =====================================================================
   Tara Zero — Catálogo
   Busca sem acento, abas de categoria, subcategorias, filtro de pronta
   entrega, contador ao vivo, filtros na URL e painel de detalhes (#p=ID).
   Conteúdo e filtros funcionam na hora; o Flip e as entradas animadas
   começam quando o GSAP chegar (TZ.onMotion).
   ===================================================================== */
(function () {
  "use strict";

  var TZ = window.TZ, BC = window.BC;
  if (!TZ || !BC) return;
  var $ = TZ.$, $$ = TZ.$$, d = document, html = d.documentElement;
  var esc = BC.escape;
  var hasFlip = false; // definido quando o movimento começa

  var grid = $("[data-grid]"), tabsEl = $("[data-cattabs]"), subEl = $("[data-subchips]");
  var input = $("#q"), clearBtn = $("[data-clear]"), stockBtn = $("[data-stock]");
  var live = $("[data-live]"), countN = $("[data-count-n]"), empty = $("[data-empty]");
  var rN = $('[data-readout="n"]'), rM = $('[data-readout="m"]');
  var drawer = $("[data-drawer]"), dwBody = $('[data-dw="body"]'), dwCrumb = $('[data-dw="crumb"]');
  var toolbar = $(".toolbar"), catalog = $(".catalog");
  var band = $(".cta-band"), bandSec = band && band.closest("section");

  var state = { cat: "todos", sub: "", q: "", estoque: false };
  var cards = {};

  /* ---------------- URL ⇄ estado ---------------- */
  function readURL() {
    var u = new URLSearchParams(location.search);
    var cat = u.get("cat") || "todos";
    if (cat !== "todos" && !BC.categoria(cat)) cat = "todos";
    state.cat = cat;
    state.sub = u.get("sub") || "";
    state.q = u.get("q") || "";
    state.estoque = u.get("estoque") === "1" && BC.temProntaEntrega();
    if (state.sub && (state.cat === "todos" || (BC.categoria(state.cat).subcategorias || []).indexOf(state.sub) === -1)) state.sub = "";
  }
  function writeURL() {
    var u = new URLSearchParams();
    if (state.cat !== "todos") u.set("cat", state.cat);
    if (state.sub) u.set("sub", state.sub);
    if (state.q) u.set("q", state.q);
    if (state.estoque) u.set("estoque", "1");
    var qs = u.toString();
    try { history.replaceState(history.state, "", location.pathname + (qs ? "?" + qs : "") + location.hash); } catch (e) {}
  }

  /* ---------------- Render inicial ---------------- */
  function renderCards() {
    grid.innerHTML = BC.produtos.map(function (p) { return TZ.card(p); }).join("");
    $$(".pcard", grid).forEach(function (el) { cards[el.getAttribute("data-id")] = el; });
    TZ.tallImgs(grid);
  }

  function renderTabs() {
    var all = [{ id: "todos", nome: "Todos" }].concat(BC.categorias);
    tabsEl.innerHTML = all.map(function (c) {
      var n = c.id === "todos" ? BC.produtos.length : BC.contar({ categoria: c.id });
      return '<button class="cattab" type="button" role="tab" id="tab-' + esc(c.id) + '" aria-controls="grade" data-cat="' + esc(c.id) + '" aria-selected="false" tabindex="-1">' + esc(c.nome) + " <small>" + n + "</small></button>";
    }).join("");
    var btns = $$(".cattab", tabsEl);
    btns.forEach(function (b, i) {
      b.addEventListener("click", function () { setCat(b.getAttribute("data-cat")); });
      b.addEventListener("keydown", function (ev) {
        var j = null, n = btns.length;
        if (ev.key === "ArrowRight") j = (i + 1) % n;
        else if (ev.key === "ArrowLeft") j = (i - 1 + n) % n;
        else if (ev.key === "Home") j = 0;
        else if (ev.key === "End") j = n - 1;
        if (j !== null) { ev.preventDefault(); btns[j].focus(); setCat(btns[j].getAttribute("data-cat")); }
      });
    });
    tabsEl.addEventListener("scroll", tabsOverflow, { passive: true });
  }
  // Abas que não cabem: máscara à direita indica que há mais para rolar
  function tabsOverflow() {
    tabsEl.classList.toggle("is-overflow", tabsEl.scrollLeft + tabsEl.clientWidth < tabsEl.scrollWidth - 2);
  }
  function showActiveTab() {
    var at = $('.cattab[aria-selected="true"]', tabsEl);
    if (!at || tabsEl.scrollWidth <= tabsEl.clientWidth) return;
    var r = at.getBoundingClientRect(), c = tabsEl.getBoundingClientRect();
    if (r.left < c.left) tabsEl.scrollLeft += r.left - c.left - 8;
    else if (r.right > c.right) tabsEl.scrollLeft += r.right - c.right + 8;
  }

  // Os chips só são recriados quando a categoria muda; senão, atualiza no lugar (o foco não se perde)
  var subsFor = null;
  function renderSubs() {
    var c = state.cat === "todos" ? null : BC.categoria(state.cat);
    var subs = c ? (c.subcategorias || []).filter(function (s) { return BC.contar({ categoria: state.cat, subcategoria: s }) > 0; }) : [];
    if (!subs.length) { subEl.hidden = true; subEl.innerHTML = ""; subsFor = null; return; }
    subEl.hidden = false;
    if (subsFor !== state.cat) {
      subsFor = state.cat;
      subEl.innerHTML = '<button class="subchip" type="button" data-sub="" aria-pressed="false">Tudo em ' + esc(c.nome) + " <small></small></button>" +
        subs.map(function (s) {
          return '<button class="subchip" type="button" data-sub="' + esc(s) + '" aria-pressed="false">' + esc(s) + " <small></small></button>";
        }).join("");
    }
    $$(".subchip", subEl).forEach(function (b) {
      var s = b.getAttribute("data-sub");
      var f = { categoria: state.cat, texto: state.q, somenteEstoque: state.estoque };
      if (s) f.subcategoria = s;
      b.setAttribute("aria-pressed", String(state.sub === s));
      $("small", b).textContent = BC.contar(f);
    });
  }
  subEl.addEventListener("click", function (ev) {
    var b = ev.target.closest && ev.target.closest(".subchip");
    if (!b) return;
    state.sub = b.getAttribute("data-sub");
    apply();
  });

  /* ---------------- Aplicar filtros ---------------- */
  var readoutTween = null;
  function paintReadout(n, m) {
    if (countN) countN.textContent = n;
    if (!rN) return;
    if (!TZ.motion) { rN.textContent = n; rM.textContent = m; return; }
    var o = { n: parseFloat(rN.textContent) || 0, m: parseFloat(rM.textContent) || 0 };
    if (readoutTween) readoutTween.kill();
    readoutTween = gsap.to(o, { n: n, m: m, duration: .6, ease: "power2.out", onUpdate: function () { rN.textContent = Math.round(o.n); rM.textContent = Math.round(o.m); } });
  }

  // Depois de filtrar, o começo da grade tem que estar à vista, logo abaixo da barra
  function revealGrid() {
    var tbh = toolbar ? toolbar.offsetHeight : 0;
    var top = grid.getBoundingClientRect().top;
    if (top >= TZ.headerH() + tbh - 2 && top <= window.innerHeight - 120) return;
    if (TZ.lenis) TZ.lenis.scrollTo(grid, { offset: 0, duration: 1 }); // o scroll-padding já inclui cabeçalho + barra
    else window.scrollTo({ top: Math.max(0, window.scrollY + top - TZ.headerH() - tbh - 12), behavior: TZ.reduce ? "auto" : "smooth" });
  }

  var first = true;
  function apply() {
    var list = BC.filtrar({ categoria: state.cat, subcategoria: state.sub, texto: state.q, somenteEstoque: state.estoque });
    var keep = {};
    list.forEach(function (p) { keep[p.id] = true; });

    var flipState = hasFlip && !first ? Flip.getState($$(".pcard", grid)) : null;
    var h0 = grid.offsetHeight;

    // reordena: resultados na ordem do filtro, depois os ocultos
    list.forEach(function (p) { var el = cards[p.id]; if (el) { el.hidden = false; grid.appendChild(el); } });
    Object.keys(cards).forEach(function (id) { if (!keep[id]) { cards[id].hidden = true; grid.appendChild(cards[id]); } });

    if (flipState) {
      // a grade não encolhe durante a animação: o documento não "pula" e a rolagem não é cortada
      grid.style.minHeight = Math.max(h0, grid.offsetHeight) + "px";
      Flip.from(flipState, {
        duration: .6, ease: "power3.inOut", absoluteOnLeave: true, stagger: .012, prune: true,
        onEnter: function (els) { return gsap.fromTo(els, { opacity: 0, scale: .94 }, { opacity: 1, scale: 1, duration: .5, ease: "power3.out", stagger: .02 }); },
        onLeave: function (els) { return gsap.to(els, { opacity: 0, scale: .94, duration: .3, ease: "power2.in" }); },
        onComplete: function () { grid.style.minHeight = ""; if (window.ScrollTrigger) ScrollTrigger.refresh(); }
      });
    }

    // abas
    $$(".cattab", tabsEl).forEach(function (b) {
      var on = b.getAttribute("data-cat") === state.cat;
      b.setAttribute("aria-selected", on);
      b.tabIndex = on ? 0 : -1;
    });
    var at = $('.cattab[aria-selected="true"]', tabsEl);
    if (at) grid.setAttribute("aria-labelledby", at.id);
    showActiveTab();
    tabsOverflow();
    renderSubs();
    if (stockBtn) stockBtn.setAttribute("aria-checked", state.estoque);
    if (clearBtn) clearBtn.hidden = !state.q;

    // contadores
    var marcas = {};
    list.forEach(function (p) { marcas[p.marca] = true; });
    paintReadout(list.length, Object.keys(marcas).length);
    var catNome = state.cat === "todos" ? "" : " em " + BC.categoriaNome(state.cat);
    if (live) live.textContent = list.length === 0 ? "Nenhum produto encontrado." : list.length + (list.length === 1 ? " produto encontrado" : " produtos encontrados") + catNome + (state.q ? " para “" + state.q + "”" : "") + ".";

    // vazio (sem repetir a mesma mensagem na faixa de baixo)
    empty.hidden = list.length > 0;
    if (bandSec) bandSec.hidden = list.length === 0;
    if (!list.length) {
      var wa = $("[data-empty-wa]", empty);
      if (wa) wa.href = BC.whatsLink("Olá! Vim pelo site da Balanças.com e não achei no catálogo: " + (state.q || "(descreva o produto)") + ". Vocês conseguem?");
    }
    writeURL();
    setPad();
    if (!first) {
      if (window.ScrollTrigger && TZ.hasG && !flipState) ScrollTrigger.refresh();
      revealGrid();
    }
    first = false;
  }

  function setCat(id) {
    if (state.cat === id) return;
    state.cat = id; state.sub = "";
    apply();
  }

  /* ---------------- Busca ---------------- */
  var tSearch = null;
  input.addEventListener("input", function () {
    clearTimeout(tSearch);
    tSearch = setTimeout(function () { state.q = input.value.trim(); apply(); }, 140);
  });
  input.addEventListener("keydown", function (ev) { if (ev.key === "Escape" && input.value) { ev.stopPropagation(); input.value = ""; state.q = ""; apply(); } });
  clearBtn.addEventListener("click", function () { input.value = ""; state.q = ""; apply(); input.focus(); });
  d.addEventListener("keydown", function (ev) {
    if (ev.key === "/" && !/^(INPUT|TEXTAREA|SELECT)$/.test((d.activeElement || {}).tagName || "") && drawer.hidden) { ev.preventDefault(); input.focus(); }
  });
  var resetBtn = $("[data-reset]");
  if (resetBtn) resetBtn.addEventListener("click", function () {
    state = { cat: "todos", sub: "", q: "", estoque: false }; input.value = ""; apply(); input.focus();
  });
  if (stockBtn) {
    stockBtn.hidden = !BC.temProntaEntrega();
    stockBtn.addEventListener("click", function () { state.estoque = !state.estoque; apply(); });
  }

  /* ---------------- Barra de filtros no celular ---------------- */
  // scroll-padding = cabeçalho + barra: o foco (Tab/Shift+Tab) nunca fica escondido atrás dela
  function setPad() {
    if (toolbar) html.style.scrollPaddingTop = (TZ.headerH() + toolbar.offsetHeight + 12) + "px";
  }
  // Rolando para baixo, a barra recolhe (transform, sem mudar o layout); rolando para cima, volta
  function initToolbarHide() {
    if (!toolbar || !catalog) return;
    var ly = window.scrollY;
    window.addEventListener("scroll", function () {
      var y = window.scrollY;
      if (Math.abs(y - ly) < 4) return;
      toolbar.classList.toggle("is-away", window.innerWidth < 768 && y > ly && y > catalog.offsetTop + 120);
      ly = y;
    }, { passive: true });
    window.addEventListener("resize", function () { if (window.innerWidth >= 768) toolbar.classList.remove("is-away"); setPad(); tabsOverflow(); });
  }

  /* ---------------- Painel de detalhes ---------------- */
  var lastFocus = null, openId = null, closing = false;
  function related(p) {
    var same = BC.produtos.filter(function (x) { return x.id !== p.id && x.subcategoria === p.subcategoria; });
    if (same.length < 3) same = same.concat(BC.produtos.filter(function (x) { return x.id !== p.id && x.categoria === p.categoria && same.indexOf(x) === -1; }));
    return same.slice(0, 3);
  }
  function drawerHTML(p) {
    var est = BC.estoque(p), cond = BC.condicao(p);
    var specs = (p.especificacoes || []).map(function (s) { return "<dt>" + esc(s[0]) + "</dt><dd>" + esc(s[1]) + "</dd>"; }).join("");
    var rel = related(p).map(function (r) {
      return '<li><a href="#p=' + encodeURIComponent(r.id) + '"><span class="stage"><img src="' + esc(BC.imgProduto(r, false)) + '" alt="" width="200" height="150" loading="lazy"></span><small>' + esc(r.marca) + "</small><span>" + esc(r.nome) + "</span></a></li>";
    }).join("");
    return '<div class="stage"><img src="' + esc(BC.imgProduto(p, false)) + '" alt="' + esc(p.marca + " " + p.nome) + '" width="800" height="600"></div>' +
      '<div class="drawer__badges"><span class="badge badge--' + esc(est.classe) + '">' + esc(est.rotulo) + "</span>" +
      (p.condicao === "seminovo" ? '<span class="badge badge--cond">' + esc(cond) + "</span>" : "") +
      '<span class="badge badge--cat">' + esc(BC.categoriaNome(p.categoria)) + "</span></div>" +
      '<p class="drawer__brand">' + esc(p.marca) + " · " + esc(p.subcategoria) + "</p>" +
      '<h2 id="dw-title"><span class="sr-only">' + esc(p.marca) + " </span>" + esc(p.nome) + "</h2>" +
      '<p class="drawer__desc">' + esc(p.descricao || p.resumo) + "</p>" +
      '<div class="drawer__price"><span class="price"><span class="price__k">Preço</span><span class="price__v">' + (p.preco ? esc(p.preco) : "Consulte") + "</span></span><small>Preço e disponibilidade<br>direto pelo WhatsApp</small></div>" +
      '<div class="drawer__actions"><a class="btn btn--primary btn--glow" href="' + esc(BC.whatsProduto(p)) + '" target="_blank" rel="noopener" data-dw-wa><svg aria-hidden="true"><use href="#i-wa"/></svg> Pedir orçamento<span class="dw-wa-x"> no WhatsApp</span></a>' +
      '<a class="btn btn--ghost dw-tel" href="' + esc(BC.telLink()) + '" aria-label="Ligar para a Balanças.com"><svg aria-hidden="true"><use href="#i-phone"/></svg><span class="btn__txt">Ligar</span></a></div>' +
      (specs ? '<div class="specs"><h3>Especificações</h3><dl>' + specs + "</dl></div>" : "") +
      (rel ? '<div class="related"><h3>Veja também</h3><ul>' + rel + "</ul></div>" : "") +
      '<p class="drawer__note">Estoque sujeito à disponibilidade. Confirme pelo WhatsApp. Imagens ilustrativas.</p>';
  }
  function trap(ev) {
    if (ev.key === "Escape") { ev.preventDefault(); closeDrawer(); return; }
    if (ev.key !== "Tab") return;
    var f = $$('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])', drawer).filter(function (el) { return el.offsetParent !== null; });
    if (!f.length) return;
    var a = f[0], z = f[f.length - 1];
    if (ev.shiftKey && d.activeElement === a) { ev.preventDefault(); z.focus(); }
    else if (!ev.shiftKey && d.activeElement === z) { ev.preventDefault(); a.focus(); }
  }
  function openDrawer(id) {
    var p = BC.porId(id);
    if (!p) return false;
    var wasOpen = !drawer.hidden;
    openId = id;
    closing = false;
    if (!wasOpen) lastFocus = d.activeElement;
    dwCrumb.textContent = "Catálogo / " + BC.categoriaNome(p.categoria) + " / " + p.subcategoria;
    dwBody.innerHTML = drawerHTML(p);
    var panel = $(".drawer__panel", drawer);
    panel.scrollTop = 0;
    if (!wasOpen) {
      drawer.hidden = false;
      d.body.style.overflow = "hidden";
      if (TZ.lenis) TZ.lenis.stop();
      d.addEventListener("keydown", trap);
      if (TZ.motion) {
        var mobile = window.innerWidth < 768;
        gsap.fromTo($(".drawer__scrim", drawer), { autoAlpha: 0 }, { autoAlpha: 1, duration: .4, ease: "power2.out" });
        gsap.fromTo(panel, mobile ? { yPercent: 100 } : { xPercent: 100 }, { xPercent: 0, yPercent: 0, duration: .65, ease: "expo.out" });
        gsap.from($$(".drawer__body > *", drawer), { opacity: 0, y: 24, duration: .7, ease: "expo.out", stagger: .04, delay: .12 });
      }
    } else if (TZ.motion) {
      gsap.from($$(".drawer__body > *", drawer), { opacity: 0, y: 16, duration: .5, ease: "expo.out", stagger: .03 });
    }
    var closeB = $(".drawer__tools [data-close]", drawer);
    if (closeB) closeB.focus({ preventScroll: true });
    // "Veja também" e a abertura pelo hash na carga trocam a entrada atual do histórico (mantendo o estado)
    if (location.hash !== "#p=" + encodeURIComponent(id)) { try { history.replaceState(history.state, "", location.pathname + location.search + "#p=" + encodeURIComponent(id)); } catch (e) {} }
    d.title = p.marca + " " + p.nome + " | Balanças.com";
    return true;
  }
  var baseTitle = d.title;
  // fromHistory: o próprio Voltar/Avançar do navegador pediu o fechamento
  function closeDrawer(fromHistory) {
    if (drawer.hidden || openId === null) return;
    // aberto por um clique na grade: fechar pela interface = voltar uma entrada
    if (!fromHistory && history.state && history.state.dw) {
      if (closing) return;
      closing = true;
      history.back();
      setTimeout(function () { if (closing && openId !== null) closeDrawer(true); }, 450);
      return;
    }
    closing = false;
    var id = openId;
    openId = null;
    var done = function () {
      drawer.hidden = true;
      d.body.style.overflow = "";
      if (TZ.lenis) TZ.lenis.start();
      var back = lastFocus && lastFocus !== d.body && d.contains(lastFocus) ? lastFocus : $('.pcard[data-id="' + (window.CSS && CSS.escape ? CSS.escape(id) : id) + '"] .pcard__actions [data-detail]', grid);
      if (back && back.focus) back.focus({ preventScroll: true });
    };
    d.removeEventListener("keydown", trap);
    if (/^#p=/.test(location.hash)) { try { history.replaceState(null, "", location.pathname + location.search); } catch (e) {} }
    d.title = baseTitle;
    if (TZ.motion) {
      var mobile = window.innerWidth < 768;
      gsap.to($(".drawer__scrim", drawer), { autoAlpha: 0, duration: .3 });
      gsap.to($(".drawer__panel", drawer), mobile ? { yPercent: 100, duration: .4, ease: "power3.in", onComplete: done } : { xPercent: 100, duration: .4, ease: "power3.in", onComplete: done });
    } else done();
  }
  drawer.addEventListener("click", function (ev) {
    if (ev.target.closest("[data-close]")) { closeDrawer(); return; }
    var a = ev.target.closest('a[href^="#p="]');
    if (a) { ev.preventDefault(); openDrawer(decodeURIComponent(a.getAttribute("href").slice(3))); }
  });

  /* Copiar link: confirmação visível e anunciada; sem Clipboard API, cópia pelo método antigo */
  var copyBtn = $("[data-copy]", drawer), toast = $("[data-toast]", drawer), toastT = null;
  function say(msg) {
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add("is-on");
    clearTimeout(toastT);
    toastT = setTimeout(function () { toast.classList.remove("is-on"); toast.textContent = ""; }, 1800);
  }
  function oldCopy(text) {
    var ta = d.createElement("textarea"), ok = false;
    ta.value = text; ta.setAttribute("readonly", ""); ta.style.cssText = "position:fixed;top:0;left:0;opacity:0;pointer-events:none";
    drawer.appendChild(ta);
    ta.select(); try { ta.setSelectionRange(0, text.length); } catch (e) {}
    try { ok = d.execCommand("copy"); } catch (e) { ok = false; }
    drawer.removeChild(ta);
    copyBtn.focus({ preventScroll: true });
    return ok;
  }
  copyBtn.addEventListener("click", function () {
    var url = location.href;
    var ok = function () {
      say("Link copiado");
      copyBtn.style.color = "var(--signal)"; copyBtn.style.borderColor = "var(--signal)";
      setTimeout(function () { copyBtn.style.color = ""; copyBtn.style.borderColor = ""; }, 1800);
    };
    var fail = function () {
      if (oldCopy(url)) { ok(); return; }
      if (navigator.share) { navigator.share({ title: d.title, url: url }).catch(function () {}); return; }
      say("Não deu para copiar. Use o endereço da barra do navegador.");
    };
    if (navigator.clipboard && navigator.clipboard.writeText && window.isSecureContext) navigator.clipboard.writeText(url).then(ok, fail);
    else fail();
  });

  grid.addEventListener("click", function (ev) {
    var a = ev.target.closest("[data-detail]");
    if (!a) return;
    ev.preventDefault();
    var id = a.getAttribute("data-detail");
    // nova entrada no histórico: o Voltar do celular fecha o painel em vez de sair do catálogo
    try { history.pushState({ dw: id }, "", location.pathname + location.search + "#p=" + encodeURIComponent(id)); } catch (e) {}
    openDrawer(id);
  });
  function fromHash() {
    var m = /^#p=(.+)$/.exec(location.hash);
    if (m) {
      var id = decodeURIComponent(m[1]);
      if (id !== openId) openDrawer(id);
    } else if (!drawer.hidden && openId !== null) closeDrawer(true);
  }
  window.addEventListener("hashchange", fromHash);
  window.addEventListener("popstate", fromHash);

  /* ---------------- Boot: conteúdo (na hora) ---------------- */
  readURL();
  input.value = state.q;
  renderCards();
  renderTabs();
  apply();
  TZ.fill(d);
  initToolbarHide();
  // no celular, cada card tem o próprio WhatsApp: a pilha flutuante sai da frente da grade
  TZ.fabAvoid(catalog, "catalog");
  TZ.boot();
  fromHash();
  if (d.fonts && d.fonts.ready) d.fonts.ready.then(function () { setPad(); tabsOverflow(); });

  /* ---------------- Boot: movimento (quando o GSAP chegar) ---------------- */
  TZ.onMotion(function () {
    hasFlip = TZ.motion && !!window.Flip;
    if (hasFlip) gsap.registerPlugin(Flip);
    if (!drawer.hidden && TZ.lenis) TZ.lenis.stop();
    // entrada dos primeiros cards: só se ainda estavam escondidos pela intro (CDN rápido);
    // com CDN lento (TZ.late) eles já estão na tela e não somem de novo
    if (TZ.motion && !TZ.late) {
      var vis = $$(".pcard:not([hidden])", grid).slice(0, 8);
      if (vis.length) gsap.from(vis, { y: 50, opacity: 0, duration: 1, ease: "power3.out", stagger: .06, delay: .15 });
      gsap.from(".toolbar__in", { y: 20, opacity: 0, duration: .9, ease: "power3.out", delay: .1 });
    }
  });
})();
