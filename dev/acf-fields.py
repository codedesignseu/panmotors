"""Generates the ACF field groups in acf-json/. Development only.

Usage: python3 dev/acf-fields.py   then run the seed (dev/seed.php) to sync them into the database.

acf-json/ is the source ACF loads from; this script is how it is written, so field labels,
instructions and limits stay consistent. Edit field groups here, not in wp-admin.

Editor rules (docs/editability.md):
- Every label and instruction is written for the client: what it is, where it shows, how long.
- Images and videos state the recommended size and format; minimums protect the layout.
- Text that breaks the layout when too long gets a character limit.
- Fields marked pm_admin_only are hidden from non-administrators (inc/acf.php).
- Fields marked pm_design (the Design tab, D12) are for administrators, and for Editors only when
  Technical → "Editors can change the design" is on (inc/design.php).
- Messages and instructions may use {settings} and {cars} tokens, which inc/acf.php turns into
  links to Pan Motors settings and to the Cars list.
- Blocks (D11, docs/blocks.md): one field group per pm/* block, located by block name. A field that
  is the same as before keeps its key, so labels, limits and saved values carry over.
"""
import json
import os
import time

OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'acf-json')
NOW = int(time.time())

IMG_FORMAT = 'JPG or WebP, sRGB colour.'


# ---------------------------------------------------------------- Field helpers
def base(key, label, name, ftype, **kw):
    f = {
        'key': 'field_pm_' + key,
        'label': label,
        'name': name,
        'aria-label': '',
        'type': ftype,
        'instructions': kw.pop('instructions', ''),
        'required': kw.pop('required', 0),
        'conditional_logic': kw.pop('conditional_logic', 0),
        'wrapper': {'width': kw.pop('width', ''), 'class': '', 'id': ''},
    }
    if kw.pop('admin_only', False):
        f['pm_admin_only'] = 1
    if kw.pop('design', False):
        f['pm_design'] = 1
    f.update(kw)
    return f


def tab(key, label, **kw):
    return base('tab_' + key, label, '', 'tab', placement='top', endpoint=0, selected=0, **kw)


def text(key, label, name=None, maxlength='', **kw):
    kw.setdefault('default_value', '')
    kw.setdefault('placeholder', '')
    kw.setdefault('prepend', '')
    kw.setdefault('append', '')
    return base(key, label, name or key, 'text', maxlength=maxlength, **kw)


def textarea(key, label, name=None, rows=3, new_lines='', maxlength='', **kw):
    kw.setdefault('default_value', '')
    kw.setdefault('placeholder', '')
    return base(key, label, name or key, 'textarea', rows=rows, new_lines=new_lines, maxlength=maxlength, **kw)


def url(key, label, name=None, **kw):
    kw.setdefault('default_value', '')
    kw.setdefault('placeholder', 'https://')
    return base(key, label, name or key, 'url', **kw)


def email(key, label, name=None, **kw):
    return base(key, label, name or key, 'email', default_value='', placeholder='', prepend='', append='', **kw)


def number(key, label, name=None, **kw):
    for k in ('default_value', 'min', 'max', 'step', 'placeholder', 'prepend', 'append'):
        kw.setdefault(k, '')
    return base(key, label, name or key, 'number', **kw)


def image(key, label, name=None, min_width='', min_height='', **kw):
    return base(key, label, name or key, 'image', return_format='id', library='all',
                min_width=min_width, min_height=min_height, min_size='', max_width='', max_height='',
                max_size=kw.pop('max_size', 8), mime_types=kw.pop('mime_types', 'jpg, jpeg, png, webp, avif'),
                preview_size='medium', **kw)


def video(key, label, name=None, **kw):
    return base(key, label, name or key, 'file', return_format='id', library='all',
                min_size='', max_size=kw.pop('max_size', 10), mime_types='mp4', **kw)


def gallery(key, label, name=None, min_width='', min_height='', **kw):
    return base(key, label, name or key, 'gallery', return_format='id', library='all',
                min=kw.pop('min', ''), max=kw.pop('max', ''), min_width=min_width, min_height=min_height, min_size='',
                max_width='', max_height='', max_size=8, mime_types='jpg, jpeg, png, webp, avif',
                insert='append', preview_size='medium', **kw)


def time_picker(key, label, name=None, **kw):
    return base(key, label, name or key, 'time_picker', display_format='H:i', return_format='H:i', **kw)


def checkbox(key, label, choices, name=None, **kw):
    return base(key, label, name or key, 'checkbox', choices=choices, default_value=[], return_format='value',
                allow_custom=0, layout='horizontal', toggle=0, save_custom=0, custom_choice_button_text='', **kw)


def button_group(key, label, choices, default, name=None, **kw):
    return base(key, label, name or key, 'button_group', choices=choices, default_value=default,
                return_format='value', allow_null=0, layout='horizontal', **kw)


def link(key, label, name=None, **kw):
    return base(key, label, name or key, 'link', return_format='array', **kw)


def page_link(key, label, name=None, **kw):
    kw.setdefault('allow_null', 1)
    return base(key, label, name or key, 'page_link', post_type=['page'], post_status=['publish'], taxonomy='',
                allow_archives=0, multiple=0, **kw)


def wysiwyg(key, label, name=None, **kw):
    return base(key, label, name or key, 'wysiwyg', default_value='', tabs='visual', toolbar='basic',
                media_upload=0, delay=0, **kw)


def page_field(key, label, name=None, **kw):
    return base(key, label, name or key, 'post_object', post_type=['page'], post_status=['publish'], taxonomy='',
                return_format='id', multiple=0, allow_null=1, bidirectional=0, ui=1, bidirectional_target=[], **kw)


def color(key, label, default, name=None, **kw):
    return base(key, label, name or key, 'color_picker', default_value=default, enable_opacity=0,
                return_format='string', custom_palette_source='', palette_colors='', show_color_wheel=True, **kw)


def select(key, label, choices, default, name=None, **kw):
    return base(key, label, name or key, 'select', choices=choices, default_value=default, return_format='value',
                multiple=0, allow_null=0, ui=0, ajax=0, placeholder='', allow_custom=0, search_placeholder='', create_options=0, save_options=0, **kw)


def range_field(key, label, default, min, max, step=1, append='', name=None, **kw):
    return base(key, label, name or key, 'range', default_value=default, min=min, max=max, step=step,
                prepend='', append=append, **kw)


def font_file(key, label, name=None, **kw):
    return base(key, label, name or key, 'file', return_format='id', library='all',
                min_size='', max_size=2, mime_types='woff2', **kw)


def true_false(key, label, name=None, default=1, on='On', off='Off', **kw):
    return base(key, label, name or key, 'true_false', message='', default_value=default, ui=1,
                ui_on_text=on, ui_off_text=off, **kw)


def toggle(key, label, instructions, **kw):
    return base(key, label, key, 'true_false', instructions=instructions, message='', default_value=1, ui=1,
                ui_on_text='Shown', ui_off_text='Hidden', **kw)


def message(key, label, msg, **kw):
    return base('msg_' + key, label, '', 'message', message=msg, new_lines='wpautop', esc_html=0, **kw)


def repeater(key, label, subs, name=None, min=0, max=0, button='Add row', layout='block', collapsed='', **kw):
    parent = 'field_pm_' + key
    for s in subs:
        s['parent_repeater'] = parent
    return base(key, label, name or key, 'repeater', layout=layout, pagination=0, min=min, max=max,
                collapsed=('field_pm_' + collapsed) if collapsed else '', button_label=button, rows_per_page=20,
                sub_fields=subs, **kw)


def group(key, title, fields, location, order=0, desc='', hide=None):
    return {
        'key': 'group_pm_' + key,
        'title': title,
        'fields': fields,
        'location': location,
        'menu_order': order,
        'position': 'normal',
        'style': 'default',
        'label_placement': 'top',
        'instruction_placement': 'label',
        'hide_on_screen': hide or '',
        'active': True,
        'description': desc,
        'show_in_rest': 0,
        'display_title': '',
        'allow_ai_access': False,
        'ai_description': '',
        'modified': NOW,
    }


DAYS = {d: d for d in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']}


# ---------------------------------------------------------------- Options: Pan Motors settings
options = group('options', 'Pan Motors settings', [
    tab('business', 'Business'),
    message('facts', 'About these settings',
            'These are the business facts used across the whole site: on the pages, in the footer and in the '
            'information Google reads. Keep them word for word the same as your Google Business Profile.'),
    text('legal_name', 'Company name (legal)', required=1, width='50', maxlength=60, placeholder='Pan Motors Ltd',
         instructions='As registered. Shown in the address on the homepage contact card.'),
    text('trading_name', 'Trading name', required=1, width='50', maxlength=40, placeholder='Pan Motors',
         instructions='The name customers know. Used for the logo text if no logo is uploaded.'),
    textarea('description', 'One-sentence description', rows=2, required=1, maxlength=200,
             instructions='One factual sentence: who you are, what you do, where. Shown as the paragraph in '
                          'the homepage About section and used by Google. Up to 200 characters.'),
    number('founded_year', 'Year founded', width='50', min=1900, max=2100, instructions='Optional. Used by Google only.'),
    gallery('business_photos', 'Business photos', max=3, min_width=1200,
            instructions='Up to three photos of the showroom for Google and AI search (not shown on the site). '
                         'Landscape, at least 1200px wide, ' + IMG_FORMAT + ' Empty: no photos are given.'),

    tab('contact', 'Contact'),
    text('street_address', 'Street', required=1, width='50', maxlength=60, placeholder='Avenue 65'),
    text('locality', 'Area', width='50', maxlength=40, placeholder='Mesoyi'),
    text('city', 'City', required=1, width='33', maxlength=40, placeholder='Paphos'),
    text('postcode', 'Postcode', required=1, width='33', maxlength=10, placeholder=8060),
    text('country', 'Country code', required=1, width='33', default_value='CY', maxlength=2,
         instructions='Two letters, e.g. CY.'),
    text('country_name', 'Country', width='50', default_value='Cyprus', maxlength=40,
         instructions='Shown at the end of the address.'),
    url('map_url', 'Google Maps link', width='50', instructions='The link to your business on Google Maps.'),
    text('map_embed_query', 'Map embed query', width='50', maxlength=120, placeholder='Pan Motors Mesoyi Paphos Cyprus',
         instructions='What the map on the Contact page searches for, as you would type it into Google Maps. The map loads '
                      'only when a visitor asks for it. Empty: a directions link instead of the map.'),
    number('latitude', 'Latitude', width='25', step='any', instructions='From the Google Maps pin. Used by Google only.'),
    number('longitude', 'Longitude', width='25', step='any'),
    repeater('phones', 'Phone numbers', [
        text('phone_number', 'Number', name='number', required=1, width='60', maxlength=24, placeholder='+357 99 000 000',
             instructions='International format with spaces.'),
        text('phone_label', 'Label', name='label', width='40', maxlength=20,
             instructions='Optional, for your own reference, e.g. Sales.'),
    ], min=1, max=4, button='Add phone number', layout='table',
        instructions='Shown on the homepage contact card, each one tappable to call.'),
    email('email', 'Email address', required=1, instructions='Shown on the homepage contact card.'),
    text('contact_button_label', 'Contact button text', width='50', default_value='Contact', maxlength=16,
         instructions='The round button at the top right and the last item of the mobile menu. Up to 16 characters.'),

    tab('hours', 'Opening hours'),
    message('hours', 'How opening hours work',
            'Each row is one line in the hours list on the homepage. "Day", "Time" and "Note" are what visitors '
            'read. "Open on", "Opens" and "Closes" tell Google the same thing: keep both in step.'),
    repeater('hours', 'Opening hours', [
        text('hours_day', 'Day', name='day', required=1, width='34', maxlength=24, placeholder='Monday — Friday'),
        text('hours_time', 'Time', name='time', required=1, width='33', maxlength=20, placeholder='08:00 — 13:00'),
        text('hours_note', 'Note', name='note', width='33', maxlength=20, placeholder='Morning'),
        checkbox('hours_days', 'Open on', DAYS, name='days', required=1),
        time_picker('hours_opens', 'Opens', name='opens', required=1, width='50'),
        time_picker('hours_closes', 'Closes', name='closes', required=1, width='50'),
    ], min=1, max=10, button='Add opening hours', collapsed='hours_day'),

    tab('marques', 'Marques'),
    repeater('marques', 'Marques', [
        text('marque_name', 'Marque', name='name', required=1, maxlength=20, placeholder='Porsche'),
    ], min=1, max=20, button='Add marque', layout='table',
        instructions='The names scrolling across the homepage under the video. Drag to reorder.'),
    text('marquee_separator', 'Separator', width='25', default_value='—', maxlength=3,
         instructions='The mark shown between the names.'),

    tab('values', 'Our Values'),
    message('values', 'Our Values',
            'The values shown by the Our Values block, on the homepage and on About. Edit them here once; '
            'every page that shows the block updates.'),
    repeater('values', 'Values', [
        text('value_index', 'Small label', name='index', width='30', maxlength=20, placeholder='01'),
        text('value_title', 'Title', name='title', required=1, width='70', maxlength=24, placeholder='Chosen'),
        textarea('value_body', 'Short text', name='body', rows=2, maxlength=140,
                 instructions='Up to 140 characters.'),
    ], min=1, max=6, button='Add value', collapsed='value_title',
        instructions='Four fit one row on a computer. Drag to reorder.'),

    tab('social', 'Social'),
    url('instagram_url', 'Instagram', width='50', instructions='Used by the "Follow the floor" button on the homepage.'),
    url('facebook_url', 'Facebook', width='50'),
    url('google_business_url', 'Google Business Profile', width='50'),
    repeater('other_profiles', 'Other profiles', [
        text('profile_label', 'Name', name='label', width='30', maxlength=30, placeholder='YouTube'),
        url('profile_url', 'Link', name='url', required=1, width='70'),
    ], min=0, max=10, button='Add profile', layout='table',
        instructions='Any other official profiles. Used by Google to connect them to your business.'),

    tab('footer', 'Footer'),
    text('footer_tagline', 'Footer line', width='50', default_value='Pan Motors — Paphos', maxlength=40,
         instructions='Next to the small logo at the bottom of every page.'),
    text('footer_copyright', 'Copyright', width='50', default_value='© {year} Pan Motors', maxlength=40,
         instructions='Bottom right of every page. {year} becomes the current year automatically.'),

    tab('notfound', 'Page not found'),
    message('notfound', 'The "page not found" page', 'Shown when someone opens a link that no longer exists.'),
    text('notfound_code', 'Large number', width='25', default_value=404, maxlength=4),
    text('notfound_eyebrow', 'Small red line', width='75', default_value='Page not found', maxlength=40),
    text('notfound_title', 'Heading', default_value='Off the Map', maxlength=30),
    textarea('notfound_text', 'Text', rows=2, maxlength=160,
             default_value='The page you were looking for has moved or no longer exists. The cars are still where we left them.'),
    text('notfound_home_label', 'Home button', width='50', default_value='Back to home', maxlength=24),
    text('notfound_contact_label', 'Contact button', width='50', default_value='Contact us', maxlength=24),

    tab('design', 'Design', design=True),
    message('design', 'About the design settings',
            'Colours, fonts, sizes, corners and logos for the whole site, on every page and in the page editor. '
            'Each setting starts at the value of the original design: change one, save, and check the site.',
            design=True),
    color('color_ink', 'Dark colour', '#0c0b0b', width='25', design=True,
          instructions='Dark backgrounds and text on light sections.'),
    color('color_paper', 'Light colour', '#f3f2f2', width='25', design=True,
          instructions='Light backgrounds and text on dark sections.'),
    color('color_accent', 'Accent colour', '#ec3013', width='25', design=True,
          instructions='Small red lines, active links, buttons on hover, focus rings.'),
    color('color_surface', 'Card colour', '#161514', width='25', design=True,
          instructions='Dark cards and the space behind photos while they load.'),
    message('contrast', 'Contrast check', '{contrast}', design=True),
    select('font_heading', 'Heading font', {
        'bodoni-moda': 'Bodoni Moda', 'playfair-display': 'Playfair Display',
        'cormorant-garamond': 'Cormorant Garamond', 'dm-serif-display': 'DM Serif Display', 'custom': 'Custom (upload)',
    }, 'bodoni-moda', width='50', design=True, instructions='Headings, titles and large numbers.'),
    select('font_body', 'Body font', {
        'archivo': 'Archivo', 'inter': 'Inter', 'manrope': 'Manrope', 'dm-sans': 'DM Sans', 'custom': 'Custom (upload)',
    }, 'archivo', width='50', design=True, instructions='Paragraphs, menus, buttons and small labels.'),
    font_file('font_heading_regular', 'Heading font file', width='25', design=True,
              instructions='WOFF2, up to 2 MB. The regular style.',
              conditional_logic=[[{'field': 'field_pm_font_heading', 'operator': '==', 'value': 'custom'}]]),
    font_file('font_heading_italic', 'Heading font file, italic', width='25', design=True,
              instructions='Optional. WOFF2.',
              conditional_logic=[[{'field': 'field_pm_font_heading', 'operator': '==', 'value': 'custom'}]]),
    font_file('font_body_regular', 'Body font file', width='25', design=True,
              instructions='WOFF2, up to 2 MB. The regular style.',
              conditional_logic=[[{'field': 'field_pm_font_body', 'operator': '==', 'value': 'custom'}]]),
    font_file('font_body_italic', 'Body font file, italic', width='25', design=True,
              instructions='Optional. WOFF2.',
              conditional_logic=[[{'field': 'field_pm_font_body', 'operator': '==', 'value': 'custom'}]]),
    message('fonts', 'Font licence',
            'Upload only fonts your licence allows on a website. Without a file, the site uses the design\'s font.',
            design=True,
            conditional_logic=[[{'field': 'field_pm_font_heading', 'operator': '==', 'value': 'custom'}],
                               [{'field': 'field_pm_font_body', 'operator': '==', 'value': 'custom'}]]),
    range_field('heading_scale', 'Heading size', 100, 85, 115, append='%', width='33', design=True,
                instructions='Makes every heading larger or smaller. 100% is the design.'),
    range_field('body_size', 'Text size', 16, 14, 18, append='px', width='33', design=True,
                instructions='Paragraph text. Small labels keep their size. 16px is the design.'),
    range_field('radius', 'Corner rounding', 24, 0, 48, append='px', width='34', design=True,
                instructions='Cards, photos and panels. 0 is square. 24px is the design.'),
    range_field('logo_height', 'Logo height, computer', 46, 24, 80, append='px', width='50', design=True,
                instructions='The logo at the top of every page. 46px is the design.'),
    range_field('logo_height_mobile', 'Logo height, phone', 46, 24, 80, append='px', width='50', design=True,
                instructions='The same logo on screens up to 880px wide.'),
    image('logo_light', 'Logo for light backgrounds', width='50', design=True,
          instructions='Optional. A dark version of the logo, used in the light footer. Empty: the main logo is '
                       'turned dark automatically. PNG or WebP with a transparent background, at least 300px wide.',
          mime_types='png, webp'),
    image('share_image', 'Default share image', min_width=1200, min_height=630, width='50', design=True,
          instructions='Shown when a page is shared on social media or in messages and the page has no featured '
                       'image of its own. 1200 × 630px. ' + IMG_FORMAT),
    message('site_icon', 'Main logo and site icon', '{identity}', design=True),

    tab('technical', 'Technical', admin_only=True),
    message('technical', 'For the site administrator',
            'Only administrators see this tab. Changing these can break links or the enquiry form.', admin_only=True),
    page_field('page_contact', 'Contact button goes to', width='50', admin_only=True,
               instructions='The page opened by the Contact button (header, mobile menu, page not found). Empty: the button is hidden.'),
    text('enquire_form_shortcode', 'Enquiry form shortcode', placeholder='[contact-form-7 id="123"]', admin_only=True,
         instructions='From the form plugin. Empty: a preview form is shown that does not send.'),
    true_false('allow_ai_crawlers', 'Allow AI crawlers', default=1, on='Allowed', off='Blocked', admin_only=True, width='50',
               instructions='GPTBot, OAI-SearchBot, ClaudeBot, PerplexityBot and Google-Extended may read the site (robots.txt). '
                            'Off: they are asked not to. Search engines are not affected.'),
    true_false('llms_txt', 'llms.txt', default=1, on='On', off='Off', admin_only=True, width='50',
               instructions='A plain-text summary for AI tools at /llms.txt: business facts, hours and the menu pages.'),
    true_false('design_editors', 'Editors can change the design', default=0, on='Yes', off='No', admin_only=True,
               instructions='Shows the Design tab to Editors. Off: only administrators see it.'),
], [[{'param': 'options_page', 'operator': '==', 'value': 'panmotors-settings'}]])


# ---------------------------------------------------------------- Cars (pm_car, data store only)
def relationship(key, label, post_type, name=None, **kw):
    return base(key, label, name or key, 'relationship', post_type=post_type, post_status=['publish'], taxonomy='',
                filters=['search'], return_format='id', min=kw.pop('min', ''), max=kw.pop('max', ''),
                elements=['featured_image'], bidirectional=0, bidirectional_target=[], **kw)


car = group('car', 'Car', [
    image('car_image', 'Photo', name='image', min_width=1960, min_height=1102,
          instructions='Landscape, at least 1960 × 1102px. ' + IMG_FORMAT + ' Keep the car in the centre: '
                       'Featured Cars crops it tall, Latest Cars shows it 16:9. Empty: a plain dark card until '
                       'the photo is ready (homepage rows need a photo).'),
    text('car_marque', 'Marque', name='marque', required=1, width='50', maxlength=20, placeholder='Porsche'),
    text('car_model', 'Model', name='model_name', required=1, width='50', maxlength=28, placeholder='911 Carrera'),
    text('car_ref', 'Reference', name='ref_no', width='33', maxlength=12, placeholder='No. 04'),
    text('car_spec', 'Detail', name='spec', width='33', maxlength=22, placeholder='Flat six'),
    textarea('car_note', 'Note', name='note', rows=2, maxlength=160, placeholder='Full service history, ceramic brakes, sport chrono.',
             instructions='One or two sentences about this car. The description in the Featured Cars page\'s car sheet, '
                          'and a short line on homepage tiles. Up to 160 characters.'),
    link('car_link', 'Link', name='link', instructions='Optional. Leave empty: the Featured Cars tile is a showcase only.'),
    text('slide_caption', 'Slider caption', name='caption', width='60', maxlength=40, placeholder='Bay four, morning light',
         instructions='Under the photo in Latest Cars.'),
    text('slide_place', 'Place', name='place', width='40', default_value='Paphos', maxlength=20,
         instructions='Right of the caption in Latest Cars.'),
    message('car_sheet', 'Car sheet',
            'Shown when a visitor opens this car on the Featured Cars page. Empty lines are left out.'),
    text('car_year', 'Year', name='year', width='25', maxlength=4, placeholder='2023'),
    text('car_power', 'Power', name='power', width='25', maxlength=14, placeholder='650 hp'),
    text('car_sprint', 'Acceleration (0–100)', name='acceleration', width='25', maxlength=20, placeholder='0–100 in 2.7 s',
         instructions='As it should read, e.g. "0–100 in 2.7 s".'),
    text('car_mileage', 'Mileage', name='mileage', width='25', maxlength=14, placeholder='4,800 km'),
    text('car_engine', 'Engine', name='engine', width='34', maxlength=30, placeholder='3.8 flat six, twin turbo'),
    text('car_gearbox', 'Gearbox', name='gearbox', width='33', maxlength=20, placeholder='PDK, 8 speed'),
    text('car_colour', 'Colour', name='colour', width='33', maxlength=24, placeholder='Jet black'),
    true_false('car_on_page', 'On the Featured Cars page', name='on_page', default=1, on='Shown', off='Hidden',
               instructions='Shown in the grid on the Featured Cars page, in the order set under Page attributes → Order. '
                            'Its number ("No. 01") is its place in that order.'),
    true_false('car_featured', 'Featured', name='featured', default=0, on='Yes', off='No',
               instructions='Shown in the homepage Featured Cars row (in the order set under Page attributes → Order). '
                            'Latest Cars shows the newest cars by date.'),
], [[{'param': 'post_type', 'operator': '==', 'value': 'pm_car'}]],
    desc='Showcase only, no prices. The name in the list is set from marque and model.')


# ---------------------------------------------------------------- Blocks (D11): one group per pm/* block
def block_group(name, title, fields):
    return group('block_' + name.replace('-', '_'), 'Block: ' + title, fields,
                 [[{'param': 'block', 'operator': '==', 'value': 'pm/' + name}]])


def heading(key, default):
    return text(key, 'Heading', maxlength=30, default_value=default, instructions='The large heading of this section.')


b_hero = block_group('hero', 'Top video', [
    text('hero_eyebrow', 'Small red line', required=1, maxlength=60,
         placeholder='Pan Motors, luxury car boutique in Paphos, Cyprus',
         instructions='Above the big title. Say who and where. Up to 60 characters.'),
    textarea('hero_title', 'Big title', rows=2, required=1, maxlength=40,
             instructions='Each new line starts a new line on the page. Up to 40 characters.'),
    video('hero_video', 'Background video',
          instructions='MP4, landscape, 10–20 seconds, under 8 MB. Plays silently. Leave empty to show only the photo.'),
    image('hero_poster', 'Background photo', required=1, min_width=1920,
          instructions='Shown while the video loads and for visitors who turn off motion. Landscape, at least 2400px wide, ' + IMG_FORMAT),
    text('hero_cta_label', 'Button text', width='50', default_value='View the cars', maxlength=24),
    page_link('hero_cta_link', 'Button goes to', width='50', required=1, allow_null=0,
              instructions='The page the button opens, e.g. Featured Cars.'),
])

b_marquee = block_group('marquee', 'Marques strip', [
    message('block_marquee', 'Content', 'The names and the separator are edited in {settings} → Marques.'),
])

b_featured = block_group('featured-cars', 'Featured Cars', [
    heading('featured_title', 'Featured Cars'),
    textarea('featured_intro', 'Short intro', rows=2, maxlength=200,
             instructions='One or two sentences next to the heading. Up to 200 characters.'),
    button_group('featured_source', 'Which cars', {'featured': 'Cars marked Featured', 'pick': 'Pick cars'}, 'featured',
                 instructions='Cars are added and edited under {cars}. Showcase only, no prices.'),
    relationship('featured_pick', 'Cars', ['pm_car'], max=12,
                 instructions='Choose the cars and drag them into order.',
                 conditional_logic=[[{'field': 'field_pm_featured_source', 'operator': '==', 'value': 'pick'}]]),
    number('featured_limit', 'How many', width='33', min=1, max=12, default_value=4,
           instructions='Four fill one row.'),
    text('featured_more_label', 'Button text', width='50', maxlength=28,
         instructions='The outlined button under the intro, e.g. "All featured cars". An arrow is added. Empty: no button.'),
    page_link('featured_more_link', 'Button goes to', width='50',
              instructions='Usually the Featured Cars page. Empty: no button.'),
])

b_values = block_group('values', 'Our Values', [
    message('block_values', 'Values', 'The values themselves are edited in {settings} → Our Values.'),
    button_group('values_style', 'Style', {'dark': 'Dark cards', 'light': 'Light section'}, 'dark',
                 instructions='Dark cards: outlined cards on the dark page, each card a link (homepage). '
                              'Light section: a light panel with a short intro, cards are not links (About).'),
    heading('values_title', 'Our Values'),
    textarea('values_intro', 'Short intro', rows=2, maxlength=160,
             default_value='Four things we hold to with every car and every client.',
             instructions='Next to the heading. Up to 160 characters.',
             conditional_logic=[[{'field': 'field_pm_values_style', 'operator': '==', 'value': 'light'}]]),
    page_link('values_link', 'Cards go to', instructions='The page each card opens, e.g. About Pan Motors. Empty: the cards are not links.',
              conditional_logic=[[{'field': 'field_pm_values_style', 'operator': '==', 'value': 'dark'}]]),
])

b_about = block_group('about', 'About Pan Motors', [
    image('about_image', 'Photo', min_width=1080, min_height=1350,
          instructions='Portrait (4:5), at least 1080 × 1350px. ' + IMG_FORMAT),
    text('about_eyebrow', 'Small red line', maxlength=40, instructions='Above the heading.'),
    heading('about_title', 'About Pan Motors'),
    message('block_about', 'Paragraph', 'The paragraph is the one-sentence description in {settings} → Business.'),
    repeater('about_stats', 'Three highlights', [
        text('stat_value', 'Word', name='value', required=1, width='40', maxlength=12, placeholder='Sales'),
        text('stat_label', 'Line under it', name='label', width='60', maxlength=28, placeholder='Luxury and performance'),
    ], min=0, max=4, button='Add highlight', layout='table',
        instructions='Shown in a row under the paragraph. Three fit best.'),
    true_false('about_fade', 'Scroll colour fade', default=1,
               instructions='The background turns from black to white while scrolling past.'),
])

b_latest = block_group('latest-cars', 'Latest Cars', [
    text('latest_eyebrow', 'Small red line', maxlength=40, instructions='Above the heading.'),
    heading('latest_title', 'Latest Cars'),
    number('latest_limit', 'How many', width='33', min=1, max=12, default_value=6,
           instructions='The newest cars by date, from {cars}.'),
])

b_live = block_group('live', 'Pan Motors Live', [
    text('live_eyebrow', 'Small red line', width='33', default_value='Social', maxlength=30),
    text('live_title', 'Heading', width='33', default_value='Pan Motors Live', maxlength=30),
    text('live_cta_label', 'Instagram button text', width='34', default_value='Follow the floor', maxlength=24,
         instructions='Opens your Instagram (link in {settings} → Social).'),
    repeater('live_posts', 'Posts', [
        button_group('post_type', 'Type', {'video': 'Video', 'photo': 'Photo'}, 'video', name='type'),
        video('post_video', 'Video', name='video', max_size=8,
              instructions='Vertical MP4 (9:16), under 8 MB. Plays silently while on screen.',
              conditional_logic=[[{'field': 'field_pm_post_type', 'operator': '==', 'value': 'video'}]]),
        image('post_image', 'Photo', name='image', min_width=1080,
              instructions='The photo, or the still shown before a video plays. Portrait (4:5), at least 1080px wide. ' + IMG_FORMAT),
        url('post_url', 'Instagram link', name='url', required=1, instructions='The post this tile opens.'),
        text('post_caption', 'Caption', name='caption', width='50', maxlength=18),
        text('post_likes', 'Likes', name='likes', width='25', maxlength=6, instructions='Optional, e.g. 24K.'),
        text('post_comments', 'Comments', name='comments', width='25', maxlength=6, instructions='Optional.'),
    ], min=0, max=9, button='Add post', collapsed='post_caption',
        instructions='Entered by hand. Six posts fill the grid. Drag to reorder.'),
])

b_showroom = block_group('showroom', 'The Showroom', [
    text('showroom_eyebrow', 'Small red line', maxlength=40, instructions='Above the heading, e.g. "Avenue 65, Mesoyi".'),
    heading('showroom_title', 'The Showroom'),
    textarea('showroom_intro', 'Intro', rows=2, maxlength=200,
             instructions='One or two sentences next to the heading. Up to 200 characters.'),
    gallery('showroom_photos', 'Photos', min=1, max=12, min_width=1600,
            instructions='Landscape (16:10), at least 2160px wide, ' + IMG_FORMAT +
                         ' The caption under each photo is the image\'s Caption in the media library.'),
    true_false('showroom_hours', 'Opening hours', default=1, on='Shown', off='Hidden',
               instructions='The opening hours from {settings} under the photos.'),
])

b_enquire = block_group('enquire', 'Come And See', [
    text('enquire_title', 'Heading', default_value='Come And See', maxlength=30,
         instructions='The heading of the dark contact card.'),
    textarea('enquire_intro', 'Intro', rows=2, maxlength=160,
             instructions='Under the heading. Up to 160 characters.'),
    message('enquire_contact', 'Contact details', 'Address, phone numbers and email are edited in {settings} → Contact.'),
    message('home_form', 'Preview form',
            'Until the enquiry form plugin is connected, a preview form is shown with these texts. It does not send.'),
    text('form_label_name', 'Name label', width='50', default_value='Name', maxlength=20),
    text('form_hint_name', 'Name hint', width='50', default_value='Full name', maxlength=40),
    text('form_label_email', 'Email label', width='50', default_value='Email', maxlength=20),
    text('form_hint_email', 'Email hint', width='50', default_value='you@domain.com', maxlength=40),
    text('form_label_phone', 'Phone label', width='50', default_value='Phone', maxlength=20),
    text('form_hint_phone', 'Phone hint', width='50', default_value='+357', maxlength=40),
    text('form_label_message', 'Message label', width='50', default_value='Message', maxlength=20),
    text('form_hint_message', 'Message hint', width='50', default_value='Tell us which car you are interested in.', maxlength=60),
    text('form_button', 'Button text', width='50', default_value='Send message', maxlength=24),
])

b_faq = block_group('faq', 'Questions', [
    text('faq_title', 'Heading', default_value='Questions', maxlength=30),
    repeater('faqs', 'Questions', [
        text('faq_question', 'Question', name='question', required=1, maxlength=120),
        textarea('faq_answer', 'Answer', name='answer', rows=3, required=1, maxlength=500,
                 instructions='One to three plain sentences, the answer first. Google reads these too.'),
    ], min=0, max=20, button='Add question', collapsed='faq_question'),
])

b_page_hero = block_group('page-hero', 'Page top', [
    message('block_page_hero', 'Title', 'The big title is the page title.'),
    text('page_eyebrow', 'Small red line', maxlength=40, instructions='Above the page title, e.g. "Avenue 65, Mesoyi".'),
    textarea('page_intro', 'Short intro', rows=2, maxlength=200,
             instructions='One or two sentences under the page title. Also used as the page description for Google. Up to 200 characters.'),
    image('page_hero_image', 'Top image', min_width=1920,
          instructions='Optional background behind the page title, shown in black and white. Landscape, at least 2400px wide, ' + IMG_FORMAT),
])

b_cta = block_group('cta-band', 'Call to action', [
    textarea('cta_title', 'Heading', rows=2, default_value='Come and see', maxlength=30,
             instructions='Each new line starts a new line on the page. Up to 30 characters.'),
    textarea('cta_text', 'Text', rows=2, maxlength=160, instructions='Optional, under the heading. Up to 160 characters.'),
    text('cta_label', 'Button text', width='50', default_value='Contact us', maxlength=24, instructions='An arrow is added.'),
    page_link('cta_link', 'Button goes to', width='50', required=1, allow_null=0, instructions='Usually the Contact page.'),
])

page_settings = group('page', 'Page settings', [
    select('schema_type', 'Type for search engines', {'auto': 'Automatic', 'web': 'Web page', 'about': 'About page',
                                                      'contact': 'Contact page'}, 'auto',
           instructions='How Google and AI search read this page. Automatic: Contact page for the page the Contact button '
                        'opens, else Web page.'),
    button_group('footer_style', 'Footer style', {'auto': 'Auto', 'dark': 'Dark', 'light': 'Light'}, 'auto',
                 instructions='Auto: the footer takes the colour of the last section on the page.'),
], [[{'param': 'post_type', 'operator': '==', 'value': 'page'}]], order=10)
page_settings['position'] = 'side'

# ---------------------------------------------------------------- D12 inner-page blocks (docs/inner-pages.md)
b_page_header = block_group('page-header', 'Page header', [
    button_group('header_style', 'Style', {'image': 'Photo', 'text': 'Text'}, 'text',
                 instructions='Photo: a full-width photo with the title over it (About, Showroom). '
                              'Text: the title on the dark page (Featured Cars, Contact).'),
    text('header_eyebrow', 'Small red line', maxlength=50, instructions='Above the title, e.g. "About — Mesoyi, Paphos".'),
    textarea('header_title', 'Title', rows=2, maxlength=40,
             instructions='The page\'s main heading. Each new line starts a new line on the page. '
                          'Empty: the page title. Up to 40 characters.'),
    textarea('header_intro', 'Short intro', rows=2, maxlength=160,
             instructions='Next to the title. Also used as the page description for Google. Up to 160 characters.'),
    image('header_image', 'Photo', min_width=1920,
          instructions='Landscape, at least 2400px wide. ' + IMG_FORMAT + ' The alt text from the media library is used.',
          conditional_logic=[[{'field': 'field_pm_header_style', 'operator': '==', 'value': 'image'}]]),
    button_group('header_filter', 'Photo colour', {'none': 'Colour', 'grayscale': 'Black and white'}, 'none', width='50',
                 conditional_logic=[[{'field': 'field_pm_header_style', 'operator': '==', 'value': 'image'}]]),
    range_field('header_brightness', 'Photo brightness', 62, 50, 80, append='%', width='50',
                instructions='Darker makes the title easier to read. About uses 60%, Showroom 62%.',
                conditional_logic=[[{'field': 'field_pm_header_style', 'operator': '==', 'value': 'image'}]]),
])

b_story = block_group('story', 'Story', [
    text('story_eyebrow', 'Small red line', width='50', maxlength=40, placeholder='Our story'),
    textarea('story_title', 'Heading', rows=2, width='50', maxlength=40,
             instructions='Each new line starts a new line on the page. Up to 40 characters.'),
    textarea('story_lead', 'First paragraph', rows=3, maxlength=300,
             instructions='Empty: the one-sentence description from {settings} → Business, so the site '
                          'describes the business the same way everywhere. Up to 300 characters.'),
    wysiwyg('story_text', 'More paragraphs', instructions='One or two short paragraphs after the first. Bold, '
                                                          'italic and links only.'),
    image('story_image', 'Photo', min_width=1080, min_height=1350,
          instructions='Portrait (4:5), at least 1080 × 1350px. ' + IMG_FORMAT),
])

b_services = block_group('services', 'What We Do', [
    heading('services_title', 'What We Do'),
    repeater('services', 'Services', [
        text('service_index', 'Small red label', name='index', maxlength=20, placeholder='01 — Sales'),
        text('service_title', 'Title', name='title', required=1, maxlength=20, placeholder='Sales'),
        textarea('service_body', 'Short text', name='body', rows=2, maxlength=110, instructions='Up to 110 characters.'),
        image('service_image', 'Photo', name='image', min_width=1200,
              instructions='At least 1200px wide, the subject in the centre: the tile is tall and narrow until '
                           'hovered. ' + IMG_FORMAT),
    ], min=2, max=4, button='Add service', collapsed='service_title',
        instructions='Two to four tiles in a row. Drag to reorder.'),
])

b_cta_image = block_group('cta-image', 'Photo call to action', [
    text('ctai_eyebrow', 'Small red line', width='50', maxlength=40, instructions='Optional, above the heading.'),
    textarea('ctai_title', 'Heading', rows=2, width='50', maxlength=30,
             instructions='Each new line starts a new line on the page. Up to 30 characters.'),
    image('ctai_image', 'Background photo', min_width=1920,
          instructions='Landscape, at least 2400px wide, darkened behind the heading. ' + IMG_FORMAT),
    button_group('ctai_filter', 'Photo colour', {'grayscale': 'Black and white', 'none': 'Colour'}, 'grayscale', width='33'),
    range_field('ctai_brightness', 'Photo brightness', 55, 40, 70, append='%', width='33',
                instructions='About uses 55%, Showroom 50%.'),
    button_group('ctai_size', 'Card height', {'tall': 'Tall', 'standard': 'Standard'}, 'tall', width='34',
                 instructions='Tall: About. Standard: a little less space above and below the heading (Showroom).'),
    text('ctai_label', 'Main button text', width='50', maxlength=26, placeholder='Visit the showroom',
         instructions='The light button. An arrow is added.'),
    page_link('ctai_link', 'Main button goes to', width='50'),
    text('ctai_label_2', 'Second button text', width='50', maxlength=26, instructions='Optional outlined button.'),
    page_link('ctai_link_2', 'Second button goes to', width='50'),
])

b_cars_grid = block_group('cars-grid', 'Cars grid', [
    message('block_cars_grid', 'Cars', 'The cars, their photos and car sheets are edited under {cars}. The filter buttons are '
                                       'made from their marques, with the number of cars for each.'),
    button_group('cars_source', 'Which cars', {'all': 'All cars on the page', 'featured': 'Featured only'}, 'all',
                 instructions='All: every car with "On the Featured Cars page" on. Featured only: the cars marked Featured.'),
    textarea('cars_intro', 'Short intro', rows=2, maxlength=200,
             instructions='Optional, above the filter buttons. Up to 200 characters.'),
    text('cars_all_label', 'First filter button', width='50', default_value='All', maxlength=16,
         instructions='The button that shows every car.'),
    text('cars_enquire_label', 'Car sheet button text', width='50', default_value='Enquire', maxlength=20,
         instructions='The red button in each car sheet. An arrow is added.'),
    page_link('cars_enquire_link', 'Car sheet button goes to', width='50', instructions='Usually the Contact page. Empty: no button.'),
    text('cars_label_year', 'Label: year', width='25', default_value='Year', maxlength=16),
    text('cars_label_engine', 'Label: engine', width='25', default_value='Engine', maxlength=16),
    text('cars_label_power', 'Label: power', width='25', default_value='Power', maxlength=16),
    text('cars_label_sprint', 'Label: acceleration', width='25', default_value='Acceleration', maxlength=16),
    text('cars_label_gearbox', 'Label: gearbox', width='25', default_value='Gearbox', maxlength=16),
    text('cars_label_colour', 'Label: colour', width='25', default_value='Colour', maxlength=16),
    text('cars_label_mileage', 'Label: mileage', width='25', default_value='Mileage', maxlength=16),
    text('cars_label_no', 'Number prefix', width='25', default_value='No.', maxlength=6,
         instructions='Before each car\'s number, e.g. "No. 01".'),
])

b_photo_slider = block_group('photo-slider', 'Photo slider', [
    text('slider_title', 'Heading', width='50', default_value='Inside', maxlength=30),
    text('slider_hint', 'Hint', width='50', default_value='Drag or use arrows', maxlength=30,
         instructions='Right of the caption under the photo.'),
    gallery('slider_photos', 'Photos', min=1, max=12, min_width=1600,
            instructions='Landscape (16:10), at least 2160px wide, ' + IMG_FORMAT +
                         ' The caption under each photo is the image\'s Caption in the media library; its alt text '
                         'describes it for screen readers. Drag to reorder.'),
])

b_visit = block_group('visit', 'Visit', [
    message('block_visit', 'Hours and address', 'The opening hours, address, phone numbers, email and map link come from '
                                                 '{settings} (Opening hours and Contact), so they match the rest of the site.'),
    text('visit_hours_label', 'Small red line, hours', width='50', default_value='Opening hours', maxlength=30),
    text('visit_find_label', 'Small red line, address', width='50', default_value='Find us', maxlength=30),
    text('visit_directions_label', 'Directions button', width='50', default_value='Get directions', maxlength=24,
         instructions='Opens the Google Maps link from {settings}. An arrow is added. Empty: no button.'),
])

b_contact_details = block_group('contact-details', 'Contact rows', [
    message('block_contact_details', 'Contact details', 'The phone numbers, email, address, map link and Instagram come from '
                                                         '{settings}. A row whose detail is empty there is left out.'),
    text('cd_call_label', 'Phone: label', width='50', default_value='Call us', maxlength=20),
    text('cd_call_action', 'Phone: action', width='50', default_value='Call', maxlength=16, instructions='An arrow is added.'),
    text('cd_email_label', 'Email: label', width='50', default_value='Email us', maxlength=20),
    text('cd_email_action', 'Email: action', width='50', default_value='Write', maxlength=16),
    text('cd_find_label', 'Address: label', width='50', default_value='Find us', maxlength=20),
    text('cd_find_action', 'Address: action', width='50', default_value='Directions', maxlength=16,
         instructions='Opens the Google Maps link in a new tab.'),
    text('cd_follow_label', 'Social: label', width='50', default_value='Follow us', maxlength=20),
    text('cd_follow_action', 'Social: action', width='50', default_value='Open', maxlength=16,
         instructions='Opens Instagram in a new tab.'),
])

b_contact_form = block_group('contact-form', 'Contact form and map', [
    text('cf_title', 'Heading', width='50', default_value='Write To Us', maxlength=30,
         instructions='The heading of the dark form card.'),
    text('cf_shortcode', 'Form shortcode', width='50', placeholder='[contact-form-7 id="123"]', admin_only=True,
         instructions='From the form plugin, for this form only. Empty: the enquiry form shortcode in {settings} → Technical.'),
    message('cf_preview', 'Preview form',
            'Until a form plugin is connected, a preview form is shown with these texts. It does not send.'),
    text('cf_label_name', 'Name label', width='50', default_value='Name', maxlength=20),
    text('cf_hint_name', 'Name hint', width='50', default_value='Full name', maxlength=40),
    text('cf_label_email', 'Email label', width='50', default_value='Email', maxlength=20),
    text('cf_hint_email', 'Email hint', width='50', default_value='you@domain.com', maxlength=40),
    text('cf_label_subject', 'Subject label', width='50', default_value='Subject', maxlength=20),
    text('cf_hint_subject', 'Subject hint', width='50', default_value='Viewing, service, boutique', maxlength=40),
    text('cf_label_message', 'Message label', width='50', default_value='Message', maxlength=20),
    text('cf_hint_message', 'Message hint', width='50', default_value='Tell us which car you are interested in.', maxlength=60),
    text('cf_button', 'Button text', width='50', default_value='Send message', maxlength=24),
    message('cf_map', 'Map', 'The map shows the "Map embed query" from {settings} → Contact. It loads from Google only '
                             'after a visitor presses the button, because Google Maps sets cookies.'),
    text('cf_map_note', 'Map note', width='50', default_value='Google Maps', maxlength=40,
         instructions='The line on the map card before it loads.'),
    text('cf_map_button', 'Map button', width='50', default_value='Show map', maxlength=20),
    text('cf_map_link', 'Directions link', width='50', default_value='Get directions', maxlength=24,
         instructions='Shown instead of the map when there is no map query. An arrow is added.'),
    text('cf_hours_label', 'Hours card: small red line', width='50', default_value='Showroom hours', maxlength=30,
         instructions='The hours themselves come from {settings} → Opening hours.'),
])

GROUPS = (options, car, page_settings, b_page_header, b_story, b_services, b_cta_image, b_cars_grid, b_photo_slider, b_visit, b_contact_details, b_contact_form, b_hero, b_marquee, b_featured, b_values, b_about, b_latest, b_live, b_showroom, b_enquire,
          b_faq, b_page_hero, b_cta)

if __name__ == '__main__':
    keys = []

    def walk(fs):
        for f in fs:
            keys.append(f['key'])
            walk(f.get('sub_fields', []))

    for g in GROUPS:
        walk(g['fields'])
        path = os.path.join(OUT, g['key'] + '.json')
        # A group whose fields did not change keeps its file (and its "modified" time).
        if os.path.exists(path):
            with open(path) as fh:
                saved = json.load(fh)
            if {k: v for k, v in saved.items() if k != 'modified'} == {k: v for k, v in g.items() if k != 'modified'}:
                continue
        with open(path, 'w') as fh:
            # ACF's saved format: slashes escaped, so a seed run does not rewrite the files.
            fh.write(json.dumps(g, indent=4, ensure_ascii=False).replace('/', '\\/') + '\n')
    dupes = {k for k in keys if keys.count(k) > 1}
    print('groups', len(GROUPS), 'fields', len(keys), 'dupes', dupes or 'none')
