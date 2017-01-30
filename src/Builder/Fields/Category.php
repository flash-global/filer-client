<?php
namespace Fei\Service\Filer\Client\Builder\Fields;

use Fei\Service\Filer\Client\Builder\OperatorBuilder;

class Category extends OperatorBuilder
{
    public function build($value, $operator = null)
    {
        $search = $this->builder->getParams();
        $search['category'] = $value;

        $this->builder->setParams($search);
    }
}
