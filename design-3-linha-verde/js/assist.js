/* =====================================================================
   Assistência técnica — hero e linha do processo
   ===================================================================== */
(function () {
  "use strict";
  var LV = window.LV;
  if (!window.BC || !LV) return;
  var $ = LV.$, $$ = LV.$$;

  // Rótulos técnicos do hero vindos do catálogo (K-300)
  var p = window.BC.porId("balmak-w300");
  if (p) {
    var cap = LV.spec(p, "capacidade");
    if (cap) $$("[data-hero-cap]").forEach(function (el) { el.textContent = cap.split(" (")[0].split(" x ")[0]; });
    $$("[data-hero-name]").forEach(function (el) { el.textContent = p.marca + " " + p.nome; });
  }

  LV.onMotion(function () {
    document.documentElement.classList.remove("is-loading");

    // HERO: mesmos cortes diagonais da home
    var band = $(".hero__band");
    var tl = gsap.timeline({ defaults: { ease: "expo.out" }, delay: 0.1 });
    gsap.set(band, { skewX: -12 });
    tl.from(band, { scaleY: 0, transformOrigin: "50% 0%", duration: 1.2, ease: "expo.inOut" }, 0);
    $$(".hero__line").forEach(function (line, i) {
      var bar = $(".hero__bar", line), word = $(".hero__word", line);
      gsap.set(bar, { skewX: -12, scaleX: 0, transformOrigin: "0% 50%" });
      gsap.set(word, { autoAlpha: 0 });
      var t0 = 0.28 + i * 0.17;
      tl.to(bar, { scaleX: 1, duration: 0.42, ease: "expo.in" }, t0)
        .set(word, { autoAlpha: 1 }, t0 + 0.42)
        .set(bar, { transformOrigin: "100% 50%" }, t0 + 0.42)
        .to(bar, { scaleX: 0, duration: 0.6, ease: "expo.out" }, t0 + 0.44);
    });
    tl.from(".stage__img", { yPercent: 14, autoAlpha: 0, duration: 1.4 }, 0.75)
      .from(".stage__shadow", { autoAlpha: 0, scaleX: 0.4, duration: 1.2 }, 0.9);
    if (window.DrawSVGPlugin) tl.from(".stage__lines .draw", { drawSVG: "0%", duration: 0.9, stagger: 0.07, ease: "power2.inOut" }, 1.2);
    tl.from(".dot", { scale: 0, transformOrigin: "50% 50%", stagger: 0.1, duration: 0.5, ease: "back.out(3)" }, 1.45)
      .from(".callout", { autoAlpha: 0, y: 8, stagger: 0.08, duration: 0.6 }, 1.6)
      .from(".hero__vref", { autoAlpha: 0, duration: 1 }, 1.6)
      .from([".crumbs", ".hero__lead", ".hero__ctas", ".hero__trust"], { autoAlpha: 0, y: 26, stagger: 0.09, duration: 1.1 }, 0.85);

    gsap.timeline({ scrollTrigger: { trigger: ".hero", start: "top top", end: "bottom top", scrub: true } })
      .to(".hero__line:nth-child(1)", { xPercent: -6, ease: "none" }, 0)
      .to(".hero__line:nth-child(2)", { xPercent: 5, ease: "none" }, 0)
      .to(".hero__line:nth-child(3)", { xPercent: -12, ease: "none" }, 0)
      .to(".stage", { yPercent: -10, ease: "none" }, 0)
      .to(band, { xPercent: 8, ease: "none" }, 0);

    if (window.matchMedia("(hover: hover) and (pointer: fine)").matches) {
      var stage = $("[data-tilt]");
      if (stage) {
        gsap.set(stage, { transformPerspective: 1100 });
        var rx = gsap.quickTo(stage, "rotationX", { duration: 0.8, ease: "power3.out" });
        var ry = gsap.quickTo(stage, "rotationY", { duration: 0.8, ease: "power3.out" });
        $(".hero").addEventListener("mousemove", function (e) { ry((e.clientX / innerWidth - 0.5) * 10); rx((e.clientY / innerHeight - 0.5) * -7); });
        $(".hero").addEventListener("mouseleave", function () { rx(0); ry(0); });
      }
    }

    // FLUXO: a linha se desenha com a rolagem e cada estação acende
    var flow = $("[data-flow]");
    if (flow) {
      var st = $$(".flow__st", flow);
      gsap.set(flow, { "--fill": 0 });
      var ftl = gsap.timeline({ scrollTrigger: { trigger: flow, start: "top 85%", end: "top 28%", scrub: 0.6 } });
      ftl.to(flow, { "--fill": 1, ease: "none", duration: 1 }, 0);
      st.forEach(function (s, i) {
        var at = i / Math.max(1, st.length - 1) * 0.9;
        ftl.from($(".flow__mk", s), { scale: 0, duration: 0.08, ease: "none" }, at)
           .from([$(".flow__n", s), $(".flow__t", s), $("p", s)], { autoAlpha: 0, y: 24, stagger: 0.02, duration: 0.12, ease: "none" }, at);
      });
    }

    // Antes → depois: a seta empurra o "depois"
    $$(".ba__row").forEach(function (row) {
      gsap.from([$(".ba__ar", row), $(".ba__to", row)], { x: -24, autoAlpha: 0, stagger: 0.1, duration: 0.9, ease: "expo.out", scrollTrigger: LV.st(row, "top 88%") });
    });

    var stamp = $("[data-stamp]");
    if (stamp) {
      gsap.timeline({ scrollTrigger: LV.st(stamp, "top 85%") })
        .fromTo(stamp, { scale: 2.4, autoAlpha: 0, rotation: -32 }, { scale: 1, autoAlpha: 1, rotation: -12, duration: 0.55, ease: "power4.in" })
        .to(stamp, { scale: 0.94, duration: 0.08, yoyo: true, repeat: 1, ease: "power1.inOut" });
    }
  });
})();
