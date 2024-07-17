<?php

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT']."/SmartDoc4/testFolder");
$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);
    $function_lines = [];
    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_STRING" && $token[1] == "__autoload") {
            $autoLoad = $token;
            $start_line = $token[2];
            $brace_count = 0;
            $end_line = $start_line;
            // Locate the opening brace of the __autoload function
            while($token = $PHPParser->_next($tokens)) {

                if($token[1] == '{') {
                    $brace_count++;
                    break;
                }
            }
            // Find the closing brace of the __autoload function
            while($brace_count > 0 && $token = $PHPParser->_next($tokens)) {
                if($token[1] == '{') {
                    $brace_count++;
                } elseif($token[1] == '}') {
                    $brace_count--;
                    if($brace_count == 0) {
                        $token = $PHPParser->_next($tokens);
                        if($token[0] == "T_WHITESPACE") $end_line = $token[2];
                    }
                }
            }
            
            $to_change[] = $file.":".$start_line.":".$autoLoad[1].":replace_name_function";
            $to_change[] = $file.":".$end_line.":fake_function_name:add_close_brackets";
        }
    }
}

var_dump($to_change);

//Actions must to be ordered by affected file line
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change."<br>";
    list($file,$file_line,$function_name,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."<br>";

                switch($action){
                    case "replace_name_function":
                        preg_match('/\(([^)]+)\)/', $line, $matches);
                        $param = $matches[1];
                        $line = "spl_autoload_register(function (".$param.") { \n";

                        if($tmp_line == $line){
                            print "Not changed.<br>";
                        } else {
                            print "Changed<br>";
                        }
                        break;
                    case "add_close_brackets":
                        $line = $line.");\n";
                        
                        if($tmp_line == $line){
                            print "Not changed.<br>";
                        } else {
                            print "Changed<br>";
                        }
                        break;
                    default:
                        print "No action executed! It is wrong.<br>";
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
        throw new Exception("Could not open file ".$file."<br>");
    }

    print "Ending ".$change."<br>";
}