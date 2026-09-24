# Page architecture (D9)

Decision 24 Sep 2026: Pan Motors is a multi-page site. Each primary menu item is its own page. This file supersedes every part of `theme-map.md` and `CLAUDE.md` that assumes a single page with anchor links (`#floor`, `#ways`, etc.). Still no car pages, no car post type, no prices (D1 stands).

## 1. Sitemap

| Page | Slug | Page template | Menu |
|---|---|---|---|
| Home | `/` | `front-page.php` | logo |
| Featured Cars | `/featured-cars/` | `templates/page-featured-cars.php` | primary |
| Our Values | `/our-values/` | `templates/page-values.php` | primary |
| About Pan Motors | `/about/` | `templates/page-about.php` | primary |
| Latest Cars | `/latest-cars/` | `templates/page-latest-cars.php` | primary |
| Live | `/live/` | `templates/page-live.php` | primary |
| Showroom | `/showroom/` | `templates/page-showroom.php` | primary |
| Contact | `/contact/` | `templates/page-contact.php` | contact pill + mobile menu |
| Privacy Policy, Cookie Policy | `/privacy-policy/`, `/cookie-policy/` | `page.php` | footer |

Menus:
- Primary: Featured Cars, Our Values, About Pan Motors, Latest Cars, Live, Showroom. Links point to the pages, never to anchors.
- Contact pill in the nav and the last item of the mobile menu: the Contact page.
- Footer: Featured Cars, Showroom, Contact, Privacy Policy, Cookie Policy.
- The current page gets `aria-current="page"` and a visible active state (underline shown, full opacity).

The theme never finds these pages by slug. A "Site pages" tab on the options page holds one Page Link / Post Object field per section page (`page_featured`, `page_values`, `page_about`, `page_latest`, `page_live`, `page_showroom`, `page_contact`). Helper: `panmotors_page( 'featured' )` returns the ID, `panmotors_page_url( 'featured' )` the permalink. The client can rename or move pages without breaking links.

## 2. One source for every piece of content

Each section's content lives on its own page and is edited there. The homepage reads it from that page. Nothing is entered twice.

| Content | Lives on | Home shows |
|---|---|---|
| Featured cars repeater | Featured Cars page | all tiles (up to 4 on home, first 4) |
| Values repeater | Our Values page | the 3 cards, each linking to Our Values |
| About image, story, stats | About page | image, first paragraph (the options description), stats |
| Latest cars repeater | Latest Cars page | the slider |
| Live posts repeater | Live page | first 6 posts |
| Showroom photos | Showroom page | slider + hours |
| FAQs | Contact page | not on home |
| Business facts, hours, marques, form shortcode | Options page (unchanged) | used everywhere |

Home page field group keeps only: the hero fields, and per section an optional override for the home heading and the link label ("View all cars", "Our story", etc., with sensible defaults).

## 3. Components

Every section becomes a reusable component in `template-parts/sections/`, called with arguments:

```php
get_template_part( 'template-parts/sections/featured-cars', null, [
  'page_id' => panmotors_page( 'featured' ),
  'context' => 'home',   // 'home' | 'page'
  'limit'   => 4,        // optional
] );
```

- `context = home`: section as in the design, with an H2 and a "view more" link to its page (styled like the design's outlined pill, e.g. "Follow the floor").
- `context = page`: full version on the section's own page. The page hero carries the H1, so the section title is omitted or becomes an H2 only if there is a sub-heading.
- Components: `featured-cars`, `values`, `about`, `latest-cars`, `live`, `showroom`, `hours`, `faq`, `enquire`, `cta-band`.
- The existing hero and marquee stay home-only in `template-parts/front/`.

## 4. Inner page layout

The design only covers the homepage. Inner pages are built from the same system. No new colours, fonts or effects.

Every inner page:
1. **Page hero** (`template-parts/components/page-hero.php`): dark ink background, optional background image with the home hero's grayscale filter and gradient, breadcrumb (small uppercase, `.18em` spacing, 0.6 opacity), accent eyebrow, H1 in Bodoni `clamp(48px, 7vw, 128px)` line-height .9, optional intro paragraph (max 46ch). Height `min(72vh, 720px)`, min 460px, content bottom-left like the home hero, `heroIn` entrance. Fields per page: `page_hero_image`, `page_eyebrow`, `page_intro`. The H1 is the page title.
2. **Intro text** (optional wysiwyg `page_body`, prose styles from `page.php`, max 68ch). This is where the SEO copy lives.
3. **The section component** in `page` context.
4. **CTA band** (`cta-band`): "Come and see" style dark card linking to Contact. Not on the Contact page itself.

Per page:
- **Featured Cars**: page hero, intro, all tiles. Up to 12, wrapping in rows of up to 4 on desktop with the same hover-widen effect per row. CTA band ("Arrange a private viewing").
- **Our Values**: page hero, intro, values shown larger. Each value gets an optional `detail` (wysiwyg) and `image`, shown only on this page, in alternating image/text rows. Home keeps the short cards.
- **About**: page hero, the one-sentence description as the lead paragraph, `about_story` (wysiwyg, the long version), image, stats, marques list (from options, as a plain list, not the marquee), CTA band. The scroll colour fade stays on the home About section only.
- **Latest Cars**: page hero, intro, all entries as a 2-column grid of figures with captions (1 column on mobile). The slider stays on home.
- **Live**: page hero, intro, all posts in the design's grid, Instagram pill.
- **Showroom**: page hero, intro, photo slider, hours, address with map link, and a "Getting here" wysiwyg (directions from Paphos centre and Paphos airport, parking). High GEO value.
- **Contact**: page hero, enquire card (details + form), hours, map link, FAQ section (`details`/`summary`). No CTA band.

## 5. SEO and GEO per page

- Every page: one H1 (its title in the page hero), unique title and meta description (SEO plugin), canonical, breadcrumb.
- JSON-LD `@graph` on every page: the `AutoDealer` node (`#business`), `WebSite`, a `WebPage` for the current page with `isPartOf` `#website` and `about` `#business`, and `BreadcrumbList` on inner pages.
- `FAQPage` only on Contact.
- Homepage outline: H1 in the hero, H2 per section preview, H3 for cards. Each preview links to its page with descriptive link text ("View all featured cars"), not "Read more".
- Inner pages have no duplicate content from home beyond short previews. Each page needs its own text (intro, story, directions).
- Add the pages to `llms.txt` with one line each.

## 6. Changes to work already done

- **Content model (section 2)**: split the Home field group into per-page field groups located by page template. Add the "Site pages" tab to options. Move the demo content to the pages. Update `acf-json/` and `dev/seed.php` (create the pages, assign templates, fill fields, map them in options).
- **Menus (section 3)**: seed menus with page links, contact pill to the Contact page, `aria-current` styling.
- **Hero and marquee**: unchanged.
- **front-page.php**: calls the section components with `context = home`.
- **theme-map.md**: add a note at the top pointing here, mark 3, 4, 9.1, 9.4 and 9.8 as superseded where they talk about anchors or the single page. Set D7 to "settled by D9". Add D9.
- **CLAUDE.md**: update the structure (templates/, template-parts/sections/), and the rule "each section's content lives on its own page".
- **TASKS.md**: replace section 4 with "Section components (home + page context)" and section 5's page item with "Inner pages" as listed in 4 above.
