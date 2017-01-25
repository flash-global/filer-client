<?php

use Fei\ApiClient\Transport\BasicTransport;
use Fei\Service\Filer\Client\Filer;
use Fei\Service\Filer\Entity\File;

require __DIR__ . '/../vendor/autoload.php';

$filer = new Filer([Filer::OPTION_BASEURL => 'http://127.0.0.1:8080']);

$filer->setTransport(new BasicTransport());

try {
    $uuid = $filer->upload(
        (new File())
            ->setCategory(File::CATEGORY_IMG)
            ->setContexts(['test 1' => 'test 1', 'test 2' => 'test 2'])
            ->setFile(new SplFileObject(__DIR__ . '/../tests/_data/avatar.png'))
    );

    $uuid = $filer->upload(
        (new File())
            ->setCategory(File::CATEGORY_IMG)
            ->setUuid($uuid)
            ->setFile(new SplFileObject(__DIR__ . '/../tests/_data/capture.png')),
        Filer::NEW_REVISION
    );

    $uuid = $filer->upload(
        (new File())
            ->setCategory(File::CATEGORY_IMG)
            ->setUuid($uuid)
            ->setFile(new SplFileObject(__DIR__ . '/../tests/_data/capture.png')),
        Filer::NEW_REVISION
    );

    $uuid = $filer->upload(
        (new File())
            ->setCategory(File::CATEGORY_IMG)
            ->setUuid($uuid)
            ->setFile(new SplFileObject(__DIR__ . '/../tests/_data/capture.png')),
        Filer::NEW_REVISION
    );

    echo $uuid . PHP_EOL;

    // delete the file and all it's revisions
    $filer->delete($uuid) . PHP_EOL;
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    $previous = $e->getPrevious();
    if ($previous instanceof Guzzle\Http\Exception\ServerErrorResponseException) {
        var_dump($previous->getRequest());
        var_dump($previous->getResponse()->getBody(true));
    }
}
