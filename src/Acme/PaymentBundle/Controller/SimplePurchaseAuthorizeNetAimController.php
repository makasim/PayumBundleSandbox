<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

use Payum\Registry\AbstractRegistry;

class SimplePurchaseAuthorizeNetAimController extends Controller
{
    public function prepareAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('amount', null, array(
                'data' => 1.23,
                'constraints' => array(new Range(array('max' => 2)))
            ))
            ->add('card_number', null, array('data' => '4007000000027'))
            ->add('card_expiration_date', null, array('data' => '10/16'))
            
            ->getForm()
        ;

        if ('POST' === $request->getMethod()) {
            
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();
                
                $storage = $this->getPayum()->getStorageForClass(
                    'Acme\PaymentBundle\Model\AuthorizeNetInstruction',
                    'simple_purchase_authorize_net'
                );

                $instruction = $storage->createModel();
                $instruction->setAmount($data['amount']);
                $instruction->setCardNum($data['card_number']);
                $instruction->setExpDate($data['card_expiration_date']);

                $storage->updateModel($instruction);
        
                return $this->redirect($this->generateUrl('acme_payment_capture_simple', array(
                    'contextName' => 'simple_purchase_authorize_net',
                    'model' => $instruction->getId()
                )));
            }
        }
        
        return $this->render('AcmePaymentBundle:SimplePurchaseAuthorizeNetAim:prepare.html.twig', array(
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