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

class PayoutPaypalMasspayController extends Controller
{
    /**
     * @Extra\Route("/paypal/masspay/prepare", name="acme_paypal_masspay_prepare")
     */
    public function prepareAction(Request $request)
    {
        $gatewayName = 'paypal_masspay';

        $form = $this->createPurchasePlusCreditCardForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage(Payout::class);

            /** @var Payout $payout */
            $payout = $storage->create();
            $payout->setRecipientEmail($data['recipient_email']);
            $payout->setCurrencyCode($data['currency']);
            $payout->setTotalAmount($data['amount']);
            $storage->update($payout);

            $captureToken = $this->getPayum()->getTokenFactory()->createPayoutToken(
                $gatewayName,
                $payout,
                'acme_payment_done'
            );

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
