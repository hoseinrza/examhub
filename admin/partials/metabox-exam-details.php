<?php
/**
 * "Exam details" metabox markup: file uploaders, featured flag, download stats.
 *
 * Expects $post (the exam being edited) in scope.
 *
 * @package    Examhub
 * @subpackage Examhub/admin/partials
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

wp_nonce_field( 'examhub_save_exam_details', 'examhub_exam_details_nonce' );

$questions_id   = (int) get_post_meta( $post->ID, '_examhub_questions_file', true );
$answers_id     = (int) get_post_meta( $post->ID, '_examhub_answers_file', true );
$questions_url  = (string) get_post_meta( $post->ID, '_examhub_questions_url', true );
$answers_url    = (string) get_post_meta( $post->ID, '_examhub_answers_url', true );
$featured       = (bool) get_post_meta( $post->ID, '_examhub_featured', true );

$questions_downloads = (int) get_post_meta( $post->ID, '_examhub_questions_downloads', true );
$answers_downloads   = (int) get_post_meta( $post->ID, '_examhub_answers_downloads', true );

$file_rows = array(
	'questions' => array(
		'label'     => __( 'سوالات', 'examhub' ),
		'field'     => 'examhub_questions_file',
		'url_field' => 'examhub_questions_url',
		'id'        => $questions_id,
		'url'       => $questions_url,
		'downloads' => $questions_downloads,
	),
	'answers'   => array(
		'label'     => __( 'پاسخنامه', 'examhub' ),
		'field'     => 'examhub_answers_file',
		'url_field' => 'examhub_answers_url',
		'id'        => $answers_id,
		'url'       => $answers_url,
		'downloads' => $answers_downloads,
	),
);
?>
<div class="examhub-metabox">
	<table class="form-table" role="presentation">

		<?php foreach ( $file_rows as $row ) : ?>
			<?php
			$file_name = $row['id'] ? basename( get_attached_file( $row['id'] ) ) : '';
			$has_file = ! empty( $row['id'] );
			$has_url = ! empty( $row['url'] );
			$use_file = $has_file && ! $has_url;
			?>
			<tr>
				<th scope="row"><label><?php echo esc_html( $row['label'] ); ?></label></th>
				<td class="examhub-resource-field" data-resource-type="<?php echo $use_file ? 'file' : 'url'; ?>">
					<div class="examhub-resource-tabs">
						<button type="button" class="examhub-resource-tab examhub-resource-tab--file <?php echo $use_file ? 'active' : ''; ?>" data-tab="file">
							<?php esc_html_e( 'آپلود فایل', 'examhub' ); ?>
						</button>
						<button type="button" class="examhub-resource-tab examhub-resource-tab--url <?php echo ! $use_file ? 'active' : ''; ?>" data-tab="url">
							<?php esc_html_e( 'آدرس خارجی', 'examhub' ); ?>
						</button>
					</div>

					<div class="examhub-resource-content">
						<div class="examhub-resource-panel examhub-resource-panel--file <?php echo $use_file ? 'active' : ''; ?>">
							<input type="hidden" name="<?php echo esc_attr( $row['field'] ); ?>" class="examhub-file-input" value="<?php echo esc_attr( $row['id'] ); ?>" />
							<span class="examhub-file-name"><?php echo $file_name ? esc_html( $file_name ) : esc_html__( 'فایلی انتخاب نشده است.', 'examhub' ); ?></span>
							<button type="button" class="button examhub-file-select"><?php esc_html_e( 'انتخاب فایل', 'examhub' ); ?></button>
							<button type="button" class="button examhub-file-remove" <?php echo $has_file ? '' : 'style="display:none"'; ?>><?php esc_html_e( 'حذف', 'examhub' ); ?></button>
						</div>

						<div class="examhub-resource-panel examhub-resource-panel--url <?php echo ! $use_file ? 'active' : ''; ?>">
							<input type="url" name="<?php echo esc_attr( $row['url_field'] ); ?>" class="examhub-url-input regular-text" placeholder="https://example.com/file.pdf" value="<?php echo esc_url( $row['url'] ); ?>" />
							<p class="description">
								<?php esc_html_e( 'آدرس مستقیم فایل یا درایو ابری (Google Drive، Dropbox، وغیره)', 'examhub' ); ?>
							</p>
						</div>
					</div>

					<p class="description">
						<?php
						printf(
							/* translators: %s: number of times this file has been downloaded. */
							esc_html__( 'تعداد دانلود تاکنون: %s', 'examhub' ),
							'<strong>' . esc_html( number_format_i18n( $row['downloads'] ) ) . '</strong>'
						);
						?>
					</p>
				</td>
			</tr>
		<?php endforeach; ?>

		<tr>
			<th scope="row"><?php esc_html_e( 'نمایش ویژه', 'examhub' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="examhub_featured" value="1" <?php checked( $featured ); ?> />
					<?php esc_html_e( 'این آزمون در ویجت «آزمون‌های پیشنهادی» و با نشان «ویژه» نمایش داده شود.', 'examhub' ); ?>
				</label>
			</td>
		</tr>

	</table>
</div>
