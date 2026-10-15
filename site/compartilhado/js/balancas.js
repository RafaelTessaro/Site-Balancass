/* =====================================================================
   Balanças.com — funções compartilhadas pelos designs
   Carregue DEPOIS dos dados, nesta ordem:
     <script src="compartilhado/dados/empresa.js"></script>    valores originais
     <script src="compartilhado/dados/ajustes.js"></script>    o que o painel mudou (área Empresa)
     <script src="compartilhado/dados/clientes.js"></script>   logos (área Clientes)
     <script src="compartilhado/dados/produtos.js"></script>
     <script src="compartilhado/js/balancas.js"></script>
   Os ajustes do painel valem no lugar dos valores de empresa.js, no
   MESMO objeto (window.EMPRESA = BC.empresa). Sem ajustes.js, ou com ele
   quebrado, o site mostra empresa.js. A capa escolhida no painel fica em
   EMPRESA.capa (só existe quando o ajustes.js tem uma capa válida).
   Não precisa editar este arquivo para adicionar produtos, clientes
   nem mudar os dados da empresa.
   ===================================================================== */
(function () {
  "use strict";

  var E = window.EMPRESA || {};

  /* ------------------------------------------------------------------
     Ajustes do painel (área Empresa): dados/ajustes.js → window.AJUSTES
     Toda parte é conferida aqui: tipo errado, link que não é http(s)
     ou item estranho numa lista fica de fora e vale o de empresa.js.
     Um ajustes.js quebrado nunca derruba o site (nada aqui lança erro).
     ------------------------------------------------------------------ */
  function ehObj(o) { return !!o && typeof o === "object" && !Array.isArray(o); }
  // Texto aparado, sem caracteres de controle e com até "max" caracteres; senão null
  function txt(v, max) {
    if (typeof v !== "string") return null;
    v = v.replace(/[\u0000-\u001f\u007f]+/g, " ").replace(/\s+/g, " ").trim();
    return v && v.length <= (max || 300) ? v : null;
  }
  function link(v) {
    var s = txt(v, 600);
    return s && /^https?:\/\/[^\s"'<>\\]+$/i.test(s) ? s : null;
  }
  function inteiro(n, min, max) { return typeof n === "number" && n % 1 === 0 && n >= min && n <= max; }
  function data(v, re) { return typeof v === "string" && re.test(v) ? v : null; }
  var RE_DIA = /^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/;   // AAAA-MM-DD
  var RE_MMDD = /^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/;         // MM-DD

  /* Grade de horários: {"0": [[ini, fim], …], …, "6": […]} (0 = domingo), minutos desde 0h,
     até 3 faixas por dia, em ordem e sem sobrepor. Retorna uma cópia limpa ou null (inválida). */
  function gradeValida(g) {
    if (!ehObj(g)) return null;
    var out = {};
    for (var d = 0; d < 7; d++) {
      var dia = g[d];
      if (!Array.isArray(dia) || dia.length > 3) return null;
      var fim = -1, lista = [];
      for (var i = 0; i < dia.length; i++) {
        var f = dia[i];
        if (!Array.isArray(f) || f.length !== 2 || !inteiro(f[0], 0, 1440) || !inteiro(f[1], 0, 1440)) return null;
        if (f[0] >= f[1] || f[0] < fim) return null;
        fim = f[1];
        lista.push([f[0], f[1]]);
      }
      out[d] = lista;
    }
    return out;
  }

  // Lista de objetos: cada item passa por "item" (que retorna o objeto limpo ou null); vazia = null
  function lista(v, max, item) {
    if (!Array.isArray(v)) return null;
    var out = [];
    for (var i = 0; i < v.length && out.length < max; i++) {
      var x = ehObj(v[i]) || typeof v[i] === "string" ? item(v[i]) : null;
      if (x) out.push(x);
    }
    return out.length ? out : null;
  }

  var SECOES = {
    contato: function (a) {
      var tel = txt(a.telefone, 40);
      if (tel) {
        E.telefone = tel;
        // Sem telefoneLink válido, sai do próprio telefone: +55 + DDD + número
        var dig = tel.replace(/\D/g, "").replace(/^55(?=\d{10,11}$)/, "");
        if (/^\d{10,11}$/.test(dig)) E.telefoneLink = "+55" + dig;
      }
      if (typeof a.telefoneLink === "string" && /^\+\d{10,14}$/.test(a.telefoneLink)) E.telefoneLink = a.telefoneLink;
      if (typeof a.whatsapp === "string" && /^55\d{10,11}$/.test(a.whatsapp)) E.whatsapp = a.whatsapp;
      var msg = txt(a.whatsappMensagemPadrao, 400);
      if (msg) E.whatsappMensagemPadrao = msg;
      var em = txt(a.email, 120);
      if (em && /^[^\s@<>"'()\\,;:]+@[^\s@<>"'()\\,;:]+\.[a-z]{2,}$/i.test(em)) E.email = em;
    },
    endereco: function (a) {
      var end = ehObj(E.endereco) ? E.endereco : (E.endereco = {});
      ["rua", "referencia", "bairro", "cidade", "uf", "cep"].forEach(function (k) {
        var v = txt(a[k], 120);
        if (k === "uf" && v && !/^[A-Z]{2}$/.test(v)) v = null;
        if (k === "cep" && v && !/^\d{5}-?\d{3}$/.test(v)) v = null;
        if (v) end[k] = v;
        else if (k === "referencia" && a[k] === "") end[k] = "";   // o painel apagou a referência
      });
    },
    mapa: function (a) { var u = link(a); if (u) E.mapa = u; },
    horario: function (a) {
      var linhas = lista(a.linhas, 10, function (h) {
        var d = txt(h.dias, 80), hs = txt(h.horas, 120);
        return d && hs ? { dias: d, horas: hs } : null;
      });
      if (linhas) E.horarios = linhas;
      var g = gradeValida(a.grade);
      if (g) E.grade = g;
    },
    feriados: function (a) {
      var MOVEIS = [-48, -47, -46, 60];   // segunda e terça de Carnaval, Cinzas, Corpus Christi
      E.feriados = {
        municipais: lista(a.municipais, 40, function (f) {
          var d = data(f.data, RE_MMDD);
          return d ? { data: d, nome: txt(f.nome, 80) || "" } : null;
        }) || [],
        moveis: Array.isArray(a.moveis) ? a.moveis.filter(function (n, i, l) { return MOVEIS.indexOf(n) !== -1 && l.indexOf(n) === i; }) : [],
        datas: lista(a.datas, 120, function (f) {
          var d = data(f.data, RE_DIA);
          return d ? { data: d, nome: txt(f.nome, 80) || "" } : null;
        }) || []
      };
    },
    aviso: function (a) {
      var t = txt(a.texto, 200);
      // O painel desligou o aviso e apagou o texto: desligado, mesmo que empresa.js tenha um aviso
      if (!t) { if (a.ativo === false) E.aviso = { ativo: false, texto: "", inicio: "", fim: "", tom: "info" }; return; }
      E.aviso = {
        ativo: a.ativo === true, texto: t,
        inicio: data(a.inicio, RE_DIA) || "", fim: data(a.fim, RE_DIA) || "",
        tom: a.tom === "alerta" ? "alerta" : "info"
      };
    },
    google: function (a) {
      var g = ehObj(E.google) ? E.google : (E.google = {});
      if (typeof a.nota === "string" && /^[1-5],\d$/.test(a.nota) && parseFloat(a.nota.replace(",", ".")) <= 5) g.nota = a.nota;
      if (inteiro(a.avaliacoes, 0, 1e6)) g.avaliacoes = a.avaliacoes;
      var u = link(a.link);
      if (u) g.link = u;
    },
    redes: function (a) {
      var r = ehObj(E.redes) ? E.redes : (E.redes = {});
      ["facebook", "instagram"].forEach(function (k) {
        if (a[k] === "") r[k] = "";                 // vazio = o link some do site
        else { var u = link(a[k]); if (u) r[k] = u; }
      });
    },
    equipe: function (a) {
      var l = lista(a, 12, function (p) {
        var n = txt(p.nome, 80);
        return n ? { nome: n, cargo: txt(p.cargo, 80) || "", texto: txt(p.texto, 300) || "" } : null;
      });
      if (l) E.equipe = l;
    },
    depoimentos: function (a) {
      var l = lista(a, 12, function (d) {
        var t = txt(d.texto, 400), n = txt(d.nome, 80);
        return t && n ? { texto: t, nome: n, empresa: txt(d.empresa, 80) || "" } : null;
      });
      if (l) E.depoimentos = l;
    },
    marcas: function (a) {
      var l = lista(a, 40, function (m) { return txt(m, 40); });
      if (l) E.marcas = l;
    },
    /* Capa do site (área Empresa → Capa do site): produto + até 4 etiquetas com o ponto em % da foto.
       Qualquer parte fora do formato invalida a capa inteira: o site mostra a Prix 7E do HTML.
       Se o produto existe, está visível e tem recorte, quem confere é o js/home-v3.js. */
    capa: function (a) {
      var id = a.produto;
      if (typeof id !== "string" || id.length > 80 || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(id)) return;
      var pos = function (n) { return typeof n === "number" && isFinite(n) && n >= 0 && n <= 100; };
      var opc = function (v, max) { return v == null || v === "" ? "" : txt(v, max); };   // vazio = "", inválido = null
      var pts = [];
      if (a.pontos != null && !Array.isArray(a.pontos)) return;
      var lis = a.pontos || [];
      if (lis.length > 4) return;
      for (var i = 0; i < lis.length; i++) {
        var p = lis[i];
        if (!ehObj(p)) return;
        var r = txt(p.rotulo, 14), v = txt(p.valor, 24), d = opc(p.detalhe, 32);
        if (!r || !v || d === null || !pos(p.x) || !pos(p.y)) return;
        pts.push({ rotulo: r, valor: v, detalhe: d, x: Math.round(p.x * 10) / 10, y: Math.round(p.y * 10) / 10 });
      }
      var texto = opc(a.texto, 48);
      if (texto === null) return;
      var w = inteiro(a.largura, 1, 20000) ? a.largura : 0, h = inteiro(a.altura, 1, 20000) ? a.altura : 0;
      E.capa = { produto: id, pontos: pts, texto: texto, largura: w && h ? w : 0, altura: w && h ? h : 0 };
    }
  };
  var AJ = window.AJUSTES, aplicados = [];
  if (ehObj(AJ)) {
    Object.keys(SECOES).forEach(function (k) {
      var v = AJ[k];
      if (v == null) return;
      // equipe/depoimentos/marcas são listas; mapa é texto; o resto, objetos
      var ok = k === "mapa" ? typeof v === "string" : /^(equipe|depoimentos|marcas)$/.test(k) ? Array.isArray(v) : ehObj(v);
      if (!ok) return;
      try { SECOES[k](v); aplicados.push(k); } catch (e) { /* parte com problema: vale o de empresa.js */ }
    });
  }

  // Clientes: a lista de dados/clientes.js (gerada pelo painel, área Clientes) vale no lugar
  // de EMPRESA.clientes — no MESMO objeto, então window.EMPRESA.clientes e BC.empresa.clientes
  // enxergam a lista nova. Itens sem logo (ou com nome de arquivo estranho) ficam de fora.
  // Sem o arquivo, continua valendo a lista de empresa.js.
  if (Array.isArray(window.CLIENTES)) {
    E.clientes = window.CLIENTES.filter(function (c) {
      return !!c && typeof c.logo === "string" && /^[\w][\w.-]*$/.test(c.logo) && c.logo.indexOf("..") === -1;
    }).map(function (c) {
      return { nome: c.nome == null ? "" : String(c.nome), logo: c.logo };
    });
  }
  var C = window.CATEGORIAS || [];
  var TODOS = (window.PRODUTOS || []).filter(function (p) { return p && p.visivel !== false; });

  // Pasta "compartilhado/" descoberta a partir do endereço deste script
  var base = (function () {
    var s = document.currentScript && document.currentScript.src;
    return s ? s.replace(/js\/balancas\.js(\?.*)?$/, "") : "compartilhado/";
  })();

  var ESTOQUE = {
    disponivel: { rotulo: "Pronta entrega", classe: "disponivel", ordem: 0 },
    consulte: { rotulo: "Consulte disponibilidade", classe: "consulte", ordem: 1 },
    encomenda: { rotulo: "Sob encomenda", classe: "encomenda", ordem: 1 },
    esgotado: { rotulo: "Esgotado", classe: "esgotado", ordem: 2 }
  };
  var CONDICAO = { novo: "Novo", seminovo: "Seminovo" };

  // Fotos que já têm versão recortada (fundo transparente) em img/produtos-recorte/.
  // Produto novo sem recorte usa automaticamente a foto normal.
  // Se você criar um recorte para um produto novo, informe no produto:  recorte: "arquivo.png",
  var RECORTES = ["2098.webp","8217.webp","9094plus.webp","allmidia.webp","argox.webp","balmak-one.webp","balmak-orion2.webp","balmak.webp","bck30.webp","bematech-sat.webp","bk200f.webp","centrium-pc.webp","el4200.webp","elgin-i9.webp","elgin-smart.webp","epson-t20.webp","fatiador.webp","gavetabema.webp","gertec504.webp","gertecg2.webp","hospitalar.webp","l42pro.webp","menno.webp","mit.webp","mt720.webp","nobreak-apc.webp","nobreak-nhs.webp","one-pesadora.webp","prix3fit.webp","prix3plus.webp","prix4due.webp","prix4uno.webp","prix5.webp","quickscan.webp","sat-custom.webp","sat-jetway.webp","sat-tanca.webp","sko44.webp","tanca.webp","tec44.webp","tl120.webp","tl220.webp","tl900.webp","uni350.webp","w300.webp","zebra.webp"];

  function temRecorte(p) {
    return !!(p && (p.recorte || RECORTES.indexOf(p.imagem) !== -1));
  }

  function normaliza(s) {
    return (s == null ? "" : String(s))
      .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
      .toLowerCase().trim();
  }

  function whatsLink(msg) {
    return "https://wa.me/" + E.whatsapp + "?text=" + encodeURIComponent(msg || E.whatsappMensagemPadrao || "Olá!");
  }

  function msgProduto(p) {
    return "Olá! Vi no site da Balanças.com o produto *" + p.marca + " " + p.nome +
      "* e gostaria de saber preço e disponibilidade.";
  }

  function textoBusca(p) {
    return normaliza([p.nome, p.marca, p.subcategoria, p.resumo, p.descricao, categoriaNome(p.categoria)].join(" "));
  }

  function categoria(id) {
    for (var i = 0; i < C.length; i++) if (C[i].id === id) return C[i];
    return null;
  }
  function categoriaNome(id) { var c = categoria(id); return c ? c.nome : ""; }

  function ordenar(lista) {
    return lista.slice().sort(function (a, b) {
      var ea = (ESTOQUE[a.estoque] || ESTOQUE.consulte).ordem;
      var eb = (ESTOQUE[b.estoque] || ESTOQUE.consulte).ordem;
      if (ea !== eb) return ea - eb;
      if (!!b.destaque !== !!a.destaque) return b.destaque ? 1 : -1;
      return 0;
    });
  }

  /**
   * filtrar({ categoria, subcategoria, texto, somenteEstoque, condicao })
   * Todos os campos são opcionais. Retorna lista ordenada (em estoque primeiro).
   */
  function filtrar(f) {
    f = f || {};
    var termos = normaliza(f.texto).split(/\s+/).filter(Boolean);
    return ordenar(TODOS.filter(function (p) {
      if (f.categoria && f.categoria !== "todos" && p.categoria !== f.categoria) return false;
      if (f.subcategoria && p.subcategoria !== f.subcategoria) return false;
      if (f.somenteEstoque && p.estoque !== "disponivel") return false;
      if (f.condicao && p.condicao !== f.condicao) return false;
      if (termos.length) {
        var t = textoBusca(p);
        for (var i = 0; i < termos.length; i++) if (t.indexOf(termos[i]) === -1) return false;
      }
      return true;
    }));
  }

  window.BC = {
    empresa: E,
    ajustes: aplicados,          // partes de ajustes.js que valeram (ex.: ["contato", "aviso"])
    gradeValida: gradeValida,    // confere uma grade de horários (usada pelo "Aberto agora")
    categorias: C,
    produtos: ordenar(TODOS),
    base: base,

    // Links
    whatsLink: whatsLink,
    whatsProduto: function (p) { return whatsLink(msgProduto(p)); },
    msgProduto: msgProduto,
    telLink: function () { return "tel:" + E.telefoneLink; },
    emailLink: function (assunto) { return "mailto:" + E.email + (assunto ? "?subject=" + encodeURIComponent(assunto) : ""); },

    // Imagens: recorte = fundo transparente (bom para fundos escuros/coloridos)
    imgProduto: function (p, recorte) {
      if (recorte && temRecorte(p)) return base + "img/produtos-recorte/" + (p.recorte || p.imagem);
      return base + "img/produtos/" + p.imagem;
    },
    temRecorte: temRecorte,
    img: function (caminho) { return base + "img/" + caminho; },

    // Produtos
    filtrar: filtrar,
    destaques: function () { return ordenar(TODOS.filter(function (p) { return p.destaque; })); },
    porId: function (id) { for (var i = 0; i < TODOS.length; i++) if (TODOS[i].id === id) return TODOS[i]; return null; },
    contar: function (f) { return filtrar(f).length; },
    categoria: categoria,
    categoriaNome: categoriaNome,
    estoque: function (p) { return ESTOQUE[p.estoque] || ESTOQUE.consulte; },
    // true se algum produto estiver marcado como "disponivel" (pronta entrega).
    // Use para esconder o filtro "Pronta entrega" quando não houver nenhum.
    temProntaEntrega: function () { return TODOS.some(function (p) { return p.estoque === "disponivel"; }); },
    condicao: function (p) { return CONDICAO[p.condicao] || ""; },

    // Utilidades
    normaliza: normaliza,
    enderecoTexto: function () {
      var a = E.endereco || {};
      return a.rua + " – " + a.bairro + ", " + a.cidade + "/" + a.uf + (a.cep ? " · CEP " + a.cep : "");
    },
    referenciaEndereco: function () {
      return (E.endereco && E.endereco.referencia) || "";
    },
    anosDesde: function () { return new Date().getFullYear() - (E.desde || 2010); },
    escape: function (s) {
      return (s == null ? "" : String(s)).replace(/[&<>"']/g, function (c) {
        return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
      });
    }
  };
})();
