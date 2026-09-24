# Pan Motors theme build tasks

Tick each task when it is done and checked in the browser.

## 0. Setup (you)
- [x] LocalWP site `panmotors.local`, WP_DEBUG on, ACF Pro installed
- [x] `wp-content/themes/panmotors/` created and set up as a git repo
- [x] Full Claude Design export (with images and videos) copied to `_design/`
- [ ] `_design/` excluded from deploys (add it to a `.distignore`, or keep it out of the production zip)
- [x] Decisions D1-D5 answered (D6 language: English for now, translation-ready)

## 1. Foundation
- [ ] Theme header in `style.css`, `functions.php` loading `inc/`
- [ ] `inc/setup.php`: theme supports (title-tag, post-thumbnails, custom-logo, html5, responsive-embeds), menus `primary` + `footer`, image sizes
- [ ] `inc/enqueue.php`: fonts, main.css, JS modules with filemtime versions
- [ ] Self-hosted woff2 fonts + `@font-face`
- [ ] `main.css`: tokens, reset, base type, utilities, reveal animations, reduced-motion
- [ ] `index.php`, `page.php`, `404.php` minimal, in the site style

## 2. Content model
- [ ] ACF options page with contact, socials, hours, form shortcode
- [ ] ACF front page field group, one tab per section (featured cars, latest cars and live posts as repeaters)
- [ ] Field groups exported to `acf-json/`
- [ ] Demo content entered from `_design/` data so the page looks like the design

## 3. Header and footer
- [ ] `header.php`: nav, logo, menu, contact pill, burger, mobile menu
- [ ] `menu.js`
- [ ] `footer.php` with dynamic year

## 4. Homepage sections (one per session)
- [ ] Hero + `hero-video.js`
- [ ] Marquee
- [ ] Featured Cars + `car-tile.php` (showcase only, no links by default)
- [ ] Our Values
- [ ] About + `heritage-fade.js`
- [ ] Latest Cars + `slider-drag.js`
- [ ] Pan Motors Live + `live-videos.js`
- [ ] Showroom + hours + `showroom-slider.js`
- [ ] Enquire card + `.pm-form` styles for plugin forms + static fallback form
- [ ] `reveal.js` wired to all sections

## 5. Extra templates
- [ ] Privacy and cookie policy pages use `page.php` cleanly

## 6. Finish
- [ ] Escaping review across all templates
- [ ] Accessibility pass: keyboard through menu, sliders, form. Focus states visible. Contrast on accent text
- [ ] Performance: image sizes, lazy loading, video preload, no render-blocking fonts, Lighthouse run
- [ ] SEO plugin installed, OG image absolute, LocalBusiness schema with hours from options
- [ ] Cross-browser: Safari (video autoplay, backdrop-filter, color-mix), Chrome, Firefox, iOS, Android
- [ ] Install the form plugin, paste its shortcode in options, check it matches the design
- [ ] Remove demo content flags, deploy
