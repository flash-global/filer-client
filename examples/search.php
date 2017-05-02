<?php
require __DIR__ . '/../vendor/autoload.php';

use Fei\Service\Filer\Client\Builder\SearchBuilder;
use Fei\Service\Filer\Client\Filer;
use Fei\ApiClient\Transport\BasicTransport;
use Fei\Service\Filer\Entity\File;

$filer = new Filer([Filer::OPTION_BASEURL => 'http://10.7.0.1:8020']);

$filer->setTransport(new BasicTransport());

try {
    $searchBuilder = new SearchBuilder();

    $searchBuilder->category()->equal(File::CATEGORY_IMG);
    $searchBuilder->category()->equal(File::CATEGORY_SUPPLIER);
    $searchBuilder->context()->key('test 1')->equal('test 1');

    $searchBuilder->context()->key('AAA')->equal('AAA');
    $searchBuilder->context()->key('CCC')->equal('CCC');

    $searchBuilder->contextCondition('OR');

    $searchBuilder->context()->key()

    $searchBuilder->uuid()->equal('bck1:30d6a8ed-f9cf-4a6d-a76e-04ec941d1f45');

    $results = $filer->search($searchBuilder);

    echo '<pre>';
    print_r($results);
    echo '</pre>';
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    $previous = $e->getPrevious();
    if ($previous instanceof Guzzle\Http\Exception\ServerErrorResponseException) {
        var_dump($previous->getRequest());
        var_dump($previous->getResponse()->getBody(true));
    }
}
