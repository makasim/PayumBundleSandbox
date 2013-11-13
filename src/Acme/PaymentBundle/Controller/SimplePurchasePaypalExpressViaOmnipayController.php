<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Bundle\PayumBundle\Security\TokenFactory;
use Payum\Registry\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class SimplePurchasePaypalExpressViaOmnipayController extends Controller
{
    public function prepareAction(Request $request)
    {
        $paymentName = 'paypal_express_checkout_via_ominpay';
        
        $form = $this->createPurchaseForm();
        if ('POST' === $request->getMethod()) {
            
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();

                $storage = $this->getPayum()->getStorageForClass(
                    'Acme\PaymentBundle\Model\PaymentDetails',
                    $paymentName
                );
                
                $paymentDetails = $storage->createModel();
                $paymentDetails['amount'] = (float) $data['amount'];
                $paymentDetails['currency'] = $data['currency'];

                $storage->updateModel($paymentDetails);

                $captureToken = $this->getTokenFactory()->createCaptureToken(
                    $paymentName,
                    $paymentDetails,
                    'acme_payment_details_view'
                );

                $paymentDetails['returnUrl'] = $captureToken->getTargetUrl();
                $paymentDetails['cancelUrl'] = $captureToken->getTargetUrl();
                
                $storage->updateModel($paymentDetails);

                return $this->redirect($captureToken->getTargetUrl());
            }
        }

        return $this->render('AcmePaymentBundle:SimplePurchasePaypalExpressViaOmnipay:prepare.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('amount', null, array(
                    'data' => 1,
                    'constraints' => array(new Range(array('max' => 2)))
                ))
            ->add('currency', null, array('data' => 'USD'))

            ->getForm()
        ;
    }

    /**
     * @return RegistryInterface
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }

    /**
     * @return TokenFactory
     */
    protected function getTokenFactory()
    {
        return $this->get('payum.security.token_factory');
    }
}