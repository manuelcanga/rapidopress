<?php
/**
 * Media Library administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

if ( !current_user_can('upload_files') )
	wp_die( __( 'You do not have permission to upload files.' ) );

$mode = get_user_option( 'media_library_mode', get_current_user_id() ) ? get_user_option( 'media_library_mode', get_current_user_id() ) : 'grid';
$modes = array( 'grid', 'list' );

if ( isset( $_GET['mode'] ) && in_array( $_GET['mode'], $modes ) ) {
	$mode = $_GET['mode'];
	update_user_option( get_current_user_id(), 'media_library_mode', $mode );
}

if ( 'grid' === $mode ) {
	wp_enqueue_media();
	wp_enqueue_script( 'media-grid' );
	wp_enqueue_script( 'media' );

	$q = $_GET;
	// let JS handle this
	unset( $q['s'] );
	$vars = wp_edit_attachments_query_vars( $q );
	$ignore = array( 'mode', 'post_type', 'post_status', 'posts_per_page' );
	foreach ( $vars as $key => $value ) {
		if ( ! $value || in_array( $key, $ignore ) ) {
			unset( $vars[ $key ] );
		}
	}

	wp_localize_script( 'media-grid', '_wpMediaGridSettings', array(
		'adminUrl' => parse_url( self_admin_url(), PHP_URL_PATH ),
		'queryVars' => (object) $vars
	) );



	$title = __('Media Library');
	$parent_file = 'upload.php';

	require_once( ABSPATH . 'wp-admin/admin-header.php' );
	?>
	<div class="wrap" id="wp-media-grid" data-search="<?php _admin_search_query() ?>">
		<h2>
		<?php
		echo esc_html( $title );
		if ( current_user_can( 'upload_files' ) ) { ?>
			<a href="media-new.php" class="add-new-h2"><?php echo esc_html_x( 'Add New', 'file' ); ?></a><?php
		}
		?>
		</h2>
		<div class="error hide-if-js">
			<p><?php _e( 'The grid view for the Media Library requires JavaScript. <a href="upload.php?mode=list">Switch to the list view</a>.' ); ?></p>
		</div>
	</div>
	<?php
	include( ABSPATH . 'wp-admin/admin-footer.php' );
	exit;
}

$wp_list_table = _get_list_table('WP_Media_List_Table');
$pagenum = $wp_list_table->get_pagenum();

// Handle bulk actions
$doaction = $wp_list_table->current_action();

if ( $doaction ) {
	check_admin_referer('bulk-media');

	if ( 'delete_all' == $doaction ) {
		$post_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type='attachment' AND post_status = 'trash'" );
		$doaction = 'delete';
	} elseif ( isset( $_REQUEST['media'] ) ) {
		$post_ids = $_REQUEST['media'];
	} elseif ( isset( $_REQUEST['ids'] ) ) {
		$post_ids = explode( ',', $_REQUEST['ids'] );
	}

	$location = 'upload.php';
	if ( $referer = wp_get_referer() ) {
		if ( false !== strpos( $referer, 'upload.php' ) )
			$location = remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'message', 'ids', 'posted' ), $referer );
	}

	switch ( $doaction ) {
		case 'detach':
			wp_media_attach_action( $_REQUEST['parent_post_id'], 'detach' );
			break;

		case 'attach':
			wp_media_attach_action( $_REQUEST['found_post_id'] );
			break;

		case 'trash':
			if ( !isset( $post_ids ) )
				break;
			foreach ( (array) $post_ids as $post_id ) {
				if ( !current_user_can( 'delete_post', $post_id ) )
					wp_die( __( 'You are not allowed to move this post to the trash.' ) );

				if ( !wp_trash_post( $post_id ) )
					wp_die( __( 'Error in moving to trash.' ) );
			}
			$location = add_query_arg( array( 'trashed' => count( $post_ids ), 'ids' => join( ',', $post_ids ) ), $location );
			break;
		case 'untrash':
			if ( !isset( $post_ids ) )
				break;
			foreach ( (array) $post_ids as $post_id ) {
				if ( !current_user_can( 'delete_post', $post_id ) )
					wp_die( __( 'You are not allowed to move this post out of the trash.' ) );

				if ( !wp_untrash_post( $post_id ) )
					wp_die( __( 'Error in restoring from trash.' ) );
			}
			$location = add_query_arg( 'untrashed', count( $post_ids ), $location );
			break;
		case 'delete':
			if ( !isset( $post_ids ) )
				break;
			foreach ( (array) $post_ids as $post_id_del ) {
				if ( !current_user_can( 'delete_post', $post_id_del ) )
					wp_die( __( 'You are not allowed to delete this post.' ) );

				if ( !wp_delete_attachment( $post_id_del ) )
					wp_die( __( 'Error in deleting.' ) );
			}
			$location = add_query_arg( 'deleted', count( $post_ids ), $location );
			break;
	}

	wp_redirect( $location );
	exit;
} elseif ( ! empty( $_GET['_wp_http_referer'] ) ) {
	 wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
	 exit;
}

$wp_list_table->prepare_items();

$title = __('Media Library');
$parent_file = 'upload.php';

wp_enqueue_script( 'media' );

add_screen_option( 'per_page' );


require_once( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
<h2>
<?php
echo esc_html( $title );
if ( current_user_can( 'upload_files' ) ) { ?>
	<a href="media-new.php" class="add-new-h2"><?php echo esc_html_x('Add New', 'file'); ?></a><?php
}
if ( ! empty( $_REQUEST['s'] ) )
	printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', get_search_query() ); ?>
</h2>

<?php
$message = '';
if ( ! empty( $_GET['posted'] ) ) {
	$message = __( 'Media attachment updated.' );
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('posted'), $_SERVER['REQUEST_URI']);
}

if ( ! empty( $_GET['attached'] ) && $attached = absint( $_GET['attached'] ) ) {
	$message = sprintf( _n( 'Reattached %d attachment.', 'Reattached %d attachments.', $attached ), $attached );
	$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'detach', 'attached' ), $_SERVER['REQUEST_URI'] );
}

if ( ! empty( $_GET['detach'] ) && $detached = absint( $_GET['detach'] ) ) {
	$message = sprintf( _n( 'Detached %d attachment.', 'Detached %d attachments.', $detached ), $detached );
	$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'detach', 'attached' ), $_SERVER['REQUEST_URI'] );
}

if ( ! empty( $_GET['deleted'] ) && $deleted = absint( $_GET['deleted'] ) ) {
	if ( 1 == $deleted ) {
		$message = __( 'Media attachment permanently deleted.' );
	} else {
		$message = _n( '%d media attachment permanently deleted.', '%d media attachments permanently deleted.', $deleted );
	}
	$message = sprintf( $message, number_format_i18n( $deleted ) );
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('deleted'), $_SERVER['REQUEST_URI']);
}

if ( ! empty( $_GET['trashed'] ) && $trashed = absint( $_GET['trashed'] ) ) {
	if ( 1 == $trashed ) {
		$message = __( 'Media attachment moved to the trash.' );
	} else {
		$message = _n( '%d media attachment moved to the trash.', '%d media attachments moved to the trash.', $trashed );
	}
	$message = sprintf( $message, number_format_i18n( $trashed ) );
	$message .= ' <a href="' . esc_url( wp_nonce_url( 'upload.php?doaction=undo&action=untrash&ids='.(isset($_GET['ids']) ? $_GET['ids'] : ''), "bulk-media" ) ) . '">' . __('Undo') . '</a>';
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('trashed'), $_SERVER['REQUEST_URI']);
}

if ( ! empty( $_GET['untrashed'] ) && $untrashed = absint( $_GET['untrashed'] ) ) {
	if ( 1 == $untrashed ) {
		$message = __( 'Media attachment restored from the trash.' );
	} else {
		$message = _n( '%d media attachment restored from the trash.', '%d media attachments restored from the trash.', $untrashed );
	}
	$message = sprintf( $message, number_format_i18n( $untrashed ) );
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('untrashed'), $_SERVER['REQUEST_URI']);
}

$messages[1] = __('Media attachment updated.');
$messages[2] = __('Media permanently deleted.');
$messages[3] = __('Error saving media attachment.');
$messages[4] = __('Media moved to the trash.') . ' <a href="' . esc_url( wp_nonce_url( 'upload.php?doaction=undo&action=untrash&ids='.(isset($_GET['ids']) ? $_GET['ids'] : ''), "bulk-media" ) ) . '">' . __('Undo') . '</a>';
$messages[5] = __('Media restored from the trash.');

if ( ! empty( $_GET['message'] ) && isset( $messages[ $_GET['message'] ] ) ) {
	$message = $messages[ $_GET['message'] ];
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
}

if ( !empty($message) ) { ?>
<div id="message" class="updated notice is-dismissible"><p><?php echo $message; ?></p></div>
<?php } ?>

<form id="posts-filter" method="get">

<?php $wp_list_table->views(); ?>

<?php $wp_list_table->display(); ?>

<div id="ajax-response"></div>
<?php find_posts_div(); ?>
</form>
</div>

<?php
include( ABSPATH . 'wp-admin/admin-footer.php' );
