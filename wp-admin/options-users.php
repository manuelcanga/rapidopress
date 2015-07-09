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
<tr class="avatar-settings<?php if ( ! $show_avatars ) echo ' hide-if-js'; ?>">
<th scope="row"><?php _e('Maximum Rating'); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e('Maximum Rating'); ?></span></legend>

<?php
$ratings = array(
	/* translators: Content suitability rating: http://bit.ly/89QxZA */
	'G' => __('G &#8212; Suitable for all audiences'),
	/* translators: Content suitability rating: http://bit.ly/89QxZA */
	'PG' => __('PG &#8212; Possibly offensive, usually for audiences 13 and above'),
	/* translators: Content suitability rating: http://bit.ly/89QxZA */
	'R' => __('R &#8212; Intended for adult audiences above 17'),
	/* translators: Content suitability rating: http://bit.ly/89QxZA */
	'X' => __('X &#8212; Even more mature than above')
);
foreach ($ratings as $key => $rating) :
	$selected = (get_option('avatar_rating') == $key) ? 'checked="checked"' : '';
	echo "\n\t<label><input type='radio' name='avatar_rating' value='" . esc_attr($key) . "' $selected/> $rating</label><br />";
endforeach;
?>

</fieldset></td>
</tr>
<tr class="avatar-settings<?php if ( ! $show_avatars ) echo ' hide-if-js'; ?>">
<th scope="row"><?php _e('Default Avatar'); ?></th>
<td class="defaultavatarpicker"><fieldset><legend class="screen-reader-text"><span><?php _e('Default Avatar'); ?></span></legend>

<?php _e('For users without a custom avatar of their own, you can either display a generic logo or a generated one based on their e-mail address.'); ?><br />

<?php
$avatar_defaults = array(
	'mystery' => __('Mystery Person'),
	'blank' => __('Blank'),
	'gravatar_default' => __('Gravatar Logo'),
	'identicon' => __('Identicon (Generated)'),
	'wavatar' => __('Wavatar (Generated)'),
	'monsterid' => __('MonsterID (Generated)'),
	'retro' => __('Retro (Generated)')
);
/**
 * Filter the default avatars.
 *
 * Avatars are stored in key/value pairs, where the key is option value,
 * and the name is the displayed avatar name.
 *
 * @since 2.6.0
 *
 * @param array $avatar_defaults Array of default avatars.
 */
$avatar_defaults = apply_filters( 'avatar_defaults', $avatar_defaults );
$default = get_option('avatar_default');
if ( empty($default) )
	$default = 'mystery';
$size = 32;
$avatar_list = '';

// Force avatars on to display these choices
add_filter( 'pre_option_show_avatars', '__return_true', 100 );

foreach ( $avatar_defaults as $default_key => $default_name ) {
	$selected = ($default == $default_key) ? 'checked="checked" ' : '';
	$avatar_list .= "\n\t<label><input type='radio' name='avatar_default' id='avatar_{$default_key}' value='" . esc_attr($default_key) . "' {$selected}/> ";

	$avatar = get_avatar( $user_email, $size, $default_key );
	$avatar = preg_replace( "/src='(.+?)'/", "src='\$1&amp;forcedefault=1'", $avatar );
	$avatar = preg_replace( "/srcset='(.+?) 2x'/", "srcset='\$1&amp;forcedefault=1 2x'", $avatar );
	$avatar_list .= $avatar;

	$avatar_list .= ' ' . $default_name . '</label>';
	$avatar_list .= '<br />';
}

remove_filter( 'pre_option_show_avatars', '__return_true', 100 );

/**
 * Filter the HTML output of the default avatar list.
 *
 * @since 2.6.0
 *
 * @param string $avatar_list HTML markup of the avatar list.
 */
echo apply_filters( 'default_avatar_select', $avatar_list );
?>

</fieldset></td>
</tr>
<?php do_settings_fields('users', 'avatars'); ?>
</table>

<?php do_settings_sections('users'); ?>

<?php submit_button(); ?>
</form>

</div>

<?php include( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
