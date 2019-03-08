<?php
/**
 * The Setup Wizard - configure the SEO settings in a few steps.
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Admin;

use RankMath\CMB2;
use RankMath\Traits\Ajax;
use RankMath\Traits\Hooker;
use RankMath\Traits\Wizard;
use RankMath\Helper as GlobalHelper;
use RankMath\Admin\Importers\Detector;
use RankMath\KB;
use MyThemeShop\Helpers\WordPress;

defined( 'ABSPATH' ) || exit;

/**
 * Setup_Wizard class.
 */
class Setup_Wizard {

	use Ajax, Hooker, Wizard;

	/**
	 * Hold steps data.
	 *
	 * @var array
	 */
	protected $steps = array();

	/**
	 * Hold current step.
	 *
	 * @var string
	 */
	protected $step = '';

	/**
	 * Current step slug.
	 *
	 * @var string
	 */
	protected $step_slug = '';

	/**
	 * The text string array.
	 *
	 * @var array
	 */
	protected $strings = null;

	/**
	 * Top level admin page.
	 *
	 * @var string
	 */
	protected $slug = 'rank-math-wizard';

	/**
	 * CMB2 object
	 *
	 * @var \CMB2
	 */
	public $cmb = null;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->strings();
		$this->action( 'cmb2_admin_init', 'steps', 9 );
		$this->action( 'cmb2_admin_init', 'register_cmb2' );
		$this->action( 'admin_menu', 'add_admin_menu' );
		$this->action( 'admin_post_rank_math_save_wizard', 'save_wizard' );

		// If not the page is not this page stop here.
		if ( ! $this->is_current_page() ) {
			return;
		}

		$this->action( 'admin_init', 'admin_page', 30 );
		$this->filter( 'user_has_cap', 'filter_user_has_cap' );
		$this->filter( 'rank_math/wizard/step/label', 'change_label' );
		$this->filter( 'rank_math/wizard/step/label_url', 'change_label_url' );
	}

	/**
	 * Register CMB2 option page for setup wizard.
	 */
	public function register_cmb2() {
		$this->cmb = new_cmb2_box( array(
			'id'           => 'rank-math-wizard',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'rank-math-wizard',
			'hookup'       => false,
			'save_fields'  => false,
			'classes'      => 'wp-core-ui rank-math-ui',
		) );

		isset( $this->steps[ $this->step ], $this->steps[ $this->step ]['form'] ) ? call_user_func( $this->steps[ $this->step ]['form'], $this ) : false;

		CMB2::pre_init( $this->cmb );
	}

	/**
	 * Execute save handler for current step.
	 */
	public function save_wizard() {

		// If no form submission, bail.
		if ( empty( $_POST ) ) {
			return wp_safe_redirect( $_POST['_wp_http_referer'] );
		}

		check_admin_referer( 'rank-math-wizard', 'security' );

		$show_content = true;
		$values       = $this->cmb->get_sanitized_values( $_POST );
		if ( isset( $this->steps[ $this->step ]['handler'] ) ) {
			$show_content = call_user_func( $this->steps[ $this->step ]['handler'], $values, $this );
			GlobalHelper::is_configured( true );
		}

		$redirect = $show_content ? $this->step_next_link() : $_POST['_wp_http_referer'];
		if ( is_string( $show_content ) ) {
			$redirect = $show_content;
		}
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Add the admin menu item, under Appearance.
	 */
	public function add_admin_menu() {
		if ( empty( $_GET['page'] ) || $this->slug !== $_GET['page'] ) {
			return;
		}

		$this->hook_suffix = add_submenu_page(
			null, esc_html( $this->strings['admin-menu'] ), esc_html( $this->strings['admin-menu'] ), 'manage_options', $this->slug, array( $this, 'admin_page' )
		);
	}

	/**
	 * Add the admin page.
	 */
	public function admin_page() {

		// Do not proceed, if we're not on the right page.
		if ( empty( $_GET['page'] ) || $this->slug !== $_GET['page'] ) {
			return;
		}

		if ( ob_get_length() ) {
			ob_end_clean();
		}

		// Enqueue styles.
		\CMB2_hookup::enqueue_cmb_css();
		\CMB2_hookup::enqueue_cmb_js();
		rank_math()->admin_assets->register();
		wp_enqueue_style( 'rank-math-wizard', rank_math()->plugin_url() . 'assets/admin/css/setup-wizard.css', array( 'wp-admin', 'buttons', 'cmb2-styles', 'select2-rm', 'rank-math-common', 'rank-math-cmb2' ), rank_math()->version );

		// Enqueue javascript.
		wp_enqueue_script( 'rank-math-wizard', rank_math()->plugin_url() . 'assets/admin/js/wizard.js', array( 'media-editor', 'select2-rm', 'rank-math-common' ), rank_math()->version, true );

		GlobalHelper::add_json( 'currentStep', $this->step );
		GlobalHelper::add_json( 'deactivated', esc_html__( 'Deactivated', 'rank-math' ) );
		GlobalHelper::add_json( 'confirm', esc_html__( 'Are you sure you want to import settings into Rank Math? Don\'t worry, your current configuration will be saved as a backup.', 'rank-math' ) );
		GlobalHelper::add_json( 'isConfigured', GlobalHelper::is_configured() );

		ob_start();

		/**
		 * Start the actual page content.
		 */
		include_once $this->get_view( 'header' );
		include_once $this->get_view( 'content' );
		include_once $this->get_view( 'footer' );
		exit;
	}

	/**
	 * 3.b. Handles form for import page.
	 */
	protected function import_form() {
		$detector = new Detector;
		$plugins  = $detector->detect();

		$count = 0;
		foreach ( $plugins as $slug => $plugin ) {
			$checked       = 'checked';
			$multi_checked = 'multicheck-checked';
			$choices       = array_keys( $plugin['choices'] );

			if ( ( array_key_exists( 'yoast', $plugins ) || array_key_exists( 'yoast-premium', $plugins ) ) && 'aioseo' === $slug ) {
				$checked       = '';
				$multi_checked = '';
				$choices       = array();
			}

			$field_args = array(
				'id'           => 'import_from_' . $slug,
				'type'         => 'group',
				'description'  => '<input type="checkbox" class="import-data" name="import[]" value="' . $slug . '" ' . $checked . ' data-plugin="' . $plugin['name'] . '" />',
				'before_group' => 0 === $count ? '<h3 class="import-label">' . esc_html__( 'Input Data From:', 'rank-math' ) . '</h3>' : '',
				'repeatable'   => false,
				'options'      => array(
					'group_title' => $plugin['name'],
					'sortable'    => false,
					'closed'      => true,
				),
			);

			$group_id = $this->cmb->add_field( $field_args );

			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$is_active = is_plugin_active( $plugin['file'] );
			/* translators: 1 is plugin name, 2 is link to Knowledge Base article, 3 Recommended message */
			$desc = sprintf( esc_html__( 'Import settings and meta data from the %1$s plugin. The process may take a few minutes if you have a large number of posts or pages. %2$s', 'rank-math' ) . ( is_plugin_active( $plugin['file'] ) ? '<br>' . esc_html__( ' %1$s plugin will be disabled automatically moving forward to avoid conflicts. %3$s', 'rank-math' ) : '' ), $plugin['name'], '<a href="' . KB::get( 'seo-import' ) . '" target="_blank">' . esc_html__( 'Learn more about the import process here.', 'rank-math' ) . '</a>', '<strong>' . __( 'It is thus recommended to import the data you need now.', 'rank-math' ) . '</strong>' );

			if ( 'aio-rich-snippet' === $slug ) {
				/* translators: 1 is plugin name, 2 is link to Knowledge Base article, 3 Recommended message */
				$desc = sprintf( esc_html__( 'Import meta data from the %1$s plugin. The process may take a few minutes if you have a large number of posts or pages. %2$s', 'rank-math' ) . ( is_plugin_active( $plugin['file'] ) ? '<br>' . esc_html__( ' %1$s plugin will be disabled automatically moving forward to avoid conflicts. %3$s', 'rank-math' ) : '' ), $plugin['name'], '<a href="' . KB::get( 'seo-import' ) . '" target="_blank">' . esc_html__( 'Learn more about the import process here.', 'rank-math' ) . '</a>', '<strong>' . __( 'It is thus recommended to import the data you need now.', 'rank-math' ) . '</strong>' );
			}

			foreach ( $plugin['choices'] as $ck => $choice ) {
				$this->cmb->add_group_field( $group_id, array(
					'id'         => $slug . '_meta',
					'type'       => 'multicheck',
					'repeatable' => false,
					'desc'       => $desc,
					'options'    => $plugin['choices'],
					'default'    => $choices,
					'dep'        => array( array( 'import_from', $slug ) ),
					'classes'    => 'nob nopb cmb-multicheck-inline with-description ' . $multi_checked . ' ' . $is_active,
					'attributes' => array( 'data-active' => $is_active ),
				));
			}

			$count++;
		}

	}

	/**
	 * 4.b. Handles form for yoursite page.
	 */
	protected function yoursite_form() {
		$displayname = $this->get_site_display_name();

		// Default value for logo.
		$logo_url = GlobalHelper::get_settings( 'titles.knowledgegraph_logo' );

		// Check if it's a MTS theme.
		if ( defined( 'MTS_THEME_NAME' ) && MTS_THEME_NAME ) {
			$theme_options = get_option( MTS_THEME_NAME );
			$logo_url      = isset( $theme_options['mts_logo'] ) ? wp_get_attachment_url( $theme_options['mts_logo'] ) : $logo_url;
		} elseif ( current_theme_supports( 'custom-logo' ) && ! empty( get_theme_mod( 'custom_logo' ) ) ) {
			$logo_url = wp_get_attachment_url( get_theme_mod( 'custom_logo' ) );
		}

		$default = get_transient( '_rank_math_site_type' );
		$default = $default ? $default : ( class_exists( 'Easy_Digital_Downloads' ) || class_exists( 'WooCommerce' ) ? 'webshop' : 'blog' );
		$this->cmb->add_field( array(
			'id'      => 'site_type',
			'type'    => 'select',
			/* translators: sitename */
			'name'    => sprintf( esc_html__( '%1$s is a&hellip;', 'rank-math' ), $displayname ),
			'options' => array(
				'blog'          => esc_html__( 'Personal Blog', 'rank-math' ),
				'news'          => esc_html__( 'Community Blog/News Site', 'rank-math' ),
				'portfolio'     => esc_html__( 'Personal Portfolio', 'rank-math' ),
				'business'      => esc_html__( 'Small Business Site', 'rank-math' ),
				'webshop'       => esc_html__( 'Webshop', 'rank-math' ),
				'otherpersonal' => esc_html__( 'Other Personal Website', 'rank-math' ),
				'otherbusiness' => esc_html__( 'Other Business Website', 'rank-math' ),
			),
			'default' => $default,
		) );

		$this->cmb->add_field( array(
			'id'         => 'business_type',
			'type'       => 'select',
			'name'       => esc_html__( 'Business Type', 'rank-math' ),
			'desc'       => esc_html__( 'Select the type that best describes your business. If you can\'t find one that applies exactly, use the generic "Organization" or "Local Business" types.', 'rank-math' ),
			'options'    => GlobalHelper::choices_business_types(),
			'attributes' => array(
				'data-s2'      => '',
				'data-default' => GlobalHelper::get_settings( 'titles.local_business_type' ) ? '0' : '1',
			),
			'default'    => GlobalHelper::get_settings( 'titles.local_business_type' ),
			'dep'        => array(
				array( 'site_type', 'news' ),
				array( 'site_type', 'business' ),
				array( 'site_type', 'webshop' ),
				array( 'site_type', 'otherbusiness' ),
			),
		) );

		$this->cmb->add_field( array(
			'id'      => 'company_name',
			'type'    => 'text',
			'name'    => esc_html__( 'Company Name', 'rank-math' ),
			'default' => GlobalHelper::get_settings( 'titles.knowledgegraph_name', $displayname ),
			'dep'     => array(
				array( 'site_type', 'news' ),
				array( 'site_type', 'business' ),
				array( 'site_type', 'webshop' ),
				array( 'site_type', 'otherbusiness' ),
			),
		) );

		$this->cmb->add_field( array(
			'id'      => 'company_logo',
			'type'    => 'file',
			'name'    => esc_html__( 'Logo for Google', 'rank-math' ),
			'default' => $logo_url,
			'desc'    => __( '<strong>Min Size: 160Χ90px, Max Size: 1920X1080px</strong>.<br />A squared image is preferred by the search engines.', 'rank-math' ),
			'options' => array( 'url' => false ),
		) );

		$this->cmb->add_field( array(
			'id'      => 'open_graph_image',
			'type'    => 'file',
			'name'    => esc_html__( 'Default Social Share Image', 'rank-math' ),
			'desc'    => __( 'When a featured image is not set, this image will be used as a thumbnail when your post is shared on Facebook. <strong>Recommended image size 1200 x 630 pixels.</strong>', 'rank-math' ),
			'options' => array( 'url' => false ),
			'default' => GlobalHelper::get_settings( 'titles.open_graph_image' ),
		) );
	}

	/**
	 * 4.c. Handles save button from yoursite page.
	 *
	 * @param array $values Array of values of step to process.
	 */
	protected function yoursite_handler( $values ) {
		$settings     = wp_parse_args( rank_math()->settings->all_raw(), array(
			'titles'  => '',
			'sitemap' => '',
		) );
		$current_user = wp_get_current_user();
		$values       = wp_parse_args( $values, array(
			'author_name'         => $current_user->display_name,
			'company_logo'        => '',
			'company_logo_id'     => '',
			'open_graph_image'    => '',
			'open_graph_image_id' => '',
		) );

		// Local SEO.
		switch ( $values['site_type'] ) {
			case 'blog':
			case 'portfolio':
				$settings['titles']['knowledgegraph_type']    = 'person';
				$settings['titles']['knowledgegraph_name']    = $values['author_name'];
				$settings['titles']['knowledgegraph_logo']    = $values['company_logo'];
				$settings['titles']['knowledgegraph_logo_id'] = $values['company_logo_id'];
				break;

			case 'news':
			case 'webshop':
			case 'business':
			case 'otherbusiness':
				$settings['titles']['knowledgegraph_type']    = 'company';
				$settings['titles']['knowledgegraph_name']    = $values['company_name'];
				$settings['titles']['knowledgegraph_logo']    = $values['company_logo'];
				$settings['titles']['local_business_type']    = $values['business_type'];
				$settings['titles']['knowledgegraph_logo_id'] = $values['company_logo_id'];
				break;

			case 'otherpersonal':
				$settings['titles']['knowledgegraph_type'] = 'person';
				$settings['titles']['knowledgegraph_name'] = $values['author_name'];
				break;
		}

		$settings['titles']['open_graph_image']    = $values['open_graph_image'];
		$settings['titles']['open_graph_image_id'] = $values['open_graph_image_id'];

		// Check and delete.
		if ( empty( $values['open_graph_image_id'] ) ) {
			unset( $settings['titles']['open_graph_image'] );
			unset( $settings['titles']['open_graph_image_id'] );
		}
		if ( empty( $values['company_logo_id'] ) ) {
			unset( $settings['titles']['knowledgegraph_logo'] );
			unset( $settings['titles']['knowledgegraph_logo_id'] );
		}

		// Post Types.
		foreach ( GlobalHelper::get_accessible_post_types() as $post_type => $label ) {
			if ( 'attachment' === $post_type ) {
				continue;
			}
			$settings['titles'][ "pt_{$post_type}_add_meta_box" ] = 'on';
		}

		// Taxonomies.
		$taxonomies = Admin_Helper::get_taxonomies_options();
		array_shift( $taxonomies );
		foreach ( $taxonomies as $taxonomy => $label ) {
			$settings['titles'][ "tax_{$taxonomy}_add_meta_box" ] = 'on';
		}

		GlobalHelper::update_all_settings( null, $settings['titles'], null );

		$business_type = array( 'news', 'business', 'webshop', 'otherbusiness' );
		$modules       = array( 'local-seo' => in_array( $values['site_type'], $business_type ) ? 'on' : 'off' );
		$users         = get_users( array( 'role__in' => array( 'administrator', 'editor', 'author', 'contributor' ) ) );

		if ( count( $users ) > 1 && ! is_plugin_active( 'members/members.php' ) ) {
			$modules['role-manager'] = 'on';
		}

		set_transient( '_rank_math_site_type', $values['site_type'] );
		GlobalHelper::update_modules( $modules );

		return true;
	}

	/**
	 * 6.b. Handles form for search console page.
	 */
	protected function searchconsole_form() {

		$sc_dep = '';
		if ( ! GlobalHelper::is_module_active( 'search-console' ) ) {
			$this->cmb->add_field( array(
				'id'      => 'search-console',
				'type'    => 'switch',
				'name'    => esc_html__( 'Search Console', 'rank-math' ),
				'desc'    => esc_html__( 'Connect Rank Math with Google Search Console to see the most important information from Google directly in your WordPress dashboard.', 'rank-math' ),
				'default' => 'off',
			) );
			$sc_dep = array( array( 'search-console', 'on' ) );
		}

		$this->cmb->add_field( array(
			'id'      => 'rank_math_sc_step2',
			'type'    => 'raw',
			/* translators: count */
			'content' => '<br>',
		));

		$data      = GlobalHelper::search_console_data();
		$primary   = '<button class="button button-primary">' . ( $data['authorized'] ? esc_html__( 'De-authorize Account', 'rank-math' ) : esc_html__( 'Authorize', 'rank-math' ) ) . '</button>';
		$secondary = '<a href="' . esc_url( GlobalHelper::get_console_auth_url() ) . '" class="button button-secondary custom"' . ( $data['authorized'] ? ' style="display:none;"' : '' ) . '>' . esc_html__( 'Get Authorization Code', 'rank-math' ) . '</a><br />';
		$this->cmb->add_field( array(
			'id'         => 'console_authorization_code',
			'type'       => 'text',
			'name'       => esc_html__( 'Search Console', 'rank-math' ),
			'desc'       => esc_html__( 'Authorize Rank Math to access data from the Google Search Console.', 'rank-math' ),
			'attributes' => array( 'data-authorized' => $data['authorized'] ? 'true' : 'false' ),
			'after'      => $primary . $secondary,
			'dep'        => $sc_dep,
		) );

		$profile = GlobalHelper::get_settings( 'general.console_profile' );

		$this->cmb->add_field( array(
			'id'         => 'console_profile',
			'type'       => 'select',
			'name'       => esc_html__( 'Search Console Profile', 'rank-math' ),
			'desc'       => esc_html__( 'After authenticating with Google Search Console, select your website from the dropdown list.', 'rank-math' ) .
				/* translators: Link to setting screen */
				'<br><br><span style="color: orange;">' . sprintf( __( 'Is your site not listed? <a href="%1$s" target="_blank">Click here</a> to get your website verified.', 'rank-math' ), GlobalHelper::get_admin_url( 'options-general#setting-panel-webmaster' ) ) . '</span>',
			'options'    => $profile ? array( $profile => $profile ) : $data['profiles'],
			'default'    => $profile,
			'after'      => '<button class="button button-primary hidden" ' . ( $data['authorized'] ? '' : 'disabled="disabled"' ) . '>' . esc_html__( 'Refresh Sites', 'rank-math' ) . '</button>',
			'attributes' => $data['authorized'] ? array() : array( 'disabled' => 'disabled' ),
			'dep'        => $sc_dep,
		) );
	}

	/**
	 * 6.c. Handles save button from search console page.
	 *
	 * @param array $values Array of values of step to process.
	 */
	protected function searchconsole_handler( $values ) {
		$settings = rank_math()->settings->all_raw();

		if ( isset( $values['console_profile'] ) ) {
			$settings['general']['console_profile'] = $values['console_profile'];
			GlobalHelper::update_modules( array( 'search-console' => 'on' ) );
		} else {
			GlobalHelper::update_modules( array( 'search-console' => 'off' ) );
		}
		GlobalHelper::update_all_settings( $settings['general'], null, null );

		return true;
	}

	/**
	 * 7.b. Handles form for sitemaps page.
	 */
	protected function sitemaps_form() {

		// Sitemap.
		$this->cmb->add_field( array(
			'id'      => 'sitemap',
			'type'    => 'switch',
			'name'    => esc_html__( 'Sitemaps', 'rank-math' ),
			'desc'    => esc_html__( 'XML Sitemaps help search engines index your website&#039;s content more effectively.', 'rank-math' ),
			'default' => GlobalHelper::is_module_active( 'sitemap' ) ? 'on' : 'off',
		) );

		$this->cmb->add_field( array(
			'id'      => 'include_images',
			'type'    => 'switch',
			'name'    => esc_html__( 'Include Images', 'rank-math' ),
			'desc'    => esc_html__( 'Include reference to images from the post content in sitemaps. This helps search engines index your images better.', 'rank-math' ),
			'default' => GlobalHelper::get_settings( 'sitemap.include_images' ) ? 'on' : 'off',
			'classes' => 'features-child',
			'dep'     => array( array( 'sitemap', 'on' ) ),
		) );

		// Post Types.
		$p_defaults = array();
		$post_types = GlobalHelper::choices_post_types();
		unset( $post_types['attachment'] );
		foreach ( $post_types as $post_type => $object ) {
			if ( true === GlobalHelper::get_settings( "sitemap.pt_{$post_type}_sitemap" ) ) {
				$p_defaults[] = $post_type;
			}
		}
		$this->cmb->add_field( array(
			'id'      => 'sitemap_post_types',
			'type'    => 'multicheck',
			'name'    => esc_html__( 'Public Post Types', 'rank-math' ),
			'desc'    => esc_html__( 'Select post types to enable SEO options for them and include them in the sitemap.', 'rank-math' ),
			'options' => $post_types,
			'default' => $p_defaults,
			'classes' => 'features-child cmb-multicheck-inline' . ( count( $post_types ) === count( $p_defaults ) ? ' multicheck-checked' : '' ),
			'dep'     => array( array( 'sitemap', 'on' ) ),
		) );

		// Taxonomies.
		$t_defaults = array();
		$taxonomies = GlobalHelper::get_accessible_taxonomies();

		unset( $taxonomies['post_tag'], $taxonomies['post_format'], $taxonomies['product_tag'] );
		$taxonomies = wp_list_pluck( $taxonomies, 'label', 'name' );
		foreach ( $taxonomies as $taxonomy => $label ) {
			if ( true === GlobalHelper::get_settings( "sitemap.tax_{$taxonomy}_sitemap" ) ) {
				$t_defaults[] = $taxonomy;
			}
		}
		$t_defaults = ! empty( $t_defaults ) ? $t_defaults : array( 'category' );

		$this->cmb->add_field( array(
			'id'      => 'sitemap_taxonomies',
			'type'    => 'multicheck',
			'name'    => esc_html__( 'Public Taxonomies', 'rank-math' ),
			'desc'    => esc_html__( 'Select taxonomies to enable SEO options for them and include them in the sitemap.', 'rank-math' ),
			'options' => $taxonomies,
			'default' => $t_defaults,
			'classes' => 'features-child cmb-multicheck-inline' . ( count( $taxonomies ) === count( $t_defaults ) ? ' multicheck-checked' : '' ),
			'dep'     => array( array( 'sitemap', 'on' ) ),
		) );
	}

	/**
	 * 7.c. Handles save button from sitemaps page.
	 *
	 * @param array $values Array of values of step to process.
	 */
	protected function sitemaps_handler( $values ) {
		$settings = rank_math()->settings->all_raw();
		GlobalHelper::update_modules( array( 'sitemap' => $values['sitemap'] ) );

		if ( 'on' === $values['sitemap'] ) {
			$settings['sitemap']['include_images'] = $values['include_images'];

			// Sitemaps - Post Types.
			$post_types = GlobalHelper::choices_post_types();
			if ( ! isset( $values['sitemap_post_types'] ) ) {
				$values['sitemap_post_types'] = array();
			}
			foreach ( $post_types as $post_type => $object ) {
				$settings['sitemap'][ "pt_{$post_type}_sitemap" ] = in_array( $post_type, $values['sitemap_post_types'] ) ? 'on' : 'off';
			}

			// Sitemaps - Taxonomies.
			$taxonomies = GlobalHelper::get_accessible_taxonomies();
			$taxonomies = wp_list_pluck( $taxonomies, 'label', 'name' );
			if ( ! isset( $values['sitemap_taxonomies'] ) ) {
				$values['sitemap_taxonomies'] = array();
			}
			foreach ( $taxonomies as $taxonomy => $label ) {
				$settings['sitemap'][ "tax_{$taxonomy}_sitemap" ] = in_array( $taxonomy, $values['sitemap_taxonomies'] ) ? 'on' : 'off';
			}

			GlobalHelper::update_all_settings( null, null, $settings['sitemap'] );
		}

		GlobalHelper::schedule_flush_rewrite();
		return true;
	}

	/**
	 * 7.b. Handles form for optimization page.
	 */
	protected function optimization_form() {

		$this->cmb->add_field( array(
			'id'      => 'noindex_empty_taxonomies',
			'type'    => 'switch',
			'name'    => esc_html__( 'Noindex Empty Category and Tag Archives', 'rank-math' ),
			'desc'    => wp_kses_post( __( 'Setting empty archives to <code>noindex</code> is useful for avoiding indexation of thin content pages and dilution of page rank. As soon as a post is added, the page is updated to <code>index</code>.', 'rank-math' ) ),
			'default' => GlobalHelper::get_settings( 'titles.noindex_empty_taxonomies' ) ? 'on' : 'off',
		) );

		$this->cmb->add_field( array(
			'id'      => 'nofollow_image_links',
			'type'    => 'switch',
			'name'    => esc_html__( 'Nofollow Image File Links', 'rank-math' ),
			'desc'    => wp_kses_post( __( 'Automatically add <code>rel="nofollow"</code> attribute for links pointing to external image files. The attribute is dynamically applied when the content is displayed, and the stored content is not changed.', 'rank-math' ) ),
			'default' => GlobalHelper::get_settings( 'general.nofollow_image_links' ) ? 'on' : 'off',
		) );

		$this->cmb->add_field( array(
			'id'      => 'nofollow_external_links',
			'type'    => 'switch',
			'name'    => esc_html__( 'Nofollow External Links', 'rank-math' ),
			'desc'    => wp_kses_post( __( 'Automatically add <code>rel="nofollow"</code> attribute for external links appearing in your posts, pages, and other post types. The attribute is dynamically applied when the content is displayed, and the stored content is not changed.', 'rank-math' ) ),
			'default' => GlobalHelper::get_settings( 'general.nofollow_external_links' ) ? 'on' : 'off',
		) );

		$this->cmb->add_field( array(
			'id'      => 'new_window_external_links',
			'type'    => 'switch',
			'name'    => esc_html__( 'Open External Links in New Tab/Window', 'rank-math' ),
			'desc'    => wp_kses_post( __( 'Automatically add a <code>target="_blank"</code> attribute to external links appearing in your posts, pages, and other post types. The attributes are applied when the content is displayed, which does not change the stored content.', 'rank-math' ) ),
			'default' => GlobalHelper::get_settings( 'general.new_window_external_links' ) ? 'on' : 'off',
		) );

		$this->cmb->add_field( array(
			'id'      => 'strip_category_base',
			'type'    => 'switch',
			'name'    => esc_html__( 'Strip Category Base', 'rank-math' ),
			/* translators: Link to kb article */
			'desc'    => sprintf( wp_kses_post( __( 'Remove /category/ from category archive URLs. <a href="%s" target="_blank">Why do this?</a><br>E.g. <code>example.com/category/my-category/</code> becomes <code>example.com/my-category</code>', 'rank-math' ) ), KB::get( 'remove-category-base' ) ),
			'default' => GlobalHelper::get_settings( 'general.strip_category_base' ) ? 'on' : 'off',
		) );
	}

	/**
	 * Optimization save handler
	 *
	 * @param array $values Array of values of step to process.
	 */
	protected function optimization_handler( $values ) {
		$settings = rank_math()->settings->all_raw();

		$settings['general']['strip_category_base']     = $values['strip_category_base'];
		$settings['titles']['noindex_empty_taxonomies'] = $values['noindex_empty_taxonomies'];

		if ( isset( $values['attachment_redirect_urls'] ) && 'on' === $values['attachment_redirect_urls'] ) {
			$settings['general']['attachment_redirect_urls']    = $values['attachment_redirect_urls'];
			$settings['general']['attachment_redirect_default'] = $values['attachment_redirect_default'];
		}

		$settings['general']['nofollow_image_links']      = $values['nofollow_image_links'];
		$settings['general']['nofollow_external_links']   = $values['nofollow_external_links'];
		$settings['general']['new_window_external_links'] = $values['new_window_external_links'];

		GlobalHelper::is_configured( true );
		GlobalHelper::update_all_settings( $settings['general'], $settings['titles'], null );
		GlobalHelper::schedule_flush_rewrite();

		return true;
	}

	/**
	 * Shows role step.
	 */
	public function role() {
		?>
		<header>
			<h1><?php esc_html_e( 'Role Manager', 'rank-math' ); ?></h1>
			<p><?php esc_html_e( 'Set capabilities here.', 'rank-math' ); ?></p>
		</header>

		<?php $this->cmb->show_form(); ?>

		<footer class="form-footer wp-core-ui rank-math-ui">
			<?php $this->skip_link(); ?>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save and Continue', 'rank-math' ); ?></button>
		</footer>
		<?php
	}

	/**
	 * Shows role step form.
	 */
	public function role_form() {
		$defaults  = GlobalHelper::get_roles_capabilities();
		$cap_count = count( GlobalHelper::get_capabilities() );

		foreach ( WordPress::get_roles() as $role => $label ) {
			$default = isset( $defaults[ $role ] ) ? $defaults[ $role ] : [];
			$this->cmb->add_field( array(
				'id'      => esc_attr( $role ),
				'type'    => 'multicheck_inline',
				'name'    => translate_user_role( $label ),
				'options' => GlobalHelper::get_capabilities(),
				'default' => $default,
				'classes' => 'cmb-big-labels' . ( count( $default ) === $cap_count ? ' multicheck-checked' : '' ),
			) );
		}
	}

	/**
	 * Updates role values.
	 *
	 * @param array $roles Role values.
	 * @return bool
	 */
	public function role_handler( $roles ) {

		if ( empty( $roles ) ) {
			return false;
		}

		GlobalHelper::set_capabilities( $roles );
		return true;
	}

	/**
	 * Shows redirection step.
	 */
	public function redirection() {
		?>
		<header>
			<h1><?php esc_html_e( '404 Monitor', 'rank-math' ); ?> </h1>
			<p><?php esc_html_e( 'Set default values for the 404 error monitor here.', 'rank-math' ); ?> <a href="<?php KB::the( '404-monitor' ); ?>" target="_blank"><?php esc_html_e( 'Learn about the options here.', 'rank-math' ); ?></a></p>
		</header>

		<?php $this->cmb->show_form(); ?>

		<footer class="form-footer wp-core-ui rank-math-ui">
			<?php $this->skip_link(); ?>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save and Continue', 'rank-math' ); ?></button>
		</footer>
		<?php
	}

	/**
	 * Shows redirection step form.
	 */
	public function redirection_form() {

		// 404 Monitor.
		$this->cmb->add_field( array(
			'id'      => '404_monitor',
			'type'    => 'switch',
			'name'    => esc_html__( '404 Monitor', 'rank-math' ),
			/* translators: Link to kb article */
			'desc'    => __( 'The 404 monitor will let you see if visitors or search engines bump into any <code>404 Not Found</code> error while browsing your site.', 'rank-math' ),
			'default' => GlobalHelper::is_module_active( '404-monitor' ) ? 'on' : 'off',
		) );

		// Redirections.
		$this->cmb->add_field( array(
			'id'      => 'redirection_title',
			'type'    => 'raw',
			'content' => sprintf( '<br><header class="redirections-header"><h1 class="text-center">%1$s</h1><p class="text-center redirections-desc">%2$s %3$s</p>', esc_html__( 'Redirections', 'rank-math' ), esc_html__( 'Set default values for the redirection module from here.', 'rank-math' ), '<a href="' . KB::get( 'redirections' ) . '" target="_blank">' . esc_html__( 'Learn more about Redirections.', 'rank-math' ) . '</a></header>' ),
		));

		$this->cmb->add_field( array(
			'id'      => 'redirections',
			'type'    => 'switch',
			'name'    => esc_html__( 'Redirections', 'rank-math' ),
			'desc'    => esc_html__( 'Set up temporary or permanent redirections. Combined with the 404 monitor, you can easily redirect faulty URLs on your site, or add custom redirections.', 'rank-math' ),
			'default' => GlobalHelper::is_module_active( 'redirections' ) ? 'on' : 'off',
		) );
	}

	/**
	 * Updates redirection values.
	 *
	 * @param array $values Redirection values.
	 * @return bool
	 */
	public function redirection_handler( $values ) {
		// Modules.
		GlobalHelper::update_modules( array(
			'404-monitor'  => $values['404_monitor'],
			'redirections' => $values['redirections'],
		) );

		return true;
	}

	/**
	 * Shows misc step.
	 */
	public function misc() {
		?>
		<header>
			<h1><?php esc_html_e( 'Miscellaneous ', 'rank-math' ); ?> </h1>
			<p><?php esc_html_e( 'Control different settings for important aspects of your website here.', 'rank-math' ); ?></p>
		</header>

		<?php $this->cmb->show_form(); ?>

		<footer class="form-footer wp-core-ui rank-math-ui">
			<a href="<?php echo esc_url( GlobalHelper::get_admin_url() ); ?>" class="button button-secondary button-skip"><?php esc_html_e( 'Skip step', 'rank-math' ); ?></a>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save and Continue', 'rank-math' ); ?></button>
		</footer>
		<?php
	}

	/**
	 * Shows misc step form.
	 */
	public function misc_form() {

		$this->cmb->add_field( array(
			'id'      => 'add_img_alt',
			'type'    => 'switch',
			'name'    => esc_html__( 'Add missing ALT attributes', 'rank-math' ),
			/* translators: Link to setting screen */
			'desc'    => sprintf( wp_kses_post( __( 'Add missing <code>alt</code> attribute for <code>img</code> tags in your post contents and featured images. This option will not change the stored content of the post, it adds the attribute on the fly when the content is displayed. You can see %s.', 'rank-math' ) ), '<a href="' . GlobalHelper::get_admin_url( 'options-general#setting-panel-images' ) . '" target="_blank">' . esc_html__( 'all options we offer here', 'rank-math' ) . '</a>' ),
			'default' => GlobalHelper::get_settings( 'general.add_img_alt' ) ? 'on' : 'off',
		) );

		$this->cmb->add_field( array(
			'id'      => 'rich_snippet',
			'type'    => 'switch',
			'name'    => esc_html__( 'Rich Snippet', 'rank-math' ),
			'desc'    => esc_html__( 'Use automatic structured data to mark up content, to help Google better understand your content\'s context for display in Search. You can set different defaults for your posts here.', 'rank-math' ),
			'default' => GlobalHelper::is_module_active( 'rich-snippet' ) ? 'on' : 'off',
		) );

		$rich_snippet    = array( array( 'rich_snippet', 'on' ) );
		$richsnp_default = array(
			'post'    => 'article',
			'product' => 'product',
		);
		foreach ( GlobalHelper::get_accessible_post_types() as $post_type ) {
			$object = get_post_type_object( $post_type );

			if ( 'product' === $post_type ) {
				$this->cmb->add_field( array(
					'id'      => 'pt_' . $post_type . '_default_rich_snippet',
					'type'    => 'radio_inline',
					/* translators: Post type name */
					'name'    => sprintf( esc_html__( 'Rich Snippet Type for %s', 'rank-math' ), $object->label ),
					'desc'    => __( 'Default rich snippet selected when creating a new product.', 'rank-math' ),
					'options' => array(
						'off'     => esc_html__( 'None', 'rank-math' ),
						'product' => esc_html__( 'Product', 'rank-math' ),
					),
					'default' => GlobalHelper::get_settings( 'titles.pt_' . $post_type . '_default_rich_snippet', ( isset( $richsnp_default[ $post_type ] ) ? $richsnp_default[ $post_type ] : 'product' ) ),
				) );

			} elseif ( 'attachment' !== $post_type ) {
				$this->cmb->add_field( array(
					'id'      => 'pt_' . $post_type . '_default_rich_snippet',
					'type'    => 'select',
					/* translators: post_type label */
					'name'    => sprintf( esc_html__( 'Rich Snippet Type for %s', 'rank-math' ), $object->label ),
					'desc'    => esc_html__( 'Default rich snippet selected when creating a new post of this type. ', 'rank-math' ),
					'options' => GlobalHelper::choices_rich_snippet_types( esc_html__( 'None (Click here to set one)', 'rank-math' ) ),
					'dep'     => $rich_snippet,
					'default' => GlobalHelper::get_settings( 'titles.pt_' . $post_type . '_default_rich_snippet', ( isset( $richsnp_default[ $post_type ] ) ? $richsnp_default[ $post_type ] : 'none' ) ),
				) );
			}

			// Article fields.
			$article_dep   = array( 'relation' => 'and' ) + $rich_snippet;
			$article_dep[] = array( 'pt_' . $post_type . '_default_rich_snippet', 'article' );
			/* translators: Google article snippet doc link */
			$article_desc = 'person' === GlobalHelper::get_settings( 'titles.knowledgegraph_type' ) ? '<div class="notice notice-warning inline" style="margin-left:0;"><p>' . sprintf( __( 'Google does not allow Person as the Publisher for articles. Organization will be used instead. You can read more about this <a href="%s" target="_blank">here</a>.', 'rank-math' ), KB::get( 'article' ) ) . '</p></div>' : '';
			$this->cmb->add_field( array(
				'id'      => 'pt_' . $post_type . '_default_article_type',
				'type'    => 'radio_inline',
				'name'    => esc_html__( 'Article Type', 'rank-math' ),
				'options' => array(
					'Article'     => esc_html__( 'Article', 'rank-math' ),
					'BlogPosting' => esc_html__( 'Blog Post', 'rank-math' ),
					'NewsArticle' => esc_html__( 'News Article', 'rank-math' ),
				),
				'default' => 'post' === $post_type ? 'BlogPosting' : 'Article',
				'dep'     => $article_dep,
				'desc'    => $article_desc,
			) );
		}
	}

	/**
	 * Updates misc values.
	 *
	 * @param array $values Misc values.
	 * @return bool
	 */
	public function misc_handler( $values ) {
		$settings = rank_math()->settings->all_raw();
		GlobalHelper::update_modules( array( 'rich-snippet' => $values['rich_snippet'] ) );

		// General.
		$settings['general']['add_img_alt'] = $values['add_img_alt'];

		// Rich Snippet.
		if ( 'on' === $values['rich_snippet'] ) {
			foreach ( GlobalHelper::get_accessible_post_types() as $post_type ) {
				if ( 'attachment' === $post_type ) {
					continue;
				}

				$id           = 'pt_' . $post_type . '_default_rich_snippet';
				$article_type = 'pt_' . $post_type . '_default_article_type';

				$settings['titles'][ $id ]           = $values[ $id ];
				$settings['titles'][ $article_type ] = $values[ $article_type ];
			}
		}
		GlobalHelper::update_all_settings( $settings['general'], $settings['titles'], null );

		return GlobalHelper::get_admin_url();
	}

	/**
	 * Modify module status.
	 *
	 * @param array  $modules Old modules instance.
	 * @param string $module  Module ID.
	 * @param bool   $action  Enable/Disable.
	 * @return array
	 */
	public function modify_module( $modules, $module, $action = 'off' ) {
		if ( 'off' === $action && in_array( $module, $modules ) ) {
			$modules = array_diff( $modules, array( $module ) );
		}

		if ( 'on' === $action && ! in_array( $module, $modules ) ) {
			$modules[] = $module;
			$modules   = array_unique( $modules );
		}

		return $modules;
	}

	/**
	 * Get the step URL.
	 *
	 * @param string $step Name of the step, appended to the URL.
	 */
	public function step_link( $step ) {
		return add_query_arg( 'step', $step );
	}

	/**
	 * Get Skip Link.
	 */
	public function skip_link() {
		?>
		<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="button button-secondary button-skip"><?php esc_html_e( 'Skip step', 'rank-math' ); ?></a>
		<?php
	}

	/**
	 * Get site display name.
	 *
	 * @return string
	 */
	protected function get_site_display_name() {
		$siteurl  = get_bloginfo( 'url' );
		$sitename = get_bloginfo( 'title' );

		return $sitename ? $sitename : $siteurl;
	}

	/**
	 * Setup steps.
	 */
	public function steps() {

		$is_advanced = $this->is_advance();

		$this->steps['compatibility'] = array(
			'name'     => esc_html__( 'Requirements', 'rank-math' ),
			'view'     => $this->get_view( 'compatibility' ),
			'nav_hide' => true,
		);

		if ( false === get_option( 'rank_math_is_configured' ) ) {
			$detector = new Detector;
			$plugins  = $detector->detect();
			if ( ! empty( $plugins ) ) {
				$this->steps['import'] = array(
					'name'     => esc_html__( 'Import', 'rank-math' ),
					'view'     => $this->get_view( 'import' ),
					'form'     => array( $this, 'import_form' ),
					'nav_hide' => $is_advanced,
				);
			}
		}

		$this->steps['yoursite'] = array(
			'name'     => esc_html__( 'Your Site', 'rank-math' ),
			'view'     => $this->get_view( 'your-site' ),
			'form'     => array( $this, 'yoursite_form' ),
			'handler'  => array( $this, 'yoursite_handler' ),
			'nav_hide' => $is_advanced,
		);

		$this->steps['searchconsole'] = array(
			'name'     => esc_html__( 'Search Console', 'rank-math' ),
			'view'     => $this->get_view( 'search-console' ),
			'form'     => array( $this, 'searchconsole_form' ),
			'handler'  => array( $this, 'searchconsole_handler' ),
			'nav_hide' => $is_advanced,
		);

		$this->steps['sitemaps'] = array(
			'name'     => esc_html__( 'Sitemaps', 'rank-math' ),
			'view'     => $this->get_view( 'sitemaps' ),
			'form'     => array( $this, 'sitemaps_form' ),
			'handler'  => array( $this, 'sitemaps_handler' ),
			'nav_hide' => $is_advanced,
		);

		$this->steps['optimization'] = array(
			'name'     => esc_html__( 'Optimization', 'rank-math' ),
			'view'     => $this->get_view( 'optimization' ),
			'form'     => array( $this, 'optimization_form' ),
			'handler'  => array( $this, 'optimization_handler' ),
			'nav_hide' => $is_advanced,
		);

		$this->steps['ready'] = array(
			'name'     => esc_html__( 'Ready', 'rank-math' ),
			'view'     => $this->get_view( 'ready' ),
			'nav_hide' => $is_advanced,
		);

		if ( GlobalHelper::is_module_active( 'role-manager' ) ) {
			$this->steps['role'] = array(
				'name'     => esc_html__( 'Role Manager', 'rank-math' ),
				'view'     => array( $this, 'role' ),
				'form'     => array( $this, 'role_form' ),
				'handler'  => array( $this, 'role_handler' ),
				'nav_hide' => ! $is_advanced,
			);
		}

		$this->steps['redirection'] = array(
			'name'     => esc_html__( '404 + Redirection', 'rank-math' ),
			'view'     => array( $this, 'redirection' ),
			'form'     => array( $this, 'redirection_form' ),
			'handler'  => array( $this, 'redirection_handler' ),
			'nav_hide' => ! $is_advanced,
		);

		$this->steps['misc'] = array(
			'name'     => esc_html__( 'Misc', 'rank-math' ),
			'view'     => array( $this, 'misc' ),
			'form'     => array( $this, 'misc_form' ),
			'handler'  => array( $this, 'misc_handler' ),
			'nav_hide' => ! $is_advanced,
		);

		$this->steps = $this->do_filter( 'wizard/steps', $this->steps );

		// Set Current Step.
		$this->step      = isset( $_REQUEST['step'] ) ? sanitize_key( $_REQUEST['step'] ) : current( array_keys( $this->steps ) );
		$this->step_slug = isset( $this->steps[ $this->step ] ) ? strtolower( $this->steps[ $this->step ]['name'] ) : '';
	}

	/**
	 * Set string.
	 */
	private function strings() {
		// Strings passed in from the config file.
		$this->strings = array(
			'admin-menu'          => esc_html__( 'Setup Wizard', 'rank-math' ),
			'title'               => esc_html__( 'Setup Wizard - Rank Math', 'rank-math' ),
			'return-to-dashboard' => esc_html__( 'Return to dashboard', 'rank-math' ),
			'btn-start'           => esc_html__( 'Start', 'rank-math' ),
			'btn-no'              => esc_html__( 'Cancel', 'rank-math' ),
		);
	}

	/**
	 * Get conflicting plugins.
	 *
	 * @return array
	 */
	private function get_conflicting_plugins() {
		$plugins_found       = array();
		$active_plugins      = get_option( 'active_plugins' );
		$conflicting_plugins = $this->get_conflicting_plugins_list();
		foreach ( $conflicting_plugins as $plugin_slug => $plugin_name ) {
			if ( in_array( $plugin_slug, $active_plugins ) !== false ) {
				$plugins_found[ $plugin_slug ] = $plugin_name;
			}
		}

		return $plugins_found;
	}

	/**
	 * Return list of possibly conflicting plugins.
	 * Used in SEO Analysis & Setup Wizard.
	 *
	 * @return array List of plugins in path => name format
	 */
	private function get_conflicting_plugins_list() {

		$plugins = array(
			'2-click-socialmedia-buttons/2-click-socialmedia-buttons.php' => '2 Click Social Media Buttons.',
			'add-link-to-facebook/add-link-to-facebook.php' => 'Add Link to Facebook.',
			'extended-wp-reset/extended-wp-reset.php'      => 'Extended WP Reset.',
			'add-meta-tags/add-meta-tags.php'              => 'Add Meta Tags.',
			'all-in-one-seo-pack/all_in_one_seo_pack.php'  => 'All In One SEO Pack',
			'easy-facebook-share-thumbnails/esft.php'      => 'Easy Facebook Share Thumbnail.',
			'facebook/facebook.php'                        => 'Facebook (official plugin).',
			'facebook-awd/AWD_facebook.php'                => 'Facebook AWD All in one.',
			'facebook-featured-image-and-open-graph-meta-tags/fb-featured-image.php' => 'Facebook Featured Image & OG Meta Tags.',
			'facebook-meta-tags/facebook-metatags.php'     => 'Facebook Meta Tags.',
			'wonderm00ns-simple-facebook-open-graph-tags/wonderm00n-open-graph.php' => 'Facebook Open Graph Meta Tags for WordPress.',
			'facebook-revised-open-graph-meta-tag/index.php' => 'Facebook Revised Open Graph Meta Tag.',
			'facebook-thumb-fixer/_facebook-thumb-fixer.php' => 'Facebook Thumb Fixer.',
			'facebook-and-digg-thumbnail-generator/facebook-and-digg-thumbnail-generator.php' => 'Fedmich\'s Facebook Open Graph Meta.',
			'header-footer/plugin.php'                     => 'Header and Footer.',
			'network-publisher/networkpub.php'             => 'Network Publisher.',
			'nextgen-facebook/nextgen-facebook.php'        => 'NextGEN Facebook OG.',
			'opengraph/opengraph.php'                      => 'Open Graph.',
			'open-graph-protocol-framework/open-graph-protocol-framework.php' => 'Open Graph Protocol Framework.',
			'seo-facebook-comments/seofacebook.php'        => 'SEO Facebook Comments.',
			'seo-ultimate/seo-ultimate.php'                => 'SEO Ultimate.',
			'sexybookmarks/sexy-bookmarks.php'             => 'Shareaholic.',
			'shareaholic/sexy-bookmarks.php'               => 'Shareaholic.',
			'sharepress/sharepress.php'                    => 'SharePress.',
			'simple-facebook-connect/sfc.php'              => 'Simple Facebook Connect.',
			'social-discussions/social-discussions.php'    => 'Social Discussions.',
			'social-sharing-toolkit/social_sharing_toolkit.php' => 'Social Sharing Toolkit.',
			'socialize/socialize.php'                      => 'Socialize.',
			'only-tweet-like-share-and-google-1/tweet-like-plusone.php' => 'Tweet, Like, Google +1 and Share.',
			'wordbooker/wordbooker.php'                    => 'Wordbooker.',
			'wordpress-seo/wp-seo.php'                     => 'Yoast SEO',
			'wordpress-seo-premium/wp-seo-premium.php'     => 'Yoast SEO Premium',
			'wpsso/wpsso.php'                              => 'WordPress Social Sharing Optimization.',
			'wp-caregiver/wp-caregiver.php'                => 'WP Caregiver.',
			'wp-facebook-like-send-open-graph-meta/wp-facebook-like-send-open-graph-meta.php' => 'WP Facebook Like Send & Open Graph Meta.',
			'wp-facebook-open-graph-protocol/wp-facebook-ogp.php' => 'WP Facebook Open Graph protocol.',
			'wp-ogp/wp-ogp.php'                            => 'WP-OGP.',
			'zoltonorg-social-plugin/zosp.php'             => 'Zolton.org Social Plugin.',
			'all-in-one-schemaorg-rich-snippets/index.php' => 'All In One Schema Rich Snippets.',
			'wp-schema-pro/wp-schema-pro.php'              => 'Schema Pro',
			'no-category-base-wpml/no-category-base-wpml.php' => 'No Category Base (WPML)',
			'all-404-redirect-to-homepage/all-404-redirect-to-homepage.php' => 'All 404 Redirect to Homepage',
			'remove-category-url/remove-category-url.php'  => 'Remove Category URL',
		);

		$redirection_plugins = array(
			'redirection/redirection.php' => 'Redirection',
		);

		$sitemap_plugins = array(
			'google-sitemap-plugin/google-sitemap-plugin.php' => 'Google Sitemap (BestWebSoft).',
			'xml-sitemaps/xml-sitemaps.php'                => 'XML Sitemaps (Denis de Bernardy and Mike Koepke).',
			'bwp-google-xml-sitemaps/bwp-simple-gxs.php'   => 'Better WordPress Google XML Sitemaps (Khang Minh).',
			'google-sitemap-generator/sitemap.php'         => 'Google XML Sitemaps (Arne Brachhold).',
			'xml-sitemap-feed/xml-sitemap.php'             => 'XML Sitemap & Google News feeds (RavanH).',
			'google-monthly-xml-sitemap/monthly-xml-sitemap.php' => 'Google Monthly XML Sitemap (Andrea Pernici).',
			'simple-google-sitemap-xml/simple-google-sitemap-xml.php' => 'Simple Google Sitemap XML (iTx Technologies).',
			'another-simple-xml-sitemap/another-simple-xml-sitemap.php' => 'Another Simple XML Sitemap.',
			'xml-maps/google-sitemap.php'                  => 'Xml Sitemap (Jason Martens).',
			'google-xml-sitemap-generator-by-anton-dachauer/adachauer-google-xml-sitemap.php' => 'Google XML Sitemap Generator by Anton Dachauer (Anton Dachauer).',
			'wp-xml-sitemap/wp-xml-sitemap.php'            => 'WP XML Sitemap (Team Vivacity).',
			'sitemap-generator-for-webmasters/sitemap.php' => 'Sitemap Generator for Webmasters (iwebslogtech).',
			'xml-sitemap-xml-sitemapcouk/xmls.php'         => 'XML Sitemap - XML-Sitemap.co.uk (Simon Hancox).',
			'sewn-in-xml-sitemap/sewn-xml-sitemap.php'     => 'Sewn In XML Sitemap (jcow).',
			'rps-sitemap-generator/rps-sitemap-generator.php' => 'RPS Sitemap Generator (redpixelstudios).',
		);

		$plugins = GlobalHelper::is_module_active( 'redirections' ) ? array_merge( $plugins, $redirection_plugins ) : $plugins;
		$plugins = GlobalHelper::is_module_active( 'sitemap' ) ? array_merge( $plugins, $sitemap_plugins ) : $plugins;

		return $plugins;
	}

	/**
	 * Checks if current step is advanced.
	 *
	 * @return bool
	 */
	public function is_advance() {
		return isset( $_REQUEST['step'] ) && in_array( $_REQUEST['step'], array( 'role', 'redirection', 'linkbuilder', 'misc' ) );
	}

	/**
	 * Get view file to display.
	 *
	 * @param string $view View to display.
	 * @return string
	 */
	public function get_view( $view ) {
		return Admin_Helper::get_view( "wizard/{$view}" );
	}

	/**
	 * [change_label description]
	 *
	 * @param  [type] $label [description].
	 * @return [type]        [description]
	 */
	public function change_label( $label ) {

		if ( $this->is_advance() ) {
			return esc_html__( 'Advance Options', 'rank-math' );
		}

		return $label;
	}

	/**
	 * [change_label_url description]
	 *
	 * @param  [type] $url [description].
	 * @return [type]      [description]
	 */
	public function change_label_url( $url ) {

		if ( $this->is_advance() ) {
			return GLobalHelper::get_admin_url( 'wizard', 'step=ready' );
		}

		return $url;
	}
}
