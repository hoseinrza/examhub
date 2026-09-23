<?php

/**
 * "Category Showcase" widget — a tile grid of taxonomy terms (e.g. subjects),
 * each showing its icon, name, and how many exam files it contains.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor/widgets
 */
class Examhub_Widget_Category_Showcase extends \Elementor\Widget_Base {

	use Examhub_Card_Style_Controls_Trait;

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return 'examhub_category_showcase';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title() {
		return __( 'ویترین دسته‌بندی‌ها (Category Showcase)', 'examhub' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_icon() {
		return 'eicon-gallery-grid';
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
	public function get_style_depends() {
		return array( 'examhub-cards', 'examhub-dark-mode' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_keywords() {
		return array( 'examhub', 'category', 'دسته بندی', 'رشته', 'درس' );
	}

	/**
	 * Register the widget's content & style controls.
	 *
	 * @since 1.0.0
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'examhub_content_section',
			array(
				'label' => __( 'محتوا', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'examhub_taxonomy',
			array(
				'label'   => __( 'منبع دسته‌بندی', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'examhub_level'     => __( 'مقطع', 'examhub' ),
					'examhub_grade'     => __( 'پایه', 'examhub' ),
					'examhub_field'     => __( 'رشته', 'examhub' ),
					'examhub_subject'   => __( 'درس', 'examhub' ),
					'examhub_exam_type' => __( 'نوع آزمون', 'examhub' ),
					'examhub_year'      => __( 'سال', 'examhub' ),
				),
				'default' => 'examhub_level',
			)
		);

		$this->add_control(
			'examhub_count',
			array(
				'label'   => __( 'تعداد نمایش', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 48,
				'default' => 8,
			)
		);

		$this->add_control(
			'examhub_show_count',
			array(
				'label'        => __( 'نمایش تعداد فایل‌ها', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'examhub' ),
				'label_off'    => __( 'خیر', 'examhub' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'examhub_fallback_icon',
			array(
				'label'       => __( 'آیکون پیش‌فرض (در صورت نبود آیکون اختصاصی)', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '📄',
				'description' => __( 'برای دسته‌بندی‌هایی که آیکون اختصاصی ندارند نمایش داده می‌شود.', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_empty_text',
			array(
				'label'   => __( 'متن حالت خالی', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'دسته‌بندی‌ای یافت نشد.', 'examhub' ),
			)
		);

		$this->end_controls_section();

		$this->register_examhub_grid_style_controls(
			array(
				'section_id'       => 'examhub_style_section',
				'section_label'    => __( 'ظاهر کاشی‌ها', 'examhub' ),
				'wrapper_selector' => '{{WRAPPER}} .examhub-category-grid',
				'item_selector'    => '{{WRAPPER}} .examhub-category-tile',
				'title_selector'   => '{{WRAPPER}} .examhub-category-tile__name',
				'columns_min'      => 2,
				'columns_default'  => '4',
				'tablet_default'   => '3',
				'mobile_default'   => '2',
			)
		);

		$this->start_controls_tabs( 'examhub_tile_color_tabs' );

		$this->start_controls_tab( 'examhub_tile_color_tab_normal', array( 'label' => __( 'عادی', 'examhub' ) ) );

		$this->add_control(
			'examhub_tile_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-category-grid' => '--examhub-tile-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_tile_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-category-grid' => '--examhub-tile-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_tile_color_tab_hover', array( 'label' => __( 'هاور', 'examhub' ) ) );

		$this->add_control(
			'examhub_tile_bg_hover',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-category-grid' => '--examhub-tile-bg-hover: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_tile_color_hover',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-category-grid' => '--examhub-tile-color-hover: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->register_examhub_dark_mode_controls( '{{WRAPPER}} .examhub-category-grid' );

		$this->end_controls_section();
	}

	/**
	 * Output the widget on the front-end.
	 *
	 * @since 1.0.0
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();
		$taxonomy = $settings['examhub_taxonomy'];
		$show_count = 'yes' === $settings['examhub_show_count'];

		$term_args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'number'     => (int) $settings['examhub_count'],
			'orderby'    => 'count',
			'order'      => 'DESC',
		);

		$terms = get_terms( $term_args );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			echo '<p class="examhub-empty">' . esc_html( $settings['examhub_empty_text'] ) . '</p>';
			return;
		}

		$fallback_icon = '' !== $settings['examhub_fallback_icon'] ? $settings['examhub_fallback_icon'] : '📄';

		echo '<div class="examhub-category-grid">';

		foreach ( $terms as $term ) {

			$icon = get_term_meta( $term->term_id, 'examhub_icon', true );
			$icon = $icon ? $icon : $fallback_icon;
			?>
			<div class="examhub-category-tile">
				<span class="examhub-category-tile__icon"><?php echo esc_html( $icon ); ?></span>
				<span class="examhub-category-tile__name"><?php echo esc_html( $term->name ); ?></span>
				<?php if ( $show_count ) : ?>
					<span class="examhub-category-tile__count">
						<?php
						printf(
							/* translators: %s: number of exam files in this category. */
							esc_html__( '%s فایل', 'examhub' ),
							esc_html( number_format_i18n( $term->count ) )
						);
						?>
					</span>
				<?php endif; ?>
			</div>
			<?php
		}

		echo '</div>';
	}

}
