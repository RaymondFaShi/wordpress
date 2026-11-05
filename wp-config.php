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
define( 'AUTH_KEY',         'rI,R,*um&kL?ZJEND}aSEZ67m5.7>?!WY6E*@i]g[QcDY$LI){]eIMHkar<QXaa.' );
define( 'SECURE_AUTH_KEY',  '|ri*C (!?wQ&(yDY:F6?Z@@G}MDC>c,)mb)RgWT~Z&gEl!$pZM0SMBa@/d9^1l*|' );
define( 'LOGGED_IN_KEY',    'Ir&wFqfCqt:+9~?68@@deAg$Sigd|VRLfx.P.Nmb]/.|VQqDF}kw$>]Jm@I=s.=5' );
define( 'NONCE_KEY',        'EEQDY$$f;|j{.?*/`/yC@u9RY@[eh}Wj(j6m .i`[3=w%?vVw3emkktT6~7gNS&E' );
define( 'AUTH_SALT',        '{77C@Exa|eKcy5)Gs3&E*K3 O9(&Ca7>^`{g`PLa5,<!w-JMx0%F WJ|>%jB+r2O' );
define( 'SECURE_AUTH_SALT', '+.B.U,FEat>ThSK!d$9ix?3tK7^es&Vz,a.kX^Gy;rtPlB*lsLl-@E_^{VMi@4P2' );
define( 'LOGGED_IN_SALT',   '~y-J^H% pC`&1hgZCs3N#I^bn`/n9+_&UvfTK1(XCqOcd=QT58~:bicd?_4#W*hc' );
define( 'NONCE_SALT',       'Rf113;hf9rx.0o4MYK&[5NV:nkmd^qstIJ,5QV/vv`i+6T~?r*]|[N4uM8c%iN%F' );

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
