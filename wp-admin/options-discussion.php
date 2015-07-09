<?php
/**
 * Discussion settings administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */
/** WordPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

if ( ! current_user_can( 'manage_options' ) )
	wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );

$title = __('Discussion Settings');
$parent_file = 'options-general.php';




include( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
<h2><?php echo esc_html( $title ); ?></h2>

<form method="post" action="options.php">
<?php settings_fields('discussion'); ?>

<table class="form-table">
<tr>
<th scope="row"><?php _e('Default article settings'); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e('Default article settings'); ?></span></legend>
<label for="default_comment_status">
<input name="default_comment_status" type="checkbox" id="default_comment_status" value="open" <?php checked('open', get_option('default_comment_status')); ?> />
<?php _e('Allow people to post comments on new articles'); ?></label>
<br />
<p class="description"><?php echo '(' . __( 'These settings may be overridden for individual articles.' ) . ')'; ?></p>
</fieldset></td>
</tr>
<tr>
<th scope="row"><?php _e('Other comment settings'); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e('Other comment settings'); ?></span></legend>
<label for="require_name_email"><input type="checkbox" name="require_name_email" id="require_name_email" value="1" <?php checked('1', get_option('require_name_email')); ?> /> <?php _e('Comment author must fill out name and e-mail'); ?></label>
<br />
<label for="comment_registration">
<input name="comment_registration" type="checkbox" id="comment_registration" value="1" <?php checked('1', get_option('comment_registration')); ?> />
<?php _e('Users must be registered and logged in to comment'); ?>
</label>
<br />

<label for="close_comments_for_old_posts">
<input name="close_comments_for_old_posts" type="checkbox" id="close_comments_for_old_posts" value="1" <?php checked('1', get_option('close_comments_for_old_posts')); ?> />
<?php printf( __('Automatically close comments on articles older than %s days'), '</label><label for="close_comments_days_old"><input name="close_comments_days_old" type="number" min="0" step="1" id="close_comments_days_old" value="' . esc_attr(get_option('close_comments_days_old')) . '" class="small-text" />'); ?>
</label>
<br />
<label for="thread_comments">
<input name="thread_comments" type="checkbox" id="thread_comments" value="1" <?php checked('1', get_option('thread_comments')); ?> />
<?php
/**
 * Filter the maximum depth of threaded/nested comments.
 *
 * @since 2.7.0.
 *
 * @param int $max_depth The maximum depth of threaded comments. Default 10.
 */
$maxdeep = (int) apply_filters( 'thread_comments_depth_max', 10 );

$thread_comments_depth = '</label><label for="thread_comments_depth"><select name="thread_comments_depth" id="thread_comments_depth">';
for ( $i = 2; $i <= $maxdeep; $i++ ) {
	$thread_comments_depth .= "<option value='" . esc_attr($i) . "'";
	if ( get_option('thread_comments_depth') == $i ) $thread_comments_depth .= " selected='selected'";
	$thread_comments_depth .= ">$i</option>";
}
$thread_comments_depth .= '</select>';

printf( __('Enable threaded (nested) comments %s levels deep'), $thread_comments_depth );

?></label>
<br />
<label for="page_comments">
<input name="page_comments" type="checkbox" id="page_comments" value="1" <?php checked('1', get_option('page_comments')); ?> />
<?php

$default_comments_page = '</label><label for="default_comments_page"><select name="default_comments_page" id="default_comments_page"><option value="newest"';
if ( 'newest' == get_option('default_comments_page') ) $default_comments_page .= ' selected="selected"';
$default_comments_page .= '>' . __('last') . '</option><option value="oldest"';
if ( 'oldest' == get_option('default_comments_page') ) $default_comments_page .= ' selected="selected"';
$default_comments_page .= '>' . __('first') . '</option></select>';

printf( __('Break comments into pages with %1$s top level comments per page and the %2$s page displayed by default'), '</label><label for="comments_per_page"><input name="comments_per_page" type="number" step="1" min="0" id="comments_per_page" value="' . esc_attr(get_option('comments_per_page')) . '" class="small-text" />', $default_comments_page );

?></label>
<br />
<label for="comment_order"><?php

$comment_order = '<select name="comment_order" id="comment_order"><option value="asc"';
if ( 'asc' == get_option('comment_order') ) $comment_order .= ' selected="selected"';
$comment_order .= '>' . __('older') . '</option><option value="desc"';
if ( 'desc' == get_option('comment_order') ) $comment_order .= ' selected="selected"';
$comment_order .= '>' . __('newer') . '</option></select>';

printf( __('Comments should be displayed with the %s comments at the top of each page'), $comment_order );

?></label>
</fieldset></td>
</tr>
<tr>
<th scope="row"><?php _e('E-mail me whenever'); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e('E-mail me whenever'); ?></span></legend>
<label for="comments_notify">
<input name="comments_notify" type="checkbox" id="comments_notify" value="1" <?php checked('1', get_option('comments_notify')); ?> />
<?php _e('Anyone posts a comment'); ?> </label>
<br />
<label for="moderation_notify">
<input name="moderation_notify" type="checkbox" id="moderation_notify" value="1" <?php checked('1', get_option('moderation_notify')); ?> />
<?php _e('A comment is held for moderation'); ?> </label>
</fieldset></td>
</tr>
<tr>
<th scope="row"><?php _e('Before a comment appears'); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e('Before a comment appears'); ?></span></legend>
<label for="comment_moderation">
<input name="comment_moderation" type="checkbox" id="comment_moderation" value="1" <?php checked('1', get_option('comment_moderation')); ?> />
<?php _e('Comment must be manually approved'); ?> </label>
<br />
<label for="comment_whitelist"><input type="checkbox" name="comment_whitelist" id="comment_whitelist" value="1" <?php checked('1', get_option('comment_whitelist')); ?> /> <?php _e('Comment author must have a previously approved comment'); ?></label>
</fieldset></td>
</tr>
<tr>
<th scope="row"><?php _e('Comment Moderation'); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e('Comment Moderation'); ?></span></legend>
<p><label for="comment_max_links"><?php printf(__('Hold a comment in the queue if it contains %s or more links. (A common characteristic of comment spam is a large number of hyperlinks.)'), '<input name="comment_max_links" type="number" step="1" min="0" id="comment_max_links" value="' . esc_attr(get_option('comment_max_links')) . '" class="small-text" />' ); ?></label></p>

<p><label for="moderation_keys"><?php _e('When a comment contains any of these words in its content, name, URL, e-mail, or IP, it will be held in the <a href="edit-comments.php?comment_status=moderated">moderation queue</a>. One word or IP per line. It will match inside words, so &#8220;press&#8221; will match &#8220;WordPress&#8221;.'); ?></label></p>
<p>
<textarea name="moderation_keys" rows="10" cols="50" id="moderation_keys" class="large-text code"><?php echo esc_textarea( get_option( 'moderation_keys' ) ); ?></textarea>
</p>
</fieldset></td>
</tr>
<tr>
<th scope="row"><?php _e('Comment Blacklist'); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e('Comment Blacklist'); ?></span></legend>
<p><label for="blacklist_keys"><?php _e('When a comment contains any of these words in its content, name, URL, e-mail, or IP, it will be marked as spam. One word or IP per line. It will match inside words, so &#8220;press&#8221; will match &#8220;WordPress&#8221;.'); ?></label></p>
<p>
<textarea name="blacklist_keys" rows="10" cols="50" id="blacklist_keys" class="large-text code"><?php echo esc_textarea( get_option( 'blacklist_keys' ) ); ?></textarea>
</p>
</fieldset></td>
</tr>
<?php do_settings_fields('discussion', 'default'); ?>
</table>

<?php do_settings_sections('discussion'); ?>

<?php submit_button(); ?>
</form>
</div>

<?php include( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
