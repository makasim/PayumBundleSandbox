<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

use Payum\Registry\AbstractRegistry;
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

                $storage = $this->getPayum()->getStorageForClass(
                    'Acme\PaymentBundle\Model\OmnipayInstruction',
                    'simple_purchase_paypal_express_via_ominpay'
                );
                
                $instruction = $storage->createModel();
                $instruction['amount'] = $data['amount'] * 100;
                $instruction['currency'] = $data['currency'];

                $storage->updateModel($instruction);
                
                $captureUrl = $this->generateUrl('acme_payment_capture_simple', array(
                    'contextName' => 'simple_purchase_paypal_express_via_ominpay',
                    'model' => $instruction->getId(),
                ), $absolute = true);
                $instruction['returnUrl'] = $captureUrl;
                $instruction['cancelUrl'] = $captureUrl;

                $storage->updateModel($instruction);

                return $this->redirect($captureUrl);
            }
        }
        
        return $this->render('AcmePaymentBundle:SimplePurchasePaypalExpressViaOmnipay:prepare.html.twig', array(
            'form' => $form->createView()
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