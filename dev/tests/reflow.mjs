// Reflow (WCAG 1.4.10): no horizontal scrolling at 320 CSS px, and at 200% zoom of a 1280px and a
// 1440px window (640 and 720 CSS px). Also lists elements that stick out of the viewport and are
// not inside a slider track or a scroller (content that is cut off by overflow rules).
// Usage: node dev/tests/reflow.mjs   (needs `npm install` in dev/)
import { chromium } from 'playwright';
import { PAGES } from './pages.mjs';

const browser = await chromium.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' });
let bad = 0;
for (const [name, path] of Object.entries(PAGES)) {
  for (const [label, width, height] of [['320px', 320, 640], ['1280 @ 200%', 640, 400], ['1440 @ 200%', 720, 450]]) {
    const ctx = await browser.newContext({ viewport: { width, height }, reducedMotion: 'reduce' });
    const page = await ctx.newPage();
    await page.goto('http://panmotors.local/' + path, { waitUntil: 'load' });
    await page.waitForTimeout(500);
    const r = await page.evaluate(() => {
      const doc = document.documentElement;
      const vw = doc.clientWidth;
      const scrolls = doc.scrollWidth > vw || document.body.scrollWidth > vw;
      const skip = (el) => el.closest('[data-slider-track], .pm-marquee, .pm-menu, dialog, .screen-reader-text, .pm-skip-link, [aria-hidden="true"], .pm-showroom__backdrop, .pm-slider__backdrop, [data-hero-img]'); // Sliders, the menu panel, hidden text and background photos (the page header photo is scaled a little and clipped).
      const out = [...document.querySelectorAll('main *, header *, footer *')].filter((el) => {
        if (skip(el)) return false;
        const b = el.getBoundingClientRect();
        if (!b.width || !b.height) return false;
        const cs = getComputedStyle(el);
        if (cs.visibility === 'hidden' || cs.position === 'fixed') return false;
        return b.right > vw + 1 || b.left < -1;
      }).filter((el, i, all) => !all.some((o) => o !== el && o.contains(el)))
        .map((el) => `${el.tagName.toLowerCase()}.${(el.className.baseVal ?? el.className).split(' ')[0]} (${Math.round(el.getBoundingClientRect().left)}–${Math.round(el.getBoundingClientRect().right)})`);
      return { scrolls, scrollWidth: doc.scrollWidth, vw, out: out.slice(0, 6) };
    });
    if (r.scrolls || r.out.length) bad++;
    console.log(`${name} ${label}`.padEnd(28), r.scrolls ? `HORIZONTAL SCROLL (${r.scrollWidth} > ${r.vw})` : 'no horizontal scroll', r.out.length ? ' · sticks out: ' + r.out.join(', ') : '');
    await ctx.close();
  }
}
await browser.close();
console.log(bad ? `${bad} page/width combinations to look at` : 'Reflow OK everywhere');
