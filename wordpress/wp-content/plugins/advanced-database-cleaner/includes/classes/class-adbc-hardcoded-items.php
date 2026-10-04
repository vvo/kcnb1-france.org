<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Hardcoded Items.
 * 
 * This class provides methods for the hardcoded items scan process.
 */
class ADBC_Hardcoded_Items extends ADBC_Singleton {

	/**
	 * WordPress options.
	 * 
	 * @var array
	 */
	private $wp_options = [ 
		'siteurl' => '',
		'home' => '',
		'blogname' => '',
		'blogdescription' => '',
		'users_can_register' => '',
		'admin_email' => '',
		'start_of_week' => '',
		'use_balanceTags' => '',
		'use_smilies' => '',
		'require_name_email' => '',
		'comments_notify' => '',
		'posts_per_rss' => '',
		'rss_use_excerpt' => '',
		'mailserver_url' => '',
		'mailserver_login' => '',
		'mailserver_pass' => '',
		'mailserver_port' => '',
		'default_category' => '',
		'default_comment_status' => '',
		'default_ping_status' => '',
		'default_pingback_flag' => '',
		'posts_per_page' => '',
		'date_format' => '',
		'time_format' => '',
		'links_updated_date_format' => '',
		'comment_moderation' => '',
		'moderation_notify' => '',
		'permalink_structure' => '',
		'gzipcompression' => '',
		'hack_file' => '',
		'blog_charset' => '',
		'moderation_keys' => '',
		'active_plugins' => '',
		'category_base' => '',
		'ping_sites' => '',
		'advanced_edit' => '',
		'comment_max_links' => '',
		'gmt_offset' => '',
		// 1.5
		'default_email_category' => '',
		'recently_edited' => '',
		'template' => '',
		'stylesheet' => '',
		'comment_whitelist' => '',
		'blacklist_keys' => '',
		'comment_registration' => '',
		'html_type' => '',
		// 1.5.1
		'use_trackback' => '',
		// 2.0
		'default_role' => '',
		'db_version' => '',
		// 2.0.1
		'uploads_use_yearmonth_folders' => '',
		'upload_path' => '',
		// 2.1
		'blog_public' => '',
		'default_link_category' => '',
		'show_on_front' => '',
		// 2.2
		'tag_base' => '',
		// 2.5
		'show_avatars' => '',
		'avatar_rating' => '',
		'upload_url_path' => '',
		'thumbnail_size_w' => '',
		'thumbnail_size_h' => '',
		'thumbnail_crop' => '',
		'medium_size_w' => '',
		'medium_size_h' => '',
		// 2.6
		'avatar_default' => '',
		// 2.7
		'large_size_w' => '',
		'large_size_h' => '',
		'image_default_link_type' => '',
		'image_default_size' => '',
		'image_default_align' => '',
		'close_comments_for_old_posts' => '',
		'close_comments_days_old' => '',
		'thread_comments' => '',
		'thread_comments_depth' => '',
		'page_comments' => '',
		'comments_per_page' => '',
		'default_comments_page' => '',
		'comment_order' => '',
		'sticky_posts' => '',
		'widget_categories' => '',
		'widget_text' => '',
		'widget_rss' => '',
		'uninstall_plugins' => '',
		// 2.8
		'timezone_string' => '',
		// 3.0
		'page_for_posts' => '',
		'page_on_front' => '',
		// 3.1
		'default_post_format' => '',
		// 3.5
		'link_manager_enabled' => '',
		// 4.3.0
		'finished_splitting_shared_terms' => '',
		'site_icon' => '',
		// 4.4.0
		'medium_large_size_w' => '',
		'medium_large_size_h' => '',
		// 4.9.6
		'wp_page_for_privacy_policy' => '',
		// 4.9.8
		'show_comments_cookies_opt_in' => '',
		// Deleted from new versions
		'blodotgsping_url' => '', 'bodyterminator' => '', 'emailtestonly' => '', 'phoneemail_separator' => '',
		'subjectprefix' => '', 'use_bbcode' => '', 'use_blodotgsping' => '', 'use_quicktags' => '', 'use_weblogsping' => '',
		'weblogs_cache_file' => '', 'use_preview' => '', 'use_htmltrans' => '', 'smilies_directory' => '', 'fileupload_allowedusers' => '',
		'use_phoneemail' => '', 'default_post_status' => '', 'default_post_category' => '', 'archive_mode' => '', 'time_difference' => '',
		'links_minadminlevel' => '', 'links_use_adminlevels' => '', 'links_rating_type' => '', 'links_rating_char' => '',
		'links_rating_ignore_zero' => '', 'links_rating_single_image' => '', 'links_rating_image0' => '', 'links_rating_image1' => '',
		'links_rating_image2' => '', 'links_rating_image3' => '', 'links_rating_image4' => '', 'links_rating_image5' => '',
		'links_rating_image6' => '', 'links_rating_image7' => '', 'links_rating_image8' => '', 'links_rating_image9' => '',
		'links_recently_updated_time' => '', 'links_recently_updated_prepend' => '', 'links_recently_updated_append' => '',
		'weblogs_cacheminutes' => '', 'comment_allowed_tags' => '', 'search_engine_friendly_urls' => '', 'default_geourl_lat' => '',
		'default_geourl_lon' => '', 'use_default_geourl' => '', 'weblogs_xml_url' => '', 'new_users_can_blog' => '', '_wpnonce' => '',
		'_wp_http_referer' => '', 'Update' => '', 'action' => '', 'rich_editing' => '', 'autosave_interval' => '', 'deactivated_plugins' => '',
		'can_compress_scripts' => '', 'page_uris' => '', 'update_core' => '', 'update_plugins' => '', 'update_themes' => '', 'doing_cron' => '',
		'random_seed' => '', 'rss_excerpt_length' => '', 'secret' => '', 'use_linksupdate' => '', 'default_comment_status_page' => '',
		'wporg_popular_tags' => '', 'what_to_show' => '', 'rss_language' => '', 'language' => '', 'enable_xmlrpc' => '', 'enable_app' => '',
		'embed_autourls' => '', 'default_post_edit_rows' => '',
		//Found in wp-admin/includes/upgrade.php
		'widget_search' => '',
		'widget_recent-posts' => '',
		'widget_recent-comments' => '',
		'widget_archives' => '',
		'widget_meta' => '',
		'sidebars_widgets' => '',
		// Found in wp-admin/includes/schema.php but not with the above list
		'initial_db_version' => '',
		'WPLANG' => '',
		// Found in wp-admin/includes/class-wp-plugins-list-table.php
		'recently_activated' => '',
		// Found in wp-admin/network/site-info.php
		'rewrite_rules' => '',
		// Found in wp-admin/network.php
		'auth_key' => '',
		'auth_salt' => '',
		'logged_in_key' => '',
		'logged_in_salt' => '',
		'nonce_key' => '',
		'nonce_salt' => '',
		// Found in wp-includes/theme.php
		'theme_switched' => '',
		// Found in wp-includes/class-wp-customize-manager.php
		'current_theme' => '',
		// Found in wp-includes/cron.php
		'cron' => '',
		'widget_nav_menu' => '',
		'_split_terms' => '',
		// Added in the new adbc 3.2.7
		'_wp_suggested_policy_text_has_changed' => '',
		'active_sitewide_plugins' => '',
		'admin_email_lifespan' => '',
		'adminhash' => '',
		'allowed_themes' => '',
		'allowedthemes' => '',
		'auto_core_update_checked' => '',
		'auto_core_update_failed' => '',
		'auto_core_update_last_checked' => '',
		'auto_core_update_notified' => '',
		'auto_plugin_theme_update_emails' => '',
		'auto_update_core_dev' => '',
		'auto_update_core_major' => '',
		'auto_update_core_minor' => '',
		'auto_update_plugins' => '',
		'auto_update_themes' => '',
		'blocklist_keys' => '',
		'blog_count' => '',
		'blog_upload_space' => '',
		'category_children' => '',
		'comment_previously_approved' => '',
		'core_updater.lock' => '',
		'customize_stashed_theme_mods' => '',
		'dashboard_widget_options' => '',
		'db_upgraded' => '',
		'deactivated_sitewide_plugins' => '',
		'delete_blog_hash' => '',
		'disallowed_keys' => '',
		'dismissed_update_core' => '',
		'dismissed_update_plugins' => '',
		'dismissed_update_themes' => '',
		'embed_size_h' => '',
		'embed_size_w' => '',
		'fileupload_maxk' => '',
		'fileupload_url' => '',
		'finished_updating_comment_type' => '',
		'fresh_site' => '',
		'ftp_credentials' => '',
		'global_terms_enabled' => '',
		'https_detection_errors' => '',
		'https_migration_required' => '',
		'illegal_names' => '',
		'large_image_threshold' => '',
		'layout_columns' => '',
		'links_per_page' => '',
		'ms_files_rewriting' => '',
		'my_array' => '',
		'my_option_name' => '',
		'nav_menu_options' => '',
		'network_admin_hash' => '',
		'new_admin_email' => '',
		'post_count' => '',
		'product_cat_children' => '',
		'recovery_keys' => '',
		'recovery_mode_auth_key' => '',
		'recovery_mode_auth_salt' => '',
		'recovery_mode_email_last_sent' => '',
		'registration' => '',
		'registrationnotification' => '',
		'secret_key' => '',
		'site_admins' => '',
		'site_logo' => '',
		'stylesheet_root' => '',
		'template_root' => '',
		'theme_mods_twentytwentythree' => '',
		'theme_switch_menu_locations' => '',
		'theme_switched_via_customizer' => '',
		'update_core_major' => '',
		'update_services' => '',
		'update_translations' => '',
		'upgrade_500_was_gutenberg_active' => '',
		'use_fileupload' => '',
		'user_count' => '',
		'welcome_user_email' => '',
		'widget_block' => '',
		'widget_calendar' => '',
		'widget_custom_html' => '',
		'widget_media_audio' => '',
		'widget_media_gallery' => '',
		'widget_media_image' => '',
		'widget_media_video' => '',
		'widget_pages' => '',
		'widget_recent_comments' => '',
		'widget_recent_entries' => '',
		'widget_tag_cloud' => '',
		'wp_calendar_block_has_published_posts' => '',
		'wp_force_deactivated_plugins' => '',
		'wpmu_sitewide_plugins' => '',
		'wpmu_upgrade_site' => '',
		'wp_attachment_pages_enabled' => '',
		// 6.9
		'wp_notes_notify' => '',
		// 7.0
		'wordpress_api_key' => '',
	];

	/**
	 * WordPress tables.
	 * 
	 * @var array
	 */
	private $wp_tables = [ 
		'terms' => '',
		'term_taxonomy' => '',
		'term_relationships' => '',
		'commentmeta' => '',
		'comments' => '',
		'links' => '',
		'options' => '',
		'postmeta' => '',
		'posts' => '',
		'users' => '',
		'usermeta' => '',
		// Since 3.0 in wp-admin/includes/upgrade.php
		'sitecategories' => '',
		// Since 4.4
		'termmeta' => '',
		'blogs' => '',
		'blog_versions' => '',
		'blogmeta' => '',
		'registration_log' => '',
		'signups' => '',
		'site' => '',
		'sitemeta' => '',
	];

	/**
	 * WordPress cron jobs.
	 * 
	 * @var array
	 */
	private $wp_cron_jobs = [ 
		'delete_expired_transients' => '',
		'do_pings' => '',
		'publish_future_post' => '',
		'recovery_mode_clean_expired_keys' => '',
		'update_network_counts' => '',
		'upgrader_scheduled_cleanup' => '',
		'wp_auto_updates_maybe_update' => '',
		'wp_delete_temp_updater_backups' => '',
		'wp_https_detection' => '',
		'wp_maybe_auto_update' => '',
		'wp_privacy_delete_old_export_files' => '',
		'wp_scheduled_auto_draft_delete' => '',
		'wp_scheduled_delete' => '',
		'wp_site_health_scheduled_check' => '',
		'wp_split_shared_term_batch' => '',
		'wp_update_comment_type_batch' => '',
		'wp_update_plugins' => '',
		'wp_update_themes' => '',
		'wp_update_user_counts' => '',
		'wp_version_check' => '',
		'importer_scheduled_cleanup' => '',
		'wp_schedule_delete' => '',
	];

	/**
	 * WordPress transients.
	 * 
	 * @var array
	 */
	private $wp_transients = [ 
		'_site_transient_available_translations' => '',
		'_site_transient_theme_roots' => '',
		'_site_transient_update_core' => '',
		'_site_transient_update_plugins' => '',
		'_site_transient_update_themes' => '',
		'_site_transient_wporg_theme_feature_list' => '',
		'_site_transient_wp_plugin_dependencies_plugin_data' => '',
		'_transient_dirsize_cache' => '',
		'_transient_doing_cron' => '',
		'_transient_health-check-site-status-result' => '',
		'_transient_is_multi_author' => '',
		'_transient_mailserver_last_checked' => '',
		'_transient_plugin_slugs' => '',
		'_transient_random_seed' => '',
		'_transient_settings_errors' => '',
		'_transient_wp_core_block_css_files' => '',
		'_transient_wporg_theme_feature_list' => '',
		'_transient_featured_content_ids' => '',
		'_transient_rewrite_rules' => '',
		'_transient_twentyfifteen_categories' => '',
		'_transient_twentyfourteen_category_count' => '',
		'_transient_twentyseventeen_categories' => '',
		'_transient_twentysixteen_categories' => '',
		'_transient_global_styles' => '',
		'_transient_update_core' => '',
		'_transient_update_plugins' => '',
		'_transient_update_themes' => '',
		'_transient_wp_styles_for_blocks' => '',
		'_site_transient_popular_importers_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_site_transient_g_url_details_response_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_site_transient_wp_font_collection_url_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'string' // url
		],
		'_site_transient_community-events-' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_site_transient_wp_remote_block_patterns_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_site_transient_browser_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_site_transient_php_check_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_site_transient_poptags_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_site_transient_wordpress_credits_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'string'
		],
		'_site_transient_wp_theme_files_patterns-' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_transient_wp_generating_att_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'int'
		],
		'_transient_oembed_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_transient_dash_v2_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_transient_rss_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_transient_feed_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_transient_feed_mod_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_site_transient_feed_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_site_transient_feed_mod_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_transient_scrape_key_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'md5'
		],
		'_site_transient_wp_plugin_dependencies_plugin_timeout_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'string'
		],
		'_transient_global_styles_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'string'
		],
		'_transient_global_styles_svg_filters_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'string'
		],
		'_transient_plugins_delete_result_' => [ 
			'rule' => 'starts_with',
			'concatenated_with' => 'int'
		],

	];

	/**
	 * WordPress posts meta.
	 * 
	 * @var array
	 */
	private $wp_posts_meta = [ 
		'_cover_hash' => '',
		'_customize_changeset_uuid' => '',
		'_customize_draft_post_name' => '',
		'_customize_restore_dismissed' => '',
		'_edit_last' => '',
		'_edit_lock' => '',
		'_encloseme' => '',
		'_export_data_grouped' => '',
		'_export_data_raw' => '',
		'_export_file_name' => '',
		'_export_file_path' => '',
		'_export_file_url' => '',
		'_menu_item_classes' => '',
		'_menu_item_menu_item_parent' => '',
		'_menu_item_object' => '',
		'_menu_item_object_id' => '',
		'_menu_item_orphaned' => '',
		'_menu_item_target' => '',
		'_menu_item_type' => '',
		'_menu_item_url' => '',
		'_menu_item_xfn' => '',
		'_pingme' => '',
		'_post_restored_from' => '',
		'_source_url' => '',
		'_starter_content_theme' => '',
		'_thumbnail_id' => '',
		'_trackbackme' => '',
		'_wp_admin_notified' => '',
		'_wp_attached_file' => '',
		'_wp_attachment_backup_sizes' => '',
		'_wp_attachment_context' => '',
		'_wp_attachment_image_alt' => '',
		'_wp_attachment_is_custom_background' => '',
		'_wp_attachment_is_custom_header' => '',
		'_wp_attachment_metadata' => '',
		'_wp_desired_post_slug' => '',
		'_wp_old_date' => '',
		'_wp_old_slug' => '',
		'_wp_page_template' => '',
		'_wp_suggested_privacy_policy_content' => '',
		'_wp_trash_meta_comments_status' => '',
		'_wp_trash_meta_status' => '',
		'_wp_trash_meta_time' => '',
		'_wp_user_notified' => '',
		'_wp_user_request_completed_timestamp' => '',
		'_wp_user_request_confirmed_timestamp' => '',
		'enclosure' => '',
		'footnotes' => '',
		'imagedata' => '',
		'is_wp_suggestion' => '',
		'origin' => '',
		'_wp_font_face_file' => '',
		'_wp_ignored_hooked_blocks' => '',
	];

	/**
	 * WordPress users meta.
	 * 
	 * @var array
	 */
	private $wp_users_meta = [ 
		'_new_email' => '',
		'admin_color' => '',
		'aim' => '',
		'closedpostboxes_post' => '',
		'comment_shortcuts' => '',
		'community-events-location' => '',
		'default_password_nag' => '',
		'description' => '',
		'dismissed_wp_pointers' => '',
		'enable_custom_fields' => '',
		'first_name' => '',
		'icq' => '',
		'last_name' => '',
		'last_update' => '',
		'locale' => '',
		'managenav-menuscolumnshidden' => '',
		'manageuploadcolumnshidden' => '',
		'meta-box-order_post' => '',
		'metaboxhidden_nav-menus' => '',
		'metaboxhidden_post' => '',
		'msn' => '',
		'nav_menu_recently_edited' => '',
		'nickname' => '',
		'primary_blog' => '',
		'rich_editing' => '',
		'session_tokens' => '',
		'show_admin_bar_front' => '',
		'show_welcome_panel' => '',
		'source_domain' => '',
		'syntax_highlighting' => '',
		'upload_per_page' => '',
		'use_ssl' => '',
		'wp_capabilities' => '',
		'wp_dashboard_quick_press_last_post_id' => '',
		'wp_media_library_mode' => '',
		'wp_persisted_preferences' => '',
		'wp_user_level' => '',
		'wp_user-settings' => '',
		'wp_user-settings-time' => '',
		'wporg_favorites' => '',
		'yim' => '',
	];

	/**
	 * WordPress post types.
	 * 
	 * @var array
	 */
	private $wp_post_types = [ 
		'post' => '',
		'page' => '',
		'attachment' => '',
		'revision' => '',
		'nav_menu_item' => '',
		'custom_css' => '',
		'customize_changeset' => '',
		'oembed_cache' => '',
		'user_request' => '',
		'wp_block' => '',
		'wp_global_styles' => '',
		'wp_navigation' => '',
		'wp_template' => '',
		'wp_template_part' => '',
		'wp_font_family' => '', // WP 6.5+
		'wp_font_face' => '', // WP 6.5+
	];

	// TO-CHECK: Make sure all ADBC hardcoded items are added here

	/**
	 * ADBC plugin options.
	 * 
	 * @var array
	 */
	private $adbc_options = [ 
		'adbc_plugin_settings' => '',
		'adbc_plugin_scan_info_options' => '',
		'adbc_plugin_scan_info_tables' => '',
		'adbc_plugin_scan_info_cron_jobs' => '',
		'adbc_plugin_scan_info_users_meta' => '',
		'adbc_plugin_scan_info_posts_meta' => '',
		'adbc_plugin_scan_info_transients' => '',
		'adbc_plugin_should_stop_scan_options' => '',
		'adbc_plugin_should_stop_scan_tables' => '',
		'adbc_plugin_should_stop_scan_cron_jobs' => '',
		'adbc_plugin_should_stop_scan_users_meta' => '',
		'adbc_plugin_should_stop_scan_posts_meta' => '',
		'adbc_plugin_should_stop_scan_transients' => '',
		'adbc_plugin_automation' => '',
		'adbc_plugin_license_key' => '',
		'adbc_plugin_license_key_license' => '',
		'adbc_plugin_conflict_notice' => '',
		'adbc_plugin_pro_api_scan_balance' => '', // this is used in the new pro version
		'adbc_plugin_license_key_pro' => '', // this is used in the new pro version
		'adbc_plugin_license_key_pro_license' => '', // this is used in the new pro version
	];

	/**
	 * ADBC plugin cron jobs.
	 * 
	 * @var array
	 */
	private $adbc_cron_jobs = [ 
		'adbc_cron_analytics' => '',
		'adbc_cron_automation' => '',
		'edd_sl_sdk_weekly_license_check_advanced-database-cleaner-premium' => '',
		'edd_sl_sdk_weekly_license_check_advanced-database-cleaner-pro' => '',
	];

	/**
	 * ADBC plugin transients.
	 * For this specific case, we save both the final transient name and the original name.
	 * 
	 * @var array
	 */
	private $adbc_transients = [ 
		'_transient_adbc_plugin_tables_to_repair' => 'adbc_plugin_tables_to_repair',
		'_transient_adbc_plugin_innodb_conversion_lock' => 'adbc_plugin_innodb_conversion_lock',
		'_transient_adbc_plugin_post_types_dict_updated' => 'adbc_plugin_post_types_dict_updated',
	];

	/**
	 * Most popular posts_meta and users_meta used to decide if a relation is unknown.
	 * @var array
	 */
	private $known_meta_dict = [ 
		// TO-CHECK: inserted all postmeta that have more than 30 relations
		"posts_meta" => [ 
			"_price" => "1023",
			"_regular_price" => "882",
			"_sale_price" => "682",
			"_sku" => "510",
			"_stock_status" => "469",
			"_elementor_data" => "453",
			"_stock" => "414",
			"_yoast_wpseo_metadesc" => "354",
			"_manage_stock" => "351",
			"_elementor_edit_mode" => "335",
			"_transaction_id" => "323",
			"_product_attributes" => "312",
			"_product_image_gallery" => "291",
			"_payment_method" => "283",
			"_customer_user" => "266",
			"_yoast_wpseo_title" => "250",
			"_visibility" => "236",
			"_virtual" => "226",
			"_sale_price_dates_to" => "206",
			"related-posts" => "206",
			"total_sales" => "205",
			"_billing_email" => "192",
			"_yoast_wpseo_focuskw" => "191",
			"description" => "191",
			"discount_type" => "190",
			"_weight" => "184",
			"coupon_amount" => "181",
			"_sale_price_dates_from" => "175",
			"_billing_first_name" => "173",
			"_billing_phone" => "173",
			"_order_total" => "172",
			"layout" => "172",
			"_purchase_note" => "170",
			"title" => "168",
			"rank_math_description" => "167",
			"_backorders" => "166",
			"_billing_last_name" => "165",
			"_elementor_template_type" => "160",
			"_width" => "157",
			"_length" => "156",
			"_height" => "155",
			"_downloadable" => "154",
			"usage_limit" => "153",
			"individual_use" => "150",
			"position" => "149",
			"_featured" => "147",
			"_sold_individually" => "145",
			"expiry_date" => "143",
			"_payment_method_title" => "142",
			"free_shipping" => "138",
			"keywords" => "135",
			"_form" => "132",
			"product_ids" => "131",
			"_elementor_page_settings" => "129",
			"_order_currency" => "126",
			"rule" => "123",
			"email" => "121",
			"rank_math_title" => "121",
			"exclude_product_ids" => "120",
			"rank_math_focus_keyword" => "119",
			"type" => "116",
			"_wxr_import_menu_item" => "115",
			"_wxr_import_parent" => "114",
			"_wxr_import_user_slug" => "114",
			"hide_on_screen" => "114",
			"allorany" => "113",
			"_shipping_address_1" => "112",
			"_shipping_city" => "111",
			"_wxr_import_has_attachment_refs" => "111",
			"_billing_country" => "110",
			"_shipping_postcode" => "109",
			"price" => "109",
			"_shipping_country" => "105",
			"_billing_city" => "104",
			"_billing_address_1" => "103",
			"_format_link_url" => "103",
			"_format_quote_source_url" => "101",
			"thumbnail" => "101",
			"_elementor_version" => "100",
			"twp_disable_ajax_load_next_post" => "99",
			"_shipping_first_name" => "96",
			"site_layout" => "96",
			"website_url" => "95",
			"_shipping_last_name" => "94",
			"Image" => "94",
			"_elementor_css" => "93",
			"apply_before_tax" => "93",
			"status" => "93",
			"_billing_postcode" => "91",
			"_shipping_address_2" => "91",
			"featured_item" => "88",
			"item_value" => "88",
			"_billing_state" => "87",
			"currency_val" => "87",
			"customer_email" => "87",
			"feat_post" => "87",
			"feat_serv_item" => "87",
			"frame_style" => "87",
			"remove_box_content" => "87",
			"remove_title_page" => "87",
			"testimonial_by" => "87",
			"blog-cats" => "86",
			"_aioseop_description" => "85",
			"_shipping_state" => "83",
			"related-cat" => "83",
			"_aioseo_description" => "82",
			"related-tag" => "82",
			"_billing_address_2" => "80",
			"_yoast_wpseo_meta-robots-noindex" => "79",
			"_order_key" => "78",
			"_layout" => "76",
			"_post_type" => "76",
			"_taxonomy" => "75",
			"_label_plural" => "74",
			"_label_singular" => "74",
			"_rewrite" => "74",
			"_tax_status" => "74",
			"_taxonomy_rewrite" => "74",
			"_tax_class" => "73",
			"_billing_company" => "71",
			"_shipping_company" => "70",
			"panels_data" => "70",
			"_customer_ip_address" => "69",
			"_product_url" => "69",
			"_order_tax" => "67",
			"minimum_amount" => "66",
			"usage_limit_per_user" => "65",
			"_aioseop_title" => "64",
			"_featured_header_id" => "64",
			"_sidebar_primary" => "64",
			"location" => "64",
			"phone" => "64",
			"url" => "64",
			"date_expires" => "62",
			"subtitle" => "62",
			"address" => "61",
			"_menu_item_icon" => "59",
			"_sidebar_secondary" => "58",
			"_wpb_shortcodes_custom_css" => "56",
			"_order_shipping" => "55",
			"video_url" => "55",
			"views" => "55",
			"_mail" => "54",
			"featured" => "54",
			"_wc_average_rating" => "53",
			"_format_video_embed" => "52",
			"exclude_sale_items" => "52",
			"product_categories" => "52",
			"_aioseo_title" => "51",
			"_cart_discount" => "51",
			"_seopress_titles_desc" => "51",
			"link" => "51",
			"_order_shipping_tax" => "50",
			"exclude_product_categories" => "50",
			"name" => "49",
			"rating" => "49",
			"_downloadable_files" => "48",
			"maximum_amount" => "48",
			"_default_attributes" => "47",
			"city" => "47",
			"rank_math_robots" => "46",
			"usage_count" => "46",
			"_et_pb_use_builder" => "45",
			"_format_audio_embed" => "45",
			"_shipping_phone" => "45",
			"_yoast_wpseo_canonical" => "45",
			"first_name" => "45",
			"_yoast_wpseo_opengraph-description" => "44",
			"geo_latitude" => "44",
			"geo_longitude" => "44",
			"last_name" => "44",
			"_completed_date" => "43",
			"_download_limit" => "43",
			"_variation_description" => "43",
			"twitter" => "43",
			"facebook" => "42",
			"_created_via" => "41",
			"_fl_builder_enabled" => "41",
			"_prices_include_tax" => "41",
			"_seopress_titles_title" => "41",
			"_yoast_wpseo_opengraph-image" => "41",
			"post_views_count" => "41",
			"thumb" => "41",
			"_download_expiry" => "40",
			"_email" => "40",
			"menu-item-mm-megamenu-posts" => "40",
			"menu-item-mm-megamenu-subcat" => "40",
			"sidebar_select" => "40",
			"start_date" => "40",
			"_customer_user_agent" => "39",
			"_format_gallery_images" => "39",
			"_paid_date" => "39",
			"field_group_layout" => "39",
			"show_on_page" => "39",
			"user_id" => "39",
			"_fl_builder_data" => "38",
			"country" => "38",
			"limit_usage_to_x_items" => "38",
			"longitude" => "38",
			"video" => "38",
			"_aioseop_keywords" => "37",
			"_yoast_wpseo_twitter-description" => "37",
			"_yoast_wpseo_twitter-title" => "37",
			"author" => "37",
			"latitude" => "37",
			"portfolio_image" => "37",
			"wpml_language" => "37",
			"_button_text" => "36",
			"_menu_item_megamenu" => "36",
			"_post_like_count" => "36",
			"_thankyou_action_done" => "36",
			"_yoast_wpseo_opengraph-title" => "36",
			"field_test_field" => "36",
			"transaction_id" => "36",
			"_crosssell_ids" => "35",
			"_order_discount" => "35",
			"_upsell_ids" => "35",
			"_user_IP" => "35",
			"_yoast_wpseo_meta-robots-nofollow" => "35",
			"ct_builder_shortcodes" => "35",
			"currency" => "35",
			"_status" => "34",
			"course_id" => "34",
			"duration" => "34",
			"heading" => "34",
			"_dropship_location" => "33",
			"_enable_dropship" => "33",
			"_order_stock_reduced" => "33",
			"_tracking_number" => "33",
			"_wc_review_count" => "33",
			"_wpb_vc_js_status" => "33",
			"cyberchimps_page_section_order" => "33",
			"cyberchimps_page_sidebar" => "33",
			"cyberchimps_portfolio_link_toggle_four" => "33",
			"cyberchimps_portfolio_link_toggle_one" => "33",
			"cyberchimps_portfolio_link_toggle_three" => "33",
			"cyberchimps_portfolio_link_toggle_two" => "33",
			"cyberchimps_portfolio_link_url_four" => "33",
			"cyberchimps_portfolio_link_url_one" => "33",
			"cyberchimps_portfolio_link_url_three" => "33",
			"cyberchimps_portfolio_link_url_two" => "33",
			"cyberchimps_portfolio_lite_image_four" => "33",
			"cyberchimps_portfolio_lite_image_four_caption" => "33",
			"cyberchimps_portfolio_lite_image_one" => "33",
			"cyberchimps_portfolio_lite_image_one_caption" => "33",
			"cyberchimps_portfolio_lite_image_three" => "33",
			"cyberchimps_portfolio_lite_image_three_caption" => "33",
			"cyberchimps_portfolio_lite_image_two" => "33",
			"cyberchimps_portfolio_lite_image_two_caption" => "33",
			"cyberchimps_portfolio_title" => "33",
			"cyberchimps_portfolio_title_toggle" => "33",
			"cyberchimps_slider_lite_slide_one_image" => "33",
			"cyberchimps_slider_lite_slide_one_url" => "33",
			"cyberchimps_slider_lite_slide_three_image" => "33",
			"cyberchimps_slider_lite_slide_three_url" => "33",
			"cyberchimps_slider_lite_slide_two_image" => "33",
			"cyberchimps_slider_lite_slide_two_url" => "33",
			"cyberchimps_slider_size" => "33",
			"redirect" => "33",
			"width" => "33",
			"_seopress_analysis_target_kw" => "32",
			"_subscription_period" => "32",
			"_user_liked" => "32",
			"end_date" => "32",
			"_order_number" => "31",
			"_subscription_period_interval" => "31",
			"amount" => "31",
			"gallery" => "31",
			"height" => "31",
			"order_id" => "31",
			"state" => "31",
			"_locale" => "30",
			"_my_meta_value_key" => "30",
			"_type" => "30",
			"_yoast_wpseo_twitter-image" => "30",
			"ed_header_overlay" => "30",
			"hide_title" => "30",
			"post-image" => "30",
			"source" => "30",
		],
		// TO-CHECK: inserted all usermeta that have more than 20 relations
		"users_meta" => [ 
			"billing_phone" => "477",
			"billing_country" => "300",
			"billing_first_name" => "290",
			"billing_last_name" => "286",
			"billing_city" => "284",
			"billing_address_1" => "274",
			"billing_postcode" => "270",
			"billing_state" => "251",
			"ignore_hints" => "226",
			"billing_email" => "224",
			"billing_address_2" => "206",
			"billing_company" => "194",
			"twitter" => "159",
			"shipping_first_name" => "139",
			"facebook" => "138",
			"shipping_last_name" => "136",
			"shipping_city" => "134",
			"shipping_address_1" => "133",
			"shipping_country" => "132",
			"shipping_postcode" => "132",
			"shipping_state" => "123",
			"shipping_address_2" => "113",
			"phone" => "112",
			"shipping_company" => "93",
			"linkedin" => "87",
			"acf_user_settings" => "84",
			"last_login" => "67",
			"shipping_phone" => "59",
			"instagram" => "58",
			"display_name" => "48",
			"pinterest" => "48",
			"wp_email_tracking_ignore_notice" => "46",
			"phone_number" => "41",
			"country" => "40",
			"themeisle_sdk_dismissed_notice_black_friday" => "39",
			"address" => "39",
			"dismiss-kirki-recommendation" => "39",
			"youtube" => "38",
			"avatar" => "36",
			"wcfmmp_profile_settings" => "35",
			"example_ignore_notice" => "33",
			"wpclever_wpcstore_ignore" => "32",
			"flickr" => "32",
			"dokan_profile_settings" => "32",
			"user_email" => "30",
			"city" => "30",
			"google" => "29",
			"dribbble" => "27",
			"shipping_email" => "27",
			"gender" => "27",
			"nag_remove_theme_review_notice_partially" => "26",
			"remove_theme_review_notice" => "26",
			"email" => "25",
			"mobile" => "25",
			"optionsframework_ignore_notice" => "24",
			"user_phone" => "24",
			"googleplus" => "23",
			"user_url" => "22",
			"wp_user_avatar" => "22",
		]
	];

	/**
	 * Constructor.
	 */
	protected function __construct() {
		parent::__construct();
		$this->add_special_wordpress_options();
		$this->add_special_wordpress_usermeta();
		if ( ADBC_VERSION_TYPE === 'PREMIUM' )
			$this->add_special_edd_sdk_cache_keys();
	}

	/**
	 * Add special WordPress options to the hardcoded options list.
	 * 
	 * @return void
	 */
	private function add_special_wordpress_options() {

		// The 'user_roles' option is added as $prefix.'user_roles'
		$sites = ADBC_Sites::instance()->get_sites_list();
		foreach ( $sites as $site ) {
			$this->wp_options[ $site['prefix'] . 'user_roles' ] = '';
		}

		// Add also theme_mods option
		$child_theme_slug = get_stylesheet();
		$parent_theme_slug = get_template();
		$this->wp_options[ 'theme_mods_' . $child_theme_slug ] = '';
		if ( $child_theme_slug != $parent_theme_slug ) {
			$this->wp_options[ 'theme_mods_' . $parent_theme_slug ] = '';
		}

	}

	/**
	 * Add special WordPress usermeta to the hardcoded usermeta list.
	 * 
	 * @return void
	 */
	private function add_special_wordpress_usermeta() {

		// Add correct prefixed capabilities and user_level usermeta
		$sites = ADBC_Sites::instance()->get_sites_list();
		foreach ( $sites as $site ) {
			$this->wp_users_meta[ $site['prefix'] . 'capabilities' ] = '';
			$this->wp_users_meta[ $site['prefix'] . 'user_level' ] = '';
			$this->wp_users_meta[ $site['prefix'] . 'user-settings' ] = '';
			$this->wp_users_meta[ $site['prefix'] . 'user-settings-time' ] = '';
			$this->wp_users_meta[ $site['prefix'] . 'dashboard_quick_press_last_post_id' ] = '';
		}

	}

	/**
	 * Add special EDD SDK options to the hardcoded items list.
	 * 
	 * @return void
	 */
	private function add_special_edd_sdk_cache_keys() {

		$license_key = ADBC_License_Manager::get_license_data( false )['key'] ?? '';

		if ( empty( $license_key ) )
			return;

		$edd_sdk_cache_option_name = self::adbc_build_edd_cache_key(
			ADBC_PLUGIN_DIR_NAME,
			$license_key,
			false
		);

		if ( ! empty( $edd_sdk_cache_option_name ) ) {
			$this->adbc_options[ $edd_sdk_cache_option_name ] = '';
		}

	}

	/**
	 * Get the WP hardcoded items based on the items type.
	 * 
	 * @param string $items_type The items type to get the hardcoded items for.
	 * @return array The hardcoded items or an empty array if the items type is not found.
	 */
	public function get_wordpress_items( $items_type ) {

		switch ( $items_type ) {
			case 'tables':
				return $this->wp_tables;
			case 'post_types':
				return $this->wp_post_types;
			case 'options':
				return $this->wp_options;
			case 'cron_jobs':
				return $this->wp_cron_jobs;
			case 'transients':
				return $this->wp_transients;
			case 'posts_meta':
				return $this->wp_posts_meta;
			case 'users_meta':
				return $this->wp_users_meta;
			default:
				return [];
		}

	}

	/**
	 * Get the ADBC hardcoded items based on the items type.
	 * 
	 * @param string $items_type The items type to get the hardcoded items for.
	 * @return array The hardcoded items or an empty array if the items type is not found.
	 */
	public function get_adbc_items( $items_type ) {

		switch ( $items_type ) {
			case 'options':
				return $this->adbc_options;
			case 'cron_jobs':
				return $this->adbc_cron_jobs;
			case 'transients':
				return $this->adbc_transients;
			default:
				return [];
		}

	}

	/**
	 * Load hardcoded scan results to tables rows.
	 * This function will override the belongs_to property of the tables rows with the hardcoded scan results.
	 * 
	 * @param array $tables_rows The tables rows to load the hardcoded scan results to.
	 * 
	 * @return void
	 */
	public function load_hardcoded_scan_results_to_tables_rows( &$tables_rows ) {

		$wp_hardcoded_items = $this->get_wordpress_items( 'tables' );
		$adbc_hardcoded_items = $this->get_adbc_items( 'tables' );

		foreach ( $tables_rows as $table_name => $table_data ) {

			// For tables, we should search for the table name without prefix, because hardcoded tables are saved without any prefix.
			$table_name_without_prefix = $tables_rows[ $table_name ]->table_name_without_prefix;

			if ( isset( $wp_hardcoded_items[ $table_name_without_prefix ] ) ) {

				$tables_rows[ $table_name ]->belongs_to = [ 
					'type' => 'w',
					'slug' => 'w',
					'name' => __( 'WordPress core', 'advanced-database-cleaner' ),
					'by' => 'l',
					'percent' => 100,
					'status' => 'active',
				];
				// Set known plugins/themes to empty arrays because we are sure that this item is not related to any plugin/theme.
				$tables_rows[ $table_name ]->known_plugins = [];
				$tables_rows[ $table_name ]->known_themes = [];

			} else if ( isset( $adbc_hardcoded_items[ $table_name_without_prefix ] ) ) {

				$tables_rows[ $table_name ]->belongs_to = [ 
					'type' => 'p',
					'slug' => ADBC_PLUGIN_DIR_NAME,
					'name' => ADBC_Plugins::instance()->get_plugin_name_from_slug( ADBC_PLUGIN_DIR_NAME ),
					'by' => 'l',
					'percent' => 100,
					'status' => 'active',
				];
				// Set known plugins/themes to empty arrays because we are sure that this item is not related to any plugin/theme.
				$tables_rows[ $table_name ]->known_plugins = [];
				$tables_rows[ $table_name ]->known_themes = [];

			}
		}
	}

	/**
	 * Load hardcoded scan results to items rows.
	 * This function will override the belongs_to property of the items rows with the hardcoded scan results.
	 * 
	 * @param array $items_rows The items rows to load the hardcoded scan results to.
	 * @param string $items_type The items type to load the hardcoded scan results for.
	 * @return void
	 */
	public function load_hardcoded_scan_results_to_items_rows( &$items_rows, $items_type ) {

		$adbc_hardcoded_items = $this->get_adbc_items( $items_type );
		$wp_hardcoded_items = $this->get_wordpress_items( $items_type );

		foreach ( $items_rows as $index => $item ) {

			if ( $this->is_item_belongs_to_wp_core( $item->name, $items_type, $wp_hardcoded_items ) ) {

				$items_rows[ $index ]->belongs_to = [ 
					'type' => 'w',
					'slug' => 'w',
					'name' => __( 'WordPress core', 'advanced-database-cleaner' ),
					'by' => 'l',
					'percent' => 100,
					'status' => 'active',
				];
				// Set known plugins/themes to empty arrays because we are sure that this item is not related to any plugin/theme.
				$items_rows[ $index ]->known_plugins = [];
				$items_rows[ $index ]->known_themes = [];

			} else if ( isset( $adbc_hardcoded_items[ $item->name ] ) ) {

				$items_rows[ $index ]->belongs_to = [ 
					'type' => 'p',
					'slug' => ADBC_PLUGIN_DIR_NAME,
					'name' => ADBC_Plugins::instance()->get_plugin_name_from_slug( ADBC_PLUGIN_DIR_NAME ),
					'by' => 'l',
					'percent' => 100,
					'status' => 'active',
				];
				// Set known plugins/themes to empty arrays because we are sure that this item is not related to any plugin/theme.
				$items_rows[ $index ]->known_plugins = [];
				$items_rows[ $index ]->known_themes = [];

			}

		}

	}

	/**
	 * Exclude hardcoded items from selected items.
	 * This function will remove the hardcoded items from the selected items.
	 * 
	 * @param array  $selected_items  The selected items to exclude the hardcoded items from.
	 * @param string $items_type      The items type to exclude the hardcoded items from.
	 * @param string $type_to_exclude The type of hardcoded items to exclude. Can be 'all', 'wp', or 'adbc'.
	 * @return array The cleaned selected items without the hardcoded items.
	 */
	public function exclude_hardcoded_items_from_selected_items( $selected_items, $items_type, $type_to_exclude = 'all' ) {

		$exclude_wp = ( $type_to_exclude === 'all' || $type_to_exclude === 'wp' );
		$exclude_adbc = ( $type_to_exclude === 'all' || $type_to_exclude === 'adbc' );

		$adbc_hardcoded_items = $exclude_adbc ? $this->get_adbc_items( $items_type ) : [];
		$wp_hardcoded_items = $exclude_wp ? $this->get_wordpress_items( $items_type ) : [];
		$cleaned_items = [];

		foreach ( $selected_items as $selected_item ) {

			if ( empty( $selected_item['name'] ) )
				continue; // skip malformed entry

			$name = $selected_item['name'];

			// For tables, we should search for the table name without prefix,
			// because hardcoded tables are saved without any prefix.
			if ( $items_type === 'tables' )
				$name = ADBC_Tables::remove_prefix_from_table_name( $name );

			$is_wp_core = false;
			$is_adbc = false;

			// Check WordPress core hardcoded items (exact + rule-based for transients).
			if ( $exclude_wp && $this->is_item_belongs_to_wp_core( $name, $items_type, $wp_hardcoded_items ) )
				$is_wp_core = true;

			// Check ADBC hardcoded items (exact matches only).
			if ( $exclude_adbc && isset( $adbc_hardcoded_items[ $name ] ) )
				$is_adbc = true;

			// If item is not hardcoded (WP core nor ADBC), keep it.
			if ( ! $is_wp_core && ! $is_adbc )
				$cleaned_items[] = $selected_item;

		}

		return $cleaned_items;

	}


	/**
	 * Check if an item exists in the dictionary of common meta keys.
	 * 
	 * @param string $item_name The item name to check.
	 * @param string $items_type The items type (posts_meta or users_meta).
	 * @return bool True if item exists in common dict, false otherwise.
	 */
	public function is_item_in_known_meta_dict( $item_name, $items_type ) {

		if ( array_key_exists( $item_name, $this->known_meta_dict[ $items_type ] ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Remove the hardcoded items from the list.
	 * 
	 * This function removes items that are known to belong to WordPress core
	 * or to the ADBC plugin (hardcoded items) from the given list.
	 * It supports both exact matches and rule-based entries.
	 * 
	 * @param array  $items_list The list of items to remove the hardcoded items from (passed by reference).
	 * @param string $items_type The items type to remove the hardcoded items from.
	 * @return array The list of items without the hardcoded items.
	 */
	public function remove_hardcoded_items_from_list( &$items_list, $items_type ) {

		$adbc_items = $this->get_adbc_items( $items_type );
		$wp_hardcoded_items = $this->get_wordpress_items( $items_type );

		foreach ( $items_list as $item_name => $data ) {

			if ( $item_name === '' )
				continue;

			// Remove WordPress core hardcoded items (exact + rule-based).
			if ( $this->is_item_belongs_to_wp_core( $item_name, $items_type, $wp_hardcoded_items ) ) {
				unset( $items_list[ $item_name ] );
				continue;
			}

			// Remove ADBC hardcoded items (exact matches for now).
			if ( isset( $adbc_items[ $item_name ] ) ) {
				unset( $items_list[ $item_name ] );
				continue;
			}

		}

		return $items_list;

	}

	/**
	 * Check if an item belongs to WordPress core.
	 * 
	 * This function checks both hardcoded items without rules and items
	 * defined with matching rules (starts_with, ends_with, contains)
	 * for the given items type.
	 * 
	 * @param string $item_name  The item name to check.
	 * @param string $items_type The items type (options, tables, cron_jobs, transients, posts_meta, users_meta).
	 * @param array|null $wp_items Optional preloaded WP core items array to avoid recomputing it.
	 * @return bool True if the item belongs to WordPress core, false otherwise.
	 */
	public function is_item_belongs_to_wp_core( $item_name, $items_type, $wp_items = null ) {

		if ( $wp_items === null )
			$wp_items = $this->get_wordpress_items( $items_type );

		if ( empty( $wp_items ) )
			return false;

		// For all items except transients, only exact match is needed.
		if ( $items_type !== 'transients' ) {

			if ( isset( $wp_items[ $item_name ] ) )
				return true;

			return false;

		}

		// ---- TRANSIENTS: exact and rule-based ---- //

		// First, check exact transients with no rule.
		if ( isset( $wp_items[ $item_name ] ) && ! is_array( $wp_items[ $item_name ] ) )
			return true;

		// Then check rule-based entries.
		foreach ( $wp_items as $pattern => $data ) {

			if ( ! is_array( $data ) || empty( $data['rule'] ) )
				continue;

			$rule = $data['rule']; // always starts_with, ends_with, contains
			$concat_type = isset( $data['concatenated_with'] ) ? $data['concatenated_with'] : 'string';

			// ---- starts_with ---- //
			if ( $rule === 'starts_with' ) {

				if ( strpos( $item_name, $pattern ) !== 0 )
					continue;

				$dynamic_part = substr( $item_name, strlen( $pattern ) );
				if ( $this->is_valid_hardcoded_concatenation( $dynamic_part, $concat_type ) )
					return true;

			}

			// ---- ends_with ---- //
			else if ( $rule === 'ends_with' ) {

				$pattern_len = strlen( $pattern );
				if ( $pattern_len === 0 || substr( $item_name, -$pattern_len ) !== $pattern )
					continue;

				$dynamic_part = substr( $item_name, 0, -$pattern_len );
				if ( $this->is_valid_hardcoded_concatenation( $dynamic_part, $concat_type ) )
					return true;

			}

			// ---- contains ---- //
			else if ( $rule === 'contains' ) {

				if ( strpos( $item_name, $pattern ) !== false )
					return true;

			}

		}

		return false;

	}

	/**
	 * Validate the dynamic part of a hardcoded item name based on its type.
	 * 
	 * Supported types:
	 * - md5    : 32 hex characters.
	 * - int    : numeric string.
	 * - string : any non-empty string.
	 * 
	 * @param string $value The dynamic part to validate.
	 * @param string $type  The expected type (md5, int, string).
	 * @return bool True if the value matches the expected type, false otherwise.
	 */
	private function is_valid_hardcoded_concatenation( $value, $type ) {

		if ( $value === '' )
			return false;

		switch ( $type ) {

			case 'md5':
				return strlen( $value ) === 32 && ctype_xdigit( $value );

			case 'int':
				return ctype_digit( $value );

			case 'string':
			default:
				return true;

		}

	}

	/**
	 * Build the EDD updater cache option name.
	 *
	 * Added for ADBC integration so we can compute the cache key
	 * without instantiating the updater and adding listeners.
	 *
	 * @param string $slug    Plugin slug.
	 * @param string $license License key.
	 * @param bool   $beta    Whether beta updates are enabled.
	 * @return string
	 */
	private static function adbc_build_edd_cache_key( $slug, $license = '', $beta = false ) {
		$key = md5(
			wp_json_encode(
				array(
					(string) $slug,
					(string) $license,
					(int) (bool) $beta,
				)
			)
		);

		return "edd_sl_{$key}";
	}

}