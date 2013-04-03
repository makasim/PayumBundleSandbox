<?php
namespace Acme\PaymentBundle\Model;

use Payum\AuthorizeNet\Aim\Model\PaymentDetails;

class AuthorizeNetInstruction extends PaymentDetails
{
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}