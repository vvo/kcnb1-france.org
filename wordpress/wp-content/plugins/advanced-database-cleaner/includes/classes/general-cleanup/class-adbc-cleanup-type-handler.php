<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Interface ADBC_Cleanup_Type_Handler
 * 
 * This interface defines the methods required for a cleanup type handler.
 */
interface ADBC_Cleanup_Type_Handler {

	/**
	 * Counts the total number of items and their total size across all sites.
	 * This method is used to get the count without any filters.
	 * 
	 * @return array{count, size}
	 */
	public function count();

	/**
	 * Counts the total number of items and their total size across all sites.
	 * This method can be filtered by all filters available in the list method.
	 * 
	 * @param array|null $args Optional arguments to filter the count, otherwise counts all items.
	 * 
	 * @return array{count, size}
	 */
	public function count_filtered( $args );

	/**
	 * Lists the items of this type across all sites.
	 * This method can be filtered by all filters available in the args array.
	 * 
	 * @param array $args The arguments to filter the list.
	 * 
	 * @return array An array of items with their details.
	 */
	public function list( $args );

	/**
	 * Deletes the specified items across all sites.
	 * The items should be an array of arrays with 'site_id' and 'id' keys.
	 * 
	 * @param array $items The items to delete, each item should have 'site_id' and 'id'.
	 * 
	 * @return int The number of affected rows.
	 */
	public function delete( $items );

	/**
	 * Purges all items of this type across all sites.
	 * This method deletes all items that match the base WHERE clause and the keep last rules.
	 * 
	 * @return int The number of deleted items.
	 */
	public function purge();

	/**
	 * Sets the "keep last" override for this handler.
	 * This can be used to set a custom rule for the "keep last" feature.
	 * 
	 * @param array|false|null $value The value to set, can be NULL (use global option), FALSE (no keep‐last for this run), or an array (custom rule).
	 */
	public function set_keep_last_config( $value );

	/**
	 * Checks if the given column is valid for sorting.
	 *
	 * @return bool True if the column is valid for sorting, false otherwise.
	 */
	public function is_valid_sortable_column( $column );

	/**
	 * Checks if the handler can have a "keep last" feature.
	 * This is true if the handler has a date column defined.
	 *
	 * @return bool True if the handler can have a "keep last" feature, false otherwise.
	 */
	public function can_have_keep_last();

}