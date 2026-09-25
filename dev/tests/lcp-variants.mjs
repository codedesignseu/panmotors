// Which hero-poster property stops it being an LCP candidate?
const VARIANTS = {
  baseline: '',
  poster90: '.pm-hero__poster{width:90%!important;height:90%!important}',
  heroShorter: '.pm-hero{height:80vh!important;min-height:0!important}',
};
export default async ({ page, sleep }) => {
  const out = {};
  let n = 0;
  for (const [name, css] of Object.entries(VARIANTS)) {
    const { identifier } = await page.send('Page.addScriptToEvaluateOnNewDocument', { source: `window.__lcp=[]; new PerformanceObserver(l=>l.getEntries().forEach(e=>window.__lcp.push((e.element? e.element.className.split(' ')[0]:'?')+'@'+Math.round(e.startTime)+' size '+e.size))).observe({type:'largest-contentful-paint', buffered:true}); if (${JSON.stringify(css)}) { const add=()=>{ const s=document.createElement('style'); s.textContent=${JSON.stringify(css)}; (document.head||document.documentElement).appendChild(s); }; document.documentElement ? add() : document.addEventListener('readystatechange', add, {once:true}); }` });
    await page.size(412, 823);
    await page.go('http://panmotors.local/?v=' + (++n));
    await sleep(3000);
    out[name] = await page.eval(`return window.__lcp`);
    await page.send('Page.removeScriptToEvaluateOnNewDocument', { identifier });
  }
  return out;
};
