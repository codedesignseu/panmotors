// Media audit on every page at 1440 and 390 (DPR 1 and 2): for each <img>, the slot width, the file
// the browser picked (from srcset/sizes), loading, fetchpriority and whether it is in the first
// viewport; each <video>'s preload; fonts loaded before first paint and render-blocking resources.
// Usage: node dev/tests/media-audit.mjs   (needs `npm install` in dev/)
import { chromium } from 'playwright';
import { PAGES } from './pages.mjs';

const browser = await chromium.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' });
const problems = [];
for (const [name, path] of Object.entries(PAGES)) {
  for (const [width, dpr] of [[1440, 1], [1440, 2], [390, 2], [390, 3]]) {
    const ctx = await browser.newContext({ viewport: { width, height: width < 600 ? 844 : 900 }, deviceScaleFactor: dpr, reducedMotion: 'reduce' });
    const page = await ctx.newPage();
    await page.goto('http://panmotors.local/' + path, { waitUntil: 'load' });
    const first = await page.evaluate(() => [...document.images].map((i) => { const r = i.getBoundingClientRect(); return { src: (i.currentSrc || i.src).split('/').pop(), aboveFold: r.top < innerHeight && r.bottom > 0 && r.width > 0 }; }));
    await page.evaluate(async () => { for (let y = 0; y < document.documentElement.scrollHeight; y += 400) { scrollTo(0, y); await new Promise((r) => setTimeout(r, 80)); } });
    await page.waitForTimeout(800);
    const rows = await page.evaluate(() => [...document.images].map((i) => {
      const r = i.getBoundingClientRect();
      const cands = (i.getAttribute('srcset') || '').split(',').map((c) => c.trim().split(/\s+/)).filter((c) => c[0]).map(([u, w]) => ({ u: u.split('/').pop(), w: parseInt(w, 10) }));
      const cur = (i.currentSrc || i.src).split('/').pop();
      // naturalWidth is divided by the candidate's density, so take the file's width from srcset.
      const picked = cands.find((c) => c.u === cur);
      return { cls: i.className.split(' ')[0], src: cur, natural: picked ? picked.w : i.naturalWidth, slot: Math.round(r.width), slotH: Math.round(r.height), hidden: r.width === 0, loading: i.getAttribute('loading'), fp: i.getAttribute('fetchpriority'), sizes: i.getAttribute('sizes'), largest: cands.length ? Math.max(...cands.map((c) => c.w)) : null, opacity: getComputedStyle(i.parentElement).opacity, hasWH: i.hasAttribute('width') && i.hasAttribute('height'), alt: i.getAttribute('alt') };
    }));
    rows.forEach((row, k) => {
      row.aboveFold = first[k]?.aboveFold;
      const need = row.slot * dpr;
      const tag = `${name} ${width}@${dpr}x ${row.cls} ${row.src}`;
      const shown = row.opacity !== '0';
      if (row.hidden) return;
      if (!row.hasWH) problems.push(`${tag}: no width/height`);
      if (row.alt === null) problems.push(`${tag}: no alt attribute`);
      if (row.aboveFold && shown && row.loading === 'lazy' && dpr === 2) problems.push(`${tag}: above the fold but lazy`);
      if (!row.aboveFold && row.loading !== 'lazy' && dpr === 2) problems.push(`${tag}: below the fold, not lazy (${row.loading})`);
      if (row.natural && need > row.natural * 1.15 && (!row.largest || row.largest > row.natural)) problems.push(`${tag}: soft, file ${row.natural}px for ${need}px (slot ${row.slot} × ${dpr}; largest candidate ${row.largest})`);
      if (row.natural && row.natural > need * 2.2 && row.natural > 800) problems.push(`${tag}: oversized, file ${row.natural}px for ${need}px (sizes="${row.sizes}")`);
    });
    if (width === 1440 && dpr === 2 || width === 390 && dpr === 2) {
      const media = await page.evaluate(() => ({
        videos: [...document.querySelectorAll('video')].map((v) => `${v.className.split(' ')[0]} preload=${v.getAttribute('preload')}`),
        lcpImg: [...document.images].filter((i) => i.getAttribute('fetchpriority') === 'high').map((i) => `${i.className.split(' ')[0]} loading=${i.getAttribute('loading')}`),
        preload: [...document.querySelectorAll('link[rel=preload]')].map((l) => `${l.as} ${l.href.split('/').pop().split('?')[0]}${l.getAttribute('fetchpriority') ? ' fp=' + l.getAttribute('fetchpriority') : ''}`),
        blocking: performance.getEntriesByType('resource').filter((e) => e.renderBlockingStatus === 'blocking').map((e) => e.name.split('/').pop().split('?')[0]),
        fontDisplay: [...document.fonts].map((f) => f.display).filter((d, i, a) => a.indexOf(d) === i),
      }));
      console.log(`${name} ${width}`.padEnd(22), JSON.stringify(media));
    }
    await ctx.close();
  }
}
await browser.close();
console.log(problems.length ? problems.join('\n') : 'No image problems');
