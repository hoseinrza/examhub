<?php

/**
 * One-time, non-destructive migration of the legacy flat taxonomies
 * (examhub_grade › examhub_field › examhub_subject) into the single
 * hierarchical examhub_structure tree.
 *
 * Each exam carried at most one grade + one field + one subject, so its path is
 * unambiguous: we create/reuse the nested structure terms grade › field › subject
 * and tag the exam with the deepest one. The old term assignments are left in
 * place, so the migration is reversible and safe to re-run (it skips exams that
 * already have a structure term).
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/includes
 */
class Examhub_Migration {

	/**
	 * Option flag set once the migration has completed.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	const FLAG = 'examhub_structure_migrated';

	/**
	 * Transient holding the migrated-count for the one-time admin notice.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	const NOTICE = 'examhub_structure_migration_notice';

	/**
	 * The legacy flat taxonomies, in the order they nest into the tree.
	 *
	 * @since 1.0.0
	 * @var   string[]
	 */
	const LEGACY_TAXONOMIES = array( 'examhub_grade', 'examhub_field', 'examhub_subject' );

	/**
	 * The hierarchical taxonomy this migration feeds (root → leaf tree). No
	 * longer registered live by Examhub_Post_Types — it was split into four
	 * flat taxonomies by Examhub_Structure_Split_Migration, which reads this
	 * one's leaf assignments — so this migration registers it ad hoc itself,
	 * the same way it already does for LEGACY_TAXONOMIES.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	const STRUCTURE_TAXONOMY = 'examhub_structure';

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

		$this->register_legacy_taxonomies();

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
	 * Migrate a single exam's legacy terms into a structure leaf.
	 *
	 * @since 1.0.0
	 * @param  int $exam_id The exam post ID.
	 * @return bool Whether a structure term was assigned.
	 */
	private function migrate_exam( $exam_id ) {

		$already = wp_get_object_terms( $exam_id, self::STRUCTURE_TAXONOMY, array( 'fields' => 'ids' ) );

		if ( ! is_wp_error( $already ) && ! empty( $already ) ) {
			return false;
		}

		$path_names = array();

		foreach ( self::LEGACY_TAXONOMIES as $taxonomy ) {
			$terms = get_the_terms( $exam_id, $taxonomy );
			if ( is_array( $terms ) && ! empty( $terms ) ) {
				$path_names[] = $terms[0]->name;
			}
		}

		if ( empty( $path_names ) ) {
			return false;
		}

		$parent  = 0;
		$leaf_id = 0;

		foreach ( $path_names as $name ) {
			$leaf_id = $this->ensure_term( $name, $parent );
			if ( ! $leaf_id ) {
				return false;
			}
			$parent = $leaf_id;
		}

		wp_set_object_terms( $exam_id, array( $leaf_id ), self::STRUCTURE_TAXONOMY, false );

		return true;
	}

	/**
	 * Find (or create) a structure term with the given name under a parent.
	 *
	 * WordPress allows same-named terms under different parents in a
	 * hierarchical taxonomy, so the tree can legitimately hold e.g. "شیمی"
	 * under both "تجربی" and "ریاضی".
	 *
	 * @since 1.0.0
	 * @param  string $name   Term name.
	 * @param  int    $parent Parent term ID (0 for a root term).
	 * @return int    The term ID, or 0 on failure.
	 */
	private function ensure_term( $name, $parent ) {

		$existing = get_terms(
			array(
				'taxonomy'   => self::STRUCTURE_TAXONOMY,
				'hide_empty' => false,
				'name'       => $name,
				'parent'     => $parent,
				'fields'     => 'ids',
			)
		);

		if ( ! is_wp_error( $existing ) && ! empty( $existing ) ) {
			return (int) $existing[0];
		}

		$result = wp_insert_term( $name, self::STRUCTURE_TAXONOMY, array( 'parent' => $parent ) );

		if ( is_wp_error( $result ) ) {
			// If it raced to existence, recover the existing ID.
			if ( isset( $result->error_data['term_exists'] ) ) {
				return (int) $result->error_data['term_exists'];
			}
			return 0;
		}

		return (int) $result['term_id'];
	}

	/**
	 * Register the legacy taxonomies — and the (now also retired)
	 * examhub_structure tree this migration writes into — for this request
	 * so their term assignments can be read/written; none of them are
	 * registered normally anymore.
	 *
	 * @since 1.0.0
	 */
	private function register_legacy_taxonomies() {

		$taxonomies = array_merge( self::LEGACY_TAXONOMIES, array( self::STRUCTURE_TAXONOMY ) );

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				register_taxonomy(
					$taxonomy,
					'examhub_exam',
					array(
						'public'       => false,
						'show_ui'      => false,
						'rewrite'      => false,
						'query_var'    => false,
						'hierarchical' => self::STRUCTURE_TAXONOMY === $taxonomy,
					)
				);
			}
		}
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
					__( 'ExamHub: %d آزمون به ساختار تحصیلیِ درختیِ جدید منتقل شد. ترم‌های قدیمی حفظ شده‌اند.', 'examhub' ),
					(int) $count
				)
			)
		);
	}

}
