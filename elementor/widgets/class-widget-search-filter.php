<?php

/**
 * "Search & Filter" widget — a flat, non-hierarchical filter bar (مقطع/پایه/
 * رشته/درس/سال selects + a search box) that re-queries exams via AJAX and
 * renders results inline, without ever leaving the page.
 *
 * As of 1.4.0 every facet is fully independent: each <select> is always
 * enabled and always lists its taxonomy's complete term set on render —
 * there is no parent/child relationship between مقطع/پایه/رشته/درس any
 * more (compare to the cascading model this widget used through 1.3.x).
 * Any combination of facets the admin enables can be combined freely; the
 * AJAX query (Examhub_Ajax::query_exams() -> Examhub_Query::get_exams())
 * intersects (AND) or unions (OR) whichever ones have a value, per the
 * widget's "نحوه ترکیب فیلترها" control. Each facet can also be switched to
 * a multi-select, sending an array of values for that one taxonomy (still
 * OR'd together within that single facet either way — AND/OR only governs
 * the relation *between* different facets).
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor/widgets
 */
class Examhub_Widget_Search_Filter extends \Elementor\Widget_Base {

	use Examhub_Card_Style_Controls_Trait;
	use Examhub_Dark_Mode_Settings_Trait;

	/**
	 * Maps each optional filter field to its switch control and taxonomy.
	 *
	 * Display labels are intentionally not stored here — they come from
	 * get_field_label() so the i18n string-extraction tooling can find every
	 * translatable string (a constant can't call __()).
	 *
	 * @since 1.0.0
	 * @var   array<string,array>
	 */
	const FIELDS = array(
		'level'     => array(
			'switch'   => 'examhub_show_level',
			'taxonomy' => 'examhub_level',
		),
		'grade'     => array(
			'switch'   => 'examhub_show_grade',
			'taxonomy' => 'examhub_grade',
		),
		'field'     => array(
			'switch'   => 'examhub_show_field',
			'taxonomy' => 'examhub_field',
		),
		'subject'   => array(
			'switch'   => 'examhub_show_subject',
			'taxonomy' => 'examhub_subject',
		),
		'year'      => array(
			'switch'   => 'examhub_show_year',
			'taxonomy' => 'examhub_year',
		),
		'term'      => array(
			'switch'   => 'examhub_show_term',
			'taxonomy' => 'examhub_term',
		),
		'exam_type' => array(
			'switch'   => 'examhub_show_type',
			'taxonomy' => 'examhub_exam_type',
		),
	);

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return 'examhub_search_filter';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title() {
		return __( 'جستجو و فیلتر (Search & Filter)', 'examhub' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_icon() {
		return 'eicon-site-search';
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
		return array( 'examhub', 'search', 'filter', 'جستجو', 'فیلتر' );
	}

	/**
	 * Translate a filter field key to its display label.
	 *
	 * @since 1.0.0
	 * @param  string $key One of the FIELDS keys.
	 * @return string
	 */
	private function get_field_label( $key ) {

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
	 * The label actually shown on a facet's <select> — the admin's
	 * "examhub_label_{$key}" override if set, otherwise get_field_label()'s
	 * default. Used both for the placeholder option text and the chip label.
	 *
	 * @since 1.5.0
	 * @param  string $key      One of the FIELDS keys.
	 * @param  array  $settings Elementor widget settings.
	 * @return string
	 */
	private function get_filter_label( $key, array $settings ) {

		$override = trim( (string) ( $settings[ "examhub_label_{$key}" ] ?? '' ) );

		return '' !== $override ? $override : $this->get_field_label( $key );
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
				'label' => __( 'فیلدهای فیلتر', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		foreach ( self::FIELDS as $key => $field ) {

			$this->add_control(
				$field['switch'],
				array(
					/* translators: %s: filter field label, e.g. "پایه". */
					'label'        => sprintf( __( 'نمایش فیلتر «%s»', 'examhub' ), $this->get_field_label( $key ) ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => __( 'بله', 'examhub' ),
					'label_off'    => __( 'خیر', 'examhub' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				)
			);

			$this->add_control(
				"examhub_multiselect_{$key}",
				array(
					/* translators: %s: filter field label, e.g. "پایه". */
					'label'        => sprintf( __( 'چندانتخابی «%s»', 'examhub' ), $this->get_field_label( $key ) ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => __( 'بله', 'examhub' ),
					'label_off'    => __( 'خیر', 'examhub' ),
					'return_value' => 'yes',
					'default'      => '',
					'condition'    => array( $field['switch'] => 'yes' ),
				)
			);

			$this->add_control(
				"examhub_label_{$key}",
				array(
					/* translators: %s: filter field label, e.g. "پایه". */
					'label'       => sprintf( __( 'برچسب «%s»', 'examhub' ), $this->get_field_label( $key ) ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'placeholder' => $this->get_field_label( $key ),
					'condition'   => array( $field['switch'] => 'yes' ),
				)
			);

			$this->add_control(
				"examhub_default_{$key}",
				array(
					/* translators: %s: filter field label, e.g. "پایه". */
					'label'       => sprintf( __( 'مقدار پیش‌فرض «%s»', 'examhub' ), $this->get_field_label( $key ) ),
					'type'        => \Elementor\Controls_Manager::SELECT2,
					'label_block' => true,
					'options'     => array_key_exists( $field['taxonomy'], Examhub_Query::STRUCTURE_TAXONOMIES )
						? Examhub_Query::get_term_choices( $field['taxonomy'] )
						: Examhub_Query::get_term_options( $field['taxonomy'] ),
					'condition'   => array( $field['switch'] => 'yes' ),
					'description' => __( 'این مقدار در بار اول بارگذاری صفحه از پیش انتخاب می‌شود؛ کاربر همچنان می‌تواند آن را تغییر دهد.', 'examhub' ),
				)
			);
		}

		$this->add_control(
			'examhub_match_type',
			array(
				'label'       => __( 'نحوه ترکیب فیلترها', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => array(
					'AND' => __( 'AND — همه فیلترهای انتخابی باید هم‌زمان صدق کنند', 'examhub' ),
					'OR'  => __( 'OR — کافی است یکی از فیلترها صدق کند (حالت پیشرفته)', 'examhub' ),
				),
				'default'     => 'AND',
				'separator'   => 'before',
				'description' => __( 'این گزینه فقط رابطه بین فیلدهای مختلف را تعیین می‌کند؛ مقادیر متعدد درون یک فیلد چندانتخابی همیشه با OR ترکیب می‌شوند.', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_show_chips',
			array(
				'label'        => __( 'نمایش ردیف فیلترهای فعال (Chips)', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'examhub' ),
				'label_off'    => __( 'خیر', 'examhub' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'examhub_show_result_count',
			array(
				'label'        => __( 'نمایش تعداد نتایج', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'examhub' ),
				'label_off'    => __( 'خیر', 'examhub' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

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
				'label'       => __( 'متن راهنمای جستجو', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'جستجوی نام آزمون…', 'examhub' ),
				'description' => __( 'فیلتر جستجو نیز مانند بقیه فیلدها فقط با کلیک «اعمال فیلتر» یا Enter اعمال می‌شود.', 'examhub' ),
				'condition'   => array( 'examhub_show_search' => 'yes' ),
			)
		);

		$this->add_control(
			'examhub_count',
			array(
				'label'       => __( 'تعداد نتایج (نمایش اول و هر بار «نمایش بیشتر»)', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 48,
				'default'     => 12,
				'description' => __( 'وقتی نتایج بیشتری وجود داشته باشد، دکمه «نمایش بیشتر» همین تعداد را هر بار اضافه می‌کند.', 'examhub' ),
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
			'examhub_actions_content_section',
			array(
				'label' => __( 'دکمه‌های اعمال / حذف فیلتر', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'examhub_apply_text',
			array(
				'label'       => __( 'متن دکمه «اعمال فیلتر»', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'اعمال فیلتر', 'examhub' ),
				'description' => __( 'این دکمه روی دسکتاپ و موبایل یکسان است — تنها نقطه‌ای که نتایج را واکشی می‌کند.', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_reset_text',
			array(
				'label'   => __( 'متن دکمه «حذف فیلترها»', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'حذف فیلترها', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_ajax_enabled',
			array(
				'label'        => __( 'فیلتر با AJAX (بدون رفرش صفحه)', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'examhub' ),
				'label_off'    => __( 'خیر', 'examhub' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'این ویجت فقط با AJAX کار می‌کند؛ این کنترل برای حالت‌های آینده رزرو شده و در حال حاضر خاموش کردن آن رفتاری تغییر نمی‌دهد.', 'examhub' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'examhub_popup_content_section',
			array(
				'label' => __( 'پاپ‌آپ فیلتر موبایل', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'examhub_layout_mode',
			array(
				'label'       => __( 'حالت چیدمان (Layout Mode)', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => array(
					'hybrid'        => __( 'ترکیبی — دسکتاپ درون‌خطی، موبایل پاپ‌آپ (پیش‌فرض)', 'examhub' ),
					'inline_always' => __( 'همیشه درون‌خطی — حتی در موبایل بدون پاپ‌آپ', 'examhub' ),
					'popup_always'  => __( 'همیشه پاپ‌آپ — حتی در دسکتاپ پشت دکمه «فیلترها»', 'examhub' ),
				),
				'default'     => 'hybrid',
				'description' => __( 'این کنترل به‌تنهایی تعیین می‌کند فیلترها در چه دستگاهی درون‌خطی و در چه دستگاهی پشت پاپ‌آپ باشند؛ بدون وابستگی به حالت تیره یا استایل‌های سراسری.', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_filters_button_text',
			array(
				'label'     => __( 'متن دکمه «فیلترها»', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'فیلترها', 'examhub' ),
				'condition' => array( 'examhub_layout_mode' => array( 'hybrid', 'popup_always' ) ),
			)
		);

		$this->add_control(
			'examhub_filters_button_icon',
			array(
				'label'     => __( 'آیکون دکمه «فیلترها»', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'eicon-filter',
					'library' => 'eicons',
				),
				'condition' => array( 'examhub_layout_mode' => array( 'hybrid', 'popup_always' ) ),
			)
		);

		$this->add_control(
			'examhub_popup_title',
			array(
				'label'     => __( 'عنوان پاپ‌آپ', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'فیلترها', 'examhub' ),
				'condition' => array( 'examhub_layout_mode' => array( 'hybrid', 'popup_always' ) ),
			)
		);

		$this->add_control(
			'examhub_popup_animation',
			array(
				'label'     => __( 'نوع پاپ‌آپ', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array(
					'slide-up' => __( 'ورقه پایین (Bottom Sheet)', 'examhub' ),
					'fade'     => __( 'مودال مرکزی (Center Modal)', 'examhub' ),
				),
				'default'   => 'slide-up',
				'condition' => array( 'examhub_layout_mode' => array( 'hybrid', 'popup_always' ) ),
			)
		);

		$this->add_control(
			'examhub_popup_field_style',
			array(
				'label'       => __( 'چیدمان فیلدها در پاپ‌آپ', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => array(
					'flat'      => __( 'ساده — همه فیلدها زیر هم (پیش‌فرض)', 'examhub' ),
					'accordion' => __( 'آکاردئونی — هر فیلد در یک بخش جمع‌شونده', 'examhub' ),
				),
				'default'     => 'flat',
				'condition'   => array( 'examhub_layout_mode' => array( 'hybrid', 'popup_always' ) ),
				'description' => __( 'این گزینه فقط روی پاپ‌آپ موبایل اثر دارد؛ نوار دسکتاپ همیشه درون‌خطی و ساده می‌ماند.', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_show_featured_majors',
			array(
				'label'        => __( 'نمایش رشته‌های پیشنهادی', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'examhub' ),
				'label_off'    => __( 'خیر', 'examhub' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'این ردیف همیشه در صفحهٔ اصلی نمایش داده می‌شود — مستقل از پاپ‌آپ و هرگز داخل آن نیست (طبق قانون «Popup Isolation»).', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_featured_majors_position',
			array(
				'label'     => __( 'محل قرارگیری رشته‌های پیشنهادی', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array(
					'above' => __( 'بالای دکمه/نوار فیلتر', 'examhub' ),
					'below' => __( 'پایین دکمه/نوار فیلتر (بالای نتایج)', 'examhub' ),
				),
				'default'   => 'above',
				'condition' => array( 'examhub_show_featured_majors' => 'yes' ),
			)
		);

		for ( $i = 1; $i <= 4; $i++ ) {

			$this->add_control(
				"examhub_featured_major_{$i}",
				array(
					/* translators: %d: featured major slot number, 1-4. */
					'label'       => sprintf( __( 'رشته پیشنهادی %d', 'examhub' ), $i ),
					'type'        => \Elementor\Controls_Manager::SELECT2,
					'label_block' => true,
					'options'     => Examhub_Query::get_term_choices( 'examhub_field' ),
					'condition'   => array( 'examhub_show_featured_majors' => 'yes' ),
				)
			);
		}

		$this->end_controls_section();

		$this->start_controls_section(
			'examhub_bar_style_section',
			array(
				'label' => __( 'ظاهر نوار جستجو و فیلتر', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'examhub_bar_gap',
			array(
				'label'      => __( 'فاصله بین فیلدها', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array(
					'size' => 10,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-bar-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'examhub_bar_align',
			array(
				'label'     => __( 'چیدمان نوار', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array(
					'flex-start'    => __( 'ابتدا', 'examhub' ),
					'center'        => __( 'وسط', 'examhub' ),
					'flex-end'      => __( 'انتها', 'examhub' ),
					'space-between' => __( 'بین فیلدها', 'examhub' ),
				),
				'default'   => 'flex-start',
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-bar-justify: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_field_heading',
			array(
				'label'     => __( 'فیلدهای ورودی (انتخاب‌گرها و جستجو)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'examhub_field_radius',
			array(
				'label'      => __( 'گردی گوشه‌ها', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-field-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'examhub_field_color_tabs' );

		$this->start_controls_tab( 'examhub_field_color_tab_normal', array( 'label' => __( 'عادی', 'examhub' ) ) );

		$this->add_control(
			'examhub_field_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-field-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_field_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-field-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_field_border',
			array(
				'label'     => __( 'رنگ حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-field-border: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_field_color_tab_focus', array( 'label' => __( 'فوکوس', 'examhub' ) ) );

		$this->add_control(
			'examhub_field_border_focus',
			array(
				'label'     => __( 'رنگ حاشیه (فوکوس)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-field-border-focus: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'examhub_apply_heading',
			array(
				'label'     => __( 'دکمه اعمال فیلتر', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->start_controls_tabs( 'examhub_apply_color_tabs' );

		$this->start_controls_tab( 'examhub_apply_color_tab_normal', array( 'label' => __( 'عادی', 'examhub' ) ) );

		$this->add_control(
			'examhub_submit_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-apply-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_submit_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-apply-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_apply_color_tab_hover', array( 'label' => __( 'هاور', 'examhub' ) ) );

		$this->add_control(
			'examhub_submit_bg_hover',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-apply-bg-hover: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_submit_color_hover',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-apply-color-hover: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'examhub_reset_heading',
			array(
				'label'     => __( 'دکمه حذف فیلترها', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->start_controls_tabs( 'examhub_reset_color_tabs' );

		$this->start_controls_tab( 'examhub_reset_color_tab_normal', array( 'label' => __( 'عادی', 'examhub' ) ) );

		$this->add_control(
			'examhub_reset_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-reset-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_reset_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-reset-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_reset_border_color',
			array(
				'label'     => __( 'رنگ حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-reset-border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_reset_color_tab_hover', array( 'label' => __( 'هاور', 'examhub' ) ) );

		$this->add_control(
			'examhub_reset_bg_hover',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-reset-bg-hover: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_reset_color_hover',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-reset-color-hover: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_reset_border_color_hover',
			array(
				'label'     => __( 'رنگ حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-reset-border-color-hover: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_reset_color_tab_active', array( 'label' => __( 'فعال', 'examhub' ) ) );

		$this->add_control(
			'examhub_reset_bg_active',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-reset-bg-active: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_reset_color_active',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-reset-color-active: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_reset_border_color_active',
			array(
				'label'     => __( 'رنگ حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-reset-border-color-active: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'examhub_reset_radius',
			array(
				'label'      => __( 'گردی گوشه‌ها', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-reset-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'examhub_reset_typography',
				'selector' => '{{WRAPPER}} .examhub-search-filter__reset',
			)
		);

		$this->add_responsive_control(
			'examhub_reset_min_height',
			array(
				'label'       => __( 'حداقل ارتفاع (لمس‌پذیری در موبایل)', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 32, 'max' => 64 ) ),
				'mobile_default' => array(
					'size' => 44,
					'unit' => 'px',
				),
				'selectors'   => array(
					'{{WRAPPER}} .examhub-search-filter__bar' => '--examhub-sf-reset-min-height: {{SIZE}}{{UNIT}};',
				),
				'description' => __( 'برای دسترس‌پذیری لمسی در موبایل، حداقل ۴۴ پیکسل پیشنهاد می‌شود.', 'examhub' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'examhub_popup_style_section',
			array(
				'label'     => __( 'ظاهر پاپ‌آپ', 'examhub' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array( 'examhub_layout_mode' => array( 'hybrid', 'popup_always' ) ),
			)
		);

		$this->add_control(
			'examhub_overlay_color',
			array(
				'label'     => __( 'رنگ پوشش پس‌زمینه (Overlay)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter' => '--examhub-sf-overlay-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_panel_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه پاپ‌آپ', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .examhub-search-filter' => '--examhub-sf-panel-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_active_filter_heading',
			array(
				'label'     => __( 'وضعیت فیلتر فعال (Active Filter)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_active_filter_bg',
			array(
				'label'       => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::COLOR,
				'description' => __( 'پس‌زمینه فیلدهای انتخاب‌شده و چیپ‌های فیلتر فعال.', 'examhub' ),
				'selectors'   => array(
					'{{WRAPPER}} .examhub-search-filter' => '--examhub-sf-active-color-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_active_filter_color',
			array(
				'label'       => __( 'رنگ حاشیه', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::COLOR,
				'description' => __( 'رنگ حاشیه فیلدهایی که مقدار انتخاب‌شده دارند و دکمه‌های رشته پیشنهادی فعال.', 'examhub' ),
				'selectors'   => array(
					'{{WRAPPER}} .examhub-search-filter' => '--examhub-sf-active-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'examhub_active_filter_text',
			array(
				'label'       => __( 'رنگ متن', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::COLOR,
				'description' => __( 'رنگ متن فیلدها و چیپ‌های فیلتر فعال.', 'examhub' ),
				'selectors'   => array(
					'{{WRAPPER}} .examhub-search-filter' => '--examhub-sf-active-text: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'examhub_active_filter_radius',
			array(
				'label'      => __( 'گردی گوشه چیپ‌ها', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .examhub-search-filter' => '--examhub-sf-active-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'examhub_active_filter_typography',
				'selector' => '{{WRAPPER}} .examhub-search-filter__chip',
			)
		);

		$this->end_controls_section();

		$this->register_examhub_dark_mode_settings_section( '{{WRAPPER}} .examhub-search-filter' );

		$this->register_examhub_sf_dark_mode_style_controls();

		$this->register_examhub_toggle_button_style_controls( '.examhub-search-filter__footer' );

		$this->register_examhub_card_style_controls();
	}

	/**
	 * Register the bespoke dark-mode Style controls specific to this widget's
	 * filters bar/dropdowns, chips, suggested subjects, popup, and mobile
	 * bottom-sheet — none of which exist in the generic card trait, since no
	 * other ExamHub widget has a popup or a suggested-subjects carousel.
	 *
	 * Every control here writes a NEW CSS variable (the `--examhub-sf-dark-*`
	 * namespace); none of them touch the existing `--examhub-sf-*` vars set
	 * by this widget's "ظاهر نوار جستجو و فیلتر"/"ظاهر پاپ‌آپ" sections above,
	 * which keep applying identically in both light and dark mode as before.
	 * In examhub-dark-mode.css, each new var sits as the most-specific tier
	 * in its fallback chain, ahead of the existing `--examhub-sf-*` value.
	 *
	 * @since 2.0.0
	 */
	private function register_examhub_sf_dark_mode_style_controls() {

		$wrapper = '{{WRAPPER}} .examhub-search-filter';
		$dark    = "body.dark-mode {$wrapper}, [data-examhub-theme=\"dark\"] {$wrapper}";

		$this->start_controls_section(
			'examhub_sf_dark_mode_style_section',
			array(
				'label' => __( 'حالت تیره — فیلترها، پاپ‌آپ و موبایل', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'examhub_sf_dark_bar_heading',
			array(
				'label' => __( 'نوار فیلتر و ورودی‌ها (Dropdowns)', 'examhub' ),
				'type'  => \Elementor\Controls_Manager::HEADING,
			)
		);

		$this->start_controls_tabs( 'examhub_sf_dark_field_tabs' );

		$this->start_controls_tab( 'examhub_sf_dark_field_tab_normal', array( 'label' => __( 'عادی', 'examhub' ) ) );

		$this->add_control(
			'examhub_sf_dark_field_bg',
			array(
				'label'     => __( 'پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-field-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_field_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-field-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_field_border',
			array(
				'label'     => __( 'حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-field-border: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_sf_dark_field_tab_hover', array( 'label' => __( 'هاور', 'examhub' ) ) );

		$this->add_control(
			'examhub_sf_dark_field_bg_hover',
			array(
				'label'     => __( 'پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-field-bg-hover: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_sf_dark_field_tab_active', array( 'label' => __( 'فعال', 'examhub' ) ) );

		$this->add_control(
			'examhub_sf_dark_field_border_active',
			array(
				'label'     => __( 'حاشیه فعال', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-field-border-active: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_field_bg_active',
			array(
				'label'     => __( 'پس‌زمینه فعال', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-field-bg-active: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_field_color_active',
			array(
				'label'     => __( 'رنگ متن فعال', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-field-color-active: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'examhub_sf_dark_chips_heading',
			array(
				'label'     => __( 'فیلترهای فعال (Chips)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_sf_dark_chip_bg',
			array(
				'label'     => __( 'پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-chip-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_chip_active_bg',
			array(
				'label'     => __( 'پس‌زمینه فعال', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-chip-active-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_chip_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-chip-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_active_color',
			array(
				'label'     => __( 'رنگ وضعیت فعال (Active State)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-active-color: {{VALUE}};' ),
				'description' => __( 'رنگ متن برای فیلتر‌های فعال، حاشیه‌های فعال و انتخاب‌های فعال', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_chip_border',
			array(
				'label'     => __( 'حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-chip-border: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'examhub_sf_dark_chip_radius',
			array(
				'label'      => __( 'گردی گوشه‌ها', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( $dark => '--examhub-sf-dark-chip-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'examhub_sf_dark_chip_typography',
				'selector' => "body.dark-mode {$wrapper} .examhub-search-filter__chip, [data-examhub-theme=\"dark\"] {$wrapper} .examhub-search-filter__chip",
			)
		);

		$this->add_control(
			'examhub_sf_dark_majors_heading',
			array(
				'label'     => __( 'رشته‌های پیشنهادی (Suggested Subjects)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_sf_dark_major_bg',
			array(
				'label'     => __( 'پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-major-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_major_active_bg',
			array(
				'label'     => __( 'پس‌زمینه فعال', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-major-active-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_major_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-major-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_major_hover',
			array(
				'label'     => __( 'رنگ هاور', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-major-hover: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_major_border',
			array(
				'label'     => __( 'حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-major-border: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'examhub_sf_dark_major_radius',
			array(
				'label'      => __( 'گردی گوشه‌ها', 'examhub' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( $dark => '--examhub-sf-dark-major-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'examhub_sf_dark_major_typography',
				'selector' => "body.dark-mode {$wrapper} .examhub-search-filter__featured-major, [data-examhub-theme=\"dark\"] {$wrapper} .examhub-search-filter__featured-major",
			)
		);

		$this->add_control(
			'examhub_sf_dark_reset_heading',
			array(
				'label'     => __( 'دکمه حذف فیلترها (حالت تیره)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->start_controls_tabs( 'examhub_sf_dark_reset_tabs' );

		$this->start_controls_tab( 'examhub_sf_dark_reset_tab_normal', array( 'label' => __( 'عادی', 'examhub' ) ) );

		$this->add_control(
			'examhub_sf_dark_reset_bg',
			array(
				'label'     => __( 'پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-reset-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_reset_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-reset-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_reset_border',
			array(
				'label'     => __( 'رنگ حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-reset-border: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_sf_dark_reset_tab_hover', array( 'label' => __( 'هاور', 'examhub' ) ) );

		$this->add_control(
			'examhub_sf_dark_reset_bg_hover',
			array(
				'label'     => __( 'پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-reset-bg-hover: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_reset_color_hover',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-reset-color-hover: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_reset_border_hover',
			array(
				'label'     => __( 'رنگ حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-reset-border-hover: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_sf_dark_reset_tab_active', array( 'label' => __( 'فعال', 'examhub' ) ) );

		$this->add_control(
			'examhub_sf_dark_reset_bg_active',
			array(
				'label'     => __( 'پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-reset-bg-active: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_reset_color_active',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-reset-color-active: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_reset_border_active',
			array(
				'label'     => __( 'رنگ حاشیه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-reset-border-active: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'examhub_sf_dark_popup_heading',
			array(
				'label'     => __( 'پاپ‌آپ', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'examhub_sf_dark_popup_bg',
			array(
				'label'     => __( 'پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-popup-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_popup_header_bg',
			array(
				'label'     => __( 'پس‌زمینه هدر', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-popup-header-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_popup_footer_bg',
			array(
				'label'     => __( 'پس‌زمینه فوتر', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-popup-footer-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_popup_overlay_color',
			array(
				'label'     => __( 'رنگ پوشش پس‌زمینه (Overlay)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-overlay-color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'examhub_sf_dark_popup_overlay_opacity',
			array(
				'label'     => __( 'شفافیت پوشش (Overlay Opacity)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ) ),
				'selectors' => array( $dark => '--examhub-sf-dark-overlay-opacity: {{SIZE}};' ),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'examhub_sf_dark_popup_shadow',
				'selector' => "body.dark-mode {$wrapper} .examhub-search-filter__panel, [data-examhub-theme=\"dark\"] {$wrapper} .examhub-search-filter__panel",
			)
		);

		$this->add_control(
			'examhub_sf_dark_close_heading',
			array(
				'label'     => __( 'دکمه بستن پاپ‌آپ', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->start_controls_tabs( 'examhub_sf_dark_close_tabs' );

		$this->start_controls_tab( 'examhub_sf_dark_close_tab_normal', array( 'label' => __( 'عادی', 'examhub' ) ) );

		$this->add_control(
			'examhub_sf_dark_close_color',
			array(
				'label'     => __( 'رنگ', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-close-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_close_bg',
			array(
				'label'     => __( 'پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-close-bg: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_sf_dark_close_tab_hover', array( 'label' => __( 'هاور', 'examhub' ) ) );

		$this->add_control(
			'examhub_sf_dark_close_color_hover',
			array(
				'label'     => __( 'رنگ', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-close-color-hover: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'examhub_sf_dark_mobile_heading',
			array(
				'label'       => __( 'موبایل (پاپ‌آپ/دکمه فیلترها)', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::HEADING,
				'separator'   => 'before',
				'description' => __( 'این رنگ‌ها فقط در عرض موبایل (زیر ۷۶۸ پیکسل) اعمال می‌شوند و هیچ‌گاه با مقادیر دسکتاپ تداخل ندارند.', 'examhub' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_mobile_trigger_bg',
			array(
				'label'     => __( 'پس‌زمینه دکمه «فیلترها»', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-mobile-trigger-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_mobile_trigger_color',
			array(
				'label'     => __( 'رنگ متن/آیکون دکمه «فیلترها»', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-mobile-trigger-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_mobile_trigger_border',
			array(
				'label'     => __( 'حاشیه دکمه «فیلترها»', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-mobile-trigger-border: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_mobile_majors_bg',
			array(
				'label'     => __( 'پس‌زمینه ردیف رشته‌های پیشنهادی', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-mobile-majors-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'examhub_sf_dark_mobile_sheet_bg',
			array(
				'label'     => __( 'پس‌زمینه ورقه پایین (Bottom Sheet)', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $dark => '--examhub-sf-dark-mobile-sheet-bg: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Collect every facet's "مقدار پیش‌فرض" (default selected value) into a
	 * filter-key => value map, ready to merge into Examhub_Query::get_exams()
	 * for the initial server-rendered page — so the editor-configured default
	 * actually narrows the first page of results, not just the dropdown's
	 * pre-selected <option>.
	 *
	 * @since 1.5.0
	 * @param  array $settings Elementor widget settings.
	 * @return array<string,string>
	 */
	private function get_default_filter_values( array $settings ) {

		$defaults = array();

		foreach ( self::FIELDS as $key => $field ) {

			$value = trim( (string) ( $settings[ "examhub_default_{$key}" ] ?? '' ) );

			if ( '' !== $value ) {
				$defaults[ $key ] = $value;
			}
		}

		return $defaults;
	}

	/**
	 * Resolve the up-to-4 "رشته پیشنهادی" (Featured Major) quick-filter
	 * buttons configured in the editor into a term_id => name map, skipping
	 * any slot left empty.
	 *
	 * @since 1.2.0
	 * @param  array $settings Elementor widget settings.
	 * @return array<int,string>
	 */
	private function get_featured_majors( array $settings ) {

		$all     = Examhub_Query::get_term_choices( 'examhub_field' );
		$majors  = array();

		for ( $i = 1; $i <= 4; $i++ ) {

			$term_id = (int) ( $settings[ "examhub_featured_major_{$i}" ] ?? 0 );

			if ( $term_id && isset( $all[ $term_id ] ) ) {
				$majors[ $term_id ] = $all[ $term_id ];
			}
		}

		return $majors;
	}

	/**
	 * Output the widget on the front-end: the filter bar (rendered once,
	 * inline on desktop/tablet; repositioned into a mobile popup by CSS +
	 * examhub-frontend.js), an initial page of results (rendered
	 * server-side) that the front-end script replaces on filter change, and
	 * — when more than one page exists — a "نمایش بیشتر" button that appends
	 * the next page in place (see loadSearchFilterPage() in
	 * public/js/examhub-frontend.js).
	 *
	 * @since 1.0.0
	 */
	protected function render() {

		$settings      = $this->get_settings_for_display();
		$display       = $this->get_examhub_display_atts( $settings );
		$count         = max( 1, (int) $settings['examhub_count'] );
		$layout_mode   = in_array( $settings['examhub_layout_mode'] ?? 'hybrid', array( 'hybrid', 'inline_always', 'popup_always' ), true )
			? $settings['examhub_layout_mode']
			: 'hybrid';
		$popup_enabled = in_array( $layout_mode, array( 'hybrid', 'popup_always' ), true );
		$show_majors   = ! empty( $settings['examhub_show_featured_majors'] ) && 'yes' === $settings['examhub_show_featured_majors'];
		$majors        = $show_majors ? $this->get_featured_majors( $settings ) : array();
		$majors_below  = $show_majors && 'below' === ( $settings['examhub_featured_majors_position'] ?? 'above' );
		$panel_id      = 'examhub-sf-panel-' . $this->get_id();
		$default_values = $this->get_default_filter_values( $settings );

		$result = Examhub_Query::get_exams(
			array_merge(
				$default_values,
				array(
					'posts_per_page' => $count,
					'paged'          => 1,
				)
			)
		);

		// Pulled into a closure (not a separate method) since it's only ever
		// emitted from this one render path, in one of two possible
		// positions (see $majors_below above).
		$render_featured_majors = function () use ( $majors ) {
			?>
			<!--
			"رشته‌های پیشنهادی" shortcut row — deliberately a sibling of the
			popup (.examhub-search-filter__panel), NOT nested inside it (Popup
			Isolation rule). It must stay visible on the main screen at all
			times; nesting it inside the popup would tie its visibility to
			.is-popup-open and hide it until the user opens the modal, which
			defeats the point of a quick shortcut.
			-->
			<div class="examhub-search-filter__featured-majors">
				<?php foreach ( $majors as $term_id => $name ) : ?>
					<button type="button" class="examhub-search-filter__featured-major" data-term-id="<?php echo esc_attr( $term_id ); ?>">
						<?php echo esc_html( $name ); ?>
					</button>
				<?php endforeach; ?>
			</div>
			<?php
		};
		?>
		<div class="examhub-search-filter"
			data-per-page="<?php echo esc_attr( $count ); ?>"
			data-show-image="<?php echo $display['show_image'] ? '1' : '0'; ?>"
			data-show-stats="<?php echo $display['show_stats'] ? '1' : '0'; ?>"
			data-paged="1"
			data-max-pages="<?php echo esc_attr( $result['max_num_pages'] ); ?>"
			data-popup-enabled="<?php echo $popup_enabled ? '1' : '0'; ?>"
			data-layout-mode="<?php echo esc_attr( $layout_mode ); ?>"
			data-animation="<?php echo esc_attr( $settings['examhub_popup_animation'] ); ?>"
			data-match-type="<?php echo esc_attr( 'OR' === $settings['examhub_match_type'] ? 'OR' : 'AND' ); ?>"
		>
			<?php if ( $show_majors && ! empty( $majors ) && ! $majors_below ) : ?>
				<?php $render_featured_majors(); ?>
			<?php endif; ?>

			<?php if ( $popup_enabled ) : ?>
				<button type="button" class="examhub-search-filter__filters-toggle examhub-btn" aria-haspopup="dialog" aria-expanded="false" aria-controls="<?php echo esc_attr( $panel_id ); ?>">
					<span class="examhub-search-filter__filters-toggle-icon" aria-hidden="true">
						<?php if ( ! empty( $settings['examhub_filters_button_icon']['value'] ) ) : ?>
							<?php
							// Wrapped in our own fixed-size <span> (see CSS) so Elementor's
							// icon — whether an SVG (sized via 1em, tied to font-size) or an
							// icon-font glyph (sized via font-size directly) — can never
							// inherit a font-size from this button, an Elementor global icon
							// setting, or a theme default. The wrapper is the single thing
							// CSS needs to size; what Elementor renders inside it doesn't
							// matter.
							\Elementor\Icons_Manager::render_icon( $settings['examhub_filters_button_icon'], array( 'aria-hidden' => 'true' ) );
							?>
						<?php else : ?>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M3 5h18M6 12h12M10 19h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
							</svg>
						<?php endif; ?>
					</span>
					<span class="examhub-search-filter__filters-toggle-text"><?php echo esc_html( $settings['examhub_filters_button_text'] ); ?></span>
				</button>

				<div class="examhub-search-filter__overlay"></div>
			<?php endif; ?>

			<?php if ( $show_majors && ! empty( $majors ) && $majors_below ) : ?>
				<?php $render_featured_majors(); ?>
			<?php endif; ?>

			<div class="examhub-search-filter__panel"
				id="<?php echo esc_attr( $panel_id ); ?>"
				role="dialog"
				aria-modal="true"
				aria-label="<?php echo esc_attr( $settings['examhub_popup_title'] ); ?>"
			>
				<?php if ( $popup_enabled ) : ?>
					<div class="examhub-search-filter__panel-header">
						<span class="examhub-search-filter__panel-title"><?php echo esc_html( $settings['examhub_popup_title'] ); ?></span>
						<button type="button" class="examhub-search-filter__panel-close" aria-label="<?php esc_attr_e( 'بستن', 'examhub' ); ?>">&times;</button>
					</div>
				<?php endif; ?>

				<div class="examhub-search-filter__panel-body">

					<?php
					$is_accordion = $popup_enabled && 'accordion' === ( $settings['examhub_popup_field_style'] ?? 'flat' );
					$field_index  = 0;
					?>

					<div class="examhub-search-filter__bar<?php echo $is_accordion ? ' examhub-search-filter__bar--accordion' : ''; ?>">

						<?php foreach ( self::FIELDS as $key => $field ) : ?>
							<?php if ( ! empty( $settings[ $field['switch'] ] ) && 'yes' === $settings[ $field['switch'] ] ) : ?>
								<?php
								// Flat model: every facet is independent, so each one
								// always lists its taxonomy's full term set and is
								// always enabled — there is no parent/child relation to
								// wait on any more. Structure taxonomies (مقطع/پایه/
								// رشته/درس) are still keyed by term_id (matches
								// Examhub_Query::get_exams()'s tax_query 'field'); the
								// flat facets (سال/نوبت/نوع‌آزمون) stay slug-keyed.
								$is_structure_facet = array_key_exists( $field['taxonomy'], Examhub_Query::STRUCTURE_TAXONOMIES );
								$is_multiselect      = ! empty( $settings[ "examhub_multiselect_{$key}" ] ) && 'yes' === $settings[ "examhub_multiselect_{$key}" ];
								$term_options        = $is_structure_facet
									? Examhub_Query::get_term_choices( $field['taxonomy'] )
									: Examhub_Query::get_term_options( $field['taxonomy'] );
								$default_value       = (string) ( $settings[ "examhub_default_{$key}" ] ?? '' );
								$field_label         = $this->get_filter_label( $key, $settings );
								$is_first_field      = 0 === $field_index;
								?>
								<div class="examhub-search-filter__field" data-filter-field="<?php echo esc_attr( $key ); ?>">
									<?php if ( $is_accordion ) : ?>
										<button type="button" class="examhub-search-filter__field-header" aria-expanded="<?php echo $is_first_field ? 'true' : 'false'; ?>">
											<span class="examhub-search-filter__field-label"><?php echo esc_html( $field_label ); ?></span>
											<span class="examhub-search-filter__field-badge" hidden></span>
											<svg class="examhub-search-filter__field-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
												<path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
											</svg>
										</button>
									<?php endif; ?>
									<div class="examhub-search-filter__field-body" <?php echo ( $is_accordion && ! $is_first_field ) ? 'hidden' : ''; ?>>
										<span class="examhub-search-filter__select-wrap">
											<select
												class="examhub-search-filter__select"
												data-filter="<?php echo esc_attr( $key ); ?>"
												<?php if ( $is_multiselect ) : ?>
													multiple
													data-multiselect="1"
												<?php endif; ?>
											>
												<?php if ( ! $is_multiselect ) : ?>
													<option value=""><?php echo esc_html( $field_label ); ?> (<?php esc_html_e( 'همه', 'examhub' ); ?>)</option>
												<?php endif; ?>
												<?php foreach ( $term_options as $value => $name ) : ?>
													<?php
													// Structure facets (رشته/درس و…) carry their linked parent
													// term id(s) so the JS can narrow a child facet's <option>
													// list to the selected parent's children client-side, with
													// no extra AJAX round-trip and no query re-run (Examhub_Query::get_structure_parents()).
													$parent_ids = $is_structure_facet ? Examhub_Query::get_structure_parents( $value, $field['taxonomy'] ) : array();
													?>
													<option
														value="<?php echo esc_attr( $value ); ?>"
														<?php if ( $parent_ids ) : ?>
															data-parent="<?php echo esc_attr( implode( ',', $parent_ids ) ); ?>"
														<?php endif; ?>
														<?php selected( $default_value, (string) $value ); ?>
													><?php echo esc_html( $name ); ?></option>
												<?php endforeach; ?>
											</select>
										</span>
									</div>
								</div>
								<?php ++$field_index; ?>
							<?php endif; ?>
						<?php endforeach; ?>

						<?php if ( ! empty( $settings['examhub_show_search'] ) && 'yes' === $settings['examhub_show_search'] ) : ?>
							<input type="search" class="examhub-search-filter__search" placeholder="<?php echo esc_attr( $settings['examhub_search_placeholder'] ); ?>" />
						<?php endif; ?>

						<div class="examhub-search-filter__actions">
							<button type="button" class="examhub-search-filter__reset examhub-btn examhub-btn--outline">
								<?php echo esc_html( $settings['examhub_reset_text'] ); ?>
							</button>
							<button type="button" class="examhub-search-filter__apply examhub-btn">
								<span class="examhub-search-filter__apply-spinner" aria-hidden="true"></span>
								<span class="examhub-search-filter__apply-label"><?php echo esc_html( $settings['examhub_apply_text'] ); ?></span>
							</button>
						</div>
					</div>

					<?php if ( ! empty( $settings['examhub_show_chips'] ) && 'yes' === $settings['examhub_show_chips'] ) : ?>
						<div class="examhub-search-filter__chips" aria-live="polite"></div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! empty( $settings['examhub_show_result_count'] ) && 'yes' === $settings['examhub_show_result_count'] ) : ?>
				<p class="examhub-search-filter__result-count">
					<?php
					printf(
						/* translators: %s: number of matching exams. */
						esc_html__( '%s آزمون یافت شد', 'examhub' ),
						esc_html( number_format_i18n( $result['found_posts'] ) )
					);
					?>
				</p>
			<?php endif; ?>

			<div class="examhub-search-filter__results">
				<?php echo wp_kses_post( examhub_render_exam_grid( $result['items'], $display ) ); ?>
			</div>

			<div class="examhub-search-filter__footer">
				<?php $this->render_examhub_toggle_button( $settings, 'examhub-search-filter__load-more', $result['max_num_pages'] ); ?>
			</div>
		</div>
		<?php
	}

}
