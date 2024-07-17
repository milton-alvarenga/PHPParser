<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.standard

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT']."/SmartDoc4/");
$to_change = [];
while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_VARIABLE") {
            $_token = $token;
            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
            if($token[0] == 'DRALL_STRUCT' && $token[1] == "{") {
                $to_change[] = $file.":".$_token[2].":".$token[1].":change_braces_for_brackets";
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
                    case "change_braces_for_brackets":
                        $newLine = str_replace("{", "[", $tmp_line);
                        $newLine = str_replace("}", "]", $newLine);

                        $line = $newLine;

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

