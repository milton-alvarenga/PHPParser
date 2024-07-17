<?php

#https://www.php.net/manual/en/migration72.deprecated.php#migration72.deprecated.assert-string-arg

include '../../lib/PHPParser.class.php';

$PHPParser = new PHPParser();

$files = $PHPParser->get_files(__DIR__."/../../SmartDoc4/");

$to_change = [];
$stringVariables = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_VARIABLE") {
            $_token = $token;
            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
            if($token[0] == "DRALL_STRUCT" && $token[1] == "=") {

                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                if($token[0] == "T_CONSTANT_ENCAPSED_STRING") {
                    $stringVariables[$_token[1]] = $token[1];
                }
            }
        }

        if($token[0] == 'T_STRING' && $token[1] == 'assert') {

            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
            if($token[0] == 'DRALL_STRUCT' && $token[1] == '(') {

                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                if($token[0] == 'T_CONSTANT_ENCAPSED_STRING') {
                    $to_change[] = $file.":".$token[2].":".$token[1].":remove_quotation_marks";
                }

                if($token[0] == 'T_VARIABLE') {
                    $verifyVariable = $stringVariables[$token[1]];

                    if($verifyVariable) {
                        $to_change[] = $file.":".$token[2].":".$token[1].",".$verifyVariable.":remove_quotation_marks_from_variable";
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
    list($file,$file_line,$stringArg,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "remove_quotation_marks":
                        //Replace first occurrence of class name to __constructor
                        $term = $stringArg;
                        $pos = strpos($line,$term);
                        $stringWithOutQuotes = preg_replace('/[\'"]/', '', $term);
                        if ($pos !== false) {
                            $line = substr_replace($line,$stringWithOutQuotes,$pos,strlen($term));
                        }
                        if($tmp_line == $line){
                            print "Not changed.\n";
                        } else {
                            print "Changed\n";
                        }
                        break;

                    case "remove_quotation_marks_from_variable";
                        $explode = explode(",",$stringArg);
                        $mainVar = $explode[0];
                        $content = $explode[1];

                        $term = $mainVar;
                        $pos = strpos($line,$term);
                        $stringWithOutQuotes = preg_replace('/[\'"]/', '', $content);
                        if ($pos !== false) {
                            $line = substr_replace($line,$stringWithOutQuotes,$pos,strlen($term));
                        }
                        if($tmp_line == $line){
                            print "Not changed.\n";
                        } else {
                            print "Changed\n";
                        }
                        break;

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