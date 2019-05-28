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

use RankMath\Helper;
use RankMath\Runner;
use RankMath\Traits\Ajax;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Param;

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
		if ( ! Helper::has_cap( 'general' ) ) {
			return;
		}

		$this->register_post_columns();
		$this->register_media_columns();
		$this->action( 'admin_enqueue_scripts', 'enqueue' );

		// Column Content.
		$this->filter( 'rank_math_title', 'get_column_title', 5 );
		$this->filter( 'rank_math_description', 'get_column_description', 5 );
		$this->filter( 'rank_math_seo_details', 'get_column_seo_details', 5 );
	}

	/**
	 * Save rows.
	 */
	public function save() {
		check_ajax_referer( 'rank-math-ajax-nonce', 'security' );
		$this->has_cap_ajax( 'general' );

		$rows = Param::post( 'rows' );
		if ( ! $rows ) {
			$this->error( esc_html__( 'No data found.', 'rank-math' ) );
		}

		foreach ( $rows as $post_id => $data ) {
			$post_id = absint( $post_id );
			if ( ! $post_id ) {
				continue;
			}

			$this->save_row( $post_id, $data );
		}

		$this->success( 'done' );
	}

	/**
	 * Save single row.
	 *
	 * @param int   $post_id Post id.
	 * @param array $data    Post data.
	 */
	private function save_row( $post_id, $data ) {
		foreach ( $data as $key => $value ) {
			if ( ! in_array( $key, [ 'focus_keyword', 'title', 'description', 'image_alt', 'image_title' ], true ) ) {
				continue;
			}

			if ( 'image_title' === $key ) {
				wp_update_post([
					'ID'         => $post_id,
					'post_title' => $value,
				]);
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

	/**
	 * Register post column hooks
	 */
	private function register_post_columns() {
		foreach ( Helper::get_allowed_post_types() as $post_type ) {
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
			$columns_hidden = array_unique( array_merge( $columns_hidden, [ 'rank_math_title', 'rank_math_description' ] ) );
			update_user_meta( $user_id, "manageedit-{$post_type}columnshidden", $columns_hidden );
			update_user_meta( $user_id, "manageedit-{$post_type}columnshidden_default", '1' );
		}
	}

	/**
	 * Register media column hooks
	 */
	private function register_media_columns() {
		if ( ! Helper::get_settings( 'titles.pt_attachment_bulk_editing' ) ) {
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

		$allowed_post_types   = Helper::get_allowed_post_types();
		$allowed_post_types[] = 'attachment';
		if ( ( 'edit' !== $screen->base && 'upload' !== $screen->base ) || ! in_array( $screen->post_type, $allowed_post_types, true ) ) {
			return;
		}

		wp_enqueue_style( 'rank-math-post-bulk-edit', rank_math()->plugin_url() . 'assets/admin/css/post-list.css', null, rank_math()->version );

		$allow_editing = Helper::get_settings( 'titles.pt_' . $screen->post_type . '_bulk_editing' );
		if ( ! $allow_editing || 'readonly' === $allow_editing ) {
			return;
		}

		wp_enqueue_script( 'rank-math-post-bulk-edit', rank_math()->plugin_url() . 'assets/admin/js/post-list.js', null, rank_math()->version, true );
		wp_localize_script( 'rank-math-post-bulk-edit', 'rankMath', [
			'security'      => wp_create_nonce( 'rank-math-ajax-nonce' ),
			'bulkEditTitle' => esc_attr__( 'Bulk Edit This Field', 'rank-math' ),
			'buttonSaveAll' => esc_attr__( 'Save All Edits', 'rank-math' ),
			'buttonCancel'  => esc_attr__( 'Cancel', 'rank-math' ),
		]);
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

		if ( Helper::get_settings( 'titles.pt_' . $post_type . '_bulk_editing' ) ) {
			$columns['rank_math_title']       = esc_html__( 'SEO Title', 'rank-math' );
			$columns['rank_math_description'] = esc_html__( 'SEO Desc', 'rank-math' );
		}

		return $columns;
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
	 * Add content for custom column.
	 *
	 * @param string $column_name The name of the column to display.
	 * @param int    $post_id     The current post ID.
	 */
	public function columns_contents( $column_name, $post_id ) {
		do_action( $column_name, $post_id );
	}

	/**
	 * Add content for title column.
	 *
	 * @param int $post_id The current post ID.
	 */
	public function get_column_title( $post_id ) {
		$title     = get_post_meta( $post_id, 'rank_math_title', true );
		$post_type = get_post_type( $post_id );

		if ( ! $title ) {
			$title = Helper::get_settings( "titles.pt_{$post_type}_title" );
		}
		?>
		<span class="rank-math-column-display"><?php echo $title; ?></span>
		<span class="rank-math-column-value" data-field="title" contenteditable="true" tabindex="11"><?php echo $title; ?></span>
		<div class="rank-math-column-edit">
			<a href="#" class="rank-math-column-save"><?php esc_html_e( 'Save', 'rank-math' ); ?></a>
			<a href="#" class="button-link-delete rank-math-column-cancel"><?php esc_html_e( 'Cancel', 'rank-math' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Add content for title column.
	 *
	 * @param int $post_id The current post ID.
	 */
	public function get_column_description( $post_id ) {
		$post_type   = get_post_type( $post_id );
		$description = get_post_meta( $post_id, 'rank_math_description', true );

		if ( ! $description ) {
			$description = Helper::get_settings( "titles.pt_{$post_type}_description" );
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

	/**
	 * Add content for title column.
	 *
	 * @param int $post_id The current post ID.
	 */
	public function get_column_seo_details( $post_id ) {
		$score     = get_post_meta( $post_id, 'rank_math_seo_score', true );
		$keyword   = get_post_meta( $post_id, 'rank_math_focus_keyword', true );
		$keyword   = explode( ',', $keyword )[0];
		$is_pillar = get_post_meta( $post_id, 'rank_math_pillar_content', true );
		$schema    = get_post_meta( $post_id, 'rank_math_rich_snippet', true );
		$score     = $score ? $score : 0;
		$class     = $this->get_seo_score_class( $score );

		$score = $score . ' / 100';
		if ( ! metadata_exists( 'post', $post_id, 'rank_math_seo_score' ) ) {
			$score = __( 'Update your post', 'rank-math' );
			$class = 'no-score';
		}
		?>
		<span class="rank-math-column-display seo-score <?php echo $class; ?>">
			<strong><?php echo $score; ?></strong>
			<?php if ( $is_pillar ) : ?>
				<img class="is-pillar" src="<?php echo esc_url( rank_math()->plugin_url() . 'assets/admin/img/pillar.svg' ); ?>" alt="<?php _e( 'Is Pillar', 'rank-math' ); ?>" title="<?php _e( 'Is Pillar', 'rank-math' ); ?>" width="25" />
			<?php endif; ?>
		</span>

		<label><?php _e( 'Focus Keyword', 'rank-math' ); ?>:</label>
		<span class="rank-math-column-display">
			<strong title="Focus Keyword"><?php _e( 'Keyword', 'rank-math' ); ?>:</strong>
			<span><?php echo $keyword ? $keyword : esc_html__( 'Not Set', 'rank-math' ); ?></span>
		</span>

		<span class="rank-math-column-value" data-field="focus_keyword" contenteditable="true" tabindex="11">
			<span><?php echo $keyword; ?></span>
		</span>

		<?php if ( $schema ) : ?>
			<span class="rank-math-column-display schema-type">
				<strong><?php _e( 'Schema', 'rank-math' ); ?>:</strong>
				<?php echo ucfirst( $schema ); ?>
			</span>
		<?php endif; ?>

		<div class="rank-math-column-edit">
			<a href="#" class="rank-math-column-save"><?php esc_html_e( 'Save', 'rank-math' ); ?></a>
			<a href="#" class="button-link-delete rank-math-column-cancel"><?php esc_html_e( 'Cancel', 'rank-math' ); ?></a>
		</div>
		<?php
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
	 * Get seo score class.
	 *
	 * @param int $score Score.
	 *
	 * @return string
	 */
	private function get_seo_score_class( $score ) {
		if ( $score > 80 ) {
			return 'great';
		}

		if ( $score > 51 && $score < 81 ) {
			return 'good';
		}

		return 'bad';
	}
}
