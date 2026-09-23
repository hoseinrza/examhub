<?php

/**
 * Handles exam file downloads: builds tracked download links, verifies them,
 * atomically increments the per-exam download counters, and streams the file.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/includes
 */
class Examhub_Download_Handler {

	/**
	 * Maps the public "type" parameter to the post meta keys that store
	 * the attachment ID and the download counter for that file.
	 *
	 * @since 1.0.0
	 * @var   array<string,array<string,string>>
	 */
	const TYPE_META_MAP = array(
		'questions' => array(
			'file'    => '_examhub_questions_file',
			'url'     => '_examhub_questions_url',
			'counter' => '_examhub_questions_downloads',
		),
		'answers'   => array(
			'file'    => '_examhub_answers_file',
			'url'     => '_examhub_answers_url',
			'counter' => '_examhub_answers_downloads',
		),
	);

	/**
	 * Build a nonce-protected admin-ajax download URL for an exam file.
	 *
	 * Using admin-ajax.php avoids the need for custom rewrite rules (and the
	 * activation-time flush that would entail) while still working for both
	 * logged-in and anonymous visitors.
	 *
	 * @since 1.0.0
	 * @param  int    $exam_id The exam post ID.
	 * @param  string $type    Either "questions" or "answers".
	 * @return string
	 */
	public static function get_download_url( $exam_id, $type ) {

		if ( ! isset( self::TYPE_META_MAP[ $type ] ) ) {
			return '';
		}

		return add_query_arg(
			array(
				'action'  => 'examhub_download',
				'exam_id' => (int) $exam_id,
				'type'    => $type,
				'_wpnonce' => wp_create_nonce( 'examhub_download_' . $exam_id . '_' . $type ),
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * AJAX callback: validate the request, count the download, and stream the file.
	 *
	 * Registered for both wp_ajax_examhub_download and wp_ajax_nopriv_examhub_download
	 * since exam downloads are open to every visitor.
	 *
	 * @since 1.0.0
	 */
	public function handle_download() {

		$exam_id = isset( $_GET['exam_id'] ) ? absint( $_GET['exam_id'] ) : 0;
		$type    = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';

		if ( ! $exam_id || ! isset( self::TYPE_META_MAP[ $type ] ) ) {
			wp_die( esc_html__( 'Invalid download request.', 'examhub' ), '', array( 'response' => 400 ) );
		}

		check_ajax_referer( 'examhub_download_' . $exam_id . '_' . $type, '_wpnonce' );

		if ( 'examhub_exam' !== get_post_type( $exam_id ) || 'publish' !== get_post_status( $exam_id ) ) {
			wp_die( esc_html__( 'Exam not found.', 'examhub' ), '', array( 'response' => 404 ) );
		}

		$meta = self::TYPE_META_MAP[ $type ];

		// Check for file-based resource first
		$attachment_id = (int) get_post_meta( $exam_id, $meta['file'], true );

		if ( $attachment_id ) {
			// Make sure the attachment is genuinely the file attached to this exam.
			if ( (int) get_post_field( 'post_parent', $attachment_id ) !== (int) $exam_id ) {
				wp_die( esc_html__( 'File not available.', 'examhub' ), '', array( 'response' => 404 ) );
			}

			$file_path = get_attached_file( $attachment_id );

			if ( ! $file_path || ! file_exists( $file_path ) ) {
				wp_die( esc_html__( 'File not available.', 'examhub' ), '', array( 'response' => 404 ) );
			}

			self::increment_counter( $exam_id, $meta['counter'] );
			self::stream_file( $file_path, get_the_title( $exam_id ) . '-' . $type );
			return;
		}

		// Check for external URL resource
		$external_url = (string) get_post_meta( $exam_id, $meta['url'], true );

		if ( $external_url ) {
			self::increment_counter( $exam_id, $meta['counter'] );
			self::stream_remote_file( esc_url_raw( $external_url ), get_the_title( $exam_id ) . '-' . $type );
			return;
		}

		wp_die( esc_html__( 'File not available.', 'examhub' ), '', array( 'response' => 404 ) );
	}

	/**
	 * Atomically increment a numeric post meta counter.
	 *
	 * Uses a single UPDATE so concurrent downloads can't race and clobber
	 * each other's increment (which a read-modify-write with update_post_meta
	 * could under load); falls back to creating the row if it doesn't exist yet.
	 *
	 * @since 1.0.0
	 * @param int    $post_id  The exam post ID.
	 * @param string $meta_key The counter meta key to increment.
	 */
	private static function increment_counter( $post_id, $meta_key ) {

		global $wpdb;

		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = meta_value + 1 WHERE post_id = %d AND meta_key = %s",
				$post_id,
				$meta_key
			)
		);

		if ( ! $updated ) {
			add_post_meta( $post_id, $meta_key, 1, true );
		}
	}

	/**
	 * Stream a file to the browser as a forced download.
	 *
	 * Reading the file through PHP (rather than redirecting to the attachment
	 * URL) lets us force "Save As" with a friendly filename even for file
	 * types like PDF that browsers would otherwise open inline.
	 *
	 * @since 1.0.0
	 * @param string $file_path Absolute path to the file on disk.
	 * @param string $base_name Filename (without extension) to present to the user.
	 */
	private static function stream_file( $file_path, $base_name ) {

		$extension = pathinfo( $file_path, PATHINFO_EXTENSION );
		$filename  = sanitize_file_name( $base_name . ( $extension ? '.' . $extension : '' ) );

		nocache_headers();
		header( 'Content-Type: ' . ( wp_check_filetype( $file_path )['type'] ?: 'application/octet-stream' ) );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $file_path ) );

		// Stop WordPress from appending anything else to the response body.
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		readfile( $file_path ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		exit;
	}

	/**
	 * Fetch an externally-linked file and stream it to the browser as a forced
	 * download, the same way an uploaded file is served, instead of just
	 * redirecting the visitor to the link (which lets the browser open it
	 * inline — e.g. a PDF — rather than downloading it).
	 *
	 * Falls back to a plain redirect if the remote fetch fails, so a broken
	 * or slow external host never leaves the visitor with a dead end.
	 *
	 * @since 1.0.0
	 * @param string $url       The external file URL.
	 * @param string $base_name Filename (without extension) to present to the user.
	 */
	private static function stream_remote_file( $url, $base_name ) {

		$tmp_file = wp_tempnam( $url );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'  => 30,
				'stream'   => true,
				'filename' => $tmp_file,
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			wp_delete_file( $tmp_file );
			wp_redirect( $url );
			exit;
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$extension    = pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION );

		if ( ! $extension && $content_type ) {
			$extension = wp_check_filetype( 'file.' . preg_replace( '#^.*/#', '', $content_type ) )['ext'];
		}

		$filename = sanitize_file_name( $base_name . ( $extension ? '.' . $extension : '' ) );

		nocache_headers();
		header( 'Content-Type: ' . ( $content_type ?: 'application/octet-stream' ) );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $tmp_file ) );

		while ( ob_get_level() ) {
			ob_end_clean();
		}

		readfile( $tmp_file ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		wp_delete_file( $tmp_file );

		exit;
	}

}
