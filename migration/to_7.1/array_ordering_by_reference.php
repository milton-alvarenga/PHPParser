<?php

#https://www.php.net/manual/en/migration71.incompatible.php#migration71.incompatible.array-order

require "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");

$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);
    $emptyArraysList = [];

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {

        if($token[0] == "T_VARIABLE") {
            $_token = $token;

            $token = $PHPParser->_next($tokens, ["T_WHITESPACE"]);
            if($token[0] == "DRALL_STRUCT" && $token[1] == "=") {
                $token = $PHPParser->_next($tokens, ["T_WHITESPACE"]);

                if($token[0] == "DRALL_STRUCT" && $token[1] == "[") {
                    $token = $PHPParser->_next($tokens);

                    if($token[0] == "DRALL_STRUCT" && $token[1] == "]") {
                        $arrInfos           = new stdClass;
                        $arrInfos->line     = $_token[2];
                        $arrInfos->variable = $_token[1];

                        $emptyArraysList[$_token[1]] = $arrInfos;
                    }
                }else if($token[0] == "T_ARRAY") {
                    $token = $PHPParser->_next($tokens);

                    if($token[0] == "DRALL_STRUCT" && $token[1] == "(") {
                        $token = $PHPParser->_next($tokens);

                        if($token[0] == "DRALL_STRUCT" && $token[1] == ")") {

                            $arrInfos           = new stdClass;
                            $arrInfos->line     = $_token[2];
                            $arrInfos->variable = $_token[1];

                            $emptyArraysList[$_token[1]] = $arrInfos;
                        }
                    }
                }
            }
        }

        if($token[0] == "DRALL_STRUCT" && $token[1] == "=") {
            $token = $PHPParser->_next($tokens);

            if($token[0] == "DRALL_STRUCT" && $token[1] == "&") {

                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

                if($token[0] == "T_VARIABLE") {
                    $emptyArrayAssignment = $emptyArraysList[$token[1]];

                    if($emptyArrayAssignment) {
                        $to_change[] = $file.":".$emptyArrayAssignment->variable.":".$token[2].":empty_array_reference";
                    }
                }
            }
        }
    }
}
sort($to_change);

print_r($to_change);