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

$file = (new File())
    ->setCategory(File::CATEGORY_IMG)
    ->setContexts(['test 1' => 'test 1', 'test 2' => 'test 2'])
    ->setFilename('avatar.png')
    ->setFile(new SplFileObject(__DIR__ . '/../tests/_data/avatar.png'));

try {
    echo $filer->uploadByChunks(
        $file,
        function (Response $response, $index) {
            var_dump($index);
        }
    ) . PHP_EOL;
} catch (FilerException $e) {
    echo $e->getMessage() . PHP_EOL;
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}