<?php
/**
 * Delete backup 
 * Class WPAdm_Method_Backup_Delete
 */
if (!class_exists('WPAdm_Method_Backup_Delete')) {
    class WPAdm_Method_Backup_Delete extends WPAdm_Method_Class {
        public function getResult()
        {
            $backups_dir = realpath(ABSPATH . '/wpadm_backups/' . $this->params['name']);
            if(strpos($backups_dir,  DIRECTORY_SEPARATOR . 'wpadm_backups' . DIRECTORY_SEPARATOR) === false || !is_dir($backups_dir)) {
                $this->result->setResult = WPAdm_result::WPADM_RESULT_ERROR;
                $this->result->setError('Wrong name backup');
            } else {
                if (is_dir($backups_dir)) {
                    WPAdm_Core::rmdir($backups_dir);
                    if (!is_dir($backups_dir)) {
                        $this->result->setResult = WPAdm_result::WPADM_RESULT_SUCCESS;
                    } else {
                        $this->result->setResult = WPAdm_result::WPADM_RESULT_ERROR;
                        $this->result->setError('Failed to remove backup');        
                    }
                }
            }
            return $this->result;
        }
    }
}