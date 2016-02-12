<?php
namespace Acme\OtherExamplesBundle\Payum\Action;

use Acme\OtherExamplesBundle\Model\Cart;
use Acme\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Registry\StorageRegistryInterface;
use Payum\Core\Request\Capture;

class CaptureCartWithPaypalExpressCheckoutAction extends GatewayAwareAction
{
    /**
     * @var StorageRegistryInterface
     */
    private $registry;

    /**
     * @param StorageRegistryInterface $registry
     */
    public function __construct(StorageRegistryInterface $registry)
    {
        $this->registry = $registry;
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

        $cartStorage = $this->registry->getStorage($cart);

        $paymentStorage = $this->registry->getStorage(PaymentDetails::class);

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
