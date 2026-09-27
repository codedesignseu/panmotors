<?php
/**
 * Visit (Showroom, inner-pages §7.3): opening hours left, address and contact right, on paper.
 * Every business fact comes from Pan Motors settings, the same source as the rest of the site.
 *
 * Args (from blocks/visit/render.php): hours_label, find_label, directions (button text).
 *
 * @package panmotors
 */

$panmotors_hours = array_filter(
	panmotors_rows( 'hours', 'option' ),
	static fn( $row ) => '' !== trim( (string) ( $row['day'] ?? '' ) ) && '' !== trim( (string) ( $row['time'] ?? '' ) )
);

// Address as in the design: name; area, street; city postcode, country.
$panmotors_lines  = array_filter(
	array(
		trim( (string) panmotors_option( 'legal_name', '' ) ),
		implode( ', ', array_filter( array( trim( (string) panmotors_option( 'locality', '' ) ), trim( (string) panmotors_option( 'street_address', '' ) ) ) ) ),
		implode( ', ', array_filter( array( trim( panmotors_option( 'city', '' ) . ' ' . panmotors_option( 'postcode', '' ) ), trim( (string) panmotors_option( 'country_name', '' ) ) ) ) ),
	)
);
$panmotors_phones = array_filter( array_map( static fn( $row ) => trim( (string) ( $row['number'] ?? '' ) ), panmotors_rows( 'phones', 'option' ) ) );
$panmotors_email  = trim( (string) panmotors_option( 'email', '' ) );
$panmotors_map    = trim( (string) panmotors_option( 'map_url', '' ) );
$panmotors_hlabel = trim( (string) ( $args['hours_label'] ?? '' ) );
$panmotors_flabel = trim( (string) ( $args['find_label'] ?? '' ) );
$panmotors_dirs   = trim( (string) ( $args['directions'] ?? '' ) );

if ( ! $panmotors_hours && ! $panmotors_lines ) {
	return;
}
?>
<section class="pm-visit pm-light pm-pad" aria-label="<?php echo esc_attr( $panmotors_flabel ? $panmotors_flabel : __( 'Visit', 'panmotors' ) ); ?>">
	<div class="pm-visit__grid">
		<?php if ( $panmotors_hours ) : ?>
			<div class="pm-visit__col" data-rise>
				<?php if ( $panmotors_hlabel ) : ?>
					<p class="pm-eyebrow pm-visit__eyebrow"><?php echo esc_html( $panmotors_hlabel ); ?></p>
				<?php endif; ?>
				<dl class="pm-visit__hours">
					<?php foreach ( $panmotors_hours as $panmotors_row ) : ?>
						<div class="pm-visit__row">
							<dt class="pm-visit__day"><?php echo esc_html( $panmotors_row['day'] ); ?></dt>
							<dd class="pm-visit__time"><?php echo esc_html( $panmotors_row['time'] ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			</div>
		<?php endif; ?>

		<div class="pm-visit__col" data-rise>
			<?php if ( $panmotors_flabel ) : ?>
				<p class="pm-eyebrow pm-visit__eyebrow"><?php echo esc_html( $panmotors_flabel ); ?></p>
			<?php endif; ?>
			<?php if ( $panmotors_lines || $panmotors_phones || $panmotors_email ) : ?>
				<address class="pm-visit__address">
					<?php if ( $panmotors_lines ) : ?>
						<p class="pm-visit__lines"><?php echo wp_kses( implode( '<br>', array_map( 'esc_html', $panmotors_lines ) ), array( 'br' => array() ) ); ?></p>
					<?php endif; ?>
					<?php if ( $panmotors_phones || $panmotors_email ) : ?>
						<p class="pm-visit__contact">
							<?php if ( $panmotors_phones ) : ?>
								<span>
									<?php
									foreach ( array_values( $panmotors_phones ) as $panmotors_i => $panmotors_n ) {
										echo $panmotors_i ? ' / ' : '';
										printf( '<a href="%s">%s</a>', esc_url( 'tel:' . panmotors_tel( $panmotors_n ) ), esc_html( $panmotors_n ) );
									}
									?>
								</span>
							<?php endif; ?>
							<?php if ( $panmotors_email ) : ?>
								<a href="<?php echo esc_url( 'mailto:' . $panmotors_email ); ?>"><?php echo esc_html( $panmotors_email ); ?></a>
							<?php endif; ?>
						</p>
					<?php endif; ?>
				</address>
			<?php endif; ?>
			<?php if ( $panmotors_dirs && $panmotors_map ) : ?>
				<a class="pm-visit__directions" href="<?php echo esc_url( $panmotors_map ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $panmotors_dirs ); ?> <span aria-hidden="true">&rarr;</span><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'panmotors' ); ?></span></a>
			<?php endif; ?>
		</div>
	</div>
</section>
