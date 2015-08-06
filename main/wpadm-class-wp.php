<?php 
    if(session_id() == '') {
        session_start();
    }
     
    require_once DRBBACKUP_BASE_DIR . "/libs/error.class.php";
    require_once DRBBACKUP_BASE_DIR . "/libs/wpadm.server.main.class.php";
    if (! class_exists("wpadm_wp_full_backup_dropbox") ) {

        add_action('wp_ajax_wpadm_local_restore', array('wpadm_wp_full_backup_dropbox', 'restore_backup') );
        add_action('wp_ajax_wpadm_restore_dropbox', array('wpadm_wp_full_backup_dropbox', 'wpadm_restore_dropbox') );
        add_action('wp_ajax_wpadm_logs', array('wpadm_wp_full_backup_dropbox', 'getLog') );
        add_action('wp_ajax_wpadm_local_backup', array('wpadm_wp_full_backup_dropbox', 'local_backup') );
        add_action('wp_ajax_wpadm_dropbox_create', array('wpadm_wp_full_backup_dropbox', 'dropbox_backup_create') );
        add_action('wp_ajax_set_user_mail', array('wpadm_wp_full_backup_dropbox', 'setUserMail') );
        add_action('wp_ajax_saveSetting', array('wpadm_wp_full_backup_dropbox', 'saveSetting') );


        add_action('admin_post_wpadm_delete_backup', array('wpadm_wp_full_backup_dropbox', 'delete_backup') );
        add_action('admin_post_dropboxConnect', array('wpadm_wp_full_backup_dropbox', 'dropboxConnect') );

        add_action('admin_post_wpadm_download', array('wpadm_wp_full_backup_dropbox', 'download') );
        add_action('init', array('wpadm_wp_full_backup_dropbox', 'init'), 10 );

        @set_time_limit(0);
        
        class wpadm_wp_full_backup_dropbox extends wpadm_class  {

            const MIN_PASSWORD = 6;
            public static function init()
            {
                parent::$plugin_name = 'dropbox-backup';
                require_once  DRBBACKUP_BASE_DIR . '/modules/class-wpadm-core.php';
                WPAdm_Core::$pl_dir = DRBBACKUP_BASE_DIR ;
            }

            static function include_admins_script()
            {
                wp_enqueue_style('css-admin-wpadm', plugins_url( "/template/css/admin-style-wpadm.css", dirname( __FILE__ )) );
                wp_enqueue_script( 'js-admin-wpadm', plugins_url( "/template/js/admin-wpadm.js",  dirname( __FILE__ ) ) );
                wp_enqueue_script( 'postbox' );
            }

            public static function setUserMail()
            {
                if (isset($_POST['email'])) {
                    $email = trim($_POST['email']);
                    $mail = get_option(PREFIX_BACKUP_ . "email");
                    if ($mail) {
                        add_option(PREFIX_BACKUP_ . "email", $email);
                    } else {
                        update_option(PREFIX_BACKUP_ . "email",$email);
                    }
                } 
                echo 'true';
                wp_die();
            }
            public static function saveSetting()
            {
                if (isset($_POST['is_admin'])) {
                    $dropbox_options = get_option(PREFIX_BACKUP_ . 'dropbox-setting');
                    if ($dropbox_options) {
                        $dropbox_options = unserialize( base64_decode( $dropbox_options ) );
                    }
                    $dropbox_options['is_admin'] = (int) $_POST['is_admin'];
                    update_option(PREFIX_BACKUP_ . 'dropbox-setting', base64_encode( serialize( $dropbox_options ) ) );
                }
            }

            public static function local_backup()
            {
                require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-core.php";
                @session_write_close();
                parent::$type = 'full'; 
                if (file_exists(WPAdm_Core::getTmpDir() . "/logs2")) {
                    unlink(WPAdm_Core::getTmpDir() . "/logs2");
                }
                if ( WPAdm_Core::dir_writeble(WPADM_DIR_BACKUP)) {
                    $backup = new WPAdm_Core(array('method' => "local_backup", 'params' => array('optimize' => 1, 'limit' => 0, 'time' => @$_POST['time'], 'types' => array('db', 'files') )), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                    $res = $backup->getResult()->toArray();
                    $res['md5_data'] = md5( print_r($res, 1) );
                    $res['name'] = $backup->name;
                    $res['time'] = $backup->time;
                    $res['type'] = 'local';
                    $res['counts'] = count($res['data']);
                } else {
                    $res['result'] = 'error';
                    $res['error'] = langWPADM::get('Website "%d" returned an error during file creation: Failed to create file, please check the permissions on the folder "%dir".', false, array('%d', '%dir'), array(SITE_HOME, WPADM_DIR_BACKUP) );
                    $res['data'] = array();
                    $res['size'] = 0;
                }

                @session_start();
                echo json_encode($res);
                wp_die();

            }
            public static function getLog()
            {   
                @session_write_close();
                @session_start();
                require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-core.php";
                $backup = new WPAdm_Core(array('method' => "local"), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                $log = WPAdm_Core::getLog();
                $log2 = WPAdm_Core::getTmpDir() . "/logs2";
                if (file_exists($log2)) {
                    $text = @file_get_contents($log2);
                    file_put_contents($log2, $log); 
                    $log = str_replace($text, "", $log);
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
                require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-core.php";
                @session_write_close();
                parent::$type = 'full'; 
                if (file_exists(WPAdm_Core::getTmpDir() . "/logs2")) {
                    unlink(WPAdm_Core::getTmpDir() . "/logs2");
                }
                $name_backup = isset($_POST['name']) ? trim($_POST['name']) : "";
                $backup = new WPAdm_Core(array('method' => "local_restore", 'params' => array('types' => array('files', 'db'), 'name_backup' => $name_backup )), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                $res = $backup->getResult()->toArray();
                @session_start();
                echo json_encode($res);
                wp_die();
            }
            public static function wpadm_restore_dropbox()
            {
                require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-core.php";
                @session_write_close();
                $log_class = new WPAdm_Core(array('method' => "local"), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                if (file_exists(WPAdm_Core::getTmpDir() . "/logs2")) {
                    unlink(WPAdm_Core::getTmpDir() . "/logs2");
                }
                if (file_exists(WPAdm_Core::getTmpDir() . "/log.log")) {
                    unlink(WPAdm_Core::getTmpDir() . "/log.log");
                }
                WPAdm_Core::log( langWPADM::get('Start Restore from Dropbox cloud' , false) );
                $dropbox_options = get_option(PREFIX_BACKUP_ . 'dropbox-setting');
                if ($dropbox_options) {
                    require_once DRBBACKUP_BASE_DIR. "/modules/dropbox.class.php";
                    $dropbox_options = unserialize( base64_decode( $dropbox_options ) ); 
                    $folder_project = self::getNameProject();
                    $dropbox = new dropbox($dropbox_options['app_key'], $dropbox_options['app_secret'], $dropbox_options['auth_token_secret']);
                    if ($dropbox->isAuth()) {
                        $name_backup = isset($_POST['name']) ? trim($_POST['name']) : "";
                        $dir_backup = WPADM_DIR_BACKUP . "/$name_backup";
                        $error = WPAdm_Core::mkdir($dir_backup);
                        if (!empty($error)) {
                            WPAdm_Core::log($error);
                            $res['result'] = WPAdm_Result::WPADM_RESULT_ERROR;
                            $res['error'] = $error;
                            $res['data'] = array();
                            $res['size'] = 0;

                        } else {
                            $files = $dropbox->listing("$folder_project/$name_backup");
                            if (isset($files['items'])) {
                                $n = count($files['items']);
                                for($i = 0; $i < $n; $i++) {
                                    $res = $dropbox->downloadFile("$folder_project/$name_backup/{$files['items'][$i]['name']}", "$dir_backup/{$files['items'][$i]['name']}");
                                    if ($res != "$dir_backup/{$files['items'][$i]['name']}" && isset($res['text'])) {
                                        WPAdm_Core::log(langWPADM::get('Error: ' , false) . $res['text'] );
                                    } else {
                                        $log = str_replace('%s', $files['items'][$i]['name'], langWPADM::get('Download file (%s) with Dropbox' , false) );
                                        WPAdm_Core::log($log);
                                    }
                                }
                                parent::$type = 'full'; 
                                $backup = new WPAdm_Core(array('method' => "local_restore", 'params' => array('types' => array('files', 'db'), 'name_backup' => $name_backup )), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                                $res = $backup->getResult()->toArray();
                                WPAdm_Core::rmdir($dir_backup);
                            }
                        }
                    } else {
                        WPAdm_Core::log( langWPADM::get('Website "%d" can\'t authorize on Dropbox with using of "app key: %k" and "app secret: %s"' , false, 
                                                   array('%d', '%k', '%s'), 
                                                   array( SITE_HOME, $dropbox_options['app_key'], $dropbox_options['app_secret'] ) ) );
                    }
                } else {
                    WPAdm_Core::log( langWPADM::get('Website "%d" returned an error during connection to Dropbox: "app key" and "app secret" wasn\'t found. Please, check your Dropbox settings.' , false, '%d', SITE_HOME) );
                }
                @session_start();
                echo json_encode($res);
                wp_die();
            }
            public static function download()
            {
                if (isset($_REQUEST['backup'])) {
                    require_once DRBBACKUP_BASE_DIR . "/class-wpadm-core.php"; 
                    require_once DRBBACKUP_BASE_DIR . '/modules/pclzip.lib.php';
                    $backup = new WPAdm_Core(array('method' => "local"), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                    $filename = $_REQUEST['backup'] . ".zip";
                    $file = WPAdm_Core::getTmpDir() . "/" . $filename;
                    if (file_exists($file)) {
                        unlink($file);
                    }
                    $archive = new PclZip($file);
                    $dir_backup = WPADM_DIR_BACKUP . '/' . $_REQUEST['backup'];

                    $backups = array('data' => array(), 'md5' => '');
                    if (is_dir($dir_backup)) { 
                        $i = 0;
                        $dir_open = opendir($dir_backup);
                        while($d = readdir($dir_open)) {
                            if ($d != '.' && $d != '..' && file_exists($dir_backup . "/$d") && substr($d, -3) != "php") {
                                $archive->add($dir_backup . "/$d", PCLZIP_OPT_REMOVE_PATH, WPADM_DIR_BACKUP );
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
                if (isset($_POST['backup-type']) ) {
                    if ($_POST['backup-type'] == 'local') {
                        require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-core.php";
                        $dir = WPADM_DIR_BACKUP . '/' . $_POST['backup-name'] ;
                        $delete = false;
                        if (is_dir($dir)) {
                            WPAdm_Core::rmdir($dir);
                            $delete = true;
                        }
                        $dir = ABSPATH . WPADM_DIR_NAME .  '/' . $_POST['backup-name'] ;
                        if (is_dir($dir)) {
                            WPAdm_Core::rmdir($dir);
                            $delete = true;
                        }
                        if ($delete) {
                            parent::setMessage( str_replace('%s', $_POST['backup-name'], langWPADM::get('Backup(%s) was deleted', false) ) );
                        }
                    } elseif ($_POST['backup-type'] == 'dropbox') {
                        require_once DRBBACKUP_BASE_DIR . "/modules/dropbox.class.php";
                        $dropbox_options = get_option(PREFIX_BACKUP_ . 'dropbox-setting');
                        if ($dropbox_options) {
                            $dropbox_options = unserialize( base64_decode( $dropbox_options ) ); 
                            $dropbox = new dropbox($dropbox_options['app_key'], $dropbox_options['app_secret'], $dropbox_options['auth_token_secret']);
                            $folder_project = self::getNameProject();
                            $res = $dropbox->deleteFile("$folder_project/{$_POST['backup-name']}");
                            if ($res['is_deleted'] === true) {
                                parent::setMessage( str_replace('%s', $_POST['backup-name'], langWPADM::get('Backup(%s) was deleted', false) ) );
                            }
                        } 
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

            public static function dropboxConnect()
            {
                require_once DRBBACKUP_BASE_DIR . "/modules/dropbox.class.php";
                if (isset($_GET['app_key']) && isset($_GET['app_secret'])) {
                    if (empty($_GET['app_key']) && empty($_GET['app_secret'])) {
                        $_GET['app_key'] = WPADM_APP_KEY;
                        $_GET['app_secret'] = WPADM_APP_SECRET;
                    }
                    $dropbox = new dropbox($_GET['app_key'], $_GET['app_secret']);
                    $_SESSION['dropbox_key'] = $_GET['app_key']; 
                    $_SESSION['dropbox_secret'] = $_GET['app_secret']; 
                    $_SESSION['dropbox_request_token'] = $dropbox->getRequestToken();
                    echo '<script>window.location.href="' . $dropbox->generateAuthUrl( admin_url('admin-post.php?action=dropboxConnect') ) . '";</script>';
                } elseif (isset($_GET['oauth_token']) && isset($_GET['uid'])) {
                    $dropbox_options = get_option(PREFIX_BACKUP_ . 'dropbox-setting');
                    if ($dropbox_options) {
                        $dropbox_options = unserialize( base64_decode( $dropbox_options ) );
                    } else {
                        $dropbox_options = array();
                        add_option(PREFIX_BACKUP_ . 'dropbox-setting', base64_encode(serialize( $dropbox_options ) ) );
                    }
                    $dropbox = new dropbox(@$_SESSION['dropbox_key'], @$_SESSION['dropbox_secret']);
                    $access_token = $dropbox->getAccessToken($_SESSION['dropbox_request_token']);
                    $dropbox_options['app_key'] = @$_SESSION['dropbox_key'] ;
                    $dropbox_options['app_secret'] = @$_SESSION['dropbox_secret'] ;
                    $dropbox_options['auth_token_secret'] = $access_token;
                    $dropbox_options['oauth_token'] = @$_GET['oauth_token'] ;
                    $dropbox_options['uid'] = @$_GET['uid'] ;
                    update_option(PREFIX_BACKUP_ . 'dropbox-setting', base64_encode( serialize( $dropbox_options ) ) );
                    echo '<script>
                    if(window.opener){
                    window.opener.connectDropbox(null, null, "'.htmlspecialchars($access_token['oauth_token_secret']).'", "'.htmlspecialchars($access_token['oauth_token']).'", "'.htmlspecialchars($access_token['uid']).'");window.close();
                    }else{
                    window.location.href="' . admin_url("admin.php?page=wpadm_wp_full_backup_dropbox") . '";
                    }
                    </script>';
                    echo '<script>window.close();</script>';exit;
                } elseif (isset($_GET['not_approved'])) {
                    if( $_GET['not_approved'] == 'true' ){
                        echo '<script>window.close();</script>';exit;
                    }
                } else {
                    WPAdm_Core::log( langWPADM::get('Website "%d" returned an error during connection to Dropbox: "app key" and "app secret" wasn\'t found. Please, check your Dropbox settings.' , true, '%d', SITE_HOME) );
                }
                exit;
            }

            public static function dropbox_backup_create()
            {      
                require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-core.php";
                @session_write_close();          
                $log = new WPAdm_Core(array('method' => "local"), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                if (file_exists(WPAdm_Core::getTmpDir() . "/logs2")) {
                    unlink(WPAdm_Core::getTmpDir() . "/logs2");
                }
                if ( WPAdm_Core::dir_writeble(WPADM_DIR_BACKUP) ) {
                    $dropbox_options = get_option(PREFIX_BACKUP_ . 'dropbox-setting');
                    $send_to_dropbox = true;
                    if ($dropbox_options) {
                        $dropbox_options = unserialize( base64_decode( $dropbox_options ) );
                        if (!isset($dropbox_options['app_key'])) {
                            WPAdm_Core::log( langWPADM::get('Website "%d" returned an error during connection to Dropbox: "App Key" wasn\'t found. Please, check your Dropbox settings.' , false, '%d', SITE_HOME) );
                            $send_to_dropbox = false;
                        }
                        if (!isset($dropbox_options['app_secret'])) {
                            WPAdm_Core::log( langWPADM::get('Website "%d" returned an error during connection to Dropbox: "App Secret" wasn\'t found. Please, check your Dropbox settings.' , false, '%d', SITE_HOME) );
                            $send_to_dropbox = false;
                        }
                        if (!isset($dropbox_options['oauth_token'])) {
                            WPAdm_Core::log( langWPADM::get('Website "%d" returned an error during file sending to Dropbox: "Auth Token not exist. Files cannot be sent to Dropbox cloud. Please, check your Dropbox settings."' , false, '%d', SITE_HOME) );    
                            $send_to_dropbox = false;
                        }
                    } else {
                        WPAdm_Core::log( langWPADM::get('Website "%d" returned an error during connection to Dropbox: "app key" and "app secret" wasn\'t found. Please, check your Dropbox settings.' , false, '%d', SITE_HOME) );
                        $res['type'] = 'local';
                        $send_to_dropbox = false;
                    }

                    if ($send_to_dropbox) {
                        parent::$type = 'full'; 
                        $backup_local = new WPAdm_Core(array('method' => "local_backup", 'params' => array('optimize' => 1, 'limit' => 0, 'time' => @$_POST['time'], 'types' => array('db', 'files') )), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                        $res = $backup_local->getResult()->toArray();
                        $res['md5_data'] = md5( print_r($res, 1) );
                        $res['name'] = $backup_local->name;
                        $res['time'] = $backup_local->time;
                        $res['type'] = 'dropbox';
                        $res['counts'] = count($res['data']);
                        unset($backup);
                        $folder_project = self::getNameProject();
                        $backup = new WPAdm_Core(array('method' => "send-to-dropbox", 
                        'params' => array('files' => $res['data'], 
                        'access_details' => array('key' => $dropbox_options['app_key'], 
                        'secret' => $dropbox_options['app_secret'], 
                        'token' => $dropbox_options['auth_token_secret'],
                        'dir' => $res['name'],
                        'folder' => $folder_project),
                        )
                        ),
                        'full_backup_dropbox', WPAdm_Core::$pl_dir) ;
                        $result_send = $backup->getResult()->toArray();
                        if ($result_send['result'] == 'error') {
                            $res = array();
                            $res['error'] = $result_send['error'];
                            $res['result'] = 'error';
                            @rename(WPAdm_Core::getTmpDir() . "/logs2", WPAdm_Core::getTmpDir() . "/logs_error_" . $backup_local->time);
                        }

                        WPAdm_Core::rmdir( WPADM_DIR_BACKUP . "/{$res['name']}");
                    }
                } else {
                    $res['result'] = 'error';
                    $res['error'] = langWPADM::get('Website "%d" returned an error during file creation: Failed to create file, please check the permissions on the folder "%dir".', false, array('%d', '%dir'), array(SITE_HOME, WPADM_DIR_BACKUP) );
                    $res['data'] = array();
                    $res['size'] = 0;
                }
                @session_start();
                echo json_encode($res);
                wp_die(); 
            }
            public static function getNameProject()
            {
                $folder_project = str_ireplace( array("http://", "https://"), '', home_url() );
                $folder_project = str_ireplace( array( "-", '/', '.'), '_', $folder_project );
                return $folder_project;
            }


            public static function wpadm_show_backup()
            {

                require_once DRBBACKUP_BASE_DIR. "/modules/dropbox.class.php";
                parent::$type = 'full';
                $dropbox_options = get_option(PREFIX_BACKUP_ . 'dropbox-setting');
                if ($dropbox_options) {
                    $dropbox_options = unserialize( base64_decode( $dropbox_options ) );
                    if (isset($dropbox_options['app_key']) && isset($dropbox_options['app_secret']) && isset($dropbox_options['auth_token_secret'])) {
                        $dropbox = new dropbox($dropbox_options['app_key'], $dropbox_options['app_secret'], $dropbox_options['auth_token_secret']);
                        $folder_project = self::getNameProject();
                        $backups = $dropbox->listing($folder_project);
                        $n = count($backups['items']);
                        $data['data'] = array();
                        for($i = 0; $i < $n; $i++) {
                            $backup = $dropbox->listing($folder_project . "/" . $backups['items'][$i]['name']); 
                            $data['data'][$i]['name'] = $backups['items'][$i]['name'];
                            $data['data'][$i]['size'] = (int)$backup['size'] * 1024 * 1024;
                            $data['data'][$i]['dt'] = parent::getDateInName($backups['items'][$i]['name']);
                            $data['data'][$i]['count'] = count($backup['items']);
                            $data['data'][$i]['type'] = 'dropbox';
                            $k = $data['data'][$i]['count'];
                            $data['data'][$i]['files'] = '[';
                            for($j = 0; $j < $k; $j++) {
                                $data['data'][$i]['files'] .= $backup['items'][$i]['name'] . ',';
                            }
                        }
                    }
                } 
                $data_local = parent::read_backups();
                if (isset($data['data'])) {
                    $data['data'] = array_merge($data_local['data'], $data['data']);
                    $data['md5'] = md5( print_r( $data['data'] , 1 ) );
                } else {
                    $data = $data_local;
                }
                $show = !get_option('wpadm_pub_key') && is_super_admin();
                $error = parent::getError(true);
                $msg = parent::getMessage(true); 
                $base_path = DRBBACKUP_BASE_DIR ;
                ob_start();
                require_once $base_path . DIRECTORY_SEPARATOR . "template" . DIRECTORY_SEPARATOR . "wpadm_show_backup.php";
                echo ob_get_clean();
            }


            public static function draw_menu()
            {
                $show = true;
                $dropbox_options = get_option(PREFIX_BACKUP_ . 'dropbox-setting');
                if ($dropbox_options) {
                    $dropbox_options = unserialize( base64_decode( $dropbox_options ) );
                }
                if ( ( isset($dropbox_options['is_admin']) && $dropbox_options['is_admin'] == 1 ) || !isset($dropbox_options['is_admin']) ) {
                    if (!is_admin() || !is_super_admin()) {
                        $show = false;
                    }
                }
                if ($show) {
                    $menu_position = '1.9998887771'; 
                    if(self::checkInstallWpadmPlugins()) {
                        $page = add_menu_page(
                        'WPAdm', 
                        'WPAdm', 
                        "read", 
                        'wpadm_plugins', 
                        'wpadm_plugins',
                        plugins_url('/img/wpadm-logo.png', dirname( __FILE__ )),
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
                        plugins_url('/img/wpadm-logo.png', dirname( __FILE__ ) ),
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
    }

?>
