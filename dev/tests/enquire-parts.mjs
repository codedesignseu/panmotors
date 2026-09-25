export default async ({ page, sleep }) => {
  await page.size(1440, 900);
  const res = {};
  for (const [site, url] of [['wp', 'http://127.0.0.1:8766/'], ['d', 'http://127.0.0.1:8766/design/index.html']]) {
    await page.go(url); await sleep(site === 'd' ? 3000 : 1000);
    res[site] = await page.eval(`
      const s = document.getElementById('enquire'); const card = s.firstElementChild;
      const cols = [...card.children].map(c => Math.round(c.getBoundingClientRect().height*10)/10);
      const form = s.querySelector('form');
      const kids = [...form.children].map(k => { const b = k.getBoundingClientRect(); return k.tagName + ':' + Math.round(b.height*10)/10; });
      const ctl = [...form.querySelectorAll('input,textarea,button')].map(e => e.tagName + ' h' + Math.round(e.getBoundingClientRect().height*10)/10 + ' lh ' + getComputedStyle(e).lineHeight + ' ff ' + getComputedStyle(e).fontFamily.slice(0,8));
      const lbl = [...form.querySelectorAll('label, label > span')].slice(0,2).map(e => e.tagName + ' h' + Math.round(e.getBoundingClientRect().height*10)/10);
      return { section: Math.round(s.getBoundingClientRect().height*10)/10, card: Math.round(card.getBoundingClientRect().height*10)/10, cols, kids, ctl, lbl, gap: getComputedStyle(form).rowGap };`);
  }
  return res;
};
