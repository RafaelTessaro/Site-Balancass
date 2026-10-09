/* =====================================================================
   Balanças.com — "Linha Verde" — js/preview.js
   ---------------------------------------------------------------------
   SÓ PARA A FASE DE ESCOLHA DA PÁGINA INICIAL. REMOVER NA PUBLICAÇÃO:
   apague este arquivo, a linha <script defer src="js/preview.js"> de
   todas as páginas e os arquivos index-2.html / index-3.html que não
   forem escolhidos (a variação escolhida vira o index.html).
   ---------------------------------------------------------------------
   (a) Numa página inicial (<body data-variacao="1|2|3">): guarda a
       variação em sessionStorage ("lv-home") e mostra um seletor discreto
       no canto inferior esquerdo: "Variação 1 · 2 · 3".
   (b) Em qualquer página: todo link com o atributo data-home aponta para
       a variação guardada (padrão: index.html), para o "Início" levar de
       volta à variação que está sendo avaliada.
   ===================================================================== */
(function () {
  "use strict";
  var KEY = "lv-home";
  var ARQ = { "1": "index.html", "2": "index-2.html", "3": "index-3.html" };
  var body = document.body;
  var v = body && body.getAttribute("data-variacao");

  function ler() { try { return sessionStorage.getItem(KEY); } catch (e) { return null; } }
  function gravar(x) { try { sessionStorage.setItem(KEY, x); } catch (e) { /* modo privado/arquivo local */ } }

  var atual = ARQ[v] || null;
  if (atual) gravar(atual);
  var home = atual || ler();
  if (!/^index(-[23])?\.html$/.test(home || "")) home = "index.html";

  // (b) links "Início" e logotipo
  Array.prototype.forEach.call(document.querySelectorAll("a[data-home]"), function (a) {
    var hash = (a.getAttribute("href") || "").split("#")[1];
    a.setAttribute("href", home + (hash ? "#" + hash : ""));
  });

  // (a) seletor de variação (só nas páginas iniciais)
  if (!atual) return;
  var css = document.createElement("style");
  css.textContent =
    ".lvp{position:fixed;left:max(10px,env(safe-area-inset-left));bottom:calc(10px + env(safe-area-inset-bottom));z-index:89;" +
    "display:flex;align-items:center;gap:2px;padding:0 4px 0 12px;background:rgba(10,10,10,.92);color:#F4F4F0;" +
    "border:1px solid #3A3A38;box-shadow:0 10px 30px -10px rgba(0,0,0,.6);font:500 10.5px/1 'Martian Mono',ui-monospace,monospace;" +
    "letter-spacing:.04em;text-transform:uppercase;-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px)}" +
    ".lvp__l{color:#8A8A85;margin-right:6px;white-space:nowrap}" +
    ".lvp a{position:relative;display:grid;place-items:center;width:40px;height:44px;color:#F4F4F0;text-decoration:none;font-size:13px}" +
    ".lvp a::before{content:'';position:absolute;inset:9px 5px;transform:skewX(-12deg);border:1px solid #494949;transition:background .25s,border-color .25s}" +
    ".lvp a:hover::before{border-color:#019F42}" +
    ".lvp a[aria-current]{color:#0A0A0A}" +
    ".lvp a[aria-current]::before{background:#019F42;border-color:#019F42}" +
    ".lvp a span{position:relative}" +
    ".lvp a:focus-visible{outline:2px solid #C6FF3D;outline-offset:-4px}" +
    "@media (max-width:340px){.lvp__l b{display:none}.lvp a{width:36px}}";
  document.head.appendChild(css);

  var nav = document.createElement("nav");
  nav.className = "lvp";
  nav.setAttribute("data-preview", "");
  nav.setAttribute("aria-label", "Variações da página inicial (pré-visualização)");
  nav.innerHTML = '<span class="lvp__l" aria-hidden="true">Var<b>iação</b></span>' +
    ["1", "2", "3"].map(function (n) {
      var cur = n === v ? ' aria-current="page"' : "";
      return '<a href="' + ARQ[n] + '"' + cur + ' aria-label="Variação ' + n + '"><span>' + n + "</span></a>";
    }).join("");
  body.appendChild(nav);
})();
