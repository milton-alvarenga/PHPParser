<?php

#https://www.php.net/manual/en/migration72.deprecated.php#migration72.deprecated.read_exif_data-function

include "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");
$to_change = [];
$to_verify = [];
while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_STRING" && $token[1] == "set_error_handler") {
            $token = $PHPParser->_next($tokens);

            if($token[0] == "DRALL_STRUCT" && $token[1] == "(") {
                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                $commaCounter = 0;
                while($token = $PHPParser->_next($tokens, ['T_WHITESPACE'])) {
                    if($token[1] == ')') break;
                    if($commaCounter == 4) break;
                    if($token[1] == ",") $commaCounter++;
                }

                if($commaCounter == 4) {
                    if($token[0] == "T_VARIABLE" && stripos($token[1], 'errcontext') !== false) {
                        $to_change[] = $file.":".$token[2].":".$token[1].":remove_errcontext_var";
                    }
                }
            }
        }
        if($token[0] == "T_VARIABLE" && stripos($token[1], 'errcontext') !== false) {
            $to_verify[] = $file.":".$token[2].":".$token[1].":verify_errcontext";
        }
    }
}
print_r($to_change);
echo "<br>";
print_r($to_verify);
echo "<br>";
//Actions must to be ordered by affected file line
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change."\n";
    list($file,$file_line,$var_name,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "remove_errcontext_var":
                        //Replace first occurrence of class name to __constructor
                        $term = $var_name;
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