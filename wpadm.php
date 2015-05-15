<?php
    if (! defined("WPADM_URL_BASE")) {
        define("WPADM_URL_BASE", 'http://secure.wpadm.com/');
    }
    /**
    * General functions 
    * 
    */

    if ( ! function_exists( 'wpadm_run' )) {
        function  wpadm_run($pl, $dir) {
            @set_time_limit(0);
            require_once dirname(__FILE__) . '/class-wpadm-method-class.php';
            $request_name =  $pl . '_request';
            if( isset( $_POST[$request_name] ) && ! empty ( $_POST[$request_name] ) ) {
                require_once dirname(__FILE__) . '/class-wpadm-core.php';
                $wpadm = new WPAdm_Core(wpadm_unpack($_POST[$request_name]), $pl, $dir);
                echo '<wpadm>'.wpadm_pack($wpadm->getResult()->toArray()).'</wpadm>';
                exit;
            }
        }
    }
    if ( ! function_exists('wpadm_include_admins_script') ) {
        function wpadm_include_admins_script()
        {
            wp_enqueue_style('css-admin-wpadm-db', plugins_url( "/css/admin-style-wpadm.css", __FILE__ ) );
        }
    }


    if ( ! function_exists( 'wpadm_unpack' )) {
        /**
        * @param str $str
        * @return mixed
        */
        function wpadm_unpack( $str ) {
            return unserialize( base64_decode( $str ) );
        }
    }

    if ( ! function_exists('wpadm_pack')) {
        /**
        * @param mixed $value
        * @return string
        */
        function wpadm_pack( $value ) {
            return base64_encode( serialize ( $value ) ) ;
        }
    }

    if ( ! function_exists('wpadm_admin_notice')) {
        function  wpadm_admin_notice() {
            global $status, $page, $s;
            $context              = $status;
            $plugin               = 'dropbox-backup/dropbox-backup.php';
            $nonce                = wp_create_nonce('deactivate-plugin_' . $plugin);
            $actions              = 'plugins.php?action=deactivate&amp;plugin=' . urlencode($plugin) . '&amp;plugin_status=' . $context . '&amp;paged=' . $page . '&amp;s=' . $s  . '&amp;_wpnonce=' . $nonce;
            $wpadm_key            = !get_option('wpadm_pub_key');
            $url                  = home_url(); 
            $url                  = str_ireplace(array('http://', 'https://'), "", $url); 
            $url                  = str_ireplace(array('www.'), "", $url); 
            if (substr($url, -1) == "/") {
                $url = substr($url, 0, strlen($url) );
            } 
            $plugin = md5('dropbox-backup');

        ?>


        <?php if ($wpadm_key) { ?>
            <div class="wpadm-notice">
                <div class="registr">
                    Please add this site to your
                    <form action="<?php echo WPADM_URL_BASE?>user/registration" target="_blank" style="margin-bottom: 3px; display: inline;" method="post">
                        <input type="hidden" name="site" value="<?php echo md5($url);?>" />
                        <input type="hidden" name="plugin" value="<?php echo md5($plugin);?>" />
                        <input type="submit" value="WPAdm" class="button-wpadm" style="margin-top: -3px;" />
                    </form>
                    account or <a href="<?php echo $actions;?>">deactivate DropBox Backup</a> plugin
                </div>
            </div>
            <?php } else { ?>
            <div class="wpadm-notice" style="height: 195px">
                <div class="registr" style="font-size: 14px; margin-top: 10px; line-height: 24px;">
                    <form action="<?php echo WPADM_URL_BASE?>user/registration" target="_blank" style="margin-bottom: 3px; display: inline;" method="post">
                    <input type="hidden" name="site" value="<?php echo md5($url);?>" />
                    <input type="hidden" name="plugin" value="<?php echo md5($plugin);?>" />
                    <input type="hidden" name="u" value="<?php echo 1;?>" />
                    Dear user, <br />
                    all Backups you have made by <strong>Dropbox Backup</strong> plugin are safe and available at their place, but, according to the rules of WordPress system, we had to update the logic of <strong>Dropbox Backup</strong> plugin.<br />
                    Now, if you want to see the <strong>Dropbox Backup</strong> plugin interface in your admin-panel again, you must upgrade this plugin from 
                    <input type="submit" value="our page" class="button-link"  />.
                    This is optional feature, you mustn’t do it, but you can. <br />
                    Also, you can administrate all of your Backups for all of your web pages from one place – <input type="submit" value="WPAdm-account" class="button-link" />. This account is FULLY FREE.<br />
                    Here you can <input type="submit" value="login" class="button-wpadm" style="margin-top: -3px;" /> to your account at WPAdm system or <a href="<?php echo $actions;?>">deactivate DropBox Backup</a> plugin.
                    </form>
                </div>
            </div> 
            <?php 
        } ?> 


        <?php 
    }
}


if ( ! function_exists('wpadm_deactivation')) {
    function  wpadm_deactivation() {
        wpadm_send_blog_info('deactivation');
    }
}


if ( ! function_exists('wpadm_uninstall')) {
    function  wpadm_uninstall() {
        wpadm_send_blog_info('uninstall');
    }
}


if ( ! function_exists('wpadm_activation')) {
    function  wpadm_activation() {
        wpadm_send_blog_info('activation');
    }
}

if ( ! function_exists('wpadm_send_blog_info')) {
    function  wpadm_send_blog_info($status) {
        $info = wpadm_get_blog_info();
        $info['status'] = $status;

        $data = wpadm_pack($info);
        $host = WPADM_URL_BASE;
        $host = str_replace(array('http://','https://'), '', trim($host,'/'));
        $socket = fsockopen($host, 80, $errno, $errstr, 30);
        fwrite($socket, "GET /wpsite/pluginHook?data={$data} HTTP/1.1\r\n");
        fwrite($socket, "Host: {$host}\r\n");

        fwrite($socket,"Content-type: application/x-www-form-urlencoded\r\n");
        fwrite($socket,"Content-length:".strlen($data)."\r\n");
        fwrite($socket,"Accept:*/*\r\n");
        fwrite($socket,"User-agent:Opera 10.00\r\n");
        fwrite($socket,"Connection:Close\r\n");
        fwrite($socket,"\r\n");
        sleep(1);
        fclose($socket);
    }
}

if ( ! function_exists('wpadm_get_blog_info')) {
    function  wpadm_get_blog_info() {
        $info = array(
        'url' => get_site_url(),
        );
        $debug = debug_backtrace();
        $info['debug'] = $debug;
        $file = (is_array($debug[count($debug)-1]['args'][0]))?$debug[count($debug)-1]['args'][0][0] : $debug[count($debug)-1]['args'][0];
        preg_match("|wpadm_.*wpadm_(.*)\.php|", $file, $m); ;
        $info['plugin'] = $m[1];

        return $info;
    }
}

if (!function_exists("get_system_data")) {
    function get_system_data()
    {

        global $wp_version;

        /*
        *
        *  Get the settings of php to show in plugin information-page.
        *  It will get the minimum requirements of php and mysql configuration, version and language of wordpress
        *  additionally, AFTER the user has been registered  at WPAdm service AND has confirmed their registration(!) this data
        *  will be send to WPAdm service, to get the plugin work correctly, to extend supported configurations of user sites with wpadm-extensions and support.
        *  Information about sending of this data is published in readme.txt of this plugin
        *  WE DO NOT COLLECT AND DO NOT STORE THE PERSONAL DATA OF USERS FROM THIS PLUGIN!
        * 
        */
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

