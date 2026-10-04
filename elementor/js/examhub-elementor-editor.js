/**
 * Cascading پایه › رشته controls for the ExamHub Exam Showcase widget.
 *
 * Every term is registered PHP-side, so this script prunes the child control's
 * option list when its parent changes. Progressive enhancement only: if the
 * Elementor panel API differs, the controls stay plain independent SELECT2s.
 */

( function ( $ ) {
	'use strict';

	if ( 'undefined' === typeof elementor || ! elementor.hooks || 'undefined' === typeof examhubElementorEditor ) {
		return;
	}

	// Child control name => [ taxonomy slug, parent control name ].
	var CASCADE = {
		examhub_field: [ 'examhub_field', 'examhub_grade' ]
	};

	/**
	 * The controls-stack view that owns the control views. The third hook
	 * argument is the element (widget) view, which has no getControlViewByName(),
	 * so resolve the panel's current page view instead.
	 */
	function getControlsView( fallback ) {

		var panelView = ( elementor.getPanelView && elementor.getPanelView() ) || null;
		var page      = panelView && panelView.getCurrentPageView ? panelView.getCurrentPageView() : null;

		if ( page && 'function' === typeof page.getControlViewByName ) {
			return page;
		}

		return fallback && 'function' === typeof fallback.getControlViewByName ? fallback : null;
	}

	function setControlOptions( view, controlName, options ) {

		if ( ! view ) {
			return;
		}

		var controlView = view.getControlViewByName( controlName );

		if ( ! controlView || ! controlView.model ) {
			return;
		}

		controlView.model.set( 'options', $.extend( { '': examhubElementorEditor.i18n.all }, options ) );
		controlView.render();
	}

	function refreshDependentControl( fallbackView, settingsModel, controlName, taxonomy, parentValue ) {

		if ( ! parentValue ) {
			setControlOptions( getControlsView( fallbackView ), controlName, {} );
			settingsModel.set( controlName, '' );
			return;
		}

		$.post( examhubElementorEditor.ajax_url, {
			action: 'examhub_dependent_terms',
			nonce: examhubElementorEditor.nonce,
			taxonomy: taxonomy,
			parent_term_id: parentValue
		} ).done( function ( response ) {

			if ( ! response || ! response.success ) {
				return;
			}

			var options      = response.data.options || {};
			var currentValue = settingsModel.get( controlName );
			var stillValid   = currentValue && Object.prototype.hasOwnProperty.call( options, String( currentValue ) );

			setControlOptions( getControlsView( fallbackView ), controlName, options );

			if ( ! stillValid ) {
				settingsModel.set( controlName, '' );
			}
		} );
	}

	elementor.hooks.addAction( 'panel/open_editor/widget/examhub_exam_showcase', function ( panel, model, view ) {

		var settingsModel = model.get( 'settings' );

		if ( ! settingsModel ) {
			return;
		}

		$.each( CASCADE, function ( controlName, config ) {

			var taxonomy      = config[ 0 ];
			var parentControl = config[ 1 ];

			// Re-opening the panel must not stack listeners (one AJAX per
			// listener): drop the handler registered on the previous open.
			settingsModel._examhubCascade = settingsModel._examhubCascade || {};

			if ( settingsModel._examhubCascade[ controlName ] ) {
				settingsModel.off( 'change:' + parentControl, settingsModel._examhubCascade[ controlName ] );
			}

			settingsModel._examhubCascade[ controlName ] = function () {
				refreshDependentControl( view, settingsModel, controlName, taxonomy, settingsModel.get( parentControl ) );
			};

			settingsModel.on( 'change:' + parentControl, settingsModel._examhubCascade[ controlName ] );
		} );
	} );

}( jQuery ) );
