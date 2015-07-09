<?php
/**
 * Users administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

if ( ! current_user_can( 'list_users' ) )
	wp_die( __( 'Cheatin&#8217; uh?' ), 403 );

$wp_list_table = _get_list_table('WP_Users_List_Table');
$pagenum = $wp_list_table->get_pagenum();
$title = __('Users');
$parent_file = 'users.php';

add_screen_option( 'per_page' );

if ( empty($_REQUEST) ) {
	$referer = '<input type="hidden" name="wp_http_referer" value="'. esc_attr( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . '" />';
} elseif ( isset($_REQUEST['wp_http_referer']) ) {
	$redirect = remove_query_arg(array('wp_http_referer', 'updated', 'delete_count'), wp_unslash( $_REQUEST['wp_http_referer'] ) );
	$referer = '<input type="hidden" name="wp_http_referer" value="' . esc_attr($redirect) . '" />';
} else {
	$redirect = 'users.php';
	$referer = '';
}

$update = '';

/**
 * @since 3.5.0
 * @access private
 */
function delete_users_add_js() { ?>
<script>
jQuery(document).ready( function($) {
	var submit = $('#submit').prop('disabled', true);
	$('input[name=delete_option]').one('change', function() {
		submit.prop('disabled', false);
	});
	$('#reassign_user').focus( function() {
		$('#delete_option1').prop('checked', true).trigger('change');
	});
});
</script>
<?php
}

switch ( $wp_list_table->current_action() ) {

/* Bulk Dropdown menu Role changes */
case 'promote':
	check_admin_referer('bulk-users');

	if ( ! current_user_can( 'promote_users' ) )
		wp_die( __( 'You can&#8217;t edit that user.' ) );

	if ( empty($_REQUEST['users']) ) {
		wp_redirect($redirect);
		exit();
	}

	$editable_roles = get_editable_roles();
	if ( empty( $editable_roles[$_REQUEST['new_role']] ) )
		wp_die(__('You can&#8217;t give users that role.'));

	$userids = $_REQUEST['users'];
	$update = 'promote';
	foreach ( $userids as $id ) {
		$id = (int) $id;

		if ( ! current_user_can('promote_user', $id) )
			wp_die(__('You can&#8217;t edit that user.'));
		// The new role of the current user must also have the promote_users cap or be a multisite super admin
		if ( $id == $current_user->ID && ! $wp_roles->role_objects[ $_REQUEST['new_role'] ]->has_cap('promote_users') ) {
				$update = 'err_admin_role';
				continue;
		}


		$user = get_userdata( $id );
		$user->set_role($_REQUEST['new_role']);
	}

	wp_redirect(add_query_arg('update', $update, $redirect));
	exit();

case 'dodelete':

	check_admin_referer('delete-users');

	if ( empty($_REQUEST['users']) ) {
		wp_redirect($redirect);
		exit();
	}

	$userids = array_map( 'intval', (array) $_REQUEST['users'] );

	if ( empty( $_REQUEST['delete_option'] ) ) {
		$url = self_admin_url( 'users.php?action=delete&users[]=' . implode( '&users[]=', $userids ) . '&error=true' );
		$url = str_replace( '&amp;', '&', wp_nonce_url( $url, 'bulk-users' ) );
		wp_redirect( $url );
		exit;
	}

	if ( ! current_user_can( 'delete_users' ) )
		wp_die(__('You can&#8217;t delete users.'));

	$update = 'del';
	$delete_count = 0;

	foreach ( $userids as $id ) {
		if ( ! current_user_can( 'delete_user', $id ) )
			wp_die(__( 'You can&#8217;t delete that user.' ) );

		if ( $id == $current_user->ID ) {
			$update = 'err_admin_del';
			continue;
		}
		switch ( $_REQUEST['delete_option'] ) {
		case 'delete':
			wp_delete_user( $id );
			break;
		case 'reassign':
			wp_delete_user( $id, $_REQUEST['reassign_user'] );
			break;
		}
		++$delete_count;
	}

	$redirect = add_query_arg( array('delete_count' => $delete_count, 'update' => $update), $redirect);
	wp_redirect($redirect);
	exit();

case 'delete':


	check_admin_referer('bulk-users');

	if ( empty($_REQUEST['users']) && empty($_REQUEST['user']) ) {
		wp_redirect($redirect);
		exit();
	}

	if ( ! current_user_can( 'delete_users' ) )
		$errors = new WP_Error( 'edit_users', __( 'You can&#8217;t delete users.' ) );

	if ( empty($_REQUEST['users']) )
		$userids = array( intval( $_REQUEST['user'] ) );
	else
		$userids = array_map( 'intval', (array) $_REQUEST['users'] );

	add_action( 'admin_head', 'delete_users_add_js' );

	include( ABSPATH . 'wp-admin/admin-header.php' );
?>
<form method="post" name="updateusers" id="updateusers">
<?php wp_nonce_field('delete-users') ?>
<?php echo $referer; ?>

<div class="wrap">
<h2><?php _e('Delete Users'); ?></h2>
<?php if ( isset( $_REQUEST['error'] ) ) : ?>
	<div class="error">
		<p><strong><?php _e( 'ERROR:' ); ?></strong> <?php _e( 'Please select an option.' ); ?></p>
	</div>
<?php endif; ?>

<?php if ( 1 == count( $userids ) ) : ?>
	<p><?php _e( 'You have specified this user for deletion:' ); ?></p>
<?php else : ?>
	<p><?php _e( 'You have specified these users for deletion:' ); ?></p>
<?php endif; ?>

<ul>
<?php
	$go_delete = 0;
	foreach ( $userids as $id ) {
		$user = get_userdata( $id );
		if ( $id == $current_user->ID ) {
			echo "<li>" . sprintf(__('ID #%1$s: %2$s <strong>The current user will not be deleted.</strong>'), $id, $user->user_login) . "</li>\n";
		} else {
			echo "<li><input type=\"hidden\" name=\"users[]\" value=\"" . esc_attr($id) . "\" />" . sprintf(__('ID #%1$s: %2$s'), $id, $user->user_login) . "</li>\n";
			$go_delete++;
		}
	}
	?>
	</ul>
<?php if ( $go_delete ) : ?>
	<?php if ( 1 == $go_delete ) : ?>
		<fieldset><p><legend><?php _e( 'What should be done with content owned by this user?' ); ?></legend></p>
	<?php else : ?>
		<fieldset><p><legend><?php _e( 'What should be done with content owned by these users?' ); ?></legend></p>
	<?php endif; ?>
	<ul style="list-style:none;">
		<li><label><input type="radio" id="delete_option0" name="delete_option" value="delete" />
		<?php _e('Delete all content.'); ?></label></li>
		<li><input type="radio" id="delete_option1" name="delete_option" value="reassign" />
		<?php echo '<label for="delete_option1">' . __( 'Attribute all content to:' ) . '</label> ';
		wp_dropdown_users( array( 'name' => 'reassign_user', 'exclude' => array_diff( $userids, array($current_user->ID) ) ) ); ?></li>
	</ul></fieldset>
	<?php
	/**
	 * Fires at the end of the delete users form prior to the confirm button.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_User $current_user WP_User object for the user being deleted.
	 */
	do_action( 'delete_user_form', $current_user );
	?>
	<input type="hidden" name="action" value="dodelete" />
	<?php submit_button( __('Confirm Deletion'), 'secondary' ); ?>
<?php else : ?>
	<p><?php _e('There are no valid users selected for deletion.'); ?></p>
<?php endif; ?>
</div>
</form>
<?php

break;

case 'doremove':
	check_admin_referer('remove-users');


	wp_die( __( 'You can&#8217;t remove users.' ) );

case 'remove':

	check_admin_referer('bulk-users');

	wp_die( __( 'You can&#8217;t remove users.' ) );



break;

default:

	if ( !empty($_GET['_wp_http_referer']) ) {
		wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce'), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		exit;
	}

	$wp_list_table->prepare_items();
	$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );
	if ( $pagenum > $total_pages && $total_pages > 0 ) {
		wp_redirect( add_query_arg( 'paged', $total_pages ) );
		exit;
	}

	include( ABSPATH . 'wp-admin/admin-header.php' );

	$messages = array();
	if ( isset($_GET['update']) ) :
		switch($_GET['update']) {
		case 'del':
		case 'del_many':
			$delete_count = isset($_GET['delete_count']) ? (int) $_GET['delete_count'] : 0;
			if ( 1 == $delete_count ) {
				$message = __( 'User deleted.' );
			} else {
				$message = _n( '%s user deleted.', '%s users deleted.', $delete_count );
			}
			$messages[] = '<div id="message" class="updated notice is-dismissible"><p>' . sprintf( $message, number_format_i18n( $delete_count ) ) . '</p></div>';
			break;
		case 'add':
			if ( isset( $_GET['id'] ) && ( $user_id = $_GET['id'] ) && current_user_can( 'edit_user', $user_id ) ) {
				$messages[] = '<div id="message" class="updated notice is-dismissible"><p>' . sprintf( __( 'New user created. <a href="%s">Edit user</a>' ),
					esc_url( add_query_arg( 'wp_http_referer', urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
						self_admin_url( 'user-edit.php?user_id=' . $user_id ) ) ) ) . '</p></div>';
			} else {
				$messages[] = '<div id="message" class="updated notice is-dismissible"><p>' . __( 'New user created.' ) . '</p></div>';
			}
			break;
		case 'promote':
			$messages[] = '<div id="message" class="updated notice is-dismissible"><p>' . __('Changed roles.') . '</p></div>';
			break;
		case 'err_admin_role':
			$messages[] = '<div id="message" class="error notice is-dismissible"><p>' . __('The current user&#8217;s role must have user editing capabilities.') . '</p></div>';
			$messages[] = '<div id="message" class="updated notice is-dismissible"><p>' . __('Other user roles have been changed.') . '</p></div>';
			break;
		case 'err_admin_del':
			$messages[] = '<div id="message" class="error notice is-dismissible"><p>' . __('You can&#8217;t delete the current user.') . '</p></div>';
			$messages[] = '<div id="message" class="updated notice is-dismissible"><p>' . __('Other users have been deleted.') . '</p></div>';
			break;
		case 'remove':
			$messages[] = '<div id="message" class="updated notice is-dismissible fade"><p>' . __('User removed from this site.') . '</p></div>';
			break;
		case 'err_admin_remove':
			$messages[] = '<div id="message" class="error notice is-dismissible"><p>' . __("You can't remove the current user.") . '</p></div>';
			$messages[] = '<div id="message" class="updated notice is-dismissible fade"><p>' . __('Other users have been removed.') . '</p></div>';
			break;
		}
	endif; ?>

<?php if ( isset($errors) && is_wp_error( $errors ) ) : ?>
	<div class="error">
		<ul>
		<?php
			foreach ( $errors->get_error_messages() as $err )
				echo "<li>$err</li>\n";
		?>
		</ul>
	</div>
<?php endif;

if ( ! empty($messages) ) {
	foreach ( $messages as $msg )
		echo $msg;
} ?>

<div class="wrap">
<h2>
<?php
echo esc_html( $title );
if ( current_user_can( 'create_users' ) ) { ?>
	<a href="user-new.php" class="add-new-h2"><?php echo esc_html_x( 'Add New', 'user' ); ?></a>
<?php }

if ( $usersearch )
	printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_html( $usersearch ) ); ?>
</h2>

<?php $wp_list_table->views(); ?>

<form method="get">

<?php $wp_list_table->search_box( __( 'Search Users' ), 'user' ); ?>

<?php $wp_list_table->display(); ?>
</form>

<br class="clear" />
</div>
<?php
break;

} // end of the $doaction switch

include( ABSPATH . 'wp-admin/admin-footer.php' );
