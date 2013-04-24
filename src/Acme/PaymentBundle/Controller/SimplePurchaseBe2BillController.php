<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

use Payum\Registry\AbstractRegistry;

use Acme\PaymentBundle\Model\Be2BillInstruction;

class SimplePurchaseBe2BillController extends Controller
{
    public function prepareAction(Request $request)
    {
        $form = $this->createFormBuilder()
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

        if ('POST' === $request->getMethod()) {
            
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();
                
                $storage = $this->getPayum()->getStorageForClass(
                    'Acme\PaymentBundle\Model\Be2BillInstruction',
                    'simple_purchase_be2bill'
                );

                /** @var Be2BillInstruction */
                $instruction = $storage->createModel();
                $instruction->setAmount($data['amount'] * 100); //be2bill amount format is cents: for example:  100.05 (EUR). will be 10005.
                $instruction->setClientemail('user@email.com');
                $instruction->setClientuseragent($request->headers->get('User-Agent', 'Unknown'));
                $instruction->setClientip($request->getClientIp());
                $instruction->setClientident('payerId');
                $instruction->setDescription('Payment for digital stuff');
                $instruction->setOrderid('orderId');
                $instruction->setCardcode($data['card_number']);
                $instruction->setCardcvv($data['card_cvv']);
                $instruction->setCardfullname($data['card_holder']);
                $instruction->setCardvaliditydate($data['card_expiration_date']);

                return $this->forward('AcmePaymentBundle:Capture:simpleCapture', array(
                    'contextName' => 'simple_purchase_be2bill',
                    'model' => $instruction
                ));
            }
        }
        
        return $this->render('AcmePaymentBundle:SimplePurchaseBe2Bill:prepare.html.twig', array(
            'form' => $form->createView()
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