/**
 * Cookies accesibles: aviso, ventana de configuración y permiso al momento.
 *
 * La elección se guarda en la cookie «pbc_cookies» (versión|categorías|fecha).
 * Mientras no hay elección válida, todo lo opcional está denegado.
 *
 * API para el resto de la web:
 *   pbcCookies.allowed( 'externos' )               → true / false
 *   pbcCookies.require( 'externos', cargar, 'YouTube' )
 *       Si ya hay permiso, llama a cargar(). Si no, pregunta en una ventana
 *       y solo llama a cargar() si la persona lo permite.
 *   pbcCookies.open()                              → abre «Configurar cookies»
 *   Evento «pbc-cookies:change» en document, con detail.categories.
 *
 * Sin dependencias. Se carga con defer.
 */
( function () {
	'use strict';

	var config = window.pbcCookiesConfig || {};
	var NAME = config.cookie || 'pbc_cookies';
	var REQUIRED = 'necesarias';
	var optional = config.categories || [];

	var granted = [];
	var decided = false;
	var ui = null;
	var banner = null;
	var status = null;

	/* ---------- Guardar y leer la elección ---------- */
	function read() {
		var match = document.cookie.match( new RegExp( '(?:^|;\\s*)' + NAME + '=([^;]*)' ) );
		if ( ! match ) {
			return null;
		}
		var parts = decodeURIComponent( match[ 1 ] ).split( '|' );
		if ( parts.length !== 3 || parts[ 0 ] !== config.version ) {
			return null;
		}
		return parts[ 1 ] ? parts[ 1 ].split( ',' ) : [];
	}

	function write( categories ) {
		var value = [ config.version, categories.join( ',' ), Date.now() ].join( '|' );
		document.cookie = NAME + '=' + encodeURIComponent( value ) +
			'; Max-Age=' + ( config.maxAge || 31536000 ) +
			'; Path=/; SameSite=Lax' +
			( 'https:' === location.protocol ? '; Secure' : '' );
	}

	function only( list ) {
		return list.filter( function ( category ) {
			return optional.indexOf( category ) !== -1;
		} );
	}

	var stored = read();
	if ( stored ) {
		granted = only( stored );
		decided = true;
	}

	function allowed( category ) {
		return REQUIRED === category || granted.indexOf( category ) !== -1;
	}

	function save( categories ) {
		var before = granted.slice();
		granted = only( categories );
		decided = true;
		write( granted );
		hideBanner();
		announce( config.saved || '' );
		document.dispatchEvent( new CustomEvent( 'pbc-cookies:change', { detail: { categories: granted.slice() } } ) );

		// Si se retira un permiso con contenido ya cargado, se recarga la página para quitarlo.
		var revoked = before.some( function ( category ) {
			return granted.indexOf( category ) === -1;
		} );
		if ( revoked && document.querySelector( '[data-pbc-cookies-loaded]' ) ) {
			window.location.reload();
		}
	}

	/* ---------- Interfaz ---------- */
	function build() {
		if ( ui ) {
			return ui;
		}
		var template = document.getElementById( 'pbc-cookies-ui' );
		if ( ! template ) {
			return null;
		}
		var fragment = template.content.cloneNode( true );
		ui = {
			dialog: fragment.querySelector( '[data-pbc-cookies-dialog]' ),
			ask: fragment.querySelector( '[data-pbc-cookies-ask]' )
		};
		banner = fragment.querySelector( '[data-pbc-cookies-banner]' );

		// El aviso va el primero de la página: es lo primero que se encuentra con el teclado o un lector de pantalla.
		if ( banner ) {
			banner.hidden = true;
			document.body.insertBefore( banner, document.body.firstChild );
		}
		document.body.appendChild( fragment );

		status = document.createElement( 'p' );
		status.className = 'pbc-cookies-status';
		status.setAttribute( 'role', 'status' );
		document.body.appendChild( status );

		ui.dialog.addEventListener( 'close', onDialogClose );
		return ui;
	}

	function announce( text ) {
		if ( ! status || ! text ) {
			return;
		}
		status.textContent = '';
		window.setTimeout( function () {
			status.textContent = text;
		}, 100 );
	}

	/* El aviso está fijo abajo: se reserva su alto para que no tape el final de
	   la página ni lo que tiene el foco. */
	var observer = null;

	function syncHeight() {
		var height = banner && ! banner.hidden ? banner.offsetHeight : 0;
		document.documentElement.style.setProperty( '--pbc-cookies-h', height + 'px' );
	}

	function showBanner() {
		if ( ! banner ) {
			return;
		}
		banner.hidden = false;
		syncHeight();
		if ( window.ResizeObserver && ! observer ) {
			observer = new ResizeObserver( syncHeight );
			observer.observe( banner );
		}
	}

	function hideBanner() {
		if ( ! banner || banner.hidden ) {
			return;
		}
		// Si el foco estaba en el aviso, pasa al contenido para no perderse.
		var hadFocus = banner.contains( document.activeElement );
		banner.hidden = true;
		syncHeight();
		if ( hadFocus ) {
			var main = document.getElementById( 'contenido' ) || document.querySelector( 'main' );
			if ( main ) {
				if ( ! main.hasAttribute( 'tabindex' ) ) {
					main.setAttribute( 'tabindex', '-1' );
				}
				main.focus( { preventScroll: true } );
			}
		}
	}

	function open() {
		if ( ! build() ) {
			return;
		}
		ui.dialog.querySelectorAll( '[data-pbc-cookies-category]' ).forEach( function ( box ) {
			if ( ! box.disabled ) {
				box.checked = allowed( box.getAttribute( 'data-pbc-cookies-category' ) );
			}
		} );
		ui.dialog.returnValue = '';
		ui.dialog.showModal();
	}

	function onDialogClose() {
		var choice = ui.dialog.returnValue;
		if ( 'all' === choice ) {
			save( optional.slice() );
		} else if ( 'none' === choice ) {
			save( [] );
		} else if ( 'save' === choice ) {
			var chosen = [];
			ui.dialog.querySelectorAll( '[data-pbc-cookies-category]' ).forEach( function ( box ) {
				if ( ! box.disabled && box.checked ) {
					chosen.push( box.getAttribute( 'data-pbc-cookies-category' ) );
				}
			} );
			save( chosen );
		}
	}

	/* Permiso en el momento: al pulsar un vídeo o el mapa sin permiso previo. */
	function ask( category, onAllow, service ) {
		if ( allowed( category ) ) {
			onAllow();
			return;
		}
		if ( ! build() || ! ui.ask ) {
			return;
		}
		var name = ui.ask.querySelector( '[data-pbc-cookies-service]' );
		if ( name ) {
			name.textContent = service || '';
		}
		ui.ask.returnValue = '';
		ui.ask.addEventListener( 'close', function handler() {
			ui.ask.removeEventListener( 'close', handler );
			if ( 'allow' === ui.ask.returnValue ) {
				save( granted.concat( [ category ] ) );
				onAllow();
			}
		} );
		ui.ask.showModal();
	}

	/* ---------- Botones y enlaces ---------- */
	document.addEventListener( 'click', function ( event ) {
		var target = event.target.closest( 'button, a' );
		if ( ! target ) {
			return;
		}
		if ( target.hasAttribute( 'data-pbc-cookies-accept' ) ) {
			save( optional.slice() );
		} else if ( target.hasAttribute( 'data-pbc-cookies-reject' ) ) {
			save( [] );
		} else if ( target.hasAttribute( 'data-pbc-cookies-open' ) ) {
			event.preventDefault();
			open();
		} else if ( 'A' === target.tagName && config.anchor && target.hash === '#' + config.anchor ) {
			// «Configurar cookies» del pie: abre la ventana en lugar de ir a la política.
			event.preventDefault();
			open();
		}
	} );

	// Botón de la política de cookies: sin JavaScript no sirve, así que nace oculto.
	document.querySelectorAll( '.pbc-cookies-settings [data-pbc-cookies-open]' ).forEach( function ( button ) {
		button.hidden = false;
	} );

	// Llegada a la política con «#configurar-cookies» desde otra página.
	if ( config.anchor && location.hash === '#' + config.anchor ) {
		build();
		open();
	}

	window.pbcCookies = {
		allowed: allowed,
		require: ask,
		open: open
	};

	if ( ! decided && optional.length && build() ) {
		showBanner();
	}
}() );
