// Tabs through the homepage and records every focus stop, in order, and whether it has a
// visible focus indicator (outline, box-shadow, background or border change vs. unfocused).
export default async ({ page, sleep }) => {
  await page.size(Number(process.env.W || 1440), 900);
  await page.media(false);
  await page.go('http://panmotors.local/');
  await sleep(1500);
  const stops = [];
  for (let i = 0; i < 80; i++) {
    await page.key('Tab', 'Tab', 9);
    await sleep(120);
    const s = await page.eval(`
      const el = document.activeElement;
      if (!el || el === document.body) return null;
      const cs = getComputedStyle(el);
      const r = el.getBoundingClientRect();
      const name = (el.getAttribute('aria-label') || el.textContent || el.getAttribute('placeholder') || el.name || '').trim().replace(/\\s+/g,' ').slice(0, 40);
      const sec = el.closest('section,header,footer,nav')?.id || el.closest('section,header,footer,nav')?.className.split(' ')[0] || '';
      const visible = r.width > 0 && r.height > 0;
      const indicator = (cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) > 0) ? 'outline ' + cs.outlineColor : (cs.boxShadow !== 'none' ? 'shadow' : (el.matches(':focus-visible') ? 'focus-visible(style)' : 'NONE'));
      return { tag: el.tagName.toLowerCase(), name, sec, top: Math.round(r.top + scrollY), visible, fv: el.matches(':focus-visible'), indicator, bg: cs.backgroundColor, border: cs.borderBottomColor };
    `);
    if (!s) break;
    if (stops.length && stops[0].name === s.name && stops[0].tag === s.tag && i > 5) break;
    stops.push(s);
  }
  return stops;
};
