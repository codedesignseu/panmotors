// Compares two dev/tests/snapshot-all.mjs runs page by page (dev/tests/diff.mjs).
// Usage: node dev/tests/diff-all.mjs <labelA> <labelB> [--full]
// Prints one line per page: pixels at each width, and every other check that differs.
import { execFileSync } from 'node:child_process';
import { PAGES } from './pages.mjs';

const [A, B, full] = process.argv.slice(2);
const diff = new URL('./diff.mjs', import.meta.url).pathname;
let clean = true;
for (const name of Object.keys(PAGES)) {
  const r = JSON.parse(execFileSync('node', [diff, `${A}-${name}`, `${B}-${name}`], { encoding: 'utf8', maxBuffer: 1 << 26 }));
  const other = Object.entries(r).filter(([k, v]) => k !== 'pixels' && !['same', 'identical', 'clean', 'all equal'].includes(v) && !/^same \(/.test(v) && !(k === 'sections' && Object.values(v).every((x) => x === 'all equal')));
  const px = Object.entries(r.pixels).map(([w, v]) => `${w}: ${v}`).join(' | ');
  if (other.length || Object.values(r.pixels).some((v) => v !== '0 differing pixels')) clean = false;
  console.log(name.padEnd(16), px, other.length ? ' DIFFERS: ' + other.map(([k]) => k).join(', ') : '');
  if (full && other.length) console.log(JSON.stringify(Object.fromEntries(other), null, 1));
}
console.log(clean ? 'ALL IDENTICAL' : 'DIFFERENCES FOUND');
