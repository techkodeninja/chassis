/*! Chassis 0.2.0 | GPL-2.0-or-later | https://github.com/techkodeninja/chassis */
( function () {
/**
 * App navigation
 *
 * Opens and closes the mobile menu for every `.app-nav` on the page. Pairs
 * with `scss/06.layouts/03.navigation`.
 *
 *   import { appNav } from '../../vendor/techkodeninja/chassis/js/app-nav.js';
 *   appNav();
 *
 * - The Menu button is `.app-nav__toggle`, or the `.menu-toggle` that Novaris
 *   prints. It toggles `.app-nav--open` and keeps `aria-expanded` in sync.
 * - Links with a submenu get a button that opens it, which helps on touch
 *   screens. It toggles `.app-nav__item--open` and keeps `aria-expanded` in sync.
 * - Escape closes the menu and returns focus to the button.
 * - Clicking outside the menu closes it.
 * - Tabbing out of the menu closes it.
 * - Widening the screen past the mobile breakpoint resets it to closed.
 * - On phones the button hides when you scroll down and comes back when you
 *   scroll up, reach the top or bottom of the page, or tab to it. It toggles
 *   `.app-nav--hidden`. `--menu-hide-on-scroll: 0` turns this off, which is
 *   what `"hide-on-scroll": false` under `menu` in `chassis.json` sets.
 *
 * @package Techkodeninja\Chassis
 * @license GPL-2.0-or-later
 */

// Submenu ids, so each toggle can point at its submenu with `aria-controls`.
let submenuId = 0;

function appNav( root = document ) {
	root.querySelectorAll( '.app-nav' ).forEach( ( nav ) => {
		const toggle = nav.querySelector( '.app-nav__toggle, .menu-toggle' );

		if ( ! toggle || toggle.dataset.appNav ) {
			return;
		}

		// Guard against wiring the same menu twice.
		toggle.dataset.appNav = 'ready';

		const setOpen = ( open ) => {
			nav.classList.toggle( 'app-nav--open', open );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		};

		const isOpen = () => nav.classList.contains( 'app-nav--open' );

		const closeSubmenus = ( except ) => {
			nav.querySelectorAll( '.app-nav__item--open' ).forEach( ( item ) => {
				if ( ! except || ! item.contains( except ) ) {
					item.classList.remove( 'app-nav__item--open' );
					item.querySelector( ':scope > .app-nav__submenu-toggle' )?.setAttribute( 'aria-expanded', 'false' );
				}
			} );
		};

		nav.querySelectorAll( '.app-nav__menu li' ).forEach( ( item ) => {
			const submenu = item.querySelector( ':scope > ul' );
			const link = item.querySelector( ':scope > a' );

			if ( ! submenu || ! link ) {
				return;
			}

			const button = document.createElement( 'button' );

			button.type = 'button';
			button.className = 'app-nav__submenu-toggle';
			button.textContent = '\u25BE'; // A small down arrow, escaped so it shows on pages that aren't UTF-8.

			if ( ! submenu.id ) {
				submenu.id = `app-nav-submenu-${ ++submenuId }`;
			}

			button.setAttribute( 'aria-expanded', 'false' );
			button.setAttribute( 'aria-haspopup', 'true' );
			button.setAttribute( 'aria-controls', submenu.id );
			button.setAttribute( 'aria-label', `Submenu for ${ link.textContent.trim() }` );

			button.addEventListener( 'click', () => {
				const open = ! item.classList.contains( 'app-nav__item--open' );

				closeSubmenus( item );
				item.classList.toggle( 'app-nav__item--open', open );
				button.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			} );

			link.after( button );
		} );

		nav.classList.add( 'app-nav--ready' );

		toggle.addEventListener( 'click', () => setOpen( ! isOpen() ) );

		// Escape works even with focus outside the nav, as long as the menu
		// is open; an open submenu closes first, then the menu.
		document.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' !== event.key ) {
				return;
			}

			const openItem = nav.contains( event.target ) ? event.target.closest( '.app-nav__item--open' ) : null;

			if ( openItem ) {
				closeSubmenus();
				openItem.querySelector( ':scope > .app-nav__submenu-toggle' )?.focus();
			} else if ( isOpen() ) {
				setOpen( false );
				toggle.focus();
			}
		} );

		// Tabbing out of the menu closes it.
		nav.addEventListener( 'focusout', ( event ) => {
			if ( nav.contains( event.relatedTarget ) ) {
				return;
			}

			closeSubmenus();

			if ( isOpen() ) {
				setOpen( false );
			}
		} );

		document.addEventListener( 'click', ( event ) => {
			if ( ! nav.contains( event.target ) ) {
				closeSubmenus();

				if ( isOpen() ) {
					setOpen( false );
				}
			}
		} );

		// The button is hidden on wider screens, so close the menu there.
		window.addEventListener( 'resize', () => {
			if ( isOpen() && 'none' === getComputedStyle( toggle ).display ) {
				setOpen( false );
			}
		} );

		hideOnScroll( nav, toggle, isOpen );
	} );
}

/**
 * Hides the Menu button while scrolling down, and shows it again on the way up.
 * Small movements are ignored, so it doesn't flicker while reading.
 */
function hideOnScroll( nav, toggle, isOpen ) {
	const threshold = 16;
	let lastY = window.scrollY;
	let ticking = false;

	const enabled = () => {
		const value = getComputedStyle( nav ).getPropertyValue( '--menu-hide-on-scroll' ).trim();

		return '0' !== value && 'none' !== getComputedStyle( toggle ).display;
	};

	const setHidden = ( hidden ) => nav.classList.toggle( 'app-nav--hidden', hidden );

	const update = () => {
		ticking = false;

		const y = window.scrollY;
		const atEdge = y <= threshold || window.innerHeight + y >= document.documentElement.scrollHeight - threshold;

		if ( ! enabled() || isOpen() || atEdge ) {
			setHidden( false );
			lastY = y;
			return;
		}

		if ( Math.abs( y - lastY ) < threshold ) {
			return;
		}

		setHidden( y > lastY );
		lastY = y;
	};

	window.addEventListener( 'scroll', () => {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( update );
		}
	}, { passive: true } );

	// Keyboard users can always reach it.
	toggle.addEventListener( 'focus', () => setHidden( false ) );
}

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

function codeCopy( root = document ) {
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

/**
 * Search panel
 *
 * Opens and closes the header search panel for every `.search-toggle` on
 * the page. Pairs with `scss/07.components/10.search-panel`.
 *
 *   <button class="search-toggle" type="button" aria-controls="search-container" aria-expanded="false">…</button>
 *   <div class="search-box" id="search-container">…search form…</div>
 *
 * - The button toggles `.search-box--open` on the panel and
 *   `.search-toggle--active` on itself, and keeps `aria-expanded` in sync.
 * - Opening the panel moves focus into its search field.
 * - Escape closes the panel and returns focus to the button.
 *
 * @package Techkodeninja\Chassis
 */

function searchPanel( root = document ) {
	root.querySelectorAll( '.search-toggle' ).forEach( ( toggle ) => {
		if ( toggle.dataset.searchPanel ) {
			return;
		}

		const panelId = toggle.getAttribute( 'aria-controls' );
		const panel = panelId ? document.getElementById( panelId ) : null;

		if ( ! panel ) {
			return;
		}

		// Guard against wiring the same toggle twice.
		toggle.dataset.searchPanel = 'ready';

		const setOpen = ( open ) => {
			panel.classList.toggle( 'search-box--open', open );
			toggle.classList.toggle( 'search-toggle--active', open );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );

			// Move focus into the search field when the panel opens.
			if ( open ) {
				panel.querySelector( '.search-field' )?.focus();
			}
		};

		const isOpen = () => panel.classList.contains( 'search-box--open' );

		toggle.addEventListener( 'click', () => setOpen( ! isOpen() ) );

		// Escape closes the panel and returns focus to the button.
		document.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' !== event.key || ! isOpen() ) {
				return;
			}

			if ( ! panel.contains( event.target ) && event.target !== toggle ) {
				return;
			}

			setOpen( false );
			toggle.focus();
		} );
	} );
}

appNav();
codeCopy();
searchPanel();
} )();
