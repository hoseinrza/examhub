<?php

/**
 * Registers the exam custom post type, its taxonomies, and the term icon field.
 *
 * @link       https://github.com/hoseinrza/final-exam-bank
 * @since      1.0.0
 *
 * @package    Examhub
 * @subpackage Examhub/includes
 */
class Examhub_Post_Types {

	/**
	 * The taxonomies that get the optional "icon" term meta field
	 * (used by the Category Showcase / Mega Library widgets).
	 *
	 * @since 1.0.0
	 * @var   string[]
	 */
	const ICON_TAXONOMIES = array( 'examhub_level', 'examhub_grade', 'examhub_field', 'examhub_subject', 'examhub_exam_type' );

	/**
	 * Register the "exam" custom post type.
	 *
	 * The post type is not publicly routable: exams are only ever displayed
	 * through the plugin's shortcodes/widgets, and the "view" action on a
	 * card opens the attached file directly rather than a single template.
	 *
	 * @since 1.0.0
	 */
	public function register_post_type() {

		$labels = array(
			'name'               => __( 'Exams', 'examhub' ),
			'singular_name'      => __( 'Exam', 'examhub' ),
			'menu_name'          => __( 'ExamHub', 'examhub' ),
			'add_new'            => __( 'Add New', 'examhub' ),
			'add_new_item'       => __( 'Add New Exam', 'examhub' ),
			'edit_item'          => __( 'Edit Exam', 'examhub' ),
			'new_item'           => __( 'New Exam', 'examhub' ),
			'view_item'          => __( 'View Exam', 'examhub' ),
			'search_items'       => __( 'Search Exams', 'examhub' ),
			'not_found'          => __( 'No exams found', 'examhub' ),
			'not_found_in_trash' => __( 'No exams found in trash', 'examhub' ),
			'all_items'          => __( 'All Exams', 'examhub' ),
		);

		$args = array(
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-portfolio',
			'capability_type' => 'post',
			'hierarchical'    => false,
			'supports'        => array( 'title', 'thumbnail' ),
			'has_archive'     => false,
			'rewrite'         => false,
			'query_var'       => false,
			'show_in_rest'    => false,
		);

		register_post_type( 'examhub_exam', $args );
	}

	/**
	 * Register the exam facet taxonomies.
	 *
	 * The academic facets (level › grade › field › subject) each live in their
	 * own FLAT taxonomy — examhub_level/examhub_grade/examhub_field/examhub_subject
	 * — so an exam is tagged with one term per facet, and the four are queried
	 * independently (AND-combined). Dependency between them for cascading-dropdown
	 * purposes is tracked separately via term meta (see Examhub_Query::STRUCTURE_PARENT_META),
	 * not native WP term hierarchy. Year, term/session, and exam type stay flat,
	 * independent facets as before.
	 *
	 * (The legacy examhub_grade/examhub_field/examhub_subject taxonomy slugs are
	 * reused here as the new flat taxonomies' slugs — they were previously
	 * de-registered placeholders for Examhub_Migration's old data; the single
	 * hierarchical examhub_structure taxonomy they fed into is now itself the
	 * retired one, split by Examhub_Structure_Split_Migration.)
	 *
	 * @since 1.0.0
	 */
	public function register_taxonomies() {

		$taxonomies = array(
			'examhub_level'     => array(
				'name'          => __( 'Levels', 'examhub' ),
				'singular_name' => __( 'Level', 'examhub' ),
				'menu_name'     => __( 'مقطع', 'examhub' ),
				'hierarchical'  => false,
			),
			'examhub_grade'     => array(
				'name'          => __( 'Grades', 'examhub' ),
				'singular_name' => __( 'Grade', 'examhub' ),
				'menu_name'     => __( 'پایه', 'examhub' ),
				'hierarchical'  => false,
			),
			'examhub_field'     => array(
				'name'          => __( 'Fields', 'examhub' ),
				'singular_name' => __( 'Field', 'examhub' ),
				'menu_name'     => __( 'رشته', 'examhub' ),
				'hierarchical'  => false,
			),
			'examhub_subject'   => array(
				'name'          => __( 'Subjects', 'examhub' ),
				'singular_name' => __( 'Subject', 'examhub' ),
				'menu_name'     => __( 'درس', 'examhub' ),
				'hierarchical'  => false,
			),
			'examhub_year'      => array(
				'name'          => __( 'Years', 'examhub' ),
				'singular_name' => __( 'Year', 'examhub' ),
				'menu_name'     => __( 'Years (سال)', 'examhub' ),
				'hierarchical'  => false,
			),
			'examhub_term'      => array(
				'name'          => __( 'Terms', 'examhub' ),
				'singular_name' => __( 'Term', 'examhub' ),
				'menu_name'     => __( 'Terms (نوبت)', 'examhub' ),
				'hierarchical'  => false,
			),
			'examhub_exam_type' => array(
				'name'          => __( 'Exam Types', 'examhub' ),
				'singular_name' => __( 'Exam Type', 'examhub' ),
				'menu_name'     => __( 'نوع آزمون', 'examhub' ),
				'hierarchical'  => false,
			),
		);

		foreach ( $taxonomies as $taxonomy => $names ) {

			$labels = array(
				'name'              => $names['name'],
				'singular_name'     => $names['singular_name'],
				'menu_name'         => $names['menu_name'],
				'search_items'      => sprintf( __( 'Search %s', 'examhub' ), $names['name'] ),
				'all_items'         => sprintf( __( 'All %s', 'examhub' ), $names['name'] ),
				'parent_item'       => sprintf( __( 'Parent %s', 'examhub' ), $names['singular_name'] ),
				'parent_item_colon' => sprintf( __( 'Parent %s:', 'examhub' ), $names['singular_name'] ),
				'edit_item'         => sprintf( __( 'Edit %s', 'examhub' ), $names['singular_name'] ),
				'update_item'       => sprintf( __( 'Update %s', 'examhub' ), $names['singular_name'] ),
				'add_new_item'      => sprintf( __( 'Add New %s', 'examhub' ), $names['singular_name'] ),
				'new_item_name'     => sprintf( __( 'New %s Name', 'examhub' ), $names['singular_name'] ),
			);

			register_taxonomy(
				$taxonomy,
				array( 'examhub_exam' ),
				array(
					'labels'            => $labels,
					'hierarchical'      => ! empty( $names['hierarchical'] ),
					'public'            => false,
					'show_ui'           => true,
					'show_admin_column' => true,
					'show_in_nav_menus' => false,
					'show_in_rest'      => false,
					'query_var'         => false,
					'rewrite'           => false,
					// Hide WordPress's default tag/category box on the exam
					// editor; the "Classification" metabox replaces it with a
					// single-select dropdown per taxonomy (Examhub_Admin).
					'meta_box_cb'       => false,
				)
			);
		}
	}

	/**
	 * Output the "icon" field on the add-term screen for icon-enabled taxonomies.
	 *
	 * @since 1.0.0
	 * @param string $taxonomy The taxonomy slug being displayed.
	 */
	public function add_icon_field( $taxonomy ) {

		if ( ! in_array( $taxonomy, self::ICON_TAXONOMIES, true ) ) {
			return;
		}
		?>
		<div class="form-field term-examhub-icon-wrap">
			<label for="examhub-icon"><?php esc_html_e( 'Icon (emoji)', 'examhub' ); ?></label>
			<input type="text" name="examhub_icon" id="examhub-icon" value="" maxlength="10" />
			<p><?php esc_html_e( 'A short emoji shown next to this term in the Category Showcase widget, e.g. 📘', 'examhub' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Output the "icon" field on the edit-term screen for icon-enabled taxonomies.
	 *
	 * @since 1.0.0
	 * @param WP_Term $term     The term being edited.
	 * @param string  $taxonomy The taxonomy slug being displayed.
	 */
	public function edit_icon_field( $term, $taxonomy ) {

		if ( ! in_array( $taxonomy, self::ICON_TAXONOMIES, true ) ) {
			return;
		}

		$icon = get_term_meta( $term->term_id, 'examhub_icon', true );
		?>
		<tr class="form-field term-examhub-icon-wrap">
			<th scope="row"><label for="examhub-icon"><?php esc_html_e( 'Icon (emoji)', 'examhub' ); ?></label></th>
			<td>
				<input type="text" name="examhub_icon" id="examhub-icon" value="<?php echo esc_attr( $icon ); ?>" maxlength="10" />
				<p class="description"><?php esc_html_e( 'A short emoji shown next to this term in the Category Showcase widget, e.g. 📘', 'examhub' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Persist the "icon" term meta when a term is created or updated.
	 *
	 * @since 1.0.0
	 * @param int    $term_id  The term ID being saved.
	 * @param int    $tt_id    The term taxonomy ID.
	 * @param string $taxonomy The taxonomy slug.
	 */
	public function save_icon_field( $term_id, $tt_id, $taxonomy ) {

		if ( ! in_array( $taxonomy, self::ICON_TAXONOMIES, true ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		if ( isset( $_POST['examhub_icon'] ) ) {
			update_term_meta( $term_id, 'examhub_icon', sanitize_text_field( wp_unslash( $_POST['examhub_icon'] ) ) );
		}
	}

	/**
	 * Hook the icon field callbacks onto every icon-enabled taxonomy.
	 *
	 * created_{$taxonomy}/edited_{$taxonomy} do NOT pass the taxonomy slug as
	 * a third argument the way the generic created_term/edited_term hooks
	 * do — WordPress only fires them with ($term_id, $tt_id[, $args]), since
	 * the taxonomy is already baked into the hook name. Registering
	 * save_icon_field() directly with accepted_args=3 silently received
	 * garbage (or nothing) as $taxonomy, so the in_array() check inside it
	 * never matched and the icon never actually saved. Wrapping in a closure
	 * that already knows $taxonomy from this loop sidesteps the mismatch
	 * entirely instead of depending on what the hook happens to pass.
	 *
	 * @since 1.0.0
	 */
	public function register_icon_field_hooks() {

		foreach ( self::ICON_TAXONOMIES as $taxonomy ) {
			add_action( "{$taxonomy}_add_form_fields", array( $this, 'add_icon_field' ) );
			add_action( "{$taxonomy}_edit_form_fields", array( $this, 'edit_icon_field' ), 10, 2 );

			add_action(
				"created_{$taxonomy}",
				function ( $term_id ) use ( $taxonomy ) {
					$this->save_icon_field( $term_id, 0, $taxonomy );
				}
			);
			add_action(
				"edited_{$taxonomy}",
				function ( $term_id ) use ( $taxonomy ) {
					$this->save_icon_field( $term_id, 0, $taxonomy );
				}
			);
		}
	}

	/**
	 * Output the "Parent" field on the add-term screen for the three
	 * dependent structure facets (پایه/رشته/درس) — the only way, outside the
	 * one-time migration, to link a newly created term to its parent so it
	 * becomes reachable from the cascading admin/Elementor dropdowns and the
	 * Mega Library tree (Examhub_Query::get_dependent_terms()). Without this
	 * field, a category added after migration would never appear there.
	 *
	 * @since 1.0.0
	 * @param string $taxonomy The taxonomy slug being displayed.
	 */
	public function add_parent_structure_field( $taxonomy ) {

		$parent_taxonomy = Examhub_Query::get_parent_taxonomy( $taxonomy );

		if ( ! $parent_taxonomy ) {
			return;
		}
		?>
		<div class="form-field term-examhub-parent-wrap">
			<label for="examhub-parent-term"><?php esc_html_e( 'والد (برای فیلتر آبشاری)', 'examhub' ); ?></label>
			<select name="examhub_parent_term" id="examhub-parent-term">
				<option value="0"><?php esc_html_e( '— انتخاب نشده —', 'examhub' ); ?></option>
				<?php foreach ( Examhub_Query::get_term_choices( $parent_taxonomy ) as $term_id => $name ) : ?>
					<option value="<?php echo esc_attr( $term_id ); ?>"><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
			<p><?php esc_html_e( 'این مورد فقط وقتی والدش انتخاب شده باشد در فیلترهای آبشاری مقطع › پایه › رشته › درس نمایش داده می‌شود.', 'examhub' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Output the "Parent" field on the edit-term screen, prefilled with the
	 * term's current (primary) parent link, if any.
	 *
	 * @since 1.0.0
	 * @param WP_Term $term     The term being edited.
	 * @param string  $taxonomy The taxonomy slug being displayed.
	 */
	public function edit_parent_structure_field( $term, $taxonomy ) {

		$parent_taxonomy = Examhub_Query::get_parent_taxonomy( $taxonomy );

		if ( ! $parent_taxonomy ) {
			return;
		}

		$current = Examhub_Query::get_structure_parent( $term->term_id, $taxonomy );
		?>
		<tr class="form-field term-examhub-parent-wrap">
			<th scope="row"><label for="examhub-parent-term"><?php esc_html_e( 'والد (برای فیلتر آبشاری)', 'examhub' ); ?></label></th>
			<td>
				<select name="examhub_parent_term" id="examhub-parent-term">
					<option value="0"><?php esc_html_e( '— انتخاب نشده —', 'examhub' ); ?></option>
					<?php foreach ( Examhub_Query::get_term_choices( $parent_taxonomy ) as $term_id => $name ) : ?>
						<option value="<?php echo esc_attr( $term_id ); ?>" <?php selected( $current, $term_id ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'این مورد فقط وقتی والدش انتخاب شده باشد در فیلترهای آبشاری مقطع › پایه › رشته › درس نمایش داده می‌شود.', 'examhub' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Persist the "Parent" field, replacing whatever parent link(s) the term
	 * already had (a plain single-select can only ever represent one parent
	 * at a time; multi-parent links from migration remain possible at the
	 * data layer but aren't managed through this simple field).
	 *
	 * @since 1.0.0
	 * @param int    $term_id  The term ID being saved.
	 * @param int    $tt_id    The term taxonomy ID.
	 * @param string $taxonomy The taxonomy slug.
	 */
	public function save_parent_structure_field( $term_id, $tt_id, $taxonomy ) {

		if ( ! Examhub_Query::get_parent_taxonomy( $taxonomy ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$parent_id = isset( $_POST['examhub_parent_term'] ) ? absint( $_POST['examhub_parent_term'] ) : 0;

		Examhub_Query::replace_structure_parent( $term_id, $taxonomy, $parent_id );
	}

	/**
	 * Hook the "Parent" field callbacks onto the three dependent structure
	 * facets (پایه/رشته/درس) — examhub_level is the root and has no parent.
	 *
	 * Same hook-signature gotcha as register_icon_field_hooks(): created_{$taxonomy}
	 * /edited_{$taxonomy} don't pass $taxonomy as a third argument, so
	 * save_parent_structure_field() is wrapped in a closure that already
	 * knows it from this loop instead of trusting WordPress to supply it —
	 * without this, the parent link silently never saved.
	 *
	 * @since 1.0.0
	 */
	public function register_parent_field_hooks() {

		foreach ( array_keys( Examhub_Query::STRUCTURE_PARENT_META ) as $taxonomy ) {
			add_action( "{$taxonomy}_add_form_fields", array( $this, 'add_parent_structure_field' ) );
			add_action( "{$taxonomy}_edit_form_fields", array( $this, 'edit_parent_structure_field' ), 10, 2 );

			add_action(
				"created_{$taxonomy}",
				function ( $term_id ) use ( $taxonomy ) {
					$this->save_parent_structure_field( $term_id, 0, $taxonomy );
				}
			);
			add_action(
				"edited_{$taxonomy}",
				function ( $term_id ) use ( $taxonomy ) {
					$this->save_parent_structure_field( $term_id, 0, $taxonomy );
				}
			);
		}
	}

}
