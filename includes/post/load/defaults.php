<?php
if(!defined('ABSPATH') ) die();
/**
 * Initialize post types and classes
 *
 * @package WordPress
 * @subpackage Post
 * @since 1.5.0
 */

class_alias('\rapidopress\post\Post', 'WP_Post');
class_alias('\rapidopress\post\Walker_PageDropdown', 'Walker_PageDropdown');
class_alias('\rapidopress\post\Walker_Page', 'Walker_Page');


/**
 * Creates the initial post types when 'init' action is fired.
 *
 * @since 2.9.0
 */
function create_initial_post_types() {
	register_post_type( 'post', array(
		'labels' => array(
			'name_admin_bar' => _x( 'Post', 'add new on admin bar' ),
		),
		'public'  => true,
		'_builtin' => true, /* internal use only. don't use this when registering your own post type. */
		'_edit_link' => 'post.php?post=%d', /* internal use only. don't use this when registering your own post type. */
		'capability_type' => 'post',
		'map_meta_cap' => true,
		'hierarchical' => false,
		'rewrite' => false,
		'query_var' => false,
		'delete_with_user' => true,
		'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'revisions', 'post-formats' ),
	) );

	register_post_type( 'page', array(
		'labels' => array(
			'name_admin_bar' => _x( 'Page', 'add new on admin bar' ),
		),
		'public' => true,
		'publicly_queryable' => false,
		'_builtin' => true, /* internal use only. don't use this when registering your own post type. */
		'_edit_link' => 'post.php?post=%d', /* internal use only. don't use this when registering your own post type. */
		'capability_type' => 'page',
		'map_meta_cap' => true,
		'hierarchical' => true,
		'rewrite' => false,
		'query_var' => false,
		'delete_with_user' => true,
		'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'page-attributes', 'custom-fields', 'comments', 'revisions' ),
	) );

	register_post_type( 'attachment', array(
		'labels' => array(
			'name' => _x('Media', 'post type general name'),
			'name_admin_bar' => _x( 'Media', 'add new from admin bar' ),
			'add_new' => _x( 'Add New', 'add new media' ),
 			'edit_item' => __( 'Edit Media' ),
 			'view_item' => __( 'View Attachment Page' ),
		),
		'public' => true,
		'show_ui' => true,
		'_builtin' => true, /* internal use only. don't use this when registering your own post type. */
		'_edit_link' => 'post.php?post=%d', /* internal use only. don't use this when registering your own post type. */
		'capability_type' => 'post',
		'capabilities' => array(
			'create_posts' => 'upload_files',
		),
		'map_meta_cap' => true,
		'hierarchical' => false,
		'rewrite' => false,
		'query_var' => false,
		'show_in_nav_menus' => false,
		'delete_with_user' => true,
		'supports' => array( 'title', 'author', 'comments' ),
	) );
	add_post_type_support( 'attachment:audio', 'thumbnail' );
	add_post_type_support( 'attachment:video', 'thumbnail' );

	register_post_type( 'revision', array(
		'labels' => array(
			'name' => __( 'Revisions' ),
			'singular_name' => __( 'Revision' ),
		),
		'public' => false,
		'_builtin' => true, /* internal use only. don't use this when registering your own post type. */
		'_edit_link' => 'revision.php?revision=%d', /* internal use only. don't use this when registering your own post type. */
		'capability_type' => 'post',
		'map_meta_cap' => true,
		'hierarchical' => false,
		'rewrite' => false,
		'query_var' => false,
		'can_export' => false,
		'delete_with_user' => true,
		'supports' => array( 'author' ),
	) );

	register_post_type( 'nav_menu_item', array(
		'labels' => array(
			'name' => __( 'Navigation Menu Items' ),
			'singular_name' => __( 'Navigation Menu Item' ),
		),
		'public' => false,
		'_builtin' => true, /* internal use only. don't use this when registering your own post type. */
		'hierarchical' => false,
		'rewrite' => false,
		'delete_with_user' => false,
		'query_var' => false,
	) );

	register_post_status( 'publish', array(
		'label'       => _x( 'Published', 'post' ),
		'public'      => true,
		'_builtin'    => true, /* internal use only. */
		'label_count' => _n_noop( 'Published <span class="count">(%s)</span>', 'Published <span class="count">(%s)</span>' ),
	) );

	register_post_status( 'future', array(
		'label'       => _x( 'Scheduled', 'post' ),
		'protected'   => true,
		'_builtin'    => true, /* internal use only. */
		'label_count' => _n_noop('Scheduled <span class="count">(%s)</span>', 'Scheduled <span class="count">(%s)</span>' ),
	) );

	register_post_status( 'draft', array(
		'label'       => _x( 'Draft', 'post' ),
		'protected'   => true,
		'_builtin'    => true, /* internal use only. */
		'label_count' => _n_noop( 'Draft <span class="count">(%s)</span>', 'Drafts <span class="count">(%s)</span>' ),
	) );

	register_post_status( 'pending', array(
		'label'       => _x( 'Pending', 'post' ),
		'protected'   => true,
		'_builtin'    => true, /* internal use only. */
		'label_count' => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>' ),
	) );

	register_post_status( 'private', array(
		'label'       => _x( 'Private', 'post' ),
		'private'     => true,
		'_builtin'    => true, /* internal use only. */
		'label_count' => _n_noop( 'Private <span class="count">(%s)</span>', 'Private <span class="count">(%s)</span>' ),
	) );

	register_post_status( 'trash', array(
		'label'       => _x( 'Trash', 'post' ),
		'internal'    => true,
		'_builtin'    => true, /* internal use only. */
		'label_count' => _n_noop( 'Trash <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>' ),
		'show_in_admin_status_list' => true,
	) );

	register_post_status( 'auto-draft', array(
		'label'    => 'auto-draft',
		'internal' => true,
		'_builtin' => true, /* internal use only. */
	) );

	register_post_status( 'inherit', array(
		'label'    => 'inherit',
		'internal' => true,
		'_builtin' => true, /* internal use only. */
		'exclude_from_search' => false,
	) );
}
