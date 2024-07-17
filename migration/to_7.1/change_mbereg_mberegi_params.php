<?php

#https://www.php.net/manual/en/migration71.incompatible.php#migration71.incompatible.mbstring

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder");

$to_verify = [];
$to_change = [];
$arrList = [];
$matchesArr = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_VARIABLE") {

            $_token = $token;

            $token = $PHPParser->_next($tokens, ["T_WHITESPACE"]);
            if($token[0] == "DRALL_STRUCT" && $token[1] == "=") {

                $token = $PHPParser->_next($tokens, ["T_WHITESPACE"]);
                if($token[0] == "DRALL_STRUCT" && $token[1] == "[") {
                    $arrList[] = $_token[1];
                }
            }

        }

        if($token[0] == "T_STRING") {

            if($token[1] == "mb_ereg" || $token[1] == "mb_eregi") {

                $token = $PHPParser->_next($tokens);
                if($token[0] == "DRALL_STRUCT" && $token[1] == "(") {

                    $token = $PHPParser->_next($tokens);
                    if($token[0] == "T_CONSTANT_ENCAPSED_STRING") {

                        $token = $PHPParser->_next($tokens);
                        $commaCount = 0;

                        while($token[1] != ")") {
                            if($commaCount == 2 && $token[0] == "T_VARIABLE") {
                                if(in_array($token[1], $arrList)) {
                                    $matchesArr[] = $token[1];
                                    $to_verify[] = $file.":".$token[2].":".$token[1].":array_already_declared";
                                }
                            }

                            $token = $PHPParser->_next($tokens, ["T_WHITESPACE"]);
                            if($token[0] == "T_VARIABLE") $commaCount++;
                        }
                    }
                }
            }
        }

        if($token[0] == "T_IF") {

            $token = $PHPParser->_next($tokens, ["T_WHITESPACE"]);
            if($token[0] == "DRALL_STRUCT" && $token[1] == "(") {

                $token = $PHPParser->_next($tokens);
                if($token[1] == "is_null") {
                    $_token = $PHPParser->_next($tokens, ["DRALL_STRUCT"]);

                    if($_token[0] == "T_VARIABLE" && in_array($_token[1], $matchesArr)) {
                        $to_change[] = $file.":".$token[2].":".$token[1].":change_is_null_to_empty";
                    }
                }

                if($token[0] == "T_VARIABLE") {

                    $varToken = $token;
                    $token = $PHPParser->_next($tokens, ["T_WHITESPACE"]);

                    if($token[0] == "T_IS_EQUAL" || $token[0] == "T_IS_IDENTICAL") {

                        $_token = $PHPParser->_next($tokens, ["T_WHITESPACE"]);
                        if($_token[0] == "T_STRING") {
                            $to_change[] = $file.":".$token[2].":".$varToken[1].":change_equal_to_empty";
                        }

                    }
                }
            }
        }
    }
}

print_r($to_change);

sort($to_change);

while($change = array_shift($to_change)) {
//    print "Starting " . $change . "<br>";
    list($file, $file_line, $operator, $action) = explode(":", $change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');

    if($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if ($file_line == $read_line) {
                $tmp_line = $line;
                print "Must to change this line on action " . $action . "\n";

                switch ($action) {
                    case "change_is_null_to_empty":
                        //Replace is_null to empty
                        $term = $operator;
                        $pos = strpos($line, $term);

                        if ($pos !== false) {
                            $line = substr_replace($line, "empty", $pos, strlen($term));
                        }
                        if ($tmp_line == $line) {
                            print "Not changed.\n";
                        } else {
                            print "Changed\n";
                        }

                        break;

                    case "change_equal_to_empty":
                        //Replace is_null to empty
                        $term = $operator;
                        $pos = strpos($line, $term);
                        $affectedLine = "if(empty($term)) { \n";

                        if ($pos !== false) {
                            $line = str_replace($line, $affectedLine, $line);
                        }
                        if ($tmp_line == $line) {
                            print "Not changed.\n";
                        } else {
                            print "Changed\n";
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
    print "Ending ".$change."\n";

}