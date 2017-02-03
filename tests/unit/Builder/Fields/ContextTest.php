<?php
namespace Tests\Fei\Service\Filer\Client\Builder\Fields;

use Codeception\Test\Unit;
use Fei\Service\Filer\Client\Builder\Fields\Context;
use Fei\Service\Filer\Client\Builder\Fields\Filename;
use Fei\Service\Filer\Client\Builder\OperatorBuilder;
use Fei\Service\Filer\Client\Builder\SearchBuilder;

class ContextTest extends Unit
{
    public function testBuild()
    {
        $builder = new SearchBuilder();
        $context = new Context($builder);

        $context->key('my_key');
        $context->build('fake', '=');

        $this->assertAttributeEquals([
            'context_value' => ['fake'],
            'context_operator' => ['='],
            'context_key' => ['my_key']
        ], 'params', $builder);
    }

    public function testKey()
    {
        $builder = new SearchBuilder();

        $context = new Context($builder);
        $res = $context->key('my_key');

        $this->assertEquals('my_key', $context->getInCache());
        $this->assertInstanceOf(OperatorBuilder::class, $res);
    }
}
