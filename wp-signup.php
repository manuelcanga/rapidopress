<?php

/** Sets up the WordPress Environment. */
require( dirname(__FILE__) . '/wp-load.php' );

add_action( 'wp_head', 'wp_no_robots' );

require( dirname( __FILE__ ) . '/wp-blog-header.php' );

if ( is_array( get_site_option( 'illegal_names' )) && isset( $_GET[ 'new' ] ) && in_array( $_GET[ 'new' ], get_site_option( 'illegal_names' ) ) == true ) {
	wp_redirect( network_site_url() );
	die();
}

/**
 * Prints signup_header via wp_head
 *
 * @since MU
 */
function do_signup_header() {
	/**
	 * Fires within the head section of the site sign-up screen.
	 *
	 * @since 3.0.0
	 */
	do_action( 'signup_header' );
}
add_action( 'wp_head', 'do_signup_header' );


wp_redirect( site_url('wp-login.php?action=register') );
die();




