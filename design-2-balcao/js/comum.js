/* =====================================================================
   Balanças.com — Design 2 "Balcão" — comum.js
   Dados da empresa, cabeçalho, menu mobile, âncoras e card de produto.
   Roda ANTES das bibliotecas do CDN: tudo que é conteúdo e interação
   funciona mesmo se o GSAP/Lenis demorar ou não carregar.
   O movimento (Lenis, revelações, títulos, contadores) começa em
   D2.iniciarMovimento(), chamado por js/movimento.js depois do CDN.
   ===================================================================== */
(function () {
  "use strict";

  var doc = document;
  var root = doc.documentElement;
  var BC = window.BC || null;
  var E = BC ? BC.empresa : null;
  var gsap = null;
  var ST = null;

  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var fine = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
  var anim = false;

  var D2 = window.D2 = {
    reduce: reduce, fine: fine, anim: false, lenis: null,
    onMovimento: [],
    esc: function (s) { return BC ? BC.escape(s) : String(s == null ? "" : s); }
  };

  function $all(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
  D2.$all = $all;

  /* Ícones decorativos: fora da árvore de acessibilidade */
  function hideIcons(ctx) {
    $all("svg.i:not([aria-hidden])", ctx).forEach(function (s) {
      s.setAttribute("aria-hidden", "true");
      s.setAttribute("focusable", "false");
    });
  }
  hideIcons();

  /* ------------------------------------------------------------------
     1. Dados da empresa (HTML já traz os dados; aqui atualizamos a partir
        de compartilhado/dados/empresa.js)
     ------------------------------------------------------------------ */
  function lowerFirst(s) { return s ? s.charAt(0).toLowerCase() + s.slice(1) : s; }

  function fillEmpresa() {
    if (!E) return;
    var esc = D2.esc;
    var map = {
      telefone: E.telefone,
      email: E.email,
      endereco: BC.enderecoTexto(),
      referencia: BC.referenciaEndereco ? BC.referenciaEndereco() : "",
      cnpj: E.cnpj,
      razao: E.razaoSocial,
      nome: E.nome,
      frase: E.frase,
      ano: String(new Date().getFullYear()),
      desde: E.desde,
      anos: BC.anosDesde(),
      ipem: E.ipem ? lowerFirst(E.ipem) : "",
      "ipem-titulo": E.ipem || "",
      "google-nota": E.google && E.google.nota,
      "google-avaliacoes": E.google && E.google.avaliacoes,
      "marcas-qtd": (E.marcas || []).length || ""
    };
    $all("[data-bc]").forEach(function (el) {
      var k = el.getAttribute("data-bc");
      var v = map[k];
      if (v === undefined || v === null || v === "") return;
      el.textContent = String(v);
      if (el.hasAttribute("data-count-to")) el.setAttribute("data-count-to", String(v));
    });

    // "todas com 5 estrelas" só enquanto a nota for 5,0
    $all("[data-only-5]").forEach(function (s) { s.hidden = !(E.google && String(E.google.nota) === "5,0"); });

    var hrefs = {
      google: E.google && E.google.link,
      mapa: E.mapa,
      facebook: E.redes && E.redes.facebook,
      email: BC.emailLink()
    };
    $all("[data-bc-href]").forEach(function (a) {
      var v = hrefs[a.getAttribute("data-bc-href")];
      if (v) a.setAttribute("href", v);
      else if (a.getAttribute("data-bc-href") === "facebook") a.hidden = true;
    });

    $all("[data-wa]").forEach(function (a) { a.setAttribute("href", BC.whatsLink(a.getAttribute("data-wa") || "")); });
    $all("[data-tel]").forEach(function (a) { a.setAttribute("href", BC.telLink()); });

    if (E.horarios && E.horarios.length) {
      var html = E.horarios.map(function (h) {
        return "<li><span>" + esc(h.dias) + "</span><span>" + esc(h.horas) + "</span></li>";
      }).join("");
      $all("[data-horarios]").forEach(function (ul) { ul.innerHTML = html; });
    }
  }

  /* ------------------------------------------------------------------
     2. "Aberto agora" — calcula pelo horário de Brasília
     ------------------------------------------------------------------ */
  // Feriados nacionais fixos (MM-DD). Municipais e móveis: só depois de confirmar com a loja.
  var FERIADOS = ["01-01", "04-21", "05-01", "09-07", "10-12", "11-02", "11-15", "11-20", "12-25"];

  function parseHorarios() {
    var sched = { 0: [], 1: [], 2: [], 3: [], 4: [], 5: [], 6: [] };
    var list = (E && E.horarios) || [];
    list.forEach(function (h) {
      var d = (BC ? BC.normaliza(h.dias) : String(h.dias).toLowerCase());
      var days = [];
      if (/segunda a sexta/.test(d)) days = [1, 2, 3, 4, 5];
      else if (/segunda a sabado/.test(d)) days = [1, 2, 3, 4, 5, 6];
      else {
        if (/segunda/.test(d)) days.push(1);
        if (/terca/.test(d)) days.push(2);
        if (/quarta/.test(d)) days.push(3);
        if (/quinta/.test(d)) days.push(4);
        if (/sexta/.test(d)) days.push(5);
        if (/sabado/.test(d)) days.push(6);
        if (/domingo/.test(d)) days.push(0);
      }
      var re = /(\d{1,2})h(\d{2})?\s*(?:as|às|a|-|–)\s*(\d{1,2})h(\d{2})?/gi;
      var txt = String(h.horas), m, slots = [];
      while ((m = re.exec(txt))) slots.push([+m[1] * 60 + (+m[2] || 0), +m[3] * 60 + (+m[4] || 0)]);
      days.forEach(function (dd) { sched[dd] = sched[dd].concat(slots); });
    });
    return sched;
  }

  function fmtH(min) { var h = Math.floor(min / 60), m = min % 60; return h + "h" + (m ? String(m).padStart(2, "0") : ""); }
  function pad2(n) { return (n < 10 ? "0" : "") + n; }

  function openStatus() {
    var els = $all("[data-open-status]");
    if (!els.length || !E) return;
    var parts;
    try {
      parts = new Intl.DateTimeFormat("en-US", {
        timeZone: "America/Sao_Paulo", weekday: "short", hour: "numeric", minute: "numeric", hourCycle: "h23",
        year: "numeric", month: "2-digit", day: "2-digit"
      }).formatToParts(new Date());
    } catch (e) { return; }
    function get(t) { for (var i = 0; i < parts.length; i++) if (parts[i].type === t) return parts[i].value; return ""; }
    var wd = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].indexOf(get("weekday"));
    var now = (+get("hour") % 24) * 60 + (+get("minute"));
    var y = +get("year"), mo = +get("month"), da = +get("day");
    var S = parseHorarios();
    function feriado(k) {
      var d = new Date(Date.UTC(y, mo - 1, da + k));
      return FERIADOS.indexOf(pad2(d.getUTCMonth() + 1) + "-" + pad2(d.getUTCDate())) !== -1;
    }
    function slotsDe(k) { return feriado(k) ? [] : (S[(wd + k) % 7] || []); }
    var feriadoHoje = feriado(0);
    var open = false, msg = "";
    slotsDe(0).forEach(function (s) { if (now >= s[0] && now < s[1]) { open = true; msg = "Aberto agora · até " + fmtH(s[1]); } });
    if (!open) {
      var nomes = ["domingo", "segunda", "terça", "quarta", "quinta", "sexta", "sábado"];
      for (var k = 0; k < 8 && !msg; k++) {
        var d = (wd + k) % 7;
        var slots = slotsDe(k).slice().sort(function (a, b) { return a[0] - b[0]; });
        for (var j = 0; j < slots.length; j++) {
          if (k === 0 && slots[j][0] <= now) continue;
          var quando = k === 0 ? "hoje" : (k === 1 ? "amanhã" : (d === 6 ? "no sábado" : (d === 0 ? "no domingo" : "na " + nomes[d])));
          msg = (feriadoHoje ? "Fechado hoje (feriado) · abre " : "Fechado agora · abre ") + quando + " às " + fmtH(slots[j][0]);
          break;
        }
      }
      if (!msg) msg = feriadoHoje ? "Fechado hoje (feriado)" : "Fechado agora";
    }
    els.forEach(function (el) {
      el.classList.toggle("is-open", open);
      var span = el.querySelector("span");
      if (span) span.textContent = msg;
    });
  }

  /* ------------------------------------------------------------------
     3. Card de produto (home e catálogo)
     ------------------------------------------------------------------ */
  /* Resume uma especificação para o chip do card, sem cortar texto:
     tira observações entre parênteses e o que vem depois de ";".
     Se não couber (≈34 caracteres), o chip não aparece no card. */
  function shortSpec(label, value) {
    var l = String(label || "").split(" / ")[0].split(" (")[0].trim();
    var v = String(value || "").replace(/\s*\([^)]*\)/g, "").replace(/ por segundo/g, "/s").split(";")[0].trim();
    return { l: l, v: v, ok: (l.length + 2 + v.length) <= 34 };
  }
  D2.shortSpec = shortSpec;

  D2.card = function (p, opt) {
    opt = opt || {};
    var esc = D2.esc;
    var base = opt.base || "";
    var href = base + "#p=" + encodeURIComponent(p.id);
    var est = BC.estoque(p);
    var cond = p.condicao === "seminovo" ? BC.condicao(p) : "";
    var nome = p.marca + " " + p.nome;
    var hid = "pn-" + p.id;
    var specs = (p.especificacoes || []).map(function (s) {
      var k = shortSpec(s[0], s[1]);
      k.t = s[0] + ": " + s[1];
      return k;
    }).filter(function (k) { return k.ok; }).slice(0, 2).map(function (k) {
      return '<li title="' + esc(k.t) + '"><span>' + esc(k.l) + ":</span> " + esc(k.v) + "</li>";
    }).join("");
    return '<article class="pcard" data-id="' + esc(p.id) + '" aria-labelledby="' + esc(hid) + '">' +
      '<div class="pcard__media is-flat">' +
        '<span class="pcard__badges"><span class="badge badge--' + esc(est.classe) + '">' + esc(est.rotulo) + "</span>" +
        (cond ? '<span class="badge badge--cond">' + esc(cond) + "</span>" : "") + "</span>" +
        '<span class="pcard__disc" aria-hidden="true"></span>' +
        '<img class="pcard__img" src="' + esc(BC.imgProduto(p, false)) + '" alt="" width="900" height="600" loading="lazy" decoding="async">' +
      "</div>" +
      '<div class="pcard__body">' +
        '<p class="pcard__brand"><b>' + esc(p.marca) + "</b> " + esc(p.subcategoria) + "</p>" +
        '<h3 class="pcard__name" id="' + esc(hid) + '"><a href="' + href + '" data-open="' + esc(p.id) + '">' + esc(p.nome) + "</a></h3>" +
        '<p class="pcard__sum">' + esc(p.resumo) + "</p>" +
        (specs ? '<ul class="chips">' + specs + "</ul>" : "") +
        '<div class="pcard__foot">' +
          '<span class="pcard__price"><small>Preço</small><strong>' + esc(p.preco || "Consulte") + "</strong></span>" +
          '<span class="pcard__actions">' +
            '<a class="pcard__btn" href="' + href + '" data-open="' + esc(p.id) + '" aria-label="Detalhes de ' + esc(nome) + '">Detalhes</a>' +
            '<a class="pcard__btn pcard__btn--wa" href="' + esc(BC.whatsProduto(p)) + '" target="_blank" rel="noopener" aria-label="Pedir ' + esc(nome) + ' no WhatsApp"><svg class="i" aria-hidden="true" focusable="false"><use href="#i-wa"/></svg></a>' +
          "</span>" +
        "</div>" +
      "</div>" +
    "</article>";
  };

  /* ------------------------------------------------------------------
     4. Cabeçalho: estado ao rolar, esconder/mostrar, pílula do menu
     ------------------------------------------------------------------ */
  var hdr = doc.querySelector(".hdr");
  var fab = doc.querySelector("[data-fab]");
  var fabHide = fab && doc.querySelector(".hero, .ast-hero, .cat-hero");

  function initHeader() {
    if (!hdr) return;
    var lastY = window.scrollY, ticking = false;
    function onScroll() {
      var y = window.scrollY;
      hdr.classList.toggle("is-scrolled", y > 8);
      var menuOpen = hdr.classList.contains("is-open");
      if (!menuOpen && y > 640 && y > lastY + 4) hdr.classList.add("is-hidden");
      else if (y < lastY - 4 || y < 640) hdr.classList.remove("is-hidden");
      if (fabHide) fab.classList.toggle("is-away", y < window.innerHeight * 0.45);
      doc.body.classList.toggle("hdr-visible", !hdr.classList.contains("is-hidden"));
      lastY = y; ticking = false;
    }
    window.addEventListener("scroll", function () { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
    onScroll();
    // A partir daqui o fundo do cabeçalho pode ficar transparente no topo
    hdr.classList.add("is-ready");
    // Ao focar algo dentro do cabeçalho, ele volta a aparecer
    hdr.addEventListener("focusin", function () { hdr.classList.remove("is-hidden"); });

    // No fim da página o rodapé já tem os mesmos botões: os flutuantes saem da frente
    var ftr = doc.querySelector(".ftr");
    if (fab && ftr && "IntersectionObserver" in window) {
      new IntersectionObserver(function (en) {
        fab.classList.toggle("is-near-end", en[0].isIntersecting);
      }).observe(ftr);
    }

    // Pílula que acompanha o link sob o mouse
    var nav = hdr.querySelector(".nav");
    var pill = nav && nav.querySelector(".nav__pill");
    if (nav && pill && fine) {
      $all("a", nav).forEach(function (a) {
        a.addEventListener("mouseenter", function () {
          pill.style.width = a.offsetWidth + "px";
          pill.style.transform = "translateX(" + a.offsetLeft + "px)";
          pill.style.opacity = "1";
        });
      });
      nav.addEventListener("mouseleave", function () { pill.style.opacity = "0"; });
    }

    // Seção atual destacada no menu (só na home)
    var links = nav ? $all('a[href^="#"]', nav) : [];
    if (links.length && "IntersectionObserver" in window) {
      var byId = {};
      links.forEach(function (a) { byId[a.getAttribute("href").slice(1)] = a; });
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (en.isIntersecting && byId[en.target.id]) {
            links.forEach(function (l) { l.removeAttribute("aria-current"); });
            byId[en.target.id].setAttribute("aria-current", "true");
          }
        });
      }, { rootMargin: "-45% 0px -50% 0px" });
      Object.keys(byId).forEach(function (id) { var s = doc.getElementById(id); if (s) io.observe(s); });
    }
  }

  /* ------------------------------------------------------------------
     5. Menu mobile (diálogo modal)
     ------------------------------------------------------------------ */
  var MENU_BP = 1040;
  function initMenu() {
    var btn = doc.querySelector(".hdr__burger");
    var menu = doc.getElementById("menu-mobile");
    if (!btn || !menu) return;
    var outside = $all("main, footer, .fab, .skip");
    function focaveis() {
      return $all('a[href], button:not([disabled])', menu).filter(function (el) { return el.offsetParent !== null || el.getClientRects().length; });
    }
    function setOpen(open, silent) {
      btn.setAttribute("aria-expanded", String(open));
      btn.setAttribute("aria-label", open ? "Fechar menu" : "Abrir menu");
      menu.classList.toggle("is-open", open);
      hdr.classList.toggle("is-open", open);
      root.style.overflow = open ? "hidden" : "";
      outside.forEach(function (el) { if (open) el.setAttribute("inert", ""); else el.removeAttribute("inert"); });
      if (D2.lenis) { if (open) D2.lenis.stop(); else D2.lenis.start(); }
      if (open) {
        hdr.classList.remove("is-hidden");
        menu.scrollTop = 0;
        if (anim && gsap) gsap.fromTo($all(".mnav__list li, .mnav__foot > *", menu), { y: 24, opacity: 0 }, { y: 0, opacity: 1, duration: .6, ease: "expo.out", stagger: .04 });
        var first = menu.querySelector("a"); if (first) setTimeout(function () { first.focus(); }, 60);
      } else if (!silent) btn.focus();
    }
    btn.addEventListener("click", function () { setOpen(btn.getAttribute("aria-expanded") !== "true"); });
    menu.addEventListener("click", function (e) { if (e.target.closest("a")) setOpen(false, true); });
    doc.addEventListener("keydown", function (e) {
      if (!menu.classList.contains("is-open")) return;
      if (e.key === "Escape") { setOpen(false); return; }
      if (e.key !== "Tab") return;
      // Foco preso entre o botão do menu e os itens do menu
      var f = focaveis();
      if (!f.length) return;
      var last = f[f.length - 1];
      if (!e.shiftKey && doc.activeElement === last) { e.preventDefault(); btn.focus(); }
      else if (e.shiftKey && doc.activeElement === btn) { e.preventDefault(); last.focus(); }
      else if (!menu.contains(doc.activeElement) && doc.activeElement !== btn) { e.preventDefault(); (e.shiftKey ? last : f[0]).focus(); }
    });
    window.addEventListener("resize", function () { if (window.innerWidth > MENU_BP && menu.classList.contains("is-open")) setOpen(false, true); });
    D2.closeMenu = function () { if (menu.classList.contains("is-open")) setOpen(false, true); };
  }

  /* ------------------------------------------------------------------
     6. Âncoras (com ou sem Lenis)
     ------------------------------------------------------------------ */
  function hdrOffset() { return -((hdr ? hdr.offsetHeight : 72) + 12); }

  function initAnchors() {
    doc.addEventListener("click", function (e) {
      var a = e.target.closest && e.target.closest('a[href*="#"]');
      if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || a.target === "_blank") return;
      var url;
      try { url = new URL(a.getAttribute("href"), location.href); } catch (err) { return; }
      if (url.pathname !== location.pathname || !url.hash || url.hash.length < 2) return;
      var id = decodeURIComponent(url.hash.slice(1));
      if (id.indexOf("=") !== -1) return; // links tipo #p=ID ficam com a página
      var target = doc.getElementById(id);
      if (!target) return;
      e.preventDefault();
      if (D2.closeMenu) D2.closeMenu();
      // O Lenis já desconta o scroll-padding-top do <html>
      if (D2.lenis) D2.lenis.scrollTo(target, { offset: 0, duration: 1.2 });
      else window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY + hdrOffset(), behavior: reduce ? "auto" : "smooth" });
      if (history.pushState) history.pushState(null, "", "#" + id);
      if (!target.hasAttribute("tabindex")) target.setAttribute("tabindex", "-1");
      target.focus({ preventScroll: true });
    });
  }

  /* ------------------------------------------------------------------
     7. Tipografia dos títulos
     ------------------------------------------------------------------ */
  /* Evita palavra curta sozinha no fim da linha ("o | seu caixa"):
     só troca o espaço por espaço não separável, sem criar elementos. */
  function colaCurtas() {
    $all(".hero__title, [data-split], .ftr__big, .sat__title, .cta-tile__t, .empty__t, .hb__kit-txt strong").forEach(function (el) {
      var w = doc.createTreeWalker(el, NodeFilter.SHOW_TEXT, null), n;
      while ((n = w.nextNode())) {
        for (var i = 0; i < 2; i++) n.nodeValue = n.nodeValue.replace(/(^|\s)(a|o|e|é|à|A|O|E|de|da|do|em|no|na|um)\s+(?=\S)/g, "$1$2 ");
      }
    });
  }

  /* Aproxima a pontuação nos títulos grandes (o Plus Jakarta tem respiro largo).
     Fora dos títulos com SplitText, que poderiam quebrar a linha antes do ponto. */
  function tightenPunct() {
    $all(".hero__title, .ftr__big, .sat__title, .cta-tile__t, .empty__t").forEach(function (el) {
      var walker = doc.createTreeWalker(el, NodeFilter.SHOW_TEXT, null);
      var nodes = [];
      while (walker.nextNode()) nodes.push(walker.currentNode);
      nodes.forEach(function (n) {
        if (!/[.,?!:]/.test(n.nodeValue)) return;
        var frag = doc.createDocumentFragment();
        n.nodeValue.split(/([.,?!:])/).forEach(function (part, i) {
          if (!part) return;
          if (i % 2) {
            var s = doc.createElement("span");
            s.className = "pd";
            s.textContent = part;
            frag.appendChild(s);
          } else frag.appendChild(doc.createTextNode(part));
        });
        n.parentNode.replaceChild(frag, n);
      });
    });
  }

  /* ------------------------------------------------------------------
     8. Movimento: revelações, títulos em linhas, contadores, botões magnéticos
     ------------------------------------------------------------------ */
  // Se o GSAP chegou tarde (rede lenta), o que já está na tela fica como está.
  var tarde = false;
  function naTela(el) { var r = el.getBoundingClientRect(); return r.bottom > 0 && r.top < window.innerHeight; }

  function initReveals() {
    if (!anim) return;
    var els = $all("[data-reveal], .shead .eyebrow");
    els = els.filter(function (el) { return !el.closest("[data-no-reveal]") && !(tarde && naTela(el)); });
    if (!els.length) return;
    // Na chegada (link com #, recarga, Voltar) a entrada é mais curta
    var chegada = true;
    setTimeout(function () { chegada = false; }, 1400);
    function revela(vis) {
      if (!vis.length) return;
      gsap.to(vis, {
        opacity: 1, translate: "0px 0px", duration: chegada ? 0.8 : 1.05, ease: "expo.out",
        stagger: { amount: Math.min(chegada ? 0.3 : 0.45, (vis.length - 1) * (chegada ? 0.05 : 0.075)) }, overwrite: true,
        onComplete: function () { gsap.set(this.targets(), { clearProps: "opacity,translate" }); }
      });
    }
    gsap.set(els, { opacity: 0, translate: "0px 34px" });
    ST.batch(els, {
      start: "top 92%",
      once: true,
      batchMax: 8,
      interval: 0.1,
      onEnter: function (batch) {
        // O que já passou (ex.: chegou por index.html#contato) aparece na hora;
        // só o que está na tela anima, com a fila curta.
        var acima = batch.filter(function (el) { return el.getBoundingClientRect().bottom <= 0; });
        var vis = batch.filter(function (el) { return acima.indexOf(el) < 0 && !gsap.isTweening(el); });
        if (acima.length) gsap.set(acima, { clearProps: "opacity,translate" });
        revela(vis);
      }
    });
    // Ao chegar no meio da página (index.html#contato, recarga, Voltar), o que já
    // aparece na tela, mesmo só a pontinha embaixo, entra junto com o resto
    function revelaNaTela() {
      revela(els.filter(function (el) {
        if (gsap.isTweening(el) || +getComputedStyle(el).opacity >= 1) return false;
        var r = el.getBoundingClientRect();
        return r.top < window.innerHeight && r.bottom > 0;
      }));
    }
    if (window.scrollY > 0) requestAnimationFrame(revelaNaTela);
    if (doc.readyState === "complete") setTimeout(revelaNaTela, 60);
    else window.addEventListener("load", function () { setTimeout(revelaNaTela, 60); });
    // Navegando com Tab: o bloco focado aparece na hora
    doc.addEventListener("focusin", function (e) {
      var r = e.target.closest && e.target.closest("[data-reveal], .shead .eyebrow");
      if (r && +getComputedStyle(r).opacity < 1) {
        gsap.to(r, { opacity: 1, translate: "0px 0px", duration: 0.25, overwrite: true, clearProps: "opacity,translate" });
      }
    });
  }

  D2.prepText = function (t) { return t.replace(/[ \t\n\r\f]+/g, " "); };

  function initSplit() {
    if (!anim || !window.SplitText) return;
    $all("[data-split]").forEach(function (h) {
      if (tarde && naTela(h)) return;
      window.SplitText.create(h, {
        type: "lines", mask: "lines", autoSplit: true, linesClass: "sl",
        // mantém os espaços não separáveis de colaCurtas() (o padrão troca \s por espaço comum)
        reduceWhiteSpace: false, prepareText: D2.prepText,
        onSplit: function (self) {
          (self.masks || []).forEach(function (m) {
            m.style.paddingBottom = ".14em"; m.style.marginBottom = "-.14em";
            m.style.paddingTop = ".04em"; m.style.marginTop = "-.04em";
          });
          return gsap.from(self.lines, {
            yPercent: 118, duration: 1.15, ease: "expo.out", stagger: 0.09,
            scrollTrigger: { trigger: h, start: "top 90%", once: true }
          });
        }
      });
    });
  }

  function fmtNum(v, dec) {
    return new Intl.NumberFormat("pt-BR", { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v);
  }
  D2.fmtNum = fmtNum;

  function initCounters() {
    if (!anim) return;
    $all("[data-count-to]").forEach(function (el) {
      var to = parseFloat(String(el.getAttribute("data-count-to")).replace(",", "."));
      if (!isFinite(to)) return;
      if (tarde && naTela(el)) return;
      var dec = +(el.getAttribute("data-count-dec") || 0);
      var o = { v: 0 };
      ST.create({
        trigger: el, start: "top 92%", once: true,
        onEnter: function () {
          gsap.to(o, {
            v: to, duration: 1.8, ease: "power3.out",
            onStart: function () { el.textContent = fmtNum(0, dec); },
            onUpdate: function () { el.textContent = fmtNum(dec ? o.v : Math.round(o.v), dec); },
            onComplete: function () { el.textContent = fmtNum(to, dec); }
          });
        }
      });
    });
  }

  function initMagnetic() {
    if (!anim || !fine) return;
    $all(".magnetic").forEach(function (el) {
      var o = { x: 0, y: 0 };
      function apply() { el.style.translate = o.x.toFixed(2) + "px " + o.y.toFixed(2) + "px"; }
      var qx = gsap.quickTo(o, "x", { duration: 0.6, ease: "power3", onUpdate: apply });
      var qy = gsap.quickTo(o, "y", { duration: 0.6, ease: "power3", onUpdate: apply });
      el.addEventListener("mousemove", function (e) {
        var r = el.getBoundingClientRect();
        qx((e.clientX - r.left - r.width / 2) * 0.28);
        qy((e.clientY - r.top - r.height / 2) * 0.38);
      });
      el.addEventListener("mouseleave", function () { qx(0); qy(0); });
    });
  }

  function initLogo() {
    if (!anim || tarde) return;
    var icon = doc.querySelector(".hdr .logo__icon");
    if (icon) gsap.from(icon, { rotate: -180, scale: 0.6, duration: 1.4, ease: "expo.out", delay: 0.1, clearProps: "transform" });
  }

  function initLenis() {
    if (!anim || !window.Lenis) return;
    try {
      var lenis = new window.Lenis({ lerp: 0.11, wheelMultiplier: 1, smoothWheel: true, syncTouch: false });
      D2.lenis = lenis;
      lenis.on("scroll", ST.update);
      gsap.ticker.add(function (t) { lenis.raf(t * 1000); });
      gsap.ticker.lagSmoothing(0);
      // Menu aberto antes do Lenis existir: mantém a página parada
      if (hdr && hdr.classList.contains("is-open")) lenis.stop();
    } catch (e) { D2.lenis = null; }
  }

  /* Chamado por js/movimento.js, depois que as bibliotecas do CDN carregaram */
  D2.iniciarMovimento = function () {
    gsap = window.gsap || null;
    ST = window.ScrollTrigger || null;
    anim = D2.anim = !!(gsap && ST && !reduce);
    tarde = (window.performance && performance.now ? performance.now() : 0) > 1500;
    D2.tarde = tarde;
    if (gsap) {
      var plugins = [];
      if (ST) plugins.push(ST);
      if (window.Flip) plugins.push(window.Flip);
      if (window.SplitText) plugins.push(window.SplitText);
      if (plugins.length) gsap.registerPlugin.apply(gsap, plugins);
    }
    initLenis();
    initLogo();
    initReveals();
    initSplit();
    initCounters();
    initMagnetic();
    if (anim) {
      if (doc.readyState === "complete") ST.refresh();
      else window.addEventListener("load", function () { ST.refresh(); });
      if (doc.fonts && doc.fonts.ready) doc.fonts.ready.then(function () { ST.refresh(); });
    }
  };

  /* ------------------------------------------------------------------
     Início (conteúdo e interação, sem depender do CDN)
     ------------------------------------------------------------------ */
  fillEmpresa();
  colaCurtas();
  tightenPunct();
  openStatus();
  setInterval(openStatus, 60000);
  initHeader();
  initMenu();
  initAnchors();
})();
