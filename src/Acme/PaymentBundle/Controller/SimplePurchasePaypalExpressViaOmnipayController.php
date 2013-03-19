<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

use Payum\Bundle\PayumBundle\Context\ContextRegistry;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Payum\Paypal\ExpressCheckout\Nvp\PaymentInstruction;

class SimplePurchasePaypalExpressViaOmnipayController extends Controller
{
    public function prepareAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('amount', null, array(
                'data' => 1,
                'constraints' => array(new Range(array('max' => 2)))
            ))
            ->add('currency', null, array('data' => 'USD'))
            
            ->getForm()
        ;

        if ('POST' === $request->getMethod()) {
            
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();
                
                $paymentContext = $this->getPayum()->getContext('simple_purchase_paypal_express_via_ominpay');

                $instruction = $paymentContext->getStorage()->createModel();
                $instruction['amount'] = $data['amount'] * 100;
                $instruction['currency'] = $data['currency'];

                $paymentContext->getStorage()->updateModel($instruction);
                
                $captureUrl = $this->generateUrl('acme_payment_capture_simple', array(
                    'contextName' => 'simple_purchase_paypal_express_via_ominpay',
                    'model' => $instruction->getId(),
                ), $absolute = true);
                $instruction['returnUrl'] = $captureUrl;
                $instruction['cancelUrl'] = $captureUrl;
        
                $paymentContext->getStorage()->updateModel($instruction);

                return $this->redirect($captureUrl);
            }
        }
        
        return $this->render('AcmePaymentBundle:SimplePurchasePaypalExpressViaOmnipay:prepare.html.twig', array(
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