/**
 * ExamHub Dark Mode fallback toggle.
 *
 * The active WP theme remains authoritative: if it has already set
 * `body.dark-mode` BEFORE this script runs, nothing here ever touches it
 * again — this whole script bails out immediately in that case. It only
 * kicks in on sites where no theme manages dark mode at all, driven by the
 * "ExamHub – Dark Mode Tokens" widget's Auto Detect/Force Dark Mode
 * switches.
 *
 * When it IS the one deciding, it sets BOTH `body.dark-mode` (so every
 * existing dark-mode rule in examhub-dark-mode.css — cards, library,
 * showcase, etc. — actually applies, not just the handful of newer rules
 * that also match the attribute below) AND `data-examhub-theme="dark"` on
 * <html> (consumed by the newer dual-selector rules). Since we only ever do
 * this when the theme hasn't already claimed `dark-mode`, there is no
 * collision risk.
 *
 * @since 2.0.0
 */
( function () {

	'use strict';

	function applyDark( apply ) {
		document.body.classList.toggle( 'dark-mode', apply );

		if ( apply ) {
			document.documentElement.setAttribute( 'data-examhub-theme', 'dark' );
		} else {
			document.documentElement.removeAttribute( 'data-examhub-theme' );
		}
	}

	function init() {

		if ( document.body.classList.contains( 'dark-mode' ) ) {
			// Theme already owns dark mode — never override it.
			return;
		}

		var carrier = document.querySelector( '.examhub-dark-mode-tokens' );

		if ( ! carrier ) {
			return;
		}

		var forceDark  = '1' === carrier.getAttribute( 'data-examhub-force-dark' );
		var autoDetect = '1' === carrier.getAttribute( 'data-examhub-auto-detect' );

		if ( forceDark ) {
			applyDark( true );
			return;
		}

		if ( ! autoDetect || ! window.matchMedia ) {
			return;
		}

		var query = window.matchMedia( '(prefers-color-scheme: dark)' );

		applyDark( query.matches );

		if ( query.addEventListener ) {
			query.addEventListener( 'change', function ( event ) {
				applyDark( event.matches );
			} );
		} else if ( query.addListener ) {
			// Older Safari/iOS fallback.
			query.addListener( function ( event ) {
				applyDark( event.matches );
			} );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

}() );
