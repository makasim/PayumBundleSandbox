<?php
namespace Acme\PaypalExpressCheckoutBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Payum\Registry\AbstractRegistry;
use Payum\Paypal\ExpressCheckout\Nvp\Api;

use Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails;

class PurchaseExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/prepare_simple_purchase", 
     *   name="acme_paypal_express_checkout_prepare_simple_purchase"
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
                
                $storage = $this->getPayum()->getStorageForClass(
                    'Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails',
                    'simple_purchase_paypal_express_checkout'
                );

                /** @var $paymentDetails PaymentDetails */
                $paymentDetails = $storage->createModel();
                $paymentDetails->setPaymentrequestCurrencycode(0, $data['currency']);
                $paymentDetails->setPaymentrequestAmt(0,  $data['amount']);

                $storage->updateModel($paymentDetails);
                $paymentDetails->setInvnum($paymentDetails->getId());
        
                $captureUrl = $this->generateUrl('acme_payment_capture_simple', array(
                    'contextName' => 'simple_purchase_paypal_express_checkout',
                    'model' => $paymentDetails->getId(),
                ), $absolute = true);
                $paymentDetails->setReturnurl($captureUrl);
                $paymentDetails->setCancelurl($captureUrl);

                $storage->updateModel($paymentDetails);

                return $this->redirect($captureUrl);
            }
        }
        
        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Extra\Route(
     *   "/repare_simple_purchase_and_doctrine",
     *   name="acme_paypal_express_checkout_prepare_simple_purchase_and_doctrine"
     * )
     * 
     * @Extra\Template
     */
    public function prepareDoctrineAction(Request $request)
    {
        $form = $this->createPurchaseForm();
        if ('POST' === $request->getMethod()) {
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();

                $storage = $this->getPayum()->getStorageForClass(
                    'Acme\PaypalExpressCheckoutBundle\Entity\PaymentDetails',
                    'simple_purchase_paypal_express_checkout_doctrine'
                );
                
                /** @var $paymentDetails PaymentDetails */
                $paymentDetails = $storage->createModel();
                $paymentDetails->setPaymentrequestCurrencycode(0, $data['currency']);
                $paymentDetails->setPaymentrequestAmt(0,  $data['amount']);

                $storage->updateModel($paymentDetails);
                $paymentDetails->setInvnum($paymentDetails->getId());

                $captureUrl = $this->generateUrl('acme_payment_capture_simple', array(
                    'contextName' => 'simple_purchase_paypal_express_checkout_doctrine',
                    'model' => $paymentDetails->getId(),
                ), $absolute = true);
                $paymentDetails->setReturnurl($captureUrl);
                $paymentDetails->setCancelurl($captureUrl);

                $storage->updateModel($paymentDetails);

                //we do forward since we do not store returnulr to database.
                return $this->forward('AcmePaymentBundle:Capture:simpleCapture', array(
                    'contextName' => 'simple_purchase_paypal_express_checkout_doctrine',
                    'model' => $paymentDetails
                ));
            }
        }

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Extra\Route(
     *   "/digital_goods_purchase",
     *   name="acme_paypal_express_checkout_prepare_digital_goods_purchase"
     * )
     * 
     * @Extra\Template
     */
    public function prepareDigitalGoodsAction(Request $request)
    {
        $eBook = array(
            'author' => 'Jules Verne', 
            'name' => 'The Mysterious Island',
            'description' => 'The Mysterious Island is a novel by Jules Verne, published in 1874.',
            'price' => 2.64,
            'currency_symbol' => '$',
            'currency' => 'USD',
            'quantity' => 2
        );

        if ('POST' === $request->getMethod()) {
            $storage = $this->getPayum()->getStorageForClass(
                'Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails',
                'simple_purchase_paypal_express_checkout'
            );

            /** @var $paymentDetails PaymentDetails */
            $paymentDetails = $storage->createModel();
            $paymentDetails->setPaymentrequestCurrencycode(0, $eBook['currency']);
            $paymentDetails->setPaymentrequestAmt(0,  $eBook['price'] * $eBook['quantity']);
            
            $paymentDetails->setNoshipping(Api::NOSHIPPING_NOT_DISPLAY_ADDRESS);
            $paymentDetails->setReqconfirmshipping(Api::REQCONFIRMSHIPPING_NOT_REQUIRED);
            $paymentDetails->setLPaymentrequestItemcategory(0, 0, Api::PAYMENTREQUEST_ITERMCATEGORY_DIGITAL);
            $paymentDetails->setLPaymentrequestAmt(0, 0, $eBook['price']);
            $paymentDetails->setLPaymentrequestQty(0, 0, $eBook['quantity']);
            $paymentDetails->setLPaymentrequestName(0, 0, $eBook['author'].'. '.$eBook['name']);
            $paymentDetails->setLPaymentrequestDesc(0, 0, $eBook['description']);

            $storage->updateModel($paymentDetails);
            $paymentDetails->setInvnum($paymentDetails->getId());

            $captureUrl = $this->generateUrl('acme_payment_capture_simple', array(
                'contextName' => 'simple_purchase_paypal_express_checkout',
                'model' => $paymentDetails->getId(),
            ), $absolute = true);
            $paymentDetails->setReturnurl($captureUrl);
            $paymentDetails->setCancelurl($captureUrl);

            $storage->updateModel($paymentDetails);

            return $this->redirect($captureUrl);
        }

        return array(
            'book' => $eBook
        );
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('amount', null, array(
                'data' => 1,
                'constraints' => array(new Range(array('max' => 2)))
            ))
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
}