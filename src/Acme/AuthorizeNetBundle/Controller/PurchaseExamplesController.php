<?php
namespace Acme\AuthorizeNetBundle\Controller;

use Acme\PaymentBundle\Controller\CaptureController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Payum\Bundle\PayumBundle\Context\ContextRegistry;

class PurchaseExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/prepare_simple_purchase",
     *   name="acme_authorize_net_prepare_simple_purchase"
     * )
     *
     * @Extra\Template
     */
    public function prepareAction(Request $request)
    {
        
        $form = $this->createPurchaseForm();
        if ('POST' === $request->getMethod()) {
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();
                
                $paymentContext = $this->getPayum()->getContext('simple_purchase_authorize_net');

                $instruction = $paymentContext->getStorage()->createModel();
                $instruction->setAmount($data['amount']);
                $instruction->setCardNum($data['card_number']);
                $instruction->setExpDate($data['card_expiration_date']);

                $paymentContext->getStorage()->updateModel($instruction);

                $captureFinishedRedirectUrl = $this->generateUrl('acme_payment_payment_details_view', array(
                    'model' => $instruction->getId(),
                    'contextName' => $paymentContext->getName()
                ));
                
                $token = $this->getCaptureController()->createMetaInfo(
                    $instruction,
                    $paymentContext->getName(),
                    $captureFinishedRedirectUrl
                );
                
                return $this->forward('AcmePaymentBundle:Capture:do', array(
                    'token' => $token,
                ));
            }
        }
        
        return array(
            'form' => $form->createView()
        );
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
     * @return CaptureController
     */
    protected function getCaptureController()
    {
        return $this->get('acme_payment.controller.capture');
    }

    /**
     * @return ContextRegistry
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}