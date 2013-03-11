<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Payum\Bundle\PayumBundle\Context\ContextRegistry;
use Payum\Request\BinaryMaskStatusRequest;
use Payum\Request\CaptureRequest;

class CaptureController extends Controller
{
    public function simpleCaptureAction($contextName, $model)
    {
        $context = $this->getPayum()->getContext($contextName);

        $captureRequest = new CaptureRequest($model);
        $context->getPayment()->execute($captureRequest);

        $statusRequest = new BinaryMaskStatusRequest($captureRequest->getModel());
        $context->getPayment()->execute($statusRequest);

        return $this->render('AcmePaymentBundle:Capture:simpleCapture.html.twig', array(
            'status' => $statusRequest
        ));
    }

    /**
     * @return ContextRegistry
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}