<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.zlib

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files(__DIR__."/../../SmartDoc4/");
print "Loaded ".count($files)." files\n";
$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_STRING" && $token[1] == "gzgetss") {
            $to_change[] = $file.":".$token[2].":".$token[1].":change_function_name_to_gzgets";
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
                    case "change_function_name_to_gzgets":
                        $lineExploded = explode("gzgetss",$line);
                        $params = explode(",", $lineExploded[1]);

                        $stream = preg_replace('/[^a-zA-Z0-9$]/', '', $params[0]);
                        $length = preg_replace('/[^a-zA-Z0-9$]/', '', $params[1]);
                        $tags = '';

                        if(count($params) > 2) {
                            $tags = preg_replace('/[^a-zA-Z0-9$]/', '', $params[2]);

                            if (!preg_match('/\$/', $tags)) {
                                $tags = "'".$tags."'";
                            }

                            $tags = ",".$tags;
                        }

                        $line = $lineExploded[0]."strip_tags(gzgets(".$stream.",".$length.")".$tags.");\n";

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