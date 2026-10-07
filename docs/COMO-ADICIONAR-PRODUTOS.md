# Como adicionar e atualizar produtos no site

O catálogo inteiro vem de **um único arquivo**: `compartilhado/dados/produtos.js`.
As fotos ficam em `compartilhado/img/produtos/`.
Não é preciso mexer no HTML nem no design. Basta editar esse arquivo e enviar as fotos.

> Depois que você escolher o design, as pastas podem mudar de nome (por exemplo `dados/produtos.js` direto no `public_html`). O passo a passo continua igual.

---

## 1. Prepare a foto do produto

- Formato **.webp**, **.jpg** ou **.png**.
- **Fundo branco** e o produto centralizado (foto do fabricante ou foto sua com fundo branco).
- Tamanho bom: **900 px de largura**. Fotos de celular muito grandes (4000 px) deixam o site lento. Se quiser converter e reduzir, use o https://squoosh.app (grátis): arraste a foto, escolha **WebP**, largura **900** e baixe.
- Use um nome **sem espaços e sem acentos**: `toledo-prix-6.webp` ✅, `Balança Prix 6 (1).JPG` ❌.

## 2. Envie a foto pelo cPanel

1. Entre no **cPanel** da HostGator → **Gerenciador de Arquivos**.
2. Abra `public_html` → `compartilhado` → `img` → `produtos`.
3. Clique em **Carregar** (Upload) e envie a foto.

## 3. Cadastre o produto

1. No Gerenciador de Arquivos, abra `public_html` → `compartilhado` → `dados`.
2. Clique com o botão direito em **`produtos.js`** → **Editar** (Edit).
3. **Copie** um bloco inteiro de produto, do `{` até o `},` (exemplo abaixo).
4. **Cole** logo antes da linha final `];`.
5. Troque as informações e clique em **Salvar alterações**.

```js
  {
    id: "toledo-prix-6",
    nome: "Prix 6",
    marca: "Toledo",
    categoria: "balancas",
    subcategoria: "Com impressora de etiquetas",
    resumo: "Balança touch com impressora de etiquetas.",
    descricao: "Balança com tela sensível ao toque, impressora de etiquetas e integração com o PDV.",
    especificacoes: [["Capacidade", "15 kg"], ["Tela", "Touch"]],
    imagem: "toledo-prix-6.webp",
    estoque: "disponivel",
    condicao: "novo",
    preco: "",
    destaque: true
  },
```

### O que vai em cada campo

| Campo | O que colocar |
|---|---|
| `id` | Nome único, minúsculo, sem espaço nem acento. Usado no link direto do produto (`produtos.html#p=toledo-prix-6`). |
| `nome` | Nome do produto, como aparece no site. |
| `marca` | Fabricante (Toledo, Balmak, Elgin…). |
| `categoria` | `"balancas"`, `"automacao"` ou `"informatica"`. |
| `subcategoria` | Copie exatamente uma das subcategorias do topo do arquivo (ex.: `"Computadoras"`, `"Leitores de código de barras"`). |
| `resumo` | Uma frase curta (aparece no card). |
| `descricao` | Texto maior (aparece ao abrir os detalhes). |
| `especificacoes` | Lista de pares `["Nome", "Valor"]`. Pode deixar `[]` se não quiser. |
| `imagem` | Nome exato do arquivo enviado no passo 2. |
| `estoque` | `"disponivel"` (Em estoque), `"encomenda"` (Sob encomenda) ou `"esgotado"`. |
| `condicao` | `"novo"` ou `"seminovo"`. |
| `preco` | `""` para mostrar "Consulte", ou um texto como `"R$ 2.490,00 à vista"`. |
| `destaque` | `true` para aparecer também na página inicial; `false` só no catálogo. |
| `visivel` | (opcional) `false` esconde o produto sem apagar. |

## 4. Tarefas do dia a dia

- **Chegou mercadoria / acabou o estoque:** troque só o `estoque:` do produto (`"disponivel"` ↔ `"encomenda"` ↔ `"esgotado"`).
- **Tirar um produto do site por um tempo:** acrescente `visivel: false,` dentro do bloco.
- **Apagar de vez:** apague o bloco inteiro, do `{` até o `},`.
- **Mudar a ordem:** produtos **em estoque** aparecem primeiro automaticamente; dentro disso, os com `destaque: true` vêm antes.
- **Telefone, endereço, horários, clientes, depoimentos:** ficam em `compartilhado/dados/empresa.js`, editados do mesmo jeito.

## 5. Conferir

Abra o site e aperte **Ctrl + F5** (ou puxe a tela para baixo no celular) para recarregar sem cache.

### Se o catálogo sumiu ou ficou vazio
Quase sempre é um detalhe de digitação no `produtos.js`:
- faltou uma **vírgula** depois de `}` (cada bloco termina com `},`);
- faltou fechar **aspas** (`"texto"`);
- usou aspas dentro do texto: troque `"Balança "top""` por `"Balança 'top'"`;
- apagou sem querer o `];` do final.

Dica: antes de editar, clique com o botão direito em `produtos.js` → **Copiar** e crie um `produtos-backup.js`. Se algo der errado, é só voltar a cópia.

---

## Quer algo ainda mais fácil?

Depois de escolhido o design, dá para criar um **painel com senha** (`seusite.com/painel`) onde você cadastra produtos por formulário, envia a foto direto do celular e marca o estoque com um clique, sem tocar em código. O painel grava este mesmo arquivo, então o site continua leve e, se o painel um dia falhar, a edição manual acima continua funcionando.
