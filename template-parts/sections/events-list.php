<?php
/**
 * Events list (Events page, _design/events/events.html): Upcoming and Past tabs with their counts,
 * then one row per event (template-parts/components/event-row.php).
 *
 * - The tabs are built from the events: a group without events has no tab. Real buttons with
 *   aria-pressed, hidden until events-list.js runs; without JS both groups show, one after the
 *   other. Each group is a <section> labelled by its tab name; the event titles are the H2s.
 * - The outer element is a <section> without a name (not a landmark), so the block runs full
 *   width in the page (.pm-blocks keeps other elements in a text column).
 * - No events at all: the empty-state text and button instead.
 *
 * Args (from blocks/events-list/render.php): upcoming, past (rows from panmotors_events()), intro,
 * tab_up, tab_past, view (row button label), empty_text, empty_label, empty_link, preview.
 *
 * @package panmotors
 */

$panmotors_groups = array_filter(
	array(
		'upcoming' => array(
			'label'  => trim( (string) ( $args['tab_up'] ?? '' ) ) ? trim( $args['tab_up'] ) : __( 'Upcoming', 'panmotors' ),
			'events' => (array) ( $args['upcoming'] ?? array() ),
		),
		'past'     => array(
			'label'  => trim( (string) ( $args['tab_past'] ?? '' ) ) ? trim( $args['tab_past'] ) : __( 'Past', 'panmotors' ),
			'events' => (array) ( $args['past'] ?? array() ),
		),
	),
	static fn( $group ) => (bool) $group['events']
);
$panmotors_intro  = trim( (string) ( $args['intro'] ?? '' ) );
$panmotors_view   = trim( (string) ( $args['view'] ?? '' ) );

if ( ! $panmotors_groups ) :
	$panmotors_text  = trim( (string) ( $args['empty_text'] ?? '' ) );
	$panmotors_label = trim( (string) ( $args['empty_label'] ?? '' ) );
	$panmotors_link  = (string) ( $args['empty_link'] ?? '' );
	if ( ! $panmotors_text && ! ( $panmotors_label && $panmotors_link ) ) {
		return;
	}
	?>
	<section class="pm-events pm-events--empty pm-pad">
		<div class="pm-events__empty" data-rise>
			<?php if ( $panmotors_text ) : ?>
				<p class="pm-events__empty-text"><?php echo esc_html( $panmotors_text ); ?></p>
			<?php endif; ?>
			<?php if ( $panmotors_label && $panmotors_link ) : ?>
				<a class="pm-pill" href="<?php echo esc_url( $panmotors_link ); ?>"><?php echo esc_html( $panmotors_label ); ?> <span aria-hidden="true">&rarr;</span></a>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return;
endif;

$panmotors_first = array_key_first( $panmotors_groups );
?>
<section class="pm-events pm-pad" data-events>
	<?php if ( $panmotors_intro ) : ?>
		<p class="pm-events__intro" data-hero-in="3"><?php echo esc_html( $panmotors_intro ); ?></p>
	<?php endif; ?>

	<div class="pm-events__tabs" role="group" aria-label="<?php esc_attr_e( 'Show events', 'panmotors' ); ?>" data-events-tabs<?php echo empty( $args['preview'] ) ? ' hidden' : ''; ?>>
		<?php foreach ( $panmotors_groups as $panmotors_key => $panmotors_group ) : ?>
			<button class="pm-events__tab" type="button" aria-pressed="<?php echo $panmotors_key === $panmotors_first ? 'true' : 'false'; ?>" aria-controls="pm-events-<?php echo esc_attr( $panmotors_key ); ?>" data-events-tab="<?php echo esc_attr( $panmotors_key ); ?>"><?php echo esc_html( $panmotors_group['label'] ); ?> <span class="pm-events__count"><?php echo esc_html( (string) count( $panmotors_group['events'] ) ); ?></span></button>
		<?php endforeach; ?>
	</div>

	<div class="pm-events__list" data-events-list>
		<?php foreach ( $panmotors_groups as $panmotors_key => $panmotors_group ) : ?>
			<section class="pm-events__group" id="pm-events-<?php echo esc_attr( $panmotors_key ); ?>" aria-label="<?php echo esc_attr( $panmotors_group['label'] ); ?>" data-events-group="<?php echo esc_attr( $panmotors_key ); ?>"<?php echo ! empty( $args['preview'] ) && $panmotors_key !== $panmotors_first ? ' hidden' : ''; ?>>
				<?php
				foreach ( $panmotors_group['events'] as $panmotors_event ) {
					get_template_part(
						'template-parts/components/event-row',
						null,
						array(
							'event' => $panmotors_event,
							'view'  => $panmotors_view,
						)
					);
				}
				?>
			</section>
		<?php endforeach; ?>
	</div>
</section>
