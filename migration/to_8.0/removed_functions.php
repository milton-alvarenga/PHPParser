<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.standard

include "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT']."/SmartDoc4/testFolder");

$deprecated_functions = [
    'hebrevc',
    'convert_cyr_string',
    'money_format',
    'ezmlm_hash',
    'restore_include_path',
    'get_magic_quotes_gpc',
    'get_magic_quotes_runtime',
    'fgetss'
];

$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_STRING" && in_array(strtolower($token[1]), $deprecated_functions)) {
            $to_change[] = $file.":".$token[2].$token[1].":deprecated_function_in_use";
        }
    }
}

print_r($to_change);