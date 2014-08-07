<?php
namespace Acme\PaymentBundle\Payum\Action;

use Acme\PaymentBundle\Entity\NotificationDetails;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\Notify;
use Payum\Core\Request\SecuredNotify;
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
        /** @var Notify $request */
        
        $notification = new NotificationDetails;
        if ($request instanceof SecuredNotify) {
            $notification->setPaymentName($request->getToken()->getPaymentName());
        } else {
            $notification->setPaymentName('unknown');
        }
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
        return $request instanceof Notify;
    }
}