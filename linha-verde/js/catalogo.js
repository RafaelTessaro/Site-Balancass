/* =====================================================================
   Balanças.com — "Linha Verde" — js/catalogo.js
   Só em produtos.html (depois do core.js): abas de categoria, tipos,
   busca sem acento, filtro de pronta entrega, contador, estado na URL
   (?cat=&sub=&q=&estoque=1) e painel de detalhes por endereço (#p=ID).
   ===================================================================== */
(function () {
  "use strict";
  var BC = window.BC, LV = window.LV;
  if (!BC || !LV) return;
  var $ = LV.$, $$ = LV.$$, esc = LV.esc;
  var doc = document.documentElement;

  var grid = $("[data-grid]"), panelBox = $("#grade"), tabsBox = $("[data-tabs]"), subsBox = $("[data-subs]");
  var satNote = $("[data-sat-note]"), jump = $("[data-jump]"), catSec = $("#catalogo");
  var qIn = $("[data-q]"), qClear = $("[data-q-clear]"), stock = $("[data-stock]"), stockWrap = $("[data-stock-wrap]");
  var countEl = $("[data-results]"), empty = $("[data-empty]"), fbar = $("[data-fbar]"), allBtn = $("[data-all]");
  if (!grid) return;

  var state = { cat: "todos", sub: "", q: "", estoque: false };
  var motion = function () { return LV.motionOK() && window.Flip; };
  var wideMQ = window.matchMedia("(min-width: 900px)");

  /* ---------- Busca ----------
     Sem acento e sem depender de hífen/espaço: "tl900" acha "TL-900", "wifi" acha "Wi-Fi",
     "prix4" acha "Prix 4". Procura também nas especificações. */
  function compacta(s) { return s.replace(/[\s\-./_,:;()]+/g, ""); }
  var INDICE = {};
  BC.produtos.forEach(function (p) {
    var t = BC.normaliza([p.nome, p.marca, p.subcategoria, p.resumo, p.descricao, BC.categoriaNome(p.categoria), LV.catCurto(p.categoria)]
      .concat((p.especificacoes || []).map(function (r) { return r.join(" "); })).join(" "));
    INDICE[p.id] = { t: t, c: compacta(t) };
  });
  function termos(q) { return BC.normaliza(q).split(/\s+/).filter(Boolean); }
  function casa(p, ts) {
    var ix = INDICE[p.id];
    if (!ix) return true;
    for (var i = 0; i < ts.length; i++) {
      if (ix.t.indexOf(ts[i]) !== -1) continue;
      var c = compacta(ts[i]);
      if (c && ix.c.indexOf(c) !== -1) continue;
      return false;
    }
    return true;
  }
  // Mesmo contrato de BC.filtrar, com a busca acima (usado na grade, nas abas e nos tipos)
  function filtrar(f) {
    var ts = termos(f.texto);
    var lista = BC.filtrar({ categoria: f.categoria, subcategoria: f.subcategoria, somenteEstoque: f.somenteEstoque });
    return ts.length ? lista.filter(function (p) { return casa(p, ts); }) : lista;
  }
  function contar(f) { return filtrar(f).length; }

  /* ---------- Estado ⇄ URL ---------- */
  function readURL() {
    var p;
    try { p = new URLSearchParams(location.search); } catch (e) { return; }
    var cat = p.get("cat");
    state.cat = cat && BC.categoria(cat) ? cat : "todos";
    var sub = p.get("sub") || "";
    var c = BC.categoria(state.cat);
    state.sub = c && c.subcategorias.indexOf(sub) !== -1 ? sub : "";
    state.q = (p.get("q") || "").slice(0, 80);
    state.estoque = p.get("estoque") === "1" && BC.temProntaEntrega();
  }
  function writeURL() {
    var p = new URLSearchParams();
    if (state.cat !== "todos") p.set("cat", state.cat);
    if (state.sub) p.set("sub", state.sub);
    if (state.q.trim()) p.set("q", state.q.trim());
    if (state.estoque) p.set("estoque", "1");
    var qs = p.toString();
    try { history.replaceState(history.state, "", location.pathname + (qs ? "?" + qs : "") + location.hash); } catch (e) { /* file:// */ }
  }

  // Faixas roláveis do celular (abas, tipos): traz o item ativo para dentro, sem rolar a página
  function mostra(el, box) {
    if (!el || !box || box.scrollWidth <= box.clientWidth + 1) return;
    var r = el.getBoundingClientRect(), b = box.getBoundingClientRect();
    if (r.left >= b.left + 8 && r.right <= b.right - 8) return;
    box.scrollLeft = Math.max(0, box.scrollLeft + r.left - b.left - 16);
  }
  function mostraAtivos() {
    mostra($('.tab[aria-selected="true"]', tabsBox), tabsBox);
    mostra($('.sub[aria-pressed="true"]', subsBox), subsBox);
  }

  /* ---------- Abas (categorias) ---------- */
  function nProdutos(n) { return n === 1 ? "1 produto" : n + " produtos"; }
  function renderTabs() {
    var cats = [{ id: "todos", nome: "Todos" }].concat(BC.categorias);
    tabsBox.innerHTML = cats.map(function (c) {
      var n = contar({ categoria: c.id, texto: state.q, somenteEstoque: state.estoque });
      var sel = c.id === state.cat;
      var nome = c.id === "todos" ? "Todos" : LV.catCurto(c.id);
      return '<button class="tab" role="tab" type="button" id="tab-' + esc(c.id) + '" data-cat="' + esc(c.id) + '"' +
        ' aria-selected="' + sel + '" aria-controls="grade" tabindex="' + (sel ? "0" : "-1") + '" title="' + esc(c.nome) + '">' +
        '<span class="st" data-t="' + esc(nome) + '">' + esc(nome) + '</span><span class="tab__n" aria-hidden="true">' + LV.pad(n) + "</span>" +
        '<span class="sr-only tab__sr">(' + nProdutos(n) + ")</span></button>";
    }).join("");
    (panelBox || grid).setAttribute("aria-labelledby", "tab-" + state.cat);
  }
  tabsBox.addEventListener("click", function (e) {
    var b = e.target.closest(".tab"); if (!b) return;
    setCat(b.getAttribute("data-cat"));
  });
  tabsBox.addEventListener("keydown", function (e) {
    var tabs = $$(".tab", tabsBox), i = tabs.indexOf(document.activeElement);
    if (i < 0) return;
    var k = e.key, n = null;
    if (k === "ArrowRight") n = (i + 1) % tabs.length;
    else if (k === "ArrowLeft") n = (i - 1 + tabs.length) % tabs.length;
    else if (k === "Home") n = 0;
    else if (k === "End") n = tabs.length - 1;
    if (n === null) return;
    e.preventDefault();
    setCat(tabs[n].getAttribute("data-cat"));
    $$(".tab", tabsBox)[n].focus({ preventScroll: true });
  });
  function setCat(id) {
    if (id === state.cat) return;
    state.cat = id; state.sub = "";
    $$(".tab", tabsBox).forEach(function (t) {
      var on = t.getAttribute("data-cat") === id;
      t.setAttribute("aria-selected", String(on)); t.tabIndex = on ? 0 : -1;
    });
    (panelBox || grid).setAttribute("aria-labelledby", "tab-" + id);
    renderSubs(); apply(true);
  }

  /* ---------- Tipos (subcategorias) ---------- */
  function renderSubs() {
    var c = BC.categoria(state.cat);
    if (!c) {
      subsBox.innerHTML = '<p class="subs__hint">Escolha uma categoria para filtrar por tipo.</p>';
      return;
    }
    var items = c.subcategorias.filter(function (s) { return BC.contar({ categoria: c.id, subcategoria: s }) > 0; });
    subsBox.innerHTML = '<button class="sub" type="button" data-sub="" aria-pressed="' + (!state.sub) + '">Todos os tipos</button>' +
      items.map(function (s) {
        return '<button class="sub" type="button" data-sub="' + esc(s) + '" aria-pressed="' + (state.sub === s) + '">' +
          esc(s) + ' <span class="sub__n">' + contar({ categoria: c.id, subcategoria: s, texto: state.q, somenteEstoque: state.estoque }) + "</span></button>";
      }).join("");
  }
  subsBox.addEventListener("click", function (e) {
    var b = e.target.closest(".sub"); if (!b) return;
    state.sub = b.getAttribute("data-sub") || "";
    $$(".sub", subsBox).forEach(function (x) { x.setAttribute("aria-pressed", String(x === b)); });
    apply(true);
  });
  // Os números das abas e dos tipos acompanham a busca
  function updateCounts() {
    $$(".tab", tabsBox).forEach(function (t) {
      var n = contar({ categoria: t.getAttribute("data-cat"), texto: state.q, somenteEstoque: state.estoque });
      var nEl = $(".tab__n", t), sr = $(".tab__sr", t);
      if (nEl) nEl.textContent = LV.pad(n);
      if (sr) sr.textContent = "(" + nProdutos(n) + ")";
    });
    $$(".sub[data-sub]", subsBox).forEach(function (b) {
      var s = b.getAttribute("data-sub"), nEl = $(".sub__n", b);
      if (s && nEl) nEl.textContent = contar({ categoria: state.cat, subcategoria: s, texto: state.q, somenteEstoque: state.estoque });
    });
  }

  /* ---------- Busca e pronta entrega ---------- */
  var tmr;
  qIn.addEventListener("input", function () {
    state.q = qIn.value;
    qClear.hidden = !qIn.value;
    clearTimeout(tmr); tmr = setTimeout(function () { apply(true); }, 140);
  });
  qIn.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && qIn.value) { e.stopPropagation(); qIn.value = ""; qIn.dispatchEvent(new Event("input")); return; }
    // Tecla "Buscar" do teclado do celular: fecha o teclado e mostra o começo dos resultados
    if (e.key === "Enter") {
      e.preventDefault();
      clearTimeout(tmr); state.q = qIn.value; apply(true, true);
      if (!wideMQ.matches) qIn.blur();
    }
  });
  qClear.addEventListener("click", function () { qIn.value = ""; state.q = ""; qClear.hidden = true; qIn.focus(); apply(true); });
  if (BC.temProntaEntrega() && stockWrap) {
    stockWrap.hidden = false;
    stock.addEventListener("change", function () { state.estoque = stock.checked; apply(true); });
  }
  function resetFiltros() {
    state.q = ""; qIn.value = ""; qClear.hidden = true; state.sub = ""; state.estoque = false; if (stock) stock.checked = false;
    if (state.cat !== "todos") setCat("todos"); else { renderSubs(); apply(true); }
  }
  $("[data-reset]").addEventListener("click", function () { resetFiltros(); qIn.focus({ preventScroll: true }); });
  // Estado vazio: a busca tem resultado em outra categoria
  if (allBtn) allBtn.addEventListener("click", function () {
    state.sub = ""; setCat("todos");
    var t = $('.tab[aria-selected="true"]', tabsBox); if (t) t.focus({ preventScroll: true });
  });

  /* ---------- Grade: todas as fichas renderizadas uma vez ---------- */
  var MSG_OUTRO = "Olá! Vim pelo catálogo do site e procuro um equipamento que não encontrei na lista.";
  grid.innerHTML = BC.produtos.map(function (p, i) {
    return '<li class="pgrid__i" data-id="' + esc(p.id) + '">' + LV.cardProduto(p, { n: i + 1, detalhe: "", sizes: "(max-width: 639px) 46vw, (max-width: 700px) 86vw, 340px" }) + "</li>";
  }).join("") +
    '<li class="pgrid__i pgrid__i--cta" data-cta><a class="pgrid__cta" href="' + BC.whatsLink(MSG_OUTRO) + '" target="_blank" rel="noopener">' +
      '<span class="mono">Outros modelos</span><span class="pgrid__cta-t">Procurando outro modelo?</span>' +
      '<span class="pgrid__cta-go"><svg class="i" aria-hidden="true"><use href="#i-wa"/></svg>A gente consegue pra você</span>' +
      '<span class="sr-only"> (abre em nova aba)</span></a></li>';

  // Lista dos produtos para buscadores (JSON-LD ItemList), gerada de produtos.js: acompanha o catálogo sozinha
  (function itemList() {
    try {
      var canon = $('link[rel="canonical"]'), base = canon ? canon.href.split("#")[0] : location.href.split("#")[0].split("?")[0];
      var abs = function (u) { try { return new URL(u, location.href).href; } catch (e) { return u; } };
      var data = {
        "@context": "https://schema.org", "@type": "ItemList", "name": "Catálogo da Balanças.com",
        "numberOfItems": BC.produtos.length,
        "itemListElement": BC.produtos.map(function (p, i) {
          return { "@type": "ListItem", "position": i + 1, "name": p.marca + " " + p.nome, "description": p.resumo,
            "image": abs(BC.imgProduto(p, false)), "url": base + "#p=" + encodeURIComponent(p.id) };
        })
      };
      var s = document.createElement("script"); s.type = "application/ld+json";
      s.textContent = JSON.stringify(data).replace(/</g, "\\u003c");
      document.head.appendChild(s);
    } catch (e) { /* ok */ }
  })();

  function plural(n) { return n === 0 ? "Nenhum produto" : (n === 1 ? "<b>1</b> produto" : "<b>" + n + "</b> produtos"); }

  // Quem trocou o filtro com a barra grudada no topo (rolado no meio da grade) volta ao começo dos resultados
  function voltaAoInicio(force) {
    if (!catSec) return false;
    var wide = wideMQ.matches;
    var gTop = grid.getBoundingClientRect().top, lim = LV.hdr() + (wide && fbar ? fbar.offsetHeight : 0);
    if (!force && !(fbar && fbar.classList.contains("is-stuck") && wide) && gTop >= lim) return false;
    if (force && gTop >= lim && gTop < window.innerHeight * 0.6) return false;
    var y = Math.max(0, (wide ? catSec : grid).getBoundingClientRect().top + window.scrollY - LV.hdr() - (wide ? 0 : 12));
    if (Math.abs(y - window.scrollY) < 4) return false;
    if (LV.lenis) LV.lenis.scrollTo(y, { duration: 0.6, force: true });
    else window.scrollTo({ top: y, behavior: LV.motionOK() ? "smooth" : "auto" });
    return true;
  }

  function apply(animate, aoInicio) {
    var lista = filtrar({ categoria: state.cat, subcategoria: state.sub, texto: state.q, somenteEstoque: state.estoque });
    var show = {}; lista.forEach(function (p) { show[p.id] = true; });
    var all = $$(".pgrid__i", grid);
    var useFlip = animate && motion();
    var rolou = animate ? voltaAoInicio(aoInicio) : false;
    var flipState = useFlip ? Flip.getState(all) : null;
    all.forEach(function (li) { li.hidden = li.hasAttribute("data-cta") ? lista.length === 0 : !show[li.getAttribute("data-id")]; });
    grid.classList.toggle("is-impar", lista.length % 2 === 1);
    countEl.innerHTML = plural(lista.length);
    updateCounts();
    empty.hidden = lista.length > 0;
    var wa = $("[data-wa-empty]");
    if (wa) wa.href = BC.whatsLink(state.q.trim()
      ? "Olá! Procurei por \"" + state.q.trim() + "\" no catálogo do site e não encontrei. Vocês conseguem?"
      : "Olá! Procurei no catálogo do site e não encontrei o que procuro. Vocês conseguem?");
    // Sem resultado aqui, mas a busca acha em outra categoria/tipo: oferece "ver em todas"
    if (allBtn) {
      var nTodos = lista.length || !(state.cat !== "todos" || state.sub) ? 0 : contar({ texto: state.q, somenteEstoque: state.estoque });
      allBtn.hidden = !nTodos;
      var allT = $(".st", allBtn);
      if (nTodos && allT) { var tx = "Ver " + nProdutos(nTodos).replace("produto", "resultado") + " em todas as categorias"; allT.textContent = tx; allT.setAttribute("data-t", tx); }
    }
    if (satNote) satNote.hidden = !/(^|\s)(cf-?e\s*)?sat(\s|$)/.test(BC.normaliza(state.q));
    writeURL();
    if (animate) mostraAtivos();
    if (animate && lista.length === 0 && !rolou) showEmpty();
    if (useFlip) {
      Flip.from(flipState, {
        duration: 0.6, ease: "power3.inOut", absolute: true, stagger: 0.012, nested: true,
        onEnter: function (els) { return gsap.fromTo(els, { opacity: 0, y: 30, skewX: -6 }, { opacity: 1, y: 0, skewX: 0, duration: 0.6, stagger: 0.03, ease: "expo.out" }); },
        onLeave: function (els) { return gsap.to(els, { opacity: 0, scale: 0.94, duration: 0.3, ease: "power2.in" }); },
        onComplete: LV.refresh
      });
      // fichas que entram na grade já ficam visíveis (podiam estar esperando a revelação por rolagem)
      var vis = $$(".pgrid__i:not([hidden]) > .spec", grid);
      if (vis.length) gsap.set(vis, { opacity: 1, y: 0 });
    } else {
      LV.refresh();
    }
  }

  // Sem resultado: traz o estado vazio (e o botão do WhatsApp) para a tela, sem tirar o foco da busca
  function showEmpty() {
    requestAnimationFrame(function () {
      var r = empty.getBoundingClientRect(), wide = wideMQ.matches;
      var top = LV.hdr() + (wide && fbar ? fbar.offsetHeight : 0) + 12;
      if (r.bottom <= window.innerHeight && r.top >= top) return;
      var dy = r.bottom > window.innerHeight ? Math.min(r.bottom - window.innerHeight + 24, r.top - top) : r.top - top;
      if (!wide) dy = Math.min(dy, qIn.getBoundingClientRect().top - LV.hdr() - 8);
      if (dy <= 0) return;
      var y = window.scrollY + dy;
      if (LV.lenis) LV.lenis.scrollTo(y, { duration: 0.8 });
      else window.scrollTo({ top: y, behavior: LV.motionOK() ? "smooth" : "auto" });
    });
  }

  /* ---------- Painel de detalhes (#p=ID) ---------- */
  var drawer = $("[data-drawer]"), panel = $(".drawer__panel", drawer), body = $("[data-dw-body]", drawer);
  var refEl = $("[data-dw-ref]", drawer), wipe = $(".drawer__wipe", drawer), copyMsg = $("[data-copy-msg]", drawer);
  var lastFocus = null, openId = null, closeTl = null, closing = false, fechandoPeloHistorico = false;
  function depth() { return (history.state && history.state.lvDepth) || 0; }

  function pageURL(id) { return location.href.split("#")[0] + "#p=" + encodeURIComponent(id); }
  function detailHTML(p) {
    var est = BC.estoque(p), rec = BC.temRecorte(p), semi = p.condicao === "seminovo";
    var rows = [["Marca", p.marca], ["Categoria", BC.categoriaNome(p.categoria)], ["Tipo", p.subcategoria]]
      .concat(p.especificacoes || []).concat([["Condição", BC.condicao(p) || "Novo"]]);
    var rel = BC.produtos.filter(function (x) { return x.id !== p.id && x.subcategoria === p.subcategoria; });
    if (rel.length < 3) rel = rel.concat(BC.produtos.filter(function (x) { return x.id !== p.id && x.categoria === p.categoria && rel.indexOf(x) === -1; }));
    rel = rel.slice(0, 3);
    // Ordem: nome → selos → preço e botões (já na primeira tela) → descrição → especificações → mesma linha
    return '<p class="mono dw__crumb">' + esc(BC.categoriaNome(p.categoria)) + " / " + esc(p.subcategoria) + "</p>" +
      '<figure class="dw__fig' + (rec ? "" : " dw__fig--photo") + '"><img src="' + BC.imgProduto(p, true) + '" alt="' + esc(p.marca + " " + p.nome) + '" width="900" height="600" decoding="async"></figure>' +
      '<p class="dw__brand">' + esc(p.marca) + "</p>" +
      '<h2 class="dw__model" id="dw-title"><span class="sr-only">' + esc(p.marca) + " </span>" + LV.nomeHTML(p.nome) + "</h2>" +
      '<div class="dw__badges"><span class="badge badge--' + esc(est.classe) + '">' + esc(est.rotulo) + "</span>" +
        (semi ? '<span class="badge badge--semi">Seminovo</span>' : "") + "</div>" +
      '<div class="dw__buy">' +
        '<p class="dw__price"><span>Preço</span><b>' + (p.preco ? esc(p.preco) : "Consulte") + "</b></p>" +
        '<div class="dw__ctas">' +
          '<a class="btn btn--green btn--lg" href="' + BC.whatsProduto(p) + '" target="_blank" rel="noopener"><svg class="i" aria-hidden="true"><use href="#i-wa"/></svg><span class="st" data-t="Pedir orçamento no WhatsApp">Pedir orçamento no WhatsApp</span><span class="sr-only"> (abre em nova aba)</span></a>' +
          '<a class="btn btn--line" href="' + BC.telLink() + '"><svg class="i" aria-hidden="true"><use href="#i-phone"/></svg><span class="st" data-t="Ligar">Ligar</span></a>' +
          '<button class="btn btn--line dw__copy" type="button" data-copy><svg class="i" aria-hidden="true"><use href="#i-link"/></svg><span class="st" data-t="Copiar link">Copiar link</span></button>' +
        "</div>" +
        '<p class="mono dw__note">Estoque sujeito à disponibilidade. Confirme pelo WhatsApp.</p>' +
      "</div>" +
      '<p class="dw__desc">' + esc(p.descricao || p.resumo) + "</p>" +
      '<dl class="dw__table">' + rows.map(function (r) { return "<div><dt>" + esc(r[0]) + "</dt><dd>" + esc(r[1]) + "</dd></div>"; }).join("") + "</dl>" +
      (rel.length ? '<div class="dw__rel"><p class="mono dw__rel-t">Da mesma linha</p><ul role="list">' + rel.map(function (r) {
        return '<li><a href="#p=' + encodeURIComponent(r.id) + '" data-detail="' + esc(r.id) + '"><img src="' + BC.imgProduto(r, true) + '" alt="" width="64" height="44" loading="lazy">' +
          '<span><small>' + esc(r.marca) + "</small><b>" + esc(r.nome) + '</b></span><svg class="i" aria-hidden="true"><use href="#i-arrow"/></svg></a></li>';
      }).join("") + "</ul></div>" : "");
  }

  function focusables() { return $$('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])', panel).filter(function (el) { return el.offsetParent !== null; }); }

  function openDetail(id, push) {
    var p = BC.porId(id);
    if (!p) return false;
    if (push) { try { history.pushState({ p: id, lvDepth: depth() + 1 }, "", "#p=" + encodeURIComponent(id)); } catch (e) { location.hash = "p=" + encodeURIComponent(id); return true; } }
    if (closeTl) {
      closeTl.kill(); closeTl = null;
      gsap.set(panel, { xPercent: 0 }); gsap.set(".drawer__scrim", { opacity: 1, visibility: "inherit" });
    }
    var already = !drawer.hidden && !closing;
    closing = false;
    if (drawer.hidden) lastFocus = document.activeElement;
    openId = id;
    body.innerHTML = detailHTML(p);
    if (copyMsg) copyMsg.textContent = "";
    refEl.textContent = "Ref. " + LV.pad(BC.produtos.indexOf(p) + 1) + " · " + LV.catCurto(p.categoria);
    document.title = p.marca + " " + p.nome + " | Catálogo Balanças.com";
    drawer.hidden = false;
    panel.scrollTop = 0;
    LV.inertBg(true, [".hdr"]);
    doc.style.overflow = "hidden"; if (LV.lenis) LV.lenis.stop();
    if (LV.motionOK()) {
      if (!already) {
        gsap.set(wipe, { visibility: "visible", skewX: -12 });
        gsap.timeline()
          .fromTo(".drawer__scrim", { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.4 }, 0)
          .fromTo(panel, { xPercent: 100 }, { xPercent: 0, duration: 0.8, ease: "expo.out" }, 0.12)
          .fromTo(wipe, { xPercent: 110 }, { xPercent: -130, duration: 1, ease: "expo.inOut" }, 0)
          .set(wipe, { visibility: "hidden" })
          .from(body.children, { y: 22, opacity: 0, stagger: 0.035, duration: 0.6, ease: "expo.out" }, 0.4);
      } else {
        gsap.from(body.children, { y: 16, opacity: 0, stagger: 0.03, duration: 0.5, ease: "expo.out" });
      }
    }
    panel.focus({ preventScroll: true });
    return true;
  }
  var baseTitle = document.title;
  function closeDetail(fromHistory) {
    if (drawer.hidden || closing) return;
    // Aberto por clique (pushState): fechar = voltar no histórico. O popstate chama sync(),
    // que fecha de vez (mesmo que a entrada de origem também tenha #p=, ex. link direto).
    if (!fromHistory && depth() > 0) { fechandoPeloHistorico = true; history.go(-depth()); return; }
    var closedId = openId;
    openId = null; closing = true;
    var done = function () {
      closeTl = null; closing = false;
      if (openId) return;
      drawer.hidden = true;
      LV.inertBg(false, [".hdr"]);
      doc.style.overflow = ""; if (LV.lenis) LV.lenis.start();
      document.title = baseTitle;
      var card = closedId && grid.querySelector('a[data-detail="' + closedId.replace(/"/g, "") + '"]');
      var tgt = (lastFocus && lastFocus !== document.body && document.contains(lastFocus) && lastFocus.offsetParent !== null) ? lastFocus
        : (card && card.offsetParent !== null ? card : qIn);
      tgt.focus({ preventScroll: tgt === lastFocus });
      lastFocus = null;
    };
    if (!fromHistory && location.hash.indexOf("#p=") === 0) {
      try { history.replaceState(null, "", location.pathname + location.search); } catch (e) { /* ok */ }
    }
    if (LV.motionOK()) {
      closeTl = gsap.timeline({ onComplete: done })
        .to(panel, { xPercent: 100, duration: 0.45, ease: "expo.in" }, 0)
        .to(".drawer__scrim", { autoAlpha: 0, duration: 0.35 }, 0.15);
    } else done();
  }
  function sync() {
    if (fechandoPeloHistorico) {
      fechandoPeloHistorico = false;
      if (/^#p=/.test(location.hash)) { try { history.replaceState(null, "", location.pathname + location.search); } catch (e) { /* ok */ } }
      closeDetail(true);
      return;
    }
    var m = /^#p=(.+)$/.exec(location.hash);
    if (m) { var id = decodeURIComponent(m[1]); if (id !== openId) openDetail(id, false); }
    else closeDetail(true);
  }
  window.addEventListener("popstate", sync);
  window.addEventListener("hashchange", sync);

  document.addEventListener("click", function (e) {
    var a = e.target.closest("a[data-detail]");
    if (a && !e.metaKey && !e.ctrlKey && !e.shiftKey) {
      var href = a.getAttribute("href") || "";
      if (href.charAt(0) === "#") { e.preventDefault(); openDetail(a.getAttribute("data-detail"), true); return; }
    }
    if (e.target.closest("[data-close]")) { closeDetail(false); return; }
    var c = e.target.closest("[data-copy]");
    if (c && openId) {
      var url = pageURL(openId);
      var ok = function () {
        c.classList.add("is-done"); var t = $(".st", c); if (t) t.textContent = "Link copiado";
        if (copyMsg) copyMsg.textContent = "Link do produto copiado.";
        setTimeout(function () { c.classList.remove("is-done"); if (t) t.textContent = "Copiar link"; if (copyMsg) copyMsg.textContent = ""; }, 2200);
      };
      if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(url).then(ok, function () { fallbackCopy(url); ok(); });
      else { fallbackCopy(url); ok(); }
    }
  });
  function fallbackCopy(t) {
    // A cópia usa um campo temporário dentro do painel; depois o foco volta para o botão (o Esc continua fechando)
    var prev = document.activeElement;
    var ta = document.createElement("textarea"); ta.value = t; ta.setAttribute("readonly", ""); ta.setAttribute("aria-hidden", "true"); ta.tabIndex = -1;
    ta.style.position = "fixed"; ta.style.opacity = "0"; ta.style.pointerEvents = "none";
    (panel || document.body).appendChild(ta); ta.select(); try { document.execCommand("copy"); } catch (e) { /* ok */ } ta.remove();
    if (prev && prev !== document.body && prev.focus) prev.focus({ preventScroll: true });
  }
  drawer.addEventListener("keydown", function (e) {
    if (e.key === "Escape") { e.preventDefault(); closeDetail(false); return; }
    if (e.key !== "Tab") return;
    var f = focusables(); if (!f.length) return;
    var first = f[0], last = f[f.length - 1];
    if (e.shiftKey && (document.activeElement === first || document.activeElement === panel)) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  });

  /* ---------- Barra de filtros "grudada" ---------- */
  var jumpOn = false;
  function stuck() {
    if (!fbar) return;
    var r = fbar.getBoundingClientRect();
    fbar.classList.toggle("is-stuck", r.top <= LV.hdr() + 1);
    // Celular (barra não fixa): atalho "Filtrar / buscar" aparece quando a barra sai da tela
    if (jump) {
      var gone = r.bottom < LV.hdr() && grid.getBoundingClientRect().bottom > window.innerHeight * 0.5;
      jump.classList.toggle("is-on", gone);
      jump.tabIndex = gone ? 0 : -1;
      if (gone !== jumpOn) { jumpOn = gone; scrollPad(); }
    }
  }
  window.addEventListener("scroll", stuck, { passive: true });
  // Distância que o navegador reserva no topo ao levar o foco do teclado para a tela:
  // cabeçalho + barra de filtros (desktop) ou + pílula "Filtrar / buscar" (celular)
  function scrollPad() {
    var wide = wideMQ.matches;
    var extra = wide && fbar ? fbar.offsetHeight + 16 : (jumpOn && jump ? jump.offsetHeight + 24 : 16);
    doc.style.scrollPaddingTop = (LV.hdr() + extra) + "px";
  }
  scrollPad(); window.addEventListener("resize", scrollPad);
  if (jump) jump.addEventListener("click", function (e) {
    e.preventDefault();
    var y = fbar.getBoundingClientRect().top + window.scrollY - LV.hdr();
    if (LV.lenis) LV.lenis.scrollTo(y, { duration: 1 }); else window.scrollTo({ top: y, behavior: LV.motionOK() ? "smooth" : "auto" });
    qIn.focus({ preventScroll: true });
  });

  /* ---------- Início ---------- */
  readURL();
  qIn.value = state.q; qClear.hidden = !state.q;
  if (stock) stock.checked = state.estoque;
  renderTabs(); renderSubs(); apply(false);
  mostraAtivos();
  stuck();
  sync();

  /* ---------- Animações do catálogo ---------- */
  LV.onMotion(function () {
    // A barra de filtros só entra animada se ainda não estava à vista (GSAP tardio, âncora, recarregar no meio)
    var fbarIn = document.querySelector(".fbar__in");
    if (!doc.classList.contains("via-vt") && !(LV.jaVisivel && fbarIn && LV.jaVisivel(fbarIn))) gsap.from(".fbar__in > *", { opacity: 0, y: 16, stagger: 0.08, duration: 0.9, ease: "expo.out", delay: 0.8 });
    // Fichas abaixo da dobra entram em lotes; as que já estão na tela não somem e reaparecem
    var cards = $$(".pgrid__i:not([hidden]) > .spec", grid).filter(function (c) { return c.getBoundingClientRect().top > window.innerHeight; });
    if (cards.length) {
      gsap.set(cards, { opacity: 0, y: 40 });
      ScrollTrigger.batch(cards, { start: "top 94%", end: "max", once: true, batchMax: 8, onEnter: function (b) { LV.reveal(b, 0.9); } });
    }
  });
})();
