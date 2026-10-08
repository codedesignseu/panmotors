<?php
/**
 * Events intro section (Home, before Pan Motors Live; _design/home-events/events-intro.html).
 *
 * A hairline, then the head: small red line and heading on the left, intro and outlined "All
 * events" button on the right. Under it one card per event, each card one link to the event page:
 * the main image (4:3) with the type and, when the event needs it, a Postponed or Sold out pill;
 * then the start day (zero-padded), "Month Year · Place" and the title (H3). No upcoming events:
 * the head and the empty-state text, no grid.
 *
 * Args (from blocks/events-intro/render.php):
 * - eyebrow (string)  Small red line.
 * - title   (string)  Heading (H2).
 * - intro   (string)  Short intro.
 * - label   (string)  Button text, or '' (no button).
 * - link    (string)  Button URL, or '' (no button).
 * - empty   (string)  Text shown when no event is coming up.
 * - events  (array[]) Rows from panmotors_event(), soonest first, cancelled ones left out.
 *
 * @package panmotors
 */

$panmotors_events  = (array) ( $args['events'] ?? array() );
$panmotors_eyebrow = trim( (string) ( $args['eyebrow'] ?? '' ) );
$panmotors_title   = trim( (string) ( $args['title'] ?? '' ) );
$panmotors_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$panmotors_link    = (string) ( $args['link'] ?? '' );
$panmotors_label   = $panmotors_link ? trim( (string) ( $args['label'] ?? '' ) ) : '';
$panmotors_empty   = trim( (string) ( $args['empty'] ?? '' ) );

if ( ! $panmotors_events && ! $panmotors_empty ) {
	return;
}

// Drawn width of one card: auto-fit columns of at least 320px with 14px gaps inside the page
// padding, so one, two or three columns depending on the width and the number of events.
$panmotors_cols  = min( 3, max( 1, count( $panmotors_events ) ) );
$panmotors_sizes = array(
	1 => 'calc(100vw - 80px)',
	2 => '(max-width: 747px) calc(100vw - 40px), calc(50vw - 47px)',
	3 => '(max-width: 747px) calc(100vw - 40px), (max-width: 1067px) calc(50vw - 47px), calc(33.33vw - 36px)',
);
?>
<section id="events" class="pm-ei pm-pad"<?php echo $panmotors_title ? ' aria-labelledby="ei-title"' : ''; ?>>
	<div class="pm-section-head pm-ei__head" data-rise>
		<?php if ( $panmotors_eyebrow || $panmotors_title ) : ?>
			<div>
				<?php if ( $panmotors_eyebrow ) : ?>
					<p class="pm-eyebrow"><?php echo esc_html( $panmotors_eyebrow ); ?></p>
				<?php endif; ?>
				<?php if ( $panmotors_title ) : ?>
					<h2 class="pm-title" id="ei-title"><?php echo esc_html( $panmotors_title ); ?></h2>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( $panmotors_intro || $panmotors_label ) : ?>
			<div class="pm-section-head__aside">
				<?php if ( $panmotors_intro ) : ?>
					<p class="pm-lede pm-ei__intro"><?php echo esc_html( $panmotors_intro ); ?></p>
				<?php endif; ?>
				<?php if ( $panmotors_label ) : ?>
					<a class="pm-pill pm-pill--small" href="<?php echo esc_url( $panmotors_link ); ?>"><?php echo esc_html( $panmotors_label ); ?> <span aria-hidden="true">&rarr;</span></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( ! $panmotors_events ) : ?>
		<p class="pm-ei__empty" data-rise><?php echo esc_html( $panmotors_empty ); ?></p>
	<?php else : ?>
		<div class="pm-ei__grid">
			<?php
			foreach ( $panmotors_events as $panmotors_event ) :
				$panmotors_start = $panmotors_event['start'];
				$panmotors_ts    = $panmotors_start->getTimestamp();
				$panmotors_tz    = $panmotors_start->getTimezone();
				$panmotors_image = $panmotors_event['main'] ? $panmotors_event['main'] : ( $panmotors_event['images'][0] ?? 0 );
				$panmotors_note  = in_array( $panmotors_event['status'], array( 'postponed', 'soldout' ), true ) ? $panmotors_event['pill'] : '';
				$panmotors_meta  = implode( ' · ', array_filter( array( wp_date( 'F Y', $panmotors_ts, $panmotors_tz ), $panmotors_event['place'] ) ) );
				$panmotors_name  = implode( ', ', array_filter( array( $panmotors_event['title'], wp_date( 'j F Y', $panmotors_ts, $panmotors_tz ), $panmotors_note ) ) );
				?>
				<a class="pm-ei__card" href="<?php echo esc_url( $panmotors_event['url'] ); ?>" aria-label="<?php echo esc_attr( $panmotors_name ); ?>" data-rise>
					<div class="pm-ei__media">
						<?php
						if ( $panmotors_image ) {
							echo wp_get_attachment_image(
								(int) $panmotors_image,
								'pm-tile',
								false,
								array(
									'sizes'   => $panmotors_sizes[ $panmotors_cols ],
									'loading' => 'lazy',
								)
							);
						}
						?>
						<?php if ( $panmotors_event['type'] || $panmotors_note ) : ?>
							<span class="pm-ei__pills">
								<?php if ( $panmotors_event['type'] ) : ?>
									<span class="pm-ei__pill"><?php echo esc_html( $panmotors_event['type'] ); ?></span>
								<?php endif; ?>
								<?php if ( $panmotors_note ) : ?>
									<span class="pm-ei__pill pm-ei__pill--status"><?php echo esc_html( $panmotors_note ); ?></span>
								<?php endif; ?>
							</span>
						<?php endif; ?>
					</div>
					<div class="pm-ei__foot">
						<time class="pm-ei__day" datetime="<?php echo esc_attr( $panmotors_start->format( 'Y-m-d' ) ); ?>"><?php echo esc_html( wp_date( 'd', $panmotors_ts, $panmotors_tz ) ); ?></time>
						<div class="pm-ei__copy">
							<p class="pm-ei__meta"><?php echo esc_html( $panmotors_meta ); ?></p>
							<h3 class="pm-ei__title"><?php echo esc_html( $panmotors_event['title'] ); ?></h3>
						</div>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
