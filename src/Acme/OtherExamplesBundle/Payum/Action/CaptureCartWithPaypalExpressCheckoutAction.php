<?php
namespace Acme\OtherExamplesBundle\Payum\Action;

use Payum\Action\PaymentAwareAction;
use Payum\Exception\RequestNotSupportedException;
use Payum\Registry\AbstractRegistry;
use Payum\Bundle\PayumBundle\Request\CaptureTokenizedDetailsRequest;

use Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails;
use Acme\OtherExamplesBundle\Model\Cart;

class CaptureCartWithPaypalExpressCheckoutAction extends PaymentAwareAction 
{
    /**
     * @var \Payum\Registry\AbstractRegistry
     */
    protected $payum;

    /**
     * @param AbstractRegistry $payum
     */
    public function __construct(AbstractRegistry $payum)
    {
        $this->payum = $payum;
    }
    
    /**
     * {@inheritdoc}
     */
    public function execute($request)
    {
        /** @var $request \Payum\Bundle\PayumBundle\Request\CaptureTokenizedDetailsRequest */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        /** @var Cart $cart */
        $cart = $request->getModel();

        $cartStorage = $this->payum->getStorageForClass(
            $cart,
            $request->getTokenizedDetails()->getPaymentName()
        );

        $paymentDetailsStorage = $this->payum->getStorageForClass(
            'Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails',
            $request->getTokenizedDetails()->getPaymentName()
        );

        /** @var $paymentDetails PaymentDetails */
        $paymentDetails = $paymentDetailsStorage->createModel();
        $paymentDetails->setPaymentrequestCurrencycode(0, $cart->getCurrency());
        $paymentDetails->setPaymentrequestAmt(0,  $cart->getPrice());
        $paymentDetails->setReturnurl($request->getTokenizedDetails()->getTargetUrl());
        $paymentDetails->setCancelurl($request->getTokenizedDetails()->getTargetUrl());
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
            $request instanceof CaptureTokenizedDetailsRequest &&
            $request->getModel() instanceof Cart &&
            null === $request->getModel()->getDetails()
        ;
    }
}