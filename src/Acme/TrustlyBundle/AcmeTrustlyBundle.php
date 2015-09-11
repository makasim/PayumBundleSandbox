<?php

namespace Acme\TrustlyBundle;

use Paradigm\PayumTrustly\Bridge\Symfony\TrustlyGatewayFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AcmeTrustlyBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /** @var $extension \Payum\Bundle\PayumBundle\DependencyInjection\PayumExtension */
        $extension = $container->getExtension('payum');

        $extension->addGatewayFactory(new TrustlyGatewayFactory());
    }
}
