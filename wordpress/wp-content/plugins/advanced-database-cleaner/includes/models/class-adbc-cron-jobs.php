<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC cron jobs class.
 * 
 * This class provides cron jobs functions.
 */
class ADBC_Cron_Jobs {

	/**
	 * Get total cron jobs.
	 * 
	 * @return int Total cron jobs.
	 */
	public static function get_total_cron_jobs_count() {

		$sites = ADBC_Sites::instance()->get_sites_list();

		$total_tasks = 0;

		foreach ( $sites as $site ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );

			$cron_jobs = self::get_cron_array();
			foreach ( $cron_jobs as $timestamp => $tasks ) {
				$total_tasks += count( $tasks );
			}

			ADBC_Sites::instance()->restore_blog();

		}

		return $total_tasks;

	}

	/**
	 * Get cron jobs names for all the sites.
	 * 
	 * @return array Associative cron jobs names.
	 */
	public static function get_cron_jobs_names() {

		$all_cron_jobs = [];

		$sites = ADBC_Sites::instance()->get_sites_list();

		foreach ( $sites as $site ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );

			$site_cron_jobs = self::get_cron_array();

			foreach ( $site_cron_jobs as $timestamp => $tasks ) {
				foreach ( $tasks as $hook => $events ) {
					$all_cron_jobs[ $hook ] = true;
				}
			}

			ADBC_Sites::instance()->restore_blog();

		}

		return $all_cron_jobs;

	}

	/**
	 * Get the core cron array.
	 * 
	 * This function retrieves the core cron array for the current site.
	 * 
	 * @return array The core cron array.
	 */
	public static function get_cron_array() {

		// Get the core cron array.
		$core_cron = _get_cron_array();

		if ( ! is_array( $core_cron ) )
			return [];

		// Return the core cron array.
		return $core_cron;

	}

	/**
	 * Get the cron jobs list for the endpoint.
	 *
	 * @param array $filters Output of sanitize_filters().
	 *
	 * @return WP_REST_Response The list of cron jobs.
	 */
	public static function get_cron_jobs_list( $filters ) {

		// Prepare variables
		$cron_jobs_list = [];
		$total_cron_jobs = 0;

		$scan_counter = new ADBC_Scan_Counter();

		$startRecord = ( $filters['current_page'] - 1 ) * $filters['items_per_page'];
		$endRecord = $startRecord + $filters['items_per_page'];
		$currentRecord = 0;

		$limit = ADBC_Settings::instance()->get_setting( 'database_rows_batch' );
		$offset = 0;

		do { // Loop through all cron jobs in batches of $limit to avoid memory issues

			$cron_jobs = self::get_cron_jobs_list_batch( $filters, $limit, $offset );

			$fetched_count = count( $cron_jobs );

			if ( ADBC_VERSION_TYPE === 'PREMIUM' )
				ADBC_Scan_Results::instance()->load_scan_results_to_items_rows( $cron_jobs, 'cron_jobs' );
			else
				ADBC_Common_Model::load_scan_results_to_items_for_free_version( $cron_jobs );

			ADBC_Hardcoded_Items::instance()->load_hardcoded_scan_results_to_items_rows( $cron_jobs, 'cron_jobs' ); // Load hardcoded items to the cron jobs rows

			foreach ( $cron_jobs as $index => $cron_job ) {

				$scan_counter->refresh_categorization_count( $cron_job->belongs_to );

				if ( ! ADBC_Common_Model::is_item_satisfies_belongs_to( $filters, $cron_job->belongs_to ) )
					continue;

				$total_cron_jobs++; // Count cron jobs that satisfy all filters and belongs_to

				// Only process the current batch if it's within the desired page range
				if ( $currentRecord >= $startRecord && $currentRecord < $endRecord ) {

					$cron_jobs_list[] = [ 
						// This id is used to identify the cron job in the frontend and take actions on it
						'composite_id' => [ 
							'items_type' => 'cron_jobs',
							'site_id' => (int) $cron_job->site_id,
							'timestamp' => (int) $cron_job->timestamp,
							'name' => $cron_job->name,
							'args' => $cron_job->args
						],
						'name' => $cron_job->name, // Used in the known addons modal & "show value modal". To be generic and work for all items types.
						'hook_name' => $cron_job->name,
						'args' => $cron_job->args,
						'timestamp' => $cron_job->timestamp,
						'frequency' => $cron_job->frequency,
						'frequency_display' => $cron_job->frequency_display,
						'interval' => $cron_job->interval,
						'site_id' => $cron_job->site_id,
						'has_action' => $cron_job->has_action ? 'yes' : 'no',
						'action' => $cron_job->action,
						'action_file' => $cron_job->action_file,
						'belongs_to' => $cron_job->belongs_to,
						'known_plugins' => $cron_job->known_plugins,
						'known_themes' => $cron_job->known_themes,
					];
				}

				$currentRecord++;
			}

			$offset += $limit;

		} while ( $fetched_count == $limit ); // Continue if the last batch was full

		// Loop over the $cron_jobs_list and $scan_counter add the plugins/themes names from the dictionary if they are empty
		// This is because load_scan_results_to_rows() only loads the names of the plugins/themes that are currently installed
		if ( ADBC_VERSION_TYPE === 'PREMIUM' )
			ADBC_Dictionary::add_missing_addons_names_from_dictionary( $cron_jobs_list, $scan_counter, 'cron_jobs' );

		// Calculate total number of pages to verify that the current page sent by the user is within the range
		$total_real_pages = max( 1, ceil( $total_cron_jobs / $filters['items_per_page'] ) );

		return ADBC_Rest::success( "", [ 
			'items' => $cron_jobs_list,
			'total_items' => $total_cron_jobs,
			'real_current_page' => min( $filters['current_page'], $total_real_pages ),
			'categorization_count' => $scan_counter->get_categorization_count(),
			'plugins_count' => $scan_counter->get_plugins_count(),
			'themes_count' => $scan_counter->get_themes_count(),
		] );
	}

	/**
	 * Get the cron jobs list that satisfy the UI filters.
	 *
	 * @param array $filters Output of sanitize_filters().
	 * @param int $limit Limit for the number of rows to return.
	 * @param int $offset Offset for the number of rows to return.
	 *
	 * @return array List of cron jobs that satisfy the filters.
	 */
	private static function get_cron_jobs_list_batch( $filters, $limit, $offset ) {

		$sites_list = ADBC_Sites::instance()->get_sites_list( $filters['site_id'] );
		$all_cron_jobs = [];

		foreach ( $sites_list as $site ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );

			$cron_jobs = self::get_cron_array();
			$schedules = wp_get_schedules();

			foreach ( $cron_jobs as $timestamp => $tasks ) {
				foreach ( $tasks as $hook => $events ) {
					foreach ( $events as $event_key => $event ) {

						// Determine human-readable frequency
						if ( empty( $event['schedule'] ) ) {
							$frequency_display = __( 'Once', 'advanced-database-cleaner' );
						} else {
							$key = $event['schedule'];
							if ( isset( $schedules[ $key ]['display'] ) && $schedules[ $key ]['display'] ) {
								$frequency_display = $schedules[ $key ]['display'];
							} else {
								// Fallback: prettify custom schedule key
								$frequency_display = ucwords( str_replace( '_', ' ', (string) $key ) );
							}
						}

						// Create a cron job object
						$cron_job = (object) [ 
							'name' => $hook,
							'hook_name' => $hook,
							'args' => $event['args'],
							'timestamp' => $timestamp,
							'frequency' => ! empty( $event['schedule'] ) ? $event['schedule'] : 'once',
							'frequency_display' => $frequency_display,
							'interval' => ! empty( $event['interval'] ) ? $event['interval'] : 'N/A',
							'site_id' => $site['id'],
							'has_action' => has_action( $hook ) !== false,
							'action' => self::get_hook_action_info( $hook )['action'],
							'action_file' => self::get_hook_action_info( $hook )['action_file'],
						];

						// Apply filters
						if ( ! self::cron_job_satisfies_filters( $cron_job, $filters ) ) {
							continue;
						}

						$all_cron_jobs[] = $cron_job;
					}
				}
			}

			ADBC_Sites::instance()->restore_blog();
		}

		// Apply sorting
		$all_cron_jobs = self::sort_cron_jobs( $all_cron_jobs, $filters );

		// Apply pagination
		return array_slice( $all_cron_jobs, $offset, $limit );
	}

	/**
	 * Check if a cron job satisfies the UI filters.
	 *
	 * @param object $cron_job The cron job object.
	 * @param array $filters Output of sanitize_filters().
	 *
	 * @return bool True if the cron job satisfies the filters.
	 */
	private static function cron_job_satisfies_filters( $cron_job, $filters ) {

		// Search filter
		if ( ! empty( $filters['search_for'] ) && ! empty( $filters['search_in'] ) ) {

			$needle = strtolower( $filters['search_for'] );

			switch ( $filters['search_in'] ) {
				case 'name':
					if ( strpos( strtolower( $cron_job->name ), $needle ) === false ) {
						return false;
					}
					break;

				case 'value':

					foreach ( $cron_job->args as $arg ) {
						if ( strpos( strtolower( $arg ), $needle ) !== false ) {
							break 2;
						}
					}

					return false;

				case 'all':
					if ( strpos( strtolower( $cron_job->name ), $needle ) !== false ) {
						break;
					}
					foreach ( $cron_job->args as $arg ) {
						if ( strpos( strtolower( $arg ), $needle ) !== false ) {
							break 2;
						}
					}
					return false;
			}
		}

		// frequency filter
		if ( $filters['frequency'] !== 'all' ) {
			if ( $cron_job->frequency !== $filters['frequency'] ) {
				return false;
			}
		}

		// interval filter
		if ( $filters['interval'] !== 'all' ) {
			if ( $cron_job->interval !== $filters['interval'] ) {
				return false;
			}
		}

		// has_action filter
		if ( isset( $filters['has_action'] ) && $filters['has_action'] !== 'all' ) {
			$expected = $filters['has_action'] === 'yes';
			if ( (bool) $cron_job->has_action !== $expected ) {
				return false;
			}
		}

		return true;

	}

	/**
	 * Get the action label and source file for the first callback registered on a hook.
	 *
	 * @param string $hook Hook name.
	 * @return array { action: string, action_file: string }
	 */
	private static function get_hook_action_info( $hook ) {

		global $wp_filter;

		$result = [ 'action' => '', 'action_file' => '' ];

		if ( empty( $hook ) || ! isset( $wp_filter[ $hook ] ) )
			return $result;

		$hook_obj = $wp_filter[ $hook ];
		if ( ! is_object( $hook_obj ) || empty( $hook_obj->callbacks ) )
			return $result;

		// Find the first registered callback
		$callback = null;
		foreach ( $hook_obj->callbacks as $priority => $callbacks ) {
			if ( empty( $callbacks ) )
				continue;
			foreach ( $callbacks as $cb ) {
				if ( isset( $cb['function'] ) ) {
					$callback = $cb['function'];
					break 2;
				}
			}
		}

		if ( $callback === null )
			return $result;

		$result['action'] = self::callback_to_string( $callback );
		$result['action_file'] = self::get_callback_file_info( $callback );

		return $result;

	}

	/**
	 * Convert a PHP callback to a readable string.
	 *
	 * @param mixed $callback Callback.
	 * @return string
	 */
	private static function callback_to_string( $callback ) {

		if ( is_string( $callback ) )
			return $callback . '()';

		if ( is_array( $callback ) && count( $callback ) === 2 ) {
			list( $obj_or_class, $method ) = $callback;
			if ( is_object( $obj_or_class ) )
				return get_class( $obj_or_class ) . '->' . (string) $method . '()';
			return (string) $obj_or_class . '::' . (string) $method . '()';
		}

		if ( $callback instanceof \Closure )
			return __( 'Anonymous function', 'advanced-database-cleaner' );

		if ( is_object( $callback ) && method_exists( $callback, '__invoke' ) )
			return get_class( $callback ) . '->__invoke()';

		return __( 'Unknown action', 'advanced-database-cleaner' );

	}

	/**
	 * Get the file:line info for a callback.
	 *
	 * @param mixed $callback Callback.
	 * @return string File:line info.
	 */
	private static function get_callback_file_info( $callback ) {

		try {

			if ( is_array( $callback ) && count( $callback ) === 2 ) {
				$class = is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];
				$method = (string) $callback[1];
				$ref = PHP_VERSION_ID >= 80400
					? \ReflectionMethod::createFromMethodName( $class . '::' . $method )
					: new \ReflectionMethod( $class, $method );
			} elseif ( is_string( $callback ) && strpos( $callback, '::' ) !== false ) {
				$ref = PHP_VERSION_ID >= 80400
					? \ReflectionMethod::createFromMethodName( $callback )
					: new \ReflectionMethod( ...explode( '::', $callback, 2 ) );
			} elseif ( $callback instanceof \Closure ) {
				$ref = new \ReflectionFunction( $callback );
			} elseif ( is_object( $callback ) ) {
				$ref = new \ReflectionMethod( $callback, '__invoke' );
			} else {
				$ref = new \ReflectionFunction( $callback );
			}

			$file = $ref->getFileName();
			if ( ! empty( $file ) )
				return $file . ':' . $ref->getStartLine();

		} catch (\Throwable $e) {
			return '';
		}

	}

	/**
	 * Sort cron jobs based on filters.
	 *
	 * @param array $cron_jobs Array of cron job objects.
	 * @param array $filters Output of sanitize_filters().
	 *
	 * @return array Sorted array of cron job objects.
	 */
	private static function sort_cron_jobs( $cron_jobs, $filters ) {

		$sort_col = $filters['sort_by'] ?? '';
		$sort_dir = strtoupper( $filters['sort_order'] ?? 'ASC' );

		$allowed_columns = [ 'hook_name', 'timestamp', 'frequency_display', 'site_id', 'interval' ];

		if ( ! in_array( $sort_col, $allowed_columns ) ) {
			return $cron_jobs;
		}

		usort( $cron_jobs, function ($a, $b) use ($sort_col, $sort_dir) {

			$val_a = $a->$sort_col;
			$val_b = $b->$sort_col;

			// Handle different data types
			if ( is_numeric( $val_a ) && is_numeric( $val_b ) ) {
				$result = $val_a <=> $val_b;
			} else {
				$result = strcasecmp( (string) $val_a, (string) $val_b );
			}

			return $sort_dir === 'DESC' ? -$result : $result;
		} );

		return $cron_jobs;
	}

	/**
	 * Count the total number of cron jobs that are not scanned.
	 * 
	 * @return int Total not scanned cron jobs.
	 */
	public static function count_total_not_scanned_cron_jobs() {

		$total_not_scanned = 0;

		$sites_list = ADBC_Sites::instance()->get_sites_list();

		foreach ( $sites_list as $site ) {

			$cron_jobs_names = self::get_site_cron_jobs_names( $site['id'] );
			$not_scanned_count = 0;

			if ( ADBC_VERSION_TYPE === 'PREMIUM' )
				$not_scanned_count = ADBC_Scan_Utils::count_not_scanned_items_in_list( "cron_jobs", $cron_jobs_names );
			else
				$not_scanned_count = ADBC_Common_Model::count_not_scanned_items_in_list_for_free( "cron_jobs", $cron_jobs_names );

			$total_not_scanned += $not_scanned_count;

		}

		return $total_not_scanned;

	}

	/**
	 * Count the total number of cron jobs that have no registered action (no callbacks hooked to their hook name).
	 *
	 * @return int Total cron jobs without action handlers.
	 */
	public static function count_total_cron_jobs_with_no_action() {

		$total_no_action = 0;

		$sites_list = ADBC_Sites::instance()->get_sites_list();

		foreach ( $sites_list as $site ) {
			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );
			$cron_jobs = self::get_cron_array();
			foreach ( $cron_jobs as $timestamp => $tasks ) {
				foreach ( $tasks as $hook => $events ) {
					if ( has_action( $hook ) === false ) {
						// Count each scheduled event for this hook
						$total_no_action += count( $events );
					}
				}
			}
			ADBC_Sites::instance()->restore_blog();
		}

		return $total_no_action;
	}

	/**
	 * Get cron jobs names for a site.
	 * 
	 * @param int $site_id Site ID.
	 * 
	 * @return array Cron jobs names.
	 */
	public static function get_site_cron_jobs_names( $site_id ) {

		$all_cron_jobs_names = [];

		ADBC_Sites::instance()->switch_to_blog_id( $site_id );

		$cron_jobs = self::get_cron_array();

		foreach ( $cron_jobs as $timestamp => $tasks ) {
			foreach ( $tasks as $hook => $events ) {
				foreach ( $events as $event_key => $event ) {
					$all_cron_jobs_names[] = $hook;
				}
			}
		}

		ADBC_Sites::instance()->restore_blog();

		return $all_cron_jobs_names;

	}

	/**
	 * Get cron jobs for a site.
	 * 
	 * @param int $site_id Site ID.
	 * 
	 * @return array Cron jobs objects.
	 */
	public static function get_site_cron_jobs( $site_id ) {

		$cron_jobs = [];

		ADBC_Sites::instance()->switch_to_blog_id( $site_id );

		$all_cron_jobs = self::get_cron_array();

		foreach ( $all_cron_jobs as $timestamp => $tasks ) {
			foreach ( $tasks as $hook => $events ) {
				foreach ( $events as $event_key => $event ) {
					// Create a cron job object
					$cron_job = (object) [ 
						'name' => $hook,
						'args' => $event['args'],
						'timestamp' => $timestamp,
					];

					$cron_jobs[] = $cron_job;
				}
			}
		}

		ADBC_Sites::instance()->restore_blog();

		return $cron_jobs;

	}

	/**
	 * Delete grouped cron jobs. Cron jobs are grouped by site ID as key.
	 * 
	 * @param array $grouped_selected Grouped selected cron jobs to delete.
	 * 
	 * @return array An array of cron job names that were not processed (not deleted).
	 */
	public static function delete_cron_jobs( $grouped_selected ) {

		$not_processed = [];

		foreach ( $grouped_selected as $site_id => $group ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site_id );

			foreach ( $group as $selected ) {

				// Try to unschedule the cron job
				$success = wp_unschedule_event( $selected['timestamp'], $selected['name'], $selected['args'] );

				if ( ! $success ) {
					$not_processed[] = $selected['name'];
				}
			}

			ADBC_Sites::instance()->restore_blog();
		}

		return $not_processed;
	}

	/**
	 * Get cron job hook names that still exist anywhere across the network from a provided list.
	 *
	 * @param array $hooks List of cron hook names to check for existence.
	 *
	 * @return array Existing hook names found across all sites.
	 */
	public static function get_cron_jobs_names_that_exists_from_list( $hooks ) {

		if ( empty( $hooks ) || ! is_array( $hooks ) )
			return [];

		$existing_hooks = [];
		$sites = ADBC_Sites::instance()->get_sites_list();

		foreach ( $sites as $site ) {
			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );
			$cron = self::get_cron_array();
			foreach ( $cron as $timestamp => $tasks ) {
				foreach ( $tasks as $hook => $events ) {
					$existing_hooks[ $hook ] = true;
				}
			}
			ADBC_Sites::instance()->restore_blog();
		}

		$result = [];
		foreach ( $hooks as $hook ) {
			if ( isset( $existing_hooks[ $hook ] ) ) {
				$result[] = $hook;
			}
		}

		return $result;
	}

}