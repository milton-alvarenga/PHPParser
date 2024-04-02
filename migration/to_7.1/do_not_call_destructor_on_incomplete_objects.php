<?php

#https://www.php.net/manual/en/migration71.incompatible.php#migration71.incompatible.dont-call-destructors

require '../../lib/PHPParser.class.php';

const DESTRUCT  = "__destruct";
const CONSTRUCT = "__construct";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");
$filesToAnalyze = [];
$throwableNames = [
    'runtimeexception',
    'throw',
    'trigger_error',
    'set_exception_handler'
];

while($file = array_shift($files)){
	
    $tokens = $PHPParser->get_tokens($file);
    $fileHasDestruct = false;
    $fileThrowExceptionOnConstructor = false;
    $destructorInfos = new stdClass;
    $constructorInfos = new stdClass;

    reset($tokens);

    while($token = $PHPParser->_next($tokens)){

        if($token[0] == "T_FUNCTION"){
    
            $token = $PHPParser->_next($tokens);
    
            if($token[0] == "T_WHITESPACE") {
                $token = $PHPParser->_next($tokens);
    
                if($token[0] == "T_STRING" && $token[1] == CONSTRUCT) {

                    while($token = $PHPParser->_next($tokens)) {
                        if($token[0] == "}") {
                            break;
                        }

                        if(in_array($token[1], $throwableNames)) {
                            $fileThrowExceptionOnConstructor = true;
                            $constructorInfos->line = $token[2];
                            break;
                        }
                    }

                }

                if($token[0] == "T_STRING" && $token[1] == DESTRUCT) {
                    $fileHasDestruct = true;
                    $destructorInfos->line = $token[2];
                }
            }
        }
    }

    if($fileHasDestruct && $fileThrowExceptionOnConstructor) {
        $filesToAnalyze[] = $file.":constructorExceptionLine:".$constructorInfos->line.":destructorLine:".$destructorInfos->line;
    }
}

print_r($filesToAnalyze);

?>