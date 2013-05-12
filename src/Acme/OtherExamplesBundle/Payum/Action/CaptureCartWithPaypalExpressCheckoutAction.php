<?php
namespace Acme\OtherExamplesBundle\Payum\Action;

use Acme\OtherExamplesBundle\Model\Cart;
use Payum\Action\ActionInterface;
use Payum\Action\PaymentAwareAction;
use Payum\Bundle\PayumBundle\Service\TokenizedTokenService;
use Payum\Exception\RequestNotSupportedException;
use Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails;
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

        $paymentName = 'paypal_express_checkout_cart';
        
        /** @var Cart $cart */
        $cart = $request->getModel();
        $cartStorage = $this->payum->getStorageForClass($cart, $paymentName);
        $detailsStorage = $this->payum->getStorageForClass(
            'Acme\PaypalExpressCheckoutBundle\Model\PaymentDetails',
            $paymentName
        );

        /** @var $paymentDetails PaymentDetails */
        $paymentDetails = $detailsStorage->createModel();
        $paymentDetails->setPaymentrequestCurrencycode(0, $cart->getCurrency());
        $paymentDetails->setPaymentrequestAmt(0,  $cart->getPrice());
        $detailsStorage->updateModel($paymentDetails);

        $captureToken = $this->tokenService->createTokenForCaptureRoute(
            $paymentName,
            $paymentDetails,
            'acme_payment_details_view'// TODO
        );

        $paymentDetails->setReturnurl($captureToken->getTargetUrl());
        $paymentDetails->setCancelurl($captureToken->getTargetUrl());
        $paymentDetails->setInvnum($paymentDetails->getId());

        $cart->setDetails($paymentDetails);
        
        $detailsStorage->updateModel($paymentDetails);
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
            $request instanceof CaptureRequest &&
            $request->getModel() instanceof Cart &&
            null === $request->getModel()->getDetails()
        ;
    }
}