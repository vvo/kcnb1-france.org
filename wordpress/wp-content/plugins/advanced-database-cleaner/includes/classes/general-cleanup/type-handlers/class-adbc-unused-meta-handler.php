<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Class ADBC_Cleanup_Unused_Meta_Handler_Base
 * 
 * This class serves as a base handler for cleaning up unused metadata across different types
 */
abstract class ADBC_Cleanup_Unused_Meta_Handler_Base extends ADBC_Abstract_Cleanup_Handler {

	// Required, all unused meta subclasses must supply
	abstract protected function items_type();
	abstract protected function table();
	abstract protected function table_suffix();
	abstract protected function pk();
	abstract protected function parent_join();
	abstract protected function parent_null_test();
	abstract protected function meta_type();

	// Common to all unused meta subclasses, provided by this base class
	protected function base_where() {
		return $this->parent_null_test();
	}
	protected function name_column() {
		return 'meta_key';
	}
	protected function value_column() {
		return 'meta_value';
	}
	protected function is_all_sites_sortable() {
		return true;
	}
	protected function sortable_columns() {
		return [ 
			'meta_id',
			'umeta_id',
			'meta_key',
			'meta_value',
			'size',
			'site_id'
		];
	}
	protected function delete_helper() {
		return function ($mid) {
			return delete_metadata_by_mid( $this->meta_type(), $mid );
		};
	}
	protected function extra_joins() {
		return $this->parent_join();
	}
	protected function date_column() {
		return null; // not used
	}
	protected function keep_last_mode() {
		return 'from_total'; // not used
	}

}

/**
 * Class ADBC_Cleanup_Unused_Postmeta_Handler
 * 
 * This class handles the cleanup of unused post metadata in WordPress.
 */
class ADBC_Cleanup_Unused_Postmeta_Handler extends ADBC_Cleanup_Unused_Meta_Handler_Base {

	protected function items_type() {
		return 'unused_postmeta';
	}
	protected function table() {
		global $wpdb;
		return $wpdb->postmeta;
	}
	protected function table_suffix() {
		return 'postmeta';
	}
	protected function pk() {
		return 'meta_id';
	}
	protected function parent_join() {
		global $wpdb;
		return "LEFT JOIN {$wpdb->posts} p ON p.ID = main.post_id";
	}
	protected function parent_null_test() {
		return 'p.ID IS NULL';
	}
	protected function meta_type() {
		return 'post';
	}

}

/** Class ADBC_Cleanup_Unused_Commentmeta_Handler
 * 
 * This class handles the cleanup of unused comment metadata in WordPress.
 */
class ADBC_Cleanup_Unused_Commentmeta_Handler extends ADBC_Cleanup_Unused_Meta_Handler_Base {

	protected function items_type() {
		return 'unused_commentmeta';
	}
	protected function table() {
		global $wpdb;
		return $wpdb->commentmeta;
	}
	protected function table_suffix() {
		return 'commentmeta';
	}
	protected function pk() {
		return 'meta_id';
	}
	protected function parent_join() {
		global $wpdb;
		return "LEFT JOIN {$wpdb->comments} c ON c.comment_ID = main.comment_id";
	}
	protected function parent_null_test() {
		return 'c.comment_ID IS NULL';
	}
	protected function meta_type() {
		return 'comment';
	}

}

/**
 * Class ADBC_Cleanup_Unused_Usermeta_Handler
 * 
 * This class handles the cleanup of unused user metadata in WordPress.
 */
class ADBC_Cleanup_Unused_Usermeta_Handler extends ADBC_Cleanup_Unused_Meta_Handler_Base {

	protected function items_type() {
		return 'unused_usermeta';
	}
	protected function table() {
		global $wpdb;
		return $wpdb->usermeta;
	}
	protected function table_suffix() {
		return 'usermeta';
	}
	protected function pk() {
		return 'umeta_id';
	}
	protected function parent_join() {
		global $wpdb;
		return "LEFT JOIN {$wpdb->users} u ON u.ID = main.user_id";
	}
	protected function parent_null_test() {
		return 'u.ID IS NULL';
	}
	protected function meta_type() {
		return 'user';
	}

	public function count_filtered( $args = [] ) {
		$args['site_id'] = get_current_blog_id();
		return parent::count_filtered( $args );
	}

	public function list( $args ) {
		$args['site_id'] = get_current_blog_id();
		return parent::list( $args );
	}

	protected function purge_native() {

		global $wpdb;

		$helper = $this->delete_helper();
		$tail = $this->delete_helper_tail_args();

		$chunk = self::PURGE_CHUNK; // number of items to delete in one run
		$deleted = 0;

		while ( true ) {

			$ids = $wpdb->get_col( "
					SELECT main.{$this->pk()}
					FROM   {$this->table()} main {$this->extra_joins()}
					WHERE  {$this->base_where()}
					LIMIT  {$chunk}
				" );

			if ( empty( $ids ) ) {
				break;
			}

			foreach ( $ids as $id ) {
				$helper( (int) $id, ...$tail );
				$deleted++;
			}

		}

		return $deleted;

	}

	protected function purge_sql() {

		global $wpdb;

		$deleted = 0;

		$sql = "
			DELETE FROM {$this->table()}
			WHERE {$this->pk()} IN (
				SELECT del_id FROM (
					SELECT main.{$this->pk()} AS del_id
					FROM   {$this->table()}  AS main
						   {$this->extra_joins()}
					WHERE  {$this->base_where()}
				) AS tmp
			)
		";

		$deleted = $wpdb->query( $sql );

		return $deleted;

	}

}

/**
 * Class ADBC_Cleanup_Unused_Termmeta_Handler
 * 
 * This class handles the cleanup of unused term metadata in WordPress.
 */
class ADBC_Cleanup_Unused_Termmeta_Handler extends ADBC_Cleanup_Unused_Meta_Handler_Base {

	protected function items_type() {
		return 'unused_termmeta';
	}
	protected function table() {
		global $wpdb;
		return $wpdb->termmeta;
	}
	protected function table_suffix() {
		return 'termmeta';
	}
	protected function pk() {
		return 'meta_id';
	}
	protected function parent_join() {
		global $wpdb;
		return "LEFT JOIN {$wpdb->terms} t ON t.term_id = main.term_id";
	}
	protected function parent_null_test() {
		return 't.term_id IS NULL';
	}
	protected function meta_type() {
		return 'term';
	}

}

/**
 * Class ADBC_Cleanup_Oembed_Cache_Meta_Handler
 * 
 * This class handles the cleanup of oEmbed cache metadata in WordPress.
 */
class ADBC_Cleanup_Oembed_Cache_Meta_Handler extends ADBC_Abstract_Cleanup_Handler {

	// Required methods from ADBC_Abstract_Cleanup_Handler
	protected function items_type() {
		return 'oembed_caches';
	}
	protected function table() {
		global $wpdb;
		return $wpdb->postmeta;
	}
	protected function table_suffix() {
		return 'postmeta';
	}
	protected function pk() {
		return 'meta_id';
	}
	protected function base_where() {
		return "meta_key LIKE '_oembed_%'";
	}
	protected function name_column() {
		return 'meta_key';
	}
	protected function value_column() {
		return 'meta_value';
	}
	protected function is_all_sites_sortable() {
		return true;
	}
	protected function sortable_columns() {
		return [ 
			'meta_id',
			'meta_key',
			'meta_value',
			'size',
			'site_id'
		];
	}
	protected function delete_helper() {
		return function ($mid) {
			return delete_metadata_by_mid( 'post', $mid );
		};
	}

}

// Register the handler with the cleanup type registry.
ADBC_Cleanup_Type_Registry::register( 'unused_postmeta', new ADBC_Cleanup_Unused_Postmeta_Handler );
ADBC_Cleanup_Type_Registry::register( 'unused_commentmeta', new ADBC_Cleanup_Unused_Commentmeta_Handler );
ADBC_Cleanup_Type_Registry::register( 'unused_usermeta', new ADBC_Cleanup_Unused_Usermeta_Handler );
ADBC_Cleanup_Type_Registry::register( 'unused_termmeta', new ADBC_Cleanup_Unused_Termmeta_Handler );
ADBC_Cleanup_Type_Registry::register( 'oembed_caches', new ADBC_Cleanup_Oembed_Cache_Meta_Handler );
