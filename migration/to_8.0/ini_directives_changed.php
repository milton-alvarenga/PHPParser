<?php

#https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.core.other

$directory = $_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder";

$iniFiles = glob($directory . '/*.ini');

$to_change = [];

while($file = array_shift($iniFiles)) {
    $lineCounter = 1;

    $config = parse_ini_file($file, true);

    foreach ($config as $key => $value) {
        if($key == 'assert.exception' && $value == 0) {
            $to_change[] = $file.":".$lineCounter.":assert.exception_directive_find_with_0";
        }
        $lineCounter++;
    }
}
var_dump($to_change);
//echo "As diretivas foram removidas dos arquivos .ini.\n";