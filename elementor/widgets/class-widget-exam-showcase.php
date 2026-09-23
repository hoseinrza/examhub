<?php

/**
 * "Exam Showcase" widget — a filterable grid of exam cards.
 *
 * The flagship ExamHub widget: lets the page builder pick a fixed
 * level/grade/field/subject/year/term combination (or leave any of them on
 * "همه") and display the matching exams as a card grid, with full control
 * over count, sort order, and which extras (image/stats) appear on each card.
 *
 * The four academic-structure controls (مقطع/پایه/رشته/درس) cascade in the
 * Elementor editor panel — see elementor/js/examhub-elementor-editor.js —
 * pruning each one's options to whatever is valid under the previous choice.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor/widgets
 */
class Examhub_Widget_Exam_Showcase extends \Elementor\Widget_Base {

	use Examhub_Card_Style_Controls_Trait;

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return 'examhub_exam_showcase';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title() {
		return __( 'ویترین آزمون‌ها (Exam Showcase)', 'examhub' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
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
		return array( 'examhub', 'exam', 'آزمون', 'سوالات', 'نمونه سوال' );
	}

	/**
	 * Register the widget's content & style controls.
	 *
	 * @since 1.0.0
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'examhub_filters_section',
			array(
				'label' => __( 'فیلتر آزمون‌ها', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'examhub_level',
			array(
				'label'       => __( 'مقطع', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => array( '' => __( 'همه', 'examhub' ) ) + Examhub_Query::get_term_choices( 'examhub_level' ),
				'default'     => '',
				'description' => __( 'انتخاب مقطع گزینه‌های پایه را در پنل ادیتور محدود می‌کند و به همین ترتیب تا درس.', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_grade',
			array(
				'label'   => __( 'پایه', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => array( '' => __( 'همه', 'examhub' ) ) + Examhub_Query::get_term_choices( 'examhub_grade' ),
				'default' => '',
			)
		);

		$this->add_control(
			'examhub_field',
			array(
				'label'   => __( 'رشته', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => array( '' => __( 'همه', 'examhub' ) ) + Examhub_Query::get_term_choices( 'examhub_field' ),
				'default' => '',
			)
		);

		$this->add_control(
			'examhub_subject',
			array(
				'label'   => __( 'درس', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => array( '' => __( 'همه', 'examhub' ) ) + Examhub_Query::get_term_choices( 'examhub_subject' ),
				'default' => '',
			)
		);

		$this->add_control(
			'examhub_year',
			array(
				'label'   => __( 'سال', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => array( '' => __( 'همه', 'examhub' ) ) + Examhub_Query::get_term_options( 'examhub_year' ),
				'default' => '',
			)
		);

		$this->add_control(
			'examhub_term',
			array(
				'label'   => __( 'نوبت', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => array( '' => __( 'همه', 'examhub' ) ) + Examhub_Query::get_term_options( 'examhub_term' ),
				'default' => '',
			)
		);

		$this->add_control(
			'examhub_type',
			array(
				'label'   => __( 'نوع آزمون', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => array( '' => __( 'همه', 'examhub' ) ) + Examhub_Query::get_term_options( 'examhub_exam_type' ),
				'default' => '',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'examhub_display_section',
			array(
				'label' => __( 'نمایش', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
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
			'examhub_orderby',
			array(
				'label'   => __( 'مرتب‌سازی', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'latest'  => __( 'جدیدترین', 'examhub' ),
					'popular' => __( 'پربازدیدترین', 'examhub' ),
					'random'  => __( 'تصادفی', 'examhub' ),
					'title'   => __( 'الفبایی', 'examhub' ),
				),
				'default' => 'latest',
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

		$this->register_examhub_card_style_controls();
	}

	/**
	 * Output the widget on the front-end.
	 *
	 * @since 1.0.0
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();

		$result = Examhub_Query::get_exams(
			array(
				'level'          => $settings['examhub_level'],
				'grade'          => $settings['examhub_grade'],
				'field'          => $settings['examhub_field'],
				'subject'        => $settings['examhub_subject'],
				'year'           => $settings['examhub_year'],
				'term'           => $settings['examhub_term'],
				'exam_type'      => $settings['examhub_type'],
				'orderby'        => $settings['examhub_orderby'],
				'posts_per_page' => (int) $settings['examhub_count'],
			)
		);

		echo wp_kses_post(
			examhub_render_exam_grid( $result['items'], $this->get_examhub_display_atts( $settings ) )
		);
	}

}
