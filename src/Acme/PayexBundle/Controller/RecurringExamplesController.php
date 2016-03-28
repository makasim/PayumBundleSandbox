<?php
namespace Acme\PayexBundle\Controller;

use Acme\PaymentBundle\Entity\AgreementDetails;
use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Core\Model\Identity;
use Payum\Core\Request\GetBinaryStatus;
use Payum\Core\Request\Sync;
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
        $gatewayName = 'payex_agreement';

        $subscription = $this->getWeatherForecastSubscriptionDetails();

        if ($request->isMethod('POST')) {
            $startDate = new \DateTime('now');
            $stopDate = new \DateTime(sprintf('now + %d days', $subscription['frequency']));

            $agreementDetailsStorage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\AgreementDetails');

            /** @var AgreementDetails $agreementDetails */
            $agreementDetails = $agreementDetailsStorage->create();
            $agreementDetails['maxAmount'] = 10000;
            $agreementDetails['purchaseOperation'] = AgreementApi::PURCHASEOPERATION_AUTHORIZATION;
            $agreementDetails['merchantRef'] = 'aRef';
            $agreementDetails['description'] = 'aDesc';
            $agreementDetails['startDate'] = $startDate->format('Y-m-d H:i:s');
            $agreementDetails['stopDate'] = $stopDate->format('Y-m-d H:i:s');

            $this->getPayum()->getGateway($gatewayName)->execute(new CreateAgreement($agreementDetails));
            $this->getPayum()->getGateway($gatewayName)->execute(new Sync($agreementDetails));

            $agreementDetailsStorage->update($agreementDetails);

            $paymentStorage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

            /** @var PaymentDetails $payment */
            $payment = $paymentStorage->create();
            $payment['price'] = $subscription['price'] * 100;
            $payment['priceArgList'] = '';
            $payment['vat'] = 0;
            $payment['currency'] = $subscription['currency'];
            $payment['orderId'] = 123;
            $payment['productNumber'] = 123;
            $payment['purchaseOperation'] = OrderApi::PURCHASEOPERATION_SALE;
            $payment['view'] = OrderApi::VIEW_CREDITCARD;
            $payment['description'] = 'a desc';
            $payment['clientIPAddress'] = $request->getClientIp();
            $payment['clientIdentifier'] = '';
            $payment['additionalValues'] = '';
            $payment['agreementRef'] = $agreementDetails['agreementRef'];
            $payment['clientLanguage'] = 'en-US';

            //recurring payment fields
            $payment['recurring'] = true;
            $payment['startDate'] = $startDate->format('Y-m-d H:i:s');
            $payment['stopDate'] = $stopDate->format('Y-m-d H:i:s');
            $payment['periodType'] = RecurringApi::PERIODTYPE_DAILY;
            $payment['period'] = 0;
            $payment['alertPeriod'] = 0;

            $paymentStorage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payex_recurring_payment_details',
                array(
                    'gatewayName' => $gatewayName,
                    'agreementId' => $agreementDetails->getId(),
                    'paymentId' => $payment->getId(),
                )
            );

            $payment['returnUrl'] = $captureToken->getTargetUrl();
            $payment['cancelUrl'] = $captureToken->getTargetUrl();
            $paymentStorage->update($payment);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'subscription' => $subscription,
        );
    }

    /**
     * @Extra\Route(
     *   "/recurring_payment_details/{gatewayName}/agreement/{agreementId}/payment/{paymentId}",
     *   name="acme_payex_recurring_payment_details"
     * )
     *
     * @Extra\Template
     */
    public function viewRecurringPaymentDetailsAction(Request $request, $gatewayName, $agreementId, $paymentId)
    {
        $payment = $this->getPayum()->getGateway($gatewayName);

        $payment->execute($syncAgreement = new Sync(new Identity(
            $agreementId,
            AgreementDetails::class
        )));
        $payment->execute($agreementStatus = new GetBinaryStatus($syncAgreement->getModel()));

        $paymentStatus = new GetBinaryStatus(new Identity(
            $paymentId,
            PaymentDetails::class
        ));
        $payment->execute($paymentStatus);

        $cancelToken = null;
        if ($paymentStatus->isCaptured()) {
            $cancelToken = $this->getPayum()->getTokenFactory()->createToken(
                $gatewayName,
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
        $token = $this->getPayum()->getHttpRequestVerifier()->verify($request);

        $payment = $this->getPayum()->getGateway($token->getGatewayName());

        $status = new GetBinaryStatus($token);
        $payment->execute($status);
        if (false == $status->isCaptured()) {
            throw new HttpException(400, 'The model status must be success.');
        }

        /** @var PaymentDetails $recurringPayment */
        $payment = $status->getModel();

        $payment->execute(new StopRecurringPayment($payment));
        $payment->execute(new Sync($payment));

        $this->getPayum()->getHttpRequestVerifier()->invalidate($token);

        return $this->redirect($token->getAfterUrl());
    }
}
