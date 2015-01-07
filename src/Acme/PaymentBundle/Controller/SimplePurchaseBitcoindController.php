<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SimplePurchaseBitcoindController extends Controller
{
    public function prepareAction(Request $request)
    {
        $paymentName = 'bitcoind';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            $details = $storage->createModel();
            $details['amount'] = $data['amt'];
            $storage->updateModel($details);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $details,
                'acme_payment_details_view'
            );

            return $this->forward('PayumBundle:Capture:do', array(
                'payum_token' => $captureToken,
            ));
        }

        return $this->render('AcmePaymentBundle:SimplePurchaseBitcoind:prepare.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('amt', null, array(
                'data' => '0.0001',
                'constraints' => array(new Range(array('max' => 1)))
            ))
            ->add('currency', null, array('data' => 'BTC', 'read_only' => true))

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
