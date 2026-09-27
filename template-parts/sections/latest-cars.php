<?php
/**
 * Latest Cars section (theme-map 4.7). The newest cars from the Cars post type (D11).
 *
 * Horizontal slider with counter, prev/next and pointer drag (slider-drag.js).
 * All slides are in the HTML; JS only moves the track.
 *
 * Args (from blocks/latest-cars/render.php):
 * - eyebrow (string)  Small red line.
 * - title   (string)  Heading.
 * - slides  (array[]) Rows from panmotors_cars(): image, caption, place.
 *
 * @package panmotors
 */

$panmotors_slides = array_values(
	array_filter(
		(array) ( $args['slides'] ?? array() ),
		static fn( $row ) => ! empty( $row['image'] )
	)
);

if ( ! $panmotors_slides ) {
	return;
}

$panmotors_eyebrow = (string) ( $args['eyebrow'] ?? '' );
$panmotors_title   = (string) ( $args['title'] ?? '' );
$panmotors_total   = count( $panmotors_slides );
?>
<section id="gallery" class="pm-latest"<?php echo $panmotors_title ? ' aria-labelledby="latest-title"' : ''; ?> data-slider>
	<div class="pm-section-head pm-latest__head pm-pad" data-rise>
		<div>
			<?php if ( $panmotors_eyebrow ) : ?>
				<p class="pm-eyebrow"><?php echo esc_html( $panmotors_eyebrow ); ?></p>
			<?php endif; ?>
			<?php if ( $panmotors_title ) : ?>
				<h2 class="pm-title" id="latest-title"><?php echo esc_html( $panmotors_title ); ?></h2>
			<?php endif; ?>
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

	<div class="pm-latest__viewport" role="region" aria-roledescription="<?php esc_attr_e( 'carousel', 'panmotors' ); ?>" aria-label="<?php echo esc_attr( $panmotors_title ? $panmotors_title : __( 'Latest cars', 'panmotors' ) ); ?>" tabindex="0" data-slider-viewport>
		<div class="pm-latest__track pm-pad" id="latest-track" data-slider-track>
			<?php foreach ( $panmotors_slides as $panmotors_i => $panmotors_slide ) : ?>
				<figure class="pm-slide" aria-roledescription="<?php esc_attr_e( 'slide', 'panmotors' ); ?>" aria-label="<?php /* translators: 1: slide number, 2: number of slides. */ echo esc_attr( sprintf( __( '%1$d / %2$d', 'panmotors' ), $panmotors_i + 1, $panmotors_total ) ); ?>">
					<div class="pm-media pm-slide__media" data-zoom>
						<?php
						echo wp_get_attachment_image(
							(int) $panmotors_slide['image'],
							'pm-wide',
							false,
							array(
								'sizes'     => '(max-width: 880px) 84vw, min(68vw, 980px)',
								'loading'   => 'lazy', // Sliders follow a hero or page header: below the fold.
								'draggable' => 'false',
							)
						);
						?>
					</div>
					<?php
					$panmotors_caption = trim( (string) ( $panmotors_slide['caption'] ?? '' ) );
					$panmotors_place   = trim( (string) ( $panmotors_slide['place'] ?? '' ) );
					?>
					<?php if ( $panmotors_caption || $panmotors_place ) : ?>
						<figcaption class="pm-slide__caption">
							<span><?php echo esc_html( $panmotors_caption ); ?></span>
							<?php if ( $panmotors_place ) : ?>
								<span><?php echo esc_html( $panmotors_place ); ?></span>
							<?php endif; ?>
						</figcaption>
					<?php endif; ?>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
