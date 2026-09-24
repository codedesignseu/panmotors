<?php
/**
 * Car tile: showcase only (D1). No link unless the optional link field is set.
 *
 * Args:
 * - car (array) One featured_cars row: image, marque, model_name, ref_no, spec, note, link.
 *
 * @package panmotors
 */

$panmotors_car = $args['car'] ?? array();
$panmotors_img = (int) ( $panmotors_car['image'] ?? 0 );
$panmotors_mod = trim( (string) ( $panmotors_car['model_name'] ?? '' ) );

if ( ! $panmotors_img && ! $panmotors_mod ) {
	return;
}

$panmotors_marque = trim( (string) ( $panmotors_car['marque'] ?? '' ) );
$panmotors_meta   = array_filter(
	array_map(
		static fn( $key ) => trim( (string) ( $panmotors_car[ $key ] ?? '' ) ),
		array( 'ref_no', 'spec', 'note' )
	)
);
$panmotors_link   = is_array( $panmotors_car['link'] ?? null ) ? $panmotors_car['link'] : array();
?>
<article class="pm-tile">
	<?php
	if ( $panmotors_img ) {
		// Tiles are a quarter of the row, and grow to about 46% on hover. Full width under 880px.
		echo wp_get_attachment_image(
			$panmotors_img,
			'pm-tile',
			false,
			array(
				'class'   => 'pm-tile__img',
				'sizes'   => '(max-width: 880px) calc(100vw - 40px), 46vw',
				'loading' => 'lazy',
			)
		);
	}
	?>
	<div class="pm-tile__shade" aria-hidden="true"></div>
	<div class="pm-tile__copy">
		<?php if ( $panmotors_marque ) : ?>
			<p class="pm-tile__marque"><?php echo esc_html( $panmotors_marque ); ?></p>
		<?php endif; ?>
		<?php if ( $panmotors_mod ) : ?>
			<h3 class="pm-tile__model">
				<?php if ( ! empty( $panmotors_link['url'] ) ) : ?>
					<a class="pm-tile__link" href="<?php echo esc_url( $panmotors_link['url'] ); ?>"<?php echo ! empty( $panmotors_link['target'] ) ? ' target="' . esc_attr( $panmotors_link['target'] ) . '" rel="noopener"' : ''; ?>><?php echo esc_html( $panmotors_mod ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $panmotors_mod ); ?>
				<?php endif; ?>
			</h3>
		<?php endif; ?>
		<?php if ( $panmotors_meta ) : ?>
			<ul class="pm-tile__meta pm-list-reset">
				<?php foreach ( $panmotors_meta as $panmotors_item ) : ?>
					<li><?php echo esc_html( $panmotors_item ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</article>
