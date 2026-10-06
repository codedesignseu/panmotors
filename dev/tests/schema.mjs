// JSON-LD and page basics check (TASKS §5). Plain Node, no browser.
// Usage: node dev/tests/schema.mjs   → dev/.cache/schema/<page>.json and a report.
//
// Per page: the ld+json is valid JSON, one @graph, every {"@id"} reference resolves inside it, no
// empty value anywhere, exactly one business entity (AutoDealer) and no other business, Car,
// Product or Offer node, FAQPage text identical to the visible questions. Also: <html lang>,
// the skip link as the first focusable element, landmarks, one H1 and no skipped heading levels,
// and the font preloads.
// Events (TASKS 4d): each event page has one Event node (name, description = the summary, startDate
// with the site's offset when timed, eventStatus, OfflineEventAttendanceMode, a Place, organizer
// #business, url, no offers) and the meta description is its summary; the Events page has an
// ItemList of exactly the upcoming events it shows, in order.
import { mkdirSync, writeFileSync } from 'node:fs';

const SITE = process.env.SITE || 'http://panmotors.local/';
const PAGES = { home: '', 'featured-cars': 'featured-cars/', about: 'about/', showroom: 'showroom/', contact: 'contact/', 'latest-cars': 'latest-cars/', events: 'events/', privacy: 'privacy-policy/', '404': 'no-such-page/' };
// Every published event, from the sitemap (Rank Math's when active, else core's). Cars are never in it.
let sitemap = '';
for (const map of ['pm_event-sitemap.xml', 'wp-sitemap-posts-pm_event-1.xml']) {
  const res = await fetch(SITE + map, { redirect: 'manual' });
  if (res.status === 200) { sitemap = await res.text(); break; }
}
if (!sitemap) console.log('PROBLEM: no events sitemap');
for (const map of ['pm_car-sitemap.xml', 'wp-sitemap-posts-pm_car-1.xml']) if ((await fetch(SITE + map, { redirect: 'manual' })).status === 200) console.log('PROBLEM: cars sitemap ' + map);
for (const url of [...sitemap.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => m[1])) PAGES['event-' + url.split('/').filter(Boolean).pop()] = url.replace(SITE, '');
const OUT = new URL('../.cache/schema/', import.meta.url).pathname;
mkdirSync(OUT, { recursive: true });

const decode = (s) => s.replace(/<[^>]+>/g, '').replace(/&#0*39;|&#x27;/g, "'").replace(/&quot;/g, '"').replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&#8217;/g, '’').replace(/&#8211;/g, '–').replace(/&#8212;/g, '—').trim();
const BUSINESS = /^(AutoDealer|LocalBusiness|Organization|Corporation|Store|AutomotiveBusiness)$/;
const FORBIDDEN = /^(Car|Vehicle|Product|Offer|AggregateOffer)$/;

const empties = (v, path, out) => {
  if (v === null || v === '' || (Array.isArray(v) && !v.length) || (typeof v === 'object' && !Array.isArray(v) && !Object.keys(v).length)) out.push(path);
  else if (typeof v === 'object') for (const [k, x] of Object.entries(v)) empties(x, `${path}.${k}`, out);
  return out;
};
const walk = (v, fn) => { if (v && typeof v === 'object') { fn(v); for (const x of Object.values(v)) walk(x, fn); } };

const report = {};
for (const [name, path] of Object.entries(PAGES)) {
  const res = await fetch(SITE + path, { headers: { 'Cache-Control': 'no-cache' } });
  const html = await res.text();
  const r = { status: res.status, problems: [] };
  const scripts = [...html.matchAll(/<script type="application\/ld\+json"[^>]*>([\s\S]*?)<\/script>/g)].map((m) => m[1]);
  r.jsonLdBlocks = scripts.length;
  if (scripts.length !== 1) r.problems.push(`${scripts.length} JSON-LD blocks`);
  let data = null;
  try { data = JSON.parse(scripts[0] || 'null'); } catch (e) { r.problems.push('invalid JSON: ' + e.message); }
  writeFileSync(`${OUT}${name}.json`, JSON.stringify(data, null, 2) + '\n');
  if (data) {
    const graph = data['@graph'] || [];
    const ids = new Set(graph.map((n) => n['@id']).filter(Boolean));
    const types = [];
    walk(graph, (n) => { if (n['@type']) types.push(...[].concat(n['@type'])); });
    r.types = graph.map((n) => n['@type']);
    walk(graph, (n) => { if (Object.keys(n).length === 1 && n['@id'] && !ids.has(n['@id'])) r.problems.push('unresolved @id ' + n['@id']); });
    const empty = empties(graph, 'graph', []);
    if (empty.length) r.problems.push('empty: ' + empty.join(', '));
    const business = types.filter((t) => BUSINESS.test(t));
    if (business.length !== 1 || business[0] !== 'AutoDealer') r.problems.push('business entities: ' + business.join(', '));
    const forbidden = types.filter((t) => FORBIDDEN.test(t));
    if (forbidden.length) r.problems.push('forbidden types: ' + forbidden.join(', '));
    // FAQPage vs the visible questions.
    const visibleQ = [...html.matchAll(/<summary class="pm-faq__question">([\s\S]*?)<\/summary>\s*<p class="pm-faq__answer">([\s\S]*?)<\/p>/g)].map((m) => [decode(m[1]), decode(m[2])]);
    const faq = graph.find((n) => n['@type'] === 'FAQPage');
    const schemaQ = faq ? faq.mainEntity.map((q) => [q.name, q.acceptedAnswer.text]) : [];
    if (JSON.stringify(visibleQ) !== JSON.stringify(schemaQ)) r.problems.push(`FAQ mismatch: ${visibleQ.length} visible, ${schemaQ.length} in schema`);
    r.faq = schemaQ.length;
    // Events.
    const events = graph.filter((n) => n['@type'] === 'Event');
    if (name.startsWith('event-')) {
      const ev = events[0];
      if (events.length !== 1) r.problems.push(`${events.length} Event nodes`);
      else {
        for (const k of ['name', 'startDate', 'eventStatus', 'eventAttendanceMode', 'location', 'organizer', 'url']) if (!ev[k]) r.problems.push('Event without ' + k);
        if (ev.eventAttendanceMode !== 'https://schema.org/OfflineEventAttendanceMode') r.problems.push('attendance mode ' + ev.eventAttendanceMode);
        if (!/^https:\/\/schema\.org\/Event(Scheduled|Cancelled|Postponed)$/.test(ev.eventStatus)) r.problems.push('eventStatus ' + ev.eventStatus);
        if (ev.location?.['@type'] !== 'Place') r.problems.push('location not a Place');
        if (ev.organizer?.['@id'] !== SITE + '#business') r.problems.push('organizer ' + JSON.stringify(ev.organizer));
        if (ev.offers || ev.price) r.problems.push('Event with offers or price');
        if (!/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2})?$/.test(ev.startDate)) r.problems.push('startDate ' + ev.startDate);
        if (ev.endDate && ev.endDate < ev.startDate) r.problems.push('endDate before startDate');
        const meta = decode((html.match(/<meta name="description" content="([^"]*)"/) || [])[1] || '');
        if (ev.description && meta !== ev.description) r.problems.push('meta description is not the summary');
        r.event = { startDate: ev.startDate, endDate: ev.endDate, status: ev.eventStatus.split('/').pop() };
      }
    } else if (events.length) r.problems.push('Event node on ' + name);
    const list = graph.find((n) => n['@type'] === 'ItemList');
    if (name === 'events') {
      // The rows of the Upcoming group, in order, as printed.
      const up = html.match(/data-events-group="upcoming">([\s\S]*?)<\/section>\s*(?:<section|<\/div>)/);
      const shown = up ? [...up[1].matchAll(/<h2 class="pm-event-row__title"[^>]*><a href="([^"]+)"/g)].map((m) => m[1]) : [];
      const listed = list ? list.itemListElement.map((i) => i.url) : [];
      if (JSON.stringify(shown) !== JSON.stringify(listed)) r.problems.push(`ItemList ${listed.length} vs ${shown.length} upcoming rows`);
      r.itemList = listed.length;
    } else if (list) r.problems.push('ItemList on ' + name);
  }
  // Page basics.
  r.lang = (html.match(/<html[^>]*lang="([^"]+)"/) || [])[1];
  if (r.lang !== 'en-GB') r.problems.push('lang ' + r.lang);
  const body = html.slice(html.indexOf('<body'));
  const firstFocusable = (body.match(/<(a\s[^>]*href|button|input|select|textarea)[^>]*>/) || [''])[0];
  if (!/pm-skip-link/.test(firstFocusable)) r.problems.push('first focusable: ' + firstFocusable.slice(0, 60));
  for (const l of ['<header', '<nav', '<main id="main"', '<footer']) if (!html.includes(l)) r.problems.push('missing ' + l);
  const main = html.slice(html.indexOf('<main'), html.indexOf('</main>'));
  const levels = [...main.matchAll(/<h([1-6])\b/g)].map((m) => Number(m[1]));
  r.headings = levels.join('');
  if (levels.filter((l) => l === 1).length !== 1) r.problems.push('H1 count ' + levels.filter((l) => l === 1).length);
  levels.forEach((l, i) => { if (i && l > levels[i - 1] + 1) r.problems.push(`skipped level h${levels[i - 1]}→h${l}`); });
  r.fontPreloads = [...html.matchAll(/<link rel="preload" href="[^"]*\/fonts\/([^"]+)" as="font"/g)].map((m) => m[1]);
  r.metaDescription = (html.match(/<meta name="description" content="([^"]*)"/) || [])[1] || null;
  report[name] = r;
}
console.log(JSON.stringify(report, null, 1));
const bad = Object.entries(report).filter(([, r]) => r.problems.length);
console.log(bad.length ? `PROBLEMS on ${bad.map(([n]) => n).join(', ')}` : 'All pages pass.');
