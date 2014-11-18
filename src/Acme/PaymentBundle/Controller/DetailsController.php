<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\OrderInterface;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\Sync;
use Symfony\Component\HttpFoundation\Request;

class DetailsController extends PayumController
{
    public function viewAction(Request $request)
    {
        $token = $this->getHttpRequestVerifier()->verify($request);

        $payment = $this->getPayum()->getPayment($token->getPaymentName());

        try {
            $payment->execute(new Sync($token));
        } catch (RequestNotSupportedException $e) {}

        $payment->execute($status = new GetHumanStatus($token));

        $refundToken = null;
        if ($status->isCaptured() || $status->isAuthorized()) {
            $refundToken = $this->getTokenFactory()->createRefundToken(
                $token->getPaymentName(),
                $status->getModel(),
                $request->getUri()
            );
        }

        return $this->render('AcmePaymentBundle:Details:view.html.twig', array(
            'status' => $status->getValue(),
            'details' => htmlspecialchars(json_encode(
                iterator_to_array($status->getModel()),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )),
            'paymentTitle' => ucwords(str_replace(array('_', '-'), ' ', $token->getPaymentName())),
            'refundToken' => $refundToken
        ));
    }

    public function viewOrderAction(Request $request)
    {
        $token = $this->getHttpRequestVerifier()->verify($request);

        $payment = $this->getPayum()->getPayment($token->getPaymentName());

        try {
            $payment->execute(new Sync($token));
        } catch (RequestNotSupportedException $e) {}

        $payment->execute($status = new GetHumanStatus($token));

        /** @var OrderInterface $order */
        $order = $this->getPayum()->getStorage($token->getDetails()->getClass())->findModelById(
            $token->getDetails()->getId()
        );

        return $this->render('AcmePaymentBundle:Details:viewOrder.html.twig', array(
            'status' => $status->getValue(),
            'order' => htmlspecialchars(json_encode(
                array(
                    'client' => array(
                        'id' => $order->getClientId(),
                        'email' => $order->getClientEmail(),
                    ),
                    'number' => $order->getNumber(),
                    'description' => $order->getCurrencyCode(),
                    'total_amount' => $order->getTotalAmount(),
                    'currency_code' => $order->getCurrencyCode(),
                    'currency_digits_after_decimal_point' => $order->getCurrencyDigitsAfterDecimalPoint(),
                    'details' => $order->getDetails(),
                ),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )),
            'paymentTitle' => ucwords(str_replace(array('_', '-'), ' ', $token->getPaymentName()))
        ));
    }
}
