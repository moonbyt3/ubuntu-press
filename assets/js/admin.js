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
} )();
