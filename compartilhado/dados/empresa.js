/* =====================================================================
   DADOS DA EMPRESA
   ---------------------------------------------------------------------
   Tudo o que aparece no site sobre a empresa (telefone, WhatsApp,
   endereço, horários, marcas, clientes) vem daqui.
   Altere aqui e o site inteiro atualiza sozinho.
   Atenção: mantenha as aspas "" e as vírgulas no final de cada linha.
   ===================================================================== */
window.EMPRESA = {
  nome: "Balanças.com",
  slogan: "Automação Comercial",
  frase: "A medida certa para o seu negócio",
  ipem: "Oficina autorizada pelo IPEM-SP",
  razaoSocial: "Fabio de Godoy Lima Ltda",
  cnpj: "12.403.843/0001-18",
  desde: 2010,            // ano de abertura (CNPJ)
  anosExperiencia: 25,    // experiência do fundador no ramo

  // Avaliações no Google (atualize de vez em quando)
  google: {
    nota: "5,0",
    avaliacoes: 56,
    link: "https://maps.google.com/?cid=12422356528564941318"
  },

  // Telefone exibido e telefone para o botão "Ligar"
  telefone: "(19) 3023-9050",
  telefoneLink: "+551930239050",

  // WhatsApp: só números, com 55 + DDD + número (sem espaços)
  whatsapp: "551930239050",
  whatsappMensagemPadrao: "Olá! Vim pelo site da Balanças.com e gostaria de atendimento.",

  email: "balancas.com@gmail.com",

  endereco: {
    rua: "Rua 13, 650",
    referencia: "Entre as Avenidas 9 e 11",
    bairro: "Boa Morte",
    cidade: "Rio Claro",
    uf: "SP",
    cep: "13500-120"
  },
  // Link para abrir a loja no Google Maps (sem iframe = sem cookies)
  mapa: "https://maps.google.com/?cid=12422356528564941318",

  horarios: [
    { dias: "Segunda a sexta", horas: "8h às 11h · 13h às 18h" },
    { dias: "Sábado", horas: "8h às 12h" },
    { dias: "Domingo e feriados", horas: "Fechado" }
  ],

  redes: {
    facebook: "https://www.facebook.com/balancas.comrc/",
    instagram: ""
  },

  // Outras marcas/produtos da empresa
  produtosProprios: [
    { nome: "BC System", descricao: "Sistema de gestão e PDV com emissão de NFC-e e NF-e." },
    { nome: "BC Fichas", descricao: "Locação de terminais emissores de fichas para eventos.", site: "http://bcfichas.com.br" }
  ],

  // Marcas com que trabalhamos (texto — aparecem na faixa de marcas)
  marcas: [
    "Toledo", "Balmak", "Elgin", "Bematech", "Epson", "Gertec", "Tanca",
    "Zebra", "Argox", "Filizola", "Urano", "Welmy", "Micheletti",
    "Daruma", "Diebold", "NHS", "SMS", "APC"
  ],

  // Clientes (logos ficam em compartilhado/img/clientes/)
  clientes: [
    { nome: "Brasil Frios Supermercados", logo: "brasil-frios.webp" },
    { nome: "Supermercados Camargo", logo: "camargo.webp" },
    { nome: "Enxuto", logo: "enxuto.webp" },
    { nome: "Examine Supermercados", logo: "examine.webp" },
    { nome: "Mercado Qualidade", logo: "qualidade.webp" },
    { nome: "Quitandão Supermercados", logo: "quitandao.webp" }
  ],

  // Depoimentos reais de clientes (do site antigo do BC System)
  depoimentos: [
    { texto: "Sistema fácil de usar, isso ajuda muito. Suporte sempre que preciso, estão à disposição!", nome: "William", empresa: "Padaria Padoka" },
    { texto: "Instalei na inauguração da minha empresa e fomos muito bem assessoradas. Atendimento nota 10. Excelente.", nome: "Lilian", empresa: "Plus Pet Store" },
    { texto: "O sistema possui um completo relatório de vendas que facilita bastante o fechamento e o controle financeiro.", nome: "Juliana", empresa: "Padaria Phenix" },
    { texto: "O sistema é bem simples de operar, o atendimento do suporte é ótimo, estou muito satisfeito, nota 10!", nome: "Alessandro", empresa: "Peixaria 14" },
    { texto: "Até o momento fui atendida em todas as minhas necessidades, principalmente no recurso que eu mais precisava: o controle de consignação.", nome: "Tatiane", empresa: "Cantinho da Mamãe" },
    { texto: "Sistema muito eficiente, prático para a geração de carnê e emissão de cupom fiscal.", nome: "Adenilza", empresa: "Ideal Confecções" }
  ],

  equipe: [
    { nome: "Fábio Godoy", cargo: "Fundador e diretor", texto: "Mais de 25 anos de experiência em balanças e automação comercial." },
    { nome: "Alexandre Silva", cargo: "Técnico de balanças", texto: "Departamento técnico de balanças comerciais e atendimento aos clientes." },
    { nome: "Victor Gomes", cargo: "Técnico de balanças", texto: "Departamento técnico de balanças comerciais e atendimento aos clientes." },
    { nome: "Tamires Oliveira", cargo: "Administrativo e vendas", texto: "Responsável pelo administrativo, vendas e financeiro." },
    { nome: "Rafael Tessaro", cargo: "Sistemas e suporte", texto: "Suporte técnico em sistemas, implantação e treinamento aos clientes." }
  ]
};
