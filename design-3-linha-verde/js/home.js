/* =====================================================================
   Home — hero "PESA. IMPRIME. VENDE.", esteira de produtos, segmentos
   ===================================================================== */
(function () {
  "use strict";
  var BC = window.BC, LV = window.LV;
  if (!BC || !LV) return;
  var $ = LV.$, $$ = LV.$$;

  /* ---------- Hero: rótulos técnicos vindos do catálogo ---------- */
  var heroP = BC.porId("toledo-prix-4-uno");
  if (heroP) {
    var cap = LV.spec(heroP, "capacidade");
    if (cap) $$("[data-hero-cap]").forEach(function (el) { el.textContent = cap.split(" (")[0].split(" x ")[0]; });
    $$("[data-hero-name]").forEach(function (el) { el.textContent = heroP.marca + " " + heroP.nome; });
  }

  /* ---------- Esteira: destaques do catálogo ---------- */
  var track = $("[data-belt-track]");
  var destaques = BC.destaques();
  if (track) {
    var total = BC.produtos.length;
    track.innerHTML = destaques.map(function (p, i) {
      return "<li>" + LV.card(p, i + 1, { detailBase: "produtos.html", noSub: true, rows: 2, cls: "spec--belt", eager: true }) + "</li>";
    }).join("") +
      '<li><a class="belt__end" href="produtos.html">' +
        '<span class="mono">Catálogo completo</span>' +
        '<span class="belt__end-mid"><span class="belt__end-n">' + total + '</span><span class="belt__end-t">produtos para o seu balcão</span></span>' +
        '<span class="belt__end-go"><span class="st" data-t="Ver todos">Ver todos</span><svg class="i" aria-hidden="true"><use href="#i-arrow"/></svg></span>' +
      "</a></li>";
    $$("[data-belt-total]").forEach(function (el) { el.textContent = LV.pad(destaques.length); });
  }
  var rollers = $("[data-rollers]");
  if (rollers) {
    var n = Math.max(8, Math.round(window.innerWidth / 120));
    rollers.innerHTML = new Array(n + 1).join('<span class="roller"></span>');
  }
  var counter = $("[data-belt-count]");
  function setCount(i) { if (counter) counter.textContent = LV.pad(i); }

  // Contador também no modo nativo (celular): acompanha a rolagem horizontal
  var nav = $("[data-belt-nav]"), prev = $("[data-belt-prev]"), next = $("[data-belt-next]");
  function navState() {
    if (!track || !prev || !next) return;
    var max = track.scrollWidth - track.clientWidth;
    prev.disabled = track.scrollLeft <= 2;
    next.disabled = track.scrollLeft >= max - 2;
  }
  if (track) {
    track.addEventListener("scroll", function () {
      var cards = track.children; if (!cards.length) return;
      var w = cards[0].getBoundingClientRect().width + 22;
      setCount(Math.min(destaques.length, Math.round(track.scrollLeft / w) + 1));
      navState();
    }, { passive: true });
  }
  if (track && nav) {
    nav.hidden = false;
    var step = function (dir) {
      var card = track.children[0];
      var w = card ? card.getBoundingClientRect().width + 22 : 300;
      var smooth = !window.matchMedia("(prefers-reduced-motion: reduce)").matches;
      track.scrollBy({ left: dir * w, behavior: smooth ? "smooth" : "auto" });
    };
    prev.addEventListener("click", function () { step(-1); });
    next.addEventListener("click", function () { step(1); });
    navState();
    window.addEventListener("resize", navState);
  }

  /* ---------- Segmentos no toque: a linha central "acende" ---------- */
  function segTouch() {
    if (!window.matchMedia("(hover: none)").matches || !("IntersectionObserver" in window)) return;
    var io = new IntersectionObserver(function (en) {
      en.forEach(function (e) { e.target.classList.toggle("is-on", e.isIntersecting); });
    }, { rootMargin: "-42% 0px -42% 0px" });
    $$(".seg__row").forEach(function (r) { io.observe(r); });
  }
  segTouch();

  /* ---------- Animações da home ---------- */
  LV.onMotion(function () {
    var html = document.documentElement;
    html.classList.remove("is-loading");

    /* HERO: três cortes verdes revelam PESA. IMPRIME. VENDE. */
    var band = $(".hero__band");
    var viaVT = html.classList.contains("via-vt");   // chegou por transição entre páginas: sem replay
    var tl = gsap.timeline({ defaults: { ease: "expo.out" }, delay: viaVT ? 0 : 0.1 });
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

    tl.from(".stage__img", { yPercent: 16, autoAlpha: 0, duration: 1.4 }, 0.75)
      .from(".stage__shadow", { autoAlpha: 0, scaleX: 0.4, duration: 1.2 }, 0.9);
    if (window.DrawSVGPlugin) {
      tl.from(".stage__lines .draw", { drawSVG: "0%", duration: 0.9, stagger: 0.07, ease: "power2.inOut" }, 1.2);
    } else {
      tl.from(".stage__lines", { autoAlpha: 0, duration: 0.8 }, 1.2);
    }
    tl.from(".dot", { scale: 0, transformOrigin: "50% 50%", stagger: 0.1, duration: 0.5, ease: "back.out(3)" }, 1.45)
      .from(".callout", { autoAlpha: 0, y: 8, stagger: 0.08, duration: 0.6 }, 1.6)
      .from(".hero__vref", { autoAlpha: 0, duration: 1 }, 1.6)
      .from([".hero__eyebrow", ".hero__lead", ".hero__ctas", ".hero__trust"], { opacity: 0, y: 26, stagger: 0.09, duration: 1.1 }, 0.85);
    if (viaVT) tl.progress(1);

    /* HERO: ao rolar, as palavras se separam e o produto sobe */
    gsap.timeline({ scrollTrigger: { trigger: ".hero", start: "top top", end: "bottom top", scrub: true } })
      .to(".hero__line:nth-child(1)", { xPercent: -7, ease: "none" }, 0)
      .to(".hero__line:nth-child(2)", { xPercent: 5, ease: "none" }, 0)
      .to(".hero__line:nth-child(3)", { xPercent: -12, ease: "none" }, 0)
      .to(".stage", { yPercent: -10, ease: "none" }, 0)
      .to(band, { xPercent: 8, ease: "none" }, 0);

    /* HERO: leve inclinação 3D do produto acompanhando o mouse */
    if (window.matchMedia("(hover: hover) and (pointer: fine)").matches) {
      var stage = $("[data-tilt]");
      if (stage) {
        gsap.set(stage, { transformPerspective: 1100 });
        var rx = gsap.quickTo(stage, "rotationX", { duration: 0.8, ease: "power3.out" });
        var ry = gsap.quickTo(stage, "rotationY", { duration: 0.8, ease: "power3.out" });
        $(".hero").addEventListener("mousemove", function (e) {
          var px = e.clientX / window.innerWidth - 0.5, py = e.clientY / window.innerHeight - 0.5;
          ry(px * 10); rx(py * -7);
        });
        $(".hero").addEventListener("mouseleave", function () { rx(0); ry(0); });
      }
    }

    /* ESTEIRA: no desktop, fixa a seção e corre na horizontal */
    var belt = gsap.matchMedia();
    belt.add("(min-width: 901px) and (min-height: 700px)", function () {
      var sec = $("#produtos"), pin = $("[data-conveyor]");
      if (!sec || !pin || !track) return;
      sec.classList.add("is-belt");
      var dist = function () { return Math.max(0, track.scrollWidth - document.documentElement.clientWidth); };
      var tween = gsap.to(track, { x: function () { return -dist(); }, ease: "none" });
      var rl = $$(".roller");
      var beltST = ScrollTrigger.create({
        trigger: pin, animation: tween, pin: true, scrub: 0.7, anticipatePin: 1,
        start: function () { return "top top+=" + LV.hdr(); },
        end: function () { return "+=" + dist(); },
        invalidateOnRefresh: true, refreshPriority: 1,
        onUpdate: function (self) {
          setCount(Math.min(destaques.length, Math.floor(self.progress * destaques.length) + 1));
          gsap.set(rl, { rotation: self.progress * 1440 });
        }
      });
      // Teclado: o foco num card fora da tela leva a esteira até ele (o deslocamento é por transform)
      var onFocus = function (e) {
        var li = e.target.closest(".belt__track > li");
        if (!li || !beltST) return;
        var rel = li.getBoundingClientRect().left - track.getBoundingClientRect().left - parseFloat(getComputedStyle(track).paddingLeft);
        var d = Math.max(1, dist());
        var y = beltST.start + Math.min(1, Math.max(0, rel / d)) * (beltST.end - beltST.start);
        if (LV.lenis) LV.lenis.scrollTo(y, { immediate: true, force: true }); else window.scrollTo(0, y);
      };
      track.addEventListener("focusin", onFocus);
      // cada produto desliza dentro da própria ficha ao entrar na esteira
      $$(".belt__track .spec").forEach(function (card) {
        var img = $(".spec__fig img", card);
        if (img) gsap.from(img, { xPercent: 28, rotation: -6, ease: "none", scrollTrigger: { trigger: card, containerAnimation: tween, start: "left right", end: "left 45%", scrub: true } });
      });
      return function () {
        track.removeEventListener("focusin", onFocus);
        sec.classList.remove("is-belt"); gsap.set(track, { clearProps: "x" });
        requestAnimationFrame(function () { ScrollTrigger.refresh(); navState(); });
      };
    });

    /* SEGMENTOS: linhas entram uma a uma, número na frente */
    $$(".seg__row").forEach(function (row) {
      gsap.from(row.children, { opacity: 0, x: -30, duration: 0.9, stagger: 0.06, ease: "expo.out", scrollTrigger: LV.st(row, "top 92%") });
    });

    /* SAT → NFC-e: risca, aponta e acende */
    var sat = $("[data-sat]");
    if (sat) {
      gsap.set(".sat__strike", { rotation: -12, scaleX: 0, transformOrigin: "0% 50%" });
      gsap.timeline({ scrollTrigger: LV.st(sat, "top 75%") })
        .from(".sat__from", { opacity: 0, x: -30, duration: 0.7, ease: "expo.out" })
        .to(".sat__strike", { scaleX: 1, duration: 0.5, ease: "expo.inOut" }, 0.45)
        .from(".sat__arrow", { opacity: 0, x: -40, duration: 0.7, ease: "expo.out" }, 0.75)
        .from(".sat__to", { opacity: 0, skewX: -12, xPercent: -10, duration: 0.9, ease: "expo.out" }, 0.9);
    }

    /* Carimbo IPEM: cai do alto e "bate" na página */
    var stamp = $("[data-stamp]");
    if (stamp) {
      gsap.timeline({ scrollTrigger: LV.st(stamp, "top 85%") })
        .fromTo(stamp, { scale: 2.4, autoAlpha: 0, rotation: -32 }, { scale: 1, autoAlpha: 1, rotation: -12, duration: 0.55, ease: "power4.in" })
        .to(stamp, { scale: 0.94, duration: 0.08, yoyo: true, repeat: 1, ease: "power1.inOut" });
    }

    /* Roteador: lâminas entram em sequência a partir do corte diagonal */
    var tiles = $$(".router__a");
    if (tiles.length) {
      var narrow = window.matchMedia("(max-width: 640px)").matches;
      gsap.from(tiles, { yPercent: 18, opacity: 0, skewX: narrow ? 0 : -6, duration: 1.1, stagger: 0.1, ease: "expo.out", scrollTrigger: LV.st(".router", "top 85%") });
    }
  });
})();
