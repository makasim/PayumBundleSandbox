<?php
namespace Acme\PaypalExpressCheckoutBundle\Model;

use Payum\Paypal\ExpressCheckout\Nvp\Model\RecurringPaymentDetails as BaseRecurringPaymentDetails;

class RecurringPaymentDetails extends BaseRecurringPaymentDetails
{
    protected $id;

    public function getId()
    {
        return $this->id;
    }
}