<?php
namespace Acme\PaypalExpressCheckoutBundle\Controller;

use Acme\PaymentBundle\Model\AgreementDetails;
use Acme\PaymentBundle\Model\RecurringPaymentDetails;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Paypal\ExpressCheckout\Nvp\Request\Api\CreateRecurringPaymentProfileRequest;
use Payum\Paypal\ExpressCheckout\Nvp\Request\Api\ManageRecurringPaymentsProfileStatusRequest;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Payum\Core\Request\SyncRequest;
use Payum\Core\Request\BinaryMaskStatusRequest;
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
     *   "/prepare_recurring_payment_agreement",
     *   name="acme_paypal_express_checkout_prepare_recurring_payment_agreement"
     * )
     *
     * @Extra\Template
     */
    public function createAgreementAction(Request $request)
    {
        $paymentName = 'paypal_express_checkout_recurring_payment_and_doctrine_orm';
        
        $subscription = $this->getWeatherForecastSubscriptionDetails();
        
        if ($request->isMethod('POST')) {
            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\AgreementDetails');
            
            /** @var $agreement AgreementDetails */
            $agreement = $storage->createModel();
            $agreement['PAYMENTREQUEST_0_AMT'] = 0;
            $agreement['L_BILLINGTYPE0'] = Api::BILLINGTYPE_RECURRING_PAYMENTS;
            $agreement['L_BILLINGAGREEMENTDESCRIPTION0'] = $subscription['description'];
            $agreement['NOSHIPPING'] = 1;
            $storage->updateModel($agreement);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $agreement,
                'acme_paypal_express_checkout_create_recurring_payment'
            );

            $agreement['RETURNURL'] = $captureToken->getTargetUrl();
            $agreement['CANCELURL'] = $captureToken->getTargetUrl();
            $agreement['INVNUM'] = $agreement->getId();
            $storage->updateModel($agreement);

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
    public function createRecurringPaymentAction(Request $request)
    {
        $token = $this->getHttpRequestVerifier()->verify($request);

        $payment = $this->getPayum()->getPayment($token->getPaymentName());

        $agreementStatus = new BinaryMaskStatusRequest($token);
        $payment->execute($agreementStatus);

        $recurringPaymentStatus = null;
        if (false == $agreementStatus->isSuccess()) {
            throw new HttpException(400, 'Billing agreement status is not success.');
        }

        $subscription = $this->getWeatherForecastSubscriptionDetails();
        $agreement = $agreementStatus->getModel();

        $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\RecurringPaymentDetails');

        $paymentDetails = $storage->createModel();
        $paymentDetails['TOKEN'] = $agreement['TOKEN'];
        $paymentDetails['DESC'] = $agreement['L_BILLINGAGREEMENTDESCRIPTION0'];
        $paymentDetails['EMAIL'] = $agreement['EMAIL'];
        $paymentDetails['AMT'] = $subscription['price'];
        $paymentDetails['CURRENCYCODE'] = $subscription['currency'];
        $paymentDetails['BILLINGFREQUENCY'] = $subscription['frequency'];
        $paymentDetails['PROFILESTARTDATE'] = date(DATE_ATOM);
        $paymentDetails['BILLINGPERIOD'] = Api::BILLINGPERIOD_DAY;

        $payment->execute(new CreateRecurringPaymentProfileRequest($paymentDetails));
        $payment->execute(new SyncRequest($paymentDetails));

        $recurringPaymentStatus = new BinaryMaskStatusRequest($paymentDetails);
        $payment->execute($recurringPaymentStatus);

        return $this->redirect($this->generateUrl('acme_paypal_express_checkout_view_recurring_payment', array(
            'paymentName' => $token->getPaymentName(),
            'billingAgreementId' => $agreement->getId(),
            'recurringPaymentId' => $paymentDetails->getId(),
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

        $billingAgreementStorage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\AgreementDetails');

        $billingAgreementDetails = $billingAgreementStorage->findModelById($billingAgreementId);

        $billingAgreementStatus = new BinaryMaskStatusRequest($billingAgreementDetails);
        $payment->execute($billingAgreementStatus);

        $recurringPaymentStorage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\RecurringPaymentDetails');

        $recurringPaymentDetails = $recurringPaymentStorage->findModelById($recurringPaymentId);

        $recurringPaymentStatus = new BinaryMaskStatusRequest($recurringPaymentDetails);
        $payment->execute($recurringPaymentStatus);

        $cancelToken = null;
        if ($recurringPaymentStatus->isSuccess()) {
            $cancelToken = $this->getTokenFactory()->createToken(
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
        $this->getHttpRequestVerifier()->invalidate($token);
        
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
        $paymentDetails = $status->getModel();
        $paymentDetails['ACTION'] = Api::RECURRINGPAYMENTACTION_CANCEL;
        
        $payment->execute(new ManageRecurringPaymentsProfileStatusRequest($paymentDetails));
        $payment->execute(new SyncRequest($paymentDetails));

        return $this->redirect($token->getAfterUrl());
    }

    /**
     * @return GenericTokenFactoryInterface
     */
    protected function getTokenFactory()
    {
        return $this->get('payum.security.token_factory');
    }
}