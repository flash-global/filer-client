<?php
namespace Tests\Fei\Service\Filer\Client;

use Codeception\Test\Unit;
use Fei\Service\Filer\Client\Filer;
use Fei\Service\Filer\Client\Service\FileWrapper;
use Fei\Service\Filer\Entity\File;

class FileWrapperTest extends Unit
{
    public function testFilerAccessors()
    {
        $expected = new Filer([]);

        $fileWrapper = new FileWrapper($expected);

        $this->assertEquals($expected, $fileWrapper->getFiler());
        $this->assertAttributeEquals($fileWrapper->getFiler(), 'filer', $fileWrapper);
    }

    public function testGetDataWhenDataAreAlreadyLoaded()
    {
        $filerWrapper = new FileWrapper(new Filer());
        $filerWrapper->setData('fake-data');

        $this->assertEquals('fake-data', $filerWrapper->getData());
    }

    public function testGetDataWhenDataAreNotAlreadyLoaded()
    {
        $fileMock = $this->getMockBuilder(File::class)->setMethods(['getData'])->getMock();
        $fileMock->expects($this->once())->method('getData')->willReturn('fake-data');

        $filerMock = $this->getMockBuilder(Filer::class)->setMethods(['getFileBinary'])->getMock();
        $filerMock->expects($this->once())->method('getFileBinary')->willReturn($fileMock);

        $filerWrapper = new FileWrapper($filerMock);

        $this->assertEquals('fake-data', $filerWrapper->getData());
    }
}
