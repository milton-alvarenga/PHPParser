<?php

#https://www.php.net/manual/en/migration73.incompatible.php#migration73.incompatible.core.arrayaccess

include "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'].'/SmartDoc4/testFolder');

$to_verify = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_VARIABLE") {

            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
            if($token[0] == "DRALL_STRUCT" && $token[1] == "[") {

                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                $_token = $token;
                if($token[0] == "T_CONSTANT_ENCAPSED_STRING") {
                    $verifyToken = preg_replace('/[^0-9]/', '', $token[1]);
                    if(is_numeric($verifyToken)) {
                        $to_verify[] = $file.":".$token[2].":array_access_via_offset_get";
                    }
                }
            }
        }
    }
}

print_r($to_verify);