/**
 * Shared PHP discovery for Local WP / Laragon / XAMPP on Windows.
 *
 * @package BePlusSmartSearch
 */

import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { globSync } from 'glob';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
export const ROOT = path.resolve( __dirname, '..' );

export function loadEnvFile() {
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

function getLocalPluginSite() {
	if ( ! process.env.APPDATA ) {
		return null;
	}

	const sitesPath = path.join( process.env.APPDATA, 'Local', 'sites.json' );
	if ( ! fs.existsSync( sitesPath ) ) {
		return null;
	}

	try {
		const sites = JSON.parse( fs.readFileSync( sitesPath, 'utf8' ) );
		const pluginRoot = ROOT.replace( /\\/g, '/' ).toLowerCase();
		let fallback = null;

		for ( const [ siteId, site ] of Object.entries( sites ) ) {
			if ( ! site || typeof site !== 'object' ) {
				continue;
			}

			if ( site.name === 'plugin' ) {
				return { siteId, site };
			}

			const rawPath = site.path ?? '';
			const sitePath = rawPath
				.replace( /^~\\?/, `${process.env.USERPROFILE}\\` )
				.replace( /\\/g, '/' )
				.toLowerCase();

			if (
				pluginRoot.includes( sitePath )
				|| pluginRoot.includes( 'local sites/plugin' )
			) {
				fallback = { siteId, site };
			}
		}

		return fallback;
	} catch {
		return null;
	}
}

function findLocalSitePhpIni() {
	const match = getLocalPluginSite();
	if ( ! match ) {
		return null;
	}

	const iniPath = path.join(
		process.env.APPDATA,
		'Local',
		'run',
		match.siteId,
		'conf',
		'php',
		'php.ini',
	);

	return fs.existsSync( iniPath ) ? iniPath : null;
}

export function phpArgs( phpBin, phpIni, args ) {
	const base = phpIni ? [ '-c', phpIni ] : [];
	return [ ...base, ...args ];
}

function phpWorks( phpBin, phpIni = null ) {
	const result = spawnSync( phpBin, phpArgs( phpBin, phpIni, [ '-v' ] ), {
		stdio: 'ignore',
		shell: false,
	} );
	return result.status === 0;
}

function findLocalLightningPhp() {
	const bases = [];

	if ( process.env.APPDATA ) {
		bases.push( path.join( process.env.APPDATA, 'Local', 'lightning-services' ) );
	}

	if ( process.env.LOCALAPPDATA ) {
		bases.push(
			path.join( process.env.LOCALAPPDATA, 'Programs', 'Local', 'lightning-services' ),
			path.join(
				process.env.LOCALAPPDATA,
				'Programs',
				'Local',
				'resources',
				'extraResources',
				'lightning-services',
			),
		);
	}

	const candidates = [];

	for ( const base of bases ) {
		if ( ! base || ! fs.existsSync( base ) ) {
			continue;
		}

		const fromSite = findPhpFromLocalSitesJson( base );
		if ( fromSite ) {
			candidates.push( fromSite );
		}

		const matches = globSync( 'php-*/bin/win64/php.exe', {
			cwd: base,
			absolute: true,
			nocase: true,
		} );
		candidates.push( ...matches.reverse() );
	}

	return candidates;
}

function findPhpFromLocalSitesJson( lightningBase ) {
	const match = getLocalPluginSite();
	if ( ! match ) {
		return null;
	}

	const version = match.site.services?.php?.version;
	if ( ! version ) {
		return null;
	}

	const dirs = globSync( `php-${version}*`, {
		cwd: lightningBase,
		absolute: true,
		nocase: true,
	} );

	for ( const dir of dirs ) {
		const phpExe = path.join( dir, 'bin', 'win64', 'php.exe' );
		if ( fs.existsSync( phpExe ) ) {
			return phpExe;
		}
	}

	return null;
}

function findPhpCandidates() {
	const candidates = [];

	if ( process.env.PHP_BIN ) {
		candidates.push( process.env.PHP_BIN );
	}

	candidates.push( 'php' );
	candidates.push( ...findLocalLightningPhp() );

	const extras = [
		'C:\\laragon\\bin\\php\\php.exe',
		'C:\\xampp\\php\\php.exe',
		'C:\\wamp64\\bin\\php\\php8.2.0\\php.exe',
		'C:\\Program Files\\PHP\\php.exe',
	];

	candidates.push( ...extras );

	return [ ...new Set( candidates.filter( Boolean ) ) ];
}

/**
 * @returns {{ php: string, phpIni: string|null }|null}
 */
export function findPhp() {
	const phpIni = findLocalSitePhpIni();

	for ( const candidate of findPhpCandidates() ) {
		if ( candidate !== 'php' && ! fs.existsSync( candidate ) ) {
			continue;
		}

		if ( phpWorks( candidate, phpIni ) ) {
			return { php: candidate, phpIni };
		}

		if ( phpWorks( candidate ) ) {
			return { php: candidate, phpIni: null };
		}
	}

	return null;
}

export function printPhpHelp() {
	console.error( `
Could not find PHP on this machine.

Fix (pick one):

  1) Start the "plugin" site in Local WP, then run:
       npm run composer:install

  2) Set PHP path in .env (copy .env.example):
       npm run find-php

  3) Install PHP globally, then reopen the terminal:
       winget install PHP.PHP.8.2

After PHP works, run:
  npm run composer:install
` );
}
