<?php
require_once 'vendor/autoload.php';

use Chella\AwsService\App;

try {
    $s3 = App::get("minioFileSystem", [
        'version' => 'latest',
        'region'  => 'us-east-1',
        'endpoint' => 'http://localhost:9000',
        'use_path_style_endpoint' => true,
        'credentials' => [
            'key' => 'appuser',
            'secret' => 'Co5noh8bi4ohP4al',
        ],
        'bucket' => 'test',
    ]);
    $s3->write("sample.txt", "Hello Minio test!!!");
    echo $s3->read("sample.txt");
    // $s3->delete("sample.txt");
}
catch (Exception $e) {
    echo $e->getMessage() . "\n";
}
