<?php
namespace Acme\PaymentBundle\Controller;

use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Security\SensitiveValue;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SimplePurchasePaypalProController extends Controller
{
    public function prepareAction(Request $request)
    {
        $gatewayName = 'paypal_pro_checkout';

        $form = $this->createPurchasePlusCreditCardForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            $payment = $storage->create();
            $payment['ACCT'] = new SensitiveValue($data['acct']);
            $payment['CVV2'] = new SensitiveValue($data['cvv2']);
            $payment['EXPDATE'] = new SensitiveValue($data['exp_date']);
            $payment['AMT'] = number_format($data['amt'], 2);
            $payment['CURRENCY'] = $data['currency'];
            $storage->update($payment);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
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

    public function prepareObtainCreditCardAction(Request $request)
    {
        $gatewayName = 'paypal_pro_checkout';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

            $payment = $storage->create();
            $payment['amt'] = number_format($data['amt'], 2);
            $payment['currency'] = $data['currency'];
            $storage->update($payment);

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $gatewayName,
                $payment,
                'acme_payment_details_view'
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return $this->render('AcmePaymentBundle:SimplePurchasePaypalPro:prepare.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchasePlusCreditCardForm()
    {
        return $this->createFormBuilder()
            ->add('amt', null, array(
                'data' => 1,
                'constraints' => array(new Range(array('max' => 2)))
            ))
            ->add('acct', null, array('data' => '5105105105105100'))
            ->add('exp_date', null, array('data' => '1215'))
            ->add('cvv2', null, array('data' => '123'))
            ->add('currency', null, array('data' => 'USD'))

            ->getForm()
        ;
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
     * @return GenericTokenFactoryInterface
     */
    protected function getTokenFactory()
    {
        return $this->get('payum.security.token_factory');
    }
}
