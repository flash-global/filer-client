<?php
namespace Fei\Service\Filer\Client\Service;

use Fei\Service\Filer\Client\Filer;
use Fei\Service\Filer\Entity\File;

class FileWrapper extends File
{
    /**
     * @var Filer
     */
    protected $filer;

    /**
     * FileWrapper constructor.
     *
     * @param Filer $filer
     * @param array $data
     */
    public function __construct(Filer $filer, $data = null)
    {
        $this->setFiler($filer);

        parent::__construct($data);
    }

    /**
     * Set Filer
     *
     * @param Filer $filer
     * @return $this
     */
    public function setFiler(Filer $filer)
    {
        $this->filer = $filer;

        return $this;
    }

    /**
     * Get Filer
     *
     * @return Filer
     */
    public function getFiler()
    {
        return $this->filer;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data = parent::getData();

        // getting the data if they are not loaded yet
        if (empty($data)) {
            $data = $this->getFiler()->getFileBinary($this)->getData();
        }

        return $data;
    }
}
