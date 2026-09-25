// Full-page side by side: WordPress vs the design. Needs dev/compare-proxy.py running.
// Usage: WIDTHS=1440,390 node dev/cdp.mjs dev/tests/fullpage.mjs
import { writeFileSync, mkdirSync } from 'node:fs';
const CACHE = new URL('../.cache/shots/', import.meta.url).pathname;
const WIDTHS = (process.env.WIDTHS || '1440,390').split(',').map(Number);
const SITES = { wp: 'http://127.0.0.1:8766/', design: 'http://127.0.0.1:8766/design/index.html' };

export default async ({ page, sleep, shot }) => {
  const out = { files: [], heights: {} };
  for (const w of WIDTHS) {
    const h = w < 600 ? 844 : 900;
    await page.size(w, h);
    await page.media(false);
    const pair = [];
    for (const [site, url] of Object.entries(SITES)) {
      await page.go(url);
      await sleep(site === 'design' ? 3000 : 1200);
      // Walk the page so every reveal and lazy image fires, then return to the top.
      const H = await page.eval(`document.documentElement.style.scrollBehavior='auto'; return document.documentElement.scrollHeight`);
      for (let y = 0; y < H; y += Math.round(h * 0.6)) { await page.eval(`scrollTo(0, ${y}); return 1`); await sleep(250); }
      await page.eval(`scrollTo(0, 0); return 1`);
      await sleep(2500);
      const full = await page.eval(`return document.documentElement.scrollHeight`);
      out.heights[`${site}-${w}`] = full;
      const r = await page.send('Page.captureScreenshot', { format: 'jpeg', quality: 70, captureBeyondViewport: true, clip: { x: 0, y: 0, width: w, height: full, scale: 1 } });
      mkdirSync(CACHE, { recursive: true });
      const file = `${CACHE}full-${site}-${w}.jpg`;
      writeFileSync(file, Buffer.from(r.data, 'base64'));
      pair.push({ site, full, file });
    }
    const scale = w < 600 ? 0.6 : 0.35;
    const W = Math.round(w * scale) * 2 + 36;
    const H = Math.round(Math.max(...pair.map((p) => p.full)) * scale) + 40;
    await page.size(W, Math.min(H, 16000));
    const html = `<meta charset="utf-8"><body style="margin:0;background:#444;font:13px sans-serif;color:#fff;display:flex;gap:12px;padding:6px;align-items:flex-start">${pair.map((p) => `<div><div style="padding:2px 0 6px">${p.site === 'wp' ? 'WordPress' : 'Design'} · ${w}px · ${p.full}px tall</div><img src="full-${p.site}-${w}.jpg" width="${Math.round(w * scale)}" style="display:block"></div>`).join('')}</body>`;
    writeFileSync(`${CACHE}compose-${w}.html`, html);
    await page.go(`file://${CACHE}compose-${w}.html`);
    await sleep(500);
    const r = await page.send('Page.captureScreenshot', { format: 'jpeg', quality: 80, captureBeyondViewport: true, clip: { x: 0, y: 0, width: W, height: Math.min(H, 16000), scale: 1 } });
    const file = `${CACHE}fullpage-${w}.jpg`;
    writeFileSync(file, Buffer.from(r.data, 'base64'));
    out.files.push(file);
  }
  return out;
};
