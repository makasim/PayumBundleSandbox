<?php
namespace Acme\PaymentBundle\Controller;

use Acme\PaymentBundle\Model\PaymentDetails;
use Payum\Bundle\PayumBundle\Security\TokenFactory;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\SensitiveValue;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class SimplePurchaseBe2BillController extends Controller
{
    public function prepareAction(Request $request)
    {
        $paymentName = 'be2bill';

        $form = $this->createPurchaseForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $storage = $this->getPayum()->getStorageForClass(
                'Acme\PaymentBundle\Model\PaymentDetails',
                $paymentName
            );

            /** @var PaymentDetails */
            $paymentDetails = $storage->createModel();
            //be2bill amount format is cents: for example:  100.05 (EUR). will be 10005.
            $paymentDetails['AMOUNT'] = $data['amount'] * 100;
            $paymentDetails['CLIENTEMAIL'] = 'user@email.com';
            $paymentDetails['CLIENTUSERAGENT'] = $request->headers->get('User-Agent', 'Unknown');
            $paymentDetails['CLIENTIP'] = $request->getClientIp();
            $paymentDetails['CLIENTIDENT'] = 'payerId';
            $paymentDetails['DESCRIPTION'] = 'Payment for digital stuff';
            $paymentDetails['ORDERID'] = 'orderId';
            $paymentDetails['CARDCODE'] = new SensitiveValue($data['card_number']);
            $paymentDetails['CARDCVV'] = new SensitiveValue($data['card_cvv']);
            $paymentDetails['CARDFULLNAME'] = new SensitiveValue($data['card_holder']);
            $paymentDetails['CARDVALIDITYDATE'] = new SensitiveValue($data['card_expiration_date']);
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

        return $this->render('AcmePaymentBundle:SimplePurchaseBe2Bill:prepare.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('amount', null, array(
                    'data' => 1.23,
                    'constraints' => array(new Range(array('max' => 2)))
                ))
            ->add('card_number', null, array('data' => '5555556778250000'))
            ->add('card_expiration_date', null, array('data' => '11-15'))
            ->add('card_holder', null, array('data' => 'John Doe'))
            ->add('card_cvv', null, array('data' => '123'))

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