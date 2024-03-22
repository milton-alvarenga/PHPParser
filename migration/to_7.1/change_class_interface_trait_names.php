<?php

#https://www.php.net/manual/en/migration71.incompatible.php#migration71.incompatible.invalid-class-names

require '../../lib/PHPParser.class.php';

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");


$to_change = [];
while($file = array_shift($files)){
	$tokens = $PHPParser->get_tokens($file);
	
	reset($tokens);
	

	$brackets = 0;
	$current_class = "";
	$current_extends_class = "";
	while($token = $PHPParser->_next($tokens)){
		
		if($token[0] == "T_CLASS"){
			$token = $PHPParser->_next($tokens);
			if($token[0] == "T_WHITESPACE"){
				$token = $PHPParser->_next($tokens);
				if($token[0] == "T_STRING"){
					$current_class = $token[1];
					$brackets = 0;
					$_token = $token;

					$token = $PHPParser->_next($tokens);
					if($token[0] == "T_WHITESPACE"){
						$token = $PHPParser->_next($tokens);
						if($token[0] == "T_EXTENDS"){
							$token = $PHPParser->_next($tokens);
							if($token[0] == "T_WHITESPACE"){
								$token = $PHPParser->_next($tokens);
								if($token[0] == "T_STRING"){
									$current_extends_class = $token[1];
								}
							}
						}
					}
				}
			}
		}

		if($current_class){
 			if($_token[0] == "T_STRING"){
				if(strtolower($current_class) == 'void' || strtolower($current_class) === 'iterable'){
					$to_change[] = $file.":".$_token[2].":".$_token[1].":change_invalid_class_name";
                }
            }
        }

        if($current_extends_class) {
            if($token[0] == "T_STRING") {
                if(strtolower($current_extends_class) == 'void' || strtolower($current_class) == 'iterable') {
                    $to_change[] = $file.":".$token[2].":".$token[1].":change_invalid_extends_class_name";
                }
            }
        }
    }

    print_r($to_change);

    //Actions must to be ordered by affected file line
    sort($to_change);
}
