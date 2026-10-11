# Como adicionar e atualizar produtos no site

O jeito principal é o **painel do site**: **`https://balancass.com/painel/`**.
Pelo celular ou pelo computador você cadastra, edita, esconde e exclui produtos, troca o estoque e envia fotos, **sem mexer em código**. O que você salva aparece no site na hora.

Editar o arquivo do catálogo à mão continua possível, mas virou **plano B** (fim desta página).

> No site, a entrada do painel é o link discreto **“Área restrita”**, no rodapé de todas as páginas. O painel não aparece no Google. Vale salvar o endereço nos favoritos do celular.

---

## 1. Primeiro acesso (uma vez só)

Feito por quem tem a senha do **cPanel** da HostGator, logo depois de o site ir para o ar.

1. Abra **`https://balancass.com/painel/`**. Aparece a tela **Primeiro acesso**.
2. Nesse momento o painel grava um **código** num arquivo que só quem entra no cPanel consegue ler.
3. Em outra aba: **cPanel → Gerenciador de Arquivos** → pasta **inicial da conta** (a mesma que tem a `public_html`) → abra **`painel-balancas-privado`** → botão direito em **`primeiro-acesso.php`** → **Ver**. Copie o código (ex.: `7KQ4-M9TX-2HPA`).
4. Volte ao painel, cole o código, escolha o **usuário** (ex.: `fabio`) e uma **senha com pelo menos 10 caracteres**. Dica: junte 3 ou 4 palavras que só você saiba, com um número. Não copie exemplos de guias (o painel recusa).
5. Pronto, você já entra. O arquivo do código some sozinho.

O código vale 24 horas. Se vencer, abra o painel de novo e leia o código novo no mesmo arquivo.

**No dia a dia:** abra o painel, digite usuário e senha. Por segurança ele sai sozinho depois de **2 horas sem uso**. Se isso acontecer com um produto aberto, ao tocar em **Salvar** o painel pede a senha numa janela e salva em seguida, sem perder o que você digitou nem a foto.

---

## 2. Adicionar um produto

1. No painel, toque em **Novo** (botão verde).
2. Preencha:

| Campo | O que colocar |
|---|---|
| **Foto** | Foto do celular ou do fabricante (veja o item 5). |
| **Nome do produto** | Como aparece no site. Ex.: `Prix 7E`. |
| **Marca** | Toledo, Balmak, Elgin… |
| **Categoria / Subcategoria** | Escolha nas listas (Balanças, Automação Comercial, Informática e PDV). |
| **Resumo** | Uma frase curta. Aparece no cartão do produto. |
| **Descrição** | Texto maior. Aparece ao abrir os detalhes. |
| **Ficha técnica** | Uma linha por característica: nome à esquerda (`Capacidade`), valor à direita (`15 kg`). As **3 primeiras linhas** aparecem no cartão. |
| **Estoque** | O selo do site: *Consulte disponibilidade* (padrão), *Pronta entrega*, *Sob encomenda* ou *Esgotado*. |
| **Condição** | Novo ou Seminovo (o selo só aparece no seminovo). |
| **Preço** | Opcional. Vazio mostra **“Consulte”**. Ex.: `R$ 2.490,00 à vista`. |
| **No site** | Ligado = aparece no site. Desligado = guardado no painel, escondido do site. |
| **Destaque** | Sobe no catálogo e entra na página inicial, que mostra até **8** destaques (veja o item 3). |

3. **Salvar.** O produto já está no site, com link próprio (`balancass.com/produtos.html#p=toledo-prix-7e`).

Depois de salvar, a lista abre no produto, com o aviso “foi cadastrado” fixo na tela e o atalho **Ver no site**.

O **código do produto** (o final desse link) é criado sozinho a partir da marca e do nome. Fica em **Avançado**; ao editar um produto antigo, evite mudar, porque links já enviados por WhatsApp ou achados no Google deixariam de abrir direto nele (quem abrir um link antigo vê o aviso “produto fora do catálogo” com o botão do WhatsApp). Em **Avançado** também há a **Anotação interna**: fica só no painel (na pasta privada da hospedagem), não aparece no site nem no arquivo do catálogo.

**Rascunho:** o que você digita fica guardado no próprio aparelho até salvar. Se fechar a página sem querer, ao abrir o mesmo produto (ou **Novo**) aparece **Recuperar** (a foto precisa ser escolhida de novo).

---

## 3. Editar, esconder, excluir, mudar a ordem

Na tela **Produtos** (a inicial do painel) estão todos, separados por categoria, com foto. Use a **busca** (nome, marca ou código, sem precisar de acento) ou os filtros.

- **Editar**: abre o formulário completo do produto.
- **★ Destaque**: o produto sobe no catálogo e entra na **página inicial**. Ela mostra **até 8 destaques**, revezando as categorias (o 1º destaque de Balanças, o 1º de Automação, o 1º de Informática, o 2º de Balanças…). O selo diz onde ele está: *Destaque · página inicial* ou *Destaque · fora da página inicial*. Para um destaque “de fora” entrar, tire a estrela de outro ou suba este com as setas.
- **Na capa do site**: selo do produto da balança grande do topo da página inicial (hoje a Toledo **Prix 7E**). Ela não se repete na vitrine logo abaixo. Esconder, excluir ou mudar o código dela pede confirmação, porque o link da capa deixaria de abrir os detalhes.
- **👁 No site**: esconde o produto **sem apagar** (ele ganha o selo *Fora do site* no painel). Bom para produto que acabou e vai voltar.
- **🗑 Excluir**: pede confirmação. Logo depois, o aviso “foi excluído” tem o botão **Desfazer**: o produto volta no mesmo lugar e com a foto.
- **↑ ↓**: muda a ordem dentro da categoria. No site vale: **Pronta entrega** primeiro, depois os **destaques**, depois **esta ordem**; **Esgotado** fica por último.
- No celular, os botões ★ e 👁 mostram o nome embaixo (*Destaque*, *No site*).

## 4. Estoque

Troque no seletor ao lado do produto e pronto: salva sozinho. No site aparece o selo:

| No painel | No site |
|---|---|
| Consulte | **Consulte disponibilidade** (padrão, tom neutro) |
| Pronta entrega | **Pronta entrega**, e o filtro “Pronta entrega” aparece sozinho no catálogo |
| Sob encomenda | **Sob encomenda** |
| Esgotado | **Esgotado** |

O catálogo sempre mostra a nota “Estoque sujeito à disponibilidade. Confirme pelo WhatsApp.”

## 5. Fotos

- Pode ser foto do **celular**: o painel gira, corta as bordas brancas, reduz e otimiza sozinho. Ou a foto do fabricante.
- Para ficar como as outras do site: **celular deitado**, produto ocupando quase toda a foto, sobre **fundo branco ou liso**. O formulário mostra a foto no formato do cartão do site e avisa quando ela vai aparecer pequena (foto em pé ou fundo que não é branco). Dá para salvar assim mesmo.
- **JPG, PNG ou WebP**, até 8 MB, com pelo menos 300 px de largura e de altura. Celular que salva em **HEIC**: na câmera, desligue “alta eficiência” (HEIF) ou compartilhe a foto como JPG.
- PNG com **fundo transparente** também vira o “recorte” usado nas áreas escuras do site.
- Para trocar: **Editar** → nova foto → **Salvar**. A antiga vai para uma lixeira privada (volta se você restaurar uma cópia).

## 6. Errou? Desfaça em “Cópias”

Antes de **cada** alteração o painel guarda uma **cópia de segurança** do catálogo: as 30 mais recentes sempre, tudo dos últimos 14 dias (até 200) e as de **excluir** e **restaurar** por 30 dias. Mudanças seguidas do mesmo tipo (setas, estoque, destaque, no site) feitas em menos de 10 minutos ficam numa cópia só.

- **Trazer de volta** (nas cópias de exclusão): devolve só aquele produto, no mesmo lugar e com a foto. É o mesmo que o **Desfazer** do aviso.
- **Restaurar**: volta o catálogo **inteiro**, com as fotos, para aquele momento, e desfaz também o que foi feito depois. A janela de confirmação diz quantas alterações serão desfeitas e quais. O estado atual também vira cópia, então dá para desfazer a própria restauração.

## 7. Senha

- **Trocar a senha:** **Conta** → Trocar senha (os outros aparelhos conectados saem).
- **Esqueci a senha:** cPanel → Gerenciador de Arquivos → `painel-balancas-privado` (ao lado da `public_html`; não é a pasta `painel` do site) → apague **só** o `config.php` → abra o painel e faça o **Primeiro acesso** de novo (item 1). Produtos, fotos e cópias continuam lá. O passo a passo também aparece no painel, em **Esqueci a senha** e em **Minha conta**.
- **Bloqueado?** Depois de 5 senhas erradas seguidas, aquela internet fica bloqueada por 15 minutos. É só esperar.

Detalhes técnicos e problemas comuns: `painel/LEIA-ME.md`.

---

## 8. O que o painel não edita

- **Telefone, WhatsApp, e-mail, endereço, horários, clientes, depoimentos:** ficam em `public_html/compartilhado/dados/empresa.js` (cPanel → Gerenciador de Arquivos → botão direito → **Editar**). Mude só o texto entre aspas e salve. Antes, faça uma cópia do arquivo (botão direito → **Copiar** → `empresa-backup.js`).
- **Feriados** (aviso “Aberto agora / Fechado agora”): os nacionais e o 9 de julho já estão no site. Feriado municipal, Carnaval, Corpus Christi ou recesso: lista `FERIADOS` no arquivo `linha-verde/js/core.js` do projeto; depois gere o pacote de novo (`python3 ferramentas/montar-site.py`) e envie o `js/core.js` novo. Não edite o `core.js` direto no servidor: o navegador dos clientes guarda os arquivos CSS/JS por até um mês, e só o pacote novo avisa que o arquivo mudou.
- **Lista para quem navega sem JavaScript:** o `produtos.html` tem uma lista curta e fixa de destaques dentro de `<noscript>` (quase ninguém vê; o Google lê a lista completa, que é gerada sozinha). Se trocar muito os destaques, atualize essa lista no projeto quando gerar o próximo pacote.

---

## Plano B: editar o catálogo à mão

Use quando o painel estiver fora do ar (ou para quem mantém o site fazer uma mudança grande de uma vez). O arquivo é **`public_html/compartilhado/dados/produtos.js`**; as fotos ficam em **`public_html/compartilhado/img/produtos/`**.

O arquivo agora está no **formato JSON**, o formato que o painel lê e grava. Ele é mais rígido que o antigo:

- **Toda chave entre aspas duplas**: `"nome": "Prix 7E"` ✅ — `nome: "Prix 7E"` ❌
- Textos sempre entre **aspas duplas** (`"…"`). Aspas dentro do texto: use `'` simples ou `\"`.
- `true`, `false` e listas `[ ]` **sem** aspas.
- **Vírgula entre os itens**, mas **nenhuma vírgula depois do último** item de uma lista ou de um bloco.
- **Sem comentários** (`//` ou `/* */`) no meio dos produtos. Anotação interna: use o painel (ela fica na pasta privada, fora deste arquivo público).

### Passo a passo

1. **Foto:** cPanel → Gerenciador de Arquivos → `public_html/compartilhado/img/produtos` → **Carregar**. Nome sem espaços e sem acentos (`toledo-prix-6.webp` ✅, `Balança Prix 6 (1).JPG` ❌), de preferência com 900 px de largura e fundo branco (o https://squoosh.app converte e reduz de graça).
2. **Cópia de segurança:** em `public_html/compartilhado/dados`, botão direito em `produtos.js` → **Copiar** → `produtos-backup.js`.
3. Botão direito em `produtos.js` → **Editar**. Copie um bloco inteiro de produto, do `{` até o `},`, cole logo depois dele e troque as informações:

```json
    {
        "id": "toledo-prix-6",
        "nome": "Prix 6",
        "marca": "Toledo",
        "categoria": "balancas",
        "subcategoria": "Com impressora de etiquetas",
        "resumo": "Balança touch com impressora de etiquetas.",
        "descricao": "Balança com tela sensível ao toque, impressora de etiquetas e integração com o PDV.",
        "especificacoes": [
            ["Capacidade", "15 kg"],
            ["Tela", "Touch"]
        ],
        "imagem": "toledo-prix-6.webp",
        "estoque": "consulte",
        "condicao": "novo",
        "preco": "",
        "destaque": true
    },
```

4. **Salvar alterações** e confira o catálogo no site. Depois abra também o **painel**, que é mais exigente que o site: se a lista de produtos aparece normal, o arquivo está certo. Se aparecer **“Não consegui ler o catálogo”**, o painel diz o motivo (ex.: “não é JSON válido”): corrija o arquivo, volte o `produtos-backup.js` ou use **Ver cópias de segurança** → **Restaurar**.

### O que vai em cada campo

| Campo | O que colocar |
|---|---|
| `"id"` | Código único, minúsculo, só letras, números e hífen. Vira o link `produtos.html#p=toledo-prix-6`. |
| `"categoria"` | `"balancas"`, `"automacao"` ou `"informatica"`. |
| `"subcategoria"` | Exatamente uma das listadas em `window.CATEGORIAS`, no topo do arquivo (ex.: `"Computadoras"`). |
| `"especificacoes"` | Lista de pares `["Nome", "Valor"]`; pode ser `[]`. |
| `"imagem"` | Nome exato do arquivo enviado no passo 1. |
| `"recorte"` | (opcional) foto com fundo transparente em `img/produtos-recorte/`. |
| `"estoque"` | `"consulte"` (padrão), `"disponivel"` (Pronta entrega), `"encomenda"` ou `"esgotado"`. |
| `"condicao"` | `"novo"` ou `"seminovo"`. |
| `"preco"` | `""` mostra “Consulte”; ou um texto como `"R$ 2.490,00 à vista"`. |
| `"destaque"` | `true` (sobe no catálogo e entra na página inicial, até 8 destaques) ou `false`. |
| `"visivel"` | (opcional) `false` esconde do site sem apagar. |
| `"nota"` | Não use aqui: o arquivo é público. A anotação interna fica no painel; se houver uma `"nota"` no arquivo, o painel a move para a pasta privada na próxima gravação. |

Para conferir no site, recarregue sem cache: **Ctrl + F5** no computador ou puxe a tela para baixo no celular.

> Quem mantém o projeto: o arquivo antigo (JavaScript à mão) foi convertido uma vez com `node ferramentas/converter-produtos.mjs compartilhado/dados/produtos.js`. Rodar de novo no arquivo convertido não muda nada; `--verificar` só confere.
