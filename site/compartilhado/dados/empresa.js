/* =====================================================================
   DADOS DA EMPRESA — Balanças.com (valores ORIGINAIS do site)
   ---------------------------------------------------------------------
   Tudo o que o site mostra sobre a empresa (telefone, WhatsApp,
   endereço, horários, marcas, equipe, depoimentos) vem daqui.

   O PAINEL MANDA POR CIMA: o que for mudado no painel, área "Empresa"
   (contato e WhatsApp, endereço e mapa, horário, feriados, aviso no
   site, Google e redes sociais, equipe, depoimentos e marcas), fica em
   compartilhado/dados/ajustes.js e vale no lugar do que está aqui.
   Os logos de clientes vêm de compartilhado/dados/clientes.js (área
   "Clientes" do painel). Este arquivo chega com as atualizações do
   site; o ajustes.js e o clientes.js, não (o que o painel gravou fica).

   Formato: JSON puro dentro de window.EMPRESA (aspas duplas em tudo,
   sem vírgula sobrando, sem comentários lá dentro). O painel lê este
   arquivo para mostrar os valores atuais; fora do formato, ele avisa.

   Campos:
     nome, slogan, frase   nome da empresa, linha abaixo do logo, frase
     ipem                  "Oficina autorizada pelo IPEM-SP"
     razaoSocial, cnpj     rodapé (© ano, razão social, CNPJ)
     desde                 ano de abertura (CNPJ) — "Desde 2010"
     anosExperiencia       experiência do fundador no ramo (anos)
     google                avaliações no Google: nota ("5,0", com vírgula),
                           avaliacoes (quantidade, número) e link
     telefone              telefone como aparece no site: "(19) 3023-9050"
     telefoneLink          o mesmo para o botão "Ligar": +55 DDD número
     whatsapp              só números: 55 + DDD + número (sem espaços)
     whatsappMensagemPadrao  mensagem que já vem escrita no WhatsApp
     email                 e-mail de contato
     endereco              rua (com número), referencia, bairro, cidade,
                           uf (2 letras) e cep (00000-000)
     mapa                  link da loja no Google Maps (sem iframe = sem cookies)
     horarios              linhas do quadro de horários: { dias, horas }.
                           O "Aberto agora" usa a grade de js/core.js; quem
                           muda o horário pelo painel muda as duas coisas.
     redes                 facebook e instagram (link completo; "" = não aparece)
     produtosProprios      outras marcas da empresa (BC System, BC Fichas)
     marcas                marcas com que trabalhamos (faixa de marcas)
     clientes              reserva da faixa de logos, só se faltar clientes.js
                           (logos em compartilhado/img/clientes/)
     depoimentos           depoimentos reais de clientes (do site antigo
                           do BC System): { texto, nome, empresa }
     equipe                { nome, cargo, texto } — a ordem é a do site
   ===================================================================== */
window.EMPRESA = {
  "nome": "Balanças.com",
  "slogan": "Automação Comercial",
  "frase": "A medida certa para o seu negócio",
  "ipem": "Oficina autorizada pelo IPEM-SP",
  "razaoSocial": "Fabio de Godoy Lima Ltda",
  "cnpj": "12.403.843/0001-18",
  "desde": 2010,
  "anosExperiencia": 25,

  "google": {
    "nota": "5,0",
    "avaliacoes": 56,
    "link": "https://maps.google.com/?cid=12422356528564941318"
  },

  "telefone": "(19) 3023-9050",
  "telefoneLink": "+551930239050",

  "whatsapp": "551930239050",
  "whatsappMensagemPadrao": "Olá! Vim pelo site da Balanças.com e gostaria de atendimento.",

  "email": "balancas.com@gmail.com",

  "endereco": {
    "rua": "Rua 13, 650",
    "referencia": "Entre as Avenidas 9 e 11",
    "bairro": "Boa Morte",
    "cidade": "Rio Claro",
    "uf": "SP",
    "cep": "13500-120"
  },
  "mapa": "https://maps.google.com/?cid=12422356528564941318",

  "horarios": [
    { "dias": "Segunda a sexta", "horas": "8h às 11h · 13h às 18h" },
    { "dias": "Sábado", "horas": "8h às 12h" },
    { "dias": "Domingo e feriados", "horas": "Fechado" }
  ],

  "redes": {
    "facebook": "https://www.facebook.com/balancas.comrc/",
    "instagram": ""
  },

  "produtosProprios": [
    { "nome": "BC System", "descricao": "Sistema de gestão e PDV com emissão de NFC-e e NF-e." },
    { "nome": "BC Fichas", "descricao": "Locação de terminais emissores de fichas para eventos.", "site": "http://bcfichas.com.br" }
  ],

  "marcas": [
    "Toledo", "Balmak", "Elgin", "Bematech", "Epson", "Gertec", "Tanca",
    "Zebra", "Argox", "Filizola", "Urano", "Welmy", "Micheletti",
    "Daruma", "Diebold", "NHS", "SMS", "APC"
  ],

  "clientes": [
    { "nome": "Brasil Frios Supermercados", "logo": "brasil-frios.webp" },
    { "nome": "Supermercados Camargo", "logo": "camargo.webp" },
    { "nome": "Examine Supermercados", "logo": "examine.webp" },
    { "nome": "Mercado Qualidade", "logo": "qualidade.webp" },
    { "nome": "Quitandão Supermercados", "logo": "quitandao.webp" }
  ],

  "depoimentos": [
    { "texto": "Sistema fácil de usar, isso ajuda muito. Suporte sempre que preciso, estão à disposição!", "nome": "William", "empresa": "Padaria Padoka" },
    { "texto": "Instalei na inauguração da minha empresa e fomos muito bem assessoradas. Atendimento nota 10. Excelente.", "nome": "Lilian", "empresa": "Plus Pet Store" },
    { "texto": "O sistema possui um completo relatório de vendas que facilita bastante o fechamento e o controle financeiro.", "nome": "Juliana", "empresa": "Padaria Phenix" },
    { "texto": "O sistema é bem simples de operar, o atendimento do suporte é ótimo, estou muito satisfeito, nota 10!", "nome": "Alessandro", "empresa": "Peixaria 14" },
    { "texto": "Até o momento fui atendida em todas as minhas necessidades, principalmente no recurso que eu mais precisava: o controle de consignação.", "nome": "Tatiane", "empresa": "Cantinho da Mamãe" },
    { "texto": "Sistema muito eficiente, prático para a geração de carnê e emissão de cupom fiscal.", "nome": "Adenilza", "empresa": "Ideal Confecções" }
  ],

  "equipe": [
    { "nome": "Fábio Godoy", "cargo": "Fundador e diretor", "texto": "Mais de 25 anos de experiência em balanças e automação comercial." },
    { "nome": "Maurício Ritter", "cargo": "Técnico de balanças", "texto": "Departamento técnico de balanças comerciais e atendimento aos clientes." },
    { "nome": "Lizi Ellen", "cargo": "Administrativo e vendas", "texto": "Responsável pelo administrativo, vendas e financeiro." },
    { "nome": "Rafael Tessaro", "cargo": "Sistemas e suporte", "texto": "Suporte técnico em sistemas, implantação e treinamento aos clientes." }
  ]
};
