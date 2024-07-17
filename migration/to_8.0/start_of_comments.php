<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.core.other

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT']."/SmartDoc4/testFolder");

$to_change = [];

while($file = array_shift($files)) {
    $tokens = $PHPParser->get_tokens($file);
    $line_content = "";

    reset($tokens);

    while($token = $PHPParser->_next($tokens,[],$line_content)){
        if($token[0] == "T_COMMENT") {
            if(strpos($token[1], '#[') !== false) {
                $to_change[] = $file.":".$token[2].":".$token[1].":change_comment_format";
            }
        }
    }
}


//Actions must to be ordered by affected file line
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change."\n";
    list($file,$file_line,$function_name,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "change_comment_format":
                        //Replace first occurrence of class name to __constructor
                        $term = "#[";
                        $pos = strpos($line,$term);
                        if ($pos !== false) {
                            $line = substr_replace($line,"//[",$pos,strlen($term));
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