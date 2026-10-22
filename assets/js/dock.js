/**
 * UbuntuPress dock: auto-hide, app name labels and launch animation.
 *
 * Timings come from Ubuntu Dock (dash-to-dock) defaults and GNOME Shell's dash.js / iconGrid.js.
 */
( function () {
	'use strict';

	var dock = document.querySelector( '.ubuntupress-dock' );
	if ( ! dock ) {
		return;
	}

	var SHOW_DELAY = 250; // dash-to-dock show-delay.
	var HIDE_DELAY = 200; // dash-to-dock hide-delay.
	var ANIMATION_TIME = 200; // dash-to-dock animation-time; the slide itself is a CSS transition.
	var LABEL_HOVER_TIMEOUT = 300; // DASH_ITEM_HOVER_TIMEOUT.
	var LAUNCH_TIME = 250; // APPICON_ANIMATION_OUT_TIME.
	var LAUNCH_SCALE = 3; // APPICON_ANIMATION_OUT_SCALE.
	var EDGE_DISTANCE = 32; // How close to the screen edge, in px, the pointer has to be.
	var LABEL_OFFSET = 8;
	var ICON_SIZES = [ 48, 32, 24, 22, 16 ]; // GNOME dash icon sizes, largest that fits wins.
	var EASE_OUT_QUAD = 'cubic-bezier(0.25, 0.46, 0.45, 0.94)';

	var position = dock.getAttribute( 'data-position' );
	var autohide = '1' === dock.getAttribute( 'data-autohide' );
	var vertical = 'bottom' !== position;
	var track = dock.querySelector( '.ubuntupress-dock__track' );
	var panel = dock.querySelector( '.ubuntupress-dock__panel' );
	var list = dock.querySelector( '.ubuntupress-dock__list' );
	var apps = dock.querySelectorAll( '.ubuntupress-dock__app' );
	var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	/*
	 * Auto-hide: a small state machine mirroring dash-to-dock's HIDDEN / SHOWING / SHOWN / HIDING.
	 * An always-visible dock is rendered shown and never leaves that state.
	 */

	var visible = ! autohide;
	var changedAt = 0;
	var overEdge = false;
	var overPanel = false;
	var focusInside = false;
	var showTimer = 0;
	var hideTimer = 0;

	function animating() {
		return ! reducedMotion.matches && Date.now() - changedAt < ANIMATION_TIME;
	}

	function setVisible( value ) {
		visible = value;
		changedAt = Date.now();
		dock.classList.toggle( 'ubuntupress-dock--visible', value );
		if ( ! value ) {
			hoverApp( null );
		}
	}

	function update() {
		if ( ! autohide ) {
			return;
		}

		if ( overEdge || overPanel || focusInside ) {
			clearTimeout( hideTimer );
			hideTimer = 0;

			if ( visible || showTimer ) {
				return;
			}
			// Reaching the edge waits for show-delay; catching the dock while it slides out
			// sends it straight back, from wherever it is.
			if ( animating() ) {
				setVisible( true );
			} else {
				showTimer = setTimeout( function () {
					showTimer = 0;
					setVisible( true );
				}, SHOW_DELAY );
			}
			return;
		}

		clearTimeout( showTimer );
		showTimer = 0;

		if ( ! visible || hideTimer ) {
			return;
		}
		// A slide-in that has started always finishes before hide-delay starts counting.
		var remaining = animating() ? ANIMATION_TIME - ( Date.now() - changedAt ) : 0;
		hideTimer = setTimeout( function () {
			hideTimer = 0;
			setVisible( false );
		}, remaining + HIDE_DELAY );
	}

	// Measured from the page edge, so the pointer counts as "at the edge" a little before it gets
	// there, and over the scrollbar on the right. The strip element (.ubuntupress-dock__edge) only
	// exists to catch the pointer above iframes, such as the editor canvas, where the page gets no events.
	function nearEdge( x, y ) {
		var root = document.documentElement;
		if ( 'bottom' === position ) {
			return y >= root.clientHeight - EDGE_DISTANCE;
		}
		if ( y < track.getBoundingClientRect().top ) {
			return false;
		}
		return 'left' === position ? x <= EDGE_DISTANCE : x >= root.clientWidth - EDGE_DISTANCE;
	}

	function setOverEdge( value ) {
		if ( autohide && value !== overEdge ) {
			overEdge = value;
			// Lights up the hidden-dock hint while show-delay runs.
			dock.classList.toggle( 'ubuntupress-dock--near', value );
			update();
		}
	}

	document.addEventListener( 'mousemove', function ( event ) {
		setOverEdge( nearEdge( event.clientX, event.clientY ) );
	}, { passive: true } );

	// Leaving the window, or moving onto an iframe past the strip, ends the hover.
	document.addEventListener( 'mouseout', function ( event ) {
		var to = event.relatedTarget;
		if ( ! to || 'IFRAME' === to.tagName ) {
			setOverEdge( false );
		}
	} );

	panel.addEventListener( 'mouseenter', function () {
		overPanel = true;
		update();
	} );

	panel.addEventListener( 'mouseleave', function () {
		overPanel = false;
		update();
	} );

	dock.addEventListener( 'focusin', function () {
		focusInside = true;
		update();
	} );

	dock.addEventListener( 'focusout', function ( event ) {
		if ( ! dock.contains( event.relatedTarget ) ) {
			focusInside = false;
			update();
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && visible && dock.contains( document.activeElement ) ) {
			document.activeElement.blur();
		}
	} );

	/* Labels: GNOME dash.js _syncLabel(). The first label waits; moving along the dock shows the next at once. */

	var labelShowing = false;
	var labelTimer = 0;
	var resetTimer = 0;
	var hovered = null;

	function labelFor( app ) {
		if ( ! app.ubuntupressLabel ) {
			var label = document.createElement( 'div' );
			label.className = 'ubuntupress-dock__label';
			label.setAttribute( 'aria-hidden', 'true' );
			label.textContent = app.getAttribute( 'data-label' );
			dock.appendChild( label );
			app.ubuntupressLabel = label;
		}
		return app.ubuntupressLabel;
	}

	function showLabel( app ) {
		var label = labelFor( app );
		var item = app.getBoundingClientRect();
		var bar = panel.getBoundingClientRect();
		var x;
		var y;

		// Restart from transparent, like showLabel() setting opacity = 0 before easing in.
		label.classList.remove( 'ubuntupress-dock__label--visible' );
		label.style.transition = 'none';
		label.style.opacity = '0';
		var width = label.offsetWidth;
		var height = label.offsetHeight;

		if ( 'left' === position ) {
			x = bar.right + LABEL_OFFSET;
			y = item.top + ( item.height - height ) / 2;
		} else if ( 'right' === position ) {
			x = bar.left - LABEL_OFFSET - width;
			y = item.top + ( item.height - height ) / 2;
		} else {
			x = item.left + ( item.width - width ) / 2;
			y = bar.top - LABEL_OFFSET - height;
		}

		x = Math.max( 4, Math.min( x, window.innerWidth - width - 4 ) );
		label.style.transform = 'translate(' + Math.round( x ) + 'px, ' + Math.round( y ) + 'px)';

		void label.offsetWidth; // Commit opacity 0 so the fade runs.
		label.style.transition = '';
		label.style.opacity = '';
		label.classList.add( 'ubuntupress-dock__label--visible' );
	}

	function hideLabel( app ) {
		if ( app.ubuntupressLabel ) {
			app.ubuntupressLabel.classList.remove( 'ubuntupress-dock__label--visible' );
		}
	}

	function hoverApp( app ) {
		if ( app === hovered ) {
			return;
		}

		if ( hovered ) {
			clearTimeout( labelTimer );
			labelTimer = 0;
			hideLabel( hovered );
			if ( labelShowing ) {
				clearTimeout( resetTimer );
				resetTimer = setTimeout( function () {
					labelShowing = false;
					resetTimer = 0;
				}, LABEL_HOVER_TIMEOUT );
			}
		}

		hovered = app;
		if ( ! app ) {
			return;
		}

		clearTimeout( resetTimer );
		resetTimer = 0;
		labelTimer = setTimeout( function () {
			labelTimer = 0;
			labelShowing = true;
			showLabel( app );
		}, labelShowing ? 0 : LABEL_HOVER_TIMEOUT );
	}

	list.addEventListener( 'mouseover', function ( event ) {
		hoverApp( event.target.closest( '.ubuntupress-dock__app' ) );
	} );

	list.addEventListener( 'mouseleave', function () {
		hoverApp( null );
	} );

	/* Launch: iconGrid.js zoomOutActorAtPos(), a copy of the icon grows 3x and fades, kept on screen. */

	function animateLaunch( app ) {
		var icon = app.querySelector( '.ubuntupress-dock__icon' );
		if ( ! icon || ! icon.animate || reducedMotion.matches ) {
			return;
		}

		var rect = icon.getBoundingClientRect();
		var clone = icon.cloneNode( true );
		var scaledWidth = rect.width * LAUNCH_SCALE;
		var scaledHeight = rect.height * LAUNCH_SCALE;
		var scaledX = rect.left - ( scaledWidth - rect.width ) / 2;
		var scaledY = rect.top - ( scaledHeight - rect.height ) / 2;
		var containedX = Math.max( 0, Math.min( scaledX, window.innerWidth - scaledWidth ) );
		var containedY = Math.max( 0, Math.min( scaledY, window.innerHeight - scaledHeight ) );

		clone.classList.add( 'ubuntupress-dock__launch' );
		clone.style.left = rect.left + 'px';
		clone.style.top = rect.top + 'px';
		clone.style.width = rect.width + 'px';
		clone.style.height = rect.height + 'px';
		dock.appendChild( clone );

		clone.animate(
			[
				{ transform: 'none', opacity: 1 },
				{
					transform: 'translate(' + ( containedX - scaledX ) + 'px, ' + ( containedY - scaledY ) + 'px) scale(' + LAUNCH_SCALE + ')',
					opacity: 0,
				},
			],
			{ duration: LAUNCH_TIME, easing: EASE_OUT_QUAD, fill: 'forwards' }
		).onfinish = function () {
			clone.remove();
		};
	}

	list.addEventListener( 'click', function ( event ) {
		var app = event.target.closest( '.ubuntupress-dock__app' );
		if ( ! app || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey ) {
			return;
		}
		hoverApp( null );
		animateLaunch( app );
	} );

	/* Icon size: like the dash, shrink icons until every app fits along the edge. */

	function fit() {
		var margin = parseFloat( getComputedStyle( track ).paddingTop ) || 0;
		var available = ( vertical ? track.clientHeight : track.clientWidth ) - 2 * margin;
		var panelPadding = ( parseFloat( getComputedStyle( panel ).paddingTop ) || 0 ) * 2;

		for ( var i = 0; i < ICON_SIZES.length; i++ ) {
			dock.style.setProperty( '--ubuntupress-dock-icon', ICON_SIZES[ i ] + 'px' );
			var length = ( vertical ? list.scrollHeight : list.scrollWidth ) + panelPadding;
			if ( length <= available ) {
				break;
			}
		}

		// The space an always-visible dock reserves follows its icon size (see dock.css).
		if ( ! autohide ) {
			var thickness = ( vertical ? panel.offsetWidth : panel.offsetHeight ) + 2 * margin;
			document.documentElement.style.setProperty( '--ubuntupress-dock-space', thickness + 'px' );
		}
	}

	fit();
	window.addEventListener( 'resize', fit );

	// Dragging a launcher would leave the dock's hover state stuck; apps in the dock aren't drag sources.
	Array.prototype.forEach.call( apps, function ( app ) {
		app.addEventListener( 'dragstart', function ( event ) {
			event.preventDefault();
		} );
	} );
}() );
