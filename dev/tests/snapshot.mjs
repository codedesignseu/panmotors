// Snapshot of the homepage for before/after comparisons (docs/migration-blocks.md §10).
// Usage: LABEL=before node dev/cdp.mjs dev/tests/snapshot.mjs   → dev/.cache/snapshots/<LABEL>/
// Then: node dev/tests/diff.mjs before after
//
// Saves: the served <main> HTML and the list of assets in the whole document (normalised),
// full-page PNGs at 1440 and 390 with reduced motion (deterministic: no video, marquee or fades),
// section tops and heights at 1440/1080/880/390, and the keyboard, reduced-motion, reveal and LCP results.
import { writeFileSync, mkdirSync } from 'node:fs';
import keyboard from './keyboard.mjs';
import reducedMotion from './reduced-motion.mjs';
import lcp from './lcp.mjs';

const URL_HOME = 'http://panmotors.local/';
const OUT = new URL(`../.cache/snapshots/${process.env.LABEL || 'snapshot'}/`, import.meta.url).pathname;
const SECTIONS = { hero: '#top', marquee: '.pm-marquee', floor: '#floor', ways: '#ways', heritage: '#heritage', gallery: '#gallery', live: '#live', showroom: '#showroom', enquire: '#enquire', footer: 'footer' };

// Served HTML, before any JS runs. ?ver= values and nonces change on every edit, so they are masked.
export const normalise = (s) => s
  .replace(/([?&])ver=[^"'&\s]*/g, '$1ver=X')
  .replace(/("nonce"\s*:\s*")[^"]+"/g, '$1X"')
  .replace(/(name="_wpnonce" value=")[^"]+"/g, '$1X"');

const assets = (html) => [...html.matchAll(/<(link|script|style)\b[^>]*>/g)]
  .map((m) => m[0].replace(/\s+/g, ' '))
  .filter((t) => !/rel=["'](shortlink|EditURI|alternate|https:\/\/api\.w\.org\/)["']/.test(t));

export default async ({ page, sleep }) => {
  mkdirSync(OUT, { recursive: true });
  const out = {};

  const html = normalise(await (await fetch(URL_HOME, { headers: { 'Cache-Control': 'no-cache' } })).text());
  const main = html.slice(html.indexOf('<main'), html.indexOf('</main>') + 7);
  writeFileSync(OUT + 'home.html', html);
  writeFileSync(OUT + 'main.html', main);
  writeFileSync(OUT + 'assets.json', JSON.stringify(assets(html), null, 1));
  out.outline = [...main.matchAll(/<h([1-6])\b[^>]*>([\s\S]*?)<\/h\1>/g)].map((m) => 'h' + m[1] + ' ' + m[2].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim());

  // Full-page PNGs, reduced motion.
  out.full = {};
  for (const w of [1440, 390]) {
    const h = w < 600 ? 844 : 900;
    await page.size(w, h);
    await page.media(true);
    await page.go(URL_HOME);
    await page.eval(`document.documentElement.style.scrollBehavior='auto'; const H=document.documentElement.scrollHeight; for (let y=0;y<H;y+=${Math.round(h * 0.6)}){ scrollTo(0,y); await new Promise(r=>setTimeout(r,200)); } scrollTo(0,0); await document.fonts.ready; await Promise.race([Promise.all([...document.images].map(i => i.complete ? 0 : new Promise(r => { i.onload = i.onerror = r; }))), new Promise(r => setTimeout(r, 8000))]); return [...document.images].filter(i => !i.complete).length`);
    await sleep(2000);
    const full = await page.eval(`return document.documentElement.scrollHeight`);
    const r = await page.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width: w, height: full, scale: 1 } });
    writeFileSync(`${OUT}full-${w}.png`, Buffer.from(r.data, 'base64'));
    out.full[w] = full;
  }

  // Section tops and heights, normal motion.
  out.sections = {};
  for (const w of [1440, 1080, 880, 390]) {
    await page.size(w, w < 600 ? 844 : 900);
    await page.media(false);
    await page.go(URL_HOME);
    await sleep(1000);
    out.sections[w] = await page.eval(`
      const o = {};
      for (const [k, s] of Object.entries(${JSON.stringify(SECTIONS)})) { const el = document.querySelector(s); if (!el) { o[k] = null; continue; } const b = el.getBoundingClientRect(); o[k] = [Math.round(b.top + scrollY), Math.round(b.height * 10) / 10]; }
      return o;`);
  }

  // Behaviour.
  out.modules = [...html.matchAll(/<script\b[^>]*\/js\/([a-z-]+)\.js/g)].map((m) => m[1]);
  out.keyboard = (await keyboard({ page, sleep })).map((s) => `${s.tag} ${s.name} [${s.sec}] top ${s.top} ${s.indicator}`);
  out.reducedMotion = await reducedMotion({ page, sleep });
  await page.size(1440, 900);
  await page.media(false);
  await page.go(URL_HOME);
  await page.eval(`document.documentElement.style.scrollBehavior='auto'; const H=document.documentElement.scrollHeight; for (let y=0;y<H;y+=400){ scrollTo(0,y); await new Promise(r=>setTimeout(r,150)); } return 1`);
  await sleep(1500);
  out.reveal = await page.eval(`return [...document.querySelectorAll('[data-rise],[data-rise-l]')].map(e => (e.closest('section')?.id || 'no-id') + ':' + (e.hasAttribute('data-rise-l') ? 'L-' : '') + e.tagName.toLowerCase() + (e.classList.contains('in') ? ' fired' : ' NOT FIRED'))`);
  const l = await lcp({ page, sleep });
  out.lcp = { element: l.lcp.at(-1)?.el, url: l.lcp.at(-1)?.url, poster: l.poster.src };
  out.preload = (html.match(/<link rel="preload" as="image"[^>]*>/) || [''])[0];
  out.console = page.console.slice();

  writeFileSync(OUT + 'snapshot.json', JSON.stringify(out, null, 1));
  return { dir: OUT, full: out.full, modules: out.modules, lcp: out.lcp, keyboardStops: out.keyboard.length, reveal: out.reveal.length, console: out.console };
};
