<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.sysvsem

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files(__DIR__."/../../SmartDoc4/");

$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == 'T_STRING' && strtolower($token[1]) == 'sem_get') {

            $to_search = strtolower($token[1]);
            $to_replace = strtolower($token[1]);
            $is_int_parameter = false;

            while($token = $PHPParser->_next($tokens)){
                if( $token[0] != 'T_WHITESPACE' ){
                    break;
                }
                $to_search .= $token[1];
                $to_replace .= $token[1];
            }

            if($token[0] == 'DRALL_STRUCT' && $token[1] == '(') {
                $commaCounter = 0;

                $to_search .= $token[1];
                $to_replace .= $token[1];

                while($token = $PHPParser->_next($tokens)){
                    if( $token[0] != 'T_WHITESPACE' ){
                        break;
                    }
                    $to_search .= $token[1];
                    $to_replace .= $token[1];
                }

                do {
                    if( $token[0] == 'T_WHITESPACE' ){
                        $to_search .= $token[1];
                        $to_replace .= $token[1];
                        continue;
                    }

                    $to_search .= $token[1];

                    if($commaCounter == 3 && $token[0] == 'T_LNUMBER') {
                        if(!is_numeric($token[1])){
                            break;
                        }
                        $is_int_parameter = true;
                        if($token[1] == 0){
                            $to_replace .= "false";
                        } else {
                            $to_replace .= "true";
                        }
                    }

                    if($token[0] == 'DRALL_STRUCT' && $token[1] == ')') {
                        $to_replace .= $token[1];
                        break;
                    }

                    if($token[0] == "DRALL_STRUCT" && $token[1] == ",") {
                        $commaCounter++;
                    }
                } while($token = $PHPParser->_next($tokens));

                if($commaCounter == 3 && $is_int_parameter) {
                    $to_change[] = $file.":".$token[2].":".$to_search.":".$to_replace.":change_integer_operator_to_boolean";
                }
            }
        }
    }
}
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change.'<br>';
    list($file,$file_line,$text_search,$text_replace,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."<br>";

                switch($action){
                    case "change_integer_operator_to_boolean":
                        $newLine = str_replace($text_search, $text_replace, $tmp_line);

                        $line = $newLine;

                        if($tmp_line == $line){
                            print "Not changed.\n";
                        } else {
                            print "Changed\n";
                        }

                        break;
                    default:
                        print "No action executed! It is wrong.\n";
                }
            }
            // process the line read.
            fputs($writing, $line);
            $read_line++;
        }

        fclose($handle);
        fclose($writing);

        rename($file.'.tmp', $file);
		chown($file,1000);
        chgrp($file,1000);
    } else {
        // error opening the file.
        throw new Exception("Could not open file ".$file."<br>");
    }

    print "Ending ".$change."<br>";
}
