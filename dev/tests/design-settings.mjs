// Design settings (D12, docs/inner-pages.md §1.5): what one setting changes, on the front end and
// in the block editor canvas. Run it once per setting and compare with a run at the defaults.
// Usage: LABEL=accent node dev/cdp.mjs dev/tests/design-settings.mjs
// Needs dev/.cache/admin-cookies.json (wp eval-file dev/tests/auth-cookies.php <admin login>).
// Screenshots: dev/.cache/shots/design-<LABEL>-*.jpg
import { readFileSync } from 'node:fs';

const cookies = JSON.parse(readFileSync(new URL('../.cache/admin-cookies.json', import.meta.url)));
const LABEL = process.env.LABEL || 'default';
const HOME_ID = process.env.HOME_ID || 6;

// Read in a document (the page, or the editor iframe's document as `d`).
const PROBE = `
  const cs = (sel, prop) => { const e = d.querySelector(sel); return e ? getComputedStyle(e)[prop] : null; };
  const root = getComputedStyle(d.documentElement);
  const fonts = [...d.fonts].filter(f => f.status === 'loaded').map(f => f.family.replace(/"/g, '') + ' ' + f.style);
  return {
    tokens: Object.fromEntries(['--ink', '--paper', '--accent', '--surface', '--r', '--font-display', '--font-body', '--h-scale', '--fs-body', '--logo-h', '--logo-h-m'].map(t => [t, root.getPropertyValue(t).trim()])),
    accent: cs('.pm-eyebrow', 'color'),
    footerBg: cs('.pm-footer', 'backgroundColor'),
    headingFont: cs('.pm-title', 'fontFamily'),
    bodyFont: cs('.pm-about__text', 'fontFamily'),
    titleSize: cs('.pm-title', 'fontSize'),
    bodySize: cs('.pm-about__text', 'fontSize'),
    ledeSize: cs('.pm-value__body', 'fontSize'),
    radius: cs('.pm-tile', 'borderTopLeftRadius'),
    logoHeight: cs('.pm-nav__logo-img', 'height'),
    footerLogo: d.querySelector('.pm-footer__logo') ? d.querySelector('.pm-footer__logo').currentSrc.split('/').pop() + ' filter ' + cs('.pm-footer__logo', 'filter') : null,
    fontsLoaded: [...new Set(fonts)],
  };`;

export default async ({ page, sleep, shot }) => {
  for (const c of cookies) await page.send('Network.setCookie', { name: c.name, value: c.value, domain: 'panmotors.local', path: '/' });
  const out = { label: LABEL };
  await page.media(true); // Reduced motion: no video or fades, so screenshots are stable.

  // Front end, logged out view (cookies only matter for the editor): 1440 and 390.
  await page.send('Network.clearBrowserCookies');
  for (const [w, h] of [[1440, 900], [390, 844]]) {
    await page.size(w, h);
    await page.go('http://panmotors.local/');
    await page.eval(`await document.fonts.ready; return 1`);
    await sleep(800);
    out['front' + w] = await page.eval(`const d = document; ${PROBE}`);
    out['front' + w].preload = await page.eval(`return [...document.querySelectorAll('link[rel=preload][as=font]')].map(l => l.href.split('/').pop())`);
    await shot(`design-${LABEL}-front-${w}-top`);
    // The Featured Cars and Values area: headings, body text, radius, accent.
    await page.eval(`scrollTo(0, document.querySelector('#floor').offsetTop); return 1`);
    await sleep(500);
    await shot(`design-${LABEL}-front-${w}-floor`);
    await page.eval(`scrollTo(0, document.documentElement.scrollHeight); return 1`);
    await sleep(500);
    await shot(`design-${LABEL}-front-${w}-footer`);
  }

  // Block editor, Home: the canvas iframe gets main.css and the same inline tokens.
  for (const c of cookies) await page.send('Network.setCookie', { name: c.name, value: c.value, domain: 'panmotors.local', path: '/' });
  await page.size(1440, 900);
  await page.go(`http://panmotors.local/wp-admin/post.php?post=${HOME_ID}&action=edit`);
  for (let i = 0; i < 40; i++) {
    const ready = await page.eval(`const f = document.querySelector('iframe[name=editor-canvas]'); const d = f && f.contentDocument; return !!(d && d.querySelector('.pm-title') && d.querySelector('.pm-about__text'))`);
    if (ready) break;
    await sleep(500);
  }
  await page.eval(`document.querySelector('.components-modal__screen-overlay .components-button[aria-label=Close]')?.click(); return 1`);
  await sleep(1500);
  out.editor = await page.eval(`const d = document.querySelector('iframe[name=editor-canvas]').contentDocument; await d.fonts.ready; ${PROBE}`);
  out.editor.inlineTokens = await page.eval(`const d = document.querySelector('iframe[name=editor-canvas]').contentDocument; return [...d.querySelectorAll('style')].filter(s => s.id === 'pm-main-inline-css' || s.textContent.includes('--logo-h-m:')).length`);
  await shot(`design-${LABEL}-editor-top`);
  await page.eval(`const d = document.querySelector('iframe[name=editor-canvas]').contentDocument; d.querySelector('#floor, .pm-featured')?.scrollIntoView(); return 1`);
  await sleep(800);
  await shot(`design-${LABEL}-editor-floor`);
  return out;
};
