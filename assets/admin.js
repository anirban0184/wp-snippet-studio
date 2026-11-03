( function () {
	'use strict';
	document.addEventListener( 'click', async function ( event ) {
		var confirmation = event.target.closest( '[data-confirm]' );
		if ( confirmation && ! window.confirm( confirmation.dataset.confirm ) ) {
			event.preventDefault();
		}
		var copy = event.target.closest( '.wpsstudio-copy' );
		if ( ! copy ) { return; }
		var input = document.getElementById( copy.dataset.target );
		if ( ! input ) { return; }
		var message;
		try {
			await navigator.clipboard.writeText( input.value );
			message = copy.dataset.copied;
		} catch ( error ) {
			input.focus();
			input.select();
			message = copy.dataset.manual;
		}
		var feedback = document.querySelector( '.wpsstudio-copy-feedback' );
		if ( feedback ) { feedback.textContent = message; }
	} );
	var form = document.querySelector( '.wpsstudio-editor-form' );
	if ( ! form ) { return; }
	var type = document.getElementById( 'wpsstudio-type' );
	var source = document.getElementById( 'wpsstudio-code' );
	var editor = null;
	if ( form.dataset.enhance === '1' && window.wp && wp.codeEditor ) {
		editor = wp.codeEditor.initialize( source );
		source.required = false;
	}
	function refresh() {
		if ( editor ) { editor.codemirror.setOption( 'mode', type.value === 'html' ? 'htmlmixed' : 'text/plain' ); }
		document.getElementById( 'wpsstudio-content-help' ).textContent = type.value === 'html' ? source.dataset.htmlHelp : source.dataset.textHelp;
	}
	type.addEventListener( 'change', refresh );
	form.addEventListener( 'submit', function ( event ) {
		if ( editor ) { editor.codemirror.save(); }
		if ( ! source.value.trim() ) {
			event.preventDefault();
			if ( editor ) { editor.codemirror.focus(); } else { source.focus(); }
		}
	} );
	refresh();
}() );
