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

        return $this->render('AcmePaymentBundle:Details:view.html.twig', array(
            'status' => $status->getValue(),
            'details' => iterator_to_array($status->getModel()),
            'paymentTitle' => ucwords(str_replace(array('_', '-'), ' ', $token->getPaymentName()))
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
            'order' => array(
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
            'paymentTitle' => ucwords(str_replace(array('_', '-'), ' ', $token->getPaymentName()))
        ));
    }
}