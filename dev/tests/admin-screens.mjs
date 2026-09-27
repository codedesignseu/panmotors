// wp-admin with WP_DEBUG on: loads the admin screens the client and the developer use, including
// the block editor for every page (each pm/* block renders its server-side preview) and a Car,
// and records console errors and any PHP notice printed into the page. PHP notices that only go
// to the log are checked separately (dev/.cache/debug.log lines added during the run).
// Usage: wp eval-file dev/tests/auth-cookies.php <login> > dev/.cache/admin-cookies.json
//        node dev/cdp.mjs dev/tests/admin-screens.mjs      (IDS="6 206 ..." for the pages)
import { readFileSync } from 'node:fs';
const cookies = JSON.parse(readFileSync(new URL(`../.cache/${process.env.COOKIES || 'admin-cookies'}.json`, import.meta.url)));
const A = 'http://panmotors.local/wp-admin/';
export default async ({ page, sleep }) => {
  for (const c of cookies) await page.send('Network.setCookie', { name: c.name, value: c.value, domain: 'panmotors.local', path: '/' });
  await page.size(1440, 900);
  const ids = (process.env.IDS || '').split(/\s+/).filter(Boolean);
  const screens = ['index.php', 'edit.php?post_type=page', 'edit.php?post_type=pm_car', 'edit.php?post_type=wp_block', 'admin.php?page=panmotors-settings', 'nav-menus.php', 'upload.php', 'customize.php', ...ids.map((id) => `post.php?post=${id}&action=edit`)];
  const out = {};
  for (const s of screens) {
    const before = page.console.length;
    await page.go(A + s);
    await sleep(s.startsWith('post.php') ? 6000 : 1500);
    const r = await page.eval(`
      const html = document.documentElement.outerHTML;
      const php = (html.match(/<b>(Notice|Warning|Deprecated|Fatal error)<\\/b>:[^<]{0,160}/g) || []);
      const frame = document.querySelector('iframe[name=editor-canvas]');
      const inFrame = frame?.contentDocument ? (frame.contentDocument.documentElement.outerHTML.match(/<b>(Notice|Warning|Deprecated|Fatal error)<\\/b>:[^<]{0,160}/g) || []) : [];
      const blocks = frame?.contentDocument ? frame.contentDocument.querySelectorAll('.wp-block[data-type^="acf/"], .wp-block[data-type^="pm/"]').length : null;
      const errors = [...document.querySelectorAll('.acf-block-preview .acf-notice, .block-editor-warning')].map((e) => e.textContent.trim().slice(0, 80));
      return { title: document.title.slice(0, 50), php: php.concat(inFrame), blocks, errors };`);
    r.console = page.console.slice(before).filter((m) => !/JQMIGRATE|DevTools|preload/.test(m));
    out[s] = r;
  }
  return out;
};
