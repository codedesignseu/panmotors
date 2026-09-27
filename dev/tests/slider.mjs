// Showroom photo slider (D12 step 5): crossfade timing next to the v2 design, backdrop, header
// overlap, keyboard, reduced motion, touch swipe and page scroll over the slider.
// Needs dev/compare-proxy.py for the design side. Usage: node dev/cdp.mjs dev/tests/slider.mjs
const WP = 'http://127.0.0.1:8766/showroom/';
const DESIGN = 'http://127.0.0.1:8766/design/v2/showroom.html';
export default async ({ page, sleep, shot }) => {
  const out = {};
  const ev = (js) => page.eval(js);
  const key = async (k, code, vk, mods = 0) => { await page.send('Input.dispatchKeyEvent', { type: 'keyDown', key: k, code, windowsVirtualKeyCode: vk, modifiers: mods }); await page.send('Input.dispatchKeyEvent', { type: 'keyUp', key: k, code, windowsVirtualKeyCode: vk, modifiers: mods }); await sleep(100); };

  // Crossfade: opacity of the shown photo over time after "next", on both sides.
  await page.size(1440, 900);
  await page.media(false);
  for (const [side, url] of [['wp', WP], ['design', DESIGN]]) {
    await page.go(url); await sleep(side === 'design' ? 3000 : 1500);
    const probe = side === 'wp'
      ? `const ps=[...document.querySelectorAll('.pm-slider__photo')]; return ps.slice(0,2).map(p=>Number(getComputedStyle(p).opacity).toFixed(2)).join('|')`
      : `const i=document.querySelector('[data-zoom] img'); return Number(getComputedStyle(i).opacity).toFixed(2) + ' ' + i.src.split('/').pop().slice(0,8)`;
    const next = side === 'wp' ? `document.querySelector('.pm-slider__row > [data-slide-next]').click()` : `document.querySelectorAll('button[aria-label="Next photograph"]')[1].click()`;
    await ev(`${next}; return 1`);
    const t0 = Date.now(); const samples = [];
    for (const t of [60, 200, 330, 500, 700, 950]) { while (Date.now() - t0 < t) await sleep(5); samples.push(t + 'ms ' + await ev(probe)); }
    out['fade_' + side] = samples;
    out['backdrop_' + side] = await ev(side === 'wp'
      ? `const b=document.querySelector('.pm-slider__bg.is-current'); const s=getComputedStyle(b); return s.filter + ' / ' + s.opacity + ' / ' + s.transform`
      : `const b=document.querySelector('section img[alt=""]'); const s=getComputedStyle(b); return s.filter + ' / ' + s.opacity + ' / ' + s.transform`);
    out['overlap_' + side] = await ev(`const s=[...document.querySelectorAll('section')]; const a=s[0].getBoundingClientRect(), b=s[1].getBoundingClientRect(); return Math.round(a.bottom - b.top) + 'px, radius ' + getComputedStyle(s[1]).borderTopLeftRadius`);
  }

  // Keyboard: every stop in the slider, focus visible; arrow keys on the photo change it.
  await page.go(WP); await sleep(1200);
  await ev(`document.documentElement.style.scrollBehavior='auto'; return 1`);
  const stops = [];
  for (let i = 0; i < 25; i++) {
    await key('Tab', 'Tab', 9);
    const s = await ev(`const a=document.activeElement; if (!a.closest('.pm-slider')) return null; const cs=getComputedStyle(a); return (a.getAttribute('aria-label') || a.className).slice(0,40) + ' | outline ' + cs.outlineStyle + ' ' + cs.outlineWidth`);
    if (s) stops.push(s);
  }
  out.sliderStops = stops;
  await ev(`document.querySelector('[data-slide-stage]').focus(); return 1`);
  const cur = () => ev(`return [...document.querySelectorAll('.pm-slider__count [data-slide-part]')].findIndex(e=>!e.hidden) + ' ' + document.querySelector('.pm-slider__captions [data-slide-part]:not([hidden])').textContent.trim()`);
  out.before = await cur();
  await key('ArrowRight', 'ArrowRight', 39); await sleep(500);
  out.afterRight = await cur();
  await key('ArrowLeft', 'ArrowLeft', 37); await key('ArrowLeft', 'ArrowLeft', 37); await sleep(500);
  out.afterLeftTwice = await cur();
  await ev(`document.querySelectorAll('[data-slide-dot]')[2].click(); return 1`); await sleep(500);
  out.dot3 = await cur() + ' / aria-current ' + await ev(`return [...document.querySelectorAll('[data-slide-dot]')].map(d=>d.getAttribute('aria-current')).join(',')`) + ' / width ' + await ev(`return getComputedStyle(document.querySelectorAll('[data-slide-dot]')[2]).width`);

  // Mouse drag of 120px to the left.
  const box = await ev(`const r=document.querySelector('[data-slide-stage]').getBoundingClientRect(); document.querySelector('[data-slide-stage]').scrollIntoView({block:'center'}); const q=document.querySelector('[data-slide-stage]').getBoundingClientRect(); return [q.x+q.width/2, q.y+q.height/2]`);
  const b1 = await cur();
  await page.send('Input.dispatchMouseEvent', { type: 'mousePressed', x: box[0], y: box[1], button: 'left', clickCount: 1 });
  await page.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: box[0] - 60, y: box[1], button: 'left' });
  await page.send('Input.dispatchMouseEvent', { type: 'mouseReleased', x: box[0] - 120, y: box[1], button: 'left', clickCount: 1 });
  await sleep(600);
  out.drag = b1 + ' -> ' + await cur();
  // A 30px drag does nothing.
  const b2 = await cur();
  await page.send('Input.dispatchMouseEvent', { type: 'mousePressed', x: box[0], y: box[1], button: 'left', clickCount: 1 });
  await page.send('Input.dispatchMouseEvent', { type: 'mouseReleased', x: box[0] - 30, y: box[1], button: 'left', clickCount: 1 });
  await sleep(600);
  out.shortDrag = b2 + ' -> ' + await cur();

  // Reduced motion: the change is instant.
  await page.media(true);
  await page.go(WP); await sleep(800);
  await ev(`document.querySelector('.pm-slider__row > [data-slide-next]').click(); return 1`); await sleep(30);
  out.reduced = { now: await cur(), opacity: await ev(`return getComputedStyle(document.querySelector('.pm-slider__photo.is-current')).opacity`), zoomTransition: await ev(`return getComputedStyle(document.querySelector('.pm-slider__photo')).transitionDuration`), headerZoom: await ev(`return getComputedStyle(document.querySelector('.pm-page-header__media')).transform`) };

  // Touch at 390: swipe left changes the photo; a vertical swipe over the photo scrolls the page.
  await page.media(false);
  await page.send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 2, mobile: true });
  await page.send('Emulation.setTouchEmulationEnabled', { enabled: true, maxTouchPoints: 5 });
  await page.go(WP); await sleep(1500);
  await ev(`document.documentElement.style.scrollBehavior='auto'; document.querySelector('[data-slide-stage]').scrollIntoView({block:'center'}); return 1`); await sleep(400);
  const t = await ev(`const q=document.querySelector('[data-slide-stage]').getBoundingClientRect(); return [q.x+q.width/2, q.y+q.height/2]`);
  const touch = async (points) => { for (const [type, x, y] of points) { await page.send('Input.dispatchTouchEvent', { type, touchPoints: type === 'touchEnd' ? [] : [{ x, y }] }); await sleep(16); } };
  const s1 = await cur();
  await touch([['touchStart', t[0] + 80, t[1]], ['touchMove', t[0] + 40, t[1]], ['touchMove', t[0] - 10, t[1]], ['touchMove', t[0] - 60, t[1]], ['touchEnd', t[0] - 60, t[1]]]);
  await sleep(700);
  out.swipe = s1 + ' -> ' + await cur();
  const y0 = await ev(`return Math.round(scrollY)`);
  await touch([['touchStart', t[0], t[1] + 60], ...Array.from({ length: 12 }, (_, i) => ['touchMove', t[0], t[1] + 60 - (i + 1) * 20]), ['touchEnd', t[0], t[1] - 180]]);
  await sleep(900);
  out.verticalScroll = y0 + ' -> ' + await ev(`return Math.round(scrollY)`) + ' (photo ' + await cur() + ')';
  await shot('slider-390');
  await page.send('Emulation.setTouchEmulationEnabled', { enabled: false });
  await page.send('Emulation.clearDeviceMetricsOverride');
  return out;
};
