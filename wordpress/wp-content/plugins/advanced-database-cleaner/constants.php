<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

// Version type: FREE or PREMIUM.
if ( ! defined( 'ADBC_VERSION_TYPE' ) )
	file_exists( __DIR__ . '/includes/premium' ) ? define( 'ADBC_VERSION_TYPE', 'PREMIUM' ) : define( 'ADBC_VERSION_TYPE', 'FREE' );

// Is this version the pro version based on the slug of the plugin folder.
if ( ! defined( 'ADBC_IS_PRO_VERSION' ) )
	basename( __DIR__ ) === 'advanced-database-cleaner-pro' ? define( 'ADBC_IS_PRO_VERSION', true ) : define( 'ADBC_IS_PRO_VERSION', false );

// Plugin folder name.
if ( ! defined( 'ADBC_PLUGIN_DIR_NAME' ) )
	define( 'ADBC_PLUGIN_DIR_NAME', dirname( plugin_basename( __FILE__ ) ) );

// Plugin folder path. Used to include files.
if ( ! defined( 'ADBC_PLUGIN_DIR_PATH' ) )
	define( 'ADBC_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );

// WordPress uploads folder path.
if ( ! defined( 'ADBC_WP_UPLOADS_DIR_PATH' ) )
	define( 'ADBC_WP_UPLOADS_DIR_PATH', wp_upload_dir()['basedir'] );

// ADBC uploads folder prefix.
if ( ! defined( 'ADBC_UPLOADS_DIR_PREFIX' ) )
	define( 'ADBC_UPLOADS_DIR_PREFIX', 'adbc_uploads_F_' );

// ADBC uploads security code length.
if ( ! defined( 'ADBC_SECURITY_CODE_LENGTH' ) )
	define( 'ADBC_SECURITY_CODE_LENGTH', 25 );

// ADBC uploads folder path.
if ( ! defined( "ADBC_UPLOADS_DIR_PATH" ) ) {
	$adbc_security_code = ADBC_Settings::instance()->get_setting( 'security_code' );
	define( "ADBC_UPLOADS_DIR_PATH", ADBC_WP_UPLOADS_DIR_PATH . '/' . ADBC_UPLOADS_DIR_PREFIX . $adbc_security_code );
}

// WordPress debug file path.
if ( ! defined( 'ADBC_WP_DEBUG_LOG_FILE_PATH' ) ) {
	$adbc_debug_log_path = WP_CONTENT_DIR . '/debug.log';
	if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) ) {
		$adbc_debug_log_path = WP_DEBUG_LOG;
	}
	define( 'ADBC_WP_DEBUG_LOG_FILE_PATH', $adbc_debug_log_path );
}

// Plugin URL. Used to enqueue scripts and styles.
if ( ! defined( 'ADBC_PLUGIN_ABSOLUTE_URL' ) )
	define( 'ADBC_PLUGIN_ABSOLUTE_URL', plugins_url( '', __FILE__ ) );

// Current website URL.
if ( ! defined( 'ADBC_WEBSITE_HOME_URL' ) )
	define( 'ADBC_WEBSITE_HOME_URL', home_url() );

// Rest API routes.
if ( ! defined( 'ADBC_REST_API_NAMESPACE' ) )
	define( 'ADBC_REST_API_NAMESPACE', "advanced-db-cleaner/v1" );

// ADBC API remote URL.
if ( ! defined( 'ADBC_API_REMOTE_URL' ) )
	define( 'ADBC_API_REMOTE_URL', "https://api.sigmaplugin.com/v1" );
