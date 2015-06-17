<?php
/*
Plugin Name: DropBox Backup
Description: DropBox Backup Plugin to create DropBox Full Backup (Files + Database) of your Web Page
Version: 1.1
*/

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wpadm.php';
if (file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wpadm-class-wp.php')) {
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wpadm-class-wp.php';
}

add_action('init', 'wpadm_full_backup_dropbox_run');

add_action('admin_print_scripts', 'wpadm_include_admins_script' );
// add item to menu

add_action('admin_notices', 'wpadm_admin_notice');


if (!function_exists('wpadm_full_backup_dropbox_run')) {
    function wpadm_full_backup_dropbox_run()
    {
        wpadm_run('dropbox-backup', dirname(__FILE__));
    }
}

