/**
 * Párkinson Bahía de Cádiz · comportamiento del tema.
 * Menú plegable, tamaño de texto, alto contraste y copiar enlace.
 * Sin dependencias. Se carga con defer.
 */
( function () {
	'use strict';

	var root = document.documentElement;
	var labels = window.pbcTheme || {};

	function store( key, value ) {
		try {
			localStorage.setItem( key, value );
		} catch ( e ) {
			// Sin almacenamiento (modo privado): la preferencia dura solo esta página.
		}
	}

	/* ---------- Menú ----------
	   En móvil y tableta el menú es un panel que se despliega bajo la
	   cabecera con el botón «Menú». Se cierra con Escape, con el mismo botón,
	   al pulsar fuera o al salir del panel con el tabulador. */
	var header = document.querySelector( '[data-pbc-header]' );
	var toggle = document.querySelector( '[data-pbc-menu-toggle]' );
	var toggleLabel = document.querySelector( '[data-pbc-menu-label]' );
	var nav = document.getElementById( 'pbc-menu' );

	function isOpen() {
		return !! toggle && toggle.getAttribute( 'aria-expanded' ) === 'true';
	}

	/* El panel no puede pasar del borde de abajo de la pantalla: si no cabe, se desplaza por dentro. */
	function fitPanel() {
		if ( ! nav || ! header ) {
			return;
		}
		var top = header.getBoundingClientRect().bottom;
		nav.style.maxHeight = isOpen() ? Math.max( 160, window.innerHeight - top ) + 'px' : '';
	}

	function setMenu( open ) {
		if ( ! toggle || ! nav ) {
			return;
		}
		nav.classList.toggle( 'pbc-nav--open', open );
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		if ( toggleLabel ) {
			toggleLabel.textContent = open ? ( labels.menuClose || 'Cerrar' ) : ( labels.menuOpen || 'Menú' );
		}
		fitPanel();
	}

	if ( toggle && nav ) {
		toggle.hidden = false;
		toggle.addEventListener( 'click', function () {
			setMenu( ! isOpen() );
		} );
		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && isOpen() ) {
				setMenu( false );
				toggle.focus();
			}
		} );
		// Un clic fuera de la cabecera solo cierra el menú; no activa lo que había debajo.
		document.addEventListener( 'click', function ( event ) {
			if ( isOpen() && header && ! header.contains( event.target ) ) {
				event.preventDefault();
				setMenu( false );
			}
		}, true );
		// Con el tabulador, al salir del panel se cierra para no tapar lo que viene después.
		header.addEventListener( 'focusout', function ( event ) {
			if ( isOpen() && event.relatedTarget && ! header.contains( event.relatedTarget ) ) {
				setMenu( false );
			}
		} );
		// Un enlace a otra parte de la misma página: se cierra el panel para ver adónde se va.
		nav.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( 'a[href*="#"]' );
			if ( link && isOpen() ) {
				setMenu( false );
			}
		} );
	}

	/* ---------- Buscador de la cabecera (escritorio) ----------
	   «Buscar» abre una barra bajo la cabecera con el foco ya en la caja.
	   Se cierra con Escape, con el mismo botón, al pulsar fuera o al salir
	   con el tabulador. Sin JavaScript la barra se ve siempre. */
	var searchToggle = document.querySelector( '[data-pbc-search-toggle]' );
	var searchPanel = document.querySelector( '[data-pbc-search-panel]' );

	function searchIsOpen() {
		return !! searchToggle && searchToggle.getAttribute( 'aria-expanded' ) === 'true';
	}

	function setSearch( open, focusInput ) {
		if ( ! searchToggle || ! searchPanel ) {
			return;
		}
		searchPanel.classList.toggle( 'pbc-search-panel--open', open );
		searchToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		if ( open && focusInput ) {
			var input = searchPanel.querySelector( 'input[type="search"]' );
			if ( input ) {
				input.focus();
			}
		}
	}

	if ( searchToggle && searchPanel ) {
		searchToggle.hidden = false;
		searchToggle.addEventListener( 'click', function () {
			setSearch( ! searchIsOpen(), true );
		} );
		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && searchIsOpen() ) {
				setSearch( false );
				searchToggle.focus();
			}
		} );
		document.addEventListener( 'click', function ( event ) {
			if ( searchIsOpen() && header && ! header.contains( event.target ) ) {
				setSearch( false );
			}
		} );
		if ( header ) {
			header.addEventListener( 'focusout', function ( event ) {
				if ( searchIsOpen() && event.relatedTarget && ! header.contains( event.relatedTarget ) ) {
					setSearch( false );
				}
			} );
		}
	}

	/* El menú en línea necesita 1200 px con el texto normal; con el texto
	   ampliado necesita más, así que el punto de corte crece con él. */
	function updateNavMode() {
		var scale = parseFloat( getComputedStyle( root ).fontSize ) / 18 || 1;
		var compact = window.innerWidth < 1200 * scale;

		if ( compact ) {
			root.setAttribute( 'data-nav', 'compact' );
			setSearch( false );
			fitPanel();
		} else {
			root.removeAttribute( 'data-nav' );
			setMenu( false );
		}
	}

	var resizeFrame = 0;
	window.addEventListener( 'resize', function () {
		cancelAnimationFrame( resizeFrame );
		resizeFrame = requestAnimationFrame( updateNavMode );
	} );
	updateNavMode();

	var scrollFrame = 0;
	window.addEventListener( 'scroll', function () {
		if ( isOpen() ) {
			cancelAnimationFrame( scrollFrame );
			scrollFrame = requestAnimationFrame( fitPanel );
		}
	}, { passive: true } );

	/* ---------- Opciones de lectura ----------
	   Hay dos juegos de botones (barra superior y menú móvil); se mueven a la vez. */
	var tools = document.querySelectorAll( '[data-pbc-reading]' );

	if ( tools.length ) {
		var sizeButtons = document.querySelectorAll( '[data-pbc-fs]' );
		var contrastButtons = document.querySelectorAll( '[data-pbc-contrast]' );

		var syncButtons = function () {
			var current = root.getAttribute( 'data-fs' ) || '0';
			var high = root.getAttribute( 'data-contrast' ) === 'high';
			sizeButtons.forEach( function ( button ) {
				button.setAttribute( 'aria-pressed', button.getAttribute( 'data-pbc-fs' ) === current ? 'true' : 'false' );
			} );
			contrastButtons.forEach( function ( button ) {
				button.setAttribute( 'aria-pressed', high ? 'true' : 'false' );
			} );
		};

		sizeButtons.forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var size = button.getAttribute( 'data-pbc-fs' );
				if ( size === '0' ) {
					root.removeAttribute( 'data-fs' );
				} else {
					root.setAttribute( 'data-fs', size );
				}
				store( 'pbc_fs', size );
				syncButtons();
				updateNavMode();
			} );
		} );

		contrastButtons.forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var high = root.getAttribute( 'data-contrast' ) !== 'high';
				if ( high ) {
					root.setAttribute( 'data-contrast', 'high' );
				} else {
					root.removeAttribute( 'data-contrast' );
				}
				store( 'pbc_contrast', high ? '1' : '0' );
				syncButtons();
			} );
		} );

		syncButtons();
		tools.forEach( function ( group ) {
			group.hidden = false;
		} );
	}

	/* ---------- Copiar enlace ---------- */
	var copyButton = document.querySelector( '[data-pbc-copy]' );
	var copyStatus = document.querySelector( '[data-pbc-copy-status]' );

	if ( copyButton && navigator.clipboard ) {
		copyButton.hidden = false;
		copyButton.addEventListener( 'click', function () {
			navigator.clipboard.writeText( copyButton.getAttribute( 'data-pbc-copy' ) ).then( function () {
				var done = labels.linkCopied || 'Enlace copiado';
				copyButton.textContent = done;
				if ( copyStatus ) {
					copyStatus.textContent = done;
				}
			} ).catch( function () {} );
		} );
	}

	/* ---------- Botón de envío con indicador de carga ----------
	   Al enviar un formulario, su botón muestra un spinner y no admite más
	   pulsaciones. Los formularios enviados con fetch lo apagan ellos con
	   window.pbcButtonLoading( boton, false ). */
	function setButtonLoading( button, loading ) {
		if ( ! button ) {
			return;
		}
		if ( loading ) {
			button.dataset.pbcLabel = button.textContent;
			button.style.minWidth = button.offsetWidth + 'px';
			button.classList.add( 'pbc-button--loading' );
			button.setAttribute( 'aria-busy', 'true' );
			if ( button.dataset.pbcLoadingText ) {
				button.textContent = button.dataset.pbcLoadingText;
			}
		} else {
			button.classList.remove( 'pbc-button--loading' );
			button.removeAttribute( 'aria-busy' );
			button.style.minWidth = '';
			if ( button.dataset.pbcLabel ) {
				button.textContent = button.dataset.pbcLabel;
			}
		}
	}

	window.pbcButtonLoading = setButtonLoading;

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target;
		var button = event.submitter && event.submitter.classList.contains( 'pbc-button' ) ? event.submitter : form.querySelector( '.pbc-button[type="submit"]' );

		if ( ! button ) {
			return;
		}
		// Segunda pulsación mientras se envía: se ignora.
		if ( button.classList.contains( 'pbc-button--loading' ) ) {
			event.preventDefault();
			return;
		}
		if ( ! event.defaultPrevented ) {
			setButtonLoading( button, true );
		}
	} );

	// Al volver atrás con el navegador la página se recupera tal cual: se apaga el indicador.
	window.addEventListener( 'pageshow', function () {
		document.querySelectorAll( '.pbc-button--loading' ).forEach( function ( button ) {
			setButtonLoading( button, false );
		} );
	} );
}() );
