<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- WordPress's template name for the pm_event post type.
/**
 * Single event (pm_event, _design/events/event.html). The content is the event's own fields and
 * story, so this is a template with shared template parts rather than a page of blocks: the
 * page header (template-parts/sections/page-header.php, Photo style with the back link, type and
 * status), then template-parts/events/single.php. The footer is dark, as the page ends dark.
 *
 * @package panmotors
 */

get_header();

while ( have_posts() ) :
	the_post();

	$panmotors_event = panmotors_event( get_the_ID() );
	if ( $panmotors_event ) {
		get_template_part( 'template-parts/events/single', null, array( 'event' => $panmotors_event ) );
	} else {
		// A draft without a start date yet (preview): title and story only.
		get_template_part( 'template-parts/components/page-hero' );
		?>
		<div class="pm-event-body pm-pad">
			<div class="pm-event-body__story"><?php the_content(); ?></div>
		</div>
		<?php
	}
endwhile;

panmotors_last_block_tone( 'dark' );

get_footer();
