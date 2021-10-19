# aws-service

[![Latest Stable Version](https://poser.pugx.org/chella/aws-servicess/v/stable)](https://packagist.org/packages/chella/aws-servicess)
[![Total Downloads](https://poser.pugx.org/chella/aws-servicess/downloads)](https://packagist.org/packages/chella/aws-servicess)
[![Latest Unstable Version](https://poser.pugx.org/chella/aws-servicess/v/unstable)](https://packagist.org/packages/chella/aws-servicess)
[![License](https://poser.pugx.org/chella/aws-servicess/license)](https://packagist.org/packages/chella/aws-servicess)

application with Slim Framework

#### Installation

Use [Composer](https://getcomposer.org/)

#### Do Composer Require as below

```
composer require chella/aws-servicess
```

#### Setup

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

#### Here you go

Happy routing.
