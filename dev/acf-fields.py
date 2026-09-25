"""Generates the ACF field groups in acf-json/. Development only.

Usage: python3 dev/acf-fields.py   then run the seed (dev/seed.php) to sync them into the database.

acf-json/ is the source ACF loads from; this script is how it is written, so field labels,
instructions and limits stay consistent. Edit field groups here, not in wp-admin.

Editor rules (docs/editability.md):
- Every label and instruction is written for the client: what it is, where it shows, how long.
- Images and videos state the recommended size and format; minimums protect the layout.
- Text that breaks the layout when too long gets a character limit.
- Fields marked pm_admin_only are hidden from non-administrators (inc/acf.php).
- Messages may use {edit:featured|about|latest|showroom|contact} and {settings} tokens, which
  inc/acf.php turns into links to that page's edit screen or to Pan Motors settings.
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
                max_size=kw.pop('max_size', 8), mime_types='jpg, jpeg, png, webp, avif', preview_size='medium', **kw)


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
    return base(key, label, name or key, 'page_link', post_type=['page'], post_status=['publish'], taxonomy='',
                allow_null=1, allow_archives=0, multiple=0, **kw)


def wysiwyg(key, label, name=None, **kw):
    return base(key, label, name or key, 'wysiwyg', default_value='', tabs='visual', toolbar='basic',
                media_upload=0, delay=0, **kw)


def page_field(key, label, name=None, **kw):
    return base(key, label, name or key, 'post_object', post_type=['page'], post_status=['publish'], taxonomy='',
                return_format='id', multiple=0, allow_null=1, bidirectional=0, ui=1, bidirectional_target=[], **kw)


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

    tab('contact', 'Contact'),
    text('street_address', 'Street', required=1, width='50', maxlength=60, placeholder='Avenue 65'),
    text('locality', 'Area', width='50', maxlength=40, placeholder='Mesoyi'),
    text('city', 'City', required=1, width='33', maxlength=40, placeholder='Paphos'),
    text('postcode', 'Postcode', required=1, width='33', maxlength=10, placeholder='8060'),
    text('country', 'Country code', required=1, width='33', default_value='CY', maxlength=2,
         instructions='Two letters, e.g. CY.'),
    text('country_name', 'Country', width='50', default_value='Cyprus', maxlength=40,
         instructions='Shown at the end of the address.'),
    url('map_url', 'Google Maps link', width='50', instructions='The link to your business on Google Maps.'),
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
    text('notfound_code', 'Large number', width='25', default_value='404', maxlength=4),
    text('notfound_eyebrow', 'Small red line', width='75', default_value='Page not found', maxlength=40),
    text('notfound_title', 'Heading', default_value='Off the Map', maxlength=30),
    textarea('notfound_text', 'Text', rows=2, maxlength=160,
             default_value='The page you were looking for has moved or no longer exists. The cars are still where we left them.'),
    text('notfound_home_label', 'Home button', width='50', default_value='Back to home', maxlength=24),
    text('notfound_contact_label', 'Contact button', width='50', default_value='Contact us', maxlength=24),

    tab('technical', 'Technical', admin_only=True),
    message('technical', 'For the site administrator',
            'Only administrators see this tab. Changing these can break links or the enquiry form.', admin_only=True),
    page_field('page_featured', 'Featured Cars page', width='50', admin_only=True),
    page_field('page_about', 'About page', width='50', admin_only=True),
    page_field('page_latest', 'Latest Cars page', width='50', admin_only=True),
    page_field('page_showroom', 'Showroom page', width='50', admin_only=True),
    page_field('page_contact', 'Contact page', width='50', admin_only=True),
    text('enquire_form_shortcode', 'Enquiry form shortcode', placeholder='[contact-form-7 id="123"]', admin_only=True,
         instructions='From the form plugin. Empty: a preview form is shown that does not send.'),
], [[{'param': 'options_page', 'operator': '==', 'value': 'panmotors-settings'}]])


# ---------------------------------------------------------------- Section pages (page templates)
def tpl(name):
    return [{'param': 'page_template', 'operator': '==', 'value': 'templates/page-' + name + '.php'}]


TEMPLATES = ['featured-cars', 'about', 'latest-cars', 'showroom', 'contact']

page_intro = group('page_intro', 'Page top', [
    text('page_eyebrow', 'Small red line', maxlength=40,
         instructions='Above the page title, e.g. "Avenue 65, Mesoyi". On the homepage it also appears above this section\'s heading.'),
    textarea('page_intro', 'Short intro', rows=2, maxlength=200,
             instructions='One or two sentences under the page title. Featured Cars and Showroom also show it on the homepage. Up to 200 characters.'),
    image('page_hero_image', 'Top image', min_width=1920,
          instructions='Optional background behind the page title, shown in black and white. Landscape, at least 2400px wide, ' + IMG_FORMAT),
    wysiwyg('page_body', 'Opening text',
            instructions='The first paragraphs of this page, under the top image. Write it for this page, do not copy the homepage.'),
], [tpl(t) for t in TEMPLATES], order=0, desc='Page title area, shared by the section pages.')

featured = group('page_featured', 'Featured Cars', [
    repeater('featured_cars', 'Cars', [
        image('car_image', 'Photo', name='image', required=1, min_width=1200,
              instructions='Portrait or square works best, at least 1400px wide. ' + IMG_FORMAT),
        text('car_marque', 'Marque', name='marque', required=1, width='50', maxlength=20, placeholder='Porsche'),
        text('car_model', 'Model', name='model_name', required=1, width='50', maxlength=28, placeholder='911 Carrera'),
        text('car_ref', 'Reference', name='ref_no', width='33', maxlength=12, placeholder='No. 04'),
        text('car_spec', 'Detail', name='spec', width='33', maxlength=22, placeholder='Flat six'),
        text('car_note', 'Note', name='note', width='34', maxlength=26, placeholder='Kept in slate grey'),
        link('car_link', 'Link', name='link', instructions='Optional. Leave empty: the tile is a showcase only.'),
    ], min=1, max=12, button='Add car', collapsed='car_model',
        instructions='Showcase only, no prices. The homepage shows the first four. Drag to reorder.'),
], [tpl('featured-cars')], order=1)

about = group('page_about', 'About', [
    image('about_image', 'Photo', min_width=1080, min_height=1350,
          instructions='Shown on the homepage About section. Portrait (4:5), at least 1080 × 1350px. ' + IMG_FORMAT),
    wysiwyg('about_story', 'Story',
            instructions='The longer story for this page. The homepage shows the one-sentence description from Pan Motors settings instead.'),
    repeater('about_stats', 'Three highlights', [
        text('stat_value', 'Word', name='value', required=1, width='40', maxlength=12, placeholder='Sales'),
        text('stat_label', 'Line under it', name='label', width='60', maxlength=28, placeholder='Luxury and performance'),
    ], min=0, max=4, button='Add highlight', layout='table',
        instructions='Shown in a row under the About text on the homepage. Three fit best.'),
    repeater('values', 'Our Values', [
        text('value_index', 'Small label', name='index', width='30', maxlength=20, placeholder='01 — Keeping'),
        text('value_title', 'Title', name='title', required=1, width='70', maxlength=24, placeholder='Kept Running'),
        textarea('value_body', 'Short text', name='body', rows=2, maxlength=140,
                 instructions='Shown on the homepage card. Up to 140 characters.'),
        wysiwyg('value_detail', 'Longer text', name='detail', instructions='For the About page.'),
        image('value_image', 'Photo', name='image', min_width=1200, instructions='Optional, for the About page. Landscape. ' + IMG_FORMAT),
    ], min=1, max=6, button='Add value', collapsed='value_title',
        instructions='The Our Values cards on the homepage. Three fit one row. Drag to reorder.'),
    message('about_marques', 'Marques', 'The marques are edited in {settings} → Marques.'),
], [tpl('about')], order=1)

latest = group('page_latest', 'Latest Cars', [
    repeater('latest_cars', 'Cars', [
        image('slide_image', 'Photo', name='image', required=1, min_width=1960, min_height=1102,
              instructions='Landscape (16:9), at least 1960 × 1102px. ' + IMG_FORMAT),
        text('slide_caption', 'Caption', name='caption', width='60', maxlength=40, placeholder='Bay four, morning light'),
        text('slide_place', 'Place', name='place', width='40', default_value='Paphos', maxlength=20),
    ], min=1, max=12, button='Add car', collapsed='slide_caption',
        instructions='The slider on the homepage, in this order. Drag to reorder.'),
], [tpl('latest-cars')], order=1)

showroom = group('page_showroom', 'Showroom', [
    gallery('showroom_photos', 'Photos', min=1, max=12, min_width=1600,
            instructions='The photo slider on the homepage. Landscape (16:10), at least 2160px wide, ' + IMG_FORMAT +
                         ' The caption under each photo is the image\'s Caption in the media library.'),
    wysiwyg('getting_here', 'Getting here',
            instructions='Directions from Paphos centre and Paphos airport, and where to park. For the Showroom page.'),
    message('showroom_hours', 'Opening hours and address', 'Hours and the address are edited in {settings}.'),
], [tpl('showroom')], order=1)

contact = group('page_contact', 'Contact', [
    tab('contact_card', 'Contact card'),
    text('enquire_title', 'Heading', default_value='Come And See', maxlength=30,
         instructions='The heading of the dark contact card, on this page and the homepage.'),
    textarea('enquire_intro', 'Intro', rows=2, maxlength=160,
             instructions='Under the heading. Up to 160 characters.'),
    message('enquire_contact', 'Contact details', 'Address, phone numbers and email are edited in {settings} → Contact.'),
    tab('contact_faq', 'Questions'),
    text('faq_title', 'Heading', default_value='Questions', maxlength=30),
    repeater('faqs', 'Questions', [
        text('faq_question', 'Question', name='question', required=1, maxlength=120),
        textarea('faq_answer', 'Answer', name='answer', rows=3, required=1, maxlength=500,
                 instructions='One to three plain sentences, the answer first. Google reads these too.'),
    ], min=0, max=20, button='Add question', collapsed='faq_question'),
], [tpl('contact')], order=1)


# ---------------------------------------------------------------- Homepage: tabs in page order
def section_tab(key, label, show_label, heading_default='', note=''):
    fields = [
        tab(key, label),
        toggle('home_show_' + key, 'Show this section', 'Switch off to hide "' + show_label + '" from the homepage.'),
    ]
    if heading_default is not None:
        fields.append(text('home_%s_title' % key, 'Heading', maxlength=30, default_value=heading_default,
                           instructions='The large heading of this section on the homepage.'))
    if note:
        fields.append(message('home_' + key, 'Content', note))
    return fields


front = group('front_page', 'Homepage', [
    tab('hero', 'Top video'),
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
    page_link('hero_cta_link', 'Button goes to', width='50', instructions='Leave empty for the Featured Cars page.'),

    *section_tab('marquee', 'Marques strip', 'the scrolling marques', heading_default=None,
                 note='The names and the separator are edited in {settings} → Marques.'),
    *section_tab('featured', 'Featured Cars', 'Featured Cars', 'Featured Cars',
                 'The cars and the short intro are edited on the {edit:featured}.'),
    *section_tab('values', 'Our Values', 'Our Values', 'Our Values',
                 'The value cards are edited on the {edit:about} → Our Values. Each card links to that page.'),
    *section_tab('about', 'About Pan Motors', 'About Pan Motors', 'About Pan Motors',
                 'The photo, the small red line and the three highlights are edited on the {edit:about}. '
                 'The paragraph is the one-sentence description in {settings} → Business.'),
    *section_tab('latest', 'Latest Cars', 'Latest Cars', 'Latest Cars',
                 'The cars and the small red line are edited on the {edit:latest}.'),
    tab('live', 'Pan Motors Live'),
    toggle('home_show_live', 'Show this section', 'Switch off to hide "Pan Motors Live" from the homepage.'),
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
    *section_tab('showroom', 'The Showroom', 'The Showroom', 'The Showroom',
                 'The photos, the small red line and the intro are edited on the {edit:showroom}. '
                 'Opening hours are in {settings} → Opening hours.'),
    tab('enquire', 'Come And See'),
    toggle('home_show_enquire', 'Show this section', 'Switch off to hide "Come And See" from the homepage.'),
    message('home_enquire', 'Content',
            'The heading and intro are edited on the {edit:contact}. The address, phone numbers and email are in {settings} → Contact.'),
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
], [[{'param': 'page_type', 'operator': '==', 'value': 'front_page'}]],
    desc='Homepage: tabs in the same order as the page.')

GROUPS = (options, front, page_intro, featured, about, latest, showroom, contact)

if __name__ == '__main__':
    keys = []

    def walk(fs):
        for f in fs:
            keys.append(f['key'])
            walk(f.get('sub_fields', []))

    for g in GROUPS:
        walk(g['fields'])
        with open(os.path.join(OUT, g['key'] + '.json'), 'w') as fh:
            json.dump(g, fh, indent=4, ensure_ascii=False)
            fh.write('\n')
    dupes = {k for k in keys if keys.count(k) > 1}
    print('groups', len(GROUPS), 'fields', len(keys), 'dupes', dupes or 'none')
