<?php
/**
 * Contact rows (Contact, inner-pages §8.2, _design/v2/contact.html): full-width link rows to call,
 * email, find and follow. Every detail comes from Pan Motors settings; a row whose detail is empty
 * there is left out. Each row is one link: small label, the detail in Bodoni, the action.
 *
 * Args (from blocks/contact-details/render.php): texts (call, email, find, follow → label, action).
 *
 * @package panmotors
 */

$panmotors_texts  = (array) ( $args['texts'] ?? array() );
$panmotors_phones = array_filter( array_map( static fn( $row ) => trim( (string) ( $row['number'] ?? '' ) ), panmotors_rows( 'phones', 'option' ) ) );
$panmotors_email  = trim( (string) panmotors_option( 'email', '' ) );
$panmotors_map    = trim( (string) panmotors_option( 'map_url', '' ) );
$panmotors_insta  = trim( (string) panmotors_option( 'instagram_url', '' ) );
$panmotors_street = implode( ', ', array_filter( array( trim( (string) panmotors_option( 'locality', '' ) ), trim( (string) panmotors_option( 'street_address', '' ) ) ) ) );
$panmotors_place  = implode(
	', ',
	array_filter(
		array(
			trim( (string) panmotors_option( 'legal_name', '' ) ),
			$panmotors_street,
			trim( panmotors_option( 'city', '' ) . ' ' . panmotors_option( 'postcode', '' ) ),
		)
	)
);
// Social: the names of the profiles that are set, Instagram first (the link).
$panmotors_social = implode(
	' — ',
	array_keys(
		array_filter(
			array(
				'Instagram' => $panmotors_insta,
				'Facebook'  => trim( (string) panmotors_option( 'facebook_url', '' ) ),
			)
		)
	)
);

$panmotors_rows = array(
	'call'   => array( $panmotors_phones ? 'tel:' . panmotors_tel( reset( $panmotors_phones ) ) : '', implode( ' / ', $panmotors_phones ), false ),
	'email'  => array( $panmotors_email ? 'mailto:' . sanitize_email( $panmotors_email ) : '', $panmotors_email, false ),
	'find'   => array( $panmotors_map, $panmotors_place, true ),
	'follow' => array( $panmotors_insta, $panmotors_social, true ),
);
$panmotors_rows = array_filter( $panmotors_rows, static fn( $row ) => '' !== $row[0] && '' !== $row[1] );

if ( ! $panmotors_rows ) {
	return;
}
?>
<section class="pm-contact-rows pm-pad" aria-label="<?php esc_attr_e( 'Contact details', 'panmotors' ); ?>">
	<ul class="pm-contact-rows__list pm-list-reset">
		<?php foreach ( $panmotors_rows as $panmotors_key => list( $panmotors_href, $panmotors_value, $panmotors_new_tab ) ) : ?>
			<?php
			$panmotors_label  = trim( (string) ( $panmotors_texts[ $panmotors_key ]['label'] ?? '' ) );
			$panmotors_action = trim( (string) ( $panmotors_texts[ $panmotors_key ]['action'] ?? '' ) );
			?>
			<li>
				<a class="pm-contact-rows__row" href="<?php echo esc_url( $panmotors_href ); ?>" data-rise<?php echo $panmotors_new_tab ? ' target="_blank" rel="noopener"' : ''; ?>>
					<?php if ( $panmotors_label ) : ?>
						<span class="pm-contact-rows__label"><?php echo esc_html( $panmotors_label ); ?></span>
					<?php endif; ?>
					<span class="pm-contact-rows__value"><?php echo esc_html( $panmotors_value ); ?></span>
					<span class="pm-contact-rows__action">
						<?php if ( $panmotors_action ) : ?>
							<?php echo esc_html( $panmotors_action ); ?> <span aria-hidden="true">&rarr;</span>
						<?php endif; ?>
						<?php if ( $panmotors_new_tab ) : ?>
							<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'panmotors' ); ?></span>
						<?php endif; ?>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
