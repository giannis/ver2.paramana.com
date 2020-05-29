<?php
/**
 * DB Class
 *
 * delivericious.gr
 * Version: 1.1
 * Started: 02-02-2010
 * Updated: 29-07-2010
 * http://www.delivericious.gr
 *
 * Original code from {@link http://php.justinvincent.com Justin Vincent (justin@visunet.ie)}
 * and from wordpress 2.9.2 {@link http://wordpress.org/}
 */

/**
 * 
 */
define('EZSQL_VERSION', 'WP1.25');

/**
 * 
 */
define('OBJECT', 'OBJECT', true);

/**
 * 
 */
define('OBJECT_K', 'OBJECT_K', false);

/**
 * 
 */
define('ARRAY_A', 'ARRAY_A', false);

/**
 * 
 */
define('ARRAY_N', 'ARRAY_N', false);

/**
 * Database Access Abstraction Object
 *
 * It is possible to replace this class with your own
 * by setting the $idb global variable in wp-content/db.php
 * file with your class. You can name it idb also, since
 * this file will not be included, if the other file is
 * available.
 */
class idb {

	/**
	 * Whether to show SQL/DB errors
	 *
	 * @access private
	 * @var bool
	 */
	var $show_errors = true;

	/**
	 * Whether to suppress errors during the DB bootstrapping.
	 *
	 * @access private
	 * @since {@internal Version Unknown}}
	 * @var bool
	 */
	var $suppress_errors = false;

	/**
	 * The last error during query.
	 *
	 * @since {@internal Version Unknown}}
	 * @var string
	 */
	var $last_error = '';

	/**
	 * Amount of queries made
	 *
	 * @access private
	 * @var int
	 */
	var $num_queries = 0;

	/**
	 * Saved result of the last query made
	 *
	 * @access private
	 * @var array
	 */
	var $last_query;

	/**
	 * Saved info on the table column
	 *
	 * @access private
	 * @var array
	 */
	var $col_info;

	/**
	 * Saved queries that were executed
	 *
	 * @access private
	 * @var array
	 */
	var $queries;

	/**
	 * Whether the database queries are ready to start executing.
	 *
	 * @access private
	 * @var bool
	 */
	var $ready = false;

	/**
	 * Database table columns charset
	 *
	 * @access public
	 * @var string
	 */
	var $charset;

	/**
	 * Database table columns collate
	 *
	 * @access public
	 * @var string
	 */
	var $collate;

	/**
	 * Whether to use mysql_real_escape_string
	 *
	 * @access public
	 * @var bool
	 */
	var $real_escape = false;

	/**
	 * Database Username
	 *
	 * @access private
	 * @var string
	 */
	var $dbuser;

    /**
	 * A textual description of the last query/get_row/get_var call
	 *
	 * @since unknown
	 * @access public
	 * @var string
	 */
	var $func_call;

    /**
	 * Set to true to enable disk cache
	 *
	 * @since 1.1
	 * @access public
	 * @var boolean
	 */
    var $use_disk_cache = false;

    /**
	 * The directory for the cache
     * Specify a cache dir.
	 *
	 * @since 1.1
	 * @access public
	 * @var boolean
	 */
    var $cache_dir = false;
    
    /**
	 * By wrapping up queries you can ensure that the default
     * is NOT to cache unless specified
	 *
	 * @since 1.1
	 * @access public
	 * @var boolean
	 */
    var $cache_queries = false;

    /**
	 * Cache expiry
	 * Note: this is hours
     *
	 * @since 1.1
	 * @access public
	 * @var int
	 */
    var $cache_timeout = 24;

    /**
	 * If true it caches the inserts also
	 *
	 * @since 1.1
	 * @access public
	 * @var boolean
	 */
    var $cache_inserts = false;

	/**
	 * Connects to the database server and selects a database
	 *
	 * PHP4 compatibility layer for calling the PHP5 constructor.
     * Enable it in PHP4 enviroment
	 *
	 * @uses idb::__construct() Passes parameters and returns result
	 *
	 * @param string $dbuser MySQL database user
	 * @param string $dbpassword MySQL database password
	 * @param string $dbname MySQL database name
	 * @param string $dbhost MySQL database host
	 */
	//function idb($dbuser, $dbpassword, $dbname, $dbhost) {
	///	return $this->__construct($dbuser, $dbpassword, $dbname, $dbhost);
	//}

	/**
	 * Connects to the database server and selects a database
	 *
	 * PHP5 style constructor for compatibility with PHP5. Does
	 * the actual setting up of the class properties and connection
	 * to the database.
	 *
	 * @param string $dbuser MySQL database user
	 * @param string $dbpassword MySQL database password
	 * @param string $dbname MySQL database name
	 * @param string $dbhost MySQL database host
	 */
	function __construct($dbuser, $dbpassword, $dbname, $dbhost) {
		register_shutdown_function(array(&$this, "__destruct"));

        //override defaults
        if (defined('DB_DISK_CACHE'))
            $this->use_disk_cache = DB_DISK_CACHE;

        if (defined('DB_CACHE_DIR'))
            $this->cache_dir = dirname(__FILE__) . '/' . preg_replace('%^/%', '', DB_CACHE_DIR);

        if (defined('DB_CACHE_QUERIES'))
            $this->cache_queries = DB_CACHE_QUERIES;

        if (defined('DB_CACHE_TIMEOUT'))
            $this->cache_timeout = DB_CACHE_TIMEOUT;

        if (defined('DB_CACHE_INSERTS'))
            $this->cache_inserts = DB_CACHE_INSERTS;

		if (defined('DEBUG') && DEBUG)
			$this->show_errors();

		if ( defined('DB_CHARSET') )
			$this->charset = DB_CHARSET;

		if ( defined('DB_COLLATE') )
			$this->collate = DB_COLLATE;

		$this->dbuser = $dbuser;

		$this->dbh = @mysql_connect($dbhost, $dbuser, $dbpassword, true);
		if (!$this->dbh) {
			$this->bail(sprintf("
<h1>Error establishing a database connection</h1>
<p>This either means that the username and password information in your <code>config.php</code> file is incorrect or we can't contact the database server at <code>%s</code>.
This could mean your host's database server is down.</p>
<ul>
	<li>Are you sure you have the correct username and password?</li>
	<li>Are you sure that you have typed the correct hostname?</li>
	<li>Are you sure that the database server is running?</li>
</ul>
<p>If you're unsure what these terms mean you should probably contact your host.</p>
", $dbhost), 'db_connect_fail');
			return;
		}

		$this->ready = true;

		if ( $this->has_cap( 'collation' ) && !empty($this->charset) ) {
			if ( function_exists('mysql_set_charset') ) {
				mysql_set_charset($this->charset, $this->dbh);
				$this->real_escape = true;
			} else {
				$collation_query = "SET NAMES '{$this->charset}'";
				if ( !empty($this->collate) )
					$collation_query .= " COLLATE '{$this->collate}'";
				$this->query($collation_query);
			}
		}

		$this->select($dbname);
	}

	/**
	 * PHP5 style destructor and will run when database object is destroyed.
	 *
	 * @since 2.0.8
	 *
	 * @return bool Always true
	 */
	function __destruct() {
		return true;
	}

	/**
	 * Selects a database using the current database connection.
	 *
	 * The database name will be changed based on the current database
	 * connection. On failure, the execution will bail and display an DB error.
	 *
	 * @param string $db MySQL database name
	 * @return null Always null.
	 */
	function select($db) {
		if (!@mysql_select_db($db, $this->dbh)) {
			$this->ready = false;
			$this->bail(sprintf('
<h1>Can&#8217;t select database</h1>
<p>We were able to connect to the database server (which means your username and password is okay) but not able to select the <code>%1$s</code> database.</p>
<ul>
<li>Are you sure it exists?</li>
<li>Does the user <code>%2$s</code> have permission to use the <code>%1$s</code> database?</li>
<li>On some systems the name of your database is prefixed with your username, so it would be like <code>username_%1$s</code>. Could that be the problem?</li>
</ul>
<p>If you don\'t know how to setup a database you should <strong>contact your host</strong>.</p>', $db, $this->dbuser), 'db_select_fail');
			return;
		}
	}

	function _weak_escape($string) {
		return addslashes($string);
	}

	function _real_escape($string) {
		if ( $this->dbh && $this->real_escape )
			return mysql_real_escape_string( $string, $this->dbh );
		else
			return addslashes( $string );
	}

	function _escape($data) {
		if ( is_array($data) ) {
			foreach ( (array) $data as $k => $v ) {
				if ( is_array($v) )
					$data[$k] = $this->_escape( $v );
				else
					$data[$k] = $this->_real_escape( $v );
			}
		} else {
			$data = $this->_real_escape( $data );
		}

		return $data;
	}

	/**
	 * Escapes content for insertion into the database using addslashes(), for security
	 *
	 * @param string|array $data
	 * @return string query safe string
	 */
	function escape($data) {
		if ( is_array($data) ) {
			foreach ( (array) $data as $k => $v ) {
				if ( is_array($v) )
					$data[$k] = $this->escape( $v );
				else
					$data[$k] = $this->_weak_escape( $v );
			}
		} else {
			$data = $this->_weak_escape( $data );
		}

		return $data;
	}

	/**
	 * Escapes content by reference for insertion into the database, for security
	 *
	 * @param string $s
	 */
	function escape_by_ref(&$string) {
		$string = $this->_real_escape( $string );
	}

	/**
	 * Prepares a SQL query for safe execution.  Uses sprintf()-like syntax.
	 *
	 * This function only supports a small subset of the sprintf syntax; it only supports %d (decimal number), %s (string).
	 * Does not support sign, padding, alignment, width or precision specifiers.
	 * Does not support argument numbering/swapping.
	 *
	 * May be called like {@link http://php.net/sprintf sprintf()} or like {@link http://php.net/vsprintf vsprintf()}.
	 *
	 * Both %d and %s should be left unquoted in the query string.
	 *
	 * <code>
	 * idb::prepare( "SELECT * FROM `table` WHERE `column` = %s AND `field` = %d", "foo", 1337 )
	 * </code>
	 *
	 * @link http://php.net/sprintf Description of syntax.
	 *
	 * @param string $query Query statement with sprintf()-like placeholders
	 * @param array|mixed $args The array of variables to substitute into the query's placeholders if being called like {@link http://php.net/vsprintf vsprintf()}, or the first variable to substitute into the query's placeholders if being called like {@link http://php.net/sprintf sprintf()}.
	 * @param mixed $args,... further variables to substitute into the query's placeholders if being called like {@link http://php.net/sprintf sprintf()}.
	 * @return null|string Sanitized query string
	 */
	function prepare($query = null) { // ( $query, *$args )
		if ( is_null( $query ) )
			return;
		$args = func_get_args();
		array_shift($args);
		// If args were passed as an array (as in vsprintf), move them up
		if ( isset($args[0]) && is_array($args[0]) )
			$args = $args[0];
		$query = str_replace("'%s'", '%s', $query); // in case someone mistakenly already singlequoted it
		$query = str_replace('"%s"', '%s', $query); // doublequote unquoting
		$query = str_replace('%s', "'%s'", $query); // quote the strings
		array_walk($args, array(&$this, 'escape_by_ref'));
		return @vsprintf($query, $args);
	}

	/**
	 * Print SQL/DB error.
	 *
	 * @global array $EZSQL_ERROR Stores error information of query and error string
	 *
	 * @param string $str The error to display
	 * @return bool False if the showing of errors is disabled.
	 */
	function print_error($str = '') {
		global $EZSQL_ERROR;

		if (!$str) $str = mysql_error($this->dbh);
		$EZSQL_ERROR[] = array ('query' => $this->last_query, 'error_str' => $str);

		if ( $this->suppress_errors )
			return false;

		if ( $caller = $this->get_caller() )
			$error_str = sprintf('Database error %1$s for query %2$s made by %3$s', $str, $this->last_query, $caller);
		else
			$error_str = sprintf('Database error %1$s for query %2$s', $str, $this->last_query);

		$log_error = true;
		if ( ! function_exists('error_log') )
			$log_error = false;

		$log_file = @ini_get('error_log');
		if ( !empty($log_file) && ('syslog' != $log_file) && !@is_writable($log_file) )
			$log_error = false;

		if ( $log_error )
			@error_log($error_str, 0);

		// Is error output turned on or not..
		if ( !$this->show_errors )
			return false;

		$str = htmlspecialchars($str, ENT_QUOTES);
		$query = htmlspecialchars($this->last_query, ENT_QUOTES);

		// If there is an error then take note of it
		print "<div id='error'>
		<p class='idberror'><strong>Database error:</strong> [$str]<br />
		<code>$query</code></p>
		</div>";
	}

	/**
	 * Enables showing of database errors.
	 *
	 * This function should be used only to enable showing of errors.
	 * idb::hide_errors() should be used instead for hiding of errors. However,
	 * this function can be used to enable and disable showing of database
	 * errors.
	 *
	 * @param bool $show Whether to show or hide errors
	 * @return bool Old value for showing errors.
	 */
	function show_errors( $show = true ) {
		$errors = $this->show_errors;
		$this->show_errors = $show;
		return $errors;
	}

	/**
	 * Disables showing of database errors.
	 *
	 * @return bool Whether showing of errors was active or not
	 */
	function hide_errors() {
		$show = $this->show_errors;
		$this->show_errors = false;
		return $show;
	}

	/**
	 * Whether to suppress database errors.
	 *
	 * @param unknown_type $suppress
	 * @return unknown
	 */
	function suppress_errors( $suppress = true ) {
		$errors = $this->suppress_errors;
		$this->suppress_errors = $suppress;
		return $errors;
	}

	/**
	 * Kill cached query results.
	 */
	function flush() {
		$this->last_result = array();
		$this->col_info = null;
		$this->last_query = null;
	}

	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * More information can be found on the codex page.
	 *
	 * @param string $query
	 * @return int|false Number of rows affected/selected or false on error
	 */
	function query($query) {
		if ( ! $this->ready )
			return false;

		// initialise return
		$return_val = 0;
		$this->flush();

		// For reg expressions
		$query = trim($query);

		// Log how the function was called
		$this->func_call = "\$db->query(\"$query\")";

		// Keep track of the last query for debug..
		$this->last_query = $query;

        // Count how many queries there have been
        $this->num_queries++;

        // Use core file cache function
        if ( $cache = $this->get_cache($query) )
            return $cache;

		// Perform the query via std mysql_query function..
		if ( defined('SAVEQUERIES') && SAVEQUERIES )
			$this->timer_start();

		$this->result = @mysql_query($query, $this->dbh);
		

		if ( defined('SAVEQUERIES') && SAVEQUERIES )
			$this->queries[] = array( $query, $this->timer_stop(), $this->get_caller() );

		// If there is an error then take note of it..
		if ( $this->last_error = mysql_error($this->dbh) ) {
            $is_insert = true;
			$this->print_error();
			return false;
		}

        // Query was an insert, delete, update, replace
        $is_insert = false;
		if ( preg_match("/^\\s*(insert|delete|update|replace|alter) /i",$query) ) {
            $is_insert = true;
			$this->rows_affected = mysql_affected_rows($this->dbh);
			// Take note of the insert_id
			if ( preg_match("/^\\s*(insert|replace) /i",$query) ) {
				$this->insert_id = mysql_insert_id($this->dbh);
			}
			// Return number of rows affected
			$return_val = $this->rows_affected;
		} else {
			$i = 0;
			while ($i < @mysql_num_fields($this->result)) {
				$this->col_info[$i] = @mysql_fetch_field($this->result);
				$i++;
			}
			$num_rows = 0;
			while ( $row = @mysql_fetch_object($this->result) ) {
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}

			@mysql_free_result($this->result);

			// Log number of rows the query returned
			$this->num_rows = $num_rows;

			// Return number of rows selected
			$return_val = $this->num_rows;
		}

        // disk caching of queries
        $this->store_cache($query, $is_insert);

		return $return_val;
	}

	/**
	 * Insert a row into a table.
	 *
	 * <code>
	 * wpdb::insert( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
	 * wpdb::insert( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
	 * </code>
	 *
	 * @since 2.5.0
	 * @see wpdb::prepare()
	 * @see wpdb::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%s' (decimal number, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	function insert( $table, $data, $format = null ) {
		return $this->_insert_replace_helper( $table, $data, $format, 'INSERT' );
	}

    /**
	 * Replace a row into a table.
	 *
	 * <code>
	 * idb::replace( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
	 * idb::replace( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
	 * </code>
	 *
	 * @since 3.0.0
	 * @see idb::prepare()
	 * @see idb::$field_types
	 * @see wp_set_idb_vars()
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%s' (decimal number, string). If omitted, all values in $data will be treated as strings unless otherwise specified in idb::$field_types.
	 * @return int|false The number of rows affected, or false on error.
	 */
	function replace( $table, $data, $format = null ) {
		return $this->_insert_replace_helper( $table, $data, $format, 'REPLACE' );
	}

	/**
	 * Helper function for insert and replace.
	 *
	 * Runs an insert or replace query based on $type argument.
	 *
	 * @access private
	 * @since 3.0.0
	 * @see idb::prepare()
	 * @see idb::$field_types
	 * @see wp_set_idb_vars()
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs).  Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%s' (decimal number, string). If omitted, all values in $data will be treated as strings unless otherwise specified in idb::$field_types.
	 * @return int|false The number of rows affected, or false on error.
	 */
	function _insert_replace_helper( $table, $data, $format = null, $type = 'INSERT' ) {
		if ( ! in_array( strtoupper( $type ), array( 'REPLACE', 'INSERT' ) ) )
			return false;
		$formats = $format = (array) $format;
		$fields = array_keys( $data );
		$formatted_fields = array();
		foreach ( $fields as $field ) {
			if ( !empty( $format ) )
				$form = ( $form = array_shift( $formats ) ) ? $form : $format[0];
			else
				$form = '%s';
			$formatted_fields[] = $form;
		}
		$sql = "{$type} INTO `$table` (`" . implode( '`,`', $fields ) . "`) VALUES ('" . implode( "','", $formatted_fields ) . "')";
		return $this->query( $this->prepare( $sql, $data ) );
	}

	/**
	 * Update a row in the table
	 *
	 * <code>
	 * idb::update( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( 'ID' => 1 ), array( '%s', '%d' ), array( '%d' ) )
	 * </code>
	 *
	 * @see idb::prepare()
	 *
	 * @param string $table table name
	 * @param array $data Data to update (in column => value pairs).  Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array $where A named array of WHERE clauses (in column => value pairs).  Multiple clauses will be joined with ANDs.  Both $where columns and $where values should be "raw".
	 * @param array|string $format (optional) An array of formats to be mapped to each of the values in $data.  If string, that format will be used for all of the values in $data.  A format is one of '%d', '%s' (decimal number, string).  If omitted, all values in $data will be treated as strings.
	 * @param array|string $format_where (optional) An array of formats to be mapped to each of the values in $where.  If string, that format will be used for all of  the items in $where.  A format is one of '%d', '%s' (decimal number, string).  If omitted, all values in $where will be treated as strings.
	 * @return int|false The number of rows updated, or false on error.
	 */
	function update($table, $data, $where, $format = null, $where_format = null) {
		if ( !is_array( $where ) )
			return false;

		$formats = $format = (array) $format;
		$bits = $wheres = array();
		foreach ( (array) array_keys($data) as $field ) {
			if ( !empty($format) )
				$form = ( $form = array_shift($formats) ) ? $form : $format[0];
			else
				$form = '%s';
			$bits[] = "`$field` = {$form}";
		}

		$where_formats = $where_format = (array) $where_format;
		foreach ( (array) array_keys($where) as $field ) {
			if ( !empty($where_format) )
				$form = ( $form = array_shift($where_formats) ) ? $form : $where_format[0];
			else
				$form = '%s';
			$wheres[] = "`$field` = {$form}";
		}

		$sql = "UPDATE `$table` SET " . implode( ', ', $bits ) . ' WHERE ' . implode( ' AND ', $wheres );
		return $this->query( $this->prepare( $sql, array_merge(array_values($data), array_values($where))) );
	}

	/**
	 * Retrieve one variable from the database.
	 *
	 * Executes a SQL query and returns the value from the SQL result.
	 * If the SQL result contains more than one column and/or more than one row, this function returns the value in the column and row specified.
	 * If $query is null, this function returns the value in the specified column and row from the previous SQL result.
	 *
	 * @param string|null $query SQL query.  If null, use the result from the previous query.
	 * @param int $x (optional) Column of value to return.  Indexed from 0.
	 * @param int $y (optional) Row of value to return.  Indexed from 0.
	 * @return string Database query result
	 */
	function get_var($query=null, $x = 0, $y = 0) {
		$this->func_call = "\$db->get_var(\"$query\",$x,$y)";
		if ( $query )
			$this->query($query);

		// Extract var out of cached results based x,y vals
		if ( !empty( $this->last_result[$y] ) ) {
			$values = array_values(get_object_vars($this->last_result[$y]));
		}

		// If there is a value return it else return null
		return (isset($values[$x]) && $values[$x]!=='') ? $values[$x] : null;
	}

	/**
	 * Retrieve one row from the database.
	 *
	 * Executes a SQL query and returns the row from the SQL result.
	 *
	 * @param string|null $query SQL query.
	 * @param string $output (optional) one of ARRAY_A | ARRAY_N | OBJECT constants.  Return an associative array (column => value, ...), a numerically indexed array (0 => value, ...) or an object ( ->column = value ), respectively.
	 * @param int $y (optional) Row to return.  Indexed from 0.
	 * @return mixed Database query result in format specifed by $output
	 */
	function get_row($query = null, $output = OBJECT, $y = 0) {
		$this->func_call = "\$db->get_row(\"$query\",$output,$y)";
		if ( $query )
			$this->query($query);
		else
			return null;

		if ( !isset($this->last_result[$y]) )
			return null;

		if ( $output == OBJECT ) {
			return $this->last_result[$y] ? $this->last_result[$y] : null;
		} elseif ( $output == ARRAY_A ) {
			return $this->last_result[$y] ? get_object_vars($this->last_result[$y]) : null;
		} elseif ( $output == ARRAY_N ) {
			return $this->last_result[$y] ? array_values(get_object_vars($this->last_result[$y])) : null;
		} else {
			$this->print_error(" \$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N");
		}
	}

	/**
	 * Retrieve one column from the database.
	 *
	 * Executes a SQL query and returns the column from the SQL result.
	 * If the SQL result contains more than one column, this function returns the column specified.
	 * If $query is null, this function returns the specified column from the previous SQL result.
	 *
	 * @param string|null $query SQL query.  If null, use the result from the previous query.
	 * @param int $x Column to return.  Indexed from 0.
	 * @return array Database query result.  Array indexed from 0 by SQL result row number.
	 */
	function get_col($query = null , $x = 0) {
		if ( $query )
			$this->query($query);

		$new_array = array();
		// Extract the column values
		for ( $i=0; $i < count($this->last_result); $i++ ) {
			$new_array[$i] = $this->get_var(null, $x, $i);
		}
		return $new_array;
	}

	/**
	 * Retrieve an entire SQL result set from the database (i.e., many rows)
	 *
	 * Executes a SQL query and returns the entire SQL result.
	 *
	 * @param string $query SQL query.
	 * @param string $output (optional) ane of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
	 * With one of the first three, return an array of rows indexed from 0 by SQL result row number.
	 * Each row is an associative array (column => value, ...),
	 * a numerically indexed array (0 => value, ...), or an object. ( ->column = value ), respectively.  With OBJECT_K,
	 * return an associative array of row objects keyed by the value of each row's first column's value.
	 * Duplicate keys are discarded.
	 * 
	 * @return mixed Database query results
	 */
	function get_results($query = null, $output = OBJECT) {
		$this->func_call = "\$db->get_results(\"$query\", $output)";

		if ( $query )
			$this->query($query);
		else
			return null;

		if ( $output == OBJECT ) {
			// Return an integer-keyed array of row objects
			return $this->last_result;
		} elseif ( $output == OBJECT_K ) {
			// Return an array of row objects with keys from column 1
			// (Duplicates are discarded)
            $new_array = array();
			foreach ( $this->last_result as $row ) {
				$key = array_shift( get_object_vars( $row ) );
				if ( !isset( $new_array[ $key ] ) )
					$new_array[ $key ] = $row;
			}
			return $new_array;
		} elseif ( $output == ARRAY_A || $output == ARRAY_N ) {
			// Return an integer-keyed array of...
			if ( $this->last_result ) {
				$i = 0;
				foreach( (array) $this->last_result as $row ) {
					if ( $output == ARRAY_N ) {
						// ...integer-keyed row arrays
						$new_array[$i] = array_values( get_object_vars( $row ) );
					} else {
						// ...column name-keyed row arrays
						$new_array[$i] = get_object_vars( $row );
					}
					++$i;
				}
				return $new_array;
			}
		}
	}

	/**
	 * Retrieve column metadata from the last query.
	 *
	 * @param string $info_type one of name, table, def, max_length, not_null, primary_key, multiple_key, unique_key, numeric, blob, type, unsigned, zerofill
	 * @param int $col_offset 0: col name. 1: which table the col's in. 2: col's max length. 3: if the col is numeric. 4: col's type
	 * @return mixed Column Results
	 */
	function get_col_info($info_type = 'name', $col_offset = -1) {
		if ( $this->col_info ) {
			if ( $col_offset == -1 ) {
				$i = 0;
				foreach( (array) $this->col_info as $col ) {
					$new_array[$i] = $col->{$info_type};
					$i++;
				}
				return $new_array;
			} else {
				return $this->col_info[$col_offset]->{$info_type};
			}
		}
	}

    /**
	 * Stores a query result
	 *
	 * @param string $query SQL query.
	 * @param boolean if is an insert.
	 *
	 * @return mixed Database query results
	 */
    function store_cache($query, $is_insert) {

        // The would be cache file for this query
        $cache_file = $this->cache_dir . '/' . md5($query);

        // disk caching of queries
        if ($this->use_disk_cache && ( $this->cache_queries && !$is_insert ) || ( $this->cache_inserts && $is_insert )) {
            if (!is_dir($this->cache_dir)) {
                $this->show_errors ? trigger_error("Could not open cache dir: $this->cache_dir", E_USER_WARNING) : null;
            }
            else {
                // Cache all result values
                $result_cache = array
                    (
                    'col_info' => $this->col_info,
                    'last_result' => $this->last_result,
                    'num_rows' => $this->num_rows,
                    'return_value' => $this->num_rows,
                );
                error_log(serialize($result_cache), 3, $cache_file);
            }
        }
    }

    /**
	 * Gets the cached results
	 *
	 * @param string $query SQL query.
	 *
	 * @return mixed Database query results
	 */
    function get_cache($query) {

        // The would be cache file for this query
        $cache_file = $this->cache_dir . '/' . md5($query);

        // Try to get previously cached version
        if ($this->use_disk_cache && file_exists($cache_file)) {
            // Only use this cache file if less than 'cache_timeout' (hours)
            if ((time() - filemtime($cache_file)) > ($this->cache_timeout * 3600)) {
                unlink($cache_file);
            }
            else {
                $result_cache = unserialize(file_get_contents($cache_file));

                $this->col_info = $result_cache['col_info'];
                $this->last_result = $result_cache['last_result'];
                $this->num_rows = $result_cache['num_rows'];

                $this->from_disk_cache = true;

                return $result_cache['return_value'];
            }
        }
    }

	/**
	 * Starts the timer, for debugging purposes.
	 *
	 * @return true
	 */
	function timer_start() {
		$mtime = microtime();
		$mtime = explode(' ', $mtime);
		$this->time_start = $mtime[1] + $mtime[0];
		return true;
	}

	/**
	 * Stops the debugging timer.
	 *
	 * @return int Total time spent on the query, in milliseconds
	 */
	function timer_stop() {
		$mtime = microtime();
		$mtime = explode(' ', $mtime);
		$time_end = $mtime[1] + $mtime[0];
		$time_total = $time_end - $this->time_start;
		return $time_total;
	}

	/**
	 * Wraps errors in a nice header and footer and dies.
	 *
	 * Will not die if idb::$show_errors is true
	 *
	 * @param string $message The Error message
	 * @param string $error_code (optional) A Computer readable string to identify the error.
	 * @return false|void
	 */
	function bail($message, $error_code = '500') {
		if ( !$this->show_errors ) {
			$this->error = $message;
			return false;
		}
		exit($message);
	}

	/**
	 * Whether or not MySQL database is at least the required minimum version.
	 *
	 * @return Error
	 */
	function check_database_version()
	{
		// Make sure the server has MySQL 4.1.2
		if ( version_compare($this->db_version(), '4.1.2', '<') )
			return trigger_error("database_version: <strong>ERROR</strong>: App %s requires MySQL 4.1.2 or higher ", E_USER_ERROR);
	}

	/**
	 * Whether of not the database supports collation.
	 *
	 * Called when generating the table scheme.
	 *
	 * @return bool True if collation is supported, false if version does not
	 */
	function supports_collation() {
		return $this->has_cap( 'collation' );
	}

	/**
	 * Generic function to determine if a database supports a particular feature
	 * @param string $db_cap the feature
	 * @param false|string|resource $dbh_or_table (not implemented) Which database to test.  False = the currently selected database, string = the database containing the specified table, resource = the database corresponding to the specified mysql resource.
	 * @return bool
	 */
	function has_cap( $db_cap ) {
		$version = $this->db_version();

		switch ( strtolower( $db_cap ) ) :
		case 'collation' :    
		case 'group_concat' : 
		case 'subqueries' :   
			return version_compare($version, '4.1', '>=');
			break;
		endswitch;

		return false;
	}

	/**
	 * Retrieve the name of the function that called idb.
	 *
	 * Requires PHP 4.3 and searches up the list of functions until it reaches
	 * the one that would most logically had called this method.
	 *
	 * @return string The name of the calling function
	 */
	function get_caller() {
		// requires PHP 4.3+
		if ( !is_callable('debug_backtrace') )
			return '';

		$bt = debug_backtrace();
		$caller = array();

		$bt = array_reverse( $bt );
		foreach ( (array) $bt as $call ) {
			if ( @$call['class'] == __CLASS__ )
				continue;
			$function = $call['function'];
			if ( isset( $call['class'] ) )
				$function = $call['class'] . "->$function";
			$caller[] = $function;
		}
		$caller = join( ', ', $caller );

		return $caller;
	}

	/**
	 * The database version number
	 * @param false|string|resource $dbh_or_table (not implemented) Which database to test.  False = the currently selected database, string = the database containing the specified table, resource = the database corresponding to the specified mysql resource.
	 * @return false|string false on failure, version number on success
	 */
	function db_version() {
		return preg_replace('/[^0-9.].*/', '', mysql_get_server_info( $this->dbh ));
	}
}

if ( ! isset($idb) ) {
	/**
	 * Database Object, if it isn't set already in db.php
	 * @global object $idb Creates a new idb object based on config.php Constants for the database
	 */
	//$idb = new idb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
}
?>
