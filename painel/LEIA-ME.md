# Painel do site · Balanças.com

Painel para **cadastrar, editar, esconder e excluir produtos e trocar as fotos** do site sozinho (um ou vários de uma vez), pelo celular ou pelo computador, para cuidar dos **logos dos clientes** (área **Clientes**), dos **dados da empresa** que o site mostra (área **Empresa**: telefone, horário, feriados, aviso no site, **produto da capa**…) e das **categorias** do catálogo, e para ver os **produtos mais procurados** no site (contagem anônima, sem cookies). Cada pessoa tem o seu usuário, como **Administrador** ou **Editor**. Feito em PHP puro (sem banco de dados, sem WordPress, sem plugins) e pensado para a HostGator/cPanel.

- Endereço: **`https://balancass.com/painel/`**. No site, a entrada é o link discreto **“Área restrita”** no rodapé de todas as páginas (com `nofollow`; o painel não aparece no Google).
- O painel grava direto no arquivo do catálogo (`compartilhado/dados/produtos.js`) e nas fotos (`compartilhado/img/produtos/`); os logos de clientes, em `compartilhado/dados/clientes.js` e `compartilhado/img/clientes/`; os dados da empresa, em `compartilhado/dados/ajustes.js`. O que você salva aparece no site na hora.
- Antes de cada alteração ele guarda uma **cópia de segurança**: as 30 mais recentes sempre, tudo dos últimos 14 dias (até 200) e as de **excluir** e **restaurar** por 30 dias. Excluiu por engano? Toque em **Desfazer** no aviso (ou **Cópias** → **Trazer de volta**): só aquele produto volta, no mesmo lugar e com a foto.

---

## 1. Como fica no servidor

```
/home/SUA-CONTA/
├── public_html/                     ← o site
│   ├── index.html, produtos.html…
│   ├── compartilhado/
│   │   ├── dados/produtos.js        ← catálogo (o painel lê e grava este arquivo)
│   │   ├── dados/clientes.js        ← lista de clientes (logos; o painel lê e grava, área Clientes)
│   │   ├── dados/empresa.js         ← dados ORIGINAIS da empresa (vêm com as atualizações; o painel só lê)
│   │   ├── dados/ajustes.js         ← o que a área Empresa mudou (o painel grava; não vem nas atualizações)
│   │   └── img/produtos/            ← fotos (o painel salva aqui; tem .htaccess que bloqueia scripts)
│   │   └── img/produtos-recorte/    ← fotos com fundo transparente (idem)
│   │   └── img/clientes/            ← logos de clientes (idem)
│   └── painel/                      ← ESTE painel
│       ├── index.php                ← a única página
│       ├── contar.php               ← recebe a contagem anônima do site (Mais procurados); público, sem sessão nem cookie
│       ├── config.php               ← caminhos e limites (único lugar com configuração)
│       ├── lib/                     ← código (bloqueado para o navegador)
│       ├── assets/                  ← visual (CSS e JS)
│       ├── pacotes/                 ← atualizações do catálogo preparadas pelo suporte (item 4, “Atualizações do catálogo preparadas pelo suporte”)
│       ├── .htaccess  .user.ini     ← proteção e limites do PHP
│       └── LEIA-ME.md               ← este arquivo (bloqueado para o navegador, como todo .md e .htaccess)
└── painel-balancas-privado/         ← DADOS PRIVADOS (criada sozinha, FORA do public_html)
    ├── usuarios.php                 ← usuários, papéis e senhas (só o "hash"; a senha não fica escrita)
    ├── config.php                   ← só a marca da conversão para usuários (painéis antigos guardavam a senha aqui)
    ├── primeiro-acesso.php          ← código do primeiro acesso / recuperar acesso (some depois de usado)
    ├── recuperar.txt                ← SÓ quando você cria, para recuperar o acesso (item 5; o painel apaga)
    ├── copias/                      ← cópias de segurança do catálogo (copia-*.php), da lista de clientes (clientes-*.php) e dos dados da empresa (ajustes-*.php)
    ├── notas.php                    ← anotações internas dos produtos (não vão para o site)
    ├── lixeira/                     ← fotos e logos trocados/excluídos (voltam se restaurar uma cópia)
    ├── sessoes/                     ← logins abertos
    ├── registro.php                 ← quem entrou e o que mudou (Conta → Atividades)
    ├── estatisticas/                ← Mais procurados: AAAA-MM.php (contadores por dia, 24 meses), visitantes-AAAA-MM-DD.php
    │                                   (só códigos HMAC curtos do dia, para o limite; apagado na virada do dia), segredo.php (chave
    │                                   do dia, trocada todo dia), ids.php e nomes.php (códigos e nomes dos produtos)
    └── tentativas.php, chave.php…   ← controle de senha errada
```

Todo arquivo da pasta privada é `.php` e começa com `<?php exit; ?>`: mesmo que alguém descubra o endereço, o servidor não mostra nada. A pasta também tem um `.htaccess` que bloqueia tudo.

Se a hospedagem não deixar criar a pasta fora do `public_html`, o painel usa `public_html/painel/privado/` (com as mesmas proteções). A tela de primeiro acesso e a página **Minha conta** mostram onde ela está.

---

## 2. Instalação no cPanel (uma vez)

1. **PHP 8.1 ou mais novo.** cPanel → **MultiPHP Manager** → marque o domínio → PHP **8.1, 8.2 ou 8.3** → Aplicar. (As extensões **GD** e **fileinfo** já vêm ligadas na HostGator.)
2. **Envie os arquivos.** O pacote do site já vem com a pasta `painel/` e com o `compartilhado/dados/produtos.js` **no formato do painel** (veja o item 9 se o painel disser que não consegue ler o catálogo).
3. **Arquivos `.htaccess`.** Confira que existem (o Gerenciador de Arquivos esconde arquivos que começam com ponto: clique em **Configurações** → marque **Mostrar arquivos ocultos**):
   - `public_html/.htaccess` (o do site)
   - `public_html/painel/.htaccess` e `public_html/painel/lib/.htaccess`
   - `public_html/compartilhado/img/produtos/.htaccess`, `.../produtos-recorte/.htaccess` e `.../clientes/.htaccess` (bloqueiam scripts nas pastas de fotos e de logos; o painel cria se faltarem; cópia em `hospedagem/compartilhado/img/`)
4. **Permissões** (normalmente já ficam assim): pastas **755**, arquivos **644**. O `produtos.js`, o `clientes.js`, o `ajustes.js`, a pasta `compartilhado/dados` e as pastas de fotos e de logos precisam poder ser gravados pela sua conta (é o padrão na HostGator).
5. **HTTPS.** Deixe o certificado (AutoSSL) ativo e o redirecionamento para `https://` ligado no `.htaccess` do site. O painel só deve ser usado com o cadeado.

---

## 3. Primeiro acesso (criar o primeiro administrador)

1. Abra **`https://balancass.com/painel/`**. Aparece a tela **Primeiro acesso**.
2. Nesse momento o painel grava um **código de primeiro acesso** num arquivo que só quem entra no cPanel consegue ler.
3. No cPanel → **Gerenciador de Arquivos** → vá até a pasta **inicial da conta** (a mesma que tem a `public_html`) → abra **`painel-balancas-privado`** → clique com o botão direito em **`primeiro-acesso.php`** → **Ver** (ou Editar). Copie o código (ex.: `7KQ4-M9TX-2HPA`).
4. Volte ao painel, cole o código, escreva **seu nome** (ex.: `Fábio Godoy`), escolha o **usuário** (ex.: `fabio`) e uma **senha com pelo menos 10 caracteres**. Dica: junte 3 ou 4 palavras que só você saiba, com um número (não copie exemplos de guias: o painel recusa a senha de exemplo de versões antigas deste guia).
5. Pronto: você já entra, como **Administrador**. O arquivo do código é apagado sozinho. Para outras pessoas usarem o painel, crie um usuário para cada uma (item 4, **Usuários**).

**Painel que já estava em uso** (versão antiga, de um usuário só): não precisa fazer nada. Na primeira visita depois da atualização, o usuário de antes vira **Administrador**, com a mesma senha, e quem estava com o painel aberto continua logado. Para pôr o seu nome: **Conta → Mudar o meu nome**.

O código vale 24 horas. Se vencer, abra o painel de novo e leia o código novo no mesmo arquivo.

---

## 4. Usando no dia a dia

**Produtos** (tela inicial): todos os produtos, separados por categoria, com foto.
- **Buscar**: digite nome, marca ou código (não precisa de acento). Filtre por categoria ou por **Mostrar** (só os do site, só os ocultos, só destaques).
- **Estoque**: troque no seletor e pronto (salva sozinho). No seletor aparece o nome curto (*Consulte*); no site, o selo completo: *Consulte disponibilidade*, *Pronta entrega*, *Sob encomenda* ou *Esgotado*.
- **★ Destaque**: o produto sobe no catálogo e entra na **página inicial**, que mostra **até 8 destaques**, revezando as categorias (o 1º destaque de Balanças, o 1º de Automação, o 1º de Informática, o 2º de Balanças…). O selo mostra *Destaque · página inicial* ou *Destaque · fora da página inicial* (já há 8 na frente: tire a estrela de outro ou ponha este mais para cima na categoria com **Mudar lugar**).
- **Na capa do site**: selo do produto que o topo da página inicial mostra de fato: a Toledo Prix 7E do `index.html` (foto e link fixos) ou o produto escolhido em **Empresa → Capa do site**. Esconder, excluir ou mudar o código desse produto pede confirmação: com a Prix 7E, o link da capa passaria a abrir o aviso “produto fora do catálogo”; com a capa escolhida, a página inicial volta sozinha para a Prix 7E e o topo da lista de produtos avisa (*“O produto escolhido para a capa do site, …, está escondido do site…”*) até alguém mostrar o produto de novo ou escolher outro. Nesse meio-tempo o selo **Na capa do site** (e a confirmação) passa para a Prix 7E, e o escolhido fica com o selo **Escolhido para a capa (fora do site)**. Se a Prix 7E também saiu do site (excluída ou escondida), o aviso diz isso: a página inicial mostra a foto dela com o link para um produto fora do catálogo, e o certo é escolher outro produto em **Empresa → Capa do site**.
- **👁 No site**: esconde o produto do site **sem apagar** (aparece o selo *Fora do site* no painel).
- **Mudar lugar**: abre a lista de lugares da categoria (*“1º no começo, antes de …”*, *“12º depois de Balmak ONE · começo de Pesadoras”*, *“10º fica onde está”*); um toque no lugar e pronto (2 toques para levar a qualquer lugar, sem recarregar a página). A lista tem a categoria inteira, mesmo com a busca ou o filtro ligados (os escondidos continuam escondidos). O aviso diz o número novo, o que o site faz com ele (Pronta entrega e destaques passam na frente, inclusive ele mesmo; Esgotado fica por último; produto fora do site: o lugar vale quando ele voltar) e quem entrou ou saiu da página inicial; tem **Desfazer** por 30 segundos (Editor também pode; no teclado, **Ctrl+Z** — **⌘Z** no Mac — também desfaz enquanto o aviso está na tela, fora dos campos de texto e sem outra janela aberta). Se nesse meio-tempo o produto mudou de categoria (em outro aparelho), o Desfazer avisa e não mexe em nada. Em tela baixa (celular deitado, zoom grande) a janela inteira rola e o **Cancelar** fica sempre à vista, embaixo. No site vale: **Pronta entrega** primeiro, depois os **destaques**, depois **esta ordem**; **Esgotado** fica por último. Sem JavaScript (ou num navegador antigo), o mesmo botão abre uma página com a mesma lista.
- **Arrastar** (só no computador, com mouse): segure a alça **⠿** à esquerda do produto e solte no lugar certo (uma linha verde mostra onde vai cair; **Esc** cancela). Só dentro da mesma categoria (fora dela o cursor mostra que não dá e, ao soltar, um aviso explica: para outra categoria, use **Editar** ou **Selecionar → Mover**); com busca ou filtro ligados, ou no **Selecionar**, a alça some (use **Mudar lugar**). Grava igual ao Mudar lugar, com **Desfazer**.
- **Editar**: abre o formulário completo. **🗑 Excluir**: pede confirmação; o aviso “foi excluído” tem o botão **Desfazer**.
- No celular, os botões ★ e 👁 têm o nome embaixo (*Destaque*, *No site*), e **Sair** fica no alto, com o nome.
- **Selecionar** (vários de uma vez): toque em **Selecionar**, marque os produtos (o cartão inteiro marca; **Marcar todos os N desta lista** respeita a busca e o filtro; Shift+clique marca um trecho) e escolha na barra de baixo: **Estoque**, **Destaque** (pôr/tirar), **No site** (mostrar/esconder), **Mover** (categoria e tipo; quem muda de categoria entra logo depois dos outros do mesmo tipo) ou **Excluir** (confirma com os nomes; **Desfazer** traz todos de volta de uma vez, uma vez, para quem excluiu). **Estoque**, **Destaque** e **No site** também têm **Desfazer** no aviso (uma vez, para quem fez): cada produto volta ao que era antes, e quem foi mudado de novo depois fica como está. Cada ação grava uma cópia de segurança só dela (Cópias → *“Antes de: mudar o estoque de 8 produtos para Esgotado”*) e uma linha em Atividades; quem já estava assim fica de fora. No máximo 200 por vez. Funciona sem JavaScript. Editor também pode.

**Mais procurados** (todos os usuários; cartão no topo de **Produtos** ou **Conta → Mais procurados**): o que os visitantes mais abrem no site e de onde vêm os contatos. Períodos **7 dias / 30 dias / este mês / mês passado / 12 meses**, comparados com o período anterior igual (*“+12%”*); totais de visitas, produtos abertos, cliques no WhatsApp e em **Ligar**; gráfico dos cliques no WhatsApp; ranking dos produtos (foto, marca, categoria, abertos + WhatsApp, link para editar; quem saiu do catálogo aparece apagado, pelo nome); de onde vêm os cliques (lugar do botão + páginas) e as páginas. **Não contar os acessos deste aparelho**: liga sozinho quando alguém entra no painel naquele aparelho (dá para desligar; a escolha fica só no navegador, em `localStorage["bc-nao-contar"]`). Os números começam no dia em que a atualização entra no ar.

**Categorias** (só administradores; Produtos → botão **Categorias**, ou Conta → **Categorias**): as abas do catálogo do site (categorias, na ordem da lista) e os filtros de tipo de cada uma (subcategorias). **Editar** muda o nome e a descrição — o endereço da categoria no site (`produtos.html?cat=balancas`) nunca muda. **Renomear** uma subcategoria muda junto todos os produtos dela (uma cópia de segurança, uma linha em Atividades). **↑ ↓** mudam a ordem das abas (os produtos acompanham: no catálogo “Todos” e na página inicial a categoria de cima vem antes) e dos tipos. **Nova categoria**: nome, descrição e as subcategorias (uma por linha); entra no fim e só aparece no site (abas e atalhos da página inicial) quando tiver produtos — cadastre ou mova produtos para ela (**Selecionar → Mover**). **Apagar**: categoria só vazia (e nunca a última); subcategoria vazia, ou escolhendo para onde vão os produtos dela (**Mover e apagar**, tudo numa alteração). Tudo volta em Cópias de segurança → Restaurar. Ex.: a subcategoria vazia “Emissores fiscais (SAT)” sai em Informática e PDV → 🗑 → Apagar. Os nomes curtos das abas (*Balanças*, *Automação*, *Informática*) valem enquanto a categoria tem o nome original; renomeada (ou nova), vale o nome dela.

**Novo** (botão verde): preencha nome, marca, categoria, subcategoria, resumo (aparece no cartão), descrição (aparece nos detalhes), ficha técnica (as 3 primeiras linhas aparecem no cartão), estoque, condição, preço (vazio = “Consulte”) e a **foto**.
- **Lugar na lista**: **Junto da mesma subcategoria** (o padrão: um fatiador novo entra logo depois dos outros fatiadores) ou **Em 1º na categoria**. Produto editado que **troca de categoria** entra logo depois dos outros da mesma subcategoria na categoria nova (só trocar a subcategoria não muda o lugar). Depois, **Mudar lugar** acerta o resto.
- O **código** do produto (o final do link `produtos.html#p=...`) é criado sozinho a partir da marca e do nome. Ao editar, evite mudar: links antigos do produto (WhatsApp, Google) deixariam de abrir direto nele.
- **Anotação interna** (em *Avançado*): fica só no painel, na pasta privada (`notas.php`). Não aparece no site nem no arquivo do catálogo.
- **Rascunho**: o que você digita fica guardado no próprio aparelho até salvar. Se fechar a página sem querer, ao abrir o mesmo produto (ou **Novo**) aparece **Recuperar**. A foto não entra no rascunho.
- **Sessão vencida** (mais de 2 h parado com o formulário aberto): ao tocar em **Salvar**, o painel pede a senha numa janela e salva em seguida, sem perder nada (nem a foto).

**Fotos**
- Pode ser foto do celular (o painel gira, recorta as bordas brancas, reduz e otimiza sozinho) ou a foto do fabricante.
- Para ficar como as outras do site: **celular deitado**, produto ocupando quase toda a foto, **fundo branco ou liso**. A prévia do formulário já mostra a foto no formato do cartão do site e avisa quando ela vai ficar pequena (foto em pé ou fundo que não é branco).
- JPG, PNG ou WebP, até 8 MB, com pelo menos 300 px de largura e de altura e até 20 megapixels. Foto em **HEIC** (alguns celulares): na câmera, desligue “alta eficiência” (HEIF) ou compartilhe a foto como JPG.
- PNG com **fundo transparente** também vira o “recorte” usado nas áreas escuras do site.
- Ao trocar a foto, a antiga vai para a lixeira privada (volta se você restaurar uma cópia).

**Clientes**: os logos da faixa “Quem confia na gente” (página inicial) e “Alguns clientes” (página Empresa), na ordem do site, cada um numa chapa branca igual à do site.
- **Novo cliente**: nome + logo. Aceita **PNG, JPG ou WebP** (o melhor é PNG com fundo transparente), até **5 MB**, com pelo menos **80 px** de largura. **SVG não** (pode carregar código; peça o PNG). A prévia já mostra o logo na chapa, com as sobras cortadas, e avisa se o fundo é colorido, se o logo é quase todo branco ou se é pequeno demais.
- O servidor confere o arquivo (tipo real + dimensões), **redesenha** com GD **mantendo a transparência**, corta as margens vazias (transparentes ou brancas), reduz para no máximo **600 × 300 px** e salva em **WebP** com nome aleatório (`<nome-do-cliente>-<10 hex>.webp`).
- **Lugar no site** (no cadastro): o padrão é **1º, no começo**; dá para escolher *“6º, depois de Mercado Qualidade”* etc. Se o vizinho escolhido sair da lista enquanto o formulário está aberto, o cliente entra em 1º e o aviso explica.
- **Mudar lugar** muda a posição no site (a mesma lista de lugares, 2 toques, com **Desfazer**; no computador dá também para **arrastar** pela alça **⠿**); **Editar** troca o nome e/ou o logo (o antigo vai para a lixeira); **🗑** pede confirmação e tem **Desfazer**.
- O nome (até 80 caracteres, sem `<` e `>`) não aparece escrito no site: é o texto alternativo do logo.
- Se o servidor ainda não tem `compartilhado/dados/clientes.js`, a tela mostra o aviso **“A lista de clientes ainda não foi instalada no servidor”**: instale o pacote de atualização (`docs/PUBLICAR-NO-CPANEL.md`, item 6) antes de cadastrar. Se cadastrar antes, o painel cria o arquivo só com o que for cadastrado.
- Logos que vieram com o site (`brasil-frios.webp`, `camargo.webp`…) nunca saem da pasta, mesmo excluídos da lista: algumas páginas os trazem direto no HTML (para quem navega sem JavaScript). Só os logos enviados pelo painel vão para a lixeira.

**Empresa** (só administradores): o que o site mostra sobre a empresa, em 10 partes, cada uma com o seu **Salvar**. Cada parte diz onde aparece no site e tem o link **Ver no site**; no celular, a fita de atalhos no alto leva direto a cada parte.
1. **Contato e WhatsApp**: telefone, WhatsApp (o 55 o painel põe sozinho), mensagem pronta do WhatsApp e e-mail.
2. **Endereço e mapa**: rua e número, referência, bairro, cidade, estado, CEP e o link do Google Maps (Compartilhar → Copiar link).
3. **Horário de funcionamento**: até 2 horários por dia, ou **Fechado**. O botão **Repetir o horário de segunda de terça a sexta** poupa digitação. O painel escreve sozinho o texto do site (“Segunda a sexta — 8h às 11h · 13h às 18h”, “Domingo e feriados — Fechado”) e o selo **Aberto agora / Fechado agora** usa o mesmo horário.
4. **Feriados e dias fechados**: os feriados nacionais e o de 9 de julho o site já conta sozinho. Aqui entram os **feriados da cidade** (dia/mês, todo ano), **Carnaval e Corpus Christi** (datas que mudam todo ano: é só marcar) e **dias fechados** avulsos (ponte, recesso, inventário; os que já passaram há mais de 60 dias saem da lista). As **Sugestões para Rio Claro** (aniversário da cidade em 24/06 e Corpus Christi) só valem depois de salvas: confira no calendário da Prefeitura.
5. **Aviso no site**: uma faixa no topo de todas as páginas (verde para novidade, amarela para atenção), com data de começo e de fim opcionais. A prévia mostra como fica. Para tirar, desligue e salve.
6. **Avaliações do Google e redes sociais**: nota (ex.: 4,9), número de avaliações, link das avaliações, Facebook e Instagram (pode ser só `@perfil`). Rede em branco some do site.
7. **Equipe** e 8. **Depoimentos**: uma linha por pessoa ou depoimento, com ↑ ↓ para a ordem do site, **Adicionar** e remover. A lista não pode ficar vazia.
9. **Marcas**: uma por linha, na ordem da faixa de marcas.
10. **Capa do site**: o produto grande do topo da página inicial (hoje a Toledo Prix 7E). Escolha o **produto** (só os que estão no site e têm foto com fundo transparente, o recorte: a tela diz por que os outros não aparecem); as **4 etiquetas** vêm preenchidas com as primeiras linhas da ficha técnica (rótulo até 14 letras, valor até 24, detalhe até 32) e a **legenda** vai depois da marca e do nome. Para o **ponto** de cada etiqueta: escolha a etiqueta e toque na foto (no computador também arrastando ou com as setas; sem JavaScript, os campos x/y em %). As prévias mostram o computador e o celular (no celular só aparecem as etiquetas 1 e 3); dá para tocar em qualquer uma das duas para marcar o ponto (no celular, a prévia do celular é a maior). As etiquetas sugeridas cortam o texto da ficha num separador natural (vírgula, “ com ”, “ e ”…), nunca dividindo uma frase entre o destaque e o detalhe; confira antes de salvar. **Voltar para a Prix 7E (capa original)** apaga a escolha. Se o produto escolhido sair do site (escondido, excluído, sem recorte), o site volta sozinho para a Prix 7E e o painel avisa aqui e no topo de Produtos. O registro diz *“Trocou a capa do site para …”*.

Tudo vai para `compartilhado/dados/ajustes.js`, que guarda **só o que foi mudado** (o resto continua vindo de `compartilhado/dados/empresa.js`, os valores originais do site). O `ajustes.js` **não vem nas atualizações do site**: o que você salvou continua depois de atualizar. Salvar uma parte sem mudar nada não grava nada. Antes de cada mudança fica uma cópia (aba **Empresa** em Cópias de segurança), e o registro diz em palavras simples o que mudou (ex.: “Mudou o telefone para (19) 3333-4444”). Se o `ajustes.js` for editado à mão e quebrar, o painel não sobrescreve: mostra a tela “Não consegui ler os ajustes do site”, com **Restaurar** uma cópia ou **Recomeçar com os dados originais** (o arquivo quebrado fica guardado em `copias/ilegivel-ajustes-*.php`).

**Cópias** (em **Conta → Cópias de segurança**, só administradores): lista das cópias de segurança (data, hora, o que ia ser feito), com as abas **Produtos**, **Clientes** e **Empresa** (cada uma tem as suas cópias e restaurar uma não mexe nas outras). Mudanças seguidas do mesmo tipo (ordem, estoque, destaque, no site) feitas em menos de 10 minutos ficam numa cópia só (“e mais 31 alterações do mesmo tipo”); cada ação em lote (**Selecionar**) e cada mudança em **Categorias** tem a sua cópia (a de uma exclusão em lote fica 30 dias).
- **Trazer de volta** (nas cópias de exclusão): devolve só aquele produto, no mesmo lugar e com a foto. Na de uma exclusão em lote: **Trazer de volta os N** (todos os que ainda estão fora do catálogo) ou, em *Ver os N produtos…*, um por um. Se a subcategoria do produto foi renomeada ou apagada depois da exclusão, ele volta na subcategoria com o nome novo (ou na primeira da categoria) e o aviso diz qual, com o link para **Editar** (vale também para o **Desfazer**).
- **Restaurar**: volta o catálogo **inteiro** (ou a lista de clientes, ou todos os dados da empresa), com as fotos, para aquele momento, e desfaz também tudo o que foi feito depois. A janela de confirmação diz quantas alterações serão desfeitas e quais. O estado atual também vira uma cópia, então dá para desfazer a restauração.

**Atualizações do catálogo preparadas pelo suporte** (só administradores): quando quem cuida do site prepara mudanças no catálogo (produtos novos, modelos que saíram de linha trocados pelos atuais, fotos novas, produtos que saem do site), elas chegam **dentro da atualização do site** (`atualizacao.zip`, pasta `painel/pacotes/`) e o catálogo do site **não muda sozinho**: no topo da lista de produtos aparece o cartão **“Atualização do catálogo pronta”**, com o resumo (ex.: *5 produtos novos, 7 trocados pelo modelo atual, 1 foto nova, 2 para tirar do site*) e o botão **Ver e aplicar**. Editores não veem nada disso.
- A tela mostra cada mudança, separada em **Produtos novos**, **Trocados pelo modelo atual**, **Fotos novas** e **Saem do site**, com a foto (toque nela para ver em tamanho grande), a marca e o nome, o resumo, as 3 primeiras linhas da ficha técnica e o **porquê**. Nas trocas aparece **No lugar de:** com o produto antigo; o novo herda dele o **destaque**, o **fora do site** e a anotação interna (estoque volta para *Consulte* e o preço fica vazio; quando o antigo tem preço ou está como *Pronta entrega*, a tela avisa: *“Hoje: Pronta entrega · R$ 990,00. O modelo novo entra como “Consulte”, sem preço; o antigo sai do site.”*).
- Cada mudança tem uma caixinha, já marcada quando pode ser aplicada. Ficam **desmarcadas** (para você decidir): o produto da **capa** do site, uma foto que você **já trocou no painel**, a troca de um produto antigo que **não está mais no catálogo** (o novo entra no fim da categoria) e a troca de um modelo que está como **Pronta entrega** (se ainda tem unidades dele para vender, deixe para depois). **Marcar todas** marca de uma vez tudo o que pode ser aplicado. O que já está assim (ex.: *Já está no catálogo*) aparece sem caixinha ativa.
- **Aplicar os marcados**: uma janela resume o que vai mudar; tudo entra no site de uma vez, com **uma** cópia de segurança antes. O quadro **Aplicado em …** no alto da tela diz **qual cópia restaurar** para desfazer tudo de uma vez (fotos inclusive): **Conta → Cópias de segurança → Restaurar** a cópia daquela data, *“Antes de: aplicar a atualização ‘…’”* (guardada 30 dias). Se você aplicou em mais de uma vez, é a cópia de antes da **primeira** (a tela mostra a data certa); ela desfaz também o que mais tiver mudado no catálogo depois. Depois de restaurar, a tela passa a dizer *“A atualização foi desfeita”*. O que ficou desmarcado pode ser aplicado depois, na mesma tela; depois que você aplicou, o cartão da lista de produtos não volta só por causa do que ficou desmarcado.
- **Agora não**: esconde o cartão da lista sem mudar nada no site. Para ver de novo: **Conta → Atualizações do catálogo** → **Mostrar o aviso de novo**.
- Se a atualização chegar com defeito (arquivo incompleto, foto que falta), a tela mostra **“Pacote com problema: …”** e nada pode ser aplicado: avise quem cuida do site.
- Uma mudança cuja categoria ou subcategoria foi **apagada ou renomeada** em **Categorias** aparece como *“não dá para aplicar”*, com o motivo (só ela; o resto aplica normalmente). Na troca de modelo, se só a subcategoria sumiu, o novo entra na subcategoria do antigo.
- As fotos novas são copiadas para `compartilhado/img/produtos/` e `produtos-recorte/` (nunca por cima de outra foto); as fotos dos produtos trocados ou tirados vão para a lixeira privada, como ao excluir pelo painel. O registro (Atividades) diz *“Aplicou a atualização “…” (15 mudanças)”*.
- A primeira é a **“Catálogo de outubro de 2026”** (`pacotes/2026-10-catalogo/`, 15 mudanças): Toledo Prix 4 Trend, Prix 6 e Prix 7T, Balmak EDGE e Urano BA37 entram; Argox OS-2140, Zebra ZT230, Datalogic QW2100, Elgin EL4200, Gertec TC 504, Busca Preço G2-E e MT 720 dão lugar aos modelos atuais (OS-214 Pro, ZT231, QuickScan QW2500, EL8600iA, TC506E 2D, Busca Preço 2D e MT-721 VT100); a Prix 5 Plus ganha a foto da versão em torre; as Balmak Órion 1 Key e 2 Key saem do site (pedido da loja). Num site instalado do zero com o `site.zip` novo, o catálogo já vem com tudo isso e o cartão não aparece (na tela, cada mudança aparece como já feita: *“Já está no catálogo”*, *“Já está com esta foto”*, *“Já não está no catálogo”*).

**Conta**: quem está usando (nome, usuário e papel), **trocar a senha** (os outros aparelhos conectados saem), as **suas** últimas atividades e o passo a passo de **Esqueci a senha**. Em **Site**, os atalhos para **Mais procurados** (todos) e **Categorias** (administradores). Para administradores, também os atalhos para **Usuários**, **Atividades**, **Cópias de segurança** e, quando houver, **Atualizações do catálogo**.

**Usuários e papéis** (Conta → Usuários, só administradores). Cada pessoa tem o seu usuário e a sua senha, com um destes papéis:

| | Administrador | Editor |
|---|---|---|
| Produtos (cadastrar, editar, foto, estoque, destaque, no site, ordem com Mudar lugar, excluir + Desfazer) | ✓ | ✓ |
| Vários produtos de uma vez (Selecionar: estoque, destaque, no site, mover, excluir + Desfazer) | ✓ | ✓ |
| Mais procurados (só ver) | ✓ | ✓ |
| Clientes (logos) | ✓ | ✓ |
| Empresa (dados do site e Capa do site) | ✓ | — |
| Categorias e subcategorias | ✓ | — |
| Cópias de segurança (ver, trazer de volta, restaurar) | ✓ | — |
| Atualização do catálogo (ver, aplicar, “Agora não”) | ✓ | — |
| Usuários | ✓ | — |
| Atividades | de todos | só as próprias (em Conta) |
| Trocar a própria senha | ✓ | ✓ |

- **Novo usuário**: nome, usuário (o painel sugere a partir do nome; não muda depois) e papel. O painel cria uma **senha temporária** (ex.: `prato-verde-tecla-47`), que aparece **uma vez só**, com os botões **Copiar senha** e **Copiar mensagem** (texto pronto para o WhatsApp, com o endereço do painel). No primeiro acesso, a pessoa é levada para **Crie a sua senha** e só depois usa o painel.
- **Editar**: muda o nome e o papel. Mudança de papel vale **na hora**, sem a pessoa entrar de novo.
- **Redefinir senha** (quem esqueceu a senha): nova senha temporária; a antiga para de funcionar e, se a pessoa estiver com o painel aberto, ela sai na próxima ação.
- **Desativar**: a pessoa sai na hora e, ao tentar entrar, vê “Este acesso está desativado. Fale com um administrador do painel.” **Reativar** devolve o acesso com a mesma senha.
- **Excluir**: tira o usuário de vez (as atividades e o que ele fez no site continuam).
- Proteções: ninguém muda o próprio papel nem se desativa ou se exclui; o painel nunca fica sem nenhum administrador ativo; no máximo 20 usuários.
- O editor também tem o **Desfazer** logo depois de excluir um produto ou cliente; trazer de volta o que **outra pessoa** excluiu é com um administrador (Cópias de segurança).

**Atividades** (Conta → Atividades, só administradores): as últimas 300 linhas do registro, com o nome de quem fez, separadas por dia, com filtros por pessoa e por tipo (**Alterações**, **Acessos**, **Alertas**: senha errada, tentativa de abrir área de administrador, acesso desativado…). Linhas repetidas seguidas (ex.: várias mudanças de lugar iguais) aparecem numa linha só.

**Sair**: botão no alto. Por segurança, o painel também sai sozinho depois de **2 horas sem uso**.

---

## 5. Esqueci a senha

**Tem outro administrador no painel?** Peça a ele para redefinir a sua senha (**Conta → Usuários → Redefinir senha**). Você recebe uma senha temporária e cria a sua no primeiro acesso.

**É o único administrador, ou ninguém consegue entrar?** Quem entra no cPanel resolve em 2 minutos (o mesmo passo a passo aparece no painel, em **Esqueci a senha** e em **Conta**):

1. cPanel → **Gerenciador de Arquivos** → pasta **`painel-balancas-privado`** (na pasta inicial da conta, ao lado da `public_html`). **Não** é a pasta `painel` do site.
2. Clique em **+ Arquivo** e crie nessa pasta um arquivo vazio chamado **`recuperar.txt`** (também vale o nome `recuperar`).
3. Abra o painel: em vez da tela de entrar aparece **Recuperar acesso**, e o painel grava um código em `primeiro-acesso.php` (na mesma pasta; veja o item 3 para ler o código).
4. Digite o código, o **seu usuário de administrador** e a senha nova duas vezes. Esse administrador ganha a senha nova (e volta a ficar ativo, se estava desativado); um usuário que não existe é criado como administrador; usuário de editor é recusado.
5. Pronto: você já entra. O painel apaga o `recuperar.txt` e o código sozinho (se não conseguir, ele avisa: apague à mão). Os outros usuários, as cópias e o resto continuam como estão.

Não use mais o método antigo de apagar o `config.php`: hoje ele não guarda mais a senha.

Bloqueado por errar a senha? Depois de **5 erros seguidos** com o mesmo usuário, **aquele usuário** fica bloqueado naquela internet por **15 minutos** (os outros continuam entrando). Se a internet inteira somar **20 erros** (de vários usuários), ela toda fica bloqueada pelo mesmo tempo. É só esperar (ou, pelo cPanel, apagar `tentativas.php` da pasta privada).

---

## 6. Segurança (o que já está feito)

- Senha guardada só como *hash* (`password_hash`); nunca em texto.
- Primeiro acesso e “Recuperar acesso” só com o código lido no cPanel (ninguém “chega antes” e cria a senha).
- Cada pessoa com o seu usuário. O papel é lido do `usuarios.php` a cada página: área de administrador (Usuários, Atividades, Cópias, Empresa e a Capa do site, Categorias, Atualizações do catálogo) recusada para editor com erro 403, pelo endereço, por formulário e pelo `fetch`. Senha temporária gerada pelo painel (nunca digitada pelo administrador), mostrada uma vez e nunca gravada no registro.
- Limite de 5 erros de senha por usuário digitado (no mesmo IP) → 15 minutos de bloqueio só para esse usuário; e um teto de 20 erros por IP, somando todos, contra tentativas em massa; tempo de resposta igual para usuário certo ou errado.
- Sessão com cookie `HttpOnly`, `SameSite=Strict` e `Secure` (no HTTPS), novo ID a cada login, expiração após 2 h sem uso, e todos os aparelhos saem quando a senha é trocada.
- Proteção contra CSRF em todos os envios (token + conferência da origem); nada é alterado por link (GET).
- Todo texto é escapado ao aparecer no painel; no catálogo, `<`, `>` e `&` são gravados como `\u003C`, `\u003E` e `\u0026` (não dá para “fechar” o script do site).
- A anotação interna dos produtos fica na pasta privada (`notas.php`), nunca no `produtos.js` público.
- `painel/.htaccess` nega `config.php`, `.user.ini`, `.htaccess` e qualquer `.md` (o `.htaccess` da raiz também). Isso depende do Apache, como na HostGator; num servidor que ignore `.htaccess` (nginx, `php -S`) é preciso repetir essas regras na configuração dele.
- Fotos de até 20 megapixels (o site usa no máximo 900 px): o processamento cabe no limite de memória de 256 MB.
- Fotos e logos conferidos (tipo real do arquivo + dimensões) e **redesenhados** pelo servidor: qualquer código escondido no arquivo é descartado; nome do arquivo sempre aleatório; pastas de fotos e de logos com `.htaccess` que impede rodar scripts. Logo em SVG é recusado (mesmo disfarçado de `.png`).
- Campos validados (tamanhos, listas fechadas, código só com `a-z 0-9 -`); nenhum caminho de arquivo vem do usuário.
- Cabeçalhos: `Content-Security-Policy` restritiva, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, `noindex`.
- Gravação do catálogo, da lista de clientes e dos ajustes da empresa “tudo ou nada” (arquivo temporário + troca) e com trava: dois aparelhos salvando ao mesmo tempo não corrompem o arquivo. Na área Empresa, cada parte grava só as suas chaves: dois administradores mexendo em partes diferentes não apagam o que o outro salvou.
- O comentário do topo do `ajustes.js` (que todo visitante do site baixa) nunca leva texto digitado: o nome de quem mudou fica só com letras, números, espaço e `.-_`.
- **Mais procurados** sem cookie e sem rastreador de fora: o site manda um aviso mínimo (`{t, p, id, o}`) para o `painel/contar.php`, que nunca abre sessão, confere cada campo contra listas fechadas (o produto precisa existir no catálogo), ignora robôs (`bot`, `crawl`, `spider`, `preview`, `headless`… ou navegador vazio), recusa aviso vindo de outro site (`Sec-Fetch-Site`/`Origin`) e responde sempre `204`. O IP não é guardado: o limite por visitante (120 avisos por dia, 30 por minuto) usa só um código HMAC(rede + navegador) de 4 letras (16 bits), e o limite por rede (o IP; no IPv6, o prefixo /64: no máximo 300 avisos e 20 navegadores diferentes por dia, para que trocar o navegador a cada pedido não fure o limite) usa um código HMAC(rede) do mesmo tamanho, os dois com uma chave trocada todo dia; a lista do dia é apagada na virada do dia. Do IP, a lista guarda só esses 16 bits do código da rede, e muitos IPs dão o mesmo código: com a chave do dia e a lista na mão, não dá para saber quem visitou (só “um entre milhares de IPs possíveis”), e dois visitantes com o mesmo código apenas dividem o limite. A lista do dia guarda no máximo 5000 códigos; se ela encher (enxurrada de robôs vindos de muitas redes), quem não cabe mais nela ainda conta, mas no máximo 20 avisos por minuto somando todos, e o dia fica marcado como **limitado** no arquivo do mês: em **Mais procurados** aparece o aviso “a contagem foi limitada por excesso de acessos automáticos” nesse dia (os números dele podem ficar menores do que as visitas de verdade). Só somas por dia ficam guardadas (24 meses). O site não conta quando `navigator.webdriver`, na página 404 e no aparelho com “Não contar os acessos deste aparelho”.

### Camada extra recomendada: “Privacidade de diretórios”

Para que o painel peça uma **segunda senha** antes mesmo de aparecer (bloqueia robôs que procuram páginas de login):

1. cPanel → **Privacidade de diretórios** (*Directory Privacy*) → navegue até `public_html` → clique em **`painel`**.
2. Marque **Proteger com senha este diretório**, dê um nome (ex.: `Painel`) e **Salvar**.
3. Em **Criar usuário**, escolha um usuário e uma senha **diferentes** dos do painel → **Salvar**.

O navegador vai pedir essa senha uma vez por sessão; depois aparece a tela normal do painel. (O cPanel acrescenta as linhas dele no `painel/.htaccess`; não apague as que já estão lá.) O bloco `<Files "contar.php">` do `painel/.htaccess` deixa **só** o `contar.php` fora dessa senha (é por ele que o site conta as visitas para **Mais procurados**; sem o bloco, os visitantes receberiam o pedido de senha). Num servidor sem `.htaccess` (nginx, `php -S`) não há nada a repetir para ele.

**Cada atualização do site tira essa senha:** o `atualizacao.zip` traz um `painel/.htaccess` novo, que substitui o do servidor junto com as linhas do cPanel. Logo depois de extrair, volte em cPanel → **Privacidade de diretórios** → `painel`, deixe marcado **Proteger com senha este diretório** (se aparecer desmarcado, marque de novo) e **Salvar**: o usuário continua lá e o cPanel grava as linhas dele no fim do arquivo novo, depois do bloco do `contar.php`. Confira numa janela anônima nova: `/painel/` pede a senha extra e o site continua contando (**Mais procurados** mostra as visitas de hoje). Passo a passo em `docs/PUBLICAR-NO-CPANEL.md`, item 6, passo 4.

---

## 7. Problemas comuns

| O que aparece | O que fazer |
|---|---|
| “A foto é maior do que o servidor aceita” | Normalmente o painel reduz a foto no próprio celular antes de enviar. Se mesmo assim aparecer, cPanel → **MultiPHP INI Editor** → seu domínio → `upload_max_filesize` = **10M** e `post_max_size` = **12M** (a pasta do painel já traz um `.user.ini` pedindo isso). |
| “Não consegui ler o catálogo” | O `produtos.js` foi editado à mão ou substituído por uma versão antiga. Vá em **Cópias** → **Restaurar** a mais recente. Se o painel nunca foi usado, o arquivo precisa ser convertido (item 9). |
| “O servidor não deixa gravar…” | Permissões: `compartilhado/dados/produtos.js`, `clientes.js` e `ajustes.js` = 644 e as pastas `compartilhado/dados`, `img/produtos`, `img/produtos-recorte`, `img/clientes` = 755. |
| “A lista de clientes ainda não foi instalada no servidor” | Falta `compartilhado/dados/clientes.js`: instale o `atualizacao.zip` gerado com `--incluir-clientes` (`docs/PUBLICAR-NO-CPANEL.md`, item 6). |
| “Não consegui ler a lista de clientes” | O `clientes.js` foi editado à mão ou substituído. **Cópias** → aba **Clientes** → **Restaurar** a mais recente. Os produtos não são afetados. |
| “Não consegui ler os ajustes do site” (área Empresa) | O `ajustes.js` foi editado à mão ou substituído. **Restaurar** uma cópia (aba **Empresa** em Cópias) ou **Recomeçar com os dados originais**. O site continua no ar, com os dados de `empresa.js`. |
| Mudei na área Empresa e o site não mudou | Recarregue a página do site. Se continuar igual, confira se o `.htaccess` da raiz tem `ajustes\.js` na regra “sem cache” (vem no pacote do site). |
| “A página ficou desatualizada” | A página ficou aberta muito tempo ou veio de outro lugar. Recarregue e repita. |
| Saí sozinho | Passou de 2 horas sem uso, a senha foi trocada em outro aparelho, ou um administrador redefiniu a sua senha ou desativou o seu acesso (o aviso diz qual). Entre de novo: o painel volta para a tela em que você estava (no formulário do produto, ofereça **Recuperar** o rascunho). |
| “Fora da página inicial” no destaque | A página inicial mostra até 8 destaques, revezando as categorias. Tire a estrela de outro produto ou ponha este mais para cima na categoria (**Mudar lugar**). |

Erros inesperados aparecem com um código (ex.: `ref. 3FA2C1`); o detalhe fica em `painel-balancas-privado/erros.php`.

---

## 8. Configuração (`painel/config.php`)

É o único lugar com caminhos e limites: pasta `compartilhado` (padrão: ao lado do painel), endereço do site, pasta privada (padrão automático), tempo de inatividade (2 h), tentativas por usuário (5), teto por internet (`tentativas_max_ip`, 20) e bloqueio (15 min), validade do código (24 h), senha mínima (10), cópias (30 sempre, 14 dias até 200, exclusões e restaurações 30 dias, juntar ações seguidas em 10 min), produto da capa (`produto_capa`, o mesmo `data-hero-id` do `index.html`; só vale enquanto ninguém escolheu outra capa em Empresa → Capa do site), foto (8 MB, de 300 px a 20 megapixels, salva com até 900 px) e qualidade (84), logos de clientes (`logo_max_bytes` 5 MB, `logo_min_largura` 80 px, salvos com até `logo_max_largura` × `logo_max_altura` = 600 × 300 px, qualidade `logo_qualidade` 90).

---

## 9. Para quem mantém o site (técnico)

**Usuários.** `usuarios.php` (pasta privada): `{"versao":1,"usuarios":[{"usuario","nome","papel":"admin|editor","hash","ativo","trocar_senha","criado","criado_por","alterado","senha_alterada","ultimo_acesso"}]}`, gravado com trava (`comTrava('instalacao')`) e de forma atômica. Login sem diferença de maiúsculas e imutável. A conversão do `config.php` antigo (`migrarContaAntiga()` em `lib/usuarios.php`) roda uma vez, no primeiro pedido: mesmo login e mesmo hash viram um Administrador e o `config.php` vira `{"migrado":…,"para":"usuarios.php"}`; a “impressão” da senha na sessão usa a mesma fórmula, então a sessão aberta continua. Ações só de administrador: `SO_ADMIN` em `index.php`. Telas: `lib/visoes-usuarios.php`. Se `usuarios.php` estiver danificado, o painel mostra o Primeiro acesso e guarda o arquivo como `usuarios-danificado-*.php`.

**Formato do catálogo.** `compartilhado/dados/produtos.js` continua sendo um script clássico (`<script src>`), mas agora em JSON puro:

```js
/* comentário de cabeçalho (gerado pelo painel) */
window.CATEGORIAS = [ …JSON… ];
window.PRODUTOS = [ …JSON… ];
```

Gerado com `json_encode(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)`. O painel lê esse formato sem executar nada e recusa qualquer outro (mostra a tela “catálogo com problema”, de onde dá para restaurar uma cópia). Campos que o painel não conhece são preservados ao editar.

**Conversão única (antes de publicar pela primeira vez):**

```bash
node ferramentas/converter-produtos.mjs compartilhado/dados/produtos.js            # converte no lugar
node ferramentas/converter-produtos.mjs compartilhado/dados/produtos.js --verificar # só confere
```

O conversor executa o arquivo antigo numa caixa de areia do Node, preserva todos os campos e a ordem (inclusive `visivel` e `recorte`), transforma comentários `// …` dentro de um produto no campo `nota` (na primeira gravação, o painel tira essas notas do arquivo público e as guarda em `notas.php`, na pasta privada), confere que os dados ficaram idênticos e grava. Rodar de novo no arquivo já convertido não muda nada. Depois da conversão, edições à mão precisam manter JSON válido (aspas duplas nas chaves); o ideal é usar só o painel.

**Página inicial e capa.** `js/home-v3.js` tira o produto da capa (`data-hero-id` em `.pn-hero__prod`) e mostra até 8 destaques revezando as categorias (na ordem das categorias do catálogo): 4 na vitrine do quadro 03 (preferindo recortes escuros; sem recorte, a foto entra num “palco” claro) e 4 nas fichas da seção 02; a faixa de atalhos da seção 02 (`.pn-cats`) ganha um atalho por categoria com produto no site (o HTML traz as 3 de sempre para quem navega sem JavaScript). O painel calcula a mesma lista em `destaquesDaHome()` (`lib/catalogo.php`) para os selos: se mudar a regra num lugar, mude no outro. O `ferramentas/montar-site.py` confere que o `data-hero-id` existe no catálogo e é igual ao `produto_capa` do `config.php`.

**Capa escolhida no painel.** `ajustes.js` → `"capa": {"produto": "<código>", "pontos": [{"rotulo", "valor", "detalhe", "x", "y"}, … até 4], "texto": "<legenda>", "largura": px, "altura": px}` (x/y = onde fica o ponto da etiqueta, em % da caixa do recorte, com uma casa; rótulo ≤ 14, valor ≤ 24, detalhe ≤ 32, texto ≤ 48; etiquetas vazias só no fim; `largura`/`altura` = tamanho do recorte quando os pontos foram marcados, para o site reservar o espaço antes de a foto chegar). Sem a chave = a Prix 7E do HTML, exatamente como antes (escolher a Prix 7E com os textos e pontos originais apaga a chave). O `balancas.js` confere a chave inteira (fora do formato = ignorada) e o `home-v3.js` troca o produto **antes** de mostrar a capa (`.js:not(.hero-pronto)` esconde `.pn-hero__prod`/`.pn-hero__ref`; o `<head>` libera em 2,6 s se algo falhar) só se o produto está no site e tem recorte; senão (ou se o recorte não carregar) fica a Prix 7E, em silêncio. No painel, `produtoCapa()` (`lib/catalogo.php`) devolve a capa escolhida (`produtoCapaAjustes()`, `lib/ajustes.php`) ou, sem escolha, a do `index.html` (`produtoCapaHtml()`); `capaEfetiva()` devolve a que o site mostra de fato (a do HTML quando a escolhida está fora: `problemaCapa()`), e é ela que `destaquesDaHome()` tira da conta, como o site. As frases do que acontece com a capa quando o produto dela sai do site vêm de `fraseCapaFora()` (o JavaScript lê a mesma frase em `data-capa-frase`).

**Fotos.** Fotos novas: `compartilhado/img/produtos/<código>-<10 hex>.webp` (fallback `.jpg`), no máximo 900 px. Com transparência, também `compartilhado/img/produtos-recorte/<mesmo nome>` e o campo `recorte` no produto. Trocar a foto remove o `recorte` antigo do produto. Recortes antigos do site (que não foram criados pelo painel) nunca são movidos, porque algumas páginas usam esses arquivos direto.

**Lista de clientes.** `compartilhado/dados/clientes.js`, no mesmo estilo do catálogo (gravado com as mesmas opções de `json_encode`):

```js
/* comentário de cabeçalho (gerado pelo painel) */
window.CLIENTES = [ {"nome": "Brasil Frios Supermercados", "logo": "brasil-frios.webp"}, … ];
```

`logo` é só o nome do arquivo em `compartilhado/img/clientes/`; a ordem da lista é a ordem no site. Todas as páginas carregam os dados nesta ordem: `empresa.js` → `ajustes.js` → `clientes.js` → `produtos.js` → `balancas.js`; o `balancas.js` usa `window.CLIENTES` no lugar de `EMPRESA.clientes` (ignorando itens sem logo) e, sem o arquivo, continua valendo a lista de `empresa.js`. O `.htaccess` da raiz manda `clientes.js` sem cache, como o `produtos.js`. O painel (`lib/clientes.php`) lê esse formato sem executar nada; outro formato mostra a tela “lista de clientes com problema”. As cópias ficam em `copias/clientes-AAAAMMDD-HHMMSS-…-<tipo>.php` (mesmas regras de retenção das do catálogo) e os logos tirados da lista, em `lixeira/clientes--<arquivo>.php`. O `ferramentas/montar-site.py` confere o formato, os logos e a ordem dos `<script>`.

**Dados da empresa (área Empresa).** `compartilhado/dados/empresa.js` é `window.EMPRESA = {JSON};` (comentários só no cabeçalho) com os valores originais; o painel lê esse arquivo sem executar nada e nunca grava nele. O que a área Empresa muda vai para `compartilhado/dados/ajustes.js`:

```js
/* comentário de cabeçalho (gerado pelo painel; nunca com texto digitado) */
window.AJUSTES = {"versao": 1, "contato": {…}, "horario": {"grade": {"0": [], "1": [[480, 660], [780, 1080]], …}, "linhas": […]}, …};
```

Toda chave é opcional (ausente = vale `empresa.js`): `contato`, `endereco`, `mapa`, `horario` (`grade` em minutos desde 0h, 0 = domingo, e as `linhas` de texto geradas pelo painel), `feriados` (`municipais` “MM-DD”, `moveis` em dias a partir da Páscoa, `datas` “AAAA-MM-DD”), `aviso`, `google`, `redes`, `equipe`, `depoimentos`, `marcas`. Arquivo vazio: `window.AJUSTES = {};`. O `balancas.js` junta os ajustes no mesmo objeto `EMPRESA` conferindo cada parte (o que estiver fora do formato é ignorado e vale `empresa.js`); o `js/core.js` usa a grade e os feriados no “Aberto agora”, mostra a faixa de aviso e atualiza os dados estruturados (JSON-LD). Código: `lib/ajustes.php` (leitura, conferência, gravação, cópias `copias/ajustes-*.php`) e `lib/visoes-empresa.php` (telas). O `.htaccess` da raiz manda `ajustes.js` sem cache, e o `ferramentas/montar-site.py` confere os dois formatos e a ordem dos `<script>`.

**Categorias.** Ficam no próprio catálogo (`window.CATEGORIAS = [{"id", "nome", "descricao", "subcategorias": ["…"]}]`); o produto aponta para a categoria pelo `id` e para a subcategoria pelo **nome**. Regras (`lib/catalogo.php`, `problemasDasCategorias()`; as mesmas no `montar-site.py`): até 12 categorias, nome até 40 letras, descrição até 200, até 30 subcategorias de até 60 letras cada; código = slug do nome, único, nunca `todos` (é a aba “Todos” do site) nem `constructor` (nome reservado do JavaScript), e nunca muda; nomes únicos sem diferença de acento ou maiúscula, contando também o nome curto com que a categoria aparece nas abas (“Automação” para “Automação Comercial”); o nome “Todos” (categoria) e “Todos os tipos” (subcategoria) são recusados. O catálogo com código de categoria inválido ou repetido vira “catálogo com problema”. Ações em `lib/categorias.php` (cada uma = uma chamada a `alterarCatalogo()`: uma cópia, uma linha no registro; renomear subcategoria leva junto os produtos dela; mover e apagar usa `moverProdutosPara()` de `lib/lote.php`), tela em `lib/visoes-categorias.php`. Nome curto das 3 originais: `CATEGORIAS_CURTAS`/`categoriaCurta()` no painel e `CAT_CURTO` no `js/core.js` (só enquanto o nome é o original).

**Edição em lote.** `a=lote` (POST: `ids[]` ≤ 200, códigos conferidos; `acao` = `estoque:<k>`, `destaque:0|1`, `visivel:0|1`, `mover` + `destino` = `<categoria>|<subcategoria>`, `excluir` + `confirmado`) e `a=lote-desfazer` (`lib/lote.php`): uma chamada a `alterarCatalogo()` por ação, com cópia do tipo `lote` (nunca se junta com as de um produto só) ou `excluir` (30 dias). O Desfazer em lote guarda na sessão a lista de códigos e usa a regra de `guardarDesfazer()`/`exigirCopiaPropria()`: vale uma vez, só para quem excluiu.

**Mudar lugar.** `a=lugar` (produto: `id`) e `a=cliente-lugar` (cliente: `n` + `logo`): GET mostra a página com a lista de lugares (sem JavaScript); POST grava com `para` = `inicio` ou o vizinho de cima (produto: código da mesma categoria; cliente: `"<n>:<logo>"`, posição dele na lista com o item) e `desfazer=1` no Desfazer (produto: mais `categoria`, a de quando mudou; se o produto já está em outra categoria, 409 “Não deu para desfazer…”; sem o campo, só o vizinho é conferido). Funções em `lib/ordem.php` (`opcoesLugar()` monta a lista; o `painel.js` repete a mesma regra na janela e no arrasto), telas em `lib/visoes.php`. Cada mudança = uma chamada a `alterarCatalogo()`/`alterarClientes()` com cópia do tipo `ordem` (junta com as seguintes do mesmo usuário em 10 min); lugar igual não grava nada. Com `X-Requested-With: fetch` a resposta é JSON (`ordem` da lista inteira, `home`, `desfazer`); conflito com outro aparelho = 409 com `recarregar: true`. As rotas antigas das setas (`a=mover`, `a=cliente-mover` com `dir`) continuam aceitas. Onde entra quem chega: cliente novo no lugar escolhido (padrão 1º); produto novo, que trocou de categoria ou movido em lote: `posicaoNaSubcategoria()` (logo depois dos outros do mesmo tipo); pacotes de atualização: `depois_de` (sem mudança).

**Mais procurados (contagem).** O `js/core.js` (seção “Contagem anônima”) manda, com `navigator.sendBeacon` (ou `fetch` com `keepalive`), depois de a página carregar: `{"t": "pagina"|"produto"|"whatsapp"|"ligar", "p": "<página>", "id": "<produto ou ''>", "o": "<lugar ou ''>"}` para `painel/contar.php`. `p` = a classe `pg-…` do `<body>` (inicio, produtos, assistencia, sistema, empresa, contato); `o` = um dos lugares de `EST_ORIGENS` (`lib/estatisticas.php`), decidido em `lugar()` no `core.js` (um link pode forçar com `data-contar-o`/`data-contar-id`); no WhatsApp e no Ligar, o código do produto só vem com `o` = `cartao`/`ficha`; `t: "produto"` (ficha aberta, sem `o`) só na página `produtos`. Os contadores ficam em `estatisticas/AAAA-MM.php`: `{"versao": 1, "dias": {"AAAA-MM-DD": {"t|p|id|o": n}}}`, gravados com `comTrava('estatisticas')`; a lista de códigos dos produtos é lida de novo só quando o `produtos.js` muda (`ids.php`). Tela: `a=procurados` (`lib/visoes-procurados.php`, sem JavaScript para os números; gráfico em SVG com atributos, sem `style=""`).

**Atualizar o site no ar sem perder o que o painel gravou:** `python3 ferramentas/montar-site.py --atualizacao` gera o `atualizacao.zip` sem `produtos.js`, sem as fotos de produtos, sem `clientes.js`/logos (estes só com `--incluir-clientes`, para quando o servidor ainda não tem `clientes.js`) e sem `ajustes.js` (o painel cria o vazio sozinho na primeira vez que alguém entra, se o servidor ainda não tiver; `--incluir-ajustes` quase nunca é necessário). Passo a passo em `docs/PUBLICAR-NO-CPANEL.md`, item 6.

**Atualizações do catálogo (pacotes).** O `produtos.js` e as fotos do servidor nunca vêm nas atualizações, então mudanças preparadas fora do painel chegam como um pacote: `painel/pacotes/<id>/pacote.json` + `fotos/*.webp` (fundo branco) + `recortes/*.webp` (transparente), com `<id>` = `AAAA-MM-nome` (igual ao `"id"` do JSON). Formato:

```json
{ "id": "2026-10-catalogo", "titulo": "…", "resumo": "…", "criado": "2026-10-11",
  "operacoes": [
    { "tipo": "adicionar",  "produto": { …produto completo… }, "depois_de": "toledo-prix-7e", "motivo": "…" },
    { "tipo": "adicionar",  "produto": { … }, "depois_de": ["toledo-prix-7t", "toledo-prix-7e"], "motivo": "…" },
    { "tipo": "substituir", "alvo": "argox-os-2140", "nome_antigo": "Argox OS-2140", "produto": { … }, "motivo": "…" },
    { "tipo": "foto",       "alvo": "toledo-prix-5-plus", "nome_antigo": "Toledo Prix 5 Plus", "imagem": "toledo-prix-5-plus-<10 hex>.webp", "recorte": "<o mesmo>", "antes": "prix5.webp", "motivo": "…" },
    { "tipo": "remover",    "alvo": "balmak-orion-2", "nome_antigo": "Balmak Órion 2 Key", "motivo": "…" } ] }
```

- `depois_de` (opcional): um código ou uma lista de até 5 em ordem de preferência; o produto entra logo depois do primeiro que estiver no catálogo (na mesma categoria), senão no fim da categoria. (Por isso uma atualização aplicada depois pode pôr produtos fora da arrumação feita com **Mudar lugar**: confira a ordem depois de aplicar.) Use a lista quando dois produtos novos entram no mesmo lugar (ex.: Prix 6 depois da Prix 7T, que também é nova, ou da Prix 7E): a ordem final fica a mesma, aplicados juntos ou em vezes diferentes.
- `nome_antigo` (opcional): nome do produto antigo (marca e modelo), que a tela mostra quando ele já não está no catálogo (o dono excluiu antes, ou o site foi instalado do zero); sem ele, aparece o código.
- **Pacote entregue não muda.** O painel guarda o que foi aplicado por mudança (tipo + código, nunca pela posição na lista), mas o conteúdo de uma mudança que já foi aplicada no servidor não é conferido de novo. Para corrigir ou acrescentar algo depois de publicar, crie **outra pasta** (outro id, ex.: `2026-11-catalogo`) com só as mudanças novas.

O painel (`lib/pacotes.php`, telas em `lib/visoes-pacotes.php`) confere tudo com as **mesmas regras do formulário do produto** (`produtoDoFormulario`: limites, categoria e subcategoria do catálogo atual, estoque, condição, ficha técnica), recusa campos desconhecidos, produto citado em duas mudanças e foto repetida, e só aceita fotos com nome `<código>-<10 hex>.webp` dentro de `fotos/` e `recortes/` do pacote, WebP de verdade (`getimagesize`), até 900 px; nenhum caminho do JSON é usado como caminho. Aplicar = uma chamada a `alterarCatalogo()` (tipo `pacote`: uma cópia, protegida 30 dias) que copia as fotos das mudanças marcadas (nunca sobrescreve; se a gravação falhar, apaga as que copiou) e grava; o que foi aplicado/dispensado fica em `pacotes.php` (pasta privada). `painel/pacotes/.htaccess` (e uma regra no `.htaccess` da raiz) bloqueia scripts e o `pacote.json` no navegador; as fotos abrem, para a tela de revisão.

Para o catálogo do **projeto** (os próximos `site/` e `site.zip`), aplique o mesmo pacote pela linha de comando, que usa as mesmas funções (não apaga fotos antigas nem guarda cópia; `--simular` só mostra; `--incluir-opcionais` aplica também o que a tela deixaria desmarcado):

```bash
php ferramentas/aplicar-pacote.php painel/pacotes/2026-10-catalogo compartilhado --simular
php ferramentas/aplicar-pacote.php painel/pacotes/2026-10-catalogo compartilhado
```

O pacote `2026-10-catalogo` já foi aplicado assim ao `compartilhado/` do projeto (os `site/` e `site.zip` novos saem com o catálogo de outubro).

O `ferramentas/montar-site.py` leva `painel/pacotes/` no `site/`, no `site.zip` e no `atualizacao.zip`, confere cada pacote (JSON, campos, fotos WebP, categorias; e, se houver PHP na máquina, a conferência do próprio painel) e avisa quando o pacote ainda não foi aplicado ao `compartilhado/` do projeto. Um pacote já aplicado no servidor pode continuar na pasta (a tela mostra “Aplicado em … por …”); para tirar, apague a pasta dele no servidor e no projeto.

**Teste local:** `php -S 127.0.0.1:8090 -t <pasta-do-site>` com o painel em `<pasta-do-site>/painel/` e `compartilhado/` ao lado (o PHP embutido ignora `.htaccess`, e mesmo assim nada da pasta privada aparece, graças ao `<?php exit; ?>`).
