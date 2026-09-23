<?php

/**
 * Central query helper that every ExamHub widget/shortcode/AJAX endpoint
 * uses to fetch exams in one standardized shape.
 *
 * Keeping a single source of truth here means adding or tweaking a widget
 * never requires touching WP_Query plumbing again.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/includes
 */
class Examhub_Query {

	/**
	 * Maps the filter keys accepted by get_exams() to their taxonomy slugs.
	 *
	 * @since 1.0.0
	 * @var   array<string,string>
	 */
	const TAXONOMY_MAP = array(
		'level'     => 'examhub_level',
		'grade'     => 'examhub_grade',
		'field'     => 'examhub_field',
		'subject'   => 'examhub_subject',
		'year'      => 'examhub_year',
		'term'      => 'examhub_term',
		'exam_type' => 'examhub_exam_type',
	);

	/**
	 * The six academic-facet taxonomies, in drill-down order, mapped to their
	 * get_exams()/TAXONOMY_MAP filter key. Flat (independent) taxonomies — the
	 * "cascading" relationship between them is not native WP term hierarchy,
	 * it's tracked via STRUCTURE_PARENT_META (see get_dependent_terms()).
	 *
	 * @since 1.0.0
	 * @var   array<string,string>
	 */
	const STRUCTURE_TAXONOMIES = array(
		'examhub_level'   => 'level',
		'examhub_grade'   => 'grade',
		'examhub_field'   => 'field',
		'examhub_subject' => 'subject',
		'examhub_year'    => 'year',
		'examhub_term'    => 'term',
	);

	/**
	 * Maps each dependent structure taxonomy to the term-meta key (on its own
	 * terms) that stores the term_id of its valid parent in the taxonomy one
	 * step up. examhub_level has no entry — it's the root, always unfiltered.
	 *
	 * Meta is added non-unique (Examhub_Query::set_structure_parent()): a term
	 * can have more than one valid parent (e.g. a field shared by two grades),
	 * since flat taxonomies can't disambiguate same-named terms by parent the
	 * way the old hierarchical examhub_structure tree could.
	 *
	 * @since 1.0.0
	 * @var   array<string,string>
	 */
	const STRUCTURE_PARENT_META = array(
		'examhub_grade'   => 'examhub_parent_level',
		'examhub_field'   => 'examhub_parent_grade',
		'examhub_subject' => 'examhub_parent_field',
		'examhub_year'    => 'examhub_parent_subject',
		'examhub_term'    => 'examhub_parent_year',
	);

	/**
	 * Hard upper bound on how many exams a single query may return.
	 *
	 * Every widget control already caps its own count at or below this, so this
	 * only matters for the public AJAX endpoint, where it stops a hand-crafted
	 * request from asking for an unbounded result set.
	 *
	 * @since 1.0.0
	 * @var   int
	 */
	const MAX_POSTS_PER_PAGE = 48;

	/**
	 * Sequence of filter keys in the cascading order.
	 *
	 * @since 1.0.0
	 * @var   array<string>
	 */
	const FILTER_SEQUENCE = array( 'level', 'grade', 'field', 'subject', 'year', 'term', 'exam_type' );

	/**
	 * Initialize AJAX hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init_ajax() {
		add_filter( 'posts_clauses', array( __CLASS__, 'filter_year_orderby_clauses' ), 10, 2 );
		add_action( 'wp_ajax_examhub_get_filter_options', array( __CLASS__, 'ajax_get_filter_options' ) );
		add_action( 'wp_ajax_nopriv_examhub_get_filter_options', array( __CLASS__, 'ajax_get_filter_options' ) );
		add_action( 'wp_ajax_examhub_get_exam_results', array( __CLASS__, 'ajax_get_exam_results' ) );
		add_action( 'wp_ajax_nopriv_examhub_get_exam_results', array( __CLASS__, 'ajax_get_exam_results' ) );
	}

	/* =========================================================================
	   AJAX ENDPOINTS
	   ========================================================================= */

	/**
	 * AJAX endpoint: return options for a specific filter based on the current selections.
	 *
	 * Expects POST params:
	 *   - nonce        : security nonce
	 *   - filter       : string filter key (level, grade, ...)
	 *   - selections   : array of selected term IDs in the order of FILTER_SEQUENCE
	 *   - selected_value : (optional) the value chosen for this filter (if any)
	 *
	 * @since 1.0.0
	 * @return void (JSON response)
	 */
	public static function ajax_get_filter_options() {
		check_ajax_referer( 'examhub_filter_nonce', 'nonce' );

		$filter_key = isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : '';
		$selections = isset( $_POST['selections'] ) ? (array) $_POST['selections'] : array();
		$selections = array_map( 'intval', $selections );

		if ( ! $filter_key || ! in_array( $filter_key, self::FILTER_SEQUENCE, true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid filter key.' ) );
		}

		$taxonomy = self::get_taxonomy_from_filter_key( $filter_key );
		if ( ! $taxonomy ) {
			wp_send_json_error( array( 'message' => 'Invalid taxonomy for filter.' ) );
		}

		// If it's a structure taxonomy, use get_dependent_terms with the last selected parent.
		if ( array_key_exists( $taxonomy, self::STRUCTURE_TAXONOMIES ) ) {
			// Find the parent term: the last selected term that is an ancestor.
			$parent_term_id = 0;
			$structure_keys = array_keys( self::STRUCTURE_TAXONOMIES );
			$current_index  = array_search( $taxonomy, $structure_keys, true );

			if ( false !== $current_index && $current_index > 0 ) {
				// The parent taxonomy is the one before it in the sequence.
				$parent_taxonomy = $structure_keys[ $current_index - 1 ];
				$parent_key      = self::STRUCTURE_TAXONOMIES[ $parent_taxonomy ];
				// Find the selected ID for that parent key from the selections.
				$parent_index = array_search( $parent_key, self::FILTER_SEQUENCE, true );
				if ( false !== $parent_index && isset( $selections[ $parent_index ] ) ) {
					$parent_term_id = (int) $selections[ $parent_index ];
				}
			}

			$options = self::get_dependent_terms( $taxonomy, $parent_term_id );
		} else {
			// Non-structure taxonomy: return all terms (no dependency).
			$options = self::get_term_choices( $taxonomy );
		}

		// Count exams for the current path (including this filter's selected value if provided).
		$selected_value = isset( $_POST['selected_value'] ) ? intval( $_POST['selected_value'] ) : 0;
		$path_terms = array();

		// Build taxonomy => term_id map from selections.
		foreach ( self::FILTER_SEQUENCE as $index => $key ) {
			if ( ! isset( $selections[ $index ] ) || ! $selections[ $index ] ) {
				continue;
			}
			$tax = self::get_taxonomy_from_filter_key( $key );
			if ( $tax ) {
				$path_terms[ $tax ] = (int) $selections[ $index ];
			}
		}

		// If a value is selected for this filter, include it.
		if ( $selected_value && $taxonomy ) {
			$path_terms[ $taxonomy ] = $selected_value;
		}

		$result_count = 0;
		if ( ! empty( $path_terms ) ) {
			$result_count = self::count_exams_for_path( $path_terms );
		}

		wp_send_json_success(
			array(
				'options'      => $options,
				'has_results'  => $result_count > 0,
				'result_count' => $result_count,
			)
		);
	}

	/**
	 * AJAX endpoint: return the final exam results based on all selected filters.
	 *
	 * Expects POST params:
	 *   - nonce    : security nonce
	 *   - filters  : array of filter_key => term_id (all filters)
	 *   - paged    : int page number
	 *   - orderby  : string sort order
	 *   - per_page : int items per page
	 *
	 * @since 1.0.0
	 * @return void (JSON response)
	 */
	public static function ajax_get_exam_results() {
		check_ajax_referer( 'examhub_filter_nonce', 'nonce' );

		$filters = isset( $_POST['filters'] ) ? (array) $_POST['filters'] : array();
		$paged   = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;
		$orderby = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'latest';
		$per_page = isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 8;

		$args = array(
			'paged'          => max( 1, $paged ),
			'orderby'        => $orderby,
			'posts_per_page' => min( self::MAX_POSTS_PER_PAGE, max( 1, $per_page ) ),
		);

		// Map filter keys to their values.
		foreach ( self::FILTER_SEQUENCE as $key ) {
			if ( isset( $filters[ $key ] ) && $filters[ $key ] ) {
				$args[ $key ] = (int) $filters[ $key ];
			}
		}

		$results = self::get_exams( $args );

		// Format each exam card for front-end.
		$items = array();
		foreach ( $results['items'] as $item ) {
			$items[] = array(
				'id'              => $item['id'],
				'title'           => $item['title'],
				'thumbnail'       => $item['thumbnail'],
				'structure'       => $item['structure'],
				'meta_line'       => $item['meta_line'],
				'year'            => $item['year'],
				'term'            => $item['term'],
				'type'            => $item['type'],
				'field'           => $item['field'],
				'questions'       => $item['questions'],
				'answers'         => $item['answers'],
				'total_downloads' => $item['total_downloads'],
				'featured'        => $item['featured'],
				'permalink'       => get_permalink( $item['id'] ),
			);
		}

		wp_send_json_success(
			array(
				'items'         => $items,
				'found_posts'   => $results['found_posts'],
				'max_num_pages' => $results['max_num_pages'],
				'current_page'  => $paged,
				'total'         => $results['found_posts'],
			)
		);
	}

	/* =========================================================================
	   PUBLIC QUERY METHODS
	   ========================================================================= */

	/**
	 * Fetch exams matching the given filters and map them to the
	 * standardized card-data shape consumed by examhub_render_exam_card().
	 *
	 * Supported $args keys: level, grade, field, subject, year, term, exam_type
	 * (taxonomy term IDs or slugs), featured (bool), search (string), orderby
	 * (latest|popular|random|title), posts_per_page (int), paged (int).
	 *
	 * @since 1.0.0
	 * @param  array $args Filter/sort/pagination arguments.
	 * @return array{items: array<int,array>, found_posts: int, max_num_pages: int}
	 */
	public static function get_exams( array $args = array() ) {

		$defaults = array(
			'level'          => '',
			'grade'          => '',
			'field'          => '',
			'subject'        => '',
			'year'           => '',
			'term'           => '',
			'exam_type'      => '',
			'featured'       => null,
			'search'         => '',
			'orderby'        => 'latest',
			'posts_per_page' => 8,
			'paged'          => 1,
			'match_type'     => 'AND',
		);

		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			'post_type'      => 'examhub_exam',
			'post_status'    => 'publish',
			'posts_per_page' => min( self::MAX_POSTS_PER_PAGE, max( 1, (int) $args['posts_per_page'] ) ),
			'paged'          => max( 1, (int) $args['paged'] ),
			'ignore_sticky_posts' => true,
		);

		if ( '' !== trim( (string) $args['search'] ) ) {
			$query_args['s'] = sanitize_text_field( $args['search'] );
		}

		$tax_query = array();

		// Build the smart structure tax query (handles level/grade/field/subject/year/term cascading).
		$structure_tax_query = self::build_structure_tax_query( $args );
		if ( ! empty( $structure_tax_query ) ) {
			$tax_query[] = $structure_tax_query;
		}

		// Add independent (non-structure) taxonomies: exam_type (year and term are now part of structure).
		$structure_taxonomies = array_keys( self::STRUCTURE_TAXONOMIES );
		foreach ( self::TAXONOMY_MAP as $filter_key => $taxonomy ) {
			if ( in_array( $taxonomy, $structure_taxonomies, true ) ) {
				continue; // Already handled by the structure query.
			}
			$value = $args[ $filter_key ];
			if ( '' === $value || null === $value || 0 === $value ) {
				continue;
			}

			// Accept single value or array of values.
			$raw_values = is_array( $value ) ? $value : array( $value );
			$term_ids   = array();

			foreach ( $raw_values as $item ) {
				$item = trim( (string) $item );
				if ( '' === $item || '0' === $item ) {
					continue;
				}

				if ( is_numeric( $item ) ) {
					// Numeric value – treat as term_id.
					$term_id = absint( $item );
					if ( $term_id > 0 ) {
						$term_ids[] = $term_id;
					}
				} else {
					// Non-numeric – treat as slug and resolve to term_id.
					$term = get_term_by( 'slug', $item, $taxonomy );
					if ( $term && ! is_wp_error( $term ) ) {
						$term_ids[] = $term->term_id;
					}
				}
			}

			$term_ids = array_unique( $term_ids );
			if ( empty( $term_ids ) ) {
				continue;
			}

			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $term_ids,
			);
		}

		if ( ! empty( $tax_query ) ) {
			if ( count( $tax_query ) > 1 ) {
				// match_type applies to the combination of the structure group and independent filters.
				$match_type = 'OR' === strtoupper( (string) $args['match_type'] ) ? 'OR' : 'AND';
				$tax_query['relation'] = $match_type;
			}
			$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$meta_query = array();

		if ( true === $args['featured'] ) {
			$meta_query[] = array(
				'key'   => '_examhub_featured',
				'value' => '1',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		self::apply_orderby( $query_args, $args['orderby'] );

		$query = new WP_Query( $query_args );

		$items = array();

		foreach ( $query->posts as $post ) {
			$items[] = self::get_card_data( $post );
		}

		return array(
			'items'         => $items,
			'found_posts'   => (int) $query->found_posts,
			'max_num_pages' => (int) $query->max_num_pages,
		);
	}

	/**
	 * Build one resilient tax_query group for the academic structure facets.
	 *
	 * The structure facets are stored as separate flat taxonomies, but they
	 * represent one logical path: level -> grade -> field -> subject -> year ->
	 * term. This method now correctly includes ALL selected ancestor facets
	 * as AND conditions, while also allowing deeper (descendant) facets to be
	 * included via OR (so that selecting "year" also returns exams that have
	 * a "term" tagged).
	 *
	 * @since 1.0.0
	 * @param  array $args Parsed get_exams() arguments.
	 * @return array
	 */
	private static function build_structure_tax_query( array $args ) {

		$taxonomies  = array_keys( self::STRUCTURE_TAXONOMIES );
		$selected    = array();
		$deepest_idx = -1;

		// Find all selected structure taxonomies and the deepest one.
		foreach ( $taxonomies as $index => $taxonomy ) {
			$key = self::STRUCTURE_TAXONOMIES[ $taxonomy ];
			$ids = self::normalize_term_ids( $args[ $key ] );

			if ( empty( $ids ) ) {
				continue;
			}

			$selected[ $taxonomy ] = $ids;
			$deepest_idx           = $index;
		}

		if ( -1 === $deepest_idx ) {
			return array();
		}

		// 1. Build the AND clauses for all selected ancestors (including the deepest itself).
		$and_clauses = array();
		for ( $i = 0; $i <= $deepest_idx; $i++ ) {
			$taxonomy = $taxonomies[ $i ];
			if ( isset( $selected[ $taxonomy ] ) ) {
				$and_clauses[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $selected[ $taxonomy ],
				);
			}
		}

		// 2. Build the OR group for the deepest taxonomy and its descendants.
		$deepest_taxonomy = $taxonomies[ $deepest_idx ];
		$deepest_ids      = $selected[ $deepest_taxonomy ];
		$or_clauses       = array(
			array(
				'taxonomy' => $deepest_taxonomy,
				'field'    => 'term_id',
				'terms'    => $deepest_ids,
			),
		);

		$parent_ids = $deepest_ids;
		for ( $index = $deepest_idx + 1; $index < count( $taxonomies ); $index++ ) {
			$taxonomy  = $taxonomies[ $index ];
			$child_ids = self::get_child_term_ids( $taxonomy, $parent_ids );

			if ( empty( $child_ids ) ) {
				break;
			}

			$or_clauses[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $child_ids,
			);

			$parent_ids = $child_ids;
		}

		// 3. Combine: If there are AND clauses (ancestors) and the OR group,
		//    we create an AND relation with the OR group as the first element.
		if ( count( $and_clauses ) > 1 || ( count( $and_clauses ) === 1 && count( $or_clauses ) > 1 ) ) {
			// If we have multiple AND clauses, we wrap them in an AND relation.
			// But we also need to include the OR group as a sub-array.
			$tax_query = array(
				'relation' => 'AND',
			);

			// Add the OR group (with its own relation) if it has more than one clause.
			if ( count( $or_clauses ) > 1 ) {
				$or_group = array(
					'relation' => 'OR',
				);
				foreach ( $or_clauses as $clause ) {
					$or_group[] = $clause;
				}
				$tax_query[] = $or_group;
			} else {
				// If only one OR clause, add it directly.
				$tax_query[] = $or_clauses[0];
			}

			// Add the AND clauses (excluding the deepest, since it's already in OR group).
			// We need to avoid duplicating the deepest. So we only add ancestors before deepest.
			for ( $i = 0; $i < $deepest_idx; $i++ ) {
				$taxonomy = $taxonomies[ $i ];
				if ( isset( $selected[ $taxonomy ] ) ) {
					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $selected[ $taxonomy ],
					);
				}
			}

			// If there are no ancestor clauses, we just return the OR group (or single clause).
			if ( count( $tax_query ) === 1 ) {
				// Only the OR group exists, so return it directly.
				return $tax_query[0];
			}

			return $tax_query;
		}

		// If only one AND clause and one OR clause (or just one clause overall), return the clause directly.
		if ( count( $or_clauses ) === 1 && count( $and_clauses ) === 1 ) {
			return $or_clauses[0];
		}

		// Fallback: return the OR group as is.
		if ( count( $or_clauses ) > 1 ) {
			$or_group = array( 'relation' => 'OR' );
			foreach ( $or_clauses as $clause ) {
				$or_group[] = $clause;
			}
			return $or_group;
		}

		return $or_clauses[0];
	}

	/**
	 * Normalize a single term id or list of term ids.
	 *
	 * @since 1.0.0
	 * @param  mixed $value Term value(s).
	 * @return array<int,int>
	 */
	private static function normalize_term_ids( $value ) {

		$values = is_array( $value ) ? $value : array( $value );
		$ids    = array();

		foreach ( $values as $item ) {
			$item = is_scalar( $item ) ? $item : 0;
			$id   = absint( $item );

			if ( $id > 0 ) {
				$ids[ $id ] = $id;
			}
		}

		return array_values( $ids );
	}

	/**
	 * Determine whether a structure term rolls up to one of the selected
	 * ancestor terms.
	 *
	 * @since 1.0.0
	 * @param  int    $term_id           Current term id.
	 * @param  string $taxonomy          Current term taxonomy.
	 * @param  string $ancestor_taxonomy Target ancestor taxonomy.
	 * @param  array  $ancestor_ids      Allowed ancestor term ids.
	 * @return bool
	 */
	private static function term_has_ancestor( $term_id, $taxonomy, $ancestor_taxonomy, array $ancestor_ids ) {

		if ( $taxonomy === $ancestor_taxonomy ) {
			return in_array( (int) $term_id, array_map( 'intval', $ancestor_ids ), true );
		}

		$parent_taxonomy = self::get_parent_taxonomy( $taxonomy );

		if ( ! $parent_taxonomy || empty( self::STRUCTURE_PARENT_META[ $taxonomy ] ) ) {
			return false;
		}

		static $parent_cache = array();

		$parent_cache_key = $taxonomy . ':' . (int) $term_id;

		if ( isset( $parent_cache[ $parent_cache_key ] ) ) {
			$parent_ids = $parent_cache[ $parent_cache_key ];
		} else {
			$parent_ids = self::normalize_term_ids(
				get_term_meta( $term_id, self::STRUCTURE_PARENT_META[ $taxonomy ] )
			);
			$parent_cache[ $parent_cache_key ] = $parent_ids;
		}

		if ( empty( $parent_ids ) ) {
			return false;
		}

		if ( $parent_taxonomy === $ancestor_taxonomy ) {
			return (bool) array_intersect( $parent_ids, array_map( 'intval', $ancestor_ids ) );
		}

		foreach ( $parent_ids as $parent_id ) {
			if ( self::term_has_ancestor( $parent_id, $parent_taxonomy, $ancestor_taxonomy, $ancestor_ids ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Fetch child term ids for one structure taxonomy under any of the given
	 * parent term ids.
	 *
	 * @since 1.0.0
	 * @param  string $taxonomy   Child taxonomy.
	 * @param  array  $parent_ids Parent term ids.
	 * @return array<int,int>
	 */
	private static function get_child_term_ids( $taxonomy, array $parent_ids ) {

		if ( empty( self::STRUCTURE_PARENT_META[ $taxonomy ] ) ) {
			return array();
		}

		$parent_ids = self::normalize_term_ids( $parent_ids );

		if ( empty( $parent_ids ) ) {
			return array();
		}

		// Request-local cache prevents repeated child lookups while building
		// cascading structure queries for the same request.
		static $request_cache = array();

		$cache_key = $taxonomy . ':' . implode( ',', $parent_ids );

		if ( isset( $request_cache[ $cache_key ] ) ) {
			return $request_cache[ $cache_key ];
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => self::STRUCTURE_PARENT_META[ $taxonomy ],
						'value'   => $parent_ids,
						'compare' => 'IN',
					),
				),
			)
		);

		if ( ! is_array( $terms ) ) {
			$request_cache[ $cache_key ] = array();
			return array();
		}

		$request_cache[ $cache_key ] = self::normalize_term_ids( $terms );

		return $request_cache[ $cache_key ];
	}

	/**
	 * Apply the requested sort order to a set of WP_Query arguments.
	 *
	 * "popular" ranks by the questions-file download counter as a proxy for
	 * popularity (the plugin does not track page views separately); posts
	 * that have not been downloaded yet are still included via the
	 * EXISTS/NOT EXISTS meta_query trick.
	 *
	 * @since 1.0.0
	 * @param array  $query_args WP_Query arguments, modified by reference.
	 * @param string $orderby    One of latest|popular|random|title.
	 */
	private static function apply_orderby( array &$query_args, $orderby ) {

		$orderby = strtolower( trim( (string) $orderby ) );

		switch ( $orderby ) {

			case 'popular':
				$query_args['meta_key'] = '_examhub_questions_downloads'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']  = array( 'meta_value_num' => 'DESC', 'date' => 'DESC' );
				$query_args['meta_query'] = isset( $query_args['meta_query'] ) ? $query_args['meta_query'] : array();
				$query_args['meta_query'][] = array(
					'relation' => 'OR',
					array(
						'key'     => '_examhub_questions_downloads',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_examhub_questions_downloads',
						'compare' => 'NOT EXISTS',
					),
				);
				break;

			case 'random':
				$query_args['orderby'] = 'rand';
				break;

			case 'title':
				$query_args['orderby'] = 'title';
				$query_args['order']   = 'ASC';
				break;

			case 'latest':
			default:
				// "جدیدترین" now means newest exam year first (examhub_year term,
				// e.g. 1403 before 1402), falling back to post date for exams
				// without a year term and as a tiebreaker within the same year.
				// See filter_year_orderby_clauses() for the actual SQL join.
				$query_args['orderby']               = 'date';
				$query_args['order']                 = 'DESC';
				$query_args['examhub_orderby_year']  = true;
				break;
		}
	}

	/**
	 * Join examhub_year's term name into ORDER BY so "جدیدترین" sorts by the
	 * exam's assigned year rather than its publish date. Only runs for
	 * queries that opted in via the `examhub_orderby_year` query var
	 * (apply_orderby()) — every other WP_Query on the site is untouched.
	 *
	 * Years are stored as plain taxonomy term names (e.g. "1403"), so they're
	 * cast to an integer for a numeric sort instead of a lexicographic one.
	 *
	 * @since 1.0.0
	 * @param  array    $clauses SQL clause fragments, keyed by clause name.
	 * @param  WP_Query $query   The query being filtered.
	 * @return array
	 */
	public static function filter_year_orderby_clauses( $clauses, $query ) {

		if ( ! $query->get( 'examhub_orderby_year' ) ) {
			return $clauses;
		}

		global $wpdb;

		$clauses['join'] .= "
			LEFT JOIN {$wpdb->term_relationships} AS examhub_year_tr ON ( {$wpdb->posts}.ID = examhub_year_tr.object_id )
			LEFT JOIN {$wpdb->term_taxonomy} AS examhub_year_tt ON ( examhub_year_tr.term_taxonomy_id = examhub_year_tt.term_taxonomy_id AND examhub_year_tt.taxonomy = 'examhub_year' )
			LEFT JOIN {$wpdb->terms} AS examhub_year_t ON ( examhub_year_tt.term_id = examhub_year_t.term_id )
		";

		$clauses['orderby'] = 'CAST(examhub_year_t.name AS UNSIGNED) DESC, ' . $clauses['orderby'];

		// A post can carry more than one examhub_year term; the join would
		// otherwise duplicate its row once per term.
		$clauses['groupby'] = "{$wpdb->posts}.ID" . ( $clauses['groupby'] ? ', ' . $clauses['groupby'] : '' );

		return $clauses;
	}

	/**
	 * Count the published exams matching a set of structure-facet term IDs,
	 * AND-combined. Replaces the old single-taxonomy count_structure_exams(),
	 * which relied on native taxonomy descendant inclusion; the four facet
	 * taxonomies are flat now, so "exams under this branch" is simply an exact
	 * match on however many facets have been chosen so far.
	 *
	 * @since 1.0.0
	 * @param  array<string,int> $term_ids_by_taxonomy Taxonomy slug => term_id.
	 * @return int
	 */
	public static function count_exams_for_path( array $term_ids_by_taxonomy ) {

		if ( empty( $term_ids_by_taxonomy ) ) {
			return 0;
		}

		$tax_query = array();

		foreach ( $term_ids_by_taxonomy as $taxonomy => $term_id ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => (int) $term_id,
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'examhub_exam',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => false,
				'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Fetch the terms of a dependent structure taxonomy whose parent-link meta
	 * points at the given parent term, for cascading dropdowns / the Mega
	 * Library tree. examhub_level (the root facet) has no parent meta, so it
	 * always returns every term, unfiltered — same as a flat, independent
	 * taxonomy like examhub_term.
	 *
	 * Results are cached (object cache, keyed by a version bumped whenever any
	 * structure term is created/edited/deleted) since the same parent is
	 * looked up repeatedly as cascading selects/the Mega Library tree render.
	 *
	 * @since 1.0.0
	 * @param  string $taxonomy        One of the STRUCTURE_TAXONOMIES keys.
	 * @param  int    $parent_term_id  The chosen parent term ID (ignored for examhub_level).
	 * @return array<int,string> term_id => name
	 */
	public static function get_dependent_terms( $taxonomy, $parent_term_id ) {

		if ( ! array_key_exists( $taxonomy, self::STRUCTURE_TAXONOMIES ) ) {
			return array();
		}

		if ( 'examhub_level' === $taxonomy ) {
			return self::get_term_choices( $taxonomy );
		}

		if ( ! isset( self::STRUCTURE_PARENT_META[ $taxonomy ] ) || ! $parent_term_id ) {
			return array();
		}

		$cache_key   = 'examhub_dep_' . $taxonomy . '_' . (int) $parent_term_id . '_' . self::get_terms_cache_version();
		$cached      = wp_cache_get( $cache_key, 'examhub' );

		if ( false !== $cached ) {
			return $cached;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::STRUCTURE_PARENT_META[ $taxonomy ],
						'value' => (int) $parent_term_id,
					),
				),
			)
		);

		$options = array();

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ (int) $term->term_id ] = $term->name;
			}
		}

		wp_cache_set( $cache_key, $options, 'examhub', HOUR_IN_SECONDS );

		return $options;
	}

	/**
	 * Link a structure term to one of its valid parents, de-duplicated so
	 * repeated migration runs or admin actions never add the same link twice.
	 * A term may end up with more than one parent link (e.g. a field shared by
	 * two grades) since flat taxonomies can't disambiguate same-named terms by
	 * parent the way the old hierarchical tree could.
	 *
	 * @since 1.0.0
	 * @param  int    $term_id        The child term (in a STRUCTURE_PARENT_META taxonomy).
	 * @param  string $taxonomy       The child term's taxonomy.
	 * @param  int    $parent_term_id The parent term ID to link.
	 * @return void
	 */
	public static function set_structure_parent( $term_id, $taxonomy, $parent_term_id ) {

		if ( ! isset( self::STRUCTURE_PARENT_META[ $taxonomy ] ) || ! $parent_term_id ) {
			return;
		}

		$meta_key   = self::STRUCTURE_PARENT_META[ $taxonomy ];
		$existing   = get_term_meta( $term_id, $meta_key );
		$parent_str = (string) (int) $parent_term_id;

		if ( in_array( $parent_str, $existing, true ) ) {
			return;
		}

		add_term_meta( $term_id, $meta_key, (int) $parent_term_id, false );
		self::bump_terms_cache_version();
	}

	/**
	 * Replace a structure term's parent link(s) with a single one (or clear
	 * it), unlike set_structure_parent() which only ever adds. Used by the
	 * "Parent" field on the term add/edit screens (Examhub_Post_Types), where
	 * an admin manages one parent at a time through a plain single-select —
	 * the only place outside the one-time migration that can create these
	 * links, so a term added after migration is reachable from the cascading
	 * dropdowns / Mega Library tree at all.
	 *
	 * @since 1.0.0
	 * @param  int    $term_id        The child term (in a STRUCTURE_PARENT_META taxonomy).
	 * @param  string $taxonomy       The child term's taxonomy.
	 * @param  int    $parent_term_id The parent term ID to link, or 0 to clear.
	 * @return void
	 */
	public static function replace_structure_parent( $term_id, $taxonomy, $parent_term_id ) {

		if ( ! isset( self::STRUCTURE_PARENT_META[ $taxonomy ] ) ) {
			return;
		}

		$meta_key = self::STRUCTURE_PARENT_META[ $taxonomy ];

		delete_term_meta( $term_id, $meta_key );

		if ( $parent_term_id ) {
			add_term_meta( $term_id, $meta_key, (int) $parent_term_id, false );
		}

		self::bump_terms_cache_version();
	}

	/**
	 * Read a structure term's current (primary) parent term ID, for
	 * prefilling the "Parent" field on the term edit screen. A term may have
	 * more than one parent link from migration; this returns the first.
	 *
	 * @since 1.0.0
	 * @param  int    $term_id  The child term.
	 * @param  string $taxonomy The child term's taxonomy.
	 * @return int 0 if unlinked.
	 */
	public static function get_structure_parent( $term_id, $taxonomy ) {

		if ( ! isset( self::STRUCTURE_PARENT_META[ $taxonomy ] ) ) {
			return 0;
		}

		$values = get_term_meta( $term_id, self::STRUCTURE_PARENT_META[ $taxonomy ] );

		return ! empty( $values ) ? (int) $values[0] : 0;
	}

	/**
	 * Read every parent term id linked to a structure term (a term can have
	 * more than one valid parent, e.g. a field shared by two grades — see
	 * set_structure_parent()). Used to render each option's `data-parent`
	 * attribute so the front-end select can filter its child facet's option
	 * list client-side without an extra AJAX round-trip.
	 *
	 * @since 1.0.0
	 * @param  int    $term_id  The child term.
	 * @param  string $taxonomy The child term's taxonomy.
	 * @return array<int,int> Parent term ids, empty if unlinked.
	 */
	public static function get_structure_parents( $term_id, $taxonomy ) {

		if ( ! isset( self::STRUCTURE_PARENT_META[ $taxonomy ] ) ) {
			return array();
		}

		$values = get_term_meta( $term_id, self::STRUCTURE_PARENT_META[ $taxonomy ] );

		return array_values( array_unique( array_map( 'absint', (array) $values ) ) );
	}

	/**
	 * The taxonomy one step up from the given structure facet (e.g.
	 * examhub_grade's parent taxonomy is examhub_level), or '' for the root
	 * facet or a non-structure taxonomy.
	 *
	 * @since 1.0.0
	 * @param  string $taxonomy One of the STRUCTURE_TAXONOMIES keys.
	 * @return string
	 */
	public static function get_parent_taxonomy( $taxonomy ) {

		$keys  = array_keys( self::STRUCTURE_TAXONOMIES );
		$index = array_search( $taxonomy, $keys, true );

		if ( false === $index || 0 === $index ) {
			return '';
		}

		return $keys[ $index - 1 ];
	}

	/**
	 * Bump the cache-busting version used by get_dependent_terms()'s cache
	 * keys, invalidating every cached dependent-terms lookup at once without
	 * having to enumerate which parent/taxonomy combinations changed.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function bump_terms_cache_version() {

		update_option( 'examhub_terms_cache_version', (string) time() );
	}

	/**
	 * Read the current cache-busting version, defaulting to "0" the first
	 * time (before anything has ever bumped it).
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private static function get_terms_cache_version() {

		$version = get_option( 'examhub_terms_cache_version' );

		return $version ? $version : '0';
	}

	/**
	 * Build the compact inline "مقطع | پایه | رشته | درس | سال" structure label for
	 * an exam, skipping any facet the exam isn't tagged with.
	 *
	 * @since 1.0.0
	 * @param  int $post_id The exam post ID.
	 * @return string
	 */
	public static function get_inline_structure_label( $post_id ) {

		$names = array();

		foreach ( array_keys( self::STRUCTURE_TAXONOMIES ) as $taxonomy ) {
			$names[] = self::get_single_term_name( $post_id, $taxonomy );
		}

		return implode( ' | ', array_filter( $names ) );
	}

	/**
	 * Map a single exam post to the standardized card-data array.
	 *
	 * @since 1.0.0
	 * @param  WP_Post $post The exam post.
	 * @return array
	 */
	public static function get_card_data( $post ) {

		$post_id = $post->ID;

		static $meta_cache = array();

		if ( ! isset( $meta_cache[ $post_id ] ) ) {
			$meta_cache[ $post_id ] = array(
				'questions_id'        => (int) get_post_meta( $post_id, '_examhub_questions_file', true ),
				'answers_id'          => (int) get_post_meta( $post_id, '_examhub_answers_file', true ),
				'questions_url'       => (string) get_post_meta( $post_id, '_examhub_questions_url', true ),
				'answers_url'         => (string) get_post_meta( $post_id, '_examhub_answers_url', true ),
				'questions_downloads' => (int) get_post_meta( $post_id, '_examhub_questions_downloads', true ),
				'answers_downloads'   => (int) get_post_meta( $post_id, '_examhub_answers_downloads', true ),
				'featured'            => (bool) get_post_meta( $post_id, '_examhub_featured', true ),
			);
		}

		$meta = $meta_cache[ $post_id ];

		$questions_id        = $meta['questions_id'];
		$answers_id          = $meta['answers_id'];
		$questions_url       = $meta['questions_url'];
		$answers_url         = $meta['answers_url'];
		$questions_downloads = $meta['questions_downloads'];
		$answers_downloads   = $meta['answers_downloads'];

		$structure = self::get_inline_structure_label( $post_id );
		$year      = self::get_single_term_name( $post_id, 'examhub_year' );
		$term      = self::get_single_term_name( $post_id, 'examhub_term' );
		$type      = self::get_single_term_name( $post_id, 'examhub_exam_type' );
		$field     = self::get_single_term_name( $post_id, 'examhub_field' );

		$questions_has_file = $questions_id > 0;
		$questions_has_url  = ! empty( $questions_url );
		$answers_has_file   = $answers_id > 0;
		$answers_has_url    = ! empty( $answers_url );

		return array(
			'id'              => $post_id,
			'title'           => get_the_title( $post_id ),
			'thumbnail'       => get_the_post_thumbnail_url( $post_id, 'medium' ),
			'featured'        => $meta['featured'],
			'structure'       => $structure,
			'year'            => $year,
			'term'            => $term,
			'type'            => $type,
			'field'           => $field,
			'meta_line'       => self::build_meta_line( $structure, $year, $term ),
			'total_downloads' => $questions_downloads + $answers_downloads,
			'questions'       => array(
				'available'     => $questions_has_file || $questions_has_url,
				'count'         => $questions_downloads,
				'type'          => $questions_has_file ? 'file' : ( $questions_has_url ? 'url' : 'none' ),
				'url'           => ( $questions_has_file || $questions_has_url ) ? Examhub_Download_Handler::get_download_url( $post_id, 'questions' ) : '',
				'view_url'      => $questions_has_file ? wp_get_attachment_url( $questions_id ) : ( $questions_has_url ? $questions_url : '' ),
			),
			'answers'         => array(
				'available'     => $answers_has_file || $answers_has_url,
				'count'         => $answers_downloads,
				'type'          => $answers_has_file ? 'file' : ( $answers_has_url ? 'url' : 'none' ),
				'url'           => ( $answers_has_file || $answers_has_url ) ? Examhub_Download_Handler::get_download_url( $post_id, 'answers' ) : '',
				'view_url'      => $answers_has_file ? wp_get_attachment_url( $answers_id ) : ( $answers_has_url ? $answers_url : '' ),
			),
		);
	}

	/**
	 * Read the single term name assigned to the exam for a flat taxonomy.
	 *
	 * @since 1.0.0
	 * @param  int    $post_id  The exam post ID.
	 * @param  string $taxonomy Taxonomy slug.
	 * @return string
	 */
	private static function get_single_term_name( $post_id, $taxonomy ) {

		static $cache = array();

		$post_id  = (int) $post_id;
		$taxonomy = (string) $taxonomy;

		if ( isset( $cache[ $post_id ][ $taxonomy ] ) ) {
			return $cache[ $post_id ][ $taxonomy ];
		}

		$terms = get_the_terms( $post_id, $taxonomy );

		$name = ( is_array( $terms ) && ! empty( $terms ) )
			? (string) $terms[0]->name
			: '';

		if ( ! isset( $cache[ $post_id ] ) ) {
			$cache[ $post_id ] = array();
		}

		$cache[ $post_id ][ $taxonomy ] = $name;

		return $name;
	}

	/**
	 * Build the human-readable subtitle line shown on a card,
	 * e.g. "مقطع | پایه | رشته | درس | سال – خرداد ۱۴۰۴".
	 *
	 * @since 1.0.0
	 * @param  string $structure Compact inline structure label (see get_inline_structure_label()).
	 * @param  string $year      Year term name.
	 * @param  string $term      Term/session name.
	 * @return string
	 */
	private static function build_meta_line( $structure, $year, $term ) {

		$secondary = implode( ' ', array_filter( array( $term, $year ) ) );

		return implode( ' – ', array_filter( array( $structure, $secondary ) ) );
	}

	/**
	 * Fetch the terms of a taxonomy as a term_id => name map, used to populate
	 * Elementor "select" controls and filter dropdowns. Every ExamHub taxonomy
	 * is flat, so this is always a simple unordered list.
	 *
	 * Terms are ordered by slug (ascending) so that dropdown items follow the
	 * tag order rather than the default alphabetical name order.
	 *
	 * @since 1.0.0
	 * @param  string $taxonomy Taxonomy slug.
	 * @return array<int,string> term_id => name
	 */
	public static function get_term_options( $taxonomy ) {

		return self::get_cached_term_options( $taxonomy, 'options' );
	}


	/**
	 * Fetch the terms of a taxonomy as a term_id => name map. Used by admin
	 * <select> fields that save a term ID — front-end filters use the
	 * term_id-keyed get_term_options().
	 *
	 * Terms are ordered by slug (ascending) so that dropdown items follow the
	 * tag order.
	 *
	 * @since 1.0.0
	 * @param  string $taxonomy Taxonomy slug.
	 * @return array<int,string>
	 */
	public static function get_term_choices( $taxonomy ) {

		return self::get_cached_term_options( $taxonomy, 'choices' );
	}

	/**
	 * Fetch and cache a taxonomy's term_id => name map.
	 *
	 * The public methods get_term_options() and get_term_choices() intentionally
	 * retain separate cache namespaces for backward compatibility, while sharing
	 * the actual retrieval logic.
	 *
	 * @since 1.0.0
	 * @param string $taxonomy     Taxonomy slug.
	 * @param string $cache_prefix Cache namespace.
	 * @return array<int,string>
	 */
	private static function get_cached_term_options( $taxonomy, $cache_prefix ) {

		$taxonomy = (string) $taxonomy;

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$cache_key = 'examhub_' . $cache_prefix . '_' . $taxonomy . '_' . self::get_terms_cache_version();
		$cached    = wp_cache_get( $cache_key, 'examhub' );

		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
				'fields'     => 'all',
			)
		);

		$options = array();

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ (int) $term->term_id ] = (string) $term->name;
			}
		}

		wp_cache_set( $cache_key, $options, 'examhub', HOUR_IN_SECONDS );

		return $options;
	}


	/* =========================================================================
	   HELPER: MAP FILTER KEY TO TAXONOMY
	   ========================================================================= */

	/**
	 * Map a filter key to its corresponding taxonomy slug.
	 *
	 * @since 1.0.0
	 * @param  string $filter_key One of the keys in FILTER_SEQUENCE.
	 * @return string|false
	 */
	private static function get_taxonomy_from_filter_key( $filter_key ) {
		return isset( self::TAXONOMY_MAP[ $filter_key ] ) ? self::TAXONOMY_MAP[ $filter_key ] : false;
	}
}

// Hook AJAX handlers when the class is loaded.
Examhub_Query::init_ajax();