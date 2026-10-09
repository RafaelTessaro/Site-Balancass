/* =====================================================================
   Balanças.com — "Linha Verde" — js/preview.js
   ---------------------------------------------------------------------
   SÓ PARA A FASE DE ESCOLHA DA PÁGINA INICIAL. REMOVER NA PUBLICAÇÃO:
   apague este arquivo, a linha <script defer src="js/preview.js"> de
   todas as páginas e os arquivos index-2.html / index-3.html que não
   forem escolhidos (a variação escolhida vira o index.html).
   ---------------------------------------------------------------------
   (a) Numa página inicial (<body data-variacao="1|2|3">): guarda a
       variação ("lv-home") em localStorage — vale também para links
       abertos em nova aba — e em sessionStorage (reserva, quando o
       localStorage está bloqueado). Mostra o seletor de variação:
       no computador, "Variação 1 · 2 · 3" no canto inferior esquerdo;
       no celular, só uma pílula "V1" que abre as opções ao tocar
       (não cobre o conteúdo nem os botões flutuantes de WhatsApp/Ligar;
       some no topo da página e aparece ao rolar).
   (b) Em qualquer página: todo link com o atributo data-home aponta para
       a variação guardada (padrão: index.html), para o "Início" levar de
       volta à variação que está sendo avaliada.
   ===================================================================== */
(function () {
  "use strict";
  var KEY = "lv-home";
  var ARQ = { "1": "index.html", "2": "index-2.html", "3": "index-3.html" };
  var VALIDO = /^index(-[23])?\.html$/;
  var body = document.body;
  var v = body && body.getAttribute("data-variacao");

  function get(s) { try { return window[s].getItem(KEY); } catch (e) { return null; } }
  function set(s, x) { try { window[s].setItem(KEY, x); } catch (e) { /* bloqueado / modo privado */ } }
  // Esta aba primeiro (sessionStorage); senão, a última escolha em qualquer aba (localStorage)
  function ler() {
    var a = get("sessionStorage"); if (VALIDO.test(a || "")) return a;
    var b = get("localStorage"); return VALIDO.test(b || "") ? b : null;
  }
  function gravar(x) { set("localStorage", x); set("sessionStorage", x); }

  var atual = ARQ[v] || null;
  if (atual) gravar(atual);
  var home = atual || ler() || "index.html";

  // (b) links "Início" e logotipo
  function apontar(h) {
    Array.prototype.forEach.call(document.querySelectorAll("a[data-home]"), function (a) {
      var hash = (a.getAttribute("href") || "").split("#")[1];
      a.setAttribute("href", h + (hash ? "#" + hash : ""));
    });
  }
  apontar(home);

  // (a) seletor de variação (só nas páginas iniciais)
  if (!atual) return;
  var css = document.createElement("style");
  css.textContent =
    ".lvp{position:fixed;left:max(10px,env(safe-area-inset-left));bottom:calc(10px + env(safe-area-inset-bottom));z-index:89;" +
    "display:flex;align-items:center;gap:2px;padding:0 4px 0 12px;background:rgba(10,10,10,.92);color:#F4F4F0;" +
    "border:1px solid #3A3A38;box-shadow:0 10px 30px -10px rgba(0,0,0,.6);font:500 10.5px/1 'Martian Mono',ui-monospace,monospace;" +
    "letter-spacing:.04em;text-transform:uppercase;-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px)}" +
    ".lvp__l{color:#8A8A85;margin-right:6px;white-space:nowrap}" +
    ".lvp__op{display:flex;align-items:center;gap:2px}" +
    ".lvp__op a{position:relative;display:grid;place-items:center;width:40px;height:44px;color:#F4F4F0;text-decoration:none;font-size:13px}" +
    ".lvp__op a::before{content:'';position:absolute;inset:9px 5px;transform:skewX(-12deg);border:1px solid #494949;transition:background .25s,border-color .25s}" +
    ".lvp__op a:hover::before{border-color:#019F42}" +
    ".lvp__op a[aria-current]{color:#0A0A0A}" +
    ".lvp__op a[aria-current]::before{background:#019F42;border-color:#019F42}" +
    ".lvp__op a span{position:relative}" +
    ".lvp a:focus-visible,.lvp__t:focus-visible{outline:2px solid #C6FF3D;outline-offset:-4px}" +
    ".lvp__t{display:none}" +
    /* Celular: pílula "V1" (alvo de toque 44 × 44) que abre as opções ao tocar */
    "@media (max-width:760px){" +
      ".lvp{padding:0;gap:0;background:none;border:0;box-shadow:none;-webkit-backdrop-filter:none;backdrop-filter:none;left:max(6px,env(safe-area-inset-left));bottom:calc(14px + env(safe-area-inset-bottom))}" +
      ".lvp__l{display:none}" +
      ".lvp__t{display:grid;place-items:center;isolation:isolate;width:44px;height:44px;margin:0;padding:0;border:0;background:none;color:#F4F4F0;font:inherit;cursor:pointer;-webkit-tap-highlight-color:transparent}" +
      ".lvp__t span[aria-hidden]{position:relative;display:flex;align-items:center;justify-content:center;white-space:nowrap;min-width:36px;height:24px;padding:0 7px;font-size:10.5px;letter-spacing:.04em}" +
      ".lvp__t span[aria-hidden]::before{content:'';position:absolute;inset:0;z-index:-1;transform:skewX(-12deg);background:rgba(10,10,10,.9);" +
        "border:1px solid #3A3A38;box-shadow:0 6px 18px -6px rgba(0,0,0,.6)}" +
      ".lvp__t span b{color:#019F42;font-weight:500}" +
      ".lvp__t[aria-expanded=true] span[aria-hidden]{color:#0A0A0A}" +
      ".lvp__t[aria-expanded=true] span[aria-hidden]::before{background:#019F42;border-color:#019F42}" +
      ".lvp__t[aria-expanded=true] span b{color:#0A0A0A}" +
      ".lvp__op{display:none;margin-left:2px;padding:0 2px;background:rgba(10,10,10,.94);border:1px solid #3A3A38;box-shadow:0 10px 30px -10px rgba(0,0,0,.6)}" +
      ".lvp.is-open .lvp__op{display:flex}" +
      /* No topo da página a pílula sai de cena (não cobre o fim do hero); volta ao rolar */
      ".lvp{transition:opacity .3s,transform .3s,visibility 0s}" +
      ".lvp.lvp--topo{opacity:0;visibility:hidden;transform:translateY(12px);pointer-events:none;transition:opacity .3s,transform .3s,visibility 0s .3s}" +
    "}" +
    "@media print{.lvp{display:none}}";
  document.head.appendChild(css);

  var nav = document.createElement("nav");
  nav.className = "lvp";
  nav.setAttribute("data-preview", "");
  nav.setAttribute("aria-label", "Variações da página inicial (pré-visualização)");
  nav.innerHTML =
    '<button class="lvp__t" type="button" aria-expanded="false" aria-controls="lvp-op">' +
      '<span aria-hidden="true"><b>V</b>' + v + '</span><span class="sr-only">Variação ' + v + ' — trocar de variação</span></button>' +
    '<span class="lvp__l" aria-hidden="true">Var<b>iação</b></span>' +
    '<span class="lvp__op" id="lvp-op">' +
    ["1", "2", "3"].map(function (n) {
      var cur = n === v ? ' aria-current="page"' : "";
      return '<a href="' + ARQ[n] + '"' + cur + ' aria-label="Variação ' + n + '"><span>' + n + "</span></a>";
    }).join("") + "</span>";
  body.appendChild(nav);

  var btn = nav.querySelector(".lvp__t");
  function abrir(on) {
    nav.classList.toggle("is-open", on);
    btn.setAttribute("aria-expanded", String(on));
  }
  btn.addEventListener("click", function () { abrir(!nav.classList.contains("is-open")); });
  // Fecha ao tocar fora, com Esc ou ao rolar a página
  document.addEventListener("click", function (e) { if (!nav.contains(e.target)) abrir(false); });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && nav.classList.contains("is-open")) { abrir(false); btn.focus(); }
  });
  function topo() { nav.classList.toggle("lvp--topo", (window.scrollY || 0) < window.innerHeight * 0.5); }
  window.addEventListener("scroll", function () { if (nav.classList.contains("is-open")) abrir(false); topo(); }, { passive: true });
  topo();
})();
