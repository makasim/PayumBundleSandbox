<?php
namespace Acme\PaymentBundle\Controller;

use Acme\PaymentBundle\Entity\Payment;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class PaymentPurchaseController extends Controller
{
    public function prepareAction(Request $request)
    {
        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Payment $payment */
            $payment = $form->getData();

            $payment->setNumber(date('ymdHis'));
            $payment->setClientId(uniqid());
            $payment->setDescription(sprintf('An order %s for a client %s', $payment->getNumber(), $payment->getClientEmail()));

            $storage = $this->getPayum()->getStorage($payment);
            $storage->update($payment);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $form->get('gateway_name')->getData(),
                $payment,
                'acme_payment_payment_done'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return $this->render('AcmePaymentBundle:OrderPurchase:prepare.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        $formBuilder = $this->createFormBuilder(null, array('data_class' => Payment::class));

        return $formBuilder
            ->add('gateway_name', 'choice', array(
                'choices' => array(
                    'paypal_express_checkout_with_ipn_enabled' => 'Paypal ExpressCheckout',
                    'paypal_pro_checkout' => 'Paypal ProCheckout',
                    'stripe_js' => 'Stripe.Js',
                    'stripe_checkout' => 'Stripe Checkout',
                    'authorize_net' => 'Authorize.Net AIM',
                    'be2bill' => 'Be2bill',
                    'be2bill_offsite' => 'Be2bill Offsite',
                    'payex' => 'Payex',
                    'redsys' => 'Redsys',
                    'offline' => 'Offline',
                    'stripe_via_omnipay' => 'Stripe (Omnipay)',
                    'paypal_express_checkout_via_omnipay' => 'Paypal ExpressCheckout (Omnipay)',
                ),
                'mapped' => false,
                'constraints' => array(new NotBlank())
            ))
            ->add('totalAmount', 'integer', array(
                'data' => 200,
                'constraints' => array(new Range(array('max' => 1000, 'min' => 100)), new NotBlank())
            ))
            ->add('currencyCode', 'text', array(
                'data' => 'USD',
                'constraints' => array(new NotBlank())
            ))
            ->add('clientEmail', 'text', array(
                'data' => 'foo@example.com',
                'constraints' => array(new Email(), new NotBlank())
            ))
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
