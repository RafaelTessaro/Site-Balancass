# Como adicionar e atualizar produtos no site

O jeito principal é o **painel do site**: **`https://balancass.com/painel/`**.
Pelo celular ou pelo computador você cadastra, edita, esconde e exclui produtos, troca o estoque e envia fotos, **sem mexer em código**. Na área **Clientes** você também coloca, troca e reordena os **logos dos clientes** (item 8), e na área **Empresa** muda telefone, horário, feriados, aviso no site, equipe e outros dados da empresa (item 10). Cada pessoa entra com o seu usuário, como **Administrador** ou **Editor** (item 9). O que você salva aparece no site na hora. Quando quem cuida do site prepara produtos novos ou trocas de modelos, eles chegam prontos no painel para você conferir e aplicar (item 12). Dá também para mudar **vários produtos de uma vez** (item 13), **trocar o produto da capa** do site (item 14), criar e renomear **categorias** (item 15) e ver os **produtos mais procurados** e de onde vêm os contatos pelo WhatsApp (item 16).

Editar o arquivo do catálogo à mão continua possível, mas virou **plano B** (fim desta página).

> No site, a entrada do painel é o link discreto **“Área restrita”**, no rodapé de todas as páginas. O painel não aparece no Google. Vale salvar o endereço nos favoritos do celular.

---

## 1. Primeiro acesso (uma vez só)

Feito por quem tem a senha do **cPanel** da HostGator, logo depois de o site ir para o ar.

1. Abra **`https://balancass.com/painel/`**. Aparece a tela **Primeiro acesso**.
2. Nesse momento o painel grava um **código** num arquivo que só quem entra no cPanel consegue ler.
3. Em outra aba: **cPanel → Gerenciador de Arquivos** → pasta **inicial da conta** (a mesma que tem a `public_html`) → abra **`painel-balancas-privado`** → botão direito em **`primeiro-acesso.php`** → **Ver**. Copie o código (ex.: `7KQ4-M9TX-2HPA`).
4. Volte ao painel, cole o código, escreva **seu nome**, escolha o **usuário** (ex.: `fabio`) e uma **senha com pelo menos 10 caracteres**. Dica: junte 3 ou 4 palavras que só você saiba, com um número. Não copie exemplos de guias (o painel recusa).
5. Pronto, você já entra, como **Administrador**. O arquivo do código some sozinho.

**Painel que já estava em uso:** não precisa fazer o primeiro acesso de novo. Depois da atualização, o usuário de antes vira **Administrador** sozinho, com a mesma senha (quem estava com o painel aberto continua logado). Para pôr o seu nome: **Conta → Mudar o meu nome**.

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
| **Categoria / Subcategoria** | Escolha nas listas (Balanças, Automação Comercial, Informática e PDV… — as da tela **Categorias**, item 15). Falta uma? Um administrador cria em **Categorias** (o que você já digitou fica no rascunho). |
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
- **★ Destaque**: o produto sobe no catálogo e entra na **página inicial**. Ela mostra **até 8 destaques**, revezando as categorias na ordem das abas (o 1º destaque de Balanças, o 1º de Automação, o 1º de Informática, o 2º de Balanças…). O selo diz onde ele está: *Destaque · página inicial* ou *Destaque · fora da página inicial*. Para um destaque “de fora” entrar, tire a estrela de outro ou suba este com as setas.
- **Na capa do site**: selo do produto grande do topo da página inicial (a Toledo **Prix 7E**, ou o que um administrador escolheu em **Empresa → Capa do site**, item 14). Ele não se repete na vitrine logo abaixo. Esconder, excluir ou mudar o código dele pede confirmação: com a Prix 7E, o link da capa deixaria de abrir os detalhes; com uma capa escolhida no painel, a página inicial volta a mostrar a Prix 7E (e o topo da lista de produtos avisa até você resolver).
- **Selecionar** (vários de uma vez): veja o item 13.
- **Mais procurados** (cartão no topo da lista): veja o item 16.
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

As cópias ficam em **Conta → Cópias de segurança** (só administradores), com as abas **Produtos**, **Clientes** e **Empresa**. Quem é editor tem o **Desfazer** logo depois de excluir; para o resto, peça a um administrador.

Antes de **cada** alteração o painel guarda uma **cópia de segurança** do catálogo: as 30 mais recentes sempre, tudo dos últimos 14 dias (até 200) e as de **excluir** e **restaurar** por 30 dias. Mudanças seguidas do mesmo tipo (setas, estoque, destaque, no site) feitas em menos de 10 minutos ficam numa cópia só.

- **Trazer de volta** (nas cópias de exclusão): devolve só aquele produto, no mesmo lugar e com a foto. É o mesmo que o **Desfazer** do aviso. Numa exclusão de vários de uma vez: **Trazer de volta os N** (todos) ou, em *Ver os N produtos…*, um por um.
- **Restaurar**: volta o catálogo **inteiro**, com as fotos, para aquele momento, e desfaz também o que foi feito depois. A janela de confirmação diz quantas alterações serão desfeitas e quais. O estado atual também vira cópia, então dá para desfazer a própria restauração.

## 7. Senha

- **Trocar a senha:** **Conta** → Trocar senha (os outros aparelhos conectados saem).
- **Esqueci a senha:** peça a um administrador para redefinir a sua senha (**Conta → Usuários → Redefinir senha**); você recebe uma senha temporária e cria a sua no primeiro acesso.
- **É o único administrador?** cPanel → Gerenciador de Arquivos → `painel-balancas-privado` (ao lado da `public_html`; não é a pasta `painel` do site) → **+ Arquivo** → crie um arquivo vazio chamado **`recuperar.txt`** → abra o painel: aparece **Recuperar acesso**. Leia o código em `primeiro-acesso.php` (mesma pasta, como no item 1), digite o código, o seu usuário de administrador e a senha nova. O painel apaga o `recuperar.txt` sozinho. Usuários, produtos, fotos e cópias continuam como estavam. Não apague mais o `config.php`: ele não guarda mais a senha. O passo a passo também aparece no painel, em **Esqueci a senha** e em **Conta**.
- **Bloqueado?** Depois de 5 senhas erradas seguidas, aquele usuário fica bloqueado por 15 minutos (quem usa outro usuário, mesmo no Wi-Fi da loja, continua entrando). É só esperar.

Detalhes técnicos e problemas comuns: `painel/LEIA-ME.md`.

---

## 8. Logos de clientes

Os logos da faixa **“Quem confia na gente”** (página inicial) e de **“Alguns clientes”** (página Empresa) são cadastrados no painel, em **Clientes** (no celular, o ícone de loja na barra de baixo; no computador, no menu do alto). O que você salva aparece nas duas páginas na hora.

**Antes de começar, consiga o logo:** o melhor é um **PNG com fundo transparente** (peça ao cliente, à gráfica ou à agência dele, ou salve do site do cliente). PNG, JPG ou WebP com **fundo branco** também servem. Até 5 MB e com pelo menos 80 px de largura (o ideal é 300 px ou mais). **SVG não é aceito** (por segurança): peça o PNG, ou abra o SVG no navegador, tire um print e use o print.

### Adicionar um cliente

1. No painel, toque em **Clientes** → **Novo cliente**.
2. Toque em **Escolher logo** e escolha o arquivo (no computador, também dá para arrastar o arquivo para a prévia).
3. Confira a prévia **“Como fica no site”**: é a mesma chapa branca do site, com as sobras vazias já cortadas. A tela avisa (em amarelo) quando o logo vai ficar ruim: fundo colorido (vira um retângulo dentro da chapa), logo quase todo branco (some na chapa branca) ou logo pequeno demais (fica sem nitidez). Dá para salvar assim mesmo, mas vale procurar uma versão melhor.
4. Escreva o **nome do cliente** (ex.: `Supermercado Bom Preço`). Ele não aparece escrito no site: é o texto do logo para o Google, para leitores de tela e para quando a imagem não carrega.
5. **Cadastrar.** O cliente novo entra no **fim da lista**.

O painel confere o arquivo, corta as sobras transparentes ou brancas, reduz para no máximo 600 × 300 px e salva em WebP, com a transparência.

### Mudar a ordem, trocar o logo, excluir

Na tela **Clientes** aparecem todos, na **ordem do site** (“1º no site”, “2º no site”…), cada um com o logo na chapa branca.

- **↑ ↓**: muda a posição do logo no site (a mesma ordem vale para a página inicial e para a página Empresa).
- **Editar**: troca o nome e/ou o logo (escolha o arquivo novo e **Salvar**). O logo antigo vai para a lixeira do painel.
- **🗑 Excluir**: pede confirmação; o logo sai do site na hora. Logo depois, o aviso “foi excluído” tem o botão **Desfazer**: o cliente volta no mesmo lugar e com o logo.
- **Errou mais de uma coisa?** **Cópias** → aba **Clientes**: as mesmas cópias de segurança dos produtos, só que da lista de clientes (**Trazer de volta** um cliente excluído ou **Restaurar** a lista inteira de um momento). As cópias de produtos e de clientes são separadas: restaurar uma não mexe na outra.

> **Aviso “A lista de clientes ainda não foi instalada no servidor”**: o site no ar ainda não recebeu a atualização que traz a lista (`compartilhado/dados/clientes.js`). Instale a atualização primeiro (`docs/PUBLICAR-NO-CPANEL.md` → “Atualizar o site já publicado”) e só depois cadastre logos. Se cadastrar antes, o painel cria a lista só com o que você cadastrar, e os logos antigos deixam de aparecer.

## 9. Usuários e permissões

Cada pessoa que mexe no site tem o **seu usuário e a sua senha** (não compartilhe a sua). Há dois papéis:

- **Administrador**: faz tudo. Produtos, Clientes, **Empresa** (com a **Capa do site**), **Categorias**, **Cópias de segurança**, **Usuários** e **Atividades** (o que cada um fez).
- **Editor**: Produtos (um ou vários de uma vez, inclusive o **Desfazer** logo depois de excluir), Clientes, **Mais procurados** e a própria senha. Não vê Empresa, Categorias, Cópias, Usuários nem as atividades dos outros.

**Dar acesso a alguém** (administrador): **Conta → Usuários → Novo usuário** → nome, usuário (o painel sugere) e papel → **Salvar**. Aparece uma **senha temporária**, uma vez só: toque em **Copiar mensagem** e mande pelo WhatsApp. No primeiro acesso, a pessoa cria a própria senha.

- **Esqueceu a senha?** **Redefinir senha** gera outra senha temporária.
- **Saiu da empresa?** **Desativar** (dá para reativar depois) ou **Excluir**. A pessoa sai do painel na hora; o que ela fez no site continua.
- **Mudar o papel:** **Editar** → papel → **Salvar**. Vale na hora.
- O painel nunca fica sem administrador: ninguém tira o próprio papel de administrador nem se desativa.

## 10. Dados da empresa

Em **Empresa** (só administradores), o que o site mostra sobre a loja, em 10 partes (a 10ª, **Capa do site**, está no item 14). Cada parte tem o seu **Salvar**, diz onde aparece no site e tem o link **Ver no site**. Salvou, o site já mostra.

- **Contato e WhatsApp:** telefone, WhatsApp, a mensagem que já vem escrita no WhatsApp e o e-mail. Digite o número com o DDD, do jeito que quiser (`(19) 99999-9999`).
- **Endereço e mapa:** rua, referência, bairro, cidade, estado, CEP e o link do Google Maps (no Maps: **Compartilhar → Copiar link**).
- **Telefone ou endereço novos:** o site muda na hora, mas a busca do Google pode levar alguns dias para mostrar o dado novo. O resumo que aparece quando alguém compartilha o link do site (no WhatsApp, no Facebook) é um texto fixo das páginas: o painel não muda (veja o item 11).
- **Horário:** até 2 horários por dia ou **Fechado**. **Repetir o horário de segunda de terça a sexta** poupa digitação. O texto do site (“Segunda a sexta — 8h às 11h · 13h às 18h”) e o selo **Aberto agora / Fechado agora** saem daqui.
- **Feriados e dias fechados:** os feriados nacionais e o 9 de julho o site já conta sozinho. Acrescente os da cidade (dia/mês), marque Carnaval ou Corpus Christi, e cadastre dias avulsos (ponte, recesso, inventário). As **Sugestões para Rio Claro** só valem depois de salvas: confira no calendário da Prefeitura.
- **Aviso no site:** uma faixa no topo de todas as páginas (verde para novidade, amarela para atenção), com começo e fim opcionais (ex.: “Fechados de 24/12 a 01/01 para férias coletivas”). Para tirar, desligue e salve.
- **Google e redes sociais:** nota e número de avaliações, link das avaliações, Facebook e Instagram (pode ser só `@perfil`; em branco, o link some do site).
- **Equipe** e **Depoimentos:** uma linha por pessoa ou depoimento, com ↑ ↓ para a ordem do site. Use depoimentos reais, com a autorização do cliente.
- **Marcas:** uma por linha, na ordem da faixa de marcas.

Mudou e se arrependeu? **Conta → Cópias de segurança → aba Empresa → Restaurar**. As mudanças ficam no arquivo `compartilhado/dados/ajustes.js`, que as atualizações do site **não** substituem.

## 11. O que o painel não edita

- **Nome, frase, CNPJ, razão social, “desde 2010”** e os valores originais da empresa: ficam em `compartilhado/dados/empresa.js`, no projeto (gere o pacote de novo depois de mudar). O que for mudado na área **Empresa** vale por cima desse arquivo.
- **Textos das páginas** (títulos, explicações, história da empresa, e o resumo que o Google e as prévias de link mostram, com o telefone e o endereço): no HTML das páginas, no projeto (`linha-verde/`).
- **Lista para quem navega sem JavaScript:** o `produtos.html` tem uma lista curta e fixa de destaques dentro de `<noscript>` (quase ninguém vê; o Google lê a lista completa, que é gerada sozinha). Se trocar muito os destaques, atualize essa lista no projeto quando gerar o próximo pacote.

## 12. Atualizações do catálogo preparadas pelo suporte

Às vezes quem cuida do site prepara mudanças no catálogo para você: **produtos novos**, modelos que **saíram de linha trocados pelos atuais**, **fotos novas** e produtos que **saem do site**. Elas chegam junto com a atualização do site, mas **não mudam nada sozinhas**: você confere e decide. Só administradores veem.

- **Onde aparece:** no topo da tela **Produtos**, o cartão **“Atualização do catálogo pronta”**, com o resumo (ex.: *5 produtos novos, 7 trocados pelo modelo atual, 1 foto nova, 2 para tirar do site*) e o botão **Ver e aplicar**.
- **Conferir:** cada mudança aparece com a foto (toque nela para ver em tamanho grande), a marca e o nome, o resumo, as 3 primeiras linhas da ficha técnica e o **porquê**. Nas trocas aparece **No lugar de:** com o produto antigo. O novo herda do antigo o **destaque**, o **fora do site** e a anotação interna; o estoque volta para *Consulte* e o preço fica vazio (se o antigo tem preço ou está como *Pronta entrega*, a tela avisa).
- **Escolher:** cada mudança tem uma caixinha, já marcada. Desmarque o que não quiser (ex.: um modelo que a loja não vai vender). Algumas já vêm **desmarcadas** para você decidir: o produto da capa, uma foto que você já trocou no painel, a troca de um produto que você já tinha tirado do catálogo ou de um modelo que está como **Pronta entrega** (se ainda tem unidades dele, deixe para depois). **Marcar todas** marca tudo de uma vez.
- **Aplicar:** **Aplicar os marcados** → a janela resume o que vai mudar → **Aplicar**. Tudo entra no site de uma vez e o cartão some. O que ficou desmarcado pode ser aplicado depois, na mesma tela (**Conta → Atualizações do catálogo**).
- **Agora não:** esconde o cartão sem mudar nada no site. Para ver de novo: **Conta → Atualizações do catálogo**.
- **Desfazer:** antes de aplicar, o painel guarda **uma** cópia de segurança. O quadro **Aplicado em …**, no alto da tela da atualização, diz a data da cópia certa: **Conta → Cópias de segurança → Restaurar** a cópia daquela data (*“Antes de: aplicar a atualização ‘…’”*) volta o catálogo e as fotos como estavam (essa cópia fica guardada 30 dias). Se você aplicou em mais de uma vez, é a de antes da **primeira**, e ela desfaz também o que mais tiver mudado no catálogo depois (a janela de confirmação mostra o quê).
- **Depois:** toque em **Ver no site** e confira no celular. Os produtos novos são produtos como os outros: estoque, preço, destaque, foto e textos se mudam pelo painel.
- **“Pacote com problema”:** a atualização chegou com defeito (por exemplo, falta uma foto) e nada pode ser aplicado. O catálogo continua como estava: avise quem cuida do site.

> **Catálogo de outubro de 2026:** 5 balanças novas (Toledo **Prix 4 Trend**, **Prix 6** e **Prix 7T**, Balmak **EDGE** e Urano **BA37**), 7 produtos de automação que saíram de linha trocados pelos atuais (Argox **OS-214 Pro**, Zebra **ZT231**, Datalogic **QuickScan QW2500**, Elgin **EL8600iA**, Gertec **TC506E 2D**, **Busca Preço 2D** e **MT-721 VT100**), a foto nova da **Prix 5 Plus** (em torre) e as duas **Balmak Órion** fora do site, como a loja pediu. O que mudou e por quê: `docs/ATUALIZAR-BALANCAS.md`; de onde saiu cada dado: `docs/FONTES-DOS-PRODUTOS.md`.
## 13. Vários produtos de uma vez (Selecionar)

Na tela **Produtos**, toque em **Selecionar**. Aparece uma caixinha em cada produto (no celular, o cartão inteiro marca) e a barra de baixo vira a barra dos marcados.

- **Marcar todos os N desta lista** marca de uma vez os que aparecem com a busca e o filtro de agora (ex.: filtre **Balanças** e busque `toledo`). No computador, **Shift+clique** marca um trecho.
- Na barra: **Estoque** (*Pronta entrega*, *Consulte*, *Sob encomenda*, *Esgotado*), **Destaque** (pôr ou tirar), **No site** (mostrar ou esconder), **Mover** (outra categoria e tipo; quem muda de categoria entra no fim dela) e **Excluir** (a janela mostra os nomes; logo depois, **Desfazer** traz todos de volta de uma vez, cada um no seu lugar e com a foto — vale uma vez, para quem excluiu). **Estoque**, **Destaque** e **No site** também têm **Desfazer** no aviso: cada produto volta a ficar como estava antes (vale uma vez, para quem fez).
- Os mesmos avisos de um produto só: esconder ou excluir o produto da capa, e quem ficou de fora dos 8 destaques da página inicial.
- Cada ação guarda **uma** cópia de segurança (ex.: *“Antes de: mudar o estoque de 8 produtos para Esgotado”*) e uma linha em Atividades. Quem já estava assim fica de fora. No máximo 200 por vez. Editor também pode. Funciona sem JavaScript.
- Para sair: **Sair da seleção** (ou o **×** da barra).

## 14. Trocar o produto da capa (Empresa → Capa do site)

O produto grande do topo da página inicial, com as 4 etiquetas (Capacidade, Teclado…), é a Toledo **Prix 7E**. Um administrador pode pôr outro:

1. **Empresa** → parte **10 · Capa do site** (no celular, pela fita de atalhos do alto).
2. Escolha o **produto**. Só aparecem os que estão **no site** e têm **foto com fundo transparente** (o recorte: a foto fica sobre a faixa verde, sem caixa branca em volta); a tela explica por que os outros não estão na lista.
3. As **4 etiquetas** já vêm preenchidas com as primeiras linhas da ficha técnica: ajuste o **rótulo** (até 14 letras), o **valor** (até 24) e o **detalhe** (até 32). A **legenda** (ex.: *impressora de etiquetas industrial*) aparece depois da marca e do nome.
4. **Ponto de cada etiqueta:** toque no número da etiqueta (1 a 4) e depois **toque na foto**, no lugar que ela descreve (no computador também dá para arrastar ou usar as setas do teclado). Sem JavaScript, use os campos de posição (x e y em %).
5. Confira as **prévias** do computador e do celular (no celular aparecem só as etiquetas **1 e 3**) → **Salvar**. A página inicial já mostra o produto novo, e o selo **Na capa do site** passa para ele na lista.

- **Voltar para a Prix 7E (capa original)**: o botão da mesma parte, com confirmação.
- Se o produto da capa for **escondido, excluído ou ficar sem recorte**, o site volta sozinho para a Prix 7E e o painel avisa no topo de **Produtos** e na **Capa do site**, até você mostrar o produto de novo ou escolher outro.
- Cada troca guarda uma cópia (**Conta → Cópias de segurança → aba Empresa**) e uma linha em Atividades (*“Trocou a capa do site para Zebra ZT231”*).

## 15. Categorias e subcategorias

As **categorias** são as abas do catálogo do site (Balanças, Automação Comercial, Informática e PDV), na ordem da lista; as **subcategorias** são os filtros de tipo de cada uma. Só administradores: **Produtos → Categorias** (botão ao lado de **Selecionar**) ou **Conta → Categorias**.

- **Editar** muda o nome e a descrição. O endereço da categoria no site (`produtos.html?cat=balancas`) **nunca muda**, então links antigos continuam abrindo.
- **Renomear** uma subcategoria muda junto todos os produtos dela, numa alteração só.
- **↑ ↓** mudam a ordem das abas (e o “Todos” e a página inicial seguem a ordem nova) e a dos tipos.
- **Nova categoria**: nome, descrição e as subcategorias (uma por linha). Ela entra no fim e só aparece no site quando tiver produtos: cadastre produtos nela ou mova com **Selecionar → Mover** (item 13). A página inicial ganha o atalho dela sozinha.
- **Apagar**: categoria só quando está vazia (e nunca a última). Subcategoria vazia sai na hora; com produtos, escolha para onde eles vão (**Mover e apagar**, tudo numa alteração). Ex.: a vazia *Emissores fiscais (SAT)* sai em **Informática e PDV → 🗑 → Apagar**.
- Cada mudança guarda uma cópia (**Cópias de segurança → Restaurar** desfaz) e uma linha em Atividades. Uma atualização do catálogo (item 12) que fale de uma categoria ou subcategoria que você apagou ou renomeou mostra aquela mudança como *“não dá para aplicar”*, com o motivo; o resto aplica normalmente.

## 16. Mais procurados

**Produtos → Mais procurados** (o cartão no topo da lista) ou **Conta → Mais procurados**. Todos os usuários veem; só mostra números, não muda nada.

- **Período:** 7 dias, 30 dias, este mês, mês passado ou 12 meses, sempre comparado com o período anterior igual (*“+12%”*).
- **Totais:** visitas às páginas, produtos abertos no catálogo, cliques no WhatsApp e em **Ligar**; o gráfico mostra os cliques no WhatsApp por dia (ou por mês).
- **Produtos mais procurados:** foto, marca, categoria, quantas vezes a ficha foi aberta e quantos pediram orçamento pelo WhatsApp daquele produto (toque no lápis para editar). Quem já saiu do catálogo aparece apagado, pelo nome.
- **De onde vêm os cliques no WhatsApp:** o lugar do botão (*Botão do topo*, *Ficha do produto*, *Botão flutuante*…) e em quais páginas; e o ranking das **páginas**.
- **Não contar os acessos deste aparelho:** liga sozinho quando alguém entra no painel naquele aparelho, para as suas visitas não parecerem interesse de clientes. Desligue no aparelho que os clientes usam (ex.: o computador do balcão).
- **Privacidade:** a contagem é anônima, **sem cookies** e sem guardar dados de quem visita (nem o IP); robôs do Google e afins não entram. Os números começam a contar no dia em que a atualização entrou e ficam guardados 24 meses.

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

1. **Foto:** cPanel → Gerenciador de Arquivos → `public_html/compartilhado/img/produtos` → **Carregar**. Nome sem espaços e sem acentos (`urano-pop-z-mira.webp` ✅, `Balança POP-Z Mira (1).JPG` ❌), de preferência com 900 px de largura e fundo branco (o https://squoosh.app converte e reduz de graça).
2. **Cópia de segurança:** em `public_html/compartilhado/dados`, botão direito em `produtos.js` → **Copiar** → `produtos-backup.js`.
3. Botão direito em `produtos.js` → **Editar**. Copie um bloco inteiro de produto, do `{` até o `},`, cole logo depois dele e troque as informações:

```json
    {
        "id": "urano-pop-z-mira",
        "nome": "POP-Z Mira",
        "marca": "Urano",
        "categoria": "balancas",
        "subcategoria": "Computadoras",
        "resumo": "Balança computadora com bateria, para o balcão.",
        "descricao": "Pesa e calcula o preço no balcão. Funciona na tomada ou na bateria.",
        "especificacoes": [
            ["Capacidade", "31 kg"],
            ["Bateria", "Até 200 h"]
        ],
        "imagem": "urano-pop-z-mira.webp",
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
| `"id"` | Código único, minúsculo, só letras, números e hífen. Vira o link `produtos.html#p=urano-pop-z-mira`. |
| `"categoria"` | O código de uma das categorias de `window.CATEGORIAS`, no topo do arquivo (`"balancas"`, `"automacao"`, `"informatica"` ou outra criada no painel). Categorias novas se criam pelo painel (item 15). |
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
