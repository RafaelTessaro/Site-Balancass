/* =====================================================================
   Balanças.com — Design 2 "Balcão" — movimento.js
   Último script da página: roda depois do GSAP/Lenis (CDN). Se o CDN
   falhar ou travar, este arquivo não roda e o site continua completo,
   só sem animação.
   ===================================================================== */
(function () {
  "use strict";
  var D2 = window.D2;
  if (!D2 || !D2.iniciarMovimento) return;
  D2.iniciarMovimento();
  (D2.onMovimento || []).forEach(function (f) {
    try { f(); } catch (e) { setTimeout(function () { throw e; }); }
  });
})();
