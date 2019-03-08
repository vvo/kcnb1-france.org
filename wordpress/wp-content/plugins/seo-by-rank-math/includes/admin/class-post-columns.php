<?php
/**
 * The admin post columns functionality.
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Admin;

use RankMath\Runner;
use RankMath\Traits\Ajax;
use RankMath\Traits\Hooker;
use RankMath\Helper as GlobalHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Post_Columns class.
 */
class Post_Columns implements Runner {

	use Hooker, Ajax;

	/**
	 * Register hooks.
	 */
	public function hooks() {
		$this->action( 'admin_init', 'init' );
		$this->ajax( 'bulk_edit_columns', 'save' );
	}

	/**
	 * Intialize.
	 */
	public function init() {
		if ( ! GlobalHelper::has_cap( 'general' ) ) {
			return;
		}

		$this->register_post_columns();
		$this->register_media_columns();
		$this->action( 'admin_enqueue_scripts', 'enqueue' );
		$this->filter( 'pre_get_posts', 'posts_by_seo_filters' );
		$this->filter( 'parse_query', 'filter_by_focus_keywords' );
		$this->filter( 'restrict_manage_posts', 'add_seo_filter', 11 );
	}

	/**
	 * Register post column hooks
	 */
	private function register_post_columns() {
		foreach ( GlobalHelper::get_allowed_post_types() as $post_type ) {
			$this->filter( "views_edit-$post_type", 'add_pillar_content_filter_link' );

			$this->filter( "manage_{$post_type}_posts_columns", 'add_columns', 11 );
			$this->action( "manage_{$post_type}_posts_custom_column", 'columns_contents', 11, 2 );
			$this->filter( "manage_edit-{$post_type}_sortable_columns", 'sortable_columns', 11 );

			// Also make them hidden by default.
			$user_id        = get_current_user_id();
			$columns_hidden = (array) get_user_meta( $user_id, "manageedit-{$post_type}columnshidden", true );
			$maybe_hidden   = get_user_meta( $user_id, "manageedit-{$post_type}columnshidden_default", true );

			// Continue if default is already set.
			if ( $maybe_hidden ) {
				continue;
			}

			// Set it to hidden by default.
			$columns_hidden = array_unique( array_merge( $columns_hidden, array( 'rank_math_title', 'rank_math_description' ) ) );
			update_user_meta( $user_id, "manageedit-{$post_type}columnshidden", $columns_hidden );
			update_user_meta( $user_id, "manageedit-{$post_type}columnshidden_default", '1' );
		}
	}

	/**
	 * Register media column hooks
	 */
	private function register_media_columns() {
		if ( ! GlobalHelper::get_settings( 'titles.pt_attachment_bulk_editing' ) ) {
			return;
		}

		$this->filter( 'manage_media_columns', 'add_media_columns', 11 );
		$this->action( 'manage_media_custom_column', 'media_contents', 11, 2 );
	}

	/**
	 * Enqueue Styles and Scripts required by plugin.
	 */
	public function enqueue() {
		$screen = get_current_screen();

		$allowed_post_types   = GlobalHelper::get_allowed_post_types();
		$allowed_post_types[] = 'attachment';
		if ( ( 'edit' !== $screen->base && 'upload' !== $screen->base ) || ! in_array( $screen->post_type, $allowed_post_types ) ) {
			return;
		}

		wp_enqueue_style( 'rank-math-post-bulk-edit', rank_math()->plugin_url() . 'assets/admin/css/post-list.css', null, rank_math()->version );

		$allow_editing = GlobalHelper::get_settings( 'titles.pt_' . $screen->post_type . '_bulk_editing' );
		if ( ! $allow_editing || 'readonly' === $allow_editing ) {
			return;
		}

		wp_enqueue_script( 'rank-math-post-bulk-edit', rank_math()->plugin_url() . 'assets/admin/js/post-list.js', null, rank_math()->version, true );
		wp_localize_script( 'rank-math-post-bulk-edit', 'rankMath', array(
			'security'      => wp_create_nonce( 'rank-math-ajax-nonce' ),
			'bulkEditTitle' => esc_attr__( 'Bulk Edit This Field', 'rank-math' ),
			'buttonSaveAll' => esc_attr__( 'Save All Edits', 'rank-math' ),
			'buttonCancel'  => esc_attr__( 'Cancel', 'rank-math' ),
		) );
	}

	/**
	 * Add columns for SEO title, description and focus keywords.
	 */
	public function add_seo_filter() {
		global $post_type;

		if ( 'attachment' === $post_type || ! in_array( $post_type, GlobalHelper::get_allowed_post_types() ) ) {
			return;
		}

		$options  = array(
			''          => esc_html__( 'All Posts', 'rank-math' ),
			'great-seo' => esc_html__( 'SEO Score: Great', 'rank-math' ),
			'good-seo'  => esc_html__( 'SEO Score: Good', 'rank-math' ),
			'bad-seo'   => esc_html__( 'SEO Score: Bad', 'rank-math' ),
			'empty-fk'  => esc_html__( 'Focus Keyword Not Set', 'rank-math' ),
			'noindexed' => esc_html__( 'Articles noindexed', 'rank-math' ),
		);
		$selected = isset( $_GET['seo-filter'] ) ? $_GET['seo-filter'] : '';
		?>
		<select name="seo-filter">
			<?php foreach ( $options as $val => $option ) : ?>
				<option value="<?php echo $val; ?>" <?php selected( $selected, $val, true ); ?>><?php echo $option; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Add columns for SEO title, description and focus keywords.
	 *
	 * @param  array $columns An array of column names.
	 * @return array
	 */
	public function add_columns( $columns ) {
		global $post_type;

		$columns['rank_math_seo_details'] = esc_html__( 'SEO Details', 'rank-math' );

		if ( GlobalHelper::get_settings( 'titles.pt_' . $post_type . '_bulk_editing' ) ) {
			$columns['rank_math_title']       = esc_html__( 'SEO Title', 'rank-math' );
			$columns['rank_math_description'] = esc_html__( 'SEO Desc', 'rank-math' );
		}

		return $columns;
	}

	/**
	 * Add content for custom column.
	 *
	 * @param string $column_name The name of the column to display.
	 * @param int    $post_id     The current post ID.
	 */
	public function columns_contents( $column_name, $post_id ) {
		$post_type = get_post_type( $post_id );

		if ( 'rank_math_title' === $column_name ) {
			$title = get_post_meta( $post_id, 'rank_math_title', true );
			if ( ! $title ) {
				$title = GlobalHelper::get_settings( "titles.pt_{$post_type}_title" );
			}
			?>
			<span class="rank-math-column-display"><?php echo $title; ?></span>
			<span class="rank-math-column-value" data-field="title" contenteditable="true" tabindex="11"><?php echo $title; ?></span>
			<div class="rank-math-column-edit">
				<a href="#" class="rank-math-column-save"><?php esc_html_e( 'Save', 'rank-math' ); ?></a>
				<a href="#" class="button-link-delete rank-math-column-cancel"><?php esc_html_e( 'Cancel', 'rank-math' ); ?></a>
			</div>
			<?php
			return;
		}

		if ( 'rank_math_description' === $column_name ) {
			$description = get_post_meta( $post_id, 'rank_math_description', true );
			if ( ! $description ) {
				$description = GlobalHelper::get_settings( "titles.pt_{$post_type}_description" );
			}
			?>
			<span class="rank-math-column-display"><?php echo $description; ?></span>
			<span class="rank-math-column-value" data-field="description" contenteditable="true" tabindex="11"><?php echo $description; ?></span>
			<div class="rank-math-column-edit">
				<a href="#" class="rank-math-column-save"><?php esc_html_e( 'Save', 'rank-math' ); ?></a>
				<a href="#" class="button-link-delete rank-math-column-cancel"><?php esc_html_e( 'Cancel', 'rank-math' ); ?></a>
			</div>
			<?php
			return;
		}

		if ( 'rank_math_seo_details' === $column_name ) {
			$score     = get_post_meta( $post_id, 'rank_math_seo_score', true );
			$schema    = get_post_meta( $post_id, 'rank_math_rich_snippet', true );
			$keyword   = get_post_meta( $post_id, 'rank_math_focus_keyword', true );
			$keyword   = explode( ',', $keyword )[0];
			$is_pillar = get_post_meta( $post_id, 'rank_math_pillar_content', true );
			$score     = $score ? $score : 0;
			$class     = 'bad';
			if ( $score > 51 && $score < 81 ) {
				$class = 'good';
			} elseif ( $score > 80 ) {
				$class = 'great';
			}

			$score = $score . ' / 100';
			if ( ! metadata_exists( 'post', $post_id, 'rank_math_seo_score' ) ) {
				$score = __( 'Update your post', 'rank-math' );
				$class = 'no-score';
			}
			?>
			<span class="rank-math-column-display seo-score <?php echo $class; ?>">
				<strong><?php echo $score; ?></strong>
				<?php if ( $is_pillar ) { ?>
					<img class="is-pillar" src="<?php echo esc_url( rank_math()->plugin_url() . 'assets/admin/img/pillar.svg' ); ?>" alt="<?php _e( 'Is Pillar', 'rank-math' ); ?>" title="<?php _e( 'Is Pillar', 'rank-math' ); ?>" width="25" />
				<?php } ?>
			</span>

			<label><?php _e( 'Focus Keyword', 'rank-math' ); ?>:</label>
			<span class="rank-math-column-display">
				<strong title="Focus Keyword"><?php _e( 'Keyword', 'rank-math' ); ?>:</strong>
				<span><?php echo $keyword ? $keyword : esc_html__( 'Not Set', 'rank-math' ); ?></span>
			</span>

			<span class="rank-math-column-value" data-field="focus_keyword" contenteditable="true" tabindex="11">
				<span><?php echo $keyword; ?></span>
			</span>

			<?php if ( $schema ) { ?>
				<span class="rank-math-column-display schema-type">
					<strong><?php _e( 'Schema', 'rank-math' ); ?>:</strong>
					<?php echo ucfirst( $schema ); ?>
				</span>
			<?php } ?>

			<div class="rank-math-column-edit">
				<a href="#" class="rank-math-column-save"><?php esc_html_e( 'Save', 'rank-math' ); ?></a>
				<a href="#" class="button-link-delete rank-math-column-cancel"><?php esc_html_e( 'Cancel', 'rank-math' ); ?></a>
			</div>

			<?php do_action( $column_name, $post_id ); ?>

			<?php
			return;
		}
	}

	/**
	 * Make seo_score column sortable
	 *
	 * @param  array $columns An array of column names.
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$columns['rank_math_seo_details'] = 'rank_math_seo_score';
		return $columns;
	}

	/**
	 * Add columns for Media Alt & Title.
	 *
	 * @param  array $columns An array of column names.
	 * @return array
	 */
	public function add_media_columns( $columns ) {

		$columns['rank_math_image_title'] = esc_html__( 'Title', 'rank-math' );
		$columns['rank_math_image_alt']   = esc_html__( 'Alternative Text', 'rank-math' );

		return $columns;
	}

	/**
	 * Add content for custom media column.
	 *
	 * @param string $column_name The name of the column to display.
	 * @param int    $post_id     The current post ID.
	 */
	public function media_contents( $column_name, $post_id ) {

		if ( 'rank_math_image_title' === $column_name ) {
			$title = get_the_title( $post_id );
			?>
			<span class="rank-math-column-display"><?php echo $title; ?></span>
			<span class="rank-math-column-value" data-field="image_title" contenteditable="true" tabindex="11"><?php echo $title; ?></span>
			<div class="rank-math-column-edit">
				<a href="#" class="rank-math-column-save"><?php esc_html_e( 'Save', 'rank-math' ); ?></a>
				<a href="#" class="button-link-delete rank-math-column-cancel"><?php esc_html_e( 'Cancel', 'rank-math' ); ?></a>
			</div>
			<?php
			return;
		}

		if ( 'rank_math_image_alt' === $column_name ) {
			$alt = get_post_meta( $post_id, '_wp_attachment_image_alt', true );
			?>
			<span class="rank-math-column-display"><?php echo $alt; ?></span>
			<span class="rank-math-column-value" data-field="image_alt" contenteditable="true" tabindex="11"><?php echo $alt; ?></span>
			<div class="rank-math-column-edit">
				<a href="#" class="rank-math-column-save"><?php esc_html_e( 'Save', 'rank-math' ); ?></a>
				<a href="#" class="button-link-delete rank-math-column-cancel"><?php esc_html_e( 'Cancel', 'rank-math' ); ?></a>
			</div>
			<?php
			return;
		}
	}

	/**
	 * Filter post in admin by pillar content.
	 *
	 * @param \WP_Query $query The wp_query instance.
	 */
	public function filter_by_focus_keywords( $query ) {
		$screen = get_current_screen();
		if ( is_null( $screen ) || 'edit' !== $screen->base || ( ! isset( $_GET['focus_keyword'] ) && ! isset( $_GET['fk_in_title'] ) ) ) {
			return;
		}

		$query->set( 'post_status', 'publish' );

		$fk_in_title = isset( $_GET['fk_in_title'] ) ? $_GET['fk_in_title'] : '';
		if ( $fk_in_title ) {
			global $wpdb;

			$meta_query = new \WP_Meta_Query( array(
				array(
					'key'     => 'rank_math_focus_keyword',
					'compare' => 'EXISTS',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => 'rank_math_robots',
						'value'   => 'noindex',
						'compare' => 'NOT LIKE',
					),
					array(
						'key'     => 'rank_math_robots',
						'compare' => 'NOT EXISTS',
					),
				),
			));

			$mq_sql = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
			$rows   = $wpdb->get_col( "SELECT {$wpdb->posts}.ID FROM $wpdb->posts {$mq_sql['join']} WHERE 1=1 {$mq_sql['where']} AND {$wpdb->posts}.post_type = '$screen->post_type' AND ({$wpdb->posts}.post_status = 'publish') AND {$wpdb->posts}.post_title NOT REGEXP REPLACE({$wpdb->postmeta}.meta_value, ',', '|')" ); // phpcs:ignore
			$query->set( 'post__in', $rows );
			return;
		}

		$focus_keyword = isset( $_GET['focus_keyword'] ) ? $_GET['focus_keyword'] : '';

		if ( 1 == $focus_keyword ) {

			$meta_args = array(
				'relation' => 'AND',
				array(
					'key'     => 'rank_math_focus_keyword',
					'compare' => 'NOT EXISTS',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => 'rank_math_robots',
						'value'   => 'noindex',
						'compare' => 'NOT LIKE',
					),
					array(
						'key'     => 'rank_math_robots',
						'compare' => 'NOT EXISTS',
					),
				),
			);
			$query->set( 'meta_query', $meta_args );
			return;
		}

		$query->set( 'meta_key', 'rank_math_focus_keyword' );
		$query->set( 'meta_value', $focus_keyword );
		$query->set( 'meta_compare', 'LIKE' );
		$query->set( 'post_type', 'any' );
	}

	/**
	 * Add view to filter list for pillar content.
	 *
	 * @param array $views An array of available list table views.
	 */
	public function add_pillar_content_filter_link( $views ) {
		global $typenow;

		$current = empty( $_GET['pillar_content'] ) ? '' : ' class="current" aria-current="page"';
		$pillars = get_posts( array(
			'post_type'      => $typenow,
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'meta_key'       => 'rank_math_pillar_content',
			'meta_value'     => 'on',
		) );

		$views['pillar_content'] = sprintf(
			'<a href="%1$s"%2$s>%3$s <span class="count">(%4$s)</span></a>',
			add_query_arg( array(
				'post_type'      => $typenow,
				'pillar_content' => 1,
			)),
			$current,
			esc_html__( 'Pillar Content', 'rank-math' ),
			number_format_i18n( count( $pillars ) )
		);

		return $views;
	}

	/**
	 * Filter post in admin by Rank Math's Filter value.
	 *
	 * @param \WP_Query $query The wp_query instance.
	 */
	public function posts_by_seo_filters( $query ) {
		$screen = get_current_screen();
		if (
			is_null( $screen ) ||
			'edit' !== $screen->base ||
			! in_array( $screen->post_type, GlobalHelper::get_allowed_post_types() )
		) {
			return;
		}

		if ( 'rank_math_seo_score' === $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'meta_key', 'rank_math_seo_score' );
			$query->set( 'meta_type', 'numeric' );
		}

		if ( empty( $_GET['pillar_content'] ) && empty( $_GET['seo-filter'] ) ) {
			return;
		}

		$meta_query = array();

		// Check for pillar content filter.
		if ( ! empty( $_GET['pillar_content'] ) ) {
			$meta_query[] = array(
				'key'   => 'rank_math_pillar_content',
				'value' => 'on',
			);
		}

		// Check for pillar seo filter.
		if ( ! empty( $_GET['seo-filter'] ) ) {
			$filter = $_GET['seo-filter'];
			$hash   = array(
				'empty-fk'  => array(
					'key'     => 'rank_math_focus_keyword',
					'compare' => 'NOT EXISTS',
				),
				'bad-seo'   => array(
					'key'     => 'rank_math_seo_score',
					'value'   => 50,
					'compare' => '<=',
					'type'    => 'numeric',
				),
				'good-seo'  => array(
					'key'     => 'rank_math_seo_score',
					'value'   => array( 51, 80 ),
					'compare' => 'BETWEEN',
				),
				'great-seo' => array(
					'key'     => 'rank_math_seo_score',
					'value'   => 80,
					'compare' => '>',
				),
				'noindexed' => array(
					'key'     => 'rank_math_robots',
					'value'   => 'noindex',
					'compare' => 'LIKE',
				),
			);

			if ( isset( $hash[ $filter ] ) ) {
				$meta_query[] = $hash[ $filter ];
			}
		}

		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Save rows.
	 */
	public function save() {

		check_ajax_referer( 'rank-math-ajax-nonce', 'security' );

		$this->has_cap_ajax( 'general' );

		$rows = isset( $_POST['rows'] ) ? $_POST['rows'] : false;
		if ( ! $rows ) {
			$this->error( esc_html__( 'No data found.', 'rank-math' ) );
		}

		foreach ( $rows as $post_id => $data ) {
			$post_id = absint( $post_id );
			if ( ! $post_id ) {
				continue;
			}

			foreach ( $data as $key => $value ) {

				if ( ! in_array( $key, array( 'focus_keyword', 'title', 'description', 'image_alt', 'image_title' ) ) ) {
					continue;
				}

				if ( 'image_title' === $key ) {
					wp_update_post( array(
						'ID'         => $post_id,
						'post_title' => $value,
					) );
					continue;
				}

				if ( 'focus_keyword' === $key ) {
					$fk    = get_post_meta( $post_id, 'rank_math_' . $key, true );
					$fk    = explode( ',', $fk );
					$fk[0] = $value;
					$value = implode( ',', $fk );
				}

				$key = 'image_alt' === $key ? '_wp_attachment_image_alt' : 'rank_math_' . $key;
				update_post_meta( $post_id, $key, $value );
			}
		}

		$this->success( 'done' );
	}
}
