# aws-service

[![Latest Stable Version](https://poser.pugx.org/tnq/aws-services/v/stable)](https://packagist.org/packages/tnq/aws-services)
[![Total Downloads](https://poser.pugx.org/tnq/aws-services/downloads)](https://packagist.org/packages/tnq/aws-services)
[![Latest Unstable Version](https://poser.pugx.org/tnq/aws-services/v/unstable)](https://packagist.org/packages/tnq/aws-services)
[![License](https://poser.pugx.org/tnq/aws-services/license)](https://packagist.org/packages/tnq/aws-services)

application with Slim Framework

#### Installation

Use [Composer](https://getcomposer.org/)

#### Do Composer Require as below
```
composer require tnq/aws-services
```

#### Setup

``` use Tnq\AwsService\App;

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

#### Here you go

Happy routing.
