<?php
namespace Acme\PaymentBundle;

use Doctrine\ODM\MongoDB\Types\Type;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AcmePaymentBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        Type::addType('object', 'Payum\Bridge\Doctrine\Types\ObjectType');
    }
}