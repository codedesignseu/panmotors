// Full-page side by side: a WordPress page and its design, reduced motion (static: every reveal
// shown, no video or zoom in progress), stitched from viewport-sized captures. Needs dev/compare-proxy.py running.
// Usage: PAGE=about/ DESIGN=v2/about.html WIDTHS=1440,390 node dev/cdp.mjs dev/tests/compare-page.mjs
// Writes dev/.cache/shots/page-<name>-<width>.png (both pages, full height) and returns their heights.
import { writeFileSync, mkdirSync } from 'node:fs';
const PAGE = process.env.PAGE || 'about/';
const DESIGN = process.env.DESIGN || 'v2/about.html';
const WIDTHS = (process.env.WIDTHS || '1440,390').split(',').map(Number);
const NAME = PAGE.replace(/\W+/g, '') || 'home';
const SHOTS = new URL('../.cache/shots/', import.meta.url).pathname;

export default async ({ page, sleep }) => {
  mkdirSync(SHOTS, { recursive: true });
  const out = {};
  for (const w of WIDTHS) {
    const h = w < 600 ? 844 : 900;
    const pngs = [];
    for (const [site, url] of [['WordPress', `http://127.0.0.1:8766/${PAGE}`], ['Design', `http://127.0.0.1:8766/design/${DESIGN}`]]) {
      await page.size(w, h);
      await page.media(true);
      await page.go(url);
      await sleep(site === 'Design' ? 3000 : 1200);
      const full = await page.eval(`document.documentElement.style.scrollBehavior='auto'; const H=document.documentElement.scrollHeight; for (let y=0;y<H;y+=${Math.round(h * 0.6)}){ scrollTo(0,y); await new Promise(r=>setTimeout(r,150)); } scrollTo(0,0); await document.fonts.ready; await Promise.race([Promise.all([...document.images].map(i => i.complete ? 0 : new Promise(r => { i.onload = i.onerror = r; }))), new Promise(r => setTimeout(r, 6000))]); await new Promise(r=>setTimeout(r,800)); return document.documentElement.scrollHeight`);
      // Viewport tiles, stitched: a full-page capture would enlarge the viewport and stretch every
      // vh-based height (the 92vh page header). Fixed bars are pinned to the top of the page.
      await page.eval(`for (const e of document.querySelectorAll('body *')) { if (getComputedStyle(e).position === 'fixed') e.style.position = 'absolute'; } return 1`);
      const tiles = [];
      for (let y = 0; y < full; y += h) {
        const top = Math.min(y, Math.max(0, full - h));
        await page.eval(`scrollTo(0, ${top}); await new Promise(r => setTimeout(r, 350)); return 1`);
        const r = await page.send('Page.captureScreenshot', { format: 'jpeg', quality: 88 });
        tiles.push({ top, data: r.data });
      }
      pngs.push({ site, full, tiles });
    }
    out[w] = pngs.map((p) => `${p.site} ${p.full}px`);
    const H = Math.max(...pngs.map((p) => p.full)) + 30, W = w * 2 + 36;
    const html = `<meta charset="utf-8"><body style="margin:0;background:#444;font:14px sans-serif;color:#fff;display:flex;gap:12px;padding:0 6px">${pngs.map((p) => `<div><div style="height:26px;line-height:26px">${p.site} · ${PAGE} · ${w}px · ${p.full}px</div><div style="position:relative;width:${w}px;height:${p.full}px;overflow:hidden">${p.tiles.map((t) => `<img src="data:image/jpeg;base64,${t.data}" width="${w}" style="position:absolute;left:0;top:${t.top}px">`).join('')}</div></div>`).join('')}</body>`;
    await page.size(W, Math.min(H, 16000));
    // A file, not a data: URL: a dozen stitched tiles are too large for a data: URL.
    writeFileSync(`${SHOTS}_compose.html`, html);
    await page.go(`file://${SHOTS}_compose.html`);
    await sleep(800);
    const shot = await page.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width: W, height: H, scale: 1 } });
    writeFileSync(`${SHOTS}page-${NAME}-${w}.png`, Buffer.from(shot.data, 'base64'));
  }
  return out;
};
