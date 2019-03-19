<?php

namespace App\Controllers;

use Sober\Controller\Controller;

class App extends Controller
{
    public function siteName()
    {
        return get_bloginfo('name');
    }

    public static function title()
    {
        if (is_home()) {
            if ($home = get_option('page_for_posts', true)) {
                return get_the_title($home);
            }
            return __('Latest Posts', 'sage');
        }
        if (is_archive()) {
            return get_the_archive_title();
        }
        if (is_search()) {
            return sprintf(__('Search Results for %s', 'sage'), get_search_query());
        }
        if (is_404()) {
            return __('Not Found', 'sage');
        }
        return get_the_title();
    }

    /**
     * Primary Nav Menu arguments
     * @return array
     */
    public function primarymenu()
    {
        $args = array(
            'theme_location'    => 'primary_navigation',
            'menu_class'        => 'navbar-nav',
            'add_li_class'      => 'pr-3',
            'walker'            => new \App\wp_bootstrap4_navwalker(),
        );
        return $args;
    }

    /**
     * Primary Nav Menu arguments
     * @return array
     */
    public function get_posts_temoignages()
    {
        return get_posts(array(
            'posts_per_page'   => 5,
            'offset'           => 0,
            'cat'         => '',
            'category_name'    => '',
            'orderby'          => 'date',
            'order'            => 'DESC',
            'include'          => '',
            'exclude'          => '',
            'meta_key'         => '',
            'meta_value'       => '',
            'post_type'        => 'temoignage',
            'post_mime_type'   => '',
            'post_parent'      => '',
            'author'	   => '',
            'author_name'	   => '',
            'post_status'      => 'publish',
            'suppress_filters' => true,
            'fields'           => '',
        ));
    }
}
