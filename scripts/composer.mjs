#!/usr/bin/env node
/**
 * Run Composer without a global `composer` binary.
 *
 * - Downloads tools/composer.phar on first use
 * - Finds PHP from PHP_BIN, PATH, or Local WP (lightning-services)
 *
 * Usage:
 *   node scripts/composer.mjs install
 *   npm run composer:install
 */

import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import https from 'node:https';
import path from 'node:path';
import { findPhp, loadEnvFile, phpArgs, printPhpHelp, ROOT } from './php-env.mjs';

const TOOLS_DIR = path.join( ROOT, 'tools' );
const COMPOSER_PHAR = path.join( TOOLS_DIR, 'composer.phar' );
const COMPOSER_URL = 'https://getcomposer.org/download/latest-stable/composer.phar';

function downloadComposerPhar() {
	return new Promise( ( resolve, reject ) => {
		fs.mkdirSync( TOOLS_DIR, { recursive: true } );

		const file = fs.createWriteStream( COMPOSER_PHAR );
		https
			.get( COMPOSER_URL, ( response ) => {
				if ( response.statusCode && response.statusCode >= 300 && response.statusCode < 400 && response.headers.location ) {
					https
						.get( response.headers.location, ( redirect ) => {
							redirect.pipe( file );
							file.on( 'finish', () => {
								file.close( () => resolve( COMPOSER_PHAR ) );
							} );
						} )
						.on( 'error', reject );
					return;
				}

				response.pipe( file );
				file.on( 'finish', () => {
					file.close( () => resolve( COMPOSER_PHAR ) );
				} );
			} )
			.on( 'error', reject );
	} );
}

async function ensureComposerPhar() {
	if ( fs.existsSync( COMPOSER_PHAR ) ) {
		return COMPOSER_PHAR;
	}

	console.log( 'Downloading Composer phar → tools/composer.phar …' );
	await downloadComposerPhar();
	console.log( 'Composer phar ready.' );
	return COMPOSER_PHAR;
}

async function main() {
	loadEnvFile();

	const args = process.argv.slice( 2 );
	if ( args.length === 0 ) {
		args.push( '--version' );
	}

	const phpInfo = findPhp();
	if ( ! phpInfo ) {
		printPhpHelp();
		process.exit( 1 );
	}

	if ( phpInfo.phpIni ) {
		console.log( `Using Local WP php.ini: ${phpInfo.phpIni}` );
	}

	const phar = await ensureComposerPhar();
	const result = spawnSync(
		phpInfo.php,
		phpArgs( phpInfo.php, phpInfo.phpIni, [ phar, ...args ] ),
		{
			cwd: ROOT,
			stdio: 'inherit',
			shell: false,
		},
	);

	process.exit( result.status ?? 1 );
}

main().catch( ( error ) => {
	console.error( error );
	process.exit( 1 );
} );
