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

$title = __('Localization Settings');
$parent_file = 'options-general.php';
/* translators: date and time format for exact current time, mainly about timezones, see http://php.net/date */
$timezone_format = _x('Y-m-d H:i:s', 'timezone date format');

/**
 * Display JavaScript on the page.
 *
 * @since 3.5.0
 */
function options_localization_add_js() {
?>
<script type="text/javascript">
	jQuery(document).ready(function($){
		$("input[name='date_format']").click(function(){
			if ( "date_format_custom_radio" != $(this).attr("id") )
				$( "input[name='date_format_custom']" ).val( $( this ).val() ).siblings( '.example' ).text( $( this ).parent( 'label' ).text() );
		});
		$("input[name='date_format_custom']").focus(function(){
			$( '#date_format_custom_radio' ).prop( 'checked', true );
		});

		$("input[name='time_format']").click(function(){
			if ( "time_format_custom_radio" != $(this).attr("id") )
				$( "input[name='time_format_custom']" ).val( $( this ).val() ).siblings( '.example' ).text( $( this ).parent( 'label' ).text() );
		});
		$("input[name='time_format_custom']").focus(function(){
			$( '#time_format_custom_radio' ).prop( 'checked', true );
		});
		$("input[name='date_format_custom'], input[name='time_format_custom']").change( function() {
			var format = $(this);
			format.siblings( '.spinner' ).addClass( 'is-active' );
			$.post(ajaxurl, {
					action: 'date_format_custom' == format.attr('name') ? 'date_format' : 'time_format',
					date : format.val()
				}, function(d) { format.siblings( '.spinner' ).removeClass( 'is-active' ); format.siblings('.example').text(d); } );
		});

		var languageSelect = $( '#WPLANG' );
		$( 'form' ).submit( function() {
			// Don't show a spinner for English and installed languages,
			// as there is nothing to download.
			if ( ! languageSelect.find( 'option:selected' ).data( 'installed' ) ) {
				$( '#submit', this ).after( '<span class="spinner language-install-spinner" />' );
			}
		});
	});
</script>
<?php
}
add_action('admin_head', 'options_localization_add_js');


include( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
<h2><?php echo esc_html( $title ); ?></h2>

<form method="post" action="options.php" novalidate="novalidate">
<?php settings_fields('localization'); 

?>

<table class="form-table">
<?php
$languages = get_available_languages();
$translations = wp_get_available_translations();
if (  defined( 'WPLANG' ) && '' !== WPLANG && 'en_US' !== WPLANG && ! in_array( WPLANG, $languages ) ) {
	$languages[] = WPLANG;
}
if ( ! empty( $languages ) || ! empty( $translations ) ) {
	?>
	<tr>
		<th width="33%" scope="row"><label for="WPLANG"><?php _e( 'Site Language' ); ?></label></th>
		<td>
			<?php
			$locale = get_locale();
			if ( ! in_array( $locale, $languages ) ) {
				$locale = '';
			}

			wp_dropdown_languages( array(
				'name'         => 'WPLANG',
				'id'           => 'WPLANG',
				'selected'     => $locale,
				'languages'    => $languages,
				'translations' => $translations,
				'show_available_translations' => wp_can_install_language_pack(),
			) );

			// Add note about deprecated WPLANG constant.
			if ( defined( 'WPLANG' ) && ( '' !== WPLANG ) && $locale !== WPLANG ) {
				if ( is_super_admin() ) {
					?>
					<p class="description">
						<strong><?php _e( 'Note:' ); ?></strong> <?php printf( __( 'The %s constant in your %s file is no longer needed.' ), '<code>WPLANG</code>', '<code>wp-config.php</code>' ); ?>
					</p>
					<?php
				}
				_deprecated_argument( 'define()', '4.0', sprintf( __( 'The %s constant in your %s file is no longer needed.' ), 'WPLANG', 'wp-config.php' ) );
			}
			?>
		</td>
	</tr>
	<?php
}
?>
<tr>
<?php
$current_offset = get_option('gmt_offset');
$tzstring = get_option('timezone_string');

$check_zone_info = true;

// Remove old Etc mappings. Fallback to gmt_offset.
if ( false !== strpos($tzstring,'Etc/GMT') )
	$tzstring = '';

if ( empty($tzstring) ) { // Create a UTC+- zone if no timezone string exists
	$check_zone_info = false;
	if ( 0 == $current_offset )
		$tzstring = 'UTC+0';
	elseif ($current_offset < 0)
		$tzstring = 'UTC' . $current_offset;
	else
		$tzstring = 'UTC+' . $current_offset;
}

?>
<th scope="row"><label for="timezone_string"><?php _e('Timezone') ?></label></th>
<td>

<select id="timezone_string" name="timezone_string" aria-describedby="timezone-description">
<?php echo wp_timezone_choice($tzstring); ?>
</select>

	<span id="utc-time"><?php printf(__('<abbr title="Coordinated Universal Time">UTC</abbr> time is <code>%s</code>'), date_i18n($timezone_format, false, 'gmt')); ?></span>
<?php if ( get_option('timezone_string') || !empty($current_offset) ) : ?>
	<span id="local-time"><?php printf(__('Local time is <code>%1$s</code>'), date_i18n($timezone_format)); ?></span>
<?php endif; ?>
<p class="description" id="timezone-description"><?php _e( 'Choose a city in the same timezone as you.' ); ?></p>
<?php if ($check_zone_info && $tzstring) : ?>
<br />
<span>
	<?php
	// Set TZ so localtime works.
	date_default_timezone_set($tzstring);
	$now = localtime(time(), true);
	if ( $now['tm_isdst'] )
		_e('This timezone is currently in daylight saving time.');
	else
		_e('This timezone is currently in standard time.');
	?>
	<br />
	<?php
	$allowed_zones = timezone_identifiers_list();

	if ( in_array( $tzstring, $allowed_zones) ) {
		$found = false;
		$date_time_zone_selected = new DateTimeZone($tzstring);
		$tz_offset = timezone_offset_get($date_time_zone_selected, date_create());
		$right_now = time();
		foreach ( timezone_transitions_get($date_time_zone_selected) as $tr) {
			if ( $tr['ts'] > $right_now ) {
			    $found = true;
				break;
			}
		}

		if ( $found ) {
			echo ' ';
			$message = $tr['isdst'] ?
				__('Daylight saving time begins on: <code>%s</code>.') :
				__('Standard time begins on: <code>%s</code>.');
			// Add the difference between the current offset and the new offset to ts to get the correct transition time from date_i18n().
			printf( $message, date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $tr['ts'] + ($tz_offset - $tr['offset']) ) );
		} else {
			_e('This timezone does not observe daylight saving time.');
		}
	}
	// Set back to UTC.
	date_default_timezone_set('UTC');
	?>
	</span>
<?php endif; ?>
</td>
</tr>
<?php
/**
 * Render the blog charset setting.
 *
 * @since 3.5.0
 */

function options_localization_blog_charset() {
	echo '<input name="blog_charset" type="text" id="blog_charset" value="' . esc_attr( get_option( 'blog_charset' ) ) . '" class="regular-text" />';
	echo '<p class="description">' . __( 'The <a href="https://codex.wordpress.org/Glossary#Character_set">character encoding</a> of your site (UTF-8 is recommended)' ) . '</p>';
}


 if ( ! in_array( get_option( 'blog_charset' ), array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ) ) )
	add_settings_field( 'blog_charset', __( 'Encoding for pages and feeds' ), 'options_localization_blog_charset', 'localization', 'default', array( 'label_for' => 'blog_charset' ) );

?>

<tr>
<th scope="row"><?php _e('Date Format') ?></th>
<td>
	<fieldset><legend class="screen-reader-text"><span><?php _e('Date Format') ?></span></legend>
<?php
	/**
	* Filter the default date formats.
	*
	* @since 2.7.0
	* @since 4.0.0 Added ISO date standard YYYY-MM-DD format.
	*
	* @param array $default_date_formats Array of default date formats.
	*/
	$date_formats = array_unique( apply_filters( 'date_formats', array( __( 'F j, Y' ), 'Y-m-d', 'm/d/Y', 'd/m/Y' ) ) );

	$custom = true;

	foreach ( $date_formats as $format ) {
		echo "\t<label title='" . esc_attr($format) . "'><input type='radio' name='date_format' value='" . esc_attr($format) . "'";
		if ( get_option('date_format') === $format ) { // checked() uses "==" rather than "==="
			echo " checked='checked'";
			$custom = false;
		}
		echo ' /> ' . date_i18n( $format ) . "</label><br />\n";
	}

	echo '	<label><input type="radio" name="date_format" id="date_format_custom_radio" value="\c\u\s\t\o\m"';
	checked( $custom );
	echo '/> ' . __( 'Custom:' ) . '<span class="screen-reader-text"> ' . __( 'enter a custom date format in the following field' ) . "</span></label>\n";
	echo '<label for="date_format_custom" class="screen-reader-text">' . __( 'Custom date format:' ) . '</label><input type="text" name="date_format_custom" id="date_format_custom" value="' . esc_attr( get_option('date_format') ) . '" class="small-text" /> <span class="screen-reader-text">' . __( 'example:' ) . ' </span><span class="example"> ' . date_i18n( get_option('date_format') ) . "</span> <span class='spinner'></span>\n";
?>
	</fieldset>
</td>
</tr>
<tr>
<th scope="row"><?php _e('Time Format') ?></th>
<td>
	<fieldset><legend class="screen-reader-text"><span><?php _e('Time Format') ?></span></legend>
<?php
	/**
	* Filter the default time formats.
	*
	* @since 2.7.0
	*
	* @param array $default_time_formats Array of default time formats.
	*/
	$time_formats = array_unique( apply_filters( 'time_formats', array( __( 'g:i a' ), 'g:i A', 'H:i' ) ) );

	$custom = true;

	foreach ( $time_formats as $format ) {
		echo "\t<label title='" . esc_attr($format) . "'><input type='radio' name='time_format' value='" . esc_attr($format) . "'";
		if ( get_option('time_format') === $format ) { // checked() uses "==" rather than "==="
			echo " checked='checked'";
			$custom = false;
		}
		echo ' /> ' . date_i18n( $format ) . "</label><br />\n";
	}

	echo '	<label><input type="radio" name="time_format" id="time_format_custom_radio" value="\c\u\s\t\o\m"';
	checked( $custom );
	echo '/> ' . __( 'Custom:' ) . '<span class="screen-reader-text"> ' . __( 'enter a custom time format in the following field' ) . "</span></label>\n";
	echo '<label for="time_format_custom" class="screen-reader-text">' . __( 'Custom time format:' ) . '</label><input type="text" name="time_format_custom" id="time_format_custom" value="' . esc_attr( get_option('time_format') ) . '" class="small-text" /> <span class="screen-reader-text">' . __( 'example:' ) . ' </span><span class="example"> ' . date_i18n( get_option('time_format') ) . "</span> <span class='spinner'></span>\n";

	echo "\t<p>" . __('<a href="https://codex.wordpress.org/Formatting_Date_and_Time">Documentation on date and time formatting</a>.') . "</p>\n";
?>
	</fieldset>
</td>
</tr>
<tr>
<th scope="row"><label for="start_of_week"><?php _e('Week Starts On') ?></label></th>
<td><select name="start_of_week" id="start_of_week">
<?php
global $wp_locale;

for ($day_index = 0; $day_index <= 6; $day_index++) :
	$selected = (get_option('start_of_week') == $day_index) ? 'selected="selected"' : '';
	echo "\n\t<option value='" . esc_attr($day_index) . "' $selected>" . $wp_locale->get_weekday($day_index) . '</option>';
endfor;
?>
</select></td>
</tr>
<?php do_settings_fields('localization', 'default'); ?>
</table>

<?php do_settings_sections('localization'); ?>

<?php submit_button(); ?>
</form>

</div>

<?php include( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
