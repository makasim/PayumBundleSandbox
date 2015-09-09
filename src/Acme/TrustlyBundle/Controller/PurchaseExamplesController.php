<?php
namespace Acme\TrustlyBundle\Controller;

use Acme\PaymentBundle\Model\PaymentDetails;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Registry\RegistryInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PurchaseExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/prepare",
     *   name="acme_trustly_prepare"
     * )
     *
     * @Extra\Template("AcmeTrustlyBundle:PurchaseExamples:prepare.html.twig")
     */
    public function prepareAction(Request $request)
    {
        $gatewayName = 'trustly';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            /** @var PaymentDetails */
            $payment = $storage->create();
            $payment['EndUserID'] = 'foo@example.com';
            $payment['MessageID'] = uniqid('', true);
            $payment['Locale'] = 'en_US';
            $payment['Amount'] = $data['amount'];
            $payment['Currency'] = $data['currencyCode'];
            $payment['Country'] = 'SE';
            $storage->update($payment);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_details_view'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return [
            'gatewayName' => $gatewayName,
            'form' => $form->createView(),
        ];
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('amount', 'text', array('required' => false, 'data' => 123))
            ->add('currencyCode', 'text', array('required' => false, 'data' => 'USD'))

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
