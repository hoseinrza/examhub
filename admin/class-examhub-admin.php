<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Examhub
 * @subpackage Examhub/admin
 * @author     Amirhossein Rezazadeh  <amir1382re@gmail.com>
 */
class Examhub_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// صفحه مدیریت سفارشی در اولین اجرای افزونه زیر منوی آزمون‌ها ثبت می‌شود.

	}

	/**
	 * Register the ExamHub administration dashboard beneath the exams menu.
	 *
	 * @since 1.2.0
	 */
	public function register_admin_menu() {

		add_submenu_page(
			'edit.php?post_type=examhub_exam',
			__( 'داشبورد آزمون‌ها', 'examhub' ),
			__( 'داشبورد', 'examhub' ),
			'edit_posts',
			'examhub-dashboard',
			array( $this, 'render_dashboard' )
		);

		// در منوی مدیریت فقط «پایه» و «رشته» به‌عنوان ساختار قابل مدیریت
		// نمایش داده شوند. سایر taxonomyها همچنان برای داده‌های داخلی و
		// فیلترهای افزونه ثبت می‌مانند و حذف نمی‌شوند.
		$hidden_taxonomies = array(
			'examhub_level',
			'examhub_subject',
			'examhub_year',
			'examhub_term',
			'examhub_exam_type',
		);

		foreach ( $hidden_taxonomies as $taxonomy ) {
			remove_submenu_page(
				'edit.php?post_type=examhub_exam',
				'edit-tags.php?taxonomy=' . $taxonomy . '&post_type=examhub_exam'
			);
		}
	}

	/**
	 * Render the ExamHub administration dashboard.
	 *
	 * @since 1.2.0
	 */
	public function render_dashboard() {

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'شما اجازه دسترسی به این صفحه را ندارید.', 'examhub' ) );
		}

		$counts           = wp_count_posts( 'examhub_exam' );
		$total            = isset( $counts->publish ) ? (int) $counts->publish : 0;
		$drafts           = isset( $counts->draft ) ? (int) $counts->draft : 0;
		$pending          = isset( $counts->pending ) ? (int) $counts->pending : 0;
		$featured         = $this->get_featured_count();
		$total_downloads  = $this->get_total_downloads();
		$recent_exams     = get_posts(
			array(
				'post_type'      => 'examhub_exam',
				'post_status'    => array( 'publish', 'draft', 'pending' ),
				'posts_per_page' => 7,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$taxonomy_items = array(
			'examhub_grade' => 'پایه',
			'examhub_field' => 'رشته',
		);
		?>
		<div class="wrap examhub-dashboard-wrap">
			<div class="examhub-dashboard-hero">
				<div>
					<span class="examhub-dashboard-kicker"><?php esc_html_e( 'مرکز مدیریت بانک آزمون', 'examhub' ); ?></span>
					<h1><?php esc_html_e( 'داشبورد آزمون‌ها', 'examhub' ); ?></h1>
					<p><?php esc_html_e( 'مدیریت سریع آزمون‌ها، دسته‌بندی‌ها و فایل‌های سوالات و پاسخنامه از یک صفحه.', 'examhub' ); ?></p>
				</div>
				<div class="examhub-dashboard-actions">
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=examhub_exam' ) ); ?>"><?php esc_html_e( 'افزودن آزمون جدید', 'examhub' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=examhub_exam' ) ); ?>"><?php esc_html_e( 'مدیریت آزمون‌ها', 'examhub' ); ?></a>
				</div>
			</div>

			<div class="examhub-dashboard-stats">
				<div class="examhub-stat-card"><span class="examhub-stat-icon">آ</span><div><strong><?php echo esc_html( number_format_i18n( $total ) ); ?></strong><span><?php esc_html_e( 'آزمون منتشرشده', 'examhub' ); ?></span></div></div>
				<div class="examhub-stat-card"><span class="examhub-stat-icon">ن</span><div><strong><?php echo esc_html( number_format_i18n( $drafts ) ); ?></strong><span><?php esc_html_e( 'پیش‌نویس', 'examhub' ); ?></span></div></div>
				<div class="examhub-stat-card"><span class="examhub-stat-icon">ر</span><div><strong><?php echo esc_html( number_format_i18n( $pending ) ); ?></strong><span><?php esc_html_e( 'در انتظار بررسی', 'examhub' ); ?></span></div></div>
				<div class="examhub-stat-card"><span class="examhub-stat-icon">★</span><div><strong><?php echo esc_html( number_format_i18n( $featured ) ); ?></strong><span><?php esc_html_e( 'آزمون ویژه', 'examhub' ); ?></span></div></div>
				<div class="examhub-stat-card"><span class="examhub-stat-icon">↓</span><div><strong><?php echo esc_html( number_format_i18n( $total_downloads ) ); ?></strong><span><?php esc_html_e( 'مجموع دانلود', 'examhub' ); ?></span></div></div>
			</div>

			<div class="examhub-dashboard-grid">
				<section class="examhub-panel">
					<div class="examhub-panel-head">
						<div><h2><?php esc_html_e( 'آخرین آزمون‌ها', 'examhub' ); ?></h2><p><?php esc_html_e( 'آخرین موارد ثبت یا ویرایش‌شده در بانک آزمون.', 'examhub' ); ?></p></div>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=examhub_exam' ) ); ?>"><?php esc_html_e( 'مشاهده همه', 'examhub' ); ?></a>
					</div>
					<?php if ( empty( $recent_exams ) ) : ?>
						<div class="examhub-empty-state"><strong><?php esc_html_e( 'هنوز آزمونی ثبت نشده است.', 'examhub' ); ?></strong><a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=examhub_exam' ) ); ?>"><?php esc_html_e( 'اولین آزمون را اضافه کنید', 'examhub' ); ?></a></div>
					<?php else : ?>
						<div class="examhub-table-wrap">
							<table class="widefat fixed striped examhub-dashboard-table">
								<thead><tr><th><?php esc_html_e( 'عنوان آزمون', 'examhub' ); ?></th><th><?php esc_html_e( 'وضعیت', 'examhub' ); ?></th><th><?php esc_html_e( 'ویژه', 'examhub' ); ?></th><th><?php esc_html_e( 'دانلود', 'examhub' ); ?></th><th><?php esc_html_e( 'ویرایش', 'examhub' ); ?></th></tr></thead>
								<tbody>
								<?php foreach ( $recent_exams as $exam ) : ?>
									<?php
									$status_labels = array( 'publish' => 'منتشرشده', 'draft' => 'پیش‌نویس', 'pending' => 'در انتظار بررسی' );
									$q_downloads = (int) get_post_meta( $exam->ID, '_examhub_questions_downloads', true );
									$a_downloads = (int) get_post_meta( $exam->ID, '_examhub_answers_downloads', true );
									?>
									<tr>
										<td><strong><?php echo esc_html( get_the_title( $exam ) ?: __( '(بدون عنوان)', 'examhub' ) ); ?></strong></td>
										<td><span class="examhub-status examhub-status--<?php echo esc_attr( $exam->post_status ); ?>"><?php echo esc_html( $status_labels[ $exam->post_status ] ?? $exam->post_status ); ?></span></td>
										<td><?php echo get_post_meta( $exam->ID, '_examhub_featured', true ) ? '<span class="examhub-star">★</span>' : '—'; ?></td>
										<td><?php echo esc_html( number_format_i18n( $q_downloads + $a_downloads ) ); ?></td>
										<td><a class="button button-small" href="<?php echo esc_url( get_edit_post_link( $exam->ID, '' ) ); ?>"><?php esc_html_e( 'ویرایش', 'examhub' ); ?></a></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</section>

				<section class="examhub-panel">
					<div class="examhub-panel-head"><div><h2><?php esc_html_e( 'مدیریت دسته‌بندی‌ها', 'examhub' ); ?></h2><p><?php esc_html_e( 'دسترسی سریع به فیلترهای ساختاری و دسته‌بندی آزمون‌ها.', 'examhub' ); ?></p></div></div>
					<div class="examhub-taxonomy-grid">
						<?php foreach ( $taxonomy_items as $taxonomy => $label ) : ?>
							<?php
							$count = wp_count_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
							$url   = add_query_arg( array( 'taxonomy' => $taxonomy, 'post_type' => 'examhub_exam' ), admin_url( 'edit-tags.php' ) );
							?>
							<a class="examhub-taxonomy-card" href="<?php echo esc_url( $url ); ?>"><span><?php echo esc_html( $label ); ?></span><strong><?php echo esc_html( number_format_i18n( (int) $count ) ); ?></strong><small><?php esc_html_e( 'مدیریت', 'examhub' ); ?> ←</small></a>
						<?php endforeach; ?>
					</div>
					<div class="examhub-quick-links">
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=examhub_exam' ) ); ?>">＋ <?php esc_html_e( 'ثبت آزمون', 'examhub' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=examhub_exam' ) ); ?>">☷ <?php esc_html_e( 'فهرست آزمون‌ها', 'examhub' ); ?></a>
					</div>
				</section>
			</div>

			<div class="examhub-dashboard-footer">
				<span><?php printf( esc_html__( 'ExamHub نسخه %s', 'examhub' ), esc_html( $this->version ) ); ?></span>
				<span><?php esc_html_e( 'مدیریت بانک آزمون برای تیم محتوا', 'examhub' ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Count featured exams.
	 *
	 * @return int
	 */
	private function get_featured_count() {
		return (int) ( new WP_Query( array(
			'post_type'      => 'examhub_exam',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
			'meta_query'     => array(
				array(
					'key'   => '_examhub_featured',
					'value' => '1',
				),
			),
		) ) )->found_posts;
	}

	/**
	 * Sum question and answer downloads.
	 *
	 * @return int
	 */
	private function get_total_downloads() {
		global $wpdb;

		$value = $wpdb->get_var(
			"SELECT COALESCE(SUM(CAST(meta_value AS UNSIGNED)), 0) FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'examhub_exam' AND pm.meta_key IN ('_examhub_questions_downloads','_examhub_answers_downloads')"
		);

		return (int) $value;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * Only loaded on the ExamHub screens (the exam list table and the exam
	 * edit screen), the only places its rules — the file-picker metabox and
	 * the download-stats columns — ever apply.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		$screen = get_current_screen();
		$is_dashboard = isset( $_GET['page'] ) && 'examhub-dashboard' === sanitize_key( wp_unslash( $_GET['page'] ) );
		$is_taxonomy  = $screen && ! empty( $screen->taxonomy ) && in_array( $screen->taxonomy, array_keys( Examhub_Query::TAXONOMY_MAP ), true );

		if ( ! $screen || ( 'examhub_exam' !== $screen->post_type && ! $is_dashboard && ! $is_taxonomy ) ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/examhub-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * Only loaded on the exam edit screen since it powers the
	 * media-library file pickers in the "exam details" metabox.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		$screen = get_current_screen();

		if ( ! $screen || 'examhub_exam' !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/examhub-admin.js', array( 'jquery' ), $this->version, false );

		wp_enqueue_script(
			$this->plugin_name . '-taxonomies',
			plugin_dir_url( __FILE__ ) . 'js/examhub-admin-taxonomies.js',
			array( 'jquery' ),
			$this->version,
			false
		);

		wp_localize_script(
			$this->plugin_name,
			'examhubAdmin',
			array(
				'selectFileLabel'         => __( 'انتخاب فایل', 'examhub' ),
				'useFileLabel'            => __( 'استفاده از این فایل', 'examhub' ),
				'noFileLabel'             => __( 'فایلی انتخاب نشده است.', 'examhub' ),
				'resourceRequiredLabel'   => __( 'برای هر بخش (سوالات/پاسخنامه) باید یا فایل آپلود کنید یا آدرس خارجی وارد کنید.', 'examhub' ),
			)
		);

		// A separate global (not reusing 'examhubAdmin'): wp_localize_script()
		// prints "var <name> = {...}" after its handle's script tag, and a
		// second call with the SAME name on a different handle would
		// overwrite — not merge with — the first, silently breaking
		// examhub-admin.js's media-picker labels.
		wp_localize_script(
			$this->plugin_name . '-taxonomies',
			'examhubAdminTaxonomies',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( Examhub_Ajax::NONCE_ACTION ),
				'unselectedLabel' => __( '— انتخاب نشده —', 'examhub' ),
			)
		);

	}

	/**
	 * Register the "exam details" metabox on the exam edit screen.
	 *
	 * @since 1.0.0
	 */
	public function add_exam_meta_boxes() {

		add_meta_box(
			'examhub-exam-details',
			__( 'جزئیات آزمون', 'examhub' ),
			array( $this, 'render_exam_details_metabox' ),
			'examhub_exam',
			'normal',
			'high'
		);

		add_meta_box(
			'examhub-exam-taxonomies',
			__( 'دسته‌بندی آزمون', 'examhub' ),
			array( $this, 'render_exam_taxonomies_metabox' ),
			'examhub_exam',
			'side',
			'high'
		);
	}

	/**
	 * Render the "exam details" metabox.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The exam post being edited.
	 */
	public function render_exam_details_metabox( $post ) {

		require plugin_dir_path( __FILE__ ) . 'partials/metabox-exam-details.php';
	}

	/**
	 * Render the "classification" metabox: one single-select dropdown per
	 * taxonomy, replacing WordPress's default free-text tag boxes (which are
	 * disabled via meta_box_cb => false in Examhub_Post_Types).
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The exam post being edited.
	 */
	public function render_exam_taxonomies_metabox( $post ) {

		require plugin_dir_path( __FILE__ ) . 'partials/metabox-exam-taxonomies.php';
	}

	/**
	 * Persist the "exam details" metabox fields (uploaded files + featured flag).
	 *
	 * @since 1.0.0
	 * @param int $post_id The exam post ID being saved.
	 */
	public function save_exam_details( $post_id ) {

		if ( ! isset( $_POST['examhub_exam_details_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['examhub_exam_details_nonce'] ) ), 'examhub_save_exam_details' )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'examhub_exam' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$resource_fields = array(
			'examhub_questions_file' => array(
				'file_meta' => '_examhub_questions_file',
				'url_meta'  => '_examhub_questions_url',
				'url_field' => 'examhub_questions_url',
			),
			'examhub_answers_file'   => array(
				'file_meta' => '_examhub_answers_file',
				'url_meta'  => '_examhub_answers_url',
				'url_field' => 'examhub_answers_url',
			),
		);

		foreach ( $resource_fields as $field => $metas ) {

			$attachment_id = isset( $_POST[ $field ] ) ? absint( $_POST[ $field ] ) : 0;
			$external_url  = isset( $_POST[ $metas['url_field'] ] ) ? esc_url_raw( wp_unslash( $_POST[ $metas['url_field'] ] ) ) : '';

			if ( $attachment_id ) {
				update_post_meta( $post_id, $metas['file_meta'], $attachment_id );
				delete_post_meta( $post_id, $metas['url_meta'] );
				wp_update_post( array( 'ID' => $attachment_id, 'post_parent' => $post_id ) );
			} elseif ( $external_url ) {
				delete_post_meta( $post_id, $metas['file_meta'] );
				update_post_meta( $post_id, $metas['url_meta'], $external_url );
			} else {
				delete_post_meta( $post_id, $metas['file_meta'] );
				delete_post_meta( $post_id, $metas['url_meta'] );
			}
		}

		update_post_meta( $post_id, '_examhub_featured', ! empty( $_POST['examhub_featured'] ) ? 1 : 0 );
	}

	/**
	 * Persist the "classification" metabox: the single term chosen in each
	 * taxonomy dropdown (or none, which clears that taxonomy for the exam).
	 *
	 * Term IDs are used rather than names so wp_set_object_terms() can never
	 * accidentally create a new term from the submitted value.
	 *
	 * @since 1.0.0
	 * @param int $post_id The exam post ID being saved.
	 */
	public function save_exam_taxonomies( $post_id ) {

		if ( ! isset( $_POST['examhub_exam_taxonomies_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['examhub_exam_taxonomies_nonce'] ) ), 'examhub_save_exam_taxonomies' )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'examhub_exam' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( array_values( Examhub_Query::TAXONOMY_MAP ) as $taxonomy ) {

			$field   = 'examhub_tax_' . $taxonomy;
			$term_id = isset( $_POST[ $field ] ) ? absint( $_POST[ $field ] ) : 0;

			wp_set_object_terms( $post_id, $term_id ? array( $term_id ) : array(), $taxonomy, false );
		}
	}

	/**
	 * Add the download-stats columns to the exams list table.
	 *
	 * @since 1.0.0
	 * @param  array $columns Existing column list.
	 * @return array
	 */
	public function set_exam_columns( $columns ) {

		$inserted = array(
			'examhub_featured'  => __( 'ویژه', 'examhub' ),
			'examhub_questions' => __( 'دانلود سوالات', 'examhub' ),
			'examhub_answers'   => __( 'دانلود پاسخنامه', 'examhub' ),
		);

		// Insert the new columns right before the "date" column.
		$offset = array_search( 'date', array_keys( $columns ), true );

		if ( false === $offset ) {
			return array_merge( $columns, $inserted );
		}

		return array_slice( $columns, 0, $offset, true ) + $inserted + array_slice( $columns, $offset, null, true );
	}

	/**
	 * Render the custom exam columns.
	 *
	 * @since 1.0.0
	 * @param string $column  Column key being rendered.
	 * @param int    $post_id Exam post ID for the current row.
	 */
	public function render_exam_column( $column, $post_id ) {

		switch ( $column ) {

			case 'examhub_featured':
				if ( get_post_meta( $post_id, '_examhub_featured', true ) ) {
					echo '<span class="examhub-featured-flag">' . esc_html__( 'ویژه', 'examhub' ) . '</span>';
				} else {
					echo '&#8212;';
				}
				break;

			case 'examhub_questions':
				echo esc_html( number_format_i18n( (int) get_post_meta( $post_id, '_examhub_questions_downloads', true ) ) );
				break;

			case 'examhub_answers':
				echo esc_html( number_format_i18n( (int) get_post_meta( $post_id, '_examhub_answers_downloads', true ) ) );
				break;
		}
	}

}
