#!/usr/bin/env node
/**
 * Package the plugin into a distributable ZIP for WordPress.
 *
 * Reads BEPLUS_SMART_SEARCH_VERSION from beplus-smart-search.php and creates
 * beplus-smart-search-v{version}.zip in the plugin root directory.
 *
 * Usage:
 *   npm run build:package
 *   node scripts/build-package.mjs
 */

import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
const ROOT = path.resolve( __dirname, '..' );
const PLUGIN_SLUG = path.basename( ROOT );

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

function run( cmd, opts = {} ) {
	return execSync( cmd, { stdio: 'inherit', cwd: ROOT, ...opts } );
}

const version = readVersion();
const zipName = `${PLUGIN_SLUG}-v${version}.zip`;
const zipPath = path.join( ROOT, zipName );

if ( fs.existsSync( zipPath ) ) {
	fs.unlinkSync( zipPath );
}

const excludes = [
	`${PLUGIN_SLUG}/node_modules/*`,
	`${PLUGIN_SLUG}/vendor/*`,
	'*.zip',

	`${PLUGIN_SLUG}/.git/*`,
	`${PLUGIN_SLUG}/.github/*`,
	`${PLUGIN_SLUG}/.husky/*`,
	`${PLUGIN_SLUG}/.gitignore`,
	`${PLUGIN_SLUG}/.nvmrc`,
	`${PLUGIN_SLUG}/.lintstagedrc.cjs`,

	`${PLUGIN_SLUG}/.cursor/*`,
	`${PLUGIN_SLUG}/AGENTS.md`,
	`${PLUGIN_SLUG}/Document Plugin.md`,

	`${PLUGIN_SLUG}/scripts/*`,
	`${PLUGIN_SLUG}/tools/*`,
	`${PLUGIN_SLUG}/docs/*`,

	`${PLUGIN_SLUG}/blocks/*/*.ts`,
	`${PLUGIN_SLUG}/blocks/*/*.tsx`,
	`${PLUGIN_SLUG}/blocks/*/*.d.ts`,
	`${PLUGIN_SLUG}/admin/js/*.ts`,

	`${PLUGIN_SLUG}/composer.json`,
	`${PLUGIN_SLUG}/composer.lock`,
	`${PLUGIN_SLUG}/package.json`,
	`${PLUGIN_SLUG}/package-lock.json`,
	`${PLUGIN_SLUG}/phpstan-bootstrap.php`,
	`${PLUGIN_SLUG}/phpstan.neon`,
	`${PLUGIN_SLUG}/tsconfig.json`,
	`${PLUGIN_SLUG}/.php-cs-fixer.dist.php`,
	`${PLUGIN_SLUG}/.php-cs-fixer.cache`,
];

const excludeArgs = excludes.map( ( e ) => `-x "${e}"` ).join( ' ' );
const cmd = `cd "${path.dirname( ROOT )}" && zip -r "${zipPath}" "${PLUGIN_SLUG}/" ${excludeArgs}`;

console.log( `Packaging ${PLUGIN_SLUG} v${version} → ${zipName}` );
run( cmd );
console.log( `Created ${zipPath}` );
