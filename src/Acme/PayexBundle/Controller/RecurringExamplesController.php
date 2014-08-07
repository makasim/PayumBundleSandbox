<?php
namespace Acme\PayexBundle\Controller;

use Acme\PaymentBundle\Model\AgreementDetails;
use Acme\PaymentBundle\Model\PaymentDetails;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Core\Request\GetBinaryStatus;
use Payum\Core\Request\Sync;
use Payum\Core\Model\Identificator;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Payex\Api\AgreementApi;
use Payum\Payex\Api\RecurringApi;
use Payum\Payex\Request\Api\CreateAgreement;
use Payum\Payex\Request\Api\StopRecurringPayment;
use Payum\Payex\Api\OrderApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecurringExamplesController extends PayumController
{
    /**
     * @return array
     */
    protected function getWeatherForecastSubscriptionDetails()
    {
        return array(
            'description' => 'Subscribe to the IT e-magazine for a week. It is 0.1$ per day.',
            'price' => 0.1,
            'currency' => 'USD',
            'frequency' => 7
        );
    }
    
    /**
     * @Extra\Route(
     *   "/prepare_recurring_payment",
     *   name="acme_payex_prepare_recurring_payment"
     * )
     *
     * @Extra\Template
     */
    public function prepareAction(Request $request)
    {
        $paymentName = 'payex_agreement';
        
        $subscription = $this->getWeatherForecastSubscriptionDetails();
        
        if ($request->isMethod('POST')) {
            $startDate = new \DateTime('now');
            $stopDate = new \DateTime(sprintf('now + %d days', $subscription['frequency']));

            $agreementDetailsStorage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\AgreementDetails');
            
            /** @var AgreementDetails $agreementDetails */
            $agreementDetails = $agreementDetailsStorage->createModel();
            $agreementDetails['maxAmount'] = 10000;
            $agreementDetails['purchaseOperation'] = AgreementApi::PURCHASEOPERATION_AUTHORIZATION;
            $agreementDetails['merchantRef'] = 'aRef';
            $agreementDetails['description'] = 'aDesc';
            $agreementDetails['startDate'] = $startDate->format('Y-m-d H:i:s');
            $agreementDetails['stopDate'] = $stopDate->format('Y-m-d H:i:s');

            $this->getPayum()->getPayment($paymentName)->execute(new CreateAgreement($agreementDetails));
            $this->getPayum()->getPayment($paymentName)->execute(new Sync($agreementDetails));
            
            $agreementDetailsStorage->updateModel($agreementDetails);
            
            $paymentDetailsStorage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            /** @var PaymentDetails $paymentDetails */
            $paymentDetails = $paymentDetailsStorage->createModel();
            $paymentDetails['price'] = $subscription['price'] * 100;
            $paymentDetails['priceArgList'] = '';
            $paymentDetails['vat'] = 0;
            $paymentDetails['currency'] = $subscription['currency'];
            $paymentDetails['orderId'] = 123;
            $paymentDetails['productNumber'] = 123;
            $paymentDetails['purchaseOperation'] = OrderApi::PURCHASEOPERATION_SALE;
            $paymentDetails['view'] = OrderApi::VIEW_CREDITCARD;
            $paymentDetails['description'] = 'a desc';
            $paymentDetails['clientIPAddress'] = $request->getClientIp();
            $paymentDetails['clientIdentifier'] = '';
            $paymentDetails['additionalValues'] = '';
            $paymentDetails['agreementRef'] = $agreementDetails['agreementRef'];
            $paymentDetails['clientLanguage'] = 'en-US';

            //recurring payment fields
            $paymentDetails['recurring'] = true;
            $paymentDetails['startDate'] = $startDate->format('Y-m-d H:i:s');
            $paymentDetails['stopDate'] = $stopDate->format('Y-m-d H:i:s');
            $paymentDetails['periodType'] = RecurringApi::PERIODTYPE_DAILY;
            $paymentDetails['period'] = 0;
            $paymentDetails['alertPeriod'] = 0;

            $paymentDetailsStorage->updateModel($paymentDetails);
            
            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payex_recurring_payment_details',
                array(
                    'paymentName' => $paymentName,
                    'agreementId' => $agreementDetails->getId(),
                    'paymentId' => $paymentDetails->getId(),
                )
            );

            $paymentDetails['returnUrl'] = $captureToken->getTargetUrl();
            $paymentDetails['cancelUrl'] = $captureToken->getTargetUrl();
            $paymentDetailsStorage->updateModel($paymentDetails);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'subscription' => $subscription,
        );
    }

    /**
     * @Extra\Route(
     *   "/recurring_payment_details/{paymentName}/agreement/{agreementId}/payment/{paymentId}",
     *   name="acme_payex_recurring_payment_details"
     * )
     *
     * @Extra\Template
     */
    public function viewRecurringPaymentDetailsAction(Request $request, $paymentName, $agreementId, $paymentId)
    {
        $payment = $this->getPayum()->getPayment($paymentName);
        
        $payment->execute($syncAgreement = new Sync(new Identificator(
            $agreementId,
            'Acme\PaymentBundle\Model\AgreementDetails'
        )));
        $payment->execute($agreementStatus = new GetBinaryStatus($syncAgreement->getModel()));

        $paymentStatus = new GetBinaryStatus(new Identificator(
            $paymentId,
            'Acme\PaymentBundle\Model\PaymentDetails'
        ));
        $payment->execute($paymentStatus);

        $cancelToken = null;
        if ($paymentStatus->isSuccess()) {
            $cancelToken = $this->getTokenFactory()->createToken(
                $paymentName,
                $paymentStatus->getModel(),
                'acme_payex_cancel_recurring_payment',
                array(),
                $request->attributes->get('_route'),
                $request->attributes->get('_route_params')
            );
        }

        return array(
            'cancelToken' => $cancelToken,
            'agreementStatus' => $agreementStatus,
            'paymentStatus' => $paymentStatus,
        );
    }

    /**
     * @Extra\Route(
     *   "/cancel_recurring_payment",
     *   name="acme_payex_cancel_recurring_payment"
     * )
     */
    public function cancelAction(Request $request)
    {
        $token = $this->getHttpRequestVerifier()->verify($request);

        $payment = $this->getPayum()->getPayment($token->getPaymentName());

        $status = new GetBinaryStatus($token);
        $payment->execute($status);
        if (false == $status->isSuccess()) {
            throw new HttpException(400, 'The model status must be success.');
        }

        /** @var PaymentDetails $recurringPayment */
        $paymentDetails = $status->getModel();

        $payment->execute(new StopRecurringPayment($paymentDetails));
        $payment->execute(new Sync($paymentDetails));

        $this->getHttpRequestVerifier()->invalidate($token);

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