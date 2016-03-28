<?php
namespace Acme\PaymentBundle\Controller;

use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Payum;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SimplePurchaseOfflineController extends Controller
{
    public function prepareAction(Request $request)
    {
        $gatewayName = 'offline';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

            /** @var PaymentDetails */
            $payment = $storage->create();
            $payment['transaction_number'] = $data['transaction_number'];
            $payment['transaction_date'] = $data['transaction_date'];
            $payment['description'] = $data['description'];
            $payment['paid'] = $data['paid'];

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

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('transaction_number', 'text', array('required' => false))
            ->add('transaction_date', 'date', array('required' => false))
            ->add('description', 'textarea', array('required' => false))
            ->add('paid', 'checkbox', array('required' => false))

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
