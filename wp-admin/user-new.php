<?php
/**
 * New User Administration Screen.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

if ( ! current_user_can( 'create_users' ) ) {
	wp_die( __( 'Cheatin&#8217; uh?' ), 403 );
}



if ( isset($_REQUEST['action']) && 'adduser' == $_REQUEST['action'] ) {
	check_admin_referer( 'add-user', '_wpnonce_add-user' );

	$user_details = null;
	$user_email = wp_unslash( $_REQUEST['email'] );
	if ( false !== strpos( $user_email, '@' ) ) {
		$user_details = get_user_by( 'email', $user_email );
	} else {
		if ( is_super_admin() ) {
			$user_details = get_user_by( 'login', $user_email );
		} else {
			wp_redirect( add_query_arg( array('update' => 'enter_email'), 'user-new.php' ) );
			die();
		}
	}

	if ( !$user_details ) {
		wp_redirect( add_query_arg( array('update' => 'does_not_exist'), 'user-new.php' ) );
		die();
	}

	if ( ! current_user_can('promote_user', $user_details->ID) )
		wp_die( __( 'Cheatin&#8217; uh?' ), 403 );

	// Adding an existing user to this blog
	$new_user_email = $user_details->user_email;
	$redirect = 'user-new.php';
	$username = $user_details->user_login;
	$user_id = $user_details->ID;
	if ( ( $username != null && !is_super_admin( $user_id ) ) && ( array_key_exists($blog_id, get_blogs_of_user($user_id)) ) ) {
		$redirect = add_query_arg( array('update' => 'addexisting'), 'user-new.php' );
	} else {
		if ( isset( $_POST[ 'noconfirmation' ] ) && is_super_admin() ) {
			add_existing_user_to_blog( array( 'user_id' => $user_id, 'role' => $_REQUEST[ 'role' ] ) );
			$redirect = add_query_arg( array('update' => 'addnoconfirmation'), 'user-new.php' );
		} else {
			$newuser_key = substr( md5( $user_id ), 0, 5 );
			add_option( 'new_user_' . $newuser_key, array( 'user_id' => $user_id, 'email' => $user_details->user_email, 'role' => $_REQUEST[ 'role' ] ) );

			$roles = get_editable_roles();
			$role = $roles[ $_REQUEST['role'] ];
			/* translators: 1: Site name, 2: site URL, 3: role, 4: activation URL */
			$message = __( 'Hi,

You\'ve been invited to join \'%1$s\' at
%2$s with the role of %3$s.

Please click the following link to confirm the invite:
%4$s' );
			wp_mail( $new_user_email, sprintf( __( '[%s] Joining confirmation' ), wp_specialchars_decode( get_option( 'blogname' ) ) ), sprintf( $message, get_option( 'blogname' ), home_url(), wp_specialchars_decode( translate_user_role( $role['name'] ) ), home_url( "/newbloguser/$newuser_key/" ) ) );
			$redirect = add_query_arg( array('update' => 'add'), 'user-new.php' );
		}
	}
	wp_redirect( $redirect );
	die();
} elseif ( isset($_REQUEST['action']) && 'createuser' == $_REQUEST['action'] ) {
	check_admin_referer( 'create-user', '_wpnonce_create-user' );

	if ( ! current_user_can('create_users') )
		wp_die( __( 'Cheatin&#8217; uh?' ), 403 );


	$user_id = edit_user();

	if ( is_wp_error( $user_id ) ) {
		$add_user_errors = $user_id;
	} else {
		if ( current_user_can( 'list_users' ) )
			$redirect = 'users.php?update=add&id=' . $user_id;
		else
			$redirect = add_query_arg( 'update', 'add', 'user-new.php' );
		wp_redirect( $redirect );
		die();
	}

}

$title = __('Add New User');
$parent_file = 'users.php';

$do_both = false;



wp_enqueue_script('wp-ajax-response');
wp_enqueue_script('user-profile');


require_once( ABSPATH . 'wp-admin/admin-header.php' );

if ( isset($_GET['update']) ) {
	$messages = array();

	if ( 'add' == $_GET['update'] )
		$messages[] = __('User added.');

}
?>
<div class="wrap">
<h2 id="add-new-user"> <?php
if ( current_user_can( 'create_users' ) ) {
	echo _x( 'Add New User', 'user' );
} elseif ( current_user_can( 'promote_users' ) ) {
	echo _x( 'Add Existing User', 'user' );
} ?>
</h2>

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

if ( ! empty( $messages ) ) {
	foreach ( $messages as $msg )
		echo '<div id="message" class="updated notice is-dismissible"><p>' . $msg . '</p></div>';
} ?>

<?php if ( isset($add_user_errors) && is_wp_error( $add_user_errors ) ) : ?>
	<div class="error">
		<?php
			foreach ( $add_user_errors->get_error_messages() as $message )
				echo "<p>$message</p>";
		?>
	</div>
<?php endif; ?>
<div id="ajax-response"></div>

<?php


if ( current_user_can( 'create_users') ) {
	if ( $do_both )
		echo '<h3 id="create-new-user">' . __( 'Add New User' ) . '</h3>';
?>
<p><?php _e('Create a brand new user and add them to this site.'); ?></p>
<form method="post" name="createuser" id="createuser" class="validate" novalidate="novalidate"<?php
	/** This action is documented in wp-admin/user-new.php */
	do_action( 'user_new_form_tag' );
?>>
<input name="action" type="hidden" value="createuser" />
<?php wp_nonce_field( 'create-user', '_wpnonce_create-user' ); ?>
<?php
// Load up the passed data, else set to a default.
$creating = isset( $_POST['createuser'] );

$new_user_login = $creating && isset( $_POST['user_login'] ) ? wp_unslash( $_POST['user_login'] ) : '';
$new_user_firstname = $creating && isset( $_POST['first_name'] ) ? wp_unslash( $_POST['first_name'] ) : '';
$new_user_lastname = $creating && isset( $_POST['last_name'] ) ? wp_unslash( $_POST['last_name'] ) : '';
$new_user_email = $creating && isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '';
$new_user_uri = $creating && isset( $_POST['url'] ) ? wp_unslash( $_POST['url'] ) : '';
$new_user_role = $creating && isset( $_POST['role'] ) ? wp_unslash( $_POST['role'] ) : '';
$new_user_send_password = $creating && isset( $_POST['send_password'] ) ? wp_unslash( $_POST['send_password'] ) : '';
$new_user_ignore_pass = $creating && isset( $_POST['noconfirmation'] ) ? wp_unslash( $_POST['noconfirmation'] ) : '';

?>
<table class="form-table">
	<tr class="form-field form-required">
		<th scope="row"><label for="user_login"><?php _e('Username'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
		<td><input name="user_login" type="text" id="user_login" value="<?php echo esc_attr($new_user_login); ?>" aria-required="true" /></td>
	</tr>
	<tr class="form-field form-required">
		<th scope="row"><label for="email"><?php _e('E-mail'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
		<td><input name="email" type="email" id="email" value="<?php echo esc_attr( $new_user_email ); ?>" /></td>
	</tr>

	<tr class="form-field">
		<th scope="row"><label for="first_name"><?php _e('First Name') ?> </label></th>
		<td><input name="first_name" type="text" id="first_name" value="<?php echo esc_attr($new_user_firstname); ?>" /></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="last_name"><?php _e('Last Name') ?> </label></th>
		<td><input name="last_name" type="text" id="last_name" value="<?php echo esc_attr($new_user_lastname); ?>" /></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="url"><?php _e('Website') ?></label></th>
		<td><input name="url" type="url" id="url" class="code" value="<?php echo esc_attr( $new_user_uri ); ?>" /></td>
	</tr>
<?php
/**
 * Filter the display of the password fields.
 *
 * @since 1.5.1
 *
 * @param bool $show Whether to show the password fields. Default true.
 */
if ( apply_filters( 'show_password_fields', true ) ) : ?>
	<tr class="form-field form-required">
		<th scope="row"><label for="pass1"><?php _e('Password'); ?> <span class="description"><?php /* translators: password input field */_e('(required)'); ?></span></label></th>
		<td>
			<input class="hidden" value=" " /><!-- #24364 workaround -->
			<input name="pass1" type="password" id="pass1" autocomplete="off" />
		</td>
	</tr>
	<tr class="form-field form-required">
		<th scope="row"><label for="pass2"><?php _e('Repeat Password'); ?> <span class="description"><?php /* translators: password input field */_e('(required)'); ?></span></label></th>
		<td>
		<input name="pass2" type="password" id="pass2" autocomplete="off" />
		<br />
		<div id="pass-strength-result"><?php _e('Strength indicator'); ?></div>
		<p class="description indicator-hint"><?php echo wp_get_password_hint(); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php _e('Send Password?') ?></th>
		<td><label for="send_password"><input type="checkbox" name="send_password" id="send_password" value="1" <?php checked( $new_user_send_password ); ?> /> <?php _e('Send this password to the new user by email.'); ?></label></td>
	</tr>
<?php endif; ?>

	<tr class="form-field">
		<th scope="row"><label for="role"><?php _e('Role'); ?></label></th>
		<td><select name="role" id="role">
			<?php
			if ( !$new_user_role )
				$new_user_role = !empty($current_role) ? $current_role : get_option('default_role');
			wp_dropdown_roles($new_user_role);
			?>
			</select>
		</td>
	</tr>
</table>

<?php
/** This action is documented in wp-admin/user-new.php */
do_action( 'user_new_form', 'add-new-user' );
?>

<?php submit_button( __( 'Add New User' ), 'primary', 'createuser', true, array( 'id' => 'createusersub' ) ); ?>

</form>
<?php } // current_user_can('create_users') ?>
</div>
<?php
include( ABSPATH . 'wp-admin/admin-footer.php' );
