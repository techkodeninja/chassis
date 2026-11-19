/**
 * Checks the version agrees everywhere it is written: `Chassis::VERSION`,
 * `package.json`, the jsDelivr links in the readme, and the newest entry in
 * the changelog. Exits non-zero on a mismatch, so a release like 0.1.7 —
 * tagged while three of the four still said 0.1.6 — can't ship again.
 *
 * Run: `npm run check:version`
 */

import { readFileSync } from 'node:fs';

const root = new URL( '..', import.meta.url );
const read = ( file ) => readFileSync( new URL( file, root ), 'utf8' );

const versions = new Map();

const php = read( 'src/Chassis.php' ).match( /const VERSION = '([^']+)'/ );
versions.set( 'src/Chassis.php (Chassis::VERSION)', php ? php[ 1 ] : null );

versions.set( 'package.json', JSON.parse( read( 'package.json' ) ).version );

const links = [ ...read( 'readme.md' ).matchAll( /chassis@([0-9]+\.[0-9]+\.[0-9]+)/g ) ].map( ( m ) => m[ 1 ] );
links.forEach( ( version, i ) => versions.set( `readme.md jsDelivr link ${ i + 1 }`, version ) );

// The first versioned heading: an `[Unreleased]` section, if there is one,
// comes before it and is skipped.
const changelog = read( 'changelog.md' ).match( /^## \[(\d+\.\d+\.\d+)\]/m );
versions.set( 'changelog.md (newest entry)', changelog ? changelog[ 1 ] : null );

const found = [ ...versions.values() ];
const expected = found.find( ( version ) => null !== version );
const bad = [ ...versions.entries() ].filter( ( [ , version ] ) => version !== expected );

if ( 0 === found.filter( ( version ) => null === version ).length && 0 === bad.length ) {
	console.log( `Version ${ expected } agrees everywhere.` );
	process.exit( 0 );
}

for ( const [ where, version ] of versions ) {
	console.error( `${ where }: ${ null === version ? 'not found' : version }` );
}

if ( bad.length ) {
	console.error( `Version mismatch: expected ${ expected } everywhere.` );
}

process.exit( 1 );
