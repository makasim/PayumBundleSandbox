<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\BinaryMaskStatusRequest;
use Payum\Core\Request\SyncRequest;
use Symfony\Component\HttpFoundation\Request;

class DetailsController extends PayumController
{
    public function viewAction(Request $request)
    {
        $token = $this->getHttpRequestVerifier()->verify($request);
        
        $payment = $this->getPayum()->getPayment($token->getPaymentName());

        try {
            $payment->execute(new SyncRequest($token));
        } catch (RequestNotSupportedException $e) {}
        
        $status = new BinaryMaskStatusRequest($token);
        $payment->execute($status);

        return $this->render('AcmePaymentBundle:Details:view.html.twig', array(
            'status' => $status,
            'paymentTitle' => ucwords(str_replace(array('_', '-'), ' ', $token->getPaymentName()))
        ));
    }
}