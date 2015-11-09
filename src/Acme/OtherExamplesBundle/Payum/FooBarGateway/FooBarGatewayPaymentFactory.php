<?php
namespace Acme\OtherExamplesBundle\Payum\FooBarGateway;

use Acme\OtherExamplesBundle\Payum\FooBarGateway\Action\CaptureAction;
use Acme\OtherExamplesBundle\Payum\FooBarGateway\Action\StatusAction;
use Payum\Bundle\PayumBundle\DependencyInjection\Factory\Gateway\AbstractGatewayFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FooBarGatewayPaymentFactory extends AbstractGatewayFactory
{
    /**
     * {@inheritDoc}
     */
    public function createGateway(ContainerBuilder $container, $gatewayName, array $config)
    {
        $captureAction = new Definition(CaptureAction::class);
        $captureAction->addArgument($config['username']);
        $captureAction->addArgument($config['password']);
        $captureAction->addTag('payum.action', ['factory_name' => $this->getName()]);
        $captureActionId = 'payum.foobar_gateway.action.capture';
        $container->setDefinition($captureActionId, $captureAction);

        $statusAction = new Definition(StatusAction::class);
        $captureAction->addTag('payum.action', ['factory_name' => $this->getName()]);
        $statusActionId = 'payum.foobar_gateway.action.status';
        $container->setDefinition($statusActionId, $statusAction);

        $config['payum.factory'] = $this->getName();
        $config['payum.context'] = $gatewayName;

        $gateway = parent::createGateway($container, $gatewayName, $config);
        $gateway->addMethodCall('addAction', array(new Reference($captureActionId)));
        $gateway->addMethodCall('addAction', array(new Reference($statusActionId)));

        return $gateway;
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
