<?php

https://www.php.net/manual/pt_BR/migration71.incompatible.php#migration71.incompatible.forbid-dynamic-calls-to-scope-introspection-functions

require '../../lib/PHPParser.class.php';

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");

$forbiddenDynamicCalls = [
    'assert',
    'compact',
    'extract',
    'func_get_args',
    'func_get_arg',
    'func_num_args',
    'get_defined_vars',
    'mb_parse_str',
    'parse_str',
];

$to_change = [];

while($file = array_shift($files)) {
	$tokens = $PHPParser->get_tokens($file);
	
    reset($tokens);

	while($token = $PHPParser->_next($tokens)){
        if($token[0] == "T_VARIABLE") {
            $token = $PHPParser->_next($tokens);
            
            if($token[0] == "T_WHITESPACE") {
                $token = $PHPParser->_next($tokens);

                if($token[0] == "DRALL_STRUCT") {
                    $token = $PHPParser->_next($tokens);

                    if($token[0] == "T_WHITESPACE") {
                        $token = $PHPParser->_next($tokens);

                        if($token[0] == "T_CONSTANT_ENCAPSED_STRING") {
                            
                            $_token = $PHPParser->_next($tokens);

                            if($_token[1] != ";") break;
                            
                            $tokenWhitoutQuotes = preg_replace('/[\'"]/', '', $token[1]);
    
                            if(in_array($tokenWhitoutQuotes, $forbiddenDynamicCalls)) {
                                $to_change[] = $file.":line:".$token[2].":".$tokenWhitoutQuotes.":forbidden_dynamic_function_call";
                            }
                        }
                    }
                }
            }
        }
    }
}

print_r($to_change);

?>