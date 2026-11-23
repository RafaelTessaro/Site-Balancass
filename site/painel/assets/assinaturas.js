/* =====================================================================
   Painel Balanças.com — tela "Assinaturas de e-mail" (index.php?a=assinaturas)
   O gerador de assinaturas do kit, com o mesmo comportamento, adaptado ao painel:
   - o endereço público das imagens, a lista delas, quem está logado e o cargo
     dessa pessoa na equipe chegam do PHP nos atributos data- do <body>
     (lib/visoes-assinaturas.php; o nome da pasta fica só em lib/assinaturas.php);
   - o que a pessoa digita fica lembrado neste navegador, separado por usuário
     do painel (duas pessoas no mesmo computador não veem os dados uma da outra);
     o que se digita depois de "Limpar para outra pessoa" não fica guardado;
   - a prévia usa as imagens do próprio servidor; a assinatura copiada ou baixada
     usa SEMPRE o endereço público (https://balancass.com/<pasta>/…).
   Sem nada inline: a página só aceita scripts do próprio painel (CSP).
   ===================================================================== */
(function () {
  'use strict';

  var CORPO = document.body;

  /* ==========================================================================
     DADOS DA EMPRESA
     Mudou o endereço ou o site? Altere aqui e todas as assinaturas geradas a
     partir de agora passam a sair com o dado novo. O lugar das imagens NÃO se
     muda aqui: vem do painel (PASTA_ASSINATURAS em lib/assinaturas.php).
     "telefone" e "email" são o que já vem preenchido no formulário: cada pessoa
     pode trocar pelo seu, e o navegador lembra o que ela digitou por último.
     O endereço também pode ser trocado na tela, em "Opções"; o daqui é o padrão,
     que vale para quem não mexeu naquele campo. Ao mudar o endereço aqui, troque
     junto "cep" e "mapa" (o link da ficha da empresa no Google Maps): sem isso,
     o mapa e o CEP continuam sendo os do lugar antigo.
     ========================================================================== */
  var E = {
    nome: 'Balanças.com',
    descritor: 'Automação Comercial',
    slogan: 'A medida certa para o seu negócio.',
    selo: 'Oficina autorizada pelo IPEM-SP',
    site: 'www.balancass.com',
    siteUrl: 'https://www.balancass.com',
    imagens: CORPO.getAttribute('data-imagens') || '',   /* endereço público da pasta de imagens, terminando em "/" (vem do painel) */
    telefone: '(19) 3023-9050',
    ddd: '19',
    email: 'balancas.com@gmail.com',
    endereco1: 'Rua 13, 650 – Boa Morte',
    endereco2: 'Rio Claro/SP',
    cep: 'CEP 13500-120',
    mapa: 'https://maps.google.com/?cid=12422356528564941318',
    facebook: 'https://www.facebook.com/balancas.comrc/'
  };

  /* A prévia usa as mesmas imagens, buscadas no próprio servidor do painel (../<pasta>/): aparece certa em qualquer
     endereço do painel. O que vai para o e-mail (copiar, baixar) usa sempre E.imagens, o endereço público. */
  var PASTA_LOCAL = CORPO.getAttribute('data-imagens-local') || E.imagens;
  /* Todas as imagens da pasta pública (a lista vem do painel). O teste da etapa 03 confere uma por uma. */
  var ARQUIVOS = (CORPO.getAttribute('data-arquivos') || '').split(/\s+/).filter(Boolean);
  var LIMITE_GMAIL = 10000;
  /* Quem está logado no painel: o formulário abre com o nome dele e o cargo dele na equipe do site */
  var USUARIO = (CORPO.getAttribute('data-usuario') || '').toLowerCase();
  var PADRAO_NOME = CORPO.getAttribute('data-nome') || '';
  var PADRAO_CARGO = CORPO.getAttribute('data-cargo') || '';
  /* Uma chave por usuário do painel: no mesmo computador, cada pessoa vê só o que ela digitou */
  var CHAVE = 'balancas-assinatura-v5:' + USUARIO;

  /* Cores da marca, fontes e peças que funcionam em qualquer programa de e-mail */
  var VERDE = '#019F42', VERDE_TEXTO = '#007A35', VOLT = '#C6FF3D', INK = '#0A0A0A', PAPEL = '#F4F4F0';
  var CINZA = '#55554F', CINZA_CLARO = '#8A8A85', TEXTO_CLARO = '#CFCFC9', FIO_ESCURO = '#494949';
  var F = 'font-family:Arial,Helvetica,sans-serif;';
  var T = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse">';
  var TAB = '<table role="presentation" cellpadding="0" cellspacing="0" border="0"';
  var Z = 'font-size:0;line-height:0';
  var LARGURA = 500;   /* largura do modelo com imagens; em telas menores a assinatura encolhe sozinha */

  function $(id) { return document.getElementById(id); }
  function limpar(s) { return String(s || '').replace(/\s+/g, ' ').trim(); }
  function maiusc(s) { return s.toLocaleUpperCase('pt-BR'); }

  /* Escapa HTML e troca acentos por códigos numéricos: a assinatura fica imune a erro de codificação. */
  function esc(s) {
    return String(s)
      .replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; })
      .replace(/[^\x00-\x7F]/gu, function (c) { return '&#' + c.codePointAt(0) + ';'; });
  }
  function semQuebra(html) { return html.replace(/ /g, '&nbsp;'); }

  /* "(19) 3023-9050" -> "551930239050". O que vem depois de uma letra (ramal, "r. 21") fica só no texto.
     Devolve '' quando o número está incompleto ou há dois números no campo: a linha sai sem link, em vez de discar errado. */
  function e164(tel) {
    var bruto = String(tel).trim();
    if (/\d\W*\b(e|ou)\b/i.test(bruto)) return '';   /* dois números ligados por "e" ou "ou" */
    var n = bruto.split(/[a-z]/i)[0].replace(/\D/g, '');
    if (n.length < 8) n = bruto.replace(/\D/g, '');   /* a letra não era de ramal (ex.: "0xx19 3023-9050") */
    if (bruto.charAt(0) === '+' && n.slice(0, 2) !== '55') return /^\d{8,15}$/.test(n) ? n : '';   /* número de outro país, escrito com + */
    n = n.replace(/^0+/, '');
    if (/^[38]00\d{6,7}$/.test(n)) return '55' + n;   /* 0800 e 0300 não levam DDD */
    if (n.length === 8 || n.length === 9) n = E.ddd + n;
    if (n.length === 10 || n.length === 11) n = '55' + n;
    return /^55(\d\d(9\d{8}|[2-5]\d{7})|[38]00\d{6,7})$/.test(n) ? n : '';
  }
  function link(href, conteudo, cor, negrito) {
    return '<a href="' + esc(href) + '" style="color:' + cor + ';text-decoration:none' + (negrito ? ';font-weight:bold' : '') + '">' + conteudo + '</a>';
  }
  /* altVisivel: estilo do texto alternativo, que aparece no lugar da imagem quando o programa de e-mail a bloqueia */
  function img(base, arquivo, w, h, alt, altVisivel) {
    return '<img src="' + esc(base + arquivo) + '" width="' + w + '" height="' + h + '" border="0" alt="' + esc(alt) + '" style="display:block;border:0' + (altVisivel ? ';' + F + altVisivel : '') + '">';
  }
  function imgLink(href, base, arquivo, w, h, alt, altVisivel) {
    return '<a href="' + esc(href) + '" style="text-decoration:none">' + img(base, arquivo, w, h, alt, altVisivel) + '</a>';
  }
  /* Número (já no formato do link) que abre o WhatsApp nos atalhos: o telefone, se estiver marcado; senão o celular */
  function numeroWhats(d) {
    return (d.telWa && e164(d.tel)) || (d.celWa && e164(d.cel)) || '';
  }
  /* Endereço que vai na assinatura: o que a pessoa escreveu em "Opções" ou, se ela não mexeu, o padrão da empresa */
  var ENDERECO_PADRAO = limpar(E.endereco1 + ', ' + E.endereco2);
  function enderecoDe(d) { return d.enderecoTexto || ENDERECO_PADRAO; }
  function enderecoMudou(d) { return enderecoDe(d) !== ENDERECO_PADRAO; }
  /* O mesmo endereço escrito de outro jeito (maiúsculas, hífen no lugar do travessão) continua sendo o da empresa */
  function mesmoLugar(d) {
    function chave(s) { return limpar(s).toLocaleLowerCase('pt-BR').replace(/[–—]/g, '-'); }
    return chave(enderecoDe(d)) === chave(ENDERECO_PADRAO);
  }
  /* Link do mapa: a ficha da empresa no Google; com outro endereço, uma busca do Google Maps pelo que foi escrito */
  function linkDoMapa(d) {
    return mesmoLugar(d) ? E.mapa : 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(enderecoDe(d));
  }
  /* O endereço é dividido nos pontos em que a linha pode quebrar: depois de um travessão e depois de uma vírgula
     que não venha antes do número (assim "Rua 13, 650" nunca se separa). Um pedaço que cabe em uma linha de celular
     fica com as palavras coladas; um mais largo quebra entre as palavras, mas mantém juntos o número e o travessão.
     Códigos com hífen (CEP, "M-29") nunca são partidos. A largura é medida com a própria fonte da assinatura. */
  function enderecoHtml(texto) {
    regua.font = '12px Arial, Helvetica, sans-serif';
    return esc(texto).replace(/(&#8211;|&#8212;|-) /g, '$1\n').replace(/, (?!\d)/g, ',\n').split('\n').map(function (pedaco) {
      var cru = pedaco.replace(/&#(\d+);/g, function (m, n) { return String.fromCodePoint(n); }).replace(/&\w+;/g, 'x');
      return regua.measureText(cru).width <= 112 ? semQuebra(pedaco)
        : pedaco.replace(/, (?=\d)/g, ',&nbsp;').replace(/ (&#8211;|&#8212;|-)$/, '&nbsp;$1');
    }).join(' ').replace(/[A-Za-z0-9]+-\d+/g, '<span style="white-space:nowrap">$&</span>');
  }
  /* "A MEDIDA CERTA PARA O SEU NEGÓCIO.": só quebra de linha no meio da frase */
  function slogan() {
    var p = maiusc(E.slogan).split(' ');
    var meio = Math.ceil((p.length - 1) / 2);
    return esc(p.slice(0, meio).join(' ')) + ' ' + semQuebra(esc(p.slice(meio).join(' ')));
  }

  /* ---------- Linhas de contato (ícone + texto) ----------
     Regras que mantêm tudo no lugar no Outlook: as células de uma mesma linha têm sempre o mesmo
     espaço em cima e embaixo, e nenhuma célula combina largura fixa com espaço lateral. */
  function contatos(d, base, P) {
    var primeiro = true, etiquetaUsada = false;
    function linha(icone, alt, href, conteudo, estilo) {
      var figura = img(base, 'ic-' + icone + '.png', 24, 18, alt);
      return '<tr><td width="24" valign="top" style="padding:2px 0 1px;' + Z + '">' + (href ? '<a href="' + esc(href) + '">' + figura + '</a>' : figura) + '</td>'
        + '<td valign="middle" style="padding:2px 0 1px 6px;' + F + estilo + '">' + conteudo + '</td></tr>';
    }
    function fone(tel, whats, celular) {
      var n = e164(tel);
      var destaque = primeiro;   /* o primeiro número da assinatura é o principal */
      primeiro = false;
      whats = whats && !!n;      /* número incompleto: sai só o texto, sem link e sem WhatsApp */
      /* o que vem depois do número (ramal, "r. 21") fica fora do link e em letra comum: assim pode descer de linha no celular */
      var antes = tel.split(/[a-z]/i)[0];
      var ramal = n && antes.length < tel.length && antes.replace(/\D/g, '').length >= 8 ? tel.slice(antes.length) : '';
      var h = n ? '<a href="tel:+' + n + '" style="color:' + P.texto + ';text-decoration:none;white-space:nowrap">' + semQuebra(esc(ramal ? antes.trim() : tel)) + '</a>'
        + (ramal ? ' <span style="font-size:' + P.corpo + 'px;font-weight:normal">' + semQuebra(esc(ramal)) + '</span>' : '') : semQuebra(esc(tel));
      if (whats && !etiquetaUsada) {   /* a etiqueta aparece uma vez só; nos outros números, o ícone já indica o WhatsApp */
        etiquetaUsada = true;
        h += '&nbsp; <a href="https://wa.me/' + n + '" style="background-color:' + P.tagFundo + ';color:' + P.tagTexto
          + ';font-size:11px;line-height:12px;font-weight:bold;letter-spacing:1px;text-decoration:none;white-space:nowrap;vertical-align:2px">&nbsp;WHATSAPP&nbsp;</a>';
      }
      /* Telefone fixo: o ícone é o de telefone e liga para o número; se ele também é WhatsApp, a etiqueta ao lado abre a conversa.
         Celular com WhatsApp: o próprio ícone é o do WhatsApp. */
      var iconeWhats = whats && celular;
      return linha(iconeWhats ? 'whatsapp' : celular ? 'cel' : 'tel', iconeWhats ? 'WhatsApp' : celular ? 'Celular' : 'Telefone',
        !n ? '' : iconeWhats ? 'https://wa.me/' + n : 'tel:+' + n, h,
        (destaque ? 'font-size:16px;font-weight:bold;' : 'font-size:' + P.corpo + 'px;') + 'line-height:18px;color:' + P.texto);
    }
    var comum = 'font-size:' + P.corpo + 'px;line-height:18px;color:' + P.texto2;
    var h = '';
    if (d.tel) h += fone(d.tel, d.telWa, false);
    if (d.cel) h += fone(d.cel, d.celWa, true);
    /* e-mail: quebra antes do @ se faltar espaço; endereços muito longos podem quebrar em qualquer ponto em vez de estourar a largura */
    if (d.email) h += linha('email', 'E-mail', 'mailto:' + d.email, link('mailto:' + d.email, esc(d.email).replace('@', '<wbr>@'), P.texto2), comum + ';word-break:break-word');
    h += linha('site', 'Site', E.siteUrl, link(E.siteUrl, esc(E.site), P.texto2), comum);
    if (d.endereco) {
      /* endereço: a primeira quebra é depois da vírgula; se ainda faltar espaço, depois do travessão.
         Um endereço trocado na tela pode ter qualquer formato, então ganha também a quebra de segurança do e-mail. */
      var mapa = linkDoMapa(d);
      h += linha('local', 'Como chegar', mapa, link(mapa, enderecoHtml(enderecoDe(d)), P.suave),
        'font-size:12px;line-height:16px;color:' + P.suave + (enderecoMudou(d) ? ';word-break:break-word' : ''));
    }
    return h;
  }

  /* A coluna da marca tem sempre duas peças: uma presa em cima e outra presa embaixo. Quando os dados
     ocupam mais altura (nome longo, tela de celular), a sobra abre entre as duas, na cor do fundo. */
  function celulaMarca(largura, valign, conteudo) {
    return '<td width="' + largura + '" valign="' + valign + '" bgcolor="' + INK + '" style="background-color:' + INK + ';' + Z + '">' + conteudo + '</td>';
  }
  /* Tamanho do nome. Se ele não cabe em uma linha na tela de um celular comum, sai até 2 px menor para caber.
     A medida é feita com a própria fonte da assinatura (Arial em negrito, maiúsculas).
     espaco = espaço entre letras; util = largura livre para o nome em um celular de 375 pontos. */
  var regua = document.createElement('canvas').getContext('2d');
  function tamanhoDoNome(nome, normal, espaco, util) {
    var texto = maiusc(nome);
    for (var extra = 0; extra <= 15; extra += 15) {   /* primeiro tenta caber em 343 px (iPhone de 375 pontos); depois, em 358 px (iPhone de 390) */
      for (var t = normal; t >= normal - 2; t--) {
        regua.font = 'bold ' + t + 'px Arial, Helvetica, sans-serif';
        if (regua.measureText(texto).width + texto.length * espaco <= util + extra) return t;
      }
    }
    return texto.length > 27 ? normal - 2 : normal;   /* não cabe no celular de nenhum jeito: decide pelo computador */
  }
  var CELULAR = 343;   /* largura da mensagem em um iPhone de 375 pontos, usada na conta acima */
  var ALT_CLARO = 'font-size:10px;line-height:12px;color:' + PAPEL;
  /* largura fixa também no estilo: é o que o Outlook clássico usa quando o Windows está com zoom de tela */
  var ABRE = TAB + ' width="' + LARGURA + '" style="width:' + LARGURA + 'px;max-width:100%">';

  /* ---------- Modelo "Dois blocos": painel escuro + logo em placa clara com corte inclinado + faixa verde ---------- */
  function doisBlocos(d, base) {
    var P = { texto: PAPEL, texto2: TEXTO_CLARO, suave: CINZA_CLARO, corpo: 12, tagFundo: VOLT, tagTexto: INK };
    var COLUNA = 132;   /* largura da placa clara (imagem plaqueta.png) */
    var whats = numeroWhats(d);
    function atalho(href, arquivo, alt) {
      return '<td width="36" style="padding:10px 0 12px;' + Z + '">' + imgLink(href, base, arquivo, 36, 28, alt, ALT_CLARO) + '</td>';
    }
    return ABRE
      + '<tr>'
      + '<td rowspan="2" valign="top" bgcolor="' + INK + '" style="background-color:' + INK + ';padding:0 6px 0 12px">'
      + TAB + ' width="100%">'
      + '<tr><td colspan="2" style="padding:9px 0 ' + (d.cargo ? 0 : 9) + 'px;' + F + 'font-size:' + tamanhoDoNome(d.nome, 19, .3, CELULAR - COLUNA - 18) + 'px;line-height:22px;font-weight:bold;letter-spacing:.3px;color:' + PAPEL + '">' + esc(maiusc(d.nome)) + '</td></tr>'
      + (d.cargo ? '<tr><td colspan="2" style="padding:2px 0 9px;' + F + 'font-size:11px;line-height:14px;font-weight:bold;letter-spacing:1.5px;color:' + VERDE + '">' + esc(maiusc(d.cargo)) + '</td></tr>' : '')
      + '<tr><td height="8" style="border-top:2px solid ' + VERDE + ';font-size:1px;line-height:1px">&nbsp;</td><td height="8" style="border-top:2px solid ' + FIO_ESCURO + ';font-size:1px;line-height:1px">&nbsp;</td></tr>'
      + contatos(d, base, P)
      + '<tr><td colspan="2" height="11" style="font-size:1px;line-height:1px">&nbsp;</td></tr>'
      + '</table></td>'
      + celulaMarca(COLUNA, 'top', imgLink(E.siteUrl, base, 'plaqueta.png', COLUNA, 106, E.nome, 'font-size:13px;line-height:18px;font-weight:bold;color:' + PAPEL))
      + '</tr>'
      + '<tr>' + celulaMarca(COLUNA, 'bottom', TAB + ' width="100%"><tr><td style="' + Z + '">&nbsp;</td>'
        + (whats ? atalho('https://wa.me/' + whats, 'bt-whats.png', 'Whats') : '')
        + atalho(E.facebook, 'bt-facebook.png', 'Face')
        + atalho(linkDoMapa(d), 'bt-mapa.png', 'Mapa')
        + '<td width="8" style="' + Z + '">&nbsp;</td></tr></table>') + '</tr>'
      /* faixa em verde escuro com letra clara: mantém o contraste também quando o celular está em tema escuro */
      + '<tr><td colspan="2" bgcolor="' + VERDE_TEXTO + '" style="background-color:' + VERDE_TEXTO + ';padding:5px 14px 5px 12px;' + F + 'font-size:10px;line-height:15px;letter-spacing:.3px;color:' + PAPEL + '">'
      + '<b style="font-size:11px;letter-spacing:.5px">' + slogan() + '</b> &nbsp;&nbsp; <span style="white-space:nowrap">' + semQuebra(esc(maiusc(E.selo))) + '</span></td></tr>'
      + '</table>';
  }

  /* ---------- Modelo "Compacta": só texto, para respostas e encaminhamentos ---------- */
  function compacta(d) {
    var ponto = ' <span style="color:#8A8A85">&#183;</span> ';
    var l1 = '<span style="font-size:14px;font-weight:bold;color:' + INK + '">' + esc(d.nome) + '</span>';
    if (d.cargo) l1 += ponto + '<span style="font-weight:bold;color:' + VERDE_TEXTO + '">' + esc(d.cargo) + '</span>';
    var whats = numeroWhats(d);
    /* número com link para ligar; o que abre o WhatsApp ganha também o link "WhatsApp". Número incompleto sai só como texto. */
    function fone(tel) {
      var n = e164(tel), t = semQuebra(esc(tel));
      if (!n) return t;
      return '<a href="tel:+' + n + '" style="color:' + CINZA + ';text-decoration:none;white-space:nowrap">' + t + '</a>'
        + (n === whats ? '&nbsp;' + link('https://wa.me/' + n, 'WhatsApp', VERDE_TEXTO, true) : '');
    }
    var p = [link(E.siteUrl, esc(E.nome), INK, true)];
    if (d.tel) p.push(fone(d.tel));
    if (d.cel) p.push(fone(d.cel));
    p.push(link(E.siteUrl, esc(E.site), CINZA));
    /* a barra verde é uma célula colorida (e não uma borda) para não apagar no tema escuro do celular */
    return T
      + '<tr><td width="3" bgcolor="' + VERDE + '" style="background-color:' + VERDE + ';' + Z + '">&nbsp;</td>'
      + '<td style="padding:1px 0 1px 11px;' + F + 'font-size:12px;line-height:19px;color:' + CINZA + '">'
      + l1 + '<br>' + p.join(ponto)
      + '</td></tr></table>';
  }

  /* Lista de modelos: para criar outro, acrescente uma linha aqui.
     O primeiro da lista é o padrão da equipe: já vem marcado e o arquivo baixado dele sai sem sufixo no nome. */
  var MODELOS = [
    { id: 'blocos', nome: 'Dois blocos', dica: 'Padrão da equipe, para e-mails novos. Dados em painel escuro, logo em placa clara', imagens: true, gerar: doisBlocos },
    { id: 'compacta', nome: 'Compacta', dica: 'Só texto: nome, cargo, telefones e site. Para respostas e encaminhamentos', imagens: false, gerar: function (d) { return compacta(d); } }
  ];
  function modelo(id) {
    for (var i = 0; i < MODELOS.length; i++) if (MODELOS[i].id === id) return MODELOS[i];
    return MODELOS[0];
  }

  /* ---------- Versão em texto simples ---------- */
  function emTexto(d) {
    if (d.modelo === 'compacta') {
      var p = [E.nome];
      if (d.tel) p.push(d.tel);
      if (d.cel) p.push(d.cel);
      p.push(E.site);
      return d.nome + (d.cargo ? ' · ' + d.cargo : '') + '\n' + p.join(' · ');
    }
    var t = [d.nome];
    if (d.cargo) t.push(d.cargo);
    t.push(E.nome + ' – ' + E.descritor, '');
    if (d.tel) t.push((d.telWa ? 'Fone/WhatsApp: ' : 'Fone: ') + d.tel);
    if (d.cel) t.push((d.celWa ? 'Celular/WhatsApp: ' : 'Celular: ') + d.cel);
    if (d.email) t.push('E-mail: ' + d.email);
    t.push('Site: ' + E.site);
    if (d.endereco) t.push(enderecoMudou(d) ? enderecoDe(d) : ENDERECO_PADRAO + ' – ' + E.cep);
    return t.join('\n');
  }

  function construir(d) {
    var m = modelo(d.modelo);
    return {
      html: m.gerar(d, E.imagens),        /* o que vai para o e-mail: imagens no endereço público */
      previa: m.gerar(d, noAr === true ? E.imagens : PASTA_LOCAL),   /* o que aparece aqui: as imagens do site, se já estiverem publicadas; senão, as da pasta local */
      texto: emTexto(d),
      imagens: m.imagens,
      dados: d
    };
  }

  /* ---------- Tela ---------- */
  var quadro = $('previa');
  var atual = null;
  var noAr = null;   /* null = ainda não testado; true/false = resultado do teste das imagens no site */
  var faltando = [], rodadaDeTeste = 0;

  $('modelos').innerHTML = MODELOS.map(function (m, i) {
    return '<label class="seg__opt"><input type="radio" name="modelo" value="' + m.id + '"' + (i === 0 ? ' checked' : '') + '>'
      + '<span class="seg__box"><b>' + m.nome + '</b><small>' + m.dica + '</small></span></label>';
  }).join('');

  function ler() {
    return {
      nome: limpar($('f-nome').value),
      cargo: limpar($('f-cargo').value),
      email: limpar($('f-email').value),
      tel: limpar($('f-tel').value),
      telWa: $('f-tel-wa').checked,
      cel: limpar($('f-cel').value),
      celWa: $('f-cel-wa').checked,
      modelo: document.querySelector('input[name="modelo"]:checked').value,
      endereco: $('o-endereco').checked,
      enderecoTexto: limpar($('f-endereco').value)
    };
  }

  function ajustarAltura() {
    var doc = quadro.contentDocument;
    if (doc && doc.body) quadro.style.height = doc.body.offsetHeight + 'px';
  }

  function pintarStatus() {
    var usa = atual ? atual.imagens : modelo(ler().modelo).imagens;
    var topo = $('status-topo'), pub = $('status-pub');
    var classe = noAr === true ? 'status is-ok' : noAr === false ? 'status is-off' : 'status';
    topo.hidden = !usa;
    topo.className = classe;
    pub.className = classe;
    var etapa = ' · <a href="#publicar">veja a etapa 03, abaixo</a>';
    if (noAr === true) {
      topo.textContent = 'Imagens publicadas no site · pode instalar';
      pub.textContent = 'As ' + ARQUIVOS.length + ' imagens estão publicadas no site. Pode copiar e instalar.';
    } else if (noAr === false && faltando.length < ARQUIVOS.length) {
      /* o <span> mantém a frase e o link em um bloco só; os nomes de arquivo aparecem em minúsculas, como são de verdade */
      topo.innerHTML = '<span>' + (faltando.length === 1 ? 'Falta 1 imagem' : 'Faltam ' + faltando.length + ' imagens') + ' no site' + etapa + '</span>';
      pub.innerHTML = '<span>' + (faltando.length === 1 ? 'Falta 1 imagem no site: ' : 'Faltam ' + faltando.length + ' das ' + ARQUIVOS.length + ' imagens no site: ')
        + '<span class="nt">' + faltando.join(', ') + '</span>. Avise quem cuida do site: para resolver, é só enviar a atualização de novo (as imagens vão junto).</span>';
    } else if (noAr === false) {
      topo.innerHTML = '<span>Imagens fora do site' + etapa + '</span>';
      pub.textContent = 'As imagens não abriram no endereço do site. Confira se o computador está conectado à internet e clique em Testar agora de novo; se continuar, avise quem cuida do site. Enquanto isso, a assinatura chega sem imagens.';
    } else {
      topo.textContent = 'Verificando as imagens no site…';
      pub.textContent = 'Verificando as imagens no site…';
    }
  }
  /* Confere no site todas as imagens que as assinaturas usam, uma por uma */
  function testarImagens() {
    var rodada = ++rodadaDeTeste, pendentes = ARQUIVOS.length, falhas = [];
    noAr = null;
    pintarStatus();
    ARQUIVOS.forEach(function (arquivo) {
      var im = new Image();
      function fim(ok) {
        if (rodada !== rodadaDeTeste) return;   /* já começou outro teste */
        if (!ok) falhas.push(arquivo);
        if (--pendentes) return;
        faltando = falhas.sort();
        noAr = faltando.length === 0;
        atualizar();   /* refaz a prévia: com tudo publicado, ela passa a usar as imagens do site */
      }
      im.onload = function () { fim(im.naturalWidth > 0); };
      im.onerror = function () { fim(false); };
      im.src = E.imagens + arquivo + '?t=' + rodada + '-' + Date.now();
    });
  }

  function atualizar() {
    var d = ler();
    $('g-opcoes').hidden = !modelo(d.modelo).imagens;
    /* avisos embaixo dos campos: telefone que não vira link e e-mail incompleto */
    $('h-tel').hidden = !d.tel || !!e164(d.tel);
    $('h-cel').hidden = !d.cel || !!e164(d.cel);
    $('h-email').hidden = !d.email || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(d.email);
    /* endereço trocado: mostra o aviso e o atalho para voltar ao padrão */
    $('b-end-padrao').hidden = !enderecoMudou(d);
    $('h-endereco').hidden = mesmoLugar(d);
    atual = d.nome ? construir(d) : null;

    var corpo = atual
      ? '<div id="assinatura">' + atual.previa + '</div>'
      : '<p style="margin:0;color:#8a8a85">Preencha o nome para ver a assinatura.</p>';
    var doc = quadro.contentDocument;
    doc.open();
    doc.write('<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><base target="_blank"></head>'
      + '<body style="margin:0;padding:20px 18px 24px;background:#fff;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:21px;color:#222">'
      + '<p style="margin:0 0 14px">Ol&#225;, tudo bem?</p>'
      + '<p style="margin:0 0 14px">Segue o or&#231;amento que combinamos. Qualquer d&#250;vida, &#233; s&#243; chamar.</p>'
      + '<p style="margin:0 0 18px">Atenciosamente,</p>'
      + corpo + '</body></html>');
    doc.close();
    ajustarAltura();

    var n = atual ? atual.html.length : 0;
    var cont = $('contagem');
    cont.innerHTML = '<b>' + n.toLocaleString('pt-BR') + '</b> de ' + LIMITE_GMAIL.toLocaleString('pt-BR') + ' caracteres · limite do Gmail';
    cont.classList.toggle('is-over', n > LIMITE_GMAIL);
    /* o passo a passo do Outlook clássico mostra o nome exato do arquivo que será baixado */
    var arquivo = atual ? nomeDoArquivo(d) : 'assinatura-seu-nome.htm';
    Array.prototype.forEach.call(document.querySelectorAll('[data-arquivo]'), function (el) {
      el.textContent = el.getAttribute('data-arquivo') === 'nome' ? arquivo.replace(/\.htm$/, '') : arquivo;
    });
    pintarStatus();
  }

  var relogio;
  function avisar(msg) {
    var t = $('toast');
    t.textContent = msg;
    t.classList.add('on');
    clearTimeout(relogio);
    relogio = setTimeout(function () { t.classList.remove('on'); }, 5200);
  }

  function exigirNome() {
    if (atual) return true;
    avisar('Preencha o nome antes de continuar.');
    $('f-nome').focus();
    return false;
  }

  /* Plano B: dispara uma cópia comum (como Ctrl + C) nesta página e entrega nela a assinatura final,
     em HTML e em texto. Não passa por seleção de tela, então o HTML sai igual ao do plano A. */
  function copiarPorEvento(html, texto) {
    var entregue = false;
    function aoCopiar(ev) {
      ev.clipboardData.setData('text/html', html);
      ev.clipboardData.setData('text/plain', texto);
      ev.preventDefault();
      entregue = true;
    }
    var isca = document.createElement('span');   /* alguns navegadores só copiam se houver algo selecionado */
    isca.textContent = '.';
    isca.style.cssText = 'position:fixed;left:-9999px;top:0';
    document.body.appendChild(isca);
    var sel = window.getSelection(), faixa = document.createRange();
    faixa.selectNodeContents(isca);
    sel.removeAllRanges();
    sel.addRange(faixa);
    document.addEventListener('copy', aoCopiar);
    try { document.execCommand('copy'); } catch (e) { entregue = false; }
    document.removeEventListener('copy', aoCopiar);
    sel.removeAllRanges();
    document.body.removeChild(isca);
    return entregue;
  }
  /* Último recurso: deixa a assinatura final (imagens no endereço público) selecionada na prévia, pronta para o Ctrl + C */
  function selecionarNaPrevia() {
    var doc = quadro.contentDocument, alvo = doc && doc.getElementById('assinatura');
    if (!alvo) return;
    alvo.innerHTML = atual.html;
    var faixa = doc.createRange();
    faixa.selectNodeContents(alvo);
    var sel = quadro.contentWindow.getSelection();
    sel.removeAllRanges();
    sel.addRange(faixa);
    quadro.contentWindow.focus();
  }
  function copiarFormatado(html, texto) {
    if (navigator.clipboard && window.ClipboardItem) {
      try {
        return navigator.clipboard.write([new ClipboardItem({
          'text/html': new Blob([html], { type: 'text/html' }),
          'text/plain': new Blob([texto], { type: 'text/plain' })
        })]).then(function () { return true; }, function () { return copiarPorEvento(html, texto); });
      } catch (e) { /* cai no plano B */ }
    }
    return Promise.resolve(copiarPorEvento(html, texto));
  }
  function copiarTexto(texto) {
    function planoB() {
      try {
        var a = document.createElement('textarea');
        a.value = texto;
        a.setAttribute('readonly', '');
        a.style.cssText = 'position:fixed;left:-9999px;top:0';
        document.body.appendChild(a);
        a.select();
        var ok = document.execCommand('copy');
        document.body.removeChild(a);
        return ok;
      } catch (e) { return false; }
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(texto).then(function () { return true; }, planoB);
    }
    return Promise.resolve(planoB());
  }

  function nomeDoArquivo(d) {
    var base = d.nome.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    var sufixo = d.modelo === MODELOS[0].id ? '' : '-' + d.modelo;
    return 'assinatura-' + (base || 'email') + sufixo + '.htm';
  }
  function comoDocumento(html, nome) {
    return '<!DOCTYPE html>\n<html lang="pt-BR">\n<head>\n<meta charset="utf-8">\n<title>Assinatura - ' + esc(nome) + '</title>\n</head>\n<body>\n' + html + '\n</body>\n</html>\n';
  }

  $('b-copiar').addEventListener('click', function () {
    if (!exigirNome()) return;
    /* imagens que faltam no site e que esta assinatura usa (o modelo Compacta não usa nenhuma) */
    var html = atual.html, todas = noAr === false && faltando.length === ARQUIVOS.length;
    var faltamNela = noAr === false ? faltando.filter(function (arquivo) { return html.indexOf(E.imagens + arquivo) >= 0; }) : [];
    copiarFormatado(atual.html, atual.texto).then(function (ok) {
      if (!ok) {
        selecionarNaPrevia();
        return avisar('Não consegui copiar sozinho. Deixei a assinatura selecionada na prévia: aperte Ctrl + C e cole no seu e-mail.');
      }
      avisar(!faltamNela.length ? 'Assinatura copiada. Agora cole com Ctrl + V no seu e-mail.'
        : 'Assinatura copiada. Atenção: ' + (todas ? 'as imagens não abriram no site (veja a etapa 03); até isso ser resolvido, ela chega sem imagens.'
          : faltamNela.length === 1 ? '1 imagem não abriu no site (veja a etapa 03); até isso ser resolvido, ela não aparece no e-mail.'
          : faltamNela.length + ' imagens não abriram no site (veja a etapa 03); até isso ser resolvido, elas não aparecem no e-mail.'));
    });
  });
  $('b-texto').addEventListener('click', function () {
    if (!exigirNome()) return;
    copiarTexto(atual.texto).then(function (ok) {
      avisar(ok ? 'Texto da assinatura copiado.' : 'Não consegui copiar o texto neste navegador.');
    });
  });
  $('b-baixar').addEventListener('click', function () {
    if (!exigirNome()) return;
    var arquivo = nomeDoArquivo(atual.dados);
    var url = URL.createObjectURL(new Blob([comoDocumento(atual.html, atual.dados.nome)], { type: 'text/html;charset=utf-8' }));
    var a = document.createElement('a');
    a.href = url;
    a.download = arquivo;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(function () { URL.revokeObjectURL(url); }, 2000);
    avisar('Arquivo baixado: ' + arquivo);
  });
  /* "Limpar para outra pessoa": a assinatura de quem não tem usuário no painel. O que se digita para essa pessoa NÃO
     fica guardado (a chave é de quem está logado): "Voltar aos meus dados", ou abrir a página de novo, traz de volta
     o nome do painel, o cargo da equipe e o que esta pessoa já tinha digitado, como estavam. */
  var outraPessoa = false;
  $('b-limpar').addEventListener('click', function () {
    outraPessoa = true;
    $('b-meus').hidden = false;
    $('f-nome').value = '';
    $('f-cargo').value = '';
    $('f-cel').value = '';
    $('f-email').value = E.email;
    $('f-tel').value = E.telefone;
    $('f-tel-wa').checked = true;
    $('f-cel-wa').checked = true;
    document.querySelector('input[name="modelo"]').checked = true;   /* a próxima pessoa começa pelo modelo padrão */
    atualizar();
    $('f-nome').focus();
  });
  $('b-meus').addEventListener('click', function () {
    outraPessoa = false;
    $('b-meus').hidden = true;
    meusDados();
    atualizar();
    $('f-nome').focus();
  });
  $('b-testar').addEventListener('click', testarImagens);
  $('b-end-padrao').addEventListener('click', function () {
    $('f-endereco').value = ENDERECO_PADRAO;
    atualizar();
    salvar();
    $('f-endereco').focus();
  });

  /* Lembra o que foi digitado neste navegador, na chave do usuário do painel (CHAVE). Só grava quando a pessoa
     muda alguma coisa: quem só abriu a página continua com o nome e o cargo que vêm do painel. */
  var CAMPOS = ['f-nome', 'f-cargo', 'f-email', 'f-tel', 'f-cel'];
  var CAIXAS = ['f-tel-wa', 'f-cel-wa', 'o-endereco'];
  function salvar() {
    if (outraPessoa) return;   /* dados de outra pessoa (depois de "Limpar para outra pessoa") não vão para a chave de quem está logado */
    try {
      var s = { modelo: document.querySelector('input[name="modelo"]:checked').value };
      CAMPOS.forEach(function (id) { s[id] = $(id).value; });
      CAIXAS.forEach(function (id) { s[id] = $(id).checked; });
      /* nome e cargo iguais aos do painel não são guardados: se o nome do usuário ou o cargo na equipe mudar, vale o novo */
      if (limpar(s['f-nome']) === limpar(PADRAO_NOME)) delete s['f-nome'];
      if (limpar(s['f-cargo']) === limpar(PADRAO_CARGO)) delete s['f-cargo'];
      /* o endereço só é guardado se a pessoa trocou: quem não mexeu continua seguindo o padrão deste arquivo, mesmo que ele mude */
      var end = limpar($('f-endereco').value);
      s['f-endereco'] = end && end !== ENDERECO_PADRAO ? end : '';
      localStorage.setItem(CHAVE, JSON.stringify(s));
    } catch (e) { /* sem armazenamento: segue sem lembrar */ }
  }
  function restaurar() {
    try {
      var s = JSON.parse(localStorage.getItem(CHAVE));
      if (!s) return;
      CAMPOS.forEach(function (id) { if (typeof s[id] === 'string') $(id).value = s[id]; });
      CAIXAS.forEach(function (id) { if (typeof s[id] === 'boolean') $(id).checked = s[id]; });
      if (typeof s['f-endereco'] === 'string' && limpar(s['f-endereco'])) $('f-endereco').value = s['f-endereco'];
      var radios = document.querySelectorAll('input[name="modelo"]');
      for (var i = 0; i < radios.length; i++) if (radios[i].value === s.modelo) radios[i].checked = true;
    } catch (e) { /* dado inválido: fica com o padrão */ }
  }

  /* Abas do passo a passo */
  var abas = Array.prototype.slice.call(document.querySelectorAll('.tab'));
  function abrirAba(aba, focar) {
    abas.forEach(function (a) {
      var ativa = a === aba;
      a.setAttribute('aria-selected', ativa ? 'true' : 'false');
      a.tabIndex = ativa ? 0 : -1;
      $(a.getAttribute('aria-controls')).hidden = !ativa;
    });
    if (focar) aba.focus();
  }
  abas.forEach(function (aba, i) {
    aba.addEventListener('click', function () { abrirAba(aba, false); });
    aba.addEventListener('keydown', function (ev) {
      var passo = ev.key === 'ArrowRight' ? 1 : ev.key === 'ArrowLeft' ? -1 : 0;
      if (!passo) return;
      ev.preventDefault();
      abrirAba(abas[(i + passo + abas.length) % abas.length], true);
    });
  });

  var form = $('form');
  form.addEventListener('submit', function (ev) { ev.preventDefault(); });
  function mudou() { atualizar(); salvar(); }
  form.addEventListener('input', mudou);
  form.addEventListener('change', mudou);
  window.addEventListener('resize', ajustarAltura);
  quadro.addEventListener('load', ajustarAltura);

  /* Acesso para conferência e ferramentas internas (gera a assinatura sem passar pela tela) */
  window.BalancasAssinatura = { construir: construir, modelos: MODELOS, empresa: E, comoDocumento: comoDocumento, nomeDoArquivo: nomeDoArquivo, e164: e164 };

  /* Os dados de quem está logado: o nome dele no painel, o cargo dele na equipe do site e o telefone, o e-mail e o
     endereço da empresa; restaurar() põe por cima o que esta pessoa já digitou neste navegador. O formulário abre
     assim, e "Voltar aos meus dados" volta a isso. */
  function meusDados() {
    $('f-nome').value = PADRAO_NOME;
    $('f-cargo').value = PADRAO_CARGO;
    $('f-tel').value = E.telefone;
    $('f-email').value = E.email;
    $('f-cel').value = '';
    $('f-tel-wa').checked = $('f-cel-wa').checked = $('o-endereco').checked = true;
    document.querySelector('input[name="modelo"]').checked = true;
    $('f-endereco').value = ENDERECO_PADRAO;
    restaurar();
  }
  $('f-endereco').placeholder = ENDERECO_PADRAO;
  $('rodape').textContent = [E.nome, E.descritor, E.endereco1.split(' – ')[0], E.endereco2, E.telefone].join(' · ');
  meusDados();
  atualizar();
  testarImagens();
})();
