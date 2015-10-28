<?php
namespace Acme\OtherExamplesBundle\Payum\Action;

use Acme\OtherExamplesBundle\Model\Cart;
use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Request\Capture;

class CaptureCartWithPaypalExpressCheckoutAction extends GatewayAwareAction
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

        $paymentStorage = $this->payum->getStorage('Acme\PaymentBundle\Entity\PaymentDetails');

        /** @var $payment PaymentDetails */
        $payment = $paymentStorage->create();
        $payment['PAYMENTREQUEST_0_CURRENCYCODE'] = $cart->getCurrency();
        $payment['PAYMENTREQUEST_0_AMT'] = $cart->getPrice();
        $payment['RETURNURL'] = $request->getToken()->getTargetUrl();
        $payment['CANCELURL'] = $request->getToken()->getTargetUrl();
        $paymentStorage->update($payment);

        $cart->setDetails($payment);
        $cartStorage->update($cart);

        $request->setModel($payment);
        $this->gateway->execute($request);
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
