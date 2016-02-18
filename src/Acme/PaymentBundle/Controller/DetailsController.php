<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\Sync;
use Symfony\Component\HttpFoundation\Request;

class DetailsController extends PayumController
{
    public function viewAction(Request $request)
    {
        $token = $this->getPayum()->getHttpRequestVerifier()->verify($request);

        $gateway = $this->getPayum()->getGateway($token->getGatewayName());

        try {
            $gateway->execute(new Sync($token));
        } catch (RequestNotSupportedException $e) {}

        $gateway->execute($status = new GetHumanStatus($token));

        $refundToken = null;
        if ($status->isCaptured() || $status->isAuthorized()) {
            $refundToken = $this->getPayum()->getTokenFactory()->createRefundToken(
                $token->getGatewayName(),
                $status->getFirstModel(),
                $request->getUri()
            );
        }

        $captureToken = null;
        if ($status->isAuthorized()) {
            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $token->getGatewayName(),
                $status->getFirstModel(),
                $request->getUri()
            );
        }

        return $this->render('AcmePaymentBundle:Details:view.html.twig', array(
            'status' => $status->getValue(),
            'payment' => htmlspecialchars(json_encode(
                iterator_to_array($status->getFirstModel()),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )),
            'gatewayTitle' => ucwords(str_replace(array('_', '-'), ' ', $token->getGatewayName())),
            'refundToken' => $refundToken,
            'captureToken' => $captureToken,
        ));
    }

    public function viewPaymentAction(Request $request)
    {
        $token = $this->getPayum()->getHttpRequestVerifier()->verify($request);

        $gateway = $this->getPayum()->getGateway($token->getGatewayName());

        try {
            $gateway->execute(new Sync($token));
        } catch (RequestNotSupportedException $e) {}

        $gateway->execute($status = new GetHumanStatus($token));

        /** @var PaymentInterface $payment */
        $payment = $status->getFirstModel();

        return $this->render('AcmePaymentBundle:Details:viewPayment.html.twig', array(
            'status' => $status->getValue(),
            'payment' => htmlspecialchars(json_encode(
                array(
                    'client' => array(
                        'id' => $payment->getClientId(),
                        'email' => $payment->getClientEmail(),
                    ),
                    'number' => $payment->getNumber(),
                    'description' => $payment->getCurrencyCode(),
                    'total_amount' => $payment->getTotalAmount(),
                    'currency_code' => $payment->getCurrencyCode(),
                    'details' => $payment->getDetails(),
                ),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )),
            'gatewayTitle' => ucwords(str_replace(array('_', '-'), ' ', $token->getGatewayName()))
        ));
    }
}
