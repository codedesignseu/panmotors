<?php
/**
 * Come And See (theme-map 4.10). Heading, intro and preview-form texts from the block, contact
 * details and the form shortcode from the options.
 *
 * The form plugin renders inside .pm-form and handles submissions (D4). Without a shortcode the
 * design's static form is shown for layout only: its submit button is disabled, so it never
 * pretends to send, and admins see a note.
 *
 * Args (from blocks/enquire/render.php):
 * - title  (string) Heading.
 * - intro  (string) Intro.
 * - form   (array)  Preview form texts per field (name, email, phone, message): label, hint.
 * - button (string) Preview form button text.
 *
 * @package panmotors
 */

$panmotors_title   = (string) ( $args['title'] ?? '' );
$panmotors_intro   = (string) ( $args['intro'] ?? '' );
$panmotors_address = implode(
	', ',
	array_filter(
		array(
			panmotors_option( 'legal_name' ),
			panmotors_option( 'street_address' ),
			panmotors_option( 'locality' ),
			trim( panmotors_option( 'city', '' ) . ' ' . panmotors_option( 'postcode', '' ) ),
			panmotors_option( 'country_name' ),
		)
	)
);
$panmotors_phones  = array_filter( array_map( static fn( $row ) => trim( (string) ( $row['number'] ?? '' ) ), panmotors_rows( 'phones', 'option' ) ) );
$panmotors_email   = panmotors_option( 'email' );
$panmotors_site    = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
$panmotors_form    = trim( (string) panmotors_option( 'enquire_form_shortcode', '' ) );
$panmotors_texts   = (array) ( $args['form'] ?? array() );
$panmotors_button  = (string) ( $args['button'] ?? '' );
$panmotors_fields  = array(
	'name'    => array( 'text', 'name' ),
	'email'   => array( 'email', 'email' ),
	'phone'   => array( 'tel', 'tel' ),
	'message' => array( 'textarea', '' ),
);
?>
<section id="enquire" class="pm-enquire pm-light pm-pad"<?php echo $panmotors_title ? ' aria-labelledby="enquire-title"' : ''; ?>>
	<div class="pm-enquire__card" data-rise>
		<div class="pm-enquire__info">
			<?php if ( $panmotors_title ) : ?>
				<h2 class="pm-enquire__title" id="enquire-title"><?php echo esc_html( $panmotors_title ); ?></h2>
			<?php endif; ?>
			<?php if ( $panmotors_intro ) : ?>
				<p class="pm-enquire__intro"><?php echo esc_html( $panmotors_intro ); ?></p>
			<?php endif; ?>

			<div class="pm-enquire__details">
				<?php if ( $panmotors_address ) : ?>
					<address><?php echo esc_html( $panmotors_address ); ?></address>
				<?php endif; ?>
				<?php if ( $panmotors_phones ) : ?>
					<p>
						<?php
						echo implode(
							' / ',
							array_map(
								static fn( $n ) => '<a href="' . esc_url( 'tel:' . panmotors_tel( $n ) ) . '">' . esc_html( $n ) . '</a>',
								$panmotors_phones
							)
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
						?>
					</p>
				<?php endif; ?>
				<?php if ( $panmotors_email ) : ?>
					<p><a href="<?php echo esc_url( 'mailto:' . sanitize_email( $panmotors_email ) ); ?>"><?php echo esc_html( $panmotors_email ); ?></a></p>
				<?php endif; ?>
				<?php if ( $panmotors_site ) : ?>
					<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( preg_replace( '/^www\./', '', $panmotors_site ) ); ?></a></p>
				<?php endif; ?>
				<?php panmotors_logo_image( 'pm-enquire__logo', '', '98px' ); // 54px tall. ?>
			</div>
		</div>

		<div class="pm-form">
			<?php if ( $panmotors_form ) : ?>
				<?php echo do_shortcode( $panmotors_form ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Form plugin output. ?>
			<?php else : ?>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<p class="pm-form__admin-note"><?php esc_html_e( 'Form plugin shortcode not set. Add it in Pan Motors settings → Technical. This preview form does not send.', 'panmotors' ); ?></p>
				<?php endif; ?>
				<form class="pm-form__static" action="#" method="post" aria-label="<?php esc_attr_e( 'Enquiry form (preview, not connected)', 'panmotors' ); ?>" onsubmit="return false">
					<?php foreach ( $panmotors_fields as $panmotors_key => list( $panmotors_type, $panmotors_auto ) ) : ?>
						<?php
						$panmotors_label = (string) ( $panmotors_texts[ $panmotors_key ]['label'] ?? '' );
						$panmotors_hint  = (string) ( $panmotors_texts[ $panmotors_key ]['hint'] ?? '' );
						$panmotors_fid   = 'pm-enquire-' . $panmotors_key;
						if ( ! $panmotors_label ) {
							continue;
						}
						?>
						<p class="pm-form__field">
							<label for="<?php echo esc_attr( $panmotors_fid ); ?>"><?php echo esc_html( $panmotors_label ); ?></label>
							<?php if ( 'textarea' === $panmotors_type ) : ?>
								<textarea id="<?php echo esc_attr( $panmotors_fid ); ?>" name="<?php echo esc_attr( $panmotors_key ); ?>" rows="3" placeholder="<?php echo esc_attr( $panmotors_hint ); ?>"></textarea>
							<?php else : ?>
								<input id="<?php echo esc_attr( $panmotors_fid ); ?>" name="<?php echo esc_attr( $panmotors_key ); ?>" type="<?php echo esc_attr( $panmotors_type ); ?>" autocomplete="<?php echo esc_attr( $panmotors_auto ); ?>" placeholder="<?php echo esc_attr( $panmotors_hint ); ?>">
							<?php endif; ?>
						</p>
					<?php endforeach; ?>
					<?php if ( $panmotors_button ) : ?>
						<button type="submit" disabled><?php echo esc_html( $panmotors_button ); ?></button>
					<?php endif; ?>
				</form>
			<?php endif; ?>
		</div>
	</div>
</section>
