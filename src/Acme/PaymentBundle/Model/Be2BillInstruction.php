<?php
namespace Acme\PaymentBundle\Model;

use Payum\Be2Bill\PaymentInstruction;

class Be2BillInstruction extends PaymentInstruction 
{
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}