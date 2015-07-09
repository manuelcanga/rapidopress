<?php
/**
 * Manage media uploaded file.
 *
 * There are many filters in here for media. Plugins can extend functionality
 * by hooking into the filters.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** Load WordPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

if (!current_user_can('upload_files'))
	wp_die(__('You do not have permission to upload files.'));

wp_enqueue_script('plupload-handlers');

$post_id = 0;
if ( isset( $_REQUEST['post_id'] ) ) {
	$post_id = absint( $_REQUEST['post_id'] );
	if ( ! get_post( $post_id ) || ! current_user_can( 'edit_post', $post_id ) )
		$post_id = 0;
}

if ( $_POST ) {
	$location = 'upload.php';
	if ( isset($_POST['html-upload']) && !empty($_FILES) ) {
		check_admin_referer('media-form');
		// Upload File button was clicked
		$id = media_handle_upload( 'async-upload', $post_id );
		if ( is_wp_error( $id ) )
			$location .= '?message=3';
	}
	wp_redirect( admin_url( $location ) );
	exit;
}

$title = __('Upload New Media');
$parent_file = 'upload.php';



require_once( ABSPATH . 'wp-admin/admin-header.php' );

$form_class = 'media-upload-form type-form validate';

if ( get_user_setting('uploader') || isset( $_GET['browser-uploader'] ) )
	$form_class .= ' html-uploader';
?>
<div class="wrap">
	<h2><?php echo esc_html( $title ); ?></h2>

	<form enctype="multipart/form-data" method="post" action="<?php echo admin_url('media-new.php'); ?>" class="<?php echo esc_attr( $form_class ); ?>" id="file-form">

	<?php media_upload_form(); ?>

	<script type="text/javascript">
	var post_id = <?php echo $post_id; ?>, shortform = 3;
	</script>
	<input type="hidden" name="post_id" id="post_id" value="<?php echo $post_id; ?>" />
	<?php wp_nonce_field('media-form'); ?>
	<div id="media-items" class="hide-if-no-js"></div>
	</form>
</div>

<?php
include( ABSPATH . 'wp-admin/admin-footer.php' );
