#!/usr/bin/env node
/**
 * lint-staged helper: format staged PHP files (skip generated manifests).
 * Uses the same PHP auto-discovery as scripts/composer.mjs (works on Windows).
 */

import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { findPhp, loadEnvFile, phpArgs, printPhpHelp, ROOT } from './php-env.mjs';

loadEnvFile();

const tmpDir = path.join( ROOT, '.php-cs-fixer-tmp' );
fs.mkdirSync( tmpDir, { recursive: true } );
process.env.TEMP = tmpDir;
process.env.TMP = tmpDir;

const skipPatterns = [ 'index.asset.php', 'settings.asset.php' ];
const files = process.argv
	.slice( 2 )
	.filter( ( file ) => ! skipPatterns.some( ( pattern ) => file.endsWith( pattern ) ) );

if ( files.length === 0 ) {
	process.exit( 0 );
}

const phpInfo = findPhp();
if ( ! phpInfo ) {
	printPhpHelp();
	process.exit( 1 );
}

const fixer = path.join( ROOT, 'vendor', 'bin', 'php-cs-fixer' );
if ( ! fs.existsSync( fixer ) ) {
	console.error( 'Missing vendor/bin/php-cs-fixer — run: npm run composer:install' );
	process.exit( 1 );
}

const result = spawnSync(
	phpInfo.php,
	phpArgs( phpInfo.php, phpInfo.phpIni, [
		fixer,
		'fix',
		'--config=.php-cs-fixer.dist.php',
		'--allow-unsupported-php-version=yes',
		'--sequential',
		'--',
		...files,
	] ),
	{
		cwd: ROOT,
		stdio: 'inherit',
		shell: false,
	},
);

process.exit( result.status ?? 1 );
