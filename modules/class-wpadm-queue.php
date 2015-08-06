<?php
/**
*
* Class WPAdm_Queue
*/
if (!class_exists('WPAdm_Queue')) {
    class WPAdm_Queue {

        /**
        * sleep, while waiting
        */
        const SLEEP_TIME = 2; //sec

        /**
        * the maximum number of falling asleep while waiting
        */
        const MAX_COUNT_SLEEPS = 1000;


        /**
        * @var array
        */
        private $contexts = array();

        /**
        * @var WPAdm_queue_status
        */
        private $status;

        /**
        * @var
        */
        private $id;

        private $error;

        public function __construct($id) {
            $this->id = $id;
        }

        public function add(WPAdm_Command_Context $context) {
            $this->contexts[] = $context;
            return $this;
        }

        public function clear()
        {

            $file = WPAdm_Core::getTmpDir() . '/' . $this->id. '.queue';
            if (file_exists($file)) {
                unlink($file);
            }

            $s = uniqid();
            $this->id = preg_replace("|(.*__).*|", '${1}'.$s, $this->id);

            $this->contexts = array();
            return $this;
        }

        public function execute() {
            $url = get_option('siteurl');
            $pu = parse_url($url);
            $host = $pu['host'];
            $path = isset($pu['path']) ? $pu['path'] . "/" : "/" ;

            $data = array(
            'method'    =>  'queue_controller',
            'params'    =>  array(
            'id'  =>  $this->id,
            ),
            'sign'      =>  '',

            );

            if (function_exists('fsockopen')) {
                $socket = @fsockopen($host, 80, $errno, $errstr, 30);
                $dp = explode(DIRECTORY_SEPARATOR, WPAdm_Core::$pl_dir );
                $pl = array_pop($dp) . '_';
                $data = $pl . 'request='.base64_encode(serialize($data)); 
                if (!$socket) {
                    //WPAdm_Core::log( "$errstr ($errno) " , "socket");
                    $curl = curl_init($url . "/");
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                    $res = curl_exec($curl);
                    curl_close($curl);
                    return $this->wait_result();
                }
                
                fwrite($socket, "POST {$path} HTTP/1.1\r\n");
                fwrite($socket, "Host: {$host}\r\n");

                fwrite($socket,"Content-type: application/x-www-form-urlencoded\r\n");
                fwrite($socket,"Content-length:".strlen($data)."\r\n");
                fwrite($socket,"Accept:*/*\r\n");
                fwrite($socket,"User-agent:Opera 10.00\r\n");
                fwrite($socket,"Connection:Close\r\n");
                fwrite($socket,"\r\n");
                fwrite($socket,"$data\r\n");
                fwrite($socket,"\r\n");

                @sleep(1);
                fclose($socket);
            }
            return $this->wait_result();
        }

        private function wait_result() {
            $step = 0;
            $done_file = WPAdm_Core::getTmpDir() . '/' . $this->id. '.queue.done';
            while (!file_exists($done_file) && $step <= self::MAX_COUNT_SLEEPS) {
                $step ++;
                @sleep(self::SLEEP_TIME);
            }
            if (!file_exists($done_file)) {
                $this->error  = 'No result of the command';
            } else {
                $queue = unserialize(file_get_contents($done_file));
                if (isset($queue['contexts'][0])) {
                    $this->error  = $queue['contexts'][0]->getError();
                }
            }
            if (file_exists($done_file)) {
                unlink($done_file);
            }
            if (!empty($this->error) ) {
                return false;
            }
            return true;
        }

        public function save() {
            $file = WPAdm_Core::getTmpDir() . '/' . $this->id. '.queue';
            $txt = serialize(
            array(
            'id' => $this->id,
            'contexts' => $this->contexts,
            )
            );
            file_put_contents($file, $txt);
            return $this;
        }

        public function getError() {
            return $this->error;
        }
    }
}