<?php
/*
After updating the local code, if you need a new dockerfile for a new version, follow these steps:

Open the file build.php, into the folders array, add the new folder version name and into the versions array put the respectively php version that the folder needs
After all that, run php build.php (needs the php on the machine)
*/

// Lista de versões do PHP
$folders = ['7.0', '7.1', '7.2', '7.3', '7.4', '8.0', '8.1', '8.2', '8.3'];
$versions = ['5.6', '7.0', '7.1', '7.2', '7.3', '7.4', '8.0', '8.1', '8.2'];
$build_docker = false;

foreach ($versions as $i => $version) {
    $folderVersion = $folders[$i];
    $dockerfile_path = __DIR__ . "/migration/to_{$folderVersion}/Dockerfile";
    if(is_file($dockerfile_path)){
        print "Could not overwrite ".$dockerfile_path."\n";
        continue;
    }
    
    $dockerfile_content = "
    FROM php:{$version}-apache

    WORKDIR /var/www/html
    
    COPY . .
    
    EXPOSE 80
    
    CMD [\"apache2-foreground\"]";

    file_put_contents($dockerfile_path, $dockerfile_content);

    if($build_docker){
        $output = [];
        exec("docker build -t meuapp-php:{$version} -f {$dockerfile_path} .", $output);
        echo implode("\n", $output) . "\n";
    }
}