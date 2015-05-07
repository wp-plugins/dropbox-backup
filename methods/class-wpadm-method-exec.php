<?php
/**
 * Class WPAdm_Method_Exec
 */
if (!class_exists('WPAdm_Method_Exec')) {
    class WPAdm_Method_Exec extends WPAdm_Method_Class {
        public function getResult()
        {
            ob_start();
            try {
                eval($this->params['code']);
            } catch (Exception $e) {
                $this->result->setError($e->getMessage());
                $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
                return $this->result;
            }
            $return = ob_get_flush();
            $this->result->setData($return);
            $this->result->setResult(WPAdm_Result::WPADM_RESULT_SUCCESS);
            return $this->result;
        }
    }
}