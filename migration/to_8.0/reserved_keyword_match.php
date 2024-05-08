<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.core.other

include '../../lib/PHPParser.class.php';

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT']."/SmartDoc4/testFolder");

$to_change = [];
$forbidden_prefixes = [
    'public',
    'private',
    'protected',
    'static',
    'abstract'
];

while($file = array_shift($files)) {

    $lines = file($file);

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while ($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_NAMESPACE") {
        $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

        if ($token[0] == "T_STRING") {
            $stringToCheck = preg_replace('/\s+/', '', strtolower($token[1]));

            if ($stringToCheck == 'match') {
                $to_change[] = $file . ":" . $token[2] . ":" . $token[1] . ":change_namespace_name";
            }
        }
    }
        if($token[0] == "T_TRAIT") {
            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

            if ($token[0] == "T_STRING") {
                $stringToCheck = preg_replace('/\s+/', '', strtolower($token[1]));

                if ($stringToCheck == 'match') {
                    $to_change[] = $file . ":" . $token[2] . ":" . $token[1] . ":change_trait_name";
                }
            }
        }

        if ($token[0] == "T_CLASS") {
            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

            if ($token[0] == "T_STRING") {
                $stringToCheck = preg_replace('/\s+/', '', strtolower($token[1]));

                if ($stringToCheck == 'match') {
                    $to_change[] = $file . ":" . $token[2] . ":" . $token[1] . ":change_class_name";
                }
            }
        }

        if ($token[0] == 'T_FUNCTION') {
            $regularExpression = '/' . implode('|', array_map('preg_quote', $forbidden_prefixes)) . '/';
            if (!preg_match($regularExpression, $lines[$token[2] - 1])) {
                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                $stringToCheck = preg_replace('/\s+/', '', strtolower($token[1]));

                if ($stringToCheck == 'match') {
                    $to_change[] = $file . ":" . $token[2] . ":" . $token[1] . ":change_function_name";
                }
            }
        }

        if ($token[0] == "T_VARIABLE") {
            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

            if ($token[0] == "DRALL_STRUCT" && $token[1] == '=') {
                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

                if($token[0] == "T_NEW") {
                    $_token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

                    if($_token[0] == "T_STRING") {
                        $stringToCheck = preg_replace('/\s+/', '', strtolower($_token[1]));

                        if($stringToCheck == 'match') {
                            $to_change[] = $file.":".$_token[2].":".$_token[1].":change_instance_name";
                        }
                    }
                }else if ($token[0] == "T_STRING") {
                    $stringToCheck = preg_replace('/\s+/', '', strtolower($token[1]));

                    if ($stringToCheck == 'match') {
                        $to_change[] = $file . ":" . $token[2] . ":" . $token[1] . ":change_instance_class_name";
                    }
                }
            }
        }
    }
}

print_r($to_change);
//Actions must to be ordered by affected file line
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change."\n";
    list($file,$file_line,$fnKeyWord,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "change_namespace_name":
                    case "change_trait_name":
                    case "change_class_name":
                    case "change_function_name":
                    case "change_instance_class_name":
                    case "change_instance_name":
                        $term = $fnKeyWord;
                        $pos = strpos($line,$term);
                        if ($pos !== false) {
                            $line = substr_replace($line,"_match",$pos,strlen($term));
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
