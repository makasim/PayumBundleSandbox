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
     * @Extra\Route(
     *   "/prepare_recurring_payment",
     *   name="acme_paypal_express_checkout_prepare_recurring_payment"
     * )
     *
     * @Extra\Template
     */
    public function prepareAction(Request $request)
    {
        $form = $this->createBillingAgreementForm();
        
        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();
                
                $paymentContext = $this->getPayum()->getContext('simple_recurring_payment_paypal_express_checkout');

                /** @var $billingAgreementDetails PaymentDetails */
                $billingAgreementDetails = $paymentContext->getStorage()->createModel();
                $billingAgreementDetails->setPaymentrequestAmt(0,  $amount = 0);
                $billingAgreementDetails->setLBillingtype(0, Api::BILLINGTYPE_RECURRING_PAYMENTS);
                $billingAgreementDetails->setLBillingagreementdescription(0, $data['billing_agreement_description']);
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
        }
        
        return array(
            'form' => $form->createView()
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

        $statusRequest = new BinaryMaskStatusRequest($captureRequest->getModel());
        $context->getPayment()->execute($statusRequest);

        $billingAgreementDetails = $statusRequest->getModel();
        
        if ($statusRequest->isSuccess()) {
            $recurringPaymentDetails = new RecurringPaymentDetails();
            $recurringPaymentDetails->setToken($statusRequest->getModel()->getToken());
            $recurringPaymentDetails->setProfilestartdate(date(DATE_ATOM));
            $recurringPaymentDetails->setDesc($billingAgreementDetails->getLBillingagreementdescription(0));
            $recurringPaymentDetails->setAmt(1.45);
            $recurringPaymentDetails->setCurrencycode('USD');
            $recurringPaymentDetails->setBillingperiod(Api::BILLINGPERIOD_DAY);
            $recurringPaymentDetails->setBillingfrequency(2);
            $recurringPaymentDetails->setEmail($billingAgreementDetails->getEmail());
            
            $context->getPayment()->execute(new CreateRecurringPaymentProfileRequest($recurringPaymentDetails));
            $context->getPayment()->execute(new GetRecurringPaymentsProfileDetailsRequest($recurringPaymentDetails));

            var_dump($recurringPaymentDetails);
        }
        var_dump($statusRequest);
        var_dump(iterator_to_array($statusRequest->getModel()));
        
        die;
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createBillingAgreementForm()
    {
        return $this->createFormBuilder()
            ->add('billing_agreement_description', null, array(
                'data' => 'Subscribe for whether forecast',
                'label' => 'Subscription desc: ',
                'constraints' => new NotBlank
            ))
    
            ->getForm()
        ;
    }
    
    /**
     * @return ContextRegistry
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}