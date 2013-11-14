<?php
namespace Acme\PayexBundle\Controller;

use Payum\Bundle\PayumBundle\Security\TokenFactory;
use Payum\Payex\Api\OrderApi;
use Payum\Payex\Model\PaymentDetails;
use Payum\Registry\RegistryInterface;
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

            $storage = $this->getPayum()->getStorageForClass(
                'Acme\PayexBundle\Model\PaymentDetails',
                $paymentName
            );

            /** @var $paymentDetails PaymentDetails */
            $paymentDetails = $storage->createModel();
            $paymentDetails->setPrice($data['amount'] * 100);
            $paymentDetails->setPriceArgList('');
            $paymentDetails->setVat(0);
            $paymentDetails->setCurrency($data['currency']);
            $paymentDetails->setOrderId(123);
            $paymentDetails->setProductNumber(123);
            $paymentDetails->setPurchaseOperation(OrderApi::PURCHASEOPERATION_SALE);
            $paymentDetails->setView(OrderApi::VIEW_CREDITCARD);
            $paymentDetails->setDescription('a desc');
            $paymentDetails->setClientIPAddress($request->getClientIp());
            $paymentDetails->setClientIdentifier('');
            $paymentDetails->setAdditionalValues('');
            $paymentDetails->setAgreementRef('');
            $paymentDetails->setClientLanguage('en-US');

            $storage->updateModel($paymentDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
            );

            $paymentDetails->setReturnurl($captureToken->getTargetUrl());
            $paymentDetails->setCancelurl($captureToken->getTargetUrl());
            $storage->updateModel($paymentDetails);

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

            $storage = $this->getPayum()->getStorageForClass(
                'Acme\PayexBundle\Model\PaymentDetails',
                $paymentName
            );

            /** @var $paymentDetails PaymentDetails */
            $paymentDetails = $storage->createModel();
            $paymentDetails->setPrice($data['amount'] * 100);
            $paymentDetails->setPriceArgList('');
            $paymentDetails->setVat(0);
            $paymentDetails->setCurrency($data['currency']);
            $paymentDetails->setOrderId(123);
            $paymentDetails->setProductNumber(123);
            $paymentDetails->setPurchaseOperation(OrderApi::PURCHASEOPERATION_SALE);
            $paymentDetails->setView(OrderApi::VIEW_CREDITCARD);
            $paymentDetails->setDescription('a desc');
            $paymentDetails->setClientIPAddress($request->getClientIp());
            $paymentDetails->setClientIdentifier('');
            $paymentDetails->setAdditionalValues('');
            $paymentDetails->setAgreementRef('');
            $paymentDetails->setClientLanguage('en-US');

            $storage->updateModel($paymentDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
            );

            $paymentDetails->setReturnurl($captureToken->getTargetUrl());
            $paymentDetails->setCancelurl($captureToken->getTargetUrl());
            $storage->updateModel($paymentDetails);

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
     * @return TokenFactory
     */
    protected function getTokenFactory()
    {
        return $this->get('payum.security.token_factory');
    }
}