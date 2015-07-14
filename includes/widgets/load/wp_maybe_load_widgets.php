<?php
if(!defined('ABSPATH') ) die();

global $pagenow;

/**
 * Determines if Widgets library should be loaded.
 * 
 * @package RapidoPress
 * @subpackage Widgets
 */

//saving widget ajax action.
$doing_ajax =  defined('DOING_AJAX') && DOING_AJAX;
$widgets_admin_page =  "widgets.php" == $pagenow;
$is_admin = is_admin();

$widgets_load =  $widgets_admin_page ||  !$is_admin || $doing_ajax ;

$widgets_load = apply_filters('\\rapidopress\\widgets\\load', $widgets_load);

if($widgets_load ) {
	add_action('init', RAPIDO_INCLUDES . 'widgets/load/default-widgets.php' );
}else  {
	add_action('init', function() {
		do_action('widgets_init');
	});
}

/**
 * Append the Widgets menu to the themes main menu.
 */
if($is_admin) {
	add_action( '_admin_menu', function() {

		$widgets_support = current_theme_supports( 'widgets' );

		if($widgets_support) {
			global $submenu;

			$submenu['themes.php'][7] = array( __( 'Widgets' ), 'edit_theme_options', 'widgets.php' );
			ksort( $submenu['themes.php'], SORT_NUMERIC );
		}
	} );

}
