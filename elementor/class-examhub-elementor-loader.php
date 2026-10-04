<?php

/**
 * Registers ExamHub's widget category and widgets with Elementor.
 *
 * This is a soft dependency: the loader is only ever instantiated and hooked
 * after Elementor confirms it has loaded (see Examhub::define_elementor_hooks()),
 * so the rest of the plugin works perfectly well without Elementor installed.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor
 */
class Examhub_Elementor_Loader {

	/**
	 * Slug of the custom widget category shown in the Elementor panel.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	const CATEGORY = 'examhub';

	/**
	 * Require the shared trait used by every widget's style controls.
	 *
	 * The widget classes themselves are required later, from
	 * register_widgets(), because each one declares `extends
	 * \Elementor\Widget_Base` — and that class isn't loaded yet at
	 * 'elementor/loaded' time (Elementor only requires it from its
	 * Widgets_Manager constructor, during 'init'). Requiring them here
	 * would throw "Class Elementor\Widget_Base not found" before Elementor
	 * has had a chance to load its own base classes.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		require_once plugin_dir_path( __FILE__ ) . 'traits/trait-examhub-card-style-controls.php';
		require_once plugin_dir_path( __FILE__ ) . 'traits/trait-examhub-dark-mode-settings.php';
	}

	/**
	 * Whether the current request is rendering inside the Elementor editor or
	 * its live-preview iframe (or the editor's AJAX widget re-render).
	 *
	 * editor->is_edit_mode() alone is not reliable: depending on the Elementor
	 * version it is false inside the preview iframe, which made the Search &
	 * Filter widget render its normal (hidden popup) markup instead of the
	 * inline editor preview.
	 *
	 * @since 1.2.5
	 * @return bool
	 */
	public static function is_editor_context() {

		if ( ! class_exists( '\\Elementor\\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return false;
		}

		$plugin = \Elementor\Plugin::$instance;

		if ( isset( $plugin->editor ) && method_exists( $plugin->editor, 'is_edit_mode' ) && $plugin->editor->is_edit_mode() ) {
			return true;
		}

		if ( isset( $plugin->preview ) && method_exists( $plugin->preview, 'is_preview_mode' ) && $plugin->preview->is_preview_mode() ) {
			return true;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
		if ( isset( $_GET['elementor-preview'] ) ) {
			return true;
		}

		return isset( $_REQUEST['action'] ) && 'elementor_ajax' === $_REQUEST['action'];
		// phpcs:enable
	}

	/**
	 * Register the "ExamHub" widget category in the Elementor panel.
	 *
	 * @since 1.0.0
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 */
	public function register_category( $elements_manager ) {

		$elements_manager->add_category(
			self::CATEGORY,
			array(
				'title' => __( 'ExamHub', 'examhub' ),
				'icon'  => 'eicon-archive-posts',
			)
		);
	}

	/**
	 * Register the six ExamHub widgets with Elementor.
	 *
	 * Elementor renamed Widgets_Manager::register_widget_type() to register()
	 * in 3.5. Because this callback is hooked onto both the new
	 * ('elementor/widgets/register') and the legacy
	 * ('elementor/widgets/widgets_registered') events — only one of which ever
	 * fires — we detect which method the manager actually exposes instead of
	 * assuming the modern one, so a site still on pre-3.5 Elementor registers
	 * its widgets instead of hitting a fatal "Call to undefined method".
	 *
	 * @since 1.0.0
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_widgets( $widgets_manager ) {

		$path = plugin_dir_path( __FILE__ );

		require_once $path . 'widgets/class-widget-exam-showcase.php';
		require_once $path . 'widgets/class-widget-category-showcase.php';
		require_once $path . 'widgets/class-widget-download-library.php';
		require_once $path . 'widgets/class-widget-featured-exams.php';
		require_once $path . 'widgets/class-widget-search-filter.php';
		require_once $path . 'widgets/class-widget-exam-mega-library.php';
		require_once $path . 'widgets/class-widget-exam-section.php';
		require_once $path . 'widgets/class-widget-dark-mode-tokens.php';

		$widgets = array(
			new \Examhub_Widget_Exam_Showcase(),
			new \Examhub_Widget_Category_Showcase(),
			new \Examhub_Widget_Download_Library(),
			new \Examhub_Widget_Featured_Exams(),
			new \Examhub_Widget_Search_Filter(),
			new \Examhub_Widget_Exam_Mega_Library(),
			new \Examhub_Widget_Exam_Section(),
			new \Examhub_Widget_Dark_Mode_Tokens(),
		);

		$use_modern_api = method_exists( $widgets_manager, 'register' );

		foreach ( $widgets as $widget ) {
			if ( $use_modern_api ) {
				$widgets_manager->register( $widget );
			} else {
				$widgets_manager->register_widget_type( $widget );
			}
		}
	}

	/**
	 * Register the "ExamHub" Elementor dynamic tag group and its one tag,
	 * which outputs the compact "مقطع | پایه | رشته | درس" inline structure
	 * label for the current exam — usable from a Loop Grid/Theme Builder
	 * template via Elementor's dynamic tags.
	 *
	 * @since 1.0.0
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Elementor's dynamic tags manager.
	 */
	public function register_dynamic_tags( $dynamic_tags_manager ) {

		require_once plugin_dir_path( __FILE__ ) . 'dynamic-tags/class-dynamic-tag-structure.php';
		require_once plugin_dir_path( __FILE__ ) . 'dynamic-tags/class-dynamic-tag-featured-image.php';
		require_once plugin_dir_path( __FILE__ ) . 'dynamic-tags/class-dynamic-tag-download-count.php';
		require_once plugin_dir_path( __FILE__ ) . 'dynamic-tags/class-dynamic-tag-term-name.php';

		$dynamic_tags_manager->register_group(
			'examhub',
			array( 'title' => __( 'ExamHub', 'examhub' ) )
		);

		$method = method_exists( $dynamic_tags_manager, 'register' ) ? 'register' : 'register_tag';

		$dynamic_tags_manager->{$method}( new \Examhub_Dynamic_Tag_Structure() );
		$dynamic_tags_manager->{$method}( new \Examhub_Dynamic_Tag_Featured_Image() );
		$dynamic_tags_manager->{$method}( new \Examhub_Dynamic_Tag_Download_Count() );
		$dynamic_tags_manager->{$method}( new \Examhub_Dynamic_Tag_Term_Name() );
	}

	/**
	 * Enqueue the cascading-controls script for the Exam Showcase widget's
	 * editor panel (مقطع › پایه › رشته › درس pruning each other's options).
	 * Only ever loaded inside the Elementor editor, never the front-end.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_editor_scripts() {

		wp_enqueue_script(
			'examhub-elementor-editor',
			plugin_dir_url( __FILE__ ) . 'js/examhub-elementor-editor.js',
			array( 'jquery', 'elementor-editor' ),
			filemtime( plugin_dir_path( __FILE__ ) . 'js/examhub-elementor-editor.js' ),
			true
		);

		wp_localize_script(
			'examhub-elementor-editor',
			'examhubElementorEditor',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( Examhub_Ajax::NONCE_ACTION ),
				'i18n'     => array(
					'all' => __( 'همه', 'examhub' ),
				),
			)
		);
	}

	/**
	 * Load ExamHub front-end CSS inside Elementor's live preview iframe.
	 *
	 * Elementor normally pulls widget dependencies when a widget renders, but
	 * preview/editor versions can temporarily show the widget shell before
	 * those dependencies are attached. Loading the shared styles here keeps the
	 * Search & Filter preview visually identical to the published widget.
	 *
	 * @since 1.2.4
	 */
	public function enqueue_preview_styles() {

		$css = plugin_dir_url( __FILE__ ) . '../public/css/examhub-cards.css';
		$path = plugin_dir_path( __FILE__ ) . '../public/css/examhub-cards.css';
		$version = file_exists( $path ) ? filemtime( $path ) : EXAMHUB_VERSION;

		wp_enqueue_style( 'examhub-cards', $css, array(), $version, 'all' );

		$dark_css = plugin_dir_url( __FILE__ ) . '../public/css/examhub-dark-mode.css';
		$dark_path = plugin_dir_path( __FILE__ ) . '../public/css/examhub-dark-mode.css';
		$dark_version = file_exists( $dark_path ) ? filemtime( $dark_path ) : EXAMHUB_VERSION;

		wp_enqueue_style( 'examhub-dark-mode', $dark_css, array( 'examhub-cards' ), $dark_version, 'all' );
	}

	/**
	 * Load the shared front-end script inside the live-preview iframe so the
	 * Search & Filter widget (accordion, chips, apply/reset) is interactive
	 * while editing, not only on the published page.
	 *
	 * @since 1.2.5
	 */
	public function enqueue_preview_scripts() {

		$path = plugin_dir_path( __FILE__ ) . '../public/js/examhub-frontend.js';

		if ( ! wp_script_is( 'examhub-frontend', 'registered' ) ) {
			wp_register_script(
				'examhub-frontend',
				plugin_dir_url( __FILE__ ) . '../public/js/examhub-frontend.js',
				array( 'jquery' ),
				file_exists( $path ) ? filemtime( $path ) : EXAMHUB_VERSION,
				true
			);
		}

		wp_enqueue_script( 'examhub-frontend' );
	}

}
