/**
 * Mapa del bloque de contacto.
 *
 * Siempre se ve el mapa estático (imagen de esta web). El mapa interactivo
 * de OpenStreetMap lo sustituye:
 * - solo, al cargar la página, si la persona ya ha permitido el contenido de
 *   otras webs (o en cuanto lo permita);
 * - al pulsar «Mover y ampliar el mapa», pidiendo permiso si hace falta.
 * Sin JavaScript, el botón abre el mapa en OpenStreetMap.
 */
( function () {
	'use strict';

	function load( panel, focus ) {
		var src = panel.dataset.pbcMapSrc || '';
		if ( panel.dataset.pbcMapLoaded || 0 !== src.indexOf( 'https://www.openstreetmap.org/' ) ) {
			return;
		}
		panel.dataset.pbcMapLoaded = '1';

		var iframe = document.createElement( 'iframe' );
		iframe.className = 'pbc-contact__map-frame';
		iframe.src = src;
		iframe.title = panel.dataset.pbcMapTitle || '';
		iframe.loading = 'lazy';
		iframe.referrerPolicy = 'strict-origin-when-cross-origin';
		iframe.setAttribute( 'data-pbc-cookies-loaded', 'externos' );

		panel.classList.remove( 'pbc-contact__map--facade', 'pbc-contact__map--static' );
		panel.classList.add( 'pbc-contact__map--loaded' );
		panel.replaceChildren( iframe );
		if ( focus ) {
			iframe.focus();
		}
	}

	function allowed() {
		return !! ( window.pbcCookies && window.pbcCookies.allowed( 'externos' ) );
	}

	function loadAllIfAllowed() {
		if ( allowed() ) {
			document.querySelectorAll( '[data-pbc-map]' ).forEach( function ( panel ) {
				load( panel, false );
			} );
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-pbc-map-button]' );
		if ( ! button || event.ctrlKey || event.metaKey || event.shiftKey || 0 !== event.button ) {
			return;
		}

		var panel = button.closest( '[data-pbc-map]' );
		if ( ! panel ) {
			return;
		}
		event.preventDefault();

		if ( window.pbcCookies ) {
			window.pbcCookies.require( 'externos', function () {
				load( panel, true );
			}, 'OpenStreetMap' );
		} else {
			load( panel, true );
		}
	} );

	// Con permiso ya dado (o en cuanto se dé), el mapa interactivo aparece solo.
	document.addEventListener( 'pbc-cookies:change', loadAllIfAllowed );
	// Se espera a que acaben los scripts diferidos: el de cookies puede ir después de este.
	if ( 'loading' === document.readyState || 'interactive' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', loadAllIfAllowed );
	} else {
		loadAllIfAllowed();
	}
}() );
