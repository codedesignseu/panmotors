<?php
/**
 * Featured Cars section (theme-map 4.4). Cars from the Cars post type (D11).
 *
 * Args (from blocks/featured-cars/render.php):
 * - title (string)  Heading.
 * - intro (string)  Short intro.
 * - more  (string)  Label of the outlined button under the intro (v2), or ''.
 * - link  (string)  Page the button opens, or '' (no button).
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
$panmotors_more  = trim( (string) ( $args['more'] ?? '' ) );
$panmotors_link  = (string) ( $args['link'] ?? '' );
$panmotors_more  = $panmotors_link ? $panmotors_more : '';
?>
<section id="floor" class="pm-featured pm-pad" aria-labelledby="featured-title">
	<div class="pm-section-head" data-rise>
		<h2 class="pm-title" id="featured-title"><?php echo esc_html( $panmotors_title ); ?></h2>
		<?php if ( $panmotors_more ) : ?>
			<div class="pm-section-head__aside">
				<?php if ( $panmotors_intro ) : ?>
					<p class="pm-lede"><?php echo esc_html( $panmotors_intro ); ?></p>
				<?php endif; ?>
				<a class="pm-pill pm-pill--small" href="<?php echo esc_url( $panmotors_link ); ?>"><?php echo esc_html( $panmotors_more ); ?> <span aria-hidden="true">&rarr;</span></a>
			</div>
		<?php elseif ( $panmotors_intro ) : ?>
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
