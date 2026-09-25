# Pan Motors theme build tasks

Tick each task when it is done and checked in the browser.

> **D11 (25 Sep 2026):** content moves to Gutenberg + ACF Blocks, see `docs/blocks.md`. Section 4b is the migration, in the order of `docs/migration-blocks.md` §10. Ticked items in sections 2 and 4 describe the old per-page model; their front end stays, their content source changes in 4b.

## 0. Setup (you)
- [x] LocalWP site `panmotors.local`, WP_DEBUG on, ACF Pro installed
- [x] `wp-content/themes/panmotors/` created and set up as a git repo
- [x] Full Claude Design export (with images and videos) copied to `_design/`
- [x] `_design/` excluded from deploys (add it to a `.distignore`, or keep it out of the production zip)
- [x] Decisions D1-D5 answered (D6 language: English for now, translation-ready)

## 1. Foundation
- [x] Theme header in `style.css`, `functions.php` loading `inc/`
- [x] `inc/setup.php`: theme supports (title-tag, post-thumbnails, custom-logo, html5, responsive-embeds), menus `primary` + `footer`, image sizes
- [x] `inc/enqueue.php`: fonts, main.css, JS modules with filemtime versions
- [x] Self-hosted woff2 fonts + `@font-face`
- [x] `main.css`: tokens, reset, base type, utilities, reveal animations, reduced-motion
- [x] `index.php`, `page.php`, `404.php` minimal, in the site style

## 2. Content model
- [x] ACF options page with entity facts (names, one-sentence description, address, geo, phones, email, hours with machine fields, marques, sameAs profiles), form shortcode
- [x] ACF front page field group, one tab per section (featured cars, latest cars and live posts as repeaters)
- [x] Field groups exported to `acf-json/`
- [x] Demo content entered from `_design/` data so the page looks like the design

## 3. Header and footer
- [x] `header.php`: nav, logo, menu, contact pill, burger, mobile menu
- [x] `menu.js`
- [x] `footer.php` with dynamic year

## 4. Section components (home context first; page context after the inner pages are designed)
- [x] Hero + `hero-video.js` (home only)
- [x] Marquee (home only)
- [x] `featured-cars` + `car-tile.php` (home context; showcase only, no links by default)
- [x] `values` (home context; values live on the About page)
- [x] `about` + `heritage-fade.js` (home context; fade on home only)
- [x] `latest-cars` + `slider-drag.js` (home context: slider; grid on the page later)
- [x] `live` + `live-videos.js` (home only, posts on Home)
- [x] `showroom` + `showroom-slider.js` (home context)
- [x] `hours` (used in the home Showroom section)
- [x] `enquire` card + `.pm-form` styles for plugin forms + static fallback form (home context)
- [ ] `cta-band` (inner pages only; basic version in place, final styling with the inner page design)
- [x] `reveal.js` (wired to each section as it is built)

## 4b. Blocks migration (D11, `docs/migration-blocks.md`)
- [ ] Cause of the lost sections found and documented (migration-blocks §0)
- [ ] DB backup, baseline snapshot of Home (HTML, head assets, screenshots 1440/390, section heights) via `dev/tests/snapshot.mjs`
- [ ] Cars post type `pm_car` + Car field group + seeded cars
- [ ] `inc/blocks.php`, `theme.json`, allowed blocks, editor CSS and JS; Home still identical
- [ ] Blocks: `pm/hero`, `pm/marquee`, `pm/featured-cars`, `pm/values`, `pm/about`, `pm/latest-cars`, `pm/live`, `pm/showroom`, `pm/enquire`
- [ ] Synced pattern "Our Values", "Homepage (full design)" pattern, hero lock on Home
- [ ] Seed builds Home as block markup; `front-page.php` and `page.php` output `the_content()`
- [ ] `dev/tests/diff.mjs`: Home identical to the baseline (HTML, head, pixels, heights, JS behaviour)
- [ ] Old model removed: `templates/`, per-page field groups, `panmotors_page()` content lookups, section switches
- [ ] Editor tests rewritten for blocks (test user deleted with `--reassign=1`), `editability.md` rewritten
- [ ] `pm/faq`, `pm/page-hero`, `pm/cta-band`

## 5. SEO, GEO and extra pages
- [ ] `inc/schema.php`: JSON-LD graph from options (AutoDealer, WebSite, WebPage, FAQPage)
- [ ] Skip link, `lang`, landmarks, heading outline checked
- [x] Preload hero poster and main fonts
- [ ] robots.txt rules and optional `llms.txt`
- [ ] Inner pages (D9, `pages.md` §4), built from blocks (D11): `pm/page-hero`, core text blocks, section blocks, `pm/cta-band`
  - [ ] Featured Cars
  - [ ] About (with Our Values)
  - [ ] Latest Cars
  - [ ] Showroom (with "Getting here")
  - [ ] Contact (enquire, hours, map link, FAQ, no CTA band)
  - [ ] Privacy and Cookie policy on `page.php`
- [ ] BreadcrumbList and per-page WebPage in the JSON-LD graph

## 6. Finish
- [ ] Escaping review across all templates
- [ ] Accessibility pass: keyboard through menu, sliders, form. Focus states visible. Contrast on accent text
- [ ] Performance: image sizes, lazy loading, video preload, no render-blocking fonts, Lighthouse run
- [ ] SEO plugin installed and configured, its own business schema turned off, OG image absolute
- [ ] Rich Results Test and Schema validator clean
- [ ] NAP matches the Google Business Profile exactly
- [ ] Cross-browser: Safari (video autoplay, backdrop-filter, color-mix), Chrome, Firefox, iOS, Android
- [ ] Install the form plugin, paste its shortcode in options, check it matches the design
- [ ] Remove demo content flags, deploy
