<?php
/**
 * WordPress Dashboard Widget Administration Screen API
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Registers dashboard widgets.
 *
 * Handles POST data, sets up filters.
 *
 * @since 2.5.0
 */
function wp_dashboard_setup() {
	global $wp_registered_widgets, $wp_registered_widget_controls, $wp_dashboard_control_callbacks;
	$wp_dashboard_control_callbacks = array();
	$screen = get_current_screen();

	/* Register Widgets and Controls */

	$response = wp_check_browser_version();

	if ( $response && $response['upgrade'] ) {
		add_filter( 'postbox_classes_dashboard_dashboard_browser_nag', 'dashboard_browser_nag_class' );
		if ( $response['insecure'] )
			wp_add_dashboard_widget( 'dashboard_browser_nag', __( 'You are using an insecure browser!' ), 'wp_dashboard_browser_nag' );
		else
			wp_add_dashboard_widget( 'dashboard_browser_nag', __( 'Your browser is out of date!' ), 'wp_dashboard_browser_nag' );
	}

	// Right Now
	if ( is_blog_admin() && current_user_can('edit_posts') )
		wp_add_dashboard_widget( 'dashboard_right_now', __( 'At a Glance' ), 'wp_dashboard_right_now' );


	// Activity Widget && Comments Widget
	if ( is_blog_admin() ) {
		wp_add_dashboard_widget( 'dashboard_activity', __( 'Activity' ), 'wp_dashboard_site_activity' );
		wp_add_dashboard_widget( 'dashboard_comments', __( 'Comments' ), 'wp_dashboard_site_comments' );
	}


	if ( is_user_admin() ) {

		/**
		 * Fires after core widgets for the User Admin dashboard have been registered.
		 *
		 * @since 3.1.0
		 */
		do_action( 'wp_user_dashboard_setup' );

		/**
		 * Filter the list of widgets to load for the User Admin dashboard.
		 *
		 * @since 3.1.0
		 *
		 * @param array $dashboard_widgets An array of dashboard widgets.
		 */
		$dashboard_widgets = apply_filters( 'wp_user_dashboard_widgets', array() );
	} else {

		/**
		 * Fires after core widgets for the admin dashboard have been registered.
		 *
		 * @since 2.5.0
		 */
		do_action( 'wp_dashboard_setup' );

		/**
		 * Filter the list of widgets to load for the admin dashboard.
		 *
		 * @since 2.5.0
		 *
		 * @param array $dashboard_widgets An array of dashboard widgets.
		 */
		$dashboard_widgets = apply_filters( 'wp_dashboard_widgets', array() );
	}

	foreach ( $dashboard_widgets as $widget_id ) {
		$name = empty( $wp_registered_widgets[$widget_id]['all_link'] ) ? $wp_registered_widgets[$widget_id]['name'] : $wp_registered_widgets[$widget_id]['name'] . " <a href='{$wp_registered_widgets[$widget_id]['all_link']}' class='edit-box open-box'>" . __('View all') . '</a>';
		wp_add_dashboard_widget( $widget_id, $name, $wp_registered_widgets[$widget_id]['callback'], $wp_registered_widget_controls[$widget_id]['callback'] );
	}

	if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['widget_id']) ) {
		check_admin_referer( 'edit-dashboard-widget_' . $_POST['widget_id'], 'dashboard-widget-nonce' );
		ob_start(); // hack - but the same hack wp-admin/widgets.php uses
		wp_dashboard_trigger_widget_control( $_POST['widget_id'] );
		ob_end_clean();
		wp_redirect( remove_query_arg( 'edit' ) );
		exit;
	}

	/** This action is documented in wp-admin/edit-form-advanced.php */
	do_action( 'do_meta_boxes', $screen->id, 'normal', '' );

	/** This action is documented in wp-admin/edit-form-advanced.php */
	do_action( 'do_meta_boxes', $screen->id, 'side', '' );
}

function wp_add_dashboard_widget( $widget_id, $widget_name, $callback, $control_callback = null, $callback_args = null ) {
	$screen = get_current_screen();
	global $wp_dashboard_control_callbacks;

	if ( $control_callback && current_user_can( 'edit_dashboard' ) && is_callable( $control_callback ) ) {
		$wp_dashboard_control_callbacks[$widget_id] = $control_callback;
		if ( isset( $_GET['edit'] ) && $widget_id == $_GET['edit'] ) {
			list($url) = explode( '#', add_query_arg( 'edit', false ), 2 );
			$widget_name .= ' <span class="postbox-title-action"><a href="' . esc_url( $url ) . '">' . __( 'Cancel' ) . '</a></span>';
			$callback = '_wp_dashboard_control_callback';
		} else {
			list($url) = explode( '#', add_query_arg( 'edit', $widget_id ), 2 );
			$widget_name .= ' <span class="postbox-title-action"><a href="' . esc_url( "$url#$widget_id" ) . '" class="edit-box open-box">' . __( 'Configure' ) . '</a></span>';
		}
	}

	$side_widgets = array( );

	$location = 'normal';
	if ( in_array($widget_id, $side_widgets) )
		$location = 'side';

	$priority = 'core';
	if ( 'dashboard_browser_nag' === $widget_id )
		$priority = 'high';

	add_meta_box( $widget_id, $widget_name, $callback, $screen, $location, $priority, $callback_args );
}

function _wp_dashboard_control_callback( $dashboard, $meta_box ) {
	echo '<form method="post" class="dashboard-widget-control-form">';
	wp_dashboard_trigger_widget_control( $meta_box['id'] );
	wp_nonce_field( 'edit-dashboard-widget_' . $meta_box['id'], 'dashboard-widget-nonce' );
	echo '<input type="hidden" name="widget_id" value="' . esc_attr($meta_box['id']) . '" />';
	submit_button( __('Submit') );
	echo '</form>';
}

/**
 * Displays the dashboard.
 *
 * @since 2.5.0
 */
function wp_dashboard() {
	$screen = get_current_screen();
	$columns = absint( $screen->get_columns() );
	$columns_css = '';
	if ( $columns ) {
		$columns_css = " columns-$columns";
	}

?>
<div id="dashboard-widgets" class="metabox-holder<?php echo $columns_css; ?>">
	<div id="postbox-container-1" class="postbox-container">
	<?php do_meta_boxes( $screen->id, 'normal', '' ); ?>
	</div>
	<div id="postbox-container-2" class="postbox-container">
	<?php do_meta_boxes( $screen->id, 'side', '' ); ?>
	</div>
	<div id="postbox-container-3" class="postbox-container">
	<?php do_meta_boxes( $screen->id, 'column3', '' ); ?>
	</div>
	<div id="postbox-container-4" class="postbox-container">
	<?php do_meta_boxes( $screen->id, 'column4', '' ); ?>
	</div>
</div>

<?php
	wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
	wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

}

//
// Dashboard Widgets
//

/**
 * Dashboard widget that displays some basic stats about the site.
 *
 * Formerly 'Right Now'. A streamlined 'At a Glance' as of 3.8.
 *
 * @since 2.7.0
 */
function wp_dashboard_right_now() {
?>
	<div class="main">
	<ul>
	<?php
	// Posts and Pages
	foreach ( array( 'post', 'page' ) as $post_type ) {
		$num_posts = wp_count_posts( $post_type );
		if ( $num_posts && $num_posts->publish ) {
			if ( 'post' == $post_type ) {
				$text = _n( '%s Post', '%s Posts', $num_posts->publish );
			} else {
				$text = _n( '%s Page', '%s Pages', $num_posts->publish );
			}
			$text = sprintf( $text, number_format_i18n( $num_posts->publish ) );
			$post_type_object = get_post_type_object( $post_type );
			if ( $post_type_object && current_user_can( $post_type_object->cap->edit_posts ) ) {
				printf( '<li class="%1$s-count"><a href="edit.php?post_type=%1$s">%2$s</a></li>', $post_type, $text );
			} else {
				printf( '<li class="%1$s-count"><span>%2$s</span></li>', $post_type, $text );
			}

		}
	}
	// Comments
	$num_comm = wp_count_comments();
	if ( $num_comm && $num_comm->approved ) {
		$text = sprintf( _n( '%s Comment', '%s Comments', $num_comm->approved ), number_format_i18n( $num_comm->approved ) );
		?>
		<li class="comment-count"><a href="edit-comments.php"><?php echo $text; ?></a></li>
		<?php
		if ( $num_comm->moderated ) {
			/* translators: Number of comments in moderation */
			$text = sprintf( _nx( '%s in moderation', '%s in moderation', $num_comm->moderated, 'comments' ), number_format_i18n( $num_comm->moderated ) );
			?>
			<li class="comment-mod-count"><a href="edit-comments.php?comment_status=moderated"><?php echo $text; ?></a></li>
			<?php
		}
	}

	/**
	 * Filter the array of extra elements to list in the 'At a Glance'
	 * dashboard widget.
	 *
	 * Prior to 3.8.0, the widget was named 'Right Now'. Each element
	 * is wrapped in list-item tags on output.
	 *
	 * @since 3.8.0
	 *
	 * @param array $items Array of extra 'At a Glance' widget items.
	 */
	$elements = apply_filters( 'dashboard_glance_items', array() );

	if ( $elements ) {
		echo '<li>' . implode( "</li>\n<li>", $elements ) . "</li>\n";
	}

	?>
	</ul>
	<?php
	update_right_now_message();

	// Check if search engines are asked not to index this site.
	if (  ! is_user_admin() && current_user_can( 'manage_options' ) && '1' != get_option( 'blog_public' ) ) {

		/**
		 * Filter the link title attribute for the 'Search Engines Discouraged'
		 * message displayed in the 'At a Glance' dashboard widget.
		 *
		 * Prior to 3.8.0, the widget was named 'Right Now'.
		 *
		 * @since 3.0.0
		 *
		 * @param string $title Default attribute text.
		 */
		$title = apply_filters( 'privacy_on_link_title', __( 'Your site is asking search engines not to index its content' ) );

		/**
		 * Filter the link label for the 'Search Engines Discouraged' message
		 * displayed in the 'At a Glance' dashboard widget.
		 *
		 * Prior to 3.8.0, the widget was named 'Right Now'.
		 *
		 * @since 3.0.0
		 *
		 * @param string $content Default text.
		 */
		$content = apply_filters( 'privacy_on_link_text' , __( 'Search Engines Discouraged' ) );

		echo "<p><a href='options-seo.php' title='$title'>$content</a></p>";
	}
	?>
	</div>
	<?php
	/*
	 * activity_box_end has a core action, but only prints content when multisite.
	 * Using an output buffer is the only way to really check if anything's displayed here.
	 */
	ob_start();

	/**
	 * Fires at the end of the 'At a Glance' dashboard widget.
	 *
	 * Prior to 3.8.0, the widget was named 'Right Now'.
	 *
	 * @since 2.5.0
	 */
	do_action( 'rightnow_end' );

	/**
	 * Fires at the end of the 'At a Glance' dashboard widget.
	 *
	 * Prior to 3.8.0, the widget was named 'Right Now'.
	 *
	 * @since 2.0.0
	 */
	do_action( 'activity_box_end' );

	$actions = ob_get_clean();

	if ( !empty( $actions ) ) : ?>
	<div class="sub">
		<?php echo $actions; ?>
	</div>
	<?php endif;
}




function _wp_dashboard_recent_comments_row( &$comment, $show_date = true ) {
	$GLOBALS['comment'] =& $comment;

	$comment_post_title = _draft_or_post_title( $comment->comment_post_ID );

	if ( current_user_can( 'edit_post', $comment->comment_post_ID ) ) {
		$comment_post_url = get_edit_post_link( $comment->comment_post_ID );
		$comment_post_link = "<a href='$comment_post_url'>$comment_post_title</a>";
	} else {
		$comment_post_link = $comment_post_title;
	}

	$comment_link = '<a class="comment-link" href="' . esc_url(get_comment_link()) . '">#</a>';

	$actions_string = '';
	if ( current_user_can( 'edit_comment', $comment->comment_ID ) ) {
		// Pre-order it: Approve | Reply | Edit | Spam | Trash.
		$actions = array(
			'approve' => '', 'unapprove' => '',
			'reply' => '',
			'edit' => '',
			'spam' => '',
			'trash' => '', 'delete' => ''
		);

		$del_nonce = esc_html( '_wpnonce=' . wp_create_nonce( "delete-comment_$comment->comment_ID" ) );
		$approve_nonce = esc_html( '_wpnonce=' . wp_create_nonce( "approve-comment_$comment->comment_ID" ) );

		$approve_url = esc_url( "comment.php?action=approvecomment&p=$comment->comment_post_ID&c=$comment->comment_ID&$approve_nonce" );
		$unapprove_url = esc_url( "comment.php?action=unapprovecomment&p=$comment->comment_post_ID&c=$comment->comment_ID&$approve_nonce" );
		$spam_url = esc_url( "comment.php?action=spamcomment&p=$comment->comment_post_ID&c=$comment->comment_ID&$del_nonce" );
		$trash_url = esc_url( "comment.php?action=trashcomment&p=$comment->comment_post_ID&c=$comment->comment_ID&$del_nonce" );
		$delete_url = esc_url( "comment.php?action=deletecomment&p=$comment->comment_post_ID&c=$comment->comment_ID&$del_nonce" );

		$actions['approve'] = "<a href='$approve_url' data-wp-lists='dim:the-comment-list:comment-$comment->comment_ID:unapproved:e7e7d3:e7e7d3:new=approved' class='vim-a' title='" . esc_attr__( 'Approve this comment' ) . "'>" . __( 'Approve' ) . '</a>';
		$actions['unapprove'] = "<a href='$unapprove_url' data-wp-lists='dim:the-comment-list:comment-$comment->comment_ID:unapproved:e7e7d3:e7e7d3:new=unapproved' class='vim-u' title='" . esc_attr__( 'Unapprove this comment' ) . "'>" . __( 'Unapprove' ) . '</a>';
		$actions['edit'] = "<a href='comment.php?action=editcomment&amp;c={$comment->comment_ID}' title='" . esc_attr__('Edit comment') . "'>". __('Edit') . '</a>';
		$actions['reply'] = '<a onclick="window.commentReply && commentReply.open(\''.$comment->comment_ID.'\',\''.$comment->comment_post_ID.'\');return false;" class="vim-r hide-if-no-js" title="'.esc_attr__('Reply to this comment').'" href="#">' . __('Reply') . '</a>';
		$actions['spam'] = "<a href='$spam_url' data-wp-lists='delete:the-comment-list:comment-$comment->comment_ID::spam=1' class='vim-s vim-destructive' title='" . esc_attr__( 'Mark this comment as spam' ) . "'>" . /* translators: mark as spam link */ _x( 'Spam', 'verb' ) . '</a>';
		if ( !EMPTY_TRASH_DAYS )
			$actions['delete'] = "<a href='$delete_url' data-wp-lists='delete:the-comment-list:comment-$comment->comment_ID::trash=1' class='delete vim-d vim-destructive'>" . __('Delete Permanently') . '</a>';
		else
			$actions['trash'] = "<a href='$trash_url' data-wp-lists='delete:the-comment-list:comment-$comment->comment_ID::trash=1' class='delete vim-d vim-destructive' title='" . esc_attr__( 'Move this comment to the trash' ) . "'>" . _x('Trash', 'verb') . '</a>';

		/**
		 * Filter the action links displayed for each comment in the 'Recent Comments'
		 * dashboard widget.
		 *
		 * @since 2.6.0
		 *
		 * @param array  $actions An array of comment actions. Default actions include:
		 *                        'Approve', 'Unapprove', 'Edit', 'Reply', 'Spam',
		 *                        'Delete', and 'Trash'.
		 * @param object $comment The comment object.
		 */
		$actions = apply_filters( 'comment_row_actions', array_filter($actions), $comment );

		$i = 0;
		foreach ( $actions as $action => $link ) {
			++$i;
			( ( ('approve' == $action || 'unapprove' == $action) && 2 === $i ) || 1 === $i ) ? $sep = '' : $sep = ' | ';

			// Reply and quickedit need a hide-if-no-js span
			if ( 'reply' == $action || 'quickedit' == $action )
				$action .= ' hide-if-no-js';

			$actions_string .= "<span class='$action'>$sep$link</span>";
		}
	}

?>

		<div id="comment-<?php echo $comment->comment_ID; ?>" <?php comment_class( array( 'comment-item', wp_get_comment_status($comment->comment_ID) ) ); ?>>

			<?php echo get_avatar( $comment, 50, 'mystery' ); ?>
			<div class="dashboard-comment-wrap">
			<h4 class="comment-meta">
				<?php printf( /* translators: 1: comment author, 2: post link, 3: notification if the comment is pending */__( 'From %1$s on %2$s%3$s' ),
					'<cite class="comment-author">' . get_comment_author_link() . '</cite>', $comment_post_link.' '.$comment_link, ' <span class="approve">' . __( '[Pending]' ) . '</span>' ); ?>
			</h4>

			<blockquote><p><?php comment_excerpt(); ?></p></blockquote>
			<p class="row-actions"><?php echo $actions_string; ?></p>
			</div>
		</div>
<?php
}

/**
 * Callback function for Activity widget.
 *
 * @since 3.8.0
 */
function wp_dashboard_site_activity() {

	echo '<div id="activity-widget">';

	$future_posts = wp_dashboard_recent_posts( array(
		'max'     => 5,
		'status'  => 'future',
		'order'   => 'ASC',
		'title'   => __( 'Publishing Soon' ),
		'id'      => 'future-posts',
	) );
	$recent_posts = wp_dashboard_recent_posts( array(
		'max'     => 5,
		'status'  => 'publish',
		'order'   => 'DESC',
		'title'   => __( 'Recently Published' ),
		'id'      => 'published-posts',
	) );


	if ( !$future_posts && !$recent_posts ) {
		echo '<div class="no-activity">';
		echo '<p class="smiley"></p>';
		echo '<p>' . __( 'No activity yet!' ) . '</p>';
		echo '</div>';
	}

	echo '</div>';
}

function wp_dashboard_site_comments() {

	echo '<div id="activity-widget">';

	$recent_comments = wp_dashboard_recent_comments();

	if ( !$recent_comments ) {
		echo '<div class="no-activity">';
		echo '<p class="smiley"></p>';
		echo '<p>' . __( 'No activity yet!' ) . '</p>';
		echo '</div>';
	}

	echo '</div>';
}

/**
 * Generates Publishing Soon and Recently Published sections.
 *
 * @since 3.8.0
 *
 * @param array $args {
 *     An array of query and display arguments.
 *
 *     @type int    $max     Number of posts to display.
 *     @type string $status  Post status.
 *     @type string $order   Designates ascending ('ASC') or descending ('DESC') order.
 *     @type string $title   Section title.
 *     @type string $id      The container id.
 * }
 * @return bool False if no posts were found. True otherwise.
 */
function wp_dashboard_recent_posts( $args ) {
	$query_args = array(
		'post_type'      => 'post',
		'post_status'    => $args['status'],
		'orderby'        => 'date',
		'order'          => $args['order'],
		'posts_per_page' => intval( $args['max'] ),
		'no_found_rows'  => true,
		'cache_results'  => false,
		'perm'           => ( 'future' === $args['status'] ) ? 'editable' : 'readable',
	);

	/**
	 * Filter the query arguments used for the Recent Posts widget.
	 *
	 * @since 4.2.0
	 *
	 * @param array $query_args The arguments passed to WP_Query to produce the list of posts.
	 */
	$query_args = apply_filters( 'dashboard_recent_posts_query_args', $query_args );
	$posts = new WP_Query( $query_args );

	if ( $posts->have_posts() ) {

		echo '<div id="' . $args['id'] . '" class="activity-block">';

		echo '<h4>' . $args['title'] . '</h4>';

		echo '<ul>';

		$today    = date( 'Y-m-d', current_time( 'timestamp' ) );
		$tomorrow = date( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) );

		while ( $posts->have_posts() ) {
			$posts->the_post();

			$time = get_the_time( 'U' );
			if ( date( 'Y-m-d', $time ) == $today ) {
				$relative = __( 'Today' );
			} elseif ( date( 'Y-m-d', $time ) == $tomorrow ) {
				$relative = __( 'Tomorrow' );
			} else {
				/* translators: date and time format for recent posts on the dashboard, see http://php.net/date */
				$relative = date_i18n( __( 'M jS' ), $time );
			}

			// Use the post edit link for those who can edit, the permalink otherwise.
			$recent_post_link = current_user_can( 'edit_post', get_the_ID() ) ? get_edit_post_link() : get_permalink();

			/* translators: 1: relative date, 2: time, 3: post edit link or permalink, 4: post title */
			$format = __( '<span>%1$s, %2$s</span> <a href="%3$s">%4$s</a>' );
			printf( "<li>$format</li>", $relative, get_the_time(), $recent_post_link, _draft_or_post_title() );
		}

		echo '</ul>';
		echo '</div>';

	} else {
		return false;
	}

	wp_reset_postdata();

	return true;
}

/**
 * Show Comments section.
 *
 * @since 3.8.0
 *
 * @param int $total_items Optional. Number of comments to query. Default 5.
 * @return bool False if no comments were found. True otherwise.
 */
function wp_dashboard_recent_comments( $total_items = 5 ) {
	// Select all comment types and filter out spam later for better query performance.
	$comments = array();

	$comments_query = array(
		'number' => $total_items * 5,
		'offset' => 0
	);
	if ( ! current_user_can( 'edit_posts' ) )
		$comments_query['status'] = 'approve';

	while ( count( $comments ) < $total_items && $possible = get_comments( $comments_query ) ) {
		foreach ( $possible as $comment ) {
			if ( ! current_user_can( 'read_post', $comment->comment_post_ID ) )
				continue;
			$comments[] = $comment;
			if ( count( $comments ) == $total_items )
				break 2;
		}
		$comments_query['offset'] += $comments_query['number'];
		$comments_query['number'] = $total_items * 10;
	}

	if ( $comments ) {
		echo '<div id="latest-comments" class="activity-block">';


		echo '<div id="the-comment-list" data-wp-lists="list:comment">';
		foreach ( $comments as $comment )
			_wp_dashboard_recent_comments_row( $comment );
		echo '</div>';

		if ( current_user_can('edit_posts') )
			_get_list_table('WP_Comments_List_Table')->views();

		wp_comment_reply( -1, false, 'dashboard', false );
		wp_comment_trashnotice();

		echo '</div>';
	} else {
		return false;
	}
	return true;
}


/**
 * Checks to see if all of the feed url in $check_urls are cached.
 *
 * If $check_urls is empty, look for the rss feed url found in the dashboard
 * widget options of $widget_id. If cached, call $callback, a function that
 * echoes out output for this widget. If not cache, echo a "Loading..." stub
 * which is later replaced by AJAX call (see top of /wp-admin/index.php)
 *
 * @since 2.5.0
 *
 * @param string $widget_id
 * @param callback $callback
 * @param array $check_urls RSS feeds
 * @return bool False on failure. True on success.
 */
function wp_dashboard_cached_rss_widget( $widget_id, $callback, $check_urls = array() ) {
	$loading = '<p class="widget-loading hide-if-no-js">' . __( 'Loading&#8230;' ) . '</p><p class="hide-if-js">' . __( 'This widget requires JavaScript.' ) . '</p>';
	$doing_ajax = ( defined('DOING_AJAX') && DOING_AJAX );

	if ( empty($check_urls) ) {
		$widgets = get_option( 'dashboard_widget_options' );
		if ( empty($widgets[$widget_id]['url']) && ! $doing_ajax ) {
			echo $loading;
			return false;
		}
		$check_urls = array( $widgets[$widget_id]['url'] );
	}

	$cache_key = 'dash_' . md5( $widget_id );
	if ( false !== ( $output = get_transient( $cache_key ) ) ) {
		echo $output;
		return true;
	}

	if ( ! $doing_ajax ) {
		echo $loading;
		return false;
	}

	if ( $callback && is_callable( $callback ) ) {
		$args = array_slice( func_get_args(), 3 );
		array_unshift( $args, $widget_id, $check_urls );
		ob_start();
		call_user_func_array( $callback, $args );
		set_transient( $cache_key, ob_get_flush(), 12 * HOUR_IN_SECONDS ); // Default lifetime in cache of 12 hours (same as the feeds)
	}

	return true;
}

/* Dashboard Widgets Controls */

// Calls widget_control callback
/**
 * Calls widget control callback.
 *
 * @since 2.5.0
 *
 * @param int $widget_control_id Registered Widget ID.
 */
function wp_dashboard_trigger_widget_control( $widget_control_id = false ) {
	global $wp_dashboard_control_callbacks;

	if ( is_scalar($widget_control_id) && $widget_control_id && isset($wp_dashboard_control_callbacks[$widget_control_id]) && is_callable($wp_dashboard_control_callbacks[$widget_control_id]) ) {
		call_user_func( $wp_dashboard_control_callbacks[$widget_control_id], '', array( 'id' => $widget_control_id, 'callback' => $wp_dashboard_control_callbacks[$widget_control_id] ) );
	}
}





/**
 * Display file upload quota on dashboard.
 *
 * Runs on the activity_box_end hook in wp_dashboard_right_now().
 *
 * @since 3.0.0
 *
 * @return bool|null True if not multisite, user can't upload files, or the space check option is disabled.
*/
function wp_dashboard_quota() {
		return true;
}
add_action( 'activity_box_end', 'wp_dashboard_quota' );

// Display Browser Nag Meta Box
function wp_dashboard_browser_nag() {
	$notice = '';
	$response = wp_check_browser_version();

	if ( $response ) {
		if ( $response['insecure'] ) {
			$msg = sprintf( __( "It looks like you're using an insecure version of <a href='%s'>%s</a>. Using an outdated browser makes your computer unsafe. For the best WordPress experience, please update your browser." ), esc_attr( $response['update_url'] ), esc_html( $response['name'] ) );
		} else {
			$msg = sprintf( __( "It looks like you're using an old version of <a href='%s'>%s</a>. For the best WordPress experience, please update your browser." ), esc_attr( $response['update_url'] ), esc_html( $response['name'] ) );
		}

		$browser_nag_class = '';
		if ( !empty( $response['img_src'] ) ) {
			$img_src = ( is_ssl() && ! empty( $response['img_src_ssl'] ) )? $response['img_src_ssl'] : $response['img_src'];

			$notice .= '<div class="alignright browser-icon"><a href="' . esc_attr($response['update_url']) . '"><img src="' . esc_attr( $img_src ) . '" alt="" /></a></div>';
			$browser_nag_class = ' has-browser-icon';
		}
		$notice .= "<p class='browser-update-nag{$browser_nag_class}'>{$msg}</p>";

		$browsehappy = 'http://browsehappy.com/';
		$locale = get_locale();
		if ( 'en_US' !== $locale )
			$browsehappy = add_query_arg( 'locale', $locale, $browsehappy );

		$notice .= '<p>' . sprintf( __( '<a href="%1$s" class="update-browser-link">Update %2$s</a> or learn how to <a href="%3$s" class="browse-happy-link">browse happy</a>' ), esc_attr( $response['update_url'] ), esc_html( $response['name'] ), esc_url( $browsehappy ) ) . '</p>';
		$notice .= '<p class="hide-if-no-js"><a href="" class="dismiss">' . __( 'Dismiss' ) . '</a></p>';
		$notice .= '<div class="clear"></div>';
	}

	/**
	* Filter the notice output for the 'Browse Happy' nag meta box.
	*
	* @since 3.2.0
	*
	* @param string $notice   The notice content.
	* @param array  $response An array containing web browser information.
	*/
	echo apply_filters( 'browse-happy-notice', $notice, $response );
}

function dashboard_browser_nag_class( $classes ) {
	$response = wp_check_browser_version();

	if ( $response && $response['insecure'] )
		$classes[] = 'browser-insecure';

	return $classes;
}

/**
 * Check if the user needs a browser update
 *
 * @since 3.2.0
 *
 * @return array|bool False on failure, array of browser data on success.
 */
function wp_check_browser_version() {
	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) )
		return false;

	$key = md5( $_SERVER['HTTP_USER_AGENT'] );

	if ( false === ($response = get_site_transient('browser_' . $key) ) ) {
		global $wp_version;

		$options = array(
			'body'			=> array( 'useragent' => $_SERVER['HTTP_USER_AGENT'] ),
			'user-agent'	=> 'WordPress/' . $wp_version . '; ' . site_url()
		);

		$response = wp_remote_post( 'http://api.wordpress.org/core/browse-happy/1.1/', $options );

		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) )
			return false;

		/**
		 * Response should be an array with:
		 *  'name' - string - A user friendly browser name
		 *  'version' - string - The most recent version of the browser
		 *  'current_version' - string - The version of the browser the user is using
		 *  'upgrade' - boolean - Whether the browser needs an upgrade
		 *  'insecure' - boolean - Whether the browser is deemed insecure
		 *  'upgrade_url' - string - The url to visit to upgrade
		 *  'img_src' - string - An image representing the browser
		 *  'img_src_ssl' - string - An image (over SSL) representing the browser
		 */
		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $response ) )
			return false;

		set_site_transient( 'browser_' . $key, $response, WEEK_IN_SECONDS );
	}

	return $response;
}

/**
 * Empty function usable by plugins to output empty dashboard widget (to be populated later by JS).
 */
function wp_dashboard_empty() {}


