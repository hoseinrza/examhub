<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Examhub
 * @subpackage Examhub/public
 * @author     Amirhossein Rezazadeh  <amir1382re@gmail.com>
 */
class Examhub_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// The theme already toggles a "dark-mode" class on <body> site-wide;
		// ExamHub never sets that class itself, it only styles against it.
		// Inject the matching CSS variables early so widgets pick up the
		// theme's dark mode immediately, with no flash of light styles.
		add_action( 'wp_head', array( $this, 'inject_dark_mode_critical_css' ), 1 );

	}

	/**
	 * Inject critical dark mode CSS variables inline to prevent FOUC.
	 *
	 * Runs very early in wp_head with priority 1 so the variables are
	 * available before any stylesheets load. Scoped to body.dark-mode,
	 * the class the theme itself applies when its dark mode is active —
	 * ExamHub never sets this class, it only reacts to it.
	 *
	 * @since    1.0.7
	 */
	public function inject_dark_mode_critical_css() {
		?>
		<style id="examhub-dark-mode-critical">
		body.dark-mode {
			--bg-primary: #1a1a1a;
			--bg-secondary: #2d2d2d;
			--bg-tertiary: #3a3a3a;
			--text-primary: #e8e8e8;
			--text-secondary: #b0b0b0;
			--text-tertiary: #888888;
			--text-muted: #707070;
			--border-color: #404040;
			--border-light: #333333;
			--accent-color: #3b82f6;
			--accent-hover: #2563eb;
			--accent-light: #60a5fa;
			--card-bg: #252525;
			--card-border: #383838;
			--sidebar-bg: #282828;
			--input-bg: #1e1e1e;
			--input-border: #383838;
			--shadow-color: rgba(0, 0, 0, 0.5);
			--shadow-sm: 0 1px 3px var(--shadow-color);
			--shadow-md: 0 4px 8px var(--shadow-color);
			--shadow-lg: 0 8px 16px var(--shadow-color);
			--shadow-xl: 0 16px 32px var(--shadow-color);
			--success-bg: #065f46;
			--success-text: #86efac;
			--success-border: #047857;
			--warning-bg: #78350f;
			--warning-text: #fcd34d;
			--warning-border: #d97706;
			--error-bg: #7f1d1d;
			--error-text: #fca5a5;
			--error-border: #dc2626;
			--info-bg: #0c2340;
			--info-text: #93c5fd;
			--info-border: #1e40af;
			--badge-bg: #d97706;
			--badge-text: #ffffff;
			--tab-inactive-bg: #383838;
			--tab-inactive-text: #b0b0b0;
			--tab-active-bg: #3b82f6;
			--tab-active-text: #ffffff;
			--tab-border-active: #60a5fa;
			--btn-primary-bg: #3b82f6;
			--btn-primary-text: #ffffff;
			--btn-primary-hover: #2563eb;
			--btn-success-bg: #059669;
			--btn-success-hover: #047857;
			--btn-secondary-bg: #383838;
			--btn-secondary-text: #e8e8e8;
			--hover-overlay: rgba(255, 255, 255, 0.05);
			--focus-outline: 2px solid #3b82f6;
		}
		</style>
		<?php
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * Neither stylesheet is force-enqueued here: exam cards are rendered
	 * exclusively through the Elementor widgets, so each widget pulls in
	 * both examhub-cards and examhub-dark-mode on demand via
	 * get_style_depends(). Dark mode CSS is scoped to body.dark-mode — the
	 * class the active theme already toggles — so it only ever changes
	 * anything on pages that both have a widget and have the theme's dark
	 * mode switched on.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_register_style( 'examhub-cards', plugin_dir_url( __FILE__ ) . 'css/examhub-cards.css', array(), $this->version, 'all' );

		wp_register_style( 'examhub-dark-mode', plugin_dir_url( __FILE__ ) . 'css/examhub-dark-mode.css', array( 'examhub-cards' ), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * Registers (but does not force-enqueue) the shared front-end script:
	 * it's only actually loaded on pages that contain one of the
	 * interactive ExamHub widgets, via each widget's get_script_depends().
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		$frontend_script_path    = plugin_dir_path( __FILE__ ) . 'js/examhub-frontend.js';
		$frontend_script_version = file_exists( $frontend_script_path ) ? filemtime( $frontend_script_path ) : $this->version;

		wp_register_script( 'examhub-frontend', plugin_dir_url( __FILE__ ) . 'js/examhub-frontend.js', array( 'jquery' ), $frontend_script_version, true );

		// Registered (not force-enqueued): only loaded on pages that contain
		// the "ExamHub – Dark Mode Tokens" widget, via its
		// get_script_depends(). The theme's own body.dark-mode toggle stays
		// authoritative; this script only drives the Auto Detect/Force Dark
		// Mode fallback when no theme manages dark mode at all.
		wp_register_script( 'examhub-dark-mode-js', plugin_dir_url( __FILE__ ) . 'js/examhub-dark-mode.js', array(), $this->version, true );

		wp_localize_script(
			'examhub-frontend',
			'examhub_vars',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( Examhub_Ajax::NONCE_ACTION ),
				'i18n'     => array(
					'loading'      => __( 'در حال بارگذاری…', 'examhub' ),
					'error'        => __( 'خطایی رخ داد. لطفاً دوباره تلاش کنید.', 'examhub' ),
					'retry'        => __( 'تلاش مجدد', 'examhub' ),
					'empty_branch' => __( 'موردی یافت نشد.', 'examhub' ),
					'files_suffix' => __( 'فایل', 'examhub' ),
				),
			)
		);

	}

}
