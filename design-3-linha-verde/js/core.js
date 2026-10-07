/* =====================================================================
   Balanças.com — Design 3 "Linha Verde" — núcleo compartilhado
   Preenche dados da empresa (window.BC), cabeçalho, menu, faixas,
   acordeão, formulário, template das fichas e o sistema de animação.
   ===================================================================== */
(function () {
  "use strict";

  var BC = window.BC;
  var E = (BC && BC.empresa) || {};
  var doc = document.documentElement;
  var LV = (window.LV = window.LV || {});
  var hooks = [];

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  LV.$ = $; LV.$$ = $$;

  var reduceMQ = window.matchMedia("(prefers-reduced-motion: reduce)");
  function hasGSAP() { return !!(window.gsap && window.ScrollTrigger); }
  LV.motionOK = function () { return hasGSAP() && !reduceMQ.matches; };
  LV.onMotion = function (fn) { hooks.push(fn); };          // ganchos das páginas (rodam dentro do matchMedia)
  LV.hdr = function () { var h = $("[data-hdr]"); return h ? h.offsetHeight : 72; };
  LV.esc = function (s) { return BC ? BC.escape(s) : String(s == null ? "" : s); };
  LV.pad = function (n) { return (n < 10 ? "0" : "") + n; };

  if (!BC) { doc.classList.remove("is-loading"); return; }

  /* ------------------------------------------------------------------
     1. Dados da empresa → HTML (o HTML já traz os mesmos dados p/ SEO)
     ------------------------------------------------------------------ */
  function setText(sel, val) { if (val == null || val === "") return; $$(sel).forEach(function (el) { el.textContent = val; }); }
  // Leitor de tela: avisa que o link abre em outra aba (WCAG G201)
  var NEW_TAB = " (abre em nova aba)";
  function newTabHint(a) {
    if (a._nt) return; a._nt = true;
    var al = a.getAttribute("aria-label");
    if (al) { if (al.indexOf(NEW_TAB.trim()) === -1) a.setAttribute("aria-label", al + NEW_TAB); return; }
    var sr = document.createElement("span"); sr.className = "sr-only"; sr.textContent = NEW_TAB;
    a.appendChild(sr);
  }
  LV.newTabHint = newTabHint;
  function fill() {
    $$("[data-wa]").forEach(function (a) {
      var msg = a.getAttribute("data-wa");
      a.href = BC.whatsLink(msg || undefined);
      a.target = "_blank"; a.rel = "noopener";
      newTabHint(a);
    });
    $$('a[target="_blank"]').forEach(newTabHint);
    $$("[data-tel]").forEach(function (a) { a.href = BC.telLink(); });
    $$("[data-email]").forEach(function (a) { a.href = BC.emailLink(a.getAttribute("data-email") || ""); });
    $$("[data-mapa]").forEach(function (a) { if (E.mapa) a.href = E.mapa; });
    $$("[data-google-link]").forEach(function (a) { if (E.google && E.google.link) a.href = E.google.link; });
    $$("[data-facebook]").forEach(function (a) {
      if (E.redes && E.redes.facebook) a.href = E.redes.facebook; else a.hidden = true;
    });
    setText("[data-tel-text]", E.telefone);
    setText("[data-email-text]", E.email);
    setText("[data-endereco]", BC.enderecoTexto());
    // "CEP 13500-120" e "Rio Claro/SP" nunca quebram (nem no espaço, nem no hífen)
    $$("[data-endereco]").forEach(function (el) {
      el.innerHTML = LV.esc(el.textContent)
        .replace(/CEP\s*([\d.\-]+)/, '<span class="nobr">CEP $1</span>')
        .replace(/Rio Claro([\/\-]SP)?/, '<span class="nobr">$&</span>');
    });
    setText("[data-referencia]", BC.referenciaEndereco ? BC.referenciaEndereco() : "");
    setText("[data-ipem]", E.ipem);
    setText("[data-cnpj]", E.cnpj);
    setText("[data-razao]", E.razaoSocial);
    setText("[data-desde]", E.desde);
    setText("[data-exp]", E.anosExperiencia);
    setText("[data-ano]", new Date().getFullYear());
    setText("[data-anos]", BC.anosDesde());
    if (E.google) { setText("[data-google-nota]", E.google.nota); setText("[data-google-qtd]", E.google.avaliacoes); }
    setText("[data-total-produtos]", BC.produtos.length);
    setText("[data-total-categorias]", (BC.categorias || []).length);

    // Horários
    $$('[data-render="horarios"]').forEach(function (ul) {
      if (!E.horarios || !E.horarios.length) return;
      ul.innerHTML = E.horarios.map(function (h) {
        return "<li><span>" + LV.esc(h.dias) + "</span><span>" + LV.esc(h.horas) + "</span></li>";
      }).join("");
    });
    // Marcas (faixa)
    $$('[data-render="marcas"]').forEach(function (ul) {
      if (!E.marcas || !E.marcas.length) return;
      ul.innerHTML = E.marcas.map(function (m) { return "<li>" + LV.esc(m) + "</li>"; }).join("");
    });
    // Clientes
    $$('[data-render="clientes"]').forEach(function (ul) {
      if (!E.clientes || !E.clientes.length) return;
      if (ul.children.length === E.clientes.length) return;   // o HTML já traz os logos com width/height
      ul.innerHTML = E.clientes.map(function (c) {
        // logos quadrados ganham mais altura na ficha; o Mercado Qualidade usa a versão recortada desta pasta
        var src = c.logo === "qualidade.webp" ? "img/clientes/qualidade.webp" : BC.img("clientes/" + c.logo);
        var sq = /brasil-frios|camargo/.test(c.logo) ? " clients__i--sq" : "";
        return '<li class="clients__i' + sq + '"><img src="' + src + '" alt="' + LV.esc(c.nome) + '" loading="lazy" decoding="async"></li>';
      }).join("");
    });
    // Depoimentos
    $$('[data-render="depoimentos"]').forEach(function (ul) {
      if (!E.depoimentos || !E.depoimentos.length) return;
      ul.innerHTML = E.depoimentos.map(function (d) {
        return '<li class="quote" data-rv><blockquote><p>' + LV.esc(d.texto) + '</p></blockquote>' +
          '<p class="quote__by"><b>' + LV.esc(d.nome) + "</b> · " + LV.esc(d.empresa) + "</p></li>";
      }).join("");
    });
    // Equipe (monogramas)
    $$('[data-render="equipe"]').forEach(function (ul) {
      if (!E.equipe || !E.equipe.length) return;
      ul.innerHTML = E.equipe.map(function (p) {
        var ini = p.nome.split(/\s+/).filter(Boolean);
        ini = (ini[0] || "").charAt(0) + (ini.length > 1 ? ini[ini.length - 1].charAt(0) : "");
        return '<li class="member" data-rv><span class="member__mono" aria-hidden="true">' + LV.esc(ini.toUpperCase()) + "</span>" +
          '<p class="member__name">' + LV.esc(p.nome) + '</p><p class="member__role mono">' + LV.esc(p.cargo) + "</p>" +
          '<p class="member__txt">' + LV.esc(p.texto) + "</p></li>";
      }).join("");
    });
  }

  /* ------------------------------------------------------------------
     2. Ficha técnica de produto (home + catálogo)
     ------------------------------------------------------------------ */
  var CAT_CURTO = { balancas: "Balanças", automacao: "Automação", informatica: "Informática" };
  LV.catCurto = function (id) { return CAT_CURTO[id] || BC.categoriaNome(id); };

  // Valores curtos para a ficha: só o trecho antes de " (" ou ";" e no máximo ~30 caracteres
  function corta(v, max, seps) {
    v = String(v == null ? "" : v).split(" (")[0].split(";")[0].trim();
    for (var i = 0; i < seps.length && v.length > max; i++) v = v.split(seps[i])[0].trim();
    if (v.length <= max) return v;
    var c = v.slice(0, max), sp = c.lastIndexOf(" ");
    return (sp > max * 0.3 ? c.slice(0, sp) : c).replace(/[,\s/–:-]+$/, "") + "…";
  }
  // Valor curto para a ficha. Não corta variantes (" ou ", ", "): na capacidade mostra "Até N kg";
  // nos demais, reticências só se passar de 38 caracteres.
  LV.curto = function (v, chave) {
    v = String(v == null ? "" : v).replace(/\s*\([^)]*\)/g, "").split(";")[0].trim();
    if (/^capacidade/i.test(chave || "")) {
      var kg = (v.match(/\d+(?:,\d+)?(?=\s*kg)/g) || []).map(function (s) { return parseFloat(s.replace(",", ".")); });
      if (kg.length > 1 && / ou |, /.test(v)) return "Até " + String(Math.max.apply(null, kg)).replace(".", ",") + " kg";
    }
    if (v.length <= 38) return v;
    var c = v.slice(0, 38), sp = c.lastIndexOf(" ");
    return (sp > 12 ? c.slice(0, sp) : c).replace(/[,\s/–:-]+$/, "") + "…";
  };
  LV.chaveCurta = function (k) { return corta(k, 26, [" / "]); };
  LV.spec = function (p, chave) {
    var s = (p.especificacoes || []).filter(function (r) { return String(r[0]).toLowerCase().indexOf(chave) === 0; })[0];
    return s ? s[1] : "";
  };

  // Imagem da ficha: variante de 480 px em img/p480/ (r = recorte, f = foto) para a moldura de ~300 px.
  // Só entra no srcset o que existe nesta lista [largura da variante, largura do original];
  // produto novo sem variante usa só o original.
  var P480 = {"r/2098.webp":[480,900],"r/8217.webp":[480,882],"r/9094plus.webp":[480,785],"r/allmidia.webp":[480,900],"r/argox.webp":[480,609],"r/balmak-one.webp":[480,900],"r/balmak-orion2.webp":[480,900],"r/balmak.webp":[480,900],"r/bck30.webp":[480,886],"r/bematech-sat.webp":[480,836],"r/centrium-pc.webp":[480,900],"r/el4200.webp":[480,685],"r/elgin-i9.webp":[480,805],"r/elgin-smart.webp":[480,873],"r/epson-t20.webp":[480,755],"r/fatiador.webp":[480,900],"r/gavetabema.webp":[480,900],"r/gertec504.webp":[480,732],"r/l42pro.webp":[480,865],"r/menno.webp":[480,900],"r/mit.webp":[480,900],"r/mt720.webp":[480,882],"r/nobreak-apc.webp":[480,598],"r/one-pesadora.webp":[480,900],"r/prix3fit.webp":[480,900],"r/prix3plus.webp":[480,900],"r/prix4due.webp":[480,809],"r/prix4uno.webp":[480,900],"r/prix5.webp":[480,900],"r/sat-custom.webp":[480,891],"r/sat-jetway.webp":[480,874],"r/sat-tanca.webp":[480,718],"r/sko44.webp":[480,886],"r/tanca.webp":[480,742],"r/tec44.webp":[480,900],"r/tl120.webp":[480,599],"r/tl900.webp":[480,631],"r/uni350.webp":[480,809],"r/w300.webp":[480,900],"r/zebra.webp":[480,785],"f/2098.webp":[480,900],"f/8217.webp":[480,882],"f/9094plus.webp":[480,785],"f/allmidia.webp":[480,900],"f/argox.webp":[480,609],"f/balmak-one.webp":[480,900],"f/balmak-orion2.webp":[480,900],"f/balmak.webp":[480,900],"f/bck30.webp":[480,886],"f/bematech-sat.webp":[480,836],"f/centrium-pc.webp":[480,900],"f/el4200.webp":[480,685],"f/elgin-i9.webp":[480,805],"f/elgin-smart.webp":[480,873],"f/epson-t20.webp":[480,755],"f/fatiador.webp":[480,900],"f/gavetabema.webp":[480,900],"f/gertec504.webp":[480,732],"f/l42pro.webp":[480,865],"f/menno.webp":[480,900],"f/mit.webp":[480,900],"f/mt720.webp":[480,882],"f/nobreak-apc.webp":[480,598],"f/one-pesadora.webp":[480,900],"f/prix3fit.webp":[480,900],"f/prix3plus.webp":[480,900],"f/prix4due.webp":[480,809],"f/prix4uno.webp":[480,900],"f/prix5.webp":[480,900],"f/sat-custom.webp":[480,891],"f/sat-jetway.webp":[480,874],"f/sat-tanca.webp":[480,718],"f/sko44.webp":[480,886],"f/tanca.webp":[480,742],"f/tec44.webp":[480,900],"f/tl120.webp":[480,599],"f/tl900.webp":[480,631],"f/uni350.webp":[480,809],"f/w300.webp":[480,900],"f/zebra.webp":[480,785]};
  LV.imgTag = function (p, eager, sizes) {
    var src = BC.imgProduto(p, true), file = src.split("/").pop(), rec = BC.temRecorte(p);
    var key = (rec ? "r/" : "f/") + file, v = P480[key];
    var set = v ? ' srcset="img/p480/' + key + " " + v[0] + "w, " + src + " " + v[1] + 'w" sizes="' + (sizes || "(max-width: 700px) 86vw, 340px") + '"' : "";
    return '<img src="' + src + '"' + set + ' alt="' + LV.esc(p.marca + " " + p.nome) + '" width="900" height="600"' +
      (eager ? "" : ' loading="lazy"') + ' decoding="async">';
  };

  LV.card = function (p, n, opts) {
    opts = opts || {};
    var esc = LV.esc, est = BC.estoque(p), rec = BC.temRecorte(p);
    var specs = (p.especificacoes || []).length ? p.especificacoes : [["Linha", p.subcategoria]];
    var rows = specs.slice(0, opts.rows || 3).map(function (r) { return [LV.chaveCurta(r[0]), LV.curto(r[1], r[0])]; });
    var href = (opts.detailBase || "") + "#p=" + encodeURIComponent(p.id);
    var semi = p.condicao === "seminovo";
    return '<article class="spec' + (opts.cls ? " " + opts.cls : "") + '" data-cat="' + esc(p.categoria) + '" data-id="' + esc(p.id) + '">' +
      '<span class="spec__tab">' + esc(LV.catCurto(p.categoria)) + "</span>" +
      '<div class="spec__head"><span>Nº ' + LV.pad(n) + "</span></div>" +
      '<figure class="spec__fig' + (rec ? "" : " spec__fig--photo") + '">' +
        LV.imgTag(p, opts.eager, opts.sizes) +
      "</figure>" +
      '<div class="spec__body">' +
        '<p class="spec__brand">' + esc(p.marca) + "</p>" +
        "<" + (opts.h || "h3") + ' class="spec__model"><span class="sr-only">' + esc(p.marca) + " </span>" + esc(p.nome) + "</" + (opts.h || "h3") + ">" +
        (opts.noSub ? "" : '<p class="spec__sub">' + esc(p.resumo) + "</p>") +
        '<dl class="spec__table">' + rows.map(function (r) {
          return "<div><dt>" + esc(r[0]) + "</dt><dd>" + esc(r[1]) + "</dd></div>";
        }).join("") + "</dl>" +
        '<div class="spec__badges"><span class="badge badge--' + esc(est.classe) + '">' + esc(est.rotulo) + "</span>" +
          (semi ? '<span class="badge badge--semi">Seminovo</span>' : "") + "</div>" +
      "</div>" +
      '<div class="spec__foot">' +
        '<p class="spec__price' + (p.preco ? " has-preco" : "") + '"><span>Preço</span>' + (p.preco ? "<b>" + esc(p.preco) + "</b>" : "<b>Consulte</b>") + "</p>" +
        '<a class="btn btn--line btn--sm" href="' + href + '" data-detail="' + esc(p.id) + '" aria-label="Detalhes: ' + esc(p.marca + " " + p.nome) + '"><span class="st" data-t="Detalhes">Detalhes</span></a>' +
        '<a class="btn btn--green btn--sm" href="' + BC.whatsProduto(p) + '" target="_blank" rel="noopener" aria-label="Pedir orçamento de ' + esc(p.marca + " " + p.nome) + ' pelo WhatsApp (abre em nova aba)">' +
          '<svg class="i" aria-hidden="true"><use href="#i-wa"/></svg><span class="st" data-t="WhatsApp">WhatsApp</span></a>' +
      "</div>" +
    "</article>";
  };

  /* ------------------------------------------------------------------
     3. Cabeçalho: link ativo, barra de progresso, botões flutuantes
     ------------------------------------------------------------------ */
  var progress = $(".hdr__progress");
  var floatBox = $("[data-float]");
  function onScrollBasic() {
    var y = window.scrollY || doc.scrollTop;
    var max = Math.max(1, doc.scrollHeight - window.innerHeight);
    if (progress && !LV.motionOK()) progress.style.transform = "scaleX(" + Math.min(1, y / max) + ")";
    if (floatBox) floatBox.classList.toggle("is-hidden", y < window.innerHeight * 0.55);
  }
  window.addEventListener("scroll", onScrollBasic, { passive: true });

  // Flutuante sai de cena quando os botões do hero, o contato, o rodapé ou a faixa final
  // (que já têm WhatsApp) aparecem — assim ele nunca cobre a frase e os CTAs do topo no celular
  function floatOff() {
    if (!floatBox || !("IntersectionObserver" in window)) return;
    var vis = new Set();
    var io = new IntersectionObserver(function (en) {
      en.forEach(function (e) { if (e.isIntersecting) vis.add(e.target); else vis.delete(e.target); });
      floatBox.classList.toggle("is-off", vis.size > 0);
    }, { rootMargin: "0px 0px -12% 0px" });
    $$(".hero__ctas, .contact, .ftr, .ask").forEach(function (el) { io.observe(el); });
  }

  function activeNav() {
    var links = $$(".nav__a[href^='#']");
    if (!links.length || !("IntersectionObserver" in window)) return;
    var map = {};
    links.forEach(function (a) { var id = a.getAttribute("href").slice(1); if (document.getElementById(id)) map[id] = a; });
    var prod = $('.nav__a[href="produtos.html"]');
    var all = links.concat(prod ? [prod] : []);
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        all.forEach(function (a) { a.removeAttribute("aria-current"); });
        // Seção sem link próprio só limpa a marcação; "Produtos" marca o link do catálogo
        var a = map[en.target.id] || (en.target.id === "produtos" ? prod : null);
        if (a) a.setAttribute("aria-current", "true");
      });
    }, { rootMargin: "-45% 0px -50% 0px" });
    $$("main > section[id]").forEach(function (s) { io.observe(s); });
  }

  // Fundo inerte enquanto um diálogo (menu, painel) está aberto
  LV.inertBg = function (on, extra) {
    ["main", ".ftr", "[data-float]"].concat(extra || []).forEach(function (s) { var el = $(s); if (el) el.inert = !!on; });
  };

  /* ------------------------------------------------------------------
     4. Menu mobile (foco preso, Esc fecha)
     ------------------------------------------------------------------ */
  var menu = $("[data-menu]"), burger = $("[data-burger]"), burgerLabel = $("[data-burger-label]");
  var menuOpen = false;
  function menuFocusables() { return [burger].concat($$("a, button", menu)); }
  function openMenu() {
    if (menuOpen) return; menuOpen = true;
    menu.hidden = false; burger.setAttribute("aria-expanded", "true"); LV.inertBg(true);
    if (burgerLabel) burgerLabel.textContent = "Fechar menu";
    doc.style.overflow = "hidden"; if (LV.lenis) LV.lenis.stop();
    if (LV.motionOK()) {
      gsap.fromTo(".menu__band", { scaleY: 0, transformOrigin: "50% 0%" }, { scaleY: 1, duration: .7, ease: "expo.inOut" });
      gsap.fromTo(".menu__list li", { yPercent: 60, opacity: 0 }, { yPercent: 0, opacity: 1, duration: .7, stagger: .05, ease: "expo.out", delay: .12 });
      gsap.fromTo(".menu__foot", { opacity: 0 }, { opacity: 1, duration: .5, delay: .35 });
    }
    var first = $("a", menu); if (first) first.focus();
  }
  function closeMenu(noFocus) {
    if (!menuOpen) return; menuOpen = false;
    burger.setAttribute("aria-expanded", "false");
    if (burgerLabel) burgerLabel.textContent = "Abrir menu";
    doc.style.overflow = ""; if (LV.lenis) LV.lenis.start();
    menu.hidden = true; LV.inertBg(false);
    if (!noFocus) burger.focus();
  }
  if (menu && burger) {
    burger.addEventListener("click", function () { menuOpen ? closeMenu() : openMenu(); });
    menu.addEventListener("click", function (e) { var a = e.target.closest("a"); if (a) closeMenu(true); });
    document.addEventListener("keydown", function (e) {
      if (!menuOpen) return;
      if (e.key === "Escape") { closeMenu(); return; }
      if (e.key === "Tab") {
        var f = menuFocusables(), i = f.indexOf(document.activeElement);
        if (e.shiftKey && i <= 0) { e.preventDefault(); f[f.length - 1].focus(); }
        else if (!e.shiftKey && i === f.length - 1) { e.preventDefault(); f[0].focus(); }
      }
    });
    window.addEventListener("resize", function () { if (menuOpen && window.innerWidth > 980) closeMenu(true); });
  }

  /* ------------------------------------------------------------------
     5. Âncoras internas com compensação do cabeçalho
     ------------------------------------------------------------------ */
  document.addEventListener("click", function (e) {
    var a = e.target.closest("a[href]");
    if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey) return;
    var href = a.getAttribute("href");
    var hash = href.charAt(0) === "#" ? href : null;
    if (!hash || hash.length < 2 || hash.indexOf("#p=") === 0) return;
    var t = document.getElementById(hash.slice(1));
    if (!t) return;
    e.preventDefault();
    // A distância do cabeçalho (e da aba da seção) vem do scroll-margin-top no CSS
    if (t.id === "inicio") { if (LV.lenis) LV.lenis.scrollTo(0, { duration: 1.4 }); else window.scrollTo({ top: 0, behavior: reduceMQ.matches ? "auto" : "smooth" }); }
    else if (LV.lenis) LV.lenis.scrollTo(t, { duration: 1.4 });
    else t.scrollIntoView({ behavior: reduceMQ.matches ? "auto" : "smooth" });
    try { history.pushState(null, "", hash); } catch (err) { /* file:// em alguns navegadores */ }
    // Leva o foco junto (pular para o conteúdo, menu): o próximo Tab continua dali
    if (!t.hasAttribute("tabindex")) t.setAttribute("tabindex", "-1");
    t.focus({ preventScroll: true });
  });

  /* ------------------------------------------------------------------
     6. Faixas (ticker): duplica a lista, pausa no botão/hover
     ------------------------------------------------------------------ */
  function setupTickers() {
    $$("[data-ticker]").forEach(function (t) {
      var track = $(".ticker__track", t), list = $(".ticker__list", t);
      if (!track || !list || t._lv) return; t._lv = true;
      var base = Array.prototype.slice.call(list.children), guard = 0;
      while (list.scrollWidth < window.innerWidth * 1.1 && guard++ < 8) {
        base.forEach(function (li) { var c = li.cloneNode(true); c.setAttribute("aria-hidden", "true"); list.appendChild(c); });
      }
      var clone = list.cloneNode(true); clone.setAttribute("aria-hidden", "true"); track.appendChild(clone);
      var btn = $("[data-ticker-btn]", t);
      if (btn) btn.addEventListener("click", function () {
        var paused = !t.classList.contains("is-paused");
        t.classList.toggle("is-paused", paused);
        btn.setAttribute("aria-pressed", String(paused));
        var use = $("use", btn); if (use) use.setAttribute("href", paused ? "#i-play" : "#i-pause");
        var sr = $(".sr-only", btn); if (sr) sr.textContent = (paused ? "Continuar" : "Pausar") + sr.textContent.replace(/^(Pausar|Continuar)/, "");
        if (t._tw) paused ? t._tw.pause() : t._tw.resume();
      });
    });
  }

  /* ------------------------------------------------------------------
     7. Acordeão (FAQ)
     ------------------------------------------------------------------ */
  function setupAccordion() {
    $$("[data-acc]").forEach(function (acc) {
      // Fecha as respostas JÁ, sem transição (o CSS desliga a transição enquanto falta .acc--ready),
      // e força o layout agora: assim o ScrollTrigger, criado no DOMContentLoaded, mede a página com a
      // altura final. A transição de abrir/fechar só passa a valer dois quadros depois.
      acc.classList.add("acc--js");
      void acc.offsetHeight;
      requestAnimationFrame(function () { requestAnimationFrame(function () { acc.classList.add("acc--ready"); }); });
      $$(".acc__b", acc).forEach(function (b) {
        var p = document.getElementById(b.getAttribute("aria-controls"));
        if (!p) return;
        p.inert = true;
        var t;
        var refresh = function () { clearTimeout(t); if (window.ScrollTrigger) ScrollTrigger.refresh(); };
        // Recalcula os gatilhos quando a resposta termina de abrir/fechar (com folga caso o
        // transitionend não venha, ex.: movimento reduzido ou aba em segundo plano).
        p.addEventListener("transitionend", function (e) { if (e.target === p && e.propertyName === "grid-template-rows") refresh(); });
        b.addEventListener("click", function () {
          var open = b.getAttribute("aria-expanded") !== "true";
          b.setAttribute("aria-expanded", String(open));
          p.classList.toggle("is-open", open);
          p.inert = !open;
          clearTimeout(t); t = setTimeout(refresh, 700);
        });
      });
    });
  }

  /* ------------------------------------------------------------------
     8. Formulário → mensagem no WhatsApp (sem backend)
     ------------------------------------------------------------------ */
  function setupForm() {
    var f = $("[data-form]"); if (!f) return;
    var err = $("[data-form-err]", f);
    f.addEventListener("submit", function (e) {
      e.preventDefault();
      var nome = f.nome.value.trim(), assunto = f.assunto.value, msg = f.mensagem.value.trim();
      var field = f.nome.closest(".field");
      if (!nome) {
        field.classList.add("is-err"); f.nome.setAttribute("aria-invalid", "true");
        err.textContent = "Escreva o seu nome para a gente saber com quem está falando.";
        f.nome.focus(); return;
      }
      field.classList.remove("is-err"); f.nome.removeAttribute("aria-invalid"); err.textContent = "";
      var texto = "Olá! Meu nome é " + nome + ".\n*Assunto:* " + assunto + (msg ? "\n" + msg : "") + "\n(Mensagem enviada pelo site)";
      var url = BC.whatsLink(texto);
      var w = window.open(url, "_blank");
      if (w) { try { w.opener = null; } catch (err) { /* ok */ } } else { location.href = url; }
    });
  }

  /* ------------------------------------------------------------------
     9. Animação (GSAP + ScrollTrigger + SplitText + Lenis)
     ------------------------------------------------------------------ */
  LV.st = function (trigger, start) { return { trigger: trigger, start: start || "top 88%", end: "max", once: true }; };

  // Revela um lote. Num salto de rolagem (End, barra, Ctrl+F, voltar do WhatsApp) os blocos que já
  // ficaram para trás aparecem direto; só os visíveis animam, com stagger curto.
  LV.reveal = function (b, dur) {
    var vis = b.filter(function (e) { return e.getBoundingClientRect().bottom > 0; });
    var past = b.filter(function (e) { return vis.indexOf(e) < 0; });
    if (past.length) gsap.set(past, { opacity: 1, y: 0, overwrite: true });
    if (vis.length) gsap.to(vis, { opacity: 1, y: 0, duration: dur || 1, ease: "expo.out", stagger: Math.min(0.08, 0.4 / Math.max(1, vis.length)), overwrite: true });
  };

  // Rede de segurança: o que recebe foco por teclado aparece na hora, mesmo antes da animação
  document.addEventListener("focusin", function (e) {
    if (!window.gsap || !e.target.closest) return;
    var el = e.target.closest("[data-rv], .router__a, .seg__row > *, .spec, .flow__st, .ba__row > *, .sat__copy, [data-intro]");
    if (el && +getComputedStyle(el).opacity < 1) gsap.to(el, { opacity: 1, x: 0, y: 0, yPercent: 0, skewX: 0, duration: 0.3, overwrite: true });
  });

  function motion() {
    if (!hasGSAP()) { doc.classList.remove("is-loading"); return; }
    var plugins = [ScrollTrigger];
    if (window.SplitText) plugins.push(SplitText);
    if (window.DrawSVGPlugin) plugins.push(DrawSVGPlugin);
    if (window.Flip) plugins.push(Flip);
    gsap.registerPlugin.apply(gsap, plugins);
    var mm = (LV.mm = gsap.matchMedia());

    mm.add("(prefers-reduced-motion: reduce)", function () {
      doc.classList.remove("is-loading");
      $$("[data-ticker]").forEach(function (t) { t.classList.add("is-static"); });
    });

    mm.add("(prefers-reduced-motion: no-preference)", function () {
      // Rolagem suave
      if (window.Lenis) {
        var lenis = (LV.lenis = new Lenis({ autoRaf: false, lerp: 0.11, wheelMultiplier: 1 }));
        lenis.on("scroll", ScrollTrigger.update);
        var raf = function (t) { lenis.raf(t * 1000); };
        gsap.ticker.add(raf); gsap.ticker.lagSmoothing(0);
        if (doc.style.overflow === "hidden") lenis.stop();   // painel ou menu já aberto antes do CDN chegar
      }

      // Ganchos específicos de cada página (hero, esteira, catálogo…)
      hooks.forEach(function (fn) { try { fn(mm); } catch (e) { if (window.console) console.warn(e); } });
      doc.classList.remove("is-loading");

      // Barra de progresso no cabeçalho
      if (progress) gsap.to(progress, { scaleX: 1, ease: "none", scrollTrigger: { start: 0, end: "max", scrub: 0.3 } });

      // Abas das seções entram pela esquerda
      $$(".sec__tab").forEach(function (tab) {
        gsap.from(tab, { xPercent: -101, duration: 1.1, ease: "expo.out", scrollTrigger: LV.st(tab.parentElement, "top 94%") });
      });

      // Títulos: linhas entram inclinadas a −12° e se endireitam
      if (window.SplitText) {
        // Ao mudar a largura (girar a tela, redimensionar), o SplitText refaz as linhas ~200 ms depois
        // e cada seção muda de altura: recalcula os gatilhos quando isso acontece.
        var resplit;
        $$("[data-split]").forEach(function (el) {
          SplitText.create(el, {
            type: "lines", linesClass: "ln", autoSplit: true,
            onSplit: function (self) {
              if (self._lvSplit) { clearTimeout(resplit); resplit = setTimeout(function () { ScrollTrigger.refresh(); }, 60); }
              self._lvSplit = true;
              return gsap.from(self.lines, {
                yPercent: 55, opacity: 0, skewX: -12, transformOrigin: "0% 100%",
                duration: 1.1, stagger: 0.09, ease: "expo.out", scrollTrigger: LV.st(el, "top 90%")
              });
            }
          });
        });
      }

      // Revelações genéricas
      var rv = $$("[data-rv]");
      if (rv.length) {
        gsap.set(rv, { opacity: 0, y: 40 });
        ScrollTrigger.batch(rv, { start: "top 92%", end: "max", once: true, batchMax: 8, onEnter: LV.reveal });
      }

      // Máscaras em paralelogramo: abrem a partir da barra diagonal
      $$("[data-mask]").forEach(function (el) {
        var s = parseFloat(getComputedStyle(el).getPropertyValue("--s")) || 15;
        var img = $("img", el) || el.firstElementChild;
        var tl = gsap.timeline({ scrollTrigger: LV.st(el, "top 90%") });
        tl.fromTo(el,
          { clipPath: "polygon(" + s + "% 0%, " + s + "% 0%, 0% 100%, 0% 100%)" },
          { clipPath: "polygon(" + s + "% 0%, 100% 0%, " + (100 - s) + "% 100%, 0% 100%)", duration: 1.15, ease: "expo.inOut", clearProps: "clipPath" });
        if (img) tl.from(img, { scale: 1.25, duration: 1.4, ease: "expo.out" }, 0.15);
      });

      // Palavras gigantes atravessam a tela com a rolagem
      $$("[data-drift]").forEach(function (el) {
        gsap.fromTo(el, { xPercent: 4 }, {
          xPercent: -22, ease: "none",
          scrollTrigger: { trigger: el.parentElement, start: "top bottom", end: "bottom top", scrub: true }
        });
      });

      // Contadores (Martian Mono, tabular)
      $$("[data-count], [data-count-from], [data-count-dec]").forEach(function (el) {
        var dec = el.hasAttribute("data-count-dec");
        var to = parseFloat((el.textContent || "0").replace(",", "."));
        if (isNaN(to)) return;
        var from = el.hasAttribute("data-count-from") ? parseFloat(el.getAttribute("data-count-from")) : 0;
        var o = { v: from };
        var fmt = function (v) { return dec ? v.toFixed(1).replace(".", ",") : String(Math.round(v)); };
        gsap.fromTo(o, { v: from }, {
          v: to, duration: 1.8, ease: "power3.out", scrollTrigger: LV.st(el, "top 90%"), immediateRender: false,
          onStart: function () { el.textContent = fmt(from); },
          onUpdate: function () { el.textContent = fmt(o.v); },
          onComplete: function () { el.textContent = fmt(to); }
        });
      });

      // Faixas: GSAP controla a velocidade, a direção e a inclinação
      var tickers = $$("[data-ticker]").map(function (t) {
        var track = $(".ticker__track", t);
        t.classList.add("is-gsap");
        var dur = parseFloat(getComputedStyle(t).getPropertyValue("--dur")) || 36;
        var tw = gsap.to(track, { xPercent: -50, duration: dur, ease: "none", repeat: -1 });
        tw.totalTime(dur * 400);
        t._tw = tw;
        if (t.classList.contains("is-paused")) tw.pause();
        t.addEventListener("mouseenter", function () { if (!t.classList.contains("is-paused")) gsap.to(tw, { timeScale: 0, duration: .5, overwrite: true }); });
        t.addEventListener("mouseleave", function () { if (!t.classList.contains("is-paused")) gsap.to(tw, { timeScale: 1, duration: .5, overwrite: true }); });
        return { el: t, tw: tw, skew: t.hasAttribute("data-skew") ? gsap.quickTo(track, "skewX", { duration: 0.45, ease: "power3.out" }) : null, speed: parseFloat(t.getAttribute("data-speed")) || 1 };
      });
      var idle;
      ScrollTrigger.create({
        start: 0, end: "max",
        onUpdate: function (self) {
          var v = self.getVelocity();
          var sk = gsap.utils.clamp(-12, 12, v / -220);
          var dir = self.direction;
          tickers.forEach(function (k) {
            if (k.skew) k.skew(sk);
            if (!k.el.classList.contains("is-paused") && !k.el.matches(":hover")) {
              gsap.to(k.tw, { timeScale: dir * (1 + Math.min(4, Math.abs(v) / 900)) * k.speed, duration: 0.3, overwrite: true });
            }
          });
          clearTimeout(idle);
          idle = setTimeout(function () {
            tickers.forEach(function (k) {
              if (k.skew) k.skew(0);
              if (!k.el.classList.contains("is-paused") && !k.el.matches(":hover")) gsap.to(k.tw, { timeScale: dir * k.speed, duration: 0.8, overwrite: true });
            });
          }, 140);
        }
      });

      // Botões magnéticos (só mouse)
      if (window.matchMedia("(hover: hover) and (pointer: fine)").matches) {
        $$("[data-magnet]").forEach(function (b) {
          var qx = gsap.quickTo(b, "x", { duration: 0.5, ease: "power3.out" }), qy = gsap.quickTo(b, "y", { duration: 0.5, ease: "power3.out" });
          b.addEventListener("mousemove", function (e) { var r = b.getBoundingClientRect(); qx((e.clientX - r.left - r.width / 2) * 0.25); qy((e.clientY - r.top - r.height / 2) * 0.35); });
          b.addEventListener("mouseleave", function () { qx(0); qy(0); });
        });
      }

      // Reajuste após imagens e fontes
      var refresh = function () { ScrollTrigger.refresh(); };
      window.addEventListener("load", refresh);
      if (document.fonts && document.fonts.ready) document.fonts.ready.then(refresh);

      return function () {
        if (LV.lenis) { LV.lenis.destroy(); LV.lenis = null; }
        $$("[data-ticker]").forEach(function (t) { t.classList.remove("is-gsap"); t._tw = null; });
      };
    });
  }

  /* ------------------------------------------------------------------
     Início
     ------------------------------------------------------------------ */
  fill();
  setupTickers();
  setupAccordion();
  setupForm();
  activeNav();
  floatOff();
  onScrollBasic();
  // As páginas registram seus ganchos; a animação começa depois que todos os scripts rodaram
  // (inclusive os do CDN, que vêm depois dos locais).
  document.addEventListener("DOMContentLoaded", function () {
    if (LV.beforeMotion) LV.beforeMotion();
    if (LV.motionOK()) motion();
    else { doc.classList.remove("is-loading"); if (hasGSAP()) motion(); }
  });
})();
