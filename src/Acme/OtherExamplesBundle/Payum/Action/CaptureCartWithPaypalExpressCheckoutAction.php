<?php
namespace Acme\OtherExamplesBundle\Payum\Action;

use Acme\OtherExamplesBundle\Model\Cart;
use Acme\PaymentBundle\Model\PaymentDetails;
use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Request\Capture;

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
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        /** @var Cart $cart */
        $cart = $request->getModel();

        $cartStorage = $this->payum->getStorage($cart);

        $paymentDetailsStorage = $this->payum->getStorage('Acme\PaymentBundle\Model\PaymentDetails');

        /** @var $paymentDetails PaymentDetails */
        $paymentDetails = $paymentDetailsStorage->create();
        $paymentDetails['PAYMENTREQUEST_0_CURRENCYCODE'] = $cart->getCurrency();
        $paymentDetails['PAYMENTREQUEST_0_AMT'] = $cart->getPrice();
        $paymentDetails['RETURNURL'] = $request->getToken()->getTargetUrl();
        $paymentDetails['CANCELURL'] = $request->getToken()->getTargetUrl();
        $paymentDetailsStorage->update($paymentDetails);

        $cart->setDetails($paymentDetails);
        $cartStorage->update($cart);

        $request->setModel($paymentDetails);
        $this->payment->execute($request);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getToken() &&
            $request->getModel() instanceof Cart &&
            null === $request->getModel()->getDetails()
        ;
    }
}
