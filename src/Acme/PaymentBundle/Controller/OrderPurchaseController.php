<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Core\Model\Order;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class OrderPurchaseController extends Controller
{
    public function prepareAction(Request $request)
    {
        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Order $order */
            $order = $form->getData();

            $order->setNumber(uniqid());
            $order->setClientId(uniqid());
            $order->setDescription(sprintf('An order %s for a client %s', $order->getNumber(), $order->getClientEmail()));

            $storage = $this->getPayum()->getStorage($order);
            $storage->updateModel($order);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $form->get('payment_name')->getData(),
                $order,
                'acme_payment_order_done'
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
        $formBuilder = $this->createFormBuilder(null, array('data_class' => 'Payum\Core\Model\Order'));

        return $formBuilder
            ->add('payment_name', 'choice', array(
                'choices' => array(
                    'paypal_express_checkout_with_ipn_enabled' => 'Paypal ExpressCheckout',
                    'paypal_pro_checkout' => 'Paypal ProCheckout',
                    'stripe_js' => 'Stripe.Js',
                    'stripe_checkout' => 'Stripe Checkout',
                    'authorize_net' => 'Authorize.Net AIM',
                    'be2bill' => 'Be2bill',
                    'be2bill_onsite' => 'Be2bill Onsite',
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
