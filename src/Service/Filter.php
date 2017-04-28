<?php
/**
 * Created by PhpStorm.
 * User: mathieuhecker
 * Date: 26/04/2017
 * Time: 17:57
 */

namespace Fei\Service\Filer\Client\Service;


use Fei\Service\Filer\Client\Builder\SearchBuilder;

class Filter extends SearchBuilder
{
    /**
     * @var array $paramsFilter
     */
    protected $paramsFilter;

    /**
     * @param array $paramsFilter
     *
     * @return $this
     */
    protected function setParamsFilter($paramsFilter)
    {
        $this->paramsFilter = $paramsFilter;

        return $this;
    }

    protected function getParamsFilter()
    {
        return $this->paramsFilter;
    }

    /**
     * @param string
     */
    protected function applyFilter()
    {

    }
}