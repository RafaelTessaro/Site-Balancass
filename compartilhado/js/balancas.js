/* =====================================================================
   Balanças.com — funções compartilhadas pelos designs
   Carregue DEPOIS de dados/empresa.js e dados/produtos.js:
     <script src="../compartilhado/dados/empresa.js"></script>
     <script src="../compartilhado/dados/produtos.js"></script>
     <script src="../compartilhado/js/balancas.js"></script>
   Não precisa editar este arquivo para adicionar produtos.
   ===================================================================== */
(function () {
  "use strict";

  var E = window.EMPRESA || {};
  var C = window.CATEGORIAS || [];
  var TODOS = (window.PRODUTOS || []).filter(function (p) { return p && p.visivel !== false; });

  // Pasta "compartilhado/" descoberta a partir do endereço deste script
  var base = (function () {
    var s = document.currentScript && document.currentScript.src;
    return s ? s.replace(/js\/balancas\.js(\?.*)?$/, "") : "../compartilhado/";
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
