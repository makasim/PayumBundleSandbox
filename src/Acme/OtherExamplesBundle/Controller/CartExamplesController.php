<?php
namespace Acme\OtherExamplesBundle\Controller;

use Acme\OtherExamplesBundle\Model\Cart;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Payum\Registry\AbstractRegistry;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Payum\Paypal\ExpressCheckout\Nvp\Model\PaymentDetails;
use Payum\Bundle\PayumBundle\Service\TokenizedTokenService;

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
        if ('POST' === $request->getMethod()) {
            $form->bind($request);
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
                
                $captureToken = $this->getTokenizedTokenService()->createTokenForCaptureRoute(
                    $paymentName,
                    $cart,
                    'acme_payment_details_view' // TODO 
                );

                return $this->forward('PayumBundle:Capture:do', array(
                    'paymentName' => $paymentName,
                    'token' => $captureToken,
                ));
            }
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
     * @return AbstractRegistry
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }

    /**
     * @return TokenizedTokenService
     */
    protected function getTokenizedTokenService()
    {
        return $this->get('payum.tokenized_details_service');
    }
}