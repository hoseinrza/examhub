<?php

/**
 * Shared "Card appearance" Style-tab controls for every ExamHub widget that
 * renders a grid of exam cards (.examhub-grid > .examhub-card).
 *
 * Centralizing these controls means each widget only has to call
 * register_examhub_card_style_controls() instead of redeclaring the same
 * dozen Elementor controls six times. Every control writes a CSS custom
 * property onto the .examhub-grid wrapper; public/css/examhub-cards.css
 * reads those variables, so Elementor only ever has to override values,
 * never duplicate layout rules.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor/traits
 */
trait Examhub_Card_Style_Controls_Trait {

	/**
	 * Register the "ظاهر کارت" Style section.
	 *
	 * Must be called from within Widget_Base::register_controls().
	 *
	 * @since 1.0.0
	 */
	protected function register_examhub_card_style_controls() {

		$this->register_examhub_grid_style_controls(
			array(
				'section_id'      => 'examhub_card_style_section',
				'section_label'   => __( 'ظاهر کارت', 'examhub' ),
				'wrapper_selector' => '{{WRAPPER}} .examhub-grid',
				'item_selector'   => '{{WRAPPER}} .examhub-card',
				'title_selector'  => '{{WRAPPER}} .examhub-card__title',
				'columns_min'     => 1,
				'columns_default' => '4',
				'tablet_default'  => '2',
				'mobile_default'  => '1',
			)
		);

		$this->add_control(
			'examhub_badge_heading',
			array(
				'label'     => __( 'نشان «ویژه»', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_badge_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه نشان', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-badge-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_badge_color',
			array(
				'label'     => __( 'رنگ متن نشان', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-badge-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_badge_border_color',
			array(
				'label'     => __( 'رنگ حاشیه نشان', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-badge-border: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'examhub_badge_radius',
			array(
				'label'      => __( 'گردی گوشه‌ها', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-badge-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'examhub_badge_typography',
				'selector' => '{{WRAPPER}} .examhub-grid .examhub-card__badge, {{WRAPPER}} .examhub-grid .examhub-card__badge--floating',
			)
		);

		$this->add_control(
			'examhub_badge_dark_heading',
			array(
				'label'     => __( 'نشان «ویژه» (حالت تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_badge_dark_bg',
			array(
				'label'     => __( 'پس‌زمینه (تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'body.dark-mode {{WRAPPER}} .examhub-grid, [data-examhub-theme="dark"] {{WRAPPER}} .examhub-grid' => '--examhub-badge-dark-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_badge_dark_color',
			array(
				'label'     => __( 'رنگ متن (تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'body.dark-mode {{WRAPPER}} .examhub-grid, [data-examhub-theme="dark"] {{WRAPPER}} .examhub-grid' => '--examhub-badge-dark-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_badge_dark_border',
			array(
				'label'     => __( 'رنگ حاشیه (تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'body.dark-mode {{WRAPPER}} .examhub-grid, [data-examhub-theme="dark"] {{WRAPPER}} .examhub-grid' => '--examhub-badge-dark-border: {{VALUE}};',
				),
			)
		);

		$this->register_examhub_chip_style_controls();

		$this->add_control(
			'examhub_questions_btn_heading',
			array(
				'label'     => __( 'دکمه «دانلود سوالات»', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->register_examhub_button_color_controls( 'questions' );

		$this->add_control(
			'examhub_answers_btn_heading',
			array(
				'label'     => __( 'دکمه «دانلود پاسخنامه»', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->register_examhub_button_color_controls( 'answers' );

		$this->add_control(
			'examhub_view_link_heading',
			array(
				'label'     => __( 'دکمه «نمایش» (پیش‌نمایش)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->register_examhub_view_link_style_controls();

		$this->register_examhub_dark_mode_controls( '{{WRAPPER}} .examhub-grid' );

		$this->register_examhub_chip_dark_mode_controls();

		$this->register_examhub_button_dark_mode_controls( 'questions', __( 'دکمه «دانلود سوالات» (حالت تیره)', 'examhub' ) );
		$this->register_examhub_button_dark_mode_controls( 'answers', __( 'دکمه «دانلود پاسخنامه» (حالت تیره)', 'examhub' ) );
		$this->register_examhub_view_link_dark_mode_controls();

		$this->end_controls_section();
	}

	/**
	 * Register light-mode color-only controls for the year/category chips
	 * shown in the card's meta row. Per design requirements these chips have
	 * a fixed position/shape — only their colors are editable.
	 *
	 * @since 1.3.0
	 */
	private function register_examhub_chip_style_controls() {

		$this->add_control(
			'examhub_chips_heading',
			array(
				'label'     => __( 'نشان سال و رشته (Year & Category)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_year_chip_bg',
			array(
				'label'     => __( 'پس‌زمینه نشان سال', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-year-chip-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_year_chip_color',
			array(
				'label'     => __( 'رنگ متن نشان سال', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-year-chip-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_year_chip_border',
			array(
				'label'     => __( 'رنگ حاشیه نشان سال', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-year-chip-border: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_category_chip_bg',
			array(
				'label'     => __( 'پس‌زمینه نشان رشته', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-category-chip-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_category_chip_color',
			array(
				'label'     => __( 'رنگ متن نشان رشته', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-category-chip-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_category_chip_border',
			array(
				'label'     => __( 'رنگ حاشیه نشان رشته', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-category-chip-border: {{VALUE}};',
				),
			)
		);
	}

	/**
	 * Register dark-mode-only color overrides for the year/category chips,
	 * independent from the light-mode controls above.
	 *
	 * @since 1.3.0
	 */
	private function register_examhub_chip_dark_mode_controls() {

		$dark = 'body.dark-mode {{WRAPPER}} .examhub-grid, [data-examhub-theme="dark"] {{WRAPPER}} .examhub-grid';

		$this->add_control(
			'examhub_chips_dark_heading',
			array(
				'label'     => __( 'نشان سال و رشته (حالت تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_year_chip_dark_bg',
			array(
				'label'     => __( 'پس‌زمینه نشان سال (تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-year-chip-dark-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_year_chip_dark_color',
			array(
				'label'     => __( 'رنگ متن نشان سال (تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-year-chip-dark-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_year_chip_dark_border',
			array(
				'label'     => __( 'رنگ حاشیه نشان سال (تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-year-chip-dark-border: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_category_chip_dark_bg',
			array(
				'label'     => __( 'پس‌زمینه نشان رشته (تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-category-chip-dark-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_category_chip_dark_color',
			array(
				'label'     => __( 'رنگ متن نشان رشته (تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-category-chip-dark-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_category_chip_dark_border',
			array(
				'label'     => __( 'رنگ حاشیه نشان رشته (تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-category-chip-dark-border: {{VALUE}};' ),
			)
		);
	}

	/**
	 * Register full Normal/Hover/Active style controls for the card's "view"
	 * link/button (.examhub-card__view-link) — background, text, border,
	 * radius, padding, typography. Mirrors register_examhub_button_color_controls()
	 * but the simple color values are written as CSS variables on the grid
	 * wrapper (matching the pre-existing --examhub-view-link-color convention)
	 * while border/radius/padding/typography apply directly to the link.
	 *
	 * @since 1.3.0
	 */
	private function register_examhub_view_link_style_controls() {

		$selector = '{{WRAPPER}} .examhub-card__view-link';

		$this->start_controls_tabs( 'examhub_view_link_tabs' );

		$this->start_controls_tab( 'examhub_view_link_tab_normal', array( 'label' => __( 'عادی', 'examhub' ) ) );

		$this->add_control(
			'examhub_view_link_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-view-link-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_view_link_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-view-link-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_view_link_border_color',
			array(
				'label'     => __( 'رنگ حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-view-link-border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_view_link_tab_hover', array( 'label' => __( 'هاور', 'examhub' ) ) );

		$this->add_control(
			'examhub_view_link_bg_hover',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-view-link-bg-hover: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_view_link_color_hover',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-view-link-color-hover: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_view_link_border_color_hover',
			array(
				'label'     => __( 'رنگ حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-view-link-border-color-hover: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_view_link_tab_active', array( 'label' => __( 'فعال', 'examhub' ) ) );

		$this->add_control(
			'examhub_view_link_bg_active',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-view-link-bg-active: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_view_link_color_active',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-view-link-color-active: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_view_link_border_color_active',
			array(
				'label'     => __( 'رنگ حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => '--examhub-view-link-border-color-active: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'examhub_view_link_border',
				'selector' => $selector,
			)
		);

		$this->add_responsive_control(
			'examhub_view_link_radius',
			array(
				'label'      => __( 'گردی گوشه‌ها', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'examhub_view_link_padding',
			array(
				'label'      => __( 'فاصله داخلی (Padding)', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'examhub_view_link_typography',
				'selector' => $selector,
			)
		);
	}

	/**
	 * Register dark-mode-only Normal/Hover/Active overrides for the card's
	 * view link/button, independent from the light-mode controls above.
	 *
	 * @since 1.3.0
	 */
	private function register_examhub_view_link_dark_mode_controls() {

		$dark = 'body.dark-mode {{WRAPPER}} .examhub-grid, [data-examhub-theme="dark"] {{WRAPPER}} .examhub-grid';

		$this->add_control(
			'examhub_view_link_dark_heading',
			array(
				'label'     => __( 'دکمه «نمایش» (حالت تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->start_controls_tabs( 'examhub_view_link_dark_tabs' );

		$this->start_controls_tab( 'examhub_view_link_dark_tab_normal', array( 'label' => __( 'عادی', 'examhub' ) ) );

		$this->add_control(
			'examhub_view_link_dark_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-view-link-dark-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_view_link_dark_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-view-link-dark-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_view_link_dark_border',
			array(
				'label'     => __( 'رنگ حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-view-link-dark-border: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_view_link_dark_tab_hover', array( 'label' => __( 'هاور', 'examhub' ) ) );

		$this->add_control(
			'examhub_view_link_dark_bg_hover',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-view-link-dark-bg-hover: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_view_link_dark_color_hover',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-view-link-dark-color-hover: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_view_link_dark_border_hover',
			array(
				'label'     => __( 'رنگ حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-view-link-dark-border-hover: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_view_link_dark_tab_active', array( 'label' => __( 'فعال', 'examhub' ) ) );

		$this->add_control(
			'examhub_view_link_dark_bg_active',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-view-link-dark-bg-active: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_view_link_dark_color_active',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-view-link-dark-color-active: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_view_link_dark_border_active',
			array(
				'label'     => __( 'رنگ حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-view-link-dark-border-active: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();
	}

	/**
	 * Register the generic "grid layout" Style-tab controls (columns, gap,
	 * radius, shadow, title typography, hover effect) against an arbitrary
	 * wrapper/item/title selector set. Shared by both the exam-card grid
	 * (register_examhub_card_style_controls()) and any other tile-style grid,
	 * such as the Category Showcase widget's term tiles.
	 *
	 * @since 1.1.0
	 * @param array $args {
	 *     @type string $section_id       Unique Style-section control id.
	 *     @type string $section_label    Style-section label.
	 *     @type string $wrapper_selector CSS selector for the grid wrapper (receives the CSS vars).
	 *     @type string $item_selector    CSS selector for a single grid item (receives the box-shadow).
	 *     @type string $title_selector   CSS selector for the item's title (receives the typography).
	 *     @type int    $columns_min      Minimum selectable column count. Default 1.
	 *     @type string $columns_default  Desktop default column count.
	 *     @type string $tablet_default   Tablet default column count.
	 *     @type string $mobile_default   Mobile default column count.
	 * }
	 * @return void Leaves the controls section open so callers can add more controls before end_controls_section().
	 */
	protected function register_examhub_grid_style_controls( array $args ) {

		$args = wp_parse_args(
			$args,
			array(
				'columns_min'     => 1,
				'columns_default' => '4',
				'tablet_default'  => '2',
				'mobile_default'  => '1',
			)
		);

		$column_options = array();
		for ( $i = $args['columns_min']; $i <= 6; $i++ ) {
			$column_options[ (string) $i ] = (string) $i;
		}

		$this->start_controls_section(
			$args['section_id'],
			array(
				'label' => $args['section_label'],
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'examhub_columns',
			array(
				'label'          => __( 'تعداد ستون', 'examhub' ),
				'type'           => \Elementor\Controls_Manager::SELECT,
				'options'        => $column_options,
				'default'        => $args['columns_default'],
				'tablet_default' => $args['tablet_default'],
				'mobile_default' => $args['mobile_default'],
				'selectors'      => array(
					$args['wrapper_selector'] => '--examhub-columns: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'examhub_gap',
			array(
				'label'      => __( 'فاصله', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 80,
					),
				),
				'default'    => array(
					'size' => 20,
					'unit' => 'px',
				),
				'selectors'  => array(
					$args['wrapper_selector'] => '--examhub-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'examhub_radius',
			array(
				'label'      => __( 'گردی گوشه‌ها (Radius)', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'default'    => array(
					'size' => 12,
					'unit' => 'px',
				),
				'selectors'  => array(
					$args['wrapper_selector'] => '--examhub-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'examhub_card_shadow',
				'label'    => __( 'سایه (Shadow)', 'examhub' ),
				'selector' => $args['item_selector'],
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'examhub_title_typography',
				'label'    => __( 'تایپوگرافی عنوان', 'examhub' ),
				'selector' => $args['title_selector'],
			)
		);

		$this->add_control(
			'examhub_hover_effect',
			array(
				'label'        => __( 'افکت Hover', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SELECT,
				'options'      => array(
					'none'   => __( 'هیچ‌کدام', 'examhub' ),
					'lift'   => __( 'بالا آمدن', 'examhub' ),
					'zoom'   => __( 'بزرگ‌نمایی تصویر', 'examhub' ),
					'shadow' => __( 'تشدید سایه', 'examhub' ),
				),
				'default'      => 'lift',
				'prefix_class' => 'examhub-hover-',
			)
		);

		$this->add_control(
			'examhub_card_hover_border',
			array(
				'label'     => __( 'رنگ حاشیه هاور کارت', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FF7A0D',
				'selectors' => array(
					$args['wrapper_selector'] => '--examhub-card-hover-border: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_card_dark_hover_border',
			array(
				'label'     => __( 'رنگ حاشیه هاور کارت (حالت تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FF7A0D',
				'selectors' => array(
					"body.dark-mode {$args['wrapper_selector']}, [data-examhub-theme=\"dark\"] {$args['wrapper_selector']}" => '--examhub-card-dark-hover-border: {{VALUE}};',
				),
			)
		);
	}

	/**
	 * Register a "حالت تیره (Dark Mode)" control group that lets editors
	 * override the dark-mode background/text/border colors per-widget,
	 * instead of relying solely on the theme-wide examhub-dark-mode.css
	 * defaults. Writes CSS variables consumed under `body.dark-mode`.
	 *
	 * @since 1.1.0
	 * @param string $wrapper_selector CSS selector for the grid wrapper that receives the CSS vars.
	 */
	protected function register_examhub_dark_mode_controls( $wrapper_selector ) {

		$this->add_control(
			'examhub_dark_mode_heading',
			array(
				'label'     => __( 'حالت تیره (Dark Mode)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_dark_bg',
			array(
				'label'     => __( 'پس‌زمینه (تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					"body.dark-mode {$wrapper_selector}" => '--examhub-dark-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark_text',
			array(
				'label'     => __( 'متن (تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					"body.dark-mode {$wrapper_selector}" => '--examhub-dark-text: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_dark_border',
			array(
				'label'     => __( 'حاشیه (تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					"body.dark-mode {$wrapper_selector}" => '--examhub-dark-border: {{VALUE}};',
				),
			)
		);
	}

	/**
	 * Register a Normal/Hover color-tab pair (background + text) for one
	 * of the card's download buttons, writing CSS variables scoped to it.
	 *
	 * @since 1.0.0
	 * @param string $key Either "questions" or "answers" — used as both the
	 *                    control-id prefix and the CSS variable namespace.
	 */
	private function register_examhub_button_color_controls( $key ) {

		$this->start_controls_tabs( "examhub_{$key}_btn_tabs" );

		$this->start_controls_tab(
			"examhub_{$key}_btn_tab_normal",
			array( 'label' => __( 'عادی', 'examhub' ) )
		);

		$this->add_control(
			"examhub_{$key}_btn_bg",
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => "--examhub-{$key}-btn-bg: {{VALUE}};",
				),
			)
		);

		$this->add_control(
			"examhub_{$key}_btn_color",
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => "--examhub-{$key}-btn-color: {{VALUE}};",
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			"examhub_{$key}_btn_tab_hover",
			array( 'label' => __( 'هاور', 'examhub' ) )
		);

		$this->add_control(
			"examhub_{$key}_btn_bg_hover",
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => "--examhub-{$key}-btn-bg-hover: {{VALUE}};",
				),
			)
		);

		$this->add_control(
			"examhub_{$key}_btn_color_hover",
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => "--examhub-{$key}-btn-color-hover: {{VALUE}};",
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			"examhub_{$key}_btn_tab_active",
			array( 'label' => __( 'فعال', 'examhub' ) )
		);

		$this->add_control(
			"examhub_{$key}_btn_bg_active",
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => "--examhub-{$key}-btn-bg-active: {{VALUE}};",
				),
			)
		);

		$this->add_control(
			"examhub_{$key}_btn_color_active",
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-grid' => "--examhub-{$key}-btn-color-active: {{VALUE}};",
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => "examhub_{$key}_btn_border",
				'selector' => "{{WRAPPER}} .examhub-btn--{$key}",
			)
		);

		$this->add_responsive_control(
			"examhub_{$key}_btn_radius",
			array(
				'label'      => __( 'گردی گوشه‌ها', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					"{{WRAPPER}} .examhub-btn--{$key}" => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			"examhub_{$key}_btn_padding",
			array(
				'label'      => __( 'فاصله داخلی (Padding)', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					"{{WRAPPER}} .examhub-btn--{$key}" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => "examhub_{$key}_btn_typography",
				'selector' => "{{WRAPPER}} .examhub-btn--{$key}",
			)
		);
	}

	/**
	 * Register a Normal/Hover/Active color-tab triple of *dark-mode-only*
	 * overrides for one of the card's download buttons. Independent from
	 * register_examhub_button_color_controls() so dark mode never has to
	 * share a value with light mode.
	 *
	 * @since 1.2.0
	 * @param string $key    Either "questions" or "answers".
	 * @param string $label  Heading label, e.g. "دکمه «دانلود سوالات» (حالت تیره)".
	 */
	protected function register_examhub_button_dark_mode_controls( $key, $label ) {

		$dark = 'body.dark-mode {{WRAPPER}} .examhub-grid, [data-examhub-theme="dark"] {{WRAPPER}} .examhub-grid';

		$this->add_control(
			"examhub_{$key}_btn_dark_heading",
			array(
				'label'     => $label,
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->start_controls_tabs( "examhub_{$key}_btn_dark_tabs" );

		$this->start_controls_tab(
			"examhub_{$key}_btn_dark_tab_normal",
			array( 'label' => __( 'عادی', 'examhub' ) )
		);

		$this->add_control(
			"examhub_{$key}_btn_dark_bg",
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => "--examhub-{$key}-btn-dark-bg: {{VALUE}};" ),
			)
		);

		$this->add_control(
			"examhub_{$key}_btn_dark_color",
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => "--examhub-{$key}-btn-dark-color: {{VALUE}};" ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			"examhub_{$key}_btn_dark_tab_hover",
			array( 'label' => __( 'هاور', 'examhub' ) )
		);

		$this->add_control(
			"examhub_{$key}_btn_dark_bg_hover",
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => "--examhub-{$key}-btn-dark-bg-hover: {{VALUE}};" ),
			)
		);

		$this->add_control(
			"examhub_{$key}_btn_dark_color_hover",
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => "--examhub-{$key}-btn-dark-color-hover: {{VALUE}};" ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			"examhub_{$key}_btn_dark_tab_active",
			array( 'label' => __( 'فعال', 'examhub' ) )
		);

		$this->add_control(
			"examhub_{$key}_btn_dark_bg_active",
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => "--examhub-{$key}-btn-dark-bg-active: {{VALUE}};" ),
			)
		);

		$this->add_control(
			"examhub_{$key}_btn_dark_color_active",
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => "--examhub-{$key}-btn-dark-color-active: {{VALUE}};" ),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();
	}

	/**
	 * Build the display-options array forwarded to examhub_render_exam_card()
	 * from the widget's own "نمایش تصویر"/"نمایش آمار دانلود" switcher controls.
	 *
	 * @since 1.0.0
	 * @param  array $settings Elementor widget settings (from $this->get_settings_for_display()).
	 * @return array{show_image: bool, show_stats: bool}
	 */
	protected function get_examhub_display_atts( array $settings ) {

		return array(
			'show_image' => ! empty( $settings['examhub_show_image'] ) && 'yes' === $settings['examhub_show_image'],
			'show_stats' => ! empty( $settings['examhub_show_stats'] ) && 'yes' === $settings['examhub_show_stats'],
		);
	}

	/**
	 * Register the Content-tab controls for the shared "نمایش بیشتر / نمایش
	 * کمتر" toggle button: independent text + icon for each state, plus a
	 * switch to fall back to the old "hide at the end" behaviour. Must be
	 * called from inside an already-open TAB_CONTENT section.
	 *
	 * @since 1.1.0
	 */
	protected function register_examhub_toggle_button_content_controls() {

		$this->add_control(
			'examhub_toggle_heading',
			array(
				'label'     => __( 'دکمه «نمایش بیشتر / نمایش کمتر»', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_show_less_enabled',
			array(
				'label'        => __( 'تبدیل به «نمایش کمتر» در پایان لیست', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'examhub' ),
				'label_off'    => __( 'خیر', 'examhub' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'در صورت خاموش بودن، دکمه پس از نمایش همه نتایج به‌سادگی پنهان می‌شود (رفتار قبلی).', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_show_more_text',
			array(
				'label'   => __( 'متن دکمه «نمایش بیشتر»', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'نمایش بیشتر', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_show_more_icon',
			array(
				'label' => __( 'آیکون دکمه «نمایش بیشتر»', 'examhub' ),
				'type'  => \Elementor\Controls_Manager::ICONS,
			)
		);

		$this->add_control(
			'examhub_show_less_text',
			array(
				'label'     => __( 'متن دکمه «نمایش کمتر»', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'نمایش کمتر', 'examhub' ),
				'condition' => array( 'examhub_show_less_enabled' => 'yes' ),
			)
		);

		$this->add_control(
			'examhub_show_less_icon',
			array(
				'label'     => __( 'آیکون دکمه «نمایش کمتر»', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'condition' => array( 'examhub_show_less_enabled' => 'yes' ),
			)
		);
	}

	/**
	 * Register the Style-tab section for the toggle button: shared alignment
	 * plus fully independent Normal/Hover style controls for each of the two
	 * states (examhub_register_examhub_toggle_state_style_controls()).
	 *
	 * @since 1.1.0
	 * @param string $footer_selector CSS selector (relative to {{WRAPPER}}) of the
	 *                                button's containing footer element, used only
	 *                                for the alignment control.
	 */
	protected function register_examhub_toggle_button_style_controls( $footer_selector ) {

		$this->start_controls_section(
			'examhub_toggle_style_section',
			array(
				'label' => __( 'دکمه «نمایش بیشتر / نمایش کمتر»', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'examhub_toggle_align',
			array(
				'label'     => __( 'ترازبندی', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'ابتدا', 'examhub' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'     => array(
						'title' => __( 'وسط', 'examhub' ),
						'icon'  => 'eicon-text-align-center',
					),
					'flex-end'   => array(
						'title' => __( 'انتها', 'examhub' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'center',
				'selectors' => array(
					"{{WRAPPER}} {$footer_selector}" => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->register_examhub_toggle_state_style_controls( 'more', __( 'حالت «نمایش بیشتر»', 'examhub' ) );
		$this->register_examhub_toggle_state_style_controls( 'less', __( 'حالت «نمایش کمتر»', 'examhub' ) );

		$this->register_examhub_toggle_dark_mode_controls( 'more', __( 'حالت «نمایش بیشتر» (حالت تیره)', 'examhub' ) );
		$this->register_examhub_toggle_dark_mode_controls( 'less', __( 'حالت «نمایش کمتر» (حالت تیره)', 'examhub' ) );

		$this->end_controls_section();
	}

	/**
	 * Register one toggle-button state's full independent style controls
	 * (typography, Normal/Hover color, background, border, radius, padding),
	 * all scoped to `.examhub-toggle-btn[data-state="{$state}"]` so the two
	 * states never share a single declaration.
	 *
	 * @since 1.1.0
	 * @param string $state       Either "more" or "less".
	 * @param string $state_label Heading label shown above this state's controls.
	 */
	private function register_examhub_toggle_state_style_controls( $state, $state_label ) {

		$selector       = "{{WRAPPER}} .examhub-toggle-btn[data-state=\"{$state}\"]";
		$selector_hover = "{$selector}:hover";

		$this->add_control(
			"examhub_toggle_{$state}_heading",
			array(
				'label'     => $state_label,
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => "examhub_toggle_{$state}_typography",
				'selector' => $selector,
			)
		);

		$this->start_controls_tabs( "examhub_toggle_{$state}_color_tabs" );

		$this->start_controls_tab(
			"examhub_toggle_{$state}_color_tab_normal",
			array( 'label' => __( 'عادی', 'examhub' ) )
		);

		$this->add_control(
			"examhub_toggle_{$state}_color",
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $selector => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			"examhub_toggle_{$state}_bg",
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $selector => 'background-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			"examhub_toggle_{$state}_color_tab_hover",
			array( 'label' => __( 'هاور', 'examhub' ) )
		);

		$this->add_control(
			"examhub_toggle_{$state}_color_hover",
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $selector_hover => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			"examhub_toggle_{$state}_bg_hover",
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $selector_hover => 'background-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			"examhub_toggle_{$state}_color_tab_active",
			array( 'label' => __( 'فعال', 'examhub' ) )
		);

		$this->add_control(
			"examhub_toggle_{$state}_color_active",
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( "{$selector}:active" => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			"examhub_toggle_{$state}_bg_active",
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( "{$selector}:active" => 'background-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => "examhub_toggle_{$state}_border",
				'selector' => $selector,
			)
		);

		$this->add_responsive_control(
			"examhub_toggle_{$state}_radius",
			array(
				'label'      => __( 'گردی گوشه‌ها', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			"examhub_toggle_{$state}_padding",
			array(
				'label'      => __( 'فاصله داخلی (Padding)', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);
	}

	/**
	 * Register dark-mode-only Normal/Hover/Active overrides for one toggle
	 * button state, independent from the light-mode controls above. Shared
	 * by every widget that calls register_examhub_toggle_button_style_controls()
	 * (Search & Filter, Download Library) since it only relies on {{WRAPPER}}.
	 *
	 * @since 1.2.0
	 * @param string $state       Either "more" or "less".
	 * @param string $state_label Heading label shown above this state's dark controls.
	 */
	private function register_examhub_toggle_dark_mode_controls( $state, $state_label ) {

		$selector       = "body.dark-mode {{WRAPPER}} .examhub-toggle-btn[data-state=\"{$state}\"], [data-examhub-theme=\"dark\"] {{WRAPPER}} .examhub-toggle-btn[data-state=\"{$state}\"]";
		$selector_hover = "body.dark-mode {{WRAPPER}} .examhub-toggle-btn[data-state=\"{$state}\"]:hover, [data-examhub-theme=\"dark\"] {{WRAPPER}} .examhub-toggle-btn[data-state=\"{$state}\"]:hover";
		$selector_active = "body.dark-mode {{WRAPPER}} .examhub-toggle-btn[data-state=\"{$state}\"]:active, [data-examhub-theme=\"dark\"] {{WRAPPER}} .examhub-toggle-btn[data-state=\"{$state}\"]:active";

		$this->add_control(
			"examhub_toggle_{$state}_dark_heading",
			array(
				'label'     => $state_label,
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->start_controls_tabs( "examhub_toggle_{$state}_dark_tabs" );

		$this->start_controls_tab(
			"examhub_toggle_{$state}_dark_tab_normal",
			array( 'label' => __( 'عادی', 'examhub' ) )
		);

		$this->add_control(
			"examhub_toggle_{$state}_dark_color",
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $selector => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			"examhub_toggle_{$state}_dark_bg",
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $selector => 'background-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			"examhub_toggle_{$state}_dark_tab_hover",
			array( 'label' => __( 'هاور', 'examhub' ) )
		);

		$this->add_control(
			"examhub_toggle_{$state}_dark_color_hover",
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $selector_hover => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			"examhub_toggle_{$state}_dark_bg_hover",
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $selector_hover => 'background-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			"examhub_toggle_{$state}_dark_tab_active",
			array( 'label' => __( 'فعال', 'examhub' ) )
		);

		$this->add_control(
			"examhub_toggle_{$state}_dark_color_active",
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $selector_active => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			"examhub_toggle_{$state}_dark_bg_active",
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $selector_active => 'background-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();
	}

	/**
	 * Output the toggle button markup shared by every widget with a
	 * "نمایش بیشتر / نمایش کمتر" footer (Download Library, Search & Filter).
	 *
	 * Both states' icon+label are rendered up front; CSS shows only the one
	 * matching the button's current `data-state` attribute, and the front-end
	 * script (examhub-frontend.js) only ever flips that attribute — no markup
	 * is re-rendered when toggling, so no AJAX round-trip is needed to collapse.
	 *
	 * @since 1.1.0
	 * @param array  $settings  Elementor widget settings (from $this->get_settings_for_display()).
	 * @param string $css_class Widget-specific class the front-end script binds its click handler to
	 *                          (e.g. "examhub-library__load-more" or "examhub-search-filter__load-more").
	 * @param int    $max_pages Total number of pages available, from Examhub_Query::get_exams().
	 */
	protected function render_examhub_toggle_button( array $settings, $css_class, $max_pages ) {

		$show_less_enabled = ! empty( $settings['examhub_show_less_enabled'] ) && 'yes' === $settings['examhub_show_less_enabled'];
		$is_single_page    = $max_pages <= 1;
		?>
		<button
			type="button"
			class="<?php echo esc_attr( $css_class ); ?> examhub-toggle-btn examhub-btn<?php echo $is_single_page ? ' examhub-hidden' : ''; ?>"
			data-state="more"
			data-toggle-enabled="<?php echo $show_less_enabled ? '1' : '0'; ?>"
		>
			<span class="examhub-toggle-btn__state examhub-toggle-btn__state--more">
				<?php if ( ! empty( $settings['examhub_show_more_icon']['value'] ) ) : ?>
					<?php \Elementor\Icons_Manager::render_icon( $settings['examhub_show_more_icon'], array( 'aria-hidden' => 'true' ) ); ?>
				<?php endif; ?>
				<span class="examhub-toggle-btn__label"><?php echo esc_html( $settings['examhub_show_more_text'] ); ?></span>
			</span>
			<span class="examhub-toggle-btn__state examhub-toggle-btn__state--less">
				<?php if ( ! empty( $settings['examhub_show_less_icon']['value'] ) ) : ?>
					<?php \Elementor\Icons_Manager::render_icon( $settings['examhub_show_less_icon'], array( 'aria-hidden' => 'true' ) ); ?>
				<?php endif; ?>
				<span class="examhub-toggle-btn__label"><?php echo esc_html( $settings['examhub_show_less_text'] ); ?></span>
			</span>
		</button>
		<?php
	}

}
