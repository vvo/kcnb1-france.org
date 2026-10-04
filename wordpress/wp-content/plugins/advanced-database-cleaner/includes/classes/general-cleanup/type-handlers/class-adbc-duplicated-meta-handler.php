<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Class ADBC_Cleanup_Duplicated_Meta_Handler_Base
 * 
 * This class serves as a base handler for cleaning up duplicated metadata in WordPress.
 */
abstract class ADBC_Cleanup_Duplicated_Meta_Handler_Base extends ADBC_Abstract_Cleanup_Handler {

	// Required, all duplicated meta subclasses must supply
	abstract protected function items_type();
	abstract protected function table();
	abstract protected function table_suffix();
	abstract protected function pk();
	abstract protected function meta_type(); // meta type for delete_metadata_by_mid(), 'post', 'comment', 'user', or 'term'

	// Common to all duplicated meta subclasses, provided by this base class
	protected function base_where() {

		$pk = $this->pk();
		$table = $this->table();
		$parent = $this->parent_column();

		return "
			EXISTS (
				SELECT 1
				FROM (
					SELECT
						{$parent}                 AS parent_id,
						meta_key,
						CRC32(meta_value)        AS vhash,
						MIN({$pk})               AS min_pk,
						COUNT(*)                 AS cnt
					FROM {$table}
					GROUP BY {$parent}, meta_key, CRC32(meta_value)
					HAVING cnt > 1
				) AS dupg
				WHERE dupg.parent_id = main.{$parent}
					AND dupg.meta_key  = main.meta_key
					AND dupg.vhash     = CRC32(main.meta_value)
					AND main.{$pk}     > dupg.min_pk
	        )";

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
			return (bool) delete_metadata_by_mid( $this->meta_type(), $mid );
		};
	}
	protected function date_column() {
		return null; // not used
	}

}

/**
 * Class ADBC_Cleanup_Duplicated_Postmeta_Handler
 * 
 * This class handles the cleanup of duplicated post metadata in WordPress.
 */
class ADBC_Cleanup_Duplicated_Postmeta_Handler extends ADBC_Cleanup_Duplicated_Meta_Handler_Base {

	protected function items_type() {
		return 'duplicated_postmeta';
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
	protected function parent_column() {
		return 'post_id';
	}
	protected function meta_type() {
		return 'post';
	}

}

/**
 * Class ADBC_Cleanup_Duplicated_Commentmeta_Handler
 * 
 * This class handles the cleanup of duplicated comment metadata in WordPress.
 */
class ADBC_Cleanup_Duplicated_Commentmeta_Handler extends ADBC_Cleanup_Duplicated_Meta_Handler_Base {

	protected function items_type() {
		return 'duplicated_commentmeta';
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
	protected function parent_column() {
		return 'comment_id';
	}
	protected function meta_type() {
		return 'comment';
	}

}

/**
 * Class ADBC_Cleanup_Duplicated_Usermeta_Handler
 * 
 * This class handles the cleanup of duplicated user metadata in WordPress.
 */
class ADBC_Cleanup_Duplicated_Usermeta_Handler extends ADBC_Cleanup_Duplicated_Meta_Handler_Base {

	protected function items_type() {
		return 'duplicated_usermeta';
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
	protected function parent_column() {
		return 'user_id';
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
					WHERE  {$this->base_where()}
				) AS tmp
			)
		";

		$deleted = $wpdb->query( $sql );

		return $deleted;

	}
}

/**
 * Class ADBC_Cleanup_Duplicated_Termmeta_Handler
 * 
 * This class handles the cleanup of duplicated term metadata in WordPress.
 */
class ADBC_Cleanup_Duplicated_Termmeta_Handler extends ADBC_Cleanup_Duplicated_Meta_Handler_Base {

	protected function items_type() {
		return 'duplicated_termmeta';
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
	protected function parent_column() {
		return 'term_id';
	}
	protected function meta_type() {
		return 'term';
	}

}

// Register the handler with the cleanup type registry.
ADBC_Cleanup_Type_Registry::register( 'duplicated_postmeta', new ADBC_Cleanup_Duplicated_Postmeta_Handler );
ADBC_Cleanup_Type_Registry::register( 'duplicated_commentmeta', new ADBC_Cleanup_Duplicated_Commentmeta_Handler );
ADBC_Cleanup_Type_Registry::register( 'duplicated_usermeta', new ADBC_Cleanup_Duplicated_Usermeta_Handler );
ADBC_Cleanup_Type_Registry::register( 'duplicated_termmeta', new ADBC_Cleanup_Duplicated_Termmeta_Handler );
