/**
 * Widget «Vídeos de ejercicios»: carga el vídeo de YouTube solo al pulsarlo.
 *
 * Hasta entonces solo hay una miniatura servida por esta web. Al pulsar se
 * cambia por el reproductor (youtube-nocookie) y el foco pasa a él. Sin
 * JavaScript, el enlace abre el vídeo en YouTube. Con el plugin de cookies
 * activo, antes se pide permiso para el contenido de otras webs.
 */
( function () {
	'use strict';

	function play( event ) {
		var link = event.target.closest( '[data-pbc-video]' );
		if ( ! link || event.ctrlKey || event.metaKey || event.shiftKey || 0 !== event.button ) {
			return;
		}

		var id = link.dataset.pbcVideo;
		if ( ! /^[A-Za-z0-9_-]{11}$/.test( id ) ) {
			return;
		}
		event.preventDefault();

		if ( window.pbcCookies ) {
			window.pbcCookies.require( 'externos', function () {
				load( link, id );
			}, 'YouTube' );
		} else {
			load( link, id );
		}
	}

	function load( link, id ) {
		var frame = link.closest( '[data-pbc-video-frame]' );
		var iframe = document.createElement( 'iframe' );
		iframe.className = 'pbc-videos__player';
		iframe.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0';
		iframe.title = link.dataset.pbcVideoTitle || '';
		iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
		iframe.allowFullscreen = true;
		iframe.referrerPolicy = 'strict-origin-when-cross-origin';
		iframe.setAttribute( 'data-pbc-cookies-loaded', 'externos' );

		frame.replaceChildren( iframe );
		iframe.focus();
	}

	document.addEventListener( 'click', play );
}() );
