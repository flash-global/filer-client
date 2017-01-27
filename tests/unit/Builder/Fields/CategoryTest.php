<?php
namespace Tests\Fei\Service\Filer\Client\Builder\Fields;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Fei\Service\Filer\Client\Builder\Fields\Category;
use Fei\Service\Filer\Client\Builder\Fields\Context;
use Fei\Service\Filer\Client\Builder\Fields\Filename;
use Fei\Service\Filer\Client\Builder\OperatorBuilder;
use Fei\Service\Filer\Client\Builder\SearchBuilder;

class CategoryTest extends Unit
{
    public function testBuild()
    {
        $builder = new SearchBuilder();
        $category = new Category($builder);

        $category->build(1);

        $this->assertAttributeEquals([
            'category' => 1
        ], 'params', $builder);
    }
}
