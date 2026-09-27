<?php
/**
 * Site footer. Closes <main>.
 *
 * @package panmotors
 */

$panmotors_tagline = panmotors_option( 'footer_tagline' );
// "{year}" in the copyright text becomes the current year.
$panmotors_copy    = str_replace( '{year}', wp_date( 'Y' ), (string) panmotors_option( 'footer_copyright', '' ) );
// Light or dark, from the page's Footer style (Auto: the last section's background).
$panmotors_dark    = 'dark' === panmotors_footer_tone();
?>
</main>

<footer class="pm-footer <?php echo $panmotors_dark ? 'pm-footer--dark' : 'pm-light'; ?>">
	<div class="pm-footer__row">
		<p class="pm-footer__brand">
			<?php
			if ( $panmotors_dark ) {
				panmotors_logo_image( 'pm-footer__logo', '', '62px' ); // 34px tall.
			} else {
				panmotors_logo_light_image( 'pm-footer__logo', '', '62px' ); // Inverted, or the logo for light backgrounds.
			}
			?>
			<?php echo esc_html( (string) $panmotors_tagline ); ?>
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

		<?php if ( $panmotors_copy ) : ?>
			<p class="pm-footer__copy"><?php echo esc_html( $panmotors_copy ); ?></p>
		<?php endif; ?>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
