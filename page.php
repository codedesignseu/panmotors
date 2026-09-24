<?php
/**
 * Default page template. Used for the privacy and cookie policy pages.
 *
 * @package panmotors
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="pm-page pm-light pm-pad">
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="pm-page__head">
				<?php the_title( '<h1 class="pm-page__title">', '</h1>' ); ?>
			</header>

			<div class="pm-page__body">
				<div class="pm-prose">
					<?php
					the_content();

					wp_link_pages(
						array(
							'before' => '<nav class="pm-pagination" aria-label="' . esc_attr__( 'Page sections', 'panmotors' ) . '">',
							'after'  => '</nav>',
						)
					);
					?>
				</div>
			</div>
		</article>
	</div>
	<?php
endwhile;

get_footer();
