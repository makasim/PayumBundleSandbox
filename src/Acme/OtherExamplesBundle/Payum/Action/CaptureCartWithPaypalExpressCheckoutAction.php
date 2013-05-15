<?php
namespace Acme\OtherExamplesBundle\Payum\Action;

use Acme\OtherExamplesBundle\Model\Cart;
use Payum\Action\ActionInterface;
use Payum\Action\PaymentAwareAction;
use Payum\Bundle\PayumBundle\Service\TokenizedTokenService;
use Payum\Exception\RequestNotSupportedException;
use Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails;
use Payum\Model\TokenizedDetails;
use Payum\Registry\AbstractRegistry;
use Payum\Request\CaptureRequest;

class CaptureCartWithPaypalExpressCheckoutAction extends PaymentAwareAction 
{
    protected $payum;
    
    protected $tokenService;
    
    public function __construct(AbstractRegistry $payum, TokenizedTokenService $tokenService)
    {
        $this->payum = $payum;
        $this->tokenService = $tokenService;
    }
    
    /**
     * {@inheritdoc}
     */
    public function execute($request)
    {
        /** @var $request CaptureRequest */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }
        
        /** @var TokenizedDetails $tokenizedDetails */
        $tokenizedDetails = $request->getModel();

        $cartStorage = $this->payum->getStorageForClass(
            $tokenizedDetails->getDetails()->getClass(),
            $tokenizedDetails->getPaymentName()
        );
        /** @var Cart $cart */
        $cart = $cartStorage->findModelById($tokenizedDetails->getDetails()->getId());

        if (false == $cart->getDetails()) {
            $detailsStorage = $this->payum->getStorageForClass(
                'Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails',
                $tokenizedDetails->getPaymentName()
            );

            /** @var $paymentDetails PaymentDetails */
            $paymentDetails = $detailsStorage->createModel();
            $paymentDetails->setPaymentrequestCurrencycode(0, $cart->getCurrency());
            $paymentDetails->setPaymentrequestAmt(0,  $cart->getPrice());
            $paymentDetails->setReturnurl($tokenizedDetails->getTargetUrl());
            $paymentDetails->setCancelurl($tokenizedDetails->getTargetUrl());
            
            $detailsStorage->updateModel($paymentDetails);

            $cart->setDetails($paymentDetails);
            $cartStorage->updateModel($cart);
        }

        $this->payment->execute(new CaptureRequest($cart));
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        if (false == $request instanceof CaptureRequest) {
            return false;
        }

        /** @var TokenizedDetails $tokenizedDetails */
        $tokenizedDetails = $request->getModel();
        if (false == $tokenizedDetails instanceof TokenizedDetails) {
            return false;
        }
        
        if ('Acme\OtherExamplesBundle\Model\Cart' != $tokenizedDetails->getDetails()->getClass()) {
            return false;
        }
        
        return true;
    }
}