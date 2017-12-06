<?php

use Fei\ApiClient\Transport\BasicTransport;
use Fei\Service\Filer\Client\Exception\FilerException;
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
            ->setFile(new SplFileObject(__DIR__ . '/../tests/_data/avatar.png'))
    );

    echo $uuid . PHP_EOL;

    // save the file at this location on the local server
    $filer->save($uuid, __DIR__ . '/../tests/_data/saveAs');
    $filer->save($uuid, __DIR__ . '/../tests/_data/saveAs/', 'file.png');
} catch (FilerException $e) {
    echo $e->getMessage() . PHP_EOL;
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}
