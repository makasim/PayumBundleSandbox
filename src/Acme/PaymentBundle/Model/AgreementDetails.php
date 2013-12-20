<?php
namespace Acme\PaymentBundle\Model;

use Payum\Core\Model\ArrayObject;

class AgreementDetails extends ArrayObject
{
    protected $id;

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}