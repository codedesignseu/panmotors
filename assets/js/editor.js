/**
 * Block editor (D11): keep the client inside the design.
 * Text formats: bold, italic and link only. Core block styles the design doesn't have are removed.
 * Embeds: YouTube, Vimeo and Instagram only.
 * Plain script, no build step. Enqueued by inc/blocks.php on enqueue_block_editor_assets.
 */
( () => {
	const { domReady, blocks, richText, data } = window.wp;

	const KEEP_FORMATS = [ 'core/bold', 'core/italic', 'core/link' ];
	const KEEP_EMBEDS = [ 'youtube', 'vimeo', 'instagram' ];
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

		( blocks.getBlockVariations( 'core/embed' ) || [] )
			.map( ( variation ) => variation.name )
			.filter( ( name ) => ! KEEP_EMBEDS.includes( name ) )
			.forEach( ( name ) => blocks.unregisterBlockVariation( 'core/embed', name ) );

		// YouTube as the default variation replaces the generic "Embed" item in the inserter.
		const youtube = blocks.getBlockVariations( 'core/embed' ).find( ( v ) => 'youtube' === v.name );
		if ( youtube ) {
			blocks.unregisterBlockVariation( 'core/embed', 'youtube' );
			blocks.registerBlockVariation( 'core/embed', { ...youtube, isDefault: true } );
		}

		Object.entries( STYLES ).forEach( ( [ block, styles ] ) => {
			styles.forEach( ( style ) => blocks.unregisterBlockStyle( block, style ) );
		} );
	} );
} )();
