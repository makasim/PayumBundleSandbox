<?php
namespace Acme\PaymentBundle\Payum\Api;

use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as HttpGuzzle6Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Payum\Core\Bridge\Httplug\HttplugClient;
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
        $options = [
            'username' => $this->container->getParameter('paypal.express_checkout.username'),
            'password' => $this->container->getParameter('paypal.express_checkout.password'),
            'signature' => $this->container->getParameter('paypal.express_checkout.signature'),
            'sandbox' => true
        ];

        return new Api(
            $options,
            new HttplugClient(new HttpGuzzle6Client(new GuzzleClient())),
            new GuzzleMessageFactory()
        );
    }
}