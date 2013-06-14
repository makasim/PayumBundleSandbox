<?php
namespace Acme\PayexBundle\Model;

use Payum\Payex\Model\PaymentDetails as BasePaymentDetails;

class PaymentDetails extends BasePaymentDetails 
{
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}