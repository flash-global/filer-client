<?php
require __DIR__ . '/../vendor/autoload.php';

use Fei\Service\Filer\Client\Builder\SearchBuilder;
use Fei\Service\Filer\Client\Filer;
use Fei\ApiClient\Transport\BasicTransport;

$filer = new Filer([Filer::OPTION_BASEURL => 'http://172.17.0.1:8003']);

$filer->setTransport(new BasicTransport());

try {
    $searchBuilder = new SearchBuilder();
    $searchBuilder->category()->equal(2);
    $searchBuilder->context()->key('test 1')->equal('test 1');
    $searchBuilder->filename()->equal('avatar.png');

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
