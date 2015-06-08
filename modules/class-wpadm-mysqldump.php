<?php
if (!class_exists('WPAdm_Mysqldump')) {
    class WPAdm_Mysqldump {

        public $charset;
        public $collate;

        public $host = '';
        public $user = '';
        public $password = '';
        public $dbh ;

        private function connect($db = '') {
            WPAdm_Core::log("----------------------------------------------------");
            WPAdm_Core::log( langWPADM::get('Connecting to MySQL...' , false) );
            if (! class_exists('wpdb')) {
                require_once ABSPATH . '/' . WPINC . '/wp-db.php';
            }
            $this->dbh = new wpdb( $this->user, $this->password, $db, $this->host );
            $errors = $this->dbh->last_error;
            if ($errors) {
                $this->setError( langWPADM::get('MySQL Connect failed: ' , false) . $errors);
            }
            return $this->dbh;
        }

        public function optimize($db) {
            $link = $this->connect($db);
            WPAdm_Core::log( langWPADM::get('Optimize Database Tables was started' , false) );
            $n = $link->query('SHOW TABLES');
            $result = $link->last_result;
            if (!empty( $link->last_error ) && $n > 0) {
                $this->setError($link->last_error);
            } else {
                for($i = 0; $i < $n; $i++ ) {
                    $res = array_values( get_object_vars( $result[$i] ) );
                    $link->query('OPTIMIZE TABLE '. $res[0]);
                    if (!empty( $link->last_error ) ) {
                        $log = str_replace('%s', $res[0], langWPADM::get('Error to Optimize Table `%s`' , false) );
                        WPAdm_Core::log($log);
                    } else {
                        $log = str_replace('%s', $res[0], langWPADM::get('Optimize Table `%s` was successfully' , false) );
                        WPAdm_Core::log($log);
                    }
                }
                WPAdm_Core::log( langWPADM::get('Optimize Database Tables was Finished' , false) );
            }

        }

        public function mysqldump($db, $filename) {
            $link = $this->connect($db);
            WPAdm_Core::log( langWPADM::get('MySQL of Dump was started' , false) );
            $tables = array();
            $n = $link->query('SHOW TABLES');
            $result = $link->last_result;
            if (!empty( $link->last_error ) && $n > 0) {
                $this->setError($link->last_error);
            } 
            for($i = 0; $i < $n; $i++ ) {
                $row = array_values( get_object_vars( $result[$i] ) );
                $tables[] = $row[0];
            }

            $return = '';
            foreach($tables as $table)
            {
                $log = str_replace('%s', $table, langWPADM::get('Add a table "%s" in the database dump' , false) );
                WPAdm_Core::log( $log );
                $num_fields = $link->query('SELECT * FROM ' . $table);
                $result = $link->last_result;
                if (!empty( $link->last_error ) && $n > 0) {
                    $this->setError($link->last_error);
                }
                $return.= 'DROP TABLE ' . $table.';';

                $ress = $link->query('SHOW CREATE TABLE ' . $table);
                $result2 = $link->last_result;
                if (!empty( $link->last_error ) && $n > 0) {
                    $this->setError($link->last_error);
                }
                $row2 = array_values( get_object_vars( $result2[0]  ) );
                $return.= "\n\n".$row2[1].";\n\n";
                if ($num_fields > 0) {
                    for ($i = 0; $i < $num_fields; $i++)
                    {
                        $row = array_values( get_object_vars( $result[$i] ) );
                        //WPAdm_Core::log('row' . print_r($row, 1));
                        $rows_num = count($row);
                        if ($rows_num > 0) {
                            $return.= 'INSERT INTO '.$table.' VALUES(';
                            for($j=0; $j < $rows_num; $j++)
                            {
                                $row[$j] = addslashes($row[$j]);
                                $row[$j] = str_replace("\n","\\n",$row[$j]);
                                if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                                if ($j<($rows_num-1)) { $return.= ','; }
                            }
                            $return.= ");\n";
                        }

                    }
                }
                $return.="\n\n\n";
            }
            unset($link);
            $handle = fopen($filename,'w+');
            fwrite($handle,$return);
            fclose($handle);
            WPAdm_Core::log( langWPADM::get('MySQL of Dump was finished' , false) ); 
            return true;
        }

        private function setError($txt)
        {
            throw new Exception($txt);
        }

        public function restore($db, $file)
        {
            $link = $this->connect($db);
            WPAdm_Core::log( langWPADM::get('Restore Database was started' , false) );
            $fo = fopen($file, "r");
            if (!$fo) {
                WPAdm_Core::log( langWPADM::get('Error in open file dump' , false) );
                $this->setError( langWPADM::get('Error in open file dump' , false) );
                return false;
            }
            $sql = "";
            while(false !== ($char = fgetc($fo))) {
                $sql .= $char;
                if ($char == ";") {
                    $char_new = fgetc($fo);
                    if ($char_new !== false && $char_new != "\n") {
                        $sql .= $char_new;
                    } else {
                        $ress = $link->query($sql);
                        if (!empty( $link->last_error ) && $n > 0) {
                            $this->setError($link->last_error);
                            WPAdm_Core::log(langWPADM::get('MySQL Error: ' , false) . $link->last_error);
                            break;
                        };
                        $sql = "";
                    }
                }
            }
            WPAdm_Core::log(langWPADM::get('Restore Database was finished' , false));  
        }
    }
}

