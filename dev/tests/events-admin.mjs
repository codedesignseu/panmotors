// Events in wp-admin as the client's Editor (TASKS 4d): the edit screen of a new event, the
// required-field messages, a published event appearing on the Events page with the count updated,
// and the event moved to the bin and deleted afterwards. Also the Events list screen and the
// options tabs the Editor sees.
// Needs dev/.cache/editor-cookies.json (wp eval-file dev/tests/auth-cookies.php pm-editor-test).
// Usage: node dev/cdp.mjs dev/tests/events-admin.mjs
import { readFileSync } from 'node:fs';
const cookies = JSON.parse(readFileSync(new URL('../.cache/editor-cookies.json', import.meta.url)));
const A = 'http://panmotors.local/wp-admin/';
const SITE = 'http://panmotors.local/';

export default async ({ page, sleep, shot }) => {
  for (const c of cookies) await page.send('Network.setCookie', { name: c.name, value: c.value, domain: 'panmotors.local', path: '/' });
  const out = {};
  const counts = async () => {
    const html = await (await fetch(SITE + 'events/', { headers: { 'Cache-Control': 'no-cache' } })).text();
    return [...html.matchAll(/data-events-tab="(\w+)">[^<]*<span class="pm-events__count">(\d+)/g)].map((m) => `${m[1]} ${m[2]}`).join(', ');
  };
  out.countsBefore = await counts();

  await page.size(1440, 900);
  await page.go(A + 'post-new.php?post_type=pm_event');
  await sleep(5000);
  // No welcome guide or fullscreen: the screen as the client works in it.
  await page.eval(`wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false); wp.data.dispatch('core/preferences').set('core', 'fullscreenMode', false); return 1`);
  await sleep(800);
  out.blocks = await page.eval(`return wp.data.select('core/block-editor').getBlocks().map(b => b.name)`);
  out.allowed = await page.eval(`return wp.data.select('core/block-editor').getInserterItems().map(i => i.name).filter(n => !n.startsWith('core/block/')).sort()`);
  out.fields = await page.eval(`return [...document.querySelectorAll('.acf-field[data-name]')].filter(f => f.offsetParent).map(f => (f.querySelector('.acf-label label')?.textContent.trim() || f.dataset.name) + (f.querySelector('.acf-label .description')?.textContent.trim() ? ' ✓help' : ''))`);
  out.mainImageHint = await page.eval(`return document.querySelector('.editor-post-featured-image')?.parentElement?.textContent.includes('2400px') || false`);
  const h = await page.eval(`return Math.max(document.querySelector('.interface-interface-skeleton__content')?.scrollHeight || 0, 900)`);
  await page.size(1440, Math.min(h + 200, 2600));
  await sleep(800);
  out.shot = await shot('events-edit-screen');
  await page.size(1440, 900);

  let step = 0;
  const publish = async () => {
    // The toolbar's Publish opens the pre-publish panel; its own Publish button publishes.
    await page.eval(`[...document.querySelectorAll('.editor-header button, .edit-post-header button')].find(b => b.textContent.trim() === 'Publish')?.click(); return 1`);
    await sleep(1500);
    await page.eval(`[...document.querySelectorAll('.editor-post-publish-panel button')].find(b => b.textContent.trim() === 'Publish')?.click(); return 1`);
    await sleep(6000);
    await shot('events-publish-' + ++step);
    return page.eval(`return {
      status: wp.data.select('core/editor').getCurrentPostAttribute('status'),
      notices: wp.data.select('core/notices').getNotices().map(n => n.content.replace(/<[^>]+>/g, '')).slice(-3),
      acf: [...document.querySelectorAll('.acf-field.acf-error')].map(f => f.dataset.name + ': ' + (f.querySelector('.acf-notice')?.textContent.trim() || ''))
    }`);
  };

  // 1. Publish with a title only (WordPress keeps Publish inert on a post with no title and no
  // text): the required-field messages for start date and place, and the main image.
  await page.eval(`wp.data.dispatch('core/editor').editPost({ title: 'Client Test Evening' }); return 1`);
  out.emptyPublish = await publish();
  // ACF passes once its fields are filled; then the main image message from the server.
  await page.eval(`
    const set = (key, value) => { const i = document.querySelector('[name="acf[' + key + ']"]'); i.value = value; i.dispatchEvent(new Event('change', { bubbles: true })); };
    set('field_pm_event_start_date', '20261128'); set('field_pm_event_place', 'Showroom, Mesoyi'); return 1`);
  await page.eval(`[...document.querySelectorAll('.editor-post-publish-panel button')].find(b => b.textContent.trim() === 'Cancel')?.click(); return 1`);
  out.noImagePublish = await publish();
  await page.eval(`[...document.querySelectorAll('.editor-post-publish-panel button')].find(b => b.textContent.trim() === 'Cancel')?.click(); return 1`);

  // 2. Fill the required fields, but with an end date before the start date.
  const media = await (await fetch(SITE + 'wp-json/wp/v2/media?search=sunset&per_page=1')).json();
  await page.eval(`
    wp.data.dispatch('core/editor').editPost({ title: 'Client Test Evening', featured_media: ${media[0]?.id || 0} });
    const set = (key, value) => { const i = document.querySelector('[name="acf[' + key + ']"]'); i.value = value; i.dispatchEvent(new Event('change', { bubbles: true })); };
    set('field_pm_event_start_date', '20261128');
    set('field_pm_event_end_date', '20261120');
    set('field_pm_event_place', 'Showroom, Mesoyi');
    set('field_pm_event_summary', 'A test evening added in wp-admin as a client would.');
    return 1`);
  await sleep(500);
  out.endBeforeStart = await publish();
  await page.eval(`[...document.querySelectorAll('.editor-post-publish-panel button')].find(b => b.textContent.trim() === 'Cancel')?.click(); return 1`);

  // 3. Fix the end date and publish.
  await page.eval(`const i = document.querySelector('[name="acf[field_pm_event_end_date]"]'); i.value = ''; i.dispatchEvent(new Event('change', { bubbles: true })); return 1`);
  out.published = await publish();
  out.link = await page.eval(`return wp.data.select('core/editor').getPermalink()`);
  out.id = await page.eval(`return wp.data.select('core/editor').getCurrentPostId()`);
  await sleep(1500);
  out.countsAfter = await counts();
  out.onEventsPage = (await (await fetch(SITE + 'events/')).text()).includes('Client Test Evening');
  const single = await (await fetch(out.link)).text();
  out.singlePage = { h1: (single.match(/<h1[^>]*>([^<]*)/) || [])[1], date: (single.match(/<dd class="pm-event-facts__value"><time datetime="([^"]+)"/) || [])[1], buttons: single.includes('pm-event-body__actions') };

  // 4. The Events list screen, then move the event to the bin (the Editor can) and empty it.
  await page.go(A + 'edit.php?post_type=pm_event');
  out.listColumns = await page.eval(`return [...document.querySelectorAll('thead th')].map(t => t.textContent.trim()).filter(Boolean)`);
  out.listRows = await page.eval(`return [...document.querySelectorAll('#the-list tr')].map(r => [r.querySelector('.row-title')?.textContent, r.querySelector('.column-pm_date')?.textContent.trim().replace(/\\s+/g, ' '), r.querySelector('.column-pm_status')?.textContent.trim()].join(' | '))`);
  await page.size(1440, 1000);
  out.listShot = await shot('events-admin-list');
  await page.eval(`document.querySelector('#post-${out.id} .submitdelete')?.click(); return 1`);
  await sleep(2500);
  await page.go(A + 'edit.php?post_status=trash&post_type=pm_event');
  out.inBin = await page.eval(`return [...document.querySelectorAll('#the-list .row-title, #the-list strong')].map(e => e.textContent.trim()).filter(Boolean)`);
  await page.eval(`document.querySelector('#delete_all')?.click(); return 1`);
  await sleep(2500);
  out.countsEnd = await counts();

  // 5. Settings tabs for the Editor (Technical hidden).
  await page.go(A + 'admin.php?page=panmotors-settings');
  out.optionsTabs = await page.eval(`return [...document.querySelectorAll('.acf-tab-button')].map(a => a.textContent.trim())`);
  out.menu = await page.eval(`return [...document.querySelectorAll('#adminmenu > li > a .wp-menu-name')].map(e => e.textContent.trim()).filter(Boolean)`);
  out.console = page.console.filter((m) => !/JQMIGRATE|Download the React DevTools/.test(m));
  return out;
};
