<?php
/**
 * 404 template. All text comes from Pan Motors settings → Page not found; empty fields hide.
 *
 * @package panmotors
 */

get_header();

$panmotors_code    = panmotors_option( 'notfound_code' );
$panmotors_eyebrow = panmotors_option( 'notfound_eyebrow' );
$panmotors_title   = panmotors_option( 'notfound_title' );
$panmotors_text    = panmotors_option( 'notfound_text' );
$panmotors_home    = panmotors_option( 'notfound_home_label' );
$panmotors_contact = panmotors_option( 'notfound_contact_label' );
?>
<div class="pm-404 pm-pad">
	<?php if ( $panmotors_code ) : ?>
		<div class="pm-404__code" aria-hidden="true"><?php echo esc_html( $panmotors_code ); ?></div>
	<?php endif; ?>
	<?php if ( $panmotors_eyebrow ) : ?>
		<p class="pm-eyebrow"><?php echo esc_html( $panmotors_eyebrow ); ?></p>
	<?php endif; ?>
	<h1 class="pm-404__title pm-title"><?php echo esc_html( $panmotors_title ? $panmotors_title : $panmotors_code ); ?></h1>
	<?php if ( $panmotors_text ) : ?>
		<p class="pm-404__text"><?php echo esc_html( $panmotors_text ); ?></p>
	<?php endif; ?>
	<div class="pm-404__actions">
		<?php if ( $panmotors_home ) : ?>
			<a class="pm-pill pm-pill--solid" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php echo esc_html( $panmotors_home ); ?> <span aria-hidden="true">&rarr;</span>
			</a>
		<?php endif; ?>
		<?php if ( $panmotors_contact && panmotors_contact_page() ) : ?>
			<a class="pm-pill" href="<?php echo esc_url( get_permalink( panmotors_contact_page() ) ); ?>"><?php echo esc_html( $panmotors_contact ); ?></a>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
