/**
 * build-admin.mjs
 * Builds admin settings script.
 *
 * Usage:
 *   node ./scripts/build-admin.mjs           → production build
 *   node ./scripts/build-admin.mjs --watch   → watch mode
 */

import esbuild from 'esbuild';
import fs from 'fs';
import path from 'path';

const isWatch = process.argv.includes( '--watch' );
const entry = './admin/js/settings.ts';
const outFile = './admin/js/settings.js';
const outDir = path.dirname( outFile );

function writeAssetFile() {
	const version = Date.now().toString();
	const content = `<?php return [ 'dependencies' => [ 'jquery', 'wp-color-picker' ], 'version' => '${version}' ];\n`;
	fs.writeFileSync( path.join( outDir, 'settings.asset.php' ), content, 'utf8' );
}

/** @type {import('esbuild').BuildOptions} */
const buildOptions = {
	entryPoints: [ entry ],
	outfile: outFile,
	bundle: true,
	format: 'iife',
	platform: 'browser',
	target: 'es2020',
	sourcemap: isWatch ? 'inline' : false,
	minify: ! isWatch,
	logLevel: isWatch ? 'warning' : 'info',
};

const assetWriterPlugin = {
	name: 'asset-writer',
	setup( build ) {
		build.onEnd( ( result ) => {
			if ( result.errors.length ) {
				return;
			}
			writeAssetFile();
			const t = new Date().toTimeString().slice( 0, 8 );
			console.log( `${t}  admin settings rebuilt` );
		} );
	},
};

if ( isWatch ) {
	const ctx = await esbuild.context( {
		...buildOptions,
		plugins: [ assetWriterPlugin ],
	} );
	await ctx.watch();
	console.log( 'Watching admin settings (admin/js/settings.ts)  ·  Ctrl+C to stop' );
} else {
	const result = await esbuild.build( buildOptions );

	if ( result.errors.length ) {
		console.error( '❌ Admin build errors:', result.errors );
		process.exit( 1 );
	}

	writeAssetFile();
	console.log( `  ✓ ${outFile}` );
	console.log( `  ✓ ${outDir}/settings.asset.php` );
	console.log( '\n✅ Admin settings built successfully.' );
}
