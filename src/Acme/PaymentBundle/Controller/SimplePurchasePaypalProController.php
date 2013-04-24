<?php
namespace Acme\PaymentBundle\Controller;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Payum\PaymentInterface;
use Payum\Registry\AbstractRegistry;
use Payum\Paypal\ProCheckout\Nvp\Model\PaymentDetails;
use Payum\Request\BinaryMaskStatusRequest;
use Payum\Request\CaptureRequest;

class SimplePurchasePaypalProController extends Controller
{
    public function prepareAction(Request $request)
    {
        $form = $this->createFormBuilder()
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
        $response = array();

        if ($request->isMethod('POST')) {
            
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();

                /** @var PaymentInterface $payment */
                $payment = $this->getPayum()->getPayment('simple_purchase_paypal_pro');
                
                /** @var $instruction \Payum\Paypal\ProCheckout\Nvp\Model\PaymentDetails */
                $instruction = new PaymentDetails();
                $instruction
                    ->setAcct($data['acct'])
                    ->setCvv2($data['cvv2'])
                    ->setExpDate($data['exp_date'])
                    ->setAmt(number_format($data['amt'], 2))
                    ->setCurrency($data['currency'])
                ;


                $captureRequest = new CaptureRequest($instruction);
                $payment->execute($captureRequest);
                
                $statusRequest = new BinaryMaskStatusRequest($captureRequest->getModel());
                $payment->execute($statusRequest);

                $response = $instruction->getResponse();
            }
        }
        
        return $this->render('AcmePaymentBundle:SimplePurchasePaypalPro:prepare.html.twig', array(
            'form' => $form->createView(),
            'response' => $response,
        ));
    }

    /**
     * @return AbstractRegistry
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}
