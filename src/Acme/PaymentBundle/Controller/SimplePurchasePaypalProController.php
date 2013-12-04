<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Bundle\PayumBundle\Security\TokenFactory;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\SensitiveValue;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SimplePurchasePaypalProController extends Controller
{
    public function prepareAction(Request $request)
    {
        $paymentName = 'paypal_pro_checkout';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorageForClass(
                'Acme\PaymentBundle\Model\PaymentDetails',
                $paymentName
            );

            $paymentDetails = $storage->createModel();
            $paymentDetails['acct'] = new SensitiveValue($data['acct']);
            $paymentDetails['cvv2'] = new SensitiveValue($data['cvv2']);
            $paymentDetails['expdate'] = new SensitiveValue($data['exp_date']);
            $paymentDetails['amt'] = number_format($data['amt'], 2);
            $paymentDetails['currency'] = $data['currency'];
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
        
        return $this->render('AcmePaymentBundle:SimplePurchasePaypalPro:prepare.html.twig', array(
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
                'data' => 1,
                'constraints' => array(new Range(array('max' => 2)))
            ))
            ->add('acct', null, array('data' => '5105105105105100'))
            ->add('exp_date', null, array('data' => '1214'))
            ->add('cvv2', null, array('data' => '123'))
            ->add('currency', null, array('data' => 'USD'))

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
     * @return TokenFactory
     */
    protected function getTokenFactory()
    {
        return $this->get('payum.security.token_factory');
    }
}
