<?php

#https://www.php.net/manual/en/migration73.incompatible.php#migration73.incompatible.core.continue-targeting-switch

include '../../lib/PHPParser.class.php';

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT']."/SmartDoc4/testFolder");

$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if(
            $token[0] == "T_WHILE" ||
            $token[0] == "T_DO" ||
            $token[0] == "T_FOREACH" ||
            $token[0] == "T_FOR"
        ) {
            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

            while($token[0] !== "T_SWITCH"){
                $token = $PHPParser->_next($tokens);
            }

            if($token[0] == 'T_SWITCH') {
                while($token[0] !== "T_CONTINUE"){
                    $token = $PHPParser->_next($tokens);
                }

                if($token[0] == 'T_CONTINUE') {
                    $_token = $token;

                    $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

                    if($token[0] == 'T_LNUMBER') continue;

                    $to_change[] = $file.":".$_token[2].":".$_token[1].":change_continue_to_break";
                }
            }
        }
    }
}
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change."\n";
    list($file,$file_line,$case_name,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "change_continue_to_break":
                        //Replace first occurrence of class name to __constructor
                        $term = $case_name;
                        $pos = strpos($line,$term);
                        if ($pos !== false) {
                            $line = substr_replace($line,"break",$pos,strlen($term));
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
