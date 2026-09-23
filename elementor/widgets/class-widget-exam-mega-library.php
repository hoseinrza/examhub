<?php

/**
 * "Exam Mega Library" widget — a collapsible two-level tree (e.g. مقطع ▸ پایه)
 * that lazily loads its branches and exam lists over AJAX as the visitor
 * expands them, so even a huge archive stays light on first paint.
 *
 * Only مقطع (the root structure facet) is rendered server-side; expanding a
 * branch calls Examhub_Ajax::mega_branch() — which walks
 * Examhub_Query::STRUCTURE_TAXONOMIES one step at a time via the same
 * parent-link term meta the cascading admin/Elementor controls use — to
 * discover which terms in the next facet actually have exams under the
 * accumulated filter path, and expanding a leaf calls
 * Examhub_Ajax::query_exams() (with that whole path) to render the matching
 * cards inline.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor/widgets
 */
class Examhub_Widget_Exam_Mega_Library extends \Elementor\Widget_Base {

	use Examhub_Card_Style_Controls_Trait;

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return 'examhub_mega_library';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title() {
		return __( 'کتابخانه جامع آزمون‌ها (Exam Mega Library)', 'examhub' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_icon() {
		return 'eicon-nerd';
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
	public function get_script_depends() {
		return array( 'examhub-frontend' );
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
		return array( 'examhub', 'mega', 'library', 'tree', 'درخت', 'آرشیو کامل' );
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
				'label' => __( 'ساختار درخت', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'examhub_tree_intro',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => __( 'این ویجت درختِ «ساختار تحصیلی» را نمایش می‌دهد: شاخه‌های سطح‌بالا، زیرشاخه‌ها، و آزمون‌های هر زیرشاخه به‌صورت تنبل بارگذاری می‌شوند.', 'examhub' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'examhub_count',
			array(
				'label'   => __( 'تعداد آزمون در هر زیرشاخه', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 24,
				'default' => 6,
			)
		);

		$this->add_control(
			'examhub_show_image',
			array(
				'label'        => __( 'نمایش تصویر', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'examhub' ),
				'label_off'    => __( 'خیر', 'examhub' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'examhub_show_stats',
			array(
				'label'        => __( 'نمایش آمار دانلود', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'examhub' ),
				'label_off'    => __( 'خیر', 'examhub' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'examhub_tree_style_section',
			array(
				'label' => __( 'ظاهر درخت', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'examhub_branch_typography',
				'label'    => __( 'تایپوگرافی عنوان شاخه', 'examhub' ),
				'selector' => '{{WRAPPER}} .examhub-mega__branch-toggle',
			)
		);

		$this->start_controls_tabs( 'examhub_branch_color_tabs' );

		$this->start_controls_tab( 'examhub_branch_color_tab_normal', array( 'label' => __( 'عادی', 'examhub' ) ) );
		$this->add_control(
			'examhub_branch_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-mega' => '--examhub-branch-bg: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'examhub_branch_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-mega' => '--examhub-branch-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_branch_color_tab_active', array( 'label' => __( 'باز', 'examhub' ) ) );
		$this->add_control(
			'examhub_branch_bg_active',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-mega' => '--examhub-branch-bg-active: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'examhub_branch_color_active',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-mega' => '--examhub-branch-color-active: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'examhub_branch_radius',
			array(
				'label'      => __( 'گردی گوشه شاخه‌ها', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array(
					'size' => 10,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .examhub-mega' => '--examhub-branch-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->register_examhub_card_style_controls();
	}

	/**
	 * Output the widget on the front-end: just the root branches, expanded
	 * lazily by public/js/examhub-frontend.js via AJAX.
	 *
	 * @since 1.0.0
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();
		$count    = (int) $settings['examhub_count'];
		$display  = $this->get_examhub_display_atts( $settings );

		// Root facet of the academic structure (مقطع) — every term, since it's flat.
		$root_taxonomy = array_key_first( Examhub_Query::STRUCTURE_TAXONOMIES );
		$branches      = get_terms(
			array(
				'taxonomy'   => $root_taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $branches ) || empty( $branches ) ) {
			echo '<p class="examhub-empty">' . esc_html__( 'محتوایی برای نمایش وجود ندارد.', 'examhub' ) . '</p>';
			return;
		}
		?>
		<div class="examhub-mega"
			data-per-page="<?php echo esc_attr( $count ); ?>"
			data-show-image="<?php echo $display['show_image'] ? '1' : '0'; ?>"
			data-show-stats="<?php echo $display['show_stats'] ? '1' : '0'; ?>"
		>
			<?php
			$root_filter_key = Examhub_Query::STRUCTURE_TAXONOMIES[ $root_taxonomy ];
			foreach ( $branches as $branch ) :
				$icon  = get_term_meta( $branch->term_id, 'examhub_icon', true );
				$icon  = $icon ? $icon : '📁';
				$total = Examhub_Query::count_exams_for_path( array( $root_taxonomy => $branch->term_id ) );
				$filters = wp_json_encode( array( $root_filter_key => $branch->term_id ) );
				?>
				<div class="examhub-mega__branch" data-loaded="0">
					<button type="button"
						class="examhub-mega__branch-toggle"
						data-filters="<?php echo esc_attr( $filters ); ?>"
						aria-expanded="false"
					>
						<span class="examhub-mega__branch-icon"><?php echo esc_html( $icon ); ?></span>
						<span class="examhub-mega__branch-name"><?php echo esc_html( $branch->name ); ?></span>
						<span class="examhub-mega__branch-count">
							<?php
							printf(
								/* translators: %s: number of exam files in this branch. */
								esc_html__( '%s فایل', 'examhub' ),
								esc_html( number_format_i18n( $total ) )
							);
							?>
						</span>
						<span class="examhub-mega__branch-arrow" aria-hidden="true">▾</span>
					</button>
					<div class="examhub-mega__children" hidden></div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

}
