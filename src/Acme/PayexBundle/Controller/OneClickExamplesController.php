<?php
namespace Acme\PayexBundle\Controller;

use Acme\PayexBundle\Model\AgreementDetails;
use Payum\Bundle\PayumBundle\Service\TokenFactory;
use Payum\Payex\Api\AgreementApi;
use Payum\Payex\Api\OrderApi;
use Payum\Payex\Model\PaymentDetails;
use Payum\Payex\Request\Api\CreateAgreementRequest;
use Payum\Request\BinaryMaskStatusRequest;
use Payum\Request\SyncRequest;
use Payum\Registry\RegistryInterface;
use Payum\Storage\Identificator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;


class OneClickExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/one_click/confirm_agreement",
     *   name="acme_payex_one_click_confirm_agreement"
     * )
     * 
     * @Extra\Template()
     */
    public function confirmAgreementAction(Request $request)
    {
        $paymentName = 'payex_agreement';
        
        $agreementStorage = $this->getPayum()->getStorageForClass(
            'Acme\PayexBundle\Model\AgreementDetails',
            $paymentName
        );

        if ($request->get('agreementRef')) {
            $syncAgreement = new SyncRequest(new Identificator(
                $request->get('agreementRef'),
                'Acme\PayexBundle\Model\AgreementDetails'
            ));
            $this->getPayum()->getPayment($paymentName)->execute($syncAgreement);

            /** @var AgreementDetails $agreement */
            $agreement = $syncAgreement->getModel();
            
            $agreementStatus = new BinaryMaskStatusRequest($agreement);

            $this->getPayum()->getPayment($paymentName)->execute($agreementStatus);

            if ($agreementStatus->isSuccess()) {
                return $this->redirect($this->generateUrl('acme_payex_one_click_purchase', array(
                    'agreementRef' => $agreement->getAgreementRef()
                )));
            } else if ($agreementStatus->isNew()) {
                $paymentStorage = $this->getPayum()->getStorageForClass(
                    'Acme\PayexBundle\Model\PaymentDetails',
                    $paymentName
                );

                /** @var $paymentDetails PaymentDetails */
                $paymentDetails = $paymentStorage->createModel();
                $paymentDetails->setPrice(1000);
                $paymentDetails->setPriceArgList('');
                $paymentDetails->setVat(0);
                $paymentDetails->setCurrency('NOK');
                $paymentDetails->setOrderId(123);
                $paymentDetails->setProductNumber(123);
                $paymentDetails->setPurchaseOperation(OrderApi::PURCHASEOPERATION_SALE);
                $paymentDetails->setView(OrderApi::VIEW_CREDITCARD);
                $paymentDetails->setDescription('a desc');
                $paymentDetails->setClientIPAddress($request->getClientIp());
                $paymentDetails->setClientIdentifier('');
                $paymentDetails->setAdditionalValues('');
                $paymentDetails->setAgreementRef($agreement->getAgreementRef());
                $paymentDetails->setClientLanguage('en-US');

                $paymentStorage->updateModel($paymentDetails);

                $captureToken = $this->getTokenFactory()->createTokenForCaptureRoute(
                    $paymentName,
                    $paymentDetails,
                    'acme_payex_one_click_confirm_agreement',
                    array('agreementRef' => $agreement->getAgreementRef())
                );

                $paymentDetails->setReturnurl($captureToken->getTargetUrl());
                $paymentDetails->setCancelurl($captureToken->getTargetUrl());
                $paymentStorage->updateModel($paymentDetails);

                return $this->redirect($captureToken->getTargetUrl());
            }
        } else {
            /** @var AgreementDetails $agreement */
            $agreement = $agreementStorage->createModel();
            $agreement->setMaxAmount(10000);
            $agreement->setPurchaseOperation(AgreementApi::PURCHASEOPERATION_AUTHORIZATION);
            $agreement->setMerchantRef('aRef');
            $agreement->setDescription('aDesc');
            $agreement->setStartDate('');
            $agreement->setStopDate('');

            $this->getPayum()->getPayment($paymentName)->execute(new CreateAgreementRequest($agreement));
            $this->getPayum()->getPayment($paymentName)->execute(new SyncRequest($agreement));

            $agreementStatus = new BinaryMaskStatusRequest($agreement);
            $this->getPayum()->getPayment($paymentName)->execute($agreementStatus);
        }
        
        return array(
            'agreementStatus' => $agreementStatus
        );
    }
    
    /**
     * @Extra\Route(
     *   "/one_click/purchase",
     *   name="acme_payex_one_click_purchase"
     * )
     * 
     * @Extra\Template()
     */
    public function purchaseAction(Request $request)
    {
        $paymentName = 'payex_agreement';
        
        $form = $this->createPurchaseForm();
        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {

                $paymentStorage = $this->getPayum()->getStorageForClass(
                    'Acme\PayexBundle\Model\PaymentDetails',
                    $paymentName
                );

                /** @var $paymentDetails PaymentDetails */
                $paymentDetails = $paymentStorage->createModel();
                $paymentDetails->setPrice(1000);
                $paymentDetails->setCurrency('NOK');
                $paymentDetails->setOrderId(123);
                $paymentDetails->setProductNumber(123);
                $paymentDetails->setPurchaseOperation(OrderApi::PURCHASEOPERATION_SALE);
                $paymentDetails->setDescription('a desc');
                $paymentDetails->setAgreementRef($request->get('agreementRef'));
                $paymentDetails->setAutoPay(true);

                $paymentStorage->updateModel($paymentDetails);

                $captureToken = $this->getTokenFactory()->createTokenForCaptureRoute(
                    $paymentName,
                    $paymentDetails,
                    'acme_payment_details_view'
                );

                $paymentDetails->setReturnurl($captureToken->getTargetUrl());
                $paymentDetails->setCancelurl($captureToken->getTargetUrl());
                $paymentStorage->updateModel($paymentDetails);

                return $this->redirect($captureToken->getTargetUrl());
            }
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