// Contrast of text axe cannot judge: text over photos, gradients, or other layers.
// Usage: node dev/tests/contrast-photos.mjs   (needs `npm install` in dev/ and a run of axe.mjs)
//
// Takes the elements axe marked "incomplete" for color-contrast (dev/.cache/axe/<AXE or after>.json),
// hides all text, screenshots each element's box, and for every pixel blends the element's text
// colour (with its alpha and the opacity of its ancestors) over the real background. Reports the
// contrast at the 10th percentile of the box (dark text on a light patch or the reverse shows up
// there) against 4.5 : 1, or 3 : 1 for large text (24px, or 18.66px bold). Reduced motion: posters,
// not video frames. Pages at 1440 and 390.
import { chromium } from 'playwright';
import { readFileSync } from 'node:fs';
import { readPng as PNG } from '../png.mjs';
import { PAGES } from './pages.mjs';

const axe = JSON.parse(readFileSync(new URL(`../.cache/axe/${process.env.AXE || 'after'}.json`, import.meta.url)));
const lum = ([r, g, b]) => { const f = (c) => { c /= 255; return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4; }; return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
const ratio = (a, b) => { const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p); return (x + 0.05) / (y + 0.05); };

const browser = await chromium.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' });
const fails = [];
let checked = 0;
for (const [name, path] of Object.entries(PAGES)) {
  for (const width of [1440, 390]) {
    const entry = axe[`${name} ${width}`];
    if (!entry) continue;
    const targets = [...new Set(entry.incomplete.filter((i) => i.id === 'color-contrast').flatMap((i) => i.nodes.filter((n) => !/non-text characters/.test(n.summary || '')).map((n) => n.target.join(' '))))];
    if (!targets.length) continue;
    const ctx = await browser.newContext({ viewport: { width, height: width < 600 ? 844 : 900 }, reducedMotion: 'reduce' });
    const page = await ctx.newPage();
    await page.goto('http://panmotors.local/' + path, { waitUntil: 'load' });
    await page.addStyleTag({ content: '*,*::before,*::after{transition:none!important}' });
    await page.evaluate(async () => { for (let y = 0; y < document.documentElement.scrollHeight; y += 500) { scrollTo(0, y); await new Promise((r) => setTimeout(r, 50)); } for (const i of document.images) i.loading = 'eager'; await Promise.all([...document.images].map((i) => i.decode().catch(() => 0))); scrollTo(0, 0); });
    for (const sel of targets) {
      const info = await page.evaluate((s) => {
        const el = document.querySelector(s);
        if (!el) return null;
        const cs = getComputedStyle(el);
        let op = 1;
        for (let n = el; n; n = n.parentElement) op *= parseFloat(getComputedStyle(n).opacity);
        const m = cs.color.match(/[\d.]+/g).map(Number);
        const r = el.getBoundingClientRect();
        const size = parseFloat(cs.fontSize), bold = parseInt(cs.fontWeight, 10) >= 700;
        return { fg: m.slice(0, 3), alpha: (m[3] ?? 1) * op, box: { x: r.x, y: r.y, width: r.width, height: r.height }, large: size >= 24 || (bold && size >= 18.66), text: el.textContent.replace(/\s+/g, ' ').trim().slice(0, 40), size };
      }, sel);
      if (!info || info.box.width < 2 || info.box.height < 2 || !info.text) continue;
      await page.waitForTimeout(150);
      await page.evaluate(() => { const st = document.createElement('style'); st.id = 'pm-hide-text'; st.textContent = '*{color:transparent!important;-webkit-text-fill-color:transparent!important;text-shadow:none!important;text-decoration-color:transparent!important;caret-color:transparent!important}'; document.head.append(st); });
      const shot = await page.locator(sel).first().screenshot({ animations: 'disabled' });
      await page.evaluate(() => document.getElementById('pm-hide-text')?.remove());
      const png = PNG(shot);
      const rs = [];
      for (let i = 0; i < png.data.length; i += 4) {
        const bg = [png.data[i], png.data[i + 1], png.data[i + 2]];
        const fg = info.fg.map((c, k) => info.alpha * c + (1 - info.alpha) * bg[k]);
        rs.push(ratio(fg, bg));
      }
      rs.sort((a, b) => a - b);
      const p10 = rs[Math.floor(rs.length * 0.1)];
      const need = info.large ? 3 : 4.5;
      checked++;
      if (p10 < need) fails.push(`${name} ${width} ${sel.slice(0, 60)} "${info.text}" ${info.size}px: ${p10.toFixed(2)} (min ${rs[0].toFixed(2)}) < ${need}`);
    }
    await ctx.close();
  }
}
await browser.close();
console.log(`${checked} text elements over photos, gradients or layers checked`);
console.log(fails.length ? fails.join('\n') : 'All at or above the minimum (10th percentile of the box)');
