/* =====================================================================
   CATÁLOGO DE PRODUTOS
   ---------------------------------------------------------------------
   Para ADICIONAR um produto: copie um bloco { ... }, cole no final da
   lista (antes do "];"), troque as informações e salve.
   Para TIRAR do site: apague o bloco, ou acrescente  visivel: false,

   Campos:
     id            → nome único, sem espaço nem acento (ex: "toledo-prix-4-uno")
     nome          → nome do produto
     marca         → fabricante
     categoria     → "balancas" | "automacao" | "informatica"
     subcategoria  → uma das subcategorias listadas em CATEGORIAS abaixo
     resumo        → frase curta que aparece no card
     descricao     → texto maior que aparece nos detalhes
     especificacoes→ lista de ["Nome", "Valor"] (pode deixar [])
     imagem        → nome do arquivo em compartilhado/img/produtos/
     estoque       → "consulte"   (Consulte disponibilidade — padrão)
                     "disponivel" (Pronta entrega)
                     "encomenda"  (Sob encomenda)
                     "esgotado"   (Esgotado)
     condicao      → "novo" | "seminovo"
     preco         → "" para não mostrar preço, ou ex: "R$ 2.490,00 à vista"
     destaque      → true para aparecer na página inicial
     visivel       → (opcional) false esconde o produto sem apagar

   Todos os produtos começam como "consulte" (Consulte disponibilidade).
   Se quiser destacar algo que está na loja, troque para "disponivel".
   ===================================================================== */

window.CATEGORIAS = [
  {
    id: "balancas",
    nome: "Balanças",
    descricao: "Comerciais, com impressora de etiquetas, industriais, de checkout e hospitalares.",
    subcategorias: ["Com impressora de etiquetas", "Computadoras", "Pesadoras", "Checkout", "Industriais e conferência", "Médico-hospitalar"]
  },
  {
    id: "automacao",
    nome: "Automação Comercial",
    descricao: "Impressoras de etiquetas, leitores, gavetas, terminais, fatiadores e mídia digital.",
    subcategorias: ["Impressoras de etiquetas", "Leitores de código de barras", "Gavetas de dinheiro", "Terminais de consulta", "Teclados e microterminais", "Fatiadores", "Tabelas digitais"]
  },
  {
    id: "informatica",
    nome: "Informática e PDV",
    descricao: "Computadores para PDV, impressoras de cupom, emissores fiscais e nobreaks.",
    subcategorias: ["Computadores", "Impressoras de cupom", "Emissores fiscais (SAT)", "Nobreaks"]
  }
];

window.PRODUTOS = [

  /* ------------------------- BALANÇAS ------------------------- */
  {
    id: "toledo-prix-4-uno",
    nome: "Prix 4 Uno",
    marca: "Toledo",
    categoria: "balancas",
    subcategoria: "Com impressora de etiquetas",
    resumo: "Balança etiquetadora compacta de 32 kg, com Ethernet e Wi-Fi integrados.",
    descricao: "Pesa, calcula o preço e imprime etiquetas com código de barras EAN-13 para leitura automática no caixa, tanto na venda direta no balcão quanto na pré-embalagem em bandejas. Usa etiquetas de 40 mm de largura, que reduzem o gasto com suprimentos, e guarda até 2.000 itens, cadastrados na própria balança ou pelo computador em rede. Indicada para pequenos e médios comércios.",
    especificacoes: [
      ["Capacidade", "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)"],
      ["Impressão", "Térmica, 8 pontos/mm, 60 mm/s"],
      ["Largura de etiqueta", "40 mm"],
      ["Memória", "Até 2.000 itens"],
      ["Conectividade", "Ethernet e Wi-Fi (WLAN); USB para backup e restauração de dados"]
    ],
    imagem: "prix4uno.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: true
  },
  {
    id: "toledo-prix-4-due",
    nome: "Prix 4 Due",
    marca: "Toledo",
    categoria: "balancas",
    subcategoria: "Com impressora de etiquetas",
    resumo: "Etiquetadora de 32 kg com 4.000 itens, 60 teclas rápidas e Ethernet/Wi-Fi.",
    descricao: "Pesa, calcula o preço e imprime etiquetas com código de barras para leitura no caixa, a até 70 mm/s. As 60 teclas rápidas acessam até 180 itens sem digitar código, e o prato de aço inoxidável tem área ampla, de 438 x 270 mm. Reconhece automaticamente o tamanho da etiqueta e opera tanto na venda direta quanto na pré-embalagem, atendendo desde estabelecimentos de pequeno porte até hipermercados.",
    especificacoes: [
      ["Capacidade", "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)"],
      ["Memória", "Até 4.000 itens"],
      ["Impressão", "Térmica, 8 pontos/mm, até 70 mm/s"],
      ["Display", "LCD alfanumérico de 2 linhas com backlight azul"],
      ["Conectividade", "Ethernet e Wi-Fi (802.11 b/g/n); USB para backup"],
      ["Prato", "Aço inoxidável, 438 x 270 mm"]
    ],
    imagem: "prix4due.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "toledo-prix-5-plus",
    nome: "Prix 5 Plus",
    marca: "Toledo",
    categoria: "balancas",
    subcategoria: "Com impressora de etiquetas",
    resumo: "Etiquetadora de 32 kg em torre, com 5.500 itens, impressão a até 100 mm/s e código 2D.",
    descricao: "Tem impressora integrada e mostrador elevado em torre, com hastes duplas, que facilita o acompanhamento da pesagem pelo cliente do outro lado do balcão. Imprime etiquetas a até 100 mm/s, com códigos de barras 1D ou códigos 2D (GS1 QR Code, GS1 Data Matrix) que podem levar validade e lote, conforme a versão. Guarda até 5.500 itens e tem 60 teclas rápidas. Indicada para balcões de atendimento e áreas com alto volume de produção.",
    especificacoes: [
      ["Capacidade", "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)"],
      ["Memória", "Até 5.500 itens"],
      ["Impressão", "Térmica, 8 pontos/mm, até 100 mm/s; códigos 1D (Code 128, GS1 DataBar Expanded) e 2D (GS1 QR Code, GS1 Data Matrix), conforme a versão"],
      ["Display", "Vácuo fluorescente, 2 linhas de 20 dígitos, em torre (coluna de 466 mm)"],
      ["Conectividade", "Ethernet e Wi-Fi (802.11 b/g/n)"]
    ],
    imagem: "prix5.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: true
  },
  {
    id: "balmak-orion-1-plus",
    nome: "Órion 1 Key",
    marca: "Balmak",
    categoria: "balancas",
    subcategoria: "Com impressora de etiquetas",
    resumo: "Etiquetadora de 30 kg com 4.000 itens, display SUPERLUX e impressão a 100 mm/s.",
    descricao: "Pesa, calcula o preço e imprime etiquetas com código de barras EAN-13 para leitura no caixa, tanto na venda direta no balcão quanto na pré-embalagem. O display SUPERLUX, de três linhas e retroiluminado, facilita a leitura mesmo com pouca luz, e a impressora aceita etiquetas de 30 x 30 mm até 60 x 120 mm. Vem nas versões plana e torre, com Ethernet e Wi-Fi na mesma balança, e é indicada para o médio e o grande varejo.",
    especificacoes: [
      ["Capacidade", "30 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 30 kg)"],
      ["Memória", "Até 4.000 itens"],
      ["Display", "SUPERLUX LCD alfanumérico de alta definição, retroiluminado, com 3 linhas"],
      ["Impressão", "Térmica, 8 pontos/mm, 100 mm/s"],
      ["Etiquetas", "De 30 x 30 mm até 60 x 120 mm"],
      ["Conectividade", "Ethernet (TCP/IP) e Wi-Fi (802.11)"]
    ],
    imagem: "balmak.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: true
  },
  {
    id: "balmak-orion-2",
    nome: "Órion 2 Key",
    marca: "Balmak",
    categoria: "balancas",
    subcategoria: "Com impressora de etiquetas",
    resumo: "Etiquetadora de 30 kg com display gráfico, 5.000 itens e versões plana ou torre.",
    descricao: "Pesa, calcula o preço e imprime etiquetas com código de barras EAN-13 ou Code 128. O Code 128 pode levar a data de validade no código e, se isso estiver programado no sistema do caixa, o operador recebe um aviso quando o item está vencido. O display gráfico azul, de 240 x 64 pixels, pode mostrar logotipos e mensagens publicitárias, e a memória comporta até 5.000 itens. Vem nas versões plana e torre, com Ethernet e Wi-Fi na mesma balança, e é indicada para o médio e o grande varejo.",
    especificacoes: [
      ["Capacidade", "30 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 30 kg)"],
      ["Memória", "Até 5.000 itens"],
      ["Display", "Gráfico azul matricial retroiluminado, 240 x 64 pixels"],
      ["Impressão", "Térmica, 8 pontos/mm, 100 mm/s; etiquetas de 30 x 30 mm até 60 x 120 mm"],
      ["Conectividade", "Ethernet (TCP/IP) e Wi-Fi (802.11)"]
    ],
    imagem: "balmak-orion2.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "toledo-prix-3-fit",
    nome: "Prix 3 Fit",
    marca: "Toledo",
    categoria: "balancas",
    subcategoria: "Computadoras",
    resumo: "Balança computadora de 32 kg com bateria de até 167 horas, para o pequeno varejo.",
    descricao: "Pesa e calcula o preço na hora, com display LCD de iluminação de fundo verde. A bateria interna recarregável garante até 167 horas de uso sem energia elétrica, então as vendas continuam em quedas de luz e em feiras ao ar livre. O prato de aço inoxidável tem centro rebaixado, que evita o escoamento de líquidos, e é fácil de higienizar. Indicada para mercados, padarias, quitandas, lojas de conveniência e feiras livres.",
    especificacoes: [
      ["Capacidade", "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)"],
      ["Display", "LCD alfanumérico com backlight verde"],
      ["Bateria", "Interna recarregável, até 167 horas de autonomia"],
      ["Prato", "Aço inoxidável com centro rebaixado, 300 x 230 mm"],
      ["Saída de dados", "RS-232C, USB ou Bluetooth (opcionais); conexão com a impressora IT400M"]
    ],
    imagem: "prix3fit.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: true
  },
  {
    id: "toledo-prix-3-plus",
    nome: "Prix 3 Plus",
    marca: "Toledo",
    categoria: "balancas",
    subcategoria: "Computadoras",
    resumo: "Balança computadora de 32 kg com prato amplo em inox e bateria de até 490 horas.",
    descricao: "Pesa e calcula o preço na hora, com prato de aço inoxidável amplo (355 x 235 mm), que acomoda melhor os produtos. A bateria interna recarregável garante até 490 horas de uso sem energia elétrica, e a função de pré-empacotamento mantém fixos o preço por quilo e a tara entre as pesagens. Pode ser ligada à impressora IT400M para emitir comandas ou etiquetas. Indicada para mercados, padarias, quitandas, lojas de conveniência e feiras livres.",
    especificacoes: [
      ["Capacidade", "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)"],
      ["Bateria", "Interna recarregável, até 490 horas de autonomia"],
      ["Display", "LCD alfanumérico com backlight verde"],
      ["Prato", "Aço inoxidável, 355 x 235 mm"],
      ["Saída de dados", "RS-232C, USB ou Bluetooth (opcionais); conexão com a impressora IT400M"]
    ],
    imagem: "prix3plus.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "balmak-one",
    nome: "ONE",
    marca: "Balmak",
    categoria: "balancas",
    subcategoria: "Computadoras",
    resumo: "Balança computadora de preços com painéis inclinados, de 15 ou 30 kg, e bateria opcional.",
    descricao: "Pesa e calcula o preço na hora, mostrando peso, preço unitário e valor total em displays LCD extragrandes com iluminação de fundo, nos lados do operador e do cliente. Os painéis são inclinados, o que dá mais conforto no uso. O prato é amplo (33,5 x 23,5 cm), e a versão com bateria interna funciona até 100 horas sem tomada. Tem saída serial RS-232 opcional para integração com a automação comercial.",
    especificacoes: [
      ["Capacidade", "15 kg (divisão de 5 g) ou 30 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 30 kg)"],
      ["Display", "LCD extragrande de alta resolução com backlight, nos lados do operador e do consumidor; mostra peso, preço unitário e preço total"],
      ["Bateria", "Interna recarregável (opcional), até 100 horas de autonomia"],
      ["Prato", "33,5 x 23,5 cm"],
      ["Saída serial", "RS-232 (DB-9) opcional, para integração com automação comercial"]
    ],
    imagem: "balmak-one.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "toledo-9094-plus",
    nome: "Prix 9094 Plus",
    marca: "Toledo",
    categoria: "balancas",
    subcategoria: "Pesadoras",
    resumo: "Pesadora compacta até 32 kg, com bateria de até 480 h e saída para o PDV.",
    descricao: "Balança pesadora compacta para o varejo e a indústria, com dois displays LCD com backlight, um para o operador e outro para o cliente. A bateria interna recarregável permite pesar com e sem energia elétrica, e a tara sucessiva facilita o preparo de receitas. O prato de aço inoxidável tem centro rebaixado para evitar que líquidos escorram sobre os displays. Conecta-se ao PDV e a sistemas de automação via RS-232C ou USB (opcional).",
    especificacoes: [
      ["Capacidade / Divisão", "3/6/15 kg x 1/2/5 g ou 6/15/32 kg x 2/5/10 g"],
      ["Display", "2 displays LCD com backlight (operador e consumidor)"],
      ["Bateria", "Interna recarregável, autonomia de até 480 h"],
      ["Conectividade", "RS-232C ou USB (opcional) para PDV, microterminais, sistemas de automação e impressoras"],
      ["Prato", "Aço inoxidável com centro rebaixado, 355 x 235 mm"]
    ],
    imagem: "9094plus.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "balmak-one-pesadora",
    nome: "ONE Pesadora",
    marca: "Balmak",
    categoria: "balancas",
    subcategoria: "Pesadoras",
    resumo: "Pesadora com display LCD com backlight, prato amplo e saída RS-232 opcional.",
    descricao: "Balança pesadora da linha ONE para o comércio. Os painéis do operador e do cliente são inclinados, o que dá mais conforto e ergonomia na leitura. O prato amplo, de plástico ABS e inox espelhado, tem abas contra o escoamento de líquidos e é fácil de higienizar. Como opcionais, a balança aceita bateria interna recarregável e saída serial RS-232 para integração com a automação comercial.",
    especificacoes: [
      ["Capacidade / Divisão", "5/10 kg x 1/2 g ou 6/15/30 kg x 2/5/10 g"],
      ["Display", "LCD extragrande de alta resolução com backlight, nos lados do operador e do cliente"],
      ["Prato", "33,5 x 23,5 cm, plástico ABS injetado e aço inox espelhado, com abas contra escoamento de líquidos"],
      ["Conectividade", "Serial RS-232 (DB-9), opcional"],
      ["Bateria", "Opcional: interna recarregável, autonomia de até 100 h"]
    ],
    imagem: "one-pesadora.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "toledo-8217",
    nome: "Prix 8217 Checkout",
    marca: "Toledo",
    categoria: "balancas",
    subcategoria: "Checkout",
    resumo: "Balança de checkout 32 kg x 5 g para embutir no caixa e integrar ao PDV.",
    descricao: "Balança para embutir no checkout de atacados, supermercados e sacolões. Ela pesa frutas, legumes e verduras direto no caixa e reduz gastos com etiquetagem no FLV. O display em torre gira na horizontal para facilitar a leitura do operador e do cliente. A saída RS-232 ou USB é compatível com a maioria dos softwares de frente de caixa, e o formato compacto permite integrar leitores de código de barras de leitura horizontal.",
    especificacoes: [
      ["Capacidade / Divisão", "32 kg x 5 g"],
      ["Comunicação", "RS-232C ou USB"],
      ["Prato", "Aço inoxidável AISI 304, com 4 rampas"],
      ["Display", "Remoto em torre, giro horizontal de 45° em 45°"],
      ["Versões", "Standard (292,2 x 335 mm) e Atacarejo (375 x 425, 500 x 300, 500 x 500 ou 600 x 300 mm)"]
    ],
    imagem: "8217.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "balmak-bck30",
    nome: "BCK-30 Checkout",
    marca: "Balmak",
    categoria: "balancas",
    subcategoria: "Checkout",
    resumo: "Balança de checkout 30 kg x 5 g com saídas RS-232 e USB para o PDV.",
    descricao: "Balança compacta para embutir no balcão do caixa: pesa frutas, legumes, verduras e pães no próprio checkout e reduz custos com etiquetagem dentro da loja. O prato em inox AISI 304, com 4 rampas, facilita o deslizamento dos produtos. As saídas RS-232 e USB integram o peso ao sistema de frente de caixa, e a balança aceita, como opcional, leitor de código de barras embutido.",
    especificacoes: [
      ["Capacidade / Divisão", "30 kg x 5 g (classe III)"],
      ["Prato", "Inox AISI 304, com 4 rampas para facilitar o deslizamento dos produtos"],
      ["Dimensões do prato", "270 x 334 mm"],
      ["Display", "LCD remoto com backlight, em mastro"],
      ["Conectividade", "Saídas seriais RS-232 e USB"],
      ["Alimentação", "Adaptador full range automático ou porta USB"]
    ],
    imagem: "bck30.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "toledo-2098",
    nome: "Prix 2098 / 2098C",
    marca: "Toledo",
    categoria: "balancas",
    subcategoria: "Industriais e conferência",
    resumo: "Balança de bancada inox até 300 kg para recebimento e conferência.",
    descricao: "Linha de balanças de bancada com plataforma em inox AISI 304, para pesagem e conferência no varejo alimentício e em processos industriais, como recebimento, expedição, almoxarifado e produção. Há versões com coluna articulada ou indicador remoto, e as configurações com bateria recarregável funcionam temporariamente sem energia elétrica. A 2098C acrescenta contagem de peças, verificação de peso por faixas de tolerância e acumulação de resultados.",
    especificacoes: [
      ["Capacidade / Divisão", "2098: 30/60 kg x 5/10 g ou 120/300 kg x 20/50 g; 2098C: 32 kg x 5 g, 60 kg x 10 g, 120 kg x 20 g ou 300 kg x 50 g"],
      ["Plataforma", "Inox AISI 304 escovado, 425 x 375 mm ou 500 x 500 mm"],
      ["Instalação", "Coluna articulada (0,5 m ou 0,8 m) ou indicação remota sobre mesa ou parede"],
      ["Comunicação", "RS-232C opcional; USB por conversor opcional"],
      ["Grau de proteção", "IP40 (2098) / IP54 (2098C)"]
    ],
    imagem: "2098.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: true
  },
  {
    id: "balmak-w300",
    nome: "K-300",
    marca: "Balmak",
    categoria: "balancas",
    subcategoria: "Industriais e conferência",
    resumo: "Balança de plataforma 300 kg x 50 g, tampa inox e indicador BK-10000.",
    descricao: "Balança de plataforma para mercados, armazéns e indústrias. A plataforma de 50 x 50 cm tem estrutura em aço carbono e tampa em aço inox. O indicador BK-10000, com display LED vermelho de 6 dígitos, tem filtro digital que estabiliza a pesagem em ambientes com vibração. As versões com bateria interna recarregável funcionam sem estar ligadas à tomada.",
    especificacoes: [
      ["Capacidade / Divisão", "300 kg x 50 g"],
      ["Plataforma", "50 x 50 cm, estrutura em aço carbono e tampa em aço inox"],
      ["Indicador", "BK-10000, IP-65, display LED vermelho de 6 dígitos"],
      ["Bateria", "Interna recarregável, até 25 h (Série Standard, K-300IB-3/S)"],
      ["Instalação", "Indicador remoto (Série Standard) ou coluna articulável com rodízios (Série Plus, K-300ICB-3/P)"]
    ],
    imagem: "w300.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "toledo-hospitalar",
    nome: "Prix 2096PP",
    marca: "Toledo",
    categoria: "balancas",
    subcategoria: "Médico-hospitalar",
    resumo: "Balança para pesar pessoas, 200 kg x 50 g, para farmácias e clínicas.",
    descricao: "Balança eletrônica para pesar pessoas em farmácias, hospitais, clínicas, consultórios e academias, com display LCD com backlight. A função tara desconta a cadeira, o banco ou outro apoio do paciente, e a superfície com borracha antiderrapante e pés niveladores dá segurança na pesagem. A régua antropométrica e a saída RS-232C são opcionais.",
    especificacoes: [
      ["Capacidade / Divisão", "200 kg x 50 g"],
      ["Display", "LCD com backlight, 6 dígitos de 26 mm de altura"],
      ["Plataforma", "400 x 400 mm, com borracha antiderrapante"],
      ["Opcionais", "Régua antropométrica e saída RS-232C"],
      ["Aprovação", "Inmetro, Portaria 236/94, classe de exatidão III"]
    ],
    imagem: "hospitalar.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "balmak-bk200f",
    nome: "BK-200F",
    marca: "Balmak",
    categoria: "balancas",
    subcategoria: "Médico-hospitalar",
    resumo: "Balança de coluna para pessoas, 200 kg x 100 g, com display LED.",
    descricao: "Balança digital de coluna para pesar pessoas em farmácias, academias, clínicas e consultórios. O display LED vermelho tem 6 dígitos. O tapete na plataforma e os pés antiderrapantes de borracha dão mais segurança ao usuário, e a tampa da plataforma pode ser retirada para limpeza. Nas versões com antropômetro (FA/FAN), a régua retrátil mede a altura junto com o peso.",
    especificacoes: [
      ["Capacidade / Divisão", "200 kg x 100 g"],
      ["Display", "LED vermelho, 6 dígitos"],
      ["Plataforma", "40 x 40 cm, chapa de aço carbono 1020"],
      ["Alimentação", "Fonte externa full range, 90-250 Vca, 50/60 Hz"],
      ["Antropômetro (versões FA/FAN)", "Régua retrátil de alumínio anodizado, até 2 m, graduação de 0,5 cm"]
    ],
    imagem: "bk200f.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  }
  /* ------------------------- AUTOMAÇÃO COMERCIAL ------------------------- */
,
  {
    id: "elgin-l42-pro",
    nome: "L42 Pro",
    marca: "Elgin",
    categoria: "automacao",
    subcategoria: "Impressoras de etiquetas",
    resumo: "Impressora de etiquetas térmica direta ou com ribbon, 203 dpi, até 102 mm/s.",
    descricao: "Imprime etiquetas de produtos, etiquetas de gôndola e códigos de barras em papel térmico ou com ribbon (transferência térmica). Reconhece automaticamente as linguagens EPL, ZPL, PPLA e PPLB, o que facilita a integração com o sistema de gestão da loja. Vem com o software BarTender UltraLite para criar e imprimir etiquetas.",
    especificacoes: [
      ["Impressão", "Transferência térmica (ribbon) ou térmica direta"],
      ["Resolução", "203 dpi (cabeça de 300 dpi opcional)"],
      ["Velocidade", "Até 102 mm/s (cerca de 4 pol/s)"],
      ["Largura de etiqueta", "20 a 111 mm (área de impressão de até 108 mm a 203 dpi)"],
      ["Conectividade", "L42 Pro: USB + 1 interface opcional (serial, Ethernet ou paralela); L42 Pro Full: USB, serial e Ethernet"],
      ["Linguagens", "PPLA, PPLB, EPL/EPL2 e ZPL/ZPL2, com reconhecimento automático"],
      ["Software incluso", "BarTender UltraLite, para criar e imprimir etiquetas"]
    ],
    imagem: "l42pro.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: true
  },
  {
    id: "argox-os-2140",
    nome: "OS-2140",
    marca: "Argox",
    categoria: "automacao",
    subcategoria: "Impressoras de etiquetas",
    resumo: "Impressora de etiquetas de mesa, 203 dpi, térmica direta ou com ribbon.",
    descricao: "Impressora de mesa que ocupa pouco espaço, para etiquetas de produtos, preços e códigos de barras, com impressão térmica direta ou por transferência térmica (ribbon). Imprime até 5 polegadas (127 mm) por segundo em etiquetas de até 110 mm de largura e aceita as emulações PPLA, PPLB e PPLZ. O mecanismo de impressão é todo em metal e a carcaça é de plástico ABS de alto impacto.",
    especificacoes: [
      ["Impressão", "Térmica direta e transferência térmica (ribbon)"],
      ["Resolução", "203 dpi (8 pontos/mm)"],
      ["Velocidade", "Até 5 pol/s (127 mm/s)"],
      ["Largura de impressão", "Até 105 mm (etiqueta de 25,4 a 110 mm)"],
      ["Linguagens", "Emulações PPLA, PPLB e PPLZ"],
      ["Construção", "Mecanismo de impressão todo em metal; carcaça em ABS de alto impacto"]
    ],
    imagem: "argox.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "zebra-zt230",
    nome: "ZT230",
    marca: "Zebra",
    categoria: "automacao",
    subcategoria: "Impressoras de etiquetas",
    resumo: "Impressora de etiquetas industrial de 4 polegadas, até 152 mm/s, para rolos de até 203 mm.",
    descricao: "Impressora de etiquetas da linha industrial ZT200 da Zebra. Aceita rolos de etiqueta de até 203 mm de diâmetro externo e, na versão com transferência térmica, ribbon de até 450 m, o que diminui as trocas de material. Tem mecanismo de impressão e tampa de mídia metálicos, display LCD gráfico com teclado para configuração e as linguagens ZPL e EPL residentes.",
    especificacoes: [
      ["Impressão", "Térmica direta; transferência térmica opcional (instalada de fábrica)"],
      ["Resolução", "203 dpi (300 dpi opcional)"],
      ["Velocidade", "Até 152 mm/s (6 pol/s)"],
      ["Largura de impressão", "Até 104 mm (etiqueta de 19,4 a 114 mm)"],
      ["Rolo de etiquetas", "Até 203 mm de diâmetro externo (tubete de 76 mm)"],
      ["Conectividade", "USB 2.0 e serial RS-232; opcional (uma por impressora): paralela, Ethernet 10/100 ou Wi-Fi 802.11a/b/g/n"],
      ["Linguagens", "ZPL, ZPL II e EPL (EPL só com cabeça de 203 dpi)"]
    ],
    imagem: "zebra.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "tanca-tl-120",
    nome: "TL-120",
    marca: "Tanca",
    categoria: "automacao",
    subcategoria: "Leitores de código de barras",
    resumo: "Leitor de mão 1D (CCD) USB, 300 scans/s; lê produtos, DANFE e boletos.",
    descricao: "Leitor de código de barras de mão para o caixa, com instalação plug and play pela porta USB. Faz 300 varreduras por segundo e lê códigos de produtos, DANFE, faturas e boletos bancários, o que agiliza as operações do dia a dia.",
    especificacoes: [
      ["Tecnologia", "CCD de imagem linear (códigos 1D)"],
      ["Velocidade", "300 varreduras (scans) por segundo"],
      ["Profundidade de campo", "Até 33 cm"],
      ["Conectividade", "USB (cabo de 2 m esticado)"],
      ["Resistência a queda", "1,5 m"]
    ],
    imagem: "tl120.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "tanca-tl-220",
    nome: "TL-220",
    marca: "Tanca",
    categoria: "automacao",
    subcategoria: "Leitores de código de barras",
    resumo: "Leitor laser 1D USB com pedestal articulável e sensor automático de leitura.",
    descricao: "Leitor laser de código de barras de mão com pedestal articulável e sensor automático de leitura: o operador pode ler os produtos com o leitor apoiado no pedestal ou na mão. Lê os principais códigos 1D, notas DANFE e boletos no padrão Febraban, com 100 varreduras por segundo e profundidade de campo de até 35 cm.",
    especificacoes: [
      ["Tecnologia", "Laser vermelho de 650 nm (códigos 1D)"],
      ["Velocidade", "100 varreduras (scans) por segundo"],
      ["Profundidade de campo", "Até 35 cm (largura de campo de 220 mm)"],
      ["Conectividade", "USB (cabo de 2 m esticado)"],
      ["Suporte", "Pedestal articulável com sensor automático de leitura"]
    ],
    imagem: "tl220.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "datalogic-quickscan-qw2100",
    nome: "QuickScan Lite QW2100",
    marca: "Datalogic",
    categoria: "automacao",
    subcategoria: "Leitores de código de barras",
    resumo: "Leitor 1D de imagem linear, até 400 leituras/s, com fio USB ou RS-232.",
    descricao: "Leitor de mão pequeno e leve, para caixas e balcões. A linha de leitura mais extensa facilita a leitura de códigos longos, como os de faturas. Lê os códigos 1D mais comuns, inclusive GS1 DataBar, com até 400 leituras por segundo, e pode ser usado num suporte articulável (vendido em kit ou à parte) para leitura com as mãos livres. Aguenta quedas de até 1,5 m e tem vedação IP42.",
    especificacoes: [
      ["Tecnologia", "Imager linear 1D com LED vermelho (610–650 nm)"],
      ["Taxa de leitura", "Até 400 leituras por segundo"],
      ["Profundidade de campo", "0 a 40 cm (código de 13 mils)"],
      ["Conectividade", "USB (QW2120) ou RS-232/teclado (QW2170)"],
      ["Robustez", "Vedação IP42; resiste a quedas de 1,5 m"]
    ],
    imagem: "quickscan.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "elgin-el4200",
    nome: "EL4200",
    marca: "Elgin",
    categoria: "automacao",
    subcategoria: "Leitores de código de barras",
    resumo: "Leitor fixo laser omnidirecional para checkout, 3.000 scans/s.",
    descricao: "Leitor fixo de mesa para o checkout: com a leitura omnidirecional, o operador passa o produto na frente do leitor sem precisar alinhar o código de barras com precisão. O laser de 650 nm projeta 30 linhas de varredura em 5 direções e lê códigos 1D, inclusive da família GS1 DataBar. Há versões USB e serial RS-232.",
    especificacoes: [
      ["Padrão de varredura", "Laser omnidirecional, 5 direções / 30 linhas"],
      ["Velocidade", "3.000 varreduras (scans) por segundo"],
      ["Fonte de luz", "Diodo laser de 650 nm"],
      ["Profundidade de leitura", "Acima de 200 mm (EAN 13 mil, PCS 90%)"],
      ["Códigos lidos", "1D, incluindo a família GS1 DataBar"],
      ["Conectividade", "USB (PN 46EL4200US0C) ou serial RS-232 (PN 46EL4200SR00), em versões separadas"]
    ],
    imagem: "el4200.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: true
  },
  {
    id: "tanca-tl-900",
    nome: "TL-900",
    marca: "Tanca",
    categoria: "automacao",
    subcategoria: "Leitores de código de barras",
    resumo: "Leitor fixo 2D omnidirecional para o caixa: lê códigos de barras e QR Code.",
    descricao: "Leitor fixo com sensor de imagem CMOS que lê códigos de barras 1D e 2D, como o QR Code, em qualquer direção, sem precisar alinhar o produto. É ligado e alimentado pela própria porta USB, sem fonte externa. Indicado para o caixa de mercados, padarias e açougues.",
    especificacoes: [
      ["Leitura", "1D e 2D omnidirecional (sensor CMOS)"],
      ["Códigos lidos", "EAN, UPC, Code 128, Code 39, Interleaved 2 of 5, QR Code, PDF417, Data Matrix, Aztec, entre outros"],
      ["Conectividade", "USB (cabo de 2 m), alimentação 5 V pela USB"],
      ["Profundidade de leitura", "Até 220 mm (UPC-A 13 mil)"]
    ],
    imagem: "tl900.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "gaveta-menno",
    nome: "Gaveta MG 41",
    marca: "Menno",
    categoria: "automacao",
    subcategoria: "Gavetas de dinheiro",
    resumo: "Gaveta em aço para o caixa, com abertura pela impressora ou manual por botão.",
    descricao: "Gaveta com gabinete em aço com pintura eletrostática, 5 divisórias para cédulas, 8 para moedas e porta-moedas removível, para manter o troco organizado no caixa. A versão MG 41B Plus abre automaticamente por comando da impressora (conector RJ12) e tem sensor de gaveta aberta e fechada. A MG 41 CS Eco abre manualmente por botão e serve para caixas sem integração com a impressora.",
    especificacoes: [
      ["Gabinete", "Aço com pintura eletrostática"],
      ["Compartimentos", "5 para cédulas e 8 para moedas, com porta-moedas removível"],
      ["Abertura (MG 41B Plus)", "Automática por comando da impressora (RJ12, 24 V/12 V), manual por chave e alavanca de emergência"],
      ["Sensor (MG 41B Plus)", "Gaveta aberta e fechada"],
      ["Abertura (MG 41 CS Eco)", "Manual por botão (a chave serve só para trancar)"],
      ["Dimensões", "405 x 420 x 115 mm (L x P x A)"]
    ],
    imagem: "menno.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "gaveta-bematech",
    nome: "Gaveta GD-56",
    marca: "Bematech",
    categoria: "automacao",
    subcategoria: "Gavetas de dinheiro",
    resumo: "Gaveta em aço com abertura pela impressora ou manual, chave e sensor de status.",
    descricao: "Gaveta de dinheiro em aço galvanizado, com porta-cédulas e moedas removível: 5 nichos para cédulas e 6 para moedas. Abre automaticamente pelo comando da impressora (conector RJ12) e também pode ser aberta manualmente. A fechadura de 3 posições vem com 2 chaves, e o sensor de status indica se a gaveta está aberta ou fechada.",
    especificacoes: [
      ["Material", "Aço galvanizado laminado a frio"],
      ["Compartimentos", "5 nichos para cédulas e 6 para moedas (porta-cédulas e moedas removível)"],
      ["Acionamento", "Solenoide de 24 V, conector padrão RJ12; fechadura de 3 posições com 2 chaves"],
      ["Sensor", "Status de gaveta aberta/fechada"],
      ["Dimensões", "410 x 115 x 415 mm (L x A x P, fechada); altura sem os pés: 100 mm"]
    ],
    imagem: "gavetabema.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "gertec-tc504",
    nome: "Terminal TC 504",
    marca: "Gertec",
    categoria: "automacao",
    subcategoria: "Terminais de consulta",
    resumo: "Terminal de consulta de preço com tela colorida e leitor omnidirecional.",
    descricao: "Terminal instalado no salão da loja para o cliente consultar o preço passando o código de barras, sem precisar ir ao caixa. Funciona em rede com o programa de consulta da loja: a tela colorida mostra nome, preço e foto do produto e, quando ninguém está consultando, exibe anúncios de produtos e promoções. As 4 teclas e o leitor de cartão magnético podem ser usados em programas de fidelidade.",
    especificacoes: [
      ["Display", "Gráfico colorido de 5,7\" (320 x 240 px)"],
      ["Leitor", "Scanner laser multi-feixe omnidirecional, 1.650 linhas por segundo"],
      ["Conectividade", "Ethernet ou Wi-Fi 802.11 b/g (conforme a versão)"],
      ["Leitor de cartão", "Magnético, trilhas 1 e 2"],
      ["Alimentação", "90 a 240 VAC"]
    ],
    imagem: "gertec504.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "gertec-g2",
    nome: "Busca Preço G2-E",
    marca: "Gertec",
    categoria: "automacao",
    subcategoria: "Terminais de consulta",
    resumo: "Terminal compacto de consulta de preço com tela colorida, Ethernet e Wi-Fi.",
    descricao: "Terminal compacto para fixar na parede ou em suporte: o cliente passa o código de barras e vê o preço na tela colorida de 3,2\", sem precisar ir ao caixa. Conecta-se à rede da loja por Ethernet ou Wi-Fi e tem alto-falante que reproduz arquivos de áudio WAV, com recurso de acessibilidade. Também exibe imagens nos formatos JPEG e BMP.",
    especificacoes: [
      ["Display", "TFT colorido de 3,2\" (320 x 240 px, 256 mil cores); exibe imagens JPEG e BMP"],
      ["Leitor", "Imager 1D unidirecional integrado"],
      ["Conectividade", "Ethernet e Wi-Fi 802.11 b/g/n 2,4 GHz; Bluetooth 4.2 para configuração"],
      ["Áudio", "Alto-falante integrado (reproduz arquivos WAV), com recurso de acessibilidade"],
      ["Dimensões", "140 x 100 x 40 mm"],
      ["Montagem", "Fixação em parede ou suporte"]
    ],
    imagem: "gertecg2.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "gertec-mt-720",
    nome: "Microterminal MT 720",
    marca: "Gertec",
    categoria: "automacao",
    subcategoria: "Teclados e microterminais",
    resumo: "Microterminal em rede para lançar pedidos e comandas no balcão ou na cozinha.",
    descricao: "Microterminal com display e teclado programável que se conecta ao sistema da loja pela rede Ethernet, para lançar pedidos e comandas. Indicado para padarias, restaurantes, bares e cozinhas. As portas seriais permitem conectar outros aparelhos, como leitor de código de barras, impressora e balança, conforme o software de automação usado.",
    especificacoes: [
      ["Display", "LCD de 2 linhas x 20 caracteres, com backlight"],
      ["Teclado", "20 teclas programáveis, com película protetora de silicone"],
      ["Rede", "Ethernet (RJ-45), protocolo TCP/IP"],
      ["Portas", "3 seriais RS-232 (1 DB9 + 2 RJ11) para aparelhos como leitor de código de barras, impressora e balança; PS/2 para teclado auxiliar"],
      ["Alimentação", "Bivolt (110/220 V)"]
    ],
    imagem: "mt720.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "gertec-tec-44",
    nome: "Teclado TEC 44",
    marca: "Gertec",
    categoria: "automacao",
    subcategoria: "Teclados e microterminais",
    resumo: "Teclado de PDV com 44 teclas programáveis e conexão USB para agilizar o caixa.",
    descricao: "Programe atalhos e funções do sistema nas 44 teclas, com até 4 códigos por tecla, e agilize o atendimento no caixa. A conexão USB é plug and play, o que facilita a instalação no PDV de supermercados, farmácias e lojas de conveniência. É construído para suportar o uso contínuo em ambientes comerciais de alto fluxo.",
    especificacoes: [
      ["Teclas", "44 teclas programáveis"],
      ["Programação", "Até 4 códigos por tecla"],
      ["Interface", "USB"],
      ["Dimensões (L x P x A)", "245 x 175 x 70 mm"],
      ["Peso", "720 g"]
    ],
    imagem: "tec44.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "smak-sko44",
    nome: "Teclado Óptico SKO-44",
    marca: "Smak",
    categoria: "automacao",
    subcategoria: "Teclados e microterminais",
    resumo: "Teclado óptico de PDV com 44 teclas programáveis e vida útil de até 100 milhões de toques.",
    descricao: "Teclado de PDV com tecnologia óptica: as teclas são lidas pela interrupção de feixes de luz, sem contato mecânico, e a vida útil chega a 100 milhões de toques. As 44 teclas legendáveis e programáveis aceitam até 63 caracteres cada, e a dupla paginação permite alternar entre duas camadas de comandos, o que agiliza o atendimento no caixa. Funciona com Windows e Linux e tem display LCD como opcional.",
    especificacoes: [
      ["Teclas", "44 teclas legendáveis e programáveis (até 63 caracteres por tecla)"],
      ["Tecnologia", "Óptica, por interrupção de feixes de luz (sem contato mecânico nas teclas)"],
      ["Vida útil", "Até 100 milhões de toques"],
      ["Paginação", "Dupla paginação (duas camadas de comandos)"],
      ["Compatibilidade", "Windows e Linux (DLL, SDK e drivers)"]
    ],
    imagem: "sko44.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "toledo-uni-350-ga",
    nome: "Fatiador Prix Veloce 350A",
    marca: "Toledo",
    categoria: "automacao",
    subcategoria: "Fatiadores",
    resumo: "Fatiador automático de frios com lâmina de 350 mm e pré-seleção de 1 a 99 fatias.",
    descricao: "Fatiador automático para frios: o carro se movimenta sozinho e fraciona a peça enquanto o operador retira as fatias para montar as bandejas. No painel touch, o operador ajusta a velocidade (10 níveis) e o curso de corte (3 níveis) e pré-seleciona de 1 a 99 fatias. É construído em alumínio anodizado e aço inox, com afiador de lâmina integrado e cantos arredondados que facilitam a limpeza.",
    especificacoes: [
      ["Funcionamento", "Automático"],
      ["Lâmina", "350 mm, 200 rpm"],
      ["Velocidade de corte", "40 a 60 fatias/min (10 níveis), com pré-seleção de 1 a 99 fatias"],
      ["Capacidade de corte", "285 x 155 mm (retangular), 200 x 200 mm (quadrada), Ø 235 mm (circular)"],
      ["Tensão", "220 V"]
    ],
    imagem: "fatiador.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: true
  },
  {
    id: "toledo-uni-350",
    nome: "Fatiador Prix Supremo 350S",
    marca: "Toledo",
    categoria: "automacao",
    subcategoria: "Fatiadores",
    resumo: "Fatiador semiautomático de frios com lâmina de 350 mm para o atendimento no balcão.",
    descricao: "Fatiador semiautomático para frios, indicado para o atendimento direto ao cliente no balcão ou no ponto de venda. A lâmina de 350 mm tem afiador integrado, e a espessura do corte é ajustada por manopla, com abertura de até 15 mm. A estrutura em alumínio anodizado e aço inox tem cantos arredondados que facilitam a higienização, e os pés ajustáveis permitem posicioná-lo em balcões de vários tamanhos.",
    especificacoes: [
      ["Funcionamento", "Semiautomático"],
      ["Lâmina", "350 mm, 200 rpm"],
      ["Abertura máxima de corte", "15 mm"],
      ["Capacidade de corte", "285 x 155 mm (retangular), 200 x 200 mm (quadrada), Ø 235 mm (circular)"],
      ["Material / proteção", "Alumínio anodizado e aço inox; IP54 e IP69"]
    ],
    imagem: "uni350.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "toledo-mit",
    nome: "Mídia Digital Prix MIT 7",
    marca: "Toledo",
    categoria: "automacao",
    subcategoria: "Tabelas digitais",
    resumo: "Exibe preços, ofertas e chamadas de senha nas TVs da loja, com integração ao MGV da Toledo.",
    descricao: "Mostra nas TVs da loja tabelas de preços, ofertas, anúncios e chamadas de senha, com o conteúdo gerenciado pelo software MIT 7 em um computador com Windows. A integração com o MGV da Toledo compartilha o cadastro de produtos e preços e reduz o retrabalho na hora de atualizar as telas. As senhas aparecem em pop-up, com filas de até três departamentos e atendimento preferencial na mesma TV.",
    especificacoes: [
      ["Tabela de preços", "De 12 a 25 linhas; até 68 preços por tela (tabela de itens compostos, 17 linhas x 4 colunas)"],
      ["Chamada de senha", "Pop-up, até 3 departamentos por TV, com senha preferencial"],
      ["Player", "MIT Player W: mini PC Android, saída HDMI, 1920 x 1080"],
      ["Rede", "Ethernet 100/1000 Mbps"],
      ["Integração", "MGV (cadastro de produtos e preços) e aplicativo de emissão de senhas para totens"]
    ],
    imagem: "mit.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "balmak-all-midia",
    nome: "All-Mídia",
    marca: "Balmak",
    categoria: "automacao",
    subcategoria: "Tabelas digitais",
    resumo: "Tabela de preços e ofertas em TV Full HD, integrada às balanças Balmak Órion.",
    descricao: "Mostra nas TVs da loja tabelas de preços, ofertas, imagens e vídeos em Full HD, com programação configurável de forma diferente para cada setor da loja. Integrado às balanças Balmak Órion pelo software DFS, usa o mesmo banco de dados das balanças: o preço alterado na balança muda sozinho na tabela digital, sem importar arquivos. Também funciona de forma independente (stand-alone) e, com as balanças Órion, organiza filas com chamada de senha.",
    especificacoes: [
      ["Resolução", "Full HD 1920 x 1080"],
      ["Player", "Mini PC com saída HDMI e Wi-Fi 802.11 b/g/n integrado"],
      ["Integração", "Balanças Órion via software DFS (atualização automática de preços)"],
      ["Chamada de senha", "Organizador de fila com senha, operando com balanças Órion"],
      ["Operação independente", "Pode ser usado sem integração com balanças (stand-alone)"]
    ],
    imagem: "allmidia.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  }
  /* ------------------------- INFORMÁTICA E PDV ------------------------- */
,
  {
    id: "centrium-mini-pc",
    nome: "Mini PC para PDV",
    marca: "Centrium",
    categoria: "informatica",
    subcategoria: "Computadores",
    resumo: "Computador compacto para o caixa, que libera espaço no balcão.",
    descricao: "Computador compacto para rodar o sistema de vendas na frente de caixa, ocupando pouco espaço no balcão. A configuração (processador, memória, armazenamento e portas) varia conforme a disponibilidade em estoque, e a equipe ajuda a escolher a versão compatível com o seu sistema e com os periféricos (impressora, balança e leitor).",
    especificacoes: [
      ["Formato", "Mini PC"],
      ["Uso", "Frente de caixa"]
    ],
    imagem: "centrium-pc.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: true
  },
  {
    id: "tanca-tp-650",
    nome: "TP-650",
    marca: "Tanca",
    categoria: "informatica",
    subcategoria: "Impressoras de cupom",
    resumo: "Impressora de cupom 80 mm, 250 mm/s, com USB, Serial e Ethernet de fábrica.",
    descricao: "Imprime cupons e comprovantes a até 250 mm/s, o que agiliza a fila no caixa da padaria, do açougue ou do mercado. Já sai de fábrica com USB, Serial e Ethernet, o que facilita ligar a impressora ao computador ou à rede, e a troca de bobina é simples (sistema Easy Load). A guilhotina tem vida útil de 1 milhão de cortes, e a garantia é de 3 anos.",
    especificacoes: [
      ["Velocidade de impressão", "250 mm/s"],
      ["Conectividade", "Ethernet, Serial e USB (integradas)"],
      ["Largura da bobina", "80 mm"],
      ["Corte", "Guilhotina (vida útil de 1 milhão de cortes) e serrilha"],
      ["Garantia", "3 anos"]
    ],
    imagem: "tanca.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "elgin-i9",
    nome: "i9 Full",
    marca: "Elgin",
    categoria: "informatica",
    subcategoria: "Impressoras de cupom",
    resumo: "Impressora de cupom 300 mm/s com guilhotina e USB, Ethernet e Serial.",
    descricao: "Impressora térmica de cupom para NFC-e/SAT e comprovantes, que imprime a até 300 mm/s para agilizar o atendimento no caixa. Reúne USB, Ethernet e Serial no mesmo equipamento, aceita bobinas de 57,5 mm ou 80 mm e tem guilhotina com corte parcial automático e vida útil de 2 milhões de cortes. A garantia é de 3 anos e inclui a cabeça térmica.",
    especificacoes: [
      ["Velocidade de impressão", "Até 300 mm/s"],
      ["Conectividade", "USB, Ethernet e Serial RS-232 (DB-9)"],
      ["Largura do papel", "57,5 mm ou 80 mm"],
      ["Guilhotina", "Corte parcial automático, vida útil de 2 milhões de cortes"],
      ["Garantia", "3 anos (impressora e cabeça térmica)"]
    ],
    imagem: "elgin-i9.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: true
  },
  {
    id: "epson-tm-t20",
    nome: "TM-T20X II",
    marca: "Epson",
    categoria: "informatica",
    subcategoria: "Impressoras de cupom",
    resumo: "Impressora de cupom Epson 250 mm/s com guilhotina, fabricada no Brasil.",
    descricao: "Impressora térmica de recibos para cupons e comprovantes, que imprime a até 250 mm/s e corta o papel automaticamente para agilizar o caixa. Tem carga rápida de papel, baixo consumo de energia e LEDs de status, e vem nas versões USB + Serial ou USB + Ethernet. É fabricada no Brasil e tem garantia Epson de 3 anos balcão.",
    especificacoes: [
      ["Velocidade de impressão", "Até 250 mm/s"],
      ["Conectividade", "USB + Serial (C31CL45011) ou USB + Ethernet (C31CL45012)"],
      ["Largura do papel", "80 mm (79,5 ± 0,5 mm)"],
      ["Guilhotina", "Automática, 1,5 milhão de cortes"],
      ["Garantia", "3 anos balcão"]
    ],
    imagem: "epson-t20.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "sat-bematech-rb2000",
    nome: "SAT RB-2000",
    marca: "Bematech",
    categoria: "informatica",
    subcategoria: "Emissores fiscais (SAT)",
    resumo: "Equipamento SAT para emissão de CF-e.",
    descricao: "Equipamento SAT (CF-e). Atenção: em SP o CF-e SAT está proibido desde 01/01/2026 — fale com a gente para migrar para NFC-e.",
    especificacoes: [
      ["Documento", "CF-e SAT"]
    ],
    imagem: "bematech-sat.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false,
    visivel: false   // SAT proibido em SP desde 01/01/2026
  },
  {
    id: "sat-elgin-smart",
    nome: "SAT Smart",
    marca: "Elgin",
    categoria: "informatica",
    subcategoria: "Emissores fiscais (SAT)",
    resumo: "Equipamento SAT para emissão de CF-e.",
    descricao: "Equipamento SAT (CF-e). Atenção: em SP o CF-e SAT está proibido desde 01/01/2026 — fale com a gente para migrar para NFC-e.",
    especificacoes: [
      ["Documento", "CF-e SAT"]
    ],
    imagem: "elgin-smart.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false,
    visivel: false   // SAT proibido em SP desde 01/01/2026
  },
  {
    id: "sat-tanca-ts1000",
    nome: "SAT TS-1000",
    marca: "Tanca",
    categoria: "informatica",
    subcategoria: "Emissores fiscais (SAT)",
    resumo: "Equipamento SAT para emissão de CF-e.",
    descricao: "Equipamento SAT (CF-e). Atenção: em SP o CF-e SAT está proibido desde 01/01/2026 — fale com a gente para migrar para NFC-e.",
    especificacoes: [
      ["Documento", "CF-e SAT"]
    ],
    imagem: "sat-tanca.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false,
    visivel: false   // SAT proibido em SP desde 01/01/2026
  },
  {
    id: "nobreak-apc",
    nome: "Back-UPS 400 VA",
    marca: "APC",
    categoria: "informatica",
    subcategoria: "Nobreaks",
    resumo: "Nobreak 400 VA para o PC do caixa, com entrada bivolt e 4 tomadas.",
    descricao: "Mantém o computador do caixa ligado durante quedas de energia, dando tempo para concluir a venda e desligar o sistema com segurança. Tem entrada bivolt (115/220 V), saída 115 V e 4 tomadas no padrão NBR 14136. Os equipamentos ligados a ele precisam funcionar em 115 V.",
    especificacoes: [
      ["Potência", "400 VA / 220 W"],
      ["Tensão", "Entrada bivolt 115/220 V; saída 115 V"],
      ["Tomadas", "4 tomadas padrão NBR 14136"],
      ["Topologia", "Standby, forma de onda senoidal aproximada"]
    ],
    imagem: "nobreak-apc.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  },
  {
    id: "nobreak-nhs",
    nome: "Nobreak 400 VA",
    marca: "NHS",
    categoria: "informatica",
    subcategoria: "Nobreaks",
    resumo: "Nobreak nacional robusto para o caixa.",
    descricao: "Nobreak NHS para proteger os equipamentos do caixa contra quedas e oscilações de energia.",
    especificacoes: [
      ["Potência", "400 VA"],
      ["Tensão", "Bivolt"]
    ],
    imagem: "nobreak-nhs.webp",
    estoque: "consulte",
    condicao: "novo",
    preco: "",
    destaque: false
  }
];
