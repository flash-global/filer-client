<?php
namespace Tests\Fei\Service\Filer\Client\Builder;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Fei\Service\Filer\Client\Builder\Fields\Category;
use Fei\Service\Filer\Client\Builder\Fields\Context;
use Fei\Service\Filer\Client\Builder\Fields\Filename;
use Fei\Service\Filer\Client\Builder\OperatorBuilder;
use Fei\Service\Filer\Client\Builder\SearchBuilder;

class OperatorBuilderTest extends Unit
{
    public function testLike()
    {
        $builder = Stub::make(OperatorBuilder::class);

        $result = $builder->like('fake');
        $this->assertInstanceOf(OperatorBuilder::class, $result);
    }

    public function testBeginsWith()
    {
        $builder = Stub::make(OperatorBuilder::class);

        $result = $builder->beginsWith('fake');
        $this->assertInstanceOf(OperatorBuilder::class, $result);
    }

    public function testEndsWith()
    {
        $builder = Stub::make(OperatorBuilder::class);

        $result = $builder->endsWith('fake');
        $this->assertInstanceOf(OperatorBuilder::class, $result);
    }


    public function testEqual()
    {
        $builder = Stub::make(OperatorBuilder::class);

        $result = $builder->equal('fake');
        $this->assertInstanceOf(OperatorBuilder::class, $result);
    }

    public function testInCacheAccessors()
    {
        $builder = Stub::make(OperatorBuilder::class);

        $builder->setIncache('fake');
        $this->assertEquals($builder->getInCache(), 'fake');
        $this->assertAttributeEquals($builder->getInCache(), 'in_cache', $builder);
    }
}
