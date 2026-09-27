// Featured Cars (D12 step 4): filters, car sheet dialog, keyboard, hover, reduced motion, no JS.
// Usage: node dev/cdp.mjs dev/tests/cars.mjs   Screenshots: dev/.cache/shots/cars-*.jpg
const URL_CARS = 'http://panmotors.local/featured-cars/';
export default async ({ page, sleep, shot }) => {
  const out = {};
  const ev = (js) => page.eval(js);
  const tab = async (shift = false) => { await page.send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9, modifiers: shift ? 8 : 0 }); await page.send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9, modifiers: shift ? 8 : 0 }); await sleep(120); };
  const key = async (k, code, vk) => { await page.send('Input.dispatchKeyEvent', { type: 'keyDown', key: k, code, windowsVirtualKeyCode: vk, ...(k === 'Enter' ? { text: '\r' } : {}) }); await page.send('Input.dispatchKeyEvent', { type: 'keyUp', key: k, code, windowsVirtualKeyCode: vk }); await sleep(250); };
  const active = () => ev(`const a=document.activeElement; return (a.tagName + ' ' + (a.getAttribute('aria-label') || a.textContent || '').replace(/\\s+/g,' ').trim()).slice(0, 60)`);
  const state = () => ev(`const d=document.querySelector('.pm-lightbox'); const s=d.querySelector('.pm-sheet:not([hidden])'); return { open: d.open, car: s?.querySelector('h2').textContent, counter: s?.querySelector('.pm-sheet__counter').textContent, locked: document.documentElement.classList.contains('pm-lb-open'), scrollLocked: getComputedStyle(document.documentElement).overflow }`);

  await page.size(1440, 900);
  await page.media(false);
  await page.go(URL_CARS);
  await sleep(1500);
  out.filtersShown = await ev(`return !document.querySelector('[data-cars-filters]').hidden`);
  out.pills = await ev(`return [...document.querySelectorAll('.pm-cars__filter')].map(b => b.textContent.replace(/\\s+/g,' ').trim() + (b.getAttribute('aria-pressed') === 'true' ? ' [on]' : ''))`);

  // Filter: Porsche. Fade .38s, then only Porsche cards, rows 3.
  await ev(`document.querySelector('[data-filter="porsche"]').click(); return 1`);
  await sleep(150);
  out.midFade = await ev(`return getComputedStyle(document.querySelector('.pm-cars__grid')).opacity`);
  await sleep(1100);
  out.porsche = await ev(`return { visible: [...document.querySelectorAll('.pm-car')].filter(c => c.offsetParent).map(c => c.querySelector('h2').textContent), rows: [...document.querySelectorAll('.pm-cars__row')].map(r => r.children.length), pressed: document.querySelector('[data-filter="porsche"]').getAttribute('aria-pressed'), opacity: getComputedStyle(document.querySelector('.pm-cars__grid')).opacity }`);
  await shot('cars-filter-porsche');

  // Open the second Porsche with a click; next/prev stay within Porsche.
  await ev(`document.querySelectorAll('.pm-car:not([hidden]) [data-car-open]')[1].scrollIntoView({block:'center'}); return 1`);
  await sleep(300);
  await ev(`[...document.querySelectorAll('.pm-car')].filter(c=>c.offsetParent)[1].querySelector('[data-car-open]').click(); return 1`);
  await sleep(900);
  out.open = await state();
  await shot('cars-dialog');
  await key('ArrowRight', 'ArrowRight', 39);
  out.next = await state();
  await key('ArrowRight', 'ArrowRight', 39);
  out.wrap = await state();
  await ev(`document.querySelector('.pm-sheet:not([hidden]) [data-lb-prev]').click(); return 1`); await sleep(200);
  out.prevButton = await state();
  // Focus trap: tab many times, focus stays in the dialog.
  const inDialog = [];
  for (let i = 0; i < 8; i++) { await tab(); inDialog.push(await ev(`return !!document.activeElement.closest('dialog')`)); }
  out.focusTrapped = inDialog.every(Boolean);
  await key('Escape', 'Escape', 27);
  out.afterEsc = { ...(await state()), focus: await active() };

  // Keyboard only: reload, tab to the pills and a card, Enter opens, Esc returns focus to it.
  await page.go(URL_CARS); await sleep(1200);
  const stops = [];
  for (let i = 0; i < 30; i++) { await tab(); stops.push(await active()); if (stops.at(-1).startsWith('BUTTON Open the car sheet') ) break; }
  out.tabStops = stops;
  await key('Enter', 'Enter', 13);
  await sleep(700);
  out.enterOpens = { ...(await state()), focus: await active() };
  await key('Escape', 'Escape', 27);
  out.escFocus = await active();

  // Hover growth and spec line.
  await ev(`document.activeElement.blur(); return 1`);
  await ev(`document.documentElement.style.scrollBehavior='auto'; document.querySelector('.pm-car').scrollIntoView({block:'center'}); return 1`); await sleep(400);
  const r = await ev(`const b=document.querySelectorAll('.pm-car')[1].getBoundingClientRect(); return [b.x+b.width/2, b.y+b.height/2]`);
  await page.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: r[0], y: r[1] }); await sleep(1400);
  out.hover = await ev(`const c=[...document.querySelectorAll('.pm-cars__row')][0].children; return { widths: [...c].map(e=>Math.round(e.getBoundingClientRect().width)), spec: getComputedStyle(c[1].querySelector('.pm-car__spec')).opacity, zoom: getComputedStyle(c[1].querySelector('img')).transform }`);
  await shot('cars-hover');

  // Reduced motion: no fade, no zoom, dialog without animation.
  await page.media(true);
  await page.go(URL_CARS); await sleep(800);
  await ev(`document.querySelector('[data-filter="ferrari"]').click(); return 1`); await sleep(60);
  out.reduced = await ev(`return { opacityRightAway: getComputedStyle(document.querySelector('.pm-cars__grid')).opacity, visible: [...document.querySelectorAll('.pm-car')].filter(c => c.offsetParent).length, fading: document.querySelector('.pm-cars__grid').classList.contains('is-fading'), zoomTransition: getComputedStyle(document.querySelector('.pm-car img')).transitionDuration }`);

  // Without JS: every car, no buttons.
  await page.send('Emulation.setScriptExecutionDisabled', { value: true });
  await page.go(URL_CARS); await sleep(800);
  out.noJs = await ev(`return 1`).catch(() => 'eval blocked');
  await page.send('Emulation.setScriptExecutionDisabled', { value: false });
  const html = await (await fetch(URL_CARS)).text();
  out.noJsHtml = { filtersHidden: /data-cars-filters[^>]*hidden/.test(html), cards: (html.match(/class="pm-car[ "]/g) || []).length, sheets: (html.match(/data-sheet=/g) || []).length, h1: (html.match(/<h1/g) || []).length, schema: /"@type":\s*"(Car|Product|Offer)"/.test(html) };
  return out;
};
