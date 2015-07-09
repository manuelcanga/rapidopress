<?php


namespace rapidopress\database;

/**
 * Errors class
 *
 * @package RapidoPress
 * @subpackage Database
 */


class Errors {
		/**
	 * Load custom DB error or display WordPress DB error.
	 *
	 * If a file exists in the wp-content directory named db-error.php, then it will
	 * be loaded instead of displaying the WordPress DB error. If it is not found,
	 * then the WordPress DB error will be displayed instead.
	 *
	 * The WordPress DB error sets the HTTP status header to 500 to try to prevent
	 * search engines from caching the message. Custom DB messages should do the
	 * same.
	 *
	 * This function was backported to WordPress 2.3.2, but originally was added
	 * in WordPress 2.5.0.
	 *
	 * @since 2.3.2
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	static function dead_db() {
			global $wpdb;

			wp_load_translations_early();

			// Load custom DB error template, if present.
			if ( file_exists( WP_CONTENT_DIR . '/db-error.php' ) ) {
				require_once( WP_CONTENT_DIR . '/db-error.php' );
				die();
			}

			// If installing or in the admin, provide the verbose message.
			if ( defined('WP_INSTALLING') || defined('WP_ADMIN') )
				wp_die($wpdb->error);

			// Otherwise, be terse.
			status_header( 500 );
			nocache_headers();
			header( 'Content-Type: text/html; charset=utf-8' );
		?>
		<!DOCTYPE html>
		<html xmlns="http://www.w3.org/1999/xhtml"<?php if ( is_rtl() ) echo ' dir="rtl"'; ?>>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php _e( 'Database Error' ); ?></title>

		</head>
		<body>
			<h1><?php _e( 'Error establishing a database connection' ); ?></h1>
		</body>
		</html>
		<?php
			die();
	}

	static function connection_error($host) {
			wp_load_translations_early();

			// Load custom DB error template, if present.
			if ( file_exists( WP_CONTENT_DIR . '/db-error.php' ) ) {
				require_once( WP_CONTENT_DIR . '/db-error.php' );
				die();
			}

			self::bail( sprintf( __( "
<h1>Error establishing a database connection</h1>
<p>This either means that the username and password information in your <code>wp-config.php</code> file is incorrect or we can't contact the database server at <code>%s</code>. This could mean your host's database server is down.</p>
<ul>
	<li>Are you sure you have the correct username and password?</li>
	<li>Are you sure that you have typed the correct hostname?</li>
	<li>Are you sure that the database server is running?</li>
</ul>
<p>If you're unsure what these terms mean you should probably contact your host. If you still need help you can always visit the <a href='https://wordpress.org/support/'>WordPress Support Forums</a>.</p>
" ), htmlspecialchars( $host, ENT_QUOTES ) ), 'db_connect_fail' );

			return false;

	}

	static function cant_select_database($db, $user) {

				wp_load_translations_early();
				self::bail( sprintf( __( '<h1>Can&#8217;t select database</h1>
			<p>We were able to connect to the database server (which means your username and password is okay) but not able to select the <code>%1$s</code> database.</p>
			<ul>
			<li>Are you sure it exists?</li>
			<li>Does the user <code>%2$s</code> have permission to use the <code>%1$s</code> database?</li>
			<li>On some systems the name of your database is prefixed with your username, so it would be like <code>username_%1$s</code>. Could that be the problem?</li>
			</ul>
			<p>If you don\'t know how to set up a database you should <strong>contact your host</strong>. If all else fails you may find help at the <a href="https://wordpress.org/support/">WordPress Support Forums</a>.</p>' ), htmlspecialchars( $db, ENT_QUOTES ), htmlspecialchars($user, ENT_QUOTES ) ), 'db_select_fail' );

	}


	/**
	 * Wraps errors in a nice header and footer and dies.
	 *
	 * Will not die if wpdb::$show_errors is false.
	 *
	 * @since 1.5.0
	 *
	 * @param string $message The Error message
	 * @param string $error_code Optional. A Computer readable string to identify the error.
	 * @return false|void
	 */
	public function bail( $message, $error_code = '500' ) {
		global $wpdb;

		if ( !$wpdb->show_errors ) {
			if ( class_exists( 'WP_Error' ) )
				$wpdb->error = new \WP_Error($error_code, $message);
			else
				$wpdb->error = $message;
			return false;
		}
		wp_die($message);
	}

}
