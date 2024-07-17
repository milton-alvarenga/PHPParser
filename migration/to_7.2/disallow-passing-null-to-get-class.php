<?php

#https://www.php.net/manual/en/migration72.incompatible.php#migration72.incompatible.no-null-to-get_class

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");

$to_change = [];
$nullVariablesList = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_VARIABLE") {
            $_token = $token;
            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
            if($token[0] == "DRALL_STRUCT" && $token[1] == "=") {

                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                if($token[0] == "T_STRING" && $token[1] == "null") {
                    $nullVariablesList[$_token[1]] = "null_variable_on:".$file.":".$token[2];
                }
            }
        }

        if($token[0] == "T_STRING" && $token[1] == "get_class") {

            $token = $PHPParser->_next($tokens);
            if($token[0] == "DRALL_STRUCT" && $token[1] == "("){

                $token = $PHPParser->_next($tokens);
                if($token[0] == "T_STRING" && $token[1] == "null") {
                    $to_change[] = $file.":".$token[2].":".$token[1].":passing_null_to_get_class";
                } else if($token[0] == "T_VARIABLE") {
                    $nullVar = $nullVariablesList[$token[1]];

                    if($nullVar) {
                        $to_change[] = $file.":".$token[2].":".$token[1].":passing_null_variable_to_get_class";
                    }
                }
            }
        }
    }
}

//Actions must be ordered by affected file line
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change."\n";
    list($file,$file_line,$argument,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "passing_null_to_get_class":
                        $term = $argument;
                        $pos = strpos($line,$term);
                        if ($pos !== false) {
                            $line = substr_replace($line,"",$pos,strlen($term));
                        }
                        if($tmp_line == $line){
                            print "Not changed.\n";
                        } else {
                            print "Changed\n";
                        }
                    break;

                    case "passing_null_variable_to_get_class":
                        $term = $argument;
                        $pos = strpos($line,$term);
                        $newGetClassLine = preg_replace('/get_class\(\$[a-zA-Z_]\w*\)/', 'get_class()', $line);

                        $newCodeBlock = "
                            if(is_null($argument)) { \n
                                $newGetClassLine \n
                            } else { \n
                                $line \n
                            }
                        ";

                        if ($pos !== false) {
                            $line = str_replace($line,$newCodeBlock,$line);
                        }

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
    } else {
        // error opening the file.
        throw new Exception("Could not open file ".$file."\n");
    }

    print "Ending ".$change."\n";
}
