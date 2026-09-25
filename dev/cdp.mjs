// Minimal headless-Chrome driver over the Chrome DevTools Protocol. Development only.
//
// Usage: node dev/cdp.mjs dev/tests/<script>.mjs
// The script default-exports async ({ page, sleep, shot }) => result, printed as JSON.
// Headless pages count as visible, so IntersectionObserver, rAF and muted autoplay behave
// like a real foreground tab. Screenshots go to dev/.cache/shots/ (git-ignored).
import { spawn } from 'node:child_process';
import { writeFileSync, mkdirSync } from 'node:fs';

const CACHE = new URL('.cache/', import.meta.url).pathname;
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9333;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

mkdirSync(CACHE + 'chrome-profile', { recursive: true });
const proc = spawn(CHROME, [
  '--headless=new', `--remote-debugging-port=${PORT}`, `--user-data-dir=${CACHE}chrome-profile`,
  '--autoplay-policy=no-user-gesture-required', '--hide-scrollbars', '--window-size=1440,900', 'about:blank',
], { stdio: 'ignore' });

let ws, id = 0;
const pending = new Map();
const listeners = [];
async function connect() {
  for (let i = 0; i < 50; i++) {
    try {
      const list = await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json();
      const target = list.find((t) => t.type === 'page');
      if (target) { ws = new WebSocket(target.webSocketDebuggerUrl); break; }
    } catch {}
    await sleep(200);
  }
  await new Promise((r) => ws.addEventListener('open', r, { once: true }));
  ws.addEventListener('message', (e) => {
    const msg = JSON.parse(e.data);
    if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg); pending.delete(msg.id); }
    else listeners.forEach((fn) => fn(msg));
  });
}
const send = (method, params = {}) => new Promise((resolve, reject) => {
  const mid = ++id;
  pending.set(mid, (m) => (m.error ? reject(new Error(method + ': ' + m.error.message)) : resolve(m.result)));
  ws.send(JSON.stringify({ id: mid, method, params }));
});

const page = {
  send,
  async size(width, height = 900) {
    await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: false });
  },
  async media(reduce) {
    await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: reduce ? 'reduce' : 'no-preference' }] });
  },
  async go(url) {
    const loaded = new Promise((r) => { const fn = (m) => { if (m.method === 'Page.loadEventFired') { listeners.splice(listeners.indexOf(fn), 1); r(); } }; listeners.push(fn); });
    await send('Page.navigate', { url });
    await Promise.race([loaded, sleep(20000)]);
    await sleep(800);
  },
  async eval(expr) {
    const r = await send('Runtime.evaluate', { expression: `(async () => { ${expr} })()`, awaitPromise: true, returnByValue: true });
    if (r.exceptionDetails) throw new Error(r.exceptionDetails.exception?.description || r.exceptionDetails.text);
    return r.result.value;
  },
  async mouse(type, x, y) {
    await send('Input.dispatchMouseEvent', { type, x, y, button: 'left', clickCount: type === 'mouseMoved' ? 0 : 1 });
  },
  async key(key, code, keyCode) {
    await send('Input.dispatchKeyEvent', { type: 'keyDown', key, code, windowsVirtualKeyCode: keyCode });
    await send('Input.dispatchKeyEvent', { type: 'keyUp', key, code, windowsVirtualKeyCode: keyCode });
  },
  console: [],
};
async function shot(name, clip) {
  const r = await send('Page.captureScreenshot', { format: 'jpeg', quality: 80, ...(clip ? { clip: { ...clip, scale: 1 } } : {}) });
  const file = `${CACHE}shots/${name}.jpg`;
  mkdirSync(`${CACHE}shots`, { recursive: true });
  writeFileSync(file, Buffer.from(r.data, 'base64'));
  return file;
}

try {
  await connect();
  await send('Page.enable');
  await send('Runtime.enable');
  listeners.push((m) => {
    if (m.method === 'Runtime.exceptionThrown') page.console.push('EXC ' + m.params.exceptionDetails.exception?.description);
    if (m.method === 'Runtime.consoleAPICalled' && ['error', 'warning'].includes(m.params.type)) page.console.push(m.params.type + ' ' + m.params.args.map((a) => a.value ?? a.description).join(' '));
  });
  const mod = await import(new URL(process.argv[2], 'file://' + process.cwd() + '/').href + '?' + Date.now());
  const out = await mod.default({ page, sleep, shot });
  console.log(JSON.stringify({ out, console: page.console }, null, 2));
} catch (e) {
  console.error('FAILED', e.message);
} finally {
  ws?.close();
  proc.kill();
}
