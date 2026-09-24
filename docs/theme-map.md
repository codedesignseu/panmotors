# Pan Motors theme map

Source: Claude Design export in `_design/` (index.html, support.js, image-slot.js, _ds/, uploads/).
Target: classic PHP WordPress theme `panmotors`, ACF Pro, vanilla CSS and JS, no build step.

Positioning: Pan Motors is a premium car boutique, not a dealership. The client lists vehicles on other sites. This website is a luxury brand presence only. No inventory, no car pages, no listings, no prices. Every car on the site is showcase content the client edits by hand.

## 1. What the export actually is

`index.html` is not a static page. It is a Claude Design "DC" template that only renders through `support.js`, which loads React from a CDN at runtime. None of this markup can be copied into PHP as-is.

| In the export | What it means | In the theme |
|---|---|---|
| `<x-dc>`, `<helmet>` | Runtime wrappers | Remove |
| `<sc-for list="{{ x }}">` | Loop over data in the `Component` class | PHP `foreach` over ACF data |
| `<sc-if>` | Conditional | PHP `if` |
| `{{ value }}` | Data binding | Escaped PHP output |
| `style-hover="..."`, `style-focus="..."` | Hover/focus styles | Real CSS `:hover` / `:focus-visible` rules |
| `onClick="{{ fn }}"`, `ref="{{ fn }}"` | React handlers | Vanilla JS modules with `data-*` hooks |
| `<script type="text/x-dc">` class `Component` | All data + behaviour | Data → ACF, behaviour → `assets/js/` |
| `<image-slot>` | Design-tool image placeholder with drag/drop editing | Plain `<img>` via `wp_get_attachment_image()` |
| Inline `style=""` on every element | Design-tool output | Move to classes in `main.css` |

Drop entirely: `support.js`, `image-slot.js`, `_ds/_ds_bundle.js` (empty), `robots.txt`, the `noindex` meta tag.

## 2. Design tokens

From the page `<style>` and the `_ds` stylesheet. The page overrides most of the design system, so only the tokens below are in use.

```css
:root {
  --ink: #0c0b0b;          /* page background */
  --paper: #f3f2f2;        /* text on dark, light sections */
  --accent: #ec3013;       /* eyebrows, hovers, submit button */
  --surface-dark: #161514; /* image tile background */
  --r: 24px;               /* corner radius (design prop "cornerRadius", 0-48) */
  --font-display: 'Bodoni Moda', serif;   /* 400, 500, italic 400 */
  --font-body: 'Archivo', sans-serif;     /* 300, 400, 500, 600 */
  --ease: cubic-bezier(.16,.84,.3,1);     /* used on nearly every transition */
  --line-dark: rgba(243,242,242,.14);
  --line-light: rgba(12,11,11,.18);
}
```

Keep from `_ds/styles.css`: `box-sizing` reset, `img { display:block; max-width:100% }`, `figure { margin:0 }`, `:focus-visible` outline in accent. Drop the rest (buttons, cards, tables, dialog, the `h1-h6` 800 weight). The page sets every heading to weight 400 inline, so the theme sets heading styles explicitly.

Fonts: self-host both as woff2 in `assets/fonts/` instead of Google Fonts (EU/GDPR, one fewer third-party request). The `_ds` file also imports Archivo 400/600/800 from Google. Remove that import.

Type scale in use:
- Eyebrow: 11px, `letter-spacing: .32em`, uppercase, accent colour
- H1 hero: `clamp(56px, 9.4vw, 176px)`, line-height .86, Bodoni
- H2 section: `clamp(40px, 6vw, 96px)`, line-height .92, Bodoni
- H3 card: `clamp(26px, 2.2vw, 34px)` (cars), `clamp(32px, 4.4vw, 44px)` (values)
- Body: 14-16px, line-height 1.7-1.8, opacity .6-.78
- Meta/labels: 10-12px, `letter-spacing: .14-.28em`, uppercase

Breakpoints: 1080px (desktop links → burger), 880px (mobile layout). Also `prefers-reduced-motion`.

## 3. Page structure → templates

The design covers the homepage only. All sections render from `front-page.php` as template parts.

| # | Section (anchor) | Template part | Content source | JS |
|---|---|---|---|---|
| 1 | Nav + mobile menu | `header.php` | Custom logo, `wp_nav_menu('primary')`, contact CTA from options | `menu.js` |
| 2 | Hero (`#top`) | `front/hero.php` | ACF front page | `hero-video.js` |
| 3 | Brand marquee | `front/marquee.php` | ACF repeater | CSS only |
| 4 | Featured Cars (`#floor`) | `front/featured-cars.php` | ACF repeater | CSS only |
| 5 | Our Values (`#ways`) | `front/values.php` | ACF repeater | none |
| 6 | About (`#heritage`) | `front/about.php` | ACF front page | `heritage-fade.js` |
| 7 | Latest Cars (`#gallery`) | `front/latest.php` | ACF repeater | `slider-drag.js` |
| 8 | Pan Motors Live (`#live`) | `front/live.php` | ACF repeater (manual) | `live-videos.js` |
| 9 | Showroom (`#showroom`) | `front/showroom.php` | ACF gallery + options (hours) | `showroom-slider.js` |
| 10 | Enquire (`#enquire`) | `front/enquire.php` | Options (contact) + form plugin shortcode | none |
| 11 | Footer | `footer.php` | Options, `wp_nav_menu('footer')` | none |

## 4. Section details

### 1. Nav
- Fixed, gradient from `rgba(12,11,11,.72)` to transparent, `backdrop-filter: blur(2px)`.
- Logo 46px high. Links 12px uppercase, opacity .78 → 1, underline grows from left on hover (`[data-underline]::after`).
- Contact pill button on the right. Under 1080px links and pill hide, 48px round burger shows.
- Mobile menu: fixed panel slides down from the top (`translateY(-102%)` → 0), Bodoni 30px links, accent + indent on hover, click closes.
- Fix while porting: desktop and mobile link order differ (Live/Showroom swapped). One WP menu solves it. Add `aria-expanded`, `aria-controls`, close on Escape, lock body scroll when open.

### 2. Hero
- 100vh, min 680px (mobile 100svh, min 560px).
- Background `<video>` muted, loop, autoplay, playsinline, grayscale + brightness .64, scale 1.04. Poster image.
- Gradient overlay, then bottom-left copy: eyebrow, H1 with a line break, bottom-right: "View the cars" pill (hover → accent, lifts 3px, letter-spacing widens) and a round sound toggle.
- Entrance animation `heroIn` (rise + blur), staggered 0 / .2s / .38s.
- Fix: the sound button shows "ON" while muted, which reads backwards. Use "Sound on" / "Sound off" text or an icon with `aria-pressed`.
- ACF: `hero_eyebrow`, `hero_title` (textarea, `<br>` from new lines), `hero_video` (file), `hero_poster` (image), `hero_cta_label`, `hero_cta_link`.

### 3. Marquee
- Brand names in Bodoni 26px uppercase, opacity .5, separated by "—", scrolling left forever (`drift` 34s, `translateX(-50%)`), faded edges with `mask-image`.
- The -50% trick needs the list printed twice. Do that in PHP, and mark the second copy `aria-hidden="true"`.
- ACF: `marquee_brands` repeater (text).

### 4. Featured Cars
- 4 tiles in a flex row, 620px tall (max 72vh, min 460px), radius `--r`. Hover grows the tile (`flex-grow: 2.6`) and zooms the image (`scale(1.055)`, 1.5s).
- Overlay text bottom-left: marque (accent), model (Bodoni), meta row: year / spec / note.
- Mobile: full width, height 78vw, min 400px.
- Content: ACF repeater `featured_cars` (max 4 recommended, layout copes with 2-6): `image`, `marque`, `model_name`, `ref_no` (the "No. 04"), `spec`, `note`. Section heading + intro as ACF text. No CPT, no car pages.
- Tiles are showcase only. Remove `cursor: pointer`, or add an optional `link` field per tile (for example to `#enquire` or an external listing). Default: no link.

### 5. Our Values
- 3-column auto-fit grid (min 320px). Outlined cards, radius `--r`, min-height 340px.
- Top row: index label + arrow, divided by a line. Then Bodoni title and body.
- Hover inverts to paper background, ink text, lifts 8px, big shadow.
- Each card links to `#enquire`.
- ACF: `values` repeater (`index`, `title`, `body`).

### 6. About
- Two-column auto-fit grid (min 340px). Left: 4:5 image, slides in from the left (`riseL`). Right: eyebrow, H2, paragraph, 3 stats in a row (2 columns on mobile).
- Scroll effect: as the section enters, background blends ink → paper via `--fade` (0-1) and text flips at 50% via `--tfade`. Uses `color-mix(in oklab, ...)`. JS updates the custom properties on scroll with `requestAnimationFrame`.
- ACF: `about_image`, `about_eyebrow`, `about_title`, `about_text` (wysiwyg basic), `about_stats` repeater (`value`, `label`).

### 7. Latest Cars
- Horizontal slider, slides `min(68vw, 980px)` wide (84vw mobile), 16:9 images, 18px gap. Caption left, place right.
- Controls: counter "01 / 06", round prev/next buttons (wrap around), and pointer drag with snap. Transform-based track, 1.05s ease.
- Add: keyboard arrows, `aria-live` on the counter, touch works through pointer events already.
- Content: ACF repeater `latest_cars`: `image`, `caption`, `place` (default place "Paphos"). The export's Greek place names (Kifisias, Athens, Sounio) are wrong. Demo content uses Paphos.

### 8. Pan Motors Live
- Eyebrow "Social", H2, "Follow the floor" pill linking to Instagram.
- 6 tiles in an auto-fit grid (min 300px, max-width 1120px), 4:5, mix of vertical videos and photos. Videos play only while in view (IntersectionObserver at .35), pause when out.
- Each tile shows ♥ likes, ✎ comments, caption, and links to an Instagram URL.
- Content: manual ACF repeater `live_posts`, no Instagram API. Fields: `type` (video / photo), `video` (file, shown when type is video), `image` (photo, or poster for a video), `url`, `caption`, `likes` (text, optional), `comments` (text, optional). When likes or comments are empty, hide that item. Instagram profile URL comes from the options page.

### 9. Showroom
- Light section (paper background) with rounded top corners, blurred and desaturated copy of the current photo behind a radial wash.
- Centre: 16:10 image with big shadow, caption + counter under it. Prev/next arrows either side on desktop, under the image on mobile. Image crossfades (.75s).
- The blurred background swaps to the current photo too.
- Below: opening hours rows (day / time in Bodoni / note), each row inverts to ink on hover and indents.
- ACF: `showroom_eyebrow`, `showroom_title`, `showroom_intro`, `showroom_photos` gallery (caption from the attachment caption). Hours come from the options page so the footer and schema can reuse them.
- Improvement: print all photos in the HTML and toggle a class, instead of swapping `src` like the export does. Lets the browser preload and makes the crossfade real.

### 10. Enquire
- Dark card inside a paper section. Left: H2 "Come And See", intro, address, phones, email, site, logo. Right: form with name, email, phone, message, underline-only inputs, accent submit.
- The form is handled by a WordPress form plugin the client installs. The theme only styles it.
- ACF option `enquire_form_shortcode` (text). The template runs it through `do_shortcode()` inside `<div class="pm-form">`.
- `main.css` styles `.pm-form` generically so any plugin's markup matches the design: labels (10px, `.26em`, uppercase, opacity .5), text/email/tel/textarea inputs (transparent, bottom border only, 16px Archivo, accent border on focus), submit button (accent pill, hover inverts to paper), plus validation messages, success message and spinner in the same style. Cover Contact Form 7, Fluent Forms and WPForms class names.
- When no shortcode is set, render the static form from the design (name, email, phone, message) so the layout can be reviewed. It does not submit.
- Make phones `tel:` links and the email a `mailto:` link.

### 11. Footer
- Paper background, top border, logo inverted (`filter: invert(1)`), 3 links, copyright.
- Copyright year is hardcoded "2025". Use `date('Y')`.

## 5. Shared behaviour → JS modules

All vanilla, loaded with `defer` (or as ES modules via `wp_enqueue_script_module`, WP 6.5+). Each module finds its elements by `data-*` attribute and exits quietly if they are missing.

| Module | Job |
|---|---|
| `reveal.js` | IntersectionObserver (threshold .15) adds `.in` to `[data-rise]` / `[data-rise-l]`, stagger `(i % 3) * 90ms` |
| `menu.js` | Burger toggle, aria, Escape, scroll lock |
| `hero-video.js` | Autoplay safety (`play().catch`), sound toggle |
| `heritage-fade.js` | Scroll → `--fade` / `--tfade` |
| `slider-drag.js` | Latest Cars track: buttons, drag, snap, counter, resize |
| `showroom-slider.js` | Showroom photos + blurred bg + counter |
| `live-videos.js` | Play/pause in view, lazy `src` |

Respect `prefers-reduced-motion`: the export already cuts all durations. Also skip the marquee animation and autoplay under that setting.

## 6. Site-wide settings (ACF options page "Pan Motors")

- Company name, address, map link
- Phones (repeater), email
- Instagram URL (and other socials)
- Opening hours repeater (`day`, `time`, `note`)
- Enquiry form shortcode
- Footer copyright text

## 7. Media

Expected in `_design/uploads/` (copy the whole export folder in, including the images you left out of the upload):

- Logo: `9JanArtboard-1-copy-20@1920x-Photoroom-c10d54bf.png` (also the favicon, make a proper site icon)
- Hero video: `0_Car_Automotive_1280x720.mp4`, poster `red-sports-car-...-background.jpg`
- Vertical videos: `0_Automotive_Cinematic_720x1280.mp4`, `0_Car_Road_720x1280.mp4`, `0_Electric_Car_Luxury_Car_720x1280.mp4`
- Showroom: `DSC04357-copy-scaled.jpg`, `IMG_6844-scaled.jpg`, `DSC08440-copy-Large.jpg`, `DSC08476-copy-Large.jpg`
- Stock/generated cars (placeholders, the client replaces them later): `black-porsche-911-...-generative-ai.jpg`, `sunset-supercar.jpg`, `black-sports-car-with-number-37-back.jpg`, `sleek-black-sports-car-dramatic-lighting.jpg`, `close-up-mclaren-720s-...jpg`

These go into the WP media library, not the theme folder. Only the logo fallback and fonts live in the theme. Output all images with `wp_get_attachment_image()` so `srcset`, `sizes` and lazy loading come for free. Hero image and poster get `fetchpriority="high"` and no lazy loading.

## 8. Decisions (settled 24 Sep 2026)

- **D1. Car pages.** None. Boutique brand site, not a listing site. No CPT, no single or archive templates.
- **D2. Latest Cars source.** Manual ACF repeater.
- **D3. Instagram section.** Manual ACF repeater, no integration. Likes and comments optional.
- **D4. Contact form.** Client installs a form plugin. Theme provides the visuals only (see section 10).
- **D5. Content.** Place names are Paphos. Photos are placeholders the client swaps later. Copy leans into the premium boutique positioning, never dealership language ("stock", "inventory", "finance", "trade-in", prices).
- **D6. Language.** Not confirmed. Build English only, with every theme string wrapped in translation functions so Greek can be added later with WPML or Polylang.
