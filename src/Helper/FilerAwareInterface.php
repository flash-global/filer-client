<?php
/**
 * FilerAwareInterface.php
 *
 * @date        29/09/17
 * @file        FilerAwareInterface.php
 */

namespace Fei\Service\Filer\Helper;

/**
 * FilerAwareInterface
 */
interface FilerAwareInterface
{
    public function getFilerClient();

    public function setFilerClient($filerClient);
}