# Novo site · Balanças.com — Automação Comercial

Propostas de design para o novo site da **Balanças.com** (Rio Claro-SP): balanças, PDV, sistema BC System e assistência técnica autorizada pelo IPEM-SP.

## Rodada atual: Linha Verde em várias páginas

A pasta **`linha-verde/`** traz o estilo escolhido (Linha Verde) com página inicial enxuta e abas dedicadas:

| Página | Arquivo |
|---|---|
| Início · Variação 1 (Vitrine) | `linha-verde/index.html` |
| Início · Variação 2 (Capítulos) | `linha-verde/index-2.html` |
| Início · Variação 3 (Painel) ⭐ favorita do júri | `linha-verde/index-3.html` |
| Assistência técnica | `linha-verde/assistencia.html` |
| Produtos (catálogo) | `linha-verde/produtos.html` |
| BC System | `linha-verde/sistema.html` |
| Empresa | `linha-verde/empresa.html` |
| Contato | `linha-verde/contato.html` |

O seletor "Variação 1 · 2 · 3" (arquivo `linha-verde/js/preview.js`) existe só para a fase de escolha e sai na publicação.

## Como ver as propostas

Baixe o projeto (botão **Code → Download ZIP** no GitHub), descompacte e abra o arquivo **`index.html`** no navegador. Ele leva às 3 propostas:

| Proposta | Pasta | Estilo |
|---|---|---|
| 1 · Tara Zero | `design-1-tara-zero/` | escuro premium, visor de LCD que "pesa" |
| 2 · Balcão ⭐ | `design-2-balcao/` | claro, organizado, kits por tipo de comércio |
| 3 · Linha Verde | `design-3-linha-verde/` | industrial, faixa diagonal verde, tipografia gigante |

Cada proposta tem **página inicial**, **catálogo de produtos** e **página de assistência técnica**. A avaliação completa está em [`docs/AVALIACAO-DOS-DESIGNS.md`](docs/AVALIACAO-DOS-DESIGNS.md).

## Estrutura

```
index.html                 página que apresenta as 3 propostas
compartilhado/
  dados/empresa.js         telefone, WhatsApp, endereço, horários, clientes…
  dados/produtos.js        catálogo de produtos (edite aqui)
  js/balancas.js           funções comuns (WhatsApp por produto, filtros, busca)
  img/                     logo em SVG, fotos de produtos, clientes, telas do sistema
design-1-tara-zero/        proposta 1
design-2-balcao/           proposta 2
design-3-linha-verde/      proposta 3 (primeira rodada)
linha-verde/               Linha Verde em várias páginas (rodada atual)
hospedagem/.htaccess       configuração para a HostGator (HTTPS, cache, segurança)
docs/                      guias e avaliações
```

## Guias

- [Como adicionar e atualizar produtos](docs/COMO-ADICIONAR-PRODUTOS.md)
- [Como o site vai ficar no ar (HostGator + cPanel)](docs/PUBLICAR-NO-CPANEL.md)
- [Fontes das especificações dos produtos](docs/FONTES-DOS-PRODUTOS.md)

## Tecnologia

Site 100% estático (HTML, CSS e JavaScript), sem banco de dados e sem WordPress. Roda em qualquer plano da HostGator e abre até direto do computador. Animações com GSAP e Lenis, ambos carregados por CDN. Sem cookies nem rastreadores.
