<?php

/**
 * "Download Library" widget — full archive with a filter sidebar (a dropdown
 * per taxonomy + a search box) and AJAX "load more" pagination, no page reloads.
 *
 * Each filter dropdown can be curated from the Elementor editor: pick exactly
 * which terms appear, or leave it empty to list them all. Renders its initial
 * page server-side (so the content is there for SEO and for visitors with JS
 * disabled) and hands the rest off to public/js/examhub-frontend.js, which talks
 * to the shared `examhub_query_exams` AJAX endpoint (Examhub_Ajax::query_exams()).
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor/widgets
 */
class Examhub_Widget_Download_Library extends \Elementor\Widget_Base {

	use Examhub_Card_Style_Controls_Trait;

	/**
	 * Maps each sidebar filter key to its enable-switch control and taxonomy.
	 *
	 * Display labels live in get_filter_label() rather than here so the i18n
	 * string-extraction tooling can find them (a constant can't call __()).
	 *
	 * @since 1.0.0
	 * @var   array<string,array>
	 */
	const FILTERS = array(
		'level'     => array(
			'switch'   => 'examhub_show_level',
			'terms'    => 'examhub_terms_level',
			'taxonomy' => 'examhub_level',
		),
		'grade'     => array(
			'switch'   => 'examhub_show_grade',
			'terms'    => 'examhub_terms_grade',
			'taxonomy' => 'examhub_grade',
		),
		'field'     => array(
			'switch'   => 'examhub_show_field',
			'terms'    => 'examhub_terms_field',
			'taxonomy' => 'examhub_field',
		),
		'subject'   => array(
			'switch'   => 'examhub_show_subject',
			'terms'    => 'examhub_terms_subject',
			'taxonomy' => 'examhub_subject',
		),
		'year'      => array(
			'switch'   => 'examhub_show_year',
			'terms'    => 'examhub_terms_year',
			'taxonomy' => 'examhub_year',
		),
		'term'      => array(
			'switch'   => 'examhub_show_term',
			'terms'    => 'examhub_terms_term',
			'taxonomy' => 'examhub_term',
		),
		'exam_type' => array(
			'switch'   => 'examhub_show_type',
			'terms'    => 'examhub_terms_type',
			'taxonomy' => 'examhub_exam_type',
		),
	);

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return 'examhub_download_library';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title() {
		return __( 'کتابخانه دانلود (Download Library)', 'examhub' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_icon() {
		return 'eicon-download-button';
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
		return array( 'examhub', 'library', 'دانلود', 'آرشیو', 'بانک سوال' );
	}

	/**
	 * Translate a sidebar filter key to its display label.
	 *
	 * Kept as a literal switch (rather than storing translated strings in the
	 * FILTERS constant) so the i18n string-extraction tooling can find each one.
	 *
	 * @since 1.0.0
	 * @param  string $key One of the FILTERS keys.
	 * @return string
	 */
	private function get_filter_label( $key ) {

		switch ( $key ) {
			case 'level':
				return __( 'مقطع', 'examhub' );
			case 'grade':
				return __( 'پایه', 'examhub' );
			case 'field':
				return __( 'رشته', 'examhub' );
			case 'subject':
				return __( 'درس', 'examhub' );
			case 'year':
				return __( 'سال', 'examhub' );
			case 'term':
				return __( 'نوبت', 'examhub' );
			case 'exam_type':
				return __( 'نوع آزمون', 'examhub' );
			default:
				return '';
		}
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

		foreach ( self::FILTERS as $key => $filter ) {

			$this->add_control(
				$filter['switch'],
				array(
					/* translators: %s: filter label, e.g. "درس". */
					'label'        => sprintf( __( 'نمایش فیلتر «%s»', 'examhub' ), $this->get_filter_label( $key ) ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => __( 'بله', 'examhub' ),
					'label_off'    => __( 'خیر', 'examhub' ),
					'return_value' => 'yes',
					'default'      => in_array( $key, array( 'level', 'year', 'exam_type' ), true ) ? 'yes' : '',
				)
			);

			$is_structure_facet = array_key_exists( $filter['taxonomy'], Examhub_Query::STRUCTURE_TAXONOMIES );

			$this->add_control(
				$filter['terms'],
				array(
					/* translators: %s: filter label, e.g. "درس". */
					'label'       => sprintf( __( 'دسته‌های «%s»', 'examhub' ), $this->get_filter_label( $key ) ),
					'type'        => \Elementor\Controls_Manager::SELECT2,
					'multiple'    => true,
					'label_block' => true,
					'options'     => $is_structure_facet ? Examhub_Query::get_term_choices( $filter['taxonomy'] ) : Examhub_Query::get_term_options( $filter['taxonomy'] ),
					'description' => __( 'خالی بگذارید تا همه‌ی دسته‌ها نمایش داده شوند.', 'examhub' ),
					'condition'   => array( $filter['switch'] => 'yes' ),
				)
			);
		}

		$this->add_control(
			'examhub_show_search',
			array(
				'label'        => __( 'نمایش جعبه جستجو', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'examhub' ),
				'label_off'    => __( 'خیر', 'examhub' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'examhub_search_placeholder',
			array(
				'label'     => __( 'متن راهنمای جستجو', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'جستجوی نام آزمون…', 'examhub' ),
				'condition' => array( 'examhub_show_search' => 'yes' ),
			)
		);

		$this->add_control(
			'examhub_per_page',
			array(
				'label'   => __( 'تعداد در هر بارگذاری', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 48,
				'default' => 6,
			)
		);

		$this->register_examhub_toggle_button_content_controls();

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
			'examhub_sidebar_style_section',
			array(
				'label' => __( 'ظاهر سایدبار', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'examhub_sidebar_position',
			array(
				'label'        => __( 'محل سایدبار', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SELECT,
				'options'      => array(
					'start' => __( 'ابتدا (سمت راست در RTL)', 'examhub' ),
					'end'   => __( 'انتها (سمت چپ در RTL)', 'examhub' ),
				),
				'default'      => 'start',
				'prefix_class' => 'examhub-sidebar-',
			)
		);

		$this->add_responsive_control(
			'examhub_sidebar_width',
			array(
				'label'      => __( 'عرض سایدبار', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 160,
						'max' => 480,
					),
				),
				'default'    => array(
					'size' => 240,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .examhub-library' => '--examhub-sidebar-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'examhub_sidebar_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-library' => '--examhub-sidebar-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sidebar_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-library' => '--examhub-sidebar-color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'examhub_sidebar_radius',
			array(
				'label'      => __( 'گردی گوشه سایدبار', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array(
					'size' => 12,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .examhub-library' => '--examhub-sidebar-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->register_examhub_toggle_button_style_controls( '.examhub-library__footer' );

		$this->register_examhub_card_style_controls();
	}

	/**
	 * Build the list of taxonomy filters to render in the sidebar, each with
	 * its enabled state and the curated (or full) set of term options.
	 *
	 * @since 1.0.0
	 * @param  array $settings Elementor widget settings.
	 * @return array<int,array{key:string,label:string,options:array<string,string>}>
	 */
	private function get_active_filters( array $settings ) {

		$active = array();

		foreach ( self::FILTERS as $key => $filter ) {

			if ( empty( $settings[ $filter['switch'] ] ) || 'yes' !== $settings[ $filter['switch'] ] ) {
				continue;
			}

			$is_structure_facet = array_key_exists( $filter['taxonomy'], Examhub_Query::STRUCTURE_TAXONOMIES );
			$all      = $is_structure_facet ? Examhub_Query::get_term_choices( $filter['taxonomy'] ) : Examhub_Query::get_term_options( $filter['taxonomy'] );
			$selected = isset( $settings[ $filter['terms'] ] ) ? (array) $settings[ $filter['terms'] ] : array();
			$selected = array_filter( $selected );

			$options = ! empty( $selected ) ? array_intersect_key( $all, array_flip( $selected ) ) : $all;

			if ( empty( $options ) ) {
				continue;
			}

			$active[] = array(
				'key'     => $key,
				'label'   => $this->get_filter_label( $key ),
				'options' => $options,
			);
		}

		return $active;
	}

	/**
	 * Output the widget on the front-end: the filter sidebar (a dropdown per
	 * enabled taxonomy + optional search box) beside the first page of results
	 * (rendered server-side) and the "load more" trigger.
	 *
	 * @since 1.0.0
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();
		$per_page = max( 1, (int) $settings['examhub_per_page'] );
		$display  = $this->get_examhub_display_atts( $settings );

		$filters    = $this->get_active_filters( $settings );
		$has_search = ! empty( $settings['examhub_show_search'] ) && 'yes' === $settings['examhub_show_search'];

		$result = Examhub_Query::get_exams(
			array(
				'posts_per_page' => $per_page,
				'paged'          => 1,
			)
		);
		?>
		<div class="examhub-library"
			data-per-page="<?php echo esc_attr( $per_page ); ?>"
			data-show-image="<?php echo $display['show_image'] ? '1' : '0'; ?>"
			data-show-stats="<?php echo $display['show_stats'] ? '1' : '0'; ?>"
			data-paged="1"
			data-max-pages="<?php echo esc_attr( $result['max_num_pages'] ); ?>"
		>
			<div class="examhub-library__layout">

				<?php if ( ! empty( $filters ) || $has_search ) : ?>
					<aside class="examhub-library__sidebar">

						<?php foreach ( $filters as $filter ) : ?>
							<div class="examhub-library__filter">
								<label class="examhub-library__filter-label"><?php echo esc_html( $filter['label'] ); ?></label>
								<select class="examhub-library__select" data-filter="<?php echo esc_attr( $filter['key'] ); ?>">
									<option value=""><?php esc_html_e( 'همه', 'examhub' ); ?></option>
									<?php foreach ( $filter['options'] as $slug => $name ) : ?>
										<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endforeach; ?>

						<?php if ( $has_search ) : ?>
							<div class="examhub-library__filter examhub-library__filter--search">
								<label class="examhub-library__filter-label"><?php esc_html_e( 'جستجو', 'examhub' ); ?></label>
								<input type="search" class="examhub-library__search" placeholder="<?php echo esc_attr( $settings['examhub_search_placeholder'] ); ?>" />
							</div>
						<?php endif; ?>

					</aside>
				<?php endif; ?>

				<div class="examhub-library__main">
					<div class="examhub-library__grid">
						<?php echo wp_kses_post( examhub_render_exam_grid( $result['items'], $display ) ); ?>
					</div>

					<div class="examhub-library__footer">
						<?php $this->render_examhub_toggle_button( $settings, 'examhub-library__load-more', $result['max_num_pages'] ); ?>
					</div>
				</div>

			</div>
		</div>
		<?php
	}

}
