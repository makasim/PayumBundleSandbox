<?php
namespace Acme\PaymentBundle\Payum\Action;

use Acme\PaymentBundle\Entity\NotificationDetails;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\NotifyRequest;
use Payum\Core\Request\SecuredNotifyRequest;
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
        /** @var NotifyRequest $request */
        
        $notification = new NotificationDetails;
        if ($request instanceof SecuredNotifyRequest) {
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
        return $request instanceof NotifyRequest;
    }
}