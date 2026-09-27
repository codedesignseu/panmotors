<?php
/**
 * Photo slider (Showroom "Inside", inner-pages §7.2, _design/v2/showroom.html).
 *
 * A paper panel with a rounded top that overlaps the page header by 24px; behind it a blurred copy
 * of the current photo under a radial wash. Every photo, its blurred copy, its caption and its
 * counter are in this HTML; photo-slider.js only switches which one is current (fade out, swap,
 * fade in), so it never writes images or text. Drag or swipe more than 50px, the arrows, the dots
 * and the arrow keys (while the slider has focus) change the photo.
 *
 * Args (from blocks/photo-slider/render.php): title, hint, photos (attachment IDs).
 *
 * @package panmotors
 */

$panmotors_photos = array_values( array_filter( array_map( 'intval', (array) ( $args['photos'] ?? array() ) ) ) );

if ( ! $panmotors_photos ) {
	return;
}

$panmotors_title = trim( (string) ( $args['title'] ?? '' ) );
$panmotors_hint  = trim( (string) ( $args['hint'] ?? '' ) );
$panmotors_total = count( $panmotors_photos );
$panmotors_multi = $panmotors_total > 1;
$panmotors_pad   = static fn( $n ) => str_pad( (string) $n, 2, '0', STR_PAD_LEFT );
$panmotors_label = $panmotors_title ? $panmotors_title : __( 'Photographs', 'panmotors' );

$panmotors_arrow = static function ( $dir, $class_name ) {
	printf(
		'<button class="%1$s" type="button" aria-controls="pm-slider-stage" aria-label="%2$s" data-slide-%3$s><span aria-hidden="true">%4$s</span></button>',
		esc_attr( $class_name ),
		'prev' === $dir ? esc_attr__( 'Previous photograph', 'panmotors' ) : esc_attr__( 'Next photograph', 'panmotors' ),
		esc_attr( $dir ),
		'prev' === $dir ? '&larr;' : '&rarr;'
	);
};
?>
<section class="pm-slider pm-light"<?php echo $panmotors_title ? ' aria-labelledby="slider-title"' : ''; ?> data-slider>
	<div class="pm-slider__backdrop" aria-hidden="true">
		<?php foreach ( $panmotors_photos as $panmotors_i => $panmotors_id ) : ?>
			<?php
			echo wp_get_attachment_image(
				$panmotors_id,
				'medium_large',
				false,
				array(
					'class'   => 'pm-slider__bg' . ( 0 === $panmotors_i ? ' is-current' : '' ),
					'alt'     => '',
					'sizes'   => '400px', // Blurred 64px: resolution is wasted here.
					'loading' => 'lazy', // Sliders follow a hero or page header: below the fold.
				)
			);
			?>
		<?php endforeach; ?>
		<div class="pm-slider__wash"></div>
	</div>

	<div class="pm-slider__inner pm-pad">
		<div class="pm-slider__head" data-rise>
			<?php if ( $panmotors_title ) : ?>
				<h2 class="pm-title" id="slider-title"><?php echo esc_html( $panmotors_title ); ?></h2>
			<?php endif; ?>
			<?php if ( $panmotors_multi ) : ?>
				<p class="pm-slider__count" aria-live="polite">
					<?php foreach ( $panmotors_photos as $panmotors_i => $panmotors_id ) : ?>
						<span data-slide-part="<?php echo esc_attr( (string) $panmotors_i ); ?>"<?php echo 0 === $panmotors_i ? ' class="is-current"' : ' hidden'; ?>>
							<span class="screen-reader-text"><?php esc_html_e( 'Photograph', 'panmotors' ); ?> </span><?php echo esc_html( $panmotors_pad( $panmotors_i + 1 ) . ' / ' . $panmotors_pad( $panmotors_total ) ); ?>
						</span>
					<?php endforeach; ?>
				</p>
			<?php endif; ?>
		</div>

		<div class="pm-slider__row">
			<?php
			if ( $panmotors_multi ) {
				$panmotors_arrow( 'prev', 'pm-slider__arrow' );
			}
			?>
			<figure class="pm-slider__figure">
				<div class="pm-slider__stage" id="pm-slider-stage" data-zoom
					<?php echo $panmotors_multi ? 'role="region" aria-roledescription="' . esc_attr__( 'carousel', 'panmotors' ) . '" aria-label="' . esc_attr( $panmotors_label ) . '" tabindex="0" data-slide-stage' : ''; ?>>
					<?php foreach ( $panmotors_photos as $panmotors_i => $panmotors_id ) : ?>
						<?php
						echo wp_get_attachment_image(
							$panmotors_id,
							'pm-showroom',
							false,
							array(
								'class'       => 'pm-slider__photo' . ( 0 === $panmotors_i ? ' is-current' : '' ),
								'sizes'       => '(max-width: 880px) calc(100vw - 40px), min(1080px, 76vw)',
								'loading'     => 'lazy', // Sliders follow a hero or page header: below the fold.
								'draggable'   => 'false',
								'aria-hidden' => 0 === $panmotors_i ? 'false' : 'true',
							)
						);
						?>
					<?php endforeach; ?>
				</div>
				<figcaption class="pm-slider__caption">
					<span class="pm-slider__captions">
						<?php foreach ( $panmotors_photos as $panmotors_i => $panmotors_id ) : ?>
							<span data-slide-part="<?php echo esc_attr( (string) $panmotors_i ); ?>"<?php echo 0 === $panmotors_i ? ' class="is-current"' : ' hidden'; ?>><?php echo esc_html( (string) wp_get_attachment_caption( $panmotors_id ) ); ?></span>
						<?php endforeach; ?>
					</span>
					<?php if ( $panmotors_hint && $panmotors_multi ) : ?>
						<span class="pm-slider__hint"><?php echo esc_html( $panmotors_hint ); ?></span>
					<?php endif; ?>
				</figcaption>
				<?php if ( $panmotors_multi ) : ?>
					<div class="pm-slider__nav">
						<?php
						$panmotors_arrow( 'prev', 'pm-slider__arrow pm-slider__arrow--small' );
						$panmotors_arrow( 'next', 'pm-slider__arrow pm-slider__arrow--small' );
						?>
					</div>
				<?php endif; ?>
			</figure>
			<?php
			if ( $panmotors_multi ) {
				$panmotors_arrow( 'next', 'pm-slider__arrow' );
			}
			?>
		</div>

		<?php if ( $panmotors_multi ) : ?>
			<div class="pm-slider__dots">
				<?php foreach ( $panmotors_photos as $panmotors_i => $panmotors_id ) : ?>
					<?php
					$panmotors_caption = (string) wp_get_attachment_caption( $panmotors_id );
					/* translators: 1: photograph number, 2: number of photographs, 3: its caption. */
					$panmotors_dot = sprintf( __( 'Photograph %1$d of %2$d: %3$s', 'panmotors' ), $panmotors_i + 1, $panmotors_total, $panmotors_caption );
					?>
					<button type="button" class="pm-slider__dot" aria-controls="pm-slider-stage" aria-label="<?php echo esc_attr( rtrim( $panmotors_dot, ': ' ) ); ?>" aria-current="<?php echo 0 === $panmotors_i ? 'true' : 'false'; ?>" data-slide-dot="<?php echo esc_attr( (string) $panmotors_i ); ?>"></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
