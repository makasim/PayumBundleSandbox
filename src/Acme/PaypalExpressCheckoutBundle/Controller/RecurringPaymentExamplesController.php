<?php
namespace Acme\PaypalExpressCheckoutBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Payum\Registry\AbstractRegistry;
use Payum\Request\SyncRequest;
use Payum\Request\BinaryMaskStatusRequest;
use Payum\Paypal\ExpressCheckout\Nvp\Model\RecurringPaymentDetails;
use Payum\Paypal\ExpressCheckout\Nvp\Request\Api\CreateRecurringPaymentProfileRequest;
use Payum\Paypal\ExpressCheckout\Nvp\Request\Api\ManageRecurringPaymentsProfileStatusRequest;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Payum\Bundle\PayumBundle\Service\TokenizedTokenService;
use Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails;

class RecurringPaymentExamplesController extends Controller
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
                'Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails',
                $paymentName
            );
            
            /** @var $billingAgreementDetails PaymentDetails */
            $billingAgreementDetails = $storage->createModel();
            $billingAgreementDetails->setPaymentrequestAmt(0,  $amount = 0);
            $billingAgreementDetails->setLBillingtype(0, Api::BILLINGTYPE_RECURRING_PAYMENTS);
            $billingAgreementDetails->setLBillingagreementdescription(0, $subscription['description']);
            $billingAgreementDetails->setNoshipping(1);

            $storage->updateModel($billingAgreementDetails);

            $captureToken = $this->getTokenizedTokenService()->createTokenForCaptureRoute(
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
            'subscription' => $subscription
        );
    }

    /**
     * @Extra\Route(
     *   "/create_recurring_payment/{paymentName}/{token}",
     *   name="acme_paypal_express_checkout_create_recurring_payment"
     * )
     */
    public function createBillingAgreementAction($paymentName, $token)
    {
        $payment = $this->getPayum()->getPayment($paymentName);

        $token = $this->getTokenizedTokenService()->findTokenizedDetailsByToken($paymentName, $token);

        $billingAgreementStatus = new BinaryMaskStatusRequest($token);
        $payment->execute($billingAgreementStatus);

        $recurringPaymentStatus = null;
        if (false == $billingAgreementStatus->isSuccess()) {
            throw new HttpException(400, 'Billing agreement status is not success.');
        }

        $subscription = $this->getWeatherForecastSubscriptionDetails();
        $billingAgreementDetails = $billingAgreementStatus->getModel();

        $recurringPaymentStorage = $this->getPayum()->getStorageForClass(
            'Acme\PaypalExpressCheckoutBundle\Model\RecurringPaymentDetails',
            $paymentName
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
            'paymentName' => $paymentName,
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
            'Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails',
            $paymentName
        );

        $billingAgreementDetails = $billingAgreementStorage->findModelById($billingAgreementId);

        $billingAgreementStatus = new BinaryMaskStatusRequest($billingAgreementDetails);
        $payment->execute($billingAgreementStatus);

        $recurringPaymentStorage = $this->getPayum()->getStorageForClass(
            'Acme\PaypalExpressCheckoutBundle\Model\RecurringPaymentDetails',
            $paymentName
        );

        $recurringPaymentDetails = $recurringPaymentStorage->findModelById($recurringPaymentId);

        $recurringPaymentStatus = new BinaryMaskStatusRequest($recurringPaymentDetails);
        $payment->execute($recurringPaymentStatus);

        $cancelToken = null;
        if ($recurringPaymentStatus->isSuccess()) {
            $cancelToken = $this->getTokenizedTokenService()->createTokenForRoute(
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
        );
    }

    /**
     * @Extra\Route(
     *   "/{paymentName}/cancel_recurring_payment/{token}",
     *   name="acme_paypal_express_checkout_cancel_recurring_payment"
     * )
     */
    public function cancelRecurringPaymentAction($paymentName, $token)
    {
        try {
            if (false == $token = $this->getTokenizedTokenService()->findTokenizedDetailsByToken($paymentName, $token)) {
                throw $this->createNotFoundException('The TokenizedDetails with requested token not found.');
            }
            if ($paymentName !== $token->getPaymentName()) {
                throw new \InvalidArgumentException(sprintf('The paymentName %s not match one %s set in the token.', $paymentName, $token->getPaymentName()));
            }
            
            $payment = $this->getPayum()->getPayment($paymentName);

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

            $this->getPayum()->getStorageForClass($token, $paymentName)->deleteModel($token);

            return $this->redirect($token->getAfterUrl());
        } catch (HttpException $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            throw new HttpException(404, 'The input parameters not valid.', $e);
        }
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
                'Acme\PaypalExpressCheckoutBundle\Entity\PaymentDetails',
                $paymentName
            );

            /** @var $billingAgreementDetails PaymentDetails */
            $billingAgreementDetails = $storage->createModel();
            $billingAgreementDetails->setPaymentrequestAmt(0,  $amount = 0);
            $billingAgreementDetails->setLBillingtype(0, Api::BILLINGTYPE_RECURRING_PAYMENTS);
            $billingAgreementDetails->setLBillingagreementdescription(0, $subscription['description']);
            $billingAgreementDetails->setNoshipping(1);

            $storage->updateModel($billingAgreementDetails);

            $captureToken = $this->getTokenizedTokenService()->createTokenForCaptureRoute(
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
            'subscription' => $subscription
        );
    }

    /**
     * @Extra\Route(
     *   "/create_doctrine_recurring_payment/{paymentName}/{token}",
     *   name="acme_paypal_express_checkout_create_doctrine_recurring_payment"
     * )
     */
    public function createDoctrineBillingAgreementAction($paymentName, $token)
    {
        $payment = $this->getPayum()->getPayment($paymentName);

        $token = $this->getTokenizedTokenService()->findTokenizedDetailsByToken($paymentName, $token);

        $billingAgreementStatus = new BinaryMaskStatusRequest($token);
        $payment->execute($billingAgreementStatus);

        $recurringPaymentStatus = null;
        if (false == $billingAgreementStatus->isSuccess()) {
            throw new HttpException(400, 'Billing agreement status is not success.');
        }

        $subscription = $this->getWeatherForecastSubscriptionDetails();
        $billingAgreementDetails = $billingAgreementStatus->getModel();

        $recurringPaymentStorage = $this->getPayum()->getStorageForClass(
            'Acme\PaypalExpressCheckoutBundle\Entity\RecurringPaymentDetails',
            $paymentName
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
            'paymentName' => $paymentName,
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
            'Acme\PaypalExpressCheckoutBundle\Entity\PaymentDetails',
            $paymentName
        );

        $billingAgreementDetails = $billingAgreementStorage->findModelById($billingAgreementId);

        $billingAgreementStatus = new BinaryMaskStatusRequest($billingAgreementDetails);
        $payment->execute($billingAgreementStatus);

        $recurringPaymentStorage = $this->getPayum()->getStorageForClass(
            'Acme\PaypalExpressCheckoutBundle\Entity\RecurringPaymentDetails',
            $paymentName
        );

        $recurringPaymentDetails = $recurringPaymentStorage->findModelById($recurringPaymentId);

        $recurringPaymentStatus = new BinaryMaskStatusRequest($recurringPaymentDetails);
        $payment->execute($recurringPaymentStatus);

        return array(
            'billingAgreementStatus' => $billingAgreementStatus,
            'recurringPaymentStatus' => $recurringPaymentStatus,
        );
    }
    
    /**
     * @return AbstractRegistry
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }

    /**
     * @return TokenizedTokenService
     */
    protected function getTokenizedTokenService()
    {
        return $this->get('payum.tokenized_details_service');
    }
}