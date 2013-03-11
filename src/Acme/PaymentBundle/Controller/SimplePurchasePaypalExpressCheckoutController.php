<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Payum\Bundle\PayumBundle\Context\ContextRegistry;
use Payum\Request\BinaryMaskStatusRequest;
use Payum\Request\CaptureRequest;

class SimplePurchasePaypalExpressCheckoutController extends Controller
{
    public function prepareAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('amount', null, array('data' => 1))
            ->add('currency', null, array('data' => 'USD'))
            
            ->getForm()
        ;

        if ('POST' === $request->getMethod()) {
            
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();
                
                $paymentContext = $this->getPayum()->getContext('simple_purchase_paypal_express_checkout');

                $instruction = $paymentContext->getStorage()->createModel();
                $instruction->setPaymentrequestCurrencycode(0, $data['currency']);
                $instruction->setPaymentrequestAmt(0,  $data['amount']);

                $paymentContext->getStorage()->updateModel($instruction);
                $instruction->setInvnum($instruction->getId());
        
                $returnUrl = $this->generateUrl('acme_payment_capture_simple_purchase_paypal_express_checkout', array(
                    'model' => $instruction->getId(),
                ), $absolute = true);
                $instruction->setReturnurl($returnUrl);
                $instruction->setCancelurl($returnUrl);
        
                $paymentContext->getStorage()->updateModel($instruction);
        
                return $this->forward('AcmePaymentBundle:SimplePurchasePaypalExpressCheckout:capture', array(
                    'model' => $instruction
                ));
            }
        }
        
        return $this->render('AcmePaymentBundle:SimplePurchasePaypalExpressCheckout:prepare.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function captureAction($model)
    {
        $context = $this->getPayum()->getContext('simple_purchase_paypal_express_checkout');

        $captureRequest = new CaptureRequest($model);
        $context->getPayment()->execute($captureRequest);

        $statusRequest = new BinaryMaskStatusRequest($captureRequest->getModel());
        $context->getPayment()->execute($statusRequest);

        return $this->render('AcmePaymentBundle:SimplePurchasePaypalExpressCheckout:captureFinished.html.twig', array(
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