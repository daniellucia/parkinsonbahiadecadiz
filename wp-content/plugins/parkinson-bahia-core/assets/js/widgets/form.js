/**
 * Widget «Formulario»: envío sin recargar la página, errores junto a cada campo
 * y carga de Cloudflare Turnstile solo cuando la persona empieza a rellenar.
 * Sin dependencias. Sin JavaScript el formulario se envía de la forma normal.
 */
( function () {
	'use strict';

	var TURNSTILE_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
	var turnstileLoading = null;

	function loadTurnstile() {
		if ( ! turnstileLoading ) {
			turnstileLoading = new Promise( function ( resolve, reject ) {
				var script = document.createElement( 'script' );
				script.src = TURNSTILE_URL;
				script.async = true;
				script.onload = function () {
					resolve( window.turnstile );
				};
				script.onerror = reject;
				document.head.appendChild( script );
			} );
		}
		return turnstileLoading;
	}

	function setLoading( button, loading ) {
		if ( window.pbcButtonLoading ) {
			window.pbcButtonLoading( button, loading );
		} else if ( button ) {
			button.setAttribute( 'aria-busy', loading ? 'true' : 'false' );
		}
	}

	function setup( form ) {
		if ( form.dataset.pbcReady ) {
			return;
		}
		form.dataset.pbcReady = '1';

		var block = form.parentElement;
		var done = block.querySelector( '[data-pbc-form-done]' );
		var summary = form.querySelector( '[data-pbc-form-summary]' );
		var button = form.querySelector( '[type="submit"]' );
		var siteKey = form.getAttribute( 'data-pbc-turnstile' );
		var turnstileId = null;
		var sending = false;

		// Los mensajes de error los ponemos nosotros, junto a cada campo y en español claro.
		form.setAttribute( 'novalidate', '' );

		/* «Otra cantidad»: al escribir una cifra se marca sola esa opción. */
		var otherInput = form.querySelector( '[data-pbc-other-input]' );
		var otherRadio = form.querySelector( '[data-pbc-other-radio]' );
		if ( otherInput && otherRadio ) {
			otherInput.addEventListener( 'input', function () {
				if ( otherInput.value !== '' ) {
					otherRadio.checked = true;
				}
			} );
		}

		/* Turnstile se carga al primer contacto con el formulario, no con la página. */
		if ( siteKey ) {
			var startTurnstile = function () {
				form.removeEventListener( 'focusin', startTurnstile );
				form.removeEventListener( 'pointerdown', startTurnstile );
				loadTurnstile().then( function ( turnstile ) {
					var box = form.querySelector( '[data-pbc-turnstile-box]' );
					if ( turnstile && box && turnstileId === null ) {
						turnstileId = turnstile.render( box, { sitekey: siteKey, language: 'es', theme: 'light' } );
					}
				} ).catch( function () {} );
			};
			form.addEventListener( 'focusin', startTurnstile );
			form.addEventListener( 'pointerdown', startTurnstile );
		}

		function clearErrors() {
			form.querySelectorAll( '[data-pbc-error]' ).forEach( function ( error ) {
				error.hidden = true;
				error.textContent = '';
			} );
			form.querySelectorAll( '[aria-invalid="true"]' ).forEach( function ( input ) {
				input.removeAttribute( 'aria-invalid' );
			} );
			summary.hidden = true;
			summary.textContent = '';
		}

		function showErrors( errors ) {
			var list = document.createElement( 'ul' );
			var heading = document.createElement( 'strong' );
			var names = Object.keys( errors );

			heading.textContent = names.length === 1 ? 'Hay un dato que revisar:' : 'Hay ' + names.length + ' datos que revisar:';

			names.forEach( function ( name ) {
				var field = form.querySelector( '[data-pbc-field="' + name + '"]' );
				var item = document.createElement( 'li' );
				var input = field ? field.querySelector( 'input:not([type="hidden"])' ) : null;

				if ( field ) {
					var error = field.querySelector( '[data-pbc-error]' );
					error.textContent = errors[ name ];
					error.hidden = false;
					field.querySelectorAll( 'input' ).forEach( function ( element ) {
						element.setAttribute( 'aria-invalid', 'true' );
					} );
				}

				if ( input && input.id ) {
					var link = document.createElement( 'a' );
					link.href = '#' + input.id;
					link.textContent = errors[ name ];
					link.addEventListener( 'click', function ( event ) {
						event.preventDefault();
						input.focus();
					} );
					item.appendChild( link );
				} else {
					item.textContent = errors[ name ];
				}
				list.appendChild( item );
			} );

			summary.appendChild( heading );
			summary.appendChild( list );
			summary.hidden = false;
			summary.focus();
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			if ( sending ) {
				return;
			}
			sending = true;
			clearErrors();
			setLoading( button, true );

			// getAttribute y no form.action: el formulario tiene un campo llamado «action» que lo taparía.
			fetch( form.getAttribute( 'action' ), {
				method: 'POST',
				body: new FormData( form ),
				headers: { Accept: 'application/json' },
				credentials: 'same-origin'
			} ).then( function ( response ) {
				return response.json().catch( function () {
					return { ok: false, errors: { _form: 'No hemos podido enviar el formulario. Inténtalo de nuevo o llámanos por teléfono.' } };
				} );
			} ).then( function ( result ) {
				if ( result && result.ok ) {
					form.hidden = true;
					done.hidden = false;
					done.focus();
					return;
				}
				showErrors( ( result && result.errors ) || { _form: 'No hemos podido enviar el formulario. Inténtalo de nuevo o llámanos por teléfono.' } );
				if ( window.turnstile && turnstileId !== null ) {
					window.turnstile.reset( turnstileId );
				}
			} ).catch( function () {
				showErrors( { _form: 'Parece que no hay conexión. Comprueba tu internet e inténtalo de nuevo, o llámanos por teléfono.' } );
			} ).then( function () {
				sending = false;
				setLoading( button, false );
			} );
		} );
	}

	function init( root ) {
		( root || document ).querySelectorAll( '[data-pbc-form]' ).forEach( setup );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			init();
		} );
	} else {
		init();
	}

	// Vista previa del editor de Elementor: el widget se vuelve a pintar al cambiar sus ajustes.
	window.addEventListener( 'elementor/frontend/init', function () {
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/pbc-form.default', function ( $scope ) {
				init( $scope && $scope[ 0 ] ? $scope[ 0 ] : document );
			} );
		}
	} );
}() );
