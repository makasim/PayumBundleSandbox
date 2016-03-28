<?php
namespace Acme\PaymentBundle\Controller;

use Acme\PaymentBundle\Entity\Payment;
use Payum\Core\Payum;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

class SimplePurchaseSofortController extends Controller
{
    /**
     * @Extra\Route("/sofort/prepare", name="acme_sofort_prepare")
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function prepareAction(Request $request)
    {
        $gatewayName = 'sofort';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage(Payment::class);

            /** @var Payment $payment */
            $payment = $storage->create();
            $payment->setTotalAmount($data['amount']);
            $payment->setCurrencyCode('EUR');
            $payment->setDescription('Test payment');
            $payment->setDetails([
                'notification_url' => 'https://google.com',
            ]);
            $storage->update($payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return ['form' => $form->createView()];
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('amount', null, array(
                'data' => 123,
                'constraints' => array(new Range(array('max' => 200)))
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
