<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Payum\Bundle\PayumBundle\Context\ContextRegistry;
use Payum\Request\BinaryMaskStatusRequest;
use Payum\Request\CaptureRequest;

class SimplePurchaseAuthorizeNetAimController extends Controller
{
    public function prepareAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('amount', null, array('data' => 1.23))
            ->add('card_number', null, array('data' => '4007000000027'))
            ->add('card_expiration_date', null, array('data' => '10/16'))
            
            ->getForm()
        ;

        if ('POST' === $request->getMethod()) {
            
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();
                
                $paymentContext = $this->getPayum()->getContext('simple_purchase_authorize_net');

                $instruction = $paymentContext->getStorage()->createModel();
                $instruction->setAmount($data['amount']);
                $instruction->setCardNum($data['card_number']);
                $instruction->setExpDate($data['card_expiration_date']);

                $paymentContext->getStorage()->updateModel($instruction);
        
                return $this->redirect($this->generateUrl('acme_payment_capture_simple', array(
                    'contextName' => 'simple_purchase_authorize_net',
                    'model' => $instruction->getId()
                )));
            }
        }
        
        return $this->render('AcmePaymentBundle:SimplePurchaseAuthorizeNetAim:prepare.html.twig', array(
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