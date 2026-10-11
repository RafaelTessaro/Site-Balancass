# Como colocar o site no ar (HostGator + cPanel)

O site publicado é a pasta **`site/`** deste projeto: a **Linha Verde, variação 3 (Painel)**, com as páginas Início, Assistência, Produtos, BC System, Empresa e Contato, a página de erro 404 e o **painel de produtos** (`/painel`).

As páginas são estáticas (HTML, CSS, JavaScript e imagens): sem banco de dados e sem WordPress. Só o painel usa PHP, que todo plano da HostGator já tem.

```
site/                      ← o CONTEÚDO desta pasta vai para public_html/
├── index.html             (página inicial)
├── assistencia.html  produtos.html  sistema.html  empresa.html  contato.html  404.html
├── css/  js/  img/
├── compartilhado/         (catálogo, dados da empresa, fotos dos produtos)
├── painel/                (painel de produtos, PHP)
└── .htaccess  robots.txt  sitemap.xml  favicon.ico
```

> **Quem mantém o projeto:** a pasta `site/` é gerada por `python3 ferramentas/montar-site.py` (Python 3, sem instalar nada) a partir de `linha-verde/`, `compartilhado/`, `painel/` e `hospedagem/`. Com `--zip` ele também gera o **`site.zip`** pronto para o cPanel. Não edite a `site/` à mão: edite as fontes e gere de novo.

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
6. Confira que os **5 arquivos `.htaccess`** chegaram:
   - `public_html/.htaccess` (HTTPS, endereços do site antigo, cache, segurança)
   - `public_html/painel/.htaccess` e `public_html/painel/lib/.htaccess` (protegem o painel)
   - `public_html/compartilhado/img/produtos/.htaccess` e `.../img/produtos-recorte/.htaccess` (impedem rodar programas na pasta de fotos)
7. **Permissões** (normalmente já ficam assim): pastas **755**, arquivos **644**.

## 3. Primeiro acesso ao painel

Logo depois de publicar, crie o usuário do painel:

1. Abra **`https://balancass.com/painel/`** → tela **Primeiro acesso**.
2. cPanel → Gerenciador de Arquivos → pasta **inicial da conta** (a que tem a `public_html`) → **`painel-balancas-privado`** → botão direito em **`primeiro-acesso.php`** → **Ver** → copie o código (ex.: `7KQ4-M9TX-2HPA`).
3. No painel: cole o código, escolha usuário e uma senha de **10 caracteres ou mais** → **Criar acesso e entrar**.

O código vale 24 horas e só quem entra no cPanel consegue ler. A pasta `painel-balancas-privado` (fora da `public_html`, criada sozinha) guarda a senha (só o *hash*), as cópias de segurança do catálogo, as anotações internas dos produtos e o registro de acessos: **não apague** essa pasta. O uso do painel no dia a dia está em [`COMO-ADICIONAR-PRODUTOS.md`](COMO-ADICIONAR-PRODUTOS.md).

## 4. Opcional: segunda senha no painel (“Privacidade de diretórios”)

Faz o navegador pedir uma senha **antes** de a tela do painel aparecer (afasta robôs que procuram páginas de login):

1. cPanel → **Privacidade de diretórios** (*Directory Privacy*) → `public_html` → clique em **`painel`**.
2. Marque **Proteger com senha este diretório**, dê um nome (ex.: `Painel`) → **Salvar**.
3. Em **Criar usuário**, escolha usuário e senha **diferentes** dos do painel → **Salvar**.

O cPanel acrescenta algumas linhas no `painel/.htaccess`; não apague as que já estavam lá.

## 5. Testes depois de publicar

- [ ] `balancass.com`, `www.balancass.com` e `http://balancass.com` abrem a página nova, sempre em **`https://balancass.com`**.
- [ ] Todas as abas do menu, o catálogo e um produto direto: `https://balancass.com/produtos.html#p=toledo-prix-7e`.
- [ ] Endereços do site antigo levam às páginas novas: `/vendas-balancas.html` → catálogo de balanças, `/quem-somos.html` → Empresa, `/services.html` → Assistência.
- [ ] Um endereço que não existe (ex.: `/teste123`) mostra a página **“Esta página saiu da linha”**.
- [ ] `https://balancass.com/painel/config.php` e `https://balancass.com/painel/LEIA-ME.md` mostram **Proibido (403)** — a proteção está ativa.
- [ ] O link **“Área restrita”** no rodapé abre o painel.
- [ ] No painel, troque o estoque de um produto e veja o selo mudar no site (depois volte).
- [ ] Botão do WhatsApp testado **num celular que não tenha o número salvo**.
- [ ] (Recomendado) **Google Search Console** → adicionar `balancass.com` → *Sitemaps* → enviar `sitemap.xml`.

**Deu “Erro 500” no site inteiro?** Quase sempre é o `.htaccess`. Renomeie `public_html/.htaccess` para `htaccess-off.txt`: se o site voltar, a hospedagem não aceitou alguma linha (as partes que dependem de módulo já estão protegidas, então isso é raro). Para o painel, veja a tabela de problemas em `painel/LEIA-ME.md`.

## 6. Atualizações futuras (importante)

Depois que o painel começar a ser usado, **o catálogo que vale é o do servidor**: o `compartilhado/dados/produtos.js` e as fotos de `compartilhado/img/produtos/` e `produtos-recorte/` são alterados pelo painel lá na HostGator.

Ao enviar uma versão nova do site:

- envie só o que mudou (páginas `.html`, `css/`, `js/`, `img/`, `painel/`);
- **nunca** substitua `compartilhado/dados/produtos.js` nem as pastas de fotos de produtos pela versão do projeto, ou as alterações feitas no painel se perdem (se acontecer: painel → **Cópias** → **Restaurar**);
- para trazer o catálogo do servidor para o projeto, baixe o `produtos.js` e as fotos novas pelo Gerenciador de Arquivos e coloque em `compartilhado/`.

Gere sempre o pacote pelo `montar-site.py`: ele acrescenta um `?v=…` nos CSS/JS, para o navegador dos clientes baixar a versão nova na hora (CSS e JS ficam guardados no navegador por até um mês).

## 7. Checklist do conteúdo

- [x] Endereço confirmado: *Rua 13, 650 – entre as Avenidas 9 e 11 – Boa Morte*. Vale conferir o Google Meu Negócio (lá o bairro aparece como "Consolação") e atualizar o site do BC Fichas, que ainda mostra o endereço antigo (Av. 12, 1313).
- [ ] Confirmar que o **(19) 3023-9050 está no WhatsApp Business** (o link `wa.me` só funciona com número registrado no WhatsApp).
- [x] IPEM-SP: oficina autorizada (o site diz "Autorizada pelo IPEM-SP", sem número).
- [ ] Instagram da empresa (se houver).
- [ ] Fotos reais: fachada, balcão, bancada técnica, equipe. Valem muito mais que fotos de banco de imagens.
- [ ] Revisar os produtos pelo painel (todos começam como "Consulte disponibilidade").
