<?php
namespace Acme\KlarnaBundle\Controller;

use Acme\PaymentBundle\Model\PaymentDetails;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
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
     * @Extra\Route(
     *   "/prepare_invoice",
     *   name="acme_klarna_prepare_invoice"
     * )
     *
     * @Extra\Template
     */
    public function prepareInvoiceAction(Request $request)
    {
        $paymentName = 'klarna_invoice';

        /** @link http://developers.klarna.com/en/testing/invoice-and-account */
        $pno = '410321-9202';

        $payment = $this->getPayum()->getPayment($paymentName);
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
            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            /** @var $details PaymentDetails */
            $details = $storage->createModel();

            foreach ($rawDetails as $name => $value) {
                $details[$name] = $value;
            }

            $storage->updateModel($details);

            $captureToken = $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $details,
                'acme_payment_details_view'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'details' => $rawDetails
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
        $paymentName = 'klarna_invoice';

        /** @link http://developers.klarna.com/en/testing/invoice-and-account */
        $pno = '410321-9202';

        $payment = $this->getPayum()->getPayment($paymentName);
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
            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            /** @var $details PaymentDetails */
            $details = $storage->createModel();

            foreach ($rawDetails as $name => $value) {
                $details[$name] = $value;
            }

            $storage->updateModel($details);

            $captureToken = $captureToken = $this->getTokenFactory()->createAuthorizeToken(
                $paymentName,
                $details,
                'acme_payment_details_view'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'details' => $rawDetails
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