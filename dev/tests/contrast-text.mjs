// Contrast of text over photos, gradients and washes, against the brightest pixel behind it.
// Usage: node dev/tests/contrast-text.mjs <label>   (needs `npm install` in dev/)
//
// For each target: screenshot the element with its text, then with the text transparent (a
// text-shadow stays, so it counts as background). The glyph mask is every pixel that changed,
// grown by 1px (the anti-aliased edge); the ratio reported is the text colour (with its alpha and ancestors' opacity)
// against the brightest background pixel in that mask (min), plus the 10th percentile (p10).
// Reduced motion (posters, first slide). Crops go to dev/.cache/shots/contrast/<label>-*.png.
import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';
import { readPng, writePng } from '../png.mjs';

const label = process.argv[2] || 'contrast';
const OUT = new URL('../.cache/shots/contrast/', import.meta.url).pathname;
mkdirSync(OUT, { recursive: true });

// [page, selector, what] — every match of the selector is measured.
export const TARGETS = [
  ['', '.pm-hero__eyebrow', 'Home hero eyebrow'],
  ['', '.pm-tile__marque', 'Home tile marque'],
  ['', '.pm-showroom__caption-text', 'Home Showroom caption'],
  ['', '.pm-showroom__count', 'Home Showroom counter'],
  ['featured-cars/', '.pm-car__marque', 'Featured Cars card marque'],
  ['about/', '.pm-page-header__eyebrow', 'About header eyebrow'],
  ['about/', '.pm-service__index', 'About What We Do index'],
  ['latest-cars/', '.pm-page-hero__eyebrow', 'Latest Cars hero eyebrow'],
  ['showroom/', '.pm-page-header__eyebrow', 'Showroom header eyebrow'],
  ['showroom/', '.pm-slider__count .is-current', 'Showroom Inside counter'],
  ['showroom/', '.pm-slider__captions .is-current', 'Showroom Inside caption'],
  ['showroom/', '.pm-slider__hint', 'Showroom Inside hint'],
];

const lum = ([r, g, b]) => { const f = (c) => { c /= 255; return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4; }; return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
const ratio = (a, b) => { const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p); return (x + 0.05) / (y + 0.05); };
const HIDE = '*{color:transparent!important;-webkit-text-fill-color:transparent!important;text-decoration-color:transparent!important;caret-color:transparent!important}';

const browser = await chromium.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' });
const rows = [];
for (const width of [1440, 390]) {
  const pages = [...new Set(TARGETS.map((t) => t[0]))];
  for (const path of pages) {
    const ctx = await browser.newContext({ viewport: { width, height: width < 600 ? 844 : 900 }, reducedMotion: 'reduce' });
    const page = await ctx.newPage();
    await page.goto('http://panmotors.local/' + path, { waitUntil: 'load' });
    await page.addStyleTag({ content: '*,*::before,*::after{transition:none!important;animation:none!important}' + (process.env.EXTRA_CSS || '') }); // EXTRA_CSS: try a fix before writing it.
    await page.evaluate(async () => { for (let y = 0; y < document.documentElement.scrollHeight; y += 400) { scrollTo(0, y); await new Promise((r) => setTimeout(r, 60)); } await Promise.race([Promise.all([...document.images].filter((i) => i.complete).map((i) => i.decode().catch(() => 0))), new Promise((r) => setTimeout(r, 3000))]); scrollTo(0, 0); }); // decode() of a lazy image that never loads would wait forever.
    await page.waitForTimeout(400);
    for (const [p, sel, what] of TARGETS.filter((t) => t[0] === path)) {
      const n = await page.locator(sel).count();
      for (let k = 0; k < n; k++) {
        const loc = page.locator(sel).nth(k);
        if (process.env.VERBOSE) console.error(width, path, sel, k);
        if (!(await loc.isVisible())) continue;
        const info = await loc.evaluate((el) => {
          const cs = getComputedStyle(el);
          let op = 1;
          for (let x = el; x; x = x.parentElement) op *= parseFloat(getComputedStyle(x).opacity);
          const m = cs.color.match(/[\d.]+/g).map(Number);
          return { fg: m.slice(0, 3), alpha: (m[3] ?? 1) * op, text: el.textContent.replace(/\s+/g, ' ').trim().slice(0, 30) };
        });
        if (!info.text) continue;
        await loc.scrollIntoViewIfNeeded({ timeout: 5000 }).catch(() => 0);
        const withText = readPng(await loc.screenshot({ animations: 'disabled', timeout: 8000 }));
        await page.evaluate((css) => { const s = document.createElement('style'); s.id = 'pm-hide'; s.textContent = css; document.head.append(s); }, HIDE);
        const bgPng = await loc.screenshot({ animations: 'disabled', timeout: 8000 });
        await page.evaluate(() => document.getElementById('pm-hide').remove());
        const bg = readPng(bgPng);
        const { width: w, height: h } = bg;
        const glyph = new Uint8Array(w * h);
        for (let i = 0; i < w * h; i++) if (Math.max(...[0, 1, 2].map((c) => Math.abs(withText.data[i * 4 + c] - bg.data[i * 4 + c]))) > 8) glyph[i] = 1;
        const mask = new Uint8Array(w * h);
        for (let y = 0; y < h; y++) for (let x = 0; x < w; x++) if (glyph[y * w + x]) for (let dy = -1; dy <= 1; dy++) for (let dx = -1; dx <= 1; dx++) { const yy = y + dy, xx = x + dx; if (yy >= 0 && yy < h && xx >= 0 && xx < w) mask[yy * w + xx] = 1; }
        const rs = [];
        for (let i = 0; i < w * h; i++) {
          if (!mask[i]) continue;
          const b = [bg.data[i * 4], bg.data[i * 4 + 1], bg.data[i * 4 + 2]];
          rs.push(ratio(info.fg.map((c, j) => info.alpha * c + (1 - info.alpha) * b[j]), b));
        }
        rs.sort((a, b) => a - b);
        const name = `${label}-${what.replace(/\W+/g, '-').toLowerCase()}-${k + 1}-${width}`;
        writePng(`${OUT}${name}.png`, withText);
        rows.push({ what: `${what} #${k + 1} "${info.text}"`, width, min: rs.length ? rs[0] : NaN, p10: rs.length ? rs[Math.floor(rs.length * 0.1)] : NaN, crop: name + '.png' });
      }
    }
    await ctx.close();
  }
}
await browser.close();
writeFileSync(`${OUT}${label}.json`, JSON.stringify(rows, null, 1));
for (const r of rows) console.log(`${r.min >= 4.5 ? 'ok  ' : 'LOW '} ${String(r.width).padEnd(5)} ${r.what.padEnd(58)} min ${r.min.toFixed(2)}  p10 ${r.p10.toFixed(2)}`);
console.log(rows.every((r) => r.min >= 4.5) ? 'All at 4.5 : 1 or more against the brightest pixel' : `${rows.filter((r) => r.min < 4.5).length} below 4.5 : 1`);
