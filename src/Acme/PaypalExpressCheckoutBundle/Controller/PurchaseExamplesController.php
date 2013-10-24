<?php
namespace Acme\PaypalExpressCheckoutBundle\Controller;

use Payum\Bundle\PayumBundle\Security\TokenFactory;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use Payum\Paypal\ExpressCheckout\Nvp\Model\PaymentDetails;
use Payum\Registry\RegistryInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

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
        $paymentName = 'paypal_express_checkout';
        
        $form = $this->createPurchaseForm();
        if ('POST' === $request->getMethod()) {
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();
                
                $storage = $this->getPayum()->getStorageForClass(
                    'Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails',
                    $paymentName
                );

                /** @var $paymentDetails PaymentDetails */
                $paymentDetails = $storage->createModel();
                $paymentDetails->setPaymentrequestCurrencycode(0, $data['currency']);
                $paymentDetails->setPaymentrequestAmt(0,  $data['amount']);
                $storage->updateModel($paymentDetails);
                
                $captureToken = $this->getTokenFactory()->createCaptureToken(
                    $paymentName,
                    $paymentDetails,
                    'acme_payment_details_view'
                );
                
                $paymentDetails->setReturnurl($captureToken->getTargetUrl());
                $paymentDetails->setCancelurl($captureToken->getTargetUrl());
                $paymentDetails->setInvnum($paymentDetails->getId());
                $storage->updateModel($paymentDetails);

                return $this->redirect($captureToken->getTargetUrl());
            }
        }
        
        return array(
            'form' => $form->createView(),
            'paymentName' => $paymentName
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
        $paymentName = 'paypal_express_checkout_plus_doctrine';
        
        $form = $this->createPurchaseForm();
        if ('POST' === $request->getMethod()) {
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();

                $storage = $this->getPayum()->getStorageForClass(
                    'Acme\PaypalExpressCheckoutBundle\Entity\PaymentDetails',
                    $paymentName
                );
                
                /** @var $paymentDetails PaymentDetails */
                $paymentDetails = $storage->createModel();
                $paymentDetails->setPaymentrequestCurrencycode(0, $data['currency']);
                $paymentDetails->setPaymentrequestAmt(0,  $data['amount']);

                $storage->updateModel($paymentDetails);

                $captureToken = $this->getTokenFactory()->createCaptureToken(
                    $paymentName,
                    $paymentDetails,
                    'acme_payment_details_view'
                );

                $paymentDetails->setReturnurl($captureToken->getTargetUrl());
                $paymentDetails->setCancelurl($captureToken->getTargetUrl());
                $paymentDetails->setInvnum($paymentDetails->getId());
                $storage->updateModel($paymentDetails);

                return $this->redirect($captureToken->getTargetUrl());
            }
        }

        return array(
            'form' => $form->createView(),
            'paymentName' => $paymentName
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
        $paymentName = 'paypal_express_checkout';
        
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
                $paymentName
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

            $captureToken = $this->getTokenFactory()->createCaptureToken(
                $paymentName,
                $paymentDetails,
                'acme_payment_details_view'
            );

            $paymentDetails->setReturnurl($captureToken->getTargetUrl());
            $paymentDetails->setCancelurl($captureToken->getTargetUrl());
            $paymentDetails->setInvnum($paymentDetails->getId());
            $storage->updateModel($paymentDetails);

            return $this->redirect($captureToken->getTargetUrl());
        }

        return array(
            'book' => $eBook,
            'paymentName' => $paymentName
        );
    }

    /**
     * @Extra\Route(
     *   "/prepare_purchase_with_ipn_enabled",
     *   name="acme_paypal_express_checkout_prepare_purchase_with_ipn_enabled"
     * )
     *
     * @Extra\Template
     */
    public function prepareWithIpnEnabledAction(Request $request)
    {
        $paymentName = 'paypal_express_checkout';

        $form = $this->createPurchaseForm();
        if ('POST' === $request->getMethod()) {
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();

                $storage = $this->getPayum()->getStorageForClass(
                    'Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails',
                    $paymentName
                );

                /** @var $paymentDetails PaymentDetails */
                $paymentDetails = $storage->createModel();
                $paymentDetails->setPaymentrequestCurrencycode(0, $data['currency']);
                $paymentDetails->setPaymentrequestAmt(0,  $data['amount']);
                $storage->updateModel($paymentDetails);

                $notifyToken = $this->getTokenFactory()->createNotifyToken($paymentName, $paymentDetails);

                $captureToken = $this->getTokenFactory()->createCaptureToken(
                    $paymentName,
                    $paymentDetails,
                    'acme_payment_details_view'
                );

                $paymentDetails->setReturnurl($captureToken->getTargetUrl());
                $paymentDetails->setCancelurl($captureToken->getTargetUrl());
                $paymentDetails->setPaymentrequestNotifyurl(0, $notifyToken->getTargetUrl());
                $paymentDetails->setInvnum($paymentDetails->getId());
                $storage->updateModel($paymentDetails);
                
                return $this->redirect($captureToken->getTargetUrl());
            }
        }

        return array(
            'form' => $form->createView(),
            'paymentName' => $paymentName
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