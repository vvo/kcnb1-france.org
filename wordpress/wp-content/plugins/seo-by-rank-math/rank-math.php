<?php // @codingStandardsIgnoreLine
/**
 * Rank Math SEO Plugin.
 *
 * @package      RANK_MATH
 * @copyright    Copyright (C) 2019, Rank Math - support@rankmath.com
 * @link         https://rankmath.com
 * @since        0.9.0
 *
 * @wordpress-plugin
 * Plugin Name:       Rank Math SEO
 * Version:           1.0.23.1
 * Plugin URI:        https://s.rankmath.com/home
 * Description:       Rank Math is a revolutionary SEO product that combines the features of many SEO tools and lets you multiply your traffic in the easiest way possible.
 * Author:            Rank Math
 * Author URI:        https://s.rankmath.com/home
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rank-math
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

/**
 * RankMath class.
 *
 * @class The class that holds the entire plugin.
 */
final class RankMath {

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	public $version = '1.0.23.1';

	/**
	 * Rank Math database version.
	 *
	 * @var string
	 */
	public $db_version = '1';

	/**
	 * Minimum version of WordPress required to run the plugin
	 *
	 * @var string
	 */
	private $wordpress_version = '4.6';

	/**
	 * Minimum version of PHP required to run the plugin
	 *
	 * @var string
	 */
	private $php_version = '5.6';

	/**
	 * Holds various class instances
	 *
	 * @var array
	 */
	private $container = [];

	/**
	 * Hold messages
	 *
	 * @var bool
	 */
	private $messages = [];

	/**
	 * The single instance of the class
	 *
	 * @var RankMath
	 */
	protected static $instance = null;

	/**
	 * Magic isset to bypass referencing plugin
	 *
	 * @param  string $prop Property to check.
	 * @return bool
	 */
	public function __isset( $prop ) {
		return isset( $this->{$prop} ) || isset( $this->container[ $prop ] );
	}

	/**
	 * Magic get method
	 *
	 * @param  string $prop Property to get.
	 * @return mixed Property value or NULL if it does not exists
	 */
	public function __get( $prop ) {
		if ( array_key_exists( $prop, $this->container ) ) {
			return $this->container[ $prop ];
		}

		return $this->{$prop};
	}

	/**
	 * Magic set method
	 *
	 * @param mixed $prop  Property to set.
	 * @param mixed $value Value to set.
	 */
	public function __set( $prop, $value ) {
		if ( property_exists( $this, $prop ) ) {
			$this->$prop = $value;
			return;
		}

		$this->container[ $prop ] = $value;
	}

	/**
	 * Magic call method.
	 *
	 * @param  string $name      Method to call.
	 * @param  array  $arguments Arguments to pass when calling.
	 * @return mixed Return value of the callback.
	 */
	public function __call( $name, $arguments ) {
		$hash = [
			'plugin_dir'   => RANK_MATH_PATH,
			'plugin_url'   => RANK_MATH_URL,
			'includes_dir' => RANK_MATH_PATH . 'includes/',
			'assets'       => RANK_MATH_URL . 'assets/front/',
			'admin_dir'    => RANK_MATH_PATH . 'includes/admin/',
		];

		if ( isset( $hash[ $name ] ) ) {
			return $hash[ $name ];
		}

		return call_user_func_array( $name, $arguments );
	}

	/**
	 * Main RankMath instance
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @see rank_math()
	 * @return RankMath
	 */
	public static function get() {
		if ( is_null( self::$instance ) && ! ( self::$instance instanceof RankMath ) ) {
			self::$instance = new RankMath();
			self::$instance->setup();
		}

		return self::$instance;
	}

	/**
	 * Instantiate the plugin
	 */
	private function setup() {
		if ( ! $this->is_requirements_meet() ) {
			return;
		}

		// Define constants.
		$this->define_constants();

		// Include required files.
		$this->includes();

		// instantiate classes.
		$this->instantiate();

		// Initialize the action hooks.
		$this->init_actions();

		// Loaded action.
		do_action( 'rank_math/loaded' );
	}

	/**
	 * Check that the WordPress and PHP setup meets the plugin requirements
	 *
	 * @return bool
	 */
	private function is_requirements_meet() {

		// Check if WordPress version is enough to run this plugin.
		if ( version_compare( get_bloginfo( 'version' ), $this->wordpress_version, '<' ) ) {
			/* translators: WordPress Version */
			$this->messages[] = sprintf( esc_html__( 'Rank Math requires WordPress version %s or above. Please update WordPress to run this plugin.', 'rank-math' ), $this->wordpress_version );
		}

		// Check if PHP version is enough to run this plugin.
		if ( version_compare( phpversion(), $this->php_version, '<' ) ) {
			/* translators: PHP Version */
			$this->messages[] = sprintf( esc_html__( 'Rank Math requires PHP version %s or above. Please update PHP to run this plugin.', 'rank-math' ), $this->php_version );
		}

		if ( empty( $this->messages ) ) {
			return true;
		}

		// Auto-deactivate plugin.
		add_action( 'admin_init', [ $this, 'auto_deactivate' ] );
		add_action( 'admin_notices', [ $this, 'activation_error' ] );

		return false;
	}

	/**
	 * Auto-deactivate plugin if requirement not meet and display a notice
	 */
	public function auto_deactivate() {
		deactivate_plugins( plugin_basename( RANK_MATH_FILE ) );
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}

	/**
	 * Plugin activation notice
	 */
	public function activation_error() {
		?>
		<div class="notice notice-error">
			<p>
				<?php echo join( '<br>', $this->messages ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Define the plugin constants
	 */
	private function define_constants() {
		define( 'RANK_MATH_VERSION', $this->version );
		define( 'RANK_MATH_FILE', __FILE__ );
		define( 'RANK_MATH_PATH', dirname( RANK_MATH_FILE ) . '/' );
		define( 'RANK_MATH_URL', plugins_url( '', RANK_MATH_FILE ) . '/' );
	}

	/**
	 * Include the required files
	 */
	private function includes() {
		include dirname( __FILE__ ) . '/vendor/autoload.php';

		// For Theme Developers.
		$file = get_stylesheet_directory() . '/rank-math.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}

	/**
	 * Instantiate classes
	 */
	private function instantiate() {
		new \RankMath\Rollbar;
		new \RankMath\Installer;

		// Setting Manager.
		$this->container['settings'] = new \RankMath\Settings;

		// JSON Manager.
		$this->container['json'] = new \MyThemeShop\Json_Manager;

		// Notification Manager.
		$this->container['notification'] = new \MyThemeShop\Notification_Center( 'rank_math_notifications' );

		// Product Registration.
		$this->container['registration'] = new \RankMath\Admin\Registration;
		if ( $this->container['registration']->invalid ) {
			return;
		}

		$this->container['manager'] = new \RankMath\Module_Manager;

		// Just Init.
		new \RankMath\Common;
		$this->container['rewrite'] = new \RankMath\Rewrite;
		new \RankMath\Compatibility;

		// Usage Tracking.
		if ( defined( 'DOING_CRON' ) && ! defined( 'DOING_AJAX' ) && \RankMath\Helper::get_settings( 'general.usage_tracking' ) ) {
			new \RankMath\Tracking;
		}
	}

	/**
	 * Initialize WordPress action hooks
	 */
	private function init_actions() {

		add_action( 'init', [ $this, 'localization_setup' ] );
		add_filter( 'cron_schedules', [ $this, 'cron_schedules' ] );

		// Add plugin action links.
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( RANK_MATH_FILE ), [ $this, 'plugin_action_links' ] );

		// Booting.
		add_action( 'rest_api_init', [ $this, 'init_rest_api' ] );

		if ( is_admin() ) {
			add_action( 'plugins_loaded', [ $this, 'init_admin' ], 15 );
		}

		// Frontend Only.
		if ( ! is_admin() ) {
			add_action( 'plugins_loaded', [ $this, 'init_frontend' ], 15 );
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			add_action( 'plugins_loaded', [ $this, 'init_wp_cli' ], 20 );
		}
	}

	/**
	 * Loads the rest api endpoints.
	 */
	public function init_rest_api() {
		// We can't do anything when requirements are not met.
		if ( ! \RankMath\Helper::is_api_available() ) {
			return;
		}

		$controllers = [
			new \RankMath\Rest\Admin,
		];

		foreach ( $controllers as $controller ) {
			$controller->register_routes();
		}
	}

	/**
	 * Initialize the admin.
	 */
	public function init_admin() {
		new \RankMath\Admin\Engine;
	}

	/**
	 * Initialize the frontend.
	 */
	public function init_frontend() {
		$this->container['frontend'] = new \RankMath\Frontend\Frontend;
	}

	/**
	 * Initialize the WP-CLI integration.
	 */
	public function init_wp_cli() {
		WP_CLI::add_command( 'rankmath sitemap generate', [ '\RankMath\CLI\Commands', 'sitemap_generate' ] );
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param  mixed $links Plugin Action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {

		$plugin_links = [
			'<a href="' . RankMath\Helper::get_admin_url( 'options-general' ) . '">' . esc_html__( 'Settings', 'rank-math' ) . '</a>',
			'<a href="' . RankMath\Helper::get_admin_url( 'wizard' ) . '">' . esc_html__( 'Setup Wizard', 'rank-math' ) . '</a>',
		];

		return array_merge( $links, $plugin_links );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param  mixed $links Plugin Row Meta.
	 * @param  mixed $file  Plugin Base file.
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {

		if ( plugin_basename( RANK_MATH_FILE ) !== $file ) {
			return $links;
		}

		$more = [
			'<a href="' . RankMath\Helper::get_admin_url( 'help' ) . '">' . esc_html__( 'Getting Started', 'rank-math' ) . '</a>',
			'<a href="https://s.rankmath.com/documentation">' . esc_html__( 'Documentation', 'rank-math' ) . '</a>',
		];

		return array_merge( $links, $more );
	}

	/**
	 * Initialize plugin for localization.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *     - WP_LANG_DIR/rank-math/rank-math-LOCALE.mo
	 *     - WP_LANG_DIR/plugins/rank-math-LOCALE.mo
	 */
	public function localization_setup() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'rank-math' );

		unload_textdomain( 'rank-math' );
		if ( false === load_textdomain( 'rank-math', WP_LANG_DIR . '/plugins/seo-by-rank-math-' . $locale . '.mo' ) ) {
			load_textdomain( 'rank-math', WP_LANG_DIR . '/seo-by-rank-math/seo-by-rank-math-' . $locale . '.mo' );
		}
		load_plugin_textdomain( 'rank-math', false, rank_math()->plugin_dir() . '/languages/' );

		$this->container['json']->add( 'version', $this->version, 'rankMath' );
		$this->container['json']->add( 'ajaxurl', admin_url( 'admin-ajax.php' ), 'rankMath' );
		$this->container['json']->add( 'adminurl', admin_url( 'admin.php' ), 'rankMath' );
		$this->container['json']->add( 'security', wp_create_nonce( 'rank-math-ajax-nonce' ), 'rankMath' );
	}

	/**
	 * Add more cron schedules.
	 *
	 * @param  array $schedules List of WP scheduled cron jobs.
	 * @return array
	 */
	public function cron_schedules( $schedules ) {

		$schedules['weekly'] = array(
			'interval' => DAY_IN_SECONDS * 7,
			'display'  => esc_html__( 'Once Weekly', 'rank-math' ),
		);

		return $schedules;
	}
}

/**
 * Main instance of RankMath.
 *
 * Returns the main instance of RankMath to prevent the need to use globals.
 *
 * @return RankMath
 */
function rank_math() {
	return RankMath::get();
}

// Kick it off.
rank_math();
