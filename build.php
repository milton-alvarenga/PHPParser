<?php

// Lista de versões do PHP
$folders = ['7.0', '7.1', '7.2', '7.3', '7.4', '8.0', '8.1', '8.2', '8.3'];
$versions = ['5.6', '7.0', '7.1', '7.2', '7.3', '7.4', '8.0', '8.1', '8.2'];

foreach ($versions as $i => $version) {
    
    $dockerfile_content = "
    FROM php:{$version}-apache

    WORKDIR /var/www/html
    
    COPY . .
    
    EXPOSE 80
    
    CMD [\"apache2-foreground\"]";

    $folderVersion = $folders[$i];

    $dockerfile_path = __DIR__ . "/migration/to_{$folderVersion}/Dockerfile";
    file_put_contents($dockerfile_path, $dockerfile_content);

    $output = [];
    exec("docker build -t meuapp-php:{$version} -f {$dockerfile_path} .", $output);
    echo implode("\n", $output) . "\n";
}
