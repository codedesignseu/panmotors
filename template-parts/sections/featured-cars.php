<?php
/**
 * Featured Cars section (theme-map 4.4). Content from the Featured Cars page.
 *
 * Args:
 * - page_id (int)    Featured Cars page.
 * - context (string) 'home' (built) or 'page' (after the inner page is designed).
 * - limit   (int)    Max tiles. Home shows 4.
 *
 * @package panmotors
 */

$panmotors_page_id = (int) ( $args['page_id'] ?? 0 );
$panmotors_limit   = (int) ( $args['limit'] ?? 0 );
$panmotors_cars    = $panmotors_page_id ? panmotors_rows( 'featured_cars', $panmotors_page_id ) : array();

if ( $panmotors_limit ) {
	$panmotors_cars = array_slice( $panmotors_cars, 0, $panmotors_limit );
}

if ( ! $panmotors_cars ) {
	return;
}

$panmotors_title = panmotors_field( 'home_featured_title', (int) get_option( 'page_on_front' ), get_the_title( $panmotors_page_id ) );
$panmotors_intro = panmotors_field( 'page_intro', $panmotors_page_id );
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
