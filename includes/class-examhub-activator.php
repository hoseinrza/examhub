<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Examhub
 * @subpackage Examhub/includes
 * @author     Amirhossein Rezazadeh  <amir1382re@gmail.com>
 */
class Examhub_Activator {

	/**
	 * Default "exam type" (نوع آزمون) terms seeded on first activation.
	 *
	 * @since 1.0.0
	 * @var   string[]
	 */
	const DEFAULT_EXAM_TYPES = array( 'نهایی', 'مستمر', 'آزمایشی', 'کنکور' );

	/**
	 * Run one-time setup on plugin activation.
	 *
	 * No rewrite flush is needed (every taxonomy/post type uses
	 * rewrite => false / query_var => false). We do register the taxonomies and
	 * seed the default "exam type" terms so the new facet is usable immediately
	 * on a fresh install — wp_insert_term() requires the taxonomy to exist.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-examhub-post-types.php';

		$post_types = new Examhub_Post_Types();
		$post_types->register_post_type();
		$post_types->register_taxonomies();

		foreach ( self::DEFAULT_EXAM_TYPES as $name ) {
			if ( ! term_exists( $name, 'examhub_exam_type' ) ) {
				wp_insert_term( $name, 'examhub_exam_type' );
			}
		}
	}

}
