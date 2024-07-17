<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.standard

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT']."/SmartDoc4/testFolder");

$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);
    $_tokens = $tokens;
    reset($tokens);

    $arrNameList = [];

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_VARIABLE") {
            $arrName = preg_replace('/["\']/', '', $token[1]);

            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
            if($token[0] == "DRALL_STRUCT" && $token[1] == "=") {

                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                if($token[0] == "DRALL_STRUCT" && $token[1] == "[" || $token[0] == "T_ARRAY") {
                    $arrNameList[$arrName] = $token[2] != null ? $token[2] : rand(0, 9);
                }
            }
        }
    }

    while($_token = $PHPParser->_next($_tokens)) {
        if($_token[0] == "T_STRING" && strtolower($_token[1]) == "implode") {
            $_token = $PHPParser->_next($_tokens, ['T_WHITESPACE', 'DRALL_STRUCT']);
            if($_token[0] == "T_VARIABLE") {
                $arrVerification = $arrNameList[$_token[1]];

                if($arrVerification) {
                    $to_change[] = $file.":".$_token[2].":".$_token[1].":must_change_the_arguments_order";
                }
            }
        }
    }
}

var_dump($to_change);

//Actions must to be ordered by affected file line
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change."\n";
    list($file,$file_line,$variable_name,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "must_change_the_arguments_order":
                        $explodedLine = explode("$", $tmp_line);

                        $baseIndex = (count($explodedLine) == 3) ? 1 : 2;
                        $elements = preg_replace('/[^a-zA-Z_]/', '', $explodedLine[$baseIndex]);
                        $delimiter = preg_replace('/[^a-zA-Z_]/', '', $explodedLine[$baseIndex + 1]);

                        if (count($explodedLine) == 3) {
                            $line = $explodedLine[0] . "$" . $delimiter . ", $" . $elements . ");\n";
                        } else {
                            $line = "$" . $explodedLine[1] . "$" . $delimiter . ", $" . $elements . ");\n";
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