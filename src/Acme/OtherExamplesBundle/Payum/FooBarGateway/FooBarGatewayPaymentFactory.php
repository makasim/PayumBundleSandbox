<?php
namespace Acme\OtherExamplesBundle\Payum\FooBarGateway;

use Payum\Bundle\PayumBundle\DependencyInjection\Factory\Payment\AbstractPaymentFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FooBarGatewayPaymentFactory extends AbstractPaymentFactory
{
    protected function addActions(Definition $paymentDefinition, ContainerBuilder $container, $contextName, array $config)
    {
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

        $paymentDefinition->addMethodCall('addAction', array(new Reference($captureActionId)));
        $paymentDefinition->addMethodCall('addAction', array(new Reference($statusActionId)));
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