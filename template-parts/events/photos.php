<?php
/**
 * Event Photographs (_design/events/event.html): H2 and counter, 56px arrows, photos 3:2 in a
 * moving track. Every photo, caption and counter is in this HTML; event-photos.js only moves the
 * track and switches the current counter. Drag or swipe more than 50px, the arrows and the arrow
 * keys (while the photos have focus) change the photo. Nothing prints without photos; one photo
 * shows without arrows or counter.
 *
 * Args: photos (attachment IDs), title, event (the event's title, for the photos' label).
 *
 * @package panmotors
 */

$panmotors_photos = array_values( array_filter( array_map( 'intval', (array) ( $args['photos'] ?? array() ) ) ) );
if ( ! $panmotors_photos ) {
	return;
}

$panmotors_title = trim( (string) ( $args['title'] ?? '' ) );
$panmotors_event = trim( (string) ( $args['event'] ?? '' ) );
$panmotors_total = count( $panmotors_photos );
$panmotors_multi = $panmotors_total > 1;
$panmotors_pad   = static fn( $n ) => str_pad( (string) $n, 2, '0', STR_PAD_LEFT );
if ( $panmotors_multi ) {
	panmotors_use_module( 'event-photos' );
}
?>
<section class="pm-event-photos"<?php echo $panmotors_title ? ' aria-labelledby="event-photos-title"' : ' aria-label="' . esc_attr__( 'Photographs', 'panmotors' ) . '"'; ?><?php echo $panmotors_multi ? ' data-event-photos' : ''; ?>>
	<div class="pm-event-photos__head pm-pad" data-rise>
		<?php if ( $panmotors_title ) : ?>
			<h2 class="pm-event-photos__title" id="event-photos-title"><?php echo esc_html( $panmotors_title ); ?></h2>
		<?php endif; ?>
		<?php if ( $panmotors_multi ) : ?>
			<div class="pm-event-photos__controls">
				<p class="pm-event-photos__count" aria-live="polite">
					<?php foreach ( $panmotors_photos as $panmotors_i => $panmotors_id ) : ?>
						<span data-photos-part="<?php echo esc_attr( (string) $panmotors_i ); ?>"<?php echo 0 === $panmotors_i ? ' class="is-current"' : ' hidden'; ?>><span class="screen-reader-text"><?php esc_html_e( 'Photograph', 'panmotors' ); ?> </span><?php echo esc_html( $panmotors_pad( $panmotors_i + 1 ) . ' / ' . $panmotors_pad( $panmotors_total ) ); ?></span>
					<?php endforeach; ?>
				</p>
				<button class="pm-round" type="button" aria-controls="pm-event-track" aria-label="<?php esc_attr_e( 'Previous photograph', 'panmotors' ); ?>" data-photos-prev><span aria-hidden="true">&larr;</span></button>
				<button class="pm-round" type="button" aria-controls="pm-event-track" aria-label="<?php esc_attr_e( 'Next photograph', 'panmotors' ); ?>" data-photos-next><span aria-hidden="true">&rarr;</span></button>
			</div>
		<?php endif; ?>
	</div>

	<div class="pm-event-photos__viewport"<?php echo $panmotors_multi ? ' role="region" aria-roledescription="' . esc_attr__( 'carousel', 'panmotors' ) . '" aria-label="' . esc_attr( $panmotors_event ? sprintf( /* translators: %s: event title. */ __( 'Photographs of %s', 'panmotors' ), $panmotors_event ) : __( 'Photographs', 'panmotors' ) ) . '" tabindex="0" data-photos-viewport' : ''; ?>>
		<div class="pm-event-photos__track pm-pad" id="pm-event-track" data-photos-track>
			<?php foreach ( $panmotors_photos as $panmotors_i => $panmotors_id ) : ?>
				<?php $panmotors_caption = trim( (string) wp_get_attachment_caption( $panmotors_id ) ); ?>
				<figure class="pm-event-photos__slide"<?php echo $panmotors_multi && $panmotors_i ? ' aria-hidden="true"' : ''; ?>>
					<div class="pm-media pm-event-photos__frame" data-zoom>
						<?php
						echo wp_get_attachment_image(
							$panmotors_id,
							'pm-wide',
							false,
							array(
								'sizes'     => '(max-width: 880px) 84vw, min(62vw, 900px)',
								'loading'   => 'lazy',
								'draggable' => 'false',
							)
						);
						?>
					</div>
					<?php if ( $panmotors_caption ) : ?>
						<figcaption class="pm-event-photos__caption"><?php echo esc_html( $panmotors_caption ); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
