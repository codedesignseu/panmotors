# Inner pages and global design settings (D12)

Decision 27 Sep 2026. Source: the second Claude Design export, stored in `_design/v2/` (index, cars, about, showroom, contact). `_design/` (v1) stays as the reference for the homepage build.

Scope from Makis:
- About: exactly as `_design/v2/about.html`. Our Values on the homepage uses the same four values as About.
- Featured Cars: exactly as `_design/v2/cars.html`.
- Latest Cars: out of scope. Leave the page and its menu item untouched until decided.
- Showroom: exactly as `_design/v2/showroom.html`.
- Contact: exactly as `_design/v2/contact.html`, plus the existing FAQs at the bottom.
- Theme-wide: design settings for colours, fonts, typography, shape and logos.

All rules in CLAUDE.md, blocks.md and theme-map §9 (SEO/GEO) still apply. Every new section is a `pm/*` ACF block. Every visible text, image and link is editable. JS never injects text: all content is in the server HTML.

---

## 1. Global design settings

New tab on the options page: **Pan Motors → Design**. Administrators only by default. A single capability switch lets Editors see it later.

### 1.1 Colours (colour pickers, with the design values as defaults)
| Token | Default | Used for |
|---|---|---|
| `--ink` | `#0c0b0b` | dark backgrounds, text on light |
| `--paper` | `#f3f2f2` | light backgrounds, text on dark |
| `--accent` | `#ec3013` | eyebrows, active nav, primary buttons, focus, selection |
| `--surface` | `#161514` | image placeholders, dark cards (contact form card) |

Every hardcoded colour in `main.css` is converted to these tokens. Transparent variants use `color-mix()` from the tokens, e.g. `rgba(243,242,242,.14)` becomes `color-mix(in srgb, var(--paper) 14%, transparent)`. Show a warning in the admin when accent on ink or ink on paper falls below WCAG AA contrast.

As built (27 Sep 2026): flat colours use `color-mix()` with the design's 8-bit alpha (e.g. 14.11764706% for .14), so the defaults draw pixel for pixel as before. Gradient stops use `rgb(var(--ink-rgb) / a)` with channel tokens printed by `inc/design.php`, because gradients with `color-mix()` stops render on a different path in Chrome (every overlay changed by 1–3/255). Fixed tokens that are not settings: `--on-accent` (#fff), `--ink-soft` (caption ink), `--shadow` (#000).

### 1.2 Typography
- **Heading font** and **Body font**: a select from a bundled, self-hosted set (latin + latin-ext woff2, OFL licensed):
  - Headings: Bodoni Moda (default), Playfair Display, Cormorant Garamond, DM Serif Display
  - Body: Archivo (default), Inter, Manrope, DM Sans
  - Plus "Custom": upload regular and italic woff2 files for either slot.
- **Heading scale** (85–115%, default 100) multiplies every heading clamp().
- **Body size** (14–18px, default the design's).
- Only the selected fonts are loaded and preloaded.

### 1.3 Shape
- **Corner radius** 0–48px, default 24 (the design's own `cornerRadius` prop). Drives `--r`.

### 1.4 Logos and identity
- Header logo: the Customizer site logo (as now). Add **logo height** desktop and mobile (defaults 46px / the current mobile value).
- **Logo for light backgrounds**: optional upload. When empty, the header logo is inverted with CSS, as in the design.
- Site icon (favicon): WordPress Site Icon, linked from the Design tab.
- **Default share image** (OG) for pages without their own.

### 1.5 Output
- Tokens print once as a small inline `:root{}` in `<head>` on the front end and in the block editor, so previews match.
- **Proof:** with all settings at their defaults, the homepage must be pixel-identical to the current baseline (dev/diff.mjs). Then change each setting once and confirm it applies everywhere, including the editor.

---

## 2. Shared changes across pages

### 2.1 Navigation
- The current page's menu link shows in the accent colour at full opacity (v2 design). Replaces the current underline active state.
- On the Contact page the Contact pill shows filled (paper background, ink text).
- Menu items stay as decided: Featured Cars, About Pan Motors, Latest Cars, Showroom, plus the Contact pill. Don't copy the v2 nav's "Live" or "Our Values" links.

### 2.2 Footer colour follows the page
v2 footers are dark on pages that end dark (Featured Cars, Contact) and light on pages that end light (About, Showroom, Home). Add a page setting "Footer style: Auto / Dark / Light". Auto takes the background of the last visible block. The logo inverts on light, as now. Footer menu stays as ours (includes the legal pages).

### 2.3 Page headers
Two variants of `pm/page-header`, both holding the page H1:
- **Text** (Featured Cars, Contact): top padding 190px (desktop), accent eyebrow, H1 `clamp(56px, 10vw, 184px)` line-height .86, intro paragraph right-aligned in the flex row (max 360px), heroIn entrance.
- **Image** (About, Showroom): full-bleed image, 92vh / min 640px (88svh / min 520px under 880px), slow zoom-in (`heroZoom` 1.14 to 1.02 over 3.2s), gradient overlay, copy bottom-left, intro bottom-right. Fields: image, filter (none / grayscale), brightness (.5–.8). About uses grayscale .6, Showroom plain .62.

### 2.4 CTA blocks
- `pm/cta-image` (About, Showroom): rounded card with background image, left gradient, optional eyebrow, big H2, primary pill (paper, turns accent on hover) + optional outline pill. Sits in a paper section.
- `pm/cta-band` (Featured Cars): paper card on the dark page, H2 left, ink pill right.

---

## 3. Our Values: one source, two looks

The four values move to **Options → Our Values** (repeater: index, title, body). This replaces the synced pattern.

`pm/values` gets a **Style** option:
- **Dark cards (home)**: as the current homepage section: outlined cards, arrow row, whole card links (to About), paper invert on hover. Grid `auto-fit minmax(260px, 1fr)` so four fit at 1440.
- **Light section (about)**: paper section with rounded top, H2 left + intro right, cards with ink hairline border, no arrow, no link, hover inverts to ink and lifts.

Default content (from v2 About): 01 Chosen, 02 Transparent, 03 Looked After, 04 Personal, with their v2 texts. The homepage now shows these four.

---

## 4. Homepage updates (from `_design/v2/index.html`)

- Our Values: the four values (§3), dark style, cards link to About.
- Featured Cars section: add the "All featured cars →" outlined pill under the intro, linking to the Featured Cars page (new in v2). Label and link are block fields.
- Everything else on Home stays as built. Re-run the baseline diff: only these two areas may change.

---

## 5. About (`_design/v2/about.html`)

Blocks in order:
1. `pm/page-header` Image: eyebrow "About — Mesoyi, Paphos", H1 "About / Pan Motors", intro "Luxury in Motion. A family-run car house on Avenue 65."
2. `pm/story`: two columns, text left (eyebrow "Our story", H2 "One Roof, One Family", rich text of 1–3 paragraphs, max 48ch), 4:5 image right (slides in from the right side with `data-rise-l`, zoom on hover). The first paragraph defaults to the Options one-sentence description, for GEO consistency.
3. `pm/services` ("What We Do"): H2 + a row of image tiles (flex, hover grows to 2.2, height `clamp(440px, 42vw, 600px)`), each with an accent index, H3 title, body. Stacks under 880px (88vw, max 480px). Repeater, 2–4 tiles.
4. `pm/values` Light section (§3), heading "Our Values", intro "Four things we hold to with every car and every client."
5. `pm/cta-image`: eyebrow "Avenue 65, Mesoyi", H2 "See The / Showroom", buttons "Visit the showroom →" (Showroom page) and "Book a visit" (Contact).
6. Footer light.

---

## 6. Featured Cars (`_design/v2/cars.html`)

### 6.1 Car data (pm_car)
Add fields to the Cars post type: year, power, acceleration (0–100), mileage, engine, gearbox, colour. The note field is the lightbox description. Reference number shows as "No. 01…" from the car's order. Seed the 12 cars from the v2 export (two have no photo, so leave the image empty and render the surface placeholder).

### 6.2 `pm/cars-grid` block
- **Header** is a `pm/page-header` Text above it (eyebrow "Pan Motors — Paphos", H1 "Featured / Cars", intro).
- **Filter pills**: "All" plus one pill per marque that has cars, each with its count. Built from the data, never hardcoded. Real `<button>`s with `aria-pressed`. The active pill is filled paper. Changing filter fades the grid out and back (.38s) as in the design. Without JS all cars show and the pills are hidden.
- **Rows**: repeating pattern 3, 2, 3, 2, 3 cards. In 2-rows the first card is wider (flex 1.5), in 3-rows the middle one. Height `clamp(420px, 44vw, 640px)`. Hover grows the card to 2.4 and zooms the image. Top corners: "No. xx" and year. Bottom: accent marque, H3 model, and a spec line (power, 0–100, mileage) that fades in on hover (always visible under 880px and on touch).
- **Lightbox**: click or Enter on a card opens a modal `<dialog>`: image 16:11, marque, close button, H2 model, note, spec table (Year, Engine, Power, Acceleration, Gearbox, Colour, Mileage), "Enquire →" to Contact, prev/next buttons, counter. Esc closes, arrow keys move within the current filter, focus is trapped and returns to the card, body scroll locks. Each car's full sheet exists in the page HTML (for example a hidden `<dl>` per card that the dialog shows), so nothing is injected.
- Block options: show all cars or Featured only (default all), and the intro text.
- Under 880px: rows stack, cards 108vw tall (max 560px), dialog becomes one column.

### 6.3 Page
1. `pm/page-header` Text + `pm/cars-grid`
2. `pm/cta-band`: "Seen One / You Like?", button "Arrange a viewing →" to Contact.
3. Footer dark.

---

## 7. Showroom (`_design/v2/showroom.html`)

1. `pm/page-header` Image: plain (no grayscale), brightness .62, eyebrow "Avenue 65, Mesoyi — Paphos", H1 "The / Showroom", intro "Paphos, open six days a week. Sales, service and the boutique under one roof."
2. `pm/photo-slider` ("Inside"): paper section with rounded top that overlaps the hero by 24px, blurred copy of the current photo behind a radial wash, H2 "Inside" + counter right, 64px round arrows either side (below the photo under 880px), 16:10 photo with big shadow, caption left + "Drag or use arrows" right, pill dots under (active dot 34px wide). Drag or swipe more than 50px changes photo. Arrow keys work when the slider has focus. Crossfade: fade out, swap, fade in. All photos in the HTML. Photos from a gallery field with captions from the media library.
3. `pm/visit`: two columns on paper. Left: eyebrow "Opening hours" + hour rows (day, time in Bodoni, hover invert). Right: eyebrow "Find us", address in large Bodoni (from Options), phone and email links, "Get directions →" outline pill (Options map URL).
4. `pm/cta-image`: H2 "Book / A Visit", buttons "Contact us →" (Contact) and "See featured cars" (Featured Cars). No eyebrow.
5. Footer light.

---

## 8. Contact (`_design/v2/contact.html`)

1. `pm/page-header` Text: eyebrow "Pan Motors — Paphos, Cyprus", H1 "Contact / Us", intro "Call, write, or walk in during showroom hours."
2. `pm/contact-details`: four full-width link rows from Options: Call us (tel), Email us (mailto), Find us (map URL, new tab), Follow us (Instagram, new tab). Grid `220px 1fr auto`: small label, value in Bodoni, action text ("Call →"). Hover inverts to paper and indents. Single column under 880px.
3. `pm/contact-form`: two columns.
   - Left: dark surface card, H2 "Write To Us", the form. The block has its own "Form shortcode" field that falls back to the Options one, so Home (name, email, phone, message) and Contact (name, email, subject, message) can use different forms. Same `.pm-form` styles. The static fallback uses the v2 fields.
   - Right: map card (min 420px) + paper hours card ("Showroom hours", rows from Options).
   - Map: Google Maps embed from an Options "Map embed query" field, lazy loaded, greyscale-inverted filter that returns to colour on hover (as in the design). It sets Google cookies, so it loads only after a click on a static placeholder ("Show map") unless a consent plugin is added. The placeholder is styled in the same dark card.
4. `pm/faq`: the existing FAQs, styled for the dark page in the site's hairline-row style (Bodoni question, body answer, accent on open), native `details`/`summary`. Outputs the FAQPage schema.
5. Footer dark.

---

## 9. Build order (one session each)

1. Global design settings (§1), with a zero-change diff at the defaults.
2. Our Values to Options + homepage updates (§3, §4) + nav active state + footer auto style (§2.1, §2.2).
3. About (§5), including `pm/page-header`, `pm/story`, `pm/services`, `pm/cta-image`.
4. Featured Cars (§6).
5. Showroom (§7).
6. Contact (§8).

Each session: compare side by side against `_design/v2/<page>.html` at 1440, 1080, 880 and 390px with the dev tools, check that every field edits in the block editor, run the heading outline, keyboard and reduced-motion checks, and keep debug.log and the console clean. The homepage baseline diff must still pass after every session, except for the intended §4 changes.
