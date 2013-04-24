<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Registry\AbstractRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Payum\Request\BinaryMaskStatusRequest;
use Payum\Request\CaptureRequest;

class CaptureController extends Controller
{
    public function simpleCaptureAction($contextName, $model)
    {
        $payment = $this->getPayum()->getPayment($contextName);

        $captureRequest = new CaptureRequest($model);
        $payment->execute($captureRequest);

        $statusRequest = new BinaryMaskStatusRequest($captureRequest->getModel());
        $payment->execute($statusRequest);

        return $this->render('AcmePaymentBundle:Capture:simpleCapture.html.twig', array(
            'status' => $statusRequest
        ));
    }

    /**
     * @return AbstractRegistry
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}