<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Core\Model\Client;
use Payum\Core\Model\Currency;
use Payum\Core\Model\Money;
use Payum\Core\Model\Order;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class OrderPurchaseController extends Controller
{
    public function prepareAction(Request $request)
    {
        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Payum\Core\Model\Order');

            $client = new Client;
            $client->setEmail($data['email']);

            /** @var Order $order */
            $order = $storage->createModel();
            $order->setClient($client);
            $order->setNumber(uniqid());
            $order->setTotalPrice(new Money($data['amount'] * 100, new Currency($data['currency'])));
            $storage->updateModel($order);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $data['payment_name'],
                $order,
                'acme_payment_order_done'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return $this->render('AcmePaymentBundle:OrderPurchase:prepare.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('payment_name', 'choice', array(
                'choices' => array(
                    'paypal_express_checkout_with_ipn_enabled' => 'Paypal ExpressCheckout',
                    'authorize_net' => 'Authorize.Net AIM',
                    'be2bill' => 'Be2bill',
                    'be2bill_onsite' => 'Be2bill Onsite',
                ),
                'constraints' => array(new NotBlank)
            ))
            ->add('amount', 'integer', array(
                'data' => 2,
                'constraints' => array(new Range(array('max' => 10)), new NotBlank)
            ))
            ->add('currency', 'text', array(
                'data' => 'USD',
                'constraints' => array(new NotBlank)
            ))
            ->add('email', 'text', array(
                'data' => 'foo@example.com',
                'constraints' => array(new Email, new NotBlank)
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
     * @return GenericTokenFactoryInterface
     */
    protected function getTokenFactory()
    {
        return $this->get('payum.security.token_factory');
    }
}