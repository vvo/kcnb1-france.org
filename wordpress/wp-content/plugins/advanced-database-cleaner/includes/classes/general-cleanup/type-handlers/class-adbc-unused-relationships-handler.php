<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Class ADBC_Cleanup_Unused_Relationships_Handler
 * 
 * This class handles the cleanup of unused term relationships in WordPress.
 */
class ADBC_Cleanup_Unused_Relationships_Handler extends ADBC_Abstract_Cleanup_Handler {

	// Required methods from ADBC_Abstract_Cleanup_Handler
	protected function items_type() {
		return 'unused_relationships';
	}
	protected function table() {
		global $wpdb;
		return $wpdb->term_relationships;
	}
	protected function table_suffix() {
		return 'term_relationships';
	}
	protected function pk() {
		return 'object_id';
	}
	protected function base_where() {
		global $wpdb;
		return "( main.term_taxonomy_id = 1 AND main.object_id NOT IN ( SELECT ID FROM {$wpdb->posts} ) )";
	}
	protected function name_column() {
		return 'term_taxonomy_id';
	}
	protected function value_column() {
		return 'term_order';
	}
	protected function is_all_sites_sortable() {
		return true;
	}
	protected function sortable_columns() {
		return [ 
			'object_id',
			'term_taxonomy_id',
			'term_order',
			'size',
			'site_id'
		];
	}
	protected function delete_helper() {
		return static function ($object_id, $term_taxonomy_id) {

			global $wpdb;

			$object_id = (int) $object_id;
			$term_taxonomy_id = (int) $term_taxonomy_id;

			// Make sure the core function is available.
			if ( ! function_exists( 'wp_delete_object_term_relationships' ) ) {
				return false;
			}

			// Resolve term_taxonomy_id -> taxonomy.
			$taxonomy = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT taxonomy FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id = %d",
					$term_taxonomy_id
				)
			);

			// If we cannot resolve taxonomy or it does not exist, bail.
			if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
				return false;
			}

			// Use native WP API. This will:
			// - delete all relationships for this object in the given taxonomy
			// - keep term counts in sync.
			$result = wp_delete_object_term_relationships( $object_id, $taxonomy );

			// Be defensive about possible return types.
			if ( is_wp_error( $result ) ) {
				return false;
			}

			// Let the caller treat any non-false, non-null as "success".
			return $result;

		};
	}

	// Optional methods for this handler
	protected function date_column() {
		return null;
	}

	// Overridable methods from ADBC_Abstract_Cleanup_Handler
	protected function add_composite_id( &$rows ) {

		foreach ( $rows as &$row ) {
			$row['composite_id'] = [ 
				'items_type' => $this->items_type(),
				'site_id' => (int) $row['site_id'],
				'id' => (int) $row['object_id'],
				'term_taxonomy_id' => (int) $row['term_taxonomy_id'],
			];
		}

		return $rows;

	}

	protected function delete_sql( $items ) {

		global $wpdb;

		if ( empty( $items ) ) {
			return 0;
		}

		$by_site = [];
		foreach ( $items as $row ) {
			$by_site[ $row['site_id'] ][] = [ 
				'object_id' => (int) $row['id'],
				'taxonomy_id' => (int) $row['term_taxonomy_id'],
			];
		}

		$deleted = 0;

		foreach ( $by_site as $site_id => $pairs ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site_id );

			foreach ( $pairs as $pair ) {

				$sql = "
					DELETE main
					FROM   {$this->table()} AS main
					WHERE  main.object_id = %d
					   	   AND main.term_taxonomy_id = %d
				";
				$query = $wpdb->prepare( $sql, $pair['object_id'], $pair['taxonomy_id'] );

				$deleted += $wpdb->query( $query );

			}

			ADBC_Sites::instance()->restore_blog();

		}

		return $deleted;

	}

	protected function delete_native( $items ) {

		if ( empty( $items ) ) {
			return 0;
		}

		// Group items by site_id so we can switch blogs properly.
		$by_site = [];
		foreach ( $items as $row ) {
			$site_id = (int) $row['site_id'];
			$by_site[ $site_id ][] = [ 
				'object_id' => (int) $row['id'],
				'term_taxonomy_id' => (int) $row['term_taxonomy_id'],
			];
		}

		$helper = $this->delete_helper();

		$deleted = 0;

		foreach ( $by_site as $site_id => $pairs ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site_id );

			foreach ( $pairs as $pair ) {

				// The helper is expected to handle the actual native deletion,
				// e.g. via wp_delete_object_term_relationships().
				$result = $helper(
					$pair['object_id'],
					$pair['term_taxonomy_id']
				);

				// Count as deleted on any non-false, non-null result.
				if ( $result !== false && $result !== null ) {
					$deleted++;
				}
			}

			ADBC_Sites::instance()->restore_blog();
		}

		return $deleted;
	}

	protected function purge_native() {

		global $wpdb;

		$helper = $this->delete_helper();

		$chunk = self::PURGE_CHUNK; // number of items to delete per loop
		$deleted = 0;

		foreach ( ADBC_Sites::instance()->get_sites_list() as $site ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );

			// Make sure the relationships table exists for this blog.
			if ( ! ADBC_Tables::is_table_exists( $this->table() ) ) {
				ADBC_Sites::instance()->restore_blog();
				continue; // skip to next site
			}

			do {
				// Select a batch of unused relationships that match our base_where().
				$sql = "
					SELECT main.object_id, main.term_taxonomy_id
					FROM  {$this->table()} AS main
					WHERE {$this->base_where()}
					LIMIT %d
				";

				$query = $wpdb->prepare( $sql, $chunk );
				$rows = $wpdb->get_results( $query, ARRAY_A );

				if ( empty( $rows ) ) {
					break;
				}

				foreach ( $rows as $row ) {
					$result = $helper(
						(int) $row['object_id'],
						(int) $row['term_taxonomy_id']
					);

					if ( $result !== false && $result !== null ) {
						$deleted++;
					}
				}

				// Loop again if we filled the chunk, in case more rows remain.
			} while ( count( $rows ) === $chunk );

			ADBC_Sites::instance()->restore_blog();

		}

		return $deleted;

	}

}

// Register the handler with the cleanup type registry.
ADBC_Cleanup_Type_Registry::register( 'unused_relationships', new ADBC_Cleanup_Unused_Relationships_Handler );