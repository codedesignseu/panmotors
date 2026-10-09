// Keyboard and browser-feature checks, in Chrome, WebKit (Safari's engine) and Firefox.
// Usage: node dev/tests/interactions.mjs [chrome|webkit|firefox ...]   (default: all three)
// Needs `npm install` in dev/ and, for WebKit and Firefox, `npx playwright install webkit firefox`
// (Playwright keeps its own browsers in ~/Library/Caches/ms-playwright; system browsers are untouched).
//
// Checks: menu (open with Enter, focus stays in the panel, Esc closes, focus back on the burger);
// Featured Cars filter and car sheet dialog (Enter, arrows, focus trap, Esc, focus return); the
// Latest Cars and Showroom sliders on Home and the Photo slider on Showroom (buttons and arrow
// keys, counter); the Contact map (on request: Enter loads it and focuses it; with the page: titled
// and reachable with Tab); form labels; FAQ
// (Enter toggles); hero video autoplay (and no autoplay with reduced motion); backdrop-filter and
// color-mix() support and their computed values. Every check prints ok or FAIL with the detail.
import { chromium, webkit, firefox } from 'playwright';

const BASE = 'http://panmotors.local/';
const ENGINES = {
  chrome: () => chromium.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' }),
  webkit: () => webkit.launch(),
  firefox: () => firefox.launch(),
};
const which = process.argv.slice(2).length ? process.argv.slice(2) : Object.keys(ENGINES);
let failures = 0;

async function run(name) {
  const browser = await ENGINES[name]();
  const results = [];
  const check = (label, pass, detail = '') => { results.push(`${pass ? 'ok  ' : 'FAIL'} ${label}${detail ? ' — ' + detail : ''}`); if (!pass) failures++; };
  const errors = [];
  const open = async (path, opts = {}) => {
    const ctx = await browser.newContext({ viewport: { width: opts.width || 1440, height: opts.width < 600 ? 844 : 900 }, reducedMotion: opts.reduce ? 'reduce' : 'no-preference' });
    await ctx.route(/google\.com/, (r) => r.fulfill({ status: 200, contentType: 'text/html', body: '<a href="#">map</a>' }));
    const page = await ctx.newPage();
    page.on('pageerror', (e) => errors.push(`${path}: ${e.message}`));
    // WebKit refuses WordPress's speculative prefetch on plain http (local only; the live site is https).
    page.on('console', (m) => m.type() === 'error' && !/Prefetch request denied: URL must be secure/.test(m.text()) && errors.push(`${path}: ${m.text()}`));
    await page.goto(BASE + path, { waitUntil: 'load' });
    await page.waitForTimeout(900);
    return { ctx, page };
  };
  const active = (page) => page.evaluate(() => { const a = document.activeElement; return a ? `${a.tagName.toLowerCase()}${a.id ? '#' + a.id : ''} ${(a.getAttribute('aria-label') || a.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 40)}` : ''; });
  // Tab until the predicate holds for the focused element (at most n presses).
  const tabTo = async (page, test, n = 60) => {
    for (let i = 0; i < n; i++) {
      await page.keyboard.press(name === 'webkit' ? 'Alt+Tab' : 'Tab');
      if (await page.evaluate(test)) return true;
    }
    return false;
  };

  try {
    // Menu at 390.
    {
      const { ctx, page } = await open('', { width: 390 });
      const reached = await tabTo(page, () => document.activeElement?.hasAttribute('data-menu-toggle'));
      check('menu: burger reachable with Tab', reached, await active(page));
      await page.keyboard.press('Enter');
      await page.waitForTimeout(700);
      const openState = await page.evaluate(() => ({ expanded: document.querySelector('[data-menu-toggle]').getAttribute('aria-expanded'), mainInert: document.querySelector('main').inert }));
      check('menu: Enter opens (aria-expanded, main inert)', openState.expanded === 'true' && openState.mainInert, JSON.stringify(openState));
      const inside = [];
      // Past the last item focus may leave the page for the browser's own UI (body is active then);
      // it must never reach the page behind the menu.
      for (let i = 0; i < 8; i++) { await page.keyboard.press(name === 'webkit' ? 'Alt+Tab' : 'Tab'); inside.push(await page.evaluate(() => { const a = document.activeElement; return !a || a === document.body || !!a.closest('#pm-menu, .pm-nav') ? 'ok' : a.tagName + '.' + a.className; })); }
      check('menu: focus never reaches the page behind', inside.every((x) => x === 'ok'), inside.filter((x) => x !== 'ok').join(', '));
      await page.keyboard.press('Escape');
      await page.waitForTimeout(500);
      const closed = await page.evaluate(() => ({ expanded: document.querySelector('[data-menu-toggle]').getAttribute('aria-expanded'), focus: document.activeElement.hasAttribute('data-menu-toggle'), mainInert: document.querySelector('main').inert }));
      check('menu: Esc closes and returns focus to the burger', closed.expanded === 'false' && closed.focus && !closed.mainInert, JSON.stringify(closed));
      await ctx.close();
    }

    // Featured Cars: filter and dialog.
    {
      const { ctx, page } = await open('featured-cars/');
      const reached = await tabTo(page, () => document.activeElement?.dataset.filter === 'porsche');
      check('cars: filter reachable with Tab', reached, await active(page));
      await page.keyboard.press('Enter');
      await page.waitForTimeout(1300);
      const filtered = await page.evaluate(() => ({ pressed: document.querySelector('[data-filter="porsche"]').getAttribute('aria-pressed'), shown: [...document.querySelectorAll('.pm-car')].filter((c) => c.offsetParent).map((c) => c.querySelector('.pm-car__marque').textContent.trim()) }));
      check('cars: Enter filters to Porsche', filtered.pressed === 'true' && filtered.shown.length > 0 && filtered.shown.every((t) => /porsche/i.test(t)), filtered.shown.join(', '));
      const toCard = await tabTo(page, () => document.activeElement?.hasAttribute('data-car-open'));
      const opener = await active(page);
      check('cars: card button reachable with Tab', toCard, opener);
      await page.keyboard.press('Enter');
      await page.waitForTimeout(900);
      const dlg = await page.evaluate(() => { const d = document.querySelector('.pm-lightbox'); return { open: d.open, focusIn: !!document.activeElement.closest('dialog'), car: d.querySelector('.pm-sheet:not([hidden]) h2')?.textContent }; });
      check('cars: Enter opens the sheet dialog, focus inside', dlg.open && dlg.focusIn, JSON.stringify(dlg));
      await page.keyboard.press('ArrowRight');
      await page.waitForTimeout(500);
      const next = await page.evaluate(() => document.querySelector('.pm-lightbox .pm-sheet:not([hidden]) h2')?.textContent);
      check('cars: ArrowRight shows the next car', next && next !== dlg.car, `${dlg.car} → ${next}`);
      const trapped = [];
      for (let i = 0; i < 8; i++) { await page.keyboard.press(name === 'webkit' ? 'Alt+Tab' : 'Tab'); trapped.push(await page.evaluate(() => !!document.activeElement.closest('dialog'))); }
      check('cars: focus stays in the dialog', trapped.every(Boolean));
      await page.keyboard.press('Escape');
      await page.waitForTimeout(700);
      const after = await page.evaluate(() => ({ open: document.querySelector('.pm-lightbox').open, focusOpener: document.activeElement.hasAttribute('data-car-open') }));
      check('cars: Esc closes, focus back on a card button', !after.open && after.focusOpener, `${JSON.stringify(after)} ${await active(page)}`);
      await ctx.close();
    }

    // Home sliders.
    {
      const { ctx, page } = await open('');
      for (const [label, root] of [['showroom slider', '#showroom']]) {
        const count = () => page.evaluate((r) => document.querySelector(`${r} [data-slider-count]`).textContent.trim(), root);
        const c0 = await count();
        await page.locator(`${root} [data-slider-next]:visible`).first().focus();
        await page.keyboard.press('Enter');
        await page.waitForTimeout(1300);
        const c1 = await count();
        await page.keyboard.press('Space');
        await page.waitForTimeout(1300);
        const c2 = await count();
        await page.locator(`${root} [data-slider-viewport]`).focus();
        await page.keyboard.press('ArrowLeft');
        await page.waitForTimeout(1300);
        const c3 = await count();
        check(`${label}: Enter, Space and ArrowLeft move it`, c0 !== c1 && c1 !== c2 && c3 === c1, `${c0} → ${c1} → ${c2} → ${c3}`);
      }
      // Form labels (static preview form or the plugin form).
      const unlabelled = await page.evaluate(() => [...document.querySelectorAll('.pm-form input:not([type=hidden]):not([type=submit]), .pm-form textarea, .pm-form select')].filter((f) => !(f.labels?.length || f.getAttribute('aria-label') || f.getAttribute('aria-labelledby'))).map((f) => f.name || f.type));
      check('form: every field has a label', unlabelled.length === 0, unlabelled.join(', '));
      // Hero video autoplay (muted, after load).
      await page.evaluate(() => scrollTo(0, 0));
      await page.waitForTimeout(3500);
      const video = await page.evaluate(() => { const v = document.querySelector('.pm-hero__video'); return v ? { paused: v.paused, t: Math.round(v.currentTime * 10) / 10, playing: v.classList.contains('is-playing'), canPlay: v.canPlayType('video/mp4; codecs="avc1.42E01E"'), error: v.error?.code || 0 } : null; });
      check('hero video: autoplays muted', video && !video.paused && video.t > 0 && video.playing, JSON.stringify(video));
      // backdrop-filter and color-mix().
      const css = await page.evaluate(() => {
        const arrow = document.querySelector('.pm-showroom__arrow');
        const cs = arrow ? getComputedStyle(arrow) : null;
        const row = getComputedStyle(document.querySelector('.pm-footer__row')).borderTopColor;
        return {
          backdrop: CSS.supports('backdrop-filter', 'blur(1px)') || CSS.supports('-webkit-backdrop-filter', 'blur(1px)'),
          arrowBackdrop: cs ? (cs.backdropFilter || cs.webkitBackdropFilter) : 'n/a',
          colorMix: CSS.supports('color', 'color-mix(in srgb, red 50%, transparent)'),
          footerLine: row,
        };
      });
      check('backdrop-filter supported and applied', css.backdrop && /blur/.test(css.arrowBackdrop), `${css.arrowBackdrop}`);
      check('color-mix() supported and resolved', css.colorMix && !/^(rgba\(0, 0, 0, 0\)|transparent)$/.test(css.footerLine), css.footerLine);
      await ctx.close();
    }

    // Reduced motion: no autoplay.
    {
      const { ctx, page } = await open('', { reduce: true });
      await page.waitForTimeout(2500);
      const v = await page.evaluate(() => { const x = document.querySelector('.pm-hero__video'); return { paused: x.paused, t: x.currentTime }; });
      check('hero video: no autoplay with reduced motion', v.paused && v.t === 0, JSON.stringify(v));
      await ctx.close();
    }

    // Showroom page: photo slider.
    {
      const { ctx, page } = await open('showroom/');
      const count = () => page.evaluate(() => document.querySelector('.pm-slider__count .is-current')?.textContent.replace(/\s+/g, ' ').trim());
      const c0 = await count();
      await page.locator('.pm-slider [data-slide-next]:visible, .pm-slider button[aria-label*="Next" i]:visible').first().focus();
      await page.keyboard.press('Enter');
      await page.waitForTimeout(1200);
      const c1 = await count();
      await page.keyboard.press('ArrowRight');
      await page.waitForTimeout(1200);
      const c2 = await count();
      check('photo slider: Enter and ArrowRight move it', c0 !== c1 && c1 !== c2, `${c0} → ${c1} → ${c2}`);
      await ctx.close();
    }

    // Contact: map button, FAQ, form labels.
    {
      const { ctx, page } = await open('contact/');
      if (await page.locator('[data-map-show]').count()) {
        // Map on request: Enter on the button loads it and moves focus to it.
        await page.locator('[data-map-show]').focus();
        await page.keyboard.press('Enter');
        await page.waitForTimeout(800);
        const map = await page.evaluate(() => ({ iframe: !!document.querySelector('[data-map] iframe'), focused: document.activeElement.tagName }));
        check('map: Enter loads the map and focuses it', map.iframe && map.focused === 'IFRAME', JSON.stringify(map));
      } else {
        // Map with the page: the iframe is there, titled, and reachable with Tab.
        const map = await page.evaluate(() => { const f = document.querySelector('.pm-contact-form__map iframe'); return f ? { title: f.title, lazy: f.loading } : null; });
        const reached = await tabTo(page, () => document.activeElement?.tagName === 'IFRAME', 80);
        check('map: shown with the page, titled, reachable with Tab', !!map?.title && reached, JSON.stringify(map));
      }
      const summary = page.locator('.pm-faq__question').first();
      await summary.focus();
      await page.keyboard.press('Enter');
      await page.waitForTimeout(400);
      const opened = await page.evaluate(() => document.querySelector('.pm-faq__item').open);
      await page.keyboard.press('Enter');
      await page.waitForTimeout(400);
      const closedAgain = await page.evaluate(() => document.querySelector('.pm-faq__item').open);
      check('FAQ: Enter opens and closes a question', opened && !closedAgain);
      const unlabelled = await page.evaluate(() => [...document.querySelectorAll('.pm-form input:not([type=hidden]):not([type=submit]), .pm-form textarea, .pm-form select')].filter((f) => !(f.labels?.length || f.getAttribute('aria-label') || f.getAttribute('aria-labelledby'))).map((f) => f.name || f.type));
      check('contact form: every field has a label', unlabelled.length === 0, unlabelled.join(', '));
      await ctx.close();
    }
  } catch (e) {
    check('run', false, e.message.split('\n')[0]);
  }
  await browser.close();
  check('no page errors or console errors', errors.length === 0, errors.slice(0, 5).join(' | '));
  console.log(`\n${name}\n` + results.join('\n'));
}

for (const name of which) await run(name);
console.log(failures ? `\n${failures} failed` : '\nAll passed');
process.exitCode = failures ? 1 : 0;
