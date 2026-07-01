/**
 * gen-block.mjs
 * Scaffolds a new dynamic block inside ./blocks/<name>/
 *
 * Usage:
 *   npm run gen -- --name my-block
 *   node ./scripts/gen-block.mjs --name my-block --title "My Block"
 */

import fs from 'fs';
import path from 'path';

const args = process.argv.slice( 2 );
const get = ( flag ) => {
	const i = args.indexOf( flag );
	return i !== -1 ? args[ i + 1 ] : null;
};

const name = get( '--name' );
const ns = 'beplus-fast-product-filter-live-search-for-woocommerce';

if ( ! name ) {
	console.error( '❌  Missing --name argument.' );
	console.error( '   Usage: npm run gen -- --name my-block' );
	process.exit( 1 );
}

if ( ! /^[a-z][a-z0-9-]*$/.test( name ) ) {
	console.error( '❌  Block name must be lowercase letters, numbers, and hyphens only.' );
	process.exit( 1 );
}

const title = get( '--title' ) ?? name.replace( /-/g, ' ' ).replace( /\b\w/g, ( c ) => c.toUpperCase() );
const category = get( '--category' ) ?? 'beplus-fast-product-filter-live-search-for-woocommerce';
const pascal = name.replace( /(^|-)([a-z])/g, ( _, __, c ) => c.toUpperCase() );
const blockDir = path.resolve( `./blocks/${name}` );

if ( fs.existsSync( blockDir ) ) {
	console.error( `❌  Block "${name}" already exists at ${blockDir}` );
	process.exit( 1 );
}

const blockJson = {
	$schema: 'https://schemas.wp.org/trunk/block.json',
	apiVersion: 3,
	name: `${ns}/${name}`,
	title,
	category,
	description: `${title} block.`,
	keywords: [ name, 'search', ns ],
	textdomain: ns,
	supports: {
		html: false,
		align: [ 'wide', 'full' ],
		spacing: {
			margin: true,
			padding: true,
		},
	},
	attributes: {
		heading: { type: 'string', default: '' },
	},
	editorScript: 'file:./index.js',
	render: 'file:./render.php',
	style: 'file:./style.css',
};

const files = {
	'block.json': JSON.stringify( blockJson, null, '\t' ),
	'index.tsx': `import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
`,
	'edit.tsx': `import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';

interface Attributes {
	heading: string;
}

interface EditProps {
	attributes: Attributes;
	setAttributes: ( attrs: Partial< Attributes > ) => void;
}

export default function ${pascal}Edit( { attributes, setAttributes }: EditProps ) {
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( '${title} Settings', '${ns}' ) } initialOpen>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<RichText
					tagName="h2"
					value={ attributes.heading }
					onChange={ ( val ) => setAttributes( { heading: val } ) }
					placeholder={ __( 'Enter heading…', '${ns}' ) }
				/>
			</div>
		</>
	);
}
`,
	'render.php': `<?php
/**
 * ${title} — dynamic block render template.
 *
 * @package BePlusFastProductFilterLiveSearch
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks HTML.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$heading = wp_kses( $attributes['heading'] ?? '', array( 'strong' => array(), 'em' => array() ) );

if ( ! $heading ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes();
?>

<div <?php echo $wrapper_attributes; ?>>
	<h2 class="beplus-fast-product-filter-live-search-for-woocommerce__${name.replace( /-/g, '-' ) }-heading"><?php echo $heading; ?></h2>
</div>
`,
	'style.css': `.wp-block-${ns.replace( '/', '-' )}-${name} {
\t/* Block styles */
}
`,
};

fs.mkdirSync( blockDir, { recursive: true } );

for ( const [ filename, content ] of Object.entries( files ) ) {
	fs.writeFileSync( path.join( blockDir, filename ), content, 'utf8' );
}

console.log( `\n✅ Block "${ns}/${name}" created at ./blocks/${name}/\n` );
console.log( '   Next steps:' );
console.log( '   1. Run: npm run build:blocks' );
console.log( '   2. Reload wp-admin — block appears in the inserter\n' );
