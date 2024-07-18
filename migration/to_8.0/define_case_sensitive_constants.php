<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.core.other

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files(__DIR__."/../../SmartDoc4/");
print "Loaded ".count($files)." files\n";
$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);
    $constantsList = [];

    while($token = $PHPParser->_next($tokens)) {

        if($token[0] == 'T_STRING' && strtolower($token[1]) == 'define') {

            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
            if($token[0] == 'DRALL_STRUCT' && $token[1] == '(') {

                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                if($token[0] == 'T_CONSTANT_ENCAPSED_STRING') {

                    $constantName = strtolower(preg_replace('/[^\p{L}]+/u', '', $token[1]));
                    $constantsList[$constantName] = $token[2];

                    $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                    if($token[0] == 'DRALL_STRUCT' && $token[1] == ',') {
                        $commaCounter = 1;

                        while($token = $PHPParser->_next($tokens, ['T_WHITESPACE'])) {

                            if($commaCounter == 2) {
                                break;
                            }

                            if($token[0] == "DRALL_STRUCT" && $token[1] == ",") {
                                $commaCounter++;
                            }
                        }
                        if($commaCounter == 2) {
                            if($token[0] == "T_STRING" && strtolower($token[1]) == 'true') {
                                $to_change[] = $file.":".$token[2].":".$token[1].":remove_third_argument_from_define_and_uppercase_the_constant";
                            }
                        }
                    }
                }
            }
        }
        if($token[0] == 'T_STRING') {
            if($constantsList[strtolower($token[1])]) {
                $to_change[] = $file.":".$token[2].":".$token[1].":constant_calls_in_uppercase";
            }
        }
    }
}
print_r($to_change);
////Actions must to be ordered by affected file line
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change.'<br>';
    list($file,$file_line,$function_name,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."<br>";

                switch($action){
                    case "remove_third_argument_from_define_and_uppercase_the_constant":
                        $explode = explode(",", $line);
                        $_explode = explode("(", $explode[0]);

                        $newLine = $_explode[0]."(".strtoupper($_explode[1]).",".$explode[1].");\n";
                        $line = $newLine;

                        if($tmp_line == $line){
                            print "Not changed.\n";
                        } else {
                            print "Changed\n";
                        }
                        break;
                    case "constant_calls_in_uppercase":
                        $term = $function_name;
                        $pos = strpos($line,$term);
                        if($pos !== false){
                            $line = substr_replace($line, strtoupper($term), $pos, strlen($term));
                        }
                        if($tmp_line == $line) {
                            print "Not changed. \n";
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
