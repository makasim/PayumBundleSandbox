<?php
namespace Acme\StripeBundle\Controller;

use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Bridge\Symfony\Form\Type\CreditCardType;
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

            $storage = $this->getPayum()->getStorage(PaymentDetails::class);

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment["amount"] = $data['amount'] * 100;
            $payment["currency"] = $data['currency'];
            $payment["description"] = "a description";
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_details_view'
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

        $storage = $this->getPayum()->getStorage(PaymentDetails::class);

        /** @var $payment PaymentDetails */
        $payment = $storage->create();
        $payment["amount"] = 100;
        $payment["currency"] = 'USD';
        $payment["description"] = "a description";

        if ($request->isMethod('POST') && $request->request->get('stripeToken')) {

            $payment["card"] = $request->request->get('stripeToken');
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_details_view'
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

            $storage = $this->getPayum()->getStorage(PaymentDetails::class);

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment["amount"] = $data['amount'] * 100;
            $payment["currency"] = $data['currency'];
            $payment["description"] = "a description";
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_details_view'
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

            $storage = $this->getPayum()->getStorage(PaymentDetails::class);
            
            /** @var CreditCardInterface $card */
            $card = $data['creditCard'];

            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment["amount"] = $data['amount'] * 100;
            $payment["currency"] = $data['currency'];
            $payment["card"] = SensitiveValue::ensureSensitive([
                'number' => $card->getNumber(),
                'exp_month' => $card->getExpireAt()->format('m'),
                'exp_year' => $card->getExpireAt()->format('Y'),
                'cvc' => $card->getSecurityCode(),
            ]);
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

//        $this->getPayum()->getGateway($gatewayName)->execute($plan = new CreatePlan([
//            "amount" => 2000,
//            "interval" => "month",
//            "name" => "Amazing Gold Plan",
//            "currency" => "usd",
//            "id" => "gold"
//        ]));

        $storage = $this->getPayum()->getStorage(PaymentDetails::class);

        $form = $this->createForm('form', null, ['method' => 'POST']);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment["amount"] = 100;
            $payment["currency"] = 'USD';
            $payment["local"] = ['save_card' => true, 'customer' => ['plan' => 'gold']];
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_details_view'
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

        $storage = $this->getPayum()->getStorage(PaymentDetails::class);

        $form = $this->createForm('form', null, ['method' => 'POST']);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var $payment PaymentDetails */
            $payment = $storage->create();
            $payment["amount"] = 100;
            $payment["currency"] = 'USD';
            $payment["customer"] = "cus_82aVMCgqBUtuLF";
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_details_view'
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
