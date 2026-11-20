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

export function searchPanel( root = document ) {
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

export default searchPanel;
