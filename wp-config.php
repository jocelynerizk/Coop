<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'coop' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

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
define( 'AUTH_KEY',         '.{{mPn6Yv@qT,msZ>=:9q`|z.-8Zd@y_$.qtQEAW}qx>x-HC|yq^(T;i,ek7cY:,' );
define( 'SECURE_AUTH_KEY',  'z_%y@hkMdq$^+:ENOr1@=s>$SPr_r`Y%B.D~^5BYL<]Qb//)uAO*R;vwX0,5y]w;' );
define( 'LOGGED_IN_KEY',    'Ai%PK,11A-MpL-0%],H*yJ*%fIe=g*ZD>#b-V`+Cgo`}z^?M7qR.AM=0rMR6|(<-' );
define( 'NONCE_KEY',        '-{ETS1K#}B=Qp:;R.:jC9x0.ue8mk[v(J&wT#<>M%l{(Fo*?ZZ//c3--z}_c&C@4' );
define( 'AUTH_SALT',        '-;S5VaC$ZS96 (2LXc|{O`X}OWpk)5^T5/8)4jtZdGeaf64#y|e)tNXXZ{ 3uPDg' );
define( 'SECURE_AUTH_SALT', 'oZo7JTEYrO}uFep79x]O:jVP9NScuE)]VXw!be ;.pq~B@}UgU;&v{/h NQ!h=;&' );
define( 'LOGGED_IN_SALT',   'AZP)9g6C}#|a>4au~Hw#}mp9yIHJ|Xo/R1?f(?FM,?`9`C$_X=>L]f<u&{|O~=>N' );
define( 'NONCE_SALT',       '+ZbqfO-HXR-B~1+h3|{  bsk#KmCB^.fMm*}kD1I:F,{b$:$CS{Oe7)e&rpH9a5Y' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
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
