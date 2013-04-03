<?php
namespace Acme\PaymentBundle\Model;

use Payum\Be2Bill\Model\PaymentDetails;

class Be2BillInstruction extends PaymentDetails 
{
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}