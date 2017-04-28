<?php
namespace Fei\Service\Filer\Client\Builder;

use Fei\Service\Filer\Client\Builder\Fields\FieldInterface;

abstract class OperatorBuilder implements FieldInterface
{
    protected $builder;
    protected $value;
    protected $in_cache;

    public function __construct(SearchBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Set the like operator for the current filter
     *
     * @param $value
     * @return $this
     */
    public function like($value)
    {
        $this->build("%$value%", 'LIKE');

        return $this;
    }

    /**
     * Set the like operator and begins with for the current filter
     *
     * @param $value
     * @return $this
     */
    public function beginsWith($value)
    {
        $this->build("$value%", 'LIKE');

        return $this;
    }

    /**
     * Set the like operator and ends with for the current filter
     *
     * @param $value
     * @return $this
     */
    public function endsWith($value)
    {
        $this->build("%$value", 'LIKE');

        return $this;
    }

    /**
     * Set the equal operator for the current filter
     *
     * @param $value
     * @return $this
     */
    public function equal($value)
    {
        $this->build("$value", '=');

        return $this;
    }

    public function in(array $values)
    {
        $this->build("('". implode("','" , $values). "')",'IN');

        return $this;
    }
    /**
     * Get InCache
     *
     * @return mixed
     */
    public function getInCache()
    {
        return $this->in_cache;
    }

    /**
     * Set InCache
     *
     * @param mixed $in_cache
     *
     * @return $this
     */
    public function setInCache($in_cache)
    {
        $this->in_cache = $in_cache;
        return $this;
    }
}
