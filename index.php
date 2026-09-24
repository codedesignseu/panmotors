<?php
/**
 * Fallback template. The site has no blog, but WordPress needs this file.
 *
 * @package panmotors
 */

get_header();
?>
<div class="pm-page pm-pad">
	<header class="pm-page__head">
		<h1 class="pm-page__title">
			<?php
			if ( is_home() && ! is_front_page() ) {
				single_post_title();
			} elseif ( is_archive() ) {
				the_archive_title();
			} elseif ( is_search() ) {
				/* translators: %s: search query. */
				printf( esc_html__( 'Search: %s', 'panmotors' ), esc_html( get_search_query() ) );
			} else {
				bloginfo( 'name' );
			}
			?>
		</h1>
	</header>

	<div class="pm-page__body">
		<?php if ( have_posts() ) : ?>
			<ul class="pm-list">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<li class="pm-list__item">
						<a class="pm-list__link" href="<?php the_permalink(); ?>">
							<span class="pm-list__title"><?php the_title(); ?></span>
							<time class="pm-meta" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						</a>
					</li>
				<?php endwhile; ?>
			</ul>

			<?php
			the_posts_pagination(
				array(
					'class'              => 'pm-pagination',
					'mid_size'           => 1,
					'prev_text'          => '<span aria-hidden="true">&larr;</span><span class="screen-reader-text">' . esc_html__( 'Previous page', 'panmotors' ) . '</span>',
					'next_text'          => '<span aria-hidden="true">&rarr;</span><span class="screen-reader-text">' . esc_html__( 'Next page', 'panmotors' ) . '</span>',
					'screen_reader_text' => esc_html__( 'Pages', 'panmotors' ),
				)
			);
			?>
		<?php else : ?>
			<p class="pm-prose"><?php esc_html_e( 'Nothing here yet.', 'panmotors' ); ?></p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
