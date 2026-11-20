/**
 * Builds `dist/chassis.js`, the script the service providers load. It joins the
 * scripts in `js/` so they run on their own, with no build step in the theme.
 * The output is parsed before it is written, so a broken concat fails the
 * build instead of shipping a `SyntaxError`, and it carries a version banner
 * so a served file says which release it is.
 */

import { execFileSync } from 'node:child_process';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const pkg = JSON.parse( readFileSync( new URL( '../package.json', import.meta.url ), 'utf8' ) );
const banner = `/*! Chassis ${ pkg.version } | GPL-2.0-or-later | https://github.com/techkodeninja/chassis */`;

const read = ( file ) => readFileSync( new URL( `../js/${ file }`, import.meta.url ), 'utf8' )
	.replace( /^export default .*$/m, '' )
	.replace( /^export function/m, 'function' )
	.trim();

const source = [ 'app-nav.js', 'code-copy.js', 'search-panel.js' ].map( read ).join( '\n\n' );

mkdirSync( new URL( '../dist/', import.meta.url ), { recursive: true } );

const out = new URL( '../dist/chassis.js', import.meta.url );

writeFileSync(
	out,
	`${ banner }\n( function () {\n${ source }\n\nappNav();\ncodeCopy();\nsearchPanel();\n} )();\n`
);

// Parse the result: a stray `export` or `import` in `js/` would otherwise
// ship as a runtime `SyntaxError` with a green build.
execFileSync( process.execPath, [ '--check', fileURLToPath( out ) ], { stdio: 'inherit' } );
