<?php
namespace Acme\PaymentBundle\Payum\Api\Paypal\ExpressCheckout\Nvp;

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
            'username' => $this->container->getParameter('paypal.express_checkout_via_token.username'),
            'password' => $this->container->getParameter('paypal.express_checkout_via_token.password'),
            'signature' => $this->container->getParameter('paypal.express_checkout_via_token.signature'),
            'token' => $this->container->getParameter('paypal.express_checkout_via_token.token'),
            'tokenSecret' => $this->container->getParameter('paypal.express_checkout_via_token.tokenSecret'),
            'sandbox' => true
        ));
    }
}