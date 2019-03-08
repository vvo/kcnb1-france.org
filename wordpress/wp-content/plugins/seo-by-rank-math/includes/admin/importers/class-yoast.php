<?php
/**
 * The Yoast SEO Import Class
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Admin\Importers
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Admin\Importers;

use RankMath\Helper;
use MyThemeShop\Helpers\DB;
use RankMath\Sitemap\Sitemap;
use MyThemeShop\Helpers\WordPress;
use RankMath\Redirections\Redirection;

defined( 'ABSPATH' ) || exit;

/**
 * Yoast class.
 */
class Yoast extends Plugin_Importer {

	/**
	 * The plugin name.
	 *
	 * @var string
	 */
	protected $plugin_name = 'Yoast SEO';

	/**
	 * Meta key, used in SQL LIKE clause for delete query.
	 *
	 * @var string
	 */
	protected $meta_key = '_yoast_wpseo_';

	/**
	 * Array of option keys to import and clean
	 *
	 * @var array
	 */
	protected $option_keys = [ 'wpseo', 'wpseo_%' ];

	/**
	 * Array of choices keys to import
	 *
	 * @var array
	 */
	protected $choices = [ 'settings', 'postmeta', 'termmeta', 'usermeta', 'redirections' ];

	/**
	 * Array of table names to drop while cleaning
	 *
	 * @var array
	 */
	protected $table_names = [ 'yoast_seo_links', 'yoast_seo_meta' ];

	/**
	 * Import settings of plugin.
	 *
	 * @return bool
	 */
	protected function settings() {

		$all_opts = rank_math()->settings->all_raw();
		$settings = $all_opts['general'];
		$titles   = $all_opts['titles'];
		$sitemap  = $all_opts['sitemap'];

		$yoast_main          = get_option( 'wpseo' );
		$yoast_permalink     = get_option( 'wpseo_permalinks' );
		$yoast_social        = get_option( 'wpseo_social' );
		$yoast_titles        = get_option( 'wpseo_titles' );
		$yoast_rss           = get_option( 'wpseo_rss' );
		$yoast_internallinks = get_option( 'wpseo_internallinks' );
		$yoast_sitemap       = get_option( 'wpseo_xml' );
		$yoast_local         = get_option( 'wpseo_local', false );

		if ( isset( $yoast_titles['separator'] ) ) {
			$separator_options = [
				'sc-dash'   => '-',
				'sc-ndash'  => '&ndash;',
				'sc-mdash'  => '&mdash;',
				'sc-middot' => '&middot;',
				'sc-bull'   => '&bull;',
				'sc-star'   => '*',
				'sc-smstar' => '&#8902;',
				'sc-pipe'   => '|',
				'sc-tilde'  => '~',
				'sc-laquo'  => '&laquo;',
				'sc-raquo'  => '&raquo;',
				'sc-lt'     => '&lt;',
				'sc-gt'     => '&gt;',
			];
			if ( isset( $separator_options[ $yoast_titles['separator'] ] ) ) {
				$titles['title_separator'] = $separator_options[ $yoast_titles['separator'] ];
			}
		}

		// Features.
		$modules  = [];
		$features = [
			'keyword_analysis_active' => 'seo-analysis',
			'enable_xml_sitemap'      => 'sitemap',
		];
		foreach ( $features as $feature => $module ) {
			$modules[ $module ] = 1 === intval( $yoast_main[ $feature ] ) ? 'on' : 'off';
		}
		Helper::update_modules( $modules );

		// Knowledge Graph Logo.
		if ( isset( $yoast_main['company_logo'] ) ) {
			$this->replace_image( $yoast_main['company_logo'], $titles, 'knowledgegraph_logo', 'knowledgegraph_logo_id' );
		}

		$hash = [
			'company_name'      => 'knowledgegraph_name',
			'company_or_person' => 'knowledgegraph_type',
		];
		$this->replace( $hash, $yoast_titles, $titles );

		$titles['local_seo'] = isset( $yoast_titles['company_or_person'] ) && ! empty( $yoast_titles['company_or_person'] ) ? 'on' : 'off';

		// Verification Codes.
		$hash = [
			'baiduverify'     => 'baidu_verify',
			'alexaverify'     => 'alexa_verify',
			'googleverify'    => 'google_verify',
			'msverify'        => 'bing_verify',
			'pinterestverify' => 'pinterest_verify',
			'yandexverify'    => 'yandex_verify',
		];
		$this->replace( $hash, $yoast_main, $settings );

		// Links.
		$hash = [
			'redirectattachment' => 'attachment_redirect_urls',
			'stripcategorybase'  => 'strip_category_base',
			'cleanslugs'         => 'url_strip_stopwords',
		];
		$this->replace( $hash, $yoast_permalink, $settings, 'convert_bool' );
		$this->replace( [ 'disable-author' => 'disable_author_archives' ], $yoast_titles, $titles, 'convert_bool' );
		$this->replace( [ 'disable-date' => 'disable_date_archives' ], $yoast_titles, $titles, 'convert_bool' );

		// Titles & Descriptions.
		$hash = [
			'title-home-wpseo'       => 'homepage_title',
			'metadesc-home-wpseo'    => 'homepage_description',
			'title-author-wpseo'     => 'author_archive_title',
			'metadesc-author-wpseo'  => 'author_archive_description',
			'title-archive-wpseo'    => 'date_archive_title',
			'metadesc-archive-wpseo' => 'date_archive_description',
			'title-search-wpseo'     => 'search_title',
			'title-404-wpseo'        => '404_title',
		];

		foreach ( Helper::get_accessible_post_types() as $post_type ) {
			$hash[ "title-{$post_type}" ]              = "pt_{$post_type}_title";
			$hash[ "metadesc-{$post_type}" ]           = "pt_{$post_type}_description";
			$hash[ "post_types-{$post_type}-maintax" ] = "pt_{$post_type}_primary_taxonomy";

			// Has Archive.
			$hash[ "title-ptarchive-{$post_type}" ]    = "pt_{$post_type}_archive_title";
			$hash[ "metadesc-ptarchive-{$post_type}" ] = "pt_{$post_type}_archive_description";

			// NOINDEX.
			if ( isset( $yoast_titles[ "noindex-{$post_type}" ] ) && $yoast_titles[ "noindex-{$post_type}" ] ) {
				$titles[ "pt_{$post_type}_custom_robots" ] = 'on';
				$titles[ "pt_{$post_type}_robots" ][]      = 'noindex';
				$titles[ "pt_{$post_type}_robots" ]        = array_unique( $titles[ "pt_{$post_type}_robots" ] );
				$sitemap[ "pt_{$post_type}_sitemap" ]      = 'off';
			} else {
				// Sitemap.
				$sitemap[ "pt_{$post_type}_sitemap" ] = 'on';
			}

			// Show/Hide Metabox.
			if ( isset( $yoast_titles[ "hideeditbox-{$post_type}" ] ) ) {
				$titles[ "pt_{$post_type}_add_meta_box" ] = $yoast_titles[ "hideeditbox-{$post_type}" ] ? 'off' : 'on';
			}
			if ( isset( $yoast_titles[ "display-metabox-pt-{$post_type}" ] ) ) {
				$show = $yoast_titles[ "display-metabox-pt-{$post_type}" ]; // phpcs:ignore
				$titles[ "pt_{$post_type}_add_meta_box" ] = ( ! $show || 'off' === $show ) ? 'off' : 'on';
			}
		}

		foreach ( Helper::get_accessible_taxonomies() as $taxonomy => $object ) {
			$hash[ "title-tax-{$taxonomy}" ]    = "tax_{$taxonomy}_title";
			$hash[ "metadesc-tax-{$taxonomy}" ] = "tax_{$taxonomy}_description";

			// NOINDEX.
			if ( isset( $yoast_titles[ "noindex-tax-{$taxonomy}" ] ) && $yoast_titles[ "noindex-tax-{$taxonomy}" ] ) {
				$titles[ "tax_{$taxonomy}_custom_robots" ] = 'on';
				$titles[ "tax_{$taxonomy}_robots" ][]      = 'noindex';
				$titles[ "tax_{$taxonomy}_robots" ]        = array_unique( $titles[ "tax_{$taxonomy}_robots" ] );
			} else {
				$sitemap[ "tax_{$taxonomy}_sitemap" ] = 'off';
			}

			// Show/Hide Metabox.
			if ( isset( $yoast_titles[ "hideeditbox-tax-{$taxonomy}" ] ) ) {
				$titles[ "tax_{$taxonomy}_add_meta_box" ] = $yoast_titles[ "hideeditbox-tax-{$taxonomy}" ] ? 'off' : 'on';
			}
			if ( isset( $yoast_titles[ "display-metabox-tax-{$post_type}" ] ) ) {
				$titles[ "tax_{$post_type}_add_meta_box" ] = $yoast_titles[ "display-metabox-tax-{$post_type}" ] ? 'off' : 'on';
			}

			// Sitemap.
			$key   = "taxonomies-{$taxonomy}-not_in_sitemap";
			$value = isset( $yoast_sitemap[ $key ] ) ? ! $yoast_sitemap[ $key ] : false;

			$sitemap[ "tax_{$taxonomy}_sitemap" ] = $value ? 'on' : 'off';
		}
		$this->replace( $hash, $yoast_titles, $titles, 'convert_variables' );

		// NOINDEX.
		$hash = [
			'noindex-subpages-wpseo' => 'noindex_archive_subpages',
			'noindex-author-wpseo'   => 'noindex_author_archive',
		];
		$this->replace( $hash, $yoast_titles, $titles, 'convert_bool' );

		// Social.
		$hash = [
			'facebook_site'   => 'social_url_facebook',
			'twitter_site'    => 'twitter_author_names',
			'instagram_url'   => 'social_url_instagram',
			'linkedin_url'    => 'social_url_linkedin',
			'youtube_url'     => 'social_url_youtube',
			'google_plus_url' => 'social_url_gplus',
			'pinterest_url'   => 'social_url_pinterest',
			'myspace_url'     => 'social_url_myspace',
			'fbadminapp'      => 'facebook_app_id',
		];
		$this->replace( $hash, $yoast_social, $titles );

		// OpenGraph.
		if ( isset( $yoast_social['og_default_image'] ) ) {
			$this->replace_image( $yoast_social['og_default_image'], $titles, 'open_graph_image', 'open_graph_image_id' );
		}

		if ( isset( $yoast_social['og_frontpage_image'] ) ) {
			$this->replace_image( $yoast_social['og_frontpage_image'], $titles, 'homepage_facebook_image', 'homepage_facebook_image_id' );
		}

		$hash = [
			'og_frontpage_title' => 'homepage_facebook_title',
			'og_frontpage_desc'  => 'homepage_facebook_description',
		];
		$this->replace( $hash, $yoast_social, $titles, 'convert_variables' );

		// Breadcrumbs.
		$hash = [
			'breadcrumbs-sep'           => 'breadcrumbs_separator',
			'breadcrumbs-home'          => 'breadcrumbs_home_label',
			'breadcrumbs-prefix'        => 'breadcrumbs_prefix',
			'breadcrumbs-archiveprefix' => 'breadcrumbs_archive_format',
			'breadcrumbs-searchprefix'  => 'breadcrumbs_search_format',
			'breadcrumbs-404crumb'      => 'breadcrumbs_404_label',
		];
		$this->replace( $hash, $yoast_titles, $settings );
		$this->replace( $hash, $yoast_internallinks, $settings );

		$hash = [ 'breadcrumbs-enable' => 'breadcrumbs' ];
		$this->replace( $hash, $yoast_titles, $settings, 'convert_bool' );
		$this->replace( $hash, $yoast_internallinks, $settings, 'convert_bool' );

		// RSS.
		$hash = [
			'rssbefore' => 'rss_before_content',
			'rssafter'  => 'rss_after_content',
		];
		$this->replace( $hash, $yoast_rss, $settings, 'convert_variables' );

		// Sitemap.
		if ( ! isset( $yoast_main['enable_xml_sitemap'] ) && isset( $yoast_sitemap['enablexmlsitemap'] ) ) {
			Helper::update_modules( [ 'sitemap' => 'on' ] );
		}
		$hash = [
			'entries-per-page' => 'items_per_page',
			'excluded-posts'   => 'exclude_posts',
		];
		$this->replace( $hash, $yoast_sitemap, $sitemap );

		if ( empty( $yoast_sitemap['excluded-posts'] ) ) {
			$sitemap['exclude_posts'] = '';
		}

		foreach ( WordPress::get_roles() as $role => $label ) {
			$key = "user_role-{$role}-not_in_sitemap";
			if ( isset( $yoast_sitemap[ $key ] ) && $yoast_sitemap[ $key ] ) {
				$sitemap['exclude_roles'][] = $role;
			}
		}
		if ( ! empty( $sitemap['exclude_roles'] ) ) {
			$sitemap['exclude_roles'] = array_unique( $sitemap['exclude_roles'] );
		}

		// Local SEO.
		if ( $yoast_local && is_array( $yoast_local ) ) {
			$titles['local_seo'] = 'on';

			// Address Format.
			$address_format_hash = [
				'address-state-postal'       => '{address} {locality}, {region} {postalcode}',
				'address-state-postal-comma' => '{address} {locality}, {region}, {postalcode}',
				'address-postal-city-state'  => '{address} {postalcode} {locality}, {region}',
				'address-postal'             => '{address} {locality} {postalcode}',
				'address-postal-comma'       => '{address} {locality}, {postalcode}',
				'address-city'               => '{address} {locality}',
				'postal-address'             => '{postalcode} {region} {locality} {address}',
			];

			$titles['local_address_format'] = $address_format_hash[ $yoast_local['address_format'] ];

			// Street Address.
			$address = [];
			$hash    = [
				'location_address' => 'streetAddress',
				'location_city'    => 'addressLocality',
				'location_state'   => 'addressRegion',
				'location_zipcode' => 'postalCode',
				'location_country' => 'addressCountry',
			];
			$this->replace( $hash, $yoast_local, $address );
			$titles['local_address'] = $address;

			if ( ! empty( $yoast_local['location_address_2'] ) ) {
				$titles['local_address']['streetAddress'] .= ' ' . $yoast_local['location_address_2'];
			}

			// Coordinates.
			if ( ! empty( $yoast_local['location_coords_lat'] ) && ! empty( $yoast_local['location_coords_long'] ) ) {
				$titles['geo'] = $yoast_local['location_coords_lat'] . ' ' . $yoast_local['location_coords_long'];
			}

			// Phone Numbers.
			if ( ! empty( $yoast_local['location_phone'] ) ) {
				$titles['phone_numbers'][] = [
					'type'   => 'customer support',
					'number' => $yoast_local['location_phone'],
				];

				if ( ! empty( $yoast_local['location_phone_2nd'] ) ) {
					$titles['phone_numbers'][] = [
						'type'   => 'customer support',
						'number' => $yoast_local['location_phone_2nd'],
					];
				}
			}

			// Opening Hours.
			if ( ! empty( $yoast_local['opening_hours_24h'] ) ) {
				$titles['opening_hours_format'] = isset( $yoast_local['opening_hours_24h'] ) && 'on' === $yoast_local['opening_hours_24h'] ? 'off' : 'on';
			}
		}

		Helper::update_all_settings( $settings, $titles, $sitemap );

		return true;
	}

	/**
	 * Import post meta of plugin.
	 *
	 * @return array
	 */
	protected function postmeta() {
		$this->set_pagination( $this->get_post_ids( true ) );

		$destination = 'update_post_meta';
		$post_ids    = $this->get_post_ids();

		$this->set_primary_term( $post_ids );

		$hash = [
			'_yoast_wpseo_title'                 => 'rank_math_title',
			'_yoast_wpseo_metadesc'              => 'rank_math_description',
			'_yoast_wpseo_focuskw'               => 'rank_math_focus_keyword',
			'_yoast_wpseo_canonical'             => 'rank_math_canonical_url',
			'_yoast_wpseo_opengraph-title'       => 'rank_math_facebook_title',
			'_yoast_wpseo_opengraph-description' => 'rank_math_facebook_description',
			'_yoast_wpseo_twitter-title'         => 'rank_math_twitter_title',
			'_yoast_wpseo_twitter-description'   => 'rank_math_twitter_description',
			'_yoast_wpseo_bctitle'               => 'rank_math_breadcrumb_title',
		];

		foreach ( $post_ids as $post ) {
			$post_id = $post->ID;
			$this->replace_meta( $hash, null, $post_id, 'post', 'convert_variables' );
			delete_post_meta( $post_id, 'rank_math_permalink' );

			// Facebook Image.
			$og_thumb = get_post_meta( $post_id, '_yoast_wpseo_opengraph-image', true );
			if ( ! empty( $og_thumb ) ) {
				$this->replace_image( $og_thumb, $destination, 'rank_math_facebook_image', 'rank_math_facebook_image_id', $post_id );
			}

			// Twitter Image.
			$twitter_thumb = get_post_meta( $post_id, '_yoast_wpseo_twitter-image', true );
			if ( ! empty( $twitter_thumb ) ) {
				$this->replace_image( $twitter_thumb, $destination, 'rank_math_twitter_image', 'rank_math_twitter_image_id', $post_id );
			}

			foreach ( [ 'rank_math_twitter_title', 'rank_math_twitter_description', 'rank_math_twitter_image' ] as $key ) {
				if ( ! empty( get_post_meta( $post_id, $key, true ) ) ) {
					update_post_meta( $post_id, 'rank_math_twitter_use_facebook', 'off' );
					break;
				}
			}

			// Cornerstone Content.
			$cornerstone = get_post_meta( $post_id, '_yoast_wpseo_is_cornerstone', true );
			if ( ! empty( $cornerstone ) ) {
				update_post_meta( $post_id, 'rank_math_pillar_content', 'on' );
			}

			$this->set_post_robots( $post_id );

			// Extra focus keywords.
			$extra_fks = get_post_meta( $post_id, '_yoast_wpseo_focuskeywords', true );
			if ( $extra_fks ) {
				$extra_fks = json_decode( $extra_fks, true );
				if ( ! empty( $extra_fks ) ) {
					$extra_fks = implode( ', ', array_map( [ $this, 'map_focus_keyword' ], $extra_fks ) );
					$main_fk   = get_post_meta( $post_id, 'rank_math_focus_keyword', true );
					$extra_fks = $main_fk . ', ' . $extra_fks;
					update_post_meta( $post_id, 'rank_math_focus_keyword', $extra_fks );
				}
			}
		}

		return $this->get_pagination_arg();
	}

	/**
	 * Set post robots
	 *
	 * @param int $post_id Post id.
	 */
	private function set_post_robots( $post_id ) {
		$robots_nofollow = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', true );
		$robots_noindex  = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
		$robots_advanced = (array) get_post_meta( $post_id, '_yoast_wpseo_meta-robots-adv', true );

		// If all are empty, then keep default robots.
		if ( empty( $robots_nofollow ) && empty( $robots_noindex ) && empty( $robots_advanced ) ) {
			update_post_meta( $post_id, 'rank_math_robots', [] );
			return;
		}

		$robots = (array) get_post_meta( $post_id, 'rank_math_robots', true );
		if ( $robots_nofollow ) {
			$robots[] = 'nofollow';
		}

		if ( '1' == $robots_noindex ) {
			$robots[] = 'noindex';
		}

		$robots_advanced = explode( ',', $robots_advanced[0] );
		if ( $robots_advanced ) {
			$robots = array_merge( $robots, $robots_advanced );
		}

		update_post_meta( $post_id, 'rank_math_robots', array_unique( $robots ) );
	}

	/**
	 * Return focus keyword from entry
	 *
	 * @param  array $entry Yoast focus keyword entry.
	 * @return string
	 */
	public function map_focus_keyword( $entry ) {
		return $entry['keyword'];
	}

	/**
	 * Set primary term for post
	 *
	 * @param int[] $post_ids Post ids.
	 */
	private function set_primary_term( $post_ids ) {
		$post_ids = wp_list_pluck( $post_ids, 'ID' );
		$table    = DB::query_builder( 'postmeta' );
		$results  = $table->whereLike( 'meta_key', 'wpseo_primary' )->whereIn( 'post_id', $post_ids )->get();

		foreach ( $results as $result ) {
			$key = str_replace( '_yoast_wpseo', 'rank_math', $result->meta_key );
			update_post_meta( $result->post_id, $key, $result->meta_value );
		}
	}

	/**
	 * Import term meta of plugin.
	 *
	 * @return array
	 */
	protected function termmeta() {
		$count         = 0;
		$destination   = 'update_term_meta';
		$taxonomy_meta = get_option( 'wpseo_taxonomy_meta' );

		if ( empty( $taxonomy_meta ) ) {
			return compact( 'count' );
		}
		$hash = [
			'wpseo_title'                 => 'rank_math_title',
			'wpseo_desc'                  => 'rank_math_description',
			'wpseo_metadesc'              => 'rank_math_description',
			'wpseo_focuskw'               => 'rank_math_focus_keyword',
			'wpseo_canonical'             => 'rank_math_canonical_url',
			'wpseo_opengraph-title'       => 'rank_math_facebook_title',
			'wpseo_opengraph-description' => 'rank_math_facebook_description',
			'wpseo_twitter-title'         => 'rank_math_twitter_title',
			'wpseo_twitter-description'   => 'rank_math_twitter_description',
			'wpseo_bctitle'               => 'rank_math_breadcrumb_title',
		];
		foreach ( $taxonomy_meta as $terms ) {
			foreach ( $terms as $term_id => $data ) {
				$count++;
				$this->replace_meta( $hash, $data, $term_id, 'term', 'convert_variables' );
				delete_term_meta( $term_id, 'rank_math_permalink' );

				// Facebook Image.
				if ( ! empty( $data['wpseo_opengraph-image'] ) ) {
					$this->replace_image( $data['wpseo_opengraph-image'], $destination, 'rank_math_facebook_image', 'rank_math_facebook_image_id', $term_id );
				}

				// Twitter Image.
				if ( ! empty( $data['wpseo_twitter-image'] ) ) {
					$this->replace_image( $data['wpseo_twitter-image'], $destination, 'rank_math_twitter_image', 'rank_math_twitter_image_id', $term_id );
				}

				foreach ( [ 'rank_math_twitter_title', 'rank_math_twitter_description', 'rank_math_twitter_image' ] as $key ) {
					if ( ! empty( get_term_meta( $term_id, $key, true ) ) ) {
						update_term_meta( $term_id, 'rank_math_twitter_use_facebook', 'off' );
						break;
					}
				}

				// NOINDEX.
				if ( ! empty( $data['wpseo_noindex'] ) && 'noindex' === $data['wpseo_noindex'] ) {
					$current   = get_term_meta( $term_id, 'rank_math_robots', true );
					$current[] = 'noindex';

					update_term_meta( $term_id, 'rank_math_robots', array_unique( $current ) );
				}
			}
		}

		return compact( 'count' );
	}

	/**
	 * Import user meta of plugin.
	 *
	 * @return array
	 */
	protected function usermeta() {

		$this->set_pagination( $this->get_user_ids( true ) );
		$user_ids = $this->get_user_ids();

		$hash = [
			'wpseo_title'    => 'rank_math_title',
			'wpseo_desc'     => 'rank_math_description',
			'wpseo_metadesc' => 'rank_math_description',
		];
		foreach ( $user_ids as $user ) {
			$userid = $user->ID;
			$this->replace_meta( $hash, null, $userid, 'user', 'convert_variables' );
		}

		return $this->get_pagination_arg();
	}

	/**
	 * Imports redirections data.
	 *
	 * @return array
	 */
	protected function redirections() {
		$redirections = get_option( 'wpseo-premium-redirects-base' );
		if ( ! $redirections ) {
			return false;
		}

		$count = 0;
		Helper::update_modules( [ 'redirections' => 'on' ] );
		foreach ( $redirections as $redirection ) {
			if ( ! isset( $redirection['origin'] ) || empty( $redirection['origin'] ) ) {
				continue;
			}

			$item = Redirection::from([
				'sources'     => [
					[
						'pattern'    => $redirection['origin'],
						'comparison' => isset( $redirection['format'] ) && 'regex' === $redirection['format'] ? 'regex' : 'exact',
					],
				],
				'url_to'      => isset( $redirection['url'] ) ? $redirection['url'] : '',
				'header_code' => isset( $redirection['type'] ) ? $redirection['type'] : '301',
			]);

			if ( false !== $item->save() ) {
				$count++;
			}
		}

		return compact( 'count' );
	}
}
