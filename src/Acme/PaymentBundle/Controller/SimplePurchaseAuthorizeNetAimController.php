<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

use Payum\Registry\AbstractRegistry;
use Payum\Bundle\PayumBundle\Service\TokenManager;

class SimplePurchaseAuthorizeNetAimController extends Controller
{
    public function prepareAction(Request $request)
    {
        $paymentName = 'authorize_net';

        $form = $this->createPurchaseForm();
        if ('POST' === $request->getMethod()) {
            
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();
                
                $storage = $this->getPayum()->getStorageForClass(
                    'Acme\PaymentBundle\Model\AuthorizeNetPaymentDetails',
                    $paymentName
                );

                $paymentDetails = $storage->createModel();
                $paymentDetails->setAmount($data['amount']);
                $paymentDetails->setCardNum($data['card_number']);
                $paymentDetails->setExpDate($data['card_expiration_date']);

                $storage->updateModel($paymentDetails);

                $captureToken = $this->getTokenManager()->createTokenForCaptureRoute(
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
        
        return $this->render('AcmePaymentBundle:SimplePurchaseAuthorizeNetAim:prepare.html.twig', array(
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
            ->add('card_number', null, array('data' => '4007000000027'))
            ->add('card_expiration_date', null, array('data' => '10/16'))

            ->getForm()
        ;
    }

    /**
     * @return AbstractRegistry
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }

    /**
     * @return TokenManager
     */
    protected function getTokenManager()
    {
        return $this->get('payum.token_manager');
    }
}