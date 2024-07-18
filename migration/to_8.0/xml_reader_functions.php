<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.xmlreader

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files(__DIR__."/../../SmartDoc4/");
print "Loaded ".count($files)." files\n";

$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_EXTENDS") {
            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

            if($token[0] == 'T_STRING' && $token[1] == "XMLReader") {

                while($token = $PHPParser->_next($tokens)){
                    if($token[0] == "T_FUNCTION") {
                        $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

                        if($token[0] == 'T_STRING') {
                            if($token[1] == 'open' || $token[1] == 'xml') {
                                $to_change[] = $file.":".$token[2].":".$token[1].":add_static_into_function_name";
                            }
                        }
                    }
                }
            }
        }
    }
}

//print_r($to_change);

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
                    case "add_static_into_function_name":
                        $explodedLine = explode($function_name,$line);
                        $acessor = explode("function",$explodedLine[0]);

                        $line = $acessor[0]."static function ".$function_name.$explodedLine[1];

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
