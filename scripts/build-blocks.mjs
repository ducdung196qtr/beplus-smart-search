/**
 * build-blocks.mjs
 * Builds all blocks in ./blocks using esbuild (Nextora theme pattern).
 *
 * Usage:
 *   node ./scripts/build-blocks.mjs           → production build
 *   node ./scripts/build-blocks.mjs --watch   → watch mode
 */

import esbuild from 'esbuild';
import { glob } from 'glob';
import fs from 'fs';
import path from 'path';

const isWatch = process.argv.includes( '--watch' );

function toWpHandle( pkg ) {
	return pkg.replace( '@wordpress/', 'wp-' ).replace( /\//g, '-' );
}

function writeAssetFile( entryFile, dependencies ) {
	const dir = path.dirname( entryFile );
	const version = Date.now().toString();
	const depsPhp = dependencies.map( ( d ) => `'${d}'` ).join( ', ' );
	const content = `<?php return [ 'dependencies' => [ ${depsPhp} ], 'version' => '${version}' ];\n`;
	fs.writeFileSync( path.join( dir, 'index.asset.php' ), content, 'utf8' );
}

const WP_PACKAGES = [
	'@wordpress/blocks',
	'@wordpress/block-editor',
	'@wordpress/server-side-render',
	'@wordpress/components',
	'@wordpress/element',
	'@wordpress/i18n',
	'@wordpress/hooks',
	'@wordpress/data',
	'@wordpress/core-data',
	'@wordpress/compose',
	'@wordpress/primitives',
	'@wordpress/blob',
	'@wordpress/notices',
	'@wordpress/plugins',
];

const WP_HANDLES = WP_PACKAGES.map( toWpHandle );

const wpExternalsPlugin = {
	name: 'wp-externals',
	setup( build ) {
		build.onResolve( { filter: /^@wordpress\// }, ( args ) => ( {
			path: args.path,
			namespace: 'wp-external',
		} ) );

		build.onLoad( { filter: /.*/, namespace: 'wp-external' }, ( args ) => {
			const globalName = args.path
				.replace( '@wordpress/', '' )
				.replace( /-([a-z])/g, ( _, l ) => l.toUpperCase() );

			return {
				contents: `module.exports = window.wp['${globalName}'];`,
				loader: 'js',
			};
		} );
	},
};

const entryPoints = await glob( './blocks/*/index.{ts,tsx}' );
// Source: blocks/*/view.source.ts → output: blocks/*/view.js (never bundle view.js as entry).
const viewEntryPoints = await glob( './blocks/*/view.source.ts' );

if ( entryPoints.length === 0 && ! isWatch ) {
	console.warn( '⚠️  No block entry points found in ./blocks/*/index.{ts,tsx}' );
}

if ( ! isWatch && entryPoints.length > 0 ) {
	console.log( `🔍 Found ${entryPoints.length} block(s):`, entryPoints );
}

/** @type {import('esbuild').BuildOptions} */
const buildOptions = {
	entryPoints,
	bundle: true,
	format: 'iife',
	platform: 'browser',
	target: 'es2020',
	jsx: 'automatic',
	jsxImportSource: 'react',
	sourcemap: isWatch ? 'inline' : false,
	minify: ! isWatch,
	logLevel: isWatch ? 'warning' : 'info',
	plugins: [ wpExternalsPlugin ],
	define: {
		'process.env.NODE_ENV': isWatch ? '"development"' : '"production"',
	},
	outdir: '.',
	outbase: '.',
};

const assetWriterPlugin = {
	name: 'asset-writer',
	setup( build ) {
		build.onEnd( ( result ) => {
			if ( result.errors.length ) {
				return;
			}
			entryPoints.forEach( ( entry ) => {
				const outFile = entry.replace( /\.tsx?$/, '.js' );
				if ( fs.existsSync( outFile ) ) {
					writeAssetFile( outFile, WP_HANDLES );
				}
			} );
			const t = new Date().toTimeString().slice( 0, 8 );
			console.log( `${t}  blocks rebuilt (${entryPoints.length})` );
		} );
	},
};

const viewBuildOptions = {
	entryPoints: viewEntryPoints,
	bundle: true,
	format: 'iife',
	platform: 'browser',
	target: 'es2020',
	minify: ! isWatch,
	logLevel: isWatch ? 'warning' : 'info',
	sourcemap: isWatch ? 'inline' : false,
	allowOverwrite: true,
	outdir: '.',
	outbase: '.',
};

async function buildViewBundles() {
	if ( ! viewEntryPoints.length ) {
		return;
	}

	for ( const entry of viewEntryPoints ) {
		const outfile = entry.replace( /\.source\.ts$/, '.bundle.js' );
		const result = await esbuild.build( {
			...viewBuildOptions,
			entryPoints: [ entry ],
			outfile,
			outdir: undefined,
			outbase: undefined,
		} );

		if ( result.errors.length ) {
			console.error( '❌ View script build errors:', result.errors );
			process.exit( 1 );
		}

		if ( fs.existsSync( outfile ) ) {
			console.log( `  ✓ ${outfile}` );
		}
	}
}

if ( entryPoints.length === 0 && viewEntryPoints.length === 0 ) {
	process.exit( 0 );
}

if ( isWatch ) {
	if ( entryPoints.length ) {
		const ctx = await esbuild.context( {
			...buildOptions,
			plugins: [ ...buildOptions.plugins, assetWriterPlugin ],
		} );
		await ctx.watch();
		console.log(
			`Watching ${entryPoints.length} block(s) under ./blocks/*/index.{ts,tsx}  ·  Ctrl+C to stop`
		);
	}

	if ( viewEntryPoints.length ) {
		const vctx = await esbuild.context( {
			plugins: [
				{
					name: 'view-rebuild-log',
					setup( b ) {
						b.onEnd( () => {
							const t = new Date().toTimeString().slice( 0, 8 );
							console.log(
								`${t}  view script(s) rebuilt (${viewEntryPoints.length})`
							);
						} );
					},
				},
			],
		} );

		for ( const entry of viewEntryPoints ) {
			const outfile = entry.replace( /\.source\.ts$/, '.bundle.js' );
			await vctx.watch( {
				...viewBuildOptions,
				entryPoints: [ entry ],
				outfile,
				outdir: undefined,
				outbase: undefined,
			} );
		}

		console.log(
			`Watching ${viewEntryPoints.length} view bundle(s) under ./blocks/*/view.source.ts`
		);
	}
} else {
	if ( entryPoints.length ) {
		const result = await esbuild.build( buildOptions );

		if ( result.errors.length ) {
			console.error( '❌ Build errors:', result.errors );
			process.exit( 1 );
		}

		entryPoints.forEach( ( entry ) => {
			const outFile = entry.replace( /\.tsx?$/, '.js' );
			if ( fs.existsSync( outFile ) ) {
				writeAssetFile( outFile, WP_HANDLES );
				console.log( `  ✓ ${outFile}` );
				console.log( `  ✓ ${path.dirname( outFile )}/index.asset.php` );
			}
		} );
	}

	await buildViewBundles();

	console.log( '\n✅ All blocks built successfully.' );
}
