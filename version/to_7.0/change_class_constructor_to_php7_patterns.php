<?php
require '../../lib/PHPParser.class.php';

$PHPParser = new PHPParser();

$files = $PHPParser->get_files("/tmp/system_name/");


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
			if($token[0] == "T_FUNCTION"){
				$token = $PHPParser->_next($tokens);
				if($token[0] == "T_WHITESPACE"){
					while($token = $PHPParser->_next($tokens)){
						if($token[0] != "T_STRING"){
							if($token == "{"){
								break;
							}
							continue;
						}
						
						if(strtolower($current_class) == strtolower($token[1])){
							$to_change[] = $file.":".$token[2].":".$token[1].":replace_construct_php4_to_php5";
						}
						break;	
					}
				}
			}
		}
		
		if($current_extends_class){
			if($token[0] == "T_VARIABLE"){
				$token = $PHPParser->_next($tokens);
				if($token[0] == "T_OBJECT_OPERATOR"){
					$token = $PHPParser->_next($tokens);
					if(
						$token[0] == "T_STRING"
					){
						$_token = $token;
						$token = $PHPParser->_next($tokens);
						while($token[0] == "T_WHITESPACE"){
							$token = $PHPParser->_next($tokens);
						}
						
						if($token == "("){
							$token = $PHPParser->_next($tokens);
							if(strtolower($current_extends_class) == strtolower($_token[1])){
								$to_change[] = $file.":".$_token[2].":".$_token[1].":replace_extends_construct_php4_to_php5_call";
							} else if(strtolower($current_class) == strtolower($_token[1])){
								$to_change[] = $file.":".$_token[2].":".$_token[1].":replace_current_class_construct_php4_to_php5_call";
							}
						}
					}
				}
			}
			
			if($token[0] == "T_STRING" && $token[1] == "parent"){
				$token = $PHPParser->_next($tokens);
				if($token[0] == "T_PAAMAYIM_NEKUDOTAYIM" || $token[0] == "T_DOUBLE_COLON"){
					$token = $PHPParser->_next($tokens);
					if($token[0] == "T_STRING"){
						$_token = $token;
						$token = $PHPParser->_next($tokens);
						while($token[0] == "T_WHITESPACE"){
							$token = $PHPParser->_next($tokens);
						}
						
						if($token == "("){
							if(strtolower($current_extends_class) == strtolower($_token[1])){
								$to_change[] = $file.":".$_token[2].":".$_token[1].":replace_extends_construct_php4_to_php5_static_call";
							}
						}
					}
				}
			}
		}
	}
}

print_r($to_change);

//Actions must to be ordered by affected file line
sort($to_change);

while($change = array_shift($to_change)){
	print "Starting ".$change."\n";
	list($file,$file_line,$function_name,$action) = explode(":",$change);
	
	$handle = fopen($file, "r");
	$writing = fopen($file.'.tmp', 'w');
	if ($handle) {
		$read_line = 1;
	    while (($line = fgets($handle)) !== false) {
	    	if($file_line == $read_line){
	    		$tmp_line = $line;
	    		print "Must to change this line on action ".$action."\n";

	    		switch($action){
	    			case "replace_construct_php4_to_php5":
			    		//Replace first occurrence of class name to __constructor
			    		$term = $function_name;
			    		$pos = strpos($line,$term);
						if ($pos !== false) {
						    $line = substr_replace($line,"__construct",$pos,strlen($term));
						}
						if($tmp_line == $line){
							print "Not changed.\n";
						} else {
							print "Changed\n";
						}
					break;
					
					case "replace_extends_construct_php4_to_php5_call":
						//Replace first occurrence of class name to __constructor
						$term = "\$this->".$function_name;
			    		$pos = strpos($line,$term);
						if ($pos !== false) {
						    $line = substr_replace($line,"parent::__construct",$pos,strlen($term));
						}
						if($tmp_line == $line){
							print "Not changed.\n";
						} else {
							print "Changed\n";
						}
					break;
					
					case "replace_current_class_construct_php4_to_php5_call":
						//Replace first occurrence of class name to __constructor
						$term = "\$this->".$function_name;
			    		$pos = strpos($line,$term);
						if ($pos !== false) {
						    $line = substr_replace($line,"\$this->__construct",$pos,strlen($term));
						}
						if($tmp_line == $line){
							print "Not changed.\n";
						} else {
							print "Changed\n";
						}
					break;
					
					
					case "replace_extends_construct_php4_to_php5_static_call":
						//Replace first occurrence of class name to __constructor
						$term = "parent::".$function_name;
			    		$pos = strpos($line,$term);
						if ($pos !== false) {
						    $line = substr_replace($line,"parent::__construct",$pos,strlen($term));
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