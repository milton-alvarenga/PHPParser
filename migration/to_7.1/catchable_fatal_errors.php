<?php

#https://www.php.net/manual/en/migration71.incompatible.php#migration71.incompatible.e-recoverable

require "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");

$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);
    while($token = $PHPParser->_next($tokens)) {
        
        if($token[0] == "T_CONSTANT_ENCAPSED_STRING") {

            $tokenWhitoutQuotes = preg_replace('/[\'"]/', '', $token[1]);

            if($tokenWhitoutQuotes == 'Catchable fatal error') {
                $to_change[] = $file.":".$token[2].":".$token[1].":catchable_error_message_detected_needs_to_be_analyzed";
            }

        }

    }

}

print_r($to_change);