<?php
if (!class_exists('WPadm_Command_Send_To_Dropbox')) {

    class WPadm_Command_Send_To_Dropbox extends WPAdm_Сommand {

        public function execute(WPAdm_Command_Context $context)
        {
            @session_start();
            require_once WPAdm_Core::getPluginDir() . '/modules/dropbox.class.php';

            WPAdm_Core::log('Send to drop box files');
            $dropbox = new dropbox($context->get('key'), $context->get('secret'), $context->get('token'));

            //$token = $dropbox->getAccessToken($_SESSION['request_token']);
            //WPAdm_Core::log('Token: ' . print_r($context->get('token'), true));

            if (!$dropbox->isAuth()) {
                $context->setError("Error auth in Dropbox");
                return false;
            }
            $files = $context->get('files');
            
            $file = explode("/", $files);
            $file_name = array_pop($file);
            $folder_project_temp = $context->get('folder_project');
            $folder_project = "";
            if (!empty($folder_project_temp)) {
                $folder_project = $folder_project_temp . "/";
                $dropbox->createDir($folder_project_temp );
                $dropbox->createDir($folder_project . $context->get('folder') ); 
            } else {
                $dropbox->createDir($context->get('folder') );
            }

            $fromFile = str_replace('//', '/', $files);
            $toFile = str_replace('//', '/', $folder_project . $context->get('folder') . '/' . $file_name);
            $res = $dropbox->uploadFile($fromFile, $toFile);
            if (isset($res['error']) && isset($res['text']) && $res['error'] == 1) {
                $context->setError("Dropbox error: " . $res['text']);
                return false;
            }
            if (isset($res['size']) && isset($res['client_mtime'])) {
                WPAdm_Core::log("file upload: " . $files . " size: " . $res['size']);
            }
            return true;
        }
    }
}