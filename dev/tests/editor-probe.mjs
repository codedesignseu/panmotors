import { readFileSync } from 'node:fs';
const cookies = JSON.parse(readFileSync(new URL('../.cache/editor-cookies.json', import.meta.url)));
export default async ({ page, sleep }) => {
  for (const c of cookies) await page.send('Network.setCookie', { name: c.name, value: c.value, domain: 'panmotors.local', path: '/' });
  await page.size(1440, 900);
  await page.go('http://panmotors.local/wp-admin/admin.php?page=panmotors-settings');
  const tabs = await page.eval(`return { wraps: document.querySelectorAll('.acf-tab-wrap').length, groups: [...document.querySelectorAll('.postbox, .acf-postbox')].map(p=>p.id), fieldsNamedEmail: document.querySelectorAll('[data-name=email]').length }`);
  const sub = await page.eval(`return [...document.querySelectorAll('#menu-appearance .wp-submenu a')].map(a=>a.textContent.trim()+' = '+a.getAttribute('href'))`);
  await page.go('http://panmotors.local/wp-admin/customize.php');
  await sleep(4000);
  const cust = await page.eval(`return { url: location.href, title: document.title, body: document.body.className.slice(0,80), msg: (document.querySelector('.wp-die-message, #error-page')?.textContent||'').trim().slice(0,120), sections: [...document.querySelectorAll('[id^=accordion-section-], [id^=accordion-panel-]')].map(e=>e.id) }`);
  return { tabs, sub, cust };
};
