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

$title = __('General Settings');
$parent_file = 'options-general.php';


/**
 * Display JavaScript on the page.
 *
 * @since 3.5.0
 */
function options_general_add_js() {
?>
<script type="text/javascript">
	jQuery(document).ready(function($){
		var $siteName = $( '#wp-admin-bar-site-name' ).children( 'a' ).first(),
			homeURL = ( <?php echo wp_json_encode( get_home_url() ); ?> || '' ).replace( /^(https?:\/\/)?(www\.)?/, '' );

		$( '#blogname' ).on( 'input', function() {
			var title = $.trim( $( this ).val() ) || homeURL;

			// Truncate to 40 characters.
			if ( 40 < title.length ) {
				title = title.substring( 0, 40 ) + '\u2026';
			}

			$siteName.text( title );
		});
	});
</script>
<?php
}
add_action('admin_head', 'options_general_add_js');


include( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
<h2><?php echo esc_html( $title ); ?></h2>

<form method="post" action="options.php" novalidate="novalidate">
<?php settings_fields('general'); ?>

<table class="form-table">
<tr>
<th scope="row"><label for="blogname"><?php _e('Site Title') ?></label></th>
<td><input name="blogname" type="text" id="blogname" value="<?php form_option('blogname'); ?>" class="regular-text" /></td>
</tr>
<tr>
<th scope="row"><label for="blogdescription"><?php _e('Tagline') ?></label></th>
<td><input name="blogdescription" type="text" id="blogdescription" aria-describedby="tagline-description" value="<?php form_option('blogdescription'); ?>" class="regular-text" />
<p class="description" id="tagline-description"><?php _e( 'In a few words, explain what this site is about.' ) ?></p></td>
</tr>
<tr>
<th scope="row"><label for="siteurl"><?php _e('RapidoPress Address (URL)') ?></label></th>
<td><input name="siteurl" type="url" id="siteurl" value="<?php form_option( 'siteurl' ); ?>"<?php disabled( defined( 'WP_SITEURL' ) ); ?> class="regular-text code<?php if ( defined( 'WP_SITEURL' ) ) echo ' disabled' ?>" /></td>
</tr>
<tr>
<th scope="row"><label for="home"><?php _e('Site Address (URL)') ?></label></th>
<td><input name="home" type="url" id="home" aria-describedby="home-description" value="<?php form_option( 'home' ); ?>"<?php disabled( defined( 'WP_HOME' ) ); ?> class="regular-text code<?php if ( defined( 'WP_HOME' ) ) echo ' disabled' ?>" />
<p class="description" id="home-description"><?php _e( 'Enter the address here if you <a href="https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory">want your site home page to be different from your WordPress installation directory.</a>' ); ?></p></td>
</tr>
<tr>
<th scope="row"><label for="admin_email"><?php _e('E-mail Address') ?> </label></th>
<td><input name="admin_email" type="email" id="admin_email" aria-describedby="admin-email-description" value="<?php form_option( 'admin_email' ); ?>" class="regular-text ltr" />
<p class="description" id="admin-email-description"><?php _e( 'This address is used for admin purposes, like new user notification.' ) ?></p></td>
</tr>
<?php do_settings_fields('general', 'default'); ?>
</table>



<?php do_settings_sections('general'); ?>

<?php submit_button(); ?>
</form>

</div>

<?php include( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
