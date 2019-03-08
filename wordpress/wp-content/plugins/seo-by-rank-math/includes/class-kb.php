<?php
/**
 * Knowledgebase links.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Core
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath;

use MyThemeShop\Helpers\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * KB class.
 */
class KB {

	/**
	 * Hold links.
	 *
	 * @var array
	 */
	private $links = array();

	/**
	 * Echo the link.
	 *
	 * @param string $id Id of the link to get.
	 */
	public static function the( $id ) {
		echo self::get( $id );
	}

	/**
	 * Return the link.
	 *
	 * @param  string $id Id of the link to get.
	 * @return string
	 */
	public static function get( $id ) {
		static $manager = null;

		if ( null === $manager ) {
			$manager = new self;
			$manager->register();
		}

		return isset( $manager->links[ $id ] ) ? $manager->links[ $id ] : '#';
	}

	/**
	 * Register links.
	 */
	private function register() {
		$links       = $this->get_links();
		$is_tracking = rank_math()->settings->get( 'general.usage_tracking' );

		foreach ( $links as $id => $link ) {

			// If not array.
			if ( ! is_array( $link ) ) {
				$this->links[ $id ] = $link;
				continue;
			}

			$this->links[ $id ] = $is_tracking ? $link[0] : $link[1];
		}
	}

	/**
	 * Get links.
	 *
	 * @return array
	 */
	private function get_links() {
		// phpcs:disable
		return array(
			'seo-suite' => array(
				'https://s.rankmath.com/home',
				'https://s.rankmath.com/home',
			),
			'logo' => array(
				'https://s.rankmath.com/suite',
				'https://rankmath.com/wordpress/plugin/seo-suite/',
			),
			'amp-plugin' => array(
				'https://s.rankmath.com/ampwp',
				'https://wordpress.org/plugins/amp/?utm_campaign=RankMath'
			),
			'amp-wp' => array(
				'https://s.rankmath.com/ampforwp',
				'https://wordpress.org/plugins/accelerated-mobile-pages/?utm_campaign=Rank+Math'
			),
			'amp-ninja' => array(
				'https://s.rankmath.com/ampninja',
				'https://codecanyon.net/item/wp-amp-ninja-accelerated-mobile-pages-for-wordpress/17626811?utm_campaign=Rank+Math'
			),
			'amp-weeblramp' => array(
				'https://s.rankmath.com/amponwp',
				'https://wordpress.org/plugins/weeblramp/?utm_campaign=Rank+Math'
			),
			'amp-woocommerce' => array(
				'https://s.rankmath.com/ampwc',
				'https://ampforwp.com/woocommerce/?utm_campaign=Rank+Math'
			),
			'wp-amp' => array(
				'https://s.rankmath.com/wpampcc',
				'https://codecanyon.net/item/wp-amp-accelerated-mobile-pages-for-wordpress-and-woocommerce/16278608?utm_campaign=Rank+Math'
			),
			'rm-privacy' => array(
				'https://rankmath.com/privacy-policy/',
				'https://rankmath.com/privacy-policy/'
			),
			'free-account' => array(
				'https://s.rankmath.com/signup',
				'https://s.rankmath.com/signup'
			),
			'seo-import' => array(
				'https://s.rankmath.com/import',
				'https://rankmath.com/kb/how-to-setup/#Import_Data'
			),
			'404-monitor' => array(
				'https://s.rankmath.com/404-errors',
				'https://rankmath.com/kb/monitor-404-errors/'
			),
			'redirections' => array(
				'https://s.rankmath.com/redirections',
				'https://rankmath.com/kb/setting-up-redirections/'
			),
			'local-seo' => array(
				'https://s.rankmath.com/localseohelp',
				'https://rankmath.com/kb/how-to-setup/#local_business_setup'
			),
			'rm-support' => array(
				'https://s.rankmath.com/support',
				'https://support.rankmath.com/'
			),
			'rm-requirements' => array(
				'https://s.rankmath.com/requirements',
				'https://rankmath.com/kb/requirements/'
			),
			'rm-kb' => array(
				'https://s.rankmath.com/documentation',
				'https://rankmath.com/kb/wordpress/seo-suite/'
			),
			'seo-tweaks' => array(
				'https://s.rankmath.com/optimization',
				'https://rankmath.com/kb/how-to-setup/#Optimization'
			),
			'review-rm' => array(
				'https://s.rankmath.com/review',
				'https://wordpress.org/support/plugin/seo-by-rank-math/reviews/?filter=5#new-post'
			),
			'score-100' => array(
				'https://s.rankmath.com/score-100',
				'https://rankmath.com/kb/score-100-in-tests/'
			),
			'fb-group' => array(
				'https://s.rankmath.com/fbgroup',
				'https://www.facebook.com/groups/rankmathseopluginwordpress/'
			),
			'rm-kb' => array(
				'https://s.rankmath.com/documentation',
				'https://rankmath.com/kb/wordpress/seo-suite/'
			),
			'wp-error-fixes' => array(
				'https://s.rankmath.com/wp-errors-fixes',
				'https://mythemeshop.com/wordpress-errors-fixes/'
			),
			'mts-forum' => array(
				'https://s.rankmath.com/support',
				'https://support.rankmath.com/'
			),
			'configure-sitemaps' => array(
				'https://s.rankmath.com/sitemaps',
				'https://rankmath.com/kb/configure-sitemaps/'
			),
			'your-site' => array(
				'https://s.rankmath.com/setup',
				'https://rankmath.com/kb/how-to-setup/'
			),
			'search-console' => array(
				'https://s.rankmath.com/setup-gsc',
				'https://rankmath.com/kb/how-to-setup/#Google_Search_Console'
			),
			'remove-category-base' => array(
				'https://s.rankmath.com/stripbase',
				'https://rankmath.com/kb/how-to-setup/#strip-category-base'
			),
			'tw-link' => array(
				'https://s.rankmath.com/twitter',
				'https://s.rankmath.com/twitter'
			),
			'fb-link' => array(
				'https://s.rankmath.com/suite-free',
				'https://s.rankmath.com/suite-free'
			),
			'article' => array(
				'https://s.rankmath.com/google-schema-article',
				'https://developers.google.com/search/docs/data-types/article/?utm_campaign=Rank+Math'
			),
			'link-settings' => array(
				'https://s.rankmath.com/generalsettings',
				'https://rankmath.com/kb/general-settings/#links'
			),
			'image-settings' => array(
				'https://s.rankmath.com/imagesettings',
				'https://rankmath.com/kb/general-settings/#images'
			),
			'breadcrumbs' => array(
				'https://s.rankmath.com/breadcrumbs',
				'https://rankmath.com/kb/general-settings/#breadcrumbs'
			),
			'webmaster-tools' => array(
				'https://s.rankmath.com/webtools',
				'https://rankmath.com/kb/general-settings/#webmaster-tools'
			),
			'edit-robotstxt' => array(
				'https://s.rankmath.com/robotstxt',
				'https://rankmath.com/kb/general-settings/#edit-robotstxt'
			),
			'edit-htaccess' => array(
				'https://s.rankmath.com/htaccess',
				'https://rankmath.com/kb/general-settings/#edit-htaccess'
			),
			'404-monitor-settings' => array(
				'https://s.rankmath.com/404monitor',
				'https://rankmath.com/kb/general-settings/#404-monitor'
			),
			'fix-404' => array(
				'https://s.rankmath.com/404errors',
				'https://rankmath.com/kb/fix-404-errors/'
			),
			'redirections-settings' => array(
				'https://s.rankmath.com/redirectionskb',
				'https://rankmath.com/kb/general-settings/#redirections'
			),
			'search-console-settings' => array(
				'https://s.rankmath.com/searchconsole',
				'https://rankmath.com/kb/general-settings/#search-console'
			),
			'other-settings' => array(
				'https://s.rankmath.com/othersettings',
				'https://rankmath.com/kb/general-settings/#others'
			),
			'titles-meta' => array(
				'https://s.rankmath.com/titlesandmeta',
				'https://rankmath.com/kb/titles-and-meta/'
			),
			'local-seo-settings' => array(
				'https://s.rankmath.com/localseo',
				'https://rankmath.com/kb/titles-and-meta/#local-seo'
			),
			'social-meta-settings' => array(
				'https://s.rankmath.com/socialmeta',
				'https://rankmath.com/kb/titles-and-meta/#social-meta'
			),
			'homepage-settings' => array(
				'https://s.rankmath.com/hometitle',
				'https://rankmath.com/kb/titles-and-meta/#homepage'
			),
			'author-settings' => array(
				'https://s.rankmath.com/authortitle',
				'https://rankmath.com/kb/titles-and-meta/#authors'
			),
			'misc-settings' => array(
				'https://s.rankmath.com/miscsettings',
				'https://rankmath.com/kb/titles-and-meta/#misc-pages'
			),
			'post-settings' => array(
				'https://s.rankmath.com/posttitles',
				'https://rankmath.com/kb/titles-and-meta/#Posts'
			),
			'page-settings' => array(
				'https://s.rankmath.com/pagetitles',
				'https://rankmath.com/kb/titles-and-meta/#pages'
			),
			'media-settings' => array(
				'https://s.rankmath.com/mediatitles',
				'https://rankmath.com/kb/titles-and-meta/#media'
			),
			'product-settings' => array(
				'https://s.rankmath.com/wcproduct',
				'https://rankmath.com/kb/titles-and-meta/#products'
			),
			'category-settings' => array(
				'https://s.rankmath.com/categorytitles',
				'https://rankmath.com/kb/titles-and-meta/#categories'
			),
			'tag-settings' => array(
				'https://s.rankmath.com/tagtitle',
				'https://rankmath.com/kb/titles-and-meta/#tags'
			),
			'product-categories-settings' => array(
				'https://s.rankmath.com/productmeta',
				'https://rankmath.com/kb/titles-and-meta/#product-categories'
			),
			'product-tags-settings' => array(
				'https://s.rankmath.com/producttags',
				'https://rankmath.com/kb/titles-and-meta/#product-tags'
			),
			'sitemap-general' => array(
				'https://s.rankmath.com/sitemapgeneral',
				'https://rankmath.com/kb/configure-sitemaps/#general'
			),
			'sitemap-posts' => array(
				'https://s.rankmath.com/sitemappost',
				'https://rankmath.com/kb/configure-sitemaps/#posts'
			),
			'sitemap-pages' => array(
				'https://s.rankmath.com/pagessitemap',
				'https://rankmath.com/kb/configure-sitemaps/#pages'
			),
			'sitemap-media' => array(
				'https://s.rankmath.com/mediasitemap',
				'https://rankmath.com/kb/configure-sitemaps/#media'
			),
			'sitemap-product' => array(
				'https://s.rankmath.com/productsitemap',
				'https://rankmath.com/kb/configure-sitemaps/#products'
			),
			'sitemap-category' => array(
				'https://s.rankmath.com/categorysitemap',
				'https://rankmath.com/kb/configure-sitemaps/#categories'
			),
			'sitemap-tag' => array(
				'https://s.rankmath.com/tagsitemap',
				'https://rankmath.com/kb/configure-sitemaps/#tags'
			),
			'sitemap-product_cat' => array(
				'https://s.rankmath.com/productcatsitemap',
				'https://rankmath.com/kb/configure-sitemaps/#product-categories'
			),
			'sitemap-product_tag' => array(
				'https://s.rankmath.com/producttagsitemap',
				'https://rankmath.com/kb/configure-sitemaps/#product-tags'
			),
			'role-manager' => array(
				'https://s.rankmath.com/rolemanager',
				'https://rankmath.com/kb/role-manager/'
			),
			'search-console-kb' => array(
				'https://s.rankmath.com/gsc',
				'https://rankmath.com/kb/search-console/'
			),
			'import-export-settings' => array(
				'https://s.rankmath.com/importexport',
				'https://rankmath.com/kb/import-export-settings/'
			),
			'how-to-setup' => array(
				'https://s.rankmath.com/setuprm',
				'https://rankmath.com/kb/how-to-setup/'
			),
			'rich-snippets' => array(
				'https://s.rankmath.com/richsnippets',
				'https://rankmath.com/kb/rich-snippets/'
			),
			'seo-analysis' => array(
				'https://s.rankmath.com/seoanalysis',
				'https://rankmath.com/kb/seo-analysis/'
			),
			'sentiments' => array(
				'https://s.rankmath.com/sentiments',
				'https://monkeylearn.com/sentiment-analysis/?utm_campaign=Rank+Math'
			),
			'toc' => array(
				'https://s.rankmath.com/tockb',
				'https://rankmath.com/kb/score-100-in-tests/#table-of-contents'
			),
			'content-length' => array(
				'https://s.rankmath.com/100contentlength',
				'https://rankmath.com/kb/score-100-in-tests/#content-length'
			),
			'social-tab' => array(
				'https://s.rankmath.com/socialtab',
				'https://rankmath.com/kb/meta-box-social-tab/'
			),
		);
		// phpcs:enable
	}
}
