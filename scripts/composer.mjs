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
import { fileURLToPath } from 'node:url';
import { globSync } from 'glob';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
const ROOT = path.resolve( __dirname, '..' );
const TOOLS_DIR = path.join( ROOT, 'tools' );
const COMPOSER_PHAR = path.join( TOOLS_DIR, 'composer.phar' );
const COMPOSER_URL = 'https://getcomposer.org/download/latest-stable/composer.phar';

function loadEnvFile() {
	const envPath = path.join( ROOT, '.env' );
	if ( ! fs.existsSync( envPath ) ) {
		return;
	}

	for ( const line of fs.readFileSync( envPath, 'utf8' ).split( '\n' ) ) {
		const trimmed = line.trim();
		if ( ! trimmed || trimmed.startsWith( '#' ) || ! trimmed.includes( '=' ) ) {
			continue;
		}

		const eq = trimmed.indexOf( '=' );
		const key = trimmed.slice( 0, eq ).trim();
		let value = trimmed.slice( eq + 1 ).trim();

		if (
			( value.startsWith( '"' ) && value.endsWith( '"' ) )
			|| ( value.startsWith( "'" ) && value.endsWith( "'" ) )
		) {
			value = value.slice( 1, -1 );
		}

		if ( ! process.env[ key ] ) {
			process.env[ key ] = value;
		}
	}
}

function phpWorks( phpBin ) {
	const result = spawnSync( phpBin, [ '-v' ], { stdio: 'ignore', shell: false } );
	return result.status === 0;
}

function findPhpCandidates() {
	const candidates = [];

	if ( process.env.PHP_BIN ) {
		candidates.push( process.env.PHP_BIN );
	}

	candidates.push( 'php' );

	const localBase = process.env.LOCALAPPDATA
		? path.join( process.env.LOCALAPPDATA, 'Programs', 'Local', 'lightning-services' )
		: null;

	if ( localBase && fs.existsSync( localBase ) ) {
		const localPhp = globSync( '**/php.exe', {
			cwd: localBase,
			absolute: true,
			nocase: true,
		} );
		candidates.push( ...localPhp.reverse() );
	}

	const extras = [
		'C:\\laragon\\bin\\php\\php.exe',
		'C:\\xampp\\php\\php.exe',
		'C:\\wamp64\\bin\\php\\php8.2.0\\php.exe',
		'C:\\Program Files\\PHP\\php.exe',
	];

	candidates.push( ...extras );

	return [ ...new Set( candidates.filter( Boolean ) ) ];
}

function findPhp() {
	for ( const candidate of findPhpCandidates() ) {
		if ( candidate !== 'php' && ! fs.existsSync( candidate ) ) {
			continue;
		}

		if ( phpWorks( candidate ) ) {
			return candidate;
		}
	}

	return null;
}

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

function printHelp() {
	console.error( `
Could not find PHP on this machine.

Fix (pick one):

  1) Local WP — open the site in Local → "Open site shell", then run:
       npm run composer:install

  2) Set PHP path in .env (copy .env.example):
       PHP_BIN=C:\\path\\to\\php.exe

  3) Install PHP globally, then reopen the terminal:
       winget install PHP.PHP.8.2

After PHP works, run:
  npm run composer:install
` );
}

async function main() {
	loadEnvFile();

	const args = process.argv.slice( 2 );
	if ( args.length === 0 ) {
		args.push( '--version' );
	}

	const php = findPhp();
	if ( ! php ) {
		printHelp();
		process.exit( 1 );
	}

	const phar = await ensureComposerPhar();
	const result = spawnSync( php, [ phar, ...args ], {
		cwd: ROOT,
		stdio: 'inherit',
		shell: false,
	} );

	process.exit( result.status ?? 1 );
}

main().catch( ( error ) => {
	console.error( error );
	process.exit( 1 );
} );
