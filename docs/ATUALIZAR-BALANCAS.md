# Catálogo: o que está velho e o que pôr no lugar

Pesquisa feita em **11/10/2026** nos sites dos fabricantes e de distribuidores brasileiros. O ponto de partida foi a cópia do catálogo que está no repositório (`compartilhado/dados/produtos.js`, gravada pelo painel em 11/10/2026 às 00:39, com 45 produtos).

> **O catálogo que vale é o do servidor.** Todas as mudanças abaixo são feitas **no painel** (`https://balancass.com/painel/`), nunca editando o `produtos.js` nem enviando o arquivo pelo cPanel: os pacotes de atualização do site não levam esse arquivo, e o que for editado à mão some na próxima vez que o painel salvar. Se algum item já foi mudado no painel depois de 11/10/2026, ignore esse item.

**Como ler as tabelas**

- **Situação:** *Em linha* (o fabricante ainda oferece), *Trocar nome* (continua à venda, mas com outro nome ou versão), *Fora de linha* (o fabricante ou o distribuidor diz que saiu de linha), *Substituído* (o fabricante vende uma versão nova no lugar), *Proibido em SP* e *Sem modelo* (o cadastro não diz qual é o modelo).
- **Certeza:** **Alta** quando a página oficial do fabricante confirma. **Média** quando a confirmação vem do catálogo oficial (às vezes de 2024) ou de um distribuidor. **Baixa** quando só há revendas ou indícios.
- Quando um dado não pôde ser confirmado, está escrito **"confirmar com o fornecedor"**. Nenhuma especificação foi inventada.

---

## Resumo

| Categoria | Produtos | Em linha | Trocar nome | Desatualizados (fora de linha, substituídos ou proibidos) | Sem modelo |
|---|---:|---:|---:|---:|---:|
| Balanças | 17 | 17 | 0 | 0 | 0 |
| Automação Comercial | 19 | 10 | 2 | 7 | 0 |
| Informática e PDV | 9 | 2 | 0 | 6 | 1 |
| **Total** | **45** | **29** | **2** | **13** | **1** |

**O principal**

1. **Balanças: nenhuma das 17 saiu de linha.** Toledo e Balmak ainda oferecem todas. O que está velho é outra coisa:
   - **Faltam os modelos novos.** Os que valem cadastrar são a **Toledo Prix 4 Trend** (página publicada em 2026), a **Prix 6**, a **Prix 7T** (com inteligência artificial), a **Balmak EDGE** (a etiquetadora mais nova da Balmak) e a **Urano BA37** (a Urano chama de "nova linha").
   - **A ficha da Toledo 2096PP cita uma portaria revogada.** A "Portaria 236/94" foi revogada pela **Portaria Inmetro 157/2022**, em vigor desde 02/01/2023.
   - **A foto da Prix 5 Plus parece ser de uma versão antiga, sem coluna.** A Toledo mostra a Prix 5 Plus em torre, e a própria ficha do site diz "em torre". Vale também conferir se a foto da **Órion 1 Key** é mesmo da versão "Key", a de teclas individuais.
2. **Automação: 7 produtos estão fora de linha.**

   | Sai | Entra |
   |---|---|
   | Argox OS-2140 | Argox OS-214 Pro |
   | Zebra ZT230 | Zebra ZT231 |
   | Datalogic QW2100 | Datalogic QuickScan QW2500 |
   | Elgin EL4200 | Elgin EL8600iA |
   | Gertec TC 504 | Gertec TC506E 2D |
   | Gertec Busca Preço G2-E | Gertec Busca Preço 2D |
   | Gertec MT 720 | Gertec MT-721 VT100 |

   Outros 2 só precisam de nome novo: **L42 Pro → L42 Pro Full** e **GD-56 → GD-56M**.
3. **Informática:**
   - **Os 3 SAT não servem mais em SP.** Desde 01/01/2026 a SEFAZ-SP recusa o CF-e SAT (erro 1001). Eles já estão escondidos: o melhor é **excluir**.
   - A **Elgin i9 Full** deu lugar à **i9 FULL 2**.
   - A **NHS não fabrica mais nobreak de 400 VA**. O menor da linha atual é o **Mini 4 de 600 VA**.
   - O **APC BZ400BI-BR** provavelmente saiu de linha. Confirme com o distribuidor; a alternativa atual da APC é o **BVX600BI-BR**.
4. **Leitores 1D estão perdendo espaço.** A GS1 quer que, até o fim de 2027, todo caixa leia códigos 2D (QR Code GS1), e leitor a laser não lê 2D. Vale dar destaque aos leitores 2D: o Tanca TL-900, que já está no site, e os substitutos acima.

---

## Balanças (17)

| Produto no site | Situação | Sugestão | Fonte (certeza) |
|---|---|---|---|
| Toledo **Prix 7E** (`toledo-prix-7e`) | Em linha (fotos da página oficial de mai/2026) | Manter. É o produto da capa do site. | [toledobrasil.com](https://www.toledobrasil.com/produto/prix-7e/) (Alta) |
| Toledo **Prix 5 Plus** (`toledo-prix-5-plus`) | Em linha | Manter. **Trocar a foto:** a do site mostra a balança sem coluna, mas a Toledo mostra a Prix 5 Plus em torre ([foto oficial](https://stportalcorporativoprd.blob.core.windows.net/blobappportalc63534e8570/wp-content/uploads/2024/08/prix5-plus-com-coluna-vista-operador-desktop-1.png)). | [toledobrasil.com](https://www.toledobrasil.com/produto/prix-5-plus/) (Alta; foto: Média) |
| Toledo **Prix 4 Uno** (`toledo-prix-4-uno`) | Em linha | Manter. A Toledo publicou em 2026 a **Prix 4 Trend**, da mesma faixa (2.000 itens). Pergunte ao distribuidor se a Uno vai sair de linha e cadastre a Trend (ver detalhes). | [toledobrasil.com](https://www.toledobrasil.com/produto/prix-4-uno/) (Alta) |
| Toledo **Prix 4 Due** (`toledo-prix-4-due`) | Em linha | Manter. | [toledobrasil.com](https://www.toledobrasil.com/produto/prix-4-due/) (Alta) |
| Balmak **Órion 1 Key** (`balmak-orion-1-plus`) | Em linha (catálogo Balmak RV1, jun/2024) | Manter. Conferir se a foto é da versão **Key** (teclas individuais). A novidade da Balmak é a **EDGE** (ver detalhes). | [catálogo oficial, cópia no distribuidor](https://www.brasilinternacional.com/arquivo/catalogo/2_1_balmak-lnea-automatizacin-comercial_3.pdf) (Média: o site da Balmak estava fora do ar) |
| Balmak **Órion 2 Key** (`balmak-orion-2`) | Em linha (catálogo jun/2024) | Manter. | mesmo catálogo (Média) |
| Toledo **Prix 3 Fit** (`toledo-prix-3-fit`) | Em linha | Manter. | [toledobrasil.com](https://www.toledobrasil.com/produto/prix-3-fit/) (Alta) |
| Toledo **Prix 3 Plus** (`toledo-prix-3-plus`) | Em linha | Manter. | [toledobrasil.com](https://www.toledobrasil.com/produto/prix-3-plus/) (Alta) |
| Balmak **ONE** (`balmak-one`) | Em linha (catálogo jun/2024, "Linha Comercial") | Manter. | mesmo catálogo (Média) |
| Toledo **9094 Plus** (`toledo-9094-plus`) | Em linha | Manter. | [toledobrasil.com](https://www.toledobrasil.com/produto/9094-plus/) (Alta) |
| Balmak **ONE Pesadora** (`balmak-one-pesadora`) | Em linha (provável) | Manter. Confirmar o código (ELPO) com o distribuidor. | catálogo jun/2024 + revendas (Média) |
| Toledo **8217 Checkout** (`toledo-8217`) | Em linha | Manter. A lista de comparação do site da Toledo cita uma **"8217M"**: confirmar com a Toledo se a versão vendida hoje é a 8217M. | [toledobrasil.com](https://www.toledobrasil.com/produto/balanca-para-checkout-8217/) (Alta) |
| Balmak **BCK-30 Checkout** (`balmak-bck30`) | Em linha (catálogo jun/2024: BCK-30PB, PA e PAN) | Manter. | mesmo catálogo (Média) |
| Toledo **Prix 2098 / 2098C** (`toledo-2098`) | Em linha | Manter. | [toledobrasil.com](https://www.toledobrasil.com/produto/2098-e-2098-c/) (Alta) |
| Balmak **K-300** (`balmak-w300`) | Em linha (Série K no catálogo jun/2024) | Manter. Confirmar a divisão com o fornecedor (as revendas dizem 50 g; um resumo da página oficial dizia 100 g). | mesmo catálogo (Média) |
| Toledo **Prix 2096PP** (`toledo-hospitalar`) | Em linha | **Corrigir a ficha:** trocar "Aprovação: Inmetro, Portaria 236/94" por "Aprovação: Inmetro, classe de exatidão III". A Portaria 236/1994 foi revogada pela Portaria Inmetro 157/2022, em vigor desde 02/01/2023. Se a Toledo informar o número da portaria de aprovação do modelo, dá para incluir. | [toledobrasil.com](https://www.toledobrasil.com/produto/2096-pp/) e [texto da Portaria 157/2022, art. 3º](https://sibapem.com.br/wp-content/uploads/2023/01/Portaria-157-2022.pdf) (Alta) |
| Balmak **BK-200F** (`balmak-bk200f`) | Em linha (provável) | Manter. O site da Balmak estava fora do ar; as revendas ainda vendem o BK-200F e o BK-200FAN. | revendas (Baixa) |

## Automação Comercial (19)

| Produto no site | Situação | Sugestão | Fonte (certeza) |
|---|---|---|---|
| Elgin **L42 Pro** (`elgin-l42-pro`) | **Trocar nome.** O site da Elgin só tem a **L42 Pro Full**, marcada "Indisponível" na loja oficial em 10/2026. | Renomear para "L42 Pro Full" e deixar a conectividade como USB, Serial e Ethernet. Estoque: "Consulte". | [elgin.com.br](https://www.elgin.com.br/impressora-termica-eti-elgin-l42pro-full/p) (Média) |
| Argox **OS-2140** (`argox-os-2140`) | **Fora de linha** ("OS-2140 (Discontinued)") | Trocar pela **Argox OS-214 Pro**. É a mais parecida, mas a Argox não diz que ela substitui a OS-2140. | [argox.com](https://www.argox.com/products-detail/os-2140/) (Alta) |
| Zebra **ZT230** (`zebra-zt230`) | **Fora de linha** desde 27/06/2022. A Zebra indica a **ZT231** e dá suporte à ZT230 até 31/01/2027. | Trocar pela **Zebra ZT231**. | [zebra.com, suporte da ZT230](https://www.zebra.com/us/en/support-downloads/printers/industrial/zt230.html) (Alta, conferida em 07/10/2026) |
| Tanca **TL-120** (`tanca-tl-120`) | Em linha (só lê 1D) | Manter. Veja a nota sobre 2D no fim da tabela. | [tanca.com.br](https://www.tanca.com.br/automacao-comercial-produto.php?cod=158) (Alta) |
| Tanca **TL-220** (`tanca-tl-220`) | Em linha (laser, só lê 1D) | Manter. | [tanca.com.br](https://www.tanca.com.br/automacao-comercial-produto.php?cod=92) (Alta) |
| Datalogic **QuickScan Lite QW2100** (`datalogic-quickscan-qw2100`) | **Fora de linha.** Saiu da página da série no site da Datalogic, e revendas o marcam como "discontinued". | Trocar pelo **QuickScan QW2500** (2D, USB). Para quem precisa de RS-232: **QD2500** (QD2590). | [datalogic.com](https://www.datalogic.com/eng/retail-transportation-logistics-other-applications/handheld-scanners/quickscan-2100-series-pd-909.html) (Média) |
| Elgin **EL4200** (`elgin-el4200`) | **Fora de linha** (distribuidor: "fora de linha e não será mais comercializado") | Trocar pelo **Elgin EL8600iA** (fixo, 2D). | [bztech.com.br](https://www.bztech.com.br/elgin/leitor-fixo-el4200) (Alta) |
| Tanca **TL-900** (`tanca-tl-900`) | Em linha | Manter. É o leitor 2D do catálogo: vale pôr em destaque. | [tanca.com.br](https://www.tanca.com.br/automacao-comercial-produto.php?cod=162) (Alta) |
| Menno **Gaveta MG 41** (`gaveta-menno`) | Em linha | Manter. | [menno.com.br](https://www.menno.com.br/suprimentos-para-escritorio/produto/gaveta-para-dinheiro-mg-41b-plus-automatica/) (Alta) |
| Bematech **Gaveta GD-56** (`gaveta-bematech`) | **Trocar nome.** Hoje é vendida pela Elgin como **GD-56M** (Bematech). | Renomear para "Gaveta GD-56M". | [elgin.com.br](https://www.elgin.com.br/Produtos/Automacao/gaveta/gd-56m) (Média) |
| Gertec **TC 504** (`gertec-tc504`) | **Fora de linha** | Trocar pelo **Gertec TC506E 2D**. Atenção: a tela é menor (3,2" contra 5,7"). | [bztech.com.br](https://www.bztech.com.br/gertec/terminal-de-consulta-tc-504) e [loja.gertec.com.br](https://loja.gertec.com.br/terminais-de-consulta) (Alta) |
| Gertec **Busca Preço G2-E** (`gertec-g2`) | **Fora de linha.** A loja oficial da Gertec só vende as versões 2D. | Trocar pelo **Busca Preço 2D** (G2 E 2D, ref. 00410780). | [loja.gertec.com.br](https://loja.gertec.com.br/terminais-de-consulta) e [bztech.com.br](https://www.bztech.com.br/gertec/terminal-de-consulta-busca-preco-g2) (Alta) |
| Gertec **MT 720** (`gertec-mt-720`) | **Fora de linha** | Trocar pelo **Gertec MT-721 VT100**. | [bztech.com.br](https://www.bztech.com.br/gertec/microterminal-nao-fiscal-mt-720) (Média: o site da Gertec estava fora do ar) |
| Gertec **TEC 44** (`gertec-tec-44`) | Em linha | Manter. Também existe a TEC44 DIS, com display. | [loja.gertec.com.br](https://loja.gertec.com.br/tec-e-44/p) (Alta) |
| Smak **SKO-44** (`smak-sko44`) | Em linha | Manter. | [smak.com.br](https://smak.com.br/produto/teclado-optico-de-44-teclas/) (Alta) |
| Toledo **Fatiador Prix Veloce 350A** (`toledo-uni-350-ga`) | Em linha | Manter. | [toledobrasil.com](https://www.toledobrasil.com/produto/fatiador-de-frios-prix-veloce-350a/) (Alta) |
| Toledo **Fatiador Prix Supremo 350S** (`toledo-uni-350`) | Em linha | Manter. | [toledobrasil.com](https://www.toledobrasil.com/produto/prix-supremo-350s/) (Alta) |
| Toledo **MIT 7** (`toledo-mit`) | Em linha | Manter. | [toledobrasil.com](https://www.toledobrasil.com/produto/mit-7/) (Alta) |
| Balmak **All-Mídia** (`balmak-all-midia`) | Em linha (catálogo jun/2024) | Manter. O catálogo descreve um player antigo (MK809 IV ou Raspberry, com software para Windows XP a 10): confirmar com a Balmak o equipamento atual antes de detalhar a ficha. | catálogo jun/2024 (Média) |

**Nota sobre leitores 1D e 2D.** A GS1 definiu como meta que, até o fim de 2027, todo caixa leia o código do produto tanto no código de barras comum quanto no 2D. Ela avisa que leitor a laser não lê 2D ([GS1, guia de implantação](https://www.gs1.org/standards/2d-barcodes-retail-point-sale-implementation-guideline/current-standard); [GS1, prontidão para 2D](https://www.gs1.org/industries/retail/solution-provider-2d-readiness)). O código de barras comum continua nas embalagens por bastante tempo, então TL-120 e TL-220 podem ficar no site, mas o destaque deve ir para os leitores 2D.

## Informática e PDV (9)

| Produto no site | Situação | Sugestão | Fonte (certeza) |
|---|---|---|---|
| Centrium **Mini PC para PDV** (`centrium-mini-pc`) | **Sem modelo.** A Centrium não tem catálogo online. | Manter genérico ou trocar pelo modelo que a loja realmente vende (processador, memória, SSD, portas): confirmar com o fornecedor. | [FONTES-DOS-PRODUTOS.md](FONTES-DOS-PRODUTOS.md) (Baixa) |
| Tanca **TP-650** (`tanca-tp-650`) | Em linha | Manter. | [tanca.com.br](https://www.tanca.com.br/automacao-comercial-produto.php?cod=93) (Alta) |
| Elgin **i9 Full** (`elgin-i9`) | **Substituído.** A loja da Elgin mostra a i9 Full "Indisponível" e vende a **i9 FULL 2**. | Trocar pela **i9 FULL 2**. | [elgin.com.br, categoria](https://www.elgin.com.br/automacao-comercial/impressora-termica-de-cupom) (Média) |
| Epson **TM-T20X II** (`epson-tm-t20`) | Em linha | Manter. | [epson.com.br](https://epson.com.br/Para-empresas/Impressoras/Impressoras-de-Pontos-de-Venda/Impressora-de-Recibos-Epson-TM-T20X-II-%28Ethernet-USB%29/p/C31CL45012) (Alta) |
| Bematech **SAT RB-2000** (`sat-bematech-rb2000`) | **Proibido em SP** (já escondido) | **Excluir.** | [SEFAZ-SP](https://portal.fazenda.sp.gov.br/servicos/sat) (Alta) |
| Elgin **SAT Smart** (`sat-elgin-smart`) | **Proibido em SP** (já escondido) | **Excluir.** | idem (Alta) |
| Tanca **SAT TS-1000** (`sat-tanca-ts1000`) | **Proibido em SP** (já escondido) | **Excluir.** | idem (Alta) |
| APC **Back-UPS 400 VA** (BZ400BI-BR) (`nobreak-apc`) | **Provável fim de linha.** Indícios: uma busca mostrou a página da APC dizendo "descontinuado em 16/05/2024", e uma revenda diz "descontinuado pelo fabricante". Mas uma FAQ da Schneider, atualizada em 01/09/2026, ainda lista o modelo. | Confirmar com o distribuidor. Se acabou, trocar pelo **APC Back-UPS BVX600BI-BR**. | [se.com, FAQ](https://www.se.com/br/pt/faqs/FAQ000233463/) (Baixa/Média) |
| NHS **Nobreak 400 VA** (`nobreak-nhs`) | **Desatualizado.** A linha atual da NHS não tem 400 VA; o menor é o Mini 4, de 600 VA. | Trocar pelo **NHS Mini 4 Bivolt 600 VA**. | [nhs.com.br, linha Interactive](https://nhs.com.br/linha-de-produtos/interactive/) (Alta) |

### SAT e NFC-e: o que muda para o catálogo

O que a SEFAZ-SP decidiu:

- A **Portaria SRE 79/2024** (DOE de 01/11/2024) alterou a Portaria CAT 147/2012. O CF-e SAT valeu **até 31/12/2025**; desde **01/01/2026** o documento do varejo em SP é a **NFC-e** (modelo 65) ([TecnoSpeed](https://blog.tecnospeed.com.br/sefaz-sp-portaria-sre/), [CRC-SP](https://online.crcsp.org.br/portal/noticias/noticia.asp?c=10087)).
- **Aviso oficial de 08/01/2026:** os CF-e SAT emitidos a partir de 01/01/2026 são recebidos com o **erro 1001, "Cupom inválido"** (art. 34-D da Portaria CAT 147/2012) ([portal da SEFAZ-SP](https://portal.fazenda.sp.gov.br/servicos/sat)).
- **Aviso de 02/03/2026:** quem emitiu SAT depois de 31/12/2025 precisa declarar os valores (PGDAS-D ou EFD), pagar o ICMS e fazer denúncia espontânea pelo SIPET.
- **Aviso de 23/03/2026:** não é preciso desativar o aparelho SAT que continua vinculado à empresa.
- Não encontrei nenhuma prorrogação.

O que isso muda no catálogo:

1. **Excluir os 3 SAT.** Em SP o aparelho não tem mais uso fiscal, então não vale deixar nem como "Sob encomenda". Eles já estão escondidos, e a subcategoria "Emissores fiscais (SAT)" só aparece no site quando tem produto visível, então ela já não aparece. No painel, porém, ela continua na lista de subcategorias; tirar de lá exige uma mudança no código (é um pedido para o desenvolvedor, nada urgente).
2. **A NFC-e não precisa de aparelho fiscal.** Bastam o sistema emissor (o BC System), o certificado digital, internet (há contingência offline) e uma **impressora térmica não fiscal**. Na vitrine, quem atende isso são a TP-650, a i9 FULL 2 e a TM-T20X II. As páginas do site ("Ainda no SAT? Regularize já", em `sistema.html#fiscal`) já falam da migração, então não é preciso criar produto para isso.

---

## Detalhes dos modelos sugeridos

Para baixar as fotos, use os links de "Foto oficial": abra no celular, salve e envie no campo **Foto** do painel. Antes de usar foto do fabricante, confirme com o distribuidor se ele autoriza.

### A. Substitutos (entram no lugar de um produto do site)

#### Zebra ZT231, no lugar da ZT230
- **Por quê:** é a substituta indicada pela própria Zebra.
- **Ficha:** térmica direta, com transferência térmica opcional. 203 dpi (300 dpi opcional). Até 12 pol/s (cerca de 305 mm/s) a 203 dpi. Largura máxima de impressão de 104 mm; etiqueta de 19,4 a 114 mm; rolo de até 203 mm de diâmetro externo. USB 2.0, serial RS-232, Ethernet 10/100, USB host e Bluetooth Low Energy; Wi-Fi 802.11ac opcional. Tela colorida sensível ao toque de 4,3". Linguagens ZPL e ZPL II.
- **Página oficial:** <https://www.zebra.com/us/en/products/printers/industrial/zt200-series/zt231.html>. Ficha em português: <https://www.zebra.com/content/dam/zebra_dam/pt_br/spec-sheets/zt231-spec-sheet-pt-br.pdf>
- **Foto oficial:** na página do produto. Certeza: Alta.

#### Argox OS-214 Pro, no lugar da OS-2140
- **Por quê:** é a de desenho mais parecido e está na linha atual. A Argox não diz que ela substitui a OS-2140.
- **Ficha:** térmica direta e transferência térmica. 203 dpi. Até 5 pol/s. Impressão de até 105 mm; etiqueta de 25,4 a 110 mm. Ribbon de até 92 m. 128 MB de SDRAM e 128 MB de Flash. Emulações PPLA, PPLB e PPLZ. **Interface: só USB 2.0** (quem precisa de rede deve ver outro modelo com o fornecedor).
- **Página oficial:** <https://www.argox.com/products-detail/os-214_pro/>
- **Foto oficial:** <https://www.argox.com/upload/catalog_m/ALL_catalog_25I09_QvaF9je1JN.png> (foto com a tampa aberta). Certeza: Alta.

#### Datalogic QuickScan QW2500 (ou QD2500), no lugar do QW2100
- **Por quê:** é o leitor de mão com fio da linha atual da Datalogic e lê 2D (QR Code, inclusive GS1 Digital Link).
- **Ficha:** lê 1D e 2D em qualquer direção. Mira com triângulos azuis e confirmação "Green Spot". Gabinete IP52. Gatilho para cerca de 10 milhões de acionamentos. Modelos: **QW2520** (USB) e **QD2590** (USB e RS-232). O QD2500 resiste a queda de 1,5 m e lê telas de celular; para o QW2500 a página não informa leitura de tela (confirmar com o fornecedor).
- **Página oficial:** <https://www.datalogic.com/eng/retail-manufacturing-healthcare-gs1-digital-link/handheld-scanners/quickscan-2500-series-pd-898.html>
- **Foto oficial:** na página do produto. Certeza: Alta.

#### Elgin EL8600iA, no lugar do EL4200
- **Por quê:** é a substituta indicada pelo distribuidor BZTech e lê QR Code.
- **Ficha (pelo resumo da página oficial; a tabela técnica não abriu, então confirmar com o fornecedor):** leitor fixo 1D e 2D, até 8 m/s, pedestal ajustável, IP52, queda de 1,5 m, USB (serial RS-232 sob consulta), garantia de 3 anos.
- **Página oficial:** <https://www.elgin.com.br/leitor-cod-barras-2d-elgin-bema-el8600ia/p>
- **Foto oficial:** <https://elgin.vtexassets.com/arquivos/ids/172365-800-800?width=800&height=800&aspect=true>. Certeza: Média.

#### Gertec TC506E 2D, no lugar do TC 504
- **Por quê:** é a substituta indicada pelo distribuidor e está à venda na loja oficial da Gertec.
- **Ficha:** tela TFT colorida de 3,2" (320 x 240), que mostra imagens JPEG e BMP. Leitor 1D e 2D (QR Code). Ethernet, Wi-Fi 2,4 GHz (802.11 b/g/n) e Bluetooth 4.2 (para configurar). Alimentação de 5 Vdc, com fonte inclusa. Ref. 00410543.
- **Página oficial:** <https://loja.gertec.com.br/tc506-e-2d/p>
- **Foto oficial:** <https://lojagertec.vtexassets.com/arquivos/ids/156170-800-auto?width=800&height=auto&aspect=true>. Certeza: Alta.

#### Gertec Busca Preço 2D (G2 E 2D), no lugar do Busca Preço G2-E
- **Por quê:** a versão 1D saiu de linha, e a loja oficial só vende a 2D.
- **Ficha:** tela TFT colorida de 3,2" (320 x 240, 256 mil cores). Leitor 1D e 2D. Ethernet, Wi-Fi 2,4 GHz e Bluetooth 4.2 (para configurar). Alto-falante que toca arquivos WAV (acessibilidade). 5 Vdc, com fonte inclusa (110/220 V). Ref. 00410780. O datasheet da Gertec cita 140 x 100 x 40 mm e garantia de 12 meses (confirmar com o fornecedor).
- **Página oficial:** <https://loja.gertec.com.br/terminal-de-consulta-busca-preco-2d-00410780/p>. Datasheet: <https://www.gertec.com.br/wp-content/uploads/2025/07/Busca-Preco-G2-E-2D.pdf>
- **Foto oficial:** <https://lojagertec.vtexassets.com/arquivos/ids/156585-800-auto?width=800&height=auto&aspect=true>. Certeza: Alta.
- **Dica:** o TC506E 2D e o Busca Preço 2D têm fichas quase iguais. Se a loja só vende um deles, dá para juntar os dois cadastros num produto só.

#### Gertec MT-721 VT100, no lugar do MT 720
- **Por quê:** é o substituto indicado pelo distribuidor BZTech.
- **Ficha (distribuidor; o site da Gertec estava fora do ar):** LCD de 2 linhas x 20 caracteres com backlight. 20 teclas programáveis. 1 Ethernet RJ-45, 2 USB e 2 seriais RJ11. 5 Vdc, com fonte inclusa. Protocolos VT100 e STX/ETX. Garantia de 1 ano.
- **Página:** <https://www.bztech.com.br/gertec/microterminal-nao-fiscal-mt-721> (distribuidor). A página oficial da Gertec não abriu (erro 503). Certeza: Média.

#### Elgin i9 FULL 2, no lugar da i9 Full
- **Por quê:** a i9 Full aparece "Indisponível" na loja da Elgin, e a i9 FULL 2 está à venda.
- **Ficha (o que a página oficial mostra):** 300 mm/s; USB, Ethernet e Serial. A tabela técnica da página não abriu. Bobina, guilhotina e garantia: **confirmar com o fornecedor** (a ficha da i9 Full não vale automaticamente para a versão 2).
- **Página oficial:** <https://www.elgin.com.br/impressora-termica-cupom-i9-full-2/p>
- **Foto oficial:** <https://elgin.vtexassets.com/arquivos/ids/172219-800-800?width=800&height=800&aspect=true>. Certeza: Média.

#### Elgin L42 Pro Full (só troca o nome da L42 Pro)
- **Ficha:** a do site continua valendo para a versão Full: USB, serial e Ethernet; até 102 mm/s; EPL, ZPL, PPLA e PPLB.
- **Página oficial:** <https://www.elgin.com.br/impressora-termica-eti-elgin-l42pro-full/p>, marcada "Indisponível" na loja oficial em 11/10/2026. Confirmar com o distribuidor se ainda há reposição. As outras etiquetadoras da Elgin (TT042 Plus e L42DT) também aparecem "Indisponível". Certeza: Média.

#### Elgin Bematech GD-56M (só troca o nome da GD-56)
- **Ficha (resumo da página oficial; confirmar com o fornecedor):** abertura elétrica por pulso de 24 V, por chave e por mecanismo de emergência. 5 nichos para cédulas e 6 para moedas. 2 fendas frontais. Sensor de posição. Aço. Conector RJ12.
- **Página oficial:** <https://www.elgin.com.br/Produtos/Automacao/gaveta/gd-56m>. Certeza: Média.

#### NHS Mini 4 Bivolt 600 VA, no lugar do "Nobreak 400 VA"
- **Por quê:** a NHS não tem mais 400 VA. O Mini 4 é o menor da linha atual e serve para o PC do caixa.
- **Ficha:** 600 VA. Entrada bivolt. Saída de 120 V (configurável para 220 V). 6 tomadas. Bateria de 12 V / 7 Ah. Transferência em menos de 4 ms. 292 x 98 x 170 mm; 5,75 kg. USB sob encomenda. A potência em watts não aparece na página oficial (revendas dizem 300 W: confirmar com o fornecedor).
- **Página oficial:** <https://nhs.com.br/produto/nobreak-mini-4-bivolt-600va-b-7ah/>
- **Foto oficial:** <https://nhs.com.br/wp-content/uploads/2026/06/produto-102260399-perspectiva-f559fa5d.png>. Certeza: Alta.

#### APC Back-UPS BVX600BI-BR, alternativa ao BZ400BI-BR
- **Por quê:** é o nobreak de entrada bivolt da APC que aparece como linha atual no site da Schneider.
- **Ficha:** 600 VA / 360 W. Bivolt. 4 tomadas (padrão NBR 14136, pelo nome do produto). Regulação automática de tensão (AVR). Tensão de saída e autonomia: **confirmar com o fornecedor**.
- **Página oficial:** <https://www.se.com/br/pt/product/BVX600BI-BR/nobreak-backups-da-apc-600-va-em-torre-115-220-v-4-tomadas-de-sa%C3%ADda-nbr-14136-avr/>
- **Foto oficial:** <https://download.schneider-electric.com/files?p_Doc_Ref=BVX600-BR_FL_V&p_File_Type=rendition_369_jpg&default_image=DefaultProductImage.png>. Certeza: Média.

### B. Modelos atuais que valem ACRESCENTAR

A ordem é de prioridade para uma loja de balanças comerciais como a de Rio Claro.

#### 1. Toledo Prix 4 Trend (Balanças → Com impressora de etiquetas)
- **Por quê:** é a etiquetadora compacta mais nova da Toledo (página publicada em 2026), na faixa de preço da Prix 4 Uno. Serve para padarias, açougues e mercadinhos.
- **Ficha:** 32 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 32 kg), classe III, com aprovação do Inmetro. Impressão a 70 mm/s, até 60 mm de largura; etiqueta EAN-13 ou papel contínuo de 40 ou 60 mm (modo comanda). Até 2.000 itens. Display LCD com backlight; 48 teclas rápidas para até 144 itens. Ethernet ou Wi-Fi 802.11 b/g; integra com o MGV.
- **Página oficial:** <https://www.toledobrasil.com/produto/prix-4-trend/>
- **Foto oficial:** <https://stportalcorporativoprd.blob.core.windows.net/blobappportalc63534e8570/wp-content/uploads/2026/06/balanca-comercial-prix-4-trend-toledo-do-brasil-vista-angular.webp>. Certeza: Alta.

#### 2. Balmak EDGE (Balanças → Com impressora de etiquetas)
- **Por quê:** a própria Balmak apresenta como "a mais nova e avançada" etiquetadora dela, feita para o pequeno e o médio varejo, com bom custo-benefício. Hoje o site só tem Balmak Órion.
- **Ficha (catálogo oficial RV1, jun/2024):** 6/15/33 kg com divisão de 2/5/10 g; carga mínima de 40 g; tara de até 5,998 kg. Até 10.000 itens; 352 teclas de atalho. Display TFT colorido de 1,5" para o operador e o cliente. Impressão térmica de 8 pontos/mm, a 100 mm/s; cabeça para 150 km; etiquetas de 30 x 30 mm até 60 x 130 mm. Ethernet, Wi-Fi (802.11) e USB (carga por pen drive). Formato torre; prato de 383 x 262 mm. Alimentação de 100 a 240 V. Garantia de 1 ano. Atende a RDC 429/2020 da Anvisa (rotulagem nutricional).
- **Versões:** nas revendas aparecem a EDGE33TE (Ethernet), a EDGE33TW (Wi-Fi) e uma "EDGE Mini" plana (EDGE33MW, com LCD de 4,3"). Confirmar os códigos com o distribuidor Balmak.
- **Página oficial:** <https://balmak.com.br/produtos/automacao-comercial/edge/> (fora do ar em 11/10/2026: certificado incompleto e erro 503). Catálogo: <https://www.brasilinternacional.com/arquivo/catalogo/2_1_balmak-lnea-automatizacin-comercial_3.pdf>
- **Foto oficial:** pedir ao distribuidor (o site não abriu). Certeza: Alta para a ficha; Média para as versões.

#### 3. Urano BA37 (Balanças → Com impressora de etiquetas)
- **Por quê:** a Urano chama a BA37 de "nova linha", e o catálogo ainda não tem nenhuma Urano.
- **Ficha:** 31 kg (6 kg x 2 g / 15 kg x 5 g / 31 kg x 10 g). Versões BA37 (sem coluna), BA37C (com coluna) e BA37-T (com coluna, sem display numérico), cada uma com Ethernet ou Wi-Fi. Impressora térmica embutida; etiqueta de 30 a 60 mm de largura (de 30 x 30 até 60 x 130 mm). Até 10.000 produtos, 300 imagens e 99 modelos de etiqueta. **Sem bateria**; fonte interna de 90 a 240 V. A velocidade de impressão não está na página (confirmar com o fornecedor).
- **Página oficial:** <https://www.urano.com.br/balancas-digitais-ba37>
- **Foto oficial:** <https://www.urano.com.br/wp-content/uploads/2022/08/Foto-alterado-01-nova-1024x643.png>. Certeza: Alta.

#### 4. Toledo Prix 7T (Balanças → Com impressora de etiquetas)
- **Por quê:** é a top de linha da Toledo para supermercado e hortifrúti. Reconhece o produto pela imagem (inteligência artificial) e mostra ofertas para o cliente. Combina com a Prix 7E, que já é a capa do site.
- **Ficha:** 32 kg (6 kg x 2 g / 15 kg x 5 g / 32 kg x 10 g), classe III. Até 100.000 itens. Impressão de 60 a 130 mm/s, de 30 a 60 mm de largura. Tela sensível ao toque de 12,1" para o operador e display de 7" para o cliente. Reconhecimento de produto por imagem e reconhecimento facial do operador. Ethernet e Wi-Fi (802.11 b/g/n); MGV 7 e MGV Cloud.
- **Página oficial:** <https://www.toledobrasil.com/produto/prix-7t/>
- **Foto oficial:** <https://stportalcorporativoprd.blob.core.windows.net/blobappportalc63534e8570/wp-content/uploads/2025/05/Prix-7t-desktop.png>. Certeza: Alta.

#### 5. Urano POP-Z Mira / Mira Pro (Balanças → Computadoras)
- **Por quê:** é a computadora de entrada de uma marca forte no pequeno varejo. Vende bem no balcão por ter bateria e preço baixo.
- **Ficha:** 31 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 31 kg). Bateria de até 200 h, e também funciona com 3 pilhas D. Display com backlight ajustável; teclado metálico na Mira Pro. Prato padrão (330 x 230 mm) ou de frutas (340 x 245 mm). **Não tem saída para PC ou PDV**, então quem precisa integrar ao caixa deve ver a Prix 3 ou a 9094 Plus.
- **Página oficial:** <https://www.urano.com.br/balanca-pop-z-mira/>
- **Foto oficial:** <https://www.urano.com.br/wp-content/uploads/2026/02/mira_pro_visao_cliente_pfrutas-1-scaled.png>. Certeza: Alta.

#### 6. Toledo Prix 6 (Balanças → Com impressora de etiquetas)
- **Por quê:** fica entre a Prix 5 Plus e a Prix 7E: tem display para o cliente com imagens de ofertas e memória grande.
- **Ficha:** 32 kg (6 kg x 2 g / 15 kg x 5 g / 32 kg x 10 g). Até 20.000 itens; 180 itens em teclas rápidas. Display do operador e display do cliente para imagens e mensagens (a página cita TFT de 7" e também display fluorescente: confirmar com a Toledo qual tela fica de qual lado). Ethernet e Wi-Fi. A velocidade de impressão não está na página.
- **Página oficial:** <https://www.toledobrasil.com/produto/prix-6/>
- **Foto oficial:** <https://stportalcorporativoprd.blob.core.windows.net/blobappportalc63534e8570/wp-content/uploads/2024/08/prix-6-desktop.png>. Certeza: Alta.

#### 7. Balmak QUICK (QUICK33B) (Balanças → Computadoras)
- **Por quê:** a Balmak a apresenta como "a primeira e única balança 3 em 1 do Brasil": pesadora, computadora e contadora de peças.
- **Ficha (revendas; confirmar com o fornecedor):** 33 kg (2 g até 6 kg / 5 g até 15 kg / 10 g até 33 kg). Display LED vermelho. Prato inox. Bateria de até 45 h. Memória para 5 itens. Não encontrei saída serial.
- **Fonte:** catálogo oficial jun/2024 (seção "Conheça também") e anúncios de revenda. Foto oficial: pedir ao distribuidor. Certeza: Média.

#### 8. Um leitor de mão 2D (Automação → Leitores de código de barras)
- **Por quê:** é a preparação para o QR Code GS1 de 2027 e também lê QR Code na tela do celular. O catálogo só tem leitor 2D fixo (TL-900).
- **Opções com página oficial:** o Datalogic QW2500 (ficha acima); o **Elgin BR-520**, de mão, 1D/2D, com sensor CMOS e leitura manual ou automática (a ficha completa não abriu: confirmar com o fornecedor), em <https://www.elgin.com.br/leitor-de-codigo-de-barras-de-mao-elgin-1d2d-br-520/p>; e o **Elgin EL720iA**, semifixo 1D/2D, tolera movimento de até 4 m/s, bom para caixa pequeno, em <https://www.elgin.com.br/leitor-de-codigo-de-barras-el720ia-semifixo-leitura-1d-e-2d-tolerancia-a-movimento-de-ate-4ms/p>. Certeza: Média.

**Pesquisados, mas não sugeridos agora:**

- **Ramuza:** não encontrei catálogo oficial online, só anúncios de revenda.
- **Welmy:** só achei a linha W200 (balanças de pessoas para farmácia) em revendas. Se a loja trabalha com elas, peça o catálogo ao representante.
- **Elgin DP30-T** (balança de PDV): aparece "Indisponível" na loja da Elgin.

---

## Como aplicar pelo painel (lista de conferência)

O catálogo que vale é o do **servidor**: faça tudo pelo painel (`https://balancass.com/painel/`, link "Área restrita" no rodapé do site). Cada alteração guarda uma cópia de segurança automática; se errar, use **Desfazer** (logo depois de excluir) ou **Cópias**.

**Regra prática para os substitutos**

- **A loja ainda tem o modelo antigo em estoque:** deixe o antigo no site com estoque "Sob encomenda" ou "Esgotado" (ou esconda) e cadastre o novo em **Novo produto**.
- **A loja não tem mais o antigo:** o mais rápido é **Editar** o antigo e trocar nome, ficha, resumo, descrição e foto. O link antigo continua abrindo. Não mude o "Código do produto" (em Avançado).

### 1. Excluir (lixeira, confirmar)
- [ ] Bematech SAT RB-2000
- [ ] Elgin SAT Smart
- [ ] Tanca SAT TS-1000

### 2. Editar (tocar no produto, mudar e Salvar)
- [ ] **Toledo Prix 2096PP:** na Ficha técnica, linha "Aprovação", trocar o valor por `Inmetro, classe de exatidão III`.
- [ ] **Toledo Prix 5 Plus:** trocar a foto pela foto oficial, em torre (link na tabela de Balanças).
- [ ] **Balmak Órion 1 Key:** conferir se a foto mostra o teclado de teclas individuais (versão Key). Se não mostrar, pedir a foto ao distribuidor.
- [ ] **Elgin L42 Pro:** Nome → `L42 Pro Full`. Na Ficha, Conectividade → `USB, Serial e Ethernet`. Estoque "Consulte".
- [ ] **Bematech Gaveta GD-56:** Nome → `Gaveta GD-56M`. Conferir a ficha com o fornecedor.
- [ ] **Elgin i9 Full:** se a loja já vende a nova, Nome → `i9 Full 2` e foto nova. Bobina, guilhotina e garantia só depois de confirmar com o fornecedor.
- [ ] **NHS Nobreak 400 VA:** Nome → `Nobreak Mini 4 Bivolt 600 VA`, ficha da seção B e foto nova. Ou exclua e cadastre como novo.
- [ ] **APC BZ400BI-BR:** confirmar com o distribuidor. Se acabou, trocar pelo BVX600BI-BR (ficha acima).
- [ ] **Centrium Mini PC:** se a loja tem um modelo fixo, preencher processador, memória, SSD e portas.
- [ ] Substitutos (por Editar ou por Novo, conforme a regra prática):
  - [ ] Argox OS-2140 → Argox OS-214 Pro
  - [ ] Zebra ZT230 → Zebra ZT231
  - [ ] Datalogic QW2100 → Datalogic QuickScan QW2500
  - [ ] Elgin EL4200 → Elgin EL8600iA
  - [ ] Gertec TC 504 → Gertec TC506E 2D
  - [ ] Gertec Busca Preço G2-E → Gertec Busca Preço 2D
  - [ ] Gertec MT 720 → Gertec MT-721 VT100

### 3. Esconder do site (botão "No site", com o olho)
- [ ] Qualquer produto fora de linha que a loja **não** tenha mais e que você ainda não quer apagar: guarde escondido até decidir.

### 4. Novo produto (botão verde "Novo")
- [ ] Toledo **Prix 4 Trend**: Balanças → Com impressora de etiquetas
- [ ] Balmak **EDGE**: Balanças → Com impressora de etiquetas
- [ ] Urano **BA37**: Balanças → Com impressora de etiquetas
- [ ] Toledo **Prix 7T**: Balanças → Com impressora de etiquetas (opcional)
- [ ] Urano **POP-Z Mira / Mira Pro**: Balanças → Computadoras
- [ ] Toledo **Prix 6**: Balanças → Com impressora de etiquetas (opcional)
- [ ] Balmak **QUICK**: Balanças → Computadoras (depois de confirmar a ficha)
- [ ] Um **leitor de mão 2D**: Automação → Leitores de código de barras

Para cada produto novo:

- [ ] Use a ficha da seção B e copie só o que está confirmado. As 3 primeiras linhas da Ficha técnica aparecem no cartão do site, então ponha primeiro Capacidade, Memória e Impressão.
- [ ] Use a foto oficial (link acima) ou uma foto do celular, deitado e com fundo branco.
- [ ] Deixe o estoque em "Consulte" até confirmar com o distribuidor.
- [ ] Destaque: a página inicial mostra no máximo 8. Para um novo entrar, tire a estrela de outro.

### 5. Depois de tudo
- [ ] No painel, toque em **Ver no site** e confira os cartões no celular.
- [ ] Atualize o `docs/FONTES-DOS-PRODUTOS.md` com as fontes dos produtos novos (pedido para o desenvolvedor).

---

### Limitações desta pesquisa

- **Balmak:** o site `balmak.com.br` não abriu (certificado incompleto e erro 503). Usei o catálogo oficial de junho de 2024 numa cópia hospedada por um distribuidor, além de revendas.
- **Gertec:** o site `gertec.com.br` não abriu (erro 503). Usei a loja oficial `loja.gertec.com.br` e o distribuidor BZTech.
- **Elgin:** as páginas de produto da loja oficial não mostraram a tabela técnica. Usei o título do produto, o resumo e distribuidores, sempre com a marca "confirmar com o fornecedor".
- **APC/Schneider:** as páginas de produto bloqueiam acesso automático. A situação do BZ400BI-BR ficou com certeza baixa.
- **Selo "Indisponível":** nas lojas oficiais, ele indica falta de estoque naquela loja, não necessariamente fim de linha. Por isso a L42 Pro Full ficou como "Trocar nome", não como "Fora de linha".
