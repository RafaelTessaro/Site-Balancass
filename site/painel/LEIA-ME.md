# Painel do site · Balanças.com

Painel para **cadastrar, editar, esconder e excluir produtos e trocar as fotos** do site sozinho, pelo celular ou pelo computador, e para cuidar dos **logos dos clientes** (área **Clientes**). Feito em PHP puro (sem banco de dados, sem WordPress, sem plugins) e pensado para a HostGator/cPanel.

- Endereço: **`https://balancass.com/painel/`**. No site, a entrada é o link discreto **“Área restrita”** no rodapé de todas as páginas (com `nofollow`; o painel não aparece no Google).
- O painel grava direto no arquivo do catálogo (`compartilhado/dados/produtos.js`) e nas fotos (`compartilhado/img/produtos/`); os logos de clientes, em `compartilhado/dados/clientes.js` e `compartilhado/img/clientes/`. O que você salva aparece no site na hora.
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
│   │   └── img/produtos/            ← fotos (o painel salva aqui; tem .htaccess que bloqueia scripts)
│   │   └── img/produtos-recorte/    ← fotos com fundo transparente (idem)
│   │   └── img/clientes/            ← logos de clientes (idem)
│   └── painel/                      ← ESTE painel
│       ├── index.php                ← a única página
│       ├── config.php               ← caminhos e limites (único lugar com configuração)
│       ├── lib/                     ← código (bloqueado para o navegador)
│       ├── assets/                  ← visual (CSS e JS)
│       ├── .htaccess  .user.ini     ← proteção e limites do PHP
│       └── LEIA-ME.md               ← este arquivo (bloqueado para o navegador, como todo .md e .htaccess)
└── painel-balancas-privado/         ← DADOS PRIVADOS (criada sozinha, FORA do public_html)
    ├── config.php                   ← usuário e senha (só o "hash"; a senha não fica escrita)
    ├── primeiro-acesso.php          ← código do primeiro acesso (some depois do cadastro)
    ├── copias/                      ← cópias de segurança do catálogo (copia-*.php) e da lista de clientes (clientes-*.php)
    ├── notas.php                    ← anotações internas dos produtos (não vão para o site)
    ├── lixeira/                     ← fotos e logos trocados/excluídos (voltam se restaurar uma cópia)
    ├── sessoes/                     ← logins abertos
    ├── registro.php                 ← quem entrou e o que mudou (aparece em "Minha conta")
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
4. **Permissões** (normalmente já ficam assim): pastas **755**, arquivos **644**. O `produtos.js`, o `clientes.js` e as pastas de fotos e de logos precisam poder ser gravados pela sua conta (é o padrão na HostGator).
5. **HTTPS.** Deixe o certificado (AutoSSL) ativo e o redirecionamento para `https://` ligado no `.htaccess` do site. O painel só deve ser usado com o cadeado.

---

## 3. Primeiro acesso (criar usuário e senha)

1. Abra **`https://balancass.com/painel/`**. Aparece a tela **Primeiro acesso**.
2. Nesse momento o painel grava um **código de primeiro acesso** num arquivo que só quem entra no cPanel consegue ler.
3. No cPanel → **Gerenciador de Arquivos** → vá até a pasta **inicial da conta** (a mesma que tem a `public_html`) → abra **`painel-balancas-privado`** → clique com o botão direito em **`primeiro-acesso.php`** → **Ver** (ou Editar). Copie o código (ex.: `7KQ4-M9TX-2HPA`).
4. Volte ao painel, cole o código, escolha o **usuário** (ex.: `fabio`) e uma **senha com pelo menos 10 caracteres**. Dica: junte 3 ou 4 palavras que só você saiba, com um número (não copie exemplos de guias: o painel recusa a senha de exemplo de versões antigas deste guia).
5. Pronto: você já entra. O arquivo do código é apagado sozinho.

O código vale 24 horas. Se vencer, abra o painel de novo e leia o código novo no mesmo arquivo.

---

## 4. Usando no dia a dia

**Produtos** (tela inicial): todos os produtos, separados por categoria, com foto.
- **Buscar**: digite nome, marca ou código (não precisa de acento). Filtre por categoria ou por **Mostrar** (só os do site, só os ocultos, só destaques).
- **Estoque**: troque no seletor e pronto (salva sozinho). No seletor aparece o nome curto (*Consulte*); no site, o selo completo: *Consulte disponibilidade*, *Pronta entrega*, *Sob encomenda* ou *Esgotado*.
- **★ Destaque**: o produto sobe no catálogo e entra na **página inicial**, que mostra **até 8 destaques**, revezando as categorias (o 1º destaque de Balanças, o 1º de Automação, o 1º de Informática, o 2º de Balanças…). O selo mostra *Destaque · página inicial* ou *Destaque · fora da página inicial* (já há 8 na frente: tire a estrela de outro ou suba este com as setas).
- **Na capa do site**: selo do produto da balança grande do topo da página inicial (hoje a Toledo Prix 7E). A capa tem foto e link fixos: esconder, excluir ou mudar o código desse produto pede confirmação, porque o link da capa passaria a abrir o aviso “produto fora do catálogo”.
- **👁 No site**: esconde o produto do site **sem apagar** (aparece o selo *Fora do site* no painel).
- **↑ ↓**: muda a ordem dentro da categoria. No site vale: **Pronta entrega** primeiro, depois os **destaques**, depois **esta ordem**; **Esgotado** fica por último.
- **Editar**: abre o formulário completo. **🗑 Excluir**: pede confirmação; o aviso “foi excluído” tem o botão **Desfazer**.
- No celular, os botões ★ e 👁 têm o nome embaixo (*Destaque*, *No site*), e **Sair** fica no alto, com o nome.

**Novo** (botão verde): preencha nome, marca, categoria, subcategoria, resumo (aparece no cartão), descrição (aparece nos detalhes), ficha técnica (as 3 primeiras linhas aparecem no cartão), estoque, condição, preço (vazio = “Consulte”) e a **foto**.
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
- **↑ ↓** mudam a posição no site; **Editar** troca o nome e/ou o logo (o antigo vai para a lixeira); **🗑** pede confirmação e tem **Desfazer**.
- O nome (até 80 caracteres, sem `<` e `>`) não aparece escrito no site: é o texto alternativo do logo.
- Se o servidor ainda não tem `compartilhado/dados/clientes.js`, a tela mostra o aviso **“A lista de clientes ainda não foi instalada no servidor”**: instale o pacote de atualização (`docs/PUBLICAR-NO-CPANEL.md`, item 6) antes de cadastrar. Se cadastrar antes, o painel cria o arquivo só com o que for cadastrado.
- Logos que vieram com o site (`brasil-frios.webp`, `camargo.webp`…) nunca saem da pasta, mesmo excluídos da lista: algumas páginas os trazem direto no HTML (para quem navega sem JavaScript). Só os logos enviados pelo painel vão para a lixeira.

**Cópias**: lista das cópias de segurança (data, hora, o que ia ser feito), com as abas **Produtos** e **Clientes** (cada uma tem as suas cópias e restaurar uma não mexe na outra). Mudanças seguidas do mesmo tipo (ordem, estoque, destaque, no site) feitas em menos de 10 minutos ficam numa cópia só (“e mais 31 alterações do mesmo tipo”).
- **Trazer de volta** (nas cópias de exclusão): devolve só aquele produto, no mesmo lugar e com a foto.
- **Restaurar**: volta o catálogo **inteiro**, com as fotos, para aquele momento, e desfaz também tudo o que foi feito depois. A janela de confirmação diz quantas alterações serão desfeitas e quais. O estado atual também vira uma cópia, então dá para desfazer a restauração.

**Conta**: trocar a senha (os outros aparelhos conectados saem), ver as últimas atividades (“Excluiu…”, “Mudou o estoque de…”, entradas e senhas erradas, com o endereço de internet só nas tentativas erradas) e o passo a passo de **Esqueci a senha**.

**Sair**: botão no alto. Por segurança, o painel também sai sozinho depois de **2 horas sem uso**.

---

## 5. Esqueci a senha

Quem tem acesso ao cPanel resolve em 1 minuto:

O mesmo passo a passo aparece no painel, em **Esqueci a senha** (tela de entrar) e em **Minha conta**.

1. cPanel → **Gerenciador de Arquivos** → pasta **`painel-balancas-privado`** (na pasta inicial da conta, ao lado da `public_html`). **Não** é a pasta `painel` do site.
2. Apague o arquivo **`config.php`** dessa pasta (só ele: as cópias e o resto continuam).
3. Abra o painel: ele volta para a tela **Primeiro acesso** e grava um código novo em `primeiro-acesso.php`. Siga o item 3.

Bloqueado por errar a senha? Depois de **5 erros seguidos** o acesso daquela internet fica bloqueado por **15 minutos**. É só esperar (ou, pelo cPanel, apagar `tentativas.php` da pasta privada).

---

## 6. Segurança (o que já está feito)

- Senha guardada só como *hash* (`password_hash`); nunca em texto.
- Primeiro acesso só com o código lido no cPanel (ninguém “chega antes” e cria a senha).
- Limite de 5 erros de senha → 15 minutos de bloqueio por IP; tempo de resposta igual para usuário certo ou errado.
- Sessão com cookie `HttpOnly`, `SameSite=Strict` e `Secure` (no HTTPS), novo ID a cada login, expiração após 2 h sem uso, e todos os aparelhos saem quando a senha é trocada.
- Proteção contra CSRF em todos os envios (token + conferência da origem); nada é alterado por link (GET).
- Todo texto é escapado ao aparecer no painel; no catálogo, `<`, `>` e `&` são gravados como `\u003C`, `\u003E` e `\u0026` (não dá para “fechar” o script do site).
- A anotação interna dos produtos fica na pasta privada (`notas.php`), nunca no `produtos.js` público.
- `painel/.htaccess` nega `config.php`, `.user.ini`, `.htaccess` e qualquer `.md` (o `.htaccess` da raiz também). Isso depende do Apache, como na HostGator; num servidor que ignore `.htaccess` (nginx, `php -S`) é preciso repetir essas regras na configuração dele.
- Fotos de até 20 megapixels (o site usa no máximo 900 px): o processamento cabe no limite de memória de 256 MB.
- Fotos e logos conferidos (tipo real do arquivo + dimensões) e **redesenhados** pelo servidor: qualquer código escondido no arquivo é descartado; nome do arquivo sempre aleatório; pastas de fotos e de logos com `.htaccess` que impede rodar scripts. Logo em SVG é recusado (mesmo disfarçado de `.png`).
- Campos validados (tamanhos, listas fechadas, código só com `a-z 0-9 -`); nenhum caminho de arquivo vem do usuário.
- Cabeçalhos: `Content-Security-Policy` restritiva, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, `noindex`.
- Gravação do catálogo e da lista de clientes “tudo ou nada” (arquivo temporário + troca) e com trava: dois aparelhos salvando ao mesmo tempo não corrompem o arquivo.

### Camada extra recomendada: “Privacidade de diretórios”

Para que o painel peça uma **segunda senha** antes mesmo de aparecer (bloqueia robôs que procuram páginas de login):

1. cPanel → **Privacidade de diretórios** (*Directory Privacy*) → navegue até `public_html` → clique em **`painel`**.
2. Marque **Proteger com senha este diretório**, dê um nome (ex.: `Painel`) e **Salvar**.
3. Em **Criar usuário**, escolha um usuário e uma senha **diferentes** dos do painel → **Salvar**.

O navegador vai pedir essa senha uma vez por sessão; depois aparece a tela normal do painel. (O cPanel acrescenta as linhas dele no `painel/.htaccess`; não apague as que já estão lá.)

---

## 7. Problemas comuns

| O que aparece | O que fazer |
|---|---|
| “A foto é maior do que o servidor aceita” | Normalmente o painel reduz a foto no próprio celular antes de enviar. Se mesmo assim aparecer, cPanel → **MultiPHP INI Editor** → seu domínio → `upload_max_filesize` = **10M** e `post_max_size` = **12M** (a pasta do painel já traz um `.user.ini` pedindo isso). |
| “Não consegui ler o catálogo” | O `produtos.js` foi editado à mão ou substituído por uma versão antiga. Vá em **Cópias** → **Restaurar** a mais recente. Se o painel nunca foi usado, o arquivo precisa ser convertido (item 9). |
| “O servidor não deixa gravar…” | Permissões: `compartilhado/dados/produtos.js` e `clientes.js` = 644 e as pastas `compartilhado/dados`, `img/produtos`, `img/produtos-recorte`, `img/clientes` = 755. |
| “A lista de clientes ainda não foi instalada no servidor” | Falta `compartilhado/dados/clientes.js`: instale o `atualizacao.zip` gerado com `--incluir-clientes` (`docs/PUBLICAR-NO-CPANEL.md`, item 6). |
| “Não consegui ler a lista de clientes” | O `clientes.js` foi editado à mão ou substituído. **Cópias** → aba **Clientes** → **Restaurar** a mais recente. Os produtos não são afetados. |
| “A página ficou desatualizada” | A página ficou aberta muito tempo ou veio de outro lugar. Recarregue e repita. |
| Saí sozinho | Passou de 2 horas sem uso, ou a senha foi trocada em outro aparelho. Entre de novo: o painel volta para a tela em que você estava (no formulário do produto, ofereça **Recuperar** o rascunho). |
| “Fora da página inicial” no destaque | A página inicial mostra até 8 destaques, revezando as categorias. Tire a estrela de outro produto ou suba este com as setas. |

Erros inesperados aparecem com um código (ex.: `ref. 3FA2C1`); o detalhe fica em `painel-balancas-privado/erros.php`.

---

## 8. Configuração (`painel/config.php`)

É o único lugar com caminhos e limites: pasta `compartilhado` (padrão: ao lado do painel), endereço do site, pasta privada (padrão automático), tempo de inatividade (2 h), tentativas (5) e bloqueio (15 min), validade do código (24 h), senha mínima (10), cópias (30 sempre, 14 dias até 200, exclusões e restaurações 30 dias, juntar ações seguidas em 10 min), produto da capa (`produto_capa`, o mesmo `data-hero-id` do `index.html`), foto (8 MB, de 300 px a 20 megapixels, salva com até 900 px) e qualidade (84), logos de clientes (`logo_max_bytes` 5 MB, `logo_min_largura` 80 px, salvos com até `logo_max_largura` × `logo_max_altura` = 600 × 300 px, qualidade `logo_qualidade` 90).

---

## 9. Para quem mantém o site (técnico)

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

**Página inicial e capa.** `js/home-v3.js` tira o produto da capa (`data-hero-id` em `.pn-hero__prod`) e mostra até 8 destaques revezando as categorias: 4 na vitrine do quadro 03 (preferindo recortes escuros; sem recorte, a foto entra num “palco” claro) e 4 nas fichas da seção 02. O painel calcula a mesma lista em `destaquesDaHome()` (`lib/catalogo.php`) para os selos: se mudar a regra num lugar, mude no outro. O `ferramentas/montar-site.py` confere que o `data-hero-id` existe no catálogo e é igual ao `produto_capa` do `config.php`.

**Fotos.** Fotos novas: `compartilhado/img/produtos/<código>-<10 hex>.webp` (fallback `.jpg`), no máximo 900 px. Com transparência, também `compartilhado/img/produtos-recorte/<mesmo nome>` e o campo `recorte` no produto. Trocar a foto remove o `recorte` antigo do produto. Recortes antigos do site (que não foram criados pelo painel) nunca são movidos, porque algumas páginas usam esses arquivos direto.

**Lista de clientes.** `compartilhado/dados/clientes.js`, no mesmo estilo do catálogo (gravado com as mesmas opções de `json_encode`):

```js
/* comentário de cabeçalho (gerado pelo painel) */
window.CLIENTES = [ {"nome": "Brasil Frios Supermercados", "logo": "brasil-frios.webp"}, … ];
```

`logo` é só o nome do arquivo em `compartilhado/img/clientes/`; a ordem da lista é a ordem no site. Todas as páginas carregam `compartilhado/dados/clientes.js` logo depois de `empresa.js` e antes de `balancas.js`; o `balancas.js` usa `window.CLIENTES` no lugar de `EMPRESA.clientes` (ignorando itens sem logo) e, sem o arquivo, continua valendo a lista de `empresa.js`. O `.htaccess` da raiz manda `clientes.js` sem cache, como o `produtos.js`. O painel (`lib/clientes.php`) lê esse formato sem executar nada; outro formato mostra a tela “lista de clientes com problema”. As cópias ficam em `copias/clientes-AAAAMMDD-HHMMSS-…-<tipo>.php` (mesmas regras de retenção das do catálogo) e os logos tirados da lista, em `lixeira/clientes--<arquivo>.php`. O `ferramentas/montar-site.py` confere o formato, os logos e a ordem dos `<script>`.

**Atualizar o site no ar sem perder o que o painel gravou:** `python3 ferramentas/montar-site.py --atualizacao` gera o `atualizacao.zip` sem `produtos.js`, sem as fotos de produtos e sem `clientes.js`/logos (estes só com `--incluir-clientes`, para quando o servidor ainda não tem `clientes.js`). Passo a passo em `docs/PUBLICAR-NO-CPANEL.md`, item 6.

**Teste local:** `php -S 127.0.0.1:8090 -t <pasta-do-site>` com o painel em `<pasta-do-site>/painel/` e `compartilhado/` ao lado (o PHP embutido ignora `.htaccess`, e mesmo assim nada da pasta privada aparece, graças ao `<?php exit; ?>`).
