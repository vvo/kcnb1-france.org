<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Class ADBC_Cleanup_Tables_Handler
 * 
 * This class serves as a handler for cleaning up database tables in WordPress.
 */
abstract class ADBC_Cleanup_Tables_Handler extends ADBC_Abstract_Cleanup_Handler {

	// Required, all tables subclasses must supply
	abstract protected function items_type();

	// Common to all tables subclasses, provided by this base class
	protected function base_where() {
		return ''; // not used
	}
	protected function table() {
		return ''; // not used
	}
	protected function table_suffix() {
		return ''; // not used
	}
	protected function pk() {
		return ''; // not used
	}
	protected function name_column() {
		return ''; // not used
	}
	protected function value_column() {
		return ''; // not used
	}
	protected function is_all_sites_sortable() {
		return true; // not used
	}
	protected function sortable_columns() {
		return []; // not used
	}
	protected function delete_helper() {
		return ''; // not used
	}

}

class ADBC_Cleanup_Optimize_Tables_Handler extends ADBC_Cleanup_Tables_Handler {

	// Required, all tables subclasses must supply
	protected function items_type() {
		return 'tables_to_optimize';
	}

	public function count_filtered( $args = [] ) {

		$tables_to_optimize = ADBC_Tables::get_tables_to_optimize();

		$total_overhead = 0;
		foreach ( $tables_to_optimize as $table ) {
			$total_overhead += $table->overhead;
		}

		return [ 
			'count' => count( $tables_to_optimize ),
			'size' => $total_overhead
		];

	}

	public function purge() {

		$to_optimize_tables = ADBC_Tables::get_tables_to_optimize();

		// get only tables names
		$to_optimize_tables = array_map( function ($table) {
			return $table->table_name;
		}, $to_optimize_tables );

		$not_optimized = ADBC_Tables::optimize_tables( $to_optimize_tables );

		return count( $to_optimize_tables ) - count( $not_optimized );

	}

	public function list( $args ) {
		return []; // not used
	}

}

class ADBC_Cleanup_Repair_Tables_Handler extends ADBC_Cleanup_Tables_Handler {

	// Required, all tables subclasses must supply
	protected function items_type() {
		return 'tables_to_repair';
	}

	public function count_filtered( $args = [] ) {
		return [ 
			'count' => ADBC_Tables::get_tables_to_repair()[0],
			'size' => 0 // size is not applicable for optimize
		];
	}

	public function purge() {

		$to_repair_tables = ADBC_Tables::get_tables_to_repair()[1];

		$not_repaired = ADBC_Tables::repair_tables( $to_repair_tables );

		return count( $to_repair_tables ) - count( $not_repaired );

	}

	public function list( $args ) {
		return []; // not used
	}

}

// Register the handlers
ADBC_Cleanup_Type_Registry::register( 'tables_to_optimize', new ADBC_Cleanup_Optimize_Tables_Handler() );
ADBC_Cleanup_Type_Registry::register( 'tables_to_repair', new ADBC_Cleanup_Repair_Tables_Handler() );