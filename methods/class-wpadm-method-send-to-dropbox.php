<?php
/**
 * Бэкап сайта
 * Class WPadm_Method_Send_To_Dropbox
 */
if (!class_exists('WPadm_Method_Send_To_Dropbox')) {
    class WPadm_Method_Send_To_Dropbox extends WPAdm_Method_Class {
        /**
         * @var WPAdm_Queue
         */
        private $queue;

        private $id;

        //private $name = '';

        public function getResult()
        {
            $errors = array();
            $this->id = uniqid('wpadm_method_send_to_dropbox_');

            $this->result->setResult(WPAdm_Result::WPADM_RESULT_SUCCESS);
            $this->result->setError('');

            $this->queue = new WPAdm_Queue($this->id);

            # КОПИРОВАНИЕ ФАЙЛОВ НА Dropbox
            $ad = $this->params['access_details'];
            WPAdm_Core::log('Начинаем копирование файлов на Dropbox');
            $this->queue->clear();
            $files = $this->params['files'];
            //$this->getResult()->setData($files);

            $dir = (isset($ad['dir'])) ? $ad['dir'] : '/';
            //$dir = trim($dir, '/') . '/' . $this->name;
            foreach($files as $file) {
                $commandContext = new WPAdm_Command_Context();
                $commandContext->addParam('command', 'send_to_dropbox')
                    ->addParam('key', $ad['key'])
                    ->addParam('secret', $ad['secret'])
                    ->addParam('token', $ad['token'])
                    ->addParam('folder_project',$ad['folder'])
                    ->addParam('folder', $dir)
                    ->addParam('files', ABSPATH . $file);
                $this->queue->add($commandContext);
                unset($commandContext);
            }
            $res = $this->queue->save()
                ->execute();
            if (!$res) {
                WPAdm_Core::log('Dropbox: ' . $this->queue->getError());
                $errors[] = 'Dropbox: '.$this->queue->getError();
            }
            WPAdm_Core::log('Закончили копирование файлов на Dropbox');
            if (count($errors) > 0) {
                $this->result->setError(implode("\n", $errors));
                $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
            }

            return $this->result;


        }



        public function createListFilesForArchive() {
            $folders = array();
            $files = array();

            $files = array_merge(
                $files,
                array(
                    ABSPATH .'/.htaccess',
                    ABSPATH .'/index.php',
                    ABSPATH .'/license.txt',
                    ABSPATH .'/readme.html',
                    ABSPATH .'/wp-activate.php',
                    ABSPATH .'/wp-blog-header.php',
                    ABSPATH .'/wp-comments-post.php',
                    ABSPATH .'/wp-config.php',
                    ABSPATH .'/wp-config-sample.php',
                    ABSPATH .'/wp-cron.php',
                    ABSPATH .'/wp-links-opml.php',
                    ABSPATH .'/wp-load.php',
                    ABSPATH .'/wp-login.php',
                    ABSPATH .'/wp-mail.php',
                    ABSPATH .'/wp-settings.php',
                    ABSPATH .'/wp-signup.php',
                    ABSPATH .'/wp-trackback.php',
                    ABSPATH .'/xmlrpc.php',
                )
            );

            if (!empty($this->params['minus-path'])) {
                foreach($files as $k=>$v) {
                    $v = str_replace(ABSPATH .'/' , '',  $v);
                    if (in_array($v, $this->params['minus-path'])) {
                        unset($files[$k]);
                        WPAdm_Core::log('Пропускаем файл ' . $v);
                    }
                }
            }

            $folders = array_merge(
                $folders,
                array(
                    ABSPATH .'/wp-admin',
                    ABSPATH .'/wp-content',
                    ABSPATH .'/wp-includes',
                )
            );

            foreach($this->params['plus-path'] as $p) {
                if (empty($p)) {
                    continue;
                }
                $p = ABSPATH .'/' . $p;
                if (file_exists($p)) {
                    if (is_dir($p)) {
                        $folders[] = $p;
                    } else{
                        $files[] = $p;
                    }
                }
            }

            $folders = array_unique($folders);
            $files = array_unique($files);

            foreach($folders as $folder) {
                if (!is_dir($folder)) {
                    continue;
                }
                $files = array_merge($files, $this->directoryToArray($folder, true));
            }
            return $files;
        }


        private function directoryToArray($directory, $recursive) {
            $array_items = array();

            $d = str_replace(ABSPATH . '/', '', $directory);
            // пропускаем ненужные директории

            if (in_array($d, $this->params['minus-path'])) {
                WPAdm_Core::log('Пропускаем папку ' . $directory);
                return array();
            }
            if ($handle = opendir($directory)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        if (is_dir($directory. "/" . $file)) {
                            if($recursive) {
                                $array_items = array_merge($array_items, $this->directoryToArray($directory. "/" . $file, $recursive));
                            }

                            $file = $directory . "/" . $file;
                            if (!is_dir($file)) {
                                $ff = preg_replace("/\/\//si", "/", $file);
                                $f = str_replace(ABSPATH . '/', '', $ff);
                                // пропускаем ненужные директории
                                if (!in_array($f, $this->params['minus-path'])) {
                                    $array_items[] = $ff;
                                } else {
                                    WPAdm_Core::log('Пропускаем файл ' . $ff);
                                }
                            }
                        } else {
                            $file = $directory . "/" . $file;
                            if (!is_dir($file)) {
                                $ff = preg_replace("/\/\//si", "/", $file);
                                $f = str_replace(ABSPATH . '/', '', $ff);
                                // пропускаем ненужные директории
                                if (!in_array($f, $this->params['minus-path'])) {
                                    $array_items[] = $ff;
                                } else {
                                    WPAdm_Core::log('Пропускаем файл ' . $ff);
                                }
                            }
                        }
                    }
                }
                closedir($handle);
            }
            return $array_items;
        }


        /*
         * Берем реквизиты доступа к MySQL из параметров WP
         * return Array()
         */
        private function getWpMysqlParams()
        {
            $db_params = array(
                'password' => 'DB_PASSWORD',
                'db' => 'DB_NAME',
                'user' => 'DB_USER',
                'host' => 'DB_HOST',
            );

            $r = "/define\('(.*)', '(.*)'\)/";
            preg_match_all($r, file_get_contents(ABSPATH . "wp-config.php"), $m);
            $params = array_combine($m[1], $m[2]);
            foreach($db_params as $k=>$p) {
                $db_params[$k] = $params[$p];
            }
            return $db_params;
        }


        private function init(array $conf) {
            //todo: нормализация
            $this->id = $conf['id'];
            $this->stime = $conf['stime'];
            $this->queue = new WPAdm_Queue($this->id);
            $this->type = $conf['type'];
        }
    }
}