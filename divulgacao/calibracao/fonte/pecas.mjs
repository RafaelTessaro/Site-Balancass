// Peças reutilizáveis das artes (HTML em texto). Usadas por artes.mjs.
// Tudo o que as artes usam está nesta pasta (fonte/img e fonte/fontes): o pacote se gera sozinho
// com Node e Playwright, sem depender do resto do repositório.
//   img/balancas-icone-claro.svg  símbolo oficial para fundo escuro (anel branco, "bq" verde)
//   img/balancas-icone.svg        símbolo oficial para fundo claro (anel cinza, "bq" verde)
//   img/balmak-one.webp           balança de bancada (Max 15 kg) — recorte do catálogo do site

export const IMG = '../img/';

let seq = 0;
const uid = (p) => `${p}${++seq}`;

/** Logo: o símbolo oficial (arquivo SVG) e o nome como no site (Big Shoulders). Sem o "Automação comercial",
 *  que fica ilegível nas artes reduzidas. tom: 'escuro' (fundo preto) ou 'claro' (fundo claro/verde). */
export const logo = (tom = 'escuro', extra = '') =>
  `<div class="lg ${extra}" data-deco><img class="lg__i" src="${IMG}${tom === 'escuro' ? 'balancas-icone-claro.svg' : 'balancas-icone.svg'}" alt=""><span class="lg__t"><b>Balanças<i>.</i>com</b></span></div>`;
export const simbolo = (tom = 'escuro', cls = 'lg__i') =>
  `<img class="${cls}" src="${IMG}${tom === 'escuro' ? 'balancas-icone-claro.svg' : 'balancas-icone.svg'}" alt="">`;

export const ICON_WA = `<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>`;
export const ICON_ARROW = `<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="square" d="M4 12h15M13 6l6 6-6 6"/></svg>`;
export const ICON_DOWN = `<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="square" d="M12 4v15M6 13l6 6 6-6"/></svg>`;

/** Peso-padrão (o mesmo desenho do site, #i-peso). rotulo: o valor gravado ("10 kg"). */
export function peso(cls = 'peso', style = '', rotulo = '20 kg') {
  const m = uid('pm'), t = uid('pt'), s = uid('ps');
  return `<svg class="${cls}" style="${style}" viewBox="0 0 120 172" aria-hidden="true" data-obj><defs>
<linearGradient id="${m}" x1="0" x2="1" y1="0" y2="0"><stop offset="0" stop-color="#4f5358"/><stop offset=".16" stop-color="#b9bdc2"/><stop offset=".3" stop-color="#f6f7f8"/><stop offset=".46" stop-color="#a7abb0"/><stop offset=".72" stop-color="#e3e5e8"/><stop offset=".9" stop-color="#8b8f94"/><stop offset="1" stop-color="#55595e"/></linearGradient>
<linearGradient id="${t}" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#f3f4f5"/><stop offset=".55" stop-color="#c3c7cb"/><stop offset="1" stop-color="#8e9297"/></linearGradient>
<radialGradient id="${s}" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#000" stop-opacity=".45"/><stop offset="1" stop-color="#000" stop-opacity="0"/></radialGradient></defs>
<ellipse cx="60" cy="160" rx="62" ry="12" fill="url(#${s})"/><path d="M10 66V150A50 11 0 0 0 110 150V66Z" fill="url(#${m})"/><ellipse cx="60" cy="66" rx="50" ry="11" fill="url(#${t})"/><ellipse cx="60" cy="66" rx="50" ry="11" fill="none" stroke="#fff" stroke-opacity=".5" stroke-width=".8"/><path d="M42 44V66A18 4.2 0 0 0 78 66V44Z" fill="url(#${m})"/><ellipse cx="60" cy="66.2" rx="18" ry="4.2" fill="#000" fill-opacity=".18"/><path d="M33 44C33 15 87 15 87 44A27 6.5 0 0 1 33 44Z" fill="url(#${m})"/><ellipse cx="60" cy="44" rx="27" ry="6.5" fill="url(#${t})" fill-opacity=".55"/><path d="M44 30C47 22 56 20 62 21" fill="none" stroke="#fff" stroke-opacity=".85" stroke-width="3" stroke-linecap="round"/><text x="60" y="120" text-anchor="middle" font-family="Martian Mono, monospace" font-size="15" font-weight="500" fill="#2a2d31" fill-opacity=".55">${rotulo}</text></svg>`;
}

/** O visual principal: a balança de bancada (Max 15 kg) com um peso-padrão de 10 kg no prato.
 *  style: posição e largura do conjunto (a altura sai da proporção da foto, 900×569). */
export const balPeso = (style = '') =>
  `<div class="bp" style="${style}" data-obj><img class="bp__b" src="${IMG}balmak-one.webp" alt="">${peso('bp__p', '', '10 kg').replace(' data-obj', '')}</div>`;

/** Certificado de EXEMPLO (o mesmo do site): faixa "Exemplo ilustrativo · sem valor de certificado",
 *  marca d'água, sem número, sem resultados (barras) e sem assinatura desenhada. --c: tamanho. */
export function cert(style = '') {
  const bar = (w) => `<i style="--w:${w}%"></i>`;
  const linhas = [[40, 56, 44, 50], [52, 64, 38, 50], [58, 70, 48, 54], [62, 72, 48, 58]]
    .map((r) => `<tr>${r.map((w) => `<td>${bar(w)}</td>`).join('')}</tr>`).join('');
  return `<div class="cert" style="${style}" data-obj>
  <p class="cert__ex">Exemplo ilustrativo · sem valor de certificado</p>
  <div class="cert__top"><span>Balanças.com · Calibração</span><span>Pág. 1/2</span></div>
  <div class="cert__head">${simbolo('claro', 'cert__logo')}<p class="cert__t">Certificado de calibração</p><p class="cert__n">Modelo ilustrativo</p></div>
  <dl class="cert__id"><div><dt>Instrumento</dt><dd>${bar(70)}</dd></div><div><dt>Capacidade</dt><dd>${bar(46)}</dd></div><div><dt>Método</dt><dd>EURAMET cg-18</dd></div></dl>
  <table class="cert__tb"><thead><tr><th>Carga</th><th>Indicação</th><th>Erro</th><th>U (k=2)</th></tr></thead><tbody>${linhas}</tbody></table>
  <div class="cert__foot"><ul class="cert__ok"><li>Repetibilidade</li><li>Excentricidade</li><li>Rastreabilidade</li></ul><span class="cert__pdf">PDF assinado digitalmente</span></div>
  <span class="cert__wm">Exemplo</span>
</div>`;
}

/** Contato (essencial): WhatsApp e site, em Archivo, legível na prévia reduzida. */
export const rodape = (style = '', { site = true } = {}) =>
  `<div class="ft" style="${style}" data-dentro><span class="wa">${ICON_WA}WhatsApp <b>(19) 3023-9050</b></span>${site ? '<span><b>balancass.com</b></span>' : ''}</div>`;

/** Condição de não acreditação (essencial quando a peça fala de auditoria). */
export const RESSALVA = 'Serviço de calibração não acreditado pela Cgcre. Consulte a adequação aos requisitos da sua empresa.';
export const RESSALVA_CURTA = 'Serviço não acreditado pela Cgcre.';
