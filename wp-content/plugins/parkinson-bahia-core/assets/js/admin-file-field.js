/**
 * Campo «archivo» de los tipos de contenido: elige un archivo de la biblioteca de medios.
 */
( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		var choose = event.target.closest( '[data-pbc-file-choose]' );
		var clear = event.target.closest( '[data-pbc-file-clear]' );
		var box = event.target.closest( 'p' );

		if ( ( ! choose && ! clear ) || ! box ) {
			return;
		}

		var input = box.querySelector( '[data-pbc-file-input]' );
		var name = box.querySelector( '[data-pbc-file-name]' );

		if ( clear ) {
			input.value = '';
			name.textContent = window.pbcFileField.none;
			return;
		}

		var frame = wp.media( { title: window.pbcFileField.title, button: { text: window.pbcFileField.button }, multiple: false } );

		frame.on( 'select', function () {
			var file = frame.state().get( 'selection' ).first().toJSON();
			input.value = file.id;
			name.textContent = file.filename || file.title;
		} );

		frame.open();
	} );
}() );
