<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Bundle\PayumBundle\Security\TokenFactory;
use Payum\Core\Registry\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SimplePurchaseKlarnaCheckoutController extends Controller
{
    public function prepareAction(Request $request)
    {
        $paymentName = 'klarna_checkout';

        // TOOD:
    }

    /**
     * @return RegistryInterface
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }

    /**
     * @return TokenFactory
     */
    protected function getTokenFactory()
    {
        return $this->get('payum.security.token_factory');
    }
}