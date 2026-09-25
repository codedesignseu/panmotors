// Every editable field, through the real wp-admin forms as the client's Editor:
// put a unique marker in each field, save, check it on the site, then restore and save again.
// Also switches each homepage section off and on. Needs dev/.cache/editor-cookies.json.
import { readFileSync, writeFileSync } from 'node:fs';
const cookies = JSON.parse(readFileSync(new URL('../.cache/editor-cookies.json', import.meta.url)));
const ADMIN = 'http://panmotors.local/wp-admin/';
const SCREENS = JSON.parse(process.env.SCREENS);   // [{ name, url }]
const SECTIONS = { marquee: '.pm-marquee', featured: '#floor', values: '#ways', about: '#heritage', latest: '#gallery', live: '#live', showroom: '#showroom', enquire: '#enquire' };
const MODULES = { about: 'heritage-fade', latest: 'slider-drag', live: 'live-videos', showroom: 'showroom-slider' };

export default async ({ page, sleep }) => {
  for (const c of cookies) await page.send('Network.setCookie', { name: c.name, value: c.value, domain: 'panmotors.local', path: '/' });
  await page.size(1440, 900);

  const collect = () => page.eval(`
    const sel = '.acf-field input[type=text], .acf-field input[type=url], .acf-field input[type=email], .acf-field textarea:not(.wp-editor-area)';
    return [...document.querySelectorAll(sel)].filter(i => i.name && i.name.startsWith('acf[') && !i.closest('.acf-clone') && !i.closest('.acf-field-time-picker') && !i.closest('.acf-field-date-picker'))
      .map(i => ({ name: i.name, type: i.type === 'textarea' ? 'textarea' : i.type, value: i.value, max: +i.getAttribute('maxlength') || 0,
                   label: (i.closest('.acf-field').querySelector(':scope > .acf-label label')?.textContent || '').replace('*','').trim(),
                   row: i.closest('.acf-row') ? ' #' + ([...i.closest('.acf-row').parentElement.children].indexOf(i.closest('.acf-row')) + 1) : '' }));`);
  const fill = (values) => page.eval(`
    const v = ${JSON.stringify(values)};
    for (const [name, value] of Object.entries(v)) { const el = document.querySelector('[name="' + CSS.escape(name) + '"]'); if (el) { el.value = value; el.dispatchEvent(new Event('change', {bubbles:true})); } }
    return Object.keys(v).length;`);
  // Classic screens (Home, settings) submit #publish; block-editor pages save the post and then
  // the ACF meta boxes, so wait for both.
  const save = async () => {
    const classic = await page.eval(`return !!document.getElementById('publish')`);
    if (classic) {
      await page.eval(`document.getElementById('publish').click(); return 1`);
      await sleep(3500);
      return page.eval(`return (document.querySelector('.notice-success, #message.updated')?.textContent || document.querySelector('.acf-error-message, .notice-error')?.textContent || 'no notice').trim().slice(0,60)`);
    }
    return page.eval(`
      await wp.data.dispatch('core/editor').savePost();
      const ed = () => wp.data.select('core/edit-post');
      for (let i = 0; i < 100; i++) { await new Promise(r => setTimeout(r, 200)); if (!wp.data.select('core/editor').isSavingPost() && !(ed()?.isSavingMetaBoxes?.())) break; }
      const notices = wp.data.select('core/notices').getNotices().map(n => n.content);
      return 'block editor: ' + (notices.join(' | ') || 'saved').slice(0, 60);`);
  };
  const html = async (path) => { const r = await fetch('http://panmotors.local' + path, { headers: { 'Cache-Control': 'no-cache' } }); return r.text(); };

  const report = { screens: {}, fields: [] };
  let n = 0;
  const all = [];
  for (const s of SCREENS) {
    await page.go(ADMIN + s.url);
    const fields = await collect();
    const markers = {};
    for (const f of fields) {
      const m = `zq${++n}x`;
      let v;
      if (f.type === 'url') v = `https://${m}.test/`;
      else if (f.type === 'email') v = `${m}@example.test`;
      else v = f.max && f.max < 12 ? m.slice(0, f.max) : ((f.max ? f.value.slice(0, Math.max(0, f.max - m.length - 1)) : f.value) + ' ' + m).trim();
      markers[f.name] = v;
      all.push({ screen: s.name, label: f.label + f.row, name: f.name, marker: f.max && f.max < 12 ? m.slice(0, f.max) : m, original: f.value, value: v });
    }
    await fill(markers);
    report.screens[s.name] = { fields: fields.length, save: await save() };
  }
  const home = await html('/'), notFound = await html('/no-such-page-' + Date.now() + '/');
  for (const f of all) {
    const where = home.includes(f.marker) ? 'home' : (notFound.includes(f.marker) ? '404' : '');
    report.fields.push(`${where ? 'SHOWN ' + where.padEnd(4) : 'not on home/404'} | ${f.screen} → ${f.label}`);
  }
  // Restore.
  for (const s of SCREENS) {
    await page.go(ADMIN + s.url);
    const originals = Object.fromEntries(all.filter((f) => f.screen === s.name).map((f) => [f.name, f.original]));
    await fill(originals);
    report.screens[s.name].restore = await save();
  }
  // Section switches (Home screen).
  report.toggles = {};
  const homeScreen = SCREENS.find((s) => s.name === 'Home');
  for (const [key, selector] of Object.entries(SECTIONS)) {
    await page.go(ADMIN + homeScreen.url);
    await page.eval(`const c = document.querySelector('[data-name=home_show_${key}] input[type=checkbox]'); c.checked = false; c.dispatchEvent(new Event('change', {bubbles:true})); return 1`);
    await save();
    const off = await html('/');
    const gone = !off.includes(selector.startsWith('#') ? `id="${selector.slice(1)}"` : 'class="pm-marquee"');
    const modGone = MODULES[key] ? !off.includes(`js/${MODULES[key]}.js`) : true;
    await page.go(ADMIN + homeScreen.url);
    await page.eval(`const c = document.querySelector('[data-name=home_show_${key}] input[type=checkbox]'); c.checked = true; c.dispatchEvent(new Event('change', {bubbles:true})); return 1`);
    await save();
    const back = (await html('/')).includes(selector.startsWith('#') ? `id="${selector.slice(1)}"` : 'class="pm-marquee"');
    report.toggles[key] = `${gone ? 'hidden' : 'STILL SHOWN'}${MODULES[key] ? (modGone ? ', script not loaded' : ', SCRIPT STILL LOADED') : ''}; back on: ${back ? 'shown' : 'MISSING'}`;
  }
  writeFileSync(new URL('../.cache/editor-fields.json', import.meta.url), JSON.stringify(report, null, 2));
  return { screens: report.screens, toggles: report.toggles, shown: report.fields.filter((f) => f.startsWith('SHOWN')).length, notShown: report.fields.filter((f) => !f.startsWith('SHOWN')) };
};
