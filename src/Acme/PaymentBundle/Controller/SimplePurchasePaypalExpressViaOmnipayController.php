<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class SimplePurchasePaypalExpressViaOmnipayController extends Controller
{
    public function prepareAction(Request $request)
    {
        $paymentName = 'paypal_express_checkout_via_omnipay';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            $paymentDetails = $storage->create();
            $paymentDetails['amount'] = (float) $data['amount'];
            $paymentDetails['currency'] = $data['currency'];

            $storage->update($paymentDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
            );

            $paymentDetails['returnUrl'] = $captureToken->getTargetUrl();
            $paymentDetails['cancelUrl'] = $captureToken->getTargetUrl();

            $storage->update($paymentDetails);

            return $this->redirect($captureToken->getTargetUrl());
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
     * @return GenericTokenFactoryInterface
     */
    protected function getTokenFactory()
    {
        return $this->get('payum.security.token_factory');
    }
}
