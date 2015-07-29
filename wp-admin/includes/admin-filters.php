<?php
/**
 *  Administration hooks
 *
 * @package WordPress
 *
 * @since 4.3.0
 */


/** Dashboard hooks */
add_action( 'activity_box_end', 'wp_dashboard_quota' );

/** Media hooks */
add_filter( 'media_upload_tabs', 'update_gallery_tab' );
add_filter( 'image_send_to_editor', 'image_add_caption', 20, 8 );
add_action( 'media_buttons', 'media_buttons' );

add_filter( 'attachment_fields_to_save', 'image_attachment_fields_to_save', 10, 2 );
add_filter( 'media_send_to_editor', 'image_media_send_to_editor', 10, 3 );

add_action( 'post-plupload-upload-ui', 'media_upload_flash_bypass' );
add_action( 'post-html-upload-ui', 'media_upload_html_bypass' );
add_filter( 'async_upload_image', 'get_media_item', 10, 2 );
add_filter( 'async_upload_audio', 'get_media_item', 10, 2 );
add_filter( 'async_upload_video', 'get_media_item', 10, 2 );
add_filter( 'async_upload_file',  'get_media_item', 10, 2 );

add_action( 'media_upload_image', 'wp_media_upload_handler' );
add_action( 'media_upload_audio', 'wp_media_upload_handler' );
add_action( 'media_upload_video', 'wp_media_upload_handler' );
add_action( 'media_upload_file',  'wp_media_upload_handler' );

add_filter( 'media_upload_gallery', 'media_upload_gallery' );
add_filter( 'media_upload_library', 'media_upload_library' );

add_action( 'attachment_submitbox_misc_actions', 'attachment_submitbox_metadata' );

/** Misc hooks */
add_action( 'update_option_siteurl', 'update_home_siteurl', 10, 2 );
add_action( 'update_option_page_on_front', 'update_home_siteurl', 10, 2 );
add_action( 'admin_head', 'wp_color_scheme_settings' );
add_action( 'admin_head', '_ipad_meta' );
add_filter( 'heartbeat_received', 'wp_check_locked_posts', 10, 3 );
add_filter( 'heartbeat_received', 'wp_refresh_post_lock', 10, 3 );
add_filter( 'heartbeat_received', 'wp_refresh_post_nonces', 10, 3 );
add_filter( 'heartbeat_settings', 'wp_heartbeat_set_suspension' );
// Run later as we have to set DOING_AUTOSAVE for back-compat
add_filter( 'heartbeat_received', 'heartbeat_autosave', 500, 2 );
add_action( 'post_edit_form_tag', 'post_form_autocomplete_off' );
add_action( 'admin_head', 'wp_admin_canonical_url' );

/** Nav Menu hooks */
add_action( 'admin_head-nav-menus.php', '_wp_delete_orphaned_draft_menu_items' );

/** Plugin hooks */
add_filter( 'whitelist_options', 'option_update_filter' );

/** Plugin Install hooks */
add_action( 'install_plugins_featured',               'install_dashboard' );
add_action( 'install_plugins_upload',                 'install_plugins_upload' );
add_action( 'install_plugins_search',                 'display_plugins_table' );
add_action( 'install_plugins_popular',                'display_plugins_table' );
add_action( 'install_plugins_recommended',            'display_plugins_table' );
add_action( 'install_plugins_new',                    'display_plugins_table' );
add_action( 'install_plugins_beta',                   'display_plugins_table' );
add_action( 'install_plugins_pre_plugin-information', 'install_plugin_information' );

/** Template hooks */
add_action( 'admin_enqueue_scripts', array( 'WP_Internal_Pointers', 'enqueue_scripts'                ) );
add_action( 'user_register',         array( 'WP_Internal_Pointers', 'dismiss_pointers_for_new_users' ) );

/** Theme Install hooks */
// add_action('install_themes_dashboard', 'install_themes_dashboard');
// add_action('install_themes_upload', 'install_themes_upload', 10, 0);
// add_action('install_themes_search', 'display_themes');
// add_action('install_themes_featured', 'display_themes');
// add_action('install_themes_new', 'display_themes');
// add_action('install_themes_updated', 'display_themes');
add_action( 'install_themes_pre_theme-information', 'install_theme_information' );

/** User hooks */
add_action( 'admin_init', 'default_password_nag_handler' );
add_action( 'profile_update', 'default_password_nag_edit_user', 10, 2 );
add_action( 'admin_notices', 'default_password_nag' );

/** Update hooks */
add_filter( 'update_footer', 'core_update_footer' );
add_action( 'admin_notices', 'update_nag', 3 );
add_action( 'network_admin_notices', 'update_nag', 3 );
add_action( 'admin_init', 'wp_plugin_update_rows' );
add_action( 'admin_init', 'wp_theme_update_rows' );
add_action( 'admin_notices', 'maintenance_nag' );
add_action( 'network_admin_notices', 'maintenance_nag' );

/** Update Core hooks */
add_action( '_core_updated_successfully', '_redirect_to_about_wordpress' );

/** Upgrade hooks */
add_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );
