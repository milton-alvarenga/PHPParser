
<?php

#https://www.php.net/manual/en/migration71.incompatible.php#migration71.incompatible.empty-string-index-operator

require "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");
$to_change = [];

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
                            $to_change[] = $file.":".$_token[2].":".$varRepresentation.":".$_token[1].":empty_variable_with_index_assignment";
                        }
                    }
                }
            }
        }
    }
}

//Actions must to be ordered by affected file line
// print_r($to_change);

while($change = array_shift($to_change)){
	// print "Starting ".$change."<br>";
	list($file,$file_line,$var_name,$newVar,$action) = explode(":",$change);
	$handle = fopen($file, "r");
	$writing = fopen($file.'.tmp', 'w');

	if ($handle) {
		$read_line = 1;
	    while (($line = fgets($handle)) !== false) {
	    	if($file_line == $read_line){
	    		$tmp_line = $line;
                
	    		print "Must to change this line on action ".$action."<br>";

	    		switch($action){
	    			case "empty_variable_with_index_assignment":
			    		$term = $var_name;
			    		$pos = strpos($line,$term);

						if ($pos !== false) {
						    $line = substr_replace($line,$newVar,$pos,strlen($term));
						}
                        print_r($line);
						if($tmp_line == $line){
							print "Not changed. <br>";
						} else {
							print "Changed <br>";
						}
					break;
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
}

?>