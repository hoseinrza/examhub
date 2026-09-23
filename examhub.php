<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/hoseinrza/final-exam-bank
 * @since             1.0.0
 * @package           Examhub
 *
 * @wordpress-plugin
 * Plugin Name:       ExamHub
 * Plugin URI:        https://github.com/hoseinrza/final-exam-bank
 * Description:       A complete WordPress solution for managing and displaying final exam papers and answer sheets. Organize exams by grade, subject, year, and term, with PDF downloads, advanced filters, search, download statistics, and Elementor integration.
 * Version:           1.0.0
 * Author:            Amirhossein Rezazadeh 
 * Author URI:        https://github.com/hoseinrza/final-exam-bank/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       examhub
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'EXAMHUB_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-examhub-activator.php
 */
function activate_examhub() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-examhub-activator.php';
	Examhub_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-examhub-deactivator.php
 */
function deactivate_examhub() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-examhub-deactivator.php';
	Examhub_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_examhub' );
register_deactivation_hook( __FILE__, 'deactivate_examhub' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-examhub.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_examhub() {

	$plugin = new Examhub();
	$plugin->run();

}
run_examhub();
