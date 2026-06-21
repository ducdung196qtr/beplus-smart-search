/** @type {import('lint-staged').Config} */
module.exports = {
	'*.php': ( files ) => {
		const source = files.filter( ( file ) => ! file.endsWith( 'index.asset.php' ) );
		if ( source.length === 0 ) {
			return [];
		}

		return `bash scripts/lint-staged-php.sh ${ source.map( ( file ) => JSON.stringify( file ) ).join( ' ' ) }`;
	},
	'{admin/js,blocks}/**/*.{ts,tsx}': () => 'npm run typecheck',
};
