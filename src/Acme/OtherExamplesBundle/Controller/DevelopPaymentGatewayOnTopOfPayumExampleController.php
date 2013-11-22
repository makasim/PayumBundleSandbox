<?php
namespace Acme\OtherExamplesBundle\Controller;

use Acme\OtherExamplesBundle\Model\Cart;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Bundle\PayumBundle\Security\TokenFactory;
use Payum\Registry\RegistryInterface;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
        $paymentName = 'foo_bar_gateway';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorageForClass(
                'Acme\PaymentBundle\Model\PaymentDetails',
                $paymentName
            );

            $paymentDetails = $storage->createModel();
            $paymentDetails['amount'] = (float) $data['amount'];
            $paymentDetails['currency'] = $data['currency'];
            $storage->updateModel($paymentDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
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