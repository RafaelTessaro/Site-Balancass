// Gera as artes da campanha de calibração (PNG; no Google Display e na prévia do site, JPG leve).
// Uso (na pasta do projeto):  node divulgacao/calibracao/fonte/gerar.mjs [nome-da-arte ...]
// Precisa de Node 18+ e do Playwright com o Chromium (npm i playwright && npx playwright install chromium).
// Tudo o que as artes usam está em fonte/ (img/ e fontes/). Cada arte vira também um HTML em fonte/html/.
//
// Conferências (qualquer falha faz o comando terminar com erro, depois de gerar o resto):
//   - as três fontes e todas as imagens carregaram;
//   - nenhum texto marcado (data-dentro: essencial; data-deco: identificador) sai da arte nem é cortado
//     pelo bloco em que está (seção com overflow escondido);
//   - nenhum texto encosta em outro texto nem no visual (data-obj: balança, peso, certificado);
//   - todo texto essencial (data-dentro) tem pelo menos o tamanho mínimo da arte (min, em px);
//   - nenhum texto fica sobre a faixa diagonal (.band), com uma folga, salvo o que tem fundo próprio
//     (rodapé, botões, etiquetas): mede cada linha de texto contra o paralelogramo da faixa;
//   - o arquivo não passa do limite (maxKB), quando há um.
// Não substitui olhar as artes: os textos de dentro do certificado ilustrativo, por exemplo, não têm
// tamanho mínimo (por isso ele leva a legenda externa "Modelo ilustrativo de certificado").
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
const registro = [];   // vai para ultima-geracao.txt (geração completa)

function pagina(a) {
  return `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>${a.nome}</title>
<link rel="stylesheet" href="../fontes/fontes.css"><link rel="stylesheet" href="../banners.css">
<style>${a.pdf ? `@page { size: ${a.w}px ${a.h}px; margin: 0 } html, body { background: none }` : ''}${a.css || ''}</style></head>
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
    // Cortado por uma seção (um bloco com overflow escondido, como as seções da lâmina)?
    for (let e = el.parentElement; e && e.id !== 'art'; e = e.parentElement) {
      if (getComputedStyle(e).overflow === 'visible') continue;
      const s = r(e);
      if (q.left < s.left - 0.5 || q.top < s.top - 0.5 || q.right > s.right + 0.5 || q.bottom > s.bottom + 0.5) erros.push(`texto cortado pelo bloco: ${nome(el)}`);
      break;
    }
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
  // Faixa diagonal: o paralelogramo de cada .band (skewX em torno do centro: x' = x + c·(y − centro))
  const faixas = [...document.querySelectorAll('.band')].map((b) => {
    const c = new DOMMatrix(getComputedStyle(b).transform).c;
    // A faixa pode estar dentro de uma seção (a lâmina): conta a partir da seção e corta no limite dela
    const pai = b.offsetParent || document.getElementById('art'), pr = pai.getBoundingClientRect();
    const corta = getComputedStyle(pai).overflow !== 'visible';
    return { pai, L: pr.left + b.offsetLeft, R: pr.left + b.offsetLeft + b.offsetWidth, cy: pr.top + b.offsetTop + b.offsetHeight / 2, c,
      y0: corta ? pr.top : -Infinity, y1: corta ? pr.bottom : Infinity };
  });
  const folgaFaixa = Math.max(4, art.width * 0.008);
  const naFaixa = (q, f) => {   // existe y entre o topo e a base da linha com a linha dentro da faixa?
    const tl = q.left - folgaFaixa, tr = q.right + folgaFaixa, tt = Math.max(q.top, f.y0), tb = Math.min(q.bottom, f.y1);
    if (tt >= tb) return false;
    if (Math.abs(f.c) < 1e-6) return tr > f.L && tl < f.R;
    // borda esquerda: L + c·(y − cy) < tr ; borda direita: R + c·(y − cy) > tl
    const y1 = f.cy + (tr - f.L) / f.c, y2 = f.cy + (tl - f.R) / f.c;
    return f.c < 0 ? Math.max(tt, y1) < Math.min(tb, y2) : Math.max(tt, y2) < Math.min(tb, y1);
  };
  const temFundo = (el, limite) => {   // o texto está sobre um fundo próprio (rodapé, botão, etiqueta), na frente da faixa?
    for (let e = el; e && e !== limite && e.id !== 'art'; e = e.parentElement) {   // o fundo da própria seção da faixa não conta
      const cs = getComputedStyle(e), antes = getComputedStyle(e, '::before');
      if (cs.backgroundColor !== 'rgba(0, 0, 0, 0)' && cs.backgroundColor !== 'transparent') return true;
      if (antes.content !== 'none' && antes.backgroundColor !== 'rgba(0, 0, 0, 0)' && antes.position === 'absolute') return true;
    }
    return false;
  };
  if (faixas.length) for (const el of textos) {
    const andar = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
    for (let n = andar.nextNode(); n; n = andar.nextNode()) {
      if (!n.textContent.trim()) continue;
      const rg = document.createRange(); rg.selectNodeContents(n);
      if ([...rg.getClientRects()].some((q) => faixas.some((f) => naFaixa(q, f) && !temFundo(n.parentElement, f.pai)))) {
        erros.push(`texto sobre a faixa verde: "${n.textContent.trim().slice(0, 40)}"`); break;
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
  // Versão em PDF (texto de verdade, nítido em qualquer zoom), quando a arte pede
  if (a.pdf) await p.pdf({ path: destino.replace(/\.(png|jpg)$/, '.pdf'), width: a.w + 'px', height: a.h + 'px', printBackground: true, preferCSSPageSize: true, pageRanges: '1' });
  const kb = Math.round(statSync(destino).size / 1024);
  if (a.maxKB && kb > a.maxKB) erros.push(`arquivo com ${kb} KB (limite ${a.maxKB} KB)`);
  const linha = `${erros.length ? '✗' : '✓'} ${a.pasta}/${a.nome}${a.jpg ? '.jpg' : '.png'}  ${a.w}×${a.h}  ${kb} KB`;
  console.log(linha); registro.push(linha, ...erros.map((e) => '    - ' + e));
  for (const e of erros) { console.log('    - ' + e); falhas.push(a.nome + ': ' + e); }
  await p.close();
}
const versaoChromium = b.version();
await b.close();
if (!so.length) {
  galeria();
  // Registro da última geração completa: versões usadas e o resultado de cada arte
  const versaoPw = require('playwright/package.json').version;
  writeFileSync(join(AQUI, 'ultima-geracao.txt'), [
    `Geração completa em ${new Date().toISOString()}`,
    `Node ${process.version} · Playwright ${versaoPw} · Chromium ${versaoChromium}`,
    `${registro.filter((l) => /^[✓✗]/.test(l)).length} artes · ${falhas.length} problema(s)`,
    '', ...registro, '',
  ].join('\n'));
}
if (falhas.length) { console.error(`\n${falhas.length} problema(s): corrija antes de usar as artes.`); process.exit(1); }
console.log('\nTodas as conferências passaram. Olhe as artes mesmo assim: composição, recortes e cores não são medidos.');

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
<body><h1>Calibração <span>de balanças</span></h1><p>Revisão 3 das artes. Clique numa arte para abrir no tamanho real. Os textos de cada anúncio, as regras de texto e os dados a confirmar estão em <a href="TEXTOS-DOS-ANUNCIOS.md">TEXTOS-DOS-ANUNCIOS.md</a>. O certificado que aparece nas artes é um exemplo ilustrativo, sem valor de certificado.</p>${secoes}</body></html>`);
  console.log('ver-todas.html atualizado');
}
