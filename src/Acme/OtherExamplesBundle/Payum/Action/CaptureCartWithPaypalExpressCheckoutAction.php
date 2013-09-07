<?php
namespace Acme\OtherExamplesBundle\Payum\Action;

use Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails;
use Acme\OtherExamplesBundle\Model\Cart;
use Payum\Action\PaymentAwareAction;
use Payum\Exception\RequestNotSupportedException;
use Payum\Registry\RegistryInterface;
use Payum\Request\SecuredCaptureRequest;

class CaptureCartWithPaypalExpressCheckoutAction extends PaymentAwareAction 
{
    /**
     * @var \Payum\Registry\RegistryInterface
     */
    protected $payum;

    /**
     * @param RegistryInterface $payum
     */
    public function __construct(RegistryInterface $payum)
    {
        $this->payum = $payum;
    }
    
    /**
     * {@inheritdoc}
     */
    public function execute($request)
    {
        /** @var $request SecuredCaptureRequest */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        /** @var Cart $cart */
        $cart = $request->getModel();

        $cartStorage = $this->payum->getStorageForClass(
            $cart,
            $request->getToken()->getPaymentName()
        );

        $paymentDetailsStorage = $this->payum->getStorageForClass(
            'Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails',
            $request->getToken()->getPaymentName()
        );

        /** @var $paymentDetails PaymentDetails */
        $paymentDetails = $paymentDetailsStorage->createModel();
        $paymentDetails->setPaymentrequestCurrencycode(0, $cart->getCurrency());
        $paymentDetails->setPaymentrequestAmt(0,  $cart->getPrice());
        $paymentDetails->setReturnurl($request->getToken()->getTargetUrl());
        $paymentDetails->setCancelurl($request->getToken()->getTargetUrl());
        $paymentDetailsStorage->updateModel($paymentDetails);

        $cart->setDetails($paymentDetails);
        $cartStorage->updateModel($cart);
        
        $request->setModel($paymentDetails);
        $this->payment->execute($request);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof SecuredCaptureRequest &&
            $request->getModel() instanceof Cart &&
            null === $request->getModel()->getDetails()
        ;
    }
}