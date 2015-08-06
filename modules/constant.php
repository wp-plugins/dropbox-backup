<?php
if( !defined('WPADM_DIR_NAME') ) {
    define('WPADM_DIR_NAME', 'wpadm_backups');
}

if (!defined('WPADM_DIR_BACKUP')) {
    define('WPADM_DIR_BACKUP',  WP_CONTENT_DIR . '/' . WPADM_DIR_NAME);
}

if (! defined("WPADM_URL_BASE")) {
    define("WPADM_URL_BASE", 'http://secure.webpage-backup.com/');
}

if (! defined("WPADM_APP_KEY")) {
    define("WPADM_APP_KEY", 'nv751n84w2nif6j');
}

if (! defined("WPADM_APP_SECRET")) {
    define("WPADM_APP_SECRET", 'qllasd4tbnqh4oi');
}

if (!defined("SERVER_URL_INDEX")) {
    define("SERVER_URL_INDEX", "http://www.webpage-backup.com/");
}
if (!defined("PHP_VERSION_DEFAULT")) {
    define("PHP_VERSION_DEFAULT", '5.2.4' );
}
if (!defined("MYSQL_VERSION_DEFAULT")) {
    define("MYSQL_VERSION_DEFAULT", '5.0' );
}

if (!defined("PREFIX_BACKUP_")) { 
    define("PREFIX_BACKUP_", "wpadm_backup_"); 
}   

if (!defined("SITE_HOME")) {
    define("SITE_HOME", str_ireplace( array( 'http://', 'https://' ), '', home_url() ) );
}
