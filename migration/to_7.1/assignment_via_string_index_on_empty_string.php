
<?php

#https://www.php.net/manual/en/migration71.incompatible.php#migration71.incompatible.empty-string-index-operator

require "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");
$indexAssignmentOnEmptyVariables = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    $emptyVariables = [];

    while($_token = $PHPParser->_next($tokens)) {

        if($_token[0] == "T_VARIABLE") {

            //CHECKING IF IS A EMPTY VARIABLE
            $token = $PHPParser->_next($tokens);
            if($token[0] == "T_WHITESPACE"){

                $token = $PHPParser->_next($tokens);
                if($token[0] == "DRALL_STRUCT") {

                    $token = $PHPParser->_next($tokens);
                    if($token[0] == "T_WHITESPACE"){

                        $token = $PHPParser->_next($tokens);
                        if($token[0] == "T_CONSTANT_ENCAPSED_STRING") {

                            if($token[1] === "''" || $token[1] === '""') {

                                if(in_array($_token[1], $emptyVariables)) break;

                                $varInfos = new stdClass;
                                $varInfos->line = $_token[2];
                                $varInfos->file = $file;

                                $emptyVariables[$_token[1]] = $varInfos;
                            }
                        }
                    }
                }
            } else if ($token[0] == "DRALL_STRUCT" && $token[1] == "[") {
                $varRepresentation = $_token[1].$token[1];

                $token = $PHPParser->_next($tokens);
                if($token[0] == "T_LNUMBER") {
                    $varRepresentation .= $token[1];

                    $token = $PHPParser->_next($tokens);
                    if($token[0] == "DRALL_STRUCT" && $token[1] == "]") {

                        $varRepresentation .= $token[1];
                        $emptyStringVerification = $emptyVariables[$_token[1]];

                        if($emptyStringVerification) {
                            $indexAssignmentOnEmptyVariables[] = "empty_variable_with_index_assigment_on:".$file.":".$_token[2].":".$varRepresentation;
                        }
                    }
                }
            }
        }
    }

    print_r($indexAssignmentOnEmptyVariables);
}

?>