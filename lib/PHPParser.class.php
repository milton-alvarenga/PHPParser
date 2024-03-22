<?php
class PHPParser {
    function get_files($fullpath_system,$files_to_return='*.{php,inc}'){
        $fullpath_system = $fullpath_system."/";

        return $this->recursive_glob($fullpath_system.$files_to_return,GLOB_BRACE);
    }

    function get_tokens($fullpath_file){
        return token_get_all(file_get_contents($fullpath_file));
    }


    function _next(&$tokens,$ignore_tag=[],&$line_content = null){
        $token = array_shift($tokens);

        if(is_null($token)){
            return $token;
        }
    
        $token = $this->create_new_line_token($tokens,$token);
        
        
        if(!is_null($line_content)){
            $line_content .= $token[1];
        }
        
        while($ignore_tag && in_array($token[0],$ignore_tag)){
            $token = $this->_next($tokens,$ignore_tag);
            
            $token = $this->create_new_line_token($tokens,$token);
            
            if(!is_null($line_content)){
                $line_content .= $token[1];
            }
        }
        return $token;
    }


    function recursive_glob($pattern, $flags = 0) {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->recursive_glob($dir.'/'.basename($pattern), $flags));
        }
        return $files;
    }

    function create_new_line_token(&$tokens,$token){
        if(is_array($token)){
            //Standard pattern of PHP
            //Drall pattern on data use string and do not need to be converted
            if(is_numeric($token[0])){
                $token[0] = token_name($token[0]);
            }
        } else {
            $token = [
                "DRALL_STRUCT"
                ,$token
                ,null
            ];
        }
        
        if($token[0] == "DRALL_NEW_LINE"){
            return $token;
        }
        
        // Split the data up by newlines
        $split_data = preg_split('#(\r\n|\n)#', $token[1], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    
        foreach ($split_data as $data){
            if ($data == "\r\n" || $data == "\n"){
                // This is a new line token
                array_unshift($tokens,["DRALL_NEW_LINE", $data,$token[2]]);
            } else {
                // Add the token under the original token name
                if(!isset($_token)){
                    $_token = [
                        $token[0]
                        ,$data
                        ,$token[2]
                    ];
                    $first = false;
                } else {
                    //Return values to the queue
                    array_unshift($tokens, [
                        $token[0]
                        ,$data
                        ,$token[2]
                    ]);
                }
            }
        }
        
        if(isset($_token)){
            $token = $_token;
            unset($_token);
        }
    
        return $token;
    }
}