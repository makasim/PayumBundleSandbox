<?php
namespace Acme\PaymentBundle\Model;

use Payum\Paypal\ProCheckout\Nvp\Model\PaymentDetails;

class PaypalProPaymentDetails extends PaymentDetails
{
    protected $id;

    public function getId()
    {
        return $this->id;
    }
}