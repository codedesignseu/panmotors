# Editability

Rule (CLAUDE.md): the client edits everything in wp-admin. No visible text, link, image or video is hardcoded. Content comes from a block's fields, the Cars list, a synced pattern, a WordPress menu, the Customizer logo, or Pan Motors settings. Theme strings left in PHP are screen-reader text, aria-labels and admin notices, all translation-ready. An empty field hides its element.

Content architecture: Gutenberg + ACF Blocks (D11, [`blocks.md`](blocks.md)). Updated 26 Sep 2026 after the migration ([`migration-blocks.md`](migration-blocks.md)); the audit of 25 Sep (field-group model) is in git history.

## Where things are edited

| Screen | What |
|---|---|
| **Pages → Home** (block editor) | The homepage sections as blocks, in page order: Top video, Marques strip, Featured Cars, Our Values, About Pan Motors, Latest Cars, Pan Motors Live, The Showroom, Come And See. Each block shows the real section; its fields are in the sidebar (Block tab). |
| **Pages → About Pan Motors** | Built as `_design/v2/about.html` (D12): Page header, Story, What We Do, Our Values (light section), Photo call to action. |
| **Pages → Featured Cars** | Built as `_design/v2/cars.html` (D12): Page header (Text), Cars grid, CTA band. |
| **Pages → The Showroom** | Built as `_design/v2/showroom.html` (D12): Page header (Photo), Photo slider, Visit, Photo call to action. |
| **Pages → Contact** | Built as `_design/v2/contact.html` (D12): Page header (Text), Contact rows, Contact form and map, Questions. |
| **Pages → Events** | Built as `_design/events/events.html`: Page header (Text), Events list (tabs, labels, how many past events, the text when there are no events), CTA band "Join The / Guest List" with its text. The events themselves come from **Events**. |
| **Events** | One entry per event, with its own page at /events/…: title, main image, story, Event details, Photographs. See "Events" below. |
| **Pages → Latest Cars** | Placeholder built from blocks (Page top, text, Latest Cars, Call to action); its design is not decided (D12). |
| **Cars** | Every showcase car once: photo (optional; homepage rows only show cars with one), marque, model, reference, detail, note (the car sheet description), optional link, slider caption, place, the car sheet (year, power, acceleration, mileage, engine, gearbox, colour), On the Featured Cars page, Featured, order (Page attributes; also the car's number on the Featured Cars page). The homepage rows and the Featured Cars page read from here. |
| **Pan Motors (settings)** | Business, Contact, Opening hours, Marques, **Our Values** (the values shown on Home and About; D12), Social, Footer, Page not found, **Events** (the Events page and the small labels on every event page). **Design** (colours, fonts, heading and text size, corner rounding, logo heights, logo for light backgrounds, default share image; D12) is for administrators, and for Editors when Technical → "Editors can change the design" is on. **Technical** (Contact button page, form shortcode, the Design switch) is visible to administrators only. |
| **Appearance → Menus** | Primary menu (header + mobile menu), Footer menu. |
| **Appearance → Customise → Site Identity** | Logo (header, contact card, footer). |
| **Media** | Images and videos; photo captions (Showroom slider) come from each image's Caption. |

## Working with blocks

| Task | How |
|---|---|
| Change a text, photo or video | Click the section, then edit its fields in the sidebar. The preview updates in place. |
| Hide a section without losing it | Block toolbar → ⋮ → **Hide** (WordPress 6.9+). Hidden blocks stay in the editor and print nothing on the site (their script is not loaded either). Show them again the same way. |
| Reorder sections | Drag the block, use the ↑ ↓ arrows in its toolbar, or the List view. The Top video on Home is locked: it cannot be moved or removed. |
| Remove a section | Block toolbar → ⋮ → Delete. It can be added back from the inserter (+), "Pan Motors" category. |
| Add plain text on an inner page | Heading, Paragraph, List, Image, Quote and Buttons are available. Nothing else from core (no columns, colours, spacing or fonts: `theme.json` switches those off). |
| Add a video | Inserter → YouTube Embed or Vimeo Embed, then paste the video's link (pasting a YouTube or Vimeo link into an empty paragraph works too). The video fills the text column and keeps 16:9 on every screen. Instagram links show as a plain link: WordPress cannot embed Instagram without an Instagram/Meta oEmbed plugin. Links from other sites (X, TikTok…) also show as plain links. |
| Start a page from the design | Inserter → Patterns → Pan Motors → "Homepage (full design)": all homepage blocks in order, empty. |
| Undo a change | Undo in the toolbar, or Page → Revisions (each save is a revision). |

## Handover note: plugins

- Plugins that add share buttons or related posts to the bottom of pages should be switched off for the Home page. Every page, Home included, goes through WordPress's normal content filters, so such plugins reach the homepage too.

## Header and mobile menu

| Element | Where |
|---|---|
| Logo | Customizer → Site Identity |
| Logo alt text / text fallback | Settings → Business → Trading name |
| Menu links | Appearance → Menus → Primary |
| Contact button (header) and last mobile-menu item: text | Settings → Contact → Contact button text |
| Contact button: link | Settings → Technical → Contact button goes to (admin). Empty or unpublished page: the button hides. |
| Current page | The menu link of the page being viewed shows in the accent colour; on the Contact page the Contact button shows filled (automatic) |
| "Skip to content", "Menu" | theme string (screen-reader / keyboard only) |

## Homepage (blocks on Pages → Home)

| Block | Element | Where |
|---|---|---|
| Top video | Small red line, big title (new line = line break) | block fields |
| | Background video, background photo | block fields (MP4 under 8 MB; photo ≥ 1920px wide, 2400 recommended) |
| | Button text, Button goes to | block fields (page picker, required) |
| | ↓ arrow | decorative icon (aria-hidden) |
| Marques strip | Names, separator | Settings → Marques (the block has no fields) |
| Featured Cars | Heading, short intro, "All featured cars" button text and page (empty: no button) | block fields |
| | Which cars | block field: Cars marked Featured (in their order) or Pick cars (drag to order); How many (4 fill a row) |
| | Cars (photo, marque, model, reference, detail, note, optional link) | Cars |
| Our Values | Style (Dark cards on Home, Light section on About), heading, short intro (light), Cards go to (dark) | block fields |
| | Values (small label, title, short text) | Settings → Our Values (one set for every page) |
| | → arrow | decorative icon (aria-hidden) |
| About Pan Motors | Media type (Image or Video), photo; for a video: source (an uploaded MP4/WebM, or a YouTube/Vimeo link) and a poster photo; small red line, heading, three highlights, scroll colour fade on/off | block fields |
| | Paragraph | Settings → Business → One-sentence description |
| Latest Cars | Small red line, heading, how many | block fields |
| | Cars (photo, slider caption, place), newest first by date | Cars |
| | Counter "01 / 06", ← → | computed / decorative (buttons have translated aria-labels) |
| Pan Motors Live | Small red line, heading, Instagram button text, posts (video/photo, link, caption, likes, comments) | block fields |
| | Instagram link | Settings → Social → Instagram |
| | ♥ ✎ | decorative icons (aria-hidden; "likes"/"comments" screen-reader text) |
| The Showroom | Small red line, heading, intro, photos, show opening hours | block fields; captions from Media → Caption |
| | Opening hours rows | Settings → Opening hours |
| Come And See | Heading, intro, preview form labels, hints and button (until the plugin is connected) | block fields |
| | Address, phones, email | Settings → Business / Contact |
| | Website, logo | site address (automatic), Customizer |
| | Form | Settings → Technical → Enquiry form shortcode (admin) |
| | "Form plugin shortcode not set" | admin notice (administrators only, translation-ready) |

## About (D12)

| Element | Where |
|---|---|
| Small red line, title (H1, line breaks kept), intro, photo, black and white or colour, brightness | Page header block, Photo style (the intro is also the page's meta description without an SEO plugin; the photo's alt text from the media library) |
| Our story: small red line, heading, photo | Story block |
| First story paragraph | Story block → First paragraph; empty: Settings → Business → One-sentence description |
| Further paragraphs | Story block → More paragraphs (bold, italic, links) |
| What We Do: heading; per tile label, title, text, photo (2–4 tiles) | What We Do block |
| Our Values | Our Values block, Light section (heading, intro); the values in Settings → Our Values |
| See The Showroom card: small red line, heading, photo, two buttons (text and page) | Photo call to action block |
| Footer colour | Page settings → Footer style (Auto: light, the last section is light) |

## Featured Cars (D12)

| Element | Where |
|---|---|
| Small red line, title (H1), intro | Page header block, Text style |
| The cars | Cars: every car with "On the Featured Cars page" on, in their order (or the Featured ones: Cars grid → Which cars) |
| Filter buttons and counts | made from the cars' marques (most cars first, then A to Z); first button text: Cars grid → First filter button |
| Card: number, year, marque, model, power / 0–100 / mileage | Cars (the number is the car's place in the order, prefix in Cars grid → Number prefix) |
| Car sheet: photo, marque, model, note, spec rows | Cars; row labels in Cars grid → Label fields |
| Car sheet button | Cars grid → Car sheet button text and page |
| "Seen One / You Like?" band | CTA band block (heading with line breaks, optional text, button text, button page) |
| Footer | Page settings → Footer style (Auto: dark, the last section is dark) |

## The Showroom (D12)

| Element | Where |
|---|---|
| Small red line, title (H1), intro, photo, colour, brightness | Page header block, Photo style |
| "Inside": heading, hint, photos | Photo slider block; each photo's caption and alt text in the media library |
| Opening hours rows | Settings → Opening hours (day and time) |
| Address, phones, email, directions link | Settings → Contact (address lines: legal name; area, street; city postcode, country) |
| "Opening hours", "Find us", "Get directions" | Visit block |
| "Book A Visit" card: heading, photo, colour, brightness, height, two buttons | Photo call to action block |
| Footer | Page settings → Footer style (Auto: light) |

## Contact (D12)

| Element | Where |
|---|---|
| Small red line, title (H1), intro | Page header block, Text style |
| Call / Email / Find / Follow rows: the details | Settings → Contact (phones, email, address, Google Maps link) and Social (Instagram, Facebook); a row with an empty detail is left out |
| Row labels and actions ("Call us", "Call →") | Contact rows block |
| Form | Contact form and map block → Form shortcode (admin), else Settings → Technical → Enquiry form shortcode; else a preview form with the block's labels and hints |
| Map | Settings → Contact → Map embed query; loads only after "Show map" (Google cookies). Empty query: a directions link. Note, button and link texts in the block |
| Showroom hours card | Settings → Opening hours; the small red line in the block |
| Questions | Questions block (heading, questions and answers) |
| Contact button in the header | shows filled on this page (automatic) |
| Footer | Page settings → Footer style (Auto: dark) |

## Events (TASKS 4d, `docs/events.md`)

### Adding an event (client guide)

1. **Events → Add event.** The screen opens with the title at the top, two empty paragraphs for the story under it, and the **Event details** and **Photographs** boxes below (drag the bar between them to give either more room).
2. **Title**: the event's name, as it should read on the page (e.g. "Night at Avenue 65"). It becomes the big heading of the event page and its address.
3. **Main image** (sidebar → Event → Set main image): required. The photo at the top of the event page, the first photo on the Events page and the image shared on social media. Landscape, at least 2400px wide, JPG or WebP. Its alt text in the media library describes it.
4. **Story**: a few paragraphs about the event. Headings, lists, quotes, images and buttons are available from the + button. Leave a paragraph empty and it is not shown.
5. **Event details**:
   - *Event type*: one word in red above the title (Evening, Drive, Unveiling…).
   - *Start date* (required) and *End date* (only for events over several days; it cannot be before the start date).
   - *All day*, or *Start time* and *End time* (shown as "19:30 — 23:00"). An event counts as past after its end time, or at the end of its last day when there is no end time.
   - *Place name* (required): short, as it reads on the page ("Showroom, Mesoyi"). *Guests*: who can come ("By invitation", "Open"); empty hides it.
   - *Address* and *Map link*: optional, for calendars and Google (not shown on the page). Without a map link, a Google Maps search for the address is used.
   - *Summary*: one or two sentences for the Events page and Google. *Lede*: the large opening sentence on the event page (empty: the summary).
   - *Status*: Automatic (Upcoming, then Past event on its own), or Cancelled, Postponed, Sold out (shown on the label; the buttons disappear).
   - *Registration button* text and page (empty: the Contact page), and *Call button* (calls the first phone number in Pan Motors settings → Contact).
6. **Photographs**: up to 12 photos for the slider on the event page; the first two also fill the Events page row after the main image. A caption in the media library shows under the photo. No photos: no slider.
7. **Publish.** If something required is missing, WordPress says what (start date, place, main image, or an end date before the start date). The event appears on the Events page straight away, under Upcoming, and the tab counts update.

### What happens by itself

- **Past events** move to the Past tab (newest first) once they are over, keep their page with the label "Past event", and lose the Register, Call and Add to calendar buttons. Nothing needs to be changed by hand.
- **Add to calendar** downloads the event for the visitor's calendar (Google, Apple, Outlook).
- The Events page, Google (structured data, sitemap) and AI tools (llms.txt) read the same fields: the event is entered once.
- Previous / Next event at the bottom of each event page go through all events in date order.
- **Delete an event**: Events → hover → Bin. It disappears from the Events page; the other events are not affected.

## Other inner pages (placeholders until their v2 build)

| Element | Where |
|---|---|
| Breadcrumb "Home" | the Home page's title |
| Page title (H1) | the page title |
| Small red line, intro, top image | Page top block (the intro is also the page's meta description without an SEO plugin) |
| Opening text, "Getting here" | core Heading / Paragraph blocks |
| Sections | the same blocks as on Home |
| Call to action | Call to action block (heading, text, button text, button page) |
| Questions (Contact) | Questions block; answers are in the HTML (`details`/`summary`) |

## Footer and 404

Footer colour: each page's **Page settings → Footer style** (sidebar of the page editor): Auto (the colour of the last visible section on the page), Dark or Light. Light inverts the logo, or uses Design → Logo for light backgrounds.

Unchanged: Settings → Footer (footer line, copyright with `{year}`), Settings → Page not found (all texts and button labels). The 404 Contact button uses Settings → Technical → Contact button goes to.

## Editor experience

- Blocks preview with the front-end markup and CSS (`main.css` + `editor.css` in the editor canvas). In the editor: no entrance or scroll animations, videos show their photo, the marquee holds still, sliders show their first slide, the About section stays dark. An empty block shows a dashed placeholder saying what to fill. The page title sits as a small label above the blocks.
- Labels and instructions are written for the client: what the field is, where it shows, how long. Image and video fields state size and format; minimum sizes protect the layout (car photos ≥ 1960 × 1102px). Text that breaks the layout has a character limit.
- Text formats: bold, italic, link. Core block styles the design doesn't have (outline buttons, rounded images, plain quotes) are removed. Embeds: YouTube and Vimeo in the inserter (Instagram links become embed blocks when pasted, and print as links). No Openverse, block directory, remote patterns, or code editor for the client.
- Messages link to where shared content is edited ({settings} → Pan Motors settings, {cars} → Cars).

## Client role (Editor)

- Can: edit all pages in the block editor, Cars, synced patterns, Pan Motors settings (including Our Values) (not Technical), Appearance → Menus, Customise → Site Identity (logo) and Menus, Media.
- Cannot: theme files, themes, plugins, users, WordPress settings, ACF field groups, Additional CSS, the homepage setting, the Site Editor ("Design").
- Hidden: Posts, Comments, Themes, Design, Fonts (menus, toolbar and direct URLs).
- `inc/admin.php` adds `edit_theme_options` to the Editor role while the theme is active and removes it on theme switch.

## How this was tested (26 Sep 2026)

With a test Editor created for the run and deleted afterwards with `--reassign=1`:
- `dev/tests/editor-caps.php`: 14 capability checks, all pass (pages, Cars, synced patterns, settings, menus, logo, media; no theme files, themes, plugins, users, options or field groups).
- `dev/tests/editor-admin.mjs`: wp-admin menus (Dashboard, Media, Pages, Cars, Pan Motors, Appearance, Profile, Tools), blocked screens, Technical tab hidden, Home opens in the block editor, Cars and Patterns reachable.
- `dev/tests/editor-blocks.mjs`: on Home, all nine blocks preview with the same text as the site; one field per block typed into the sidebar (the 7 blocks with fields), saved, shown on the site, restored; Featured Cars moved below Our Values and back; Latest Cars hidden (section and `slider-drag.js` gone) and shown again; the Our Values pattern edited once and shown on Home and About; a car added under Cars (Featured, order 0) appeared as tile 1 of Featured Cars and slide 1 of Latest Cars, then was deleted and disappeared. The hero reports `canRemove=false canMove=false`.

## Search and AI (TASKS §5)

| Element | Where |
|---|---|
| Business facts in the JSON-LD and llms.txt | Settings → Business, Contact, Opening hours, Marques, Social (the same fields as the visible text) |
| Photos for Google and AI search (not shown on the site) | Settings → Business → Business photos (up to 3) |
| Map position (JSON-LD `geo`) | Settings → Contact → Latitude and Longitude (both, or neither is used) |
| AI crawlers in robots.txt | Settings → Technical → Allow AI crawlers (admin) |
| /llms.txt on or off | Settings → Technical → llms.txt (admin) |
| A page's type for search engines (About page, Contact page) | Page settings → Type for search engines |
| Meta description without an SEO plugin | the page header intro; Home: the one-sentence description |

