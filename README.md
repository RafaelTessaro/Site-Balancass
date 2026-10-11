# Novo site · Balanças.com — Automação Comercial

Site da **Balanças.com** (Rio Claro-SP): balanças, PDV, sistema BC System e assistência técnica autorizada pelo IPEM-SP.

## Site final

Escolhido: **Linha Verde · Variação 3 (Painel)**, com a frase **“A medida certa para o seu negócio.”** abrindo a página e a balança Toledo **Prix 7E** na capa.

A pasta **`site/`** é o pacote pronto para a HostGator: o conteúdo dela vai inteiro para a `public_html`.

| Endereço | O que é |
|---|---|
| `balancass.com/` | Início (variação 3 · Painel) |
| `/assistencia.html` · `/produtos.html` · `/sistema.html` · `/empresa.html` · `/contato.html` | Abas do site |
| `/produtos.html#p=toledo-prix-7e` | Link direto de um produto (abre os detalhes) |
| `/painel/` | **Painel com login** para adicionar, editar, esconder e excluir produtos, mudar o estoque e trocar fotos. Entrada pelo link discreto **“Área restrita”** no rodapé (fora do Google) |
| qualquer endereço inexistente | Página 404 no estilo do site |

**Publicar:** [`docs/PUBLICAR-NO-CPANEL.md`](docs/PUBLICAR-NO-CPANEL.md) (PHP 8.3, envio do `site.zip`, primeiro acesso ao painel, testes).
**Produtos no dia a dia:** [`docs/COMO-ADICIONAR-PRODUTOS.md`](docs/COMO-ADICIONAR-PRODUTOS.md) (pelo painel; edição manual como plano B).

**Gerar o pacote de novo** (depois de mudar `linha-verde/`, `compartilhado/`, `painel/` ou `hospedagem/`):

```bash
python3 ferramentas/montar-site.py          # recria site/ (Python 3, sem instalar nada)
python3 ferramentas/montar-site.py --zip    # recria site/ e gera site.zip para o cPanel
```

O script usa `linha-verde/index-3.html` como `index.html`, leva só o CSS/JS que as 6 páginas usam, tira o seletor de variações da fase de escolha, troca `../compartilhado/` por `compartilhado/`, acerta canonical e `og:url`, põe `?v=…` nos CSS/JS, monta `.htaccess` (de `hospedagem/.htaccess`), `robots.txt`, `sitemap.xml`, `404.html` e `favicon.ico`, copia o painel sem dados privados e **confere tudo** (referências quebradas, restos de `../`, formato do catálogo). Se algo estiver errado, ele para e a `site/` anterior fica como estava.

> Depois que o painel estiver em uso, o catálogo que vale é o do servidor. Ao publicar uma versão nova, não sobrescreva `compartilhado/dados/produtos.js` nem as fotos de produtos (detalhes no guia de publicação).

## Estrutura

```
site/                      PACOTE PUBLICADO (gerado; não editar à mão)
linha-verde/               fontes do site escolhido: páginas, css/, js/, img/, 404.html
  index-3.html             → vira o site/index.html
  index.html, index-2.html variações 1 e 2 (só para histórico; não vão para o ar)
  COMPONENTES.md           documentação dos componentes visuais
compartilhado/
  dados/empresa.js         telefone, WhatsApp, endereço, horários, clientes…
  dados/produtos.js        catálogo de produtos (formato JSON do painel)
  js/balancas.js           funções comuns (WhatsApp por produto, filtros, busca)
  img/                     logo em SVG, fotos de produtos, clientes, telas do sistema
painel/                    painel de produtos em PHP (LEIA-ME.md com instalação e segurança)
hospedagem/                .htaccess do site e das pastas de fotos, favicon.ico
ferramentas/
  montar-site.py           gera a pasta site/ (e o site.zip)
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
- [Avaliação das propostas de design](docs/AVALIACAO-DOS-DESIGNS.md)

## Tecnologia

Páginas 100% estáticas (HTML, CSS e JavaScript), sem banco de dados e sem WordPress. Animações com GSAP e Lenis, carregados por CDN. Sem cookies nem rastreadores no site. O painel é PHP puro (8.1 a 8.3), também sem banco de dados: grava direto no `compartilhado/dados/produtos.js` e nas pastas de fotos, com login, cópias de segurança automáticas e proteção contra os ataques comuns.

## Histórico das propostas

Na 1ª rodada foram feitas 3 propostas (abra o `index.html` da raiz para ver): **1 · Tara Zero** (escuro premium), **2 · Balcão** (claro, kits por tipo de comércio) e **3 · Linha Verde** (industrial, faixa diagonal verde). A Linha Verde foi escolhida e ganhou páginas dedicadas e três variações de página inicial em `linha-verde/` (1 · Vitrine, 2 · Capítulos, 3 · Painel); o dono escolheu a **3 · Painel**. Avaliação completa em [`docs/AVALIACAO-DOS-DESIGNS.md`](docs/AVALIACAO-DOS-DESIGNS.md).

As propostas da 1ª rodada e as variações 1 e 2 continuam abrindo direto do computador (leem o mesmo `compartilhado/`), mas não fazem parte do site publicado.
