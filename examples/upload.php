<?php

use Fei\ApiClient\Transport\BasicTransport;
use Fei\Service\Filer\Client\Filer;
use Fei\Service\Filer\Entity\File;

require __DIR__ . '/../vendor/autoload.php';

$filer = new Filer([Filer::OPTION_BASEURL => 'http://127.0.0.1:8020']);

$filer->setTransport(new BasicTransport());

try {
    $uuid = $filer->upload(
        (new File())
            ->setCategory(File::CATEGORY_IMG)
            ->setContexts(['test 1' => 'test 1', 'test 2' => 'test 2'])
            ->setFilename('avatar.png')
            ->setFile(new SplFileObject(__DIR__ . '/../tests/_data/avatar.png'))
    );

    echo (is_string($uuid) ? $uuid : (string) $uuid->getBody()) . PHP_EOL;

    $uuid = $filer->upload(
        (new File())
            ->setCategory(File::CATEGORY_IMG)
            ->setUuid($uuid)
            ->setFile(new SplFileObject(__DIR__ . '/../tests/_data/capture.png'))
            ->setContexts(['test 1' => 'test 1' ,'test 3' => 'test 1', 'test 4' => 'test 2']),
        Filer::NEW_REVISION
    );

    echo (is_string($uuid) ? $uuid : (string) $uuid->getBody()) . PHP_EOL;

    $file = $filer->retrieve($uuid);
    $file->setContexts(['test 1' => 'New value']);
    $filer->upload($file);

    echo (is_string($uuid) ? $uuid : (string) $uuid->getBody()) . PHP_EOL;

} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    $previous = $e->getPrevious();
    if ($previous instanceof Guzzle\Http\Exception\ServerErrorResponseException) {
        var_dump($previous->getRequest());
        var_dump($previous->getResponse()->getBody(true));
    }
}
