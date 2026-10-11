#!/usr/bin/env node
/* =====================================================================
   converter-produtos.mjs — conversão ÚNICA do catálogo para o formato
   que o painel (painel/) lê e grava.
   ---------------------------------------------------------------------
   O compartilhado/dados/produtos.js antigo é JavaScript escrito à mão
   (chaves sem aspas, comentários). O painel em PHP só lê o formato novo:

       /* comentário de cabeçalho * /
       window.CATEGORIAS = <JSON>;
       window.PRODUTOS = <JSON>;

   Este script lê o arquivo antigo (executando-o numa caixa de areia do
   Node, sem acesso a nada), preserva TODOS os campos e a ordem deles
   (inclusive os opcionais, como visivel e recorte), transforma os
   comentários de linha que estão dentro de um produto no campo "nota"
   (anotação interna; não aparece nas páginas) e grava o formato novo.
   Antes de gravar, confere que o resultado tem exatamente os mesmos
   dados do original.

   Uso:
     node ferramentas/converter-produtos.mjs ENTRADA [SAIDA]
       ENTRADA  produtos.js atual
       SAIDA    onde gravar (padrão: sobrescreve a ENTRADA)
     --verificar   só confere e mostra o resumo, sem gravar nada

   Pode ser rodado de novo no arquivo já convertido: ele só reescreve no
   formato padrão (nada muda).
   ===================================================================== */
import fs from "node:fs";
import path from "node:path";
import vm from "node:vm";
import assert from "node:assert/strict";

const args = process.argv.slice(2);
const soVerificar = args.includes("--verificar");
const pos = args.filter((a) => !a.startsWith("--"));
if (pos.length < 1 || pos.length > 2) {
  console.error("Uso: node ferramentas/converter-produtos.mjs ENTRADA [SAIDA] [--verificar]");
  process.exit(2);
}
const entrada = path.resolve(pos[0]);
const saida = path.resolve(pos[1] || pos[0]);
const fonte = fs.readFileSync(entrada, "utf8").replace(/^﻿/, "");

/* ------------------------------------------------------------------
   Formato novo — idêntico ao que o painel (painel/lib/catalogo.php) grava
   ------------------------------------------------------------------ */
function cabecalho(linhaData) {
  return [
    "/* =====================================================================",
    "   CATÁLOGO DE PRODUTOS — Balanças.com",
    "   ---------------------------------------------------------------------",
    "   Arquivo GERADO pelo painel de administração do site. Para adicionar,",
    "   editar, esconder ou excluir produtos e trocar fotos, use o painel:",
    "   ele confere os dados, guarda cópias de segurança e não deixa o",
    "   catálogo quebrar. O que for editado à mão aqui some na próxima vez",
    "   que o painel salvar.",
    "",
    "   Formato: JSON puro dentro de window.CATEGORIAS e window.PRODUTOS.",
    "   Campos de cada produto: id, nome, marca, categoria, subcategoria,",
    "   resumo, descricao, especificacoes ([[\"Nome\", \"Valor\"], ...]),",
    "   imagem (em compartilhado/img/produtos/), recorte (opcional, em",
    "   compartilhado/img/produtos-recorte/), estoque (consulte | disponivel |",
    "   encomenda | esgotado), condicao (novo | seminovo), preco (\"\" = Consulte),",
    "   destaque, visivel e nota (anotação interna, não aparece nas páginas).",
    "   " + linhaData,
    "   ===================================================================== */",
    ""
  ].join("\n");
}

// Mesmo resultado de json_encode(..., JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE |
// JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) do PHP.
function jsonPhp(valor) {
  return JSON.stringify(valor, null, 4)
    .replace(/</g, "\\u003C")
    .replace(/>/g, "\\u003E")
    .replace(/&/g, "\\u0026")
    .replace(/\u2028/g, "\\u2028")
    .replace(/\u2029/g, "\\u2029");
}

function montar(categorias, produtos, linhaData) {
  return cabecalho(linhaData) +
    "window.CATEGORIAS = " + jsonPhp(categorias) + ";\n" +
    "window.PRODUTOS = " + jsonPhp(produtos) + ";\n";
}

/* Lê o formato novo sem executar nada: acha o JSON depois de "window.X =". */
function jsonDepois(src, nome) {
  const m = new RegExp("(^|\\n)window\\." + nome + "\\s*=\\s*").exec(src);
  if (!m) return undefined;
  let i = m.index + m[0].length, ini = i, prof = 0, str = false;
  for (; i < src.length; i++) {
    const c = src[i];
    if (str) {
      if (c === "\\") i++;
      else if (c === '"') str = false;
      continue;
    }
    if (c === '"') str = true;
    else if (c === "[" || c === "{") prof++;
    else if (c === "]" || c === "}") { prof--; if (prof === 0) return JSON.parse(src.slice(ini, i + 1)); }
  }
  throw new Error("JSON de window." + nome + " não termina");
}
function lerFormatoNovo(src) {
  try {
    const cats = jsonDepois(src, "CATEGORIAS"), prods = jsonDepois(src, "PRODUTOS");
    if (Array.isArray(cats) && Array.isArray(prods)) return { categorias: cats, produtos: prods };
  } catch (e) { /* não é o formato novo */ }
  return null;
}

/* Lê o formato antigo executando o arquivo numa caixa de areia (só "window"). */
function lerFormatoAntigo(src) {
  const janela = {};
  vm.runInNewContext(src, { window: janela }, { filename: path.basename(entrada), timeout: 2000 });
  if (!Array.isArray(janela.CATEGORIAS) || !Array.isArray(janela.PRODUTOS)) {
    throw new Error("o arquivo não define window.CATEGORIAS e window.PRODUTOS");
  }
  // Copia "limpa" (objetos do contexto do vm → objetos normais), preservando a ordem das chaves
  return JSON.parse(JSON.stringify({ categorias: janela.CATEGORIAS, produtos: janela.PRODUTOS }));
}

/* Comentários de linha ("// ...") dentro de cada produto viram o campo "nota". */
function notasDosComentarios(src) {
  const notas = {}, descartados = [];
  const inicioProdutos = src.search(/window\.PRODUTOS\s*=/);
  if (inicioProdutos < 0) return { notas, descartados };
  // Percorre o código marcando strings, para não confundir "//" dentro de texto (ex.: URLs)
  let i = 0, str = null, idAtual = null;
  const re = /\bid\s*:\s*(["'])((?:\\.|(?!\1).)*)\1/y;
  while (i < src.length) {
    const c = src[i];
    if (str) {
      if (c === "\\") i += 2;
      else { if (c === str) str = null; i++; }
      continue;
    }
    if (i >= inicioProdutos) {
      re.lastIndex = i;
      const m = re.exec(src);
      if (m) { idAtual = m[2]; i = re.lastIndex; continue; }
    }
    if (c === '"' || c === "'" || c === "`") { str = c; i++; continue; }
    if (c === "/" && src[i + 1] === "*") {
      const fim = src.indexOf("*/", i + 2);
      const txt = src.slice(i + 2, fim < 0 ? src.length : fim).replace(/^[\s=*-]+|[\s=*-]+$/g, "").trim();
      if (i > inicioProdutos && txt) descartados.push(txt);
      i = fim < 0 ? src.length : fim + 2;
      continue;
    }
    if (c === "/" && src[i + 1] === "/") {
      const fim = src.indexOf("\n", i);
      const txt = src.slice(i + 2, fim < 0 ? src.length : fim).trim();
      if (i > inicioProdutos && idAtual && txt) (notas[idAtual] = notas[idAtual] || []).push(txt);
      else if (txt) descartados.push(txt);
      i = fim < 0 ? src.length : fim;
      continue;
    }
    i++;
  }
  return { notas, descartados };
}

/* Mesmas regras que o painel usa ao gravar (avisos, não impedem a conversão). */
function avisos(categorias, produtos) {
  const out = [], ids = new Set();
  const cat = Object.fromEntries(categorias.map((c) => [c.id, c]));
  const ENUM = { estoque: ["consulte", "disponivel", "encomenda", "esgotado"], condicao: ["novo", "seminovo"] };
  produtos.forEach((p, n) => {
    const q = "produto " + (n + 1) + " (" + (p && p.id) + ")";
    if (!p || typeof p !== "object") return out.push(q + ": não é um objeto");
    if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(p.id || "")) out.push(q + ": id fora do padrão [a-z0-9-]");
    if (ids.has(p.id)) out.push(q + ": id repetido"); ids.add(p.id);
    for (const k of ["nome", "marca", "categoria", "subcategoria", "imagem"]) if (!p[k]) out.push(q + ": falta " + k);
    if (p.categoria && !cat[p.categoria]) out.push(q + ": categoria desconhecida " + p.categoria);
    else if (p.subcategoria && !cat[p.categoria].subcategorias.includes(p.subcategoria)) out.push(q + ": subcategoria desconhecida " + p.subcategoria);
    for (const k of Object.keys(ENUM)) if (p[k] != null && !ENUM[k].includes(p[k])) out.push(q + ": " + k + " inválido " + p[k]);
    for (const k of ["imagem", "recorte"]) if (p[k] != null && !/^[A-Za-z0-9][A-Za-z0-9._-]{0,120}\.(webp|jpe?g|png)$/i.test(p[k])) out.push(q + ": " + k + " com nome de arquivo inválido");
    if (!Array.isArray(p.especificacoes || [])) out.push(q + ": especificacoes não é lista");
  });
  return out;
}

/* ------------------------------------------------------------------ */
let dados, origem;
const novo = lerFormatoNovo(fonte);
if (novo) {
  dados = novo; origem = "formato novo (já convertido)";
} else {
  dados = lerFormatoAntigo(fonte); origem = "formato antigo (JavaScript à mão)";
  const { notas, descartados } = notasDosComentarios(fonte);
  const ids = new Set(dados.produtos.map((p) => p.id));
  for (const [id, lista] of Object.entries(notas)) {
    if (!ids.has(id)) continue;
    const p = dados.produtos.find((x) => x.id === id);
    const txt = lista.join(" · ");
    p.nota = p.nota ? p.nota + " · " + txt : txt;
    console.log("  nota em " + id + ': "' + txt + '"');
  }
  if (descartados.length) console.log("  comentários de organização descartados (não cabem em JSON): " + descartados.map((t) => '"' + t + '"').join(", "));
}

const agora = new Date();
const dois = (n) => String(n).padStart(2, "0");
const quando = dois(agora.getDate()) + "/" + dois(agora.getMonth() + 1) + "/" + agora.getFullYear() + " " + dois(agora.getHours()) + ":" + dois(agora.getMinutes());
const texto = montar(dados.categorias, dados.produtos, "Convertido para o formato do painel em " + quando + ".");

// Conferência: o arquivo novo, executado como o site executa, tem exatamente os mesmos dados
const janela = {};
vm.runInNewContext(texto, { window: janela }, { timeout: 2000 });
assert.deepEqual(JSON.parse(JSON.stringify(janela.CATEGORIAS)), dados.categorias, "CATEGORIAS diferentes depois da conversão");
assert.deepEqual(JSON.parse(JSON.stringify(janela.PRODUTOS)), dados.produtos, "PRODUTOS diferentes depois da conversão");
assert.deepEqual(lerFormatoNovo(texto), dados, "o formato novo não é lido de volta");
if (!novo) {
  // Tudo o que existia no original continua igual (o único acréscimo permitido é "nota")
  const orig = lerFormatoAntigo(fonte);
  assert.deepEqual(dados.categorias, orig.categorias);
  assert.equal(dados.produtos.length, orig.produtos.length);
  dados.produtos.forEach((p, i) => {
    const sem = Object.fromEntries(Object.entries(p).filter(([k]) => k !== "nota" || "nota" in orig.produtos[i]));
    assert.deepEqual(sem, orig.produtos[i], "produto " + p.id + " mudou");
    assert.deepEqual(Object.keys(sem), Object.keys(orig.produtos[i]), "ordem dos campos mudou em " + p.id);
  });
}

const av = avisos(dados.categorias, dados.produtos);
const ocultos = dados.produtos.filter((p) => p.visivel === false).length;
console.log("Origem: " + origem);
console.log("Categorias: " + dados.categorias.length + " · Produtos: " + dados.produtos.length + " (" + ocultos + " ocultos)");
console.log("Campos opcionais: " + ["visivel", "recorte", "nota"].map((k) => k + "=" + dados.produtos.filter((p) => k in p).length).join(", "));
if (av.length) { console.log("AVISOS (o painel vai pedir correção ao editar estes produtos):"); av.forEach((a) => console.log("  - " + a)); }
else console.log("Validação: tudo dentro das regras do painel.");

if (soVerificar) {
  console.log("--verificar: nada foi gravado.");
} else {
  const tmp = saida + ".tmp-" + process.pid;
  fs.writeFileSync(tmp, texto, { encoding: "utf8", mode: 0o644 });
  fs.renameSync(tmp, saida);
  console.log("Gravado: " + saida + " (" + Buffer.byteLength(texto) + " bytes)");
}
