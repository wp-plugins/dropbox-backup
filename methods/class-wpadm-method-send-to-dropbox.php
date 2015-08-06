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

            $ad = $this->params['access_details'];
            WPAdm_Core::log( langWPADM::get('Start copy to Dropbox Cloud' , false) );
            $this->queue->clear();
            $files = $this->params['files'];
            //$this->getResult()->setData($files);

            $dir = (isset($ad['dir'])) ? $ad['dir'] : '/';
            //$dir = trim($dir, '/') . '/' . $this->name;
            if (is_array($files)) {
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
            }
            $res = $this->queue->save()
                ->execute();
            if (!$res) {
                WPAdm_Core::log(langWPADM::get('Answer from Dropbox ' , false) . $this->queue->getError());
                $errors[] = langWPADM::get('Answer from Dropbox ' , false) . $this->queue->getError();
            }
            WPAdm_Core::log( langWPADM::get('End Copy Files to Dropbox' , false) );
            if (count($errors) > 0) {
                $this->result->setError(implode("\n", $errors));
                $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
            } 

            return $this->result;


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