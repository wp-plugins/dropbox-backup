<?php

if (!class_exists('WPAdm_Method_Local_Backup')) {
    class WPAdm_Method_Local_Backup extends WPAdm_Method_Class {

        private $start = true;

        public function __construct($params)
        {
            parent::__construct($params);
            $this->init(
            array(
            'id' => uniqid('wpadm_method_backup__'),
            'stime' => time(),
            )
            );
            $file_log = WPAdm_Core::getTmpDir() . "/log.log";
            if (file_exists($file_log)) {
                unlink($file_log);
            }
            WPAdm_Core::log('Create Unique Id '. $this->id);


            $name = get_option('siteurl');

            $name = str_replace("http://", '', $name);
            $name = str_replace("https://", '', $name);
            $name = preg_replace("|\W|", "_", $name);
            $this->time = date("d.m.Y H:i");   //23.04.2015 13:45
            $name .= '-' . wpadm_class::$type . '-' . date("Y_m_d_H_i");
            $this->name = $name;


            // folder for backup
            $this->dir = ABSPATH . 'wpadm_backups/' . $this->name;
            if (($f = $this->checkBackup()) !== false) {
                $this->dir = ABSPATH . 'wpadm_backups/' . $f;
            }
            WPAdm_Core::mkdir(ABSPATH . 'wpadm_backups/');
            WPAdm_Core::mkdir($this->dir);
        }
        public function checkBackup()
        {
            $archives = glob("{$this->dir}");
            if (empty($archives) && count($archives) <= 1) {
                return false;
            }
            $n = count($archives);
            $f = "{$this->name}({$n})";
            return $f;
        }
        public function getResult()
        {
            if ($this->start === false) {
                $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
                $this->result->setError('Backup process was started, please, wait a few minutes...');
                return $this->result;
            }
            $errors = array();

            $this->result->setResult(WPAdm_Result::WPADM_RESULT_SUCCESS);
            $this->result->setError('');

            WPAdm_Core::log('Start Backup process...');

            # create db dump
            if (in_array('db', $this->params['types']) ) {
                WPAdm_Core::log('Creating Database Dump');
                WPAdm_Core::mkdir(ABSPATH . 'wpadm_backup');
                $mysql_dump_file = ABSPATH . 'wpadm_backup/mysqldump.sql';
                if (file_exists($mysql_dump_file)) {
                    unlink($mysql_dump_file);
                }
                $wp_mysql_params = $this->getWpMysqlParams();

                if (isset($this->params['optimize']) && ($this->params['optimize']==1)) {
                    WPAdm_Core::log('Optimize Database Tables');
                    $commandContext = new WPAdm_Command_Context();
                    $commandContext ->addParam('command','mysqloptimize')
                    ->addParam('host', $wp_mysql_params['host'])
                    ->addParam('db', $wp_mysql_params['db'])
                    ->addParam('user', $wp_mysql_params['user'])
                    ->addParam('password', $wp_mysql_params['password']);
                    $this->queue->clear()
                    ->add($commandContext);
                    unset($commandContext);
                }

                $commandContext = new WPAdm_Command_Context();
                $commandContext ->addParam('command','mysqldump')
                ->addParam('host', $wp_mysql_params['host'])
                ->addParam('db', $wp_mysql_params['db'])
                ->addParam('user', $wp_mysql_params['user'])
                ->addParam('password', $wp_mysql_params['password'])
                ->addParam('tables', '')
                ->addParam('to_file', $mysql_dump_file);
                $res = $this->queue->add($commandContext)
                ->save()
                ->execute();
                if (!$res) {
                    WPAdm_Core::log('Error: Dump of Database wasn\'t created('.$this->queue->getError().')');
                    $errors[] = 'MySQL Error: '.$this->queue->getError();
                } elseif (0 == (int)filesize($mysql_dump_file)) {
                    $errors[] = 'MySQL Error: Database-Dump File is empty';
                    WPAdm_Core::log('Dump of Database wasn\'t created (File of Database-Dump is empty!)');
                } else {
                    $size_dump = round( (filesize($mysql_dump_file) / 1024 / 1024) , 2);
                    WPAdm_Core::log('Database Dump was successfully created(' . $size_dump . 'Mb):' . $mysql_dump_file);
                }
                unset($commandContext);
            }


            if (in_array('files', $this->params['types']) ) {
                #ЗАРХИВИРУЕМ ФАЙЛЫ
                WPAdm_Core::log('Create a list of files for Backup');
                $files = $this->createListFilesForArchive();
            }
            if (isset($mysql_dump_file) && file_exists($mysql_dump_file) && filesize($mysql_dump_file) > 0) {
                $files[] = $mysql_dump_file;
            }

            if (empty($files)) {
                $errors[] = 'Error: the list of Backup files is empty';
            }

            // split the file list by 170kbayt lists, To break one big task into smaller
            $files2 = array();
            $files2[0] = array();
            $i = 0;
            $size = 0;
            foreach($files as $f) {
                if ($size > 170000) {//~170kbyte
                    $i ++;
                    $size = 0;
                    $files2[$i] = array();
                }
                $f_size =(int)@filesize($f);
                if ($f_size == 0 || $f_size > 1000000) {
                    WPAdm_Core::log('File '. $f .' Size ' . $f_size);
                }
                $size += $f_size;
                $files2[$i][] = $f;
            }

            WPAdm_Core::log('List of Backup-Files was successfully created');

            $this->queue->clear();

            foreach($files2 as $files) {
                $commandContext = new WPAdm_Command_Context();
                $commandContext ->addParam('command', 'archive')
                ->addParam('files', $files)
                ->addParam('to_file', $this->dir . '/'.$this->name)
                ->addParam('max_file_size', 900000)
                ->addParam('remove_path', ABSPATH);

                $this->queue->add($commandContext);
                unset($commandContext);
            }
            WPAdm_Core::log('Backup of Files was started');
            $this->queue->save()
            ->execute();
            WPAdm_Core::log('End of File Backup');

            $files = glob($this->dir . '/'.$this->name . '*');
            $urls = array();
            $totalSize = 0;
            foreach($files as $file) {
                $urls[] = str_replace(ABSPATH, '', $file);
                $totalSize += @intval( filesize($file) );
            }
            $this->result->setData($urls);
            $this->result->setSize($totalSize);
            $size = $totalSize / 1024 / 1024; /// MByte
            $size = round($size, 2);
            WPAdm_Core::log('Backup Size ' . $size . " Mb");

            $remove_from_server = 0;
            #Removing TMP-files
            WPAdm_Core::rmdir(ABSPATH . 'wpadm_backup');

            #Removind old backups(if limit the number of stored backups)
            if ($this->params['limit'] != 0) {
                WPAdm_Core::log('Limits of Backups ' . $this->params['limit']); 
                WPAdm_Core::log('Removing of old Backups was started');
                $files = glob(ABSPATH . 'wpadm_backups/*');
                if (count($files) > $this->params['limit']) {
                    $files2 = array();
                    foreach($files as $f) {
                        $fa = explode('-', $f);
                        if (count($fa) != 3) {
                            continue;
                        }
                        $files2[$fa[2]] = $f;

                    }
                    ksort($files2);
                    $d = count($files2) - $this->params['limit'];
                    $del = array_slice($files2, 0, $d);
                    foreach($del as $d) {
                        WPAdm_Core::rmdir($d);
                    }
                }
                WPAdm_Core::log('Removing of old Backups was Finished'); 
            }
            wpadm_class::setBackup(1);
            if (!empty($errors)) {
                $this->result->setError(implode("\n", $errors));
                $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
                wpadm_class::setStatus(0);
                wpadm_class::setErrors( implode(", ", $errors) );
            } else {
                wpadm_class::setStatus(1);
                WPAdm_Core::log('Backup creating is completed successfully!');
            }
            wpadm_class::backupSend();

            return $this->result;

        }
        public function createListFilesForArchive() {
            $folders = array();
            $files = array();

            $files = array_merge(
            $files,
            array(
            ABSPATH . '.htaccess',
            ABSPATH . 'index.php',
            ABSPATH . 'license.txt',
            ABSPATH . 'readme.html',
            ABSPATH . 'wp-activate.php',
            ABSPATH . 'wp-blog-header.php',
            ABSPATH . 'wp-comments-post.php',
            ABSPATH . 'wp-config.php',
            ABSPATH . 'wp-config-sample.php',
            ABSPATH . 'wp-cron.php',
            ABSPATH . 'wp-links-opml.php',
            ABSPATH . 'wp-load.php',
            ABSPATH . 'wp-login.php',
            ABSPATH . 'wp-mail.php',
            ABSPATH . 'wp-settings.php',
            ABSPATH . 'wp-signup.php',
            ABSPATH . 'wp-trackback.php',
            ABSPATH . 'xmlrpc.php',
            )
            );

            if (!empty($this->params['minus-path'])) {
                $minus_path = explode(",", $this->params['minus-path']);
                foreach($files as $k => $v) {
                    $v = str_replace(ABSPATH  , '',  $v);
                    if (in_array($v, $minus_path)) {
                        unset($files[$k]);
                        WPAdm_Core::log('Skip of File ' . $v);
                    }
                }
            }

            $folders = array_merge(
            $folders,
            array(
            ABSPATH . 'wp-admin',
            ABSPATH . 'wp-content',
            ABSPATH . 'wp-includes',
            )
            );
            if (!empty($this->params['plus-path'])) {
                $plus_path = explode(",", $this->params['plus-path']);
                foreach($plus_path as $p) {
                    if (empty($p)) {
                        continue;
                    }
                    $p = ABSPATH . $p;
                    if (file_exists($p)) {
                        if (is_dir($p)) {
                            $folders[] = $p;
                        } else{
                            $files[] = $p;
                        }
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

            $d = str_replace(ABSPATH, '', $directory);
            // Skip dirs 
            if (isset($this->params['minus-path'])) {
                $minus_path = explode(",", $this->params['minus-path']);
                if (in_array($d, $minus_path) ) {
                    WPAdm_Core::log('Skip of Folder ' . $directory);
                    return array();
                }
            } else {
                $minus_path = array();
            }

            $d = str_replace('\\', '/', $d);
            $tmp = explode('/', $d);
            $d1 = mb_strtolower($tmp[0]);
            unset($tmp[0]);
            $d2 = mb_strtolower(implode('/', $tmp));
            if (strpos($d2, 'cache') !== false && isset($tmp[0])&& !in_array($tmp[0], array('plugins', 'themes')) ) {
                WPAdm_Core::log('Skip of Cache-Folder ' . $directory);
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
                                $f = str_replace(ABSPATH, '', $ff);
                                // skip "minus" dirs
                                if (!in_array($f, $minus_path)) {
                                    $array_items[] = $ff;
                                } else {
                                    WPAdm_Core::log('Skip of File ' . $ff);
                                }
                            }
                        } else {
                            $file = $directory . "/" . $file;
                            if (!is_dir($file)) {
                                $ff = preg_replace("/\/\//si", "/", $file);
                                $f = str_replace(ABSPATH, '', $ff);
                                // skip "minus" dirs
                                if (!in_array($f, $minus_path)) {
                                    $array_items[] = $ff;
                                } else {
                                    WPAdm_Core::log('Skip of Folder ' . $ff);
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
        * returns the elements of access to MySQL from WP options
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
            $this->id = $conf['id'];
            $this->stime = $conf['stime'];
            $this->queue = new WPAdm_Queue($this->id);
        }
    }
}
