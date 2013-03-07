<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Payum\Bundle\PayumBundle\Context\ContextInterface;
use Payum\Bundle\PayumBundle\Context\ContextRegistry;
use Payum\Request\StatusRequestInterface;

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
        
                $returnUrl = $this->generateUrl('payum_payment_capture', array(
                    'contextName' => $paymentContext->getName(),
                    'model' => $instruction->getId(),
                ), $absolute = true);
                $instruction->setReturnurl($returnUrl);
                $instruction->setCancelurl($returnUrl);
        
                $paymentContext->getStorage()->updateModel($instruction);
        
                return $this->forward('PayumBundle:Capture:do', array(
                    'contextName' => $paymentContext->getName(),
                    'model' => $instruction
                ));
            }
        }
        
        return $this->render('AcmePaymentBundle:SimplePurchasePaypalExpressCheckout:prepare.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function captureFinishedAction(StatusRequestInterface $statusRequest, ContextInterface $context)
    {
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