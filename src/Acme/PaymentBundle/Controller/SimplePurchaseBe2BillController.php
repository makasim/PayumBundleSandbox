<?php
namespace Acme\PaymentBundle\Controller;

use Acme\PaymentBundle\Model\Be2BillPaymentDetails;
use Payum\Bundle\PayumBundle\Service\TokenFactory;
use Payum\Registry\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

class SimplePurchaseBe2BillController extends Controller
{
    public function prepareAction(Request $request)
    {
        $paymentName = 'be2bill';

        $form = $this->createPurchaseForm();
        if ('POST' === $request->getMethod()) {
            
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();
                
                $storage = $this->getPayum()->getStorageForClass(
                    'Acme\PaymentBundle\Model\Be2BillPaymentDetails',
                    $paymentName
                );

                /** @var Be2BillPaymentDetails */
                $paymentDetails = $storage->createModel();
                $paymentDetails->setAmount($data['amount'] * 100); //be2bill amount format is cents: for example:  100.05 (EUR). will be 10005.
                $paymentDetails->setClientemail('user@email.com');
                $paymentDetails->setClientuseragent($request->headers->get('User-Agent', 'Unknown'));
                $paymentDetails->setClientip($request->getClientIp());
                $paymentDetails->setClientident('payerId');
                $paymentDetails->setDescription('Payment for digital stuff');
                $paymentDetails->setOrderid('orderId');
                $paymentDetails->setCardcode($data['card_number']);
                $paymentDetails->setCardcvv($data['card_cvv']);
                $paymentDetails->setCardfullname($data['card_holder']);
                $paymentDetails->setCardvaliditydate($data['card_expiration_date']);

                $storage->updateModel($paymentDetails);

                $captureToken = $this->getTokenFactory()->createTokenForCaptureRoute(
                    $paymentName,
                    $paymentDetails,
                    'acme_payment_details_view'
                );

                return $this->forward('PayumBundle:Capture:do', array(
                    'paymentName' => $paymentName,
                    'token' => $captureToken,
                ));
            }
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