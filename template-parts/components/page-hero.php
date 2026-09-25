<?php
/**
 * Inner page hero: breadcrumb, eyebrow, H1 (the page title), intro, optional background image.
 *
 * Used by the pm/page-hero block, and by page.php (title only) on pages without that block.
 *
 * Args:
 * - page_id (int)    Page whose title is the H1. Defaults to the current post.
 * - eyebrow (string) Small red line.
 * - intro   (string) Short intro.
 * - image   (int)    Background image attachment ID.
 *
 * @package panmotors
 */

$panmotors_id       = (int) ( $args['page_id'] ?? 0 );
$panmotors_id       = $panmotors_id ? $panmotors_id : (int) get_the_ID();
$panmotors_image_id = (int) ( $args['image'] ?? 0 );
$panmotors_eyebrow  = (string) ( $args['eyebrow'] ?? '' );
$panmotors_intro    = (string) ( $args['intro'] ?? '' );

// Breadcrumb: Home, then any parent pages, then this page.
$panmotors_crumbs = array(
	array(
		'label' => get_the_title( (int) get_option( 'page_on_front' ) ),
		'url'   => home_url( '/' ),
	),
);
foreach ( array_reverse( get_post_ancestors( $panmotors_id ) ) as $panmotors_parent ) {
	$panmotors_crumbs[] = array(
		'label' => get_the_title( $panmotors_parent ),
		'url'   => get_permalink( $panmotors_parent ),
	);
}
?>
<section class="pm-page-hero<?php echo $panmotors_image_id ? ' pm-page-hero--image' : ''; ?>" aria-labelledby="page-title">
	<?php if ( $panmotors_image_id ) : ?>
		<div class="pm-page-hero__media" aria-hidden="true">
			<?php
			echo wp_get_attachment_image(
				$panmotors_image_id,
				'pm-hero',
				false,
				array(
					'class'         => 'pm-page-hero__img',
					'alt'           => '',
					'sizes'         => '100vw',
					'loading'       => 'eager',
					'fetchpriority' => 'high',
				)
			);
			?>
		</div>
		<div class="pm-page-hero__shade" aria-hidden="true"></div>
	<?php endif; ?>

	<div class="pm-page-hero__copy">
		<nav class="pm-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'panmotors' ); ?>" data-hero-in="1">
			<ol class="pm-breadcrumb__list pm-list-reset">
				<?php foreach ( $panmotors_crumbs as $panmotors_crumb ) : ?>
					<li class="pm-breadcrumb__item"><a href="<?php echo esc_url( $panmotors_crumb['url'] ); ?>"><?php echo esc_html( $panmotors_crumb['label'] ); ?></a></li>
				<?php endforeach; ?>
				<li class="pm-breadcrumb__item" aria-current="page"><?php echo esc_html( get_the_title( $panmotors_id ) ); ?></li>
			</ol>
		</nav>

		<?php if ( $panmotors_eyebrow ) : ?>
			<p class="pm-eyebrow pm-page-hero__eyebrow" data-hero-in="1"><?php echo esc_html( $panmotors_eyebrow ); ?></p>
		<?php endif; ?>

		<h1 class="pm-page-hero__title" id="page-title" data-hero-in="2"><?php echo esc_html( get_the_title( $panmotors_id ) ); ?></h1>

		<?php if ( $panmotors_intro ) : ?>
			<p class="pm-page-hero__intro" data-hero-in="3"><?php echo esc_html( $panmotors_intro ); ?></p>
		<?php endif; ?>
	</div>
</section>
