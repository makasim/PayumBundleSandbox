<?php
namespace Acme\PaymentBundle\Controller;

use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Payum;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

class PaypalProHostedController extends Controller
{
    /**
     * @Extra\Route("/paypal/prohosted/prepare", name="acme_paypal_pro_hosted_prepare")
     *
     * @Extra\Template("AcmePaymentBundle::prepare.html.twig")
     */
    public function prepareAction(Request $request)
    {
        $gatewayName = 'paypal_pro_hosted';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage(PaymentDetails::class);

            /** @var $payment PaymentDetails */
            $payment                  = $storage->create();
            $payment['currency_code'] = $data['currency'];
            $payment['subtotal']      = $data['amount'];
            $payment['bn']            = 'FR_Test_H3S';

            $payment['address_override']    = 'true';
            $payment['showShippingAddress'] = 'false';

            $storage->update($payment);

            $notifyToken = $this->getPayum()->getTokenFactory()->createNotifyToken($gatewayName, $payment);

            $captureToken = $this->getPayum()->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_done'
            );

            $payment['notify_url']    = $notifyToken->getTargetUrl();
            $payment['return']        = $captureToken->getTargetUrl();
            $payment['cancel_return'] = $captureToken->getTargetUrl();

            $payment['cbt'] = 'Merchant ABCD';

            $payment['invoice'] = $payment->getId();
            $storage->update($payment);

            return $this->forward('PayumBundle:Capture:do', array(
                'payum_token' => $captureToken,
            ));
        }

        return array(
            'form'        => $form->createView(),
            'gatewayName' => $gatewayName,
        );
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('amount', null, array(
                'data'        => 1,
                'constraints' => array(new Range(array('max' => 2))),
            ))
            ->add('currency', null, array('data' => 'EUR'))
            ->getForm();
    }

    /**
     * @return Payum
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}
