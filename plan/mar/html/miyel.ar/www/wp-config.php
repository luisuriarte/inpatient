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
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'www_miyel' );

/** Database username */
define( 'DB_USER', 'www_miyel' );

/** Database password */
define( 'DB_PASSWORD', 'S4nC4rl0sC3ntr0' );

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
define( 'AUTH_KEY',         'Uag-7QsZ5<P3[mOq2H&W4}(DyXP<V2N+q-x[vAOTu*5T9PqoRo-F6!yFD)D[=-I&' );
define( 'SECURE_AUTH_KEY',  ',D^4;N=;=}7zbM?|Yj<FIa93A[D9uIb{vy>Pb-2,u](U[ hI8KVX1:|2+g.~83WX' );
define( 'LOGGED_IN_KEY',    '&X;]vdkCZ4y4u^[X@RxEhwT6aXhT^DU xE83HE%^G5s7.QUgShdgY8~Ccn`AcI@M' );
define( 'NONCE_KEY',        '>+n<k:Bje*{[e7c319Kw=r?~p|x*;R`HhHTs8jD)s7p%cEmw9?9!:LCJ/G2W%5pb' );
define( 'AUTH_SALT',        'tA5IC)wW`0Zt(=oA:n@SmOjO8n:.9|AB4NDip(q~k}CPcwpd.eD`_}{jQ5~S}j[G' );
define( 'SECURE_AUTH_SALT', 'Ovw[XO$rk8) >O]qU2^Gye<gTbkyjuHw78|32?d.Yd}P_b1!D-ibCnj^{6o&Vi#g' );
define( 'LOGGED_IN_SALT',   '4hu%,vK3B5eN}*^bF]a=m&wALNL@Z8L8wF`dTV*.;C/C?$0ReY6.foR Nc($RuB>' );
define( 'NONCE_SALT',       'p[l]s<+CgW#h2@?KWa[=% 5q:Km{DcD?YpdC_Nu/Lp$zk$={6_mf+Wl+6ssG~_<I' );

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
