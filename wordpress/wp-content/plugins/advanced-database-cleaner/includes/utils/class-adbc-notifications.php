<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Notifications class.
 *
 * Manages all types of plugin notifications, including warnings.
 */
class ADBC_Notifications extends ADBC_Singleton {

	private const WP_MIN_VERSION = '5.0';
	private const PHP_MIN_VERSION = '7.0';

	// Initializes the notifications meta array with the default keys.
	private static $notifications_meta = [ 

		// The messages are empty because they will be initialized in the constructor, because we can't use the translation functions in this context.
		"php_is_old" => [ 
			"type" => "warning",
			"message" => "",
			"is_critical" => true,
			"dismissible" => false,
			"save" => false,
			"global" => false,
		],
		"wp_is_old" => [ 
			"type" => "warning",
			"message" => "",
			"is_critical" => true,
			"dismissible" => false,
			"save" => false,
			"global" => false,
		],
		"uploads_folder" => [ 
			"type" => "warning",
			"message" => "",
			"is_critical" => false,
			"dismissible" => false,
			"save" => true,
			"global" => false,
		],
		"wp_file_system" => [ 
			"type" => "warning",
			"message" => "",
			"is_critical" => false,
			"dismissible" => false,
			"save" => true,
			"global" => false,
		],
		"rating_notice" => [ 
			"type" => "rating",
			"message" => "",
			"is_critical" => false,
			"dismissible" => true,
			"save" => true,
			"global" => true,
			"condition" => [ 'ADBC_Notifications', 'should_show_rating_notice' ]
		],
		"addons_activity_info" => [ 
			"type" => "info",
			"message" => "",
			"is_critical" => false,
			"dismissible" => true,
			"save" => true,
			"global" => false,
			"condition" => [ 'ADBC_Notifications', 'always_return_true' ] // Always true, so it can be added automatically later.
		],
		"migration_available" => [ 
			"type" => "info",
			"message" => "",
			"is_critical" => false,
			"dismissible" => true,
			"save" => true,
			"global" => false,
			"condition" => [ 'ADBC_Notifications', 'old_data_import_available' ]
		],
		"imported_tasks_deactivated_notice" => [ 
			"type" => "info",
			"message" => "",
			"is_critical" => false,
			"dismissible" => true,
			"save" => true,
			"global" => false,
		],
		"pro_remote_scan_upsell" => [ 
			"type" => "info",
			"message" => "",
			"is_critical" => false,
			"dismissible" => true,
			"save" => true,
			"global" => false,
			"condition" => [ 'ADBC_Notifications', 'should_show_pro_remote_scan_upsell' ]
		],
		"ltd_migration_notice" => [ 
			"type" => "info",
			"message" => "",
			"is_critical" => false,
			"dismissible" => true,
			"save" => true,
			"global" => true,
			"condition" => [ 'ADBC_Notifications', 'should_show_ltd_migration_notice' ]
		],
		"woptimize_notice" => [ 
			"type" => "info",
			"message" => "",
			"is_critical" => false,
			"dismissible" => true,
			"save" => true,
			"global" => false,
			"condition" => [ 'ADBC_Notifications', 'should_show_woptimize_notice' ]
		],
		"woocommerce_not_detected" => [ 
			"type" => "info",
			"message" => "",
			"is_critical" => false,
			"dismissible" => false,
			"save" => false,
			"global" => false,
		],

	];

	private $notifications = [];

	/**
	 * Constructor.
	 */
	protected function __construct() {

		parent::__construct();

		// Initialize the notifications meta array with the messages.
		$this->initialize_messages();

		// Load the notifications from the settings.
		$notifications = ADBC_Settings::instance()->get_setting( 'notifications' );

		// Add the notifications fetched from the settings to the notifications array with their dismissed state.
		foreach ( $notifications as $key => $dismissed ) {
			$this->notifications[ $key ] = array_merge( self::$notifications_meta[ $key ], $dismissed );
		}

		// Check and add compatibility warnings to the notifications array.
		$this->check_and_add_compatibility_warnings();

		// Conditionally add or remove notifications based on specific conditions.
		$this->conditionally_add_remove_notifications();

	}

	/**
	 * Get all non dismissed notifications.
	 *
	 * @return array Notifications array.
	 */
	public function get_all_non_dismissed() {
		return array_filter( $this->notifications, function ($n) {
			return ! $n['dismissed'];
		} );
	}

	/**
	 * Get all warnings.
	 *
	 * @return array Warnings array.
	 */
	public function get_warnings() {
		return array_filter( $this->get_all_non_dismissed(), function ($n) {
			return $n['type'] === 'warning';
		} );
	}

	/**
	 * Get all local notifications (non-global).
	 *
	 * @return array Local notifications array.
	 */
	public function get_local_notifications() {
		return array_filter( $this->get_all_non_dismissed(), function ($n) {
			return ! $n['global'] && $n['type'] !== 'warning';
		} );
	}

	/**
	 * Get all global notifications (non-warning).
	 *
	 * @return array Global notifications array.
	 */
	public function get_global_notifications() {
		return array_filter( $this->get_all_non_dismissed(), function ($n) {
			return $n['global'] && $n['type'] !== 'warning';
		} );
	}

	/**
	 * Add a notification.
	 * 
	 * @param string $key Notification key.
	 *
	 * @return bool True if the notification was added, false otherwise.
	 */
	public function add_notification( $key ) {

		// If the notification already exists, return true.
		if ( isset( $this->notifications[ $key ] ) ) {
			return true;
		}

		// Check if the notification key is valid.
		if ( ! isset( self::$notifications_meta[ $key ] ) ) {
			return false;
		}

		// If the notification is not already set, add it with the default values.
		$this->notifications[ $key ] = self::$notifications_meta[ $key ];
		$this->notifications[ $key ]['dismissed'] = false;

		return $this->update();

	}

	/**
	 * Dismiss a notification.
	 *
	 * @param string $key Notification key.
	 * 
	 * @return bool True if the notification was dismissed, false otherwise.
	 */
	public function dismiss_notification( $key ) {

		// If the notification exist and is dismissible, update its dismissed state.
		if ( isset( $this->notifications[ $key ] ) && $this->notifications[ $key ]['dismissible'] ) {
			$this->notifications[ $key ]['dismissed'] = true;
			return $this->update();
		}

		// If the notification doesn't exist or is not dismissible, return false.
		return false;

	}

	/**
	 * Delete a notification.
	 *
	 * @param string $key Notification key.
	 * 
	 * @return bool True if the notification was deleted, false otherwise.
	 */
	public function delete_notification( $key ) {

		// if it doesn't exist, return true.
		if ( ! isset( $this->notifications[ $key ] ) ) {
			return true;
		}
		// Check if the notification key is valid.
		if ( ! isset( self::$notifications_meta[ $key ] ) ) {
			return false;
		}

		// If the notification exists, unset it and update the database.
		unset( $this->notifications[ $key ] );

		return $this->update();

	}

	/**
	 * Update the notifications in the database.
	 *
	 * @return bool True if the update was successful, false otherwise.
	 */
	private function update() {

		$notifications_to_save = [];

		// Save only the notifications that need to be saved in the database.
		foreach ( $this->notifications as $key => $notification ) {
			if ( $notification['save'] ) {
				$notifications_to_save[ $key ] = [ 
					'dismissed' => $notification['dismissed']
				];
			}
		}

		return ADBC_Settings::instance()->update_settings( [ 'notifications' => $notifications_to_save ] );

	}

	/**
	 * Get all notification keys.
	 *
	 * @return array Array of notification keys.
	 */
	public static function get_notifications_keys() {
		return array_keys( self::$notifications_meta );
	}

	/**
	 * Check and add compatibility warnings to the notifications array.
	 * 
	 * @return void
	 */
	private function check_and_add_compatibility_warnings() {

		// Check PHP version.
		if ( version_compare( PHP_VERSION, self::PHP_MIN_VERSION, '<' ) )
			$this->add_notification( 'php_is_old' );

		// Check WordPress version.
		if ( version_compare( get_bloginfo( 'version' ), self::WP_MIN_VERSION, '<' ) )
			$this->add_notification( 'wp_is_old' );

		// Check if WooCommerce is installed and activated.
		if ( ! class_exists( 'WooCommerce' ) )
			$this->add_notification( 'woocommerce_not_detected' );

	}

	/**
	 * Conditionally add notifications based on specific conditions.
	 *
	 * @return void
	 */
	private function conditionally_add_remove_notifications() {

		foreach ( self::$notifications_meta as $key => $meta ) {

			// If the condition is empty, or the condition is not callable, or the notification has been dismissed, skip it.
			if ( empty( $meta['condition'] ) || $this->is_notification_dismissed( $key ) || ! is_callable( $meta['condition'] ) ) {
				continue;
			}

			// Only add it if the condition passes, otherwise, if it exists, remove it.
			if ( call_user_func( $meta['condition'] ) ) {
				$this->notifications[ $key ] = $meta;
				$this->notifications[ $key ]['dismissed'] = false;
			} else if ( isset( $this->notifications[ $key ] ) ) {
				unset( $this->notifications[ $key ] );
			}

		}

		$this->update();

	}

	/**
	 * Initialize the messages for the notifications.
	 * 
	 * @return void
	 */
	private function initialize_messages() {

		// Add message for notifications with type 'warning' since they are printed directly on the screen. For other types, we hardcode them in frontend.

		self::$notifications_meta["php_is_old"]["message"] =
			sprintf(
				/* translators: 1: PHP version */
				__( "Please upgrade your PHP to %s or higher to use the plugin without issues.", "advanced-database-cleaner" ), self::PHP_MIN_VERSION );

		self::$notifications_meta["wp_is_old"]["message"] =
			sprintf(
				/* translators: 1: WordPress version */
				__( "Please upgrade WordPress to %s or higher to use the plugin without issues.", "advanced-database-cleaner" ), self::WP_MIN_VERSION );

		self::$notifications_meta["uploads_folder"]["message"] =
			__( "Uploads folder is not writable. Check permissions.", "advanced-database-cleaner" );

		self::$notifications_meta["wp_file_system"]["message"] =
			__( "WordPress file system API is not initialized.", "advanced-database-cleaner" );

	}

	/**
	 * Check if the user should see the rating notice.
	 *
	 * @return bool True if the user should see the rating notice, false otherwise.
	 */
	private static function should_show_rating_notice() {

		$rating_notice_date = ADBC_Settings::instance()->get_setting( 'rating_notice_date' );

		// No need to check if the date is valid, because the setting validator ensures it.
		$timestamp = DateTime::createFromFormat( 'd/m/Y', $rating_notice_date );

		return ( time() - $timestamp->getTimestamp() ) >= 7 * DAY_IN_SECONDS;

	}

	/**
	 * Always return true, used for conditions that should always pass.
	 *
	 * @return bool Always true.
	 */
	private static function always_return_true() {
		return true;
	}

	/**
	 * Whether to show the Pro remote scan upsell in the scan modal.
	 * Only for Pro version (lifetime plan) users; dismissed when they redeem/sync credits.
	 *
	 * @return bool True if ADBC_IS_PRO_VERSION.
	 */
	private static function should_show_pro_remote_scan_upsell() {
		return defined( 'ADBC_IS_PRO_VERSION' ) && ADBC_IS_PRO_VERSION === true;
	}

	/**
	 * Whether to show the LTD migration (V4 upgrade) global notice.
	 * Shows when reminder date is not set or 7+ days have passed since "Remind me in a week".
	 *
	 * @return bool True if the notice should show.
	 */
	private static function should_show_ltd_migration_notice() {

		// Return false if we are not in pro version, because the notice is only for pro users.
		if ( ! ADBC_IS_PRO_VERSION )
			return false;

		$reminder_date = ADBC_Settings::instance()->get_setting( 'ltd_migration_reminder_date' );

		$timestamp = DateTime::createFromFormat( 'd/m/Y', $reminder_date );

		return ( time() - $timestamp->getTimestamp() ) >= 7 * DAY_IN_SECONDS;
	}

	/**
	 * Delay the LTD migration notice by 7 days (remind me in a week).
	 *
	 * @return bool True on success.
	 */
	public function delay_ltd_migration_notice() {
		// Set the LTD migration reminder date to today.
		ADBC_Settings::instance()->update_settings( [ 'ltd_migration_reminder_date' => date( 'd/m/Y' ) ] );
		return $this->delete_notification( 'ltd_migration_notice' );
	}

	/**
	 * Whether to show the WOptimize promo notice.
	 * Shows only when WOptimize is not installed yet.
	 *
	 * @return bool True if the notice should show.
	 */
	private static function should_show_woptimize_notice() {

		// Don't promote WOptimize if it is already installed.
		if ( ADBC_Admin_Init::is_woptimize_installed() )
			return false;

		return true;

	}

	/**
	 * Delay the rating notice by 7 days.
	 *
	 * @return bool True if the notice was delayed, false otherwise.
	 */
	public function delay_rating_notice() {
		// Set the rating notice date to today.
		ADBC_Settings::instance()->update_settings( [ 'rating_notice_date' => date( 'd/m/Y' ) ] );
		return $this->delete_notification( 'rating_notice' );
	}

	/**
	 * Check if there is a need to migrate from an old version (before 4.0.0).
	 *
	 * @return bool True if there is a need to migrate, false otherwise.
	 */
	public static function old_data_import_available() {

		// If we are in free or pro, return false.
		if ( ADBC_VERSION_TYPE === 'FREE' || ADBC_IS_PRO_VERSION === true )
			return false;

		// If the free migration is not done, check all available migration data.
		if ( ADBC_Settings::instance()->get_setting( 'free_migration_done' ) === '0' )
			return count( ADBC_Migration::get_available_migration_data() ) > 0;
		else  // If the free migration is done, check only the available migration data for the pro version.
			return count( ADBC_Migration::get_available_migration_data( 'pro' ) ) > 0;

	}

	/**
	 * Check if a notification is dismissed.
	 * 
	 * @param string $key Notification key.
	 * 
	 * @return bool True if the notification is dismissed, false otherwise.
	 */
	public function is_notification_dismissed( $key ) {
		return isset( $this->notifications[ $key ] ) && isset( $this->notifications[ $key ]['dismissed'] ) && $this->notifications[ $key ]['dismissed'] === true;
	}

}