<?php

#https://www.php.net/manual/en/migration71.incompatible.php#migration71.incompatible.long2ip

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");

$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_STRING" && $token[1] == "long2ip") {
            $_token = $token;

            $token = $PHPParser->_next($tokens);
            if($token[0] == "DRALL_STRUCT" && $token[1] == "("){
                $token = $PHPParser->_next($tokens);

                if($token[0] !== "T_INT_CAST") {
                    $to_change[] = $file.":".$token[2].":".$token[1].":needs_to_convert_into_integer";
                }
            }
        }
    }
}

sort($to_change);

while($change = array_shift($to_change)) {
	print "Starting ".$change."\n";
	list($file,$file_line,$var_to_convert,$action) = explode(":",$change);
	$handle = fopen($file, "r");
	$writing = fopen($file.'.tmp', 'w');

    if($handle) {
		$read_line = 1;
	    while (($line = fgets($handle)) !== false) {
	    	if($file_line == $read_line){
	    		$tmp_line = $line;
	    		print "Must to change this line on action ".$action."\n";

	    		switch($action){
	    			case "needs_to_convert_into_integer":
			    		//Replace first occurrence of class name to __constructor
			    		$term = $var_to_convert;
			    		$pos = strpos($line,$term);
                        
                        $newVar = "(int)" . $var_to_convert;
                        print_r($newVar);
						if ($pos !== false) {
						    $line = substr_replace($line,$newVar,$pos,strlen($term));
						}
						if($tmp_line == $line){
							print "Not changed.\n";
						} else {
							print "Changed\n";
						}
					break;
					default:
						print "No action executed! It is wrong.\n";
                }
            }
	        // process the line read.
	        fputs($writing, $line);
	        $read_line++;
	    }
	
	    fclose($handle);
	    fclose($writing);
	    
	    rename($file.'.tmp', $file);
	} else {
	    // error opening the file.
	    throw new Exception("Could not open file ".$file."\n");
	} 
	
	print "Ending ".$change."\n";
}