<?php
/**
 * Site footer. Closes <main>.
 *
 * @package panmotors
 */

$panmotors_tagline = panmotors_option( 'footer_tagline' );
$panmotors_holder  = panmotors_option( 'footer_copyright', panmotors_option( 'trading_name', get_bloginfo( 'name' ) ) );
$panmotors_street  = panmotors_option( 'street_address' );
$panmotors_city    = panmotors_option( 'city' );
$panmotors_phones  = panmotors_rows( 'phones', 'option' );
$panmotors_phone   = $panmotors_phones[0]['number'] ?? '';
?>
</main>

<footer class="pm-footer pm-light">
	<div class="pm-footer__row">
		<p class="pm-footer__brand">
			<?php panmotors_logo_image( 'pm-footer__logo', '', '62px' ); // 34px tall. ?>
			<?php echo esc_html( $panmotors_tagline ?? '' ); ?>
		</p>

		<?php
		wp_nav_menu(
			array(
				'theme_location'       => 'footer',
				'container'            => 'nav',
				'container_class'      => 'pm-footer__nav',
				'container_aria_label' => __( 'Footer', 'panmotors' ),
				'menu_class'           => 'pm-footer__links pm-list-reset',
				'depth'                => 1,
				'fallback_cb'          => false,
			)
		);
		?>

		<p class="pm-footer__copy">&copy; <?php echo esc_html( wp_date( 'Y' ) . ' ' . $panmotors_holder ); ?></p>
	</div>

	<?php if ( $panmotors_street || $panmotors_city || $panmotors_phone ) : ?>
		<address class="pm-footer__address">
			<?php
			$panmotors_place = implode( ', ', array_filter( array( $panmotors_street, $panmotors_city ) ) );
			if ( $panmotors_place ) {
				echo '<span>' . esc_html( $panmotors_place ) . '</span>';
			}
			if ( $panmotors_phone ) {
				printf(
					'<a href="%s">%s</a>',
					esc_url( 'tel:' . panmotors_tel( $panmotors_phone ) ),
					esc_html( $panmotors_phone )
				);
			}
			?>
		</address>
	<?php endif; ?>
</footer>

<?php wp_footer(); ?>
</body>
</html>
