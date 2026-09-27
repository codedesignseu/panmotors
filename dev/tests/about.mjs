// About page (D12 step 3): header zoom, What We Do tile growth, values hover, reduced motion,
// keyboard order and heading outline. Usage: node dev/cdp.mjs dev/tests/about.mjs
const URL_ABOUT = 'http://panmotors.local/about/';
export default async ({ page, sleep, shot }) => {
  const out = {};
  const hover = async (sel) => {
    const r = await page.eval(`document.documentElement.style.scrollBehavior='auto'; const e=document.querySelector('${sel}'); e.scrollIntoView({block:'center'}); await new Promise(r=>setTimeout(r,300)); const b=e.getBoundingClientRect(); return [b.x+b.width/2, b.y+b.height/2]`);
    await page.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: r[0], y: r[1] });
  };
  await page.size(1440, 900);
  await page.media(false);
  await page.go(URL_ABOUT);
  await sleep(150);
  const zoom = () => page.eval(`return getComputedStyle(document.querySelector('.pm-page-header__media')).transform`);
  out.zoomStart = await zoom();
  await sleep(3600);
  out.zoomEnd = await zoom();
  out.headerHeight = await page.eval(`return Math.round(document.querySelector('.pm-page-header').getBoundingClientRect().height) + ' of viewport 900'`);
  out.h1 = await page.eval(`return [...document.querySelectorAll('h1')].map(h=>h.textContent)`);
  out.outline = await page.eval(`return [...document.querySelectorAll('main h1, main h2, main h3')].map(h=>h.tagName).join(' ')`);
  const widths = () => page.eval(`return [...document.querySelectorAll('.pm-service')].map(e=>Math.round(e.getBoundingClientRect().width))`);
  await page.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: 5, y: 5 });
  out.tilesRest = await widths();
  await hover('.pm-service:nth-child(2)');
  await sleep(1400);
  out.tilesHover = await widths();
  out.tileImgScale = await page.eval(`return getComputedStyle(document.querySelector('.pm-service:nth-child(2) img')).transform`);
  await shot('about-tile-hover');
  await hover('.pm-values--light .pm-value:nth-child(3)');
  await sleep(1200);
  out.valueHover = await page.eval(`const e=document.querySelector('.pm-values--light .pm-value:nth-child(3)'); const s=getComputedStyle(e); return s.backgroundColor + ' / ' + s.color + ' / ' + s.transform`);
  await shot('about-value-hover');
  out.footer = await page.eval(`return document.querySelector('.pm-footer').className`);
  out.currentNav = await page.eval(`return document.querySelector('.pm-nav__link[aria-current=page]')?.textContent`);
  // Keyboard: every focus stop in order.
  await page.go(URL_ABOUT); await sleep(500);
  out.keyboard = [];
  for (let i = 0; i < 20; i++) {
    await page.send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
    await page.send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
    const f = await page.eval(`const a=document.activeElement; return a===document.body?null:(a.tagName+' '+(a.textContent||'').trim().slice(0,28)+' | outline '+getComputedStyle(a).outlineStyle)`);
    if (!f || out.keyboard.includes(f)) break;
    out.keyboard.push(f);
  }
  // Reduced motion: no zoom animation running, content visible.
  await page.media(true);
  await page.go(URL_ABOUT); await sleep(400);
  out.reduced = await page.eval(`return { zoom: getComputedStyle(document.querySelector('.pm-page-header__media')).transform, hidden: [...document.querySelectorAll('[data-rise],[data-rise-l],[data-hero-in]')].filter(e=>getComputedStyle(e).opacity < 1).length }`);
  return out;
};
