<?php
/**
 * The metabox functionality of the plugin.
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Admin;

use CMB2_hookup;
use RankMath\KB;
use RankMath\CMB2;
use RankMath\Runner;
use RankMath\Replace_Vars;
use RankMath\Traits\Hooker;
use RankMath\Helper as GlobalHelper;
use MyThemeShop\Helpers\Str;
use MyThemeShop\Helpers\Url;

defined( 'ABSPATH' ) || exit;

/**
 * Metabox class.
 */
class Metabox implements Runner {

	use Hooker;

	/**
	 * Metabox id.
	 *
	 * @var string
	 */
	private $metabox_id = 'rank_math_metabox';

	/**
	 * Register hooks.
	 */
	public function hooks() {
		$this->action( 'rank_math/admin/enqueue_scripts', 'enqueue' );
		$this->action( 'cmb2_admin_init', 'add_main_metabox', 30 );
		$this->action( 'cmb2_admin_init', 'add_link_suggestion_metabox', 30 );
		$this->action( 'cmb2_' . CMB2::current_object_type() . '_process_fields_' . $this->metabox_id, 'save_meta' );
		$this->action( 'cmb2_save_field', 'invalidate_facebook_object_cache', 10, 4 );
	}

	/**
	 * Enqueue Styles and Scripts required for metabox.
	 */
	public function enqueue() {

		$screen = get_current_screen();
		if ( ! in_array( $screen->base, array( 'post', 'term', 'profile', 'user-edit' ) ) ) {
			return;
		}

		// Styles.
		CMB2_hookup::enqueue_cmb_css();
		Replace_Vars::setup_json();
		wp_enqueue_style( 'rank-math-metabox', rank_math()->plugin_url() . '/assets/admin/css/metabox.css', array( 'rank-math-common', 'rank-math-cmb2' ), rank_math()->version );

		// JSON data.
		GlobalHelper::add_json( 'locale', substr( get_locale(), 0, 2 ) );
		GlobalHelper::add_json( 'overlayImages', GlobalHelper::choices_overlay_images() );
		GlobalHelper::add_json( 'customPermalinks', (bool) get_option( 'permalink_structure', false ) );
		GlobalHelper::add_json( 'defautOgImage', GlobalHelper::get_settings( 'titles.open_graph_image', '' ) );
		GlobalHelper::add_json( 'postSettings', array(
			'linkSuggestions' => GlobalHelper::get_settings( 'titles.pt_' . $screen->post_type . '_link_suggestions' ),
			'useFocusKeyword' => 'focus_keywords' === GlobalHelper::get_settings( 'titles.pt_' . $screen->post_type . '_ls_use_fk' ),
		) );

		$js = rank_math()->plugin_url() . 'assets/admin/js/';
		wp_enqueue_script( 'jquery-caret', rank_math()->plugin_url() . 'assets/vendor/jquery.caret.min.js', array( 'jquery' ), '1.3.3', true );
		wp_enqueue_script( 'jquery-tag-editor', $js . 'jquery.tag-editor.js', array( 'jquery-ui-autocomplete', 'jquery-caret' ), '1.0.21', true );
		wp_enqueue_script( 'rank-math-assessor', $js . 'assessor.js', null, rank_math()->version, true );

		if ( Admin_Helper::is_post_edit() ) {
			global $post;
			GlobalHelper::add_json( 'objectID', $post->ID );
			GlobalHelper::add_json( 'objectType', 'post' );
			GlobalHelper::add_json( 'parentDomain', Url::get_domain( home_url() ) );
			GlobalHelper::add_json( 'noFollowDomains', Str::to_arr_no_empty( GlobalHelper::get_settings( 'general.nofollow_domains' ) ) );
			GlobalHelper::add_json( 'noFollowExcludeDomains', Str::to_arr_no_empty( GlobalHelper::get_settings( 'general.nofollow_exclude_domains' ) ) );
			GlobalHelper::add_json( 'noFollowExternalLinks', GlobalHelper::get_settings( 'general.nofollow_external_links' ) );
			GlobalHelper::add_json( 'featuredImageNotice', esc_html__( 'The featured image should be at least 200 by 200 pixels to be picked up by Facebook and other social media sites.', 'rank-math' ) );

			wp_enqueue_script( 'rank-math-post-metabox', $js . 'post-metabox.js', array( 'clipboard', 'rank-math-common', 'rank-math-assessor', 'jquery-tag-editor' ), rank_math()->version, true );
		}

		if ( Admin_Helper::is_term_edit() ) {
			GlobalHelper::add_json( 'objectID', isset( $_REQUEST['tag_ID'] ) ? absint( $_REQUEST['tag_ID'] ) : 0 );
			GlobalHelper::add_json( 'objectType', 'term' );

			wp_enqueue_script( 'rank-math-term-metabox', $js . 'term-metabox.js', array( 'rank-math-common', 'rank-math-assessor', 'jquery-tag-editor' ), rank_math()->version, true );
		}

		if ( $this->is_user_metabox() && Admin_Helper::is_user_edit() ) {
			global $user_id;
			GlobalHelper::add_json( 'objectID', $user_id );
			GlobalHelper::add_json( 'objectType', 'user' );

			wp_enqueue_script( 'rank-math-user-metabox', $js . 'user-metabox.js', array( 'rank-math-common', 'rank-math-assessor', 'jquery-tag-editor' ), rank_math()->version, true );
		}

		$this->assessor();
	}

	/**
	 * Add main metabox.
	 */
	public function add_main_metabox() {
		if ( $this->can_add_metabox() ) {
			return;
		}

		$cmb = new_cmb2_box( array(
			'id'               => $this->metabox_id,
			'title'            => esc_html__( 'Rank Math SEO', 'rank-math' ),
			'object_types'     => $this->get_object_types(),
			'taxonomies'       => GlobalHelper::get_allowed_taxonomies(),
			'new_term_section' => false,
			'new_user_section' => 'add-existing-user',
			'context'          => 'normal',
			'priority'         => $this->get_priority(),
			'cmb_styles'       => false,
			'classes'          => 'rank-math-metabox-wrap' . ( Admin_Helper::is_term_profile_page() ? ' rank-math-metabox-frame' : '' ),
		) );

		$tabs = $this->get_tabs();
		$cmb->add_field( array(
			'id'   => 'setting-panel-container-' . $this->metabox_id,
			'type' => 'meta_tab_container_open',
			'tabs' => $tabs,
		) );

		foreach ( $tabs as $id => $tab ) {

			if ( ! GlobalHelper::has_cap( $tab['capability'] ) ) {
				continue;
			}

			$cmb->add_field( array(
				'id'   => 'setting-panel-' . $id,
				'type' => 'tab_open',
			) );

			include_once $tab['file'];

			/**
			 * Add setting into specific tab of main metabox.
			 *
			 * The dynamic part of the hook name. $id, is the tab id.
			 *
			 * @param CMB2 $cmb CMB2 object.
			 */
			$this->do_action( 'metabox/settings/' . $id, $cmb );

			$cmb->add_field( array(
				'id'   => 'setting-panel-' . $id . '-close',
				'type' => 'tab_close',
			) );
		}

		$cmb->add_field( array(
			'id'   => 'setting-panel-container-close-' . $this->metabox_id,
			'type' => 'tab_container_close',
		) );

		CMB2::pre_init( $cmb );
	}

	/**
	 * Add link suggestion metabox.
	 */
	public function add_link_suggestion_metabox() {

		$allowed_post_types = array();
		foreach ( GlobalHelper::get_accessible_post_types() as $post_type ) {

			if ( false === GlobalHelper::get_settings( 'titles.pt_' . $post_type . '_link_suggestions' ) ) {
				continue;
			}

			$allowed_post_types[] = $post_type;
		}

		// Early Bail!
		if ( empty( $allowed_post_types ) ) {
			return;
		}

		$cmb = new_cmb2_box( array(
			'id'           => $this->metabox_id . '_link_suggestions',
			'title'        => esc_html__( 'Link Suggestions', 'rank-math' ),
			'object_types' => $allowed_post_types,
			'context'      => 'side',
			'priority'     => 'default',
		) );

		$cmb->add_field( array(
			'id'   => $this->metabox_id . '_link_suggestions_tooltip',
			'type' => 'raw',
			'content' => '<div id="rank-math-link-suggestions-tooltip" class="hidden">' . Admin_Helper::get_tooltip( esc_html__( 'Click on the button to copy URL or insert link in content. You can also drag and drop links in the post content.', 'rank-math' ) ) . '</div>',
		) );

		$cmb->add_field( array(
			'id'        => 'rank_math_social_tabs',
			'type'      => 'raw',
			'file'      => rank_math()->includes_dir() . 'metaboxes/link-suggestions.php',
			'not_found' => '<em><small>' . esc_html__( 'We can\'t show any link suggestions for this post. Try selecting categories and tags for this post, and mark other posts as Pillar Content to make them show up here.', 'rank-math' ) . '</small></em>',
		) );

		CMB2::pre_init( $cmb );
	}

	/**
	 * Output the WordPress editor.
	 *
	 * @param object $term Current taxonomy term object.
	 */
	public function category_description_editor( $term ) {
		?>
		<tr class="form-field term-description-wrap rank-math-term-description-wrap">
			<th scope="row"><label for="description"><?php esc_html_e( 'Description', 'rank-math' ); ?></label></th>
			<td>
				<?php
				wp_editor( html_entity_decode( $term->description, ENT_QUOTES, 'UTF-8' ), 'rank_math_description', array(
					'textarea_name' => 'description',
					'textarea_rows' => 5,
					'quicktags'     => false,
				) );
				?>
			</td>
			<script>
				// Remove the non-html field
				jQuery('textarea#description').closest('.form-field').remove();
			</script>
		</tr>
		<?php
	}

	/**
	 * Save post meta handler.
	 *
	 * @param  CMB2 $cmb CMB2 metabox object.
	 */
	public function save_meta( $cmb ) {
		/**
		 * Hook into save handler for main metabox.
		 *
		 * @param CMB2 $cmb CMB2 object.
		 */
		$this->do_action( 'metabox/process_fields', $cmb );
	}

	/**
	 * Invalidate facebook object cache for the post.
	 *
	 * @param string     $field_id The current field id paramater.
	 * @param bool       $updated  Whether the metadata update action occurred.
	 * @param string     $action   Action performed. Could be "repeatable", "updated", or "removed".
	 * @param CMB2_Field $field    This field object.
	 */
	public function invalidate_facebook_object_cache( $field_id, $updated, $action, $field ) {

		// Early Bail!
		if ( ! in_array( $field_id, array( 'rank_math_facebook_title', 'rank_math_facebook_image', 'rank_math_facebook_description' ) ) || ! $updated ) {
			return;
		}

		$app_id = GlobalHelper::get_settings( 'titles.facebook_app_id' );
		$secret = GlobalHelper::get_settings( 'titles.facebook_secret' );

		// Early Bail!
		if ( ! $app_id || ! $secret ) {
			return;
		}

		wp_remote_post( 'https://graph.facebook.com/', array(
			'body' => array(
				'id'           => get_permalink( $field->object_id() ),
				'scrape'       => true,
				'access_token' => $app_id . '|' . $secret,
			),
		) );
	}

	/**
	 * Get object types to register metabox to
	 *
	 * @return array
	 */
	private function get_object_types() {
		$taxonomies   = GlobalHelper::get_allowed_taxonomies();
		$object_types = GlobalHelper::get_allowed_post_types();

		if ( is_array( $taxonomies ) && ! empty( $taxonomies ) ) {
			$object_types[] = 'term';
			$this->description_field_editor();
			remove_filter( 'pre_term_description', 'wp_filter_kses' );
			remove_filter( 'term_description', 'wp_kses_data' );
		}

		if ( $this->is_user_metabox() ) {
			$object_types[] = 'user';
		}

		return $object_types;
	}

	/**
	 * Get metabox priority
	 *
	 * @return string
	 */
	private function get_priority() {
		$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : ( isset( $_GET['post'] ) ? get_post_type( $_GET['post'] ) : '' );

		$priority = 'product' === $post_type ? 'default' : 'high';

		return $this->do_filter( 'metabox/priority', $priority );
	}

	/**
	 * Adds custom category description editor.
	 *
	 * @return {void}
	 */
	private function description_field_editor() {
		$taxonomy        = filter_input( INPUT_GET, 'taxonomy', FILTER_DEFAULT, array( 'options' => array( 'default' => '' ) ) );
		$taxonomy_object = get_taxonomy( $taxonomy );
		if ( empty( $taxonomy_object ) || empty( $taxonomy_object->public ) ) {
			return;
		}

		if ( ! GlobalHelper::get_settings( 'titles.tax_' . $taxonomy . '_add_meta_box' ) ) {
			return;
		}
		$this->action( "{$taxonomy}_edit_form_fields", 'category_description_editor', 1 );
	}

	/**
	 * Can add metabox
	 *
	 * @return bool
	 */
	private function can_add_metabox() {
		return ! GlobalHelper::has_cap( 'onpage_general' ) &&
			! GlobalHelper::has_cap( 'onpage_advanced' ) &&
			! GlobalHelper::has_cap( 'onpage_snippet' ) &&
			! GlobalHelper::has_cap( 'onpage_social' );
	}

	/**
	 * Is user metabox enabled.
	 *
	 * @return bool
	 */
	private function is_user_metabox() {
		return ( false === GlobalHelper::get_settings( 'titles.disable_author_archives' ) && GlobalHelper::get_settings( 'titles.author_add_meta_box' ) );
	}

	/**
	 * Get tabs.
	 *
	 * @return array
	 */
	private function get_tabs() {

		$tabs = array(
			'general'  => array(
				'icon'       => 'dashicons dashicons-admin-generic',
				'title'      => esc_html__( 'General', 'rank-math' ),
				'desc'       => esc_html__( 'This tab contains general options.', 'rank-math' ),
				'file'       => rank_math()->includes_dir() . 'metaboxes/general.php',
				'capability' => 'onpage_general',
			),
			'advanced' => array(
				'icon'       => 'dashicons dashicons-admin-tools',
				'title'      => esc_html__( 'Advanced', 'rank-math' ),
				'desc'       => esc_html__( 'This tab contains advance options.', 'rank-math' ),
				'file'       => rank_math()->includes_dir() . 'metaboxes/advanced.php',
				'capability' => 'onpage_advanced',
			),
			'social'   => array(
				'icon'       => 'dashicons dashicons-share',
				'title'      => esc_html__( 'Social', 'rank-math' ),
				'desc'       => esc_html__( 'This tab contains social options.', 'rank-math' ),
				'file'       => rank_math()->includes_dir() . 'metaboxes/social.php',
				'capability' => 'onpage_social',
			),
		);

		/**
		 * Allow developers to add new tabs into main metabox.
		 *
		 * @param array $tabs Array of tabs.
		 */
		return $this->do_filter( 'metabox/tabs', $tabs );
	}

	/**
	 * Assessor data
	 */
	private function assessor() {
		$data = array(
			'powerWords'       => $this->power_words(),
			'hasTOCPlugin'     => $this->has_toc_plugin(),
			'mtsConnected'     => GlobalHelper::is_mythemeshop_connected(),
			'tocKbLink'        => KB::get( 'toc' ),
			'sentimentKbLink'  => KB::get( 'sentiments' ),
			'focusKeywordLink' => admin_url( 'edit.php?focus_keyword=%focus_keyword%&post_type=%post_type%' ),
		);

		GlobalHelper::add_json( 'assessor', $data );
		GlobalHelper::add_json( 'isUserRegistered', GlobalHelper::is_mythemeshop_connected() );
	}

	/**
	 * Return power words
	 *
	 * @return array
	 */
	private function power_words() {
		$words = include_once rank_math()->plugin_dir() . 'assets/vendor/powerwords.php';
		return $this->do_filter( 'metabox/power_words', $words );
	}

	/**
	 * Check if any TOC plugin detected
	 *
	 * @return bool
	 */
	private function has_toc_plugin() {
		$plugins_found  = array();
		$active_plugins = get_option( 'active_plugins' );
		$toc_plugins    = array(
			'cm-table-of-content/cm-table-of-content.php' => 'CM Table Of Contents',
			'easy-table-of-contents/easy-table-of-contents.php' => 'Easy Table of Contents',
			'fx-toc/fx-toc.php'                           => 'f(x) TOC',
			'hm-content-toc/hm-content-toc.php'           => 'HM Content TOC',
			'shortcodes-ultimate/shortcodes-ultimate.php' => 'Shortcodes Ultimate',
			'bainternet-simple-toc/simple-toc.php'        => 'Simple TOC',
			'content-table/content-table.php'             => 'Table of content',
			'table-of-contents-plus/toc.php'              => 'Table of Contents Plus',
			'wp-shortcode/wp-shortcode.php'               => 'WP Shortcode by MyThemeShop',
			'wp-shortcode-pro/wp-shortcode-pro.php'       => 'WP Shortcode Pro by MyThemeShop',
			'thrive-visual-editor/thrive-visual-editor.php' => 'Thrive Architect',
			'fixed-toc/fixed-toc.php'                     => 'Fixed TOC',
		);

		foreach ( $toc_plugins as $plugin_slug => $plugin_name ) {
			if ( in_array( $plugin_slug, $active_plugins ) !== false ) {
				$plugins_found[ $plugin_slug ] = $plugin_name;
			}
		}

		return empty( $plugins_found ) ? false : $plugins_found;
	}
}
