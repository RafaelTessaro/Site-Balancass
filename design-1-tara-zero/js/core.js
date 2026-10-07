/* =====================================================================
   Tara Zero — núcleo compartilhado (todas as páginas)
   Dados da empresa, cabeçalho, menu, preloader, cursor, Lenis + GSAP,
   revelações ao rolar e o card de produto.

   Carregamento em duas fases:
   1) CONTEÚDO — este arquivo e o script da página são <script> clássicos
      no fim do <body>, ANTES das bibliotecas do CDN. Renderizam tudo e
      ligam menu, FAQ, filtros e formulário na hora, mesmo sem CDN.
   2) MOVIMENTO — TZ.startMotion roda no DOMContentLoaded, depois que os
      scripts "defer" do CDN (GSAP, ScrollTrigger, Lenis…) chegaram.
      Sem CDN, o site continua funcionando, só que sem animação.
   ===================================================================== */
(function () {
  "use strict";

  var d = document;
  var html = d.documentElement;
  var BC = window.BC || null;
  var E = (BC && BC.empresa) || {};
  var mqReduce = window.matchMedia("(prefers-reduced-motion: reduce)");
  var mqFine = window.matchMedia("(hover: hover) and (pointer: fine)");

  var TZ = window.TZ = {
    reduce: mqReduce.matches,
    fine: mqFine.matches,
    hasG: false,      // GSAP disponível (definido em startMotion)
    motion: false,    // GSAP disponível e sem "reduzir movimento"
    late: false,      // a intro do <head> já tinha expirado quando o GSAP chegou
    painted: false,   // o conteúdo já foi pintado antes do movimento começar
    lenis: null,
    mm: null,
    _ready: [],
    _isReady: false,
    _pre: [],
    _post: []
  };
  // Se um quadro for pintado antes do movimento começar, o conteúdo já foi visto:
  // não escondemos de novo o que está na tela só para animar a entrada.
  if (window.requestAnimationFrame) requestAnimationFrame(function () { TZ.painted = true; });

  function $(s, r) { return (r || d).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || d).querySelectorAll(s)); }
  TZ.$ = $; TZ.$$ = $$;

  function headerH() { var h = $(".hdr"); return h ? h.offsetHeight : 72; }
  TZ.headerH = headerH;

  // Ganchos das páginas: _pre roda antes das revelações (classes de layout),
  // _post depois (timelines, pins…). Só rodam se o GSAP carregar.
  TZ.onPre = function (fn) { TZ._pre.push(fn); };
  TZ.onMotion = function (fn) { TZ._post.push(fn); };
  function runAll(list) { list.forEach(function (fn) { try { fn(); } catch (e) { if (window.console) console.warn(e); } }); }

  /* ---------------- Dados da empresa nos elementos ---------------- */
  TZ.fill = function (root) {
    root = root || d;
    if (!BC) return;
    var g = E.google || {};
    var txt = {
      telefone: E.telefone, email: E.email, endereco: BC.enderecoTexto(), referencia: BC.referenciaEndereco(),
      cnpj: E.cnpj, razao: E.razaoSocial, nota: g.nota, avaliacoes: g.avaliacoes, desde: E.desde,
      frase: E.frase, ipem: E.ipem, anos: E.anosExperiencia
    };
    $$("[data-bc]", root).forEach(function (el) {
      var k = el.getAttribute("data-bc"), v = txt[k];
      if (v !== undefined && v !== null && v !== "") {
        // "Rio Claro/SP" e "· CEP 13500-120" nunca quebram no meio
        if (k === "endereco") {
          var a = E.endereco || {}, cid = a.cidade && a.uf ? BC.escape(a.cidade + "/" + a.uf) : "";
          var h = BC.escape(v).replace(/ · CEP (\S+)/, ' <span class="nw">· CEP&nbsp;$1</span>');
          el.innerHTML = cid ? h.replace(cid, '<span class="nw">' + cid + "</span>") : h;
        }
        else el.textContent = v;
      } else if (k === "referencia") el.hidden = true;
    });
    var hrefs = { tel: BC.telLink(), email: BC.emailLink(), mapa: E.mapa, google: g.link, facebook: E.redes && E.redes.facebook };
    $$("[data-bc-href]", root).forEach(function (el) {
      var v = hrefs[el.getAttribute("data-bc-href")];
      if (v) el.setAttribute("href", v);
    });
    $$("[data-wa]", root).forEach(function (el) {
      el.setAttribute("href", BC.whatsLink(el.getAttribute("data-wa") || ""));
    });
    $$("[data-year]", root).forEach(function (el) { el.textContent = new Date().getFullYear(); });

    // Horários
    if (E.horarios && E.horarios.length) {
      $$("[data-hours]", root).forEach(function (dl) {
        dl.innerHTML = E.horarios.map(function (h) {
          return "<dt>" + BC.escape(h.dias) + "</dt><dd>" + BC.escape(h.horas) + "</dd>";
        }).join("");
      });
      $$("[data-hours-list]", root).forEach(function (ul) {
        ul.innerHTML = E.horarios.map(function (h) {
          return "<li><span>" + BC.escape(h.dias) + " · " + BC.escape(h.horas) + "</span></li>";
        }).join("");
      });
    }
  };

  /* ---------------- Aberto agora? (horário de Brasília) ---------------- */
  // Grade de horários usada para o selo "Aberto agora". Mantenha igual a EMPRESA.horarios.
  var GRADE = { 0: [], 1: [[8, 11], [13, 18]], 2: [[8, 11], [13, 18]], 3: [[8, 11], [13, 18]], 4: [[8, 11], [13, 18]], 5: [[8, 11], [13, 18]], 6: [[8, 12]] };
  var DIAS = ["domingo", "segunda", "terça", "quarta", "quinta", "sexta", "sábado"];
  // FERIADOS — nesses dias o selo mostra "Feriado · abre …" em vez de "Aberto agora".
  //
  // 1) Nacionais fixos (formato "MM-DD"): Confraternização (01-01), Tiradentes (04-21),
  //    Dia do Trabalho (05-01), Independência (09-07), N. Sra. Aparecida (10-12),
  //    Finados (11-02), Proclamação da República (11-15), Consciência Negra (11-20) e Natal (12-25).
  var FERIADOS = ["01-01", "04-21", "05-01", "09-07", "10-12", "11-02", "11-15", "11-20", "12-25"];
  //
  // 2) COMO ACRESCENTAR OS FERIADOS MUNICIPAIS DE RIO CLARO (ou qualquer data em que a loja feche):
  //    - Confirme com a loja quais datas ela realmente fecha (o calendário oficial sai por
  //      decreto da Prefeitura de Rio Claro todo ano).
  //    - Data que se repete todo ano no mesmo dia → coloque "MM-DD" na lista abaixo.
  //      Exemplo (só depois de confirmar): "06-24" para o aniversário da cidade.
  //    - Data que só vale num ano (ponte, ponto facultativo, recesso) → use "AAAA-MM-DD",
  //      ex.: "2026-12-24". Datas de anos passados podem ser apagadas sem problema.
  //    - Mantenha as aspas e as vírgulas; a lista pode ficar vazia ([]).
  var FERIADOS_LOCAIS = [];
  //
  // 3) Nacionais móveis, calculados pela Páscoa (não precisa mexer): Carnaval (seg e ter),
  //    Sexta-Feira Santa e Corpus Christi.
  function pascoa(y) {
    var a = y % 19, b = Math.floor(y / 100), c = y % 100, dd = Math.floor(b / 4), e = b % 4,
      f = Math.floor((b + 8) / 25), g = Math.floor((b - f + 1) / 3), h = (19 * a + b - dd - g + 15) % 30,
      i = Math.floor(c / 4), k = c % 4, l = (32 + 2 * e + 2 * i - h - k) % 7, m = Math.floor((a + 11 * h + 22 * l) / 451),
      mes = Math.floor((h + l - 7 * m + 114) / 31), dia = ((h + l - 7 * m + 114) % 31) + 1;
    return Date.UTC(y, mes - 1, dia, 12);
  }
  var DAY = 864e5;
  function mmdd(t) { var x = new Date(t); return ("0" + (x.getUTCMonth() + 1)).slice(-2) + "-" + ("0" + x.getUTCDate()).slice(-2); }
  function feriado(t) {
    var x = new Date(t), md = mmdd(t);
    if (FERIADOS.indexOf(md) !== -1) return true;
    if (FERIADOS_LOCAIS.indexOf(md) !== -1 || FERIADOS_LOCAIS.indexOf(x.getUTCFullYear() + "-" + md) !== -1) return true;
    var p = pascoa(x.getUTCFullYear());
    return [p - 48 * DAY, p - 47 * DAY, p - 2 * DAY, p + 60 * DAY].some(function (q) { return mmdd(q) === md; });
  }
  function agoraSP() {
    try {
      var parts = new Intl.DateTimeFormat("en-US", { timeZone: "America/Sao_Paulo", year: "numeric", month: "numeric", day: "numeric", hour: "numeric", minute: "numeric", hourCycle: "h23" }).formatToParts(new Date());
      var o = {};
      parts.forEach(function (p) { o[p.type] = p.value; });
      var h = parseInt(o.hour, 10) % 24, m = parseInt(o.minute, 10);
      var t = Date.UTC(+o.year, +o.month - 1, +o.day, 12); // meio-dia UTC da data de São Paulo
      return { t: t, dia: new Date(t).getUTCDay(), h: h + m / 60 };
    } catch (e) {
      var n = new Date();
      return { t: Date.UTC(n.getFullYear(), n.getMonth(), n.getDate(), 12), dia: n.getDay(), h: n.getHours() + n.getMinutes() / 60 };
    }
  }
  function slotsDe(t) { return feriado(t) ? [] : (GRADE[new Date(t).getUTCDay()] || []); }
  TZ.status = function () {
    var a = agoraSP(), hoje = slotsDe(a.t), i;
    for (i = 0; i < hoje.length; i++) {
      if (a.h >= hoje[i][0] && a.h < hoje[i][1]) return { aberto: true, txt: "Aberto agora · até " + hoje[i][1] + "h" };
    }
    for (i = 0; i < hoje.length; i++) {
      if (a.h < hoje[i][0]) return { aberto: false, txt: (i > 0 ? "Pausa para almoço · volta às " : "Fechado · abre hoje às ") + hoje[i][0] + "h" };
    }
    var pre = feriado(a.t) && GRADE[a.dia] && GRADE[a.dia].length ? "Feriado" : "Fechado";
    for (var k = 1; k <= 14; k++) {
      var t = a.t + k * DAY, s = slotsDe(t);
      if (s.length) return { aberto: false, txt: pre + " · abre " + (k === 1 ? "amanhã" : DIAS[new Date(t).getUTCDay()]) + " às " + s[0][0] + "h" };
    }
    return { aberto: false, txt: "Fechado" };
  };
  function paintStatus() {
    var s = TZ.status();
    $$("[data-status]").forEach(function (el) {
      el.classList.toggle("is-open", s.aberto);
      el.classList.toggle("is-closed", !s.aberto);
      var t = $("[data-status-text]", el); if (t) t.textContent = s.txt;
    });
  }

  /* ---------------- Especificações curtas para chips ---------------- */
  // "Capacidade" vira a maior capacidade do texto ("até 32 kg"); o resto perde os parênteses.
  TZ.specKey = function (k) { return String(k).split(" / ")[0].split(" (")[0].trim(); };
  function specClean(v, k) {
    v = String(v);
    if (/^capacidade/i.test(k || "")) {
      var m = v.match(/\d{1,3}(?:\.\d{3})*(?:,\d+)?(?=\s*kg)/g);
      if (m) {
        var mx = Math.max.apply(null, m.map(function (x) { return parseFloat(x.replace(/\./g, "").replace(",", ".")); }));
        return "até " + String(mx).replace(".", ",") + " kg";
      }
    }
    v = v.replace(/\s*varreduras \(scans\) por segundo/i, " scans/s");
    return v.split(" (")[0].split(";")[0].trim();
  }
  TZ.specVal = function (v, k, max) {
    max = max || 30;
    v = specClean(v, k);
    if (v.length > max) v = v.slice(0, max - 2).replace(/[\s,.:\-–]+\S*$/, "") + "…";
    return v;
  };
  // Um chip não pode esconder alternativas: "Coluna articulada (…) ou indicador remoto" fica de fora.
  function chipOk(s) {
    if (/^capacidade/i.test(s[0])) return true;
    return !(/ ou /.test(s[1]) && !/ ou /.test(specClean(s[1], s[0])));
  }
  // até 2 especificações curtas, na ordem do cadastro
  TZ.specChips = function (p) {
    var all = (p.especificacoes || []).filter(function (s) { return s && s[0] && s[1]; });
    var good = all.filter(chipOk);
    var short = good.filter(function (s) { return (TZ.specKey(s[0]) + " " + specClean(s[1], s[0])).length <= 30; });
    return (short.length ? short : good).slice(0, 2);
  };

  /* ---------------- Card de produto (vitrine e catálogo) ---------------- */
  TZ.card = function (p, o) {
    o = o || {};
    var e = BC.escape;
    var est = BC.estoque(p);
    var href = (o.base || "") + "#p=" + encodeURIComponent(p.id);
    var cond = p.condicao === "seminovo" ? '<span class="badge badge--cond">Seminovo</span>' : "";
    var chips = TZ.specChips(p).map(function (s) { return '<li title="' + e(s[0] + ": " + s[1]) + '"><span>' + e(TZ.specKey(s[0])) + "</span> " + e(TZ.specVal(s[1], s[0])) + "</li>"; }).join("");
    var nomeCompleto = p.marca + " " + p.nome;
    return '<article class="pcard" data-id="' + e(p.id) + '" data-cursor="view">' +
      '<div class="pcard__in" data-tilt>' +
        '<div class="stage pcard__stage"><img src="' + e(BC.imgProduto(p, false)) + '" alt="' + e(nomeCompleto) + '" width="800" height="600" loading="lazy" decoding="async">' +
        (o.fig ? '<span class="stage__fig" aria-hidden="true">' + e(o.fig) + "</span>" : "") + "</div>" +
        '<div class="pcard__badges"><span class="badge badge--' + e(est.classe) + '">' + e(est.rotulo) + "</span>" + cond + "</div>" +
        '<div class="pcard__body">' +
          '<p class="pcard__meta"><b>' + e(p.marca) + "</b> · " + e(p.subcategoria) + "</p>" +
          // o nome cobre o card inteiro para o clique; no teclado, a parada é o botão "Detalhes"
          '<h3 class="pcard__name"><a href="' + href + '" data-detail="' + e(p.id) + '" tabindex="-1"><span class="sr-only">' + e(p.marca) + " </span>" + e(p.nome) + "</a></h3>" +
          '<p class="pcard__sum">' + e(p.resumo) + "</p>" +
          (chips ? '<ul class="chips">' + chips + "</ul>" : "") +
          '<p class="pcard__price"><span>Preço</span><b>' + (p.preco ? e(p.preco) : "Consulte") + "</b></p>" +
          '<div class="pcard__actions">' +
            '<a class="btn btn--ghost btn--sm" href="' + href + '" data-detail="' + e(p.id) + '" aria-label="Detalhes de ' + e(nomeCompleto) + '">Detalhes</a>' +
            '<a class="btn btn--wa btn--sm" href="' + e(BC.whatsProduto(p)) + '" target="_blank" rel="noopener" aria-label="Pedir orçamento de ' + e(nomeCompleto) + ' no WhatsApp"><svg aria-hidden="true"><use href="#i-wa"/></svg><span class="btn__txt">Orçamento</span></a>' +
          "</div>" +
        "</div>" +
      "</div>" +
    "</article>";
  };

  // Fotos em pé (ou composições) ganham menos respiro no palco
  TZ.tallImgs = function (root) {
    $$(".pcard__stage img", root).forEach(function (img) {
      if (img._tall) return; img._tall = true;
      var f = function () { if (img.naturalHeight > img.naturalWidth * 1.15) img.parentNode.classList.add("is-tall"); };
      if (img.complete && img.naturalWidth) f(); else img.addEventListener("load", f, { once: true });
    });
  };

  /* ---------------- Cabeçalho, progresso e botão flutuante ---------------- */
  var fab = null, fabState = {}, fabAll = {};
  function fabSync() {
    if (!fab) return;
    var small = window.innerWidth < 768;
    var off = Object.keys(fabState).some(function (k) { return fabState[k] && (small || fabAll[k]); });
    fab.classList.toggle("is-off", off);
  }
  // Esconde a pilha flutuante enquanto um alvo com CTA próprio estiver na tela
  // (só no celular, ou em qualquer largura com allSizes)
  TZ.fabAvoid = function (el, key, allSizes) {
    if (!fab || !el || !("IntersectionObserver" in window)) return;
    fabAll[key] = !!allSizes;
    new IntersectionObserver(function (es) {
      es.forEach(function (en) { fabState[key] = en.isIntersecting; });
      fabSync();
    }).observe(el);
  };
  function initHeader() {
    var hdr = $(".hdr"), bar = $(".hdr__progress");
    var top = $(".hero") || $(".ahero") || $(".cat-hero");
    fab = $("[data-fab]");
    var ticking = false;
    function update() {
      ticking = false;
      var y = window.scrollY || window.pageYOffset;
      var max = Math.max(1, d.documentElement.scrollHeight - window.innerHeight);
      if (hdr) hdr.classList.toggle("is-scrolled", y > 12);
      if (bar) bar.style.transform = "scaleX(" + Math.min(1, y / max).toFixed(4) + ")";
      if (fab && top) fab.classList.toggle("is-hidden", y < window.innerHeight * 0.55);
    }
    window.addEventListener("scroll", function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
    window.addEventListener("resize", function () { update(); fabSync(); });
    update();
    // no celular, a pilha flutuante sai da frente de blocos que já têm CTA próprio alinhado à direita
    [".router", "#duvidas", "#contato", ".cta-band", ".site-footer"].forEach(function (s) { TZ.fabAvoid($(s), s); });
  }

  /* ---------------- Menu móvel ---------------- */
  function initMenu() {
    var btn = $(".burger"), menu = $("#mmenu");
    if (!btn || !menu) return;
    var lastFocus = null;
    // o botão vem antes do menu no DOM: ele é a 1ª parada do ciclo
    function focusables() { return [btn].concat($$("a[href], button:not([disabled])", menu)); }
    function inert(on) {
      $$("main, .site-footer, .fab, .skip-link").forEach(function (el) {
        if (on) el.setAttribute("inert", ""); else el.removeAttribute("inert");
      });
    }
    function onKey(ev) {
      if (ev.key === "Escape") { ev.preventDefault(); close(); return; }
      if (ev.key !== "Tab") return;
      var f = focusables(), first = f[0], last = f[f.length - 1], a = d.activeElement;
      if (f.indexOf(a) === -1) { ev.preventDefault(); (ev.shiftKey ? last : first).focus(); return; }
      if (ev.shiftKey && a === first) { ev.preventDefault(); last.focus(); }
      else if (!ev.shiftKey && a === last) { ev.preventDefault(); first.focus(); }
    }
    function open() {
      lastFocus = d.activeElement;
      menu.hidden = false;
      menu.scrollTop = 0;
      btn.setAttribute("aria-expanded", "true");
      btn.setAttribute("aria-label", "Fechar menu");
      $(".hdr").classList.add("is-solid");
      if (TZ.lenis) TZ.lenis.stop();
      d.body.style.overflow = "hidden";
      inert(true);
      d.addEventListener("keydown", onKey);
      if (TZ.motion) {
        gsap.fromTo($$(".mmenu__list li, .mmenu__foot > *", menu), { y: 28, opacity: 0 }, { y: 0, opacity: 1, duration: .6, ease: "expo.out", stagger: .045 });
      }
      var f = $("a", menu); if (f) f.focus({ preventScroll: true });
    }
    function close(noFocus) {
      if (menu.hidden) return;
      menu.hidden = true;
      btn.setAttribute("aria-expanded", "false");
      btn.setAttribute("aria-label", "Abrir menu");
      $(".hdr").classList.remove("is-solid");
      d.body.style.overflow = "";
      inert(false);
      d.removeEventListener("keydown", onKey);
      if (TZ.lenis) TZ.lenis.start();
      if (!noFocus) (lastFocus && lastFocus.focus ? lastFocus : btn).focus({ preventScroll: true });
    }
    TZ.menuOpen = function () { return !menu.hidden; };
    btn.addEventListener("click", function () { menu.hidden ? open() : close(); });
    $$("a", menu).forEach(function (a) {
      a.addEventListener("click", function (ev) {
        var h = a.getAttribute("href") || "";
        close(true);
        if (h.charAt(0) === "#") {
          ev.preventDefault();
          TZ.scrollTo(h);
          if (history.replaceState && h !== "#topo") history.replaceState(null, "", h);
        }
      });
    });
    window.addEventListener("resize", function () { if (window.innerWidth >= 1024 && !menu.hidden) close(true); });
  }

  /* ---------------- Rolagem até âncoras ---------------- */
  // O scroll-padding-top do <html> é a fonte única do desconto do cabeçalho:
  // o Lenis já o considera; no ramo nativo descontamos à mão.
  TZ.scrollTo = function (hash, imm) {
    var target = null;
    if (hash === "#topo" || hash === "#") target = 0;
    else { try { target = d.querySelector(hash); } catch (e) { target = null; } }
    if (target === null) return false;
    if (TZ.lenis) {
      TZ.lenis.resize();
      TZ.lenis.scrollTo(target, { offset: 0, duration: 1.4, immediate: !!imm, force: true });
    } else {
      var pad = parseFloat(getComputedStyle(html).scrollPaddingTop) || (headerH() + 20);
      var y = target === 0 ? 0 : target.getBoundingClientRect().top + window.scrollY - pad;
      window.scrollTo({ top: Math.max(0, y), behavior: (TZ.reduce || imm) ? "auto" : "smooth" });
    }
    if (target !== 0 && target.focus) {
      if (!target.hasAttribute("tabindex") && !/^(A|BUTTON|INPUT|SELECT|TEXTAREA)$/.test(target.tagName)) target.setAttribute("tabindex", "-1");
      target.focus({ preventScroll: true });
    }
    return true;
  };
  function initAnchors() {
    d.addEventListener("click", function (ev) {
      var a = ev.target.closest && ev.target.closest('a[href^="#"]');
      if (!a || a.closest("#mmenu")) return;
      var h = a.getAttribute("href");
      if (!h || h.length < 2 && h !== "#") return;
      if (/^#p=/.test(h)) return; // catálogo trata
      if (h === "#topo" || /^#[A-Za-z][\w-]*$/.test(h)) {
        ev.preventDefault();
        TZ.scrollTo(h);
        if (history.replaceState && h !== "#topo") history.replaceState(null, "", h);
      }
    });
  }

  // Âncora vinda de outra página (ex.: index.html#contato): espera o layout final
  // (imagens, fontes, pin-spacers do ScrollTrigger) antes de rolar.
  function deepLink() {
    var h = location.hash;
    if (!h || !/^#[A-Za-z][\w-]*$/.test(h) || h === "#topo") return;
    var moved = false;
    var mark = function () { moved = true; };
    ["wheel", "touchstart", "keydown"].forEach(function (t) { window.addEventListener(t, mark, { once: true, passive: true }); });
    var go = function () {
      if (moved) return;
      if (window.ScrollTrigger) ScrollTrigger.refresh();
      if (TZ.lenis) TZ.lenis.resize();
      TZ.scrollTo(h, true);
    };
    var later = function () {
      var f = d.fonts && d.fonts.ready;
      (f ? f : Promise.resolve()).then(function () { requestAnimationFrame(go); });
    };
    if (d.readyState === "complete") setTimeout(later, 0);
    else window.addEventListener("load", later, { once: true });
  }

  /* ---------------- Logo animado ---------------- */
  TZ.logoIn = function (svg, opts) {
    if (!window.gsap || !svg) return null;
    opts = opts || {};
    var ring = $(".logo-ring", svg), b = $(".logo-b", svg), q = $(".logo-q", svg);
    var tl = gsap.timeline({ delay: opts.delay || 0 });
    if (ring) {
      ring.style.strokeDasharray = "1 1";
      tl.fromTo(ring, { strokeDashoffset: 1 }, { strokeDashoffset: 0, duration: opts.ring || .7, ease: "power3.inOut", onComplete: function () { ring.style.strokeDasharray = ""; ring.style.strokeDashoffset = ""; } }, 0);
    }
    if (b) tl.from(b, { y: -70, autoAlpha: 0, duration: opts.bq || .6, ease: "expo.out" }, (opts.ring || .7) * .45);
    if (q) tl.from(q, { y: 70, autoAlpha: 0, duration: opts.bq || .6, ease: "expo.out" }, (opts.ring || .7) * .55);
    return tl;
  };

  /* ---------------- Preloader ---------------- */
  function finishReady() {
    if (TZ._isReady) return;
    TZ._isReady = true;
    html.classList.remove("is-preloading");
    TZ._ready.forEach(function (fn) { try { fn(); } catch (e) { if (window.console) console.warn(e); } });
    TZ._ready = [];
  }
  TZ.whenReady = function (fn) { if (TZ._isReady) fn(); else TZ._ready.push(fn); };

  // Curto (≈0,5 s): o logo monta e a cortina sobe. Até o GSAP chegar, a caixa fica invisível.
  function initPreloader() {
    var pre = $(".preloader");
    if (!html.classList.contains("is-preloading") || !pre || !TZ.motion) {
      html.classList.remove("is-preloading");
      finishReady();
      return;
    }
    if (window.__tzPre) clearTimeout(window.__tzPre);
    try { sessionStorage.setItem("tz-pre", "1"); } catch (e) {}
    var read = $(".preloader__read", pre);
    var o = { v: 0 };
    var tl = gsap.timeline({ onComplete: function () { pre.style.display = "none"; html.classList.remove("is-preloading"); finishReady(); } });
    tl.set(".preloader__box", { opacity: 1 }, 0)
      .add(TZ.logoIn($(".preloader__mark", pre), { ring: .34, bq: .28 }), 0)
      .to(o, { v: 1, duration: .26, ease: "power2.out", onUpdate: function () { if (read) read.textContent = ((1 - o.v) * (1 - o.v) * Math.random() * 9.999).toFixed(3); }, onComplete: function () { if (read) read.textContent = "0.000"; } }, 0)
      .from($$(".preloader__read, .preloader__lbl", pre), { opacity: 0, y: 6, duration: .22, stagger: .04, ease: "power2.out" }, .02)
      .to(pre, { yPercent: -100, duration: .28, ease: "power3.inOut" }, .22)
      .add(function () { finishReady(); }, .24);
    function skip() { tl.progress(1); }
    pre.addEventListener("click", skip);
    d.addEventListener("keydown", function k() { skip(); d.removeEventListener("keydown", k); });
  }

  /* ---------------- Cursor, ímã, spotlight e tilt ---------------- */
  function initSpot(root) {
    $$("[data-spot]", root).forEach(function (el) {
      if (el._spot) return; el._spot = true;
      el.addEventListener("pointermove", function (ev) {
        var r = el.getBoundingClientRect();
        el.style.setProperty("--mx", (ev.clientX - r.left) + "px");
        el.style.setProperty("--my", (ev.clientY - r.top) + "px");
      });
    });
  }
  TZ.initSpot = initSpot;

  TZ.bindTilt = function (root) {
    if (!TZ.motion || !mqFine.matches) return;
    $$("[data-tilt]", root).forEach(function (el) {
      if (el._tilt) return; el._tilt = true;
      gsap.set(el, { transformPerspective: 900 });
      var rx = gsap.quickTo(el, "rotationX", { duration: .5, ease: "power3.out" });
      var ry = gsap.quickTo(el, "rotationY", { duration: .5, ease: "power3.out" });
      el.addEventListener("pointermove", function (ev) {
        var r = el.getBoundingClientRect();
        var px = (ev.clientX - r.left) / r.width - .5, py = (ev.clientY - r.top) / r.height - .5;
        el.style.setProperty("--mx", (ev.clientX - r.left) + "px");
        el.style.setProperty("--my", (ev.clientY - r.top) + "px");
        ry(px * 12); rx(-py * 12);
      });
      el.addEventListener("pointerleave", function () { rx(0); ry(0); });
    });
  };

  function initCursor() {
    var c = $(".cursor");
    if (!c || !TZ.motion || !mqFine.matches) return;
    html.classList.add("has-cursor");
    var ring = $(".cursor__ring", c);
    var xTo = gsap.quickTo(c, "x", { duration: .45, ease: "power3.out" });
    var yTo = gsap.quickTo(c, "y", { duration: .45, ease: "power3.out" });
    var shown = false;
    window.addEventListener("pointermove", function (ev) {
      if (ev.pointerType && ev.pointerType !== "mouse") return;
      if (!shown) { gsap.set(c, { x: ev.clientX, y: ev.clientY }); shown = true; gsap.to(c, { autoAlpha: 1, duration: .3 }); }
      xTo(ev.clientX); yTo(ev.clientY);
    }, { passive: true });
    d.addEventListener("pointerleave", function () { gsap.to(c, { autoAlpha: 0, duration: .3 }); shown = false; });
    var state = "";
    d.addEventListener("pointerover", function (ev) {
      var t = ev.target;
      var view = t.closest && t.closest('[data-cursor="view"]');
      var link = t.closest && t.closest("a, button, [role=tab], input, select, textarea, label");
      var next = view && !(link && link.closest(".pcard__actions")) ? "view" : (link ? "link" : "");
      if (next === state) return;
      state = next;
      c.classList.toggle("is-view", next === "view");
      gsap.to(ring, { scale: next === "view" ? 2.1 : next === "link" ? 1.45 : 1, duration: .45, ease: "power3.out" });
    });
  }

  function initMagnetic() {
    if (!TZ.motion || !mqFine.matches) return;
    $$("[data-magnetic]").forEach(function (el) {
      var xTo = gsap.quickTo(el, "x", { duration: .6, ease: "elastic.out(1, .5)" });
      var yTo = gsap.quickTo(el, "y", { duration: .6, ease: "elastic.out(1, .5)" });
      el.addEventListener("pointermove", function (ev) {
        var r = el.getBoundingClientRect();
        xTo((ev.clientX - (r.left + r.width / 2)) * .28);
        yTo((ev.clientY - (r.top + r.height / 2)) * .35);
      });
      el.addEventListener("pointerleave", function () { xTo(0); yTo(0); });
    });
  }

  /* ---------------- Movimento: Lenis + ScrollTrigger + revelações ---------------- */
  function scrambleLabel(el) {
    if (el._sc) return; el._sc = true;
    var nodes = Array.prototype.slice.call(el.childNodes);
    var last = nodes[nodes.length - 1];
    if (!last || last.nodeType !== 3 || !last.textContent.trim()) return;
    var span = d.createElement("span");
    span.textContent = last.textContent;
    el.replaceChild(span, last);
    var final = span.textContent;
    if (!window.ScrambleTextPlugin) return;
    gsap.to(span, {
      duration: 1.1, ease: "none",
      scrambleText: { text: final, chars: "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789", revealDelay: .25, speed: .6 },
      scrollTrigger: { trigger: el, start: "top 90%", once: true }
    });
  }

  // Já está na tela e já foi visto? Então não escondemos para animar a entrada.
  function seen(el) {
    if (!TZ.painted && !TZ.late) return false;
    if (html.classList.contains("is-intro") && el.closest(".hero, .cat-hero, .ahero, .toolbar, .catalog")) return false;
    var r = el.getBoundingClientRect();
    return r.top < window.innerHeight && r.bottom > 0;
  }
  TZ.seen = seen;

  // Revelações animam só opacidade/posição: o conteúdo continua na ordem do Tab e na árvore de acessibilidade.
  TZ.reveal = function (root) {
    if (!TZ.motion || !window.ScrollTrigger) return;
    root = root || d;
    $$("[data-split]", root).forEach(function (el) {
      if (el._split || !window.SplitText) return; el._split = true;
      if (seen(el)) return;
      SplitText.create(el, {
        type: "lines", mask: "lines", linesClass: "ln", autoSplit: true,
        onSplit: function (self) {
          return gsap.from(self.lines, {
            yPercent: 112, duration: 1.15, ease: "expo.out", stagger: .09,
            scrollTrigger: { trigger: el, start: "top 88%", once: true }
          });
        }
      });
    });
    $$("[data-fade]", root).forEach(function (el) {
      if (el._fade) return; el._fade = true;
      if (seen(el)) return;
      gsap.from(el, { y: 32, opacity: 0, duration: 1.1, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 90%", once: true } });
    });
    $$("[data-stagger]", root).forEach(function (el) {
      if (el._stag) return;
      var kids = Array.prototype.slice.call(el.children).filter(function (k) { return k.tagName !== "NOSCRIPT"; });
      if (!kids.length) return;
      el._stag = true;
      if (seen(el)) return;
      gsap.from(kids, { y: 44, opacity: 0, duration: 1, ease: "power3.out", stagger: .08, scrollTrigger: { trigger: el, start: "top 88%", once: true } });
    });
    $$("[data-scramble]", root).forEach(scrambleLabel);
    $$("[data-parallax]", root).forEach(function (el) {
      if (el._px) return; el._px = true;
      gsap.fromTo(el, { yPercent: 6 }, { yPercent: -6, ease: "none", scrollTrigger: { trigger: el, start: "top bottom", end: "bottom top", scrub: true } });
    });
  };

  TZ.count = function (el, opts) {
    opts = opts || {};
    var to = parseFloat(el.getAttribute("data-count"));
    var from = parseFloat(el.getAttribute("data-count-from") || "0");
    var dec = parseInt(el.getAttribute("data-decimals") || "0", 10);
    var fmt = opts.format || function (v) { return v.toFixed(dec); };
    if (!TZ.motion || !window.ScrollTrigger || isNaN(to)) { if (!isNaN(to)) el.textContent = fmt(to); return; }
    var o = { v: from };
    gsap.to(o, {
      v: to, duration: opts.duration || 1.6, ease: "power2.out",
      onStart: function () { el.textContent = fmt(from); },
      onUpdate: function () { el.textContent = fmt(dec ? o.v : Math.round(o.v)); },
      onComplete: function () { el.textContent = fmt(to); },
      scrollTrigger: { trigger: el, start: "top 92%", once: true }
    });
  };

  function initMotion() {
    if (window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
    if (window.SplitText) gsap.registerPlugin(SplitText);
    if (window.ScrambleTextPlugin) gsap.registerPlugin(ScrambleTextPlugin);
    TZ.mm = gsap.matchMedia();
    TZ.mm.add("(prefers-reduced-motion: no-preference)", function () {
      if (window.Lenis) {
        var lenis = new Lenis({ lerp: .11, wheelMultiplier: 1, touchMultiplier: 1.4 });
        TZ.lenis = lenis;
        if (window.ScrollTrigger) lenis.on("scroll", ScrollTrigger.update);
        var raf = function (t) { lenis.raf(t * 1000); };
        gsap.ticker.add(raf);
        // menu ou painel já abertos antes do movimento começar: a página atrás não rola
        if ((TZ.menuOpen && TZ.menuOpen()) || d.body.style.overflow === "hidden") lenis.stop();
        // mantém o lagSmoothing padrão: se a página travar no carregamento, as animações pausam em vez de pular
        return function () { gsap.ticker.remove(raf); lenis.destroy(); TZ.lenis = null; };
      }
    });
  }

  /* ---------------- Marcas (marquee) e FAQ ---------------- */
  TZ.marquee = function () {
    var ul = $("[data-brands]"), track = $("[data-marquee-track]");
    if (!ul || !track || track._done) return;
    track._done = true;
    var marcas = E.marcas || [];
    var esc = BC.escape;
    if (marcas.length) ul.innerHTML = marcas.map(function (m) { return '<li><span class="marquee__item">' + esc(m) + "</span></li>"; }).join("");
    var clone = ul.cloneNode(true);
    clone.setAttribute("aria-hidden", "true");
    track.appendChild(clone);
    var mq = $("[data-marquee]"), btn = $("[data-marquee-toggle]");
    if (btn && mq) btn.addEventListener("click", function () {
      var paused = mq.classList.toggle("is-paused");
      btn.setAttribute("aria-pressed", paused);
      btn.innerHTML = '<svg aria-hidden="true"><use href="#i-' + (paused ? "play" : "pause") + '"/></svg><span>' + (paused ? "CONTINUAR" : "PAUSAR") + "</span>";
    });
    if (TZ.reduce && btn) btn.hidden = true;
  };
  // Inclinação da faixa conforme a velocidade da rolagem; volta a zero quando a rolagem para.
  function marqueeMotion() {
    var skew = $(".marquee__skew");
    if (!skew || !TZ.motion || !window.ScrollTrigger) return;
    var to = gsap.quickTo(skew, "skewX", { duration: .5, ease: "power3.out" });
    ScrollTrigger.create({
      trigger: ".brands", start: "top bottom", end: "bottom top",
      onUpdate: function (s) { to(gsap.utils.clamp(-8, 8, s.getVelocity() / -260)); },
      onLeave: function () { to(0); }, onLeaveBack: function () { to(0); }
    });
    ScrollTrigger.addEventListener("scrollEnd", function () { to(0); });
  }

  /* =========================================================
     6. FAQ (acordeão acessível)
     ========================================================= */
  TZ.faq = function () {
    var refreshT = null;
    $$("[data-faq] .qa").forEach(function (qa, i) {
      var btn = $(".qa__btn", qa), panel = $(".qa__panel", qa);
      if (!btn || !panel || btn._faq) return; btn._faq = true;
      var id = "faq-" + (i + 1);
      btn.id = id + "-btn"; panel.id = id;
      btn.setAttribute("aria-controls", id);
      btn.addEventListener("click", function () {
        var open = btn.getAttribute("aria-expanded") !== "true";
        btn.setAttribute("aria-expanded", open);
        qa.classList.toggle("is-open", open);
        if (window.ScrollTrigger && TZ.hasG) { clearTimeout(refreshT); refreshT = setTimeout(function () { ScrollTrigger.refresh(); }, 560); }
      });
    });
  };

  /* ---------------- Contador da multa (IPEM) ---------------- */
  function initFine() {
    var fine = $("[data-fine]");
    if (!fine || !TZ.motion || !window.ScrollTrigger) return;
    var fmt = function (v) { return Math.round(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); };
    var o = { v: 100 };
    gsap.to(o, {
      v: 1500000, duration: 2.2, ease: "power3.inOut",
      onStart: function () { fine.textContent = fmt(100); },
      onUpdate: function () { fine.textContent = fmt(o.v); },
      scrollTrigger: { trigger: fine, start: "top 90%", once: true }
    });
  }

  /* ---------------- Animações contínuas pausam fora da tela ---------------- */
  function initOffscreen() {
    if (!("IntersectionObserver" in window)) return;
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { e.target.classList.toggle("is-off", !e.isIntersecting); });
    });
    $$(".sys__orbit, .btn--glow, .hero__halo, .marquee").forEach(function (el) { io.observe(el); });
  }

  /* ---------------- Foco nunca cai em algo invisível ---------------- */
  function initFocusGuard() {
    d.addEventListener("focusin", function (e) {
      var t = e.target.closest && e.target.closest("[data-fade], [data-stagger] > *, .pcard, .rail-end");
      if (!t || !window.gsap) return;
      if (+getComputedStyle(t).opacity < 1) { gsap.killTweensOf(t); gsap.set(t, { opacity: 1, y: 0 }); }
    });
  }

  /* ---------------- Boot ---------------- */
  // Fase de conteúdo (chamada pelo script da página depois de renderizar)
  TZ.boot = function () {
    initSpot(d);
  };

  // Fase de movimento: o GSAP (se chegou) já está disponível
  TZ.startMotion = function () {
    if (TZ._started) return;
    TZ._started = true;
    TZ.hasG = !!window.gsap;
    TZ.motion = TZ.hasG && !TZ.reduce;
    TZ.late = !html.classList.contains("is-intro");
    TZ.paintedAtStart = TZ.painted; // diagnóstico
    if (!TZ.hasG) {
      html.classList.remove("is-preloading", "is-intro");
      finishReady();
      deepLink();
      return;
    }
    initMotion();
    runAll(TZ._pre);
    initCursor();
    initMagnetic();
    TZ.bindTilt(d);
    TZ.reveal(d);
    initFine();
    $$("[data-count]").forEach(function (el) { TZ.count(el); });
    marqueeMotion();
    runAll(TZ._post);
    if (!$(".hero")) html.classList.remove("is-intro");
    initPreloader();
    if (window.ScrollTrigger) {
      // reposiciona gatilhos depois que imagens e fontes chegam
      if (d.readyState !== "complete") window.addEventListener("load", function () { ScrollTrigger.refresh(); }, { once: true });
      if (d.fonts && d.fonts.ready) d.fonts.ready.then(function () { ScrollTrigger.refresh(); });
    }
    deepLink();
  };

  // Início imediato: conteúdo e interações não dependem do CDN
  TZ.fill(d);
  paintStatus();
  setInterval(paintStatus, 60000);
  initHeader();
  initMenu();
  initAnchors();
  initOffscreen();
  initFocusGuard();

  if (d.readyState === "loading") d.addEventListener("DOMContentLoaded", TZ.startMotion);
  else setTimeout(TZ.startMotion, 0);
})();
