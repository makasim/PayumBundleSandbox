<?php
namespace Acme\PaymentBundle\Payum\Action;

use Acme\PaymentBundle\Entity\NotificationDetails;
use Payum\Action\ActionInterface;
use Payum\Request\SecuredNotifyRequest;
use Symfony\Bridge\Doctrine\RegistryInterface;

class StoreNotificationAction implements ActionInterface
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
     */
    public function execute($request)
    {
        /** @var SecuredNotifyRequest $request */
        
        $notification = new NotificationDetails;
        $notification->setPaymentName($request->getToken()->getPaymentName());
        $notification->setDetails($request->getNotification());
        $notification->setCreatedAt(new \DateTime);
        $this->doctrine->getManager()->persist($notification);

        $this->doctrine->getManager()->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof SecuredNotifyRequest;
    }
}