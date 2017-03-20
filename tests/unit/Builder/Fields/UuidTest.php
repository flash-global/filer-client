<?php
namespace Tests\Fei\Service\Filer\Client\Builder\Fields;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Fei\Service\Filer\Client\Builder\Fields\Category;
use Fei\Service\Filer\Client\Builder\Fields\Context;
use Fei\Service\Filer\Client\Builder\Fields\Filename;
use Fei\Service\Filer\Client\Builder\Fields\Uuid;
use Fei\Service\Filer\Client\Builder\OperatorBuilder;
use Fei\Service\Filer\Client\Builder\SearchBuilder;

class UuidTest extends Unit
{
    public function testBuild()
    {
        $builder = new SearchBuilder();
        $category = new Uuid($builder);

        $category->build('fake');

        $this->assertAttributeEquals([
            'uuid' => 'fake',
        ], 'params', $builder);
    }

    public function testBuilderAccessors()
    {
        $searchBuilder = new SearchBuilder();
        $uuid = new Uuid($searchBuilder);

        $uuid->setBuilder($searchBuilder);
        $this->assertEquals($uuid->getBuilder(), $searchBuilder);
        $this->assertAttributeEquals($uuid->getBuilder(), 'builder', $uuid);
    }

    public function testEqual()
    {
        $uuid = Stub::make(Uuid::class, [
            'build' => Stub::once()
        ]);

        $this->assertEquals($uuid, $uuid->equal('fake-uuid'));
    }
}
