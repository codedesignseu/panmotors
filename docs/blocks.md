# Content architecture: Gutenberg + ACF Blocks (D11)

Decision 25 Sep 2026. The client edits every page in the block editor (Gutenberg) and sees the real design while editing. This supersedes the content model in `docs/pages.md` section 2 and 3 (content stored on other pages, per-page field groups, `panmotors_page()` lookups for content). Sitemap, menus and SEO rules in `pages.md` still apply.

## 1. Why

- The previous model made the homepage read its sections from other pages. When those pages were deleted, the homepage lost five sections. A page must never depend on another page's existence.
- Classic ACF screens show forms, not the page. The client needs to see what they are editing, add or remove sections, and reorder them.

## 2. What stays

Everything on the front end: tokens, `main.css`, fonts, header, footer, mobile menu, all JS modules, image sizes, the section markup and classes, reveal, accessibility and reduced-motion behaviour, the dev comparison tools. The homepage must look identical before and after the migration.

Also stays: the Options page (Business, Contact, Opening hours, Marques, Social, Footer, Page not found, Technical), menus, the Editor role setup, `dev/acf-fields.py` as the single source of field definitions, the seed.

## 3. The model

### Pages are built from blocks
Every page, including Home, is a normal page whose content is a list of blocks. `front-page.php` and `page.php` just output the header, `the_content()` and the footer. The `templates/` page templates and the per-page field groups are removed.

### Block library (namespace `pm/`)
Each section becomes an ACF Block registered with `block.json` (ACF block API v3 or current), rendered by its existing PHP template adapted to read `get_field()` from the block.

| Block | Content lives in | Notes |
|---|---|---|
| `pm/hero` | block fields (eyebrow, title, video, poster, CTA label and link) | homepage only |
| `pm/marquee` | Options → Marques | no fields, separator from Options |
| `pm/featured-cars` | Cars (see below) | fields: heading, intro, which cars (all featured / pick), limit |
| `pm/values` | block fields (repeater) | used as a synced pattern on Home and About |
| `pm/about` | block fields (image, eyebrow, heading, stats) + Options description | option: "Scroll colour fade" on/off |
| `pm/latest-cars` | Cars | fields: eyebrow, heading, number of cars |
| `pm/live` | block fields (posts repeater) + Options Instagram URL | |
| `pm/showroom` | block fields (photos gallery, eyebrow, heading, intro) + Options hours | option: show hours |
| `pm/enquire` | block fields (heading, intro) + Options contact + form shortcode | |
| `pm/faq` | block fields (questions repeater) | outputs FAQPage schema when present |
| `pm/page-hero` | block fields | inner pages, design to be agreed |
| `pm/cta-band` | block fields | inner pages, design to be agreed |

Inner pages may also use a small set of core blocks for plain text: heading, paragraph, list, image, quote, buttons. Everything else from core is hidden.

### Cars: one place, used everywhere
A non-public post type `pm_car` ("Cars" in wp-admin). It is a data store only: `public => false`, `show_ui => true`, no single pages, no archive, not in sitemaps, no URLs. D1 (no car pages) stands.

Fields: image (featured image), marque, model name, reference no., spec, note, slider caption, place (default Paphos), "Featured" toggle, order.

- `pm/featured-cars` shows cars marked Featured (or a manual pick), in the chosen order.
- `pm/latest-cars` shows the newest cars.
- The client adds a car once and it appears wherever it should.

### Sections repeated on several pages
Use WordPress synced patterns. The seed creates a synced pattern "Our Values" containing `pm/values` and inserts it on Home and later on About. Editing it once updates both pages.

## 4. Editor experience

- Blocks render in the editor with the real front-end markup and CSS (ACF preview mode). Fields sit in the block sidebar with the help texts already written.
- Load the front-end CSS and fonts in the editor. In the editor, reveal animations are off (content visible), videos show their poster and don't autoplay, sliders are static or clickable.
- `theme.json` (classic theme, settings only): no custom colours, gradients, font sizes, spacing or typography controls, no layout controls, no font library, no Openverse, no block directory. The design stays intact whatever the client does.
- Allowed blocks: all `pm/*`, the core text blocks listed above, and synced patterns.
- Block patterns for quick starts: "Homepage (full design)" and one pattern per inner page once designed.
- Lock the hero block on Home against removal and moving. Other blocks stay free (client can hide a section by removing it, or reorder).
- Every block has a clear title, icon, one-line description and an example preview in the inserter.
- Editor role: unchanged from the editability session (edits pages, cars, patterns, options except Technical, menus, media, logo).

## 5. Scripts

Each block's JS module loads only when that block renders (keep `panmotors_use_module()` or use `viewScriptModule` in `block.json`).

## 6. SEO and GEO

No change to the rules in `theme-map.md` §9. Blocks render server-side, so the HTML is the same. The homepage H1 lives in `pm/hero`. Inner pages get their H1 from `pm/page-hero` (or the page title when there is none). FAQ schema comes from `pm/faq`.

## 7. Safety rules

- No page reads content from another page. Shared content lives in Options, the Cars post type or synced patterns.
- Deleting a page never breaks another page.
- The seed never deletes field groups, patterns or pages it didn't create, and never runs without `acf-json/` committed first.
- Seed content is stored as real block markup, so a fresh install matches this site.
