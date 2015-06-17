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
            WPAdm_Core::log("Connecting to MySQL...");
            if ($db) {
                $link = mysqli_connect($this->host, $this->user, $this->password, $db);
            } else {
                $link = mysqli_connect($this->host, $this->user, $this->password);
            }
            if (mysqli_connect_errno()) {
                $this->setError('MySQL Connect failed: ' . mysqli_connect_error());
            }
            $this->dbh = $link;
            $this->init_charset($link);
            $this->set_charset($link);
            return $link;

        }

        public function set_charset( $link, $charset = null, $collate = null ) {
            if ( ! isset( $charset ) )
                $charset = $this->charset;
            if ( ! isset( $collate ) )
                $collate = $this->collate;
            WPAdm_Core::log("MySQL set Charset $charset");
            if (! empty( $charset ) ) {
                if ( function_exists( 'mysqli_set_charset' )) {
                    mysqli_set_charset( $link, $charset );
                } else {
                    $query = $this->prepare( 'SET NAMES %s', $charset );
                    if ( ! empty( $collate ) )
                        $query .= $this->prepare( ' COLLATE %s', $collate );
                    mysqli_query( $link, $query );
                }

            }
        }
        public function init_charset($link) 
        {
            if ( function_exists('is_multisite') && is_multisite() ) {
                $this->charset = 'utf8';
                if ( defined( 'DB_COLLATE' ) && DB_COLLATE ) {
                    $this->collate = DB_COLLATE;
                } else {
                    $this->collate = 'utf8_general_ci';
                }
            } elseif ( defined( 'DB_COLLATE' ) ) {
                $this->collate = DB_COLLATE;
            }

            if ( defined( 'DB_CHARSET' ) ) {
                $this->charset = DB_CHARSET;
            }

            if ( ( ! ( $link instanceof mysqli ) )
            || ( empty( $link ) || ! ( $link instanceof mysqli ) ) ) {
                return;
            }

            if ( 'utf8' === $this->charset && $this->has_cap( 'utf8mb4' ) ) {
                $this->charset = 'utf8mb4';
            }

            if ( 'utf8mb4' === $this->charset && ( ! $this->collate || stripos( $this->collate, 'utf8_' ) === 0 ) ) {
                $this->collate = 'utf8mb4_unicode_ci';
            }
        }
        public function has_cap( $db_cap ) {
            $version = $this->db_version();

            switch ( strtolower( $db_cap ) ) {
                case 'collation' :    // @since 2.5.0
                case 'group_concat' : // @since 2.7.0
                case 'subqueries' :   // @since 2.7.0
                    return version_compare( $version, '4.1', '>=' );
                case 'set_charset' :
                    return version_compare( $version, '5.0.7', '>=' );
                case 'utf8mb4' :      // @since 4.1.0
                    if ( version_compare( $version, '5.5.3', '<' ) ) {
                        return false;
                    }
                    $client_version = mysqli_get_client_info();
                    if ( false !== strpos( $client_version, 'mysqlnd' ) ) {
                        $client_version = preg_replace( '/^\D+([\d.]+).*/', '$1', $client_version );
                        return version_compare( $client_version, '5.0.9', '>=' );
                    } else {
                        return version_compare( $client_version, '5.5.3', '>=' );
                    }
            }

            return false;
        }    
        public function db_version() {

            $server_info = mysqli_get_server_info( $this->dbh );

            return preg_replace( '/[^0-9.].*/', '', $server_info );
        }
        public function prepare( $query, $args ) {
            if ( is_null( $query ) )
                return;

            // This is not meant to be foolproof -- but it will catch obviously incorrect usage.
            if ( strpos( $query, '%' ) === false ) {
                _doing_it_wrong( 'wpdb::prepare', sprintf( __( 'The query argument of %s must have a placeholder.' ), 'wpdb::prepare()' ), '3.9' );
            }

            $args = func_get_args();
            array_shift( $args );
            // If args were passed as an array (as in vsprintf), move them up
            if ( isset( $args[0] ) && is_array($args[0]) )
                $args = $args[0];
            $query = str_replace( "'%s'", '%s', $query ); // in case someone mistakenly already singlequoted it
            $query = str_replace( '"%s"', '%s', $query ); // doublequote unquoting
            $query = preg_replace( '|(?<!%)%f|' , '%F', $query ); // Force floats to be locale unaware
            $query = preg_replace( '|(?<!%)%s|', "'%s'", $query ); // quote the strings, avoiding escaped strings like %%s
            array_walk( $args, array( $this, 'escape_by_ref' ) );
            return @vsprintf( $query, $args );
        }

        public function optimize($db) {
            $link = $this->connect($db);
            WPAdm_Core::log("Optimize Database Tables was started");
            if (!$result = mysqli_query($link, 'SHOW TABLES')) {
                $this->setError(mysqli_error($link));
            };
            while($row = mysqli_fetch_row($result))
            {
                if (!mysqli_query($link, 'OPTIMIZE TABLE '.$row[0])) {
                    $this->setError(mysqli_error($link));
                };
            }
            WPAdm_Core::log("Optimize Database Tables was Finished");

        }

        public function mysqldump($db, $filename) {
            $link = $this->connect($db);
            WPAdm_Core::log("MySQL of Dump was started");
            $tables = array();
            if (!$result = mysqli_query($link, 'SHOW TABLES')) {
                $this->setError(mysqli_error($link));
            };
            while($row = mysqli_fetch_row($result))
            {
                $tables[] = $row[0];
            }

            //cycle through

            $return = '';
            $charset = mysqli_get_charset($link);
            if (isset($charset->charset)) {
                $return .= "SET NAMES '{$charset->charset}';\n\n";
                WPAdm_Core::log("SET NAMES Database {$charset->charset};");
            }
            foreach($tables as $table)
            {
                WPAdm_Core::log("Add a table {$table} in the database dump");
                mysqli_close($link);
                $link = $this->connect($db);
                if (!$result = mysqli_query($link, 'SELECT * FROM ' . $table)) {
                    $this->setError(mysqli_error($link));
                };
                $num_fields = mysqli_num_fields($result);

                $return.= 'DROP TABLE '.$table.';';
                if (!$ress = mysqli_query($link, 'SHOW CREATE TABLE ' . $table)) {
                    $this->setError(mysqli_error($link));
                };

                $row2 = mysqli_fetch_row($ress);
                $return.= "\n\n".$row2[1].";\n\n";

                for ($i = 0; $i < $num_fields; $i++)
                {
                    while($row = mysqli_fetch_row($result))
                    {
                        $return.= 'INSERT INTO '.$table.' VALUES(';
                        for($j=0; $j<$num_fields; $j++)
                        {
                            //$row[$j] = mb_convert_encoding($row[$j], 'UTF-8', 'auto');
                            $row[$j] = addslashes($row[$j]);
                            $row[$j] = str_replace("\n","\\n",$row[$j]);
                            if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                            if ($j<($num_fields-1)) { $return.= ','; }
                        }
                        $return.= ");\n";
                    }
                }
                $return.="\n\n\n";
            }

            mysqli_close($link);
            $handle = fopen($filename,'w+');
            fwrite($handle,$return);
            fclose($handle);
            WPAdm_Core::log("MySQL of Dump was finished"); 
            return true;
        }

        private function setError($txt)
        {
            //WPAdm_Core::log($txt);
            throw new Exception($txt);
        }

        public function restore($db, $file)
        {
            $link = $this->connect($db);
            WPAdm_Core::log("Restore Database was started");
            $fo = fopen($file, "r");
            if (!$fo) {
                WPAdm_Core::log("Error in open file dump");
                $this->setError("Error in open file dump");
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
                        $ress = mysqli_query($link, $sql);
                        if (!$ress) {
                            $this->setError(mysqli_error($link));
                            WPAdm_Core::log("MySQL Error: " . mysqli_error($link));
                            break;
                        };
                        $sql = "";
                    }
                }
            }
            WPAdm_Core::log("Restore Database was finished");  
        }
    }
}

