/* =====================================================================
   Balanças.com — Design 2 "Balcão" — produtos.js (catálogo)
   Busca sem acento, abas de categoria, chips de subcategoria, filtro de
   pronta entrega, estado na URL, filtragem animada (Flip) e painel de
   detalhes acessível com link direto (#p=ID).
   ===================================================================== */
(function () {
  "use strict";

  var doc = document;
  var BC = window.BC;
  var D2 = window.D2 || {};
  var gsap = window.gsap;
  var Flip = window.Flip;
  var anim = !!D2.anim;
  var esc = D2.esc;
  var $all = D2.$all;
  if (!BC) return;

  var grid = doc.querySelector("[data-grid]");
  var tabsBox = doc.querySelector("[data-tabs]");
  var subsBox = doc.querySelector("[data-subs]");
  var search = doc.querySelector("[data-search]");
  var searchClear = doc.querySelector("[data-search-clear]");
  var stockWrap = doc.querySelector("[data-stock]");
  var stockInput = doc.querySelector("[data-stock-input]");
  var countEl = doc.querySelector("[data-count]");
  var empty = doc.querySelector("[data-empty]");
  var emptyWa = doc.querySelector("[data-empty-wa]");
  var emptyTxt = doc.querySelector("[data-empty-txt]");
  var clearBtns = $all("[data-clear]");
  var toolbarClear = doc.querySelector(".toolbar__clear");

  var st = { cat: "todos", sub: "", q: "", estoque: false };
  var cards = {};
  var temPronta = BC.temProntaEntrega ? BC.temProntaEntrega() : true;

  function plural(n, s, p) { return n + " " + (n === 1 ? s : p); }
  function catNome(id) { return id === "todos" ? "Todos" : BC.categoriaNome(id); }

  /* ---------- URL ---------- */
  function readURL() {
    var u = new URLSearchParams(location.search);
    var c = u.get("cat");
    if (c && (c === "todos" || BC.categoria(c))) st.cat = c;
    var s = u.get("sub");
    if (s && st.cat !== "todos") {
      var cat = BC.categoria(st.cat);
      if (cat && cat.subcategorias.indexOf(s) !== -1) st.sub = s;
    }
    st.q = (u.get("q") || "").slice(0, 80);
    st.estoque = temPronta && (u.get("estoque") === "1");
  }
  function writeURL() {
    var u = new URLSearchParams();
    if (st.cat !== "todos") u.set("cat", st.cat);
    if (st.sub) u.set("sub", st.sub);
    if (st.q) u.set("q", st.q);
    if (st.estoque) u.set("estoque", "1");
    var qs = u.toString();
    try { history.replaceState(history.state, "", location.pathname + (qs ? "?" + qs : "") + location.hash); } catch (e) { /* file:// em alguns navegadores */ }
  }

  /* ---------- Montagem inicial ---------- */
  function build() {
    grid.innerHTML = BC.produtos.map(function (p) { return D2.card(p); }).join("");
    $all(".pcard", grid).forEach(function (c) { cards[c.getAttribute("data-id")] = c; });

    // contagens
    var marcas = {};
    BC.produtos.forEach(function (p) { marcas[p.marca] = 1; });
    var sp = doc.querySelector('[data-stat="produtos"]'); if (sp) sp.textContent = BC.produtos.length;
    var sm = doc.querySelector('[data-stat="marcas"]'); if (sm) sm.textContent = Math.max(Object.keys(marcas).length, (BC.empresa.marcas || []).length);

    // abas a partir de BC.categorias
    var tabs = [{ id: "todos", nome: "Todos" }].concat(BC.categorias);
    tabsBox.innerHTML = tabs.map(function (c) {
      var n = BC.contar({ categoria: c.id });
      return '<button class="tab" role="tab" type="button" aria-selected="false" tabindex="-1" aria-controls="grade" data-cat="' + esc(c.id) + '">' +
        esc(c.nome) + ' <span class="tab__n">' + n + "</span></button>";
    }).join("");
    $all("[data-cat-count]").forEach(function (el) { el.textContent = BC.contar({ categoria: el.getAttribute("data-cat-count") }); });

    if (temPronta && stockWrap) stockWrap.hidden = false;
  }

  /* ---------- Indicador das abas ---------- */
  var ind;
  function moveInd(instant) {
    var sel = tabsBox.querySelector('[aria-selected="true"]');
    if (!sel || !ind) return;
    if (instant) ind.style.transition = "none";
    ind.style.width = sel.offsetWidth + "px";
    ind.style.transform = "translateX(" + sel.offsetLeft + "px)";
    if (instant) { void ind.offsetWidth; ind.style.transition = ""; }
  }

  function syncControls() {
    $all(".tab", tabsBox).forEach(function (t) {
      var on = t.getAttribute("data-cat") === st.cat;
      t.setAttribute("aria-selected", String(on));
      t.tabIndex = on ? 0 : -1;
    });
    moveInd();
    $all("[data-cat-tile]").forEach(function (b) { b.setAttribute("aria-pressed", String(b.getAttribute("data-cat-tile") === st.cat)); });

    // chips de subcategoria (só as que têm produto)
    var cat = st.cat !== "todos" ? BC.categoria(st.cat) : null;
    if (!cat) { subsBox.hidden = true; subsBox.innerHTML = ""; }
    else {
      var subs = cat.subcategorias.filter(function (s) { return BC.contar({ categoria: cat.id, subcategoria: s }) > 0; });
      subsBox.innerHTML = '<button class="chip" type="button" aria-pressed="' + (!st.sub) + '" data-sub="">Todas</button>' +
        subs.map(function (s) {
          return '<button class="chip" type="button" aria-pressed="' + (st.sub === s) + '" data-sub="' + esc(s) + '">' + esc(s) +
            " <small>" + BC.contar({ categoria: cat.id, subcategoria: s }) + "</small></button>";
        }).join("");
      subsBox.hidden = false;
    }
    if (search.value !== st.q) search.value = st.q;
    searchClear.hidden = !st.q;
    if (stockInput) stockInput.checked = st.estoque;
  }

  /* ---------- Aplicar filtros ---------- */
  function apply(animate) {
    var list = BC.filtrar({ categoria: st.cat, subcategoria: st.sub, texto: st.q, somenteEstoque: st.estoque });
    var ids = {};
    list.forEach(function (p) { ids[p.id] = true; });
    var all = Object.keys(cards).map(function (k) { return cards[k]; });
    var doFlip = animate && anim && Flip && all.length < 120;
    var state = doFlip ? Flip.getState(all) : null;

    all.forEach(function (c) { c.classList.toggle("is-hidden", !ids[c.getAttribute("data-id")]); });
    list.forEach(function (p) { if (cards[p.id]) grid.appendChild(cards[p.id]); });

    if (state) {
      grid.classList.add("is-flipping");
      Flip.from(state, {
        duration: 0.6, ease: "power3.inOut", absolute: true, stagger: 0.008, nested: true,
        onEnter: function (els) { return gsap.fromTo(els, { opacity: 0, scale: 0.94 }, { opacity: 1, scale: 1, duration: 0.5, delay: 0.12, ease: "power3.out", stagger: 0.02 }); },
        onLeave: function (els) { return gsap.to(els, { opacity: 0, scale: 0.94, duration: 0.28, ease: "power2.in" }); },
        onComplete: function () {
          grid.classList.remove("is-flipping");
          gsap.set(all, { clearProps: "opacity,scale,transform" });
        }
      });
    }

    // contador e estado vazio
    var n = list.length;
    var ctx = [];
    if (st.cat !== "todos") ctx.push(st.sub || catNome(st.cat));
    if (st.q) ctx.push("“" + st.q + "”");
    countEl.innerHTML = n ? "<b>" + plural(n, "produto", "produtos") + "</b>" + (ctx.length ? " <span>· " + esc(ctx.join(" · ")) + "</span>" : "")
      : "Nenhum produto encontrado";
    empty.hidden = n > 0;
    if (!n) {
      var procura = st.q || (st.sub || (st.cat !== "todos" ? catNome(st.cat) : ""));
      emptyTxt.textContent = st.q ? "Não encontramos “" + st.q + "” no catálogo. Conte o que você procura e a gente busca com as marcas com que trabalhamos." :
        "Nenhum produto com esses filtros agora. Conte o que você procura e a gente busca com as marcas com que trabalhamos.";
      emptyWa.setAttribute("href", BC.whatsLink("Olá! Procurei" + (procura ? " por *" + procura + "*" : "") + " no catálogo do site da Balanças.com e não encontrei. Vocês conseguem para mim?"));
    }
    var filtered = st.cat !== "todos" || st.sub || st.q || st.estoque;
    if (toolbarClear) toolbarClear.hidden = !filtered;
    writeURL();
  }

  function update(animate) { syncControls(); apply(animate); }

  /* ---------- Eventos ---------- */
  function setCat(id, scroll) {
    if (st.cat === id) return;
    st.cat = id; st.sub = "";
    update(true);
    if (scroll) {
      var list = doc.getElementById("lista");
      if (D2.lenis) D2.lenis.scrollTo(list, { offset: -((doc.querySelector(".hdr") || {}).offsetHeight || 72) - 8 });
      else list.scrollIntoView({ behavior: D2.reduce ? "auto" : "smooth" });
    }
  }

  function bind() {
    tabsBox.addEventListener("click", function (e) {
      var t = e.target.closest(".tab"); if (t) setCat(t.getAttribute("data-cat"));
    });
    tabsBox.addEventListener("keydown", function (e) {
      var tabs = $all(".tab", tabsBox);
      var i = tabs.indexOf(doc.activeElement);
      if (i < 0) return;
      var j = null;
      if (e.key === "ArrowRight") j = (i + 1) % tabs.length;
      else if (e.key === "ArrowLeft") j = (i - 1 + tabs.length) % tabs.length;
      else if (e.key === "Home") j = 0;
      else if (e.key === "End") j = tabs.length - 1;
      if (j !== null) { e.preventDefault(); setCat(tabs[j].getAttribute("data-cat")); tabsBox.querySelector('[data-cat="' + st.cat + '"]').focus(); }
    });
    subsBox.addEventListener("click", function (e) {
      var c = e.target.closest(".chip"); if (!c) return;
      st.sub = c.getAttribute("data-sub") || "";
      update(true);
    });
    $all("[data-cat-tile]").forEach(function (b) {
      b.addEventListener("click", function () {
        var id = b.getAttribute("data-cat-tile");
        if (st.cat === id) { st.cat = "todos"; st.sub = ""; update(true); return; }
        setCat(id, true);
      });
    });
    var timer;
    search.addEventListener("input", function () {
      clearTimeout(timer);
      searchClear.hidden = !search.value;
      timer = setTimeout(function () { st.q = search.value.trim(); apply(true); }, 160);
    });
    search.addEventListener("keydown", function (e) { if (e.key === "Escape" && search.value) { e.preventDefault(); search.value = ""; st.q = ""; update(true); } });
    searchClear.addEventListener("click", function () { search.value = ""; st.q = ""; update(true); search.focus(); });
    if (stockInput) stockInput.addEventListener("change", function () { st.estoque = stockInput.checked; apply(true); });
    clearBtns.forEach(function (b) {
      b.addEventListener("click", function () { st = { cat: "todos", sub: "", q: "", estoque: false }; update(true); });
    });
    window.addEventListener("resize", function () { moveInd(true); });
  }

  /* ==================================================================
     Painel de detalhes
     ================================================================== */
  var drawer = doc.querySelector("[data-drawer]");
  var panel = drawer.querySelector(".drawer__panel");
  var body = drawer.querySelector("[data-dw-body]");
  var crumb = drawer.querySelector("[data-dw-crumb]");
  var dwWa = drawer.querySelector("[data-dw-wa]");
  var dwCopy = drawer.querySelector("[data-dw-copy]");
  var dwTip = drawer.querySelector("[data-dw-copy-tip]");
  var lastFocus = null;
  var openedByPush = false;
  var currentId = null;
  var closeTimer = null;

  function productURL(p) {
    return location.href.split("#")[0] + "#p=" + encodeURIComponent(p.id);
  }
  function waProduto(p) {
    var msg = BC.msgProduto(p);
    if (/^https?:/.test(location.protocol)) msg += "\n" + productURL(p);
    return BC.whatsLink(msg);
  }

  function related(p) {
    var same = BC.produtos.filter(function (o) { return o.id !== p.id && o.subcategoria === p.subcategoria; });
    var cat = BC.produtos.filter(function (o) { return o.id !== p.id && o.categoria === p.categoria && o.subcategoria !== p.subcategoria; });
    return same.concat(cat).slice(0, 3);
  }

  function renderDrawer(p) {
    var est = BC.estoque(p);
    var cond = p.condicao === "seminovo" ? BC.condicao(p) : "";
    var specs = (p.especificacoes || []).map(function (s) { return "<div><dt>" + esc(s[0]) + "</dt><dd>" + esc(s[1]) + "</dd></div>"; }).join("");
    var rel = related(p);
    crumb.textContent = BC.categoriaNome(p.categoria) + " · " + p.subcategoria;
    body.innerHTML =
      '<div class="dw-stage">' +
        '<span class="pcard__badges"><span class="badge badge--' + esc(est.classe) + '">' + esc(est.rotulo) + "</span>" + (cond ? '<span class="badge badge--cond">' + esc(cond) + "</span>" : "") + "</span>" +
        '<span class="dw-stage__disc" aria-hidden="true"></span>' +
        '<img src="' + esc(BC.imgProduto(p, true)) + '" alt="' + esc(p.marca + " " + p.nome) + '" width="900" height="600">' +
      "</div>" +
      '<div class="dw-main">' +
        '<p class="dw-brand"><b>' + esc(p.marca) + "</b> · " + esc(p.subcategoria) + "</p>" +
        '<h2 class="dw-title" id="dw-title">' + esc(p.nome) + "</h2>" +
        '<p class="dw-lead">' + esc(p.resumo) + "</p>" +
        '<div class="dw-price"><span><small>Preço</small><strong>' + esc(p.preco || "Consulte") + "</strong></span>" +
          "<span>" + (p.preco ? "Confirme a disponibilidade pelo WhatsApp." : "Peça seu orçamento: respondemos pelo WhatsApp.") + "</span></div>" +
      "</div>" +
      (p.descricao ? '<section class="dw-sec"><h3>Sobre o produto</h3><p>' + esc(p.descricao) + "</p></section>" : "") +
      (specs ? '<section class="dw-sec"><h3>Especificações</h3><dl class="specs">' + specs + "</dl></section>" : "") +
      (p.categoria === "balancas" ? '<p class="dw-service"><svg class="i" aria-hidden="true"><use href="#i-shield"/></svg><span>Depois da compra, conte com a nossa assistência técnica: somos oficina autorizada pelo <span class="nw">IPEM-SP</span>. <a href="' + esc(BC.whatsLink("Olá! Vim pelo site da Balanças.com e preciso de manutenção na minha balança " + p.marca + " " + p.nome + ".")) + '" target="_blank" rel="noopener">Falar com a assistência</a></span></p>' : "") +
      (rel.length ? '<section class="dw-rel"><h3>Veja também</h3><div class="dw-rel__list">' + rel.map(function (r) {
        return '<a class="rel" href="#p=' + encodeURIComponent(r.id) + '" data-rel="' + esc(r.id) + '"><span class="rel__img"><img src="' + esc(BC.imgProduto(r, true)) + '" alt="" loading="lazy"></span><span class="rel__n">' + esc(r.nome) + '</span><span class="rel__b">' + esc(r.marca) + "</span></a>";
      }).join("") + "</div></section>" : "");
    dwWa.setAttribute("href", waProduto(p));
    dwWa.setAttribute("aria-label", "Pedir " + p.marca + " " + p.nome + " no WhatsApp");
    body.scrollTop = 0;
  }

  var outside = $all("header.hdr, main, footer, .fab, .skip");
  function setInert(on) { outside.forEach(function (el) { if (on) el.setAttribute("inert", ""); else el.removeAttribute("inert"); }); }

  function openDrawer(id, pushed) {
    var p = BC.porId(id);
    if (!p) return false;
    var already = !drawer.hidden && drawer.classList.contains("is-open");
    clearTimeout(closeTimer);
    currentId = id;
    if (!already) {
      lastFocus = doc.activeElement && doc.activeElement !== doc.body ? doc.activeElement : null;
      openedByPush = !!pushed;
    }
    renderDrawer(p);
    if (!already) {
      drawer.hidden = false;
      setInert(true);
      doc.documentElement.style.overflow = "hidden";
      if (D2.lenis) D2.lenis.stop();
      void drawer.offsetWidth;
      drawer.classList.add("is-open");
      setTimeout(function () { var c = drawer.querySelector(".drawer__close"); if (c) c.focus({ preventScroll: true }); }, 40);
    }
    if (anim) {
      gsap.fromTo(body.children, { opacity: 0, y: 18 }, { opacity: 1, y: 0, duration: 0.7, ease: "expo.out", stagger: 0.05, delay: already ? 0 : 0.15, clearProps: "opacity,transform" });
    }
    return true;
  }

  function closeDrawer(fromHash) {
    if (drawer.hidden || !drawer.classList.contains("is-open")) return;
    if (!fromHash) {
      if (openedByPush) { openedByPush = false; history.back(); return; }
      try { history.replaceState(history.state, "", location.pathname + location.search); } catch (e) { /* ignore */ }
    }
    drawer.classList.remove("is-open");
    setInert(false);
    doc.documentElement.style.overflow = "";
    if (D2.lenis) D2.lenis.start();
    var back = lastFocus || (currentId && cards[currentId] && cards[currentId].querySelector("[data-open]"));
    currentId = null;
    closeTimer = setTimeout(function () { drawer.hidden = true; }, D2.reduce ? 10 : 620);
    if (back && back.focus) back.focus({ preventScroll: true });
  }

  function hashId() {
    var m = /^#p=([^&]+)/.exec(location.hash || "");
    return m ? decodeURIComponent(m[1]) : null;
  }

  function bindDrawer() {
    drawer.addEventListener("click", function (e) {
      if (e.target.closest("[data-close]")) { e.preventDefault(); closeDrawer(); }
    });
    doc.addEventListener("keydown", function (e) {
      if (drawer.hidden) return;
      if (e.key === "Escape") { e.preventDefault(); closeDrawer(); return; }
      if (e.key === "Tab") {
        var f = $all('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])', panel).filter(function (el) { return el.offsetParent !== null; });
        if (!f.length) return;
        var first = f[0], last = f[f.length - 1];
        if (e.shiftKey && (doc.activeElement === first || doc.activeElement === panel)) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && doc.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
    // Relacionados: troca o conteúdo sem empilhar histórico
    body.addEventListener("click", function (e) {
      var r = e.target.closest("[data-rel]");
      if (!r) return;
      e.preventDefault();
      var id = r.getAttribute("data-rel");
      try { history.replaceState(history.state, "", location.pathname + location.search + "#p=" + encodeURIComponent(id)); } catch (err) { /* ignore */ }
      openDrawer(id, openedByPush);
      var c = drawer.querySelector(".drawer__close"); if (c) c.focus({ preventScroll: true });
    });
    // Links "Detalhes" e nome do produto: marcamos que o histórico ganhou uma entrada
    grid.addEventListener("click", function (e) {
      var a = e.target.closest("[data-open]");
      if (!a || e.metaKey || e.ctrlKey || e.shiftKey) return;
      e.preventDefault();
      var id = a.getAttribute("data-open");
      lastFocus = a;
      try { history.pushState({ p: id }, "", location.pathname + location.search + "#p=" + encodeURIComponent(id)); } catch (err) { /* ignore */ }
      openDrawer(id, true);
    });
    window.addEventListener("hashchange", function () {
      var id = hashId();
      if (id) openDrawer(id, false);
      else closeDrawer(true);
    });
    window.addEventListener("popstate", function () {
      var id = hashId();
      if (id) openDrawer(id, false);
      else { openedByPush = false; closeDrawer(true); }
    });
    dwCopy.addEventListener("click", function () {
      var p = currentId && BC.porId(currentId);
      if (!p) return;
      var url = productURL(p);
      function ok() { dwTip.textContent = "Link copiado!"; dwTip.classList.add("is-on"); setTimeout(function () { dwTip.classList.remove("is-on"); }, 1800); }
      try {
        if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(url).then(ok, fallback);
        else fallback();
      } catch (e) { fallback(); }
      function fallback() {
        var t = doc.createElement("textarea");
        t.value = url; t.setAttribute("readonly", ""); t.style.position = "fixed"; t.style.opacity = "0";
        doc.body.appendChild(t); t.select();
        try { doc.execCommand("copy"); ok(); } catch (e) { dwTip.textContent = url; dwTip.classList.add("is-on"); }
        t.remove();
      }
    });
  }

  /* ---------- Entrada ---------- */
  function intro() {
    if (!anim) return;
    var vis = $all(".pcard:not(.is-hidden)", grid).slice(0, 12);
    gsap.from(vis, { opacity: 0, translate: "0px 30px", duration: 1, ease: "expo.out", stagger: 0.05, delay: 0.25, clearProps: "opacity,translate" });
  }

  build();
  readURL();
  ind = doc.createElement("span");
  ind.className = "tabs__ind";
  ind.setAttribute("aria-hidden", "true");
  tabsBox.insertBefore(ind, tabsBox.firstChild);
  tabsBox.classList.add("has-ind");
  update(false);
  moveInd(true);
  if (doc.fonts && doc.fonts.ready) doc.fonts.ready.then(function () { moveInd(true); });
  bind();
  bindDrawer();
  intro();
  var first = hashId();
  if (first) openDrawer(first, false);
})();
