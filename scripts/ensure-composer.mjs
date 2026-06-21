#!/usr/bin/env node
/**
 * Ensure Composer vendor/ is installed before commit/push.
 *
 * Usage:
 *   npm run ensure:composer
 */

import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..' );
const PHPSTAN = path.join( ROOT, 'vendor', 'bin', 'phpstan' );
const PHPSTAN_BAT = path.join( ROOT, 'vendor', 'bin', 'phpstan.bat' );

function hasVendor() {
	return fs.existsSync( PHPSTAN ) || fs.existsSync( PHPSTAN_BAT );
}

function runComposerInstall() {
	console.log( 'vendor/ missing — running npm run composer:install …' );
	const result = spawnSync( 'npm', [ 'run', 'composer:install' ], {
		cwd: ROOT,
		stdio: 'inherit',
		shell: true,
	} );
	return result.status === 0;
}

if ( hasVendor() ) {
	console.log( 'Composer OK: vendor/bin/phpstan found.' );
	process.exit( 0 );
}

if ( runComposerInstall() && hasVendor() ) {
	console.log( 'Composer dependencies installed.' );
	process.exit( 0 );
}

console.error( `
Composer dependencies are not installed.

Do NOT run: composer install
Use instead:  npm run composer:install

If PHP is missing:
  1) Local → Open site shell → npm run composer:install
  2) npm run find-php → set PHP_BIN in .env
  3) winget install PHP.PHP.8.2
` );
process.exit( 1 );
