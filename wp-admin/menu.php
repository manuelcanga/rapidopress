<?php
/**
 * Build Administration Menu.
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Constructs the admin menu.
 *
 * The elements in the array are :
 *     0: Menu item name
 *     1: Minimum level or capability required.
 *     2: The URL of the item's file
 *     3: Class
 *     4: ID
 *     5: Icon for top level menu
 *
 * @global array $menu
 * @name $menu
 * @var array
 */

$menu[2] = array( __('Dashboard'), 'read', 'index.php', '', 'menu-top menu-top-first menu-icon-dashboard', 'menu-dashboard', 'dashicons-dashboard' );

$submenu[ 'index.php' ][0] = array( __('Home'), 'read', 'index.php' );

/*
TRASWEB 
if (  is_super_admin() )
	$update_data = wp_get_update_data();
*/


if ( current_user_can( 'update_core' ) )
	$cap = 'update_core';
elseif ( current_user_can( 'update_plugins' ) )
	$cap = 'update_plugins';
else
	$cap = 'update_themes';

/*
TRASWEB
 $submenu[ 'index.php' ][10] = array( sprintf( __('Updates %s'), "<span class='update-plugins count-{$update_data['counts']['total']}' title='{$update_data['title']}'><span class='update-count'>" . number_format_i18n($update_data['counts']['total']) . "</span></span>" ), $cap, 'update-core.php'); */
unset( $cap );

$menu[4] = array( '', 'read', 'separator1', '', 'wp-menu-separator' );


/** -------------------------------- POSTS -------------------------------- */
$menu[5] = array( __('Posts'), 'edit_posts', 'edit.php', '', 'open-if-no-js menu-top menu-icon-post', 'menu-posts', 'dashicons-admin-post' );
	$submenu['edit.php'][5]  = array( __('All Posts'), 'edit_posts', 'edit.php' );
	/* translators: add new post */
	$submenu['edit.php'][10]  = array( _x('Add New', 'post'), get_post_type_object( 'post' )->cap->create_posts, 'post-new.php' );

	$i = 15;
	foreach ( get_taxonomies( array(), 'objects' ) as $tax ) {
		if ( ! $tax->show_ui || ! $tax->show_in_menu || ! in_array('post', (array) $tax->object_type, true) )
			continue;

		$submenu['edit.php'][$i++] = array( esc_attr( $tax->labels->menu_name ), $tax->cap->manage_terms, 'edit-tags.php?taxonomy=' . $tax->name );
	}
	unset($tax);

	/** --------- COMMENTS ------ */
	$awaiting_mod = wp_count_comments();
	$awaiting_mod = $awaiting_mod->moderated;
	$submenu['edit.php'][25] = array( sprintf( __('Comments %s'), "<span class='awaiting-mod count-$awaiting_mod'><span class='pending-count'>" . number_format_i18n($awaiting_mod) . "</span></span>" ), 'edit_posts', 'edit-comments.php', '', 'menu-icon-comments', 'menu-comments', 'dashicons-admin-comments' );
	unset($awaiting_mod);


/** -------------------------------- PAGES -------------------------------- */
$menu[20] = array( __('Pages'), 'edit_pages', 'edit.php?post_type=page', '', 'menu-top menu-icon-page', 'menu-pages', 'dashicons-admin-page' );
	$submenu['edit.php?post_type=page'][5] = array( __('All Pages'), 'edit_pages', 'edit.php?post_type=page' );
	/* translators: add new page */
	$submenu['edit.php?post_type=page'][10] = array( _x('Add New', 'page'), get_post_type_object( 'page' )->cap->create_posts, 'post-new.php?post_type=page' );
	$i = 15;
	foreach ( get_taxonomies( array(), 'objects' ) as $tax ) {
		if ( ! $tax->show_ui || ! $tax->show_in_menu  || ! in_array('page', (array) $tax->object_type, true) )
			continue;

		$submenu['edit.php?post_type=page'][$i++] = array( esc_attr( $tax->labels->menu_name ), $tax->cap->manage_terms, 'edit-tags.php?taxonomy=' . $tax->name . '&amp;post_type=page' );
	}
	unset($tax);


$_wp_last_object_menu = 20; // The index of the last top-level menu in the object menu group

foreach ( (array) get_post_types( array('show_ui' => true, '_builtin' => false, 'show_in_menu' => true ) ) as $ptype ) {
	$ptype_obj = get_post_type_object( $ptype );
	// Check if it should be a submenu.
	if ( $ptype_obj->show_in_menu !== true )
		continue;
	$ptype_menu_position = is_int( $ptype_obj->menu_position ) ? $ptype_obj->menu_position : ++$_wp_last_object_menu; // If we're to use $_wp_last_object_menu, increment it first.
	$ptype_for_id = sanitize_html_class( $ptype );

	if ( is_string( $ptype_obj->menu_icon ) ) {
		// Special handling for data:image/svg+xml and Dashicons.
		if ( 0 === strpos( $ptype_obj->menu_icon, 'data:image/svg+xml;base64,' ) || 0 === strpos( $ptype_obj->menu_icon, 'dashicons-' ) ) {
			$menu_icon = $ptype_obj->menu_icon;
		} else {
			$menu_icon = esc_url( $ptype_obj->menu_icon );
		}
		$ptype_class = $ptype_for_id;
	} else {
		$menu_icon   = 'dashicons-admin-post';
		$ptype_class = 'post';
	}

	/*
	 * If $ptype_menu_position is already populated or will be populated
	 * by a hard-coded value below, increment the position.
	 */
	$core_menu_positions = array(59, 60, 65, 70, 75, 80, 85, 99);
	while ( isset($menu[$ptype_menu_position]) || in_array($ptype_menu_position, $core_menu_positions) )
		$ptype_menu_position++;

	$menu[$ptype_menu_position] = array( esc_attr( $ptype_obj->labels->menu_name ), $ptype_obj->cap->edit_posts, "edit.php?post_type=$ptype", '', 'menu-top menu-icon-' . $ptype_class, 'menu-posts-' . $ptype_for_id, $menu_icon );
	$submenu["edit.php?post_type=$ptype"][5]  = array( $ptype_obj->labels->all_items, $ptype_obj->cap->edit_posts,  "edit.php?post_type=$ptype");
	$submenu["edit.php?post_type=$ptype"][10]  = array( $ptype_obj->labels->add_new, $ptype_obj->cap->create_posts, "post-new.php?post_type=$ptype" );

	$i = 15;
	foreach ( get_taxonomies( array(), 'objects' ) as $tax ) {
		if ( ! $tax->show_ui || ! $tax->show_in_menu || ! in_array($ptype, (array) $tax->object_type, true) )
			continue;

		$submenu["edit.php?post_type=$ptype"][$i++] = array( esc_attr( $tax->labels->menu_name ), $tax->cap->manage_terms, "edit-tags.php?taxonomy=$tax->name&amp;post_type=$ptype" );
	}
}
unset($ptype, $ptype_obj, $ptype_class, $ptype_for_id, $ptype_menu_position, $menu_icon, $i, $tax);

$menu[59] = array( '', 'read', 'separator2', '', 'wp-menu-separator' );

$appearance_cap = current_user_can( 'switch_themes') ? 'switch_themes' : 'edit_theme_options';



/** -------------------------------- Appearance -------------------------------- */
$menu[60] = array( __( 'Appearance' ), $appearance_cap, 'themes.php', '', 'menu-top menu-icon-appearance', 'menu-appearance', 'dashicons-admin-appearance' );
	$submenu['themes.php'][5] = array( __( 'Themes' ), $appearance_cap, 'themes.php' );

	/** Multimedia Library */
	$submenu['themes.php'][6] = array( __('Library'), 'upload_files', 'upload.php');

	/** MENUS */
	if ( current_theme_supports( 'menus' ) || current_theme_supports( 'widgets' ) ) {
		$submenu['themes.php'][10] = array( __( 'Menus' ), 'edit_theme_options', 'nav-menus.php' );
	}



unset( $appearance_cap );






/** -------------------------------- Users -------------------------------- */
if ( current_user_can('list_users') )
	$menu[70] = array( __('Users'), 'list_users', 'users.php', '', 'menu-top menu-icon-users', 'menu-users', 'dashicons-admin-users' );
else
	$menu[70] = array( __('Profile'), 'read', 'profile.php', '', 'menu-top menu-icon-users', 'menu-users', 'dashicons-admin-users' );

if ( current_user_can('list_users') ) {
	$_wp_real_parent_file['profile.php'] = 'users.php'; // Back-compat for plugins adding submenus to profile.php.
	$submenu['users.php'][5] = array(__('All Users'), 'list_users', 'users.php');
	if ( current_user_can( 'create_users' ) ) {
		$submenu['users.php'][10] = array(_x('Add New', 'user'), 'create_users', 'user-new.php');
	} 

	$submenu['users.php'][15] = array(__('Your Profile'), 'read', 'profile.php');
} else {
	$_wp_real_parent_file['users.php'] = 'profile.php';
	$submenu['profile.php'][5] = array(__('Your Profile'), 'read', 'profile.php');
	if ( current_user_can( 'create_users' ) ) {
		$submenu['profile.php'][10] = array(__('Add New User'), 'create_users', 'user-new.php');
	} 
}


/** -------------------------------- Settings -------------------------------- */
$menu[80] = array( __('Settings'), 'manage_options', 'options-general.php', '', 'menu-top menu-icon-settings', 'menu-settings', 'dashicons-admin-settings' );
	$submenu['options-general.php'][10] = array(_x('General', 'settings screen'), 'manage_options', 'options-general.php');
	/** --------- PLUGINS ------ */
	if (  current_user_can( 'update_plugins' ) ) {
		if ( ! isset( $update_data ) )
			$update_data = wp_get_update_data();
		$count = "<span class='update-plugins count-{$update_data['counts']['plugins']}'><span class='plugin-count'>" . number_format_i18n($update_data['counts']['plugins']) . "</span></span>";
	}

	$submenu['options-general.php'][15] = array( sprintf( __('Plugins %s'), $count ), 'activate_plugins', 'plugins.php', '', 'menu-icon-plugins', 'menu-plugins', 'dashicons-admin-plugins' );
	unset( $update_data );
	$submenu['options-general.php'][20] = array(__('Localization'), 'manage_options', 'options-localization.php');
	$submenu['options-general.php'][25] = array(__('Users'), 'manage_options', 'options-users.php');
	$submenu['options-general.php'][30] = array(__('Contents'), 'manage_options', 'options-contents.php');
	$submenu['options-general.php'][35] = array(__('Search engines'), 'manage_options', 'options-seo.php');
	$submenu['options-general.php'][40] = array(__('Permalinks'), 'manage_options', 'options-permalink.php');
	$submenu['options-general.php'][40] = array(__('Tracking'), 'manage_options', 'options-tracking.php');
	$submenu['options-general.php'][50] = array(__('Discussion'), 'manage_options', 'options-discussion.php');
	$submenu['options-general.php'][55] = array(__('Media'), 'manage_options', 'options-media.php');

	$count = '';
$_wp_last_utility_menu = 80; // The index of the last top-level menu in the utility menu group

$menu[99] = array( '', 'read', 'separator-last', '', 'wp-menu-separator' );

// Back-compat for old top-levels
$_wp_real_parent_file['post.php'] = 'edit.php';
$_wp_real_parent_file['post-new.php'] = 'edit.php';
$_wp_real_parent_file['edit-pages.php'] = 'edit.php?post_type=page';
$_wp_real_parent_file['page-new.php'] = 'edit.php?post_type=page';

// ensure we're backwards compatible
$compat = array(
	'index' => 'dashboard',
	'edit' => 'posts',
	'post' => 'posts',
	'upload' => 'media',
	'edit-pages' => 'pages',
	'page' => 'pages',
	'edit-comments' => 'comments',
	'options-general' => 'settings',
	'themes' => 'appearance',
	);

require_once(ABSPATH . 'wp-admin/includes/menu.php');
