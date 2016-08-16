<?php
namespace Acme\PaymentBundle\Controller;

use Acme\PaymentBundle\Entity\Payout;
use Payum\Core\Payum;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Range;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

class PaypalRestController extends Controller
{
    /**
     * @Extra\Route("/paypal/rest/prepare", name="acme_paypal_rest_prepare")
     */
    public function prepareAction(Request $request)
    {
        $gatewayName = 'paypal_rest';

        $form = $this->createPurchasePlusCreditCardForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->get('payum')->getStorage('MyApp\PaymentBundle\Entity\PaymentDetails');

            $payment = $storage->create();
            $storage->update($payment);

            $payer = new Payer();
            $payer->payment_method = "paypal";

            $amount = new Amount();
            $amount->currency = "USD";
            $amount->total = "1.00";

            $transaction = new Transaction();
            $transaction->amount = $amount;
            $transaction->description = "This is the payment description.";

            $captureToken = $this->get('payum')->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'payment_done'  // the route to redirect after capture
            );

            $redirectUrls = new RedirectUrls();
            $redirectUrls->return_url = $captureToken->getTargetUrl();
            $redirectUrls->cancel_url = $captureToken->getTargetUrl();

            $payment->intent = "sale";
            $payment->payer = $payer;
            $payment->redirect_urls = $redirectUrls;
            $payment->transactions = array($transaction);
            $storage->update($payment);

            return $this->redirect($captureToken->getTargetUrl());

            return $this->redirect($captureToken->getTargetUrl());
        }

        return $this->render('AcmePaymentBundle::prepare.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @return Form
     */
    protected function createPurchasePlusCreditCardForm()
    {
        return $this->createFormBuilder()
            ->add('amount', null, [
                'data' => 150,
                'constraints' => [new Range(['max' => 200])]
            ])
            ->add('currency', null, ['data' => 'USD'])
            ->add('recipient_email', null, [
                'data' => $this->container->getParameter('paypal.express_checkout.usd_testuser_login'),
                'constraints' => [new Email]
            ])

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
