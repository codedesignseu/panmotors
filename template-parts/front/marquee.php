<?php
/**
 * Marquee: marque names from Options → Marques, scrolling left forever.
 *
 * The CSS loop moves the track by -50%, so the list is printed twice. The second copy
 * is hidden from assistive tech. Each copy holds at least six names so short lists
 * still fill the width.
 *
 * @package panmotors
 */

$panmotors_marques = array_values(
	array_filter(
		array_map(
			static fn( $row ) => trim( (string) ( $row['name'] ?? '' ) ),
			panmotors_rows( 'marques', 'option' )
		)
	)
);

if ( ! $panmotors_marques ) {
	return;
}

$panmotors_names = array();
while ( count( $panmotors_names ) < 6 ) {
	$panmotors_names = array_merge( $panmotors_names, $panmotors_marques );
}
?>
<section class="pm-marquee" aria-label="<?php esc_attr_e( 'Marques', 'panmotors' ); ?>">
	<div class="pm-marquee__track">
		<?php foreach ( array( false, true ) as $panmotors_copy ) : ?>
			<ul class="pm-marquee__list pm-list-reset"<?php echo $panmotors_copy ? ' aria-hidden="true"' : ''; ?>>
				<?php foreach ( $panmotors_names as $panmotors_i => $panmotors_name ) : ?>
					<?php
					// Repeats inside one copy are for visual fill only.
					$panmotors_hide = ! $panmotors_copy && $panmotors_i >= count( $panmotors_marques );
					?>
					<li class="pm-marquee__item"<?php echo $panmotors_hide ? ' aria-hidden="true"' : ''; ?>><?php echo esc_html( $panmotors_name ); ?></li>
					<li class="pm-marquee__item pm-marquee__dash" aria-hidden="true">&mdash;</li>
				<?php endforeach; ?>
			</ul>
		<?php endforeach; ?>
	</div>
</section>
