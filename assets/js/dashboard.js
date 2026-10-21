/**
 * WPSkin dashboard: GNOME-style app folders and type-to-search.
 */
( function () {
	'use strict';

	var root = document.querySelector( '.wpskin-dashboard' );
	if ( ! root ) {
		return;
	}

	var grid = root.querySelector( '.wpskin-dashboard__cards' );
	var results = root.querySelector( '.wpskin-dashboard__results' );
	var noResults = root.querySelector( '.wpskin-dashboard__no-results' );
	var input = root.querySelector( '.wpskin-search__input' );
	var openFolder = null;
	var opener = null;

	/* Folders */

	function showFolder( folder, trigger ) {
		openFolder = folder;
		opener = trigger;
		folder.hidden = false;
		var first = folder.querySelector( '.wpskin-card' );
		if ( first ) {
			first.focus();
		}
	}

	function hideFolder() {
		if ( ! openFolder ) {
			return;
		}
		openFolder.hidden = true;
		openFolder = null;
		if ( opener ) {
			opener.focus();
		}
	}

	root.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest( '.wpskin-card--folder' );
		if ( trigger ) {
			var folder = document.getElementById( trigger.getAttribute( 'aria-controls' ) );
			if ( folder ) {
				showFolder( folder, trigger );
			}
			return;
		}

		if ( event.target.classList.contains( 'wpskin-folder__backdrop' ) ) {
			hideFolder();
		}
	} );

	// Hover moves focus, so the initially focused card doesn't stay highlighted next to the hovered one.
	root.addEventListener( 'mouseover', function ( event ) {
		if ( ! openFolder ) {
			return;
		}
		var card = event.target.closest( '.wpskin-card' );
		if ( card && openFolder.contains( card ) && card !== document.activeElement ) {
			card.focus( { preventScroll: true } );
		}
	} );

	/* Search */

	function visibleResults() {
		return Array.prototype.filter.call( results.querySelectorAll( '.wpskin-card' ), function ( card ) {
			return ! card.hidden;
		} );
	}

	function search() {
		var query = input.value.trim().toLowerCase();
		var matches = 0;

		grid.hidden = query !== '';
		results.hidden = query === '';

		Array.prototype.forEach.call( results.querySelectorAll( '.wpskin-card' ), function ( card ) {
			var match = query !== '' && card.getAttribute( 'data-search' ).indexOf( query ) !== -1;
			card.hidden = ! match;
			if ( match ) {
				matches++;
			}
		} );

		noResults.hidden = query === '' || matches > 0;
	}

	input.addEventListener( 'input', search );

	input.addEventListener( 'keydown', function ( event ) {
		if ( 'Enter' === event.key ) {
			var first = visibleResults()[ 0 ];
			if ( first ) {
				event.preventDefault();
				window.location.href = first.href;
			}
		}
	} );

	/* Keyboard: Escape closes a folder or clears the search; typing anywhere starts a search. */

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key ) {
			if ( openFolder ) {
				hideFolder();
			} else if ( input.value ) {
				input.value = '';
				search();
				input.focus();
			}
			return;
		}

		var target = event.target;
		var typing = target.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test( target.tagName );

		if ( openFolder || typing || event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1 || ' ' === event.key ) {
			return;
		}

		event.preventDefault();
		input.focus();
		input.value += event.key;
		search();
	} );
}() );
