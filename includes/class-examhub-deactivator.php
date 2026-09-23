<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Examhub
 * @subpackage Examhub/includes
 * @author     Amirhossein Rezazadeh  <amir1382re@gmail.com>
 */
class Examhub_Deactivator {

	/**
	 * Run cleanup on plugin deactivation.
	 *
	 * Deliberately a no-op (mirrors Examhub_Activator::activate()): the post
	 * type and taxonomies register no rewrite rules, so there is nothing to
	 * flush on the way out. Data removal is handled separately by uninstall.php.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
	}

}
