<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.core.other

include "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'].'/SmartDoc4/testFolder');
$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);
    $inside_php_code = false;
    $line_content = "";
    $affected = false;
    $_token = "";

    while($token = $PHPParser->_next($tokens,[],$line_content)){
        if($token[0] == "T_OPEN_TAG"){
            $inside_php_code = true;
        }
        if(!$inside_php_code){
            continue;
        }

        if($token[0] == "T_CLOSE_TAG"){
            $inside_php_code = false;
            continue;
        }

        //Is it a new line?
        if(
            ($token[0] == "DRALL_STRUCT" && $token[1] == ";")
            ||
            ($token[0] == "DRALL_NEW_LINE")
        ){
            if($affected){
                $line_content = str_replace(";", "", $line_content);
                $line_number = "";

                if(strpos($line_content, "+") !== false || strpos($line_content, "-") !== false){
                    $_token[0] != "DRALL_NEW_LINE" ? $line_number = $_token[2] : $line_number = $token[2];
                    $to_change[] = $file.";".$line_content.";".$line_number.";concatenation_affected_to_the_new_mode";
                }
            }
            $line_content = "";
            $_token = "";
            $affected = false;
            continue;
        }

        if($token[0] == "DRALL_STRUCT" && $token[1] == "."){
            $_token = $PHPParser->_next($tokens, [], $line_content);
            $affected = true;
        }
    }
}


sort($to_change);

while($change = array_shift($to_change)) {
    print "Starting " . $change . "<br>";
    list($file, $line_content, $line_number, $action) = explode(";", $change);
    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($line_number == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "concatenation_affected_to_the_new_mode":
                        $explodedConcat = explode(".", $line_content);
                        $newLine = "";
                        $start = reset($explodedConcat);
                        $end = end($explodedConcat);

                        foreach($explodedConcat as $index => $concat) {
                            if (strpos($concat, '+') !== false || strpos($concat, '-') !== false) {
                                if(!preg_match('/^\(.+\)$/', $concat)) {
                                    $explodedConcat[$index] = "(" . $concat . ")";
                                }

                                if ($start == $concat) $start = $explodedConcat[$index];
                                if ($end == $concat) $end = $explodedConcat[$index];
                            }


                            if ($explodedConcat[$index] == $start) {
                                $newLine .= $explodedConcat[$index] . ".";
                            } else if ($explodedConcat[$index] == $end) {
                                $newLine .= $explodedConcat[$index] . ";\n";
                            } else {
                                $newLine .= $explodedConcat[$index] . ".";
                            }
                        }
                        $line = $newLine;

                        if ($tmp_line == $line) {
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