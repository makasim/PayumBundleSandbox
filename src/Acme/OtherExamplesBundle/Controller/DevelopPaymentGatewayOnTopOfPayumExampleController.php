<?php
namespace Acme\OtherExamplesBundle\Controller;

use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Core\Payum;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class DevelopPaymentGatewayOnTopOfPayumExampleController extends PayumController
{
    /**
     * @Extra\Route(
     *   "/develop-payment-gateway-on-top-of-payum",
     *   name="acme_other_develop_payment_gateway_on_top_of_payum"
     * )
     *
     * @Extra\Template
     */
    public function developPaymentGatewayAction(Request $request)
    {
        $gatewayName = 'foo_bar_gateway';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage(PaymentDetails::class);

            $payment = $storage->create();
            $payment['amount'] = (float) $data['amount'];
            $payment['currency'] = $data['currency'];
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'form' => $form->createView()
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
