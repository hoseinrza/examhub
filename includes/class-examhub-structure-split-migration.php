<?php

/**
 * One-time, non-destructive migration of the single hierarchical
 * examhub_structure taxonomy into the four independent flat taxonomies
 * (examhub_level › examhub_grade › examhub_field › examhub_subject).
 *
 * Runs after the legacy Examhub_Migration (which populates examhub_structure
 * in the first place) — see Examhub::define_migration_hooks(). Each exam's
 * single structure leaf term is resolved to its name path exactly like the
 * old Examhub_Query::get_structure_path() did, then anchored from the LEAF
 * end (درس is always the most specific, مقطع is the one most likely to be
 * missing in a shallower tree) onto the four new taxonomies. The old
 * examhub_structure assignment is left in place, so this is reversible and
 * safe to re-run (it skips exams that already have any of the four new terms).
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/includes
 */
class Examhub_Structure_Split_Migration {

	/**
	 * Option flag set once the migration has completed.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	const FLAG = 'examhub_structure_split_migrated';

	/**
	 * Transient holding the migrated-count for the one-time admin notice.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	const NOTICE = 'examhub_structure_split_migration_notice';

	/**
	 * The taxonomy being split, in root → leaf order (level › grade › field › subject).
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	const LEGACY_STRUCTURE_TAXONOMY = 'examhub_structure';

	/**
	 * Run the migration once, guarded by the completion flag.
	 *
	 * Hooked on admin_init; only an administrator triggers it so the work
	 * happens in a predictable context and the notice has somewhere to show.
	 *
	 * @since 1.0.0
	 */
	public function maybe_migrate() {

		if ( get_option( self::FLAG ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->register_legacy_structure_taxonomy();

		if ( ! taxonomy_exists( self::LEGACY_STRUCTURE_TAXONOMY ) ) {
			// Nothing to split from on a fresh install that never had the old
			// hierarchical taxonomy registered.
			update_option( self::FLAG, time() );
			return;
		}

		$exam_ids = get_posts(
			array(
				'post_type'        => 'examhub_exam',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);

		$migrated = 0;

		foreach ( $exam_ids as $exam_id ) {
			if ( $this->migrate_exam( (int) $exam_id ) ) {
				$migrated++;
			}
		}

		update_option( self::FLAG, time() );
		set_transient( self::NOTICE, $migrated, DAY_IN_SECONDS );
	}

	/**
	 * Split a single exam's legacy structure leaf into the four new taxonomies.
	 *
	 * @since 1.0.0
	 * @param  int $exam_id The exam post ID.
	 * @return bool Whether any new facet term was assigned.
	 */
	private function migrate_exam( $exam_id ) {

		foreach ( array_keys( Examhub_Query::STRUCTURE_TAXONOMIES ) as $taxonomy ) {
			$already = wp_get_object_terms( $exam_id, $taxonomy, array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $already ) && ! empty( $already ) ) {
				return false;
			}
		}

		$names = $this->get_legacy_structure_names( $exam_id );

		if ( empty( $names ) ) {
			return false;
		}

		// Anchor from the leaf end: درس is always present, مقطع is the first
		// one omitted when the legacy tree is shallower than 4 levels.
		$ordered_taxonomies = array_keys( Examhub_Query::STRUCTURE_TAXONOMIES );
		$values             = array();

		foreach ( array_reverse( $ordered_taxonomies ) as $taxonomy ) {
			if ( empty( $names ) ) {
				break;
			}
			$values[ $taxonomy ] = array_pop( $names );
		}

		$assigned = false;
		$prev_id  = 0;

		foreach ( $ordered_taxonomies as $taxonomy ) {

			if ( ! isset( $values[ $taxonomy ] ) ) {
				continue;
			}

			$term_id = $this->find_or_create_term( $values[ $taxonomy ], $taxonomy );

			if ( ! $term_id ) {
				continue;
			}

			wp_set_object_terms( $exam_id, array( $term_id ), $taxonomy, false );
			$assigned = true;

			if ( $prev_id ) {
				Examhub_Query::set_structure_parent( $term_id, $taxonomy, $prev_id );
			}

			$prev_id = $term_id;
		}

		return $assigned;
	}

	/**
	 * Resolve the exam's legacy examhub_structure leaf term to its full
	 * root → leaf path of names (same logic the old
	 * Examhub_Query::get_structure_path() used).
	 *
	 * @since 1.0.0
	 * @param  int $exam_id The exam post ID.
	 * @return string[] Names ordered root → leaf, or empty when untagged.
	 */
	private function get_legacy_structure_names( $exam_id ) {

		$terms = get_the_terms( $exam_id, self::LEGACY_STRUCTURE_TAXONOMY );

		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return array();
		}

		$leaf = $terms[0];
		$path = array();

		foreach ( array_reverse( get_ancestors( $leaf->term_id, self::LEGACY_STRUCTURE_TAXONOMY ) ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, self::LEGACY_STRUCTURE_TAXONOMY );
			if ( $ancestor && ! is_wp_error( $ancestor ) ) {
				$path[] = $ancestor->name;
			}
		}

		$path[] = $leaf->name;

		return $path;
	}

	/**
	 * Register the legacy examhub_structure taxonomy for this request, since
	 * it's no longer registered normally — independent of whether
	 * Examhub_Migration also happens to register it earlier in the same
	 * request, so this migration works correctly even on a request where
	 * Examhub_Migration's own flag is already set and it returns early
	 * without touching taxonomy registration at all.
	 *
	 * @since 1.0.0
	 */
	private function register_legacy_structure_taxonomy() {

		if ( taxonomy_exists( self::LEGACY_STRUCTURE_TAXONOMY ) ) {
			return;
		}

		register_taxonomy(
			self::LEGACY_STRUCTURE_TAXONOMY,
			'examhub_exam',
			array(
				'public'       => false,
				'show_ui'      => false,
				'rewrite'      => false,
				'query_var'    => false,
				'hierarchical' => true,
			)
		);
	}

	/**
	 * Find (or create) a term by name in one of the new flat taxonomies.
	 *
	 * Flat taxonomies can't disambiguate same-named terms by parent the way
	 * the old hierarchical tree could, so this matches by name alone and
	 * reuses whatever it finds — the parent-link meta (set by the caller)
	 * is what records this particular chain's relationship.
	 *
	 * @since 1.0.0
	 * @param  string $name     Term name.
	 * @param  string $taxonomy One of the four new structure taxonomies.
	 * @return int    The term ID, or 0 on failure.
	 */
	private function find_or_create_term( $name, $taxonomy ) {

		$existing = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'name'       => $name,
				'fields'     => 'ids',
			)
		);

		if ( ! is_wp_error( $existing ) && ! empty( $existing ) ) {
			return (int) $existing[0];
		}

		$result = wp_insert_term( $name, $taxonomy );

		if ( is_wp_error( $result ) ) {
			if ( isset( $result->error_data['term_exists'] ) ) {
				return (int) $result->error_data['term_exists'];
			}
			return 0;
		}

		return (int) $result['term_id'];
	}

	/**
	 * Show a one-time success notice after the migration runs.
	 *
	 * @since 1.0.0
	 */
	public function admin_notice() {

		$count = get_transient( self::NOTICE );

		if ( false === $count ) {
			return;
		}

		delete_transient( self::NOTICE );

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d: number of migrated exams. */
					__( 'ExamHub: %d آزمون به ۴ فیلد مستقل (مقطع/پایه/رشته/درس) منتقل شد. ساختار درختیِ قبلی حفظ شده‌است.', 'examhub' ),
					(int) $count
				)
			)
		);
	}

}
