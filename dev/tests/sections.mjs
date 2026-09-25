// Section-by-section top and height, WordPress vs the design. Needs dev/compare-proxy.py.
export default async ({ page, sleep }) => {
  const out = {};
  for (const w of (process.env.WIDTHS || '1440,1080,880,390').split(',').map(Number)) {
    await page.size(w, w < 600 ? 844 : 900);
    const rows = {};
    for (const [site, url] of [['wp', 'http://127.0.0.1:8766/'], ['d', 'http://127.0.0.1:8766/design/index.html']]) {
      await page.go(url);
      await sleep(site === 'd' ? 3000 : 1000);
      const r = await page.eval(`
        const q = (s) => document.querySelector(s);
        const els = { hero: q('#top'), marquee: q('#top').nextElementSibling, floor: q('#floor'), ways: q('#ways'), heritage: q('#heritage'), gallery: q('#gallery'), live: q('#live'), showroom: q('#showroom'), enquire: q('#enquire'), footer: q('footer') };
        const o = {};
        for (const [k, el] of Object.entries(els)) { if (!el) { o[k] = null; continue; } const b = el.getBoundingClientRect(); o[k] = [Math.round(b.top + scrollY), Math.round(b.height * 10) / 10]; }
        return o;`);
      for (const [k, v] of Object.entries(r)) (rows[k] ??= {})[site] = v;
    }
    out[w] = Object.fromEntries(Object.entries(rows).map(([k, v]) => [k, `${JSON.stringify(v.wp)} vs ${JSON.stringify(v.d)}${JSON.stringify(v.wp?.[1]) === JSON.stringify(v.d?.[1]) ? '' : '  <-- height differs'}`]));
  }
  return out;
};
