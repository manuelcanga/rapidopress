<?php
if(!defined('ABSPATH') ) die();

/**
 * Default Widgets
 *
 * @package RapidoPress
 * @subpackage Widgets
 */

add_action( 'sidebar_admin_setup',  function() {
	wp_enqueue_script( 'admin-widgets' );
	wp_enqueue_style( 'admin-widgets');
});

rapidopress\widgets\visibility\Widget_Conditions::init();

register_widget('rapidopress\widgets\Widget_Text');

register_widget('rapidopress\widgets\Banner_Widget');

register_widget('rapidopress\widgets\Widget_Categories');

register_widget('rapidopress\widgets\Widget_Recent_Posts');

register_widget('rapidopress\widgets\Nav_Menu_Widget');

register_widget('rapidopress\widgets\Twitter_Timeline_Widget');

register_widget('rapidopress\widgets\Widget_Facebook_LikeBox');




/**
 * Fires after all default WordPress widgets have been registered.
 *
 * @since 2.2.0
 */
do_action( 'widgets_init' );



