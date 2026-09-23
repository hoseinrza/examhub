/**
 * Shared front-end behaviour for every interactive ExamHub widget:
 * Download Library (chips + load more), Featured Exams (tabs),
 * Search & Filter (live filtering), and Exam Mega Library (lazy accordion).
 *
 * All AJAX goes through the two endpoints registered in Examhub_Ajax —
 * `examhub_query_exams` (filter/paginate exam cards) and
 * `examhub_mega_branch` (discover which child terms a branch actually has —
 * used only by the mega library tree). Both require examhub_vars.nonce,
 * localized alongside examhub_vars.ajax_url by Examhub_Public::enqueue_scripts().
 *
 * Event delegation is used throughout because every one of these widgets
 * replaces or appends markup that didn't exist when the page first loaded.
 */

( function ( $ ) {
	'use strict';

	if ( 'undefined' === typeof examhub_vars ) {
		return;
	}

	var i18n = examhub_vars.i18n || {};

	/**
	 * POST to the shared ExamHub AJAX endpoint with full AJAX control.
	 * Uses $.ajax to set cache and headers for better firewall compatibility.
	 *
	 * @param {string} action AJAX action name (without the wp_ajax_ prefix).
	 * @param {Object} data   Extra request parameters.
	 * @return {jQuery.jqXHR}
	 */
	function examhubRequest( action, data ) {

		var payload = $.extend( { action: action, nonce: examhub_vars.nonce }, data );

		return $.ajax( {
			url: examhub_vars.ajax_url,
			type: 'POST',
			data: payload,
			cache: false,
			dataType: 'json',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-Requested-With', 'XMLHttpRequest' );
			},
			timeout: 30000
		} )
		.fail( function ( jqXHR, textStatus, errorThrown ) {
			console.warn( 'ExamHub AJAX request failed:', action, textStatus, errorThrown );
		} );
	}

	/**
	 * Strip the "examhub_" taxonomy prefix to get the filter key that
	 * Examhub_Query::TAXONOMY_MAP / Examhub_Ajax::query_exams expect
	 * (e.g. "examhub_level" -> "level", "examhub_exam_type" -> "exam_type").
	 *
	 * @param {string} taxonomy Full taxonomy slug.
	 * @return {string}
	 */
	function filterKey( taxonomy ) {

		return ( taxonomy || '' ).replace( /^examhub_/, '' );
	}

	/* ---------------------------------------------------------------------
	 * Shared "Show More / Show Less" toggle button (.examhub-toggle-btn),
	 * used by both the Download Library and Search & Filter footers.
	 * ------------------------------------------------------------------- */

	/**
	 * Reflect the current page/total-pages into the button's data-state:
	 * "more" while pages remain, "less" once the last page has loaded (only
	 * if the widget's "تبدیل به نمایش کمتر" switch is on — otherwise it just
	 * hides, matching the previous behaviour).
	 *
	 * @param {jQuery} $btn      The .examhub-toggle-btn element.
	 * @param {number} paged     Page currently loaded.
	 * @param {number} maxPages  Total pages available for the current filters.
	 */
	function updateToggleButtonState( $btn, paged, maxPages ) {

		var toggleEnabled = '1' === String( $btn.data( 'toggle-enabled' ) );

		if ( paged < maxPages ) {
			$btn.attr( 'data-state', 'more' ).removeClass( 'examhub-hidden' );
			return;
		}

		if ( maxPages <= 1 ) {

			// Everything that exists already fits on the one page that's
			// already rendered — there's nothing left to load and nothing
			// previously-loaded to collapse, so the button stays hidden
			// regardless of the "تبدیل به نمایش کمتر" switch. (Previously
			// this inverted the switch instead — `examhub_count` results or
			// fewer would still show the button after an AJAX filter
			// change, with nothing for it to do.)
			$btn.attr( 'data-state', 'more' ).addClass( 'examhub-hidden' );
			return;
		}

		if ( toggleEnabled ) {
			$btn.attr( 'data-state', 'less' ).removeClass( 'examhub-hidden' );
		} else {
			$btn.addClass( 'examhub-hidden' );
		}
	}

	/**
	 * Collapse a result grid back to its first batch, entirely client-side
	 * (no AJAX round-trip needed since every loaded card is already in the
	 * DOM). Animates the extra cards out, then flips the button back to its
	 * "more" state and scrolls it into view so the page doesn't jump.
	 *
	 * @param {jQuery} $btn          The .examhub-toggle-btn element.
	 * @param {jQuery} $items        All currently-loaded item nodes in the grid.
	 * @param {number} initialCount  How many items the first page loaded.
	 */
	function collapseToggleGrid( $btn, $items, initialCount ) {

		var $extra = initialCount > 0 ? $items.slice( initialCount ) : $items.slice( 0 );

		if ( ! $extra.length ) {
			$btn.attr( 'data-state', 'more' );
			return;
		}

		$btn.prop( 'disabled', true );

		$extra.stop( true, true ).slideUp( 200, function () {
			$( this ).remove();
		} );

		window.setTimeout( function () {

			$btn.attr( 'data-state', 'more' ).prop( 'disabled', false );

			var el = $btn.get( 0 );

			if ( el && el.scrollIntoView ) {
				el.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			}
		}, 220 );
	}

	/* ---------------------------------------------------------------------
	 * Download Library — filter sidebar (dropdowns + search) + "load more"
	 * ------------------------------------------------------------------- */

	/**
	 * Read the sidebar's current dropdown + search selections into a query
	 * args object. Stateless, so the same call serves both a filter change
	 * (page 1, replace) and "load more" (next page, append).
	 *
	 * @param {jQuery} $library The widget root element.
	 * @return {Object}
	 */
	function collectLibraryFilters( $library ) {

		var args = {};

		$library.find( '.examhub-library__select' ).each( function () {

			var $select = $( this );
			var value   = $select.val();

			if ( value ) {
				args[ $select.data( 'filter' ) ] = value;
			}
		} );

		var search = $library.find( '.examhub-library__search' ).val();

		if ( search ) {
			args.search = search;
		}

		return args;
	}

	function loadLibraryPage( $library, paged, append ) {

		var $grid     = $library.find( '.examhub-library__grid' );
		var $loadMore = $library.find( '.examhub-library__load-more' );

		var args = $.extend(
			{
				per_page:   $library.data( 'per-page' ),
				paged:      paged,
				show_image: $library.data( 'show-image' ),
				show_stats: $library.data( 'show-stats' ),
				orderby:    'latest'
			},
			collectLibraryFilters( $library )
		);

		$library.addClass( 'is-loading' );
		$loadMore.prop( 'disabled', true );

		examhubRequest( 'examhub_query_exams', args )
			.done( function ( response ) {

				if ( ! response || ! response.success ) {
					return;
				}

				if ( append ) {
					$grid.append( $( response.data.html ).children() );
				} else {
					$grid.html( response.data.html );
				}

				$library.data( 'paged', response.data.paged );
				$library.data( 'max-pages', response.data.max_pages );

				updateToggleButtonState( $loadMore, response.data.paged, response.data.max_pages );
			} )
			.always( function () {
				$library.removeClass( 'is-loading' );
				$loadMore.prop( 'disabled', false );
			} );
	}

	function reloadLibrary( $library ) {

		$library.data( 'paged', 1 );
		loadLibraryPage( $library, 1, false );
	}

	$( document ).on( 'change', '.examhub-library__select', function () {

		reloadLibrary( $( this ).closest( '.examhub-library' ) );
	} );

	$( document ).on( 'keydown', '.examhub-library__search', function ( e ) {

		if ( 'Enter' === e.key ) {
			e.preventDefault();
			reloadLibrary( $( this ).closest( '.examhub-library' ) );
		}
	} );

	$( document ).on( 'click', '.examhub-library__load-more', function ( e ) {

		e.preventDefault();

		var $btn = $( this );

		if ( $btn.prop( 'disabled' ) ) {
			return;
		}

		var $library = $btn.closest( '.examhub-library' );

		if ( 'less' === $btn.attr( 'data-state' ) ) {
			collapseToggleGrid( $btn, $library.find( '.examhub-library__grid' ).children(), parseInt( $library.data( 'per-page' ), 10 ) || 0 );
			$library.data( 'paged', 1 );
			return;
		}

		var nextPage = ( parseInt( $library.data( 'paged' ), 10 ) || 1 ) + 1;

		loadLibraryPage( $library, nextPage, true );
	} );

	/* ---------------------------------------------------------------------
	 * Featured Exams — tab switching (panels are pre-rendered, no AJAX)
	 * ------------------------------------------------------------------- */

	$( document ).on( 'click', '.examhub-featured__tab', function () {

		var $tab      = $( this );
		var $featured = $tab.closest( '.examhub-featured' );
		var key       = $tab.data( 'tab' );

		if ( $tab.hasClass( 'is-active' ) ) {
			return;
		}

		$featured.find( '.examhub-featured__tab' )
			.removeClass( 'is-active' )
			.attr( 'aria-selected', 'false' );

		$tab.addClass( 'is-active' ).attr( 'aria-selected', 'true' );

		$featured.find( '.examhub-featured__panel' ).each( function () {

			var $panel = $( this );

			if ( $panel.data( 'panel' ) === key ) {
				$panel.addClass( 'is-active' ).removeAttr( 'hidden' );
			} else {
				$panel.removeClass( 'is-active' ).attr( 'hidden', 'hidden' );
			}
		} );
	} );

	/* ---------------------------------------------------------------------
	 * Exam Section — heading panel + category tabs (AJAX-filtered card row)
	 * ------------------------------------------------------------------- */

	$( document ).on( 'click', '.examhub-section__tab', function () {

		var $tab     = $( this );
		var $section = $tab.closest( '.examhub-section' );

		if ( $tab.hasClass( 'is-active' ) ) {
			return;
		}

		$section.find( '.examhub-section__tab' )
			.removeClass( 'is-active' )
			.attr( 'aria-selected', 'false' );

		$tab.addClass( 'is-active' ).attr( 'aria-selected', 'true' );

		var $grid          = $section.find( '.examhub-section__grid' );
		var taxonomy       = $section.data( 'taxonomy' );
		var term           = $tab.data( 'term' ) || '';
		var parentTaxonomy = $section.data( 'parent-taxonomy' );
		var parentTerm     = $section.data( 'parent-term' );

		var args = {
			per_page:   $section.data( 'per-page' ),
			paged:      1,
			show_image: $section.data( 'show-image' ),
			show_stats: $section.data( 'show-stats' ),
			orderby:    $section.data( 'orderby' ) || 'latest'
		};

		if ( term && taxonomy ) {
			args[ filterKey( taxonomy ) ] = term;
		}

		// Keep whatever مقطع/پایه/رشته "محدود به والد" scope the admin set
		// on this widget in effect for every tab — including "همه" — so
		// clicking a tab can never widen the result set past that branch.
		if ( parentTerm && parentTaxonomy ) {
			args[ filterKey( parentTaxonomy ) ] = parentTerm;
		}

		$section.addClass( 'is-loading' );

		examhubRequest( 'examhub_query_exams', args )
			.done( function ( response ) {

				if ( response && response.success ) {
					$grid.html( response.data.html );
				}
			} )
			.always( function () {
				$section.removeClass( 'is-loading' );
			} );
	} );

	/* ---------------------------------------------------------------------
	 * Search & Filter — ExamFilterController
	 *
	 * Flat filter model: every facet (مقطع/پایه/رشته/درس/سال/نوبت/نوع) is
	 * independent — none of them disable, populate, or wait on any other,
	 * EXCEPT for the real structure chain مقطع ▸ پایه ▸ رشته ▸ درس, where
	 * each facet's <option> list is narrowed client-side to its parent's
	 * chosen children (see DEPENDENCY_CHAINS below). سال/نوبت/نوع
	 * are global facets (mirroring Examhub_Precomputed_Index::$global_filters
	 * on the PHP side) and must never be narrowed by, or wait on, anything.
	 *
	 * Now we have two independent chains: level→grade and field→subject.
	 * The single unified pipeline rule still holds: every control only ever
	 * mutates an in-memory FilterState; the actual exam-results query
	 * (examhub_query_exams) fires from exactly one place — applyFilters() —
	 * triggered by Apply or Enter in the search box.
	 * ------------------------------------------------------------------- */

	var FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), select:not([disabled]), input:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

	// ============================================================
	// ۱. تعریف زنجیره‌های وابستگی به صورت آرایه‌ای از آرایه‌ها
	// ============================================================
	var DEPENDENCY_CHAINS = [
		[ 'level', 'grade' ],   // مقطع → پایه
		[ 'field', 'subject' ]  // رشته → درس
	];

	/**
	 * Per-widget controller. One instance is created the first time any of
	 * its events fire (lazily, via getController()) and cached on the
	 * widget's jQuery data, so a page with several Search & Filter
	 * instances never cross-contaminates state.
	 *
	 * @param {jQuery} $widget The widget root element.
	 */
	function ExamFilterController( $widget ) {

		this.$widget         = $widget;
		this.state           = { paged: 1 };
		this.appliedState    = {}; // Track filters that have been applied
		this.perPage         = parseInt( $widget.data( 'per-page' ), 10 ) || 12;
		this.matchType       = $widget.data( 'match-type' ) || 'AND';
		this.requestInFlight = false;
		this.autoApplyTimer  = null;
		this.needsApply      = false;

		this.seedFromDefaults();
	}

	/**
	 * Read whatever value(s) each <select> already carries on first render —
	 * either nothing, or an editor-configured "مقدار پیش‌فرض" default — into
	 * FilterState. Without this, an Apply tap with no prior `change` event
	 * would silently ignore Elementor's per-facet default, even though the
	 * dropdown visibly shows it selected.
	 */
	ExamFilterController.prototype.seedFromDefaults = function () {

		var controller = this;

		// First, populate state from all selects (including non-structure ones)
		controller.$widget.find( '.examhub-search-filter__select' ).each( function () {

			var $select = $( this );
			var value   = readSelectValue( $select );
			var isEmpty = ! value || ( $.isArray( value ) && ! value.length );

			if ( ! isEmpty ) {
				controller.state[ $select.data( 'filter' ) ] = value;
				$select.addClass( 'has-value' );
			}

			updateFieldBadge( $select );
		} );

		// Apply dependencies for each chain from top to bottom
		$.each( DEPENDENCY_CHAINS, function( i, chain ) {
			for ( var j = 0; j < chain.length - 1; j++ ) {
				applyDependentOptionsFilter( controller, chain[ j ], chain );
			}
		} );

		// Initialize appliedState to match initial defaults, so load-more works
		// correctly without triggering a re-apply on first click
		controller.appliedState = $.extend( {}, controller.state );
		delete controller.appliedState.paged;

		renderActiveFilterChips( controller );
	};

	// ============================================================
	// ۲. تابع جدید برای اعمال وابستگی‌ها روی همه‌ی زنجیره‌ها
	// ============================================================
	function applyAllDependencies( controller, changedKey ) {
		// اگر کلید تغییر کرده در هیچ زنجیره‌ای نباشد، کاری نمی‌کنیم
		var found = false;
		$.each( DEPENDENCY_CHAINS, function( i, chain ) {
			if ( chain.indexOf( changedKey ) !== -1 ) {
				found = true;
				// برای هر زنجیره‌ای که شامل این کلید است، از آن کلید به پایین آبشار را اجرا کن
				applyDependentOptionsFilter( controller, changedKey, chain );
			}
		} );
		// اگر کلید در هیچ زنجیره‌ای نبود، کاری نکن (سال، نوبت، نوع، جستجو)
	}

	// ============================================================
	// ۳. تابع اصلی فیلتر کردن گزینه‌های فرزند برای یک زنجیره مشخص
	// ============================================================
	function applyDependentOptionsFilter( controller, parentKey, chain ) {
		var parentIndex = chain.indexOf( parentKey );
		if ( parentIndex === -1 || parentIndex === chain.length - 1 ) {
			return; // کلید والد در این زنجیره وجود ندارد یا آخرین عنصر است (فرزندی ندارد)
		}

		var childKey = chain[ parentIndex + 1 ];
		var $child = controller.$widget.find( '.examhub-search-filter__select[data-filter="' + childKey + '"]' );
		if ( ! $child.length ) {
			return;
		}

		var parentValue = controller.state[ parentKey ];
		var parentValues = parentValue ? ( $.isArray( parentValue ) ? parentValue : [ parentValue ] ) : [];
		var selected = readSelectValue( $child );
		var stillValid = $.isArray( selected ) ? [] : selected;
		var anyRemoved = false;

		$child.find( 'option[value!=""]' ).each( function() {
			var $option = $( this );
			var parents = ( $option.attr( 'data-parent' ) || '' ).split( ',' );
			var isVisible = ! parentValues.length || $.grep( parents, function( id ) {
				return -1 !== $.inArray( id, parentValues );
			} ).length > 0;

			$option.prop( 'hidden', ! isVisible ).prop( 'disabled', ! isVisible );

			if ( ! isVisible && $.isArray( selected ) && -1 !== $.inArray( $option.attr( 'value' ), selected ) ) {
				anyRemoved = true;
			} else if ( isVisible && $.isArray( selected ) && -1 !== $.inArray( $option.attr( 'value' ), selected ) ) {
				stillValid.push( $option.attr( 'value' ) );
			} else if ( ! $.isArray( selected ) && selected === $option.attr( 'value' ) && ! isVisible ) {
				anyRemoved = true;
				stillValid = '';
			}
		} );

		if ( anyRemoved ) {
			$child.val( $.isArray( selected ) ? stillValid : '' );
			$child.toggleClass( 'has-value', readSelectValue( $child ).length > 0 );
			updateFieldBadge( $child );
			controller.setField( childKey, readSelectValue( $child ) );
		}

		// به‌روزرسانی آبشاری برای فرزند بعدی در همان زنجیره
		applyDependentOptionsFilter( controller, childKey, chain );
	}

	/**
	 * Find or lazily create the controller instance for a given widget root.
	 *
	 * @param {jQuery} $widget
	 * @return {ExamFilterController}
	 */
	function getController( $widget ) {

		var controller = $widget.data( 'examhub-filter-controller' );

		if ( ! controller ) {
			controller = new ExamFilterController( $widget );
			$widget.data( 'examhub-filter-controller', controller );
		}

		return controller;
	}

	/**
	 * Mutate one FilterState field, without making any request. This is the
	 * ONLY way UI controls are allowed to touch state — selects (single or
	 * multi-value), the search box, and the quick-major pills all funnel
	 * through here.
	 *
	 * @param {string}              key   FilterState key (level/grade/field/subject/year/term/exam_type/search).
	 * @param {string|Array|null}   value New value (array for a multi-select facet), or empty/null to clear.
	 */
	ExamFilterController.prototype.setField = function ( key, value ) {

		var isEmpty = ! value || ( $.isArray( value ) && ! value.length );

		if ( isEmpty ) {
			delete this.state[ key ];
		} else {
			this.state[ key ] = value;
		}

		renderActiveFilterChips( this );
	};

	/**
	 * Read a select's current value(s) the way the FilterState expects:
	 * a plain string for single-select, an array for multi-select.
	 *
	 * @param {jQuery} $select
	 * @return {string|Array}
	 */
	function readSelectValue( $select ) {

		return $select.is( '[data-multiselect="1"]' ) ? ( $select.val() || [] ) : ( $select.val() || '' );
	}

	/**
	 * Show/hide the "+2"-style count badge next to a multi-select field's
	 * accordion header, so the selection is still visible once the section
	 * is collapsed and the <select> itself is hidden.
	 *
	 * @param {jQuery} $select
	 */
	function updateFieldBadge( $select ) {

		if ( ! $select.is( '[data-multiselect="1"]' ) ) {
			return;
		}

		var $badge = $select.closest( '.examhub-search-filter__field' ).find( '.examhub-search-filter__field-badge' );

		if ( ! $badge.length ) {
			return;
		}

		var count = ( $select.val() || [] ).length;

		if ( count > 0 ) {
			$badge.text( count ).prop( 'hidden', false );
		} else {
			$badge.prop( 'hidden', true );
		}
	}

	$( document ).on( 'change', '.examhub-search-filter__select', function () {

		var $select    = $( this );
		var value      = readSelectValue( $select );
		var controller = getController( $select.closest( '.examhub-search-filter' ) );
		var key        = $select.data( 'filter' );

		controller.setField( key, value );
		$select.toggleClass( 'has-value', $.isArray( value ) ? value.length > 0 : '' !== value );
		updateFieldBadge( $select );

		// اعمال وابستگی‌ها با استفاده از زنجیره‌های تعریف‌شده
		applyAllDependencies( controller, key );
	} );

	/**
	 * Independent accordion sections (Task 3): each field header toggles only
	 * its own body — facets stay independent in the data model, so there's
	 * no reason to force a single-open accordion here.
	 */
	$( document ).on( 'click', '.examhub-search-filter__field-header', function () {

		var $header = $( this );
		var $body   = $header.next( '.examhub-search-filter__field-body' );
		var isOpen  = 'true' === $header.attr( 'aria-expanded' );

		$header.attr( 'aria-expanded', isOpen ? 'false' : 'true' );
		$body.prop( 'hidden', isOpen );
	} );

	$( document ).on( 'input', '.examhub-search-filter__search', function () {

		var $search = $( this );
		getController( $search.closest( '.examhub-search-filter' ) ).setField( 'search', $search.val() );
	} );

	/**
	 * Display label for one FilterState key, used only to word its chip.
	 * Mirrors Examhub_Widget_Search_Filter::get_field_label() on the PHP side.
	 */
	var FILTER_LABELS = {
		level: 'مقطع',
		grade: 'پایه',
		field: 'رشته',
		subject: 'درس',
		year: 'سال',
		term: 'نوبت',
		exam_type: 'نوع آزمون',
		search: 'جستجو'
	};

	/**
	 * Re-render the "[ مقطع: دوازدهم ✕ ]" active-filter chip row from the
	 * current FilterState (Task 4). Purely a read of state — never makes a
	 * request. Each chip's × clears just that one field (and, for a
	 * multi-select facet, just that one value) and re-renders, same as
	 * touching the control directly; the user still has to tap Apply.
	 *
	 * @param {ExamFilterController} controller
	 */
	function renderActiveFilterChips( controller ) {

		var $chips = controller.$widget.find( '.examhub-search-filter__chips' );

		if ( ! $chips.length ) {
			return;
		}

		$chips.empty();

		$.each( controller.state, function ( key ) {

			if ( 'paged' === key ) {
				return;
			}

			var value = controller.state[ key ];
			var label = FILTER_LABELS[ key ] || key;

			if ( $.isArray( value ) ) {

				$.each( value, function ( i, singleValue ) {
					appendFilterChip( $chips, controller, key, label, singleValue );
				} );
			} else {
				appendFilterChip( $chips, controller, key, label, value );
			}
		} );
	}

	/**
	 * @param {jQuery}               $chips
	 * @param {ExamFilterController} controller
	 * @param {string}               key
	 * @param {string}               label
	 * @param {string}               value
	 */
	function appendFilterChip( $chips, controller, key, label, value ) {

		var $select  = controller.$widget.find( '.examhub-search-filter__select[data-filter="' + key + '"]' );
		var text     = 'search' === key ? value : ( $select.find( 'option[value="' + value + '"]' ).text() || value );

		var $chip = $( '<span class="examhub-search-filter__chip"></span>' )
			.text( label + ': ' + text )
			.data( 'filter', key )
			.data( 'value', value );

		$( '<button type="button" class="examhub-search-filter__chip-remove" aria-label="حذف"></button>' )
			.text( '✕' )
			.appendTo( $chip );

		$chips.append( $chip );
	}

	$( document ).on( 'click', '.examhub-search-filter__chip-remove', function () {

		var $chip      = $( this ).closest( '.examhub-search-filter__chip' );
		var $widget    = $chip.closest( '.examhub-search-filter' );
		var controller = getController( $widget );
		var key        = $chip.data( 'filter' );
		var value      = $chip.data( 'value' );
		var $select    = $widget.find( '.examhub-search-filter__select[data-filter="' + key + '"]' );

		if ( 'search' === key ) {

			$widget.find( '.examhub-search-filter__search' ).val( '' );
			controller.setField( 'search', '' );
			scheduleApplyFilters( controller );
			return;
		}

		if ( $.isArray( controller.state[ key ] ) ) {

			var remaining = $.grep( controller.state[ key ], function ( v ) {
				return String( v ) !== String( value );
			} );

			$select.val( remaining );
			controller.setField( key, remaining );
		} else {

			$select.val( '' );
			controller.setField( key, '' );
		}

		$select.toggleClass( 'has-value', readSelectValue( $select ).length > 0 );
		updateFieldBadge( $select );
		scheduleApplyFilters( controller );

	} );

	/**
	 * Build the query args object for the current FilterState — the only
	 * place that reads state to talk to the server.
	 *
	 * @param {ExamFilterController} controller
	 * @param {number}               paged
	 * @return {Object}
	 */
	function buildQueryArgs( controller, paged ) {

		var $widget = controller.$widget;

		return $.extend(
			{
				per_page:   controller.perPage,
				paged:      paged,
				match_type: controller.matchType,
				show_image: $widget.data( 'show-image' ),
				show_stats: $widget.data( 'show-stats' ),
				orderby:    'latest'
			},
			controller.state,
			{ paged: paged }
		);
	}

	/**
	 * Lock every interactive control in the widget while a request is in
	 * flight, and show a loading state on the Apply button specifically.
	 *
	 * @param {ExamFilterController} controller
	 */
	function lockFilterUI( controller ) {

		controller.requestInFlight = true;
		controller.$widget.addClass( 'is-loading' );
		controller.$widget.find( '.examhub-search-filter__apply' ).prop( 'disabled', true ).addClass( 'is-applying' );
		controller.$widget.find( '.examhub-search-filter__reset' ).prop( 'disabled', true );
		controller.$widget.find( '.examhub-search-filter__select, .examhub-search-filter__search' ).prop( 'disabled', true );
		controller.$widget.find( '.examhub-search-filter__featured-major' ).prop( 'disabled', true );
	}

	/**
	 * Reverse lockFilterUI().
	 *
	 * @param {ExamFilterController} controller
	 */
	function unlockFilterUI( controller ) {

		controller.requestInFlight = false;
		controller.$widget.removeClass( 'is-loading' );
		controller.$widget.find( '.examhub-search-filter__apply' ).prop( 'disabled', false ).removeClass( 'is-applying' );
		controller.$widget.find( '.examhub-search-filter__reset' ).prop( 'disabled', false );
		controller.$widget.find( '.examhub-search-filter__select, .examhub-search-filter__search' ).prop( 'disabled', false );
		controller.$widget.find( '.examhub-search-filter__featured-major' ).prop( 'disabled', false );

		if ( controller.needsApply ) {
			controller.needsApply = false;
			scheduleApplyFilters( controller, 0 );
		}
	}

	/**
	 * Reflect the current FilterState into the page URL (history.replaceState,
	 * no navigation/reload) so the result set is bookmarkable/shareable and
	 * survives a refresh. Best-effort: silently no-ops if the History API
	 * is unavailable.
	 *
	 * @param {ExamFilterController} controller
	 */
	function syncFilterStateToUrl( controller ) {

		if ( ! window.history || ! window.history.replaceState ) {
			return;
		}

		var url = new URL( window.location.href );

		$.each( [ 'level', 'grade', 'field', 'subject', 'year', 'term', 'exam_type', 'search' ], function ( i, key ) {

			if ( controller.state[ key ] ) {
				url.searchParams.set( key, controller.state[ key ] );
			} else {
				url.searchParams.delete( key );
			}
		} );

		window.history.replaceState( null, '', url.toString() );
	}

	/**
	 * The single AJAX pipeline (Task 6): build args from state, request,
	 * replace ONLY the results container, reset pagination to page 1.
	 *
	 * @param {ExamFilterController} controller
	 * @param {boolean}              append True for "نمایش بیشتر" (append instead of replace).
	 */
	function fetchExams( controller, append ) {

		if ( controller.requestInFlight ) {
			return;
		}

		var $widget      = controller.$widget;
		var $results     = $widget.find( '.examhub-search-filter__results' );
		var $loadMore    = $widget.find( '.examhub-search-filter__load-more' );
		var $resultCount = $widget.find( '.examhub-search-filter__result-count' );
		var paged        = controller.state.paged || 1;

		lockFilterUI( controller );
		$loadMore.prop( 'disabled', true );

		examhubRequest( 'examhub_query_exams', buildQueryArgs( controller, paged ) )
			.done( function ( response ) {

				if ( ! response || ! response.success ) {
					return;
				}

				if ( append ) {
					var $newItems = $( response.data.html ).children();
					$results.find( '.examhub-grid' ).append( $newItems );

					if ( $newItems.length ) {
						$newItems.first().get( 0 ).scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
					}
				} else {
					$results.html( response.data.html );
				}

				controller.state.paged = response.data.paged;
				$widget.data( 'max-pages', response.data.max_pages );

				if ( $resultCount.length && undefined !== response.data.found_posts ) {
					$resultCount.text( response.data.found_posts + ' آزمون یافت شد' );
				}

				updateToggleButtonState( $loadMore, response.data.paged, response.data.max_pages );
				syncFilterStateToUrl( controller );
			} )
			.always( function () {
				unlockFilterUI( controller );
				$loadMore.prop( 'disabled', false );
			} );
	}

	/**
	 * Check if current filter state differs from the last applied state.
	 * Ignores 'paged' since that's managed separately.
	 *
	 * @param {ExamFilterController} controller
	 * @return {boolean}
	 */
	function hasFilterChanges( controller ) {

		var currentFilters = $.extend( {}, controller.state );
		delete currentFilters.paged;

		var appliedFilters = $.extend( {}, controller.appliedState );

		// Convert to JSON strings for deep comparison
		return JSON.stringify( currentFilters ) !== JSON.stringify( appliedFilters );
	}

	/**
	 * Task 1's single entry point: reset pagination to page 1 and run the
	 * query. Called by Apply, the search box's Enter key, quick-major picks
	 * once committed, Reset, and load-more when filters have changed.
	 * Never called by a bare select change.
	 *
	 * @param {ExamFilterController} controller
	 */
	function applyFilters( controller ) {

		if ( controller.autoApplyTimer ) {
			window.clearTimeout( controller.autoApplyTimer );
			controller.autoApplyTimer = null;
		}

		if ( controller.requestInFlight ) {
			controller.needsApply = true;
			return;
		}

		controller.state.paged = 1;

		// Save current filters as applied
		controller.appliedState = $.extend( {}, controller.state );
		delete controller.appliedState.paged;

		fetchExams( controller, false );
	}

	function scheduleApplyFilters( controller, delay ) {

		if ( controller.autoApplyTimer ) {
			window.clearTimeout( controller.autoApplyTimer );
		}

		controller.autoApplyTimer = window.setTimeout( function () {
			controller.autoApplyTimer = null;
			applyFilters( controller );
		}, undefined === delay ? 250 : delay );
	}

	$( document ).on( 'click', '.examhub-search-filter__apply', function ( e ) {

		e.preventDefault();

		var $widget    = $( this ).closest( '.examhub-search-filter' );
		var controller = getController( $widget );

		applyFilters( controller );
		closeSearchFilterPopup( $widget );
	} );

	$( document ).on( 'keydown', '.examhub-search-filter__search', function ( e ) {

		if ( 'Enter' !== e.key ) {
			return;
		}

		e.preventDefault();

		var $widget = $( this ).closest( '.examhub-search-filter' );

		applyFilters( getController( $widget ) );
		closeSearchFilterPopup( $widget );
	} );

	$( document ).on( 'click', '.examhub-search-filter__reset', function ( e ) {

		e.preventDefault();

		var $widget    = $( this ).closest( '.examhub-search-filter' );
		var controller = getController( $widget );

		controller.state = { paged: 1 };
		renderActiveFilterChips( controller );

		$widget.find( '.examhub-search-filter__search' ).val( '' );
		$widget.find( '.examhub-search-filter__featured-major' ).removeClass( 'is-active' );
		$widget.find( '.examhub-search-filter__select' ).removeClass( 'has-value' ).val( '' );
		$widget.find( '.examhub-search-filter__field-badge' ).prop( 'hidden', true );

		// پس از ریست، وابستگی‌ها را دوباره اعمال کن تا همه‌ی گزینه‌ها قابل‌مشاهده شوند
		$.each( DEPENDENCY_CHAINS, function( i, chain ) {
			for ( var j = 0; j < chain.length - 1; j++ ) {
				applyDependentOptionsFilter( controller, chain[ j ], chain );
			}
		} );

		applyFilters( controller );
	} );

	/**
	 * Quick Major pills (Task 4 — fixed at 4, Elementor-configured): unlike
	 * every other control, this one is a direct shortcut, not a staged field
	 * — picking one sets the "field" (رشته) select's value AND immediately
	 * re-queries, with no separate "اعمال فیلتر" tap required. It lives
	 * outside the popup specifically so it can behave like a one-tap filter
	 * (Popup Isolation, see render()'s doc comment), so the interaction
	 * should match that promise instead of silently requiring Apply too.
	 */
	$( document ).on( 'click', '.examhub-search-filter__featured-major', function () {

		var $btn       = $( this );
		var $widget    = $btn.closest( '.examhub-search-filter' );
		var controller = getController( $widget );
		var $field     = $widget.find( '.examhub-search-filter__select[data-filter="field"]' );

		$widget.find( '.examhub-search-filter__featured-major' ).removeClass( 'is-active' );
		$btn.addClass( 'is-active' );

		if ( ! $field.length ) {
			return;
		}

		var termId = $btn.data( 'term-id' );

		$field.val( $field.is( '[data-multiselect="1"]' ) ? [ String( termId ) ] : String( termId ) );
		$field.trigger( 'change' ); // This will trigger cascade and update state.

		applyFilters( controller );
	} );

	/* ---------------------------------------------------------------------
	 * Mobile filter popup: opened by the "فیلترها" button, contains the same
	 * filter bar the desktop layout shows inline. Closes on the × button,
	 * an overlay click, or Esc; traps Tab focus while open; locks page
	 * scroll; restores focus to the toggle button on close.
	 * ------------------------------------------------------------------- */

	function openSearchFilterPopup( $widget ) {

		if ( 'undefined' === typeof window.matchMedia || ! window.matchMedia( '(max-width: 767px)' ).matches ) {
			return;
		}

		var $panel = $widget.find( '.examhub-search-filter__panel' );

		$widget.data( 'last-focus', $widget.find( '.examhub-search-filter__filters-toggle' ).get( 0 ) );
		$widget.addClass( 'is-popup-open' );
		$widget.find( '.examhub-search-filter__filters-toggle' ).attr( 'aria-expanded', 'true' );
		$( 'body' ).addClass( 'examhub-no-scroll' );

		var $firstFocusable = $panel.find( FOCUSABLE_SELECTOR ).filter( ':visible' ).first();

		if ( $firstFocusable.length ) {
			$firstFocusable.get( 0 ).focus();
		}
	}

	function closeSearchFilterPopup( $widget ) {

		if ( ! $widget.hasClass( 'is-popup-open' ) ) {
			return;
		}

		$widget.removeClass( 'is-popup-open' );
		$widget.find( '.examhub-search-filter__filters-toggle' ).attr( 'aria-expanded', 'false' );

		if ( ! $( '.examhub-search-filter.is-popup-open' ).length ) {
			$( 'body' ).removeClass( 'examhub-no-scroll' );
		}

		var lastFocus = $widget.data( 'last-focus' );

		if ( lastFocus ) {
			lastFocus.focus();
		}
	}

	$( document ).on( 'click', '.examhub-search-filter__filters-toggle', function () {

		openSearchFilterPopup( $( this ).closest( '.examhub-search-filter' ) );
	} );

	$( document ).on( 'click', '.examhub-search-filter__panel-close, .examhub-search-filter__overlay', function () {

		closeSearchFilterPopup( $( this ).closest( '.examhub-search-filter' ) );
	} );

	$( document ).on( 'keydown', function ( e ) {

		if ( 'Escape' !== e.key && 'Esc' !== e.key ) {
			return;
		}

		var $open = $( '.examhub-search-filter.is-popup-open' );

		if ( $open.length ) {
			closeSearchFilterPopup( $open );
		}
	} );

	// Focus trap: while the popup is open, Tab cycles only within the panel.
	$( document ).on( 'keydown', '.examhub-search-filter.is-popup-open .examhub-search-filter__panel', function ( e ) {

		if ( 'Tab' !== e.key ) {
			return;
		}

		var $focusable = $( this ).find( FOCUSABLE_SELECTOR ).filter( ':visible' );

		if ( ! $focusable.length ) {
			return;
		}

		var first = $focusable.first().get( 0 );
		var last  = $focusable.last().get( 0 );

		if ( e.shiftKey && document.activeElement === first ) {
			e.preventDefault();
			last.focus();
		} else if ( ! e.shiftKey && document.activeElement === last ) {
			e.preventDefault();
			first.focus();
		}
	} );

	$( document ).on( 'click', '.examhub-search-filter__load-more', function ( e ) {

		e.preventDefault();

		var $btn = $( this );

		if ( $btn.prop( 'disabled' ) ) {
			return;
		}

		var $widget    = $btn.closest( '.examhub-search-filter' );
		var controller = getController( $widget );

		if ( 'less' === $btn.attr( 'data-state' ) ) {
			collapseToggleGrid( $btn, $widget.find( '.examhub-search-filter__results .examhub-grid' ).children(), controller.perPage );
			controller.state.paged = 1;
			return;
		}

		// If filters have changed since last apply, apply them first (resets to page 1)
		if ( hasFilterChanges( controller ) ) {
			applyFilters( controller );
			return;
		}

		// Otherwise, load the next page
		controller.state.paged = ( parseInt( controller.state.paged, 10 ) || 1 ) + 1;

		fetchExams( controller, true );
	} );

	/* ---------------------------------------------------------------------
	 * Exam Mega Library — lazily-loaded accordion that walks the academic
	 * structure facets (مقطع ▸ پایه ▸ رشته ▸ درس → exam cards).
	 *
	 * Each node (branch or leaf button) carries a data-filters attribute: a
	 * JSON object accumulating every facet chosen on the path down to it,
	 * e.g. {"level":12} for a root branch, {"level":12,"grade":34} for one of
	 * its children. examhub_mega_branch (PHP) walks
	 * Examhub_Query::STRUCTURE_TAXONOMIES one facet at a time using that same
	 * accumulated path, so this stays correct regardless of how many facets
	 * deep the tree actually goes — only the first AJAX hop is ever shown
	 * before falling back to "show all matching exams" (Examhub_Ajax::mega_branch()
	 * returning no further dependent terms), the same two-hop UX as before.
	 * ------------------------------------------------------------------- */

	// Facet order Examhub_Query::STRUCTURE_TAXONOMIES walks, mirrored here so
	// the client can turn an accumulated filters object into the ordered
	// "path" array examhub_mega_branch expects.
	var STRUCTURE_FACET_ORDER = [ 'level', 'grade', 'field', 'subject' ];

	function filtersToPath( filters ) {

		var path = [];

		$.each( STRUCTURE_FACET_ORDER, function ( i, key ) {

			if ( ! filters.hasOwnProperty( key ) ) {
				return false;
			}
			path.push( filters[ key ] );
		} );

		return path;
	}

	function megaLoadExams( $mega, filters, $target ) {

		$target.html( '<p class="examhub-mega__loading">' + ( i18n.loading || '…' ) + '</p>' );

		examhubRequest( 'examhub_query_exams', $.extend( {
			per_page:   $mega.data( 'per-page' ),
			paged:      1,
			show_image: $mega.data( 'show-image' ),
			show_stats: $mega.data( 'show-stats' ),
			orderby:    'latest'
		}, filters ) )
			.done( function ( response ) {

				if ( response && response.success ) {
					$target.html( response.data.html );
				} else {
					$target.html( '<p class="examhub-mega__loading">' + ( i18n.error || 'خطایی رخ داد.' ) + '</p>' );
				}
			} )
			.fail( function () {
				$target.html( '<p class="examhub-mega__loading">' + ( i18n.error || 'خطایی رخ داد.' ) + '</p>' );
			} );
	}

	function buildLeafMarkup( branch, parentFilters ) {

		var filters = $.extend( {}, parentFilters );
		filters[ branch.taxonomy ] = branch.id;

		var $leaf = $( '<div class="examhub-mega__leaf" data-loaded="0"></div>' );

		var $toggle = $( '<button type="button" class="examhub-mega__leaf-toggle" aria-expanded="false"></button>' )
			.attr( 'data-filters', JSON.stringify( filters ) );

		$toggle.append( $( '<span class="examhub-mega__leaf-name"></span>' ).text( branch.name ) );
		$toggle.append(
			$( '<span class="examhub-mega__leaf-count"></span>' ).text(
				branch.count + ' ' + ( i18n.files_suffix || 'فایل' )
			)
		);

		var $exams = $( '<div class="examhub-mega__leaf-exams" hidden></div>' );

		$leaf.append( $toggle ).append( $exams );

		return $leaf;
	}

	$( document ).on( 'click', '.examhub-mega__branch-toggle', function () {

		var $toggle   = $( this );
		var $branch   = $toggle.closest( '.examhub-mega__branch' );
		var $mega     = $toggle.closest( '.examhub-mega' );
		var $children = $branch.find( '> .examhub-mega__children' );
		var expanded  = 'true' === $toggle.attr( 'aria-expanded' );
		var filters   = JSON.parse( $toggle.attr( 'data-filters' ) || '{}' );

		$toggle.attr( 'aria-expanded', expanded ? 'false' : 'true' );
		$children.attr( 'hidden', expanded ? 'hidden' : null );

		if ( expanded || '1' === $branch.data( 'loaded' ) ) {
			return;
		}

		$branch.data( 'loaded', '1' );

		$children.html( '<p class="examhub-mega__loading">' + ( i18n.loading || '…' ) + '</p>' );

		examhubRequest( 'examhub_mega_branch', {
			path: filtersToPath( filters )
		} )
			.done( function ( response ) {

				if ( ! response || ! response.success ) {
					$children.html( '<p class="examhub-mega__loading">' + ( i18n.error || 'خطایی رخ داد.' ) + '</p>' );
					return;
				}

				var branches = response.data.branches || [];

				// A branch with no deeper facet terms shows its own exams directly.
				if ( ! branches.length ) {
					megaLoadExams( $mega, filters, $children );
					return;
				}

				$children.empty();

				$.each( branches, function ( i, branch ) {
					$children.append( buildLeafMarkup( branch, filters ) );
				} );
			} )
			.fail( function () {

				$children.html( '<p class="examhub-mega__loading">' + ( i18n.error || 'خطایی رخ داد.' ) + '</p>' );
			} );
	} );

	$( document ).on( 'click', '.examhub-mega__leaf-toggle', function () {

		var $toggle  = $( this );
		var $leaf    = $toggle.closest( '.examhub-mega__leaf' );
		var $mega    = $toggle.closest( '.examhub-mega' );
		var $exams   = $leaf.find( '> .examhub-mega__leaf-exams' );
		var expanded = 'true' === $toggle.attr( 'aria-expanded' );
		var filters  = JSON.parse( $toggle.attr( 'data-filters' ) || '{}' );

		$toggle.attr( 'aria-expanded', expanded ? 'false' : 'true' );
		$exams.attr( 'hidden', expanded ? 'hidden' : null );

		if ( expanded || '1' === $leaf.data( 'loaded' ) ) {
			return;
		}

		$leaf.data( 'loaded', '1' );

		megaLoadExams( $mega, filters, $exams );
	} );

}( jQuery ) );