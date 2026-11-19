/**
 * Checks the `chassis.json` schema and the PHP that reads it agree:
 *
 * - the schema is valid JSON with the structure a JSON Schema needs,
 * - every `settings`/`styles` leaf in the schema is either in
 *   `Chassis::STYLES_MAP` or in the handled list (and every map entry
 *   exists in the schema), so the two can't drift apart,
 * - the `chassis.json` examples in the readme and the theme guide parse and
 *   only use settings the schema knows.
 *
 * This is a small validator for what the schema actually uses (`type`,
 * `properties`, `additionalProperties`, `enum`, `oneOf`, `required`,
 * `propertyNames`), not a full JSON Schema implementation. It covers every
 * keyword in `schemas/chassis.schema.json` today.
 *
 * Run: `npm run check:schema`
 */

import { readFileSync } from 'node:fs';

const root = new URL( '..', import.meta.url );
const read = ( file ) => readFileSync( new URL( file, root ), 'utf8' );

const errors = [];
const fail = ( message ) => errors.push( message );

// The `settings`/`styles` paths `stylesProperties()` handles itself, not
// through `STYLES_MAP`.
const HANDLED = [
	'settings.palette',
	'settings.custom',
	'styles.menu-button.position',
	'styles.menu-button.hide-on-scroll',
	'styles.menu-button.border',
	'styles.post.border.style',
	'styles.post.border.bottom-style',
];

// ---------------------------------------------------------------------------
// The schema itself.
// ---------------------------------------------------------------------------

let schema;

try {
	schema = JSON.parse( read( 'schemas/chassis.schema.json' ) );
} catch ( error ) {
	fail( `schemas/chassis.schema.json doesn't parse: ${ error.message }` );
}

if ( schema ) {
	if ( 'string' !== typeof schema.$schema || ! schema.$schema ) {
		fail( 'schemas/chassis.schema.json: no `$schema`.' );
	}

	if ( 'object' !== schema.type || ! schema.properties || 'object' !== typeof schema.properties ) {
		fail( 'schemas/chassis.schema.json: should be an object schema with `properties`.' );
	}

	const TYPES = [ 'string', 'number', 'integer', 'boolean', 'object', 'array', 'null' ];

	const checkNode = ( node, path ) => {
		if ( ! node || 'object' !== typeof node || Array.isArray( node ) ) {
			fail( `${ path }: schema node should be an object.` );
			return;
		}

		if ( 'type' in node ) {
			const types = Array.isArray( node.type ) ? node.type : [ node.type ];

			for ( const type of types ) {
				if ( ! TYPES.includes( type ) ) {
					fail( `${ path }: unknown type "${ type }".` );
				}
			}
		}

		if ( 'properties' in node ) {
			if ( ! node.properties || 'object' !== typeof node.properties || Array.isArray( node.properties ) ) {
				fail( `${ path }: "properties" should be an object.` );
			} else {
				for ( const [ key, child ] of Object.entries( node.properties ) ) {
					checkNode( child, `${ path }.${ key }` );
				}
			}
		}

		if ( node.additionalProperties && 'object' === typeof node.additionalProperties ) {
			checkNode( node.additionalProperties, `${ path } (additionalProperties)` );
		}

		if ( 'oneOf' in node ) {
			if ( ! Array.isArray( node.oneOf ) ) {
				fail( `${ path }: "oneOf" should be a list.` );
			} else {
				node.oneOf.forEach( ( option, i ) => checkNode( option, `${ path } oneOf[${ i }]` ) );
			}
		}

		if ( 'enum' in node && ! Array.isArray( node.enum ) ) {
			fail( `${ path }: "enum" should be a list.` );
		}
	};

	checkNode( schema, 'schemas/chassis.schema.json' );
}

// ---------------------------------------------------------------------------
// The `STYLES_MAP` against the schema leaves.
// ---------------------------------------------------------------------------

if ( schema ) {
	const leaves = new Set();

	const walk = ( node, path ) => {
		const props = node.properties || {};

		if ( Object.keys( props ).length > 0 ) {
			for ( const [ key, child ] of Object.entries( props ) ) {
				walk( child, path ? `${ path }.${ key }` : key );
			}

			return;
		}

		leaves.add( path );
	};

	for ( const group of [ 'settings', 'styles' ] ) {
		walk( schema.properties[ group ], group );
	}

	const php = read( 'src/Chassis.php' );
	const mapKeys = new Set(
		[ ...php.matchAll( /'([a-z0-9_.:-]+)'\s*=>\s*\[\s*'([a-z0-9-]+)'\s*,\s*(true|false)\s*\]/g ) ]
			.map( ( match ) => match[ 1 ] )
	);

	const isHandled = ( path ) => HANDLED.some( ( prefix ) => path === prefix || path.startsWith( `${ prefix }.` ) );

	for ( const leaf of leaves ) {
		if ( ! mapKeys.has( leaf ) && ! isHandled( leaf ) ) {
			fail( `Schema leaf "${ leaf }" has no entry in STYLES_MAP and isn't handled.` );
		}
	}

	for ( const key of mapKeys ) {
		if ( ! leaves.has( key ) ) {
			fail( `STYLES_MAP entry "${ key }" isn't a leaf in the schema.` );
		}
	}

	console.log( `STYLES_MAP: ${ mapKeys.size } entries, ${ leaves.size } schema leaves, ${ HANDLED.length } handled paths.` );
}

// ---------------------------------------------------------------------------
// The `chassis.json` examples in the docs.
// ---------------------------------------------------------------------------

const TOP_KEYS = [ '$schema', 'settings', 'styles', 'color', 'font', 'spacing', 'layout', 'width', 'design', 'menu' ];

function validate( value, node, path, problems ) {
	if ( true === node ) {
		return;
	}

	if ( false === node ) {
		problems.push( `${ path }: not allowed.` );
		return;
	}

	if ( node.oneOf ) {
		const ok = node.oneOf.some( ( option ) => {
			const tried = [];
			validate( value, option, path, tried );
			return 0 === tried.length;
		} );

		if ( ! ok ) {
			problems.push( `${ path }: doesn't match any allowed shape.` );
		}

		return;
	}

	if ( node.enum && ! node.enum.includes( value ) ) {
		problems.push( `${ path }: ${ JSON.stringify( value ) } isn't one of ${ node.enum.join( ', ' ) }.` );
		return;
	}

	if ( node.type ) {
		const types = Array.isArray( node.type ) ? node.type : [ node.type ];
		const actual = null === value ? 'null' : Array.isArray( value ) ? 'array' : typeof value;
		const ok = types.some( ( type ) => type === actual || ( 'integer' === type && 'number' === actual && Number.isInteger( value ) ) );

		if ( ! ok ) {
			problems.push( `${ path }: should be ${ types.join( ' or ' ) }, got ${ actual }.` );
			return;
		}
	}

	if ( null !== value && 'object' === typeof value && ! Array.isArray( value ) ) {
		for ( const key of node.required || [] ) {
			if ( ! ( key in value ) ) {
				problems.push( `${ path }: missing required "${ key }".` );
			}
		}

		const props = node.properties || {};

		for ( const [ key, val ] of Object.entries( value ) ) {
			const child = path ? `${ path }.${ key }` : key;

			if ( key in props ) {
				validate( val, props[ key ], child, problems );
			} else if ( node.propertyNames && node.propertyNames.pattern && ! new RegExp( node.propertyNames.pattern ).test( key ) ) {
				problems.push( `${ child }: name doesn't match ${ node.propertyNames.pattern }.` );
			} else if ( false === node.additionalProperties ) {
				problems.push( `${ child }: isn't a setting.` );
			} else if ( node.additionalProperties && 'object' === typeof node.additionalProperties ) {
				validate( val, node.additionalProperties, child, problems );
			}
		}
	}

	if ( Array.isArray( value ) && node.items ) {
		value.forEach( ( item, i ) => validate( item, node.items, `${ path }[${ i }]`, problems ) );
	}
}

if ( schema ) {
	let checked = 0;

	for ( const file of [ 'readme.md', 'docs/build-a-theme.md' ] ) {
		const text = read( file );
		const blocks = [ ...text.matchAll( /```json\n([\s\S]*?)```/g ) ].map( ( match ) => match[ 1 ] );

		for ( const code of blocks ) {
			let value;

			try {
				value = JSON.parse( code );
			} catch {
				// A fragment, not a whole file (like the `menu` sample): skip it.
				if ( code.trim().startsWith( '{' ) ) {
					fail( `${ file }: a JSON example doesn't parse.` );
				}

				continue;
			}

			if ( ! value || 'object' !== typeof value || Array.isArray( value ) ) {
				continue;
			}

			// Only `chassis.json` examples: `package.json` and `composer.json`
			// samples live in the same files.
			if ( ! Object.keys( value ).some( ( key ) => TOP_KEYS.includes( key ) ) ) {
				continue;
			}

			checked++;
			const problems = [];
			validate( value, schema, '', problems );

			for ( const problem of problems ) {
				fail( `${ file }: ${ problem }` );
			}
		}
	}

	console.log( `Examples: ${ checked } chassis.json samples in the docs.` );
}

// ---------------------------------------------------------------------------

if ( errors.length ) {
	for ( const error of errors ) {
		console.error( error );
	}

	process.exit( 1 );
}

console.log( 'Schema checks passed.' );
