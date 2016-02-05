<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Core\Payum;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Security\SensitiveValue;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class SimplePurchaseStripeViaOmnipayController extends Controller
{
    public function prepareAction(Request $request)
    {
        $gatewayName = 'stripe_via_omnipay';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

            $payment = $storage->create();
            $payment['amount'] = $data['amount'] * 100;
            $payment['currency'] = $data['currency'];
            $payment['card'] = new SensitiveValue($data['card']);
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
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
     * @return Payum
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}
