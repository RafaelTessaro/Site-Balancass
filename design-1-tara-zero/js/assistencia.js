/* =====================================================================
   Tara Zero — Assistência técnica
   Display de diagnóstico (Err → reparo → teste de segmentos → 0.000),
   linha do processo, tipos de balança (a partir do catálogo), marcas e FAQ.
   ===================================================================== */
(function () {
  "use strict";

  var TZ = window.TZ, BC = window.BC;
  if (!TZ || !BC) return;
  var $ = TZ.$, $$ = TZ.$$, d = document;
  var esc = BC.escape;
  var motion = TZ.hasG && !TZ.reduce;

  /* ---------------- Tipos de balança (imagens do catálogo) ---------------- */
  var TIPOS = [
    { id: "toledo-prix-3-fit", sub: "Computadoras", titulo: "Comerciais", texto: "Pesam e calculam o preço no balcão." },
    { id: "toledo-prix-4-uno", sub: "Com impressora de etiquetas", titulo: "Com etiqueta", texto: "Pesam e imprimem etiqueta com código de barras." },
    { id: "toledo-8217", sub: "Checkout", titulo: "Checkout", texto: "Integradas ao caixa do mercado." },
    { id: "toledo-2098", sub: "Industriais e conferência", titulo: "Industriais", texto: "Plataformas para volumes e conferência." },
    { id: "toledo-hospitalar", sub: "Médico-hospitalar", titulo: "Hospitalares", texto: "Para farmácias, clínicas e academias." }
  ];
  function renderTypes() {
    var box = $("[data-types]");
    if (!box) return;
    box.innerHTML = TIPOS.map(function (t, i) {
      var p = BC.porId(t.id);
      var img = p ? '<img src="' + esc(BC.imgProduto(p, false)) + '" alt="' + esc(p.marca + " " + p.nome) + '" width="400" height="400" loading="lazy" decoding="async">' : "";
      return '<a class="type" href="produtos.html?cat=balancas&amp;sub=' + encodeURIComponent(t.sub) + '" data-cursor="view">' +
        '<span class="stage">' + img + "</span>" +
        '<span class="type__n">' + ("0" + (i + 1)).slice(-2) + "</span>" +
        "<h3>" + esc(t.titulo) + "</h3><p>" + esc(t.texto) + "</p>" +
        '<span class="type__go">Ver modelos <svg aria-hidden="true"><use href="#i-arrow-ur"/></svg></span></a>';
    }).join("");
  }

  /* ---------------- Display de diagnóstico ---------------- */
  var seg = $("[data-diag]"), segWrap = seg && seg.parentNode;
  var steps = $$("[data-dstep]"), statusEl = $("[data-diag-status]");
  function led(n, on) { var l = $('[data-dled="' + n + '"]'); if (l) l.classList.toggle("is-on", !!on); }
  function finalState() {
    if (!seg) return;
    seg.textContent = "0.000"; segWrap.classList.remove("is-err");
    led("falha", false); led("reparo", false); led("ok", true);
    steps.forEach(function (s) { s.classList.add("is-done"); });
    if (statusEl) statusEl.textContent = "PRONTA PARA O IPEM";
  }
  var diagTl = null;
  function runDiag(delay) {
    if (!seg) return;
    if (!motion) { finalState(); return; }
    if (diagTl) diagTl.kill();
    gsap.set(seg, { autoAlpha: 1 });
    diagTl = gsap.timeline({ delay: delay || 0 })
      .add(function () {
        steps.forEach(function (s) { s.classList.remove("is-done"); });
        led("ok", false); led("reparo", false); led("falha", true);
        segWrap.classList.add("is-err"); seg.textContent = "Err";
        if (statusEl) statusEl.textContent = "COM DEFEITO";
      }, 0)
      .to(seg, { autoAlpha: .12, duration: .14, repeat: 5, yoyo: true, ease: "steps(1)" }, .1)
      .add(function () { steps[0].classList.add("is-done"); }, .95)
      .add(function () {
        led("falha", false); led("reparo", true); segWrap.classList.remove("is-err");
        seg.textContent = "-----"; if (statusEl) statusEl.textContent = "EM REPARO";
      }, 1.35)
      .add(function () { steps[1].classList.add("is-done"); }, 1.9)
      .add(function () { seg.textContent = "88.888"; }, 2.35)
      .add(function () { seg.textContent = "0.000"; steps[2].classList.add("is-done"); }, 2.95)
      .add(function () {
        led("reparo", false); led("ok", true); steps[3].classList.add("is-done");
        if (statusEl) statusEl.textContent = "PRONTA PARA O IPEM";
      }, 3.45);
  }
  var runBtn = $("[data-diag-run]");
  if (runBtn) runBtn.addEventListener("click", function () { runDiag(0); });

  /* ---------------- Linha do processo ---------------- */
  function initSteps() {
    var box = $("[data-steps]");
    if (!box || !motion || !window.ScrollTrigger || !TZ.mm) return;
    TZ.mm.add("(min-width: 1024px) and (prefers-reduced-motion: no-preference)", function () {
      var bar = d.createElement("span");
      bar.className = "steps__bar";
      bar.setAttribute("aria-hidden", "true");
      box.appendChild(bar);
      var items = $$(".step", box);
      gsap.fromTo(bar, { scaleX: 0 }, {
        scaleX: 1, ease: "none",
        scrollTrigger: {
          trigger: box, start: "top 80%", end: "bottom 55%", scrub: .6,
          onUpdate: function (s) {
            items.forEach(function (it, i) { it.classList.toggle("is-lit", s.progress >= i / items.length + .02); });
          }
        }
      });
      return function () { bar.remove(); items.forEach(function (it) { it.classList.remove("is-lit"); }); };
    });
  }

  /* ---------------- Intro do topo ---------------- */
  function intro() {
    if (!motion) { finalState(); return; }
    gsap.from(".ahero__product img", { autoAlpha: 0, yPercent: 8, scale: .95, duration: 1.5, ease: "expo.out", delay: .1 });
    gsap.from(".ahero .hero__halo", { autoAlpha: 0, scale: .5, duration: 1.6, ease: "expo.out", delay: .2 });
    gsap.from(".diag", { autoAlpha: 0, y: 40, duration: 1.2, ease: "expo.out", delay: .35 });
    gsap.from(".ahero__chips li", { autoAlpha: 0, y: 14, duration: .8, ease: "power3.out", stagger: .06, delay: .6 });
    runDiag(1.1);
  }

  /* ---------------- Boot ---------------- */
  renderTypes();
  TZ.marquee();
  TZ.faq();
  TZ.fill(d);
  TZ.boot();
  initSteps();
  intro();
})();
