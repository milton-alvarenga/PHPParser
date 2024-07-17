<?php

#https://www.php.net/manual/en/migration72.deprecated.php#migration72.deprecated.png2wbmp-jpeg2wbmp

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");

$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);
    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_STRING") {
            if($token[1] == "png2wbmp" || $token[1] == "jpeg2wbmp") {
                $to_change[] = $file.":".$token[2].":".$token[1].":incorrect_usage_of_$token[1]";
            }
        }
    }
}

print_r($to_change);