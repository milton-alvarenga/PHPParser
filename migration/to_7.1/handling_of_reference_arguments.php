<?php

#https://www.php.net/manual/en/migration71.incompatible.php#migration71.incompatible.call_user_func-with-ref-args

require '../../lib/PHPParser.class.php';

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");
$to_change = [];

while($file = array_shift($files)){

    $tokens = $PHPParser->get_tokens($file);

    
    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_STRING" && $token[1] == "call_user_func") {
            $to_change[] = $file . ":" . $token[2] . ":change_to_call_user_func_array";
        }
    }
}

print_r($to_change);

?>