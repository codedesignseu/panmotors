<?php
/**
 * Latest Cars section (theme-map 4.7). Slides from the Latest Cars page.
 *
 * Home: horizontal slider with counter, prev/next and pointer drag (slider-drag.js).
 * All slides are in the HTML; JS only moves the track.
 *
 * Args:
 * - page_id (int)    Latest Cars page.
 * - context (string) 'home' (built) or 'page' (grid, after the page is designed).
 *
 * @package panmotors
 */

$panmotors_page_id = (int) ( $args['page_id'] ?? 0 );
$panmotors_slides  = array_values(
	array_filter(
		$panmotors_page_id ? panmotors_rows( 'latest_cars', $panmotors_page_id ) : array(),
		static fn( $row ) => ! empty( $row['image'] )
	)
);

if ( ! $panmotors_slides ) {
	return;
}

$panmotors_eyebrow = panmotors_field( 'page_eyebrow', $panmotors_page_id );
$panmotors_title   = panmotors_field( 'home_latest_title', (int) get_option( 'page_on_front' ), get_the_title( $panmotors_page_id ) );
$panmotors_total   = count( $panmotors_slides );
?>
<section id="gallery" class="pm-latest" aria-labelledby="latest-title" data-slider>
	<div class="pm-section-head pm-latest__head pm-pad" data-rise>
		<div>
			<?php if ( $panmotors_eyebrow ) : ?>
				<p class="pm-eyebrow"><?php echo esc_html( $panmotors_eyebrow ); ?></p>
			<?php endif; ?>
			<h2 class="pm-title" id="latest-title"><?php echo esc_html( $panmotors_title ); ?></h2>
		</div>

		<?php if ( $panmotors_total > 1 ) : ?>
			<div class="pm-latest__controls">
				<p class="pm-latest__counter" aria-live="polite">
					<span class="screen-reader-text"><?php esc_html_e( 'Car', 'panmotors' ); ?></span>
					<span data-slider-count><?php echo esc_html( sprintf( '%02d / %02d', 1, $panmotors_total ) ); ?></span>
				</p>
				<button class="pm-round" type="button" aria-controls="latest-track" aria-label="<?php esc_attr_e( 'Previous car', 'panmotors' ); ?>" data-slider-prev>&larr;</button>
				<button class="pm-round" type="button" aria-controls="latest-track" aria-label="<?php esc_attr_e( 'Next car', 'panmotors' ); ?>" data-slider-next>&rarr;</button>
			</div>
		<?php endif; ?>
	</div>

	<div class="pm-latest__viewport" role="region" aria-roledescription="<?php esc_attr_e( 'carousel', 'panmotors' ); ?>" aria-label="<?php echo esc_attr( $panmotors_title ); ?>" tabindex="0" data-slider-viewport>
		<div class="pm-latest__track pm-pad" id="latest-track" data-slider-track>
			<?php foreach ( $panmotors_slides as $panmotors_i => $panmotors_slide ) : ?>
				<figure class="pm-slide" aria-roledescription="<?php esc_attr_e( 'slide', 'panmotors' ); ?>" aria-label="<?php echo esc_attr( sprintf( '%d / %d', $panmotors_i + 1, $panmotors_total ) ); ?>">
					<div class="pm-media pm-slide__media" data-zoom>
						<?php
						echo wp_get_attachment_image(
							(int) $panmotors_slide['image'],
							'pm-wide',
							false,
							array(
								'sizes'     => '(max-width: 880px) 84vw, min(68vw, 980px)',
								'loading'   => 0 === $panmotors_i ? 'eager' : 'lazy',
								'draggable' => 'false',
							)
						);
						?>
					</div>
					<figcaption class="pm-slide__caption">
						<span><?php echo esc_html( $panmotors_slide['caption'] ?? '' ); ?></span>
						<span><?php echo esc_html( $panmotors_slide['place'] ?? '' ); ?></span>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
