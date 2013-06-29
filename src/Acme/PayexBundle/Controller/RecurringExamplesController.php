<?php
namespace Acme\PayexBundle\Controller;

use Acme\PayexBundle\Model\AgreementDetails;
use Acme\PayexBundle\Model\PaymentDetails;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Payum\Request\BinaryMaskStatusRequest;
use Payum\Request\SyncRequest;
use Payum\Storage\Identificator;
use Payum\Registry\RegistryInterface;
use Payum\Payex\Api\AgreementApi;
use Payum\Payex\Api\RecurringApi;
use Payum\Payex\Request\Api\CreateAgreementRequest;
use Payum\Payex\Request\Api\StopRecurringPaymentRequest;
use Payum\Payex\Api\OrderApi;
use Payum\Bundle\PayumBundle\Service\TokenManager;

class RecurringExamplesController extends Controller
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
            
            $agreementDetailsStorage = $this->getPayum()->getStorageForClass(
                'Acme\PayexBundle\Model\AgreementDetails',
                $paymentName
            );
            
            /** @var AgreementDetails $agreementDetails */
            $agreementDetails = $agreementDetailsStorage->createModel();
            $agreementDetails->setMaxAmount(10000);
            $agreementDetails->setPurchaseOperation(AgreementApi::PURCHASEOPERATION_AUTHORIZATION);
            $agreementDetails->setMerchantRef('aRef');
            $agreementDetails->setDescription('aDesc');
            $agreementDetails->setStartDate($startDate->format('Y-m-d H:i:s'));
            $agreementDetails->setStopDate($stopDate->format('Y-m-d H:i:s'));

            $this->getPayum()->getPayment($paymentName)->execute(new CreateAgreementRequest($agreementDetails));
            $this->getPayum()->getPayment($paymentName)->execute(new SyncRequest($agreementDetails));
            
            $agreementDetailsStorage->updateModel($agreementDetails);
            
            $paymentDetailsStorage = $this->getPayum()->getStorageForClass(
                'Acme\PayexBundle\Model\PaymentDetails',
                $paymentName
            );

            /** @var PaymentDetails $paymentDetails */
            $paymentDetails = $paymentDetailsStorage->createModel();
            $paymentDetails->setPrice($subscription['price'] * 100);
            $paymentDetails->setPriceArgList('');
            $paymentDetails->setVat(0);
            $paymentDetails->setCurrency($subscription['currency']);
            $paymentDetails->setOrderId(123);
            $paymentDetails->setProductNumber(123);
            $paymentDetails->setPurchaseOperation(OrderApi::PURCHASEOPERATION_AUTHORIZATION);
            $paymentDetails->setView(OrderApi::VIEW_CREDITCARD);
            $paymentDetails->setDescription('a desc');
            $paymentDetails->setClientIPAddress($request->getClientIp());
            $paymentDetails->setClientIdentifier('');
            $paymentDetails->setAdditionalValues('');
            $paymentDetails->setAgreementRef($agreementDetails['agreementRef']);
            $paymentDetails->setClientLanguage('en-US');
            
            //recurring payment fields
            $paymentDetails->setRecurring(true);
            $paymentDetails->setStartDate($startDate->format('Y-m-d H:i:s'));
            $paymentDetails->setStopDate($stopDate->format('Y-m-d H:i:s'));
            $paymentDetails->setPeriodType(RecurringApi::PERIODTYPE_DAILY);
            $paymentDetails->setPeriod(0);
            $paymentDetails->setAlertPeriod(0);

            $paymentDetailsStorage->updateModel($paymentDetails);
            
            $captureToken = $this->getTokenManager()->createTokenForCaptureRoute(
                $paymentName,
                $paymentDetails,
                'acme_payex_recurring_payment_details',
                array(
                    'paymentName' => $paymentName,
                    'agreementId' => $agreementDetails->getId(),
                    'paymentId' => $paymentDetails->getId(),
                )
            );

            $paymentDetails->setReturnurl($captureToken->getTargetUrl());
            $paymentDetails->setCancelurl($captureToken->getTargetUrl());
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
        
        $payment->execute($syncAgreement = new SyncRequest(new Identificator(
            $agreementId,
            'Acme\PayexBundle\Model\AgreementDetails'
        )));
        $payment->execute($agreementStatus = new BinaryMaskStatusRequest($syncAgreement->getModel()));

        $paymentStatus = new BinaryMaskStatusRequest(new Identificator(
            $paymentId,
            'Acme\PayexBundle\Model\PaymentDetails'
        ));
        $payment->execute($paymentStatus);

        $cancelToken = null;
        if ($paymentStatus->isSuccess()) {
            $cancelToken = $this->getTokenManager()->createTokenForRoute(
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
     *   "/cancle_recurring_payment",
     *   name="acme_payex_cancel_recurring_payment"
     * )
     */
    public function cancelAction(Request $request)
    {
        $token = $this->getTokenManager()->getTokenFromRequest($request);

        $payment = $this->getPayum()->getPayment($token->getPaymentName());

        $status = new BinaryMaskStatusRequest($token);
        $payment->execute($status);
        if (false == $status->isSuccess()) {
            throw new HttpException(400, 'The model status must be success.');
        }

        /** @var PaymentDetails $recurringPayment */
        $paymentDetails = $status->getModel();

        $payment->execute(new StopRecurringPaymentRequest($paymentDetails));
        $payment->execute(new SyncRequest($paymentDetails));

        $this->getTokenManager()->deleteToken($token);

        return $this->redirect($token->getAfterUrl());
    }

    /**
     * @return RegistryInterface
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }

    /**
     * @return TokenManager
     */
    protected function getTokenManager()
    {
        return $this->get('payum.token_manager');
    }
}