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

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_DOUBLE_CAST" && $token[1] == "(real)") {
            $to_change[] = $file.":".$token[2].":".$token[1].":change_real_cast_to_float";
        }
    }
}


//Actions must to be ordered by affected file line
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change."\n";
    list($file,$file_line,$cast_name,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "change_real_cast_to_float":
                        //Replace first occurrence of class name to __constructor
                        $term = $cast_name;
                        $pos = strpos($line,$term);
                        if ($pos !== false) {
                            $line = substr_replace($line,"(float)",$pos,strlen($term));
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