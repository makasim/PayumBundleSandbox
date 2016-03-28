<?php
namespace Acme\KlarnaBundle\Controller;

use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Payum;
use Payum\Klarna\Invoice\Request\Api\GetAddresses;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PurchaseExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/prepare_checkout",
     *   name="acme_klarna_prepare_checkout"
     * )
     *
     * @Extra\Template
     */
    public function prepareCheckoutAction(Request $request)
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

        $gatewayName = 'klarna_checkout';

        if ($request->isMethod('POST')) {
            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment['purchase_country'] = 'SE';
            $payment['purchase_currency'] = 'SEK';
            $payment['locale'] = 'sv-se';
            $storage->update($payment);

            $authorizeToken = $this->getPayum()->getTokenFactory()->createAuthorizeToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            $payment['merchant'] = array(
                'terms_uri' => 'http://example.com/terms',
                'checkout_uri' => 'http://example.com/fuck',
                'confirmation_uri' => $authorizeToken->getTargetUrl(),
                'push_uri' => $this->getPayum()->getTokenFactory()->createNotifyToken($gatewayName, $payment)->getTargetUrl()
            );
            $payment['cart'] = array(
                'items' => $cartItems
            );
            $storage->update($payment);

            return $this->redirect($authorizeToken->getTargetUrl());
        }

        return array(
            'cartItems' => $cartItems
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_invoice",
     *   name="acme_klarna_prepare_invoice"
     * )
     *
     * @Extra\Template
     */
    public function prepareInvoiceAction(Request $request)
    {
        $gatewayName = 'klarna_invoice';

        /** @link http://developers.klarna.com/en/testing/invoice-and-account */
        $pno = '410321-9202';

        $payment = $this->getPayum()->getGateway($gatewayName);
        $payment->execute($getAddresses = new GetAddresses($pno));

        $firstAddress = $getAddresses->getFirstAddress();
        $firstAddress->setEmail($firstAddress->getEmail() ?: 'info@payum.com');
        $firstAddress->setTelno($firstAddress->getTelno() ?: '0700 00 00 00');

        $rawDetails = array(
            'pno' => '410321-9202',
            'amount' => -1,
            'gender' => \KlarnaFlags::MALE,
            'articles' => array(
                array(
                    'qty' => 4,
                    'artNo' => 'HANDLING',
                    'title' => 'Handling fee',
                    'price' => '50.99',
                    'vat' => '25',
                    'discount' => '0',
                    'flags' => \KlarnaFlags::INC_VAT | \KlarnaFlags::IS_HANDLING
                ),
            ),
            'billing_address' => $firstAddress->toArray(),
            'shipping_address' => $firstAddress->toArray(),
        );

        if ($request->isMethod('POST')) {
            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

            /** @var $payment PaymentDetails */
            $payment = $storage->create();

            foreach ($rawDetails as $name => $value) {
                $payment[$name] = $value;
            }

            $storage->update($payment);

            $captureToken = $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'payment' => $rawDetails
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_authorize_invoice",
     *   name="acme_klarna_prepare_authorize_invoice"
     * )
     *
     * @Extra\Template
     */
    public function prepareAuthorizeInvoiceAction(Request $request)
    {
        $gatewayName = 'klarna_invoice';

        /** @link http://developers.klarna.com/en/testing/invoice-and-account */
        $pno = '410321-9202';

        $payment = $this->getPayum()->getGateway($gatewayName);
        $payment->execute($getAddresses = new GetAddresses($pno));

        $firstAddress = $getAddresses->getFirstAddress();
        $firstAddress->setEmail($firstAddress->getEmail() ?: 'info@payum.com');
        $firstAddress->setTelno($firstAddress->getTelno() ?: '0700 00 00 00');

        $rawDetails = array(
            'pno' => '410321-9202',
            'amount' => -1,
            'gender' => \KlarnaFlags::MALE,
            'articles' => array(
                array(
                    'qty' => 4,
                    'artNo' => 'HANDLING',
                    'title' => 'Handling fee',
                    'price' => '50.99',
                    'vat' => '25',
                    'discount' => '0',
                    'flags' => \KlarnaFlags::INC_VAT | \KlarnaFlags::IS_HANDLING
                ),
            ),
            'billing_address' => $firstAddress->toArray(),
            'shipping_address' => $firstAddress->toArray(),
        );

        if ($request->isMethod('POST')) {
            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

            /** @var $payment PaymentDetails */
            $payment = $storage->create();

            foreach ($rawDetails as $name => $value) {
                $payment[$name] = $value;
            }

            $storage->update($payment);

            $captureToken = $captureToken = $this->getPayum()->getTokenFactory()->createAuthorizeToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'payment' => $rawDetails
        );
    }

    /**
     * @return Payum
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}
