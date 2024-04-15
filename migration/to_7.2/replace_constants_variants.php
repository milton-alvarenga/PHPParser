<?php

#https://www.php.net/manual/en/migration72.deprecated.php#migration72.deprecated.INTL_IDNA_VARIANT_2003-variant

include "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT']."/SmartDoc4/testFolder");
$to_change = [];
$willChange = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)) {
        if($token[0] == "T_STRING") {
            if($token[1] == "idn_to_utf8" || $token[1] == "idn_to_ascii") {
                $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);
                if($token[0] == "DRALL_STRUCT" && $token[1] == "(") {

                    while($token = $PHPParser->_next($tokens)) {
                        if($token[0] == "T_STRING" && $token[1] == "INTL_IDNA_VARIANT_2003") {
                            $willChange[] = $file.":".$token[2].":INTL_IDNA_VARIANT_2003_will_be_replaced_by_ INTL_IDNA_VARIANT_UTS46";
                            $to_change[] = $file.":".$token[2].":".$token[1].":replace_deprecated_constant";
                        }
                        if($token[0] == ")") {
                            break;
                        }
                    }

                }
            }
        }
    }

}

print_r($to_change);
echo "<br>";
print_r($willChange);

//Actions must to be ordered by affected file line
sort($to_change);

while($change = array_shift($to_change)){
    print "Starting ".$change."\n";
    list($file,$file_line,$deprecated_constant,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "replace_deprecated_constant":
                        $term = $deprecated_constant;
                        $pos = strpos($line,$term);
                        if ($pos !== false) {
                            $line = substr_replace($line,"INTL_IDNA_VARIANT_UTS46",$pos,strlen($term));
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
