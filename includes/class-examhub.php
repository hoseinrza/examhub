<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Examhub
 * @subpackage Examhub/includes
 * @author     Amirhossein Rezazadeh  <amir1382re@gmail.com>
 */
class Examhub {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Examhub_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'EXAMHUB_VERSION' ) ) {
			$this->version = EXAMHUB_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'examhub';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_post_type_hooks();
		$this->define_migration_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_ajax_hooks();
		$this->define_elementor_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Examhub_Loader. Orchestrates the hooks of the plugin.
	 * - Examhub_i18n. Defines internationalization functionality.
	 * - Examhub_Admin. Defines all hooks for the admin area.
	 * - Examhub_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-examhub-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-examhub-i18n.php';

		/**
		 * Core data layer: custom post type/taxonomies, the central query
		 * class, the download handler + counters, the shared card template
		 * helpers, and the front-end AJAX endpoints built on top of them.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-examhub-post-types.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-examhub-query.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-examhub-migration.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-examhub-structure-split-migration.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-examhub-download-handler.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/examhub-template-functions.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-examhub-ajax.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-examhub-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-examhub-public.php';

		$this->loader = new Examhub_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Examhub_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Examhub_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register the custom post type, taxonomies, and term-icon field hooks.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_post_type_hooks() {

		$post_types = new Examhub_Post_Types();

		$this->loader->add_action( 'init', $post_types, 'register_post_type' );
		$this->loader->add_action( 'init', $post_types, 'register_taxonomies' );
		$this->loader->add_action( 'init', $post_types, 'register_icon_field_hooks' );
		$this->loader->add_action( 'init', $post_types, 'register_parent_field_hooks' );

	}

	/**
	 * Register the one-time taxonomy migrations and their admin notices:
	 * first the legacy flat taxonomies → the (now retired) hierarchical
	 * examhub_structure tree, then that tree → the four independent flat
	 * taxonomies. The second migration reads examhub_structure, so it's
	 * registered after the first and both run in order on the same request.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_migration_hooks() {

		$migration = new Examhub_Migration();

		$this->loader->add_action( 'admin_init', $migration, 'maybe_migrate' );
		$this->loader->add_action( 'admin_notices', $migration, 'admin_notice' );

		$structure_split = new Examhub_Structure_Split_Migration();

		$this->loader->add_action( 'admin_init', $structure_split, 'maybe_migrate', 20 );
		$this->loader->add_action( 'admin_notices', $structure_split, 'admin_notice' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Examhub_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_exam_meta_boxes' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_exam_details' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_exam_taxonomies' );

		$this->loader->add_filter( 'manage_examhub_exam_posts_columns', $plugin_admin, 'set_exam_columns' );
		$this->loader->add_action( 'manage_examhub_exam_posts_custom_column', $plugin_admin, 'render_exam_column', 10, 2 );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Examhub_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Register the AJAX endpoints that power downloads, filtering, and the
	 * mega-library tree. Hooked for both logged-in and anonymous visitors
	 * since exams are free and open to everyone.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_ajax_hooks() {

		$download_handler = new Examhub_Download_Handler();

		$this->loader->add_action( 'wp_ajax_examhub_download', $download_handler, 'handle_download' );
		$this->loader->add_action( 'wp_ajax_nopriv_examhub_download', $download_handler, 'handle_download' );

		$ajax = new Examhub_Ajax();

		$this->loader->add_action( 'wp_ajax_examhub_query_exams', $ajax, 'query_exams' );
		$this->loader->add_action( 'wp_ajax_nopriv_examhub_query_exams', $ajax, 'query_exams' );
		$this->loader->add_action( 'wp_ajax_examhub_mega_branch', $ajax, 'mega_branch' );
		$this->loader->add_action( 'wp_ajax_nopriv_examhub_mega_branch', $ajax, 'mega_branch' );
		$this->loader->add_action( 'wp_ajax_examhub_dependent_terms', $ajax, 'dependent_terms' );
		$this->loader->add_action( 'wp_ajax_nopriv_examhub_dependent_terms', $ajax, 'dependent_terms' );

		// Bust the cached cascading-dropdown lookups whenever any structure
		// term (مقطع/پایه/رشته/درس) is created, edited, or deleted.
		foreach ( array_keys( Examhub_Query::STRUCTURE_TAXONOMIES ) as $taxonomy ) {
			$this->loader->add_action( "created_{$taxonomy}", 'Examhub_Query', 'bump_terms_cache_version' );
			$this->loader->add_action( "edited_{$taxonomy}", 'Examhub_Query', 'bump_terms_cache_version' );
			$this->loader->add_action( "delete_{$taxonomy}", 'Examhub_Query', 'bump_terms_cache_version' );
		}

	}

	/**
	 * Register the ExamHub Elementor widgets — but only once Elementor has
	 * actually finished loading.
	 *
	 * This is the soft-dependency hinge: add_action() can normally be queued
	 * for a hook that hasn't fired yet regardless of plugin load order. The
	 * one exception is when something (an mu-plugin, an early-loaded theme,
	 * a different load order on the host) causes Elementor to finish booting
	 * — and fire 'elementor/loaded' — before this very file is included. In
	 * that edge case the action has already completed and a callback queued
	 * afterwards would simply never run, even though Elementor is active and
	 * the rest of ExamHub (hooked on 'init', which always fires later) works
	 * fine. did_action() lets us detect that and initialise immediately
	 * instead of waiting for an event that already happened.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_elementor_hooks() {

		if ( did_action( 'elementor/loaded' ) ) {
			$this->init_elementor_widgets();
			return;
		}

		$this->loader->add_action( 'elementor/loaded', $this, 'init_elementor_widgets' );

	}

	/**
	 * Require the Elementor integration and register its category + widgets.
	 *
	 * @since 1.0.0
	 */
	public function init_elementor_widgets() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'elementor/class-examhub-elementor-loader.php';

		$elementor_loader = new Examhub_Elementor_Loader();

		add_action( 'elementor/elements/categories_registered', array( $elementor_loader, 'register_category' ) );

		/*
		 * Elementor renamed this hook in 3.5 (elementor/widgets/widgets_registered
		 * -> elementor/widgets/register), both passing $widgets_manager to the same
		 * callback signature. Hooking both keeps registration working whichever
		 * major version is active; only one of the two will ever actually fire.
		 */
		add_action( 'elementor/widgets/register', array( $elementor_loader, 'register_widgets' ) );
		add_action( 'elementor/widgets/widgets_registered', array( $elementor_loader, 'register_widgets' ) );

		add_action( 'elementor/dynamic_tags/register', array( $elementor_loader, 'register_dynamic_tags' ) );

		add_action( 'elementor/editor/after_enqueue_scripts', array( $elementor_loader, 'enqueue_editor_scripts' ) );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Examhub_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
