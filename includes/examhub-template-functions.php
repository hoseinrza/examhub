<?php

/**
 * Shared front-end template helpers.
 *
 * Procedural on purpose: this is the single rendering entry point used by
 * shortcodes, AJAX responses, and every Elementor widget so the markup for
 * an exam card only has to be maintained in one partial.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Render a single exam card and return the resulting HTML.
 *
 * @since 1.0.0
 * @param  array $exam Standardized card data from Examhub_Query::get_card_data().
 * @param  array $atts {
 *     Optional display flags.
 *
 *     @type bool $show_image Whether to render the featured image. Default true.
 *     @type bool $show_stats Whether to render the download counter. Default true.
 * }
 * @return string
 */
function examhub_render_exam_card( array $exam, array $atts = array() ) {

	$atts = wp_parse_args(
		$atts,
		array(
			'show_image' => true,
			'show_stats' => true,
		)
	);

	ob_start();

	include plugin_dir_path( __FILE__ ) . '../public/partials/card-exam.php';

	return ob_get_clean();
}

/**
 * Render a list of exam cards wrapped in the standard grid container.
 *
 * @since 1.0.0
 * @param  array  $exams      Array of standardized card-data arrays.
 * @param  array  $atts       Display flags forwarded to examhub_render_exam_card().
 * @param  string $empty_text Message shown when $exams is empty.
 * @return string
 */
function examhub_render_exam_grid( array $exams, array $atts = array(), $empty_text = '' ) {

	if ( empty( $exams ) ) {
		$empty_text = $empty_text ? $empty_text : __( 'آزمونی یافت نشد.', 'examhub' );

		return '<p class="examhub-empty">' . esc_html( $empty_text ) . '</p>';
	}

	$html = '<div class="examhub-grid">';

	foreach ( $exams as $exam ) {
		$html .= examhub_render_exam_card( $exam, $atts );
	}

	$html .= '</div>';

	return $html;
}
