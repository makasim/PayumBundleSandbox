<?php
namespace Acme\PaymentBundle\Model;

class OfflinePaymentDetails extends \ArrayObject
{
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}