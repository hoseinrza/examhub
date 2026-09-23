<?php

/**
 * Shared "Dark Mode Settings" Style-tab controls — the expanded, fully
 * Elementor-driven dark mode system layered on top of the original 3-field
 * override in Examhub_Card_Style_Controls_Trait::register_examhub_dark_mode_controls().
 *
 * This trait never touches that legacy method. Every widget that already
 * calls it keeps rendering byte-identical dark colors for existing saved
 * settings; this trait only adds new, additive control IDs and new CSS
 * variables (the `--examhub-dark2-*` namespace) that fall back through the
 * legacy `--examhub-dark-*` vars down to the global `--dark-*` tokens
 * (defined by Examhub_Widget_Dark_Mode_Tokens) and finally to today's
 * hardcoded literals in examhub-dark-mode.css — so a site with none of this
 * configured renders exactly as it did before this trait existed.
 *
 * @since      2.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor/traits
 */
trait Examhub_Dark_Mode_Settings_Trait {

	/**
	 * Built-in dark mode presets. Each maps a short set of global `--dark-*`
	 * tokens to literal hex values; selecting one in
	 * Examhub_Widget_Dark_Mode_Tokens fully defines that palette site-wide.
	 *
	 * @since 2.0.0
	 * @var   array<string,array>
	 */
	const DARK_MODE_PRESETS = array(
		'modern'  => array(
			'label'  => 'Modern Dark',
			'tokens' => array(
				'bg-primary'      => '#1a1a1a',
				'bg-secondary'    => '#2d2d2d',
				'card-bg'         => '#232323',
				'text-primary'    => '#e8e8e8',
				'text-secondary'  => '#b0b0b0',
				'border-color'    => '#3a3a3a',
				'accent-primary'  => '#3b82f6',
				'btn-primary-bg'  => '#3b82f6',
				'btn-primary-hover' => '#2563eb',
			),
		),
		'amoled'  => array(
			'label'  => 'AMOLED Black',
			'tokens' => array(
				'bg-primary'      => '#000000',
				'bg-secondary'    => '#0a0a0a',
				'card-bg'         => '#050505',
				'text-primary'    => '#f5f5f5',
				'text-secondary'  => '#a3a3a3',
				'border-color'    => '#1f1f1f',
				'accent-primary'  => '#22d3ee',
				'btn-primary-bg'  => '#06b6d4',
				'btn-primary-hover' => '#0891b2',
			),
		),
		'github'  => array(
			'label'  => 'GitHub Dark',
			'tokens' => array(
				'bg-primary'      => '#0d1117',
				'bg-secondary'    => '#161b22',
				'card-bg'         => '#161b22',
				'text-primary'    => '#c9d1d9',
				'text-secondary'  => '#8b949e',
				'border-color'    => '#30363d',
				'accent-primary'  => '#58a6ff',
				'btn-primary-bg'  => '#238636',
				'btn-primary-hover' => '#2ea043',
			),
		),
		'discord' => array(
			'label'  => 'Discord Style',
			'tokens' => array(
				'bg-primary'      => '#313338',
				'bg-secondary'    => '#2b2d31',
				'card-bg'         => '#232428',
				'text-primary'    => '#f2f3f5',
				'text-secondary'  => '#b5bac1',
				'border-color'    => '#1e1f22',
				'accent-primary'  => '#5865f2',
				'btn-primary-bg'  => '#5865f2',
				'btn-primary-hover' => '#4752c4',
			),
		),
		'slate'   => array(
			'label'  => 'Slate Dark',
			'tokens' => array(
				'bg-primary'      => '#0f172a',
				'bg-secondary'    => '#1e293b',
				'card-bg'         => '#1e293b',
				'text-primary'    => '#e2e8f0',
				'text-secondary'  => '#94a3b8',
				'border-color'    => '#334155',
				'accent-primary'  => '#38bdf8',
				'btn-primary-bg'  => '#0ea5e9',
				'btn-primary-hover' => '#0284c7',
			),
		),
	);

	/**
	 * Register the per-widget "تنظیمات حالت تیره (Dark Mode Settings)" Style
	 * section: the legacy 3-field override first (untouched, for back-compat),
	 * then the expanded Background/Text/Border/Button/State token groups.
	 *
	 * Must be called from a widget that also `use`s
	 * Examhub_Card_Style_Controls_Trait, since it calls that trait's
	 * register_examhub_dark_mode_controls() directly.
	 *
	 * @since 2.0.0
	 * @param string $wrapper_selector CSS selector (relative to {{WRAPPER}}) for the widget's main wrapper.
	 */
	protected function register_examhub_dark_mode_settings_section( $wrapper_selector ) {

		$this->start_controls_section(
			'examhub_dark_mode_settings_section',
			array(
				'label' => __( 'تنظیمات حالت تیره (Dark Mode Settings)', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'examhub_dark2_classic_heading',
			array(
				'label' => __( 'حالت تیره (کلاسیک)', 'examhub' ),
				'type'  => \Elementor\Controls_Manager::HEADING,
			)
		);

		// Untouched legacy method — keeps existing saved values rendering
		// byte-identically. Does not open/close its own section since the
		// section is already open here.
		$this->register_examhub_dark_mode_controls( $wrapper_selector );

		$dark_selector = "body.dark-mode {$wrapper_selector}, [data-examhub-theme=\"dark\"] {$wrapper_selector}";

		$this->add_control(
			'examhub_dark2_bg_heading',
			array(
				'label'     => __( 'پس‌زمینه (گسترده)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_dark2_bg_primary',
			array(
				'label'     => __( 'پس‌زمینه صفحه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-bg-primary: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_bg_secondary',
			array(
				'label'     => __( 'پس‌زمینه ثانویه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-bg-secondary: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_bg_tertiary',
			array(
				'label'     => __( 'پس‌زمینه سوم', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-bg-tertiary: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_bg_hover',
			array(
				'label'     => __( 'پس‌زمینه هاور', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-bg-hover: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_card_bg',
			array(
				'label'     => __( 'پس‌زمینه کارت', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-card-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_sidebar_bg',
			array(
				'label'     => __( 'پس‌زمینه سایدبار', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-sidebar-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_input_bg',
			array(
				'label'     => __( 'پس‌زمینه ورودی', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-input-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_text_heading',
			array(
				'label'     => __( 'متن (گسترده)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_dark2_text_secondary',
			array(
				'label'     => __( 'متن ثانویه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-text-secondary: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_text_muted',
			array(
				'label'     => __( 'متن کم‌رنگ (Muted)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-text-muted: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_text_tertiary',
			array(
				'label'     => __( 'متن سوم', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-text-tertiary: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_text_inverse',
			array(
				'label'     => __( 'متن معکوس', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-text-inverse: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_border_heading',
			array(
				'label'     => __( 'حاشیه و اکسنت (گسترده)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_dark2_border_color',
			array(
				'label'     => __( 'رنگ حاشیه اصلی', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_border_light',
			array(
				'label'     => __( 'حاشیه روشن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-border-light: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_border_dark',
			array(
				'label'     => __( 'حاشیه تیره', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-border-dark: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_card_border',
			array(
				'label'     => __( 'حاشیه کارت', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-card-border: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_input_border',
			array(
				'label'     => __( 'حاشیه ورودی', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-input-border: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_input_focus',
			array(
				'label'     => __( 'فوکاس ورودی', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-input-focus: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_accent_primary',
			array(
				'label'     => __( 'اکسنت اصلی', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-accent-primary: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_accent_primary_hover',
			array(
				'label'     => __( 'اکسنت اصلی - هاور', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-accent-primary-hover: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_accent_light',
			array(
				'label'     => __( 'اکسنت روشن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-accent-light: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_accent_lighter',
			array(
				'label'     => __( 'اکسنت روشن‌تر', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-accent-lighter: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_btn_heading',
			array(
				'label'     => __( 'دکمه‌ها (گسترده)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->start_controls_tabs( 'examhub_dark2_primary_btn_tabs' );

		$this->start_controls_tab( 'examhub_dark2_primary_btn_tab_normal', array( 'label' => __( 'دکمه اصلی - عادی', 'examhub' ) ) );

		$this->add_control(
			'examhub_dark2_primary_btn_bg',
			array(
				'label'     => __( 'پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-primary-btn-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_primary_btn_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-primary-btn-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_dark2_primary_btn_tab_hover', array( 'label' => __( 'دکمه اصلی - هاور', 'examhub' ) ) );

		$this->add_control(
			'examhub_dark2_primary_btn_bg_hover',
			array(
				'label'     => __( 'پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-primary-btn-bg-hover: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->start_controls_tabs( 'examhub_dark2_secondary_btn_tabs' );

		$this->start_controls_tab( 'examhub_dark2_secondary_btn_tab_normal', array( 'label' => __( 'دکمه فرعی - عادی', 'examhub' ) ) );

		$this->add_control(
			'examhub_dark2_secondary_btn_bg',
			array(
				'label'     => __( 'پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-secondary-btn-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_secondary_btn_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-secondary-btn-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_dark2_secondary_btn_tab_hover', array( 'label' => __( 'دکمه فرعی - هاور', 'examhub' ) ) );

		$this->add_control(
			'examhub_dark2_secondary_btn_bg_hover',
			array(
				'label'     => __( 'پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-secondary-btn-bg-hover: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'examhub_dark2_badge_heading',
			array(
				'label'     => __( 'بج و نشان‌ها', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_dark2_badge_bg',
			array(
				'label'     => __( 'پس‌زمینه بج', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-badge-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_badge_text',
			array(
				'label'     => __( 'متن بج', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-badge-text: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_states_heading',
			array(
				'label'     => __( 'وضعیت‌ها (Active / Selected / Hover / Focus)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_dark2_active_color',
			array(
				'label'     => __( 'رنگ وضعیت Active', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-active-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_selected_color',
			array(
				'label'     => __( 'رنگ وضعیت Selected', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-selected-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_hover_color',
			array(
				'label'     => __( 'رنگ وضعیت Hover', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-hover-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_focus_color',
			array(
				'label'     => __( 'رنگ وضعیت Focus', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-focus-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_success_heading',
			array(
				'label'     => __( 'رنگ‌های وضعیت (موفقیت / هشدار / خطا / اطلاعات)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_dark2_success_bg',
			array(
				'label'     => __( 'پس‌زمینه موفقیت', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-success-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_success_text',
			array(
				'label'     => __( 'متن موفقیت', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-success-text: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_warning_bg',
			array(
				'label'     => __( 'پس‌زمینه هشدار', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-warning-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_warning_text',
			array(
				'label'     => __( 'متن هشدار', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-warning-text: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_error_bg',
			array(
				'label'     => __( 'پس‌زمینه خطا', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-error-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_error_text',
			array(
				'label'     => __( 'متن خطا', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-error-text: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_info_bg',
			array(
				'label'     => __( 'پس‌زمینه اطلاعات', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-info-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark2_info_text',
			array(
				'label'     => __( 'متن اطلاعات', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					$dark_selector => '--examhub-dark2-info-text: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register the global Enable/Auto-Detect/Force/Preset controls used only
	 * by Examhub_Widget_Dark_Mode_Tokens. Targets `body.dark-mode,
	 * [data-examhub-theme="dark"]` directly (no {{WRAPPER}}) so the resulting
	 * CSS variables are genuinely site-wide regardless of where the editor
	 * drops the widget (e.g. a footer template).
	 *
	 * Must be called from inside an already-open TAB_STYLE section.
	 *
	 * @since 2.0.0
	 */
	protected function register_examhub_dark_mode_global_controls() {

		$global_selector = 'body.dark-mode, [data-examhub-theme="dark"]';

		$this->add_control(
			'examhub_dark_mode_enabled',
			array(
				'label'        => __( 'فعال‌سازی استایل‌دهی حالت تیره', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'examhub' ),
				'label_off'    => __( 'خیر', 'examhub' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'در صورت خاموش بودن، این ویجت هیچ توکن یا اسکریپتی منتشر نمی‌کند.', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_auto_detect_theme',
			array(
				'label'        => __( 'تشخیص خودکار تم (Auto Detect Theme)', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'examhub' ),
				'label_off'    => __( 'خیر', 'examhub' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array( 'examhub_dark_mode_enabled' => 'yes' ),
				'description'  => __( 'فقط زمانی فعال می‌شود که تم سایت خودش کلاس body.dark-mode را تنظیم نکرده باشد؛ بر اساس prefers-color-scheme مرورگر کاربر تصمیم می‌گیرد.', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_force_dark_mode',
			array(
				'label'        => __( 'اجبار حالت تیره (Force Dark Mode)', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'examhub' ),
				'label_off'    => __( 'خیر', 'examhub' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array( 'examhub_dark_mode_enabled' => 'yes' ),
				'description'  => __( 'در صورت فعال بودن تم سایت همچنان در اولویت است؛ این گزینه فقط زمانی اعمال می‌شود که تم خودش body.dark-mode را تنظیم نکرده باشد. در صورت تداخل با «تشخیص خودکار»، این گزینه برتری دارد.', 'examhub' ),
			)
		);

		$preset_options = array( 'custom' => __( 'سفارشی (Custom)', 'examhub' ) );

		foreach ( self::DARK_MODE_PRESETS as $slug => $preset ) {
			$preset_options[ $slug ] = $preset['label'];
		}

		$this->add_control(
			'examhub_dark_preset',
			array(
				'label'       => __( 'پیش‌تنظیم (Preset)', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $preset_options,
				'default'     => 'modern',
				'condition'   => array( 'examhub_dark_mode_enabled' => 'yes' ),
				'description' => __( 'انتخاب یک پیش‌تنظیم، پالت رنگی را برای کل سایت تعیین می‌کند. برای تنظیم دستی تک‌تک رنگ‌ها، «سفارشی» را انتخاب کنید.', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_dark_custom_heading',
			array(
				'label'     => __( 'نشانه‌های سراسری سفارشی (Global Dark Mode Tokens)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'examhub_dark_mode_enabled' => 'yes',
					'examhub_dark_preset'       => 'custom',
				),
			)
		);

		$custom_tokens = array(
			// Backgrounds
			'examhub_dark_bg_primary'      => array( __( 'پس‌زمینه صفحه (Page Background)', 'examhub' ), '--dark-bg-primary' ),
			'examhub_dark_bg_secondary'    => array( __( 'پس‌زمینه ظرف (Container Background)', 'examhub' ), '--dark-bg-secondary' ),
			'examhub_dark_bg_tertiary'     => array( __( 'پس‌زمینه سوم (Tertiary Background)', 'examhub' ), '--dark-bg-tertiary' ),
			'examhub_dark_bg_hover'        => array( __( 'پس‌زمینه هاور (Hover Background)', 'examhub' ), '--dark-bg-hover' ),
			'examhub_dark_card_bg'         => array( __( 'پس‌زمینه کارت (Card Background)', 'examhub' ), '--dark-card-bg' ),
			'examhub_dark_sidebar_bg'      => array( __( 'پس‌زمینه سایدبار (Sidebar Background)', 'examhub' ), '--dark-sidebar-bg' ),
			'examhub_dark_input_bg'        => array( __( 'پس‌زمینه ورودی (Input Background)', 'examhub' ), '--dark-input-bg' ),

			// Text Colors
			'examhub_dark_text_primary'    => array( __( 'متن اصلی (Primary Text)', 'examhub' ), '--dark-text-primary' ),
			'examhub_dark_text_secondary'  => array( __( 'متن ثانویه (Secondary Text)', 'examhub' ), '--dark-text-secondary' ),
			'examhub_dark_text_tertiary'   => array( __( 'متن سوم (Tertiary Text)', 'examhub' ), '--dark-text-tertiary' ),
			'examhub_dark_text_muted'      => array( __( 'متن کم‌رنگ (Muted Text)', 'examhub' ), '--dark-text-muted' ),
			'examhub_dark_text_inverse'    => array( __( 'متن معکوس (Inverse Text)', 'examhub' ), '--dark-text-inverse' ),

			// Borders & Dividers
			'examhub_dark_border_color'    => array( __( 'رنگ حاشیه (Border Color)', 'examhub' ), '--dark-border-color' ),
			'examhub_dark_border_light'    => array( __( 'حاشیه روشن (Light Border)', 'examhub' ), '--dark-border-light' ),
			'examhub_dark_border_dark'     => array( __( 'حاشیه تیره (Dark Border)', 'examhub' ), '--dark-border-dark' ),
			'examhub_dark_card_border'     => array( __( 'حاشیه کارت (Card Border)', 'examhub' ), '--dark-card-border' ),
			'examhub_dark_input_border'    => array( __( 'حاشیه ورودی (Input Border)', 'examhub' ), '--dark-input-border' ),
			'examhub_dark_input_focus'     => array( __( 'فوکاس ورودی (Input Focus)', 'examhub' ), '--dark-input-focus' ),

			// Accent Colors
			'examhub_dark_accent_primary'  => array( __( 'رنگ اصلی (Primary Accent)', 'examhub' ), '--dark-accent-primary' ),
			'examhub_dark_accent_primary_hover' => array( __( 'اکسنت اصلی - هاور (Primary Accent Hover)', 'examhub' ), '--dark-accent-primary-hover' ),
			'examhub_dark_accent_light'    => array( __( 'اکسنت روشن (Light Accent)', 'examhub' ), '--dark-accent-light' ),
			'examhub_dark_accent_lighter'  => array( __( 'اکسنت روشن‌تر (Lighter Accent)', 'examhub' ), '--dark-accent-lighter' ),

			// Buttons
			'examhub_dark_primary_btn_bg'  => array( __( 'پس‌زمینه دکمه اصلی (Primary Button BG)', 'examhub' ), '--dark-btn-primary-bg' ),
			'examhub_dark_primary_btn_text' => array( __( 'متن دکمه اصلی (Primary Button Text)', 'examhub' ), '--dark-btn-primary-text' ),
			'examhub_dark_primary_btn_hover' => array( __( 'هاور دکمه اصلی (Primary Button Hover)', 'examhub' ), '--dark-btn-primary-hover' ),
			'examhub_dark_secondary_btn_bg' => array( __( 'پس‌زمینه دکمه فرعی (Secondary Button BG)', 'examhub' ), '--dark-btn-secondary-bg' ),
			'examhub_dark_secondary_btn_text' => array( __( 'متن دکمه فرعی (Secondary Button Text)', 'examhub' ), '--dark-btn-secondary-text' ),

			// Badges
			'examhub_dark_badge_bg'        => array( __( 'پس‌زمینه بج (Badge Background)', 'examhub' ), '--dark-badge-bg' ),
			'examhub_dark_badge_text'      => array( __( 'متن بج (Badge Text)', 'examhub' ), '--dark-badge-text' ),

			// State Colors
			'examhub_dark_success_bg'      => array( __( 'پس‌زمینه موفقیت (Success Background)', 'examhub' ), '--dark-success-bg' ),
			'examhub_dark_success_text'    => array( __( 'متن موفقیت (Success Text)', 'examhub' ), '--dark-success-text' ),
			'examhub_dark_warning_bg'      => array( __( 'پس‌زمینه هشدار (Warning Background)', 'examhub' ), '--dark-warning-bg' ),
			'examhub_dark_warning_text'    => array( __( 'متن هشدار (Warning Text)', 'examhub' ), '--dark-warning-text' ),
			'examhub_dark_error_bg'        => array( __( 'پس‌زمینه خطا (Error Background)', 'examhub' ), '--dark-error-bg' ),
			'examhub_dark_error_text'      => array( __( 'متن خطا (Error Text)', 'examhub' ), '--dark-error-text' ),
			'examhub_dark_info_bg'         => array( __( 'پس‌زمینه اطلاعات (Info Background)', 'examhub' ), '--dark-info-bg' ),
			'examhub_dark_info_text'       => array( __( 'متن اطلاعات (Info Text)', 'examhub' ), '--dark-info-text' ),
		);

		foreach ( $custom_tokens as $control_id => $token ) {

			list( $label, $css_var ) = $token;

			$this->add_control(
				$control_id,
				array(
					'label'     => $label,
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						$global_selector => "{$css_var}: {{VALUE}};",
					),
					'condition' => array(
						'examhub_dark_mode_enabled' => 'yes',
						'examhub_dark_preset'       => 'custom',
					),
				)
			);
		}
	}
}
