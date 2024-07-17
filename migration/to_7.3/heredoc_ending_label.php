<?php
//phpinfo();
#https://www.php.net/manual/en/migration73.incompatible.php#migration73.incompatible.core.heredoc-nowdoc

include __DIR__."/../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'].'/SmartDoc4/testFolder');
$to_change = [];
while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);
    reset($tokens);

    $heredocContent = [];

    while($token = $PHPParser->_next($tokens)) {

        if($token[0] == "T_START_HEREDOC") {
            $_token = $token;

            if(!empty($heredocContent)) $heredocContent = [];

            $token = $PHPParser->_next($tokens, ['T_WHITESPACE']);

            $heredocStartTag = $token[2];

            while($token = $PHPParser->_next($tokens, ['DRALL_NEW_LINE', 'T_WHITESPACE'])) {

                if($token[1] == ";") {
                    break;
                }

                $tokenName = preg_replace('/[^a-zA-Z]/', '',$token[1]);

                $heredocContent[] = $token[1].":".$token[2];
            }

            $heredocEndingTag = explode(":", array_pop($heredocContent));

            foreach($heredocContent as $key => $heredoc) {
                $explode = explode(":", $heredoc);

                $heredocContent[$key] = preg_replace('/[^a-zA-Z]/', '',$explode[0]);
            }

            if(in_array($heredocEndingTag[0], $heredocContent)) {
                $to_change[] = $file.":".$heredocStartTag.":change_start_heredoc_tag";
                $to_change[] = $file.":".$heredocEndingTag[1].":change_end_heredoc_tag";
            }
        }
    }
}


//Actions must to be ordered by affected file line
sort($to_change);

while($change = array_shift($to_change)){
//    print "Starting ".$change."\n";
    list($file,$file_line,$action) = explode(":",$change);

    $handle = fopen($file, "r");
    $writing = fopen($file.'.tmp', 'w');
    if ($handle) {
        $read_line = 1;
        while (($line = fgets($handle)) !== false) {
            if($file_line == $read_line){
                $tmp_line = $line;
//                print "Must to change this line on action ".$action."\n";

                switch($action){
                    case "change_start_heredoc_tag":
                        $explode = explode("=", $line);
                        $newLine = $explode[0]." = <<<HEREDOC\n";

                        $line = str_replace($line, $newLine, $line);

                        if($tmp_line == $line){
                            print "Not changed.\n";
                        } else {
                            print "Changed\n";
                        }
                        break;

                    case "change_end_heredoc_tag":
                        $newLine = "HEREDOC;\n";

                        $line = str_replace($line, $newLine, $line);

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

//    print "Ending ".$change."\n";
}