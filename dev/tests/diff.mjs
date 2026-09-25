// Compares two homepage snapshots from dev/tests/snapshot.mjs. Plain Node, no browser.
// Usage: node dev/tests/diff.mjs before after
// Writes diff-<width>.png (changed pixels in red over a faded "after") to the second snapshot folder.
import { readFileSync } from 'node:fs';
import { readPng, writePng } from '../png.mjs';

const [A, B] = process.argv.slice(2);
const dir = (l) => new URL(`../.cache/snapshots/${l}/`, import.meta.url).pathname;
const read = (l, f) => readFileSync(dir(l) + f, 'utf8');
const json = (l, f) => JSON.parse(read(l, f));
const eq = (a, b) => JSON.stringify(a) === JSON.stringify(b);
const report = {};

// First differing line of two texts, with context.
function firstDiff(a, b) {
  const la = a.split('\n'), lb = b.split('\n');
  for (let i = 0; i < Math.max(la.length, lb.length); i++) if (la[i] !== lb[i]) return { line: i + 1, before: (la[i] || '').slice(0, 300), after: (lb[i] || '').slice(0, 300) };
  return null;
}

const mainA = read(A, 'main.html'), mainB = read(B, 'main.html');
const squash = (s) => s.replace(/>\s+</g, '><').trim();
const noCr = (s) => s.replace(/\r/g, '');
report.mainHtml = mainA === mainB ? 'identical'
  : squash(mainA) === squash(mainB) ? 'identical except whitespace between tags'
  : noCr(mainA) === noCr(mainB) ? 'identical except \\r line endings in text'
  : { differs: firstDiff(mainA.replace(/></g, '>\n<'), mainB.replace(/></g, '>\n<')) };

const asA = json(A, 'assets.json'), asB = json(B, 'assets.json');
report.assets = eq(asA, asB) ? 'identical' : { removed: asA.filter((x) => !asB.includes(x)), added: asB.filter((x) => !asA.includes(x)) };

const sA = json(A, 'snapshot.json'), sB = json(B, 'snapshot.json');
report.pixels = {};
for (const w of Object.keys(sA.full)) {
  const pa = readPng(`${dir(A)}full-${w}.png`), pb = readPng(`${dir(B)}full-${w}.png`);
  if (pa.width !== pb.width || pa.height !== pb.height) { report.pixels[w] = `size differs: ${pa.width}x${pa.height} vs ${pb.width}x${pb.height}`; continue; }
  // Two runs of the same page differ by up to 3/255 in a few pixels of scaled photos (GPU
  // resampling). Every difference is counted; those above 3/255 are counted separately.
  let n = 0, big = 0;
  const out = Buffer.alloc(pb.data.length);
  const rows = new Set();
  for (let i = 0; i < pb.data.length; i += 4) {
    const delta = Math.max(Math.abs(pa.data[i] - pb.data[i]), Math.abs(pa.data[i + 1] - pb.data[i + 1]), Math.abs(pa.data[i + 2] - pb.data[i + 2]));
    if (delta > 3) big++;
    if (delta) { n++; rows.add(Math.floor(i / 4 / pb.width)); out[i] = 255; out[i + 1] = 0; out[i + 2] = 0; out[i + 3] = 255; }
    else { out[i] = 255 - (255 - pb.data[i]) / 4; out[i + 1] = 255 - (255 - pb.data[i + 1]) / 4; out[i + 2] = 255 - (255 - pb.data[i + 2]) / 4; out[i + 3] = 255; }
  }
  writePng(`${dir(B)}diff-${w}.png`, { width: pb.width, height: pb.height, data: out });
  const ys = [...rows].sort((a, b) => a - b);
  report.pixels[w] = n ? `${n} differing pixels (${big} by more than 3/255), rows ${ys[0]}–${ys.at(-1)}` : '0 differing pixels';
}

report.sections = {};
for (const w of Object.keys(sA.sections)) {
  const bad = Object.keys(sA.sections[w]).filter((k) => !eq(sA.sections[w][k], sB.sections[w][k]));
  report.sections[w] = bad.length ? bad.map((k) => `${k}: ${JSON.stringify(sA.sections[w][k])} → ${JSON.stringify(sB.sections[w][k])}`) : 'all equal';
}
// Focus stops: same elements in the same order. Tops move a few px between runs of the same
// page (focus scrolling during reveal transitions), so up to 12px counts as the same.
const stop = (s) => s.replace(/ top -?\d+/, '');
const top = (s) => Number((s.match(/ top (-?\d+)/) || [0, 0])[1]);
const kbA = sA.keyboard, kbB = sB.keyboard;
const kbMoved = kbA.map((s, i) => Math.abs(top(s) - top(kbB[i] || ''))).filter((d) => d > 12);
report.keyboard = eq(kbA.map(stop), kbB.map(stop)) && !kbMoved.length ? `same (${kbA.length} stops)` : { before: kbA, after: kbB };
for (const k of ['outline', 'modules', 'reducedMotion', 'reveal', 'lcp', 'preload', 'full']) {
  report[k] = eq(sA[k], sB[k]) ? 'same' : { before: sA[k], after: sB[k] };
}
report.console = sB.console.length ? sB.console : 'clean';
console.log(JSON.stringify(report, null, 2));
