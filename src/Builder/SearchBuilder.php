<?php
namespace Fei\Service\Filer\Client\Builder;

use Fei\Service\Filer\Client\Builder\Fields\Category;
use Fei\Service\Filer\Client\Builder\Fields\Context;
use Fei\Service\Filer\Client\Builder\Fields\Filename;
use Fei\Service\Filer\Client\Builder\Fields\Uuid;

class SearchBuilder
{
    protected $params = [];

    /**
     * Add a filter the the filename
     *
     * @return Filename
     */
    public function filename()
    {
        return new Filename($this);
    }

    /**
     * Add a filter the the uuid field
     *
     * @return Uuid
     */
    public function uuid()
    {
        return new Uuid($this);
    }

    /**
     * Set the condition type for the contexts
     *
     * @param string $type
     *
     * @return $this
     */
    public function contextCondition($type = 'AND')
    {
        $type = strtoupper($type);

        if (!in_array($type, ['AND', 'OR'])) {
            throw new FilerException('Type has to be either "AND" or "OR"!');
        }

        $params = $this->getParams();
        $params['context_condition'] = $type;

        $this->setParams($params);
    }

    /**
     * Add a filter the the contexts
     *
     * @return Context
     */
    public function context()
    {
        return new Context($this);
    }

    /**
     * Add a filter the the category
     *
     * @return Category
     */
    public function category()
    {
        return new Category($this);
    }

    /**
     * Get Params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set Params
     *
     * @param array $params
     *
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }
    
    public function __call($name, $arguments)
    {
        $class = 'Fei\Service\Filer\Client\Builder\Fields\\' . ucfirst($this->toCamelCase($name));

        if (class_exists($class)) {
            return new $class($this);
        } else {
            throw new \Exception("Cannot load " . $name . ' filter!');
        }
    }

    /**
     * @param $offset
     *
     * @return string
     */
    public function toCamelCase($offset)
    {
        $parts = explode('_', $offset);
        array_walk($parts, function (&$offset) {
            $offset = ucfirst($offset);
        });

        return implode('', $parts);
    }
}
