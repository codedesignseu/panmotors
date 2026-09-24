# Theme audit against updated docs

Date: 24 Sep 2026. Scope: code built in TASKS.md section 1 (Foundation), checked against the updated `CLAUDE.md`, `TASKS.md` and `docs/theme-map.md` (new section 9, SEO and GEO).

No car post type, `single-car.php`, `archive-car.php` or car ACF group was ever created. A grep for dealership wording, Greek place names and `register_post_type` in theme code finds nothing.

## Keep

- `style.css`: theme header, text domain `panmotors`, boutique wording.
- `inc/setup.php`: theme supports (title-tag, post-thumbnails, custom-logo, html5, responsive-embeds), menus `primary` + `footer`, cropped image sizes for the design's fixed ratios (16:9, 16:10, 4:5), srcset cap raised to 2560.
- `inc/enqueue.php`: `filemtime()` versions, JS as script modules (deferred), `menu` and `reveal` global. Preloads Bodoni 400 and Archivo 400 latin woff2 (9.6).
- `assets/fonts/`: self-hosted variable woff2, latin + latin-ext, OFL licences. No Google Fonts requests.
- `assets/css/main.css`: tokens, reset, explicit heading styles at weight 400, utilities, reveal animations gated on `html.pm-js` so content stays visible without JS, reduced-motion rules.
- `header.php`: `language_attributes()` on `<html>`, skip link to `#main` as the first element in `<body>` (9.1).
- `index.php`, `page.php`, `404.php`: one H1 each, `<main id="main">`.

## Change

| File | Problem | Fix |
|---|---|---|
| `functions.php` | Deleted in the working tree (accident). Theme cannot load. | Restore from git and add `schema` to the `inc/` list. |
| `inc/enqueue.php` | Front-page modules load whenever the file exists, even if their section renders nothing. Breaks 9.6 "only load a section's JS when that section exists". | Add `panmotors_use_module( $name )`. Each template part calls it when it renders. Classic themes print script modules in the footer, so late enqueue works. `menu` and `reveal` stay global. |
| `404.php` | Eyebrow is a `<span>`. 9.1: eyebrow lines are `<p>`. | Use `<p class="pm-eyebrow">`. |
| `assets/css/main.css` | `.pm-eyebrow` only sets `margin-bottom`. As a `<p>` it relies on the global `p` reset, and inside the home H1 it will be a `<span>` that must look identical. | Make the class self-contained: explicit `margin: 0 0 22px`, font, weight and line-height, so it renders the same on `<p>` or a `<span>` inside the H1. |

## Missing (section 1 scope)

| File | What | Why |
|---|---|---|
| `assets/css/main.css` | Reset for `address` (no italic), `dl`/`dd` margins, `summary` (no marker, pointer), `details`, plus a `.pm-list-reset` utility. | 9.1 uses `address`, `dl`, `details`/`summary` and a `ul` for the marquee. |
| `inc/setup.php` | `pm-og` image size, 1200x630 cropped. | 9.7 OG and Twitter image. |
| `inc/schema.php` | File does not exist. | New in CLAUDE.md structure. Stub now so `functions.php` loads it. The JSON-LD graph itself is TASKS.md section 5. |

## Not in scope yet (later sections)

- Hero poster preload with `fetchpriority="high"` (needs the ACF hero field, section 5).
- `page.php` breadcrumb and `WebPage` schema (section 5).
- FAQ section, robots.txt, `llms.txt`.
- Content model changes: none needed now. Section 2 (options page, front page fields) has not been started.

## Open decision

- **D7 (extra pages)** is referenced in theme-map 9.8 but not settled in section 8. The section 5 `page.php` task depends on it.

## Verification status

No WordPress install is linked yet, so nothing has been checked in the browser. Checks so far: `php -l` on every PHP file.

## Applied

All Change and Missing items above were applied on 24 Sep 2026 in separate commits (schema stub, markup and semantics, CSS). No content model changes were needed.
