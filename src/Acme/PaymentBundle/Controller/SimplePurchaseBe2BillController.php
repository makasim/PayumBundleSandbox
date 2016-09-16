<?php
namespace Acme\PaymentBundle\Controller;

use Acme\PaymentBundle\Entity\Payment;
use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Payum;
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

            $storage = $this->getPayum()->getStorage(PaymentDetails::class);

            /** @var PaymentDetails $payment */
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

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            return $this->forward('PayumBundle:Capture:do', array(
                'payum_token' => $captureToken,
            ));
        }

        return $this->render('AcmePaymentBundle::prepare.html.twig', array(
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

            $storage = $this->getPayum()->getStorage(PaymentDetails::class);

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

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return $this->render('AcmePaymentBundle::prepare.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function prepareOffsiteAction(Request $request)
    {
        $gatewayName = 'be2bill_offsite';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage(Payment::class);

            /** @var Payment $payment */
            $payment = $storage->create();
            //be2bill amount format is cents: for example:  100.05 (EUR). will be 10005.
            $payment->setNumber('orderId'.uniqid());
            $payment->setTotalAmount($data['amount'] * 100);
            $payment->setClientId('payerId');
            $payment->setDescription('Payment for digital stuff');
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return $this->render('AcmePaymentBundle::prepare.html.twig', array(
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
     * @return Payum
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}
