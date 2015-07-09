<?php
if(!defined('RAPIDO_PRESS') ) die();

/**
 * Disable error reporting
 *
 * Set this to error_reporting( -1 ) for debugging
 */
error_reporting(0);

/** Set ABSPATH for execution */
define( 'ABSPATH', realpath('../../../') . '/' );
define( 'WPINC', 'wp-includes' );
define( 'RAPIDO_DEPENDENCIES', realpath('../') . '/' );
define('RAPIDO_INCLUDES', ABSPATH.'includes/');



/**
 * @ignore
 */
function __() {}

/**
 * @ignore
 */
function _x() {}

/**
 * @ignore
 */
function add_filter() {}

/**
 * @ignore
 */
function esc_attr() {}

/**
 * @ignore
 */
function apply_filters() {}

/**
 * @ignore
 */
function get_option() {}

/**
 * @ignore
 */
function is_lighttpd_before_150() {}

/**
 * @ignore
 */
function add_action() {}

/**
 * @ignore
 */
function do_action_ref_array() {}

/**
 * @ignore
 */
function get_bloginfo() {}

/**
 * @ignore
 */
function is_admin() {return true;}

/**
 * @ignore
 */
function site_url() {}

/**
 * @ignore
 */
function root_url() {}


/**
 * @ignore
 */
function admin_url() {}

/**
 * @ignore
 */
function did_action() {}

/**
 * @ignore
 */
function wp_guess_url() {}
if ( ! function_exists( 'json_encode' ) ) :
/**
 * @ignore
 */
function json_encode() {}
endif;

function get_file($path) {

	if ( function_exists('realpath') )
		$path = realpath($path);

	if ( ! $path || ! @is_file($path) )
		return '';

	return @file_get_contents($path);
}



require( RAPIDO_DEPENDENCIES . 'WP_Dependencies.class.php' );
require( RAPIDO_DEPENDENCIES . 'WP_Scripts.class.php' );
require( RAPIDO_DEPENDENCIES . 'WP_Styles.class.php' );
require( RAPIDO_DEPENDENCIES . 'functions/loader.php' );


require(RAPIDO_INCLUDES . 'init/version.php');

