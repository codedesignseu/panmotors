// Snapshot of the homepage for before/after comparisons (docs/migration-blocks.md §10).
// Usage: LABEL=before node dev/cdp.mjs dev/tests/snapshot.mjs   → dev/.cache/snapshots/<LABEL>/
// Another page: PAGE=about/ LABEL=about-before ... (its sections are listed in page order; the
// homepage-only reduced-motion check is skipped). Every page at once: node dev/tests/snapshot-all.mjs <label>.
// Then: node dev/tests/diff.mjs before after
//
// Saves: the served <main> HTML and the list of assets in the whole document (normalised),
// full-page PNGs at 1440 and 390 with reduced motion, section tops and heights at
// 1440/1080/880/390, and the keyboard, reduced-motion, reveal and LCP results.
//
// The PNGs are deterministic: two runs of unchanged code give 0 differing pixels. Before each
// capture settle() waits for every image in the page's width to load and decode(), waits for
// document.fonts.ready, pauses every video on its poster, and finishes (or, when infinite,
// cancels) every animation and transition; dev/cdp.mjs renders without the GPU.
import { writeFileSync, mkdirSync } from 'node:fs';
import keyboard from './keyboard.mjs';
import reducedMotion from './reduced-motion.mjs';
import lcp from './lcp.mjs';

const PAGE = process.env.PAGE || '';
const URL_HOME = 'http://panmotors.local/' + PAGE;
const OUT = new URL(`../.cache/snapshots/${process.env.LABEL || 'snapshot'}/`, import.meta.url).pathname;
const SECTIONS = { hero: '#top', marquee: '.pm-marquee', floor: '#floor', ways: '#ways', heritage: '#heritage', gallery: '#gallery', live: '#live', showroom: '#showroom', enquire: '#enquire', footer: 'footer' };

// Served HTML, before any JS runs. ?ver= values and nonces change on every edit, so they are masked.
export const normalise = (s) => s
  .replace(/\/themes\/panmotors-[a-z]+\//g, '/themes/panmotors/') // A baseline taken from a worktree theme folder.
  .replace(/([?&])ver=[^"'&\s]*/g, '$1ver=X')
  .replace(/("nonce"\s*:\s*")[^"]+"/g, '$1X"')
  .replace(/(name="_wpnonce" value=")[^"]+"/g, '$1X"');

const assets = (html) => [...html.matchAll(/<(link|script|style)\b[^>]*>/g)]
  .map((m) => m[0].replace(/\s+/g, ' '))
  .filter((t) => !/rel=["'](shortlink|EditURI|alternate|https:\/\/api\.w\.org\/)["']/.test(t));

// Puts the page in a fixed visual state for a capture (see the header).
export const SETTLE = `
  if (!document.getElementById('pm-snapshot-still')) {
    const st = document.createElement('style');
    st.id = 'pm-snapshot-still';
    st.textContent = '*,*::before,*::after{transition-duration:0s!important;transition-delay:0s!important;animation-delay:0s!important;caret-color:transparent!important}';
    document.head.append(st);
  }
  document.documentElement.style.scrollBehavior = 'auto';
  for (const v of document.querySelectorAll('video')) { v.autoplay = false; v.pause(); if (v.currentSrc || v.getAttribute('src')) v.load(); }
  await document.fonts.ready;
  // Every image in the page's width (the scroll pass before this started the lazy ones; slides
  // outside it are off screen). loading is left alone: switching a sizes="auto" image to eager
  // makes Chrome pick another file.
  const inWidth = [...document.images].filter((i) => { const b = i.getBoundingClientRect(); return b.width && b.right > 0 && b.left < innerWidth; });
  // A lazy image the scroll pass did not start is scrolled to; then wait until each has pixels.
  const ready = (i) => i.complete && i.naturalWidth > 0;
  const until = Date.now() + 20000;
  for (const i of inWidth) {
    while (!ready(i) && Date.now() < until) {
      i.scrollIntoView({ block: 'center' });
      await new Promise((r) => setTimeout(r, 150));
    }
  }
  scrollTo(0, 0);
  await Promise.all(inWidth.map((i) => i.decode().catch(() => 0)));
  for (const a of document.getAnimations()) { if (a.effect?.getComputedTiming().endTime === Infinity) a.cancel(); else a.finish(); }
  await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
  return inWidth.filter((i) => !ready(i)).length;
`;

export default async ({ page, sleep }) => {
  mkdirSync(OUT, { recursive: true });
  const out = {};
  // Start from an empty browser cache: which srcset candidate the browser picks can depend on
  // what is already cached, so every run starts from the same state.
  await page.send('Network.enable');
  await page.send('Network.clearBrowserCache');
  // Then fill it with every image candidate, so each run sees the same, complete cache.
  await page.size(1440, 900);
  await page.go(URL_HOME);
  out.warmed = await page.eval(`const urls = new Set(); for (const i of document.images) { if (i.getAttribute('src')) urls.add(i.src); (i.getAttribute('srcset') || '').split(',').forEach(c => { const u = c.trim().split(/\\s+/)[0]; if (u) urls.add(new URL(u, location.href).href); }); } await Promise.all([...urls].map(u => fetch(u).then(r => r.blob()).catch(() => 0))); return urls.size`);

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
    await page.eval(`document.documentElement.style.scrollBehavior='auto'; const H=document.documentElement.scrollHeight; for (let y=0;y<H;y+=${Math.round(h * 0.6)}){ scrollTo(0,y); await new Promise(r=>setTimeout(r,200)); } scrollTo(0,0); return 1`);
    out.notLoaded = await page.eval(SETTLE);
    await sleep(2000);
    const full = await page.eval(`return document.documentElement.scrollHeight`);
    // A full-page capture resizes the viewport, and images with sizes="auto" may pick a new
    // candidate. The first capture triggers that; settle again, then keep the second.
    const capture = () => page.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width: w, height: full, scale: 1 } });
    await capture();
    await page.eval(SETTLE);
    await sleep(1500);
    const r = await capture();
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
      const map = ${PAGE ? 'null' : JSON.stringify(SECTIONS)} || Object.fromEntries([...document.querySelectorAll('main > section, main .pm-blocks > section, main > .pm-blocks > *, footer')].map((e, i) => [i + ':' + (e.className.split(' ')[0] || e.tagName), null]));
      if (${PAGE ? 'true' : 'false'}) { [...document.querySelectorAll('main > section, main .pm-blocks > section, main > .pm-blocks > *, footer')].forEach((el, i) => { const b = el.getBoundingClientRect(); o[i + ':' + (el.className.split(' ')[0] || el.tagName)] = [Math.round(b.top + scrollY), Math.round(b.height * 10) / 10]; }); return o; }
      for (const [k, s] of Object.entries(map)) { const el = document.querySelector(s); if (!el) { o[k] = null; continue; } const b = el.getBoundingClientRect(); o[k] = [Math.round(b.top + scrollY), Math.round(b.height * 10) / 10]; }
      return o;`);
  }

  // Behaviour.
  out.modules = [...html.matchAll(/<script\b[^>]*\/js\/([a-z-]+)\.js/g)].map((m) => m[1]);
  out.keyboard = (await keyboard({ page, sleep })).map((s) => `${s.tag} ${s.name} [${s.sec}] top ${s.top} ${s.indicator}`);
  out.reducedMotion = PAGE ? 'skipped (homepage only)' : await reducedMotion({ page, sleep });
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
