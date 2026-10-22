( function () {
	'use strict';

	// Accent swatches preview the colour across the page before it's saved.
	document.addEventListener( 'change', function ( event ) {
		var swatch = event.target;
		if ( ! swatch.classList || ! swatch.classList.contains( 'ubuntupress-swatch' ) ) {
			return;
		}
		document.documentElement.style.setProperty( '--ubuntupress-accent', swatch.getAttribute( 'data-color' ) );
		document.documentElement.style.setProperty( '--ubuntupress-accent-hover', swatch.getAttribute( 'data-hover' ) );
	} );

	// "Choose Image" opens the Media Library and shows the pick as a selected thumbnail.
	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest && event.target.closest( '.ubuntupress-backgrounds__choose' );
		if ( ! button || ! window.wp || ! window.wp.media ) {
			return;
		}
		event.preventDefault();

		if ( ! button.ubuntupressFrame ) {
			button.ubuntupressFrame = window.wp.media( {
				title: button.getAttribute( 'data-title' ),
				library: { type: 'image' },
				button: { text: button.getAttribute( 'data-button' ) },
				multiple: false,
			} );

			button.ubuntupressFrame.on( 'select', function () {
				var attachment = button.ubuntupressFrame.state().get( 'selection' ).first().toJSON();
				var size = attachment.sizes && ( attachment.sizes.medium || attachment.sizes.full );
				var picker = button.closest( '.ubuntupress-backgrounds' );
				var radio = picker.querySelector( '.ubuntupress-background--image' );

				picker.querySelector( '.ubuntupress-backgrounds__image' ).value = attachment.id;
				radio.style.setProperty( '--ubuntupress-background', 'url(' + JSON.stringify( size ? size.url : attachment.url ) + ') center / cover no-repeat' );
				radio.hidden = false;
				radio.checked = true;
				radio.focus();
			} );
		}

		button.ubuntupressFrame.open();
	} );
} )();
