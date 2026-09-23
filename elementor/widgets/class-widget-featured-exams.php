<?php

/**
 * "Featured Exams" widget — tabbed shelves for "پیشنهادی" (manually featured),
 * "جدید" (latest), and "پربازدیدترین‌ها" (most downloaded) exams.
 *
 * All three shelves are queried and rendered server-side up front (the lists
 * are short, so there's no need for AJAX); a small inline-friendly script in
 * public/js/examhub-frontend.js just toggles which panel is visible.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor/widgets
 */
class Examhub_Widget_Featured_Exams extends \Elementor\Widget_Base {

	use Examhub_Card_Style_Controls_Trait;

	/**
	 * Definition of the three possible shelves: switch control and query args.
	 *
	 * Tab labels live in get_tab_label() rather than here so the i18n
	 * string-extraction tooling can find them (a constant can't call __()).
	 *
	 * @since 1.0.0
	 * @var   array<string,array>
	 */
	const TABS = array(
		'featured' => array(
			'switch' => 'examhub_show_featured_tab',
			'args'   => array(
				'featured' => true,
				'orderby'  => 'latest',
			),
		),
		'latest'   => array(
			'switch' => 'examhub_show_latest_tab',
			'args'   => array(
				'orderby' => 'latest',
			),
		),
		'popular'  => array(
			'switch' => 'examhub_show_popular_tab',
			'args'   => array(
				'orderby' => 'popular',
			),
		),
	);

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return 'examhub_featured_exams';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title() {
		return __( 'آزمون‌های پیشنهادی (Featured Exams)', 'examhub' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_icon() {
		return 'eicon-product-related';
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
		return array( 'examhub', 'featured', 'ویژه', 'پیشنهادی', 'پربازدید' );
	}

	/**
	 * Translate a tab key to its display label.
	 *
	 * Kept as a literal switch (rather than storing translated strings in
	 * the TABS constant) so the i18n string-extraction tooling can find
	 * every label.
	 *
	 * @since 1.0.0
	 * @param  string $key One of the TABS keys.
	 * @return string
	 */
	private function get_tab_label( $key ) {

		switch ( $key ) {
			case 'featured':
				return __( 'آزمون‌های پیشنهادی', 'examhub' );
			case 'latest':
				return __( 'آزمون‌های جدید', 'examhub' );
			case 'popular':
				return __( 'پربازدیدترین‌ها', 'examhub' );
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
				'label' => __( 'تب‌ها', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		foreach ( self::TABS as $key => $tab ) {

			$this->add_control(
				$tab['switch'],
				array(
					/* translators: %s: tab label, e.g. "آزمون‌های پیشنهادی". */
					'label'        => sprintf( __( 'نمایش تب «%s»', 'examhub' ), $this->get_tab_label( $key ) ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => __( 'بله', 'examhub' ),
					'label_off'    => __( 'خیر', 'examhub' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				)
			);
		}

		$this->add_control(
			'examhub_count',
			array(
				'label'   => __( 'تعداد نمایش در هر تب', 'examhub' ),
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
			'examhub_tab_style_section',
			array(
				'label' => __( 'ظاهر تب‌ها', 'examhub' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->start_controls_tabs( 'examhub_tab_color_tabs' );

		$this->start_controls_tab( 'examhub_tab_color_tab_normal', array( 'label' => __( 'عادی', 'examhub' ) ) );
		$this->add_control(
			'examhub_tab_color',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-featured' => '--examhub-tab-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'examhub_tab_color_tab_active', array( 'label' => __( 'فعال', 'examhub' ) ) );
		$this->add_control(
			'examhub_tab_color_active',
			array(
				'label'     => __( 'رنگ متن', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-featured' => '--examhub-tab-color-active: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'examhub_tab_border_active',
			array(
				'label'     => __( 'رنگ خط زیر تب', 'examhub' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .examhub-featured' => '--examhub-tab-border-active: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->register_examhub_card_style_controls();
	}

	/**
	 * Output the widget on the front-end: one button + one panel per enabled shelf.
	 *
	 * @since 1.0.0
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();
		$display  = $this->get_examhub_display_atts( $settings );
		$count    = (int) $settings['examhub_count'];

		$enabled = array();

		foreach ( self::TABS as $key => $tab ) {
			if ( ! empty( $settings[ $tab['switch'] ] ) && 'yes' === $settings[ $tab['switch'] ] ) {
				$enabled[ $key ] = $tab;
			}
		}

		if ( empty( $enabled ) ) {
			echo '<p class="examhub-empty">' . esc_html__( 'حداقل یک تب را فعال کنید.', 'examhub' ) . '</p>';
			return;
		}

		$first = array_key_first( $enabled );
		?>
		<div class="examhub-featured" data-default-tab="<?php echo esc_attr( $first ); ?>">
			<div class="examhub-featured__tabs" role="tablist">
				<?php foreach ( $enabled as $key => $tab ) : ?>
					<button type="button"
						class="examhub-featured__tab<?php echo $key === $first ? ' is-active' : ''; ?>"
						data-tab="<?php echo esc_attr( $key ); ?>"
						role="tab"
						aria-selected="<?php echo $key === $first ? 'true' : 'false'; ?>"
					>
						<?php echo esc_html( $this->get_tab_label( $key ) ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<?php foreach ( $enabled as $key => $tab ) :
				$query_args = array_merge( array( 'posts_per_page' => $count ), $tab['args'] );
				$result     = Examhub_Query::get_exams( $query_args );
				?>
				<div class="examhub-featured__panel<?php echo $key === $first ? ' is-active' : ''; ?>"
					data-panel="<?php echo esc_attr( $key ); ?>"
					<?php echo $key === $first ? '' : 'hidden'; ?>
				>
					<?php echo wp_kses_post( examhub_render_exam_grid( $result['items'], $display ) ); ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

}
