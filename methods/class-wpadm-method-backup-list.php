<?php
/**
 * Return a list of backups 
 * Class WPAdm_Method_Exec
 */
if (!class_exists('WPAdm_Method_Backup_List')) {
    class WPAdm_Method_Backup_List extends WPAdm_Method_Class {
        public function getResult()
        {
            $backups_dir = ABSPATH . '/wpadm_backups/';
            $dirs = glob($backups_dir . '*');
    
            $backups = array();
            foreach($dirs as $dir) {
                if (preg_match("|(.*)\-(.*)\-(.*)|", $dir, $mm)) {
                    $tmp = explode('/', $dir);
                    $name = array_pop($tmp);
                    list($y,$m,$d, $h,$i) = explode('_', $mm[3]);
                    $dt = "$y-$m-$d $h:$i";
                    $backup = array(
                        'name' => $name,
                        'type' => $mm[2],
                        'dt' => $dt,
                    );
                    $files = glob($dir . '/*.zip');
                    $size = 0;
                    foreach($files as $k=>$v) {
                        $size += (int)filesize($v);
                        $files[$k] = str_replace(ABSPATH, '', $v);
                    }
                    $backup['files'] = $files;
                    $backup['size'] = $size;
                    if ($size > 0) {
                        $backups[] = $backup;
                    }
    
                }
            }
            $this->result->setData($backups);
            $this->result->setResult(WPAdm_result::WPADM_RESULT_SUCCESS);
            return $this->result;
        }
    
    }
}