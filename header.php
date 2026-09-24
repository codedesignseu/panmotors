<?php
/**
 * Site header: skip link, fixed nav, mobile menu panel. Opens <main>.
 *
 * @package panmotors
 */

$panmotors_contact_id  = panmotors_page( 'contact' );
$panmotors_contact_url = $panmotors_contact_id ? get_permalink( $panmotors_contact_id ) : home_url( '/' );
$panmotors_contact_cur = $panmotors_contact_id && is_page( $panmotors_contact_id ) ? ' aria-current="page"' : '';
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<a class="pm-skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'panmotors' ); ?></a>
<?php wp_body_open(); ?>

<header class="pm-header">
	<nav class="pm-nav" aria-label="<?php esc_attr_e( 'Main', 'panmotors' ); ?>">
		<?php panmotors_logo( 'pm-nav__logo' ); ?>

		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'pm-nav__links pm-list-reset',
				'depth'          => 1,
				'fallback_cb'    => false,
				'pm_link_class'  => 'pm-nav__link pm-underline',
			)
		);
		?>

		<button class="pm-burger" type="button" aria-expanded="false" aria-controls="pm-menu" data-menu-toggle>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'panmotors' ); ?></span>
			<span class="pm-burger__line" aria-hidden="true"></span>
			<span class="pm-burger__line" aria-hidden="true"></span>
		</button>

		<a class="pm-nav__contact" href="<?php echo esc_url( $panmotors_contact_url ); ?>"<?php echo $panmotors_contact_cur; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed string. ?>><?php esc_html_e( 'Contact', 'panmotors' ); ?></a>
	</nav>

	<div class="pm-menu" id="pm-menu" data-menu inert>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'pm-menu__links pm-list-reset',
				'depth'          => 1,
				'fallback_cb'    => false,
				'pm_link_class'  => 'pm-menu__link',
				// Contact is appended as the last item. items_wrap runs through sprintf(), so % is doubled.
				'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s' . str_replace(
					'%',
					'%%',
					sprintf(
						'<li><a class="pm-menu__link" href="%s"%s>%s</a></li>',
						esc_url( $panmotors_contact_url ),
						$panmotors_contact_cur,
						esc_html__( 'Contact', 'panmotors' )
					)
				) . '</ul>',
			)
		);
		?>
	</div>
</header>

<main id="main" tabindex="-1">
