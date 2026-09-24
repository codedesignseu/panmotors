<?php
/**
 * 404 template.
 *
 * @package panmotors
 */

get_header();
?>
<div class="pm-404 pm-pad">
	<div class="pm-404__code" aria-hidden="true">404</div>
	<p class="pm-eyebrow"><?php esc_html_e( 'Page not found', 'panmotors' ); ?></p>
	<h1 class="pm-404__title pm-title"><?php esc_html_e( 'Off the Map', 'panmotors' ); ?></h1>
	<p class="pm-404__text"><?php esc_html_e( 'The page you were looking for has moved or no longer exists. The cars are still where we left them.', 'panmotors' ); ?></p>
	<div class="pm-404__actions">
		<a class="pm-pill pm-pill--solid" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php esc_html_e( 'Back to home', 'panmotors' ); ?> <span aria-hidden="true">&rarr;</span>
		</a>
		<a class="pm-pill" href="<?php echo esc_url( home_url( '/#enquire' ) ); ?>"><?php esc_html_e( 'Contact us', 'panmotors' ); ?></a>
	</div>
</div>
<?php
get_footer();
