<?php

/**
 * Elementor dynamic tag: the name of one of the current exam's structure
 * taxonomy terms (مقطع/پایه/رشته/درس/سال/نوبت/نوع), selectable per use, for
 * a Text control inside a Loop Grid/Theme Builder template.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.1.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor/dynamic-tags
 */
class Examhub_Dynamic_Tag_Term_Name extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return 'examhub-term-name';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title() {
		return __( 'نام دسته‌بندی آزمون', 'examhub' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_group() {
		return 'examhub';
	}

	/**
	 * @inheritDoc
	 */
	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	/**
	 * One control: which taxonomy facet to read the term name from.
	 *
	 * @since 1.1.0
	 */
	protected function register_controls() {

		$this->add_control(
			'taxonomy',
			array(
				'label'   => __( 'دسته‌بندی', 'examhub' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'examhub_level'     => __( 'مقطع', 'examhub' ),
					'examhub_grade'     => __( 'پایه', 'examhub' ),
					'examhub_field'     => __( 'رشته', 'examhub' ),
					'examhub_subject'   => __( 'درس', 'examhub' ),
					'examhub_year'      => __( 'سال', 'examhub' ),
					'examhub_term'      => __( 'نوبت', 'examhub' ),
					'examhub_exam_type' => __( 'نوع آزمون', 'examhub' ),
				),
				'default' => 'examhub_subject',
			)
		);
	}

	/**
	 * @inheritDoc
	 */
	public function render() {

		$post_id = get_the_ID();

		if ( ! $post_id || 'examhub_exam' !== get_post_type( $post_id ) ) {
			return;
		}

		$taxonomy = $this->get_settings( 'taxonomy' );
		$terms    = get_the_terms( $post_id, $taxonomy );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		echo esc_html( $terms[0]->name );
	}

}
