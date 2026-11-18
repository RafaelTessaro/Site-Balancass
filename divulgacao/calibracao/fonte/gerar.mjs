// Gera as artes da campanha "Certificado de calibração" (PNG e, no Google Display, JPG leve).
// Uso (na pasta do projeto):  node divulgacao/calibracao/fonte/gerar.mjs [nome-da-arte ...]
// Precisa do Playwright com o Chromium (o mesmo das conferências do site).
// Cada arte vira também um HTML em fonte/html/ (abre no navegador para conferir ou ajustar).
import { createRequire } from 'node:module';
import { mkdirSync, writeFileSync, statSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { ARTES } from './artes.mjs';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');
const AQUI = dirname(fileURLToPath(import.meta.url));
const SAIDA = join(AQUI, '..');
const so = process.argv.slice(2);

function pagina(a) {
  return `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>${a.nome}</title>
<link rel="stylesheet" href="../fontes/fontes.css"><link rel="stylesheet" href="../banners.css">
<style>${a.css || ''}</style></head>
<body><div class="art ${a.classe || ''}" id="art" style="--w:${a.w}px;--h:${a.h}px">${a.html}</div></body></html>`;
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
  await p.evaluate(() => document.fonts.ready);
  const faltam = await p.evaluate(() => [...document.images].filter((i) => !i.complete || !i.naturalWidth).map((i) => i.src));
  if (faltam.length) throw new Error(a.nome + ': imagem não carregou: ' + faltam.join(', '));
  // Nada pode vazar da arte (texto cortado na borda)
  const vaza = await p.evaluate(() => {
    const art = document.getElementById('art').getBoundingClientRect();
    return [...document.querySelectorAll('[data-dentro]')].filter((el) => {
      const r = el.getBoundingClientRect();
      return r.left < art.left - 0.5 || r.top < art.top - 0.5 || r.right > art.right + 0.5 || r.bottom > art.bottom + 0.5;
    }).map((el) => el.className || el.tagName);
  });
  if (vaza.length) console.warn('  ! ' + a.nome + ': sai da arte → ' + vaza.join(', '));
  const destino = join(SAIDA, a.pasta, a.nome + (a.jpg ? '.jpg' : '.png'));
  mkdirSync(dirname(destino), { recursive: true });
  const el = await p.$('#art');
  await el.screenshot(a.jpg ? { path: destino, type: 'jpeg', quality: a.jpg } : { path: destino });
  const kb = Math.round(statSync(destino).size / 1024);
  console.log(`${a.pasta}/${a.nome}${a.jpg ? '.jpg' : '.png'}  ${a.w}×${a.h}  ${kb} KB${a.maxKB && kb > a.maxKB ? '  ← ACIMA DE ' + a.maxKB + ' KB' : ''}`);
  await p.close();
}
await b.close();
