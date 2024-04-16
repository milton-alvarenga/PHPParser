<?php

#https://www.php.net/manual/en/migration72.deprecated.php#migration72.deprecated.parse_str-no-second-arg

include "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'].'/SmartDoc4/testFolder');
$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_STRING" && $token[1] == "parse_str") {
            $_token = $token;
            $token = $PHPParser->_next($tokens);
            if($token[0] == "DRALL_STRUCT" && $token[1] == "(") {

                $token = $PHPParser->_next($tokens);
                if($token[0] == "T_VARIABLE") {
                    $token = $PHPParser->_next($tokens);
                    if($token[0] == "DRALL_STRUCT" && $token[1] == ")") {
                        $to_change[] = $file.":".$_token[2].":".$_token[1].":parse_str_with_only_one_argument";
                    }
                }
            }
        }
    }
}

print_r($to_change);