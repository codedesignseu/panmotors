"""Generates the ACF field groups in acf-json/. Development only.

Usage: python3 dev/acf-fields.py   then run the seed (dev/seed.php) to sync them into the database.

acf-json/ is the source ACF loads from; this script is how it is written, so field labels,
instructions and limits stay consistent. Edit field groups here, not in wp-admin.

Editor rules (docs/editability.md):
- Every label and instruction is written for the client: what it is, where it shows, how long.
- Images and videos state the recommended size and format; minimums protect the layout.
- Text that breaks the layout when too long gets a character limit.
- Fields marked pm_admin_only are hidden from non-administrators (inc/acf.php).
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
    kw.setdefault('allow_null', 1)
    return base(key, label, name or key, 'page_link', post_type=['page'], post_status=['publish'], taxonomy='',
                allow_archives=0, multiple=0, **kw)


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
    page_field('page_contact', 'Contact button goes to', width='50', admin_only=True,
               instructions='The page opened by the Contact button (header, mobile menu, page not found). Empty: the button is hidden.'),
    text('enquire_form_shortcode', 'Enquiry form shortcode', placeholder='[contact-form-7 id="123"]', admin_only=True,
         instructions='From the form plugin. Empty: a preview form is shown that does not send.'),
], [[{'param': 'options_page', 'operator': '==', 'value': 'panmotors-settings'}]])


# ---------------------------------------------------------------- Cars (pm_car, data store only)
def relationship(key, label, post_type, name=None, **kw):
    return base(key, label, name or key, 'relationship', post_type=post_type, post_status=['publish'], taxonomy='',
                filters=['search'], return_format='id', min=kw.pop('min', ''), max=kw.pop('max', ''),
                elements=['featured_image'], bidirectional=0, bidirectional_target=[], **kw)


def true_false(key, label, name=None, default=1, on='On', off='Off', **kw):
    return base(key, label, name or key, 'true_false', message='', default_value=default, ui=1,
                ui_on_text=on, ui_off_text=off, **kw)


car = group('car', 'Car', [
    image('car_image', 'Photo', name='image', required=1, min_width=1960, min_height=1102,
          instructions='Landscape, at least 1960 × 1102px. ' + IMG_FORMAT + ' Keep the car in the centre: '
                       'Featured Cars crops it tall, Latest Cars shows it 16:9.'),
    text('car_marque', 'Marque', name='marque', required=1, width='50', maxlength=20, placeholder='Porsche'),
    text('car_model', 'Model', name='model_name', required=1, width='50', maxlength=28, placeholder='911 Carrera'),
    text('car_ref', 'Reference', name='ref_no', width='33', maxlength=12, placeholder='No. 04'),
    text('car_spec', 'Detail', name='spec', width='33', maxlength=22, placeholder='Flat six'),
    text('car_note', 'Note', name='note', width='34', maxlength=26, placeholder='Kept in slate grey'),
    link('car_link', 'Link', name='link', instructions='Optional. Leave empty: the Featured Cars tile is a showcase only.'),
    text('slide_caption', 'Slider caption', name='caption', width='60', maxlength=40, placeholder='Bay four, morning light',
         instructions='Under the photo in Latest Cars.'),
    text('slide_place', 'Place', name='place', width='40', default_value='Paphos', maxlength=20,
         instructions='Right of the caption in Latest Cars.'),
    true_false('car_featured', 'Featured', name='featured', default=0, on='Yes', off='No',
               instructions='Shown in Featured Cars (in the order set under Page attributes → Order). '
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
])

b_values = block_group('values', 'Our Values', [
    heading('values_title', 'Our Values'),
    page_link('values_link', 'Cards go to', instructions='The page each card opens, e.g. About Pan Motors. Empty: the cards are not links.'),
    repeater('values', 'Values', [
        text('value_index', 'Small label', name='index', width='30', maxlength=20, placeholder='01 — Keeping'),
        text('value_title', 'Title', name='title', required=1, width='70', maxlength=24, placeholder='Kept Running'),
        textarea('value_body', 'Short text', name='body', rows=2, maxlength=140,
                 instructions='Up to 140 characters.'),
    ], min=1, max=6, button='Add value', collapsed='value_title',
        instructions='Three fit one row. Drag to reorder.'),
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
    text('cta_title', 'Heading', default_value='Come and see', maxlength=30),
    textarea('cta_text', 'Text', rows=2, maxlength=160,
             default_value='Call, write, or walk in during showroom hours. Someone from the family will answer.'),
    text('cta_label', 'Button text', width='50', default_value='Contact us', maxlength=24),
    page_link('cta_link', 'Button goes to', width='50', required=1, allow_null=0, instructions='Usually the Contact page.'),
])

GROUPS = (options, car, b_hero, b_marquee, b_featured, b_values, b_about, b_latest, b_live, b_showroom, b_enquire,
          b_faq, b_page_hero, b_cta)

if __name__ == '__main__':
    keys = []

    def walk(fs):
        for f in fs:
            keys.append(f['key'])
            walk(f.get('sub_fields', []))

    for g in GROUPS:
        walk(g['fields'])
        with open(os.path.join(OUT, g['key'] + '.json'), 'w') as fh:
            # ACF's saved format: slashes escaped, so a seed run does not rewrite the files.
            fh.write(json.dumps(g, indent=4, ensure_ascii=False).replace('/', '\\/') + '\n')
    dupes = {k for k in keys if keys.count(k) > 1}
    print('groups', len(GROUPS), 'fields', len(keys), 'dupes', dupes or 'none')
