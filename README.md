# Novo site · Balanças.com — Automação Comercial

Site da **Balanças.com** (Rio Claro-SP): balanças, PDV, sistema BC System e assistência técnica autorizada pelo IPEM-SP.

## Site final

Escolhido: **Linha Verde · Variação 3 (Painel)**, com a frase **“A medida certa para o seu negócio.”** abrindo a página e a balança Toledo **Prix 7E** na capa (o dono pode pôr outro produto na capa pelo painel, em **Empresa → Capa do site**).

A pasta **`site/`** é o pacote pronto para a HostGator: o conteúdo dela vai inteiro para a `public_html`.

| Endereço | O que é |
|---|---|
| `balancass.com/` | Início (variação 3 · Painel) |
| `/assistencia.html` · `/produtos.html` · `/sistema.html` · `/empresa.html` · `/contato.html` | Abas do site |
| `/produtos.html#p=toledo-prix-7e` | Link direto de um produto (abre os detalhes) |
| `/painel/` | **Painel com login** para adicionar, editar, esconder e excluir produtos (um ou **vários de uma vez**), mudar o estoque e trocar fotos, para cuidar das **categorias e subcategorias** do catálogo, dos **logos de clientes** (área **Clientes**: incluir, trocar, reordenar, excluir) e dos **dados da empresa** que o site mostra (área **Empresa**: contato, endereço, horário, feriados, aviso no site, Google e redes, equipe, depoimentos, marcas e o **produto da capa**), e para ver os **produtos mais procurados** e de onde vêm os contatos pelo WhatsApp. Cada pessoa com o seu usuário, como **Administrador** ou **Editor**. Entrada pelo link discreto **“Área restrita”** no rodapé (fora do Google) |
| qualquer endereço inexistente | Página 404 no estilo do site |

**Publicar:** [`docs/PUBLICAR-NO-CPANEL.md`](docs/PUBLICAR-NO-CPANEL.md) (PHP 8.3, envio do `site.zip`, primeiro acesso ao painel, testes).
**Produtos (um ou vários de uma vez), categorias, capa do site, Mais procurados, logos de clientes, usuários e dados da empresa no dia a dia:** [`docs/COMO-ADICIONAR-PRODUTOS.md`](docs/COMO-ADICIONAR-PRODUTOS.md) (pelo painel; edição manual como plano B).
**Atualizar o site que já está no ar:** `atualizacao.zip` (guia de publicação, item 6).

**Gerar o pacote de novo** (depois de mudar `linha-verde/`, `compartilhado/`, `painel/` ou `hospedagem/`):

```bash
python3 ferramentas/montar-site.py          # recria site/ (Python 3, sem instalar nada)
python3 ferramentas/montar-site.py --zip    # recria site/ e gera site.zip (site COMPLETO, 1ª publicação)
python3 ferramentas/montar-site.py --atualizacao                     # site/ + atualizacao.zip (sem produtos, fotos, clientes e ajustes.js)
python3 ferramentas/montar-site.py --atualizacao --incluir-clientes  # idem, levando clientes.js e logos (só se o servidor ainda não tem clientes.js)
python3 ferramentas/montar-site.py --atualizacao --incluir-ajustes   # idem, levando ajustes.js (raramente: o painel cria o vazio sozinho)
```

O script usa `linha-verde/index-3.html` como `index.html`, leva só o CSS/JS que as 6 páginas usam, tira o seletor de variações da fase de escolha, troca `../compartilhado/` por `compartilhado/`, acerta canonical e `og:url`, põe `?v=…` nos CSS/JS, monta `.htaccess` (de `hospedagem/.htaccess`), `robots.txt`, `sitemap.xml`, `404.html` e `favicon.ico`, copia o painel sem dados privados e **confere tudo** (referências quebradas, restos de `../`, formato do catálogo, da lista de clientes, do `empresa.js` e do `ajustes.js`, a ordem `empresa.js` → `ajustes.js` → `clientes.js` → `produtos.js` → `balancas.js` em todas as páginas, nenhum arquivo da pasta privada no pacote). Se algo estiver errado, ele para e a `site/` anterior fica como estava.

**Mudanças no catálogo preparadas no projeto** (produtos novos, modelos que saíram de linha trocados pelos atuais, fotos novas, produtos que saem do site) viram uma **atualização do catálogo** em `painel/pacotes/<AAAA-MM-nome>/` (`pacote.json` + `fotos/` + `recortes/`). Ela vai no `atualizacao.zip` e um administrador revê e aplica no painel (cartão “Atualização do catálogo pronta”, com uma cópia de segurança antes). No projeto, a mesma atualização é aplicada ao `compartilhado/` pela linha de comando, com as mesmas regras do painel:

```bash
php ferramentas/aplicar-pacote.php painel/pacotes/2026-10-catalogo compartilhado --simular   # só mostra
php ferramentas/aplicar-pacote.php painel/pacotes/2026-10-catalogo compartilhado              # aplica (catálogo + fotos)
```

A de **outubro de 2026** (`2026-10-catalogo`: 5 balanças novas, 7 trocas na automação, foto nova da Prix 5 Plus e as Balmak Órion fora) já está aplicada ao `compartilhado/` do projeto. Formato e regras: `painel/LEIA-ME.md`, item 9.

> Depois que o painel estiver em uso, o catálogo, a lista de clientes e os ajustes da área Empresa que valem são os do servidor. Para publicar uma versão nova, use o `atualizacao.zip`, que não leva `produtos.js`, as fotos de produtos, (sem `--incluir-clientes`) o `clientes.js` e os logos, nem (sem `--incluir-ajustes`) o `ajustes.js` (detalhes no guia de publicação, item 6).

## Estrutura

```
site/                      PACOTE PUBLICADO (gerado; não editar à mão)
linha-verde/               fontes do site escolhido: páginas, css/, js/, img/, 404.html
  index-3.html             → vira o site/index.html
  index.html, index-2.html variações 1 e 2 (só para histórico; não vão para o ar)
  COMPONENTES.md           documentação dos componentes visuais
compartilhado/
  dados/empresa.js         valores ORIGINAIS da empresa: telefone, WhatsApp, endereço, horários, equipe… (JSON)
  dados/ajustes.js         o que a área Empresa do painel mudou (gerado pelo painel; vale por cima de empresa.js)
  dados/produtos.js        catálogo de produtos (formato JSON do painel)
  dados/clientes.js        logos de clientes, na ordem do site (gerado pelo painel; vale no lugar da lista de empresa.js)
  js/balancas.js           funções comuns (junta empresa.js + ajustes.js, WhatsApp por produto, filtros, busca)
  img/                     logo em SVG, fotos de produtos, logos de clientes, telas do sistema
painel/                    painel em PHP: produtos (e em lote), categorias, clientes, área Empresa (com a capa), Mais procurados, usuários e papéis (LEIA-ME.md com instalação e segurança)
  contar.php               recebe a contagem anônima do site (Mais procurados): sem sessão, sem cookie
  pacotes/<id>/            atualizações do catálogo (pacote.json + fotos/ + recortes/), aplicadas pelo administrador no painel
hospedagem/                .htaccess do site e das pastas de fotos, favicon.ico
ferramentas/
  montar-site.py           gera a pasta site/ (e o site.zip ou o atualizacao.zip); confere as atualizações do catálogo
  aplicar-pacote.php       aplica uma atualização do catálogo ao compartilhado/ do projeto (mesmas funções do painel)
  converter-produtos.mjs   conversão única do catálogo antigo para o formato do painel
docs/                      guias e avaliações
design-1-tara-zero/  design-2-balcao/  design-3-linha-verde/   propostas da 1ª rodada
index.html                 página que apresenta as 3 propostas da 1ª rodada
```

## Guias

- [Como colocar o site no ar (HostGator + cPanel)](docs/PUBLICAR-NO-CPANEL.md)
- [Como adicionar e atualizar produtos (painel)](docs/COMO-ADICIONAR-PRODUTOS.md)
- [Painel: instalação, segurança e problemas comuns](painel/LEIA-ME.md)
- [Fontes das especificações dos produtos](docs/FONTES-DOS-PRODUTOS.md)
- [Catálogo: o que estava velho e o que mudou em outubro de 2026](docs/ATUALIZAR-BALANCAS.md)
- [Avaliação das propostas de design](docs/AVALIACAO-DOS-DESIGNS.md)

## Tecnologia

Páginas 100% estáticas (HTML, CSS e JavaScript), sem banco de dados e sem WordPress. Animações com GSAP e Lenis, carregados por CDN. Sem cookies nem rastreadores no site: a única contagem é a própria, anônima (sem cookies, sem serviços de fora, sem guardar o IP), para o painel mostrar os produtos mais procurados. O painel é PHP puro (8.1 a 8.3), também sem banco de dados: grava direto no `compartilhado/dados/produtos.js`, no `compartilhado/dados/clientes.js`, no `compartilhado/dados/ajustes.js` e nas pastas de fotos e de logos, com usuários (Administrador ou Editor), cópias de segurança automáticas e proteção contra os ataques comuns.

## Histórico das propostas

Na 1ª rodada foram feitas 3 propostas (abra o `index.html` da raiz para ver): **1 · Tara Zero** (escuro premium), **2 · Balcão** (claro, kits por tipo de comércio) e **3 · Linha Verde** (industrial, faixa diagonal verde). A Linha Verde foi escolhida e ganhou páginas dedicadas e três variações de página inicial em `linha-verde/` (1 · Vitrine, 2 · Capítulos, 3 · Painel); o dono escolheu a **3 · Painel**. Avaliação completa em [`docs/AVALIACAO-DOS-DESIGNS.md`](docs/AVALIACAO-DOS-DESIGNS.md).

As propostas da 1ª rodada e as variações 1 e 2 continuam abrindo direto do computador (leem o mesmo `compartilhado/`), mas não fazem parte do site publicado.
