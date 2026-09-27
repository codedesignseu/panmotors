// A page in the block editor (D12), as the administrator: each block previews with the
// front-end markup, lists its fields in the sidebar, and one text field typed in the sidebar shows
// in the live preview. Nothing is saved. Needs dev/.cache/admin-cookies.json.
// Usage: node dev/cdp.mjs dev/tests/editor-about.mjs   (About)
//        PAGE_ID=206 node dev/cdp.mjs dev/tests/editor-about.mjs   (Featured Cars)
import { readFileSync } from 'node:fs';

const cookies = JSON.parse(readFileSync(new URL('../.cache/admin-cookies.json', import.meta.url)));
const ABOUT_ID = Number(process.env.PAGE_ID || process.env.ABOUT_ID || 208);
const EDITS = {
  'pm/page-header': ['header_eyebrow', '.pm-page-header'],
  'pm/story': ['story_eyebrow', '.pm-story'],
  'pm/services': ['services_title', '.pm-services'],
  'pm/values': ['values_intro', '.pm-values'],
  'pm/cta-image': ['ctai_eyebrow', '.pm-cta-image'],
  'pm/cars-grid': [process.env.CARS_FIELD || 'cars_enquire_label', '.pm-cars'],
  'pm/cta-band': ['cta_text', '.pm-cta'],
};

export default async ({ page, sleep, shot }) => {
  for (const c of cookies) await page.send('Network.setCookie', { name: c.name, value: c.value, domain: 'panmotors.local', path: '/' });
  await page.size(1440, 900);
  await page.go(`http://panmotors.local/wp-admin/post.php?post=${ABOUT_ID}&action=edit`);
  for (let i = 0; i < 60; i++) {
    if (await page.eval(`return !!document.querySelector('iframe[name=editor-canvas]')?.contentDocument?.querySelector('.pm-cta-image, .pm-cta')`).catch(() => false)) break;
    await sleep(500);
  }
  await page.eval(`wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false); return 1`);
  await sleep(2500);
  const out = { blocks: [], inserter: null };
  const blocks = await page.eval(`return wp.data.select('core/block-editor').getBlocks().map(b => ({ id: b.clientId, name: b.name }))`);
  const canvas = `document.querySelector('iframe[name=editor-canvas]').contentDocument`;
  for (const b of blocks) {
    const [field, sel] = EDITS[b.name] || [];
    const row = { name: b.name };
    row.preview = await page.eval(`return !!${canvas}.querySelector('${sel}')`);
    await page.eval(`wp.data.dispatch('core/block-editor').selectBlock(${JSON.stringify(b.id)}); wp.data.dispatch('core/edit-post').openGeneralSidebar('edit-post/block'); return 1`);
    for (let i = 0; i < 30; i++) { if (await page.eval(`return !!document.querySelector('.block-editor-block-inspector .acf-field[data-name="${field}"] :is(input, textarea)')`)) break; await sleep(300); }
    row.fields = await page.eval(`return [...document.querySelectorAll('.block-editor-block-inspector .acf-field[data-name]')].filter(f => !f.closest('.acf-row, .acf-clone') ).map(f => f.dataset.name).filter(Boolean)`);
    const value = 'Live preview check ' + b.name.slice(3);
    await page.eval(`const el = document.querySelector('.block-editor-block-inspector .acf-field[data-name="${field}"] :is(input, textarea)'); const set = Object.getOwnPropertyDescriptor(el.constructor.prototype, 'value').set; set.call(el, ${JSON.stringify(value)}); el.dispatchEvent(new Event('input', { bubbles: true })); el.dispatchEvent(new Event('change', { bubbles: true })); return 1`);
    row.live = 'NOT SHOWN';
    for (let i = 0; i < 40; i++) { if (await page.eval(`return ${canvas}.querySelector('${sel}')?.textContent.includes(${JSON.stringify(value)}) || false`)) { row.live = 'shown'; break; } await sleep(300); }
    await page.eval(`${canvas}.querySelector('${sel}')?.scrollIntoView({block: 'start'}); return 1`);
    await sleep(500);
    await shot('editor-' + ABOUT_ID + '-' + b.name.slice(3));
    out.blocks.push(row);
  }
  out.inserter = await page.eval(`return wp.data.select('core/block-editor').getInserterItems().map(i => i.name).filter(n => ['pm/page-header','pm/story','pm/services','pm/cta-image','pm/cars-grid','pm/cta-band'].includes(n))`);
  out.dirtyNotSaved = await page.eval(`return wp.data.select('core/editor').isEditedPostDirty()`);
  return out;
};
