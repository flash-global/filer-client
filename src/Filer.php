<?php

namespace Fei\Service\Filer\Client;

use Fei\ApiClient\AbstractApiClient;
use Fei\ApiClient\ApiRequestOption;
use Fei\ApiClient\RequestDescriptor;
use Fei\ApiClient\ResponseDescriptor;
use Fei\Service\Filer\Client\Builder\SearchBuilder;
use Fei\Service\Filer\Client\Exception\FilerException;
use Fei\Service\Filer\Client\Exception\ValidationException;
use Fei\Service\Filer\Client\Service\FileWrapper;
use Fei\Service\Filer\Entity\File;
use Fei\Service\Filer\Validator\FileValidator;
use Guzzle\Http\Exception\BadResponseException;

/**
 * Class Filer
 *
 * @package Fei\Service\Filer\Client
 */
class Filer extends AbstractApiClient implements FilerInterface
{
    const API_FILER_PATH_INFO = '/api/files';

    public function search(SearchBuilder $builder)
    {
        $request = (new RequestDescriptor())
            ->setMethod('GET')
            ->setUrl(
                $this->buildUrl(
                    self::API_FILER_PATH_INFO . '?criterias=' . urlencode(json_encode($builder->getParams()))
                )
            );

        $response = $this->send($request);

        $body = \json_decode($response->getBody(), true);

        $body['files'] = $body['files'] ?? [];

        foreach ($body['files'] as &$file) {
            $file = new FileWrapper($this, $file);
        }

        return $body['files'];
    }

    /**
     * {@inheritdoc}
     */
    public function upload(File $file, $flags = null)
    {
        $this->validateFile($file);

        if ($flags & self::NEW_REVISION && $file->getUuid() === null) {
            throw new ValidationException('UUID must be set when adding a new revision');
        }

        $uuidCreated = false;
        if ($flags & self::ASYNC_UPLOAD && $file->getUuid() === null) {
            $file->setUuid($this->createUuid($file->getCategory()));
            $uuidCreated = true;
        }

        if ($flags & self::ASYNC_UPLOAD && !$this->getAsyncTransport()) {
            throw new FilerException('Asynchronous Transport has to be set');
        }

        $method = 'POST';
        if ($flags & self::NEW_REVISION) {
            $method = 'PUT';
        } elseif ($file->getUuid() !== null && !$uuidCreated) {
            $method = 'PATCH';
        }

        $request = (new RequestDescriptor())
            ->setMethod($method)
            ->setUrl($this->buildUrl(self::API_FILER_PATH_INFO));
        $request->addHeader('Content-Type', 'application/x-www-form-urlencoded');
        $request->setBodyParams(['file' => \json_encode($file->toArray())]);

        return $this->send($request, $flags & self::ASYNC_UPLOAD ? ApiRequestOption::NO_RESPONSE : 0);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve($uuid)
    {
        if (!$this->getTransport()) {
            throw new FilerException('Synchronous Transport has to be set');
        }

        $request = (new RequestDescriptor())
            ->setMethod('GET')
            ->setUrl($this->buildUrl(self::API_FILER_PATH_INFO . '?uuid=' . urlencode($uuid)));

        /** @var File $file */
        $file = $this->fetch($request);

        return $file;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(RequestDescriptor $request, $flags = 0)
    {
        $file = parent::fetch($request, $flags);

        $fileWrapper = new FileWrapper($this, $file->toArray());

        return $fileWrapper;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($uuid, $revision = null)
    {
        if (!$this->getTransport()) {
            throw new FilerException('Synchronous Transport has to be set');
        }

        if (null === $revision) {
            $baseUrl = self::API_FILER_PATH_INFO;
            $appendParams = '';
        } else {
            $baseUrl = self::API_FILER_PATH_INFO . '/revisions';
            $appendParams = '&rev=' . urlencode($revision);
        }

        $request = (new RequestDescriptor())
            ->setMethod('DELETE')
            ->setUrl($this->buildUrl(
                $baseUrl. '?uuid=' . urlencode($uuid) . $appendParams
            ));

        $this->send($request);
    }

    /**
     * {@inheritdoc}
     */
    public function truncate($uuid, $keep = 0)
    {
        if (!$this->getTransport()) {
            throw new FilerException('Synchronous Transport has to be set');
        }

        $request = (new RequestDescriptor())
            ->setMethod('DELETE')
            ->setUrl($this->buildUrl(
                self::API_FILER_PATH_INFO . '/truncate?uuid=' . urlencode($uuid) . '&keep=' . urlencode($keep)
            ));

        $this->send($request);
    }

    /**
     * {@inheritdoc}
     */
    public function serve($uuid)
    {
        if (!$this->getTransport()) {
            throw new FilerException('Synchronous Transport has to be set');
        }

        $request = (new RequestDescriptor())
            ->setMethod('GET')
            ->setUrl($this->buildUrl(
                self::API_FILER_PATH_INFO . '/data/' . urlencode($uuid)
            ));

        $file = $this->retrieve($uuid);
        $response = $this->send($request);

        if ($response instanceof ResponseDescriptor) {
            // transforming string into resource
            $fp = fopen('php://temp', 'rb+');
            fwrite($fp, $response->getBody());
            fseek($fp, 0);
            $value = $fp;
            $stat = fstat($fp); // getting information about the resource

            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header('Content-disposition: attachment; filename="' . $file->getFilename() . '"');

            if (isset($stat['size'])) {
                header('Content-Length: ' . $stat['size']);
            }

            $this->fpassthru($value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save($uuid, $path, $as = null)
    {
        if (!$this->getTransport()) {
            throw new FilerException('Synchronous Transport has to be set');
        }

        $request = (new RequestDescriptor())
            ->setMethod('GET')
            ->setUrl($this->buildUrl(
                self::API_FILER_PATH_INFO . '/data/' . urlencode($uuid)
            ));

        $response = $this->send($request);

        if ($response instanceof ResponseDescriptor) {
            $stream = $response->getBody();

            // if we get a binary stream we save it
            if (ctype_print($stream) === false) {
                $file = $this->retrieve($uuid);
                $path = (substr($path, -1) === '/') ? $path : $path . '/';

                $filename = (null !== $as && is_string($as)) ? $as : $file->getFilename();

                file_put_contents($path . $filename, $stream);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function send(RequestDescriptor $request, $flags = 0)
    {
        try {
            $response = parent::send($request, $flags);

            if ($response instanceof ResponseDescriptor) {
                $body = \json_decode($response->getBody(), true);

                if (isset($body['uuid'])) {
                    return $body['uuid'];
                }

                if (isset($body['message'])) {
                    return $body['message'];
                }

                return $response;
            }
        } catch (\Exception $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof BadResponseException) {
                $data = \json_decode($previous->getResponse()->getBody(true), true);
                if (isset($data['code']) && isset($data['error'])) {
                    throw new FilerException($data['error'], $data['code'], $e);
                }
            }

            throw new FilerException($e->getMessage(), $e->getCode(), $e);
        }

        return null;
    }

    /**
     * Return an new UUID created by the Filer API
     *
     * @param int $category
     *
     * @return string
     */
    public function createUuid($category)
    {
        if (!$this->getTransport()) {
            throw new FilerException('Synchronous Transport has to be set');
        }

        $request = (new RequestDescriptor())
            ->setMethod('POST')
            ->setUrl($this->buildUrl(self::API_FILER_PATH_INFO . '/uuid?category=' . urlencode($category)));
        $request->addHeader('Content-Type', 'application/x-www-form-urlencoded');

        return $this->send($request);
    }

    /**
     * Returns to binary stream of the requested file
     *
     * @param File $file
     *
     * @return File
     */
    public function getFileBinary(File $file)
    {
        $request = (new RequestDescriptor())
            ->setMethod('GET')
            ->setUrl($this->buildUrl(self::API_FILER_PATH_INFO . '/data/' . $file->getUuid()));

        $response = $this->send($request);

        if ($response instanceof ResponseDescriptor) {
            $file->setData($response->getBody());
        }

        return $file;
    }

    /**
     * Create a new instance of a File entity from a local file
     *
     * @param string $path The path of the file
     *
     * @return File
     */
    public static function embed($path)
    {
        return (new File())->setFile(new \SplFileObject($path));
    }

    /**
     * Validate a File entity
     *
     * @param File $file
     */
    protected function validateFile(File $file)
    {
        $validator = new FileValidator();

        if (!$validator->validate($file)) {
            throw (new ValidationException(
                sprintf('File entity is not valid: (%s)', $validator->getErrorsAsString())
            ))->setErrors($validator->getErrors());
        }
    }

    /**
     * Writes to the output buffer
     *
     * @param resource $resource
     */
    protected function fpassthru($resource)
    {
        // streaming the content of the file
        fpassthru($resource);
        exit;
    }
}
