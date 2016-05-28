<?php
/**
 * General settings administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

/** WordPress Translation Install API */
require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );

if ( ! current_user_can( 'manage_options' ) )
	wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );

$title = __('Users Settings');
$parent_file = 'options-general.php';

/**
 * Output JavaScript to toggle display of additional settings if avatars are disabled.
 *
 * @since 4.2.0
 */
function options_users_add_js() {
?>
	<script>
	(function($){
		var parent = $( '#show_avatars' ),
			children = $( '.avatar-settings' );
		parent.change(function(){
			children.toggleClass( 'hide-if-js', ! this.checked );
		});
	})(jQuery);
	</script>
<?php
}
add_action( 'admin_print_footer_scripts', 'options_users_add_js' );


include( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
<h2><?php echo esc_html( $title ); ?></h2>

<form method="post" action="options.php" novalidate="novalidate">
<?php settings_fields('users'); ?>

<table class="form-table">
<tr>
<th scope="row"><?php _e('Membership') ?></th>
<td> <fieldset><legend class="screen-reader-text"><span><?php _e('Membership') ?></span></legend><label for="users_can_register">
<input name="users_can_register" type="checkbox" id="users_can_register" value="1" <?php checked('1', get_option('users_can_register')); ?> />
<?php _e('Anyone can register') ?></label>
</fieldset></td>
</tr>
<tr>
<th scope="row"><label for="default_role"><?php _e('New User Default Role') ?></label></th>
<td>
<select name="default_role" id="default_role"><?php wp_dropdown_roles( get_option('default_role') ); ?></select>
</td>
</tr>
<?php do_settings_fields('users', 'default'); ?>
</table>
<hr />

<h3 class="title"><?php _e('Avatars'); ?></h3>

<p><?php _e('An avatar is an image that follows you from weblog to weblog appearing beside your name when you comment on avatar enabled sites.'); ?></p>

<?php
// the above would be a good place to link to codex documentation on the gravatar functions, for putting it in themes. anything like that?

$show_avatars = get_option( 'show_avatars' );
?>

<table class="form-table">
<tr>
<th scope="row"><?php _e('Avatar Display'); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e('Avatar Display'); ?></span></legend>
	<label for="show_avatars">
		<input type="checkbox" id="show_avatars" name="show_avatars" value="1" <?php checked( $show_avatars, 1 ); ?> />
		<?php _e( 'Show Avatars' ); ?>
	</label>
</fieldset></td>
</tr>
<?php do_settings_fields('users', 'avatars'); ?>
</table>

<?php do_settings_sections('users'); ?>

<?php submit_button(); ?>
</form>

</div>

<?php include( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
