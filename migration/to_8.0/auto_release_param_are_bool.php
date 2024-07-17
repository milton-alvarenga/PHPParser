<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.sysvsem

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'].'/SmartDoc4/testFolder');

$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == 'T_STRING' && strtolower($token[1]) == 'sem_get') {

            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
            if($token[0] == 'DRALL_STRUCT' && $token[1] == '(') {
                $commaCounter = 0;

                while($token = $PHPParser->_next($tokens, ['T_WHITESPACE'])) {

                    if($commaCounter == 3) {
                        break;
                    }

                    if($token[0] == 'DRALL_STRUCT' && $token[1] == ')') {
                        break;
                    }

                    if($token[0] == "DRALL_STRUCT" && $token[1] == ",") {
                        $commaCounter++;
                    }
                }

                if($commaCounter == 3) {
                    $to_change[] = $file.":".$token[2].":".$token[1].":change_integer_operator_to_boolean";
                }
            }
        }
    }
}
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change.'<br>';
    list($file,$file_line,$integer_operator,$action) = explode(":",$change);

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

                        $term = ");";
                        $pos = strpos($line,$term);
                        if ($pos !== false) {
                            $replace = "true";

                            if($integer_operator == 0) {
                                $replace = "false";
                            }

                            $line = substr_replace($line,$replace.")",($pos-1),strlen($term));
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
        throw new Exception("Could not open file ".$file."<br>");
    }

    print "Ending ".$change."<br>";
}
