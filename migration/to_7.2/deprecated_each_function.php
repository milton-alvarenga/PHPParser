<?php

#https://www.php.net/manual/en/migration72.deprecated.php#migration72.deprecated.each-function

include "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT']."/SmartDoc4/testFolder");

$to_change = [];

while($file = array_pop($files)) {
    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);
    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_WHILE") {
            $while_line = $token[2];

            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
            if ($token[0] == "DRALL_STRUCT" && $token[1] == "(") {
                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                if ($token[0] == "T_VARIABLE") {
                    $variable_name = $token[1];

                    $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                    if ($token[1] == "=") {
                        $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                        if ($token[0] == "T_STRING" && $token[1] == "each") {
                            $token = $PHPParser->_next($tokens, ['T_WHITESPACE', 'DRALL_STRUCT']);
                            $foreach_line = "foreach($variable_name as " . $token[1] . " => \$key) {";
                            $to_change[] = $file . ":" . $while_line . ":" . $foreach_line;
                        }
                    }
                }
                elseif ($token[0] == "T_LIST") {
                    $token = $PHPParser->_next($tokens);
                    if ($token[1] == "(") {
                        $variables = array();
                        $array_name = "";
                        while ($token = $PHPParser->_next($tokens)) {
                            if ($token[0] == "T_VARIABLE") {
                                $variables[] = $token[1];
                            } elseif ($token[0] == "DRALL_STRUCT" && $token[1] == ",") {
                                continue;
                            } elseif ($token[0] == "DRALL_STRUCT" && $token[1] == ")") {
                                $token = $PHPParser->_next($tokens, ['T_WHITESPACE', 'DRALL_STRUCT']);

                                if ($token[0] == "T_STRING" && $token[1] == "each") {
                                    $token = $PHPParser->_next($tokens, ['T_WHITESPACE', 'DRALL_STRUCT']);
                                    $array_name = $token[1];
                                }

                                if(!$array_name) $array_name = isset($variables[1]) ? $variables[1] : $variables[0];

                                if(count($variables) > 1) {
                                    $foreach_line = "foreach($variables[1] as $array_name => $variables[0]) {";
                                } else {
                                    $foreach_line = "foreach($variables[0] as $array_name) {";
                                }

                                $to_change[] = $file . ":" . $while_line . ":" . $foreach_line;
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
}

sort($to_change);

while($change = array_shift($to_change)){
    list($file,$line_number,$new_line) = explode(":",$change);
    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $current_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($current_line == $line_number){
                $line = $new_line . "\n";
            }
            fputs($writing, $line);
            $current_line++;
        }
        fclose($handle);
        fclose($writing);
        rename($file.'.tmp', $file);
    } else {
        // Erro ao abrir o arquivo.
        throw new Exception("Could not open file ".$file."\n");
    }
}