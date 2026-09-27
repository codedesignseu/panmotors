// Snapshots every page (dev/tests/snapshot.mjs), one headless Chrome per page, in turn.
// Usage: node dev/tests/snapshot-all.mjs <label>   → dev/.cache/snapshots/<label>-<page>/
// Compare two runs: node dev/tests/diff-all.mjs <labelA> <labelB>
import { execFileSync } from 'node:child_process';
import { PAGES } from './pages.mjs';

const label = process.argv[2];
if (!label) {
  console.error('Usage: node dev/tests/snapshot-all.mjs <label>');
  process.exit(1);
}
const cdp = new URL('../cdp.mjs', import.meta.url).pathname;
const snapshot = new URL('./snapshot.mjs', import.meta.url).pathname;
for (const [name, path] of Object.entries(PAGES)) {
  const env = { ...process.env, PAGE: path, LABEL: `${label}-${name}` };
  const out = execFileSync('node', [cdp, snapshot], { env, encoding: 'utf8', maxBuffer: 1 << 26 });
  const r = JSON.parse(out.slice(out.indexOf('{')));
  console.log(name.padEnd(16), JSON.stringify(r.out?.full), 'console:', (r.console || []).length);
}
