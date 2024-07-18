<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.standard

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files(__DIR__."/../../SmartDoc4/");
print "Loaded ".count($files)." files\n";

$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);
    $streamContextVar = "";
    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_VARIABLE") {
            $varName = $token[1];

            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
            if($token[0] == "DRALL_STRUCT" && $token[1] == "=") {

                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                if($token[0] == "T_STRING" && $token[1] == "stream_context_create") {
                    $_token = $token;
                    $streamContextVar = $varName;
                    $protocolVersionNeedle = '';
                    while($token = $PHPParser->_next($tokens)) {

                        if($token[0] == "T_CONSTANT_ENCAPSED_STRING") {
                            $stringWithOutQuotes = preg_replace('/["\']/', '', $token[1]);

                            if($stringWithOutQuotes == "protocol_version") $protocolVersionNeedle = $token;
                        }

                        if($token[0] == "DRALL_STRUCT" && $token[1] == ";") break;
                    }

                    if($protocolVersionNeedle == '') {
                        $to_change[] = $file.":".$_token[2].":".$_token[1].":add_protocol_version_on_stream_context_create";
                    }
                }
            }
        }

        if($token[0] == "T_STRING" && $token[1] == "file_get_contents") {
            $to_change[] = $file.":".$token[2].":".$streamContextVar.":add_context_on_file_gets_content";
        }
    }
}

//Actions must to be ordered by affected file line
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change."\n";
    list($file,$file_line,$var_name,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "add_protocol_version_on_stream_context_create":
                        $explodedLine = explode("=> [",$line);

                        $line = $explodedLine[0]."=> ['protocol_version' => '1.0', ".$explodedLine[1]."\n";

                        if($tmp_line == $line){
                            print "Not changed.\n";
                        } else {
                            print "Changed\n";
                        }

                        break;
                    case "add_context_on_file_gets_content":
                        $explodedLine = explode(",",$line);

                        if(count($explodedLine) == 2) {
                            $string = preg_replace('/[^a-zA-Z]/', '', $explodedLine[1]);
                            $newLine = $explodedLine[0].", ".$string.", ".$var_name.");\n";
                            $line = $newLine;
                        } else if (count($explodedLine) > 2) {
                            $newLine = $explodedLine[0].", ".$explodedLine[1].", ".$var_name.");\n";
                            $line = $newLine;
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