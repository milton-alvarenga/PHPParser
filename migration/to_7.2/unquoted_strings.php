<?php

#https://www.php.net/manual/en/migration72.deprecated.php#migration72.deprecated.unquoted-strings

include '../../lib/PHPParser.class.php';

$PHPParser = new PHPParser();

$files = $PHPParser->get_files(__DIR__."/../../SmartDoc4/");
$globalConstantsList = [];
$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_STRING") {
            if($token[1] == "define") {
                $token = $PHPParser->_next($tokens, ['T_WHITESPACE', 'DRALL_STRUCT']);

                if($token[0] == "T_CONSTANT_ENCAPSED_STRING") {
                    $constantWithOutQuotes = preg_replace('/["\']/', '', $token[1]);
                    $globalConstantsList[$constantWithOutQuotes] = $file.":".$token[2];
                }
            }

            if(!preg_match('/["\']/', $token[1])) {
                $globalConstantInUse = $globalConstantsList[$token[1]];

                if(!$globalConstantInUse) {
                    $to_change[] = $file.":".$token[2].":".$token[1].":unquoted_string";
                }
            }

        }
    }
}

sort($to_change);



while($change = array_shift($to_change)){
    print "Starting ".$change."\n";
    list($file,$file_line,$constantName,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "unquoted_string":
                        $term = $constantName;
                        print_r($term);
                        $pos = strpos($line,$term);
                        if ($pos !== false) {
                            $line = substr_replace($line,'"'.$term.'"',$pos,strlen($term));
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
        chown($file,1000);
        chgrp($file,1000);
    } else {
        // error opening the file.
        throw new Exception("Could not open file ".$file."\n");
    }

    print "Ending ".$change."\n";
}