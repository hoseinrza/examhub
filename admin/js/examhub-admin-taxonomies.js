/**
 * Cascading مقطع ›پایه › رشته › درس selects on the exam "Classification"
 * metabox. The initial render (metabox-exam-taxonomies.php) always lists
 * every term in every dropdown so an exam's already-saved value is never
 * hidden; this script only prunes a child select's options going forward,
 * the moment its parent select changes.
 *
 * Talks to the examhub_dependent_terms AJAX action (Examhub_Ajax::dependent_terms()),
 * the same endpoint the Elementor editor's cascading Exam Showcase control uses.
 *
 * Select2-aware: WordPress core (and several admin/page-builder plugins)
 * enhance ordinary <select> elements with Select2, which hides the native
 * element and renders its own widget on top. Reading/writing .val() on the
 * original element still works either way, but two things need extra care:
 * Select2 intercepts the native "change" with its own events when the user
 * picks an option through its UI, and it caches its own rendered option list
 * — so plain DOM option swaps via jQuery don't show up in the visible widget
 * until it's explicitly told to refresh.
 */

( function ( $ ) {
	'use strict';

	if ( 'undefined' === typeof examhubAdminTaxonomies || ! examhubAdminTaxonomies.ajax_url ) {
		return;
	}

	/**
	 * Events that signal "this select's value changed," covering both a
	 * plain native <select> and one Select2 has enhanced.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	var CHANGE_EVENTS = 'change select2:select select2:clear select2:unselect';

	/**
	 * If Select2 has enhanced this select, tell it to re-render so its
	 * visible widget reflects the new <option> list and selected value just
	 * written via plain DOM manipulation — Select2 doesn't pick this up on
	 * its own. A no-op for a plain, unenhanced <select>.
	 *
	 * @param {jQuery} $select
	 */
	function refreshSelect2( $select ) {

		if ( $.fn.select2 && $select.hasClass( 'select2-hidden-accessible' ) ) {
			$select.trigger( 'change.select2' );
		}
	}

	/**
	 * Find the <select> that cascades FROM the given taxonomy (i.e. the one
	 * whose data-examhub-cascade-parent equals it), if any.
	 *
	 * @param {string} taxonomy Parent taxonomy slug.
	 * @return {jQuery}
	 */
	function findChildSelect( taxonomy ) {

		return $( '[data-examhub-cascade-parent="' + taxonomy + '"]' );
	}

	/**
	 * Refresh a cascading child select's options for the given parent term ID,
	 * then cascade the same refresh onward to its own child (if any).
	 *
	 * @param {jQuery} $select        The child <select> to repopulate.
	 * @param {number} parentTermId   The chosen parent term ID (0 clears it).
	 */
	function refreshCascadingSelect( $select, parentTermId ) {

		var taxonomy = $select.data( 'examhub-cascade' );
		var previousValue = $select.val();

		if ( ! parentTermId ) {
			resetSelect( $select );
			return;
		}

		$select.prop( 'disabled', true );

		$.post( examhubAdminTaxonomies.ajax_url, {
			action: 'examhub_dependent_terms',
			nonce: examhubAdminTaxonomies.nonce,
			taxonomy: taxonomy,
			parent_term_id: parentTermId
		} )
			.done( function ( response ) {

				if ( ! response || ! response.success ) {
					resetSelect( $select );
					return;
				}

				var options = response.data.options || {};
				var hasPreviousValue = false;

				$select.empty().append(
					$( '<option></option>' ).attr( 'value', '0' ).text( examhubAdminTaxonomies.unselectedLabel )
				);

				$.each( options, function ( termId, name ) {

					$select.append( $( '<option></option>' ).attr( 'value', termId ).text( name ) );

					if ( String( termId ) === String( previousValue ) ) {
						hasPreviousValue = true;
					}
				} );

				$select.val( hasPreviousValue ? previousValue : '0' );
				refreshSelect2( $select );
			} )
			.fail( function () {
				resetSelect( $select );
			} )
			.always( function () {
				$select.prop( 'disabled', false );
				cascadeOnward( $select );
			} );
	}

	/**
	 * Collapse a child select down to just the placeholder, e.g. when its
	 * parent is cleared.
	 *
	 * @param {jQuery} $select
	 */
	function resetSelect( $select ) {

		$select.empty().append(
			$( '<option></option>' ).attr( 'value', '0' ).text( examhubAdminTaxonomies.unselectedLabel )
		);
		refreshSelect2( $select );
		cascadeOnward( $select );
	}

	/**
	 * Propagate a cascading refresh to whatever select depends on $select.
	 *
	 * @param {jQuery} $select The select that just changed/refreshed.
	 */
	function cascadeOnward( $select ) {

		var taxonomy = $select.data( 'examhub-cascade' );
		var $child   = findChildSelect( taxonomy );

		if ( $child.length ) {
			refreshCascadingSelect( $child, parseInt( $select.val(), 10 ) || 0 );
		}
	}

	$( document ).on( CHANGE_EVENTS, '[data-examhub-cascade]', function () {

		cascadeOnward( $( this ) );
	} );

}( jQuery ) );
