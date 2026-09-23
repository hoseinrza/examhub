<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * Removes everything ExamHub created in the database: every exam post (and its
 * attached post meta, including the download counters and file references) and
 * every term in the seven ExamHub taxonomies (مقطع/پایه/رشته/درس, سال, نوبت,
 * نوع آزمون) — and the "examhub_icon"/parent-link term meta that rides along
 * with them.
 *
 * What it intentionally does NOT touch: the uploaded question/answer files
 * themselves. Those live in the Media Library as ordinary attachments the site
 * owner may still want — deleting them here would be surprising and possibly
 * destructive, so they are left in place.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete all ExamHub data for the current site.
 *
 * The post type and taxonomies are not registered during uninstall (the plugin
 * is already deactivated), so we register them first — get_terms()/wp_delete_term()
 * refuse to operate on an unknown taxonomy, and registering is the cleanest way
 * to let the normal API handle cache invalidation and relationship cleanup.
 *
 * @since 1.0.0
 */
function examhub_uninstall_site() {

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-examhub-post-types.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-examhub-query.php';

	$post_types = new Examhub_Post_Types();
	$post_types->register_post_type();
	$post_types->register_taxonomies();

	// Delete every exam post; wp_delete_post( …, true ) also removes its meta.
	$exam_ids = get_posts(
		array(
			'post_type'        => 'examhub_exam',
			'post_status'      => 'any',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);

	foreach ( $exam_ids as $exam_id ) {
		wp_delete_post( $exam_id, true );
	}

	// Delete every term (and its term meta) in the current ExamHub taxonomies
	// plus the retired legacy ones (whose terms may linger after migration).
	// The retired list includes the old hierarchical examhub_structure
	// taxonomy itself (split into the four current flat taxonomies below) and
	// the even-older flat examhub_grade/examhub_field/examhub_subject
	// taxonomies that examhub_structure was originally migrated from — note
	// the latter three slugs are NOT the same taxonomies as the current
	// examhub_grade/examhub_field/examhub_subject (registered below as live,
	// current taxonomies); they're unrelated legacy data that predates this
	// plugin reusing those slugs. The retired ones are registered ad hoc so
	// wp_delete_term() works on them.
	$retired = array( 'examhub_structure', 'examhub_grade', 'examhub_field', 'examhub_subject' );

	foreach ( $retired as $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy( $taxonomy, 'examhub_exam', array( 'public' => false ) );
		}
	}

	$taxonomies = array_unique(
		array_merge(
			array_keys( Examhub_Query::STRUCTURE_TAXONOMIES ),
			array( 'examhub_year', 'examhub_term', 'examhub_exam_type' ),
			$retired
		)
	);

	foreach ( $taxonomies as $taxonomy ) {

		$term_ids = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		if ( is_array( $term_ids ) ) {
			foreach ( $term_ids as $term_id ) {
				wp_delete_term( $term_id, $taxonomy );
			}
		}
	}

	// Remove the migration flags/options so a fresh re-install starts clean.
	delete_option( 'examhub_structure_migrated' );
	delete_transient( 'examhub_structure_migration_notice' );
	delete_option( 'examhub_structure_split_migrated' );
	delete_transient( 'examhub_structure_split_migration_notice' );
	delete_option( 'examhub_terms_cache_version' );
}

// Run the cleanup for every site on the network (or just the one on single-site).
if ( is_multisite() ) {

	$site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		examhub_uninstall_site();
		restore_current_blog();
	}
} else {
	examhub_uninstall_site();
}
