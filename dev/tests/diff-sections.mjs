// Pixel diff per homepage section between two snapshots, each section compared at its own top
// (so a taller section above does not count as a change below it). Plain Node.
// Usage: node dev/tests/diff-sections.mjs before after
import { readFileSync } from 'node:fs';
import { readPng } from '../png.mjs';

const [A, B] = process.argv.slice(2);
const dir = (l) => new URL(`../.cache/snapshots/${l}/`, import.meta.url).pathname;
const sA = JSON.parse(readFileSync(dir(A) + 'snapshot.json')), sB = JSON.parse(readFileSync(dir(B) + 'snapshot.json'));
const out = {};
for (const w of Object.keys(sA.full)) {
  const pa = readPng(`${dir(A)}full-${w}.png`), pb = readPng(`${dir(B)}full-${w}.png`);
  out[w] = {};
  for (const [name, [topA, hA]] of Object.entries(sA.sections[w])) {
    const [topB, hB] = sB.sections[w][name];
    if (Math.round(hA) !== Math.round(hB)) { out[w][name] = `height ${hA} → ${hB}`; continue; }
    let n = 0, big = 0;
    const y0A = Math.round(topA), y0B = Math.round(topB), rows = Math.min(Math.floor(hA), pa.height - y0A, pb.height - y0B);
    for (let y = 0; y < rows; y++) for (let x = 0; x < pa.width; x++) {
      const i = ((y0A + y) * pa.width + x) * 4, j = ((y0B + y) * pb.width + x) * 4;
      const d = Math.max(Math.abs(pa.data[i] - pb.data[j]), Math.abs(pa.data[i + 1] - pb.data[j + 1]), Math.abs(pa.data[i + 2] - pb.data[j + 2]));
      if (d) { n++; if (d > 3) big++; }
    }
    out[w][name] = n ? `${n} px (${big} > 3/255)` : 0;
  }
}
console.log(JSON.stringify(out, null, 1));
