<?php

#https://www.php.net/manual/en/migration72.deprecated.php#migration72.deprecated.create_function-function

include "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT']."/SmartDoc4/testFolder");
$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($_token = $PHPParser->_next($tokens )) {
        if($_token[0] == "T_STRING" && $_token[1] == "create_function") {
            $token = $PHPParser->_next($tokens);
            if($token[0] == "DRALL_STRUCT" && $token[1] == "(") {
                $token = $PHPParser->_next($tokens);
                $params = $token[1];

                $token = $PHPParser->_next($tokens);
                if($token[0] == "DRALL_STRUCT" && $token[1] == ",") {
                    $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                    $function = $token[1];

                    $token = $PHPParser->_next($tokens);
                    if($token[0] == "DRALL_STRUCT" && $token[1] == ")") {
                        $to_change[] = $file.":".$_token[2].":".$params.":".$function.":change_to_anonymous_function";
                    }
                }
            }
        }
    }
}

//Actions must to be ordered by affected file line
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change."\n";
    list($file,$file_line,$params,$function,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "change_to_anonymous_function":
                        $term = $params;
                        $pos = strpos($line,$term);
                        $explode = explode("=", $line);
                        $mainVar = $explode[0];

                        $paramsWithoutQuotes = str_replace("'", "", $params);
                        $functionWithoutQuotes = str_replace("'", "", $function);

                        $newLine = $mainVar."= function(".$paramsWithoutQuotes.") {".$functionWithoutQuotes."}; \n";

                        if ($pos !== false) {
                            $line = str_replace($line,$newLine,$line);
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
             //process the line read.
            fputs($writing, $line);
            $read_line++;
        }

        fclose($handle);
        fclose($writing);

        rename($file.'.tmp', $file);
    } else {
         //error opening the file.
        throw new Exception("Could not open file ".$file."\n");
    }

    print "Ending ".$change."\n";
}