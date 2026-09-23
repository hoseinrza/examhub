<?php

/**
 * "ExamHub – Dark Mode Tokens" — an invisible utility widget meant to be
 * dropped once site-wide (e.g. into a footer/header Theme Builder template).
 * It renders no visible markup; it only emits the global `--dark-*` CSS
 * variable set (from a built-in preset or fully custom colors) and a tiny
 * data carrier read by public/js/examhub-dark-mode.js for the Auto
 * Detect/Force Dark Mode fallback.
 *
 * Every other ExamHub widget's "تنظیمات حالت تیره" controls
 * (Examhub_Dark_Mode_Settings_Trait) fall back to these tokens when left
 * blank — the same `var(--local, var(--global, <literal>))` fallback chain
 * already used throughout examhub-dark-mode.css.
 *
 * Selectors for every color control here target `body.dark-mode,
 * [data-examhub-theme="dark"]` directly instead of `{{WRAPPER}}`, so the
 * resulting variables are genuinely global regardless of where in the DOM
 * this widget instance happens to sit.
 *
 * @since      2.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor/widgets
 */
class Examhub_Widget_Dark_Mode_Tokens extends \Elementor\Widget_Base {

	use Examhub_Dark_Mode_Settings_Trait;

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return 'examhub_dark_mode_tokens';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title() {
		return __( 'ExamHub – نشانه‌های حالت تیره (سراسری)', 'examhub' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_icon() {
		return 'eicon-paint-brush';
	}

	/**
	 * @inheritDoc
	 */
	public function get_categories() {
		return array( Examhub_Elementor_Loader::CATEGORY );
	}

	/**
	 * @inheritDoc
	 */
	public function get_keywords() {
		return array( 'examhub', 'dark mode', 'tokens', 'حالت تیره' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_script_depends() {
		return array( 'examhub-dark-mode-js' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_style_depends() {
		return array( 'examhub-dark-mode' );
	}

	/**
	 * @inheritDoc
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'examhub_dark_mode_tokens_info_section',
			array(
				'label' => __( 'راهنما', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'examhub_dark_mode_tokens_notice',
			array(
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw'  => __( 'این ویجت چیزی روی صفحه نمایش نمی‌دهد. کافی است یک‌بار آن را در هر بخشی از سایت (مثلاً قالب فوتر) قرار دهید تا تنظیمات حالت تیره برای همهٔ ویجت‌های ExamHub در سراسر سایت اعمال شود.', 'examhub' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'examhub_dark_mode_tokens_style_section',
			array(
				'label' => __( 'تنظیمات حالت تیره (Dark Mode Settings)', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->register_examhub_dark_mode_global_controls();

		$this->end_controls_section();
	}

	/**
	 * Emit nothing visible — only the preset's CSS (when not "custom") and a
	 * hidden data carrier for the front-end Auto Detect/Force Dark Mode
	 * fallback script.
	 *
	 * @since 2.0.0
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();

		$enabled = ! empty( $settings['examhub_dark_mode_enabled'] ) && 'yes' === $settings['examhub_dark_mode_enabled'];

		if ( ! $enabled ) {

			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p style="opacity:.6;font-size:12px;">' . esc_html__( 'استایل‌دهی حالت تیره غیرفعال است.', 'examhub' ) . '</p>';
			}

			return;
		}

		$preset = isset( $settings['examhub_dark_preset'] ) ? $settings['examhub_dark_preset'] : 'modern';

		if ( 'custom' !== $preset && isset( self::DARK_MODE_PRESETS[ $preset ] ) ) {

			$tokens = self::DARK_MODE_PRESETS[ $preset ]['tokens'];
			$lines  = array();

			foreach ( $tokens as $name => $value ) {
				$lines[] = '--dark-' . $name . ': ' . $value . ';';
			}

			echo '<style id="examhub-dark-mode-preset-' . esc_attr( $this->get_id() ) . '">';
			echo 'body.dark-mode, [data-examhub-theme="dark"] {' . implode( ' ', $lines ) . '}';
			echo '</style>';
		}

		$auto_detect = ! empty( $settings['examhub_auto_detect_theme'] ) && 'yes' === $settings['examhub_auto_detect_theme'];
		$force_dark  = ! empty( $settings['examhub_force_dark_mode'] ) && 'yes' === $settings['examhub_force_dark_mode'];

		if ( $auto_detect || $force_dark ) {
			?>
			<div
				class="examhub-dark-mode-tokens"
				data-examhub-auto-detect="<?php echo $auto_detect ? '1' : '0'; ?>"
				data-examhub-force-dark="<?php echo $force_dark ? '1' : '0'; ?>"
				hidden
			></div>
			<?php
		}
	}

}
