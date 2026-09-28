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
- [x] Cause of the lost sections found and documented (migration-blocks §0)
- [x] DB backup, baseline snapshot of Home (HTML, head assets, screenshots 1440/390, section heights) via `dev/tests/snapshot.mjs`
- [x] Cars post type `pm_car` + Car field group + seeded cars
- [x] `inc/blocks.php`, `theme.json`, allowed blocks, editor CSS and JS; Home still identical
- [x] Blocks: `pm/hero`, `pm/marquee`, `pm/featured-cars`, `pm/values`, `pm/about`, `pm/latest-cars`, `pm/live`, `pm/showroom`, `pm/enquire`
- [x] Synced pattern "Our Values", "Homepage (full design)" pattern, hero lock on Home
- [x] Seed builds Home as block markup; `front-page.php` and `page.php` print the blocks (`panmotors_the_blocks()`)
- [x] `dev/tests/diff.mjs`: Home identical to the baseline (HTML, head, pixels, heights, JS behaviour)
- [x] Old model removed: `templates/`, per-page field groups, `panmotors_page()` content lookups, section switches (core Hide instead), orphaned field data
- [x] Editor tests rewritten for blocks (test user deleted with `--reassign=1`), `editability.md` rewritten
- [x] `pm/faq`, `pm/page-hero`, `pm/cta-band` (placeholder look until the inner pages are designed)

## 4c. Inner pages and design settings (D12, `docs/inner-pages.md` §9)
One session each. Every session: compare with `_design/v2/<page>.html` at 1440, 1080, 880 and 390px, check every field in the block editor, heading outline, keyboard, reduced motion, clean debug.log and console, and the homepage baseline diff (only the §4 changes may differ).
- [x] 1. Global design settings (§1): Design tab, tokens, fonts, contrast warning; zero-change diff at the defaults
- [x] 2. Our Values to Options + homepage updates (§3, §4) + nav active state + footer auto style (§2.1, §2.2)
- [x] 3. About (§5), including `pm/page-header`, `pm/story`, `pm/services`, `pm/cta-image`
- [x] 4. Featured Cars (§6)
- [x] 5. Showroom (§7)
- [x] 6. Contact (§8)

## 5. SEO and GEO
- [x] `inc/schema.php`: one JSON-LD `@graph` on every page from Options and the page (AutoDealer, WebSite, WebPage / AboutPage / ContactPage, BreadcrumbList on inner pages, FAQPage from `pm/faq`); empty values left out; no Car, Product or Offer
- [x] SEO plugin coordination: Yoast and Rank Math schema switched off by their filters (one business entity); theme meta description only without an SEO plugin
- [x] robots.txt through the `robots_txt` filter (AI crawlers allowed, Technical switch to block them), one Sitemap line; core sitemap: pages only
- [x] `/llms.txt` (rewrite rule, Technical switch)
- [x] Skip link, `lang`, landmarks, heading outline checked (`dev/tests/schema.mjs`)
- [x] Preload hero poster and main fonts (only the fonts chosen in Design)
- [x] Inner pages built from blocks (D11) as the v2 design (D12, section 4c): Featured Cars, About, Showroom, Contact; Latest Cars kept as a placeholder (design undecided); Privacy and Cookie policy on `page.php`

## 6. Finish
- [x] Escaping review across all templates (PHPCS with WordPress Coding Standards in `dev/`, 0 errors/warnings, no rules excluded; i18n and empty-field review)
- [x] Accessibility pass: keyboard through menu, sliders, form. Focus states visible. Contrast on accent text (axe 0 serious/critical on all pages at 1440 and 390; reflow at 320px and 200%; text over photos 4.5 : 1 against the brightest pixel, commit 5398245)
- [ ] Performance: image sizes, lazy loading, video preload, no render-blocking fonts, Lighthouse run (done except the Lighthouse "after" run: `node dev/tests/lighthouse.mjs after`)
- [ ] SEO plugin installed and configured, its own business schema turned off, OG image absolute (Rank Math installed and active; configuration pending)
- [ ] Rich Results Test and Schema validator clean
- [ ] NAP matches the Google Business Profile exactly
- [x] Cross-browser, automated: Chrome, WebKit and Firefox (Playwright): keyboard, dialog, sliders, video autoplay, backdrop-filter, color-mix (`dev/tests/interactions.mjs`)
- [ ] Cross-browser by hand: Safari (macOS), iOS Safari, Android Chrome
- [ ] Install the form plugin, paste its shortcode in options, check it matches the design (Fluent Forms installed and active; shortcode and check pending)
- [x] Deploy package: `bash dev/build-zip.sh` (production files only, `.distignore`), `screenshot.png`, `Requires PHP: 8.2` (tested on a fresh install with PHP 8.2)
- [ ] Remove demo content flags, deploy

### Still to do by Efthimios
1. **Rank Math**: run its setup wizard. Then Titles & Meta → Local SEO / Schema: leave the business schema off (the theme already switches Rank Math's schema off with its filter and prints one AutoDealer graph, `inc/schema.php`). Set the default OG/Twitter image to the 1200×630 showroom photo. Sitemap: pages only (no posts, no Cars, no media attachments). Check one page's source: one JSON-LD graph, no second Organization/LocalBusiness. Set the meta descriptions in Rank Math: with an SEO plugin active the theme stops printing its own, so pages have none until Rank Math is set up (checked 28 Sep 2026: `node dev/tests/schema.mjs` passes, one graph, no description).
2. **Fluent Forms**: build the enquiry form (name, email, phone, message, consent if needed) with a visible label on every field, set the notification email and the success message. Paste its shortcode in Pan Motors settings → Technical → form shortcode (and in the Contact page's Contact form block if it should differ). Check Home (Enquire) and Contact at 1440 and 390, submit it empty once to see the error styles, send one real test.
3. **Rich Results Test** and **Schema.org validator** on Home, About, Showroom and Contact (after deploy, on the live URLs).
4. **NAP**: compare name, address, phones and hours in Pan Motors settings with the Google Business Profile, character for character.
5. **Safari, iOS and Android by hand**: menu, cars dialog, sliders (swipe), hero video autoplay (muted), blurred backdrops, the Contact map button.
6. **Content**: replace demo photos with the client's (WebP, descriptive file names and alt text, see theme-map 9.5); delete the default "Sample Page"; the red labels over photos now sit on darker shades, so check the look with the client (revert commit 5398245 if they prefer the old one).
7. **Deploy**: remove the demo content flags, upload `dev/.cache/dist/panmotors-theme.zip` (rebuild it with `bash dev/build-zip.sh` after the last commit), set the environment type to production, then check debug.log and the console on the live site.
