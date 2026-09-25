// Largest Contentful Paint candidates at mobile size.
export default async ({ page, sleep }) => {
  await page.send('Page.addScriptToEvaluateOnNewDocument', { source: `window.__lcp=[]; new PerformanceObserver(l=>l.getEntries().forEach(e=>window.__lcp.push({t:Math.round(e.startTime), size:e.size, el:e.element? (e.element.tagName+'.'+e.element.className).slice(0,60):'?', url:(e.url||'').split('/').pop()}))).observe({type:'largest-contentful-paint', buffered:true});` });
  await page.size(412, 823);
  await page.media(false);
  await page.go('http://panmotors.local/');
  await sleep(4000);
  return page.eval(`const p=document.querySelector('.pm-hero__poster'); const r=p.getBoundingClientRect(); return { lcp: window.__lcp, poster: { w: Math.round(r.width), h: Math.round(r.height), src: p.currentSrc.split('/').pop(), natural: p.naturalWidth + 'x' + p.naturalHeight, bytes: performance.getEntriesByName(p.currentSrc)[0]?.encodedBodySize } }`);
};
