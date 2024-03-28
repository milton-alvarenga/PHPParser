<?php

https://www.php.net/manual/en/migration71.incompatible.php#migration71.incompatible.too-few-arguments-exception

require '../../lib/PHPParser.class.php';

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");

$functionsAndArguments = [];
$verifyingFunctionsAndArguments = [];
$stack_fn = [];
$fns_list = [];
$to_change = [];

while($file = array_shift($files)){
	$tokens = $PHPParser->get_tokens($file);
	
    reset($tokens);

	while($token = $PHPParser->_next($tokens)){
        //CONDITION TO SEE THE QUANTITY OF ARGUMENTS ON THE FUNCTION
		if($token[0] == "T_FUNCTION"){

            $token = $PHPParser->_next($tokens);
            if($token[0] == "T_WHITESPACE"){

                $_token = $PHPParser->_next($tokens);
                if($_token[0] == "T_STRING"){

                    $token = $PHPParser->_next($tokens);
                    if ($token[0] == "DRALL_STRUCT" && $token[1] == "(") {
                        
                        $functionName = $_token[1];
                        $argumentsCount = 0;

                        while($token[1] != ")"){
							$token = $PHPParser->_next($tokens);

                            if($token[0] == "T_VARIABLE") $argumentsCount++;
						}

                        $functionsAndArguments[$functionName] = array(
                            "fileName" => $file,
                            "arguments" => $argumentsCount,
                            "line" => $_token[2]
                        );
                    }
                }
            }
        }
        //CONDITION TO SEE THE ARG QTY ON THE FUNCTIONS WICH HAVE "->" BEFORE
        if($token[0] == "T_OBJECT_OPERATOR") {
            $_token = $PHPParser->_next($tokens);

            if($_token[0] == "T_STRING") {

                $functionName = $_token[1];
                $argumentsCount = 0;
                $token = $PHPParser->_next($tokens);
                
                while($token[1] != ")") {
                    $token = $PHPParser->_next($tokens);

                    if($token[0] == "T_CONSTANT_ENCAPSED_STRING") $argumentsCount++;
                }
                    
                $verifyingFunctionsAndArguments[$functionName] = array(
                    "fileName" => $file,
                    "arguments" => $argumentsCount,
                    "line" => $_token[2]
                );
            }
        }

        //CONDITION TO SEE IF THE ARGS OF THE FUNCTIONS THAT ARE CALLED WITHOUT THE "->" ARE OK
        if($token[0] == "T_STRING") {
            
            $fn = new StdClass();
            $fn->nm = $token[1];
            $fn->args = 0;
            $fn->file = $file;
            $fn->line = $token[2];
            
            array_push($stack_fn, $fn);
            array_push($fns_list, $fn);

            $token = $PHPParser->_next($tokens);
            
            if($token[1] == "(") {

                $_token = $PHPParser->_next($tokens);
                if($_token[1] != ")") {
                    
                    $stack_fn[count($stack_fn)-1]->args++;

                    while($token = $PHPParser->_next($tokens)){

                        // Check if it's the start of a function call
                        if ($token[0] == "T_STRING") {
                            $fn = new stdClass();
                            
                            $fn->nm = $token[1];
                            $fn->args = 0;
                            $fn->file = $file;
                            $fn->line = $token[2];

                            $fns_list[] = $fn;
                            $stack_fn[] = $fn;
                        }
                        
                        // Check if it's an argument separator
                        if (
                            $stack_fn && $token[1] === ',' ||
                            $stack_fn && $token[0] == 'T_STRING'
                        ) {
                            $stack_fn[count($stack_fn)-1]->args++;
                        }
        
                        // Check if it's the end of a function call
                        if ($stack_fn && $token[1] === ')') {
                            array_pop($stack_fn);
                        }
                    }
                }
            }
        }
    }
}

if(count($fns_list) > 0) {
    
    foreach($fns_list as $function) {
        $verifyingFunctionsAndArguments[$function->nm] = array(
            "fileName" => $function->file,
            "arguments" => $function->args,
            "line" => $function->line
        );
    }

}

foreach($verifyingFunctionsAndArguments as $functionName => $functionArgs) {
    if($functionArgs['arguments'] < $functionsAndArguments[$functionName]['arguments']) {
        $to_change[] = $functionArgs['fileName'].":".$functionArgs['line'].":".$functionName."_expecting_".$functionsAndArguments[$functionName]['arguments']."_arguments_have_".$functionArgs['arguments'];
    }
}

print_r($to_change);
// echo "<br>";
// print_r($functionsAndArguments);
// echo "<br>";
// print_r($verifyingFunctionsAndArguments);