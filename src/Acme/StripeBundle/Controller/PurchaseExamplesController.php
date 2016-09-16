<?php
namespace Acme\StripeBundle\Controller;

use Acme\PaymentBundle\Entity\Payment;
use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Bridge\Symfony\Form\Type\CreditCardType;
use Payum\Core\Model\CreditCard;
use Payum\Core\Model\CreditCardInterface;
use Payum\Core\Payum;
use Payum\Core\Security\SensitiveValue;
use Payum\Stripe\Request\Api\CreatePlan;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class PurchaseExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/prepare_js",
     *   name="acme_stripe_prepare_js"
     * )
     *
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function prepareJsAction(Request $request)
    {
        $gatewayName = 'stripe_js';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage(Payment::class);

            /** @var $payment Payment */
            $payment = $storage->create();
            $payment->setTotalAmount($data['amount'] * 100);
            $payment->setCurrencyCode($data['currency']);
            $payment->setDescription('A stripe.js example payment.');
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'gatewayName' => $gatewayName
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_checkout",
     *   name="acme_stripe_prepare_checkout"
     * )
     *
     * @Extra\Template("AcmeStripeBundle:PurchaseExamples:prepareCheckout.html.twig")
     */
    public function prepareCheckoutAction(Request $request)
    {
        $gatewayName = 'stripe_checkout';

        /*
        $storage = $this->getPayum()->getStorage(PaymentDetails::class);

        /** @var $payment PaymentDetails *
        $payment = $storage->create();
        $payment["amount"] = 100;
        $payment["currency"] = 'USD';
        $payment["description"] = "A Stripe Checkout example payment.";

        if ($request->isMethod('POST') && $request->request->get('stripeToken')) {

            $payment["card"] = $request->request->get('stripeToken');
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        */

        $storage = $this->getPayum()->getStorage(Payment::class);

        /** @var $payment Payment */
        $payment = $storage->create();
        $payment->setTotalAmount(100);
        $payment->setCurrencyCode('USD');
        $payment->setDescription('A Stripe Checkout example payment.');

        if ($request->isMethod('POST') && $request->request->get('stripeToken')) {
            $card = new CreditCard();
            $card->setToken($request->request->get('stripeToken'));

            $payment->setCreditCard($card);
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'publishable_key' => $this->container->getParameter('stripe.publishable_key'),
            'model' => $payment,
            'gatewayName' => $gatewayName
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_delayed_checkout",
     *   name="acme_stripe_prepare_checkout_delayed"
     * )
     *
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function prepareCheckoutDelayedAction(Request $request)
    {
        $gatewayName = 'stripe_checkout';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage(Payment::class);

            /** @var $payment Payment */
            $payment = $storage->create();
            $payment->setTotalAmount($data['amount'] * 100);
            $payment->setCurrencyCode($data['currency']);
            $payment->setDescription('A Stripe Checkout example delayed payment.');
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'gatewayName' => $gatewayName
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_direct",
     *   name="acme_stripe_prepare_direct"
     * )
     *
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function prepareDirectAction(Request $request)
    {
        $gatewayName = 'stripe_js';

        $form = $this->createPurchaseWithCreditCardForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage(Payment::class);
            
            /** @var CreditCardInterface $card */
            $card = $data['creditCard'];

            /** @var $payment Payment */
            $payment = $storage->create();
            $payment->setTotalAmount($data['amount'] * 100);
            $payment->setCurrencyCode($data['currency']);
            $payment->setCreditCard($card);
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            return $this->forward('PayumBundle:Capture:do', array(
                'payum_token' => $captureToken,
            ));
        }

        return array(
            'form' => $form->createView(),
            'gatewayName' => $gatewayName
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_subscription",
     *   name="acme_stripe_prepare_subscription"
     * )
     *
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function prepareSubscriptionAction(Request $request)
    {
        $gatewayName = 'stripe_checkout';

        /**
         * Uncomment this the first time you try to create a subscription.
         * It creates the "gold" plan to which the customer will be subscribed.

        $this->getPayum()->getGateway($gatewayName)->execute($plan = new CreatePlan([
            "amount" => 2000,
            "interval" => "month",
            "name" => "Amazing Gold Plan",
            "currency" => "usd",
            "id" => "gold"
        ]));

         */

        $storage = $this->getPayum()->getStorage(Payment::class);

        $form = $this->createForm('form', null, ['method' => 'POST']);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var $payment Payment */
            $payment = $storage->create();
            $payment->setTotalAmount(100);
            $payment->setCurrencyCode('USD');
            $payment->setDescription('Stripe: a subscrition.');
            $payment->setDetails([
                'local' => [
                    'save_card' => true,
                    'customer' => ['plan' => 'gold']
                ]
            ]);
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return ['form' => $form->createView()];
    }

    /**
     * @Extra\Route(
     *   "/prepare_charge_stored_card",
     *   name="acme_stripe_prepare_charge_stored_card"
     * )
     *
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function prepareChargeStoredCardAction(Request $request)
    {
        $gatewayName = 'stripe_checkout';

        $storage = $this->getPayum()->getStorage(Payment::class);

        $form = $this->createForm('form', null, ['method' => 'POST']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var $payment Payment */
            $payment = $storage->create();
            $payment->setTotalAmount(100);
            $payment->setCurrencyCode('USD');
            $payment->setClientId('cus_9ClvuGk84Vfepc');
            $payment->setDescription('Stripe: Charge a stored card.');
            $payment->setDetails([
                'customer' => 'cus_9ClvuGk84Vfepc'
            ]);
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
            $gatewayName,
            $payment,
            'acme_payment_done'
            );

            return $this->redirect($captureToken->getTargetUrl());
            }

        return ['form' => $form->createView()];
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
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseWithCreditCardForm()
    {
        return $this->createFormBuilder()
            ->add('amount', null, array(
                'data' => 1.23,
                'constraints' => array(new Range(array('max' => 2)))
            ))
            ->add('currency', null, array('data' => 'USD'))
            ->add('creditCard', CreditCardType::class)

            ->getForm()
        ;
    }

    /**
     * @return Payum
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}
