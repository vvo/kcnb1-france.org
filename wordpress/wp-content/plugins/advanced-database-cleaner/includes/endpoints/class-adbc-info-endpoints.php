<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Info Endpoints.
 * 
 * This class provides the endpoints (controllers) for the info routes.
 */
class ADBC_Info_Endpoints {

	/**
	 * Get the system info.
	 * 
	 * @return WP_REST_Response The system info.
	 */
	public static function get_system_information() {

		try {

			global $wpdb;
			$settings = ADBC_Settings::instance()->get_settings();

			// Get locale info.
			$locale = get_locale();

			// Get theme info
			$theme_data = wp_get_theme();
			$theme = $theme_data->get( 'Name' ) . ' ' . $theme_data->get( 'Version' );
			$parent_theme = "N/A";
			if ( $theme_data->parent() ) {
				$parent_theme_data = $theme_data->parent();
				$parent_theme = $parent_theme_data->get( 'Name' ) . ' ' . $parent_theme_data->get( 'Version' );
			}

			$adbc_plugin_type = ADBC_VERSION_TYPE;
			$adbc_plugin_type = ADBC_IS_PRO_VERSION ? 'PRO' : $adbc_plugin_type;

			$data = array(
				'wordpress' => array(
					'title' => __( 'WordPress', 'advanced-database-cleaner' ),
					'generated_on' => date( 'Y-m-d H:i:s' ),
					'data' => array(
						__( 'Version', 'advanced-database-cleaner' ) . ": " . get_bloginfo( 'version' ),
						__( 'Site URL', 'advanced-database-cleaner' ) . ": " . site_url(),
						__( 'Home URL', 'advanced-database-cleaner' ) . ": " . home_url(),
						__( 'Multisite', 'advanced-database-cleaner' ) . ": " . ( is_multisite() ? __( 'Yes', 'advanced-database-cleaner' ) : __( 'No', 'advanced-database-cleaner' ) ),
						__( 'Sites', 'advanced-database-cleaner' ) . ": " . ADBC_Sites::instance()->count_sites_in_multisite(),
						__( 'Language', 'advanced-database-cleaner' ) . ": " . ( ! empty( $locale ) ? $locale : 'en_US' ),
						__( 'Theme', 'advanced-database-cleaner' ) . ": " . $theme,
						__( 'Parent theme', 'advanced-database-cleaner' ) . ": " . $parent_theme,
						__( 'Memory limit', 'advanced-database-cleaner' ) . ": " . ( defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : 'N/A' ),
						__( 'Autoload warning threshold', 'advanced-database-cleaner' ) . ": " . ADBC_Common_Utils::format_bytes( ADBC_Options::get_autoload_warning_limit() )
					)
				),
				'database' => array(
					'title' => __( 'Database', 'advanced-database-cleaner' ),
					'data' => array(
						__( 'Database size', 'advanced-database-cleaner' ) . ": " . ADBC_Database::get_database_size_sql(),
						__( 'Total tables', 'advanced-database-cleaner' ) . ": " . ADBC_Tables::get_total_tables_count() . " (" . ADBC_Tables::get_total_tables_with_invalid_prefix_count() . " " . __( 'with invalid prefix', 'advanced-database-cleaner' ) . ")",
						__( 'Total options', 'advanced-database-cleaner' ) . ": " . ADBC_Options::get_total_options_count(),
						__( 'Total cron jobs', 'advanced-database-cleaner' ) . ": " . ADBC_Cron_Jobs::get_total_cron_jobs_count(),
						__( 'Total transients', 'advanced-database-cleaner' ) . ": " . ADBC_Transients::get_total_transients_count(),
						__( 'Total user meta', 'advanced-database-cleaner' ) . ": " . ADBC_Users_Meta::get_total_users_meta_count(),
						__( 'Total post meta', 'advanced-database-cleaner' ) . ": " . ADBC_Posts_Meta::get_total_posts_meta_count(),
						__( 'Total post types', 'advanced-database-cleaner' ) . ": " . ADBC_Post_Types::get_total_post_types_count(),
					)
				),
				'adbc' => array(
					'title' => 'Advanced DB Cleaner',
					'data' => array(
						__( 'Version', 'advanced-database-cleaner' ) . ": " . ADBC_PLUGIN_VERSION . ' - ' . $adbc_plugin_type,
						__( 'Installed on', 'advanced-database-cleaner' ) . ": " . ADBC_Common_Utils::format_date_friendly( $settings['installed_on'] ),
						__( 'License status', 'advanced-database-cleaner' ) . ": " . ( ADBC_VERSION_TYPE === 'PREMIUM' ? ADBC_License_Manager::get_license_status() : 'adbc_hidden' ),
						__( 'License plan', 'advanced-database-cleaner' ) . ": " . ( ADBC_VERSION_TYPE === 'PREMIUM' ? ADBC_License_Manager::get_plan_name() : 'adbc_hidden' ),
						__( 'License expiry/renewal', 'advanced-database-cleaner' ) . ": " . ( ADBC_VERSION_TYPE === 'PREMIUM' ? ADBC_License_Manager::get_license_expiration() : 'adbc_hidden' ),
					)
				),
				'adbc_settings' => array(
					'title' => __( 'Settings', 'advanced-database-cleaner' ),
					'data' => array(
						__( 'Cleanup method', 'advanced-database-cleaner' ) . ": " . $settings['sql_or_native_cleanup_method'],
						__( 'Database rows batches', 'advanced-database-cleaner' ) . ": " . $settings['database_rows_batch'],
						__( 'Reduce CPU usage', 'advanced-database-cleaner' ) . ": " . ( $settings['reduce_cpu_usage'] === '1' ? __( 'Yes', 'advanced-database-cleaner' ) : __( 'No', 'advanced-database-cleaner' ) ),
						__( 'CPU work time', 'advanced-database-cleaner' ) . ": " . $settings['cpu_work_time_ms'],
						__( 'CPU rest time', 'advanced-database-cleaner' ) . ": " . $settings['cpu_rest_time_ms'],
						__( 'File line batches', 'advanced-database-cleaner' ) . ": " . $settings['file_lines_batch'],
						__( 'File content chunks', 'advanced-database-cleaner' ) . ": " . $settings['file_content_chunks'],
						__( 'Scan max execution time', 'advanced-database-cleaner' ) . ": " . $settings['scan_max_execution_time'],
						__( 'Analytics enabled', 'advanced-database-cleaner' ) . ": " . ( $settings['analytics_enabled'] === '1' ? __( 'Yes', 'advanced-database-cleaner' ) : __( 'No', 'advanced-database-cleaner' ) ),
						__( 'Addons activity enabled', 'advanced-database-cleaner' ) . ": " . ( $settings['addons_activity_enabled'] === '1' ? __( 'Yes', 'advanced-database-cleaner' ) : __( 'No', 'advanced-database-cleaner' ) ),
						__( 'Show tables with invalid prefix', 'advanced-database-cleaner' ) . ": " . ( $settings['show_tables_with_invalid_prefix'] === '1' ? __( 'Yes', 'advanced-database-cleaner' ) : __( 'No', 'advanced-database-cleaner' ) ),
						__( 'Last successful analytics execution', 'advanced-database-cleaner' ) . ": " . ADBC_Common_Utils::format_timestamp_friendly( $settings['analytics_execution']['success'] ),
						__( 'Last failed analytics execution', 'advanced-database-cleaner' ) . ": " . ADBC_Common_Utils::format_timestamp_friendly( $settings['analytics_execution']['fail'] ),
						__( 'Last successful addons activity execution', 'advanced-database-cleaner' ) . ": " . ADBC_Common_Utils::format_timestamp_friendly( $settings['addons_activity_execution']['success'] ),
						__( 'Last failed addons activity execution', 'advanced-database-cleaner' ) . ": " . ADBC_Common_Utils::format_timestamp_friendly( $settings['addons_activity_execution']['fail'] ),
					)
				),
				'webserver' => array(
					'title' => __( 'Webserver', 'advanced-database-cleaner' ),
					'data' => array(
						__( 'PHP version', 'advanced-database-cleaner' ) . ": " . phpversion(),
						__( 'MySQL version', 'advanced-database-cleaner' ) . ": " . $wpdb->db_version(),
						__( 'Server', 'advanced-database-cleaner' ) . ": " . ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ) : 'N/A' ),
						"HTTPS: " . ( isset( $_SERVER['HTTPS'] ) ? __( 'Yes', 'advanced-database-cleaner' ) : __( 'No', 'advanced-database-cleaner' ) ),
					)
				),
				'php' => array(
					'title' => __( 'PHP', 'advanced-database-cleaner' ),
					'data' => array(
						__( 'Memory Limit', 'advanced-database-cleaner' ) . ": " . ini_get( 'memory_limit' ),
						__( 'Upload Max Filesize', 'advanced-database-cleaner' ) . ": " . ini_get( 'upload_max_filesize' ),
						__( 'Post Max Size', 'advanced-database-cleaner' ) . ": " . ini_get( 'post_max_size' ),
						__( 'Max execution time', 'advanced-database-cleaner' ) . ": " . ini_get( 'max_execution_time' ),
						"cURL: " . ( function_exists( 'curl_init' ) ? __( 'Supported', 'advanced-database-cleaner' ) : __( 'Not supported', 'advanced-database-cleaner' ) ),
					)
				),
				'dropins' => array(
					'title' => __( 'Drop-ins', 'advanced-database-cleaner' ),
					'data' => ADBC_Plugins::instance()->get_dropins_list()
				),
				'muPlugins' => array(
					'title' => __( 'Must-use plugins', 'advanced-database-cleaner' ),
					'data' => ADBC_Plugins::instance()->get_mu_plugins_list()
				),
				'activePlugins' => array(
					'title' => __( 'Active plugins', 'advanced-database-cleaner' ),
					'data' => ADBC_Plugins::instance()->get_plugins_list( 'active' )
				),
				'inactivePlugins' => array(
					'title' => __( 'Inactive plugins', 'advanced-database-cleaner' ),
					'data' => ADBC_Plugins::instance()->get_plugins_list( 'inactive' )
				)
			);

			// If multisite, add the 'networkActivePlugins' key
			if ( is_multisite() ) {
				$data['networkActivePlugins'] = array(
					'title' => __( 'Network active plugins', 'advanced-database-cleaner' ),
					'data' => ADBC_Plugins::instance()->get_network_active_plugins_list()
				);
			}

			return ADBC_Rest::success( "", $data );

		} catch (Exception $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

}