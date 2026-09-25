// The client's editing flow in the block editor, as an Editor (D11). Development only.
// Needs a test Editor and dev/.cache/editor-cookies.json from dev/tests/auth-cookies.php:
//   wp user create pm-editor-test pm-editor-test@example.test --role=editor --user_pass=...
//   wp eval-file dev/tests/auth-cookies.php pm-editor-test > dev/.cache/editor-cookies.json
//   node dev/cdp.mjs dev/tests/editor-blocks.mjs
//   wp user delete pm-editor-test --reassign=1 --yes      (always --reassign: see migration-blocks §0)
//
// Checks, on Home: every block previews with the same text as the front end; one field per block
// edited through the block sidebar shows on the site and is restored; two blocks reordered and put
// back; a block hidden and shown again (its script unloads too); the Our Values synced pattern
// edited once; a car added under Cars shows in Featured and Latest Cars, then is deleted.
import { readFileSync } from 'node:fs';

const cookies = JSON.parse(readFileSync(new URL('../.cache/editor-cookies.json', import.meta.url)));
const SITE = 'http://panmotors.local/';
const ADMIN = SITE + 'wp-admin/';
const HOME_ID = Number(process.env.HOME_ID || 6);
const PATTERN_ID = Number(process.env.PATTERN_ID || 567);

// One field per block, edited through the sidebar. Marquee has no fields (Pan Motors settings).
const EDITS = {
  'pm/hero': 'hero_eyebrow',
  'pm/featured-cars': 'featured_title',
  'pm/about': 'about_eyebrow',
  'pm/latest-cars': 'latest_title',
  'pm/live': 'live_title',
  'pm/showroom': 'showroom_intro',
  'pm/enquire': 'enquire_intro',
};
const SECTION = {
  'pm/hero': '#top', 'pm/marquee': '.pm-marquee', 'pm/featured-cars': '#floor', 'pm/values': '#ways', 'pm/about': '#heritage',
  'pm/latest-cars': '#gallery', 'pm/live': '#live', 'pm/showroom': '#showroom', 'pm/enquire': '#enquire',
};

const html = async (path = '') => (await fetch(SITE + path, { headers: { 'Cache-Control': 'no-cache' } })).text();
const order = (h) => [...h.matchAll(/<section[^>]*?(?:id="([a-z]+)"|class="(pm-marquee)")/g)].map((m) => m[1] || m[2]);

export default async ({ page, sleep, shot }) => {
  for (const c of cookies) await page.send('Network.setCookie', { name: c.name, value: c.value, domain: 'panmotors.local', path: '/' });
  await page.size(1440, 900);
  const out = { preview: {}, fields: {}, shots: [] };

  const open = async (id) => {
    await page.go(`${ADMIN}post.php?post=${id}&action=edit`);
    for (let i = 0; i < 60; i++) {
      const ready = await page.eval(`return !!(window.wp?.data?.select('core/block-editor')?.getBlocks().length && document.querySelector('iframe[name=editor-canvas]')?.contentDocument?.querySelector('section'))`).catch(() => false);
      if (ready) break;
      await sleep(500);
    }
    await page.eval(`wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false); wp.data.dispatch('core/preferences').set('core', 'fixedToolbar', false); return 1`);
    await sleep(3000); // ACF previews render after load.
  };
  const blocks = () => page.eval(`return wp.data.select('core/block-editor').getBlocks().map(b => ({ id: b.clientId, name: b.name, data: b.attributes.data || null, hidden: b.attributes.metadata?.blockVisibility === false }))`);
  const save = async () => {
    await page.eval(`await wp.data.dispatch('core/editor').savePost(); for (let i = 0; i < 100 && wp.data.select('core/editor').isSavingPost(); i++) await new Promise(r => setTimeout(r, 200)); return 1`);
    await sleep(800);
    return page.eval(`return wp.data.select('core/editor').didPostSaveRequestSucceed() ? 'saved' : 'SAVE FAILED'`);
  };
  // Edit a field the way the client does: select the block, type in its sidebar field.
  const typeInSidebar = async (clientId, name, value) => {
    await page.eval(`wp.data.dispatch('core/block-editor').selectBlock(${JSON.stringify(clientId)}); wp.data.dispatch('core/edit-post').openGeneralSidebar('edit-post/block'); return 1`);
    for (let i = 0; i < 30; i++) {
      if (await page.eval(`return !!document.querySelector('.block-editor-block-inspector .acf-field[data-name="${name}"] :is(input, textarea)')`)) break;
      await sleep(300);
    }
    const ok = await page.eval(`
      const el = document.querySelector('.block-editor-block-inspector .acf-field[data-name="${name}"] :is(input, textarea)');
      if (!el) return false;
      const set = Object.getOwnPropertyDescriptor(el.constructor.prototype, 'value').set;
      set.call(el, ${JSON.stringify(value)});
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
      return true;`);
    for (let i = 0; i < 30; i++) {
      // ACF keeps block data by field name or by field key; either way the value is in it.
      const stored = await page.eval(`return JSON.stringify(wp.data.select('core/block-editor').getBlockAttributes(${JSON.stringify(clientId)})?.data || {})`);
      if (Object.values(JSON.parse(stored)).includes(value)) return ok ? 'stored' : 'NO FIELD';
      await sleep(300);
    }
    return 'NOT STORED';
  };

  // 1. Home in the editor: every block previews like the front end.
  await open(HOME_ID);
  const front = await html();
  out.blocks = (await blocks()).map((b) => b.name);
  out.heroLocked = await page.eval(`const b = wp.data.select('core/block-editor').getBlocks()[0]; return b.name + ' ' + JSON.stringify(b.attributes.lock || {}) + ' canRemove=' + wp.data.select('core/block-editor').canRemoveBlock(b.clientId) + ' canMove=' + wp.data.select('core/block-editor').canMoveBlock(b.clientId)`);
  const text = (s) => s.replace(/\s+/g, ' ').trim();
  for (const [name, sel] of Object.entries(SECTION)) {
    const inEditor = await page.eval(`const d = document.querySelector('iframe[name=editor-canvas]').contentDocument; const s = d.querySelector('${sel}'); return s ? { text: s.textContent, cls: s.className } : null`);
    const m = front.match(new RegExp(`<section[^>]*${sel.startsWith('#') ? `id="${sel.slice(1)}"` : 'class="pm-marquee'}[\\s\\S]*?</section>`));
    const frontText = m ? text(m[0].replace(/<[^>]+>/g, ' ').replace(/&[a-z#0-9]+;/g, (e) => ({ '&amp;': '&', '&rarr;': '→', '&larr;': '←', '&darr;': '↓', '&#9829;': '♥', '&#9998;': '✎' }[e] || e))) : null;
    const bare = (s) => s.replace(/\s+/g, '');
    out.preview[name] = !inEditor ? 'MISSING IN EDITOR'
      : text(inEditor.text) === frontText ? 'same text as the site'
      : bare(inEditor.text) === bare(frontText || '') ? 'same text as the site (spacing between tags differs)'
      : { editor: text(inEditor.text).slice(0, 160), site: (frontText || '').slice(0, 160) };
  }
  out.shots.push(await shot('editor-home-top'));
  // A selected block with its fields in the sidebar.
  const fc = (await blocks()).find((b) => b.name === 'pm/featured-cars');
  await page.eval(`wp.data.dispatch('core/block-editor').selectBlock(${JSON.stringify(fc.id)}); wp.data.dispatch('core/edit-post').openGeneralSidebar('edit-post/block'); document.querySelector('iframe[name=editor-canvas]').contentDocument.querySelector('#floor').scrollIntoView(); return 1`);
  await sleep(2500);
  out.shots.push(await shot('editor-home-featured-selected'));
  await page.eval(`wp.data.dispatch('core/block-editor').clearSelectedBlock(); return 1`);
  for (const sel of ['#floor', '#heritage', '#showroom']) {
    await page.eval(`document.querySelector('iframe[name=editor-canvas]').contentDocument.querySelector('${sel}').scrollIntoView(); return 1`);
    await sleep(1200);
    out.shots.push(await shot('editor-home-' + sel.slice(1)));
  }

  // 2. One field per block, through the sidebar.
  let n = 0;
  const originals = {};
  for (const b of await blocks()) {
    const name = EDITS[b.name];
    if (!name) continue;
    const marker = `zq${++n}x`;
    const key = b.data?.['_' + name];
    originals[b.id] = { name, value: b.data?.[name] ?? b.data?.[key] ?? '' };
    const value = (String(originals[b.id].value).slice(0, 20) + ' ' + marker).trim();
    out.fields[b.name] = { field: name, typed: await typeInSidebar(b.id, name, value), marker };
  }
  out.fieldsSave = await save();
  const edited = await html();
  for (const f of Object.values(out.fields)) f.onSite = edited.includes(f.marker) ? 'shown' : 'NOT SHOWN';
  for (const [id, o] of Object.entries(originals)) await typeInSidebar(id, o.name, o.value);
  out.fieldsRestore = await save();
  const restored = await html();
  out.fieldsRestored = Object.values(out.fields).every((f) => !restored.includes(f.marker)) ? 'all markers gone' : 'MARKER LEFT';

  // 3. Reorder two blocks (Featured Cars down one, below Our Values) and put them back.
  const featured = (await blocks()).find((b) => b.name === 'pm/featured-cars');
  await page.eval(`wp.data.dispatch('core/block-editor').moveBlocksDown([${JSON.stringify(featured.id)}], ''); return 1`);
  out.reorderSave = await save();
  out.reorderSite = order(await html()).join(' ');
  await page.eval(`wp.data.dispatch('core/block-editor').moveBlocksUp([${JSON.stringify(featured.id)}], ''); return 1`);
  out.reorderBackSave = await save();
  out.reorderBack = order(await html()).join(' ');

  // 4. Hide a block (Hide on the block toolbar sets metadata.blockVisibility) and show it again.
  const latest = (await blocks()).find((b) => b.name === 'pm/latest-cars');
  const setVisible = (v) => page.eval(`const s = wp.data.select('core/block-editor'); const id = ${JSON.stringify(latest.id)}; const m = { ...(s.getBlockAttributes(id).metadata || {}) }; if (${v}) delete m.blockVisibility; else m.blockVisibility = false; wp.data.dispatch('core/block-editor').updateBlockAttributes(id, { metadata: m }); return 1`);
  await setVisible(false);
  out.hideSave = await save();
  out.hiddenInEditor = await page.eval(`return wp.data.select('core/block-editor').getBlocks().filter(b => b.attributes.metadata?.blockVisibility === false).map(b => b.name)`);
  let h = await html();
  out.hidden = `${h.includes('id="gallery"') ? 'STILL SHOWN' : 'section gone'}, ${h.includes('js/slider-drag.js') ? 'SCRIPT STILL LOADED' : 'script not loaded'}`;
  await setVisible(true);
  out.unhideSave = await save();
  h = await html();
  out.unhidden = `${h.includes('id="gallery"') ? 'section back' : 'MISSING'}, ${h.includes('js/slider-drag.js') ? 'script back' : 'SCRIPT MISSING'}`;

  // 5. The synced pattern: edit Our Values once (in the pattern), shown on Home and About.
  await open(PATTERN_ID);
  const vb = (await blocks()).find((b) => b.name === 'pm/values');
  const vOrig = vb.data.values_title ?? vb.data[vb.data._values_title];
  out.pattern = { typed: await typeInSidebar(vb.id, 'values_title', vOrig + ' zqp') };
  out.pattern.save = await save();
  out.pattern.home = (await html()).includes(vOrig + ' zqp') ? 'shown' : 'NOT SHOWN';
  out.pattern.about = (await html('about/')).includes(vOrig + ' zqp') ? 'shown' : 'NOT SHOWN';
  await typeInSidebar(vb.id, 'values_title', vOrig);
  out.pattern.restore = await save();
  out.pattern.restored = (await html()).includes(vOrig + ' zqp') ? 'MARKER LEFT' : 'restored';

  // 6. Add a car under Cars: Featured (order 0 = first) with a caption, then delete it.
  await page.go(ADMIN + 'post-new.php?post_type=pm_car');
  await sleep(2500);
  const imageId = Number(process.env.CAR_IMAGE || 0);
  out.car = { form: await page.eval(`
    const set = (name, v) => { const el = document.querySelector('.acf-field[data-name="' + name + '"] :is(input[type=text], input[type=hidden], textarea)'); if (!el) return name + ' missing'; el.value = v; el.dispatchEvent(new Event('change', { bubbles: true })); return 'ok'; };
    const r = [set('image', '${imageId}'), set('marque', 'Zqtest'), set('model_name', 'Zqcar Coupé'), set('caption', 'Zqcaption test car')];
    const t = document.querySelector('.acf-field[data-name="featured"] input[type=checkbox]'); t.checked = true; t.dispatchEvent(new Event('change', { bubbles: true }));
    document.getElementById('menu_order').value = '0';
    return r.join(',');`) };
  await page.eval(`document.getElementById('publish').click(); return 1`);
  await sleep(4000);
  out.car.saved = await page.eval(`return (document.querySelector('#message, .notice-success')?.textContent || document.querySelector('.acf-error-message, .notice-error')?.textContent || 'no notice').trim().slice(0, 60)`);
  out.car.id = await page.eval(`return Number(document.getElementById('post_ID')?.value || 0)`);
  h = await html();
  const featuredSection = (h.match(/<section id="floor"[\s\S]*?<\/section>/) || [''])[0];
  const latestSection = (h.match(/<section id="gallery"[\s\S]*?<\/section>/) || [''])[0];
  out.car.featured = featuredSection.includes('Zqcar Coupé') ? `shown (tile ${[...featuredSection.matchAll(/pm-tile__model">\s*([^<]+)/g)].map((m) => m[1].trim()).indexOf('Zqcar Coupé') + 1} of ${(featuredSection.match(/class="pm-tile"/g) || []).length})` : 'NOT SHOWN';
  out.car.latest = latestSection.includes('Zqcaption test car') ? `shown (slide ${[...latestSection.matchAll(/<figcaption[^>]*>\s*<span>([^<]*)/g)].map((m) => m[1]).indexOf('Zqcaption test car') + 1})` : 'NOT SHOWN';
  await page.go(ADMIN + 'edit.php?post_type=pm_car');
  await sleep(1500);
  out.shots.push(await shot('cars-list-with-test-car'));
  // Delete: Trash, then Delete permanently, through the list's own links.
  await page.eval(`location.href = document.querySelector('#post-${out.car.id} .submitdelete').href; return 1`);
  await sleep(2500);
  await page.go(ADMIN + 'edit.php?post_status=trash&post_type=pm_car');
  await sleep(1500);
  await page.eval(`location.href = document.querySelector('#post-${out.car.id} .submitdelete').href; return 1`);
  await sleep(2500);
  h = await html();
  out.car.removed = h.includes('Zqcar') ? 'STILL ON SITE' : 'gone from the site';
  await page.go(ADMIN + 'edit.php?post_type=pm_car');
  await sleep(1500);
  out.shots.push(await shot('cars-list'));
  return out;
};
