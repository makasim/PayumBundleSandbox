<?php
namespace Acme\PaymentBundle\Model;

use Payum\Model\ArrayObject;

class AgreementDetails extends ArrayObject
{
    protected $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}