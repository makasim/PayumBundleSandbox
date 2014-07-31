<?php
namespace Acme\PaymentBundle\Payum\Api;

use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Factory
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return Api
     */
    public function createPaypalExpressCheckoutApi()
    {
        return new Api(array(
            'username' => $this->container->getParameter('paypal.express_checkout.username'),
            'password' => $this->container->getParameter('paypal.express_checkout.password'),
            'signature' => $this->container->getParameter('paypal.express_checkout.signature'),
            'sandbox' => true
        ));
    }
}