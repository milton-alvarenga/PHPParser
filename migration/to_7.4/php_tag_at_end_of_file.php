<?php

#https://www.php.net/manual/en/migration74.incompatible.php#migration74.incompatible.core.php-tag

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files(__DIR__."/../../SmartDoc4/");
print "Loaded ".count($files)." files\n";

$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    $inside_php_code = false;

    while($token = $PHPParser->_next($tokens)) {

        if($token[0] == "T_OPEN_TAG"){
            $inside_php_code = true;
        }

        if($token[0] == "T_CLOSE_TAG"){
            $inside_php_code = false;
        }

        if($token[0] == "DRALL_STRUCT" && $token[1] == "<"){
            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

            if($token[0] == "DRALL_STRUCT" && $token[1] == "?"){
                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                if($token[0] == "T_STRING" && $token[1] == "php" && $inside_php_code){
                    $to_change[] = $file.":".$token[2].":".$token[1].":remove_useless_php_tag";
                }
            }
        }
    }
}

//Actions must to be ordered by affected file line
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change."\n";
    list($file,$file_line,$tagName,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "remove_useless_php_tag":
                        $term = "<?".$tagName;
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
        chown($file,1000);
        chgrp($file,1000);
    } else {
        // error opening the file.
        throw new Exception("Could not open file ".$file."\n");
    }

    print "Ending ".$change."\n";
}