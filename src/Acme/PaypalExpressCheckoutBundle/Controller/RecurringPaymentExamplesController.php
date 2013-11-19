<?php
namespace Acme\PaypalExpressCheckoutBundle\Controller;

use Acme\PaymentBundle\Model\AgreementDetails;
use Acme\PaymentBundle\Model\RecurringPaymentDetails;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Bundle\PayumBundle\Security\TokenFactory;
use Payum\Paypal\ExpressCheckout\Nvp\Request\Api\CreateRecurringPaymentProfileRequest;
use Payum\Paypal\ExpressCheckout\Nvp\Request\Api\ManageRecurringPaymentsProfileStatusRequest;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Payum\Request\SyncRequest;
use Payum\Request\BinaryMaskStatusRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecurringPaymentExamplesController extends PayumController
{
    /**
     * @return array
     */
    protected function getWeatherForecastSubscriptionDetails()
    {
        return array(
            'description' => 'Subscribe to the weather forecast for a week. It is 0.05$ per day.',
            'price' => 0.05,
            'currency' => 'USD',
            'frequency' => 7
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_recurring_payment",
     *   name="acme_paypal_express_checkout_prepare_recurring_payment"
     * )
     *
     * @Extra\Template
     */
    public function prepareAction(Request $request)
    {
        $paymentName = 'paypal_express_checkout_recurring_payment';
        
        $subscription = $this->getWeatherForecastSubscriptionDetails();
        
        if ($request->isMethod('POST')) {
            $storage = $this->getPayum()->getStorageForClass(
                'Acme\PaymentBundle\Entity\AgreementDetails',
                $paymentName
            );
            
            /** @var $billingAgreementDetails AgreementDetails */
            $billingAgreementDetails = $storage->createModel();
            $billingAgreementDetails->setPaymentrequestAmt(0,  $amount = 0);
            $billingAgreementDetails->setLBillingtype(0, Api::BILLINGTYPE_RECURRING_PAYMENTS);
            $billingAgreementDetails->setLBillingagreementdescription(0, $subscription['description']);
            $billingAgreementDetails->setNoshipping(1);

            $storage->updateModel($billingAgreementDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $billingAgreementDetails,
                'acme_paypal_express_checkout_create_recurring_payment'
            );

            $billingAgreementDetails->setReturnurl($captureToken->getTargetUrl());
            $billingAgreementDetails->setCancelurl($captureToken->getTargetUrl());
            $billingAgreementDetails->setInvnum($billingAgreementDetails->getId());
            $storage->updateModel($billingAgreementDetails);

            return $this->redirect($captureToken->getTargetUrl());
        }
        
        return array(
            'subscription' => $subscription,
            'paymentName' => $paymentName
        );
    }

    /**
     * @Extra\Route(
     *   "/create_recurring_payment/{payum_token}",
     *   name="acme_paypal_express_checkout_create_recurring_payment"
     * )
     */
    public function createBillingAgreementAction(Request $request)
    {
        $token = $this->getHttpRequestVerifier()->verify($request);

        $payment = $this->getPayum()->getPayment($token->getPaymentName());

        $billingAgreementStatus = new BinaryMaskStatusRequest($token);
        $payment->execute($billingAgreementStatus);

        $recurringPaymentStatus = null;
        if (false == $billingAgreementStatus->isSuccess()) {
            throw new HttpException(400, 'Billing agreement status is not success.');
        }

        $subscription = $this->getWeatherForecastSubscriptionDetails();
        $billingAgreementDetails = $billingAgreementStatus->getModel();

        $recurringPaymentStorage = $this->getPayum()->getStorageForClass(
            'Acme\PaymentBundle\Model\RecurringPaymentDetails',
            $token->getPaymentName()
        );

        $recurringPaymentDetails = $recurringPaymentStorage->createModel();
        $recurringPaymentDetails->setToken($billingAgreementDetails->getToken());
        $recurringPaymentDetails->setDesc($billingAgreementDetails->getLBillingagreementdescription(0));
        $recurringPaymentDetails->setEmail($billingAgreementDetails->getEmail());
        $recurringPaymentDetails->setAmt($subscription['price']);
        $recurringPaymentDetails->setCurrencycode($subscription['currency']);
        $recurringPaymentDetails->setBillingfrequency($subscription['frequency']);
        $recurringPaymentDetails->setProfilestartdate(date(DATE_ATOM));
        $recurringPaymentDetails->setBillingperiod(Api::BILLINGPERIOD_DAY);

        $payment->execute(new CreateRecurringPaymentProfileRequest($recurringPaymentDetails));
        $payment->execute(new SyncRequest($recurringPaymentDetails));

        $recurringPaymentStatus = new BinaryMaskStatusRequest($recurringPaymentDetails);
        $payment->execute($recurringPaymentStatus);

        return $this->redirect($this->generateUrl('acme_paypal_express_checkout_view_recurring_payment', array(
            'paymentName' => $token->getPaymentName(),
            'billingAgreementId' => $billingAgreementDetails->getId(),
            'recurringPaymentId' => $recurringPaymentDetails->getId(),
        )));
    }

    /**
     * @Extra\Route(
     *   "/payment/{paymentName}/details/{billingAgreementId}/{recurringPaymentId}",
     *   name="acme_paypal_express_checkout_view_recurring_payment"
     * )
     *
     * @Extra\Template
     */
    public function viewRecurringPaymentDetailsAction($paymentName, $billingAgreementId, $recurringPaymentId, Request $request)
    {
        $payment = $this->getPayum()->getPayment($paymentName);

        $billingAgreementStorage = $this->getPayum()->getStorageForClass(
            'Acme\PaymentBundle\Entity\AgreementDetails',
            $paymentName
        );

        $billingAgreementDetails = $billingAgreementStorage->findModelById($billingAgreementId);

        $billingAgreementStatus = new BinaryMaskStatusRequest($billingAgreementDetails);
        $payment->execute($billingAgreementStatus);

        $recurringPaymentStorage = $this->getPayum()->getStorageForClass(
            'Acme\PaymentBundle\Model\RecurringPaymentDetails',
            $paymentName
        );

        $recurringPaymentDetails = $recurringPaymentStorage->findModelById($recurringPaymentId);

        $recurringPaymentStatus = new BinaryMaskStatusRequest($recurringPaymentDetails);
        $payment->execute($recurringPaymentStatus);

        $cancelToken = null;
        if ($recurringPaymentStatus->isSuccess()) {
            $cancelToken = $this->getTokenFactory()->createTokenForRoute(
                $paymentName, 
                $recurringPaymentDetails, 
                'acme_paypal_express_checkout_cancel_recurring_payment',
                array(),
                $request->attributes->get('_route'),
                $request->attributes->get('_route_params')
            );
        }

        return array(
            'cancelToken' => $cancelToken, 
            'billingAgreementStatus' => $billingAgreementStatus,
            'recurringPaymentStatus' => $recurringPaymentStatus,
            'paymentName' => $paymentName
        );
    }

    /**
     * @Extra\Route(
     *   "/cancel_recurring_payment/{payum_token}",
     *   name="acme_paypal_express_checkout_cancel_recurring_payment"
     * )
     */
    public function cancelRecurringPaymentAction(Request $request)
    {
        $token = $this->getHttpRequestVerifier()->verify($request);
        
        $payment = $this->getPayum()->getPayment($token->getPaymentName());

        $status = new BinaryMaskStatusRequest($token);
        $payment->execute($status);
        if (false == $status->isSuccess()) {
            throw new HttpException(400, 'The model status must be success.');
        }
        if (false == $status->getModel() instanceof RecurringPaymentDetails) {
            throw new HttpException(400, 'The model associated with token not a recurring payment one.');
        }
            
        /** @var RecurringPaymentDetails $recurringPayment */
        $recurringPaymentDetails = $status->getModel();
        $recurringPaymentDetails->setAction(Api::RECURRINGPAYMENTACTION_CANCEL);
        
        $payment->execute(new ManageRecurringPaymentsProfileStatusRequest($recurringPaymentDetails));
        $payment->execute(new SyncRequest($recurringPaymentDetails));

        $this->getHttpRequestVerifier()->invalidate($token);

        return $this->redirect($token->getAfterUrl());
    }

    /**
     * @Extra\Route(
     *   "/prepare_doctrine_recurring_payment",
     *   name="acme_paypal_express_checkout_prepare_recurring_payment_plus_doctrine"
     * )
     *
     * @Extra\Template
     */
    public function prepareDoctrineAction(Request $request)
    {
        $paymentName = 'paypal_express_checkout_recurring_payment_plus_doctrine';

        $subscription = $this->getWeatherForecastSubscriptionDetails();

        if ($request->isMethod('POST')) {
            $storage = $this->getPayum()->getStorageForClass(
                'Acme\PaymentBundle\Entity\AgreementDetails',
                $paymentName
            );

            /** @var $billingAgreementDetails AgreementDetails */
            $billingAgreementDetails = $storage->createModel();
            $billingAgreementDetails->setPaymentrequestAmt(0,  $amount = 0);
            $billingAgreementDetails->setLBillingtype(0, Api::BILLINGTYPE_RECURRING_PAYMENTS);
            $billingAgreementDetails->setLBillingagreementdescription(0, $subscription['description']);
            $billingAgreementDetails->setNoshipping(1);

            $storage->updateModel($billingAgreementDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $billingAgreementDetails,
                'acme_paypal_express_checkout_create_doctrine_recurring_payment'
            );

            $billingAgreementDetails->setReturnurl($captureToken->getTargetUrl());
            $billingAgreementDetails->setCancelurl($captureToken->getTargetUrl());
            $billingAgreementDetails->setInvnum($billingAgreementDetails->getId());
            $storage->updateModel($billingAgreementDetails);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'subscription' => $subscription,
            'paymentName' => $paymentName
        );
    }

    /**
     * @Extra\Route(
     *   "/create_doctrine_recurring_payment/{payum_token}",
     *   name="acme_paypal_express_checkout_create_doctrine_recurring_payment"
     * )
     */
    public function createDoctrineBillingAgreementAction(Request $request)
    {
        $token = $this->getHttpRequestVerifier()->verify($request);

        $payment = $this->getPayum()->getPayment($token->getPaymentName());

        $billingAgreementStatus = new BinaryMaskStatusRequest($token);
        $payment->execute($billingAgreementStatus);

        $recurringPaymentStatus = null;
        if (false == $billingAgreementStatus->isSuccess()) {
            throw new HttpException(400, 'Billing agreement status is not success.');
        }

        $subscription = $this->getWeatherForecastSubscriptionDetails();
        $billingAgreementDetails = $billingAgreementStatus->getModel();

        $recurringPaymentStorage = $this->getPayum()->getStorageForClass(
            'Acme\PaymentBundle\Entity\RecurringPaymentDetails',
            $token->getPaymentName()
        );

        $recurringPaymentDetails = $recurringPaymentStorage->createModel();
        $recurringPaymentDetails->setToken($billingAgreementDetails->getToken());
        $recurringPaymentDetails->setDesc($billingAgreementDetails->getLBillingagreementdescription(0));
        $recurringPaymentDetails->setEmail($billingAgreementDetails->getEmail());
        $recurringPaymentDetails->setAmt($subscription['price']);
        $recurringPaymentDetails->setCurrencycode($subscription['currency']);
        $recurringPaymentDetails->setBillingfrequency($subscription['frequency']);
        $recurringPaymentDetails->setProfilestartdate(date(DATE_ATOM));
        $recurringPaymentDetails->setBillingperiod(Api::BILLINGPERIOD_DAY);

        $payment->execute(new CreateRecurringPaymentProfileRequest($recurringPaymentDetails));
        $payment->execute(new SyncRequest($recurringPaymentDetails));

        $recurringPaymentStatus = new BinaryMaskStatusRequest($recurringPaymentDetails);
        $payment->execute($recurringPaymentStatus);

        return $this->redirect($this->generateUrl('acme_paypal_express_checkout_view_doctrine_recurring_payment', array(
            'paymentName' => $token->getPaymentName(),
            'billingAgreementId' => $billingAgreementDetails->getId(),
            'recurringPaymentId' => $recurringPaymentDetails->getId(),
        )));
    }

    /**
     * @Extra\Route(
     *   "/payment/{paymentName}/doctrine-details/{billingAgreementId}/{recurringPaymentId}",
     *   name="acme_paypal_express_checkout_view_doctrine_recurring_payment"
     * )
     *
     * @Extra\Template
     */
    public function viewDoctrineRecurringPaymentDetailsAction($paymentName, $billingAgreementId, $recurringPaymentId)
    {
        $payment = $this->getPayum()->getPayment($paymentName);

        $billingAgreementStorage = $this->getPayum()->getStorageForClass(
            'Acme\PaymentBundle\Entity\AgreementDetails',
            $paymentName
        );

        $billingAgreementDetails = $billingAgreementStorage->findModelById($billingAgreementId);

        $billingAgreementStatus = new BinaryMaskStatusRequest($billingAgreementDetails);
        $payment->execute($billingAgreementStatus);

        $recurringPaymentStorage = $this->getPayum()->getStorageForClass(
            'Acme\PaymentBundle\Entity\RecurringPaymentDetails',
            $paymentName
        );

        $recurringPaymentDetails = $recurringPaymentStorage->findModelById($recurringPaymentId);

        $recurringPaymentStatus = new BinaryMaskStatusRequest($recurringPaymentDetails);
        $payment->execute($recurringPaymentStatus);

        return array(
            'billingAgreementStatus' => $billingAgreementStatus,
            'recurringPaymentStatus' => $recurringPaymentStatus,
            'paymentName' => $paymentName
        );
    }

    /**
     * @return TokenFactory
     */
    protected function getTokenFactory()
    {
        return $this->get('payum.security.token_factory');
    }
}