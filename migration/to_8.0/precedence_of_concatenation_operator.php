<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.core.other

include "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'].'/SmartDoc4/testFolder');

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "DRALL_STRUCT" && $token[1] == ".") {
            $token = $PHPParser->_next($tokens);
        }
    }

}