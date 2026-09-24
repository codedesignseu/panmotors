# Pan Motors WordPress theme

Custom classic WordPress theme for Pan Motors (panmotors.com), a premium car boutique in Paphos, Cyprus. Built by Code Designs.

This is a luxury brand presence site, not a car listing site. No inventory, no car post type, no car pages, no prices. All cars are showcase content in ACF repeaters. Never use dealership language in demo copy.

## Read first
- `docs/theme-map.md` has the section-by-section map, tokens, ACF fields and settled decisions. Follow it.
- `_design/` is the Claude Design export and the visual reference. Never edit it. Never enqueue anything from it.
- `TASKS.md` is the build checklist. Work one task at a time, tick it off, then stop for review.

## Stack
- Classic PHP theme, no page builder, no block theme. Local site: LocalWP `panmotors.local`.
- ACF Pro for all client-editable content. Field groups saved as local JSON in `acf-json/` and committed.
- Vanilla CSS with custom properties. No Sass, no Tailwind, no build step.
- Vanilla ES6+ JS. No jQuery, no React, no slider libraries.
- Self-hosted fonts in `assets/fonts/` (Bodoni Moda, Archivo). No Google Fonts requests.

## Converting the export
- `_design/index.html` is a Claude Design runtime template, not static HTML. `sc-for`, `sc-if`, `{{ }}`, `style-hover`, `onClick`, `ref` and `<image-slot>` all need rewriting. See section 1 of the theme map.
- Move every inline style into `assets/css/main.css` using BEM-style classes (`.pm-hero__title`). Inline styles are allowed only for values that come from PHP (for example a CSS custom property).
- `style-hover` becomes a `:hover` rule. Also add `:focus-visible` wherever there is a hover state.
- Match the design closely: spacing, clamp() sizes, easing `cubic-bezier(.16,.84,.3,1)`, durations, radius `--r: 24px`.

## Structure
```
panmotors/
  style.css  functions.php  front-page.php  header.php  footer.php
  index.php  page.php  404.php
  inc/        setup.php enqueue.php acf.php helpers.php
  template-parts/front/   one file per homepage section
  template-parts/components/  car-tile.php etc.
  assets/css/main.css   assets/js/*.js   assets/fonts/   assets/img/
  acf-json/
  docs/  _design/
```
`functions.php` only requires files from `inc/`.

## Rules
- Escape all output: `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` for rich text.
- Every ACF read handles empty values. A missing field hides its element, it never prints an empty tag or a PHP notice.
- Images via `wp_get_attachment_image()` with proper `sizes`. Register image sizes in `inc/setup.php`. Hero and poster load eagerly with `fetchpriority="high"`, everything else lazy.
- Enqueue assets with `filemtime()` versioning. JS with `defer` strategy or as script modules.
- Each JS module queries its own `data-*` hooks and returns early if absent.
- Respect `prefers-reduced-motion`: no marquee, no autoplay, no scroll effects.
- Accessibility: real buttons for controls, `aria-expanded` on the burger, `aria-label` on icon buttons, `aria-live` on slider counters, alt text from the media library.
- Text domain `panmotors`. Wrap theme strings in `__()` / `esc_html__()`.
- No `noindex`, no `robots.txt` from the export.
- The contact form comes from a plugin shortcode stored in ACF options. The theme styles it through the `.pm-form` wrapper and never handles submissions itself.
- Instagram posts are entered manually in ACF. No API calls.
- Prefix all PHP functions, handles and option names with `panmotors_` / `pm-`.

## Workflow
- After each task: load the page in the browser at 1440px, 1080px, 880px and 390px widths and compare against `_design/index.html`.
- Check the PHP error log is clean (`WP_DEBUG` on locally).
- Commit after each finished task with a clear message.
- Stop and ask when a decision in theme-map.md section 8 is still open and the task depends on it.
