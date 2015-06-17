<?php 

    if (!defined("SERVER_URL_INDEX")) {
        define("SERVER_URL_INDEX", "http://www.wpadm.com/");
    }
    if (!defined("PHP_VERSION_DEFAULT")) {
        define("PHP_VERSION_DEFAULT", '5.2.4' );
    }
    if (!defined("MYSQL_VERSION_DEFAULT")) {
        define("MYSQL_VERSION_DEFAULT", '5.0' );
    }

    if (!defined("_PREFIX_STAT")) {
        define("_PREFIX_STAT", "counter_free_wpadm_");   
    }

    if (!defined("PREFIX_BACKUP_")) { 
        define("PREFIX_BACKUP_", "wpadm_backup_"); 
    }   
    if (!defined("PAGES_NEXT_PREV_COUNT_STAT")) {   
        define("PAGES_NEXT_PREV_COUNT_STAT", 3);
    }

    if (!class_exists("wpadm_class")) {

        add_action('admin_post_wpadm_activate_plugin', array('wpadm_class', 'activatePlugin') );
        add_action('admin_post_wpadm_delete_pub_key', array('wpadm_class', 'delete_pub_key') );

        //add_action('admin_post_wpadm_getJs', array('wpadm_class', 'getJs') );

        //add_action('admin_print_scripts', array('wpadm_class', 'includeJs' ));

        class wpadm_class {

            protected static $result = ""; 
            protected static $class = ""; 
            protected static $title = ""; 
            public static $type = ""; 
            public static $plugin_name = ""; 
            protected static $plugins = array('stats-counter' => '1.1',
            'wpadm_full_backup_storage' => '1.0',  
            'wpadm_full_backup_s3' => '1.0',  
            'wpadm_full_backup_ftp' => '1.0',  
            'dropbox-backup' => '1.0',  
            'wpadm_db_backup_storage' => '1.0',  
            'database-backup-amazon-s3' => '1.0',  
            'wpadm_file_backup_s3' => '1.0',  
            'wpadm_file_backup_ftp' => '1.0',  
            'wpadm_file_backup_dropbox' => '1.0',  
            'wpadm_db_backup_ftp' => '1.0',  
            'wpadm_db_backup_dropbox' => '1.0',  
            'wpadm_file_backup_storage' => '1.0',
            ); 
            const MIN_PASSWORD = 6; 


            private static $backup = "1";

            private static $status = "0";
            private static $error = "";

            public static function setBackup($b)
            {
                self::$backup = $b;
            }

            public static function setStatus($s)
            {
                self::$status = $s;
            }
            public static function setErrors($e)
            {
                self::$error = $e;
            }

            public static function getDateInName($name)
            {
                $date_ = explode(self::$type . '-', $name);
                if (isset($date_[1])) {
                    $date = explode('_', $date_[1]);
                    $d = "{$date[0]}-{$date[1]}-{$date[2]} {$date[3]}:" . preg_replace("/\([0-9]+\)/", '', $date[4]);
                }
                return $d;

            }
            public static function backupSend()
            {
                $data['status'] = self::$backup . self::$status;
                $data['error'] = self::$error;
                $data['pl'] = WPAdm_Core::$plugin_name;
                $data['site'] = get_option('siteurl');
                $data['actApi'] = 'setBackup';
                self::sendToServer($data);
            }



            static function delete_pub_key() 
            {
                delete_option('wpadm_pub_key');   
                header("Location: " . $_SERVER['HTTP_REFERER']);
            }
            public static function checkInstallWpadmPlugins()
            {
                $return = false;
                $i = 1;
                foreach(self::$plugins as $plugin => $version) {
                    if (self::check_plugin($plugin)) {
                        $i ++;
                    }
                }
                if ($i > 2) {
                    $return = true;
                }
                return $return;
            }

            static function setResponse($data) 
            {
                $msg = errorWPADM::getMessage($data['code']);
                if(isset($data['data'])) {
                    if (isset($data['data']['replace'])) {
                        foreach($data['data']['replace'] as $key => $value) {
                            $msg = str_replace("<$key>", $value, $msg);
                        }
                    }
                }
                if ($data['status'] == 'success') {
                    self::setMessage($msg);
                } else {
                    self::setError($msg);
                }

                return isset($data['data']) ? $data['data'] : array();

            }


            protected static function setError($msg = "")
            {
                if (!empty($msg)) {
                    $_SESSION['errorMsgWpadmDB'] = isset($_SESSION['errorMsgWpadmDB']) ? $_SESSION['errorMsgWpadmDB'] . '<br />' . $msg : $msg;
                }
            }
            protected static function getError($del = false)
            {
                $error = "";
                if (isset($_SESSION['errorMsgWpadmDB'])) {
                    $error = $_SESSION['errorMsgWpadmDB'];
                    if($del) {
                        unset($_SESSION['errorMsgWpadmDB']);
                    }
                }
                return $error;
            }

            protected static function setMessage($msg)
            {
                if (!empty($msg)) {
                    $_SESSION['msgWpadmDB'] = isset($_SESSION['msgWpadmDB']) ? $_SESSION['msgWpadmDB'] . '<br />' . $msg : $msg;
                }
            }
            protected static function getMessage($del = false)
            {
                $msg = "";
                if (isset($_SESSION['msgWpadmDB'])) {
                    $msg = $_SESSION['msgWpadmDB'];
                    if($del) {
                        unset($_SESSION['msgWpadmDB']);
                    }
                }
                return $msg;
            }



            public static function sendToServer($postdata = array(), $stat = false)
            {
                if (count($postdata) > 0) {

                    if ($stat) {
                        if ($counter_id = get_option(_PREFIX_STAT . 'counter_id')) {
                            $postdata['counter_id'] = $counter_id;
                        }
                    }
                    $postdata = http_build_query($postdata, '', '&');

                    $length = strlen($postdata); 


                    if (function_exists("curl_init") && function_exists("curl_setopt") && function_exists("curl_exec") && function_exists("curl_close")) {
                        if ($stat) {
                            $url = SERVER_URL_VISIT_STAT . "/Api.php";
                        } else {
                            $url = WPADM_URL_BASE . "api/";
                        }
                        $curl = curl_init($url);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl, CURLOPT_POST, true);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
                        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
                        curl_setopt($curl, CURLOPT_USERPWD, "admin24:admin24");
                        self::$result = curl_exec($curl);
                        curl_close($curl);
                        if ($stat) {
                            return unserialize(self::$result);
                        } else {
                            return json_decode(self::$result, true);
                        }
                    } elseif (function_exists("fsockopen")) {
                        if ($stat) {
                            $url = SERVER_URL_STAT;
                            $req = '/Api.php';
                        } else {
                            $url = substr(WPADM_URL_BASE, 7);
                            $req = '/api/';
                        }
                        $out = "POST " . $req . " HTTP/1.1\r\n";
                        $out.= "HOST: " . $url . "\r\n";
                        $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
                        $out.= "Content-Length: ".$length."\r\n";
                        $out.= "Connection:Close\r\n\r\n";
                        $out.= $postdata."\r\n\r\n";
                        try {
                            $errno='';
                            $errstr = '';
                            $socket = @fsockopen($url, 80, $errno, $errstr, 30);
                            if($socket) {
                                if(!fwrite($socket, $out)) {
                                    throw new Exception("unable to write fsockopen");
                                } else {
                                    while ($in = @fgets ($socket, 1024)) {
                                        self::$result .= $in;
                                    } 
                                }
                                self::$result = explode("\r\n\r\n", self::$result);
                                if ($stat) {
                                    return unserialize(self::$result);
                                } else {
                                    return json_decode(self::$result, true);
                                }
                                throw new Exception("error in data");
                            } else {
                                throw new Exception("unable to create socket");
                            }
                            fclose($socket);
                        } catch(exception $e) {
                            return false;
                        }
                    }  
                }
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
                        $info = self::$plugin_name;
                        $mail = get_option(PREFIX_BACKUP_ . "email");
                        if ($mail) {
                            add_option(PREFIX_BACKUP_ . "email", $email);
                        } else {
                            update_option(PREFIX_BACKUP_ . "email",$email);
                        }
                        $data = self::sendToServer(
                        array(
                        'actApi' => "activate",
                        'email' => $email,
                        'password' => $password,
                        'url' => get_option("siteurl"),
                        'plugin' => $info,
                        )
                        );
                        $res = self::setResponse($data);
                    }
                }
                if (isset($res['url']) && !empty($res['url'])) {
                    header("Location: " . $res['url']);
                } else {
                    header("Location: " . admin_url("admin.php?page=wpadm_plugins"));
                }
            }

            public static function include_admins_script()
            {
                wp_enqueue_style('css-admin-wpadm-db', plugins_url( "/template/css/admin-style-wpadm.css", dirname(__FILE__) ) );

                wp_enqueue_script( 'js-admin-wpadm-db', plugins_url( "/template/js/admin-wpadm.js",  dirname(__FILE__) ) );
                wp_enqueue_script( 'postbox' );

            }
            protected static function read_backups($dirs_read = false)
            {
                $name = get_option('siteurl');

                $name = str_replace("http://", '', $name);
                $name = str_replace("https://", '', $name);
                $name = preg_replace("|\W|", "_", $name);
                $name .= '-' . self::$type . '-' . date("Y_m_d_H_i");

                $dir_backup = ABSPATH . 'wpadm_backups';

                $backups = array('data' => array(), 'md5' => '');
                if (is_dir($dir_backup)) { 
                    $i = 0;
                    $dir_open = opendir($dir_backup);
                    while($d = readdir($dir_open)) {
                        if ($d != '.' && $d != '..' && is_dir($dir_backup . "/$d") && strpos($d, self::$type) !== false) {
                            $backups['data'][$i]['dt'] = self::getDateInName($d);
                            $backups['data'][$i]['name'] = "$d";
                            if ($dirs_read === false) {
                                $size = 0;
                                $dir_b = opendir($dir_backup . "/$d");
                                $count_zip = 0;
                                $backups['data'][$i]['files'] = "[";
                                while($d_b = readdir($dir_b)) {
                                    if ($d_b != '.' && $d_b != '..' && file_exists($dir_backup . "/$d/$d_b") && substr($d_b, -3) != "php") {
                                        $backups['data'][$i]['files'] .= "$d_b,";
                                        $size += filesize($dir_backup . "/$d/$d_b");
                                        $count_zip = $count_zip + 1;
                                    }
                                }
                                $backups['data'][$i]['files'] .= ']';
                                $backups['data'][$i]['size'] = $size;
                                $backups['data'][$i]['type'] = 'local';
                                $backups['data'][$i]['count'] = $count_zip;
                            }
                            $i += 1;
                        }
                    }
                }
                $backups['md5'] = md5( print_r($backups['data'] , 1) );
                return $backups;
            }
            public static function check_plugin($name = "", $version = false)
            {
                if (!empty($name)) {
                    if ( ! function_exists( 'get_plugins' ) ) {
                        require_once ABSPATH . 'wp-admin/includes/plugin.php';
                    }
                    $name2 = str_replace("-", "_", $name);
                    $plugin = get_plugins("/$name");
                    if (empty($plugin)) {
                        $plugin = get_plugins("/$name2");
                    }
                    if (count($plugin) > 0) {
                        if (isset(self::$plugins[$name]) && (isset($plugin["$name.php"]) || isset($plugin["$name2.php"]))) {
                            if ($version) {
                                if (self::$plugins[$name] == $plugin["$name.php"]['Version']) {
                                    return true;
                                }
                                if (self::$plugins[$name] == $plugin["$name2.php"]['Version']) {
                                    return true;
                                }
                            } else {
                                if (is_plugin_active("$name/$name.php") || is_plugin_active("$name/$name2.php") || is_plugin_active("$name2/$name2.php")) {
                                    return true;
                                }
                            }
                        }
                    }
                    return false;
                }
            }
        }
    }

    if (! function_exists('wpadm_plugins')) {
        function wpadm_plugins()
        {
            global $wp_version;

            $c = get_system_data();
            $phpVersion         = $c['php_verion'];
            $maxExecutionTime   = $c['maxExecutionTime'];
            $maxMemoryLimit     = $c['maxMemoryLimit'];
            $extensions         = $c['extensions'];
            $disabledFunctions  = $c['disabledFunctions'];
            //try set new max time

            $newMaxExecutionTime = $c['newMaxExecutionTime'];
            $upMaxExecutionTime = $c['upMaxExecutionTime'];
            $maxExecutionTime = $c['maxExecutionTime'];

            //try set new memory limit
            $upMemoryLimit = $c['upMemoryLimit'];
            $newMemoryLimit = $c['newMemoryLimit']; 
            $maxMemoryLimit = $c['maxMemoryLimit'];

            //try get mysql version
            $mysqlVersion = $c['mysqlVersion'];

            $show = !get_option('wpadm_pub_key') || (!is_super_admin() || !is_admin()) || !get_option(_PREFIX_STAT . 'counter_id');
        ?> 


        <?php if (!$show) {?>
            <div class="cfTabsContainer">
                <div id="cf_signin" class="cfContentContainer" style="display: block;">
                    <form method="post" action="<?php echo WPADM_URL_BASE . "user/login" ; ?>" autocomplete="off" target="_blank">
                        <div class="inline" style="width: 52%; margin-top: 0; color: #fff;">
                            WPAdm Sign-In:
                            <input class="input-small" type="email" required="required" name="username" placeholder="Email">
                            <input class="input-small" type="password" required="required" name="password" placeholder="Password">
                            <input class="button-wpadm" type="submit" value="Sign-In" name="submit" style="margin-top:1px;">    
                        </div>
                        <div class="wpadm-info-auth" style="width: 45%;">
                            Enter your email and password from an account at <a href="http://www.wpadm.com" target="_blank" style="color: #fff;" >www.wpadm.com</a>.<br /> After submitting user credentials you will be redirected to your Admin area on <a href="http://www.wpadm.com" style="color: #fff;" target="_blank">www.wpadm.com</a>.
                        </div>
                    </form>
                </div>
            </div>
            <?php } else {?>
            <div class="cfTabsContainer" style="display: none;">
                <div id="cf_activate" class="cfContentContainer">
                    <form method="post" action="<?php echo admin_url( 'admin-post.php?action=wpadm_activate_plugin' )?>" >
                        <div class="wpadm-info-title">
                            Free Sign Up to use more functionality...
                        </div>
                        <div class="wpadm-registr-info">
                            <table class="form-table">
                                <tbody>
                                    <tr valign="top">
                                        <th scope="row">
                                            <label for="email">E-mail</label>
                                        </th>
                                        <td>
                                            <input id="email" class="regular-text" type="text" name="email" value="">
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row">
                                            <label for="password">Password</label>
                                        </th>
                                        <td>
                                            <input id="password" class="regular-text" type="password" name="password" value="">
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row">
                                            <label for="password-confirm">Password confirm</label>
                                        </th>
                                        <td>
                                            <input id="password-confirm" class="regular-text" type="password" name="password-confirm" value="">
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row">
                                        </th>
                                        <td>
                                            <input class="button-wpadm" type="submit" value="Register & Activate" name="submit">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="wpadm-info">
                            <span style="font-weight:bold; font-size: 14px;">If you are NOT registered at WPAdm,</span> enter your email and password to use as your Account Data for authorization on WPAdm. <br /><span style="font-weight: bold;font-size: 14px;">If you already have an account at WPAdm</span> and you want to Sign-In, so please, enter your registered credential data (email and password twice).
                        </div>
                    </form>
                </div>
            </div>  
            <?php } ?>

        <script>
            jQuery(document).ready(function() {
                jQuery('.plugins-icon').click(function() {
                    title = jQuery(this).parent('.plugins-title');
                    box = title.parent('.plugins-box');
                    content = box.find('.plugins-info-content');
                    display = content.css('display');
                    if (display == 'none') {
                        content.show('slow');
                    } else {
                        content.hide('slow');
                    }
                })
            })
            function showRegistartion(show)
            {
                if (show) {
                    jQuery('.cfTabsContainer').show('slow');
                } else {
                    jQuery('.cfTabsContainer').hide('slow');
                }
            }
        </script>

        <div class="clear" style="margin-bottom: 50px;"></div>
        <table class="wp-list-table widefat fixed" >
            <thead>
                <tr>
                    <th></th>
                    <th>Recommended value</th>
                    <th>Your value</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>  
                <tr>
                    <th scope="row">PHP Version</th>
                    <td><?php echo PHP_VERSION_DEFAULT ?> or greater</td>
                    <td><?php echo check_version($phpVersion , PHP_VERSION_DEFAULT) === false ? '<span style="color:#fb8004;font-weight:bold;">' . $phpVersion .'</span>' : $phpVersion ?></td>
                    <td><?php echo (check_version($phpVersion , PHP_VERSION_DEFAULT) ? '<span style="color:green;font-weight:bold;">OK</span>' : '<span style="color:#fb8004;font-weight:bold;">Please update Your version for correct working of plugin</span>') ?></td>
                </tr>
                <tr>
                    <th scope="row">MySQL Version</th>
                    <td><?php echo MYSQL_VERSION_DEFAULT ?> or greater</td>
                    <td><?php echo check_version($mysqlVersion , MYSQL_VERSION_DEFAULT) === false ? '<span style="color:#fb8004;font-weight:bold;">' . $mysqlVersion .'</span>' : $mysqlVersion; ?></td>
                    <td><?php echo (check_version($mysqlVersion , MYSQL_VERSION_DEFAULT) ? '<span style="color:green;font-weight:bold;">OK</span>' : '<span style="color:#fb8004;font-weight:bold;">Please update Your version for correct working of plugin</span>') ?></td>
                </tr>
                <tr>
                    <th scope="row">Max Execution Time</th>
                    <td><?php echo $newMaxExecutionTime ?></td>
                    <td><?php echo ($upMaxExecutionTime == 0) ? '<span style="color:#fb8004;font-weight:bold;">' . $maxExecutionTime .'</span>' : $maxExecutionTime; ?></td>
                    <td><?php echo ($upMaxExecutionTime == 1) ? '<span style="color:green; font-weight:bold;">OK</span>' : '<span style="color:#fb8004;font-weight:bold;">Correct operation of the plugin can not be guaranteed</span>'; ?></td>
                </tr>
                <tr>
                    <th scope="row">Max Memory Limit</th>
                    <td><?php echo $newMemoryLimit . 'M' ?></td>
                    <td><?php echo ($upMemoryLimit == 0) ? '<span style="color:#fb8004;font-weight:bold;">' . $maxMemoryLimit .'</span>' : $maxMemoryLimit  ?></td>
                    <td><?php echo ($upMemoryLimit == 1) ? '<span style="color:green;font-weight:bold;">OK</span>' : '<span style="color:#fb8004;font-weight:bold;">Correct operation of the plugin can not be guaranteed.</span>'; ?></td>
                </tr>
                <tr>
                    <th scope="row">PHP Extensions</th>
                    <?php $ex = $c['ex']; ?>
                    <td><?php echo ( $ex ) === false ? 'All present' : '<span style="color:#ffba00;font-weight:bold;">' . implode(", ", $ex) . '</span>'; ?></td>
                    <td><?php echo ( $ex ) === false ? 'Found' : '<span style="color:#ffba00;font-weight:bold;">Not Found</span>'; ?></td>
                    <td><?php echo ( $ex ) === false ? '<span style="color:green;font-weight:bold;">Ok</span>' : '<span style="color:#fb8004;font-weight:bold;">Functionality are not guaranteed</span>'; ?></td>
                </tr>
                <tr>
                    <th scope="row">Disabled Functions</th>
                    <td colspan="3" align="left"><?php echo ( $func = $c['func']) === false ? '<span style="color:green;font-weight:bold;">All the necessary functions are enabled</span>' : '<span style="color:#fb8004;font-weight:bold;">Please enable these functions for correct work of plugin: ' . implode(", ", $func) . '</span>'; ?></td>
                </tr>
                <tr>
                    <th scope="row">Plugin Access</th>
                    <td colspan="3" align="left"><?php echo ( ( is_admin() && is_super_admin() ) ? "<span style=\"color:green; font-weight:bold;\">Granted</span>" : "<span style=\"color:red; font-weight:bold;\">You can't administrate this WPAdm Plugin(s) as it requires an 'Admin' access for this website</span>")?></td>
                </tr>
            </tbody>
        </table>
        <?php 
        }
    }

    if (! function_exists('check_function')) {
        function check_function($func, $search, $type = false)
        {
            if (is_string($func)) {
                $func = explode(", ", $func);
            }
            if (is_string($search)) {
                $search = explode(", ", $search);
            }
            $res = false;
            $n = count($search);
            for($i = 0; $i < $n; $i++) {
                if (in_array($search[$i], $func) === $type) {
                    $res[] = $search[$i];
                }
            }
            return $res;
        }
    }

    if (! function_exists('check_version')) {
        function check_version($ver, $ver2)
        {
            return version_compare($ver, $ver2, ">");
        }
    }
    if (!function_exists("get_system_data")) {
        function get_system_data()
        {

            global $wp_version;

            $phpVersion         = phpversion();
            $maxExecutionTime   = ini_get('max_execution_time');
            $maxMemoryLimit     = ini_get('memory_limit');
            $extensions         = implode(', ', get_loaded_extensions());
            $disabledFunctions  = ini_get('disable_functions');
            $mysqlVersion       = '';
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD);
            if (!mysqli_connect_errno()) {
                $mysqlVersion = $mysqli->server_info;
            }
            $upMaxExecutionTime = 0;
            $newMaxExecutionTime = intval($maxExecutionTime) + 60;
            @set_time_limit( $newMaxExecutionTime );
            if( ini_get('max_execution_time') == $newMaxExecutionTime ){
                $upMaxExecutionTime = 1;
                $maxExecutionTime = ini_get('max_execution_time');
            }
            $upMemoryLimit = 0;
            $newMemoryLimit = intval($maxMemoryLimit) + 60;
            ini_set('memory_limit', $newMemoryLimit.'M');
            if( ini_get('memory_limit') == $newMemoryLimit ){
                $upMemoryLimit = 1;
                $maxMemoryLimit = ini_get('memory_limit');
            }
            $extensions_search = array('curl', 'json', 'mysqli', 'sockets', 'zip', 'ftp');
            $disabledFunctions_search = array('set_time_limit', 'curl_init', 'fsockopen', 'ftp_connect');

            $ex = check_function($extensions, $extensions_search);
            $func = check_function($disabledFunctions, $disabledFunctions_search, true);

            return array('wp_version' => $wp_version, 'php_verion' => phpversion(), 
            'maxExecutionTime' => $maxExecutionTime, 'maxMemoryLimit' => $maxMemoryLimit, 
            'extensions' => $extensions, 'disabledFunctions' => $disabledFunctions,
            'mysqlVersion' => $mysqlVersion, 'upMaxExecutionTime'  => $upMaxExecutionTime,
            'newMaxExecutionTime' => $newMaxExecutionTime, 'upMemoryLimit' => $upMemoryLimit,
            'newMemoryLimit' => $newMaxExecutionTime, 'maxMemoryLimit' => $maxMemoryLimit,
            'ex' => $ex, 'func' => $func, 'wp_lang' => get_option('WPLANG'),
            );

        }
    }

?>
