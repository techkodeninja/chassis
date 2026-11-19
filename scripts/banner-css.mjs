/**
 * Adds a version banner to the built CSS, so a served file says which
 * release it is. Run after Sass: `npm run build:css` does this for you.
 */

import { readFileSync, writeFileSync } from 'node:fs';

const pkg = JSON.parse( readFileSync( new URL( '../package.json', import.meta.url ), 'utf8' ) );
const banner = `/*! Chassis ${ pkg.version } | GPL-2.0-or-later | https://github.com/techkodeninja/chassis */\n`;

for ( const file of [ 'chassis.css', 'chassis-classicpress.css' ] ) {
	const url = new URL( `../dist/${ file }`, import.meta.url );
	const css = readFileSync( url, 'utf8' );

	if ( ! css.startsWith( '/*! Chassis' ) ) {
		writeFileSync( url, banner + css );
	}
}
