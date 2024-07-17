<?php

#https://www.php.net/manual/en/migration72.deprecated.php#migration72.deprecated.track_errors-and-php_errormsg

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files(__DIR__."/../../SmartDoc4/");

$to_change = [];

while($file = array_shift($files)) {

    $errorMessageStarted = false;
    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {

        if($token[0] == "T_VARIABLE") {
            $_token = $token;
            $_token[1] = substr($token[1], 1);
            if($_token[1] == "php_errormsg") {
                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                if($token[0] == "DRALL_STRUCT" && $token[1] == "=") {
                    $errorMessageStarted = true;
                } else if($token[1] !== "=") {
                    if(!$errorMessageStarted) {
                        $to_change[] = $file.":".($_token[2] - 1).":error_message_var_not_started";
                    }
                }

            }
        }
    }
}

sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change."\n";
    list($file,$file_line,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "error_message_var_not_started":
                        //Just concat this line before where the $php_errormsg was called
                        $line .= "\$php_errormsg = error_get_last()['message'] ?? null;\n";
                        
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

