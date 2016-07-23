<?php
/**
 * Theme, template, and stylesheet functions.
 *
 * @package WordPress
 * @subpackage Theme
 */

/**
 * Returns an array of WP_Theme objects based on the arguments.
 *
 * Despite advances over get_themes(), this function is quite expensive, and grows
 * linearly with additional themes. Stick to wp_get_theme() if possible.
 *
 * @since 3.4.0
 *
 * @param array $args The search arguments. Optional.
 * - errors      mixed  True to return themes with errors, false to return themes without errors, null
 *                      to return all themes. Defaults to false.
 * - allowed     mixed  (Multisite) True to return only allowed themes for a site. False to return only
 *                      disallowed themes for a site. 'site' to return only site-allowed themes. 'network'
 *                      to return only network-allowed themes. Null to return all themes. Defaults to null.
 * - blog_id     int    (Multisite) The blog ID used to calculate which themes are allowed. Defaults to 0,
 *                      synonymous for the current blog.
 * @return Array of WP_Theme objects.
 */
function wp_get_themes( $args = array() ) {
	global $wp_theme_directories;

	$defaults = array( 'errors' => false, 'allowed' => null, 'blog_id' => 0 );
	$args = wp_parse_args( $args, $defaults );

	$theme_directories = search_theme_directories();

	if ( count( $wp_theme_directories ) > 1 ) {
		// Make sure the current theme wins out, in case search_theme_directories() picks the wrong
		// one in the case of a conflict. (Normally, last registered theme root wins.)
		$current_theme = get_stylesheet();
		if ( isset( $theme_directories[ $current_theme ] ) ) {
			$root_of_current_theme = get_raw_theme_root( $current_theme );
			if ( ! in_array( $root_of_current_theme, $wp_theme_directories ) )
				$root_of_current_theme = WP_CONTENT_DIR . $root_of_current_theme;
			$theme_directories[ $current_theme ]['theme_root'] = $root_of_current_theme;
		}
	}

	if ( empty( $theme_directories ) )
		return array();


	$themes = array();
	static $_themes = array();

	foreach ( $theme_directories as $theme => $theme_root ) {
		if ( isset( $_themes[ $theme_root['theme_root'] . '/' . $theme ] ) )
			$themes[ $theme ] = $_themes[ $theme_root['theme_root'] . '/' . $theme ];
		else
			$themes[ $theme ] = $_themes[ $theme_root['theme_root'] . '/' . $theme ] = new WP_Theme( $theme, $theme_root['theme_root'] );
	}

	if ( null !== $args['errors'] ) {
		foreach ( $themes as $theme => $wp_theme ) {
			if ( $wp_theme->errors() != $args['errors'] )
				unset( $themes[ $theme ] );
		}
	}

	return $themes;
}

/**
 * Gets a WP_Theme object for a theme.
 *
 * @since 3.4.0
 *
 * @param string $stylesheet Directory name for the theme. Optional. Defaults to current theme.
 * @param string $theme_root Absolute path of the theme root to look in. Optional. If not specified, get_raw_theme_root()
 * 	is used to calculate the theme root for the $stylesheet provided (or current theme).
 * @return WP_Theme Theme object. Be sure to check the object's exists() method if you need to confirm the theme's existence.
 */
function wp_get_theme( $stylesheet = null, $theme_root = null ) {
	global $wp_theme_directories;

	if ( empty( $stylesheet ) )
		$stylesheet = get_stylesheet();

	if ( empty( $theme_root ) ) {
		$theme_root = get_raw_theme_root( $stylesheet );
		if ( false === $theme_root )
			$theme_root = WP_CONTENT_DIR . '/themes';
		elseif ( ! in_array( $theme_root, (array) $wp_theme_directories ) )
			$theme_root = WP_CONTENT_DIR . $theme_root;
	}

	return new WP_Theme( $stylesheet, $theme_root );
}

/**
 * Clears the cache held by get_theme_roots() and WP_Theme.
 *
 * @since 3.5.0
 * @param bool $clear_update_cache Whether to clear the Theme updates cache
 */
function wp_clean_themes_cache( $clear_update_cache = true ) {
	if ( $clear_update_cache )
		delete_site_transient( 'update_themes' );
	search_theme_directories( true );
	foreach ( wp_get_themes( array( 'errors' => null ) ) as $theme )
		$theme->cache_delete();
}

/**
 * Whether a child theme is in use.
 *
 * @since 3.0.0
 *
 * @return bool true if a child theme is in use, false otherwise.
 **/
function is_child_theme() {
	return ( TEMPLATEPATH !== STYLESHEETPATH );
}

/**
 * Retrieve name of the current stylesheet.
 *
 * The theme name that the administrator has currently set the front end theme
 * as.
 *
 * For all intents and purposes, the template name and the stylesheet name are
 * going to be the same for most cases.
 *
 * @since 1.5.0
 *
 * @return string Stylesheet name.
 */
function get_stylesheet() {
	/**
	 * Filter the name of current stylesheet.
	 *
	 * @since 1.5.0
	 *
	 * @param string $stylesheet Name of the current stylesheet.
	 */
	return apply_filters( 'stylesheet', get_option( 'stylesheet' ) );
}

/**
 * Retrieve stylesheet directory path for current theme.
 *
 * @since 1.5.0
 *
 * @return string Path to current theme directory.
 */
function get_stylesheet_directory() {
	$stylesheet = get_stylesheet();
	$theme_root = get_theme_root( $stylesheet );
	$stylesheet_dir = "$theme_root/$stylesheet";

	/**
	 * Filter the stylesheet directory path for current theme.
	 *
	 * @since 1.5.0
	 *
	 * @param string $stylesheet_dir Absolute path to the current them.
	 * @param string $stylesheet     Directory name of the current theme.
	 * @param string $theme_root     Absolute path to themes directory.
	 */
	return apply_filters( 'stylesheet_directory', $stylesheet_dir, $stylesheet, $theme_root );
}

/**
 * Retrieve stylesheet directory URI.
 *
 * @since 1.5.0
 *
 * @return string
 */
function get_stylesheet_directory_uri() {
	$stylesheet = str_replace( '%2F', '/', rawurlencode( get_stylesheet() ) );
	$theme_root_uri = get_theme_root_uri( $stylesheet );
	$stylesheet_dir_uri = "$theme_root_uri/$stylesheet";

	/**
	 * Filter the stylesheet directory URI.
	 *
	 * @since 1.5.0
	 *
	 * @param string $stylesheet_dir_uri Stylesheet directory URI.
	 * @param string $stylesheet         Name of the activated theme's directory.
	 * @param string $theme_root_uri     Themes root URI.
	 */
	return apply_filters( 'stylesheet_directory_uri', $stylesheet_dir_uri, $stylesheet, $theme_root_uri );
}

/**
 * Retrieve URI of current theme stylesheet.
 *
 * The stylesheet file name is 'style.css' which is appended to {@link
 * get_stylesheet_directory_uri() stylesheet directory URI} path.
 *
 * @since 1.5.0
 *
 * @return string
 */
function get_stylesheet_uri() {
	$stylesheet_dir_uri = get_stylesheet_directory_uri();
	$stylesheet_uri = $stylesheet_dir_uri . '/style.css';
	/**
	 * Filter the URI of the current theme stylesheet.
	 *
	 * @since 1.5.0
	 *
	 * @param string $stylesheet_uri     Stylesheet URI for the current theme/child theme.
	 * @param string $stylesheet_dir_uri Stylesheet directory URI for the current theme/child theme.
	 */
	return apply_filters( 'stylesheet_uri', $stylesheet_uri, $stylesheet_dir_uri );
}

/**
 * Retrieve localized stylesheet URI.
 *
 * The stylesheet directory for the localized stylesheet files are located, by
 * default, in the base theme directory. The name of the locale file will be the
 * locale followed by '.css'. If that does not exist, then the text direction
 * stylesheet will be checked for existence, for example 'ltr.css'.
 *
 * The theme may change the location of the stylesheet directory by either using
 * the 'stylesheet_directory_uri' filter or the 'locale_stylesheet_uri' filter.
 * If you want to change the location of the stylesheet files for the entire
 * WordPress workflow, then change the former. If you just have the locale in a
 * separate folder, then change the latter.
 *
 * @since 2.1.0
 *
 * @return string
 */
function get_locale_stylesheet_uri() {
	global $wp_locale;
	$stylesheet_dir_uri = get_stylesheet_directory_uri();
	$dir = get_stylesheet_directory();
	$locale = get_locale();
	if ( file_exists("$dir/$locale.css") )
		$stylesheet_uri = "$stylesheet_dir_uri/$locale.css";
	elseif ( !empty($wp_locale->text_direction) && file_exists("$dir/{$wp_locale->text_direction}.css") )
		$stylesheet_uri = "$stylesheet_dir_uri/{$wp_locale->text_direction}.css";
	else
		$stylesheet_uri = '';
	/**
	 * Filter the localized stylesheet URI.
	 *
	 * @since 2.1.0
	 *
	 * @param string $stylesheet_uri     Localized stylesheet URI.
	 * @param string $stylesheet_dir_uri Stylesheet directory URI.
	 */
	return apply_filters( 'locale_stylesheet_uri', $stylesheet_uri, $stylesheet_dir_uri );
}

/**
 * Retrieve name of the current theme.
 *
 * @since 1.5.0
 *
 * @return string Template name.
 */
function get_template() {
	/**
	 * Filter the name of the current theme.
	 *
	 * @since 1.5.0
	 *
	 * @param string $template Current theme's directory name.
	 */
	return apply_filters( 'template', get_option( 'template' ) );
}

/**
 * Retrieve current theme directory.
 *
 * @since 1.5.0
 *
 * @return string Template directory path.
 */
function get_template_directory() {
	$template = get_template();
	$theme_root = get_theme_root( $template );
	$template_dir = "$theme_root/$template";

	/**
	 * Filter the current theme directory path.
	 *
	 * @since 1.5.0
	 *
	 * @param string $template_dir The URI of the current theme directory.
	 * @param string $template     Directory name of the current theme.
	 * @param string $theme_root   Absolute path to the themes directory.
	 */
	return apply_filters( 'template_directory', $template_dir, $template, $theme_root );
}

/**
 * Retrieve theme directory URI.
 *
 * @since 1.5.0
 *
 * @return string Template directory URI.
 */
function get_template_directory_uri() {
	$template = str_replace( '%2F', '/', rawurlencode( get_template() ) );
	$theme_root_uri = get_theme_root_uri( $template );
	$template_dir_uri = "$theme_root_uri/$template";

	/**
	 * Filter the current theme directory URI.
	 *
	 * @since 1.5.0
	 *
	 * @param string $template_dir_uri The URI of the current theme directory.
	 * @param string $template         Directory name of the current theme.
	 * @param string $theme_root_uri   The themes root URI.
	 */
	return apply_filters( 'template_directory_uri', $template_dir_uri, $template, $theme_root_uri );
}

/**
 * Retrieve theme roots.
 *
 * @since 2.9.0
 *
 * @return array|string An array of theme roots keyed by template/stylesheet or a single theme root if all themes have the same root.
 */
function get_theme_roots() {
	global $wp_theme_directories;

	if ( count($wp_theme_directories) <= 1 )
		return '/themes';

	$theme_roots = get_site_transient( 'theme_roots' );
	if ( false === $theme_roots ) {
		search_theme_directories( true ); // Regenerate the transient.
		$theme_roots = get_site_transient( 'theme_roots' );
	}
	return $theme_roots;
}

/**
 * Register a directory that contains themes.
 *
 * @since 2.9.0
 *
 * @param string $directory Either the full filesystem path to a theme folder or a folder within WP_CONTENT_DIR
 * @return bool
 */
function register_theme_directory( $directory ) {
	global $wp_theme_directories;

	if ( ! file_exists( $directory ) ) {
		// Try prepending as the theme directory could be relative to the content directory
		$directory = WP_CONTENT_DIR . '/' . $directory;
		// If this directory does not exist, return and do not register
		if ( ! file_exists( $directory ) ) {
			return false;
		}
	}

	if ( ! is_array( $wp_theme_directories ) ) {
		$wp_theme_directories = array();
	}

	$untrailed = untrailingslashit( $directory );
	if ( ! empty( $untrailed ) && ! in_array( $untrailed, $wp_theme_directories ) ) {
		$wp_theme_directories[] = $untrailed;
	}

	return true;
}

/**
 * Search all registered theme directories for complete and valid themes.
 *
 * @since 2.9.0
 *
 * @param bool $force Optional. Whether to force a new directory scan. Defaults to false.
 * @return array Valid themes found
 */
function search_theme_directories( $force = false ) {
	global $wp_theme_directories;
	if ( empty( $wp_theme_directories ) )
		return false;

	static $found_themes;
	if ( ! $force && isset( $found_themes ) )
		return $found_themes;

	$found_themes = array();

	$wp_theme_directories = (array) $wp_theme_directories;
	$relative_theme_roots = array();

	// Set up maybe-relative, maybe-absolute array of theme directories.
	// We always want to return absolute, but we need to cache relative
	// to use in get_theme_root().
	foreach ( $wp_theme_directories as $theme_root ) {
		if ( 0 === strpos( $theme_root, WP_CONTENT_DIR ) )
			$relative_theme_roots[ str_replace( WP_CONTENT_DIR, '', $theme_root ) ] = $theme_root;
		else
			$relative_theme_roots[ $theme_root ] = $theme_root;
	}

	/**
	 * Filter whether to get the cache of the registered theme directories.
	 *
	 * @since 3.4.0
	 *
	 * @param bool   $cache_expiration Whether to get the cache of the theme directories. Default false.
	 * @param string $cache_directory  Directory to be searched for the cache.
	 */
	if ( $cache_expiration = apply_filters( 'wp_cache_themes_persistently', false, 'search_theme_directories' ) ) {
		$cached_roots = get_site_transient( 'theme_roots' );
		if ( is_array( $cached_roots ) ) {
			foreach ( $cached_roots as $theme_dir => $theme_root ) {
				// A cached theme root is no longer around, so skip it.
				if ( ! isset( $relative_theme_roots[ $theme_root ] ) )
					continue;
				$found_themes[ $theme_dir ] = array(
					'theme_file' => $theme_dir . '/style.css',
					'theme_root' => $relative_theme_roots[ $theme_root ], // Convert relative to absolute.
				);
			}
			return $found_themes;
		}
		if ( ! is_int( $cache_expiration ) )
			$cache_expiration = 1800; // half hour
	} else {
		$cache_expiration = 1800; // half hour
	}

	/* Loop the registered theme directories and extract all themes */
	foreach ( $wp_theme_directories as $theme_root ) {

		// Start with directories in the root of the current theme directory.
		$dirs = @ scandir( $theme_root );
		if ( ! $dirs ) {
			trigger_error( "$theme_root is not readable", E_USER_NOTICE );
			continue;
		}
		foreach ( $dirs as $dir ) {
			if ( ! is_dir( $theme_root . '/' . $dir ) || $dir[0] == '.' || $dir == 'CVS' )
				continue;
			if ( file_exists( $theme_root . '/' . $dir . '/style.css' ) ) {
				// wp-content/themes/a-single-theme
				// wp-content/themes is $theme_root, a-single-theme is $dir
				$found_themes[ $dir ] = array(
					'theme_file' => $dir . '/style.css',
					'theme_root' => $theme_root,
				);
			} else {
				$found_theme = false;
				// wp-content/themes/a-folder-of-themes/*
				// wp-content/themes is $theme_root, a-folder-of-themes is $dir, then themes are $sub_dirs
				$sub_dirs = @ scandir( $theme_root . '/' . $dir );
				if ( ! $sub_dirs ) {
					trigger_error( "$theme_root/$dir is not readable", E_USER_NOTICE );
					continue;
				}
				foreach ( $sub_dirs as $sub_dir ) {
					if ( ! is_dir( $theme_root . '/' . $dir . '/' . $sub_dir ) || $dir[0] == '.' || $dir == 'CVS' )
						continue;
					if ( ! file_exists( $theme_root . '/' . $dir . '/' . $sub_dir . '/style.css' ) )
						continue;
					$found_themes[ $dir . '/' . $sub_dir ] = array(
						'theme_file' => $dir . '/' . $sub_dir . '/style.css',
						'theme_root' => $theme_root,
					);
					$found_theme = true;
				}
				// Never mind the above, it's just a theme missing a style.css.
				// Return it; WP_Theme will catch the error.
				if ( ! $found_theme )
					$found_themes[ $dir ] = array(
						'theme_file' => $dir . '/style.css',
						'theme_root' => $theme_root,
					);
			}
		}
	}

	asort( $found_themes );

	$theme_roots = array();
	$relative_theme_roots = array_flip( $relative_theme_roots );

	foreach ( $found_themes as $theme_dir => $theme_data ) {
		$theme_roots[ $theme_dir ] = $relative_theme_roots[ $theme_data['theme_root'] ]; // Convert absolute to relative.
	}

	if ( $theme_roots != get_site_transient( 'theme_roots' ) )
		set_site_transient( 'theme_roots', $theme_roots, $cache_expiration );

	return $found_themes;
}

/**
 * Retrieve path to themes directory.
 *
 * Does not have trailing slash.
 *
 * @since 1.5.0
 *
 * @param string $stylesheet_or_template The stylesheet or template name of the theme
 * @return string Theme path.
 */
function get_theme_root( $stylesheet_or_template = false ) {
	global $wp_theme_directories;

	if ( $stylesheet_or_template && $theme_root = get_raw_theme_root( $stylesheet_or_template ) ) {
		// Always prepend WP_CONTENT_DIR unless the root currently registered as a theme directory.
		// This gives relative theme roots the benefit of the doubt when things go haywire.
		if ( ! in_array( $theme_root, (array) $wp_theme_directories ) )
			$theme_root = WP_CONTENT_DIR . $theme_root;
	} else {
		$theme_root = WP_CONTENT_DIR . '/themes';
	}

	/**
	 * Filter the absolute path to the themes directory.
	 *
	 * @since 1.5.0
	 *
	 * @param string $theme_root Absolute path to themes directory.
	 */
	return apply_filters( 'theme_root', $theme_root );
}

/**
 * Retrieve URI for themes directory.
 *
 * Does not have trailing slash.
 *
 * @since 1.5.0
 *
 * @param string $stylesheet_or_template Optional. The stylesheet or template name of the theme.
 * 	Default is to leverage the main theme root.
 * @param string $theme_root Optional. The theme root for which calculations will be based, preventing
 * 	the need for a get_raw_theme_root() call.
 * @return string Themes URI.
 */
function get_theme_root_uri( $stylesheet_or_template = false, $theme_root = false ) {
	global $wp_theme_directories;

	if ( $stylesheet_or_template && ! $theme_root )
		$theme_root = get_raw_theme_root( $stylesheet_or_template );

	if ( $stylesheet_or_template && $theme_root ) {
		if ( in_array( $theme_root, (array) $wp_theme_directories ) ) {
			// Absolute path. Make an educated guess. YMMV -- but note the filter below.
			if ( 0 === strpos( $theme_root, WP_CONTENT_DIR ) )
				$theme_root_uri = content_url( str_replace( WP_CONTENT_DIR, '', $theme_root ) );
			elseif ( 0 === strpos( $theme_root, ABSPATH ) )
				$theme_root_uri = site_url( str_replace( ABSPATH, '', $theme_root ) );
			elseif ( 0 === strpos( $theme_root, WP_PLUGIN_DIR ) || 0 === strpos( $theme_root, WPMU_PLUGIN_DIR ) )
				$theme_root_uri = plugins_url( basename( $theme_root ), $theme_root );
			else
				$theme_root_uri = $theme_root;
		} else {
			$theme_root_uri = content_url( $theme_root );
		}
	} else {
		$theme_root_uri = content_url( 'themes' );
	}

	/**
	 * Filter the URI for themes directory.
	 *
	 * @since 1.5.0
	 *
	 * @param string $theme_root_uri         The URI for themes directory.
	 * @param string $siteurl                WordPress web address which is set in General Options.
	 * @param string $stylesheet_or_template Stylesheet or template name of the theme.
	 */
	return apply_filters( 'theme_root_uri', $theme_root_uri, get_option( 'siteurl' ), $stylesheet_or_template );
}

/**
 * Get the raw theme root relative to the content directory with no filters applied.
 *
 * @since 3.1.0
 *
 * @param string $stylesheet_or_template The stylesheet or template name of the theme
 * @param bool $skip_cache Optional. Whether to skip the cache. Defaults to false, meaning the cache is used.
 * @return string Theme root
 */
function get_raw_theme_root( $stylesheet_or_template, $skip_cache = false ) {
	global $wp_theme_directories;

	if ( count($wp_theme_directories) <= 1 )
		return '/themes';

	$theme_root = false;

	// If requesting the root for the current theme, consult options to avoid calling get_theme_roots()
	if ( ! $skip_cache ) {
		if ( get_option('stylesheet') == $stylesheet_or_template )
			$theme_root = get_option('stylesheet_root');
		elseif ( get_option('template') == $stylesheet_or_template )
			$theme_root = get_option('template_root');
	}

	if ( empty($theme_root) ) {
		$theme_roots = get_theme_roots();
		if ( !empty($theme_roots[$stylesheet_or_template]) )
			$theme_root = $theme_roots[$stylesheet_or_template];
	}

	return $theme_root;
}

/**
 * Display localized stylesheet link element.
 *
 * @since 2.1.0
 */
function locale_stylesheet() {
	$stylesheet = get_locale_stylesheet_uri();
	if ( empty($stylesheet) )
		return;
	echo '<link rel="stylesheet" href="' . $stylesheet . '" type="text/css" media="screen" />';
}

/**
 * Start preview theme output buffer.
 *
 * Will only perform task if the user has permissions and template and preview
 * query variables exist.
 *
 * @since 2.6.0
 */
function preview_theme() {
	if ( ! (isset($_GET['template']) && isset($_GET['preview'])) )
		return;

	if ( !current_user_can( 'switch_themes' ) )
		return;

	// Admin Thickbox requests
	if ( isset( $_GET['preview_iframe'] ) )
		show_admin_bar( false );

	$_GET['template'] = preg_replace('|[^a-z0-9_./-]|i', '', $_GET['template']);

	if ( validate_file($_GET['template']) )
		return;

	add_filter( 'template', '_preview_theme_template_filter' );

	if ( isset($_GET['stylesheet']) ) {
		$_GET['stylesheet'] = preg_replace('|[^a-z0-9_./-]|i', '', $_GET['stylesheet']);
		if ( validate_file($_GET['stylesheet']) )
			return;
		add_filter( 'stylesheet', '_preview_theme_stylesheet_filter' );
	}

	// Prevent theme mods to current theme being used on theme being previewed
	add_filter( 'pre_option_theme_mods_' . get_option( 'stylesheet' ), '__return_empty_array' );

	ob_start( 'preview_theme_ob_filter' );
}

/**
 * Private function to modify the current template when previewing a theme
 *
 * @since 2.9.0
 * @access private
 *
 * @return string
 */
function _preview_theme_template_filter() {
	return isset($_GET['template']) ? $_GET['template'] : '';
}

/**
 * Private function to modify the current stylesheet when previewing a theme
 *
 * @since 2.9.0
 * @access private
 *
 * @return string
 */
function _preview_theme_stylesheet_filter() {
	return isset($_GET['stylesheet']) ? $_GET['stylesheet'] : '';
}

/**
 * Callback function for ob_start() to capture all links in the theme.
 *
 * @since 2.6.0
 * @access private
 *
 * @param string $content
 * @return string
 */
function preview_theme_ob_filter( $content ) {
	return preg_replace_callback( "|(<a.*?href=([\"']))(.*?)([\"'].*?>)|", 'preview_theme_ob_filter_callback', $content );
}

/**
 * Manipulates preview theme links in order to control and maintain location.
 *
 * Callback function for preg_replace_callback() to accept and filter matches.
 *
 * @since 2.6.0
 * @access private
 *
 * @param array $matches
 * @return string
 */
function preview_theme_ob_filter_callback( $matches ) {
	if ( strpos($matches[4], 'onclick') !== false )
		$matches[4] = preg_replace('#onclick=([\'"]).*?(?<!\\\)\\1#i', '', $matches[4]); //Strip out any onclicks from rest of <a>. (?<!\\\) means to ignore the '" if it's escaped by \  to prevent breaking mid-attribute.
	if (
		( false !== strpos($matches[3], '/wp-admin/') )
	||
		( false !== strpos( $matches[3], '://' ) && 0 !== strpos( $matches[3], site_url() ) )
	)
		return $matches[1] . "#$matches[2] onclick=$matches[2]return false;" . $matches[4];

	$stylesheet = isset( $_GET['stylesheet'] ) ? $_GET['stylesheet'] : '';
	$template   = isset( $_GET['template'] )   ? $_GET['template']   : '';

	$link = add_query_arg( array( 'preview' => 1, 'template' => $template, 'stylesheet' => $stylesheet, 'preview_iframe' => 1 ), $matches[3] );
	if ( 0 === strpos($link, 'preview=1') )
		$link = "?$link";
	return $matches[1] . esc_attr( $link ) . $matches[4];
}

/**
 * Switches the theme.
 *
 * Accepts one argument: $stylesheet of the theme. It also accepts an additional function signature
 * of two arguments: $template then $stylesheet. This is for backwards compatibility.
 *
 * @since 2.5.0
 *
 * @param string $stylesheet Stylesheet name
 */
function switch_theme( $stylesheet ) {
	global $wp_theme_directories, $sidebars_widgets;

	$_sidebars_widgets = null;
	if ( is_array( $sidebars_widgets ) ) {
		$_sidebars_widgets = $sidebars_widgets;
	}

	if ( is_array( $_sidebars_widgets ) ) {
		set_theme_mod( 'sidebars_widgets', array( 'time' => time(), 'data' => $_sidebars_widgets ) );
	}

	$old_theme  = wp_get_theme();
	$new_theme = wp_get_theme( $stylesheet );

	if ( func_num_args() > 1 ) {
		$template = $stylesheet;
		$stylesheet = func_get_arg( 1 );
	} else {
		$template = $new_theme->get_template();
	}

	update_option( 'template', $template );
	update_option( 'stylesheet', $stylesheet );

	if ( count( $wp_theme_directories ) > 1 ) {
		update_option( 'template_root', get_raw_theme_root( $template, true ) );
		update_option( 'stylesheet_root', get_raw_theme_root( $stylesheet, true ) );
	} else {
		delete_option( 'template_root' );
		delete_option( 'stylesheet_root' );
	}

	$new_name  = $new_theme->get('Name');

	update_option( 'current_theme', $new_name );

	// Migrate from the old mods_{name} option to theme_mods_{slug}.
	if ( is_admin() && false === get_option( 'theme_mods_' . $stylesheet ) ) {
		$default_theme_mods = (array) get_option( 'mods_' . $new_name );
		add_option( "theme_mods_$stylesheet", $default_theme_mods );
	} else {
		/*
		 * Since retrieve_widgets() is called when initializing a theme in the Customizer,
		 * we need to to remove the theme mods to avoid overwriting changes made via
		 * the Customizer when accessing wp-admin/widgets.php.
		 */
		if ( 'wp_ajax_customize_save' === current_action() ) {
			remove_theme_mod( 'sidebars_widgets' );
		}
	}

	update_option( 'theme_switched', $old_theme->get_stylesheet() );
	/**
	 * Fires after the theme is switched.
	 *
	 * @since 1.5.0
	 *
	 * @param string   $new_name  Name of the new theme.
	 * @param WP_Theme $new_theme WP_Theme instance of the new theme.
	 */
	do_action( 'switch_theme', $new_name, $new_theme );
}

/**
 * Checks that current theme files 'index.php' and 'style.css' exists.
 *
 * Does not check the default theme, which is the fallback and should always exist.
 * Will switch theme to the fallback theme if current theme does not validate.
 * You can use the 'validate_current_theme' filter to return false to
 * disable this functionality.
 *
 * @since 1.5.0
 * @see WP_DEFAULT_THEME
 *
 * @return bool
 */
function validate_current_theme() {
	/**
	 * Filter whether to validate the current theme.
	 *
	 * @since 2.7.0
	 *
	 * @param bool true Validation flag to check the current theme.
	 */
	if ( defined('WP_INSTALLING') || ! apply_filters( 'validate_current_theme', true ) )
		return true;

	if ( get_template() != WP_DEFAULT_THEME && !file_exists(get_template_directory() . '/index.php') ) {
		switch_theme( WP_DEFAULT_THEME );
		return false;
	}

	if ( get_stylesheet() != WP_DEFAULT_THEME && !file_exists(get_template_directory() . '/style.css') ) {
		switch_theme( WP_DEFAULT_THEME );
		return false;
	}

	if ( is_child_theme() && ! file_exists( get_stylesheet_directory() . '/style.css' ) ) {
		switch_theme( WP_DEFAULT_THEME );
		return false;
	}

	return true;
}

/**
 * Retrieve all theme modifications.
 *
 * @since 3.1.0
 *
 * @return array|null Theme modifications.
 */
function get_theme_mods() {
	$theme_slug = get_option( 'stylesheet' );
    $mods = get_option( "theme_mods_$theme_slug" ); 
    if ( false === $mods ) { 
		$theme_name = get_option( 'current_theme' );
		if ( false === $theme_name )
			$theme_name = wp_get_theme()->get('Name');
		$mods = get_option( "mods_$theme_name" ); // Deprecated location.
		if ( is_admin() && false !== $mods ) {
			update_option( "theme_mods_$theme_slug", $mods );
			delete_option( "mods_$theme_name" );
		}
	}
	return $mods;
}

/**
 * Retrieve theme modification value for the current theme.
 *
 * If the modification name does not exist, then the $default will be passed
 * through {@link http://php.net/sprintf sprintf()} PHP function with the first
 * string the template directory URI and the second string the stylesheet
 * directory URI.
 *
 * @since 2.1.0
 *
 * @param string $name Theme modification name.
 * @param bool|string $default
 * @return string
 */
function get_theme_mod( $name, $default = false ) {
	$mods = get_theme_mods();

	if ( isset( $mods[$name] ) ) {
		/**
		 * Filter the theme modification, or 'theme_mod', value.
		 *
		 * The dynamic portion of the hook name, `$name`, refers to
		 * the key name of the modification array. For example,
		 * 'header_textcolor', 'header_image', and so on depending
		 * on the theme options.
		 *
		 * @since 2.2.0
		 *
		 * @param string $current_mod The value of the current theme modification.
		 */
		return apply_filters( "theme_mod_{$name}", $mods[$name] );
	}

	if ( is_string( $default ) )
		$default = sprintf( $default, get_template_directory_uri(), get_stylesheet_directory_uri() );

	/** This filter is documented in wp-includes/theme.php */
	return apply_filters( "theme_mod_{$name}", $default );
}

/**
 * Update theme modification value for the current theme.
 *
 * @since 2.1.0
 *
 * @param string $name Theme modification name.
 * @param mixed  $value theme modification value.
 */
function set_theme_mod( $name, $value ) {
	$mods = get_theme_mods();
	$old_value = isset( $mods[ $name ] ) ? $mods[ $name ] : false;

	/**
	 * Filter the theme mod value on save.
	 *
	 * The dynamic portion of the hook name, `$name`, refers to the key name of
	 * the modification array. For example, 'header_textcolor', 'header_image',
	 * and so on depending on the theme options.
	 *
	 * @since 3.9.0
	 *
	 * @param string $value     The new value of the theme mod.
	 * @param string $old_value The current value of the theme mod.
	 */
	$mods[ $name ] = apply_filters( "pre_set_theme_mod_$name", $value, $old_value );

	$theme = get_option( 'stylesheet' );
	update_option( "theme_mods_$theme", $mods );
}

/**
 * Remove theme modification name from current theme list.
 *
 * If removing the name also removes all elements, then the entire option will
 * be removed.
 *
 * @since 2.1.0
 *
 * @param string $name Theme modification name.
 * @return null
 */
function remove_theme_mod( $name ) {
	$mods = get_theme_mods();

	if ( ! isset( $mods[ $name ] ) )
		return;

	unset( $mods[ $name ] );

	if ( empty( $mods ) )
		return remove_theme_mods();

	$theme = get_option( 'stylesheet' );
	update_option( "theme_mods_$theme", $mods );
}

/**
 * Remove theme modifications option for current theme.
 *
 * @since 2.1.0
 */
function remove_theme_mods() {
	delete_option( 'theme_mods_' . get_option( 'stylesheet' ) );

	// Old style.
	$theme_name = get_option( 'current_theme' );
	if ( false === $theme_name )
		$theme_name = wp_get_theme()->get('Name');
	delete_option( 'mods_' . $theme_name );
}

/**
 * Retrieve text color for custom header.
 *
 * @since 2.1.0
 *
 * @return string
 */
function get_header_textcolor() {
	return get_theme_mod('header_textcolor', get_theme_support( 'custom-header', 'default-text-color' ) );
}

/**
 * Display text color for custom header.
 *
 * @since 2.1.0
 */
function header_textcolor() {
	echo get_header_textcolor();
}

/**
 * Whether to display the header text.
 *
 * @since 3.4.0
 *
 * @return bool
 */
function display_header_text() {
	return false;
}

/**
 * Check whether a header image is set or not.
 *
 * @since 4.2.0
 *
 * @see get_header_image()
 *
 * @return bool Whether a header image is set or not.
 */
function has_header_image() {
	return false;
}

/**
 * Retrieve header image for custom header.
 *
 * @since 2.1.0
 *
 * @return string|false
 */
function get_header_image() {
	return '';
}


/**
 * Display header image URL.
 *
 * @since 2.1.0
 */
function header_image() {
	echo '';
}


/**
 * Get the header image data.
 *
 * @since 3.4.0
 *
 * @return object
 */
function get_custom_header() {
	return new stdClass();
}


/**
 * Retrieve background image for custom background.
 *
 * @since 3.0.0
 *
 * @return string
 */
function get_background_image() {
	return '';
}

/**
 * Display background image path.
 *
 * @since 3.0.0
 */
function background_image() {
	echo get_background_image();
}

/**
 * Retrieve value for custom background color.
 *
 * @since 3.0.0
 *
 * @return string
 */
function get_background_color() {
	return '';
}

/**
 * Display background color value.
 *
 * @since 3.0.0
 */
function background_color() {
	echo get_background_color();
}



/**
 * Add callback for custom TinyMCE editor stylesheets.
 *
 * The parameter $stylesheet is the name of the stylesheet, relative to
 * the theme root. It also accepts an array of stylesheets.
 * It is optional and defaults to 'editor-style.css'.
 *
 * This function automatically adds another stylesheet with -rtl prefix, e.g. editor-style-rtl.css.
 * If that file doesn't exist, it is removed before adding the stylesheet(s) to TinyMCE.
 * If an array of stylesheets is passed to add_editor_style(),
 * RTL is only added for the first stylesheet.
 *
 * Since version 3.4 the TinyMCE body has .rtl CSS class.
 * It is a better option to use that class and add any RTL styles to the main stylesheet.
 *
 * @since 3.0.0
 *
 * @param array|string $stylesheet Optional. Stylesheet name or array thereof, relative to theme root.
 * 	Defaults to 'editor-style.css'
 */
function add_editor_style( $stylesheet = 'editor-style.css' ) {

	add_theme_support( 'editor-style' );

	if ( ! is_admin() )
		return;

	global $editor_styles;
	$editor_styles = (array) $editor_styles;
	$stylesheet    = (array) $stylesheet;
	if ( is_rtl() ) {
		$rtl_stylesheet = str_replace('.css', '-rtl.css', $stylesheet[0]);
		$stylesheet[] = $rtl_stylesheet;
	}

	$editor_styles = array_merge( $editor_styles, $stylesheet );
}

/**
 * Removes all visual editor stylesheets.
 *
 * @since 3.1.0
 *
 * @return bool True on success, false if there were no stylesheets to remove.
 */
function remove_editor_styles() {
	if ( ! current_theme_supports( 'editor-style' ) )
		return false;
	_remove_theme_support( 'editor-style' );
	if ( is_admin() )
		$GLOBALS['editor_styles'] = array();
	return true;
}

/**
 * Retrieve any registered editor stylesheets
 *
 * @since 4.0.0
 *
 * @global $editor_styles Registered editor stylesheets
 *
 * @return array If registered, a list of editor stylesheet URLs.
 */
function get_editor_stylesheets() {
	$stylesheets = array();
	// load editor_style.css if the current theme supports it
	if ( ! empty( $GLOBALS['editor_styles'] ) && is_array( $GLOBALS['editor_styles'] ) ) {
		$editor_styles = $GLOBALS['editor_styles'];

		$editor_styles = array_unique( array_filter( $editor_styles ) );
		$style_uri = get_stylesheet_directory_uri();
		$style_dir = get_stylesheet_directory();

		// Support externally referenced styles (like, say, fonts).
		foreach ( $editor_styles as $key => $file ) {
			if ( preg_match( '~^(https?:)?//~', $file ) ) {
				$stylesheets[] = esc_url_raw( $file );
				unset( $editor_styles[ $key ] );
			}
		}

		// Look in a parent theme first, that way child theme CSS overrides.
		if ( is_child_theme() ) {
			$template_uri = get_template_directory_uri();
			$template_dir = get_template_directory();

			foreach ( $editor_styles as $key => $file ) {
				if ( $file && file_exists( "$template_dir/$file" ) ) {
					$stylesheets[] = "$template_uri/$file";
				}
			}
		}

		foreach ( $editor_styles as $file ) {
			if ( $file && file_exists( "$style_dir/$file" ) ) {
				$stylesheets[] = "$style_uri/$file";
			}
		}
	}
	return $stylesheets;
}

/**
 * Allows a theme to register its support of a certain feature
 *
 * Must be called in the theme's functions.php file to work.
 * If attached to a hook, it must be after_setup_theme.
 * The init hook may be too late for some features.
 *
 * @since 2.9.0
 *
 * @param string $feature The feature being added.
 * @return void|bool False on failure, void otherwise.
 */
function add_theme_support( $feature ) {
	global $_wp_theme_features;

	if ( func_num_args() == 1 )
		$args = true;
	else
		$args = array_slice( func_get_args(), 1 );

	switch ( $feature ) {
		case 'post-formats' :
			if ( is_array( $args[0] ) ) {
				$post_formats = get_post_format_slugs();
				unset( $post_formats['standard'] );

				$args[0] = array_intersect( $args[0], array_keys( $post_formats ) );
			}
			break;

		case 'html5' :
			// You can't just pass 'html5', you need to pass an array of types.
			if ( empty( $args[0] ) ) {
				// Build an array of types for back-compat.
				$args = array( 0 => array( 'comment-list', 'comment-form', 'search-form' ) );
			} elseif ( ! is_array( $args[0] ) ) {
				_doing_it_wrong( "add_theme_support( 'html5' )", __( 'You need to pass an array of types.' ), '3.6.1' );
				return false;
			}

			// Calling 'html5' again merges, rather than overwrites.
			if ( isset( $_wp_theme_features['html5'] ) )
				$args[0] = array_merge( $_wp_theme_features['html5'][0], $args[0] );
			break;

		// Ensure that 'title-tag' is accessible in the admin.
		case 'title-tag' :
			// Can be called in functions.php but must happen before wp_loaded, i.e. not in header.php.
			if ( did_action( 'wp_loaded' ) ) {
				/* translators: 1: Theme support 2: hook name */
				_doing_it_wrong( "add_theme_support( 'title-tag' )", sprintf( __( 'Theme support for %1$s should be registered before the %2$s hook.' ),
					'<code>title-tag</code>', '<code>wp_loaded</code>' ), '4.1' );

				return false;
			}
	}

	$_wp_theme_features[ $feature ] = $args;
}


/**
 * Gets the theme support arguments passed when registering that support
 *
 * @since 3.1.0
 *
 * @param string $feature the feature to check
 * @return mixed The array of extra arguments or the value for the registered feature.
 */
function get_theme_support( $feature ) {
	global $_wp_theme_features;
	if ( ! isset( $_wp_theme_features[ $feature ] ) )
		return false;

	if ( func_num_args() <= 1 )
		return $_wp_theme_features[ $feature ];

	$args = array_slice( func_get_args(), 1 );

	return $_wp_theme_features[ $feature ];
}

/**
 * Allows a theme to de-register its support of a certain feature
 *
 * Should be called in the theme's functions.php file. Generally would
 * be used for child themes to override support from the parent theme.
 *
 * @since 3.0.0
 * @see add_theme_support()
 * @param string $feature the feature being added
 * @return null|bool Whether feature was removed.
 */
function remove_theme_support( $feature ) {
	// Blacklist: for internal registrations not used directly by themes.
	if ( in_array( $feature, array( 'editor-style', 'widgets', 'menus' ) ) )
		return false;

	return _remove_theme_support( $feature );
}

/**
 * Do not use. Removes theme support internally, ignorant of the blacklist.
 *
 * @access private
 * @since 3.1.0
 * @param string $feature
 */
function _remove_theme_support( $feature ) {
	global $_wp_theme_features;

	if ( ! isset( $_wp_theme_features[ $feature ] ) )
		return false;

	unset( $_wp_theme_features[ $feature ] );
	return true;
}

/**
 * Checks a theme's support for a given feature
 *
 * @since 2.9.0
 * @param string $feature the feature being checked
 * @return boolean
 */
function current_theme_supports( $feature ) {
	global $_wp_theme_features;

	if ( !isset( $_wp_theme_features[$feature] ) )
		return false;

	if ( 'title-tag' == $feature ) {
		// Don't confirm support unless called internally.
		$trace = debug_backtrace();
		if ( ! in_array( $trace[1]['function'], array( '_wp_render_title_tag', 'wp_title' ) ) ) {
			return false;
		}
	}

	// If no args passed then no extra checks need be performed
	if ( func_num_args() <= 1 )
		return true;

	$args = array_slice( func_get_args(), 1 );

	switch ( $feature ) {
		case 'post-thumbnails':
			// post-thumbnails can be registered for only certain content/post types by passing
			// an array of types to add_theme_support(). If no array was passed, then
			// any type is accepted
			if ( true === $_wp_theme_features[$feature] )  // Registered for all types
				return true;
			$content_type = $args[0];
			return in_array( $content_type, $_wp_theme_features[$feature][0] );

		case 'html5':
		case 'post-formats':
			// specific post formats can be registered by passing an array of types to
			// add_theme_support()

			// Specific areas of HTML5 support *must* be passed via an array to add_theme_support()

			$type = $args[0];
			return in_array( $type, $_wp_theme_features[$feature][0] );
	}

	/**
	 * Filter whether the current theme supports a specific feature.
	 *
	 * The dynamic portion of the hook name, `$feature`, refers to the specific theme
	 * feature. Possible values include 'post-formats', 'post-thumbnails', 'menus',  and 'html5'.
	 *
	 * @since 3.4.0
	 *
	 * @param bool   true     Whether the current theme supports the given feature. Default true.
	 * @param array  $args    Array of arguments for the feature.
	 * @param string $feature The theme feature.
	 */
	return apply_filters( "current_theme_supports-{$feature}", true, $args, $_wp_theme_features[$feature] );
}

/**
 * Checks a theme's support for a given feature before loading the functions which implement it.
 *
 * @since 2.9.0
 *
 * @param string $feature The feature being checked.
 * @param string $include Path to the file.
 * @return bool True if the current theme supports the supplied feature, false otherwise.
 */
function require_if_theme_supports( $feature, $include ) {
	if ( current_theme_supports( $feature ) ) {
		require ( $include );
		return true;
	}
	return false;
}

/**
 * Checks an attachment being deleted to see if it's a header or background image.
 *
 * If true it removes the theme modification which would be pointing at the deleted
 * attachment
 *
 * @access private
 * @since 3.0.0
 * @param int $id the attachment id
 */
function _delete_attachment_theme_mod( $id ) {
	$attachment_image = wp_get_attachment_url( $id );
	$header_image = get_header_image();
	$background_image = get_background_image();

	if ( $header_image && $header_image == $attachment_image )
		remove_theme_mod( 'header_image' );

	if ( $background_image && $background_image == $attachment_image )
		remove_theme_mod( 'background_image' );
}

/**
 * Checks if a theme has been changed and runs 'after_switch_theme' hook on the next WP load
 *
 * @since 3.3.0
 */
function check_theme_switched() {
	if ( $stylesheet = get_option( 'theme_switched' ) ) {
		$old_theme = wp_get_theme( $stylesheet );

		if ( $old_theme->exists() ) {
			/**
			 * Fires on the first WP load after a theme switch if the old theme still exists.
			 *
			 * This action fires multiple times and the parameters differs
			 * according to the context, if the old theme exists or not.
			 * If the old theme is missing, the parameter will be the slug
			 * of the old theme.
			 *
			 * @since 3.3.0
			 *
			 * @param string   $old_name  Old theme name.
			 * @param WP_Theme $old_theme WP_Theme instance of the old theme.
			 */
			do_action( 'after_switch_theme', $old_theme->get( 'Name' ), $old_theme );
		} else {
			/** This action is documented in wp-includes/theme.php */
			do_action( 'after_switch_theme', $stylesheet );
		}

		update_option( 'theme_switched', false );
	}
}

 


/**
 * Whether the site is being previewed in the Customizer.
 *
 * @since 4.0.0
 *
 * RAPIDO_DEPRECATED
 *
 *
 * @return bool True if the site is being previewed in the Customizer, false otherwise.
 */
function is_customize_preview() {
	return false;
}

function wp_customize_url( $stylesheet = null ) {
	return null;
}
