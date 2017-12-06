<?php

namespace Fei\Service\Filer\Client\Exception;

use GuzzleHttp\Exception\RequestException;

/**
 * Class FilerException
 *
 * @package Fei\Service\Filer\Client\Exception
 */
class FilerException extends \RuntimeException
{
    /**
     * Create a FilerException with Guzzle RequestException
     *
     * @param RequestException $e
     *
     * @return FilerException
     */
    public static function createWithRequestException(RequestException $e)
    {
        if ($e->getResponse() !== null) {
            $data = json_decode($e->getResponse()->getBody()->getContents(), true);

            if (isset($data['code']) && isset($data['error'])) {
                return new static($data['error'], $data['code'], $e);
            }
        }

        return new static($e->getMessage(), $e->getCode(), $e);
    }
}
