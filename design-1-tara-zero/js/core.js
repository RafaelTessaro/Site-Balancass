/* =====================================================================
   Tara Zero — núcleo compartilhado (todas as páginas)
   Dados da empresa, cabeçalho, menu, preloader, cursor, Lenis + GSAP,
   revelações ao rolar e o card de produto.
   ===================================================================== */
(function () {
  "use strict";

  var d = document;
  var html = d.documentElement;
  var BC = window.BC || null;
  var E = (BC && BC.empresa) || {};
  var hasG = !!window.gsap;
  var mqReduce = window.matchMedia("(prefers-reduced-motion: reduce)");
  var mqFine = window.matchMedia("(hover: hover) and (pointer: fine)");

  var TZ = window.TZ = {
    reduce: mqReduce.matches,
    fine: mqFine.matches,
    hasG: hasG,
    lenis: null,
    mm: null,
    _ready: [],
    _isReady: false
  };

  function $(s, r) { return (r || d).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || d).querySelectorAll(s)); }
  TZ.$ = $; TZ.$$ = $$;

  function headerH() { return $(".hdr") ? $(".hdr").offsetHeight : 72; }
  TZ.headerH = headerH;

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
      var v = txt[el.getAttribute("data-bc")];
      if (v !== undefined && v !== null && v !== "") el.textContent = v;
      else if (el.getAttribute("data-bc") === "referencia") el.hidden = true;
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
  function agoraSP() {
    try {
      var parts = new Intl.DateTimeFormat("en-US", { timeZone: "America/Sao_Paulo", weekday: "short", hour: "numeric", minute: "numeric", hourCycle: "h23" }).formatToParts(new Date());
      var o = {};
      parts.forEach(function (p) { o[p.type] = p.value; });
      var wd = { Sun: 0, Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6 }[o.weekday];
      var h = parseInt(o.hour, 10) % 24, m = parseInt(o.minute, 10);
      return { dia: wd, t: h + m / 60 };
    } catch (e) {
      var n = new Date(); return { dia: n.getDay(), t: n.getHours() + n.getMinutes() / 60 };
    }
  }
  TZ.status = function () {
    var a = agoraSP(), slots = GRADE[a.dia] || [], i;
    for (i = 0; i < slots.length; i++) {
      if (a.t >= slots[i][0] && a.t < slots[i][1]) return { aberto: true, txt: "Aberto agora · até " + slots[i][1] + "h" };
    }
    for (i = 0; i < slots.length; i++) {
      if (a.t < slots[i][0]) return { aberto: false, txt: (i > 0 ? "Pausa para almoço · volta às " : "Fechado · abre hoje às ") + slots[i][0] + "h" };
    }
    for (var k = 1; k <= 7; k++) {
      var dd = (a.dia + k) % 7;
      if (GRADE[dd] && GRADE[dd].length) {
        return { aberto: false, txt: "Fechado · abre " + (k === 1 ? "amanhã" : DIAS[dd]) + " às " + GRADE[dd][0][0] + "h" };
      }
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

  /* ---------------- Card de produto (vitrine e catálogo) ---------------- */
  TZ.card = function (p, o) {
    o = o || {};
    var e = BC.escape;
    var est = BC.estoque(p);
    var href = (o.base || "") + "#p=" + encodeURIComponent(p.id);
    var cond = p.condicao === "seminovo" ? '<span class="badge badge--cond">Seminovo</span>' : "";
    var chips = (p.especificacoes || []).filter(function (s) { return s && s[1] && String(s[1]).length <= 24 && String(s[0]).length <= 14; })
      .slice(0, 2).map(function (s) { return "<li><span>" + e(s[0]) + "</span>" + e(s[1]) + "</li>"; }).join("");
    var nomeCompleto = p.marca + " " + p.nome;
    return '<article class="pcard" data-id="' + e(p.id) + '" data-cursor="view">' +
      '<div class="pcard__in" data-tilt>' +
        '<div class="stage pcard__stage"><img src="' + e(BC.imgProduto(p, false)) + '" alt="' + e(nomeCompleto) + '" width="800" height="600" loading="lazy" decoding="async">' +
        (o.fig ? '<span class="stage__fig" aria-hidden="true">' + e(o.fig) + "</span>" : "") + "</div>" +
        '<div class="pcard__badges"><span class="badge badge--' + e(est.classe) + '">' + e(est.rotulo) + "</span>" + cond + "</div>" +
        '<div class="pcard__body">' +
          '<p class="pcard__meta"><b>' + e(p.marca) + "</b> · " + e(p.subcategoria) + "</p>" +
          '<h3 class="pcard__name"><a href="' + href + '" data-detail="' + e(p.id) + '">' + e(p.nome) + "</a></h3>" +
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

  /* ---------------- Cabeçalho, progresso e botão flutuante ---------------- */
  function initHeader() {
    var hdr = $(".hdr"), bar = $(".hdr__progress"), fab = $("[data-fab]"), hero = $(".hero");
    var ticking = false;
    function update() {
      ticking = false;
      var y = window.scrollY || window.pageYOffset;
      var max = Math.max(1, d.documentElement.scrollHeight - window.innerHeight);
      if (hdr) hdr.classList.toggle("is-scrolled", y > 12);
      if (bar) bar.style.transform = "scaleX(" + Math.min(1, y / max).toFixed(4) + ")";
      if (fab && hero) fab.classList.toggle("is-hidden", y < window.innerHeight * 0.55);
    }
    window.addEventListener("scroll", function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
    window.addEventListener("resize", update);
    update();
  }

  /* ---------------- Menu móvel ---------------- */
  function initMenu() {
    var btn = $(".burger"), menu = $("#mmenu");
    if (!btn || !menu) return;
    var lastFocus = null;
    function focusables() { return $$('a[href], button:not([disabled])', menu).concat([btn]); }
    function open() {
      lastFocus = d.activeElement;
      menu.hidden = false;
      btn.setAttribute("aria-expanded", "true");
      btn.setAttribute("aria-label", "Fechar menu");
      $(".hdr").classList.add("is-solid");
      if (TZ.lenis) TZ.lenis.stop();
      d.body.style.overflow = "hidden";
      if (hasG && !TZ.reduce) {
        gsap.fromTo($$(".mmenu__list li, .mmenu__foot > *", menu), { y: 28, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: .6, ease: "expo.out", stagger: .045 });
      }
      var f = $("a", menu); if (f) f.focus();
    }
    function close(noFocus) {
      menu.hidden = true;
      btn.setAttribute("aria-expanded", "false");
      btn.setAttribute("aria-label", "Abrir menu");
      $(".hdr").classList.remove("is-solid");
      d.body.style.overflow = "";
      if (TZ.lenis) TZ.lenis.start();
      if (!noFocus && lastFocus) lastFocus.focus();
    }
    btn.addEventListener("click", function () { menu.hidden ? open() : close(); });
    menu.addEventListener("keydown", function (ev) {
      if (ev.key === "Escape") { close(); return; }
      if (ev.key !== "Tab") return;
      var f = focusables(), first = f[0], last = f[f.length - 1];
      if (ev.shiftKey && d.activeElement === first) { ev.preventDefault(); last.focus(); }
      else if (!ev.shiftKey && d.activeElement === last) { ev.preventDefault(); first.focus(); }
    });
    btn.addEventListener("keydown", function (ev) { if (ev.key === "Escape" && !menu.hidden) close(); });
    $$("a", menu).forEach(function (a) {
      a.addEventListener("click", function (ev) {
        var h = a.getAttribute("href") || "";
        close(true);
        if (h.charAt(0) === "#") {
          ev.preventDefault();
          TZ.scrollTo(h);
        }
      });
    });
    window.addEventListener("resize", function () { if (window.innerWidth >= 1024 && !menu.hidden) close(true); });
  }

  /* ---------------- Rolagem suave até âncoras ---------------- */
  TZ.scrollTo = function (hash) {
    var target = null;
    if (hash === "#topo" || hash === "#") target = 0;
    else { try { target = d.querySelector(hash); } catch (e) { target = null; } }
    if (target === null) return;
    if (TZ.lenis) TZ.lenis.scrollTo(target, { offset: target === 0 ? 0 : -(headerH() + 12), duration: 1.4 });
    else if (target === 0) window.scrollTo({ top: 0, behavior: TZ.reduce ? "auto" : "smooth" });
    else {
      var y = target.getBoundingClientRect().top + window.scrollY - headerH() - 12;
      window.scrollTo({ top: y, behavior: TZ.reduce ? "auto" : "smooth" });
    }
    if (target && target.focus && target !== 0) {
      if (!target.hasAttribute("tabindex")) target.setAttribute("tabindex", "-1");
      target.focus({ preventScroll: true });
    }
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

  /* ---------------- Logo animado ---------------- */
  TZ.logoIn = function (svg, opts) {
    if (!hasG || !svg) return null;
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

  function initPreloader() {
    var pre = $(".preloader");
    if (!html.classList.contains("is-preloading") || !pre || !hasG || TZ.reduce) {
      html.classList.remove("is-preloading");
      finishReady();
      return;
    }
    try { sessionStorage.setItem("tz-pre", "1"); } catch (e) {}
    var read = $(".preloader__read", pre);
    var o = { v: 0 };
    var tl = gsap.timeline({ onComplete: function () { pre.style.display = "none"; finishReady(); } });
    tl.add(TZ.logoIn($(".preloader__mark", pre), { ring: .55, bq: .5 }), 0)
      .to(o, { v: 1, duration: .6, ease: "power2.out", onUpdate: function () { if (read) read.textContent = ((1 - o.v) * (1 - o.v) * Math.random() * 9.999).toFixed(3); }, onComplete: function () { if (read) read.textContent = "0.000"; } }, .05)
      .from($$(".preloader__read, .preloader__lbl", pre), { autoAlpha: 0, y: 8, duration: .4, stagger: .06, ease: "power2.out" }, .15)
      .to(pre, { yPercent: -100, duration: .5, ease: "power3.inOut" }, .62)
      .add(function () { finishReady(); }, .7);
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
    if (!hasG || TZ.reduce || !mqFine.matches) return;
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
    if (!c || !hasG || TZ.reduce || !mqFine.matches) return;
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
    if (!hasG || TZ.reduce || !mqFine.matches) return;
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

  TZ.reveal = function (root) {
    if (!hasG || TZ.reduce || !window.ScrollTrigger) return;
    root = root || d;
    $$("[data-split]", root).forEach(function (el) {
      if (el._split || !window.SplitText) return; el._split = true;
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
      gsap.from(el, { y: 32, autoAlpha: 0, duration: 1.1, ease: "power3.out", scrollTrigger: { trigger: el, start: "top 90%", once: true } });
    });
    $$("[data-stagger]", root).forEach(function (el) {
      if (el._stag) return;
      var kids = Array.prototype.slice.call(el.children).filter(function (k) { return k.tagName !== "NOSCRIPT"; });
      if (!kids.length) return;
      el._stag = true;
      gsap.from(kids, { y: 44, autoAlpha: 0, duration: 1, ease: "power3.out", stagger: .08, scrollTrigger: { trigger: el, start: "top 88%", once: true } });
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
    if (!hasG || TZ.reduce || !window.ScrollTrigger) { el.textContent = fmt(to); return; }
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
    if (!hasG) return;
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
        // mantém o lagSmoothing padrão: se a página travar no carregamento, as animações pausam em vez de pular
        return function () { gsap.ticker.remove(raf); lenis.destroy(); TZ.lenis = null; };
      }
    });
  }

  /* ---------------- Marcas (marquee) e FAQ ---------------- */
  TZ.marquee = function () {
    var ul = $("[data-brands]"), track = $("[data-marquee-track]");
    if (!ul || !track) return;
    var marcas = E.marcas || [];
    var motion = hasG && !TZ.reduce;
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
    if (motion && window.ScrollTrigger) {
      var skew = $(".marquee__skew");
      var to = gsap.quickTo(skew, "skewX", { duration: .5, ease: "power3.out" });
      ScrollTrigger.create({
        trigger: ".brands", start: "top bottom", end: "bottom top",
        onUpdate: function (s) { to(gsap.utils.clamp(-8, 8, s.getVelocity() / -260)); },
        onLeave: function () { to(0); }, onLeaveBack: function () { to(0); }
      });
    }
  };

  /* =========================================================
     6. FAQ (acordeão acessível)
     ========================================================= */
  TZ.faq = function () {
    var refreshT = null;
    $$("[data-faq] .qa").forEach(function (qa, i) {
      var btn = $(".qa__btn", qa), panel = $(".qa__panel", qa);
      var id = "faq-" + (i + 1);
      btn.id = id + "-btn"; panel.id = id;
      btn.setAttribute("aria-controls", id);
      panel.setAttribute("aria-labelledby", btn.id);
      btn.addEventListener("click", function () {
        var open = btn.getAttribute("aria-expanded") !== "true";
        btn.setAttribute("aria-expanded", open);
        qa.classList.toggle("is-open", open);
        if (window.ScrollTrigger) { clearTimeout(refreshT); refreshT = setTimeout(function () { ScrollTrigger.refresh(); }, 560); }
      });
    });
  };

  /* ---------------- Contador da multa (IPEM) ---------------- */
  function initFine() {
    var fine = $("[data-fine]");
    if (!fine || !hasG || TZ.reduce || !window.ScrollTrigger) return;
    var fmt = function (v) { return Math.round(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); };
    var o = { v: 100 };
    gsap.to(o, {
      v: 1500000, duration: 2.2, ease: "power3.inOut",
      onStart: function () { fine.textContent = fmt(100); },
      onUpdate: function () { fine.textContent = fmt(o.v); },
      scrollTrigger: { trigger: fine, start: "top 90%", once: true }
    });
  }

  /* ---------------- Boot ---------------- */
  TZ.boot = function () {
    initSpot(d);
    TZ.bindTilt(d);
    TZ.reveal(d);
    initFine();
    if (!$(".hero")) html.classList.remove("is-intro");
    $$("[data-count]").forEach(function (el) { TZ.count(el); });
    if (window.ScrollTrigger) {
      // reposiciona gatilhos depois que imagens e fontes chegam
      window.addEventListener("load", function () { ScrollTrigger.refresh(); });
      if (d.fonts && d.fonts.ready) d.fonts.ready.then(function () { ScrollTrigger.refresh(); });
    }
    // âncora vinda de outra página (ex.: index.html#contato)
    var h = location.hash;
    if (h && /^#[A-Za-z][\w-]*$/.test(h) && h !== "#topo") {
      setTimeout(function () { TZ.scrollTo(h); }, 350);
    }
  };

  // Início imediato (scripts com defer já rodam com o DOM pronto)
  TZ.fill(d);
  paintStatus();
  setInterval(paintStatus, 60000);
  initHeader();
  initMenu();
  initAnchors();
  initMotion();
  initCursor();
  initMagnetic();
  initPreloader();

  if (!hasG) {
    html.classList.remove("is-preloading", "is-intro");
  }
})();
