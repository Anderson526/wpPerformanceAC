/* AC Performance: carga diferida de iframes y vídeos con IntersectionObserver. */
( function () {
	'use strict';

	function loadElement( el ) {
		var src = el.getAttribute( 'data-anderc-src' );
		if ( src ) {
			el.setAttribute( 'src', src );
			el.removeAttribute( 'data-anderc-src' );
		}
	}

	function init() {
		var elements = document.querySelectorAll( '[data-anderc-src]' );
		if ( ! elements.length ) {
			return;
		}

		if ( ! ( 'IntersectionObserver' in window ) ) {
			// Navegadores antiguos: cargar todo de inmediato.
			elements.forEach( loadElement );
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						loadElement( entry.target );
						observer.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '200px 0px' }
		);

		elements.forEach( function ( el ) {
			observer.observe( el );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
