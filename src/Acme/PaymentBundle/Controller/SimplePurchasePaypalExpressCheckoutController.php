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
        
                $captureUrl = $this->generateUrl('acme_payment_capture_simple', array(
                    'contextName' => 'simple_purchase_paypal_express_checkout',
                    'model' => $instruction->getId(),
                ), $absolute = true);
                $instruction->setReturnurl($captureUrl);
                $instruction->setCancelurl($captureUrl);
        
                $paymentContext->getStorage()->updateModel($instruction);

                return $this->redirect($captureUrl);
            }
        }
        
        return $this->render('AcmePaymentBundle:SimplePurchasePaypalExpressCheckout:prepare.html.twig', array(
            'form' => $form->createView()
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