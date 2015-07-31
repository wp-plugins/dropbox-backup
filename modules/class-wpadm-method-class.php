<?php


if (!class_exists('WPAdm_Method_Class')) {
    abstract class WPAdm_Method_Class {
        /**
         * @var mixed
         */
        protected $params;
    
        /**
         * @var WPAdm_result
         */
        protected  $result;
    
        /**
         * @param mixed $params
         */
        public function __construct($params) {
            $this->params = $params;
            $this->result = new WPAdm_Result();
        }
    
        abstract function getResult();
    }
}