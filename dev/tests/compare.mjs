// Side by side: WordPress vs the design, per section and width. Needs dev/compare-proxy.py running.
// Usage: SECTIONS=live,showroom WIDTHS=1440,390 node dev/cdp.mjs dev/tests/compare.mjs
import { readFileSync } from 'node:fs';
const SECTIONS = (process.env.SECTIONS || 'live,showroom').split(',');
const WIDTHS = (process.env.WIDTHS || '1440,390').split(',').map(Number);
const SITES = { wp: 'http://panmotors.local/', design: 'http://127.0.0.1:8766/design/index.html' };
export default async ({ page, sleep, shot }) => {
  const files = [];
  for (const w of WIDTHS) {
    const h = w < 600 ? 844 : 900;
    await page.size(w, h);
    await page.media(false);
    for (const [site, url] of Object.entries(SITES)) {
      await page.go(url);
      await sleep(site === 'design' ? 3000 : 1500);
      for (const sec of SECTIONS) {
        await page.eval(`document.documentElement.style.scrollBehavior='auto'; const el=document.getElementById('${sec}'); scrollTo(0, el.getBoundingClientRect().top + scrollY - 82); return 1`);
        await sleep(3500);
        files.push({ w, h, sec, site, file: await shot(`${site}-${sec}-${w}`) });
      }
    }
  }
  const out = [];
  for (const w of WIDTHS) for (const sec of SECTIONS) {
    const pair = ['wp', 'design'].map((s) => files.find((f) => f.w === w && f.sec === sec && f.site === s));
    const img = (f) => 'data:image/jpeg;base64,' + readFileSync(f.file).toString('base64');
    const W = w * 2 + 36, H = pair[0].h + 34;
    await page.size(W, H);
    const html = `<meta charset="utf-8"><body style="margin:0;background:#444;font:13px sans-serif;color:#fff;display:flex;gap:12px;padding:6px 6px">${pair.map((f, i) => `<div><div style="padding:2px 0 6px">${i ? 'Design' : 'WordPress'} · ${sec} · ${w}px</div><img src="${img(f)}" width="${w}" height="${f.h}" style="display:block"></div>`).join('')}</body>`;
    await page.go('data:text/html;base64,' + Buffer.from(html).toString('base64'));
    out.push(await shot(`sbs-${sec}-${w}`, { x: 0, y: 0, width: W, height: H }));
  }
  return out;
};
