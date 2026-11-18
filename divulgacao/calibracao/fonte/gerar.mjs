// Gera as artes da campanha de calibração (PNG; no Google Display e na prévia do site, JPG leve).
// Uso (na pasta do projeto):  node divulgacao/calibracao/fonte/gerar.mjs [nome-da-arte ...]
// Precisa de Node 18+ e do Playwright com o Chromium (npm i playwright && npx playwright install chromium).
// Tudo o que as artes usam está em fonte/ (img/ e fontes/). Cada arte vira também um HTML em fonte/html/.
//
// Conferências (qualquer falha faz o comando terminar com erro, depois de gerar o resto):
//   - as três fontes e todas as imagens carregaram;
//   - nenhum texto marcado (data-dentro: essencial; data-deco: identificador) sai da arte;
//   - nenhum texto encosta em outro texto nem no visual (data-obj: balança, peso, certificado);
//   - todo texto essencial (data-dentro) tem pelo menos o tamanho mínimo da arte (min, em px);
//   - o arquivo não passa do limite (maxKB), quando há um.
// Não substitui olhar as artes: a sobreposição com a faixa verde inclinada, por exemplo, não é medida.
import { createRequire } from 'node:module';
import { mkdirSync, writeFileSync, statSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { ARTES, GRUPOS } from './artes.mjs';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');
const AQUI = dirname(fileURLToPath(import.meta.url));
const SAIDA = join(AQUI, '..');
const so = process.argv.slice(2);
const falhas = [];

function pagina(a) {
  return `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>${a.nome}</title>
<link rel="stylesheet" href="../fontes/fontes.css"><link rel="stylesheet" href="../banners.css">
<style>${a.css || ''}</style></head>
<body><div class="art ${a.classe || ''}" id="art" style="--w:${a.w}px;--h:${a.h}px">${a.html}</div></body></html>`;
}

function conferir(min) {
  const art = document.getElementById('art').getBoundingClientRect();
  const r = (el) => el.getBoundingClientRect();
  const nome = (el) => (el.className && el.className.baseVal !== undefined ? el.className.baseVal : el.className) || el.tagName;
  const cruza = (a, b, folga = 1) => a.left < b.right - folga && b.left < a.right - folga && a.top < b.bottom - folga && b.top < a.bottom - folga;
  const textos = [...document.querySelectorAll('[data-dentro], [data-deco]')];
  const objs = [...document.querySelectorAll('[data-obj]')];
  const erros = [];
  for (const el of textos) {
    const q = r(el);
    if (q.left < art.left - 0.5 || q.top < art.top - 0.5 || q.right > art.right + 0.5 || q.bottom > art.bottom + 0.5) erros.push(`sai da arte: ${nome(el)}`);
    for (const o of objs) if (cruza(q, r(o))) erros.push(`texto encosta no visual: ${nome(el)} × ${nome(o)}`);
  }
  for (let i = 0; i < textos.length; i++) for (let j = i + 1; j < textos.length; j++) {
    if (textos[i].contains(textos[j]) || textos[j].contains(textos[i])) continue;
    if (cruza(r(textos[i]), r(textos[j]))) erros.push(`textos encostados: ${nome(textos[i])} × ${nome(textos[j])}`);
  }
  if (min) {
    for (const el of document.querySelectorAll('[data-dentro]')) {
      const andar = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
      for (let n = andar.nextNode(); n; n = andar.nextNode()) {
        if (!n.textContent.trim()) continue;
        const fs = parseFloat(getComputedStyle(n.parentElement).fontSize);
        if (fs < min - 0.01) { erros.push(`letra pequena (${fs}px < ${min}px): "${n.textContent.trim().slice(0, 40)}"`); break; }
      }
    }
  }
  for (const f of ['900 20px "Big Shoulders"', '400 20px "Archivo"', '400 20px "Martian Mono"'])
    if (!document.fonts.check(f)) erros.push('fonte não carregou: ' + f);
  return erros;
}

mkdirSync(join(AQUI, 'html'), { recursive: true });
const b = await chromium.launch();
const ctx = await b.newContext({ deviceScaleFactor: 1 });
for (const a of ARTES) {
  if (so.length && !so.includes(a.nome)) continue;
  const html = join(AQUI, 'html', a.nome + '.html');
  writeFileSync(html, pagina(a));
  const p = await ctx.newPage();
  await p.setViewportSize({ width: a.w, height: a.h });
  await p.goto(pathToFileURL(html).href, { waitUntil: 'networkidle' });
  // As fontes da web só descem quando algum texto as usa: pede as três antes de conferir
  await p.evaluate(() => Promise.all(['900 20px "Big Shoulders"', '400 20px "Archivo"', '400 20px "Martian Mono"'].map((f) => document.fonts.load(f))));
  await p.evaluate(() => document.fonts.ready);
  const faltam = await p.evaluate(() => [...document.images].filter((i) => !i.complete || !i.naturalWidth).map((i) => i.src));
  const erros = faltam.map((s) => 'imagem não carregou: ' + s).concat(await p.evaluate(conferir, a.min || 0));
  const destino = join(SAIDA, a.pasta, a.nome + (a.jpg ? '.jpg' : '.png'));
  mkdirSync(dirname(destino), { recursive: true });
  const el = await p.$('#art');
  await el.screenshot(a.jpg ? { path: destino, type: 'jpeg', quality: a.jpg } : { path: destino });
  const kb = Math.round(statSync(destino).size / 1024);
  if (a.maxKB && kb > a.maxKB) erros.push(`arquivo com ${kb} KB (limite ${a.maxKB} KB)`);
  console.log(`${erros.length ? '✗' : '✓'} ${a.pasta}/${a.nome}${a.jpg ? '.jpg' : '.png'}  ${a.w}×${a.h}  ${kb} KB`);
  for (const e of erros) { console.log('    - ' + e); falhas.push(a.nome + ': ' + e); }
  await p.close();
}
await b.close();
if (!so.length) galeria();
if (falhas.length) { console.error(`\n${falhas.length} problema(s): corrija antes de usar as artes.`); process.exit(1); }
console.log('\nTodas as conferências passaram. Olhe as artes mesmo assim (a faixa verde não é medida).');

// Galeria (ver-todas.html): todas as artes, separadas pelo uso
function galeria() {
  const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;');
  const secoes = GRUPOS.map(([titulo, nota, filtro]) => {
    const itens = ARTES.filter(filtro).map((a) => {
      const arq = `${a.pasta}/${a.nome}${a.jpg ? '.jpg' : '.png'}`;
      const kb = Math.round(statSync(join(SAIDA, arq)).size / 1024);
      return `<figure><a href="${arq}" target="_blank"><img src="${arq}" width="${a.w}" height="${a.h}" loading="lazy" alt=""></a><figcaption><b>${esc(a.nome)}</b><span>${a.w}×${a.h} · ${kb} KB</span></figcaption></figure>`;
    }).join('');
    return `<section><h2>${titulo}</h2>${nota ? `<p class="n">${nota}</p>` : ''}<div class="g">${itens}</div></section>`;
  }).join('');
  writeFileSync(join(SAIDA, 'ver-todas.html'), `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Artes · Calibração de balanças</title>
<link rel="stylesheet" href="fonte/fontes/fontes.css">
<style>body{margin:0;background:#0A0A0A;color:#F4F4F0;font-family:Archivo,Arial,sans-serif;padding:32px 20px 60px}h1{font:900 56px/.9 "Big Shoulders",sans-serif;text-transform:uppercase;margin:0 0 10px}h1 span{color:#019F42}p{color:#D6D6D0;max-width:62em;line-height:1.5}
h2{font:500 13px/1 "Martian Mono",monospace;letter-spacing:.05em;text-transform:uppercase;color:#C6FF3D;margin:40px 0 10px;padding-top:16px;border-top:1px solid #2B2B2A}.n{margin:0 0 16px;font-size:15px}
.g{display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start}figure{margin:0;max-width:340px}figure img{display:block;max-width:100%;height:auto;max-height:420px;width:auto;background:#222}
figcaption{display:grid;gap:2px;margin-top:8px;font:400 11px/1.4 "Martian Mono",monospace;color:#8A8A85}figcaption b{color:#F4F4F0;font-weight:500;word-break:break-all}a{color:inherit}</style></head>
<body><h1>Calibração <span>de balanças</span></h1><p>Revisão 2 das artes. Clique numa arte para abrir no tamanho real. Os textos de cada anúncio, as regras de texto e os dados a confirmar estão em <a href="TEXTOS-DOS-ANUNCIOS.md">TEXTOS-DOS-ANUNCIOS.md</a>. O certificado que aparece nas artes é um exemplo ilustrativo, sem valor de certificado.</p>${secoes}</body></html>`);
  console.log('ver-todas.html atualizado');
}
