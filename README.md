# aws-service

[![Latest Stable Version](https://poser.pugx.org/chella/aws-services/v/stable)](https://packagist.org/packages/chella/aws-services)
[![Total Downloads](https://poser.pugx.org/chella/aws-services/downloads)](https://packagist.org/packages/chella/aws-services)
[![Latest Unstable Version](https://poser.pugx.org/chella/aws-services/v/unstable)](https://packagist.org/packages/chella/aws-services)
[![License](https://poser.pugx.org/chella/aws-services/license)](https://packagist.org/packages/chella/aws-services)

application with Slim Framework

#### Installation

Use [Composer](https://getcomposer.org/)

#### Do Composer Require as below

```
composer require chella/aws-services
```

#### Setup S3

```use Tnq\AwsService\App;

try {
    $s3 = App::get("fileSystemS3", [
        "key"=>"###",
        "secret" => "###",
        "bucket" => "###",
        "region" => "###"
    ]);
}
catch (Exception $e) {
    echo $e->getMessage() . "\n";
}

```

#### Setup Minio

```use Tnq\AwsService\App;

try {
    $minio = App::get("minioFileSystem", [
        'version' => 'latest',
        'region'  => 'us-east-1',
        'endpoint' => 'http://localhost:9000',
        'use_path_style_endpoint' => true,
        'credentials' => [
            'key' => '###',
            'secret' => '###',
        ],
        'bucket' => '###',
    ]);

    ## for writing content over minio server
    ## 1st arg is filename
    ## 2nd arg is content
    $minio->write('sample.txt', 'hello minio test');

    ## for read content over minio server
    ## 1st arg is filename
    echo $minio->read('sample.txt');

    ## for delete content over minio server
    ## 1st arg is filename
    echo $minio->delete('sample.txt');
}
catch (Exception $e) {
    echo $e->getMessage() . "\n";
}

```

### Minio docker documentation

https://registry.hub.docker.com/r/minio/minio/

#### Here you go

Happy coding.
