<?php
/**
 * "Classification" metabox markup: one single-select dropdown per ExamHub
 * taxonomy, replacing WordPress's default free-text tag boxes so the editor
 * picks an existing term instead of typing it.
 *
 * Expects $post (the exam being edited) in scope.
 *
 * @package    Examhub
 * @subpackage Examhub/admin/partials
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

wp_nonce_field( 'examhub_save_exam_taxonomies', 'examhub_exam_taxonomies_nonce' );

$examhub_taxonomies = array(
	'examhub_level'     => __( 'مقطع', 'examhub' ),
	'examhub_grade'     => __( 'پایه', 'examhub' ),
	'examhub_field'     => __( 'رشته', 'examhub' ),
	'examhub_subject'   => __( 'درس', 'examhub' ),
	'examhub_year'      => __( 'سال', 'examhub' ),
	'examhub_term'      => __( 'نوبت', 'examhub' ),
	'examhub_exam_type' => __( 'نوع آزمون', 'examhub' ),
);

// Each of the four structure facets cascades into the next — used by
// admin/js/examhub-admin-taxonomies.js to re-fetch a select's options
// whenever the select it depends on changes. مقطع has no parent of its
// own but still needs the plain "cascade source" marker so changing it
// triggers پایه's refresh.
$examhub_cascade_taxonomies = array_keys( Examhub_Query::STRUCTURE_TAXONOMIES );
?>
<div class="examhub-taxonomies">
	<?php foreach ( $examhub_taxonomies as $taxonomy => $label ) : ?>
		<?php
		// term_id => name (all ExamHub taxonomies are flat).
		$choices = Examhub_Query::get_term_choices( $taxonomy );

		$assigned   = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
		$current_id = ( ! is_wp_error( $assigned ) && ! empty( $assigned ) ) ? (int) $assigned[0] : 0;

		$field_id   = 'examhub_tax_' . $taxonomy;
		$manage_url = add_query_arg(
			array(
				'taxonomy'  => $taxonomy,
				'post_type' => 'examhub_exam',
			),
			admin_url( 'edit-tags.php' )
		);

		$is_cascade_source = in_array( $taxonomy, $examhub_cascade_taxonomies, true );
		$cascade_parent    = Examhub_Query::get_parent_taxonomy( $taxonomy );
		?>
		<p class="examhub-tax-field">
			<label for="<?php echo esc_attr( $field_id ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label>

			<?php if ( ! empty( $choices ) || $cascade_parent ) : ?>
				<select
					name="<?php echo esc_attr( $field_id ); ?>"
					id="<?php echo esc_attr( $field_id ); ?>"
					class="widefat"
					<?php if ( $is_cascade_source ) : ?>
						data-examhub-cascade="<?php echo esc_attr( $taxonomy ); ?>"
					<?php endif; ?>
					<?php if ( $cascade_parent ) : ?>
						data-examhub-cascade-parent="<?php echo esc_attr( $cascade_parent ); ?>"
					<?php endif; ?>
				>
					<option value="0"><?php esc_html_e( '— انتخاب نشده —', 'examhub' ); ?></option>
					<?php foreach ( $choices as $term_id => $name ) : ?>
						<option value="<?php echo esc_attr( $term_id ); ?>" <?php selected( $current_id, $term_id ); ?>>
							<?php echo esc_html( $name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<span class="description">
					<?php
					printf(
						/* translators: %s: link to the taxonomy management screen. */
						esc_html__( 'هنوز موردی اضافه نشده‌است. %s', 'examhub' ),
						'<a href="' . esc_url( $manage_url ) . '">' . esc_html__( 'افزودن', 'examhub' ) . '</a>'
					);
					?>
				</span>
			<?php endif; ?>
		</p>
	<?php endforeach; ?>

	<p class="examhub-tax-help description">
		<?php esc_html_e( 'برای افزودن یا ویرایش دسته‌ها از زیرمنوهای ExamHub (مقطع، پایه، رشته، درس، سال، نوبت، نوع آزمون) استفاده کنید. انتخاب مقطع گزینه‌های پایه را محدود می‌کند و به همین ترتیب تا درس.', 'examhub' ); ?>
	</p>
</div>
