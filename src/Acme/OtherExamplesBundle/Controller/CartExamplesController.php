<?php
namespace Acme\OtherExamplesBundle\Controller;

use Acme\OtherExamplesBundle\Model\Cart;
use Payum\Bundle\PayumBundle\Security\TokenFactory;
use Payum\Registry\RegistryInterface;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CartExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/select_payment", 
     *   name="acme_other_example_select_payment"
     * )
     * 
     * @Extra\Template
     */
    public function selectPaymentAction(Request $request)
    {
        $form = $this->createChoosePaymentForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();
            $paymentName = $data['payment_name'];

            $cartStorage = $this->getPayum()->getStorageForClass(
                'Acme\OtherExamplesBundle\Model\Cart',
                $paymentName
            );

            /** @var $cart Cart */
            $cart = $cartStorage->createModel();
            $cart->setPrice(1.23);
            $cart->setCurrency('USD');
            $cartStorage->updateModel($cart);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $cart,
                'acme_payment_details_view' // TODO
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