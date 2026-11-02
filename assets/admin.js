( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		var control = event.target.closest( '[data-confirm]' );
		if ( control && ! window.confirm( control.dataset.confirm ) ) {
			event.preventDefault();
		}
	} );

	var form = document.querySelector( '.wpsstudio-editor-form' );
	if ( ! form ) {
		return;
	}
	var type = document.getElementById( 'wpsstudio-type' );
	var scope = document.getElementById( 'wpsstudio-scope' );
	var source = document.getElementById( 'wpsstudio-code' );
	var editor = null;
	if ( window.wp && wp.codeEditor && window.wpsstudioEditorSettings ) {
		editor = wp.codeEditor.initialize( source, window.wpsstudioEditorSettings );
		// Native validation cannot focus a required textarea hidden by CodeMirror.
		source.required = false;
	}
	var modes = { php: 'text/x-php', css: 'text/css', js: 'javascript', html: 'htmlmixed', text: 'text/plain' };
	function refresh() {
		var value = type.value;
		var php = value === 'php';
		var content = value === 'html' || value === 'text';
		form.querySelector( '.wpsstudio-php-settings' ).hidden = ! php;
		form.querySelector( '.wpsstudio-shortcode-info' ).hidden = ! content;
		form.querySelector( '.wpsstudio-priority-setting' ).hidden = content || ( php && scope.value === 'manual' );
		if ( editor ) {
			editor.codemirror.setOption( 'mode', modes[ value ] || 'text/plain' );
		}
		// Help text is translated in the PHP view through data attributes below.
		var help = document.getElementById( 'wpsstudio-code-help' );
		help.textContent = source.dataset[ value + 'Help' ] || '';
	}
	type.addEventListener( 'change', refresh );
	scope.addEventListener( 'change', refresh );
	form.addEventListener( 'submit', function ( event ) {
		if ( editor ) {
			editor.codemirror.save();
		}
		if ( ! source.value.trim() ) {
			event.preventDefault();
			if ( editor ) {
				editor.codemirror.focus();
			} else {
				source.focus();
			}
		}
	} );
	refresh();
}() );
