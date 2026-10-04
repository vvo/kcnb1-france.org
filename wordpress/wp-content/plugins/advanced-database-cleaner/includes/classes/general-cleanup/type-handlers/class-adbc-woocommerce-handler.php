<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Orphaned WooCommerce customer analytics handler.
 *
 * Analytics rows are orphaned when they reference a registered WordPress user
 * that no longer exists. Guest customer rows (user_id = 0) are excluded.
 */
class ADBC_Cleanup_Woocommerce_Orphaned_Customer_Analytics_Handler extends ADBC_Abstract_Cleanup_Handler {

	protected function items_type() {
		return 'woocommerce_orphaned_customer_analytics';
	}

	protected function table() {
		global $wpdb;
		return $wpdb->prefix . 'wc_customer_lookup';
	}

	protected function table_suffix() {
		return 'wc_customer_lookup';
	}

	protected function pk() {
		return 'customer_id';
	}

	protected function base_where() {
		global $wpdb;
		return "main.user_id > 0 AND NOT EXISTS (SELECT 1 FROM {$wpdb->users} users WHERE users.ID = main.user_id)";
	}

	protected function name_column() {
		return 'username';
	}

	protected function value_column() {
		return 'email';
	}

	protected function is_all_sites_sortable() {
		return true;
	}

	protected function sortable_columns() {
		return [ 'customer_id', 'user_id', 'email', 'username', 'site_id' ];
	}

	protected function include_size_in_list() {
		return false;
	}

	protected function extra_select() {
		return [ 'user_id' ];
	}

	protected function date_column() {
		return null; // Orphaned customer analytics do not support keep last.
	}

	protected function delete_helper() {
		return ''; // Unused because native cleanup delegates to SQL cleanup.
	}

	protected function delete_helper_tail_args() {
		return [];
	}

	protected function delete_native( $items ) {
		return $this->delete_sql( $items );
	}

	protected function purge_native() {
		return $this->purge_sql();
	}

}


/**
 * Orphaned WooCommerce product variations handler.
 *
 * Product variations live in wp_posts with post_type = 'product_variation'.
 * A variation is considered orphaned when its post_parent no longer points
 * to an existing product (the parent post is missing or is not a product).
 */
class ADBC_Cleanup_Woocommerce_Orphaned_Product_Variations_Handler extends ADBC_Cleanup_Posts_Handler_Base {

	protected function items_type() {
		return 'woocommerce_orphaned_product_variations';
	}
	protected function base_where() {
		global $wpdb;
		return "main.post_type = 'product_variation'
			AND (
				main.post_parent = 0
				OR NOT EXISTS (
					SELECT 1 FROM {$wpdb->posts} p
					WHERE p.ID = main.post_parent AND p.post_type = 'product'
				)
			)";
	}
	protected function date_column() {
		return null; // Orphaned product variations do not support keep last.
	}
	protected function include_size_in_list() {
		return false;
	}
	protected function sortable_columns() {
		return [ 'ID', 'post_title', 'post_content', 'post_date_gmt', 'site_id' ];
	}

	/**
	 * Delete selected orphaned variations and their related data across sites.
	 *
	 * @param array $items Selected items with site_id and id keys.
	 * @return int Number of variation rows deleted.
	 */
	protected function delete_sql( $items ) {
		if ( empty( $items ) ) {
			return 0;
		}

		$by_site = [];
		foreach ( $items as $item ) {
			$by_site[ $item['site_id'] ][] = (int) $item['id'];
		}

		$deleted = 0;

		foreach ( $by_site as $site_id => $ids ) {
			ADBC_Sites::instance()->switch_to_blog_id( $site_id );

			if ( ADBC_Tables::is_table_exists( $this->table() ) ) {
				$deleted += $this->delete_sql_by_ids( $ids );
			}

			ADBC_Sites::instance()->restore_blog();
		}

		return $deleted;
	}

	/**
	 * Purge orphaned variations and their related data in bounded chunks.
	 *
	 * @return int Number of variation rows deleted.
	 */
	protected function purge_sql() {
		global $wpdb;

		$deleted = 0;

		foreach ( ADBC_Sites::instance()->get_sites_list() as $site ) {
			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );

			if ( ! ADBC_Tables::is_table_exists( $this->table() ) ) {
				ADBC_Sites::instance()->restore_blog();
				continue;
			}

			while ( true ) {
				$ids = $wpdb->get_col( "
					SELECT main.{$this->pk()}
					FROM {$this->table()} main
					WHERE {$this->base_where()}
					LIMIT " . self::PURGE_CHUNK
				);

				if ( empty( $ids ) ) {
					break;
				}

				$chunk_deleted = $this->delete_sql_by_ids( $ids );
				if ( $chunk_deleted === 0 ) {
					break;
				}

				$deleted += $chunk_deleted;
			}

			ADBC_Sites::instance()->restore_blog();
		}

		return $deleted;
	}

	/**
	 * Delete variation rows and the related records cleaned by WOptimize.
	 *
	 * @param array $ids Variation post IDs.
	 * @return int Number of variation rows deleted.
	 */
	private function delete_sql_by_ids( $ids ) {
		global $wpdb;

		$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->term_relationships}
			WHERE object_id IN ( {$placeholders} )",
			...$ids
		) );

		$lookup_table = $wpdb->prefix . 'wc_product_meta_lookup';
		if ( ADBC_Tables::is_table_exists( $lookup_table ) ) {
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$lookup_table}
				WHERE product_id IN ( {$placeholders} )",
				...$ids
			) );
		}

		$comment_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT comment_ID
			FROM {$wpdb->comments}
			WHERE comment_post_ID IN ( {$placeholders} )",
			...$ids
		) );

		if ( ! empty( $comment_ids ) ) {
			$comment_ids = array_map( 'intval', $comment_ids );
			$comment_placeholders = implode( ',', array_fill( 0, count( $comment_ids ), '%d' ) );

			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$wpdb->commentmeta}
				WHERE comment_id IN ( {$comment_placeholders} )",
				...$comment_ids
			) );

			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$wpdb->comments}
				WHERE comment_ID IN ( {$comment_placeholders} )",
				...$comment_ids
			) );
		}

		$affected = $wpdb->query( $wpdb->prepare(
			"DELETE posts, postmeta
			FROM {$wpdb->posts} posts
			LEFT JOIN {$wpdb->postmeta} postmeta ON postmeta.post_id = posts.ID
			WHERE posts.ID IN ( {$placeholders} )",
			...$ids
		) );

		return $affected === false || $affected === 0 ? 0 : count( $ids );
	}

}


/**
 * Purge-only handler for expired WooCommerce customer sessions.
 *
 * This cleanup type intentionally has no count, list, or selected-row delete
 * behavior. It is exposed in the UI as a one-click maintenance tool.
 */
class ADBC_Cleanup_Woocommerce_Expired_Sessions_Handler implements ADBC_Cleanup_Type_Handler {

	public function count() {
		return [ 'count' => 0, 'size' => 0 ];
	}

	public function count_filtered( $args ) {
		return [ 'count' => 0, 'size' => 0 ];
	}

	public function list( $args ) {
		return [];
	}

	public function delete( $items ) {
		return 0;
	}

	public function purge() {
		global $wpdb;

		$deleted = 0;

		foreach ( ADBC_Sites::instance()->get_sites_list() as $site ) {
			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );

			$table = $wpdb->prefix . 'woocommerce_sessions';
			if ( ADBC_Tables::is_table_exists( $table ) ) {
				$affected = $wpdb->query( "DELETE FROM {$table} WHERE session_expiry < UNIX_TIMESTAMP()" );
				if ( $affected !== false ) {
					$deleted += $affected;
				}
			}

			ADBC_Sites::instance()->restore_blog();
		}

		return $deleted;
	}

	public function set_keep_last_config( $value ) {
		// Expired sessions do not support retention rules.
	}

	public function is_valid_sortable_column( $column ) {
		return false;
	}

	public function can_have_keep_last() {
		return false;
	}

}


// Register the handlers with the cleanup type registry.
ADBC_Cleanup_Type_Registry::register( 'woocommerce_orphaned_customer_analytics', new ADBC_Cleanup_Woocommerce_Orphaned_Customer_Analytics_Handler() );
ADBC_Cleanup_Type_Registry::register( 'woocommerce_orphaned_product_variations', new ADBC_Cleanup_Woocommerce_Orphaned_Product_Variations_Handler() );
ADBC_Cleanup_Type_Registry::register( 'woocommerce_expired_sessions', new ADBC_Cleanup_Woocommerce_Expired_Sessions_Handler() );
