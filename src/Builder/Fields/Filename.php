<?php
namespace Fei\Service\Filer\Client\Builder\Fields;

use Fei\Service\Filer\Client\Builder\OperatorBuilder;

class Filename extends OperatorBuilder
{
    public function build($value, $operator = null)
    {
        $search = $this->builder->getParams();
        $search['filename'] = $value;
        $search['filename_operator'] = $operator ?? '=';

        $this->builder->setParams($search);
    }
}
