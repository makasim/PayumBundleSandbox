<?php
namespace Acme\OtherExamplesBundle\Payum\Action;

use Acme\OtherExamplesBundle\Model\Cart;
use Acme\PaymentBundle\Model\PaymentDetails;
use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Request\SecuredCaptureRequest;

class CaptureCartWithPaypalExpressCheckoutAction extends PaymentAwareAction 
{
    /**
     * @var \Payum\Core\Registry\RegistryInterface
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
            'Acme\PaymentBundle\Entity\PaymentDetails',
            $request->getToken()->getPaymentName()
        );

        /** @var $paymentDetails PaymentDetails */
        $paymentDetails = $paymentDetailsStorage->createModel();
        $paymentDetails['PAYMENTREQUEST_0_CURRENCYCODE'] = $cart->getCurrency();
        $paymentDetails['PAYMENTREQUEST_0_AMT'] = $cart->getPrice();
        $paymentDetails['RETURNURL'] = $request->getToken()->getTargetUrl();
        $paymentDetails['CANCELURL'] = $request->getToken()->getTargetUrl();
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