/**
 * Block editor (D11): keep the client inside the design.
 * Text formats: bold, italic and link only. Core block styles the design doesn't have are removed.
 * Plain script, no build step. Enqueued by inc/blocks.php on enqueue_block_editor_assets.
 */
( () => {
	const { domReady, blocks, richText, data } = window.wp;

	const KEEP_FORMATS = [ 'core/bold', 'core/italic', 'core/link' ];
	const STYLES = {
		'core/button': [ 'fill', 'outline' ],
		'core/image': [ 'default', 'rounded' ],
		'core/quote': [ 'default', 'plain' ],
	};

	domReady( () => {
		data.select( 'core/rich-text' )
			.getFormatTypes()
			.map( ( format ) => format.name )
			.filter( ( name ) => ! KEEP_FORMATS.includes( name ) )
			.forEach( ( name ) => richText.unregisterFormatType( name ) );

		Object.entries( STYLES ).forEach( ( [ block, styles ] ) => {
			styles.forEach( ( style ) => blocks.unregisterBlockStyle( block, style ) );
		} );
	} );
} )();
