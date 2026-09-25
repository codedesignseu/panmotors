# Editability

Rule (CLAUDE.md): the client edits everything in wp-admin. No visible text, link, image or video is hardcoded. Content comes from an ACF field, a WordPress menu, the Customizer logo, or Pan Motors settings. Theme strings left in PHP are screen-reader text, aria-labels and admin notices, all translation-ready. An empty field hides its element.

Audit 25 Sep 2026: homepage, header, mobile menu, footer and 404. "Before" is the source before this pass; "Now" is where the client edits it.

## Where things are edited

| Screen | What |
|---|---|
| **Pages → Home** | Tabs in page order: Top video, Marques strip, Featured Cars, Our Values, About Pan Motors, Latest Cars, Pan Motors Live, The Showroom, Come And See. Each section (except the top video) has a **Show this section** switch. |
| **Pages → Featured Cars / About Pan Motors / Latest Cars / The Showroom / Contact** | The content each homepage section shows (single source, D9). The Home tabs link to these screens. |
| **Pan Motors (settings)** | Business, Contact, Opening hours, Marques, Social, Footer, Page not found. **Technical** (page mapping, form shortcode) is visible to administrators only. |
| **Appearance → Menus** | Primary menu (header + mobile menu), Footer menu. |
| **Appearance → Customise → Site Identity** | Logo (header, contact card, footer). |
| **Media** | Images and videos; photo captions (Showroom slider) come from each image's Caption. |

## Header and mobile menu

| Element | Before | Now |
|---|---|---|
| Logo | Customizer | Customizer → Site Identity |
| Logo alt text / text fallback | Options: trading name | Settings → Business → Trading name |
| Menu links | Menu "Primary" | Appearance → Menus → Primary |
| Contact button (header) and last mobile-menu item: text | **hardcoded "Contact"** | Settings → Contact → Contact button text |
| Contact button: link | Contact page (mapping) | Settings → Technical → Contact page (admin) |
| "Skip to content", "Menu" | theme string | theme string (screen-reader / keyboard only) |

## Homepage

| Section | Element | Before | Now |
|---|---|---|---|
| Top video | Small red line, big title | Home fields | Home → Top video |
| | Background video, background photo | Home fields | Home → Top video (MP4 under 8 MB; photo ≥ 2400px wide) |
| | Button text | Home field | Home → Top video → Button text |
| | Button link | Home text field, fallback Featured Cars page | Home → Top video → Button goes to (page picker; empty = Featured Cars) |
| | ↓ arrow | decorative icon | decorative icon (aria-hidden) |
| Marques strip | Names | Settings → Marques | Settings → Marques (drag to reorder) |
| | Separator "—" | **hardcoded** | Settings → Marques → Separator |
| | Show/hide | — | Home → Marques strip → Show this section |
| Featured Cars | Heading | Home override, fallback page title | Home → Featured Cars → Heading |
| | Intro | Featured Cars page → Short intro | same |
| | Cars (photo, marque, model, reference, detail, note, optional link) | Featured Cars page | same (first four on the homepage) |
| Our Values | Heading | Home override, **hardcoded fallback "Our Values"** | Home → Our Values → Heading |
| | Cards (small label, title, short text) | About page → Our Values | same; each card links to the About page |
| | → arrow | decorative icon | decorative icon (aria-hidden) |
| About Pan Motors | Heading | Home override, fallback page title | Home → About Pan Motors → Heading |
| | Small red line | About page → Small red line | same |
| | Paragraph | Settings → One-sentence description | same |
| | Photo, three highlights | About page | same |
| Latest Cars | Heading | Home override, fallback page title | Home → Latest Cars → Heading |
| | Small red line | Latest Cars page → Small red line | same |
| | Cars (photo, caption, place) | Latest Cars page | same |
| | Counter "01 / 06", ← → | computed / decorative | computed / decorative (buttons have translated aria-labels) |
| Pan Motors Live | Small red line, heading | Home fields, **hardcoded heading fallback** | Home → Pan Motors Live |
| | Instagram button text | Home field | Home → Pan Motors Live → Instagram button text |
| | Instagram link | Settings → Instagram | Settings → Social → Instagram |
| | Posts (video/photo, link, caption, likes, comments) | Home repeater | Home → Pan Motors Live → Posts |
| | ♥ ✎ | decorative icons | decorative icons (aria-hidden; "likes"/"comments" screen-reader text) |
| The Showroom | Heading | Home override, fallback page title | Home → The Showroom → Heading |
| | Small red line, intro, photos | Showroom page | same; captions from Media → Caption |
| | Opening hours rows | Settings → Opening hours | same |
| Come And See | Heading | Contact page field, **hardcoded fallback** | Contact page → Contact card → Heading |
| | Intro | Contact page field | same |
| | Address, phones, email | Settings | Settings → Business / Contact |
| | Website | site address | site address (automatic) |
| | Logo | Customizer | Customizer |
| | Form | Settings → form shortcode | Settings → Technical → Enquiry form shortcode (admin) |
| | Preview form labels, hints, button (until the plugin is connected) | **hardcoded** | Home → Come And See → Preview form |
| | "Form plugin shortcode not set" | admin notice | admin notice (admins only, translation-ready) |

## Footer

| Element | Before | Now |
|---|---|---|
| Small logo | Customizer | Customizer |
| Footer line | Settings → Footer tagline | Settings → Footer → Footer line |
| Menu links | Menu "Footer" | Appearance → Menus → Footer |
| Copyright | **hardcoded "©" + year** + holder field | Settings → Footer → Copyright, e.g. "© {year} Pan Motors" ({year} is automatic) |

## 404 page

| Element | Before | Now |
|---|---|---|
| "404", small red line, heading, text | **hardcoded** | Settings → Page not found |
| "Back to home" and "Contact us" buttons | **hardcoded** | Settings → Page not found |
| Contact button link | Contact page (mapping) | Settings → Technical → Contact page (admin) |

## Inner pages (placeholders until designed)

| Element | Now | To do |
|---|---|---|
| Breadcrumb "Home" | the Home page's title (was **hardcoded**) | — |
| Page title, small red line, intro, top image, opening text | Page → title / Page top | — |
| CTA band heading, text, button | **hardcoded defaults in the templates** | move to fields when the inner pages are designed |
| "… section: component coming next" | temporary placeholder | removed as each page is built |

## Editor experience

- Labels and instructions are written for the client: what the field is, where it shows, how long.
- Image and video fields state size and format (e.g. "Landscape, at least 2400px wide, JPG or WebP, sRGB colour"); minimum sizes protect the layout (e.g. Latest Cars photos ≥ 1960 × 1102px).
- Text that breaks the layout has a character limit (e.g. car model 28, hero title 40, footer line 40).
- Repeaters have a min/max, "Add car" / "Add post" style buttons, collapsed rows showing the title, and drag-to-reorder.
- Messages link straight to where content is edited ("… edited on the About Pan Motors page").

## Client role (Editor)

- Can: edit all pages (Home and the section pages), Pan Motors settings (not Technical), Appearance → Menus, Customise → Site Identity (logo) and Menus, Media.
- Cannot: theme files, themes, plugins, users, WordPress settings, ACF field groups, Additional CSS, the homepage setting.
- Hidden: Posts, Comments, Themes, Patterns, Fonts (menus, toolbar and direct URLs).
- `inc/admin.php` adds `edit_theme_options` to the Editor role while the theme is active and removes it on theme switch.

## How this was tested (25 Sep 2026)

- `dev/tests/editor-caps.php`: 12 capability checks as the Editor, all pass.
- `dev/tests/editor-admin.mjs`: wp-admin tour as the Editor (menus, blocked screens, tab order, Technical hidden).
- `dev/tests/editor-fields.mjs`: all 161 text/URL/email fields on Home, the five section pages and Pan Motors settings saved through the real forms with a marker; 136 appear on the homepage or 404, the other 25 belong to inner pages or Google data (FAQs, inner-page intros, phone labels, Maps/Facebook/Google Business links). All 8 section switches hide their section and its script. Everything restored; visible text and links identical to before.
- `dev/tests/media-fields.php`: every homepage image, video and the logo swapped for a test file and shown, then restored.
