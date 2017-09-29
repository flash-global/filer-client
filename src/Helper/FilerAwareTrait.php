<?php
/**
 * FilerAwareTrait.php
 *
 * @date        29/09/17
 * @file        FilerAwareTrait.php
 */

namespace Fei\Service\Filer\Helper;

use Fei\Service\Filer\Client\Filer;

/**
 * FilerAwareTrait
 */
trait FilerAwareTrait
{
    /** @var Filer */
    protected $filerClient;

    /**
     * @return Filer
     */
    public function getFilerClient()
    {
        return $this->filerClient;
    }

    /**
     * @param Filer $filerClient
     *
     * @return FilerAwareTrait
     */
    public function setFilerClient($filerClient)
    {
        $this->filerClient = $filerClient;

        return $this;
    }
}