<?php
/**
 * Opening hours rows (theme-map 4.9), from Options → Opening hours.
 *
 * Shows the display fields (day, time, note). The machine fields feed the JSON-LD.
 *
 * Args: none.
 *
 * @package panmotors
 */

$panmotors_hours = array_filter(
	panmotors_rows( 'hours', 'option' ),
	static fn( $row ) => '' !== trim( (string) ( $row['day'] ?? '' ) ) && '' !== trim( (string) ( $row['time'] ?? '' ) )
);

if ( ! $panmotors_hours ) {
	return;
}
?>
<dl class="pm-hours">
	<?php foreach ( $panmotors_hours as $panmotors_row ) : ?>
		<div class="pm-hours__row">
			<dt class="pm-hours__day"><?php echo esc_html( $panmotors_row['day'] ); ?></dt>
			<dd class="pm-hours__time"><?php echo esc_html( $panmotors_row['time'] ); ?></dd>
			<?php if ( ! empty( $panmotors_row['note'] ) ) : ?>
				<dd class="pm-hours__note"><?php echo esc_html( $panmotors_row['note'] ); ?></dd>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</dl>
