<?php

#https://www.php.net/manual/en/migration71.incompatible.php#migration71.incompatible.removed-ini-directives

$directory = $_SERVER['DOCUMENT_ROOT'] . "/SmartDoc4/testFolder";

$iniFiles = glob($directory . '/*.ini');

$removedIniDirectives = [
    'session.entropy_file',
    'session.entropy_length',
    'session.hash_function',
    'session.hash_bits_per_character'
];

$to_change = [];

while($file = array_shift($iniFiles)) {

    $content = file_get_contents($file);

    $lines = explode("\n", $content);

    foreach ($removedIniDirectives as $directive) {
        $lines = array_filter($lines, function($line) use ($directive) {
            return strpos($line, $directive) === false;
        });
    }
    
    $newContent = implode("\n", $lines);

    file_put_contents($file, $newContent);
}

echo "As diretivas foram removidas dos arquivos .ini.\n";