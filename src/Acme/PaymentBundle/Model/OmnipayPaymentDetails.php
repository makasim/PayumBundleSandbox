<?php
namespace Acme\PaymentBundle\Model;

class OmnipayPaymentDetails extends \ArrayObject
{
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}