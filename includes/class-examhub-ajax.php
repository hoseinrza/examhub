<?php

/**
 * Shared AJAX endpoints used by the front-end widgets to filter, search,
 * and progressively load exams without a page reload.
 *
 * Every interactive widget (Download Library, Search & Filter, Exam Mega
 * Library) is built on top of these two actions so the query/render logic
 * stays in one place.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/includes
 */
class Examhub_Ajax {

	/**
	 * The nonce action shared by all front-end ExamHub AJAX requests.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	const NONCE_ACTION = 'examhub_frontend';

	/**
	 * AJAX callback: query exams by filters and return rendered card HTML
	 * plus pagination info as JSON.
	 *
	 * Registered for wp_ajax_examhub_query_exams and the _nopriv variant.
	 *
	 * @since 1.0.0
	 */
	public function query_exams() {

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$args = array(
			'search'         => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
			'orderby'        => isset( $_POST['orderby'] ) ? sanitize_key( wp_unslash( $_POST['orderby'] ) ) : 'latest',
			'posts_per_page' => isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 8,
			'paged'          => isset( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1,
			'match_type'     => isset( $_POST['match_type'] ) && 'OR' === strtoupper( sanitize_key( wp_unslash( $_POST['match_type'] ) ) ) ? 'OR' : 'AND',
		);

		// Read each taxonomy filter (level, grade, field, subject, year, term, exam_type) by its key.
		foreach ( array_keys( Examhub_Query::TAXONOMY_MAP ) as $filter_key ) {
			$args[ $filter_key ] = self::sanitize_term_param( $filter_key );
		}

		if ( isset( $_POST['featured'] ) && '1' === $_POST['featured'] ) {
			$args['featured'] = true;
		}

		$display_atts = array(
			'show_image' => ! isset( $_POST['show_image'] ) || '0' !== $_POST['show_image'],
			'show_stats' => ! isset( $_POST['show_stats'] ) || '0' !== $_POST['show_stats'],
		);

		$result = Examhub_Query::get_exams( $args );

		wp_send_json_success(
			array(
				'html'         => examhub_render_exam_grid( $result['items'], $display_atts ),
				'found_posts'  => $result['found_posts'],
				'max_pages'    => $result['max_num_pages'],
				'paged'        => $args['paged'],
				'_debug_tax'   => $result['_debug_tax'] ?? null,
				'_debug_sql'   => $result['_debug_sql'] ?? null,
				'_debug_args'  => $args,
			)
		);
	}

	/**
	 * AJAX callback: given the chain of structure term IDs chosen so far (one
	 * per taxonomy, in Examhub_Query::STRUCTURE_TAXONOMIES order), return the
	 * dependent terms of the next taxonomy in the chain — each with the exam
	 * count for the full accumulated path — for lazily expanding a branch of
	 * the Exam Mega Library tree on demand.
	 *
	 * Registered for wp_ajax_examhub_mega_branch and the _nopriv variant.
	 *
	 * @since 1.0.0
	 */
	public function mega_branch() {

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$path = isset( $_POST['path'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['path'] ) ) : array();
		$path = array_values( array_filter( $path ) );

		$taxonomies = array_keys( Examhub_Query::STRUCTURE_TAXONOMIES );
		$step       = count( $path );

		if ( $step < 1 || $step >= count( $taxonomies ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'examhub' ) ), 400 );
		}

		$next_taxonomy = $taxonomies[ $step ];
		$parent_id     = (int) end( $path );

		$candidates = Examhub_Query::get_dependent_terms( $next_taxonomy, $parent_id );

		$branches = array();

		foreach ( $candidates as $term_id => $name ) {

			$term = get_term( $term_id, $next_taxonomy );

			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}

			$term_path = array();
			foreach ( array_slice( $taxonomies, 0, $step ) as $index => $taxonomy ) {
				$term_path[ $taxonomy ] = $path[ $index ];
			}
			$term_path[ $next_taxonomy ] = (int) $term_id;

			$branches[] = array(
				'id'       => (int) $term_id,
				'slug'     => $term->slug,
				'name'     => $name,
				'taxonomy' => Examhub_Query::STRUCTURE_TAXONOMIES[ $next_taxonomy ],
				'count'    => Examhub_Query::count_exams_for_path( $term_path ),
			);
		}

		wp_send_json_success( array( 'branches' => $branches ) );
	}

	/**
	 * AJAX callback: return the structure terms in a dependent taxonomy
	 * (پایه/رشته/درس) whose parent-link meta matches the chosen parent term —
	 * for cascading selects in the post-edit metabox, the Elementor editor's
	 * Exam Showcase control, and the front-end Search & Filter widget.
	 *
	 * Registered for both wp_ajax_examhub_dependent_terms and the _nopriv
	 * variant: this only ever returns public taxonomy term names, the same
	 * data already exposed via every widget's filter <select> options, so
	 * there's no capability check — anonymous visitors need it too.
	 *
	 * @since 1.0.0
	 */
	public function dependent_terms() {

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : '';

		if ( ! array_key_exists( $taxonomy, Examhub_Query::STRUCTURE_PARENT_META ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid taxonomy.', 'examhub' ) ), 400 );
		}

		$parent_term_id = isset( $_POST['parent_term_id'] ) ? absint( $_POST['parent_term_id'] ) : 0;

		wp_send_json_success(
			array( 'options' => Examhub_Query::get_dependent_terms( $taxonomy, $parent_term_id ) )
		);
	}

	/**
	 * Read a taxonomy filter value from $_POST (term slug or numeric term ID,
	 * single value or — when the widget's "چندانتخابی" control is on for that
	 * facet — an array of either) safely.
	 *
	 * @since 1.0.0
	 * @param  string $key The $_POST key (matches Examhub_Query::TAXONOMY_MAP keys).
	 * @return string|array<int,string>
	 */
	private static function sanitize_term_param( $key ) {

		if ( empty( $_POST[ $key ] ) ) {
			return '';
		}

		$taxonomy     = isset( Examhub_Query::TAXONOMY_MAP[ $key ] ) ? Examhub_Query::TAXONOMY_MAP[ $key ] : '';
		$is_structure = $taxonomy && array_key_exists( $taxonomy, Examhub_Query::STRUCTURE_TAXONOMIES );
		$raw          = wp_unslash( $_POST[ $key ] );

		if ( is_array( $raw ) ) {

			$values = array();

			foreach ( $raw as $item ) {
				// برای ساختاری، عدد؛ برای غیرساختاری، اسلاگ (عنوان پاک‌شده)
				$values[] = $is_structure ? absint( $item ) : sanitize_title( (string) $item );
			}

			return array_values( array_filter( $values ) );
		}

		return $is_structure ? absint( $raw ) : sanitize_title( (string) $raw );
	}
}