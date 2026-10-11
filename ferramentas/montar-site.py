#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
montar-site.py — monta o PACOTE DE PUBLICAÇÃO do site da Balanças.com.

Gera a pasta  site/  (na raiz do projeto), pronta para ser enviada INTEIRA
para a  public_html  da HostGator (cPanel):

    site/
    ├── index.html               ← linha-verde/index-3.html (variação 3 · Painel, a escolhida)
    ├── assistencia.html  produtos.html  sistema.html  empresa.html  contato.html
    ├── 404.html                 ← página de erro (caminhos absolutos)
    ├── css/  js/  img/          ← só o que as 6 páginas usam
    ├── compartilhado/           ← dados (empresa.js, produtos.js), balancas.js e fotos
    ├── painel/                  ← painel de produtos (PHP), sem dados privados
    ├── .htaccess  robots.txt  sitemap.xml  favicon.ico

O que o script faz nas páginas:
  * tira o seletor de variações da fase de escolha (js/preview.js e o
    trecho de CSS dele em home-v3.css); os links "Início" vão para index.html;
  * troca "../compartilhado/" por "compartilhado/" (HTML, CSS e JS);
  * acerta canonical e og:url (https://balancass.com/ e /<pagina>.html);
  * acrescenta o favicon.ico e um "?v=…" nos CSS/JS (o navegador guarda
    CSS/JS por 1 mês; com o ?v= cada publicação nova é baixada na hora).

No fim, confere o resultado (arquivos que faltam, restos de "../",
preview.js, variações antigas, formato do catálogo, dados privados no
painel) e para com erro se algo estiver errado. A pasta site/ antiga só é
substituída quando tudo deu certo.

Uso (Python 3.8+, sem instalar nada):
    python3 ferramentas/montar-site.py            # gera site/
    python3 ferramentas/montar-site.py --zip      # gera site/ e site.zip (para Carregar + Extrair no cPanel)
    python3 ferramentas/montar-site.py --saida /outra/pasta

Fontes: linha-verde/ (páginas, css, js, img, 404.html), compartilhado/,
painel/ e hospedagem/ (.htaccess, .htaccess das pastas de fotos, favicon.ico).
Nada nas fontes é alterado.
"""
import argparse
import datetime
import fnmatch
import hashlib
import json
import os
import re
import shutil
import sys
import tempfile
import zipfile
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
FONTE = RAIZ / "linha-verde"
COMPARTILHADO = RAIZ / "compartilhado"
PAINEL = RAIZ / "painel"
HOSPEDAGEM = RAIZ / "hospedagem"

DOMINIO = "https://balancass.com"

# (arquivo publicado, arquivo de origem em linha-verde/) — a ordem é a do sitemap
PAGINAS = [
    ("index.html", "index-3.html"),        # Variação 3 · Painel (escolhida pelo dono)
    ("assistencia.html", "assistencia.html"),
    ("produtos.html", "produtos.html"),
    ("sistema.html", "sistema.html"),
    ("empresa.html", "empresa.html"),
    ("contato.html", "contato.html"),
]

# Arquivos/pastas que nunca entram no pacote
LIXO = [".DS_Store", "Thumbs.db", "desktop.ini", "*.log", "*.bak", "*.tmp", "*.tmp-*", "*~", "*.swp", ".git*"]
# Do painel: dados privados de teste (a pasta privada é criada pelo próprio painel no servidor)
PAINEL_FORA = ["privado", "painel-balancas-privado", "erros.php", "sessoes", "copias", "lixeira"]
PRIVADOS = {"config.php"}  # nome permitido só em painel/config.php (o de configuração, sem senha)
SINAIS_PRIVADOS = ["primeiro-acesso.php", "tentativas.php", "registro.php", "chave.php", "erros.php"]

erros = []


def erro(msg):
    erros.append(msg)


def ler(p):
    return Path(p).read_text(encoding="utf-8")


def gravar(p, texto):
    p = Path(p)
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(texto, encoding="utf-8", newline="\n")


def eh_lixo(nome):
    return any(fnmatch.fnmatch(nome, padrao) for padrao in LIXO)


def copiar_arvore(origem, destino, fora=()):
    """Copia uma pasta inteira (com arquivos ocultos como .htaccess), sem o lixo."""
    def ignorar(pasta, nomes):
        return [n for n in nomes if eh_lixo(n) and n != ".htaccess" or n in fora]
    shutil.copytree(origem, destino, ignore=ignorar)


def url_da_pagina(nome):
    return DOMINIO + "/" if nome == "index.html" else DOMINIO + "/" + nome


def versao(arquivo):
    return hashlib.sha1(Path(arquivo).read_bytes()).hexdigest()[:10]


# ---------------------------------------------------------------------------
# Páginas
# ---------------------------------------------------------------------------
RE_PREVIEW = re.compile(r'[ \t]*<script\b[^>]*\bsrc="js/preview\.js"[^>]*>\s*</script>[ \t]*\r?\n?')
RE_CANONICAL = re.compile(r'<link rel="canonical" href="[^"]*">')
RE_OGURL = re.compile(r'<meta property="og:url" content="[^"]*">')
RE_ICONE_SVG = re.compile(r'([ \t]*)(<link rel="icon" href="[^"]*" type="image/svg\+xml">)')


def montar_pagina(publicado, origem):
    texto = ler(FONTE / origem)

    # 1. Seletor de variações (só existia na fase de escolha)
    texto, n = RE_PREVIEW.subn("", texto)
    if n != 1:
        erro(f"{origem}: esperava 1 <script src=\"js/preview.js\">, achei {n}")
    texto = re.sub(r'\s+data-variacao="\d+"', "", texto)
    # Links para as variações antigas (não deveria haver nenhum) → index.html
    texto = re.sub(r'href="index-\d+\.html', 'href="index.html', texto)

    # 2. Pastas compartilhado/ e painel/ agora ficam ao lado das páginas
    texto = texto.replace("../compartilhado/", "compartilhado/")
    texto = texto.replace('href="../painel/"', 'href="painel/"')

    # 3. Endereço oficial da página
    url = url_da_pagina(publicado)
    texto, n1 = RE_CANONICAL.subn(f'<link rel="canonical" href="{url}">', texto)
    texto, n2 = RE_OGURL.subn(f'<meta property="og:url" content="{url}">', texto)
    if n1 != 1 or n2 != 1:
        erro(f"{origem}: esperava 1 canonical e 1 og:url (achei {n1} e {n2})")

    # 4. favicon.ico (navegadores sem ícone SVG, favoritos, Windows) antes do ícone SVG
    texto, n = RE_ICONE_SVG.subn(r'\1<link rel="icon" href="favicon.ico" sizes="32x32">\n\1\2', texto, count=1)
    if n != 1:
        erro(f"{origem}: não achei o <link rel=\"icon\"> do SVG")
    return texto


def versionar(texto, site):
    """Acrescenta ?v=<hash do arquivo> nos CSS/JS locais (não nos dados, que nunca ficam em cache)."""
    def troca(m):
        attr, caminho = m.group(1), m.group(2)
        if caminho.startswith("compartilhado/dados/"):
            return m.group(0)
        alvo = site / caminho
        if not alvo.is_file():
            return m.group(0)  # a conferência final aponta o arquivo que falta
        return f'{attr}="{caminho}?v={versao(alvo)}"'
    return re.sub(r'(?<![\w-])(href|src)="((?:css|js|compartilhado/js|compartilhado/dados)/[^"?#]+\.(?:css|js))"', troca, texto)


def tirar_seletor_do_css(texto):
    """home-v3.css: remove a seção 8 (estilo do seletor de variações) e a linha dela no índice."""
    marca = re.search(r"/\* =+\s*\n\s*8\. SELETOR DE VARIAÇÃO \(js/preview\.js\)", texto)
    if not marca:
        erro("css/home-v3.css: não achei a seção 8 (seletor de variação) para remover")
        return texto
    resto = texto[marca.start():]
    if re.search(r"\n\s*/\* =+\s*\n\s*9\.", resto):
        erro("css/home-v3.css: a seção 8 não é mais a última; revise o corte")
        return texto
    texto = texto[:marca.start()].rstrip() + "\n"
    texto = re.sub(r"\n[ \t]*8\. Seletor de variação \(js/preview\.js\)[^\n]*", "", texto, count=1)
    return texto


# ---------------------------------------------------------------------------
# Arquivos da raiz
# ---------------------------------------------------------------------------
def htaccess():
    texto = ler(HOSPEDAGEM / ".htaccess")
    texto = re.sub(r"https://[A-Za-z0-9.-]+%\{REQUEST_URI\}", DOMINIO + "%{REQUEST_URI}", texto)
    obrigatorio = {
        "redirecionamento para " + DOMINIO: DOMINIO + "%{REQUEST_URI}",
        "ErrorDocument 404 /404.html": "ErrorDocument 404 /404.html",
        "bloqueio de painel/lib e painel/privado": r"RewriteRule ^painel/(lib|privado)(/|$) - [F,L]",
        "bloqueio de config.php/LEIA-ME.md/.user.ini do painel": r"RewriteRule ^painel/(config\.php|LEIA-ME\.md|\.user\.ini|\.htaccess)$",
        "bloqueio de .md/.ini/.log em qualquer pasta": r"\.(log|md|ini|sh)$",
        "bloqueio de .zip/.bak/.sql na raiz": r"RewriteRule ^[^/]+\.(zip|bak|sql)$ - [F,L,NC]",
    }
    for nome, trecho in obrigatorio.items():
        if trecho not in texto:
            erro(f"hospedagem/.htaccess: falta {nome}")
    cab = ("# Pacote gerado por ferramentas/montar-site.py em " + hoje_br() +
           " (origem: hospedagem/.htaccess).\n")
    return cab + texto


def robots():
    return ("# Balanças.com — instruções para os robôs de busca\n"
            "User-agent: *\n"
            "Allow: /\n"
            "Disallow: /painel/\n"
            "\n"
            f"Sitemap: {DOMINIO}/sitemap.xml\n")


def sitemap():
    data = datetime.date.today().isoformat()
    linhas = ['<?xml version="1.0" encoding="UTF-8"?>',
              '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">']
    for publicado, _ in PAGINAS:
        linhas.append(f"  <url><loc>{url_da_pagina(publicado)}</loc><lastmod>{data}</lastmod></url>")
    linhas.append("</urlset>")
    return "\n".join(linhas) + "\n"


def hoje_br():
    return datetime.datetime.now().strftime("%d/%m/%Y %H:%M")


# ---------------------------------------------------------------------------
# Catálogo: precisa estar no formato do painel (JSON dentro de window.X = …;)
# ---------------------------------------------------------------------------
def json_depois(src, nome):
    m = re.search(r"(?:^|\n)window\." + nome + r"\s*=\s*", src)
    if not m:
        raise ValueError(f'não achei "window.{nome} ="')
    ini = i = m.end()
    if src[ini:ini + 1] != "[":
        raise ValueError(f"window.{nome} não começa com [")
    prof, dentro = 0, False
    while i < len(src):
        c = src[i]
        if dentro:
            if c == "\\":
                i += 1
            elif c == '"':
                dentro = False
        elif c == '"':
            dentro = True
        elif c in "[{":
            prof += 1
        elif c in "]}":
            prof -= 1
            if prof == 0:
                return json.loads(src[ini:i + 1])
        i += 1
    raise ValueError(f"window.{nome} não termina")


def conferir_catalogo(arquivo):
    try:
        src = ler(arquivo)
        cats, prods = json_depois(src, "CATEGORIAS"), json_depois(src, "PRODUTOS")
    except (ValueError, json.JSONDecodeError) as ex:
        erro("compartilhado/dados/produtos.js não está no formato do painel (" + str(ex) + "). "
             "Rode antes: node ferramentas/converter-produtos.mjs compartilhado/dados/produtos.js")
        return None
    ids = [p.get("id") for p in prods]
    if len(ids) != len(set(ids)):
        erro("produtos.js: há produtos com o mesmo id")
    fotos = COMPARTILHADO / "img" / "produtos"
    for p in prods:
        if p.get("imagem") and not (fotos / p["imagem"]).is_file():
            erro(f"produtos.js: foto não encontrada para {p.get('id')}: img/produtos/{p['imagem']}")
    return len(cats), len(prods), sum(1 for p in prods if p.get("visivel") is not False)


# ---------------------------------------------------------------------------
# Conferência final do pacote
# ---------------------------------------------------------------------------
# (?<![\w-]) evita atributos como data-bc-href="tel", que não são endereços
RE_REFS = re.compile(r'(?<![\w-])(?:src|href|poster)="([^"]+)"|(?<![\w-])srcset="([^"]+)"|url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)')


def refs_locais(texto):
    for m in RE_REFS.finditer(texto):
        if m.group(2):
            for parte in m.group(2).split(","):
                parte = parte.strip().split(" ")[0]
                if parte:
                    yield parte
        else:
            yield m.group(1) or m.group(3)


def externo(ref):
    return re.match(r"^(?:[a-z][a-z0-9+.-]*:|//|#)", ref, re.I) is not None


def conferir(site):
    html_publicos = [site / p for p, _ in PAGINAS] + [site / "404.html"]
    textos_site = [f for f in site.rglob("*") if f.is_file() and f.suffix in (".html", ".css", ".js")
                   and "painel" not in f.relative_to(site).parts]

    for f in textos_site:
        rel = f.relative_to(site).as_posix()
        t = ler(f)
        if "../compartilhado" in t:
            erro(f"{rel}: ainda tem referência a ../compartilhado")
        if f.suffix == ".html":
            for proibido in ("preview.js", "index-2.html", "index-3.html", "data-variacao", 'class="lvp'):
                if proibido in t:
                    erro(f"{rel}: ainda contém {proibido}")
            if re.search(r'(?:src|href|srcset)="\.\./', t):
                erro(f"{rel}: tem caminho com ../ (sai da pasta do site)")

    # Toda referência local (HTML e CSS) aponta para um arquivo que existe
    for f in html_publicos + [x for x in textos_site if x.suffix == ".css"]:
        rel = f.relative_to(site).as_posix()
        for ref in refs_locais(ler(f)):
            if externo(ref) or ref.startswith("data:"):
                continue
            caminho = ref.split("#")[0].split("?")[0]
            if not caminho:
                continue
            alvo = (site / caminho.lstrip("/")) if caminho.startswith("/") else (f.parent / caminho)
            if caminho.endswith("/"):
                alvo = alvo / "index.html" if (alvo / "index.html").exists() else alvo / "index.php"
            if not alvo.exists():
                erro(f"{rel}: referência quebrada → {ref}")
        if f.name == "404.html":
            for ref in refs_locais(ler(f)):
                if not externo(ref) and not ref.startswith("/"):
                    erro(f"404.html: caminho relativo ({ref}); na página de erro tudo precisa começar com /")

    # canonical / og:url
    for publicado, _ in PAGINAS:
        t = ler(site / publicado)
        url = url_da_pagina(publicado)
        if f'<link rel="canonical" href="{url}">' not in t or f'<meta property="og:url" content="{url}">' not in t:
            erro(f"{publicado}: canonical/og:url diferente de {url}")

    # Só o que é da versão escolhida
    for padrao in ("index-2.html", "index-3.html", "js/preview.js", "css/home-v1.css", "css/home-v2.css",
                   "js/home-v1.js", "js/home-v2.js", "COMPONENTES.md"):
        if (site / padrao).exists():
            erro(f"o pacote não deveria ter {padrao}")

    # Painel sem dados privados
    pnl = site / "painel"
    for f in pnl.rglob("*"):
        rel = f.relative_to(site).as_posix()
        if f.is_dir() and f.name in PAINEL_FORA:
            erro(f"{rel}: pasta privada de teste no pacote")
        if f.is_file() and (f.name in SINAIS_PRIVADOS or (f.name in PRIVADOS and f.parent != pnl)):
            erro(f"{rel}: arquivo privado de teste no pacote")
        if f.is_file() and f.suffix == ".php" and f.read_bytes().startswith(b"<?php exit; ?>"):
            erro(f"{rel}: arquivo da pasta privada (começa com <?php exit; ?>) no pacote")
    for obrig in ("index.php", "config.php", ".htaccess", ".user.ini", "lib/.htaccess", "assets/painel.css", "assets/painel.js"):
        if not (pnl / obrig).is_file():
            erro(f"painel/{obrig} faltando no pacote")
    for pasta in ("produtos", "produtos-recorte"):
        if not (site / "compartilhado" / "img" / pasta / ".htaccess").is_file():
            erro(f"compartilhado/img/{pasta}/.htaccess faltando (bloqueia scripts na pasta de fotos)")
    for obrig in (".htaccess", "robots.txt", "sitemap.xml", "404.html", "favicon.ico"):
        if not (site / obrig).is_file():
            erro(f"{obrig} faltando no pacote")

    # Produto da capa: o data-hero-id do index.html existe no catálogo e é o mesmo do painel/config.php
    m = re.search(r'\bdata-hero-id="([a-z0-9-]+)"', ler(site / "index.html"))
    if not m:
        erro("index.html: falta o data-hero-id no produto da capa (.pn-hero__prod)")
    else:
        capa = m.group(1)
        cfg = re.search(r"'produto_capa'\s*=>\s*'([a-z0-9-]+)'", ler(pnl / "config.php"))
        if not cfg or cfg.group(1) != capa:
            erro(f"painel/config.php: 'produto_capa' deve ser '{capa}' (o data-hero-id do index.html)")
        try:
            ids = [p.get("id") for p in json_depois(ler(site / "compartilhado" / "dados" / "produtos.js"), "PRODUTOS")]
            if capa not in ids:
                erro(f"index.html: o produto da capa '{capa}' não está no catálogo")
        except Exception:
            pass  # conferir_catalogo já aponta o problema do catálogo
    if 'href="painel/"' not in ler(site / "index.html"):
        erro("index.html: falta o link “Área restrita” (painel/) no rodapé")


# ---------------------------------------------------------------------------
def montar(destino):
    destino = destino.resolve()
    fontes = (RAIZ, FONTE, COMPARTILHADO, PAINEL, HOSPEDAGEM)
    if any(destino == f or str(f).startswith(str(destino) + os.sep) or str(destino).startswith(str(f) + os.sep)
           for f in fontes[1:]) or destino == RAIZ or str(RAIZ).startswith(str(destino) + os.sep):
        sys.exit(f"Pasta de saída perigosa (é ou contém uma pasta de origem): {destino}")
    if destino.exists() and any(destino.iterdir()):
        parece_pacote = (destino / "index.html").is_file() and (destino / "compartilhado").is_dir()
        if not parece_pacote:
            sys.exit(f"{destino} existe e não parece um pacote gerado por este script; escolha outra --saida.")

    destino.parent.mkdir(parents=True, exist_ok=True)
    tmp = Path(tempfile.mkdtemp(prefix=".site-montando-", dir=destino.parent))
    site = tmp / "site"
    try:
        site.mkdir()

        # compartilhado/ inteiro (o painel precisa de todas as fotos e dos dados)
        copiar_arvore(COMPARTILHADO, site / "compartilhado")
        for pasta in ("produtos", "produtos-recorte"):
            regra = HOSPEDAGEM / "compartilhado" / "img" / pasta / ".htaccess"
            if regra.is_file():
                shutil.copy2(regra, site / "compartilhado" / "img" / pasta / ".htaccess")
        for f in (site / "compartilhado").rglob("*"):
            if f.is_file() and f.suffix in (".js", ".css", ".html"):
                t = ler(f)
                if "../compartilhado/" in t:
                    gravar(f, t.replace("../compartilhado/", "compartilhado/"))
        resumo_catalogo = conferir_catalogo(site / "compartilhado" / "dados" / "produtos.js")

        # Páginas
        paginas = {pub: montar_pagina(pub, org) for pub, org in PAGINAS}

        # CSS/JS: só os arquivos que as páginas usam (fica de fora o das variações 1 e 2 e o preview.js)
        usados = set()
        for t in paginas.values():
            usados.update(m.group(1) for m in re.finditer(r'(?<![\w-])(?:href|src)="((?:css|js)/[^"?#]+)"', t))
        for rel in sorted(usados):
            origem = FONTE / rel
            if not origem.is_file():
                erro(f"as páginas usam {rel}, que não existe em linha-verde/")
                continue
            t = ler(origem).replace("../compartilhado/", "compartilhado/")
            if rel == "css/home-v3.css":
                t = tirar_seletor_do_css(t)
            gravar(site / rel, t)

        # Imagens próprias do estilo (miniaturas p480, imagens de compartilhamento, ícone 512)
        copiar_arvore(FONTE / "img", site / "img")

        for pub, t in paginas.items():
            gravar(site / pub, versionar(t, site))

        # Raiz
        shutil.copy2(FONTE / "404.html", site / "404.html")
        shutil.copy2(HOSPEDAGEM / "favicon.ico", site / "favicon.ico")
        gravar(site / ".htaccess", htaccess())
        gravar(site / "robots.txt", robots())
        gravar(site / "sitemap.xml", sitemap())

        # Painel (sem a pasta privada de testes)
        copiar_arvore(PAINEL, site / "painel", fora=PAINEL_FORA)

        conferir(site)
        if erros:
            print("NÃO MONTADO — problemas encontrados:", file=sys.stderr)
            for e in erros:
                print("  - " + e, file=sys.stderr)
            sys.exit(1)

        # Tudo certo: troca a pasta antiga pela nova
        velho = None
        if destino.exists():
            velho = tmp / "antigo"
            destino.rename(velho)
        site.rename(destino)
    finally:
        shutil.rmtree(tmp, ignore_errors=True)

    # Resumo
    arquivos = [f for f in destino.rglob("*") if f.is_file()]
    total = sum(f.stat().st_size for f in arquivos)
    print(f"Pacote montado em {destino}")
    print(f"  {len(arquivos)} arquivos, {total / 1024 / 1024:.1f} MB")
    print("  Páginas: " + ", ".join(p for p, _ in PAGINAS) + " + 404.html (index.html = linha-verde/index-3.html)")
    print("  CSS/JS: " + ", ".join(sorted(f.relative_to(destino).as_posix() for f in arquivos
                                          if f.parts[-2] in ("css", "js") and f.parent.parent == destino)))
    if resumo_catalogo:
        c, p, v = resumo_catalogo
        print(f"  Catálogo: {c} categorias, {p} produtos ({v} no site), formato do painel ✓")
    print("  Painel: painel/ (primeiro acesso pelo código gravado no cPanel; veja painel/LEIA-ME.md)")
    print("  Próximo passo: enviar o CONTEÚDO de site/ para public_html (docs/PUBLICAR-NO-CPANEL.md).")


def compactar(site):
    """site.zip com o CONTEÚDO de site/ (index.html na raiz do zip), inclusive os .htaccess."""
    site = site.resolve()
    destino = site.with_suffix(".zip")
    tmp = destino.with_name(destino.name + ".tmp")
    with zipfile.ZipFile(tmp, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for f in sorted(site.rglob("*")):
            if f.is_file():
                z.write(f, f.relative_to(site).as_posix())
    tmp.replace(destino)
    with zipfile.ZipFile(destino) as z:
        nomes = z.namelist()
    ht = sum(1 for n in nomes if n.endswith(".htaccess"))
    print(f"  ZIP: {destino} ({destino.stat().st_size / 1024 / 1024:.1f} MB, {len(nomes)} arquivos, {ht} .htaccess)")


def main():
    ap = argparse.ArgumentParser(description="Monta a pasta site/ para publicar na HostGator.")
    ap.add_argument("--saida", default=str(RAIZ / "site"), help="pasta de saída (padrão: site/ na raiz do projeto)")
    ap.add_argument("--zip", action="store_true", help="também gera <saida>.zip com o conteúdo da pasta, pronto para o cPanel")
    args = ap.parse_args()
    montar(Path(args.saida))
    if args.zip:
        compactar(Path(args.saida))


if __name__ == "__main__":
    main()
