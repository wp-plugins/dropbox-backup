<?php
require_once dirname(__FILE__) . '/pclzip.lib.php';
if (!class_exists('WPAdm_Archive')) {
    class WPAdm_Archive {
        private $remove_path = '';
        private $files = array();
        /**
         * @var PclZip
         */
        private $archive;
        private $md5_file = '';
    
        public function __construct($file, $md5_file = '') {
            $this->archive = new PclZip($file);
            $this->files[] = $file;
            $this->md5_file = $md5_file;
        }
    
        public function add($file) {
            if (empty($this->remove_path)) {
                $this->archive->add($file);
            } else {
                $this->archive->add($file, PCLZIP_OPT_REMOVE_PATH, $this->remove_path);
            }
            $this->saveMd5($file);
        }
        
        protected function saveMd5($file) {
            if ($this->md5_file) {
                $files = explode(',', $file); {
                    foreach($files as $f) {
                        file_put_contents($this->md5_file, $f . "\t" . md5_file($f) . "\t" . basename($this->archive->zipname) . "\n", FILE_APPEND);
                    }
                }
            }
        }
    
        public function setRemovePath($remove_path) {
            $this->remove_path = $remove_path;
        }
    }
}