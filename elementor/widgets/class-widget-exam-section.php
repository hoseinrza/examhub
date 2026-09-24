<?php

/**
 * "Exam Section" widget — a row of category tabs with an inline "view all"
 * button, above an AJAX-filtered row of exam cards.
 *
 * The header is a single line: the category tabs and a "مشاهده همه" button
 * (pinned to the right). Clicking a tab re-queries the cards over AJAX (via the
 * shared `examhub_query_exams` endpoint) without a page reload. The tab
 * taxonomy and exactly which of its terms become tabs are both chosen from the
 * Elementor editor.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor/widgets
 */
class Examhub_Widget_Exam_Section extends \Elementor\Widget_Base {

	use Examhub_Card_Style_Controls_Trait;

	/**
	 * The taxonomies that can drive the tab row, in display order.
	 *
	 * Labels live in get_taxonomy_label() rather than here so the i18n
	 * string-extraction tooling can find them (a constant can't call __()).
	 *
	 * @since 1.0.0
	 * @var   string[]
	 */
	const TAB_TAXONOMIES = array( 'examhub_level', 'examhub_grade', 'examhub_field', 'examhub_subject', 'examhub_year', 'examhub_term', 'examhub_exam_type' );

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return 'examhub_exam_section';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title() {
		return __( 'بخش آزمون‌ها با تب (Exam Section)', 'examhub' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_icon() {
		return 'eicon-tabs';
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
		return array( 'examhub', 'section', 'tabs', 'بخش', 'تب', 'دسته بندی', 'آزمون' );
	}

	/**
	 * Translate a tab-taxonomy slug to its display label.
	 *
	 * @since 1.0.0
	 * @param  string $taxonomy One of TAB_TAXONOMIES.
	 * @return string
	 */
	private function get_taxonomy_label( $taxonomy ) {

		switch ( $taxonomy ) {
			case 'examhub_level':
				return __( 'مقطع', 'examhub' );
			case 'examhub_grade':
				return __( 'پایه', 'examhub' );
			case 'examhub_field':
				return __( 'رشته', 'examhub' );
			case 'examhub_subject':
				return __( 'درس', 'examhub' );
			case 'examhub_year':
				return __( 'سال', 'examhub' );
			case 'examhub_term':
				return __( 'نوبت', 'examhub' );
			case 'examhub_exam_type':
				return __( 'نوع آزمون', 'examhub' );
			default:
				return '';
		}
	}

	/**
	 * Build the taxonomy => label options map for the tab-source select.
	 *
	 * @since 1.0.0
	 * @return array<string,string>
	 */
	private function get_taxonomy_options() {

		$options = array();

		foreach ( self::TAB_TAXONOMIES as $taxonomy ) {
			$options[ $taxonomy ] = $this->get_taxonomy_label( $taxonomy );
		}

		return $options;
	}

	/**
	 * Register the widget's content & style controls.
	 *
	 * @since 1.0.0
	 */
	protected function register_controls() {

		/* ----------------------------------------------------------------
		 * Content — tabs + cards
		 * -------------------------------------------------------------- */

		$this->start_controls_section(
			'examhub_tabs_section',
			array(
				'label' => __( 'تب‌ها و کارت‌ها', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'examhub_tab_taxonomy',
			array(
				'label'   => __( 'منبع تب‌ها (دسته‌بندی)', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->get_taxonomy_options(),
				'default' => 'examhub_level',
			)
		);

		foreach ( self::TAB_TAXONOMIES as $taxonomy ) {

			// پایه/رشته/درس are dependent structure facets — same hierarchy
			// (Examhub_Query::STRUCTURE_PARENT_META) the cascading selects in
			// the post-edit metabox, Exam Showcase, and Search & Filter all
			// honor. Let the admin scope the tab row to one والد so the tabs
			// shown (and the exams each tab filters to) stay inside that
			// branch instead of mixing terms from unrelated مقطع/پایه/رشته.
			$parent_taxonomy = Examhub_Query::get_parent_taxonomy( $taxonomy );

			if ( $parent_taxonomy ) {
				$this->add_control(
					'examhub_tab_parent_' . $taxonomy,
					array(
						/* translators: %s: parent taxonomy label, e.g. "پایه". */
						'label'       => sprintf( __( 'محدود به والد (%s) — اختیاری', 'examhub' ), $this->get_taxonomy_label( $parent_taxonomy ) ),
						'type'        => \Elementor\Controls_Manager::SELECT2,
						'label_block' => true,
						'options'     => array( '' => __( '— بدون محدودیت (همه) —', 'examhub' ) ) + Examhub_Query::get_term_choices( $parent_taxonomy ),
						'default'     => '',
						'description' => __( 'اگر انتخاب شود، فقط زیرمجموعه‌های همین والد به‌عنوان تب نمایش داده می‌شوند و آزمون‌های هر تب هم به همان والد محدود می‌شوند — مطابق همان وابستگی سلسله‌مراتبی مقطع›پایه›رشته›درس که در بقیه ویجت‌ها استفاده می‌شود.', 'examhub' ),
						'condition'   => array( 'examhub_tab_taxonomy' => $taxonomy ),
					)
				);
			}

			$this->add_control(
				'examhub_terms_' . $taxonomy,
				array(
					/* translators: %s: taxonomy label, e.g. "رشته". */
					'label'       => sprintf( __( 'دسته‌های نمایشی (%s)', 'examhub' ), $this->get_taxonomy_label( $taxonomy ) ),
					'type'        => \Elementor\Controls_Manager::SELECT2,
					'multiple'    => true,
					'label_block' => true,
					'options'     => Examhub_Query::get_term_options( $taxonomy ),
					'description' => __( 'خالی بگذارید تا همه‌ی دسته‌ها نمایش داده شوند.', 'examhub' ),
					'condition'   => array( 'examhub_tab_taxonomy' => $taxonomy ),
				)
			);
		}

		$this->add_control(
			'examhub_show_all_tab',
			array(
				'label'        => __( 'نمایش تب «همه»', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'examhub' ),
				'label_off'    => __( 'خیر', 'examhub' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'examhub_count',
			array(
				'label'   => __( 'تعداد کارت', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 48,
				'default' => 4,
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

		/* ----------------------------------------------------------------
		 * Content — "view all" button
		 * -------------------------------------------------------------- */

		$this->start_controls_section(
			'examhub_button_section',
			array(
				'label' => __( 'دکمه مشاهده همه', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'examhub_show_button',
			array(
				'label'        => __( 'نمایش دکمه', 'examhub' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'بله', 'examhub' ),
				'label_off'    => __( 'خیر', 'examhub' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'examhub_button_text',
			array(
				'label'     => __( 'متن دکمه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'مشاهده همه', 'examhub' ),
				'condition' => array( 'examhub_show_button' => 'yes' ),
			)
		);

		$this->add_control(
			'examhub_button_link',
			array(
				'label'       => __( 'لینک دکمه', 'examhub' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'placeholder' => 'https://example.com',
				'default'     => array( 'url' => '#' ),
				'condition'   => array( 'examhub_show_button' => 'yes' ),
			)
		);

		$this->end_controls_section();

		/* ----------------------------------------------------------------
		 * Style — tabs
		 * -------------------------------------------------------------- */

		$this->start_controls_section(
			'examhub_tabs_style_section',
			array(
				'label' => __( 'ظاهر تب‌ها', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->start_controls_tabs( 'examhub_section_tab_color_tabs' );

		$this->start_controls_tab( 'examhub_section_tab_normal', array( 'label' => __( 'عادی', 'examhub' ) ) );
		$this->add_control(
			'examhub_tab_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-section' => '--examhub-tab-bg: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'examhub_tab_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-section' => '--examhub-tab-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_section_tab_active', array( 'label' => __( 'فعال', 'examhub' ) ) );
		$this->add_control(
			'examhub_tab_bg_active',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-section' => '--examhub-tab-bg-active: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'examhub_tab_color_active',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-section' => '--examhub-tab-color-active: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		/* ----------------------------------------------------------------
		 * Style — "view all" button
		 * -------------------------------------------------------------- */

		$this->start_controls_section(
			'examhub_button_style_section',
			array(
				'label'     => __( 'ظاهر دکمه', 'examhub' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array( 'examhub_show_button' => 'yes' ),
			)
		);

		$this->start_controls_tabs( 'examhub_section_btn_tabs' );

		$this->start_controls_tab( 'examhub_section_btn_normal', array( 'label' => __( 'عادی', 'examhub' ) ) );
		$this->add_control(
			'examhub_section_btn_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-section' => '--examhub-section-btn-bg: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'examhub_section_btn_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-section' => '--examhub-section-btn-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_section_btn_hover', array( 'label' => __( 'هاور', 'examhub' ) ) );
		$this->add_control(
			'examhub_section_btn_bg_hover',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-section' => '--examhub-section-btn-bg-hover: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'examhub_section_btn_color_hover',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-section' => '--examhub-section-btn-color-hover: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->register_examhub_card_style_controls();
	}

	/**
	 * Read the term ID of the optional "محدود به والد" scope control for the
	 * given taxonomy, if that taxonomy has a parent in the structure
	 * hierarchy and the admin picked one.
	 *
	 * @since 1.0.0
	 * @param  array  $settings Elementor widget settings.
	 * @param  string $taxonomy The chosen tab taxonomy.
	 * @return int 0 when unset/not applicable.
	 */
	private function get_tab_parent_term_id( array $settings, $taxonomy ) {

		$control = 'examhub_tab_parent_' . $taxonomy;

		return isset( $settings[ $control ] ) ? absint( $settings[ $control ] ) : 0;
	}

	/**
	 * Resolve saved IDs or legacy slugs to IDs in the selected taxonomy.
	 * Numeric values prefer an existing ID; otherwise try the legacy slug.
	 * This reads old settings without rewriting Elementor documents.
	 *
	 * @param array  $values   Saved control values.
	 * @param string $taxonomy Selected taxonomy.
	 * @return int[]
	 */
	private function resolve_tab_term_ids( array $values, $taxonomy ) {
		$ids = array();

		foreach ( $values as $value ) {
			if ( ! is_string( $value ) && ! is_int( $value ) ) {
				continue;
			}
			$value = trim( (string) $value );
			if ( '' === $value ) {
				continue;
			}

			$term = null;
			if ( ctype_digit( $value ) && (int) $value > 0 ) {
				$term = get_term( (int) $value, $taxonomy );
			}
			if ( ! $term || is_wp_error( $term ) ) {
				$term = get_term_by( 'slug', $value, $taxonomy );
			}
			if ( $term && ! is_wp_error( $term ) ) {
				$ids[] = (int) $term->term_id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Resolve the list of tab terms (term ID => plain name) for the chosen taxonomy.
	 *
	 * When a "محدود به والد" parent scope is set, the candidate terms are
	 * first pruned to that parent's dependents — the exact same
	 * Examhub_Query::STRUCTURE_PARENT_META lookup used by every other
	 * cascading control in the plugin — before curated terms (if any) narrow
	 * the list further.
	 *
	 * @since 1.0.0
	 * @param  array  $settings Elementor widget settings.
	 * @param  string $taxonomy The chosen tab taxonomy.
	 * @return array<int,string> term ID => name
	 */
	private function get_tab_terms( array $settings, $taxonomy ) {

		$control  = 'examhub_terms_' . $taxonomy;
		$selected = isset( $settings[ $control ] ) ? array_filter( (array) $settings[ $control ] ) : array();

		$parent_term_id = $this->get_tab_parent_term_id( $settings, $taxonomy );

		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		);

		if ( $parent_term_id && array_key_exists( $taxonomy, Examhub_Query::STRUCTURE_PARENT_META ) ) {

			$dependent_ids = array_keys( Examhub_Query::get_dependent_terms( $taxonomy, $parent_term_id ) );

			if ( empty( $dependent_ids ) ) {
				return array();
			}

			$args['include'] = $dependent_ids;
		}

		if ( ! empty( $selected ) ) {
			$selected_ids = $this->resolve_tab_term_ids( $selected, $taxonomy );
			if ( isset( $args['include'] ) ) {
				$selected_ids = array_values( array_intersect( $selected_ids, $args['include'] ) );
			}
			// An empty include would remove the restriction and return every term.
			if ( empty( $selected_ids ) ) {
				return array();
			}
			$args['include'] = $selected_ids;
		}

		$terms     = get_terms( $args );
		$tab_terms = array();

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$tab_terms[ (int) $term->term_id ] = $term->name;
			}
		}

		return $tab_terms;
	}

	/**
	 * Output the widget on the front-end: the tab row (with an inline "view
	 * all" button) and the initial set of cards, rendered server-side.
	 *
	 * @since 1.0.0
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();
		$taxonomy = $settings['examhub_tab_taxonomy'];
		$count    = max( 1, (int) $settings['examhub_count'] );
		$orderby  = $settings['examhub_orderby'];
		$display  = $this->get_examhub_display_atts( $settings );

		$tab_terms = $this->get_tab_terms( $settings, $taxonomy );
		$show_all  = ! empty( $settings['examhub_show_all_tab'] ) && 'yes' === $settings['examhub_show_all_tab'];

		if ( ! $show_all && empty( $tab_terms ) ) {
			echo '<p class="examhub-empty">' . esc_html__( 'آزمونی یافت نشد.', 'examhub' ) . '</p>';
			return;
		}

		$parent_taxonomy   = Examhub_Query::get_parent_taxonomy( $taxonomy );
		$parent_term_id    = $this->get_tab_parent_term_id( $settings, $taxonomy );

		// When the "all" tab is hidden, the first curated term is the default view.
		$default_term = 0;
		if ( ! $show_all && ! empty( $tab_terms ) ) {
			$default_term = (int) array_key_first( $tab_terms );
		}

		$query_args = array(
			'orderby'        => $orderby,
			'posts_per_page' => $count,
		);

		if ( $default_term > 0 ) {
			$filter_key                = str_replace( 'examhub_', '', $taxonomy );
			$query_args[ $filter_key ] = $default_term;
		}

		// Keep the parent scope's restriction in effect even for the "همه"
		// tab — otherwise "همه" would silently widen the result set beyond
		// the branch the admin deliberately scoped the tabs to.
		if ( $parent_term_id && $parent_taxonomy ) {
			$parent_filter_key                = str_replace( 'examhub_', '', $parent_taxonomy );
			$query_args[ $parent_filter_key ]  = $parent_term_id;
		}

		$result = Examhub_Query::get_exams( $query_args );

		// "View all" button.
		$show_button = ! empty( $settings['examhub_show_button'] ) && 'yes' === $settings['examhub_show_button'];
		$link        = isset( $settings['examhub_button_link'] ) ? $settings['examhub_button_link'] : array();
		$button_url  = ! empty( $link['url'] ) ? $link['url'] : '';
		$has_button  = $show_button && '' !== $button_url && '' !== trim( (string) $settings['examhub_button_text'] );

		$button_atts = '';
		if ( ! empty( $link['is_external'] ) ) {
			$button_atts .= ' target="_blank"';
		}
		if ( ! empty( $link['nofollow'] ) ) {
			$button_atts .= ' rel="nofollow"';
		}
		?>
		<div class="examhub-section"
			data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
			data-per-page="<?php echo esc_attr( $count ); ?>"
			data-orderby="<?php echo esc_attr( $orderby ); ?>"
			data-show-image="<?php echo $display['show_image'] ? '1' : '0'; ?>"
			data-show-stats="<?php echo $display['show_stats'] ? '1' : '0'; ?>"
			<?php if ( $parent_term_id && $parent_taxonomy ) : ?>
				data-parent-taxonomy="<?php echo esc_attr( $parent_taxonomy ); ?>"
				data-parent-term="<?php echo esc_attr( $parent_term_id ); ?>"
			<?php endif; ?>
		>
			<div class="examhub-section__header">

				<div class="examhub-section__tabs" role="tablist">
					<?php if ( $show_all ) : ?>
						<button type="button" class="examhub-section__tab is-active" data-term="" role="tab" aria-selected="true">
							<?php esc_html_e( 'همه', 'examhub' ); ?>
						</button>
					<?php endif; ?>

					<?php
					$first = true;
					foreach ( $tab_terms as $term_id => $name ) :
						$is_active = ! $show_all && $first;
						?>
						<button type="button" class="examhub-section__tab<?php echo $is_active ? ' is-active' : ''; ?>" data-term="<?php echo esc_attr( $term_id ); ?>" role="tab" aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>">
							<?php echo esc_html( $name ); ?>
						</button>
						<?php
						$first = false;
					endforeach;
					?>
				</div>

				<?php if ( $has_button ) : ?>
					<a class="examhub-section__button" href="<?php echo esc_url( $button_url ); ?>"<?php echo $button_atts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<span class="examhub-section__button-text"><?php echo esc_html( $settings['examhub_button_text'] ); ?></span>
						<span class="examhub-section__button-arrow" aria-hidden="true">«</span>
					</a>
				<?php endif; ?>

			</div>

			<div class="examhub-section__grid">
				<?php echo wp_kses_post( examhub_render_exam_grid( $result['items'], $display ) ); ?>
			</div>
		</div>
		<?php
	}

}
