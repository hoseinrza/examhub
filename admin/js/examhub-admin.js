(function( $ ) {
	'use strict';

	function initResourceFields() {

		$( '.examhub-resource-field' ).each( function() {

			var $field       = $( this );
			var $tabs        = $field.find( '.examhub-resource-tab' );
			var $filePanel   = $field.find( '.examhub-resource-panel--file' );
			var $urlPanel    = $field.find( '.examhub-resource-panel--url' );
			var $fileInput   = $filePanel.find( '.examhub-file-input' );
			var $fileName    = $filePanel.find( '.examhub-file-name' );
			var $fileSelect  = $filePanel.find( '.examhub-file-select' );
			var $fileRemove  = $filePanel.find( '.examhub-file-remove' );
			var $urlInput    = $urlPanel.find( '.examhub-url-input' );
			var frame;

			// Tab switching
			$tabs.on( 'click', function( e ) {
				e.preventDefault();

				var $btn = $( this );
				var tab = $btn.data( 'tab' );

				$tabs.removeClass( 'active' );
				$field.find( '.examhub-resource-panel' ).removeClass( 'active' );

				$btn.addClass( 'active' );
				$field.find( '.examhub-resource-panel--' + tab ).addClass( 'active' );

				$field.attr( 'data-resource-type', tab );
			} );

			// File picker
			$fileSelect.on( 'click', function( e ) {
				e.preventDefault();

				if ( frame ) {
					frame.open();
					return;
				}

				frame = wp.media( {
					title: examhubAdmin.selectFileLabel,
					button: { text: examhubAdmin.useFileLabel },
					multiple: false
				} );

				frame.on( 'select', function() {
					var attachment = frame.state().get( 'selection' ).first().toJSON();

					$fileInput.val( attachment.id );
					$fileName.text( attachment.filename || attachment.title );
					$fileRemove.show();
					$urlInput.val( '' );
				} );

				frame.open();
			} );

			// File remove
			$fileRemove.on( 'click', function( e ) {
				e.preventDefault();

				$fileInput.val( '' );
				$fileName.text( examhubAdmin.noFileLabel );
				$fileRemove.hide();
			} );

			// URL input change
			$urlInput.on( 'input', function() {
				if ( $( this ).val().trim() ) {
					$fileInput.val( '' );
					$fileName.text( examhubAdmin.noFileLabel );
					$fileRemove.hide();
				}
			} );
		} );
	}

	// Validate on form submit
	function initFormValidation() {

		var $form = $( '#post' );

		$form.on( 'submit', function() {

			var isValid = true;

			$( '.examhub-resource-field' ).each( function() {

				var $field = $( this );
				var $fileInput = $field.find( '.examhub-file-input' );
				var $urlInput = $field.find( '.examhub-url-input' );
				var hasFile = $fileInput.val().trim() !== '';
				var hasUrl = $urlInput.val().trim() !== '';

				if ( ! hasFile && ! hasUrl ) {
					$field.addClass( 'examhub-resource-field--error' );
					isValid = false;
				} else {
					$field.removeClass( 'examhub-resource-field--error' );
				}
			} );

			if ( ! isValid ) {
				alert( examhubAdmin.resourceRequiredLabel );
				return false;
			}
		} );
	}

	$( function() {
		initResourceFields();
		initFormValidation();
	} );

})( jQuery );
