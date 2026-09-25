// wp-admin as the client's Editor: menus, blocked screens, tab order, admin-only fields.
// Needs dev/.cache/editor-cookies.json from dev/tests/auth-cookies.php.
import { readFileSync, writeFileSync } from 'node:fs';
const cookies = JSON.parse(readFileSync(new URL('../.cache/editor-cookies.json', import.meta.url)));
const A = 'http://panmotors.local/wp-admin/';
export default async ({ page, sleep, shot }) => {
  for (const c of cookies) await page.send('Network.setCookie', { name: c.name, value: c.value, domain: 'panmotors.local', path: '/' });
  await page.size(1440, 900);
  const out = {};
  await page.go(A);
  out.menu = await page.eval(`return [...document.querySelectorAll('#adminmenu > li > a .wp-menu-name')].map(e=>e.textContent.trim()).filter(Boolean)`);
  out.appearanceSub = await page.eval(`return [...document.querySelectorAll('#menu-appearance .wp-submenu a')].map(a=>a.textContent.trim())`);
  out.toolbarNew = await page.eval(`return [...document.querySelectorAll('#wp-admin-bar-new-content .ab-submenu a')].map(a=>a.textContent.trim())`);
  out.blocked = {};
  for (const p of ['edit.php', 'edit-comments.php', 'plugins.php', 'users.php', 'theme-editor.php', 'themes.php', 'font-library.php', 'site-editor.php?p=/pattern', 'options-general.php', 'edit.php?post_type=acf-field-group']) {
    await page.go(A + p);
    out.blocked[p] = await page.eval(`return location.pathname.replace('/wp-admin/','') + location.search + ' | ' + (document.querySelector('.wp-die-message, #error-page p')?.textContent.trim().slice(0,50) || document.title.slice(0,40))`);
  }
  await page.go(A + 'nav-menus.php');
  out.menusScreen = await page.eval(`return document.title.slice(0,30) + ' | menus: ' + document.querySelectorAll('#select-menu-to-edit option').length`);
  await page.go(A + 'customize.php');
  await sleep(2500);
  out.customizer = await page.eval(`return document.title.includes('Error') ? 'DENIED' : [...document.querySelectorAll('[id^=accordion-section-], [id^=accordion-panel-]')].map(e=>e.id.replace(/^accordion-(section|panel)-/,'')).filter((v,i,a)=>a.indexOf(v)===i)`);
  // Options page.
  await page.go(A + 'admin.php?page=panmotors-settings');
  out.optionsTabs = await page.eval(`return [...document.querySelectorAll('.acf-tab-button')].map(a=>a.textContent.trim())`);
  out.optionsTechnicalFields = await page.eval(`return document.querySelectorAll('[data-name=enquire_form_shortcode], [data-name=page_contact]').length`);
  const oh = await page.eval(`return document.documentElement.scrollHeight`);
  await page.size(1440, Math.min(oh, 1400));
  out.optionsShot = await shot('editor-options');
  // Home edit screen.
  await page.size(1440, 900);
  const front = await page.eval(`return 0`);
  await page.go('http://panmotors.local/wp-admin/post.php?post=6&action=edit');
  await sleep(800);
  out.homeTabs = await page.eval(`return [...document.querySelectorAll('#acf-group_pm_front_page .acf-tab-button')].map(a=>a.textContent.trim())`);
  out.homeEditor = await page.eval(`return !!document.querySelector('#postdivrich, .block-editor')`);
  out.homeLinks = await page.eval(`return [...document.querySelectorAll('#acf-group_pm_front_page .acf-field-message a')].map(a=>a.textContent.trim() + ' → ' + a.getAttribute('href').replace('http://panmotors.local/wp-admin/',''))`);
  await page.eval(`document.querySelectorAll('.notice').forEach(n=>n.remove()); [...document.querySelectorAll('#acf-group_pm_front_page .acf-tab-button')].find(a=>a.textContent.trim()==='Top video').click(); document.getElementById('acf-group_pm_front_page').scrollIntoView(); scrollBy(0,-40); return 1`);
  await sleep(400);
  out.homeShot = await shot('editor-home');
  // Featured tab for a second view.
  await page.eval(`[...document.querySelectorAll('#acf-group_pm_front_page .acf-tab-button')].find(a=>a.textContent.trim()==='Pan Motors Live').click(); document.getElementById('acf-group_pm_front_page').scrollIntoView(); scrollBy(0,-40); return 1`);
  await sleep(400);
  out.homeLiveShot = await shot('editor-home-live');
  return out;
};
