/* =====================================================================
   Balanças.com — "Linha Verde" — js/assistencia.js
   Só em assistencia.html (depois do core.js): rótulos técnicos do hero
   vindos do catálogo, linha do processo e antes → depois.
   ===================================================================== */
(function () {
  "use strict";
  var LV = window.LV, BC = window.BC;
  if (!LV || !BC) return;
  var $ = LV.$, $$ = LV.$$;

  // Rótulos do hero a partir do produto (Balmak K-300)
  var p = BC.porId("balmak-w300");
  if (p) {
    var cap = LV.spec(p, "capacidade");
    if (cap) $$("[data-hero-cap]").forEach(function (el) { el.textContent = cap.split(" (")[0].split(" x ")[0]; });
    $$("[data-hero-name]").forEach(function (el) { el.textContent = p.marca + " " + p.nome; });
  }

  LV.onMotion(function () {
    // Processo: a linha e os marcadores acompanham a rolagem; o texto de cada
    // estação aparece com gatilho próprio (quem para de rolar não vê colunas vazias)
    var flow = $("[data-flow]");
    if (flow) {
      var st = $$(".flow__st", flow);
      gsap.set(flow, { "--fill": 0 });
      var ftl = gsap.timeline({ scrollTrigger: { trigger: flow, start: "top 85%", end: "top 28%", scrub: 0.6 } });
      ftl.to(flow, { "--fill": 1, ease: "none", duration: 1 }, 0);
      st.forEach(function (s, i) {
        var at = i / Math.max(1, st.length - 1) * 0.9;
        ftl.from($(".flow__mk", s), { scale: 0, duration: 0.08, ease: "none" }, at);
        gsap.from(s.querySelectorAll(".flow__n, .flow__t, p"), {
          opacity: 0, y: 24, stagger: 0.05, duration: 0.8, ease: "expo.out", delay: i * 0.08,
          scrollTrigger: LV.st(s, "top 85%")
        });
      });
    }

    // Antes → depois: a seta empurra o "depois"
    $$(".ba__row").forEach(function (row) {
      gsap.from([$(".ba__ar", row), $(".ba__to", row)], { x: -24, opacity: 0, stagger: 0.1, duration: 0.9, ease: "expo.out", scrollTrigger: LV.st(row, "top 88%") });
    });

    // Tipos de balança: linhas entram da esquerda
    $$(".type").forEach(function (row) {
      gsap.from(row.children, { opacity: 0, x: -30, duration: 0.9, stagger: 0.06, ease: "expo.out", scrollTrigger: LV.st(row, "top 92%") });
    });
  });
})();
