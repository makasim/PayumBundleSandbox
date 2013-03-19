<?php
namespace Acme\PaymentBundle\Model;

use Payum\Paypal\ExpressCheckout\Nvp\Model\PaymentDetails;

class PaypalExpressCheckoutPaymentDetails extends PaymentDetails 
{
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}