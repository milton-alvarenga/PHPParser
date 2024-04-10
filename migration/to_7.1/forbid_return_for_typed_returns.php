<?php

#https://www.php.net/manual/en/migration71.incompatible.php#migration71.incompatible.typed-returns-compile-time

include "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");

$declaredTypedFunctions = [];
$usedTypedFunctions = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_FUNCTION") {

            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
            if($token[0] == "T_STRING") {
                $functionName = $token[1];

                $token = $PHPParser->_next($tokens);
                if($token[1] == "(") {

                    while($token[1] != ")"){
                        $token = $PHPParser->_next($tokens);
                    }

                    $token = $PHPParser->_next($tokens);
                    if($token[1] == ":") {
                        $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

                        if($token[0] == "T_STRING") {
                            $declaredTypedFunctions[$functionName] = $file.":".$token[2].":".$token[1].":function_declared";
                        }
                    }
                }

            }
        }

        if($token[0] == 'T_STRING') {
            $functionCalled = $token;

            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

            if($token[0] == "DRALL_STRUCT" && $token[1] == "(") {
                $functionExistsInArray = $declaredTypedFunctions[$functionCalled[1]];

                if($functionExistsInArray) {
                    $usedTypedFunctions[] = $file.":".$functionCalled[2].":".$functionCalled[1].":using_declared_function";
                }
            }
        }
    }
}

print_r($usedTypedFunctions);
echo "<br>";
print_r($declaredTypedFunctions);