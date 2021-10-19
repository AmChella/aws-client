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
    // $s3->write("sample.txt", "test");
    echo $s3->delete("sample.txt");
}
catch (Exception $e) {
    echo $e->getMessage() . "\n";
}

// date_default_timezone_set('America/Los_Angeles');
// require 'vendor/autoload.php';

// $s3 = new Aws\S3\S3Client([
//         'version' => 'latest',
//         'region'  => 'us-east-1',
//         'endpoint' => 'http://localhost:9000',
//         'use_path_style_endpoint' => true,
//         'credentials' => [
//                 'key'    => 'appuser',
//                 'secret' => 'Co5noh8bi4ohP4al',
//             ],
// ]);


// Send a PutObject request and get the result object.
// $insert = $s3->putObject([
//      'Bucket' => 'test',
//      'Key'    => 'sample.txt',
//      'Body'   => 'Hello from MinIO!!'
// ]);

// Download the contents of the object.
// $retrive = $s3->getObject([
//      'Bucket' => 'test',
//      'Key'    => 'sample.txt',
//      'SaveAs' => 'testkey_local'
// ]);

// Print the body of the result by indexing into the result object.
// echo $retrive['Body'];