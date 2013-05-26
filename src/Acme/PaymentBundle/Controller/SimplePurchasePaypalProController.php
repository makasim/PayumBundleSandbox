<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Payum\Registry\AbstractRegistry;
use Payum\Bundle\PayumBundle\Service\TokenManager;

class SimplePurchasePaypalProController extends Controller
{
    public function prepareAction(Request $request)
    {
        $paymentName = 'paypal_pro_checkout';

        $form = $this->createPurchaseForm();
        if ($request->isMethod('POST')) {
            
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();

                $storage = $this->getPayum()->getStorageForClass(
                    'Acme\PaymentBundle\Model\PaypalProPaymentDetails',
                    $paymentName
                );
                
                $paymentDetails = $storage->createModel();
                $paymentDetails
                    ->setAcct($data['acct'])
                    ->setCvv2($data['cvv2'])
                    ->setExpDate($data['exp_date'])
                    ->setAmt(number_format($data['amt'], 2))
                    ->setCurrency($data['currency'])
                ;

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
