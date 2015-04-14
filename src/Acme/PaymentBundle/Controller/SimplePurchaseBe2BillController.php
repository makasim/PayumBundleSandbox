<?php
namespace Acme\PaymentBundle\Controller;

use Acme\PaymentBundle\Model\PaymentDetails;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Security\SensitiveValue;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class SimplePurchaseBe2BillController extends Controller
{
    public function prepareAction(Request $request)
    {
        $gatewayName = 'be2bill';

        $form = $this->createPurchaseWithCreditCardForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            /** @var PaymentDetails */
            $payment = $storage->create();
            //be2bill amount format is cents: for example:  100.05 (EUR). will be 10005.
            $payment['AMOUNT'] = $data['amount'] * 100;
            $payment['CLIENTEMAIL'] = 'user@email.com';
            $payment['CLIENTUSERAGENT'] = $request->headers->get('User-Agent', 'Unknown');
            $payment['CLIENTIP'] = $request->getClientIp();
            $payment['CLIENTIDENT'] = 'payerId'.uniqid();
            $payment['DESCRIPTION'] = 'Payment for digital stuff';
            $payment['ORDERID'] = 'orderId'.uniqid();
            $payment['CARDCODE'] = new SensitiveValue($data['card_number']);
            $payment['CARDCVV'] = new SensitiveValue($data['card_cvv']);
            $payment['CARDFULLNAME'] = new SensitiveValue($data['card_holder']);
            $payment['CARDVALIDITYDATE'] = new SensitiveValue($data['card_expiration_date']);
            $storage->update($payment);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_details_view'
            );

            return $this->forward('PayumBundle:Capture:do', array(
                'payum_token' => $captureToken,
            ));
        }

        return $this->render('AcmePaymentBundle:SimplePurchaseBe2Bill:prepare.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function prepareObtainCreditCardAction(Request $request)
    {
        $gatewayName = 'be2bill';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            /** @var PaymentDetails */
            $payment = $storage->create();
            //be2bill amount format is cents: for example:  100.05 (EUR). will be 10005.
            $payment['AMOUNT'] = $data['amount'] * 100;
            $payment['CLIENTEMAIL'] = 'user@email.com';
            $payment['CLIENTUSERAGENT'] = $request->headers->get('User-Agent', 'Unknown');
            $payment['CLIENTIP'] = $request->getClientIp();
            $payment['CLIENTIDENT'] = 'payerId'.uniqid();
            $payment['DESCRIPTION'] = 'Payment for digital stuff';
            $payment['ORDERID'] = 'orderId'.uniqid();
            $storage->update($payment);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_details_view'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return $this->render('AcmePaymentBundle:SimplePurchaseBe2Bill:prepare.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function prepareOnsiteAction(Request $request)
    {
        $gatewayName = 'be2bill_offsite';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            /** @var PaymentDetails */
            $payment = $storage->create();
            //be2bill amount format is cents: for example:  100.05 (EUR). will be 10005.
            $payment['AMOUNT'] = $data['amount'] * 100;
            $payment['CLIENTIDENT'] = 'payerId';
            $payment['DESCRIPTION'] = 'Payment for digital stuff';
            $payment['ORDERID'] = uniqid();
            $storage->update($payment);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_details_view'
            );

            /**
             * PAY ATTENTION
             *
             * You have also configure these urls in the account configuration section on be2bill site:
             *
             * return url: http://your-domain-here.dev/payment/capture/session-token
             * cancel url: http://your-domain-here.dev/payment/capture/session-token
             *
             * To get notifications add this url to be2bill as notify url (change be2bill_offsite to your payment name):
             *
             * http://your-domain-here.dev/payment/notify/unsafe/be2bill_offsite
             */
            $request->getSession()->set('payum_token', $captureToken->getHash());

            return $this->forward('PayumBundle:Capture:do', array(
                'payum_token' => $captureToken,
            ));
        }

        return $this->render('AcmePaymentBundle:SimplePurchaseBe2Bill:prepare.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('amount', null, array(
                'data' => 1.23,
                'constraints' => array(new Range(array('max' => 2)))
            ))

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
            ->add('card_number', null, array('data' => '5555556778250000'))
            ->add('card_expiration_date', null, array('data' => '11-15'))
            ->add('card_holder', null, array('data' => 'John Doe'))
            ->add('card_cvv', null, array('data' => '123'))

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
