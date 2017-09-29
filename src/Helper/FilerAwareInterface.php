<?php
/**
 * FilerAwareInterface.php
 *
 * @date        29/09/17
 * @file        FilerAwareInterface.php
 */

namespace Fei\Service\Filer\Helper;

use Fei\Service\Filer\Client\Filer;

/**
 * FilerAwareInterface
 */
interface FilerAwareInterface
{
    /**
     * @return Filer
     */
    public function getFilerClient();

    /**
     * @param Filer $filerClient
     *
     * @return $this
     */
    public function setFilerClient($filerClient);
}