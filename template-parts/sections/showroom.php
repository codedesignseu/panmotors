<?php
/**
 * Showroom section (theme-map 4.9). Photos from the block, hours from the options.
 *
 * Every photo is in the HTML, stacked; showroom-slider.js crossfades by toggling .is-active
 * on the photo and on its blurred background copy. Captions come from the media library.
 *
 * Args (from blocks/showroom/render.php): eyebrow, title, intro, photos (attachment IDs),
 * hours (bool, show the opening hours).
 *
 * @package panmotors
 */

$panmotors_photos = array_values( array_filter( array_map( 'intval', (array) ( $args['photos'] ?? array() ) ) ) );

if ( ! $panmotors_photos ) {
	return;
}

$panmotors_eyebrow = (string) ( $args['eyebrow'] ?? '' );
$panmotors_title   = (string) ( $args['title'] ?? '' );
$panmotors_intro   = (string) ( $args['intro'] ?? '' );
$panmotors_total   = count( $panmotors_photos );
$panmotors_multi   = $panmotors_total > 1;
$panmotors_first   = (string) wp_get_attachment_caption( $panmotors_photos[0] );

$panmotors_arrow = static function ( $dir, $class ) {
	printf(
		'<button class="%1$s" type="button" aria-controls="showroom-photos" aria-label="%2$s" data-slider-%3$s>%4$s</button>',
		esc_attr( $class ),
		'prev' === $dir ? esc_attr__( 'Previous showroom photograph', 'panmotors' ) : esc_attr__( 'Next showroom photograph', 'panmotors' ),
		esc_attr( $dir ),
		'prev' === $dir ? '&larr;' : '&rarr;'
	);
};
?>
<section id="showroom" class="pm-showroom pm-light" aria-labelledby="showroom-title" data-showroom>
	<div class="pm-showroom__backdrop" aria-hidden="true">
		<?php foreach ( $panmotors_photos as $panmotors_i => $panmotors_id ) : ?>
			<?php
			echo wp_get_attachment_image(
				$panmotors_id,
				'medium_large',
				false,
				array(
					'class'   => 'pm-showroom__bg' . ( 0 === $panmotors_i ? ' is-active' : '' ),
					'alt'     => '',
					'sizes'   => '400px', // Blurred 64px: resolution is wasted here.
					'loading' => 0 === $panmotors_i ? 'eager' : 'lazy',
				)
			);
			?>
		<?php endforeach; ?>
	</div>
	<div class="pm-showroom__wash" aria-hidden="true"></div>

	<div class="pm-showroom__inner pm-pad">
		<div class="pm-section-head pm-showroom__head" data-rise>
			<div>
				<?php if ( $panmotors_eyebrow ) : ?>
					<p class="pm-eyebrow"><?php echo esc_html( $panmotors_eyebrow ); ?></p>
				<?php endif; ?>
				<h2 class="pm-title" id="showroom-title"><?php echo esc_html( $panmotors_title ); ?></h2>
			</div>
			<?php if ( $panmotors_intro ) : ?>
				<p class="pm-lede"><?php echo esc_html( $panmotors_intro ); ?></p>
			<?php endif; ?>
		</div>

		<div class="pm-showroom__stage">
			<?php
			if ( $panmotors_multi ) {
				$panmotors_arrow( 'prev', 'pm-round pm-showroom__arrow' );
			}
			?>
			<figure class="pm-showroom__figure">
				<div class="pm-showroom__photos" id="showroom-photos" data-zoom
					<?php echo $panmotors_multi ? 'role="region" aria-roledescription="' . esc_attr__( 'carousel', 'panmotors' ) . '" aria-label="' . esc_attr( $panmotors_title ) . '" tabindex="0" data-slider-viewport' : ''; ?>>
					<?php foreach ( $panmotors_photos as $panmotors_i => $panmotors_id ) : ?>
						<?php
						echo wp_get_attachment_image(
							$panmotors_id,
							'pm-showroom',
							false,
							array(
								'class'        => 'pm-showroom__photo' . ( 0 === $panmotors_i ? ' is-active' : '' ),
								'sizes'        => '(max-width: 880px) calc(100vw - 40px), min(1080px, 76vw)',
								'loading'      => 0 === $panmotors_i ? 'eager' : 'lazy',
								'aria-hidden'  => 0 === $panmotors_i ? 'false' : 'true',
								'data-caption' => (string) wp_get_attachment_caption( $panmotors_id ),
							)
						);
						?>
					<?php endforeach; ?>
				</div>
				<figcaption class="pm-showroom__caption">
					<span class="pm-showroom__caption-text" data-slider-caption><?php echo esc_html( $panmotors_first ); ?></span>
					<?php if ( $panmotors_multi ) : ?>
						<span class="pm-showroom__count" aria-live="polite">
							<span class="screen-reader-text"><?php esc_html_e( 'Photograph', 'panmotors' ); ?></span>
							<span data-slider-count><?php echo esc_html( sprintf( '%02d / %02d', 1, $panmotors_total ) ); ?></span>
						</span>
					<?php endif; ?>
				</figcaption>
				<?php if ( $panmotors_multi ) : ?>
					<div class="pm-showroom__nav">
						<?php
						$panmotors_arrow( 'prev', 'pm-round pm-showroom__nav-btn' );
						$panmotors_arrow( 'next', 'pm-round pm-showroom__nav-btn' );
						?>
					</div>
				<?php endif; ?>
			</figure>
			<?php
			if ( $panmotors_multi ) {
				$panmotors_arrow( 'next', 'pm-round pm-showroom__arrow' );
			}
			?>
		</div>

		<?php
		if ( ! empty( $args['hours'] ) ) {
			get_template_part( 'template-parts/sections/hours' );
		}
		?>
	</div>
</section>
