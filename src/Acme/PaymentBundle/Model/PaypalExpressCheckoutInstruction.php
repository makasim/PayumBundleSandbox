<?php
namespace Acme\PaymentBundle\Model;

use Payum\Paypal\ExpressCheckout\Nvp\PaymentInstruction;

class PaypalExpressCheckoutInstruction extends PaymentInstruction 
{
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}