<?php
/**
 * The Search Console Sitemaps
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Search_Console;

use RankMath\Helper;
use MyThemeShop\Helpers\Str;
use RankMath\Traits\Hooker;

defined( 'ABSPATH' ) || exit;

/**
 * Sitemaps class.
 */
class Sitemaps {

	use Hooker;

	/**
	 * Hold search console api client.
	 *
	 * @var Client
	 */
	private $client;

	/**
	 * The Constructor.
	 *
	 * @param Client $client Client object.
	 */
	public function __construct( $client ) {
		$this->client = $client;

		$this->action( 'admin_init', 'admin_init' );
	}

	/**
	 * Admin Initialize.
	 */
	public function admin_init() {
		if ( ! empty( $_GET['refresh_sitemaps'] ) ) {
			check_admin_referer( 'rank_math_refresh_sitemaps', 'security' );

			if ( $this->sync_sitemaps() ) {
				Helper::add_notification( esc_html__( 'Sitemaps list refreshed.', 'rank-math' ), [ 'type' => 'success' ] );
			}
		}
	}

	/**
	 * Display data table.
	 */
	public function display_table() {
		echo '<form method="post">';

		$this->table = new Sitemaps_List;
		$this->table->prepare_items();
		$this->table->get_refresh_button();
		$this->table->display();

		echo '</form>';
	}

	/**
	 * Get sitemaps from api.
	 *
	 * @param  boolean $with_index With index data.
	 * @param  boolean $force      Purge cache and fetch new data.
	 * @return array
	 */
	public function get_sitemaps( $with_index = false, $force = false ) {
		return $this->client->fetch_sitemaps( $with_index, $force );
	}

	/**
	 * Sync sitemaps with google search console.
	 */
	private function sync_sitemaps() {

		if ( $this->selected_site_is_domain_property() ) {
			return false;
		}

		if ( ! $this->check_selected_site() ) {
			return false;
		}

		$remote_sitemaps = $this->get_sitemaps();

		$delete_sitemaps  = array();
		$sitemaps_in_list = false;
		$local_sitemap    = trailingslashit( $this->client->profile ) . 'sitemap_index.xml';

		foreach ( $remote_sitemaps as $sitemap ) {
			if ( $sitemap['path'] === $local_sitemap ) {
				$sitemaps_in_list = true;
			} else {
				$delete_sitemaps[] = $sitemap['path'];
			}
		}

		// Submit it.
		if ( ! $sitemaps_in_list ) {
			$query = $this->client->submit_sitemap( $local_sitemap );
		}

		// Delete it.
		if ( ! empty( $delete_sitemaps ) ) {
			foreach ( $delete_sitemaps as $sitemap ) {
				$query = $this->client->delete_sitemap( $sitemap );
			}
		}
	}

	/**
	 * Check if selected profile same as site url.
	 *
	 * @return boolean
	 */
	private function check_selected_site() {

		if ( ! Helper::get_module( 'sitemap' ) || empty( $this->client->profile ) ) {
			return false;
		}

		// Normalize URLs.
		$this_site     = trailingslashit( site_url( '', 'http' ) );
		$selected_site = trailingslashit( str_replace( 'https://', 'http://', $this->client->profile ) );

		// Check if site URL matches.
		if ( $this_site !== $selected_site ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if selected profile is a Domain Property.
	 *
	 * @return boolean
	 */
	public function selected_site_is_domain_property() {

		if ( ! Helper::get_module( 'sitemap' ) || empty( $this->client->profile ) ) {
			return false;
		}

		if ( Str::starts_with( 'sc-domain:', $this->client->profile ) ) {
			return true;
		}

		return false;
	}
}
