<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Class ADBC_Cleanup_Comments_Handler_Base
 * 
 * This class serves as a base handler for cleaning up comments in WordPress.
 */
abstract class ADBC_Cleanup_Comments_Handler_Base extends ADBC_Abstract_Cleanup_Handler {

	// Required, all comments subclasses must supply
	abstract protected function items_type();
	abstract protected function base_where();

	// Common to all comments subclasses, provided by this base class
	protected function table() {
		global $wpdb;
		return $wpdb->comments;
	}
	protected function table_suffix() {
		return 'comments';
	}
	protected function pk() {
		return 'comment_ID';
	}
	protected function name_column() {
		return 'comment_author';
	}
	protected function value_column() {
		return 'comment_content';
	}
	protected function is_all_sites_sortable() {
		return true;
	}
	protected function sortable_columns() {
		return [ 
			'comment_ID',
			'comment_author',
			'comment_content',
			'comment_post_ID',
			'comment_date_gmt',
			'size',
			'site_id'
		];
	}
	protected function extra_select() {
		return [ 
			'comment_post_ID',
			'comment_date_gmt',
		];
	}
	protected function date_column() {
		return 'comment_date_gmt';
	}
	protected function keep_last_mode() {
		return 'per_parent';
	}
	protected function parent_column() {
		return 'comment_post_ID';
	}
	protected function delete_helper() {
		return 'wp_delete_comment';
	}
	protected function delete_helper_tail_args() {
		return [ true ]; // force delete argument
	}

}

/**
 * Class ADBC_Cleanup_Spam_Comments_Handler
 * 
 * This class handles the cleanup of spam comments in WordPress.
 */
class ADBC_Cleanup_Spam_Comments_Handler extends ADBC_Cleanup_Comments_Handler_Base {

	protected function items_type() {
		return 'spam_comments';
	}
	protected function base_where() {
		return "comment_approved = 'spam'";
	}

}

/**
 * Class ADBC_Cleanup_Trash_Comments_Handler
 * 
 * This class handles the cleanup of trashed comments in WordPress.
 */
class ADBC_Cleanup_Trash_Comments_Handler extends ADBC_Cleanup_Comments_Handler_Base {

	protected function items_type() {
		return 'trashed_comments';
	}
	protected function base_where() {
		return "( comment_approved = 'trash' )";
	}
}

/**
 * Class ADBC_Cleanup_Trash_Comments_Handler
 * 
 * This class handles the cleanup of trashed comments in WordPress.
 */
class ADBC_Cleanup_Unapproved_Comments_Handler extends ADBC_Cleanup_Comments_Handler_Base {

	protected function items_type() {
		return 'unapproved_comments';
	}
	protected function base_where() {
		return "comment_approved = '0'";
	}
}

/**
 * Class ADBC_Cleanup_Pingbacks_Handler
 * 
 * This class handles the cleanup of pingback comments in WordPress.
 */
class ADBC_Cleanup_Pingbacks_Handler extends ADBC_Cleanup_Comments_Handler_Base {

	protected function items_type() {
		return 'pingbacks';
	}
	protected function base_where() {
		return "comment_type = 'pingback'";
	}
}

/**
 * Class ADBC_Cleanup_Trackbacks_Handler
 * 
 * This class handles the cleanup of trackback comments in WordPress.
 */
class ADBC_Cleanup_Trackbacks_Handler extends ADBC_Cleanup_Comments_Handler_Base {

	protected function items_type() {
		return 'trackbacks';
	}
	protected function base_where() {
		return "comment_type = 'trackback'";
	}
}

// Register the handler with the cleanup type registry.
ADBC_Cleanup_Type_Registry::register( 'spam_comments', new ADBC_Cleanup_Spam_Comments_Handler );
ADBC_Cleanup_Type_Registry::register( 'trashed_comments', new ADBC_Cleanup_Trash_Comments_Handler );
ADBC_Cleanup_Type_Registry::register( 'unapproved_comments', new ADBC_Cleanup_Unapproved_Comments_Handler );
ADBC_Cleanup_Type_Registry::register( 'pingbacks', new ADBC_Cleanup_Pingbacks_Handler );
ADBC_Cleanup_Type_Registry::register( 'trackbacks', new ADBC_Cleanup_Trackbacks_Handler );