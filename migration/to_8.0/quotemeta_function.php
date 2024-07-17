<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.standard

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . '/SmartDoc4/testFolder');

$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_STRING" && strtolower($token[1]) == "quotemeta") {
            $to_change[] = $file.":".$token[2].":".$token[1].":quotemeta_function_in_use_check_the_behavior";
        }
    }
}

print_r($to_change);
