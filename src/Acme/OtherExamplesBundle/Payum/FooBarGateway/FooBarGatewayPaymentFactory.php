<?php
namespace Acme\OtherExamplesBundle\Payum\FooBarGateway;

use Payum\Bundle\PayumBundle\DependencyInjection\Factory\Payment\AbstractPaymentFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class FooBarGatewayPaymentFactory extends AbstractPaymentFactory
{
    /**
     * {@inheritDoc}
     */
    public function createPayment(ContainerBuilder $container, $paymentName, array $config)
    {
        if (isset($config['service'])) {
            return new DefinitionDecorator($config['service']);
        }

        $config['payum.factory'] = $this->getName();
        $config['payum.context'] = $paymentName;

        $payment = new Definition('Payum\Core\Payment', array($config));
        $payment->setFactoryService('payum.payment_factory');
        $payment->setFactoryMethod('create');

        $captureActionClass = 'Acme\OtherExamplesBundle\Payum\FooBarGateway\Action\CaptureAction';
        $captureActionDefinition = new Definition($captureActionClass);
        $captureActionDefinition->addArgument($config['username']);
        $captureActionDefinition->addArgument($config['password']);
        $captureActionId = 'payum.foobar_gateway.action.capture';
        $container->setDefinition($captureActionId, $captureActionDefinition);

        $statusActionClass = 'Acme\OtherExamplesBundle\Payum\FooBarGateway\Action\StatusAction';
        $statusActionDefinition = new Definition($statusActionClass);
        $statusActionId = 'payum.foobar_gateway.action.status';
        $container->setDefinition($statusActionId, $statusActionDefinition);

        $payment->addMethodCall('addAction', array(new Reference($captureActionId)));
        $payment->addMethodCall('addAction', array(new Reference($statusActionId)));

        return $payment;
    }

    /**
     * {@inheritDoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        parent::addConfiguration($builder);

        $builder
            ->children()
                ->scalarNode('username')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
            ->end()
        ;
    }

    public function getName()
    {
        return 'foo_bar_gateway';
    }
}
