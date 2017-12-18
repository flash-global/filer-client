<?php

use Fei\ApiClient\Transport\BasicTransport;
use Fei\Service\Filer\Client\Exception\FilerException;
use Fei\Service\Filer\Client\Filer;
use Fei\Service\Filer\Entity\File;
use GuzzleHttp\Psr7\Response;

include __DIR__ . '/../vendor/autoload.php';

$chunkSize = 20 * 1024 * 1024;

$filer = new Filer([Filer::OPTION_BASEURL => 'http://127.0.0.1:8020']);
$filer->setTransport(new BasicTransport());

try {
    $file = (new File())
        ->setCategory(File::CATEGORY_IMG)
        ->setContexts(['test 1' => 'test 1', 'test 2' => 'test 2'])
        ->setFilename('avatar.png')
        ->setFile(new SplFileObject(__DIR__ . '/../tests/_data/avatar.png'));

    $uuid = $filer->uploadByChunks(
        $file,
        null,
        function (Response $response, $index) {
            var_dump($index);
        }
    );
    echo $uuid . PHP_EOL;

    $file = (new File())
        ->setCategory(File::CATEGORY_IMG)
        ->setContexts(['test 1' => 'test 1', 'test 2' => 'test 2', 'test 3' => 'test 3'])
        ->setFile(new SplFileObject(__DIR__ . '/../tests/_data/capture.png'))
        ->setUuid($uuid);

    $uuid = $filer->uploadByChunks(
        $file,
        Filer::NEW_REVISION
    );
    echo $uuid . PHP_EOL;

    $file = $filer->retrieve($uuid);

    $file->setContexts(['test 1' => 'New value']);

    $uuid = $filer->uploadByChunks(
        $file
    );

    echo $uuid . PHP_EOL;

} catch (FilerException $e) {
    echo $e->getMessage() . PHP_EOL;
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}