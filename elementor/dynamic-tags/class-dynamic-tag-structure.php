<?php

/**
 * Elementor dynamic tag: the compact inline "مقطع | پایه | رشته | درس"
 * structure label for the current exam, for use in a Loop Grid/Theme
 * Builder template (or any other Elementor text control that accepts
 * dynamic tags) without needing one of ExamHub's own widgets.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor/dynamic-tags
 */
class Examhub_Dynamic_Tag_Structure extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return 'examhub-structure-label';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title() {
		return __( 'ساختار تحصیلی (مقطع | پایه | رشته | درس)', 'examhub' );
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
	 * No settings to configure — the tag always reflects the current post.
	 *
	 * @since 1.0.0
	 */
	protected function register_controls() {}

	/**
	 * @inheritDoc
	 */
	public function render() {

		$post_id = get_the_ID();

		if ( ! $post_id || 'examhub_exam' !== get_post_type( $post_id ) ) {
			return;
		}

		echo esc_html( Examhub_Query::get_inline_structure_label( $post_id ) );
	}

}
