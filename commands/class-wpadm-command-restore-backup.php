<?php
if (!class_exists('WPadm_Command_Restore_Backup')) {
    class WPadm_Command_Restore_Backup extends WPAdm_Сommand {

        public function execute(WPAdm_Command_Context $context)
        {
            if( file_exists($context->get('zip_file')) ) {  
                require_once WPAdm_Core::getPluginDir() . '/modules/pclzip.lib.php';
                $this->archive = new PclZip($context->get('zip_file'));
                $file = $context->get('file');  
                $is_dump =  $file && strpos($file, "mysqldump.sql");
                
                WPAdm_Core::log( "Data decompression " . basename($context->get('zip_file'))  );
                if ($is_dump !== false)  {
                    $inzip = str_replace(ABSPATH, "", $file); 
                    $file_in_zip = $this->archive->extract(PCLZIP_OPT_BY_NAME, $inzip);
                } else {
                    $file_in_zip = $this->archive->extract(PCLZIP_OPT_REPLACE_NEWER);
                }
                
                if ($file_in_zip == 0) {
                    WPAdm_Core::log( langWPADM::get("Website \"%d\" returned an error during archive extracting: ", false, "%d", SITE_HOME) . $this->archive->errorInfo(true) );
                    $context->setError( langWPADM::get("Website \"%d\" returned an error during archive extracting: ", false, "%d", SITE_HOME) . $this->archive->errorInfo(true) );  
                    return false;
                }
                //WPAdm_Core::log(print_r($file_in_zip, 1));
                if ($is_dump !== false) {
                    $db_host = $context->get('db_host');
                    if ($db_host !== false) {
                        require_once WPAdm_Core::getPluginDir() . '/modules/class-wpadm-mysqldump.php';
                        $mysqldump = new WPAdm_Mysqldump();
                        $mysqldump->host = $context->get('db_host');
                        $mysqldump->user = $context->get('db_user');
                        $mysqldump->password = $context->get('db_password');
                        try {
                            $mysqldump->restore($context->get('db_name'), $file);
                        } catch (Exception $e) {
                            $context->setError($e->getMessage());
                            return false;
                        }
                    }
                } 
            } else {
                $context->setError( langWPADM::get("Website \"%d\" returned an error: The necessary file of archive \"%f\" wasn't found ", false, array('%d', '%f'), array(SITE_HOME, $context->get('zip_file') ) ) );
                WPAdm_Core::log( langWPADM::get("Website \"%d\" returned an error: The necessary file of archive \"%f\" wasn't found ", false, array('%d', '%f'), array(SITE_HOME, $context->get('zip_file') ) ) );
                return false;
            }
            return true;
        }
    }
}