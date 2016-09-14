<?php
namespace Acme\PaypalExpressCheckoutBundle\Controller;

use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Payum;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class PurchaseExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/prepare_simple_purchase_doctrine_orm",
     *   name="acme_paypal_express_checkout_prepare_simple_purchase_doctrine_orm"
     * )
     *
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function prepareSimplePurchaseAndDoctrineOrmAction(Request $request)
    {
        $gatewayName = 'paypal_express_checkout_and_doctrine_orm';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage(PaymentDetails::class);

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment['PAYMENTREQUEST_0_CURRENCYCODE'] = $data['currency'];
            $payment['PAYMENTREQUEST_0_AMT'] = $data['amount'];
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            $payment['INVNUM'] = $payment->getId();
            $storage->update($payment);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'gatewayName' => $gatewayName
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_simple_purchase_with_confirm_order_step",
     *   name="acme_paypal_express_checkout_prepare_simple_purchase_with_confirm_order_step"
     * )
     *
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function prepareSimplePurchaseWithConfirmOrderStepAction(Request $request)
    {
        $gatewayName = 'paypal_express_checkout_and_doctrine_orm';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage(PaymentDetails::class);

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment['PAYMENTREQUEST_0_CURRENCYCODE'] = $data['currency'];
            $payment['PAYMENTREQUEST_0_AMT'] = $data['amount'];
            $payment['AUTHORIZE_TOKEN_USERACTION'] = '';
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            $payment['INVNUM'] = $payment->getId();
            $storage->update($payment);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'gatewayName' => $gatewayName
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_simple_purchase_doctrine_mongo_odm",
     *   name="acme_paypal_express_checkout_prepare_simple_purchase_doctrine_mongo_odm"
     * )
     *
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function prepareSimplePurchaseAndDoctrineMongoOdmAction(Request $request)
    {
        $gatewayName = 'paypal_express_checkout_and_doctrine_mongo_odm';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Document\PaymentDetails');

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment['PAYMENTREQUEST_0_CURRENCYCODE'] = $data['currency'];
            $payment['PAYMENTREQUEST_0_AMT'] = $data['amount'];
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            $payment['INVNUM'] = $payment->getId();
            $storage->update($payment);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'gatewayName' => $gatewayName
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_purchase_configured_in_backend",
     *   name="acme_paypal_prepare_purchase_configured_in_backend"
     * )
     *
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function preparePurchaseConfiguredInBackendAction(Request $request)
    {
        $gatewayName = 'paypal_configured_in_backend';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Document\PaymentDetails');

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment['PAYMENTREQUEST_0_CURRENCYCODE'] = $data['currency'];
            $payment['PAYMENTREQUEST_0_AMT'] = $data['amount'];
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            $payment['INVNUM'] = $payment->getId();
            $storage->update($payment);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'gatewayName' => '', // dynamic we cannot show a code example
            'message' => 'This gateway must be configured in sonata backend. If you get an exception go to /admin/dashboard and configure it.',
        );
    }

    /**
     * @Extra\Route(
     *   "/digital_goods_purchase",
     *   name="acme_paypal_express_checkout_prepare_digital_goods_purchase"
     * )
     *
     * @Extra\Template
     */
    public function prepareDigitalGoodsAction(Request $request)
    {
        $gatewayName = 'paypal_express_checkout_and_doctrine_orm';

        $eBook = array(
            'author' => 'Jules Verne',
            'name' => 'The Mysterious Island',
            'description' => 'The Mysterious Island is a novel by Jules Verne, published in 1874.',
            'price' => 2.64,
            'currency_symbol' => '$',
            'currency' => 'USD',
            'quantity' => 2
        );

        if ('POST' === $request->getMethod()) {
            $storage = $this->getPayum()->getStorage(PaymentDetails::class);

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment['PAYMENTREQUEST_0_CURRENCYCODE'] = $eBook['currency'];
            $payment['PAYMENTREQUEST_0_AMT'] = $eBook['price'] * $eBook['quantity'];
            $payment['NOSHIPPING'] = Api::NOSHIPPING_NOT_DISPLAY_ADDRESS;
            $payment['REQCONFIRMSHIPPING'] = Api::REQCONFIRMSHIPPING_NOT_REQUIRED;
            $payment['L_PAYMENTREQUEST_0_ITEMCATEGORY0'] = Api::PAYMENTREQUEST_ITERMCATEGORY_DIGITAL;
            $payment['L_PAYMENTREQUEST_0_AMT0'] = $eBook['price'];
            $payment['L_PAYMENTREQUEST_0_QTY0'] = $eBook['quantity'];
            $payment['L_PAYMENTREQUEST_0_NAME0'] = $eBook['author'].'. '.$eBook['name'];
            $payment['L_PAYMENTREQUEST_0_DESC0'] = $eBook['description'];
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            $payment['INVNUM'] = $payment->getId();
            $storage->update($payment);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'book' => $eBook,
            'gatewayName' => $gatewayName
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_simple_purchase_with_custom_api",
     *   name="acme_paypal_express_checkout_prepare_simple_purchase_with_custom_api"
     * )
     *
     * @Extra\Template
     */
    public function prepareWithCustomApiAction(Request $request)
    {
        $gatewayName = 'paypal_express_checkout_and_custom_api';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage(PaymentDetails::class);

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment['PAYMENTREQUEST_0_CURRENCYCODE'] = $data['currency'];
            $payment['PAYMENTREQUEST_0_AMT'] = $data['amount'];
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            $payment['INVNUM'] = $payment->getId();
            $storage->update($payment);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'gatewayName' => $gatewayName,
            'paypal_usd_testuser_login' => $this->getParameter('paypal.express_checkout.usd_testuser_login'),
            'paypal_usd_testuser_password' => $this->getParameter('paypal.express_checkout.usd_testuser_password')
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_purchase_with_ipn_enabled",
     *   name="acme_paypal_express_checkout_prepare_purchase_with_ipn_enabled"
     * )
     *
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function prepareWithIpnEnabledAction(Request $request)
    {
        $gatewayName = 'paypal_express_checkout_with_ipn_enabled';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage(PaymentDetails::class);

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment['PAYMENTREQUEST_0_CURRENCYCODE'] = $data['currency'];
            $payment['PAYMENTREQUEST_0_AMT'] = $data['amount'];
            $storage->update($payment);

            $notifyToken = $this->getPayum()->getTokenFactory()->createNotifyToken($gatewayName, $payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            $payment['PAYMENTREQUEST_0_NOTIFYURL'] = $notifyToken->getTargetUrl();
            $payment['INVNUM'] = $payment->getId();
            $storage->update($payment);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'gatewayName' => $gatewayName
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_simple_authorize",
     *   name="acme_paypal_express_checkout_prepare_simple_authorize"
     * )
     *
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function prepareSimpleAuthorizeAction(Request $request)
    {
        $gatewayName = 'paypal_express_checkout_and_doctrine_orm';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage(PaymentDetails::class);

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment['PAYMENTREQUEST_0_CURRENCYCODE'] = $data['currency'];
            $payment['PAYMENTREQUEST_0_AMT'] = $data['amount'];
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createAuthorizeToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            $payment['INVNUM'] = $payment->getId();
            $storage->update($payment);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'gatewayName' => $gatewayName
        );
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('amount', null, array(
                'data' => 1,
                'constraints' => array(new Range(array('max' => 2)))
            ))
            ->add('currency', null, array('data' => 'USD'))
            ->getForm()
        ;
    }

    /**
     * @return Payum
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}
