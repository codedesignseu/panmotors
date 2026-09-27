// Lighthouse on every page, mobile and desktop: performance score, LCP, CLS and TBT, plus the
// accessibility, best-practices and SEO scores. Median of RUNS runs (default 3) per page and form factor.
// Usage: node dev/tests/lighthouse.mjs <label>   (needs `npm install` in dev/)
// Writes dev/.cache/lighthouse/<label>.json and prints a table. Uses system Chrome with a throwaway
// profile (chrome-launcher). Local: no CDN, no HTTP/2, so absolute numbers run a little pessimistic.
import lighthouse from 'lighthouse';
import desktopConfig from 'lighthouse/core/config/desktop-config.js';
import * as chromeLauncher from 'chrome-launcher';
import { writeFileSync, mkdirSync } from 'node:fs';
import { PAGES } from './pages.mjs';

const label = process.argv[2] || 'lighthouse';
const RUNS = Number(process.env.RUNS || 3);
const OUT = new URL('../.cache/lighthouse/', import.meta.url).pathname;
mkdirSync(OUT, { recursive: true });
const only = process.env.ONLY ? process.env.ONLY.split(',') : null;

const chrome = await chromeLauncher.launch({ chromePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', chromeFlags: ['--headless=new'] });
const median = (xs) => [...xs].sort((a, b) => a - b)[Math.floor(xs.length / 2)];
const results = {};
try {
  for (const [name, path] of Object.entries(PAGES)) {
    if (only && !only.includes(name)) continue;
    for (const form of ['mobile', 'desktop']) {
      const runs = [];
      for (let i = 0; i < RUNS; i++) {
        const r = await lighthouse('http://panmotors.local/' + path, { port: chrome.port, output: 'json', logLevel: 'error', onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'] }, form === 'desktop' ? desktopConfig : undefined);
        const a = r.lhr.audits;
        runs.push({
          perf: Math.round(r.lhr.categories.performance.score * 100),
          lcp: Math.round(a['largest-contentful-paint'].numericValue),
          cls: Math.round(a['cumulative-layout-shift'].numericValue * 1000) / 1000,
          tbt: Math.round(a['total-blocking-time'].numericValue),
          fcp: Math.round(a['first-contentful-paint'].numericValue),
          a11y: Math.round(r.lhr.categories.accessibility.score * 100),
          bp: Math.round(r.lhr.categories['best-practices'].score * 100),
          seo: Math.round(r.lhr.categories.seo.score * 100),
          lcpElement: a['largest-contentful-paint-element']?.details?.items?.[0]?.items?.[0]?.node?.snippet?.slice(0, 120),
          failed: Object.values(a).filter((x) => x.score !== null && x.score < 0.9 && ['binary', 'numeric', 'metricSavings'].includes(x.scoreDisplayMode) && !['largest-contentful-paint', 'first-contentful-paint', 'speed-index', 'total-blocking-time', 'interactive', 'cumulative-layout-shift', 'max-potential-fid'].includes(x.id)).map((x) => x.id),
        });
      }
      const m = Object.fromEntries(['perf', 'lcp', 'cls', 'tbt', 'fcp', 'a11y', 'bp', 'seo'].map((k) => [k, median(runs.map((r) => r[k]))]));
      m.lcpElement = runs[0].lcpElement;
      m.failed = [...new Set(runs.flatMap((r) => r.failed))];
      results[`${name} ${form}`] = m;
      console.log(`${name} ${form}`.padEnd(24), `perf ${m.perf}`.padEnd(9), `LCP ${m.lcp}ms`.padEnd(13), `CLS ${m.cls}`.padEnd(10), `TBT ${m.tbt}ms`.padEnd(11), `a11y ${m.a11y} bp ${m.bp} seo ${m.seo}`, m.failed.length ? ' flags: ' + m.failed.join(', ') : '');
    }
  }
} finally {
  await chrome.kill();
}
writeFileSync(`${OUT}${label}.json`, JSON.stringify(results, null, 1));
