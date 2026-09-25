# Editability

Rule (CLAUDE.md): the client edits everything in wp-admin. No visible text, link, image or video is hardcoded. Content comes from a block's fields, the Cars list, a synced pattern, a WordPress menu, the Customizer logo, or Pan Motors settings. Theme strings left in PHP are screen-reader text, aria-labels and admin notices, all translation-ready. An empty field hides its element.

Content architecture: Gutenberg + ACF Blocks (D11, [`blocks.md`](blocks.md)). Updated 26 Sep 2026 after the migration ([`migration-blocks.md`](migration-blocks.md)); the audit of 25 Sep (field-group model) is in git history.

## Where things are edited

| Screen | What |
|---|---|
| **Pages → Home** (block editor) | The homepage sections as blocks, in page order: Top video, Marques strip, Featured Cars, Our Values (synced pattern), About Pan Motors, Latest Cars, Pan Motors Live, The Showroom, Come And See. Each block shows the real section; its fields are in the sidebar (Block tab). |
| **Pages → Featured Cars / About Pan Motors / Latest Cars / The Showroom / Contact** | Inner pages, built from blocks: Page top, text, the section blocks, Call to action. Placeholders until the inner pages are designed (D10). |
| **Cars** | Every showcase car once: photo, marque, model, reference, detail, note, optional link, slider caption, place, Featured, order. Featured Cars and Latest Cars read from here. |
| **Patterns → Our Values** (or "Edit original" on the block) | The Our Values cards, shared by Home and About. Edit once, both pages change. |
| **Pan Motors (settings)** | Business, Contact, Opening hours, Marques, Social, Footer, Page not found. **Technical** (Contact button page, form shortcode) is visible to administrators only. |
| **Appearance → Menus** | Primary menu (header + mobile menu), Footer menu. |
| **Appearance → Customise → Site Identity** | Logo (header, contact card, footer). |
| **Media** | Images and videos; photo captions (Showroom slider) come from each image's Caption. |

## Working with blocks

| Task | How |
|---|---|
| Change a text, photo or video | Click the section, then edit its fields in the sidebar. The preview updates in place. |
| Hide a section without losing it | Block toolbar → ⋮ → **Hide** (WordPress 6.9+). Hidden blocks stay in the editor and print nothing on the site (their script is not loaded either). Show them again the same way. |
| Reorder sections | Drag the block, use the ↑ ↓ arrows in its toolbar, or the List view. The Top video on Home is locked: it cannot be moved or removed. |
| Remove a section | Block toolbar → ⋮ → Delete. It can be added back from the inserter (+), "Pan Motors" category. |
| Add plain text on an inner page | Heading, Paragraph, List, Image, Quote and Buttons are available. Nothing else from core (no columns, colours, spacing or fonts: `theme.json` switches those off). |
| Start a page from the design | Inserter → Patterns → Pan Motors → "Homepage (full design)": all homepage blocks in order, empty. |
| Undo a change | Undo in the toolbar, or Page → Revisions (each save is a revision). |

## Header and mobile menu

| Element | Where |
|---|---|
| Logo | Customizer → Site Identity |
| Logo alt text / text fallback | Settings → Business → Trading name |
| Menu links | Appearance → Menus → Primary |
| Contact button (header) and last mobile-menu item: text | Settings → Contact → Contact button text |
| Contact button: link | Settings → Technical → Contact button goes to (admin). Empty or unpublished page: the button hides. |
| "Skip to content", "Menu" | theme string (screen-reader / keyboard only) |

## Homepage (blocks on Pages → Home)

| Block | Element | Where |
|---|---|---|
| Top video | Small red line, big title (new line = line break) | block fields |
| | Background video, background photo | block fields (MP4 under 8 MB; photo ≥ 1920px wide, 2400 recommended) |
| | Button text, Button goes to | block fields (page picker, required) |
| | ↓ arrow | decorative icon (aria-hidden) |
| Marques strip | Names, separator | Settings → Marques (the block has no fields) |
| Featured Cars | Heading, short intro | block fields |
| | Which cars | block field: Cars marked Featured (in their order) or Pick cars (drag to order); How many (4 fill a row) |
| | Cars (photo, marque, model, reference, detail, note, optional link) | Cars |
| Our Values | Heading, Cards go to (page), values (small label, title, short text) | Our Values synced pattern |
| | → arrow | decorative icon (aria-hidden) |
| About Pan Motors | Photo, small red line, heading, three highlights, scroll colour fade on/off | block fields |
| | Paragraph | Settings → Business → One-sentence description |
| Latest Cars | Small red line, heading, how many | block fields |
| | Cars (photo, slider caption, place), newest first by date | Cars |
| | Counter "01 / 06", ← → | computed / decorative (buttons have translated aria-labels) |
| Pan Motors Live | Small red line, heading, Instagram button text, posts (video/photo, link, caption, likes, comments) | block fields |
| | Instagram link | Settings → Social → Instagram |
| | ♥ ✎ | decorative icons (aria-hidden; "likes"/"comments" screen-reader text) |
| The Showroom | Small red line, heading, intro, photos, show opening hours | block fields; captions from Media → Caption |
| | Opening hours rows | Settings → Opening hours |
| Come And See | Heading, intro, preview form labels, hints and button (until the plugin is connected) | block fields |
| | Address, phones, email | Settings → Business / Contact |
| | Website, logo | site address (automatic), Customizer |
| | Form | Settings → Technical → Enquiry form shortcode (admin) |
| | "Form plugin shortcode not set" | admin notice (administrators only, translation-ready) |

## Inner pages (placeholders until designed)

| Element | Where |
|---|---|
| Breadcrumb "Home" | the Home page's title |
| Page title (H1) | the page title |
| Small red line, intro, top image | Page top block (the intro is also the page's meta description without an SEO plugin) |
| Opening text, "Getting here" | core Heading / Paragraph blocks |
| Sections | the same blocks as on Home |
| Call to action | Call to action block (heading, text, button text, button page) |
| Questions (Contact) | Questions block; answers are in the HTML (`details`/`summary`) |

## Footer and 404

Unchanged: Settings → Footer (footer line, copyright with `{year}`), Settings → Page not found (all texts and button labels). The 404 Contact button uses Settings → Technical → Contact button goes to.

## Editor experience

- Blocks preview with the front-end markup and CSS (`main.css` + `editor.css` in the editor canvas). In the editor: no entrance or scroll animations, videos show their photo, the marquee holds still, sliders show their first slide, the About section stays dark. An empty block shows a dashed placeholder saying what to fill. The page title sits as a small label above the blocks.
- Labels and instructions are written for the client: what the field is, where it shows, how long. Image and video fields state size and format; minimum sizes protect the layout (car photos ≥ 1960 × 1102px). Text that breaks the layout has a character limit.
- Text formats: bold, italic, link. Core block styles the design doesn't have (outline buttons, rounded images, plain quotes) are removed. No Openverse, block directory, remote patterns, or code editor for the client.
- Messages link to where shared content is edited ({settings} → Pan Motors settings, {cars} → Cars).

## Client role (Editor)

- Can: edit all pages in the block editor, Cars, synced patterns, Pan Motors settings (not Technical), Appearance → Menus, Customise → Site Identity (logo) and Menus, Media.
- Cannot: theme files, themes, plugins, users, WordPress settings, ACF field groups, Additional CSS, the homepage setting, the Site Editor ("Design").
- Hidden: Posts, Comments, Themes, Design, Fonts (menus, toolbar and direct URLs).
- `inc/admin.php` adds `edit_theme_options` to the Editor role while the theme is active and removes it on theme switch.

## How this was tested (26 Sep 2026)

With a test Editor created for the run and deleted afterwards with `--reassign=1`:
- `dev/tests/editor-caps.php`: 14 capability checks, all pass (pages, Cars, synced patterns, settings, menus, logo, media; no theme files, themes, plugins, users, options or field groups).
- `dev/tests/editor-admin.mjs`: wp-admin menus (Dashboard, Media, Pages, Cars, Pan Motors, Appearance, Profile, Tools), blocked screens, Technical tab hidden, Home opens in the block editor, Cars and Patterns reachable.
- `dev/tests/editor-blocks.mjs`: on Home, all nine blocks preview with the same text as the site; one field per block typed into the sidebar (the 7 blocks with fields), saved, shown on the site, restored; Featured Cars moved below Our Values and back; Latest Cars hidden (section and `slider-drag.js` gone) and shown again; the Our Values pattern edited once and shown on Home and About; a car added under Cars (Featured, order 0) appeared as tile 1 of Featured Cars and slide 1 of Latest Cars, then was deleted and disappeared. The hero reports `canRemove=false canMove=false`.
