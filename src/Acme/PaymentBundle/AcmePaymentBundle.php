<?php
namespace Acme\PaymentBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AcmePaymentBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'PayumBundle';
    }
}