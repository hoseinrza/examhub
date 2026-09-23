/**
 * Cascading مقطع › پایه › رشته › درس controls for the Exam Showcase widget's
 * Elementor editor panel. Each control is registered (PHP-side) with every
 * term in its taxonomy — Elementor controls can't lazily fetch remote
 * options — so this script prunes the next control's option list the moment
 * its parent control changes, using the same examhub_dependent_terms AJAX
 * action the post-edit metabox's cascading selects use.
 *
 * Progressive enhancement only: if a future Elementor version changes the
 * internal panel APIs this relies on (getControlViewByName, the settings
 * model's change events), the four controls simply keep working as plain,
 * independent SELECT2s — nothing here is required for the widget to render
 * or save correctly.
 */

( function ( $ ) {
	'use strict';

	if ( 'undefined' === typeof elementor || ! elementor.hooks || 'undefined' === typeof examhubElementorEditor ) {
		return;
	}

	// Child control name => [ taxonomy slug, parent control name ].
	var CASCADE = {
		examhub_grade:   [ 'examhub_grade', 'examhub_level' ],
		examhub_field:   [ 'examhub_field', 'examhub_grade' ],
		examhub_subject: [ 'examhub_subject', 'examhub_field' ]
	};

	/**
	 * Rebuild a control's SELECT2 option list and re-render it.
	 *
	 * @param {Backbone.View} view        The widget's controls-stack view.
	 * @param {string}        controlName Control name to refresh.
	 * @param {Object}        options     New term_id => name map.
	 */
	function setControlOptions( view, controlName, options ) {

		if ( ! view || 'function' !== typeof view.getControlViewByName ) {
			return;
		}

		var controlView = view.getControlViewByName( controlName );

		if ( ! controlView || ! controlView.model ) {
			return;
		}

		var withPlaceholder = $.extend( { '': examhubElementorEditor.i18n.all }, options );

		controlView.model.set( 'options', withPlaceholder );
		controlView.render();
	}

	/**
	 * Fetch the dependent terms for one cascading control and apply them,
	 * clearing the control's current value if it's no longer valid.
	 *
	 * @param {Backbone.View} view           The widget's controls-stack view.
	 * @param {Backbone.Model} settingsModel The widget's settings model.
	 * @param {string} controlName           Control to refresh (e.g. "examhub_grade").
	 * @param {string} taxonomy              Its taxonomy slug.
	 * @param {string|number} parentValue    The parent control's current term ID.
	 */
	function refreshDependentControl( view, settingsModel, controlName, taxonomy, parentValue ) {

		if ( ! parentValue ) {
			setControlOptions( view, controlName, {} );
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

			var options       = response.data.options || {};
			var currentValue  = settingsModel.get( controlName );
			var stillValid    = currentValue && options.hasOwnProperty( String( currentValue ) );

			setControlOptions( view, controlName, options );

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

			settingsModel.on( 'change:' + parentControl, function () {
				refreshDependentControl( view, settingsModel, controlName, taxonomy, settingsModel.get( parentControl ) );
			} );
		} );
	} );

}( jQuery ) );
