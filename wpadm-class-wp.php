<?php 
    if (! defined("WPADM_URL_BASE")) {
        define("WPADM_URL_BASE", 'http://secure.wpadm.com/');
    }

    if(session_id() == '') {
        session_start();
    }

    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "libs/error.class.php";
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "libs/wpadm.server.main.class.php";
    if (! class_exists("wpadm_wp_full_backup_dropbox") ) {

        add_action('wp_ajax_wpadm_local_restore', array('wpadm_wp_full_backup_dropbox', 'restore_backup') );
        add_action('wp_ajax_wpadm_logs', array('wpadm_wp_full_backup_dropbox', 'getLog') );
        add_action('wp_ajax_wpadm_local_backup', array('wpadm_wp_full_backup_dropbox', 'local_backup') );
        
        add_action('admin_post_wpadm_delete_backup', array('wpadm_wp_full_backup_dropbox', 'delete_backup') );
        add_action('admin_post_wpadm_download', array('wpadm_wp_full_backup_dropbox', 'download') );
        
        class wpadm_wp_full_backup_dropbox extends wpadm_class  {

            const MIN_PASSWORD = 6;

            public static function local_backup()
            {
                require_once dirname(__FILE__) . "/class-wpadm-core.php";
                @session_write_close();
                parent::$type = 'full'; 
                if (file_exists(WPAdm_Core::getTmpDir() . "/logs2")) {
                    unlink(WPAdm_Core::getTmpDir() . "/logs2");
                }
                $backup = new WPAdm_Core(array('method' => "local_backup", 'params' => array('optimize' => 1, 'limit' => 0, 'types' => array('db', 'files') )), 'full_backup_dropbox', dirname(__FILE__));
                $res = $backup->getResult()->toArray();
                $res['md5_data'] = md5( print_r($res, 1) );
                $res['name'] = $backup->name;
                $res['time'] = $backup->time;
                $res['type'] = 'local';
                $res['counts'] = count($res['data']);

                @session_start();
                echo json_encode($res);
                wp_die();

            }
            public static function getLog()
            {   
                @session_write_close();
                @session_start();
                require_once dirname(__FILE__) . "/class-wpadm-core.php";
                $backup = new WPAdm_Core(array('method' => "local"), 'full_backup_dropbox', dirname(__FILE__));
                $log = WPAdm_Core::getLog();
                $log2 = WPAdm_Core::getTmpDir() . "/logs2";
                if (file_exists($log2)) {
                    $text = file_get_contents($log2);
                    $log = str_replace($text, "", $log);
                    file_put_contents($log2, $log); 
                } else {
                    file_put_contents($log2, $log);
                }
                $log = explode("\n", $log);
                krsort($log);
                echo json_encode(array('log' => $log));

                exit;
            }
            public static function restore_backup()
            {
                require_once dirname(__FILE__) . "/class-wpadm-core.php";
                @session_write_close();
                parent::$type = 'full'; 
                if (file_exists(WPAdm_Core::getTmpDir() . "/logs2")) {
                    unlink(WPAdm_Core::getTmpDir() . "/logs2");
                }
                $name_backup = isset($_POST['name']) ? trim($_POST['name']) : "";
                $backup = new WPAdm_Core(array('method' => "local_restore", 'params' => array('types' => array('files', 'db'), 'name_backup' => $name_backup )), 'full_backup_dropbox', dirname(__FILE__));
                $res = $backup->getResult()->toArray();
                @session_start();
                echo json_encode($res);
                wp_die();
            }
            public static function download()
            {
                if (isset($_REQUEST['backup'])) {
                    require_once dirname(__FILE__) . "/class-wpadm-core.php"; 
                    require_once dirname(__FILE__) . '/modules/pclzip.lib.php';
                    $backup = new WPAdm_Core(array('method' => "local"), 'full_backup_dropbox', dirname(__FILE__));
                    $filename = $_REQUEST['backup'] . ".zip";
                    $file = WPAdm_Core::getTmpDir() . "/" . $filename;
                    if (file_exists($file)) {
                        unlink($file);
                    }
                    $archive = new PclZip($file);
                    $dir_backup = ABSPATH . 'wpadm_backups/' . $_REQUEST['backup'];

                    $backups = array('data' => array(), 'md5' => '');
                    if (is_dir($dir_backup)) { 
                        $i = 0;
                        $dir_open = opendir($dir_backup);
                        while($d = readdir($dir_open)) {
                            if ($d != '.' && $d != '..' && file_exists($dir_backup . "/$d") && substr($d, -3) != "php") {
                                $archive->add($dir_backup . "/$d", PCLZIP_OPT_REMOVE_PATH, ABSPATH . 'wpadm_backups');
                            }
                        }
                    }


                    $now = gmdate("D, d M Y H:i:s");
                    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
                    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
                    header("Last-Modified: {$now} GMT");

                    // force download  
                    header("Content-Type: application/force-download");
                    header("Content-Type: application/octet-stream");
                    header("Content-Type: application/download");

                    // disposition / encoding on response body
                    header("Content-Disposition: attachment;filename={$filename}");
                    header("Content-Transfer-Encoding: binary");

                    ob_start();
                    $df = fopen("php://output", 'w');
                    echo file_get_contents($file);
                    fclose($df);
                    echo ob_get_clean();
                    unlink($file);
                    exit;
                }
            }

            public static function delete_backup()
            {
                if (isset($_POST['backup-type']) && $_POST['backup-type'] == 'local') {
                    require_once dirname(__FILE__) . "/class-wpadm-core.php";
                    $dir = ABSPATH . 'wpadm_backups/' . $_POST['backup-name'] ;
                    if (is_dir($dir)) {
                        WPAdm_Core::rmdir($dir);
                    }
                }
                header("Location: " . admin_url("admin.php?page=wpadm_wp_full_backup_dropbox"));
            }

            public static function activatePlugin()
            {
                if (isset($_POST['email']) && isset($_POST['password']) && isset($_POST['password-confirm'])) {
                    $email = trim(stripslashes(strip_tags($_POST['email'])));
                    $password = trim(strip_tags($_POST['password']));
                    $password_confirm = trim(strip_tags($_POST['password-confirm'])); 
                    $sent = true;
                    if (empty($email)) { 
                        parent::setError("Error, Email is empty.");
                        $sent = false;
                    }
                    if (!preg_match("/^([a-z0-9_\-]+\.)*[a-z0-9_\-]+@([a-z0-9][a-z0-9\-]*[a-z0-9]\.)+[a-z]{2,4}$/i", $email)) {
                        parent::setError("Error, Incorrect Email");
                        $sent = false;
                    }
                    if (empty($password)) {
                        parent::setError("Error, Password is empty.");
                        $sent = false;
                    }
                    if (strlen($password) < self::MIN_PASSWORD) {
                        parent::setError("Error, the minimum number of characters for the password \"" . self::MIN_PASSWORD . "\".");
                        $sent = false;
                    }

                    if ($password != $password_confirm) {
                        parent::setError("Error, passwords do not match");
                        $sent = false;
                    }
                    if ($sent) {
                        $info = self::getPluginName();
                        $data = parent::sendToServer(
                        array(
                        'actApi' => "activate",
                        'email' => $email,
                        'password' => $password,
                        'url' => get_option("siteurl"),
                        'plugin' => $info,
                        )
                        );
                        parent::setResponse($data);
                    }
                }
                header("Location: " . admin_url("admin.php?page=wpadm_wp_full_backup_dropbox"));
            }

            protected static function getPluginName()
            {

                preg_match("|wpadm_wp_(.*)|", __CLASS__, $m);
                return $m[1];
            }
            protected static function getPathPlugin()
            {
                return "wpadm_full_backup_dropbox";
            }


            public static function wpadm_show_backup()
            {

                $show = !get_option('wpadm_pub_key') && is_super_admin();
                if (!$show) {
                    $data = parent::sendToServer(
                    array(
                    'actApi' => 'backupsCache',
                    'public_key' => get_option('wpadm_pub_key'),
                    'storage' => 'dropbox',
                    'type' => 'full',
                    )
                    );
                }
                parent::$type = 'full';
                $data_local = parent::read_backups();
                if (isset($data['data'])) {
                    $data['data'] = array_merge($data_local['data'], $data['data']);
                } else {
                    $data = $data_local;
                }

                $error = parent::getError(true);
                $msg = parent::getMessage(true); 
                ob_start();
                require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "template/wpadm_show_backup.php";
                echo ob_get_clean();
            }


            public static function draw_menu()
            {
                $menu_position = '1.9998887771'; 
                if(self::checkInstallWpadmPlugins()) {
                    $page = add_menu_page(
                    'WPAdm', 
                    'WPAdm', 
                    "read", 
                    'wpadm_plugins', 
                    'wpadm_plugins',
                    plugins_url('/wpadm-logo.png', __FILE__),
                    $menu_position     
                    );
                    add_submenu_page(
                    'wpadm_plugins', 
                    "Dropbox Full Backup",
                    "Dropbox Full Backup",
                    'read',
                    'wpadm_wp_full_backup_dropbox',
                    array('wpadm_wp_full_backup_dropbox', 'wpadm_show_backup')
                    );
                } else {
                    $page = add_menu_page(
                    'Dropbox Full Backup', 
                    'Dropbox Full Backup', 
                    "read", 
                    'wpadm_wp_full_backup_dropbox', 
                    array('wpadm_wp_full_backup_dropbox', 'wpadm_show_backup'),
                    plugins_url('/wpadm-logo.png', __FILE__),
                    $menu_position     
                    );

                    add_submenu_page(
                    'wpadm_wp_full_backup_dropbox', 
                    "WPAdm",
                    "WPAdm",
                    'read',
                    'wpadm_plugins',
                    'wpadm_plugins'
                    );
                }

            }
        }
    }

?>
