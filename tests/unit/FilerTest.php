<?php

namespace Tests\Fei\Service\Filer\Client;

use Codeception\Util\Stub;
use Fei\ApiClient\ApiClientException;
use Fei\ApiClient\RequestDescriptor;
use Fei\ApiClient\ResponseDescriptor;
use Fei\ApiClient\Transport\AsyncTransportInterface;
use Fei\ApiClient\ApiRequestOption;
use Fei\ApiClient\Transport\SyncTransportInterface;
use Fei\ApiClient\Transport\TransportException;
use Fei\Service\Filer\Client\Builder\SearchBuilder;
use Fei\Service\Filer\Client\Exception\FilerException;
use Fei\Service\Filer\Client\Exception\ValidationException;
use Fei\Service\Filer\Client\Filer;
use Fei\Service\Filer\Client\Service\FileWrapper;
use Fei\Service\Filer\Entity\File;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class FilerTest
 *
 * @package Tests\Fei\Service\Filer\Client
 */
class FilerTest extends TestCase
{
    public function testChunkSizeAccessors()
    {
        $filer = new Filer();

        $this->assertEquals(2 * 1024 * 1024, $filer->getChunkSize());

        $filer->setChunkSize(10);

        $this->assertEquals(10, $filer->getChunkSize());
        $this->assertAttributeEquals($filer->getChunkSize(), 'chunkSize', $filer);
    }

    public function testChunkUploadConcurrencyAccessors()
    {
        $filer = new Filer();

        $this->assertEquals(5, $filer->getChunkUploadConcurrency());

        $filer->setChunkUploadConcurrency(10);

        $this->assertEquals(10, $filer->getChunkUploadConcurrency());
        $this->assertAttributeEquals($filer->getChunkUploadConcurrency(), 'chunkUploadConcurrency', $filer);
    }

    public function testUploadFileNotValid()
    {
        $filer = new Filer();

        $this->setExpectedException(ValidationException::class);

        $filer->upload(new File());
    }

    public function testUploadNoAsyncTransport()
    {
        $filer = new Filer();

        $this->setExpectedException(FilerException::class, 'Asynchronous Transport has to be set');

        $filer->upload($this->getValidFileInstance(), Filer::ASYNC_UPLOAD);
    }

    public function testUploadAsyncTransport()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $request = new RequestDescriptor();
        $flag = null;

        $transport = $this->getMockBuilder(AsyncTransportInterface::class)->getMockForAbstractClass();
        $transport->expects($this->once())->method('send')->willReturnCallback(
            function (RequestDescriptor $requestDescriptor, $mFlag) use (&$request, &$flag, $transport) {
                $request = $requestDescriptor;
                $flag = $mFlag;
                return $transport;
            }
        );

        $filer->setAsyncTransport($transport);

        $file = $this->getValidFileInstance();

        $result = $filer->upload($file, Filer::ASYNC_UPLOAD);

        $this->assertNotFalse($flag & ApiRequestOption::NO_RESPONSE);
        $this->assertEquals('PATCH', $request->getMethod());
        $this->assertEquals($request->getUrl(), 'http://url/api/files');
        $this->assertEquals(
            ['file' => \json_encode($file->toArray())],
            $request->getBodyParams()
        );
        $this->assertNull($result);
    }

    public function testUploadAsyncTransportNoUuid()
    {
        $filer = $this->getMockBuilder(Filer::class)
            ->setConstructorArgs([[Filer::OPTION_BASEURL => 'http://url']])
            ->setMethods(['createUuid'])
            ->getMock();
        $filer->expects($this->once())->method('createUuid')->willReturn('bck1:22b52127-7dbd-4294-81f4-8638906c646a');

        $transport = $this->getMockBuilder(AsyncTransportInterface::class)->getMockForAbstractClass();
        $transport->expects($this->once())->method('send')->willReturnCallback(
            function (RequestDescriptor $requestDescriptor, $mFlag) use (&$request, &$flag, $transport) {
                $request = $requestDescriptor;
                $flag = $mFlag;
                return $transport;
            }
        );

        $filer->setAsyncTransport($transport);

        $file = $this->getValidFileInstance();
        $file->setUuid(null);

        $result = $filer->upload($file, Filer::ASYNC_UPLOAD);

        $this->assertNotFalse($flag & ApiRequestOption::NO_RESPONSE);
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals($request->getUrl(), 'http://url/api/files');
        $this->assertEquals(
            ['file' => \json_encode($file->toArray())],
            $request->getBodyParams()
        );
        $this->assertNull($result);
    }

    public function testUploadAsyncTransportFail()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $transport = $this->getMockBuilder(AsyncTransportInterface::class)->getMockForAbstractClass();
        $transport->expects($this->once())->method('send')->willThrowException(new TransportException());

        $this->setExpectedException(FilerException::class);

        $filer->setAsyncTransport($transport);

        $filer->upload($this->getValidFileInstance(), Filer::ASYNC_UPLOAD);
    }

    public function testUploadBasicTransport()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $request = new RequestDescriptor();
        $flag = null;

        $transport = $this->getMockBuilder(SyncTransportInterface::class)->getMockForAbstractClass();
        $transport->expects($this->once())->method('send')->willReturnCallback(
            function (RequestDescriptor $requestDescriptor, $mFlag) use (&$request, &$flag, $transport) {
                $request = $requestDescriptor;
                $flag = $mFlag;
                return (new ResponseDescriptor())->setBody(json_encode([
                    'uuid' => 'test-uuid'
                ]));
            }
        );

        $filer->setTransport($transport);

        $file = $this->getValidFileInstance();
        $file->setUuid(null);

        $result = $filer->upload($file);

        $this->assertTrue($flag === 0);
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals($request->getUrl(), 'http://url/api/files');
        $this->assertEquals(
            ['file' => \json_encode($file->toArray())],
            $request->getBodyParams()
        );
        $this->assertEquals($result, 'test-uuid');
    }

    public function testUploadNewRevisionNoUuid()
    {
        $filer = new Filer();

        $this->setExpectedException(ValidationException::class, 'UUID must be set when adding a new revision');

        $file = $this->getValidFileInstance();
        $file->setUuid(null);

        $filer->upload($file, Filer::NEW_REVISION);
    }

    public function testUploadNewRevision()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $request = new RequestDescriptor();
        $flag = null;

        $transport = $this->getMockBuilder(AsyncTransportInterface::class)->getMockForAbstractClass();
        $transport->expects($this->once())->method('send')->willReturnCallback(
            function (RequestDescriptor $requestDescriptor, $mFlag) use (&$request, &$flag, $transport) {
                $request = $requestDescriptor;
                $flag = $mFlag;
                return $transport;
            }
        );

        $filer->setAsyncTransport($transport);

        $file = $this->getValidFileInstance();

        $filer->upload($file, Filer::ASYNC_UPLOAD|Filer::NEW_REVISION);

        $this->assertNotFalse($flag & ApiRequestOption::NO_RESPONSE);
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals($request->getUrl(), 'http://url/api/files');
        $this->assertEquals($request->getHeader('Content-Type'), 'application/x-www-form-urlencoded');
        $this->assertEquals(
            ['file' => \json_encode($file->toArray())],
            $request->getBodyParams()
        );
    }

    public function testAsyncTransportUpdateLastRevision()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $request = new RequestDescriptor();
        $flag = null;

        $transport = $this->getMockBuilder(AsyncTransportInterface::class)->getMockForAbstractClass();
        $transport->expects($this->once())->method('send')->willReturnCallback(
            function (RequestDescriptor $requestDescriptor, $mFlag) use (&$request, &$flag, $transport) {
                $request = $requestDescriptor;
                $flag = $mFlag;
                return $transport;
            }
        );

        $filer->setAsyncTransport($transport);

        $file = $this->getValidFileInstance();

        $filer->upload($file, Filer::ASYNC_UPLOAD);

        $this->assertNotFalse($flag & ApiRequestOption::NO_RESPONSE);
        $this->assertEquals('PATCH', $request->getMethod());
        $this->assertEquals($request->getUrl(), 'http://url/api/files');
        $this->assertEquals($request->getHeader('Content-Type'), 'application/x-www-form-urlencoded');
        $this->assertEquals(
            ['file' => \json_encode($file->toArray())],
            $request->getBodyParams()
        );
    }

    public function testBasicTransportUpdateLastRevision()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $request = new RequestDescriptor();
        $flag = null;

        $transport = $this->getMockBuilder(SyncTransportInterface::class)->getMockForAbstractClass();
        $transport->expects($this->once())->method('send')->willReturnCallback(
            function (RequestDescriptor $requestDescriptor, $mFlag) use (&$request, &$flag, $transport) {
                $request = $requestDescriptor;
                $flag = $mFlag;
                return $transport;
            }
        );

        $filer->setTransport($transport);

        $file = $this->getValidFileInstance();

        $filer->upload($file);

        $this->assertNotFalse($flag & ApiRequestOption::NO_RESPONSE);
        $this->assertEquals('PATCH', $request->getMethod());
        $this->assertEquals($request->getUrl(), 'http://url/api/files');
        $this->assertEquals($request->getHeader('Content-Type'), 'application/x-www-form-urlencoded');
        $this->assertEquals(
            ['file' => \json_encode($file->toArray())],
            $request->getBodyParams()
        );
    }

    public function testRetrieveNoSyncTransport()
    {
        $filer = new Filer();

        $this->setExpectedException(FilerException::class, 'Synchronous Transport has to be set');

        $filer->retrieve('test');
    }

    public function testRetrieveFileDoesNotExist()
    {
        $transport = $this->getMockBuilder(SyncTransportInterface::class)->getMockForAbstractClass();

        $filer = $this->getMockBuilder(Filer::class)
            ->setMethods(['fetch'])
            ->getMock();

        $filer->setTransport($transport);

        $badResponseException = new RequestException('BadResponseException', new Request('GET', 'test'));
        $apiClientException = new ApiClientException(
            'An error occurred while transporting a request',
            0,
            $badResponseException
        );

        $filerException = new FilerException('File not found', 404, $apiClientException);

        $filer->expects($this->once())
            ->method('fetch')
            ->willThrowException($filerException);

        $this->setExpectedException(FilerException::class, 'File not found');

        $return = $filer->retrieve('abcd:aca798ec-032b-481e-8514-561867d7ecdb');

        $this->assertNull($return);
    }

    public function testRetrieve()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $request1 = new RequestDescriptor();

        $transport = $this->getMockBuilder(SyncTransportInterface::class)->getMockForAbstractClass();
        $transport->expects($this->once())->method('send')->withConsecutive(
            [$this->callback(function (RequestDescriptor $requestDescriptor) use (&$request1) {
                return $request1 = $requestDescriptor;
            })]
        )->willReturnOnConsecutiveCalls(
            (new ResponseDescriptor())->setBody(json_encode([
                "data" => [
                    "id" => 19,
                    "uuid" => "abcd:aca798ec-032b-481e-8514-561867d7ecdb",
                    "revision" => 3,
                    "category" => 3,
                    "created_at" => "2016-12-23T10:01:57+0000",
                    "content_type" => "image/gif",
                    "context" => null,
                ],
                "meta" => [
                    "entity" => "Fei\\Service\\Filer\\Entity\\File"
                ]
            ]))
        );

        $filer->setTransport($transport);

        $result = $filer->retrieve('abcd:aca798ec-032b-481e-8514-561867d7ecdb');

        $this->assertEquals($request1->getMethod(), 'GET');
        $this->assertEquals(
            $request1->getUrl(),
            'http://url/api/files?uuid=' . urlencode('abcd:aca798ec-032b-481e-8514-561867d7ecdb')
        );

        $this->assertEquals(
            (new FileWrapper($filer))
                ->setCreatedAt('2016-12-23T10:01:57+0000')
                ->setId(19)
                ->setUuid('abcd:aca798ec-032b-481e-8514-561867d7ecdb')
                ->setCategory(3)
                ->setRevision(3)
                ->setFiler($filer)
                ->setContentType('image/gif'),
            $result
        );
    }

    public function testDeleteWithoutTransportSet()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $this->setExpectedException(FilerException::class, 'Synchronous Transport has to be set');

        $filer->delete('fake-uuid');
    }

    public function testDeleteEntireFile()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $request = new RequestDescriptor();
        $flag = null;

        $transport = $this->getMockBuilder(SyncTransportInterface::class)->getMockForAbstractClass();
        $transport->expects($this->once())->method('send')->willReturnCallback(
            function (RequestDescriptor $requestDescriptor, $mFlag) use (&$request, &$flag, $transport) {
                $request = $requestDescriptor;
                $flag = $mFlag;
                return (new ResponseDescriptor())->setBody(json_encode([
                    'message' => 'returned-message'
                ]));
            }
        );

        $filer->setTransport($transport);

        $file = $this->getValidFileInstance();

        $result = $filer->delete($file->getUuid());

        $this->assertNotFalse($flag & ApiRequestOption::NO_RESPONSE);
        $this->assertEquals($request->getMethod(), 'DELETE');

        $url = 'http://url/api/files?uuid=' . urlencode($file->getUuid());
        $this->assertEquals($request->getUrl(), $url);
        $this->assertEquals($result, null);
    }

    public function testDeleteOneRevisionOfFileAndItsPreviousRevisions()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $request = new RequestDescriptor();
        $flag = null;

        $transport = $this->getMockBuilder(SyncTransportInterface::class)->getMockForAbstractClass();
        $transport->expects($this->once())->method('send')->willReturnCallback(
            function (RequestDescriptor $requestDescriptor, $mFlag) use (&$request, &$flag, $transport) {
                $request = $requestDescriptor;
                $flag = $mFlag;
                return (new ResponseDescriptor())->setBody(json_encode([
                    'message' => 'returned-message'
                ]));
            }
        );

        $filer->setTransport($transport);

        $file = $this->getValidFileInstance();

        $result = $filer->delete($file->getUuid(), 3);

        $this->assertNotFalse($flag & ApiRequestOption::NO_RESPONSE);
        $this->assertEquals($request->getMethod(), 'DELETE');

        $url = 'http://url/api/files/revisions?uuid=' . urlencode($file->getUuid()) . '&rev=3';
        $this->assertEquals($request->getUrl(), $url);
        $this->assertEquals($result, null);
    }

    public function testTruncateWithoutTransportSet()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $this->setExpectedException(FilerException::class, 'Synchronous Transport has to be set');

        $filer->truncate('fake-uuid');
    }

    public function testTruncate()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $request = new RequestDescriptor();
        $flag = null;

        $transport = $this->getMockBuilder(SyncTransportInterface::class)->getMockForAbstractClass();
        $transport->expects($this->once())->method('send')->willReturnCallback(
            function (RequestDescriptor $requestDescriptor, $mFlag) use (&$request, &$flag, $transport) {
                $request = $requestDescriptor;
                $flag = $mFlag;
                return (new ResponseDescriptor())->setBody(json_encode([
                    'message' => 'returned-message'
                ]));
            }
        );

        $filer->setTransport($transport);

        $file = $this->getValidFileInstance();

        $result = $filer->truncate($file->getUuid(), 1);

        $this->assertNotFalse($flag & ApiRequestOption::NO_RESPONSE);
        $this->assertEquals($request->getMethod(), 'DELETE');

        $url = 'http://url/api/files/truncate?uuid=' . urlencode($file->getUuid()) . '&keep=1';
        $this->assertEquals($request->getUrl(), $url);
        $this->assertEquals($result, null);
    }

    public function testServeWithoutTransportSet()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $this->setExpectedException(FilerException::class, 'Synchronous Transport has to be set');

        $filer->serve('fake-uuid');
    }

    public function testServe()
    {
        $responseDescriptor = $this->getMockBuilder(ResponseDescriptor::class)->setMethods(['getBody'])->getMock();
        $responseDescriptor->expects($this->once())->method('getBody')->willReturn('binary-data');

        $file = $this->getValidFileInstance();

        $filer = Stub::make(Filer::class, [
            'send' => $responseDescriptor,
            'retrieve' => $file,
            'fpassthru' => null
        ]);

        $transport = $this->getMockBuilder(SyncTransportInterface::class)->getMockForAbstractClass();
        $filer->setTransport($transport);

        $returned = $filer->serve($file->getUuid());

        $this->assertNull($returned);
    }

    public function testServeWhenTheResponseIsNotAnInstanceOfResponseDescriptor()
    {
        $file = $this->getValidFileInstance();

        $expected = new \stdClass();
        $filer = Stub::make(Filer::class, [
            'send' => $expected,
            'retrieve' => $file
        ]);

        $transport = $this->getMockBuilder(SyncTransportInterface::class)->getMockForAbstractClass();
        $filer->setTransport($transport);

        $returned = $filer->serve($file->getUuid());

        $this->assertNull($returned);
    }

    public function testSaveWithNoSyncTransport()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $this->setExpectedException(FilerException::class, 'Synchronous Transport has to be set');

        $filer->save('fake-uuid', '/my/path');
    }

    public function testSaveWhenResponseBodyIsNotAValidStream()
    {
        $responseDescriptor = $this->getMockBuilder(ResponseDescriptor::class)->setMethods(['getBody'])->getMock();
        $responseDescriptor->expects($this->once())->method('getBody')->willReturn('binary-data');

        $file = $this->getValidFileInstance();

        $filer = Stub::make(Filer::class, [
            'send' => $responseDescriptor
        ]);

        $transport = $this->getMockBuilder(SyncTransportInterface::class)->getMockForAbstractClass();
        $filer->setTransport($transport);

        $filer->save($file->getUuid(), __DIR__ . '/../_data/');
    }

    public function testSaveWhenResponseBodyIsAValidStream()
    {
        $fileObject = new \SplFileObject(__DIR__ . '/../_data/avatar.png');

        $data = '';
        while (!$fileObject->eof()) {
            $data .= $fileObject->fgets();
        }

        $responseDescriptor = $this->getMockBuilder(ResponseDescriptor::class)->setMethods(['getBody'])->getMock();
        $responseDescriptor->expects($this->once())->method('getBody')->willReturn($data);

        $file = $this->getValidFileInstance();
        $file->setFilename('test.png');

        $filer = Stub::make(Filer::class, [
            'send' => $responseDescriptor,
            'retrieve' => $file
        ]);
        $transport = $this->getMockBuilder(SyncTransportInterface::class)->getMockForAbstractClass();
        $filer->setTransport($transport);

        $filer->save($file->getUuid(), __DIR__ . '/../_data/');

        if (is_file(__DIR__ . '/../_data/test.png')) {
            @unlink(__DIR__ . '/../_data/test.png');
        }
    }

    public function testGetFileBinary()
    {

        $responseDescriptor = $this->getMockBuilder(ResponseDescriptor::class)->setMethods(['getBody'])->getMock();
        $responseDescriptor->expects($this->once())->method('getBody')->willReturn('binary-data');

        $file = $this->getValidFileInstance();

        $filerStub = Stub::make(Filer::class, [
            'send' => $responseDescriptor
        ]);

        $returned = $filerStub->getFileBinary($file);

        $this->assertEquals('binary-data', $returned->getData());
        $this->assertEquals($file, $returned);
    }

    public function testCreatedUuidNoSyncTransport()
    {
        $filer = new Filer();

        $this->setExpectedException(FilerException::class, 'Synchronous Transport has to be set');

        $filer->createUuid(1);
    }

    public function testCreatedUuidBadCategory()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $response = new Response(400, [], <<<JSON
{
   "code": 400,
   "error": "Backend not found for this category of file",
   "type": "Fei\\\Service\\\Filer\\\Exception",
   "file": "/home/dev/php7/app/src/Service/FilesManager.php",
   "line": 191
}
JSON
        );

        $previous = new RequestException('test', new Request('POST', 'test'), $response);
        $exception = new ApiClientException('Test', 0, $previous);

        $transport = $this->getMockBuilder(SyncTransportInterface::class)->getMockForAbstractClass();
        $transport->expects($this->once())->method('send')->willThrowException($exception);

        $filer->setTransport($transport);

        $this->setExpectedException(FilerException::class, 'Backend not found for this category of file', 400);

        $filer->createUuid(10);
    }

    public function testCreatedUuid()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $request = new RequestDescriptor();
        $flag = null;

        $transport = $this->getMockBuilder(SyncTransportInterface::class)->getMockForAbstractClass();
        $transport->expects($this->once())->method('send')->willReturnCallback(
            function (RequestDescriptor $requestDescriptor, $mFlag) use (&$request, &$flag, $transport) {
                $request = $requestDescriptor;
                $flag = $mFlag;
                return (new ResponseDescriptor())->setBody(json_encode([
                    'uuid' => 'test-uuid'
                ]));
            }
        );

        $filer->setTransport($transport);

        $result = $filer->createUuid(1);

        $this->assertTrue($flag === 0);
        $this->assertEquals($request->getMethod(), 'POST');
        $this->assertEquals($request->getUrl(), 'http://url/api/files/uuid?category=1');
        $this->assertEquals([], $request->getBodyParams());
        $this->assertEquals($result, 'test-uuid');
    }

    public function testSearch()
    {
        $filer = new Filer([Filer::OPTION_BASEURL => 'http://url']);

        $request = new RequestDescriptor();
        $flag = null;

        $arr = (new File())
            ->setUuid('test:00000000-0000-0000-0000-000000000000')
            ->setRevision(2)
            ->setCategory(3)
            ->setFilename('fake-filename')
            ->toArray();

        $transport = $this->getMockBuilder(SyncTransportInterface::class)->getMockForAbstractClass();
        $transport->expects($this->once())->method('send')->willReturnCallback(
            function (RequestDescriptor $requestDescriptor, $mFlag) use (&$request, &$flag, $transport, $arr) {
                $request = $requestDescriptor;
                $flag = $mFlag;
                return (new ResponseDescriptor())->setBody(json_encode([
                    'files' => [$arr]
                ]));
            }
        );

        $filer->setTransport($transport);

        $builder = new SearchBuilder();
        $builder->filename()->equal('fake-filename');
        $result = $filer->search($builder);

        $this->assertEquals([
            new FileWrapper($filer, $arr)
        ], $result);
    }

    public function testEmbed()
    {
        $file = Filer::embed(__DIR__ . '/../_data/avatar.png');

        $this->assertEquals(
            new \SplFileObject(__DIR__ . '/../_data/avatar.png'),
            $file->getFile()
        );
    }

    public function testUploadByChunkCreateUuid()
    {
        $file = (new File())
            ->setCategory(File::CATEGORY_IMG)
            ->setFile(new \SplFileObject(__DIR__ . '/../_data/avatar.png'));

        $filer = $this->getMockBuilder(Filer::class)
            ->enableOriginalConstructor()
            ->setMethods(['createUuid', 'getClient'])
            ->getMock();

        $filer->setChunkSize(1000);

        $client = $this->getMockBuilder(Client::class)
            ->getMock();

        $client->method('sendAsync')->willReturn(new Response());

        $filer->expects($this->once())->method('createUuid')->with($file->getCategory())->willReturn('uuid');
        $filer->expects($this->once())->method('getClient')->willReturn($client);

        $filer->uploadByChunks($file);

        $this->assertEquals('uuid', $file->getUuid());
    }

    /**
     * @return File
     */
    protected function getValidFileInstance()
    {
        return (new File())
            ->setUuid('test:00000000-0000-0000-0000-000000000000')
            ->setRevision(2)
            ->setCategory(3)
            ->setFile(new \SplFileObject(__DIR__ . '/../_data/avatar.png'));
    }
}
