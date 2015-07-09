<?php

namespace rapidopress\user;

/**
 * WordPress User Query class.
 *
 * @since 3.1.0
 *
 * @see WP_User_Query::prepare_query() for information on accepted arguments.
 */
class User_Query {

	/**
	 * Query vars, after parsing
	 *
	 * @since 3.5.0
	 * @access public
	 * @var array
	 */
	public $query_vars = array();

	/**
	 * List of found user ids
	 *
	 * @since 3.1.0
	 * @access private
	 * @var array
	 */
	private $results;

	/**
	 * Total number of found users for the current query
	 *
	 * @since 3.1.0
	 * @access private
	 * @var int
	 */
	private $total_users = 0;

	/**
	 * Metadata query container.
	 *
	 * @since 4.2.0
	 * @access public
	 * @var object WP_Meta_Query
	 */
	public $meta_query = false;

	private $compat_fields = array( 'results', 'total_users' );

	// SQL clauses
	public $query_fields;
	public $query_from;
	public $query_where;
	public $query_orderby;
	public $query_limit;

	/**
	 * PHP5 constructor.
	 *
	 * @since 3.1.0
	 *
	 * @param null|string|array $args Optional. The query variables.
	 */
	public function __construct( $query = null ) {
		if ( ! empty( $query ) ) {
			$this->prepare_query( $query );
			$this->query();
		}
	}

	/**
	 * Prepare the query variables.
	 *
	 * @since 3.1.0
	 * @since 4.2.0 Added 'meta_value_num' support for `$orderby` parameter. Added multi-dimensional array syntax
	 *              for `$orderby` parameter.
	 * @access public
	 *
	 * @param string|array $query {
	 *     Optional. Array or string of Query parameters.
	 *
	 *     @type int          $blog_id         The site ID. Default is the global blog id.
	 *     @type string       $role            Role name. Default empty.
	 *     @type string       $meta_key        User meta key. Default empty.
	 *     @type string       $meta_value      User meta value. Default empty.
	 *     @type string       $meta_compare    Comparison operator to test the `$meta_value`. Accepts '=', '!=',
	 *                                         '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN',
	 *                                         'NOT BETWEEN', 'EXISTS', 'NOT EXISTS', 'REGEXP', 'NOT REGEXP',
	 *                                         or 'RLIKE'. Default '='.
	 *     @type array        $include         An array of user IDs to include. Default empty array.
	 *     @type array        $exclude         An array of user IDs to exclude. Default empty array.
	 *     @type string       $search          Search keyword. Searches for possible string matches on columns.
	 *                                         When `$search_columns` is left empty, it tries to determine which
	 *                                         column to search in based on search string. Default empty.
	 *     @type array        $search_columns  Array of column names to be searched. Accepts 'ID', 'login',
	 *                                         'nicename', 'email', 'url'. Default empty array.
	 *     @type string|array $orderby         Field(s) to sort the retrieved users by. May be a single value,
	 *                                         an array of values, or a multi-dimensional array with fields as keys
	 *                                         and orders ('ASC' or 'DESC') as values. Accepted values are'ID',
	 *                                         'display_name' (or 'name'), 'user_login' (or 'login'),
	 *                                         'user_nicename' (or 'nicename'), 'user_email' (or 'email'),
	 *                                         'user_url' (or 'url'), 'user_registered' (or 'registered'),
	 *                                         'post_count', 'meta_value', 'meta_value_num', the value of
	 *                                         `$meta_key`, or an array key of `$meta_query`. To use 'meta_value'
	 *                                         or 'meta_value_num', `$meta_key` must be also be defined.
	 *                                         Default 'user_login'.
	 *     @type string       $order           Designates ascending or descending order of users. Order values
	 *                                         passed as part of an `$orderby` array take precedence over this
	 *                                         parameter. Accepts 'ASC', 'DESC'. Default 'ASC'.
	 *     @type int          $offset          Number of users to offset in retrieved results. Can be used in
	 *                                         conjunction with pagination. Default 0.
	 *     @type int          $number          Number of users to limit the query for. Can be used in conjunction
	 *                                         with pagination. Value -1 (all) is not supported.
	 *                                         Default empty (all users).
	 *     @type bool         $count_total     Whether to count the total number of users found. If pagination is not
	 *                                         needed, setting this to false can improve performance. Default true.
	 *     @type string|array $fields          Which fields to return. Single or all fields (string), or array
	 *                                         of fields. Accepts 'ID', 'display_name', 'login', 'nicename', 'email',
	 *                                         'url', 'registered'. Use 'all' for all fields and 'all_with_meta' to
	 *                                         include meta fields. Default 'all'.
	 *     @type string       $who             Type of users to query. Accepts 'authors'. Default empty (all users).
	 * }
	 */
	public function prepare_query( $query = array() ) {
		global $wpdb;

		if ( empty( $this->query_vars ) || ! empty( $query ) ) {
			$this->query_limit = null;
			$this->query_vars = wp_parse_args( $query, array(
				'blog_id' => $GLOBALS['blog_id'],
				'role' => '',
				'meta_key' => '',
				'meta_value' => '',
				'meta_compare' => '',
				'include' => array(),
				'exclude' => array(),
				'search' => '',
				'search_columns' => array(),
				'orderby' => 'login',
				'order' => 'ASC',
				'offset' => '',
				'number' => '',
				'count_total' => true,
				'fields' => 'all',
				'who' => ''
			) );
		}

		/**
		 * Fires before the WP_User_Query has been parsed.
		 *
		 * The passed WP_User_Query object contains the query variables, not
		 * yet passed into SQL.
		 *
		 * @since 4.0.0
		 *
		 * @param WP_User_Query $this The current WP_User_Query instance,
		 *                            passed by reference.
		 */
		do_action( 'pre_get_users', $this );

		$qv =& $this->query_vars;

		if ( is_array( $qv['fields'] ) ) {
			$qv['fields'] = array_unique( $qv['fields'] );

			$this->query_fields = array();
			foreach ( $qv['fields'] as $field ) {
				$field = 'ID' === $field ? 'ID' : sanitize_key( $field );
				$this->query_fields[] = "$wpdb->users.$field";
			}
			$this->query_fields = implode( ',', $this->query_fields );
		} elseif ( 'all' == $qv['fields'] ) {
			$this->query_fields = "$wpdb->users.*";
		} else {
			$this->query_fields = "$wpdb->users.ID";
		}

		if ( isset( $qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;

		$this->query_from = "FROM $wpdb->users";
		$this->query_where = "WHERE 1=1";

		// Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
		if ( ! empty( $qv['include'] ) ) {
			$include = wp_parse_id_list( $qv['include'] );
		} else {
			$include = false;
		}

		$blog_id = 0;
		if ( isset( $qv['blog_id'] ) ) {
			$blog_id = absint( $qv['blog_id'] );
		}

		if ( isset( $qv['who'] ) && 'authors' == $qv['who'] && $blog_id ) {
			$qv['meta_key'] = $wpdb->get_blog_prefix( $blog_id ) . 'user_level';
			$qv['meta_value'] = 0;
			$qv['meta_compare'] = '!=';
			$qv['blog_id'] = $blog_id = 0; // Prevent extra meta query
		}

		// Meta query.
		$this->meta_query = new \WP_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );

		$role = '';
		if ( isset( $qv['role'] ) ) {
			$role = trim( $qv['role'] );
		}

		if ( $blog_id && ( $role  ) ) {
			$cap_meta_query = array();
			$cap_meta_query['key'] = $wpdb->get_blog_prefix( $blog_id ) . 'capabilities';

			if ( $role ) {
				$cap_meta_query['value'] = '"' . $role . '"';
				$cap_meta_query['compare'] = 'like';
			}

			if ( empty( $this->meta_query->queries ) ) {
				$this->meta_query->queries = array( $cap_meta_query );
			} elseif ( ! in_array( $cap_meta_query, $this->meta_query->queries, true ) ) {
				// Append the cap query to the original queries and reparse the query.
				$this->meta_query->queries = array(
					'relation' => 'AND',
					array( $this->meta_query->queries, $cap_meta_query ),
				);
			}

			$this->meta_query->parse_query_vars( $this->meta_query->queries );
		}

		if ( ! empty( $this->meta_query->queries ) ) {
			$clauses = $this->meta_query->get_sql( 'user', $wpdb->users, 'ID', $this );
			$this->query_from .= $clauses['join'];
			$this->query_where .= $clauses['where'];

			if ( 'OR' == $this->meta_query->relation ) {
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
			}
		}

		// sorting
		$qv['order'] = isset( $qv['order'] ) ? strtoupper( $qv['order'] ) : '';
		$order = $this->parse_order( $qv['order'] );

		if ( empty( $qv['orderby'] ) ) {
			// Default order is by 'user_login'.
			$ordersby = array( 'user_login' => $order );
		} else if ( is_array( $qv['orderby'] ) ) {
			$ordersby = $qv['orderby'];
		} else {
			// 'orderby' values may be a comma- or space-separated list.
			$ordersby = preg_split( '/[,\s]+/', $qv['orderby'] );
		}

		$orderby_array = array();
		foreach ( $ordersby as $_key => $_value ) {
			if ( ! $_value ) {
				continue;
			}

			if ( is_int( $_key ) ) {
				// Integer key means this is a flat array of 'orderby' fields.
				$_orderby = $_value;
				$_order = $order;
			} else {
				// Non-integer key means this the key is the field and the value is ASC/DESC.
				$_orderby = $_key;
				$_order = $_value;
			}

			$parsed = $this->parse_orderby( $_orderby );

			if ( ! $parsed ) {
				continue;
			}

			$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
		}

		// If no valid clauses were found, order by user_login.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = "user_login $order";
		}

		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

		// limit
		if ( isset( $qv['number'] ) && $qv['number'] ) {
			if ( $qv['offset'] )
				$this->query_limit = $wpdb->prepare("LIMIT %d, %d", $qv['offset'], $qv['number']);
			else
				$this->query_limit = $wpdb->prepare("LIMIT %d", $qv['number']);
		}

		$search = '';
		if ( isset( $qv['search'] ) )
			$search = trim( $qv['search'] );

		if ( $search ) {
			$leading_wild = ( ltrim($search, '*') != $search );
			$trailing_wild = ( rtrim($search, '*') != $search );
			if ( $leading_wild && $trailing_wild )
				$wild = 'both';
			elseif ( $leading_wild )
				$wild = 'leading';
			elseif ( $trailing_wild )
				$wild = 'trailing';
			else
				$wild = false;
			if ( $wild )
				$search = trim($search, '*');

			$search_columns = array();
			if ( $qv['search_columns'] )
				$search_columns = array_intersect( $qv['search_columns'], array( 'ID', 'user_login', 'user_email', 'user_url', 'user_nicename' ) );
			if ( ! $search_columns ) {
				if ( false !== strpos( $search, '@') )
					$search_columns = array('user_email');
				elseif ( is_numeric($search) )
					$search_columns = array('user_login', 'ID');
				elseif ( preg_match('|^https?://|', $search)  )
					$search_columns = array('user_url');
				else
					$search_columns = array('user_login', 'user_nicename');
			}

			/**
			 * Filter the columns to search in a WP_User_Query search.
			 *
			 * The default columns depend on the search term, and include 'user_email',
			 * 'user_login', 'ID', 'user_url', and 'user_nicename'.
			 *
			 * @since 3.6.0
			 *
			 * @param array         $search_columns Array of column names to be searched.
			 * @param string        $search         Text being searched.
			 * @param WP_User_Query $this           The current WP_User_Query instance.
			 */
			$search_columns = apply_filters( 'user_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		if ( ! empty( $include ) ) {
			// Sanitized earlier.
			$ids = implode( ',', $include );
			$this->query_where .= " AND $wpdb->users.ID IN ($ids)";
		} elseif ( ! empty( $qv['exclude'] ) ) {
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND $wpdb->users.ID NOT IN ($ids)";
		}

		// Date queries are allowed for the user_registered field.
		if ( ! empty( $qv['date_query'] ) && is_array( $qv['date_query'] ) ) {
			$date_query = new \WP_Date_Query( $qv['date_query'], 'user_registered' );
			$this->query_where .= $date_query->get_sql();
		}

		/**
		 * Fires after the WP_User_Query has been parsed, and before
		 * the query is executed.
		 *
		 * The passed WP_User_Query object contains SQL parts formed
		 * from parsing the given query.
		 *
		 * @since 3.1.0
		 *
		 * @param WP_User_Query $this The current WP_User_Query instance,
		 *                            passed by reference.
		 */
		do_action_ref_array( 'pre_user_query', array( &$this ) );
	}

	/**
	 * Execute the query, with the current variables.
	 *
	 * @since 3.1.0
	 *
	 * @global wpdb $wpdb WordPress database object for queries.
	 */
	public function query() {
		global $wpdb;

		$qv =& $this->query_vars;

		$query = "SELECT $this->query_fields $this->query_from $this->query_where $this->query_orderby $this->query_limit";

		if ( is_array( $qv['fields'] ) || 'all' == $qv['fields'] ) {
			$this->results = $wpdb->get_results( $query );
		} else {
			$this->results = $wpdb->get_col( $query );
		}

		/**
		 * Filter SELECT FOUND_ROWS() query for the current WP_User_Query instance.
		 *
		 * @since 3.2.0
		 *
		 * @global wpdb $wpdb WordPress database abstraction object.
		 *
		 * @param string $sql The SELECT FOUND_ROWS() query for the current WP_User_Query.
		 */
		if ( isset( $qv['count_total'] ) && $qv['count_total'] )
			$this->total_users = $wpdb->get_var( apply_filters( 'found_users_query', 'SELECT FOUND_ROWS()' ) );

		if ( !$this->results )
			return;

		if ( 'all_with_meta' == $qv['fields'] ) {
			cache_users( $this->results );

			$r = array();
			foreach ( $this->results as $userid )
				$r[ $userid ] = new User( $userid, '', $qv['blog_id'] );

			$this->results = $r;
		} elseif ( 'all' == $qv['fields'] ) {
			foreach ( $this->results as $key => $user ) {
				$this->results[ $key ] = new User( $user, '', $qv['blog_id'] );
			}
		}
	}

	/**
	 * Retrieve query variable.
	 *
	 * @since 3.5.0
	 * @access public
	 *
	 * @param string $query_var Query variable key.
	 * @return mixed
	 */
	public function get( $query_var ) {
		if ( isset( $this->query_vars[$query_var] ) )
			return $this->query_vars[$query_var];

		return null;
	}

	/**
	 * Set query variable.
	 *
	 * @since 3.5.0
	 * @access public
	 *
	 * @param string $query_var Query variable key.
	 * @param mixed $value Query variable value.
	 */
	public function set( $query_var, $value ) {
		$this->query_vars[$query_var] = $value;
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns
	 *
	 * @access protected
	 * @since 3.1.0
	 *
	 * @param string $string
	 * @param array $cols
	 * @param bool $wild Whether to allow wildcard searches. Default is false for Network Admin, true for
	 *  single site. Single site allows leading and trailing wildcards, Network Admin only trailing.
	 * @return string
	 */
	protected function get_search_sql( $string, $cols, $wild = false ) {
		global $wpdb;

		$searches = array();
		$leading_wild = ( 'leading' == $wild || 'both' == $wild ) ? '%' : '';
		$trailing_wild = ( 'trailing' == $wild || 'both' == $wild ) ? '%' : '';
		$like = $leading_wild . $wpdb->esc_like( $string ) . $trailing_wild;

		foreach ( $cols as $col ) {
			if ( 'ID' == $col ) {
				$searches[] = $wpdb->prepare( "$col = %s", $string );
			} else {
				$searches[] = $wpdb->prepare( "$col LIKE %s", $like );
			}
		}

		return ' AND (' . implode(' OR ', $searches) . ')';
	}

	/**
	 * Return the list of users.
	 *
	 * @since 3.1.0
	 * @access public
	 *
	 * @return array Array of results.
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * Return the total number of users for the current query.
	 *
	 * @since 3.1.0
	 * @access public
	 *
	 * @return int Number of total users.
	 */
	public function get_total() {
		return $this->total_users;
	}

	/**
	 * Parse and sanitize 'orderby' keys passed to the user query.
	 *
	 * @since 4.2.0
	 * @access protected
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $orderby Alias for the field to order by.
	 * @return string|bool Value to used in the ORDER clause, if `$orderby` is valid. False otherwise.
	 */
	protected function parse_orderby( $orderby ) {
		global $wpdb;

		$meta_query_clauses = $this->meta_query->get_clauses();

		$_orderby = '';
		if ( in_array( $orderby, array( 'login', 'nicename', 'email', 'url', 'registered' ) ) ) {
			$_orderby = 'user_' . $orderby;
		} elseif ( in_array( $orderby, array( 'user_login', 'user_nicename', 'user_email', 'user_url', 'user_registered' ) ) ) {
			$_orderby = $orderby;
		} elseif ( 'name' == $orderby || 'display_name' == $orderby ) {
			$_orderby = 'display_name';
		} elseif ( 'post_count' == $orderby ) {
			// todo: avoid the JOIN
			$where = get_posts_by_author_sql( 'post' );
			$this->query_from .= " LEFT OUTER JOIN (
				SELECT post_author, COUNT(*) as post_count
				FROM $wpdb->posts
				$where
				GROUP BY post_author
			) p ON ({$wpdb->users}.ID = p.post_author)
			";
			$_orderby = 'post_count';
		} elseif ( 'ID' == $orderby || 'id' == $orderby ) {
			$_orderby = 'ID';
		} elseif ( 'meta_value' == $orderby || $this->get( 'meta_key' ) == $orderby ) {
			$_orderby = "$wpdb->usermeta.meta_value";
		} elseif ( 'meta_value_num' == $orderby ) {
			$_orderby = "$wpdb->usermeta.meta_value+0";
		} elseif ( 'include' === $orderby && ! empty( $this->query_vars['include'] ) ) {
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( $wpdb->users.ID, $include_sql )";
		} elseif ( isset( $meta_query_clauses[ $orderby ] ) ) {
			$meta_clause = $meta_query_clauses[ $orderby ];
			$_orderby = sprintf( "CAST(%s.meta_value AS %s)", esc_sql( $meta_clause['alias'] ), esc_sql( $meta_clause['cast'] ) );
		}

		return $_orderby;
	}

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 *
	 * @since 4.2.0
	 * @access protected
	 *
	 * @param string $order The 'order' query variable.
	 * @return string The sanitized 'order' query variable.
	 */
	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}

	/**
	 * Make private properties readable for backwards compatibility.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param string $name Property to get.
	 * @return mixed Property.
	 */
	public function __get( $name ) {
		if ( in_array( $name, $this->compat_fields ) ) {
			return $this->$name;
		}
	}

	/**
	 * Make private properties settable for backwards compatibility.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param string $name  Property to check if set.
	 * @param mixed  $value Property value.
	 * @return mixed Newly-set property.
	 */
	public function __set( $name, $value ) {
		if ( in_array( $name, $this->compat_fields ) ) {
			return $this->$name = $value;
		}
	}

	/**
	 * Make private properties checkable for backwards compatibility.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param string $name Property to check if set.
	 * @return bool Whether the property is set.
	 */
	public function __isset( $name ) {
		if ( in_array( $name, $this->compat_fields ) ) {
			return isset( $this->$name );
		}
	}

	/**
	 * Make private properties un-settable for backwards compatibility.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param string $name Property to unset.
	 */
	public function __unset( $name ) {
		if ( in_array( $name, $this->compat_fields ) ) {
			unset( $this->$name );
		}
	}

	/**
	 * Make private/protected methods readable for backwards compatibility.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param callable $name      Method to call.
	 * @param array    $arguments Arguments to pass when calling.
	 * @return mixed|bool Return value of the callback, false otherwise.
	 */
	public function __call( $name, $arguments ) {
		if ( 'get_search_sql' === $name ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		}
		return false;
	}
}

