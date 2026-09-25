<?php
/**
 * Featured Cars section (theme-map 4.4). Cars from the Cars post type (D11).
 *
 * Args (from blocks/featured-cars/render.php):
 * - title (string)  Heading.
 * - intro (string)  Short intro.
 * - cars  (array[]) Rows from panmotors_cars(), in display order.
 *
 * @package panmotors
 */

$panmotors_cars = (array) ( $args['cars'] ?? array() );

if ( ! $panmotors_cars ) {
	return;
}

$panmotors_title = (string) ( $args['title'] ?? '' );
$panmotors_intro = (string) ( $args['intro'] ?? '' );
?>
<section id="floor" class="pm-featured pm-pad" aria-labelledby="featured-title">
	<div class="pm-section-head" data-rise>
		<h2 class="pm-title" id="featured-title"><?php echo esc_html( $panmotors_title ); ?></h2>
		<?php if ( $panmotors_intro ) : ?>
			<p class="pm-lede"><?php echo esc_html( $panmotors_intro ); ?></p>
		<?php endif; ?>
	</div>

	<div class="pm-featured__tiles">
		<?php
		foreach ( $panmotors_cars as $panmotors_car ) {
			get_template_part( 'template-parts/components/car-tile', null, array( 'car' => $panmotors_car ) );
		}
		?>
	</div>
</section>
