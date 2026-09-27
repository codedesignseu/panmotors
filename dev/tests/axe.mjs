// axe-core on every page at 1440 and 390 (WCAG 2.2 A/AA rules and best practices).
// Usage: node dev/tests/axe.mjs [label]   (needs `npm install` in dev/)
// Prints the serious and critical findings per page and width; the full results go to
// dev/.cache/axe/<label>.json. Runs system Chrome through Playwright with a throwaway profile.
// Excluded: .pm-404__code, the giant ghost "404" behind the 404 title. It is aria-hidden pure
// decoration (the title says the same), which WCAG 1.4.3 exempts from the contrast minimum.
import { chromium } from 'playwright';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { PAGES } from './pages.mjs';

const label = process.argv[2] || 'axe';
const AXE = readFileSync(new URL('../node_modules/axe-core/axe.min.js', import.meta.url), 'utf8');
const OUT = new URL('../.cache/axe/', import.meta.url).pathname;
mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' });
const all = {};
let serious = 0;
for (const [name, path] of Object.entries(PAGES)) {
  for (const width of [1440, 390]) {
    for (const motion of ['no-preference', 'reduce']) {
      const ctx = await browser.newContext({ viewport: { width, height: width < 600 ? 844 : 900 }, reducedMotion: motion });
      const page = await ctx.newPage();
      await page.goto('http://panmotors.local/' + path, { waitUntil: 'load' });
      // Scroll through so every reveal has fired and lazy media is in, then back to the top.
      await page.evaluate(async () => { for (let y = 0; y < document.documentElement.scrollHeight; y += 500) { scrollTo(0, y); await new Promise((r) => setTimeout(r, 60)); } scrollTo(0, 0); });
      await page.waitForTimeout(1200);
      await page.addScriptTag({ content: AXE });
      const r = await page.evaluate(() => window.axe.run({ exclude: [['.pm-404__code']] }, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'] }, resultTypes: ['violations', 'incomplete'] }));
      const key = `${name} ${width}${motion === 'reduce' ? ' reduced' : ''}`;
      all[key] = { violations: r.violations, incomplete: r.incomplete.map(({ id, impact, nodes }) => ({ id, impact, nodes: nodes.map((n) => ({ target: n.target, summary: n.failureSummary })) })) };
      const bad = r.violations.filter((v) => ['serious', 'critical'].includes(v.impact));
      serious += bad.length;
      const other = r.violations.filter((v) => !['serious', 'critical'].includes(v.impact));
      console.log(key.padEnd(30), bad.length ? 'SERIOUS/CRITICAL: ' + bad.map((v) => `${v.id} (${v.nodes.length}) ${v.nodes.slice(0, 3).map((n) => n.target.join(' ')).join(' | ')}`).join('; ') : 'none serious', other.length ? ' · minor/moderate: ' + other.map((v) => `${v.id}(${v.nodes.length})`).join(', ') : '');
      await ctx.close();
    }
  }
}
await browser.close();
writeFileSync(`${OUT}${label}.json`, JSON.stringify(all, null, 1));
console.log(serious ? `${serious} serious or critical rule failures` : 'No serious or critical findings');
