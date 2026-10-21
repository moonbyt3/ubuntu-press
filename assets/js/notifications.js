/**
 * WPSkin notification center: moves admin notices into a toolbar popup.
 */
( function ( $ ) {
	'use strict';

	var NOTICE_SELECTOR = 'div.notice, div.updated, div.error, div.update-nag';
	var SEVERITY = [
		[ 'error', '.notice-error, div.error' ],
		[ 'warning', '.notice-warning, .update-nag' ],
		[ 'success', '.notice-success, div.updated' ],
		[ 'info', '.notice-info' ]
	];

	$( function () {
		var $panel = $( '#wpskin-notices-panel' );
		var $list = $panel.find( '.wpskin-notices-panel__list' );
		var $node = $( '#wp-admin-bar-wpskin-notices' );
		var $button = $node.children( '.ab-item' );
		var $count = $node.find( '.wpskin-notices-count' );
		var content = document.getElementById( 'wpbody-content' );
		var l10n = window.wpskinNotices || { one: '%d notification', many: '%d notifications' };

		if ( ! $panel.length || ! $node.length ) {
			return;
		}

		$panel.appendTo( document.body );
		$button.attr( {
			role: 'button',
			'aria-haspopup': 'dialog',
			'aria-expanded': 'false',
			'aria-controls': 'wpskin-notices-panel'
		} );

		/**
		 * Same rules core uses in common.js, plus the core update nag (which is marked inline).
		 */
		function isMovable( el ) {
			var $el = $( el );
			return $el.hasClass( 'update-nag' ) || ! $el.is( '.inline, .below-h2' );
		}

		function sweep() {
			if ( ! content ) {
				return;
			}
			$( content ).find( NOTICE_SELECTOR ).filter( function () {
				return isMovable( this ) && ! $( this ).parent().closest( NOTICE_SELECTOR ).length;
			} ).appendTo( $list );
		}

		function visibleNotices() {
			return $list.children().filter( function () {
				return ! $( this ).hasClass( 'hidden' ) && this.style.display !== 'none';
			} );
		}

		function update() {
			var $visible = visibleNotices();
			var total = $visible.length;
			var level = '';

			$.each( SEVERITY, function ( i, pair ) {
				if ( $visible.filter( pair[ 1 ] ).length ) {
					level = pair[ 0 ];
					return false;
				}
			} );

			$count.text( total > 99 ? '99+' : total ).prop( 'hidden', total === 0 );
			$node.attr( 'data-level', level ).toggleClass( 'has-notices', total > 0 );
			$button.attr( 'aria-label', ( total === 1 ? l10n.one : l10n.many ).replace( '%d', total ) );
			$panel.toggleClass( 'is-empty', total === 0 );
		}

		function open() {
			$panel.prop( 'hidden', false );
			$node.addClass( 'is-open' );
			$button.attr( 'aria-expanded', 'true' );
			$panel.find( '.wpskin-notices-panel__close' ).trigger( 'focus' );
		}

		function close( returnFocus ) {
			if ( $panel.prop( 'hidden' ) ) {
				return;
			}
			$panel.prop( 'hidden', true );
			$node.removeClass( 'is-open' );
			$button.attr( 'aria-expanded', 'false' );
			if ( returnFocus ) {
				$button.trigger( 'focus' );
			}
		}

		$button.on( 'click', function ( event ) {
			event.preventDefault();
			if ( $panel.prop( 'hidden' ) ) {
				open();
			} else {
				close( false );
			}
		} );

		$panel.on( 'click', '.wpskin-notices-panel__close', function () {
			close( true );
		} );

		$( document ).on( 'click', function ( event ) {
			if ( ! $( event.target ).closest( '#wpskin-notices-panel, #wp-admin-bar-wpskin-notices' ).length ) {
				close( false );
			}
		} ).on( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				close( true );
			}
		} );

		sweep();
		update();

		// Catch notices added later by core or plugins, and keep the counter in sync.
		if ( window.MutationObserver ) {
			if ( content ) {
				new MutationObserver( throttle( sweep ) ).observe( content, { childList: true, subtree: true } );
			}
			new MutationObserver( throttle( update ) ).observe( $list[ 0 ], {
				childList: true,
				subtree: true,
				attributes: true,
				attributeFilter: [ 'class', 'style' ]
			} );
		}
	} );

	/**
	 * Runs fn at most once per animation frame.
	 */
	function throttle( fn ) {
		var queued = false;
		return function () {
			if ( queued ) {
				return;
			}
			queued = true;
			window.requestAnimationFrame( function () {
				queued = false;
				fn();
			} );
		};
	}
}( jQuery ) );
