<?php
/**
 * Cars grid (Featured Cars, inner-pages §6.2, _design/v2/cars.html).
 *
 * - Filter buttons: the first shows every car, then one per marque that has cars (most cars first,
 *   then A to Z), each with its count. Hidden until cars-grid.js runs; without JS every car shows.
 * - Rows of 3, 2, 3, 2, 3 cards; in a row of 3 the middle card is wider, in a row of 2 the first.
 * - Each card opens its car sheet in the <dialog>. Every sheet is in this HTML (hidden); the
 *   script only shows one and moves between them, it never writes text.
 * - No Car, Product or Offer schema (theme-map §9).
 *
 * Args (from blocks/cars-grid/render.php): cars (rows from panmotors_cars()), intro, all (first
 * filter label), enquire (sheet button label), link (sheet button URL), labels (year, engine,
 * power, sprint, gearbox, colour, mileage, no).
 *
 * @package panmotors
 */

$panmotors_cars = array_values(
	array_filter(
		(array) ( $args['cars'] ?? array() ),
		static fn( $car ) => '' !== trim( (string) ( $car['model_name'] ?? '' ) )
	)
);

if ( ! $panmotors_cars ) {
	return;
}

$panmotors_labels  = (array) ( $args['labels'] ?? array() );
$panmotors_label   = static fn( $key, $fallback ) => ( '' !== trim( (string) ( $panmotors_labels[ $key ] ?? '' ) ) ) ? trim( $panmotors_labels[ $key ] ) : $fallback;
$panmotors_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$panmotors_enquire = trim( (string) ( $args['enquire'] ?? '' ) );
$panmotors_link    = (string) ( $args['link'] ?? '' );
$panmotors_no      = $panmotors_label( 'no', __( 'No.', 'panmotors' ) );

// Marques with their counts: most cars first, then A to Z.
$panmotors_marques = array();
foreach ( $panmotors_cars as $panmotors_car ) {
	$panmotors_m = trim( (string) ( $panmotors_car['marque'] ?? '' ) );
	if ( '' !== $panmotors_m ) {
		$panmotors_marques[ $panmotors_m ] = ( $panmotors_marques[ $panmotors_m ] ?? 0 ) + 1;
	}
}
uksort(
	$panmotors_marques,
	static fn( $a, $b ) => $panmotors_marques[ $b ] === $panmotors_marques[ $a ] ? strnatcasecmp( $a, $b ) : $panmotors_marques[ $b ] <=> $panmotors_marques[ $a ]
);

// Rows: 3, 2, 3, 2, 3, then the pattern repeats.
$panmotors_rows    = array();
$panmotors_pattern = array( 3, 2, 3, 2, 3 );
$panmotors_n_cars  = count( $panmotors_cars );
for ( $panmotors_i = 0, $panmotors_p = 0; $panmotors_i < $panmotors_n_cars; $panmotors_p++ ) {
	$panmotors_n      = $panmotors_pattern[ $panmotors_p % count( $panmotors_pattern ) ];
	$panmotors_rows[] = array(
		'size' => $panmotors_n, // The pattern's size: a last row may hold fewer cards.
		'cars' => array_slice( array_keys( $panmotors_cars ), $panmotors_i, $panmotors_n ),
	);
	$panmotors_i     += $panmotors_n;
}

$panmotors_specs = static function ( $car ) use ( $panmotors_label ) {
	return array_filter(
		array(
			$panmotors_label( 'year', __( 'Year', 'panmotors' ) )           => $car['year'] ?? '',
			$panmotors_label( 'engine', __( 'Engine', 'panmotors' ) )       => $car['engine'] ?? '',
			$panmotors_label( 'power', __( 'Power', 'panmotors' ) )         => $car['power'] ?? '',
			$panmotors_label( 'sprint', __( 'Acceleration', 'panmotors' ) ) => $car['acceleration'] ?? '',
			$panmotors_label( 'gearbox', __( 'Gearbox', 'panmotors' ) )     => $car['gearbox'] ?? '',
			$panmotors_label( 'colour', __( 'Colour', 'panmotors' ) )       => $car['colour'] ?? '',
			$panmotors_label( 'mileage', __( 'Mileage', 'panmotors' ) )     => $car['mileage'] ?? '',
		),
		static fn( $v ) => '' !== trim( (string) $v )
	);
};
$panmotors_total = count( $panmotors_cars );
$panmotors_pad   = static fn( $n ) => str_pad( (string) $n, 2, '0', STR_PAD_LEFT );
// Block editor preview: no script runs there, so the filter buttons and the first car sheet are
// shown as they are (editor.css places the sheet under the grid), and every field has a preview.
$panmotors_preview = ! empty( $args['preview'] );
?>
<section class="pm-cars pm-pad" data-cars>
	<?php if ( $panmotors_intro ) : ?>
		<p class="pm-cars__intro"><?php echo esc_html( $panmotors_intro ); ?></p>
	<?php endif; ?>

	<?php if ( count( $panmotors_marques ) > 1 ) : ?>
		<div class="pm-cars__filters" role="group" aria-label="<?php esc_attr_e( 'Show cars by marque', 'panmotors' ); ?>" data-cars-filters data-hero-in="3"<?php echo $panmotors_preview ? '' : ' hidden'; ?>>
			<button type="button" class="pm-cars__filter" aria-pressed="true" data-filter=""><?php echo esc_html( $panmotors_label( 'all', ( '' !== trim( (string) ( $args['all'] ?? '' ) ) ? trim( (string) $args['all'] ) : __( 'All', 'panmotors' ) ) ) ); ?> <span class="pm-cars__count"><?php echo esc_html( (string) $panmotors_total ); ?></span></button>
			<?php foreach ( $panmotors_marques as $panmotors_m => $panmotors_count ) : ?>
				<button type="button" class="pm-cars__filter" aria-pressed="false" data-filter="<?php echo esc_attr( sanitize_title( $panmotors_m ) ); ?>"><?php echo esc_html( $panmotors_m ); ?> <span class="pm-cars__count"><?php echo esc_html( (string) $panmotors_count ); ?></span></button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="pm-cars__grid" data-cars-grid>
		<?php foreach ( $panmotors_rows as $panmotors_row ) : ?>
			<div class="pm-cars__row" data-rise>
				<?php foreach ( $panmotors_row['cars'] as $panmotors_j => $panmotors_k ) : ?>
					<?php
					$panmotors_car  = $panmotors_cars[ $panmotors_k ];
					$panmotors_wide = ( 2 === $panmotors_row['size'] && 0 === $panmotors_j ) || ( 3 === $panmotors_row['size'] && 1 === $panmotors_j );
					$panmotors_img  = (int) ( $panmotors_car['image'] ?? 0 );
					$panmotors_line = array_filter( array( $panmotors_car['power'] ?? '', $panmotors_car['acceleration'] ?? '', $panmotors_car['mileage'] ?? '' ), static fn( $v ) => '' !== trim( (string) $v ) );
					?>
					<article class="pm-car<?php echo $panmotors_wide ? ' pm-car--wide' : ''; ?>" data-car="<?php echo esc_attr( (string) $panmotors_car['id'] ); ?>" data-marque="<?php echo esc_attr( sanitize_title( (string) $panmotors_car['marque'] ) ); ?>" data-zoom>
						<?php if ( $panmotors_img ) : ?>
							<div class="pm-media pm-car__media">
								<?php
								echo wp_get_attachment_image(
									$panmotors_img,
									'pm-tile',
									false,
									array(
										'alt'     => '',
										'sizes'   => '(max-width: 880px) calc(100vw - 40px), 55vw',
										'loading' => 'lazy',
									)
								);
								?>
							</div>
						<?php endif; ?>
						<div class="pm-car__shade" aria-hidden="true"></div>
						<p class="pm-car__top">
							<span><?php echo esc_html( $panmotors_no . ' ' . $panmotors_pad( $panmotors_k + 1 ) ); ?></span>
							<?php if ( ! empty( $panmotors_car['year'] ) ) : ?>
								<span><?php echo esc_html( (string) $panmotors_car['year'] ); ?></span>
							<?php endif; ?>
						</p>
						<div class="pm-car__copy">
							<?php if ( ! empty( $panmotors_car['marque'] ) ) : ?>
								<p class="pm-car__marque"><?php echo esc_html( (string) $panmotors_car['marque'] ); ?></p>
							<?php endif; ?>
							<h2 class="pm-car__model"><?php echo esc_html( (string) $panmotors_car['model_name'] ); ?></h2>
							<?php if ( $panmotors_line ) : ?>
								<p class="pm-car__spec">
									<?php foreach ( $panmotors_line as $panmotors_v ) : ?>
										<span><?php echo esc_html( (string) $panmotors_v ); ?></span>
									<?php endforeach; ?>
								</p>
							<?php endif; ?>
						</div>
						<button type="button" class="pm-car__open" aria-haspopup="dialog" aria-controls="pm-cars-dialog" data-car-open="<?php echo esc_attr( (string) $panmotors_car['id'] ); ?>">
							<span class="screen-reader-text">
								<?php
								/* translators: %s: marque and model, e.g. "Porsche 911 Turbo S". */
								echo esc_html( sprintf( __( 'Open the car sheet: %s', 'panmotors' ), trim( $panmotors_car['marque'] . ' ' . $panmotors_car['model_name'] ) ) );
								?>
							</span>
						</button>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<dialog class="pm-lightbox" id="pm-cars-dialog" data-cars-dialog<?php echo $panmotors_preview ? ' open' : ''; ?>>
		<?php foreach ( $panmotors_cars as $panmotors_k => $panmotors_car ) : ?>
			<?php
			$panmotors_id    = (string) $panmotors_car['id'];
			$panmotors_img   = (int) ( $panmotors_car['image'] ?? 0 );
			$panmotors_sheet = $panmotors_specs( $panmotors_car );
			?>
			<article class="pm-sheet<?php echo ( $panmotors_preview && 0 === $panmotors_k ) ? ' is-current' : ''; ?>" data-sheet="<?php echo esc_attr( $panmotors_id ); ?>" aria-labelledby="pm-sheet-title-<?php echo esc_attr( $panmotors_id ); ?>"<?php echo ( $panmotors_preview && 0 === $panmotors_k ) ? '' : ' hidden'; ?>>
				<div class="pm-sheet__media">
					<?php
					if ( $panmotors_img ) {
						echo wp_get_attachment_image(
							$panmotors_img,
							'pm-tile',
							false,
							array(
								'sizes'   => '(max-width: 880px) calc(100vw - 80px), 60vw',
								'loading' => 'lazy',
							)
						);
					}
					?>
				</div>
				<div class="pm-sheet__info">
					<div class="pm-sheet__top">
						<p class="pm-sheet__marque"><?php echo esc_html( (string) ( $panmotors_car['marque'] ?? '' ) ); ?></p>
						<button type="button" class="pm-sheet__button" data-lb-close aria-label="<?php esc_attr_e( 'Close', 'panmotors' ); ?>"><span aria-hidden="true">&#10005;</span></button>
					</div>
					<h2 class="pm-sheet__title" id="pm-sheet-title-<?php echo esc_attr( $panmotors_id ); ?>"><?php echo esc_html( (string) $panmotors_car['model_name'] ); ?></h2>
					<?php if ( ! empty( $panmotors_car['note'] ) ) : ?>
						<p class="pm-sheet__note"><?php echo esc_html( (string) $panmotors_car['note'] ); ?></p>
					<?php endif; ?>
					<?php if ( $panmotors_sheet ) : ?>
						<dl class="pm-sheet__specs">
							<?php foreach ( $panmotors_sheet as $panmotors_dt => $panmotors_dd ) : ?>
								<div class="pm-sheet__spec"><dt><?php echo esc_html( $panmotors_dt ); ?></dt><dd><?php echo esc_html( (string) $panmotors_dd ); ?></dd></div>
							<?php endforeach; ?>
						</dl>
					<?php endif; ?>
					<div class="pm-sheet__actions">
						<?php if ( $panmotors_enquire && $panmotors_link ) : ?>
							<a class="pm-sheet__enquire" href="<?php echo esc_url( $panmotors_link ); ?>"><?php echo esc_html( $panmotors_enquire ); ?> <span aria-hidden="true">&rarr;</span></a>
						<?php endif; ?>
						<button type="button" class="pm-sheet__button pm-sheet__button--nav" data-lb-prev aria-label="<?php esc_attr_e( 'Previous car', 'panmotors' ); ?>"><span aria-hidden="true">&larr;</span></button>
						<button type="button" class="pm-sheet__button pm-sheet__button--nav" data-lb-next aria-label="<?php esc_attr_e( 'Next car', 'panmotors' ); ?>"><span aria-hidden="true">&rarr;</span></button>
						<p class="pm-sheet__counter"><span data-lb-pos><?php echo esc_html( $panmotors_pad( $panmotors_k + 1 ) ); ?></span> / <span data-lb-total><?php echo esc_html( $panmotors_pad( $panmotors_total ) ); ?></span></p>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</dialog>
</section>
