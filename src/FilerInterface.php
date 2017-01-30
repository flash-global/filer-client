<?php

namespace Fei\Service\Filer\Client;

use Fei\Service\Filer\Client\Builder\SearchBuilder;
use Fei\Service\Filer\Entity\File;

/**
 * Interface FilerInterface
 *
 * @package Fei\Service\Filer\Client
 */
interface FilerInterface
{
    const ASYNC_UPLOAD = 2;
    const NEW_REVISION = 4;

    /**
     * Search for files
     *
     * @param SearchBuilder $builder
     *
     * @return array Return an array of File instances
     */
    public function search(SearchBuilder $builder);

    /**
     * Upload a file
     *
     * @param File $file  The File entity to upload
     * @param null $flags Upload options:
     *                    * self::ASYNC_UPLOAD: set the current upload asynchronous (the UUID File entity property must
     *                    be set). The default behaviour is synchronous
     *                    * self::NEW_REVISION: create a new revision (the UUID File entity property must be set)
     *
     * @return string|null Return the UUID of the created File or Revision
     */
    public function upload(File $file, $flags = null);

    /**
     * Restore a file
     *
     * @param string $uuid UUID of the file to be fetch
     *
     * @return File
     */
    public function retrieve($uuid);

    /**
     * Delete (forever) a file
     *
     * @param string $uuid UUID of the file to be deleted
     */
    public function delete($uuid);

    /**
     * Truncate revision of a File
     *
     * @param string $uuid UUID of the file to truncate revisions
     * @param int    $keep Number of revision to keep from the beginning
     */
    public function truncate($uuid, $keep = 0);

    /**
     * Fetch a file and serve it to download immediately
     *
     * @param string $uuid UUID of the file to serve
     */
    public function serve($uuid);

    /**
     * Save a local copy of a File
     *
     * @param string $uuid UUID of the file to save
     * @param string $path The path where to save the file
     * @param string $as   Specify the filename
     */
    public function save($uuid, $path, $as = null);

    /**
     * Create a new instance of a File entity from a local file
     *
     * @param string $path The path of the file
     *
     * @return File
     */
    public static function embed($path);
}
