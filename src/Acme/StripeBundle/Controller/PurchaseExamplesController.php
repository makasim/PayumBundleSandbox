<?php
namespace Acme\StripeBundle\Controller;

use Acme\PaymentBundle\Model\PaymentDetails;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Payum\Core\Registry\RegistryInterface;
use Payum\Stripe\Keys;
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
     * @Extra\Template("AcmeStripeBundle:PurchaseExamples:prepare.html.twig")
     */
    public function prepareJsAction(Request $request)
    {
        $paymentName = 'stripe_js';
        
        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            /** @var $details PaymentDetails */
            $details = $storage->createModel();
            $details["amount"] = $data['amount'] * 100;
            $details["currency"] = $data['currency'];
            $details["description"] = "a description";
            $storage->updateModel($details);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $details,
                'acme_payment_details_view'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'paymentName' => $paymentName
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
        $paymentName = 'stripe_checkout';

        $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

        /** @var $details PaymentDetails */
        $details = $storage->createModel();
        $details["amount"] = 100;
        $details["currency"] = 'USD';
        $details["description"] = "a description";

        if ($request->isMethod('POST') && $request->request->get('stripeToken')) {

            $details["card"] = $request->request->get('stripeToken');
            $storage->updateModel($details);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $details,
                'acme_payment_details_view'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        /** @var Keys $keys */
        $keys = $this->get('payum.context.'.$paymentName.'.keys');

        return array(
            'publishable_key' => $keys->getPublishableKey(),
            'model' => $details,
            'paymentName' => $paymentName
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_delayed_checkout",
     *   name="acme_stripe_prepare_checkout_delayed"
     * )
     *
     * @Extra\Template("AcmeStripeBundle:PurchaseExamples:prepare.html.twig")
     */
    public function prepareCheckoutDelayedAction(Request $request)
    {
        $paymentName = 'stripe_checkout';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            /** @var $details PaymentDetails */
            $details = $storage->createModel();
            $details["amount"] = $data['amount'] * 100;
            $details["currency"] = $data['currency'];
            $details["description"] = "a description";
            $storage->updateModel($details);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $details,
                'acme_payment_details_view'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView(),
            'paymentName' => $paymentName
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