/**
 * Code copy
 *
 * Adds a Copy button to code blocks in post content, and announces the result
 * to screen readers. Pairs with `scss/07.components/09.code-copy`.
 *
 * Without the clipboard API (plain `http://` pages, where it is undefined),
 * it falls back to a temporary textarea and `execCommand`, so the button
 * still works there.
 *
 * @package Techkodeninja\Chassis
 */

export function codeCopy( root = document ) {
	root.querySelectorAll( '.entry__content pre, .entry-content pre' ).forEach( ( block ) => {
		if ( block.dataset.codeCopy ) {
			return;
		}

		block.dataset.codeCopy = 'ready';

		// Read the text now, so it doesn't include the button's label.
		const text = ( block.querySelector( 'code' ) ?? block ).textContent;

		const button = document.createElement( 'button' );

		button.type = 'button';
		button.className = 'code-copy';
		button.textContent = 'Copy';
		button.setAttribute( 'aria-label', 'Copy code' );

		let resetTimer;

		button.addEventListener( 'click', async () => {
			clearTimeout( resetTimer );

			try {
				await copyText( text );
				button.textContent = 'Copied';
				announce( 'Copied' );
			} catch {
				button.textContent = 'Copy failed';
				announce( 'Copy failed' );
			}

			resetTimer = setTimeout( () => {
				button.textContent = 'Copy';
				announce( '' );
			}, 2000 );
		} );

		// First in the block, so the sticky button sits at the top of it.
		block.prepend( button );
	} );
}

/**
 * Copies text. Uses the clipboard API when there is one; otherwise a
 * temporary textarea with `execCommand`, for pages without a secure context.
 */
async function copyText( text ) {
	if ( navigator.clipboard ) {
		await navigator.clipboard.writeText( text );
		return;
	}

	if ( ! document.execCommand ) {
		throw new Error( 'copy unavailable' );
	}

	const area = document.createElement( 'textarea' );

	area.value = text;
	area.setAttribute( 'readonly', '' );
	area.style.position = 'absolute';
	area.style.left = '-9999px';
	document.body.append( area );
	area.select();

	try {
		if ( ! document.execCommand( 'copy' ) ) {
			throw new Error( 'copy failed' );
		}
	} finally {
		area.remove();
	}
}

/**
 * Announces the result to screen readers through one shared live region,
 * instead of putting `aria-live` on the button itself.
 */
let liveRegion;

function announce( message ) {
	if ( ! liveRegion ) {
		liveRegion = document.createElement( 'div' );
		liveRegion.className = 'screen-reader-text';
		liveRegion.setAttribute( 'aria-live', 'polite' );
		document.body.append( liveRegion );
	}

	liveRegion.textContent = message;
}

export default codeCopy;
