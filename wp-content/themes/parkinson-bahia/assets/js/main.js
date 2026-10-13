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

	/* ---------- Menú ---------- */
	var toggle = document.querySelector( '[data-pbc-menu-toggle]' );
	var nav = document.getElementById( 'pbc-menu' );

	function setMenu( open ) {
		if ( ! toggle || ! nav ) {
			return;
		}
		nav.classList.toggle( 'pbc-nav--open', open );
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		toggle.textContent = open ? ( labels.menuClose || 'Cerrar' ) : ( labels.menuOpen || 'Menú' );
	}

	if ( toggle && nav ) {
		toggle.hidden = false;
		toggle.addEventListener( 'click', function () {
			setMenu( toggle.getAttribute( 'aria-expanded' ) !== 'true' );
		} );
		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && toggle.getAttribute( 'aria-expanded' ) === 'true' ) {
				setMenu( false );
				toggle.focus();
			}
		} );
	}

	/* El menú en línea necesita 1200 px con el texto normal; con el texto
	   ampliado necesita más, así que el punto de corte crece con él. */
	function updateNavMode() {
		var scale = parseFloat( getComputedStyle( root ).fontSize ) / 18 || 1;
		var compact = window.innerWidth < 1200 * scale;

		if ( compact ) {
			root.setAttribute( 'data-nav', 'compact' );
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

	/* ---------- Opciones de lectura ---------- */
	var tools = document.querySelector( '[data-pbc-reading]' );

	if ( tools ) {
		var sizeButtons = tools.querySelectorAll( '[data-pbc-fs]' );
		var contrastButton = tools.querySelector( '[data-pbc-contrast]' );

		var syncButtons = function () {
			var current = root.getAttribute( 'data-fs' ) || '0';
			sizeButtons.forEach( function ( button ) {
				button.setAttribute( 'aria-pressed', button.getAttribute( 'data-pbc-fs' ) === current ? 'true' : 'false' );
			} );
			if ( contrastButton ) {
				contrastButton.setAttribute( 'aria-pressed', root.getAttribute( 'data-contrast' ) === 'high' ? 'true' : 'false' );
			}
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

		if ( contrastButton ) {
			contrastButton.addEventListener( 'click', function () {
				var high = root.getAttribute( 'data-contrast' ) !== 'high';
				if ( high ) {
					root.setAttribute( 'data-contrast', 'high' );
				} else {
					root.removeAttribute( 'data-contrast' );
				}
				store( 'pbc_contrast', high ? '1' : '0' );
				syncButtons();
			} );
		}

		syncButtons();
		tools.hidden = false;
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
