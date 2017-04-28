<?php
require __DIR__ . '/../vendor/autoload.php';

use Fei\Service\Filer\Client\Builder\SearchBuilder;
use Fei\Service\Filer\Client\Filer;
use Fei\ApiClient\Transport\BasicTransport;
use Fei\Service\Filer\Entity\File;
//$proxy = new BeanstalkProxyTransport();
//$proxy->setPheanstalk(new Pheanstalk($serviceManager->get('config')->get('app', 'pheanstalk-ip')));
//
//$service = new Filer();


echo urldecode("http://filer.test.flash-global.net/api/files?criterias=%7B%22category%22%3A%5B%2211%22%5D%2C%22context_value%22%3A%5B%22%28%27IN1704CFC5%27%2C%27RG1704CF4H%27%29%22%5D%2C%22context_operator%22%3A%5B%22in%22%5D%2C%22context_key%22%3A%5B%22contextKey%22%5D%7D ");die;
$filer = new Filer([Filer::OPTION_BASEURL => 'http://filer.test.flash-global.net/']);
$filer->setTransport(new BasicTransport());
$searchBuilder = new SearchBuilder();
$searchBuilder->category()->equal(File::CATEGORY_CLIENT);
$searchBuilder->context()->key('context')->equal('dossier');
$searchBuilder->context()->key('contextKey')->in(['IN1704CFC5','RG1704CF4H']);
$fw = $filer->search($searchBuilder);
var_dump($fw);
/*
$filer->setTransport(new BasicTransport());

try {
    $searchBuilder = new SearchBuilder();

    $searchBuilder->category()->equal(File::CATEGORY_IMG);
    $searchBuilder->category()->equal(File::CATEGORY_SUPPLIER);
    $searchBuilder->context()->key('test 1')->equal('test 1');

    $searchBuilder->context()->key('AAA')->equal('AAA');
    $searchBuilder->context()->key('CCC')->equal('CCC');

    $searchBuilder->contextCondition('OR');

    $searchBuilder->context()->key('numeroordre')->in([111,222]);

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
*/