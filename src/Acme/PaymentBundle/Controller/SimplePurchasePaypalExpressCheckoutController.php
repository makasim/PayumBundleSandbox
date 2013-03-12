<?php
namespace Acme\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Payum\Bundle\PayumBundle\Context\ContextRegistry;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Payum\Paypal\ExpressCheckout\Nvp\PaymentInstruction;

class SimplePurchasePaypalExpressCheckoutController extends Controller
{
    public function prepareAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('amount', null, array('data' => 1))
            ->add('currency', null, array('data' => 'USD'))
            
            ->getForm()
        ;

        if ('POST' === $request->getMethod()) {
            
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();
                
                $paymentContext = $this->getPayum()->getContext('simple_purchase_paypal_express_checkout');

                $instruction = $paymentContext->getStorage()->createModel();
                $instruction->setPaymentrequestCurrencycode(0, $data['currency']);
                $instruction->setPaymentrequestAmt(0,  $data['amount']);

                $paymentContext->getStorage()->updateModel($instruction);
                $instruction->setInvnum($instruction->getId());
        
                $captureUrl = $this->generateUrl('acme_payment_capture_simple', array(
                    'contextName' => 'simple_purchase_paypal_express_checkout',
                    'model' => $instruction->getId(),
                ), $absolute = true);
                $instruction->setReturnurl($captureUrl);
                $instruction->setCancelurl($captureUrl);
        
                $paymentContext->getStorage()->updateModel($instruction);

                return $this->redirect($captureUrl);
            }
        }
        
        return $this->render('AcmePaymentBundle:SimplePurchasePaypalExpressCheckout:prepare.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function prepareDigitalGoodsAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('amount', null, array('data' => 1))
            ->add('currency', null, array('data' => 'USD'))

            ->getForm()
        ;

        if ('POST' === $request->getMethod()) {

            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();

                $paymentContext = $this->getPayum()->getContext('simple_purchase_paypal_express_checkout');

                /** @var $instruction PaymentInstruction */
                $instruction = $paymentContext->getStorage()->createModel();
                $instruction->setPaymentrequestCurrencycode(0, $data['currency']);
                $instruction->setPaymentrequestAmt(0,  $data['amount']);
                $instruction->setNoshipping(Api::NOSHIPPING_NOT_DISPLAY_ADDRESS);
                $instruction->setReqconfirmshipping(Api::REQCONFIRMSHIPPING_NOT_REQUIRED);
                $instruction->setLPaymentrequestItemcategory(0, 0, Api::PAYMENTREQUEST_ITERMCATEGORY_DIGITAL);
                $instruction->setLPaymentrequestAmt(0, 0, $data['amount']);
                $instruction->setLPaymentrequestQty(0, 0, 1);
                $instruction->setLPaymentrequestName(0, 0, 'Awesome e-book');
                $instruction->setLPaymentrequestDesc(0, 0, 'Great stories of America.');

                $paymentContext->getStorage()->updateModel($instruction);
                $instruction->setInvnum($instruction->getId());

                $captureUrl = $this->generateUrl('acme_payment_capture_simple', array(
                    'contextName' => 'simple_purchase_paypal_express_checkout',
                    'model' => $instruction->getId(),
                ), $absolute = true);
                $instruction->setReturnurl($captureUrl);
                $instruction->setCancelurl($captureUrl);

                $paymentContext->getStorage()->updateModel($instruction);

                return $this->redirect($captureUrl);
            }
        }

        return $this->render('AcmePaymentBundle:SimplePurchasePaypalExpressCheckout:prepareDigitalGoods.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @return ContextRegistry
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}