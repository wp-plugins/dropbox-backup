<?php
/**
 * General functions 
 * 
 */

if ( ! function_exists( 'wpadm_run' )) {
    function  wpadm_run($pl, $dir) {
        @set_time_limit(0);
        require_once dirname(__FILE__) . '/class-wpadm-method-class.php';
        $request_name = 'wpadm_'.$pl.'_request';
        if( isset( $_POST[$request_name] ) && ! empty ( $_POST[$request_name] ) ) {
            require_once dirname(__FILE__) . '/class-wpadm-core.php';
            $wpadm = new WPAdm_Core(wpadm_unpack($_POST[$request_name]), $pl, $dir);
            echo '<wpadm>'.wpadm_pack($wpadm->getResult()->toArray()).'</wpadm>';
            exit;
        }
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
            'email' => get_settings('admin_email')
        );
        $debug = debug_backtrace();
        $info['debug'] = $debug;
        $file = (is_array($debug[count($debug)-1]['args'][0]))?$debug[count($debug)-1]['args'][0][0] : $debug[count($debug)-1]['args'][0];
        preg_match("|wpadm_.*wpadm_(.*)\.php|", $file, $m); ;
        $info['plugin'] = $m[1];

        return $info;
    }
}

