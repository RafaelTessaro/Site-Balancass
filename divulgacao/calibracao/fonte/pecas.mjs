// Peças reutilizáveis das artes (HTML em texto). Usadas por artes.mjs.
// Imagens: os recortes de produtos do site (compartilhado/img/produtos-recorte).

export const IMG = '../../../../compartilhado/img/produtos-recorte/';

let seq = 0;
const uid = (p) => `${p}${++seq}`;

/** Símbolo da marca (o mesmo do sprite do site). O anel usa currentColor; o "bq" é sempre verde. */
export function simbolo(cls = 'lg__i') {
  const b = uid('b'), q = uid('q'), m = uid('m');
  return `<svg class="${cls}" viewBox="-100 -100 200 200" aria-hidden="true"><defs>
<path id="${b}" d="M -30 -91.2 L -30 0 A 30 30 0 0 0 26 15 L 26 56.3 A 62 62 0 0 1 -62 0 L -62 -73.3 A 96 96 0 0 1 -30 -91.2 Z"/>
<path id="${q}" d="M 30 91.2 L 30 0 A 30 30 0 0 0 -26 -15 L -26 -56.3 A 62 62 0 0 1 62 0 L 62 73.3 A 96 96 0 0 1 30 91.2 Z"/>
<mask id="${m}" maskUnits="userSpaceOnUse" x="-100" y="-100" width="200" height="200"><rect x="-100" y="-100" width="200" height="200" fill="#fff"/><circle r="34" fill="#000"/><use href="#${b}" fill="#000" stroke="#000" stroke-width="8" stroke-linejoin="round"/><use href="#${q}" fill="#000" stroke="#000" stroke-width="8" stroke-linejoin="round"/></mask></defs>
<circle r="100" fill="currentColor" mask="url(#${m})"/><use href="#${b}" fill="#019F42"/><use href="#${q}" fill="#019F42"/></svg>`;
}

export const logo = (extra = '') =>
  `<div class="lg ${extra}">${simbolo()}<span class="lg__t"><b>Balanças<i>.</i>com</b><small>Automação comercial</small></span></div>`;

export const ICON_WA = `<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>`;
export const ICON_ARROW = `<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="square" d="M4 12h15M13 6l6 6-6 6"/></svg>`;

/** Peso-padrão (o mesmo desenho do site, #i-peso). */
export function peso(cls = 'peso', style = '') {
  const m = uid('pm'), t = uid('pt'), s = uid('ps');
  return `<svg class="${cls}" style="${style}" viewBox="0 0 120 172" aria-hidden="true"><defs>
<linearGradient id="${m}" x1="0" x2="1" y1="0" y2="0"><stop offset="0" stop-color="#4f5358"/><stop offset=".16" stop-color="#b9bdc2"/><stop offset=".3" stop-color="#f6f7f8"/><stop offset=".46" stop-color="#a7abb0"/><stop offset=".72" stop-color="#e3e5e8"/><stop offset=".9" stop-color="#8b8f94"/><stop offset="1" stop-color="#55595e"/></linearGradient>
<linearGradient id="${t}" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#f3f4f5"/><stop offset=".55" stop-color="#c3c7cb"/><stop offset="1" stop-color="#8e9297"/></linearGradient>
<radialGradient id="${s}" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#000" stop-opacity=".55"/><stop offset="1" stop-color="#000" stop-opacity="0"/></radialGradient></defs>
<ellipse cx="60" cy="160" rx="62" ry="12" fill="url(#${s})"/><path d="M10 66V150A50 11 0 0 0 110 150V66Z" fill="url(#${m})"/><ellipse cx="60" cy="66" rx="50" ry="11" fill="url(#${t})"/><ellipse cx="60" cy="66" rx="50" ry="11" fill="none" stroke="#fff" stroke-opacity=".5" stroke-width=".8"/><path d="M42 44V66A18 4.2 0 0 0 78 66V44Z" fill="url(#${m})"/><ellipse cx="60" cy="66.2" rx="18" ry="4.2" fill="#000" fill-opacity=".18"/><path d="M33 44C33 15 87 15 87 44A27 6.5 0 0 1 33 44Z" fill="url(#${m})"/><ellipse cx="60" cy="44" rx="27" ry="6.5" fill="url(#${t})" fill-opacity=".55"/><path d="M44 30C47 22 56 20 62 21" fill="none" stroke="#fff" stroke-opacity=".85" stroke-width="3" stroke-linecap="round"/><text x="60" y="118" text-anchor="middle" font-family="Martian Mono, monospace" font-size="15" font-weight="500" fill="#2a2d31" fill-opacity=".55">20 kg</text><text x="60" y="134" text-anchor="middle" font-family="Martian Mono, monospace" font-size="7.5" fill="#2a2d31" fill-opacity=".45" letter-spacing="1">M1</text></svg>`;
}

/** Certificado ilustrado (o mesmo do site). style: posição e --c (tamanho). */
export function cert(style = '', { linhas = 4 } = {}) {
  const tab = [
    ['0 kg', '0,00', '0 g', '29 g'], ['75 kg', '75,00', '0 g', '31 g'],
    ['150 kg', '150,05', '+50 g', '36 g'], ['300 kg', '300,05', '+50 g', '48 g'],
  ].slice(0, linhas).map((r) => `<tr>${r.map((c) => `<td>${c}</td>`).join('')}</tr>`).join('');
  return `<div class="cert" style="${style}">
  <div class="cert__top"><span>Balanças.com · Calibração</span><span>Pág. 1/2</span></div>
  <div class="cert__head">${simbolo('cert__logo')}<p class="cert__t">Certificado de calibração</p><p class="cert__n">Nº 0147/2026</p></div>
  <dl class="cert__id"><div><dt>Instrumento</dt><dd>Balança de plataforma</dd></div><div><dt>Capacidade</dt><dd>300 kg · d = 50 g</dd></div><div><dt>Método</dt><dd>EURAMET cg-18</dd></div></dl>
  <table class="cert__tb"><thead><tr><th>Carga</th><th>Indicação</th><th>Erro</th><th>U (k=2)</th></tr></thead><tbody>${tab}</tbody></table>
  <div class="cert__foot"><ul class="cert__ok"><li>Repetibilidade</li><li>Excentricidade</li><li>Padrões rastreáveis</li></ul><span class="cert__sig"><i></i>Executado por</span>
  <span class="cert__dig"><svg viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" d="M12 2.8 19.5 6v5.6c0 4.6-3.2 8.3-7.5 9.6-4.3-1.3-7.5-5-7.5-9.6V6z"/><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="square" d="m8.6 12.2 2.4 2.4 4.6-4.8"/></svg><b>Assinado</b>digitalmente</span></div>
</div>`;
}

export const balanca = (style = '', arq = 'w300.webp') => `<img class="bal" src="${IMG}${arq}" style="${style}" alt="">`;

export const rodape = (style = '', { site = true } = {}) =>
  `<div class="ft" style="${style}"><span class="wa">${ICON_WA}<b>(19) 3023-9050</b></span>${site ? '<span><b>balancass.com</b></span>' : ''}<span>Rio Claro-SP <b>e região</b></span></div>`;
