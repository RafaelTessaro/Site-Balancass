# Como colocar o site no ar (HostGator + cPanel)

O site publicado é a pasta **`site/`** deste projeto: a **Linha Verde, variação 3 (Painel)**, com as páginas Início, Assistência, Produtos, BC System, Empresa e Contato, a página de erro 404 e o **painel de produtos** (`/painel`).

As páginas são estáticas (HTML, CSS, JavaScript e imagens): sem banco de dados e sem WordPress. Só o painel usa PHP, que todo plano da HostGator já tem.

```
site/                      ← o CONTEÚDO desta pasta vai para public_html/
├── index.html             (página inicial)
├── assistencia.html  produtos.html  sistema.html  empresa.html  contato.html  404.html
├── css/  js/  img/
├── compartilhado/         (catálogo, lista de clientes, dados da empresa, fotos e logos)
├── painel/                (painel: produtos, logos de clientes, dados da empresa e usuários; PHP)
└── .htaccess  robots.txt  sitemap.xml  favicon.ico
```

> **Quem mantém o projeto:** a pasta `site/` é gerada por `python3 ferramentas/montar-site.py` (Python 3, sem instalar nada) a partir de `linha-verde/`, `compartilhado/`, `painel/` e `hospedagem/`. Com `--zip` ele também gera o **`site.zip`** (site completo, para a **primeira** publicação); com `--atualizacao`, o **`atualizacao.zip`** (para atualizar o site que já está no ar sem apagar o que foi feito no painel — item 6). Não edite a `site/` à mão: edite as fontes e gere de novo.

---

## 1. Antes de enviar (uma vez)

1. **PHP 8.3** — cPanel → **MultiPHP Manager** (em *Software*) → marque **balancass.com** → escolha **PHP 8.3** → **Aplicar**. O painel funciona do 8.1 ao 8.3. As extensões **GD** e **fileinfo** já vêm ligadas na HostGator; se o painel reclamar de fotos, confira em **Selecionar versão do PHP** → *Extensões*.
2. **Cadeado (HTTPS)** — cPanel → **Status do SSL/TLS** → o domínio precisa estar com certificado válido (se não estiver, **Executar AutoSSL** e espere). Em 10/10/2026 o `https://balancass.com` já abria com cadeado. O `.htaccess` do site manda todo mundo para `https://balancass.com` (sem `www`), então ele **só pode subir com o certificado funcionando**.
3. **Backup do site antigo** — cPanel → **Gerenciador de Arquivos** → `public_html` → **Selecionar tudo** → **Compactar** → baixe o `.zip` e guarde.

## 2. Enviar o site

1. Tenha o **`site.zip`** em mãos:
   - se recebeu o arquivo pronto, é ele;
   - se baixou o projeto do GitHub (**Code → Download ZIP**), descompacte, **entre** na pasta `site`, selecione **tudo o que está dentro** e compacte (Windows: botão direito → *Enviar para → Pasta compactada*; Mac: antes aperte **Cmd + Shift + .** para mostrar os arquivos ocultos, senão o `.htaccess` fica de fora).
2. No **Gerenciador de Arquivos**, clique em **Configurações** (canto superior direito) → marque **Mostrar arquivos ocultos (dotfiles)** → Salvar.
3. Tire o site antigo da `public_html` **movendo** (em vez de apagar): crie a pasta `bkp/site-antigo-AAAA-MM` na pasta inicial da conta (fora da `public_html`), volte à `public_html`, clique em **Selecionar tudo**, desmarque com **Ctrl+clique** as pastas `cgi-bin`, `.well-known` e `sistemas` (e pastas de outros sites, se houver) → **Mover** → digite `/bkp/site-antigo-AAAA-MM` → **Mover arquivo(s)**. Assim o site antigo vira backup, sai do ar e backups grandes (`public_html.zip`, `bkp.zip`) deixam de ficar públicos. A pasta `sistemas` fica: o site novo só redireciona `/sistemas/` e `/sistemas/sistemas.html` para a página do BC System; os demais arquivos dela (inclusive `.zip`) continuam acessíveis.
4. **Carregar** o `site.zip` para `public_html` → volte, clique com o botão direito no `site.zip` → **Extrair** → confirme `/public_html` → apague o `site.zip`.
5. Confira que o **`index.html` ficou direto em `public_html/`** (e não em `public_html/site/`). Se ficou dentro de uma pasta, mova o conteúdo dela um nível acima.
6. Confira que os **6 arquivos `.htaccess`** chegaram:
   - `public_html/.htaccess` (HTTPS, endereços do site antigo, cache, segurança)
   - `public_html/painel/.htaccess` e `public_html/painel/lib/.htaccess` (protegem o painel)
   - `public_html/compartilhado/img/produtos/.htaccess`, `.../img/produtos-recorte/.htaccess` e `.../img/clientes/.htaccess` (impedem rodar programas nas pastas de fotos e de logos; o painel recria se faltar)
7. **Permissões** (normalmente já ficam assim): pastas **755**, arquivos **644**.

## 3. Primeiro acesso ao painel

Logo depois de publicar, crie o usuário do painel:

1. Abra **`https://balancass.com/painel/`** → tela **Primeiro acesso**.
2. cPanel → Gerenciador de Arquivos → pasta **inicial da conta** (a que tem a `public_html`) → **`painel-balancas-privado`** → botão direito em **`primeiro-acesso.php`** → **Ver** → copie o código (ex.: `7KQ4-M9TX-2HPA`).
3. No painel: cole o código, escreva seu nome, escolha usuário e uma senha de **10 caracteres ou mais** → **Criar acesso e entrar**. Você entra como **Administrador**; os usuários das outras pessoas são criados depois, em **Conta → Usuários**.

O código vale 24 horas e só quem entra no cPanel consegue ler. A pasta `painel-balancas-privado` (fora da `public_html`, criada sozinha) guarda os usuários e senhas (só o *hash*), as cópias de segurança, as anotações internas dos produtos e o registro de acessos: **não apague** essa pasta. O uso do painel no dia a dia está em [`COMO-ADICIONAR-PRODUTOS.md`](COMO-ADICIONAR-PRODUTOS.md).

## 4. Opcional: segunda senha no painel (“Privacidade de diretórios”)

Faz o navegador pedir uma senha **antes** de a tela do painel aparecer (afasta robôs que procuram páginas de login):

1. cPanel → **Privacidade de diretórios** (*Directory Privacy*) → `public_html` → clique em **`painel`**.
2. Marque **Proteger com senha este diretório**, dê um nome (ex.: `Painel`) → **Salvar**.
3. Em **Criar usuário**, escolha usuário e senha **diferentes** dos do painel → **Salvar**.

O cPanel acrescenta algumas linhas no `painel/.htaccess`; não apague as que já estavam lá. **Cada atualização do site tira essa senha** (o `atualizacao.zip` traz um `painel/.htaccess` novo, que substitui o do servidor junto com as linhas do cPanel): depois de extrair, ligue de novo como no item 6, passo 4.

## 5. Testes depois de publicar

- [ ] `balancass.com`, `www.balancass.com` e `http://balancass.com` abrem a página nova, sempre em **`https://balancass.com`**.
- [ ] Todas as abas do menu, o catálogo e um produto direto: `https://balancass.com/produtos.html#p=toledo-prix-7e`.
- [ ] Endereços do site antigo levam às páginas novas: `/vendas-balancas.html` → catálogo de balanças, `/quem-somos.html` → Empresa, `/services.html` → Assistência.
- [ ] Um endereço que não existe (ex.: `/teste123`) mostra a página **“Esta página saiu da linha”**.
- [ ] `https://balancass.com/painel/config.php` e `https://balancass.com/painel/LEIA-ME.md` mostram **Proibido (403)** — a proteção está ativa.
- [ ] O link **“Área restrita”** no rodapé abre o painel.
- [ ] No painel, troque o estoque de um produto e veja o selo mudar no site (depois volte).
- [ ] No painel, abra **Clientes**: a lista aparece sem o aviso “ainda não foi instalada” e com os mesmos logos do site.
- [ ] No painel, abra **Empresa**: os campos mostram o que o site mostra (telefone, horário, equipe…).
- [ ] Botão do WhatsApp testado **num celular que não tenha o número salvo**.
- [ ] (Recomendado) **Google Search Console** → adicionar `balancass.com` → *Sitemaps* → enviar `sitemap.xml`.

**Deu “Erro 500” no site inteiro?** Quase sempre é o `.htaccess`. Renomeie `public_html/.htaccess` para `htaccess-off.txt`: se o site voltar, a hospedagem não aceitou alguma linha (as partes que dependem de módulo já estão protegidas, então isso é raro). Para o painel, veja a tabela de problemas em `painel/LEIA-ME.md`.

## 6. Atualizar o site já publicado

Depois que o painel começa a ser usado, **o que vale é o que está no servidor**: o catálogo (`compartilhado/dados/produtos.js`), as fotos de `compartilhado/img/produtos/` e `produtos-recorte/`, a lista de clientes (`compartilhado/dados/clientes.js`), os logos de `compartilhado/img/clientes/` e os dados da empresa mudados na área Empresa (`compartilhado/dados/ajustes.js`) são alterados pelo painel lá na HostGator. Por isso, para atualizar o site **não use o `site.zip`** (ele traria o catálogo do projeto por cima): use o **`atualizacao.zip`**.

**O que é o `atualizacao.zip`:** o site completo (páginas, `css/`, `js/`, `img/`, `painel/`, `.htaccess`…) **menos** os dados que o painel altera. Ele **nunca** leva `produtos.js` nem as fotos dos produtos, então os produtos, o estoque, os destaques e as fotos que você mudou no painel **não são tocados**. A lista de clientes e os logos só vão junto quando o pacote é gerado com `--incluir-clientes`, e o `ajustes.js` (área Empresa), com `--incluir-ajustes` (veja abaixo). O `empresa.js` (os dados originais da empresa) vai sempre: o que foi mudado no painel fica no `ajustes.js` e continua valendo por cima dele. Os `.htaccess` das pastas de fotos e de logos vão sempre (são iguais aos do servidor e bloqueiam scripts).

**E os produtos novos?** Mudanças no catálogo preparadas por quem cuida do site (produtos novos, modelos que saíram de linha trocados pelos atuais, fotos novas, produtos que saem do site) vão **dentro do painel**, na pasta `painel/pacotes/` (uma “atualização do catálogo”). Elas **não mudam o site sozinhas**: um administrador revê e aplica no painel (passo 8 abaixo).

### Quem mantém o projeto: gerar o pacote

```bash
python3 ferramentas/montar-site.py --atualizacao                     # o normal: sem clientes.js e sem ajustes.js
python3 ferramentas/montar-site.py --atualizacao --incluir-clientes  # só quando o servidor ainda não tem clientes.js
python3 ferramentas/montar-site.py --atualizacao --incluir-ajustes   # raramente necessário (veja abaixo)
```

- **`ajustes.js` (área Empresa):** gere **sem** `--incluir-ajustes`, inclusive na atualização que traz os **usuários** e a área **Empresa**. O servidor que ainda não tem `compartilhado/dados/ajustes.js` ganha o arquivo vazio (`window.AJUSTES = {};`) **sozinho, na primeira vez que alguém entra no painel** depois de atualizar; até lá o site funciona normalmente com os dados de `empresa.js` (só fica um aviso de "arquivo não encontrado" no console do navegador). Assim nenhum pacote leva um `ajustes.js` que poderia apagar o que foi salvo na área Empresa. Se um pacote com `--incluir-ajustes` for extraído por cima depois de usar a área Empresa: painel → **Conta → Cópias de segurança** → aba **Empresa** → **Restaurar** a mais recente traz tudo de volta **menos a última mudança** (a cópia é feita antes de cada mudança), que precisa ser feita de novo.

- **`--incluir-clientes`** leva também `compartilhado/dados/clientes.js` e os logos de `img/clientes/`. Use **só** se o servidor ainda **não** tem `compartilhado/dados/clientes.js` (ou seja, o painel ainda mostra o aviso “A lista de clientes ainda não foi instalada no servidor”). É o caso da **primeira atualização** depois da área Clientes. Depois que alguém cadastrar logos pelo painel, gere sempre **sem** essa opção, senão a lista do servidor seria substituída pela do projeto (se acontecer: painel → **Conta → Cópias de segurança** → aba **Clientes** → **Restaurar** a mais recente, que traz tudo de volta menos a última mudança feita antes da atualização).
- O pacote também leva o `painel/config.php`. Se um dia você mudou algo nele direto no servidor, refaça a mudança depois de atualizar.
- **Atualização do catálogo** (`painel/pacotes/<id>/`): vai sempre no pacote. Antes de gerar, aplique a mesma atualização ao catálogo do **projeto**, para os próximos `site/` e `site.zip` já saírem com ela: `php ferramentas/aplicar-pacote.php painel/pacotes/2026-10-catalogo compartilhado` (com `--simular` só mostra o que faria). O script de montagem confere o pacote e avisa se ele ainda não foi aplicado ao projeto.
- O script confere tudo antes (formato do catálogo, da lista de clientes, do `empresa.js` e do `ajustes.js`; a ordem `empresa.js` → `ajustes.js` → `clientes.js` → `produtos.js` → `balancas.js` em todas as páginas; o `.htaccess` mandando esses arquivos sem cache) e diz no fim o que ficou de fora do zip.

### No cPanel: instalar a atualização

1. **Antes, por garantia, um backup:** Gerenciador de Arquivos → `public_html` → **Selecionar tudo** → **Compactar** → baixe o `.zip` e depois apague-o do servidor. (As cópias de segurança do painel ficam na pasta privada, fora da `public_html`; a atualização não mexe nelas.)
2. cPanel → **Gerenciador de Arquivos** → **Configurações** → marque **Mostrar arquivos ocultos (dotfiles)** (para ver os `.htaccess`).
3. Abra a pasta **`public_html`** → **Carregar** → escolha o **`atualizacao.zip`** → espere chegar a 100% → volte.
4. Botão direito no **`atualizacao.zip`** → **Extrair** → confirme **`/public_html`** → **Extrair arquivo(s)**. O cPanel **substitui** os arquivos com o mesmo nome (páginas, CSS, JS, painel) e não apaga nada que não esteja no zip: produtos, fotos, (sem `--incluir-clientes`) clientes e logos e (sem `--incluir-ajustes`) os dados da área Empresa continuam como estavam.
   - **Se você usa a senha extra do cPanel** (item 4): o `painel/.htaccess` do zip substitui o do servidor e **as linhas da senha extra vão embora junto** (o `/painel/` passa a abrir direto na tela de login). Logo depois de extrair: cPanel → **Privacidade de diretórios** → `public_html` → **`painel`** → deixe marcado **Proteger com senha este diretório** (depois da extração ele costuma aparecer desmarcado: marque de novo) → **Salvar**. O usuário da senha extra continua lá (não precisa criar de novo) e o cPanel grava as linhas dele de novo no fim do `painel/.htaccess` novo, depois do bloco do `contar.php`. Confira numa **janela anônima nova**: navegue um pouco pelo site e depois abra `/painel/`, que tem que pedir a senha extra (feche essa janela sem entrar no painel: a janela em que alguém entra no painel deixa de contar); no seu aparelho, **Produtos → Mais procurados** mostra as visitas de hoje (o site continua contando).
5. **Apague o `atualizacao.zip`** da `public_html` (botão direito → **Excluir**). Ele não fica acessível pela internet (o `.htaccess` bloqueia `.zip` na raiz), mas não tem por que ficar lá.
6. Abra o site numa **aba anônima** e confira as páginas que mudaram; abra o painel e veja **Produtos** e **Clientes**.
7. **Depois desta atualização (usuários e área Empresa), entre no painel** com o usuário e a senha de sempre (é nessa hora que o painel cria o `ajustes.js`). O seu usuário vira **Administrador** sozinho, com a mesma senha (se o painel já estava aberto, continua logado). No menu aparece **Empresa**; em **Conta** ficam **Usuários**, **Atividades** e **Cópias de segurança**. Para pôr o seu nome: **Conta → Mudar o meu nome**. Para outras pessoas usarem o painel, crie um usuário para cada uma em **Conta → Usuários** (veja `COMO-ADICIONAR-PRODUTOS.md`, item 9).
8. **Se a atualização traz mudanças no catálogo** (a de outubro de 2026 traz: 5 balanças novas, 7 modelos que saíram de linha trocados pelos atuais, a foto nova da Prix 5 Plus e as Balmak Órion fora do site): entre no painel como **administrador** → **Produtos**. No topo aparece **“Atualização do catálogo pronta”** → **Ver e aplicar** → confira cada mudança (desmarque o que não quiser) → **Aplicar os marcados** → confirme. Depois toque em **Ver no site** e confira no celular. Para desfazer tudo: **Conta → Cópias de segurança → Restaurar** a cópia *“Antes de: aplicar a atualização ‘…’”* com a data que o quadro **Aplicado em …** da tela indica (se aplicou em mais de uma vez, a de antes da primeira). Passo a passo em `COMO-ADICIONAR-PRODUTOS.md`, item 12.
9. **Atualização com Mais procurados, edição em lote, capa do site e categorias** (outubro de 2026): é uma atualização **normal** — `python3 ferramentas/montar-site.py --atualizacao`, **sem** opção a mais — e se instala com os passos 1 a 6. Nada do que está no servidor muda: produtos, fotos, clientes, dados da empresa, usuários e cópias continuam iguais; a capa continua a **Toledo Prix 7E** (até alguém escolher outra em **Empresa → Capa do site**) e as categorias continuam as mesmas (até alguém mudar em **Categorias**). Depois de extrair:
   - **Entre no painel** em cada aparelho que você usa para ver o site (celular e computador): ao entrar, o painel liga sozinho **“Não contar os acessos deste aparelho”**, para as suas visitas não parecerem interesse de clientes (dá para desligar em **Produtos → Mais procurados**, no fim da tela).
   - Os números de **Mais procurados** começam do zero **no dia em que a atualização entra** (nada é contado antes). A contagem é anônima: sem cookies, sem serviço de fora e sem guardar o IP de quem visita.
   - Se o painel tem a **senha extra do cPanel** (“Privacidade de diretórios”, LEIA-ME do painel, item 6): extrair o zip **tira a senha extra** (o `painel/.htaccess` novo substitui o do servidor, com as linhas do cPanel): ligue de novo logo depois de extrair, como no passo 4 (cPanel → **Privacidade de diretórios** → `painel` → **Proteger com senha este diretório** marcado → **Salvar**; o usuário continua o mesmo). O `painel/.htaccess` novo traz um bloco `<Files "contar.php">` que deixa só esse arquivo aberto mesmo com a senha (é por ele que o site conta as visitas); o cPanel grava as linhas dele no fim do arquivo, depois do bloco. Se você mexer no `.htaccess` à mão, não apague esse bloco.
   - Confira: abra o site numa **janela anônima nova** (ela conta como um visitante qualquer; não entre no painel nela, senão ela deixa de contar), navegue um pouco e veja no painel **Produtos → Mais procurados**: os números de hoje já aparecem.

O `montar-site.py` acrescenta um `?v=…` nos CSS/JS de cada pacote, para o navegador dos clientes baixar a versão nova na hora (CSS e JS ficam guardados no navegador por até um mês). Por isso, gere sempre o pacote pelo script, em vez de enviar arquivos soltos.

**Para trazer para o projeto o que foi feito no painel** (por exemplo, antes de uma mudança grande): baixe pelo Gerenciador de Arquivos o `produtos.js`, o `clientes.js`, o `ajustes.js`, as fotos novas de `img/produtos/` e `img/produtos-recorte/` e os logos novos de `img/clientes/`, e coloque nas mesmas pastas de `compartilhado/`.

## 7. Checklist do conteúdo

- [x] Endereço confirmado: *Rua 13, 650 – entre as Avenidas 9 e 11 – Boa Morte*. Vale conferir o Google Meu Negócio (lá o bairro aparece como "Consolação") e atualizar o site do BC Fichas, que ainda mostra o endereço antigo (Av. 12, 1313).
- [ ] Confirmar que o **(19) 3023-9050 está no WhatsApp Business** (o link `wa.me` só funciona com número registrado no WhatsApp).
- [x] IPEM-SP: oficina autorizada (o site diz "Autorizada pelo IPEM-SP", sem número).
- [ ] Instagram da empresa (se houver).
- [ ] Fotos reais: fachada, balcão, bancada técnica, equipe. Valem muito mais que fotos de banco de imagens.
- [ ] Revisar os produtos pelo painel (todos começam como "Consulte disponibilidade").
