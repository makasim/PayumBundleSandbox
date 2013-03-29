<?php
namespace Acme\AuthorizeNetBundle\Model;

use Payum\AuthorizeNet\Aim\PaymentInstruction;

class PaymentDetails extends PaymentInstruction
{
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}