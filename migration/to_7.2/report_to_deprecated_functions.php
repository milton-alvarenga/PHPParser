<?php

#https://www.php.net/manual/en/migration72.deprecated.php#migration72.deprecated.INTL_IDNA_VARIANT_2003-variant

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT']."/SmartDoc4/testFolder");
$willChange = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[1] == "idn_to_utf8" || $token[1] == "idn_to_ascii") {
            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
            if($token[0] == "DRALL_STRUCT" && $token[1] == "(") {

                while($token = $PHPParser->_next($tokens)) {
                    if($token[0] == "T_STRING" && $token[1] == "INTL_IDNA_VARIANT_2003") {
                        $willChange[] = $file.":".$token[2].":INTL_IDNA_VARIANT_2003_will_be_replaced_by_ INTL_IDNA_VARIANT_UTS46";
                    }
                    if($token[0] == ")") {
                        break;
                    }
                }

            }
        }
    }
}

print_r($willChange);
