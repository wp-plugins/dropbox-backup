<?php
/*
Plugin Name: Dropbox Backup & Restore
Description: Dropbox Backup & Restore Plugin to create Dropbox Full Backup (Files + Database) of your Web Page
Version: 1.2.9.5
*/

if (!defined('DRBBACKUP_BASE_DIR')) {
    define('DRBBACKUP_BASE_DIR', dirname(__FILE__));
}

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '/modules/constant.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '/functions/wpadm.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '/main/wpadm-class-wp.php';

add_action('init', 'wpadm_full_backup_dropbox_run');

add_action('admin_print_scripts', array('wpadm_wp_full_backup_dropbox', 'include_admins_script' ));
add_action('admin_menu', array('wpadm_wp_full_backup_dropbox', 'draw_menu'));
add_action('admin_post_activate_wpadm_full_backup_dropbox', array('wpadm_wp_full_backup_dropbox', 'activatePlugin') );

if ( !get_option('wpadm_pub_key')/* && (is_admin())*/) {
    add_action('admin_notices', 'wpadm_admin_notice');
}

if (!function_exists('wpadm_full_backup_dropbox_run')) {
    function wpadm_full_backup_dropbox_run()
    {
        wpadm_run('dropbox-backup', dirname(__FILE__));
    }
}

