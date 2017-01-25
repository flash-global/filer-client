<?php

use Fei\ApiClient\Transport\BasicTransport;
use Fei\ApiClient\Transport\BeanstalkProxyTransport;
use Fei\Service\Filer\Client\Filer;
use Fei\Service\Filer\Entity\File;

require __DIR__ . '/../vendor/autoload.php';

$filer = new Filer([Filer::OPTION_BASEURL => 'http://127.0.0.1:8080']);

$filer->setTransport(new BasicTransport());

$proxy = new BeanstalkProxyTransport();
$proxy->setPheanstalk(new \Pheanstalk\Pheanstalk('127.0.0.1'));

$filer->setAsyncTransport($proxy);

try {
    //$file = Filer::embed('/Users/vincent/Downloads/100MB.pdf');
    $file = Filer::embed(__DIR__ . '/../tests/_data/avatar.png');
    $file->setCategory(File::CATEGORY_IMG)
        ->setContexts(['test 1' => 'test 1', 'test 2' => 'test 2']);

    $filer->upload($file, Filer::ASYNC_UPLOAD);

    echo $file->getUuid() . PHP_EOL;

    $file->setFile(new SplFileObject(__DIR__ . '/../tests/_data/capture.png'));

    $filer->upload($file, Filer::ASYNC_UPLOAD|Filer::NEW_REVISION);
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    $previous = $e->getPrevious();
    if ($previous instanceof Guzzle\Http\Exception\ServerErrorResponseException) {
        var_dump($previous->getRequest());
        var_dump($previous->getResponse()->getBody(true));
    }
}
