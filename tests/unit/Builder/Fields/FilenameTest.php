<?php
namespace Tests\Fei\Service\Filer\Client\Builder\Fields;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Fei\Service\Filer\Client\Builder\Fields\Category;
use Fei\Service\Filer\Client\Builder\Fields\Context;
use Fei\Service\Filer\Client\Builder\Fields\Filename;
use Fei\Service\Filer\Client\Builder\OperatorBuilder;
use Fei\Service\Filer\Client\Builder\SearchBuilder;

class FilenameTest extends Unit
{
    public function testBuild()
    {
        $builder = new SearchBuilder();
        $category = new Filename($builder);

        $category->build('fake');

        $this->assertAttributeEquals([
            'filename' => 'fake',
            'filename_operator' => '='
        ], 'params', $builder);
    }
}
