<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.xmlrpc

include "../../lib/PHPParser.class.php";

$PHPParser = new PHPParser();

$files = $PHPParser->get_files($_SERVER['DOCUMENT_ROOT'].'/SmartDoc4/testFolder');

$forbiddenFunctions = [
    'xmlrpc_decode_request',
    'xmlrpc_decode',
    'xmlrpc_encode_request',
    'xmlrpc_encode',
    'xmlrpc_get_type',
    'xmlrpc_is_fault',
    'xmlrpc_parse_method_descriptions',
    'xmlrpc_server_add_introspection_data',
    'xmlrpc_server_call_method',
    'xmlrpc_server_create',
    'xmlrpc_server_destroy',
    'xmlrpc_server_register_introspection_callback',
    'xmlrpc_server_register_method',
    'xmlrpc_set_type'
];

$to_change = [];

while($file = array_shift($files)) {

    $tokens = $PHPParser->get_tokens($file);

    reset($tokens);

    while($token = $PHPParser->_next($tokens)){
        if($token[0] == "T_STRING" && in_array($token[1], $forbiddenFunctions)){
            $to_change[] = $file.":".$token[2].":".$token[1].":need_to_install_xml_rpc_support";
        }
    }
}