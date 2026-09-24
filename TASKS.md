# Pan Motors theme build tasks

Tick each task when it is done and checked in the browser.

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
- [ ] `featured-cars` + `car-tile.php` (showcase only, no links by default)
- [ ] `values` (home; values live on the About page)
- [ ] `about` + `heritage-fade.js` (fade on home only)
- [ ] `latest-cars` + `slider-drag.js` (slider on home, grid on page)
- [ ] `live` + `live-videos.js` (home only, posts on Home)
- [ ] `showroom` + `showroom-slider.js`
- [ ] `hours`
- [ ] `faq` with `details`/`summary` (Contact page)
- [ ] `enquire` card + `.pm-form` styles for plugin forms + static fallback form
- [ ] `cta-band`
- [ ] `reveal.js` wired to all sections

## 5. SEO, GEO and extra pages
- [ ] `inc/schema.php`: JSON-LD graph from options (AutoDealer, WebSite, WebPage, FAQPage)
- [ ] Skip link, `lang`, landmarks, heading outline checked
- [x] Preload hero poster and main fonts
- [ ] robots.txt rules and optional `llms.txt`
- [ ] Inner pages (D9, `pages.md` §4): page hero with breadcrumb, intro, section component, CTA band
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
