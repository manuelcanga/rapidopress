<?php
/**
 * BackPress Styles Procedural API
 *
 * @since 2.6.0
 *
 * @package WordPress
 * @subpackage BackPress
 */



/**
 * Initialize $wp_styles if it has not been set.
 *
 * @global WP_Styles $wp_styles
 *
 * @since 4.2.0
 *
 * @return WP_Styles WP_Styles instance.
 */
function wp_styles() {
	global $wp_styles;
	if ( ! ( $wp_styles instanceof WP_Styles ) ) {
		$wp_styles = new WP_Styles();
	}
	return $wp_styles;
}

/**
 * Display styles that are in the $handles queue.
 *
 * Passing an empty array to $handles prints the queue,
 * passing an array with one string prints that style,
 * and passing an array of strings prints those styles.
 *
 * @since 2.6.0
 *
 * @param string|bool|array $handles Styles to be printed. Default 'false'.
 * @return array On success, a processed array of WP_Dependencies items; otherwise, an empty array.
 */
function wp_print_styles( $handles = false ) {
	if ( '' === $handles ) { // for wp_head
		$handles = false;
	}
	/**
	 * Fires before styles in the $handles queue are printed.
	 *
	 * @since 2.6.0
	 */
	if ( ! $handles ) {
		do_action( 'wp_print_styles' );
		return array(); // No need to instantiate if nothing is there.
	}

	_wp_scripts_maybe_doing_it_wrong( __FUNCTION__ );

	return wp_styles()->do_items( $handles );
}

/**
 * Add extra CSS styles to a registered stylesheet.
 *
 * Styles will only be added if the stylesheet in already in the queue.
 * Accepts a string $data containing the CSS. If two or more CSS code blocks
 * are added to the same stylesheet $handle, they will be printed in the order
 * they were added, i.e. the latter added styles can redeclare the previous.
 *
 * @see WP_Styles::add_inline_style()
 *
 * @since 3.3.0
 *
 * @param string $handle Name of the stylesheet to add the extra styles to. Must be lowercase.
 * @param string $data   String containing the CSS styles to be added.
 * @return bool True on success, false on failure.
 */
function wp_add_inline_style( $handle, $data ) {
	_wp_scripts_maybe_doing_it_wrong( __FUNCTION__ );

	if ( false !== stripos( $data, '</style>' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Do not pass style tags to wp_add_inline_style().' ), '3.7' );
		$data = trim( preg_replace( '#<style[^>]*>(.*)</style>#is', '$1', $data ) );
	}

	return wp_styles()->add_inline_style( $handle, $data );
}

/**
 * Register a CSS stylesheet.
 *
 * @see WP_Dependencies::add()
 * @link http://www.w3.org/TR/CSS2/media.html#media-types List of CSS media types.
 *
 * @since 4.3.0 A return value was added. 
 *
 * @param string      $handle Name of the stylesheet.
 * @param string|bool $src    Path to the stylesheet from the WordPress root directory. Example: '/css/mystyle.css'.
 * @param array       $deps   An array of registered style handles this stylesheet depends on. Default empty array.
 * @param string|bool $ver    String specifying the stylesheet version number. Used to ensure that the correct version
 *                            is sent to the client regardless of caching. Default 'false'. Accepts 'false', 'null', or 'string'.
 * @param string      $media  Optional. The media for which this stylesheet has been defined.
 *                            Default 'all'. Accepts 'all', 'aural', 'braille', 'handheld', 'projection', 'print',
 *                            'screen', 'tty', or 'tv'.
 * @return bool Whether the style has been registered. True on success, false on failure. 
 */
function wp_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
	_wp_scripts_maybe_doing_it_wrong( __FUNCTION__ );

    return wp_styles()->add( $handle, $src, $deps, $ver, $media ); 
}

/**
 * Remove a registered stylesheet.
 *
 * @see WP_Dependencies::remove()
 *
 * @since 2.1.0
 *
 * @param string $handle Name of the stylesheet to be removed.
 */
function wp_deregister_style( $handle ) {
	_wp_scripts_maybe_doing_it_wrong( __FUNCTION__ );

	wp_styles()->remove( $handle );
}

/**
 * Enqueue a CSS stylesheet.
 *
 * Registers the style if source provided (does NOT overwrite) and enqueues.
 *
 * @see WP_Dependencies::add(), WP_Dependencies::enqueue()
 * @link http://www.w3.org/TR/CSS2/media.html#media-types List of CSS media types.
 *
 * @since 2.6.0
 *
 * @param string      $handle Name of the stylesheet.
 * @param string|bool $src    Path to the stylesheet from the root directory of WordPress. Example: '/css/mystyle.css'.
 * @param array       $deps   An array of registered style handles this stylesheet depends on. Default empty array.
 * @param string|bool $ver    String specifying the stylesheet version number, if it has one. This parameter is used
 *                            to ensure that the correct version is sent to the client regardless of caching, and so
 *                            should be included if a version number is available and makes sense for the stylesheet.
 * @param string      $media  Optional. The media for which this stylesheet has been defined.
 *                            Default 'all'. Accepts 'all', 'aural', 'braille', 'handheld', 'projection', 'print',
 *                            'screen', 'tty', or 'tv'.
 */
function wp_enqueue_style( $handle, $src = false, $deps = array(), $ver = false, $media = 'all' ) {
	_wp_scripts_maybe_doing_it_wrong( __FUNCTION__ );

	$wp_styles = wp_styles();

	if ( $src ) {
		$_handle = explode('?', $handle);
		$wp_styles->add( $_handle[0], $src, $deps, $ver, $media );
	}
	$wp_styles->enqueue( $handle );
}

/**
 * Remove a previously enqueued CSS stylesheet.
 *
 * @see WP_Dependencies::dequeue()
 *
 * @since 3.1.0
 *
 * @param string $handle Name of the stylesheet to be removed.
 */
function wp_dequeue_style( $handle ) {
	_wp_scripts_maybe_doing_it_wrong( __FUNCTION__ );

	wp_styles()->dequeue( $handle );
}

/**
 * Check whether a CSS stylesheet has been added to the queue.
 *
 * @global WP_Styles $wp_styles The WP_Styles object for printing styles.
 *
 * @since 2.8.0
 *
 * @param string $handle Name of the stylesheet.
 * @param string $list   Optional. Status of the stylesheet to check. Default 'enqueued'.
 *                       Accepts 'enqueued', 'registered', 'queue', 'to_do', and 'done'.
 * @return bool Whether style is queued.
 */
function wp_style_is( $handle, $list = 'enqueued' ) {
	_wp_scripts_maybe_doing_it_wrong( __FUNCTION__ );

	return (bool) wp_styles()->query( $handle, $list );
}

/**
 * Add metadata to a CSS stylesheet.
 *
 * Works only if the stylesheet has already been added.
 *
 * Possible values for $key and $value:
 * 'conditional' string      Comments for IE 6, lte IE 7 etc.
 * 'rtl'         bool|string To declare an RTL stylesheet.
 * 'suffix'      string      Optional suffix, used in combination with RTL.
 * 'alt'         bool        For rel="alternate stylesheet".
 * 'title'       string      For preferred/alternate stylesheets.
 *
 * @see WP_Dependency::add_data()
 *
 * @since 3.6.0
 *
 * @param string $handle Name of the stylesheet.
 * @param string $key    Name of data point for which we're storing a value.
 *                       Accepts 'conditional', 'rtl' and 'suffix', 'alt' and 'title'.
 * @param mixed  $value  String containing the CSS data to be added.
 * @return bool True on success, false on failure.
 */
function wp_style_add_data( $handle, $key, $value ) {
	return wp_styles()->add_data( $handle, $key, $value );
}



/**
 * Registers an admin colour scheme css file.
 *
 * Allows a plugin to register a new admin colour scheme. For example:
 *
 *     wp_admin_css_color( 'classic', __( 'Classic' ), admin_url( "css/colors-classic.css" ), array(
 *         '#07273E', '#14568A', '#D54E21', '#2683AE'
 *     ) );
 *
 * @since 2.5.0
 *
 * @todo Properly document optional arguments as such
 *
 * @param string $key The unique key for this theme.
 * @param string $name The name of the theme.
 * @param string $url The url of the css file containing the colour scheme.
 * @param array $colors Optional An array of CSS color definitions which are used to give the user a feel for the theme.
 * @param array $icons Optional An array of CSS color definitions used to color any SVG icons
 */
function wp_admin_css_color( $key, $name, $url, $colors = array(), $icons = array() ) {
	global $_wp_admin_css_colors;

	if ( !isset($_wp_admin_css_colors) )
		$_wp_admin_css_colors = array();

	$_wp_admin_css_colors[$key] = (object) array(
		'name' => $name,
		'url' => $url,
		'colors' => $colors,
		'icon_colors' => $icons,
	);
}


/**
 * Registers the default Admin color schemes
 *
 * @since 3.0.0
 */
function register_admin_color_schemes() {
	$suffix = is_rtl() ? '-rtl' : '';
	$suffix .= defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	wp_admin_css_color( 'fresh', _x( 'Default', 'admin color scheme' ),
		false,
		array( '#222', '#333', '#0073aa', '#00a0d2' ),
		array( 'base' => '#999', 'focus' => '#00a0d2', 'current' => '#fff' )
	);

	// Other color schemes are not available when running out of src
	if ( false !== strpos( $GLOBALS['wp_version'], '-src' ) )
		return;

	wp_admin_css_color( 'light', _x( 'Light', 'admin color scheme' ),
		admin_url( "css/colors/light/colors$suffix.css" ),
		array( '#e5e5e5', '#999', '#d64e07', '#04a4cc' ),
		array( 'base' => '#999', 'focus' => '#ccc', 'current' => '#ccc' )
	);

	wp_admin_css_color( 'blue', _x( 'Blue', 'admin color scheme' ),
		admin_url( "css/colors/blue/colors$suffix.css" ),
		array( '#096484', '#4796b3', '#52accc', '#74B6CE' ),
		array( 'base' => '#e5f8ff', 'focus' => '#fff', 'current' => '#fff' )
	);

	wp_admin_css_color( 'midnight', _x( 'Midnight', 'admin color scheme' ),
		admin_url( "css/colors/midnight/colors$suffix.css" ),
		array( '#25282b', '#363b3f', '#69a8bb', '#e14d43' ),
		array( 'base' => '#f1f2f3', 'focus' => '#fff', 'current' => '#fff' )
	);

	wp_admin_css_color( 'sunrise', _x( 'Sunrise', 'admin color scheme' ),
		admin_url( "css/colors/sunrise/colors$suffix.css" ),
		array( '#b43c38', '#cf4944', '#dd823b', '#ccaf0b' ),
		array( 'base' => '#f3f1f1', 'focus' => '#fff', 'current' => '#fff' )
	);

	wp_admin_css_color( 'ectoplasm', _x( 'Ectoplasm', 'admin color scheme' ),
		admin_url( "css/colors/ectoplasm/colors$suffix.css" ),
		array( '#413256', '#523f6d', '#a3b745', '#d46f15' ),
		array( 'base' => '#ece6f6', 'focus' => '#fff', 'current' => '#fff' )
	);

	wp_admin_css_color( 'ocean', _x( 'Ocean', 'admin color scheme' ),
		admin_url( "css/colors/ocean/colors$suffix.css" ),
		array( '#627c83', '#738e96', '#9ebaa0', '#aa9d88' ),
		array( 'base' => '#f2fcff', 'focus' => '#fff', 'current' => '#fff' )
	);

	wp_admin_css_color( 'coffee', _x( 'Coffee', 'admin color scheme' ),
		admin_url( "css/colors/coffee/colors$suffix.css" ),
		array( '#46403c', '#59524c', '#c7a589', '#9ea476' ),
		array( 'base' => '#f3f2f1', 'focus' => '#fff', 'current' => '#fff' )
	);
}


/**
 * Display the URL of a WordPress admin CSS file.
 *
 * @see WP_Styles::_css_href and its style_loader_src filter.
 *
 * @since 2.3.0
 *
 * @param string $file file relative to wp-admin/ without its ".css" extension.
 */
function wp_admin_css_uri( $file = 'wp-admin' ) {
	if ( defined('WP_INSTALLING') ) {
		$_file = "./$file.css";
	} else {
		$_file = admin_url("$file.css");
	}
	$_file = add_query_arg( 'version', get_bloginfo( 'version' ),  $_file );

	/**
	 * Filter the URI of a WordPress admin CSS file.
	 *
	 * @since 2.3.0
	 *
	 * @param string $_file Relative path to the file with query arguments attached.
	 * @param string $file  Relative path to the file, minus its ".css" extension.
	 */
	return apply_filters( 'wp_admin_css_uri', $_file, $file );
}

/**
 * Enqueues or directly prints a stylesheet link to the specified CSS file.
 *
 * "Intelligently" decides to enqueue or to print the CSS file. If the
 * 'wp_print_styles' action has *not* yet been called, the CSS file will be
 * enqueued. If the wp_print_styles action *has* been called, the CSS link will
 * be printed. Printing may be forced by passing true as the $force_echo
 * (second) parameter.
 *
 * For backward compatibility with WordPress 2.3 calling method: If the $file
 * (first) parameter does not correspond to a registered CSS file, we assume
 * $file is a file relative to wp-admin/ without its ".css" extension. A
 * stylesheet link to that generated URL is printed.
 *
 * @since 2.3.0
 * @uses $wp_styles WordPress Styles Object
 *
 * @param string $file Optional. Style handle name or file name (without ".css" extension) relative
 * 	 to wp-admin/. Defaults to 'wp-admin'.
 * @param bool $force_echo Optional. Force the stylesheet link to be printed rather than enqueued.
 */
function wp_admin_css( $file = 'wp-admin', $force_echo = false ) {
	global $wp_styles;
	if ( ! ( $wp_styles instanceof WP_Styles ) ) {
		$wp_styles = new WP_Styles();
	}

	// For backward compatibility
	$handle = 0 === strpos( $file, 'css/' ) ? substr( $file, 4 ) : $file;

    if ( wp_styles()->query( $handle ) ) { 
		if ( $force_echo || did_action( 'wp_print_styles' ) ) // we already printed the style queue. Print this one immediately
			wp_print_styles( $handle );
		else // Add to style queue
			wp_enqueue_style( $handle );
		return;
	}

	/**
	 * Filter the stylesheet link to the specified CSS file.
	 *
	 * If the site is set to display right-to-left, the RTL stylesheet link
	 * will be used instead.
	 *
	 * @since 2.3.0
	 *
	 * @param string $file Style handle name or filename (without ".css" extension)
	 *                     relative to wp-admin/. Defaults to 'wp-admin'.
	 */
	echo apply_filters( 'wp_admin_css', "<link rel='stylesheet' href='" . esc_url( wp_admin_css_uri( $file ) ) . "' type='text/css' />\n", $file );

	if ( function_exists( 'is_rtl' ) && is_rtl() ) {
		/** This filter is documented in wp-includes/general-template.php */
		echo apply_filters( 'wp_admin_css', "<link rel='stylesheet' href='" . esc_url( wp_admin_css_uri( "$file-rtl" ) ) . "' type='text/css' />\n", "$file-rtl" );
	}
}

/**
 * Enqueues the default ThickBox js and css.
 *
 * If any of the settings need to be changed, this can be done with another js
 * file similar to media-upload.js. That file should
 * require array('thickbox') to ensure it is loaded after.
 *
 * @since 2.5.0
 */
function add_thickbox() {
	wp_enqueue_script( 'thickbox' );

	if(!is_admin() ) //it's always loaded in admin inside of wp-admin-commons
		wp_enqueue_style( 'thickbox' );
}
