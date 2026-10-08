# Events (TASKS 4d, 6 Oct 2026)

Source: the Claude Design export in `_design/events/` (`events.html` list, `event.html?e=0…4` single page). Photos are the same files as `_design/uploads/` (byte for byte), so the seed reuses the attachments it already imported.

## Model

- **Post type `pm_event`** ("Events" in wp-admin, `inc/events.php`). Public, with single pages at **`/events/{slug}/`** (no clash with the Events page at `/events/`: the post type's rule needs a slug after `/events/`, and the page keeps `/events/` itself). No archive: the Events page lists them. Editors manage events like pages (`capability_type` page, as Cars). In the sitemap (core's, and Rank Math's: `inc/crawl.php` turns Rank Math's Events sitemap on unless it was saved off). Cars stay out of every sitemap.
- **Edit screen**: title and story in the block editor (text blocks only: paragraph, heading, list, quote, image, buttons; two starting paragraphs), Main image (the featured image) in the sidebar with a size hint, then the **Event details** and **Photographs** field groups (`dev/acf-fields.py`) in the meta-box pane under the story. WordPress 7.1 keeps meta boxes in that pane, closed by default; `assets/js/event-editor.js` opens it at half the screen on this screen until the user sets it themselves, so a new event shows its fields straight away. WordPress always puts the title and the story together at the top, so the order is title → story → details → photographs, with the main image in the sidebar.
- **Fields**: Event type, Start date (required), End date, All day, Start time, End time, Place name (required), Guests, Address, Map link (falls back to a Google Maps search for the address), Summary (list row, meta description, schema description), Lede (falls back to the summary), Status (Automatic, Cancelled, Postponed, Sold out), Registration button label (default "Register interest") and page (default the Contact page), Call button switch (default on), Photographs (gallery, up to 12).
- **Settings → Events** (Options tab, Editors can use it): the Events page (target of "All events", the breadcrumb and the menu's current item on an event page) and the small labels of the event page (back link, status labels, fact labels, All day, Photographs, Call, Add to calendar, Previous / Next event). An emptied label falls back to the theme default.

## Status

`panmotors_event()` works it out on every request, in the site's timezone (Settings → General → Timezone):

- An event ends at its end time on its last day (the end date, else the start date), or at the end of that day when there is no end time. Until then it is **Upcoming**; after, **Past event**.
- Cancelled and Postponed show that label whatever the date. Sold out shows until the event is over, then Past event.
- Only an upcoming event with status Automatic shows the buttons (Register interest, Call, Add to calendar). Past, cancelled, postponed and sold-out events keep their page and lose the buttons.

## Pages

- **Events page**: a normal page: `pm/page-header` (Text), `pm/events-list`, `pm/cta-band` (paper card with the optional text). Tabs Upcoming / Past with counts are built from the events (a group with no events has no tab); real buttons with `aria-pressed`, hidden without JS (both groups then show, one after the other). Each group is a `<section>` labelled by its tab name; event titles are the H2s. Upcoming soonest first, past newest first. Block options: intro, show past events, how many past events. No events at all: the empty-state text and button (block fields).
- **Home**: `pm/events-intro` before Pan Motors Live (`_design/home-events/events-intro.html`, layout only; the theme's tokens, pills and reveal). A hairline over the head (small red line and H2 left; intro and "All events" pill right), then auto-fit cards (`minmax(min(100%, 320px), 1fr)`, 14px gap). Each card is one link to the event page, named "Title, 17 October 2026": the main image (4:3, `pm-tile` cropped by CSS, lazy; a dark surface without one) with the type pill and a Postponed or Sold out pill, then the start day zero-padded ("08"), "Month Year · Place" and the title (H3). Hover and focus: the card lifts 8px and the image zooms, the text stays paper. The next 3 events by default (1–6), soonest first, read from `panmotors_events()` (same status and timezone rules); cancelled events never show. No upcoming events: the head and the empty-state text, no grid. The button goes to the block's page, else Settings → Events → Events page, else the `pm_event` archive (there is none, so no button). No schema on Home: each event's own page carries its Event JSON-LD. Added to this site by `dev/migrations/2026-10-09-events-intro.php`.
- **Event page**: `single-pm_event.php` + `template-parts/events/` (single, photos). The content is the event's own fields, so it is a template with shared parts, not blocks (D11 stands: no page reads another page). The header is `template-parts/sections/page-header.php` (Photo style, modifier `event`, with the new back link and status pill args). Facts with `<time>`; empty facts drop out and the row closes up. Photographs: moving track (`assets/js/event-photos.js`), all photos in the HTML, drag/swipe > 50px, arrow keys, `aria-live` counter, instant under reduced motion, hidden without photos. Previous / next in date order, wrapping; hidden with one event. Footer dark.
- **.ics**: `/events/{slug}/?ics=1`, written by the theme (`panmotors_event_ics()`): CRLF line endings, lines folded at 75 octets, text escaped, timed events in UTC, all-day events as dates, `STATUS:CANCELLED` for cancelled events, `X-Robots-Tag: noindex`.

## SEO and GEO

- Event JSON-LD in the page's `@graph` (`inc/schema.php`): WebPage, BreadcrumbList (Home → Events page → event), Event with name, description (summary), startDate / endDate with the site's offset (dates only when there is no time), eventStatus (Scheduled, Cancelled, Postponed), OfflineEventAttendanceMode, location Place (+ PostalAddress with the address when filled, hasMap), image (main image and photographs), organizer `#business`, url. No offers or prices. Empty values left out.
- The Events page gets an ItemList of its upcoming events, in the order shown.
- Meta description of an event page: its summary (the SEO plugin's own description wins when written).
- llms.txt: "Upcoming events" and "Past events", one line each: title, date, URL (and the status when cancelled, postponed or sold out).
- `dev/tests/schema.mjs` checks all of it, for every event in the sitemap.

## Demo content

The seed creates the five events of the design (`panmotors_demo_events()` in `dev/lib.php`), demo-flagged and only when missing, with their four photos each (the first is the main image), and the Events page as block markup. `dev/migrations/2026-10-06-events.php` creates the Events page on an existing site (only when missing), sets Settings → Events → Events page, and adds Events to the Primary menu (before Showroom, after Latest Cars) and the Footer menu (before Showroom).

**The demo dates are fixed** (17 Oct, 8 Nov, 5 Dec 2026 upcoming; 14 Jun and 22 Mar 2026 past). On a fresh install the three upcoming ones (the cards of Events intro on Home) are created only while fewer than three events are coming up, and a date that has passed moves forward by whole years, so they are upcoming when created; once created they keep their date. After 5 December 2026 every demo event counts as past: the Upcoming tab disappears and the Events page opens on Past. Replace them with real events before launch.

## Site setting to check

The site's timezone is UTC (Settings → General). Event times are read in the site's timezone, so set it to **Nicosia** before launch: then 19:30 is 19:30 in Cyprus, the JSON-LD carries +03:00 / +02:00 and calendar files the right UTC time (checked: `2026-10-17T19:30:00+03:00`, `DTSTART:20261017T163000Z`).
