<?php
namespace Acme\PaymentBundle\Model;

use Payum\AuthorizeNet\Aim\PaymentInstruction;

class AuthorizeNetInstruction extends PaymentInstruction
{
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}