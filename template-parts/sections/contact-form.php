<?php
/**
 * Contact form and map (Contact, inner-pages §8.3, _design/v2/contact.html).
 *
 * Left: the dark form card with the form plugin's shortcode (styled through .pm-form), or a preview
 * form with the v2 fields that does not send. Right: the map card and the showroom hours.
 *
 * The map: Google Maps sets cookies, so nothing loads from Google until a visitor presses the
 * button. The iframe is in the HTML inside a <template> (inert); contact-map.js puts it in place on
 * click. Without a map query the card shows a directions link instead. In the block editor the
 * card only shows the placeholder.
 *
 * Args (from blocks/contact-form/render.php): title, shortcode, form (name, email, subject,
 * message → label, hint), button, map_note, map_button, map_link, hours (label), preview.
 *
 * @package panmotors
 */

$panmotors_title    = trim( (string) ( $args['title'] ?? '' ) );
$panmotors_code     = trim( (string) ( $args['shortcode'] ?? '' ) );
$panmotors_texts    = (array) ( $args['form'] ?? array() );
$panmotors_button   = trim( (string) ( $args['button'] ?? '' ) );
$panmotors_query    = trim( (string) panmotors_option( 'map_embed_query', '' ) );
$panmotors_map_url  = trim( (string) panmotors_option( 'map_url', '' ) );
$panmotors_note     = trim( (string) ( $args['map_note'] ?? '' ) );
$panmotors_show     = trim( (string) ( $args['map_button'] ?? '' ) );
$panmotors_dirs     = trim( (string) ( $args['map_link'] ?? '' ) );
$panmotors_hlabel   = trim( (string) ( $args['hours'] ?? '' ) );
$panmotors_name     = trim( (string) panmotors_option( 'trading_name', get_bloginfo( 'name' ) ) );
$panmotors_hours    = array_filter(
	panmotors_rows( 'hours', 'option' ),
	static fn( $row ) => '' !== trim( (string) ( $row['day'] ?? '' ) ) && '' !== trim( (string) ( $row['time'] ?? '' ) )
);
$panmotors_fields   = array(
	'name'    => array( 'text', 'name' ),
	'email'   => array( 'email', 'email' ),
	'subject' => array( 'text', 'off' ),
	'message' => array( 'textarea', '' ),
);
$panmotors_can_load = $panmotors_query && $panmotors_show && empty( $args['preview'] );
?>
<section class="pm-contact-form pm-pad"<?php echo $panmotors_title ? ' aria-labelledby="contact-form-title"' : ''; ?>>
	<div class="pm-contact-form__grid">
		<div class="pm-contact-form__card" data-rise>
			<?php if ( $panmotors_title ) : ?>
				<h2 class="pm-contact-form__title" id="contact-form-title"><?php echo esc_html( $panmotors_title ); ?></h2>
			<?php endif; ?>
			<div class="pm-form">
				<?php if ( $panmotors_code ) : ?>
					<?php echo do_shortcode( $panmotors_code ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Form plugin output. ?>
				<?php else : ?>
					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<p class="pm-form__admin-note"><?php esc_html_e( 'Form plugin shortcode not set. Add it to this block or in Pan Motors settings → Technical. This preview form does not send.', 'panmotors' ); ?></p>
					<?php endif; ?>
					<form class="pm-form__static" action="#" method="post" aria-label="<?php esc_attr_e( 'Contact form (preview, not connected)', 'panmotors' ); ?>" onsubmit="return false">
						<?php foreach ( $panmotors_fields as $panmotors_key => list( $panmotors_type, $panmotors_auto ) ) : ?>
							<?php
							$panmotors_label = trim( (string) ( $panmotors_texts[ $panmotors_key ]['label'] ?? '' ) );
							$panmotors_hint  = trim( (string) ( $panmotors_texts[ $panmotors_key ]['hint'] ?? '' ) );
							$panmotors_fid   = 'pm-contact-' . $panmotors_key;
							if ( ! $panmotors_label ) {
								continue;
							}
							?>
							<p class="pm-form__field">
								<label for="<?php echo esc_attr( $panmotors_fid ); ?>"><?php echo esc_html( $panmotors_label ); ?></label>
								<?php if ( 'textarea' === $panmotors_type ) : ?>
									<textarea id="<?php echo esc_attr( $panmotors_fid ); ?>" name="<?php echo esc_attr( $panmotors_key ); ?>" rows="4" placeholder="<?php echo esc_attr( $panmotors_hint ); ?>"></textarea>
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

		<div class="pm-contact-form__side">
			<div class="pm-contact-form__map" data-map data-rise>
				<div class="pm-contact-form__placeholder" data-map-placeholder>
					<?php if ( $panmotors_note ) : ?>
						<p class="pm-contact-form__note"><?php echo esc_html( $panmotors_note ); ?></p>
					<?php endif; ?>
					<?php if ( $panmotors_query && $panmotors_show ) : ?>
						<button type="button" class="pm-pill pm-contact-form__show" data-map-show<?php echo $panmotors_can_load ? '' : ' disabled'; ?>><?php echo esc_html( $panmotors_show ); ?></button>
					<?php elseif ( $panmotors_map_url && $panmotors_dirs ) : ?>
						<a class="pm-pill pm-contact-form__show" href="<?php echo esc_url( $panmotors_map_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $panmotors_dirs ); ?> <span aria-hidden="true">&rarr;</span><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'panmotors' ); ?></span></a>
					<?php endif; ?>
				</div>
				<?php if ( $panmotors_can_load ) : ?>
					<template data-map-frame>
						<iframe class="pm-contact-form__iframe" src="<?php echo esc_url( 'https://www.google.com/maps?q=' . rawurlencode( $panmotors_query ) . '&output=embed' ); ?>" title="<?php /* translators: %s: business name. */ echo esc_attr( sprintf( __( '%s on Google Maps', 'panmotors' ), $panmotors_name ) ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
					</template>
				<?php endif; ?>
			</div>

			<?php if ( $panmotors_hours ) : ?>
				<div class="pm-contact-form__hours pm-light" data-rise>
					<?php if ( $panmotors_hlabel ) : ?>
						<p class="pm-eyebrow pm-contact-form__eyebrow"><?php echo esc_html( $panmotors_hlabel ); ?></p>
					<?php endif; ?>
					<dl class="pm-contact-form__dl">
						<?php foreach ( $panmotors_hours as $panmotors_row ) : ?>
							<div class="pm-contact-form__row">
								<dt><?php echo esc_html( $panmotors_row['day'] ); ?></dt>
								<dd><?php echo esc_html( $panmotors_row['time'] ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
