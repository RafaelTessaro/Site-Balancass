/* =====================================================================
   Balanças.com — "Linha Verde" — núcleo (js/core.js)
   Incluído em TODAS as páginas, depois de empresa.js, ajustes.js,
   clientes.js, produtos.js e balancas.js (todos com "defer").
   - Preenche os dados da empresa no HTML (data-bc, data-bc-href, data-wa,
     data-bc-list, data-bc-se) e o status "aberto agora". Os dados já vêm
     com os ajustes do painel (área Empresa) aplicados pelo balancas.js.
   - Faixa de aviso no topo (aviso do painel) e dados estruturados
     (JSON-LD) atualizados com telefone, e-mail, endereço e horários.
   - Ficha de produto LV.cardProduto(p) e listas automáticas (data-produtos).
   - Cabeçalho, menu mobile, âncoras, faixas (ticker), acordeão, formulário
     de WhatsApp, abas de kits, botões flutuantes.
   - Animação: GSAP + ScrollTrigger (+ SplitText/DrawSVG se houver) e Lenis,
     com o sistema de revelação data-reveal. Respeita "reduzir movimento".
   - Contagem anônima para o painel ("Mais procurados", seção 12): páginas,
     produtos abertos e cliques no WhatsApp/Ligar, sem cookies.
   API pública em window.LV — documentada em COMPONENTES.md.
   ===================================================================== */
(function () {
  "use strict";

  var BC = window.BC;
  var E = (BC && BC.empresa) || {};
  var doc = document.documentElement;
  var LV = (window.LV = window.LV || {});
  var hooks = [];

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  LV.$ = $; LV.$$ = $$;

  var reduceMQ = window.matchMedia("(prefers-reduced-motion: reduce)");
  var fineMQ = window.matchMedia("(hover: hover) and (pointer: fine)");
  function hasGSAP() { return !!(window.gsap && window.ScrollTrigger); }
  LV.motionOK = function () { return hasGSAP() && !reduceMQ.matches; };
  LV.reduzido = function () { return reduceMQ.matches; };
  LV.onMotion = function (fn) { hooks.push(fn); };          // ganchos das páginas (rodam dentro do matchMedia)
  LV.hdr = function () { var h = $("[data-hdr]"); return h ? h.offsetHeight : 72; };
  LV.esc = function (s) { return BC ? BC.escape(s) : String(s == null ? "" : s).replace(/[&<>"']/g, function (c) { return "&#" + c.charCodeAt(0) + ";"; }); };
  LV.pad = function (n) { return (n < 10 ? "0" : "") + n; };
  LV.pagina = (decodeURIComponent(location.pathname.split("/").pop() || "") || "index.html").toLowerCase();
  LV.refresh = function () { if (window.ScrollTrigger) ScrollTrigger.refresh(); };

  /* Página "já à vista" quando o GSAP chega:
     - LV.tarde():  a rede de segurança do <head> (2,6 s) já mostrou tudo ("lv-late");
     - LV.pronto(): o topo deve aparecer TERMINADO (chegou pela transição entre páginas — "via-vt" —
                    ou chegou tarde): as timelines de abertura vão direto ao fim (tl.progress(1));
     - LV.jaVisivel(el): o elemento está na tela e a pessoa JÁ o viu (um quadro foi pintado antes do
                    GSAP, chegada por âncora, recarregar no meio da página, transição ou chegada tarde)
                    → não esconder para animar de novo. Vale para data-reveal, contadores, abas .sec__tab,
                    SAT e carimbo; use também nos ganchos das páginas (LV.onMotion). */
  var pintou = false, manter = false;
  requestAnimationFrame(function () { requestAnimationFrame(function () { pintou = true; }); });
  LV.tarde = function () { return doc.classList.contains("lv-late"); };
  LV.pronto = function () { return doc.classList.contains("via-vt") || LV.tarde(); };
  LV.naTela = function (el) {
    if (!el) return false;
    var r = el.getBoundingClientRect();
    return !!(r.width || r.height) && r.bottom > 0 && r.top < (window.innerHeight || doc.clientHeight);
  };
  LV.jaVisivel = function (el) { return manter && LV.naTela(el); };

  /* ------------------------------------------------------------------
     1. Dados da empresa → HTML (o HTML já traz os mesmos dados p/ SEO)
     ------------------------------------------------------------------ */
  var NEW_TAB = " (abre em nova aba)";
  function newTabHint(a) {
    if (a._nt) return; a._nt = true;
    var al = a.getAttribute("aria-label");
    if (al) { if (al.indexOf(NEW_TAB.trim()) === -1) a.setAttribute("aria-label", al + NEW_TAB); return; }
    var sr = document.createElement("span"); sr.className = "sr-only"; sr.textContent = NEW_TAB;
    a.appendChild(sr);
  }
  LV.newTabHint = newTabHint;

  function siteBCFichas() {
    var p = (E.produtosProprios || []).filter(function (x) { return /fichas/i.test(x.nome); })[0];
    return (p && p.site) || "http://bcfichas.com.br";
  }

  // Valores de data-bc="chave"
  function valor(k) {
    var end = E.endereco || {};
    switch (k) {
      case "telefone": return E.telefone;
      case "whatsapp": return mesmoNumero() ? E.telefone : foneWhats();
      case "whatsapp-mesmo": return mesmoNumero() ? "O mesmo número" : foneWhats();
      case "email": return E.email;
      case "endereco": return BC.enderecoTexto();
      case "rua": return end.rua;
      case "rua-numero": var n = /,\s*(\d{1,6})\b/.exec(end.rua || ""); return n ? n[1] : null;   // "Rua 13, 650" → "650"
      case "bairro": return end.bairro;
      case "cidade": return end.cidade && end.uf ? end.cidade + "/" + end.uf : end.cidade;
      case "cidade-nome": return end.cidade;
      case "bairro-frase": return end.bairro;
      case "cidade-uf": return end.cidade && end.uf ? end.cidade + "-" + end.uf : end.cidade;
      case "cep": return end.cep;
      case "referencia": return BC.referenciaEndereco ? BC.referenciaEndereco() : end.referencia;
      case "dias-abertos": return diasAbertos();
      case "ipem": return E.ipem;
      case "cnpj": return E.cnpj;
      case "razao": return E.razaoSocial;
      case "frase": return E.frase;
      case "desde": return E.desde;
      case "experiencia": return E.anosExperiencia;
      case "ano": return new Date().getFullYear();
      case "anos": return BC.anosDesde();
      case "google-nota": return E.google && E.google.nota;
      case "google-qtd": return E.google && E.google.avaliacoes;
      case "total-produtos": return BC.produtos.length;
      case "total-categorias": return (BC.categorias || []).length;
      case "total-marcas": return (E.marcas || []).length;
    }
    return null;
  }
  // Destinos de data-bc-href="chave"
  function destino(k) {
    switch (k) {
      case "whatsapp": return BC.whatsLink();
      case "tel": return BC.telLink();
      case "email": return BC.emailLink("");
      case "mapa": return E.mapa;
      case "google": return E.google && E.google.link;
      case "facebook": return E.redes && E.redes.facebook;
      case "instagram": return E.redes && E.redes.instagram;
      case "bcfichas": return siteBCFichas();
    }
    return null;
  }

  function nobr(el) {
    // "CEP 13500-120", "Rio Claro/SP" e "IPEM-SP" nunca quebram (nem no espaço, nem no hífen)
    el.innerHTML = LV.esc(el.textContent)
      .replace(/CEP\s*([\d.\-]+)/, '<span class="nobr">CEP $1</span>')
      .replace(/Rio Claro([\/\-]SP)?/, '<span class="nobr">$&</span>')
      .replace(/IPEM-SP/g, '<span class="nobr">IPEM-SP</span>');
  }

  /* Faixa de clientes: o CSS diz quantas colunas cabem (--cols-max, muda com a largura) e aqui
     equilibramos as fileiras para a quantidade de logos que o painel tiver
     (5 → 5; 7 → 4 + 3; 8 → 4 + 4; 10 → 5 + 5; 12 → 6 + 6; no celular, 2 ou 3 por fileira). */
  var ro = null;
  function calculaColunas(ul) {
    var n = ul.children.length;
    if (!n) return;
    var max = parseInt(getComputedStyle(ul).getPropertyValue("--cols-max"), 10) || 6;
    var cols = Math.ceil(n / Math.ceil(n / max));
    // Poucos logos: as chapas não crescem demais (mínimo de 2/3 das colunas que cabem)
    cols = Math.min(max, Math.max(cols, Math.ceil(max * 2 / 3)));
    if (ul.style.getPropertyValue("--cols") !== String(cols)) ul.style.setProperty("--cols", cols);
  }
  function colunasClientes(ul) {
    calculaColunas(ul);
    if (ul.lvCols) return;
    ul.lvCols = true;
    if (window.ResizeObserver) {
      ro = ro || new ResizeObserver(function (lista) { lista.forEach(function (r) { calculaColunas(r.target); }); });
      ro.observe(ul);
    } else {
      window.addEventListener("resize", function () { calculaColunas(ul); });
    }
  }

  var LISTAS = {
    horarios: function (ul) {
      if (!E.horarios || !E.horarios.length) return;
      ul.innerHTML = E.horarios.map(function (h) {
        return "<li><span>" + LV.esc(h.dias) + "</span><span>" + LV.esc(h.horas) + "</span></li>";
      }).join("");
    },
    marcas: function (ul) {
      if (!E.marcas || !E.marcas.length) return;
      ul.innerHTML = E.marcas.map(function (m) { return "<li>" + LV.esc(m) + "</li>"; }).join("");
    },
    clientes: function (ul) {
      // E.clientes = dados/clientes.js (painel, área Clientes) ou, sem ele, a lista de empresa.js
      var lista = Array.isArray(E.clientes) ? E.clientes : null;
      if (!lista) return;
      var bloco = ul.closest(".clients, .pn-prova__c") || ul;
      if (!lista.length) { ul.innerHTML = ""; bloco.hidden = true; return; }   // o painel tirou todos
      bloco.hidden = false;
      // O HTML já traz os MESMOS logos na mesma ordem (com width/height)? Então fica como está.
      var noHtml = $$("img", ul).map(function (im) { return (im.getAttribute("src") || "").split("/").pop() + "|" + im.alt; });
      var nosDados = lista.map(function (c) { return c.logo + "|" + c.nome; });
      if (noHtml.join("\n") !== nosDados.join("\n")) {
        ul.innerHTML = lista.map(function (c) {
          // qualidade.webp: a versão recortada (sem a margem branca) mora em img/clientes/
          var src = c.logo === "qualidade.webp" ? "img/clientes/qualidade.webp" : BC.img("clientes/" + encodeURIComponent(c.logo));
          var sq = /^(brasil-frios|camargo)\./.test(c.logo) ? " clients__i--sq" : "";
          return '<li class="clients__i' + sq + '"><img src="' + LV.esc(src) + '" alt="' + LV.esc(c.nome) + '" loading="lazy" decoding="async"></li>';
        }).join("");
        // Logo novo (do painel): os quase quadrados ganham mais altura na chapa, como os do HTML
        $$("img", ul).forEach(function (im) {
          var li = im.parentNode;
          function mede() { if (im.naturalWidth && im.naturalHeight && im.naturalWidth / im.naturalHeight < 1.35) li.classList.add("clients__i--sq"); }
          if (im.complete) mede(); else im.addEventListener("load", mede, { once: true });
        });
      }
      colunasClientes(ul);
    },
    depoimentos: function (ul) {
      var D = E.depoimentos;
      if (!D || !D.length) return;
      var ini = parseInt(ul.getAttribute("data-inicio"), 10) || 0;
      var lim = parseInt(ul.getAttribute("data-limite"), 10) || D.length;
      var rv = ul.hasAttribute("data-sem-reveal") ? "" : " data-reveal";
      // Poucos depoimentos (painel): a lista que começa no 4º ("data-inicio") dá a volta e
      // recomeça do 1º, em vez de ficar vazia; com até "data-limite" itens, mostra todos.
      var qtd = Math.min(lim, D.length), de = D.length > lim ? ini % D.length : 0, sel = [];
      for (var i = 0; i < qtd; i++) sel.push(D[(de + i) % D.length]);
      ul.innerHTML = sel.map(function (d) {
        return '<li class="quote"' + rv + '><blockquote><p>' + LV.esc(d.texto) + "</p></blockquote>" +
          '<p class="quote__by"><b>' + LV.esc(d.nome) + "</b>" + (d.empresa ? " · " + LV.esc(d.empresa) : "") + "</p></li>";
      }).join("");
    },
    equipe: function (ul) {
      if (!E.equipe || !E.equipe.length) return;
      var rv = ul.hasAttribute("data-sem-reveal") ? "" : " data-reveal";
      ul.innerHTML = E.equipe.map(function (p) {
        var ini = String(p.nome || "").split(/\s+/).filter(Boolean);
        ini = (ini[0] || "").charAt(0) + (ini.length > 1 ? ini[ini.length - 1].charAt(0) : "");
        return '<li class="member"' + rv + '><span class="member__mono" aria-hidden="true">' + LV.esc(ini.toUpperCase()) + "</span>" +
          '<p class="member__name">' + LV.esc(p.nome) + '</p><p class="member__role mono">' + LV.esc(p.cargo) + "</p>" +
          '<p class="member__txt">' + LV.esc(p.texto) + "</p></li>";
      }).join("");
    }
  };

  // WhatsApp e telefone são o mesmo número? (o painel pode separar os dois)
  function mesmoNumero() {
    var w = String(E.whatsapp || "").replace(/\D/g, "");
    return !w || w === String(E.telefoneLink || "").replace(/\D/g, "");
  }
  // "5519999999999" → "(19) 99999-9999"
  function foneWhats() {
    var m = /^55(\d{2})(\d{4,5})(\d{4})$/.exec(String(E.whatsapp || ""));
    return m ? "(" + m[1] + ") " + m[2] + "-" + m[3] : E.telefone;
  }

  // data-bc-se="condição": o trecho só aparece quando a condição vale (ex.: ", todas com 5 estrelas")
  function condicao(k) {
    switch (k) {
      case "google-5": return !!(E.google && E.google.nota === "5,0");
      case "referencia": return !!valor("referencia");
      case "mesmo-numero": return mesmoNumero();
      case "outro-numero": return !mesmoNumero();
    }
    return true;
  }

  // "de segunda a sábado": os dias em que a loja abre, pela grade de horários (seg…sáb, dom)
  var DIAS_ORDEM = [1, 2, 3, 4, 5, 6, 0];
  function diasAbertos() {
    var g = GRADE, nomes = ["domingo", "segunda", "terça", "quarta", "quinta", "sexta", "sábado"];
    var abre = DIAS_ORDEM.filter(function (d) { return (g[d] || []).length > 0; });
    if (!abre.length) return null;
    if (abre.length === 7) return "todos os dias";
    var pos = abre.map(function (d) { return DIAS_ORDEM.indexOf(d); });
    var seguidos = pos[pos.length - 1] - pos[0] === pos.length - 1;
    if (seguidos && abre.length > 2) return "de " + nomes[abre[0]] + " a " + nomes[abre[abre.length - 1]];
    var n = abre.map(function (d) { return nomes[d]; });
    return n.length === 1 ? "só " + n[0] : n.slice(0, -1).join(", ") + " e " + n[n.length - 1];
  }

  // Primeira letra na mesma caixa do texto que já está no HTML ("Entre as…" / "entre as…")
  function mesmaCaixa(v, antes) {
    var c = (antes || "").trim().charAt(0);
    if (!c || c.toLowerCase() === c.toUpperCase()) return v;
    return (c === c.toUpperCase() ? v.charAt(0).toUpperCase() : v.charAt(0).toLowerCase()) + v.slice(1);
  }

  function fill() {
    $$("[data-wa]").forEach(function (a) {
      a.href = BC.whatsLink(a.getAttribute("data-wa") || undefined);
      a.target = "_blank"; a.rel = "noopener";
    });
    $$("[data-bc-href]").forEach(function (a) {
      var k = a.getAttribute("data-bc-href"), url = destino(k), rede = k === "facebook" || k === "instagram";
      var li = rede ? a.closest("li") || a : null;
      if (url) { a.href = url; if (li) li.hidden = false; }   // rede que o painel preencheu aparece
      else if (li) li.hidden = true;                          // rede vazia: o link some
    });
    $$("[data-bc]").forEach(function (el) {
      var k = el.getAttribute("data-bc");
      if (k === "status") return;   // ver statusAgora()
      var v = valor(k);
      if (k === "referencia" && v === "") {
        // O painel apagou a referência: some o texto (e a quebra de linha antes dele)
        el.hidden = true;
        var br = el.previousElementSibling;
        if (br && br.tagName === "BR") br.hidden = true;
        return;
      }
      if (v == null || v === "") return;
      v = String(v);
      if (k === "referencia" || k === "dias-abertos") v = mesmaCaixa(v, el.textContent);
      if (k === "bairro-frase") {
        // "no bairro da Boa Morte": o HTML fica enquanto o bairro for o mesmo; outro bairro → "no bairro X"
        if (el.textContent.trim().slice(-v.length) === v) return;
        v = "no bairro " + v;
      }
      el.textContent = v;
      if (k === "endereco" || k === "ipem") nobr(el);
    });
    $$("[data-bc-se]").forEach(function (el) { el.hidden = !condicao(el.getAttribute("data-bc-se")); });
    $$("[data-bc-list]").forEach(function (ul) {
      var fn = LISTAS[ul.getAttribute("data-bc-list")];
      if (fn) fn(ul);
    });
    // Texto que estica no hover (.st): a largura reservada (data-t) acompanha o texto novo
    $$(".st[data-t]").forEach(function (st) {
      if ((st.hasAttribute("data-bc") || $("[data-bc]", st)) && st.getAttribute("data-t") !== st.textContent) st.setAttribute("data-t", st.textContent);
    });
    // Aba do navegador: "Contato: WhatsApp (19) 3023-9050 | …" com o número atual do WhatsApp
    // (o mesmo telefone, ou o celular, quando o painel cadastrou um WhatsApp diferente)
    var RE_FONE = /\(\d{2}\)\s?\d{4,5}-\d{4}/, fone = valor("whatsapp");
    if (fone) document.title = document.title.replace(RE_FONE, fone);
    // Resumo da página (description, og, twitter) com o telefone e a nota do Google atuais.
    // A busca do Google lê a página já montada; as prévias de link (WhatsApp, Facebook) leem o HTML como veio.
    var g = E.google || {};
    $$('meta[name="description"], meta[property="og:description"], meta[name="twitter:description"]').forEach(function (m) {
      var c = m.getAttribute("content") || "", n = c;
      if (E.telefone) n = n.replace(RE_FONE, E.telefone);
      if (g.nota && g.avaliacoes != null) n = n.replace(/nota \d,\d no Google \(\d+ avaliaç(ões|ão)\)/, "nota " + g.nota + " no Google (" + g.avaliacoes + (+g.avaliacoes === 1 ? " avaliação" : " avaliações") + ")");
      if (n !== c) m.setAttribute("content", n);
    });
    // Leitor de tela: avisa que o link abre em outra aba (por último, para não ser apagado pelos textos acima)
    $$('a[target="_blank"]').forEach(newTabHint);
  }

  /* ------------------------------------------------------------------
     2. "Aberto agora" — horário de Rio Claro (America/Sao_Paulo)
     Grade em minutos desde 0h (0 = domingo … 6 = sábado). Vale a grade
     do painel (área Empresa → Horário: EMPRESA.grade, vinda do ajustes.js)
     quando ela é válida; senão, esta, que é a mesma de EMPRESA.horarios.
     ------------------------------------------------------------------ */
  var GRADE_PADRAO = {
    0: [],                                   // domingo
    1: [[480, 660], [780, 1080]], 2: [[480, 660], [780, 1080]], 3: [[480, 660], [780, 1080]],
    4: [[480, 660], [780, 1080]], 5: [[480, 660], [780, 1080]],
    6: [[480, 720]]                          // sábado
  };
  var GRADE = (BC && BC.gradeValida && BC.gradeValida(E.grade)) || GRADE_PADRAO;
  LV.GRADE_HORARIOS = GRADE;
  var DIAS = ["domingo", "segunda", "terça", "quarta", "quinta", "sexta", "sábado"];

  /* Feriados (a loja fecha: "Domingo e feriados — Fechado"). O status "Aberto agora" do site
     consulta esta lista, no calendário de Rio Claro (America/Sao_Paulo).

     NACIONAIS (Leis 662/49, 6.802/80, 10.607/02 e 14.759/23) — já incluídos, todo ano:
       01/01 Confraternização · 21/04 Tiradentes · 01/05 Trabalho · 07/09 Independência ·
       12/10 N. Sra. Aparecida · 02/11 Finados · 15/11 República · 20/11 Consciência Negra ·
       25/12 Natal · Sexta-feira Santa (móvel, 2 dias antes da Páscoa).
     ESTADUAL (SP): 09/07 Revolução Constitucionalista — já incluído.

     >>> FERIADOS MUNICIPAIS DE RIO CLARO, Carnaval, Corpus Christi e dias avulsos
         (ponte, recesso, inventário): agora o DONO CADASTRA NO PAINEL, área Empresa →
         Feriados e dias fechados (gravado em compartilhado/dados/ajustes.js). <<<
     O painel manda EMPRESA.feriados = { municipais: [{data:"MM-DD"}], moveis: [60, -47…],
     datas: [{data:"AAAA-MM-DD"}] }, que entra abaixo em MUNICIPAIS, MOVEIS e DATAS
     (móveis = dias contados a partir da Páscoa: −48 segunda e −47 terça de Carnaval,
     −46 Quarta-feira de Cinzas, 60 Corpus Christi). Não precisa mexer aqui.
     Para conferir: LV.statusAgora() e LV.FERIADOS no console do navegador. */
  var FERIADOS = {
    FIXOS: ["01-01", "04-21", "05-01", "07-09", "09-07", "10-12", "11-02", "11-15", "11-20", "12-25"],
    MUNICIPAIS: [],          // feriados municipais ("MM-DD") — vêm do painel
    MOVEIS: [-2],            // −2 = Sexta-feira Santa (nacional); os do painel entram depois
    DATAS: []                // dias avulsos "AAAA-MM-DD" — vêm do painel
  };
  (function (F) {
    if (!F || typeof F !== "object") return;
    function junta(lista, alvo, re) {
      if (!Array.isArray(lista)) return;
      lista.forEach(function (f) {
        var d = f && typeof f === "object" ? f.data : f;
        if (typeof d === "string" && re.test(d) && alvo.indexOf(d) === -1) alvo.push(d);
      });
    }
    try {
      junta(F.municipais, FERIADOS.MUNICIPAIS, /^\d{2}-\d{2}$/);
      junta(F.datas, FERIADOS.DATAS, /^\d{4}-\d{2}-\d{2}$/);
      if (Array.isArray(F.moveis)) F.moveis.forEach(function (n) {
        if ([-48, -47, -46, 60].indexOf(n) !== -1 && FERIADOS.MOVEIS.indexOf(n) === -1) FERIADOS.MOVEIS.push(n);
      });
    } catch (e) { /* feriados do painel com problema: ficam só os nacionais/estaduais */ }
  })(E.feriados);
  LV.FERIADOS = FERIADOS;
  function pascoa(y) {   // algoritmo de Meeus/Jones/Butcher → Date (UTC)
    var a = y % 19, b = Math.floor(y / 100), c = y % 100, d = Math.floor(b / 4), e = b % 4,
      f = Math.floor((b + 8) / 25), g = Math.floor((b - f + 1) / 3), h = (19 * a + b - d - g + 15) % 30,
      i = Math.floor(c / 4), k = c % 4, l = (32 + 2 * e + 2 * i - h - k) % 7, m = Math.floor((a + 11 * h + 22 * l) / 451),
      mes = Math.floor((h + l - 7 * m + 114) / 31), dia = ((h + l - 7 * m + 114) % 31) + 1;
    return Date.UTC(y, mes - 1, dia);
  }
  var DIA_MS = 864e5;
  function ehFeriado(t) {   // t = meia-noite UTC do dia (no calendário de Rio Claro)
    var dt = new Date(t), y = dt.getUTCFullYear();
    var md = LV.pad(dt.getUTCMonth() + 1) + "-" + LV.pad(dt.getUTCDate());
    if (FERIADOS.FIXOS.indexOf(md) !== -1 || (FERIADOS.MUNICIPAIS || []).indexOf(md) !== -1 ||
      (FERIADOS.DATAS || []).indexOf(y + "-" + md) !== -1) return true;
    var p = pascoa(y);
    return FERIADOS.MOVEIS.some(function (n) { return p + n * DIA_MS === t; });
  }
  LV.ehFeriado = function (d) { d = d || new Date(); return ehFeriado(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate())); };
  function agora() {
    try {
      var parts = new Intl.DateTimeFormat("en-US", { timeZone: "America/Sao_Paulo", year: "numeric", month: "numeric", day: "numeric", weekday: "short", hour: "numeric", minute: "numeric", hourCycle: "h23" }).formatToParts(new Date());
      var o = {}; parts.forEach(function (p) { o[p.type] = p.value; });
      var dia = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].indexOf(o.weekday);
      var h = parseInt(o.hour, 10) % 24, m = parseInt(o.minute, 10);
      var t = Date.UTC(+o.year, +o.month - 1, +o.day);
      if (dia >= 0 && !isNaN(h) && !isNaN(t)) return { dia: dia, min: h * 60 + m, t: t };
    } catch (e) { /* navegador antigo */ }
    var d = new Date(); return { dia: d.getDay(), min: d.getHours() * 60 + d.getMinutes(), t: Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()) };
  }
  function hora(min) { var h = Math.floor(min / 60), m = min % 60; return h + "h" + (m ? LV.pad(m) : ""); }
  function grade(dia, t) { return ehFeriado(t) ? [] : (GRADE[dia] || []); }
  LV.statusAgora = function () {
    var a = agora(), feriado = ehFeriado(a.t), hoje = grade(a.dia, a.t);
    for (var i = 0; i < hoje.length; i++) {
      if (a.min >= hoje[i][0] && a.min < hoje[i][1]) {
        return { aberto: true, curto: "Aberto agora", texto: "Aberto agora · fecha às " + hora(hoje[i][1]) };
      }
    }
    var fechado = feriado ? "Fechado · feriado" : "Fechado agora";
    for (var d = 0; d < 15; d++) {
      var dia = (a.dia + d) % 7, t = a.t + d * DIA_MS, g = grade(dia, t);
      for (var j = 0; j < g.length; j++) {
        if (d === 0 && g[j][0] <= a.min) continue;
        var quando = d === 0 ? "hoje" : d === 1 ? "amanhã" : d < 7 ? DIAS[dia] : "dia " + new Date(t).getUTCDate();
        return { aberto: false, curto: fechado, texto: fechado + " · abre " + quando + " às " + hora(g[j][0]) };
      }
    }
    return { aberto: false, curto: fechado, texto: fechado };
  };
  function status() {
    var els = $$('[data-bc="status"]'); if (!els.length) return;
    var s = LV.statusAgora();
    els.forEach(function (el) {
      el.textContent = el.hasAttribute("data-curto") ? s.curto : s.texto;
      el.classList.toggle("is-aberto", s.aberto); el.classList.toggle("is-fechado", !s.aberto);
      el.hidden = false;
    });
  }

  /* ------------------------------------------------------------------
     2b. Faixa de aviso (painel → área Empresa → Aviso no site)
     Aparece no topo de todas as páginas enquanto o aviso está ligado e
     hoje (America/Sao_Paulo) está entre "inicio" e "fim" (cada um opcional).
     Texto puro (textContent). Fechou: some até fechar o navegador
     (sessionStorage, por texto: um aviso novo volta a aparecer).
     ------------------------------------------------------------------ */
  function hojeISO() {
    var d = new Date(agora().t);
    return d.getUTCFullYear() + "-" + LV.pad(d.getUTCMonth() + 1) + "-" + LV.pad(d.getUTCDate());
  }
  LV.avisoAtivo = function () {
    var a = E.aviso;
    if (!a || a.ativo !== true || typeof a.texto !== "string" || !a.texto.trim()) return null;
    var hoje = hojeISO(), re = /^\d{4}-\d{2}-\d{2}$/;
    if (re.test(a.inicio || "") && hoje < a.inicio) return null;
    if (re.test(a.fim || "") && hoje > a.fim) return null;
    return a;
  };
  var AVISO_CHAVE = "lv-aviso-fechado";
  function aviso() {
    var a = LV.avisoAtivo(), main = $("main");
    if (!a || !main || $(".lv-aviso")) return;
    var texto = a.texto.trim();
    try { if (sessionStorage.getItem(AVISO_CHAVE) === texto) return; } catch (e) { /* sem armazenamento: mostra */ }
    var alerta = a.tom === "alerta";
    var box = document.createElement("div");
    box.className = "lv-aviso" + (alerta ? " lv-aviso--alerta" : "");
    box.setAttribute("role", "region");
    box.setAttribute("aria-label", "Aviso");
    box.innerHTML = '<div class="lv-aviso__in">' +
      '<p class="lv-aviso__txt"><span class="lv-aviso__tag" aria-hidden="true">' + (alerta ? "Atenção" : "Aviso") + "</span>" +
        '<span class="lv-aviso__msg"></span></p>' +
      '<button class="lv-aviso__x" type="button" aria-label="Fechar o aviso">' +
        '<svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="square" d="M6 6l12 12M18 6L6 18"/></svg>' +
      "</button></div>";
    $(".lv-aviso__msg", box).textContent = texto;
    $(".lv-aviso__x", box).addEventListener("click", function () {
      try { sessionStorage.setItem(AVISO_CHAVE, texto); } catch (e) { /* ok: fecha só nesta página */ }
      var foco = box.contains(document.activeElement);
      box.parentNode.removeChild(box);
      if (foco) {   // o foco não se perde: vai para o começo do conteúdo
        if (!main.hasAttribute("tabindex")) main.setAttribute("tabindex", "-1");
        main.focus({ preventScroll: true });
      }
      LV.refresh();
    });
    main.insertBefore(box, main.firstChild);
    LV.refresh();   // GSAP já ativo (raro: o aviso entra antes dele): recalcula as posições
  }

  /* ------------------------------------------------------------------
     2c. Dados estruturados (JSON-LD do <head>) com os dados atuais:
     telefone, e-mail, endereço, mapa, horários, redes e equipe. Só
     reescreve o bloco quando algo mudou (sem ajustes, fica como veio).
     ------------------------------------------------------------------ */
  var DIAS_EN = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
  function horaLD(min) { min = Math.min(min, 1439); return LV.pad(Math.floor(min / 60)) + ":" + LV.pad(min % 60); }
  function horariosLD() {
    // Dias com as mesmas faixas viram um grupo (seg…sex juntos), na ordem seg…sáb, dom
    var grupos = [];
    DIAS_ORDEM.forEach(function (d) {
      var g = GRADE[d] || [];
      if (!g.length) return;
      var chave = JSON.stringify(g), grupo = grupos.filter(function (x) { return x.chave === chave; })[0];
      if (grupo) grupo.dias.push(DIAS_EN[d]); else grupos.push({ chave: chave, faixas: g, dias: [DIAS_EN[d]] });
    });
    var out = [];
    grupos.forEach(function (gr) {
      gr.faixas.forEach(function (f) {
        out.push({ "@type": "OpeningHoursSpecification", dayOfWeek: gr.dias.length > 1 ? gr.dias.slice() : gr.dias[0], opens: horaLD(f[0]), closes: horaLD(f[1]) });
      });
    });
    return out;
  }
  function telLD() {   // "+551930239050" → "+55-19-3023-9050"
    var m = /^\+55(\d{2})(\d{4,5})(\d{4})$/.exec(E.telefoneLink || "");
    return m ? "+55-" + m[1] + "-" + m[2] + "-" + m[3] : null;
  }
  function atualizaLD(o) {
    var mudou = false;
    function poe(alvo, k, v) {
      if (v == null || v === "" || JSON.stringify(alvo[k]) === JSON.stringify(v)) return;
      alvo[k] = v; mudou = true;
    }
    var tel = telLD(), end = E.endereco || {};
    (function visita(x) {
      if (Array.isArray(x)) { x.forEach(visita); return; }
      if (!x || typeof x !== "object") return;
      if (typeof x.telephone === "string" && tel) poe(x, "telephone", tel);
      if (typeof x.email === "string" && E.email) poe(x, "email", E.email);
      if (x.address && typeof x.address === "object" && !Array.isArray(x.address)) {
        var a = x.address;
        poe(a, "streetAddress", end.rua); poe(a, "addressLocality", end.cidade);
        poe(a, "addressRegion", end.uf); poe(a, "postalCode", end.cep);
        // "Endereço: Rua 13, 650, entre as Avenidas 9 e 11 – Boa Morte, Rio Claro-SP." no fim da descrição
        if (typeof x.description === "string" && /Endereço: /.test(x.description) && end.rua && end.bairro && end.cidade && end.uf) {
          var ref = valor("referencia");
          poe(x, "description", x.description.replace(/Endereço: [^]*$/, "Endereço: " + end.rua +
            (ref ? ", " + mesmaCaixa(ref, "e") : "") + " – " + end.bairro + ", " + end.cidade + "-" + end.uf + "."));
        }
      }
      if (typeof x.hasMap === "string" && E.mapa) poe(x, "hasMap", E.mapa);
      if (Array.isArray(x.openingHoursSpecification)) poe(x, "openingHoursSpecification", horariosLD());
      if (Array.isArray(x.sameAs)) {
        var r = E.redes || {}, s = [];
        x.sameAs.forEach(function (u) {
          var rede = /facebook\.com|fb\.com/i.test(u) ? "facebook" : /instagram\.com/i.test(u) ? "instagram" : null;
          if (!rede) s.push(u); else if (r[rede]) s.push(r[rede]);
        });
        ["facebook", "instagram"].forEach(function (k) { if (r[k] && s.indexOf(r[k]) === -1) s.push(r[k]); });
        poe(x, "sameAs", s);
      }
      if (Array.isArray(x.employee) && Array.isArray(E.equipe) && E.equipe.length) {
        poe(x, "employee", E.equipe.map(function (p) {
          var o = { "@type": "Person", name: p.nome };
          if (p.cargo) o.jobTitle = p.cargo;
          return o;
        }));
      }
      Object.keys(x).forEach(function (k) { if (k !== "address" && x[k] && typeof x[k] === "object") visita(x[k]); });
    })(o);
    return mudou;
  }
  function jsonLD() {
    $$('script[type="application/ld+json"]').forEach(function (s) {
      try {
        var j = JSON.parse(s.textContent);
        if (atualizaLD(j)) s.textContent = JSON.stringify(j, null, 1);
      } catch (e) { /* bloco ilegível: fica como está */ }
    });
  }

  /* ------------------------------------------------------------------
     3. Ficha de produto (LV.cardProduto) e listas (data-produtos)
     ------------------------------------------------------------------ */
  // Nome curto das categorias que vieram com o site, enquanto a categoria tem o nome original; renomeada
  // no painel (ou categoria nova), vale o nome do catálogo. O mesmo de CATEGORIAS_CURTAS (painel/lib/catalogo.php).
  var CAT_CURTO = { balancas: ["Balanças", "Balanças"], automacao: ["Automação", "Automação Comercial"], informatica: ["Informática", "Informática e PDV"] };
  LV.catCurto = function (id) {
    var nome = BC ? BC.categoriaNome(id) : "", c = Object.prototype.hasOwnProperty.call(CAT_CURTO, id) ? CAT_CURTO[id] : null;
    return c && (!nome || nome === c[1]) ? c[0] : (nome || id);
  };

  function corta(v, max, seps) {
    v = String(v == null ? "" : v).split(" (")[0].split(";")[0].trim();
    for (var i = 0; i < seps.length && v.length > max; i++) v = v.split(seps[i])[0].trim();
    if (v.length <= max) return v;
    var c = v.slice(0, max), sp = c.lastIndexOf(" ");
    return (sp > max * 0.3 ? c.slice(0, sp) : c).replace(/[,\s/–:-]+$/, "") + "…";
  }
  // Valor curto para a ficha: na capacidade mostra "Até N kg"; nos demais, reticências após 38 caracteres
  LV.curto = function (v, chave) {
    v = String(v == null ? "" : v).replace(/\s*\([^)]*\)/g, "").split(";")[0].trim();
    if (/^capacidade/i.test(chave || "")) {
      var kg = (v.match(/\d+(?:,\d+)?(?=\s*kg)/g) || []).map(function (s) { return parseFloat(s.replace(",", ".")); });
      if (kg.length > 1 && / ou |, /.test(v)) return "Até " + String(Math.max.apply(null, kg)).replace(".", ",") + " kg";
    }
    if (v.length <= 38) return v;
    var c = v.slice(0, 38), sp = c.lastIndexOf(" ");
    return (sp > 12 ? c.slice(0, sp) : c).replace(/[,\s/–:-]+$/, "") + "…";
  };
  LV.chaveCurta = function (k) { return corta(k, 26, [" / "]); };
  LV.spec = function (p, chave) {
    var s = (p.especificacoes || []).filter(function (r) { return String(r[0]).toLowerCase().indexOf(chave) === 0; })[0];
    return s ? s[1] : "";
  };

  // Miniaturas de 480 px: img/p480/r/ (recorte, de compartilhado/img/produtos-recorte) e img/p480/f/
  // (foto em fundo branco, de compartilhado/img/produtos). WebP q82, geradas com Pillow.
  // [largura da miniatura, largura do original]. Original com 480 px ou menos (bk200f, gertecg2,
  // hospitalar, quickscan) e produto novo sem miniatura usam só o original.
  var P480 = {"r/2098.webp":[480,900],"r/8217.webp":[480,882],"r/9094plus.webp":[480,785],"r/allmidia.webp":[480,900],"r/argox.webp":[480,609],"r/balmak-one.webp":[480,900],"r/balmak-orion2.webp":[480,900],"r/balmak.webp":[480,900],"r/bck30.webp":[480,886],"r/bematech-sat.webp":[480,836],"r/centrium-pc.webp":[480,900],"r/el4200.webp":[480,685],"r/elgin-i9.webp":[480,805],"r/elgin-smart.webp":[480,873],"r/epson-t20.webp":[480,755],"r/fatiador.webp":[480,900],"r/gavetabema.webp":[480,900],"r/gertec504.webp":[480,732],"r/l42pro.webp":[480,865],"r/menno.webp":[480,900],"r/mit.webp":[480,900],"r/mt720.webp":[480,882],"r/nobreak-apc.webp":[480,598],"r/nobreak-nhs.webp":[480,483],"r/one-pesadora.webp":[480,900],"r/prix3fit.webp":[480,900],"r/prix3plus.webp":[480,900],"r/prix4due.webp":[480,809],"r/prix4uno.webp":[480,900],"r/prix5.webp":[480,900],"r/prix7e.webp":[480,900],"r/sat-custom.webp":[480,891],"r/sat-jetway.webp":[480,874],"r/sat-tanca.webp":[480,718],"r/sko44.webp":[480,886],"r/tanca.webp":[480,742],"r/tec44.webp":[480,900],"r/tl120.webp":[480,599],"r/tl220.webp":[480,513],"r/tl900.webp":[480,631],"r/uni350.webp":[480,809],"r/w300.webp":[480,900],"r/zebra.webp":[480,785],"f/2098.webp":[480,900],"f/8217.webp":[480,882],"f/9094plus.webp":[480,785],"f/allmidia.webp":[480,900],"f/argox.webp":[480,609],"f/balmak-one.webp":[480,900],"f/balmak-orion2.webp":[480,900],"f/balmak.webp":[480,900],"f/bck30.webp":[480,886],"f/bematech-sat.webp":[480,836],"f/centrium-pc.webp":[480,900],"f/el4200.webp":[480,685],"f/elgin-i9.webp":[480,805],"f/elgin-smart.webp":[480,873],"f/epson-t20.webp":[480,755],"f/fatiador.webp":[480,900],"f/gavetabema.webp":[480,900],"f/gertec504.webp":[480,732],"f/l42pro.webp":[480,865],"f/menno.webp":[480,900],"f/mit.webp":[480,900],"f/mt720.webp":[480,882],"f/nobreak-apc.webp":[480,598],"f/nobreak-nhs.webp":[480,483],"f/one-pesadora.webp":[480,900],"f/prix3fit.webp":[480,900],"f/prix3plus.webp":[480,900],"f/prix4due.webp":[480,809],"f/prix4uno.webp":[480,900],"f/prix5.webp":[480,900],"f/prix7e.webp":[480,900],"f/sat-custom.webp":[480,891],"f/sat-jetway.webp":[480,874],"f/sat-tanca.webp":[480,718],"f/sko44.webp":[480,886],"f/tanca.webp":[480,742],"f/tec44.webp":[480,900],"f/tl120.webp":[480,599],"f/tl220.webp":[480,513],"f/tl900.webp":[480,631],"f/uni350.webp":[480,809],"f/w300.webp":[480,900],"f/zebra.webp":[480,785]};
  LV.imgProduto = function (p, opts) {
    opts = opts || {};
    var src = BC.imgProduto(p, true), file = src.split("/").pop(), pasta = BC.temRecorte(p) ? "r/" : "f/";
    var v = P480[pasta + file];
    var set = v ? ' srcset="img/p480/' + pasta + file + " " + v[0] + "w, " + src + " " + v[1] + 'w" sizes="' + (opts.sizes || "(max-width: 639px) 46vw, 340px") + '"' : "";
    return '<img src="' + src + '"' + set + ' alt="' + LV.esc(opts.alt != null ? opts.alt : p.marca + " " + p.nome) + '" width="900" height="600"' +
      (opts.eager ? "" : ' loading="lazy"') + ' decoding="async">';
  };
  // Foto pequena do produto, do melhor endereço para o pior: miniatura de 480 px (se houver), recorte
  // (ou foto) original e a foto em fundo branco. Produto sem foto → lista vazia.
  LV.fotosProduto = function (p) {
    var l = [], src = BC.imgProduto(p, true), file = src.split("/").pop(), pasta = BC.temRecorte(p) ? "r/" : "f/";
    if (P480[pasta + file]) l.push("img/p480/" + pasta + file);
    [src, BC.imgProduto(p, false)].forEach(function (u) { if (u.split("/").pop() && l.indexOf(u) < 0) l.push(u); });
    return l;
  };
  // Põe a 1ª foto da lista no <img>; se não carregar, tenta a próxima; se nenhuma carregar, esconde
  // o <img> (fica o fundo do quadro, nunca o ícone de imagem quebrada).
  LV.poeFoto = function (img, lista) {
    var i = 0;   // posição, na lista, da foto que está no <img>
    function semFoto() { img.onerror = null; img.removeAttribute("src"); img.hidden = true; }
    function falhou() { if (++i < lista.length) img.src = lista[i]; else semFoto(); }
    img.removeAttribute("srcset"); img.removeAttribute("sizes");
    if (!lista.length) { semFoto(); return; }
    img.onerror = falhou;
    if (img.getAttribute("src") !== lista[0]) img.src = lista[0];
    else if (img.complete && !img.naturalWidth) falhou();   // a mesma do HTML, que já falhou antes daqui
  };
  // Rede de segurança para as demais fotos de produto (fichas, vitrine, painel de detalhes): se o arquivo
  // faltar no servidor (ex.: foto de uma atualização do catálogo que não chegou), a ficha tenta a foto
  // original no lugar da miniatura e, se ainda falhar, fica sem foto, nunca com o ícone de imagem quebrada.
  document.addEventListener("error", function (e) {
    var img = e.target;
    if (!img || img.tagName !== "IMG" || img.onerror) return;   // quem tem tratamento próprio (LV.poeFoto) decide
    if (!/\/img\/(produtos|produtos-recorte|p480\/[rf])\//.test(img.currentSrc || img.src || "")) return;
    if (img.hasAttribute("srcset") && img.currentSrc && img.currentSrc !== img.src) {
      img.removeAttribute("srcset"); img.removeAttribute("sizes");   // falhou a miniatura: vai a original (src)
      return;
    }
    img.hidden = true;
  }, true);

  // Nome de modelo para títulos display: o que tem hífen ("G2-E", "BCK-30") não quebra no hífen
  // e um final curto ("MIT 7", "Prix 3") não fica sozinho na última linha.
  LV.nomeHTML = function (nome) {
    return LV.esc(nome).replace(/(\S+-\S+)/g, '<span class="nobr">$1</span>').replace(/ \/ /g, "\u00a0/ ").replace(/ (\S{1,2})$/, "\u00a0$1");
  };

  // Na página do catálogo o "Detalhes" abre o painel ali mesmo; nas outras, vai para produtos.html#p=ID
  function baseDetalhe() { return document.querySelector("[data-drawer]") ? "" : "produtos.html"; }

  /**
   * LV.cardProduto(p, opts) → HTML (string) da ficha técnica do produto.
   * opts: n (nº da ficha), variante ("ficha" | "compacta" | "escura"), linhas (nº de linhas
   * da tabela, padrão 3), resumo (false esconde), h ("h3"), detalhe (URL base do "Detalhes"),
   * eager (imagem sem lazy), sizes, classe (classes extras).
   */
  LV.cardProduto = function (p, opts) {
    if (typeof p === "string") p = BC.porId(p);
    if (!p) return "";
    opts = opts || {};
    var esc = LV.esc, est = BC.estoque(p), rec = BC.temRecorte(p);
    var n = opts.n || (BC.produtos.indexOf(p) + 1);
    var specs = (p.especificacoes || []).length ? p.especificacoes : [["Linha", p.subcategoria]];
    var rows = specs.slice(0, opts.linhas == null ? 3 : opts.linhas).map(function (r) { return [LV.chaveCurta(r[0]), LV.curto(r[1], r[0])]; });
    var href = (opts.detalhe != null ? opts.detalhe : baseDetalhe()) + "#p=" + encodeURIComponent(p.id);
    var semi = p.condicao === "seminovo", h = opts.h || "h3";
    var cls = "spec" + (opts.variante && opts.variante !== "ficha" ? " spec--" + opts.variante : "") + (opts.classe ? " " + opts.classe : "");
    return '<article class="' + cls + '" data-cat="' + esc(p.categoria) + '" data-id="' + esc(p.id) + '">' +
      '<span class="spec__tab" title="' + esc(BC.categoriaNome(p.categoria) || LV.catCurto(p.categoria)) + '">' + esc(LV.catCurto(p.categoria)) + "</span>" +
      '<div class="spec__head" aria-hidden="true"><span>Nº ' + LV.pad(n) + "</span></div>" +
      '<figure class="spec__fig' + (rec ? "" : " spec__fig--photo") + '">' + LV.imgProduto(p, opts) + "</figure>" +
      '<div class="spec__body">' +
        '<p class="spec__brand">' + esc(p.marca) + "</p>" +
        "<" + h + ' class="spec__model"><span class="sr-only">' + esc(p.marca) + " </span>" + LV.nomeHTML(p.nome) + "</" + h + ">" +
        (opts.resumo === false ? "" : '<p class="spec__sub">' + esc(p.resumo) + "</p>") +
        (rows.length ? '<dl class="spec__table">' + rows.map(function (r) {
          return "<div><dt>" + esc(r[0]) + "</dt><dd>" + esc(r[1]) + "</dd></div>";
        }).join("") + "</dl>" : "") +
        '<div class="spec__badges"><span class="badge badge--' + esc(est.classe) + '">' + esc(est.rotulo) + "</span>" +
          (semi ? '<span class="badge badge--semi">Seminovo</span>' : "") + "</div>" +
      "</div>" +
      '<div class="spec__foot">' +
        '<p class="spec__price' + (p.preco ? " has-preco" : "") + '"><span>Preço</span>' + (p.preco ? "<b>" + esc(p.preco) + "</b>" : "<b>Consulte</b>") + "</p>" +
        '<a class="btn btn--line btn--sm" href="' + href + '" data-detail="' + esc(p.id) + '" aria-label="Detalhes: ' + esc(p.marca + " " + p.nome) + '"><span class="st" data-t="Detalhes">Detalhes</span></a>' +
        '<a class="btn btn--green btn--sm" href="' + BC.whatsProduto(p) + '" target="_blank" rel="noopener" aria-label="Solicitar orçamento de ' + esc(p.marca + " " + p.nome) + ' pelo WhatsApp (abre em nova aba)">' +
          '<svg class="i" aria-hidden="true"><use href="#i-wa"/></svg><span class="st" data-t="WhatsApp">WhatsApp</span></a>' +
      "</div>" +
    "</article>";
  };

  // Lista de produtos por seletor: "destaques", "todos", "cat:balancas", "sub:Checkout" ou "id1,id2,id3"
  LV.produtos = function (sel) {
    sel = String(sel || "destaques").trim();
    if (sel === "destaques") return BC.destaques();
    if (sel === "todos") return BC.produtos.slice();
    if (sel.indexOf("cat:") === 0) return BC.filtrar({ categoria: sel.slice(4) });
    if (sel.indexOf("sub:") === 0) return BC.filtrar({ subcategoria: sel.slice(4) });
    return sel.split(",").map(function (id) { return BC.porId(id.trim()); }).filter(Boolean);
  };
  // Itens <li class="pgrid__i"> prontos para uma <ul class="pgrid">
  LV.listaProdutos = function (lista, opts) {
    return (lista || []).map(function (p) {
      return '<li class="pgrid__i" data-id="' + LV.esc(p.id) + '">' + LV.cardProduto(p, opts) + "</li>";
    }).join("");
  };
  function renderProdutos() {
    $$("[data-produtos]").forEach(function (ul) {
      var lista = LV.produtos(ul.getAttribute("data-produtos"));
      var lim = parseInt(ul.getAttribute("data-limite"), 10);
      if (lim > 0) lista = lista.slice(0, lim);
      var linhas = ul.getAttribute("data-linhas");
      ul.innerHTML = LV.listaProdutos(lista, {
        variante: ul.getAttribute("data-variante") || undefined,
        linhas: linhas != null ? parseInt(linhas, 10) : undefined,
        resumo: ul.getAttribute("data-resumo") === "nao" ? false : undefined,
        eager: ul.hasAttribute("data-eager")
      });
      if (!ul.hasAttribute("data-sem-reveal")) $$(".pgrid__i > .spec", ul).forEach(function (c) { c.setAttribute("data-reveal", ""); });
    });
  }

  /* ------------------------------------------------------------------
     4. Cabeçalho: link ativo, barra de progresso, botões flutuantes
     ------------------------------------------------------------------ */
  function navAtivo() {
    // O HTML já marca aria-current="page"; isto só cobre páginas sem marcação
    if ($(".nav__a[aria-current]")) return;
    var home = /^index(-\d+)?\.html$/.test(LV.pagina);
    $$(".nav__a, .menu__list a, .ftr__col a").forEach(function (a) {
      var href = (a.getAttribute("href") || "").split("#")[0].toLowerCase();
      if ((home && a.hasAttribute("data-home")) || (!home && href === LV.pagina)) a.setAttribute("aria-current", "page");
    });
  }

  var progress = $(".hdr__progress");
  var floatBox = $("[data-float]");
  function onScrollBasic() {
    var y = window.scrollY || doc.scrollTop;
    var max = Math.max(1, doc.scrollHeight - window.innerHeight);
    if (progress && !LV.motionOK()) progress.style.transform = "scaleX(" + Math.min(1, y / max) + ")";
    if (floatBox) floatBox.classList.toggle("is-hidden", y < window.innerHeight * 0.55);
  }
  window.addEventListener("scroll", onScrollBasic, { passive: true });

  // O flutuante sai de cena quando aparece um bloco que já tem WhatsApp ([data-float-off]) ou o rodapé
  function floatOff() {
    if (!floatBox || !("IntersectionObserver" in window)) return;
    var vis = new Set();
    var io = new IntersectionObserver(function (en) {
      en.forEach(function (e) { if (e.isIntersecting) vis.add(e.target); else vis.delete(e.target); });
      floatBox.classList.toggle("is-off", vis.size > 0);
    }, { rootMargin: "0px 0px -12% 0px" });
    $$("[data-float-off], .ftr").forEach(function (el) { io.observe(el); });
  }

  // Fundo inerte enquanto um diálogo (menu, painel) está aberto
  LV.inertBg = function (on, extra) {
    ["main", ".ftr", "[data-float]", "[data-preview]"].concat(extra || []).forEach(function (s) { var el = $(s); if (el) el.inert = !!on; });
  };

  /* ------------------------------------------------------------------
     5. Menu mobile (foco preso, Esc fecha)
     ------------------------------------------------------------------ */
  var menu = $("[data-menu]"), burger = $("[data-burger]"), burgerLabel = $("[data-burger-label]");
  var menuOpen = false;
  function menuFocusables() { return [burger].concat($$("a, button", menu)); }
  function openMenu() {
    if (menuOpen) return; menuOpen = true;
    menu.hidden = false; burger.setAttribute("aria-expanded", "true"); LV.inertBg(true);
    if (burgerLabel) burgerLabel.textContent = "Fechar menu";
    doc.style.overflow = "hidden"; if (LV.lenis) LV.lenis.stop();
    if (LV.motionOK()) {
      gsap.fromTo($(".menu__band", menu), { scaleY: 0, transformOrigin: "50% 0%" }, { scaleY: 1, duration: .7, ease: "expo.inOut" });
      gsap.fromTo($$(".menu__list li", menu), { yPercent: 60, opacity: 0 }, { yPercent: 0, opacity: 1, duration: .7, stagger: .05, ease: "expo.out", delay: .12 });
      gsap.fromTo($(".menu__foot", menu), { opacity: 0 }, { opacity: 1, duration: .5, delay: .35 });
    }
    var first = $("a", menu); if (first) first.focus();
  }
  function closeMenu(noFocus) {
    if (!menuOpen) return; menuOpen = false;
    burger.setAttribute("aria-expanded", "false");
    if (burgerLabel) burgerLabel.textContent = "Abrir menu";
    doc.style.overflow = ""; if (LV.lenis) LV.lenis.start();
    menu.hidden = true; LV.inertBg(false);
    if (!noFocus) burger.focus();
  }
  LV.fecharMenu = closeMenu;
  if (menu && burger) {
    burger.addEventListener("click", function () { menuOpen ? closeMenu() : openMenu(); });
    menu.addEventListener("click", function (e) { var a = e.target.closest("a"); if (a) closeMenu(true); });
    document.addEventListener("keydown", function (e) {
      if (!menuOpen) return;
      if (e.key === "Escape") { closeMenu(); return; }
      if (e.key === "Tab") {
        var f = menuFocusables(), i = f.indexOf(document.activeElement);
        if (e.shiftKey && i <= 0) { e.preventDefault(); f[f.length - 1].focus(); }
        else if (!e.shiftKey && i === f.length - 1) { e.preventDefault(); f[0].focus(); }
      }
    });
    window.addEventListener("resize", function () { if (menuOpen && window.innerWidth > 1080) closeMenu(true); });
  }

  /* ------------------------------------------------------------------
     6. Âncoras internas (rolagem suave; distância do cabeçalho via scroll-margin-top)
     ------------------------------------------------------------------ */
  // Posição de rolagem que deixa o alvo logo abaixo do cabeçalho: desconta só o scroll-margin-top
  // (cabeçalho + aba da seção), sem somar o scroll-padding que o catálogo usa para a barra de filtros.
  // Mesma conta para o clique numa âncora (LV.irPara) e para a chegada por pagina.html#secao.
  function yAlvo(t) {
    return Math.max(0, t.getBoundingClientRect().top + (window.scrollY || doc.scrollTop || 0) - (parseFloat(getComputedStyle(t).scrollMarginTop) || 0));
  }
  LV.irPara = function (t) {
    if (typeof t === "string") t = document.getElementById(t.replace(/^#/, ""));
    if (!t) return;
    var y = yAlvo(t);
    if (LV.lenis) LV.lenis.scrollTo(y, { duration: 1.3 });
    else window.scrollTo({ top: y, behavior: reduceMQ.matches ? "auto" : "smooth" });
    if (!t.hasAttribute("tabindex")) t.setAttribute("tabindex", "-1");
    t.focus({ preventScroll: true });
  };
  document.addEventListener("click", function (e) {
    var a = e.target.closest("a[href]");
    if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey) return;
    var href = a.getAttribute("href");
    if (href.charAt(0) !== "#" || href.length < 2 || href.indexOf("#p=") === 0) return;
    var t = document.getElementById(href.slice(1));
    if (!t) return;
    e.preventDefault();
    LV.irPara(t);
    try { history.pushState(null, "", href); } catch (err) { /* file:// em alguns navegadores */ }
  });

  /* ------------------------------------------------------------------
     7. Faixas (ticker): duplica a lista, pausa no botão/hover
     ------------------------------------------------------------------ */
  function setupTickers() {
    $$("[data-ticker]").forEach(function (t) {
      var track = $(".ticker__track", t), list = $(".ticker__list", t);
      if (!track || !list || t._lv) return; t._lv = true;
      var base = Array.prototype.slice.call(list.children), guard = 0;
      while (list.scrollWidth < window.innerWidth * 1.1 && guard++ < 8) {
        base.forEach(function (li) { var c = li.cloneNode(true); c.setAttribute("aria-hidden", "true"); list.appendChild(c); });
      }
      var clone = list.cloneNode(true); clone.setAttribute("aria-hidden", "true"); track.appendChild(clone);
      var btn = $("[data-ticker-btn]", t);
      if (btn) btn.addEventListener("click", function () {
        var paused = !t.classList.contains("is-paused");
        t.classList.toggle("is-paused", paused);
        btn.setAttribute("aria-pressed", String(paused));
        var use = $("use", btn); if (use) use.setAttribute("href", paused ? "#i-play" : "#i-pause");
        var sr = $(".sr-only", btn); if (sr) sr.textContent = (paused ? "Continuar" : "Pausar") + sr.textContent.replace(/^(Pausar|Continuar)/, "");
        if (t._tw) paused ? t._tw.pause() : t._tw.resume();
      });
    });
  }

  /* ------------------------------------------------------------------
     8. Acordeão (FAQ) — sem JS as respostas ficam abertas
     ------------------------------------------------------------------ */
  function setupAccordion() {
    $$("[data-acc]").forEach(function (acc) {
      acc.classList.add("acc--js");
      void acc.offsetHeight;
      requestAnimationFrame(function () { requestAnimationFrame(function () { acc.classList.add("acc--ready"); }); });
      $$(".acc__b", acc).forEach(function (b) {
        var p = document.getElementById(b.getAttribute("aria-controls"));
        if (!p) return;
        p.inert = b.getAttribute("aria-expanded") !== "true";   // item aberto no HTML continua aberto
        var t;
        var refresh = function () { clearTimeout(t); LV.refresh(); };
        p.addEventListener("transitionend", function (e) { if (e.target === p && e.propertyName === "grid-template-rows") refresh(); });
        b.addEventListener("click", function () {
          var open = b.getAttribute("aria-expanded") !== "true";
          b.setAttribute("aria-expanded", String(open));
          p.classList.toggle("is-open", open);
          p.inert = !open;
          clearTimeout(t); t = setTimeout(refresh, 700);
        });
      });
    });
  }

  /* ------------------------------------------------------------------
     9. Formulário → mensagem no WhatsApp (sem backend)
     ------------------------------------------------------------------ */
  function setupForm() {
    $$("[data-form]").forEach(function (f) {
      var err = $("[data-form-err]", f);
      // ?assunto=... na URL pré-seleciona o assunto (ex.: contato.html?assunto=BC%20Fichas)
      try {
        var q = new URLSearchParams(location.search).get("assunto");
        if (q && f.assunto) $$("option", f.assunto).forEach(function (o) { if (BC.normaliza(o.textContent).indexOf(BC.normaliza(q)) !== -1) f.assunto.value = o.value || o.textContent; });
      } catch (e) { /* ok */ }
      f.addEventListener("submit", function (e) {
        e.preventDefault();
        var nome = f.nome.value.trim(), assunto = f.assunto ? f.assunto.value : "", msg = f.mensagem ? f.mensagem.value.trim() : "";
        var field = f.nome.closest(".field");
        if (!nome) {
          if (field) field.classList.add("is-err"); f.nome.setAttribute("aria-invalid", "true");
          if (err) err.textContent = "Escreva o seu nome para a gente saber com quem está falando.";
          f.nome.focus(); return;
        }
        if (field) field.classList.remove("is-err"); f.nome.removeAttribute("aria-invalid"); if (err) err.textContent = "";
        var texto = "Olá! Meu nome é " + nome + "." + (assunto ? "\n*Assunto:* " + assunto : "") + (msg ? "\n" + msg : "") + "\n(Mensagem enviada pelo site)";
        var url = BC.whatsLink(texto);
        contar("whatsapp", "", "formulario");   // 12. contagem anônima
        var w = window.open(url, "_blank");
        if (w) { try { w.opener = null; } catch (er) { /* ok */ } } else { location.href = url; }
      });
    });
  }

  /* ------------------------------------------------------------------
     10. Abas de kits por segmento ([data-kits]) — sem JS todos os kits aparecem
     ------------------------------------------------------------------ */
  // Produtos de exemplo dos kits (.kit__p): o catálogo que vale é o do servidor, que o painel edita e
  // que pode estar antes ou depois de uma atualização do catálogo. Cada item traz o código do modelo
  // atual (data-detail) e, em data-ou, o(s) que ele substitui, em ordem de preferência. Vale o primeiro
  // que existir e estiver visível: marca, nome e foto vêm do catálogo (assim a foto trocada no painel
  // aparece aqui). Se nenhum existir, o item sai da lista (nunca um link para produto fora do catálogo);
  // remover em vez de esconder mantém a regra do celular (só os 2 primeiros) contando certo.
  function hidratarKits() {
    $$(".kit__prods").forEach(function (ul) {
      var usados = Object.create(null);   // sem protótipo: código "constructor" não engana
      $$(".kit__p[data-detail]", ul).forEach(function (a) {
        var li = a.closest("li") || a, p = null;
        var ids = [a.getAttribute("data-detail")].concat((a.getAttribute("data-ou") || "").split(/\s+/));
        for (var i = 0; i < ids.length && !p; i++) if (ids[i]) p = BC.porId(ids[i]);
        if (!p || usados[p.id]) { li.parentNode.removeChild(li); return; }   // o mesmo produto não aparece 2 vezes no kit
        usados[p.id] = true;
        a.setAttribute("data-detail", p.id);
        a.setAttribute("href", baseDetalhe() + "#p=" + encodeURIComponent(p.id));
        var marca = $("small", a), nome = $("b", a), img = $("img", a);
        if (marca) marca.textContent = p.marca;
        if (nome) nome.innerHTML = LV.nomeHTML(p.nome);   // "MT-721" não quebra no hífen
        if (img) LV.poeFoto(img, LV.fotosProduto(p));
      });
      if (!$(".kit__p", ul)) ul.hidden = true;
    });
  }
  function setupKits() {
    $$("[data-kits]").forEach(function (box) {
      var tabs = $$("[data-kit-tab]", box), panels = $$("[data-kit-panel]", box);
      if (!tabs.length || tabs.length !== panels.length) return;
      box.classList.add("kits--js");
      var list = $("[data-kit-tabs]", box); if (list) list.hidden = false;
      function sel(i, focus, anim) {
        tabs.forEach(function (t, k) {
          var on = k === i;
          t.setAttribute("aria-selected", String(on)); t.tabIndex = on ? 0 : -1;
          panels[k].hidden = !on;
        });
        if (focus) tabs[i].focus();
        // chips no celular: mantém a aba ativa à vista
        if (list && list.scrollWidth > list.clientWidth) {
          var r = tabs[i].getBoundingClientRect(), lr = list.getBoundingClientRect();
          if (r.left < lr.left || r.right > lr.right) list.scrollTo({ left: list.scrollLeft + r.left - lr.left - 16, behavior: reduceMQ.matches ? "auto" : "smooth" });
        }
        if (anim && LV.motionOK()) {
          gsap.fromTo(panels[i].children, { opacity: 0, y: 18 }, { opacity: 1, y: 0, duration: .6, stagger: .05, ease: "expo.out", overwrite: true });
        }
        LV.refresh();
      }
      tabs.forEach(function (t, i) {
        t.addEventListener("click", function () { sel(i, false, true); });
        t.addEventListener("keydown", function (e) {
          var n = null, k = e.key;
          if (k === "ArrowRight" || k === "ArrowDown") n = (i + 1) % tabs.length;
          else if (k === "ArrowLeft" || k === "ArrowUp") n = (i - 1 + tabs.length) % tabs.length;
          else if (k === "Home") n = 0; else if (k === "End") n = tabs.length - 1;
          if (n === null) return;
          e.preventDefault(); sel(n, true, true);
        });
      });
      sel(0, false, false);
    });
  }

  /* ------------------------------------------------------------------
     11. Animação (GSAP + ScrollTrigger + SplitText + Lenis)
     ------------------------------------------------------------------ */
  LV.st = function (trigger, start) { return { trigger: trigger, start: start || "top 88%", end: "max", once: true }; };

  // Revela um lote. Num salto de rolagem os blocos que já ficaram para trás aparecem direto.
  LV.reveal = function (b, dur) {
    if (!window.gsap) return;
    b = [].concat(b);
    var vis = b.filter(function (e) { return e.getBoundingClientRect().bottom > 0; });
    var past = b.filter(function (e) { return vis.indexOf(e) < 0; });
    if (past.length) gsap.set(past, { opacity: 1, x: 0, y: 0, scale: 1, overwrite: true });
    if (vis.length) gsap.to(vis, { opacity: 1, x: 0, y: 0, scale: 1, duration: dur || 1, ease: "expo.out", stagger: Math.min(0.08, 0.4 / Math.max(1, vis.length)), overwrite: true });
  };

  /**
   * LV.wipe(el, opts) — a barra verde inclinada atravessa o elemento e o revela.
   * opts: tl (timeline onde encaixar), at (posição na timeline), cor ("green" | "paper" | "ink"),
   * delay. Cria a barra (.lv-wipe) se o elemento ainda não tiver uma. Retorna a timeline.
   */
  LV.wipe = function (el, opts) {
    opts = opts || {};
    if (!el || !window.gsap) return null;
    var bar = el.querySelector(":scope > .lv-wipe, :scope > .phero__bar");
    if (!bar) {
      bar = document.createElement("span"); bar.className = "lv-wipe"; bar.setAttribute("aria-hidden", "true");
      if (getComputedStyle(el).position === "static") el.style.position = "relative";
      el.appendChild(bar);
    }
    if (opts.cor) bar.style.background = opts.cor === "ink" ? "var(--ink)" : opts.cor === "paper" ? "var(--paper)" : "var(--green)";
    var word = el.querySelector(":scope > .phero__w") || el;
    var kids = word === el ? Array.prototype.filter.call(el.childNodes, function (n) { return n !== bar; }) : [word];
    var targets = kids.filter(function (n) { return n.nodeType === 1; });
    var tl = opts.tl || gsap.timeline({ delay: opts.delay || 0 });
    var at = opts.at == null ? 0 : opts.at;
    gsap.set(bar, { skewX: -12, scaleX: 0, transformOrigin: "0% 50%" });
    if (word === el && !targets.length) {
      // só texto solto: esconde via cor
      gsap.set(el, { color: "transparent" });
      tl.to(bar, { scaleX: 1, duration: 0.42, ease: "expo.in" }, at)
        .set(el, { clearProps: "color" }, at + 0.42);
    } else {
      gsap.set(targets, { autoAlpha: 0 });
      tl.to(bar, { scaleX: 1, duration: 0.42, ease: "expo.in" }, at)
        .set(targets, { autoAlpha: 1 }, at + 0.42);
    }
    tl.set(bar, { transformOrigin: "100% 50%" }, at + 0.42)
      .to(bar, { scaleX: 0, duration: 0.6, ease: "expo.out" }, at + 0.44);
    return tl;
  };

  // Rede de segurança: o que recebe foco por teclado aparece na hora, mesmo antes da animação
  document.addEventListener("focusin", function (e) {
    if (!window.gsap || !e.target.closest) return;
    var el = e.target.closest("[data-reveal], [data-reveal] > *, [data-intro], .spec");
    if (el && +getComputedStyle(el).opacity < 1) gsap.to(el, { opacity: 1, x: 0, y: 0, yPercent: 0, skewX: 0, scale: 1, duration: 0.3, overwrite: true });
  });

  // Abertura do hero de página (.phero): faixa desce, barras verdes revelam as linhas do título.
  // Curta (~1,5 s): o texto de apoio entra junto com o título. Os botões (.btns[data-intro]) NÃO
  // entram aqui: o base.css os mostra por animação CSS desde o 1º quadro (visíveis em < 1 s, sem
  // esperar o GSAP do CDN).
  function introPhero() {
    var h = $(".phero"); if (!h) return;
    var pronto = LV.pronto();
    var tl = gsap.timeline({ defaults: { ease: "expo.out" } });
    var band = $(".phero__band", h);
    if (band) tl.fromTo(band, { scaleY: 0, transformOrigin: "50% 0%" }, { scaleY: 1, duration: 1, ease: "expo.inOut" }, 0);
    $$(".phero__l", h).forEach(function (line, i) { LV.wipe(line, { tl: tl, at: 0.1 + i * 0.12, cor: line.classList.contains("phero__l--green") ? "paper" : "green" }); });
    var fig = $$(".phero__fig", h);
    if (fig.length) tl.from(fig, { yPercent: 12, autoAlpha: 0, duration: 1.2 }, 0.3);
    var calls = $$(".phero__call", h);
    if (calls.length) tl.from(calls, { autoAlpha: 0, y: 8, stagger: 0.08, duration: 0.6 }, 0.85);
    var ref = $(".phero__ref", h);
    if (ref) tl.from(ref, { autoAlpha: 0, duration: 0.9 }, 0.8);
    var rest = $$("[data-intro]", h).filter(function (el) { return !el.classList.contains("phero__w") && !el.classList.contains("btns"); });
    if (rest.length) tl.from(rest, { opacity: 0, y: 24, stagger: 0.06, duration: 0.9 }, 0.3);
    // Estado final garantido: chegou pela transição entre páginas ou tarde → já termina aqui;
    // se o "pagereveal" (View Transitions) ainda vier depois, termina na hora em que ele chegar.
    if (pronto) tl.progress(1);
    else aoRevelar(function () { tl.progress(1); });
    // Ao rolar: título se separa um pouco e a figura sobe
    var lines = $$(".phero__l", h);
    var stl = gsap.timeline({ scrollTrigger: { trigger: h, start: "top top", end: "bottom top", scrub: true } });
    lines.forEach(function (l, i) { stl.to(l, { xPercent: i % 2 ? 4 : -6, ease: "none" }, 0); });
    if (fig.length) stl.to(fig, { yPercent: -8, ease: "none" }, 0);
    if (band) stl.to(band, { xPercent: 6, ease: "none" }, 0);
  }

  // Se a abertura começou ANTES do "pagereveal" e a página chegou pela transição entre páginas,
  // fn() roda quando ele chegar (ex.: termina a timeline do hero). LV.aoRevelar para os ganchos.
  function aoRevelar(fn) {
    if (window.LV_REVELOU || !("onpagereveal" in window)) return;
    window.addEventListener("pagereveal", function (e) { if (e.viewTransition) fn(); }, { once: true });
  }
  LV.aoRevelar = aoRevelar;

  // data-reveal: "" (sobe) | "left" | "right" | "scale" | "stagger" | "mask" | "split" | "wipe"
  var FROM = { "": { y: 40 }, up: { y: 40 }, left: { x: -40 }, right: { x: 40 }, scale: { scale: 0.92 } };
  function reveals() {
    var simples = [], all = $$("[data-reveal]");
    all.forEach(function (el) {
      // Já está na tela e já foi visto (âncora, recarregar no meio, GSAP tardio): fica como está
      if (LV.jaVisivel(el)) return;
      var t = el.getAttribute("data-reveal") || "";
      var start = el.getAttribute("data-reveal-start") || "top 90%";
      var delay = parseFloat(el.getAttribute("data-reveal-delay")) || 0;
      if (FROM[t]) { simples.push(el); return; }
      if (t === "stagger") {
        var kids = Array.prototype.slice.call(el.children);
        if (!kids.length) return;
        gsap.set(kids, { opacity: 0, y: 32 });
        ScrollTrigger.create({ trigger: el, start: start, once: true, onEnter: function () {
          gsap.to(kids, { opacity: 1, y: 0, duration: 0.9, ease: "expo.out", stagger: 0.07, delay: delay, overwrite: true });
        } });
        return;
      }
      if (t === "mask") {
        var s = parseFloat(getComputedStyle(el).getPropertyValue("--s")) || 15;
        var img = $("img", el);
        var tl = gsap.timeline({ scrollTrigger: LV.st(el, start), delay: delay });
        tl.fromTo(el,
          { clipPath: "polygon(" + s + "% 0%, " + s + "% 0%, 0% 100%, 0% 100%)" },
          { clipPath: "polygon(" + s + "% 0%, 100% 0%, " + (100 - s) + "% 100%, 0% 100%)", duration: 1.15, ease: "expo.inOut", clearProps: "clipPath" });
        if (img) tl.from(img, { scale: 1.22, duration: 1.4, ease: "expo.out" }, 0.15);
        return;
      }
      if (t === "split") {
        if (!window.SplitText) { simples.push(el); return; }
        SplitText.create(el, {
          type: "lines", linesClass: "ln", autoSplit: true,
          onSplit: function (self) {
            if (self._lvSplit) { clearTimeout(reveals._rs); reveals._rs = setTimeout(LV.refresh, 60); }
            self._lvSplit = true;
            return gsap.from(self.lines, {
              yPercent: 55, opacity: 0, skewX: -12, transformOrigin: "0% 100%", delay: delay,
              duration: 1.1, stagger: 0.09, ease: "expo.out", scrollTrigger: LV.st(el, start)
            });
          }
        });
        return;
      }
      if (t === "wipe") {
        var wtl = gsap.timeline({ scrollTrigger: LV.st(el, start), delay: delay });
        LV.wipe(el, { tl: wtl, at: 0, cor: el.getAttribute("data-reveal-cor") || "green" });
        return;
      }
      simples.push(el);
    });
    if (simples.length) {
      simples.forEach(function (el) { gsap.set(el, Object.assign({ opacity: 0 }, FROM[el.getAttribute("data-reveal") || ""] || FROM[""])); });
      ScrollTrigger.batch(simples, { start: "top 92%", end: "max", once: true, batchMax: 8, onEnter: function (b) { LV.reveal(b); } });
    }
  }

  var rodou = false;
  function motion() {
    if (!hasGSAP()) { doc.classList.remove("is-loading"); return; }
    var plugins = [ScrollTrigger];
    if (window.SplitText) plugins.push(SplitText);
    if (window.DrawSVGPlugin) plugins.push(DrawSVGPlugin);
    if (window.Flip) plugins.push(Flip);
    gsap.registerPlugin.apply(gsap, plugins);
    var mm = (LV.mm = gsap.matchMedia());

    mm.add("(prefers-reduced-motion: reduce)", function () {
      rodou = true;
      doc.classList.remove("is-loading");
      $$("[data-ticker]").forEach(function (t) { t.classList.add("is-static"); });
    });

    mm.add("(prefers-reduced-motion: no-preference)", function () {
      // Rodando de novo (a pessoa desligou "reduzir movimento" com a página aberta): nada do que
      // já está na tela some para animar outra vez
      if (rodou) manter = true;
      rodou = true;
      // Rolagem suave
      if (window.Lenis) {
        var lenis = (LV.lenis = new Lenis({ autoRaf: false, lerp: 0.11, wheelMultiplier: 1 }));
        lenis.on("scroll", ScrollTrigger.update);
        var raf = function (t) { lenis.raf(t * 1000); };
        gsap.ticker.add(raf); gsap.ticker.lagSmoothing(0);
        if (doc.style.overflow === "hidden") lenis.stop();   // painel ou menu já aberto antes do CDN chegar
      }

      // Tira "is-loading" ANTES de criar as animações de entrada e na mesma tarefa: os from() leem
      // os valores finais reais (sem o estado escondido do CSS) e já pintam o estado inicial — sem piscar.
      doc.classList.remove("is-loading");
      try { introPhero(); } catch (e) { if (window.console) console.warn(e); }
      // Ganchos de cada página (hero da home, catálogo, assistência…)
      hooks.forEach(function (fn) { try { fn(mm); } catch (e) { if (window.console) console.warn(e); } });

      if (progress) gsap.to(progress, { scaleX: 1, ease: "none", scrollTrigger: { start: 0, end: "max", scrub: 0.3 } });

      // Abas das seções entram pela esquerda (as que já estão à vista ficam no lugar)
      $$(".sec__tab").forEach(function (tab) {
        if (LV.jaVisivel(tab)) return;
        gsap.from(tab, { xPercent: -101, duration: 1.1, ease: "expo.out", scrollTrigger: LV.st(tab.parentElement, "top 94%") });
      });

      reveals();

      // Palavras gigantes atravessam a tela com a rolagem
      $$("[data-drift]").forEach(function (el) {
        gsap.fromTo(el, { xPercent: 4 }, {
          xPercent: -22, ease: "none",
          scrollTrigger: { trigger: el.parentElement, start: "top bottom", end: "bottom top", scrub: true }
        });
      });
      // Paralaxe vertical: data-parallax="-12" (yPercent ao atravessar a tela)
      $$("[data-parallax]").forEach(function (el) {
        var v = parseFloat(el.getAttribute("data-parallax")) || -10;
        gsap.fromTo(el, { yPercent: -v / 2 }, { yPercent: v / 2, ease: "none", scrollTrigger: { trigger: el, start: "top bottom", end: "bottom top", scrub: true } });
      });

      // Contadores: data-count (inteiro), data-count-from="2010", data-count-dec (uma casa, vírgula)
      $$("[data-count], [data-count-from], [data-count-dec]").forEach(function (el) {
        if (LV.jaVisivel(el)) return;   // número já à vista: não volta a zero
        var dec = el.hasAttribute("data-count-dec");
        var to = parseFloat((el.textContent || "0").replace(",", "."));
        if (isNaN(to)) return;
        var from = el.hasAttribute("data-count-from") ? parseFloat(el.getAttribute("data-count-from")) : 0;
        var o = { v: from };
        var fmt = function (v) { return dec ? v.toFixed(1).replace(".", ",") : String(Math.round(v)); };
        gsap.fromTo(o, { v: from }, {
          v: to, duration: 1.8, ease: "power3.out", scrollTrigger: LV.st(el, "top 90%"), immediateRender: false,
          onStart: function () { el.textContent = fmt(from); },
          onUpdate: function () { el.textContent = fmt(o.v); },
          onComplete: function () { el.textContent = fmt(to); }
        });
      });

      // Faixas: GSAP controla velocidade, direção e inclinação conforme a rolagem
      var tickers = $$("[data-ticker]").map(function (t) {
        var track = $(".ticker__track", t);
        t.classList.add("is-gsap");
        var dur = parseFloat(getComputedStyle(t).getPropertyValue("--dur")) || 36;
        var tw = gsap.to(track, { xPercent: -50, duration: dur, ease: "none", repeat: -1 });
        tw.totalTime(dur * 400);
        t._tw = tw;
        if (t.classList.contains("is-paused")) tw.pause();
        t.addEventListener("mouseenter", function () { if (!t.classList.contains("is-paused")) gsap.to(tw, { timeScale: 0, duration: .5, overwrite: true }); });
        t.addEventListener("mouseleave", function () { if (!t.classList.contains("is-paused")) gsap.to(tw, { timeScale: 1, duration: .5, overwrite: true }); });
        return { el: t, tw: tw, skew: t.hasAttribute("data-skew") ? gsap.quickTo(track, "skewX", { duration: 0.45, ease: "power3.out" }) : null, speed: parseFloat(t.getAttribute("data-speed")) || 1 };
      });
      if (tickers.length) {
        var idle;
        ScrollTrigger.create({
          start: 0, end: "max",
          onUpdate: function (self) {
            var v = self.getVelocity(), sk = gsap.utils.clamp(-12, 12, v / -220), dir = self.direction;
            tickers.forEach(function (k) {
              if (k.skew) k.skew(sk);
              if (!k.el.classList.contains("is-paused") && !k.el.matches(":hover")) {
                gsap.to(k.tw, { timeScale: dir * (1 + Math.min(4, Math.abs(v) / 900)) * k.speed, duration: 0.3, overwrite: true });
              }
            });
            clearTimeout(idle);
            idle = setTimeout(function () {
              tickers.forEach(function (k) {
                if (k.skew) k.skew(0);
                if (!k.el.classList.contains("is-paused") && !k.el.matches(":hover")) gsap.to(k.tw, { timeScale: dir * k.speed, duration: 0.8, overwrite: true });
              });
            }, 140);
          }
        });
      }

      // SAT → NFC-e: risca, aponta e acende ([data-sat])
      $$("[data-sat]").forEach(function (sat) {
        if (LV.jaVisivel(sat)) return;
        var strike = $(".sat__strike", sat);
        if (strike) gsap.set(strike, { rotation: -12, scaleX: 0, transformOrigin: "0% 50%" });
        var tl = gsap.timeline({ scrollTrigger: LV.st(sat, "top 75%") });
        tl.from($(".sat__from", sat), { opacity: 0, x: -30, duration: 0.7, ease: "expo.out" });
        if (strike) tl.to(strike, { scaleX: 1, duration: 0.5, ease: "expo.inOut" }, 0.45);
        tl.from($(".sat__arrow", sat), { opacity: 0, x: -40, duration: 0.7, ease: "expo.out" }, 0.75)
          .from($(".sat__to", sat), { opacity: 0, skewX: -12, xPercent: -10, duration: 0.9, ease: "expo.out" }, 0.9);
      });

      // Carimbo: cai do alto e "bate" na página ([data-stamp])
      $$("[data-stamp]").forEach(function (stamp) {
        if (stamp.closest(".phero") || LV.jaVisivel(stamp)) return;
        gsap.timeline({ scrollTrigger: LV.st(stamp, "top 85%") })
          .fromTo(stamp, { scale: 2.4, autoAlpha: 0, rotation: -32 }, { scale: 1, autoAlpha: 1, rotation: -12, duration: 0.55, ease: "power4.in" })
          .to(stamp, { scale: 0.94, duration: 0.08, yoyo: true, repeat: 1, ease: "power1.inOut" });
      });

      if (fineMQ.matches) {
        // Botões magnéticos
        $$("[data-magnet]").forEach(function (b) {
          var qx = gsap.quickTo(b, "x", { duration: 0.5, ease: "power3.out" }), qy = gsap.quickTo(b, "y", { duration: 0.5, ease: "power3.out" });
          b.addEventListener("mousemove", function (e) { var r = b.getBoundingClientRect(); qx((e.clientX - r.left - r.width / 2) * 0.25); qy((e.clientY - r.top - r.height / 2) * 0.35); });
          b.addEventListener("mouseleave", function () { qx(0); qy(0); });
        });
        // Inclinação 3D seguindo o mouse (data-tilt; a área sensível é a seção pai)
        $$("[data-tilt]").forEach(function (el) {
          var area = el.closest("section") || el;
          gsap.set(el, { transformPerspective: 1100 });
          var rx = gsap.quickTo(el, "rotationX", { duration: 0.8, ease: "power3.out" });
          var ry = gsap.quickTo(el, "rotationY", { duration: 0.8, ease: "power3.out" });
          area.addEventListener("mousemove", function (e) { ry((e.clientX / innerWidth - 0.5) * 10); rx((e.clientY / innerHeight - 0.5) * -7); });
          area.addEventListener("mouseleave", function () { rx(0); ry(0); });
        });
      }

      window.addEventListener("load", LV.refresh);
      if (document.fonts && document.fonts.ready) document.fonts.ready.then(LV.refresh);

      return function () {
        if (LV.lenis) { LV.lenis.destroy(); LV.lenis = null; }
        $$("[data-ticker]").forEach(function (t) { t.classList.remove("is-gsap"); t._tw = null; });
      };
    });
  }

  /* ------------------------------------------------------------------
     12. Contagem anônima ("Mais procurados", no painel)
     Manda um aviso pequeno para painel/contar.php a cada página vista,
     a cada produto aberto no catálogo (produtos.html#p=… e o "Detalhes"
     das fichas) e a cada clique no WhatsApp ou em "Ligar":
       { t: "pagina" | "produto" | "whatsapp" | "ligar",
         p: a página (classe pg-… do <body>), id: código do produto (botões
         de um produto só: ficha do catálogo e cartão) ou "",
         o: o lugar do botão ("topo", "flutuante", "rodape"…; ver lugar()) ou "" }
     Sem cookies, sem identificador guardado no aparelho, nada pessoal: o
     painel só soma quantos, por dia. Não conta navegador automatizado
     (navigator.webdriver), a página 404 nem o aparelho que pediu para não
     contar (localStorage "bc-nao-contar" = "1": o painel liga sozinho
     quando alguém entra nele). Nunca atrasa nem quebra a página: a visita
     sai depois da carga, nada espera resposta e todo erro é ignorado.
     Um link pode dizer o próprio lugar: data-contar-o="…" (e data-contar-id).
     ------------------------------------------------------------------ */
  var CONTAR_PAGINA = (function () {
    var m = /(?:^|\s)pg-(inicio|produtos|assistencia|sistema|empresa|contato)(?=\s|$|-)/.exec((document.body && document.body.className) || "");
    return m ? m[1] : "";
  })();
  var contarURL = "", contados = 0, abertos = Object.create(null);   // mapa sem protótipo (código "constructor")
  function semContar() {
    if (!CONTAR_PAGINA || navigator.webdriver || !/^https?:$/.test(location.protocol)) return true;
    try { return window.localStorage.getItem("bc-nao-contar") === "1"; } catch (e) { return false; }
  }
  function contar(t, id, o) {
    try {
      if (semContar() || ++contados > 80) return;
      if (!contarURL) {
        // painel/ fica ao lado de compartilhado/ (BC.img("") = …/compartilhado/img/)
        try { contarURL = new URL("../../painel/contar.php", new URL(BC.img(""), location.href)).href; }
        catch (e) { contarURL = "painel/contar.php"; }
      }
      var corpo = JSON.stringify({ t: t, p: CONTAR_PAGINA, id: id || "", o: o || "" });
      if (navigator.sendBeacon && navigator.sendBeacon(contarURL, corpo)) return;
      if (window.fetch) fetch(contarURL, { method: "POST", body: corpo, keepalive: true, credentials: "omit", headers: { "Content-Type": "text/plain" } }).catch(function () { /* ok */ });
    } catch (e) { /* a contagem nunca atrapalha o site */ }
  }
  LV.contar = contar;
  function idDoEndereco() {
    var m = /^#p=(.+)$/.exec(location.hash);
    if (!m) return "";
    try { return decodeURIComponent(m[1]); } catch (e) { return m[1]; }
  }
  function produtoExiste(id) { return !!(id && BC && BC.porId(id)); }
  // Seções com id que viram o lugar do clique (as outras: "secao")
  var LUGAR_SECAO = { ipem: "ipem", faq: "faq", recursos: "recursos", informatica: "informatica", fichas: "fichas", ajuda: "assunto", canais: "canais", fiscal: "sat" };
  function lugar(a) {
    var c = function (s) { return a.closest(s); }, el;
    if ((el = c("[data-contar-o]"))) return [el.getAttribute("data-contar-o"), el.getAttribute("data-contar-id") || ""];
    if (c("[data-drawer]")) { var id = idDoEndereco(); return produtoExiste(id) ? ["ficha", id] : ["naoachou", ""]; }
    if ((el = c(".spec[data-id]"))) return produtoExiste(el.getAttribute("data-id")) ? ["cartao", el.getAttribute("data-id")] : ["secao", ""];
    if (c("[data-float]")) return ["flutuante", ""];
    if (c(".hdr")) return ["topo", ""];
    if (c("[data-menu]")) return ["menu", ""];
    if (c(".ftr")) return ["rodape", ""];
    if (c(".kit, [data-kits]")) return ["kit", ""];
    if (c(".pn-hero, .phero")) return ["hero", ""];
    if (c(".ask")) return ["chamada", ""];
    if (c(".pn-panel, #painel")) return ["quadros", ""];
    if (c(".empty, [data-cta]")) return ["naoachou", ""];
    if (c(".cat__sat, .sat")) return ["sat", ""];
    var sec = c("section[id]");
    return [sec && LUGAR_SECAO[sec.id] || "secao", ""];
  }
  function contagem() {
    if (!CONTAR_PAGINA) return;
    // Página vista: depois da carga, quando o navegador estiver livre (ou ao sair antes disso)
    var foi = false;
    var vista = function () { if (!foi) { foi = true; contar("pagina"); } };
    var folga = function () { if (window.requestIdleCallback) requestIdleCallback(vista, { timeout: 3000 }); else setTimeout(vista, 1200); };
    if (document.prerendering) document.addEventListener("prerenderingchange", folga, { once: true });   // pré-carregada: só conta se abrir
    else if (document.readyState === "complete") folga();
    else window.addEventListener("load", folga, { once: true });
    window.addEventListener("pagehide", function () { if (!document.prerendering) vista(); }, { once: true });
    // Produto aberto no catálogo: o painel de detalhes ([data-dw-body]) recebe o produto do endereço (#p=…).
    // Uma vez por produto em cada visita à página (voltar e avançar não somam de novo).
    var dw = $("[data-drawer] [data-dw-body]");
    if (dw && window.MutationObserver) new MutationObserver(function () {
      var id = idDoEndereco();
      if (abertos[id] || !produtoExiste(id)) return;
      abertos[id] = true;
      contar("produto", id);
    }).observe(dw, { childList: true });
    // Cliques no WhatsApp e em "Ligar" (também com o botão do meio, que abre em outra aba)
    var clique = function (e) {
      if (e.type === "auxclick" && e.button !== 1) return;
      var a = e.target && e.target.closest ? e.target.closest("a[href]") : null;
      if (!a) return;
      var href = a.getAttribute("href") || "";
      var t = /^tel:/i.test(href) ? "ligar" : /(?:\/\/|\.)(?:wa\.me|whatsapp\.com)\//i.test(href) ? "whatsapp" : "";
      if (!t) return;
      var l = lugar(a);
      contar(t, l[1], l[0]);
    };
    document.addEventListener("click", clique, true);
    document.addEventListener("auxclick", clique, true);
  }

  /* ------------------------------------------------------------------
     Início
     ------------------------------------------------------------------ */
  try { contagem(); } catch (e) { /* a contagem nunca atrapalha o site */ }
  if (BC) {
    try { aviso(); } catch (e) { if (window.console) console.warn(e); }   // antes do resto: a página já nasce com a faixa
    fill();
    status(); setInterval(status, 60000);
    renderProdutos();
    hidratarKits();
    try { jsonLD(); } catch (e) { /* dados estruturados: ficam como vieram */ }
  }
  navAtivo();
  // Números decorativos do menu ("01 Início") e das fichas ("Nº 01") ficam fora do nome acessível
  $$(".menu__list a .mono, .spec__head").forEach(function (el) { el.setAttribute("aria-hidden", "true"); });
  setupTickers();
  setupAccordion();
  setupKits();
  if (BC) setupForm();
  floatOff();
  onScrollBasic();
  // As páginas registram seus ganchos; a animação começa depois que todos os scripts
  // (inclusive os do CDN, que vêm depois dos locais) rodaram.
  var iniciou = false;
  function alvoDoHash() {
    var h = location.hash;
    if (h.length < 2 || h.indexOf("#p=") === 0) return null;
    try { return document.getElementById(decodeURIComponent(h.slice(1))); } catch (e) { return null; }
  }
  /* Chegada por âncora com as fontes ainda chegando (link compartilhado, nova aba, Google): a rolagem
     inicial acontece com as fontes de reserva; quando as fontes da web chegam, a página encolhe
     (~1000 px) e a ancoragem de rolagem do navegador não acompanha o alvo, que acaba sob o
     cabeçalho. Enquanto a pessoa não rolar nem tocar na página, realinha o alvo depois de
     document.fonts.ready e do "load" (depois do LV.refresh, registrado antes em motion()) e a cada
     mudança de altura até a carga terminar. Ao primeiro gesto (roda, toque, clique, tecla), solta. */
  function fixarAncora(alvo) {
    var solto = false, fontes = false, carregou = document.readyState === "complete", ro = null, fimT = 0;
    var gestos = ["wheel", "touchstart", "pointerdown", "mousedown", "keydown"];
    var fonts = document.fonts;
    function soltar() {
      if (solto) return; solto = true; clearTimeout(fimT);
      gestos.forEach(function (n) { window.removeEventListener(n, soltar, true); });
      if (ro) ro.disconnect();
      if (fonts && fonts.removeEventListener) fonts.removeEventListener("loadingdone", alinhar);
    }
    function alinhar() {
      if (solto || !alvo.getClientRects().length) return;
      var y = yAlvo(alvo), atual = window.scrollY || doc.scrollTop || 0;
      if (LV.lenis) {
        LV.lenis.resize();
        if (Math.abs(y - atual) > 1 || LV.lenis.isScrolling === "smooth") LV.lenis.scrollTo(y, { immediate: true, force: true });
      } else if (Math.abs(y - atual) > 1) window.scrollTo(0, y);
    }
    function talvezFim() {
      if (solto || !fontes || !carregou) return;
      // Ainda há fonte a caminho (pedida depois do 1º "ready"): espera a próxima
      if (fonts && fonts.status === "loading") { fontes = false; fonts.ready.then(aoFontes); return; }
      clearTimeout(fimT);
      fimT = setTimeout(function () { alinhar(); soltar(); }, 700);   // última olhada e solta
    }
    function aoFontes() { fontes = true; alinhar(); talvezFim(); }
    gestos.forEach(function (n) { window.addEventListener(n, soltar, { capture: true, passive: true }); });
    if (fonts && fonts.ready) {
      fonts.ready.then(aoFontes);
      if (fonts.addEventListener) fonts.addEventListener("loadingdone", alinhar);
    } else fontes = true;
    if (carregou) talvezFim();
    else window.addEventListener("load", function () { carregou = true; alinhar(); talvezFim(); }, { once: true });
    if (window.ResizeObserver) { ro = new ResizeObserver(function () { alinhar(); }); ro.observe(document.body); }
  }
  function iniciar(visto) {
    if (iniciou) return; iniciou = true;
    // Chegada por âncora (pagina.html#secao): o navegador só rola até ela no fim da carga. Rola já
    // (mesma conta do LV.irPara) para medir o que vai estar na tela.
    var alvo = alvoDoHash(), fixar = false;
    if (alvo) {
      if ((window.scrollY || doc.scrollTop || 0) < 2) { window.scrollTo(0, yAlvo(alvo)); fixar = true; }
      else {
        // O próprio navegador já levou até a âncora (com ou sem o scroll-padding do catálogo)?
        // Recarregar no meio da página (posição restaurada longe do alvo) não entra aqui.
        var d = alvo.getBoundingClientRect().top - (parseFloat(getComputedStyle(alvo).scrollMarginTop) || 0);
        var pad = parseFloat(getComputedStyle(doc).scrollPaddingTop) || 0;
        fixar = Math.abs(d) < 3 || Math.abs(d - pad) < 3;
      }
    }
    // "manter": o que já está na tela não some para animar de novo (ver LV.jaVisivel)
    manter = !!visto || !!alvo || LV.pronto() || (window.scrollY || doc.scrollTop || 0) > 2;
    if (LV.beforeMotion) LV.beforeMotion();
    if (LV.motionOK()) motion();
    else { doc.classList.remove("is-loading"); if (hasGSAP()) motion(); }
    // Depois de motion(): o Lenis já existe e o LV.refresh das fontes/"load" roda antes do realinhamento
    if (fixar) fixarAncora(alvo);
  }
  document.addEventListener("DOMContentLoaded", function () {
    var visto = pintou || !!window.LV_REVELOU;   // algum quadro já foi pintado antes do GSAP
    if (!window.LV_REVELOU && "onpagereveal" in window && !document.hidden) {
      // O 1º quadro ainda não saiu: o "pagereveal" vem logo antes dele e diz se a página chegou
      // pela transição entre páginas ("via-vt", marcado no <head>). Começar ali evita a corrida
      // em que a abertura toca do zero e a transição mostra o hero pela metade.
      window.addEventListener("pagereveal", function () { iniciar(false); }, { once: true });
      setTimeout(function () { iniciar(visto); }, 400);   // rede de segurança (aoRevelar cobre o resto)
    } else iniciar(visto);
  });
})();
