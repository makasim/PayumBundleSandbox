<?php
namespace Acme\PaymentBundle\Controller;

use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SimplePurchaseOfflineController extends Controller
{
    public function prepareAction(Request $request)
    {
        // 4548 8120 4940 0004
        // 12/20
        // 123
        // 123456

        $paymentName = 'offline';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            /** @var PaymentDetails */
            $paymentDetails = $storage->createModel();
            $paymentDetails['transaction_number'] = $data['transaction_number'];
            $paymentDetails['transaction_date'] = $data['transaction_date'];
            $paymentDetails['description'] = $data['description'];
            $paymentDetails['paid'] = $data['paid'];

            $storage->updateModel($paymentDetails);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
            );

            return $this->forward('PayumBundle:Capture:do', array(
                'payum_token' => $captureToken,
            ));
        }

        return $this->render('AcmePaymentBundle:SimplePurchaseOffline:prepare.html.twig', array(
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