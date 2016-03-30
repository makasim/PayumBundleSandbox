<?php
namespace Acme\PaymentBundle\Payum\Api\Paypal\ExpressCheckout\Nvp;


use Acme\PaymentBundle\Payum\Paypal\ExpressCheckout\Nvp\ApiViaToken;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FactoryViaToken
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
     * @return ApiViaToken
     */
    public function createPaypalExpressCheckoutApi()
    {
        return new ApiViaToken(array(
            'username' => $this->container->getParameter('paypal.express_checkout_via_token.username'),
            'password' => $this->container->getParameter('paypal.express_checkout_via_token.password'),
            'signature' => $this->container->getParameter('paypal.express_checkout_via_token.signature'),
            'token' => $this->container->getParameter('paypal.express_checkout_via_token.token'),
            'tokenSecret' => $this->container->getParameter('paypal.express_checkout_via_token.tokenSecret'),
            'sandbox' => true
        ));
    }
}