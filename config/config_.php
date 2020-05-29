<?php
// ** MySQL settings - You can get this info from your web host ** //
switch($_SERVER['SERVER_NAME']) {
	case "localhost":
		define('DB_NAME', '');

		/** MySQL database username */
		define('DB_USER', 'root');

		/** MySQL database password */
		define('DB_PASSWORD', '');

		/** MySQL hostname */
		define('DB_HOST', 'localhost');

		/** Database Charset to use in creating database tables. */
		define('DB_CHARSET', 'utf8');

		/** The Database Collate type. Don't change this if in doubt. */
		define('DB_COLLATE', 'utf8_unicode_ci');
		break;

    default:
		define('DB_NAME', '');

		/** MySQL database username */
		define('DB_USER', '');

		/** MySQL database password */
		define('DB_PASSWORD', '');

		/** MySQL hostname */
		define('DB_HOST', 'localhost');

		/** Database Charset to use in creating database tables. */
		define('DB_CHARSET', 'utf8');

		/** The Database Collate type. Don't change this if in doubt. */
		define('DB_COLLATE', 'utf8_unicode_ci');

        /*set the cache*/
        //enable disk cache of queries
        define('DB_DISK_CACHE', true);

        // Specify a cache dir.
		define('DB_CACHE_DIR', '../db_cache');

        // By wrapping up queries you can ensure that the default
        // is NOT to cache unless specified
		define('DB_CACHE_QUERIES', true);

        // Cache expiry
		define('DB_CACHE_TIMEOUT', 24);
		break;
}

/**
 * Database Table prefix.
 *
 */
define('DB_PREFIX', '');
?>