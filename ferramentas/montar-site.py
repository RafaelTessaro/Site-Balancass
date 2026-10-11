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
    ├── compartilhado/           ← dados (empresa.js, ajustes.js, clientes.js, produtos.js), balancas.js, fotos e logos
    ├── painel/                  ← painel de produtos e clientes (PHP), sem dados privados
    ├── .htaccess  robots.txt  sitemap.xml  favicon.ico

O que o script faz nas páginas:
  * tira o seletor de variações da fase de escolha (js/preview.js e o
    trecho de CSS dele em home-v3.css); os links "Início" vão para index.html;
  * troca "../compartilhado/" por "compartilhado/" (HTML, CSS e JS);
  * acerta canonical e og:url (https://balancass.com/ e /<pagina>.html);
  * acrescenta o favicon.ico e um "?v=…" nos CSS/JS (o navegador guarda
    CSS/JS por 1 mês; com o ?v= cada publicação nova é baixada na hora).

No fim, confere o resultado (arquivos que faltam, restos de "../",
preview.js, variações antigas, formato do catálogo, da lista de clientes,
dos dados da empresa (empresa.js) e dos ajustes do painel (ajustes.js),
a ordem dos dados em todas as páginas — empresa.js → ajustes.js →
clientes.js → produtos.js → balancas.js —, dados privados no painel) e
para com erro se algo estiver errado. A pasta site/ antiga só é
substituída quando tudo deu certo.

Uso (Python 3.8+, sem instalar nada):
    python3 ferramentas/montar-site.py            # gera site/
    python3 ferramentas/montar-site.py --zip      # gera site/ e site.zip (site COMPLETO, para a 1ª publicação)
    python3 ferramentas/montar-site.py --atualizacao                     # gera site/ e atualizacao.zip
    python3 ferramentas/montar-site.py --atualizacao --incluir-clientes  # idem, levando também os clientes
    python3 ferramentas/montar-site.py --atualizacao --incluir-ajustes   # idem, levando também o ajustes.js
    python3 ferramentas/montar-site.py --saida /outra/pasta

atualizacao.zip = o site completo MENOS os dados que o painel altera no
servidor, para extrair por cima da public_html sem perder nada:
  * nunca leva compartilhado/dados/produtos.js nem as fotos de
    compartilhado/img/produtos/ e img/produtos-recorte/;
  * só leva compartilhado/dados/clientes.js e os logos de img/clientes/
    com --incluir-clientes (use quando o servidor ainda NÃO tem clientes.js,
    ou seja, antes de o painel começar a cadastrar logos);
  * só leva compartilhado/dados/ajustes.js (o que o painel mudou na área
    Empresa: contato, horário, feriados, aviso…) com --incluir-ajustes —
    quase nunca é preciso: no servidor que ainda não tem o arquivo, o painel
    cria o vazio ("window.AJUSTES = {};") na primeira vez que alguém entra;
  * os .htaccess dessas pastas (que bloqueiam scripts) vão sempre.
O zip fica ao lado da pasta de saída (padrão: atualizacao.zip na raiz do projeto).

Fontes: linha-verde/ (páginas, css, js, img, 404.html), compartilhado/,
painel/ e hospedagem/ (.htaccess, .htaccess das pastas de fotos e logos, favicon.ico).
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
# Nomes que só podem estar nestes lugares do painel: painel/config.php (configuração, sem senha) e
# painel/lib/usuarios.php (código); em outro lugar, são da pasta privada (senhas, usuários)
PRIVADOS = {"config.php": "painel", "usuarios.php": "painel/lib"}
SINAIS_PRIVADOS = ["primeiro-acesso.php", "tentativas.php", "registro.php", "registro-anterior.php", "chave.php", "erros.php",
                   "recuperar.txt", "recuperar"]
# Pastas de imagens que o painel grava; cada uma leva um .htaccess que bloqueia scripts (hospedagem/compartilhado/img/<pasta>/)
PASTAS_DO_PAINEL = ("produtos", "produtos-recorte", "clientes")
# Logo de cliente: só o nome do arquivo (mesma regra do painel, lib/clientes.php)
RE_ARQUIVO_LOGO = re.compile(r"^[A-Za-z0-9][A-Za-z0-9._-]{0,120}\.(?:webp|png|jpe?g|svg)$")

erros = []
avisos = []   # não impedem a montagem; aparecem no resumo


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
        "clientes.js sem cache (no-cache, como produtos.js)": r"clientes\.js",
        # sem esta regra, uma mudança na área Empresa (telefone, aviso, horário) ficaria 1 mês no cache
        "ajustes.js sem cache (no-cache, como produtos.js)": r"ajustes\.js)$",
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
def json_depois(src, nome, abre="["):
    m = re.search(r"(?:^|\n)window\." + nome + r"\s*=\s*", src)
    if not m:
        raise ValueError(f'não achei "window.{nome} ="')
    ini = i = m.end()
    if src[ini:ini + 1] != abre:
        raise ValueError(f"window.{nome} não começa com {abre}")
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


def conferir_clientes(arquivo):
    """compartilhado/dados/clientes.js: existe e está no formato do painel (window.CLIENTES = [JSON];)."""
    if not arquivo.is_file():
        erro("compartilhado/dados/clientes.js não existe. É a lista de logos de clientes que o painel edita "
             "(window.CLIENTES = [{\"nome\": \"…\", \"logo\": \"arquivo.webp\"}, …];). Crie o arquivo no formato do painel "
             "(painel/LEIA-ME.md, item 9) com os clientes de compartilhado/dados/empresa.js.")
        return None
    try:
        lista = json_depois(ler(arquivo), "CLIENTES")
    except (ValueError, json.JSONDecodeError) as ex:
        erro("compartilhado/dados/clientes.js não está no formato do painel (" + str(ex) + "). "
             "O formato é: window.CLIENTES = [ {\"nome\": \"…\", \"logo\": \"arquivo.webp\"} ];  (JSON puro, aspas duplas).")
        return None
    if not isinstance(lista, list):
        erro("clientes.js: window.CLIENTES precisa ser uma lista [ … ]")
        return None
    logos = arquivo.parent.parent / "img" / "clientes"   # compartilhado/img/clientes do pacote
    sem_logo = 0
    for n, c in enumerate(lista, 1):
        if not isinstance(c, dict) or not isinstance(c.get("nome"), str) or not c["nome"].strip():
            erro(f"clientes.js: o item {n} não tem \"nome\"")
            continue
        logo = c.get("logo", "")
        if not isinstance(logo, str):
            erro(f"clientes.js: o \"logo\" de {c['nome']} não é texto")
        elif not logo:
            sem_logo += 1  # o site ignora (não aparece)
        elif not RE_ARQUIVO_LOGO.match(logo):
            erro(f"clientes.js: nome de arquivo inválido no logo de {c['nome']}: {logo!r}")
        elif not (logos / logo).is_file():
            erro(f"clientes.js: logo não encontrado para {c['nome']}: img/clientes/{logo}")
    return len(lista), sem_logo


def dados_js(src, nome, abre):
    """Arquivo de dados do painel: comentário(s) /* … */ + window.NOME = <JSON>; e nada mais.
    Retorna o JSON. Qualquer outra coisa (código solto, vírgula sobrando, aspas simples) → ValueError."""
    resto = src.lstrip("﻿")
    while True:
        resto = resto.lstrip()
        if not resto.startswith("/*"):
            break
        fim = resto.find("*/")
        if fim < 0:
            raise ValueError("comentário /* sem o */ de fechamento")
        resto = resto[fim + 2:]
    m = re.match(r"window\." + nome + r"\s*=\s*", resto)
    if not m:
        raise ValueError(f'depois do comentário do topo precisa vir "window.{nome} = …"')
    corpo = resto[m.end():].rstrip()
    if not corpo.endswith(";"):
        raise ValueError(f"window.{nome} precisa terminar com ;")
    corpo = corpo[:-1].rstrip()
    if not corpo.startswith(abre):
        raise ValueError(f"window.{nome} não começa com {abre}")
    try:
        return json.loads(corpo)
    except json.JSONDecodeError as ex:
        raise ValueError(f"o conteúdo de window.{nome} não é JSON puro ({ex.msg}, linha {ex.lineno} do bloco)")


def texto_ok(v, vazio=False):
    return isinstance(v, str) and (vazio or bool(v.strip()))


def conferir_empresa(arquivo):
    """compartilhado/dados/empresa.js: window.EMPRESA = {JSON}; com os campos que o site e o painel usam."""
    try:
        d = dados_js(ler(arquivo), "EMPRESA", "{")
    except (OSError, ValueError) as ex:
        erro("compartilhado/dados/empresa.js não está no formato JSON (" + str(ex) + "). O formato é: comentário no topo + "
             "window.EMPRESA = { \"nome\": \"…\", … };  (aspas duplas em tudo, sem comentários lá dentro). "
             "O painel (área Empresa) lê este arquivo para mostrar os valores atuais.")
        return None
    problemas = []
    for k in ("nome", "telefone", "email", "mapa", "whatsappMensagemPadrao"):
        if not texto_ok(d.get(k)):
            problemas.append(f'"{k}" (texto)')
    if not re.fullmatch(r"\+55\d{10,11}", str(d.get("telefoneLink", ""))):
        problemas.append('"telefoneLink" (+55 DDD número, só dígitos)')
    if not re.fullmatch(r"55\d{10,11}", str(d.get("whatsapp", ""))):
        problemas.append('"whatsapp" (55 DDD número, só dígitos)')
    end = d.get("endereco")
    if not isinstance(end, dict) or not all(texto_ok(end.get(k)) for k in ("rua", "bairro", "cidade", "uf", "cep")):
        problemas.append('"endereco" (rua, bairro, cidade, uf, cep)')
    g = d.get("google")
    if not isinstance(g, dict) or not texto_ok(g.get("nota")) or not isinstance(g.get("avaliacoes"), int):
        problemas.append('"google" (nota em texto, avaliacoes em número)')
    if not isinstance(d.get("redes"), dict):
        problemas.append('"redes" ({ "facebook": "…", "instagram": "…" })')
    listas = {"horarios": ("dias", "horas"), "equipe": ("nome", "cargo"), "depoimentos": ("texto", "nome"), "clientes": ("nome",)}
    for k, campos in listas.items():
        v = d.get(k)
        if not isinstance(v, list) or not v or not all(isinstance(x, dict) and all(texto_ok(x.get(c)) for c in campos) for x in v):
            problemas.append(f'"{k}" (lista de {{ {", ".join(campos)} }})')
    if not isinstance(d.get("marcas"), list) or not all(texto_ok(x) for x in d["marcas"]):
        problemas.append('"marcas" (lista de textos)')
    if problemas:
        erro("compartilhado/dados/empresa.js: campo faltando ou com tipo errado: " + "; ".join(problemas))
    return d


# Ajustes do painel (área Empresa): chaves e o tipo de cada uma (contrato com painel/lib/ajustes.php)
AJUSTES_CHAVES = {"versao": int, "contato": dict, "endereco": dict, "mapa": str, "horario": dict, "feriados": dict,
                  "aviso": dict, "google": dict, "redes": dict, "equipe": list, "depoimentos": list, "marcas": list}
RE_DIA = re.compile(r"\d{4}-\d{2}-\d{2}")


def conferir_ajustes(arquivo):
    """compartilhado/dados/ajustes.js: existe e está no formato do painel (window.AJUSTES = {JSON};).
    Retorna a lista das partes preenchidas ([] = sem ajustes) ou None se houver problema."""
    if not arquivo.is_file():
        erro("compartilhado/dados/ajustes.js não existe. É o arquivo que o painel grava na área Empresa; "
             "crie-o com um comentário no topo e a linha  window.AJUSTES = {};  (sem ajustes, o site usa empresa.js).")
        return None
    try:
        a = dados_js(ler(arquivo), "AJUSTES", "{")
    except ValueError as ex:
        erro("compartilhado/dados/ajustes.js não está no formato do painel (" + str(ex) + "). "
             "O formato é: comentário no topo + window.AJUSTES = { … };  (JSON puro; vazio = {}).")
        return None
    problemas = []
    for k, v in a.items():
        tipo = AJUSTES_CHAVES.get(k)
        if tipo is None:
            problemas.append(f'chave desconhecida "{k}"')
        elif not isinstance(v, tipo) or (tipo is int and isinstance(v, bool)):
            problemas.append(f'"{k}" precisa ser {dict(zip((dict, list, str, int), ("um objeto {…}", "uma lista […]", "um texto", "um número")))[tipo]}')
    h = a.get("horario")
    if isinstance(h, dict) and "grade" in h:
        g = h["grade"]
        ok = isinstance(g, dict) and all(isinstance(g.get(str(d)), list) and len(g[str(d)]) <= 3 for d in range(7))
        if ok:
            for d in range(7):
                fim = -1
                for f in g[str(d)]:
                    if not (isinstance(f, list) and len(f) == 2 and all(isinstance(x, int) and 0 <= x <= 1440 for x in f)
                            and f[0] < f[1] and f[0] >= fim):
                        ok = False
                        break
                    fim = f[1]
        if not ok:
            problemas.append('"horario.grade" fora do formato ({"0": [[início, fim], …], …, "6": […]}, minutos de 0 a 1440)')
    av = a.get("aviso")
    if isinstance(av, dict):
        # Desligado, o texto pode ficar vazio: é o que o painel grava ao salvar "Aviso no site" sem aviso
        if not texto_ok(av.get("texto", ""), vazio=True):
            problemas.append('"aviso.texto" precisa ser um texto')
        elif av.get("ativo") is True and not texto_ok(av.get("texto")):
            problemas.append('"aviso.texto" vazio com o aviso ligado')
        for k in ("inicio", "fim"):
            if av.get(k) not in (None, "") and not (isinstance(av[k], str) and RE_DIA.fullmatch(av[k])):
                problemas.append(f'"aviso.{k}" precisa ser AAAA-MM-DD')
        if av.get("tom", "info") not in ("info", "alerta"):
            problemas.append('"aviso.tom" precisa ser "info" ou "alerta"')
    for k in ("mapa",):
        if isinstance(a.get(k), str) and not a[k].startswith("https://"):
            problemas.append(f'"{k}" precisa começar com https://')
    if problemas:
        erro("compartilhado/dados/ajustes.js: " + "; ".join(problemas))
        return None
    return [k for k in a if k != "versao"]


RE_SCRIPT = re.compile(r"<script\b([^>]*)>", re.I)
# Ordem dos dados em todas as páginas: cada um precisa do anterior (balancas.js junta tudo)
ORDEM_DADOS = ["compartilhado/dados/empresa.js", "compartilhado/dados/ajustes.js", "compartilhado/dados/clientes.js",
               "compartilhado/dados/produtos.js", "compartilhado/js/balancas.js"]


def conferir_scripts_dados(rel, texto, completa=True):
    """A página carrega os dados na ordem empresa.js → ajustes.js → clientes.js → produtos.js → balancas.js,
    nenhum com async. completa=False (404.html): clientes.js e produtos.js são opcionais."""
    ordem = []
    for m in RE_SCRIPT.finditer(texto):
        src = re.search(r'\bsrc="([^"]+)"', m.group(1))
        if src:
            ordem.append((src.group(1).split("?")[0].lstrip("/"), m.group(1)))
    nomes = [s for s, _ in ordem]
    if "compartilhado/js/balancas.js" not in nomes:
        return False
    exigidos = ORDEM_DADOS if completa else [ORDEM_DADOS[0], ORDEM_DADOS[1], ORDEM_DADOS[4]]
    faltam = [a for a in exigidos if a not in nomes]
    if faltam:
        erro(f"{rel}: não carrega {', '.join(faltam)}. A ordem certa (em linha-verde/) é: "
             + " → ".join(a.split("/")[-1] for a in ORDEM_DADOS) + ' (<script defer src="../compartilhado/dados/ajustes.js"></script> '
             "logo depois de empresa.js).")
        return False
    pos = [nomes.index(a) for a in ORDEM_DADOS if a in nomes]
    if pos != sorted(pos):
        erro(f"{rel}: os dados estão fora de ordem; precisa ser " + " → ".join(a.split("/")[-1] for a in ORDEM_DADOS))
    for a in ORDEM_DADOS:
        if a in nomes and re.search(r"\basync\b", ordem[nomes.index(a)][1]):
            erro(f"{rel}: {a.split('/')[-1]} não pode ser async (balancas.js precisa dele antes)")
    return True


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
        if f.is_file() and (f.name in SINAIS_PRIVADOS or (f.name in PRIVADOS and f.parent.relative_to(site).as_posix() != PRIVADOS[f.name])):
            erro(f"{rel}: arquivo privado de teste no pacote")
        if f.is_file() and f.suffix == ".php" and f.read_bytes().startswith(b"<?php exit; ?>"):
            erro(f"{rel}: arquivo da pasta privada (começa com <?php exit; ?>) no pacote")
    for obrig in ("index.php", "config.php", ".htaccess", ".user.ini", "lib/.htaccess", "assets/painel.css", "assets/painel.js"):
        if not (pnl / obrig).is_file():
            erro(f"painel/{obrig} faltando no pacote")
    for pasta in PASTAS_DO_PAINEL:
        if not (site / "compartilhado" / "img" / pasta / ".htaccess").is_file():
            erro(f"compartilhado/img/{pasta}/.htaccess faltando (bloqueia scripts na pasta de fotos/logos; "
                 f"modelo em hospedagem/compartilhado/img/{pasta}/.htaccess)")

    # Dados na ordem certa em todas as páginas: empresa.js → ajustes.js → clientes.js → produtos.js → balancas.js
    for publicado, _ in PAGINAS:
        if not conferir_scripts_dados(publicado, ler(site / publicado)):
            if "balancas.js" not in ler(site / publicado):
                erro(f"{publicado}: não carrega compartilhado/js/balancas.js (nem os dados da empresa)")
    if "balancas.js" in ler(site / "404.html"):
        conferir_scripts_dados("404.html", ler(site / "404.html"), completa=False)
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
        for pasta in PASTAS_DO_PAINEL:
            regra = HOSPEDAGEM / "compartilhado" / "img" / pasta / ".htaccess"
            if regra.is_file():
                (site / "compartilhado" / "img" / pasta).mkdir(parents=True, exist_ok=True)
                shutil.copy2(regra, site / "compartilhado" / "img" / pasta / ".htaccess")
        for f in (site / "compartilhado").rglob("*"):
            if f.is_file() and f.suffix in (".js", ".css", ".html"):
                t = ler(f)
                if "../compartilhado/" in t:
                    gravar(f, t.replace("../compartilhado/", "compartilhado/"))
        resumo_catalogo = conferir_catalogo(site / "compartilhado" / "dados" / "produtos.js")
        resumo_clientes = conferir_clientes(site / "compartilhado" / "dados" / "clientes.js")
        conferir_empresa(site / "compartilhado" / "dados" / "empresa.js")
        resumo_ajustes = conferir_ajustes(site / "compartilhado" / "dados" / "ajustes.js")

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
    if resumo_clientes:
        n, sem = resumo_clientes
        print(f"  Clientes: {n} na lista (clientes.js no formato do painel ✓)"
              + (f"; {sem} sem logo (não aparecem no site)" if sem else ""))
    print("  Empresa: empresa.js em JSON ✓; ajustes.js no formato do painel ✓ ("
          + ("sem ajustes: vale empresa.js" if not resumo_ajustes else "ajustes em: " + ", ".join(resumo_ajustes)) + ")")
    print("  Ordem dos dados em todas as páginas ✓ (empresa.js → ajustes.js → clientes.js → produtos.js → balancas.js)")
    for a in avisos:
        print("  AVISO: " + a)
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


def fica_fora_da_atualizacao(rel, incluir_clientes, incluir_ajustes=False):
    """Arquivos que o painel altera no servidor (não podem ser sobrescritos por uma atualização)."""
    arquivos = {"compartilhado/dados/produtos.js"}
    pastas = ["compartilhado/img/produtos/", "compartilhado/img/produtos-recorte/"]
    if not incluir_ajustes:
        arquivos.add("compartilhado/dados/ajustes.js")
    if not incluir_clientes:
        arquivos.add("compartilhado/dados/clientes.js")
        pastas.append("compartilhado/img/clientes/")
    if rel in arquivos:
        return True
    # O .htaccess dessas pastas (bloqueia scripts) vai sempre: é igual ao do servidor
    return any(rel.startswith(p) and rel != p + ".htaccess" for p in pastas)


def compactar_atualizacao(site, incluir_clientes, incluir_ajustes=False):
    """atualizacao.zip: o site completo MENOS os dados que o painel altera no servidor."""
    site = site.resolve()
    destino = site.parent / "atualizacao.zip"
    tmp = destino.with_name(destino.name + ".tmp")
    fora = []
    with zipfile.ZipFile(tmp, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for f in sorted(site.rglob("*")):
            if not f.is_file():
                continue
            rel = f.relative_to(site).as_posix()
            if fica_fora_da_atualizacao(rel, incluir_clientes, incluir_ajustes):
                fora.append(rel)
                continue
            z.write(f, rel)
    # Confere o zip antes de trocar o anterior
    with zipfile.ZipFile(tmp) as z:
        nomes = z.namelist()
    problemas = [n for n in nomes if fica_fora_da_atualizacao(n, incluir_clientes, incluir_ajustes)]
    for obrig in ["index.html", ".htaccess", "painel/index.php", "compartilhado/js/balancas.js"] + \
                 [f"compartilhado/img/{p}/.htaccess" for p in PASTAS_DO_PAINEL]:
        if obrig not in nomes:
            problemas.append("faltou " + obrig)
    if incluir_clientes and "compartilhado/dados/clientes.js" not in nomes:
        problemas.append("faltou compartilhado/dados/clientes.js (--incluir-clientes)")
    if incluir_ajustes and "compartilhado/dados/ajustes.js" not in nomes:
        problemas.append("faltou compartilhado/dados/ajustes.js (--incluir-ajustes)")
    if not incluir_ajustes and "compartilhado/dados/ajustes.js" in nomes:
        problemas.append("compartilhado/dados/ajustes.js entrou sem --incluir-ajustes")
    if problemas:
        tmp.unlink()
        sys.exit("atualizacao.zip NÃO gerado: " + "; ".join(problemas))
    tmp.replace(destino)
    fotos = sum(1 for r in fora if r.startswith(("compartilhado/img/produtos/", "compartilhado/img/produtos-recorte/")))
    logos = sum(1 for r in fora if r.startswith("compartilhado/img/clientes/"))
    print(f"  ATUALIZAÇÃO: {destino} ({destino.stat().st_size / 1024 / 1024:.1f} MB, {len(nomes)} arquivos)")
    print(f"    fora do zip (o servidor mantém o que o painel gravou): compartilhado/dados/produtos.js, {fotos} fotos de produtos"
          + ("" if incluir_clientes else f", compartilhado/dados/clientes.js e {logos} logos de clientes")
          + ("" if incluir_ajustes else ", compartilhado/dados/ajustes.js (área Empresa)"))
    if incluir_clientes:
        print("    COM a lista de clientes (clientes.js + img/clientes/): use só se o servidor ainda não tem")
        print("    compartilhado/dados/clientes.js — senão os logos cadastrados no painel seriam substituídos.")
    if incluir_ajustes:
        print("    COM o ajustes.js (área Empresa): raramente necessário (o painel cria o vazio sozinho)")
        print("    — senão o que foi mudado no painel (telefone, horário, aviso…) volta ao original.")
        try:
            vazio = not dados_js(ler(site / "compartilhado" / "dados" / "ajustes.js"), "AJUSTES", "{")
        except ValueError:
            vazio = False
        if not vazio:
            print("    ATENÇÃO: o ajustes.js do projeto NÃO está vazio: o que estiver nele vai para o site.")
    else:
        print("    Sem ajustes.js: se o servidor ainda não tem compartilhado/dados/ajustes.js, o painel cria o vazio")
        print("    na primeira vez que alguém entrar. Até lá o site funciona, com os dados de empresa.js.")
    print("    Como instalar: docs/PUBLICAR-NO-CPANEL.md → “Atualizar o site já publicado”.")


def main():
    ap = argparse.ArgumentParser(description="Monta a pasta site/ para publicar na HostGator.")
    ap.add_argument("--saida", default=str(RAIZ / "site"), help="pasta de saída (padrão: site/ na raiz do projeto)")
    ap.add_argument("--zip", action="store_true", help="também gera <saida>.zip com o site COMPLETO, para a primeira publicação")
    ap.add_argument("--atualizacao", action="store_true",
                    help="também gera atualizacao.zip (ao lado da saída): o site completo MENOS produtos.js e as fotos "
                         "de produtos (e, sem --incluir-clientes, sem clientes.js e os logos; sem --incluir-ajustes, "
                         "sem ajustes.js), para extrair por cima do site já publicado")
    ap.add_argument("--incluir-clientes", action="store_true",
                    help="com --atualizacao: leva também compartilhado/dados/clientes.js e img/clientes/ "
                         "(só quando o servidor ainda não tem clientes.js)")
    ap.add_argument("--incluir-ajustes", action="store_true",
                    help="com --atualizacao: leva também compartilhado/dados/ajustes.js, os ajustes da área Empresa "
                         "do painel (só quando o servidor ainda não tem ajustes.js: a 1ª atualização com a área Empresa)")
    args = ap.parse_args()
    if args.incluir_clientes and not args.atualizacao:
        ap.error("--incluir-clientes só vale junto com --atualizacao")
    if args.incluir_ajustes and not args.atualizacao:
        ap.error("--incluir-ajustes só vale junto com --atualizacao")
    montar(Path(args.saida))
    if args.zip:
        compactar(Path(args.saida))
    if args.atualizacao:
        compactar_atualizacao(Path(args.saida), args.incluir_clientes, args.incluir_ajustes)


if __name__ == "__main__":
    main()
