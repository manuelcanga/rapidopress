<?php
/**
 * Install plugin administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */
// TODO route this pages via a specific iframe handler instead of the do_action below
if ( !defined( 'IFRAME_REQUEST' ) && isset( $_GET['tab'] ) && ( 'plugin-information' == $_GET['tab'] ) )
	define( 'IFRAME_REQUEST', true );

/**
 * WordPress Administration Bootstrap.
 */
require_once( dirname( __FILE__ ) . '/admin.php' );

if ( ! current_user_can('install_plugins') )
	wp_die(__('You do not have sufficient permissions to install plugins on this site.'));

$wp_list_table = _get_list_table('WP_Plugin_Install_List_Table');
$pagenum = $wp_list_table->get_pagenum();

if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
	$location = remove_query_arg( '_wp_http_referer', wp_unslash( $_SERVER['REQUEST_URI'] ) );

	if ( ! empty( $_REQUEST['paged'] ) ) {
		$location = add_query_arg( 'paged', (int) $_REQUEST['paged'], $location );
	}

	wp_redirect( $location );
	exit;
}

$wp_list_table->prepare_items();

$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );

if ( $pagenum > $total_pages && $total_pages > 0 ) {
	wp_redirect( add_query_arg( 'paged', $total_pages ) );
	exit;
}

$title = __( 'Add Plugins' );
$parent_file = 'plugins.php';

wp_enqueue_script( 'plugin-install' );
if ( 'plugin-information' != $tab )
	add_thickbox();

$body_id = $tab;

wp_enqueue_script( 'updates' );

/**
 * Fires before each tab on the Install Plugins screen is loaded.
 *
 * The dynamic portion of the action hook, `$tab`, allows for targeting
 * individual tabs, for instance 'install_plugins_pre_plugin-information'.
 *
 * @since 2.7.0
 */
do_action( "install_plugins_pre_$tab" );

/**
 * WordPress Administration Template Header.
 */
include(ABSPATH . 'wp-admin/admin-header.php');
?>
<div class="wrap">
<h2>
	<?php
	echo esc_html( $title );
	if ( ! empty( $tabs['upload'] ) && current_user_can( 'upload_plugins' ) ) {
		if ( $tab === 'upload' ) {
			$href = self_admin_url( 'plugin-install.php' );
			$text = _x( 'Browse', 'plugins' );
		} else {
			$href = self_admin_url( 'plugin-install.php?tab=upload' );
			$text = __( 'Upload Plugin' );
		}
		echo ' <a href="' . $href . '" class="upload add-new-h2">' . $text . '</a>';
	}
	?>
</h2>

<?php
if ( $tab !== 'upload' ) {
	$wp_list_table->views();
	echo '<br class="clear" />';
}

/**
 * Fires after the plugins list table in each tab of the Install Plugins screen.
 *
 * The dynamic portion of the action hook, `$tab`, allows for targeting
 * individual tabs, for instance 'install_plugins_plugin-information'.
 *
 * @since 2.7.0
 *
 * @param int $paged The current page number of the plugins list table.
 */
do_action( "install_plugins_$tab", $paged ); ?>
</div>

<?php
wp_print_request_filesystem_credentials_modal();

/**
 * WordPress Administration Template Footer.
 */
include(ABSPATH . 'wp-admin/admin-footer.php');
