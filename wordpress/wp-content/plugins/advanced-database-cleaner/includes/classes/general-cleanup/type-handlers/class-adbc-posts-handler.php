<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Class ADBC_Cleanup_Posts_Handler_Base
 * 
 * This class serves as a base handler for cleaning up posts in WordPress.
 */
abstract class ADBC_Cleanup_Posts_Handler_Base extends ADBC_Abstract_Cleanup_Handler {

	// Required, all posts subclasses must supply
	abstract protected function items_type();
	abstract protected function base_where();

	// Common to all posts subclasses, provided by this base class
	protected function table() {
		global $wpdb;
		return $wpdb->posts;
	}
	protected function table_suffix() {
		return 'posts';
	}
	protected function pk() {
		return 'ID';
	}
	protected function name_column() {
		return 'post_title';
	}
	protected function value_column() {
		return 'post_content';
	}
	protected function is_all_sites_sortable() {
		return true;
	}
	protected function sortable_columns() {
		return [ 
			'ID',
			'post_title',
			'post_content',
			'post_date_gmt',
			'size',
			'site_id'
		];
	}
	protected function extra_select() {
		return [ 'post_date_gmt' ];
	}
	protected function delete_helper() {
		return 'wp_delete_post';
	}
	protected function delete_helper_tail_args() {
		return [ true ]; // force delete argument
	}
	protected function date_column() {
		return 'post_date_gmt';
	}
	protected function keep_last_mode() {
		return 'from_total';
	}
	protected function all_post_types() {
		return "SELECT DISTINCT post_type FROM {$this->table()}";
	}
	/**
	 * WordPress core post types allowed for generic post cleanup.
	 * Intentionally excludes all plugin / theme CPTs (e.g. shop_order).
	 *
	 * @return string Comma-separated list of escaped post types, already quoted for SQL IN(...).
	 */
	protected function wp_core_post_types() {

		$wp_core_types = [ 
			// Classic core:
			'post',
			'page',
			'attachment',
			'revision',
			'nav_menu_item',

			// Customizer / internal:
			'custom_css',
			'customize_changeset',
			'oembed_cache',
			'user_request',

			// Block editor / site editor:
			'wp_block',
			'wp_template',
			'wp_template_part',
			'wp_global_styles',
			'wp_navigation',

			// Font Library (WP 6.5+):
			'wp_font_family',
			'wp_font_face',
		];

		return "'" . implode( "','", $wp_core_types ) . "'";

	}


}

/**
 * Class ADBC_Cleanup_Revisions_Handler
 * 
 * This class handles the cleanup of post revisions in WordPress.
 */
class ADBC_Cleanup_Revisions_Handler extends ADBC_Cleanup_Posts_Handler_Base {

	protected function items_type() {
		return 'revisions';
	}
	protected function base_where() {
		return "post_type='revision'";
	}
	protected function keep_last_mode() {
		return 'per_parent';
	}
	protected function parent_column() {
		return 'post_parent';
	}
}

/**
 * Class ADBC_Cleanup_Auto_Drafts_Handler
 * 
 * This class handles the cleanup of auto-draft posts in WordPress.
 */
class ADBC_Cleanup_Auto_Drafts_Handler extends ADBC_Cleanup_Posts_Handler_Base {

	protected function items_type() {
		return 'auto_drafts';
	}
	protected function base_where() {
		return "( post_status='auto-draft' AND post_type in ({$this->all_post_types()}) ) ";
	}
	protected function keep_last_mode() {
		return 'per_parent';
	}
	protected function parent_column() {
		return 'post_parent';
	}

}

/**
 * Class ADBC_Cleanup_Trashed_Posts_Handler
 * 
 * This class handles the cleanup of trashed posts in WordPress.
 */
class ADBC_Cleanup_Trashed_Posts_Handler extends ADBC_Cleanup_Posts_Handler_Base {

	protected function items_type() {
		return 'trashed_posts';
	}
	protected function base_where() {
		return "( post_status = 'trash' AND post_type IN ({$this->wp_core_post_types()}) )";
	}

}

// Register the handler with the cleanup type registry.
ADBC_Cleanup_Type_Registry::register( 'revisions', new ADBC_Cleanup_Revisions_Handler );
ADBC_Cleanup_Type_Registry::register( 'auto_drafts', new ADBC_Cleanup_Auto_Drafts_Handler );
ADBC_Cleanup_Type_Registry::register( 'trashed_posts', new ADBC_Cleanup_Trashed_Posts_Handler );
