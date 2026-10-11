/* =====================================================================
   CATÁLOGO DE PRODUTOS — Balanças.com
   ---------------------------------------------------------------------
   Arquivo GERADO pelo painel de administração do site. Para adicionar,
   editar, esconder ou excluir produtos e trocar fotos, use o painel:
   ele confere os dados, guarda cópias de segurança e não deixa o
   catálogo quebrar. O que for editado à mão aqui some na próxima vez
   que o painel salvar.

   Formato: JSON puro dentro de window.CATEGORIAS e window.PRODUTOS.
   Campos de cada produto: id, nome, marca, categoria, subcategoria,
   resumo, descricao, especificacoes ([["Nome", "Valor"], ...]),
   imagem (em compartilhado/img/produtos/), recorte (opcional, em
   compartilhado/img/produtos-recorte/), estoque (consulte | disponivel |
   encomenda | esgotado), condicao (novo | seminovo), preco ("" = Consulte),
   destaque e visivel. (A anotação interna de cada produto fica só no
   painel, na pasta privada: não é publicada aqui.)
   Última gravação pelo painel: 11/10/2026 12:13.
   ===================================================================== */
window.CATEGORIAS = [
    {
        "id": "balancas",
        "nome": "Balanças",
        "descricao": "Comerciais, com impressora de etiquetas, industriais, de checkout e hospitalares.",
        "subcategorias": [
            "Com impressora de etiquetas",
            "Computadoras",
            "Pesadoras",
            "Checkout",
            "Industriais e conferência",
            "Médico-hospitalar"
        ]
    },
    {
        "id": "automacao",
        "nome": "Automação Comercial",
        "descricao": "Impressoras de etiquetas, leitores, gavetas, terminais, fatiadores e mídia digital.",
        "subcategorias": [
            "Impressoras de etiquetas",
            "Leitores de código de barras",
            "Gavetas de dinheiro",
            "Terminais de consulta",
            "Teclados e microterminais",
            "Fatiadores",
            "Tabelas digitais"
        ]
    },
    {
        "id": "informatica",
        "nome": "Informática e PDV",
        "descricao": "Computadores para PDV, impressoras de cupom, emissores fiscais e nobreaks.",
        "subcategorias": [
            "Computadores",
            "Impressoras de cupom",
            "Emissores fiscais (SAT)",
            "Nobreaks"
        ]
    }
];
window.PRODUTOS = [
    {
        "id": "toledo-prix-7e",
        "nome": "Prix 7E",
        "marca": "Toledo",
        "categoria": "balancas",
        "subcategoria": "Com impressora de etiquetas",
        "resumo": "Etiquetadora de 32 kg com 20.000 itens e display do cliente que mostra suas ofertas em vídeo e imagem.",
        "descricao": "Novidade de 2026 da linha Prix. Pesa, calcula o preço e imprime etiquetas com código de barras para o caixa, tanto na venda direta no balcão quanto na pré-embalagem, a até 100 mm/s. O display voltado para o cliente exibe vídeos e imagens das suas ofertas enquanto ele acompanha a pesagem, com o conteúdo gerenciado pelo MGV Cloud. Guarda até 20.000 itens com receita e tabela nutricional, acessa 180 itens pelas teclas rápidas e registra as vendas de até 500 operadores. Imprime códigos 2D (GS1 QR Code e GS1 Data Matrix), que podem levar validade e lote, e aceita etiqueta contínua, que ajusta o tamanho ao conteúdo e economiza papel. Indicada para supermercados, padarias, açougues e hortifrútis.",
        "especificacoes": [
            [
                "Capacidade",
                "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)"
            ],
            [
                "Memória",
                "Até 20.000 itens"
            ],
            [
                "Impressão",
                "Térmica, 8 pontos/mm, até 100 mm/s; mecanismo em aço inox; aceita etiqueta contínua"
            ],
            [
                "Display",
                "Alfanumérico de 2 linhas x 20 dígitos para o operador; display do cliente com vídeos e imagens (MGV Cloud)"
            ],
            [
                "Códigos",
                "Code 128, GS1 DataBar Expanded, GS1 QR Code, GS1 Data Matrix e GS1 Digital Link"
            ],
            [
                "Conectividade",
                "Ethernet (100 Mbps) e Wi-Fi (802.11 b/g/n); integração com MGV 7 e MGV Cloud"
            ],
            [
                "Cadastro",
                "20.000 receitas, 20.000 tabelas nutricionais, 180 itens nas teclas rápidas e até 500 operadores"
            ],
            [
                "Prato",
                "Aço inoxidável, 437 x 270 mm"
            ]
        ],
        "imagem": "prix7e.webp",
        "recorte": "prix7e.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": true
    },
    {
        "id": "toledo-prix-7t",
        "nome": "Prix 7T",
        "marca": "Toledo",
        "categoria": "balancas",
        "subcategoria": "Com impressora de etiquetas",
        "resumo": "Etiquetadora de 32 kg que reconhece o produto pela imagem, com 100.000 itens e tela de toque de 12,1 polegadas.",
        "descricao": "Pesa, calcula o preço e imprime etiquetas, na venda direta no balcão ou na pré-embalagem, a até 130 mm/s. Câmeras na torre reconhecem o produto pela imagem (a balança aprende os itens da loja durante a operação) e o rosto do operador, o que dá mais controle em lojas com vários turnos. O operador usa uma tela de toque de 12,1 polegadas, com teclas rápidas identificadas por imagem, e o cliente vê num display de 7 polegadas imagens e vídeos das suas promoções, gerenciados pelo MGV 7 ou pelo MGV Cloud. Guarda até 100.000 itens com receita e tabela nutricional, aceita etiqueta contínua e imprime códigos 2D (GS1 QR Code e GS1 Data Matrix) que podem levar validade e lote. Indicada para supermercados, hipermercados, padarias, açougues e hortifrútis.",
        "especificacoes": [
            [
                "Capacidade",
                "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)"
            ],
            [
                "Memória",
                "Até 100.000 itens"
            ],
            [
                "Impressão",
                "Térmica, 8 pontos/mm, até 130 mm/s; etiquetas de 30 a 60 mm de largura; aceita etiqueta contínua"
            ],
            [
                "Display",
                "Tela sensível ao toque colorida de 12,1\" para o operador; LCD colorido de 7\" para o cliente, com imagens e vídeos"
            ],
            [
                "Inteligência artificial",
                "Reconhecimento do produto por imagem e reconhecimento facial do operador, com câmeras na torre"
            ],
            [
                "Códigos",
                "Code 128, GS1 DataBar Expanded, GS1 QR Code, GS1 Data Matrix e GS1 Digital Link"
            ],
            [
                "Conectividade",
                "Ethernet (Gigabit) e Wi-Fi (802.11 b/g/n); integração com MGV 7 e MGV Cloud"
            ],
            [
                "Cadastro",
                "100.000 receitas, 100.000 tabelas nutricionais, teclas de acesso rápido com imagem e até 500 operadores"
            ],
            [
                "Prato",
                "Aço inoxidável, 495 x 300 mm"
            ]
        ],
        "imagem": "toledo-prix-7t-8e917913b2.webp",
        "recorte": "toledo-prix-7t-8e917913b2.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "toledo-prix-6",
        "nome": "Prix 6",
        "marca": "Toledo",
        "categoria": "balancas",
        "subcategoria": "Com impressora de etiquetas",
        "resumo": "Etiquetadora de 32 kg com 20.000 itens e display colorido de 7 polegadas que mostra ao cliente o produto e as promoções.",
        "descricao": "Pesa, calcula o preço e imprime etiquetas, na venda direta no balcão ou na pré-embalagem, a até 100 mm/s. O operador usa um display fluorescente a vácuo de 2 linhas, fácil de ler de qualquer ângulo, e o cliente acompanha a venda num display colorido de 7 polegadas que mostra a imagem do produto e até 10 mensagens de promoção; com a torre opcional, esse display fica no alto. Guarda até 20.000 itens com receita e tabela nutricional, acessa 180 itens pelas 60 teclas rápidas e registra as vendas de até 500 operadores. Aceita etiqueta contínua, que economiza papel, e, conforme a versão, imprime códigos 2D com validade e lote. Indicada para padarias, açougues e balcões com alto volume de produção.",
        "especificacoes": [
            [
                "Capacidade",
                "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)"
            ],
            [
                "Memória",
                "Até 20.000 itens"
            ],
            [
                "Impressão",
                "Térmica, 8 pontos/mm, até 100 mm/s; etiquetas de até 60 mm de largura; aceita etiqueta contínua"
            ],
            [
                "Display",
                "Fluorescente a vácuo (VFD), 2 linhas de 20 dígitos, para o operador; LCD colorido de 7\" para o cliente, com imagens e mensagens"
            ],
            [
                "Versões",
                "Standard ou com torre (opcional), em que colunas duplas elevam o display do cliente"
            ],
            [
                "Códigos",
                "Code 128 e GS1 DataBar Expanded; GS1 QR Code, GS1 Data Matrix e GS1 Digital Link, conforme a versão"
            ],
            [
                "Conectividade",
                "Ethernet e Wi-Fi (802.11 b/g); integração com o MGV 7"
            ],
            [
                "Cadastro",
                "20.000 receitas, 20.000 tabelas nutricionais, 180 itens nas teclas rápidas e até 500 operadores"
            ],
            [
                "Prato",
                "Aço inoxidável, com aba para escoamento de líquido"
            ]
        ],
        "imagem": "toledo-prix-6-ab09c21dc2.webp",
        "recorte": "toledo-prix-6-ab09c21dc2.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "toledo-prix-5-plus",
        "nome": "Prix 5 Plus",
        "marca": "Toledo",
        "categoria": "balancas",
        "subcategoria": "Com impressora de etiquetas",
        "resumo": "Etiquetadora de 32 kg em torre, com 5.500 itens, impressão a até 100 mm/s e código 2D.",
        "descricao": "Tem impressora integrada e mostrador elevado em torre, com hastes duplas, que facilita o acompanhamento da pesagem pelo cliente do outro lado do balcão. Imprime etiquetas a até 100 mm/s, com códigos de barras 1D ou códigos 2D (GS1 QR Code, GS1 Data Matrix) que podem levar validade e lote, conforme a versão. Guarda até 5.500 itens e tem 60 teclas rápidas. Indicada para balcões de atendimento e áreas com alto volume de produção.",
        "especificacoes": [
            [
                "Capacidade",
                "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)"
            ],
            [
                "Memória",
                "Até 5.500 itens"
            ],
            [
                "Impressão",
                "Térmica, 8 pontos/mm, até 100 mm/s; códigos 1D (Code 128, GS1 DataBar Expanded) e 2D (GS1 QR Code, GS1 Data Matrix), conforme a versão"
            ],
            [
                "Display",
                "Vácuo fluorescente, 2 linhas de 20 dígitos, em torre (coluna de 466 mm)"
            ],
            [
                "Conectividade",
                "Ethernet e Wi-Fi (802.11 b/g/n)"
            ]
        ],
        "imagem": "toledo-prix-5-plus-495bf16c9c.webp",
        "recorte": "toledo-prix-5-plus-495bf16c9c.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": true
    },
    {
        "id": "toledo-prix-4-trend",
        "nome": "Prix 4 Trend",
        "marca": "Toledo",
        "categoria": "balancas",
        "subcategoria": "Com impressora de etiquetas",
        "resumo": "Etiquetadora compacta de 32 kg com 2.000 itens, Ethernet e Wi-Fi e Modo Comanda, que soma as compras numa comanda só.",
        "descricao": "Pesa, calcula o preço e imprime etiquetas com código de barras EAN-13 para leitura no caixa, na venda direta no balcão ou na pré-embalagem, a 70 mm/s. No Modo Comanda, soma as compras de um cliente e, no final, imprime uma comanda em papel contínuo com o total em código de barras e a identificação do operador, sem gastar etiqueta adesiva. Guarda até 2.000 itens, cadastrados na balança ou pelo computador, e as 48 teclas rápidas acessam até 144 itens sem digitar código. Trabalha em rede Ethernet ou Wi-Fi com o MGV, o programa de gestão de vendas da Toledo, e tem prato de inox removível, fácil de limpar. Indicada para mercados, mercearias, padarias, açougues e hortifrútis de pequeno e médio porte.",
        "especificacoes": [
            [
                "Capacidade",
                "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)"
            ],
            [
                "Memória",
                "Até 2.000 itens"
            ],
            [
                "Impressão",
                "Térmica, 8 pontos/mm, 70 mm/s; etiquetas de até 60 mm de largura; código de barras EAN-13"
            ],
            [
                "Modo Comanda",
                "Soma as vendas do cliente e imprime uma comanda em papel térmico contínuo de 40 ou 60 mm"
            ],
            [
                "Display",
                "LCD alfanumérico com backlight azul, para o operador e para o cliente"
            ],
            [
                "Teclas rápidas",
                "48 teclas, com acesso a até 144 itens"
            ],
            [
                "Conectividade",
                "Ethernet e Wi-Fi (802.11 b/g); integração com o MGV; USB para backup"
            ],
            [
                "Prato",
                "Aço inoxidável, 359 x 251 mm, removível, com aba para escoamento de líquido"
            ],
            [
                "Alimentação",
                "Multivoltagem automática, de 93,5 a 264 Vca (50 a 60 Hz)"
            ]
        ],
        "imagem": "toledo-prix-4-trend-7bfd42eccc.webp",
        "recorte": "toledo-prix-4-trend-7bfd42eccc.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "toledo-prix-4-uno",
        "nome": "Prix 4 Uno",
        "marca": "Toledo",
        "categoria": "balancas",
        "subcategoria": "Com impressora de etiquetas",
        "resumo": "Balança etiquetadora compacta de 32 kg, com Ethernet e Wi-Fi integrados.",
        "descricao": "Pesa, calcula o preço e imprime etiquetas com código de barras EAN-13 para leitura automática no caixa, tanto na venda direta no balcão quanto na pré-embalagem em bandejas. Usa etiquetas de 40 mm de largura, que reduzem o gasto com suprimentos, e guarda até 2.000 itens, cadastrados na própria balança ou pelo computador em rede. Indicada para pequenos e médios comércios.",
        "especificacoes": [
            [
                "Capacidade",
                "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)"
            ],
            [
                "Impressão",
                "Térmica, 8 pontos/mm, 60 mm/s"
            ],
            [
                "Largura de etiqueta",
                "40 mm"
            ],
            [
                "Memória",
                "Até 2.000 itens"
            ],
            [
                "Conectividade",
                "Ethernet e Wi-Fi (WLAN); USB para backup e restauração de dados"
            ]
        ],
        "imagem": "prix4uno.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": true
    },
    {
        "id": "toledo-prix-4-due",
        "nome": "Prix 4 Due",
        "marca": "Toledo",
        "categoria": "balancas",
        "subcategoria": "Com impressora de etiquetas",
        "resumo": "Etiquetadora de 32 kg com 4.000 itens, 60 teclas rápidas e Ethernet/Wi-Fi.",
        "descricao": "Pesa, calcula o preço e imprime etiquetas com código de barras para leitura no caixa, a até 70 mm/s. As 60 teclas rápidas acessam até 180 itens sem digitar código, e o prato de aço inoxidável tem área ampla, de 438 x 270 mm. Reconhece automaticamente o tamanho da etiqueta e opera tanto na venda direta quanto na pré-embalagem, atendendo desde estabelecimentos de pequeno porte até hipermercados.",
        "especificacoes": [
            [
                "Capacidade",
                "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)"
            ],
            [
                "Memória",
                "Até 4.000 itens"
            ],
            [
                "Impressão",
                "Térmica, 8 pontos/mm, até 70 mm/s"
            ],
            [
                "Display",
                "LCD alfanumérico de 2 linhas com backlight azul"
            ],
            [
                "Conectividade",
                "Ethernet e Wi-Fi (802.11 b/g/n); USB para backup"
            ],
            [
                "Prato",
                "Aço inoxidável, 438 x 270 mm"
            ]
        ],
        "imagem": "prix4due.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "balmak-edge",
        "nome": "EDGE",
        "marca": "Balmak",
        "categoria": "balancas",
        "subcategoria": "Com impressora de etiquetas",
        "resumo": "Etiquetadora torre de 33 kg com 10.000 itens, display colorido com a imagem do produto para operador e cliente e carga de dados por pen drive.",
        "descricao": "Pesa, calcula o preço e imprime etiquetas com código de barras, na venda direta no balcão ou na pré-embalagem, a 100 mm/s. Feita pela Balmak para o pequeno e o médio varejo, tem formato torre: o display colorido, no alto da coluna, mostra ao operador e ao cliente as informações da pesagem e a imagem do item pesado. Guarda até 10.000 itens, imprime etiquetas com tabela nutricional e os selos de “alto em” da RDC 429 da Anvisa e tem 352 teclas de atalho, em 4 páginas de teclado. O cadastro pode ser feito no teclado da própria balança, pela rede com o software de gestão EDGE ou por pen drive na porta USB, então ela também funciona sozinha, sem computador. O prato de aço inox, de 383 x 262 mm, tem grade de apoio. Indicada para padarias, açougues, hortifrútis, lojas de conveniência, sorveterias, restaurantes e mercados.",
        "especificacoes": [
            [
                "Capacidade",
                "33 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 33 kg)"
            ],
            [
                "Memória",
                "Até 10.000 itens"
            ],
            [
                "Impressão",
                "Térmica, 8 pontos/mm, 100 mm/s; etiquetas de 30 x 30 mm até 60 x 130 mm; cabeça térmica com vida útil de 150 km"
            ],
            [
                "Display",
                "TFT colorido no alto da torre, para o operador e para o cliente, com a imagem do item pesado"
            ],
            [
                "Teclas rápidas",
                "352 teclas de atalho, em 4 páginas de teclado"
            ],
            [
                "Etiquetas",
                "99 modelos (16 fixos e 83 personalizáveis no software de gestão EDGE); tabela nutricional com os selos “alto em” (RDC 429 da Anvisa)"
            ],
            [
                "Conectividade",
                "Ethernet (TCP/IP) e Wi-Fi (802.11), conforme a versão; USB para carga de dados por pen drive"
            ],
            [
                "Prato",
                "Aço inoxidável, 383 x 262 mm, com grade de apoio"
            ],
            [
                "Formato",
                "Torre; 400 x 440 x 570 mm (L x P x A); 6,5 kg"
            ]
        ],
        "imagem": "balmak-edge-bc25842b92.webp",
        "recorte": "balmak-edge-bc25842b92.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "urano-ba37",
        "nome": "BA37",
        "marca": "Urano",
        "categoria": "balancas",
        "subcategoria": "Com impressora de etiquetas",
        "resumo": "Etiquetadora compacta de 31 kg, sem coluna, com memória para 10.000 produtos, rede por cabo (ou Wi-Fi, conforme o modelo) e a data de validade no código de barras.",
        "descricao": "Pesa, calcula o preço e imprime etiquetas com código de barras, na venda direta no balcão ou na pré-embalagem, de produtos vendidos por peso ou por unidade, inclusive congelados (glaciados) e com líquido (drenados). É a versão compacta, sem coluna, com 40 teclas rápidas. Guarda até 10.000 produtos com tabela nutricional, receitas, alérgenos e tara predeterminada, e põe a data de validade no código de barras, para o caixa barrar produto vencido (se o sistema do caixa aceitar). Entra na rede por cabo ou, conforme o modelo, por Wi-Fi, e o cadastro é feito no Urano Connect, programa gratuito que acompanha a balança, no próprio teclado ou por pen drive. Não tem bateria: trabalha ligada na tomada. Indicada para açougues, padarias, restaurantes e supermercados.",
        "especificacoes": [
            [
                "Capacidade",
                "31 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 31 kg)"
            ],
            [
                "Memória",
                "Até 10.000 itens"
            ],
            [
                "Impressão",
                "Térmica embutida, velocidade ajustável; etiquetas com gap de 30 a 60 mm de largura ou papel térmico; cabeça térmica com vida útil de 150 km"
            ],
            [
                "Display",
                "LCD verde com backlight de alto brilho e display matricial verde para o nome do produto e mensagens"
            ],
            [
                "Teclas rápidas",
                "40 teclas, com até 3 produtos cada (até 120 produtos)"
            ],
            [
                "Códigos",
                "EAN-13, EAN-8, UPC-A, UPC-E, EAN-128, Code 128 (controle de validade), ITF25, Code 39, Code 93, PDF417 e QR Code"
            ],
            [
                "Conectividade",
                "Ethernet, ou Wi-Fi e Ethernet na mesma balança, conforme o modelo; IP fixo ou DHCP; carga também por pen drive"
            ],
            [
                "Cadastro",
                "Tabela nutricional, fornecedor, receitas, alérgenos e tara predeterminada; até 300 imagens e 99 modelos de etiqueta"
            ],
            [
                "Prato",
                "Aço inoxidável, 320 x 220 mm"
            ]
        ],
        "imagem": "urano-ba37-760913165c.webp",
        "recorte": "urano-ba37-760913165c.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "toledo-prix-3-fit",
        "nome": "Prix 3 Fit",
        "marca": "Toledo",
        "categoria": "balancas",
        "subcategoria": "Computadoras",
        "resumo": "Balança computadora de 32 kg com bateria de até 167 horas, para o pequeno varejo.",
        "descricao": "Pesa e calcula o preço na hora, com display LCD de iluminação de fundo verde. A bateria interna recarregável garante até 167 horas de uso sem energia elétrica, então as vendas continuam em quedas de luz e em feiras ao ar livre. O prato de aço inoxidável tem centro rebaixado, que evita o escoamento de líquidos, e é fácil de higienizar. Indicada para mercados, padarias, quitandas, lojas de conveniência e feiras livres.",
        "especificacoes": [
            [
                "Capacidade",
                "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)"
            ],
            [
                "Display",
                "LCD alfanumérico com backlight verde"
            ],
            [
                "Bateria",
                "Interna recarregável, até 167 horas de autonomia"
            ],
            [
                "Prato",
                "Aço inoxidável com centro rebaixado, 300 x 230 mm"
            ],
            [
                "Saída de dados",
                "RS-232C, USB ou Bluetooth (opcionais); conexão com a impressora IT400M"
            ]
        ],
        "imagem": "prix3fit.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": true
    },
    {
        "id": "toledo-prix-3-plus",
        "nome": "Prix 3 Plus",
        "marca": "Toledo",
        "categoria": "balancas",
        "subcategoria": "Computadoras",
        "resumo": "Balança computadora de 32 kg com prato amplo em inox e bateria de até 490 horas.",
        "descricao": "Pesa e calcula o preço na hora, com prato de aço inoxidável amplo (355 x 235 mm), que acomoda melhor os produtos. A bateria interna recarregável garante até 490 horas de uso sem energia elétrica, e a função de pré-empacotamento mantém fixos o preço por quilo e a tara entre as pesagens. Pode ser ligada à impressora IT400M para emitir comandas ou etiquetas. Indicada para mercados, padarias, quitandas, lojas de conveniência e feiras livres.",
        "especificacoes": [
            [
                "Capacidade",
                "32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg)"
            ],
            [
                "Bateria",
                "Interna recarregável, até 490 horas de autonomia"
            ],
            [
                "Display",
                "LCD alfanumérico com backlight verde"
            ],
            [
                "Prato",
                "Aço inoxidável, 355 x 235 mm"
            ],
            [
                "Saída de dados",
                "RS-232C, USB ou Bluetooth (opcionais); conexão com a impressora IT400M"
            ]
        ],
        "imagem": "prix3plus.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "balmak-one",
        "nome": "ONE",
        "marca": "Balmak",
        "categoria": "balancas",
        "subcategoria": "Computadoras",
        "resumo": "Balança computadora de preços com painéis inclinados, de 15 ou 30 kg, e bateria opcional.",
        "descricao": "Pesa e calcula o preço na hora, mostrando peso, preço unitário e valor total em displays LCD extragrandes com iluminação de fundo, nos lados do operador e do cliente. Os painéis são inclinados, o que dá mais conforto no uso. O prato é amplo (33,5 x 23,5 cm), e a versão com bateria interna funciona até 100 horas sem tomada. Tem saída serial RS-232 opcional para integração com a automação comercial.",
        "especificacoes": [
            [
                "Capacidade",
                "15 kg (divisão de 5 g) ou 30 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 30 kg)"
            ],
            [
                "Display",
                "LCD extragrande de alta resolução com backlight, nos lados do operador e do consumidor; mostra peso, preço unitário e preço total"
            ],
            [
                "Bateria",
                "Interna recarregável (opcional), até 100 horas de autonomia"
            ],
            [
                "Prato",
                "33,5 x 23,5 cm"
            ],
            [
                "Saída serial",
                "RS-232 (DB-9) opcional, para integração com automação comercial"
            ]
        ],
        "imagem": "balmak-one.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "toledo-9094-plus",
        "nome": "Prix 9094 Plus",
        "marca": "Toledo",
        "categoria": "balancas",
        "subcategoria": "Pesadoras",
        "resumo": "Pesadora compacta até 32 kg, com bateria de até 480 h e saída para o PDV.",
        "descricao": "Balança pesadora compacta para o varejo e a indústria, com dois displays LCD com backlight, um para o operador e outro para o cliente. A bateria interna recarregável permite pesar com e sem energia elétrica, e a tara sucessiva facilita o preparo de receitas. O prato de aço inoxidável tem centro rebaixado para evitar que líquidos escorram sobre os displays. Conecta-se ao PDV e a sistemas de automação via RS-232C ou USB (opcional).",
        "especificacoes": [
            [
                "Capacidade / Divisão",
                "3/6/15 kg x 1/2/5 g ou 6/15/32 kg x 2/5/10 g"
            ],
            [
                "Display",
                "2 displays LCD com backlight (operador e consumidor)"
            ],
            [
                "Bateria",
                "Interna recarregável, autonomia de até 480 h"
            ],
            [
                "Conectividade",
                "RS-232C ou USB (opcional) para PDV, microterminais, sistemas de automação e impressoras"
            ],
            [
                "Prato",
                "Aço inoxidável com centro rebaixado, 355 x 235 mm"
            ]
        ],
        "imagem": "9094plus.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "balmak-one-pesadora",
        "nome": "ONE Pesadora",
        "marca": "Balmak",
        "categoria": "balancas",
        "subcategoria": "Pesadoras",
        "resumo": "Pesadora com display LCD com backlight, prato amplo e saída RS-232 opcional.",
        "descricao": "Balança pesadora da linha ONE para o comércio. Os painéis do operador e do cliente são inclinados, o que dá mais conforto e ergonomia na leitura. O prato amplo, de plástico ABS e inox espelhado, tem abas contra o escoamento de líquidos e é fácil de higienizar. Como opcionais, a balança aceita bateria interna recarregável e saída serial RS-232 para integração com a automação comercial.",
        "especificacoes": [
            [
                "Capacidade / Divisão",
                "5/10 kg x 1/2 g ou 6/15/30 kg x 2/5/10 g"
            ],
            [
                "Display",
                "LCD extragrande de alta resolução com backlight, nos lados do operador e do cliente"
            ],
            [
                "Prato",
                "33,5 x 23,5 cm, plástico ABS injetado e aço inox espelhado, com abas contra escoamento de líquidos"
            ],
            [
                "Conectividade",
                "Serial RS-232 (DB-9), opcional"
            ],
            [
                "Bateria",
                "Opcional: interna recarregável, autonomia de até 100 h"
            ]
        ],
        "imagem": "one-pesadora.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "toledo-8217",
        "nome": "Prix 8217 Checkout",
        "marca": "Toledo",
        "categoria": "balancas",
        "subcategoria": "Checkout",
        "resumo": "Balança de checkout 32 kg x 5 g para embutir no caixa e integrar ao PDV.",
        "descricao": "Balança para embutir no checkout de atacados, supermercados e sacolões. Ela pesa frutas, legumes e verduras direto no caixa e reduz gastos com etiquetagem no FLV. O display em torre gira na horizontal para facilitar a leitura do operador e do cliente. A saída RS-232 ou USB é compatível com a maioria dos softwares de frente de caixa, e o formato compacto permite integrar leitores de código de barras de leitura horizontal.",
        "especificacoes": [
            [
                "Capacidade / Divisão",
                "32 kg x 5 g"
            ],
            [
                "Comunicação",
                "RS-232C ou USB"
            ],
            [
                "Prato",
                "Aço inoxidável AISI 304, com 4 rampas"
            ],
            [
                "Display",
                "Remoto em torre, giro horizontal de 45° em 45°"
            ],
            [
                "Versões",
                "Standard (292,2 x 335 mm) e Atacarejo (375 x 425, 500 x 300, 500 x 500 ou 600 x 300 mm)"
            ]
        ],
        "imagem": "8217.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "balmak-bck30",
        "nome": "BCK-30 Checkout",
        "marca": "Balmak",
        "categoria": "balancas",
        "subcategoria": "Checkout",
        "resumo": "Balança de checkout 30 kg x 5 g com saídas RS-232 e USB para o PDV.",
        "descricao": "Balança compacta para embutir no balcão do caixa: pesa frutas, legumes, verduras e pães no próprio checkout e reduz custos com etiquetagem dentro da loja. O prato em inox AISI 304, com 4 rampas, facilita o deslizamento dos produtos. As saídas RS-232 e USB integram o peso ao sistema de frente de caixa, e a balança aceita, como opcional, leitor de código de barras embutido.",
        "especificacoes": [
            [
                "Capacidade / Divisão",
                "30 kg x 5 g (classe III)"
            ],
            [
                "Prato",
                "Inox AISI 304, com 4 rampas para facilitar o deslizamento dos produtos"
            ],
            [
                "Dimensões do prato",
                "270 x 334 mm"
            ],
            [
                "Display",
                "LCD remoto com backlight, em mastro"
            ],
            [
                "Conectividade",
                "Saídas seriais RS-232 e USB"
            ],
            [
                "Alimentação",
                "Adaptador full range automático ou porta USB"
            ]
        ],
        "imagem": "bck30.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "toledo-2098",
        "nome": "Prix 2098 / 2098C",
        "marca": "Toledo",
        "categoria": "balancas",
        "subcategoria": "Industriais e conferência",
        "resumo": "Balança de bancada inox até 300 kg para recebimento e conferência.",
        "descricao": "Linha de balanças de bancada com plataforma em inox AISI 304, para pesagem e conferência no varejo alimentício e em processos industriais, como recebimento, expedição, almoxarifado e produção. Há versões com coluna articulada ou indicador remoto, e as configurações com bateria recarregável funcionam temporariamente sem energia elétrica. A 2098C acrescenta contagem de peças, verificação de peso por faixas de tolerância e acumulação de resultados.",
        "especificacoes": [
            [
                "Capacidade / Divisão",
                "2098: 30/60 kg x 5/10 g ou 120/300 kg x 20/50 g; 2098C: 32 kg x 5 g, 60 kg x 10 g, 120 kg x 20 g ou 300 kg x 50 g"
            ],
            [
                "Plataforma",
                "Inox AISI 304 escovado, 425 x 375 mm ou 500 x 500 mm"
            ],
            [
                "Instalação",
                "Coluna articulada (0,5 m ou 0,8 m) ou indicação remota sobre mesa ou parede"
            ],
            [
                "Comunicação",
                "RS-232C opcional; USB por conversor opcional"
            ],
            [
                "Grau de proteção",
                "IP40 (2098) / IP54 (2098C)"
            ]
        ],
        "imagem": "2098.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": true
    },
    {
        "id": "balmak-w300",
        "nome": "K-300",
        "marca": "Balmak",
        "categoria": "balancas",
        "subcategoria": "Industriais e conferência",
        "resumo": "Balança de plataforma 300 kg x 50 g, tampa inox e indicador BK-10000.",
        "descricao": "Balança de plataforma para mercados, armazéns e indústrias. A plataforma de 50 x 50 cm tem estrutura em aço carbono e tampa em aço inox. O indicador BK-10000, com display LED vermelho de 6 dígitos, tem filtro digital que estabiliza a pesagem em ambientes com vibração. As versões com bateria interna recarregável funcionam sem estar ligadas à tomada.",
        "especificacoes": [
            [
                "Capacidade / Divisão",
                "300 kg x 50 g"
            ],
            [
                "Plataforma",
                "50 x 50 cm, estrutura em aço carbono e tampa em aço inox"
            ],
            [
                "Indicador",
                "BK-10000, IP-65, display LED vermelho de 6 dígitos"
            ],
            [
                "Bateria",
                "Interna recarregável, até 25 h (Série Standard, K-300IB-3/S)"
            ],
            [
                "Instalação",
                "Indicador remoto (Série Standard) ou coluna articulável com rodízios (Série Plus, K-300ICB-3/P)"
            ]
        ],
        "imagem": "w300.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "toledo-hospitalar",
        "nome": "Prix 2096PP",
        "marca": "Toledo",
        "categoria": "balancas",
        "subcategoria": "Médico-hospitalar",
        "resumo": "Balança para pesar pessoas, 200 kg x 50 g, para farmácias e clínicas.",
        "descricao": "Balança eletrônica para pesar pessoas em farmácias, hospitais, clínicas, consultórios e academias, com display LCD com backlight. A função tara desconta a cadeira, o banco ou outro apoio do paciente, e a superfície com borracha antiderrapante e pés niveladores dá segurança na pesagem. A régua antropométrica e a saída RS-232C são opcionais.",
        "especificacoes": [
            [
                "Capacidade / Divisão",
                "200 kg x 50 g"
            ],
            [
                "Display",
                "LCD com backlight, 6 dígitos de 26 mm de altura"
            ],
            [
                "Plataforma",
                "400 x 400 mm, com borracha antiderrapante"
            ],
            [
                "Opcionais",
                "Régua antropométrica e saída RS-232C"
            ],
            [
                "Aprovação",
                "Inmetro, Portaria 236/94, classe de exatidão III"
            ]
        ],
        "imagem": "hospitalar.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "balmak-bk200f",
        "nome": "BK-200F",
        "marca": "Balmak",
        "categoria": "balancas",
        "subcategoria": "Médico-hospitalar",
        "resumo": "Balança de coluna para pessoas, 200 kg x 100 g, com display LED.",
        "descricao": "Balança digital de coluna para pesar pessoas em farmácias, academias, clínicas e consultórios. O display LED vermelho tem 6 dígitos. O tapete na plataforma e os pés antiderrapantes de borracha dão mais segurança ao usuário, e a tampa da plataforma pode ser retirada para limpeza. Nas versões com antropômetro (FA/FAN), a régua retrátil mede a altura junto com o peso.",
        "especificacoes": [
            [
                "Capacidade / Divisão",
                "200 kg x 100 g"
            ],
            [
                "Display",
                "LED vermelho, 6 dígitos"
            ],
            [
                "Plataforma",
                "40 x 40 cm, chapa de aço carbono 1020"
            ],
            [
                "Alimentação",
                "Fonte externa full range, 90-250 Vca, 50/60 Hz"
            ],
            [
                "Antropômetro (versões FA/FAN)",
                "Régua retrátil de alumínio anodizado, até 2 m, graduação de 0,5 cm"
            ]
        ],
        "imagem": "bk200f.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "elgin-l42-pro",
        "nome": "L42 Pro",
        "marca": "Elgin",
        "categoria": "automacao",
        "subcategoria": "Impressoras de etiquetas",
        "resumo": "Impressora de etiquetas térmica direta ou com ribbon, 203 dpi, até 102 mm/s.",
        "descricao": "Imprime etiquetas de produtos, etiquetas de gôndola e códigos de barras em papel térmico ou com ribbon (transferência térmica). Reconhece automaticamente as linguagens EPL, ZPL, PPLA e PPLB, o que facilita a integração com o sistema de gestão da loja. Vem com o software BarTender UltraLite para criar e imprimir etiquetas.",
        "especificacoes": [
            [
                "Impressão",
                "Transferência térmica (ribbon) ou térmica direta"
            ],
            [
                "Resolução",
                "203 dpi (cabeça de 300 dpi opcional)"
            ],
            [
                "Velocidade",
                "Até 102 mm/s (cerca de 4 pol/s)"
            ],
            [
                "Largura de etiqueta",
                "20 a 111 mm (área de impressão de até 108 mm a 203 dpi)"
            ],
            [
                "Conectividade",
                "L42 Pro: USB + 1 interface opcional (serial, Ethernet ou paralela); L42 Pro Full: USB, serial e Ethernet"
            ],
            [
                "Linguagens",
                "PPLA, PPLB, EPL/EPL2 e ZPL/ZPL2, com reconhecimento automático"
            ],
            [
                "Software incluso",
                "BarTender UltraLite, para criar e imprimir etiquetas"
            ]
        ],
        "imagem": "l42pro.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": true
    },
    {
        "id": "argox-os-214-pro",
        "nome": "OS-214 Pro",
        "marca": "Argox",
        "categoria": "automacao",
        "subcategoria": "Impressoras de etiquetas",
        "resumo": "Impressora de etiquetas de mesa, compacta, para papel térmico ou com ribbon: 203 dpi, até 127 mm/s e 128 MB de memória Flash.",
        "descricao": "Impressora de mesa que ocupa pouco espaço, para etiquetas de produtos, preços e códigos de barras, com impressão térmica direta ou por transferência térmica (ribbon). Imprime até 5 polegadas (127 mm) por segundo em etiquetas de 25,4 a 110 mm de largura, aceita rolos de até 109 mm de diâmetro e ribbon de até 92 m, e tem 128 MB de memória Flash e 128 MB de SDRAM. Imprime códigos de barras 1D, GS1 DataBar, QR Code e Data Matrix e aceita as emulações PPLA, PPLB e PPLZ. A ligação com o computador é só por USB, com drivers da Argox para Windows, Linux e macOS. O mecanismo de impressão é todo em metal, a carcaça é de plástico ABS de alto impacto e a cabeça de impressão é de encaixe e se coloca em 2 segundos.",
        "especificacoes": [
            [
                "Impressão",
                "Térmica direta e transferência térmica (ribbon); 203 dpi (8 pontos/mm)"
            ],
            [
                "Velocidade",
                "Até 127 mm/s (5 pol/s)"
            ],
            [
                "Conectividade",
                "USB 2.0"
            ],
            [
                "Largura de impressão",
                "Até 105 mm (etiqueta de 25,4 a 110 mm)"
            ],
            [
                "Rolo e ribbon",
                "Rolo de até 109 mm de diâmetro externo; ribbon de até 92 m"
            ],
            [
                "Memória",
                "128 MB de Flash e 128 MB de SDRAM"
            ],
            [
                "Linguagens",
                "Emulações PPLA, PPLB e PPLZ"
            ],
            [
                "Construção",
                "Mecanismo de impressão todo em metal; carcaça em ABS de alto impacto"
            ],
            [
                "Dimensões e peso",
                "186 x 278 x 165 mm (L x P x A); 2,0 kg"
            ]
        ],
        "imagem": "argox-os-214-pro-0de4cd873a.webp",
        "recorte": "argox-os-214-pro-0de4cd873a.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "zebra-zt231",
        "nome": "ZT231",
        "marca": "Zebra",
        "categoria": "automacao",
        "subcategoria": "Impressoras de etiquetas",
        "resumo": "Impressora de etiquetas industrial de 4 polegadas, até 305 mm/s, com tela colorida sensível ao toque e rolos de até 203 mm de diâmetro.",
        "descricao": "Impressora de etiquetas da linha industrial ZT200 da Zebra, com estrutura e gabinete de metal, feita para uso industrial leve, como etiquetas de preço, de paletes, de expedição e de recebimento de mercadorias. Imprime até 12 polegadas (305 mm) por segundo a 203 dpi, ou com textos e códigos mais nítidos usando a cabeça opcional de 300 dpi, e aceita rolos de etiqueta de até 203 mm de diâmetro externo e, na versão com transferência térmica, ribbon de 450 m, o que diminui as trocas de material. A tela colorida sensível ao toque de 4,3\" mostra o estado da impressora e traz assistentes e vídeos de ajuda. Já vem com USB, serial, Ethernet e USB host, e aceita depois, instalados no local, Wi-Fi, guilhotina, destacador e codificação RFID. Usa a linguagem ZPL, compatível com todas as impressoras Zebra, e EPL nos modelos de 203 dpi.",
        "especificacoes": [
            [
                "Impressão",
                "Térmica direta ou com ribbon (transferência térmica, conforme a versão); 203 dpi (300 dpi opcional)"
            ],
            [
                "Velocidade",
                "Até 305 mm/s (12 pol/s) a 203 dpi; até 203 mm/s (8 pol/s) a 300 dpi"
            ],
            [
                "Conectividade",
                "USB 2.0, serial RS-232, Ethernet 10/100, USB host e Bluetooth Low Energy; opcionais: Wi-Fi 802.11ac e segunda Ethernet 10/100"
            ],
            [
                "Tela",
                "Colorida sensível ao toque, de 4,3\""
            ],
            [
                "Largura de impressão",
                "Até 104 mm (etiqueta de 19,4 a 114 mm)"
            ],
            [
                "Rolo de etiquetas",
                "Até 203 mm de diâmetro externo (tubete de 76 mm); ribbon de 450 m na versão com transferência térmica"
            ],
            [
                "Memória",
                "256 MB de SDRAM e 256 MB de Flash"
            ],
            [
                "Linguagens",
                "ZPL e ZPL II; EPL e EPL2 (só com cabeça de 203 dpi)"
            ],
            [
                "Dimensões e peso",
                "241 x 432 x 279 mm (L x P x A); 9,1 kg"
            ]
        ],
        "imagem": "zebra-zt231-80d6bfb607.webp",
        "recorte": "zebra-zt231-80d6bfb607.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "tanca-tl-120",
        "nome": "TL-120",
        "marca": "Tanca",
        "categoria": "automacao",
        "subcategoria": "Leitores de código de barras",
        "resumo": "Leitor de mão 1D (CCD) USB, 300 scans/s; lê produtos, DANFE e boletos.",
        "descricao": "Leitor de código de barras de mão para o caixa, com instalação plug and play pela porta USB. Faz 300 varreduras por segundo e lê códigos de produtos, DANFE, faturas e boletos bancários, o que agiliza as operações do dia a dia.",
        "especificacoes": [
            [
                "Tecnologia",
                "CCD de imagem linear (códigos 1D)"
            ],
            [
                "Velocidade",
                "300 varreduras (scans) por segundo"
            ],
            [
                "Profundidade de campo",
                "Até 33 cm"
            ],
            [
                "Conectividade",
                "USB (cabo de 2 m esticado)"
            ],
            [
                "Resistência a queda",
                "1,5 m"
            ]
        ],
        "imagem": "tl120.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "tanca-tl-220",
        "nome": "TL-220",
        "marca": "Tanca",
        "categoria": "automacao",
        "subcategoria": "Leitores de código de barras",
        "resumo": "Leitor laser 1D USB com pedestal articulável e sensor automático de leitura.",
        "descricao": "Leitor laser de código de barras de mão com pedestal articulável e sensor automático de leitura: o operador pode ler os produtos com o leitor apoiado no pedestal ou na mão. Lê os principais códigos 1D, notas DANFE e boletos no padrão Febraban, com 100 varreduras por segundo e profundidade de campo de até 35 cm.",
        "especificacoes": [
            [
                "Tecnologia",
                "Laser vermelho de 650 nm (códigos 1D)"
            ],
            [
                "Velocidade",
                "100 varreduras (scans) por segundo"
            ],
            [
                "Profundidade de campo",
                "Até 35 cm (largura de campo de 220 mm)"
            ],
            [
                "Conectividade",
                "USB (cabo de 2 m esticado)"
            ],
            [
                "Suporte",
                "Pedestal articulável com sensor automático de leitura"
            ]
        ],
        "imagem": "tl220.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "datalogic-quickscan-qw2500",
        "nome": "QuickScan QW2500",
        "marca": "Datalogic",
        "categoria": "automacao",
        "subcategoria": "Leitores de código de barras",
        "resumo": "Leitor de mão 2D com fio USB: lê códigos de barras e QR Code em qualquer direção, com vedação IP52 e resistência a quedas de 1,5 m.",
        "descricao": "Leitor de mão com fio, o modelo mais simples da série QuickScan 2500 da Datalogic, feito para o caixa do varejo. O sensor de imagem 2D lê os códigos 1D mais comuns, inclusive GS1 DataBar, e códigos 2D como QR Code, Data Matrix e PDF417, em qualquer direção, sem precisar alinhar o produto, e também lê códigos na tela do celular. Um ponto azul mostra para onde o leitor aponta, e cada leitura é confirmada por bipe e pelo “Green Spot”, uma luz verde projetada sobre o código, útil em ambiente barulhento. O sensor de movimento Motionix deixa o leitor pronto assim que é pego e, apoiado no suporte, ele pode ser configurado para ler sozinho os códigos apresentados. Tem vedação IP52, resiste a quedas de 1,5 m no concreto e o gatilho aguenta 10 milhões de acionamentos.",
        "especificacoes": [
            [
                "Leitura",
                "1D e 2D, como QR Code; em qualquer direção, com imager 2D VGA (640 x 480 pixels)"
            ],
            [
                "Conectividade",
                "USB; código do modelo com cabo USB: QW2520"
            ],
            [
                "Profundidade de campo",
                "1 a 40 cm (EAN/UPC de 13 mils); 1 a 33 cm (QR Code de 20 mils)"
            ],
            [
                "Códigos lidos",
                "1D (inclusive GS1 DataBar), QR Code, Micro QR, Data Matrix, Aztec, MaxiCode, PDF417 e códigos postais"
            ],
            [
                "Mira e confirmação",
                "Ponto azul de mira; bipe ajustável e “Green Spot” (luz verde sobre o código) a cada leitura"
            ],
            [
                "Robustez",
                "Vedação IP52; resiste a quedas repetidas de 1,5 m no concreto; gatilho para 10 milhões de acionamentos"
            ],
            [
                "Dimensões e peso",
                "68 x 126 x 148 mm (L x P x A); 145 g sem o cabo"
            ]
        ],
        "imagem": "datalogic-quickscan-qw2500-d1abf2d727.webp",
        "recorte": "datalogic-quickscan-qw2500-d1abf2d727.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "elgin-el8600ia",
        "nome": "EL8600iA",
        "marca": "Elgin",
        "categoria": "automacao",
        "subcategoria": "Leitores de código de barras",
        "resumo": "Leitor fixo 2D para o checkout, com inteligência artificial para códigos danificados ou com respingos d’água e tolerância a produtos passando a até 8 m/s.",
        "descricao": "Leitor fixo de mesa para o caixa: o operador passa o produto na frente do leitor, sem precisar alinhar o código, e ele lê códigos 1D e 2D, como o QR Code. Tem inteligência artificial para ler códigos borrados, danificados ou com respingos d’água, o que evita digitar o código à mão, e tolera produtos passando a até 8 m/s. O pedestal ajustável permite inclinar o leitor para a posição mais confortável para o operador. É ligado e alimentado pela porta USB, sem fonte externa (a ligação serial RS-232, com cabo vendido à parte, é sob consulta), e tem um modo, que pode ser ativado na configuração, para ler códigos na tela do celular. Tem vedação IP52, resiste a quedas de 1,5 m e a Elgin dá 3 anos de garantia, contados da nota fiscal de compra.",
        "especificacoes": [
            [
                "Leitura",
                "1D e 2D, com inteligência artificial; lê por imagem (1280 x 1024 pixels) códigos danificados, borrados ou com respingos d’água"
            ],
            [
                "Conectividade",
                "USB 2.0 (cabo de 2 m), alimentado pela USB; serial RS-232 sob consulta (cabo vendido à parte)"
            ],
            [
                "Tolerância ao movimento",
                "Até 8 m/s"
            ],
            [
                "Códigos lidos",
                "EAN-13, EAN-8, UPC, Code 128, GS1-128, Code 39, Interleaved 2 of 5, GS1 DataBar, QR Code, Data Matrix, PDF417 e Aztec, entre outros"
            ],
            [
                "Profundidade de leitura",
                "5 a 180 mm (UPC de 13 mils); 10 a 180 mm (QR Code de 20 mils)"
            ],
            [
                "Robustez",
                "Vedação IP52; resiste a quedas de 1,5 m; janela de vidro temperado"
            ],
            [
                "Pedestal",
                "Ajustável, com regulagem de inclinação"
            ],
            [
                "Dimensões",
                "153 x 112 x 187 mm (L x P x A, com pedestal)"
            ]
        ],
        "imagem": "elgin-el8600ia-3811a63d90.webp",
        "recorte": "elgin-el8600ia-3811a63d90.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": true
    },
    {
        "id": "tanca-tl-900",
        "nome": "TL-900",
        "marca": "Tanca",
        "categoria": "automacao",
        "subcategoria": "Leitores de código de barras",
        "resumo": "Leitor fixo 2D omnidirecional para o caixa: lê códigos de barras e QR Code.",
        "descricao": "Leitor fixo com sensor de imagem CMOS que lê códigos de barras 1D e 2D, como o QR Code, em qualquer direção, sem precisar alinhar o produto. É ligado e alimentado pela própria porta USB, sem fonte externa. Indicado para o caixa de mercados, padarias e açougues.",
        "especificacoes": [
            [
                "Leitura",
                "1D e 2D omnidirecional (sensor CMOS)"
            ],
            [
                "Códigos lidos",
                "EAN, UPC, Code 128, Code 39, Interleaved 2 of 5, QR Code, PDF417, Data Matrix, Aztec, entre outros"
            ],
            [
                "Conectividade",
                "USB (cabo de 2 m), alimentação 5 V pela USB"
            ],
            [
                "Profundidade de leitura",
                "Até 220 mm (UPC-A 13 mil)"
            ]
        ],
        "imagem": "tl900.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "gaveta-menno",
        "nome": "Gaveta MG 41",
        "marca": "Menno",
        "categoria": "automacao",
        "subcategoria": "Gavetas de dinheiro",
        "resumo": "Gaveta em aço para o caixa, com abertura pela impressora ou manual por botão.",
        "descricao": "Gaveta com gabinete em aço com pintura eletrostática, 5 divisórias para cédulas, 8 para moedas e porta-moedas removível, para manter o troco organizado no caixa. A versão MG 41B Plus abre automaticamente por comando da impressora (conector RJ12) e tem sensor de gaveta aberta e fechada. A MG 41 CS Eco abre manualmente por botão e serve para caixas sem integração com a impressora.",
        "especificacoes": [
            [
                "Gabinete",
                "Aço com pintura eletrostática"
            ],
            [
                "Compartimentos",
                "5 para cédulas e 8 para moedas, com porta-moedas removível"
            ],
            [
                "Abertura (MG 41B Plus)",
                "Automática por comando da impressora (RJ12, 24 V/12 V), manual por chave e alavanca de emergência"
            ],
            [
                "Sensor (MG 41B Plus)",
                "Gaveta aberta e fechada"
            ],
            [
                "Abertura (MG 41 CS Eco)",
                "Manual por botão (a chave serve só para trancar)"
            ],
            [
                "Dimensões",
                "405 x 420 x 115 mm (L x P x A)"
            ]
        ],
        "imagem": "menno.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "gaveta-bematech",
        "nome": "Gaveta GD-56",
        "marca": "Bematech",
        "categoria": "automacao",
        "subcategoria": "Gavetas de dinheiro",
        "resumo": "Gaveta em aço com abertura pela impressora ou manual, chave e sensor de status.",
        "descricao": "Gaveta de dinheiro em aço galvanizado, com porta-cédulas e moedas removível: 5 nichos para cédulas e 6 para moedas. Abre automaticamente pelo comando da impressora (conector RJ12) e também pode ser aberta manualmente. A fechadura de 3 posições vem com 2 chaves, e o sensor de status indica se a gaveta está aberta ou fechada.",
        "especificacoes": [
            [
                "Material",
                "Aço galvanizado laminado a frio"
            ],
            [
                "Compartimentos",
                "5 nichos para cédulas e 6 para moedas (porta-cédulas e moedas removível)"
            ],
            [
                "Acionamento",
                "Solenoide de 24 V, conector padrão RJ12; fechadura de 3 posições com 2 chaves"
            ],
            [
                "Sensor",
                "Status de gaveta aberta/fechada"
            ],
            [
                "Dimensões",
                "410 x 115 x 415 mm (L x A x P, fechada); altura sem os pés: 100 mm"
            ]
        ],
        "imagem": "gavetabema.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "gertec-tc506e-2d",
        "nome": "Terminal TC506E 2D",
        "marca": "Gertec",
        "categoria": "automacao",
        "subcategoria": "Terminais de consulta",
        "resumo": "Terminal de consulta de preço maior (220 x 135 x 95 mm), com a tela colorida de 3,2\" num painel redondo, leitor de códigos 1D e 2D (lê QR Code) e conexão por Ethernet ou Wi-Fi.",
        "descricao": "Fica no salão da loja para o cliente conferir o preço sozinho, sem ir ao caixa: ele passa o código de barras no leitor, logo abaixo do painel redondo, e a tela colorida mostra o nome e o preço enviados pelo programa de consulta do seu sistema. O leitor lê os códigos de barras comuns (1D) e também códigos 2D, como QR Code e Data Matrix. Liga-se à rede da loja por cabo Ethernet ou por Wi-Fi e é configurado pelo celular Android, com o aplicativo da Gertec via Bluetooth, ou à distância, por um navegador de internet. A tela exibe imagens JPEG e BMP e animações para destacar informações importantes e, enquanto ninguém consulta, mostra as mensagens que você cadastrar; o alto-falante toca áudios WAV, recurso que ajuda na acessibilidade. Vem com fonte e suporte de fixação.",
        "especificacoes": [
            [
                "Leitor",
                "Imager CMOS 1D e 2D; lê QR Code e Data Matrix"
            ],
            [
                "Display",
                "TFT colorido de 3,2\" (320 x 240 px); exibe imagens JPEG e BMP"
            ],
            [
                "Conectividade",
                "Ethernet (RJ-45) e Wi-Fi 2,4 GHz (802.11 b/g/n); Bluetooth 4.2 para configuração"
            ],
            [
                "Configuração",
                "Aplicativo da Gertec para Android (via Bluetooth) ou navegador de internet"
            ],
            [
                "Áudio",
                "Alto-falante de 1,5 W que reproduz arquivos WAV, com recurso de acessibilidade"
            ],
            [
                "Alimentação",
                "5 Vcc, com fonte inclusa (entrada de 100 a 240 Vca, 50/60 Hz)"
            ],
            [
                "Dimensões e peso",
                "220 x 135 x 95 mm (L x P x A); 430 g"
            ],
            [
                "Instalação",
                "Em parede ou superfície vertical, com o suporte de fixação que acompanha"
            ],
            [
                "Garantia",
                "12 meses"
            ]
        ],
        "imagem": "gertec-tc506e-2d-f09c849a3e.webp",
        "recorte": "gertec-tc506e-2d-f09c849a3e.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "gertec-busca-preco-2d",
        "nome": "Busca Preço 2D",
        "marca": "Gertec",
        "categoria": "automacao",
        "subcategoria": "Terminais de consulta",
        "resumo": "Terminal de consulta de preço compacto e leve (140 x 100 x 40 mm, 250 g), com tela colorida de 3,2\", leitor de códigos 1D e 2D, Ethernet e Wi-Fi e aba personalizável no alto.",
        "descricao": "Versão atual do Busca Preço da Gertec, que agora lê os códigos de barras comuns (1D) e também códigos 2D. Compacto e leve (250 g), fica no salão da loja: o cliente passa o produto no leitor e vê o preço na tela colorida, sem precisar ir ao caixa. Ajuda a loja a cumprir o Decreto 5.903/2006, que exige leitores de preço a no máximo 15 metros de qualquer produto quando os preços são informados por código de barras. Liga-se à rede por Ethernet ou Wi-Fi, e o Bluetooth serve para configurá-lo pelo aplicativo da Gertec no celular. A tela mostra imagens e propagandas durante a consulta ou na espera, o alto-falante toca áudios WAV (recurso de acessibilidade) e a aba de cima é personalizável; vem com fonte e suporte de fixação.",
        "especificacoes": [
            [
                "Leitor",
                "Códigos de barras 1D e 2D"
            ],
            [
                "Display",
                "TFT colorido de 3,2\" (320 x 240 px, 256 mil cores); exibe imagens JPEG e BMP"
            ],
            [
                "Conectividade",
                "Ethernet e Wi-Fi 2,4 GHz (802.11 b/g/n); Bluetooth 4.2 para configuração"
            ],
            [
                "Áudio",
                "Alto-falante que reproduz arquivos WAV, com recurso de acessibilidade"
            ],
            [
                "Alimentação",
                "5 Vcc, com fonte inclusa (110/220 V)"
            ],
            [
                "Dimensões e peso",
                "140 x 100 x 40 mm (L x P x A); 250 g"
            ],
            [
                "Montagem",
                "Suporte de fixação incluso; aba personalizável"
            ],
            [
                "Garantia",
                "12 meses"
            ]
        ],
        "imagem": "gertec-busca-preco-2d-d2a071e35e.webp",
        "recorte": "gertec-busca-preco-2d-d2a071e35e.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "gertec-mt-721",
        "nome": "Microterminal MT-721 VT100",
        "marca": "Gertec",
        "categoria": "automacao",
        "subcategoria": "Teclados e microterminais",
        "resumo": "Microterminal em rede Ethernet com display de 2 linhas, 20 teclas programáveis e protocolos VT100 e STX/ETX, para comandas e cartões de consumo.",
        "descricao": "Microterminal ligado ao computador da loja pela rede Ethernet: todo o processamento fica no sistema, e o aparelho serve para digitar e mostrar informações no ponto de atendimento. É indicado para cartões de consumo e comandas em padarias, bares, lanchonetes e restaurantes self-service, e também para livrarias, papelarias e apontamento de produção na indústria. Tem display de cristal líquido de 2 linhas por 20 caracteres, com luz de fundo, e 20 teclas programáveis e relegendáveis, protegidas por uma película de silicone contra poeira e líquidos. Funciona com os protocolos VT100 e STX/ETX, além do protocolo da própria Gertec, e as 2 portas seriais e as 2 USB permitem ligar periféricos, como um teclado auxiliar, conforme o seu sistema. Vários microterminais podem ser ligados ao mesmo computador pela rede.",
        "especificacoes": [
            [
                "Teclado",
                "20 teclas programáveis; relegendáveis e com película protetora de silicone"
            ],
            [
                "Rede",
                "Ethernet 10/100 Mbps (RJ-45), TCP/IP"
            ],
            [
                "Protocolos",
                "VT100 e STX/ETX, além do protocolo Gertec"
            ],
            [
                "Display",
                "Cristal líquido de 2 linhas x 20 caracteres, com backlight"
            ],
            [
                "Portas",
                "2 seriais RJ11 (acompanha cabo adaptador para DB9) e 2 USB"
            ],
            [
                "Alimentação",
                "5 Vcc, com fonte inclusa (90 a 240 V, chaveamento automático)"
            ],
            [
                "Dimensões e peso",
                "130 x 160 x 50 mm (L x P x A); 690 g"
            ],
            [
                "Garantia",
                "12 meses"
            ]
        ],
        "imagem": "gertec-mt-721-19e72965bf.webp",
        "recorte": "gertec-mt-721-19e72965bf.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "gertec-tec-44",
        "nome": "Teclado TEC 44",
        "marca": "Gertec",
        "categoria": "automacao",
        "subcategoria": "Teclados e microterminais",
        "resumo": "Teclado de PDV com 44 teclas programáveis e conexão USB para agilizar o caixa.",
        "descricao": "Programe atalhos e funções do sistema nas 44 teclas, com até 4 códigos por tecla, e agilize o atendimento no caixa. A conexão USB é plug and play, o que facilita a instalação no PDV de supermercados, farmácias e lojas de conveniência. É construído para suportar o uso contínuo em ambientes comerciais de alto fluxo.",
        "especificacoes": [
            [
                "Teclas",
                "44 teclas programáveis"
            ],
            [
                "Programação",
                "Até 4 códigos por tecla"
            ],
            [
                "Interface",
                "USB"
            ],
            [
                "Dimensões (L x P x A)",
                "245 x 175 x 70 mm"
            ],
            [
                "Peso",
                "720 g"
            ]
        ],
        "imagem": "tec44.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "smak-sko44",
        "nome": "Teclado Óptico SKO-44",
        "marca": "Smak",
        "categoria": "automacao",
        "subcategoria": "Teclados e microterminais",
        "resumo": "Teclado óptico de PDV com 44 teclas programáveis e vida útil de até 100 milhões de toques.",
        "descricao": "Teclado de PDV com tecnologia óptica: as teclas são lidas pela interrupção de feixes de luz, sem contato mecânico, e a vida útil chega a 100 milhões de toques. As 44 teclas legendáveis e programáveis aceitam até 63 caracteres cada, e a dupla paginação permite alternar entre duas camadas de comandos, o que agiliza o atendimento no caixa. Funciona com Windows e Linux e tem display LCD como opcional.",
        "especificacoes": [
            [
                "Teclas",
                "44 teclas legendáveis e programáveis (até 63 caracteres por tecla)"
            ],
            [
                "Tecnologia",
                "Óptica, por interrupção de feixes de luz (sem contato mecânico nas teclas)"
            ],
            [
                "Vida útil",
                "Até 100 milhões de toques"
            ],
            [
                "Paginação",
                "Dupla paginação (duas camadas de comandos)"
            ],
            [
                "Compatibilidade",
                "Windows e Linux (DLL, SDK e drivers)"
            ]
        ],
        "imagem": "sko44.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "toledo-uni-350-ga",
        "nome": "Fatiador Prix Veloce 350A",
        "marca": "Toledo",
        "categoria": "automacao",
        "subcategoria": "Fatiadores",
        "resumo": "Fatiador automático de frios com lâmina de 350 mm e pré-seleção de 1 a 99 fatias.",
        "descricao": "Fatiador automático para frios: o carro se movimenta sozinho e fraciona a peça enquanto o operador retira as fatias para montar as bandejas. No painel touch, o operador ajusta a velocidade (10 níveis) e o curso de corte (3 níveis) e pré-seleciona de 1 a 99 fatias. É construído em alumínio anodizado e aço inox, com afiador de lâmina integrado e cantos arredondados que facilitam a limpeza.",
        "especificacoes": [
            [
                "Funcionamento",
                "Automático"
            ],
            [
                "Lâmina",
                "350 mm, 200 rpm"
            ],
            [
                "Velocidade de corte",
                "40 a 60 fatias/min (10 níveis), com pré-seleção de 1 a 99 fatias"
            ],
            [
                "Capacidade de corte",
                "285 x 155 mm (retangular), 200 x 200 mm (quadrada), Ø 235 mm (circular)"
            ],
            [
                "Tensão",
                "220 V"
            ]
        ],
        "imagem": "fatiador.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": true
    },
    {
        "id": "toledo-uni-350",
        "nome": "Fatiador Prix Supremo 350S",
        "marca": "Toledo",
        "categoria": "automacao",
        "subcategoria": "Fatiadores",
        "resumo": "Fatiador semiautomático de frios com lâmina de 350 mm para o atendimento no balcão.",
        "descricao": "Fatiador semiautomático para frios, indicado para o atendimento direto ao cliente no balcão ou no ponto de venda. A lâmina de 350 mm tem afiador integrado, e a espessura do corte é ajustada por manopla, com abertura de até 15 mm. A estrutura em alumínio anodizado e aço inox tem cantos arredondados que facilitam a higienização, e os pés ajustáveis permitem posicioná-lo em balcões de vários tamanhos.",
        "especificacoes": [
            [
                "Funcionamento",
                "Semiautomático"
            ],
            [
                "Lâmina",
                "350 mm, 200 rpm"
            ],
            [
                "Abertura máxima de corte",
                "15 mm"
            ],
            [
                "Capacidade de corte",
                "285 x 155 mm (retangular), 200 x 200 mm (quadrada), Ø 235 mm (circular)"
            ],
            [
                "Material / proteção",
                "Alumínio anodizado e aço inox; IP54 e IP69"
            ]
        ],
        "imagem": "uni350.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "toledo-mit",
        "nome": "Mídia Digital Prix MIT 7",
        "marca": "Toledo",
        "categoria": "automacao",
        "subcategoria": "Tabelas digitais",
        "resumo": "Exibe preços, ofertas e chamadas de senha nas TVs da loja, com integração ao MGV da Toledo.",
        "descricao": "Mostra nas TVs da loja tabelas de preços, ofertas, anúncios e chamadas de senha, com o conteúdo gerenciado pelo software MIT 7 em um computador com Windows. A integração com o MGV da Toledo compartilha o cadastro de produtos e preços e reduz o retrabalho na hora de atualizar as telas. As senhas aparecem em pop-up, com filas de até três departamentos e atendimento preferencial na mesma TV.",
        "especificacoes": [
            [
                "Tabela de preços",
                "De 12 a 25 linhas; até 68 preços por tela (tabela de itens compostos, 17 linhas x 4 colunas)"
            ],
            [
                "Chamada de senha",
                "Pop-up, até 3 departamentos por TV, com senha preferencial"
            ],
            [
                "Player",
                "MIT Player W: mini PC Android, saída HDMI, 1920 x 1080"
            ],
            [
                "Rede",
                "Ethernet 100/1000 Mbps"
            ],
            [
                "Integração",
                "MGV (cadastro de produtos e preços) e aplicativo de emissão de senhas para totens"
            ]
        ],
        "imagem": "mit.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "balmak-all-midia",
        "nome": "All-Mídia",
        "marca": "Balmak",
        "categoria": "automacao",
        "subcategoria": "Tabelas digitais",
        "resumo": "Tabela de preços e ofertas em TV Full HD, integrada às balanças Balmak Órion.",
        "descricao": "Mostra nas TVs da loja tabelas de preços, ofertas, imagens e vídeos em Full HD, com programação configurável de forma diferente para cada setor da loja. Integrado às balanças Balmak Órion pelo software DFS, usa o mesmo banco de dados das balanças: o preço alterado na balança muda sozinho na tabela digital, sem importar arquivos. Também funciona de forma independente (stand-alone) e, com as balanças Órion, organiza filas com chamada de senha.",
        "especificacoes": [
            [
                "Resolução",
                "Full HD 1920 x 1080"
            ],
            [
                "Player",
                "Mini PC com saída HDMI e Wi-Fi 802.11 b/g/n integrado"
            ],
            [
                "Integração",
                "Balanças Órion via software DFS (atualização automática de preços)"
            ],
            [
                "Chamada de senha",
                "Organizador de fila com senha, operando com balanças Órion"
            ],
            [
                "Operação independente",
                "Pode ser usado sem integração com balanças (stand-alone)"
            ]
        ],
        "imagem": "allmidia.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "centrium-mini-pc",
        "nome": "Mini PC para PDV",
        "marca": "Centrium",
        "categoria": "informatica",
        "subcategoria": "Computadores",
        "resumo": "Computador compacto para o caixa, que libera espaço no balcão.",
        "descricao": "Computador compacto para rodar o sistema de vendas na frente de caixa, ocupando pouco espaço no balcão. A configuração (processador, memória, armazenamento e portas) varia conforme a disponibilidade em estoque, e a equipe ajuda a escolher a versão compatível com o seu sistema e com os periféricos (impressora, balança e leitor).",
        "especificacoes": [
            [
                "Formato",
                "Mini PC"
            ],
            [
                "Uso",
                "Frente de caixa"
            ]
        ],
        "imagem": "centrium-pc.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": true
    },
    {
        "id": "tanca-tp-650",
        "nome": "TP-650",
        "marca": "Tanca",
        "categoria": "informatica",
        "subcategoria": "Impressoras de cupom",
        "resumo": "Impressora de cupom 80 mm, 250 mm/s, com USB, Serial e Ethernet de fábrica.",
        "descricao": "Imprime cupons e comprovantes a até 250 mm/s, o que agiliza a fila no caixa da padaria, do açougue ou do mercado. Já sai de fábrica com USB, Serial e Ethernet, o que facilita ligar a impressora ao computador ou à rede, e a troca de bobina é simples (sistema Easy Load). A guilhotina tem vida útil de 1 milhão de cortes, e a garantia é de 3 anos.",
        "especificacoes": [
            [
                "Velocidade de impressão",
                "250 mm/s"
            ],
            [
                "Conectividade",
                "Ethernet, Serial e USB (integradas)"
            ],
            [
                "Largura da bobina",
                "80 mm"
            ],
            [
                "Corte",
                "Guilhotina (vida útil de 1 milhão de cortes) e serrilha"
            ],
            [
                "Garantia",
                "3 anos"
            ]
        ],
        "imagem": "tanca.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "elgin-i9",
        "nome": "i9 Full",
        "marca": "Elgin",
        "categoria": "informatica",
        "subcategoria": "Impressoras de cupom",
        "resumo": "Impressora de cupom 300 mm/s com guilhotina e USB, Ethernet e Serial.",
        "descricao": "Impressora térmica de cupom para NFC-e e comprovantes, que imprime a até 300 mm/s para agilizar o atendimento no caixa. Reúne USB, Ethernet e Serial no mesmo equipamento, aceita bobinas de 57,5 mm ou 80 mm e tem guilhotina com corte parcial automático e vida útil de 2 milhões de cortes. A garantia é de 3 anos e inclui a cabeça térmica.",
        "especificacoes": [
            [
                "Velocidade de impressão",
                "Até 300 mm/s"
            ],
            [
                "Conectividade",
                "USB, Ethernet e Serial RS-232 (DB-9)"
            ],
            [
                "Largura do papel",
                "57,5 mm ou 80 mm"
            ],
            [
                "Guilhotina",
                "Corte parcial automático, vida útil de 2 milhões de cortes"
            ],
            [
                "Garantia",
                "3 anos (impressora e cabeça térmica)"
            ]
        ],
        "imagem": "elgin-i9.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": true
    },
    {
        "id": "epson-tm-t20",
        "nome": "TM-T20X II",
        "marca": "Epson",
        "categoria": "informatica",
        "subcategoria": "Impressoras de cupom",
        "resumo": "Impressora de cupom Epson 250 mm/s com guilhotina, fabricada no Brasil.",
        "descricao": "Impressora térmica de recibos para cupons e comprovantes, que imprime a até 250 mm/s e corta o papel automaticamente para agilizar o caixa. Tem carga rápida de papel, baixo consumo de energia e LEDs de status, e vem nas versões USB + Serial ou USB + Ethernet. É fabricada no Brasil e tem garantia Epson de 3 anos balcão.",
        "especificacoes": [
            [
                "Velocidade de impressão",
                "Até 250 mm/s"
            ],
            [
                "Conectividade",
                "USB + Serial (C31CL45011) ou USB + Ethernet (C31CL45012)"
            ],
            [
                "Largura do papel",
                "80 mm (79,5 ± 0,5 mm)"
            ],
            [
                "Guilhotina",
                "Automática, 1,5 milhão de cortes"
            ],
            [
                "Garantia",
                "3 anos balcão"
            ]
        ],
        "imagem": "epson-t20.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "sat-bematech-rb2000",
        "nome": "SAT RB-2000",
        "marca": "Bematech",
        "categoria": "informatica",
        "subcategoria": "Emissores fiscais (SAT)",
        "resumo": "Equipamento SAT para emissão de CF-e.",
        "descricao": "Equipamento SAT (CF-e). Atenção: em SP o CF-e SAT está proibido desde 01/01/2026 — fale com a gente para migrar para NFC-e.",
        "especificacoes": [
            [
                "Documento",
                "CF-e SAT"
            ]
        ],
        "imagem": "bematech-sat.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false,
        "visivel": false
    },
    {
        "id": "sat-elgin-smart",
        "nome": "SAT Smart",
        "marca": "Elgin",
        "categoria": "informatica",
        "subcategoria": "Emissores fiscais (SAT)",
        "resumo": "Equipamento SAT para emissão de CF-e.",
        "descricao": "Equipamento SAT (CF-e). Atenção: em SP o CF-e SAT está proibido desde 01/01/2026 — fale com a gente para migrar para NFC-e.",
        "especificacoes": [
            [
                "Documento",
                "CF-e SAT"
            ]
        ],
        "imagem": "elgin-smart.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false,
        "visivel": false
    },
    {
        "id": "sat-tanca-ts1000",
        "nome": "SAT TS-1000",
        "marca": "Tanca",
        "categoria": "informatica",
        "subcategoria": "Emissores fiscais (SAT)",
        "resumo": "Equipamento SAT para emissão de CF-e.",
        "descricao": "Equipamento SAT (CF-e). Atenção: em SP o CF-e SAT está proibido desde 01/01/2026 — fale com a gente para migrar para NFC-e.",
        "especificacoes": [
            [
                "Documento",
                "CF-e SAT"
            ]
        ],
        "imagem": "sat-tanca.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false,
        "visivel": false
    },
    {
        "id": "nobreak-apc",
        "nome": "Back-UPS 400 VA",
        "marca": "APC",
        "categoria": "informatica",
        "subcategoria": "Nobreaks",
        "resumo": "Nobreak 400 VA para o PC do caixa, com entrada bivolt e 4 tomadas.",
        "descricao": "Mantém o computador do caixa ligado durante quedas de energia, dando tempo para concluir a venda e desligar o sistema com segurança. Tem entrada bivolt (115/220 V), saída 115 V e 4 tomadas no padrão NBR 14136. Os equipamentos ligados a ele precisam funcionar em 115 V.",
        "especificacoes": [
            [
                "Potência",
                "400 VA / 220 W"
            ],
            [
                "Tensão",
                "Entrada bivolt 115/220 V; saída 115 V"
            ],
            [
                "Tomadas",
                "4 tomadas padrão NBR 14136"
            ],
            [
                "Topologia",
                "Standby, forma de onda senoidal aproximada"
            ]
        ],
        "imagem": "nobreak-apc.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    },
    {
        "id": "nobreak-nhs",
        "nome": "Nobreak 400 VA",
        "marca": "NHS",
        "categoria": "informatica",
        "subcategoria": "Nobreaks",
        "resumo": "Nobreak nacional robusto para o caixa.",
        "descricao": "Nobreak NHS para proteger os equipamentos do caixa contra quedas e oscilações de energia.",
        "especificacoes": [
            [
                "Potência",
                "400 VA"
            ],
            [
                "Tensão",
                "Bivolt"
            ]
        ],
        "imagem": "nobreak-nhs.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": false
    }
];
