<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Bundle\PayumBundle\Security\TokenFactory;
use Payum\Registry\RegistryInterface;
use Payum\Security\SensitiveValue;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class SimplePurchaseStripeViaOmnipayController extends Controller
{
    public function prepareAction(Request $request)
    {
        $paymentName = 'stripe_via_ominpay';
        
        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorageForClass(
                'Acme\PaymentBundle\Model\PaymentDetails',
                $paymentName
            );

            $paymentDetails = $storage->createModel();
            $paymentDetails['amount'] = $data['amount'] * 100;
            $paymentDetails['currency'] = $data['currency'];
            $paymentDetails['card'] = new SensitiveValue($data['card']);
            $storage->updateModel($paymentDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
            );

            return $this->forward('PayumBundle:Capture:do', array(
                'payum_token' => $captureToken,
            ));
        }

        return $this->render('AcmePaymentBundle:SimplePurchaseStripeViaOmnipay:prepare.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        $creditCardBuilder = $this->get('form.factory')->createNamedBuilder('card')
            ->add('number', null, array('data' => '4242424242424242'))
            ->add('expiryMonth', null, array('data' => '6'))
            ->add('expiryYear', null, array('data' => '2016'))
            ->add('cvv', null, array('data' => '123'))
        ;

        $builder =  $this->createFormBuilder()
            ->add('amount', null, array(
                'data' => 1.23,
                'constraints' => array(new Range(array('max' => 2)))
            ))
            ->add('currency', null, array('data' => 'USD'))
            ->add($creditCardBuilder)
        ;

        return $builder->getForm();
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