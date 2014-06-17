<?php
namespace Acme\KlarnaBundle\Controller;

use Acme\PaymentBundle\Model\PaymentDetails;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PurchaseExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/prepare_simple_purchase", 
     *   name="acme_klarna_prepare_simple_purchase"
     * )
     * 
     * @Extra\Template
     */
    public function prepareAction(Request $request)
    {
        $cartItems = array(
            array(
                'reference' => '123456789',
                'name' => 'Klarna t-shirt',
                'quantity' => 2,
                'unit_price' => 12300,
                'discount_rate' => 1000,
                'tax_rate' => 2500
            ),
            array(
                'type' => 'shipping_fee',
                'reference' => 'SHIPPING',
                'name' => 'Shipping Fee',
                'quantity' => 1,
                'unit_price' => 4900,
                'tax_rate' => 2500
            )
        );

        $paymentName = 'klarna_checkout';

        if ($request->isMethod('POST')) {
            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            /** @var $paymentDetails PaymentDetails */
            $details = $storage->createModel();
            $details['purchase_country'] = 'SE';
            $details['purchase_currency'] = 'SEK';
            $details['locale'] = 'sv-se';
            $storage->updateModel($details);

            $captureToken = $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $details,
                'acme_payment_details_view'
            );

            $details['merchant'] = array(
                'terms_uri' => 'http://example.com/terms',
                'checkout_uri' => 'http://example.com/fuck',
                'confirmation_uri' => $captureToken->getTargetUrl(),
                'push_uri' => $this->getTokenFactory()->createNotifyToken($paymentName, $details)->getTargetUrl()
            );
            $details['cart'] = array(
                'items' => $cartItems
            );
            $storage->updateModel($details);

            return $this->redirect($captureToken->getTargetUrl());
        }
        
        return array(
            'cartItems' => $cartItems
        );
    }

    /**
     * @return RegistryInterface
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }

    /**
     * @return GenericTokenFactoryInterface
     */
    protected function getTokenFactory()
    {
        return $this->get('payum.security.token_factory');
    }
}