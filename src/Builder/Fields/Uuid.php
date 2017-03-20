<?php
namespace Fei\Service\Filer\Client\Builder\Fields;

use Fei\Service\Filer\Client\Builder\SearchBuilder;

class Uuid
{
    protected $builder;

    public function __construct(SearchBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function build($value)
    {
        $search = $this->builder->getParams();
        $search['uuid'] = $value;

        $this->builder->setParams($search);
    }

    /**
     * Set the equal operator for the current filter
     *
     * @param $value
     * @return $this
     */
    public function equal($value)
    {
        $this->build("$value");

        return $this;
    }

    /**
     * Get Builder
     *
     * @return SearchBuilder
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * Set Builder
     *
     * @param SearchBuilder $builder
     *
     * @return $this
     */
    public function setBuilder($builder)
    {
        $this->builder = $builder;
        return $this;
    }
}
