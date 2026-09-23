<?php

/**
 * Elementor dynamic tag: the current exam's total download count
 * (questions + answers), for use in a Text control inside a Loop
 * Grid/Theme Builder template.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.1.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor/dynamic-tags
 */
class Examhub_Dynamic_Tag_Download_Count extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return 'examhub-download-count';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title() {
		return __( 'تعداد دانلود آزمون', 'examhub' );
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
	 * @since 1.1.0
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

		$questions_downloads = (int) get_post_meta( $post_id, '_examhub_questions_downloads', true );
		$answers_downloads   = (int) get_post_meta( $post_id, '_examhub_answers_downloads', true );

		echo esc_html( number_format_i18n( $questions_downloads + $answers_downloads ) );
	}

}
