<?php

/**
 * Elementor dynamic tag: the current exam's featured image URL, for use in
 * an Image control inside a Loop Grid/Theme Builder template.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.1.0
 *
 * @package    Examhub
 * @subpackage Examhub/elementor/dynamic-tags
 */
class Examhub_Dynamic_Tag_Featured_Image extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return 'examhub-featured-image';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title() {
		return __( 'تصویر شاخص آزمون', 'examhub' );
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
		return array( \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY );
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
	public function get_value( array $options = array() ) {

		$post_id = get_the_ID();

		if ( ! $post_id || 'examhub_exam' !== get_post_type( $post_id ) ) {
			return array(
				'id'  => '',
				'url' => '',
			);
		}

		return array(
			'id'  => get_post_thumbnail_id( $post_id ),
			'url' => get_the_post_thumbnail_url( $post_id, 'medium' ),
		);
	}

}
