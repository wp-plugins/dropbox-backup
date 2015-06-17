<?php

if (!class_exists('WPAdm_Method_Test')) {
    class WPAdm_Method_Test extends WPAdm_Method_Class {
        public function getResult()
        {
    //        $this->result->setResult(WPAdm_Result::WPADM_RESULT_SUCCESS);
    //        $this->result->setData($guid);
            if(isset($this->params['test'])) {
                if ($this->params['test'] == 'keys') {
                    $this->testKeys($this->params);
                }
            }
    
            return $this->result;
        }
    
        private function testKeys($params) {
            $str = 'test string';
    
            if (!function_exists('openssl_public_decrypt')) {
                // зашифруем строку
                openssl_private_encrypt($str, $sign, $params['private']);
                // проверим подпись
                openssl_public_decrypt($sign, $str2, $params['public']);
                $ret = ($str == $str2);
            } else {
                set_include_path(get_include_path() . PATH_SEPARATOR . WPAdm_Core::getPluginDir() . '/modules/phpseclib');
                require_once 'Crypt/RSA.php';
                // зашифруем строку
                define('CRYPT_RSA_PKCS15_COMPAT', true);
                $rsa = new Crypt_RSA();
                $rsa->loadKey($params['private']);
                $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
                $ciphertext = $rsa->encrypt($str);
    
                // проверим подпись
                $rsa = new Crypt_RSA();
                $rsa->loadKey($params['public']);
                $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
                $ret = ($str == $rsa->decrypt($ciphertext));
            }
            $this->result->setResult(WPAdm_result::WPADM_RESULT_SUCCESS);
            $this->result->setData(
                array(
                    'match' => (int)$ret
                )
            );
    
        }
    }
}