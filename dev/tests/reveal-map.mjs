// Which elements reveal on scroll, per section, in WordPress vs the design; and do they fire.
export default async ({ page, sleep }) => {
  await page.size(1440, 900);
  await page.media(false);
  const res = {};
  for (const [site, url] of [['wp', 'http://127.0.0.1:8766/'], ['d', 'http://127.0.0.1:8766/design/index.html']]) {
    await page.go(url); await sleep(site === 'd' ? 3000 : 1000);
    await page.eval(`document.documentElement.style.scrollBehavior='auto'; const H=document.documentElement.scrollHeight; for (let y=0;y<H;y+=400){ scrollTo(0,y); await new Promise(r=>setTimeout(r,150)); } return 1`);
    await sleep(1500);
    res[site] = await page.eval(`return [...document.querySelectorAll('[data-rise],[data-rise-l]')].map(e => (e.closest('section')?.id || 'no-id') + ':' + (e.hasAttribute('data-rise-l') ? 'L-' : '') + e.tagName.toLowerCase() + (e.classList.contains('in') ? ' ✓' : ' NOT FIRED'))`);
  }
  return res;
};
