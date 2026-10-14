/**
 * Mapa del bloque de contacto: se carga solo al pulsar «Ver el mapa».
 * Sin JavaScript, el enlace abre el mapa en OpenStreetMap. Con el plugin de
 * cookies activo, antes se pide permiso para el contenido de otras webs.
 */
( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-pbc-map-button]' );
		if ( ! button || event.ctrlKey || event.metaKey || event.shiftKey || 0 !== event.button ) {
			return;
		}

		var panel = button.closest( '[data-pbc-map]' );
		var src = panel ? panel.dataset.pbcMapSrc : '';
		if ( ! src || 0 !== src.indexOf( 'https://www.openstreetmap.org/' ) ) {
			return;
		}
		event.preventDefault();

		if ( window.pbcCookies ) {
			window.pbcCookies.require( 'externos', function () {
				load( panel, src );
			}, 'OpenStreetMap' );
		} else {
			load( panel, src );
		}
	} );

	function load( panel, src ) {
		var iframe = document.createElement( 'iframe' );
		iframe.className = 'pbc-contact__map-frame';
		iframe.src = src;
		iframe.title = panel.dataset.pbcMapTitle || '';
		iframe.loading = 'lazy';
		iframe.referrerPolicy = 'strict-origin-when-cross-origin';
		iframe.setAttribute( 'data-pbc-cookies-loaded', 'externos' );

		panel.classList.remove( 'pbc-contact__map--facade' );
		panel.classList.add( 'pbc-contact__map--loaded' );
		panel.replaceChildren( iframe );
		iframe.focus();
	}
}() );
