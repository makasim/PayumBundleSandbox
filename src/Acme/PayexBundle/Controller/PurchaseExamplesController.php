<?php
namespace Acme\PayexBundle\Controller;

use Acme\PaymentBundle\Model\PaymentDetails;
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
        $paymentName = 'payex';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            /** @var $paymentDetails PaymentDetails */
            $paymentDetails = $storage->create();
            $paymentDetails['price'] = $data['amount'] * 100;
            $paymentDetails['priceArgList'] = '';
            $paymentDetails['vat'] = 0;
            $paymentDetails['currency'] = $data['currency'];
            $paymentDetails['orderId'] = 123;
            $paymentDetails['productNumber'] = 123;
            $paymentDetails['purchaseOperation'] = OrderApi::PURCHASEOPERATION_SALE;
            $paymentDetails['view'] = OrderApi::VIEW_CREDITCARD;
            $paymentDetails['description'] = 'a desc';
            $paymentDetails['clientIPAddress'] = $request->getClientIp();
            $paymentDetails['clientIdentifier'] = '';
            $paymentDetails['additionalValues'] = '';
            $paymentDetails['agreementRef'] = '';
            $paymentDetails['clientLanguage'] = 'en-US';
            $paymentDetails['autoPay'] = false;

            $storage->update($paymentDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
            );

            $paymentDetails['returnUrl'] = $captureToken->getTargetUrl();
            $paymentDetails['cancelUrl'] = $captureToken->getTargetUrl();
            $storage->update($paymentDetails);

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
        $paymentName = 'payex';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            /** @var $paymentDetails PaymentDetails */
            $paymentDetails = $storage->create();
            $paymentDetails['price'] = $data['amount'] * 100;
            $paymentDetails['priceArgList'] = '';
            $paymentDetails['vat'] = 0;
            $paymentDetails['currency'] = $data['currency'];
            $paymentDetails['orderId'] = 123;
            $paymentDetails['productNumber'] = 123;
            $paymentDetails['purchaseOperation'] = OrderApi::PURCHASEOPERATION_SALE;
            $paymentDetails['view'] = OrderApi::VIEW_CREDITCARD;
            $paymentDetails['description'] = 'a desc';
            $paymentDetails['clientIPAddress'] = $request->getClientIp();
            $paymentDetails['clientIdentifier'] = '';
            $paymentDetails['additionalValues'] = '';
            $paymentDetails['agreementRef'] = '';
            $paymentDetails['clientLanguage'] = 'en-US';

            $storage->update($paymentDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
            );

            $paymentDetails['returnUrl'] = $captureToken->getTargetUrl();
            $paymentDetails['cancelUrl'] = $captureToken->getTargetUrl();
            $storage->update($paymentDetails);

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
