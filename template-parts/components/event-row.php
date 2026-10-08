<?php
/**
 * One event on the Events page (_design/events/events.html) and in Upcoming events on Home: two
 * columns between hairlines. Left: the day in Bodoni beside the month and year, the type in red,
 * the title (H2, or H3 under a section heading; a link to the event), the summary, the place and a
 * "View event" pill. Right: up to three photos (main image, then the photographs); the hovered
 * photo grows. Empty fields leave their element out.
 *
 * Args: event (row from panmotors_event()), view (button label), heading ('h2' by default; 'h3' under
 * a section heading, as on Home).
 *
 * @package panmotors
 */

$panmotors_event = (array) ( $args['event'] ?? array() );
if ( ! $panmotors_event ) {
	return;
}

$panmotors_start = $panmotors_event['start'];
$panmotors_view  = trim( (string) ( $args['view'] ?? '' ) );
$panmotors_h     = 'h3' === ( $args['heading'] ?? '' ) ? 'h3' : 'h2';
$panmotors_id    = 'event-' . (int) $panmotors_event['id'];
$panmotors_note  = in_array( $panmotors_event['status'], array( 'cancelled', 'postponed' ), true ) || ( 'soldout' === $panmotors_event['status'] && ! $panmotors_event['past'] ) ? $panmotors_event['pill'] : '';
?>
<article class="pm-event-row" aria-labelledby="<?php echo esc_attr( $panmotors_id ); ?>" data-rise>
	<div class="pm-event-row__copy">
		<p class="pm-event-row__date">
			<time datetime="<?php echo esc_attr( $panmotors_start->format( 'Y-m-d' ) ); ?>">
				<span class="pm-event-row__day"><?php echo esc_html( wp_date( 'd', $panmotors_start->getTimestamp(), $panmotors_start->getTimezone() ) ); ?></span>
				<span class="pm-event-row__month"><?php echo esc_html( wp_date( 'F', $panmotors_start->getTimestamp(), $panmotors_start->getTimezone() ) ); ?><br><?php echo esc_html( wp_date( 'Y', $panmotors_start->getTimestamp(), $panmotors_start->getTimezone() ) ); ?></span>
			</time>
		</p>
		<?php if ( $panmotors_event['type'] || $panmotors_note ) : ?>
			<p class="pm-event-row__type"><?php echo esc_html( implode( ' — ', array_filter( array( $panmotors_event['type'], $panmotors_note ) ) ) ); ?></p>
		<?php endif; ?>
		<<?php echo $panmotors_h; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- h2 or h3. ?> class="pm-event-row__title" id="<?php echo esc_attr( $panmotors_id ); ?>"><a href="<?php echo esc_url( $panmotors_event['url'] ); ?>"><?php echo esc_html( $panmotors_event['title'] ); ?></a></<?php echo $panmotors_h; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- h2 or h3. ?>>
		<?php if ( $panmotors_event['summary'] ) : ?>
			<p class="pm-event-row__summary"><?php echo esc_html( $panmotors_event['summary'] ); ?></p>
		<?php endif; ?>
		<?php if ( $panmotors_event['place'] || $panmotors_view ) : ?>
			<div class="pm-event-row__foot">
				<?php if ( $panmotors_event['place'] ) : ?>
					<span class="pm-event-row__place"><?php echo esc_html( $panmotors_event['place'] ); ?></span>
				<?php endif; ?>
				<?php if ( $panmotors_view ) : ?>
					<a class="pm-pill pm-pill--small pm-event-row__view" href="<?php echo esc_url( $panmotors_event['url'] ); ?>"><?php echo esc_html( $panmotors_view ); ?><span class="screen-reader-text">: <?php echo esc_html( $panmotors_event['title'] ); ?></span> <span aria-hidden="true">&rarr;</span></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $panmotors_event['images'] ) : ?>
		<div class="pm-event-row__pics">
			<?php foreach ( $panmotors_event['images'] as $panmotors_i => $panmotors_image ) : ?>
				<div class="pm-media pm-event-row__pic<?php echo 0 === $panmotors_i ? ' pm-event-row__pic--first' : ''; ?>" data-zoom>
					<?php
					echo wp_get_attachment_image(
						$panmotors_image,
						'pm-tile',
						false,
						array(
							// A landscape photo filling a tall slot: its drawn width follows the slot's height
							// (clamp(320px, 34vw, 500px), 72vw on phones) at 3:2.
							'sizes'   => '(max-width: 880px) 108vw, min(51vw, 750px)',
							'loading' => 'lazy',
						)
					);
					?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</article>
