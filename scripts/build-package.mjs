#!/usr/bin/env node
/**
 * Package the plugin into a distributable ZIP for WordPress.
 * Cross-platform (Windows/macOS/Linux) — uses archiver, not the zip CLI.
 *
 * Ships runtime files only (PHP, built JS/CSS, block assets, readme.txt).
 * Dev tooling (.env, composer wrappers, TS sources, docs) is excluded.
 *
 * Usage:
 *   npm run build:package
 *   node scripts/build-package.mjs
 */

import archiver from 'archiver';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { globSync } from 'glob';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
const ROOT = path.resolve( __dirname, '..' );
const PLUGIN_SLUG = path.basename( ROOT );

/**
 * Allowlist — only paths needed to run the plugin in WordPress.
 * Run `npm run build` before packaging so block/admin JS is up to date.
 */
const INCLUDE_GLOBS = [
	'beplus-smart-search.php',
	'readme.txt',
	'src/**/*.php',
	'includes/**/*.php',
	'admin/**/*.php',
	'admin/css/**',
	'admin/js/*.js',
	'admin/js/*.asset.php',
	'blocks/**/block.json',
	'blocks/**/render.php',
	'blocks/**/style.css',
	'blocks/**/index.js',
	'blocks/**/index.asset.php',
	'blocks/**/view.bundle.js',
	'languages/**',
];

function readVersion() {
	const bootstrap = fs.readFileSync(
		path.join( ROOT, 'beplus-smart-search.php' ),
		'utf8',
	);
	const m = bootstrap.match(
		/define\(\s*'BEPLUS_SMART_SEARCH_VERSION'\s*,\s*'([^']+)'\s*\)/,
	);
	if ( ! m ) {
		console.error(
			'Could not parse BEPLUS_SMART_SEARCH_VERSION from beplus-smart-search.php',
		);
		process.exit( 1 );
	}
	return m[ 1 ];
}

function collectFiles() {
	/** @type {Set<string>} */
	const files = new Set();

	for ( const pattern of INCLUDE_GLOBS ) {
		for ( const rel of globSync( pattern, {
			cwd: ROOT,
			dot: false,
			nodir: true,
			nocase: process.platform === 'win32',
		} ) ) {
			files.add( rel.split( path.sep ).join( '/' ) );
		}
	}

	return [ ...files ].sort();
}

function createZip( zipPath, files ) {
	return new Promise( ( resolve, reject ) => {
		const output = fs.createWriteStream( zipPath );
		const archive = archiver( 'zip', { zlib: { level: 9 } } );

		output.on( 'close', resolve );
		archive.on( 'error', reject );
		output.on( 'error', reject );

		archive.pipe( output );

		for ( const rel of files ) {
			const abs = path.join( ROOT, rel );
			if ( ! fs.existsSync( abs ) ) {
				continue;
			}
			const entryName = `${PLUGIN_SLUG}/${rel}`;
			archive.file( abs, { name: entryName } );
		}

		archive.finalize();
	} );
}

const version = readVersion();
const zipName = `${PLUGIN_SLUG}-v${version}.zip`;
const zipPath = path.join( ROOT, zipName );

if ( fs.existsSync( zipPath ) ) {
	fs.unlinkSync( zipPath );
}

const files = collectFiles();

if ( files.length === 0 ) {
	console.error( 'No files matched the release allowlist. Run npm run build first.' );
	process.exit( 1 );
}

const missingBuild = [
	'blocks/advanced-woo-search/index.js',
	'blocks/advanced-woo-search/view.bundle.js',
	'admin/js/settings.js',
].filter( ( f ) => ! files.includes( f ) );

if ( missingBuild.length > 0 ) {
	console.warn(
		'Warning: built assets missing — run `npm run build` before packaging:',
		missingBuild.join( ', ' ),
	);
}

console.log( `Packaging ${PLUGIN_SLUG} v${version} → ${zipName} (${files.length} files)` );

await createZip( zipPath, files );

const { size } = fs.statSync( zipPath );
console.log( `Created ${zipPath} (${( size / 1024 ).toFixed( 1 )} KB)` );
