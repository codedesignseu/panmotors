<?php
/**
 * Event page body (_design/events/event.html): photo header, facts, lede and story with the
 * buttons, the Photographs slider, previous and next event. Empty fields leave their element out.
 *
 * Args: event (row from panmotors_event()).
 *
 * @package panmotors
 */

$panmotors_event = (array) ( $args['event'] ?? array() );
if ( ! $panmotors_event ) {
	return;
}

$panmotors_events_page = panmotors_events_page();
$panmotors_tags        = array( 'time' => array( 'datetime' => true ) );

// 1. Header: the event's main image, the back link, type and status, the title (the page's H1).
get_template_part(
	'template-parts/sections/page-header',
	null,
	array(
		'style'      => 'image',
		'modifier'   => 'event',
		'eyebrow'    => $panmotors_event['type'],
		'pill'       => $panmotors_event['pill'],
		'title'      => $panmotors_event['title'],
		'image'      => $panmotors_event['main'],
		'brightness' => 60,
		'back'       => $panmotors_events_page ? array(
			'url'   => get_permalink( $panmotors_events_page ),
			'label' => panmotors_event_label( 'back', __( 'All events', 'panmotors' ) ),
		) : null,
	)
);

// 2. Facts: date, time, place, guests. An empty fact is left out and the row closes up.
$panmotors_facts = array_filter(
	array(
		panmotors_event_label( 'date', __( 'Date', 'panmotors' ) )     => panmotors_event_date_html( $panmotors_event ),
		panmotors_event_label( 'time', __( 'Time', 'panmotors' ) )     => panmotors_event_time_html( $panmotors_event ),
		panmotors_event_label( 'place', __( 'Place', 'panmotors' ) )   => esc_html( $panmotors_event['place'] ),
		panmotors_event_label( 'guests', __( 'Guests', 'panmotors' ) ) => esc_html( $panmotors_event['guests'] ),
	),
	static fn( $html ) => '' !== $html
);
?>
<div class="pm-event-facts pm-pad">
	<dl class="pm-event-facts__list" style="--pm-facts: <?php echo esc_attr( (string) count( $panmotors_facts ) ); ?>" data-rise>
		<?php foreach ( $panmotors_facts as $panmotors_label => $panmotors_value ) : ?>
			<div class="pm-event-facts__item">
				<dt class="pm-event-facts__label"><?php echo esc_html( $panmotors_label ); ?></dt>
				<dd class="pm-event-facts__value"><?php echo wp_kses( $panmotors_value, $panmotors_tags ); ?></dd>
			</div>
		<?php endforeach; ?>
	</dl>
</div>
<?php
// 3. Lede left; story and buttons right.
$panmotors_story = (string) apply_filters( 'the_content', get_the_content( null, false, $panmotors_event['id'] ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core's filter, applied to the event's story.
// The two starting paragraphs left empty print nothing.
$panmotors_story = trim( (string) preg_replace( '#<p[^>]*>(?:\s|&nbsp;|<br\s*/?>)*</p>#i', '', $panmotors_story ) );

$panmotors_buttons = array();
if ( $panmotors_event['buttons'] ) {
	if ( $panmotors_event['register']['url'] ) {
		$panmotors_buttons[] = sprintf(
			'<a class="pm-pill pm-pill--solid pm-pill--accent" href="%s">%s <span class="pm-pill__arrow" aria-hidden="true">&rarr;</span></a>',
			esc_url( $panmotors_event['register']['url'] ),
			esc_html( $panmotors_event['register']['label'] )
		);
	}
	$panmotors_phone = $panmotors_event['show_call'] ? trim( (string) ( panmotors_rows( 'phones', 'option' )[0]['number'] ?? '' ) ) : '';
	if ( $panmotors_phone ) {
		$panmotors_buttons[] = sprintf(
			'<a class="pm-pill pm-event-body__pill" href="tel:%s">%s %s</a>',
			esc_attr( panmotors_tel( $panmotors_phone ) ),
			esc_html( panmotors_event_label( 'call', __( 'Call', 'panmotors' ) ) ),
			esc_html( $panmotors_phone )
		);
	}
	$panmotors_buttons[] = sprintf(
		'<a class="pm-pill pm-event-body__pill" href="%s" download rel="nofollow">%s</a>',
		esc_url( panmotors_event_ics_url( $panmotors_event ) ),
		esc_html( panmotors_event_label( 'calendar', __( 'Add to calendar', 'panmotors' ) ) )
	);
}

if ( $panmotors_event['lede'] || $panmotors_story || $panmotors_buttons ) :
	?>
	<div class="pm-event-body pm-pad">
		<div class="pm-event-body__grid">
			<?php if ( $panmotors_event['lede'] ) : ?>
				<div class="pm-event-body__lede" data-rise>
					<p><?php echo esc_html( $panmotors_event['lede'] ); ?></p>
				</div>
			<?php endif; ?>
			<?php if ( $panmotors_story || $panmotors_buttons ) : ?>
				<div class="pm-event-body__main" data-rise>
					<?php if ( $panmotors_story ) : ?>
						<div class="pm-event-body__story">
							<?php echo $panmotors_story; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The block editor's content, through the_content. ?>
						</div>
					<?php endif; ?>
					<?php if ( $panmotors_buttons ) : ?>
						<div class="pm-event-body__actions">
							<?php echo implode( '', $panmotors_buttons ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
endif;

// 4. Photographs.
get_template_part(
	'template-parts/events/photos',
	null,
	array(
		'photos' => $panmotors_event['photos'],
		'title'  => panmotors_event_label( 'photos', __( 'Photographs', 'panmotors' ) ),
		'event'  => html_entity_decode( $panmotors_event['title'], ENT_QUOTES, 'UTF-8' ),
	)
);

// 5. Previous and next event, in date order. Not when this is the only event.
$panmotors_near = panmotors_event_neighbours( $panmotors_event['id'] );
if ( $panmotors_near['prev'] && $panmotors_near['next'] ) :
	?>
	<nav class="pm-event-nav pm-pad" aria-label="<?php esc_attr_e( 'More events', 'panmotors' ); ?>">
		<div class="pm-event-nav__grid">
			<?php
			foreach ( array( 'prev', 'next' ) as $panmotors_dir ) :
				$panmotors_other = $panmotors_near[ $panmotors_dir ];
				$panmotors_label = 'prev' === $panmotors_dir ? panmotors_event_label( 'prev', __( 'Previous event', 'panmotors' ) ) : panmotors_event_label( 'next', __( 'Next event', 'panmotors' ) );
				?>
				<a class="pm-event-nav__card pm-event-nav__card--<?php echo esc_attr( $panmotors_dir ); ?>" href="<?php echo esc_url( $panmotors_other['url'] ); ?>" rel="<?php echo esc_attr( $panmotors_dir ); ?>" data-zoom>
					<?php
					if ( $panmotors_other['main'] ) {
						echo wp_get_attachment_image(
							$panmotors_other['main'],
							'pm-wide',
							false,
							array(
								'class'   => 'pm-event-nav__img',
								'alt'     => '',
								'sizes'   => '(max-width: 880px) calc(100vw - 40px), calc(50vw - 47px)',
								'loading' => 'lazy',
							)
						);
					}
					?>
					<span class="pm-event-nav__copy">
						<span class="pm-event-nav__label"><?php echo 'prev' === $panmotors_dir ? '<span aria-hidden="true">&larr;</span> ' : ''; ?><?php echo esc_html( $panmotors_label ); ?><?php echo 'next' === $panmotors_dir ? ' <span aria-hidden="true">&rarr;</span>' : ''; ?></span>
						<span class="pm-event-nav__title"><?php echo esc_html( $panmotors_other['title'] ); ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</nav>
	<?php
endif;
