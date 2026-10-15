/* =====================================================================
   Balanças.com — "Linha Verde" — página inicial, variação 3 "Painel"
   js/home-v3.js — só index-3.html (depois de core.js, antes do CDN).
   1. Produtos (window.BC): a página inicial mostra até 8 destaques (sem o
      produto da capa), revezando as categorias; 4 vão para a vitrine do
      módulo 03 e 4 para as fichas da seção 02. A mesma regra está no painel
      (destaquesDaHome em painel/lib/catalogo.php): se mudar aqui, mude lá.
   2. Vitrine automática: troca a cada 5 s; pausa pelo botão, com o mouse
      em cima, com o foco do teclado dentro, fora da tela ou com a aba
      oculta. Com "reduzir movimento" começa parada.
   3. Links das categorias (contagem real do catálogo).
   4. Animações (LV.onMotion): abertura do hero, quadros que "acendem"
      com um corte diagonal, régua 2010 → hoje, logos dos clientes.
   0. Antes de tudo: a capa escolhida no painel (EMPRESA.capa, de
      dados/ajustes.js) troca a Prix 7E do HTML pelo produto escolhido.
   A nota do Google fica sempre fixa em "5,0" (sem contador).
   ===================================================================== */
(function () {
  "use strict";
  var LV = window.LV, BC = window.BC;
  // Com capa escolhida no painel (html.tem-capa, do <head>), a capa fica escondida (css/home-v3.css) até aqui:
  // decidido o produto, ela aparece — sem piscar a Prix 7E. Se algo falhar, o <head> mostra a capa do HTML em 8 s.
  try { if (LV && BC && BC.produtos) capaDoPainel(); }
  catch (e) { if (window.console) console.warn(e); }
  finally { document.documentElement.classList.add("hero-pronto"); }
  if (!LV || !BC || !BC.produtos) return;
  var $ = LV.$, $$ = LV.$$, esc = LV.esc;
  var reduceMQ = window.matchMedia("(prefers-reduced-motion: reduce)");
  var fineMQ = window.matchMedia("(hover: hover) and (pointer: fine)");

  /* ------------------------------------------------------------------
     0. Capa escolhida no painel (área Empresa → Capa do site)
     EMPRESA.capa = { produto, pontos: [{rotulo, valor, detalhe, x, y}],
     texto, largura, altura } (já conferido pelo balancas.js). Vale só
     se o produto está no site (BC.porId) e tem recorte; senão fica a
     Prix 7E do HTML, como sempre. As etiquetas usam as mesmas regras do
     CSS da Prix 7E (quais aparecem em cada tela, em cima ou embaixo) com
     o ponto em x/y % da foto; o lado de cada caixa sai de arrumarCapa().
     ------------------------------------------------------------------ */
  function capaDoPainel() {
    var c = BC.empresa && BC.empresa.capa, p = c && BC.porId(c.produto);
    var prod = document.querySelector(".pn-hero__prod"), hero = prod && prod.closest(".pn-hero");
    if (!p || !BC.temRecorte(p) || !prod || !hero) return;
    var ref = hero.querySelector(".pn-hero__ref"), esc = LV.esc;
    var antes = { prod: prod.innerHTML, id: prod.getAttribute("data-hero-id"), ref: ref ? ref.innerHTML : "" };
    var w = c.largura, h = c.altura;
    if (!(w > 0 && h > 0)) { w = 900; h = 900; }
    var nome = p.marca + " " + p.nome;
    hero.classList.add("pn-hero--capa");
    prod.classList.add("is-carregando");
    prod.setAttribute("data-hero-id", p.id);
    proporcao(prod, w / h);
    prod.innerHTML = '<img src="' + esc(BC.imgProduto(p, true)) + '" width="' + w + '" height="' + h + '" alt="' + esc(nome + (c.texto ? ", " + c.texto : "")) +
      '" fetchpriority="high" decoding="async">' +
      '<ul class="pn-specs" role="list" aria-label="' + esc("Destaques técnicos: " + nome) + '">' + c.pontos.map(function (pt, i) {
        return '<li class="pn-hero__call pn-spec pn-spec--' + (i + 1) + (i % 2 ? " pn-spec--dn" : "") + '"><span class="pn-spec__box">' +
          '<span class="pn-spec__k mono"><b>0' + (i + 1) + "</b> " + esc(pt.rotulo) + '</span><span class="pn-spec__v">' + esc(pt.valor) + "</span>" +
          (pt.detalhe ? '<span class="pn-spec__s">' + esc(pt.detalhe) + "</span>" : "") + "</span></li>";
      }).join("") + "</ul>";
    // Ponto de cada etiqueta (as regras do CSS ficam para a Prix 7E: o estilo no elemento vale mais)
    Array.prototype.forEach.call(prod.querySelectorAll(".pn-hero__call"), function (li, i) {
      li.style.setProperty("--x", c.pontos[i].x);
      li.style.setProperty("--y", c.pontos[i].y);
    });
    if (ref) {
      ref.innerHTML = '<a href="produtos.html#p=' + encodeURIComponent(p.id) + '"><span class="pn-hero__ref-m">' + esc(p.marca) + "</span> " + esc(p.nome) +
        (c.texto ? '<span class="pn-hero__ref-x"> — ' + esc(c.texto) + "</span>" : "") + '<svg class="i" aria-hidden="true"><use href="#i-arrow"/></svg></a>';
    }
    var img = prod.querySelector("img");
    var pronta = function () {
      prod.classList.remove("is-carregando");
      // A foto mudou depois que os pontos foram marcados? Vale a proporção real (os pontos seguem em % da foto)
      if (img.naturalWidth && img.naturalHeight && Math.abs(img.naturalWidth / img.naturalHeight - w / h) > 0.02 * (w / h)) {
        proporcao(prod, img.naturalWidth / img.naturalHeight);
      }
      arrumarCapa(hero);
    };
    img.addEventListener("load", pronta, { once: true });
    img.addEventListener("error", function () {
      // Recorte sumiu do servidor: volta a Prix 7E do HTML
      hero.classList.remove("pn-hero--capa");
      prod.classList.remove("is-carregando");
      prod.removeAttribute("style");
      prod.innerHTML = antes.prod;
      prod.setAttribute("data-hero-id", antes.id);
      if (ref) { ref.innerHTML = antes.ref; ref.classList.remove("pn-hero__ref--curta", "pn-hero__ref--dir"); }
    }, { once: true });
    if (img.complete && img.naturalWidth) pronta();
    arrumarCapa(hero);
    // A conta vale para o layout de agora: refaz quando ele assenta (fontes que chegam depois, a página inteira
    // carregada, o título ou o texto mudando de tamanho), senão uma etiqueta pode ficar de fora sem precisar
    var agenda = 0;
    var refazer = function () {
      if (agenda) return;
      var f = function () { agenda = 0; arrumarCapa(hero); };
      agenda = window.requestAnimationFrame ? window.requestAnimationFrame(f) : setTimeout(f, 16);
    };
    window.addEventListener("load", refazer);
    if (document.fonts) {
      if (document.fonts.ready) document.fonts.ready.then(refazer);
      if (document.fonts.addEventListener) document.fonts.addEventListener("loadingdone", refazer);
    }
    if ("ResizeObserver" in window) {
      var larg = 0;
      new ResizeObserver(function () {
        if (hero.offsetWidth === larg) return;   // do hero, só a largura muda o lado das etiquetas
        larg = hero.offsetWidth;
        arrumarCapa(hero);
      }).observe(hero);
      // O título e o texto de apoio são obstáculos: mudam de tamanho quando a fonte chega (a conta não mexe neles)
      var obs = new ResizeObserver(refazer);
      Array.prototype.forEach.call(hero.querySelectorAll(".pn-hero__t, .pn-hero__lead"), function (el) { obs.observe(el); });
    } else window.addEventListener("resize", function () { arrumarCapa(hero); });
  }

  // --ar = largura/altura da foto; --k = 1/--ar (o fio mede em cqw); --raiz: produtos estreitos
  // (torres) ficam com a mesma "presença" de uma balança deitada, não com a mesma largura
  function proporcao(prod, ar) {
    prod.style.setProperty("--ar", ar.toFixed(4));
    prod.style.setProperty("--k", (1 / ar).toFixed(4));
    prod.style.setProperty("--raiz", Math.min(1, Math.sqrt(ar / 1.64)).toFixed(4));
  }

  /* Lado de cada etiqueta: na mesma fila (em cima ou embaixo da foto), a da esquerda cresce para a
     esquerda e a da direita para a direita; sozinha, para o lado com mais espaço. Se a caixa sair do
     hero, cobrir o texto, a legenda ou a outra etiqueta da fila, troca de lado (ou sobe/desce um degrau);
     fica a posição que menos atrapalha. Medidas sem as transformações do GSAP (offsetLeft/Top). */
  function arrumarCapa(hero) {
    if (!hero.classList.contains("pn-hero--capa")) return;
    var W = hero.offsetWidth, cel = window.matchMedia("(max-width: 640px)").matches;
    var tab = window.matchMedia("(min-width: 861px) and (max-width: 1180px)").matches;
    var hr = hero.getBoundingClientRect();
    // Caixa de um elemento em relação ao hero, sem transformações
    var caixa = function (el) {
      var x = 0, y = 0, e = el;
      while (e && e !== hero) { x += e.offsetLeft; y += e.offsetTop; e = e.offsetParent; }
      return { l: x, t: y, r: x + el.offsetWidth, b: y + el.offsetHeight };
    };
    var rel = function (r) { return { l: r.left - hr.left, t: r.top - hr.top, r: r.right - hr.left, b: r.bottom - hr.top }; };
    var area = function (a, b) { return Math.max(0, Math.min(a.r, b.r) - Math.max(a.l, b.l)) * Math.max(0, Math.min(a.b, b.b) - Math.max(a.t, b.t)); };
    // Obstáculos: o texto do hero (linhas do título, apoio, botões, selo) e a legenda
    var obst = [];
    Array.prototype.forEach.call(hero.querySelectorAll(".pn-hero__l, .pn-hero__eyebrow > *, .pn-hero__ctas .btn"), function (el) { obst.push(caixa(el)); });
    var lead = hero.querySelector(".pn-hero__lead");
    if (lead && document.createRange) { var rg = document.createRange(); rg.selectNodeContents(lead); obst.push(rel(rg.getBoundingClientRect())); }
    var ref = hero.querySelector(".pn-hero__ref"), strip = hero.querySelector(".pn-hero__strip");
    var hdr = parseFloat(getComputedStyle(hero).paddingTop) || 0;
    var lim = { l: 8, r: W - 8, t: hdr + 4, b: strip ? strip.offsetTop - 4 : hero.offsetHeight - 4 };
    // Legenda vertical (computador) longa demais para a coluna: fica só marca e nome
    if (ref) {
      ref.classList.remove("pn-hero__ref--curta");
      var vis = ref.closest(".pn-hero__visual") || ref.parentElement, rr = rel(ref.getBoundingClientRect());
      if (!cel && window.matchMedia("(min-width: 861px)").matches && rr.b - rr.t > vis.offsetHeight - 16) ref.classList.add("pn-hero__ref--curta");
    }
    var todas = hero.querySelectorAll(".pn-hero--capa .pn-hero__call");
    Array.prototype.forEach.call(todas, function (li) { li.classList.remove("pn-spec--sem-lugar"); });
    var lis = Array.prototype.filter.call(todas, function (li) { return getComputedStyle(li).display !== "none"; });
    var embaixo = function (li) {
      var n = +(/pn-spec--(\d)/.exec(li.className) || [0, 0])[1];
      return n === 2 || n === 4 || (n === 3 && (cel || tab));
    };
    // Uma etiqueta pode trocar de lado, subir/descer um degrau e, se a fila dela estiver cheia (dois pontos no
    // mesmo x, por exemplo), passar para a outra fila (em cima ↔ embaixo). Sair do hero custa mais que tudo.
    var prod = hero.querySelector(".pn-hero__prod"), meio = prod ? caixa(prod).l + prod.offsetWidth / 2 : W / 2;
    var tentar = function (ref2) {
      lim.b = strip ? strip.offsetTop - 4 : hero.offsetHeight - 4;   // a legenda numa linha própria aumenta o hero
      var dentroLeg = ref2 ? [rel(ref2.getBoundingClientRect())] : [];
      var postas = [], ruim = 0;
      // Mede do zero (nada da conta anterior: lado, degrau, fila), para o resultado não depender da vez anterior
      lis.forEach(function (li) {
        li.classList.remove("pn-spec--sobe", "pn-spec--desce", "pn-spec--d", "pn-spec--e");
        li.style.removeProperty("--extra");
      });
      lis.forEach(function (li) { li._x = caixa(li).l; li._baixo = embaixo(li); });
      var ordem = lis.slice().sort(function (a, b) { return (a._baixo - b._baixo) || (a._x - b._x); });
      ordem.forEach(function (li) {
        var box = li.querySelector(".pn-spec__box"), fila = ordem.filter(function (o) { return o._baixo === li._baixo; });
        var prefFila = fila.length > 1 ? (fila[0] === li ? "e" : "d") : (cel ? "d" : (li._x < meio ? "d" : "e"));
        var melhor = null;
        [false, true].forEach(function (troca) {
          var baixo = troca ? !li._baixo : li._baixo, pref = troca ? (li._x < meio ? "d" : "e") : prefFila;
          li.classList.toggle("pn-spec--sobe", troca && !baixo);
          li.classList.toggle("pn-spec--desce", troca && baixo);
          ["e", "d"].forEach(function (lado) {
            [0, 1].forEach(function (degrau) {
              li.classList.toggle("pn-spec--d", lado === "d");
              li.classList.toggle("pn-spec--e", lado === "e");
              var ex = degrau ? box.offsetHeight + 6 : 0;
              li.style.setProperty("--extra", ex + "px");
              var b = caixa(box);
              var fora = Math.max(0, lim.l - b.l) + Math.max(0, b.r - lim.r) + Math.max(0, lim.t - b.t) + Math.max(0, b.b - lim.b);
              var custo = fora * 1e5 + (lado === pref ? 0 : 1) + degrau * 40 + (troca ? 300 : 0);
              obst.concat(dentroLeg).forEach(function (o) { custo += area(b, o) * 2; });
              postas.forEach(function (o) { custo += area(b, { l: o.l - 6, t: o.t - 4, r: o.r + 6, b: o.b + 4 }) * 4; });
              if (!melhor || custo < melhor.custo) melhor = { custo: custo, lado: lado, ex: ex, b: b, troca: troca, baixo: baixo };
            });
          });
        });
        li.classList.toggle("pn-spec--sobe", melhor.troca && !melhor.baixo);
        li.classList.toggle("pn-spec--desce", melhor.troca && melhor.baixo);
        li.classList.toggle("pn-spec--d", melhor.lado === "d");
        li.classList.toggle("pn-spec--e", melhor.lado === "e");
        li.style.setProperty("--extra", melhor.ex + "px");
        // Quanto a caixa ficou em cima de outra etiqueta (sem lugar nenhum livre)
        li._bate = postas.reduce(function (t, o) { return t + area(melhor.b, o); }, 0) / Math.max(1, (melhor.b.r - melhor.b.l) * (melhor.b.b - melhor.b.t));
        postas.push(melhor.b);
        // conta como problema o que atrapalha ou mudou de fila (trocar de lado ou de degrau é normal)
        ruim += melhor.custo > 200 ? melhor.custo : 0;
      });
      return ruim;
    };
    // No celular e no tablet estreito, a legenda fica num canto da faixa: se atrapalhar uma etiqueta, vai para
    // o primeiro canto livre (embaixo do outro lado, depois em cima); sem canto livre, desce para uma linha própria
    var CANTOS = ["pn-hero__ref--dir", "pn-hero__ref--topo", "pn-hero__ref--linha"];
    var usar = function (c) { CANTOS.forEach(function (k) { ref.classList.toggle(k, c.indexOf(k) >= 0); }); hero.classList.toggle("pn-hero--legenda-linha", c.indexOf("pn-hero__ref--linha") >= 0); };
    var posicionar = function () {
      if (ref) usar([]);
      var ruim = tentar(ref);
      if (ref && ruim && window.matchMedia("(max-width: 860px)").matches) {
        var melhor = { r: ruim, c: [] };
        [["pn-hero__ref--dir"], ["pn-hero__ref--topo"], ["pn-hero__ref--topo", "pn-hero__ref--dir"], ["pn-hero__ref--linha"]].some(function (c) {
          usar(c);
          var r = tentar(ref);
          if (r < melhor.r) melhor = { r: r, c: c };
          return r === 0;
        });
        usar(melhor.c);
        tentar(ref);
      }
    };
    posicionar();
    // Duas etiquetas sem lugar nenhum a não ser uma em cima da outra (pontos muito juntos, textos longos, tela
    // estreita): a de número maior fica de fora nesta largura — como a 04 da Prix 7E no computador estreito
    for (var volta = 0; volta < 2; volta++) {
      var sobra = lis.filter(function (li) { return li._bate > 0.15; }).sort(function (a, b) { return b.className.localeCompare(a.className); })[0];
      if (!sobra) break;
      sobra.classList.add("pn-spec--sem-lugar");
      lis = lis.filter(function (li) { return li !== sobra; });
      posicionar();
    }
  }

  /* ------------------------------------------------------------------
     1. Seleção dos produtos
     ------------------------------------------------------------------ */
  // Recortes claros/inox ficam ruins sobre o preto da vitrine: vão para as fichas (palco branco)
  var CLAROS = ["9094plus.webp", "hospitalar.webp", "bk200f.webp", "2098.webp", "8217.webp", "prix3plus.webp"];

  // Pega até n produtos alternando as categorias (balança, automação, informática, balança…).
  // Mapa sem protótipo: uma categoria criada no painel com o código "constructor" não quebra a página.
  function variado(lista, n) {
    var grupos = Object.create(null), ordem = [], out = [];
    lista.forEach(function (p) {
      if (!grupos[p.categoria]) { grupos[p.categoria] = []; ordem.push(p.categoria); }
      grupos[p.categoria].push(p);
    });
    while (out.length < n) {
      var antes = out.length;
      ordem.forEach(function (c) { if (out.length < n && grupos[c].length) out.push(grupos[c].shift()); });
      if (out.length === antes) break;
    }
    return out;
  }
  function fora(lista, usados) { return lista.filter(function (p) { return usados.indexOf(p) < 0; }); }

  // Produto da capa (data-hero-id): já está no topo da página, não se repete logo abaixo
  var heroEl = $(".pn-hero__prod"), capa = heroEl ? heroEl.getAttribute("data-hero-id") : "";
  var semCapa = function (p) { return p.id !== capa; };
  var escuro = function (p) { return BC.temRecorte(p) && CLAROS.indexOf(p.imagem) < 0; };
  // Os 8 destaques da página inicial: o 1º de cada categoria, depois o 2º de cada… (BC.destaques()
  // já vem com Pronta entrega primeiro e, depois, na ordem do painel)
  var home = variado(BC.destaques().filter(semCapa), 8);
  // Vitrine (fundo preto): prefere recortes escuros; se faltarem, entra foto normal num palco claro,
  // desde que as fichas continuem com 4
  var vitrine = variado(home.filter(escuro), 4);
  var resto = fora(home, vitrine);
  while (vitrine.length < 4 && resto.length > 4) vitrine.push(resto.shift());
  if (vitrine.length < 2) vitrine = vitrine.concat(variado(fora(BC.produtos.filter(semCapa).filter(escuro), home), 4 - vitrine.length));
  var grade = fora(home, vitrine).slice(0, 4);
  if (grade.length < 4) grade = grade.concat(variado(fora(fora(BC.produtos.filter(semCapa), vitrine), grade), 4 - grade.length));

  /* ------------------------------------------------------------------
     2. Vitrine automática (módulo 03)
     ------------------------------------------------------------------ */
  var DUR = 5000;
  function montaVitrine(box, lista) {
    var n = lista.length;
    var ul = $("[data-pn-slides]", box), dotsUl = $("[data-pn-dots]", box), ctrl = $("[data-pn-ctrl]", box);
    var cnt = $("[data-pn-count]", box), tot = $("[data-pn-total]", box), btn = $("[data-pn-pause]", box);
    var tile = box.closest(".pn-t") || box;

    box.setAttribute("role", "region");
    box.setAttribute("aria-roledescription", "carrossel");
    box.setAttribute("aria-label", "Vitrine de produtos");
    ul.innerHTML = lista.map(function (p, i) {
      return '<li class="pn-vit__slide' + (i ? "" : " is-on") + '" role="group" aria-roledescription="produto" aria-label="' + (i + 1) + " de " + n + '">' +
        '<figure class="pn-vit__fig' + (escuro(p) ? "" : " pn-vit__fig--palco") + '">' + LV.imgProduto(p, { alt: "", sizes: "(max-width: 640px) 86vw, (max-width: 1060px) 50vw, 460px" }) + "</figure>" +
        '<p class="pn-vit__cap"><span class="pn-vit__brand mono">' + esc(p.marca) + " · " + esc(LV.catCurto(p.categoria)) + "</span>" +
        '<a class="pn-vit__name" href="produtos.html#p=' + encodeURIComponent(p.id) + '"><span class="sr-only">' + esc(p.marca) + " </span>" + esc(p.nome) +
        '<svg class="i" aria-hidden="true"><use href="#i-arrow"/></svg></a></p></li>';
    }).join("");
    if (n < 2) return;   // um produto só: fica parado, sem controles

    dotsUl.innerHTML = lista.map(function (p, i) {
      return '<li><button class="pn-vit__dot" type="button" aria-label="Mostrar ' + esc(p.marca + " " + p.nome) + " (" + (i + 1) + " de " + n + ')"' +
        (i ? "" : ' aria-current="true"') + '><span class="pn-vit__fill"></span></button></li>';
    }).join("");
    tot.textContent = LV.pad(n);
    ctrl.hidden = false;

    var slides = $$(".pn-vit__slide", ul), dots = $$(".pn-vit__dot", dotsUl), fills = $$(".pn-vit__fill", dotsUl);
    var cur = 0, t = 0, last = 0, raf = 0;
    var st = { user: reduceMQ.matches, hover: false, focus: false, fora: true, oculta: document.hidden };
    function tocando() { return !st.user && !st.hover && !st.focus && !st.fora && !st.oculta; }

    function fill(i, r) { fills[i].style.transform = "skewX(-12deg) scaleX(" + Math.max(0, Math.min(1, r)) + ")"; }
    function sync() {
      var on = tocando();
      ul.setAttribute("aria-live", on ? "off" : "polite");
      btn.setAttribute("aria-pressed", st.user ? "true" : "false");
      // Só regrava o ícone quando muda: regravar o <use> no meio de um clique faz o Chrome perder o "click"
      var u = $("use", btn), h = st.user ? "#i-play" : "#i-pause";
      if (u.getAttribute("href") !== h) u.setAttribute("href", h);
      if (on && !raf) { last = 0; raf = requestAnimationFrame(frame); }
    }
    function ir(i) {
      i = (i + n) % n;
      if (i === cur) return;
      var a = slides[cur], b = slides[i];
      a.classList.remove("is-on"); a.classList.add("is-out");
      b.classList.remove("is-out");
      // força o ponto de partida (direita) antes de entrar
      void b.offsetWidth;
      b.classList.add("is-on");
      setTimeout(function () { if (!a.classList.contains("is-on")) a.classList.remove("is-out"); }, 850);
      dots[cur].removeAttribute("aria-current"); fill(cur, 0);
      cur = i; t = 0;
      dots[cur].setAttribute("aria-current", "true"); fill(cur, 0);
      cnt.textContent = LV.pad(cur + 1);
    }
    function frame(now) {
      raf = 0;
      if (!tocando()) return;
      if (last) t += Math.min(100, now - last);
      last = now;
      fill(cur, t / DUR);
      if (t >= DUR) ir(cur + 1);
      raf = requestAnimationFrame(frame);
    }

    dots.forEach(function (d, i) {
      d.addEventListener("click", function () { st.user = true; ir(i); fill(cur, 1); sync(); });
    });
    btn.addEventListener("click", function () { st.user = !st.user; sync(); });
    if (fineMQ.matches) {
      // Mouse em cima pausa. Enquanto pausada, confere se o mouse ainda está lá
      // (rolagem ou redimensionamento podem "sair" sem disparar mouseleave).
      var hv = 0;
      var solta = function () { clearInterval(hv); hv = 0; st.hover = false; sync(); };
      tile.addEventListener("mouseenter", function () {
        st.hover = true; sync();
        if (!hv) hv = setInterval(function () { if (!tile.matches(":hover")) solta(); }, 700);
      });
      tile.addEventListener("mouseleave", solta);
    }
    // Foco do teclado dentro da vitrine: para enquanto estiver lá
    box.addEventListener("focusin", function (e) {
      var kb = true;
      try { kb = e.target.matches(":focus-visible"); } catch (err) { /* navegador antigo */ }
      if (kb) { st.focus = true; sync(); }
    });
    box.addEventListener("focusout", function (e) { if (!box.contains(e.relatedTarget)) { st.focus = false; sync(); } });
    document.addEventListener("visibilitychange", function () { st.oculta = document.hidden; sync(); });
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (en) { st.fora = !en[en.length - 1].isIntersecting; sync(); }, { threshold: 0.25 }).observe(box);
    } else { st.fora = false; }
    // "Reduzir movimento" ligado/desligado com a página aberta
    var onRM = function () { if (reduceMQ.matches) st.user = true; sync(); };
    if (reduceMQ.addEventListener) reduceMQ.addEventListener("change", onRM);
    if (st.user) fill(0, 0);
    sync();
  }
  var vitBox = $("[data-pn-vit]");
  if (vitBox && vitrine.length) montaVitrine(vitBox, vitrine);

  /* ------------------------------------------------------------------
     Direto do catálogo (seção 02): quatro fichas que NÃO estão na vitrine do
     quadro 03 — o core.js revela os cards (data-reveal)
     ------------------------------------------------------------------ */
  var gradeEl = $("[data-pn-destaques]");
  if (gradeEl && grade.length) {
    // Ficha limpa: variante "compacta" (sem nº, tabela e "Preço: consulte"); o resumo de uma
    // frase e o selo "Consulte disponibilidade" voltam pelo css/home-v3.css
    gradeEl.innerHTML = grade.map(function (p, i) {
      return '<li class="pgrid__i" data-id="' + esc(p.id) + '">' +
        LV.cardProduto(p, { n: i + 1, variante: "compacta", linhas: 0, sizes: "(max-width: 639px) 64vw, (max-width: 900px) 46vw, (max-width: 1519px) 23vw, 340px" }) + "</li>";
    }).join("");
    $$(".pgrid__i > .spec", gradeEl).forEach(function (c) { c.setAttribute("data-reveal", ""); });
  }

  /* ------------------------------------------------------------------
     3. Categorias: um atalho por categoria com produto no site, na ordem
     do catálogo (painel → Categorias), com o nome curto e a contagem real.
     O HTML traz as 3 de sempre (para quem navega sem JavaScript); o
     primeiro <li> serve de modelo para as outras.
     ------------------------------------------------------------------ */
  var catsUl = $(".pn-cats"), catModelo = catsUl && $("li", catsUl);
  var comProdutos = (BC.categorias || []).filter(function (c) { return BC.contar({ categoria: c.id }) > 0; });
  if (catModelo && $("[data-pn-cat]", catModelo) && comProdutos.length) {
    catsUl.textContent = "";
    comProdutos.forEach(function (c) {
      var li = catModelo.cloneNode(true), a = $("[data-pn-cat]", li), qtd = BC.contar({ categoria: c.id });
      li.hidden = false;
      a.href = "produtos.html?cat=" + encodeURIComponent(c.id);
      a.setAttribute("data-pn-cat", c.id);
      // Nome acessível = texto visível ("Balanças 16 itens"); o nome completo vai no title
      a.title = c.nome;
      $("[data-pn-cat-nome]", a).textContent = LV.catCurto(c.id);
      $("[data-pn-cat-n]", a).textContent = qtd;
      var q = $(".pn-cats__q", a);
      if (q) q.lastChild.textContent = qtd === 1 ? " item" : " itens";
      catsUl.appendChild(li);
    });
    catsUl.classList.toggle("pn-cats--muitas", comProdutos.length > 3);
    // Mais de 3: no computador, no máximo 4 por linha, linhas equilibradas (5 = 3 + 2, 7 = 4 + 3, 9 = 3 + 3 + 3);
    // o 1º de cada linha e as linhas de baixo ganham classe para os fios (css/home-v3.css)
    if (comProdutos.length > 3) {
      var linhas = Math.ceil(comProdutos.length / 4), cols = Math.ceil(comProdutos.length / linhas);
      catsUl.style.setProperty("--cols", cols);
      catsUl.classList.toggle("pn-cats--menor", comProdutos.length > 5);
      Array.prototype.forEach.call(catsUl.children, function (li, i) {
        li.classList.toggle("pn-cats__ini", i % cols === 0);
        li.classList.toggle("pn-cats__baixo", i >= cols);
      });
    }
  } else if (catsUl) {
    catsUl.hidden = true;   // nenhuma categoria com produto no site
  }

  /* ------------------------------------------------------------------
     4. Animações (só com GSAP e sem "reduzir movimento")
     ------------------------------------------------------------------ */
  var doc = document.documentElement;
  // Está na tela agora (ou já ficou para trás, acima)?
  function naTela(el) { return el.getBoundingClientRect().top < window.innerHeight; }

  // GSAP atrasado: se a rede de segurança do <head> (2,6 s) já mostrou a página, o core.js
  // não esconde de novo o que já está na tela (títulos divididos, data-reveal, contadores,
  // abas de seção). Só o que está abaixo da dobra ainda anima ao rolar.
  var antes = LV.beforeMotion;
  LV.beforeMotion = function () {
    if (antes) antes();
    if (!window.LV_LIBEROU || !LV.motionOK()) return;
    $$("[data-reveal]").forEach(function (el) { if (naTela(el)) el.removeAttribute("data-reveal"); });
    $$("[data-count], [data-count-from], [data-count-dec]").forEach(function (el) {
      if (naTela(el)) { el.removeAttribute("data-count"); el.removeAttribute("data-count-from"); el.removeAttribute("data-count-dec"); }
    });
    // As abas de seção são animadas logo depois, na mesma tarefa: termina as que já estão à vista
    var abas = $$(".sec__tab").filter(naTela);
    if (abas.length && window.Promise) Promise.resolve().then(function () {
      abas.forEach(function (tab) {
        gsap.getTweensOf(tab).forEach(function (tw) { var st = tw.scrollTrigger; tw.progress(1); tw.kill(); if (st) st.kill(); });
        gsap.set(tab, { clearProps: "transform" });
      });
    });
  };

  // Estado dos módulos: fica no gancho (que roda de novo quando "reduzir movimento" é
  // desligado com a página aberta), não no elemento.
  var acesos = null, rodou = false;
  var grid = $(".pn-grid");
  if (grid) grid.addEventListener("focusin", function (e) {
    // Foco do teclado num módulo ainda apagado: acende na hora
    var t = e.target.closest(".pn-t");
    if (t && acesos && !acesos.has(t) && window.gsap) { acesos.add(t); gsap.set(t, { clearProps: "clipPath" }); }
  });

  LV.onMotion(function () {
    var viaVT = doc.classList.contains("via-vt");
    // "pronto": a página já está à vista (transição entre páginas, CDN atrasado ou o gancho
    // rodando de novo) → nada do que já aparece na tela some para animar outra vez
    var pronto = viaVT || !!window.LV_LIBEROU || rodou;
    rodou = true;
    var tl = null, tiles = $$(".pn-t");
    acesos = window.WeakSet ? new WeakSet() : { _l: [], has: function (t) { return this._l.indexOf(t) >= 0; }, add: function (t) { this._l.push(t); } };

    // Hero: a faixa desce (a partir da borda de cima do hero), as barras verdes revelam as linhas
    // e a imagem sobe. A imagem (LCP) nunca fica invisível: só se move.
    var hero = $(".pn-hero");
    if (hero) {
      tl = gsap.timeline({ defaults: { ease: "expo.out" }, delay: pronto ? 0 : 0.08 });
      var band = $(".pn-hero__band", hero), fig = $(".pn-hero__prod", hero) || $(".pn-hero__fig", hero), img = $("img", fig || hero);
      if (band) {
        // A faixa é bem mais alta que o hero (−120vh … +120vh): a origem da escala fica na borda
        // de cima do hero, senão a parte visível "pula" de 0 a 100% em poucos quadros.
        var vis = band.parentElement.getBoundingClientRect(), o = hero.getBoundingClientRect().top - vis.top - (parseFloat(getComputedStyle(band).top) || 0);
        tl.fromTo(band, { scaleY: 0, transformOrigin: "50% " + Math.max(0, Math.round(o)) + "px" }, { scaleY: 1, duration: 1.1, ease: "power3.inOut" }, 0);
      }
      $$(".pn-hero__l", hero).forEach(function (l, i) {
        LV.wipe(l, { tl: tl, at: 0.36 + i * 0.16, cor: l.classList.contains("pn-hero__l--green") ? "paper" : "green" });
      });
      if (img) tl.fromTo(img, { yPercent: 8 }, { yPercent: 0, duration: 1.3, immediateRender: true }, 0.62);
      // Etiquetas técnicas: o ponto acende, o fio cresce a partir dele e a etiqueta aparece
      var calls = $$(".pn-hero__call", hero);
      if (calls.length) {
        tl.from(calls, { autoAlpha: 0, duration: 0.35, stagger: 0.12 }, 1.1);
        calls.forEach(function (c, i) {
          var box = $(".pn-spec__box", c), up = !c.classList.contains("pn-spec--dn");
          if (box) tl.from(box, { y: up ? 10 : -10, duration: 0.6 }, 1.2 + i * 0.12);
        });
      }
      tl.from($$(".pn-hero__ref", hero), { autoAlpha: 0, y: 8, duration: 0.6 }, 1.5);
      var rest = $$("[data-intro]", hero).filter(function (el) { return !el.classList.contains("pn-hero__w"); });
      if (rest.length) tl.from(rest, { opacity: 0, y: 24, stagger: 0.08, duration: 1.05 }, 0.72);
      if (pronto) tl.progress(1);

      var stl = gsap.timeline({ scrollTrigger: { trigger: hero, start: "top top", end: "bottom top", scrub: true } });
      $$(".pn-hero__l", hero).forEach(function (l, i) { stl.to(l, { xPercent: i % 2 ? 4 : -5, ease: "none" }, 0); });
      if (fig) stl.to(fig, { yPercent: -10, ease: "none" }, 0);
      if (band) stl.to(band, { xPercent: 7, ease: "none" }, 0);
    }

    // Painel: os quadros "acendem" com um corte diagonal, na ordem 01 → 05
    if (tiles.length) {
      var FECHADO = "polygon(0% 0%, 0% 0%, -14% 100%, -14% 100%)";
      var ABERTO = "polygon(0% 0%, 114% 0%, 100% 100%, -14% 100%)";
      // Com a página já à vista, só fecha o que está abaixo da dobra
      var fechar = pronto ? tiles.filter(function (t) { return !naTela(t); }) : tiles;
      tiles.forEach(function (t) { if (fechar.indexOf(t) < 0) acesos.add(t); });
      if (fechar.length) gsap.set(fechar, { clipPath: FECHADO });
      var abre = function (lote) {
        lote = lote.filter(function (t) { return !acesos.has(t); });
        if (!lote.length) return;
        lote.forEach(function (t) { acesos.add(t); });
        var tl2 = gsap.timeline();
        tl2.to(lote, { clipPath: ABERTO, duration: 1.05, ease: "expo.inOut", stagger: 0.11, clearProps: "clipPath" }, 0);
        lote.forEach(function (t, i) {
          var at = 0.42 + i * 0.11;
          var tab = $(".pn-t__tab", t);
          if (tab) tl2.from(tab, { xPercent: -104, duration: 0.9, ease: "expo.out" }, at);
          var ticks = $(".pn-ruler__ticks", t);
          if (ticks) tl2.from(ticks, { scaleX: 0, duration: 1.2, ease: "expo.inOut" }, at + 0.1);
          var lbl = $(".pn-ruler__lbl", t);
          if (lbl) tl2.from(lbl, { autoAlpha: 0, y: 6, duration: 0.5 }, at + 0.9);
          var band2 = $(".pn-vit__band, .pn-sist__band", t);
          if (band2) tl2.from(band2, { scaleY: 0, transformOrigin: "50% 0%", duration: 1, ease: "expo.inOut" }, at);
          var logos = $$(".pn-clients .clients__i", t);
          // só opacidade: as chapas já têm transição CSS de transform (paginas.css)
          if (logos.length) tl2.from(logos, { autoAlpha: 0, duration: 0.6, stagger: 0.07, ease: "power2.out" }, at + 0.25);
        });
      };
      // "top 97%": a primeira fileira, que espia na dobra, já acende na abertura
      ScrollTrigger.batch(tiles, { start: "top 97%", end: "max", once: true, batchMax: 7, onEnter: abre });
      // Marcas de registro "fecham" no canto quando o painel entra
      var panel = $(".pn-panel");
      if (panel && !(pronto && naTela(panel))) {
        gsap.from($$(".pn-mark"), { scale: 2.2, autoAlpha: 0, duration: 0.9, ease: "expo.out", stagger: 0.06, scrollTrigger: LV.st(panel, "top 85%") });
      }
    }

    // Chegou pela transição entre páginas, mas o "pagereveal" veio depois do DOMContentLoaded:
    // no primeiro quadro a abertura já aparece terminada (o destino aparece pronto)
    if (!pronto && !window.LV_REVELOU && "onpagereveal" in window) {
      window.addEventListener("pagereveal", function (e) {
        if (!e.viewTransition) return;
        if (tl) tl.progress(1);
        tiles.forEach(function (t) { if (!acesos.has(t) && naTela(t)) { acesos.add(t); gsap.set(t, { clearProps: "clipPath" }); } });
      }, { once: true });
    }

    // Imagem do BC System: leve paralaxe dentro do módulo
    var sImg = $(".pn-sist__vis img");
    if (sImg) gsap.fromTo(sImg, { yPercent: 8 }, { yPercent: -4, ease: "none", scrollTrigger: { trigger: sImg.parentElement, start: "top bottom", end: "bottom top", scrub: true } });
  });
})();
