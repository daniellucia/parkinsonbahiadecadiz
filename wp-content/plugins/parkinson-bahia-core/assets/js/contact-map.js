/**
 * Mapa del bloque de contacto: se carga solo al pulsar «Ver el mapa».
 * Sin JavaScript, el enlace abre el mapa en OpenStreetMap.
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

		var iframe = document.createElement( 'iframe' );
		iframe.className = 'pbc-contact__map-frame';
		iframe.src = src;
		iframe.title = panel.dataset.pbcMapTitle || '';
		iframe.loading = 'lazy';
		iframe.referrerPolicy = 'strict-origin-when-cross-origin';

		panel.classList.remove( 'pbc-contact__map--facade' );
		panel.classList.add( 'pbc-contact__map--loaded' );
		panel.replaceChildren( iframe );
		iframe.focus();
	} );
}() );
