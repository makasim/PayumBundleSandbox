<?php
namespace Acme\PayexBundle\Controller;

use Acme\PaymentBundle\Model\AgreementDetails;
use Acme\PaymentBundle\Model\PaymentDetails;
use Payum\Core\Request\BinaryMaskStatusRequest;
use Payum\Core\Request\SyncRequest;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Model\Identificator;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Payex\Api\AgreementApi;
use Payum\Payex\Api\OrderApi;
use Payum\Payex\Request\Api\CreateAgreementRequest;
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
        
        $agreementStorage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\AgreementDetails');

        if ($request->get('agreementRef')) {
            $syncAgreement = new SyncRequest(new Identificator(
                $request->get('agreementRef'),
                'Acme\PaymentBundle\Model\AgreementDetails'
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
                $paymentStorage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

                /** @var $paymentDetails PaymentDetails */
                $paymentDetails = $paymentStorage->createModel();
                $paymentDetails['price'] = 1000;
                $paymentDetails['priceArgList'] = '';
                $paymentDetails['vat'] = 0;
                $paymentDetails['currency'] = 'NOK';
                $paymentDetails['orderId'] = 123;
                $paymentDetails['productNumber'] = 123;
                $paymentDetails['purchaseOperation'] = OrderApi::PURCHASEOPERATION_SALE;
                $paymentDetails['view'] = OrderApi::VIEW_CREDITCARD;
                $paymentDetails['description'] = 'a desc';
                $paymentDetails['clientIPAddress'] = $request->getClientIp();
                $paymentDetails['clientIdentifier'] = '';
                $paymentDetails['additionalValues'] = '';
                $paymentDetails['agreementRef'] = $agreement->getAgreementRef();
                $paymentDetails['clientLanguage'] = 'en-US';

                $paymentStorage->updateModel($paymentDetails);

                $captureToken = $this->getTokenFactory()->createCaptureToken(
                    $paymentName,
                    $paymentDetails,
                    'acme_payex_one_click_confirm_agreement',
                    array('agreementRef' => $agreement->getAgreementRef())
                );

                $paymentDetails['Returnurl'] = $captureToken->getTargetUrl();
                $paymentDetails['Cancelurl'] = $captureToken->getTargetUrl();
                $paymentStorage->updateModel($paymentDetails);

                return $this->redirect($captureToken->getTargetUrl());
            }
        } else {
            /** @var AgreementDetails $agreement */
            $agreement = $agreementStorage->createModel();
            $agreement['maxAmount'] = 10000;
            $agreement['purchaseOperation'] = AgreementApi::PURCHASEOPERATION_AUTHORIZATION;
            $agreement['merchantRef'] = 'aRef';
            $agreement['description'] = 'aDesc';
            $agreement['startDate'] = '';
            $agreement['stopDate'] = '';

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
        $form->handleRequest($request);
        if ($form->isValid()) {

            $paymentStorage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            /** @var $paymentDetails PaymentDetails */
            $paymentDetails = $paymentStorage->createModel();
            $paymentDetails['price'] = 1000;
            $paymentDetails['currency'] = 'NOK';
            $paymentDetails['orderId'] = 123;
            $paymentDetails['productNumber'] = 123;
            $paymentDetails['purchaseOperation'] = OrderApi::PURCHASEOPERATION_SALE;
            $paymentDetails['description'] = 'a desc';
            $paymentDetails['agreementRef'] = $request->get('agreementRef');
            $paymentDetails['autoPay'] = true;

            $paymentStorage->updateModel($paymentDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
            );

            $paymentDetails['Returnurl'] = $captureToken->getTargetUrl();
            $paymentDetails['Cancelurl'] = $captureToken->getTargetUrl();
            $paymentStorage->updateModel($paymentDetails);

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