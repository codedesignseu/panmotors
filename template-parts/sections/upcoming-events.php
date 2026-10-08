<?php
/**
 * Upcoming events section (Home, after Latest Cars; an addition to the design agreed on
 * 8 Oct 2026, CLAUDE.md). The section head of Featured Cars (small red line, heading, intro and an
 * outlined button to the Events page), then the event rows of the Events page
 * (template-parts/components/event-row.php) with the titles as H3s. Hidden without upcoming events.
 *
 * Args (from blocks/upcoming-events/render.php):
 * - eyebrow (string)  Small red line.
 * - title   (string)  Heading.
 * - intro   (string)  Short intro.
 * - more    (string)  Label of the outlined button, or ''.
 * - link    (string)  Page the button opens, or '' (no button).
 * - view    (string)  Label of the button on each event.
 * - events  (array[]) Rows from panmotors_event(), soonest first.
 *
 * @package panmotors
 */

$panmotors_events = (array) ( $args['events'] ?? array() );

if ( ! $panmotors_events ) {
	return;
}

$panmotors_eyebrow = trim( (string) ( $args['eyebrow'] ?? '' ) );
$panmotors_title   = trim( (string) ( $args['title'] ?? '' ) );
$panmotors_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$panmotors_link    = (string) ( $args['link'] ?? '' );
$panmotors_more    = $panmotors_link ? trim( (string) ( $args['more'] ?? '' ) ) : '';
$panmotors_view    = trim( (string) ( $args['view'] ?? '' ) );
?>
<section id="events" class="pm-upcoming pm-pad"<?php echo $panmotors_title ? ' aria-labelledby="upcoming-title"' : ''; ?>>
	<?php if ( $panmotors_eyebrow || $panmotors_title || $panmotors_intro || $panmotors_more ) : ?>
		<div class="pm-section-head" data-rise>
			<div>
				<?php if ( $panmotors_eyebrow ) : ?>
					<p class="pm-eyebrow"><?php echo esc_html( $panmotors_eyebrow ); ?></p>
				<?php endif; ?>
				<?php if ( $panmotors_title ) : ?>
					<h2 class="pm-title" id="upcoming-title"><?php echo esc_html( $panmotors_title ); ?></h2>
				<?php endif; ?>
			</div>
			<?php if ( $panmotors_intro || $panmotors_more ) : ?>
				<div class="pm-section-head__aside">
					<?php if ( $panmotors_intro ) : ?>
						<p class="pm-lede"><?php echo esc_html( $panmotors_intro ); ?></p>
					<?php endif; ?>
					<?php if ( $panmotors_more ) : ?>
						<a class="pm-pill pm-pill--small" href="<?php echo esc_url( $panmotors_link ); ?>"><?php echo esc_html( $panmotors_more ); ?> <span aria-hidden="true">&rarr;</span></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="pm-upcoming__list">
		<?php
		foreach ( $panmotors_events as $panmotors_event ) {
			get_template_part(
				'template-parts/components/event-row',
				null,
				array(
					'event'   => $panmotors_event,
					'view'    => $panmotors_view,
					'heading' => $panmotors_title ? 'h3' : 'h2',
				)
			);
		}
		?>
	</div>
</section>
