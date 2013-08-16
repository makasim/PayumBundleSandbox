<?php

namespace Acme\OtherExamplesBundle;

use Payum\Bridge\JMSPayment\DependencyInjection\Factory\Payment\JMSPaymentPaymentFactory;
use Payum\Bundle\PayumBundle\DependencyInjection\PayumExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AcmeOtherExamplesBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        /** @var  PayumExtension $payumExtension */
        $payumExtension = $container->getExtension('payum');

        $payumExtension->addPaymentFactory(new JMSPaymentPaymentFactory);
    }
}