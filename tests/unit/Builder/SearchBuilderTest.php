<?php
namespace Tests\Fei\Service\Filer\Client\Builder;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Fei\Service\Filer\Client\Builder\Fields\Category;
use Fei\Service\Filer\Client\Builder\Fields\Context;
use Fei\Service\Filer\Client\Builder\Fields\Filename;
use Fei\Service\Filer\Client\Builder\SearchBuilder;

class SearchBuilderTest extends Unit
{
    public function testFilename()
    {
        $builder = new SearchBuilder();

        $this->assertInstanceOf(Filename::class, $builder->filename());
    }

    public function testCategory()
    {
        $builder = new SearchBuilder();

        $this->assertInstanceOf(Category::class, $builder->category());
    }

    public function testContext()
    {
        $builder = new SearchBuilder();

        $this->assertInstanceOf(Context::class, $builder->context());
    }

    public function testParamsAccessors()
    {
        $builder = new SearchBuilder();
        $builder->setParams(['a' => 'b']);

        $this->assertEquals(['a' => 'b'], $builder->getParams());
        $this->assertAttributeEquals($builder->getParams(), 'params', $builder);
    }

    public function testToCamelCase()
    {
        $builder = new SearchBuilder();

        $this->assertEquals('HelloWorld', $builder->toCamelCase('hello_world'));
        $this->assertEquals('HelloWorld', $builder->toCamelCase('helloWorld'));
    }

    public function testCallMagicMethod()
    {
        $builder = new SearchBuilder();
        
        $this->assertInstanceOf(Filename::class, $builder->__call('filename', null));

        $this->setExpectedException(\Exception::class);

        $builder->fakeMethode();
    }
}
