<?php
/**
 * Come And See (theme-map 4.10). Heading and intro from the Contact page, contact details and
 * the form shortcode from the options.
 *
 * The form plugin renders inside .pm-form and handles submissions (D4). Without a shortcode the
 * design's static form is shown for layout only: its submit button is disabled, so it never
 * pretends to send, and admins see a note.
 *
 * Args:
 * - page_id (int)    Contact page.
 * - context (string) 'home' (built) or 'page' (after the Contact page is designed).
 *
 * @package panmotors
 */

$panmotors_page_id = (int) ( $args['page_id'] ?? 0 );
$panmotors_title   = panmotors_field(
	'home_enquire_title',
	(int) get_option( 'page_on_front' ),
	panmotors_field( 'enquire_title', $panmotors_page_id, __( 'Come And See', 'panmotors' ) )
);
$panmotors_intro   = panmotors_field( 'enquire_intro', $panmotors_page_id );
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
?>
<section id="enquire" class="pm-enquire pm-light pm-pad" aria-labelledby="enquire-title">
	<div class="pm-enquire__card" data-rise>
		<div class="pm-enquire__info">
			<h2 class="pm-enquire__title" id="enquire-title"><?php echo esc_html( $panmotors_title ); ?></h2>
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
				<?php if ( current_user_can( 'edit_theme_options' ) ) : ?>
					<p class="pm-form__admin-note"><?php esc_html_e( 'Form plugin shortcode not set. Add it in Pan Motors settings → Enquiry form. This preview form does not send.', 'panmotors' ); ?></p>
				<?php endif; ?>
				<form class="pm-form__static" action="#" method="post" aria-label="<?php esc_attr_e( 'Enquiry form (preview, not connected)', 'panmotors' ); ?>" onsubmit="return false">
					<p class="pm-form__field">
						<label for="pm-enquire-name"><?php esc_html_e( 'Name', 'panmotors' ); ?></label>
						<input id="pm-enquire-name" name="name" type="text" autocomplete="name" placeholder="<?php esc_attr_e( 'Full name', 'panmotors' ); ?>">
					</p>
					<p class="pm-form__field">
						<label for="pm-enquire-email"><?php esc_html_e( 'Email', 'panmotors' ); ?></label>
						<input id="pm-enquire-email" name="email" type="email" autocomplete="email" placeholder="<?php esc_attr_e( 'you@domain.com', 'panmotors' ); ?>">
					</p>
					<p class="pm-form__field">
						<label for="pm-enquire-phone"><?php esc_html_e( 'Phone', 'panmotors' ); ?></label>
						<input id="pm-enquire-phone" name="phone" type="tel" autocomplete="tel" placeholder="+357">
					</p>
					<p class="pm-form__field">
						<label for="pm-enquire-message"><?php esc_html_e( 'Message', 'panmotors' ); ?></label>
						<textarea id="pm-enquire-message" name="message" rows="3" placeholder="<?php esc_attr_e( 'Tell us which car you are interested in.', 'panmotors' ); ?>"></textarea>
					</p>
					<button type="submit" disabled><?php esc_html_e( 'Send message', 'panmotors' ); ?></button>
				</form>
			<?php endif; ?>
		</div>
	</div>
</section>
