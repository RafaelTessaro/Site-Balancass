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
  var INDICE = Object.create(null);   // mapas por código sem protótipo: um código "constructor" não engana
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
    state.cat = cat && temProdutos(cat) ? cat : "todos";
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
    bordas(tabsBox); bordas(subsBox);
  }
  // Faixa rolável com mais itens fora da tela: a borda daquele lado esmaece (com 4 ou mais categorias,
  // no celular, as abas não cabem numa linha). Só classes; o CSS (catalogo.css) faz o resto.
  function bordas(box) {
    if (!box) return;
    var max = box.scrollWidth - box.clientWidth;
    box.classList.toggle("is-mais-esq", max > 1 && box.scrollLeft > 4);
    box.classList.toggle("is-mais-dir", max > 1 && box.scrollLeft < max - 4);
  }
  [tabsBox, subsBox].forEach(function (box) {
    if (box) box.addEventListener("scroll", function () { bordas(box); }, { passive: true });
  });
  window.addEventListener("resize", function () { bordas(tabsBox); bordas(subsBox); });

  /* ---------- Abas (categorias) ----------
     Uma aba por categoria do catálogo (o painel cria, renomeia, apaga e muda a ordem), na ordem de
     produtos.js. Categoria sem produto no site (nova, ou com tudo escondido) não ganha aba. */
  function nProdutos(n) { return n === 1 ? "1 produto" : n + " produtos"; }
  function temProdutos(id) { return !!BC.categoria(id) && BC.contar({ categoria: id }) > 0; }
  function renderTabs() {
    var cats = [{ id: "todos", nome: "Todos" }].concat(BC.categorias.filter(function (c) { return temProdutos(c.id); }));
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
    apply(true, false, renderSubs);   // os tipos mudam DEPOIS de o Flip ler a tela (a barra pode mudar de altura)
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
    if (state.cat !== "todos") setCat("todos"); else apply(true, false, renderSubs);
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

  /* Troca animada (GSAP Flip). Quem fica desliza até o lugar novo; quem sai esmaece rápido onde estava
     (fora do fluxo: absoluteOnLeave); quem entra sobe depois que a saída quase acabou — os textos de
     uma ficha que sai e de uma que entra no mesmo lugar não se misturam. A caixa da grade (.cat__body:
     grade, aviso e estado vazio) fica com a altura que está na tela e vai até a altura nova numa tween
     só: a seção de baixo (Kits) anda uma vez, suave, só a diferença real. (Com todas as fichas
     absolutas, a grade ficava com altura 0 durante a troca: os Kits subiam até a barra de filtros e
     desciam de volta no fim.) Troca nova no meio de outra: o Flip.getState lê o que está na tela e
     termina a anterior; a caixa continua da altura do momento. */
  var caixa = grid.closest(".cat__body") || panelBox || grid;
  var anim = null, trocando = false;
  var SAI = 0.2, ENTRA = 0.16;   // saída curta; a entrada começa quando a saída já está quase em 0
  function fimAnim() {
    if (trocando) return;   // terminada à força por uma troca nova: ela segura a caixa e mede de novo
    anim = null;
    $$(".pgrid__i", grid).forEach(function (li) {   // quem saiu volta ao [hidden] de sempre
      if (li._lvSai) { li._lvSai = false; li.style.display = ""; li.hidden = true; li.removeAttribute("inert"); }
    });
    caixa.style.height = "";
    if (LV.lenis) LV.lenis.resize();
    LV.refresh();
  }
  /* Altura natural da caixa sem soltar a altura travada. (Soltar para medir encolhia a página por um
     instante: o navegador puxava a rolagem para cima e um segundo clique rápido caía em outro lugar.)
     Pelos offsets, que ignoram os transforms das animações. */
  function alturaNatural() {
    var cs = getComputedStyle(caixa), num = function (v) { return parseFloat(v) || 0; };
    var pb = num(cs.paddingBottom) + num(cs.borderBottomWidth);
    var soma = pb > 0 || /flex|grid/.test(cs.display);   // senão a margem de baixo do último escapa da caixa
    var fundo = caixa.clientTop + num(cs.paddingTop);
    Array.prototype.forEach.call(caixa.children, function (el) {
      if (!el.getClientRects().length) return;
      var s = getComputedStyle(el);
      if (s.position === "absolute" || s.position === "fixed") return;
      var top = el.offsetParent === caixa ? el.offsetTop + caixa.clientTop : el.offsetTop - caixa.offsetTop;
      fundo = Math.max(fundo, top + el.offsetHeight + (soma ? num(s.marginBottom) : 0));
    });
    return fundo + pb;
  }

  // antes(): mudança da barra de filtros (os tipos da categoria) feita depois de ler a tela
  function apply(animate, aoInicio, antes) {
    var lista = filtrar({ categoria: state.cat, subcategoria: state.sub, texto: state.q, somenteEstoque: state.estoque });
    var show = Object.create(null); lista.forEach(function (p) { show[p.id] = true; });
    var all = $$(".pgrid__i", grid);
    var vazio = lista.length === 0;
    var naGrade = function (li) { return li.hasAttribute("data-cta") ? !vazio : !!show[li.getAttribute("data-id")]; };
    var sat = /(^|\s)(cf-?e\s*)?sat(\s|$)/.test(BC.normaliza(state.q));
    // Mesmas fichas de antes (ex.: mais uma letra na busca): nada a animar, e uma troca em curso segue
    var igual = empty.hidden === !vazio && (!satNote || satNote.hidden === !sat) &&
      all.every(function (li) { return naGrade(li) === !(li.hidden || li._lvSai); });
    var useFlip = animate && motion() && !igual;
    var rolou = animate ? voltaAoInicio(aoInicio) : false;
    var flipState = null, h0 = 0, topo0 = 0, vazioAntes = !empty.hidden;
    if (useFlip) {
      var r0 = caixa.getBoundingClientRect();
      h0 = r0.height; topo0 = r0.top;              // a altura na tela (no meio de uma troca, a da tween)
      trocando = true;
      // Só quem fica, quem entra e quem sai à vista. Quem sai fora da tela some já, fora do Flip
      // (saindo de "Todos" eram umas 40 fichas postas em absoluto: a troca travava o dobro do tempo).
      var vh = window.innerHeight;
      var aVista = function (li) { var r = li.getBoundingClientRect(); return r.height > 0 && r.bottom > -80 && r.top < vh + 80; };
      flipState = Flip.getState(all.filter(function (li) { return naGrade(li) || (!li.hidden && aVista(li)); }));   // lê a tela e termina a troca anterior
      if (anim) anim.progress(1).kill();
      trocando = false;
      caixa.style.height = h0 + "px";
    } else if (anim && !igual) {                   // sem movimento no meio de uma troca: termina ela já
      trocando = true; anim.progress(1).kill(); trocando = false; anim = null;
      caixa.style.height = "";
    }
    if (antes) antes();
    if (useFlip) {
      // A barra mudou de altura (outra fileira de tipos): a caixa compensa, e a seção de baixo não salta
      h0 = Math.max(0, h0 - (caixa.getBoundingClientRect().top - topo0));
      caixa.style.height = h0 + "px";
    }
    if (!igual) all.forEach(function (li) {
      var on = naGrade(li), es = flipState && !on && flipState.getElementState(li);
      li._lvSai = !!(es && es.isVisible);
      if (li._lvSai) {
        // Sai esmaecendo: escondida só pelo style (o [hidden] do CSS é !important e o Flip não a mostraria)
        li.hidden = false; li.style.display = "none"; li.setAttribute("inert", "");
      } else {
        li.hidden = !on; li.style.display = ""; li.removeAttribute("inert");
      }
    });
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
    if (satNote) satNote.hidden = !sat;
    writeURL();
    if (animate) mostraAtivos();
    if (animate && vazio && !rolou) showEmpty();
    if (useFlip) {
      var h1 = alturaNatural();   // altura nova (quem sai já está fora do fluxo)
      anim = Flip.from(flipState, {
        duration: 0.6, ease: "power3.inOut", absoluteOnLeave: true, stagger: 0.012, nested: true,
        zIndex: 1,   // quem fica passa POR CIMA de quem entra ou sai (opaca: um texto nunca aparece através do outro)
        onEnter: function (els) {
          return gsap.fromTo(els, { opacity: 0, y: 30, skewX: -6 }, { opacity: 1, y: 0, skewX: 0, duration: 0.6, ease: "expo.out", delay: ENTRA,
            stagger: function (i) { return Math.min(i, 12) * 0.03; } });   // cascata nas primeiras; as de baixo da dobra não atrasam o fim
        },
        onLeave: function (els) {
          return gsap.fromTo(els, { opacity: function (i, el) { return flipState.getProperty(el, "opacity"); } },
            { opacity: 0, scale: 0.94, duration: SAI, ease: "power2.out" });
        },
        onComplete: fimAnim
      });
      anim.to(caixa, { height: h1, duration: 0.6, ease: "power3.inOut" }, 0);
      all.forEach(function (li) {
        // Ficha que estava entrando (meio transparente) e fica: termina de aparecer, sem saltar para 1
        var es = !li.hidden && !li._lvSai && flipState.getElementState(li);
        if (es && es.isVisible && es.opacity < 0.98) anim.fromTo(li, { opacity: es.opacity }, { opacity: 1, duration: 0.35, ease: "power1.out" }, 0);
      });
      if (vazio && !vazioAntes) anim.fromTo(empty.children, { opacity: 0, y: 24 }, { opacity: 1, y: 0, duration: 0.6, ease: "expo.out", stagger: 0.05 }, ENTRA);
      // fichas que entram na grade já ficam visíveis (podiam estar esperando a revelação por rolagem)
      var vis = $$(".pgrid__i:not([hidden]) > .spec", grid).filter(function (s) { return !s.parentNode._lvSai; });
      if (vis.length) gsap.set(vis, { opacity: 1, y: 0 });
    } else if (!anim) {
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
    // Mesma subcategoria DA MESMA categoria (duas categorias podem ter subcategorias com o mesmo nome)
    var rel = BC.produtos.filter(function (x) { return x.id !== p.id && x.categoria === p.categoria && x.subcategoria === p.subcategoria; });
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
          '<a class="btn btn--green btn--lg" href="' + BC.whatsProduto(p) + '" target="_blank" rel="noopener"><svg class="i" aria-hidden="true"><use href="#i-wa"/></svg><span class="st" data-t="Solicitar orçamento no WhatsApp">Solicitar orçamento no WhatsApp</span><span class="sr-only"> (abre em nova aba)</span></a>' +
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

  // Modelos que saíram de linha → o modelo atual que entrou no lugar deles (as trocas da atualização do
  // catálogo de outubro de 2026, as mesmas dos data-ou dos kits). Um link antigo já compartilhado
  // (WhatsApp, "Copiar link") mostra o modelo atual, quando ele está no catálogo e no site.
  // código antigo → [código do modelo atual, nome do modelo antigo (o mesmo nome_antigo do pacote)]
  var SUCESSORES = {
    "argox-os-2140": ["argox-os-214-pro", "Argox OS-2140"],
    "zebra-zt230": ["zebra-zt231", "Zebra ZT230"],
    "datalogic-quickscan-qw2100": ["datalogic-quickscan-qw2500", "Datalogic QuickScan Lite QW2100"],
    "elgin-el4200": ["elgin-el8600ia", "Elgin EL4200"],
    "gertec-tc504": ["gertec-tc506e-2d", "Gertec Terminal TC 504"],
    "gertec-g2": ["gertec-busca-preco-2d", "Gertec Busca Preço G2-E"],
    "gertec-mt-720": ["gertec-mt-721", "Gertec Microterminal MT 720"]
  };

  // Link de um produto que não está mais no catálogo (excluído, escondido ou com código novo)
  function missingHTML(id) {
    var nome = String(id || "").toLowerCase().replace(/[^a-z0-9-]+/g, "").slice(0, 60).replace(/-+/g, " ").trim();
    var suc = Object.prototype.hasOwnProperty.call(SUCESSORES, id) ? SUCESSORES[id] : null;
    var novo = suc ? BC.porId(suc[0]) : null;
    if (novo) {
      nome = suc[1];
      var msgNovo = "Olá! Abri um link do site (produto “" + nome + "”, que saiu de linha) e vi o " + novo.marca + " " + novo.nome + ". Podem me passar o preço e a disponibilidade?";
      return '<p class="mono dw__crumb">Catálogo</p>' +
        '<h2 class="dw__model" id="dw-title">Este modelo saiu de linha</h2>' +
        '<p class="dw__desc">O fabricante não vende mais este modelo (“' + esc(nome) + "”). No lugar dele, temos o " + esc(novo.marca + " " + novo.nome) + ".</p>" +
        '<div class="dw__rel"><p class="mono dw__rel-t">Modelo atual</p><ul role="list"><li><a href="#p=' + encodeURIComponent(novo.id) + '" data-detail="' + esc(novo.id) + '">' +
          '<img src="' + BC.imgProduto(novo, true) + '" alt="" width="64" height="44" loading="lazy"><span><small>' + esc(novo.marca) + "</small><b>" + esc(novo.nome) + '</b></span><svg class="i" aria-hidden="true"><use href="#i-arrow"/></svg></a></li></ul></div>' +
        '<div class="dw__buy"><div class="dw__ctas">' +
          '<a class="btn btn--green btn--lg" href="' + BC.whatsLink(msgNovo) + '" target="_blank" rel="noopener"><svg class="i" aria-hidden="true"><use href="#i-wa"/></svg><span class="st" data-t="Solicitar orçamento no WhatsApp">Solicitar orçamento no WhatsApp</span><span class="sr-only"> (abre em nova aba)</span></a>' +
          '<button class="btn btn--line" type="button" data-close><span class="st" data-t="Ver o catálogo">Ver o catálogo</span><svg class="i" aria-hidden="true"><use href="#i-arrow"/></svg></button>' +
        "</div></div>";
    }
    var msg = "Olá! Abri um link do site" + (nome ? " (produto “" + nome + "”)" : "") + " que não está mais no catálogo. Vocês ainda têm esse produto ou um parecido?";
    return '<p class="mono dw__crumb">Catálogo</p>' +
      '<h2 class="dw__model" id="dw-title">Produto fora do catálogo</h2>' +
      '<p class="dw__desc">Este produto não está mais no catálogo do site' + (nome ? " (“" + esc(nome) + "”)" : "") + ". Fale com a gente: conferimos se ainda temos ou indicamos um parecido.</p>" +
      '<div class="dw__buy"><div class="dw__ctas">' +
        '<a class="btn btn--green btn--lg" href="' + BC.whatsLink(msg) + '" target="_blank" rel="noopener"><svg class="i" aria-hidden="true"><use href="#i-wa"/></svg><span class="st" data-t="Falar no WhatsApp">Falar no WhatsApp</span><span class="sr-only"> (abre em nova aba)</span></a>' +
        '<button class="btn btn--line" type="button" data-close><span class="st" data-t="Ver o catálogo">Ver o catálogo</span><svg class="i" aria-hidden="true"><use href="#i-arrow"/></svg></button>' +
      "</div></div>";
  }

  function focusables() { return $$('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])', panel).filter(function (el) { return el.offsetParent !== null; }); }

  function openDetail(id, push) {
    var p = BC.porId(id);
    if (!p && push) return false;   // clique num link interno: sempre é de um produto que existe
    if (push) { try { history.pushState({ p: id, lvDepth: depth() + 1 }, "", "#p=" + encodeURIComponent(id)); } catch (e) { location.hash = "p=" + encodeURIComponent(id); return true; } }
    if (closeTl) {
      closeTl.kill(); closeTl = null;
      gsap.set(panel, { xPercent: 0 }); gsap.set(".drawer__scrim", { opacity: 1, visibility: "inherit" });
    }
    var already = !drawer.hidden && !closing;
    closing = false;
    if (drawer.hidden) lastFocus = document.activeElement;
    openId = id;
    body.innerHTML = p ? detailHTML(p) : missingHTML(id);
    if (copyMsg) copyMsg.textContent = "";
    var saiu = !p && Object.prototype.hasOwnProperty.call(SUCESSORES, id) && !!BC.porId(SUCESSORES[id][0]);
    refEl.textContent = p ? "Ref. " + LV.pad(BC.produtos.indexOf(p) + 1) + " · " + LV.catCurto(p.categoria) : (saiu ? "Saiu de linha" : "Fora do catálogo");
    document.title = (p ? p.marca + " " + p.nome : (saiu ? "Modelo fora de linha" : "Produto fora do catálogo")) + " | Catálogo Balanças.com";
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
      var card = null;
      try { card = closedId && grid.querySelector('a[data-detail="' + closedId.replace(/["\\]/g, "") + '"]'); } catch (e) { /* código estranho no endereço */ }
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
    if (m) {
      var id = m[1];
      try { id = decodeURIComponent(id); } catch (e) { /* endereço com % quebrado: usa como está */ }
      if (id !== openId) openDetail(id, false);
    }
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
