<?php
/**
 * Writing settings administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

if ( ! current_user_can( 'manage_options' ) )
	wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );

$title = __('Contents Settings');
$parent_file = 'options-general.php';


/**
 * Display JavaScript on the page.
 *
 * @since 3.5.0
 */
function options_contents_add_js() {
?>
<script type="text/javascript">
	jQuery(document).ready(function($){
		var section = $('#front-static-pages'),
			staticPage = section.find('input:radio[value="page"]'),
			selects = section.find('select'),
			check_disabled = function(){
				selects.prop( 'disabled', ! staticPage.prop('checked') );
			};
		check_disabled();
 		section.find('input:radio').change(check_disabled);
	});
</script>
<?php
}
add_action('admin_head', 'options_contents_add_js');

include( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
<h2><?php echo esc_html( $title ); ?></h2>

<form method="post" action="options.php">
<?php settings_fields('contents'); ?>


<?php if ( ! get_pages() ) : ?>
<input name="show_on_front" type="hidden" value="posts" />
<table class="form-table">
<?php
	if ( 'posts' != get_option( 'show_on_front' ) ) :
		update_option( 'show_on_front', 'posts' );
	endif;

else :
	if ( 'page' == get_option( 'show_on_front' ) && ! get_option( 'page_on_front' ) && ! get_option( 'page_for_posts' ) )
		update_option( 'show_on_front', 'posts' );
?>
<table class="form-table">
<tr>
<th scope="row"><?php _e( 'Front page displays' ); ?></th>
<td id="front-static-pages"><fieldset><legend class="screen-reader-text"><span><?php _e( 'Front page displays' ); ?></span></legend>
	<p><label>
		<input name="show_on_front" type="radio" value="posts" class="tog" <?php checked( 'posts', get_option( 'show_on_front' ) ); ?> />
		<?php _e( 'Your latest posts' ); ?>
	</label>
	</p>
	<p><label>
		<input name="show_on_front" type="radio" value="page" class="tog" <?php checked( 'page', get_option( 'show_on_front' ) ); ?> />
		<?php printf( __( 'A <a href="%s">static page</a> (select below)' ), 'edit.php?post_type=page' ); ?>
	</label>
	</p>
<ul>
	<li><label for="page_on_front"><?php printf( __( 'Front page: %s' ), wp_dropdown_pages( array( 'name' => 'page_on_front', 'echo' => 0, 'show_option_none' => __( '&mdash; Select &mdash;' ), 'option_none_value' => '0', 'selected' => get_option( 'page_on_front' ) ) ) ); ?></label></li>
	<li><label for="page_for_posts"><?php printf( __( 'Posts page: %s' ), wp_dropdown_pages( array( 'name' => 'page_for_posts', 'echo' => 0, 'show_option_none' => __( '&mdash; Select &mdash;' ), 'option_none_value' => '0', 'selected' => get_option( 'page_for_posts' ) ) ) ); ?></label></li>
</ul>
<?php if ( 'page' == get_option( 'show_on_front' ) && get_option( 'page_for_posts' ) == get_option( 'page_on_front' ) ) : ?>
<div id="front-page-warning" class="error inline"><p><?php _e( '<strong>Warning:</strong> these pages should not be the same!' ); ?></p></div>
<?php endif; ?>
</fieldset></td>
</tr>
<?php endif; ?>
</table>
<hr />


<table class="form-table">
<tr>
<th scope="row"><label for="posts_per_page"><?php _e( 'Blog pages show at most' ); ?></label></th>
<td>
<input name="posts_per_page" type="number" step="1" min="1" id="posts_per_page" value="<?php form_option( 'posts_per_page' ); ?>" class="small-text" /> <?php _e( 'posts' ); ?>
</td>
</tr>
<tr>
<th scope="row"><label for="default_category"><?php _e('Default Post Category') ?></label></th>
<td>
<?php
wp_dropdown_categories(array('hide_empty' => 0, 'name' => 'default_category', 'orderby' => 'name', 'selected' => get_option('default_category'), 'hierarchical' => true));
?>
</td>
</tr>
<?php
$post_formats = get_post_format_strings();
unset( $post_formats['standard'] );
?>
<tr>
<th scope="row"><label for="default_post_format"><?php _e('Default Post Format') ?></label></th>
<td>
	<select name="default_post_format" id="default_post_format">
		<option value="0"><?php echo get_post_format_string( 'standard' ); ?></option>
<?php foreach ( $post_formats as $format_slug => $format_name ): ?>
		<option<?php selected( get_option( 'default_post_format' ), $format_slug ); ?> value="<?php echo esc_attr( $format_slug ); ?>"><?php echo esc_html( $format_name ); ?></option>
<?php endforeach; ?>
	</select>
</td>
</tr>

<?php
do_settings_fields('contents', 'default');
?>
</table>


<?php
$limit_options = array(
	'0'			=> __( 'no revisions'),
	'1'			=> __( '1 revision'),
	'2' 		=>   sprintf(__('%d revisions'), 2),
	'3'			=>   sprintf(__('%d revisions'), 3),
	'4'			=>   sprintf(__('%d revisions'), 4),
	'5'			=>   sprintf(__('%d revisions'), 5),
	'10'		=>   sprintf(__('%d revisions'), 10),
	'20'		=>   sprintf(__('%d revisions'), 20),
	'50'		=>   sprintf(__('%d revisions'), 50)
);
?>
<table class="form-table">
<tr>
<th scope="row"><label for="limit_revisions"><?php _e('Limit post revisions') ?></label></th>
<td>
	<select name="limit_revisions" id="limit_revisions">
		<option value="-1"><?php echo _e( 'RapidoPress default (unlimited revisions)'); ?></option>
<?php foreach ( $limit_options as $limit => $limit_name ): ?>
		<option<?php selected( get_option( 'limit_revisions' ), $limit ); ?> value="<?php echo esc_attr( $limit_name ); ?>"><?php echo esc_html( $limit_name); ?></option>
<?php endforeach; ?>
	</select>
<p class="description" id="limit_revisions-description"><?php _e( 'Limit the number of revisions that RapidoPress keeps for each post type. By default, an infinite number of revisions are stored if a post type supports revisions. Keep in mind that if you restrict this number, RapidoPress will purge the older revisions only after the post is updated.' ); ?></p>
</td>
</tr>
</table>

<?php do_settings_sections('contents'); ?>

<?php submit_button(); ?>
</form>
</div>

<?php include( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
