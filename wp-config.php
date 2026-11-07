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


$host = $_SERVER[ 'HTTP_HOST' ] ?? 'jpstore01.com';

// 限制允许访问的域名，避免 Host Header 注入
$allowedHosts = [
    'jpstore01.com',
    'www.jpstore01.com',
    '7s7.cc',
    'www.7s7.cc',
    '127.0.0.1:8080',
];

if ( !in_array( $host, $allowedHosts, true ) ) {
    $host = 'jpstore01.com';
}

$scheme = (
    ( !empty($_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] !== 'off' )
    || ( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] ?? '' ) === 'https'
) ? 'https' : 'http';

define( 'WP_HOME', $scheme . '://' . $host );
define( 'WP_SITEURL', $scheme . '://' . $host );


// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'jpstore01' );

/** Database username */
define( 'DB_USER', 'jpstore01' );

/** Database password */
define( 'DB_PASSWORD', 'CwedSir1fw42SBFm' );

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
define( 'AUTH_KEY',         '!fr3eQdPdFWQ%BH2eFfF<&BZ[kq]=.ChhE^%m~{xuDi(|X*v(1-CE`ic=0KALTo;' );
define( 'SECURE_AUTH_KEY',  '$%r6met*pc!yR<*`%M$wF[NL{;g):QkB%:@Ab6yW(/Id##:&dW#E#acpj[cU.84=' );
define( 'LOGGED_IN_KEY',    'ZV^1.ztXHDR0r&rZ<d6?$?/l!i0%WjQ%2{FBN3~|l[aCUc-cY_NJ]~O=UdqWpq0n' );
define( 'NONCE_KEY',        'r={tP@V$<FGV}PW4.K.!-IVD3`A?u?a|/4Qo|BPVeD raRbL0R4dHxA)AuLtt,e]' );
define( 'AUTH_SALT',        'd#aNzXvd/hrhi(72g>%ruHc4{=2pbP.#p:+L~>3b&[)O}aoA3oi4Yv#!5f,d-y%t' );
define( 'SECURE_AUTH_SALT', '1L:lL1p^:0&;T67L^KB)&UOEuBI8qBIY0r_6%9#nchd#94Nxg9_E%Trb##GLHypM' );
define( 'LOGGED_IN_SALT',   'y|`h!6q>8(j;]~W*v?I`3+b$Q`C/>A/Cl;_)M6OZC^SQ:hSp5~5$OCFRo)i/ymCG' );
define( 'NONCE_SALT',       '>s8Cq3J43^3XC}wtBcIg; OhAX[0SzbcKt|W(u&=ed;oXg{UG)q$*1q(2TXF6hri' );

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
