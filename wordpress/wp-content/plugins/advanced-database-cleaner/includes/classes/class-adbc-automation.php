<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC_Automation class.
 *
 * This class manages automation tasks, including creating, updating, deleting,
 * and running scheduled tasks.
 */
class ADBC_Automation extends ADBC_Singleton {

	public const AUTOMATION_EVENT_DIR = ADBC_UPLOADS_DIR_PATH . '/automation_events';

	/**
	 * Get raw automation tasks from the database (without computed fields like next_run).
	 * 
	 * @return array
	 */
	private function get_raw_tasks() {

		$tasks = get_option( 'adbc_plugin_automation' );

		// sort by created_at descending
		if ( is_array( $tasks ) ) {
			uasort( $tasks, function ($a, $b) {
				return ( $b['created_at'] ?? 0 ) <=> ( $a['created_at'] ?? 0 );
			} );
		}

		return is_array( $tasks ) ? $tasks : [];

	}

	/**
	 * Get all existing automation tasks from the database.
	 * 
	 * @return array
	 */
	public function tasks() {
		$tasks = $this->get_raw_tasks();

		foreach ( $tasks as &$task ) {
			$scheduled = $task['active'] ? wp_next_scheduled( 'adbc_cron_automation', [ $task['id'] ] ) : '';
			$task['next_run'] = $this->compute_virtual_next_run( $task, $scheduled );
		}
		return $tasks;
	}

	/**
	 * Save the automation tasks to the database.
	 * 
	 * @param array $tasks associative array of tasks, where keys are task IDs and values are task details.
	 * 
	 * @return void
	 */
	private function save( $tasks ) {
		update_option( 'adbc_plugin_automation', $tasks, false );
	}

	/**
	 * Create a new automation task.
	 * 
	 * @param array $task associative array containing task details.
	 * 
	 * @return string|null The ID of the created task or null if the task could not be scheduled.
	 */
	public function create( $task ) {

		// Generate a unique ID for the task and set default values
		$task['id'] = wp_generate_uuid4();
		$task['last_run'] = 0;
		$task['created_at'] = time();
		$task['updated_at'] = 0;

		// Add and schedule the new task
		$all = $this->get_raw_tasks();
		$all[ $task['id'] ] = $task;

		if ( ! $this->schedule( $task ) )
			return null; // If scheduling fails, return null

		$this->save( $all );

		return $task['id'];

	}

	/**
	 * Update an existing automation task.
	 * 
	 * @param string $id The ID of the task to update.
	 * @param array $task associative array containing updated task details.
	 * 
	 * @return array|null The updated task details or null if we failed to update it (e.g., unknown ID, unable to schedule).
	 */
	public function update( $id, $task ) {

		$all = $this->get_raw_tasks();

		// unknown id
		if ( ! isset( $all[ $id ] ) ) {
			return null;
		}

		// snapshot before change
		$previous = $all[ $id ];

		// Overwrite with new values
		$task['id'] = $id; // Ensure the ID remains the same
		$task['last_run'] = $previous['last_run'] ?? 0; // Keep last run time from previous task
		$task['created_at'] = $previous['created_at']; // Keep created_at from previous task
		$task['updated_at'] = time();
		$all[ $id ] = $task;

		// Decide if we need to reschedule
		$previous_active = (bool) ( $previous['active'] ?? true );
		$new_active = (bool) ( $task['active'] ?? true );

		$frequency_changed = ( $task['frequency'] ?? '' ) !== ( $previous['frequency'] ?? '' );
		$date_changed = ( $task['start_datetime'] ?? '' ) !== ( $previous['start_datetime'] ?? '' );

		// Unschedule the task if the user deactivated it
		if ( $previous_active && ! $new_active ) {
			if ( ! $this->unschedule( $id ) ) {
				return null;
			}
		} elseif ( $new_active && ( ! $previous_active || $frequency_changed || $date_changed ) ) {
			// Reschedule the task if it becomes active OR frequency/start_time changed while it's active
			if ( ! $this->unschedule( $id ) ) {
				return null;
			}
			if ( ! $this->schedule( $task ) ) {
				return null;
			}

		}

		$this->save( $all );

		return $task;

	}

	/**
	 * Delete an automation task by its ID.
	 * 
	 * @param string $id The ID of the task to delete.
	 * 
	 * @return bool True if the task was deleted, false if the ID was unknown or deletion failed.
	 */
	public function delete( $id ) {

		$all = $this->get_raw_tasks();

		// unknown id
		if ( ! isset( $all[ $id ] ) ) {
			return false;
		}

		// unschedule fails
		if ( ! $this->unschedule( $id ) ) {
			return false;
		}

		unset( $all[ $id ] );
		$this->save( $all );

		self::delete_events_file( $id );

		return true;

	}

	/**
	 * Get a specific automation task by its ID.
	 * 
	 * @param string $id The ID of the task to retrieve.
	 * 
	 * @return array|null The task details if found, null if the task does not exist.
	 */
	public function get_task( $id ) {

		$all = $this->tasks();

		// unknown id
		if ( ! isset( $all[ $id ] ) ) {
			return null;
		}

		return $all[ $id ];

	}

	/**
	 * Schedule a task based on its frequency and start time.
	 * 
	 * @param array $task
	 * 
	 * @return bool True if the task was scheduled successfully, false if there was an error.
	 */
	public function schedule( $task ) {

		if ( ! $task['active'] ) {
			return true;
		}

		$start_timestamp = $task['start_datetime'];
		$frequency = $task['frequency'];

		if ( $frequency === 'adbc_once' )
			return wp_schedule_single_event( $start_timestamp, 'adbc_cron_automation', [ $task['id'] ] ) === true;
		else
			return wp_schedule_event( $start_timestamp, $frequency, 'adbc_cron_automation', [ $task['id'] ] ) === true;

	}

	/**
	 * Unschedule a task by its ID.
	 * 
	 * @param string $id The ID of the task to unschedule.
	 * 
	 * @return bool True if the task was unscheduled successfully, false otherwise.
	 */
	public function unschedule( $id ) {
		return is_int( wp_clear_scheduled_hook( 'adbc_cron_automation', [ $id ] ) );
	}

	/**
	 * Static proxy for run_task_by_id to be used in the hooks.
	 * 
	 * @param string $id The ID of the task to run.
	 * 
	 * @return void
	 */
	public static function _run_task_by_id( $id ) {
		self::instance()->run_task_by_id( $id );
	}

	/**
	 * Run a task by its ID.
	 * 
	 * @param string $id The ID of the task to run.
	 * 
	 * @return void
	 */
	public function run_task_by_id( $id ) {

		$all = $this->get_raw_tasks();

		$task = $all[ $id ] ?? null;

		// If the task does not exist or is inactive, do nothing
		if ( ! $task || ! $task['active'] ) {
			return;
		}

		$results = [];

		// Run automation task based on its type
		if ( $task['type'] === 'general_cleanup' ) {

			foreach ( $task['operations'] as $items_type => $keep_last_config ) {

				if ( in_array( $keep_last_config, [ 'no_keep_last', 'default' ] ) ) {
					$affected = ADBC_General_Cleanup::run_cleanup( $items_type, $keep_last_config );
				} elseif ( is_array( $keep_last_config ) ) { // 'override' with given custom keep_last config value
					$affected = ADBC_General_Cleanup::run_cleanup( $items_type, 'custom_keep_last', $keep_last_config );
				} else {
					$affected = 0;
				}

				$results[ $items_type ] = $affected;

			}

		} elseif ( $task['type'] === 'custom_cleanup' ) {
			// custom_cleanup types can be added later
		}

		// If it was a one-off task, unschedule it now
		if ( $task['frequency'] === 'adbc_once' ) {
			$task['active'] = false; // Mark as inactive to prevent future runs
			$this->unschedule( $id );
		}

		// Update the last run time
		$task['last_run'] = time();
		$all[ $id ] = $task;
		$this->save( $all );

		// LOG the run timestamp and results
		if ( ADBC_VERSION_TYPE === "PREMIUM" )
			ADBC_Automation_Events_Log::log_event( $id, [ $task['last_run'] => $results ] );

	}

	/**
	 * Get the IDs of all scheduled automation tasks from the wordpress cron array.
	 * 
	 * @return string[] Array of task IDs that are scheduled.
	 */
	public static function get_scheduled_automation_crons_ids() {

		$crons = ADBC_Cron_Jobs::get_cron_array();
		$scheduled_tasks = [];

		foreach ( $crons as $timestamp => $cron ) {
			foreach ( $cron as $hook => $events ) {
				if ( $hook === 'adbc_cron_automation' ) {
					foreach ( $events as $event ) {
						if ( isset( $event['args'][0] ) ) {
							$scheduled_tasks[] = $event['args'][0];
						}
					}
				}
			}
		}

		return $scheduled_tasks;

	}

	/**
	 * Get the events file path for a specific task ID.
	 * 
	 * @param string $task_id The ID of the task.
	 * 
	 * @return string The path to the events file for the task.
	 */
	public static function get_events_file( $task_id ) {
		return ADBC_Automation::AUTOMATION_EVENT_DIR . "/{$task_id}.log";
	}
	/**
	 * Delete the events file for a specific task ID.
	 * 
	 * @param string $task_id The ID of the task whose events file should be deleted.
	 * 
	 * @return bool True if the events file was deleted successfully, false otherwise.
	 */
	public static function delete_events_file( $task_id ) {

		$task_events_file = self::get_events_file( $task_id );

		if ( ! ADBC_Files::instance()->exists( $task_events_file ) ) {
			return true;
		}

		return @unlink( $task_events_file );

	}


	/**
	 * Check if there is any automation task that handles the given items type.
	 * 
	 * @param string $items_type The type of items to check for automation.
	 * 
	 * @return bool True if there is an automation task for the given items type, false otherwise.
	 */
	public static function has_automation( $items_type ) {

		$tasks = self::instance()->get_raw_tasks();

		foreach ( $tasks as $task ) {
			if ( $task['type'] === 'general_cleanup' && isset( $task['operations'][ $items_type ] ) ) {
				return true; // Found a task with automation for the given items type
			}
		}

		return false; // No automation found for the given items type

	}

	/**
	 * Deactivate all automation tasks.
	 * 
	 * @return void
	 */
	public function deactivate_all_tasks() {

		$all = $this->get_raw_tasks();

		foreach ( $all as $id => $task ) {
			$all[ $id ]['active'] = false;
		}

		$this->save( $all );

		wp_unschedule_hook( 'adbc_cron_automation' );

	}

	/**
	 * Compute a user-facing next_run.
	 *
	 * Rules:
	 * - If inactive → ''
	 * - If WP-cron has a timestamp: future → use it; past/now → show now (running now)
	 * - If no WP-cron timestamp (e.g., just reactivated):
	 *   - adbc_once → max(start_datetime, now)
	 *   - recurring → start_datetime if in the future, otherwise now (running now)
	 *
	 * @param array $task
	 * @param int|string $scheduled Timestamp returned by wp_next_scheduled() or ''
	 * @return int|string Next run unix timestamp or '' when none
	 */
	private function compute_virtual_next_run( $task, $scheduled ) {

		if ( empty( $task['active'] ) ) {
			return '';
		}

		$now = time();

		// Prefer WP-cron timestamp when present; coerce past to "now" to reflect immediate run
		if ( is_int( $scheduled ) ) {
			return ( $scheduled > $now ) ? $scheduled : $now;
		}

		$frequency = $task['frequency'] ?? '';
		$start = (int) ( $task['start_datetime'] ?? 0 );

		// One-off: if start passed, it will run now after reactivation
		if ( $frequency === 'adbc_once' ) {
			return max( $start, $now );
		}

		// Recurring: show start if in future, else now to indicate immediate run
		return ( $start > $now ) ? $start : $now;

	}

}
