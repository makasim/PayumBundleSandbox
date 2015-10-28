<?php
namespace Acme\PayexBundle\Controller;

use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Payex\Api\OrderApi;
use Payum\Core\Registry\RegistryInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class PurchaseExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/prepare_simple_purchase",
     *   name="acme_payex_prepare_simple_purchase"
     * )
     *
     * @Extra\Template
     */
    public function prepareAction(Request $request)
    {
        $gatewayName = 'payex';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment['price'] = $data['amount'] * 100;
            $payment['priceArgList'] = '';
            $payment['vat'] = 0;
            $payment['currency'] = $data['currency'];
            $payment['orderId'] = 123;
            $payment['productNumber'] = 123;
            $payment['purchaseOperation'] = OrderApi::PURCHASEOPERATION_SALE;
            $payment['view'] = OrderApi::VIEW_CREDITCARD;
            $payment['description'] = 'a desc';
            $payment['clientIPAddress'] = $request->getClientIp();
            $payment['clientIdentifier'] = '';
            $payment['additionalValues'] = '';
            $payment['agreementRef'] = '';
            $payment['clientLanguage'] = 'en-US';
            $payment['autoPay'] = false;

            $storage->update($payment);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_details_view'
            );

            $payment['returnUrl'] = $captureToken->getTargetUrl();
            $payment['cancelUrl'] = $captureToken->getTargetUrl();
            $storage->update($payment);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_purchase_with_transaction_callback",
     *   name="acme_payex_prepare_purchase_with_transaction_callback"
     * )
     *
     * @Extra\Template
     */
    public function prepareWithTransactionCallbackAction(Request $request)
    {
        $gatewayName = 'payex';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment['price'] = $data['amount'] * 100;
            $payment['priceArgList'] = '';
            $payment['vat'] = 0;
            $payment['currency'] = $data['currency'];
            $payment['orderId'] = 123;
            $payment['productNumber'] = 123;
            $payment['purchaseOperation'] = OrderApi::PURCHASEOPERATION_SALE;
            $payment['view'] = OrderApi::VIEW_CREDITCARD;
            $payment['description'] = 'a desc';
            $payment['clientIPAddress'] = $request->getClientIp();
            $payment['clientIdentifier'] = '';
            $payment['additionalValues'] = '';
            $payment['agreementRef'] = '';
            $payment['clientLanguage'] = 'en-US';

            $storage->update($payment);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_details_view'
            );

            $payment['returnUrl'] = $captureToken->getTargetUrl();
            $payment['cancelUrl'] = $captureToken->getTargetUrl();
            $storage->update($payment);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
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
