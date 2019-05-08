<?php
/**
 * The <head> tag.
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Frontend
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Frontend;

use RankMath\Post;
use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Sitemap\Router;

defined( 'ABSPATH' ) || exit;

/**
 * Head class.
 */
class Head {

	use Hooker;

	/**
	 * Hold generate instance.
	 *
	 * @var Generate
	 */
	public $generate = null;

	/**
	 * The Constructor.
	 */
	public function __construct() {

		$this->action( 'wp_head', 'head', 1 );

		if ( Helper::is_module_active( 'amp' ) ) {
			$this->action( 'amphtml_template_head', 'head', 1 );
			$this->action( 'weeblramp_print_meta_data', 'head', 1 );
			$this->action( 'better-amp/template/head', 'head', 99 );
			$this->action( 'amp_post_template_head', 'head', 9 );
			remove_action( 'better-amp/template/head', 'better_amp_print_rel_canonical' );
		}

		$this->action( 'wp_head', 'front_page_specific_init', 0 );
		$this->filter( 'language_attributes', 'search_results_schema' );

		// The head function here calls action rank_math/head, to which we hook all our functionality.
		$this->action( 'rank_math/head', 'metadesc', 6 );
		$this->action( 'rank_math/head', 'robots', 10 );
		$this->action( 'rank_math/head', 'canonical', 20 );
		$this->action( 'rank_math/head', 'adjacent_rel_links', 21 );

		$this->filter( 'wp_title', 'title', 15 );
		$this->filter( 'thematic_doctitle', 'title', 15 );
		$this->filter( 'pre_get_document_title', 'title', 15 );

		// Code to move title inside the Rank Math's meta.
		remove_action( 'wp_head', '_wp_render_title_tag', 1 );
		add_action( 'rank_math/head', '_wp_render_title_tag', 1 );

		// Initalize generate now.
		$this->generate = new Generate;
		add_action( 'wp', array( $this->generate, 'generate' ) );

		// Force Rewrite title.
		if ( Helper::get_settings( 'titles.rewrite_title' ) && ! current_theme_supports( 'title-tag' ) ) {
			ob_start();
			add_action( 'wp_head', array( $this, 'rewrite_title' ), 9999 );
		}
	}

	/**
	 * Initialize the functions that only need to run on the frontpage.
	 */
	public function front_page_specific_init() {
		if ( ! is_front_page() ) {
			return;
		}

		$this->action( 'rank_math/head', 'webmaster_tools_authentication', 90 );
	}

	/**
	 * Output Webmaster Tools authentication strings.
	 */
	public function webmaster_tools_authentication() {
		$tools = array(
			'google_verify'    => 'google-site-verification',
			'bing_verify'      => 'msvalidate.01',
			'baidu_verify'     => 'baidu-site-verification',
			'alexa_verify'     => 'alexaVerifyID',
			'yandex_verify'    => 'yandex-verification',
			'pinterest_verify' => 'p:domain_verify',
			'norton_verify'    => 'norton-safeweb-site-verification',
		);

		foreach ( $tools as $id => $name ) {
			$content = trim( Helper::get_settings( "general.{$id}" ) );
			if ( empty( $content ) ) {
				continue;
			}

			printf( '<meta name="%1$s" content="%2$s">' . "\n", esc_attr( $name ), esc_attr( $content ) );
		}
	}

	/**
	 * Add Search Result Page schema.
	 *
	 * @param  string $output A space-separated list of language attributes.
	 * @return string
	 */
	public function search_results_schema( $output ) {
		if ( ! is_search() ) {
			return $output;
		}

		return preg_replace( '/itemtype="([^"]+)"/', 'itemtype="https://schema.org/SearchResultsPage', $output );
	}

	/**
	 * Main wrapper function attached to wp_head.
	 * This combines all the output on the frontend of the plugin.
	 */
	public function head() {
		global $wp_query;

		$old_wp_query = null;
		if ( ! $wp_query->is_main_query() ) {
			$old_wp_query = $wp_query;
			wp_reset_query();
		}

		$this->credits();

		// Remove actions that we will handle through our rank_math/head call, and probably change the output of.
		remove_action( 'wp_head', 'rel_canonical' );
		remove_action( 'wp_head', 'index_rel_link' );
		remove_action( 'wp_head', 'start_post_rel_link' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
		remove_action( 'wp_head', 'noindex', 1 );

		if ( Helper::is_module_active( 'amp' ) ) {
			remove_action( 'amp_post_template_head', 'amp_post_template_add_title' );
			remove_action( 'amp_post_template_head', 'amp_post_template_add_canonical' );
		}

		/**
		 * Allow other plugins to output inside the Rank Math section of the head tag.
		 */
		$this->do_action( 'head' );

		$this->credits( true );

		if ( ! empty( $old_wp_query ) ) {
			$GLOBALS['wp_query'] = $old_wp_query;
			unset( $old_wp_query );
		}
	}

	/**
	 * Main title function.
	 *
	 * @param  string $title Title that might have already been set.
	 * @return string
	 */
	public function title( $title ) {
		if ( is_feed() ) {
			return $title;
		}

		$generated = $this->generate->get( 'title' );
		$generated = is_string( $generated ) && '' !== $generated ? $generated : $title;
		return convert_smilies( $generated );
	}

	/**
	 * Outputs the meta description element or returns the description text.
	 *
	 * @param  bool $echo Echo or return output flag.
	 * @return string
	 */
	public function metadesc( $echo = true ) {
		if ( ! $echo ) {
			return $this->generate->get( 'desc' );
		}

		$generated = $this->generate->get( 'desc' );
		if ( is_string( $generated ) && '' !== $generated ) {
			echo '<meta name="description" content="', $generated, '"/>', "\n";
		} elseif ( Helper::has_cap( 'general' ) && is_singular() ) {
			echo '<!-- ', \html_entity_decode( esc_html__( 'Admin only notice: this page has no meta description set. Please edit the page to add one, or setup a template in Rank Math -> Titles &amp; Metas.', 'rank-math' ) ), ' -->', "\n";
		}
	}

	/**
	 * Output the meta robots value.
	 */
	public function robots() {
		$robots    = $this->generate->get( 'robots' );
		$robotsstr = join( ',', $robots );
		if ( is_string( $robotsstr ) && $robotsstr ) {
			echo '<meta name="robots" content="', esc_attr( $robotsstr ), '"/>', "\n";
		}

		// If a page has a noindex, it should _not_ have a canonical, as these are opposing indexing directives.
		if ( 'noindex' === $robots['index'] ) {
			$this->remove_action( 'rank_math/head', 'canonical', 20 );
			$this->remove_action( 'rank_math/head', 'adjacent_rel_links', 21 );
		}
	}

	/**
	 * This function normally outputs the canonical but is also used in other places to retrieve
	 * the canonical URL for the current page.
	 *
	 * @param  bool $echo        Whether or not to output the canonical element.
	 * @param  bool $un_paged    Whether or not to return the canonical with or without pagination added to the URL.
	 * @param  bool $no_override Whether or not to return a manually overridden canonical.
	 * @return string  $canonical
	 */
	public function canonical( $echo = true, $un_paged = false, $no_override = false ) {
		$canonical = $this->generate->get( 'canonical' );
		if ( '' === $canonical ) {
			$this->generate->generate_canonical();
			$canonical = $this->generate->get( 'canonical' );
		}

		if ( $un_paged ) {
			$canonical = $this->generate->get( 'canonical_unpaged' );
		} elseif ( $no_override ) {
			$canonical = $this->generate->get( 'canonical_no_override' );
		}

		if ( is_string( $canonical ) && '' !== $canonical && $echo ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical, null, 'other' ) . '" />' . "\n";
		}

		return $canonical;
	}

	/**
	 * Adds 'prev' and 'next' links to archives.
	 *
	 * @link http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
	 */
	public function adjacent_rel_links() {
		// Don't do this for Genesis, as the way Genesis handles homepage functionality is different and causes issues sometimes.
		/**
		 * Allows devs to allow echoing rel="next" / rel="prev" by Rank Math on Genesis installs.
		 *
		 * @param bool $unsigned Whether or not to rel=next / rel=prev .
		 */
		if ( is_home() && function_exists( 'genesis' ) && false === $this->do_filter( 'frontend/genesis/force_adjacent_rel_home', false ) ) {
			return;
		}

		/**
		 * Allows disabling of Rank Math adjacent links if this is being handled by other code.
		 *
		 * @param bool $links_generated Indicates if other code has handled adjacent links.
		 */
		if ( true === $this->do_filter( 'frontend/disable_adjacent_rel_links', false ) ) {
			return;
		}

		if ( is_singular() ) {
			$this->adjacent_rel_links_single();
			return;
		}
		$this->adjacent_rel_links_archive();
	}

	/**
	 * Output the rel next/prev links for a single post / page.
	 *
	 * @return void
	 */
	private function adjacent_rel_links_single() {
		$num_pages = 1;

		$queried_object = get_queried_object();
		if ( ! empty( $queried_object ) ) {
			$num_pages = substr_count( $queried_object->post_content, '<!--nextpage-->' ) + 1;
		}

		if ( 1 === $num_pages ) {
			return;
		}

		$page = max( 1, (int) get_query_var( 'page' ) );
		$url  = get_permalink( get_queried_object_id() );

		if ( $page > 1 ) {
			$this->adjacent_rel_link( 'prev', $url, $page - 1, 'page' );
		}

		if ( $page < $num_pages ) {
			$this->adjacent_rel_link( 'next', $url, $page + 1, 'page' );
		}
	}

	/**
	 * Output the rel next/prev links for an archive page.
	 */
	private function adjacent_rel_links_archive() {
		$url = $this->canonical( false, true, true );
		if ( ! is_string( $url ) || '' === $url ) {
			return;
		}

		$paged = max( 1, (int) get_query_var( 'paged' ) );
		if ( 2 === $paged ) {
			$this->adjacent_rel_link( 'prev', $url, $paged - 1 );
		}

		// Make sure to use index.php when needed, done after paged == 2 check so the prev links to homepage will not have index.php erroneously.
		if ( is_front_page() ) {
			$url = Router::get_base_url( '' );
		}

		if ( $paged > 2 ) {
			$this->adjacent_rel_link( 'prev', $url, $paged - 1 );
		}

		if ( $paged < $GLOBALS['wp_query']->max_num_pages ) {
			$this->adjacent_rel_link( 'next', $url, $paged + 1 );
		}
	}

	/**
	 * Get adjacent pages link for archives.
	 *
	 * @param string $rel       Link relationship, prev or next.
	 * @param string $url       The un-paginated URL of the current archive.
	 * @param string $page      The page number to add on to $url for the $link tag.
	 * @param string $query_arg Optional. The argument to use to set for the page to load.
	 */
	private function adjacent_rel_link( $rel, $url, $page, $query_arg = 'paged' ) {
		global $wp_rewrite;

		if ( $page > 1 ) {
			$url = ! $wp_rewrite->using_permalinks() ? add_query_arg( $query_arg, $page, $url ) : user_trailingslashit( trailingslashit( $url ) . $this->get_pagination_base() . $page );
		}

		/**
		 * Allow changing link rel output by Rank Math.
		 *
		 * @param string $link The full `<link` element.
		 */
		$link = $this->do_filter( "frontend/{$rel}_rel_link", '<link rel="' . esc_attr( $rel ) . '" href="' . esc_url( $url ) . "\" />\n" );
		if ( is_string( $link ) && '' !== $link ) {
			echo $link;
		}
	}

	/**
	 * Return the base for pagination.
	 *
	 * @return string The pagination base.
	 */
	private function get_pagination_base() {
		global $wp_rewrite;

		return ( ! is_singular() || Post::is_home_static_page() ) ? trailingslashit( $wp_rewrite->pagination_base ) : '';
	}

	/**
	 * Credits
	 *
	 * @param boolean $closing Is closing credits needed.
	 */
	private function credits( $closing = false ) {

		if ( $this->do_filter( 'frontend/remove_credit_notice', false ) ) {
			return;
		}

		if ( false === $closing ) {
			if ( ! Helper::is_whitelabel() ) {
				echo "\n<!-- " . esc_html__( 'Search Engine Optimization by Rank Math - https://s.rankmath.com/home', 'rank-math' ) . " -->\n";
			}
			return;
		}

		if ( ! Helper::is_whitelabel() ) {
			echo '<!-- /' . esc_html__( 'Rank Math WordPress SEO plugin', 'rank-math' ) . " -->\n\n";
		}
	}

	/**
	 * Used in the force rewrite functionality this retrieves the output, replaces the title with the proper SEO
	 * title and then flushes the output.
	 */
	public function rewrite_title() {
		global $wp_query;
		// Check if we're in the main query to support bad themes and plugins.
		$old_wp_query = null;
		if ( ! $wp_query->is_main_query() ) {
			$old_wp_query = $wp_query;
			wp_reset_query();
		}

		$content = ob_get_clean();
		$title   = $this->generate->get( 'title' );
		if ( empty( $title ) ) {
			echo $content;
		}

		// Find all titles, strip them out and add the new one.
		$content = preg_replace( '/<title.*?\/title>/i', '', $content );
		$content = str_replace( '<head>', '<head>' . "\n" . '<title>' . esc_html( $title ) . '</title>', $content );
		if ( ! empty( $old_wp_query ) ) {
			$GLOBALS['wp_query'] = $old_wp_query;
		}

		echo $content;
	}
}
