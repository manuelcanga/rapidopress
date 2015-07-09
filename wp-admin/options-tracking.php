<?php
/**
 * Reading settings administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

if ( ! current_user_can( 'manage_options' ) )
	wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );

$title = __( 'Tracking Settings' );
$parent_file = 'options-general.php';


include( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
<h2><?php echo esc_html( $title ); ?></h2>

<form method="post" action="options.php">
<?php
settings_fields( 'tracking' );

?>

<table class="form-table">
<tr>
<th scope="row"><?php _e('Tracking Code'); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e('Tracking Code'); ?></span></legend>
<p><label for="tracking_code"><?php _e('Add code for tracking (it will be inserted before the <code>&lt;/head&gt;</code> tag).'); ?></label></p>
<p>
<textarea name="tracking_code" rows="10" cols="50" id="tracking_code" class="large-text code"><?php echo esc_textarea( get_option( 'tracking_code' ) ); ?></textarea>
</p>
</fieldset></td>
</tr>
<?php do_settings_fields( 'tracking', 'default' ); ?>
</table>

<?php do_settings_sections( 'tracking' ); ?>

<?php submit_button(); ?>
</form>
</div>
<?php include( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
