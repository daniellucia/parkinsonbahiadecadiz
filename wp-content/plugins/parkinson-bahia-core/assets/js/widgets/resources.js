/**
 * Widget «Guías para descargar»: filtro por tema.
 *
 * Sin JavaScript el filtro queda oculto y se ve la lista entera. Cada botón
 * indica con aria-pressed si está elegido y un aviso role="status" dice
 * cuántas guías se muestran.
 */
( function () {
	'use strict';

	function init( root ) {
		var filter = root.querySelector( '[data-pbc-resources-filter]' );
		var list = root.querySelector( '[data-pbc-resources-list]' );
		var status = root.querySelector( '[data-pbc-resources-status]' );

		if ( ! filter || ! list || filter.dataset.pbcReady ) {
			return;
		}
		filter.dataset.pbcReady = '1';
		filter.hidden = false;

		filter.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( 'button[data-topic]' );
			if ( ! button ) {
				return;
			}

			var topic = button.dataset.topic;
			var shown = 0;

			filter.querySelectorAll( 'button[data-topic]' ).forEach( function ( item ) {
				item.setAttribute( 'aria-pressed', item === button ? 'true' : 'false' );
			} );

			list.querySelectorAll( '[data-topic]' ).forEach( function ( item ) {
				var visible = '' === topic || item.dataset.topic === topic;
				item.hidden = ! visible;
				if ( visible ) {
					shown++;
				}
			} );

			if ( status ) {
				status.textContent = 1 === shown ? status.dataset.one : status.dataset.many.replace( '%d', String( shown ) );
			}
		} );
	}

	function boot( scope ) {
		( scope || document ).querySelectorAll( '.pbc-resources' ).forEach( init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			boot();
		} );
	} else {
		boot();
	}

	// Vista previa del editor de Elementor.
	window.addEventListener( 'elementor/frontend/init', function () {
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/pbc-resources.default', function ( $scope ) {
				boot( $scope[ 0 ] );
			} );
		}
	} );
}() );
