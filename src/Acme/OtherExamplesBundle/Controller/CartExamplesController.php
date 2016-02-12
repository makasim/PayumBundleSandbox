<?php
namespace Acme\OtherExamplesBundle\Controller;

use Acme\OtherExamplesBundle\Model\Cart;
use Payum\Core\Payum;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
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
            $gatewayName = $data['payment_name'];

            $cartStorage = $this->getPayum()->getStorage('Acme\OtherExamplesBundle\Model\Cart');

            /** @var $cart Cart */
            $cart = $cartStorage->create();
            $cart->setPrice(1.23);
            $cart->setCurrency('USD');
            $cartStorage->update($cart);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
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
     * @return Payum
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}
