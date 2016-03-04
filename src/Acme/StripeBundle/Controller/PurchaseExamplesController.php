<?php
namespace Acme\StripeBundle\Controller;

use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Payum;
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

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

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

        $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

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

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

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
     * @return Payum
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}
