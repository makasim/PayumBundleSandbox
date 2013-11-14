<?php
namespace Acme\OtherExamplesBundle\Controller;

use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Bundle\PayumBundle\Security\TokenFactory;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Payum\Request\BinaryMaskStatusRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class JmsPaymentExamplesController extends PayumController
{
    /**
     * @Extra\Route(
     *   "/prepare_paypal_via_jms_plugin",
     *   name="acme_other_paypal_via_jms_plugin"
     * )
     *
     * @Extra\Template
     */
    public function prepareAction(Request $request)
    {
        $paymentName = 'paypal_express_checkout_via_jms_plugin';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $paymentInstruction = new PaymentInstruction(
                $data['amount'],
                $data['currency'],
                'paypal_express_checkout'
            );
            $paymentInstruction->setState(PaymentInstruction::STATE_VALID);

            $payment = new Payment($paymentInstruction, $data['amount']);

            $this->getDoctrine()->getManager()->persist($paymentInstruction);
            $this->getDoctrine()->getManager()->persist($payment);
            $this->getDoctrine()->getManager()->flush();

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $payment,
                'acme_other_purchase_done_paypal_via_jms_plugin'
            );

            $payment->getPaymentInstruction()->getExtendedData()->set(
                'return_url',
                $captureToken->getTargetUrl()
            );
            $payment->getPaymentInstruction()->getExtendedData()->set(
                'cancel_url',
                $captureToken->getTargetUrl()
            );

            //the state manipulations  is needed for saving changes in extended data.
            $oldState = $payment->getPaymentInstruction()->getState();
            $payment->getPaymentInstruction()->setState(PaymentInstruction::STATE_INVALID);

            $this->getDoctrine()->getManager()->persist($paymentInstruction);
            $this->getDoctrine()->getManager()->persist($payment);
            $this->getDoctrine()->getManager()->flush();

            $payment->getPaymentInstruction()->setState($oldState);

            $this->getDoctrine()->getManager()->persist($paymentInstruction);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Extra\Route(
     *   "/purchase_done_paypal_via_jms_plugin",
     *   name="acme_other_purchase_done_paypal_via_jms_plugin"
     * )
     *
     * @Extra\Template
     */
    public function viewAction(Request $request)
    {
        $token = $this->getHttpRequestVerifier()->verify($request);

        $status = new BinaryMaskStatusRequest($token);

        $this->getPayum()->getPayment($token->getPaymentName())->execute($status);

        return array(
            'status' => $status,
        );
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createChoosePaymentForm()
    {
        return $this->createFormBuilder()
            ->add('payment_name', 'choice', array(
                'choices' => array(
                    'paypal_express_checkout_plus_cart' => 'Paypal express checkout',
                    'authorize_net_plus_cart' => 'Authorize.Net',
                )
            ))
            ->getForm()
        ;
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
     * @return TokenFactory
     */
    protected function getTokenFactory()
    {
        return $this->get('payum.security.token_factory');
    }
}