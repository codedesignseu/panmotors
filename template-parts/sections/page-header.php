<?php
/**
 * Page header (inner-pages §2.3): the page's H1, as a full-bleed photo or as text on the dark page.
 * The H1 carries id="page-title" for the section label. No breadcrumb on screen (v2); the
 * breadcrumb is structured data only.
 *
 * Args (from blocks/page-header/render.php):
 * - style      (string) 'image' or 'text'.
 * - eyebrow    (string) Small red line.
 * - title      (string) Heading; new lines start new lines.
 * - intro      (string) Short intro.
 * - image      (int)    Photo (image style).
 * - grayscale  (bool)   Photo in black and white.
 * - brightness (int)    Photo brightness in percent, 50–80.
 * - modifier   (string) Extra class suffix, e.g. 'event' (pm-page-header--event).
 * - back       (array)  Optional link above the title: url, label (an event's "All events").
 * - pill       (string) Optional status next to the small red line (an event's "Upcoming").
 *
 * @package panmotors
 */

$panmotors_title   = trim( (string) ( $args['title'] ?? '' ) );
$panmotors_eyebrow = trim( (string) ( $args['eyebrow'] ?? '' ) );
$panmotors_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$panmotors_image   = (int) ( $args['image'] ?? 0 );
$panmotors_photo   = 'image' === ( $args['style'] ?? '' ) && $panmotors_image;

if ( ! $panmotors_title ) {
	return;
}

$panmotors_class = 'pm-page-header pm-page-header--' . ( $panmotors_photo ? 'image' : 'text' );
if ( ! empty( $args['modifier'] ) ) {
	$panmotors_class .= ' pm-page-header--' . sanitize_html_class( $args['modifier'] );
}
$panmotors_back = (array) ( $args['back'] ?? array() );
$panmotors_pill = trim( (string) ( $args['pill'] ?? '' ) );
if ( $panmotors_photo && ! empty( $args['grayscale'] ) ) {
	$panmotors_class .= ' pm-page-header--grayscale';
}
$panmotors_brightness = max( 50, min( 80, (int) ( $args['brightness'] ?? 62 ) ) ) / 100;
?>
<section class="<?php echo esc_attr( $panmotors_class ); ?>" aria-labelledby="page-title"<?php echo $panmotors_photo ? ' style="--pm-brightness: ' . esc_attr( (string) $panmotors_brightness ) . '"' : ''; ?>>
	<?php if ( $panmotors_photo ) : ?>
		<div class="pm-page-header__media" data-hero-img>
			<?php
			echo wp_get_attachment_image(
				$panmotors_image,
				'pm-hero',
				false,
				array(
					'class'         => 'pm-page-header__img',
					'sizes'         => '100vw',
					'loading'       => 'eager',
					'fetchpriority' => 'high',
				)
			);
			?>
		</div>
		<div class="pm-page-header__shade" aria-hidden="true"></div>
	<?php endif; ?>

	<div class="pm-page-header__copy">
		<div class="pm-page-header__head">
			<?php
			if ( ! empty( $panmotors_back['url'] ) && ! empty( $panmotors_back['label'] ) ) {
				printf( '<a class="pm-page-header__back" href="%s" data-hero-in="1"><span aria-hidden="true">&larr;</span> %s</a>', esc_url( $panmotors_back['url'] ), esc_html( $panmotors_back['label'] ) );
			}
			if ( $panmotors_pill ) :
				?>
				<div class="pm-page-header__meta" data-hero-in="1">
					<?php if ( $panmotors_eyebrow ) : ?>
						<p class="pm-eyebrow pm-page-header__eyebrow"><?php echo esc_html( $panmotors_eyebrow ); ?></p>
					<?php endif; ?>
					<p class="pm-page-header__pill"><?php echo esc_html( $panmotors_pill ); ?></p>
				</div>
				<?php
			elseif ( $panmotors_eyebrow ) :
				?>
				<p class="pm-eyebrow pm-page-header__eyebrow" data-hero-in="1"><?php echo esc_html( $panmotors_eyebrow ); ?></p>
			<?php endif; ?>
			<h1 class="pm-page-header__title" id="page-title" data-hero-in="2"><?php echo nl2br( esc_html( $panmotors_title ), false ); ?></h1>
		</div>
		<?php if ( $panmotors_intro ) : ?>
			<p class="pm-page-header__intro" data-hero-in="3"><?php echo esc_html( $panmotors_intro ); ?></p>
		<?php endif; ?>
	</div>
</section>
