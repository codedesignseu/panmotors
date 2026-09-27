// Contact (D12 step 6): the map loads nothing from Google before "Show map"; after the click the
// map loads, focus moves to it and the hover filter works. Keyboard stops, an FAQ item, reduced
// motion. Usage: node dev/cdp.mjs dev/tests/contact.mjs   Screenshots: dev/.cache/shots/contact-*.jpg
const URL_CONTACT = 'http://panmotors.local/contact/';
export default async ({ page, sleep, shot }) => {
  const out = {};
  const ev = (js) => page.eval(js);
  const requests = [];
  page.on((m) => {
    if (m.method === 'Network.requestWillBeSent') requests.push(m.params.request.url);
  });
  await page.send('Network.enable');
  await page.send('Network.clearBrowserCache');
  await page.send('Network.clearBrowserCookies');
  await page.size(1440, 900);
  await page.media(false);
  await page.go(URL_CONTACT);
  await ev(`document.documentElement.style.scrollBehavior='auto'; const H=document.documentElement.scrollHeight; for (let y=0;y<H;y+=400){ scrollTo(0,y); await new Promise(r=>setTimeout(r,150)); } return 1`);
  await sleep(1500);
  const google = (list) => list.filter((u) => /google\.|gstatic\.|googleapis\./.test(u));
  out.googleBeforeClick = google(requests);
  out.requestsBeforeClick = requests.length;

  // Keyboard: tab to the button, press Enter.
  await ev(`scrollTo(0,0); return 1`);
  const stops = [];
  for (let i = 0; i < 60; i++) {
    await page.send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
    await page.send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
    await sleep(80);
    const s = await ev(`const a=document.activeElement; if (a===document.body) return null; const cs=getComputedStyle(a); const sec=a.closest('section')?.className.split(' ')[0] || a.closest('header,footer,nav')?.tagName; return sec + ' | ' + a.tagName + ' ' + (a.getAttribute('aria-label') || a.textContent || a.placeholder || '').replace(/\\s+/g,' ').trim().slice(0,34) + ' | ' + (cs.outlineStyle !== 'none' ? 'outline' : (cs.backgroundColor)) `);
    if (!s || stops.includes(s)) break;
    stops.push(s);
    if (s.includes('Show map')) break;
  }
  out.stopsToMap = stops.filter((s) => !/^(HEADER|NAV)/.test(s) && !s.startsWith('undefined'));
  await page.send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13, text: '\r' });
  await page.send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13 });
  await sleep(3500);
  out.afterClick = await ev(`const f=document.querySelector('.pm-contact-form__iframe'); return { iframe: !!f, title: f?.title, focused: document.activeElement === f, placeholderGone: !document.querySelector('[data-map-placeholder]'), filter: f && getComputedStyle(f).filter }`);
  out.googleAfterClick = google(requests).map((u) => u.split('?')[0]).slice(0, 4);
  await ev(`document.querySelector('.pm-contact-form__map').scrollIntoView({block:'center'}); return 1`); await sleep(500);
  const m = await ev(`const r=document.querySelector('.pm-contact-form__map').getBoundingClientRect(); return [r.x+r.width/2, r.y+r.height/2]`);
  await page.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: m[0], y: m[1] }); await sleep(1300);
  out.hoverFilter = await ev(`return getComputedStyle(document.querySelector('.pm-contact-form__iframe')).filter`);
  await shot('contact-map-loaded');
  await page.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: 5, y: 5 }); await sleep(1300);
  out.outFilter = await ev(`return getComputedStyle(document.querySelector('.pm-contact-form__iframe')).filter`);

  // FAQ: open one with the keyboard (focus the summary, Enter).
  await ev(`document.querySelectorAll('.pm-faq__question')[2].focus(); document.querySelectorAll('.pm-faq__question')[2].scrollIntoView({block:'center'}); return 1`); await sleep(300);
  await page.send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13, text: '\r' });
  await page.send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13 });
  await sleep(700);
  out.faq = await ev(`const d=document.querySelectorAll('.pm-faq__item')[2]; return { open: d.open, colour: getComputedStyle(d.querySelector('summary')).color, focus: getComputedStyle(d.querySelector('summary')).outlineStyle, answer: d.querySelector('.pm-faq__answer').textContent.slice(0,40) }`);
  await shot('contact-faq-open');
  out.faqStops = await ev(`return document.querySelectorAll('.pm-faq__question').length`);

  // Reduced motion: hover a row, no indent.
  await page.media(true);
  await page.go(URL_CONTACT); await sleep(800);
  const r = await ev(`const e=document.querySelector('.pm-contact-rows__row'); const b=e.getBoundingClientRect(); return [b.x+b.width/2, b.y+b.height/2]`);
  await page.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: r[0], y: r[1] }); await sleep(200);
  out.reducedRow = await ev(`const s=getComputedStyle(document.querySelector('.pm-contact-rows__row')); return s.paddingLeft + ' ' + s.backgroundColor + ' ' + s.transitionDuration`);
  await page.media(false);
  await page.go(URL_CONTACT); await sleep(800);
  await page.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: r[0], y: r[1] }); await sleep(900);
  out.normalRow = await ev(`const s=getComputedStyle(document.querySelector('.pm-contact-rows__row')); return s.paddingLeft + ' ' + s.backgroundColor`);
  out.pill = await ev(`const p=document.querySelector('.pm-nav__contact'); const s=getComputedStyle(p); return p.getAttribute('aria-current') + ' ' + s.backgroundColor + ' / ' + s.color`);
  return out;
};
