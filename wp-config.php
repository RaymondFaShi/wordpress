<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wpshop' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '123456' );

/** Database hostname */
define( 'DB_HOST', '192.168.88.2' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ']$DsCf/^NHcwX!Wau%&KG5<OjjA{u]RD>nujHddi~31~+D9lKEWFK)/gOs]{?QrI' );
define( 'SECURE_AUTH_KEY',  ' `TXCCuY]20qXhr&:{?UJ/,A*6Q)=UnHc?3(.D3SSy1b^4khpLHq*[.s2Zth!MCW' );
define( 'LOGGED_IN_KEY',    'iQRy&-[n<zZr*^A5^N!S4PCv*1H6IWfUm.aF@o>aXIs&y-<%U/bC;Ta`?)exmg#]' );
define( 'NONCE_KEY',        '@sc6NKZGA&G5J)c*?cn%IxIF*?bw[Kn]S@;BMbSj0x2eSpPT{@,XlEC6dTP-d(p]' );
define( 'AUTH_SALT',        '9,>]0sC$h6,X-{Rw7niezp4Uj8xs5JfV26o;]W7H2aoQv$8wq!2H-a,7y(y|J)TF' );
define( 'SECURE_AUTH_SALT', ';)7j4 [5Y]#$]rmXiu$(EME[.1fyU2_-vi8fNmQqj$KB$ss/FGivTg;u pwi&<d[' );
define( 'LOGGED_IN_SALT',   '7|w+&T]Et5o08Z_8dKq-v4vaw]+#W+a4z v?q0Ssh4JzJ/3+)i;X/M&{FF$%Dd$T' );
define( 'NONCE_SALT',       '9km^p#;bE{X6!bzxY`rP@,)d|:aHqVK)KRj}5_IKQ+HaSn]:j Xm0]iRl6uBo-X}' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
