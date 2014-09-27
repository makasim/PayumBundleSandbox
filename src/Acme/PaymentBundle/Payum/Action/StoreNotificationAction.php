<?php
namespace Acme\PaymentBundle\Payum\Action;

use Acme\PaymentBundle\Entity\NotificationDetails;
use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Symfony\Bridge\Doctrine\RegistryInterface;

class StoreNotificationAction extends PaymentAwareAction
{
    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @param RegistryInterface $doctrine
     */
    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    /**
     * {@inheritDoc}
     *
     * @param Notify $request
     */
    public function execute($request)
    {
        $notification = new NotificationDetails;

        $request->getToken() ?
            $notification->setPaymentName($request->getToken()->getPaymentName()) :
            $notification->setPaymentName('unknown')
        ;

        $this->payment->execute($getHttpRequest = new GetHttpRequest);

        $notification->setDetails($getHttpRequest->query);
        $notification->setCreatedAt(new \DateTime);
        $this->doctrine->getManager()->persist($notification);

        $this->doctrine->getManager()->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof Notify;
    }
}