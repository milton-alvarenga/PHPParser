<?php
//https://www.php.net/manual/en/migration70.incompatible.php#migration70.incompatible.variable-handling

require '../../lib/PHPParser.class.php';

$PHPParser = new PHPParser();

$files = $PHPParser->get_files("/tmp/system_name/");


$to_change = [];

while($file = array_shift($files)){
	print $file."\n";
	$tokens = $PHPParser->get_tokens($file));
	
	reset($tokens);
	$inside_php_code = false;
	$line_content = "";
	$affected = false;
	while($token = $PHPParser->_next($tokens,[],$line_content)){
		if($token[0] == "T_OPEN_TAG"){
			$inside_php_code = true;
		}
		if(!$inside_php_code){
			continue;
		}
		
		if($token[0] == "T_CLOSE_TAG"){
			$inside_php_code = false;
			continue;
		}
		
		//Is it a new line?
		if(
			($token[0] == "DRALL_STRUCT" && $token[1] == ";")
			||
			($token[0] == "DRALL_NEW_LINE")
		){
			if($affected){
				$to_change[] = $file.":".$line_content.":line".$token[2].":variable_affected_to_the_new_parse_mode";
			}
			$line_content = "";
			$affected = false;
			continue;
		}
		//print_r($token);
		                        // old meaning            // new meaning
		/*
		$foo->$bar['baz']       $foo->{$bar['baz']}       ($foo->$bar)['baz']
		$foo->$bar['baz']()     $foo->{$bar['baz']}()     ($foo->$bar)['baz']()
		Foo::$bar['baz']()      Foo::{$bar['baz']}()      (Foo::$bar)['baz']()
		$$foo['bar']['baz']     ${$foo['bar']['baz']}     ($$foo)['bar']['baz']
		*/
		if($token[0] == "T_VARIABLE"){
			$token = $PHPParser->_next($tokens,["T_WHITESPACE"],$line_content);
			if($token[0] == "T_OBJECT_OPERATOR"){
				$token = $PHPParser->_next($tokens,["T_WHITESPACE"],$line_content);
				if($token[0] == "T_VARIABLE"){
					$token = $PHPParser->_next($tokens,["T_WHITESPACE"],$line_content);
					if($token[0] == "DRALL_STRUCT" && $token[1] == "["){
						$affected = true;
					}
				}
			}
		} else if($token[0] == "T_STRING"){
			$token = $PHPParser->_next($tokens,["T_WHITESPACE"],$line_content);
			if(in_array($token[0],["T_PAAMAYIM_NEKUDOTAYIM","T_DOUBLE_COLON"])){
				$token = $PHPParser->_next($tokens,["T_WHITESPACE"],$line_content);
				if($token[0] == "T_VARIABLE"){
					$token = $PHPParser->_next($tokens,["T_WHITESPACE"],$line_content);
					if($token[0] == "DRALL_STRUCT" && $token[1] == "["){
						$affected = true;
					}
				}
			}
		//Get $$
		} else if($token[0] == "DRALL_STRUCT" && $token[1] == "$"){
			$token = $PHPParser->_next($tokens,["T_WHITESPACE"],$line_content);
			if(
				in_array($token[0],["T_VARIABLE"],$line_content)
			){
				$token = $PHPParser->_next($tokens,["T_WHITESPACE"],$line_content);
				if($token[0] == "DRALL_STRUCT" && $token[1] == "["){
					$affected = true;
				}
			}
		}
	}
	

/*
if($token[0] == "T_CLASS")
if($token[0] == "T_WHITESPACE")
if($token[0] == "T_STRING")
if($token[0] == "T_EXTENDS")
if($token[0] == "T_FUNCTION")
if($token[0] == "T_OBJECT_OPERATOR")
if($token[0] == "T_VARIABLE")
if($token[0] == "T_STRING" && $token[1] == "parent")
if($token[0] == "T_PAAMAYIM_NEKUDOTAYIM"
$token[0] == "T_DOUBLE_COLON")
*/
}
print_r($to_change);
die();

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