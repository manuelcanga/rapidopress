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

$title = __( 'Search Engines Settings' );
$parent_file = 'options-general.php';


include( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
<h2><?php echo esc_html( $title ); ?></h2>

<form method="post" action="options.php">
<?php
settings_fields( 'seo' );

?>

<table class="form-table">
<tr class="option-site-visibility">
<th scope="row"><?php has_action( 'blog_privacy_selector' ) ? _e( 'Site Visibility' ) : _e( 'Search Engine Visibility' ); ?> </th>
<td><fieldset><legend class="screen-reader-text"><span><?php has_action( 'blog_privacy_selector' ) ? _e( 'Site Visibility' ) : _e( 'Search Engine Visibility' ); ?> </span></legend>
<?php if ( has_action( 'blog_privacy_selector' ) ) : ?>
	<input id="blog-public" type="radio" name="blog_public" value="1" <?php checked('1', get_option('blog_public')); ?> />
	<label for="blog-public"><?php _e( 'Allow search engines to index this site' );?></label><br/>
	<input id="blog-norobots" type="radio" name="blog_public" value="0" <?php checked('0', get_option('blog_public')); ?> />
	<label for="blog-norobots"><?php _e( 'Discourage search engines from indexing this site' ); ?></label>
	<p class="description"><?php _e( 'Note: Neither of these options blocks access to your site &mdash; it is up to search engines to honor your request.' ); ?></p>
	<?php
	/**
	 * Enable the legacy 'Site Visibility' privacy options.
	 *
	 * By default the privacy options form displays a single checkbox to 'discourage' search
	 * engines from indexing the site. Hooking to this action serves a dual purpose:
	 * 1. Disable the single checkbox in favor of a multiple-choice list of radio buttons.
	 * 2. Open the door to adding additional radio button choices to the list.
	 *
	 * Hooking to this action also converts the 'Search Engine Visibility' heading to the more
	 * open-ended 'Site Visibility' heading.
	 *
	 * @since 2.1.0
	 */
	do_action( 'blog_privacy_selector' );
	?>
<?php else : ?>
	<label for="blog_public"><input name="blog_public" type="checkbox" id="blog_public" value="0" <?php checked( '0', get_option( 'blog_public' ) ); ?> />
	<?php _e( 'Discourage search engines from indexing this site' ); ?></label>
	<p class="description"><?php _e( 'It is up to search engines to honor this request.' ); ?></p>
<?php endif; ?>
</fieldset></td>
</tr>
<?php do_settings_fields( 'seo', 'default' ); ?>
</table>

<?php do_settings_sections( 'seo' ); ?>

<?php submit_button(); ?>
</form>
</div>
<?php include( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
