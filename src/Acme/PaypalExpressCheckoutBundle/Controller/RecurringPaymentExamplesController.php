<?php
namespace Acme\PaypalExpressCheckoutBundle\Controller;

use Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails;
use Payum\Paypal\ExpressCheckout\Nvp\Model\RecurringPaymentDetails;
use Payum\Paypal\ExpressCheckout\Nvp\Request\Api\CreateRecurringPaymentProfileRequest;
use Payum\Paypal\ExpressCheckout\Nvp\Request\Api\GetRecurringPaymentsProfileDetailsRequest;
use Payum\Request\BinaryMaskStatusRequest;
use Payum\Request\CaptureRequest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Payum\Bundle\PayumBundle\Context\ContextRegistry;
use Payum\Paypal\ExpressCheckout\Nvp\Api;

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
        $subscription = $this->getWeatherForecastSubscriptionDetails();
        
        if ($request->isMethod('POST')) {                
            $paymentContext = $this->getPayum()->getContext('simple_recurring_payment_paypal_express_checkout');

            /** @var $billingAgreementDetails PaymentDetails */
            $billingAgreementDetails = $paymentContext->getStorage()->createModel();
            $billingAgreementDetails->setPaymentrequestAmt(0,  $amount = 0);
            $billingAgreementDetails->setLBillingtype(0, Api::BILLINGTYPE_RECURRING_PAYMENTS);
            $billingAgreementDetails->setLBillingagreementdescription(0, $subscription['description']);
            $billingAgreementDetails->setNoshipping(1);
            
            $paymentContext->getStorage()->updateModel($billingAgreementDetails);
            $billingAgreementDetails->setInvnum($billingAgreementDetails->getId());
    
            $captureUrl = $this->generateUrl('acme_paypal_express_checkout_create_recurring_payment', array(
                'contextName' => 'simple_recurring_payment_paypal_express_checkout',
                'billingAgreementDetails' => $billingAgreementDetails->getId(),
            ), $absolute = true);
            $billingAgreementDetails->setReturnurl($captureUrl);
            $billingAgreementDetails->setCancelurl($captureUrl);
    
            $paymentContext->getStorage()->updateModel($billingAgreementDetails);

            return $this->redirect($captureUrl);
        }
        
        return array(
            'subscription' => $subscription
        );
    }

    /**
     * @Extra\Route(
     *   "/create_recurring_payment/{contextName}/{billingAgreementDetails}",
     *   name="acme_paypal_express_checkout_create_recurring_payment"
     * )
     *
     * @Extra\Template
     */
    public function createBillingAgreementAction($contextName, $billingAgreementDetails)
    {
        $context = $this->getPayum()->getContext($contextName);

        $captureRequest = new CaptureRequest($billingAgreementDetails);
        $context->getPayment()->execute($captureRequest);

        $billingAgreementStatus = new BinaryMaskStatusRequest($captureRequest->getModel());
        $context->getPayment()->execute($billingAgreementStatus);

        $recurringPaymentStatus = null;
        if ($billingAgreementStatus->isSuccess()) {
            $subscription = $this->getWeatherForecastSubscriptionDetails();
            $billingAgreementDetails = $billingAgreementStatus->getModel();
            
            $recurringPaymentDetails = new RecurringPaymentDetails();
            $recurringPaymentDetails->setToken($billingAgreementDetails->getToken());
            $recurringPaymentDetails->setDesc($billingAgreementDetails->getLBillingagreementdescription(0));
            $recurringPaymentDetails->setEmail($billingAgreementDetails->getEmail());
            $recurringPaymentDetails->setAmt($subscription['price']);
            $recurringPaymentDetails->setCurrencycode($subscription['currency']);
            $recurringPaymentDetails->setBillingfrequency($subscription['frequency']);
            $recurringPaymentDetails->setProfilestartdate(date(DATE_ATOM));
            $recurringPaymentDetails->setBillingperiod(Api::BILLINGPERIOD_DAY);
            
            $context->getPayment()->execute(new CreateRecurringPaymentProfileRequest($recurringPaymentDetails));
            $context->getPayment()->execute(new GetRecurringPaymentsProfileDetailsRequest($recurringPaymentDetails));

            $recurringPaymentStatus = new BinaryMaskStatusRequest($recurringPaymentDetails);
            $context->getPayment()->execute($recurringPaymentStatus);
        }
        
        return array(
            'billingAgreementStatus' => $billingAgreementStatus,
            'recurringPaymentStatus' => $recurringPaymentStatus,
        );
    }
    
    /**
     * @return ContextRegistry
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}